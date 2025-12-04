<!DOCTYPE html>
<html lang="vi">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>SmartSpending - Quản Lý Chi Tiêu</title>
	<link rel="icon" type="image/x-icon" href="<?= BASE_URL; ?>/favicon.ico">
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
	<link rel="stylesheet" href="<?= BASE_URL; ?>/login_signup/login_signup.css">
</head>

<body>
	<div class="page-bg">
		<div class="container" id="container">
			<div class="form-container sign-up-container">
				<form id="signUpForm">
					<h1>Tạo Tài Khoản</h1>
					<p class="form-subtitle">Tham gia SmartSpending ngay hôm nay</p>
					<button type="button" class="btn-google" onclick="googleSignUp()">
						<svg width="18" height="18" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48">
							<path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"/>
							<path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"/>
							<path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z"/>
							<path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.15 1.45-4.92 2.3-8.16 2.3-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"/>
							<path fill="none" d="M0 0h48v48H0z"/>
						</svg>
						<span>Đăng ký với Google</span>
					</button>
					<div class="divider">
						<span>hoặc</span>
					</div>
					<div class="input-group">
						<i class="fas fa-at icon"></i>
						<input type="text" placeholder="Họ Tên" name="full_name"/>
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
					<h1>Đăng Nhập</h1>
					<p class="form-subtitle">Chào mừng trở lại SmartSpending</p>
					<button type="button" class="btn-google" onclick="googleSignIn()">
						<svg width="18" height="18" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48">
							<path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"/>
							<path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"/>
							<path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z"/>
							<path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.15 1.45-4.92 2.3-8.16 2.3-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"/>
							<path fill="none" d="M0 0h48v48H0z"/>
						</svg>
						<span>Đăng nhập với Google</span>
					</button>
					<div class="divider">
						<span>hoặc</span>
					</div>
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
						<div class="overlay-content">
							<div class="logo-circle">
								<i class="fas fa-wallet"></i>
							</div>
							<h1>Chào mừng trở lại!</h1>
							<p>Tiếp tục quản lý chi tiêu thông minh của bạn</p>
							<button class="ghost" id="signIn">ĐĂNG NHẬP</button>
						</div>
					</div>
					<div class="overlay-panel overlay-right">
						<div class="overlay-content">
							<div class="logo-circle">
								<i class="fas fa-wallet"></i>
							</div>
							<h1>Xin chào!</h1>
							<p>Bắt đầu hành trình quản lý tài chính của bạn</p>
							<button class="ghost" id="signUp">ĐĂNG KÝ</button>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>

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

		// Google Sign In
		function googleSignIn() {
			const url = '<?= BASE_URL; ?>/auth/login/google_login';
			console.log('Redirecting to:', url);
			window.location.href = url;
		}

		// Google Sign Up
		function googleSignUp() {
			const url = '<?= BASE_URL; ?>/auth/login/google_login';
			console.log('Redirecting to:', url);
			window.location.href = url;
		}
        
		// Show alert function
		function showAlert(message, type) {
			// Remove existing alert if any
			const existingAlert = document.querySelector('.alert');
			if (existingAlert) {
				existingAlert.remove();
			}

			const alertDiv = document.createElement('div');
			alertDiv.className = `alert ${type}`;
            
			const icon = type === 'success' ? 'check-circle' : 
						type === 'warning' ? 'exclamation-circle' : 
						type === 'error' ? 'times-circle' : 'info-circle';
            
			alertDiv.innerHTML = `
				<i class="fas fa-${icon}"></i>
				<span class="alert-message">${message}</span>
				<span class="close-btn" onclick="this.parentElement.remove()">&times;</span>
			`;
			document.body.appendChild(alertDiv);

			// Auto remove after 4 seconds
			setTimeout(() => {
				alertDiv.style.opacity = '0';
				setTimeout(() => alertDiv.remove(), 300);
			}, 4000);
		}

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
				const response = await fetch('<?= BASE_URL; ?>/auth/login/api_login', {
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
				const response = await fetch('<?= BASE_URL; ?>/auth/login/api_register', {
					method: 'POST',
					headers: {
						'Content-Type': 'application/json'
					},
					body: JSON.stringify(data)
				});

				const result = await response.json();

				if (result.success) {
					// Show success message
					showAlert(result.message || 'Đăng ký thành công!', 'success');
                    
					// Switch to login tab and fill in the credentials
					if (result.switch_to_login) {
						setTimeout(() => {
							// Switch to login panel
							container.classList.remove('right-panel-active');
                            
							// Fill in login form
							const loginForm = document.getElementById('signInForm');
							loginForm.querySelector('input[name="email"]').value = result.login_email || '';
							loginForm.querySelector('input[name="password"]').value = result.login_password || '';
                            
							// Focus on submit button
							loginForm.querySelector('.btn-submit').focus();
                            
							showAlert('Thông tin đã được điền sẵn. Vui lòng nhấn Đăng nhập.', 'info');
						}, 1000);
					}
				} else {
					showAlert(result.message || 'Đăng ký thất bại', 'error');
				}
			} catch (error) {
				console.error('Error:', error);
				showAlert('Lỗi kết nối. Vui lòng thử lại.', 'error');
			}
		});

		// Show toast notification function
		function showAlert(message, type) {
			// Create container if not exists
			let container = document.getElementById('toastContainer');
			if (!container) {
				container = document.createElement('div');
				container.id = 'toastContainer';
				container.className = 'toast-container';
				document.body.appendChild(container);
			}

			const icons = {
				success: 'fa-check-circle',
				error: 'fa-times-circle',
				warning: 'fa-exclamation-triangle',
				info: 'fa-info-circle'
			};

			const titles = {
				success: 'Th\u00e0nh c\u00f4ng',
				error: 'L\u1ed7i',
				warning: 'C\u1ea3nh b\u00e1o',
				info: 'Th\u00f4ng tin'
			};

			const toast = document.createElement('div');
			toast.className = `auth-toast auth-toast-${type}`;
			toast.innerHTML = `
				<div class="auth-toast-icon-wrapper">
					<i class="fas ${icons[type]}"></i>
				</div>
				<div class="auth-toast-content">
					<div class="auth-toast-title">${titles[type]}</div>
					<div class="auth-toast-message">${message}</div>
				</div>
				<button class="auth-toast-close" aria-label="Close">
					<i class="fas fa-times"></i>
				</button>
			`;

			container.appendChild(toast);

			// Add show animation
			setTimeout(() => toast.classList.add('auth-toast-show'), 10);

			// Close button handler
			const closeBtn = toast.querySelector('.auth-toast-close');
			const removeToast = () => {
				toast.classList.remove('auth-toast-show');
				toast.classList.add('auth-toast-hide');
				setTimeout(() => toast.remove(), 300);
			};
			
			closeBtn.addEventListener('click', removeToast);

			// Auto remove after 4 seconds
			setTimeout(removeToast, 4000);
		}
	</script>
</body>

</html>
