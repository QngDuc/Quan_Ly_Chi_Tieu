# Quan Ly Chi Tieu (PHP thuần + XAMPP)

## Điểm vào và định tuyến
- Điểm vào duy nhất: `public/index.php` → `http://localhost/Quan_Ly_Chi_Tieu/public/`
- Router mặc định vào `App\Controllers\Auth\Login@index` (trang đăng nhập/đăng ký)
- Nhánh route đặc biệt:
	- `/auth/...` -> `App\Controllers\Auth\*`
	- `/admin/...` -> `App\Controllers\Admin\*`
	- Mặc định khác -> `App\Controllers\User\*`

## Auth
- View: `app/views/auth/login.php`
- API:
	- `POST /auth/login/api_login`
	- `POST /auth/login/api_register`
	- `GET  /auth/login/logout`

File cũ `app/views/login_signup.php` đã bỏ, để lại stub 301 redirect về `/auth/login`.

## Cấu trúc thư mục chính
- `app/core`: App, Request, Response, Views, Autoloader
- `app/controllers`: `Auth/`, `Admin/`, `User/`
- `app/models`: các model nghiệp vụ
- `app/services`: tiện ích chung
- `app/middleware`: kiểm tra phiên, CSRF, v.v.
- `public/`: assets + front controller, `.htaccess` bật cache tĩnh

## Chạy trên XAMPP (PHP thuần)
1. Đặt repo vào `htdocs`, đường dẫn: `C:\xampp\htdocs\Quan_Ly_Chi_Tieu`
2. Truy cập: `http://localhost/Quan_Ly_Chi_Tieu/public/`
3. Composer là tùy chọn: nếu không có `vendor/autoload.php`, app dùng autoloader nội bộ `App\Core\Autoloader`.

## Bảo mật và hiệu năng
- Session cookies: `HttpOnly`, `SameSite=Lax`, `Secure` khi HTTPS
- Tĩnh: `.htaccess` cấu hình cache CSS/JS/ảnh/font để tăng tốc tải

## Dev tooling (tuỳ chọn)
- Composer scripts: `lint`, `fix`, `stan`, `test`
- Cài dev deps: `composer update`; Chạy test: `php vendor\phpunit\phpunit\phpunit -c phpunit.xml`
