using System;
using System.Collections.Generic;

namespace QuanLyQuanAoApi.Models;

public partial class AuditLog
{
    public ulong Id { get; set; }

    public ulong? UserId { get; set; }

    public string Action { get; set; } = null!;

    public string? EntityType { get; set; }

    public ulong? EntityId { get; set; }

    public string? Description { get; set; }

    public string? IpAddress { get; set; }

    public DateTime CreatedAt { get; set; }

    public virtual User? User { get; set; }
}
