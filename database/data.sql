-- -- ============================================
-- -- SmartSpending - Default Data (Money Lover Style)
-- -- Version: 6.0.0
-- -- Date: December 6, 2025
-- -- Description: Dữ liệu mặc định (danh mục theo phong cách Money Lover)
-- -- ============================================

-- USE `quan_ly_chi_tieu`;

-- SET FOREIGN_KEY_CHECKS = 0;

-- -- ============================================
-- -- KHOẢN CHI (EXPENSE CATEGORIES)
-- -- ============================================

-- -- 1. Ăn uống
-- INSERT INTO `categories` (`name`, `type`, `color`, `icon`, `is_default`, `parent_id`) VALUES
-- ('Ăn uống', 'expense', '#e74c3c', 'fa-utensils', 1, NULL);

-- -- 2. Hóa đơn & Tiện ích (Parent + Children)
-- INSERT INTO `categories` (`name`, `type`, `color`, `icon`, `is_default`, `parent_id`) VALUES
-- ('Hóa đơn & Tiện ích', 'expense', '#3498db', 'fa-file-invoice-dollar', 1, NULL);
-- SET @parent_hoadon = LAST_INSERT_ID();

-- INSERT INTO `categories` (`name`, `type`, `color`, `icon`, `is_default`, `parent_id`) VALUES
-- ('Hóa đơn điện thoại', 'expense', '#3498db', 'fa-mobile-alt', 1, @parent_hoadon),
-- ('Hóa đơn nước', 'expense', '#3498db', 'fa-tint', 1, @parent_hoadon),
-- ('Hóa đơn điện', 'expense', '#3498db', 'fa-bolt', 1, @parent_hoadon),
-- ('Hóa đơn gas', 'expense', '#3498db', 'fa-fire', 1, @parent_hoadon),
-- ('Hóa đơn TV', 'expense', '#3498db', 'fa-tv', 1, @parent_hoadon),
-- ('Hóa đơn internet', 'expense', '#3498db', 'fa-wifi', 1, @parent_hoadon),
-- ('Thuê nhà', 'expense', '#3498db', 'fa-home', 1, @parent_hoadon),
-- ('Hóa đơn tiện ích khác', 'expense', '#3498db', 'fa-file-invoice', 1, @parent_hoadon);

-- -- 3. Mua sắm (Parent + Children)
-- INSERT INTO `categories` (`name`, `type`, `color`, `icon`, `is_default`, `parent_id`) VALUES
-- ('Mua sắm', 'expense', '#9b59b6', 'fa-shopping-bag', 1, NULL);
-- SET @parent_muasam = LAST_INSERT_ID();

-- INSERT INTO `categories` (`name`, `type`, `color`, `icon`, `is_default`, `parent_id`) VALUES
-- ('Đồ dùng cá nhân', 'expense', '#9b59b6', 'fa-user', 1, @parent_muasam),
-- ('Đồ gia dụng', 'expense', '#9b59b6', 'fa-couch', 1, @parent_muasam),
-- ('Làm đẹp', 'expense', '#9b59b6', 'fa-spa', 1, @parent_muasam);

-- -- 4. Gia đình (Parent + Children)
-- INSERT INTO `categories` (`name`, `type`, `color`, `icon`, `is_default`, `parent_id`) VALUES
-- ('Gia đình', 'expense', '#1abc9c', 'fa-users', 1, NULL);
-- SET @parent_giadinh = LAST_INSERT_ID();

-- INSERT INTO `categories` (`name`, `type`, `color`, `icon`, `is_default`, `parent_id`) VALUES
-- ('Sửa & trang trí nhà', 'expense', '#1abc9c', 'fa-tools', 1, @parent_giadinh),
-- ('Dịch vụ gia đình', 'expense', '#1abc9c', 'fa-broom', 1, @parent_giadinh),
-- ('Vật nuôi', 'expense', '#1abc9c', 'fa-paw', 1, @parent_giadinh);

-- -- 5. Di chuyển (Parent + Children)
-- INSERT INTO `categories` (`name`, `type`, `color`, `icon`, `is_default`, `parent_id`) VALUES
-- ('Di chuyển', 'expense', '#34495e', 'fa-car', 1, NULL);
-- SET @parent_dichuyen = LAST_INSERT_ID();

-- INSERT INTO `categories` (`name`, `type`, `color`, `icon`, `is_default`, `parent_id`) VALUES
-- ('Bảo dưỡng xe', 'expense', '#34495e', 'fa-wrench', 1, @parent_dichuyen);

-- -- 6. Sức khỏe (Parent + Children)
-- INSERT INTO `categories` (`name`, `type`, `color`, `icon`, `is_default`, `parent_id`) VALUES
-- ('Sức khỏe', 'expense', '#e67e22', 'fa-heartbeat', 1, NULL);
-- SET @parent_suckhoe = LAST_INSERT_ID();

