<?php 
use App\Middleware\CsrfProtection;
$this->partial('header'); 
?>

<!-- Budgets Specific Styles -->
<link rel="stylesheet" href="<?php echo BASE_URL; ?>/resources/css/budgets.css">
<?php echo CsrfProtection::getTokenMeta(); ?>

<section>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3><i class="fas fa-wallet me-2"></i>Quản lý Ngân sách</h3>
        <div class="d-flex gap-2">
            <select class="form-select form-select-sm" id="periodSelect" style="width: 150px;">
                <option value="monthly">Tháng này</option>
                <option value="weekly">Tuần này</option>
                <option value="yearly">Năm này</option>
            </select>
            <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#createBudgetModal">
                <i class="fas fa-plus-circle"></i> Thêm ngân sách
            </button>
        </div>
    </div>

    <!-- Summary Cards -->
        <!-- Category chips (large, sticky) -->
        <div id="categoryChipsWrap" class="mb-3">
            <div id="categoryChips" class="d-flex gap-2 flex-wrap"></div>
        </div>

    <div class="row g-3 mb-4" id="summaryCards">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div>
                        <p class="text-muted mb-2 small fw-semibold">Tổng ngân sách</p>
                        <h3 class="mb-0 fw-bold text-primary" id="totalBudget">0 ₫</h3>
                    </div>
                    <div class="bg-primary p-3 rounded-3">
                        <i class="fas fa-wallet fa-2x text-primary"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div>
                        <p class="text-muted mb-2 small fw-semibold">Đã chi tiêu</p>
                        <h3 class="mb-0 fw-bold text-danger" id="totalSpent">0 ₫</h3>
                    </div>
                    <div class="bg-danger p-3 rounded-3">
                        <i class="fas fa-arrow-down fa-2x text-danger"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div>
                        <p class="text-muted mb-2 small fw-semibold">Còn lại</p>
                        <h3 class="mb-0 fw-bold text-success" id="totalRemaining">0 ₫</h3>
                    </div>
                    <div class="bg-success p-3 rounded-3">
                        <i class="fas fa-piggy-bank fa-2x text-success"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Budgets List -->
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div id="budgetsList">
                <!-- Budgets will be loaded dynamically -->
            </div>
        </div>
    </div>

    <!-- Empty State -->
    <div class="text-center py-5" id="emptyState" style="display: none;">
        <i class="fas fa-wallet text-muted" style="font-size: 4rem;"></i>
        <h5 class="mt-3 text-muted">Chưa có ngân sách nào</h5>
        <p class="text-muted">Tạo ngân sách đầu tiên để theo dõi chi tiêu của bạn</p>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createBudgetModal">
            <i class="fas fa-plus-circle"></i> Tạo ngân sách mới
        </button>
    </div>
</section>

<!-- Create/Edit Budget Modal -->
<div class="modal fade" id="createBudgetModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="budgetModalTitle">Tạo ngân sách mới</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="budgetForm">
                    <input type="hidden" id="budget_id" name="budget_id">
                    
                    <div class="mb-3">
                        <label class="form-label">Danh mục *</label>
                        <select class="form-select" id="budget_category" name="category_id" required>
                            <option value="">-- Chọn danh mục --</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Số tiền ngân sách *</label>
                        <input type="number" class="form-control" id="budget_amount" name="amount" 
                               required min="1" step="1000" placeholder="1000000">
                        <small class="text-muted">Số tiền tối đa cho phép chi tiêu trong kỳ</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Chu kỳ *</label>
                        <select class="form-select" id="budget_period" name="period" required>
                            <option value="monthly">Hàng tháng</option>
                            <option value="weekly">Hàng tuần</option>
                            <option value="yearly">Hàng năm</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Ngưỡng cảnh báo (%)</label>
                        <input type="number" class="form-control" id="budget_threshold" name="alert_threshold" 
                               value="80" min="1" max="100" step="1">
                        <small class="text-muted">Cảnh báo khi chi tiêu vượt quá % này (mặc định 80%)</small>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                <button type="button" class="btn btn-primary" id="saveBudgetBtn" onclick="saveBudget()">Lưu</button>
            </div>
        </div>
    </div>
