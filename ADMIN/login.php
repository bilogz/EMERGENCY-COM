<?php
/**
 * Admin Login Page
 * Emergency Communication System - Admin Panel Login
 */

// Security Headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Permissions-Policy: geolocation=(), microphone=(), camera=()');

// Load secure configuration for keys
require_once __DIR__ . '/api/config.env.php';
$recaptchaSiteKey = getSecureConfig('RECAPTCHA_SITE_KEY', '');
$adminApiKey = getSecureConfig('ADMIN_API_KEY', '');
$requireOtp = filter_var(getSecureConfig('ADMIN_REQUIRE_OTP', true), FILTER_VALIDATE_BOOLEAN);

// Check if user is already logged in
session_start();

// Regenerate session ID for security
if (!isset($_SESSION['created'])) {
    session_regenerate_id(true);
    $_SESSION['created'] = true;
}

if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: sidebar/dashboard.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="robots" content="noindex, nofollow">
    <meta name="referrer" content="strict-origin-when-cross-origin">
    <meta http-equiv="Content-Security-Policy" content="default-src 'self'; script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://www.google.com https://www.gstatic.com; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://www.google.com; font-src 'self' https://cdnjs.cloudflare.com; img-src 'self' data: https://www.google.com https://www.gstatic.com; frame-src https://www.google.com; connect-src 'self' https://www.google.com https://www.gstatic.com;">
    <title>Admin Login - Emergency Communication System</title>
    <link rel="icon" type="image/x-icon" href="sidebar/images/favicon.ico">
    <link rel="stylesheet" href="sidebar/css/global.css">
    <link rel="stylesheet" href="sidebar/css/forms.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <?php if (!empty($recaptchaSiteKey)): ?>
        <script src="https://www.google.com/recaptcha/api.js?render=<?php echo htmlspecialchars($recaptchaSiteKey, ENT_QUOTES, 'UTF-8'); ?>"></script>
    <?php endif; ?>
    <script>
        // Immediate reset check (runs before page loads)
        (function() {
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('reset') === 'attempts') {
                localStorage.removeItem('admin_login_attempts');
                localStorage.removeItem('admin_account_locked');
                localStorage.removeItem('admin_lockout_time');
                console.log('Login attempts reset immediately');
                // Remove the parameter from URL and reload
                window.location.href = window.location.pathname;
            }
        })();
    </script>
    <style>
        /* Login Page Specific Styles */
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            background: var(--bg-color-1);
            padding: 1rem;
        }
        
        .login-container {
            width: 100%;
            max-width: 450px;
            background: var(--card-bg-1);
            border-radius: 16px;
            border: 1px solid var(--border-color-1);
            box-shadow: 0 8px 24px var(--shadow-1);
            padding: 3rem 2.5rem;
            animation: fadeInUp 0.5s ease;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .login-header {
            text-align: center;
            margin-bottom: 2.5rem;
        }

        .login-logo {
            width: 80px;
            height: 80px;
            margin: 0 auto 1.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--primary-color-1);
            border-radius: 16px;
            box-shadow: 0 4px 12px rgba(76, 138, 137, 0.3);
        }

        .login-logo img {
            width: 60px;
            height: 60px;
            object-fit: contain;
        }

        .login-logo i {
            font-size: 2.5rem;
            color: white;
        }

        .login-title {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--text-color-1);
            margin-bottom: 0.5rem;
        }

        .login-subtitle {
            font-size: 0.95rem;
            color: var(--text-secondary-1);
            line-height: 1.5;
        }

        .login-form {
            margin-top: 2rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
            position: relative;
        }

        .form-label {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 0.75rem;
            font-weight: 500;
            color: var(--text-color-1);
            font-size: 14px;
        }

        .form-label i {
            color: var(--primary-color-1);
            font-size: 16px;
        }

        .form-control {
            width: 100%;
            padding: 0.875rem 1rem;
            padding-left: 2.75rem;
            border: 2px solid var(--border-color-1);
            border-radius: 8px;
            font-size: 14px;
            font-family: var(--font-family-1);
            background-color: var(--bg-color-1);
            color: var(--text-color-1);
            transition: all 0.3s ease;
            outline: none;
        }

        .form-control:focus {
            border-color: var(--primary-color-1);
            box-shadow: 0 0 0 3px rgba(76, 138, 137, 0.1);
        }

        .form-control::placeholder {
            color: var(--text-secondary-1);
            opacity: 0.6;
        }

        .input-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-secondary-1);
            font-size: 16px;
            pointer-events: none;
            transition: color 0.3s ease;
        }

        .form-control:focus + .input-icon,
        .form-group:has(.form-control:focus) .input-icon {
            color: var(--primary-color-1);
        }

        .password-toggle {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--text-secondary-1);
            cursor: pointer;
            font-size: 16px;
            padding: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: color 0.3s ease;
        }

        .password-toggle:hover {
            color: var(--primary-color-1);
        }

        .form-options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            font-size: 13px;
        }

        .remember-me {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            cursor: pointer;
        }

        .remember-me input[type="checkbox"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
            accent-color: var(--primary-color-1);
        }

        .remember-me label {
            cursor: pointer;
            color: var(--text-color-1);
            user-select: none;
        }

        .forgot-password {
            color: var(--primary-color-1);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .forgot-password:hover {
            color: #4ca8a6;
            text-decoration: underline;
        }

        .btn-login {
            width: 100%;
            padding: 0.875rem 1.5rem;
            background-color: var(--primary-color-1);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            margin-top: 1rem;
        }

        .btn-login:hover {
            background-color: #4ca8a6;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(76, 138, 137, 0.3);
        }

        .btn-login:active {
            transform: translateY(0);
        }

        .btn-login:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        .btn-login i {
            font-size: 16px;
        }

        .error-message {
            background-color: rgba(220, 53, 69, 0.1);
            border: 1px solid rgba(220, 53, 69, 0.3);
            color: #dc3545;
            padding: 0.875rem 1rem;
            border-radius: 8px;
            font-size: 13px;
            margin-bottom: 1.5rem;
            display: none;
            align-items: center;
            gap: 0.75rem;
        }

        .error-message.show {
            display: flex;
        }

        .error-message i {
            font-size: 16px;
        }

        .login-footer {
            text-align: center;
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid var(--border-color-1);
        }

        .login-footer-text {
            color: var(--text-secondary-1);
            font-size: 13px;
        }

        .login-footer-link {
            color: var(--primary-color-1);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .login-footer-link:hover {
            color: #4ca8a6;
            text-decoration: underline;
        }

        /* Security Features */
        .security-badge {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.75rem 1rem;
            background: rgba(76, 138, 137, 0.1);
            border: 1px solid rgba(76, 138, 137, 0.3);
            border-radius: 8px;
            margin-bottom: 1.5rem;
            font-size: 12px;
            color: var(--text-color-1);
        }

        .security-badge i {
            color: var(--primary-color-1);
            font-size: 14px;
        }

        .security-warning {
            background-color: rgba(255, 193, 7, 0.1);
            border: 1px solid rgba(255, 193, 7, 0.3);
            color: #856404;
            padding: 0.75rem 1rem;
            border-radius: 8px;
            font-size: 12px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .security-warning i {
            font-size: 14px;
            flex-shrink: 0;
        }

        .security-warning.show {
            display: flex;
        }

        .security-warning.hidden {
            display: none;
        }

        .rate-limit-warning {
            background-color: rgba(220, 53, 69, 0.1);
            border: 1px solid rgba(220, 53, 69, 0.3);
            color: #721c24;
            padding: 0.875rem 1rem;
            border-radius: 8px;
            font-size: 13px;
            margin-bottom: 1.5rem;
            display: none;
            align-items: center;
            gap: 0.75rem;
        }

        .rate-limit-warning.show {
            display: flex;
        }

        .rate-limit-warning i {
            font-size: 16px;
        }

        .attempt-counter {
            font-weight: 600;
            color: #dc3545;
        }

        /* reCAPTCHA v3 is invisible - no visible widget needed */
        .grecaptcha-badge {
            visibility: hidden !important;
        }

        .login-info {
            background: rgba(76, 138, 137, 0.05);
            border-left: 3px solid var(--primary-color-1);
            padding: 0.75rem 1rem;
            margin-bottom: 1.5rem;
            font-size: 12px;
            color: var(--text-secondary-1);
            line-height: 1.6;
        }

        .login-info i {
            color: var(--primary-color-1);
            margin-right: 0.5rem;
        }

        .account-locked {
            background-color: rgba(220, 53, 69, 0.1);
            border: 1px solid rgba(220, 53, 69, 0.3);
            color: #721c24;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            text-align: center;
            font-size: 13px;
            display: none;
        }

        .account-locked.show {
            display: block;
        }

        .account-locked i {
            font-size: 24px;
            margin-bottom: 0.5rem;
            display: block;
        }

        .lockout-timer {
            font-weight: 700;
            color: #dc3545;
            font-size: 16px;
        }

        /* Loading Spinner */
        .spinner {
            display: none;
            width: 18px;
            height: 18px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-top-color: white;
            border-radius: 50%;
            animation: spin 0.6s linear infinite;
        }

        .btn-login.loading .spinner {
            display: block;
        }

        .btn-login.loading .btn-text {
            display: none;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Responsive Design */
        @media (max-width: 480px) {
            .login-container {
                padding: 2rem 1.5rem;
            }

            .login-title {
                font-size: 1.5rem;
            }

            .login-logo {
                width: 70px;
                height: 70px;
            }

            .login-logo img,
            .login-logo i {
                width: 50px;
                height: 50px;
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <div class="login-logo">
                <img src="sidebar/images/logo.svg" alt="Logo" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                <i class="fas fa-shield-alt" style="display: none;"></i>
            </div>
            <h1 class="login-title">Admin Login</h1>
            <p class="login-subtitle">Emergency Communication System<br>Administrative Panel</p>
        </div>

        <!-- Security Badge -->
        <div class="security-badge">
            <i class="fas fa-shield-alt"></i>
            <span>Secure Admin Access Only</span>
            <i class="fas fa-lock"></i>
        </div>

        <!-- Security Warning -->
        <div class="security-warning" id="securityWarning">
            <i class="fas fa-exclamation-triangle"></i>
            <span>Unauthorized access is strictly prohibited. All login attempts are monitored and logged.</span>
        </div>

        <!-- Rate Limit Warning -->
        <div class="rate-limit-warning" id="rateLimitWarning">
            <i class="fas fa-ban"></i>
            <span>Multiple failed login attempts detected. <span class="attempt-counter" id="attemptCount"></span> attempt(s) remaining. Account will be temporarily locked after 5 failed attempts.</span>
        </div>

        <!-- Account Locked Warning -->
        <div class="account-locked" id="accountLocked">
            <i class="fas fa-lock"></i>
            <div><strong>Account Temporarily Locked</strong></div>
            <div>Too many failed login attempts. Please try again in <span class="lockout-timer" id="lockoutTimer">15:00</span> minutes.</div>
        </div>

        <!-- Login Info -->
        <div class="login-info">
            <i class="fas fa-info-circle"></i>
            <strong>Security Notice:</strong> This is a restricted administrative area. Access is logged and monitored. Ensure you are using a secure connection.
        </div>

        <div class="error-message" id="errorMessage">
            <i class="fas fa-exclamation-circle"></i>
            <span id="errorText"></span>
        </div>

        <form class="login-form" id="loginForm">
            <div class="form-group">
                <label for="email" class="form-label">
                    <i class="fas fa-envelope"></i>
                    Email Address
                </label>
                <input 
                    type="email" 
                    id="email" 
                    name="email" 
                    class="form-control" 
                    placeholder="admin@example.com" 
                    required 
                    autocomplete="email"
                >
                <i class="fas fa-envelope input-icon"></i>
            </div>

            <div class="form-group">
                <label for="password" class="form-label">
                    <i class="fas fa-lock"></i>
                    Password
                </label>
                <input 
                    type="password" 
                    id="password" 
                    name="password" 
                    class="form-control" 
                    placeholder="Enter your password" 
                    required 
                    autocomplete="current-password"
                >
                <i class="fas fa-lock input-icon"></i>
                <button type="button" class="password-toggle" id="passwordToggle" aria-label="Toggle password visibility">
                    <i class="fas fa-eye"></i>
                </button>
            </div>

            <!-- Google reCAPTCHA v3 (invisible - no checkbox needed) -->
            <input type="hidden" id="recaptchaResponse" name="recaptcha_response">
            <div id="recaptcha-error" style="display: none; color: #dc3545; font-size: 13px; text-align: center; padding: 10px; margin-bottom: 1rem;">
                <i class="fas fa-exclamation-triangle"></i> Security verification failed. Please refresh the page.
            </div>

            <div class="form-options">
                <label class="remember-me">
                    <input type="checkbox" id="rememberMe" name="rememberMe">
                    <label for="rememberMe">Remember me</label>
                </label>
                <a href="#" class="forgot-password" id="forgotPassword">Forgot Password?</a>
            </div>

            <button type="submit" class="btn-login" id="loginButton">
                <div class="spinner"></div>
                <span class="btn-text">
                    <i class="fas fa-sign-in-alt"></i>
                    Sign In
                </span>
            </button>
        </form>

        <div class="login-footer">
            <p class="login-footer-text">
                Need help? <a href="#" class="login-footer-link">Contact Support</a>
            </p>
        </div>
    </div>

    <!-- OTP Verification Modal -->
    <div id="otpModal" class="otp-modal" style="display: none;">
        <div class="otp-modal-content">
            <button class="otp-modal-close" id="otpModalClose">&times;</button>
            <h3>Verify Your Email</h3>
            <p style="color: var(--text-secondary-1); margin-bottom: 1.5rem;">
                We've sent a 6-digit verification code to <strong id="otpEmailDisplay"></strong>
            </p>
            
            <div id="otpSentBanner" style="display:none; margin-bottom:1rem; padding:0.75rem; border-radius:6px; background: rgba(40,167,69,0.12); border:1px solid rgba(40,167,69,0.2); color: #28a745;">
                <i class="fas fa-check-circle"></i> Verification code sent successfully.
            </div>
            <div id="otpWarnBanner" style="display:none; margin-bottom:1rem; padding:0.75rem; border-radius:6px; background: rgba(255,193,7,0.12); border:1px solid rgba(255,193,7,0.2); color: #856404;">
                <i class="fas fa-exclamation-triangle"></i> Email delivery failed. Use the debug code below for testing.
            </div>
            <div id="otpDebugCode" style="display:none; margin-bottom:1rem; padding:1rem; background: #fffacd; border:2px solid #ffd700; border-radius:6px; font-weight:700; text-align:center; font-size:1.2rem; color: #d4941e;"></div>

            <form id="otpModalForm">
                <div class="form-group">
                    <label for="otp" class="form-label"><i class="fas fa-key"></i> Verification Code</label>
                    <input type="text" id="otp" name="otp" class="form-control" placeholder="Enter 6-digit code" maxlength="6" pattern="[0-9]{6}" required autocomplete="one-time-code" style="text-align: center; font-size: 1.5rem; letter-spacing: 0.5rem;">
                    <small class="form-text" style="margin-top: 0.25rem; font-size: 12px; color: var(--text-secondary-1);">Enter the 6-digit code sent to your email</small>
                </div>

                <div class="error-message" id="otpModalErrorMessage" style="display: none;">
                    <i class="fas fa-exclamation-circle"></i>
                    <span id="otpModalErrorText"></span>
                </div>

                <button type="submit" class="btn-login" id="otpVerifyButton">
                    <i class="fas fa-check"></i>
                    <span>Verify Code</span>
                </button>
                <div style="text-align: center; margin-top: 1rem;">
                    <button type="button" class="login-footer-link" id="otpResendButton" style="background: none; border: none; cursor: pointer;">Resend Code</button>
                </div>
            </form>
        </div>
    </div>

    <style>
        /* OTP Modal Styles */
        .otp-modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 10000;
        }
        .otp-modal-content {
            background: var(--card-bg-1);
            border-radius: 16px;
            padding: 2.5rem;
            max-width: 450px;
            width: 90%;
            position: relative;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.3);
        }
        .otp-modal-close {
            position: absolute;
            top: 1rem;
            right: 1rem;
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: var(--text-secondary-1);
            padding: 0.5rem;
            line-height: 1;
        }
        .otp-modal-close:hover {
            color: var(--text-color-1);
        }
        .otp-modal-content h3 {
            margin-top: 0;
            margin-bottom: 0.5rem;
            color: var(--text-color-1);
        }
    </style>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // Runtime configuration injected from server
        const RECAPTCHA_SITE_KEY = '<?php echo htmlspecialchars($recaptchaSiteKey, ENT_QUOTES, 'UTF-8'); ?>';
        const ADMIN_API_KEY = '<?php echo htmlspecialchars($adminApiKey, ENT_QUOTES, 'UTF-8'); ?>';
        const REQUIRE_OTP = <?php echo $requireOtp ? 'true' : 'false'; ?>;

        // Security Configuration
        const SECURITY_CONFIG = {
            MAX_ATTEMPTS: 5,
            LOCKOUT_DURATION: 15 * 60 * 1000, // 15 minutes in milliseconds
            CAPTCHA_REQUIRED_AFTER: 2, // Show CAPTCHA after 2 failed attempts
            STORAGE_KEY: 'admin_login_attempts',
            LOCKOUT_KEY: 'admin_account_locked',
            LOCKOUT_TIME_KEY: 'admin_lockout_time'
        };

        // Security Functions
        function getLoginAttempts() {
            const attempts = localStorage.getItem(SECURITY_CONFIG.STORAGE_KEY);
            return attempts ? parseInt(attempts) : 0;
        }

        function incrementLoginAttempts() {
            const attempts = getLoginAttempts() + 1;
            localStorage.setItem(SECURITY_CONFIG.STORAGE_KEY, attempts.toString());
            return attempts;
        }

        function resetLoginAttempts() {
            localStorage.removeItem(SECURITY_CONFIG.STORAGE_KEY);
        }
        
        // Check URL for reset parameter (for testing/admin use)
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('reset') === 'attempts') {
            localStorage.removeItem(SECURITY_CONFIG.STORAGE_KEY);
            localStorage.removeItem(SECURITY_CONFIG.LOCKOUT_KEY);
            localStorage.removeItem(SECURITY_CONFIG.LOCKOUT_TIME_KEY);
            console.log('Login attempts reset');
            // Remove the parameter from URL
            window.history.replaceState({}, document.title, window.location.pathname);
            alert('Login attempts have been reset. You can try logging in again.');
        }

        function isAccountLocked() {
            const lockoutTime = localStorage.getItem(SECURITY_CONFIG.LOCKOUT_TIME_KEY);
            if (!lockoutTime) return false;
            
            const now = Date.now();
            const lockoutEnd = parseInt(lockoutTime);
            
            if (now < lockoutEnd) {
                return true;
            } else {
                // Lockout expired, clear it
                localStorage.removeItem(SECURITY_CONFIG.LOCKOUT_TIME_KEY);
                localStorage.removeItem(SECURITY_CONFIG.LOCKOUT_KEY);
                resetLoginAttempts();
                return false;
            }
        }

        function lockAccount() {
            const lockoutEnd = Date.now() + SECURITY_CONFIG.LOCKOUT_DURATION;
            localStorage.setItem(SECURITY_CONFIG.LOCKOUT_TIME_KEY, lockoutEnd.toString());
            localStorage.setItem(SECURITY_CONFIG.LOCKOUT_KEY, 'true');
        }

        function updateLockoutTimer() {
            const lockoutTime = localStorage.getItem(SECURITY_CONFIG.LOCKOUT_TIME_KEY);
            if (!lockoutTime) {
                document.getElementById('accountLocked').classList.remove('show');
                return;
            }
            
            const now = Date.now();
            const lockoutEnd = parseInt(lockoutTime);
            const remaining = Math.max(0, lockoutEnd - now);
            
            if (remaining > 0) {
                const minutes = Math.floor(remaining / 60000);
                const seconds = Math.floor((remaining % 60000) / 1000);
                document.getElementById('lockoutTimer').textContent = 
                    `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
                document.getElementById('accountLocked').classList.add('show');
            } else {
                document.getElementById('accountLocked').classList.remove('show');
                localStorage.removeItem(SECURITY_CONFIG.LOCKOUT_TIME_KEY);
                localStorage.removeItem(SECURITY_CONFIG.LOCKOUT_KEY);
                resetLoginAttempts();
            }
        }

        function updateSecurityWarnings() {
            const attempts = getLoginAttempts();
            const remaining = SECURITY_CONFIG.MAX_ATTEMPTS - attempts;
            
            // CAPTCHA container is always visible (no need to add show class)
            // Just ensure it's displayed
            const captchaContainer = document.getElementById('captchaContainer');
            if (captchaContainer) {
                captchaContainer.style.display = 'flex';
            }
            
            if (attempts > 0 && attempts < SECURITY_CONFIG.MAX_ATTEMPTS) {
                document.getElementById('rateLimitWarning').classList.add('show');
                document.getElementById('attemptCount').textContent = remaining;
            } else {
                document.getElementById('rateLimitWarning').classList.remove('show');
            }
        }

        // Reset reCAPTCHA v3 (no-op for v3, tokens are single-use)
        function resetRecaptcha() {
            // reCAPTCHA v3 tokens are generated fresh each time, no reset needed
            console.log('reCAPTCHA v3 - new token will be generated on next submit');
        }

        // Build authenticated headers for admin APIs
        function buildApiHeaders() {
            const headers = { 'Content-Type': 'application/json' };
            if (ADMIN_API_KEY) {
                headers['X-Admin-Api-Key'] = ADMIN_API_KEY;
            }
            return headers;
        }

        // OTP Modal functions
        let pendingLoginData = null;
        let otpResendTimer = null;

        function openOtpModal(email, password, recaptchaResponse, rememberMe) {
            document.getElementById('otpModal').style.display = 'flex';
            document.getElementById('otpEmailDisplay').textContent = email;
            document.getElementById('otp').value = '';
            document.getElementById('otp').focus();
            document.getElementById('otpModalErrorMessage').style.display = 'none';
            
            // Store login data for later use
            pendingLoginData = {
                email: email,
                password: password,
                recaptcha_response: recaptchaResponse,
                rememberMe: rememberMe
            };
        }

        function closeOtpModal() {
            document.getElementById('otpModal').style.display = 'none';
            document.getElementById('otpModalErrorMessage').style.display = 'none';
            pendingLoginData = null;
        }

        function startResendCooldown(seconds) {
            if (otpResendTimer) clearInterval(otpResendTimer);
            const resendBtn = document.getElementById('otpResendButton');
            let remaining = seconds;
            resendBtn.disabled = true;
            resendBtn.textContent = `Resend Code (${remaining}s)`;

            otpResendTimer = setInterval(() => {
                remaining--;
                if (remaining > 0) {
                    resendBtn.textContent = `Resend Code (${remaining}s)`;
                } else {
                    clearInterval(otpResendTimer);
                    resendBtn.disabled = false;
                    resendBtn.textContent = 'Resend Code';
                }
            }, 1000);
        }

        // Password Toggle
        const passwordToggle = document.getElementById('passwordToggle');
        const passwordInput = document.getElementById('password');
        
        passwordToggle.addEventListener('click', function() {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            
            const icon = this.querySelector('i');
            icon.classList.toggle('fa-eye');
            icon.classList.toggle('fa-eye-slash');
        });

        // Form Submission
        const loginForm = document.getElementById('loginForm');
        const loginButton = document.getElementById('loginButton');
        const errorMessage = document.getElementById('errorMessage');
        const errorText = document.getElementById('errorText');

        function showError(message) {
            errorText.textContent = message;
            errorMessage.classList.add('show');
            setTimeout(() => {
                errorMessage.classList.remove('show');
            }, 5000);
        }

        function hideError() {
            errorMessage.classList.remove('show');
        }

        // Check account lockout status on page load
        if (isAccountLocked()) {
            updateLockoutTimer();
            setInterval(updateLockoutTimer, 1000);
        }
        
        // reCAPTCHA v3 configuration
        let recaptchaLoaded = false;

        // Ensure reCAPTCHA is ready on page load
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize security warnings
            updateSecurityWarnings();
            
            // Block login if captcha keys are not configured
            if (!RECAPTCHA_SITE_KEY) {
                const recaptchaError = document.getElementById('recaptcha-error');
                if (recaptchaError) {
                    recaptchaError.style.display = 'block';
                    recaptchaError.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Security verification is not configured. Please contact the administrator.';
                }
                loginButton.disabled = true;
                return;
            }
            
            // Check if reCAPTCHA loaded
            if (typeof grecaptcha !== 'undefined') {
                grecaptcha.ready(function() {
                    recaptchaLoaded = true;
                    console.log('reCAPTCHA v3 ready');
                });
            } else {
                // Check again after a delay
                setTimeout(function() {
                    if (typeof grecaptcha !== 'undefined') {
                        grecaptcha.ready(function() {
                            recaptchaLoaded = true;
                            console.log('reCAPTCHA v3 ready (delayed)');
                        });
                    } else {
                        console.warn('reCAPTCHA script not loaded');
                        document.getElementById('recaptcha-error').style.display = 'block';
                    }
                }, 3000);
            }
        });
        
        // Get reCAPTCHA v3 token
        async function getRecaptchaToken(action) {
            if (typeof grecaptcha === 'undefined') {
                console.error('reCAPTCHA not loaded');
                return '';
            }
            try {
                const token = await grecaptcha.execute(RECAPTCHA_SITE_KEY, { action: action });
                return token;
            } catch (error) {
                console.error('reCAPTCHA execute error:', error);
                return '';
            }
        }

        // Update lockout timer every second if locked
        setInterval(function() {
            if (isAccountLocked()) {
                updateLockoutTimer();
            }
        }, 1000);

        loginForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            hideError();

            // Check if account is locked
            if (isAccountLocked()) {
                updateLockoutTimer();
                showError('Account is temporarily locked due to multiple failed login attempts. Please try again later.');
                return;
            }

            const email = document.getElementById('email').value.trim();
            const password = document.getElementById('password').value;
            const rememberMe = document.getElementById('rememberMe').checked;
            const attempts = getLoginAttempts();

            // Validation
            if (!email || !password) {
                showError('Please fill in all fields.');
                return;
            }

            if (!RECAPTCHA_SITE_KEY) {
                showError('Security verification is not configured. Please contact your administrator.');
                return;
            }

            // reCAPTCHA v3 validation - get token
            if (!recaptchaLoaded) {
                showError('Security verification is loading. Please wait a moment and try again.');
                return;
            }
            
            // Get fresh reCAPTCHA v3 token
            let recaptchaResponse = '';
            try {
                recaptchaResponse = await getRecaptchaToken('admin_login');
            } catch (error) {
                console.error('Failed to get reCAPTCHA token:', error);
            }
            
            if (!recaptchaResponse) {
                showError('Security verification failed. Please refresh the page and try again.');
                return;
            }

            // Show loading state
            loginButton.classList.add('loading');
            loginButton.disabled = true;

            try {
                const response = await fetch('api/login-web.php', {
                    method: 'POST',
                    headers: buildApiHeaders(),
                    body: JSON.stringify({
                        email: email,
                        password: password,
                        recaptcha_response: recaptchaResponse
                    })
                });

                const data = await response.json();

                if (data.success) {
                    // Reset login attempts on successful login
                    resetLoginAttempts();
                    localStorage.removeItem(SECURITY_CONFIG.LOCKOUT_KEY);
                    localStorage.removeItem(SECURITY_CONFIG.LOCKOUT_TIME_KEY);
                    
                    // Store session data
                    if (rememberMe) {
                        localStorage.setItem('admin_remember', 'true');
                        localStorage.setItem('admin_email', email);
                    } else {
                        localStorage.removeItem('admin_remember');
                        localStorage.removeItem('admin_email');
                    }

                    // Log successful login attempt (for security monitoring)
                    console.log('Admin login successful:', email, new Date().toISOString());

                    // Show success message
                    Swal.fire({
                        icon: 'success',
                        title: 'Login Successful!',
                        text: 'Welcome back, ' + (data.username || 'Admin') + '!',
                        showConfirmButton: false,
                        timer: 1500
                    }).then(() => {
                        // Redirect to dashboard
                        window.location.href = 'sidebar/dashboard.php';
                    });
                } else if (data.requires_otp) {
                    // Credentials valid, but OTP verification required
                    loginButton.classList.remove('loading');
                    loginButton.disabled = false;
                    
                    // Send OTP to email
                    try {
                        const otpResponse = await fetch('api/send-admin-otp.php', {
                            method: 'POST',
                            headers: buildApiHeaders(),
                            body: JSON.stringify({
                                email: email,
                                name: data.username || 'Admin',
                                purpose: 'login'
                            })
                        });

                        const otpData = await otpResponse.json();

                        if (otpData.success) {
                            // Show debug OTP if email not sent
                            if (!otpData.otp_sent && otpData.debug_otp) {
                                document.getElementById('otpWarnBanner').style.display = 'block';
                                document.getElementById('otpDebugCode').textContent = otpData.debug_otp;
                                document.getElementById('otpDebugCode').style.display = 'block';
                            } else {
                                document.getElementById('otpSentBanner').style.display = 'block';
                            }

                            openOtpModal(email, password, recaptchaResponse, rememberMe);
                            startResendCooldown(60);
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: otpData.message || 'Failed to send verification code. Please try again.',
                                confirmButtonColor: '#dc3545'
                            });
                            resetRecaptcha();
                        }
                    } catch (error) {
                        console.error('Send OTP error:', error);
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'A connection error occurred. Please try again.',
                            confirmButtonColor: '#dc3545'
                        });
                        resetRecaptcha();
                    }
                } else {
                    // Increment failed login attempts
                    const newAttempts = incrementLoginAttempts();
                    
                    // Lock account if max attempts reached
                    if (newAttempts >= SECURITY_CONFIG.MAX_ATTEMPTS) {
                        lockAccount();
                        updateLockoutTimer();
                        showError('Too many failed login attempts. Your account has been temporarily locked for security reasons.');
                        Swal.fire({
                            icon: 'error',
                            title: 'Account Locked',
                            html: `Too many failed login attempts.<br>Your account has been temporarily locked for <strong>15 minutes</strong> for security reasons.<br><br>Please contact your system administrator if you believe this is an error.`,
                            confirmButtonText: 'OK',
                            confirmButtonColor: '#dc3545'
                        });
                    } else {
                        const remaining = SECURITY_CONFIG.MAX_ATTEMPTS - newAttempts;
                        updateSecurityWarnings();
                        
                        // Generic error message (don't reveal if email exists)
                        showError('Invalid credentials. Please check your email and password.');
                        
                        // Show warning if close to lockout
                        if (remaining <= 2) {
                            Swal.fire({
                                icon: 'warning',
                                title: 'Security Warning',
                                html: `Invalid login attempt.<br><strong>${remaining}</strong> attempt(s) remaining before account lockout.`,
                                confirmButtonText: 'OK',
                                confirmButtonColor: '#ffc107'
                            });
                        }
                    }
                    
                    // Reset reCAPTCHA for next attempt
                    resetRecaptcha();
                    
                    loginButton.classList.remove('loading');
                    loginButton.disabled = false;
                }
            } catch (error) {
                console.error('Login error:', error);
                // Don't reveal specific error details for security
                showError('A connection error occurred. Please check your internet connection and try again.');
                loginButton.classList.remove('loading');
                loginButton.disabled = false;
            }
        });

        // OTP Modal close and resend handlers
        document.getElementById('otpModalClose').addEventListener('click', closeOtpModal);

        // OTP Verification form submission
        const otpForm = document.getElementById('otpModalForm');
        otpForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            if (!pendingLoginData) {
                Swal.fire({ icon: 'error', title: 'Error', text: 'Session expired. Please start over.' });
                closeOtpModal();
                return;
            }

            const otp = document.getElementById('otp').value.trim();
            if (!otp || otp.length !== 6) {
                document.getElementById('otpModalErrorText').textContent = 'Please enter a valid 6-digit code.';
                document.getElementById('otpModalErrorMessage').style.display = 'flex';
                return;
            }

            const verifyButton = document.getElementById('otpVerifyButton');
            verifyButton.disabled = true;
            verifyButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Verifying...';

            try {
                // Verify OTP
                const verifyResponse = await fetch('api/verify-admin-otp.php', {
                    method: 'POST',
                    headers: buildApiHeaders(),
                    body: JSON.stringify({
                        otp: otp,
                        email: pendingLoginData.email,
                        purpose: 'login'
                    })
                });

                const verifyData = await verifyResponse.json();

                if (!verifyData.success) {
                    document.getElementById('otpModalErrorText').textContent = verifyData.message || 'Invalid verification code.';
                    document.getElementById('otpModalErrorMessage').style.display = 'flex';
                    verifyButton.disabled = false;
                    verifyButton.innerHTML = '<i class="fas fa-check"></i> <span>Verify Code</span>';
                    return;
                }

                // OTP verified, now complete login
                // Note: Server will skip reCAPTCHA verification since OTP is already verified
                const loginResponse = await fetch('api/login-web.php', {
                    method: 'POST',
                    headers: buildApiHeaders(),
                    body: JSON.stringify({
                        email: pendingLoginData.email,
                        password: pendingLoginData.password,
                        recaptcha_response: '', // Not needed when OTP is verified
                        otp_verified: true
                    })
                });

                const loginData = await loginResponse.json();

                if (loginData.success) {
                    // Reset login attempts
                    resetLoginAttempts();
                    localStorage.removeItem(SECURITY_CONFIG.LOCKOUT_KEY);
                    localStorage.removeItem(SECURITY_CONFIG.LOCKOUT_TIME_KEY);
                    
                    // Store session data
                    if (pendingLoginData.rememberMe) {
                        localStorage.setItem('admin_remember', 'true');
                        localStorage.setItem('admin_email', pendingLoginData.email);
                    } else {
                        localStorage.removeItem('admin_remember');
                        localStorage.removeItem('admin_email');
                    }

                    Swal.fire({
                        icon: 'success',
                        title: 'Login Successful!',
                        text: 'Welcome back, ' + (loginData.username || 'Admin') + '!',
                        showConfirmButton: false,
                        timer: 1500
                    }).then(() => {
                        window.location.href = 'sidebar/dashboard.php';
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: loginData.message || 'Login failed. Please try again.',
                        confirmButtonColor: '#dc3545'
                    });
                    verifyButton.disabled = false;
                    verifyButton.innerHTML = '<i class="fas fa-check"></i> <span>Verify Code</span>';
                    closeOtpModal();
                }
            } catch (error) {
                console.error('Verify/Login error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'A connection error occurred. Please try again.',
                    confirmButtonColor: '#dc3545'
                });
                verifyButton.disabled = false;
                verifyButton.innerHTML = '<i class="fas fa-check"></i> <span>Verify Code</span>';
            }
        });

        // Resend OTP button
        document.getElementById('otpResendButton').addEventListener('click', async function() {
            if (!pendingLoginData) return;

            try {
                const response = await fetch('api/send-admin-otp.php', {
                    method: 'POST',
                    headers: buildApiHeaders(),
                    body: JSON.stringify({
                        email: pendingLoginData.email,
                        name: 'Admin',
                        purpose: 'login'
                    })
                });

                const data = await response.json();
                if (data.success) {
                    document.getElementById('otpSentBanner').style.display = 'block';
                    if (data.debug_otp) {
                        document.getElementById('otpWarnBanner').style.display = 'block';
                        document.getElementById('otpDebugCode').textContent = data.debug_otp;
                        document.getElementById('otpDebugCode').style.display = 'block';
                    }
                    startResendCooldown(60);
                    Swal.fire({
                        icon: 'success',
                        title: 'Code Sent',
                        text: 'A new verification code has been sent to your email.',
                        timer: 2000,
                        showConfirmButton: false
                    });
                }
            } catch (error) {
                Swal.fire({ icon: 'error', title: 'Error', text: 'Failed to resend code. Please try again.' });
            }
        });

        // Prevent form autofill abuse
        document.addEventListener('DOMContentLoaded', function() {
            // Clear any autofilled data on page load for security
            setTimeout(function() {
                if (passwordInput.value && !document.getElementById('rememberMe').checked) {
                    // Only clear if not remembered
                    const remembered = localStorage.getItem('admin_remember');
                    if (remembered !== 'true') {
                        passwordInput.value = '';
                    }
                }
            }, 100);

            // Note: Developer tools are enabled for debugging purposes
            // You can re-enable the restrictions below if needed for production
        });

        // Check if email was remembered
        document.addEventListener('DOMContentLoaded', function() {
            const remembered = localStorage.getItem('admin_remember');
            const rememberedEmail = localStorage.getItem('admin_email');
            
            if (remembered === 'true' && rememberedEmail) {
                document.getElementById('email').value = rememberedEmail;
                document.getElementById('rememberMe').checked = true;
            }
        });

        // Forgot Password (placeholder)
        document.getElementById('forgotPassword').addEventListener('click', function(e) {
            e.preventDefault();
            Swal.fire({
                icon: 'info',
                title: 'Password Recovery',
                html: 'For security reasons, password recovery must be handled by your system administrator.<br><br>Please contact your administrator with your registered email address to reset your password.',
                confirmButtonText: 'OK',
                confirmButtonColor: '#4c8a89'
            });
        });

        // Security: Clear sensitive data on page unload
        window.addEventListener('beforeunload', function() {
            // Clear password field
            passwordInput.value = '';
            resetRecaptcha();
        });

        // Security: Detect if page is being viewed in iframe (clickjacking protection)
        if (window.self !== window.top) {
            document.body.innerHTML = '<div style="text-align:center;padding:50px;"><h2>Access Denied</h2><p>This page cannot be displayed in a frame for security reasons.</p></div>';
        }
    </script>
</body>
</html>

