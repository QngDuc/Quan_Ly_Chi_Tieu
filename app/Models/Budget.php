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
        $sql = "
            SELECT 
                b.*,
                c.name as category_name,
                c.color as category_color,
                c.icon as category_icon,
                c.type as category_type,
                COALESCE(SUM(t.amount), 0) as spent,
                ROUND((COALESCE(SUM(t.amount), 0) / b.amount) * 100, 2) as percentage_used,
                b.amount - COALESCE(SUM(t.amount), 0) as remaining
            FROM budgets b
            INNER JOIN categories c ON b.category_id = c.id
            LEFT JOIN transactions t ON 
                t.category_id = b.category_id 
                AND t.user_id = b.user_id
                AND t.type = 'expense'
                AND t.date BETWEEN b.start_date AND b.end_date
            WHERE b.user_id = ? AND b.period = ? AND b.is_active = 1
            GROUP BY b.id, c.name, c.color, c.icon, c.type
            ORDER BY b.created_at DESC
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId, $period]);
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
        $sql = "
            SELECT 
                COUNT(*) as total_budgets,
                SUM(b.amount) as total_budget_amount,
                SUM(COALESCE(spent.total, 0)) as total_spent
            FROM budgets b
            LEFT JOIN (
                SELECT 
                    t.category_id,
                    SUM(t.amount) as total
                FROM transactions t
                INNER JOIN budgets b2 ON 
                    t.category_id = b2.category_id 
                    AND t.user_id = b2.user_id
                    AND t.date BETWEEN b2.start_date AND b2.end_date
                WHERE t.user_id = ? AND t.type = 'expense' AND b2.period = ?
                GROUP BY t.category_id
            ) spent ON b.category_id = spent.category_id
            WHERE b.user_id = ? AND b.period = ? AND b.is_active = 1
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId, $period, $userId, $period]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Get budgets that exceeded alert threshold
     */
    public function getAlerts($userId, $period = 'monthly')
    {
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
                AND t.date BETWEEN b.start_date AND b.end_date
            WHERE b.user_id = ? AND b.period = ? AND b.is_active = 1
            GROUP BY b.id, c.name
            HAVING percentage_used >= b.alert_threshold
            ORDER BY percentage_used DESC
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId, $period]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
