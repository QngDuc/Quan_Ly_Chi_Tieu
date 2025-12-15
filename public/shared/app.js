// === SMARTSPENDING - MAIN APPLICATION JS ===

// Utility Functions
var SmartSpending = window.SmartSpending || {};

// Populate core helpers only if not already present (idempotent)
SmartSpending = Object.assign({
    // Format currency in Vietnamese
    formatCurrency: (amount) => {
        return new Intl.NumberFormat('vi-VN', {
            style: 'currency',
            currency: 'VND'
        }).format(amount);
    },

    // Format date
    formatDate: (date) => {
        return new Intl.DateTimeFormat('vi-VN').format(new Date(date));
    },

    // Show toast notification (Modern style with close button)
    showToast: (message, type = 'success') => {
        // Create container if not exists
        let container = document.getElementById('toastContainer');
        if (!container) {
            container = document.createElement('div');
            container.id = 'toastContainer';
            container.className = 'toast-container';
            document.body.appendChild(container);
        }

        const icons = {
            success: 'fa-check-circle',
            error: 'fa-times-circle',
            warning: 'fa-exclamation-triangle',
            info: 'fa-info-circle'
        };

        const titles = {
            success: 'Thành công',
            error: 'Lỗi',
            warning: 'Cảnh báo',
            info: 'Thông tin'
        };

        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        toast.innerHTML = `
            <div class="toast-icon-wrapper">
                <i class="fas ${icons[type]}"></i>
            </div>
            <div class="toast-content">
                <div class="toast-title">${titles[type]}</div>
                <div class="toast-message">${message}</div>
            </div>
            <button class="toast-close" aria-label="Close">
                <i class="fas fa-times"></i>
            </button>
        `;

        container.appendChild(toast);

        // Add show animation
        setTimeout(() => toast.classList.add('toast-show'), 10);

        // Close button handler
        const closeBtn = toast.querySelector('.toast-close');
        const removeToast = () => {
            toast.classList.remove('toast-show');
            toast.classList.add('toast-hide');
            setTimeout(() => toast.remove(), 300);
        };

        closeBtn.addEventListener('click', removeToast);

        // Auto remove after 4 seconds
        setTimeout(removeToast, 4000);
    },

    // Custom confirm dialog (FinTrack style)
    // Usage: SmartSpending.showConfirm(title, message, onConfirm, { cancelText, confirmText })
    showConfirm: (title, message, onConfirm, options = {}) => {
        const cancelText = options.cancelText || 'Hủy';
        const confirmText = options.confirmText || 'Xóa';

        const overlay = document.createElement('div');
        overlay.className = 'confirm-overlay';
        overlay.innerHTML = `
            <div class="confirm-dialog">
                <div class="confirm-title">${title}</div>
                <div class="confirm-message">${message}</div>
                <div class="confirm-actions">
                    <button class="confirm-btn confirm-btn-cancel">${cancelText}</button>
                    <button class="confirm-btn confirm-btn-delete">${confirmText}</button>
                </div>
            </div>
        `;

        document.body.appendChild(overlay);

        const cancelBtn = overlay.querySelector('.confirm-btn-cancel');
        const deleteBtn = overlay.querySelector('.confirm-btn-delete');

        const closeDialog = () => {
            overlay.style.animation = 'fadeOut 0.2s ease-out';
            setTimeout(() => overlay.remove(), 200);
        };

        cancelBtn.addEventListener('click', closeDialog);
        deleteBtn.addEventListener('click', () => {
            closeDialog();
            if (typeof onConfirm === 'function') onConfirm();
        });

        // Close on backdrop click
        overlay.addEventListener('click', (e) => {
            if (e.target === overlay) closeDialog();
        });
    },

    // Confirm dialog
    confirm: (message, callback) => {
        if (confirm(message)) {
            callback();
        }
    },

    // Loading spinner management
    loaderElement: null,

    showLoader: () => {
        if (!SmartSpending.loaderElement) {
            SmartSpending.loaderElement = document.createElement('div');
            SmartSpending.loaderElement.className = 'global-loader';
            SmartSpending.loaderElement.innerHTML = `
                <div class="loader-spinner">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            `;
            document.body.appendChild(SmartSpending.loaderElement);
        }

        SmartSpending.loaderElement.style.display = 'flex';
        setTimeout(() => {
            SmartSpending.loaderElement.classList.add('visible');
        }, 10);
    },

    hideLoader: () => {
        if (SmartSpending.loaderElement) {
            SmartSpending.loaderElement.classList.remove('visible');
            setTimeout(() => {
                if (SmartSpending.loaderElement) {
                    SmartSpending.loaderElement.style.display = 'none';
                }
            }, 300);
        }
    }
});

