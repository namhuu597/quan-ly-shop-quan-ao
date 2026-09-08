using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Mvc;
using Microsoft.EntityFrameworkCore;
using System.Text.Json;
using QuanLyQuanAoApi.Models;

using QuanLyQuanAoApi.Data;

namespace QuanLyQuanAoApi.Controllers;

[ApiController]
[Route("api/[controller]")]
[Authorize]
public class ProductsController : ControllerBase
{
    private readonly AppDbContext _context;

    public ProductsController(AppDbContext context)
    {
        _context = context;
    }


    // =========================================
    // JWT / PERMISSION HELPER
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


    // =========================================
    // GET: api/products
    // =========================================
    [HttpGet]
    public async Task<IActionResult> GetProducts(
        [FromQuery] string? keyword,
        [FromQuery] ulong? categoryId,
        [FromQuery] ulong? colorId,
        [FromQuery] string? status
    )
    {
        if (!HasPermission("product.read"))
        {
            return Forbid();
        }


        var query =
            _context.Products
                .AsQueryable();


        if (!string.IsNullOrWhiteSpace(keyword))
        {
            keyword = keyword.Trim();

            query = query.Where(p =>
                p.Name.Contains(keyword)
                || p.ProductCode.Contains(keyword)
            );
        }


        if (
            categoryId.HasValue
            && categoryId.Value > 0
        )
        {
            query = query.Where(p =>
                p.CategoryId == categoryId.Value
            );
        }


        if (
            colorId.HasValue
            && colorId.Value > 0
        )
        {
            query = query.Where(p =>
                p.ProductVariants.Any(v =>
                    v.ColorId == colorId.Value
                )
            );
        }


        if (!string.IsNullOrWhiteSpace(status))
        {
            status =
                status.Trim().ToUpper();

            query = query.Where(p =>
                p.Status == status
            );
        }


        var products =
            await query
                .OrderByDescending(p =>
                    p.CreatedAt
                )
                .Select(p => new
                {
                    p.Id,
                    p.ProductCode,
                    p.Name,
                    p.BasePrice,
                    p.Status,

                    CategoryName =
                        p.Category.Name,

                    BrandName =
                        p.Brand != null
                            ? p.Brand.Name
                            : null,

                    ImagePath =
                        p.ProductImages
                            .OrderByDescending(i =>
                                i.IsPrimary
                            )
                            .ThenBy(i =>
                                i.SortOrder
                            )
                            .Select(i =>
                                i.ImagePath
                            )
                            .FirstOrDefault(),

                    TotalStock =
                        p.ProductVariants
                            .Sum(v =>
                                (int?)v.StockQuantity
                            )
                            ?? 0
                })
                .ToListAsync();


        return Ok(products);
    }


    // =========================================
    // GET: api/products/1
    // =========================================
    [HttpGet("{id}")]
    public async Task<IActionResult> GetProduct(
        ulong id
    )
    {
        if (!HasPermission("product.read"))
        {
            return Forbid();
        }


        var product =
            await _context.Products
                .Where(p => p.Id == id)
                .Select(p => new
                {
                    p.Id,
                    p.ProductCode,
                    p.Name,
                    p.Description,
                    p.BasePrice,
                    p.Status,
                    p.CategoryId,
                    p.BrandId,

                    CategoryName =
                        p.Category.Name,

                    BrandName =
                        p.Brand != null
                            ? p.Brand.Name
                            : null,

                    TotalStock =
                        p.ProductVariants
                            .Sum(v =>
                                (int?)v.StockQuantity
                            )
                            ?? 0,

                    Images =
                        p.ProductImages
                            .OrderByDescending(i =>
                                i.IsPrimary
                            )
                            .ThenBy(i =>
                                i.SortOrder
                            )
                            .Select(i => new
                            {
                                i.Id,
                                i.ImagePath,
                                i.IsPrimary,
                                i.SortOrder
                            })
                            .ToList(),

                    Variants =
                        p.ProductVariants
                            .OrderBy(v =>
                                v.Size.Id
                            )
                            .ThenBy(v =>
                                v.Color.Name
                            )
                            .Select(v => new
                            {
                                v.Id,
                                v.Sku,

                                v.SizeId,

                                SizeName =
                                    v.Size.Name,

                                v.ColorId,

                                ColorName =
                                    v.Color.Name,

                                v.Price,
                                v.StockQuantity,
                                v.IsActive
                            })
                            .ToList()
                })
                .FirstOrDefaultAsync();


        if (product == null)
        {
            return NotFound(new
            {
                message =
                    "Không tìm thấy sản phẩm."
            });
        }


        return Ok(product);
    }


