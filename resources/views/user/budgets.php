<?php

use App\Middleware\CsrfProtection;

$this->partial('header');
?>

<link rel="stylesheet" href="<?php echo BASE_URL; ?>/css/budgets.css">
<!-- Load Font Awesome (CDN) so category icon classes render correctly -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" integrity="sha512-pzw6XQm5QYh9n1Vb1s0Jp6K1M9GZrFv6e6i2Y1q7K1lY5qj7e5Z2e5x0Z6h1K7r5sV1q7Y1q7e5Z2e5x0Z6h1K7r5sV==" crossorigin="anonymous" referrerpolicy="no-referrer" />
<?php echo CsrfProtection::getTokenMeta(); ?>

<main class="container budgets-page py-4">
    <div class="budgets-top d-flex flex-column flex-md-row align-items-start gap-3 justify-content-between mb-4">
        <div class="d-flex align-items-center gap-3">
            <h2 class="mb-0"><i class="fas fa-wallet me-2"></i>Quản lý Ngân sách</h2>
        </div>

        <div class="d-flex gap-2 align-items-center">
            <select id="periodSelect" class="form-select form-select-sm">
                <option value="monthly">Tháng này</option>
                <option value="weekly">Tuần này</option>
                <option value="yearly">Năm này</option>
            </select>

            <button id="openCreateBudget" class="btn btn-primary btn-sm" type="button" aria-haspopup="dialog">
                <i class="fas fa-plus-circle me-1"></i> Tạo ngân sách
            </button>
        </div>
    </div>

    <section class="row gx-4">
        <aside class="col-lg-4 mb-4">

            <div class="card summary-card mb-3">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="small text-muted">Tổng ngân sách</div>
                            <h3 id="totalBudget" class="mb-0">0 ₫</h3>
                        </div>
                        <div class="icon-round bg-primary text-white"><i class="fas fa-wallet"></i></div>
                    </div>
                </div>
            </div>

            <div class="card summary-card mb-3">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="small text-muted">Đã chi tiêu</div>
                            <h4 id="totalSpent" class="mb-0 text-danger">0 ₫</h4>
                        </div>
                        <div class="icon-round bg-danger text-white"><i class="fas fa-arrow-down"></i></div>
                    </div>
                </div>
            </div>

            <div class="card summary-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="small text-muted">Còn lại</div>
                            <h4 id="totalRemaining" class="mb-0 text-success">0 ₫</h4>
                        </div>
                        <div class="icon-round bg-success text-white"><i class="fas fa-piggy-bank"></i></div>
                    </div>
                </div>
            </div>
        </aside>

        <div class="col-lg-8">
            <div class="card mb-3">
                <div class="card-body d-flex flex-column gap-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Ngân sách của bạn</h5>
                        <div class="small text-muted">Sắp xếp theo % đã dùng</div>
                    </div>

                    <div class="table-responsive">
                        <table id="budgetsTable" class="table align-middle budget-table mb-0">
                            <thead>
                                <tr>
                                    <th>Danh mục</th>
                                    <th class="text-end">Giới hạn</th>
                                    <th class="text-end">Đã chi</th>
                                    <th class="text-end">Còn lại</th>
                                    <th style="width:220px">Tiến độ</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody id="budgetsList"></tbody>
                        </table>
                    </div>

                    <div id="emptyState" class="text-center py-5" style="display:none;">
                        <i class="fas fa-wallet text-muted" style="font-size:3rem"></i>
                        <h5 class="mt-3 text-muted">Chưa có ngân sách nào</h5>
                        <p class="text-muted">Tạo ngân sách đầu tiên để bắt đầu quản lý chi tiêu</p>
                        <button id="emptyCreateBtn" class="btn btn-primary">Tạo ngân sách mới</button>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <h6 class="mb-3">Phân bổ theo danh mục</h6>
                    <div style="height:280px;">
                        <canvas id="budgetPie" aria-label="Biểu đồ phân bổ ngân sách"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Modal -->
    <div class="modal fade" id="createBudgetModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form id="budgetForm" novalidate>
                    <div class="modal-header">
                        <h5 class="modal-title" id="budgetModalTitle">Tạo ngân sách mới</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" id="budget_id" name="budget_id">

                        <div class="mb-3">
                            <label class="form-label">Danh mục *</label>
                            <div class="input-group">
                                <input type="text" id="budget_category_picker" class="form-control" placeholder="Chọn danh mục" readonly required aria-haspopup="dialog">
                                <input type="hidden" id="budget_category" name="category_id">
                                <button class="btn btn-outline-secondary" type="button" id="openCategoryChooser" title="Chọn danh mục">
                                    <i class="fas fa-list"></i>
                                </button>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Số tiền ngân sách *</label>
                            <input type="number" id="budget_amount" name="amount" class="form-control" required min="1" step="1000" placeholder="1,000,000">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Chu kỳ *</label>
                            <select id="budget_period" name="period" class="form-select" required>
                                <option value="monthly">Hàng tháng</option>
                                <option value="weekly">Hàng tuần</option>
                                <option value="yearly">Hàng năm</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Ngưỡng cảnh báo (%)</label>
                            <input type="number" id="budget_threshold" name="alert_threshold" class="form-control" value="80" min="1" max="100">
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

    <!-- Category chooser modal -->
    <div class="modal fade" id="categoryChooserModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Chọn danh mục</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="categoryList" class="category-chooser-list"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
    window.BASE_URL = "<?php echo BASE_URL; ?>";
</script>
<script src="<?php echo BASE_URL; ?>/js/budgets.js"></script>

<?php $this->partial('footer'); ?>