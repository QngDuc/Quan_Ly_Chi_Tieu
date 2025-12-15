<?php
namespace App\Controllers\User;

use App\Core\Controllers;
use App\Services\DashboardService;
use App\Middleware\AuthCheck;

class Dashboard extends Controllers
{
    private $dashboardService;

    public function __construct()
    {
        parent::__construct();
        AuthCheck::requireUser();
        
        $transactionModel = $this->model('Transaction');
        if (!$transactionModel) {
            throw new \RuntimeException("Transaction model could not be loaded.");
        }
        $this->dashboardService = new DashboardService($transactionModel);
    }

    public function index($range = null)
    {
        $userId = $this->getCurrentUserId();
        
        // Default to current month
        if (!$range) {
            $range = date('Y-m');
        }
        
        // 1. Lấy dữ liệu thống kê cơ bản
        $dashboardData = $this->dashboardService->getDashboardData($userId, $range);

        // 2. Xử lý dữ liệu JARS (6 Hũ)
        $walletModel = $this->model('Wallet');
        $rawBalances = $walletModel->getWalletBalances($userId);
        
        $budgetModel = $this->model('Budget');
        $settings = $budgetModel->getUserSmartSettings($userId);

        // Cấu hình hiển thị 6 hũ
        $jars = [
            'nec'  => ['name' => 'Thiết yếu', 'desc' => 'Ăn uống, sinh hoạt', 'color' => 'primary',   'percent' => $settings['nec_percent'] ?? 55],
            'ffa'  => ['name' => 'Tự do TC',  'desc' => 'Đầu tư, tiết kiệm',  'color' => 'success',   'percent' => $settings['ffa_percent'] ?? 10],
            'ltss' => ['name' => 'TK dài hạn','desc' => 'Mua xe, mua nhà',    'color' => 'info',      'percent' => $settings['ltss_percent'] ?? 10],
            'edu'  => ['name' => 'Giáo dục',  'desc' => 'Sách, khóa học',     'color' => 'warning',   'percent' => $settings['edu_percent'] ?? 10],
            'play' => ['name' => 'Hưởng thụ', 'desc' => 'Du lịch, giải trí',  'color' => 'danger',    'percent' => $settings['play_percent'] ?? 10],
            'give' => ['name' => 'Cho đi',    'desc' => 'Từ thiện',           'color' => 'secondary', 'percent' => $settings['give_percent'] ?? 5],
        ];

        // Gán số dư thực tế
        foreach ($jars as $code => &$jar) {
            $jar['balance'] = $rawBalances[$code] ?? 0;
        }

        $lineChartSubtitle = '3 tháng gần nhất';

        $data = [
            'title' => 'Tổng quan',
            'range' => $range,
            'totals' => $dashboardData['totals'],
            'recentTransactions' => $dashboardData['recentTransactions'],
            'pieChartData' => json_encode($dashboardData['pieChartData']),
            'lineChartData' => json_encode($dashboardData['lineChartData']),
            'lineChartSubtitle' => $lineChartSubtitle,
            'jars' => $jars // <-- Biến mới cho View Dashboard
        ];

        $this->view->render('user/dashboard', $data);
    }
}