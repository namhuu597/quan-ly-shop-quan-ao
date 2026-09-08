-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 07, 2026 at 06:02 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `quanlyquanao`
--

-- --------------------------------------------------------

--
-- Table structure for table `audit_logs`
--

CREATE TABLE `audit_logs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `entity_type` varchar(100) DEFAULT NULL,
  `entity_id` bigint(20) UNSIGNED DEFAULT NULL,
  `description` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `brands`
--

CREATE TABLE `brands` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `brands`
--

INSERT INTO `brands` (`id`, `name`, `description`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Nike', 'Thương hiệu Nike', 1, '2026-09-03 17:49:24', NULL),
(2, 'Adidas', 'Thương hiệu Adidas', 1, '2026-09-03 17:49:24', NULL),
(3, 'Uniqlo', 'Thương hiệu Uniqlo', 1, '2026-09-03 17:49:24', NULL),
(4, 'Local Brand', 'Thương hiệu thời trang nội địa', 1, '2026-09-03 17:49:24', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `description`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Áo thun', 'Các sản phẩm áo thun', 1, '2026-09-03 17:49:10', NULL),
(2, 'Áo sơ mi', 'Các sản phẩm áo sơ mi', 1, '2026-09-03 17:49:10', NULL),
(3, 'Áo khoác', 'Các sản phẩm áo khoác', 1, '2026-09-03 17:49:10', NULL),
(4, 'Quần jean', 'Các sản phẩm quần jean', 1, '2026-09-03 17:49:10', NULL),
(5, 'Quần short', 'Các sản phẩm quần short', 1, '2026-09-03 17:49:10', NULL),
(6, 'Váy', 'Các sản phẩm váy', 1, '2026-09-03 17:49:10', NULL),
(7, 'Phụ kiện', 'Phụ kiện thời trang', 1, '2026-09-03 17:49:10', NULL),
(8, 'Đồ thể thao', 'Các sản phẩm quần áo thể thao', 1, '2026-09-03 18:16:46', '2026-09-03 18:19:34');

-- --------------------------------------------------------

--
-- Table structure for table `colors`
--

CREATE TABLE `colors` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(50) NOT NULL,
  `color_code` varchar(20) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `colors`
--

INSERT INTO `colors` (`id`, `name`, `color_code`, `is_active`) VALUES
(1, 'Đen', '#000000', 1),
(2, 'Trắng', '#FFFFFF', 1),
(3, 'Đỏ', '#FF0000', 1),
(4, 'Xanh dương', '#0000FF', 1),
(5, 'Xám', '#808080', 1);

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `customer_code` varchar(30) NOT NULL,
  `full_name` varchar(150) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`id`, `customer_code`, `full_name`, `phone`, `email`, `address`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'KH0001', 'Phạm Gia Bảo', '0987654JQK', 'baoancut@gmail.com', 'London, Anh Quốc', 1, '2026-09-03 18:38:50', '2026-09-03 18:44:34'),
(2, 'KH0002', 'Hoàng Hữu Nam', '0999999999', 'vuabocphet@gmail.com', 'Cầu Nguyệt', 1, '2026-09-03 18:54:36', NULL),
(3, 'KH0003', 'C. Ronaldo', '5454513442', 'goat@gmail.com', 'Ả rập', 1, '2026-09-04 19:58:57', NULL),
(4, 'KH0004', 'Messi', '5454513443', 'bot@gmail.com', 'Mĩ', 1, '2026-09-04 20:29:18', NULL),
(5, 'KH0005', 'Trump', '000000000001', 'chum@gmail.com', 'White house', 1, '2026-09-04 20:34:34', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `inventory_transactions`
--

CREATE TABLE `inventory_transactions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `product_variant_id` bigint(20) UNSIGNED NOT NULL,
  `type` enum('IMPORT','SALE','ADJUSTMENT') NOT NULL,
  `quantity` int(11) NOT NULL,
  `stock_before` int(11) NOT NULL,
  `stock_after` int(11) NOT NULL,
  `order_id` bigint(20) UNSIGNED DEFAULT NULL,
  `created_by` bigint(20) UNSIGNED NOT NULL,
  `reason` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `inventory_transactions`
