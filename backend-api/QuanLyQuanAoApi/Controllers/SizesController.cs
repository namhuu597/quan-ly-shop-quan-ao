using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Mvc;
using Microsoft.EntityFrameworkCore;
using QuanLyQuanAoApi.Data;
using QuanLyQuanAoApi.Models;

namespace QuanLyQuanAoApi.Controllers;

[ApiController]
[Route("api/[controller]")]
[Authorize]
public class SizesController : ControllerBase
{
    private readonly AppDbContext _context;

    public SizesController(AppDbContext context)
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
    // GET: api/sizes
    // Permission: size.read
    // =========================================
    [HttpGet]
    public async Task<ActionResult<IEnumerable<object>>> GetSizes()
    {
        if (!HasPermission("size.read"))
        {
            return Forbid();
        }

        var sizes = await _context.Sizes
            .OrderBy(s => s.Id)
            .Select(s => new
            {
                s.Id,
                s.Name,

                VariantCount =
                    s.ProductVariants.Count,

                s.IsActive
            })
            .ToListAsync();

        return Ok(sizes);
    }


    // =========================================
    // GET: api/sizes/1
    // Permission: size.read
    // =========================================
    [HttpGet("{id}")]
    public async Task<ActionResult<object>> GetSize(ulong id)
    {
        if (!HasPermission("size.read"))
        {
            return Forbid();
        }

        var size = await _context.Sizes
            .Where(s => s.Id == id)
            .Select(s => new
            {
                s.Id,
                s.Name,

                VariantCount =
                    s.ProductVariants.Count,

                s.IsActive
            })
            .FirstOrDefaultAsync();

        if (size == null)
        {
            return NotFound(new
            {
                message = "Không tìm thấy Size."
            });
        }

        return Ok(size);
    }


    // =========================================
    // POST: api/sizes
    // Permission: size.create
    // =========================================
    [HttpPost]
    public async Task<ActionResult<Size>> CreateSize(
        [FromBody] SizeRequest request
    )
    {
        if (!HasPermission("size.create"))
        {
            return Forbid();
        }

        var name =
            request.Name?.Trim();

        if (string.IsNullOrWhiteSpace(name))
        {
            return BadRequest(new
            {
                message =
                    "Tên Size không được để trống."
            });
        }


        var exists =
            await _context.Sizes
                .AnyAsync(s =>
                    s.Name == name
                );

        if (exists)
        {
            return Conflict(new
            {
                message =
                    "Tên Size đã tồn tại."
            });
        }


        var size = new Size
        {
            Name = name,
            IsActive = true
        };


        _context.Sizes.Add(size);

        await _context.SaveChangesAsync();


        return CreatedAtAction(
            nameof(GetSize),
            new { id = size.Id },
            new
            {
                size.Id,
                size.Name,
                VariantCount = 0,
                size.IsActive
            }
        );
    }


    // =========================================
    // PUT: api/sizes/1
    // Permission: size.update
    // =========================================
    [HttpPut("{id}")]
    public async Task<IActionResult> UpdateSize(
        ulong id,
        [FromBody] SizeRequest request
    )
    {
        if (!HasPermission("size.update"))
        {
            return Forbid();
        }

        var size =
            await _context.Sizes.FindAsync(id);

        if (size == null)
        {
            return NotFound(new
            {
                message =
                    "Không tìm thấy Size."
            });
        }


        var name =
            request.Name?.Trim();

        if (string.IsNullOrWhiteSpace(name))
        {
            return BadRequest(new
            {
                message =
                    "Tên Size không được để trống."
            });
        }


        var exists =
            await _context.Sizes
                .AnyAsync(s =>
                    s.Id != id
                    && s.Name == name
                );

        if (exists)
        {
            return Conflict(new
            {
                message =
                    "Tên Size đã tồn tại."
            });
        }


        size.Name = name;

        await _context.SaveChangesAsync();


        return Ok(new
        {
            message =
                "Cập nhật Size thành công."
        });
    }


    // =========================================
    // PATCH: api/sizes/1/status
    // Permission: size.delete
    // =========================================
    [HttpPatch("{id}/status")]
    public async Task<IActionResult> ToggleStatus(
        ulong id
    )
    {
        if (!HasPermission("size.delete"))
        {
            return Forbid();
        }

        var size =
            await _context.Sizes.FindAsync(id);

        if (size == null)
        {
            return NotFound(new
            {
                message =
                    "Không tìm thấy Size."
            });
        }


        size.IsActive =
            !(size.IsActive ?? true);


        await _context.SaveChangesAsync();


        return Ok(new
        {
            message =
                "Cập nhật trạng thái Size thành công.",

            id = size.Id,
            isActive = size.IsActive
        });
    }
}


// =========================================
// DTO
// =========================================
public class SizeRequest
{
    public string? Name { get; set; }
}
