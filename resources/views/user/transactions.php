<?php 
use App\Middleware\CsrfProtection;
$this->partial('header'); 
?>

<!-- Transactions Specific Styles: header will include streamed CSS -->
<?php echo CsrfProtection::getTokenMeta(); ?>

<section>
    <!-- Header -->
    <div class="transactions-header">
        <h3><i class="fas fa-exchange-alt me-2"></i>Giao dịch</h3>
        <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addTransactionModal">
            <i class="fas fa-plus me-2"></i>Thêm giao dịch
        </button>
    </div>

    <!-- Summary Cards -->
    <div class="summary-cards">
        <div class="summary-card">
            <div class="summary-card-label">Tổng thu</div>
            <h4 class="summary-card-value income" id="totalIncome">0 ₫</h4>
        </div>
        <div class="summary-card">
            <div class="summary-card-label">Tổng chi</div>
            <h4 class="summary-card-value expense" id="totalExpense">0 ₫</h4>
        </div>
        <div class="summary-card">
            <div class="summary-card-label">Số dư</div>
            <h4 class="summary-card-value balance" id="balance">0 ₫</h4>
        </div>
    </div>

    <!-- Filters -->
    <div class="filters-section">
        <div class="filters-row">
            <div class="filter-group">
                <label>Tháng</label>
                <select class="form-select" id="rangeFilter">
                    <?php
                    for ($i = 2; $i >= 0; $i--) {
                        $monthValue = date('Y-m', strtotime("-$i months"));
                        $monthLabel = 'Tháng ' . date('n/Y', strtotime("-$i months"));
                        $selected = (isset($current_range) && $current_range == $monthValue) ? 'selected' : '';
                        if ($i == 0 && !isset($current_range)) $selected = 'selected';
                        echo "<option value='$monthValue' $selected>$monthLabel</option>";
                    }
                    ?>
                </select>
            </div>
            <div class="filter-group">
                <label>Danh mục</label>
                <select class="form-select" id="categoryFilter">
                    <option value="all">Tất cả danh mục</option>
                    <?php if (!empty($categories)): ?>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>" <?php echo (isset($current_category) && $current_category == $cat['id']) ? 'selected' : ''; ?>>
                                <?php echo $this->escape($cat['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>
            <div class="filter-group">
                <label>Loại</label>
                <select class="form-select" id="typeFilter">
                    <option value="all">Tất cả</option>
                    <option value="income">Thu nhập</option>
                    <option value="expense">Chi tiêu</option>
                </select>
            </div>
            <div class="filter-group">
                <label>Sắp xếp</label>
                <select class="form-select" id="sortFilter">
                    <option value="desc">Mới nhất</option>
                    <option value="asc">Cũ nhất</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Transactions List -->
    <div class="transactions-list" id="transactionsList">
        <?php if (!empty($transactions)): ?>
            <?php foreach ($transactions as $t): ?>
                <div class="transaction-item">
                    <div class="transaction-icon <?php echo ($t['type'] == 'income') ? 'income' : 'expense'; ?>">
                        <i class="fas <?php echo isset($t['icon']) ? $t['icon'] : (($t['type'] == 'income') ? 'fa-arrow-up' : 'fa-arrow-down'); ?>"></i>
                    </div>
                    <div class="transaction-info">
                        <div class="transaction-category"><?php echo $this->escape($t['category_name']); ?></div>
                        <div class="transaction-description"><?php echo $this->escape($t['description']); ?></div>
                    </div>
                    <div class="transaction-date"><?php echo date('d/m/Y', strtotime($t['date'])); ?></div>
                    <div class="transaction-amount <?php echo ($t['type'] == 'income') ? 'income' : 'expense'; ?>">
                        <?php echo ($t['type'] == 'income') ? '+' : '-'; ?><?php echo number_format($t['amount'], 0, ',', '.'); ?> ₫
                    </div>
                    <div class="transaction-actions">
                        <button type="button" 
                                class="btn btn-sm btn-outline-primary btn-edit-transaction" 
                                data-id="<?php echo $t['id']; ?>"
                                data-category="<?php echo $t['category_id']; ?>"
                                data-amount="<?php echo $t['amount']; ?>"
                                data-type="<?php echo $t['type']; ?>"
                                data-description="<?php echo htmlspecialchars($t['description'], ENT_QUOTES, 'UTF-8'); ?>"
                                data-date="<?php echo $t['date']; ?>"
                                data-bs-toggle="modal" 
                                data-bs-target="#editTransactionModal" 
                                title="Sửa">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-danger btn-delete-transaction" data-id="<?php echo $t['id']; ?>" title="Xóa">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-receipt"></i>
                <h5>Chưa có giao dịch nào</h5>
                <p>Thêm giao dịch đầu tiên để bắt đầu theo dõi chi tiêu của bạn</p>
            </div>
        <?php endif; ?>
            </table>
        </div>
        
        <!-- Pagination -->
    </div>

    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
    <div class="pagination-wrapper">
        <nav>
            <ul class="pagination mb-0">
                <li class="page-item <?php echo ($current_page <= 1) ? 'disabled' : ''; ?>">
                    <a class="page-link" href="<?php echo BASE_URL; ?>/transactions/index/<?php echo $current_range; ?>/<?php echo $current_category; ?>/<?php echo $current_page - 1; ?>">
                        <i class="fas fa-chevron-left"></i>
                    </a>
                </li>
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <li class="page-item <?php echo ($i == $current_page) ? 'active' : ''; ?>">
                        <a class="page-link" href="<?php echo BASE_URL; ?>/transactions/index/<?php echo $current_range; ?>/<?php echo $current_category; ?>/<?php echo $i; ?>"><?php echo $i; ?></a>
                    </li>
                <?php endfor; ?>
                <li class="page-item <?php echo ($current_page >= $total_pages) ? 'disabled' : ''; ?>">
                    <a class="page-link" href="<?php echo BASE_URL; ?>/transactions/index/<?php echo $current_range; ?>/<?php echo $current_category; ?>/<?php echo $current_page + 1; ?>">
                        <i class="fas fa-chevron-right"></i>
                    </a>
                </li>
            </ul>
        </nav>
    </div>
    <?php endif; ?>
</section>

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
<!-- Page-specific JS loaded by footer from resources/js/transactions.js -->

<?php $this->partial('footer'); ?>
