<?php
namespace App\Models;

use App\Core\ConnectDB;
use PDO;

/**
 * RecurringTransaction Model
 * Handles automatic recurring transactions
 */
class RecurringTransaction
{
    private $db;

    public function __construct()
    {
        $this->db = (new ConnectDB())->getConnection();
    }

    /**
     * Get all recurring transactions for a user with category details
     */
    public function getByUser($userId)
    {
        $sql = "SELECT rt.*, c.name as category_name, c.type, c.color, c.icon 
                FROM recurring_transactions rt
                INNER JOIN categories c ON rt.category_id = c.id
                WHERE rt.user_id = ?
                ORDER BY rt.is_active DESC, rt.next_occurrence ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get recurring transaction by ID
     */
    public function getById($id, $userId)
    {
        $sql = "SELECT * FROM recurring_transactions WHERE id = ? AND user_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id, $userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Create new recurring transaction
     */
    public function create($data)
    {
        $sql = "INSERT INTO recurring_transactions 
                (user_id, category_id, amount, type, description, frequency, start_date, end_date, next_occurrence, is_active) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $data['user_id'],
            $data['category_id'],
            $data['amount'],
            $data['type'],
            $data['description'],
            $data['frequency'],
            $data['start_date'],
            $data['end_date'],
            $data['next_occurrence'],
            $data['is_active']
        ]);
        
        return $this->db->lastInsertId();
    }

    /**
     * Update recurring transaction
     */
    public function update($id, $userId, $data)
    {
        $fields = [];
        $values = [];
        
        foreach ($data as $key => $value) {
            $fields[] = "$key = ?";
            $values[] = $value;
        }
        
        $values[] = $id;
        $values[] = $userId;
        
        $sql = "UPDATE recurring_transactions SET " . implode(', ', $fields) . " WHERE id = ? AND user_id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($values);
    }

    /**
     * Delete recurring transaction
     */
    public function delete($id, $userId)
    {
        $sql = "DELETE FROM recurring_transactions WHERE id = ? AND user_id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$id, $userId]);
    }

    /**
     * Get due recurring transactions (for cron job)
     */
    public function getDueTransactions()
    {
        $sql = "SELECT rt.*, c.type as category_type 
                FROM recurring_transactions rt
                INNER JOIN categories c ON rt.category_id = c.id
                WHERE rt.is_active = 1 
                AND rt.next_occurrence <= CURDATE()
                AND (rt.end_date IS NULL OR rt.end_date >= CURDATE())";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Process recurring transaction (create actual transaction)
     */
    public function process($recurring)
    {
        // Insert into transactions table
        $sql = "INSERT INTO transactions (user_id, category_id, amount, type, description, transaction_date, created_at)
                VALUES (?, ?, ?, ?, ?, ?, NOW())";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $recurring['user_id'],
            $recurring['category_id'],
            $recurring['amount'],
            $recurring['type'],
            $recurring['description'] . ' (Tự động)',
            $recurring['next_occurrence']
        ]);
        
        // Calculate next occurrence
        $nextOccurrence = $this->calculateNextOccurrence($recurring['next_occurrence'], $recurring['frequency']);
        
        // Update next_occurrence
        $updateSql = "UPDATE recurring_transactions SET next_occurrence = ? WHERE id = ?";
        $updateStmt = $this->db->prepare($updateSql);
        $updateStmt->execute([$nextOccurrence, $recurring['id']]);
        
        return true;
    }

    /**
     * Calculate next occurrence date
     */
    private function calculateNextOccurrence($currentDate, $frequency)
    {
        $date = new \DateTime($currentDate);
        
        switch ($frequency) {
            case 'daily':
                $date->modify('+1 day');
                break;
            case 'weekly':
                $date->modify('+1 week');
                break;
            case 'monthly':
                $date->modify('+1 month');
                break;
            case 'yearly':
                $date->modify('+1 year');
                break;
        }
        
        return $date->format('Y-m-d');
    }

    /**
     * Get stats for dashboard
     */
    public function getStats($userId)
    {
        $sql = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active,
                    SUM(CASE WHEN type = 'income' AND is_active = 1 AND frequency = 'monthly' THEN amount ELSE 0 END) as monthly_income,
                    SUM(CASE WHEN type = 'expense' AND is_active = 1 AND frequency = 'monthly' THEN amount ELSE 0 END) as monthly_expense
                FROM recurring_transactions
                WHERE user_id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
