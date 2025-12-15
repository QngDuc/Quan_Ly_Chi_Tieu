<?php

namespace App\Controllers\User;

use App\Core\Controllers;
use App\Core\Response;
use App\Core\SessionManager;
use App\Models\Budget;
use App\Middleware\CsrfProtection;

class Budgets extends Controllers
{
    private $budgetModel;

    public function __construct()
    {
        parent::__construct();
        $this->budgetModel = $this->model('Budget');
    }

    // ... (Giữ nguyên hàm index cũ của bạn nếu có) ...
   public function index()
    {
        $userId = $this->getCurrentUserId();

        // 1. Lấy dữ liệu 6 Hũ và Cài đặt tỷ lệ (QUAN TRỌNG)
        $walletModel = $this->model('Wallet');
        $wallets = $walletModel->getAllWallets($userId); 
        
        $settings = $this->budgetModel->getUserSmartSettings($userId); 

        // 2. Lấy danh sách ngân sách
        $budgets = $this->budgetModel->getBudgetsWithSpending($userId, 'monthly') ?? [];

        // 3. Lấy danh mục và lọc (Chỉ lấy 'expense' và loại bỏ 'Khoản Chi' + ID 1)
        $categoryModel = $this->model('Category'); 
        $allCategories = $categoryModel->getAll($userId);
        
        $expenseCategories = array_values(array_filter($allCategories, function($cat) {
            return isset($cat['type']) 
                   && $cat['type'] === 'expense' 
                   && $cat['name'] !== 'Khoản Chi' 
                   && $cat['id'] != 1;
        }));

        // 4. Truyền toàn bộ sang View
        $this->view('user/budgets', [
            'title' => 'Quản lý ngân sách',
            'budgets' => $budgets,
            'categories' => $expenseCategories,
            'wallets' => $wallets,   // Dùng để vẽ hũ nước
            'settings' => $settings  // Dùng để hiện %
        ]);
    }
    /**
     * API: Lấy danh sách ngân sách
     * URL: /budgets/api_get_all?period=monthly
     */
    public function api_get_all()
    {
        $userId = $this->getCurrentUserId();
        $period = isset($_GET['period']) ? $_GET['period'] : 'monthly';

        // Gọi đúng tên hàm trong Model: getBudgetsWithSpending
        $budgets = $this->budgetModel->getBudgetsWithSpending($userId, $period);
        
        Response::successResponse('Lấy dữ liệu thành công', [
            'budgets' => $budgets
        ]);
    }

    /**
     * API: Tạo ngân sách mới
     * URL: /budgets/api_create
     */
    public function api_create()
    {
        if ($this->request->method() !== 'POST') {
            Response::errorResponse('Method Not Allowed', null, 405);
            return;
        }

        try {
            CsrfProtection::verify();
        } catch (\Exception $e) {
            Response::errorResponse('CSRF token invalid', null, 403);
            return;
        }

        $userId = $this->getCurrentUserId();
        $data = $this->request->json();

        // Validate dữ liệu đầu vào
        $categoryId = $data['category_id'] ?? null;
        $amount = $data['amount'] ?? 0;
        $period = $data['period'] ?? 'monthly';

        if (!$categoryId || $amount <= 0) {
            Response::errorResponse('Vui lòng nhập danh mục và số tiền hợp lệ');
            return;
        }

        // Tính toán ngày bắt đầu/kết thúc dựa trên period (Logic khớp với Model)
        $now = new \DateTime();
        $startDate = '';
        $endDate = '';
        
        switch ($period) {
            case 'weekly':
                $startDate = (clone $now)->modify('monday this week')->format('Y-m-d');
                $endDate = (clone $now)->modify('sunday this week')->format('Y-m-d');
                break;
            case 'yearly':
                $startDate = $now->format('Y-01-01');
                $endDate = $now->format('Y-12-31');
                break;
            case 'daily':
                $startDate = $now->format('Y-m-d');
                $endDate = $now->format('Y-m-d');
                break;
            case 'monthly':
            default:
                $startDate = $now->format('Y-m-01');
                $endDate = $now->format('Y-m-t');
                break;
        }

        // Chuẩn bị dữ liệu để gọi hàm create($data) của Model
        $budgetData = [
            'user_id' => $userId,
            'category_id' => $categoryId,
            'amount' => $amount,
            'period' => $period,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'alert_threshold' => 80, // Mặc định cảnh báo ở 80%
            'is_active' => 1
        ];

        try {
            // Gọi đúng tên hàm trong Model: create
            $newId = $this->budgetModel->create($budgetData);
            
            if ($newId) {
                Response::successResponse('Tạo ngân sách thành công', ['id' => $newId]);
            } else {
                Response::errorResponse('Lỗi hệ thống khi tạo ngân sách');
            }
        } catch (\Exception $e) {
            // Model ném Exception nếu ngân sách đã tồn tại
            Response::errorResponse($e->getMessage());
        }
    }

    /**
     * API: Xóa ngân sách
     * URL: /budgets/api_delete/{id}
     */
    public function api_delete($id)
    {
        if ($this->request->method() !== 'POST') {
            Response::errorResponse('Method Not Allowed', null, 405);
            return;
        }
        
        try {
            CsrfProtection::verify();
        } catch (\Exception $e) {
            Response::errorResponse('CSRF token invalid', null, 403);
            return;
        }

        $userId = $this->getCurrentUserId();

        // Kiểm tra quyền sở hữu trước khi xóa (Bảo mật)
        $budget = $this->budgetModel->getById($id);
        if (!$budget || $budget['user_id'] != $userId) {
            Response::errorResponse('Ngân sách không tồn tại hoặc bạn không có quyền xóa');
            return;
        }
        
        // Gọi đúng tên hàm trong Model: delete
        $deleted = $this->budgetModel->delete($id);

        if ($deleted) {
            Response::successResponse('Đã xóa ngân sách');
        } else {
            Response::errorResponse('Không thể xóa ngân sách này');
        }
    }

    /**
     * API: Lấy dữ liệu biểu đồ xu hướng
     * URL: /budgets/api_get_trend
     */
    public function api_get_trend()
    {
        $userId = $this->getCurrentUserId();
        
        // Gọi đúng tên hàm trong Model: getMonthlyTrend
        $trendData = $this->budgetModel->getMonthlyTrend($userId);

        Response::successResponse('Success', [
            'trend' => $trendData
        ]);
    }

    /**
     * API: Lấy tỷ lệ 6 hũ (JARS) cho người dùng
     * URL: /budgets/api_get_jars
     */
    public function api_get_jars()
    {
        $userId = $this->getCurrentUserId();
        $jars = $this->budgetModel->getUserJars($userId);
        $settings = $this->budgetModel->getUserSmartSettings($userId);

        Response::successResponse('Success', [
            'jars' => $jars,
            'settings' => $settings
        ]);
    }
}