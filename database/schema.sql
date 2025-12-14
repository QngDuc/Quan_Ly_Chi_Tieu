-- ==========================================================
-- SMART SPENDING - FINAL DATABASE (VIP PRO - NONE EDITION)
-- Updated: 2025-12-14
-- Note: Added 'none' type for Root Categories
-- ==========================================================

-- 1. SETUP DATABASE
DROP DATABASE IF EXISTS `quan_ly_chi_tieu`;
CREATE DATABASE `quan_ly_chi_tieu` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `quan_ly_chi_tieu`;

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+07:00";
SET FOREIGN_KEY_CHECKS = 0;

-- ==========================================================
-- 2. CREATE TABLES
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
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2.2. Categories (Updated ENUM with 'none')
CREATE TABLE `categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `type` enum('income','expense') NOT NULL,
  -- QUAN TRỌNG: Thêm 'none' và set làm mặc định
  `group_type` enum('nec', 'ffa', 'ltss', 'edu', 'play', 'give', 'none') NOT NULL DEFAULT 'none',
  `color` varchar(7) DEFAULT '#000000',
  `icon` varchar(50) DEFAULT 'fa-tag',
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
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
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_budgets_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_budgets_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2.5. Goals
CREATE TABLE `goals` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `target_amount` decimal(15,2) NOT NULL,
  `deadline` date DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  `status` enum('active','completed') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_goals_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2.6. User Wallets
CREATE TABLE `user_wallets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `jar_code` enum('nec', 'ffa', 'ltss', 'edu', 'play', 'give') NOT NULL,
  `balance` decimal(15,2) DEFAULT 0.00,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_jar` (`user_id`, `jar_code`),
  CONSTRAINT `fk_wallets_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2.7. User Budget Settings
