<?php

use App\Middleware\CsrfProtection;

$this->partial('header');
?>

<link rel="stylesheet" href="<?php echo BASE_URL; ?>/css/budgets.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />

<style>
    .jar-card {
        transition: all 0.3s ease;
        border: 1px solid rgba(0, 0, 0, 0.05);
        border-radius: 16px;
        background: #fff;
        overflow: hidden;
        position: relative;
        z-index: 1;
    }

    .jar-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 12px 24px rgba(0, 0, 0, 0.08) !important;
    }

    .jar-icon-box {
        width: 48px;
        height: 48px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 12px;
        font-size: 1.25rem;
    }

    /* Hiệu ứng nước (Water Effect) */
    .jar-bg-water {
        position: absolute;
        bottom: 0;
        left: 0;
        width: 100%;
        z-index: -1;
        opacity: 0.1;
        transition: height 1s ease-in-out;
    }

    .bg-nec {
        background-color: #dc3545;
    }

    .text-nec {
        color: #dc3545;
    }

    .bg-ffa {
        background-color: #f59e0b;
    }

    .text-ffa {
        color: #f59e0b;
    }

    .bg-ltss {
        background-color: #0d6efd;
    }

    .text-ltss {
        color: #0d6efd;
    }

    .bg-edu {
        background-color: #0dcaf0;
    }

    .text-edu {
        color: #0dcaf0;
    }

    .bg-play {
        background-color: #d63384;
    }

    .text-play {
        color: #d63384;
    }

    .bg-give {
        background-color: #198754;
    }

    .text-give {
        color: #198754;
    }

    .bg-nec-subtle {
        background-color: #fee2e2;
        color: #dc3545;
    }

    .bg-ffa-subtle {
        background-color: #fef3c7;
        color: #d97706;
    }

    .bg-ltss-subtle {
        background-color: #dbeafe;
        color: #0d6efd;
    }

    .bg-edu-subtle {
        background-color: #cffafe;
        color: #0891b2;
    }

    .bg-play-subtle {
        background-color: #fce7f3;
        color: #d63384;
    }

    .bg-give-subtle {
        background-color: #dcfce7;
        color: #16a34a;
    }
</style>

<?php echo CsrfProtection::getTokenMeta(); ?>

