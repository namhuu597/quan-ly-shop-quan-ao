using System.Security.Claims;
using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Mvc;
using Microsoft.EntityFrameworkCore;
using QuanLyQuanAoApi.Data;
using QuanLyQuanAoApi.Models;

namespace QuanLyQuanAoApi.Controllers;

[ApiController]
[Route("api/[controller]")]
[Authorize]
public class InventoryController : ControllerBase
{
    private readonly AppDbContext _context;

    public InventoryController(AppDbContext context)
    {
        _context = context;
    }


    // =========================================
    // JWT / PERMISSION HELPERS
    // =========================================

    private bool HasPermission(string permission)
    {
        return User
            .FindAll("permission")
            .Any(c =>
                string.Equals(
                    c.Value,
                    permission,
                    StringComparison.Ordinal
                )
            );
    }


    private ulong CurrentUserId()
    {
        var value =
            User.FindFirst(
                ClaimTypes.NameIdentifier
            )?.Value;

        return ulong.TryParse(
            value,
            out var id
        )
            ? id
            : 0;
    }


    // =========================================
    // GET: api/inventory
    // DANH SÁCH TỒN KHO + THỐNG KÊ
    // =========================================
    [HttpGet]
    public async Task<IActionResult> GetInventory(
        [FromQuery] string? keyword,
        [FromQuery] string? stockStatus
    )
    {
        if (!HasPermission("inventory.read"))
        {
            return Forbid();
        }

        var query =
            from pv in _context.ProductVariants
            join p in _context.Products
                on pv.ProductId equals p.Id
            join s in _context.Sizes
                on pv.SizeId equals s.Id
            join c in _context.Colors
                on pv.ColorId equals c.Id
            select new
            {
                VariantId = pv.Id,
                pv.Sku,
                pv.StockQuantity,
                pv.IsActive,

                ProductCode = p.ProductCode,
                ProductName = p.Name,

                SizeName = s.Name,

                ColorName = c.Name,
                ColorCode = c.ColorCode
            };


        if (!string.IsNullOrWhiteSpace(keyword))
        {
            keyword = keyword.Trim();

            query = query.Where(v =>
                v.ProductCode.Contains(keyword)
                || v.ProductName.Contains(keyword)
                || v.Sku.Contains(keyword)
                || v.SizeName.Contains(keyword)
                || v.ColorName.Contains(keyword)
            );
        }


        if (!string.IsNullOrWhiteSpace(stockStatus))
        {
            stockStatus = stockStatus.Trim().ToLower();

            if (stockStatus == "out")
            {
                query = query.Where(v =>
                    v.StockQuantity == 0
                );
            }
            else if (stockStatus == "low")
            {
                query = query.Where(v =>
                    v.StockQuantity > 0
                    && v.StockQuantity <= 5
                );
            }
            else if (stockStatus == "available")
            {
                query = query.Where(v =>
                    v.StockQuantity > 5
                );
            }
            else
            {
                return BadRequest(new
                {
                    message =
                        "Trạng thái tồn kho không hợp lệ."
                });
            }
        }


        var variants =
            await query
                .OrderBy(v => v.ProductName)
                .ThenBy(v => v.SizeName)
                .ThenBy(v => v.ColorName)
                .ToListAsync();


        var activeVariants =
            _context.ProductVariants
                .Where(v =>
                    v.IsActive == true
                );


        var totalVariants =
            await activeVariants
                .CountAsync();


        var totalStock =
            await activeVariants
                .SumAsync(v =>
                    (int?)v.StockQuantity
                )
            ?? 0;


        var outStock =
            await activeVariants
                .CountAsync(v =>
                    v.StockQuantity == 0
                );


        var lowStock =
            await activeVariants
                .CountAsync(v =>
                    v.StockQuantity > 0
                    && v.StockQuantity <= 5
                );


        return Ok(new
        {
            stats = new
            {
                totalVariants,
                totalStock,
                outStock,
                lowStock
            },

            variants
        });
    }


