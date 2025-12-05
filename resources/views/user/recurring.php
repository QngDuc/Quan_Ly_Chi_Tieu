<?php 
use App\Middleware\CsrfProtection;
$this->partial('header'); 
?>

<link rel="stylesheet" href="<?php echo BASE_URL; ?>/user/transactions/transactions.css">
<?php echo CsrfProtection::getTokenMeta(); ?>

<section>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3><i class="fas fa-sync-alt me-2"></i>Giao dịch Định kỳ</h3>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#recurringModal" onclick="openCreateModal()">
            <i class="fas fa-plus-circle me-2"></i>Thêm Định kỳ
        </button>
    </div>

    <!-- Stats Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h5 class="text-muted">Tổng số</h5>
                    <h3 id="totalCount">0</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h5 class="text-success">Đang hoạt động</h5>
                    <h3 id="activeCount">0</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h5 class="text-success">Thu định kỳ/tháng</h5>
                    <h3 id="monthlyIncome">0đ</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h5 class="text-danger">Chi định kỳ/tháng</h5>
                    <h3 id="monthlyExpense">0đ</h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Table -->
    <div class="card">
        <div class="card-body">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Trạng thái</th>
                        <th>Loại</th>
                        <th>Danh mục</th>
                        <th>Số tiền</th>
                        <th>Tần suất</th>
                        <th>Ngày tiếp theo</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody id="recurringList">
                    <tr><td colspan="7" class="text-center">Đang tải...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</section>

<!-- Create/Edit Modal -->
<div class="modal fade" id="recurringModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">Thêm Giao dịch Định kỳ</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="recurringForm">
                <div class="modal-body">
                    <input type="hidden" id="recurring_id">
                    
                    <div class="mb-3">
                        <label class="form-label">Loại giao dịch</label>
                        <select class="form-select" id="type" required onchange="filterCategories()">
                            <option value="expense">Chi tiêu</option>
                            <option value="income">Thu nhập</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Danh mục</label>
                        <select class="form-select" id="category_id" required>
                            <option value="">Chọn danh mục</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>" data-type="<?= $cat['type'] ?>">
                                    <?= $this->escape($cat['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Số tiền</label>
                        <input type="number" class="form-control" id="amount" required min="0" step="0.01">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Mô tả</label>
                        <input type="text" class="form-control" id="description">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Tần suất</label>
                        <select class="form-select" id="frequency" required>
                            <option value="daily">Hàng ngày</option>
                            <option value="weekly">Hàng tuần</option>
                            <option value="monthly">Hàng tháng</option>
                            <option value="yearly">Hàng năm</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Ngày bắt đầu</label>
                        <input type="date" class="form-control" id="start_date" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Ngày kết thúc (tùy chọn)</label>
                        <input type="date" class="form-control" id="end_date">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-primary">Lưu</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
const BASE_URL = '<?= BASE_URL ?>';
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

// Load data on page load
document.addEventListener('DOMContentLoaded', function() {
    loadRecurringTransactions();
    filterCategories(); // Filter categories on load
});

// Filter categories by type
function filterCategories() {
    const type = document.getElementById('type').value;
    const categorySelect = document.getElementById('category_id');
    const options = categorySelect.querySelectorAll('option');
    
    options.forEach(option => {
        if (option.value === '') {
            option.style.display = 'block';
            return;
        }
        const optionType = option.getAttribute('data-type');
        option.style.display = (optionType === type) ? 'block' : 'none';
    });
    
    // Reset selection if current selection doesn't match type
    const selectedOption = categorySelect.options[categorySelect.selectedIndex];
    if (selectedOption && selectedOption.getAttribute('data-type') !== type) {
        categorySelect.value = '';
    }
}

// Load recurring transactions
function loadRecurringTransactions() {
    fetch(`${BASE_URL}/recurring/api_get_all`)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.text();
        })
        .then(text => {
            console.log('Raw response:', text);
            if (!text || text.trim() === '') {
                throw new Error('Empty response from server');
            }
            try {
                const data = JSON.parse(text);
                if (data.success) {
                    renderRecurring(data.data.recurring_transactions);
                    updateStats(data.data.recurring_transactions);
                } else {
                    throw new Error(data.message || 'Failed to load data');
                }
            } catch (e) {
                console.error('JSON parse error:', e);
                throw new Error('Invalid JSON response: ' + e.message);
            }
        })
        .catch(error => {
            console.error('Error loading recurring transactions:', error);
            document.getElementById('recurringList').innerHTML = 
                `<tr><td colspan="7" class="text-center text-danger">Lỗi: ${error.message}</td></tr>`;
            if (typeof SmartSpending !== 'undefined') {
                SmartSpending.showToast('Lỗi tải dữ liệu: ' + error.message, 'error');
            }
        });
}

// Update stats
function updateStats(recurring) {
    const active = recurring.filter(r => r.is_active == 1);
    const monthlyIncome = active
        .filter(r => r.type === 'income' && r.frequency === 'monthly')
        .reduce((sum, r) => sum + parseFloat(r.amount), 0);
    const monthlyExpense = active
        .filter(r => r.type === 'expense' && r.frequency === 'monthly')
        .reduce((sum, r) => sum + parseFloat(r.amount), 0);
    
    document.getElementById('totalCount').textContent = recurring.length;
    document.getElementById('activeCount').textContent = active.length;
    document.getElementById('monthlyIncome').textContent = formatCurrency(monthlyIncome);
    document.getElementById('monthlyExpense').textContent = formatCurrency(monthlyExpense);
}

