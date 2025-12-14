<?php

namespace App\Controllers\User;

use App\Core\Controllers;
use App\Core\Response;
use App\Services\Validator;
use App\Middleware\CsrfProtection;
use App\Middleware\AuthCheck;
use App\Services\FinancialUtils;
use App\Core\ConnectDB;
use App\Core\SessionManager;

class Transactions extends Controllers
{
    private $transactionModel;
    private $categoryModel;
    private $budgetModel;

    public function __construct()
    {
        parent::__construct();
        // Kiểm tra quyền user
        AuthCheck::requireUser();
        $this->transactionModel = $this->model('Transaction');
        $this->categoryModel = $this->model('Category');
        $this->budgetModel = $this->model('Budget');
    }

    public function index($range = null, $categoryId = 'all', $page = 1)
    {
        $userId = $this->getCurrentUserId();

        // Default to current month if no range provided
        if (!$range) {
            $range = date('Y-m');
        }

        $filters = [
            'range' => $range,
            'category_id' => ($categoryId === 'all') ? null : $categoryId,
        ];

        // Pagination settings (max rows per page)
        $perPage = 6;
        $offset = ($page - 1) * $perPage;

        $allTransactions = $this->transactionModel->getAllByUser($userId, $filters);
        $totalTransactions = count($allTransactions);
        $totalPages = ceil($totalTransactions / $perPage);

        // Get paginated transactions
        $transactions = array_slice($allTransactions, $offset, $perPage);
        $categories = $this->categoryModel->getAll();

        $data = [
            'title' => 'Tất cả Giao dịch',
            'transactions' => $transactions,
            'categories' => $categories,
            'current_range' => $range,
            'current_category' => $categoryId,
            'current_page' => $page,
            'total_pages' => $totalPages,
            'total_transactions' => $totalTransactions,
            'per_page' => $perPage
        ];

        $this->view('user/transactions', $data);
    }

    public function add()
    {
        if ($this->request->method() === 'POST') {
            $userId = $this->getCurrentUserId();

            // Sanitize and prepare data from the form
            $type = $this->request->post('type', null) ?? 'expense';
            $amount = $this->request->post('amount', null);
            $categoryId = $this->request->post('category_id', null);
            $date = $this->request->post('date', null) ?? date('Y-m-d');
            $description = trim((string)($this->request->post('description', null) ?? ''));

            // Basic validation
            if ($amount > 0 && !empty($categoryId)) {
                // Use TransactionController to apply JARS logic and wallet checks
                $txController = new TransactionController();
                $res = $txController->createTransaction($userId, [
                    'type' => $type,
                    'amount' => $amount,
                    'category_id' => $categoryId,
                    'date' => $date,
                    'description' => $description
                ]);
                // Flash result message for web UI
                $session = new SessionManager();
                if (is_array($res) && isset($res['success']) && $res['success'] === true) {
                    $session->flash('toast', ['type' => 'success', 'message' => 'Thêm giao dịch thành công']);
                } else {
                    $msg = is_array($res) && isset($res['message']) ? $res['message'] : 'Không thể thêm giao dịch';
                    $session->flash('toast', ['type' => 'error', 'message' => $msg]);
                }
            }
        }

        // Redirect back to transactions page to see the result
        $this->redirect('/transactions');
    }


