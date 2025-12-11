<?php
namespace App\Models;

use App\Core\ConnectDB;
use \PDO;

class Budget
{
    private $db;

    public function __construct()
    {
        $this->db = (new ConnectDB())->getConnection();
    }

    /**
     * Get all budgets with spending data for a user
     */
    public function getBudgetsWithSpending($userId, $period = 'monthly')
    {
        // Compute date range based on requested period (client can request weekly/monthly/yearly)
        $now = new \DateTime();
        switch ($period) {
            case 'weekly':
                $startDate = (clone $now)->modify('monday this week')->format('Y-m-d');
                $endDate = (clone $now)->modify('sunday this week')->format('Y-m-d');
                break;
            case 'yearly':
                $startDate = (clone $now)->modify('first day of january ' . $now->format('Y'))->format('Y-01-01');
                $endDate = (clone $now)->modify('last day of december ' . $now->format('Y'))->format('Y-12-31');
                break;
            case 'daily':
                $startDate = $now->format('Y-m-d');
                $endDate = $now->format('Y-m-d');
                break;
            case 'monthly':
            default:
                $startDate = $now->format('Y-m-01');
                $endDate = $now->format('Y-m-t');
                break;
        }

        $sql = "
            SELECT 
                b.*, 
                c.name as category_name, 
                c.color as category_color, 
                c.icon as category_icon, 
                c.type as category_type, 
                COALESCE(SUM(t.amount), 0) as spent, 
                CASE WHEN b.amount IS NULL OR b.amount = 0 THEN 0 ELSE ROUND((COALESCE(SUM(t.amount),0) / NULLIF(b.amount,0)) * 100, 2) END as percentage_used, 
                (b.amount - COALESCE(SUM(t.amount), 0)) as remaining 
            FROM budgets b
            INNER JOIN categories c ON b.category_id = c.id
            LEFT JOIN transactions t ON t.category_id = b.category_id
                AND t.user_id = b.user_id
                AND t.type = 'expense'
                AND t.date BETWEEN ? AND ?
            WHERE b.user_id = ? AND b.is_active = 1
            GROUP BY b.id, c.name, c.color, c.icon, c.type
            ORDER BY b.id DESC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$startDate, $endDate, $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get a single budget by ID
     */
    public function getById($id)
    {
        $stmt = $this->db->prepare("SELECT * FROM budgets WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Create a new budget
     */
    public function create($data)
    {
        // Check if budget already exists for this category and period
        $stmt = $this->db->prepare("
            SELECT id FROM budgets 
            WHERE user_id = ? AND category_id = ? AND period = ? AND is_active = 1
        ");
        $stmt->execute([$data['user_id'], $data['category_id'], $data['period']]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($existing) {
            throw new \Exception('Ngân sách cho danh mục này đã tồn tại trong kỳ này');
        }

        $sql = "
            INSERT INTO budgets 
            (user_id, category_id, amount, period, start_date, end_date, alert_threshold, is_active, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ";

        $stmt = $this->db->prepare($sql);
        try {
            $result = $stmt->execute([
                $data['user_id'],
                $data['category_id'],
                $data['amount'],
                $data['period'],
                $data['start_date'],
                $data['end_date'],
                $data['alert_threshold'],
                $data['is_active']
            ]);
        } catch (\PDOException $e) {
            $msg = $e->getMessage();
            // If DB missing alert_threshold or created_at, retry with a compatible INSERT
            if (stripos($msg, 'Unknown column') !== false || stripos($msg, 'field list') !== false) {
                // Try without alert_threshold but with created_at
                try {
                    $sql2 = "INSERT INTO budgets (user_id, category_id, amount, period, start_date, end_date, is_active, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
                    $stmt2 = $this->db->prepare($sql2);
                    $result = $stmt2->execute([
                        $data['user_id'],
                        $data['category_id'],
                        $data['amount'],
                        $data['period'],
                        $data['start_date'],
                        $data['end_date'],
                        $data['is_active']
                    ]);
                } catch (\PDOException $e2) {
                    // Try without created_at (some very old schemas)
                    $sql3 = "INSERT INTO budgets (user_id, category_id, amount, period, start_date, end_date, alert_threshold, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                    $stmt3 = $this->db->prepare($sql3);
                    $result = $stmt3->execute([
                        $data['user_id'],
                        $data['category_id'],
                        $data['amount'],
                        $data['period'],
                        $data['start_date'],
                        $data['end_date'],
                        $data['alert_threshold'],
                        $data['is_active']
                    ]);
                }
            } else {
                throw $e; // rethrow unexpected PDO errors
            }
        }

        return $result ? $this->db->lastInsertId() : false;
    }

    /**
     * Update a budget
     */
    public function update($id, $data)
    {
        $fields = [];
        $values = [];
        
        if (isset($data['amount'])) {
            $fields[] = 'amount = ?';
            $values[] = $data['amount'];
        }
        if (isset($data['alert_threshold'])) {
            $fields[] = 'alert_threshold = ?';
            $values[] = $data['alert_threshold'];
        }
        if (isset($data['is_active'])) {
            $fields[] = 'is_active = ?';
            $values[] = $data['is_active'];
        }
        
        $fields[] = 'updated_at = NOW()';
        $values[] = $id;
        
        $sql = "UPDATE budgets SET " . implode(', ', $fields) . " WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($values);
    }

    /**
     * Delete a budget
     */
    public function delete($id)
    {
        $stmt = $this->db->prepare("DELETE FROM budgets WHERE id = ?");
        return $stmt->execute([$id]);
    }

    /**
     * Get budget summary for user
     */
    public function getSummary($userId, $period = 'monthly')
    {
        // Compute date range for the summary based on requested period
        $now = new \DateTime();
        switch ($period) {
            case 'weekly':
                $startDate = (clone $now)->modify('monday this week')->format('Y-m-d');
                $endDate = (clone $now)->modify('sunday this week')->format('Y-m-d');
                break;
            case 'yearly':
                $startDate = (clone $now)->modify('first day of january ' . $now->format('Y'))->format('Y-01-01');
                $endDate = (clone $now)->modify('last day of december ' . $now->format('Y'))->format('Y-12-31');
                break;
            case 'daily':
                $startDate = $now->format('Y-m-d');
                $endDate = $now->format('Y-m-d');
                break;
            case 'monthly':
            default:
                $startDate = $now->format('Y-m-01');
                $endDate = $now->format('Y-m-t');
                break;
        }

        $sql = "
            SELECT 
                COUNT(*) as total_budgets,
                SUM(b.amount) as total_budget_amount,
                COALESCE(SUM(t.amount), 0) as total_spent
            FROM budgets b
            LEFT JOIN transactions t ON t.category_id = b.category_id
                AND t.user_id = b.user_id
                AND t.type = 'expense'
                AND t.date BETWEEN ? AND ?
            WHERE b.user_id = ? AND b.is_active = 1
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$startDate, $endDate, $userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Get budgets that exceeded alert threshold
     */
    public function getAlerts($userId, $period = 'monthly')
    {
        // Compute date range based on requested period
        $now = new \DateTime();
        switch ($period) {
            case 'weekly':
                $startDate = (clone $now)->modify('monday this week')->format('Y-m-d');
                $endDate = (clone $now)->modify('sunday this week')->format('Y-m-d');
                break;
            case 'yearly':
                $startDate = (clone $now)->modify('first day of january ' . $now->format('Y'))->format('Y-01-01');
                $endDate = (clone $now)->modify('last day of december ' . $now->format('Y'))->format('Y-12-31');
                break;
            case 'daily':
                $startDate = $now->format('Y-m-d');
                $endDate = $now->format('Y-m-d');
                break;
            case 'monthly':
            default:
                $startDate = $now->format('Y-m-01');
                $endDate = $now->format('Y-m-t');
                break;
        }

        $sql = "
            SELECT 
                b.*,
                c.name as category_name,
                COALESCE(SUM(t.amount), 0) as spent,
                ROUND((COALESCE(SUM(t.amount), 0) / b.amount) * 100, 2) as percentage_used
            FROM budgets b
            INNER JOIN categories c ON b.category_id = c.id
            LEFT JOIN transactions t ON 
                t.category_id = b.category_id 
                AND t.user_id = b.user_id
                AND t.type = 'expense'
                AND t.date BETWEEN ? AND ?
            WHERE b.user_id = ? AND b.is_active = 1
            GROUP BY b.id, c.name
            HAVING percentage_used >= b.alert_threshold
            ORDER BY percentage_used DESC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$startDate, $endDate, $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get monthly trend of budget totals and spending for the last N months
     * Returns array of ['labels' => [...], 'budget' => [...], 'spent' => [...]]
     */
    public function getMonthlyTrend($userId, $months = 6)
    {
        $results = ['labels' => [], 'budget' => [], 'spent' => []];
        $now = new \DateTime();

        for ($i = $months - 1; $i >= 0; $i--) {
            $d = (clone $now)->modify("-{$i} months");
            $year = $d->format('Y');
            $month = $d->format('m');
            $start = "$year-$month-01";
            $end = (new \DateTime($start))->format('Y-m-t');

            // Sum budgets active in that month (amounts)
            $sqlBud = "
                SELECT COALESCE(SUM(b.amount),0) as total_budget
                FROM budgets b
                WHERE b.user_id = ? AND b.is_active = 1
                  AND NOT (b.end_date < ? OR b.start_date > ?)
            ";
            $stmt = $this->db->prepare($sqlBud);
            $stmt->execute([$userId, $start, $end]);
            $rowB = $stmt->fetch(PDO::FETCH_ASSOC);
            $totalBudget = isset($rowB['total_budget']) ? floatval($rowB['total_budget']) : 0.0;

            // Sum spent in that month across expense transactions
            $sqlSpent = "
                SELECT COALESCE(SUM(t.amount),0) as total_spent
                FROM transactions t
                WHERE t.user_id = ? AND t.type = 'expense' AND t.date BETWEEN ? AND ?
            ";
            $stmt2 = $this->db->prepare($sqlSpent);
            $stmt2->execute([$userId, $start, $end]);
            $rowS = $stmt2->fetch(PDO::FETCH_ASSOC);
            $totalSpent = isset($rowS['total_spent']) ? floatval($rowS['total_spent']) : 0.0;

            $results['labels'][] = $d->format('M');
            $results['budget'][] = $totalBudget;
            $results['spent'][] = $totalSpent;
        }

        return $results;
    }
}
