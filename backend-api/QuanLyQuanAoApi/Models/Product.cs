using System;
using System.Collections.Generic;

namespace QuanLyQuanAoApi.Models;

public partial class Product
{
    public ulong Id { get; set; }

    public string ProductCode { get; set; } = null!;

    public ulong CategoryId { get; set; }

    public ulong? BrandId { get; set; }

    public string Name { get; set; } = null!;

    public string? Description { get; set; }

    public decimal BasePrice { get; set; }

    public string Status { get; set; } = null!;

    public DateTime CreatedAt { get; set; }

    public DateTime? UpdatedAt { get; set; }

    public virtual Brand? Brand { get; set; }

    public virtual Category Category { get; set; } = null!;

    public virtual ICollection<ProductImage> ProductImages { get; set; } = new List<ProductImage>();

    public virtual ICollection<ProductVariant> ProductVariants { get; set; } = new List<ProductVariant>();
}
