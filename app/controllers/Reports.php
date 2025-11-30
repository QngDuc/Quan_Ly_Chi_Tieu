<?php
namespace App\Controllers;

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

    public function index()
    {
        $userId = $this->getCurrentUserId();
        
        // Get last 3 months data (current month - 2 months)
        $reportLine = $this->getLineChartData($userId);
        $reportPie = $this->getPieChartData($userId);

        $data = [
            'title' => 'Báo cáo',
            'reportLine' => $reportLine,
            'reportPie' => $reportPie
        ];

        $this->view('reports/index', $data);
    }

    private function getLineChartData($userId)
    {
        $months = [];
        $income = [];
        $expense = [];

        // Get last 3 months (current month and previous 2 months)
        for ($i = 2; $i >= 0; $i--) {
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

    private function getPieChartData($userId)
    {
        // Get category breakdown for last 3 months
        $startDate = date('Y-m-01', strtotime('-2 months'));
        $endDate = date('Y-m-t');

        $results = $this->transactionModel->getCategoryBreakdown($userId, $startDate, $endDate);

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
