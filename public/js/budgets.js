const BudgetsApp = (function(){
    let categories = [];
    let currentPeriod = 'monthly';
    let selectedCategoryId = null;
    let budgetsCache = [];
    // Pagination state
    let pageSize = 3;
    let currentPage = 1;
    let totalPages = 1;
    // Auto-refresh handle
    let autoRefreshHandle = null;
    let pieChart = null;

    function init(){
        document.addEventListener('DOMContentLoaded', onReady);
    }

    function onReady(){
        bindUI();
        loadCategories().then(() => loadBudgets());
    }

    function bindUI(){
        document.getElementById('periodSelect')?.addEventListener('change', (e)=>{
            currentPeriod = e.target.value;
            loadBudgets();
        });

        document.getElementById('openCreateBudget')?.addEventListener('click', ()=>openCreateModal());
        
        document.getElementById('openCategoryChooser')?.addEventListener('click', ()=>{
            renderCategoryChooser();
            new bootstrap.Modal(document.getElementById('categoryChooserModal')).show();
        });
        document.getElementById('budget_category_picker')?.addEventListener('click', ()=>{
            renderCategoryChooser();
            new bootstrap.Modal(document.getElementById('categoryChooserModal')).show();
        });

        document.getElementById('budgetsList')?.addEventListener('click', (e)=>{
            const btn = e.target.closest('button');
            if (!btn) return;
            if (btn.dataset.action === "edit") openEditModal(btn.dataset.id);
            if (btn.dataset.action === "delete") confirmDelete(btn.dataset.id);
        });

        const form = document.getElementById('budgetForm');
        if (form){
            form.addEventListener('submit', (e)=>{
                e.preventDefault();
                submitBudgetForm();
            });
        }
    }

    async function fetchJson(url){
        try {
            const r = await fetch(url, {cache:'no-store'});
            if (!r.ok) throw new Error('API Error');
            return await r.json();
        } catch(e) {
            console.error(e);
            return { success: false, message: e.message };
        }
    }

    function loadCategories(){
        return fetchJson(`${window.BASE_URL}/budgets/api_get_categories`)
            .then(data => {
                if (data.success) {
                    categories = data.data.categories || [];
                }
            });
    }

    function loadBudgets(){
        fetchJson(`${window.BASE_URL}/budgets/api_get_all?period=${currentPeriod}`).then(data=>{
            if (!data || !data.success) return;
            budgetsCache = data.data.budgets || [];

            renderBudgets(budgetsCache);
            // Prefer server-provided summary if available
            if (data.data.summary) calculateAndRenderSummary(data.data.summary);
            else calculateAndRenderSummary(budgetsCache);
            renderPie(budgetsCache);
            renderTrend(budgetsCache);
            renderPagination();
        });
    }

    function calculateAndRenderSummary(source) {
        // Accept either an array of budgets or a summary object from API
        let totalBudget = 0;
        let totalSpent = 0;

        if (Array.isArray(source)) {
            source.forEach(b => {
                totalBudget += parseFloat(b.amount) || 0;
                totalSpent += Math.abs(parseFloat(b.spent) || 0);
            });
        } else if (source && source.total_budget !== undefined) {
            totalBudget = parseFloat(source.total_budget) || 0;
            totalSpent = parseFloat(source.total_spent) || 0;
        }

        const totalRemaining = totalBudget - totalSpent;

        const elTotal = document.getElementById('totalBudget');
        const elSpent = document.getElementById('totalSpent');
        const elRemaining = document.getElementById('totalRemaining');
        if (elTotal) elTotal.innerText = formatCurrency(totalBudget);
        if (elSpent) elSpent.innerText = formatCurrency(totalSpent);
        if (elRemaining) elRemaining.innerText = formatCurrency(totalRemaining);
    }

    function renderBudgets(list){
        const container = document.getElementById('budgetsList');
        const empty = document.getElementById('emptyState');
        const table = document.getElementById('budgetsTable');
        
        if (!list.length) {
            container.innerHTML = '';
            table.style.display = 'none';
            empty.style.display = 'block';
            return;
        }
        
        table.style.display = 'table';
        empty.style.display = 'none';

        // Apply pagination
        totalPages = Math.max(1, Math.ceil(list.length / pageSize));
        if (currentPage > totalPages) currentPage = totalPages;
        const start = (currentPage - 1) * pageSize;
        const pageItems = list.slice(start, start + pageSize);

        container.innerHTML = pageItems.map(b => {
            const amount = parseFloat(b.amount) || 0;
            const spent = Math.abs(parseFloat(b.spent) || 0);
            const remaining = amount - spent;
            
            let pct = 0;
            if (amount > 0) pct = (spent / amount) * 100;
            const visualPct = Math.min(Math.max(pct, 0), 100);
            
            let barColor = 'bg-gradient-success';
            let statusText = `${pct.toFixed(1)}%`;
            let statusClass = 'text-primary';
            const threshold = parseFloat(b.alert_threshold) || 80;

            if (pct >= 100) {
                barColor = 'bg-gradient-danger';
                statusText = 'Vượt mức';
                statusClass = 'text-danger fw-bold';
            } else if (pct >= threshold) {
                barColor = 'bg-gradient-warning';
                statusClass = 'text-warning fw-bold';
            }

            return `
                <tr>
                    <td class="ps-4">
                        <div class="d-flex align-items-center">
                            <div class="budget-icon-wrapper me-3" style="background-color: ${b.category_color}15; color: ${b.category_color};">
                                <i class="fas ${b.category_icon || 'fa-tag'}"></i>
                            </div>
                            <div>
                                <span class="budget-info-name">${escapeHtml(b.category_name)}</span>
                                <span class="budget-info-limit">Giới hạn: ${formatCurrency(amount)}</span>
                            </div>
                        </div>
                    </td>
                    <td class="text-end">
                        <span class="fw-bold ${remaining < 0 ? 'text-danger' : 'text-success'}">
                            ${formatCurrency(remaining)}
                        </span>
                    </td>
                    <td class="align-middle">
                        <div class="d-flex justify-content-between small mb-1">
                            <span class="text-muted">Chi: ${formatCurrency(spent)}</span>
                            <span class="${statusClass}">${statusText}</span>
                        </div>
                        <div class="progress-modern-wrapper" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="${Math.round(visualPct)}" aria-label="Tiến độ sử dụng ngân sách">
                            <div class="progress-modern-bar ${barColor}" style="width: ${visualPct}%"></div>
                        </div>
                        <div class="small mt-1 text-muted">${Math.round(visualPct)}% • ${formatCurrency(spent)} / ${formatCurrency(amount)}</div>
                    </td>
                    <td class="text-end pe-4">
                        <button class="btn-action-round" data-action="edit" data-id="${b.id}" title="Sửa">
                            <i class="fas fa-pen"></i>
                        </button>
                        <button class="btn-action-round delete" data-action="delete" data-id="${b.id}" title="Xóa">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    </td>
                </tr>
            `;
        }).join('');
    }

    function renderPagination(){
        const container = document.getElementById('budgetsPagination');
        if (!container) return;
        if (budgetsCache.length <= pageSize) { container.innerHTML = ''; return; }

        let html = '';
        const prevDisabled = currentPage <= 1 ? 'disabled' : '';
        const nextDisabled = currentPage >= totalPages ? 'disabled' : '';

        html += `<nav aria-label="Trang ngân sách"><ul class="pagination">`;
        html += `<li class="page-item ${prevDisabled}"><button class="page-link" data-action="prev">«</button></li>`;

        // show up to 5 page buttons
        const maxButtons = 5;
        let startPage = Math.max(1, currentPage - Math.floor(maxButtons/2));
        let endPage = Math.min(totalPages, startPage + maxButtons - 1);
        if (endPage - startPage < maxButtons - 1) startPage = Math.max(1, endPage - maxButtons + 1);

        for (let p = startPage; p <= endPage; p++){
            const active = p === currentPage ? 'active' : '';
            html += `<li class="page-item ${active}"><button class="page-link" data-page="${p}">${p}</button></li>`;
        }

        html += `<li class="page-item ${nextDisabled}"><button class="page-link" data-action="next">»</button></li>`;
        html += `</ul></nav>`;

        container.innerHTML = html;

        container.querySelectorAll('button.page-link').forEach(btn => {
            btn.addEventListener('click', (e)=>{
                const action = btn.getAttribute('data-action');
                if (action === 'prev') { if (currentPage>1) currentPage--; }
                else if (action === 'next') { if (currentPage<totalPages) currentPage++; }
                else {
                    const p = parseInt(btn.getAttribute('data-page'));
                    if (!isNaN(p)) currentPage = p;
                }
                renderBudgets(budgetsCache);
                renderPagination();
            });
        });
    }

    // Auto-refresh: poll budgets endpoint every interval seconds (default 60s)
    function startBudgetAutoRefresh(intervalSeconds = 60){
        stopBudgetAutoRefresh();
        autoRefreshHandle = setInterval(()=>{
            loadBudgets();
        }, intervalSeconds * 1000);
    }

    function stopBudgetAutoRefresh(){
        if (autoRefreshHandle) { clearInterval(autoRefreshHandle); autoRefreshHandle = null; }
    }

    // Expose refresh and auto-refresh control
    window.budgets = window.budgets || {};
    window.budgets.refresh = loadBudgets;
    window.budgets.startAutoRefresh = startBudgetAutoRefresh;
    window.budgets.stopAutoRefresh = stopBudgetAutoRefresh;

    // Listen for a cross-window event 'transaction:created' to refresh budgets when transactions are added elsewhere
    window.addEventListener('transaction:created', ()=>{
        loadBudgets();
    });

    // (Giữ nguyên các hàm renderPie, modal handler như cũ vì logic không đổi)
    function renderPie(budgets){
        const ctx = document.getElementById('budgetPie');
        if (!ctx || typeof Chart === 'undefined') return;
        if (pieChart) pieChart.destroy();

        const labels = budgets.map(b => b.category_name);
        const data = budgets.map(b => parseFloat(b.amount));
        const colors = budgets.map(b => b.category_color || '#ccc');

        pieChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    data: data,
                    backgroundColor: colors,
                    borderWidth: 0,
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom', labels: { boxWidth: 10, usePointStyle: true, font: {size: 11} } }
                },
                cutout: '70%'
            }
        });
    }

    async function renderTrend(budgets){
        const ctx = document.getElementById('budgetTrend');
        if (!ctx || typeof Chart === 'undefined') return;
        if (window.trendChart) window.trendChart.destroy();

        // Try to fetch real trend data from backend
        try {
            const resp = await fetch(`${window.BASE_URL}/budgets/api_get_trend?months=6`, { cache: 'no-store' });
            if (resp.ok) {
                const json = await resp.json();
                if (json && json.success && json.data && json.data.trend) {
                    const trend = json.data.trend;
                    const labels = trend.labels || [];
                    const budgetsData = (trend.budget || []).map(v => Number(v));
                    const spentData = (trend.spent || []).map(v => Number(v));

                    window.trendChart = new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: labels,
                            datasets: [
                                { label: 'Ngân sách', data: budgetsData, borderColor: '#16a085', backgroundColor: 'rgba(22,160,133,0.08)', tension: 0.3, fill: true },
                                { label: 'Chi tiêu', data: spentData, borderColor: '#ef4444', backgroundColor: 'rgba(239,68,68,0.06)', tension: 0.3, fill: true }
                            ]
                        },
                        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } }
                    });
                    return;
                }
            }
        } catch(e){
            // fallback to demo rendering below
            console.warn('Trend API not available, falling back to demo trend');
        }

        // Fallback: simple distribution of current totals over last 6 months
        const months = 6;
        const now = new Date();
        const labels = [];
        for (let i = months - 1; i >= 0; i--) {
            const d = new Date(now.getFullYear(), now.getMonth() - i, 1);
            labels.push(d.toLocaleString('vi-VN', { month: 'short' }));
        }

        const totalBudget = budgets.reduce((s, b) => s + (parseFloat(b.amount) || 0), 0);
        const totalSpent = budgets.reduce((s, b) => s + Math.abs(parseFloat(b.spent) || 0), 0);

        const budgetsData = new Array(months).fill(Math.round(totalBudget / months));
        const spentData = new Array(months).fill(Math.round(totalSpent / months));

        window.trendChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [
                    { label: 'Ngân sách', data: budgetsData, borderColor: '#16a085', backgroundColor: 'rgba(22,160,133,0.08)', tension: 0.3, fill: true },
                    { label: 'Chi tiêu', data: spentData, borderColor: '#ef4444', backgroundColor: 'rgba(239,68,68,0.06)', tension: 0.3, fill: true }
                ]
            },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } }
        });
    }

    function openCreateModal(){
        document.getElementById('budgetForm').reset();
        document.getElementById('budget_id').value = '';
        document.getElementById('budgetModalTitle').innerText = 'Tạo ngân sách mới';
        document.getElementById('budget_amount_display').value = '';
        new bootstrap.Modal(document.getElementById('createBudgetModal')).show();
    }

    function openEditModal(id){
        const b = budgetsCache.find(x => String(x.id) === String(id));
        if (!b) return;
        
        document.getElementById('budget_id').value = b.id;
        document.getElementById('budget_category').value = b.category_id;
        document.getElementById('budget_category_picker').value = b.category_name;
        
        const amt = parseFloat(b.amount);
        document.getElementById('budget_amount').value = amt;
        document.getElementById('budget_amount_display').value = new Intl.NumberFormat('vi-VN').format(amt);
        
        document.getElementById('budget_period').value = b.period || 'monthly';
        document.getElementById('budget_threshold').value = b.alert_threshold || 80;
        document.getElementById('budgetModalTitle').innerText = 'Cập nhật ngân sách';
        
        new bootstrap.Modal(document.getElementById('createBudgetModal')).show();
    }

    function submitBudgetForm(){
        const id = document.getElementById('budget_id').value;
        const rawAmount = document.getElementById('budget_amount').value;
        
        const payload = {
            category_id: document.getElementById('budget_category').value,
            amount: parseFloat(rawAmount),
            period: document.getElementById('budget_period').value,
            alert_threshold: document.getElementById('budget_threshold').value
        };

        if(!payload.category_id || !payload.amount) {
            alert('Vui lòng nhập đầy đủ thông tin');
            return;
        }

        const url = id ? `${window.BASE_URL}/budgets/api_update/${id}` : `${window.BASE_URL}/budgets/api_create`;
        const csrf = document.querySelector('meta[name="csrf-token"]')?.content;

        fetch(url, {
            method: 'POST',
            headers: {'Content-Type': 'application/json', 'X-CSRF-Token': csrf},
            body: JSON.stringify(payload)
        })
        .then(async r => {
            const text = await r.text();
            let res;
            try {
                res = text ? JSON.parse(text) : { success: false, message: 'Empty response' };
            } catch(err) {
                console.error('Invalid JSON response from server:', text);
                alert('Lỗi: phản hồi không hợp lệ từ server');
                return;
            }

            if(res.success){
                bootstrap.Modal.getInstance(document.getElementById('createBudgetModal')).hide();
                loadBudgets();
            } else {
                alert(res.message || 'Lỗi khi tạo ngân sách');
            }
        }).catch(err => {
            console.error('submitBudgetForm error', err);
            alert('Lỗi kết nối tới server');
        });
    }

    function confirmDelete(id){
        if(!confirm('Bạn có chắc muốn xóa ngân sách này?')) return;
        const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
        fetch(`${window.BASE_URL}/budgets/api_delete/${id}`, {
            method: 'POST', 
            headers: {'X-CSRF-Token': csrf}
        }).then(r=>r.json()).then(d => {
            if(d.success) loadBudgets();
            else alert(d.message);
        });
    }

    function renderCategoryChooser(){
        const list = document.getElementById('categoryList');
        list.innerHTML = '';
        const expenseCats = categories.filter(c => c.type === 'expense');
        expenseCats.forEach(c => {
            const item = document.createElement('button');
            item.className = 'list-group-item list-group-item-action d-flex align-items-center py-3 border-0';
            item.innerHTML = `
                <div class="me-3" style="color: ${c.color}; font-size: 1.2rem; width: 30px; text-align: center;"><i class="fas ${c.icon}"></i></div>
                <div class="fw-medium">${c.name}</div>
            `;
            item.onclick = () => {
                document.getElementById('budget_category').value = c.id;
                document.getElementById('budget_category_picker').value = c.name;
                bootstrap.Modal.getInstance(document.getElementById('categoryChooserModal')).hide();
            };
            list.appendChild(item);
        });
    }

    function formatCurrency(v){ 
        return new Intl.NumberFormat('vi-VN').format(Math.round(v)) + ' ₫'; 
    }
    
    function escapeHtml(s){ 
        return (s||'').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":"&#39;"}[c])); 
    }

    return { init };
})();

BudgetsApp.init();