<?php

namespace App\Models;

use App\Core\ConnectDB;
use \PDO;

class Budget
{
    private $db;
    private $hasIsActive = false;

    public function __construct()
    {
        $this->db = (new ConnectDB())->getConnection();
        // Detect if the budgets table has an `is_active` column in this schema
        try {
            $stmt = $this->db->prepare("SHOW COLUMNS FROM budgets LIKE 'is_active'");
            $stmt->execute();
            $this->hasIsActive = (bool)$stmt->fetch(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            $this->hasIsActive = false;
        }
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
                COALESCE(c.group_type, 'needs') as category_group,
                COALESCE(SUM(ABS(t.amount)), 0) as spent, 
                CASE WHEN b.amount IS NULL OR b.amount = 0 THEN 0 ELSE ROUND((COALESCE(SUM(ABS(t.amount)),0) / NULLIF(b.amount,0)) * 100, 2) END as percentage_used, 
                (b.amount - COALESCE(SUM(ABS(t.amount)), 0)) as remaining 
            FROM budgets b
            INNER JOIN categories c ON b.category_id = c.id
            LEFT JOIN transactions t ON t.category_id = b.category_id
                AND t.user_id = b.user_id
                AND t.type = 'expense'
                AND t.date BETWEEN ? AND ?
        ";

        // Append WHERE clause depending on schema
        $sql .= " WHERE b.user_id = ?";
        if ($this->hasIsActive) {
            $sql .= " AND b.is_active = 1";
        }
        $sql .= "\n            GROUP BY b.id, c.name, c.color, c.icon, c.type, c.group_type\n            ORDER BY b.id DESC\n        ";

        $params = [$startDate, $endDate, $userId];
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
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
        $selectSql = "SELECT id FROM budgets WHERE user_id = ? AND category_id = ? AND period = ?";
        if ($this->hasIsActive) {
            $selectSql .= " AND is_active = 1";
        }
        $stmt = $this->db->prepare($selectSql);
        $stmt->execute([$data['user_id'], $data['category_id'], $data['period']]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existing) {
            throw new \Exception('Ngân sách cho danh mục này đã tồn tại trong kỳ này');
        }

        // Build INSERT depending on whether `is_active` column exists
        if ($this->hasIsActive) {
            $sql = "INSERT INTO budgets (user_id, category_id, amount, period, start_date, end_date, alert_threshold, is_active, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";
            $params = [
                $data['user_id'],
                $data['category_id'],
                $data['amount'],
                $data['period'],
                $data['start_date'],
                $data['end_date'],
                $data['alert_threshold'],
                ($data['is_active'] ?? 1)
            ];
        } else {
            $sql = "INSERT INTO budgets (user_id, category_id, amount, period, start_date, end_date, alert_threshold, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
            $params = [
                $data['user_id'],
                $data['category_id'],
                $data['amount'],
                $data['period'],
                $data['start_date'],
                $data['end_date'],
                $data['alert_threshold']
            ];
        }

        $stmt = $this->db->prepare($sql);
        try {
            $result = $stmt->execute($params);
        } catch (\PDOException $e) {
            $msg = $e->getMessage();
            // If DB missing alert_threshold or created_at, retry with a compatible INSERT
            if (stripos($msg, 'Unknown column') !== false || stripos($msg, 'field list') !== false) {
                // Try without alert_threshold but with created_at
                try {
                    $sql2 = "INSERT INTO budgets (user_id, category_id, amount, period, start_date, end_date, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())";
                    $stmt2 = $this->db->prepare($sql2);
                    $result = $stmt2->execute([
                        $data['user_id'],
                        $data['category_id'],
                        $data['amount'],
                        $data['period'],
                        $data['start_date'],
                        $data['end_date']
                    ]);
                } catch (\PDOException $e2) {
                    // Try without created_at (some very old schemas)
                    $sql3 = "INSERT INTO budgets (user_id, category_id, amount, period, start_date, end_date, alert_threshold) VALUES (?, ?, ?, ?, ?, ?, ?)";
                    $stmt3 = $this->db->prepare($sql3);
                    $result = $stmt3->execute([
                        $data['user_id'],
                        $data['category_id'],
                        $data['amount'],
                        $data['period'],
                        $data['start_date'],
                        $data['end_date'],
                        $data['alert_threshold']
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
        if ($this->hasIsActive && isset($data['is_active'])) {
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
                COALESCE(SUM(ABS(t.amount)), 0) as total_spent
            FROM budgets b
            LEFT JOIN transactions t ON t.category_id = b.category_id
                AND t.user_id = b.user_id
                AND t.type = 'expense'
                AND t.date BETWEEN ? AND ?
            WHERE b.user_id = ?
        ";

        if ($this->hasIsActive) {
            $sql .= " AND b.is_active = 1";
        }
        

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
                COALESCE(SUM(ABS(t.amount)), 0) as spent,
                ROUND((COALESCE(SUM(ABS(t.amount)), 0) / b.amount) * 100, 2) as percentage_used
            FROM budgets b
            INNER JOIN categories c ON b.category_id = c.id
            LEFT JOIN transactions t ON 
                t.category_id = b.category_id 
                AND t.user_id = b.user_id
                AND t.type = 'expense'
                AND t.date BETWEEN ? AND ?
            WHERE b.user_id = ?
            GROUP BY b.id, c.name
            HAVING percentage_used >= b.alert_threshold
            ORDER BY percentage_used DESC
        ";

        if ($this->hasIsActive) {
            // if has is_active we need to insert the condition
            $sql = str_replace('WHERE b.user_id = ?', "WHERE b.user_id = ? AND b.is_active = 1", $sql);
        }

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
                                WHERE b.user_id = ?
                                    AND NOT (b.end_date < ? OR b.start_date > ?)
                        ";
                        if ($this->hasIsActive) {
                                $sqlBud = str_replace('WHERE b.user_id = ?', "WHERE b.user_id = ? AND b.is_active = 1", $sqlBud);
                        }
            $stmt = $this->db->prepare($sqlBud);
            $stmt->execute([$userId, $start, $end]);
            $rowB = $stmt->fetch(PDO::FETCH_ASSOC);
            $totalBudget = isset($rowB['total_budget']) ? floatval($rowB['total_budget']) : 0.0;

            // Sum spent in that month across expense transactions
            $sqlSpent = "
                SELECT COALESCE(SUM(ABS(t.amount)),0) as total_spent
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

    /**
     * Lấy cài đặt tỷ lệ 6 hũ
     */
    public function getUserSmartSettings($userId)
    {
        $stmt = $this->db->prepare("SELECT * FROM user_budget_settings WHERE user_id = ?");
        $stmt->execute([$userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$result) {
            $this->initUserSmartSettings($userId);
            // Trả về mặc định chuẩn 6 hũ
            return [
                'nec_percent' => 55,
                'ffa_percent' => 10,
                'ltss_percent' => 10,
                'edu_percent' => 10,
                'play_percent' => 10,
                'give_percent' => 5
            ];
        }
        return $result;
    }

    /**
     * Khởi tạo mặc định
     */
    public function initUserSmartSettings($userId)
    {
        $sql = "INSERT IGNORE INTO user_budget_settings 
                (user_id, nec_percent, ffa_percent, ltss_percent, edu_percent, play_percent, give_percent) 
                VALUES (?, 55, 10, 10, 10, 10, 5)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$userId]);
    }

    /**
     * Cập nhật tỷ lệ 6 hũ
     */
    public function updateUserSmartSettings($userId, $nec, $ffa, $ltss, $edu, $play, $give)
    {
        if (($nec + $ffa + $ltss + $edu + $play + $give) != 100) return false;

        $this->getUserSmartSettings($userId); // Đảm bảo record tồn tại

        $sql = "UPDATE user_budget_settings SET 
                nec_percent = ?, ffa_percent = ?, ltss_percent = ?, 
                edu_percent = ?, play_percent = ?, give_percent = ? 
                WHERE user_id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$nec, $ffa, $ltss, $edu, $play, $give, $userId]);
    }

    /**
     * Lấy mảng 6 hũ (jar) cho user. Trả về mảng 6 số nguyên theo thứ tự [nec, ffa, ltss, edu, play, give]
     */
    public function getUserJars($userId)
    {
        $stmt = $this->db->prepare("SELECT * FROM user_budget_settings WHERE user_id = ?");
        $stmt->execute([$userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            $this->initUserSmartSettings($userId);
            return [55,10,10,10,10,5];
        }

        // Prefer explicit nec_* columns if present
        if (array_key_exists('nec_percent', $row)) {
            return [
                intval($row['nec_percent'] ?? 0),
                intval($row['ffa_percent'] ?? 0),
                intval($row['ltss_percent'] ?? 0),
                intval($row['edu_percent'] ?? 0),
                intval($row['play_percent'] ?? 0),
                intval($row['give_percent'] ?? 0)
            ];
        }

        // Fallback to jar1..jar6 if present
        if (array_key_exists('jar1_percent', $row)) {
            return [
                intval($row['jar1_percent'] ?? 0),
                intval($row['jar2_percent'] ?? 0),
                intval($row['jar3_percent'] ?? 0),
                intval($row['jar4_percent'] ?? 0),
                intval($row['jar5_percent'] ?? 0),
                intval($row['jar6_percent'] ?? 0)
            ];
        }

        // As a last resort, try to map existing 3-way settings into 6 jars
        $settings = $this->getUserSmartSettings($userId);
        $needs = $settings['needs_percent'] ?? 50;
        $wants = $settings['wants_percent'] ?? 30;
        $savings = $settings['savings_percent'] ?? 20;
        $map = [
            (int)round($needs * 0.6),
            (int)round($needs * 0.4),
            (int)round($wants * 0.5),
            (int)round($wants * 0.5),
            (int)round($savings * 0.6),
            (int)round($savings * 0.4)
        ];
        $tot = array_sum($map) ?: 1;
        for ($i = 0; $i < 6; $i++) $map[$i] = (int)round($map[$i] / $tot * 100);
        $rem = 100 - array_sum($map);
        for ($i = 0; $i < $rem; $i++) $map[$i % 6]++;
        return $map;
    }

    /**
     * Cập nhật 6 hũ cho user. $jars là mảng 6 số nguyên theo thứ tự [nec, ffa, ltss, edu, play, give]
     */
    public function updateUserJars($userId, array $jars)
    {
        if (count($jars) !== 6) return false;
        $jars = array_map('intval', $jars);
        if (array_sum($jars) !== 100) return false;

        // Ensure record exists
        $this->getUserSmartSettings($userId);

        // Read one row to detect which columns exist
        $stmt = $this->db->prepare("SELECT * FROM user_budget_settings WHERE user_id = ?");
        $stmt->execute([$userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        $sets = [];
        $params = [];

        if ($row && array_key_exists('nec_percent', $row)) {
            $sets[] = 'nec_percent = ?'; $params[] = $jars[0];
            $sets[] = 'ffa_percent = ?'; $params[] = $jars[1];
            $sets[] = 'ltss_percent = ?'; $params[] = $jars[2];
            $sets[] = 'edu_percent = ?'; $params[] = $jars[3];
            $sets[] = 'play_percent = ?'; $params[] = $jars[4];
            $sets[] = 'give_percent = ?'; $params[] = $jars[5];
        }

        if ($row && array_key_exists('jar1_percent', $row)) {
            $sets[] = 'jar1_percent = ?'; $params[] = $jars[0];
            $sets[] = 'jar2_percent = ?'; $params[] = $jars[1];
            $sets[] = 'jar3_percent = ?'; $params[] = $jars[2];
            $sets[] = 'jar4_percent = ?'; $params[] = $jars[3];
            $sets[] = 'jar5_percent = ?'; $params[] = $jars[4];
            $sets[] = 'jar6_percent = ?'; $params[] = $jars[5];
        }

        // If no known columns, attempt to write nec_* columns (best effort)
        if (empty($sets)) {
            $sets = [
                'nec_percent = ?', 'ffa_percent = ?', 'ltss_percent = ?',
                'edu_percent = ?', 'play_percent = ?', 'give_percent = ?'
            ];
            $params = $jars;
        }

        $sql = 'UPDATE user_budget_settings SET ' . implode(', ', $sets) . ' WHERE user_id = ?';
        $params[] = $userId;
        $stmt2 = $this->db->prepare($sql);
        try {
            return $stmt2->execute($params);
        } catch (\PDOException $e) {
            // If update fails (missing columns), try fallback: update only jar1..jar6 using separate statement
            try {
                $sql2 = 'UPDATE user_budget_settings SET jar1_percent = ?, jar2_percent = ?, jar3_percent = ?, jar4_percent = ?, jar5_percent = ?, jar6_percent = ? WHERE user_id = ?';
                $stmt3 = $this->db->prepare($sql2);
                return $stmt3->execute([$jars[0],$jars[1],$jars[2],$jars[3],$jars[4],$jars[5], $userId]);
            } catch (\Exception $ex) {
                return false;
            }
        }
    }
}
