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
public class OrdersController : ControllerBase
{
    private readonly AppDbContext _context;

    public OrdersController(AppDbContext context)
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


    private bool CanReadAllOrders()
    {
        return HasPermission(
            "order.read"
        );
    }


    private bool CanReadOwnOrders()
    {
        return HasPermission(
            "order.read.own"
        );
    }


    private bool CanAccessOrders()
    {
        return CanReadAllOrders()
            || CanReadOwnOrders();
    }


    // =========================================
    // GET: api/orders
    // DANH SÁCH ĐƠN HÀNG
    // =========================================
    [HttpGet]
    public async Task<IActionResult> GetOrders(
        [FromQuery] string? keyword,
        [FromQuery] string? status
    )
    {
        if (!CanAccessOrders())
        {
            return Forbid();
        }

        var currentUserId =
            CurrentUserId();
        var query =
            from o in _context.Orders

            join u in _context.Users
                on o.CreatedBy equals u.Id

            join c0 in _context.Customers
                on o.CustomerId equals c0.Id
                into customerGroup

            from c in customerGroup.DefaultIfEmpty()

            select new
            {
                o.Id,
                o.OrderCode,
                o.Status,
                o.TotalAmount,
                o.DepositAmount,
                o.DepositStatus,
                o.CreatedAt,
                o.CreatedBy,

                CustomerName =
                    c != null
                        ? c.FullName
                        : null,

                EmployeeName =
                    u.FullName
            };
        if (
            !CanReadAllOrders()
            && CanReadOwnOrders()
        )
        {
            query =
                query.Where(o =>
                    o.CreatedBy
                    == currentUserId
                );
        }



        if (!string.IsNullOrWhiteSpace(keyword))
        {
            keyword =
                keyword.Trim();

            query =
                query.Where(o =>
                    o.OrderCode.Contains(keyword)
                    || (
                        o.CustomerName != null
                        && o.CustomerName.Contains(keyword)
                    )
                    || o.EmployeeName.Contains(keyword)
                );
        }


        if (!string.IsNullOrWhiteSpace(status))
        {
            status =
                status.Trim().ToUpper();

            if (
                status != "PENDING"
                && status != "COMPLETED"
                && status != "CANCELLED"
            )
            {
                return BadRequest(new
                {
                    message =
                        "Trạng thái đơn hàng không hợp lệ."
                });
            }

            query =
                query.Where(o =>
                    o.Status == status
                );
        }


        var orders =
            await query
                .OrderByDescending(o =>
                    o.CreatedAt
                )
                .Select(o => new
                {
                    o.Id,
                    o.OrderCode,
                    o.Status,
                    o.TotalAmount,
                    o.DepositAmount,
                    o.DepositStatus,
                    o.CreatedAt,
                    o.CustomerName,
                    o.EmployeeName
                })
                .ToListAsync();


        return Ok(orders);
    }


    // =========================================
    // GET: api/orders/1
    // CHI TIẾT ĐƠN HÀNG
    // =========================================
    [HttpGet("{id}")]
    public async Task<IActionResult> GetOrder(
        ulong id
    )
    {
        if (!CanAccessOrders())
        {
            return Forbid();
        }

        var currentUserId =
            CurrentUserId();
        var orderQuery =
            from o in _context.Orders

            join u in _context.Users
                on o.CreatedBy equals u.Id

            join c0 in _context.Customers
                on o.CustomerId equals c0.Id
                into customerGroup

            from c in customerGroup.DefaultIfEmpty()

            where o.Id == id

            select new
            {
                o.Id,
                o.OrderCode,
                o.CustomerId,
                o.CreatedBy,
                o.Status,
                o.TotalAmount,
                o.DepositAmount,
                o.DepositStatus,
                o.CancelledBy,
                o.Note,
                o.CreatedAt,
                o.CompletedAt,
                o.CancelledAt,

                CustomerCode =
                    c != null
                        ? c.CustomerCode
                        : null,

                CustomerName =
                    c != null
                        ? c.FullName
                        : null,

                CustomerPhone =
                    c != null
                        ? c.Phone
                        : null,

                CustomerEmail =
                    c != null
                        ? c.Email
                        : null,

                CustomerAddress =
                    c != null
                        ? c.Address
                        : null,

                EmployeeName =
                    u.FullName
            };
        if (
            !CanReadAllOrders()
            && CanReadOwnOrders()
        )
        {
            orderQuery =
                orderQuery.Where(o =>
                    o.CreatedBy
                    == currentUserId
                );
        }



        var order =
            await orderQuery
                .FirstOrDefaultAsync();


        if (order == null)
        {
            return NotFound(new
            {
                message =
                    "Không tìm thấy đơn hàng hoặc bạn không có quyền xem đơn này."
            });
        }


        var details =
            await (
                from od in _context.OrderDetails

                join pv in _context.ProductVariants
                    on od.ProductVariantId equals pv.Id

                join p in _context.Products
                    on pv.ProductId equals p.Id

                join s in _context.Sizes
                    on pv.SizeId equals s.Id

                join c in _context.Colors
                    on pv.ColorId equals c.Id

                where od.OrderId == id

                orderby od.Id

                select new
                {
                    od.Id,
                    od.Quantity,
                    od.UnitPrice,
                    od.Subtotal,

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
                        c.ColorCode
                }
            )
            .ToListAsync();


        return Ok(new
        {
            order,
            details
        });
    }


