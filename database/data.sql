-- ============================================
-- SmartSpending - Data Seeding (Screenshot Match)
-- Version: 6.1.0 (Full Hierarchy)
-- ============================================

USE `quan_ly_chi_tieu`;

SET FOREIGN_KEY_CHECKS = 0;
-- TRUNCATE TABLE `categories`;
INSERT INTO users (username, email, password, full_name, role) VALUES
('huyhoangpro187', 'huyhoangpro187@gmail.com', '$2y$10$M12D3rP.nNSdTxDMq/FQbeJfKwrPHoJSq9.itE/N3gZVt.afkEft.', 'Nguyễn Huy Hoàng', 'admin');
-- ============================================
-- NHÓM 1: KHOẢN CHI (EXPENSE)
-- ============================================

-- 1.0. Root: Khoản Chi
INSERT INTO `categories` (parent_id, name, type, color, icon, is_default) 
VALUES (NULL, 'Khoản Chi', 'expense', '#E74C3C', 'fa-wallet', 1);
SET @root_chi = LAST_INSERT_ID();

-- 1.1. Ăn uống (Level 1)
INSERT INTO `categories` (parent_id, name, type, color, icon, is_default)
VALUES (@root_chi, 'Ăn uống', 'expense', '#FF6B6B', 'fa-utensils', 1);

-- 1.2. Hoá đơn & Tiện ích (Level 1 & Children)
INSERT INTO `categories` (parent_id, name, type, color, icon, is_default)
VALUES (@root_chi, 'Hoá đơn & Tiện ích', 'expense', '#3498DB', 'fa-file-invoice-dollar', 1);
SET @parent_hoadon = LAST_INSERT_ID();

    -- Children of Hoá đơn
    INSERT INTO `categories` (parent_id, name, type, color, icon, is_default) VALUES
    (@parent_hoadon, 'Hoá đơn điện thoại', 'expense', '#9B59B6', 'fa-phone', 1),
    (@parent_hoadon, 'Hoá đơn nước', 'expense', '#3498DB', 'fa-tint', 1),
    (@parent_hoadon, 'Hoá đơn điện', 'expense', '#F1C40F', 'fa-bolt', 1),
    (@parent_hoadon, 'Hoá đơn gas', 'expense', '#E67E22', 'fa-fire', 1),
    (@parent_hoadon, 'Hoá đơn TV', 'expense', '#2C3E50', 'fa-tv', 1),
    (@parent_hoadon, 'Hoá đơn internet', 'expense', '#1ABC9C', 'fa-wifi', 1),
    (@parent_hoadon, 'Thuê nhà', 'expense', '#7F8C8D', 'fa-home', 1),
    (@parent_hoadon, 'Hoá đơn tiện ích khác', 'expense', '#BDC3C7', 'fa-receipt', 1);

-- 1.3. Mua sắm (Level 1 & Children)
INSERT INTO `categories` (parent_id, name, type, color, icon, is_default)
VALUES (@root_chi, 'Mua sắm', 'expense', '#2ECC71', 'fa-shopping-basket', 1);
SET @parent_muasam = LAST_INSERT_ID();

    -- Children of Mua sắm
    INSERT INTO `categories` (parent_id, name, type, color, icon, is_default) VALUES
    (@parent_muasam, 'Đồ dùng cá nhân', 'expense', '#16A085', 'fa-user-cog', 1),
    (@parent_muasam, 'Đồ gia dụng', 'expense', '#D35400', 'fa-couch', 1),
    (@parent_muasam, 'Làm đẹp', 'expense', '#FF9FF3', 'fa-spa', 1);

-- 1.4. Gia đình (Level 1 & Children)
INSERT INTO `categories` (parent_id, name, type, color, icon, is_default)
VALUES (@root_chi, 'Gia đình', 'expense', '#1ABC9C', 'fa-house-user', 1);
SET @parent_giadinh = LAST_INSERT_ID();

    -- Children of Gia đình
    INSERT INTO `categories` (parent_id, name, type, color, icon, is_default) VALUES
    (@parent_giadinh, 'Sửa & trang trí nhà', 'expense', '#F39C12', 'fa-paint-roller', 1),
    (@parent_giadinh, 'Dịch vụ gia đình', 'expense', '#8E44AD', 'fa-concierge-bell', 1),
    (@parent_giadinh, 'Vật nuôi', 'expense', '#27AE60', 'fa-dog', 1);

-- 1.5. Di chuyển (Level 1 & Children)
INSERT INTO `categories` (parent_id, name, type, color, icon, is_default)
VALUES (@root_chi, 'Di chuyển', 'expense', '#F1C40F', 'fa-car', 1);
SET @parent_dichuyen = LAST_INSERT_ID();

    -- Children of Di chuyển
    INSERT INTO `categories` (parent_id, name, type, color, icon, is_default) VALUES
    (@parent_dichuyen, 'Bảo dưỡng xe', 'expense', '#34495E', 'fa-wrench', 1);