    // =========================================
    // GET: api/inventory/import-options
    // BIẾN THỂ CHO FORM NHẬP KHO
    // =========================================
    [HttpGet("import-options")]
    public async Task<IActionResult> GetImportOptions()
    {
        if (!HasPermission("inventory.import"))
        {
            return Forbid();
        }

        var variants =
            await (
                from pv in _context.ProductVariants
                join p in _context.Products
                    on pv.ProductId equals p.Id
                join s in _context.Sizes
                    on pv.SizeId equals s.Id
                join c in _context.Colors
                    on pv.ColorId equals c.Id

                where
                    pv.IsActive == true
                    && p.Status == "ACTIVE"

                orderby
                    p.Name,
                    s.Name,
                    c.Name

                select new
                {
                    VariantId = pv.Id,
                    pv.Sku,
                    pv.StockQuantity,

                    ProductCode = p.ProductCode,
                    ProductName = p.Name,

                    SizeName = s.Name,
                    ColorName = c.Name
                }
            )
            .ToListAsync();


        return Ok(variants);
    }


    // =========================================
    // POST: api/inventory/import
    // NHẬP KHO + GHI LỊCH SỬ
    // =========================================
    [HttpPost("import")]
    public async Task<IActionResult> ImportStock(
        [FromBody] InventoryImportRequest request
    )
    {
        if (!HasPermission("inventory.import"))
        {
            return Forbid();
        }

        if (request.VariantId == 0)
        {
            return BadRequest(new
            {
                message =
                    "Vui lòng chọn sản phẩm."
            });
        }


        if (request.Quantity <= 0)
        {
            return BadRequest(new
            {
                message =
                    "Số lượng nhập phải lớn hơn 0."
            });
        }


        var reason =
            request.Reason?
                .Trim();

        if (string.IsNullOrWhiteSpace(reason))
        {
            return BadRequest(new
            {
                message =
                    "Vui lòng nhập lý do nhập kho."
            });
        }


        await using var transaction =
            await _context.Database
                .BeginTransactionAsync();


        try
        {
            var variant =
                await _context.ProductVariants
                    .FirstOrDefaultAsync(v =>
                        v.Id == request.VariantId
                        && v.IsActive == true
                    );


            if (variant == null)
            {
                return NotFound(new
                {
                    message =
                        "Sản phẩm không tồn tại hoặc đã ngừng sử dụng."
                });
            }


            var productActive =
                await _context.Products
                    .AnyAsync(p =>
                        p.Id == variant.ProductId
                        && p.Status == "ACTIVE"
                    );


            if (!productActive)
            {
                return BadRequest(new
                {
                    message =
                        "Sản phẩm không tồn tại hoặc đã ngừng sử dụng."
                });
            }


            var stockBefore =
                variant.StockQuantity;

            var stockAfter =
                stockBefore
                + request.Quantity;


            variant.StockQuantity =
                stockAfter;


            _context.InventoryTransactions.Add(
                new InventoryTransaction
                {
                    ProductVariantId =
                        request.VariantId,

                    Type =
                        "IMPORT",

                    Quantity =
                        request.Quantity,

                    StockBefore =
                        stockBefore,

                    StockAfter =
                        stockAfter,

                    OrderId =
                        null,

                    CreatedBy =
                        CurrentUserId(),

                    Reason =
                        reason,

                    CreatedAt =
                        DateTime.Now
                }
            );


            await _context.SaveChangesAsync();

            await transaction.CommitAsync();


            return Ok(new
            {
                message =
                    "Nhập kho thành công.",

                variantId =
                    request.VariantId,

                stockBefore,
                stockAfter
            });
        }
        catch (Exception ex)
        {
            await transaction.RollbackAsync();

            return StatusCode(
                500,
                new
                {
                    message =
                        "Không thể nhập kho.",

                    detail =
                        ex.Message
                }
            );
        }
    }

