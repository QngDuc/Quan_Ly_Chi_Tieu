-- ==========================================================
-- SMART SPENDING - FULL DATABASE INSTALLATION
-- Version: 6.2.0 (Merged & Optimized)
-- Date: 2025-12-13
-- ==========================================================

-- 1. SETUP DATABASE
DROP DATABASE IF EXISTS `quan_ly_chi_tieu`;
CREATE DATABASE `quan_ly_chi_tieu` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `quan_ly_chi_tieu`;

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+07:00";
SET FOREIGN_KEY_CHECKS = 0;

-- ==========================================================
-- 2. CREATE TABLES (FINAL SCHEMA)
-- ==========================================================

-- 2.1. Users
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL UNIQUE,
  `email` varchar(100) NOT NULL UNIQUE,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) DEFAULT NULL,
  `role` enum('user','admin') NOT NULL DEFAULT 'user',
  `avatar` varchar(255) DEFAULT 'https://www.svgrepo.com/show/452030/avatar-default.svg',
  `notify_budget_limit` tinyint(1) NOT NULL DEFAULT 1,
  `notify_goal_reminder` tinyint(1) NOT NULL DEFAULT 1,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2.2. Categories (Tích hợp sẵn 6 Hũ chi tiêu)
CREATE TABLE `categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `type` enum('income','expense') NOT NULL,
  `group_type` enum('nec', 'ffa', 'ltss', 'edu', 'play', 'give') NOT NULL DEFAULT 'nec' COMMENT '6 Jars System',
  `color` varchar(7) DEFAULT '#000000',
  `icon` varchar(50) DEFAULT 'fa-tag',
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_parent_id` (`parent_id`),
  CONSTRAINT `fk_categories_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_categories_parent` FOREIGN KEY (`parent_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2.3. Transactions
CREATE TABLE `transactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `date` date NOT NULL,
  `description` text DEFAULT NULL,
  `type` enum('income','expense') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_category_id` (`category_id`),
  CONSTRAINT `fk_transactions_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_transactions_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2.4. Budgets
CREATE TABLE `budgets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `period` enum('weekly','monthly','yearly') NOT NULL DEFAULT 'monthly',
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `alert_threshold` int(11) DEFAULT 80,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  CONSTRAINT `fk_budgets_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_budgets_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2.5. Goals (Tích hợp cột start_date và category_id)
CREATE TABLE `goals` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `target_amount` decimal(15,2) NOT NULL,
  `deadline` date DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  `status` enum('active','completed','archived') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  CONSTRAINT `fk_goals_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_goals_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2.6. Goal Transactions
CREATE TABLE `goal_transactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `goal_id` int(11) NOT NULL,
  `transaction_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_goal_id` (`goal_id`),
  KEY `idx_transaction_id` (`transaction_id`),
  CONSTRAINT `fk_goal_transactions_goal` FOREIGN KEY (`goal_id`) REFERENCES `goals` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_goal_transactions_transaction` FOREIGN KEY (`transaction_id`) REFERENCES `transactions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2.7. User Budget Settings (Cấu hình 6 Hũ)
CREATE TABLE `user_budget_settings` (
  `user_id` int(11) PRIMARY KEY,
  `nec_percent` int(3) DEFAULT 55,  -- Nhu cầu thiết yếu
  `ffa_percent` int(3) DEFAULT 10,  -- Tự do tài chính
  `ltss_percent` int(3) DEFAULT 10, -- Tiết kiệm dài hạn
  `edu_percent` int(3) DEFAULT 10,  -- Giáo dục
  `play_percent` int(3) DEFAULT 10, -- Hưởng thụ
  `give_percent` int(3) DEFAULT 5,  -- Cho đi
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_user_budget_settings_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==========================================================
-- 3. SEED DATA
-- ==========================================================

-- 3.1. Insert Users (Admin ID = 2 để khớp với data transactions)
INSERT INTO `users` (id, username, email, password, full_name, role) VALUES
(2, 'Admin', 'admin@gmail.com', '$2y$10$M12D3rP.nNSdTxDMq/FQbeJfKwrPHoJSq9.itE/N3gZVt.afkEft.', 'Admin SmartSpending', 'admin');

-- 3.2. Insert Categories
-- QUAN TRỌNG: Thứ tự Insert phải giữ nguyên để đảm bảo ID khớp với Transactions tháng 10/11
-- ID 1: Root Chi
INSERT INTO `categories` (parent_id, name, type, group_type, color, icon, is_default) VALUES 
(NULL, 'Khoản Chi', 'expense', 'nec', '#E74C3C', 'fa-wallet', 1);
SET @root_chi = LAST_INSERT_ID();

-- ID 2: Ăn uống (NEC)
INSERT INTO `categories` (parent_id, name, type, group_type, color, icon, is_default) VALUES 
(@root_chi, 'Ăn uống', 'expense', 'nec', '#FF6B6B', 'fa-utensils', 1);

-- ID 3: Hoá đơn (NEC)
INSERT INTO `categories` (parent_id, name, type, group_type, color, icon, is_default) VALUES 
(@root_chi, 'Hoá đơn & Tiện ích', 'expense', 'nec', '#3498DB', 'fa-file-invoice-dollar', 1);
SET @parent_hoadon = LAST_INSERT_ID();

    -- Children of Hoá đơn (ID 4 -> 11)
    INSERT INTO `categories` (parent_id, name, type, group_type, color, icon, is_default) VALUES
    (@parent_hoadon, 'Hoá đơn điện thoại', 'expense', 'nec', '#9B59B6', 'fa-phone', 1), -- ID 4
    (@parent_hoadon, 'Hoá đơn nước', 'expense', 'nec', '#3498DB', 'fa-tint', 1),  -- ID 5
    (@parent_hoadon, 'Hoá đơn điện', 'expense', 'nec', '#F1C40F', 'fa-bolt', 1),  -- ID 6
    (@parent_hoadon, 'Hoá đơn gas', 'expense', 'nec', '#E67E22', 'fa-fire', 1),   -- ID 7
    (@parent_hoadon, 'Hoá đơn TV', 'expense', 'nec', '#2C3E50', 'fa-tv', 1),      -- ID 8
    (@parent_hoadon, 'Hoá đơn internet', 'expense', 'nec', '#1ABC9C', 'fa-wifi', 1), -- ID 9
    (@parent_hoadon, 'Thuê nhà', 'expense', 'nec', '#7F8C8D', 'fa-home', 1),
    (@parent_hoadon, 'Hoá đơn tiện ích khác', 'expense', 'nec', '#BDC3C7', 'fa-receipt', 1);

-- ID 12: Mua sắm (PLAY)
INSERT INTO `categories` (parent_id, name, type, group_type, color, icon, is_default) VALUES 
(@root_chi, 'Mua sắm', 'expense', 'play', '#2ECC71', 'fa-shopping-basket', 1);
SET @parent_muasam = LAST_INSERT_ID();

    -- Children of Mua sắm
    INSERT INTO `categories` (parent_id, name, type, group_type, color, icon, is_default) VALUES
    (@parent_muasam, 'Đồ dùng cá nhân', 'expense', 'nec', '#16A085', 'fa-user-cog', 1),
    (@parent_muasam, 'Đồ gia dụng', 'expense', 'nec', '#D35400', 'fa-couch', 1),
    (@parent_muasam, 'Làm đẹp', 'expense', 'play', '#FF9FF3', 'fa-spa', 1);

-- ID 16: Gia đình (NEC)
INSERT INTO `categories` (parent_id, name, type, group_type, color, icon, is_default) VALUES 
(@root_chi, 'Gia đình', 'expense', 'nec', '#1ABC9C', 'fa-house-user', 1);
SET @parent_giadinh = LAST_INSERT_ID();

    -- Children of Gia đình
    INSERT INTO `categories` (parent_id, name, type, group_type, color, icon, is_default) VALUES
    (@parent_giadinh, 'Sửa & trang trí nhà', 'expense', 'nec', '#F39C12', 'fa-paint-roller', 1),
    (@parent_giadinh, 'Dịch vụ gia đình', 'expense', 'nec', '#8E44AD', 'fa-concierge-bell', 1),
    (@parent_giadinh, 'Vật nuôi', 'expense', 'play', '#27AE60', 'fa-dog', 1);

-- ID 20: Di chuyển (NEC)
INSERT INTO `categories` (parent_id, name, type, group_type, color, icon, is_default) VALUES 
(@root_chi, 'Di chuyển', 'expense', 'nec', '#F1C40F', 'fa-car', 1);
SET @parent_dichuyen = LAST_INSERT_ID();

    -- Children of Di chuyển
    INSERT INTO `categories` (parent_id, name, type, group_type, color, icon, is_default) VALUES
    (@parent_dichuyen, 'Bảo dưỡng xe', 'expense', 'nec', '#34495E', 'fa-wrench', 1),
    (@parent_dichuyen, 'Tiền xăng', 'expense', 'nec', '#E67E22', 'fa-gas-pump', 1); -- Added manually to match common needs

-- ID 23: Sức khoẻ (NEC)
INSERT INTO `categories` (parent_id, name, type, group_type, color, icon, is_default) VALUES 
(@root_chi, 'Sức khoẻ', 'expense', 'nec', '#E74C3C', 'fa-briefcase-medical', 1);
SET @parent_suckhoe = LAST_INSERT_ID();

    -- Children of Sức khoẻ
    INSERT INTO `categories` (parent_id, name, type, group_type, color, icon, is_default) VALUES
    (@parent_suckhoe, 'Khám sức khoẻ', 'expense', 'nec', '#2980B9', 'fa-stethoscope', 1),
    (@parent_suckhoe, 'Thể dục thể thao', 'expense', 'nec', '#2ECC71', 'fa-dumbbell', 1);

-- ID 26: Giải trí (PLAY)
INSERT INTO `categories` (parent_id, name, type, group_type, color, icon, is_default) VALUES 
(@root_chi, 'Giải trí', 'expense', 'play', '#3498DB', 'fa-gamepad', 1);
SET @parent_giaitri = LAST_INSERT_ID();

    -- Children of Giải trí
    INSERT INTO `categories` (parent_id, name, type, group_type, color, icon, is_default) VALUES
    (@parent_giaitri, 'Dịch vụ trực tuyến', 'expense', 'play', '#9B59B6', 'fa-cloud', 1),
    (@parent_giaitri, 'Vui - chơi', 'expense', 'play', '#F1C40F', 'fa-dice', 1);

-- Other Level 1 (ID 29+)
INSERT INTO `categories` (parent_id, name, type, group_type, color, icon, is_default) VALUES
(@root_chi, 'Giáo dục', 'expense', 'edu', '#2C3E50', 'fa-graduation-cap', 1),
(@root_chi, 'Quà tặng & Quyên góp', 'expense', 'give', '#FF6B6B', 'fa-gift', 1),
(@root_chi, 'Bảo hiểm', 'expense', 'ltss', '#16A085', 'fa-shield-alt', 1),
(@root_chi, 'Đầu tư', 'expense', 'ffa', '#F39C12', 'fa-chart-line', 1),
(@root_chi, 'Các chi phí khác', 'expense', 'play', '#95A5A6', 'fa-question-circle', 1),
(@root_chi, 'Tiền chuyến đi', 'expense', 'play', '#1ABC9C', 'fa-plane', 1),
(@root_chi, 'Trả lãi', 'expense', 'nec', '#C0392B', 'fa-percentage', 1);

-- Root: Khoản Thu (ID ~36)
INSERT INTO `categories` (parent_id, name, type, color, icon, is_default) 
VALUES (NULL, 'Khoản Thu', 'income', '#2ECC71', 'fa-piggy-bank', 1);
SET @root_thu = LAST_INSERT_ID();

INSERT INTO `categories` (parent_id, name, type, color, icon, is_default) VALUES
(@root_thu, 'Lương', 'income', '#27AE60', 'fa-money-bill-wave', 1),
(@root_thu, 'Thu nhập khác', 'income', '#F1C40F', 'fa-coins', 1),
(@root_thu, 'Tiền chuyển đến', 'income', '#3498DB', 'fa-hand-holding-usd', 1),
(@root_thu, 'Thu lãi', 'income', '#E67E22', 'fa-percent', 1);

-- Root: Nợ/Cho vay (ID ~41)
INSERT INTO `categories` (parent_id, name, type, group_type, color, icon, is_default) 
VALUES (NULL, 'Nợ/Cho vay', 'expense', 'nec', '#7F8C8D', 'fa-balance-scale', 1);
SET @root_no = LAST_INSERT_ID();

INSERT INTO `categories` (parent_id, name, type, group_type, color, icon, is_default) VALUES
(@root_no, 'Cho vay', 'expense', 'nec', '#E74C3C', 'fa-hand-holding-water', 1),
(@root_no, 'Trả nợ', 'expense', 'nec', '#C0392B', 'fa-file-invoice', 1),
(@root_no, 'Đi vay', 'income', 'nec', '#2ECC71', 'fa-hand-holding-medical', 1),
(@root_no, 'Thu nợ', 'income', 'nec', '#27AE60', 'fa-check-circle', 1);

-- 3.3. Insert Transactions (Oct & Nov 2025)
-- Note: category_id here relies on the exact order of INSERTs above being consistent with your original system.
INSERT INTO `transactions` (user_id, category_id, amount, date, description, type) VALUES
-- October
(2, 2, -200000, '2025-10-01', 'Ăn sáng', 'expense'),
(2, 2, -500000, '2025-10-02', 'Ăn trưa', 'expense'),
(2, 2, -300000, '2025-10-03', 'Ăn tối', 'expense'),
(2, 6, -400000, '2025-10-04', 'Tiền điện', 'expense'),
(2, 5, -250000, '2025-10-05', 'Tiền nước', 'expense'),
(2, 9, -150000, '2025-10-06', 'Tiền internet', 'expense'),
(2, 12, -1000000, '2025-10-07', 'Mua quần áo', 'expense'),
(2, 14, -800000, '2025-10-08', 'Mua đồ gia dụng', 'expense'),
(2, 25, -300000, '2025-10-09', 'Vé xem phim', 'expense'), -- Note: Check ID 25 maps to 'Vui chơi' or similar
(2, 25, -200000, '2025-10-10', 'Đi chơi bowling', 'expense'),
(2, 36, 9000000, '2025-10-25', 'Lương tháng 10', 'income'), -- ID 36 should be 'Lương' or root 'Khoản Thu' child
(2, 37, 500000, '2025-10-26', 'Thu nhập khác', 'income'),
(2, 20, -150000, '2025-10-11', 'Tiền xăng', 'expense'),
(2, 23, -200000, '2025-10-12', 'Khám sức khỏe', 'expense'),
(2, 28, -400000, '2025-10-13', 'Học phí', 'expense'), -- Check ID 28 is Edu
(2, 29, -300000, '2025-10-14', 'Quà sinh nhật', 'expense'), -- Check ID 29 is Gift
(2, 30, -250000, '2025-10-15', 'Bảo hiểm xe', 'expense'), -- Check ID 30 is Insurance
(2, 31, -1000000, '2025-10-16', 'Đầu tư chứng khoán', 'expense'), -- Check ID 31 is Invest
(2, 32, -50000, '2025-10-17', 'Chi phí khác', 'expense'),

-- November
(2, 2, -220000, '2025-11-01', 'Ăn sáng', 'expense'),
(2, 2, -520000, '2025-11-02', 'Ăn trưa', 'expense'),
(2, 2, -320000, '2025-11-03', 'Ăn tối', 'expense'),
(2, 6, -410000, '2025-11-04', 'Tiền điện', 'expense'),
(2, 5, -260000, '2025-11-05', 'Tiền nước', 'expense'),
(2, 9, -160000, '2025-11-06', 'Tiền internet', 'expense'),
(2, 12, -1100000, '2025-11-07', 'Mua quần áo', 'expense'),
(2, 14, -850000, '2025-11-08', 'Mua đồ gia dụng', 'expense'),
(2, 25, -350000, '2025-11-09', 'Vé xem phim', 'expense'),
(2, 25, -250000, '2025-11-10', 'Đi chơi bowling', 'expense'),
(2, 36, 9000000, '2025-11-25', 'Lương tháng 11', 'income'),
(2, 37, 600000, '2025-11-26', 'Thu nhập khác', 'income'),
(2, 20, -180000, '2025-11-11', 'Tiền xăng', 'expense'),
(2, 23, -250000, '2025-11-12', 'Khám sức khỏe', 'expense'),
(2, 28, -450000, '2025-11-13', 'Học phí', 'expense'),
(2, 29, -350000, '2025-11-14', 'Quà sinh nhật', 'expense'),
(2, 30, -300000, '2025-11-15', 'Bảo hiểm xe', 'expense'),
(2, 31, -1200000, '2025-11-16', 'Đầu tư chứng khoán', 'expense'),
(2, 32, -70000, '2025-11-17', 'Chi phí khác', 'expense');

SET FOREIGN_KEY_CHECKS = 1;