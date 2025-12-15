<?php

namespace App\Models;

use App\Core\ConnectDB;
use PDO;

/**
 * Transaction model
 *
 * @method array getTotalsForPeriod(int $userId, string $startDate, string $endDate)
 * @method array getRecentTransactions(int $userId, int $limit = 5)
 * @method array getCategoryBreakdown(int $userId, string $startDate, string $endDate, string $type = null)
 * @method array getLineChartData(int $userId, int $months = 6)
 */
class Transaction
{
    private $db;

    public function __construct()
    {
        $this->db = (new ConnectDB())->getConnection();
    }

    // [QUAN TRỌNG] Hàm này bắt buộc phải có để tính toán khi sửa/xóa
    public function getById($id)
    {
        $stmt = $this->db->prepare("SELECT * FROM transactions WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getAllByUser($userId, $filters = [])
    {
        $sql = "SELECT t.*, c.name as category_name, c.icon as category_icon, c.type as category_type 
                FROM transactions t
                LEFT JOIN categories c ON t.category_id = c.id
                WHERE t.user_id = :user_id";

        $params = [':user_id' => $userId];

        if (!empty($filters['range'])) {
            $sql .= " AND DATE_FORMAT(t.date, '%Y-%m') = :range";
            $params[':range'] = $filters['range'];
        }

        if (!empty($filters['category_id'])) {
            $sql .= " AND t.category_id = :category_id";
            $params[':category_id'] = $filters['category_id'];
        }

        $sql .= " ORDER BY t.date DESC, t.created_at DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getRecent($userId, $limit = 5)
    {
        $sql = "SELECT t.*, c.name as category_name 
                FROM transactions t
                JOIN categories c ON t.category_id = c.id
                WHERE t.user_id = ? 
                ORDER BY t.date DESC, t.created_at DESC 
                LIMIT " . intval($limit);
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getTotalBalance($userId)
    {
        $stmt = $this->db->prepare("SELECT SUM(amount) as total FROM transactions WHERE user_id = ?");
        $stmt->execute([$userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'] ?? 0;
    }

    /**
     * Get totals (income and expense) for a given date range
     * Returns ['income' => float, 'expense' => float]
     */
    public function getTotalsForPeriod($userId, $startDate, $endDate)
    {
        $sql = "SELECT 
                    COALESCE(SUM(CASE WHEN amount > 0 THEN amount ELSE 0 END),0) as income,
                    COALESCE(SUM(CASE WHEN amount < 0 THEN -amount ELSE 0 END),0) as expense
                FROM transactions
                WHERE user_id = ? AND date BETWEEN ? AND ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId, $startDate, $endDate]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return [
            'income' => isset($row['income']) ? floatval($row['income']) : 0.0,
            'expense' => isset($row['expense']) ? floatval($row['expense']) : 0.0
        ];
    }

    /**
     * Return recent transactions (wrapper)
     */
    public function getRecentTransactions($userId, $limit = 5)
    {
        return $this->getRecent($userId, $limit);
    }

    /**
     * Get category breakdown for a period, filtered by type (income|expense|null)
     * Returns array of ['name'=>..., 'total'=>...] ordered by total desc
     */
    public function getCategoryBreakdown($userId, $startDate, $endDate, $type = null)
    {
        $sql = "SELECT c.name as name, COALESCE(SUM(CASE WHEN t.amount < 0 THEN -t.amount WHEN t.amount > 0 THEN t.amount ELSE 0 END),0) as total
                FROM transactions t
                LEFT JOIN categories c ON t.category_id = c.id
                WHERE t.user_id = ? AND t.date BETWEEN ? AND ?";
        $params = [$userId, $startDate, $endDate];
        if ($type === 'expense') {
            $sql .= " AND t.type = 'expense'";
        } elseif ($type === 'income') {
            $sql .= " AND t.type = 'income'";
        }
        $sql .= " GROUP BY c.id ORDER BY total DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        // Normalize output
        $out = [];
        foreach ($rows as $r) {
            $out[] = ['name' => $r['name'] ?? 'Khác', 'total' => floatval($r['total'])];
        }
        return $out;
    }

    /**
     * Produce line chart data (monthly totals) for recent months
     * Returns ['labels'=>[], 'income'=>[], 'expense'=>[]]
     */
    public function getLineChartData($userId, $months = 6)
    {
        // Build months array (YYYY-MM) ending with current month
        $labels = [];
        $dates = [];
        for ($i = $months - 1; $i >= 0; $i--) {
            $m = new \DateTime("first day of -{$i} months");
            $label = $m->format('M Y');
            $key = $m->format('Y-m');
            $labels[] = $label;
            $dates[] = $key;
        }

        $placeholders = implode(',', array_fill(0, count($dates), '?'));
        $sql = "SELECT DATE_FORMAT(date, '%Y-%m') as ym, 
                    COALESCE(SUM(CASE WHEN amount > 0 THEN amount ELSE 0 END),0) as income,
                    COALESCE(SUM(CASE WHEN amount < 0 THEN -amount ELSE 0 END),0) as expense
                FROM transactions
                WHERE user_id = ? AND DATE_FORMAT(date, '%Y-%m') IN ($placeholders)
                GROUP BY ym";

        $params = array_merge([$userId], $dates);
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $map = [];
        foreach ($rows as $r) {
            $map[$r['ym']] = ['income' => floatval($r['income']), 'expense' => floatval($r['expense'])];
        }

        $incomeData = [];
        $expenseData = [];
        foreach ($dates as $d) {
            if (isset($map[$d])) {
                $incomeData[] = $map[$d]['income'];
                $expenseData[] = $map[$d]['expense'];
            } else {
                $incomeData[] = 0;
                $expenseData[] = 0;
            }
        }

        return ['labels' => $labels, 'income' => $incomeData, 'expense' => $expenseData];
    }
    
    // --- CÁC HÀM CRUD CƠ BẢN ---
    public function create($data) { /* Logic đã xử lý ở Controller */ }

    // Chỉ update thông tin cơ bản, việc tính toán ví làm ở Controller
    public function update($id, $data)
    {
        $sql = "UPDATE transactions SET 
                amount = :amount, 
                category_id = :category_id, 
                date = :date, 
                description = :description,
                type = :type
                WHERE id = :id";
        
        $data['id'] = $id;
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($data);
    }

    public function deleteTransaction($id, $userId)
    {
        $stmt = $this->db->prepare("DELETE FROM transactions WHERE id = ? AND user_id = ?");
        return $stmt->execute([$id, $userId]);
    }
}