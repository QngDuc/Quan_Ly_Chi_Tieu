<?php
use App\Middleware\CsrfProtection;
$this->partial('header');
?>

<!-- <link rel="stylesheet" href="<?php echo BASE_URL; ?>/css/budgets.css"> -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
<?php echo CsrfProtection::getTokenMeta(); ?>

<main class="container budgets-page py-4">
    <div class="budgets-header mb-4">
        <div class="row align-items-center g-3">
            <div class="col-md-6">
                <div class="d-flex align-items-center gap-3">
                    <div class="header-icon-box">
                        <i class="fas fa-wallet"></i>
                    </div>
                    <div>
                        <h2 class="page-title">Quản lý Ngân sách</h2>
                        <p class="page-subtitle">Kiểm soát chi tiêu, tiết kiệm hiệu quả</p>
                    </div>
                </div>
            </div>
            
        </div>
    </div>

    <!-- Top summary small cards -->
    <div class="row g-3 mb-4 align-items-start">
        <div class="col-md-3">
            <div class="card summary-small">
                <div class="card-body">
                    <p class="small text-muted mb-1">Tổng ngân sách</p>
                    <h4 id="totalBudget" class="mb-0 fw-bold">0 ₫</h4>
                    <p class="small text-muted mb-0">Tổng hạn mức hiện tại</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card summary-small">
                <div class="card-body">
                    <p class="small text-muted mb-1">Đã chi</p>
                    <h4 id="totalSpent" class="mb-0 fw-bold">0 ₫</h4>
                    <p class="small text-muted mb-0">Số tiền đã chi trong kỳ</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card summary-small">
                <div class="card-body">
                    <p class="small text-muted mb-1">Còn lại</p>
                    <h4 id="totalRemaining" class="mb-0 fw-bold">0 ₫</h4>
                    <p class="small text-muted mb-0">Số tiền còn lại trong kỳ</p>
                </div>
            </div>
        </div>
        <div class="col-md-3 d-flex justify-content-end">
            <div>
                <button id="openCreateBudget" class="btn btn-primary custom-btn-add">
                    <i class="fas fa-plus"></i>
                    <span>Thêm Ngân sách</span>
                </button>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-12">
            <div class="card list-card border-0 shadow-sm h-100">
                <div class="card-header bg-transparent border-0 pt-4 pb-2 px-4 d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold text-dark mb-0">Danh sách chi tiết</h5>
                    <div class="badge bg-light text-muted border fw-normal px-3 py-2 rounded-pill">
                        <i class="fas fa-sort-amount-down me-1"></i> % Sử dụng
                    </div>
                </div>
                <div class="card-body px-0">
                    <!-- Smart Budget summary (50/30/20) shown above the table -->
                    <div id="smartAllocation" class="px-4 pb-3" style="border-bottom:1px solid #f1f3f5;">
                        <!-- Filled by JS -->
                        <div class="d-flex justify-content-between align-items-center py-2">
                            <div class="d-flex gap-3 align-items-center" id="smartAllocationDetails">
                                <!-- percent boxes inserted here -->
                            </div>
                            <div>
                                <button id="editSmartRatiosBtn" class="btn btn-outline-primary btn-sm">Chỉnh tỷ lệ</button>
                            </div>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table id="budgetsTable" class="table table-hover align-middle custom-table mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="ps-4" style="width: 35%;">Danh mục</th>
                                    <th class="text-end" style="width: 20%;">Còn lại</th>
                                    <th style="width: 35%;">Tiến độ</th>
                                    <th class="text-end pe-4" style="width: 10%;"></th>
                                </tr>
                            </thead>
                            <tbody id="budgetsList">
                                </tbody>
                        </table>
                            </div>

                            <!-- Nút xem tất cả ngân sách -->
                            <div class="d-flex justify-content-center my-3">
                                <button id="budgetsViewAllBtn" class="btn btn-outline-primary" style="display:none; min-width:120px;">Xem tất cả</button>
                            </div>
                            <div id="budgetsPagination" class="d-flex justify-content-center my-3"></div>
                    </div>

                    <div id="emptyState" class="text-center py-5" style="display:none;">
                        <div class="empty-icon-wrapper mb-3">
                            <i class="fas fa-piggy-bank"></i>
                        </div>
                        <h6 class="text-dark fw-bold">Chưa có ngân sách nào</h6>
                        <p class="text-muted small">Hãy tạo ngân sách để bắt đầu theo dõi.</p>
                        <button onclick="(function(){var e=document.getElementById('openCreateBudget'); if(e) e.click();})()" class="btn btn-outline-primary rounded-pill px-4">
                            Tạo ngay
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Insights / charts -->
    <div class="charts-row mt-1" style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">
        <div class="card chart-card border-0 shadow-sm mb-4">
            <div class="card-body p-4">
                <h6 class="fw-bold text-dark mb-4">Ngân sách vs Chi tiêu theo thời gian</h6>
                <div class="chart-container">
                    <canvas id="budgetTrend"></canvas>
                </div>
            </div>
        </div>
        <div class="card chart-card border-0 shadow-sm mb-4">
            <div class="card-body p-4">
                <h6 class="fw-bold text-dark mb-4">Phân bổ ngân sách theo danh mục</h6>
                <div class="chart-container">
                    <canvas id="budgetPie"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="createBudgetModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <form id="budgetForm" novalidate>
                    <div class="modal-header border-0 pb-0 px-4 pt-4">
                        <h5 class="modal-title fw-bold" id="budgetModalTitle">Thiết lập ngân sách</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body px-4 pt-3 pb-4">
                        <input type="hidden" id="budget_id" name="budget_id">
                        
                        <div class="mb-4">
                            <label class="form-label fw-bold small text-muted">DANH MỤC</label>
                            <div class="input-group input-group-lg cursor-pointer" onclick="(function(){var e=document.getElementById('openCategoryChooser'); if(e) e.click();})()">
                                <input type="text" id="budget_category_picker" class="form-control bg-light border-0 rounded-3 ps-3" placeholder="Chọn danh mục..." readonly style="cursor: pointer;">
                                <input type="hidden" id="budget_category" name="category_id">
                                <span class="input-group-text bg-light border-0 text-muted rounded-3 ms-1"><i class="fas fa-chevron-right"></i></span>
                            </div>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-md-7">
                                <label class="form-label fw-bold small text-muted">SỐ TIỀN</label>
                                <div class="input-group input-group-lg">
                                    <input type="text" id="budget_amount_display" class="form-control fw-bold border-0 bg-light rounded-start-3" placeholder="0" oninput="formatInputMoney(this)">
                                    <input type="hidden" id="budget_amount" name="amount">
                                    <span class="input-group-text border-0 bg-light rounded-end-3 text-muted">₫</span>
                                </div>
                            </div>
                            <div class="col-md-5">
                                <label class="form-label fw-bold small text-muted">CHU KỲ</label>
                                <select id="budget_period" name="period" class="form-select form-select-lg border-0 bg-light rounded-3">
                                    <option value="monthly">Tháng này</option>
                                    <option value="weekly">Tuần này</option>
                                    <option value="yearly">Năm này</option>
                                </select>
                            </div>
                        </div>

                        <div class="p-3 bg-light rounded-3">
                            <label class="form-label fw-bold small text-muted d-flex justify-content-between mb-2">
                                <span>CẢNH BÁO KHI ĐẠT</span>
                                <span id="thresholdValue" class="badge bg-warning text-dark">80%</span>
                            </label>
                            <input type="range" class="form-range" id="budget_threshold" name="alert_threshold" min="50" max="100" step="5" value="80" oninput="document.getElementById('thresholdValue').innerText = this.value + '%'">
                        </div>
                    </div>
                    <div class="modal-footer border-0 px-4 pb-4 pt-0">
                        <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Hủy</button>
                        <button type="submit" class="btn btn-primary rounded-pill px-4">Lưu Ngân Sách</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="categoryChooserModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-scrollable">
            <div class="modal-content rounded-4 border-0">
                <div class="modal-header border-bottom">
                    <h5 class="modal-title fw-bold">Chọn danh mục</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-0">
                    <div id="categoryList" class="list-group list-group-flush"></div>
                </div>
            </div>
        </div>
    </div>
