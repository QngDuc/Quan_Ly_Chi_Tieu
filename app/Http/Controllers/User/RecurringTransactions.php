<?php
namespace App\Http\Controllers\User;

use App\Core\Controllers;
use App\Core\Response;
use App\Middleware\AuthCheck;
use App\Middleware\CsrfProtection;

/**
 * Recurring Transactions Controller
 * Manages automatic recurring transactions (monthly salary, rent, subscriptions)
 */
class RecurringTransactions extends Controllers
{
    private $recurringModel;
    private $categoryModel;

    public function __construct()
    {
        parent::__construct();
        AuthCheck::requireUser();
        
        $this->recurringModel = $this->model('RecurringTransaction');
        $this->categoryModel = $this->model('Category');
    }

    /**
     * Display recurring transactions page
     */
    public function index()
    {
        $userId = $this->getCurrentUserId();
        $categories = $this->categoryModel->getByUser($userId);
        
        $this->view->render('user/recurring', [
            'categories' => $categories,
            'title' => 'Giao dịch Định kỳ'
        ]);
    }

    /**
     * API: Get all recurring transactions
     */
    public function api_get_all()
    {
        if ($this->request->method() !== 'GET') {
            Response::errorResponse('Method Not Allowed', null, 405);
            return;
        }

        try {
            $userId = $this->getCurrentUserId();
            $recurring = $this->recurringModel->getByUser($userId);
            
            Response::successResponse('Success', [
                'recurring_transactions' => $recurring
            ]);
        } catch (\Exception $e) {
            Response::errorResponse('Error: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * API: Create recurring transaction
     */
    public function api_create()
    {
        if ($this->request->method() !== 'POST') {
            Response::errorResponse('Method Not Allowed', null, 405);
            return;
        }

        CsrfProtection::verify();

        try {
            $userId = $this->getCurrentUserId();
            $data = $this->request->json();

            // Validation
            $errors = [];
            if (empty($data['category_id'])) {
                $errors['category_id'] = 'Vui lòng chọn danh mục';
            }
            if (empty($data['amount']) || !is_numeric($data['amount']) || $data['amount'] <= 0) {
                $errors['amount'] = 'Số tiền phải lớn hơn 0';
            }
            if (empty($data['frequency']) || !in_array($data['frequency'], ['daily', 'weekly', 'monthly', 'yearly'])) {
                $errors['frequency'] = 'Tần suất không hợp lệ';
            }
            if (empty($data['start_date'])) {
                $errors['start_date'] = 'Vui lòng chọn ngày bắt đầu';
            }

            if (!empty($errors)) {
                Response::errorResponse('Validation failed', $errors, 400);
                return;
            }

            // Calculate next occurrence
            $nextOccurrence = $this->calculateNextOccurrence($data['start_date'], $data['frequency']);

            $recurringData = [
                'user_id' => $userId,
                'category_id' => $data['category_id'],
                'amount' => $data['amount'],
                'type' => $data['type'] ?? 'expense',
                'description' => $data['description'] ?? null,
                'frequency' => $data['frequency'],
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'] ?? null,
                'next_occurrence' => $nextOccurrence,
                'is_active' => 1
            ];

            $id = $this->recurringModel->create($recurringData);
            
            Response::successResponse('Tạo thành công', ['id' => $id]);
        } catch (\Exception $e) {
            Response::errorResponse('Error: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * API: Update recurring transaction
     */
    public function api_update()
    {
        if ($this->request->method() !== 'POST') {
            Response::errorResponse('Method Not Allowed', null, 405);
            return;
        }

        CsrfProtection::verify();

        try {
            $userId = $this->getCurrentUserId();
            $data = $this->request->json();

            if (empty($data['id'])) {
                Response::errorResponse('ID is required', null, 400);
                return;
            }

            // Validation
            $errors = [];
            if (isset($data['amount']) && (!is_numeric($data['amount']) || $data['amount'] <= 0)) {
                $errors['amount'] = 'Số tiền phải lớn hơn 0';
            }
            if (isset($data['frequency']) && !in_array($data['frequency'], ['daily', 'weekly', 'monthly', 'yearly'])) {
                $errors['frequency'] = 'Tần suất không hợp lệ';
            }

            if (!empty($errors)) {
                Response::errorResponse('Validation failed', $errors, 400);
                return;
            }

            // Recalculate next occurrence if frequency changed
            if (isset($data['frequency']) || isset($data['start_date'])) {
                $existing = $this->recurringModel->getById($data['id'], $userId);
                if (!$existing) {
                    Response::errorResponse('Not found', null, 404);
                    return;
                }

                $startDate = $data['start_date'] ?? $existing['start_date'];
                $frequency = $data['frequency'] ?? $existing['frequency'];
                $data['next_occurrence'] = $this->calculateNextOccurrence($startDate, $frequency);
            }

            $updateData = array_filter($data, function($key) {
                return in_array($key, ['category_id', 'amount', 'type', 'description', 'frequency', 'start_date', 'end_date', 'next_occurrence', 'is_active']);
            }, ARRAY_FILTER_USE_KEY);

            $success = $this->recurringModel->update($data['id'], $userId, $updateData);
            
            if ($success) {
                Response::successResponse('Cập nhật thành công');
            } else {
                Response::errorResponse('Update failed', null, 400);
            }
        } catch (\Exception $e) {
            Response::errorResponse('Error: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * API: Delete recurring transaction
     */
    public function api_delete()
    {
        if ($this->request->method() !== 'POST') {
            Response::errorResponse('Method Not Allowed', null, 405);
            return;
        }

        CsrfProtection::verify();

        try {
            $userId = $this->getCurrentUserId();
            $data = $this->request->json();

            if (empty($data['id'])) {
                Response::errorResponse('ID is required', null, 400);
                return;
            }

            $success = $this->recurringModel->delete($data['id'], $userId);
            
            if ($success) {
                Response::successResponse('Xóa thành công');
            } else {
                Response::errorResponse('Delete failed', null, 400);
            }
        } catch (\Exception $e) {
            Response::errorResponse('Error: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * API: Toggle active status
     */
    public function api_toggle()
    {
        if ($this->request->method() !== 'POST') {
            Response::errorResponse('Method Not Allowed', null, 405);
            return;
        }

        CsrfProtection::verify();

        try {
            $userId = $this->getCurrentUserId();
            $data = $this->request->json();

            if (empty($data['id'])) {
                Response::errorResponse('ID is required', null, 400);
                return;
            }

            $existing = $this->recurringModel->getById($data['id'], $userId);
            if (!$existing) {
                Response::errorResponse('Not found', null, 404);
                return;
            }

            $newStatus = $existing['is_active'] ? 0 : 1;
            $success = $this->recurringModel->update($data['id'], $userId, ['is_active' => $newStatus]);
            
            if ($success) {
                Response::successResponse('Cập nhật thành công', ['is_active' => $newStatus]);
            } else {
                Response::errorResponse('Toggle failed', null, 400);
            }
        } catch (\Exception $e) {
            Response::errorResponse('Error: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Calculate next occurrence date
     */
    private function calculateNextOccurrence($startDate, $frequency)
    {
        $date = new \DateTime($startDate);
        $now = new \DateTime();

        // If start date is in the future, return it
        if ($date > $now) {
            return $date->format('Y-m-d');
        }

        // Otherwise calculate next occurrence from today
        switch ($frequency) {
            case 'daily':
                $date->modify('+1 day');
                break;
            case 'weekly':
                $date->modify('+1 week');
                break;
            case 'monthly':
                $date->modify('+1 month');
                break;
            case 'yearly':
                $date->modify('+1 year');
                break;
        }

        return $date->format('Y-m-d');
    }

    protected function getCurrentUserId()
    {
        return $this->session->get('user_id');
    }
}
