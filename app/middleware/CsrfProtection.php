<?php

declare(strict_types=1);

namespace App\Middleware;

final class CsrfProtection
{
    private static string $sessionKey = 'csrf_token';
    private static int $tokenLifetime = 3600; // seconds

    public static function generateToken(): string
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $token = bin2hex(random_bytes(32));
        $_SESSION[self::$sessionKey] = [
            'token' => $token,
            'time' => time(),
        ];

        return $token;
    }

    public static function getToken(): string
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!empty($_SESSION[self::$sessionKey])) {
            $tokenData = $_SESSION[self::$sessionKey];
            if ((time() - ($tokenData['time'] ?? 0)) < self::$tokenLifetime && !empty($tokenData['token'])) {
                return (string) $tokenData['token'];
            }
        }

        return self::generateToken();
    }

    public static function validateToken(?string $token = null): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if ($token === null) {
            if (isset($_POST['csrf_token'])) {
                $token = (string) $_POST['csrf_token'];
            } elseif (isset($_SERVER['HTTP_X_CSRF_TOKEN'])) {
                $token = (string) $_SERVER['HTTP_X_CSRF_TOKEN'];
            } else {
                $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
                if ($contentType === 'application/json' || strpos($contentType, 'application/json') !== false) {
                    $json = json_decode(file_get_contents('php://input'), true);
                    if (isset($json['csrf_token'])) {
                        $token = (string) $json['csrf_token'];
                    }
                }
            }
        }

        if (empty($token) || empty($_SESSION[self::$sessionKey])) {
            return false;
        }

        $tokenData = $_SESSION[self::$sessionKey];
        if ((time() - ($tokenData['time'] ?? 0)) >= self::$tokenLifetime) {
            unset($_SESSION[self::$sessionKey]);
            return false;
        }

        return hash_equals((string) $tokenData['token'], (string) $token);
    }

    public static function verify(): void
    {
        if (!self::validateToken()) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode([
                'status' => 'error',
                'message' => 'CSRF token validation failed',
            ]);
            exit;
        }
    }

    public static function getTokenInput(): string
    {
        $token = self::getToken();
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
    }

    public static function getTokenMeta(): string
    {
        $token = self::getToken();
        return '<meta name="csrf-token" content="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
    }

    public static function refreshToken(): string
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        unset($_SESSION[self::$sessionKey]);
        return self::generateToken();
    }
}
