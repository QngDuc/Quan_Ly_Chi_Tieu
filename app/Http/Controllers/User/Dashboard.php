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

        // Write a debug copy of chart data to storage/logs for diagnosis
        try {
            $logDir = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'logs';
            if (!is_dir($logDir)) @mkdir($logDir, 0755, true);
            $msg = '[' . date('Y-m-d H:i:s') . '] Dashboard data:\n';
            $msg .= 'pieChartData: ' . json_encode($dashboardData['pieChartData']) . "\n";
            $msg .= 'lineChartData: ' . json_encode($dashboardData['lineChartData']) . "\n\n";
            @file_put_contents($logDir . DIRECTORY_SEPARATOR . 'dashboard_data.log', $msg, FILE_APPEND);
        } catch (\Exception $e) {
            // ignore logging errors
        }

        $this->view->render('user/dashboard', $data);
    }
}
