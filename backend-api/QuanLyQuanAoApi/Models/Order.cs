using System;
using System.Collections.Generic;

namespace QuanLyQuanAoApi.Models;

public partial class Order
{
    public ulong Id { get; set; }

    public string OrderCode { get; set; } = null!;

    public ulong? CustomerId { get; set; }

    public string? GuestName { get; set; }

    public string? GuestPhone { get; set; }

    public ulong CreatedBy { get; set; }

    public string Status { get; set; } = null!;

    public decimal TotalAmount { get; set; }

    public decimal DepositAmount { get; set; }

    public string DepositStatus { get; set; } = null!;

    public string? Note { get; set; }

    public DateTime? CompletedAt { get; set; }

    public DateTime? CancelledAt { get; set; }

    public string? CancelledBy { get; set; }

    public DateTime CreatedAt { get; set; }

    public DateTime? UpdatedAt { get; set; }

    public virtual User CreatedByNavigation { get; set; } = null!;

    public virtual Customer? Customer { get; set; }

    public virtual ICollection<InventoryTransaction> InventoryTransactions { get; set; } = new List<InventoryTransaction>();

    public virtual ICollection<OrderDetail> OrderDetails { get; set; } = new List<OrderDetail>();
}
