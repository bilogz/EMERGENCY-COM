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
                    <p class="login-instruction">Log in using your registered contact number and full name.</p>
                    
                    <!-- Phone OTP Login Form (Hidden by default) -->
                    <form class="auth-form" id="phoneOtpForm" style="display: none;">
                        <div class="form-group">
                            <label for="otp_phone">
                                <i class="fas fa-phone"></i> Mobile Number
                            </label>
                            <div class="input-with-prefix">
                                <span class="prefix">+63</span>
                                <input type="tel" id="otp_phone" name="otp_phone" pattern="[0-9]{10}" maxlength="10" placeholder="9XXXXXXXXX" required autocomplete="tel">
                            </div>
                            <small class="form-hint">We'll send you a verification code via SMS</small>
                        </div>
                        
                        <div class="error-message" id="otpErrorMessage" style="display: none;">
                            <i class="fas fa-exclamation-circle"></i>
                            <span id="otpErrorText"></span>
                        </div>
                        
                        <button type="submit" class="btn btn-primary" id="sendOtpButton">
                            <i class="fas fa-paper-plane"></i>
                            <span class="btn-text">Send OTP</span>
                            <span class="btn-spinner" style="display: none;">
                                <i class="fas fa-spinner fa-spin"></i>
                            </span>
                        </button>
                        
                        <button type="button" class="btn btn-secondary" id="backToRegularLogin" style="margin-top: 0.5rem;">
                            <i class="fas fa-arrow-left"></i> Back to Regular Login
                        </button>
                    </form>

                    <!-- Login Form -->
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
                            <small class="form-hint">Enter your 10-digit mobile number (without spaces)</small>
                        </div>
                        
                        <div class="error-message" id="errorMessage" style="display: none;">
                            <i class="fas fa-exclamation-circle"></i>
                            <span id="errorText"></span>
                        </div>
                        
                        <button type="submit" class="btn btn-primary btn-large" id="loginButton">
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
                        <span class="google-logo-wrapper">
                            <svg width="18" height="18" viewBox="0 0 18 18" xmlns="http://www.w3.org/2000/svg">
                                <g fill="#000" fill-rule="evenodd">
                                    <path d="M9 3.48c1.69 0 2.83.73 3.48 1.34l2.54-2.48C13.46.89 11.43 0 9 0 5.48 0 2.44 2.02.96 4.96l2.91 2.26C4.6 5.05 6.62 3.48 9 3.48z" fill="#EA4335"/>
                                    <path d="M17.64 9.2c0-.74-.06-1.28-.19-1.84H9v3.34h4.96c-.21 1.18-.84 2.18-1.79 2.87l2.75 2.13c1.66-1.52 2.72-3.76 2.72-6.5z" fill="#4285F4"/>
                                    <path d="M3.88 10.78A5.54 5.54 0 0 1 3.58 9c0-.62.11-1.22.29-1.78L.96 4.96A9.008 9.008 0 0 0 0 9c0 1.45.35 2.82.96 4.04l2.92-2.26z" fill="#FBBC05"/>
                                    <path d="M9 18c2.43 0 4.47-.8 5.96-2.18l-2.75-2.13c-.76.53-1.78.9-3.21.9-2.38 0-4.4-1.57-5.12-3.74L.96 13.04C2.45 15.98 5.48 18 9 18z" fill="#34A853"/>
                                </g>
                            </svg>
                        </span>
                        <span class="google-text">Sign in with Google</span>
                    </button>

                    <!-- Phone OTP Login -->
                    <div class="auth-divider">
                        <span>OR</span>
                    </div>
                    <button type="button" id="phoneOtpLoginBtn" class="btn btn-phone-otp">
                        <i class="fas fa-mobile-alt"></i>
                        <span>Login with Phone Number (OTP)</span>
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
            if (!googleClientId) {
                console.error('Google Client ID not loaded');
                return;
            }

            // Wait for Google Identity Services to load
            if (typeof google === 'undefined' || !google.accounts) {
                setTimeout(initializeGoogleSignIn, 100);
                return;
            }

            const googleLoginBtn = document.getElementById('googleLoginBtn');
            if (googleLoginBtn) {
                googleLoginBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    // Use Google Identity Services OAuth 2.0
                    const tokenClient = google.accounts.oauth2.initTokenClient({
                        client_id: googleClientId,
                        scope: 'email profile',
                        callback: handleGoogleTokenResponse,
                    });
                    
                    tokenClient.requestAccessToken({ prompt: 'consent' });
                });
            }
        }

        function handleGoogleTokenResponse(tokenResponse) {
            if (tokenResponse.error) {
                console.error('Google OAuth error:', tokenResponse.error);
                Swal.fire({
                    icon: 'error',
                    title: 'Authentication Error',
                    text: tokenResponse.error_description || 'Failed to authenticate with Google. Please try again.'
                });
                return;
            }

            // Exchange access token for user info
            fetch('https://www.googleapis.com/oauth2/v2/userinfo?access_token=' + tokenResponse.access_token)
                .then(res => {
                    if (!res.ok) {
                        throw new Error('Failed to fetch user info');
                    }
                    return res.json();
                })
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

        // Phone OTP Login Handler
        document.addEventListener('DOMContentLoaded', function() {
            const phoneOtpBtn = document.getElementById('phoneOtpLoginBtn');
            const phoneOtpForm = document.getElementById('phoneOtpForm');
            const loginForm = document.getElementById('loginForm');
            const backToRegularBtn = document.getElementById('backToRegularLogin');
            const sendOtpBtn = document.getElementById('sendOtpButton');
            const otpPhoneInput = document.getElementById('otp_phone');
            const otpErrorMessage = document.getElementById('otpErrorMessage');
            const otpErrorText = document.getElementById('otpErrorText');

            // Show phone OTP form
            if (phoneOtpBtn) {
                phoneOtpBtn.addEventListener('click', function() {
                    loginForm.style.display = 'none';
                    phoneOtpForm.style.display = 'block';
                    otpPhoneInput.focus();
                });
            }

            // Back to regular login
            if (backToRegularBtn) {
                backToRegularBtn.addEventListener('click', function() {
                    phoneOtpForm.style.display = 'none';
                    loginForm.style.display = 'block';
                    otpErrorMessage.style.display = 'none';
                });
            }

            // Phone input validation
            if (otpPhoneInput) {
                otpPhoneInput.addEventListener('input', function () {
                    this.value = this.value.replace(/\D/g, '');
                    if (this.value.length === 1 && this.value === '0') {
                        this.value = '';
                    }
                    otpErrorMessage.style.display = 'none';
                });
            }

            // Send OTP handler
            if (phoneOtpForm) {
                phoneOtpForm.addEventListener('submit', async function(e) {
                    e.preventDefault();
                    otpErrorMessage.style.display = 'none';

                    const phone = otpPhoneInput.value.trim();
                    
                    if (!phone || phone.length !== 10) {
                        otpErrorText.textContent = 'Please enter a valid 10-digit mobile number.';
                        otpErrorMessage.style.display = 'flex';
                        return;
                    }

                    // Disable button and show loading
                    sendOtpBtn.disabled = true;
                    sendOtpBtn.querySelector('.btn-text').style.display = 'none';
                    sendOtpBtn.querySelector('.btn-spinner').style.display = 'inline-block';

                    try {
                        const phoneWithPrefix = '+63' + phone;
                        const response = await fetch('api/send-otp.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify({ phone: phoneWithPrefix })
                        });

                        const data = await response.json();

                        if (data.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'OTP Sent!',
                                text: 'A verification code has been sent to your phone. Please check your SMS.',
                                showConfirmButton: false,
                                timer: 2000
                            }).then(() => {
                                // TODO: Show OTP verification modal/input
                                // For now, redirect to regular login
                                Swal.fire({
                                    icon: 'info',
                                    title: 'OTP Feature',
                                    text: 'OTP verification will be implemented soon. Please use regular login for now.',
                                    confirmButtonText: 'OK'
                                }).then(() => {
                                    phoneOtpForm.style.display = 'none';
                                    loginForm.style.display = 'block';
                                });
                            });
                        } else {
                            otpErrorText.textContent = data.message || 'Failed to send OTP. Please try again.';
                            otpErrorMessage.style.display = 'flex';
                        }
                    } catch (error) {
                        console.error('Send OTP error:', error);
                        otpErrorText.textContent = 'Connection error. Please check your internet connection.';
                        otpErrorMessage.style.display = 'flex';
                    } finally {
                        sendOtpBtn.disabled = false;
                        sendOtpBtn.querySelector('.btn-text').style.display = 'inline';
                        sendOtpBtn.querySelector('.btn-spinner').style.display = 'none';
                    }
                });
            }
        });

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
        
        // Login with phone (no CAPTCHA)
        loginForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            hideError();
            
            const fullName = document.getElementById('full_name').value.trim();
            const phone = document.getElementById('phone').value.trim();
            
            if (!fullName) {
                showError('Please enter your full name.');
                return;
            }
            
            if (!phone) {
                showError('Please enter your mobile number.');
                return;
            }
            
            // Validate phone number (should be 10 digits)
            if (phone.length !== 10 || !/^[1-9]\d{9}$/.test(phone)) {
                showError('Please enter a valid 10-digit mobile number.');
                return;
            }
            
            setLoading(true);
            
            try {
                // Add +63 prefix to phone number
                const phoneWithPrefix = '+63' + phone;
                const payload = { phone: phoneWithPrefix, name: fullName };

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
                        text: 'Welcome, ' + (data.user_name || fullName),
                        showConfirmButton: false,
                        timer: 1500
                    }).then(() => {
                        window.location.href = '../index.php';
                    });
                } else {
                    showError(data.message || 'Login failed. Please check your phone number and name.');
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


