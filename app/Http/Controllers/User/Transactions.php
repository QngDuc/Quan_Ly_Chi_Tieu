<?php
namespace App\Http\Controllers\User;

use App\Core\Controllers;
use App\Core\Response;
use App\Services\Validator;
use App\Middleware\CsrfProtection;
use App\Middleware\AuthCheck;

class Transactions extends Controllers
{
    private $transactionModel;
    private $categoryModel;

    public function __construct()
    {
        parent::__construct();
        // Kiểm tra quyền user
        AuthCheck::requireUser();
        $this->transactionModel = $this->model('Transaction');
        $this->categoryModel = $this->model('Category');
    }

    public function index($range = null, $categoryId = 'all', $page = 1)
    {
        $userId = $this->getCurrentUserId();

        // Default to current month if no range provided
        if (!$range) {
            $range = date('Y-m');
        }

        $filters = [
            'range' => $range,
            'category_id' => ($categoryId === 'all') ? null : $categoryId,
        ];
        
        // Pagination settings
        $perPage = 7;
        $offset = ($page - 1) * $perPage;
        
        $allTransactions = $this->transactionModel->getAllByUser($userId, $filters);
        $totalTransactions = count($allTransactions);
        $totalPages = ceil($totalTransactions / $perPage);
        
        // Get paginated transactions
        $transactions = array_slice($allTransactions, $offset, $perPage);
        $categories = $this->categoryModel->getAll();

        $data = [
            'title' => 'Tất cả Giao dịch',
            'transactions' => $transactions,
            'categories' => $categories,
            'current_range' => $range,
            'current_category' => $categoryId,
            'current_page' => $page,
            'total_pages' => $totalPages,
            'total_transactions' => $totalTransactions,
            'per_page' => $perPage
        ];

        $this->view('user/transactions', $data);
    }

    public function add()
    {
        if ($this->request->method() === 'POST') {
            $userId = $this->getCurrentUserId();
            
            // Sanitize and prepare data from the form
            $type = $this->request->post('type', 'expense');
            $amount = $this->request->post('amount', 0);
            $categoryId = $this->request->post('category_id', 0);
            $date = $this->request->post('date', date('Y-m-d'));
            $description = trim($this->request->post('description', ''));

            // Basic validation
            if ($amount > 0 && !empty($categoryId)) {
                $this->transactionModel->createTransaction(
                    $userId,
                    $categoryId,
                    $amount,
                    $type,
                    $date,
                    $description
                );
            }
        }

        // Redirect back to dashboard to see the result
        $this->redirect('/dashboard');
    }

    public function api_add()
    {
        if ($this->request->method() !== 'POST') {
            Response::errorResponse('Method Not Allowed', null, 405);
            return;
        }

        try {
            // Verify CSRF token
            CsrfProtection::verify();
            
            $userId = $this->getCurrentUserId();
            
            // Get JSON data
            $data = $this->request->json();
            
            // Validate data
            $validator = new Validator();
            if (!$validator->validateTransaction($data)) {
                // Return detailed validation errors
                Response::errorResponse($validator->getFirstError(), $validator->getErrors());
                return;
            }

            // Get validated data
            $validData = $validator->getData();

            // Create transaction
            $result = $this->transactionModel->createTransaction(
                $userId,
                $validData['category_id'],
                $validData['amount'],
                $validData['type'] ?? 'expense',
                $validData['date'],
                $validData['description']
            );

            if ($result) {
                Response::successResponse('Thêm giao dịch thành công');
            } else {
                Response::errorResponse('Không thể thêm giao dịch');
            }
        } catch (\Exception $e) {
            Response::errorResponse('Lỗi: ' . $e->getMessage(), null, 500);
        }
    }

