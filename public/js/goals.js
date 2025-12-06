// === GOALS PAGE JS ===

/**
 * Goals Management with AJAX
 * Features: Create, Update, Delete, Mark Completed
 */

(function() {
    'use strict';

    // State
    let currentGoalId = null;
    let goalsData = [];

    // DOM Elements
    const goalForm = document.getElementById('goalForm');
    const goalModal = document.getElementById('goalModal');
    const goalModalLabel = document.getElementById('goalModalLabel');
    const btnAddGoal = document.getElementById('btnAddGoal');
    const goalsContainer = document.getElementById('goalsContainer');
    
    // Amount input masking
    const amountInput = document.getElementById('goalTargetAmount');
    
    /**
     * Initialize
     */
    function init() {
        setupEventListeners();
        setupAmountMasking();
        setMinDate();
    }
    
    /**
     * Setup event listeners
     */
    function setupEventListeners() {
        // Add goal button
        if (btnAddGoal) {
            btnAddGoal.addEventListener('click', handleAddGoal);
        }
        
        // Form submit
        if (goalForm) {
            goalForm.addEventListener('submit', handleFormSubmit);
        }
        
        // Edit buttons
        document.addEventListener('click', function(e) {
            if (e.target.closest('.btn-edit-goal')) {
                e.preventDefault();
                const goalId = e.target.closest('.btn-edit-goal').dataset.goalId;
                handleEditGoal(goalId);
            }
            
            if (e.target.closest('.btn-delete-goal')) {
                e.preventDefault();
                const goalId = e.target.closest('.btn-delete-goal').dataset.goalId;
                handleDeleteGoal(goalId);
            }
            
            if (e.target.closest('.btn-mark-completed')) {
                e.preventDefault();
                const goalId = e.target.closest('.btn-mark-completed').dataset.goalId;
                handleMarkCompleted(goalId);
            }
        });
        
        // Modal reset
        if (goalModal) {
            goalModal.addEventListener('hidden.bs.modal', resetForm);
        }
    }
    
    /**
     * Setup amount input masking
     */
    function setupAmountMasking() {
        if (!amountInput) return;
        
        amountInput.addEventListener('input', function(e) {
            let value = e.target.value.replace(/[^\d]/g, '');
            e.target.value = formatNumber(value);
        });
        
        amountInput.addEventListener('blur', function(e) {
            if (!e.target.value) {
                e.target.value = '0';
            }
        });
    }
    
    /**
     * Set minimum date to today
     */
    function setMinDate() {
        const deadlineInput = document.getElementById('goalDeadline');
        if (deadlineInput) {
            const today = new Date().toISOString().split('T')[0];
            deadlineInput.setAttribute('min', today);
        }
    }
    
    /**
     * Handle add goal
     */
    function handleAddGoal() {
        currentGoalId = null;
        resetForm();
        goalModalLabel.textContent = 'Thêm Mục Tiêu';
    }
    
    /**
     * Handle edit goal
     */
    async function handleEditGoal(goalId) {
        currentGoalId = goalId;
        goalModalLabel.textContent = 'Chỉnh Sửa Mục Tiêu';
        
        // Find goal data from DOM
        const goalCard = document.querySelector(`[data-goal-id="${goalId}"]`);
        if (!goalCard) return;
        
        // Extract data from card
        const name = goalCard.querySelector('.goal-name').textContent.trim();
        const description = goalCard.querySelector('.text-muted.small')?.textContent.trim() || '';
        const targetText = goalCard.querySelector('.text-muted').textContent;
        const targetAmount = targetText.match(/\/\s*([\d,\.]+)/)?.[1].replace(/[^\d]/g, '') || '0';
        const deadlineText = goalCard.querySelector('.goal-deadline small').textContent;
        const deadlineMatch = deadlineText.match(/(\d{2})\/(\d{2})\/(\d{4})/);
        const deadline = deadlineMatch ? `${deadlineMatch[3]}-${deadlineMatch[2]}-${deadlineMatch[1]}` : '';
        
        // Fill form
        document.getElementById('goalId').value = goalId;
        document.getElementById('goalName').value = name;
        document.getElementById('goalDescription').value = description;
        document.getElementById('goalTargetAmount').value = formatNumber(targetAmount);
        document.getElementById('goalDeadline').value = deadline;
        
        // Show modal
        const modal = new bootstrap.Modal(goalModal);
        modal.show();
    }
    
    /**
     * Handle form submit
     */
    async function handleFormSubmit(e) {
        e.preventDefault();
        
        const formData = new FormData(goalForm);
        
        // Remove formatting from amount
        const targetAmount = formData.get('target_amount').replace(/[^\d]/g, '');
        formData.set('target_amount', targetAmount);
        
        // Show loader
        SmartSpending.showLoader();
        
        try {
            const url = currentGoalId 
                ? `${window.BASE_URL}/goals/api_update_goal/${currentGoalId}`
                : `${window.BASE_URL}/goals/api_create_goal`;
            
            const response = await fetch(url, {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (result.success) {
                SmartSpending.showToast(result.message, 'success');
                
                // Close modal
                const modal = bootstrap.Modal.getInstance(goalModal);
                modal.hide();
                
                // Reload page to refresh data
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            } else {
                SmartSpending.showToast(result.message || 'Có lỗi xảy ra', 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            SmartSpending.showToast('Có lỗi xảy ra khi xử lý yêu cầu', 'error');
        } finally {
            SmartSpending.hideLoader();
        }
    }
    
    /**
     * Handle delete goal
     */
    async function handleDeleteGoal(goalId) {
        if (!confirm('Bạn có chắc chắn muốn xóa mục tiêu này?')) {
            return;
        }
        
        SmartSpending.showLoader();
        
        try {
            const formData = new FormData();
            formData.append('csrf_token', document.querySelector('input[name="csrf_token"]').value);
            
            const response = await fetch(`${window.BASE_URL}/goals/api_delete_goal/${goalId}`, {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (result.success) {
                SmartSpending.showToast(result.message, 'success');
                
                // Remove card from DOM with animation
                const goalCard = document.querySelector(`[data-goal-id="${goalId}"]`);
                if (goalCard) {
                    goalCard.style.opacity = '0';
                    goalCard.style.transform = 'scale(0.9)';
                    setTimeout(() => {
                        goalCard.remove();
                        
                        // Check if no goals left
                        if (goalsContainer.querySelectorAll('[data-goal-id]').length === 0) {
                            window.location.reload();
                        }
                    }, 300);
                }
            } else {
                SmartSpending.showToast(result.message || 'Có lỗi xảy ra', 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            SmartSpending.showToast('Có lỗi xảy ra khi xử lý yêu cầu', 'error');
        } finally {
            SmartSpending.hideLoader();
        }
    }
    
    /**
     * Handle mark completed
     */
    async function handleMarkCompleted(goalId) {
        SmartSpending.showLoader();
        
        try {
            const formData = new FormData();
            formData.append('csrf_token', document.querySelector('input[name="csrf_token"]').value);
            formData.append('status', 'completed');
            
            const response = await fetch(`${window.BASE_URL}/goals/api_update_status/${goalId}`, {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (result.success) {
                SmartSpending.showToast(result.message, 'success');
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            } else {
                SmartSpending.showToast(result.message || 'Có lỗi xảy ra', 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            SmartSpending.showToast('Có lỗi xảy ra khi xử lý yêu cầu', 'error');
        } finally {
            SmartSpending.hideLoader();
        }
    }
    
    /**
     * Reset form
     */
    function resetForm() {
        if (goalForm) {
            goalForm.reset();
        }
        currentGoalId = null;
        document.getElementById('goalId').value = '';
        document.getElementById('goalTargetAmount').value = '0';
    }
    
    /**
     * Format number with thousands separator
     */
    function formatNumber(num) {
        return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    }
    
    // Initialize on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})();
