<?php
namespace App\Controllers\Admin;

use App\Core\Controllers;
use App\Models\User;

class Users extends Controllers
{
    protected $userModel;

    public function __construct()
    {
        parent::__construct();
        
        // Check admin permission
        if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
            http_response_code(403);
            die('Access Denied: Admin only');
        }
        
        $this->userModel = $this->model('User');
    }

    public function index()
    {
        $this->users();
    }

    /**
     * Quản lý người dùng
     */
    public function users()
    {
        $users = $this->userModel->getAllUsers();
        
        $this->view->set('title', 'Quản lý người dùng - Admin');
        $this->view->set('users', $users);
        $this->view->render('admin/users');
    }

    /**
     * API: Cập nhật trạng thái người dùng (active/inactive)
     */
    public function api_toggle_user_status()
    {
        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Invalid request method']);
            exit();
        }

        $data = json_decode(file_get_contents('php://input'), true);
        $userId = $data['user_id'] ?? 0;
        $isActive = $data['is_active'] ?? 1;

        // Không cho phép vô hiệu hóa chính mình
        if ($userId == $_SESSION['user_id']) {
            echo json_encode(['success' => false, 'message' => 'Không thể vô hiệu hóa tài khoản của chính bạn']);
            exit();
        }

        $result = $this->userModel->updateUserStatus($userId, $isActive);
        
        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Cập nhật trạng thái thành công']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Có lỗi xảy ra']);
        }
        exit();
    }

    /**
     * API: Cập nhật vai trò người dùng (user/admin)
     */
    public function api_update_user_role()
    {
        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Invalid request method']);
            exit();
        }

        $data = json_decode(file_get_contents('php://input'), true);
        $userId = $data['user_id'] ?? 0;
        $role = $data['role'] ?? 'user';

        // Không cho phép thay đổi role của user id = 1
        if ($userId == 1) {
            echo json_encode(['success' => false, 'message' => 'Không thể thay đổi quyền của tài khoản admin chính']);
            exit();
        }

        // Không cho phép tự thay đổi role của chính mình
        if ($userId == $_SESSION['user_id']) {
            echo json_encode(['success' => false, 'message' => 'Không thể thay đổi quyền của chính bạn']);
            exit();
        }

        if (!in_array($role, ['user', 'admin'])) {
            echo json_encode(['success' => false, 'message' => 'Vai trò không hợp lệ']);
            exit();
        }

        $result = $this->userModel->updateUserRole($userId, $role);
        
        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Cập nhật vai trò thành công']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Có lỗi xảy ra']);
        }
        exit();
    }
}
