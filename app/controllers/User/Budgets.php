<?php
namespace App\Controllers\User;

use App\Core\Controllers;
use App\Core\ApiResponse;
use App\Services\Validator;
use App\Middleware\CsrfProtection;

class Budgets extends Controllers
{
    private $db;

    public function __construct()
    {
        parent::__construct();
        if (!$this->isLoggedIn()) {
            $this->redirect('/login_signup');
        }
        $this->db = (new \App\Core\ConnectDB())->getConnection();
    }

    /**
     * Display budgets index page with 6 Jars Method
     */
    public function index($period = null)
    {
        $userId = $this->getCurrentUserId();
        
        if (!$period) {
            $period = date('Y-m');
        }

        // Get 6 Jars allocation data
        $jarSummary = $this->getJarSummary($userId, $period);
        
        // Get total income for the month
        $totalIncome = $this->getTotalIncome($userId, $period);
        
        $data = [
            'title' => 'Quản lý Ngân sách - Phương pháp 6 Lọ',
            'jar_summary' => $jarSummary,
            'total_income' => $totalIncome,
            'current_period' => $period
        ];

        $this->view('budgets', $data);
    }
    
    /**
     * Get 6 Jars summary
     */
    private function getJarSummary($userId, $period)
    {
        $allocation = $this->getOrCreateAllocation($userId, $period);
        $spentAmounts = $this->getSpentAmounts($userId, $period);
        
        $jars = ['nec', 'ffa', 'edu', 'ltss', 'play', 'give'];
        $summary = [];
        
        foreach ($jars as $jar) {
            $amount = $allocation[$jar . '_amount'] ?? 0;
            $spent = $spentAmounts[$jar] ?? 0;
            
            $summary[$jar] = [
                'percentage' => $allocation[$jar . '_percentage'] ?? 0,
                'amount' => $amount,
                'spent' => $spent,
                'remaining' => $amount - $spent
            ];
        }
        
        return $summary;
    }
    
    /**
     * Get or create jar allocation
     */
    private function getOrCreateAllocation($userId, $period)
    {
        $stmt = $this->db->prepare("
            SELECT * FROM jar_allocations 
            WHERE user_id = ? AND month = ?
        ");
        $stmt->execute([$userId, $period . '-01']);
        $allocation = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$allocation) {
            $this->createDefaultAllocation($userId, $period);
            $stmt->execute([$userId, $period . '-01']);
            $allocation = $stmt->fetch(\PDO::FETCH_ASSOC);
        }

        return $allocation;
    }
    
