<?php
namespace App\Http\Controllers\User;

use App\Core\Controllers;
use App\Core\Response;
use App\Middleware\CsrfProtection;
use App\Middleware\AuthCheck;
use App\Services\Validator;
use Exception;

/**
 * Goals Controller
 * Quản lý mục tiêu tiết kiệm
 */
class Goals extends Controllers {
    
    private $goalModel;
    private $transactionModel;
    
    public function __construct() {
        parent::__construct();
        // Kiểm tra quyền user
        AuthCheck::requireUser();
        $this->goalModel = $this->model('Goal');
        $this->transactionModel = $this->model('Transaction');
    }
    
    /**
     * Trang danh sách mục tiêu
     */
    public function index() {
        $userId = $this->getCurrentUserId();
        
        // Lấy danh sách mục tiêu
        $goals = $this->goalModel->getByUserId($userId);
        
        // Lấy thống kê
        $statistics = $this->goalModel->getStatistics($userId);
        
        // Chuẩn bị dữ liệu cho view
        $data = [
            'title' => 'Mục Tiêu Tiết Kiệm',
            'goals' => $goals,
            'statistics' => $statistics,
            'csrf_token' => CsrfProtection::generateToken()
        ];
        
        $this->view('user/goals', $data);
    }
    
    /**
     * API: Lấy danh sách mục tiêu
     */
    public function api_get_goals() {
        try {
            $userId = $this->getCurrentUserId();
            $goals = $this->goalModel->getByUserId($userId);
            $statistics = $this->goalModel->getStatistics($userId);
            
            Response::successResponse('Goals retrieved successfully', [
                'goals' => $goals,
                'statistics' => $statistics
            ]);
            
        } catch (Exception $e) {
            Response::errorResponse('Failed to retrieve goals: ' . $e->getMessage());
        }
    }
    
    /**
     * API: Tạo mục tiêu mới
     */
    public function api_create_goal() {
        if ($this->request->method() !== 'POST') {
            Response::errorResponse('Method not allowed', null, 405);
            return;
        }
        
        // Verify CSRF token
        CsrfProtection::verify();
        
        try {
            // Validate input
            $validator = new Validator();
            
            $rules = [
                'name' => ['required' => true, 'max_length' => 255],
                'target_amount' => ['required' => true, 'numeric' => true, 'min' => 0],
                'deadline' => ['required' => true, 'date' => true]
            ];
            
            $data = $this->request->all();

            if (!$validator->validate($data, $rules)) {
                Response::errorResponse('Validation failed', $validator->getErrors(), 400);
                return;
            }
            
            // Chuẩn bị dữ liệu
            $goalData = [
                'user_id' => $this->getCurrentUserId(),
                'name' => $validator->sanitize($data['name']),
                'description' => $validator->sanitize($data['description'] ?? ''),
                'target_amount' => floatval($data['target_amount']),
                'deadline' => $data['deadline'],
                'status' => 'active'
            ];
            
            // Tạo mục tiêu
            if ($this->goalModel->create($goalData)) {
                Response::successResponse('Goal created successfully');
            } else {
                Response::errorResponse('Failed to create goal');
            }
            
        } catch (Exception $e) {
            Response::errorResponse('Error: ' . $e->getMessage());
        }
    }
    
    /**
     * API: Cập nhật mục tiêu
     */
    public function api_update_goal($id = null) {
        if ($this->request->method() !== 'POST') {
            Response::errorResponse('Method not allowed', null, 405);
            return;
        }
        
        if (!$id) {
            Response::errorResponse('Goal ID is required', null, 400);
            return;
        }
        
        // Verify CSRF token
        CsrfProtection::verify();
        
        try {
            $userId = $this->getCurrentUserId();
            
            // Kiểm tra quyền sở hữu
            $goal = $this->goalModel->getById($id, $userId);
            if (!$goal) {
                Response::errorResponse('Goal not found', null, 404);
                return;
            }
            
            // Validate input
            $validator = new Validator();
            
            $rules = [
                'name' => ['required' => true, 'max_length' => 255],
                'target_amount' => ['required' => true, 'numeric' => true, 'min' => 0],
                'deadline' => ['required' => true, 'date' => true]
            ];
            
            $data = $this->request->all();

            if (!$validator->validate($data, $rules)) {
                Response::errorResponse('Validation failed', $validator->getErrors(), 400);
                return;
            }
            
            // Chuẩn bị dữ liệu
            $updateData = [
                'name' => $validator->sanitize($data['name']),
                'description' => $validator->sanitize($data['description'] ?? ''),
                'target_amount' => floatval($data['target_amount']),
                'deadline' => $data['deadline'],
                'status' => $data['status'] ?? 'active'
            ];
            
            // Cập nhật mục tiêu
            if ($this->goalModel->update($id, $userId, $updateData)) {
                Response::successResponse('Goal updated successfully');
            } else {
                Response::errorResponse('Failed to update goal');
            }
            
        } catch (Exception $e) {
            Response::errorResponse('Error: ' . $e->getMessage());
        }
    }
    
