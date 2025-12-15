</main>
</div>

<!-- Toast Container -->
<div class="toast-container" id="toastContainer"></div>

<!-- Global Notification Modal (used instead of alert/toast when configured) -->
<div class="modal fade" id="globalNotificationModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="globalNotificationModalTitle">Thông báo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="globalNotificationModalBody"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
            </div>
        </div>
    </div>
</div>



<footer class="site-footer simple-footer">
    <!-- <div class="footer-inner">
        <div class="footer-left">© <?php echo date('Y'); ?> SmartSpending</div>

        <div class="footer-center">
            <a href="<?php echo BASE_URL; ?>/privacy">Chính sách</a>
            <span class="sep">·</span>
            <a href="<?php echo BASE_URL; ?>/terms">Điều khoản</a>
            <span class="sep">·</span>
            <a href="<?php echo BASE_URL; ?>/contact">Liên hệ</a>
        </div>

        <div class="footer-right">
            <a href="mailto:huyhoangpro187@gmail.com" class="footer-email">huyhoangpro187@gmail.com</a>
            <span class="social-row">
                <a href="https://facebook.com" target="_blank" rel="noopener" aria-label="Facebook" class="social-icon"><i class="bi bi-facebook"></i></a>
                <a href="https://twitter.com" target="_blank" rel="noopener" aria-label="Twitter" class="social-icon"><i class="bi bi-twitter"></i></a>
                <a href="https://instagram.com" target="_blank" rel="noopener" aria-label="Instagram" class="social-icon"><i class="bi bi-instagram"></i></a>
            </span>
        </div>
    </div> -->
</footer>


<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<!-- Bootstrap Icons CSS (for footer/social icons) -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
<!-- Chart.js for visualizations -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>


<!-- Input Masking -->
<script src="<?php echo BASE_URL; ?>/shared/input-masking.js"></script>

<!-- App.js - Shared utilities (public/shared/app.js served by webserver) -->
<script src="<?php echo BASE_URL; ?>/shared/app.js"></script>

<!-- Page-specific JavaScript -->
<?php
$rawUrl = trim($_GET['url'] ?? '', '/');
if ($rawUrl === '') {
    $requestUri = $_SERVER['REQUEST_URI'] ?? '';
    $basePath = BASE_URL;
    if ($basePath !== '' && strpos($requestUri, $basePath) === 0) {
        $path = substr($requestUri, strlen($basePath));
    } else {
        $path = $requestUri;
    }
    $path = strtok($path, '?');
    $rawUrl = trim($path, '/');
}

$segments = $rawUrl !== '' ? explode('/', $rawUrl) : [];
$page = $segments[0] ?? '';
if ($page === 'home' || $page === '') {
    $page = 'dashboard';
}

// Load page-specific JS: prefer public/user/{page}/{page}.js if published, else fall back to resources/js/{page}.js
if ($page && in_array($page, ['dashboard', 'transactions', 'budgets', 'goals', 'reports', 'profile'])) {
    $projectRoot = realpath(__DIR__ . '/../../..');
    $publicPagePath = $projectRoot . '/public/user/' . $page . '/' . $page . '.js';
    if (file_exists($publicPagePath)) {
        echo '<script src="' . BASE_URL . '/user/' . $page . '/' . $page . '.js"></script>' . "\n";
    } else {
        // fallback to public/js (useful during development)
        echo '<script src="' . BASE_URL . '/js/' . $page . '.js"></script>' . "\n";
    }
}
?>

<?php
// Page-specific scripts can be registered by views (set 'pageScripts')
if (!empty($pageScripts)) {
    echo $pageScripts;
}
?>

<script>
    // Populate transaction category select with child categories only (no parents)
    (function() {
        try {
            var sel = document.getElementById('transactionCategory');
            if (!sel) return;
            var url = '<?php echo BASE_URL; ?>/budgets/api_get_categories';
            fetch(url, {
                    cache: 'no-store'
                })
                .then(function(r) {
                    if (!r.ok) throw new Error('Network');
                    return r.json();
                })
                .then(function(payload) {
                    var cats = (payload && payload.data && payload.data.categories) ? payload.data.categories : (payload.categories || []);
                    // keep only child categories (parent_id > 0)
                    var children = cats.filter(function(c) {
                        return Number(c.parent_id) > 0;
                    });
                    // If none found, fall back to any categories that look like leaf nodes (no children)
                    if (children.length === 0 && cats.length > 0) {
                        // build child map
                        var map = {};
                        cats.forEach(function(x) {
                            map[String(x.parent_id)] = (map[String(x.parent_id)] || 0) + 1;
                        });
                        children = cats.filter(function(c) {
                            return !(map[String(c.id)] > 0);
                        });
                    }
                    // Render options
                    sel.innerHTML = '<option value="">Chọn danh mục</option>';
                    children.forEach(function(c) {
                        var opt = document.createElement('option');
                        opt.value = c.id;
                        opt.textContent = c.name;
                        sel.appendChild(opt);
                    });
                }).catch(function() {
                    /* keep placeholder option if fetch fails */ });
        } catch (e) {
            /* no-op */ }
    })();
</script>

<?php if ($page !== 'transactions'): ?>
<!-- Add Transaction Modal (global) -->
<div class="modal fade" id="addTransactionModal" tabindex="-1" aria-labelledby="addTransactionModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addTransactionModalLabel">Thêm Giao Dịch Mới</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="addTransactionForm" action="<?php echo BASE_URL; ?>/transactions/add" method="POST">
                    <div class="mb-3">
                        <label for="transactionType" class="form-label">Loại Giao Dịch</label>
                        <select class="form-select" id="transactionType" name="type" required>
                            <option value="expense">Chi Tiêu</option>
                            <option value="income">Thu Nhập</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="transactionAmount" class="form-label">Số Tiền</label>
                        <input type="number" class="form-control" id="transactionAmount" name="amount" required min="0">
                    </div>
                    <div class="mb-3">
                        <label for="transactionCategory" class="form-label">Danh Mục</label>
                        <select class="form-select" id="transactionCategory" name="category_id" required>
                            <option value="">Chọn danh mục</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="transactionDate" class="form-label">Ngày</label>
                        <input type="date" class="form-control" id="transactionDate" name="date" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="transactionDescription" class="form-label">Mô Tả</label>
                        <textarea class="form-control" id="transactionDescription" name="description" rows="3"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">Lưu Giao Dịch</button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>
</body>

</html>