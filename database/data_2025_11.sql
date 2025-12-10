-- SmartSpending - Sample Data for November 2025
USE `quan_ly_chi_tieu`;

-- Sample transactions for November 2025
INSERT INTO `transactions` (user_id, category_id, amount, date, description, type)
VALUES
(2, 2, -220000, '2025-11-01', 'Ăn sáng', 'expense'),
(2, 2, -520000, '2025-11-02', 'Ăn trưa', 'expense'),
(2, 2, -320000, '2025-11-03', 'Ăn tối', 'expense'),
(2, 3, -410000, '2025-11-04', 'Tiền điện', 'expense'),
(2, 3, -260000, '2025-11-05', 'Tiền nước', 'expense'),
(2, 3, -160000, '2025-11-06', 'Tiền internet', 'expense'),
(2, 7, -1100000, '2025-11-07', 'Mua quần áo', 'expense'),
(2, 8, -850000, '2025-11-08', 'Mua đồ gia dụng', 'expense'),
(2, 13, -350000, '2025-11-09', 'Vé xem phim', 'expense'),
(2, 13, -250000, '2025-11-10', 'Đi chơi bowling', 'expense'),
(2, 15, 9000000, '2025-11-25', 'Lương tháng 11', 'income'),
(2, 16, 600000, '2025-11-26', 'Thu nhập khác', 'income'),
(2, 9, -180000, '2025-11-11', 'Tiền xăng', 'expense'),
(2, 10, -250000, '2025-11-12', 'Khám sức khỏe', 'expense'),
(2, 17, -450000, '2025-11-13', 'Học phí', 'expense'),
(2, 18, -350000, '2025-11-14', 'Quà sinh nhật', 'expense'),
(2, 19, -300000, '2025-11-15', 'Bảo hiểm xe', 'expense'),
(2, 20, -1200000, '2025-11-16', 'Đầu tư chứng khoán', 'expense'),
(2, 21, -70000, '2025-11-17', 'Chi phí khác', 'expense');
