using System;
using System.Collections.Generic;

namespace QuanLyQuanAoApi.Models;

public partial class ProductVariant
{
    public ulong Id { get; set; }

    public ulong ProductId { get; set; }

    public ulong SizeId { get; set; }

    public ulong ColorId { get; set; }

    public string Sku { get; set; } = null!;

    public decimal? Price { get; set; }

    public int StockQuantity { get; set; }

    public bool? IsActive { get; set; }

    public DateTime CreatedAt { get; set; }

    public DateTime? UpdatedAt { get; set; }

    public virtual Color Color { get; set; } = null!;

    public virtual ICollection<InventoryTransaction> InventoryTransactions { get; set; } = new List<InventoryTransaction>();

    public virtual ICollection<OrderDetail> OrderDetails { get; set; } = new List<OrderDetail>();

    public virtual Product Product { get; set; } = null!;

    public virtual Size Size { get; set; } = null!;
}
