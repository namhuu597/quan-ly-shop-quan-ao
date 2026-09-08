using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Mvc;
using Microsoft.EntityFrameworkCore;
using QuanLyQuanAoApi.Data;
using QuanLyQuanAoApi.Models;

namespace QuanLyQuanAoApi.Controllers;

[ApiController]
[Route("api/[controller]")]
[Authorize]
public class ColorsController : ControllerBase
{
    private readonly AppDbContext _context;

    public ColorsController(AppDbContext context)
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
    // GET: api/colors
    // Permission: color.read
    // =========================================
    [HttpGet]
    public async Task<ActionResult<IEnumerable<object>>> GetColors()
    {
        if (!HasPermission("color.read"))
        {
            return Forbid();
        }

        var colors = await _context.Colors
            .OrderBy(c => c.Name)
            .Select(c => new
            {
                c.Id,
                c.Name,
                c.ColorCode,

                VariantCount =
                    c.ProductVariants.Count,

                c.IsActive
            })
            .ToListAsync();

        return Ok(colors);
    }


    // =========================================
    // GET: api/colors/1
    // Permission: color.read
    // =========================================
    [HttpGet("{id}")]
    public async Task<ActionResult<object>> GetColor(ulong id)
    {
        if (!HasPermission("color.read"))
        {
            return Forbid();
        }

        var color = await _context.Colors
            .Where(c => c.Id == id)
            .Select(c => new
            {
                c.Id,
                c.Name,
                c.ColorCode,

                VariantCount =
                    c.ProductVariants.Count,

                c.IsActive
            })
            .FirstOrDefaultAsync();

        if (color == null)
        {
            return NotFound(new
            {
                message = "Không tìm thấy màu sắc."
            });
        }

        return Ok(color);
    }


    // =========================================
    // POST: api/colors
    // Permission: color.create
    // =========================================
    [HttpPost]
    public async Task<ActionResult<Color>> CreateColor(
        [FromBody] ColorRequest request
    )
    {
        if (!HasPermission("color.create"))
        {
            return Forbid();
        }

        var name =
            request.Name?.Trim();

        var colorCode =
            request.ColorCode?.Trim().ToUpper();


        if (string.IsNullOrWhiteSpace(name))
        {
            return BadRequest(new
            {
                message =
                    "Tên màu không được để trống."
            });
        }


        if (
            !string.IsNullOrWhiteSpace(colorCode)
            && !System.Text.RegularExpressions.Regex.IsMatch(
                colorCode,
                @"^#[0-9A-F]{6}$"
            )
        )
        {
            return BadRequest(new
            {
                message =
                    "Mã màu HEX không hợp lệ. Ví dụ: #000000."
            });
        }


        var exists =
            await _context.Colors
                .AnyAsync(c =>
                    c.Name == name
                );

        if (exists)
        {
            return Conflict(new
            {
                message =
                    "Tên màu đã tồn tại."
            });
        }


        var color = new Color
        {
            Name = name,
            ColorCode =
                string.IsNullOrWhiteSpace(colorCode)
                    ? null
                    : colorCode,
            IsActive = true
        };


        _context.Colors.Add(color);

        await _context.SaveChangesAsync();


        return CreatedAtAction(
            nameof(GetColor),
            new { id = color.Id },
            new
            {
                color.Id,
                color.Name,
                color.ColorCode,
                VariantCount = 0,
                color.IsActive
            }
        );
    }


    // =========================================
    // PUT: api/colors/1
    // Permission: color.update
    // =========================================
    [HttpPut("{id}")]
    public async Task<IActionResult> UpdateColor(
        ulong id,
        [FromBody] ColorRequest request
    )
    {
        if (!HasPermission("color.update"))
        {
            return Forbid();
        }

        var color =
            await _context.Colors.FindAsync(id);

        if (color == null)
        {
            return NotFound(new
            {
                message =
                    "Không tìm thấy màu sắc."
            });
        }


        var name =
            request.Name?.Trim();

        var colorCode =
            request.ColorCode?.Trim().ToUpper();


        if (string.IsNullOrWhiteSpace(name))
        {
            return BadRequest(new
            {
                message =
                    "Tên màu không được để trống."
            });
        }


        if (
            !string.IsNullOrWhiteSpace(colorCode)
            && !System.Text.RegularExpressions.Regex.IsMatch(
                colorCode,
                @"^#[0-9A-F]{6}$"
            )
        )
        {
            return BadRequest(new
            {
                message =
                    "Mã màu HEX không hợp lệ. Ví dụ: #000000."
            });
        }


        var exists =
            await _context.Colors
                .AnyAsync(c =>
                    c.Id != id
                    && c.Name == name
                );

        if (exists)
        {
            return Conflict(new
            {
                message =
                    "Tên màu đã tồn tại."
            });
        }


        color.Name = name;

        color.ColorCode =
            string.IsNullOrWhiteSpace(colorCode)
                ? null
                : colorCode;


        await _context.SaveChangesAsync();


        return Ok(new
        {
            message =
                "Cập nhật màu sắc thành công."
        });
    }


    // =========================================
    // PATCH: api/colors/1/status
    // Permission: color.delete
    // =========================================
    [HttpPatch("{id}/status")]
    public async Task<IActionResult> ToggleStatus(
        ulong id
    )
    {
        if (!HasPermission("color.delete"))
        {
            return Forbid();
        }

        var color =
            await _context.Colors.FindAsync(id);

        if (color == null)
        {
            return NotFound(new
            {
                message =
                    "Không tìm thấy màu sắc."
            });
        }


        color.IsActive =
            !(color.IsActive ?? true);


        await _context.SaveChangesAsync();


        return Ok(new
        {
            message =
                "Cập nhật trạng thái màu sắc thành công.",

            id = color.Id,
            isActive = color.IsActive
        });
    }
}


public class ColorRequest
{
    public string? Name { get; set; }

    public string? ColorCode { get; set; }
}
