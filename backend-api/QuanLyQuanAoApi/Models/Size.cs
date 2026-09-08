using System;
using System.Collections.Generic;

namespace QuanLyQuanAoApi.Models;

public partial class Size
{
    public ulong Id { get; set; }

    public string Name { get; set; } = null!;

    public bool? IsActive { get; set; }

    public virtual ICollection<ProductVariant> ProductVariants { get; set; } = new List<ProductVariant>();
}
