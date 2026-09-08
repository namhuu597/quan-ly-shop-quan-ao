using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Mvc;
using Microsoft.EntityFrameworkCore;
using QuanLyQuanAoApi.Data;
using QuanLyQuanAoApi.Models;

namespace QuanLyQuanAoApi.Controllers;

[ApiController]
[Route("api/[controller]")]
[Authorize]
public class CustomersController : ControllerBase
{
    private readonly AppDbContext _context;

    public CustomersController(AppDbContext context)
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
    // GET: api/customers
    // =========================================
    [HttpGet]
    public async Task<ActionResult<IEnumerable<object>>> GetCustomers(
        [FromQuery] string? keyword
    )
    {
        if (!HasPermission("customer.read"))
        {
            return Forbid();
        }


        var query =
            _context.Customers
                .AsQueryable();


        if (!string.IsNullOrWhiteSpace(keyword))
        {
            keyword = keyword.Trim();

            query = query.Where(c =>
                c.CustomerCode.Contains(keyword)
                || c.FullName.Contains(keyword)
                || (c.Phone != null && c.Phone.Contains(keyword))
                || (c.Email != null && c.Email.Contains(keyword))
            );
        }


        var customers = await query
            .OrderByDescending(c => c.CreatedAt)
            .Select(c => new
            {
                c.Id,
                c.CustomerCode,
                c.FullName,
                c.Phone,
                c.Email,
                c.Address,

                OrderCount =
                    c.Orders.Count,

                CompletedOrders =
                    c.Orders.Count(o =>
                        o.Status == "COMPLETED"
                    ),

                TotalSpent =
                    c.Orders
                        .Where(o =>
                            o.Status == "COMPLETED"
                        )
                        .Sum(o =>
                            (decimal?)o.TotalAmount
                        )
                        ?? 0,

                c.IsActive,
                c.CreatedAt
            })
            .ToListAsync();


        return Ok(customers);
    }


    // =========================================
    // GET: api/customers/1
    // =========================================
    [HttpGet("{id}")]
    public async Task<ActionResult<object>> GetCustomer(
        ulong id
    )
    {
        if (!HasPermission("customer.read"))
        {
            return Forbid();
        }


        var customer =
            await _context.Customers
                .Where(c => c.Id == id)
                .Select(c => new
                {
                    c.Id,
                    c.CustomerCode,
                    c.FullName,
                    c.Phone,
                    c.Email,
                    c.Address,

                    OrderCount =
                        c.Orders.Count,

                    CompletedOrders =
                        c.Orders.Count(o =>
                            o.Status == "COMPLETED"
                        ),

                    TotalSpent =
                        c.Orders
                            .Where(o =>
                                o.Status == "COMPLETED"
                            )
                            .Sum(o =>
                                (decimal?)o.TotalAmount
                            )
                            ?? 0,

                    c.IsActive,
                    c.CreatedAt
                })
                .FirstOrDefaultAsync();


        if (customer == null)
        {
            return NotFound(new
            {
                message =
                    "Không tìm thấy khách hàng."
            });
        }


        return Ok(customer);
    }


    // =========================================
    // GET: api/customers/1/orders
    // =========================================
    [HttpGet("{id}/orders")]
    public async Task<IActionResult> GetCustomerOrders(
        ulong id
    )
    {
        if (!HasPermission("customer.read"))
        {
            return Forbid();
        }


        var customerExists =
            await _context.Customers
                .AnyAsync(c => c.Id == id);


        if (!customerExists)
        {
            return NotFound(new
            {
                message =
                    "Không tìm thấy khách hàng."
            });
        }


        var orders =
            await (
                from o in _context.Orders

                join u in _context.Users
                    on o.CreatedBy equals u.Id

                where o.CustomerId == id

                orderby o.CreatedAt descending

                select new
                {
                    o.Id,
                    o.OrderCode,
                    o.Status,
                    o.TotalAmount,
                    o.CreatedAt,
                    o.CompletedAt,

                    EmployeeName =
                        u.FullName
                }
            )
            .ToListAsync();


        return Ok(orders);
    }