--

INSERT INTO `inventory_transactions` (`id`, `product_variant_id`, `type`, `quantity`, `stock_before`, `stock_after`, `order_id`, `created_by`, `reason`, `created_at`) VALUES
(1, 1, 'SALE', 1, 15, 14, 1, 1, 'Bán hàng - DH0001', '2026-09-03 19:10:18'),
(2, 1, 'IMPORT', 50, 14, 64, NULL, 1, 'Nhà Trắng', '2026-09-03 19:16:51'),
(3, 1, 'ADJUSTMENT', -4, 64, 60, NULL, 1, 'Thừa 4', '2026-09-03 19:18:31'),
(4, 1, 'SALE', 30, 60, 30, 2, 1, 'Bán hàng - DH0002', '2026-09-04 18:42:35'),
(5, 1, 'SALE', 30, 30, 0, 3, 1, 'Bán hàng - DH0003', '2026-09-04 18:42:42'),
(6, 2, 'SALE', 50, 150, 100, 4, 1, 'Bán hàng - DH0004', '2026-09-04 18:55:10'),
(7, 3, 'SALE', 50, 50, 0, 5, 1, 'Bán hàng - DH0005', '2026-09-04 18:57:37'),
(8, 2, 'SALE', 10, 100, 90, 6, 1, 'Bán hàng - DH0006', '2026-09-04 18:58:26'),
(9, 2, 'SALE', 50, 90, 40, 7, 1, 'Giữ hàng cho đơn chờ hoàn thành - DH0007', '2026-09-04 19:18:53'),
(10, 2, 'SALE', 10, 40, 30, 8, 1, 'Giữ hàng cho đơn chờ hoàn thành - DH0008', '2026-09-04 19:20:23'),
(11, 2, 'ADJUSTMENT', 10, 30, 40, 8, 1, 'Hoàn kho - khách không hoàn thành đơn DH0008. Lý do: Không thanh toán nốt', '2026-09-04 19:48:51'),
(12, 3, 'IMPORT', 500, 0, 500, NULL, 1, 'Old trafford', '2026-09-04 19:55:22'),
(13, 3, 'SALE', 50, 500, 450, 9, 1, 'Giữ hàng cho đơn chờ hoàn thành - DH0009', '2026-09-04 19:59:48'),
(14, 3, 'ADJUSTMENT', 50, 450, 500, 9, 1, 'Hoàn kho - khách không hoàn thành đơn DH0009. Lý do: Bùng', '2026-09-04 20:00:16'),
(15, 2, 'IMPORT', 460, 40, 500, NULL, 1, 'Old Trafford', '2026-09-04 20:33:16'),
(16, 2, 'SALE', 50, 500, 450, 10, 2, 'Giữ hàng cho đơn chờ hoàn thành - DH0010', '2026-09-04 20:35:50'),
(17, 3, 'SALE', 200, 500, 300, 11, 2, 'Giữ hàng cho đơn chờ hoàn thành - DH0011', '2026-09-04 20:37:03'),
(18, 3, 'ADJUSTMENT', 200, 300, 500, 11, 2, 'Hoàn kho - khách không hoàn thành đơn DH0011. Lý do: Dog', '2026-09-04 20:37:19'),
(19, 1, 'IMPORT', 500, 0, 500, NULL, 1, 'Argentina', '2026-09-04 23:05:49');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `order_code` varchar(30) NOT NULL,
  `customer_id` bigint(20) UNSIGNED DEFAULT NULL,
  `guest_name` varchar(150) DEFAULT NULL,
  `guest_phone` varchar(20) DEFAULT NULL,
  `created_by` bigint(20) UNSIGNED NOT NULL,
  `status` enum('PENDING','COMPLETED','CANCELLED') NOT NULL DEFAULT 'PENDING',
  `total_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `deposit_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `deposit_status` enum('PENDING','APPLIED','FORFEITED','REFUNDED') NOT NULL DEFAULT 'PENDING',
  `note` text DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `cancelled_at` datetime DEFAULT NULL,
  `cancelled_by` enum('CUSTOMER','SHOP') DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `order_code`, `customer_id`, `guest_name`, `guest_phone`, `created_by`, `status`, `total_amount`, `deposit_amount`, `deposit_status`, `note`, `completed_at`, `cancelled_at`, `cancelled_by`, `created_at`, `updated_at`) VALUES
