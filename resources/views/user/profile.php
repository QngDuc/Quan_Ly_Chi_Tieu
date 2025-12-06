<?php $this->partial('header'); ?>

<!-- Profile Specific Styles -->
<link rel="stylesheet" href="<?php echo BASE_URL; ?>/user/profile/profile.css">

<div class="container-fluid" style="background: white; min-height: 100vh; padding: 40px 20px;">
    <div class="container" style="max-width: 800px;">
        <!-- Page Title -->
        <h2 class="profile-page-title">Hồ Sơ Của Tôi</h2>

        <!-- Profile Card -->
        <div class="card profile-main-card">
            <!-- Avatar -->
            <div class="profile-avatar-wrapper">
                <div id="avatarDisplay" class="profile-avatar-display" onclick="document.getElementById('avatarInput').click();">
                    <?php if (!empty($user['avatar'])): ?>
                        <img src="<?php echo BASE_URL; ?>/public/uploads/avatars/<?php echo htmlspecialchars($user['avatar'], ENT_QUOTES, 'UTF-8'); ?>" alt="Avatar">
                    <?php else: ?>
                        <i class="fas fa-user"></i>
                    <?php endif; ?>
                </div>
                <button type="button" onclick="document.getElementById('avatarInput').click();" class="profile-avatar-edit-btn">
                    <i class="fas fa-pen"></i>
                </button>
                <input type="file" id="avatarInput" accept="image/*" style="display: none;">
            </div>

            <!-- User Info -->
            <h3 class="profile-user-name">
                <?php echo htmlspecialchars($user['full_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
            </h3>
            <p class="profile-user-email">
                Email: <?php echo htmlspecialchars($user['email'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
            </p>

            <!-- Action Buttons -->
            <div class="profile-action-buttons">
                <button type="button" class="btn profile-btn-edit" data-bs-toggle="modal" data-bs-target="#editProfileModal">
                    <i class="fas fa-edit"></i>Chỉnh Sửa Hồ Sơ
                </button>
                <button type="button" class="btn profile-btn-change-pass" data-bs-toggle="modal" data-bs-target="#changePasswordModal">
                    <i class="fas fa-lock"></i>Đổi Mật Khẩu
                </button>
            </div>
        </div>

        <!-- Settings Card -->
        <div class="card profile-settings-card">
            <h4 class="profile-settings-title">
                <i class="fas fa-cog"></i>Cài Đặt
            </h4>

            <!-- Settings Grid -->
            <div class="profile-settings-grid">
                <!-- Preferred Currency -->
                <div class="profile-setting-item">
                    <div class="profile-setting-header">
                        <i class="fas fa-dollar-sign"></i>
                        <span class="profile-setting-label">Đơn Vị Tiền Tệ</span>
                    </div>
                    <p class="profile-setting-value">VND (₫)</p>
                </div>

                <!-- Language -->
                <div class="profile-setting-item">
                    <div class="profile-setting-header">
                        <i class="fas fa-language"></i>
                        <span class="profile-setting-label">Ngôn Ngữ</span>
                    </div>
                    <p class="profile-setting-value">Tiếng Việt</p>
                </div>

                <!-- Monthly Start Date -->
                <div class="profile-setting-item">
                    <div class="profile-setting-header">
                        <i class="fas fa-calendar-alt"></i>
                        <span class="profile-setting-label">Ngày Bắt Đầu Tháng</span>
                    </div>
                    <p class="profile-setting-value">Ngày 1 mỗi tháng</p>
                </div>
            </div>

            <!-- Notification Preferences -->
            <div class="profile-notification-section">
                <h5 class="profile-notification-title">Tùy Chọn Thông Báo</h5>
                
                <div class="profile-notification-list">
                    <div class="profile-notification-item">
                        <div class="notification-info">
                            <i class="fas fa-chart-line"></i>
                            <span>Cảnh Báo Giới Hạn Ngân Sách</span>
                        </div>
                        <label class="toggle-switch">
                            <input type="checkbox" checked>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                    
                    <div class="profile-notification-item">
                        <div class="notification-info">
                            <i class="fas fa-bullseye"></i>
                            <span>Nhắc Nhở Mục Tiêu</span>
                        </div>
                        <label class="toggle-switch">
                            <input type="checkbox" checked>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                    
                    <div class="profile-notification-item">
                        <div class="notification-info">
                            <i class="fas fa-envelope"></i>
                            <span>Email Tóm Tắt Hàng Tuần</span>
                        </div>
                        <label class="toggle-switch">
                            <input type="checkbox" checked>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Data Management -->
            <div class="profile-data-section">
                <h5 class="profile-data-title">Quản Lý Dữ Liệu</h5>
                
                <div class="profile-data-buttons">
                    <button type="button" class="btn profile-btn-export" onclick="exportData()">
                        <i class="fas fa-download"></i>Xuất Dữ Liệu
                    </button>
                    <button type="button" class="btn profile-btn-clear" onclick="clearAllData()">
                        <i class="fas fa-trash"></i>Xóa Tất Cả Dữ Liệu
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Edit Profile Modal -->
<div class="modal fade" id="editProfileModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content profile-modal-content">
            <div class="modal-header profile-modal-header">
                <h5 class="modal-title profile-modal-title">Chỉnh Sửa Hồ Sơ</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="editProfileForm">
                <div class="modal-body profile-modal-body">
                    <div class="mb-3">
                        <label class="profile-modal-label">Tên</label>
                        <input type="text" name="name" class="form-control profile-modal-input" required value="<?php echo htmlspecialchars($user['full_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label class="profile-modal-label">Email</label>
                        <input type="email" name="email" class="form-control profile-modal-input" required value="<?php echo htmlspecialchars($user['email'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                    </div>
                </div>
                <div class="modal-footer profile-modal-footer">
                    <button type="button" class="btn profile-modal-btn-cancel" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn profile-modal-btn-submit">Lưu Thay Đổi</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Change Password Modal -->
<div class="modal fade" id="changePasswordModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content profile-modal-content">
            <div class="modal-header profile-modal-header">
                <h5 class="modal-title profile-modal-title">Đổi Mật Khẩu</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="changePasswordForm">
                <div class="modal-body profile-modal-body">
                    <div class="mb-3">
                        <label class="profile-modal-label">Mật Khẩu Hiện Tại</label>
                        <input type="password" name="current_password" class="form-control profile-modal-input" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="profile-modal-label">Mật Khẩu Mới</label>
                        <input type="password" name="new_password" class="form-control profile-modal-input" required minlength="6">
                    </div>
                    
                    <div class="mb-3">
                        <label class="profile-modal-label">Xác Nhận Mật Khẩu Mới</label>
                        <input type="password" name="confirm_password" class="form-control profile-modal-input" required minlength="6">
                    </div>
                </div>
                <div class="modal-footer profile-modal-footer">
                    <button type="button" class="btn profile-modal-btn-cancel" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn profile-modal-btn-submit">Đổi Mật Khẩu</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="<?php echo BASE_URL; ?>/user/profile/profile.js"></script>

<?php $this->partial('footer'); ?>
