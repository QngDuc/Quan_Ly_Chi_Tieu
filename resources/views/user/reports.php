<?php $this->partial('header'); ?>

<section class="reports-section">
    <!-- Header with Filters -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="mb-0">
            <i class="fas fa-chart-line me-2"></i>
            Báo cáo Chi tiêu
        </h3>
        <button id="exportReport" class="btn btn-primary">
            <i class="fas fa-download me-2"></i>
            Xuất báo cáo
        </button>
    </div>

    <!-- Filters Card -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label for="periodFilter" class="form-label">
                        <i class="fas fa-calendar me-1"></i>
                        Kỳ báo cáo
                    </label>
                    <select id="periodFilter" class="form-select">
                        <option value="this_month" <?php echo ($current_period ?? 'last_3_months') === 'this_month' ? 'selected' : ''; ?>>
                            Tháng này
                        </option>
                        <option value="last_3_months" <?php echo ($current_period ?? 'last_3_months') === 'last_3_months' ? 'selected' : ''; ?>>
                            3 tháng gần đây
                        </option>
                        <option value="last_6_months" <?php echo ($current_period ?? 'last_3_months') === 'last_6_months' ? 'selected' : ''; ?>>
                            6 tháng gần đây
                        </option>
                        <option value="this_year" <?php echo ($current_period ?? 'last_3_months') === 'this_year' ? 'selected' : ''; ?>>
                            Năm nay
                        </option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="typeFilter" class="form-label">
                        <i class="fas fa-filter me-1"></i>
                        Loại giao dịch
                    </label>
                    <select id="typeFilter" class="form-select">
                        <option value="all" <?php echo ($current_type ?? 'all') === 'all' ? 'selected' : ''; ?>>
                            Tất cả
                        </option>
                        <option value="expense" <?php echo ($current_type ?? 'all') === 'expense' ? 'selected' : ''; ?>>
                            Chi tiêu
                        </option>
                        <option value="income" <?php echo ($current_type ?? 'all') === 'income' ? 'selected' : ''; ?>>
                            Thu nhập
                        </option>
                    </select>
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <div class="text-muted small">
                        <i class="fas fa-info-circle me-1"></i>
                        Biểu đồ sẽ cập nhật tự động
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="row g-4">
        <!-- Line Chart -->
        <div class="col-lg-8">
            <div class="card h-100">
                <div class="card-header bg-transparent border-0">
                    <h5 class="mb-0">
                        <i class="fas fa-chart-bar me-2 text-primary"></i>
                        Thu nhập và Chi tiêu theo Thời gian
                    </h5>
                </div>
                <div class="card-body">
                    <div style="height: 350px; position: relative;">
                        <canvas id="lineChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pie Chart -->
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-header bg-transparent border-0">
                    <h5 class="mb-0">
                        <i class="fas fa-chart-pie me-2 text-success"></i>
                        Phân bổ theo Danh mục
                    </h5>
                </div>
                <div class="card-body">
                    <div style="height: 350px; position: relative;">
                        <canvas id="pieChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Summary Stats (Optional) -->
    <div class="row g-4 mt-2">
        <div class="col-md-3">
            <div class="card">
                <div class="card-body text-center">
                    <div class="text-muted mb-2">
                        <i class="fas fa-wallet"></i> Tổng thu nhập
                    </div>
                    <h4 class="text-success mb-0" id="totalIncome">-</h4>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body text-center">
                    <div class="text-muted mb-2">
                        <i class="fas fa-shopping-cart"></i> Tổng chi tiêu
                    </div>
                    <h4 class="text-danger mb-0" id="totalExpense">-</h4>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body text-center">
                    <div class="text-muted mb-2">
                        <i class="fas fa-balance-scale"></i> Chênh lệch
                    </div>
                    <h4 class="mb-0" id="balance">-</h4>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body text-center">
                    <div class="text-muted mb-2">
                        <i class="fas fa-piggy-bank"></i> Tỷ lệ tiết kiệm
                    </div>
                    <h4 class="text-info mb-0" id="savingsRate">-</h4>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