    public function api_add()
    {
        if ($this->request->method() !== 'POST') {
            Response::errorResponse('Method Not Allowed', null, 405);
            return;
        }

        try {
            // Verify CSRF token
            CsrfProtection::verify();

            $userId = $this->getCurrentUserId();
            $data = $this->request->json();

            $validator = new Validator();
            if (!$validator->validateTransaction($data)) {
                Response::errorResponse($validator->getFirstError(), $validator->getErrors());
                return;
            }

            $validData = $validator->getData();
            // Cờ xác nhận từ Modal (nếu người dùng đã bấm "Tiếp tục" ở lần cảnh báo trước)
            $isConfirmed = isset($data['confirmed']) && $data['confirmed'] === true;

            // === [NÂNG CẤP] LOGIC KIỂM TRA NGÂN SÁCH ===
            if (($validData['type'] ?? 'expense') === 'expense') {

                // 1. HARD CHECK: Kiểm tra tổng số dư khả dụng (Bắt buộc - để tránh âm ví)
                $currentBalance = $this->transactionModel->getTotalBalance($userId);
                if ($currentBalance < $validData['amount']) {
                    Response::errorResponse('Giao dịch thất bại: Số dư tài khoản hiện tại (' . number_format($currentBalance) . 'đ) không đủ để thanh toán khoản này.');
                    return;
                }

                // 2. SOFT CHECK: Kiểm tra ngân sách & Cấu hình người dùng
                // Load User Model để lấy cài đặt thông báo
                $userModel = $this->model('User');
                $currentUser = $userModel->getUserById($userId);

                // [QUAN TRỌNG] Chỉ chạy kiểm tra ngân sách nếu user đang BẬT tùy chọn này
                if ($currentUser && isset($currentUser['notify_budget_limit']) && $currentUser['notify_budget_limit'] == 1) {

                    if (!$isConfirmed) {
                        $budgetModel = $this->model('Budget');
                        $budgets = $budgetModel->getBudgetsWithSpending($userId, 'monthly');

                        foreach ($budgets as $budget) {
                            if ($budget['category_id'] == $validData['category_id']) {
                                $spentPositive = abs($budget['spent']);
                                $newTotal = $spentPositive + $validData['amount'];

                                // Nếu chi tiêu vượt quá hạn mức ngân sách
                                if ($newTotal > $budget['amount']) {
                                    // Trả về mã đặc biệt để Frontend hiện Modal cảnh báo
                                    Response::successResponse('Cảnh báo vượt ngân sách', [
                                        'requires_confirmation' => true,
                                        'message' => "Hạn mức '" . $budget['category_name'] . "' chỉ còn " . number_format($budget['remaining']) . "đ. Giao dịch này sẽ khiến bạn bị âm ngân sách mục này. \n\nBạn có muốn dùng số dư dư thừa từ các khoản khác để tiếp tục thanh toán?"
                                    ]);
                                    return; // Dừng lại, chờ xác nhận
                                }
                                break;
                            }
                        }
                    }
                }

                // 3. HARD JAR CHECK: Ensure the expense does not exceed the user's jar allocation for that period
                // This is a strict rule (no confirmation) — calculates user's income for the month and applies jar percentage
                $category = $this->categoryModel->getById($validData['category_id'], $userId);
                $groupType = $category['group_type'] ?? 'nec';

                // Map group_type to jar index used by Budget::getUserJars()
                $groupMap = [
                    'nec' => 0,
                    'ffa' => 1,
                    'ltss' => 2,
                    'edu' => 3,
                    'play' => 4,
                    'give' => 5
                ];

                $jarIndex = $groupMap[$groupType] ?? 0;

                // Determine month period from the transaction date
                $txMonth = substr($validData['date'], 0, 7); // YYYY-MM
                list($startDate, $endDate) = array_slice(FinancialUtils::getPeriodDates($txMonth), 0, 2);

                // Total income in the month
                $totals = $this->transactionModel->getTotalsForPeriod($userId, $startDate, $endDate);
                $incomeForMonth = isset($totals['income']) ? floatval($totals['income']) : 0.0;

                $budgetModel = $this->model('Budget');
                $jars = $budgetModel->getUserJars($userId);
                $jarPercent = isset($jars[$jarIndex]) ? floatval($jars[$jarIndex]) : 0.0;

                $jarAllowance = ($incomeForMonth * $jarPercent) / 100.0;

                // Calculate spent in this jar (group_type) within the same period
                // Use a DB connection directly to query spent per group_type
                $db = (new ConnectDB())->getConnection();
                $stmt = $db->prepare(
                    "SELECT COALESCE(SUM(ABS(t.amount)),0) as spent FROM transactions t JOIN categories c ON t.category_id = c.id WHERE t.user_id = ? AND t.type = 'expense' AND c.group_type = ? AND t.date BETWEEN ? AND ?"
                );
                $stmt->execute([$userId, $groupType, $startDate, $endDate]);
                $row = $stmt->fetch(\PDO::FETCH_ASSOC);
                $spentInJar = isset($row['spent']) ? floatval($row['spent']) : 0.0;

                $remainingInJar = $jarAllowance - $spentInJar;

                if ($validData['amount'] > $remainingInJar) {
                    Response::errorResponse('Giao dịch thất bại: Số tiền vượt quá hạn mức của lọ "' . strtoupper($groupType) . '" cho kỳ này. Còn lại: ' . number_format(max(0, $remainingInJar)) . 'đ');
                    return;
                }
            }

            // 3. LƯU GIAO DỊCH (Nếu các kiểm tra trên đều qua hoặc đã được confirm hoặc user tắt thông báo)
            // Delegate creation to TransactionController which implements JARS logic
            $txController = new TransactionController();
            $result = $txController->createTransaction($userId, [
                'type' => $validData['type'] ?? 'expense',
                'amount' => $validData['amount'],
                'category_id' => $validData['category_id'],
                'date' => $validData['date'],
                'description' => $validData['description'] ?? ''
            ]);

            // Logic phụ: Cập nhật ngân sách 'Cho vay' nếu là giao dịch Thu nợ/Trả nợ
            $debtCategoryIds = [44, 42]; // id Thu nợ, Trả nợ
            $loanCategoryId = 13; // id Cho vay
            if (in_array($validData['category_id'], $debtCategoryIds)) {
                $budgetModel = $this->model('Budget');
                $budgets = $budgetModel->getBudgetsWithSpending($userId, 'monthly');
                foreach ($budgets as $budget) {
                    if ($budget['category_id'] == $loanCategoryId) {
                        // Create adjustment using TransactionController to keep consistent JARS handling
                        $txController->createTransaction($userId, [
                            'type' => 'expense',
                            'amount' => abs($validData['amount']),
                            'category_id' => $loanCategoryId,
                            'date' => $validData['date'],
                            'description' => 'Thu nợ tự động (Điều chỉnh ngân sách)'
                        ]);
                        break;
                    }
                }
            }

            if ($result) {
                // Tính toán cập nhật số dư các hũ cho kỳ của giao dịch vừa thêm
                try {
                    $txMonth = substr($validData['date'], 0, 7);
                    list($startDate, $endDate) = array_slice(FinancialUtils::getPeriodDates($txMonth), 0, 2);

                    $totals = $this->transactionModel->getTotalsForPeriod($userId, $startDate, $endDate);
                    $incomeForMonth = isset($totals['income']) ? floatval($totals['income']) : 0.0;

                    $budgetModel = $this->model('Budget');
                    $jars = $budgetModel->getUserJars($userId);
                    $jarKeys = ['nec','ffa','ltss','edu','play','give'];

                    $db = (new ConnectDB())->getConnection();
                    $jarUpdates = [];
                    foreach ($jarKeys as $idx => $key) {
                        $percent = isset($jars[$idx]) ? floatval($jars[$idx]) : 0.0;
                        $allowance = ($incomeForMonth * $percent) / 100.0;
                        $stmt = $db->prepare("SELECT COALESCE(SUM(ABS(t.amount)),0) as spent FROM transactions t JOIN categories c ON t.category_id = c.id WHERE t.user_id = ? AND t.type = 'expense' AND c.group_type = ? AND t.date BETWEEN ? AND ?");
                        $stmt->execute([$userId, $key, $startDate, $endDate]);
                        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
                        $spent = isset($row['spent']) ? floatval($row['spent']) : 0.0;
                        $remaining = $allowance - $spent;
                        $jarUpdates[$key] = [
                            'percent' => $percent,
                            'allowance' => $allowance,
                            'spent' => $spent,
                            'remaining' => $remaining
                        ];
                    }
                } catch (\Exception $e) {
                    $jarUpdates = null;
                }

                Response::successResponse('Thêm giao dịch thành công', ['jar_updates' => $jarUpdates]);
            } else {
                Response::errorResponse('Không thể thêm giao dịch');
            }
        } catch (\Exception $e) {
            Response::errorResponse('Lỗi: ' . $e->getMessage(), null, 500);
        }
    }

