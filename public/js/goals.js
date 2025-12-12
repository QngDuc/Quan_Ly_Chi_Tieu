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
        let formSending = false;

        // DOM Elements
        const goalForm = document.getElementById('goalForm');
        const goalModal = document.getElementById('goalModal');
        const goalModalLabel = document.getElementById('goalModalLabel');
        const btnAddGoal = document.getElementById('btnAddGoal');
        const goalsContainer = document.getElementById('goalsContainer');
        const amountInput = document.getElementById('goalTargetAmount');

        function init() {
            setupEventListeners();
            setupAmountMasking();
            setMinDate();
        }

        function setupEventListeners() {
            if (btnAddGoal) btnAddGoal.addEventListener('click', handleAddGoal);
            if (goalForm) goalForm.addEventListener('submit', handleFormSubmit);

            document.addEventListener('click', function(e) {
                const editBtn = e.target.closest('.btn-edit-goal');
                if (editBtn) {
                    e.preventDefault();
                    const goalId = editBtn.dataset.goalId;
                    handleEditGoal(goalId);
                    return;
                }

                const deleteBtn = e.target.closest('.btn-delete-goal');
                if (deleteBtn) {
                    e.preventDefault();
                    const goalId = deleteBtn.dataset.goalId;
                    handleDeleteGoal(goalId);
                    return;
                }

                const markBtn = e.target.closest('.btn-mark-completed');
                if (markBtn) {
                    e.preventDefault();
                    const goalId = markBtn.dataset.goalId;
                    handleMarkCompleted(goalId);
                    return;
                }
            });

            if (goalModal) goalModal.addEventListener('hidden.bs.modal', resetForm);
        }

        function setupAmountMasking() {
            if (!amountInput) return;
            amountInput.addEventListener('input', function(e) {
                let value = e.target.value.replace(/[^\d]/g, '');
                e.target.value = formatNumber(value);
            });
            amountInput.addEventListener('blur', function(e) {
                if (!e.target.value) e.target.value = '0';
            });
        }

        function setMinDate() {
            const deadlineInput = document.getElementById('goalDeadline');
            if (!deadlineInput) return;
            const today = new Date().toISOString().split('T')[0];
            deadlineInput.setAttribute('min', today);
        }

        function handleAddGoal() {
            currentGoalId = null;
            resetForm();
            if (goalModalLabel) goalModalLabel.textContent = 'Thêm Mục Tiêu';
        }

        async function handleEditGoal(goalId) {
            currentGoalId = goalId;
            if (goalModalLabel) goalModalLabel.textContent = 'Chỉnh Sửa Mục Tiêu';

            const goalCard = document.querySelector(`[data-goal-id="${goalId}"]`);
            if (!goalCard) return;

            const name = goalCard.querySelector('.goal-name')?.textContent.trim() || '';
            const description = goalCard.querySelector('.text-muted.small')?.textContent.trim() || '';
            const targetText = goalCard.querySelector('.text-muted')?.textContent || '';
            const targetAmount = (targetText.match(/\/\s*([\d,\.]+)/)?.[1] || '').replace(/[^\d]/g, '') || '0';
            const deadlineText = goalCard.querySelector('.goal-deadline small')?.textContent || '';
            const deadlineMatch = deadlineText.match(/(\d{2})\/(\d{2})\/(\d{4})/);
            const deadline = deadlineMatch ? `${deadlineMatch[3]}-${deadlineMatch[2]}-${deadlineMatch[1]}` : '';

            document.getElementById('goalId').value = goalId;
            document.getElementById('goalName').value = name;
            document.getElementById('goalDescription').value = description;
            document.getElementById('goalTargetAmount').value = formatNumber(targetAmount);
            document.getElementById('goalDeadline').value = deadline;

            const modal = new bootstrap.Modal(goalModal);
            modal.show();
        }

        async function handleFormSubmit(e) {
            e.preventDefault();
            if (formSending) return;
            formSending = true;
            const saveBtn = document.getElementById('btnSaveGoal');
            if (saveBtn) { saveBtn.disabled = true; saveBtn.dataset.sending = '1'; }

            const formData = new FormData(goalForm);
            const rawAmount = formData.get('target_amount') || '';
            formData.set('target_amount', String(rawAmount).replace(/[^\d]/g, ''));

            SmartSpending.showLoader();
            try {
                const url = currentGoalId ? `${window.BASE_URL}/goals/api_update_goal/${currentGoalId}` : `${window.BASE_URL}/goals/api_create_goal`;
                const response = await fetch(url, { method: 'POST', body: formData });
                const text = await response.text();
                let result = null;
                try { result = text ? JSON.parse(text) : null; } catch (err) { result = null; }
                const resp = result || { success: response.ok, message: text };

                if (resp.success) {
                    SmartSpending.showToast(resp.message || 'Thành công', 'success');
                    const modal = bootstrap.Modal.getInstance(goalModal);
                    if (modal) modal.hide();
                    setTimeout(() => window.location.reload(), 800);
                } else {
                    SmartSpending.showToast(resp.message || 'Có lỗi xảy ra', 'error');
                }
            } catch (err) {
                console.error('Error:', err);
                SmartSpending.showToast('Có lỗi xảy ra khi xử lý yêu cầu', 'error');
            } finally {
                SmartSpending.hideLoader();
                formSending = false;
                if (saveBtn) { saveBtn.disabled = false; delete saveBtn.dataset.sending; }
            }
        }

        function handleDeleteGoal(goalId) {
            SmartSpending.showConfirm(
                'Xóa Mục Tiêu?',
                'Bạn có chắc chắn muốn xóa mục tiêu này? Hành động này không thể hoàn tác.',
                () => {
                    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                    (async () => {
                        SmartSpending.showLoader();
                        try {
                            const response = await fetch(`${window.BASE_URL}/goals/api_delete_goal/${goalId}`, {
                                method: 'POST',
                                headers: csrfToken ? { 'X-CSRF-Token': csrfToken } : {},
                            });
                            const text = await response.text();
                            let json = null;
                            try { json = text ? JSON.parse(text) : null; } catch (err) { json = null; }
                            const respData = json || { success: response.ok, message: text };

                            if (respData.success === true || respData.status === 'success' || response.ok) {
                                SmartSpending.showToast(respData.message || 'Xóa mục tiêu thành công!', 'success');
                                const goalCard = document.querySelector(`[data-goal-id="${goalId}"]`);
                                if (goalCard) {
                                    goalCard.style.opacity = '0';
                                    goalCard.style.transform = 'scale(0.9)';
                                    setTimeout(() => {
                                        goalCard.remove();
                                        if (goalsContainer && goalsContainer.querySelectorAll('[data-goal-id]').length === 0) window.location.reload();
                                    }, 300);
                                }
                            } else {
                                SmartSpending.showToast(respData.message || 'Không thể xóa mục tiêu', 'error');
                            }
                        } catch (error) {
                            console.error('Error deleting goal:', error);
                            SmartSpending.showToast('Lỗi khi xóa mục tiêu', 'error');
                        } finally {
                            SmartSpending.hideLoader();
                        }
                    })();
                }
            );
        }

        async function handleMarkCompleted(goalId) {
            SmartSpending.showLoader();
            try {
                const formData = new FormData();
                const tokenInput = document.querySelector('input[name="csrf_token"]');
                if (tokenInput) formData.append('csrf_token', tokenInput.value);
                formData.append('status', 'completed');
                const response = await fetch(`${window.BASE_URL}/goals/api_update_status/${goalId}`, { method: 'POST', body: formData });
                const result = await response.json();
                if (result.success) {
                    SmartSpending.showToast(result.message, 'success');
                    setTimeout(() => window.location.reload(), 800);
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

        function resetForm() {
            if (goalForm) goalForm.reset();
            currentGoalId = null;
            const idEl = document.getElementById('goalId'); if (idEl) idEl.value = '';
            const amt = document.getElementById('goalTargetAmount'); if (amt) amt.value = '0';
        }

        function formatNumber(num) {
            return String(num).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
        }

        if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init); else init();

    })();
