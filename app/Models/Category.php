<?php
namespace App\Models;

use App\Core\ConnectDB;
use App\Services\FinancialUtils;
use \PDO;

class Category
{
    private $db;

    public function __construct()
    {
        $this->db = (new ConnectDB())->getConnection();
    }

    /**
     * Get all categories (default + user-specific)
     */
    public function getAll($userId = null)
    {
        if ($userId === null) {
            // Only default categories
            $stmt = $this->db->prepare("SELECT * FROM categories WHERE user_id IS NULL ORDER BY type, name");
            $stmt->execute();
        } else {
            // Default categories + user's custom categories
            $stmt = $this->db->prepare("SELECT * FROM categories WHERE user_id IS NULL OR user_id = ? ORDER BY type, name");
            $stmt->execute([$userId]);
        }
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get expense categories only
     */
    public function getExpenseCategories($userId = null)
    {
        if ($userId === null) {
            $stmt = $this->db->prepare("SELECT * FROM categories WHERE type = 'expense' AND user_id IS NULL ORDER BY name");
            $stmt->execute();
        } else {
            $stmt = $this->db->prepare("SELECT * FROM categories WHERE type = 'expense' AND (user_id IS NULL OR user_id = ?) ORDER BY name");
            $stmt->execute([$userId]);
        }
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get income categories only
     */
    public function getIncomeCategories($userId = null)
    {
        if ($userId === null) {
            $stmt = $this->db->prepare("SELECT * FROM categories WHERE type = 'income' AND user_id IS NULL ORDER BY name");
            $stmt->execute();
        } else {
            $stmt = $this->db->prepare("SELECT * FROM categories WHERE type = 'income' AND (user_id IS NULL OR user_id = ?) ORDER BY name");
            $stmt->execute([$userId]);
        }
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get user's custom categories
     */
    public function getUserCategories($userId)
    {
        $stmt = $this->db->prepare("SELECT * FROM categories WHERE user_id = ? ORDER BY type, name");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get a category by ID
     */
    public function getById($id, $userId = null)
    {
        if ($userId === null) {
            $stmt = $this->db->prepare("SELECT * FROM categories WHERE id = ?");
            $stmt->execute([$id]);
        } else {
            // User can only access default categories or their own custom categories
            $stmt = $this->db->prepare("SELECT * FROM categories WHERE id = ? AND (user_id IS NULL OR user_id = ?)");
            $stmt->execute([$id, $userId]);
        }
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Create a new category (custom or default)
     * @param int|null $userId NULL for default categories (Admin), User ID for custom
     */
    public function create($userId, $data)
    {
        $isDefault = ($userId === null) ? 1 : 0;
        $sql = "INSERT INTO categories (user_id, name, type, color, icon, is_default) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute([
            $userId,
            $data['name'],
            $data['type'],
            $data['color'] ?? '#3498db',
            $data['icon'] ?? 'fa-circle',
            $isDefault
        ]);
        return $result ? $this->db->lastInsertId() : false;
    }

    /**
     * Update a category (custom or default)
     * @param int $id Category ID
     * @param int|null $userId NULL for admin updating default categories
     */
    public function update($id, $userId, $data)
    {
        if ($userId === null) {
            // Admin updating default category
            $sql = "UPDATE categories SET name = ?, type = ?, color = ?, icon = ? WHERE id = ? AND user_id IS NULL";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                $data['name'],
                $data['type'],
                $data['color'] ?? '#3498db',
                $data['icon'] ?? 'fa-circle',
                $id
            ]);
        } else {
            // User updating their own custom category
            $sql = "UPDATE categories SET name = ?, type = ?, color = ?, icon = ? WHERE id = ? AND user_id = ? AND is_default = 0";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                $data['name'],
                $data['type'],
                $data['color'] ?? '#3498db',
                $data['icon'] ?? 'fa-circle',
                $id,
                $userId
            ]);
        }
    }

    /**
     * Delete a category
     * @param int $id Category ID
     * @param int|null $userId NULL for admin deleting default categories
     * @return bool|string Returns true on success, error message on failure
     * 
     * NOTE: Foreign key constraint (ON DELETE RESTRICT) in database prevents
     * deletion if category has transactions. No manual check needed.
     */
    public function delete($id, $userId)
    {
        try {
            if ($userId === null) {
                // Admin deleting default category
                $sql = "DELETE FROM categories WHERE id = ? AND user_id IS NULL";
                $stmt = $this->db->prepare($sql);
                $result = $stmt->execute([$id]);
            } else {
                // User deleting their own custom category
                $sql = "DELETE FROM categories WHERE id = ? AND user_id = ? AND is_default = 0";
                $stmt = $this->db->prepare($sql);
                $result = $stmt->execute([$id, $userId]);
            }
            
            return $result;
            
        } catch (\PDOException $e) {
            // FK constraint violation (category has transactions)
            if ($e->getCode() == '23000') {
                return 'Không thể xóa danh mục đang có giao dịch';
            }
            // Other database errors
            return 'Lỗi database: ' . $e->getMessage();
        }
    }

    /**
     * Check if a category name already exists for a user
     */
    public function nameExists($name, $userId, $excludeId = null)
    {
        if ($excludeId) {
            $sql = "SELECT COUNT(*) as count FROM categories WHERE name = ? AND user_id = ? AND id != ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$name, $userId, $excludeId]);
        } else {
            $sql = "SELECT COUNT(*) as count FROM categories WHERE name = ? AND user_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$name, $userId]);
        }
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'] > 0;
    }

    /**
     * Get categories grouped by type
     */
    public function getAllGrouped($userId = null)
    {
        $categories = $this->getAll($userId);
        $grouped = [
            'income' => [],
            'expense' => []
        ];

        foreach ($categories as $category) {
            $grouped[$category['type']][] = $category;
        }

        return $grouped;
    }
}
