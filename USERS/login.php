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
    <script src="js/translations.js?v=<?= @filemtime(__DIR__ . '/js/translations.js') ?>"></script>
    <script src="js/language-manager.js?v=<?= @filemtime(__DIR__ . '/js/language-manager.js') ?>"></script>
    <script src="js/global-translator.js?v=<?= @filemtime(__DIR__ . '/js/global-translator.js') ?>"></script>
    <script src="js/language-selector-modal.js?v=<?= @filemtime(__DIR__ . '/js/language-selector-modal.js') ?>"></script>
    <script src="js/language-sync.js?v=<?= @filemtime(__DIR__ . '/js/language-sync.js') ?>"></script>
    <script src="https://accounts.google.com/gsi/client" async defer></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const langBtn = document.getElementById('languageSelectorBtn');
            if (langBtn && window.languageSelectorModal) {
                langBtn.addEventListener('click', function() {
                    window.languageSelectorModal.open();
                });
            }
        });
    </script>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <button class="sidebar-toggle-btn" aria-label="Toggle menu" onclick="window.sidebarToggle()" data-no-translate>
        <i class="fas fa-bars"></i>
    </button>

    <main class="main-content">
        <div class="main-container">
            <div class="sub-container content-main">
                <div class="auth-page">
                <section class="page-content">
                    <h2 data-translate="login.title">User Login</h2>
                    <p class="login-instruction" data-translate="login.instruction">Log in using your registered contact number and full name.</p>
                    
                    <!-- Login Form -->
                    <form class="auth-form" id="loginForm" style="display: block;">
                        <div class="form-group">
                            <label for="full_name">
                                <i class="fas fa-user"></i> <span data-translate="login.fullName">Full Name</span>
                            </label>
                            <input type="text" id="full_name" name="full_name" placeholder="Juan Dela Cruz" required autocomplete="name">
                        </div>

                        <div class="form-group">
                            <label for="phone">
                                <i class="fas fa-phone"></i> <span data-translate="login.mobileNumber">Mobile Number</span>
                            </label>
                            <div class="input-with-prefix">
                                <span class="prefix">+63</span>
                                <input type="tel" id="phone" name="phone" pattern="[0-9]{10}" maxlength="10" placeholder="9XXXXXXXXX" title="Enter 10 digits without spaces" required autocomplete="tel">
                            </div>
                            <small class="form-hint" data-translate="login.mobileHint">Enter your 10-digit mobile number (without spaces)</small>
                        </div>
                        
                        <div class="error-message" id="errorMessage" style="display: none;">
                            <i class="fas fa-exclamation-circle"></i>
                            <span id="errorText"></span>
                        </div>
                        
                        <button type="submit" class="btn btn-primary btn-large" id="loginButton">
                            <i class="fas fa-sign-in-alt"></i>
                            <span class="btn-text" data-translate="login.login">Login</span>
                            <span class="btn-spinner" style="display: none;">
                                <i class="fas fa-spinner fa-spin"></i>
                            </span>
                        </button>
                    </form>

                    <!-- Email OTP Login Form (Hidden by default) -->
                    <form class="auth-form" id="emailOtpForm" style="display: none;">
                        <div class="form-group">
                            <label for="otp_email">
                                <i class="fas fa-envelope"></i> Email Address (Gmail)
                            </label>
                            <input type="email" id="otp_email" name="otp_email" placeholder="your.email@gmail.com" required autocomplete="email">
                            <small class="form-hint">We'll send you a verification code via email</small>
                        </div>
                        
                        <div class="error-message" id="emailOtpErrorMessage" style="display: none;">
                            <i class="fas fa-exclamation-circle"></i>
                            <span id="emailOtpErrorText"></span>
                        </div>
                        
                        <button type="submit" class="btn btn-primary" id="sendEmailOtpButton">
                            <i class="fas fa-paper-plane"></i>
                            <span class="btn-text">Send OTP to Email</span>
                            <span class="btn-spinner" style="display: none;">
                                <i class="fas fa-spinner fa-spin"></i>
                            </span>
                        </button>
                        
                        <button type="button" class="btn btn-secondary" id="backToRegularLoginFromEmail" style="margin-top: 0.5rem;">
                            <i class="fas fa-arrow-left"></i> <span data-translate="login.backToLogin">Back to Regular Login</span>
                        </button>
                    </form>

                    <!-- Phone OTP Login Form (Hidden by default) -->
                    <form class="auth-form" id="phoneOtpForm" style="display: none;">
                        <div class="form-group">
                            <label for="otp_phone">
                                <i class="fas fa-phone"></i> <span data-translate="login.mobileNumber">Mobile Number</span>
                            </label>
                            <div class="input-with-prefix">
                                <span class="prefix">+63</span>
                                <input type="tel" id="otp_phone" name="otp_phone" pattern="[0-9]{10}" maxlength="10" placeholder="9XXXXXXXXX" required autocomplete="tel">
                            </div>
                            <small class="form-hint" data-translate="login.smsHint">We'll send you a verification code via SMS</small>
                        </div>
                        
                        <div class="error-message" id="otpErrorMessage" style="display: none;">
                            <i class="fas fa-exclamation-circle"></i>
                            <span id="otpErrorText"></span>
                        </div>
                        
                        <button type="submit" class="btn btn-primary" id="sendOtpButton">
                            <i class="fas fa-paper-plane"></i>
                            <span class="btn-text" data-translate="login.sendOTP">Send OTP</span>
                            <span class="btn-spinner" style="display: none;">
                                <i class="fas fa-spinner fa-spin"></i>
                            </span>
                        </button>
                        
                        <button type="button" class="btn btn-secondary" id="backToRegularLogin" style="margin-top: 0.5rem;">
                            <i class="fas fa-arrow-left"></i> <span data-translate="login.backToLogin">Back to Regular Login</span>
                        </button>
                    </form>

                    <!-- Email OTP Verification Modal for Login -->
                    <div id="emailOtpLoginModal" class="modal" aria-hidden="true" style="display:none;">
                        <div class="modal-backdrop"></div>
                        <div class="modal-content" role="dialog" aria-modal="true" aria-labelledby="emailOtpLoginModalTitle">
                            <button class="modal-close" id="emailOtpLoginModalClose" aria-label="Close">&times;</button>
                            <h3 id="emailOtpLoginModalTitle" data-translate="login.verifyEmail">Verify Your Email</h3>
                            <p class="modal-sub">We've sent a 6-digit verification code to <strong id="emailOtpEmailDisplay"></strong></p>

                            <div id="emailOtpLoginSentBanner" style="display:none; margin-bottom:0.75rem; padding:0.75rem; border-radius:6px; background: rgba(40,167,69,0.12); border:1px solid rgba(40,167,69,0.2); color: #28a745;">Verification code sent successfully.</div>
                            <div id="emailOtpLoginWarnBanner" style="display:none; margin-bottom:0.75rem; padding:0.75rem; border-radius:6px; background: rgba(255,193,7,0.12); border:1px solid rgba(255,193,7,0.2); color: #856404;">Verification code generated but email delivery failed. Use the debug code below for testing.</div>
                            <div id="emailOtpLoginDebugCode" style="display:none; margin-bottom:1rem; padding:1rem; background: #fffacd; border:2px solid #ffd700; border-radius:6px; font-weight:700; text-align:center; font-size:1.2rem; color: #d4941e;"></div>

                            <form id="emailOtpLoginModalForm" class="auth-form">
                                <div class="form-group">
                                    <label for="email_otp_login_code">
                                        <i class="fas fa-key"></i> Verification Code
                                    </label>
                                    <input type="text" id="email_otp_login_code" name="email_otp_login_code" placeholder="Enter 6-digit code" maxlength="6" pattern="[0-9]{6}" required autocomplete="one-time-code">
                                    <small class="form-hint">Enter the 6-digit code sent to your email</small>
                                </div>

                                <div class="error-message" id="emailOtpLoginModalErrorMessage" style="display: none;">
                                    <i class="fas fa-exclamation-circle"></i>
                                    <span id="emailOtpLoginModalErrorText"></span>
                                </div>

                                <div class="modal-actions">
                                    <button type="submit" class="btn btn-primary" id="emailOtpLoginVerifyButton">
                                        <i class="fas fa-check-circle"></i>
                                        <span class="btn-text">Verify & Login</span>
                                        <span class="btn-spinner" style="display: none;">
                                            <i class="fas fa-spinner fa-spin"></i>
                                        </span>
                                    </button>

                                    <button type="button" class="btn-link" id="emailOtpLoginResendButton">
                                        <i class="fas fa-redo"></i> Resend Code
                                    </button>

                                    <button type="button" class="btn-link" id="emailOtpLoginBackButton">
                                        <i class="fas fa-arrow-left"></i> Back
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Phone OTP Verification Modal for Login -->
                    <div id="otpLoginModal" class="modal" aria-hidden="true" style="display:none;">
                        <div class="modal-backdrop"></div>
                        <div class="modal-content" role="dialog" aria-modal="true" aria-labelledby="otpLoginModalTitle">
                            <button class="modal-close" id="otpLoginModalClose" aria-label="Close">&times;</button>
                            <h3 id="otpLoginModalTitle" data-translate="login.verifyPhone">Verify Your Phone</h3>
                            <p class="modal-sub">We've sent a 6-digit verification code to <strong id="otpPhoneDisplay"></strong></p>

                            <div id="otpLoginSentBanner" style="display:none; margin-bottom:0.75rem; padding:0.75rem; border-radius:6px; background: rgba(40,167,69,0.12); border:1px solid rgba(40,167,69,0.2); color: #28a745;">Verification code sent successfully.</div>
                            <div id="otpLoginWarnBanner" style="display:none; margin-bottom:0.75rem; padding:0.75rem; border-radius:6px; background: rgba(255,193,7,0.12); border:1px solid rgba(255,193,7,0.2); color: #856404;">Verification code generated but SMS delivery failed. Use the debug code below for testing.</div>
                            <div id="otpLoginDebugCode" style="display:none; margin-bottom:1rem; padding:1rem; background: #fffacd; border:2px solid #ffd700; border-radius:6px; font-weight:700; text-align:center; font-size:1.2rem; color: #d4941e;"></div>

                            <form id="otpLoginModalForm" class="auth-form">
                                <div class="form-group">
                                    <label for="otp_login_code">
                                        <i class="fas fa-key"></i> Verification Code
                                    </label>
                                    <input type="text" id="otp_login_code" name="otp_login_code" placeholder="Enter 6-digit code" maxlength="6" pattern="[0-9]{6}" required autocomplete="one-time-code">
                                    <small class="form-hint">Enter the 6-digit code sent to your phone</small>
                                </div>

                                <div class="error-message" id="otpLoginModalErrorMessage" style="display: none;">
                                    <i class="fas fa-exclamation-circle"></i>
                                    <span id="otpLoginModalErrorText"></span>
                                </div>

                                <div class="modal-actions">
                                    <button type="submit" class="btn btn-primary" id="otpLoginVerifyButton">
                                        <i class="fas fa-check-circle"></i>
                                        <span class="btn-text">Verify & Login</span>
                                        <span class="btn-spinner" style="display: none;">
                                            <i class="fas fa-spinner fa-spin"></i>
                                        </span>
                                    </button>

                                    <button type="button" class="btn-link" id="otpLoginResendButton">
                                        <i class="fas fa-redo"></i> Resend Code
                                    </button>

                                    <button type="button" class="btn-link" id="otpLoginBackButton">
                                        <i class="fas fa-arrow-left"></i> Back
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Alternative Login Options -->
                    <div class="auth-divider">
                        <span data-translate="login.or">OR</span>
                    </div>
                    <div class="alternative-login-buttons" style="display: flex; gap: 1rem; flex-wrap: wrap;">
                        <!-- Google OAuth Login -->
                        <button type="button" id="googleLoginBtn" class="btn btn-google" data-no-translate style="flex: 1; min-width: 200px;">
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
                            <span class="google-text">Login with Google</span>
                        </button>

                        <!-- Phone OTP Login -->
                        <button type="button" id="phoneOtpLoginBtn" class="btn btn-phone-otp" style="flex: 1; min-width: 200px;">
                            <i class="fas fa-mobile-alt"></i>
                            <span data-translate="login.withPhone">Login with Phone Number (OTP)</span>
                        </button>
                    </div>

                    <!-- Create Account Button -->
                    <div class="auth-switch" style="margin-top: 1rem;">
                        <a href="signup.php" class="btn btn-secondary sign-up-btn" style="width: 100%; justify-content: center;">
                            <i class="fas fa-user-plus"></i>
                            <span data-translate="login.createAccount">Create Account</span>
                        </a>
                    </div>
                </section>
                </div>
            </div>
        </div>
    </main>

    <?php include 'includes/footer-snippet.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="<?= $assetBase ?>js/mobile-menu.js"></script>
    <script src="<?= $assetBase ?>js/theme-toggle.js"></script>
    <script>


        // Email OTP Login Handler
        document.addEventListener('DOMContentLoaded', function() {
            const emailOtpBtn = document.getElementById('emailOtpLoginBtn');
            const emailOtpForm = document.getElementById('emailOtpForm');
            const loginForm = document.getElementById('loginForm');
            const backToRegularFromEmailBtn = document.getElementById('backToRegularLoginFromEmail');
            const sendEmailOtpBtn = document.getElementById('sendEmailOtpButton');
            const otpEmailInput = document.getElementById('otp_email');
            const emailOtpErrorMessage = document.getElementById('emailOtpErrorMessage');
            const emailOtpErrorText = document.getElementById('emailOtpErrorText');

            // Show email OTP form
            if (emailOtpBtn) {
                emailOtpBtn.addEventListener('click', function() {
                    loginForm.style.display = 'none';
                    emailOtpForm.style.display = 'block';
                    otpEmailInput.focus();
                });
            }

            // Back to regular login from email form
            if (backToRegularFromEmailBtn) {
                backToRegularFromEmailBtn.addEventListener('click', function() {
                    emailOtpForm.style.display = 'none';
                    loginForm.style.display = 'block';
                    emailOtpErrorMessage.style.display = 'none';
                });
            }

            // Send Email OTP handler
            if (emailOtpForm) {
                emailOtpForm.addEventListener('submit', async function(e) {
                    e.preventDefault();
                    emailOtpErrorMessage.style.display = 'none';

                    const email = otpEmailInput.value.trim();
                    
                    if (!email || !email.includes('@')) {
                        emailOtpErrorText.textContent = 'Please enter a valid email address.';
                        emailOtpErrorMessage.style.display = 'flex';
                        return;
                    }

                    // Disable button and show loading
                    sendEmailOtpBtn.disabled = true;
                    sendEmailOtpBtn.querySelector('.btn-text').style.display = 'none';
                    sendEmailOtpBtn.querySelector('.btn-spinner').style.display = 'inline-block';

                    try {
                        const response = await fetch('api/send-login-email-otp.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify({ email: email })
                        });

                        const data = await response.json();

                        if (data.success) {
                            // Store email for OTP verification
                            window.emailOtpEmail = email;
                            
                            // Show appropriate banner
                            const sentBanner = document.getElementById('emailOtpLoginSentBanner');
                            const warnBanner = document.getElementById('emailOtpLoginWarnBanner');
                            const debugCode = document.getElementById('emailOtpLoginDebugCode');
                            
                            sentBanner.style.display = 'none';
                            warnBanner.style.display = 'none';
                            debugCode.style.display = 'none';

                            if (data.otp_sent === true) {
                                sentBanner.textContent = 'Verification code sent successfully to ' + email + '.';
                                sentBanner.style.display = 'block';
                            } else {
                                warnBanner.style.display = 'block';
                                if (data.debug_otp) {
                                    debugCode.innerHTML = '<strong>DEBUG OTP CODE:</strong><br>' + data.debug_otp;
                                    debugCode.style.display = 'block';
                                    console.log('DEBUG OTP:', data.debug_otp);
                                }
                            }
                            
                            // Show OTP verification modal
                            document.getElementById('emailOtpEmailDisplay').textContent = email;
                            openEmailOtpLoginModal();
                            startEmailOtpResendCooldown(60);
                            
                            Swal.fire({
                                icon: 'success',
                                title: 'OTP Sent!',
                                text: 'A verification code has been sent to your email. Please check your inbox.',
                                showConfirmButton: false,
                                timer: 2000
                            });
                        } else {
                            emailOtpErrorText.textContent = data.message || 'Failed to send OTP. Please try again.';
                            emailOtpErrorMessage.style.display = 'flex';
                        }
                    } catch (error) {
                        console.error('Send Email OTP error:', error);
                        emailOtpErrorText.textContent = 'Connection error. Please check your internet connection.';
                        emailOtpErrorMessage.style.display = 'flex';
                    } finally {
                        sendEmailOtpBtn.disabled = false;
                        sendEmailOtpBtn.querySelector('.btn-text').style.display = 'inline';
                        sendEmailOtpBtn.querySelector('.btn-spinner').style.display = 'none';
                    }
                });
            }
        });

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
                            // Store phone for OTP verification
                            window.otpPhoneNumber = phoneWithPrefix;
                            
                            // Show appropriate banner
                            const sentBanner = document.getElementById('otpLoginSentBanner');
                            const warnBanner = document.getElementById('otpLoginWarnBanner');
                            const debugCode = document.getElementById('otpLoginDebugCode');
                            
                            sentBanner.style.display = 'none';
                            warnBanner.style.display = 'none';
                            debugCode.style.display = 'none';

                            if (data.otp_sent === true) {
                                sentBanner.textContent = 'Verification code sent successfully to ' + phoneWithPrefix + '.';
                                sentBanner.style.display = 'block';
                            } else {
                                warnBanner.style.display = 'block';
                                if (data.debug_otp) {
                                    debugCode.innerHTML = '<strong>DEBUG OTP CODE:</strong><br>' + data.debug_otp;
                                    debugCode.style.display = 'block';
                                    console.log('DEBUG OTP:', data.debug_otp);
                                }
                            }
                            
                            // Show OTP verification modal
                            document.getElementById('otpPhoneDisplay').textContent = phoneWithPrefix;
                            openOtpLoginModal();
                            startOtpResendCooldown(60);
                            
                            Swal.fire({
                                icon: 'success',
                                title: 'OTP Sent!',
                                text: 'A verification code has been sent to your phone. Please check your SMS.',
                                showConfirmButton: false,
                                timer: 2000
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

        // Email OTP Login Modal Functions
        const emailOtpLoginModal = document.getElementById('emailOtpLoginModal');
        const emailOtpLoginModalForm = document.getElementById('emailOtpLoginModalForm');
        const emailOtpLoginVerifyButton = document.getElementById('emailOtpLoginVerifyButton');
        const emailOtpLoginModalErrorMessage = document.getElementById('emailOtpLoginModalErrorMessage');
        const emailOtpLoginModalErrorText = document.getElementById('emailOtpLoginModalErrorText');
        const emailOtpLoginResendButton = document.getElementById('emailOtpLoginResendButton');
        const emailOtpLoginBackButton = document.getElementById('emailOtpLoginBackButton');
        const emailOtpLoginModalClose = document.getElementById('emailOtpLoginModalClose');

        function openEmailOtpLoginModal() {
            emailOtpLoginModal.style.display = 'flex';
            emailOtpLoginModal.setAttribute('aria-hidden', 'false');
            document.getElementById('email_otp_login_code').value = '';
            document.getElementById('email_otp_login_code').focus();
        }

        function closeEmailOtpLoginModal() {
            emailOtpLoginModal.style.display = 'none';
            emailOtpLoginModal.setAttribute('aria-hidden', 'true');
            emailOtpLoginModalErrorMessage.style.display = 'none';
        }

        // Close modal buttons
        if (emailOtpLoginModalClose) emailOtpLoginModalClose.addEventListener('click', closeEmailOtpLoginModal);
        if (emailOtpLoginBackButton) emailOtpLoginBackButton.addEventListener('click', function() {
            closeEmailOtpLoginModal();
            emailOtpForm.style.display = 'none';
            loginForm.style.display = 'block';
        });

        // Resend cooldown timer for email
        let emailOtpResendCountdownTimer = null;
        function startEmailOtpResendCooldown(seconds) {
            if (emailOtpResendCountdownTimer) clearInterval(emailOtpResendCountdownTimer);
            let remaining = seconds;
            emailOtpLoginResendButton.disabled = true;
            emailOtpLoginResendButton.innerHTML = `<i class="fas fa-redo"></i> Resend Code (${remaining}s)`;

            emailOtpResendCountdownTimer = setInterval(() => {
                remaining--;
                if (remaining <= 0) {
                    clearInterval(emailOtpResendCountdownTimer);
                    emailOtpLoginResendButton.disabled = false;
                    emailOtpLoginResendButton.innerHTML = '<i class="fas fa-redo"></i> Resend Code';
                } else {
                    emailOtpLoginResendButton.innerHTML = `<i class="fas fa-redo"></i> Resend Code (${remaining}s)`;
                }
            }, 1000);
        }

        // Resend Email OTP
        if (emailOtpLoginResendButton) {
            emailOtpLoginResendButton.addEventListener('click', async function() {
                if (emailOtpLoginResendButton.disabled) return;
                
                const email = window.emailOtpEmail;
                if (!email) {
                    Swal.fire({ icon: 'error', title: 'Error', text: 'Email not found.' });
                    return;
                }
                
                try {
                    const response = await fetch('api/send-login-email-otp.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ email: email })
                    });
                    
                    const data = await response.json();
                    if (data.success) {
                        Swal.fire({ icon: 'success', title: 'Code Resent', text: 'A new verification code has been sent to your email.', timer: 1500, showConfirmButton: false });
                        startEmailOtpResendCooldown(60);
                    } else {
                        Swal.fire({ icon: 'error', title: 'Error', text: data.message || 'Failed to resend code.' });
                    }
                } catch (error) {
                    console.error('Resend Email OTP error:', error);
                    Swal.fire({ icon: 'error', title: 'Error', text: 'A connection error occurred.' });
                }
            });
        }

        // Verify Email OTP and Login
        if (emailOtpLoginModalForm) {
            emailOtpLoginModalForm.addEventListener('submit', async function(e) {
                e.preventDefault();
                
                const otp = document.getElementById('email_otp_login_code').value.trim();
                const email = window.emailOtpEmail;
                
                if (!otp || otp.length !== 6) {
                    emailOtpLoginModalErrorText.textContent = 'Please enter a valid 6-digit code.';
                    emailOtpLoginModalErrorMessage.style.display = 'flex';
                    return;
                }
                
                if (!email) {
                    emailOtpLoginModalErrorText.textContent = 'Email not found. Please start over.';
                    emailOtpLoginModalErrorMessage.style.display = 'flex';
                    return;
                }
                
                setEmailOtpLoginLoading(true);
                
                try {
                    const response = await fetch('api/verify-login-email-otp.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ otp: otp, email: email })
                    });
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        // Store user info in sessionStorage for Firebase chat
                        if (data.user_id) {
                            sessionStorage.setItem('user_id', data.user_id);
                        }
                        if (data.user_name) {
                            sessionStorage.setItem('user_name', data.user_name);
                        }
                        if (data.email) {
                            sessionStorage.setItem('user_email', data.email);
                        }
                        
                        Swal.fire({
                            icon: 'success',
                            title: 'Login Successful!',
                            text: 'Welcome, ' + (data.user_name || 'User'),
                            showConfirmButton: false,
                            timer: 1500
                        }).then(() => {
                            window.location.href = '../index.php';
                        });
                    } else {
                        emailOtpLoginModalErrorText.textContent = data.message || 'Invalid verification code.';
                        emailOtpLoginModalErrorMessage.style.display = 'flex';
                    }
                    
                    setEmailOtpLoginLoading(false);
                } catch (error) {
                    console.error('Email OTP verify error:', error);
                    emailOtpLoginModalErrorText.textContent = 'A connection error occurred. Please try again.';
                    emailOtpLoginModalErrorMessage.style.display = 'flex';
                    setEmailOtpLoginLoading(false);
                }
            });
        }
        
        function setEmailOtpLoginLoading(isLoading) {
            const btn = emailOtpLoginVerifyButton;
            if (isLoading) {
                btn.disabled = true;
                btn.querySelector('.btn-text').style.display = 'none';
                btn.querySelector('.btn-spinner').style.display = 'inline-block';
            } else {
                btn.disabled = false;
                btn.querySelector('.btn-text').style.display = 'inline';
                btn.querySelector('.btn-spinner').style.display = 'none';
            }
        }

        // Phone OTP Login Modal Functions
        const otpLoginModal = document.getElementById('otpLoginModal');
        const otpLoginModalForm = document.getElementById('otpLoginModalForm');
        const otpLoginVerifyButton = document.getElementById('otpLoginVerifyButton');
        const otpLoginModalErrorMessage = document.getElementById('otpLoginModalErrorMessage');
        const otpLoginModalErrorText = document.getElementById('otpLoginModalErrorText');
        const otpLoginResendButton = document.getElementById('otpLoginResendButton');
        const otpLoginBackButton = document.getElementById('otpLoginBackButton');
        const otpLoginModalClose = document.getElementById('otpLoginModalClose');

        function openOtpLoginModal() {
            otpLoginModal.style.display = 'flex';
            otpLoginModal.setAttribute('aria-hidden', 'false');
            document.getElementById('otp_login_code').value = '';
            document.getElementById('otp_login_code').focus();
        }

        function closeOtpLoginModal() {
            otpLoginModal.style.display = 'none';
            otpLoginModal.setAttribute('aria-hidden', 'true');
            otpLoginModalErrorMessage.style.display = 'none';
        }

        // Close modal buttons
        if (otpLoginModalClose) otpLoginModalClose.addEventListener('click', closeOtpLoginModal);
        if (otpLoginBackButton) otpLoginBackButton.addEventListener('click', function() {
            closeOtpLoginModal();
            phoneOtpForm.style.display = 'none';
            loginForm.style.display = 'block';
        });

        // Resend cooldown timer
        let otpResendCountdownTimer = null;
        function startOtpResendCooldown(seconds) {
            if (otpResendCountdownTimer) clearInterval(otpResendCountdownTimer);
            let remaining = seconds;
            otpLoginResendButton.disabled = true;
            otpLoginResendButton.innerHTML = `<i class="fas fa-redo"></i> Resend Code (${remaining}s)`;

            otpResendCountdownTimer = setInterval(() => {
                remaining--;
                if (remaining <= 0) {
                    clearInterval(otpResendCountdownTimer);
                    otpLoginResendButton.disabled = false;
                    otpLoginResendButton.innerHTML = '<i class="fas fa-redo"></i> Resend Code';
                } else {
                    otpLoginResendButton.innerHTML = `<i class="fas fa-redo"></i> Resend Code (${remaining}s)`;
                }
            }, 1000);
        }

        // Resend OTP
        if (otpLoginResendButton) {
            otpLoginResendButton.addEventListener('click', async function() {
                if (otpLoginResendButton.disabled) return;
                
                const phone = window.otpPhoneNumber;
                if (!phone) {
                    Swal.fire({ icon: 'error', title: 'Error', text: 'Phone number not found.' });
                    return;
                }
                
                try {
                    const response = await fetch('api/send-otp.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ phone: phone, name: 'User' })
                    });
                    
                    const data = await response.json();
                    if (data.success) {
                        Swal.fire({ icon: 'success', title: 'Code Resent', text: 'A new verification code has been sent to your phone.', timer: 1500, showConfirmButton: false });
                        startOtpResendCooldown(60);
                    } else {
                        Swal.fire({ icon: 'error', title: 'Error', text: data.message || 'Failed to resend code.' });
                    }
                } catch (error) {
                    console.error('Resend OTP error:', error);
                    Swal.fire({ icon: 'error', title: 'Error', text: 'A connection error occurred.' });
                }
            });
        }

        // Verify OTP and Login
        if (otpLoginModalForm) {
            otpLoginModalForm.addEventListener('submit', async function(e) {
                e.preventDefault();
                
                const otp = document.getElementById('otp_login_code').value.trim();
                
                if (!otp || otp.length !== 6) {
                    otpLoginModalErrorText.textContent = 'Please enter a valid 6-digit code.';
                    otpLoginModalErrorMessage.style.display = 'flex';
                    return;
                }
                
                setOtpLoginLoading(true);
                
                try {
                    const response = await fetch('api/verify-otp.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ otp: otp })
                    });
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        // Store user info in sessionStorage for Firebase chat
                        if (data.user_id) {
                            sessionStorage.setItem('user_id', data.user_id);
                        }
                        if (data.user_name) {
                            sessionStorage.setItem('user_name', data.user_name);
                        }
                        if (data.phone) {
                            sessionStorage.setItem('user_phone', data.phone);
                        }
                        
                        Swal.fire({
                            icon: 'success',
                            title: 'Login Successful!',
                            text: 'Welcome, ' + (data.user_name || 'User'),
                            showConfirmButton: false,
                            timer: 1500
                        }).then(() => {
                            window.location.href = '../index.php';
                        });
                    } else {
                        otpLoginModalErrorText.textContent = data.message || 'Invalid verification code.';
                        otpLoginModalErrorMessage.style.display = 'flex';
                    }
                    
                    setOtpLoginLoading(false);
                } catch (error) {
                    console.error('OTP verify error:', error);
                    otpLoginModalErrorText.textContent = 'A connection error occurred. Please try again.';
                    otpLoginModalErrorMessage.style.display = 'flex';
                    setOtpLoginLoading(false);
                }
            });
        }
        
        function setOtpLoginLoading(isLoading) {
            const btn = otpLoginVerifyButton;
            if (isLoading) {
                btn.disabled = true;
                btn.querySelector('.btn-text').style.display = 'none';
                btn.querySelector('.btn-spinner').style.display = 'inline-block';
            } else {
                btn.disabled = false;
                btn.querySelector('.btn-text').style.display = 'inline';
                btn.querySelector('.btn-spinner').style.display = 'none';
            }
        }

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
                    // Store user info in sessionStorage for Firebase chat
                    if (data.user_id) {
                        sessionStorage.setItem('user_id', data.user_id);
                    }
                    if (data.user_name || fullName) {
                        sessionStorage.setItem('user_name', data.user_name || fullName);
                    }
                    if (data.phone) {
                        sessionStorage.setItem('user_phone', data.phone);
                    }
                    
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

        // Google OAuth Login
        (function() {
            let googleClientId = null;
            let googleApiLoaded = false;
            let initializationAttempts = 0;
            const maxInitializationAttempts = 50; // 5 seconds max wait time
            let clickHandlerAttached = false;

            // Wait for DOM to be ready
            function init() {
                console.log('Initializing Google OAuth...');
                const googleLoginBtn = document.getElementById('googleLoginBtn');
                if (!googleLoginBtn) {
                    console.error('Google login button not found. Retrying...');
                    setTimeout(init, 200);
                    return;
                }
                
                console.log('Google login button found');

                // Load Google Client ID
                console.log('Fetching Google config...');
                fetch('api/get-google-config.php')
                    .then(res => {
                        if (!res.ok) {
                            throw new Error('Failed to fetch Google config: HTTP ' + res.status);
                        }
                        return res.json();
                    })
                    .then(data => {
                        console.log('Google config response:', data);
                        console.log('Response type:', typeof data);
                        console.log('Has success:', data && data.success);
                        console.log('Has client_id:', data && data.client_id);
                        
                        if (data && data.success === true && data.client_id) {
                            googleClientId = data.client_id;
                            window.googleClientId = googleClientId; // Make available globally for debugging
                            console.log('Google Client ID loaded successfully:', googleClientId.substring(0, 20) + '...');
                            initializeGoogleLogin();
                        } else {
                            console.error('Google Client ID not found in config. Response:', JSON.stringify(data, null, 2));
                            const errorMsg = data && data.message ? data.message : 'Google OAuth is not configured.';
                            console.error('Error message:', errorMsg);
                            showGoogleButtonError(errorMsg);
                            
                            // Show user-friendly message
                            if (data && data.debug) {
                                console.warn('Debug info:', data.debug);
                            }
                        }
                    })
                    .catch(err => {
                        console.error('Failed to load Google config:', err);
                        showGoogleButtonError('Unable to load Google login. Please use the regular login form.');
                    });
            }

            // Check if Google API is loaded
            function checkGoogleApiLoaded() {
                if (typeof google !== 'undefined' && google.accounts && google.accounts.oauth2) {
                    googleApiLoaded = true;
                    return true;
                }
                return false;
            }

            function initializeGoogleLogin() {
                if (!googleClientId) {
                    console.error('Google Client ID not loaded');
                    return;
                }

                const googleLoginBtn = document.getElementById('googleLoginBtn');
                if (!googleLoginBtn) {
                    console.error('Google login button not found');
                    return;
                }

                // Attach click handler only once
                if (!clickHandlerAttached) {
                    console.log('Attaching click handler to Google login button');
                    // Remove any existing listeners by cloning the button
                    const newBtn = googleLoginBtn.cloneNode(true);
                    googleLoginBtn.parentNode.replaceChild(newBtn, googleLoginBtn);
                    
                    newBtn.addEventListener('click', function(e) {
                        e.preventDefault();
                        e.stopPropagation();
                        console.log('Google login button clicked');
                        
                        if (!googleClientId) {
                            console.error('Google Client ID not available');
                            Swal.fire({
                                icon: 'error',
                                title: 'Configuration Error',
                                text: 'Google login is not properly configured. Please use the regular login form.'
                            });
                            return;
                        }

                        if (!checkGoogleApiLoaded()) {
                            console.warn('Google API not loaded yet');
                            Swal.fire({
                                icon: 'info',
                                title: 'Loading...',
                                text: 'Google login service is still loading. Please wait a moment and try again.',
                                timer: 2000
                            });
                            // Retry initialization
                            setTimeout(initializeGoogleLogin, 500);
                            return;
                        }
                        
                        console.log('Initializing Google OAuth token client...');
                        try {
                            // Use Google Identity Services OAuth 2.0
                            const tokenClient = google.accounts.oauth2.initTokenClient({
                                client_id: googleClientId,
                                scope: 'email profile',
                                callback: handleGoogleTokenResponse,
                            });
                            
                            console.log('Requesting access token...');
                            tokenClient.requestAccessToken({ prompt: 'consent' });
                        } catch (error) {
                            console.error('Error initializing Google OAuth:', error);
                            Swal.fire({
                                icon: 'error',
                                title: 'Authentication Error',
                                text: 'Failed to start Google login: ' + error.message
                            });
                        }
                    });
                    clickHandlerAttached = true;
                    console.log('Click handler attached successfully');
                }

                // Wait for Google Identity Services to load
                if (!checkGoogleApiLoaded()) {
                    initializationAttempts++;
                    console.log('Waiting for Google API to load... Attempt ' + initializationAttempts);
                    if (initializationAttempts < maxInitializationAttempts) {
                        setTimeout(initializeGoogleLogin, 100);
                    } else {
                        console.error('Google Identity Services failed to load after maximum attempts');
                        showGoogleButtonError('Google login service is taking too long to load. Please refresh the page or use the regular login form.');
                    }
                    return;
                }

                console.log('Google API loaded successfully');
                // Remove any error styling
                const btn = document.getElementById('googleLoginBtn');
                if (btn) {
                    btn.style.opacity = '1';
                    btn.style.cursor = 'pointer';
                    btn.disabled = false;
                    console.log('Google login button initialized successfully');
                }
            }

            function showGoogleButtonError(message) {
                const googleLoginBtn = document.getElementById('googleLoginBtn');
                if (googleLoginBtn) {
                    googleLoginBtn.style.opacity = '0.6';
                    googleLoginBtn.style.cursor = 'pointer'; // Keep pointer so user can click
                    googleLoginBtn.disabled = false; // Don't disable, allow click to show setup message
                    googleLoginBtn.title = message + ' Click to setup.';
                    
                    // Add click handler to show setup instructions
                    googleLoginBtn.addEventListener('click', function(e) {
                        e.preventDefault();
                        Swal.fire({
                            icon: 'info',
                            title: 'Google OAuth Not Configured',
                            html: `
                                <p>Google login is not configured yet.</p>
                                <p><strong>To enable Google login:</strong></p>
                                <ol style="text-align: left; margin: 1rem 0;">
                                    <li>Get Google OAuth credentials from <a href="https://console.cloud.google.com/" target="_blank">Google Cloud Console</a></li>
                                    <li>Configure them using the setup page</li>
                                </ol>
                                <a href="api/setup-google-oauth.php" target="_blank" style="display: inline-block; margin-top: 1rem; padding: 0.75rem 1.5rem; background: #4285f4; color: white; text-decoration: none; border-radius: 5px; font-weight: bold;">Open Setup Page</a>
                            `,
                            confirmButtonText: 'OK',
                            width: '600px'
                        });
                    }, { once: true }); // Only attach once
                }
            }

            // Initialize when DOM is ready
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', function() {
                    setTimeout(init, 100); // Small delay to ensure everything is ready
                });
            } else {
                setTimeout(init, 100);
            }
        })();

        function handleGoogleTokenResponse(tokenResponse) {
            console.log('Google token response received:', tokenResponse);
            
            if (tokenResponse.error) {
                console.error('Google OAuth error:', tokenResponse.error);
                let errorMsg = 'Failed to authenticate with Google. Please try again.';
                
                if (tokenResponse.error === 'popup_closed_by_user') {
                    errorMsg = 'Authentication cancelled.';
                } else if (tokenResponse.error_description) {
                    errorMsg = tokenResponse.error_description;
                }
                
                Swal.fire({
                    icon: 'error',
                    title: 'Authentication Error',
                    text: errorMsg
                });
                return;
            }

            if (!tokenResponse.access_token) {
                console.error('No access token in response');
                Swal.fire({
                    icon: 'error',
                    title: 'Authentication Error',
                    text: 'No access token received from Google.'
                });
                return;
            }

            console.log('Fetching user info from Google...');
            fetch('https://www.googleapis.com/oauth2/v2/userinfo?access_token=' + tokenResponse.access_token)
                .then(res => {
                    if (!res.ok) {
                        throw new Error('Failed to fetch user info: HTTP ' + res.status);
                    }
                    return res.json();
                })
                .then(userInfo => {
                    console.log('User info received:', userInfo);
                    if (!userInfo || !userInfo.email) {
                        throw new Error('Invalid user info received from Google');
                    }
                    console.log('Verifying user with backend...');
                    verifyGoogleUser(userInfo);
                })
                .catch(err => {
                    console.error('Google token error:', err);
                    Swal.fire({
                        icon: 'error',
                        title: 'Authentication Error',
                        text: 'Failed to authenticate with Google: ' + (err.message || 'Unknown error')
                    });
                });
        }

        async function verifyGoogleUser(userInfo) {
            try {
                console.log('Sending user info to backend for verification...');
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

                console.log('Backend response status:', response.status);
                if (!response.ok) {
                    const errorText = await response.text();
                    // Escape HTML to prevent syntax errors
                    const safeErrorText = errorText.replace(/</g, '&lt;').replace(/>/g, '&gt;');
                    console.error('Backend error:', safeErrorText);
                    throw new Error('Server error: HTTP ' + response.status + ' - ' + safeErrorText);
                }

                const data = await response.json();
                console.log('Backend response:', data);

                if (data.success) {
                    // Store user info in sessionStorage for Firebase chat
                    if (data.user_id) {
                        sessionStorage.setItem('user_id', data.user_id);
                    }
                    if (data.user_name || data.username) {
                        sessionStorage.setItem('user_name', data.user_name || data.username);
                    }
                    if (data.email) {
                        sessionStorage.setItem('user_email', data.email);
                    }
                    if (data.phone) {
                        sessionStorage.setItem('user_phone', data.phone);
                    }
                    
                    Swal.fire({
                        icon: 'success',
                        title: data.is_new_user ? 'Account Created!' : 'Login Successful!',
                        text: 'Welcome, ' + (data.user_name || data.username || 'User'),
                        showConfirmButton: false,
                        timer: 1500
                    }).then(() => {
                        console.log('Redirecting to index.php...');
                        window.location.href = '../index.php';
                    });
                } else {
                    console.error('Backend returned error:', data.message);
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
                    text: 'Please check your internet connection and try again. Error: ' + error.message
                });
            }
        }
        
        // Debug: Check if button exists after page load
        window.addEventListener('load', function() {
            setTimeout(function() {
                const btn = document.getElementById('googleLoginBtn');
                console.log('Page loaded. Google login button exists:', !!btn);
                if (btn) {
                    console.log('Button disabled:', btn.disabled);
                    console.log('Button opacity:', btn.style.opacity);
                }
            }, 1000);
        });
    </script>

    

</body>
</html>


