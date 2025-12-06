<?php
namespace App\Models;

use App\Core\ConnectDB;
use App\Services\FinancialUtils;
use \PDO;

class Transaction
{
    private $db;

    public function __construct()
    {
        $this->db = (new ConnectDB())->getConnection();
    }

    public function getLineChartData($userId)
    {
        // Always show the last 3 months: current, last month, and the month before that.
        $startDate = date('Y-m-01', strtotime('-2 months'));
        $endDate = date('Y-m-t');

        $format = '%Y-%m'; // Always aggregate by month
        
        $sql = "
            SELECT 
                DATE_FORMAT(date, '{$format}') as period,
                SUM(CASE WHEN type = 'income' THEN ABS(amount) ELSE 0 END) as income,
                SUM(CASE WHEN type = 'expense' THEN ABS(amount) ELSE 0 END) as expense
            FROM transactions
            WHERE user_id = ? AND date BETWEEN ? AND ?
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

    public function getTotalsForPeriod($userId, $startDate, $endDate)
    {
        $stmt = $this->db->prepare("
            SELECT
                COALESCE(SUM(CASE WHEN type = 'income' THEN ABS(amount) ELSE 0 END), 0) AS income,
                COALESCE(SUM(CASE WHEN type = 'expense' THEN ABS(amount) ELSE 0 END), 0) AS expense
            FROM transactions t
            WHERE t.user_id = ? AND t.date BETWEEN ? AND ?
        ");
        $stmt->execute([$userId, $startDate, $endDate]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getTotalBalance($userId)
    {
        $stmt = $this->db->prepare("
            SELECT 
                COALESCE(SUM(CASE WHEN type = 'income' THEN ABS(amount) ELSE 0 END), 0) - 
                COALESCE(SUM(CASE WHEN type = 'expense' THEN ABS(amount) ELSE 0 END), 0) AS balance 
            FROM transactions 
            WHERE user_id = ?
        ");
        $stmt->execute([$userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['balance'] ?? 0;
    }

    public function getRecentTransactions($userId, $limit = 5)
    {
        $stmt = $this->db->prepare("
            SELECT t.description, t.amount, t.date, c.name as category_name
            FROM transactions t
            JOIN categories c ON t.category_id = c.id
            WHERE t.user_id = ?
            ORDER BY t.date DESC
            LIMIT " . (int)$limit
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function createTransaction($userId, $categoryId, $amount, $type, $date, $description)
    {
        // Get category type from database to determine if income or expense
        $categoryStmt = $this->db->prepare("SELECT type FROM categories WHERE id = ?");
        $categoryStmt->execute([$categoryId]);
        $category = $categoryStmt->fetch(PDO::FETCH_ASSOC);
        
        // Use FinancialUtils to normalize amount
        $finalAmount = FinancialUtils::normalizeAmount($amount, $category['type'] ?? 'expense');

        $stmt = $this->db->prepare(
            "INSERT INTO transactions (user_id, category_id, amount, date, description) VALUES (?, ?, ?, ?, ?)"
        );
        
        return $stmt->execute([$userId, $categoryId, $finalAmount, $date, $description]);
    }

    public function getAllByUser($userId, $filters = [])
    {
        $sql = "
            SELECT t.id, t.description, t.amount, t.date, c.name as category_name, t.category_id
            FROM transactions t
            JOIN categories c ON t.category_id = c.id
        ";
        $where = ["t.user_id = ?"];
        $params = [$userId];

        if (!empty($filters['range'])) {
            // Use FinancialUtils to get period dates
            list($startDate, $endDate) = FinancialUtils::getPeriodDates($filters['range']);
            $where[] = "t.date BETWEEN ? AND ?";
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

        $sql .= " ORDER BY t.date DESC, t.id DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function deleteTransaction($id, $userId)
    {
        $sql = "DELETE FROM transactions WHERE id = ? AND user_id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$id, $userId]);
    }

    public function updateTransaction($id, $userId, $categoryId, $amount, $type, $date, $description)
    {
        // Determine if amount should be negative based on category type
        $categoryStmt = $this->db->prepare("SELECT type FROM categories WHERE id = ?");
        $categoryStmt->execute([$categoryId]);
        $category = $categoryStmt->fetch(PDO::FETCH_ASSOC);
        
        // Use FinancialUtils to normalize amount
        $finalAmount = FinancialUtils::normalizeAmount($amount, $category['type'] ?? 'expense');
        
        $sql = "UPDATE transactions 
                SET category_id = ?, amount = ?, description = ?, date = ?
                WHERE id = ? AND user_id = ?";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$categoryId, $finalAmount, $description, $date, $id, $userId]);
    }

    public function getMonthTotals($userId, $startDate, $endDate)
    {
        $stmt = $this->db->prepare("
            SELECT 
                SUM(CASE WHEN type = 'income' THEN ABS(amount) ELSE 0 END) as income,
                SUM(CASE WHEN type = 'expense' THEN ABS(amount) ELSE 0 END) as expense
            FROM transactions 
            WHERE user_id = ? 
            AND date BETWEEN ? AND ?
        ");
        $stmt->execute([$userId, $startDate, $endDate]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getCategoryBreakdown($userId, $startDate, $endDate, $type = 'all')
    {
        $sql = "
            SELECT c.name, SUM(ABS(t.amount)) as total
            FROM transactions t
            JOIN categories c ON t.category_id = c.id
            WHERE t.user_id = ? 
            AND t.date BETWEEN ? AND ?
        ";
        
        $params = [$userId, $startDate, $endDate];
        
        // Add type filter if specified
        if ($type === 'expense') {
            $sql .= " AND t.type = 'expense'";
        } elseif ($type === 'income') {
            $sql .= " AND t.type = 'income'";
        }
        
        $sql .= "
            GROUP BY c.id, c.name
            ORDER BY total DESC
            LIMIT 10
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function deleteAllByUser($userId)
    {
        $sql = "DELETE FROM transactions WHERE user_id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$userId]);
    }

    public function getTransactionsByUser($userId)
    {
        $sql = "SELECT t.*, c.name as category_name 
                FROM transactions t
                LEFT JOIN categories c ON t.category_id = c.id
                WHERE t.user_id = ?
                ORDER BY t.date DESC, t.id DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
