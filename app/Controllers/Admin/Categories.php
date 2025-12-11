<?php
namespace App\Controllers\Admin;

use App\Core\Controllers;
use App\Core\Response;
use App\Middleware\AuthCheck;
use App\Middleware\CsrfProtection;

class Categories extends Controllers
{
    private $categoryModel;

    public function __construct()
    {
        parent::__construct();
        AuthCheck::requireAdmin();
        $this->categoryModel = $this->model('Category');
    }

    /**
     * Display categories management page
     */
    public function index()
    {
        // Get all default categories (user_id IS NULL)
        $categories = $this->categoryModel->getAll(null);
        
        $data = [
            'title' => 'Quản lý Danh mục Gốc',
            'categories' => $categories
        ];
        
        $this->view('admin/categories', $data);
    }

    /**
     * API: Get all default categories
     */
    public function api_get_categories()
    {
        if ($this->request->method() !== 'GET') {
            Response::errorResponse('Method Not Allowed', null, 405);
            return;
        }

        try {
            $categories = $this->categoryModel->getAll(null);
            Response::successResponse('Lấy danh sách danh mục thành công', ['categories' => $categories]);
        } catch (\Exception $e) {
            Response::errorResponse('Lỗi: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * API: Create new default category
     */
    public function api_create()
    {
        if ($this->request->method() !== 'POST') {
            Response::errorResponse('Method Not Allowed', null, 405);
            return;
        }

        CsrfProtection::verify();

        try {
            $data = $this->request->json();

            // Validation
            if (empty($data['name'])) {
                Response::errorResponse('Tên danh mục không được để trống', null, 400);
                return;
            }

            if (empty($data['type']) || !in_array($data['type'], ['income', 'expense'])) {
                Response::errorResponse('Loại danh mục không hợp lệ', null, 400);
                return;
            }

            // Check if name already exists in default categories
            $existing = $this->db->prepare("SELECT COUNT(*) as count FROM categories WHERE name = ? AND user_id IS NULL");
            $existing->execute([$data['name']]);
            if ($existing->fetch(\PDO::FETCH_ASSOC)['count'] > 0) {
                Response::errorResponse('Danh mục này đã tồn tại', null, 400);
                return;
            }

            // Create default category (userId = null)
            $categoryData = [
                'name' => $data['name'],
                'type' => $data['type'],
                'color' => $data['color'] ?? '#3498db',
                'icon' => $data['icon'] ?? 'fa-circle'
            ];

            $categoryId = $this->categoryModel->create(null, $categoryData);

            if ($categoryId) {
                Response::successResponse('Tạo danh mục thành công', ['category_id' => $categoryId]);
            } else {
                Response::errorResponse('Không thể tạo danh mục', null, 500);
            }
        } catch (\Exception $e) {
            Response::errorResponse('Lỗi: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * API: Update default category
     */
    public function api_update($id)
    {
        if ($this->request->method() !== 'POST') {
            Response::errorResponse('Method Not Allowed', null, 405);
            return;
        }

        CsrfProtection::verify();

        try {
            $data = $this->request->json();

            // Check if category exists
            $category = $this->categoryModel->getById($id, null);
            if (!$category) {
                Response::errorResponse('Không tìm thấy danh mục', null, 404);
                return;
            }

            // Validation
            if (empty($data['name'])) {
                Response::errorResponse('Tên danh mục không được để trống', null, 400);
                return;
            }

            if (empty($data['type']) || !in_array($data['type'], ['income', 'expense'])) {
                Response::errorResponse('Loại danh mục không hợp lệ', null, 400);
                return;
            }

            // Check if name already exists (excluding current category)
            $existing = $this->db->prepare("SELECT COUNT(*) as count FROM categories WHERE name = ? AND user_id IS NULL AND id != ?");
            $existing->execute([$data['name'], $id]);
            if ($existing->fetch(\PDO::FETCH_ASSOC)['count'] > 0) {
                Response::errorResponse('Tên danh mục này đã tồn tại', null, 400);
                return;
            }

            $categoryData = [
                'name' => $data['name'],
                'type' => $data['type'],
                'color' => $data['color'] ?? $category['color'],
                'icon' => $data['icon'] ?? $category['icon']
            ];

            $result = $this->categoryModel->update($id, null, $categoryData);

            if ($result) {
                Response::successResponse('Cập nhật danh mục thành công');
            } else {
                Response::errorResponse('Không thể cập nhật danh mục', null, 500);
            }
        } catch (\Exception $e) {
            Response::errorResponse('Lỗi: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * API: Delete default category
     * Database FK constraint handles validation automatically
     */
    public function api_delete($id)
    {
        if ($this->request->method() !== 'POST') {
            Response::errorResponse('Method Not Allowed', null, 405);
            return;
        }

        CsrfProtection::verify();

        try {
            // Check if category exists
            $category = $this->categoryModel->getById($id, null);
            if (!$category) {
                Response::errorResponse('Không tìm thấy danh mục', null, 404);
                return;
            }

            $result = $this->categoryModel->delete($id, null);

            // Result can be true (success), false (general failure), or string (error message)
            if ($result === true) {
                Response::successResponse('Xóa danh mục thành công');
            } elseif (is_string($result)) {
                Response::errorResponse($result, null, 400);
            } else {
                Response::errorResponse('Không thể xóa danh mục', null, 400);
            }
        } catch (\Exception $e) {
            Response::errorResponse('Lỗi: ' . $e->getMessage(), null, 500);
        }
    }
}
