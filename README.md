# HỆ THỐNG QUẢN LÝ SHOP QUẦN ÁO GIA BẢO BOUTIQUE

## 1. Giới thiệu

Đây là đồ án môn **Phân tích & Thiết kế Hệ thống Thông tin** với đề tài:

**Hệ thống quản lý shop quần áo Gia Bảo Boutique**

Hệ thống hỗ trợ quản lý toàn bộ các nghiệp vụ chính của một cửa hàng quần áo như:

- Quản lý sản phẩm
- Quản lý danh mục
- Quản lý Size
- Quản lý màu sắc
- Quản lý biến thể sản phẩm
- Quản lý hình ảnh sản phẩm
- Quản lý tồn kho
- Nhập kho và điều chỉnh tồn kho
- Quản lý khách hàng
- Quản lý đơn hàng
- Quản lý tiền cọc
- Quản lý nhân viên
- Phân quyền người dùng
- Báo cáo doanh thu
- Theo dõi lịch sử thay đổi

Hệ thống được thiết kế theo hướng tách biệt giữa giao diện và xử lý nghiệp vụ.

---

# 2. Kiến trúc hệ thống

Kiến trúc tổng quát:

```text
Người dùng
    |
    v
Web Browser
    |
    v
PHP Frontend
    |
    | REST API + JSON + JWT
    v
ASP.NET Core Web API
    |
    | Entity Framework Core
    v
MariaDB / MySQL