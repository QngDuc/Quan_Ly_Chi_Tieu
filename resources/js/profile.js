// === PROFILE PAGE JS ===

document.addEventListener('DOMContentLoaded', function() {
    const BASE_URL = window.location.origin + '/Quan_Ly_Chi_Tieu';
    // Handle avatar upload
    const avatarInput = document.getElementById('avatarInput');
    if (avatarInput) {
        avatarInput.addEventListener('change', function(e) {
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
                reader.onload = function(e) {
                    const avatarDisplay = document.getElementById('avatarDisplay');
                    avatarDisplay.innerHTML = `<img src="${e.target.result}" alt="Avatar" style="width: 100%; height: 100%; object-fit: cover;">`;
                };
                reader.readAsDataURL(file);
                
                // Save to localStorage for persistence (you can also upload to server)
                const reader2 = new FileReader();
                reader2.onload = function(e) {
                    localStorage.setItem('userAvatar', e.target.result);
                    SmartSpending.showToast('Cập nhật ảnh đại diện thành công!', 'success');
                };
                reader2.readAsDataURL(file);
            }
        });
        
        // Load saved avatar from localStorage
        const savedAvatar = localStorage.getItem('userAvatar');
        if (savedAvatar) {
            const avatarDisplay = document.getElementById('avatarDisplay');
            avatarDisplay.innerHTML = `<img src="${savedAvatar}" alt="Avatar" style="width: 100%; height: 100%; object-fit: cover;">`;
        }
    }

    // Handle edit profile form
    const editProfileForm = document.getElementById('editProfileForm');
    if (editProfileForm) {
        editProfileForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = {
                name: this.querySelector('[name="name"]').value,
                email: this.querySelector('[name="email"]').value
            };
            
            fetch(`${BASE_URL}/profile/api_update`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
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
        changePasswordForm.addEventListener('submit', function(e) {
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
                    'Content-Type': 'application/json'
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
        function() {
            fetch(`${BASE_URL}/profile/api_clear_data`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
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
