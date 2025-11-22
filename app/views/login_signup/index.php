<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SmartSpending - Quản Lý Chi Tiêu</title>
    <link rel="stylesheet" href="<?= BASE_URL; ?>/css/login_signup.css">
</head>

<body>
    <div class="page-bg">
        <div class="container" id="container">
            <div class="form-container sign-up-container">
                <form id="signUpForm">
                    <h1>Tạo tài khoản</h1>
                    <div class="social-container">
                        <a href="#" class="social"><i class="fab fa-google"></i></a>
                    </div>
                    <span>hoặc sử dụng email của bạn để đăng ký</span>
                    <div class="input-group">
                        <i class="fas fa-at icon"></i>
                        <input type="text" placeholder="Họ Tên" name="full_name" autocomplete="fullsername" required />
                    </div>
                    <div class="input-group">
                        <i class="fas fa-envelope icon"></i>
                        <input type="email" placeholder="Email" name="email" autocomplete="email" required />
                    </div>
                    <div class="input-group">
                        <i class="fas fa-lock icon"></i>
                        <input type="password" placeholder="Mật khẩu" name="password" id="password2" autocomplete="new-password" required />
                        <span class="toggle-password" onclick="togglePassword('password2')">
                            <i class="fas fa-eye"></i>
                        </span>
                    </div>
                    <div class="input-group">
                        <i class="fas fa-lock icon"></i>
                        <input type="password" placeholder="Xác nhận mật khẩu" name="confirm_password" id="confirmPassword" autocomplete="new-password" required />
                        <span class="toggle-password" onclick="togglePassword('confirmPassword')">
                            <i class="fas fa-eye"></i>
                        </span>
                    </div>
                    <button class="btn-submit" type="submit">ĐĂNG KÝ</button>
                </form>
            </div>

            <div class="form-container sign-in-container">
                <form id="signInForm">
                    <h1>Đăng nhập vào SmartSpending </h1>
                    <div class="social-container">
                        <a href="#" class="social"><i class="fab fa-google"></i></a>
                    </div>
                    <span>hoặc sử dụng tài khoản email của bạn</span>
                    <div class="input-group">
                        <i class="fas fa-envelope icon"></i>
                        <input type="email" placeholder="Email" name="email" autocomplete="email" required />
                    </div>
                    <div class="input-group">
                        <i class="fas fa-lock icon"></i>
                        <input type="password" placeholder="Mật khẩu" name="password" id="password" autocomplete="current-password" required />
                        <span class="toggle-password" onclick="togglePassword('password')">
                            <i class="fas fa-eye"></i>
                        </span>
                    </div>
                    <a href="#">Quên mật khẩu?</a>
                    <button class="btn-submit" type="submit">ĐĂNG NHẬP</button>
                </form>
            </div>

            <div class="overlay-container">
                <div class="overlay">
                    <div class="overlay-panel overlay-left">
                        <h1>Chào mừng trở lại!</h1>
                        <p>Để giữ kết nối với chúng tôi, vui lòng đăng nhập bằng thông tin cá nhân của bạn</p>
                        <button class="ghost" id="signIn">ĐĂNG NHẬP</button>
                    </div>
                    <div class="overlay-panel overlay-right">
                        <h1>Chào bạn!</h1>
                        <p>Nhập thông tin cá nhân của bạn và bắt đầu hành trình với chúng tôi</p>
                        <button class="ghost" id="signUp">ĐĂNG KÝ</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- FontAwesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <script>
        const signUpButton = document.getElementById('signUp');
        const signInButton = document.getElementById('signIn');
        const container = document.getElementById('container');

        signUpButton.addEventListener('click', () => {
            container.classList.add('right-panel-active');
        });

        signInButton.addEventListener('click', () => {
            container.classList.remove('right-panel-active');
        });

        function togglePassword(inputId) {
            const input = document.getElementById(inputId);
            if (input.type === 'password') {
                input.type = 'text';
            } else {
                input.type = 'password';
            }
        }

        // Handle Sign In Form
        document.getElementById('signInForm').addEventListener('submit', async (e) => {
            e.preventDefault();

            const formData = new FormData(document.getElementById('signInForm'));
            const data = {
                email: formData.get('email'),
                password: formData.get('password')
            };

            try {
                const response = await fetch('<?= BASE_URL; ?>/login_signup/api_login', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(data)
                });

                const result = await response.json();

                if (result.success) {
                    // Show success message
                    showAlert('Đăng nhập thành công!', 'success');
                    // Redirect after 1 second
                    setTimeout(() => {
                        window.location.href = result.redirect_url || '<?= BASE_URL; ?>/dashboard';
                    }, 1000);
                } else {
                    showAlert(result.message || 'Đăng nhập thất bại', 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                showAlert('Lỗi kết nối. Vui lòng thử lại.', 'error');
            }
        });

        // Handle Sign Up Form
        document.getElementById('signUpForm').addEventListener('submit', async (e) => {
            e.preventDefault();

            const formData = new FormData(document.getElementById('signUpForm'));
            const data = {
                full_name: formData.get('full_name'),
                username: formData.get('username'),
                email: formData.get('email'),
                password: formData.get('password'),
                confirm_password: formData.get('confirm_password')
            };

            // Validate passwords match
            if (data.password !== data.confirm_password) {
                showAlert('Mật khẩu xác nhận không khớp', 'error');
                return;
            }

            try {
                const response = await fetch('<?= BASE_URL; ?>/login_signup/api_register', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(data)
                });

                const result = await response.json();

                if (result.success) {
                    // Show success message
                    showAlert('Đăng ký thành công!', 'success');
                    // Redirect after 1 second
                    setTimeout(() => {
                        window.location.href = result.redirect_url || '<?= BASE_URL; ?>/dashboard';
                    }, 1000);
                } else {
                    showAlert(result.message || 'Đăng ký thất bại', 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                showAlert('Lỗi kết nối. Vui lòng thử lại.', 'error');
            }
        });

        // Show alert function
        function showAlert(message, type) {
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert ${type}`;
            alertDiv.innerHTML = `
                        <span>${message}</span>
                        <span class="close-btn" onclick="this.parentElement.style.display='none';">&times;</span>
                    `;
            document.body.appendChild(alertDiv);

            // Auto remove after 5 seconds
            setTimeout(() => {
                alertDiv.style.display = 'none';
            }, 5000);
        }
    </script>
</body>

</html>
