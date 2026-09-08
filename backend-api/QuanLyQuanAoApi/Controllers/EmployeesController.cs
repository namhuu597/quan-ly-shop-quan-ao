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
public class EmployeesController : ControllerBase
{
    private readonly AppDbContext _context;

    public EmployeesController(AppDbContext context)
    {
        _context = context;
    }


    // =========================================
    // HÀM HỖ TRỢ JWT / PERMISSION
    // =========================================

    private bool HasPermission(string permission)
    {
        // ADMIN được phép toàn bộ chức năng quản trị.
        // Các role khác vẫn phải có permission tương ứng trong JWT.
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
    // GET: api/employees
    // DANH SÁCH NHÂN VIÊN + THỐNG KÊ
    // Permission: user.read
    // =========================================
    [HttpGet]
    public async Task<IActionResult> GetEmployees(
        [FromQuery] string? keyword,
        [FromQuery] string? status
    )
    {
        if (!HasPermission("user.read"))
        {
            return Forbid();
        }


        var query =
            _context.Users
                .AsQueryable();


        if (!string.IsNullOrWhiteSpace(keyword))
        {
            keyword =
                keyword.Trim();

            query =
                query.Where(u =>
                    u.Username.Contains(keyword)
                    || u.FullName.Contains(keyword)
                    || u.Email.Contains(keyword)
                    || (
                        u.Phone != null
                        && u.Phone.Contains(keyword)
                    )
                );
        }


        if (!string.IsNullOrWhiteSpace(status))
        {
            status =
                status.Trim()
                    .ToLower();

            if (status == "active")
            {
                query =
                    query.Where(u =>
                        u.IsActive == true
                    );
            }
            else if (status == "inactive")
            {
                query =
                    query.Where(u =>
                        u.IsActive == false
                    );
            }
            else
            {
                return BadRequest(new
                {
                    message =
                        "Trạng thái tài khoản không hợp lệ."
                });
            }
        }


        var users =
            await query
                .OrderByDescending(u =>
                    u.Id
                )
                .Select(u => new
                {
                    u.Id,
                    u.Username,
                    u.Email,
                    u.FullName,
                    u.Phone,
                    u.IsActive,
                    u.CreatedAt,

                    RoleNames =
                        u.Roles
                            .OrderBy(r =>
                                r.Name
                            )
                            .Select(r =>
                                r.Name
                            )
                            .ToList()
                })
                .ToListAsync();


        var employees =
            users
                .Select(u => new
                {
                    u.Id,
                    u.Username,
                    u.Email,
                    u.FullName,
                    u.Phone,
                    u.IsActive,
                    u.CreatedAt,

                    Roles =
                        u.RoleNames.Count > 0
                            ? string.Join(
                                ", ",
                                u.RoleNames
                            )
                            : null
                })
                .ToList();


        var totalUsers =
            await _context.Users
                .CountAsync();


        var activeUsers =
            await _context.Users
                .CountAsync(u =>
                    u.IsActive == true
                );


        var inactiveUsers =
            await _context.Users
                .CountAsync(u =>
                    u.IsActive == false
                );


        return Ok(new
        {
            stats = new
            {
                totalUsers,
                activeUsers,
                inactiveUsers
            },

            employees
        });
    }


    // =========================================
    // GET: api/employees/form-options
    // ROLE DÙNG CHO FORM THÊM NHÂN VIÊN
    // Permission: user.create
    // =========================================
    [HttpGet("form-options")]
    public async Task<IActionResult> GetFormOptions()
    {
        if (!HasPermission("user.create"))
        {
            return Forbid();
        }


        var roles =
            await _context.Roles
                .Where(r =>
                    r.IsActive == true
                    && (
                        r.Name == "MANAGER"
                        || r.Name == "EMPLOYEE"
                    )
                )
                .OrderBy(r =>
                    r.Id
                )
                .Select(r => new
                {
                    r.Id,
                    r.Name,
                    r.Description
                })
                .ToListAsync();


        return Ok(new
        {
            roles
        });
    }


    // =========================================
    // POST: api/employees
    // TẠO NHÂN VIÊN + GÁN ROLE
    // Permission: user.create
    // =========================================
    [HttpPost]
    public async Task<IActionResult> CreateEmployee(
        [FromBody] EmployeeCreateRequest request
    )
    {
        if (!HasPermission("user.create"))
        {
            return Forbid();
        }


        var fullName =
            request.FullName?
                .Trim()
            ?? "";

        var username =
            request.Username?
                .Trim()
            ?? "";

        var email =
            request.Email?
                .Trim()
            ?? "";

        var phone =
            request.Phone?
                .Trim()
            ?? "";

        var password =
            request.Password
            ?? "";


        if (fullName == "")
        {
            return BadRequest(new
            {
                message =
                    "Vui lòng nhập họ tên nhân viên."
            });
        }


        if (username == "")
        {
            return BadRequest(new
            {
                message =
                    "Vui lòng nhập tên đăng nhập."
            });
        }


        if (
            username.Length < 4
            || username.Length > 30
            || !System.Text.RegularExpressions.Regex.IsMatch(
                username,
                @"^[a-zA-Z0-9_.]+$"
            )
        )
        {
            return BadRequest(new
            {
                message =
                    "Tên đăng nhập phải từ 4-30 ký tự và chỉ gồm chữ, số, dấu chấm hoặc gạch dưới."
            });
        }


        if (
            email != ""
            && !System.Net.Mail.MailAddress.TryCreate(
                email,
                out _
            )
        )
        {
            return BadRequest(new
            {
                message =
                    "Email không hợp lệ."
            });
        }


        if (password.Length < 6)
        {
            return BadRequest(new
            {
                message =
                    "Mật khẩu phải có ít nhất 6 ký tự."
            });
        }


        if (request.RoleId == 0)
        {
            return BadRequest(new
            {
                message =
                    "Vui lòng chọn vai trò."
            });
        }


        var role =
            await _context.Roles
                .FirstOrDefaultAsync(r =>
                    r.Id == request.RoleId
                    && r.IsActive == true
                    && (
                        r.Name == "MANAGER"
                        || r.Name == "EMPLOYEE"
                    )
                );


        if (role == null)
        {
            return BadRequest(new
            {
                message =
                    "Vai trò không hợp lệ."
            });
        }


        var usernameExists =
            await _context.Users
                .AnyAsync(u =>
                    u.Username == username
                );


        if (usernameExists)
        {
            return Conflict(new
            {
                message =
                    "Tên đăng nhập đã tồn tại."
            });
        }


        if (email != "")
        {
            var emailExists =
                await _context.Users
                    .AnyAsync(u =>
                        u.Email == email
                    );


            if (emailExists)
            {
                return Conflict(new
                {
                    message =
                        "Email đã được sử dụng."
                });
            }
        }


        await using var transaction =
            await _context.Database
                .BeginTransactionAsync();


        try
        {
            var user =
                new User
                {
                    Username =
                        username,

                    Email =
                        email,

                    PasswordHash =
                        BCrypt.Net.BCrypt.HashPassword(
                            password
                        ),

                    FullName =
                        fullName,

                    Phone =
                        phone == ""
                            ? null
                            : phone,

                    IsActive =
                        true,

                    CreatedAt =
                        DateTime.Now
                };


            user.Roles.Add(
                role
            );


            _context.Users.Add(
                user
            );


            await _context.SaveChangesAsync();

            await transaction.CommitAsync();


            return StatusCode(
                StatusCodes.Status201Created,
                new
                {
                    message =
                        "Thêm nhân viên thành công.",

                    id =
                        user.Id
                }
            );
        }
        catch (Exception ex)
        {
            await transaction.RollbackAsync();

            return StatusCode(
                StatusCodes.Status500InternalServerError,
                new
                {
                    message =
                        "Không thể thêm nhân viên.",

                    detail =
                        ex.Message
                }
            );
        }
    }


    // =========================================
    // GET: api/employees/1
    // CHI TIẾT NHÂN VIÊN + ROLE HIỆN TẠI
    // Permission: user.update
    // =========================================
    [HttpGet("{id}")]
    public async Task<IActionResult> GetEmployee(
        ulong id
    )
    {
        if (!HasPermission("user.update"))
        {
            return Forbid();
        }


        var employee =
            await _context.Users
                .Where(u =>
                    u.Id == id
                )
                .Select(u => new
                {
                    u.Id,
                    u.Username,
                    u.Email,
                    u.FullName,
                    u.Phone,
                    u.IsActive,

                    RoleId =
                        u.Roles
                            .Select(r =>
                                (ulong?)r.Id
                            )
                            .FirstOrDefault(),

                    RoleName =
                        u.Roles
                            .Select(r =>
                                r.Name
                            )
                            .FirstOrDefault()
                })
                .FirstOrDefaultAsync();


        if (employee == null)
        {
            return NotFound(new
            {
                message =
                    "Không tìm thấy nhân viên."
            });
        }


        return Ok(employee);
    }


    // =========================================
    // GET: api/employees/edit-options
    // ROLE DÙNG CHO FORM SỬA
    // Permission: user.update
    // =========================================
    [HttpGet("edit-options")]
    public async Task<IActionResult> GetEditOptions()
    {
        if (!HasPermission("user.update"))
        {
            return Forbid();
        }


        var isAdmin =
            User.IsInRole(
                "ADMIN"
            );


        var rolesQuery =
            _context.Roles
                .Where(r =>
                    r.IsActive == true
                );


        if (!isAdmin)
        {
            rolesQuery =
                rolesQuery.Where(r =>
                    r.Name != "ADMIN"
                );
        }


        var roles =
            await rolesQuery
                .OrderBy(r =>
                    r.Id
                )
                .Select(r => new
                {
                    r.Id,
                    r.Name,
                    r.Description
                })
                .ToListAsync();


        return Ok(new
        {
            roles
        });
    }


    // =========================================
    // PUT: api/employees/1
    // CẬP NHẬT NHÂN VIÊN + ROLE + MẬT KHẨU
    // Permission: user.update
    // =========================================
    [HttpPut("{id}")]
    public async Task<IActionResult> UpdateEmployee(
        ulong id,
        [FromBody] EmployeeUpdateRequest request
    )
    {
        if (!HasPermission("user.update"))
        {
            return Forbid();
        }


        var actorUserId =
            CurrentUserId();

        var actorIsAdmin =
            User.IsInRole(
                "ADMIN"
            );


        var employee =
            await _context.Users
                .Include(u =>
                    u.Roles
                )
                .FirstOrDefaultAsync(u =>
                    u.Id == id
                );


        if (employee == null)
        {
            return NotFound(new
            {
                message =
                    "Không tìm thấy nhân viên."
            });
        }


        var targetIsAdmin =
            employee.Roles.Any(r =>
                r.Name == "ADMIN"
            );


        if (
            targetIsAdmin
            && !actorIsAdmin
        )
        {
            return Forbid();
        }


        var fullName =
            request.FullName?
                .Trim()
            ?? "";

        var username =
            request.Username?
                .Trim()
            ?? "";

        var email =
            request.Email?
                .Trim()
            ?? "";

        var phone =
            request.Phone?
                .Trim()
            ?? "";

        var newPassword =
            request.NewPassword
            ?? "";


        if (fullName == "")
        {
            return BadRequest(new
            {
                message =
                    "Vui lòng nhập họ tên nhân viên."
            });
        }


        if (username == "")
        {
            return BadRequest(new
            {
                message =
                    "Vui lòng nhập tên đăng nhập."
            });
        }


        if (
            username.Length < 4
            || username.Length > 30
            || !System.Text.RegularExpressions.Regex.IsMatch(
                username,
                @"^[a-zA-Z0-9_.]+$"
            )
        )
        {
            return BadRequest(new
            {
                message =
                    "Tên đăng nhập không hợp lệ."
            });
        }


        if (
            email != ""
            && !System.Net.Mail.MailAddress.TryCreate(
                email,
                out _
            )
        )
        {
            return BadRequest(new
            {
                message =
                    "Email không hợp lệ."
            });
        }


        if (
            newPassword != ""
            && newPassword.Length < 6
        )
        {
            return BadRequest(new
            {
                message =
                    "Mật khẩu mới phải có ít nhất 6 ký tự."
            });
        }


        if (request.RoleId == 0)
        {
            return BadRequest(new
            {
                message =
                    "Vui lòng chọn vai trò."
            });
        }


        var usernameExists =
            await _context.Users
                .AnyAsync(u =>
                    u.Id != id
                    && u.Username == username
                );


        if (usernameExists)
        {
            return Conflict(new
            {
                message =
                    "Tên đăng nhập đã tồn tại."
            });
        }


        if (email != "")
        {
            var emailExists =
                await _context.Users
                    .AnyAsync(u =>
                        u.Id != id
                        && u.Email == email
                    );


            if (emailExists)
            {
                return Conflict(new
                {
                    message =
                        "Email đã được sử dụng."
                });
            }
        }


        var selectedRole =
            await _context.Roles
                .FirstOrDefaultAsync(r =>
                    r.Id == request.RoleId
                    && r.IsActive == true
                );


        if (selectedRole == null)
        {
            return BadRequest(new
            {
                message =
                    "Vai trò không hợp lệ."
            });
        }


        if (
            selectedRole.Name == "ADMIN"
            && !actorIsAdmin
        )
        {
            return Forbid();
        }


        if (
            actorUserId == id
            && actorIsAdmin
            && selectedRole.Name != "ADMIN"
        )
        {
            return BadRequest(new
            {
                message =
                    "Bạn không thể tự thay đổi vai trò ADMIN của chính mình."
            });
        }


        await using var transaction =
            await _context.Database
                .BeginTransactionAsync();


        try
        {
            employee.Username =
                username;

            employee.Email =
                email;

            employee.FullName =
                fullName;

            employee.Phone =
                phone == ""
                    ? null
                    : phone;


            if (newPassword != "")
            {
                employee.PasswordHash =
                    BCrypt.Net.BCrypt.HashPassword(
                        newPassword
                    );
            }


            employee.Roles.Clear();

            employee.Roles.Add(
                selectedRole
            );


            employee.UpdatedAt =
                DateTime.Now;


            await _context.SaveChangesAsync();

            await transaction.CommitAsync();


            return Ok(new
            {
                message =
                    "Cập nhật nhân viên thành công."
            });
        }
        catch (Exception ex)
        {
            await transaction.RollbackAsync();

            return StatusCode(
                StatusCodes.Status500InternalServerError,
                new
                {
                    message =
                        "Không thể cập nhật nhân viên.",

                    detail =
                        ex.Message
                }
            );
        }
    }


    // =========================================
    // PATCH: api/employees/1/status
    // KHÓA / KÍCH HOẠT TÀI KHOẢN
    // Permission: user.disable
    // =========================================
    [HttpPatch("{id}/status")]
    public async Task<IActionResult> ToggleEmployeeStatus(
        ulong id
    )
    {
        if (!HasPermission("user.disable"))
        {
            return Forbid();
        }


        if (id == 0)
        {
            return BadRequest(new
            {
                message =
                    "Tài khoản không hợp lệ."
            });
        }


        var actorUserId =
            CurrentUserId();

        var actorIsAdmin =
            User.IsInRole(
                "ADMIN"
            );


        if (
            actorUserId > 0
            && id == actorUserId
        )
        {
            return BadRequest(new
            {
                message =
                    "Bạn không thể khóa tài khoản đang đăng nhập."
            });
        }


        var user =
            await _context.Users
                .Include(u =>
                    u.Roles
                )
                .FirstOrDefaultAsync(u =>
                    u.Id == id
                );


        if (user == null)
        {
            return NotFound(new
            {
                message =
                    "Không tìm thấy tài khoản."
            });
        }


        if (
            user.Roles.Any(r =>
                r.Name == "ADMIN"
            )
            && !actorIsAdmin
        )
        {
            return Forbid();
        }


        user.IsActive =
            !(user.IsActive == true);

        user.UpdatedAt =
            DateTime.Now;


        await _context.SaveChangesAsync();


        return Ok(new
        {
            message =
                "Cập nhật trạng thái tài khoản thành công.",

            id =
                user.Id,

            isActive =
                user.IsActive == true
        });
    }
}


// =========================================
// DTO
// =========================================

public class EmployeeCreateRequest
{
    public string? FullName { get; set; }

    public string? Username { get; set; }

    public string? Email { get; set; }

    public string? Phone { get; set; }

    public string? Password { get; set; }

    public ulong RoleId { get; set; }
}


public class EmployeeUpdateRequest
{
    public string? FullName { get; set; }

    public string? Username { get; set; }

    public string? Email { get; set; }

    public string? Phone { get; set; }

    public ulong RoleId { get; set; }

    public string? NewPassword { get; set; }
}
