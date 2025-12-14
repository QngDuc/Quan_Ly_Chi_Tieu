// === PROFILE PAGE JS ===

// Helper to read CSRF token from meta or hidden input
function getCsrfToken() {
    return document.querySelector('input[name="csrf_token"]')?.value || document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
}

document.addEventListener('DOMContentLoaded', function () {
    const BASE_URL = (typeof window !== 'undefined' && typeof window.BASE_URL !== 'undefined' && window.BASE_URL) ? window.BASE_URL : (window.location.origin + '/Quan_Ly_Chi_Tieu');
    // Handle avatar upload
    const avatarInput = document.getElementById('avatarInput');
    if (avatarInput) {
        avatarInput.addEventListener('change', function (e) {
            const file = e.target.files[0];
            if (file) {
                // Validate file type
                if (!file.type.startsWith('image/')) {
                    SmartSpending.showToast('Vui lòng chọn file ảnh', 'error');
                    return;
                }

                // Validate file size (max 2MB)
                if (file.size > 2 * 1024 * 1024) {
                    SmartSpending.showToast('Kích thước ảnh phải nhỏ hơn 2MB', 'error');
                    return;
                }

                // Preview image
                const reader = new FileReader();
                reader.onload = function (e) {
                    const avatarDisplay = document.getElementById('avatarDisplay');
                    avatarDisplay.innerHTML = `<img src="${e.target.result}" alt="Avatar" style="width: 100%; height: 100%; object-fit: cover;">`;
                };
                reader.readAsDataURL(file);

                // Save to localStorage for persistence (you can also upload to server)
                const reader2 = new FileReader();
                reader2.onload = function (e) {
                    localStorage.setItem('userAvatar', e.target.result);
                    SmartSpending.showToast('Cập nhật ảnh đại diện thành công!', 'success');
                };
                reader2.readAsDataURL(file);
            }
        });

        // Load saved avatar from localStorage — only apply if server didn't already render an avatar
        const savedAvatar = localStorage.getItem('userAvatar');
        if (savedAvatar) {
            const avatarDisplay = document.getElementById('avatarDisplay');
            // If server rendered an <img> (i.e. avatar comes from DB/Google), don't override it
            const hasServerImg = avatarDisplay && avatarDisplay.querySelector && avatarDisplay.querySelector('img');
            if (avatarDisplay && !hasServerImg) {
                avatarDisplay.innerHTML = `<img src="${savedAvatar}" alt="Avatar" style="width: 100%; height: 100%; object-fit: cover;">`;
            }
        }
    // --- XỬ LÝ NÚT GẠT THÔNG BÁO (NOTIFICATION TOGGLES) ---
    const notificationToggles = document.querySelectorAll('.notification-toggle');

    if (notificationToggles.length === 0) {
        console.debug('profile.js: No notification toggles found');
    }

    notificationToggles.forEach(toggle => {
        console.debug('profile.js: attaching listener to', toggle.dataset.key);
            toggle.addEventListener('change', function () {
                // 1. Lấy thông tin từ nút vừa gạt
                const key = this.dataset.key; // Tên cột (vd: notify_budget_limit)
                const value = this.checked;   // Trạng thái mới (true/false)

                // 2. Lấy CSRF Token để bảo mật (quan trọng!)
                // Thử lấy từ thẻ input hidden trước, nếu không có thì lấy từ thẻ meta
                const csrfToken = document.querySelector('input[name="csrf_token"]')?.value ||
                    document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                console.debug('profile.js: csrf token', csrfToken);

                // 3. Gửi Request xuống Server (API Controller Bước 3)
                console.debug('profile.js: sending preference to', `${BASE_URL}/profile/api_update_preference`, { key, value });
                fetch(`${BASE_URL}/profile/api_update_preference`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': csrfToken
                    },
                    body: JSON.stringify({
                        key: key,
                        value: value
                    })
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Thành công: Hiện thông báo nhỏ (Toast) cho ngầu
                            if (typeof SmartSpending !== 'undefined') {
                                SmartSpending.showToast('Đã lưu cài đặt', 'success');
                            }
                            console.log(`Saved: ${key} = ${value}`);
                        } else {
                            // Thất bại: Bật/tắt lại cái nút về như cũ (Revert UI)
                            this.checked = !value;
                            if (typeof SmartSpending !== 'undefined') {
                                SmartSpending.showToast(data.message || 'Lỗi khi lưu', 'error');
                            }
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        // Lỗi mạng: Revert UI luôn
                        this.checked = !value;
                        if (typeof SmartSpending !== 'undefined') {
                            SmartSpending.showToast('Lỗi kết nối server', 'error');
                        }
                    });
            });
        });
    }

    // Handle edit profile form
    const editProfileForm = document.getElementById('editProfileForm');
    if (editProfileForm) {
        editProfileForm.addEventListener('submit', function (e) {
            e.preventDefault();

            const formData = {
                name: this.querySelector('[name="name"]').value,
                email: this.querySelector('[name="email"]').value
            };

            fetch(`${BASE_URL}/profile/api_update`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': getCsrfToken()
                },
                body: JSON.stringify(formData)
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        SmartSpending.showToast('Cập nhật hồ sơ thành công!', 'success');
                        setTimeout(() => {
                            window.location.reload();
                        }, 1000);
                    } else {
                        SmartSpending.showToast(data.message || 'Có lỗi xảy ra', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    SmartSpending.showToast('Có lỗi xảy ra', 'error');
                });
        });
    }

    // Handle change password form
    const changePasswordForm = document.getElementById('changePasswordForm');
    if (changePasswordForm) {
        changePasswordForm.addEventListener('submit', function (e) {
            e.preventDefault();

            const newPassword = this.querySelector('[name="new_password"]').value;
            const confirmPassword = this.querySelector('[name="confirm_password"]').value;

            if (newPassword !== confirmPassword) {
                SmartSpending.showToast('Mật khẩu không khớp', 'error');
                return;
            }

            const formData = {
                current_password: this.querySelector('[name="current_password"]').value,
                new_password: newPassword
            };

            fetch(`${BASE_URL}/profile/api_change_password`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': getCsrfToken()
                },
                body: JSON.stringify(formData)
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        SmartSpending.showToast('Đổi mật khẩu thành công!', 'success');
                        const modal = bootstrap.Modal.getInstance(document.getElementById('changePasswordModal'));
                        modal.hide();
                        this.reset();
                    } else {
                        SmartSpending.showToast(data.message || 'Có lỗi xảy ra', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    SmartSpending.showToast('Có lỗi xảy ra', 'error');
                });
        });
    }
});

