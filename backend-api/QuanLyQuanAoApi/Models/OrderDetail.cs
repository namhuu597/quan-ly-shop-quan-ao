using System;
using System.Collections.Generic;

namespace QuanLyQuanAoApi.Models;

public partial class OrderDetail
{
    public ulong Id { get; set; }

    public ulong OrderId { get; set; }

    public ulong ProductVariantId { get; set; }

    public int Quantity { get; set; }

    public decimal UnitPrice { get; set; }

    public decimal Subtotal { get; set; }

    public virtual Order Order { get; set; } = null!;

    public virtual ProductVariant ProductVariant { get; set; } = null!;
}
