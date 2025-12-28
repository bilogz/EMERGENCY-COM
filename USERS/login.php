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
                    <p>Log in using your registered contact number. Verify you are not a bot using CAPTCHA.</p>
                    
                    <!-- Login Form with CAPTCHA -->
                    <form class="auth-form" id="loginForm" style="display: block;">
                        <div class="form-group">
                            <label for="full_name">
                                <i class="fas fa-user"></i> Full Name
                            </label>
                            <input type="text" id="full_name" name="full_name" placeholder="Juan Dela Cruz" required autocomplete="name">
                        </div>

                        <div class="form-group">
                            <label for="phone">
                                <i class="fas fa-phone"></i> Mobile Number
                            </label>
                            <div class="input-with-prefix">
                                <span class="prefix">+63</span>
                                <input type="tel" id="phone" name="phone" pattern="[0-9]{10}" maxlength="10" placeholder="9XXXXXXXXX" title="Enter 10 digits without spaces" required autocomplete="tel">
                            </div>
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
                    </form>

                    <!-- Google OAuth Login -->
                    <div class="auth-divider">
                        <span>OR</span>
                    </div>
                    <button type="button" id="googleLoginBtn" class="btn btn-google">
                        <i class="fab fa-google"></i>
                        <span>Continue with Google</span>
                    </button>

                    <!-- Guest Login Option -->
                    <div class="auth-divider">
                        <span>OR</span>
                    </div>
                    <button type="button" id="guestLoginBtn" class="btn btn-secondary guest-login-btn">
                        <i class="fas fa-user-secret"></i>
                        <span>Continue as Guest (Emergency Only)</span>
                    </button>
                    <p class="guest-notice" style="margin-top: 0.5rem; font-size: 0.85rem; color: #666; text-align: center;">
                        <i class="fas fa-info-circle"></i> Guest access is limited to emergency calls only
                    </p>

                    <div class="auth-switch">
                        <span>Don't have an account?</span>
                        <a href="signup.php" class="btn btn-secondary sign-up-btn">
                            <i class="fas fa-user-plus"></i>
                            <span>Sign Up</span>
                        </a>
                    </div>
                </section>
            </div>
        </div>
    </main>

    <?php include 'includes/footer-snippet.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="<?= $assetBase ?>js/mobile-menu.js"></script>
    <script src="<?= $assetBase ?>js/theme-toggle.js"></script>
    <!-- Google Sign-In API -->
    <script src="https://accounts.google.com/gsi/client" async defer></script>
    <script>
        // Load Google OAuth Client ID
        let googleClientId = null;
        fetch('api/get-google-config.php')
            .then(res => res.json())
            .then(data => {
                if (data.success && data.client_id) {
                    googleClientId = data.client_id;
                    initializeGoogleSignIn();
                }
            })
            .catch(err => console.error('Failed to load Google config:', err));

        function initializeGoogleSignIn() {
            if (!googleClientId) return;

            const googleLoginBtn = document.getElementById('googleLoginBtn');
            if (googleLoginBtn) {
                googleLoginBtn.addEventListener('click', function() {
                    // Use Google Identity Services
                    google.accounts.oauth2.initTokenClient({
                        client_id: googleClientId,
                        scope: 'email profile',
                        callback: handleGoogleTokenResponse
                    }).requestAccessToken();
                });
            }
        }

        function handleGoogleTokenResponse(tokenResponse) {
            // Exchange access token for user info
            fetch('https://www.googleapis.com/oauth2/v2/userinfo', {
                headers: {
                    'Authorization': 'Bearer ' + tokenResponse.access_token
                }
            })
            .then(res => res.json())
            .then(userInfo => {
                // Send user info to backend for verification
                verifyGoogleUser(userInfo);
            })
            .catch(err => {
                console.error('Google token error:', err);
                Swal.fire({
                    icon: 'error',
                    title: 'Authentication Error',
                    text: 'Failed to authenticate with Google. Please try again.'
                });
            });
        }

        async function verifyGoogleUser(userInfo) {
            try {
                const response = await fetch('api/google-oauth.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        action: 'verify',
                        user_info: userInfo
                    })
                });

                const data = await response.json();

                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: data.is_new_user ? 'Account Created!' : 'Login Successful!',
                        text: 'Welcome, ' + data.username,
                        showConfirmButton: false,
                        timer: 1500
                    }).then(() => {
                        window.location.href = '../index.php';
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: data.message || 'Failed to authenticate with Google.'
                    });
                }
            } catch (error) {
                console.error('Google OAuth error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Connection Error',
                    text: 'Please check your internet connection and try again.'
                });
            }
        }

        // Guest Login Handler
        document.addEventListener('DOMContentLoaded', function() {
            const guestLoginBtn = document.getElementById('guestLoginBtn');
            if (guestLoginBtn) {
                guestLoginBtn.addEventListener('click', async function() {
                    const result = await Swal.fire({
                        title: 'Continue as Guest?',
                        html: `
                            <p>Guest access is limited to emergency calls only.</p>
                            <p><strong>You will be able to:</strong></p>
                            <ul style="text-align: left; margin: 1rem 0;">
                                <li>Access emergency hotlines</li>
                                <li>Make emergency calls</li>
                            </ul>
                            <p><strong>You will NOT be able to:</strong></p>
                            <ul style="text-align: left; margin: 1rem 0;">
                                <li>Receive personalized alerts</li>
                                <li>Access your profile</li>
                                <li>Manage preferences</li>
                            </ul>
                        `,
                        icon: 'info',
                        showCancelButton: true,
                        confirmButtonText: 'Continue as Guest',
                        cancelButtonText: 'Cancel',
                        confirmButtonColor: '#28a745'
                    });

                    if (result.isConfirmed) {
                        try {
                            const response = await fetch('api/user-login.php', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                },
                                body: JSON.stringify({
                                    login_type: 'guest',
                                    agreement_accepted: true
                                })
                            });

                            const data = await response.json();

                            if (data.success) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Guest Access Granted',
                                    text: 'Redirecting to emergency services...',
                                    timer: 1500,
                                    showConfirmButton: false
                                }).then(() => {
                                    window.location.href = 'emergency-call.php';
                                });
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: data.message || 'Failed to grant guest access.'
                                });
                            }
                        } catch (error) {
                            console.error('Guest login error:', error);
                            Swal.fire({
                                icon: 'error',
                                title: 'Connection Error',
                                text: 'Please check your internet connection.'
                            });
                        }
                    }
                });
            }
        });
    </script>
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
        
        // Phone input validation (digits only, no leading zero)
        const phoneInput = document.getElementById('phone');
        if (phoneInput) {
            phoneInput.addEventListener('input', function () {
                this.value = this.value.replace(/\D/g, '');
                if (this.value.length === 1 && this.value === '0') {
                    this.value = '';
                }
            });
        }
        
        // Login with phone + CAPTCHA
        loginForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            hideError();
            
            const phone = document.getElementById('phone').value.trim();
            
            if (!phone) {
                showError('Please enter your mobile number.');
                return;
            }
            
            // Validate phone number (should be 10 digits)
            if (phone.length !== 10 || !/^[1-9]\d{9}$/.test(phone)) {
                showError('Please enter a valid 10-digit mobile number.');
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
                // Add +63 prefix to phone number
                const phoneWithPrefix = '+63' + phone;
                const payload = { phone: phoneWithPrefix, captcha_token: captchaToken };

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
                        window.location.href = 'index.php';
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