    // =========================================
    // POST: api/customers
    // =========================================
    [HttpPost]
    public async Task<IActionResult> CreateCustomer(
        [FromBody] CustomerRequest request
    )
    {
        if (!HasPermission("customer.create"))
        {
            return Forbid();
        }


        var fullName =
            request.FullName?.Trim();

        var phone =
            request.Phone?.Trim();

        var email =
            request.Email?.Trim();

        var address =
            request.Address?.Trim();


        if (string.IsNullOrWhiteSpace(fullName))
        {
            return BadRequest(new
            {
                message =
                    "Vui lòng nhập họ tên khách hàng."
            });
        }


        if (
            !string.IsNullOrWhiteSpace(email)
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
            !string.IsNullOrWhiteSpace(phone)
            && !System.Text.RegularExpressions.Regex.IsMatch(
                phone,
                @"^(0|\+84)[0-9]{9,10}$"
            )
        )
        {
            return BadRequest(new
            {
                message =
                    "Số điện thoại không hợp lệ."
            });
        }


        if (
            !string.IsNullOrWhiteSpace(phone)
            && !System.Text.RegularExpressions.Regex.IsMatch(
                phone,
                @"^(0|\+84)[0-9]{9,10}$"
            )
        )
        {
            return BadRequest(new
            {
                message =
                    "Số điện thoại không hợp lệ."
            });
        }


        if (!string.IsNullOrWhiteSpace(phone))
        {
            var phoneExists =
                await _context.Customers
                    .AnyAsync(c =>
                        c.Phone == phone
                    );


            if (phoneExists)
            {
                return Conflict(new
                {
                    message =
                        "Số điện thoại này đã tồn tại."
                });
            }
        }


        var lastId =
            await _context.Customers
                .OrderByDescending(c => c.Id)
                .Select(c => (ulong?)c.Id)
                .FirstOrDefaultAsync()
            ?? 0;


        var nextNumber =
            lastId + 1;


        var customerCode =
            "KH"
            + nextNumber
                .ToString()
                .PadLeft(4, '0');


        var customer =
            new Customer
            {
                CustomerCode =
                    customerCode,

                FullName =
                    fullName,

                Phone =
                    string.IsNullOrWhiteSpace(phone)
                        ? null
                        : phone,

                Email =
                    string.IsNullOrWhiteSpace(email)
                        ? null
                        : email,

                Address =
                    string.IsNullOrWhiteSpace(address)
                        ? null
                        : address,

                IsActive =
                    true,

                CreatedAt =
                    DateTime.Now
            };


        _context.Customers.Add(
            customer
        );


        await _context.SaveChangesAsync();


        return CreatedAtAction(
            nameof(GetCustomer),
            new
            {
                id = customer.Id
            },
            new
            {
                customer.Id,
                customer.CustomerCode,
                customer.FullName,
                customer.Phone,
                customer.Email,
                customer.Address,
                customer.IsActive,
                customer.CreatedAt
            }
        );
    }


    // =========================================
    // PUT: api/customers/1
    // =========================================
    [HttpPut("{id}")]
    public async Task<IActionResult> UpdateCustomer(
        ulong id,
        [FromBody] CustomerRequest request
    )
    {
        if (!HasPermission("customer.update"))
        {
            return Forbid();
        }


        var customer =
            await _context.Customers
                .FindAsync(id);


        if (customer == null)
        {
            return NotFound(new
            {
                message =
                    "Không tìm thấy khách hàng."
            });
        }


        var fullName =
            request.FullName?.Trim();

        var phone =
            request.Phone?.Trim();

        var email =
            request.Email?.Trim();

        var address =
            request.Address?.Trim();


        if (string.IsNullOrWhiteSpace(fullName))
        {
            return BadRequest(new
            {
                message =
                    "Vui lòng nhập họ tên khách hàng."
            });
        }


        if (
            !string.IsNullOrWhiteSpace(email)
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


        if (!string.IsNullOrWhiteSpace(phone))
        {
            var phoneExists =
                await _context.Customers
                    .AnyAsync(c =>
                        c.Id != id
                        && c.Phone == phone
                    );


            if (phoneExists)
            {
                return Conflict(new
                {
                    message =
                        "Số điện thoại này đã được sử dụng bởi khách hàng khác."
                });
            }
        }


        customer.FullName =
            fullName;

        customer.Phone =
            string.IsNullOrWhiteSpace(phone)
                ? null
                : phone;

        customer.Email =
            string.IsNullOrWhiteSpace(email)
                ? null
                : email;

        customer.Address =
            string.IsNullOrWhiteSpace(address)
                ? null
                : address;

        customer.IsActive =
            request.IsActive;


        await _context.SaveChangesAsync();


        return Ok(new
        {
            message =
                "Cập nhật khách hàng thành công."
        });
    }
}


// =========================================
// DTO
// =========================================
public class CustomerRequest
{
    public string? FullName { get; set; }

    public string? Phone { get; set; }

    public string? Email { get; set; }

    public string? Address { get; set; }

    public bool IsActive { get; set; } = true;
}