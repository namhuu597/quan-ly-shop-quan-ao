using System;
using System.Collections.Generic;

namespace QuanLyQuanAoApi.Models;

public partial class OrderStatusHistory
{
    public int Id { get; set; }

    public int OrderId { get; set; }

    public string? FromStatus { get; set; }

    public string ToStatus { get; set; } = null!;

    public string? Reason { get; set; }

    public int ChangedBy { get; set; }

    public DateTime CreatedAt { get; set; }
}
