<?php
namespace App\Services;

use App\Models\Transaction;

class DashboardService
{
    private $transactionModel;

    public function __construct(Transaction $transactionModel)
    {
        $this->transactionModel = $transactionModel;
    }

    public function getDashboardData($userId, $range = 'this_month')
    {
        // 1. Determine Current and Previous Date Ranges using FinancialUtils
        list($startDate, $endDate, $prevStartDate, $prevEndDate) = FinancialUtils::getPeriodDates($range);

        // 2. Get Totals for Both Periods
        $currentTotals = $this->transactionModel->getTotalsForPeriod($userId, $startDate, $endDate);
        $previousTotals = $this->transactionModel->getTotalsForPeriod($userId, $prevStartDate, $prevEndDate);

        // 3. Calculate Trends using FinancialUtils
        $incomeTrend = FinancialUtils::calculatePercentageChange($previousTotals['income'], $currentTotals['income']);
        $expenseTrend = FinancialUtils::calculatePercentageChange($previousTotals['expense'], $currentTotals['expense']);
        
        // Get Total Balance
        $totalBalance = $this->transactionModel->getTotalBalance($userId);

        // Calculate savings rate using FinancialUtils
        $currentSavingsRate = FinancialUtils::calculateSavingsRate($currentTotals['income'], $currentTotals['expense']);
        $previousSavingsRate = FinancialUtils::calculateSavingsRate($previousTotals['income'], $previousTotals['expense']);
        $savingsRateTrend = $currentSavingsRate - $previousSavingsRate;

        $totals = [
            'income' => (float) $currentTotals['income'],
            'expense' => (float) $currentTotals['expense'],
            'balance' => (float) $totalBalance,
            'savingsRate' => $currentSavingsRate,
            'income_trend' => $incomeTrend,
            'expense_trend' => $expenseTrend,
            'savings_rate_trend' => $savingsRateTrend,
        ];

        // --- Get Recent Transactions ---
        $recentTransactions = $this->transactionModel->getRecentTransactions($userId);

        // --- Get Data for Pie Chart (Category Breakdown for current period) ---
        $pieChartData = $this->transactionModel->getCategoryBreakdown($userId, $startDate, $endDate, 'expense');

        // Calculate Remaining Balance for Pie Chart
        $currentIncome = (float) $currentTotals['income'];
        $currentExpense = (float) $currentTotals['expense'];
        $netBalance = $currentIncome - $currentExpense;

        if ($currentIncome > 0 && $netBalance > 0) {
            $pieChartData[] = ['name' => 'Số dư còn lại', 'total' => $netBalance];
        }

        // --- 4. Get Data for Line Chart ---
        $lineChartData = $this->transactionModel->getLineChartData($userId);

        // Calculate Net Income and Trend Class for View
        $netIncome = $totals['income'] - $totals['expense'];
        $netTrendClass = ($netIncome >= 0) ? 'up' : 'down';
        
        $totals['net_income'] = $netIncome;
        $totals['net_trend_class'] = $netTrendClass;

        return [
            'totals' => $totals,
            'recentTransactions' => $recentTransactions,
            'pieChartData' => $pieChartData,
            'lineChartData' => $lineChartData
        ];
    }
}