(1, 'DH0001', 2, NULL, NULL, 1, 'COMPLETED', 350000.00, 0.00, 'PENDING', NULL, '2026-09-03 19:10:18', NULL, NULL, '2026-09-03 19:10:18', NULL),
(2, 'DH0002', 1, NULL, NULL, 1, 'COMPLETED', 10500000.00, 0.00, 'PENDING', NULL, '2026-09-04 18:42:35', NULL, NULL, '2026-09-04 18:42:35', NULL),
(3, 'DH0003', 1, NULL, NULL, 1, 'COMPLETED', 10500000.00, 0.00, 'PENDING', NULL, '2026-09-04 18:42:42', NULL, NULL, '2026-09-04 18:42:42', NULL),
(4, 'DH0004', 2, NULL, NULL, 1, 'COMPLETED', 25000000.00, 0.00, 'PENDING', NULL, '2026-09-04 18:55:10', NULL, NULL, '2026-09-04 18:55:10', NULL),
(5, 'DH0005', NULL, NULL, NULL, 1, 'COMPLETED', 25000000.00, 0.00, 'PENDING', NULL, '2026-09-04 18:57:37', NULL, NULL, '2026-09-04 18:57:37', NULL),
(6, 'DH0006', 2, NULL, NULL, 1, 'COMPLETED', 5000000.00, 0.00, 'PENDING', NULL, '2026-09-04 18:58:26', NULL, NULL, '2026-09-04 18:58:26', NULL),
(7, 'DH0007', NULL, NULL, NULL, 1, 'COMPLETED', 25000000.00, 5000000.00, 'APPLIED', NULL, '2026-09-04 19:47:39', NULL, NULL, '2026-09-04 19:18:53', '2026-09-04 19:47:39'),
(8, 'DH0008', NULL, NULL, NULL, 1, 'CANCELLED', 5000000.00, 0.00, 'FORFEITED', NULL, NULL, '2026-09-04 19:48:51', 'CUSTOMER', '2026-09-04 19:20:23', '2026-09-04 19:48:51'),
(9, 'DH0009', 3, NULL, NULL, 1, 'CANCELLED', 25000000.00, 5000000.00, 'FORFEITED', NULL, NULL, '2026-09-04 20:00:16', 'CUSTOMER', '2026-09-04 19:59:48', '2026-09-04 20:00:16'),
(10, 'DH0010', 5, NULL, NULL, 2, 'COMPLETED', 25000000.00, 5000000.00, 'APPLIED', NULL, '2026-09-04 20:36:12', NULL, NULL, '2026-09-04 20:35:50', '2026-09-04 20:36:12'),
(11, 'DH0011', 1, NULL, NULL, 2, 'CANCELLED', 100000000.00, 50000000.00, 'FORFEITED', NULL, NULL, '2026-09-04 20:37:19', 'CUSTOMER', '2026-09-04 20:37:03', '2026-09-04 20:37:19');

-- --------------------------------------------------------

--
-- Table structure for table `order_details`
--

CREATE TABLE `order_details` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `order_id` bigint(20) UNSIGNED NOT NULL,
  `product_variant_id` bigint(20) UNSIGNED NOT NULL,
  `quantity` int(11) NOT NULL,
  `unit_price` decimal(12,2) NOT NULL,
  `subtotal` decimal(12,2) NOT NULL
) ;

--
-- Dumping data for table `order_details`
--