</div>

<script>
window.BASE_URL = "<?php echo BASE_URL; ?>";
let categories = [];
let currentPeriod = 'monthly';
let selectedCategoryId = null; // filter by category when set
let currentBudgetsCache = [];

document.addEventListener('DOMContentLoaded', function() {
    // Ensure categories are loaded first so we can filter out budgets
    loadCategories().then(() => {
        loadBudgets();
    });
    
    // Period change handler
    document.getElementById('periodSelect').addEventListener('change', function(e) {
        currentPeriod = e.target.value;
        loadBudgets();
    });
    
    // Modal open handler
    document.getElementById('createBudgetModal').addEventListener('show.bs.modal', function() {
        resetBudgetForm();
    });
});

// Load categories for dropdown
function loadCategories() {
    return fetch(`${BASE_URL}/budgets/api_get_categories`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                categories = data.data.categories;
                renderCategoryOptions();
                renderCategoryChips();
            }
            return categories;
        })
        .catch(error => {
            console.error('Error loading categories:', error);
            return [];
        });
}

// Render large category chips for quick filtering
function renderCategoryChips() {
    const wrap = document.getElementById('categoryChips');
    if (!wrap) return;
    wrap.innerHTML = '';

    // Add an "All" chip
    const allChip = document.createElement('button');
    allChip.className = 'category-chip btn btn-sm btn-outline-secondary';
    allChip.textContent = 'Tất cả';
    allChip.onclick = () => {
        selectedCategoryId = null;
        updateChipActive();
        renderBudgets(currentBudgetsCache);
    };
    wrap.appendChild(allChip);

    categories.forEach(cat => {
        const btn = document.createElement('button');
        btn.className = 'category-chip btn btn-sm btn-light';
        btn.innerHTML = `<span style="display:inline-block;width:10px;height:10px;background:${cat.color || '#000'};border-radius:2px;margin-right:8px;"></span> ${cat.name}`;
        btn.onclick = () => {
            selectedCategoryId = parseInt(cat.id);
            updateChipActive();
            renderBudgets(currentBudgetsCache);
        };
        wrap.appendChild(btn);
    });

    updateChipActive();
}

function updateChipActive() {
    const chips = document.querySelectorAll('#categoryChips .category-chip');
    chips.forEach(ch => ch.classList.remove('active'));
    if (selectedCategoryId === null) {
        // first chip is All
        if (chips[0]) chips[0].classList.add('active');
    } else {
        chips.forEach(ch => {
            const txt = ch.textContent || '';
            if (txt.includes(String(selectedCategoryId))) {
                // Not reliable to find by id text; instead rely on index mapping: skip
            }
        });
        // simpler approach: iterate categories and mark matching index+1
        categories.forEach((cat, idx) => {
            if (parseInt(cat.id) === selectedCategoryId) {
                const chip = document.querySelectorAll('#categoryChips .category-chip')[idx+1];
                if (chip) chip.classList.add('active');
            }
        });
    }
}

// Render category dropdown
function renderCategoryOptions() {
    const select = document.getElementById('budget_category');
    const currentValue = select.value;
    
    select.innerHTML = '<option value="">-- Chọn danh mục --</option>';
    
    categories.forEach(cat => {
        const option = document.createElement('option');
        option.value = cat.id;
        option.textContent = cat.name;
        option.style.color = cat.color || '#000';
        select.appendChild(option);
    });
    
    if (currentValue) {
        select.value = currentValue;
    }
}

