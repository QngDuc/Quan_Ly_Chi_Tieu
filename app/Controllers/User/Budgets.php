<?php

namespace App\Controllers\User;

use App\Core\Controllers;
use App\Core\Response;
use App\Core\ConnectDB;
use App\Middleware\AuthCheck;
use App\Middleware\CsrfProtection;

class Budgets extends Controllers
{
    private $budgetModel;

    public function __construct()
    {
        parent::__construct();
        AuthCheck::requireUser();
        $this->budgetModel = $this->model('Budget');
    }

    /**
     * Trang danh sách ngân sách
     */
    public function index()
    {
        $userId = $this->getCurrentUserId();

        $budgets = $this->budgetModel->getBudgetsWithSpending($userId, 'monthly');
        $summary = $this->budgetModel->getSummary($userId, 'monthly');
        $alerts = $this->budgetModel->getAlerts($userId, 'monthly');

        $categoryModel = $this->model('Category');
        $categories = $categoryModel ? $categoryModel->getAll($userId) : [];

        $data = [
            'title' => 'Ngân Sách',
            'budgets' => $budgets,
            'summary' => $summary,
            'alerts' => $alerts,
            'categories' => $categories,
            'csrf_token' => CsrfProtection::generateToken()
        ];

        $this->view('user/budgets', $data);
    }

    /**
     * API: Phân bổ thu nhập vào 6 hũ (VIP PRO LOGIC)
     */
    public function api_distribute_income()
    {
        if ($this->request->method() !== 'POST') {
            Response::errorResponse('Method Not Allowed', null, 405);
            return;
        }

        $userId = $this->request->session('user_id');
        if (!$userId) {
            Response::errorResponse('Unauthorized', null, 401);
            return;
        }

        $data = $this->request->json();
        $amount = isset($data['amount']) ? floatval($data['amount']) : 0;

        if ($amount <= 0) {
            Response::errorResponse('Số tiền phải lớn hơn 0');
            return;
        }

        $db = (new ConnectDB())->getConnection();

        try {
            $db->beginTransaction();

            // 1. Lấy cấu hình tỷ lệ hiện tại
            $stmt = $db->prepare("SELECT * FROM user_budget_settings WHERE user_id = ?");
            $stmt->execute([$userId]);
            $settings = $stmt->fetch(\PDO::FETCH_ASSOC);

            // Nếu chưa có, tạo mặc định
            if (!$settings) {
                $settings = ['nec_percent'=>55, 'ffa_percent'=>10, 'ltss_percent'=>10, 'edu_percent'=>10, 'play_percent'=>10, 'give_percent'=>5];
                // Insert default (Optional)
            }

            // 2. Chia tiền vào các hũ
            $jars = ['nec', 'ffa', 'ltss', 'edu', 'play', 'give'];
            $sqlWallet = "INSERT INTO user_wallets (user_id, jar_code, balance) VALUES (?, ?, ?) 
                          ON DUPLICATE KEY UPDATE balance = balance + VALUES(balance)";
            $stmtWallet = $db->prepare($sqlWallet);

            foreach ($jars as $jar) {
                $percent = $settings[$jar . '_percent'] ?? 0;
                $jarAmount = $amount * ($percent / 100);
                
                if ($jarAmount > 0) {
                    $stmtWallet->execute([$userId, $jar, $jarAmount]);
                }
            }

            // 3. Ghi lại lịch sử giao dịch (Transaction)
            // Lấy ID danh mục Lương (ID 16 - Check lại DB xem đúng ID chưa)
            $salaryCatId = 16; 
            
            $sqlTrans = "INSERT INTO transactions (user_id, category_id, amount, date, description, type) VALUES (?, ?, ?, CURDATE(), ?, 'income')";
            $stmtTrans = $db->prepare($sqlTrans);
            $stmtTrans->execute([$userId, $salaryCatId, $amount, 'Phân bổ thu nhập JARS']);

            $db->commit();
            Response::successResponse('Đã phân bổ thu nhập thành công!');

        } catch (\Exception $e) {
            $db->rollBack();
            Response::errorResponse('Lỗi hệ thống: ' . $e->getMessage());
        }
    }

    /**
     * API: Cập nhật tỷ lệ 6 hũ (nec, ffa, ltss, edu, play, give)
     */
    public function api_update_ratios()
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
        if (!$userId) {
            Response::errorResponse('Unauthorized', null, 401);
            return;
        }

        $data = $this->request->json();
        $keys = ['nec','ffa','ltss','edu','play','give'];
        $vals = [];
        $total = 0;
        foreach ($keys as $k) {
            $v = isset($data[$k]) ? intval($data[$k]) : 0;
            $vals[$k] = $v;
            $total += $v;
        }

        if ($total !== 100) {
            Response::errorResponse('Tổng tỷ lệ phải bằng 100%');
            return;
        }

        // Persist using Budget model helper
        try {
            $ok = $this->budgetModel->updateUserSmartSettings($userId, $vals['nec'], $vals['ffa'], $vals['ltss'], $vals['edu'], $vals['play'], $vals['give']);
            if ($ok) {
                Response::successResponse('Đã lưu cấu hình');
                return;
            }
            Response::errorResponse('Không thể lưu cấu hình');
        } catch (\Exception $e) {
            Response::errorResponse('Lỗi hệ thống: ' . $e->getMessage());
        }
    }
}