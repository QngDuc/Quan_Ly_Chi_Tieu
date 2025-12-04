<?php
namespace App\Controllers\Auth;

use App\Core\Controllers;

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
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (isset($_SESSION['user_id'])) {
            if (($_SESSION['role'] ?? 'user') === 'admin') {
                $this->redirect('/admin/dashboard');
            } else {
                $this->redirect('/dashboard');
            }
            return;
        }
        $this->view->set('title', 'Đăng nhập & Đăng ký - Smart Spending');
        $this->view->render('auth/login');
    }

    public function api_register()
    {
        header('Content-Type: application/json');
        $response = ['success' => false, 'message' => 'Yêu cầu không hợp lệ.', 'redirect_url' => ''];

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            $data = json_decode(file_get_contents('php://input'), true);

            $fullName = trim($data['full_name'] ?? '');
            $email = trim($data['email'] ?? '');
            $password = $data['password'] ?? '';
            $confirmPassword = $data['confirm_password'] ?? '';

            if (empty($fullName) || empty($email) || empty($password) || empty($confirmPassword)) {
                $response['message'] = 'Vui lòng điền đầy đủ các trường bắt buộc.';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $response['message'] = 'Địa chỉ email không hợp lệ.';
            } elseif (strlen($password) < 8) {
                $response['message'] = 'Mật khẩu phải có ít nhất 8 ký tự.';
            } elseif ($password !== $confirmPassword) {
                $response['message'] = 'Mật khẩu xác nhận không khớp.';
            } else {
                $username = $email;

                if ($this->userModel->getUserByUsername($username)) {
                    $response['message'] = 'Tên người dùng này (email) đã tồn tại.';
                } elseif ($this->userModel->getUserByEmail($email)) {
                    $response['message'] = 'Địa chỉ email này đã được sử dụng.';
                } else {
                    $userId = $this->userModel->createUser($username, $email, $password, $fullName);
                    if ($userId) {
                        $response['success'] = true;
                        $response['message'] = 'Đăng ký thành công! Vui lòng đăng nhập.';
                        $response['switch_to_login'] = true;
                        $response['login_email'] = $email;
                        $response['login_password'] = $password;
                    } else {
                        $response['message'] = 'Đã có lỗi xảy ra trong quá trình đăng ký. Vui lòng thử lại.';
                    }
                }
            }
        }

        echo json_encode($response);
        exit();
    }

    public function api_login()
    {
        header('Content-Type: application/json');
        $response = ['success' => false, 'message' => 'Yêu cầu không hợp lệ.', 'redirect_url' => ''];

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            $data = json_decode(file_get_contents('php://input'), true);

            $email = trim($data['email'] ?? '');
            $password = $data['password'] ?? '';

            if (empty($email) || empty($password)) {
                $response['message'] = 'Vui lòng nhập email và mật khẩu.';
            } else {
                $user = $this->userModel->authenticate($email, $password);

                if ($user) {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['full_name'] = $user['full_name'];
                    $_SESSION['role'] = $user['role'] ?? 'user';
                    $response['success'] = true;
                    $response['message'] = 'Đăng nhập thành công!';
                    if (($user['role'] ?? 'user') === 'admin') {
                        $response['redirect_url'] = BASE_URL . '/admin/dashboard';
                    } else {
                        $response['redirect_url'] = BASE_URL . '/dashboard';
                    }
                } else {
                    $response['message'] = 'Email hoặc mật khẩu không chính xác.';
                }
            }
        }

        echo json_encode($response);
        exit();
    }

    public function logout()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        session_destroy();
        $this->redirect('/auth/login');
    }

    /**
     * Redirect to Google OAuth
     */
    public function google_login()
    {
        $clientId = GOOGLE_CLIENT_ID;
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'];
        $redirectUri = $protocol . '://' . $host . BASE_URL . '/auth/login/google_callback';
        $state = bin2hex(random_bytes(16));
        
        // Store state in session for verification
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['google_oauth_state'] = $state;
        
        $authUrl = "https://accounts.google.com/o/oauth2/v2/auth?" . http_build_query([
            'client_id' => $clientId,
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'scope' => 'email profile',
            'state' => $state,
            'access_type' => 'offline',
            'prompt' => 'consent'
        ]);
        
        header('Location: ' . $authUrl);
        exit();
    }

    /**
     * Handle Google OAuth callback
     */
    public function google_callback()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $code = $_GET['code'] ?? null;
        $state = $_GET['state'] ?? null;
        $error = $_GET['error'] ?? null;

        // Check for errors
        if ($error) {
            $_SESSION['error'] = 'Đăng nhập Google bị hủy';
            $this->redirect('/auth/login');
            return;
        }

        // Verify state
        if (!$state || !isset($_SESSION['google_oauth_state']) || $state !== $_SESSION['google_oauth_state']) {
            $_SESSION['error'] = 'Invalid state parameter';
            $this->redirect('/auth/login');
            return;
        }

        unset($_SESSION['google_oauth_state']);

        if (!$code) {
            $_SESSION['error'] = 'Không nhận được mã xác thực';
            $this->redirect('/auth/login');
            return;
        }

        try {
            // Exchange code for access token
            $tokenData = $this->getGoogleAccessToken($code);
            
            if (!$tokenData || !isset($tokenData['access_token'])) {
                throw new \Exception('Không thể lấy access token');
            }

            // Get user info from Google
            $userInfo = $this->getGoogleUserInfo($tokenData['access_token']);
            
            if (!$userInfo || !isset($userInfo['email'])) {
                throw new \Exception('Không thể lấy thông tin người dùng');
            }

            // Check if user exists
            $user = $this->userModel->getUserByEmail($userInfo['email']);

            if ($user) {
                // User exists, log them in
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_name'] = $user['full_name'];
                $_SESSION['role'] = $user['role'] ?? 'user';

                $this->redirect('/dashboard');
            } else {
                // User doesn't exist, create new account
                $fullName = $userInfo['name'] ?? $userInfo['email'];
                $email = $userInfo['email'];
                $randomPassword = bin2hex(random_bytes(16));
                $username = explode('@', $email)[0]; // Use email prefix as username
                
                $newUserId = $this->userModel->createUser($username, $email, $randomPassword, $fullName);

                if ($newUserId) {
                    $_SESSION['user_id'] = $newUserId;
                    $_SESSION['user_email'] = $userInfo['email'];
                    $_SESSION['user_name'] = $userInfo['name'] ?? $userInfo['email'];
                    $_SESSION['role'] = 'user';

                    $this->redirect('/dashboard');
                } else {
                    throw new \Exception('Không thể tạo tài khoản');
                }
            }
        } catch (\Exception $e) {
            $_SESSION['error'] = 'Lỗi: ' . $e->getMessage();
            $this->redirect('/auth/login');
        }
    }

    /**
     * Get Google access token
     */
    private function getGoogleAccessToken($code)
    {
        $tokenUrl = 'https://oauth2.googleapis.com/token';
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'];
        $redirectUri = $protocol . '://' . $host . BASE_URL . '/auth/login/google_callback';
        
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
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
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
     * Get Google user info
     */
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
}