INSERT INTO `order_details` (`id`, `order_id`, `product_variant_id`, `quantity`, `unit_price`, `subtotal`) VALUES
(1, 1, 1, 1, 350000.00, 350000.00),
(2, 2, 1, 30, 350000.00, 10500000.00),
(3, 3, 1, 30, 350000.00, 10500000.00),
(4, 4, 2, 50, 500000.00, 25000000.00),
(5, 5, 3, 50, 500000.00, 25000000.00),
(6, 6, 2, 10, 500000.00, 5000000.00),
(7, 7, 2, 50, 500000.00, 25000000.00),
(8, 8, 2, 10, 500000.00, 5000000.00),
(9, 9, 3, 50, 500000.00, 25000000.00),
(10, 10, 2, 50, 500000.00, 25000000.00),
(11, 11, 3, 200, 500000.00, 100000000.00);

-- --------------------------------------------------------

--
-- Table structure for table `order_status_history`
--

CREATE TABLE `order_status_history` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `from_status` enum('PENDING','COMPLETED','CANCELLED') DEFAULT NULL,
  `to_status` enum('PENDING','COMPLETED','CANCELLED') NOT NULL,
  `reason` varchar(500) DEFAULT NULL,
  `changed_by` int(11) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `order_status_history`
--

INSERT INTO `order_status_history` (`id`, `order_id`, `from_status`, `to_status`, `reason`, `changed_by`, `created_at`) VALUES
(1, 7, 'PENDING', 'COMPLETED', '', 1, '2026-09-04 19:47:39'),
(2, 8, 'PENDING', 'CANCELLED', 'Khách hàng không hoàn thành đơn. Không thanh toán nốt', 1, '2026-09-04 19:48:51'),
(3, 9, 'PENDING', 'CANCELLED', 'Khách hàng không hoàn thành đơn. Bùng', 1, '2026-09-04 20:00:16'),
(4, 10, 'PENDING', 'COMPLETED', '', 2, '2026-09-04 20:36:12'),
(5, 11, 'PENDING', 'CANCELLED', 'Khách hàng không hoàn thành đơn. Dog', 2, '2026-09-04 20:37:19');

-- --------------------------------------------------------

--
-- Table structure for table `permissions`
--

