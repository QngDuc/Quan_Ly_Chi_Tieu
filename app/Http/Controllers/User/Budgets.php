<?php
namespace App\Http\Controllers\User;

use App\Core\Controllers;
use App\Core\Response;
use App\Services\Validator;
use App\Middleware\CsrfProtection;
use App\Middleware\AuthCheck;

class Budgets extends Controllers
{
    protected $db;

    public function __construct()
    {
        parent::__construct();
        // Kiểm tra quyền user (ngăn admin truy cập)
        AuthCheck::requireUser();
        $this->db = (new \App\Core\ConnectDB())->getConnection();
    }

    /**
     * Display budgets index page with 50/30/20 Rule
     */
    public function index($period = null)
    {
        $data = [
            'title' => 'Quản lý Ngân sách - Quy tắc 50/30/20',
            'current_period' => $period
        ];
        $this->view('user/budgets', $data);
    }

    /**
     * API: Get all jar templates
     * GET /budgets/api_get_jars
     */
    public function api_get_jars()
    {
        if ($this->request->method() !== 'GET') {
            Response::errorResponse('Method Not Allowed', null, 405);
            return;
        }

        try {
            $userId = $this->getCurrentUserId();
            $jarModel = new \App\Models\JarTemplate();
            $jars = $jarModel->getByUser($userId);
            
            // Get categories for each jar
            foreach ($jars as &$jar) {
                $jar['categories'] = $jarModel->getCategories($jar['id']);
            }
            
            $totalPercentage = $jarModel->getTotalPercentage($userId);
            
            Response::successResponse('Lấy danh sách hũ thành công', [
                'jars' => $jars,
                'total_percentage' => $totalPercentage
            ]);
        } catch (\Exception $e) {
            Response::errorResponse('Lỗi: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * API: Create a new jar template
     * POST /budgets/api_create_jar
     */
    public function api_create_jar()
    {
        if ($this->request->method() !== 'POST') {
            Response::errorResponse('Method Not Allowed', null, 405);
            return;
        }

        CsrfProtection::verify();

        try {
            $userId = $this->getCurrentUserId();
            $input = $this->request->json();
            
            // Validate input
            if (empty($input['name']) || !isset($input['percentage'])) {
                Response::errorResponse('Vui lòng nhập tên hũ và phần trăm', null, 400);
                return;
            }

            $jarModel = new \App\Models\JarTemplate();
            
            // Check total percentage
            $currentTotal = $jarModel->getTotalPercentage($userId);
            $newTotal = $currentTotal + floatval($input['percentage']);
            
            if ($newTotal > 100) {
                Response::errorResponse('Tổng phần trăm không được vượt quá 100%', null, 400);
                return;
            }

            $data = [
                'user_id' => $userId,
                'name' => $input['name'],
                'percentage' => floatval($input['percentage']),
                'color' => $input['color'] ?? '#6c757d',
                'icon' => $input['icon'] ?? null,
                'description' => $input['description'] ?? null,
                'order_index' => $input['order_index'] ?? 0
            ];

            $jarId = $jarModel->create($data);
            
            if ($jarId) {
                // Add categories if provided
                if (!empty($input['categories']) && is_array($input['categories'])) {
                    foreach ($input['categories'] as $index => $categoryName) {
                        $jarModel->addCategory($jarId, $categoryName, $index);
                    }
                }
                
                Response::successResponse('Tạo hũ thành công', ['jar_id' => $jarId]);
            } else {
                Response::errorResponse('Không thể tạo hũ');
            }
        } catch (\Exception $e) {
            Response::errorResponse('Lỗi: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * API: Update a jar template
     * POST /budgets/api_update_jar/{id}
     */
    public function api_update_jar($id)
    {
        if ($this->request->method() !== 'POST') {
            Response::errorResponse('Method Not Allowed', null, 405);
            return;
        }

        CsrfProtection::verify();

        try {
            $userId = $this->getCurrentUserId();
            $input = $this->request->json();
            
            $jarModel = new \App\Models\JarTemplate();
            
            // Check if jar exists
            $jar = $jarModel->getById($id, $userId);
            if (!$jar) {
                Response::errorResponse('Không tìm thấy hũ', null, 404);
                return;
            }

            // Validate percentage
            $currentTotal = $jarModel->getTotalPercentage($userId);
            $newTotal = $currentTotal - floatval($jar['percentage']) + floatval($input['percentage']);
            
            if ($newTotal > 100) {
                Response::errorResponse('Tổng phần trăm không được vượt quá 100%', null, 400);
                return;
            }

            $data = [
                'name' => $input['name'],
                'percentage' => floatval($input['percentage']),
                'color' => $input['color'] ?? $jar['color'],
                'icon' => $input['icon'] ?? $jar['icon'],
                'description' => $input['description'] ?? $jar['description'],
                'order_index' => $input['order_index'] ?? $jar['order_index']
            ];

            $result = $jarModel->update($id, $userId, $data);
            
            if ($result) {
                Response::successResponse('Cập nhật hũ thành công');
            } else {
                Response::errorResponse('Không thể cập nhật hũ');
            }
        } catch (\Exception $e) {
            Response::errorResponse('Lỗi: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * API: Delete a jar template
     * POST /budgets/api_delete_jar/{id}
     */
    public function api_delete_jar($id)
    {
        if ($this->request->method() !== 'POST') {
            Response::errorResponse('Method Not Allowed', null, 405);
            return;
        }

        CsrfProtection::verify();

        try {
            $userId = $this->getCurrentUserId();
            $jarModel = new \App\Models\JarTemplate();
            
            $result = $jarModel->delete($id, $userId);
            
            if ($result) {
                Response::successResponse('Xóa hũ thành công');
            } else {
                Response::errorResponse('Không thể xóa hũ');
            }
        } catch (\Exception $e) {
            Response::errorResponse('Lỗi: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * API: Create default 50/30/20 jars
     * POST /budgets/api_create_default_503020
     */
    public function api_create_default_503020()
    {
        if ($this->request->method() !== 'POST') {
            Response::errorResponse('Method Not Allowed', null, 405);
            return;
        }

        CsrfProtection::verify();

        try {
            $userId = $this->getCurrentUserId();
            $jarModel = new \App\Models\JarTemplate();
            
            // Check if user already has jars
            $existingJars = $jarModel->getByUser($userId);
            if (!empty($existingJars)) {
                Response::errorResponse('Bạn đã có các hũ ngân sách. Vui lòng xóa hết trước khi tạo mặc định.', null, 400);
                return;
            }

            $defaults = [
                [
                    'name' => 'Nhu cầu thiết yếu',
                    'percentage' => 50,
                    'color' => '#dc3545', // Danger/Red
                    'description' => 'Chi phí sinh hoạt, ăn uống, đi lại...',
                    'categories' => ['Ăn uống', 'Đi lại', 'Hóa đơn', 'Thuê nhà']
                ],
                [
                    'name' => 'Mong muốn',
                    'percentage' => 30,
                    'color' => '#ffc107', // Warning/Yellow
                    'description' => 'Mua sắm, giải trí, du lịch...',
                    'categories' => ['Mua sắm', 'Giải trí', 'Du lịch']
                ],
                [
                    'name' => 'Tiết kiệm & Đầu tư',
                    'percentage' => 20,
                    'color' => '#28a745', // Success/Green
                    'description' => 'Tiết kiệm dài hạn, đầu tư...',
                    'categories' => ['Tiết kiệm', 'Đầu tư']
                ]
            ];

            foreach ($defaults as $index => $jar) {
                $data = [
                    'user_id' => $userId,
                    'name' => $jar['name'],
                    'percentage' => $jar['percentage'],
                    'color' => $jar['color'],
                    'description' => $jar['description'],
                    'order_index' => $index
                ];
                
                $jarId = $jarModel->create($data);
                
                if ($jarId && !empty($jar['categories'])) {
                    foreach ($jar['categories'] as $catIndex => $catName) {
                        $jarModel->addCategory($jarId, $catName, $catIndex);
                    }
                }
            }
            
            Response::successResponse('Đã tạo bộ hũ 50/30/20 thành công');
            
        } catch (\Exception $e) {
            Response::errorResponse('Lỗi: ' . $e->getMessage(), null, 500);
        }
    }


}