    // =========================================
    // GET: api/inventory/variant/1
    // LẤY THÔNG TIN BIẾN THỂ ĐỂ ĐIỀU CHỈNH
    // =========================================
    [HttpGet("variant/{variantId}")]
    public async Task<IActionResult> GetVariantForAdjustment(
        ulong variantId
    )
    {
        if (!HasPermission("inventory.adjust"))
        {
            return Forbid();
        }

        var variant =
            await (
                from pv in _context.ProductVariants
                join p in _context.Products
                    on pv.ProductId equals p.Id
                join s in _context.Sizes
                    on pv.SizeId equals s.Id
                join c in _context.Colors
                    on pv.ColorId equals c.Id

                where
                    pv.Id == variantId
                    && pv.IsActive == true
                    && p.Status == "ACTIVE"

                select new
                {
                    VariantId =
                        pv.Id,

                    pv.Sku,
                    pv.StockQuantity,

                    ProductCode =
                        p.ProductCode,

                    ProductName =
                        p.Name,

                    SizeName =
                        s.Name,

                    ColorName =
                        c.Name
                }
            )
            .FirstOrDefaultAsync();


        if (variant == null)
        {
            return NotFound(new
            {
                message =
                    "Không tìm thấy sản phẩm hoặc biến thể đã ngừng sử dụng."
            });
        }


        return Ok(variant);
    }


    // =========================================
    // POST: api/inventory/adjust
    // ĐIỀU CHỈNH TỒN KHO + GHI LỊCH SỬ
    // =========================================
    [HttpPost("adjust")]
    public async Task<IActionResult> AdjustStock(
        [FromBody] InventoryAdjustRequest request
    )
    {
        if (!HasPermission("inventory.adjust"))
        {
            return Forbid();
        }

        if (request.VariantId == 0)
        {
            return BadRequest(new
            {
                message =
                    "Biến thể sản phẩm không hợp lệ."
            });
        }


        if (request.NewStock < 0)
        {
            return BadRequest(new
            {
                message =
                    "Tồn kho mới không được nhỏ hơn 0."
            });
        }


        var reason =
            request.Reason?
                .Trim();

        if (string.IsNullOrWhiteSpace(reason))
        {
            return BadRequest(new
            {
                message =
                    "Vui lòng nhập lý do điều chỉnh."
            });
        }


        await using var transaction =
            await _context.Database
                .BeginTransactionAsync();


        try
        {
            var variant =
                await _context.ProductVariants
                    .FirstOrDefaultAsync(v =>
                        v.Id == request.VariantId
                        && v.IsActive == true
                    );


            if (variant == null)
            {
                return NotFound(new
                {
                    message =
                        "Không tìm thấy biến thể sản phẩm hoặc biến thể đã ngừng sử dụng."
                });
            }


            var productActive =
                await _context.Products
                    .AnyAsync(p =>
                        p.Id == variant.ProductId
                        && p.Status == "ACTIVE"
                    );


            if (!productActive)
            {
                return BadRequest(new
                {
                    message =
                        "Không tìm thấy sản phẩm hoặc sản phẩm đã ngừng sử dụng."
                });
            }


            var stockBefore =
                variant.StockQuantity;

            var stockAfter =
                request.NewStock;


            if (stockBefore == stockAfter)
            {
                return Conflict(new
                {
                    message =
                        "Tồn kho mới giống tồn kho hiện tại."
                });
            }


            var difference =
                stockAfter
                - stockBefore;


            variant.StockQuantity =
                stockAfter;


            _context.InventoryTransactions.Add(
                new InventoryTransaction
                {
                    ProductVariantId =
                        request.VariantId,

                    Type =
                        "ADJUSTMENT",

                    Quantity =
                        difference,

                    StockBefore =
                        stockBefore,

                    StockAfter =
                        stockAfter,

                    OrderId =
                        null,

                    CreatedBy =
                        CurrentUserId(),

                    Reason =
                        reason,

                    CreatedAt =
                        DateTime.Now
                }
            );


            await _context.SaveChangesAsync();

            await transaction.CommitAsync();


            return Ok(new
            {
                message =
                    "Điều chỉnh tồn kho thành công.",

                variantId =
                    request.VariantId,

                stockBefore,
                stockAfter,
                difference
            });
        }
        catch (Exception ex)
        {
            await transaction.RollbackAsync();

            return StatusCode(
                500,
                new
                {
                    message =
                        "Không thể điều chỉnh tồn kho.",

                    detail =
                        ex.Message
                }
            );
        }
    }


