-- SmartSpending - Sample Data for October 2025
USE `quan_ly_chi_tieu`;

-- Sample transactions for October 2025
INSERT INTO `transactions` (user_id, category_id, amount, date, description, type) VALUES
(2, 2, -200000, '2025-10-01', 'Ăn sáng', 'expense'),
(2, 2, -500000, '2025-10-02', 'Ăn trưa', 'expense'),
(2, 2, -300000, '2025-10-03', 'Ăn tối', 'expense'),
(2, 3, -400000, '2025-10-04', 'Tiền điện', 'expense'),
(2, 3, -250000, '2025-10-05', 'Tiền nước', 'expense'),
(2, 3, -150000, '2025-10-06', 'Tiền internet', 'expense'),
(2, 7, -1000000, '2025-10-07', 'Mua quần áo', 'expense'),
(2, 8, -800000, '2025-10-08', 'Mua đồ gia dụng', 'expense'),
(2, 13, -300000, '2025-10-09', 'Vé xem phim', 'expense'),
(2, 13, -200000, '2025-10-10', 'Đi chơi bowling', 'expense'),
(2, 15, 9000000, '2025-10-25', 'Lương tháng 10', 'income'),
(2, 16, 500000, '2025-10-26', 'Thu nhập khác', 'income'),
(2, 9, -150000, '2025-10-11', 'Tiền xăng', 'expense'),
(2, 10, -200000, '2025-10-12', 'Khám sức khỏe', 'expense'),
(2, 17, -400000, '2025-10-13', 'Học phí', 'expense'),
(2, 18, -300000, '2025-10-14', 'Quà sinh nhật', 'expense'),
(2, 19, -250000, '2025-10-15', 'Bảo hiểm xe', 'expense'),
(2, 20, -1000000, '2025-10-16', 'Đầu tư chứng khoán', 'expense'),
(2, 21, -50000, '2025-10-17', 'Chi phí khác', 'expense');