    /**
     * Create default allocation
     */
    private function createDefaultAllocation($userId, $period)
    {
        $stmt = $this->db->prepare("
            INSERT INTO jar_allocations (
                user_id, month, total_income,
                nec_percentage, ffa_percentage, edu_percentage,
                ltss_percentage, play_percentage, give_percentage
            ) VALUES (?, ?, 0, 55, 10, 10, 10, 10, 5)
        ");
        $stmt->execute([$userId, $period . '-01']);
    }
    
    /**
     * Get spent amounts by jar
     */
    private function getSpentAmounts($userId, $period)
    {
        $startDate = $period . '-01';
        $endDate = date('Y-m-t', strtotime($startDate));
        
        $jarMapping = [
            'nec' => ['Ăn uống hàng ngày', 'Tiền nhà', 'Điện nước', 'Đi lại', 'Y tế', 'Bảo hiểm'],
            'ffa' => ['Đầu tư', 'Tài sản sinh lời', 'Thu nhập thụ động'],
            'edu' => ['Sách vở', 'Khóa học', 'Hội thảo', 'Đào tạo'],
            'ltss' => ['Tiết kiệm khẩn cấp', 'Mục tiêu dài hạn', 'Quỹ dự phòng'],
            'play' => ['Du lịch', 'Giải trí', 'Ăn uống cao cấp', 'Sở thích', 'Mua sắm'],
            'give' => ['Quyên góp', 'Hỗ trợ cộng đồng', 'Quà tặng ý nghĩa']
        ];
        
        $spent = ['nec' => 0, 'ffa' => 0, 'edu' => 0, 'ltss' => 0, 'play' => 0, 'give' => 0];

        $stmt = $this->db->prepare("
            SELECT c.name, SUM(ABS(t.amount)) as total
            FROM transactions t
            JOIN categories c ON t.category_id = c.id
            WHERE t.user_id = ? 
            AND t.type = 'expense'
            AND t.date BETWEEN ? AND ?
            GROUP BY c.name
        ");
        $stmt->execute([$userId, $startDate, $endDate]);
        $transactions = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($transactions as $trans) {
            foreach ($jarMapping as $jar => $categories) {
                if (in_array($trans['name'], $categories)) {
                    $spent[$jar] += $trans['total'];
                    break;
                }
            }
        }

        return $spent;
    }
    
    /**
     * Update jar percentages
     */
    private function updatePercentages($userId, $period, $percentages)
    {
        $allocation = $this->getOrCreateAllocation($userId, $period);
        $totalIncome = $allocation['total_income'];

        $stmt = $this->db->prepare("
            UPDATE jar_allocations SET
                nec_percentage = ?, nec_amount = ?,
                ffa_percentage = ?, ffa_amount = ?,
                edu_percentage = ?, edu_amount = ?,
                ltss_percentage = ?, ltss_amount = ?,
                play_percentage = ?, play_amount = ?,
                give_percentage = ?, give_amount = ?
            WHERE user_id = ? AND month = ?
        ");

        return $stmt->execute([
            $percentages['nec'], ($totalIncome * $percentages['nec']) / 100,
            $percentages['ffa'], ($totalIncome * $percentages['ffa']) / 100,
            $percentages['edu'], ($totalIncome * $percentages['edu']) / 100,
            $percentages['ltss'], ($totalIncome * $percentages['ltss']) / 100,
            $percentages['play'], ($totalIncome * $percentages['play']) / 100,
            $percentages['give'], ($totalIncome * $percentages['give']) / 100,
            $userId, $period . '-01'
        ]);
    }
    
    /**
     * Update total income
     */
    private function updateIncome($userId, $period, $income)
    {
        $allocation = $this->getOrCreateAllocation($userId, $period);

        $stmt = $this->db->prepare("
            UPDATE jar_allocations SET
                total_income = ?,
                nec_amount = ?,
                ffa_amount = ?,
                edu_amount = ?,
                ltss_amount = ?,
                play_amount = ?,
                give_amount = ?
            WHERE user_id = ? AND month = ?
        ");

        return $stmt->execute([
            $income,
            ($income * $allocation['nec_percentage']) / 100,
            ($income * $allocation['ffa_percentage']) / 100,
            ($income * $allocation['edu_percentage']) / 100,
            ($income * $allocation['ltss_percentage']) / 100,
            ($income * $allocation['play_percentage']) / 100,
            ($income * $allocation['give_percentage']) / 100,
            $userId, $period . '-01'
        ]);
    }
    
    /**
     * Get total income for a specific month
     */
    private function getTotalIncome($userId, $period)
    {
        $stmt = $this->db->prepare("
            SELECT COALESCE(SUM(amount), 0) as total
            FROM transactions
            WHERE user_id = ? 
            AND type = 'income'
            AND DATE_FORMAT(date, '%Y-%m') = ?
        ");
        $stmt->execute([$userId, $period]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $result['total'] ?? 0;
    }

    /**
     * API: Get 6 Jars summary for a specific period
     */
    public function api_get_jars($period = null)
    {
        try {
            $userId = $this->getCurrentUserId();
            
            if (!$period) {
                $period = date('Y-m');
            }

            $jarSummary = $this->getJarSummary($userId, $period);
            $totalIncome = $this->getTotalIncome($userId, $period);

            ApiResponse::success('Success', [
                'jar_summary' => $jarSummary,
                'total_income' => $totalIncome
            ]);
        } catch (\Exception $e) {
            ApiResponse::serverError($e->getMessage());
        }
    }
    
    /**
     * API: Update jar allocation percentages
     */
    public function api_update_percentages()
    {
        try {
            $userId = $this->getCurrentUserId();
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['period']) || !isset($data['percentages'])) {
                ApiResponse::error('Missing required fields', 400);
                return;
            }
            
            $period = $data['period'];
            $percentages = $data['percentages'];
            
            // Validate percentages sum to 100
            $total = array_sum($percentages);
            if ($total != 100) {
                ApiResponse::error('Percentages must sum to 100%', 400);
                return;
            }
            
            $this->updatePercentages($userId, $period, $percentages);
            $jarSummary = $this->getJarSummary($userId, $period);
            
            ApiResponse::success('Cập nhật tỷ lệ thành công', ['jar_summary' => $jarSummary]);
        } catch (\Exception $e) {
            ApiResponse::serverError($e->getMessage());
        }
    }
    
    /**
     * API: Update total income
     */
    public function api_update_income()
    {
        try {
            $userId = $this->getCurrentUserId();
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['period']) || !isset($data['income'])) {
                ApiResponse::error('Missing required fields', 400);
                return;
            }
            
            $period = $data['period'];
            $income = floatval($data['income']);
            
            $this->updateIncome($userId, $period, $income);
            $jarSummary = $this->getJarSummary($userId, $period);
            
            ApiResponse::success('Cập nhật thu nhập thành công', ['jar_summary' => $jarSummary]);
        } catch (\Exception $e) {
            ApiResponse::serverError($e->getMessage());
        }
    }

}
