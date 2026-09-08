using System;
using System.Collections.Generic;

namespace QuanLyQuanAoApi.Models;

public partial class InventoryTransaction
{
    public ulong Id { get; set; }

    public ulong ProductVariantId { get; set; }

    public string Type { get; set; } = null!;

    public int Quantity { get; set; }

    public int StockBefore { get; set; }

    public int StockAfter { get; set; }

    public ulong? OrderId { get; set; }

    public ulong CreatedBy { get; set; }

    public string? Reason { get; set; }

    public DateTime CreatedAt { get; set; }

    public virtual User CreatedByNavigation { get; set; } = null!;

    public virtual Order? Order { get; set; }

    public virtual ProductVariant ProductVariant { get; set; } = null!;
}
