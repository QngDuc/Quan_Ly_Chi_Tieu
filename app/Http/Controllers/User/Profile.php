<?php
namespace App\Http\Controllers\User;

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
    /**
     * API: Cập nhật cài đặt thông báo (Bước 3)
     * POST /profile/api_update_preference
     */
    public function api_update_preference() {
        // 1. Chỉ chấp nhận phương thức POST
        if ($this->request->method() !== 'POST') {
            Response::errorResponse('Method Not Allowed', null, 405);
            return;
        }

        try {
            // 2. Xác thực CSRF Token (Bắt buộc để chống tấn công giả mạo)
            CsrfProtection::verify();
            
            // 3. Lấy dữ liệu JSON từ client gửi lên
            $data = $this->request->json();
            
            // Lấy tên cột (key) và giá trị bật/tắt (value)
            $key = $data['key'] ?? '';
            // Chuyển đổi: nếu true -> 1, false -> 0 (để lưu vào MySQL tinyint)
            $value = (isset($data['value']) && $data['value'] === true) ? 1 : 0;

            // 4. Gọi Model để thực hiện update
            $userModel = $this->model('User');
            $userId = $this->getCurrentUserId();
            
            // Gọi hàm updateNotificationSetting bạn vừa viết ở Bước 2
            $success = $userModel->updateNotificationSetting($userId, $key, $value);

            // 5. Trả về kết quả cho Frontend
            if ($success) {
                Response::successResponse('Đã lưu cài đặt');
            } else {
                // Thất bại thường do tên key gửi lên không nằm trong danh sách cho phép (whitelist)
                Response::errorResponse('Cập nhật thất bại: Cài đặt không hợp lệ');
            }
        } catch (\Exception $e) {
            Response::errorResponse('Lỗi server: ' . $e->getMessage(), null, 500);
        }
    }
    
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
