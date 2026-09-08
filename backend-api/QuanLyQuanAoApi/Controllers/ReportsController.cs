using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Mvc;
using Microsoft.EntityFrameworkCore;
using QuanLyQuanAoApi.Data;

namespace QuanLyQuanAoApi.Controllers;

[ApiController]
[Route("api/[controller]")]
[Authorize]
public class ReportsController : ControllerBase
{
    private readonly AppDbContext _context;

    public ReportsController(AppDbContext context)
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
    // GET: api/reports/overview
    // Permission: report.read
    // =========================================
    [HttpGet("overview")]
    public async Task<IActionResult> GetOverview(
        [FromQuery] DateTime? fromDate,
        [FromQuery] DateTime? toDate
    )
    {
        if (!HasPermission("report.read"))
        {
            return Forbid();
        }

        var ordersQuery =
            _context.Orders
                .AsNoTracking()
                .AsQueryable();


        if (fromDate.HasValue)
        {
            var from =
                fromDate.Value.Date;

            ordersQuery =
                ordersQuery.Where(o =>
                    o.CreatedAt >= from
                );
        }


        if (toDate.HasValue)
        {
            var toExclusive =
                toDate.Value.Date.AddDays(1);

            ordersQuery =
                ordersQuery.Where(o =>
                    o.CreatedAt < toExclusive
                );
        }


        // =========================================
        // THỐNG KÊ TỔNG QUAN
        // =========================================

        var salesRevenue =
            await ordersQuery
                .Where(o =>
                    o.Status == "COMPLETED"
                )
                .SumAsync(o =>
                    (decimal?)o.TotalAmount
                )
            ?? 0m;


        var forfeitedDepositRevenue =
            await ordersQuery
                .Where(o =>
                    o.Status == "CANCELLED"
                    && o.CancelledBy == "CUSTOMER"
                    && o.DepositStatus == "FORFEITED"
                )
                .SumAsync(o =>
                    (decimal?)o.DepositAmount
                )
            ?? 0m;


        var pendingDeposit =
            await ordersQuery
                .Where(o =>
                    o.Status == "PENDING"
                    && o.DepositStatus == "PENDING"
                )
                .SumAsync(o =>
                    (decimal?)o.DepositAmount
                )
            ?? 0m;


        var refundedDeposit =
            await ordersQuery
                .Where(o =>
                    o.Status == "CANCELLED"
                    && o.CancelledBy == "SHOP"
                    && o.DepositStatus == "REFUNDED"
                )
                .SumAsync(o =>
                    (decimal?)o.DepositAmount
                )
            ?? 0m;


        var completedOrders =
            await ordersQuery.CountAsync(o =>
                o.Status == "COMPLETED"
            );


        var pendingOrders =
            await ordersQuery.CountAsync(o =>
                o.Status == "PENDING"
            );


        var cancelledOrders =
            await ordersQuery.CountAsync(o =>
                o.Status == "CANCELLED"
            );


        var totalRevenue =
            salesRevenue
            + forfeitedDepositRevenue;


        // =========================================
        // SẢN PHẨM ĐÃ BÁN
        // =========================================

        var completedOrderIds =
            ordersQuery
                .Where(o =>
                    o.Status == "COMPLETED"
                )
                .Select(o =>
                    o.Id
                );


        var totalSold =
            await _context.OrderDetails
                .AsNoTracking()
                .Where(od =>
                    completedOrderIds.Contains(
                        od.OrderId
                    )
                )
                .SumAsync(od =>
                    (int?)od.Quantity
                )
            ?? 0;


        // =========================================
        // KHÁCH HÀNG PHÁT SINH ĐƠN
        // =========================================

        var totalCustomers =
            await ordersQuery
                .Where(o =>
                    o.Status == "COMPLETED"
                    && o.CustomerId != null
                )
                .Select(o =>
                    o.CustomerId
                )
                .Distinct()
                .CountAsync();


        // =========================================
        // TOP SẢN PHẨM
        // =========================================

        var topProducts =
            await (
                from od in _context.OrderDetails.AsNoTracking()

                join o in ordersQuery
                    on od.OrderId equals o.Id

                join pv in _context.ProductVariants.AsNoTracking()
                    on od.ProductVariantId equals pv.Id

                join p in _context.Products.AsNoTracking()
                    on pv.ProductId equals p.Id

                where o.Status == "COMPLETED"

                group new
                {
                    od,
                    p
                }
                by new
                {
                    p.Id,
                    p.ProductCode,
                    ProductName = p.Name
                }
                into g

                orderby
                    g.Sum(x => x.od.Quantity) descending,
                    g.Sum(x => x.od.Subtotal) descending

                select new
                {
                    productCode =
                        g.Key.ProductCode,

                    productName =
                        g.Key.ProductName,

                    totalQuantity =
                        g.Sum(x =>
                            x.od.Quantity
                        ),

                    totalRevenue =
                        g.Sum(x =>
                            x.od.Subtotal
                        )
                }
            )
            .Take(5)
            .ToListAsync();


        // =========================================
        // TOP KHÁCH HÀNG
        // =========================================

        var topCustomers =
            await (
                from c in _context.Customers.AsNoTracking()

                join o in ordersQuery
                    on c.Id equals o.CustomerId

                where o.Status == "COMPLETED"

                group o
                by new
                {
                    c.Id,
                    c.CustomerCode,
                    c.FullName
                }
                into g

                orderby
                    g.Sum(x => x.TotalAmount) descending,
                    g.Count() descending

                select new
                {
                    customerCode =
                        g.Key.CustomerCode,

                    fullName =
                        g.Key.FullName,

                    totalOrders =
                        g.Count(),

                    totalSpent =
                        g.Sum(x =>
                            x.TotalAmount
                        )
                }
            )
            .Take(5)
            .ToListAsync();


        // =========================================
        // DOANH THU THEO NGÀY
        // =========================================

        var chartRows =
            await ordersQuery
                .Where(o =>
                    o.Status == "COMPLETED"
                    || (
                        o.Status == "CANCELLED"
                        && o.CancelledBy == "CUSTOMER"
                        && o.DepositStatus == "FORFEITED"
                    )
                )
                .GroupBy(o =>
                    o.CreatedAt.Date
                )
                .Select(g => new
                {
                    saleDate =
                        g.Key,

                    totalOrders =
                        g.Count(o =>
                            o.Status == "COMPLETED"
                        ),

                    revenue =
                        g.Sum(o =>
                            o.Status == "COMPLETED"
                                ? o.TotalAmount
                                : o.DepositAmount
                        )
                })
                .Where(x =>
                    x.revenue > 0
                )
                .OrderBy(x =>
                    x.saleDate
                )
                .ToListAsync();


        return Ok(new
        {
            summary = new
            {
                totalRevenue,
                salesRevenue,
                forfeitedDepositRevenue,
                pendingDeposit,
                refundedDeposit,
                completedOrders,
                pendingOrders,
                cancelledOrders
            },

            totalSold,
            totalCustomers,
            topProducts,
            topCustomers,
            chartRows
        });
    }
}
