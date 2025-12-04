<?php 
use App\Middleware\CsrfProtection;
$this->partial('header'); 
?>

<!-- Budgets Specific Styles -->
<link rel="stylesheet" href="<?php echo BASE_URL; ?>/user/budgets/budgets.css">
<?php echo CsrfProtection::getTokenMeta(); ?>

<section>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3>Quản lý Ngân sách</h3>
        <div class="d-flex gap-2">
            <select id="periodFilter" class="form-select" style="width: auto;">
                <?php 
                $currentPeriod = $current_period ?? date('Y-m');
                // Generate last 12 months
                for ($i = 0; $i < 12; $i++) {
                    $period = date('Y-m', strtotime("-$i months"));
                    $monthNum = (int)date('n', strtotime($period . '-01'));
                    $yearNum = date('Y', strtotime($period . '-01'));
                    $label = 'Tháng ' . $monthNum . '/' . $yearNum;
                    $selected = ($period === $currentPeriod) ? 'selected' : '';
                    echo "<option value=\"$period\" $selected>$label</option>";
                }
                ?>
            </select>
            <button class="btn btn-primary" onclick="showSettingsModal()">
                <i class="bi bi-gear"></i> Cài đặt
            </button>
        </div>
    </div>

    <!-- Income Summary Card -->
    <div class="income-card mb-4">
        <div class="income-header">
            <i class="bi bi-cash-coin income-icon"></i>
            <div>
                <div class="income-label">Thu nhập tháng này</div>
                <div class="income-amount" id="totalIncome">
                    <?php echo '₫ ' . number_format($total_income ?? 0, 0, ',', '.'); ?>
                </div>
            </div>
        </div>
    </div>

    <!-- 6 Jars Grid -->
    <div class="jars-grid mb-4" id="jarsGrid">
        <?php 
        $jars = [
            'nec' => ['name' => 'Thiết yếu', 'icon' => 'bi-basket', 'color' => '#3498db', 'default' => 55],
            'ffa' => ['name' => 'Tự do tài chính', 'icon' => 'bi-piggy-bank', 'color' => '#2ecc71', 'default' => 10],
            'edu' => ['name' => 'Giáo dục', 'icon' => 'bi-book', 'color' => '#9b59b6', 'default' => 10],
            'ltss' => ['name' => 'Tiết kiệm', 'icon' => 'bi-wallet2', 'color' => '#e74c3c', 'default' => 10],
            'play' => ['name' => 'Vui chơi', 'icon' => 'bi-controller', 'color' => '#f39c12', 'default' => 10],
            'give' => ['name' => 'Từ thiện', 'icon' => 'bi-heart', 'color' => '#1abc9c', 'default' => 5]
        ];
        
        foreach ($jars as $key => $jar):
            $jarData = $jar_summary[$key] ?? null;
            $percentage = $jarData['percentage'] ?? $jar['default'];
            $amount = $jarData['amount'] ?? 0;
            $spent = $jarData['spent'] ?? 0;
            $remaining = $amount - $spent;
            $progress = $amount > 0 ? ($spent / $amount) * 100 : 0;
            $progressClass = $progress >= 100 ? 'danger' : ($progress >= 80 ? 'warning' : 'success');
        ?>
        <div class="jar-card" data-jar="<?php echo $key; ?>">
            <div class="jar-header">
                <div class="jar-icon" style="background: <?php echo $jar['color']; ?>">
                    <i class="bi <?php echo $jar['icon']; ?>"></i>
                </div>
                <div class="jar-info">
                    <div class="jar-name"><?php echo $jar['name']; ?></div>
                    <div class="jar-percentage"><?php echo $percentage; ?>%</div>
                </div>
            </div>
            <div class="jar-amounts">
                <div class="jar-amount-item">
                    <span class="label">Phân bổ:</span>
                    <span class="value">₫ <?php echo number_format($amount, 0, ',', '.'); ?></span>
                </div>
                <div class="jar-amount-item">
                    <span class="label">Đã chi:</span>
                    <span class="value text-danger">₫ <?php echo number_format($spent, 0, ',', '.'); ?></span>
                </div>
                <div class="jar-amount-item">
                    <span class="label">Còn lại:</span>
                    <span class="value text-success">₫ <?php echo number_format($remaining, 0, ',', '.'); ?></span>
                </div>
            </div>
            <div class="jar-progress">
                <div class="progress" style="height: 8px;">
                    <div class="progress-bar bg-<?php echo $progressClass; ?>" 
                         style="width: <?php echo min($progress, 100); ?>%"></div>
                </div>
                <div class="progress-text"><?php echo round($progress, 1); ?>% đã sử dụng</div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Category Guide -->
    <div class="card p-4">
        <h5 class="mb-3">Danh mục theo từng lọ</h5>
        <div class="row">
            <?php 
            $jarCategories = [
                'nec' => ['name' => 'Thiết yếu (55%)', 'color' => '#3498db', 'items' => ['Ăn uống hàng ngày', 'Tiền nhà', 'Điện nước', 'Đi lại', 'Y tế', 'Bảo hiểm']],
                'ffa' => ['name' => 'Tự do tài chính (10%)', 'color' => '#2ecc71', 'items' => ['Đầu tư', 'Tài sản sinh lời', 'Thu nhập thụ động']],
                'edu' => ['name' => 'Giáo dục (10%)', 'color' => '#9b59b6', 'items' => ['Sách vở', 'Khóa học', 'Hội thảo', 'Đào tạo']],
                'ltss' => ['name' => 'Tiết kiệm (10%)', 'color' => '#e74c3c', 'items' => ['Tiết kiệm khẩn cấp', 'Mục tiêu dài hạn', 'Quỹ dự phòng']],
                'play' => ['name' => 'Vui chơi (10%)', 'color' => '#f39c12', 'items' => ['Du lịch', 'Giải trí', 'Ăn uống cao cấp', 'Sở thích']],
                'give' => ['name' => 'Từ thiện (5%)', 'color' => '#1abc9c', 'items' => ['Quyên góp', 'Hỗ trợ cộng đồng', 'Quà tặng ý nghĩa']]
            ];
            
            foreach ($jarCategories as $key => $cat):
            ?>
            <div class="col-md-6 mb-3">
                <div class="guide-item">
                    <div class="guide-header">
                        <div class="guide-dot" style="background: <?php echo $cat['color']; ?>"></div>
                        <strong><?php echo $cat['name']; ?></strong>
                    </div>
                    <ul class="guide-list">
                        <?php foreach ($cat['items'] as $item): ?>
                            <li><?php echo $item; ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Settings Modal -->
