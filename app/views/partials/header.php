<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $this->escape($title ?? 'Quản Lý Chi Tiêu'); ?></title>
    <link rel="icon" type="image/x-icon" href="<?php echo BASE_URL; ?>/public/favicon.ico">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?php echo BASE_URL; ?>/public/css/style.css" rel="stylesheet">
    
    <script>
        const BASE_URL = '<?php echo BASE_URL; ?>';
    </script>

    <!-- Page-specific CSS -->
    <?php
        $url = trim($_GET['url'] ?? '', '/');
        $page = explode('/', $url)[0];
        
        // Treat home and dashboard as the same page (dashboard)
        if ($page === 'home' || $page === '') {
            $page = 'dashboard';
        }
        
        // Load page-specific CSS if it exists
        $cssFile = BASE_URL . '/public/css/' . $page . '.css';
        if ($page && in_array($page, ['dashboard', 'transactions', 'budgets', 'goals', 'reports', 'profile'])) {
            echo '<link href="' . $cssFile . '" rel="stylesheet">' . "\n";
        }
    ?>
</head>
<body>
    <header class="navbar">
        <div class="brand">
            <h2><i class="fas fa-wallet me-2"></i>Smart<span>Spending</span></h2>
        </div>
        
        <nav class="nav-links">
            <a href="<?php echo BASE_URL; ?>/dashboard" class="<?php echo (trim($_GET['url'] ?? '', '/') === '' || strpos($_GET['url'] ?? '', 'dashboard') === 0) ? 'active' : ''; ?>">Tổng quan</a>
            <a href="<?php echo BASE_URL; ?>/transactions" class="<?php echo strpos($_GET['url'] ?? '', 'transactions') === 0 ? 'active' : ''; ?>">Giao dịch</a>
            <a href="<?php echo BASE_URL; ?>/budgets" class="<?php echo strpos($_GET['url'] ?? '', 'budgets') === 0 ? 'active' : ''; ?>">Ngân sách</a>
            <a href="<?php echo BASE_URL; ?>/goals" class="<?php echo strpos($_GET['url'] ?? '', 'goals') === 0 ? 'active' : ''; ?>">Mục tiêu</a>
            <a href="<?php echo BASE_URL; ?>/reports" class="<?php echo strpos($_GET['url'] ?? '', 'reports') === 0 ? 'active' : ''; ?>">Báo cáo</a>
        </nav>

        <div class="user-actions">
            <i class="fas fa-wallet icon-action" title="Ví của tôi"></i> 
            
            <!-- User Dropdown from previous header -->
            <div class="dropdown">
                <a class="icon-action dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="far fa-user-circle" title="Tài khoản"></i>
                </a>
                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                    <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/profile"><i class="fas fa-user me-2"></i>Hồ sơ</a></li>
                    
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/login_signup/logout"><i class="fas fa-sign-out-alt me-2"></i>Đăng xuất</a></li>
                </ul>
            </div>
        </div>
    </header>

    <div class="container">
        <main class="main-content">