</main>

<div class="modal fade" id="budgetWarningModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-warning bg-opacity-10 border-bottom-0">
                <h5 class="modal-title text-warning fw-bold">
                    <i class="fas fa-exclamation-triangle me-2"></i>Cảnh báo vượt ngân sách
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body py-4">
                <p id="budgetWarningMessage" class="mb-0 text-dark">
                    </p>
            </div>
            <div class="modal-footer border-top-0 pt-0">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Hủy bỏ</button>
                <button type="button" class="btn btn-warning text-dark fw-bold px-4" id="confirmOverBudgetBtn">
                    Tiếp tục thanh toán
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    window.BASE_URL = "<?php echo BASE_URL; ?>";
    function formatInputMoney(input) {
        let value = input.value.replace(/\D/g, '');
        document.getElementById('budget_amount').value = value;
        input.value = new Intl.NumberFormat('vi-VN').format(value);
    }
</script>
<!-- Load Chart.js for budgets charts -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>if(typeof Chart === 'undefined'){var s=document.createElement('script');s.src = BASE_URL + '/vendor/chart.min.js';document.head.appendChild(s);}</script>
<?php $this->partial('footer'); ?>

<!-- Small modal for editing Smart Budget ratios -->
<div class="modal fade" id="smartBudgetModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-0 pb-0 px-4 pt-4">
                <h5 class="modal-title fw-bold">Chỉnh tỷ lệ Ngân sách 50/30/20</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body px-4 pt-2 pb-3">
                <div class="mb-3">
                    <label class="form-label small text-muted">Tổng thu nhập tháng (tùy chọn)</label>
                    <input type="number" id="smartIncome" class="form-control" placeholder="Nhập thu nhập (không bắt buộc)">
                </div>

                <div class="mb-3 d-flex align-items-center gap-3">
                    <div style="flex:1">
                        <small class="d-block text-muted">Needs</small>
                        <input type="number" id="needsInput" min="0" max="100" value="50" class="form-control d-inline-block" style="width:120px;">
                    </div>
                    <div class="text-end">
                        <small id="needsAmount">0₫</small>
                    </div>
                </div>

                <div class="mb-3 d-flex align-items-center gap-3">
                    <div style="flex:1">
                        <small class="d-block text-muted">Wants</small>
                        <input type="number" id="wantsInput" min="0" max="100" value="30" class="form-control d-inline-block" style="width:120px;">
                    </div>
                    <div class="text-end">
                        <small id="wantsAmount">0₫</small>
                    </div>
                </div>

                <div class="mb-3 d-flex align-items-center gap-3">
                    <div style="flex:1">
                        <small class="d-block text-muted">Savings</small>
                        <input type="number" id="savingsInput" min="0" max="100" value="20" class="form-control d-inline-block" style="width:120px;">
                    </div>
                    <div class="text-end">
                        <small id="savingsAmount">0₫</small>
                    </div>
                </div>
                <div class="text-center mt-3">
                    <canvas id="smartBudgetChart" width="300" height="160" style="max-width:100%;"></canvas>
                </div>
            </div>
            <div class="modal-footer border-0 px-4 pb-4 pt-0">
                <button type="button" id="resetRatiosBtn" class="btn btn-secondary rounded-pill px-4">Khôi phục mặc định</button>
                <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Hủy</button>
                <button type="button" id="saveRatiosBtn" class="btn btn-primary rounded-pill px-4">Lưu tỷ lệ</button>
            </div>
        </div>
    </div>
</div>

<script src="<?php echo BASE_URL; ?>/js/smart-budget.js"></script>