CREATE TABLE `user_budget_settings` (
  `user_id` int(11) PRIMARY KEY,
  `nec_percent` int(3) DEFAULT 55,
  `ffa_percent` int(3) DEFAULT 10,
  `ltss_percent` int(3) DEFAULT 10,
  `edu_percent` int(3) DEFAULT 10,
  `play_percent` int(3) DEFAULT 10,
  `give_percent` int(3) DEFAULT 5,
  CONSTRAINT `fk_settings_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ==========================================================
-- 3. INSERT DATA (SEED)
-- ==========================================================

-- 3.1. Insert Admin User (ID = 2)
-- Password mặc định: 123456
INSERT INTO `users` (id, username, email, password, full_name, role) VALUES
(1, 'Admin', 'admin@gmail.com', '$2y$10$M12D3rP.nNSdTxDMq/FQbeJfKwrPHoJSq9.itE/N3gZVt.afkEft.', 'Admin Vip Pro', 'admin');

-- 3.2. INSERT CATEGORIES (SỬ DỤNG 'none' CHO ROOT VÀ INCOME)

-- A. ROOT KHOẢN CHI (ID 1) -> Dùng 'none'
INSERT INTO `categories` (id, parent_id, name, type, group_type, color, icon, is_default) VALUES 
(1, NULL, 'Khoản Chi', 'expense', 'none', '#E74C3C', 'fa-wallet', 1);

-- Các danh mục con (Set đúng group_type cho 6 hũ)
INSERT INTO `categories` (id, parent_id, name, type, group_type, color, icon, is_default) VALUES
(2, 1, 'Hoá đơn', 'expense', 'nec', '#3498DB', 'fa-file-invoice-dollar', 1),
(3, 1, 'Thực phẩm ăn uống', 'expense', 'nec', '#E67E22', 'fa-utensils', 1),
(4, 1, 'Du lịch di chuyển', 'expense', 'play', '#F1C40F', 'fa-plane-departure', 1),
(5, 1, 'Sức khoẻ', 'expense', 'nec', '#2ECC71', 'fa-heartbeat', 1),
(6, 1, 'Chi tiêu cá nhân', 'expense', 'nec', '#9B59B6', 'fa-user-circle', 1),
(7, 1, 'Mua sắm', 'expense', 'play', '#FF9FF3', 'fa-shopping-bag', 1),
(8, 1, 'Giáo dục', 'expense', 'edu', '#34495E', 'fa-graduation-cap', 1),
(9, 1, 'Giải trí', 'expense', 'play', '#D35400', 'fa-gamepad', 1),
(10, 1, 'Đầu tư tiết kiệm', 'expense', 'ffa', '#27AE60', 'fa-piggy-bank', 1),
(11, 1, 'Kinh doanh', 'expense', 'nec', '#7F8C8D', 'fa-briefcase', 1),
(12, 1, 'Trả nợ', 'expense', 'nec', '#C0392B', 'fa-money-bill-wave', 1),
(13, 1, 'Từ thiện', 'expense', 'give', '#FF6B6B', 'fa-hand-holding-heart', 1),
(14, 1, 'Dịch vụ tiện ích', 'expense', 'nec', '#1ABC9C', 'fa-bolt', 1);

-- B. ROOT KHOẢN THU (ID 15) -> Dùng 'none'
INSERT INTO `categories` (id, parent_id, name, type, group_type, color, icon, is_default) VALUES 
(15, NULL, 'Khoản Thu', 'income', 'none', '#2ECC71', 'fa-hand-holding-usd', 1);

-- Các danh mục con của Thu -> Dùng 'none' (Tiền thu chưa chia hũ tại đây)
INSERT INTO `categories` (id, parent_id, name, type, group_type, color, icon, is_default) VALUES
(16, 15, 'Lương', 'income', 'none', '#27AE60', 'fa-money-bill', 1),
(17, 15, 'Thưởng', 'income', 'none', '#F1C40F', 'fa-gift', 1),
(18, 15, 'Lãi suất', 'income', 'none', '#E67E22', 'fa-percent', 1),
(19, 15, 'Thu nhập khác', 'income', 'none', '#3498DB', 'fa-coins', 1);

-- C. VAY & NỢ (ID 20) -> Dùng 'none'
INSERT INTO `categories` (id, parent_id, name, type, group_type, color, icon, is_default) VALUES 
(20, NULL, 'Vay & Nợ', 'income', 'none', '#95A5A6', 'fa-balance-scale', 1);

INSERT INTO `categories` (id, parent_id, name, type, group_type, color, icon, is_default) VALUES 
(21, 20, 'Đi vay', 'income', 'none', '#7F8C8D', 'fa-hand-holding-medical', 1),
(22, 20, 'Thu nợ', 'income', 'none', '#27AE60', 'fa-check-circle', 1);


-- 3.3. INSERT TRANSACTIONS (MAPPED ID)
-- ID 16: Lương | ID 3: Thực phẩm | ID 2: Hoá đơn | ID 7: Mua sắm | ID 10: Đầu tư

INSERT INTO `transactions` (user_id, category_id, amount, date, description, type) VALUES
-- THÁNG 10/2025 (Dữ liệu lịch sử)
(2, 16, 15000000, '2025-10-01', 'Lương tháng 10', 'income'), 
(2, 17, 2000000, '2025-10-15', 'Thưởng dự án', 'income'),

(2, 3, -50000, '2025-10-02', 'Ăn sáng', 'expense'),
(2, 3, -3000000, '2025-10-30', 'Tiền chợ cả tháng', 'expense'),
(2, 2, -1200000, '2025-10-05', 'Tiền điện nước', 'expense'),
(2, 14, -300000, '2025-10-06', 'Tiền Wifi 6 tháng', 'expense'),
(2, 7, -800000, '2025-10-08', 'Mua áo khoác', 'expense'),
(2, 9, -300000, '2025-10-12', 'Xem phim Joker', 'expense'),
(2, 4, -200000, '2025-10-20', 'Xăng xe đi phượt', 'expense'),
(2, 8, -600000, '2025-10-15', 'Mua khoá học Udemy', 'expense'),
(2, 10, -2000000, '2025-10-02', 'Mua vàng tích trữ', 'expense'),
(2, 13, -500000, '2025-10-20', 'Quà 20/10', 'expense'),

-- THÁNG 11/2025 (Dữ liệu hiện tại)
(2, 16, 15000000, '2025-11-01', 'Lương tháng 11', 'income'),

(2, 3, -150000, '2025-11-02', 'Ăn lẩu Haidilao', 'expense'),
(2, 3, -1500000, '2025-11-10', 'Đi siêu thị Go!', 'expense'),
(2, 2, -1100000, '2025-11-05', 'Hoá đơn điện', 'expense'),
(2, 7, -2500000, '2025-11-11', 'Săn sale Shopee 11/11', 'expense'),
(2, 10, -3000000, '2025-11-02', 'Gửi tiết kiệm Online', 'expense'),
(2, 5, -500000, '2025-11-12', 'Khám răng', 'expense');

-- 3.4. INSERT BUDGETS
INSERT INTO `budgets` (user_id, category_id, amount, period, start_date, end_date, alert_threshold) VALUES
-- Ngân sách Thực phẩm (ID 3): 5 triệu
(2, 3, 5000000, 'monthly', '2025-11-01', '2025-11-30', 80),
-- Ngân sách Mua sắm (ID 7): 2 triệu
(2, 7, 2000000, 'monthly', '2025-11-01', '2025-11-30', 80),
-- Ngân sách Giáo dục (ID 8): 1 triệu
(2, 8, 1000000, 'monthly', '2025-11-01', '2025-11-30', 80);

-- 3.5. INIT WALLETS
INSERT IGNORE INTO `user_wallets` (user_id, jar_code, balance) VALUES 
(2, 'nec', 0), (2, 'ffa', 0), (2, 'ltss', 0), (2, 'edu', 0), (2, 'play', 0), (2, 'give', 0);

-- 3.6. INIT SETTINGS
INSERT IGNORE INTO `user_budget_settings` (user_id) VALUES (2);

SET FOREIGN_KEY_CHECKS = 1;