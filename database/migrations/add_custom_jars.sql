-- Migration: Add custom jar templates for users
-- Allows users to create their own jar types instead of fixed 6 jars

CREATE TABLE IF NOT EXISTS `jar_templates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL COMMENT 'ID người dùng',
  `name` varchar(100) NOT NULL COMMENT 'Tên hũ',
  `percentage` decimal(5,2) NOT NULL DEFAULT 0.00 COMMENT 'Phần trăm phân bổ',
  `color` varchar(20) NOT NULL DEFAULT '#6c757d' COMMENT 'Màu hiển thị (hex)',
  `icon` varchar(50) DEFAULT NULL COMMENT 'Font Awesome icon class',
  `description` text DEFAULT NULL COMMENT 'Mô tả hũ',
  `order_index` int(11) NOT NULL DEFAULT 0 COMMENT 'Thứ tự hiển thị',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add sample categories for each jar (optional)
CREATE TABLE IF NOT EXISTS `jar_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `jar_id` int(11) NOT NULL COMMENT 'ID hũ',
  `category_name` varchar(100) NOT NULL COMMENT 'Tên mục con',
  `order_index` int(11) NOT NULL DEFAULT 0 COMMENT 'Thứ tự hiển thị',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_jar_id` (`jar_id`),
  FOREIGN KEY (`jar_id`) REFERENCES `jar_templates`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Update jar_allocations to support dynamic jars
CREATE TABLE IF NOT EXISTS `jar_allocations_v2` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL COMMENT 'ID người dùng',
  `jar_template_id` int(11) NOT NULL COMMENT 'ID mẫu hũ',
  `month` varchar(7) NOT NULL COMMENT 'Tháng phân bổ (YYYY-MM)',
  `allocated_amount` decimal(15,2) NOT NULL DEFAULT 0.00 COMMENT 'Số tiền được phân bổ',
  `spent_amount` decimal(15,2) NOT NULL DEFAULT 0.00 COMMENT 'Số tiền đã chi/tiết kiệm',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_jar_month` (`user_id`, `jar_template_id`, `month`),
  KEY `idx_user_month` (`user_id`, `month`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`jar_template_id`) REFERENCES `jar_templates`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add default jar templates for existing users (optional migration data)
-- Users can modify or delete these later
INSERT INTO `jar_templates` (`user_id`, `name`, `percentage`, `color`, `icon`, `description`, `order_index`)
SELECT 
  u.id,
  jar_data.name,
  jar_data.percentage,
  jar_data.color,
  jar_data.icon,
  jar_data.description,
  jar_data.order_index
FROM `users` u
CROSS JOIN (
  SELECT 'Thiết yếu' as name, 55.00 as percentage, '#3498db' as color, 'fa-home' as icon, 'Chi phí sinh hoạt hàng ngày' as description, 1 as order_index
  UNION ALL SELECT 'Tự do tài chính', 10.00, '#2ecc71', 'fa-chart-line', 'Đầu tư, thu nhập thụ động', 2
  UNION ALL SELECT 'Giáo dục', 10.00, '#9b59b6', 'fa-graduation-cap', 'Học tập, phát triển bản thân', 3
  UNION ALL SELECT 'Tiết kiệm', 10.00, '#e74c3c', 'fa-piggy-bank', 'Tiết kiệm dài hạn, quỹ khẩn cấp', 4
  UNION ALL SELECT 'Vui chơi', 10.00, '#f39c12', 'fa-smile', 'Giải trí, thư giãn', 5
  UNION ALL SELECT 'Từ thiện', 5.00, '#1abc9c', 'fa-hand-holding-heart', 'Quyên góp, hỗ trợ cộng đồng', 6
) jar_data
WHERE NOT EXISTS (
  SELECT 1 FROM `jar_templates` WHERE `user_id` = u.id
);