    // =========================================
    // PATCH: api/products/1/status
    // =========================================
    [HttpPatch("{id}/status")]
    public async Task<IActionResult> ToggleStatus(
        ulong id
    )
    {
        if (!HasPermission("product.update"))
        {
            return Forbid();
        }


        var product =
            await _context.Products
                .FindAsync(id);

        if (product == null)
        {
            return NotFound(new
            {
                message =
                    "Không tìm thấy sản phẩm."
            });
        }


        product.Status =
            product.Status == "ACTIVE"
                ? "INACTIVE"
                : "ACTIVE";


        await _context.SaveChangesAsync();


        return Ok(new
        {
            message =
                "Cập nhật trạng thái sản phẩm thành công.",

            id = product.Id,
            status = product.Status
        });
    }
// =========================================
// GET: api/products/form-options
// DỮ LIỆU CHO FORM THÊM/SỬA SẢN PHẨM
// =========================================
[HttpGet("form-options")]
public async Task<IActionResult> GetFormOptions()
{
    if (
        !HasPermission("product.create")
        && !HasPermission("product.update")
    )
    {
        return Forbid();
    }


    var categories = await _context.Categories
        .Where(c => c.IsActive == true)
        .OrderBy(c => c.Name)
        .Select(c => new
        {
            c.Id,
            c.Name
        })
        .ToListAsync();


    var brands = await _context.Brands
        .Where(b => b.IsActive == true)
        .OrderBy(b => b.Name)
        .Select(b => new
        {
            b.Id,
            b.Name
        })
        .ToListAsync();


    var sizes = await _context.Sizes
        .Where(s => s.IsActive == true)
        .OrderBy(s => s.Id)
        .Select(s => new
        {
            s.Id,
            s.Name
        })
        .ToListAsync();


    var colors = await _context.Colors
        .Where(c => c.IsActive == true)
        .OrderBy(c => c.Name)
        .Select(c => new
        {
            c.Id,
            c.Name,
            c.ColorCode
        })
        .ToListAsync();


    return Ok(new
    {
        categories,
        brands,
        sizes,
        colors
    });
}
// =========================================
// POST: api/products
// TẠO SẢN PHẨM + BIẾN THỂ + HÌNH ẢNH
// =========================================
[HttpPost]
[Consumes("multipart/form-data")]
public async Task<IActionResult> CreateProduct(
    [FromForm] string productJson,
    [FromForm] int primaryImageIndex = 0
)
{
    if (!HasPermission("product.create"))
    {
        return Forbid();
    }


    var images =
        Request.Form.Files.ToList();

    ProductCreateRequest? request;

    try
    {
        request = JsonSerializer.Deserialize<ProductCreateRequest>(
            productJson,
            new JsonSerializerOptions
            {
                PropertyNameCaseInsensitive = true
            }
        );
    }
    catch
    {
        return BadRequest(new
        {
            message = "Dữ liệu sản phẩm không hợp lệ."
        });
    }


    if (request == null)
    {
        return BadRequest(new
        {
            message = "Dữ liệu sản phẩm không hợp lệ."
        });
    }


    // =========================================
    // VALIDATE SẢN PHẨM
    // =========================================

    var productCode =
        request.ProductCode?.Trim();

    var name =
        request.Name?.Trim();

    var description =
        request.Description?.Trim();


    if (
        string.IsNullOrWhiteSpace(productCode)
        || string.IsNullOrWhiteSpace(name)
    )
    {
        return BadRequest(new
        {
            message =
                "Vui lòng nhập đầy đủ mã và tên sản phẩm."
        });
    }


    if (request.CategoryId == 0)
    {
        return BadRequest(new
        {
            message =
                "Vui lòng chọn danh mục."
        });
    }


    if (request.BasePrice < 0)
    {
        return BadRequest(new
        {
            message =
                "Giá bán không hợp lệ."
        });
    }


    request.Status =
        request.Status?.Trim().ToUpper()
        ?? "ACTIVE";


    if (
        request.Status != "ACTIVE"
        && request.Status != "INACTIVE"
    )
    {
        return BadRequest(new
        {
            message =
                "Trạng thái sản phẩm không hợp lệ."
        });
    }


    // =========================================
    // KIỂM TRA MÃ SP
    // =========================================

    var productCodeExists =
        await _context.Products
            .AnyAsync(p =>
                p.ProductCode == productCode
            );


    if (productCodeExists)
    {
        return Conflict(new
        {
            message =
                "Mã sản phẩm đã tồn tại."
        });
    }


    // =========================================
    // KIỂM TRA DANH MỤC
    // =========================================

    var categoryExists =
        await _context.Categories
            .AnyAsync(c =>
                c.Id == request.CategoryId
                && c.IsActive == true
            );


    if (!categoryExists)
    {
        return BadRequest(new
        {
            message =
                "Danh mục không tồn tại hoặc đã ngừng sử dụng."
        });
    }


    // =========================================
    // KIỂM TRA THƯƠNG HIỆU
    // =========================================

    if (request.BrandId.HasValue)
    {
        var brandExists =
            await _context.Brands
                .AnyAsync(b =>
                    b.Id == request.BrandId.Value
                    && b.IsActive == true
                );


        if (!brandExists)
        {
            return BadRequest(new
            {
                message =
                    "Thương hiệu không tồn tại hoặc đã ngừng sử dụng."
            });
        }
    }


    // =========================================
    // KIỂM TRA BIẾN THỂ
    // =========================================

    if (
        request.Variants == null
        || request.Variants.Count == 0
    )
    {
        return BadRequest(new
        {
            message =
                "Sản phẩm phải có ít nhất một biến thể."
        });
    }


    var combinationSet =
        new HashSet<string>();

    var skuSet =
        new HashSet<string>(
            StringComparer.OrdinalIgnoreCase
        );


    foreach (var variant in request.Variants)
    {
        if (
            variant.SizeId == 0
            || variant.ColorId == 0
            || string.IsNullOrWhiteSpace(variant.Sku)
        )
        {
            return BadRequest(new
            {
                message =
                    "Vui lòng nhập đầy đủ Size, Màu và SKU cho tất cả biến thể."
            });
        }


        if (variant.StockQuantity < 0)
        {
            return BadRequest(new
            {
                message =
                    "Tồn kho không được nhỏ hơn 0."
            });
        }


        if (
            variant.Price.HasValue
            && variant.Price.Value < 0
        )
        {
            return BadRequest(new
            {
                message =
                    "Giá biến thể không hợp lệ."
            });
        }


        var combinationKey =
            variant.SizeId
            + "_"
            + variant.ColorId;


        if (!combinationSet.Add(combinationKey))
        {
            return Conflict(new
            {
                message =
                    "Không được tạo hai biến thể có cùng Size và Màu."
            });
        }


        var sku =
            variant.Sku.Trim();


        if (!skuSet.Add(sku))
        {
            return Conflict(new
            {
                message =
                    "SKU bị trùng trong danh sách biến thể."
            });
        }


        var skuExists =
            await _context.ProductVariants
                .AnyAsync(v =>
                    v.Sku == sku
                );


        if (skuExists)
        {
            return Conflict(new
            {
                message =
                    "SKU "
                    + sku
                    + " đã tồn tại."
            });
        }


        var sizeExists =
            await _context.Sizes
                .AnyAsync(s =>
                    s.Id == variant.SizeId
                    && s.IsActive == true
                );


        if (!sizeExists)
        {
            return BadRequest(new
            {
                message =
                    "Size của một biến thể không hợp lệ."
            });
        }


        var colorExists =
            await _context.Colors
                .AnyAsync(c =>
                    c.Id == variant.ColorId
                    && c.IsActive == true
                );


        if (!colorExists)
        {
            return BadRequest(new
            {
                message =
                    "Màu sắc của một biến thể không hợp lệ."
            });
        }
    }


    // =========================================
    // KIỂM TRA HÌNH ẢNH
    // =========================================

    var allowedImageTypes =
        new[]
        {
            "image/jpeg",
            "image/png",
            "image/webp"
        };


    if (images.Count > 0)
    {
        foreach (var image in images)
        {
            if (
                !allowedImageTypes.Contains(
                    image.ContentType.ToLower()
                )
            )
            {
                return BadRequest(new
                {
                    message =
                        "Chỉ chấp nhận ảnh JPG, JPEG, PNG hoặc WEBP."
                });
            }


            if (image.Length > 5 * 1024 * 1024)
            {
                return BadRequest(new
                {
                    message =
                        "Mỗi hình ảnh không được vượt quá 5MB."
                });
            }
        }
    }


    await using var transaction =
        await _context.Database
            .BeginTransactionAsync();


    var savedFiles =
        new List<string>();


    try
    {
        // =========================================
        // 1. PRODUCT
        // =========================================

        var product =
            new Product
            {
                ProductCode =
                    productCode,

                CategoryId =
                    request.CategoryId,

                BrandId =
                    request.BrandId,

                Name =
                    name,

                Description =
                    string.IsNullOrWhiteSpace(description)
                        ? null
                        : description,

                BasePrice =
                    request.BasePrice,

                Status =
                    request.Status,

                CreatedAt =
                    DateTime.Now
            };


        _context.Products.Add(product);

        await _context.SaveChangesAsync();


        // =========================================
        // 2. VARIANT
        // =========================================

        foreach (var item in request.Variants)
        {
            var variant =
                new ProductVariant
                {
                    ProductId =
                        product.Id,

                    SizeId =
                        item.SizeId,

                    ColorId =
                        item.ColorId,

                    Sku =
                        item.Sku.Trim(),

                    Price =
                        item.Price,

                    StockQuantity =
                        item.StockQuantity,

                    IsActive =
                        true
                };


            _context.ProductVariants.Add(
                variant
            );
        }


        await _context.SaveChangesAsync();


        // =========================================
        // 3. HÌNH ẢNH
        // =========================================

        if (
            images.Count > 0
        )
        {
            var uploadDirectory =
                @"D:\xampp\htdocs\quanlyquanao\uploads\products";


            Directory.CreateDirectory(
                uploadDirectory
            );


            if (
                primaryImageIndex < 0
                || primaryImageIndex >= images.Count
            )
            {
                primaryImageIndex = 0;
            }


            for (
                var i = 0;
                i < images.Count;
                i++
            )
            {
                var image =
                    images[i];


                var extension =
                    image.ContentType.ToLower() switch
                    {
                        "image/jpeg" => ".jpg",
                        "image/png" => ".png",
                        "image/webp" => ".webp",
                        _ => ""
                    };


                var fileName =
                    "product_"
                    + Guid.NewGuid()
                        .ToString("N")
                    + extension;


                var absolutePath =
                    Path.Combine(
                        uploadDirectory,
                        fileName
                    );


                await using (
                    var stream =
                        new FileStream(
                            absolutePath,
                            FileMode.Create
                        )
                )
                {
                    await image.CopyToAsync(
                        stream
                    );
                }


                savedFiles.Add(
                    absolutePath
                );


                var relativePath =
                    "uploads/products/"
                    + fileName;


                var productImage =
                    new ProductImage
                    {
                        ProductId =
                            product.Id,

                        ImagePath =
                            relativePath,

                        IsPrimary =
                            i == primaryImageIndex,

                        SortOrder =
                            i
                    };


                _context.ProductImages.Add(
                    productImage
                );
            }


            await _context.SaveChangesAsync();
        }


        await transaction.CommitAsync();


        return CreatedAtAction(
            nameof(GetProduct),
            new
            {
                id = product.Id
            },
            new
            {
                message =
                    "Thêm sản phẩm thành công.",

                id =
                    product.Id,

                productCode =
                    product.ProductCode
            }
        );
    }
    catch (Exception ex)
    {
        await transaction.RollbackAsync();


        foreach (var file in savedFiles)
        {
            try
            {
                if (System.IO.File.Exists(file))
                {
                    System.IO.File.Delete(file);
                }
            }
            catch
            {
            }
        }


        return StatusCode(
            500,
            new
            {
                message =
                    "Không thể thêm sản phẩm.",

                detail =
                    ex.Message
            }
        );
    }
}

