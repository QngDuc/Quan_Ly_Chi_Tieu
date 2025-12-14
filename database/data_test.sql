-- ==========================================================
-- BIG DATA TEST - THÁNG 10 & 11/2025
-- User ID: 2 (Admin)
-- Note: Xoá data cũ trước khi nạp để tránh trùng lặp
-- ==========================================================

DELETE FROM `transactions` WHERE user_id = 2;
DELETE FROM `budgets` WHERE user_id = 2;
DELETE FROM `user_wallets` WHERE user_id = 2;

-- Khởi tạo lại ví về 0
INSERT IGNORE INTO `user_wallets` (user_id, jar_code, balance) VALUES 
(2, 'nec', 0), (2, 'ffa', 0), (2, 'ltss', 0), (2, 'edu', 0), (2, 'play', 0), (2, 'give', 0);

-- ==========================================================
-- 1. THÁNG 10/2025 (QUÁ KHỨ - FULL THÁNG)
-- ==========================================================

INSERT INTO `transactions` (user_id, category_id, amount, date, description, type) VALUES
-- THU NHẬP (Tổng: ~25 Triệu)
(2, 16, 18000000, '2025-10-01', 'Lương cứng T10', 'income'),
(2, 17, 5000000, '2025-10-15', 'Thưởng dự án Tech', 'income'),
(2, 19, 2500000, '2025-10-28', 'Freelance code web', 'income'),

-- CHI TIÊU HÀNG NGÀY (Ăn uống - ID 3)
(2, 3, -45000, '2025-10-01', 'Bún bò Huế', 'expense'),
(2, 3, -35000, '2025-10-02', 'Cơm tấm', 'expense'),
(2, 3, -55000, '2025-10-03', 'Phở đặc biệt', 'expense'),
(2, 3, -3000000, '2025-10-04', 'Siêu thị Go! (Thực phẩm tuần 1)', 'expense'),
(2, 3, -40000, '2025-10-05', 'Bánh mì chảo', 'expense'),
(2, 3, -150000, '2025-10-06', 'Highlands Coffee với đối tác', 'expense'),
(2, 3, -2500000, '2025-10-15', 'Siêu thị Coopmart (Thực phẩm tuần 3)', 'expense'),
(2, 3, -500000, '2025-10-20', 'Liên hoan công ty', 'expense'),
(2, 3, -45000, '2025-10-25', 'Hủ tiếu Nam Vang', 'expense'),

-- HOÁ ĐƠN & TIỆN ÍCH (ID 2 & 14)
(2, 2, -1250000, '2025-10-10', 'Tiền điện tháng 10', 'expense'),
(2, 2, -180000, '2025-10-10', 'Tiền nước', 'expense'),
(2, 14, -350000, '2025-10-10', 'Internet VNPT 6 tháng', 'expense'),
(2, 14, -150000, '2025-10-01', 'Gói cước 4G Mobifone', 'expense'),

-- MUA SẮM & GIẢI TRÍ (PLAY - ID 7 & 9 & 4)
(2, 7, -850000, '2025-10-08', 'Mua giày Adidas', 'expense'),
(2, 7, -320000, '2025-10-12', 'Mua sách Tiki', 'expense'),
(2, 9, -220000, '2025-10-14', 'Vé xem phim CGV', 'expense'),
(2, 9, -500000, '2025-10-25', 'Netflix Premium', 'expense'),
(2, 4, -1500000, '2025-10-20', 'Trip Vũng Tàu 2N1Đ', 'expense'),

-- SỨC KHOẺ & CÁ NHÂN (NEC - ID 5 & 6)
(2, 5, -600000, '2025-10-05', 'Khám răng', 'expense'),
(2, 6, -200000, '2025-10-18', 'Cắt tóc + Gội đầu', 'expense'),

-- ĐẦU TƯ & TIẾT KIỆM (FFA - ID 10)
(2, 10, -5000000, '2025-10-02', 'Mua 0.5 chỉ vàng', 'expense'),
(2, 10, -2000000, '2025-10-30', 'Gửi tiết kiệm lãi kép', 'expense'),

-- TỪ THIỆN (GIVE - ID 13)
(2, 13, -500000, '2025-10-15', 'Mừng cưới bạn thân', 'expense');