<div class="modal fade" id="settingsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Cài đặt Tỷ lệ 6 Lọ</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info mb-3">
                    <i class="bi bi-info-circle"></i>
                    Tổng tỷ lệ phải bằng 100%. Mặc định: NEC 55%, FFA 10%, EDU 10%, LTSS 10%, PLAY 10%, GIVE 5%
                </div>
                <form id="settingsForm">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Thiết yếu (NEC) %</label>
                            <input type="number" class="form-control jar-percentage" 
                                   id="necPercentage" name="nec" value="55" min="0" max="100" step="1">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Tự do tài chính (FFA) %</label>
                            <input type="number" class="form-control jar-percentage" 
                                   id="ffaPercentage" name="ffa" value="10" min="0" max="100" step="1">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Giáo dục (EDU) %</label>
                            <input type="number" class="form-control jar-percentage" 
                                   id="eduPercentage" name="edu" value="10" min="0" max="100" step="1">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Tiết kiệm (LTSS) %</label>
                            <input type="number" class="form-control jar-percentage" 
                                   id="ltssPercentage" name="ltss" value="10" min="0" max="100" step="1">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Vui chơi (PLAY) %</label>
                            <input type="number" class="form-control jar-percentage" 
                                   id="playPercentage" name="play" value="10" min="0" max="100" step="1">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Từ thiện (GIVE) %</label>
                            <input type="number" class="form-control jar-percentage" 
                                   id="givePercentage" name="give" value="5" min="0" max="100" step="1">
                        </div>
                    </div>
                    <div class="alert alert-warning mt-2" id="percentageWarning" style="display: none;">
                        <i class="bi bi-exclamation-triangle"></i>
                        <span id="percentageWarningText">Tổng tỷ lệ phải bằng 100%</span>
                    </div>
                    <div class="text-center mt-3">
                        <strong>Tổng: <span id="totalPercentage">100</span>%</strong>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                <button type="button" class="btn btn-primary" id="saveSettingsBtn" onclick="saveSettings()">
                    <span class="btn-text">Lưu</span>
                    <span class="spinner-border spinner-border-sm d-none" role="status"></span>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
let currentPeriod = '<?php echo $current_period ?? date('Y-m'); ?>';

// Period filter change
document.getElementById('periodFilter').addEventListener('change', function() {
    currentPeriod = this.value;
    loadJarData();
});

// Load jar data from API
function loadJarData() {
    fetch(`${BASE_URL}/budgets/api_get_jars/${currentPeriod}`)
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                updateJarDisplay(data.data);
            }
        })
        .catch(error => console.error('Error:', error));
}