    // =========================================
    // PUT: api/products/1
    // CẬP NHẬT SẢN PHẨM + BIẾN THỂ + HÌNH ẢNH
    // =========================================
    [HttpPut("{id}")]
    [Consumes("multipart/form-data")]
    public async Task<IActionResult> UpdateProduct(
        ulong id,
        [FromForm] string productJson,
        [FromForm] string? deleteImageIdsJson,
        [FromForm] ulong? primaryImageId,
        [FromForm] int? newPrimaryIndex
    )
    {
        if (!HasPermission("product.update"))
        {
            return Forbid();
        }


        var images =
            Request.Form.Files.ToList();

        ProductUpdateRequest? request;

        try
        {
            request =
                JsonSerializer.Deserialize<ProductUpdateRequest>(
                    productJson,
                    new JsonSerializerOptions
                    {
                        PropertyNameCaseInsensitive = true
                    }
                );
        }
        catch
        {
            return BadRequest(new
            {
                message =
                    "Dữ liệu sản phẩm không hợp lệ."
            });
        }

        if (request == null)
        {
            return BadRequest(new
            {
                message =
                    "Dữ liệu sản phẩm không hợp lệ."
            });
        }

        var product =
            await _context.Products
                .FirstOrDefaultAsync(p =>
                    p.Id == id
                );

        if (product == null)
        {
            return NotFound(new
            {
                message =
                    "Không tìm thấy sản phẩm."
            });
        }

        var productCode =
            request.ProductCode?.Trim();

        var name =
            request.Name?.Trim();

        var description =
            request.Description?.Trim();

        var status =
            request.Status?.Trim().ToUpper()
            ?? "ACTIVE";

        if (
            string.IsNullOrWhiteSpace(productCode)
            || string.IsNullOrWhiteSpace(name)
            || request.CategoryId == 0
        )
        {
            return BadRequest(new
            {
                message =
                    "Vui lòng nhập đầy đủ các trường bắt buộc."
            });
        }

        if (request.BasePrice < 0)
        {
            return BadRequest(new
            {
                message =
                    "Giá bán không hợp lệ."
            });
        }

        if (
            status != "ACTIVE"
            && status != "INACTIVE"
        )
        {
            return BadRequest(new
            {
                message =
                    "Trạng thái sản phẩm không hợp lệ."
            });
        }

        var codeExists =
            await _context.Products
                .AnyAsync(p =>
                    p.Id != id
                    && p.ProductCode == productCode
                );

        if (codeExists)
        {
            return Conflict(new
            {
                message =
                    "Mã sản phẩm đã tồn tại."
            });
        }

        var categoryExists =
            await _context.Categories
                .AnyAsync(c =>
                    c.Id == request.CategoryId
                    && c.IsActive == true
                );

        if (!categoryExists)
        {
            return BadRequest(new
            {
                message =
                    "Danh mục không tồn tại hoặc đã ngừng sử dụng."
            });
        }

        if (request.BrandId.HasValue)
        {
            var brandExists =
                await _context.Brands
                    .AnyAsync(b =>
                        b.Id == request.BrandId.Value
                        && b.IsActive == true
                    );

            if (!brandExists)
            {
                return BadRequest(new
                {
                    message =
                        "Thương hiệu không tồn tại hoặc đã ngừng sử dụng."
                });
            }
        }

        if (
            request.Variants == null
            || !request.Variants.Any(v => !v.Delete)
        )
        {
            return BadRequest(new
            {
                message =
                    "Sản phẩm phải có ít nhất một biến thể."
            });
        }

        var combinationSet =
            new HashSet<string>();

        var skuSet =
            new HashSet<string>(
                StringComparer.OrdinalIgnoreCase
            );

        foreach (
            var variant in request.Variants
                .Where(v => !v.Delete)
        )
        {
            if (
                variant.SizeId == 0
                || variant.ColorId == 0
                || string.IsNullOrWhiteSpace(variant.Sku)
            )
            {
                return BadRequest(new
                {
                    message =
                        "Vui lòng nhập đầy đủ Size, Màu và SKU."
                });
            }

            if (
                variant.Price.HasValue
                && variant.Price.Value < 0
            )
            {
                return BadRequest(new
                {
                    message =
                        "Giá biến thể không hợp lệ."
                });
            }

            var combinationKey =
                variant.SizeId
                + "_"
                + variant.ColorId;

            if (!combinationSet.Add(combinationKey))
            {
                return Conflict(new
                {
                    message =
                        "Không được tạo hai biến thể có cùng Size và Màu."
                });
            }

            var sku =
                variant.Sku.Trim();

            if (!skuSet.Add(sku))
            {
                return Conflict(new
                {
                    message =
                        "SKU không được trùng nhau."
                });
            }

            var skuExists =
                await _context.ProductVariants
                    .AnyAsync(v =>
                        v.Sku == sku
                        && v.Id != variant.Id
                    );

            if (skuExists)
            {
                return Conflict(new
                {
                    message =
                        "SKU " + sku + " đã tồn tại."
                });
            }

            var sizeExists =
                await _context.Sizes
                    .AnyAsync(s =>
                        s.Id == variant.SizeId
                        && s.IsActive == true
                    );

            if (!sizeExists)
            {
                return BadRequest(new
                {
                    message =
                        "Size của một biến thể không hợp lệ."
                });
            }

            var colorExists =
                await _context.Colors
                    .AnyAsync(c =>
                        c.Id == variant.ColorId
                        && c.IsActive == true
                    );

            if (!colorExists)
            {
                return BadRequest(new
                {
                    message =
                        "Màu sắc của một biến thể không hợp lệ."
                });
            }
        }

        var allowedImageTypes =
            new[]
            {
                "image/jpeg",
                "image/png",
                "image/webp"
            };

        foreach (var image in images)
        {
            if (
                !allowedImageTypes.Contains(
                    image.ContentType.ToLower()
                )
            )
            {
                return BadRequest(new
                {
                    message =
                        "Chỉ chấp nhận ảnh JPG, JPEG, PNG hoặc WEBP."
                });
            }

            if (image.Length > 5 * 1024 * 1024)
            {
                return BadRequest(new
                {
                    message =
                        "Mỗi hình ảnh không được vượt quá 5MB."
                });
            }
        }

        List<ulong> deleteImageIds =
            new();

        if (
            !string.IsNullOrWhiteSpace(
                deleteImageIdsJson
            )
        )
        {
            try
            {
                deleteImageIds =
                    JsonSerializer.Deserialize<List<ulong>>(
                        deleteImageIdsJson
                    )
                    ?? new List<ulong>();
            }
            catch
            {
                return BadRequest(new
                {
                    message =
                        "Danh sách ảnh cần xóa không hợp lệ."
                });
            }
        }

        await using var transaction =
            await _context.Database
                .BeginTransactionAsync();

        var savedFiles =
            new List<string>();

        var filesToDeleteAfterCommit =
            new List<string>();

        try
        {
            // 1. PRODUCT
            product.ProductCode =
                productCode;

            product.CategoryId =
                request.CategoryId;

            product.BrandId =
                request.BrandId;

            product.Name =
                name;

            product.Description =
                string.IsNullOrWhiteSpace(description)
                    ? null
                    : description;

            product.BasePrice =
                request.BasePrice;

            product.Status =
                status;

            await _context.SaveChangesAsync();

            // 2. VARIANTS
            var existingVariants =
                await _context.ProductVariants
                    .Where(v =>
                        v.ProductId == id
                    )
                    .ToDictionaryAsync(v =>
                        v.Id
                    );

            foreach (var item in request.Variants)
            {
                if (item.Id > 0)
                {
                    if (
                        !existingVariants.TryGetValue(
                            item.Id,
                            out var variant
                        )
                    )
                    {
                        throw new Exception(
                            "Có biến thể không thuộc sản phẩm này."
                        );
                    }

                    if (item.Delete)
                    {
                        var isUsedInOrders =
                            await _context.OrderDetails
                                .AnyAsync(od =>
                                    od.ProductVariantId
                                    == variant.Id
                                );

                        if (isUsedInOrders)
                        {
                            variant.IsActive =
                                false;
                        }
                        else
                        {
                            _context.ProductVariants
                                .Remove(variant);
                        }

                        continue;
                    }

                    variant.SizeId =
                        item.SizeId;

                    variant.ColorId =
                        item.ColorId;

                    variant.Sku =
                        item.Sku!.Trim();

                    variant.Price =
                        item.Price;

                    variant.IsActive =
                        true;
                }
                else
                {
                    if (item.Delete)
                    {
                        continue;
                    }

                    var newVariant =
                        new ProductVariant
                        {
                            ProductId =
                                id,

                            SizeId =
                                item.SizeId,

                            ColorId =
                                item.ColorId,

                            Sku =
                                item.Sku!.Trim(),

                            Price =
                                item.Price,

                            StockQuantity =
                                0,

                            IsActive =
                                true
                        };

                    _context.ProductVariants
                        .Add(newVariant);
                }
            }

            await _context.SaveChangesAsync();

            // 3. IMAGES CŨ
            var currentImages =
                await _context.ProductImages
                    .Where(i =>
                        i.ProductId == id
                    )
                    .OrderBy(i =>
                        i.SortOrder
                    )
                    .ThenBy(i =>
                        i.Id
                    )
                    .ToListAsync();

            foreach (
                var oldImage in currentImages
                    .Where(i =>
                        deleteImageIds.Contains(i.Id)
                    )
                    .ToList()
            )
            {
                var absoluteOldPath =
                    Path.Combine(
                        @"D:\xampp\htdocs\quanlyquanao",
                        oldImage.ImagePath
                            .Replace('/', Path.DirectorySeparatorChar)
                    );

                filesToDeleteAfterCommit.Add(
                    absoluteOldPath
                );

                _context.ProductImages
                    .Remove(oldImage);
            }

            await _context.SaveChangesAsync();

            var remainingImages =
                await _context.ProductImages
                    .Where(i =>
                        i.ProductId == id
                    )
                    .OrderBy(i =>
                        i.SortOrder
                    )
                    .ThenBy(i =>
                        i.Id
                    )
                    .ToListAsync();

            foreach (var image in remainingImages)
            {
                image.IsPrimary =
                    false;
            }

            // 4. ẢNH MỚI
            var newImageEntities =
                new List<ProductImage>();

            if (images.Count > 0)
            {
                var uploadDirectory =
                    @"D:\xampp\htdocs\quanlyquanao\uploads\products";

                Directory.CreateDirectory(
                    uploadDirectory
                );

                var nextSortOrder =
                    remainingImages.Count == 0
                        ? 0
                        : remainingImages.Max(i =>
                            i.SortOrder
                        ) + 1;

                for (
                    var i = 0;
                    i < images.Count;
                    i++
                )
                {
                    var image =
                        images[i];

                    var extension =
                        image.ContentType.ToLower() switch
                        {
                            "image/jpeg" => ".jpg",
                            "image/png" => ".png",
                            "image/webp" => ".webp",
                            _ => ""
                        };

                    var fileName =
                        "product_"
                        + Guid.NewGuid()
                            .ToString("N")
                        + extension;

                    var absolutePath =
                        Path.Combine(
                            uploadDirectory,
                            fileName
                        );

                    await using (
                        var stream =
                            new FileStream(
                                absolutePath,
                                FileMode.Create
                            )
                    )
                    {
                        await image.CopyToAsync(
                            stream
                        );
                    }

                    savedFiles.Add(
                        absolutePath
                    );

                    var newImage =
                        new ProductImage
                        {
                            ProductId =
                                id,

                            ImagePath =
                                "uploads/products/"
                                + fileName,

                            IsPrimary =
                                false,

                            SortOrder =
                                nextSortOrder + i
                        };

                    newImageEntities.Add(
                        newImage
                    );

                    _context.ProductImages
                        .Add(newImage);
                }

                await _context.SaveChangesAsync();
            }

            // 5. CHỌN ẢNH CHÍNH
            ProductImage? finalPrimary =
                null;

            if (
                newPrimaryIndex.HasValue
                && newPrimaryIndex.Value >= 0
                && newPrimaryIndex.Value
                    < newImageEntities.Count
            )
            {
                finalPrimary =
                    newImageEntities[
                        newPrimaryIndex.Value
                    ];
            }
            else if (
                primaryImageId.HasValue
                && primaryImageId.Value > 0
            )
            {
                finalPrimary =
                    remainingImages
                        .FirstOrDefault(i =>
                            i.Id
                            == primaryImageId.Value
                        );
            }

            if (finalPrimary == null)
            {
                finalPrimary =
                    remainingImages
                        .FirstOrDefault()
                    ?? newImageEntities
                        .FirstOrDefault();
            }

            if (finalPrimary != null)
            {
                finalPrimary.IsPrimary =
                    true;
            }

            await _context.SaveChangesAsync();

            await transaction.CommitAsync();

            foreach (
                var file in filesToDeleteAfterCommit
            )
            {
                try
                {
                    if (
                        System.IO.File.Exists(file)
                    )
                    {
                        System.IO.File.Delete(
                            file
                        );
                    }
                }
                catch
                {
                }
            }

            return Ok(new
            {
                message =
                    "Cập nhật sản phẩm thành công.",

                id =
                    product.Id
            });
        }
        catch (Exception ex)
        {
            await transaction.RollbackAsync();

            foreach (var file in savedFiles)
            {
                try
                {
                    if (
                        System.IO.File.Exists(file)
                    )
                    {
                        System.IO.File.Delete(file);
                    }
                }
                catch
                {
                }
            }

            return StatusCode(
                500,
                new
                {
                    message =
                        "Không thể cập nhật sản phẩm.",

                    detail =
                        ex.Message
                }
            );
        }
    }

}

