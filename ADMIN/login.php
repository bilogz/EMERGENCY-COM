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
    <meta http-equiv="Content-Security-Policy" content="default-src 'self'; script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com; font-src 'self' https://cdnjs.cloudflare.com; img-src 'self' data:;">
    <title>Admin Login - Emergency Communication System</title>
    <link rel="icon" type="image/x-icon" href="sidebar/images/favicon.ico">
    <link rel="stylesheet" href="sidebar/css/global.css">
    <link rel="stylesheet" href="sidebar/css/forms.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
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

        .captcha-container {
            margin-bottom: 1.5rem;
            padding: 1rem;
            background: var(--bg-color-1);
            border: 2px solid var(--border-color-1);
            border-radius: 8px;
            display: none;
        }

        .captcha-container.show {
            display: block;
        }

        .captcha-label {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 0.75rem;
            font-weight: 500;
            color: var(--text-color-1);
            font-size: 14px;
        }

        .captcha-label i {
            color: var(--primary-color-1);
            font-size: 16px;
        }

        .captcha-input {
            width: 100%;
            padding: 0.875rem 1rem;
            border: 2px solid var(--border-color-1);
            border-radius: 8px;
            font-size: 14px;
            font-family: var(--font-family-1);
            background-color: var(--bg-color-1);
            color: var(--text-color-1);
            transition: all 0.3s ease;
            outline: none;
            text-transform: uppercase;
            letter-spacing: 2px;
        }

        .captcha-input:focus {
            border-color: var(--primary-color-1);
            box-shadow: 0 0 0 3px rgba(76, 138, 137, 0.1);
        }

        .captcha-display {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            margin-bottom: 0.75rem;
        }

        .captcha-code {
            flex: 1;
            padding: 1rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 8px;
            text-align: center;
            font-size: 24px;
            font-weight: 700;
            color: white;
            letter-spacing: 4px;
            font-family: 'Courier New', monospace;
            user-select: none;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }

        .captcha-refresh {
            padding: 0.75rem;
            background: var(--primary-color-1);
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .captcha-refresh:hover {
            background: #4ca8a6;
            transform: rotate(180deg);
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

            <!-- CAPTCHA -->
            <div class="captcha-container" id="captchaContainer">
                <label class="captcha-label">
                    <i class="fas fa-robot"></i>
                    Security Verification
                </label>
                <div class="captcha-display">
                    <div class="captcha-code" id="captchaCode">ABCD</div>
                    <button type="button" class="captcha-refresh" id="captchaRefresh" aria-label="Refresh CAPTCHA">
                        <i class="fas fa-sync-alt"></i>
                    </button>
                </div>
                <input 
                    type="text" 
                    id="captchaInput" 
                    class="captcha-input" 
                    placeholder="Enter CAPTCHA code" 
                    maxlength="4"
                    autocomplete="off"
                >
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

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
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
            
            if (attempts >= SECURITY_CONFIG.CAPTCHA_REQUIRED_AFTER) {
                document.getElementById('captchaContainer').classList.add('show');
            } else {
                document.getElementById('captchaContainer').classList.remove('show');
            }
            
            if (attempts > 0 && attempts < SECURITY_CONFIG.MAX_ATTEMPTS) {
                document.getElementById('rateLimitWarning').classList.add('show');
                document.getElementById('attemptCount').textContent = remaining;
            } else {
                document.getElementById('rateLimitWarning').classList.remove('show');
            }
        }

        // CAPTCHA Functions
        function generateCaptcha() {
            const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
            let captcha = '';
            for (let i = 0; i < 4; i++) {
                captcha += chars.charAt(Math.floor(Math.random() * chars.length));
            }
            return captcha;
        }

        let currentCaptcha = generateCaptcha();
        document.getElementById('captchaCode').textContent = currentCaptcha;

        document.getElementById('captchaRefresh').addEventListener('click', function() {
            currentCaptcha = generateCaptcha();
            document.getElementById('captchaCode').textContent = currentCaptcha;
            document.getElementById('captchaInput').value = '';
        });

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

        // Update security warnings on page load
        updateSecurityWarnings();

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

            // CAPTCHA validation (if required)
            if (attempts >= SECURITY_CONFIG.CAPTCHA_REQUIRED_AFTER) {
                const captchaInput = document.getElementById('captchaInput').value.trim().toUpperCase();
                if (!captchaInput || captchaInput !== currentCaptcha) {
                    showError('Invalid CAPTCHA code. Please try again.');
                    // Refresh CAPTCHA on error
                    currentCaptcha = generateCaptcha();
                    document.getElementById('captchaCode').textContent = currentCaptcha;
                    document.getElementById('captchaInput').value = '';
                    return;
                }
            }

            // Show loading state
            loginButton.classList.add('loading');
            loginButton.disabled = true;

            try {
                const response = await fetch('api/login-web.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        email: email,
                        password: password
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
                    
                    // Refresh CAPTCHA on failed attempt
                    if (newAttempts >= SECURITY_CONFIG.CAPTCHA_REQUIRED_AFTER) {
                        currentCaptcha = generateCaptcha();
                        document.getElementById('captchaCode').textContent = currentCaptcha;
                        document.getElementById('captchaInput').value = '';
                    }
                    
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

            // Disable right-click context menu for additional security
            document.addEventListener('contextmenu', function(e) {
                e.preventDefault();
                return false;
            });

            // Disable common developer tools shortcuts (basic protection)
            document.addEventListener('keydown', function(e) {
                // Disable F12, Ctrl+Shift+I, Ctrl+Shift+J, Ctrl+U
                if (e.key === 'F12' || 
                    (e.ctrlKey && e.shiftKey && (e.key === 'I' || e.key === 'J')) ||
                    (e.ctrlKey && e.key === 'U')) {
                    e.preventDefault();
                    Swal.fire({
                        icon: 'warning',
                        title: 'Security Notice',
                        text: 'Developer tools are disabled on this page for security reasons.',
                        confirmButtonText: 'OK',
                        confirmButtonColor: '#4c8a89',
                        timer: 2000
                    });
                    return false;
                }
            });
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
            document.getElementById('captchaInput').value = '';
        });

        // Security: Detect if page is being viewed in iframe (clickjacking protection)
        if (window.self !== window.top) {
            document.body.innerHTML = '<div style="text-align:center;padding:50px;"><h2>Access Denied</h2><p>This page cannot be displayed in a frame for security reasons.</p></div>';
        }
    </script>
</body>
</html>