CREATE TABLE `permissions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `code` varchar(100) NOT NULL,
  `description` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `permissions`
--

INSERT INTO `permissions` (`id`, `code`, `description`) VALUES
(1, 'product.read', 'Xem và tìm kiếm sản phẩm'),
(2, 'product.create', 'Thêm sản phẩm'),
(3, 'product.update', 'Cập nhật sản phẩm'),
(4, 'product.delete', 'Ngừng kinh doanh sản phẩm'),
(5, 'category.read', 'Xem danh mục'),
(6, 'category.create', 'Thêm danh mục'),
(7, 'category.update', 'Cập nhật danh mục'),
(8, 'category.delete', 'Ngừng sử dụng danh mục'),
(9, 'brand.read', 'Xem thương hiệu'),
(10, 'brand.create', 'Thêm thương hiệu'),
(11, 'brand.update', 'Cập nhật thương hiệu'),
(12, 'brand.delete', 'Ngừng sử dụng thương hiệu'),
(13, 'size.read', 'Xem size'),
(14, 'size.create', 'Thêm size'),
(15, 'size.update', 'Cập nhật size'),
(16, 'size.delete', 'Ngừng sử dụng size'),
(17, 'color.read', 'Xem màu sắc'),
(18, 'color.create', 'Thêm màu sắc'),
(19, 'color.update', 'Cập nhật màu sắc'),
(20, 'color.delete', 'Ngừng sử dụng màu sắc'),
(21, 'customer.read', 'Xem và tìm kiếm khách hàng'),
(22, 'customer.create', 'Thêm khách hàng'),
(23, 'customer.update', 'Cập nhật khách hàng'),
(24, 'order.create', 'Tạo đơn hàng'),
(25, 'order.read', 'Xem tất cả đơn hàng'),
(26, 'order.read.own', 'Xem đơn hàng do mình tạo'),
(27, 'order.complete', 'Hoàn thành đơn hàng'),
(28, 'order.cancel', 'Hủy đơn hàng'),
(29, 'inventory.read', 'Xem tồn kho'),
(30, 'inventory.import', 'Nhập hàng'),
(31, 'inventory.adjust', 'Điều chỉnh tồn kho'),
(32, 'report.read', 'Xem dashboard và báo cáo'),
(33, 'user.read', 'Xem tài khoản'),
(34, 'user.create', 'Tạo tài khoản'),
(35, 'user.update', 'Cập nhật tài khoản'),
(36, 'user.disable', 'Khóa hoặc mở khóa tài khoản'),
(37, 'role.read', 'Xem role'),
(38, 'role.create', 'Tạo role'),
(39, 'role.update', 'Cập nhật role'),
(40, 'role.assign', 'Gán role cho user'),
(41, 'permission.read', 'Xem permission'),
(42, 'permission.assign', 'Gán permission cho role');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `product_code` varchar(30) NOT NULL,
  `category_id` bigint(20) UNSIGNED NOT NULL,
  `brand_id` bigint(20) UNSIGNED DEFAULT NULL,
  `name` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `base_price` decimal(12,2) NOT NULL DEFAULT 0.00,
  `status` enum('ACTIVE','INACTIVE') NOT NULL DEFAULT 'ACTIVE',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `product_code`, `category_id`, `brand_id`, `name`, `description`, `base_price`, `status`, `created_at`, `updated_at`) VALUES
(1, 'SP001', 8, 1, 'Set quần + áo đội tuyển Argentina', 'Áo có chữ kí của Messi + thêm 5m', 350000.00, 'ACTIVE', '2026-09-03 17:50:12', '2026-09-04 18:33:58'),
(4, 'SP002', 8, 2, 'Áo đấu MU', NULL, 500000.00, 'ACTIVE', '2026-09-04 18:54:23', NULL),
(5, 'SP003', 1, 4, 'Áo nhiều sao', NULL, 50000.00, 'ACTIVE', '2026-09-04 23:11:36', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `product_images`
--

CREATE TABLE `product_images` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `product_id` bigint(20) UNSIGNED NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `is_primary` tinyint(1) NOT NULL DEFAULT 0,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `product_images`
--

INSERT INTO `product_images` (`id`, `product_id`, `image_path`, `is_primary`, `sort_order`, `created_at`) VALUES
(1, 1, 'uploads/products/product_6a995354224f50.57673401.jpg', 1, 0, '2026-09-03 18:00:36'),
(2, 4, 'uploads/products/product_6a9ab1700184f3.17555134.jpg', 1, 0, '2026-09-04 18:54:24'),
(3, 4, 'uploads/products/product_6a9ab17002fe12.74823605.png', 0, 1, '2026-09-04 18:54:24'),
(4, 5, 'uploads/products/product_6a9aedb8731607.57396438.webp', 1, 0, '2026-09-04 23:11:36');

-- --------------------------------------------------------

--
-- Table structure for table `product_variants`
--

CREATE TABLE `product_variants` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `product_id` bigint(20) UNSIGNED NOT NULL,
  `size_id` bigint(20) UNSIGNED NOT NULL,
  `color_id` bigint(20) UNSIGNED NOT NULL,
  `sku` varchar(50) NOT NULL,
  `price` decimal(12,2) DEFAULT NULL,
  `stock_quantity` int(11) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ;

--
-- Dumping data for table `product_variants`
--

INSERT INTO `product_variants` (`id`, `product_id`, `size_id`, `color_id`, `sku`, `price`, `stock_quantity`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 1, 2, 1, 'HD-BLK-M', NULL, 500, 1, '2026-09-03 17:50:12', '2026-09-04 23:05:49'),
(2, 4, 1, 3, 'MU-S-DO', NULL, 450, 1, '2026-09-04 18:54:23', '2026-09-04 20:35:50'),
(3, 4, 1, 2, 'MU-S-TRANG', NULL, 500, 1, '2026-09-04 18:54:23', '2026-09-04 20:37:19'),
(4, 5, 1, 1, 'ANS-S-DEN', NULL, 500, 1, '2026-09-04 23:11:36', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(50) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `name`, `description`, `is_active`, `created_at`) VALUES
(1, 'ADMIN', 'Quản trị viên hệ thống', 1, '2026-09-03 16:49:18'),
(2, 'MANAGER', 'Quản lý cửa hàng', 1, '2026-09-03 16:49:18'),
(3, 'EMPLOYEE', 'Nhân viên bán hàng', 1, '2026-09-03 16:49:18');

-- --------------------------------------------------------

--
-- Table structure for table `role_permissions`
--

CREATE TABLE `role_permissions` (
  `role_id` bigint(20) UNSIGNED NOT NULL,
  `permission_id` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `role_permissions`
--

INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES
(1, 1),
(1, 2),
(1, 3),
(1, 4),
(1, 5),
(1, 6),
(1, 7),
(1, 8),
(1, 9),
(1, 10),
(1, 11),
(1, 12),
(1, 13),
(1, 14),
(1, 15),
(1, 16),
(1, 17),
(1, 18),
(1, 19),
(1, 20),
(1, 21),
(1, 22),
(1, 23),
(1, 24),
(1, 25),
(1, 26),
(1, 27),
(1, 28),
(1, 29),
(1, 30),
(1, 31),
(1, 32),
(1, 33),
(1, 34),
(1, 35),
(1, 36),
(1, 37),
(1, 38),
(1, 39),
(1, 40),
(1, 41),
(1, 42),
(2, 1),
(2, 2),
(2, 3),
(2, 5),
(2, 6),
(2, 7),
(2, 9),
(2, 10),
(2, 11),
(2, 13),
(2, 14),
(2, 15),
(2, 17),
(2, 18),
(2, 19),
(2, 21),
(2, 22),
(2, 23),
(2, 24),
(2, 25),
(2, 27),
(2, 28),
(2, 29),
(2, 30),
(2, 31),
(2, 32),
(2, 33),
(3, 1),
(3, 21),
(3, 22),
(3, 23),
(3, 24),
(3, 26),
(3, 29);

-- --------------------------------------------------------

--
-- Table structure for table `sizes`
--

CREATE TABLE `sizes` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(20) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sizes`
--

