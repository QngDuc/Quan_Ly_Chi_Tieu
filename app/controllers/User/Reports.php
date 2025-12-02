<?php
namespace App\Controllers\User;

use App\Core\Controllers;

class Reports extends Controllers
{
    private $transactionModel;

    public function __construct()
    {
        parent::__construct();
        if (!$this->isLoggedIn()) {
            $this->redirect('/login_signup');
        }
        $this->transactionModel = $this->model('Transaction');
    }

    public function index($period = 'last_3_months', $type = 'all')
    {
        $userId = $this->getCurrentUserId();
        
        // Get data based on filters
        $reportLine = $this->getLineChartData($userId, $period);
        $reportPie = $this->getPieChartData($userId, $period, $type);

        $data = [
            'title' => 'Báo cáo',
            'reportLine' => $reportLine,
            'reportPie' => $reportPie,
            'current_period' => $period,
            'current_type' => $type
        ];

        $this->view('reports', $data);
    }

    /**
     * API endpoint to get report data dynamically
     */
    public function api_get_report_data()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            exit;
        }

        try {
            $userId = $this->getCurrentUserId();
            $period = $_GET['period'] ?? 'last_3_months';
            $type = $_GET['type'] ?? 'all';

            $reportLine = $this->getLineChartData($userId, $period);
            $reportPie = $this->getPieChartData($userId, $period, $type);

            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => [
                    'lineChart' => $reportLine,
                    'pieChart' => $reportPie
                ]
            ]);
        } catch (\Exception $e) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Lỗi: ' . $e->getMessage()
            ]);
        }
        exit;
    }

    private function getLineChartData($userId, $period = 'last_3_months')
    {
        $months = [];
        $income = [];
        $expense = [];

        // Determine the number of months based on period
        $monthCount = match($period) {
            'this_month' => 1,
            'last_3_months' => 3,
            'last_6_months' => 6,
            'this_year' => 12,
            default => 3
        };

        // Get data for the specified period
        for ($i = $monthCount - 1; $i >= 0; $i--) {
            $date = date('Y-m', strtotime("-$i months"));
            $monthName = date('m/Y', strtotime("-$i months"));
            
            $startDate = $date . '-01';
            $endDate = date('Y-m-t', strtotime($startDate));

            // Get totals for this month
            $result = $this->transactionModel->getMonthTotals($userId, $startDate, $endDate);

            $months[] = $monthName;
            $income[] = floatval($result['income'] ?? 0);
            $expense[] = floatval($result['expense'] ?? 0);
        }

        return [
            'labels' => $months,
            'income' => $income,
            'expense' => $expense
        ];
    }

    private function getPieChartData($userId, $period = 'last_3_months', $type = 'all')
    {
        // Determine date range based on period
        list($startDate, $endDate) = match($period) {
            'this_month' => [date('Y-m-01'), date('Y-m-t')],
            'last_3_months' => [date('Y-m-01', strtotime('-2 months')), date('Y-m-t')],
            'last_6_months' => [date('Y-m-01', strtotime('-5 months')), date('Y-m-t')],
            'this_year' => [date('Y-01-01'), date('Y-12-31')],
            default => [date('Y-m-01', strtotime('-2 months')), date('Y-m-t')]
        };

        // Get category breakdown with optional type filter
        $results = $this->transactionModel->getCategoryBreakdown($userId, $startDate, $endDate, $type);

        $labels = [];
        $data = [];

        foreach ($results as $row) {
            $labels[] = $row['name'];
            $data[] = floatval($row['total']);
        }

        return [
            'labels' => $labels,
            'data' => $data
        ];
    }
}
