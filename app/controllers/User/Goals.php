<?php
namespace App\Controllers\User;

use App\Core\Controllers;
use App\Middleware\CsrfProtection;
use App\Middleware\AuthCheck;
use App\Core\ApiResponse;
use App\Services\Validator;
use Exception;

/**
 * Goals Controller
 * Quản lý mục tiêu tiết kiệm
 */
class Goals extends Controllers {
    
    private $goalModel;
    private $transactionModel;
    private $csrfProtection;
    
    public function __construct() {
        parent::__construct();
        // Kiểm tra quyền user
        AuthCheck::requireUser();
        $this->goalModel = $this->model('Goal');
        $this->transactionModel = $this->model('Transaction');
        $this->csrfProtection = new CsrfProtection();
    }
    
    /**
     * Trang danh sách mục tiêu
     */
    public function index() {
        // Kiểm tra đăng nhập
        if (!isset($_SESSION['user_id'])) {
            header('Location: ' . BASE_URL . '/auth/login');
            exit();
        }
        
        $userId = $_SESSION['user_id'];
        
        // Lấy danh sách mục tiêu
        $goals = $this->goalModel->getByUserId($userId);
        
        // Lấy thống kê
        $statistics = $this->goalModel->getStatistics($userId);
        
        // Chuẩn bị dữ liệu cho view
        $data = [
            'title' => 'Mục Tiêu Tiết Kiệm',
            'goals' => $goals,
            'statistics' => $statistics,
            'csrf_token' => $this->csrfProtection->generateToken()
        ];
        
        $this->view('user/goals', $data);
    }
    
    /**
     * API: Lấy danh sách mục tiêu
     */
    public function api_get_goals() {
        header('Content-Type: application/json');
        
        if (!isset($_SESSION['user_id'])) {
            echo ApiResponse::error('Unauthorized', 401);
            exit();
        }
        
        try {
            $userId = $_SESSION['user_id'];
            $goals = $this->goalModel->getByUserId($userId);
            $statistics = $this->goalModel->getStatistics($userId);
            
            echo ApiResponse::success([
                'goals' => $goals,
                'statistics' => $statistics
            ], 'Goals retrieved successfully');
            
        } catch (Exception $e) {
            echo ApiResponse::error('Failed to retrieve goals: ' . $e->getMessage());
        }
    }
    
    /**
     * API: Tạo mục tiêu mới
     */
    public function api_create_goal() {
        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo ApiResponse::error('Method not allowed', 405);
            exit();
        }
        
        if (!isset($_SESSION['user_id'])) {
            echo ApiResponse::error('Unauthorized', 401);
            exit();
        }
        
        // Verify CSRF token
        if (!$this->csrfProtection->validateToken($_POST['csrf_token'] ?? '')) {
            echo ApiResponse::error('Invalid CSRF token', 403);
            exit();
        }
        