-- INSERT INTO `categories` (`name`, `type`, `color`, `icon`, `is_default`, `parent_id`) VALUES
-- ('Khám sức khỏe', 'expense', '#e67e22', 'fa-stethoscope', 1, @parent_suckhoe),
-- ('Thể dục thể thao', 'expense', '#e67e22', 'fa-running', 1, @parent_suckhoe);

-- -- 7. Giáo dục
-- INSERT INTO `categories` (`name`, `type`, `color`, `icon`, `is_default`, `parent_id`) VALUES
-- ('Giáo dục', 'expense', '#2c3e50', 'fa-graduation-cap', 1, NULL);

-- -- 8. Giải trí (Parent + Children)
-- INSERT INTO `categories` (`name`, `type`, `color`, `icon`, `is_default`, `parent_id`) VALUES
-- ('Giải trí', 'expense', '#f39c12', 'fa-gamepad', 1, NULL);
-- SET @parent_giaitri = LAST_INSERT_ID();

-- INSERT INTO `categories` (`name`, `type`, `color`, `icon`, `is_default`, `parent_id`) VALUES
-- ('Dịch vụ trực tuyến', 'expense', '#f39c12', 'fa-globe', 1, @parent_giaitri),
-- ('Vui - chơi', 'expense', '#f39c12', 'fa-smile', 1, @parent_giaitri);

-- -- 9. Quà tặng & Quyên góp
-- INSERT INTO `categories` (`name`, `type`, `color`, `icon`, `is_default`, `parent_id`) VALUES
-- ('Quà tặng & Quyên góp', 'expense', '#e91e63', 'fa-gift', 1, NULL);

-- -- 10. Bảo hiểm
-- INSERT INTO `categories` (`name`, `type`, `color`, `icon`, `is_default`, `parent_id`) VALUES
-- ('Bảo hiểm', 'expense', '#607d8b', 'fa-shield-alt', 1, NULL);

-- -- 11. Đầu tư
-- INSERT INTO `categories` (`name`, `type`, `color`, `icon`, `is_default`, `parent_id`) VALUES
-- ('Đầu tư', 'expense', '#2980b9', 'fa-chart-line', 1, NULL);

-- -- 12. Các chi phí khác
-- INSERT INTO `categories` (`name`, `type`, `color`, `icon`, `is_default`, `parent_id`) VALUES
-- ('Các chi phí khác', 'expense', '#95a5a6', 'fa-ellipsis-h', 1, NULL);

-- -- 13. Tiền chuyến đi
-- INSERT INTO `categories` (`name`, `type`, `color`, `icon`, `is_default`, `parent_id`) VALUES
-- ('Tiền chuyến đi', 'expense', '#16a085', 'fa-plane', 1, NULL);

-- -- 14. Trả lãi
-- INSERT INTO `categories` (`name`, `type`, `color`, `icon`, `is_default`, `parent_id`) VALUES
-- ('Trả lãi', 'expense', '#c0392b', 'fa-percent', 1, NULL);

-- -- ============================================
-- -- KHOẢN THU (INCOME CATEGORIES)
-- -- ============================================

-- -- 1. Lương
-- INSERT INTO `categories` (`name`, `type`, `color`, `icon`, `is_default`, `parent_id`) VALUES
-- ('Lương', 'income', '#27ae60', 'fa-money-bill-wave', 1, NULL);

-- -- 2. Thu nhập khác
-- INSERT INTO `categories` (`name`, `type`, `color`, `icon`, `is_default`, `parent_id`) VALUES
-- ('Thu nhập khác', 'income', '#2ecc71', 'fa-coins', 1, NULL);

-- -- 3. Tiền chuyển đến
-- INSERT INTO `categories` (`name`, `type`, `color`, `icon`, `is_default`, `parent_id`) VALUES
-- ('Tiền chuyển đến', 'income', '#1abc9c', 'fa-arrow-down', 1, NULL);

-- -- 4. Thu lãi
-- INSERT INTO `categories` (`name`, `type`, `color`, `icon`, `is_default`, `parent_id`) VALUES
-- USE `quan_ly_chi_tieu`;

-- SET FOREIGN_KEY_CHECKS = 0;

-- -- ============================================
-- -- Seed categories using the new taxonomy (destructive replacement)
-- -- Top-level groups: Khoản Chi (Chi tiêu), Khoản Thu (Thu nhập), Nợ/Cho vay
-- -- ============================================

-- -- Top-level: Khoản Chi (Chi tiêu)
-- INSERT INTO `categories` (user_id, parent_id, name, type, color, icon, is_default, created_at)
-- VALUES (NULL, NULL, 'Khoản Chi (Chi tiêu)', 'expense', '#E74C3C', 'fa-folder', 1, NOW());
-- SET @khoan_chi_id = LAST_INSERT_ID();

