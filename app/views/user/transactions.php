<?php 
use App\Middleware\CsrfProtection;
$this->partial('header'); 
?>

<!-- Transactions Specific Styles -->
<link rel="stylesheet" href="<?php echo BASE_URL; ?>/user/transactions/transactions.css">
<?php echo CsrfProtection::getTokenMeta(); ?>

<div class="container-fluid" style="background: white; min-height: 100vh; padding: 40px 20px;">
    <div class="container" style="max-width: 1200px;">
        <!-- Header -->
        <div class="transactions-header">
            <h2 class="transactions-title">Giao dịch</h2>
            <button type="button" class="btn transactions-add-btn" data-bs-toggle="modal" data-bs-target="#addTransactionModal">
                <i class="fas fa-plus"></i>Thêm giao dịch
            </button>
        </div>

        <!-- Filters Card -->
        <div class="card transactions-filters-card">
            <div class="transactions-filters">
                <select class="form-select custom-select-arrow transactions-filter-select" id="rangeFilter">
                    <?php
                    for ($i = 2; $i >= 0; $i--) {
                        $monthValue = date('Y-m', strtotime("-$i months"));
                        $monthLabel = 'Tháng ' . date('n', strtotime("-$i months"));
                        $selected = (isset($current_range) && $current_range == $monthValue) ? 'selected' : '';
                        if ($i == 0 && !isset($current_range)) $selected = 'selected';
                        echo "<option value='$monthValue' $selected>$monthLabel</option>";
                    }
                    ?>
                </select>
                <select class="form-select custom-select-arrow transactions-filter-select transactions-filter-category" id="categoryFilter">
                    <option value="all">Tất cả danh mục</option>
                    <?php if (!empty($categories)): ?>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>" <?php echo (isset($current_category) && $current_category == $cat['id']) ? 'selected' : ''; ?>>
                                <?php echo $this->escape($cat['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
                <select class="form-select custom-select-arrow transactions-filter-select transactions-filter-type">
                    <option>Tất cả loại</option>
                    <option>Thu nhập</option>
                    <option>Chi tiêu</option>
                </select>
                <select class="form-select custom-select-arrow transactions-filter-select transactions-filter-sort">
                    <option>Mới nhất</option>
                    <option>Cũ nhất</option>
                </select>
            </div>
        </div>

        <!-- Transactions Table Card -->

        <div class="card transactions-table-card">
            <div class="table-responsive">
                <table class="table transactions-table" style="margin-bottom: 0;">
                    <thead>
                        <tr>
                            <th>Ngày</th>
                            <th>Danh mục</th>
                            <th>Mô tả</th>
                            <th style="text-align: right;">Số tiền</th>
                            <th style="text-align: center;">Loại</th>
                            <th style="text-align: center;">Hành động</th>
                        </tr>
                    </thead>
                <tbody>
                    <?php if (!empty($transactions)): ?>
                        <?php foreach ($transactions as $t): ?>
                            <tr>
                                <td class="transactions-date">
                                    <?php echo date('d M Y', strtotime($t['date'])); ?>
                                </td>
                                <td>
                                    <div class="transactions-category-wrapper">
                                        <div class="transactions-category-icon <?php echo ($t['amount'] < 0) ? 'expense' : 'income'; ?>">
                                            <i class="fas <?php echo ($t['amount'] < 0) ? 'fa-shopping-cart' : 'fa-wallet'; ?>"></i>
                                        </div>
                                        <span class="transactions-category-name">
                                            <?php echo $this->escape($t['category_name']); ?>
                                        </span>
                                    </div>
                                </td>
                                <td class="transactions-description">
                                    <?php echo $this->escape($t['description']); ?>
                                </td>
                                <td class="transactions-amount">
                                    <?php echo number_format(abs($t['amount']), 0, ',', '.'); ?> ₫
                                </td>
                                <td class="transactions-type-badge">
                                    <?php if ($t['amount'] > 0): ?>
                                        <span class="transactions-badge-income">Thu nhập</span>
                                    <?php else: ?>
                                        <span class="transactions-badge-expense">Chi tiêu</span>
                                    <?php endif; ?>
                                </td>
                                <td class="transactions-actions">
                                    <button type="button" 
                                            class="btn btn-sm btn-edit-transaction transactions-btn-edit" 
                                            data-id="<?php echo $t['id']; ?>"
                                            data-category="<?php echo $t['category_id']; ?>"
                                            data-amount="<?php echo abs($t['amount']); ?>"
                                            data-description="<?php echo htmlspecialchars($t['description'], ENT_QUOTES, 'UTF-8'); ?>"
                                            data-date="<?php echo $t['date']; ?>"
                                            data-bs-toggle="modal" 
                                            data-bs-target="#editTransactionModal" 
                                            title="Sửa">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-delete-transaction transactions-btn-delete" data-id="<?php echo $t['id']; ?>" title="Xóa">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="6" class="transactions-empty">Không có giao dịch để hiển thị.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
        <div class="transactions-pagination-wrapper">
            <div class="transactions-pagination-info">
                Hiển thị <?php echo count($transactions); ?> / <?php echo $total_transactions; ?> giao dịch
            </div>
            <div class="transactions-pagination">
                <?php if ($current_page > 1): ?>
                    <a href="<?php echo BASE_URL; ?>/transactions/index/<?php echo $current_range; ?>/<?php echo $current_category; ?>/<?php echo $current_page - 1; ?>" class="transactions-pagination-btn">Trước</a>
                <?php else: ?>
                    <button disabled class="transactions-pagination-btn">Trước</button>
                <?php endif; ?>
                
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <?php if ($i == $current_page): ?>
                        <button class="transactions-pagination-number active"><?php echo $i; ?></button>
                    <?php else: ?>
                        <a href="<?php echo BASE_URL; ?>/transactions/index/<?php echo $current_range; ?>/<?php echo $current_category; ?>/<?php echo $i; ?>" class="transactions-pagination-number"><?php echo $i; ?></a>
                    <?php endif; ?>
                <?php endfor; ?>
                
                <?php if ($current_page < $total_pages): ?>
                    <a href="<?php echo BASE_URL; ?>/transactions/index/<?php echo $current_range; ?>/<?php echo $current_category; ?>/<?php echo $current_page + 1; ?>" class="transactions-pagination-btn">Sau</a>
                <?php else: ?>
                    <button disabled class="transactions-pagination-btn">Sau</button>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
    </div>
</div>

<!-- Add Transaction Modal -->
<div class="modal fade" id="addTransactionModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Thêm giao dịch mới</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="addTransactionForm">
                <div class="modal-body transactions-modal-body">
                    <!-- 1. Date -->
                    <div class="mb-3">
                        <input type="date" name="transaction_date" class="form-control transactions-modal-input" required value="<?php echo date('Y-m-d'); ?>">
                    </div>
                    
                    <!-- 2. Description -->
                    <div class="mb-3">
                        <input type="text" name="description" class="form-control transactions-modal-input" required placeholder="Nhập mô tả">
                    </div>
                    
                    <!-- 3. Type (Income/Expense) -->
                    <div class="mb-3">
                        <select name="type" id="add_type" class="form-select custom-select-arrow transactions-modal-input" required>
                            <option value="">Chọn loại</option>
                            <option value="income">Thu nhập</option>
                            <option value="expense">Chi tiêu</option>
                        </select>
                    </div>
                    
                    <!-- 4. Category -->
                    <div class="mb-3">
                        <select name="category_id" id="add_category_id" class="form-select custom-select-arrow transactions-modal-input" required>
                            <option value="">Chọn danh mục</option>
                            <?php if (!empty($categories)): ?>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo $cat['id']; ?>" data-type="<?php echo $cat['type']; ?>">
                                        <?php echo $this->escape($cat['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                    
                    <!-- 5. Amount -->
                    <div class="mb-3">
                        <input type="number" name="amount" class="form-control transactions-modal-input amount-input" required min="1" step="1000" placeholder="Nhập số tiền" id="add_amount">
                        <div class="form-text">Ví dụ: 50000 (50 nghìn đồng)</div>
                    </div>
                </div>
                <div class="modal-footer transactions-modal-footer">
                    <button type="button" class="btn transactions-modal-btn-cancel" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn transactions-modal-btn-submit">Thêm</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Transaction Modal -->
<div class="modal fade" id="editTransactionModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Sửa giao dịch</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="editTransactionForm">
                <input type="hidden" name="id" id="edit_transaction_id">
                <div class="modal-body transactions-modal-body">
                    <!-- 1. Date -->
                    <div class="mb-3">
                        <input type="date" name="transaction_date" id="edit_date" class="form-control transactions-modal-input" required>
                    </div>
                    
                    <!-- 2. Description -->
                    <div class="mb-3">
                        <input type="text" name="description" id="edit_description" class="form-control transactions-modal-input" required placeholder="Nhập mô tả">
                    </div>
                    
                    <!-- 3. Type (Income/Expense) -->
                    <div class="mb-3">
                        <select name="type" id="edit_type" class="form-select custom-select-arrow transactions-modal-input" required>
                            <option value="">Chọn loại</option>
                            <option value="income">Thu nhập</option>
                            <option value="expense">Chi tiêu</option>
                        </select>
                    </div>
                    
                    <!-- 4. Category -->
                    <div class="mb-3">
                        <select name="category_id" id="edit_category_id" class="form-select custom-select-arrow transactions-modal-input" required>
                            <option value="">Chọn danh mục</option>
                            <?php if (!empty($categories)): ?>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo $cat['id']; ?>" data-type="<?php echo $cat['type']; ?>">
                                        <?php echo $this->escape($cat['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                    
                    <!-- 5. Amount -->
                    <div class="mb-3">
                        <input type="number" name="amount" id="edit_amount" class="form-control transactions-modal-input amount-input" required min="0" step="1000" placeholder="0">
                    </div>
                </div>
                <div class="modal-footer transactions-modal-footer">
                    <button type="button" class="btn transactions-modal-btn-cancel" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn transactions-modal-btn-submit">Cập nhật</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Pass PHP variables to JavaScript
    window.BASE_URL = "<?php echo BASE_URL; ?>";
</script>
<script src="<?php echo BASE_URL; ?>/user/transactions/transactions.js"></script>

<?php $this->partial('footer'); ?>
