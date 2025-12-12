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
                const btnSync = document.getElementById('btnSyncPending');
                if (btnSync) btnSync.addEventListener('click', syncPendingGoals);
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
                // optional fields
                const startEl = document.getElementById('goalStartDate'); if (startEl) startEl.value = '';
                const catEl = document.getElementById('goalCategory'); if (catEl) catEl.value = goalCard.dataset.categoryId || '';
                const statusEl = document.getElementById('goalStatus'); if (statusEl) statusEl.value = goalCard.dataset.status || 'active';

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
                // client-side validation: ensure start_date <= deadline
                const startDate = formData.get('start_date') || '';
                const deadline = formData.get('deadline') || '';
                if (startDate && deadline) {
                    try {
                        const s = new Date(startDate);
                        const d = new Date(deadline);
                        if (s > d) {
                            SmartSpending.showToast('Ngày bắt đầu phải nhỏ hơn hoặc bằng ngày đến hạn', 'error');
                            SmartSpending.hideLoader();
                            formSending = false;
                            if (saveBtn) { saveBtn.disabled = false; delete saveBtn.dataset.sending; }
                            return;
                        }
                    } catch (err) {}
                }
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
                async () => {
                    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                    SmartSpending.showLoader();
                    try {
                        const response = await fetch(`${window.BASE_URL}/goals/api_delete_goal/${goalId}`, {
                            method: 'POST',
                            headers: csrfToken ? { 'X-CSRF-Token': csrfToken } : {}
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
                }
            );
        }

        /* -- Local pending storage for goals when backend schema not ready -- */
        const PENDING_KEY = 'ss_pending_goals_v1';
        function getPendingGoals() {
            try { return JSON.parse(localStorage.getItem(PENDING_KEY) || '[]'); } catch (e) { return []; }
        }
        function savePendingGoal(obj) {
            const list = getPendingGoals();
            obj._created_at = (new Date()).toISOString();
            list.push(obj);
            localStorage.setItem(PENDING_KEY, JSON.stringify(list));
        }
        function clearPendingGoals() { localStorage.removeItem(PENDING_KEY); updatePendingCount(); }
        function updatePendingCount() {
            const c = getPendingGoals().length;
            const el = document.getElementById('pendingCount');
            const btn = document.getElementById('btnSyncPending');
            if (el) el.textContent = c;
            if (btn) btn.style.display = c > 0 ? 'inline-block' : 'none';
        }

        async function syncPendingGoals() {
            const list = getPendingGoals();
            if (!list.length) { SmartSpending.showToast('Không có mục tiêu tạm nào cần đồng bộ', 'info'); return; }
            SmartSpending.showLoader();
            let successCount = 0;
            for (const item of list) {
                try {
                    const fd = new FormData();
                    for (const k of Object.keys(item)) if (!k.startsWith('_')) fd.append(k, item[k]);
                    const response = await fetch(`${window.BASE_URL}/goals/api_create_goal`, { method: 'POST', body: fd });
                    const text = await response.text();
                    let json = null; try { json = text ? JSON.parse(text) : null; } catch(e){ json = null; }
                    const resp = json || { success: response.ok };
                    if (resp && resp.success) successCount++;
                } catch (err) {
                    console.warn('Sync failed for an item', err);
                }
            }
            SmartSpending.hideLoader();
            if (successCount === list.length) {
                clearPendingGoals();
                SmartSpending.showToast('Đồng bộ thành công tất cả mục tiêu tạm', 'success');
                setTimeout(()=> window.location.reload(), 700);
            } else if (successCount > 0) {
                // remove only sent ones for simplicity: clear all and keep none
                clearPendingGoals();
                SmartSpending.showToast(`Đồng bộ một phần: ${successCount}/${list.length}`, 'warning');
                setTimeout(()=> window.location.reload(), 900);
            } else {
                SmartSpending.showToast('Không thể đồng bộ mục tiêu tạm, thử lại sau', 'error');
            }
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

        // Charts: fetch goals and render progress & pie charts
        let progressChart = null, pieChart = null;
        async function renderCharts() {
            if (typeof Chart === 'undefined') return;
            try {
                const r = await fetch(`${window.BASE_URL}/goals/api_get_goals`, { cache: 'no-store' });
                const json = await r.json();
                if (!json || !json.success) return;
                const goals = json.data.goals || [];

                const labels = goals.map(g => g.name.substring(0,12));
                const saved = goals.map(g => Number(g.current_amount) || 0);
                const remaining = goals.map(g => Math.max(0, (Number(g.target_amount)||0) - (Number(g.current_amount)||0)));

                const ctx = document.getElementById('goalProgressChart');
                if (ctx) {
                    if (progressChart) progressChart.destroy();
                    progressChart = new Chart(ctx, {
                        type: 'bar',
                        data: {
                            labels: labels,
                            datasets: [
                                { label: 'Đã tiết kiệm', data: saved, backgroundColor: '#16a085' },
                                { label: 'Còn lại', data: remaining, backgroundColor: '#9ca3af' }
                            ]
                        },
                        options: { responsive:true, maintainAspectRatio:false }
                    });
                }

                const pctx = document.getElementById('goalPieChart');
                if (pctx) {
                    if (pieChart) pieChart.destroy();
                    pieChart = new Chart(pctx, {
                        type: 'doughnut',
                        data: {
                            labels: labels,
                            datasets: [{ data: saved, backgroundColor: labels.map((_,i)=>['#10b981','#34d399','#60a5fa','#f97316','#ef4444','#f59e0b'][i%6]) }]
                        },
                        options: { responsive:true, maintainAspectRatio:false, plugins:{legend:{position:'bottom'}} }
                    });
                }
            } catch (e) {
                console.warn('Unable to render goal charts', e);
            }
        }

        function formatNumber(num) {
            return String(num).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
        }

        if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', function(){ init(); renderCharts(); updatePendingCount(); }); else { init(); renderCharts(); updatePendingCount(); }

    })();
