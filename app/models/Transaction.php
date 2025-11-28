<?php
namespace App\Models;

use App\Core\ConnectDB;
use \PDO;

class Transaction
{
    private $db;

    public function __construct()
    {
        $this->db = (new ConnectDB())->getConnection();
    }

    /**
     * Fetches all necessary data for the dashboard based on a date range.
     * @param int $userId The ID of the user.
     * @param string $range The date range ('month', 'week', 'year', or a specific day 'Y-m-d').
     * @return array An associative array with keys 'totals', 'recentTransactions', 'lineChart', 'pieChart'.
     */
    public function getDashboardData($userId, $range = 'this_month')
    {
        // 1. Determine Current and Previous Date Ranges
        list($startDate, $endDate, $prevStartDate, $prevEndDate) = $this->getPeriodDates($range);

        // 2. Get Totals for Both Periods
        $currentTotals = $this->getTotalsForPeriod($userId, $startDate, $endDate);
        $previousTotals = $this->getTotalsForPeriod($userId, $prevStartDate, $prevEndDate);

        // 3. Calculate Trends
        $incomeTrend = $this->calculatePercentageChange($previousTotals['income'], $currentTotals['income']);
        $expenseTrend = $this->calculatePercentageChange($previousTotals['expense'], $currentTotals['expense']);
        
        // --- Get Total Balance (unaffected by date range) ---
        $stmtTotalBalance = $this->db->prepare("SELECT COALESCE(SUM(amount), 0) AS balance FROM transactions WHERE user_id = ?");
        $stmtTotalBalance->execute([$userId]);
        $totalBalance = $stmtTotalBalance->fetch(PDO::FETCH_ASSOC);

        $currentSavingsRate = $currentTotals['income'] > 0 ? round((($currentTotals['income'] - $currentTotals['expense']) / $currentTotals['income']) * 100) : 0;
        $previousSavingsRate = $previousTotals['income'] > 0 ? round((($previousTotals['income'] - $previousTotals['expense']) / $previousTotals['income']) * 100) : 0;
        $savingsRateTrend = $currentSavingsRate - $previousSavingsRate;

        $totals = [
            'income' => (float) $currentTotals['income'],
            'expense' => (float) $currentTotals['expense'],
            'balance' => (float) $totalBalance['balance'],
            'savingsRate' => $currentSavingsRate,
            'income_trend' => $incomeTrend,
            'expense_trend' => $expenseTrend,
            'savings_rate_trend' => $savingsRateTrend,
        ];


        // --- Get Recent Transactions ---
        $stmtRecent = $this->db->prepare("
            SELECT t.description, t.amount, t.transaction_date, c.name as category_name
            FROM transactions t
            JOIN categories c ON t.category_id = c.id
            WHERE t.user_id = ?
            ORDER BY t.transaction_date DESC
            LIMIT 5
        ");
        $stmtRecent->execute([$userId]);
        $recentTransactions = $stmtRecent->fetchAll(PDO::FETCH_ASSOC);


        // --- Get Data for Pie Chart (Category Breakdown for current period) ---
        $stmtPie = $this->db->prepare("
            SELECT c.name, SUM(ABS(t.amount)) as total
            FROM transactions t
            JOIN categories c ON t.category_id = c.id
            WHERE t.user_id = ? AND t.amount < 0 AND t.transaction_date BETWEEN ? AND ?
            GROUP BY c.name
            ORDER BY total DESC
        ");
        $stmtPie->execute([$userId, $startDate, $endDate]);
        $pieChartData = $stmtPie->fetchAll(PDO::FETCH_ASSOC);

        // Calculate Remaining Balance for Pie Chart
        $currentIncome = (float) $currentTotals['income'];
        $currentExpense = (float) $currentTotals['expense'];
        $netBalance = $currentIncome - $currentExpense;

        if ($currentIncome > 0 && $netBalance > 0) {
            $pieChartData[] = ['name' => 'Số dư còn lại', 'total' => $netBalance];
        }

        // --- 4. Get Data for Line Chart ---
        $lineChartData = $this->getLineChartData($userId);

        return [
            'totals' => $totals,
            'recentTransactions' => $recentTransactions,
            'pieChartData' => $pieChartData,
            'lineChartData' => $lineChartData
        ];
    }

    public function getLineChartData($userId)
    {
        // Always show the last 3 months: current, last month, and the month before that.
        $startDate = date('Y-m-01', strtotime('-2 months'));
        $endDate = date('Y-m-t');

        $format = '%Y-%m'; // Always aggregate by month
        
        $sql = "
            SELECT 
                DATE_FORMAT(transaction_date, '{$format}') as period,
                SUM(CASE WHEN amount > 0 THEN amount ELSE 0 END) as income,
                SUM(CASE WHEN amount < 0 THEN ABS(amount) ELSE 0 END) as expense
            FROM transactions
            WHERE user_id = ? AND transaction_date BETWEEN ? AND ?
            GROUP BY period
            ORDER BY period ASC
        ";

        $stmt = $this->db->prepare($sql);
        $params = [$userId, $startDate, $endDate];
        error_log("Line Chart Query Params - UserID: $userId, Start: $startDate, End: $endDate");
        error_log("Line Chart SQL: " . $sql);
        
        $stmt->execute($params);
        $dbData = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Debug: Log the query results
        error_log("Line Chart Data from DB: " . json_encode($dbData));

        // Create a complete set of labels for the 3-month period
        $periodData = [];
        $currentDate = new \DateTime($startDate);
        $finalDate = new \DateTime($endDate);
        $interval = new \DateInterval('P1M'); // Month by month

        while ($currentDate <= $finalDate) {
            $periodData[$currentDate->format('Y-m')] = ['income' => 0, 'expense' => 0];
            $currentDate->add($interval);
        }

        // Populate with data from DB
        foreach ($dbData as $row) {
            if (isset($periodData[$row['period']])) {
                $periodData[$row['period']] = [
                    'income' => (float)$row['income'],
                    'expense' => (float)$row['expense']
                ];
            }
        }

        // Finalize arrays for Chart.js
        $labels = array_map(function($p) { return "Tháng " . ltrim(substr($p, 5), '0'); }, array_keys($periodData));
        
        $incomeData = array_values(array_column($periodData, 'income'));
        $expenseData = array_values(array_column($periodData, 'expense'));

        $result = [
            'labels' => $labels,
            'income' => $incomeData,
            'expense' => $expenseData,
        ];
        
        // Debug: Log final result
        error_log("Line Chart Result: " . json_encode($result));

        return $result;
    }

    private function getTotalsForPeriod($userId, $startDate, $endDate)
    {
        $stmt = $this->db->prepare("
            SELECT
                COALESCE(SUM(CASE WHEN t.amount > 0 THEN t.amount ELSE 0 END), 0) AS income,
                COALESCE(SUM(ABS(CASE WHEN t.amount < 0 THEN t.amount ELSE 0 END)), 0) AS expense
            FROM transactions t
            WHERE t.user_id = ? AND t.transaction_date BETWEEN ? AND ?
        ");
        $stmt->execute([$userId, $startDate, $endDate]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    private function calculatePercentageChange($previous, $current)
    {
        if ($previous == 0) {
            return ($current > 0) ? 100 : 0; // If previous was 0, any increase is 100%
        }
        return round((($current - $previous) / $previous) * 100);
    }

    private function getPeriodDates($range) {
        switch ($range) {
            case 'this_week':
                $startDate = date('Y-m-d', strtotime('monday this week'));
                $endDate = date('Y-m-d', strtotime('sunday this week'));
                $prevStartDate = date('Y-m-d', strtotime('monday last week'));
                $prevEndDate = date('Y-m-d', strtotime('sunday last week'));
                break;
            case 'this_year':
                $startDate = date('Y-01-01');
                $endDate = date('Y-12-31');
                $prevStartDate = date('Y-01-01', strtotime('-1 year'));
                $prevEndDate = date('Y-12-31', strtotime('-1 year'));
                break;
            case 'this_month':
                $startDate = date('Y-m-01');
                $endDate = date('Y-m-t');
                $prevStartDate = date('Y-m-01', strtotime('first day of last month'));
                $prevEndDate = date('Y-m-t', strtotime('last day of last month'));
                break;
            case 'last_month':
                $startDate = date('Y-m-01', strtotime('first day of last month'));
                $endDate = date('Y-m-t', strtotime('last day of last month'));
                $prevStartDate = date('Y-m-01', strtotime('-2 months'));
                $prevEndDate = date('Y-m-t', strtotime('-2 months'));
                break;
            default:
                $startDate = date('Y-m-01');
                $endDate = date('Y-m-t');
                $prevStartDate = date('Y-m-01', strtotime('first day of last month'));
                $prevEndDate = date('Y-m-t', strtotime('last day of last month'));
                break;
        }
        return [$startDate, $endDate, $prevStartDate, $prevEndDate];
    }

    public function createTransaction($userId, $categoryId, $amount, $type, $date, $description)
    {
        // Ensure amount is stored correctly: negative for expense, positive for income
        $finalAmount = ($type === 'expense') ? -abs($amount) : abs($amount);

        $stmt = $this->db->prepare(
            "INSERT INTO transactions (user_id, category_id, amount, transaction_date, description) VALUES (?, ?, ?, ?, ?)"
        );
        
        return $stmt->execute([$userId, $categoryId, $finalAmount, $date, $description]);
    }

    public function getAllByUser($userId, $filters = [])
    {
        $sql = "
            SELECT t.id, t.description, t.amount, t.transaction_date, c.name as category_name
            FROM transactions t
            JOIN categories c ON t.category_id = c.id
        ";
        $where = ["t.user_id = ?"];
        $params = [$userId];

        if (!empty($filters['range'])) {
            list($startDate, $endDate) = $this->getPeriodDates($filters['range']);
            $where[] = "t.transaction_date BETWEEN ? AND ?";
            $params[] = $startDate;
            $params[] = $endDate;
        }

        if (!empty($filters['category_id'])) {
            $where[] = "t.category_id = ?";
            $params[] = $filters['category_id'];
        }

        if (count($where) > 0) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }

        $sql .= " ORDER BY t.transaction_date DESC, t.id DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