public class ProductCreateRequest
{
    public string? ProductCode { get; set; }

    public ulong CategoryId { get; set; }

    public ulong? BrandId { get; set; }

    public string? Name { get; set; }

    public string? Description { get; set; }

    public decimal BasePrice { get; set; }

    public string? Status { get; set; }

    public List<ProductVariantCreateRequest> Variants { get; set; }
        = new();
}


public class ProductVariantCreateRequest
{
    public ulong SizeId { get; set; }

    public ulong ColorId { get; set; }

    public string? Sku { get; set; }

    public decimal? Price { get; set; }

    public int StockQuantity { get; set; }
}
public class ProductUpdateRequest
{
    public string? ProductCode { get; set; }

    public ulong CategoryId { get; set; }

    public ulong? BrandId { get; set; }

    public string? Name { get; set; }

    public string? Description { get; set; }

    public decimal BasePrice { get; set; }

    public string? Status { get; set; }

    public List<ProductVariantUpdateRequest> Variants { get; set; }
        = new();
}


public class ProductVariantUpdateRequest
{
    public ulong Id { get; set; }

    public ulong SizeId { get; set; }

    public ulong ColorId { get; set; }

    public string? Sku { get; set; }

    public decimal? Price { get; set; }

    public bool Delete { get; set; }
}
