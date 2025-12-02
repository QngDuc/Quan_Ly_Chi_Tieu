# Cấu Trúc Dự Án Mới - Phân Chia User & Admin

## Mục tiêu
Tổ chức lại project thành 2 modules rõ ràng:
1. **User Module** - Chức năng cho người dùng thường
2. **Admin Module** - Chức năng quản trị

## Cấu trúc mới

```
app/
├── controllers/
│   ├── User/                    # Controllers cho User
│   │   ├── Budgets.php
│   │   ├── Dashboard.php
│   │   ├── Goals.php
│   │   ├── Profile.php
│   │   ├── Reports.php
│   │   └── Transactions.php
│   ├── Admin/                   # Controllers cho Admin
│   │   ├── Dashboard.php        # Admin dashboard
│   │   ├── Users.php            # Quản lý users (rename từ Admin.php)
│   │   ├── Categories.php       # Quản lý categories
│   │   └── Settings.php         # Cài đặt hệ thống
│   └── Login_signup.php         # Shared authentication
│
├── views/
│   ├── user/                    # Views cho User
│   │   ├── budgets.php
│   │   ├── dashboard.php
│   │   ├── goals.php
│   │   ├── profile.php
│   │   ├── reports.php
│   │   └── transactions.php
│   ├── admin/                   # Views cho Admin
│   │   ├── dashboard.php        # Admin dashboard
│   │   ├── users.php            # Đã có
│   │   ├── categories.php       # Quản lý danh mục
│   │   └── settings.php         # Cài đặt
│   ├── partials/                # Shared components
│   │   ├── header.php
│   │   ├── footer.php
│   │   ├── user_sidebar.php     # Sidebar cho user
│   │   └── admin_sidebar.php    # Sidebar cho admin
│   └── login_signup.php         # Shared login/signup
│
├── models/                      # Không thay đổi
├── core/                        # Không thay đổi
├── middleware/                  # Không thay đổi
└── services/                    # Không thay đổi

public/
├── user/                        # Assets cho User
│   ├── budgets/
│   ├── dashboard/
│   ├── goals/
│   ├── profile/
│   ├── reports/
│   └── transactions/
├── admin/                       # Assets cho Admin
│   ├── dashboard/
│   ├── users/
│   ├── categories/
│   └── settings/
├── shared/                      # Shared assets
│   ├── style.css
│   ├── app.js
│   └── input-masking.js
└── login_signup/                # Auth assets
```

## Thay đổi cần thực hiện

### 1. Namespaces
- User controllers: `App\Controllers\User`
- Admin controllers: `App\Controllers\Admin`

### 2. Routing
- User routes: `/dashboard`, `/budgets`, `/goals`, etc.
- Admin routes: `/admin/dashboard`, `/admin/users`, `/admin/categories`, etc.

### 3. View paths
- User: `user/dashboard`, `user/budgets`, etc.
- Admin: `admin/dashboard`, `admin/users`, etc.

### 4. Middleware
- User pages: AuthMiddleware
- Admin pages: AdminMiddleware

## Lợi ích
- ✅ Tách biệt rõ ràng User vs Admin
- ✅ Dễ mở rộng và bảo trì
- ✅ Tránh conflict giữa các modules
- ✅ Security tốt hơn
- ✅ Code organization chuyên nghiệp
