/**
 * Budgets Manager (single clean module)
 * - Ensures only one module IIFE exists to avoid duplicate/fragment issues
 * - Destroys Chart.js instances before redrawing
 */
(function () {
    let currentPeriod = 'monthly';
    const tableBody = document.getElementById('budgetsList');
    const emptyState = document.getElementById('emptyState');
    const periodSelect = document.getElementById('periodFilter') || document.getElementById('periodSelect');

    let trendChartInstance = null;
    let pieChartInstance = null;

    function formatCurrencyLocal(amount) {
        if (window.SmartSpending && typeof window.SmartSpending.formatCurrency === 'function') {
            return window.SmartSpending.formatCurrency(amount);
        }
        try {
            return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(amount);
        } catch (e) {
            return amount;
        }
    }

    function init() {
        bindUI();
        loadBudgets();
        loadCharts();
    }

    function bindUI() {
        periodSelect?.addEventListener('change', (e) => {
            currentPeriod = e.target.value;
            loadBudgets();
        });

        document.getElementById('openCreateBudget')?.addEventListener('click', () => {
            const modalEl = document.getElementById('createBudgetModal');
            if (modalEl) {
                // Kiểm tra xem đã có modal chưa, nếu có thì lấy lại dùng, chưa có mới tạo
                // (Yêu cầu Bootstrap 5+)
                const modalInstance = bootstrap.Modal.getOrCreateInstance(modalEl);
                modalInstance.show();
            }
        });

        const createForm = document.getElementById('createBudgetForm');
        if (createForm) createForm.addEventListener('submit', handleCreateBudget);
    }
    // public/js/budgets.js

    // public/js/budgets.js

    async function loadBudgets() {
        if (tableBody) tableBody.innerHTML = '<tr><td colspan="4" class="text-center py-4">Đang tải...</td></tr>';
        try {
            const resp = await fetch(`${BASE_URL}/budgets/api_get_all?period=${currentPeriod}`, { cache: 'no-store', credentials: 'same-origin' });
            if (!resp.ok) throw new Error('API error');

            // --- BẮT ĐẦU SỬA ---
            // Đọc text trước để tránh lỗi "stream already read" nếu parse JSON thất bại
            const text = await resp.text();
            let res;
            try {
                res = JSON.parse(text);
            } catch (e) {
                console.error('Non-JSON response', text);
                throw e;
            }
            // --- KẾT THÚC SỬA ---

            if (res.success) renderTable(res.data.budgets || []);
        } catch (e) {
            console.error('loadBudgets error', e);
            if (tableBody) tableBody.innerHTML = '<tr><td colspan="4" class="text-center py-4">Không thể tải dữ liệu</td></tr>';
        }
    }

    function renderTable(budgets) {
        if (!tableBody) return;
        tableBody.innerHTML = '';
        if (!budgets || budgets.length === 0) {
            if (emptyState) emptyState.style.display = 'block';
            return;
        }
        if (emptyState) emptyState.style.display = 'none';

        budgets.forEach(b => {
            let percent = b.amount > 0 ? (b.spent / b.amount) * 100 : 0;
            if (percent > 100) percent = 100;
            let pClass = percent >= 100 ? 'bg-danger' : (percent > 80 ? 'bg-warning' : 'bg-success');

            const row = document.createElement('tr');
            row.innerHTML = `
                <td class="ps-4">
                    <div class="d-flex align-items-center">
                        <div class="me-3" style="width: 36px; height: 36px; background: ${b.category_color || '#ccc'}20; color: ${b.category_color || '#666'}; display: flex; align-items: center; justify-content: center; border-radius: 50%;">
                            <i class="fas ${b.category_icon || 'fa-circle'}"></i>
                        </div>
                        <div><div class="fw-bold text-dark">${b.category_name}</div><small class="text-muted">${(b.category_group || '').toUpperCase()}</small></div>
                    </div>
                </td>
                <td class="text-end">
                    <div class="fw-bold text-dark">${parseFloat(b.spent || 0).toLocaleString('vi-VN')} ₫</div>
                    <small class="text-muted">/ ${parseFloat(b.amount || 0).toLocaleString('vi-VN')} ₫</small>
                </td>
                <td class="ps-4 align-middle">
                    <div class="progress" style="height: 6px; border-radius: 3px;">
                        <div class="progress-bar ${pClass}" style="width: ${percent}%"></div>
                    </div>
                </td>
                <td class="text-end pe-4">
                    <button class="btn btn-sm text-danger opacity-50 hover-opacity-100" onclick="deleteBudget(${b.id})"><i class="fas fa-trash"></i></button>
                </td>
            `;
            tableBody.appendChild(row);
        });
    }

    async function handleCreateBudget(e) {
        e.preventDefault();
        const btn = e.submitter;
        const oldText = btn.innerHTML;
        btn.disabled = true; btn.innerHTML = 'Đang xử lý...';

        const fd = new FormData(e.target);
        const amountRaw = (fd.get('amount') || '').toString().replace(/\D/g, '');

        const data = {
            category_id: fd.get('category_id'),
            amount: amountRaw,
            period: fd.get('period')
        };

        try {
            const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            const resp = await fetch(`${BASE_URL}/budgets/api_create`, {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
                body: JSON.stringify(Object.assign({}, data, { csrf_token: csrf }))
            });
            let res;
            try { res = await resp.json(); } catch (e) { const t = await resp.text(); console.error('Non-JSON response', t); res = { success: false, message: 'Invalid server response' }; }

            if (res.success) {
                const modal = document.getElementById('createBudgetModal');
                if (modal) bootstrap.Modal.getInstance(modal)?.hide();
                e.target.reset();
                loadBudgets();
                alert('Tạo ngân sách thành công!');
                setTimeout(() => window.location.reload(), 300);
            } else {
                let msg = res.message || 'Lỗi';
                if (res.data && res.data.message) msg += "\n" + res.data.message;
                alert(msg);
            }
        } catch (err) { alert('Lỗi hệ thống'); console.error(err); }
        finally { btn.disabled = false; btn.innerHTML = oldText; }
    }

    async function loadCharts() {
        function ensureFreshCanvas(canvasEl) {
            if (!canvasEl) return null;
            try {
                // Try destroying chart attached to canvas
                const existing = (typeof Chart !== 'undefined' && Chart.getChart) ? Chart.getChart(canvasEl) : null;
                if (existing && typeof existing.destroy === 'function') {
                    try { existing.destroy(); } catch (e) { /* ignore */ }
                }
            } catch (e) { /* ignore */ }

            try {
                // Fallback: destroy any Chart.instances bound to this element
                if (typeof Chart !== 'undefined' && Chart.instances) {
                    Object.values(Chart.instances).forEach(c => {
                        try {
                            if (!c) return;
                            const canvasNode = c.canvas && c.canvas.node ? c.canvas.node : c.canvas;
                            if (!canvasNode) return;
                            if (canvasNode === canvasEl || (canvasNode.id && canvasNode.id === canvasEl.id)) {
                                if (typeof c.destroy === 'function') c.destroy();
                            }
                        } catch (ee) { /* ignore */ }
                    });
                }
            } catch (e) { /* ignore */ }

            // Replace canvas node with a clone to ensure no internal Chart references remain
            try {
                const newCanvas = canvasEl.cloneNode(true);
                canvasEl.parentNode.replaceChild(newCanvas, canvasEl);
                return newCanvas;
            } catch (e) {
                return canvasEl;
            }
        }

        const freshTrend = ensureFreshCanvas(document.getElementById('budgetTrend'));
        if (freshTrend) {
            if (trendChartInstance) { try { trendChartInstance.destroy(); } catch (e) {} trendChartInstance = null; }
            try {
                const resp = await fetch(`${BASE_URL}/budgets/api_get_trend`, { cache: 'no-store' });
                if (!resp.ok) throw new Error('API error');
                const res = await resp.json();
                if (res.success && res.data && res.data.trend) {
                    const ctxReal = (freshTrend.getContext && freshTrend.getContext('2d')) ? freshTrend.getContext('2d') : freshTrend;
                    trendChartInstance = new Chart(ctxReal, {
                        type: 'bar',
                        data: {
                            labels: res.data.trend.labels || [],
                            datasets: [
                                { label: 'Ngân sách', data: (res.data.trend.budget || []).map(Number), backgroundColor: '#e2e8f0', borderRadius: 4 },
                                { label: 'Thực chi', data: (res.data.trend.spent || []).map(Number), backgroundColor: '#3b82f6', borderRadius: 4 }
                            ]
                        },
                        options: { responsive: true, plugins: { legend: { position: 'bottom' } }, scales: { x: { grid: { display: false } }, y: { beginAtZero: true } } }
                    });
                }
            } catch (e) { console.warn('loadCharts trend error', e); }
        }

        const freshPie = ensureFreshCanvas(document.getElementById('budgetPie'));
        if (freshPie) {
            if (pieChartInstance) { try { pieChartInstance.destroy(); } catch (e) { } pieChartInstance = null; }
            // Improved doughnut appearance — fetch actual jars data from server
            try {
                const resp = await fetch(`${BASE_URL}/budgets/api_get_jars`, { cache: 'no-store', credentials: 'same-origin' });
                let jarsData = [55, 10, 35];
                if (resp.ok) {
                    try {
                        const jr = await resp.json();
                        if (jr && jr.success && jr.data && Array.isArray(jr.data.jars)) jarsData = jr.data.jars.slice(0,3).map(Number);
                    } catch (e) { /* fall back to defaults */ }
                }

                const labels = ['Thiết yếu', 'Hưởng thụ', 'Tiết kiệm'];
                const colors = ['#ef4444', '#f59e0b', '#3b82f6'];
                const ctxPieReal = (freshPie.getContext && freshPie.getContext('2d')) ? freshPie.getContext('2d') : freshPie;
                pieChartInstance = new Chart(ctxPieReal, {
                    type: 'doughnut',
                    data: {
                        labels: labels,
                        datasets: [{
                            data: jarsData,
                            backgroundColor: colors,
                            borderWidth: 4,
                            hoverOffset: 8,
                            borderRadius: 8
                        }]
                    },
                    options: {
                        cutout: '50%',
                        responsive: true,
                        maintainAspectRatio: false,
                        aspectRatio: 1.2,
                        plugins: {
                            legend: {
                                position: 'right',
                                labels: { usePointStyle: true, pointStyle: 'circle', boxWidth: 10, padding: 12 }
                            },
                            tooltip: {
                                callbacks: {
                                    label: function (context) {
                                        const data = context.chart.data.datasets[0].data;
                                        const total = data.reduce((sum, v) => sum + Number(v || 0), 0);
                                        const value = Number(context.raw || 0);
                                        const pct = total ? ((value / total) * 100).toFixed(1) : 0;
                                        return context.label + ': ' + value + '% (' + pct + '%)';
                                    }
                                }
                            }
                        },
                        layout: { padding: { left: 10, right: 10, top: 6, bottom: 6 } },
                        elements: { arc: { borderWidth: 0 } }
                    }
                });
            } catch (e) {
                console.warn('loadCharts pie error', e);
            }
        }
    }

    window.deleteBudget = async function (id) {
        if (!confirm('Xóa ngân sách này?')) return;
        try {
            await fetch(`${BASE_URL}/budgets/api_delete/${id}`, { method: 'POST', headers: { 'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content } });
        } catch (e) { console.error(e); }
        loadBudgets();
    };

    document.addEventListener('DOMContentLoaded', init);
})();