    // =========================================
    // PATCH: api/orders/1/complete
    // HOÀN THÀNH ĐƠN
    // =========================================
    [HttpPatch("{id}/complete")]
    public async Task<IActionResult> CompleteOrder(
        ulong id,
        [FromBody] CompleteOrderRequest request
    )
    {
        if (!CanAccessOrders())
        {
            return Forbid();
        }

        var currentUserId =
            CurrentUserId();

        await using var transaction =
            await _context.Database
                .BeginTransactionAsync();

        try
        {
            var order =
                await _context.Orders
                    .FirstOrDefaultAsync(o =>
                        o.Id == id
                    );


            if (order == null)
            {
                return NotFound(new
                {
                    message =
                        "Không tìm thấy đơn hàng."
                });
            }
            if (
                !CanReadAllOrders()
                && CanReadOwnOrders()
                && order.CreatedBy != currentUserId
            )
            {
                return Forbid();
            }



            if (order.Status != "PENDING")
            {
                return Conflict(new
                {
                    message =
                        "Chỉ có thể hoàn thành đơn hàng đang ở trạng thái Chưa hoàn thành."
                });
            }


            order.Status =
                "COMPLETED";

            order.DepositStatus =
                "APPLIED";

            order.CompletedAt =
                DateTime.Now;

            order.CancelledAt =
                null;

            order.CancelledBy =
                null;


            _context.OrderStatusHistories.Add(
                new OrderStatusHistory
                {
                    OrderId =
                        (int)id,

                    FromStatus =
                        "PENDING",

                    ToStatus =
                        "COMPLETED",

                    Reason =
                        string.IsNullOrWhiteSpace(
                            request.Reason
                        )
                            ? null
                            : request.Reason.Trim(),

                    ChangedBy =
                        (int)currentUserId,

                    CreatedAt =
                        DateTime.Now
                }
            );


            await _context.SaveChangesAsync();

            await transaction.CommitAsync();


            return Ok(new
            {
                message =
                    "Hoàn thành đơn hàng thành công."
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
                        "Không thể hoàn thành đơn hàng.",

                    detail =
                        ex.Message
                }
            );
        }
    }