// Pass initial data to JavaScript
window.initialReportData = {
    lineChart: <?php echo json_encode($reportLine ?? []); ?>,
    pieChart: <?php echo json_encode($reportPie ?? []); ?>
};

// Initialize charts with initial data on page load
document.addEventListener('DOMContentLoaded', function() {
    if (window.initialReportData && window.initialReportData.lineChart) {
        const data = window.initialReportData;
        const styles = getComputedStyle(document.documentElement);
        const gridColor = styles.getPropertyValue('--chart-grid').trim();
        const textColor = styles.getPropertyValue('--chart-text').trim();

        // Line Chart
        const lineCtx = document.getElementById('lineChart');
        if (lineCtx && data.lineChart.labels) {
            new Chart(lineCtx, {
                type: 'bar',
                data: {
                    labels: data.lineChart.labels,
                    datasets: [{
                        label: 'Thu nhập',
                        data: data.lineChart.income,
                        backgroundColor: '#10B981',
                        borderRadius: 8
                    }, {
                        label: 'Chi tiêu',
                        data: data.lineChart.expense,
                        backgroundColor: '#EF4444',
                        borderRadius: 8
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: { color: gridColor },
                            ticks: {
                                color: textColor,
                                callback: function(value) {
                                    if (value >= 1000000) return (value / 1000000) + 'tr';
                                    if (value >= 1000) return (value / 1000) + 'k';
                                    return value;
                                }
                            }
                        },
                        x: {
                            grid: { display: false },
                            ticks: { color: textColor }
                        }
                    },
                    plugins: {
                        legend: { labels: { color: textColor } }
                    }
                }
            });
        }

        // Pie Chart
        const pieCtx = document.getElementById('pieChart');
        if (pieCtx && data.pieChart.labels) {
            const pieColors = ['#3B82F6','#F97316','#10B981','#EF4444','#8B5CF6','#F59E0B','#EC4899','#14B8A6','#6366F1','#F43F5E'];
            new Chart(pieCtx, {
                type: 'doughnut',
                data: {
                    labels: data.pieChart.labels,
                    datasets: [{
                        data: data.pieChart.data,
                        backgroundColor: pieColors,
                        borderWidth: 2,
                        borderColor: '#ffffff',
                        hoverOffset: 15
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                color: textColor,
                                padding: 15,
                                usePointStyle: true
                            }
                        }
                    }
                }
            });
        }

        // Update summary stats
        const totalIncome = data.lineChart.income.reduce((a, b) => a + b, 0);
        const totalExpense = data.lineChart.expense.reduce((a, b) => a + b, 0);
        const balance = totalIncome - totalExpense;
        const savingsRate = totalIncome > 0 ? ((balance / totalIncome) * 100).toFixed(1) : 0;

        document.getElementById('totalIncome').textContent = new Intl.NumberFormat('vi-VN').format(totalIncome) + ' ₫';
        document.getElementById('totalExpense').textContent = new Intl.NumberFormat('vi-VN').format(totalExpense) + ' ₫';
        document.getElementById('balance').textContent = new Intl.NumberFormat('vi-VN').format(balance) + ' ₫';
        document.getElementById('balance').className = balance >= 0 ? 'text-success mb-0' : 'text-danger mb-0';
        document.getElementById('savingsRate').textContent = savingsRate + '%';
    }

    // Export functionality
    const exportBtn = document.getElementById('exportReport');
    if (exportBtn) {
        exportBtn.addEventListener('click', function() {
            const period = document.getElementById('periodFilter')?.value || 'last_3_months';
            const type = document.getElementById('typeFilter')?.value || 'all';
            
            const params = new URLSearchParams({
                period: period,
                type: type
            });
            
            // Prefer modern XLSX export endpoint
            const url = `${BASE_URL}/reports/export_xlsx?${params.toString()}`;
            window.location.href = url;
        });
    }
});
</script>

<?php $this->partial('footer'); ?>
