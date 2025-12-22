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
                    <p>Log in using your registered contact number. Quick verification required.</p>
                    
                    <!-- Login Form with CAPTCHA -->
                    <form class="auth-form" id="loginForm" style="display: block;">
                        <div class="form-group">
                            <label for="phone">
                                <i class="fas fa-phone"></i> Contact Number
                            </label>
                            <input type="tel" id="phone" name="phone" placeholder="+63 9XX XXX XXXX" required autocomplete="tel">
                        </div>
                        
                        <!-- reCAPTCHA v2 -->
                        <div class="form-group" id="recaptchaContainer" style="margin: 1rem 0; display: flex; justify-content: center;">
                            <script src="https://www.google.com/recaptcha/api.js" async defer></script>
                            <div class="g-recaptcha" data-sitekey="6LeIxAcTAAAAAJcZVRqyHh71UMIEGNQ_MXjiZKhI" data-theme="dark"></div>
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

    <script>
        // Form Elements
        const loginForm = document.getElementById('loginForm');
        const loginButton = document.getElementById('loginButton');
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
        
        // Login with phone + CAPTCHA
        loginForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            hideError();
            
            const phone = document.getElementById('phone').value.trim();
            
            if (!phone) {
                showError('Please enter your contact number.');
                return;
            }
            
            // Get reCAPTCHA token
            const captchaToken = grecaptcha.getResponse();
            if (!captchaToken) {
                showError('Please verify that you are not a bot.');
                return;
            }
            
            setLoading(true);
            
            try {
                const payload = { phone: phone, captcha_token: captchaToken };

                const response = await fetch('api/login-with-phone.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(payload)
                });
                
                const data = await response.json();
                console.log('login response', data);
                
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Login Successful!',
                        text: 'Welcome, ' + (data.user_name || 'User'),
                        showConfirmButton: false,
                        timer: 1500
                    }).then(() => {
                        window.location.href = 'home.php';
                    });
                } else {
                    showError(data.message || 'Login failed. Please check your phone number.');
                    grecaptcha.reset(); // Reset CAPTCHA on failure
                }
                setLoading(false);
            } catch (error) {
                console.error('Login error:', error);
                showError('A connection error occurred. Please try again.');
                setLoading(false);
            }
        });
    </script>
</body>
</html>
