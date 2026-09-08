using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Mvc;
using Microsoft.EntityFrameworkCore;
using QuanLyQuanAoApi.Data;
using QuanLyQuanAoApi.Models;

namespace QuanLyQuanAoApi.Controllers;

[ApiController]
[Route("api/[controller]")]
[Authorize]
public class CategoriesController : ControllerBase
{
    private readonly AppDbContext _context;

    public CategoriesController(AppDbContext context)
    {
        _context = context;
    }


    // =========================================
    // JWT / PERMISSION HELPER
    // =========================================
    private bool HasPermission(string permission)
    {
        if (User.IsInRole("ADMIN"))
        {
            return true;
        }

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
    // GET: api/categories
    // LẤY DANH SÁCH DANH MỤC
    // Permission: category.read
    // =========================================
    [HttpGet]
    public async Task<ActionResult<IEnumerable<object>>> GetCategories()
    {
        if (!HasPermission("category.read"))
        {
            return Forbid();
        }

        var categories = await _context.Categories
            .OrderByDescending(c => c.Id)
            .Select(c => new
            {
                c.Id,
                c.Name,
                c.Description,

                ProductCount = c.Products.Count,

                c.IsActive,
                c.CreatedAt,
                c.UpdatedAt
            })
            .ToListAsync();

        return Ok(categories);
    }


    // =========================================
    // GET: api/categories/1
    // XEM CHI TIẾT DANH MỤC
    // Permission: category.read
    // =========================================
    [HttpGet("{id}")]
    public async Task<ActionResult<object>> GetCategory(ulong id)
    {
        if (!HasPermission("category.read"))
        {
            return Forbid();
        }

        var category = await _context.Categories
            .Where(c => c.Id == id)
            .Select(c => new
            {
                c.Id,
                c.Name,
                c.Description,

                ProductCount = c.Products.Count,

                c.IsActive,
                c.CreatedAt,
                c.UpdatedAt
            })
            .FirstOrDefaultAsync();

        if (category == null)
        {
            return NotFound(new
            {
                message = "Không tìm thấy danh mục."
            });
        }

        return Ok(category);
    }


    // =========================================
    // POST: api/categories
    // THÊM DANH MỤC
    // Permission: category.create
    // =========================================
    [HttpPost]
    public async Task<ActionResult<Category>> CreateCategory(
        [FromBody] CategoryRequest request
    )
    {
        if (!HasPermission("category.create"))
        {
            return Forbid();
        }

        var name = request.Name?.Trim();

        if (string.IsNullOrWhiteSpace(name))
        {
            return BadRequest(new
            {
                message = "Tên danh mục không được để trống."
            });
        }

        var exists = await _context.Categories
            .AnyAsync(c => c.Name == name);

        if (exists)
        {
            return Conflict(new
            {
                message = "Tên danh mục đã tồn tại."
            });
        }

        var category = new Category
        {
            Name = name,
            Description = request.Description?.Trim(),
            IsActive = true,
            CreatedAt = DateTime.Now,
            UpdatedAt = null
        };

        _context.Categories.Add(category);

        await _context.SaveChangesAsync();

        return CreatedAtAction(
            nameof(GetCategory),
            new { id = category.Id },
            new
            {
                category.Id,
                category.Name,
                category.Description,
                ProductCount = 0,
                category.IsActive,
                category.CreatedAt,
                category.UpdatedAt
            }
        );
    }


    // =========================================
    // PUT: api/categories/1
    // CẬP NHẬT DANH MỤC
    // Permission: category.update
    // =========================================
    [HttpPut("{id}")]
    public async Task<IActionResult> UpdateCategory(
        ulong id,
        [FromBody] CategoryRequest request
    )
    {
        if (!HasPermission("category.update"))
        {
            return Forbid();
        }

        var category =
            await _context.Categories.FindAsync(id);

        if (category == null)
        {
            return NotFound(new
            {
                message = "Không tìm thấy danh mục."
            });
        }

        var name = request.Name?.Trim();

        if (string.IsNullOrWhiteSpace(name))
        {
            return BadRequest(new
            {
                message = "Tên danh mục không được để trống."
            });
        }

        var exists = await _context.Categories
            .AnyAsync(c =>
                c.Id != id
                && c.Name == name
            );

        if (exists)
        {
            return Conflict(new
            {
                message = "Tên danh mục đã tồn tại."
            });
        }

        category.Name = name;

        category.Description =
            request.Description?.Trim();

        category.UpdatedAt =
            DateTime.Now;

        await _context.SaveChangesAsync();

        return Ok(new
        {
            message =
                "Cập nhật danh mục thành công."
        });
    }


    // =========================================
    // PATCH: api/categories/1/status
    // BẬT / TẮT DANH MỤC
    // Permission: category.delete
    // =========================================
    [HttpPatch("{id}/status")]
    public async Task<IActionResult> ToggleStatus(ulong id)
    {
        if (!HasPermission("category.delete"))
        {
            return Forbid();
        }

        var category =
            await _context.Categories.FindAsync(id);

        if (category == null)
        {
            return NotFound(new
            {
                message = "Không tìm thấy danh mục."
            });
        }

        category.IsActive =
            !(category.IsActive ?? true);

        category.UpdatedAt =
            DateTime.Now;

        await _context.SaveChangesAsync();

        return Ok(new
        {
            message =
                "Cập nhật trạng thái thành công.",

            id = category.Id,
            isActive = category.IsActive
        });
    }
}


// =========================================
// DTO NHẬN DỮ LIỆU TỪ CLIENT
// =========================================
public class CategoryRequest
{
    public string? Name { get; set; }

    public string? Description { get; set; }
}
