-- ============================================
-- SmartSpending - Database Schema
-- Version: 6.0.0
-- Date: December 6, 2025
-- Description: Cấu trúc database hoàn chỉnh (không có dữ liệu)
-- ============================================

CREATE DATABASE IF NOT EXISTS `quan_ly_chi_tieu` 
DEFAULT CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

USE `quan_ly_chi_tieu`;

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+07:00";
SET FOREIGN_KEY_CHECKS = 0;

-- Drop all tables
DROP TABLE IF EXISTS `jar_allocations_v2`;
DROP TABLE IF EXISTS `jar_categories`;
DROP TABLE IF EXISTS `jar_templates`;
DROP TABLE IF EXISTS `goal_transactions`;
DROP TABLE IF EXISTS `goals`;
DROP TABLE IF EXISTS `recurring_transactions`;
DROP TABLE IF EXISTS `budgets`;
DROP TABLE IF EXISTS `jar_allocations`;
DROP TABLE IF EXISTS `transactions`;
DROP TABLE IF EXISTS `categories`;
DROP TABLE IF EXISTS `users`;

-- Drop views
DROP VIEW IF EXISTS `v_monthly_summary`;
DROP VIEW IF EXISTS `v_category_summary`;

-- Drop procedures
DROP PROCEDURE IF EXISTS `sp_get_user_balance`;
DROP PROCEDURE IF EXISTS `sp_get_budget_status`;

-- ============================================
-- TABLE: users
-- ============================================
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

-- ============================================
-- TABLE: categories
-- ============================================
CREATE TABLE `categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL COMMENT 'NULL = danh mục mặc định, NOT NULL = danh mục tùy chỉnh',
  `parent_id` int(11) DEFAULT NULL COMMENT 'ID danh mục cha (NULL = danh mục gốc)',
  `name` varchar(100) NOT NULL COMMENT 'Tên danh mục',
  `type` enum('income','expense') NOT NULL COMMENT 'Loại: thu nhập hoặc chi tiêu',
  `color` varchar(7) DEFAULT '#3498db' COMMENT 'Mã màu hex',
  `icon` varchar(50) DEFAULT 'fa-circle' COMMENT 'Font Awesome icon class',
  `is_default` tinyint(1) NOT NULL DEFAULT 0 COMMENT '1 = danh mục hệ thống, 0 = tùy chỉnh',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_parent_id` (`parent_id`),
  KEY `idx_type` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Bảng danh mục';

-- ============================================
-- TABLE: transactions
-- ============================================
CREATE TABLE `transactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL COMMENT 'ID người dùng',
  `category_id` int(11) NOT NULL COMMENT 'ID danh mục',
  `amount` decimal(15,2) NOT NULL COMMENT 'Số tiền giao dịch',
  `type` enum('income','expense') NOT NULL COMMENT 'Loại: thu nhập hoặc chi tiêu',
  `description` text DEFAULT NULL COMMENT 'Mô tả giao dịch',
  `date` date NOT NULL COMMENT 'Ngày giao dịch',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_category_id` (`category_id`),
  KEY `idx_date` (`date`),
  KEY `idx_type` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Bảng giao dịch';

-- ============================================
-- TABLE: budgets
-- ============================================
CREATE TABLE `budgets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL COMMENT 'ID người dùng',
  `category_id` int(11) NOT NULL COMMENT 'ID danh mục',
  `amount` decimal(15,2) NOT NULL COMMENT 'Số tiền ngân sách',
  `period` enum('daily','weekly','monthly','yearly') NOT NULL DEFAULT 'monthly' COMMENT 'Chu kỳ ngân sách',
  `start_date` date NOT NULL COMMENT 'Ngày bắt đầu',
  `end_date` date DEFAULT NULL COMMENT 'Ngày kết thúc (NULL = không giới hạn)',
  `alert_threshold` int(11) DEFAULT 80 COMMENT 'Ngưỡng cảnh báo (%)',
  `is_active` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Trạng thái: 1=active, 0=inactive',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_category_id` (`category_id`),
  KEY `idx_period` (`period`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Bảng ngân sách';

-- ============================================
-- TABLE: goals
-- ============================================
CREATE TABLE `goals` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL COMMENT 'ID người dùng',
  `name` varchar(100) NOT NULL COMMENT 'Tên mục tiêu',
  `description` text DEFAULT NULL COMMENT 'Mô tả mục tiêu',
  `target_amount` decimal(15,2) NOT NULL COMMENT 'Số tiền mục tiêu',
  `current_amount` decimal(15,2) NOT NULL DEFAULT 0.00 COMMENT 'Số tiền hiện tại',
  `deadline` date DEFAULT NULL COMMENT 'Hạn hoàn thành',
  `icon` varchar(50) DEFAULT 'fa-bullseye' COMMENT 'Font Awesome icon',
  `color` varchar(7) DEFAULT '#3498db' COMMENT 'Mã màu hex',
  `is_completed` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Trạng thái: 0=chưa hoàn thành, 1=đã hoàn thành',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Bảng mục tiêu tài chính';

-- ============================================
-- TABLE: goal_transactions
-- ============================================
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

-- ============================================
-- TABLE: recurring_transactions
-- ============================================
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

-- ============================================
-- TABLE: jar_allocations (Legacy)
-- ============================================
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

-- ============================================
-- TABLE: jar_templates
-- ============================================
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

-- ============================================
-- TABLE: jar_categories
-- ============================================
CREATE TABLE `jar_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `jar_id` int(11) NOT NULL COMMENT 'ID jar trong jar_templates',
  `category_id` int(11) NOT NULL COMMENT 'ID danh mục trong categories',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_jar_id` (`jar_id`),
  KEY `idx_category_id` (`category_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Bảng ánh xạ Jar - Category';

-- ============================================
-- TABLE: jar_allocations_v2
-- ============================================
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
-- FOREIGN KEYS
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

ALTER TABLE `jar_allocations`
  ADD CONSTRAINT `fk_jar_allocations_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `jar_templates`
  ADD CONSTRAINT `fk_jar_templates_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `jar_categories`
  ADD CONSTRAINT `fk_jar_categories_jar_id` FOREIGN KEY (`jar_id`) REFERENCES `jar_templates` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `jar_allocations_v2`
  ADD CONSTRAINT `fk_jar_allocations_v2_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_jar_allocations_v2_jar_template_id` FOREIGN KEY (`jar_template_id`) REFERENCES `jar_templates` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

-- ============================================
-- VIEWS
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
-- STORED PROCEDURES
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

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================
-- SCHEMA COMPLETE
-- ============================================