// Load all budgets
function loadBudgets() {
    fetch(`${BASE_URL}/budgets/api_get_all?period=${currentPeriod}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Filter out budgets whose category no longer exists (legacy budgets)
                const validBudgets = (data.data.budgets || []).filter(b => {
                    return categories.some(c => parseInt(c.id) === parseInt(b.category_id));
                });

                // cache for client-side filtering by chips
                currentBudgetsCache = validBudgets;

                renderBudgets(validBudgets);

                // Adjust summary to reflect filtered budgets totals
                const adjustedSummary = Object.assign({}, data.data.summary);
                adjustedSummary.total_budget = validBudgets.reduce((s, b) => s + (parseFloat(b.amount) || 0), 0);
                adjustedSummary.total_spent = validBudgets.reduce((s, b) => s + (parseFloat(b.spent) || 0), 0);
                adjustedSummary.remaining = adjustedSummary.total_budget - adjustedSummary.total_spent;

                renderSummary(adjustedSummary);
            }
        })
        .catch(error => console.error('Error:', error));
}

// Format currency
function formatCurrency(amount) {
    return new Intl.NumberFormat('vi-VN').format(amount) + ' ₫';
}

// Render summary cards
function renderSummary(summary) {
    document.getElementById('totalBudget').textContent = formatCurrency(summary.total_budget || 0);
    document.getElementById('totalSpent').textContent = formatCurrency(summary.total_spent || 0);
    document.getElementById('totalRemaining').textContent = formatCurrency(summary.remaining || 0);
}

// Render budgets list
function renderBudgets(budgets) {
    const list = document.getElementById('budgetsList');
    const emptyState = document.getElementById('emptyState');
    
    // Apply category chip filter if selected
    let filtered = budgets || [];
    if (selectedCategoryId !== null) {
        filtered = filtered.filter(b => parseInt(b.category_id) === parseInt(selectedCategoryId));
    }

    if (!filtered || filtered.length === 0) {
        list.innerHTML = '';
        emptyState.style.display = 'block';
        return;
    }
    
    emptyState.style.display = 'none';
    
    list.innerHTML = filtered.map(budget => {
        const percentage = parseFloat(budget.percentage_used) || 0;
        const isOverBudget = percentage > 100;
        const isNearLimit = percentage >= budget.alert_threshold && percentage <= 100;
        
        let progressColor = 'bg-success';
        let statusClass = '';
        let statusText = '';
        
        if (isOverBudget) {
            progressColor = 'bg-danger';
            statusClass = 'text-danger';
            statusText = '<i class="fas fa-exclamation-triangle"></i> Vượt ngân sách';
        } else if (isNearLimit) {
            progressColor = 'bg-warning';
            statusClass = 'text-warning';
            statusText = '<i class="fas fa-exclamation-circle"></i> Gần đạt ngưỡng';
        } else {
            statusClass = 'text-success';
            statusText = '<i class="fas fa-check-circle"></i> Trong giới hạn';
        }
        
        return `
            <div class="budget-item mb-3 pb-3 border-bottom">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div class="d-flex align-items-center gap-2">
                        <div class="category-icon" style="background-color: ${budget.category_color}20; color: ${budget.category_color}; width: 40px; height: 40px; border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                            <i class="${budget.category_icon || 'fas fa-circle'} fa-lg"></i>
                        </div>
                        <div>
                            <h6 class="mb-0">${budget.category_name}</h6>
                            <small class="${statusClass}">${statusText}</small>
                        </div>
                    </div>
                    <div class="dropdown">
                        <button class="btn btn-sm btn-light dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            <i class="fas fa-ellipsis-v"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="#" onclick="editBudget(${budget.id}); return false;">
                                <i class="fas fa-edit"></i> Sửa
                            </a></li>
                            <li><a class="dropdown-item" href="#" onclick="toggleBudget(${budget.id}); return false;">
                                <i class="fas fa-power-off"></i> ${budget.is_active ? 'Tắt' : 'Bật'}
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="#" onclick="deleteBudget(${budget.id}); return false;">
                                <i class="fas fa-trash"></i> Xóa
                            </a></li>
                        </ul>
                    </div>
                </div>
                
                <div class="progress mb-2" style="height: 20px;">
                    <div class="progress-bar ${progressColor}" role="progressbar" 
                         style="width: ${Math.min(percentage, 100)}%;" 
                         aria-valuenow="${percentage}" aria-valuemin="0" aria-valuemax="100">
                        ${percentage.toFixed(1)}%
                    </div>
                </div>
                
                <div class="d-flex justify-content-between text-muted small">
                    <span>Đã chi: <strong class="text-dark">${formatCurrency(budget.spent)}</strong></span>
                    <span>Ngân sách: <strong class="text-dark">${formatCurrency(budget.amount)}</strong></span>
                    <span>Còn lại: <strong class="${budget.remaining >= 0 ? 'text-success' : 'text-danger'}">${formatCurrency(budget.remaining)}</strong></span>
                </div>
            </div>
        `;
    }).join('');
}

// Reset budget form
function resetBudgetForm() {
    document.getElementById('budgetForm').reset();
    document.getElementById('budget_id').value = '';
    document.getElementById('budgetModalTitle').textContent = 'Tạo ngân sách mới';
    document.getElementById('budget_threshold').value = 80;
}

// Save budget
function saveBudget() {
    const budgetId = document.getElementById('budget_id').value;
    const formData = {
        category_id: parseInt(document.getElementById('budget_category').value),
        amount: parseFloat(document.getElementById('budget_amount').value),
        period: document.getElementById('budget_period').value,
        alert_threshold: parseFloat(document.getElementById('budget_threshold').value)
    };
    
    if (!formData.category_id || !formData.amount) {
        alert('Vui lòng điền đầy đủ thông tin');
        return;
    }
    
    const url = budgetId 
        ? `${BASE_URL}/budgets/api_update/${budgetId}`
        : `${BASE_URL}/budgets/api_create`;
    
    fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify(formData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            bootstrap.Modal.getInstance(document.getElementById('createBudgetModal')).hide();
            loadBudgets();
            alert(budgetId ? 'Cập nhật ngân sách thành công' : 'Tạo ngân sách thành công');
        } else {
            alert('Lỗi: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Đã xảy ra lỗi khi lưu ngân sách');
    });
}

// Edit budget
function editBudget(id) {
    fetch(`${BASE_URL}/budgets/api_get_all?period=${currentPeriod}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Prefer the budgets currently rendered (which are filtered)
                const allBudgets = data.data.budgets || [];
                const budget = allBudgets.find(b => b.id == id);
                if (budget) {
                    document.getElementById('budget_id').value = budget.id;
                    document.getElementById('budget_category').value = budget.category_id;
                    document.getElementById('budget_amount').value = budget.amount;
                    document.getElementById('budget_period').value = budget.period || 'monthly';
                    document.getElementById('budget_threshold').value = budget.alert_threshold ?? 80;
                    document.getElementById('budgetModalTitle').textContent = 'Sửa ngân sách';
                    
                    new bootstrap.Modal(document.getElementById('createBudgetModal')).show();
                }
            }
        });
}

// Delete budget
function deleteBudget(id) {
    if (!confirm('Bạn có chắc muốn xóa ngân sách này?')) return;
    
    fetch(`${BASE_URL}/budgets/api_delete/${id}`, {
        method: 'POST',
        headers: {
            'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            loadBudgets();
            alert('Xóa ngân sách thành công');
        } else {
            alert('Lỗi: ' + data.message);
        }
    });
}

// Toggle budget active status
function toggleBudget(id) {
    fetch(`${BASE_URL}/budgets/api_toggle/${id}`, {
        method: 'POST',
        headers: {
            'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            loadBudgets();
        } else {
            alert('Lỗi: ' + data.message);
        }
    });
}
</script>

<style>
/* Large category chips and sticky behavior */
#categoryChipsWrap{position:sticky;top:72px;z-index:1020;background:transparent;padding-top:6px;padding-bottom:6px}
.category-chip{font-size:0.95rem;padding:8px 12px;border-radius:14px;border:1px solid rgba(0,0,0,0.06)}
.category-chip.active{background:#0d6efd;color:#fff;border-color:#0d6efd}
#categoryChips .btn{box-shadow:0 1px 2px rgba(0,0,0,0.03)}
</style>

<?php $this->partial('footer'); ?>
