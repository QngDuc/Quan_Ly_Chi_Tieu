<?php
namespace App\Controllers\User;

use App\Core\Controllers;
use App\Core\Response;
use App\Services\Validator;
use App\Middleware\CsrfProtection;
use App\Middleware\AuthCheck;
use App\Models\User;
use App\Models\Transaction;
use Exception;

class Profile extends Controllers
{
    public function __construct()
    {
        parent::__construct();
        // Kiểm tra quyền user
        AuthCheck::requireUser();
    }

    public function index() {
        $userModel = $this->model('User');
        $user = $userModel->getUserById($this->getCurrentUserId());
        
        $this->view('user/profile', [
            'user' => $user,
            'page' => 'profile'
        ]);
    }

    public function api_update() {
        if ($this->request->method() !== 'POST') {
            Response::errorResponse('Method Not Allowed', null, 405);
            return;
        }

        try {
            // Verify CSRF token
            CsrfProtection::verify();
            
            $data = $this->request->json();
            
            // Validate data
            $validator = new Validator();
            if (!$validator->validateProfile($data)) {
                Response::errorResponse($validator->getFirstError(), $validator->getErrors());
                return;
            }

            $userModel = $this->model('User');
            $userId = $this->getCurrentUserId();
            
            // Get validated data
            $validData = $validator->getData();
            
            // Update profile
            $success = $userModel->updateProfile($userId, [
                'name' => $validData['name'],
                'email' => $validData['email']
            ]);

            if ($success) {
                $this->request->setSession('user_name', $validData['name']);
                $this->request->setSession('full_name', $validData['name']);
                Response::successResponse('Cập nhật thành công', ['user' => $validData]);
            } else {
                Response::errorResponse('Không thể cập nhật thông tin');
            }
        } catch (Exception $e) {
            Response::errorResponse('Lỗi: ' . $e->getMessage(), null, 500);
        }
    }

    public function api_change_password() {
        if ($this->request->method() !== 'POST') {
            Response::errorResponse('Method Not Allowed', null, 405);
            return;
        }

        try {
            // Verify CSRF token
            CsrfProtection::verify();
            
            $data = $this->request->json();
            
            // Validate data
            $validator = new Validator();
            if (!$validator->validatePasswordChange($data)) {
                Response::errorResponse($validator->getFirstError(), $validator->getErrors());
                return;
            }

            $userModel = $this->model('User');
            $userId = $this->getCurrentUserId();
            
            // Get validated data
            $validData = $validator->getData();
            
            // Verify current password
            $user = $userModel->getUserById($userId);
            if (!password_verify($validData['current_password'], $user['password'])) {
                Response::errorResponse('Mật khẩu hiện tại không đúng', null, 401);
                return;
            }
            
            // Update password
            $success = $userModel->updatePassword($userId, $validData['new_password']);

            if ($success) {
                Response::successResponse('Đổi mật khẩu thành công');
            } else {
                Response::errorResponse('Không thể đổi mật khẩu');
            }
        } catch (Exception $e) {
            Response::errorResponse('Lỗi: ' . $e->getMessage(), null, 500);
        }
    }

    public function api_clear_data() {
        if ($this->request->method() !== 'POST') {
            Response::errorResponse('Method Not Allowed', null, 405);
            return;
        }

        try {
            // Verify CSRF token
            CsrfProtection::verify();
            
            $userModel = $this->model('User');
            $transactionModel = $this->model('Transaction');
            $userId = $this->getCurrentUserId();
            
            // Delete all transactions for this user
            $success = $transactionModel->deleteAllByUser($userId);

            if ($success) {
                Response::successResponse('Đã xóa tất cả dữ liệu');
            } else {
                Response::errorResponse('Không thể xóa dữ liệu');
            }
        } catch (Exception $e) {
            Response::errorResponse('Lỗi: ' . $e->getMessage(), null, 500);
        }
    }

    public function export_data() {
        try {
            $transactionModel = $this->model('Transaction');
            $userId = $this->getCurrentUserId();
            
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
