<?php
namespace App\Core;

/**
 * Session Manager - Abstraction layer for session handling
 * Makes testing easier by removing direct $_SESSION access
 */
class SessionManager
{
    /**
     * Start session if not already started
     */
    public function start()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * Get a session value
     */
    public function get($key, $default = null)
    {
        $this->start();
        return $_SESSION[$key] ?? $default;
    }

    /**
     * Set a session value
     */
    public function set($key, $value)
    {
        $this->start();
        $_SESSION[$key] = $value;
    }

    /**
     * Check if session key exists
     */
    public function has($key)
    {
        $this->start();
        return isset($_SESSION[$key]);
    }

    /**
     * Remove a session key
     */
    public function remove($key)
    {
        $this->start();
        unset($_SESSION[$key]);
    }

    /**
     * Get all session data
     */
    public function all()
    {
        $this->start();
        return $_SESSION;
    }

    /**
     * Destroy session
     */
    public function destroy()
    {
        $this->start();
        $_SESSION = [];
        session_destroy();
    }

    /**
     * Regenerate session ID (for security after login)
     */
    public function regenerate($deleteOldSession = true)
    {
        $this->start();
        session_regenerate_id($deleteOldSession);
    }

    /**
     * Flash a message (available only for next request)
     */
    public function flash($key, $value)
    {
        $this->set('_flash_' . $key, $value);
    }

    /**
     * Get and remove flash message
     */
    public function getFlash($key, $default = null)
    {
        $this->start();
        $flashKey = '_flash_' . $key;
        $value = $_SESSION[$flashKey] ?? $default;
        unset($_SESSION[$flashKey]);
        return $value;
    }

    /**
     * Check if user is logged in
     */
    public function isLoggedIn()
    {
        return $this->has('user_id');
    }

    /**
     * Get current user ID
     */
    public function getUserId()
    {
        return $this->get('user_id');
    }

    /**
     * Get current user role
     */
    public function getRole()
    {
        return $this->get('role', 'user');
    }

    /**
     * Check if current user is admin
     */
    public function isAdmin()
    {
        return $this->getRole() === 'admin';
    }

    /**
     * Set user session data after login
     */
    public function login($userData)
    {
        $this->regenerate();
        $this->set('user_id', $userData['id']);
        $this->set('username', $userData['username']);
        $this->set('email', $userData['email']);
        $this->set('full_name', $userData['full_name'] ?? '');
        $this->set('role', $userData['role'] ?? 'user');
        $this->set('last_login', time());
    }

    /**
     * Clear user session data on logout
     */
    public function logout()
    {
        $this->destroy();
    }
}
