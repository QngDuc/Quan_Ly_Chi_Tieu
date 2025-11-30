<?php $this->partial('header', ['title' => 'SmartSpending - Quản Lý Tài Chính']); ?>

<!-- Dashboard Specific Styles -->
<link rel="stylesheet" href="<?php echo BASE_URL; ?>/public/css/dashboard.css">

<section>
    <h3 class="mb-3">Tổng quan</h3>

    <div class="card p-3">
        <div class="stats-grid">
            <div class="card stat-card">
                <h3>Tổng Số Dư</h3>
                <div class="value"><?php echo number_format($totals['balance'] ?? 0, 0, ',', '.'); ?> ₫</div>
                <?php
                $netIncome = ($totals['income'] ?? 0) - ($totals['expense'] ?? 0);
                $netTrendClass = ($netIncome >= 0) ? 'up' : 'down';
                ?>
                <div class="trend <?php echo $netTrendClass; ?>">
                    <i class="fas fa-arrow-trend-<?php echo $netTrendClass; ?>"></i>
                    <?php echo ($netIncome >= 0) ? '+' : ''; ?><?php echo number_format($netIncome, 0, ',', '.'); ?> ₫ tháng này
                </div>
            </div>
            <div class="card stat-card">
                <h3>Tổng Thu Nhập</h3>
                <div class="value"><?php echo number_format($totals['income'] ?? 0, 0, ',', '.'); ?> ₫</div>
                <?php if ($totals['income_trend'] !== null): ?>
                <div class="trend <?php echo ($totals['income_trend'] >= 0) ? 'up' : 'down'; ?>">
                    <i class="fas fa-arrow-trend-<?php echo ($totals['income_trend'] >= 0) ? 'up' : 'down'; ?>"></i>
                    <?php echo ($totals['income_trend'] >= 0) ? '+' : ''; ?><?php echo $totals['income_trend']; ?>% so với kỳ trước
                </div>
                <?php else: ?>
                <div class="trend" style="opacity: 0.5;">
                    <i class="fas fa-minus"></i> Chưa có dữ liệu so sánh
                </div>
                <?php endif; ?>
            </div>
            <div class="card stat-card">
                <h3>Tổng Chi Tiêu</h3>
                <div class="value"><?php echo number_format($totals['expense'] ?? 0, 0, ',', '.'); ?> ₫</div>
                <?php if ($totals['expense_trend'] !== null): ?>
                <div class="trend <?php echo ($totals['expense_trend'] <= 0) ? 'up' : 'down'; ?>">
                    <i class="fas fa-arrow-trend-<?php echo ($totals['expense_trend'] <= 0) ? 'down' : 'up'; ?>"></i>
                    <?php echo ($totals['expense_trend'] >= 0) ? '+' : ''; ?><?php echo $totals['expense_trend']; ?>% so với kỳ trước
                </div>
                <?php else: ?>
                <div class="trend" style="opacity: 0.5;">
                    <i class="fas fa-minus"></i> Chưa có dữ liệu so sánh
                </div>
                <?php endif; ?>
            </div>
            <div class="card stat-card">
                <h3>Tỷ Lệ Tiết Kiệm</h3>
                <div class="value"><?php echo ($totals['savingsRate'] ?? 0); ?>%</div>
                <?php if ($totals['income'] > 0 || ($totals['income_trend'] !== null && $totals['expense_trend'] !== null)): ?>
                <div class="trend <?php echo ($totals['savings_rate_trend'] >= 0) ? 'up' : 'down'; ?>">
                    <i class="fas fa-arrow-trend-<?php echo ($totals['savings_rate_trend'] >= 0) ? 'up' : 'down'; ?>"></i>
                    <?php echo ($totals['savings_rate_trend'] >= 0) ? '+' : ''; ?><?php echo $totals['savings_rate_trend']; ?>% so với kỳ trước
                </div>
                <?php else: ?>
                <div class="trend" style="opacity: 0.5;">
                    <i class="fas fa-minus"></i> Chưa có dữ liệu so sánh
                </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="section-header">
            <h2>Tổng Quan Chi Tiêu</h2>
            <div class="dropdown">
                <button class="btn btn-secondary dropdown-toggle" type="button" id="rangeDropdownButton" data-bs-toggle="dropdown" aria-expanded="false" style="min-width: 150px;">
                    <?php
                    // Display selected month in Vietnamese format
                    $currentRange = $range ?? date('Y-m');
                    if (preg_match('/^\d{4}-\d{2}$/', $currentRange)) {
                        $monthNumber = (int)date('n', strtotime($currentRange . '-01'));
                        echo 'Tháng ' . $monthNumber;
                    } else {
                        echo 'Tháng ' . date('n');
                    }
                    ?>
                </button>
                <ul class="dropdown-menu" aria-labelledby="rangeDropdownButton">
                    <?php
                    // Generate last 3 months dynamically
                    for ($i = 2; $i >= 0; $i--) {
                        $monthDate = date('Y-m', strtotime("-$i months"));
                        $monthNumber = date('n', strtotime($monthDate . '-01'));
                        $isActive = ($monthDate === ($range ?? date('Y-m'))) ? 'active' : '';
                        echo '<li><a class="dropdown-item ' . $isActive . '" href="' . BASE_URL . '/dashboard/index/' . $monthDate . '">Tháng ' . $monthNumber . '</a></li>';
                    }
                    ?>
                </ul>
            </div>
        </div>

        <div class="charts-grid">
            <div class="card chart-card">
                <div class="chart-header">
                    <h3>Thu Nhập vs Chi Tiêu</h3>
                    <span class="subtitle"><?php echo $lineChartSubtitle; ?></span>
                </div>
                <div class="chart-area">
                    <canvas id="lineChart"></canvas>
                </div>
            </div>

            <div class="card chart-card">
                <div class="chart-header">
                    <h3>Phân Bổ Chi Tiêu</h3>
                    <span class="subtitle">Danh mục chi tiêu <?php
                                                                switch ($range ?? 'this_month') {
                                                                    default:
                                                                        echo 'tháng này';
                                                                        break;
                                                                }
                                                                ?></span>
                </div>
                <div class="pie-area">
                    <canvas id="pieChart"></canvas>
                </div>
            </div>
        </div>

        <div class="transactions-section">
            <div class="card table-card">
                <div class="card-header">
                    <h3>Giao Dịch Gần Đây</h3>
                    <a href="/Quan_Ly_Chi_Tieu/transactions" class="view-all">Xem tất cả</a>
                </div>
                <table class="custom-table">
                    <thead>
                        <tr>
                            <th width="35%">Giao dịch</th>
                            <th width="25%">Danh mục</th>
                            <th width="20%">Ngày</th>
                            <th width="20%" style="text-align: right;">Số tiền</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentTransactions as $tx): ?>
                            <tr>
                                <td><?php echo $this->escape($tx['description']); ?></td>
                                <td><?php echo $this->escape($tx['category_name']); ?></td>
                                <td><?php echo date('d/m/Y', strtotime($tx['transaction_date'])); ?></td>
                                <td class="amount" style="text-align:right;">
                                    <?php if ($tx['amount'] < 0): ?>
                                        <span class="text-dark">- <?php echo number_format(abs($tx['amount']), 0, ',', '.'); ?> ₫</span>
                                    <?php else: ?>
                                        <span class="text-green">+ <?php echo number_format($tx['amount'], 0, ',', '.'); ?> ₫</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>

<!-- Data for JS -->
<script>
    window.pieChartData = <?php echo $pieChartData ?? '[]'; ?>;
    window.lineChartData = <?php echo $lineChartData ?? '[]'; ?>;
</script>

<script src="<?php echo BASE_URL; ?>/public/js/dashboard.js"></script>

<?php $this->partial('footer'); ?>