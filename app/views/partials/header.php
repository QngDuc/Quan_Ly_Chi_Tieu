<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $this->escape($title ?? 'Quản Lý Chi Tiêu'); ?></title>
    <link rel="icon" type="image/x-icon" href="<?php echo BASE_URL; ?>/favicon.ico">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?php echo BASE_URL; ?>/shared/style.css" rel="stylesheet">
    
    <script>
        const BASE_URL = '<?php echo BASE_URL; ?>';
    </script>

    <!-- Page-specific CSS -->
    <?php
        $rawUrl = trim($_GET['url'] ?? '', '/');
        if ($rawUrl === '') {
            $requestUri = $_SERVER['REQUEST_URI'] ?? '';
            $basePath = BASE_URL;
            if ($basePath !== '' && strpos($requestUri, $basePath) === 0) {
                $path = substr($requestUri, strlen($basePath));
            } else {
                $path = $requestUri;
            }
            $path = strtok($path, '?');
            $rawUrl = trim($path, '/');
        }

        $segments = $rawUrl !== '' ? explode('/', $rawUrl) : [];
        $page = $segments[0] ?? '';
        if ($page === 'home' || $page === '') { $page = 'dashboard'; }

        // Load page-specific CSS if it exists (from module-specific folders)
        if (in_array($page, ['dashboard', 'transactions', 'budgets', 'goals', 'reports', 'profile'])) {
            $cssFile = BASE_URL . '/user/' . $page . '/' . $page . '.css';
            echo '<link href="' . $cssFile . '" rel="stylesheet">' . "\n";
        } elseif ($page === 'admin') {
            $cssFile = BASE_URL . '/admin/dashboard.css';
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
            <?php
                $isActive = function($name) use ($page) { return $page === $name ? 'active' : ''; };
            ?>
            <a href="<?php echo BASE_URL; ?>/dashboard" class="<?php echo $isActive('dashboard'); ?>">Tổng quan</a>
            <a href="<?php echo BASE_URL; ?>/transactions" class="<?php echo $isActive('transactions'); ?>">Giao dịch</a>
            <a href="<?php echo BASE_URL; ?>/budgets" class="<?php echo $isActive('budgets'); ?>">Ngân sách</a>
            <a href="<?php echo BASE_URL; ?>/goals" class="<?php echo $isActive('goals'); ?>">Mục tiêu</a>
            <a href="<?php echo BASE_URL; ?>/reports" class="<?php echo $isActive('reports'); ?>">Báo cáo</a>
        </nav>

        <div class="user-actions">
            <i class="fas fa-wallet icon-action" title="Ví của tôi"></i>

            <div class="dropdown">
                <a class="icon-action dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="far fa-user-circle" title="Tài khoản"></i>
                </a>
                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                    <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/profile"><i class="fas fa-user me-2"></i>Hồ sơ</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/auth/login/logout"><i class="fas fa-sign-out-alt me-2"></i>Đăng xuất</a></li>
                </ul>
            </div>
        </div>
    </header>

    <div class="container">
        <main class="main-content">