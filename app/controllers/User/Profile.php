<?php
namespace App\Controllers\User;

use App\Core\Controllers;
use App\Core\ApiResponse;
use App\Services\Validator;
use App\Middleware\CsrfProtection;
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
        
        $this->view('profile', [
            'user' => $user,
            'page' => 'profile'
        ]);
    }

    public function api_update() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            ApiResponse::methodNotAllowed();
        }

        try {
            // Verify CSRF token
            CsrfProtection::verify();
            
            $data = json_decode(file_get_contents('php://input'), true);
            
            // Validate data
            $validator = new Validator();
            if (!$validator->validateProfile($data)) {
                ApiResponse::validationError($validator->getErrors(), $validator->getFirstError());
            }

            $userModel = $this->model('User');
            $userId = $_SESSION['user_id'];
            
            // Get validated data
            $validData = $validator->getData();
            
            // Update profile
            $success = $userModel->updateProfile($userId, [
                'name' => $validData['name'],
                'email' => $validData['email']
            ]);

            if ($success) {
                $_SESSION['user_name'] = $validData['name'];
                $_SESSION['full_name'] = $validData['name'];
                ApiResponse::success('Cập nhật thành công', ['user' => $validData]);
            } else {
                ApiResponse::error('Không thể cập nhật thông tin');
            }
        } catch (Exception $e) {
            ApiResponse::serverError($e->getMessage());
        }
    }

    public function api_change_password() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            ApiResponse::methodNotAllowed();
        }

        try {
            // Verify CSRF token
            CsrfProtection::verify();
            
            $data = json_decode(file_get_contents('php://input'), true);
            
            // Validate data
            $validator = new Validator();
            if (!$validator->validatePasswordChange($data)) {
                ApiResponse::validationError($validator->getErrors(), $validator->getFirstError());
            }

            $userModel = $this->model('User');
            $userId = $_SESSION['user_id'];
            
            // Get validated data
            $validData = $validator->getData();
            
            // Verify current password
            $user = $userModel->getUserById($userId);
            if (!password_verify($validData['current_password'], $user['password'])) {
                ApiResponse::error('Mật khẩu hiện tại không đúng', null, 401);
            }
            
            // Update password
            $success = $userModel->updatePassword($userId, $validData['new_password']);

            if ($success) {
                ApiResponse::success('Đổi mật khẩu thành công');
            } else {
                ApiResponse::error('Không thể đổi mật khẩu');
            }
        } catch (Exception $e) {
            ApiResponse::serverError($e->getMessage());
        }
    }

    public function api_clear_data() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            ApiResponse::methodNotAllowed();
        }

        try {
            // Verify CSRF token
            CsrfProtection::verify();
            
            $userModel = $this->model('User');
            $transactionModel = $this->model('Transaction');
            $userId = $_SESSION['user_id'];
            
            // Delete all transactions for this user
            $success = $transactionModel->deleteAllByUser($userId);

            if ($success) {
                ApiResponse::success('Đã xóa tất cả dữ liệu');
            } else {
                ApiResponse::error('Không thể xóa dữ liệu');
            }
        } catch (Exception $e) {
            ApiResponse::serverError($e->getMessage());
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