-- ==========================================================
-- 2. THÁNG 11/2025 (HIỆN TẠI - DATA ĐỂ TEST CẢNH BÁO)
-- ==========================================================

INSERT INTO `transactions` (user_id, category_id, amount, date, description, type) VALUES
-- THU NHẬP
(2, 16, 18000000, '2025-11-01', 'Lương cứng T11', 'income'),
(2, 19, 1200000, '2025-11-12', 'Bán bàn phím cũ', 'income'),

-- CHI TIÊU HÀNG NGÀY (Ăn uống - ID 3)
(2, 3, -50000, '2025-11-01', 'Ăn sáng', 'expense'),
(2, 3, -60000, '2025-11-02', 'Cơm gà xối mỡ', 'expense'),
(2, 3, -3200000, '2025-11-03', 'BigC (Đồ ăn cả tháng)', 'expense'),
(2, 3, -55000, '2025-11-04', 'Phở tái nạm', 'expense'),
(2, 3, -1200000, '2025-11-10', 'Ăn buffet Poseidon', 'expense'), -- Ăn sang chảnh
(2, 3, -40000, '2025-11-11', 'Bánh mì dân tổ', 'expense'),
(2, 3, -40000, '2025-11-12', 'Xôi xéo', 'expense'),

-- SĂN SALE 11/11 (MUA SẮM - ID 7 - Gây lố ngân sách)
(2, 7, -2500000, '2025-11-11', 'Shopee Sale: Quần áo', 'expense'),
(2, 7, -1500000, '2025-11-11', 'Lazada Sale: Mỹ phẩm', 'expense'),
(2, 7, -800000, '2025-11-12', 'Tiktok Shop: Đồ gia dụng', 'expense'),

-- HOÁ ĐƠN (ID 2)
(2, 2, -1100000, '2025-11-05', 'Điện tháng 11 (Giảm do trời mát)', 'expense'),
(2, 2, -150000, '2025-11-05', 'Nước sinh hoạt', 'expense'),

-- XE CỘ & DI CHUYỂN (ID 4 - PLAY/NEC tuỳ cấu hình, ở đây map vào Play)
(2, 4, -500000, '2025-11-08', 'Thay nhớt xe + Bảo dưỡng', 'expense'),
(2, 4, -100000, '2025-11-01', 'Đổ xăng đầy bình', 'expense'),
(2, 4, -100000, '2025-11-10', 'Đổ xăng', 'expense'),

-- GIÁO DỤC (EDU - ID 8)
(2, 8, -2500000, '2025-11-14', 'Đóng học phí IELTS', 'expense'),

-- TRẢ NỢ (ID 12)
(2, 12, -5000000, '2025-11-02', 'Trả góp thẻ tín dụng', 'expense');

-- ==========================================================
-- 3. CÀI ĐẶT NGÂN SÁCH THÁNG 11 (BUDGETS)
-- Mục đích: Để thấy cái Xanh, cái Đỏ, cái Vàng
-- ==========================================================

INSERT INTO `budgets` (user_id, category_id, amount, period, start_date, end_date, alert_threshold) VALUES
-- 1. Ăn uống (ID 3): Ngân sách 6tr.
-- Thực tế tiêu: ~4.6tr -> Trạng thái: AN TOÀN (Xanh)
(2, 3, 6000000, 'monthly', '2025-11-01', '2025-11-30', 80),

-- 2. Mua sắm (ID 7): Ngân sách 3tr.
-- Thực tế tiêu: 4.8tr (Do săn sale 11/11) -> Trạng thái: NGUY HIỂM (Đỏ Lòm)
(2, 7, 3000000, 'monthly', '2025-11-01', '2025-11-30', 80),

-- 3. Giáo dục (ID 8): Ngân sách 3tr.
-- Thực tế tiêu: 2.5tr -> Trạng thái: CẢNH BÁO (Vàng - Do đã đạt >80%)
(2, 8, 3000000, 'monthly', '2025-11-01', '2025-11-30', 80),

-- 4. Hoá đơn (ID 2): Ngân sách 2tr.
-- Thực tế tiêu: 1.25tr -> Trạng thái: AN TOÀN (Xanh)
(2, 2, 2000000, 'monthly', '2025-11-01', '2025-11-30', 80);