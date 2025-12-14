<?php

namespace App\Controllers\Auth;

use App\Core\Controllers;
use App\Core\Response;
use App\Core\ConnectDB;

class Login extends Controllers
{
    protected $userModel;

    public function __construct()
    {
        parent::__construct();
        $this->userModel = $this->model('User');
    }

    public function index()
    {
        // Nếu đã login thì chuyển hướng
        if ($this->request->session('user_id')) {
            if (($this->request->session('role') ?? 'user') === 'admin') {
                $this->redirect('/admin/dashboard');
            } else {
                $this->redirect('/dashboard');
            }
            return;
        }

        $data = ['title' => 'Đăng nhập & Đăng ký - Smart Spending'];
        $this->view('auth/login', $data);
    }

    /**
     * Hàm hỗ trợ: Khởi tạo dữ liệu mặc định cho User mới (Ví + Cài đặt)
     * Dùng chung cho cả Register và Google Login
     */
    private function initNewUserData($userId)
    {
        try {
            // A. Khởi tạo Cài đặt tỷ lệ 6 hũ (Budget Settings)
            $budgetModel = $this->model('Budget');
            if ($budgetModel) {
                $budgetModel->initUserSmartSettings($userId);
            }

            // B. Khởi tạo 6 Ví thực tế (User Wallets)
            $db = (new ConnectDB())->getConnection();
            $jars = ['nec', 'ffa', 'ltss', 'edu', 'play', 'give'];
            
            $sql = "INSERT IGNORE INTO user_wallets (user_id, jar_code, balance) VALUES (?, ?, 0)";
            $stmt = $db->prepare($sql);
            
            foreach ($jars as $jarCode) {
                $stmt->execute([$userId, $jarCode]);
            }

            return true;
        } catch (\Exception $e) {
            // Log lỗi nhưng không chặn flow chính
            error_log("Init data error for user $userId: " . $e->getMessage());
            return false;
        }
    }

