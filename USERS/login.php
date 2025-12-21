<?php
$assetBase = '../ADMIN/header/';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Login</title>
    <link rel="icon" type="image/x-icon" href="<?= $assetBase ?>images/favicon.ico">
    <link rel="stylesheet" href="<?= $assetBase ?>css/global.css">
    <link rel="stylesheet" href="<?= $assetBase ?>css/buttons.css">
    <link rel="stylesheet" href="<?= $assetBase ?>css/hero.css">
    <link rel="stylesheet" href="../ADMIN/sidebar/css/global.css">
    <link rel="stylesheet" href="../ADMIN/sidebar/css/sidebar.css">
    <link rel="stylesheet" href="../ADMIN/sidebar/css/content.css">
    <link rel="stylesheet" href="../ADMIN/sidebar/css/buttons.css">
    <link rel="stylesheet" href="css/user.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <button class="sidebar-toggle-btn" aria-label="Toggle menu" onclick="window.sidebarToggle()">
        <i class="fas fa-bars"></i>
    </button>

    <main class="main-content">
        <div class="main-container">
            <div class="sub-container content-main">
                <section class="page-content">
                    <h2>User Login</h2>
                    <p id="loginDescription">Log in using your registered name and mobile number.</p>
                    
                    <!-- Login Method Toggle -->
                    <div class="login-method-toggle">
                        <button type="button" class="method-btn active" data-method="name-phone" id="methodNamePhone">
                            <i class="fas fa-user"></i> Name + Phone
                        </button>
                        <button type="button" class="method-btn" data-method="phone-only" id="methodPhoneOnly">
                            <i class="fas fa-phone"></i> Phone Only
                        </button>
                    </div>
                    
                    <form class="auth-form" id="loginForm">
                        <div class="form-group" id="nameGroup">
                            <label for="full_name">Full Name</label>
                            <input type="text" id="full_name" name="full_name" placeholder="Juan Dela Cruz">
                        </div>
                        <div class="form-group">
                            <label for="phone">Mobile Number</label>
                            <input type="tel" id="phone" name="phone" placeholder="+63 9XX XXX XXXX" required>
                        </div>
                        
                        <div class="error-message" id="errorMessage" style="display: none;">
                            <i class="fas fa-exclamation-circle"></i>
                            <span id="errorText"></span>
                        </div>
                        
                        <button type="submit" class="btn btn-primary" id="loginButton">
                            <i class="fas fa-sign-in-alt"></i>
                            <span class="btn-text">Login</span>
                            <span class="btn-spinner" style="display: none;">
                                <i class="fas fa-spinner fa-spin"></i>
                            </span>
                        </button>
                        
                        <div class="divider">
                            <span>or</span>
                        </div>
                        
                        <button type="button" class="btn btn-secondary" id="guestLoginButton">
                            <i class="fas fa-user-secret"></i>
                            Continue as Guest
                        </button>
                        
                        <p class="auth-switch">
                            Don't have an account?
                            <a href="signup.php">Sign up</a>
                        </p>
                    </form>
                </section>
            </div>
        </div>
    </main>

    <?php include 'includes/footer-snippet.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="<?= $assetBase ?>js/mobile-menu.js"></script>
    <script src="<?= $assetBase ?>js/theme-toggle.js"></script>
    <style>
        .login-method-toggle {
            display: flex;
            gap: 0.75rem;
            margin-bottom: 1.5rem;
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 10px;
            padding: 0.5rem;
        }
        
        .method-btn {
            flex: 1;
            padding: 0.75rem 1rem;
            border: none;
            background: transparent;
            border-radius: 8px;
            cursor: pointer;
            font-size: 0.9rem;
            font-weight: 500;
            color: var(--text-muted);
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }
        
        .method-btn:hover {
            background: rgba(0, 0, 0, 0.05);
        }
        
        .method-btn.active {
            background: var(--primary-color-1, #4c8a89);
            color: white;
        }
        
        .method-btn i {
            font-size: 1rem;
        }
        
        .error-message {
            background-color: rgba(220, 53, 69, 0.1);
            border: 1px solid rgba(220, 53, 69, 0.3);
            color: #dc3545;
            padding: 0.75rem 1rem;
            border-radius: 8px;
            font-size: 0.9rem;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .error-message i {
            font-size: 1rem;
        }
        
        .divider {
            display: flex;
            align-items: center;
            text-align: center;
            margin: 1.5rem 0;
            color: var(--text-muted);
            font-size: 0.85rem;
        }
        
        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            border-bottom: 1px solid var(--card-border);
        }
        
        .divider span {
            padding: 0 1rem;
        }
        
        .btn-secondary {
            background-color: var(--card-bg);
            border: 2px solid var(--card-border);
            color: var(--text-color-1);
        }
        
        .btn-secondary:hover {
            background-color: rgba(0, 0, 0, 0.05);
            border-color: var(--primary-color-1, #4c8a89);
        }
        
        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        
        .btn-spinner {
            display: inline-block;
        }
        
        [data-theme="dark"] .method-btn:hover {
            background: rgba(255, 255, 255, 0.05);
        }
        
        [data-theme="dark"] .btn-secondary:hover {
            background-color: rgba(255, 255, 255, 0.05);
        }
    </style>
    <script>
        // Login Method Toggle
        const methodButtons = document.querySelectorAll('.method-btn');
        const nameGroup = document.getElementById('nameGroup');
        const fullNameInput = document.getElementById('full_name');
        let currentMethod = 'name-phone';
        
        const loginDescription = document.getElementById('loginDescription');
        
        methodButtons.forEach(btn => {
            btn.addEventListener('click', function() {
                methodButtons.forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                currentMethod = this.dataset.method;
                
                if (currentMethod === 'phone-only') {
                    nameGroup.style.display = 'none';
                    fullNameInput.removeAttribute('required');
                    fullNameInput.value = '';
                    loginDescription.textContent = 'Log in using your registered mobile number only.';
                } else {
                    nameGroup.style.display = 'block';
                    fullNameInput.setAttribute('required', 'required');
                    loginDescription.textContent = 'Log in using your registered name and mobile number.';
                }
            });
        });
        
        // Form Elements
        const loginForm = document.getElementById('loginForm');
        const loginButton = document.getElementById('loginButton');
        const guestLoginButton = document.getElementById('guestLoginButton');
        const errorMessage = document.getElementById('errorMessage');
        const errorText = document.getElementById('errorText');
        
        function showError(message) {
            errorText.textContent = message;
            errorMessage.style.display = 'flex';
            setTimeout(() => {
                errorMessage.style.display = 'none';
            }, 5000);
        }
        
        function hideError() {
            errorMessage.style.display = 'none';
        }
        
        function setLoading(isLoading) {
            if (isLoading) {
                loginButton.disabled = true;
                loginButton.querySelector('.btn-text').style.display = 'none';
                loginButton.querySelector('.btn-spinner').style.display = 'inline-block';
            } else {
                loginButton.disabled = false;
                loginButton.querySelector('.btn-text').style.display = 'inline';
                loginButton.querySelector('.btn-spinner').style.display = 'none';
            }
        }
        
        // Standard Login
        loginForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            hideError();
            
            const phone = document.getElementById('phone').value.trim();
            const fullName = document.getElementById('full_name').value.trim();
            
            // Validation
            if (!phone) {
                showError('Please enter your mobile number.');
                return;
            }
            
            if (currentMethod === 'name-phone' && !fullName) {
                showError('Please enter your full name.');
                return;
            }
            
            setLoading(true);
            
            try {
                const response = await fetch('api/user-login.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        login_type: 'standard',
                        phone: phone,
                        full_name: currentMethod === 'name-phone' ? fullName : null
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Login Successful!',
                        text: 'Welcome back, ' + (data.username || 'User') + '!',
                        showConfirmButton: false,
                        timer: 1500
                    }).then(() => {
                        // Redirect to home page
                        window.location.href = 'home.php';
                    });
                } else {
                    showError(data.message || 'Login failed. Please check your credentials and try again.');
                    setLoading(false);
                }
            } catch (error) {
                console.error('Login error:', error);
                showError('A connection error occurred. Please check your internet connection and try again.');
                setLoading(false);
            }
        });
        
        // Guest Login
        guestLoginButton.addEventListener('click', async function() {
            hideError();
            this.disabled = true;
            this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Logging in...';
            
            try {
                const response = await fetch('api/user-login.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        login_type: 'guest'
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Guest Access Granted!',
                        text: 'You are now browsing as a guest. Some features may be limited.',
                        showConfirmButton: false,
                        timer: 1500
                    }).then(() => {
                        // Redirect to home page
                        window.location.href = 'home.php';
                    });
                } else {
                    showError(data.message || 'Guest login failed. Please try again.');
                    this.disabled = false;
                    this.innerHTML = '<i class="fas fa-user-secret"></i> Continue as Guest';
                }
            } catch (error) {
                console.error('Guest login error:', error);
                showError('A connection error occurred. Please try again.');
                this.disabled = false;
                this.innerHTML = '<i class="fas fa-user-secret"></i> Continue as Guest';
            }
        });
    </script>
</body>
</html>


