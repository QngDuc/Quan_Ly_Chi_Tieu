-- ============================================
-- SmartSpending - Default Data (Money Lover Style)
-- Version: 6.0.0
-- Date: December 6, 2025
-- Description: Dữ liệu mặc định (danh mục theo phong cách Money Lover)
-- ============================================

USE `quan_ly_chi_tieu`;

SET FOREIGN_KEY_CHECKS = 0;

-- ============================================
-- KHOẢN CHI (EXPENSE CATEGORIES)
-- ============================================

-- 1. Ăn uống
INSERT INTO `categories` (`name`, `type`, `color`, `icon`, `is_default`, `parent_id`) VALUES
('Ăn uống', 'expense', '#e74c3c', 'fa-utensils', 1, NULL);

-- 2. Hóa đơn & Tiện ích (Parent + Children)
INSERT INTO `categories` (`name`, `type`, `color`, `icon`, `is_default`, `parent_id`) VALUES
('Hóa đơn & Tiện ích', 'expense', '#3498db', 'fa-file-invoice-dollar', 1, NULL);
SET @parent_hoadon = LAST_INSERT_ID();

INSERT INTO `categories` (`name`, `type`, `color`, `icon`, `is_default`, `parent_id`) VALUES
('Hóa đơn điện thoại', 'expense', '#3498db', 'fa-mobile-alt', 1, @parent_hoadon),
('Hóa đơn nước', 'expense', '#3498db', 'fa-tint', 1, @parent_hoadon),
('Hóa đơn điện', 'expense', '#3498db', 'fa-bolt', 1, @parent_hoadon),
('Hóa đơn gas', 'expense', '#3498db', 'fa-fire', 1, @parent_hoadon),
('Hóa đơn TV', 'expense', '#3498db', 'fa-tv', 1, @parent_hoadon),
('Hóa đơn internet', 'expense', '#3498db', 'fa-wifi', 1, @parent_hoadon),
('Thuê nhà', 'expense', '#3498db', 'fa-home', 1, @parent_hoadon),
('Hóa đơn tiện ích khác', 'expense', '#3498db', 'fa-file-invoice', 1, @parent_hoadon);

-- 3. Mua sắm (Parent + Children)
INSERT INTO `categories` (`name`, `type`, `color`, `icon`, `is_default`, `parent_id`) VALUES
('Mua sắm', 'expense', '#9b59b6', 'fa-shopping-bag', 1, NULL);
SET @parent_muasam = LAST_INSERT_ID();

INSERT INTO `categories` (`name`, `type`, `color`, `icon`, `is_default`, `parent_id`) VALUES
('Đồ dùng cá nhân', 'expense', '#9b59b6', 'fa-user', 1, @parent_muasam),
('Đồ gia dụng', 'expense', '#9b59b6', 'fa-couch', 1, @parent_muasam),
('Làm đẹp', 'expense', '#9b59b6', 'fa-spa', 1, @parent_muasam);

-- 4. Gia đình (Parent + Children)
INSERT INTO `categories` (`name`, `type`, `color`, `icon`, `is_default`, `parent_id`) VALUES
('Gia đình', 'expense', '#1abc9c', 'fa-users', 1, NULL);
SET @parent_giadinh = LAST_INSERT_ID();

INSERT INTO `categories` (`name`, `type`, `color`, `icon`, `is_default`, `parent_id`) VALUES
('Sửa & trang trí nhà', 'expense', '#1abc9c', 'fa-tools', 1, @parent_giadinh),
('Dịch vụ gia đình', 'expense', '#1abc9c', 'fa-broom', 1, @parent_giadinh),
('Vật nuôi', 'expense', '#1abc9c', 'fa-paw', 1, @parent_giadinh);

-- 5. Di chuyển (Parent + Children)
INSERT INTO `categories` (`name`, `type`, `color`, `icon`, `is_default`, `parent_id`) VALUES
('Di chuyển', 'expense', '#34495e', 'fa-car', 1, NULL);
SET @parent_dichuyen = LAST_INSERT_ID();

INSERT INTO `categories` (`name`, `type`, `color`, `icon`, `is_default`, `parent_id`) VALUES
('Bảo dưỡng xe', 'expense', '#34495e', 'fa-wrench', 1, @parent_dichuyen);

-- 6. Sức khỏe (Parent + Children)
INSERT INTO `categories` (`name`, `type`, `color`, `icon`, `is_default`, `parent_id`) VALUES
('Sức khỏe', 'expense', '#e67e22', 'fa-heartbeat', 1, NULL);
SET @parent_suckhoe = LAST_INSERT_ID();

INSERT INTO `categories` (`name`, `type`, `color`, `icon`, `is_default`, `parent_id`) VALUES
('Khám sức khỏe', 'expense', '#e67e22', 'fa-stethoscope', 1, @parent_suckhoe),
('Thể dục thể thao', 'expense', '#e67e22', 'fa-running', 1, @parent_suckhoe);

-- 7. Giáo dục
INSERT INTO `categories` (`name`, `type`, `color`, `icon`, `is_default`, `parent_id`) VALUES
('Giáo dục', 'expense', '#2c3e50', 'fa-graduation-cap', 1, NULL);

