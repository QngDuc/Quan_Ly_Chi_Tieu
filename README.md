# Quan Ly Chi Tieu - Hệ Thống Quản Lý Tài Chính Cá Nhân

Một ứng dụng quản lý tài chính cá nhân dựa trên web được xây dựng bằng PHP và MySQL. Hệ thống này giúp người dùng theo dõi thu nhập, chi tiêu, ngân sách, mục tiêu tiết kiệm và tạo báo cáo tài chính.

## Tính Năng

- **Xác Thực Người Dùng**: Hệ thống đăng nhập và đăng ký an toàn
- **Bảng Điều Khiển**: Tổng quan về tình trạng tài chính với biểu đồ và tóm tắt
- **Quản Lý Giao Dịch**: Thêm, chỉnh sửa và phân loại giao dịch thu nhập và chi tiêu
- **Theo Dõi Ngân Sách**: Thiết lập và giám sát ngân sách hàng tháng/hàng tuần theo danh mục
- **Mục Tiêu Tiết Kiệm**: Tạo và theo dõi tiến độ hướng tới mục tiêu tài chính
- **Báo Cáo Tài Chính**: Tạo báo cáo chi tiết và phân tích
- **Quản Lý Hồ Sơ**: Cập nhật thông tin cá nhân và sở thích

## Yêu Cầu Hệ Thống

Trước khi chạy ứng dụng này, hãy đảm bảo bạn đã cài đặt các phần mềm sau:

- **XAMPP** (hoặc bất kỳ máy chủ web nào hỗ trợ PHP và MySQL)
- **PHP 7.4 trở lên**
- **MySQL 5.7 trở lên**
- **Git**
- **Trình duyệt web** (Chrome, Firefox, Safari, v.v.)

## Cài Đặt

### Bước 1: Sao Chép Kho Lưu Trữ

Mở terminal/command prompt và chạy:

```bash
git clone https://github.com/HuyHoangI4t/Quan_Ly_Chi_Tieu.git
```

```bash
cd Quan_Ly_Chi_Tieu
```

### Bước 2: Thiết Lập Dự Án

1. Sao chép thư mục dự án vào thư mục gốc tài liệu của máy chủ web:
   - Đối với XAMPP: Sao chép vào `C:\xampp\htdocs\`
   - Dự án sẽ truy cập được tại `C:\xampp\htdocs\Quan_Ly_Chi_Tieu`

### Bước 3: Thiết Lập Cơ Sở Dữ Liệu

1. Khởi động XAMPP và đảm bảo dịch vụ Apache và MySQL đang chạy
2. Mở phpMyAdmin (thường tại `http://localhost/phpmyadmin`)
3. Tạo cơ sở dữ liệu mới có tên `quan_ly_chi_tieu`
4. Nhập lược đồ cơ sở dữ liệu:
   - Chuyển đến tab "Import" trong phpMyAdmin
   - Chọn tệp `database/quan_ly_chi_tieu.sql` từ dự án
   - Nhấp "Go" để nhập

### Bước 4: Cấu Hình

Ứng dụng đi kèm với cấu hình cơ sở dữ liệu mặc định. Nếu bạn cần thay đổi cài đặt cơ sở dữ liệu:

1. Mở `config/database.php`
2. Cập nhật các hằng số nếu cần:
   ```php
   define('DB_HOST', '127.0.0.1'); // hoặc  define('DB_HOST', 'localhost');
   define('DB_USER', 'root');
   define('DB_PASS', ''); // Thêm mật khẩu nếu đã đặt
   define('DB_NAME', 'quan_ly_chi_tieu');
   ```

## Cách Sử Dụng

### Truy Cập Ứng Dụng

1. Khởi động XAMPP (Apache và MySQL)
2. Mở trình duyệt web
3. Điều hướng đến: `http://localhost/Quan_Ly_Chi_Tieu/public`

### Bắt Đầu Sử Dụng

1. **Đăng Ký**: Tạo tài khoản mới với email và mật khẩu
2. **Đăng Nhập**: Sử dụng thông tin đăng nhập để truy cập bảng điều khiển
3. **Thêm Giao Dịch**: Bắt đầu theo dõi thu nhập và chi tiêu
4. **Thiết Lập Ngân Sách**: Tạo ngân sách cho các danh mục chi tiêu khác nhau
5. **Theo Dõi Mục Tiêu**: Thiết lập mục tiêu tiết kiệm và giám sát tiến độ
6. **Xem Báo Cáo**: Phân tích dữ liệu tài chính với báo cáo chi tiết

### Điều Hướng

- **Bảng Điều Khiển**: Trang tổng quan chính
- **Giao Dịch**: Quản lý thu nhập và chi tiêu
- **Ngân Sách**: Thiết lập và giám sát giới hạn chi tiêu
- **Mục Tiêu**: Theo dõi mục tiêu tiết kiệm
- **Báo Cáo**: Xem phân tích tài chính
- **Hồ Sơ**: Cập nhật thông tin cá nhân

## Cấu Trúc Dự Án

```
Quan_Ly_Chi_Tieu/
├── app/
│   ├── controllers/       # Bộ điều khiển ứng dụng
│   ├── core/              # Lớp ứng dụng cốt lõi
│   ├── models/            # Mô hình dữ liệu
│   └── views/             # Mẫu giao diện
├── config/                # Tệp cấu hình
├── database/              # Lược đồ cơ sở dữ liệu và di chuyển
├── public/                # Tài sản web công khai
│   ├── css/               # Bảng kiểu
│   ├── js/                # Tệp JavaScript
│   └── images/            # Tài sản hình ảnh
└── README.md              # Tệp này
```

## Công Nghệ Sử Dụng

- **Backend**: PHP (Kiến trúc MVC)
- **Cơ Sở Dữ Liệu**: MySQL
- **Frontend**: HTML5, CSS3, JavaScript
- **Tạo Kiểu**: CSS tùy chỉnh với biểu tượng Font Awesome
- **Biểu Đồ**: Chart.js để trực quan hóa dữ liệu

## Đóng Góp

1. Fork kho lưu trữ
2. Tạo nhánh tính năng (`git checkout -b feature/AmazingFeature`)
3. Cam kết thay đổi của bạn (`git commit -m 'Add some AmazingFeature'`)
4. Đẩy lên nhánh (`git push origin feature/AmazingFeature`)
5. Mở Pull Request

## Giấy Phép

Dự án này được cấp phép theo Giấy phép MIT - xem tệp LICENSE để biết chi tiết.

## Hỗ Trợ

Nếu bạn gặp vấn đề hoặc có câu hỏi:

1. Kiểm tra lại các bước cài đặt
2. Đảm bảo dịch vụ XAMPP đang chạy
3. Xác minh cài đặt kết nối cơ sở dữ liệu
4. Kiểm tra nhật ký lỗi PHP trong XAMPP

Để được hỗ trợ thêm, vui lòng tạo vấn đề trong kho lưu trữ.
