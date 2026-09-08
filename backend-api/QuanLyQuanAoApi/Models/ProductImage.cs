using System;
using System.Collections.Generic;

namespace QuanLyQuanAoApi.Models;

public partial class ProductImage
{
    public ulong Id { get; set; }

    public ulong ProductId { get; set; }

    public string ImagePath { get; set; } = null!;

    public bool IsPrimary { get; set; }

    public int SortOrder { get; set; }

    public DateTime CreatedAt { get; set; }

    public virtual Product Product { get; set; } = null!;
}
