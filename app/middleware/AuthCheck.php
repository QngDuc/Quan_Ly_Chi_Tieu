<?php
namespace App\Middleware;

/**
 * AuthCheck - Kiểm tra quyền truy cập
 */
class AuthCheck
{
    /**
     * Kiểm tra người dùng đã đăng nhập chưa
     */
    public static function requireLogin()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            header('Location: ' . BASE_URL . '/auth/login');
            exit('Unauthorized: Please login first');
        }
    }

    /**
     * Kiểm tra quyền admin
     */
    public static function requireAdmin()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Kiểm tra đã đăng nhập
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            header('Location: ' . BASE_URL . '/auth/login');
            exit('Unauthorized: Please login first');
        }

        // Kiểm tra quyền admin
        if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
            http_response_code(403);
            header('Location: ' . BASE_URL . '/dashboard');
            exit('Access Denied: Admin only');
        }
    }

    /**
     * Kiểm tra quyền user (ngăn admin truy cập trang user)
     */
    public static function requireUser()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Kiểm tra đã đăng nhập
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            header('Location: ' . BASE_URL . '/auth/login');
            exit('Unauthorized: Please login first');
        }

        // Nếu là admin, chuyển về trang admin
        if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
            http_response_code(403);
            header('Location: ' . BASE_URL . '/admin/dashboard');
            exit('Access Denied: User only');
        }
    }

    /**
     * Kiểm tra chỉ cho phép khách (chưa đăng nhập)
     */
    public static function requireGuest()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (isset($_SESSION['user_id'])) {
            // Redirect dựa trên role
            if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
                header('Location: ' . BASE_URL . '/admin/dashboard');
            } else {
                header('Location: ' . BASE_URL . '/dashboard');
            }
            exit();
        }
    }
}
