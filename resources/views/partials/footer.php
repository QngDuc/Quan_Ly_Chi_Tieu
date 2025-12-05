    </main>
</div>

<!-- Toast Container -->
<div class="toast-container" id="toastContainer"></div>

<footer>
    <div class="footer-content">
        <p>© 2025 SmartSpending. Bảo lưu mọi quyền.</p>
        <div class="footer-links">
            <a href="<?php echo BASE_URL; ?>/privacy">Chính sách bảo mật</a>
            <a href="<?php echo BASE_URL; ?>/terms">Điều khoản sử dụng</a>
            <a href="<?php echo BASE_URL; ?>/contact">Liên hệ</a>
        </div>
    </div>
</footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Chart.js for visualizations -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- Input Masking -->
    <script src="<?php echo BASE_URL; ?>/shared/input-masking.js"></script>
    
    <!-- App.js - Shared utilities -->
    <script src="<?php echo BASE_URL; ?>/shared/app.js"></script>
    
    <!-- Page-specific JavaScript -->
    <?php
        $url = trim($_GET['url'] ?? '', '/');
        $page = explode('/', $url)[0];
        
        // Treat home and dashboard as the same page (dashboard)
        if ($page === 'home' || $page === '') {
            $page = 'dashboard';
        }
        
        // Load page-specific JS if it exists
        if ($page && in_array($page, ['dashboard', 'transactions', 'budgets', 'goals', 'reports', 'profile'])) {
            echo '<script src="' . BASE_URL . '/user/' . $page . '/' . $page . '.js"></script>' . "\n";
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
                            </div>            </div>
        </div>
    </div>
</body>
</html>