    /**
     * API: Xóa mục tiêu
     */
    public function api_delete_goal($id = null) {
        if ($this->request->method() !== 'POST') {
            Response::errorResponse('Method not allowed', null, 405);
            return;
        }
        
        if (!$id) {
            Response::errorResponse('Goal ID is required', null, 400);
            return;
        }
        
        // Verify CSRF token
        CsrfProtection::verify();
        
        try {
            $userId = $this->getCurrentUserId();
            
            // Kiểm tra quyền sở hữu
            $goal = $this->goalModel->getById($id, $userId);
            if (!$goal) {
                Response::errorResponse('Goal not found', null, 404);
                return;
            }
            
            // Xóa mục tiêu
            if ($this->goalModel->delete($id, $userId)) {
                Response::successResponse('Goal deleted successfully');
            } else {
                Response::errorResponse('Failed to delete goal');
            }
            
        } catch (Exception $e) {
            Response::errorResponse('Error: ' . $e->getMessage());
        }
    }
    
    /**
     * API: Liên kết transaction với goal
     */
    public function api_link_transaction() {
        if ($this->request->method() !== 'POST') {
            Response::errorResponse('Method not allowed', null, 405);
            return;
        }
        
        // Verify CSRF token
        CsrfProtection::verify();
        
        try {
            $goalId = $this->request->post('goal_id');
            $transactionId = $this->request->post('transaction_id');
            
            if (!$goalId || !$transactionId) {
                Response::errorResponse('Goal ID and Transaction ID are required', null, 400);
                return;
            }
            
            $userId = $this->getCurrentUserId();
            
            // Verify ownership
            $goal = $this->goalModel->getById($goalId, $userId);
            if (!$goal) {
                Response::errorResponse('Goal not found', null, 404);
                return;
            }
            
            // Link transaction
            if ($this->goalModel->linkTransaction($goalId, $transactionId)) {
                Response::successResponse('Transaction linked to goal successfully');
            } else {
                Response::errorResponse('Failed to link transaction');
            }
            
        } catch (Exception $e) {
            Response::errorResponse('Error: ' . $e->getMessage());
        }
    }
    
    /**
     * API: Cập nhật trạng thái mục tiêu
     */
    public function api_update_status($id = null) {
        if ($this->request->method() !== 'POST') {
            Response::errorResponse('Method not allowed', null, 405);
            return;
        }
        
        if (!$id) {
            Response::errorResponse('Goal ID is required', null, 400);
            return;
        }
        
        // Verify CSRF token
        CsrfProtection::verify();
        
        try {
            $userId = $this->getCurrentUserId();
            $status = $this->request->post('status');
            
            if (!in_array($status, ['active', 'completed', 'cancelled'])) {
                Response::errorResponse('Invalid status', null, 400);
                return;
            }
            
            // Verify ownership
            $goal = $this->goalModel->getById($id, $userId);
            if (!$goal) {
                Response::errorResponse('Goal not found', null, 404);
                return;
            }
            
            // Update status
            if ($this->goalModel->updateStatus($id, $userId, $status)) {
                Response::successResponse('Goal status updated successfully');
            } else {
                Response::errorResponse('Failed to update goal status');
            }
            
        } catch (Exception $e) {
            Response::errorResponse('Error: ' . $e->getMessage());
        }
    }
}
