<?php
namespace App\Controllers\Admin;

use App\Core\Controllers;
use App\Models\User;
use App\Models\Transaction;
use App\Models\Category;
use App\Middleware\AuthCheck;

class Dashboard extends Controllers
{
    protected $userModel;
    protected $transactionModel;
    protected $categoryModel;

    public function __construct()
    {
        parent::__construct();
        
        // Kiểm tra quyền admin
        AuthCheck::requireAdmin();
        
        $this->userModel = $this->model('User');
        $this->transactionModel = $this->model('Transaction');
        $this->categoryModel = $this->model('Category');
    }

    public function index()
    {
        // Get system statistics
        $stats = [
            'total_users' => $this->getTotalUsers(),
            'active_users' => $this->getActiveUsers(),
            'total_transactions' => $this->getTotalTransactions(),
            'total_categories' => $this->getTotalCategories(),
            'recent_users' => $this->getRecentUsers(5),
            'system_activity' => $this->getSystemActivity()
        ];

        $data = [
            'title' => 'Admin Dashboard - Quản lý hệ thống',
            'stats' => $stats
        ];

        $this->view('admin/dashboard', $data);
    }

    private function getTotalUsers()
    {
        $users = $this->userModel->getAllUsers();
        return count($users);
    }

    private function getActiveUsers()
    {
        $users = $this->userModel->getAllUsers();
        return count(array_filter($users, function($user) {
            return $user['is_active'] == 1;
        }));
    }

    private function getTotalTransactions()
    {
        $db = (new \App\Core\ConnectDB())->getConnection();
        $stmt = $db->query("SELECT COUNT(*) as total FROM transactions");
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $result['total'];
    }

    private function getTotalCategories()
    {
        $db = (new \App\Core\ConnectDB())->getConnection();
        $stmt = $db->query("SELECT COUNT(*) as total FROM categories WHERE is_default = 1");
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $result['total'];
    }

    private function getRecentUsers($limit = 5)
    {
        $db = (new \App\Core\ConnectDB())->getConnection();
        $stmt = $db->prepare("SELECT id, username, email, full_name, role, created_at FROM users ORDER BY created_at DESC LIMIT :limit");
        $stmt->bindValue(':limit', (int)$limit, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    private function getSystemActivity()
    {
        $db = (new \App\Core\ConnectDB())->getConnection();
        
        // Get transactions stats for last 30 days
        $stmt = $db->query("
            SELECT 
                DATE(date) as activity_date,
                COUNT(*) as transaction_count,
                SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END) as total_income,
                SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END) as total_expense
            FROM transactions
            WHERE date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
            GROUP BY DATE(date)
            ORDER BY DATE(date) DESC
            LIMIT 7
        ");
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}
