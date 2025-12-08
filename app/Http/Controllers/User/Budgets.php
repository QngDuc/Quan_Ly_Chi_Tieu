<?php
namespace App\Http\Controllers\User;

use App\Core\Controllers;
use App\Core\Response;
use App\Services\Validator;
use App\Middleware\CsrfProtection;
use App\Middleware\AuthCheck;

class Budgets extends Controllers
{
    protected $db;
    protected $budgetModel;
    protected $categoryModel;

    public function __construct()
    {
        parent::__construct();
        // Kiểm tra quyền user (ngăn admin truy cập)
        AuthCheck::requireUser();
        $this->db = (new \App\Core\ConnectDB())->getConnection();
        $this->budgetModel = new \App\Models\Budget();
        $this->categoryModel = new \App\Models\Category();
    }

    /**
     * Display budgets index page (Money Lover style)
     */
    public function index()
    {
        $data = [
            'title' => 'Quản lý Ngân sách'
        ];
        $this->view('user/budgets', $data);
    }

    /**
     * API: Get all budgets with spending data
     * GET /budgets/api_get_all
     */
    public function api_get_all()
    {
        if ($this->request->method() !== 'GET') {
            Response::errorResponse('Method Not Allowed', null, 405);
            return;
        }

        try {
            $userId = $this->getCurrentUserId();
            $period = $_GET['period'] ?? 'monthly'; // monthly, weekly, yearly
            
            // Get budgets with spending data
            $budgets = $this->budgetModel->getBudgetsWithSpending($userId, $period);
            
            // Calculate summary
            $totalBudget = 0;
            $totalSpent = 0;
            $activeCount = 0;
            
            foreach ($budgets as $budget) {
                $totalBudget += $budget['amount'];
                $totalSpent += $budget['spent'];
                if ($budget['is_active']) {
                    $activeCount++;
                }
            }
            
            Response::successResponse('Lấy danh sách ngân sách thành công', [
                'budgets' => $budgets,
                'summary' => [
                    'total_budget' => $totalBudget,
                    'total_spent' => $totalSpent,
                    'remaining' => $totalBudget - $totalSpent,
                    'active_count' => $activeCount,
                    'period' => $period
                ]
            ]);
        } catch (\Exception $e) {
            // Write detailed error to a log file for debugging
            try {
                $logDir = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'logs';
                if (!is_dir($logDir)) @mkdir($logDir, 0755, true);
                $msg = '[' . date('Y-m-d H:i:s') . '] api_get_all error: ' . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n\n";
                @file_put_contents($logDir . DIRECTORY_SEPARATOR . 'budgets_error.log', $msg, FILE_APPEND);
            } catch (\Exception $ex) {
                // ignore logging failures
            }
            Response::errorResponse('Lỗi: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * API: Get expense categories for budget creation
     * GET /budgets/api_get_categories
     */
    public function api_get_categories()
    {
        if ($this->request->method() !== 'GET') {
            Response::errorResponse('Method Not Allowed', null, 405);
            return;
        }

        try {
            $userId = $this->getCurrentUserId();
            $categories = $this->categoryModel->getExpenseCategories($userId);
            
            Response::successResponse('Lấy danh sách danh mục thành công', [
                'categories' => $categories
            ]);
        } catch (\Exception $e) {
            // Write detailed error to a log file for debugging
            try {
                $logDir = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'logs';
                if (!is_dir($logDir)) @mkdir($logDir, 0755, true);
                $msg = '[' . date('Y-m-d H:i:s') . '] api_get_categories error: ' . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n\n";
                @file_put_contents($logDir . DIRECTORY_SEPARATOR . 'budgets_error.log', $msg, FILE_APPEND);
            } catch (\Exception $ex) {
                // ignore logging failures
            }
            Response::errorResponse('Lỗi: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * API: Create a new budget
     * POST /budgets/api_create
     */
    public function api_create()
    {
        if ($this->request->method() !== 'POST') {
            Response::errorResponse('Method Not Allowed', null, 405);
            return;
        }

        CsrfProtection::verify();

        try {
            $userId = $this->getCurrentUserId();
            $input = $this->request->json();
            
            // Validate input (manual)
            $errors = [];

            if (!isset($input['category_id']) || !is_numeric($input['category_id'])) {
                $errors['category_id'][] = 'category_id phải là số và không được để trống';
            }
            if (!isset($input['amount']) || !is_numeric($input['amount']) || floatval($input['amount']) < 1) {
                $errors['amount'][] = 'amount phải là số >= 1';
            }
            if (!isset($input['period']) || !in_array($input['period'], ['weekly', 'monthly', 'yearly'], true)) {
                $errors['period'][] = 'period phải là một trong: weekly, monthly, yearly';
            }

            if (!empty($errors)) {
                Response::errorResponse('Dữ liệu không hợp lệ', $errors, 400);
                return;
            }

            // Calculate start_date and end_date based on period
            $period = $input['period'];
            $now = new \DateTime();
            
            switch ($period) {
                case 'weekly':
                    $startDate = $now->modify('monday this week')->format('Y-m-d');
                    $endDate = (clone $now)->modify('sunday this week')->format('Y-m-d');
                    break;
                case 'yearly':
                    $startDate = $now->format('Y-01-01');
                    $endDate = $now->format('Y-12-31');
                    break;
                case 'monthly':
                default:
                    $startDate = $now->format('Y-m-01');
                    $endDate = $now->format('Y-m-t');
                    break;
            }

            $data = [
                'user_id' => $userId,
                'category_id' => $input['category_id'],
                'amount' => floatval($input['amount']),
                'period' => $period,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'alert_threshold' => $input['alert_threshold'] ?? 80,
                'is_active' => 1
            ];

            $budgetId = $this->budgetModel->create($data);
            
            if ($budgetId) {
                Response::successResponse('Tạo ngân sách thành công', ['budget_id' => $budgetId]);
            } else {
                Response::errorResponse('Không thể tạo ngân sách');
            }
        } catch (\Exception $e) {
            Response::errorResponse('Lỗi: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * API: Update a budget
     * POST /budgets/api_update/{id}
     */
    public function api_update($id)
    {
        if ($this->request->method() !== 'POST') {
            Response::errorResponse('Method Not Allowed', null, 405);
            return;
        }

        CsrfProtection::verify();

        try {
            $userId = $this->getCurrentUserId();
            $input = $this->request->json();
            
            // Validate input (manual)
            $errors = [];
            if (!isset($input['amount']) || !is_numeric($input['amount']) || floatval($input['amount']) < 1) {
                $errors['amount'][] = 'amount phải là số >= 1';
            }

            if (!empty($errors)) {
                Response::errorResponse('Dữ liệu không hợp lệ', $errors, 400);
                return;
            }

            // Check if budget exists
            $budget = $this->budgetModel->getById($id);
            if (!$budget || $budget['user_id'] != $userId) {
                Response::errorResponse('Không tìm thấy ngân sách', null, 404);
                return;
            }

            $data = [
                'amount' => floatval($input['amount']),
                'alert_threshold' => $input['alert_threshold'] ?? $budget['alert_threshold'],
                'is_active' => isset($input['is_active']) ? intval($input['is_active']) : $budget['is_active']
            ];

            $result = $this->budgetModel->update($id, $data);
            
            if ($result) {
                Response::successResponse('Cập nhật ngân sách thành công');
            } else {
                Response::errorResponse('Không thể cập nhật ngân sách');
            }
        } catch (\Exception $e) {
            Response::errorResponse('Lỗi: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * API: Delete a budget
     * POST /budgets/api_delete/{id}
     */
    public function api_delete($id)
    {
        if ($this->request->method() !== 'POST') {
            Response::errorResponse('Method Not Allowed', null, 405);
            return;
        }

        CsrfProtection::verify();

        try {
            $userId = $this->getCurrentUserId();
            
            // Check if budget exists
            $budget = $this->budgetModel->getById($id);
            if (!$budget || $budget['user_id'] != $userId) {
                Response::errorResponse('Không tìm thấy ngân sách', null, 404);
                return;
            }
            
            $result = $this->budgetModel->delete($id);
            
            if ($result) {
                Response::successResponse('Xóa ngân sách thành công');
            } else {
                Response::errorResponse('Không thể xóa ngân sách');
            }
        } catch (\Exception $e) {
            Response::errorResponse('Lỗi: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * API: Toggle budget active status
     * POST /budgets/api_toggle/{id}
     */
    public function api_toggle($id)
    {
        if ($this->request->method() !== 'POST') {
            Response::errorResponse('Method Not Allowed', null, 405);
            return;
        }

        CsrfProtection::verify();

        try {
            $userId = $this->getCurrentUserId();
            
            // Check if budget exists
            $budget = $this->budgetModel->getById($id);
            if (!$budget || $budget['user_id'] != $userId) {
                Response::errorResponse('Không tìm thấy ngân sách', null, 404);
                return;
            }
            
            $result = $this->budgetModel->update($id, [
                'is_active' => $budget['is_active'] ? 0 : 1
            ]);
            
            if ($result) {
                Response::successResponse('Cập nhật trạng thái thành công', [
                    'is_active' => !$budget['is_active']
                ]);
            } else {
                Response::errorResponse('Không thể cập nhật trạng thái');
            }
        } catch (\Exception $e) {
            Response::errorResponse('Lỗi: ' . $e->getMessage(), null, 500);
        }
    }
}
