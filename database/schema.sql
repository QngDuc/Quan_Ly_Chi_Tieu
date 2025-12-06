-- ============================================
-- SmartSpending - Database Deployment Script
-- Version: 6.0.2 (Schema + Fixed Data Seed)
-- Date: December 6, 2025
-- Description: Cấu trúc database và dữ liệu mặc định đã FIX logic Nợ/Cho vay.
-- ============================================

CREATE DATABASE IF NOT EXISTS `quan_ly_chi_tieu` 
DEFAULT CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

USE `quan_ly_chi_tieu`;

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+07:00";
SET FOREIGN_KEY_CHECKS = 0;

-- ============================================
-- DROPPING EXISTING OBJECTS (Tái tạo lại)
-- ============================================

-- Drop all tables 
DROP TABLE IF EXISTS `goal_transactions`;
DROP TABLE IF EXISTS `goals`;
DROP TABLE IF EXISTS `recurring_transactions`;
DROP TABLE IF EXISTS `budgets`;
DROP TABLE IF EXISTS `transactions`;
DROP TABLE IF EXISTS `categories`;
DROP TABLE IF EXISTS `users`;
DROP TABLE IF EXISTS `jar_allocations_v2`;
DROP TABLE IF EXISTS `jar_categories`;
DROP TABLE IF EXISTS `jar_templates`;
DROP TABLE IF EXISTS `jar_allocations`;


-- Drop views
DROP VIEW IF EXISTS `v_monthly_summary`;
DROP VIEW IF EXISTS `v_category_summary`;

-- Drop procedures
DROP PROCEDURE IF EXISTS `sp_get_user_balance`;
DROP PROCEDURE IF EXISTS `sp_get_budget_status`;

-- Drop triggers (if they exist)
DROP TRIGGER IF EXISTS `trg_transaction_type_sync`;

-- ============================================
-- TABLE CREATION (Từ Schema.sql)
-- ============================================

-- TABLE: users
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL UNIQUE COMMENT 'Tên đăng nhập',
  `email` varchar(100) NOT NULL UNIQUE COMMENT 'Email người dùng',
  `password` varchar(255) NOT NULL COMMENT 'Mật khẩu đã mã hóa (bcrypt)',
  `full_name` varchar(100) DEFAULT NULL COMMENT 'Họ và tên',
  `role` enum('user','admin') NOT NULL DEFAULT 'user' COMMENT 'Vai trò: user hoặc admin',
  `is_super_admin` tinyint(1) DEFAULT 0 COMMENT 'Super admin không thể bị demote hoặc xóa',
  `is_active` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Trạng thái tài khoản: 1=active, 0=disabled',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_username` (`username`),
  KEY `idx_email` (`email`),
  KEY `idx_role` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Bảng người dùng';