-- 8. Giải trí (Parent + Children)
INSERT INTO `categories` (`name`, `type`, `color`, `icon`, `is_default`, `parent_id`) VALUES
('Giải trí', 'expense', '#f39c12', 'fa-gamepad', 1, NULL);
SET @parent_giaitri = LAST_INSERT_ID();

INSERT INTO `categories` (`name`, `type`, `color`, `icon`, `is_default`, `parent_id`) VALUES
('Dịch vụ trực tuyến', 'expense', '#f39c12', 'fa-globe', 1, @parent_giaitri),
('Vui - chơi', 'expense', '#f39c12', 'fa-smile', 1, @parent_giaitri);

-- 9. Quà tặng & Quyên góp
INSERT INTO `categories` (`name`, `type`, `color`, `icon`, `is_default`, `parent_id`) VALUES
('Quà tặng & Quyên góp', 'expense', '#e91e63', 'fa-gift', 1, NULL);

-- 10. Bảo hiểm
INSERT INTO `categories` (`name`, `type`, `color`, `icon`, `is_default`, `parent_id`) VALUES
('Bảo hiểm', 'expense', '#607d8b', 'fa-shield-alt', 1, NULL);

-- 11. Đầu tư
INSERT INTO `categories` (`name`, `type`, `color`, `icon`, `is_default`, `parent_id`) VALUES
('Đầu tư', 'expense', '#2980b9', 'fa-chart-line', 1, NULL);

-- 12. Các chi phí khác
INSERT INTO `categories` (`name`, `type`, `color`, `icon`, `is_default`, `parent_id`) VALUES
('Các chi phí khác', 'expense', '#95a5a6', 'fa-ellipsis-h', 1, NULL);

-- 13. Tiền chuyến đi
INSERT INTO `categories` (`name`, `type`, `color`, `icon`, `is_default`, `parent_id`) VALUES
('Tiền chuyến đi', 'expense', '#16a085', 'fa-plane', 1, NULL);

-- 14. Trả lãi
INSERT INTO `categories` (`name`, `type`, `color`, `icon`, `is_default`, `parent_id`) VALUES
('Trả lãi', 'expense', '#c0392b', 'fa-percent', 1, NULL);

-- ============================================
-- KHOẢN THU (INCOME CATEGORIES)
-- ============================================

-- 1. Lương
INSERT INTO `categories` (`name`, `type`, `color`, `icon`, `is_default`, `parent_id`) VALUES
('Lương', 'income', '#27ae60', 'fa-money-bill-wave', 1, NULL);

-- 2. Thu nhập khác
INSERT INTO `categories` (`name`, `type`, `color`, `icon`, `is_default`, `parent_id`) VALUES
('Thu nhập khác', 'income', '#2ecc71', 'fa-coins', 1, NULL);

-- 3. Tiền chuyển đến
INSERT INTO `categories` (`name`, `type`, `color`, `icon`, `is_default`, `parent_id`) VALUES
('Tiền chuyển đến', 'income', '#1abc9c', 'fa-arrow-down', 1, NULL);

-- 4. Thu lãi
INSERT INTO `categories` (`name`, `type`, `color`, `icon`, `is_default`, `parent_id`) VALUES
('Thu lãi', 'income', '#16a085', 'fa-percentage', 1, NULL);

-- ============================================
-- NỢ/CHO VAY (DEBT/LOAN CATEGORIES)
-- ============================================

-- 1. Cho vay (income type - tiền ra)
INSERT INTO `categories` (`name`, `type`, `color`, `icon`, `is_default`, `parent_id`) VALUES
('Cho vay', 'expense', '#f1c40f', 'fa-hand-holding-usd', 1, NULL);

-- 2. Trả nợ (expense type - tiền ra)
INSERT INTO `categories` (`name`, `type`, `color`, `icon`, `is_default`, `parent_id`) VALUES
('Trả nợ', 'expense', '#e74c3c', 'fa-hand-holding-usd', 1, NULL);

-- 3. Đi vay (income type - tiền vào)
INSERT INTO `categories` (`name`, `type`, `color`, `icon`, `is_default`, `parent_id`) VALUES
('Đi vay', 'income', '#3498db', 'fa-handshake', 1, NULL);

-- 4. Thu nợ (income type - tiền vào)
INSERT INTO `categories` (`name`, `type`, `color`, `icon`, `is_default`, `parent_id`) VALUES
('Thu nợ', 'income', '#9b59b6', 'fa-receipt', 1, NULL);

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================
-- SUMMARY
-- ============================================
-- Total Categories: 41
--
-- Khoản Chi (Expense): 30 categories
--   - Parent: 14 categories
--   - Children: 16 categories
--
-- Khoản Thu (Income): 7 categories (all parent)
--   - Lương, Thu nhập khác, Tiền chuyển đến, Thu lãi
--   - Đi vay, Thu nợ
--
-- Nợ/Cho vay (Mixed): 4 categories
--   - Expense: Cho vay, Trả nợ (tiền ra)
--   - Income: Đi vay, Thu nợ (tiền vào)
-- ============================================
