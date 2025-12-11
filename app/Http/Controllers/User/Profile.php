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
    public function api_update_preference()
    {
        // 1. Chỉ chấp nhận phương thức POST
        if ($this->request->method() !== 'POST') {
            Response::errorResponse('Method Not Allowed', null, 405);
            return;
        }

        try {
            // 2. Xác thực CSRF Token
            CsrfProtection::verify();

            // 3. Lấy dữ liệu
            $data = $this->request->json();
            $key = $data['key'] ?? '';

            // === [SỬA ĐOẠN NÀY] ===
            // Chuyển đổi linh hoạt hơn: chấp nhận true, "true", 1, "1" là bật. Còn lại là tắt.
            // Cách cũ ($data['value'] === true) quá chặt, dễ fail.
            $rawValue = $data['value'] ?? 0;
            $value = filter_var($rawValue, FILTER_VALIDATE_BOOLEAN) ? 1 : 0;
            // ======================

            // DEBUG: Ghi log để xem server thực sự nhận được gì (Xem trong storage/logs hoặc error_log của xampp)
            error_log("API Update Pref - User: " . $this->getCurrentUserId() . " - Key: $key - RawValue: " . json_encode($rawValue) . " - DBValue: $value");

            // 4. Gọi Model
            $userModel = $this->model('User');
            $userId = $this->getCurrentUserId();

            // Gọi hàm update trong Model
            $success = $userModel->updateNotificationSetting($userId, $key, $value);

            if ($success) {
                // Trả về cả value đã lưu để frontend check nếu cần
                Response::successResponse('Đã lưu cài đặt', ['saved_value' => $value]);
            } else {
                // Thất bại có thể do Key không nằm trong Whitelist của Model
                Response::errorResponse('Cập nhật thất bại: Tên cài đặt không hợp lệ');
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

    public function index()
    {
        $userModel = $this->model('User');
        $user = $userModel->getUserById($this->getCurrentUserId());

        $this->view('user/profile', [
            'user' => $user,
            'page' => 'profile'
        ]);
    }

    public function api_update()
    {
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

    public function api_change_password()
    {
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

    public function api_clear_data()
    {
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

    public function export_data()
    {
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
            fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

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