    public function api_update($id)
    {
        if ($this->request->method() !== 'POST') {
            Response::errorResponse('Method Not Allowed', null, 405);
            return;
        }

        try {
            // Verify CSRF token
            CsrfProtection::verify();
            
            $userId = $this->getCurrentUserId();
            $data = $this->request->json();
            
            // Validate data
            $validator = new Validator();
            if (!$validator->validateTransaction($data)) {
                Response::errorResponse($validator->getFirstError(), $validator->getErrors());
                return;
            }

            // Get validated data
            $validData = $validator->getData();

            $result = $this->transactionModel->updateTransaction(
                $id, 
                $userId, 
                $validData['category_id'], 
                $validData['amount'], 
                $validData['type'] ?? 'expense', 
                $validData['date'], 
                $validData['description']
            );

            if ($result) {
                Response::successResponse('Cập nhật thành công', ['id' => $id]);
            } else {
                Response::errorResponse('Không thể cập nhật');
            }
        } catch (\Exception $e) {
            Response::errorResponse('Lỗi: ' . $e->getMessage(), null, 500);
        }
    }

    public function api_delete($id)
    {
        if ($this->request->method() !== 'POST') {
            Response::errorResponse('Method Not Allowed', null, 405);
            return;
        }

        try {
            // Verify CSRF token
            CsrfProtection::verify();
            
            $userId = $this->getCurrentUserId();
            $result = $this->transactionModel->deleteTransaction($id, $userId);

            if ($result) {
                Response::successResponse('Xóa giao dịch thành công', ['id' => $id]);
            } else {
                Response::errorResponse('Không thể xóa giao dịch');
            }
        } catch (\Exception $e) {
            Response::errorResponse('Lỗi: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * API endpoint to fetch transactions with filtering and pagination
     * GET /transactions/api_get_transactions?range=2025-01&category=all&page=1
     */
    public function api_get_transactions()
    {
        if ($this->request->method() !== 'GET') {
            Response::errorResponse('Method Not Allowed', null, 405);
            return;
        }

        try {
            $userId = $this->getCurrentUserId();
            
            // Get filters from query params
            $range = $this->request->get('range', date('Y-m'));
            $categoryId = $this->request->get('category', 'all');
            $page = (int)$this->request->get('page', 1);
            $perPage = (int)$this->request->get('per_page', 7);
            
            $filters = [
                'range' => $range,
                'category_id' => ($categoryId === 'all') ? null : $categoryId,
            ];
            
            // Get all matching transactions
            $allTransactions = $this->transactionModel->getAllByUser($userId, $filters);
            $totalTransactions = count($allTransactions);
            $totalPages = ceil($totalTransactions / $perPage);
            
            // Apply pagination
            $offset = ($page - 1) * $perPage;
            $transactions = array_slice($allTransactions, $offset, $perPage);
            
            // Format transactions for response
            $formattedTransactions = array_map(function($t) {
                return [
                    'id' => $t['id'],
                    'amount' => $t['amount'],
                    'description' => $t['description'],
                    'transaction_date' => $t['date'],
                    'category_id' => $t['category_id'],
                    'category_name' => $t['category_name'],
                    'type' => $t['amount'] >= 0 ? 'income' : 'expense',
                    'formatted_amount' => number_format(abs($t['amount']), 0, ',', '.') . ' ₫',
                    'formatted_date' => date('d M Y', strtotime($t['date']))
                ];
            }, $transactions);
            
            Response::successResponse('Lấy danh sách giao dịch thành công', [
                'transactions' => $formattedTransactions,
                'pagination' => [
                    'current_page' => $page,
                    'total_pages' => $totalPages,
                    'total_items' => $totalTransactions,
                    'per_page' => $perPage,
                    'has_next' => $page < $totalPages,
                    'has_prev' => $page > 1
                ],
                'filters' => [
                    'range' => $range,
                    'category' => $categoryId
                ]
            ]);
        } catch (\Exception $e) {
            Response::errorResponse('Lỗi: ' . $e->getMessage(), null, 500);
        }
    }
}
