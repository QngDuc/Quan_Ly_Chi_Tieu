<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title ?? 'Quản lý người dùng - Admin'; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?php echo BASE_URL; ?>/shared/style.css" rel="stylesheet">
    <style>
        .admin-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
        }
        .badge-admin { background-color: #dc3545; }
        .badge-user { background-color: #6c757d; }
        .badge-active { background-color: #28a745; }
        .badge-inactive { background-color: #6c757d; }
    </style>
</head>
<body>
    <div class="admin-header">
        <div class="container">
            <h1><i class="fas fa-users-cog"></i> Quản lý người dùng</h1>
            <p class="mb-0">Quản lý tài khoản và phân quyền người dùng</p>
        </div>
    </div>

    <div class="container">
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Tên đăng nhập</th>
                                <th>Email</th>
                                <th>Họ và tên</th>
                                <th>Vai trò</th>
                                <th>Trạng thái</th>
                                <th>Ngày tạo</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($users)): ?>
                                <?php foreach ($users as $user): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($user['id']); ?></td>
                                        <td><?php echo htmlspecialchars($user['username']); ?></td>
                                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                                        <td><?php echo htmlspecialchars($user['full_name'] ?? '-'); ?></td>
                                        <td>
                                            <span class="badge <?php echo $user['role'] === 'admin' ? 'badge-admin' : 'badge-user'; ?>">
                                                <?php echo $user['role'] === 'admin' ? 'Admin' : 'User'; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge <?php echo $user['is_active'] ? 'badge-active' : 'badge-inactive'; ?>">
                                                <?php echo $user['is_active'] ? 'Hoạt động' : 'Vô hiệu hóa'; ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('d/m/Y H:i', strtotime($user['created_at'])); ?></td>
                                        <td>
                                            <?php if ($user['id'] != 1 && $user['id'] != $_SESSION['user_id']): ?>
                                                <button class="btn btn-sm btn-<?php echo $user['role'] === 'admin' ? 'warning' : 'success'; ?>" 
                                                        onclick="toggleRole(<?php echo $user['id']; ?>, '<?php echo $user['role']; ?>')">
                                                    <?php echo $user['role'] === 'admin' ? 'Hạ xuống User' : 'Thăng lên Admin'; ?>
                                                </button>
                                                <button class="btn btn-sm btn-<?php echo $user['is_active'] ? 'danger' : 'primary'; ?>" 
                                                        onclick="toggleStatus(<?php echo $user['id']; ?>, <?php echo $user['is_active'] ? 0 : 1; ?>)">
                                                    <?php echo $user['is_active'] ? 'Vô hiệu hóa' : 'Kích hoạt'; ?>
                                                </button>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="text-center">Không có người dùng nào</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="mt-3">
            <a href="<?php echo BASE_URL; ?>/dashboard" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Quay lại Dashboard
            </a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?php echo BASE_URL; ?>/shared/app.js"></script>
    <script>
        async function toggleRole(userId, currentRole) {
            const newRole = currentRole === 'admin' ? 'user' : 'admin';
            const confirmMsg = currentRole === 'admin' 
                ? 'Bạn có chắc muốn hạ quyền user này xuống User?'
                : 'Bạn có chắc muốn thăng quyền user này lên Admin?';

            if (!confirm(confirmMsg)) return;

            try {
                const response = await fetch('<?php echo BASE_URL; ?>/admin/api_update_user_role', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ user_id: userId, role: newRole })
                });

                const data = await response.json();
                alert(data.message);
                
                if (data.success) {
                    location.reload();
                }
            } catch (error) {
                alert('Có lỗi xảy ra: ' + error.message);
            }
        }

        async function toggleStatus(userId, newStatus) {
            const confirmMsg = newStatus === 1 
                ? 'Bạn có chắc muốn kích hoạt tài khoản này?'
                : 'Bạn có chắc muốn vô hiệu hóa tài khoản này?';

            if (!confirm(confirmMsg)) return;

            try {
                const response = await fetch('<?php echo BASE_URL; ?>/admin/api_toggle_user_status', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ user_id: userId, is_active: newStatus })
                });

                const data = await response.json();
                alert(data.message);
                
                if (data.success) {
                    location.reload();
                }
            } catch (error) {
                alert('Có lỗi xảy ra: ' + error.message);
            }
        }
    </script>
</body>
</html>