-- 1.6. Sức khoẻ (Level 1 & Children)
INSERT INTO `categories` (parent_id, name, type, color, icon, is_default)
VALUES (@root_chi, 'Sức khoẻ', 'expense', '#E74C3C', 'fa-briefcase-medical', 1);
SET @parent_suckhoe = LAST_INSERT_ID();

    -- Children of Sức khoẻ
    INSERT INTO `categories` (parent_id, name, type, color, icon, is_default) VALUES
    (@parent_suckhoe, 'Khám sức khoẻ', 'expense', '#2980B9', 'fa-stethoscope', 1),
    (@parent_suckhoe, 'Thể dục thể thao', 'expense', '#2ECC71', 'fa-dumbbell', 1);

-- 1.7. Giải trí (Level 1 & Children)
INSERT INTO `categories` (parent_id, name, type, color, icon, is_default)
VALUES (@root_chi, 'Giải trí', 'expense', '#3498DB', 'fa-gamepad', 1);
SET @parent_giaitri = LAST_INSERT_ID();

    -- Children of Giải trí
    INSERT INTO `categories` (parent_id, name, type, color, icon, is_default) VALUES
    (@parent_giaitri, 'Dịch vụ trực tuyến', 'expense', '#9B59B6', 'fa-cloud', 1),
    (@parent_giaitri, 'Vui - chơi', 'expense', '#F1C40F', 'fa-dice', 1);

-- 1.8. Các mục khác (Level 1 - No children in screenshots)
INSERT INTO `categories` (parent_id, name, type, color, icon, is_default) VALUES
(@root_chi, 'Giáo dục', 'expense', '#2C3E50', 'fa-graduation-cap', 1),
(@root_chi, 'Quà tặng & Quyên góp', 'expense', '#FF6B6B', 'fa-gift', 1),
(@root_chi, 'Bảo hiểm', 'expense', '#16A085', 'fa-shield-alt', 1),
(@root_chi, 'Đầu tư', 'expense', '#F39C12', 'fa-chart-line', 1),
(@root_chi, 'Các chi phí khác', 'expense', '#95A5A6', 'fa-question-circle', 1),
(@root_chi, 'Tiền chuyến đi', 'expense', '#1ABC9C', 'fa-plane', 1),
(@root_chi, 'Trả lãi', 'expense', '#C0392B', 'fa-percentage', 1);


-- ============================================
-- NHÓM 2: KHOẢN THU (INCOME)
-- ============================================

-- 2.0. Root: Khoản Thu
INSERT INTO `categories` (parent_id, name, type, color, icon, is_default) 
VALUES (NULL, 'Khoản Thu', 'income', '#2ECC71', 'fa-piggy-bank', 1);
SET @root_thu = LAST_INSERT_ID();

-- 2.1. Children of Khoản Thu
INSERT INTO `categories` (parent_id, name, type, color, icon, is_default) VALUES
(@root_thu, 'Lương', 'income', '#27AE60', 'fa-money-bill-wave', 1),
(@root_thu, 'Thu nhập khác', 'income', '#F1C40F', 'fa-coins', 1),
(@root_thu, 'Tiền chuyển đến', 'income', '#3498DB', 'fa-hand-holding-usd', 1),
(@root_thu, 'Thu lãi', 'income', '#E67E22', 'fa-percent', 1);


-- ============================================
-- NHÓM 3: NỢ/CHO VAY (DEBT/LOAN)
-- ============================================

-- 3.0. Root: Nợ/Cho vay
INSERT INTO `categories` (parent_id, name, type, color, icon, is_default) 
VALUES (NULL, 'Nợ/Cho vay', 'expense', '#7F8C8D', 'fa-balance-scale', 1);
SET @root_no = LAST_INSERT_ID();

-- 3.1. Children of Nợ/Cho vay
-- Lưu ý: Type expense làm giảm ví, Type income làm tăng ví
INSERT INTO `categories` (parent_id, name, type, color, icon, is_default) VALUES
(@root_no, 'Cho vay', 'expense', '#E74C3C', 'fa-hand-holding-water', 1), -- Tiền đi ra
(@root_no, 'Trả nợ', 'expense', '#C0392B', 'fa-file-invoice', 1),       -- Tiền đi ra
(@root_no, 'Đi vay', 'income', '#2ECC71', 'fa-hand-holding-medical', 1), -- Tiền đi vào
(@root_no, 'Thu nợ', 'income', '#27AE60', 'fa-check-circle', 1);         -- Tiền đi vào

SET FOREIGN_KEY_CHECKS = 1;