// Export data function
function exportData() {
    const BASE_URL = window.location.origin + '/Quan_Ly_Chi_Tieu';

    // Show loading toast
    SmartSpending.showToast('Đang xuất dữ liệu...', 'success');

    // Create a hidden link and trigger download
    const link = document.createElement('a');
    link.href = `${BASE_URL}/profile/export_data`;
    link.download = `SmartSpending_Data_${new Date().toISOString().split('T')[0]}.csv`;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);

    // Show success message after a short delay
    setTimeout(() => {
        SmartSpending.showToast('Đã xuất dữ liệu thành công!', 'success');
    }, 500);
}

// Clear all data function
function clearAllData() {
    const BASE_URL = window.location.origin + '/Quan_Ly_Chi_Tieu';
    SmartSpending.showConfirm(
        'Xóa Tất Cả Dữ Liệu?',
        'Thao tác này sẽ xóa vĩnh viễn tất cả giao dịch của bạn. Hành động này không thể hoàn tác.',
        function () {
            fetch(`${BASE_URL}/profile/api_clear_data`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': getCsrfToken()
                }
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        SmartSpending.showToast('Đã xóa tất cả dữ liệu thành công!', 'success');
                        setTimeout(() => {
                            window.location.href = `${BASE_URL}/dashboard`;
                        }, 1500);
                    } else {
                        SmartSpending.showToast(data.message || 'Có lỗi xảy ra', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    SmartSpending.showToast('Có lỗi xảy ra', 'error');
                });
        }
    );
}
