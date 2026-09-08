using Microsoft.AspNetCore.Mvc;
using QuanLyQuanAoApi.Data;

namespace QuanLyQuanAoApi.Controllers;

[ApiController]
[Route("api/[controller]")]
public class TestController : ControllerBase
{
    private readonly AppDbContext _context;

    public TestController(AppDbContext context)
    {
        _context = context;
    }

    [HttpGet("database")]
    public async Task<IActionResult> TestDatabase()
    {
        bool canConnect =
            await _context.Database.CanConnectAsync();

        if (!canConnect)
        {
            return StatusCode(
                500,
                new
                {
                    message = "Không kết nối được database"
                }
            );
        }

        return Ok(
            new
            {
                message = "Kết nối database quanlyquanao thành công"
            }
        );
    }
}