        try {
            // Validate input
            $validator = new Validator();
            
            $rules = [
                'name' => ['required' => true, 'max_length' => 255],
                'target_amount' => ['required' => true, 'numeric' => true, 'min' => 0],
                'deadline' => ['required' => true, 'date' => true]
            ];
            
            if (!$validator->validate($_POST, $rules)) {
                echo ApiResponse::error('Validation failed', 400, $validator->getErrors());
                exit();
            }
            
            // Chuẩn bị dữ liệu
            $data = [
                'user_id' => $_SESSION['user_id'],
                'name' => $validator->sanitize($_POST['name']),
                'description' => $validator->sanitize($_POST['description'] ?? ''),
                'target_amount' => floatval($_POST['target_amount']),
                'deadline' => $_POST['deadline'],
                'status' => 'active'
            ];
            
            // Tạo mục tiêu
            if ($this->goalModel->create($data)) {
                echo ApiResponse::success(null, 'Goal created successfully');
            } else {
                echo ApiResponse::error('Failed to create goal');
            }
            
        } catch (Exception $e) {
            echo ApiResponse::error('Error: ' . $e->getMessage());
        }
    }
    
    /**
     * API: Cập nhật mục tiêu
     */
    public function api_update_goal($id = null) {
        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo ApiResponse::error('Method not allowed', 405);
            exit();
        }
        
        if (!isset($_SESSION['user_id'])) {
            echo ApiResponse::error('Unauthorized', 401);
            exit();
        }
        
        if (!$id) {
            echo ApiResponse::error('Goal ID is required', 400);
            exit();
        }
        
        // Verify CSRF token
        if (!$this->csrfProtection->validateToken($_POST['csrf_token'] ?? '')) {
            echo ApiResponse::error('Invalid CSRF token', 403);
            exit();
        }
        
        try {
            $userId = $_SESSION['user_id'];
            
            // Kiểm tra quyền sở hữu
            $goal = $this->goalModel->getById($id, $userId);
            if (!$goal) {
                echo ApiResponse::error('Goal not found', 404);
                exit();
            }
            
            // Validate input
            $validator = new Validator();
            
            $rules = [
                'name' => ['required' => true, 'max_length' => 255],
                'target_amount' => ['required' => true, 'numeric' => true, 'min' => 0],
                'deadline' => ['required' => true, 'date' => true]
            ];
            
            if (!$validator->validate($_POST, $rules)) {
                echo ApiResponse::error('Validation failed', 400, $validator->getErrors());
                exit();
            }
            
            // Chuẩn bị dữ liệu
            $data = [
                'name' => $validator->sanitize($_POST['name']),
                'description' => $validator->sanitize($_POST['description'] ?? ''),
                'target_amount' => floatval($_POST['target_amount']),
                'deadline' => $_POST['deadline'],
                'status' => $_POST['status'] ?? 'active'
            ];
            
            // Cập nhật mục tiêu
            if ($this->goalModel->update($id, $userId, $data)) {
                echo ApiResponse::success(null, 'Goal updated successfully');
            } else {
                echo ApiResponse::error('Failed to update goal');
            }
            
        } catch (Exception $e) {
            echo ApiResponse::error('Error: ' . $e->getMessage());
        }
    }
    
    /**
     * API: Xóa mục tiêu
     */
    public function api_delete_goal($id = null) {
        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo ApiResponse::error('Method not allowed', 405);
            exit();
        }
        
        if (!isset($_SESSION['user_id'])) {
            echo ApiResponse::error('Unauthorized', 401);
            exit();
        }
        
        if (!$id) {
            echo ApiResponse::error('Goal ID is required', 400);
            exit();
        }
        
        // Verify CSRF token
        if (!$this->csrfProtection->validateToken($_POST['csrf_token'] ?? '')) {
            echo ApiResponse::error('Invalid CSRF token', 403);
            exit();
        }
        
        try {
            $userId = $_SESSION['user_id'];
            
            // Kiểm tra quyền sở hữu
            $goal = $this->goalModel->getById($id, $userId);
            if (!$goal) {
                echo ApiResponse::error('Goal not found', 404);
                exit();
            }
            
            // Xóa mục tiêu
            if ($this->goalModel->delete($id, $userId)) {
                echo ApiResponse::success(null, 'Goal deleted successfully');
            } else {
                echo ApiResponse::error('Failed to delete goal');
            }
            
        } catch (Exception $e) {
            echo ApiResponse::error('Error: ' . $e->getMessage());
        }
    }
    
    /**
     * API: Liên kết transaction với goal
     */
    public function api_link_transaction() {
        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo ApiResponse::error('Method not allowed', 405);
            exit();
        }
        
        if (!isset($_SESSION['user_id'])) {
            echo ApiResponse::error('Unauthorized', 401);
            exit();
        }
        
        // Verify CSRF token
        if (!$this->csrfProtection->validateToken($_POST['csrf_token'] ?? '')) {
            echo ApiResponse::error('Invalid CSRF token', 403);
            exit();
        }
        
        try {
            $goalId = $_POST['goal_id'] ?? null;
            $transactionId = $_POST['transaction_id'] ?? null;
            
            if (!$goalId || !$transactionId) {
                echo ApiResponse::error('Goal ID and Transaction ID are required', 400);
                exit();
            }
            
            $userId = $_SESSION['user_id'];
            
            // Verify ownership
            $goal = $this->goalModel->getById($goalId, $userId);
            if (!$goal) {
                echo ApiResponse::error('Goal not found', 404);
                exit();
            }
            
            // Link transaction
            if ($this->goalModel->linkTransaction($goalId, $transactionId)) {
                echo ApiResponse::success(null, 'Transaction linked to goal successfully');
            } else {
                echo ApiResponse::error('Failed to link transaction');
            }
            
        } catch (Exception $e) {
            echo ApiResponse::error('Error: ' . $e->getMessage());
        }
    }
    
    /**
     * API: Cập nhật trạng thái mục tiêu
     */
    public function api_update_status($id = null) {
        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo ApiResponse::error('Method not allowed', 405);
            exit();
        }
        
        if (!isset($_SESSION['user_id'])) {
            echo ApiResponse::error('Unauthorized', 401);
            exit();
        }
        
        if (!$id) {
            echo ApiResponse::error('Goal ID is required', 400);
            exit();
        }
        
        // Verify CSRF token
        if (!$this->csrfProtection->validateToken($_POST['csrf_token'] ?? '')) {
            echo ApiResponse::error('Invalid CSRF token', 403);
            exit();
        }
        
        try {
            $userId = $_SESSION['user_id'];
            $status = $_POST['status'] ?? null;
            
            if (!in_array($status, ['active', 'completed', 'cancelled'])) {
                echo ApiResponse::error('Invalid status', 400);
                exit();
            }
            
            // Verify ownership
            $goal = $this->goalModel->getById($id, $userId);
            if (!$goal) {
                echo ApiResponse::error('Goal not found', 404);
                exit();
            }
            
            // Update status
            if ($this->goalModel->updateStatus($id, $userId, $status)) {
                echo ApiResponse::success(null, 'Goal status updated successfully');
            } else {
                echo ApiResponse::error('Failed to update goal status');
            }
            
        } catch (Exception $e) {
            echo ApiResponse::error('Error: ' . $e->getMessage());
        }
    }
}