    // =========================================
    // PATCH: api/orders/1/cancel
    // HỦY / KHÔNG HOÀN THÀNH ĐƠN
    // =========================================
    [HttpPatch("{id}/cancel")]
    public async Task<IActionResult> CancelOrder(
        ulong id,
        [FromBody] CancelOrderRequest request
    )
    {
        if (!CanAccessOrders())
        {
            return Forbid();
        }

        var currentUserId =
            CurrentUserId();

        var cancelledBy =
            request.CancelledBy?
                .Trim()
                .ToUpper();

        var reason =
            request.Reason?
                .Trim();


        if (
            cancelledBy != "CUSTOMER"
            && cancelledBy != "SHOP"
        )
        {
            return BadRequest(new
            {
                message =
                    "Vui lòng chọn bên không hoàn thành đơn."
            });
        }


        if (string.IsNullOrWhiteSpace(reason))
        {
            return BadRequest(new
            {
                message =
                    "Vui lòng nhập lý do không hoàn thành đơn."
            });
        }


        await using var transaction =
            await _context.Database
                .BeginTransactionAsync();

        try
        {
            var order =
                await _context.Orders
                    .FirstOrDefaultAsync(o =>
                        o.Id == id
                    );


            if (order == null)
            {
                return NotFound(new
                {
                    message =
                        "Không tìm thấy đơn hàng."
                });
            }
            if (
                !CanReadAllOrders()
                && CanReadOwnOrders()
                && order.CreatedBy != currentUserId
            )
            {
                return Forbid();
            }



            if (order.Status != "PENDING")
            {
                return Conflict(new
                {
                    message =
                        "Chỉ có thể xử lý đơn hàng đang ở trạng thái Chưa hoàn thành."
                });
            }


            var details =
                await _context.OrderDetails
                    .Where(od =>
                        od.OrderId == id
                    )
                    .OrderBy(od =>
                        od.Id
                    )
                    .ToListAsync();


            foreach (var detail in details)
            {
                var variant =
                    await _context.ProductVariants
                        .FirstOrDefaultAsync(v =>
                            v.Id
                            == detail.ProductVariantId
                        );


                if (variant == null)
                {
                    throw new Exception(
                        "Không tìm thấy biến thể sản phẩm khi hoàn kho."
                    );
                }


                var stockBefore =
                    variant.StockQuantity;

                var stockAfter =
                    stockBefore
                    + detail.Quantity;


                variant.StockQuantity =
                    stockAfter;


                var inventoryReason =
                    cancelledBy == "CUSTOMER"
                        ? "Hoàn kho - khách không hoàn thành đơn "
                            + order.OrderCode
                            + ". Lý do: "
                            + reason
                        : "Hoàn kho - shop không hoàn thành đơn "
                            + order.OrderCode
                            + ". Lý do: "
                            + reason;


                _context.InventoryTransactions.Add(
                    new InventoryTransaction
                    {
                        ProductVariantId =
                            detail.ProductVariantId,

                        Type =
                            "ADJUSTMENT",

                        Quantity =
                            detail.Quantity,

                        StockBefore =
                            stockBefore,

                        StockAfter =
                            stockAfter,

                        OrderId =
                            id,

                        CreatedBy =
                            currentUserId,

                        Reason =
                            inventoryReason,

                        CreatedAt =
                            DateTime.Now
                    }
                );
            }


            order.Status =
                "CANCELLED";

            order.DepositStatus =
                cancelledBy == "CUSTOMER"
                    ? "FORFEITED"
                    : "REFUNDED";

            order.CancelledAt =
                DateTime.Now;

            order.CancelledBy =
                cancelledBy;

            order.CompletedAt =
                null;


            var historyReason =
                cancelledBy == "CUSTOMER"
                    ? "Khách hàng không hoàn thành đơn. "
                        + reason
                    : "Shop không hoàn thành đơn. "
                        + reason;


            _context.OrderStatusHistories.Add(
                new OrderStatusHistory
                {
                    OrderId =
                        (int)id,

                    FromStatus =
                        "PENDING",

                    ToStatus =
                        "CANCELLED",

                    Reason =
                        historyReason,

                    ChangedBy =
                        (int)currentUserId,

                    CreatedAt =
                        DateTime.Now
                }
            );


            await _context.SaveChangesAsync();

            await transaction.CommitAsync();


            return Ok(new
            {
                message =
                    "Đơn hàng đã chuyển sang Không hoàn thành và tồn kho đã được hoàn lại."
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
                        "Không thể chuyển đơn hàng sang Không hoàn thành.",

                    detail =
                        ex.Message
                }
            );
        }
    }