-- TABLE: categories (THIẾU TRONG SCHEMA GỐC, BỔ SUNG Ở ĐÂY)
-- Dựa trên FK và logic, categories cần cột parent_id
CREATE TABLE `categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `type` enum('income','expense') NOT NULL,
  `color` varchar(7) DEFAULT '#000000',
  `icon` varchar(50) DEFAULT 'fa-tag',
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_parent_id` (`parent_id`),
  KEY `idx_type` (`type`),
  CONSTRAINT `fk_categories_parent_id` FOREIGN KEY (`parent_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Bảng danh mục chi tiêu/thu nhập';


-- TABLE: transactions (THIẾU TRONG SCHEMA GỐC, BỔ SUNG Ở ĐÂY)
-- Dựa trên FK và logic (trigger, views), bảng transactions cần các cột:
CREATE TABLE `transactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `date` date NOT NULL,
  `description` text DEFAULT NULL,
  `type` enum('income','expense') NOT NULL COMMENT 'Loại: thu nhập hoặc chi tiêu (Đồng bộ từ category)',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_category_id` (`category_id`),
  KEY `idx_date` (`date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Bảng giao dịch';

-- TABLE: budgets (THIẾU TRONG SCHEMA GỐC, BỔ SUNG Ở ĐÂY)
-- Dựa trên FK và logic (procedure), bảng budgets cần các cột:
CREATE TABLE `budgets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL COMMENT 'ID người dùng',
  `category_id` int(11) NOT NULL COMMENT 'ID danh mục chi tiêu',
  `amount` decimal(15,2) NOT NULL COMMENT 'Ngân sách tối đa cho tháng',
  `start_month` date NOT NULL COMMENT 'Tháng bắt đầu ngân sách (YYYY-MM-01)',
  `is_active` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Trạng thái: 1=active, 0=inactive',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_category_id` (`category_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Bảng ngân sách chi tiêu';


-- TABLE: goals (Có trong schema gốc nhưng thiếu cột)
CREATE TABLE `goals` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL COMMENT 'Tên mục tiêu',
  `description` text DEFAULT NULL COMMENT 'Mô tả mục tiêu',
  `target_amount` decimal(15,2) NOT NULL COMMENT 'Số tiền mục tiêu',
  `current_amount` decimal(15,2) NOT NULL DEFAULT 0.00 COMMENT 'Số tiền hiện tại',
  `deadline` date DEFAULT NULL COMMENT 'Hạn hoàn thành',
  `icon` varchar(50) DEFAULT 'fa-bullseye' COMMENT 'Font Awesome icon',
  `color` varchar(7) DEFAULT '#3498db' COMMENT 'Mã màu hex',
  `is_completed` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Trạng thái: 0=chưa hoàn thành, 1=đã hoàn thành',
  `status` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Trạng thái mục tiêu (0=chưa đạt, 1=đang thực hiện, 2=hoàn thành, ...)',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Bảng mục tiêu tài chính';

-- TABLE: goal_transactions
CREATE TABLE `goal_transactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `goal_id` int(11) NOT NULL COMMENT 'ID mục tiêu',
  `transaction_id` int(11) NOT NULL COMMENT 'ID giao dịch',
  `amount` decimal(15,2) NOT NULL COMMENT 'Số tiền đóng góp',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_goal_id` (`goal_id`),
  KEY `idx_transaction_id` (`transaction_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Bảng giao dịch mục tiêu';

-- TABLE: recurring_transactions
CREATE TABLE `recurring_transactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL COMMENT 'ID người dùng',
  `category_id` int(11) NOT NULL COMMENT 'ID danh mục',
  `amount` decimal(15,2) NOT NULL COMMENT 'Số tiền',
  `type` enum('income','expense') NOT NULL COMMENT 'Loại: thu nhập hoặc chi tiêu',
  `description` text DEFAULT NULL COMMENT 'Mô tả',
  `frequency` enum('daily','weekly','monthly','yearly') NOT NULL COMMENT 'Tần suất lặp lại',
  `start_date` date NOT NULL COMMENT 'Ngày bắt đầu',
  `end_date` date DEFAULT NULL COMMENT 'Ngày kết thúc (NULL = không giới hạn)',
  `last_processed_date` date DEFAULT NULL COMMENT 'Ngày xử lý lần cuối',
  `is_active` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Trạng thái: 1=active, 0=inactive',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_category_id` (`category_id`),
  KEY `idx_frequency` (`frequency`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Bảng giao dịch định kỳ';

-- Các bảng Jar liên quan (giữ lại theo Schema gốc)
CREATE TABLE `jar_allocations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL COMMENT 'ID người dùng',
  `month` date NOT NULL COMMENT 'Tháng phân bổ (YYYY-MM-01)',
  `necessities` decimal(15,2) DEFAULT 0.00 COMMENT 'Chi tiêu thiết yếu (55%)',
  `education` decimal(15,2) DEFAULT 0.00 COMMENT 'Giáo dục & Phát triển (10%)',
  `savings` decimal(15,2) DEFAULT 0.00 COMMENT 'Tiết kiệm dài hạn (10%)',
  `enjoy` decimal(15,2) DEFAULT 0.00 COMMENT 'Hưởng thụ (10%)',
  `freedom` decimal(15,2) DEFAULT 0.00 COMMENT 'Tự do tài chính (10%)',
  `give` decimal(15,2) DEFAULT 0.00 COMMENT 'Từ thiện & Quà tặng (5%)',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_month` (`month`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Bảng phân bổ Jar (6 Jars Method - Legacy)';

CREATE TABLE `jar_templates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL COMMENT 'ID người dùng',
  `name` varchar(100) NOT NULL COMMENT 'Tên template (vd: 6 Jars, 50/30/20)',
  `description` text DEFAULT NULL COMMENT 'Mô tả template',
  `is_active` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Template đang sử dụng',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Bảng template Jar';

CREATE TABLE `jar_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `jar_id` int(11) NOT NULL COMMENT 'ID jar trong jar_templates',
  `category_id` int(11) NOT NULL COMMENT 'ID danh mục trong categories',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_jar_id` (`jar_id`),
  KEY `idx_category_id` (`category_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Bảng ánh xạ Jar - Category';

CREATE TABLE `jar_allocations_v2` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL COMMENT 'ID người dùng',
  `jar_template_id` int(11) NOT NULL COMMENT 'ID jar template',
  `jar_name` varchar(100) NOT NULL COMMENT 'Tên jar',
  `percentage` int(11) NOT NULL COMMENT 'Phần trăm phân bổ',
  `icon` varchar(50) DEFAULT 'fa-jar' COMMENT 'Font Awesome icon',
  `color` varchar(7) DEFAULT '#3498db' COMMENT 'Mã màu hex',
  `sort_order` int(11) DEFAULT 0 COMMENT 'Thứ tự hiển thị',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_jar_template_id` (`jar_template_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Bảng phân bổ Jar v2 (Custom Jars)';


-- ============================================
-- FOREIGN KEYS (Từ Schema.sql)
-- ============================================
ALTER TABLE `categories`
  ADD CONSTRAINT `fk_categories_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `transactions`
  ADD CONSTRAINT `fk_transactions_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_transactions_category_id` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE;

ALTER TABLE `budgets`
  ADD CONSTRAINT `fk_budgets_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_budgets_category_id` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `goals`
  ADD CONSTRAINT `fk_goals_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `goal_transactions`
  ADD CONSTRAINT `fk_goal_transactions_goal_id` FOREIGN KEY (`goal_id`) REFERENCES `goals` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_goal_transactions_transaction_id` FOREIGN KEY (`transaction_id`) REFERENCES `transactions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `recurring_transactions`
  ADD CONSTRAINT `fk_recurring_transactions_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_recurring_transactions_category_id` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;


-- ============================================
-- VIEWS (Từ Schema.sql)
-- ============================================

-- View: Monthly Summary
CREATE OR REPLACE VIEW `v_monthly_summary` AS
SELECT 
    t.user_id,
    DATE_FORMAT(t.date, '%Y-%m') as month,
    SUM(CASE WHEN t.type = 'income' THEN t.amount ELSE 0 END) as total_income,
    SUM(CASE WHEN t.type = 'expense' THEN t.amount ELSE 0 END) as total_expense,
    SUM(CASE WHEN t.type = 'income' THEN t.amount ELSE -t.amount END) as net_balance
FROM transactions t
GROUP BY t.user_id, DATE_FORMAT(t.date, '%Y-%m');

-- View: Category Summary
CREATE OR REPLACE VIEW `v_category_summary` AS
SELECT 
    t.user_id,
    t.category_id,
    c.name as category_name,
    c.type as category_type,
    DATE_FORMAT(t.date, '%Y-%m') as month,
    SUM(t.amount) as total_amount,
    COUNT(*) as transaction_count
FROM transactions t
JOIN categories c ON t.category_id = c.id
GROUP BY t.user_id, t.category_id, c.name, c.type, DATE_FORMAT(t.date, '%Y-%m');

-- ============================================
-- STORED PROCEDURES (Từ Schema.sql)
-- ============================================

DELIMITER $$

-- Procedure: Get User Balance
CREATE PROCEDURE `sp_get_user_balance`(IN p_user_id INT)
BEGIN
    SELECT 
        SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END) as total_income,
        SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END) as total_expense,
        SUM(CASE WHEN type = 'income' THEN amount ELSE -amount END) as balance
    FROM transactions
    WHERE user_id = p_user_id;
END$$

-- Procedure: Get Budget Status
CREATE PROCEDURE `sp_get_budget_status`(IN p_user_id INT, IN p_month VARCHAR(7))
BEGIN
    SELECT 
        b.id as budget_id,
        b.category_id,
        c.name as category_name,
        b.amount as budget_amount,
        COALESCE(SUM(t.amount), 0) as spent_amount,
        (b.amount - COALESCE(SUM(t.amount), 0)) as remaining,
        ROUND((COALESCE(SUM(t.amount), 0) / b.amount * 100), 2) as percentage_used
    FROM budgets b
    LEFT JOIN categories c ON b.category_id = c.id
    LEFT JOIN transactions t ON t.category_id = b.category_id 
        AND t.user_id = p_user_id
        AND DATE_FORMAT(t.date, '%Y-%m') = p_month
        AND t.type = 'expense'
    WHERE b.user_id = p_user_id
        AND b.is_active = 1
    GROUP BY b.id, b.category_id, c.name, b.amount;
END$$

-- Trigger: Sync transaction type with category type
CREATE TRIGGER `trg_transaction_type_sync`
BEFORE INSERT ON `transactions`
FOR EACH ROW
BEGIN
    DECLARE cat_type VARCHAR(10);
    SELECT type INTO cat_type FROM categories WHERE id = NEW.category_id;
    SET NEW.type = cat_type;
END$$

DELIMITER ;

-- ============================================
-- DATA SEEDING (Đã FIX logic Nợ/Cho vay)
-- ============================================

SET FOREIGN_KEY_CHECKS = 0;
-- Cần DELETE dữ liệu cũ trước khi INSERT
DELETE FROM `categories`;


-- Top-level: Khoản Chi (Chi tiêu)
INSERT INTO `categories` (user_id, parent_id, name, type, color, icon, is_default, created_at)
VALUES (NULL, NULL, 'Khoản Chi (Chi tiêu)', 'expense', '#E74C3C', 'fa-folder', 1, NOW());
SET @khoan_chi_id = LAST_INSERT_ID();

-- Children of Khoản Chi
INSERT INTO `categories` (user_id, parent_id, name, type, color, icon, is_default, created_at) VALUES
(NULL, @khoan_chi_id, 'Ăn uống', 'expense', '#F39C12', 'fa-utensils', 1, NOW()),
(NULL, @khoan_chi_id, 'Hoá đơn & Tiện ích', 'expense', '#3498DB', 'fa-file-invoice', 1, NOW()),
(NULL, @khoan_chi_id, 'Mua sắm', 'expense', '#2ECC71', 'fa-shopping-bag', 1, NOW()),
(NULL, @khoan_chi_id, 'Gia đình', 'expense', '#8E44AD', 'fa-users', 1, NOW()),
(NULL, @khoan_chi_id, 'Di chuyển', 'expense', '#34495E', 'fa-car-side', 1, NOW()),
(NULL, @khoan_chi_id, 'Sức khoẻ', 'expense', '#16A085', 'fa-heartbeat', 1, NOW()),
(NULL, @khoan_chi_id, 'Giáo dục', 'expense', '#9B59B6', 'fa-graduation-cap', 1, NOW()),
(NULL, @khoan_chi_id, 'Giải trí', 'expense', '#E74C3C', 'fa-film', 1, NOW()),
(NULL, @khoan_chi_id, 'Quà tặng & Quyên góp', 'expense', '#FF6B6B', 'fa-gift', 1, NOW()),
(NULL, @khoan_chi_id, 'Bảo hiểm', 'expense', '#2C3E50', 'fa-shield-alt', 1, NOW()),
(NULL, @khoan_chi_id, 'Đầu tư', 'expense', '#27AE60', 'fa-chart-line', 1, NOW()),
(NULL, @khoan_chi_id, 'Các chi phí khác', 'expense', '#7F8C8D', 'fa-ellipsis-h', 1, NOW()),
(NULL, @khoan_chi_id, 'Tiền chuyến đi', 'expense', '#1ABC9C', 'fa-plane', 1, NOW()),
(NULL, @khoan_chi_id, 'Trả lãi', 'expense', '#C0392B', 'fa-coins', 1, NOW());

-- Hoá đơn & Tiện ích children
SET @hoadon_id = (SELECT id FROM categories WHERE name = 'Hoá đơn & Tiện ích' AND parent_id = @khoan_chi_id LIMIT 1);
INSERT INTO `categories` (user_id, parent_id, name, type, color, icon, is_default, created_at) VALUES
(NULL, @hoadon_id, 'Hoá đơn điện thoại', 'expense', '#9B59B6', 'fa-mobile-alt', 1, NOW()),
(NULL, @hoadon_id, 'Hoá đơn nước', 'expense', '#3498DB', 'fa-tint', 1, NOW()),
(NULL, @hoadon_id, 'Hoá đơn điện', 'expense', '#2980B9', 'fa-plug', 1, NOW()),
(NULL, @hoadon_id, 'Hoá đơn gas', 'expense', '#E67E22', 'fa-fire', 1, NOW()),
(NULL, @hoadon_id, 'Hoá đơn TV', 'expense', '#7F8C8D', 'fa-tv', 1, NOW()),
(NULL, @hoadon_id, 'Hoá đơn internet', 'expense', '#1ABC9C', 'fa-wifi', 1, NOW()),
(NULL, @hoadon_id, 'Thuê nhà', 'expense', '#95A5A6', 'fa-home', 1, NOW()),
(NULL, @hoadon_id, 'Hoá đơn tiện ích khác', 'expense', '#BDC3C7', 'fa-receipt', 1, NOW());

-- Mua sắm children
SET @muasam_id = (SELECT id FROM categories WHERE name = 'Mua sắm' AND parent_id = @khoan_chi_id LIMIT 1);
INSERT INTO `categories` (user_id, parent_id, name, type, color, icon, is_default, created_at) VALUES
(NULL, @muasam_id, 'Đồ dùng cá nhân', 'expense', '#F1C40F', 'fa-user', 1, NOW()),
(NULL, @muasam_id, 'Đồ gia dụng', 'expense', '#E67E22', 'fa-couch', 1, NOW()),
(NULL, @muasam_id, 'Làm đẹp', 'expense', '#FF6B81', 'fa-spa', 1, NOW());

-- Gia đình children
SET @giadinh_id = (SELECT id FROM categories WHERE name = 'Gia đình' AND parent_id = @khoan_chi_id LIMIT 1);
INSERT INTO `categories` (user_id, parent_id, name, type, color, icon, is_default, created_at) VALUES
(NULL, @giadinh_id, 'Sửa & trang trí nhà', 'expense', '#D35400', 'fa-tools', 1, NOW()),
(NULL, @giadinh_id, 'Dịch vụ gia đình', 'expense', '#7D3C98', 'fa-concierge-bell', 1, NOW()),
(NULL, @giadinh_id, 'Vật nuôi', 'expense', '#27AE60', 'fa-paw', 1, NOW());

-- Di chuyển children
SET @dichuyen_id = (SELECT id FROM categories WHERE name = 'Di chuyển' AND parent_id = @khoan_chi_id LIMIT 1);
INSERT INTO `categories` (user_id, parent_id, name, type, color, icon, is_default, created_at)
VALUES (NULL, @dichuyen_id, 'Bảo dưỡng xe', 'expense', '#95A5A6', 'fa-tools', 1, NOW());

-- Sức khoẻ children
SET @suckhoe_id = (SELECT id FROM categories WHERE name = 'Sức khoẻ' AND parent_id = @khoan_chi_id LIMIT 1);
INSERT INTO `categories` (user_id, parent_id, name, type, color, icon, is_default, created_at) VALUES
(NULL, @suckhoe_id, 'Khám sức khoẻ', 'expense', '#3498DB', 'fa-stethoscope', 1, NOW()),
(NULL, @suckhoe_id, 'Thể dục thể thao', 'expense', '#1ABC9C', 'fa-dumbbell', 1, NOW());

-- Giải trí children
SET @giaitri_id = (SELECT id FROM categories WHERE name = 'Giải trí' AND parent_id = @khoan_chi_id LIMIT 1);
INSERT INTO `categories` (user_id, parent_id, name, type, color, icon, is_default, created_at) VALUES
(NULL, @giaitri_id, 'Dịch vụ trực tuyến', 'expense', '#2980B9', 'fa-broadcast-tower', 1, NOW()),
(NULL, @giaitri_id, 'Vui - chơi', 'expense', '#F39C12', 'fa-gamepad', 1, NOW());

-- Top-level: Khoản Thu (Thu nhập)
INSERT INTO `categories` (user_id, parent_id, name, type, color, icon, is_default, created_at)
VALUES (NULL, NULL, 'Khoản Thu (Thu nhập)', 'income', '#2ECC71', 'fa-folder-open', 1, NOW());
SET @khoan_thu_id = LAST_INSERT_ID();

-- Children of Khoản Thu (Tất cả đều là 'income')
INSERT INTO `categories` (user_id, parent_id, name, type, color, icon, is_default, created_at) VALUES
(NULL, @khoan_thu_id, 'Lương', 'income', '#2980B9', 'fa-money-bill-wave', 1, NOW()),
(NULL, @khoan_thu_id, 'Thu nhập khác', 'income', '#16A085', 'fa-wallet', 1, NOW()),
(NULL, @khoan_thu_id, 'Tiền chuyển đến', 'income', '#9B59B6', 'fa-exchange-alt', 1, NOW()),
(NULL, @khoan_thu_id, 'Thu lãi', 'income', '#27AE60', 'fa-percentage', 1, NOW());

-- Top-level: Nợ/Cho vay (FIXED)
INSERT INTO `categories` (user_id, parent_id, name, type, color, icon, is_default, created_at)
VALUES (NULL, NULL, 'Nợ/Cho vay', 'expense', '#7F8C8D', 'fa-hand-holding-usd', 1, NOW());
SET @no_id = LAST_INSERT_ID();

INSERT INTO `categories` (user_id, parent_id, name, type, color, icon, is_default, created_at) VALUES
(NULL, @no_id, 'Cho vay', 'expense', '#E67E22', 'fa-handshake', 1, NOW()),    -- Giảm số dư: expense
(NULL, @no_id, 'Trả nợ', 'expense', '#C0392B', 'fa-reply', 1, NOW()),         -- Giảm số dư: expense
(NULL, @no_id, 'Đi vay', 'income', '#95A5A6', 'fa-money-check-alt', 1, NOW()),  -- Tăng số dư: income (FIXED)
(NULL, @no_id, 'Thu nợ', 'income', '#2C3E50', 'fa-receipt', 1, NOW());         -- Tăng số dư: income (FIXED)


SET FOREIGN_KEY_CHECKS = 1;

-- ============================================
-- DEPLOYMENT COMPLETE
-- ============================================