    // =========================================
    // GET: api/inventory/history
    // LỊCH SỬ GIAO DỊCH KHO + THỐNG KÊ
    // =========================================
    [HttpGet("history")]
    public async Task<IActionResult> GetInventoryHistory(
        [FromQuery] string? keyword,
        [FromQuery] string? type,
        [FromQuery] ulong? variantId
    )
    {
        if (!HasPermission("inventory.read"))
        {
            return Forbid();
        }

        var query =
            from it in _context.InventoryTransactions

            join pv in _context.ProductVariants
                on it.ProductVariantId equals pv.Id

            join p in _context.Products
                on pv.ProductId equals p.Id

            join s in _context.Sizes
                on pv.SizeId equals s.Id

            join c in _context.Colors
                on pv.ColorId equals c.Id

            join u in _context.Users
                on (ulong)it.CreatedBy equals u.Id

            join o0 in _context.Orders
                on it.OrderId equals o0.Id
                into orderGroup

            from o in orderGroup.DefaultIfEmpty()

            select new
            {
                it.Id,
                it.Type,
                it.Quantity,
                it.StockBefore,
                it.StockAfter,
                it.Reason,
                it.CreatedAt,

                VariantId =
                    pv.Id,

                pv.Sku,

                ProductCode =
                    p.ProductCode,

                ProductName =
                    p.Name,

                SizeName =
                    s.Name,

                ColorName =
                    c.Name,

                ColorCode =
                    c.ColorCode,

                EmployeeName =
                    u.FullName,

                OrderId =
                    o != null
                        ? (ulong?)o.Id
                        : null,

                OrderCode =
                    o != null
                        ? o.OrderCode
                        : null
            };


        if (
            variantId.HasValue
            && variantId.Value > 0
        )
        {
            query =
                query.Where(t =>
                    t.VariantId
                    == variantId.Value
                );
        }


        if (!string.IsNullOrWhiteSpace(keyword))
        {
            keyword =
                keyword.Trim();

            query =
                query.Where(t =>
                    t.ProductCode.Contains(keyword)
                    || t.ProductName.Contains(keyword)
                    || t.Sku.Contains(keyword)
                    || t.EmployeeName.Contains(keyword)
                    || (
                        t.OrderCode != null
                        && t.OrderCode.Contains(keyword)
                    )
                    || (
                        t.Reason != null
                        && t.Reason.Contains(keyword)
                    )
                );
        }


        if (!string.IsNullOrWhiteSpace(type))
        {
            type =
                type.Trim().ToUpper();

            if (
                type != "SALE"
                && type != "IMPORT"
                && type != "ADJUSTMENT"
            )
            {
                return BadRequest(new
                {
                    message =
                        "Loại giao dịch kho không hợp lệ."
                });
            }

            query =
                query.Where(t =>
                    t.Type == type
                );
        }


        var transactions =
            await query
                .OrderByDescending(t =>
                    t.CreatedAt
                )
                .ThenByDescending(t =>
                    t.Id
                )
                .ToListAsync();


        var statsQuery =
            _context.InventoryTransactions
                .AsQueryable();


        var totalTransactions =
            await statsQuery.CountAsync();


        var totalImport =
            await statsQuery
                .Where(t =>
                    t.Type == "IMPORT"
                )
                .SumAsync(t =>
                    (int?)t.Quantity
                )
            ?? 0;


        var totalSale =
            await statsQuery
                .Where(t =>
                    t.Type == "SALE"
                )
                .SumAsync(t =>
                    (int?)t.Quantity
                )
            ?? 0;


        var totalAdjustment =
            await statsQuery
                .CountAsync(t =>
                    t.Type == "ADJUSTMENT"
                );


        return Ok(new
        {
            stats = new
            {
                totalTransactions,
                totalImport,
                totalSale,
                totalAdjustment
            },

            transactions
        });
    }

}


// =========================================
// DTO
// =========================================

public class InventoryImportRequest
{
    public ulong VariantId { get; set; }

    public int Quantity { get; set; }

    public string? Reason { get; set; }

}
public class InventoryAdjustRequest
{
    public ulong VariantId { get; set; }

    public int NewStock { get; set; }

    public string? Reason { get; set; }

}
