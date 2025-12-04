<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Request;

final class CsrfProtection
{
    private static string $sessionKey = 'csrf_token';
    private static int $tokenLifetime = 3600; // seconds

    public static function generateToken(): string
    {
        $request = new Request();
        $token = bin2hex(random_bytes(32));
        $request->setSession(self::$sessionKey, [
            'token' => $token,
            'time' => time(),
        ]);

        return $token;
    }

    public static function getToken(): string
    {
        $request = new Request();
        $tokenData = $request->session(self::$sessionKey);

        if (!empty($tokenData)) {
            if ((time() - ($tokenData['time'] ?? 0)) < self::$tokenLifetime && !empty($tokenData['token'])) {
                return (string) $tokenData['token'];
            }
        }

        return self::generateToken();
    }

    public static function validateToken(?string $token = null): bool
    {
        $request = new Request();

        if ($token === null) {
            $token = $request->post('csrf_token');
            if (!$token) {
                // Check headers
                $token = $request->header('X-CSRF-TOKEN');
            }
            if (!$token) {
                // Check JSON body
                $token = $request->json('csrf_token');
            }
        }

        if (!$token) {
            return false;
        }

        $tokenData = $request->session(self::$sessionKey);
        if (empty($tokenData) || empty($tokenData['token'])) {
            return false;
        }

        if ((time() - ($tokenData['time'] ?? 0)) >= self::$tokenLifetime) {
            $request->unsetSession(self::$sessionKey);
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
        $request = new Request();
        $request->unsetSession(self::$sessionKey);
        return self::generateToken();
    }
}
