<?php 
use App\Middleware\CsrfProtection;
$this->partial('header'); 
?>

<link rel="stylesheet" href="<?php echo BASE_URL; ?>/admin/dashboard.css">
<?php echo CsrfProtection::getTokenMeta(); ?>

<section>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3><i class="fas fa-tags me-2"></i>Quản lý Danh mục Gốc</h3>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#categoryModal" onclick="openCreateModal()">
            <i class="fas fa-plus-circle me-2"></i>Thêm Danh mục
        </button>
    </div>

    <!-- Stats Cards -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <h5 class="text-success"><i class="fas fa-arrow-up me-2"></i>Thu nhập</h5>
                    <h3 id="incomeCount">0</h3>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <h5 class="text-danger"><i class="fas fa-arrow-down me-2"></i>Chi tiêu</h5>
                    <h3 id="expenseCount">0</h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Categories Tables -->
    <div class="row">
        <!-- Income Categories -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="fas fa-plus-circle me-2"></i>Danh mục Thu nhập</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Icon</th>
                                    <th>Tên</th>
                                    <th>Màu</th>
                                    <th>Thao tác</th>
                                </tr>
                            </thead>
                            <tbody id="incomeCategories">
                                <!-- Loaded via JS -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Expense Categories -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0"><i class="fas fa-minus-circle me-2"></i>Danh mục Chi tiêu</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Icon</th>
                                    <th>Tên</th>
                                    <th>Màu</th>
                                    <th>Thao tác</th>
                                </tr>
                            </thead>
                            <tbody id="expenseCategories">
                                <!-- Loaded via JS -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Category Modal -->
<div class="modal fade" id="categoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">Thêm Danh mục</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="categoryForm">
                    <input type="hidden" id="category_id">
                    
                    <div class="mb-3">
                        <label class="form-label">Tên danh mục *</label>
                        <input type="text" class="form-control" id="category_name" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Loại *</label>
                        <select class="form-select" id="category_type" required>
                            <option value="income">Thu nhập</option>
                            <option value="expense">Chi tiêu</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Màu sắc</label>
                        <input type="color" class="form-control form-control-color" id="category_color" value="#3498db">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Icon (Font Awesome)</label>
                        <input type="text" class="form-control" id="category_icon" placeholder="fa-circle">
                        <small class="text-muted">VD: fa-shopping-cart, fa-home, fa-car...</small>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                <button type="button" class="btn btn-primary" onclick="saveCategory()">Lưu</button>
            </div>
        </div>
    </div>
</div>

<script>
window.BASE_URL = "<?php echo BASE_URL; ?>";

document.addEventListener('DOMContentLoaded', function() {
    loadCategories();
});

function loadCategories() {
    fetch(`${BASE_URL}/admin/categories/api_get_categories`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                renderCategories(data.data.categories);
            }
        })
        .catch(error => console.error('Error:', error));
}

function renderCategories(categories) {
    const income = categories.filter(c => c.type === 'income');
    const expense = categories.filter(c => c.type === 'expense');
    
    document.getElementById('incomeCount').textContent = income.length;
    document.getElementById('expenseCount').textContent = expense.length;
    
    const incomeTable = document.getElementById('incomeCategories');
    const expenseTable = document.getElementById('expenseCategories');
    
    incomeTable.innerHTML = income.map(cat => renderRow(cat)).join('');
    expenseTable.innerHTML = expense.map(cat => renderRow(cat)).join('');
}

function renderRow(category) {
    return `
        <tr>
            <td><i class="fas ${category.icon}" style="color: ${category.color}"></i></td>
            <td>${category.name}</td>
            <td><span class="badge" style="background-color: ${category.color}">${category.color}</span></td>
            <td>
                <button class="btn btn-sm btn-outline-primary" onclick="editCategory(${category.id})">
                    <i class="fas fa-edit"></i>
                </button>
                <button class="btn btn-sm btn-outline-danger" onclick="deleteCategory(${category.id})">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        </tr>
    `;
}

function openCreateModal() {
    document.getElementById('modalTitle').textContent = 'Thêm Danh mục';
    document.getElementById('categoryForm').reset();
    document.getElementById('category_id').value = '';
    document.getElementById('category_color').value = '#3498db';
}

function saveCategory() {
    const id = document.getElementById('category_id').value;
    const data = {
        name: document.getElementById('category_name').value,
        type: document.getElementById('category_type').value,
        color: document.getElementById('category_color').value,
        icon: document.getElementById('category_icon').value || 'fa-circle'
    };
    
    if (!data.name) {
        SmartSpending.showToast('Vui lòng nhập tên danh mục', 'error');
        return;
    }
    
    const url = id ? `${BASE_URL}/admin/categories/api_update/${id}` : `${BASE_URL}/admin/categories/api_create`;
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    
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
            SmartSpending.showToast(id ? 'Cập nhật thành công!' : 'Tạo danh mục thành công!', 'success');
            bootstrap.Modal.getInstance(document.getElementById('categoryModal')).hide();
            loadCategories();
        } else {
            SmartSpending.showToast(data.message || 'Có lỗi xảy ra', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        SmartSpending.showToast('Có lỗi xảy ra', 'error');
    });
}

function editCategory(id) {
    fetch(`${BASE_URL}/admin/categories/api_get_categories`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const category = data.data.categories.find(c => c.id == id);
                if (category) {
                    document.getElementById('modalTitle').textContent = 'Chỉnh sửa Danh mục';
                    document.getElementById('category_id').value = category.id;
                    document.getElementById('category_name').value = category.name;
                    document.getElementById('category_type').value = category.type;
                    document.getElementById('category_color').value = category.color;
                    document.getElementById('category_icon').value = category.icon;
                    
                    const modal = new bootstrap.Modal(document.getElementById('categoryModal'));
                    modal.show();
                }
            }
        });
}

function deleteCategory(id) {
    if (!confirm('Bạn có chắc muốn xóa danh mục này?')) return;
    
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    
    fetch(`${BASE_URL}/admin/categories/api_delete/${id}`, {
        method: 'POST',
        headers: {
            'X-CSRF-Token': csrfToken
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            SmartSpending.showToast('Xóa danh mục thành công!', 'success');
            loadCategories();
        } else {
            SmartSpending.showToast(data.message || 'Có lỗi xảy ra', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        SmartSpending.showToast('Có lỗi xảy ra', 'error');
    });
}
</script>

<?php $this->partial('footer'); ?>
