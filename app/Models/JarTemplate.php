<?php
namespace App\Models;

use PDO;

class JarTemplate
{
    private $db;

    public function __construct()
    {
        $this->db = (new \App\Core\ConnectDB())->getConnection();
    }

    /**
     * Get all jar templates for a user
     */
    public function getByUser($userId)
    {
        $sql = "SELECT * FROM jar_templates 
                WHERE user_id = ? 
                ORDER BY order_index ASC, id ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get a single jar template
     */
    public function getById($id, $userId)
    {
        $sql = "SELECT * FROM jar_templates WHERE id = ? AND user_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id, $userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Create a new jar template
     */
    public function create($data)
    {
        $sql = "INSERT INTO jar_templates 
                (user_id, name, percentage, color, icon, description, order_index) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute([
            $data['user_id'],
            $data['name'],
            $data['percentage'],
            $data['color'] ?? '#6c757d',
            $data['icon'] ?? null,
            $data['description'] ?? null,
            $data['order_index'] ?? 0
        ]);
        
        return $result ? $this->db->lastInsertId() : false;
    }

    /**
     * Update a jar template
     */
    public function update($id, $userId, $data)
    {
        $sql = "UPDATE jar_templates 
                SET name = ?, 
                    percentage = ?, 
                    color = ?, 
                    icon = ?, 
                    description = ?, 
                    order_index = ?
                WHERE id = ? AND user_id = ?";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            $data['name'],
            $data['percentage'],
            $data['color'] ?? '#6c757d',
            $data['icon'] ?? null,
            $data['description'] ?? null,
            $data['order_index'] ?? 0,
            $id,
            $userId
        ]);
    }

    /**
     * Delete a jar template
     */
    public function delete($id, $userId)
    {
        $sql = "DELETE FROM jar_templates WHERE id = ? AND user_id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$id, $userId]);
    }

    /**
     * Get categories for a jar
     */
    public function getCategories($jarId)
    {
        $sql = "SELECT * FROM jar_categories 
                WHERE jar_id = ? 
                ORDER BY order_index ASC, id ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$jarId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Add category to a jar
     */
    public function addCategory($jarId, $categoryName, $orderIndex = 0)
    {
        $sql = "INSERT INTO jar_categories (jar_id, category_name, order_index) 
                VALUES (?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$jarId, $categoryName, $orderIndex]);
    }

    /**
     * Delete category from a jar
     */
    public function deleteCategory($categoryId)
    {
        $sql = "DELETE FROM jar_categories WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$categoryId]);
    }

    /**
     * Calculate total percentage for user's jars
     */
    public function getTotalPercentage($userId)
    {
        $sql = "SELECT SUM(percentage) as total FROM jar_templates WHERE user_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'] ?? 0;
    }

    /**
     * Reorder jars
     */
    public function reorder($userId, $jarOrders)
    {
        $this->db->beginTransaction();
        try {
            foreach ($jarOrders as $order) {
                $sql = "UPDATE jar_templates SET order_index = ? WHERE id = ? AND user_id = ?";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([$order['order_index'], $order['id'], $userId]);
            }
            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }
}
