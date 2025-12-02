-- ============================================
-- SmartSpending - Full Database Schema
-- Version: 4.0.0
-- Date: December 2, 2025
-- Description: Schema đầy đủ với phân quyền Admin/User
-- ============================================

-- Create database
CREATE DATABASE IF NOT EXISTS `quan_ly_chi_tieu` 
DEFAULT CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

USE `quan_ly_chi_tieu`;

-- Set configuration
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+07:00";

-- ============================================
-- DROP EXISTING TABLES
-- ============================================
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `goal_transactions`;
DROP TABLE IF EXISTS `goals`;
DROP TABLE IF EXISTS `budgets`;
DROP TABLE IF EXISTS `jar_allocations`;
DROP TABLE IF EXISTS `recurring_transactions`;
DROP TABLE IF EXISTS `transactions`;
DROP TABLE IF EXISTS `categories`;
DROP TABLE IF EXISTS `users`;

SET FOREIGN_KEY_CHECKS = 1;

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
  `name` varchar(100) NOT NULL COMMENT 'Tên danh mục',
  `type` enum('income','expense') NOT NULL COMMENT 'Loại: thu nhập hoặc chi tiêu',
  `color` varchar(7) DEFAULT '#3498db' COMMENT 'Mã màu hex',
  `icon` varchar(50) DEFAULT 'fa-circle' COMMENT 'Font Awesome icon class',
  `is_default` tinyint(1) NOT NULL DEFAULT 0 COMMENT '1 = danh mục hệ thống, 0 = tùy chỉnh',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_type` (`type`),
  CONSTRAINT `fk_categories_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
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
  KEY `idx_type` (`type`),
  KEY `idx_user_date` (`user_id`, `date`),
  CONSTRAINT `fk_transactions_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_transactions_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Bảng giao dịch';

-- ============================================
-- TABLE: recurring_transactions
-- ============================================
CREATE TABLE `recurring_transactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL COMMENT 'ID người dùng',
  `category_id` int(11) NOT NULL COMMENT 'ID danh mục',
  `amount` decimal(15,2) NOT NULL COMMENT 'Số tiền',
  `type` enum('income','expense') NOT NULL COMMENT 'Loại giao dịch',
  `description` text DEFAULT NULL COMMENT 'Mô tả',
  `frequency` enum('daily','weekly','monthly','yearly') NOT NULL COMMENT 'Tần suất',
  `start_date` date NOT NULL COMMENT 'Ngày bắt đầu',
  `end_date` date DEFAULT NULL COMMENT 'Ngày kết thúc (NULL = vô hạn)',
  `next_occurrence` date NOT NULL COMMENT 'Lần xuất hiện tiếp theo',
  `is_active` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1 = active, 0 = paused',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_category_id` (`category_id`),
  KEY `idx_next_occurrence` (`next_occurrence`),
  KEY `idx_is_active` (`is_active`),
  CONSTRAINT `fk_recurring_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_recurring_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Bảng giao dịch định kỳ';

-- ============================================
-- TABLE: budgets
-- ============================================
CREATE TABLE `budgets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL COMMENT 'ID người dùng',
  `category_id` int(11) NOT NULL COMMENT 'ID danh mục',
  `amount` decimal(15,2) NOT NULL COMMENT 'Số tiền ngân sách',
  `period` enum('monthly','yearly') NOT NULL DEFAULT 'monthly' COMMENT 'Kỳ ngân sách',
  `start_date` date NOT NULL COMMENT 'Ngày bắt đầu',
  `end_date` date NOT NULL COMMENT 'Ngày kết thúc',
  `alert_threshold` int(3) DEFAULT 80 COMMENT 'Ngưỡng cảnh báo (% ngân sách)',
  `is_active` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1 = active, 0 = inactive',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_category_id` (`category_id`),
  KEY `idx_period` (`period`),
  KEY `idx_dates` (`start_date`, `end_date`),
  CONSTRAINT `fk_budgets_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_budgets_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Bảng ngân sách';

-- ============================================
-- TABLE: goals
-- ============================================
CREATE TABLE `goals` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL COMMENT 'ID người dùng',
  `name` varchar(255) NOT NULL COMMENT 'Tên mục tiêu',
  `description` text DEFAULT NULL COMMENT 'Mô tả chi tiết',
  `target_amount` decimal(15,2) NOT NULL COMMENT 'Số tiền mục tiêu',
  `deadline` date NOT NULL COMMENT 'Ngày đến hạn',
  `status` enum('active','completed','cancelled') DEFAULT 'active' COMMENT 'Trạng thái',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_status` (`status`),
  KEY `idx_deadline` (`deadline`),
  CONSTRAINT `fk_goals_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Bảng mục tiêu tiết kiệm';

-- ============================================
-- TABLE: goal_transactions
-- ============================================
CREATE TABLE `goal_transactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `goal_id` int(11) NOT NULL COMMENT 'ID mục tiêu',
  `transaction_id` int(11) NOT NULL COMMENT 'ID giao dịch',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_goal_transaction` (`goal_id`, `transaction_id`),
  KEY `idx_goal_id` (`goal_id`),
  KEY `idx_transaction_id` (`transaction_id`),
  CONSTRAINT `fk_goal_transactions_goal` FOREIGN KEY (`goal_id`) REFERENCES `goals` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_goal_transactions_transaction` FOREIGN KEY (`transaction_id`) REFERENCES `transactions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Bảng liên kết mục tiêu và giao dịch';

-- ============================================
-- TABLE: jar_allocations (6 Jars Method)
-- ============================================
CREATE TABLE `jar_allocations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL COMMENT 'ID người dùng',
  `month` varchar(7) NOT NULL COMMENT 'Tháng phân bổ (YYYY-MM)',
  `total_income` decimal(15,2) NOT NULL DEFAULT 0.00 COMMENT 'Tổng thu nhập tháng',
  `nec_percentage` decimal(5,2) NOT NULL DEFAULT 55.00 COMMENT 'Necessities %',
  `nec_amount` decimal(15,2) NOT NULL DEFAULT 0.00 COMMENT 'Số tiền Thiết yếu',
  `nec_spent` decimal(15,2) NOT NULL DEFAULT 0.00 COMMENT 'Đã chi Thiết yếu',
  `ffa_percentage` decimal(5,2) NOT NULL DEFAULT 10.00 COMMENT 'Financial Freedom %',
  `ffa_amount` decimal(15,2) NOT NULL DEFAULT 0.00 COMMENT 'Số tiền Tự do tài chính',
  `ffa_saved` decimal(15,2) NOT NULL DEFAULT 0.00 COMMENT 'Đã tiết kiệm FFA',
  `edu_percentage` decimal(5,2) NOT NULL DEFAULT 10.00 COMMENT 'Education %',
  `edu_amount` decimal(15,2) NOT NULL DEFAULT 0.00 COMMENT 'Số tiền Giáo dục',
  `edu_spent` decimal(15,2) NOT NULL DEFAULT 0.00 COMMENT 'Đã chi Giáo dục',
  `ltss_percentage` decimal(5,2) NOT NULL DEFAULT 10.00 COMMENT 'Long-term Savings %',
  `ltss_amount` decimal(15,2) NOT NULL DEFAULT 0.00 COMMENT 'Số tiền Tiết kiệm dài hạn',
  `ltss_saved` decimal(15,2) NOT NULL DEFAULT 0.00 COMMENT 'Đã tiết kiệm LTSS',
  `play_percentage` decimal(5,2) NOT NULL DEFAULT 10.00 COMMENT 'Play %',
  `play_amount` decimal(15,2) NOT NULL DEFAULT 0.00 COMMENT 'Số tiền Vui chơi',
  `play_spent` decimal(15,2) NOT NULL DEFAULT 0.00 COMMENT 'Đã chi Vui chơi',
  `give_percentage` decimal(5,2) NOT NULL DEFAULT 5.00 COMMENT 'Give %',
  `give_amount` decimal(15,2) NOT NULL DEFAULT 0.00 COMMENT 'Số tiền Từ thiện',
  `give_spent` decimal(15,2) NOT NULL DEFAULT 0.00 COMMENT 'Đã chi Từ thiện',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_month` (`user_id`, `month`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_month` (`month`),
  CONSTRAINT `fk_jar_allocations_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Bảng phân bổ 6 chiếc lọ';

-- ============================================
-- DEFAULT DATA: Categories
-- ============================================

-- Default Income Categories
INSERT INTO `categories` (`name`, `type`, `color`, `icon`, `is_default`) VALUES
('Lương', 'income', '#27ae60', 'fa-money-bill-wave', 1),
('Thưởng', 'income', '#f1c40f', 'fa-gift', 1),
('Đầu tư', 'income', '#2980b9', 'fa-chart-line', 1),
('Freelance', 'income', '#d35400', 'fa-laptop-code', 1),
('Thu nhập khác', 'income', '#7f8c8d', 'fa-coins', 1);

-- Default Expense Categories - 6 Jars Method
-- JAR 1: Necessities (55%) - Thiết yếu
INSERT INTO `categories` (`name`, `type`, `color`, `icon`, `is_default`) VALUES
('Ăn uống', 'expense', '#e74c3c', 'fa-utensils', 1),
('Giao thông', 'expense', '#3498db', 'fa-car', 1),
('Nhà ở', 'expense', '#1abc9c', 'fa-home', 1),
('Tiện ích', 'expense', '#34495e', 'fa-bolt', 1),
('Sức khỏe', 'expense', '#e67e22', 'fa-heartbeat', 1),
('Quần áo', 'expense', '#8e44ad', 'fa-tshirt', 1);

-- JAR 2: Financial Freedom Account (10%) - Tự do tài chính  
INSERT INTO `categories` (`name`, `type`, `color`, `icon`, `is_default`) VALUES
('Đầu tư chứng khoán', 'expense', '#2980b9', 'fa-chart-line', 1),
('Tiết kiệm định kỳ', 'expense', '#16a085', 'fa-piggy-bank', 1);

-- JAR 3: Education (10%) - Giáo dục
INSERT INTO `categories` (`name`, `type`, `color`, `icon`, `is_default`) VALUES
('Sách & Tài liệu', 'expense', '#2ecc71', 'fa-book', 1),
('Khóa học', 'expense', '#27ae60', 'fa-graduation-cap', 1),
('Hội thảo & Sự kiện', 'expense', '#229954', 'fa-users', 1);

-- JAR 4: Long-term Savings (10%) - Tiết kiệm dài hạn
INSERT INTO `categories` (`name`, `type`, `color`, `icon`, `is_default`) VALUES
('Mua nhà/Xe', 'expense', '#9b59b6', 'fa-car-building', 1),
('Du lịch lớn', 'expense', '#16a085', 'fa-plane', 1),
('Quỹ khẩn cấp', 'expense', '#8e44ad', 'fa-shield-alt', 1);

-- JAR 5: Play (10%) - Vui chơi
INSERT INTO `categories` (`name`, `type`, `color`, `icon`, `is_default`) VALUES
('Giải trí', 'expense', '#f39c12', 'fa-film', 1),
('Mua sắm cá nhân', 'expense', '#e67e22', 'fa-shopping-bag', 1),
('Ăn uống sang', 'expense', '#d35400', 'fa-glass-cheers', 1);

-- JAR 6: Give (5%) - Từ thiện
INSERT INTO `categories` (`name`, `type`, `color`, `icon`, `is_default`) VALUES
('Từ thiện', 'expense', '#e74c3c', 'fa-hand-holding-heart', 1),
('Quà tặng', 'expense', '#c0392b', 'fa-gift', 1);

-- ============================================
-- VIEWS
-- ============================================

DROP VIEW IF EXISTS `v_monthly_summary`;
DROP VIEW IF EXISTS `v_category_summary`;

-- View: Monthly summary
CREATE VIEW `v_monthly_summary` AS
SELECT 
    t.user_id,
    DATE_FORMAT(t.date, '%Y-%m') AS month,
    t.type,
    COUNT(*) AS transaction_count,
    SUM(t.amount) AS total_amount
FROM transactions t
GROUP BY t.user_id, month, t.type;

-- View: Category summary
CREATE VIEW `v_category_summary` AS
SELECT 
    t.user_id,
    c.id AS category_id,
    c.name AS category_name,
    c.type,
    COUNT(t.id) AS transaction_count,
    SUM(t.amount) AS total_amount
FROM transactions t
INNER JOIN categories c ON t.category_id = c.id
GROUP BY t.user_id, c.id, c.name, c.type;

-- ============================================
-- STORED PROCEDURES
-- ============================================

DROP PROCEDURE IF EXISTS `sp_get_user_balance`;
DROP PROCEDURE IF EXISTS `sp_get_budget_status`;

DELIMITER $$

-- Get user balance
CREATE PROCEDURE `sp_get_user_balance`(IN p_user_id INT)
BEGIN
    SELECT 
        COALESCE(SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END), 0) AS total_income,
        COALESCE(SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END), 0) AS total_expense,
        COALESCE(SUM(CASE WHEN type = 'income' THEN amount ELSE -amount END), 0) AS balance
    FROM transactions
    WHERE user_id = p_user_id;
END$$

-- Get budget status
CREATE PROCEDURE `sp_get_budget_status`(IN p_user_id INT, IN p_budget_id INT)
BEGIN
    SELECT 
        b.id,
        b.amount AS budget_amount,
        COALESCE(SUM(t.amount), 0) AS spent_amount,
        b.amount - COALESCE(SUM(t.amount), 0) AS remaining,
        ROUND((COALESCE(SUM(t.amount), 0) / b.amount * 100), 2) AS percentage_used
    FROM budgets b
    LEFT JOIN transactions t ON 
        b.category_id = t.category_id AND 
        b.user_id = t.user_id AND
        t.date BETWEEN b.start_date AND b.end_date
    WHERE b.id = p_budget_id AND b.user_id = p_user_id
    GROUP BY b.id, b.amount;
END$$
-- ============================================
-- TRIGGERS
-- ============================================

DROP TRIGGER IF EXISTS `trg_transactions_set_type`;

DELIMITER $$

-- Auto set transaction type based on category
CREATE TRIGGER `trg_transactions_set_type`
BEFORE INSERT ON `transactions`
FOR EACH ROW
BEGIN
    DECLARE cat_type VARCHAR(10);
    SELECT type INTO cat_type FROM categories WHERE id = NEW.category_id;
    SET NEW.type = cat_type;
END$$

DELIMITER ;

-- ============================================
-- ADMIN SETUP (Optional - For existing users)
-- ============================================

-- Uncomment line below to set specific user as admin
-- UPDATE `users` SET `role` = 'admin' WHERE `email` = 'your_email@example.com';

-- ============================================
-- END OF SCHEMA
-- ============================================
