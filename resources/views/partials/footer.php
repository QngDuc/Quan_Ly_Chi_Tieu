</main>
</div>

<!-- Toast Container -->
<div class="toast-container" id="toastContainer"></div>



<footer class="site-footer simple-footer">
    <div class="footer-inner">
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
                <a href="https://facebook.com/smartspending" target="_blank" rel="noopener" aria-label="Facebook" class="social-icon"><i class="bi bi-facebook"></i></a>
                <a href="https://twitter.com/smartspending" target="_blank" rel="noopener" aria-label="Twitter" class="social-icon"><i class="bi bi-twitter"></i></a>
                <a href="https://instagram.com/smartspending" target="_blank" rel="noopener" aria-label="Instagram" class="social-icon"><i class="bi bi-instagram"></i></a>
            </span>
        </div>
    </div>
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
$url = trim($_GET['url'] ?? '', '/');
$page = explode('/', $url)[0];

// Treat home and dashboard as the same page (dashboard)
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

<!-- Add Transaction Modal -->
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
                            <!-- Hardcoding some categories for now based on quan_ly_chi_tieu.sql -->
                            <option value="1">Lương</option> <!-- income -->
                            <option value="2">Freelance</option> <!-- income -->
                            <option value="3">Đầu tư</option> <!-- income -->
                            <option value="4">Ăn uống</option> <!-- expense -->
                            <option value="5">Di chuyển</option> <!-- expense -->
                            <option value="6">Mua sắm</option> <!-- expense -->
                            <option value="7">Giải trí</option> <!-- expense -->
                            <option value="8">Tiền điện nước</option> <!-- expense -->
                            <option value="9">Sức khỏe</option> <!-- expense -->
                            <option value="10">Giáo dục</option> <!-- expense -->
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
</body>

</html>