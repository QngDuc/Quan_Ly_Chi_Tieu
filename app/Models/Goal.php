<?php
namespace App\Models;

use App\Core\ConnectDB;
use PDO;

/**
 * Goal Model
 * Quản lý mục tiêu tiết kiệm
 */
class Goal {
    private $db;
    
    public function __construct() {
        $connectDB = new ConnectDB();
        $this->db = $connectDB->getConnection();
    }
    
    /**
     * Lấy tất cả mục tiêu của người dùng
     */
    public function getByUserId($userId) {
        $sql = "SELECT g.*, 
                       IFNULL(SUM(t.amount), 0) as current_amount,
                       CASE 
                           WHEN g.target_amount > 0 THEN ROUND((IFNULL(SUM(t.amount), 0) / g.target_amount) * 100, 2)
                           ELSE 0 
                       END as progress_percentage
                FROM goals g
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
     * Lấy mục tiêu theo ID
     */
    public function getById($id, $userId) {
        $sql = "SELECT g.*, 
                       IFNULL(SUM(t.amount), 0) as current_amount,
                       CASE 
                           WHEN g.target_amount > 0 THEN ROUND((IFNULL(SUM(t.amount), 0) / g.target_amount) * 100, 2)
                           ELSE 0 
                       END as progress_percentage
                FROM goals g
                LEFT JOIN goal_transactions gt ON g.id = gt.goal_id
                LEFT JOIN transactions t ON gt.transaction_id = t.id
                WHERE g.id = :id AND g.user_id = :user_id
                GROUP BY g.id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Tạo mục tiêu mới
     */
    public function create($data) {
        $sql = "INSERT INTO goals (user_id, name, description, target_amount, deadline, status, created_at) 
                VALUES (:user_id, :name, :description, :target_amount, :deadline, :status, NOW())";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':user_id', $data['user_id'], PDO::PARAM_INT);
        $stmt->bindParam(':name', $data['name'], PDO::PARAM_STR);
        $stmt->bindParam(':description', $data['description'], PDO::PARAM_STR);
        $stmt->bindParam(':target_amount', $data['target_amount'], PDO::PARAM_STR);
        $stmt->bindParam(':deadline', $data['deadline'], PDO::PARAM_STR);
        $stmt->bindParam(':status', $data['status'], PDO::PARAM_STR);
        
        return $stmt->execute();
    }
    
    /**
     * Cập nhật mục tiêu
     */
    public function update($id, $userId, $data) {
        $sql = "UPDATE goals 
                SET name = :name, 
                    description = :description, 
                    target_amount = :target_amount, 
                    deadline = :deadline, 
                    status = :status,
                    updated_at = NOW()
                WHERE id = :id AND user_id = :user_id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindParam(':name', $data['name'], PDO::PARAM_STR);
        $stmt->bindParam(':description', $data['description'], PDO::PARAM_STR);
        $stmt->bindParam(':target_amount', $data['target_amount'], PDO::PARAM_STR);
        $stmt->bindParam(':deadline', $data['deadline'], PDO::PARAM_STR);
        $stmt->bindParam(':status', $data['status'], PDO::PARAM_STR);
        
        return $stmt->execute();
    }
    
    /**
     * Xóa mục tiêu
     */
    public function delete($id, $userId) {
        // Xóa các liên kết goal_transactions trước
        $sqlDeleteLinks = "DELETE FROM goal_transactions WHERE goal_id = :id";
        $stmtDeleteLinks = $this->db->prepare($sqlDeleteLinks);
        $stmtDeleteLinks->bindParam(':id', $id, PDO::PARAM_INT);
        $stmtDeleteLinks->execute();
        
        // Xóa mục tiêu
        $sql = "DELETE FROM goals WHERE id = :id AND user_id = :user_id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        
        return $stmt->execute();
    }
    
    /**
     * Liên kết transaction với goal
     */
    public function linkTransaction($goalId, $transactionId) {
        $sql = "INSERT INTO goal_transactions (goal_id, transaction_id, created_at) 
                VALUES (:goal_id, :transaction_id, NOW())";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':goal_id', $goalId, PDO::PARAM_INT);
        $stmt->bindParam(':transaction_id', $transactionId, PDO::PARAM_INT);
        
        return $stmt->execute();
    }
    
    /**
     * Hủy liên kết transaction với goal
     */
    public function unlinkTransaction($goalId, $transactionId) {
        $sql = "DELETE FROM goal_transactions 
                WHERE goal_id = :goal_id AND transaction_id = :transaction_id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':goal_id', $goalId, PDO::PARAM_INT);
        $stmt->bindParam(':transaction_id', $transactionId, PDO::PARAM_INT);
        
        return $stmt->execute();
    }
    
    /**
     * Lấy các giao dịch liên kết với mục tiêu
     */
    public function getGoalTransactions($goalId, $userId) {
        $sql = "SELECT t.*, c.name as category_name, c.color as category_color
                FROM transactions t
                INNER JOIN goal_transactions gt ON t.id = gt.transaction_id
                INNER JOIN categories c ON t.category_id = c.id
                WHERE gt.goal_id = :goal_id AND t.user_id = :user_id
                ORDER BY t.date DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':goal_id', $goalId, PDO::PARAM_INT);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Cập nhật trạng thái mục tiêu
     */
    public function updateStatus($id, $userId, $status) {
        $sql = "UPDATE goals 
                SET status = :status, updated_at = NOW()
                WHERE id = :id AND user_id = :user_id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindParam(':status', $status, PDO::PARAM_STR);
        
        return $stmt->execute();
    }
    
    /**
     * Lấy thống kê tổng quan
     */
    public function getStatistics($userId) {
        $sql = "SELECT 
                    COUNT(*) as total_goals,
                    SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_goals,
                    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_goals,
                    SUM(target_amount) as total_target,
                    (SELECT IFNULL(SUM(t.amount), 0) 
                     FROM goal_transactions gt 
                     INNER JOIN transactions t ON gt.transaction_id = t.id 
                     INNER JOIN goals g ON gt.goal_id = g.id 
                     WHERE g.user_id = :user_id) as total_saved
                FROM goals 
                WHERE user_id = :user_id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
