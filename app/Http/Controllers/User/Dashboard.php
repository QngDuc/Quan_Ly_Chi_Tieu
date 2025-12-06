<?php
namespace App\Http\Controllers\User;

use App\Core\Controllers;
use App\Services\DashboardService;
use App\Middleware\AuthCheck;

class Dashboard extends Controllers
{
    private $dashboardService;

    public function __construct()
    {
        parent::__construct();
        // Kiểm tra quyền user (ngăn admin truy cập)
        AuthCheck::requireUser();
        
        // Dependency Injection (Manual)
        $transactionModel = $this->model('Transaction');
        $this->dashboardService = new DashboardService($transactionModel);
    }

    public function index($range = null)
    {
        $userId = $this->getCurrentUserId();
        error_log("Dashboard - Current User ID: " . $userId);
        
        // Default to current month if no range provided
        if (!$range) {
            $range = date('Y-m');
        }
        
        $dashboardData = $this->dashboardService->getDashboardData($userId, $range);

        // Determine subtitle for line chart (always shows 3 recent months)
        $lineChartSubtitle = '3 tháng gần nhất';

        $data = [
            'title' => 'Tổng quan',
            'range' => $range,
            'totals' => $dashboardData['totals'],
            'recentTransactions' => $dashboardData['recentTransactions'],
            'pieChartData' => json_encode($dashboardData['pieChartData']),
            'lineChartData' => json_encode($dashboardData['lineChartData']),
            'lineChartSubtitle' => $lineChartSubtitle
        ];
        
        $this->view->render('user/dashboard', $data);
    }
}
