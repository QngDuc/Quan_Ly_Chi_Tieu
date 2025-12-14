<?php
namespace App\Models;

use App\Core\ConnectDB;
use PDO;

class Goal {
    private $db;
    
    public function __construct() {
        $connectDB = new ConnectDB();
        $this->db = $connectDB->getConnection();
        // Ensure goal_transactions table exists (some schemas may not include it)
        try {
            $check = $this->db->prepare("SHOW TABLES LIKE 'goal_transactions'");
            $check->execute();
            $exists = (bool)$check->fetch(PDO::FETCH_ASSOC);
            if (!$exists) {
                $createSql = "CREATE TABLE IF NOT EXISTS goal_transactions (
                    id INT NOT NULL AUTO_INCREMENT,
                    goal_id INT NOT NULL,
                    transaction_id INT NOT NULL,
                    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
                $this->db->exec($createSql);
            }
        } catch (\Exception $e) {
            // Ignore — operations will gracefully handle missing table later
        }
    }
    
    /**
     * Lấy danh sách mục tiêu (Logic THỦ CÔNG: Tính tổng từ bảng goal_transactions)
     */
    public function getByUserId($userId) {
        $sql = "SELECT g.*, 
                       -- Tính tổng tiền đã nạp vào mục tiêu này từ bảng goal_transactions
                       -- Dùng ABS vì giao dịch chi ra thường là số âm
                       COALESCE(SUM(ABS(t.amount)), 0) as current_amount, 
                       CASE 
                           WHEN g.target_amount > 0 THEN 
                               ROUND((COALESCE(SUM(ABS(t.amount)), 0) / g.target_amount) * 100, 2)
                           ELSE 0 
                       END as progress_percentage
                FROM goals g
                -- Join bảng trung gian để lấy các giao dịch đã được nạp thủ công
                LEFT JOIN goal_transactions gt ON g.id = gt.goal_id
                LEFT JOIN transactions t ON gt.transaction_id = t.id
                WHERE g.user_id = :user_id
                GROUP BY g.id
                ORDER BY g.created_at DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Lấy chi tiết 1 Goal (Logic thủ công)
     */
    public function getById($id, $userId) {
        $sql = "SELECT g.*,
                       COALESCE(SUM(ABS(t.amount)), 0) as current_amount,
                       CASE
                           WHEN g.target_amount > 0 THEN ROUND((COALESCE(SUM(ABS(t.amount)), 0) / g.target_amount) * 100, 2)
                           ELSE 0
                       END as progress_percentage
                FROM goals g
                LEFT JOIN goal_transactions gt ON g.id = gt.goal_id
                LEFT JOIN transactions t ON gt.transaction_id = t.id
                WHERE g.id = :id AND g.user_id = :user_id
                GROUP BY g.id
                LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id, ':user_id' => $userId]);
        $goal = $stmt->fetch(PDO::FETCH_ASSOC);
        return $goal ?: false;
    }
    
    /**
     * Hàm nạp tiền vào mục tiêu (Quan trọng nhất)
     */
    public function deposit($userId, $goalId, $amount, $date, $note) {
        try {
            // Prevent rapid duplicate deposits (double-submit protection)
            $checkSql = "SELECT t.id FROM transactions t
                         WHERE t.user_id = :uid AND t.amount = :amount AND t.date = :date
                         AND t.description LIKE 'Nạp mục tiêu:%' AND t.created_at >= DATE_SUB(NOW(), INTERVAL 5 SECOND) LIMIT 1";
            $chk = $this->db->prepare($checkSql);
            $chk->execute([':uid' => $userId, ':amount' => -abs($amount), ':date' => $date]);
            $existing = $chk->fetch(PDO::FETCH_ASSOC);
            if ($existing) {
                // Ensure link exists in goal_transactions
                $existsLink = $this->db->prepare("SELECT id FROM goal_transactions WHERE goal_id = :gid AND transaction_id = :tid LIMIT 1");
                $existsLink->execute([':gid' => $goalId, ':tid' => $existing['id']]);
                if (!$existsLink->fetch(PDO::FETCH_ASSOC)) {
                    $ins = $this->db->prepare("INSERT INTO goal_transactions (goal_id, transaction_id, created_at) VALUES (:gid, :tid, NOW())");
                    $ins->execute([':gid' => $goalId, ':tid' => $existing['id']]);
                }
                return true;
            }

            $this->db->beginTransaction();

            // 1. Lấy thông tin goal để biết category_id (nếu có)
            $stmtGoal = $this->db->prepare("SELECT category_id, name FROM goals WHERE id = ?");
            $stmtGoal->execute([$goalId]);
            $goal = $stmtGoal->fetch(PDO::FETCH_ASSOC);
            
            // Nếu goal ko có category, dùng category ID 1 (Hoặc tìm category "Tiết kiệm" nếu bạn có logic đó)
            $catId = $goal['category_id'] ?? 1; 

            // 2. Tạo giao dịch Expense (để trừ tiền ví chính)
            // Nạp vào heo đất = Chi tiền ra khỏi ví -> Type: expense, Số tiền âm
            $transactionAmount = -abs($amount); 
            
            $sqlTrans = "INSERT INTO transactions (user_id, category_id, amount, date, description, type, created_at) 
                         VALUES (:uid, :cid, :amount, :date, :desc, 'expense', NOW())";
            $stmtTrans = $this->db->prepare($sqlTrans);
            $stmtTrans->execute([
                ':uid' => $userId,
                ':cid' => $catId,
                ':amount' => $transactionAmount,
                ':date' => $date,
                ':desc' => "Nạp mục tiêu: " . ($note ? $note : $goal['name'])
            ]);
            $transactionId = $this->db->lastInsertId();

            // 3. Liên kết giao dịch này vào bảng goal_transactions
            $sqlLink = "INSERT INTO goal_transactions (goal_id, transaction_id, created_at) VALUES (:gid, :tid, NOW())";
            $stmtLink = $this->db->prepare($sqlLink);
            $stmtLink->execute([':gid' => $goalId, ':tid' => $transactionId]);

            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }
    
    // Giữ nguyên các hàm create, update, delete cũ...
    public function create($data) {
        // Guard against rapid duplicate goal creation (double-submit)
        $dupSql = "SELECT id FROM goals WHERE user_id = :user_id AND name = :name AND target_amount = :target_amount
                   AND deadline = :deadline AND created_at >= DATE_SUB(NOW(), INTERVAL 5 SECOND) LIMIT 1";
        $dupStmt = $this->db->prepare($dupSql);
        $dupStmt->execute([
            ':user_id' => $data['user_id'],
            ':name' => $data['name'],
            ':target_amount' => $data['target_amount'],
            ':deadline' => $data['deadline']
        ]);
        if ($dupStmt->fetch(PDO::FETCH_ASSOC)) {
            return true; // consider duplicate as success to avoid creating twice
        }

        $sql = "INSERT INTO goals (user_id, name, description, target_amount, start_date, deadline, category_id, status, created_at) 
                VALUES (:user_id, :name, :description, :target_amount, :start_date, :deadline, :category_id, :status, NOW())";
        $stmt = $this->db->prepare($sql);
        // ... (Bind param như cũ) ...
        $stmt->bindParam(':user_id', $data['user_id'], PDO::PARAM_INT);
        $stmt->bindParam(':name', $data['name'], PDO::PARAM_STR);
        $stmt->bindParam(':description', $data['description'], PDO::PARAM_STR);
        $stmt->bindParam(':target_amount', $data['target_amount'], PDO::PARAM_STR);
        
        $startDate = !empty($data['start_date']) ? $data['start_date'] : null;
        $categoryId = !empty($data['category_id']) ? $data['category_id'] : null;
        
        $stmt->bindParam(':start_date', $startDate, $startDate ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->bindParam(':deadline', $data['deadline'], PDO::PARAM_STR);
        $stmt->bindParam(':category_id', $categoryId, $categoryId ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $stmt->bindParam(':status', $data['status'], PDO::PARAM_STR);
        return $stmt->execute();
    }

    public function update($id, $userId, $data) {
        $sql = "UPDATE goals SET name=:name, description=:description, target_amount=:target_amount, start_date=:start_date, deadline=:deadline, category_id=:category_id, status=:status, updated_at=NOW() WHERE id=:id AND user_id=:user_id";
        $stmt = $this->db->prepare($sql);
        // ... (Bind param tương tự create) ...
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindParam(':name', $data['name'], PDO::PARAM_STR);
        $stmt->bindParam(':description', $data['description'], PDO::PARAM_STR);
        $stmt->bindParam(':target_amount', $data['target_amount'], PDO::PARAM_STR);
        
        $startDate = !empty($data['start_date']) ? $data['start_date'] : null;
        $categoryId = !empty($data['category_id']) ? $data['category_id'] : null;
        
        $stmt->bindParam(':start_date', $startDate, $startDate ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->bindParam(':deadline', $data['deadline'], PDO::PARAM_STR);
        $stmt->bindParam(':category_id', $categoryId, $categoryId ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $stmt->bindParam(':status', $data['status'], PDO::PARAM_STR);
        return $stmt->execute();
    }

    public function delete($id, $userId) {
        try {
            $this->db->beginTransaction();

            // 1) Lấy danh sách transaction_id được liên kết với goal này
            $stmt = $this->db->prepare("SELECT transaction_id FROM goal_transactions WHERE goal_id = :id");
            $stmt->execute([':id' => $id]);
            $rows = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);

            // 2) Xóa các giao dịch nạp mục tiêu liên quan (chỉ những giao dịch có mô tả 'Nạp mục tiêu:%' và của user)
            if (!empty($rows)) {
                // Tạo chuỗi placeholders an toàn
                $placeholders = implode(',', array_fill(0, count($rows), '?'));
                $params = $rows;
                // Thêm userId làm tham số cuối
                $params[] = $userId;
                $delTransSql = "DELETE FROM transactions WHERE id IN ($placeholders) AND user_id = ? AND description LIKE 'Nạp mục tiêu:%'";
                $delTrans = $this->db->prepare($delTransSql);
                $delTrans->execute($params);
            }

            // 3) Xóa liên kết trong goal_transactions
            $delLinks = $this->db->prepare("DELETE FROM goal_transactions WHERE goal_id = :id");
            $delLinks->execute([':id' => $id]);

            // 4) Xóa bản ghi mục tiêu
            $delGoal = $this->db->prepare("DELETE FROM goals WHERE id = :id AND user_id = :user_id");
            $delGoal->execute([':id' => $id, ':user_id' => $userId]);

            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            try { $this->db->rollBack(); } catch (\Exception $ex) {}
            return false;
        }
    }
    
    public function getStatistics($userId) {
        // Logic thống kê đơn giản
        $goals = $this->getByUserId($userId);
        $totalSaved = 0;
        $active = 0; $completed = 0; $target = 0;
        foreach($goals as $g) {
            $totalSaved += $g['current_amount'];
            $target += $g['target_amount'];
            if($g['status'] == 'active') $active++;
            if($g['status'] == 'completed') $completed++;
        }
        return ['total_goals'=>count($goals), 'active_goals'=>$active, 'completed_goals'=>$completed, 'total_target'=>$target, 'total_saved'=>$totalSaved];
    }

    /**
     * Withdraw full saved amount from a goal back to main balance.
     * This will create a positive income transaction for the user and remove
     * linked "deposit" transactions that were used to fund the goal.
     */
    public function withdraw($userId, $goalId)
    {
        try {
            $goal = $this->getById($goalId, $userId);
            if (!$goal) return false;

            $currentAmount = isset($goal['current_amount']) ? floatval($goal['current_amount']) : 0.0;
            if ($currentAmount <= 0) return false;

            $this->db->beginTransaction();

            // Use the goal's category if available, otherwise fallback to 1
            $catId = $goal['category_id'] ?? 1;

            // 1) Create income transaction to represent returning money to main balance
            $sqlInc = "INSERT INTO transactions (user_id, category_id, amount, date, description, type, created_at)
                       VALUES (:uid, :cid, :amount, :date, :desc, 'income', NOW())";
            $stmtInc = $this->db->prepare($sqlInc);
            $stmtInc->execute([
                ':uid' => $userId,
                ':cid' => $catId,
                ':amount' => abs($currentAmount),
                ':date' => date('Y-m-d'),
                ':desc' => 'Rút mục tiêu: ' . ($goal['name'] ?? '')
            ]);

            // 2) Remove linked deposit transactions (those tied to this goal and with description 'Nạp mục tiêu:%')
            $stmtLinks = $this->db->prepare("SELECT transaction_id FROM goal_transactions WHERE goal_id = ?");
            $stmtLinks->execute([$goalId]);
            $rows = $stmtLinks->fetchAll(PDO::FETCH_COLUMN, 0);

            if (!empty($rows)) {
                // Delete only transactions that match the deposit description and belong to user
                $placeholders = implode(',', array_fill(0, count($rows), '?'));
                $params = $rows;
                $params[] = $userId;
                $delSql = "DELETE FROM transactions WHERE id IN ($placeholders) AND user_id = ? AND description LIKE 'Nạp mục tiêu:%'";
                $delStmt = $this->db->prepare($delSql);
                $delStmt->execute($params);

                // Remove goal_transactions links
                $delLinks = $this->db->prepare("DELETE FROM goal_transactions WHERE goal_id = ?");
                $delLinks->execute([$goalId]);
            }

            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            try { $this->db->rollBack(); } catch (\Exception $ex) {}
            return false;
        }
    }
}