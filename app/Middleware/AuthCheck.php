<?php
namespace App\Middleware;

use App\Core\SessionManager;
use App\Core\Container;

/**
 * AuthCheck - Kiểm tra quyền truy cập
 * Refactored to use SessionManager instead of direct $_SESSION access
 */
class AuthCheck
{
    private static $session = null;

    /**
     * Get SessionManager instance
     */
    private static function getSession()
    {
        if (self::$session === null) {
            $container = Container::getInstance();
            if ($container->has(SessionManager::class)) {
                self::$session = $container->make(SessionManager::class);
            } else {
                self::$session = new SessionManager();
            }
        }
        return self::$session;
    }

    /**
     * Kiểm tra người dùng đã đăng nhập chưa
     */
    public static function requireLogin()
    {
        $session = self::getSession();
        if (!$session->isLoggedIn()) {
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
        $session = self::getSession();

        // Kiểm tra đã đăng nhập
        if (!$session->isLoggedIn()) {
            http_response_code(401);
            header('Location: ' . BASE_URL . '/auth/login');
            exit('Unauthorized: Please login first');
        }

        // Kiểm tra quyền admin
        if (!$session->isAdmin()) {
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
        $session = self::getSession();

        // Kiểm tra đã đăng nhập
        if (!$session->isLoggedIn()) {
            http_response_code(401);
            header('Location: ' . BASE_URL . '/auth/login');
            exit('Unauthorized: Please login first');
        }

        // Nếu là admin, chuyển về trang admin
        if ($session->isAdmin()) {
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
        $session = self::getSession();

        if ($session->isLoggedIn()) {
            // Redirect dựa trên role
            if ($session->isAdmin()) {
                header('Location: ' . BASE_URL . '/admin/dashboard');
            } else {
                header('Location: ' . BASE_URL . '/dashboard');
            }
            exit();
        }
    }
}