-- -- Children of Khoản Chi
-- INSERT INTO `categories` (user_id, parent_id, name, type, color, icon, is_default, created_at) VALUES
-- (NULL, @khoan_chi_id, 'Ăn uống', 'expense', '#F39C12', 'fa-utensils', 1, NOW()),
-- (NULL, @khoan_chi_id, 'Hoá đơn & Tiện ích', 'expense', '#3498DB', 'fa-file-invoice', 1, NOW()),
-- (NULL, @khoan_chi_id, 'Mua sắm', 'expense', '#2ECC71', 'fa-shopping-bag', 1, NOW()),
-- (NULL, @khoan_chi_id, 'Gia đình', 'expense', '#8E44AD', 'fa-users', 1, NOW()),
-- (NULL, @khoan_chi_id, 'Di chuyển', 'expense', '#34495E', 'fa-car-side', 1, NOW()),
-- (NULL, @khoan_chi_id, 'Sức khoẻ', 'expense', '#16A085', 'fa-heartbeat', 1, NOW()),
-- (NULL, @khoan_chi_id, 'Giáo dục', 'expense', '#9B59B6', 'fa-graduation-cap', 1, NOW()),
-- (NULL, @khoan_chi_id, 'Giải trí', 'expense', '#E74C3C', 'fa-film', 1, NOW()),
-- (NULL, @khoan_chi_id, 'Quà tặng & Quyên góp', 'expense', '#FF6B6B', 'fa-gift', 1, NOW()),
-- (NULL, @khoan_chi_id, 'Bảo hiểm', 'expense', '#2C3E50', 'fa-shield-alt', 1, NOW()),
-- (NULL, @khoan_chi_id, 'Đầu tư', 'expense', '#27AE60', 'fa-chart-line', 1, NOW()),
-- (NULL, @khoan_chi_id, 'Các chi phí khác', 'expense', '#7F8C8D', 'fa-ellipsis-h', 1, NOW()),
-- (NULL, @khoan_chi_id, 'Tiền chuyến đi', 'expense', '#1ABC9C', 'fa-plane', 1, NOW()),
-- (NULL, @khoan_chi_id, 'Trả lãi', 'expense', '#C0392B', 'fa-coins', 1, NOW());

-- -- Hoá đơn & Tiện ích children
-- SET @hoadon_id = (SELECT id FROM categories WHERE name = 'Hoá đơn & Tiện ích' AND parent_id = @khoan_chi_id LIMIT 1);
-- INSERT INTO `categories` (user_id, parent_id, name, type, color, icon, is_default, created_at) VALUES
-- (NULL, @hoadon_id, 'Hoá đơn điện thoại', 'expense', '#9B59B6', 'fa-mobile-alt', 1, NOW()),
-- (NULL, @hoadon_id, 'Hoá đơn nước', 'expense', '#3498DB', 'fa-tint', 1, NOW()),
-- (NULL, @hoadon_id, 'Hoá đơn điện', 'expense', '#2980B9', 'fa-plug', 1, NOW()),
-- (NULL, @hoadon_id, 'Hoá đơn gas', 'expense', '#E67E22', 'fa-fire', 1, NOW()),
-- (NULL, @hoadon_id, 'Hoá đơn TV', 'expense', '#7F8C8D', 'fa-tv', 1, NOW()),
-- (NULL, @hoadon_id, 'Hoá đơn internet', 'expense', '#1ABC9C', 'fa-wifi', 1, NOW()),
-- (NULL, @hoadon_id, 'Thuê nhà', 'expense', '#95A5A6', 'fa-home', 1, NOW()),
-- (NULL, @hoadon_id, 'Hoá đơn tiện ích khác', 'expense', '#BDC3C7', 'fa-receipt', 1, NOW());

-- -- Mua sắm children
-- SET @muasam_id = (SELECT id FROM categories WHERE name = 'Mua sắm' AND parent_id = @khoan_chi_id LIMIT 1);
-- INSERT INTO `categories` (user_id, parent_id, name, type, color, icon, is_default, created_at) VALUES
-- (NULL, @muasam_id, 'Đồ dùng cá nhân', 'expense', '#F1C40F', 'fa-user', 1, NOW()),
-- (NULL, @muasam_id, 'Đồ gia dụng', 'expense', '#E67E22', 'fa-couch', 1, NOW()),
-- (NULL, @muasam_id, 'Làm đẹp', 'expense', '#FF6B81', 'fa-spa', 1, NOW());

