

USE `quan_ly_chi_tieu`;

-- Xóa dữ liệu cũ của tháng 10 nếu có
DELETE FROM `transactions` WHERE user_id = 2 AND transaction_date >= '2025-10-01' AND transaction_date <= '2025-10-31';

-- Thêm giao dịch tháng 10/2025 cho user_id = 2
-- Thu nhập tháng 10
INSERT INTO `transactions` (`user_id`, `category_id`, `amount`, `description`, `transaction_date`) VALUES
(2, 9, 5000000, 'Lương tháng 10', '2025-10-01'),
(2, 10, 1000000, 'Thưởng dự án', '2025-10-15'),
(2, 12, 500000, 'Làm thêm cuối tuần', '2025-10-20');

-- Chi tiêu tháng 10
INSERT INTO `transactions` (`user_id`, `category_id`, `amount`, `description`, `transaction_date`) VALUES
(2, 3, -1500000, 'Tiền nhà tháng 10', '2025-10-01'),
(2, 4, -800000, 'Tiền điện nước', '2025-10-02'),
(2, 2, -250000, 'Chợ cuối tuần', '2025-10-05'),
(2, 2, -150000, 'Tiền xăng', '2025-10-06'),
(2, 2, -85000, 'Ăn trưa văn phòng', '2025-10-07'),
(2, 7, -450000, 'Mua giày thể thao', '2025-10-08'),
(2, 2, -120000, 'Cafe với bạn bè', '2025-10-09'),
(2, 5, -300000, 'Vé xem phim', '2025-10-10'),
(2, 2, -180000, 'Siêu thị', '2025-10-12'),
(2, 2, -50000, 'Grab/Taxi', '2025-10-14'),
(2, 6, -500000, 'Khám bác sĩ', '2025-10-15'),
(2, 2, -95000, 'Ăn sáng ngoài', '2025-10-16'),
(2, 7, -280000, 'Mua quần áo', '2025-10-17'),
(2, 5, -200000, 'Đi karaoke', '2025-10-18'),
(2, 2, -160000, 'Ăn nhà hàng', '2025-10-19'),
(2, 2, -100000, 'Sửa xe', '2025-10-21'),
(2, 2, -220000, 'Chợ cuối tuần', '2025-10-22'),
(2, 7, -350000, 'Mua đồ điện tử', '2025-10-23'),
(2, 5, -180000, 'Game online', '2025-10-24'),
(2, 2, -75000, 'Cafe làm việc', '2025-10-25'),
(2, 2, -120000, 'Tiền xăng', '2025-10-26'),
(2, 8, -150000, 'Chi tiêu khác', '2025-10-28'),
(2, 2, -190000, 'Đi chợ', '2025-10-29'),
(2, 5, -250000, 'Đi ăn tối', '2025-10-30'),
(2, 2, -80000, 'Ăn sáng', '2025-10-31');