INSERT INTO `sizes` (`id`, `name`, `is_active`) VALUES
(1, 'S', 1),
(2, 'M', 1),
(3, 'L', 1),
(4, 'XL', 1),
(5, 'XXL', 1),
(6, '3XL', 1);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `full_name` varchar(150) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password_hash`, `full_name`, `phone`, `avatar`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'admin', 'admin@gmail.com', '$2y$10$259OuTo5Sc.DXsABiwkrL.MOKAJK7wt7Lfd8gDmG4lctUrWUtEatK', 'Quản trị viên', NULL, NULL, 1, '2026-09-03 17:04:19', NULL),
(2, 'vip001', 'uynam22@gmail.com', '$2y$10$AXX97N0fWM9WcAqe4GTgjuHJxmfZOYgJAciUjy1vg3cZ1ixFX17xi', 'Bảo cute', '0965459873', NULL, 1, '2026-09-03 19:29:37', NULL),
(3, 'dangcap1', 'dangcap@bayb.com', '$2y$10$QTaGCExdU0DRlnUXwyA7e.o64mj0/IWA9w4VvD3wFENiHic2/swVy', 'TenHag', '0512312312', NULL, 1, '2026-09-04 20:38:26', NULL),
(4, 'vip002', 'dangcap@bayb.comm', '$2y$10$0aA6c7TbhH3U5VKF5BRCseNSww8hBIx7RsGwdWdKA6XNU7vK9icvK', 'Boi Boi Boi', '0512312313', NULL, 1, '2026-09-04 23:05:05', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `user_roles`
--

CREATE TABLE `user_roles` (
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `role_id` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `user_roles`
--

INSERT INTO `user_roles` (`user_id`, `role_id`) VALUES
(1, 1),
(2, 3),
(3, 2),
(4, 3);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_audit_user` (`user_id`),
  ADD KEY `idx_audit_created_at` (`created_at`);

--
-- Indexes for table `brands`
--
ALTER TABLE `brands`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `colors`
--
ALTER TABLE `colors`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `customer_code` (`customer_code`),
  ADD UNIQUE KEY `phone` (`phone`),
  ADD KEY `idx_customers_name` (`full_name`);

