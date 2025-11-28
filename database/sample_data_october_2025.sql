

USE `quan_ly_chi_tieu`;

-- Xóa dữ liệu cũ của tháng 10 nếu có
DELETE FROM `transactions` WHERE user_id = 1 AND transaction_date >= '2025-10-01' AND transaction_date <= '2025-10-31';

-- Thêm giao dịch tháng 10/2025 cho user_id = 1
-- Thu nhập tháng 10
INSERT INTO `transactions` (`user_id`, `category_id`, `amount`, `description`, `transaction_date`) VALUES
(1, 9, 5000000, 'Lương tháng 10', '2025-10-01'),
(1, 10, 1000000, 'Thưởng dự án', '2025-10-15'),
(1, 12, 500000, 'Làm thêm cuối tuần', '2025-10-20');

-- Chi tiêu tháng 10
INSERT INTO `transactions` (`user_id`, `category_id`, `amount`, `description`, `transaction_date`) VALUES
(1, 3, -1500000, 'Tiền nhà tháng 10', '2025-10-01'),
(1, 4, -800000, 'Tiền điện nước', '2025-10-02'),
(1, 1, -250000, 'Chợ cuối tuần', '2025-10-05'),
(1, 2, -150000, 'Tiền xăng', '2025-10-06'),
(1, 1, -85000, 'Ăn trưa văn phòng', '2025-10-07'),
(1, 7, -450000, 'Mua giày thể thao', '2025-10-08'),
(1, 1, -120000, 'Cafe với bạn bè', '2025-10-09'),
(1, 5, -300000, 'Vé xem phim', '2025-10-10'),
(1, 1, -180000, 'Siêu thị', '2025-10-12'),
(1, 2, -50000, 'Grab/Taxi', '2025-10-14'),
(1, 6, -500000, 'Khám bác sĩ', '2025-10-15'),
(1, 1, -95000, 'Ăn sáng ngoài', '2025-10-16'),
(1, 7, -280000, 'Mua quần áo', '2025-10-17'),
(1, 5, -200000, 'Đi karaoke', '2025-10-18'),
(1, 1, -160000, 'Ăn nhà hàng', '2025-10-19'),
(1, 2, -100000, 'Sửa xe', '2025-10-21'),
(1, 1, -220000, 'Chợ cuối tuần', '2025-10-22'),
(1, 7, -350000, 'Mua đồ điện tử', '2025-10-23'),
(1, 5, -180000, 'Game online', '2025-10-24'),
(1, 1, -75000, 'Cafe làm việc', '2025-10-25'),
(1, 2, -120000, 'Tiền xăng', '2025-10-26'),
(1, 8, -150000, 'Chi tiêu khác', '2025-10-28'),
(1, 1, -190000, 'Đi chợ', '2025-10-29'),
(1, 5, -250000, 'Đi ăn tối', '2025-10-30'),
(1, 1, -80000, 'Ăn sáng', '2025-10-31');

