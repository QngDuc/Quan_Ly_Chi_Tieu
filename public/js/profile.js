// === PROFILE PAGE JS ===

document.addEventListener('DOMContentLoaded', function() {
    // Handle profile update form
    const profileForm = document.getElementById('profileForm');
    if (profileForm) {
        profileForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            fetch(this.action, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    SmartSpending.showToast('Cập nhật thông tin thành công!', 'success');
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

    // Handle password change form
    const passwordForm = document.getElementById('passwordForm');
    if (passwordForm) {
        passwordForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const newPassword = document.getElementById('newPassword').value;
            const confirmPassword = document.getElementById('confirmPassword').value;
            
            if (newPassword !== confirmPassword) {
                SmartSpending.showToast('Mật khẩu xác nhận không khớp', 'error');
                return;
            }
            
            const formData = new FormData(this);
            
            fetch(this.action, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    SmartSpending.showToast('Đổi mật khẩu thành công!', 'success');
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

    // Handle avatar upload
    const avatarInput = document.getElementById('avatarInput');
    if (avatarInput) {
        avatarInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const formData = new FormData();
                formData.append('avatar', file);
                
                fetch(`${BASE_URL}/profile/upload-avatar`, {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        SmartSpending.showToast('Cập nhật ảnh đại diện thành công!', 'success');
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
            }
        });
    }
});