    // =========================================
    // GET: api/orders/form-options
    // DỮ LIỆU CHO FORM TẠO ĐƠN
    // =========================================
    [HttpGet("form-options")]
    public async Task<IActionResult> GetOrderFormOptions()
    {
        if (!HasPermission("order.create"))
        {
            return Forbid();
        }

        var customers =
            await _context.Customers
                .Where(c =>
                    c.IsActive == true
                )
                .OrderBy(c =>
                    c.FullName
                )
                .Select(c => new
                {
                    c.Id,
                    c.CustomerCode,
                    c.FullName,
                    c.Phone,
                    c.Email,
                    c.Address
                })
                .ToListAsync();


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
                    && pv.StockQuantity > 0

                orderby
                    p.Name,
                    s.Name,
                    c.Name

                select new
                {
                    VariantId =
                        pv.Id,

                    pv.Sku,

                    VariantPrice =
                        pv.Price,

                    pv.StockQuantity,

                    ProductId =
                        p.Id,

                    ProductCode =
                        p.ProductCode,

                    ProductName =
                        p.Name,

                    BasePrice =
                        p.BasePrice,

                    SizeName =
                        s.Name,

                    ColorName =
                        c.Name
                }
            )
            .ToListAsync();


        return Ok(new
        {
            customers,
            variants
        });
    }


    // =========================================
    // POST: api/orders
    // TẠO ĐƠN + CHI TIẾT + TRỪ TỒN KHO
    // =========================================
    [HttpPost]
    public async Task<IActionResult> CreateOrder(
        [FromBody] CreateOrderRequest request
    )
    {
        if (!HasPermission("order.create"))
        {
            return Forbid();
        }

        var currentUserId =
            CurrentUserId();

        if (
            request.Items == null
            || request.Items.Count == 0
        )
        {
            return BadRequest(new
            {
                message =
                    "Vui lòng chọn ít nhất một sản phẩm."
            });
        }


        if (
            !request.CustomerId.HasValue
            || request.CustomerId.Value == 0
        )
        {
            if (
                string.IsNullOrWhiteSpace(
                    request.GuestName
                )
            )
            {
                return BadRequest(new
                {
                    message =
                        "Vui lòng nhập họ tên khách vãng lai."
                });
            }
        }


        if (request.DepositAmount < 0)
        {
            return BadRequest(new
            {
                message =
                    "Tiền cọc không được nhỏ hơn 0."
            });
        }


        if (
            request.CustomerId.HasValue
            && request.CustomerId.Value > 0
        )
        {
            var customerExists =
                await _context.Customers
                    .AnyAsync(c =>
                        c.Id
                            == request.CustomerId.Value
                        && c.IsActive == true
                    );


            if (!customerExists)
            {
                return BadRequest(new
                {
                    message =
                        "Khách hàng không tồn tại hoặc đã ngừng hoạt động."
                });
            }
        }


        var requestedItems =
            request.Items
                .Where(i =>
                    i.VariantId > 0
                )
                .GroupBy(i =>
                    i.VariantId
                )
                .Select(g => new
                {
                    VariantId =
                        g.Key,

                    Quantity =
                        g.Sum(x =>
                            x.Quantity
                        )
                })
                .ToList();


        if (requestedItems.Count == 0)
        {
            return BadRequest(new
            {
                message =
                    "Vui lòng chọn ít nhất một sản phẩm."
            });
        }


        if (
            requestedItems.Any(i =>
                i.Quantity <= 0
            )
        )
        {
            return BadRequest(new
            {
                message =
                    "Số lượng sản phẩm phải lớn hơn 0."
            });
        }


        await using var transaction =
            await _context.Database
                .BeginTransactionAsync();


        try
        {
            var preparedItems =
                new List<PreparedOrderItem>();

            decimal totalAmount =
                0;


            foreach (var requested in requestedItems)
            {
                var variant =
                    await _context.ProductVariants
                        .FirstOrDefaultAsync(v =>
                            v.Id
                                == requested.VariantId
                            && v.IsActive == true
                        );


                if (variant == null)
                {
                    throw new Exception(
                        "Có sản phẩm/biến thể không còn tồn tại hoặc đã ngừng sử dụng."
                    );
                }


                var product =
                    await _context.Products
                        .FirstOrDefaultAsync(p =>
                            p.Id
                                == variant.ProductId
                        );


                if (
                    product == null
                    || product.Status != "ACTIVE"
                )
                {
                    throw new Exception(
                        "Có sản phẩm hiện không còn hoạt động."
                    );
                }


                var stockBefore =
                    variant.StockQuantity;


                if (
                    requested.Quantity
                    > stockBefore
                )
                {
                    throw new Exception(
                        "Sản phẩm \""
                        + product.Name
                        + "\" (SKU: "
                        + variant.Sku
                        + ") chỉ còn "
                        + stockBefore
                        + " sản phẩm trong kho."
                    );
                }


                var unitPrice =
                    variant.Price
                    ?? product.BasePrice;


                var subtotal =
                    unitPrice
                    * requested.Quantity;


                preparedItems.Add(
                    new PreparedOrderItem
                    {
                        Variant =
                            variant,

                        Quantity =
                            requested.Quantity,

                        UnitPrice =
                            unitPrice,

                        Subtotal =
                            subtotal,

                        StockBefore =
                            stockBefore,

                        StockAfter =
                            stockBefore
                            - requested.Quantity
                    }
                );


                totalAmount +=
                    subtotal;
            }


            if (
                request.DepositAmount
                > totalAmount
            )
            {
                return BadRequest(new
                {
                    message =
                        "Tiền cọc không được lớn hơn tổng giá trị đơn hàng."
                });
            }


            var lastId =
                await _context.Orders
                    .MaxAsync(o =>
                        (ulong?)o.Id
                    )
                ?? 0;


            var orderCode =
                "DH"
                + (lastId + 1)
                    .ToString("D4");


            var order =
                new Order
                {
                    OrderCode =
                        orderCode,

                    CustomerId =
                        request.CustomerId.HasValue
                        && request.CustomerId.Value > 0
                            ? request.CustomerId
                            : null,

                    GuestName =
                        request.CustomerId.HasValue
                        && request.CustomerId.Value > 0
                            ? null
                            : request.GuestName?.Trim(),

                    GuestPhone =
                        request.CustomerId.HasValue
                        && request.CustomerId.Value > 0
                            ? null
                            : (
                                string.IsNullOrWhiteSpace(
                                    request.GuestPhone
                                )
                                    ? null
                                    : request.GuestPhone.Trim()
                            ),

                    CreatedBy =
                        currentUserId,

                    Status =
                        "PENDING",

                    TotalAmount =
                        totalAmount,

                    DepositAmount =
                        request.DepositAmount,

                    DepositStatus =
                        "PENDING",

                    Note =
                        string.IsNullOrWhiteSpace(
                            request.Note
                        )
                            ? null
                            : request.Note.Trim(),

                    CompletedAt =
                        null,

                    CancelledAt =
                        null,

                    CancelledBy =
                        null,

                    CreatedAt =
                        DateTime.Now
                };


            _context.Orders.Add(
                order
            );

            await _context.SaveChangesAsync();


            foreach (var item in preparedItems)
            {
                _context.OrderDetails.Add(
                    new OrderDetail
                    {
                        OrderId =
                            order.Id,

                        ProductVariantId =
                            item.Variant.Id,

                        Quantity =
                            item.Quantity,

                        UnitPrice =
                            item.UnitPrice,

                        Subtotal =
                            item.Subtotal
                    }
                );


                item.Variant.StockQuantity =
                    item.StockAfter;


                _context.InventoryTransactions.Add(
                    new InventoryTransaction
                    {
                        ProductVariantId =
                            item.Variant.Id,

                        Type =
                            "SALE",

                        Quantity =
                            item.Quantity,

                        StockBefore =
                            item.StockBefore,

                        StockAfter =
                            item.StockAfter,

                        OrderId =
                            order.Id,

                        CreatedBy =
                            currentUserId,

                        Reason =
                            "Giữ hàng cho đơn chờ hoàn thành - "
                            + orderCode,

                        CreatedAt =
                            DateTime.Now
                    }
                );
            }


            await _context.SaveChangesAsync();

            await transaction.CommitAsync();


            return CreatedAtAction(
                nameof(GetOrder),
                new
                {
                    id =
                        order.Id
                },
                new
                {
                    message =
                        "Tạo đơn hàng thành công.",

                    id =
                        order.Id,

                    orderCode =
                        order.OrderCode
                }
            );
        }
        catch (Exception ex)
        {
            await transaction.RollbackAsync();

            return StatusCode(
                500,
                new
                {
                    message =
                        "Không thể tạo đơn hàng.",

                    detail =
                        ex.Message
                }
            );
        }
    }


    // =========================================
    // GET: api/orders/history
    // LỊCH SỬ TẤT CẢ ĐƠN HÀNG
    // =========================================
    [HttpGet("history")]
    public async Task<IActionResult> GetOrderHistory(
        [FromQuery] string? keyword,
        [FromQuery] string? status
    )
    {
        if (!CanAccessOrders())
        {
            return Forbid();
        }

        var currentUserId =
            CurrentUserId();

        var query =
            from h in _context.OrderStatusHistories

            join o in _context.Orders
                on (ulong)h.OrderId equals o.Id

            join creator in _context.Users
                on o.CreatedBy equals creator.Id

            join changer in _context.Users
                on (ulong)h.ChangedBy equals changer.Id

            join c0 in _context.Customers
                on o.CustomerId equals c0.Id
                into customerGroup

            from c in customerGroup.DefaultIfEmpty()

            select new
            {
                h.Id,
                h.OrderId,
                h.FromStatus,
                h.ToStatus,
                h.Reason,
                h.CreatedAt,

                o.OrderCode,
                o.CreatedBy,

                CustomerName =
                    c != null
                        ? c.FullName
                        : null,

                OrderCreatorName =
                    creator.FullName,

                ChangedByName =
                    changer.FullName
            };
        // Nhân viên chỉ xem lịch sử đơn của mình.
        if (
            !CanReadAllOrders()
            && CanReadOwnOrders()
        )
        {
            query =
                query.Where(h =>
                    h.CreatedBy
                    == currentUserId
                );
        }



        // Tìm kiếm
        if (!string.IsNullOrWhiteSpace(keyword))
        {
            keyword =
                keyword.Trim();

            query =
                query.Where(h =>
                    h.OrderCode.Contains(keyword)
                    || (
                        h.CustomerName != null
                        && h.CustomerName.Contains(keyword)
                    )
                    || h.OrderCreatorName.Contains(keyword)
                    || h.ChangedByName.Contains(keyword)
                    || (
                        h.Reason != null
                        && h.Reason.Contains(keyword)
                    )
                );
        }


        // Lọc trạng thái sau
        if (!string.IsNullOrWhiteSpace(status))
        {
            status =
                status.Trim().ToUpper();

            if (
                status != "PENDING"
                && status != "COMPLETED"
                && status != "CANCELLED"
            )
            {
                return BadRequest(new
                {
                    message =
                        "Trạng thái lịch sử đơn hàng không hợp lệ."
                });
            }

            query =
                query.Where(h =>
                    h.ToStatus == status
                );
        }


        var histories =
            await query
                .OrderByDescending(h =>
                    h.CreatedAt
                )
                .ThenByDescending(h =>
                    h.Id
                )
                .Select(h => new
                {
                    h.Id,
                    OrderId =
                        h.OrderId,

                    h.FromStatus,
                    h.ToStatus,
                    h.Reason,
                    h.CreatedAt,
                    h.OrderCode,
                    h.CustomerName,
                    h.OrderCreatorName,
                    h.ChangedByName
                })
                .ToListAsync();


        return Ok(histories);
    }

}


// =========================================
// DTO
// =========================================

public class CompleteOrderRequest
{
    public string? Reason { get; set; }
}


public class CancelOrderRequest
{
    public string? CancelledBy { get; set; }

    public string? Reason { get; set; }
}
public class CreateOrderRequest
{
    public ulong? CustomerId { get; set; }

    public string? GuestName { get; set; }

    public string? GuestPhone { get; set; }

    public decimal DepositAmount { get; set; }

    public string? Note { get; set; }


    public List<CreateOrderItemRequest> Items { get; set; }
        = new();
}


public class CreateOrderItemRequest
{
    public ulong VariantId { get; set; }

    public int Quantity { get; set; }
}


// DTO nội bộ chỉ dùng khi tạo đơn
public class PreparedOrderItem
{
    public ProductVariant Variant { get; set; }
        = null!;

    public int Quantity { get; set; }

    public decimal UnitPrice { get; set; }

    public decimal Subtotal { get; set; }

    public int StockBefore { get; set; }

    public int StockAfter { get; set; }
}