    public function api_update($id)
    {
        if ($this->request->method() !== 'POST') {
            Response::errorResponse('Method Not Allowed', null, 405);
            return;
        }

        try {
            // Verify CSRF token
            CsrfProtection::verify();

            $userId = $this->getCurrentUserId();
            $data = $this->request->json();
            if (!is_array($data)) {
                $data = [];
            }

            // Validate data
            $validator = new Validator();
            if (!$validator->validateTransaction($data)) {
                Response::errorResponse($validator->getFirstError(), $validator->getErrors());
                return;
            }

            // Get validated data
            $validData = $validator->getData();

            $result = $this->transactionModel->updateTransaction(
                $id,
                $userId,
                $validData['category_id'],
                $validData['amount'],
                $validData['type'] ?? 'expense',
                $validData['date'],
                $validData['description']
            );

            if ($result) {
                // Tính jar updates cho kỳ của giao dịch (dùng ngày mới từ validData)
                try {
                    $txMonth = substr($validData['date'], 0, 7);
                    list($startDate, $endDate) = array_slice(FinancialUtils::getPeriodDates($txMonth), 0, 2);

                    $totals = $this->transactionModel->getTotalsForPeriod($userId, $startDate, $endDate);
                    $incomeForMonth = isset($totals['income']) ? floatval($totals['income']) : 0.0;

                    $budgetModel = $this->model('Budget');
                    $jars = $budgetModel->getUserJars($userId);
                    $jarKeys = ['nec','ffa','ltss','edu','play','give'];

                    $db = (new ConnectDB())->getConnection();
                    $jarUpdates = [];
                    foreach ($jarKeys as $idx => $key) {
                        $percent = isset($jars[$idx]) ? floatval($jars[$idx]) : 0.0;
                        $allowance = ($incomeForMonth * $percent) / 100.0;
                        $stmt = $db->prepare("SELECT COALESCE(SUM(ABS(t.amount)),0) as spent FROM transactions t JOIN categories c ON t.category_id = c.id WHERE t.user_id = ? AND t.type = 'expense' AND c.group_type = ? AND t.date BETWEEN ? AND ?");
                        $stmt->execute([$userId, $key, $startDate, $endDate]);
                        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
                        $spent = isset($row['spent']) ? floatval($row['spent']) : 0.0;
                        $remaining = $allowance - $spent;
                        $jarUpdates[$key] = [
                            'percent' => $percent,
                            'allowance' => $allowance,
                            'spent' => $spent,
                            'remaining' => $remaining
                        ];
                    }
                } catch (\Exception $e) {
                    $jarUpdates = null;
                }

                Response::successResponse('Cập nhật thành công', ['id' => $id, 'jar_updates' => $jarUpdates]);
            } else {
                Response::errorResponse('Không thể cập nhật');
            }
        } catch (\Exception $e) {
            Response::errorResponse('Lỗi: ' . $e->getMessage(), null, 500);
        }
    }

