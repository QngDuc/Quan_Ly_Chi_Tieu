<?php
namespace App\Models;

use App\Core\ConnectDB;

class User
{
    private $db;

    public function __construct()
    {
        $this->db = (new ConnectDB())->getConnection();
    }

    public function createUser($username, $email, $password, $fullName)
    {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        // Luôn gán role là 'user' khi đăng ký mới
        $role = 'user';
        
        $stmt = $this->db->prepare("INSERT INTO users (username, email, password, full_name, role) VALUES (?, ?, ?, ?, ?)");
        if ($stmt->execute([$username, $email, $hashedPassword, $fullName, $role])) {
            return $this->db->lastInsertId();
        }
        return false;
    }

    public function authenticate($emailOrUsername, $password)
    {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE (email = ? OR username = ?) AND is_active = 1");
        $stmt->execute([$emailOrUsername, $emailOrUsername]);
        $user = $stmt->fetch(\PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            return $user;
        }
        return false;
    }

    public function isAdmin($userId)
    {
        $stmt = $this->db->prepare("SELECT role FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $user && $user['role'] === 'admin';
    }

    public function getAllUsers()
    {
        $stmt = $this->db->query("SELECT id, username, email, full_name, role, is_active, created_at FROM users ORDER BY created_at DESC");
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function updateUserStatus($userId, $isActive)
    {
        $stmt = $this->db->prepare("UPDATE users SET is_active = ? WHERE id = ?");
        return $stmt->execute([$isActive, $userId]);
    }

    public function updateUserRole($userId, $role)
    {
        // Check if user is super admin (cannot be demoted)
        $checkStmt = $this->db->prepare("SELECT is_super_admin FROM users WHERE id = ?");
        $checkStmt->execute([$userId]);
        $user = $checkStmt->fetch(\PDO::FETCH_ASSOC);
        
        if ($user && $user['is_super_admin'] == 1) {
            return false; // Cannot change super admin role
        }
        
        $stmt = $this->db->prepare("UPDATE users SET role = ? WHERE id = ?");
        return $stmt->execute([$role, $userId]);
    }

    /**
     * Check if user is super admin
     */
    public function isSuperAdmin($userId)
    {
        $stmt = $this->db->prepare("SELECT is_super_admin FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $user && $user['is_super_admin'] == 1;
    }

    public function getUserByUsername($username)
    {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    public function getUserByEmail($email)
    {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    public function getUserById($id)
    {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    public function updateProfile($userId, $data)
    {
        $stmt = $this->db->prepare("UPDATE users SET full_name = ?, email = ? WHERE id = ?");
        return $stmt->execute([$data['name'], $data['email'], $userId]);
    }

    public function updatePassword($userId, $newPassword)
    {
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $this->db->prepare("UPDATE users SET password = ? WHERE id = ?");
        return $stmt->execute([$hashedPassword, $userId]);
    }

    /**
     * Cập nhật cài đặt thông báo (Bước 2)
     * @param int $userId ID người dùng
     * @param string $column Tên cột cần update (notify_budget_limit, ...)
     * @param int $value Giá trị 0 hoặc 1
     */
    public function updateNotificationSetting($userId, $column, $value)
    {
        // 1. Whitelist: Chỉ cho phép update đúng 3 cột này để bảo mật (tránh user gửi tên cột linh tinh lên)
        $allowedColumns = [
            'notify_budget_limit', 
            'notify_goal_reminder', 
            'notify_weekly_summary'
        ];
        
        if (!in_array($column, $allowedColumns)) {
            return false; // Tên cột không hợp lệ -> Chặn ngay
        }

        // 2. Thực hiện Update
        // Lưu ý: Vì $column đã được kiểm tra trong whitelist ở trên nên việc nối chuỗi vào SQL là an toàn.
        $sql = "UPDATE users SET {$column} = ? WHERE id = ?";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$value, $userId]);
    }
}
