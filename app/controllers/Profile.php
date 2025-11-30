<?php
namespace App\Controllers;

use App\Core\Controllers;
use App\Models\User;
use App\Models\Transaction;
use Exception;

class Profile extends Controllers
{
    public function __construct()
    {
        parent::__construct();
        // Redirect to login if not logged in
        if (!$this->isLoggedIn()) {
            $this->redirect('/login_signup');
        }
    }

    public function index() {
        $userModel = $this->model('User');
        $user = $userModel->getUserById($_SESSION['user_id']);
        
        $this->view('profile/index', [
            'user' => $user,
            'page' => 'profile'
        ]);
    }

    public function api_update() {
        header('Content-Type: application/json');
        ob_clean();
        
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!$data) {
                throw new Exception('Invalid data');
            }

            $userModel = $this->model('User');
            $userId = $_SESSION['user_id'];
            
            // Update profile
            $success = $userModel->updateProfile($userId, [
                'name' => $data['name'] ?? '',
                'email' => $data['email'] ?? ''
            ]);

            if ($success) {
                $_SESSION['user_name'] = $data['name'];
                $_SESSION['full_name'] = $data['name'];
                echo json_encode(['success' => true, 'message' => 'Cập nhật thành công']);
            } else {
                throw new Exception('Update failed');
            }
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function api_change_password() {
        header('Content-Type: application/json');
        ob_clean();
        
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!$data || !isset($data['current_password']) || !isset($data['new_password'])) {
                throw new Exception('Missing required fields');
            }

            $userModel = $this->model('User');
            $userId = $_SESSION['user_id'];
            
            // Verify current password
            $user = $userModel->getUserById($userId);
            if (!password_verify($data['current_password'], $user['password'])) {
                throw new Exception('Mật khẩu hiện tại không đúng');
            }
            
            // Update password
            $success = $userModel->updatePassword($userId, $data['new_password']);

            if ($success) {
                echo json_encode(['success' => true, 'message' => 'Đổi mật khẩu thành công']);
            } else {
                throw new Exception('Update password failed');
            }
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function api_clear_data() {
        header('Content-Type: application/json');
        ob_clean();
        
        try {
            $userModel = $this->model('User');
            $transactionModel = $this->model('Transaction');
            $userId = $_SESSION['user_id'];
            
            // Delete all transactions for this user
            $success = $transactionModel->deleteAllByUser($userId);

            if ($success) {
                echo json_encode(['success' => true, 'message' => 'Đã xóa tất cả dữ liệu']);
            } else {
                throw new Exception('Delete failed');
            }
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function export_data() {
        try {
            $transactionModel = $this->model('Transaction');
            $userId = $_SESSION['user_id'];
            
            // Get all transactions for this user
            $transactions = $transactionModel->getTransactionsByUser($userId);
            
            // Set headers for CSV download
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="SmartSpending_Data_' . date('Y-m-d') . '.csv"');
            header('Pragma: no-cache');
            header('Expires: 0');
            
            // Create output stream
            $output = fopen('php://output', 'w');
            
            // Add BOM for Excel UTF-8 support
            fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // Add headers
            fputcsv($output, ['Ngày', 'Danh mục', 'Mô tả', 'Loại', 'Số tiền (VND)']);
            
            // Add data rows
            foreach ($transactions as $transaction) {
                $type = $transaction['amount'] > 0 ? 'Thu nhập' : 'Chi tiêu';
                $amount = abs($transaction['amount']);
                
                fputcsv($output, [
                    date('d/m/Y', strtotime($transaction['transaction_date'])),
                    $transaction['category_name'] ?? 'N/A',
                    $transaction['description'],
                    $type,
                    number_format($amount, 0, ',', '.')
                ]);
            }
            
            fclose($output);
            exit;
            
        } catch (Exception $e) {
            echo 'Lỗi: ' . $e->getMessage();
        }
    }
}
