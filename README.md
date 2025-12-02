# 💰 SmartSpending - Quản Lý Chi Tiêu

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)
[![PHP Version](https://img.shields.io/badge/PHP-7.4%2B-blue)](https://www.php.net/)
[![MySQL](https://img.shields.io/badge/MySQL-5.7%2B-orange)](https://www.mysql.com/)

Hệ thống quản lý chi tiêu cá nhân chuyên nghiệp với PHP MVC, tích hợp phương pháp **6 Jars** (T. Harv Eker), phân quyền Admin/User, và phân tích tài chính chi tiết.

---

## ✨ Tính Năng

### 👤 Quản lý người dùng
- **Authentication**: Đăng ký, đăng nhập, đổi mật khẩu
- **Phân quyền**: Admin/User với middleware bảo vệ
- **Admin Panel**: Quản lý users, thăng/hạ quyền, kích hoạt/vô hiệu hóa tài khoản

### 💰 Quản lý tài chính
- **Dashboard**: Tổng quan thu chi, biểu đồ xu hướng 3 tháng, top categories
- **Transactions**: Thêm/sửa/xóa giao dịch, lọc theo thời gian/danh mục, xuất CSV
- **6 Jars Budget**: Phân bổ thu nhập theo 6 mục đích (NEC 55%, FFA 10%, EDU 10%, LTSS 10%, PLAY 10%, GIVE 5%)
- **Goals**: Thiết lập mục tiêu tiết kiệm, theo dõi tiến độ
- **Reports**: Báo cáo chi tiết theo tháng/năm với biểu đồ

### 🔒 Bảo mật
- **CSRF Protection**: Token-based protection cho mọi POST request
- **Password Hashing**: Bcrypt encryption
- **SQL Injection Prevention**: PDO Prepared Statements
- **Role-Based Access Control**: Admin/User permissions
- **Session Management**: Secure session handling

---

## 🛠️ Công Nghệ Sử Dụng

### Backend
- **PHP 7.4+**: Ngôn ngữ lập trình chính
- **PDO**: Database access với Prepared Statements
- **Custom MVC**: Kiến trúc MVC tự xây dựng
- **Composer**: Dependency management & PSR-4 autoloading

### Frontend
- **HTML5 & CSS3**: Giao diện người dùng
- **Bootstrap 5**: CSS Framework responsive
- **JavaScript (Vanilla)**: Logic frontend
- **Chart.js**: Biểu đồ trực quan
- **AJAX/Fetch API**: Giao tiếp với backend không reload trang

### Database
- **MySQL 5.7+** / **MariaDB 10.4+**: Lưu trữ dữ liệu
- **InnoDB Engine**: Hỗ trợ Foreign Keys và Transactions

---

## 📁 Cấu Trúc Dự Án

```
Quan_Ly_Chi_Tieu/
├── app/
│   ├── controllers/     # Admin, Budgets, Dashboard, Goals, Profile, Reports, Transactions
│   ├── core/           # App, ApiResponse, ConnectDB, Controllers, Views, Request, Response
│   ├── middleware/     # Middleware.php (Auth, Guest, Admin, CSRF)
│   ├── models/         # Category, Goal, Transaction, User
│   ├── services/       # FinancialUtils, Validator
│   └── views/          # View templates + admin/users.php
├── config/
│   ├── constants.php   # App constants
│   └── database.php    # DB configuration
├── database/
│   ├── full_schema.sql        # Complete database schema v4.0
│   └── test_data_oct_nov.sql  # Sample data
├── public/             # Document root
│   ├── index.php       # Entry point
│   ├── budgets/        # Budgets assets
│   ├── dashboard/      # Dashboard assets
│   ├── goals/          # Goals assets
│   ├── profile/        # Profile assets
│   ├── reports/        # Reports assets
│   ├── transactions/   # Transactions assets
│   ├── login_signup/   # Auth assets
│   └── shared/         # Common assets (style.css, app.js, input-masking.js)
├── vendor/             # Composer dependencies
├── .env.example        # Environment template
#### 3. Cấu hình Environment

**Tạo file .env từ template:**
```bash
cp .env.example .env
```

**Chỉnh sửa .env:**
```env
DB_HOST=localhost
DB_NAME=quan_ly_chi_tieu
DB_USER=root
DB_PASS=your_password_here
```

#### 4. Cấu hình Database

**Tạo database:**
```sql
CREATE DATABASE quan_ly_chi_tieu CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

**Import schema:**
```bash
# Import complete schema (tables, views, procedures, triggers, default data)
mysql -u root -p quan_ly_chi_tieu < database/full_schema.sql

# (Optional) Import sample transactions data
mysql -u root -p quan_ly_chi_tieu < database/test_data_oct_nov.sql
```

**Hoặc import từ phpMyAdmin:**
1. Mở phpMyAdmin: `http://localhost/phpmyadmin`
2. Tạo database `quan_ly_chi_tieu`
3. Import file `database/full_schema.sql`
4. (Optional) Import file `database/test_data_oct_nov.sql`

**Cập nhật config (nếu không dùng .env):**

Sửa file `config/database.php`:
```php
<?php
define('DB_HOST', 'localhost');
define('DB_NAME', 'quan_ly_chi_tieu');
define('DB_USER', 'root');
define('DB_PASS', '');  // Mật khẩu MySQL của bạn
define('DB_CHARSET', 'utf8mb4');
```mport complete schema (bao gồm tables, views, procedures, triggers)
mysql -u root -p quan_ly_chi_tieu < database/schema.sql

# (Optional) Import sample data
mysql -u root -p quan_ly_chi_tieu < database/sample_data.sql
```

**Hoặc import từ XAMPP phpMyAdmin:**
1. Mở phpMyAdmin
2. Tạo database `quan_ly_chi_tieu`
#### 5. Cấu hình Virtual Host (Optional - Recommended)
4. (Optional) Import file `database/sample_data.sql`

**Cập nhật config:**

Sửa file `config/database.php`:
```php
<?php
define('DB_HOST', 'localhost');
define('DB_NAME', 'quan_ly_chi_tieu');
define('DB_USER', 'root');
define('DB_PASS', '');  // Mật khẩu MySQL của bạn
define('DB_CHARSET', 'utf8mb4');
```

#### 4. Cấu hình Virtual Host (Optional - Recommended)

**Cho Apache (XAMPP):**

Thêm vào `httpd-vhosts.conf`:
```apache
<VirtualHost *:80>
    ServerName smartspending.local
    DocumentRoot "C:/xampp/htdocs/Quan_Ly_Chi_Tieu/public"
    
    <Directory "C:/xampp/htdocs/Quan_Ly_Chi_Tieu/public">
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

Thêm vào `C:\Windows\System32\drivers\etc\hosts`:
```
127.0.0.1    smartspending.local
```

#### 6. Khởi động Server

**Với XAMPP:**
- Start Apache và MySQL
- Truy cập: `http://smartspending.local` hoặc `http://localhost/Quan_Ly_Chi_Tieu/public`

**Với PHP Built-in Server:**
```bash
cd public
php -S localhost:8000
```
Truy cập: `http://localhost:8000`

#### 7. Đăng ký & Đăng nhập

**User đầu tiên tự động là Admin:**
- Đăng ký tài khoản đầu tiên → Tự động có quyền Admin
- Các user sau → Role mặc định là User

**Admin có thể:**
- Truy cập `/admin/users` để quản lý người dùng
## 📖 Hướng Dẫn Sử Dụng

### Cho User thường:
1. **Dashboard**: Xem tổng quan thu chi, biểu đồ xu hướng 3 tháng
2. **Transactions**: Thêm/sửa/xóa giao dịch, lọc theo tháng/danh mục
3. **Budgets (6 Jars)**: Phân bổ thu nhập, theo dõi chi tiêu theo 6 mục đích
4. **Goals**: Thiết lập mục tiêu tiết kiệm, theo dõi tiến độ
5. **Reports**: Báo cáo và biểu đồ chi tiết theo tháng/năm
6. **Profile**: Cập nhật thông tin cá nhân, đổi mật khẩu

### Cho Admin:
- Tất cả chức năng của User
- **Admin Panel** (`/admin/users`): Quản lý toàn bộ người dùng
  - Xem danh sách users
  - Thăng cấp user lên admin
  - Hạ quyền admin xuống user
  - Kích hoạt/vô hiệu hóa tài khoản

---

## 📚 Documentation

- **[API Documentation](API.md)**: Chi tiết các API endpoints
- **[Changelog](CHANGELOG.md)**: Lịch sử phiên bản
- **[Contributing](CONTRIBUTING.md)**: Hướng dẫn đóng góp

---

## 🛠️ Công Nghệ

**Backend:**
- PHP 7.4+ với Custom MVC Framework
- PSR-4 Autoloading (Composer)
- PDO với Prepared Statements
- Middleware Pattern (Auth, Admin, CSRF)
- Service Layer Architecture

**Frontend:**
- Bootstrap 5 (Responsive UI)
- Vanilla JavaScript (ES6+)
- Chart.js (Data Visualization)
- Fetch API (AJAX)

**Database:**
- MySQL 5.7+ / MariaDB 10.4+
- InnoDB Engine
- Foreign Keys & Transactions
- Views, Stored Procedures, Triggers

---

## 🔐 Bảo Mật

- ✅ Password hashing với bcrypt
- ✅ PDO Prepared Statements (SQL Injection prevention)
- ✅ CSRF Token protection
- ✅ Role-based access control (RBAC)
- ✅ Session security
- ✅ XSS protection (htmlspecialchars)

---

## 🤝 Contributing

Contributions are welcome! Please read [CONTRIBUTING.md](CONTRIBUTING.md) for details.

---

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

---

## 👨‍💻 Author

**HUYHOANG**
- Email: huyhoangpro187@gmail.com
- GitHub: [@HuyHoangI4t](https://github.com/HuyHoangI4t)

---

## 🙏 Acknowledgments

- T. Harv Eker for the 6 Jars Money Management System
- Bootstrap team for the amazing CSS framework
- Chart.js for beautiful data visualization

---

**⭐ If you find this project helpful, please give it a star!**



---

---

**HUYHOANG** - huyhoangpro187@gmail.com - [@HuyHoangI4t](https://github.com/HuyHoangI4t)