// Render recurring transactions
function renderRecurring(recurring) {
    const tbody = document.getElementById('recurringList');
    
    if (recurring.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" class="text-center">Chưa có giao dịch định kỳ</td></tr>';
        return;
    }
    
    tbody.innerHTML = recurring.map(r => `
        <tr>
            <td>
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" ${r.is_active == 1 ? 'checked' : ''} 
                           onchange="toggleStatus(${r.id})">
                </div>
            </td>
            <td>
                <span class="badge ${r.type === 'income' ? 'bg-success' : 'bg-danger'}">
                    ${r.type === 'income' ? 'Thu' : 'Chi'}
                </span>
            </td>
            <td>
                <i class="fas ${r.icon}" style="color: ${r.color}"></i>
                ${r.category_name}
            </td>
            <td class="${r.type === 'income' ? 'text-success' : 'text-danger'}">
                ${r.type === 'income' ? '+' : '-'}${formatCurrency(r.amount)}
            </td>
            <td>${translateFrequency(r.frequency)}</td>
            <td>${formatDate(r.next_occurrence)}</td>
            <td>
                <button class="btn btn-sm btn-outline-primary" onclick="openEditModal(${r.id})">
                    <i class="fas fa-edit"></i>
                </button>
                <button class="btn btn-sm btn-outline-danger" onclick="deleteRecurring(${r.id})">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        </tr>
    `).join('');
}

// Open create modal
function openCreateModal() {
    document.getElementById('modalTitle').textContent = 'Thêm Giao dịch Định kỳ';
    document.getElementById('recurringForm').reset();
    document.getElementById('recurring_id').value = '';
    document.getElementById('type').value = 'expense';
    filterCategories();
}

// Open edit modal
function openEditModal(id) {
    fetch(`${BASE_URL}/recurring/api_get_all`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const recurring = data.data.recurring_transactions.find(r => r.id == id);
                if (recurring) {
                    document.getElementById('modalTitle').textContent = 'Chỉnh sửa Giao dịch Định kỳ';
                    document.getElementById('recurring_id').value = recurring.id;
                    document.getElementById('type').value = recurring.type;
                    filterCategories();
                    document.getElementById('category_id').value = recurring.category_id;
                    document.getElementById('amount').value = recurring.amount;
                    document.getElementById('description').value = recurring.description || '';
                    document.getElementById('frequency').value = recurring.frequency;
                    document.getElementById('start_date').value = recurring.start_date;
                    document.getElementById('end_date').value = recurring.end_date || '';
                    
                    new bootstrap.Modal(document.getElementById('recurringModal')).show();
                }
            }
        });
}

// Submit form
document.getElementById('recurringForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const id = document.getElementById('recurring_id').value;
    const url = id ? `${BASE_URL}/recurring/api_update` : `${BASE_URL}/recurring/api_create`;
    
    const data = {
        type: document.getElementById('type').value,
        category_id: document.getElementById('category_id').value,
        amount: document.getElementById('amount').value,
        description: document.getElementById('description').value,
        frequency: document.getElementById('frequency').value,
        start_date: document.getElementById('start_date').value,
        end_date: document.getElementById('end_date').value || null
    };
    
    if (id) data.id = id;
    
    fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': csrfToken
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            if (typeof SmartSpending !== 'undefined') {
                SmartSpending.showToast(data.message || 'Thành công!', 'success');
            }
            bootstrap.Modal.getInstance(document.getElementById('recurringModal')).hide();
            loadRecurringTransactions();
        } else {
            if (typeof SmartSpending !== 'undefined') {
                SmartSpending.showToast(data.message || 'Có lỗi xảy ra', 'error');
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
        if (typeof SmartSpending !== 'undefined') {
            SmartSpending.showToast('Lỗi: ' + error.message, 'error');
        }
    });
});

// Toggle status
function toggleStatus(id) {
    fetch(`${BASE_URL}/recurring/api_toggle`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': csrfToken
        },
        body: JSON.stringify({ id: id })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            if (typeof SmartSpending !== 'undefined') {
                SmartSpending.showToast('Cập nhật thành công!', 'success');
            }
            loadRecurringTransactions();
        } else {
            if (typeof SmartSpending !== 'undefined') {
                SmartSpending.showToast(data.message || 'Có lỗi xảy ra', 'error');
            }
        }
    });
}

// Delete recurring
function deleteRecurring(id) {
    if (!confirm('Bạn có chắc muốn xóa giao dịch định kỳ này?')) return;
    
    fetch(`${BASE_URL}/recurring/api_delete`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': csrfToken
        },
        body: JSON.stringify({ id: id })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            if (typeof SmartSpending !== 'undefined') {
                SmartSpending.showToast('Xóa thành công!', 'success');
            }
            loadRecurringTransactions();
        } else {
            if (typeof SmartSpending !== 'undefined') {
                SmartSpending.showToast(data.message || 'Có lỗi xảy ra', 'error');
            }
        }
    });
}

// Helper functions
function translateFrequency(freq) {
    const map = {
        daily: 'Hàng ngày',
        weekly: 'Hàng tuần',
        monthly: 'Hàng tháng',
        yearly: 'Hàng năm'
    };
    return map[freq] || freq;
}

function formatDate(dateStr) {
    if (!dateStr) return '';
    const date = new Date(dateStr);
    return date.toLocaleDateString('vi-VN');
}

function formatCurrency(amount) {
    return new Intl.NumberFormat('vi-VN', {
        style: 'currency',
        currency: 'VND'
    }).format(amount);
}
</script>

<?php $this->partial('footer'); ?>
