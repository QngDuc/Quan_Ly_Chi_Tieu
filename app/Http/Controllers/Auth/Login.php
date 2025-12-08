<?php
namespace App\Http\Controllers\Auth;

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
        if ($this->request->session('user_id')) {
            if (($this->request->session('role') ?? 'user') === 'admin') {
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
        $response = ['success' => false, 'message' => 'Yêu cầu không hợp lệ.', 'redirect_url' => ''];

        if ($this->request->method() === 'POST') {
            $data = $this->request->json();

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

        $this->response->json($response)->send();
    }

    public function api_login()
    {
        $response = ['success' => false, 'message' => 'Yêu cầu không hợp lệ.', 'redirect_url' => ''];

        if ($this->request->method() === 'POST') {
            $data = $this->request->json();

            $email = trim($data['email'] ?? '');
            $password = $data['password'] ?? '';

            if (empty($email) || empty($password)) {
                $response['message'] = 'Vui lòng nhập email và mật khẩu.';
            } else {
                $user = $this->userModel->authenticate($email, $password);

                if ($user) {
                    $this->request->setSession('user_id', $user['id']);
                    $this->request->setSession('username', $user['username']);
                    $this->request->setSession('full_name', $user['full_name']);
                    $this->request->setSession('role', $user['role'] ?? 'user');
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

        $this->response->json($response)->send();
    }

    public function logout()
    {
        $this->request->destroySession();
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
        $this->request->setSession('google_oauth_state', $state);
        // If called as popup, remember that so callback can respond with JS
        $isPopup = $this->request->get('popup');
        if ($isPopup) {
            $this->request->setSession('google_oauth_popup', true);
        } else {
            $this->request->unsetSession('google_oauth_popup');
        }
        
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
        $code = $this->request->get('code');
        $state = $this->request->get('state');
        $error = $this->request->get('error');

        // Check for errors
        if ($error) {
            $this->request->setSession('error', 'Đăng nhập Google bị hủy');
            if ($this->request->session('google_oauth_popup')) {
                $this->request->unsetSession('google_oauth_popup');
                $redirectTarget = BASE_URL . '/auth/login';
                echo "<!doctype html><html><head><meta charset=\"utf-8\"><title>Đã hủy</title></head><body><script>try{if(window.opener && !window.opener.closed){window.opener.location.href='" . $redirectTarget . "';window.close();}else{window.location.href='" . $redirectTarget . "';}}catch(e){window.location.href='" . $redirectTarget . "';}</script></body></html>";
                exit();
            }
            $this->redirect('/auth/login');
            return;
        }

        // Verify state
        $savedState = $this->request->session('google_oauth_state');
        if (!$state || !$savedState || $state !== $savedState) {
            $this->request->setSession('error', 'Invalid state parameter');
            if ($this->request->session('google_oauth_popup')) {
                $this->request->unsetSession('google_oauth_popup');
                $redirectTarget = BASE_URL . '/auth/login';
                echo "<!doctype html><html><head><meta charset=\"utf-8\"><title>Lỗi</title></head><body><script>try{if(window.opener && !window.opener.closed){window.opener.location.href='" . $redirectTarget . "';window.close();}else{window.location.href='" . $redirectTarget . "';}}catch(e){window.location.href='" . $redirectTarget . "';}</script></body></html>";
                exit();
            }
            $this->redirect('/auth/login');
            return;
        }

        $this->request->unsetSession('google_oauth_state');

        if (!$code) {
            $this->request->setSession('error', 'Không nhận được mã xác thực');
            if ($this->request->session('google_oauth_popup')) {
                $this->request->unsetSession('google_oauth_popup');
                $redirectTarget = BASE_URL . '/auth/login';
                echo "<!doctype html><html><head><meta charset=\"utf-8\"><title>Lỗi</title></head><body><script>try{if(window.opener && !window.opener.closed){window.opener.location.href='" . $redirectTarget . "';window.close();}else{window.location.href='" . $redirectTarget . "';}}catch(e){window.location.href='" . $redirectTarget . "';}</script></body></html>";
                exit();
            }
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
                $this->request->setSession('user_id', $user['id']);
                $this->request->setSession('user_email', $user['email']);
                $this->request->setSession('user_name', $user['full_name']);
                $this->request->setSession('role', $user['role'] ?? 'user');
                // If OAuth initiated from a popup, respond with JS to notify opener and close
                if ($this->request->session('google_oauth_popup')) {
                    $this->request->unsetSession('google_oauth_popup');
                    $redirectTarget = (($user['role'] ?? 'user') === 'admin') ? BASE_URL . '/admin/dashboard' : BASE_URL . '/dashboard';
                    echo "<!doctype html><html><head><meta charset=\"utf-8\"><title>Đăng nhập...</title></head><body><script>try{if(window.opener && !window.opener.closed){window.opener.location.href='" . $redirectTarget . "';window.close();}else{window.location.href='" . $redirectTarget . "';}}catch(e){window.location.href='" . $redirectTarget . "';}</script></body></html>";
                    exit();
                } else {
                    $this->redirect('/dashboard');
                }
            } else {
                // User doesn't exist, create new account
                $fullName = $userInfo['name'] ?? $userInfo['email'];
                $email = $userInfo['email'];
                $randomPassword = bin2hex(random_bytes(16));
                $username = explode('@', $email)[0]; // Use email prefix as username
                
                $newUserId = $this->userModel->createUser($username, $email, $randomPassword, $fullName);

                if ($newUserId) {
                    $this->request->setSession('user_id', $newUserId);
                    $this->request->setSession('user_email', $userInfo['email']);
                    $this->request->setSession('user_name', $userInfo['name'] ?? $userInfo['email']);
                    $this->request->setSession('role', 'user');

                    if ($this->request->session('google_oauth_popup')) {
                        $this->request->unsetSession('google_oauth_popup');
                        $redirectTarget = BASE_URL . '/dashboard';
                        echo "<!doctype html><html><head><meta charset=\"utf-8\"><title>Đăng nhập...</title></head><body><script>try{if(window.opener && !window.opener.closed){window.opener.location.href='" . $redirectTarget . "';window.close();}else{window.location.href='" . $redirectTarget . "';}}catch(e){window.location.href='" . $redirectTarget . "';}</script></body></html>";
                        exit();
                    } else {
                        $this->redirect('/dashboard');
                    }
                } else {
                    throw new \Exception('Không thể tạo tài khoản');
                }
            }
        } catch (\Exception $e) {
            $this->request->setSession('error', 'Lỗi: ' . $e->getMessage());
            if ($this->request->session('google_oauth_popup')) {
                $this->request->unsetSession('google_oauth_popup');
                $redirectTarget = BASE_URL . '/auth/login';
                echo "<!doctype html><html><head><meta charset=\"utf-8\"><title>Lỗi</title></head><body><script>try{if(window.opener && !window.opener.closed){window.opener.location.href='" . $redirectTarget . "';window.close();}else{window.location.href='" . $redirectTarget . "';}}catch(e){window.location.href='" . $redirectTarget . "';}</script></body></html>";
                exit();
            }
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