-- -- Gia đình children
-- SET @giadinh_id = (SELECT id FROM categories WHERE name = 'Gia đình' AND parent_id = @khoan_chi_id LIMIT 1);
-- INSERT INTO `categories` (user_id, parent_id, name, type, color, icon, is_default, created_at) VALUES
-- (NULL, @giadinh_id, 'Sửa & trang trí nhà', 'expense', '#D35400', 'fa-tools', 1, NOW()),
-- (NULL, @giadinh_id, 'Dịch vụ gia đình', 'expense', '#7D3C98', 'fa-concierge-bell', 1, NOW()),
-- (NULL, @giadinh_id, 'Vật nuôi', 'expense', '#27AE60', 'fa-paw', 1, NOW());

-- -- Di chuyển children
-- SET @dichuyen_id = (SELECT id FROM categories WHERE name = 'Di chuyển' AND parent_id = @khoan_chi_id LIMIT 1);
-- INSERT INTO `categories` (user_id, parent_id, name, type, color, icon, is_default, created_at)
-- VALUES (NULL, @dichuyen_id, 'Bảo dưỡng xe', 'expense', '#95A5A6', 'fa-tools', 1, NOW());

-- -- Sức khoẻ children
-- SET @suckhoe_id = (SELECT id FROM categories WHERE name = 'Sức khoẻ' AND parent_id = @khoan_chi_id LIMIT 1);
-- INSERT INTO `categories` (user_id, parent_id, name, type, color, icon, is_default, created_at) VALUES
-- (NULL, @suckhoe_id, 'Khám sức khoẻ', 'expense', '#3498DB', 'fa-stethoscope', 1, NOW()),
-- (NULL, @suckhoe_id, 'Thể dục thể thao', 'expense', '#1ABC9C', 'fa-dumbbell', 1, NOW());

-- -- Giải trí children
-- SET @giaitri_id = (SELECT id FROM categories WHERE name = 'Giải trí' AND parent_id = @khoan_chi_id LIMIT 1);
-- INSERT INTO `categories` (user_id, parent_id, name, type, color, icon, is_default, created_at) VALUES
-- (NULL, @giaitri_id, 'Dịch vụ trực tuyến', 'expense', '#2980B9', 'fa-broadcast-tower', 1, NOW()),
-- (NULL, @giaitri_id, 'Vui - chơi', 'expense', '#F39C12', 'fa-gamepad', 1, NOW());

-- -- ============================================
-- -- 2. KHOẢN THU (INCOME CATEGORIES)
-- -- ============================================

-- -- Top-level: Khoản Thu (Thu nhập)
-- INSERT INTO `categories` (user_id, parent_id, name, type, color, icon, is_default, created_at)
-- VALUES (NULL, NULL, 'Khoản Thu (Thu nhập)', 'income', '#2ECC71', 'fa-folder-open', 1, NOW());
-- SET @khoan_thu_id = LAST_INSERT_ID();

-- -- Children of Khoản Thu (Tất cả đều là 'income')
-- INSERT INTO `categories` (user_id, parent_id, name, type, color, icon, is_default, created_at) VALUES
-- (NULL, @khoan_thu_id, 'Lương', 'income', '#2980B9', 'fa-money-bill-wave', 1, NOW()),
-- (NULL, @khoan_thu_id, 'Thu nhập khác', 'income', '#16A085', 'fa-wallet', 1, NOW()),
-- (NULL, @khoan_thu_id, 'Tiền chuyển đến', 'income', '#9B59B6', 'fa-exchange-alt', 1, NOW()),
-- (NULL, @khoan_thu_id, 'Thu lãi', 'income', '#27AE60', 'fa-percentage', 1, NOW());

-- -- ============================================
-- -- 3. NỢ/CHO VAY (FIXED LOAN/DEBT TYPES)
-- -- ============================================

-- -- Top-level: Nợ/Cho vay
-- INSERT INTO `categories` (user_id, parent_id, name, type, color, icon, is_default, created_at)
-- VALUES (NULL, NULL, 'Nợ/Cho vay', 'expense', '#7F8C8D', 'fa-hand-holding-usd', 1, NOW());
-- SET @no_id = LAST_INSERT_ID();

-- INSERT INTO `categories` (user_id, parent_id, name, type, color, icon, is_default, created_at) VALUES
-- (NULL, @no_id, 'Cho vay', 'expense', '#E67E22', 'fa-handshake', 1, NOW()),    -- Giảm số dư: expense
-- (NULL, @no_id, 'Trả nợ', 'expense', '#C0392B', 'fa-reply', 1, NOW()),         -- Giảm số dư: expense
-- (NULL, @no_id, 'Đi vay', 'income', '#95A5A6', 'fa-money-check-alt', 1, NOW()),  -- Tăng số dư: income (Đã Fix)
-- (NULL, @no_id, 'Thu nợ', 'income', '#2C3E50', 'fa-receipt', 1, NOW());         -- Tăng số dư: income (Đã Fix)

-- SET FOREIGN_KEY_CHECKS = 1;
-- -- End of seed data (new taxonomy)