// Global event listeners
document.addEventListener('DOMContentLoaded', function () {
    // Initialize tooltips if Bootstrap is loaded
    if (typeof bootstrap !== 'undefined') {
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    }

    // Add smooth scroll behavior
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            const href = this.getAttribute('href');
            // Skip if href is just '#' or empty
            if (!href || href === '#') {
                return;
            }
            e.preventDefault();
            const target = document.querySelector(href);
            if (target) {
                target.scrollIntoView({ behavior: 'smooth' });
            }
        });
    });

    // Set CSS variable for header height so layout can compute main-content = 100vh - header
    function setHeaderHeightVar() {
        try {
            var header = document.querySelector('header.navbar');
            var h = header ? header.offsetHeight : 72;
            document.documentElement.style.setProperty('--header-height', h + 'px');
        } catch (e) { /* no-op */ }
    }

    // initial set and update on resize
    setHeaderHeightVar();
    window.addEventListener('resize', function () { setHeaderHeightVar(); });
});

// Export for use in other files
window.SmartSpending = SmartSpending;

// Modal-based notification helper (falls back to toast if Bootstrap modal unavailable)
SmartSpending.showModal = function(message, title = 'Thông báo', type = 'info', autoHide = true, timeout = 3500) {
    try {
        // Prefer the global modal in footer if available
        var modalEl = document.getElementById('globalNotificationModal');
        var titleEl = document.getElementById('globalNotificationModalTitle');
        var bodyEl = document.getElementById('globalNotificationModalBody');
        if (titleEl) titleEl.textContent = title || 'Thông báo';
        if (bodyEl) bodyEl.innerHTML = message;

        if (modalEl && typeof bootstrap !== 'undefined') {
            var inst = bootstrap.Modal.getOrCreateInstance(modalEl);
            inst.show();
            if (autoHide && timeout > 0) {
                setTimeout(function() {
                    try { inst.hide(); } catch (e) {}
                }, timeout);
            }
            return;
        }
    } catch (e) {
        // ignore and fallback
        console.warn('showModal error', e);
    }

    // fallback to toast
    SmartSpending.showToast(message, type === 'error' ? 'error' : 'info');
};

// Convenience wrappers
SmartSpending.showError = function(msg, title) { SmartSpending.showModal(msg, title || 'Lỗi', 'error', false); };
SmartSpending.showInfo = function(msg, title) { SmartSpending.showModal(msg, title || 'Thông tin', 'info', true, 3000); };

// Override default alert to use modal notifications
window.alert = function(msg) { try { SmartSpending.showModal(String(msg), 'Thông báo', 'info', false); } catch (e) { console.log('Alert:', msg); } };

// Avatar upload helper: detects profile avatar form and handles AJAX upload
document.addEventListener('DOMContentLoaded', function () {
    try {
        // Find form by action contains 'profile/api_upload_avatar'
        var forms = document.querySelectorAll('form');
        var avatarForm = null;
        for (var i = 0; i < forms.length; i++) {
            var f = forms[i];
            var action = f.getAttribute('action') || '';
            if (action.indexOf('/profile/api_upload_avatar') !== -1 || action.indexOf('profile/api_upload_avatar') !== -1) {
                avatarForm = f;
                break;
            }
        }

        // Fallback: element with data-avatar-upload
        if (!avatarForm) avatarForm = document.querySelector('[data-avatar-upload]');

        if (!avatarForm) return; // no profile upload form on page

        var fileInput = avatarForm.querySelector('input[type=file][name=avatar]');
        if (!fileInput) return;

        avatarForm.addEventListener('submit', function (e) {
            e.preventDefault();

            var file = fileInput.files[0];
            if (!file) {
                SmartSpending.showToast('Vui lòng chọn file ảnh.', 'error');
                return;
            }

            var fd = new FormData();
            fd.append('avatar', file);

            // Try to attach CSRF token if present as meta tag or hidden input
            var token = document.querySelector('meta[name=csrf-token]');
            if (token) fd.append('csrf_token', token.getAttribute('content'));
            var hiddenToken = avatarForm.querySelector('input[name=csrf_token]');
            if (hiddenToken) fd.append('csrf_token', hiddenToken.value);

            SmartSpending.showLoader();

            fetch(avatarForm.action || (location.pathname + '/profile/api_upload_avatar'), {
                method: 'POST',
                body: fd,
                credentials: 'same-origin'
            }).then(function (res) {
                SmartSpending.hideLoader();
                return res.json();
            }).then(function (json) {
                if (json && json.status === 'success') {
                    var newUrl = json.data && json.data.avatar ? json.data.avatar : null;
                    if (newUrl) {
                        // Update avatar images on page (img.avatar)
                        document.querySelectorAll('img.avatar').forEach(function (img) {
                            img.src = newUrl + '?t=' + Date.now();
                        });
                    }
                    SmartSpending.showToast('Cập nhật avatar thành công', 'success');
                } else {
                    var msg = (json && json.message) ? json.message : 'Không thể tải lên avatar';
                    SmartSpending.showToast(msg, 'error');
                }
            }).catch(function (err) {
                SmartSpending.hideLoader();
                // Sử dụng backtick (`) thay vì nháy đơn để an toàn hơn
                SmartSpending.showToast(`Lỗi khi tải lên: ${err.message || err}`, 'error');
            });
        });
    } catch (e) {
        // swallow errors silently to avoid breaking other pages
        console.error('Avatar upload init error', e);
    }
});