    public function api_delete($id)
    {
        if ($this->request->method() !== 'POST') {
            Response::errorResponse('Method Not Allowed', null, 405);
            return;
        }

        try {
            // Verify CSRF token
            CsrfProtection::verify();

            $userId = $this->getCurrentUserId();

            // Lấy ngày của giao dịch trước khi xóa để tính lại số dư hũ
            $db = (new ConnectDB())->getConnection();
            $stmtGet = $db->prepare("SELECT date FROM transactions WHERE id = ? AND user_id = ? LIMIT 1");
            $stmtGet->execute([$id, $userId]);
            $txRow = $stmtGet->fetch(\PDO::FETCH_ASSOC);
            $txDate = $txRow['date'] ?? date('Y-m-d');

            $result = $this->transactionModel->deleteTransaction($id, $userId);

            if ($result) {
                // Tính jar updates cho kỳ của giao dịch đã xóa
                try {
                    $txMonth = substr($txDate, 0, 7);
                    list($startDate, $endDate) = array_slice(FinancialUtils::getPeriodDates($txMonth), 0, 2);

                    $totals = $this->transactionModel->getTotalsForPeriod($userId, $startDate, $endDate);
                    $incomeForMonth = isset($totals['income']) ? floatval($totals['income']) : 0.0;

                    $budgetModel = $this->model('Budget');
                    $jars = $budgetModel->getUserJars($userId);
                    $jarKeys = ['nec','ffa','ltss','edu','play','give'];

                    $jarUpdates = [];
                    foreach ($jarKeys as $idx => $key) {
                        $percent = isset($jars[$idx]) ? floatval($jars[$idx]) : 0.0;
                        $allowance = ($incomeForMonth * $percent) / 100.0;
                        $stmt = $db->prepare("SELECT COALESCE(SUM(ABS(t.amount)),0) as spent FROM transactions t JOIN categories c ON t.category_id = c.id WHERE t.user_id = ? AND t.type = 'expense' AND c.group_type = ? AND t.date BETWEEN ? AND ?");
                        $stmt->execute([$userId, $key, $startDate, $endDate]);
                        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
                        $spent = isset($row['spent']) ? floatval($row['spent']) : 0.0;
                        $remaining = $allowance - $spent;
                        $jarUpdates[$key] = [
                            'percent' => $percent,
                            'allowance' => $allowance,
                            'spent' => $spent,
                            'remaining' => $remaining
                        ];
                    }
                } catch (\Exception $e) {
                    $jarUpdates = null;
                }

                Response::successResponse('Xóa giao dịch thành công', ['id' => $id, 'jar_updates' => $jarUpdates]);
            } else {
                Response::errorResponse('Không thể xóa giao dịch');
            }
        } catch (\Exception $e) {
            Response::errorResponse('Lỗi: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * API endpoint to fetch transactions with filtering and pagination
     * GET /transactions/api_get_transactions?range=2025-01&category=all&page=1
     */
    public function api_get_transactions()
    {
        if ($this->request->method() !== 'GET') {
            Response::errorResponse('Method Not Allowed', null, 405);
            return;
        }

        try {
            $userId = $this->getCurrentUserId();

            // Get filters from query params
            $range = $this->request->get('range', date('Y-m'));
            $categoryId = $this->request->get('category', 'all');
            $page = (int)$this->request->get('page', 1);
            $perPage = (int)$this->request->get('per_page', 6);

            $filters = [
                'range' => $range,
                'category_id' => ($categoryId === 'all') ? null : $categoryId,
            ];

            // Get all matching transactions
            $allTransactions = $this->transactionModel->getAllByUser($userId, $filters);

            // Apply sort if requested (default newest)
            $sort = $this->request->get('sort', 'newest');
            if ($sort === 'oldest') {
                $allTransactions = array_reverse($allTransactions);
            }
            $totalTransactions = count($allTransactions);
            $totalPages = ceil($totalTransactions / $perPage);

            // Apply pagination
            $offset = ($page - 1) * $perPage;
            $transactions = array_slice($allTransactions, $offset, $perPage);

            // Format transactions for response
            $formattedTransactions = array_map(function ($t) {
                return [
                    'id' => $t['id'],
                    'amount' => $t['amount'],
                    'description' => $t['description'],
                    'transaction_date' => $t['date'],
                    'category_id' => $t['category_id'],
                    'category_name' => $t['category_name'],
                    'type' => $t['amount'] >= 0 ? 'income' : 'expense',
                    'formatted_amount' => number_format(abs($t['amount']), 0, ',', '.') . ' ₫',
                    'formatted_date' => date('d M Y', strtotime($t['date']))
                ];
            }, $transactions);

            Response::successResponse('Lấy danh sách giao dịch thành công', [
                'transactions' => $formattedTransactions,
                'pagination' => [
                    'current_page' => $page,
                    'total_pages' => $totalPages,
                    'total_items' => $totalTransactions,
                    'per_page' => $perPage,
                    'has_next' => $page < $totalPages,
                    'has_prev' => $page > 1
                ],
                'filters' => [
                    'range' => $range,
                    'category' => $categoryId
                ]
            ]);
        } catch (\Exception $e) {
            Response::errorResponse('Lỗi: ' . $e->getMessage(), null, 500);
        }
    }
}