// Update jar display
function updateJarDisplay(data) {
    const jars = data.jar_summary;
    const totalIncome = data.total_income;
    
    // Update total income
    document.getElementById('totalIncome').textContent = '₫ ' + formatNumber(totalIncome);
    
    // Update each jar
    const jarKeys = ['nec', 'ffa', 'edu', 'ltss', 'play', 'give'];
    jarKeys.forEach(key => {
        const jarData = jars[key] || {};
        const jarCard = document.querySelector(`[data-jar="${key}"]`);
        
        if (jarCard) {
            const amount = jarData.amount || 0;
            const spent = jarData.spent || 0;
            const remaining = amount - spent;
            const progress = amount > 0 ? (spent / amount) * 100 : 0;
            
            jarCard.querySelector('.jar-percentage').textContent = (jarData.percentage || 0) + '%';
            jarCard.querySelector('.jar-amount-item:nth-child(1) .value').textContent = '₫ ' + formatNumber(amount);
            jarCard.querySelector('.jar-amount-item:nth-child(2) .value').textContent = '₫ ' + formatNumber(spent);
            jarCard.querySelector('.jar-amount-item:nth-child(3) .value').textContent = '₫ ' + formatNumber(remaining);
            
            const progressBar = jarCard.querySelector('.progress-bar');
            progressBar.style.width = Math.min(progress, 100) + '%';
            progressBar.className = 'progress-bar bg-' + (progress >= 100 ? 'danger' : (progress >= 80 ? 'warning' : 'success'));
            jarCard.querySelector('.progress-text').textContent = progress.toFixed(1) + '% đã sử dụng';
        }
    });
}

// Show settings modal
function showSettingsModal() {
    const modal = new bootstrap.Modal(document.getElementById('settingsModal'));
    
    // Load current percentages
    fetch(`${BASE_URL}/budgets/api_get_jars/${currentPeriod}`)
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                const jars = data.data.jar_summary;
                document.getElementById('necPercentage').value = jars.nec?.percentage || 55;
                document.getElementById('ffaPercentage').value = jars.ffa?.percentage || 10;
                document.getElementById('eduPercentage').value = jars.edu?.percentage || 10;
                document.getElementById('ltssPercentage').value = jars.ltss?.percentage || 10;
                document.getElementById('playPercentage').value = jars.play?.percentage || 10;
                document.getElementById('givePercentage').value = jars.give?.percentage || 5;
                updateTotalPercentage();
            }
        });
    
    modal.show();
}

// Update total percentage
function updateTotalPercentage() {
    const percentages = document.querySelectorAll('.jar-percentage');
    let total = 0;
    percentages.forEach(input => {
        total += parseFloat(input.value) || 0;
    });
    
    document.getElementById('totalPercentage').textContent = total.toFixed(0);
    
    const warning = document.getElementById('percentageWarning');
    const saveBtn = document.getElementById('saveSettingsBtn');
    
    if (total !== 100) {
        warning.style.display = 'block';
        warning.querySelector('#percentageWarningText').textContent = 
            `Tổng hiện tại: ${total}%. Cần điều chỉnh để đạt 100%`;
        saveBtn.disabled = true;
    } else {
        warning.style.display = 'none';
        saveBtn.disabled = false;
    }
}

// Add event listeners to percentage inputs
document.querySelectorAll('.jar-percentage').forEach(input => {
    input.addEventListener('input', updateTotalPercentage);
});

// Save settings
function saveSettings() {
    const btn = document.getElementById('saveSettingsBtn');
    const btnText = btn.querySelector('.btn-text');
    const spinner = btn.querySelector('.spinner-border');
    
    btn.disabled = true;
    btnText.classList.add('d-none');
    spinner.classList.remove('d-none');
    
    const percentages = {
        nec: parseFloat(document.getElementById('necPercentage').value),
        ffa: parseFloat(document.getElementById('ffaPercentage').value),
        edu: parseFloat(document.getElementById('eduPercentage').value),
        ltss: parseFloat(document.getElementById('ltssPercentage').value),
        play: parseFloat(document.getElementById('playPercentage').value),
        give: parseFloat(document.getElementById('givePercentage').value)
    };
    
    fetch(`${BASE_URL}/budgets/api_update_percentages`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            period: currentPeriod,
            percentages: percentages
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            showToast('Cập nhật thành công!', 'success');
            bootstrap.Modal.getInstance(document.getElementById('settingsModal')).hide();
            loadJarData();
        } else {
            showToast(data.message || 'Có lỗi xảy ra', 'danger');
        }
    })
    .catch(error => {
        showToast('Có lỗi xảy ra', 'danger');
        console.error('Error:', error);
    })
    .finally(() => {
        btn.disabled = false;
        btnText.classList.remove('d-none');
        spinner.classList.add('d-none');
    });
}

// Format number
function formatNumber(num) {
    return Math.round(num).toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
}

// Show toast notification
function showToast(message, type = 'info') {
    const toastContainer = document.getElementById('toastContainer') || createToastContainer();
    const toast = document.createElement('div');
    toast.className = `toast align-items-center text-white bg-${type} border-0`;
    toast.setAttribute('role', 'alert');
    toast.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">${message}</div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    `;
    toastContainer.appendChild(toast);
    const bsToast = new bootstrap.Toast(toast);
    bsToast.show();
    toast.addEventListener('hidden.bs.toast', () => toast.remove());
}

function createToastContainer() {
    const container = document.createElement('div');
    container.id = 'toastContainer';
    container.className = 'toast-container position-fixed top-0 end-0 p-3';
    container.style.zIndex = '9999';
    document.body.appendChild(container);
    return container;
}
</script>

<?php $this->partial('footer'); ?>
