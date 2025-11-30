<?php
namespace App\Controllers;

use App\Core\Controllers;

class Transactions extends Controllers
{
    private $transactionModel;
    private $categoryModel;

    public function __construct()
    {
        parent::__construct();
        if (!$this->isLoggedIn()) {
            $this->redirect('/login_signup');
        }
        $this->transactionModel = $this->model('Transaction');
        $this->categoryModel = $this->model('Category');
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
        
        // Pagination settings
        $perPage = 7;
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

        $this->view('transactions/index', $data);
    }

    public function add()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $userId = $this->getCurrentUserId();
            
            // Sanitize and prepare data from the form
            $type = $_POST['type'] ?? 'expense';
            $amount = $_POST['amount'] ?? 0;
            $categoryId = $_POST['category_id'] ?? 0;
            $date = $_POST['date'] ?? date('Y-m-d');
            $description = trim($_POST['description'] ?? '');

            // Basic validation
            if ($amount > 0 && !empty($categoryId)) {
                $this->transactionModel->createTransaction(
                    $userId,
                    $categoryId,
                    $amount,
                    $type,
                    $date,
                    $description
                );
            }
        }

        // Redirect back to dashboard to see the result
        $this->redirect('/dashboard');
    }

    public function api_add()
    {
        // Clear any output buffer to prevent PHP errors from corrupting JSON
        if (ob_get_length()) ob_clean();
        
        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            exit;
        }

        try {
            $userId = $this->getCurrentUserId();
            
            // Get JSON data
            $data = json_decode(file_get_contents('php://input'), true);
            
            $type = $data['type'] ?? 'expense';
            $amount = floatval($data['amount'] ?? 0);
            $categoryId = intval($data['category_id'] ?? 0);
            $date = $data['date'] ?? date('Y-m-d');
            $description = trim($data['description'] ?? '');

            // Validation
            if ($amount <= 0) {
                echo json_encode(['success' => false, 'message' => 'Số tiền phải lớn hơn 0']);
                exit;
            }

            if (empty($categoryId)) {
                echo json_encode(['success' => false, 'message' => 'Vui lòng chọn danh mục']);
                exit;
            }

            // Create transaction
            $result = $this->transactionModel->createTransaction(
                $userId,
                $categoryId,
                $amount,
                $type,
                $date,
                $description
            );

            if ($result) {
                echo json_encode(['success' => true, 'message' => 'Thêm giao dịch thành công']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Không thể thêm giao dịch']);
            }
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()]);
        }
        exit;
    }

    public function api_update($id)
    {
        // Clear any output buffer to prevent PHP errors from corrupting JSON
        if (ob_get_length()) ob_clean();
        
        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            exit;
        }

        try {
            $userId = $this->getCurrentUserId();
            $data = json_decode(file_get_contents('php://input'), true);
            
            $type = $data['type'] ?? 'expense';
            $amount = floatval($data['amount'] ?? 0);
            $categoryId = intval($data['category_id'] ?? 0);
            $date = $data['transaction_date'] ?? $data['date'] ?? date('Y-m-d');
            $description = trim($data['description'] ?? '');

            if ($amount <= 0 || empty($categoryId)) {
                echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ']);
                exit;
            }

            $result = $this->transactionModel->updateTransaction($id, $userId, $categoryId, $amount, $type, $date, $description);

            if ($result) {
                echo json_encode(['success' => true, 'message' => 'Cập nhật thành công']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Không thể cập nhật']);
            }
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()]);
        }
        exit;
    }

    public function api_delete($id)
    {
        // Clear any output buffer to prevent PHP errors from corrupting JSON
        if (ob_get_length()) ob_clean();
        
        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            exit;
        }

        try {
            $userId = $this->getCurrentUserId();
            $result = $this->transactionModel->deleteTransaction($id, $userId);

            if ($result) {
                echo json_encode(['success' => true, 'message' => 'Xóa giao dịch thành công']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Không thể xóa giao dịch']);
            }
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()]);
        }
        exit;
    }
}
