using System.IdentityModel.Tokens.Jwt;
using System.Security.Claims;
using System.Text;
using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Mvc;
using Microsoft.EntityFrameworkCore;
using Microsoft.IdentityModel.Tokens;
using QuanLyQuanAoApi.Data;

namespace QuanLyQuanAoApi.Controllers;

[ApiController]
[Route("api/[controller]")]
public class AuthController : ControllerBase
{
    private readonly AppDbContext _context;
    private readonly IConfiguration _configuration;

    public AuthController(
        AppDbContext context,
        IConfiguration configuration
    )
    {
        _context =
            context;

        _configuration =
            configuration;
    }


    // =========================================
    // POST: api/auth/login
    // =========================================
    [AllowAnonymous]
    [HttpPost("login")]
    public async Task<IActionResult> Login(
        [FromBody] LoginRequest request
    )
    {
        var username =
            request.Username?
                .Trim()
            ?? "";

        var password =
            request.Password
            ?? "";


        if (
            username == ""
            || password == ""
        )
        {
            return BadRequest(new
            {
                message =
                    "Vui lòng nhập đầy đủ tên đăng nhập và mật khẩu."
            });
        }


        var user =
            await _context.Users
                .Include(u =>
                    u.Roles
                )
                .ThenInclude(r =>
                    r.Permissions
                )
                .FirstOrDefaultAsync(u =>
                    u.Username == username
                );


        if (user == null)
        {
            return Unauthorized(new
            {
                message =
                    "Tên đăng nhập hoặc mật khẩu không đúng."
            });
        }


        if (user.IsActive != true)
        {
            return StatusCode(
                StatusCodes.Status403Forbidden,
                new
                {
                    message =
                        "Tài khoản đã bị khóa."
                }
            );
        }


        var passwordOk =
            BCrypt.Net.BCrypt.Verify(
                password,
                user.PasswordHash
            );


        if (!passwordOk)
        {
            return Unauthorized(new
            {
                message =
                    "Tên đăng nhập hoặc mật khẩu không đúng."
            });
        }


        var roles =
            user.Roles
                .Where(r =>
                    r.IsActive == true
                )
                .Select(r =>
                    r.Name
                )
                .Distinct()
                .OrderBy(x =>
                    x
                )
                .ToList();


        var permissions =
            user.Roles
                .Where(r =>
                    r.IsActive == true
                )
                .SelectMany(r =>
                    r.Permissions
                )
                .Select(p =>
                    p.Code
                )
                .Distinct()
                .OrderBy(x =>
                    x
                )
                .ToList();


        var tokenResult =
            CreateJwtToken(
                user.Id,
                user.Username,
                user.FullName,
                roles,
                permissions
            );


        return Ok(new
        {
            id =
                user.Id,

            username =
                user.Username,

            fullName =
                user.FullName,

            email =
                user.Email,

            roles,
            permissions,

            accessToken =
                tokenResult.Token,

            expiresAt =
                tokenResult.ExpiresAt
        });
    }


    // =========================================
    // GET: api/auth/me
    // TEST TOKEN
    // =========================================
    [Authorize]
    [HttpGet("me")]
    public IActionResult Me()
    {
        var userId =
            User.FindFirst(
                ClaimTypes.NameIdentifier
            )?.Value;

        var username =
            User.FindFirst(
                ClaimTypes.Name
            )?.Value;

        var fullName =
            User.FindFirst(
                "full_name"
            )?.Value;

        var roles =
            User.FindAll(
                ClaimTypes.Role
            )
            .Select(c =>
                c.Value
            )
            .ToList();

        var permissions =
            User.FindAll(
                "permission"
            )
            .Select(c =>
                c.Value
            )
            .ToList();


        return Ok(new
        {
            userId,
            username,
            fullName,
            roles,
            permissions
        });
    }


    // =========================================
    // TẠO JWT
    // =========================================
    private (
        string Token,
        DateTime ExpiresAt
    ) CreateJwtToken(
        ulong userId,
        string username,
        string fullName,
        List<string> roles,
        List<string> permissions
    )
    {
        var jwtKey =
            _configuration["Jwt:Key"]
            ?? throw new InvalidOperationException(
                "Jwt:Key chưa được cấu hình."
            );

        var issuer =
            _configuration["Jwt:Issuer"]
            ?? throw new InvalidOperationException(
                "Jwt:Issuer chưa được cấu hình."
            );

        var audience =
            _configuration["Jwt:Audience"]
            ?? throw new InvalidOperationException(
                "Jwt:Audience chưa được cấu hình."
            );

        var expireMinutes =
            _configuration.GetValue<int?>(
                "Jwt:ExpireMinutes"
            )
            ?? 480;


        var expiresAt =
            DateTime.UtcNow.AddMinutes(
                expireMinutes
            );


        var claims =
            new List<Claim>
            {
                new Claim(
                    ClaimTypes.NameIdentifier,
                    userId.ToString()
                ),

                new Claim(
                    ClaimTypes.Name,
                    username
                ),

                new Claim(
                    "full_name",
                    fullName
                )
            };


        foreach (var role in roles)
        {
            claims.Add(
                new Claim(
                    ClaimTypes.Role,
                    role
                )
            );
        }


        foreach (
            var permission in permissions
        )
        {
            claims.Add(
                new Claim(
                    "permission",
                    permission
                )
            );
        }


        var key =
            new SymmetricSecurityKey(
                Encoding.UTF8.GetBytes(
                    jwtKey
                )
            );


        var credentials =
            new SigningCredentials(
                key,
                SecurityAlgorithms.HmacSha256
            );


        var jwt =
            new JwtSecurityToken(
                issuer:
                    issuer,

                audience:
                    audience,

                claims:
                    claims,

                notBefore:
                    DateTime.UtcNow,

                expires:
                    expiresAt,

                signingCredentials:
                    credentials
            );


        var token =
            new JwtSecurityTokenHandler()
                .WriteToken(
                    jwt
                );


        return (
            token,
            expiresAt
        );
    }
}


public class LoginRequest
{
    public string? Username { get; set; }

    public string? Password { get; set; }
}