    /**
     * API: Đăng ký tài khoản mới
     */
    public function api_register()
    {
        if ($this->request->method() !== 'POST') {
            Response::errorResponse('Method Not Allowed', null, 405);
            return;
        }

        try {
            $data = $this->request->json();

            $fullName = trim($data['full_name'] ?? '');
            $email = trim($data['email'] ?? '');
            $password = $data['password'] ?? '';
            $confirmPassword = $data['confirm_password'] ?? '';

            // 1. Validate dữ liệu
            $errors = [];
            if (empty($fullName)) $errors[] = 'Họ tên không được để trống.';
            if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Email không hợp lệ.';
            if (empty($password) || strlen($password) < 6) $errors[] = 'Mật khẩu phải từ 6 ký tự.';
            if ($password !== $confirmPassword) $errors[] = 'Mật khẩu nhập lại không khớp.';

            if ($this->userModel->getUserByEmail($email)) {
                $errors[] = 'Email này đã được sử dụng.';
            }

            if (!empty($errors)) {
                Response::errorResponse('Đăng ký thất bại', ['errors' => $errors], 400);
                return;
            }

            // 2. Tạo User mới
            $userId = $this->userModel->createUser($email, $email, $password, $fullName);

            if ($userId) {
                // 3. Gọi hàm khởi tạo dữ liệu (Code sạch hơn nhiều)
                $this->initNewUserData($userId);

                Response::successResponse('Đăng ký tài khoản thành công!', [
                    'redirect_url' => '/login'
                ]);
            } else {
                Response::errorResponse('Có lỗi xảy ra khi tạo tài khoản.');
            }

        } catch (\Exception $e) {
            Response::errorResponse('Lỗi hệ thống: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * API: Đăng nhập thường
     */
    public function api_login()
    {
        if ($this->request->method() !== 'POST') {
            Response::errorResponse('Method Not Allowed', null, 405);
            return;
        }

        try {
            $data = $this->request->json();
            $email = trim($data['email'] ?? '');
            $password = $data['password'] ?? '';

            if (empty($email) || empty($password)) {
                Response::errorResponse('Vui lòng nhập email và mật khẩu.', null, 400);
                return;
            }

            $user = $this->userModel->getUserByEmail($email);

            if ($user && password_verify($password, $user['password'])) {
                if (isset($user['is_active']) && $user['is_active'] == 0) {
                     Response::errorResponse('Tài khoản đã bị khóa.');
                     return;
                }

                // Lưu session
                $this->setLoginSession($user);

                $redirectUrl = ($user['role'] === 'admin') ? '/admin/dashboard' : '/dashboard';

                Response::successResponse('Đăng nhập thành công!', [
                    'redirect_url' => $redirectUrl,
                    'user' => [
                        'id' => $user['id'],
                        'full_name' => $user['full_name'],
                        'role' => $user['role']
                    ]
                ]);
            } else {
                Response::errorResponse('Email hoặc mật khẩu không đúng.', null, 401);
            }
        } catch (\Exception $e) {
            Response::errorResponse('Lỗi hệ thống: ' . $e->getMessage(), null, 500);
        }
    }

    public function logout()
    {
        session_unset();
        session_destroy();
        $this->redirect('/login');
    }

    // =========================================================================
    // GOOGLE LOGIN & OAUTH 2.0
    // =========================================================================

    /**
     * Bước 1: Chuyển hướng người dùng sang Google
     */
    public function google_redirect()
    {
        if (!defined('GOOGLE_CLIENT_ID')) {
            $this->redirect('/login?error=' . urlencode('Google OAuth chưa được cấu hình.'));
            return;
        }

        $authUrl = 'https://accounts.google.com/o/oauth2/v2/auth';
        
        // [QUAN TRỌNG] Redirect URI phải là URL tuyệt đối (scheme + host + path)
        // Prefer `APP_URL` from env; nếu không có, dựng URL từ request
            if (defined('APP_URL') && !empty(constant('APP_URL'))) {
                $baseRedirect = rtrim(constant('APP_URL'), '/');
        } else {
            $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
            $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
            // try to derive base path (keep /Quan_Ly_Chi_Tieu/public if present)
            $script = $_SERVER['SCRIPT_NAME'] ?? '';
            $basePath = str_replace('/index.php', '', $script);
            $baseRedirect = rtrim($scheme . $host . $basePath, '/');
        }

        $redirectUri = $baseRedirect . '/auth/login/google_callback';

        $params = [
            'client_id' => GOOGLE_CLIENT_ID,
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'scope' => 'openid email profile',
            'access_type' => 'online',
            'prompt' => 'select_account'
        ];

        if (isset($_GET['popup']) && $_GET['popup'] == '1') {
            $params['state'] = 'popup=1';
        }

        $this->response->redirect($authUrl . '?' . http_build_query($params));
    }

    /**
     * Bước 2: Xử lý Callback từ Google
     */
    public function google_login()
    {
        $code = $_GET['code'] ?? null;
        if (!$code) {
            $this->redirect('/login');
            return;
        }

        try {
            // 1. Get Access Token
            $tokenData = $this->getAccessToken($code);
            if (!$tokenData || !isset($tokenData['access_token'])) {
                 // Debug: Nếu lỗi thì in ra để xem (bỏ comment nếu cần thiết)
                 // var_dump($tokenData); die(); 
                 throw new \Exception('Không thể lấy Access Token từ Google.');
            }

            // 2. Get User Info
            $googleUser = $this->getGoogleUserInfo($tokenData['access_token']);
            if (!$googleUser || !isset($googleUser['email'])) {
                throw new \Exception('Không thể lấy thông tin người dùng từ Google.');
            }

            $email = $googleUser['email'];
            $name = $googleUser['name'] ?? 'Google User';
            $avatar = $googleUser['picture'] ?? '';
            
            // 3. Check or Create User
            $user = $this->userModel->getUserByEmail($email);
            
            if (!$user) {
                // Auto register
                $randomPass = bin2hex(random_bytes(8)); 
                $userId = $this->userModel->createUser($email, $email, $randomPass, $name);
                
                if ($userId) {
                    $this->userModel->updateAvatar($userId, $avatar);
                    
                    // Gọi hàm khởi tạo dữ liệu ví (đã tách ra)
                    $this->initNewUserData($userId);
                    
                    $user = $this->userModel->getUserById($userId);
                } else {
                    throw new \Exception('Lỗi tạo tài khoản Google.');
                }
            }

            // 4. Set Session
            $this->setLoginSession($user);

            // 5. Điều hướng (Hỗ trợ Popup)
            $path = ($user['role'] === 'admin') ? '/admin/dashboard' : '/dashboard';
            $state = $_GET['state'] ?? null;

            if ($state && strpos($state, 'popup=1') !== false) {
                // Build absolute base URL (prefer APP_URL, otherwise derive from request)
                    if (defined('APP_URL') && !empty(constant('APP_URL'))) {
                        $baseRedirect = rtrim(constant('APP_URL'), '/');
                } else {
                    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
                    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
                    $script = $_SERVER['SCRIPT_NAME'] ?? '';
                    $basePath = str_replace('/index.php', '', $script);
                    $baseRedirect = rtrim($scheme . $host . $basePath, '/');
                }

                $absoluteRedirect = $baseRedirect . $path;

                echo '<!doctype html><html><head><meta charset="utf-8"><title>Đang xử lý...</title></head><body>';
                echo "<script>if(window.opener){window.opener.location='" . addslashes($absoluteRedirect) . "'; window.close();} else {window.location='" . addslashes($path) . "';}</script>";
                echo '</body></html>';
                return;
            }

            // Non-popup flow: use relative path (Controllers::redirect will prefix BASE_URL)
            $this->redirect($path);

        } catch (\Exception $e) {
            $this->redirect('/login?error=' . urlencode($e->getMessage()));
        }
    }

    public function google_callback()
    {
        return $this->google_login();
    }

    /**
     * Helper: Lấy Token từ Google (Đã FIX lỗi URI và cURL)
     */
    private function getAccessToken($code)
    {
        if (!defined('GOOGLE_CLIENT_ID')) return null;

        $tokenUrl = 'https://oauth2.googleapis.com/token';
        
        // Ensure redirect URI is absolute and matches the one sent to Google
            if (defined('APP_URL') && !empty(constant('APP_URL'))) {
                $baseRedirect = rtrim(constant('APP_URL'), '/');
        } else {
            $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
            $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
            $script = $_SERVER['SCRIPT_NAME'] ?? '';
            $basePath = str_replace('/index.php', '', $script);
            $baseRedirect = rtrim($scheme . $host . $basePath, '/');
        }

        $redirectUri = $baseRedirect . '/auth/login/google_callback'; 

        $params = [
            'code' => $code,
            'client_id' => GOOGLE_CLIENT_ID,
            'client_secret' => GOOGLE_CLIENT_SECRET,
            'redirect_uri' => $redirectUri,
            'grant_type' => 'authorization_code'
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $tokenUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        
        // [FIX] Dùng http_build_query để gửi đúng định dạng application/x-www-form-urlencoded
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params)); 
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Localhost only
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        // Không dùng curl_close($ch) nữa vì PHP 8+ tự xử lý

        if ($response === false) {
             // Log error curl
             error_log('Curl error: ' . curl_error($ch));
             return null;
        }

        if ($httpCode !== 200) {
            error_log('Google Token Error Code: ' . $httpCode . ' Response: ' . $response);
            return null;
        }

        return json_decode($response, true);
    }

    private function getGoogleUserInfo($accessToken)
    {
        $userInfoUrl = 'https://www.googleapis.com/oauth2/v2/userinfo';
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $userInfoUrl);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $accessToken]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($httpCode !== 200) {
            return null;
        }

        return json_decode($response, true);
    }

    /**
     * Helper: Set Session tập trung
     */
    private function setLoginSession($user) {
        $this->request->setSession('user_id', $user['id']);
        $this->request->setSession('email', $user['email']);
        $this->request->setSession('full_name', $user['full_name']);
        $this->request->setSession('role', $user['role']);
        $this->request->setSession('avatar', $user['avatar'] ?? 'default_avatar.png');
    }
}