<main class="container budgets-page py-4">
    <div class="budgets-header mb-4">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div class="d-flex align-items-center gap-3">
                <div class="header-icon-box" style="background: white; padding: 12px; border-radius: 50%; box-shadow: 0 2px 5px rgba(0,0,0,0.05);">
                    <i class="fas fa-chart-pie fa-lg text-primary"></i>
                </div>
                <div>
                    <h2 class="page-title mb-0 fw-bold">Hệ thống JARS</h2>
                    <p class="page-subtitle text-muted mb-0 small">Quản lý tài chính theo phương pháp 6 chiếc hũ</p>
                </div>
            </div>
            <div class="d-flex gap-2">
                <button id="openIncomeModal" class="btn btn-success shadow-sm fw-bold px-3" data-bs-toggle="modal" data-bs-target="#incomeDistributeModal">
                    <i class="fas fa-hand-holding-usd me-2"></i>Nạp tiền
                </button>

                <button id="editSmartRatiosBtn" class="btn btn-outline-primary bg-white shadow-sm fw-medium" data-bs-toggle="modal" data-bs-target="#smartBudgetModal">
                    <i class="fas fa-sliders-h me-2"></i>Cấu hình
                </button>
                <button id="openCreateBudget" class="btn btn-primary shadow-sm fw-medium px-3">
                    <i class="fas fa-plus me-2"></i>Thêm Ngân Sách
                </button>
            </div>
        </div>
    </div>

    <div id="jarsContainer" class="row g-3 mb-4">
        <?php
        $jarConfig = [
            'nec'  => ['name' => 'Thiết yếu (NEC)',  'style' => 'nec',  'icon' => 'fa-utensils'],
            'ffa'  => ['name' => 'Tự do TC (FFA)',   'style' => 'ffa',  'icon' => 'fa-chart-line'],
            'ltss' => ['name' => 'Tiết kiệm dài hạn', 'style' => 'ltss', 'icon' => 'fa-piggy-bank'],
            'edu'  => ['name' => 'Giáo dục (EDU)',   'style' => 'edu',  'icon' => 'fa-graduation-cap'],
            'play' => ['name' => 'Hưởng thụ (PLAY)', 'style' => 'play', 'icon' => 'fa-gamepad'],
            'give' => ['name' => 'Cho đi (GIVE)',    'style' => 'give', 'icon' => 'fa-hand-holding-heart']
        ];

        $walletsData = $wallets ?? [];
        $settingsData = $settings ?? [];

        if (!empty($walletsData)):
            foreach ($walletsData as $wallet):
                $code = $wallet['jar_code'];
                $conf = $jarConfig[$code];
                $balance = $wallet['balance'];
                $percent = $settingsData[$code . '_percent'] ?? 0;

                // Giả lập chiều cao nước (max 100%) để làm hiệu ứng, dựa trên số dư (ví dụ: 10tr là đầy)
                $waterHeight = min(100, ($balance / 10000000) * 100);
                if ($balance > 0 && $waterHeight < 15) $waterHeight = 15; // Min height để thấy màu
        ?>
                <div class="col-12 col-md-6 col-lg-4">
                    <div class="card jar-card h-100 shadow-sm">
                        <div class="jar-bg-water bg-<?= $conf['style'] ?>" style="height: <?= $waterHeight ?>%"></div>

                        <div class="card-body p-4">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div class="jar-icon-box bg-<?= $conf['style'] ?>-subtle">
                                    <i class="fas <?= $conf['icon'] ?>"></i>
                                </div>
                                <span class="badge bg-white text-dark border shadow-sm rounded-pill px-3 py-2 fw-normal">
                                    Tỷ lệ: <b><?= $percent ?>%</b>
                                </span>
                            </div>

                            <h6 class="text-muted text-uppercase fw-bold small mb-1"><?= $conf['name'] ?></h6>
                            <h3 class="fw-bold mb-3 text-dark"><?= number_format($balance, 0, ',', '.') ?> <small class="text-muted fs-6">₫</small></h3>
                        </div>
                    </div>
                </div>
        <?php endforeach;
        endif; ?>
    </div>

    <div class="row g-4">
        <div class="col-lg-12">
            <div class="card list-card border-0 shadow-sm h-100 rounded-4">
                <div class="card-header bg-white border-bottom py-3 px-4 d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold text-dark mb-0">Chi tiết Ngân sách chi tiêu</h5>
                    <select class="form-select form-select-sm w-auto border-0 bg-light fw-bold" id="periodFilter">
                        <option value="monthly">Tháng này</option>
                        <option value="weekly">Tuần này</option>
                        <option value="yearly">Năm nay</option>
                    </select>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <tbody id="budgetsList"></tbody>
                        </table>
                    </div>
                    <div id="emptyState" class="text-center py-5" style="display:none;">
                        <h6 class="text-dark fw-bold">Chưa có ngân sách nào</h6>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="charts-row mt-4 d-flex gap-4">
        <div class="card chart-card border-0 shadow-sm mb-4 rounded-4 flex-fill">
            <div class="card-body p-4">
                <h6 class="fw-bold text-dark mb-4">Xu hướng chi tiêu</h6>
                <div class="chart-container"><canvas id="budgetTrend"></canvas></div>
            </div>
        </div>
        <div class="card chart-card border-0 shadow-sm mb-4 rounded-4 flex-fill">
            <div class="card-body p-4">
                <h6 class="fw-bold text-dark mb-4">Phân bổ danh mục</h6>
                <div class="chart-container"><canvas id="budgetPie"></canvas></div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="createBudgetModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow-lg rounded-4">
                <form id="createBudgetForm" novalidate>
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


    <div class="modal fade" id="smartBudgetModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <div class="modal-header border-0 pb-0 px-4 pt-4">
                    <h5 class="modal-title fw-bold text-primary"><i class="fas fa-sliders-h me-2"></i>Cấu hình Tỷ lệ JARS</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body px-4 py-4">
                    <?php
                    $jarInputs = [
                        ['id' => 'nec', 'label' => 'NEC - Thiết yếu', 'color' => 'danger', 'def' => 55],
                        ['id' => 'ffa', 'label' => 'FFA - Tự do TC', 'color' => 'warning', 'def' => 10],
                        ['id' => 'ltss', 'label' => 'LTSS - TK dài hạn', 'color' => 'primary', 'def' => 10],
                        ['id' => 'edu', 'label' => 'EDU - Giáo dục', 'color' => 'info', 'def' => 10],
                        ['id' => 'play', 'label' => 'PLAY - Hưởng thụ', 'color' => 'pink', 'def' => 10],
                        ['id' => 'give', 'label' => 'GIVE - Cho đi', 'color' => 'success', 'def' => 5]
                    ];
                    foreach ($jarInputs as $j):
                        $val = $settingsData[$j['id'] . '_percent'] ?? $j['def'];
                    ?>
                        <div class="mb-3">
                            <div class="d-flex justify-content-between mb-1">
                                <label class="fw-bold text-dark small"><?= $j['label'] ?></label>
                                <div class="d-flex align-items-center gap-2">
                                    <input type="range" class="form-range jar-input" id="<?= $j['id'] ?>Input"
                                        min="0" max="100" step="5" value="<?= $val ?>">
                                    <span class="badge bg-<?= $j['color'] === 'pink' ? 'danger' : $j['color'] ?>" style="width: 50px;">
                                        <span id="<?= $j['id'] ?>Percent"><?= $val ?></span>%
                                    </span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <span class="small fw-bold">Tổng tỷ lệ:</span>
                        <span id="totalPercent" class="fw-bold text-success">100%</span>
                    </div>
                </div>
                <div class="modal-footer border-0 px-4 pb-4">
                    <button type="button" class="btn btn-primary w-100" id="saveRatiosBtn">Lưu Cấu Hình</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="incomeDistributeModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <div class="modal-header border-0 bg-success text-white rounded-top-4 px-4 py-3">
                    <h5 class="modal-title fw-bold"><i class="fas fa-coins me-2"></i>Phân bổ Thu nhập</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-4 text-center">
                        <label class="form-label text-muted small text-uppercase fw-bold">Nhập tổng thu nhập</label>
                        <div class="input-group input-group-lg border rounded-3 overflow-hidden shadow-sm">
                            <span class="input-group-text bg-white border-0 ps-3 text-success fw-bold">₫</span>
                            <input type="text" class="form-control border-0 fw-bold text-success fs-2 text-center"
                                id="incomeAmountInput" placeholder="0" onkeyup="SmartSpending.previewIncome(this)">
                        </div>
                    </div>

                    <h6 class="fw-bold text-dark mb-3 small text-uppercase border-bottom pb-2">Dự kiến phân bổ</h6>
                    <div class="row g-2" id="incomePreviewList">
                        <div class="text-center text-muted py-3 small">Nhập số tiền để xem phân bổ</div>
                    </div>
                </div>
                <div class="modal-footer border-0 px-4 pb-4">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Hủy</button>
                    <button type="button" class="btn btn-success px-4 fw-bold" id="confirmDistributeBtn" onclick="SmartSpending.submitIncome()">
                        <i class="fas fa-check me-2"></i>Xác nhận Nạp
                    </button>
                </div>
            </div>
        </div>
    </div>

</main>

<script>
    window.BASE_URL = "<?php echo BASE_URL; ?>";
    window.JARS_SETTINGS = <?php echo json_encode($settingsData); ?>;
</script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="<?php echo BASE_URL; ?>/js/budgets.js"></script>
<script src="<?php echo BASE_URL; ?>/js/smart-budget.js"></script>

<?php $this->partial('footer'); ?>