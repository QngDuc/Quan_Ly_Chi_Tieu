<?php 
use App\Middleware\CsrfProtection;
$this->partial('header'); 
?>

<!-- Budgets Specific Styles -->
<link rel="stylesheet" href="<?php echo BASE_URL; ?>/user/budgets/budgets.css">
<?php echo CsrfProtection::getTokenMeta(); ?>

<section>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3>Quản lý Ngân sách - Phương pháp Hũ</h3>
        <div class="d-flex gap-2">
            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#createJarModal">
                <i class="bi bi-plus-circle"></i> Tạo hũ mới
            </button>
        </div>
    </div>

    <!-- Income Summary Card -->
    <div class="income-card mb-4">
        <div class="income-header">
            <i class="bi bi-cash-coin income-icon"></i>
            <div>
                <div class="income-label">Tổng tỷ lệ đã phân bổ</div>
                <div class="income-amount" id="totalPercentage">
                    0%
                </div>
            </div>
        </div>
    </div>

    <!-- Jars Grid -->
    <div class="jars-grid mb-4" id="jarsGrid">
        <!-- Jars will be loaded dynamically -->
    </div>

    <!-- Empty State -->
    <div class="text-center py-5" id="emptyState" style="display: none;">
        <i class="bi bi-jar text-muted" style="font-size: 4rem;"></i>
        <h5 class="mt-3 text-muted">Chưa có hũ nào</h5>
        <p class="text-muted">Tạo hũ đầu tiên để bắt đầu quản lý ngân sách</p>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createJarModal">
            <i class="bi bi-plus-circle"></i> Tạo hũ mới
        </button>
    </div>
</section>

<!-- Create/Edit Jar Modal -->
<div class="modal fade" id="createJarModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="jarModalTitle">Tạo hũ mới</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="jarForm">
                    <input type="hidden" id="jar_id" name="jar_id">
                    
                    <div class="mb-3">
                        <label class="form-label">Tên hũ *</label>
                        <input type="text" class="form-control" id="jar_name" name="name" required 
                               placeholder="VD: Thiết yếu, Tiết kiệm, Du lịch...">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Phần trăm phân bổ (%) *</label>
                        <input type="number" class="form-control" id="jar_percentage" name="percentage" 
                               required min="0" max="100" step="1" placeholder="10">
                        <small class="text-muted">Tổng tất cả các hũ không được vượt quá 100%</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Màu sắc</label>
                        <input type="color" class="form-control form-control-color" id="jar_color" 
                               name="color" value="#6c757d">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Icon (Font Awesome)</label>
                        <input type="text" class="form-control" id="jar_icon" name="icon" 
                               placeholder="fa-home, fa-piggy-bank, fa-graduation-cap...">
                        <small class="text-muted">Nhập tên class icon từ Font Awesome hoặc Bootstrap Icons</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Mô tả</label>
                        <textarea class="form-control" id="jar_description" name="description" 
                                  rows="3" placeholder="Mô tả mục đích của hũ này..."></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Danh mục con (mỗi dòng một mục)</label>
                        <textarea class="form-control" id="jar_categories" name="categories" 
                                  rows="4" placeholder="Ăn uống&#10;Tiền nhà&#10;Điện nước"></textarea>
                        <small class="text-muted">Nhập các danh mục chi tiết cho hũ này</small>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                <button type="button" class="btn btn-primary" id="saveJarBtn" onclick="saveJar()">Lưu</button>
            </div>
        </div>
    </div>
</div>

<script>
window.BASE_URL = "<?php echo BASE_URL; ?>";

document.addEventListener('DOMContentLoaded', function() {
    loadJars();
});

// Load all jars
function loadJars() {
    fetch(`${BASE_URL}/budgets/api_get_jars`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                renderJars(data.data.jars);
                document.getElementById('totalPercentage').textContent = data.data.total_percentage + '%';
            }
        })
        .catch(error => console.error('Error:', error));
}

