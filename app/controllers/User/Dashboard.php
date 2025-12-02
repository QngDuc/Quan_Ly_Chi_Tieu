<?php
namespace App\Controllers\User;

use App\Core\Controllers;
use App\Models\Transaction;

class Dashboard extends Controllers
{
    private $transactionModel;

    public function __construct()
    {
        parent::__construct();
        // Redirect to login if not logged in
        if (!$this->isLoggedIn()) {
            $this->redirect('/login_signup');
        }
        $this->transactionModel = $this->model('Transaction');
    }

    public function index($range = null)
    {
        $userId = $this->getCurrentUserId();
        error_log("Dashboard - Current User ID: " . $userId);
        
        // Default to current month if no range provided
        if (!$range) {
            $range = date('Y-m');
        }
        
        $dashboardData = $this->transactionModel->getDashboardData($userId, $range);

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
        
        $this->view->render('dashboard', $data);
    }
}
