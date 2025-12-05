// === SMARTSPENDING - MAIN APPLICATION JS ===

// Utility Functions
const SmartSpending = {
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
    showConfirm: (title, message, onConfirm) => {
        const overlay = document.createElement('div');
        overlay.className = 'confirm-overlay';
        overlay.innerHTML = `
            <div class="confirm-dialog">
                <div class="confirm-title">${title}</div>
                <div class="confirm-message">${message}</div>
                <div class="confirm-actions">
                    <button class="confirm-btn confirm-btn-cancel">Cancel</button>
                    <button class="confirm-btn confirm-btn-delete">Delete</button>
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
            onConfirm();
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
};

// Global event listeners
document.addEventListener('DOMContentLoaded', function() {
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
});

// Export for use in other files
window.SmartSpending = SmartSpending;