// Render jars
function renderJars(jars) {
    const grid = document.getElementById('jarsGrid');
    const emptyState = document.getElementById('emptyState');
    
    if (!jars || jars.length === 0) {
        grid.innerHTML = '';
        emptyState.style.display = 'block';
        return;
    }
    
    emptyState.style.display = 'none';
    
    grid.innerHTML = jars.map(jar => `
        <div class="jar-card">
            <div class="jar-header">
                <div class="jar-icon" style="background: ${jar.color}">
                    <i class="${jar.icon || 'bi bi-jar'}"></i>
                </div>
                <div class="jar-info">
                    <div class="jar-name">${jar.name}</div>
                    <div class="jar-percentage">${jar.percentage}%</div>
                </div>
            </div>
            <div class="jar-body">
                ${jar.description ? `<p class="jar-description">${jar.description}</p>` : ''}
                ${jar.categories && jar.categories.length > 0 ? `
                    <ul class="jar-categories">
                        ${jar.categories.map(cat => `<li>${cat.category_name}</li>`).join('')}
                    </ul>
                ` : ''}
            </div>
            <div class="jar-actions">
                <button class="btn btn-sm btn-outline-primary" onclick="editJar(${jar.id})">
                    <i class="bi bi-pencil"></i> Sửa
                </button>
                <button class="btn btn-sm btn-outline-danger" onclick="deleteJar(${jar.id})">
                    <i class="bi bi-trash"></i> Xóa
                </button>
            </div>
        </div>
    `).join('');
}

// Save jar (create or update)
function saveJar() {
    const form = document.getElementById('jarForm');
    const formData = new FormData(form);
    const jarId = document.getElementById('jar_id').value;
    
    const data = {
        name: formData.get('name'),
        percentage: parseFloat(formData.get('percentage')),
        color: formData.get('color'),
        icon: formData.get('icon'),
        description: formData.get('description'),
        categories: formData.get('categories') ? formData.get('categories').split('\n').filter(c => c.trim()) : []
    };
    
    const url = jarId ? `${BASE_URL}/budgets/api_update_jar/${jarId}` : `${BASE_URL}/budgets/api_create_jar`;
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
            SmartSpending.showToast(jarId ? 'Cập nhật hũ thành công!' : 'Tạo hũ thành công!', 'success');
            bootstrap.Modal.getInstance(document.getElementById('createJarModal')).hide();
            form.reset();
            loadJars();
        } else {
            SmartSpending.showToast(data.message || 'Có lỗi xảy ra', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        SmartSpending.showToast('Có lỗi xảy ra', 'error');
    });
}

// Edit jar
function editJar(jarId) {
    fetch(`${BASE_URL}/budgets/api_get_jars`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const jar = data.data.jars.find(j => j.id == jarId);
                if (jar) {
                    document.getElementById('jarModalTitle').textContent = 'Chỉnh sửa hũ';
                    document.getElementById('jar_id').value = jar.id;
                    document.getElementById('jar_name').value = jar.name;
                    document.getElementById('jar_percentage').value = jar.percentage;
                    document.getElementById('jar_color').value = jar.color;
                    document.getElementById('jar_icon').value = jar.icon || '';
                    document.getElementById('jar_description').value = jar.description || '';
                    document.getElementById('jar_categories').value = jar.categories ? 
                        jar.categories.map(c => c.category_name).join('\n') : '';
                    
                    const modal = new bootstrap.Modal(document.getElementById('createJarModal'));
                    modal.show();
                }
            }
        });
}

// Delete jar
function deleteJar(jarId) {
    if (!confirm('Bạn có chắc muốn xóa hũ này?')) return;
    
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    
    fetch(`${BASE_URL}/budgets/api_delete_jar/${jarId}`, {
        method: 'POST',
        headers: {
            'X-CSRF-Token': csrfToken
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            SmartSpending.showToast('Xóa hũ thành công!', 'success');
            loadJars();
        } else {
            SmartSpending.showToast(data.message || 'Có lỗi xảy ra', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        SmartSpending.showToast('Có lỗi xảy ra', 'error');
    });
}

// Reset form when modal is hidden
document.getElementById('createJarModal').addEventListener('hidden.bs.modal', function() {
    document.getElementById('jarForm').reset();
    document.getElementById('jar_id').value = '';
    document.getElementById('jarModalTitle').textContent = 'Tạo hũ mới';
});
</script>

<?php $this->partial('footer'); ?>