--
-- Indexes for table `inventory_transactions`
--
ALTER TABLE `inventory_transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_inventory_order` (`order_id`),
  ADD KEY `fk_inventory_user` (`created_by`),
  ADD KEY `idx_inventory_variant` (`product_variant_id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `order_code` (`order_code`),
  ADD KEY `idx_orders_customer` (`customer_id`),
  ADD KEY `idx_orders_created_by` (`created_by`),
  ADD KEY `idx_orders_status` (`status`),
  ADD KEY `idx_orders_created_at` (`created_at`);

--
-- Indexes for table `order_details`
--
ALTER TABLE `order_details`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_order_details_variant` (`product_variant_id`),
  ADD KEY `idx_order_details_order` (`order_id`);

--
-- Indexes for table `order_status_history`
--
ALTER TABLE `order_status_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_order_status_history_order` (`order_id`,`created_at`);

--
-- Indexes for table `permissions`
--
ALTER TABLE `permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `product_code` (`product_code`),
  ADD KEY `fk_products_brand` (`brand_id`),
  ADD KEY `idx_products_name` (`name`),
  ADD KEY `idx_products_category` (`category_id`);

--
-- Indexes for table `product_images`
--
ALTER TABLE `product_images`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_product_images_product` (`product_id`);

--
-- Indexes for table `product_variants`
--
ALTER TABLE `product_variants`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `sku` (`sku`),
  ADD UNIQUE KEY `uq_product_variant` (`product_id`,`size_id`,`color_id`),
  ADD KEY `fk_variants_size` (`size_id`),
  ADD KEY `fk_variants_color` (`color_id`),
  ADD KEY `idx_variants_product` (`product_id`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `role_permissions`
--
ALTER TABLE `role_permissions`
  ADD PRIMARY KEY (`role_id`,`permission_id`),
  ADD KEY `fk_role_permissions_permission` (`permission_id`);

--
-- Indexes for table `sizes`
--
ALTER TABLE `sizes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `user_roles`
--
ALTER TABLE `user_roles`
  ADD PRIMARY KEY (`user_id`,`role_id`),
  ADD KEY `fk_user_roles_role` (`role_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `brands`
--
ALTER TABLE `brands`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `colors`
--
ALTER TABLE `colors`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `inventory_transactions`
--
ALTER TABLE `inventory_transactions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `order_details`
--
ALTER TABLE `order_details`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `order_status_history`
--
ALTER TABLE `order_status_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `permissions`
--
ALTER TABLE `permissions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `product_images`
--
ALTER TABLE `product_images`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `product_variants`
--
ALTER TABLE `product_variants`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `sizes`
--
ALTER TABLE `sizes`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD CONSTRAINT `fk_audit_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `inventory_transactions`
--
ALTER TABLE `inventory_transactions`
  ADD CONSTRAINT `fk_inventory_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_inventory_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `fk_inventory_variant` FOREIGN KEY (`product_variant_id`) REFERENCES `product_variants` (`id`);

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `fk_orders_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_orders_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `order_details`
--
ALTER TABLE `order_details`
  ADD CONSTRAINT `fk_order_details_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_order_details_variant` FOREIGN KEY (`product_variant_id`) REFERENCES `product_variants` (`id`);

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `fk_products_brand` FOREIGN KEY (`brand_id`) REFERENCES `brands` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_products_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`);

--
-- Constraints for table `product_images`
--
ALTER TABLE `product_images`
  ADD CONSTRAINT `fk_product_images_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `product_variants`
--
ALTER TABLE `product_variants`
  ADD CONSTRAINT `fk_variants_color` FOREIGN KEY (`color_id`) REFERENCES `colors` (`id`),
  ADD CONSTRAINT `fk_variants_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  ADD CONSTRAINT `fk_variants_size` FOREIGN KEY (`size_id`) REFERENCES `sizes` (`id`);

--
-- Constraints for table `role_permissions`
--
ALTER TABLE `role_permissions`
  ADD CONSTRAINT `fk_role_permissions_permission` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_role_permissions_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_roles`
--
ALTER TABLE `user_roles`
  ADD CONSTRAINT `fk_user_roles_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_user_roles_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
