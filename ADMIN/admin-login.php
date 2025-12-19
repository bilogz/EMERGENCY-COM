<?php
// Simple Admin Login Page at project root
$assetBase = 'ADMIN/header/';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login</title>
    <link rel="icon" type="image/x-icon" href="<?= $assetBase ?>images/favicon.ico">
    <link rel="stylesheet" href="<?= $assetBase ?>css/global.css">
    <link rel="stylesheet" href="<?= $assetBase ?>css/buttons.css">
    <link rel="stylesheet" href="<?= $assetBase ?>css/hero.css">
    <link rel="stylesheet" href="ADMIN/sidebar/css/global.css">
    <link rel="stylesheet" href="ADMIN/sidebar/css/content.css">
    <link rel="stylesheet" href="ADMIN/sidebar/css/buttons.css">
    <link rel="stylesheet" href="USERS/css/user.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="<?= $assetBase ?>js/theme-toggle.js" defer></script>
    <style>
        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            background-color: var(--bg-color);
            color: var(--text-color);
            font-family: var(--font-family);
            transition: background-color 0.3s ease, color 0.3s ease;
        }
        /* Light / dark backgrounds driven by system or user theme */
        [data-theme="light"] body {
            background: radial-gradient(circle at top, #e0f2fe 0, #eff6ff 35%, #e5e7eb 100%);
        }
        [data-theme="dark"] body {
            background: radial-gradient(circle at top, #1e3a8a 0, #020617 45%, #000 100%);
        }
        .admin-login-wrapper {
            width: 100%;
            max-width: 420px;
            padding: 1.5rem;
        }
        .admin-card {
            background: var(--card-bg);
            border-radius: 1rem;
            padding: 2rem 2.25rem;
            box-shadow: 0 24px 80px var(--shadow);
            border: 1px solid var(--border-color);
            backdrop-filter: blur(14px);
        }
        .admin-logo-row {
            display: flex;
            align-items: center;
            gap: 0.6rem;
            margin-bottom: 0.5rem;
        }
        .admin-logo-circle {
            width: 40px;
            height: 40px;
            border-radius: 999px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: radial-gradient(circle at 20% 0, #38bdf8, #0f172a);
            box-shadow: 0 0 0 1px rgba(56, 189, 248, 0.7);
        }
        .admin-logo-circle i {
            color: #e5e7eb;
        }
        .admin-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--text-color);
        }
        .admin-subtitle {
            font-size: 0.9rem;
            color: var(--text-secondary);
            margin-bottom: 1.25rem;
        }
        .security-banner {
            display: flex;
            align-items: flex-start;
            gap: 0.55rem;
            padding: 0.55rem 0.75rem;
            border-radius: 0.6rem;
            background: rgba(22, 101, 52, 0.15);
            border: 1px solid rgba(34, 197, 94, 0.45);
            color: #bbf7d0;
            font-size: 0.75rem;
            margin-bottom: 1.15rem;
        }
        .security-banner i {
            margin-top: 0.05rem;
            font-size: 0.8rem;
        }
        .security-banner strong {
            font-weight: 600;
            color: #dcfce7;
        }
        .auth-form {
            display: flex;
            flex-direction: column;
            gap: 0.9rem;
        }
        .form-group {
            display: flex;
            flex-direction: column;
            gap: 0.3rem;
        }
        label {
            font-size: 0.8rem;
            color: var(--text-color);
        }
        .input-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }
        .input-wrapper input {
            width: 100%;
            padding: 0.6rem 0.75rem;
            padding-left: 2.25rem;
            border-radius: 0.55rem;
            border: 1px solid #1e293b;
            background: rgba(15, 23, 42, 0.9);
            color: #e5e7eb;
            font-size: 0.85rem;
            outline: none;
            transition: border-color 0.15s ease, box-shadow 0.15s ease, background 0.15s ease;
        }
        .input-wrapper input::placeholder {
            color: #64748b;
        }
        .input-wrapper input:focus {
            border-color: #38bdf8;
            box-shadow: 0 0 0 1px rgba(56, 189, 248, 0.75);
            background: rgba(15, 23, 42, 1);
        }
        .input-wrapper .input-icon-left {
            position: absolute;
            left: 0.8rem;
            font-size: 0.9rem;
            color: #64748b;
        }
        .input-wrapper .toggle-password-btn {
            position: absolute;
            right: 0.55rem;
            background: none;
            border: none;
            color: #64748b;
            cursor: pointer;
            padding: 0.1rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .input-helper {
            font-size: 0.72rem;
            color: var(--text-secondary);
        }
        .input-helper strong {
            color: #e5e7eb;
        }
        .error-text {
            font-size: 0.72rem;
            color: #fca5a5;
            display: none;
        }
        .password-meter {
            margin-top: 0.2rem;
            height: 4px;
            border-radius: 999px;
            background: #020617;
            overflow: hidden;
        }
        .password-meter-bar {
            height: 100%;
            width: 0%;
            background: linear-gradient(90deg, #ef4444, #facc15, #22c55e);
            transition: width 0.2s ease;
        }
        .options-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 0.3rem;
            margin-bottom: 0.35rem;
        }
        .remember-me {
            display: flex;
            align-items: center;
            gap: 0.35rem;
            font-size: 0.75rem;
            color: var(--text-secondary);
        }
        .remember-me input {
            width: 14px;
            height: 14px;
        }
        .forgot-link, .security-settings-link {
            font-size: 0.75rem;
            color: #38bdf8;
            text-decoration: none;
        }
        .forgot-link:hover, .security-settings-link:hover {
            text-decoration: underline;
        }
        .btn-primary {
            width: 100%;
            margin-top: 0.5rem;
            padding: 0.65rem 0.8rem;
            border-radius: 0.6rem;
            border: none;
            background: linear-gradient(135deg, var(--primary-color), #0ea5e9);
            color: #ffffff;
            font-weight: 600;
            font-size: 0.9rem;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.4rem;
            box-shadow: 0 10px 32px rgba(15, 118, 110, 0.6);
            transition: transform 0.12s ease, box-shadow 0.12s ease, filter 0.12s ease, opacity 0.12s ease;
        }
        .btn-primary:disabled {
            opacity: 0.65;
            cursor: not-allowed;
            box-shadow: none;
        }
        .btn-primary:not(:disabled):hover {
            transform: translateY(-1px);
            box-shadow: 0 18px 40px rgba(8, 47, 73, 0.9);
            filter: brightness(1.05);
        }
        .btn-primary i {
            font-size: 0.9rem;
        }
        .footer-meta {
            margin-top: 0.9rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.7rem;
            color: var(--text-secondary);
        }
        .footer-meta span {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
        }
        .footer-meta i {
            font-size: 0.75rem;
        }
        .tiny-note {
            margin-top: 0.4rem;
            font-size: 0.7rem;
            color: var(--text-secondary);
        }
        @media (max-width: 640px) {
            body {
                align-items: flex-start;
                padding-top: 3.5rem;
            }
            .admin-card {
                padding: 1.6rem 1.5rem;
                border-radius: 0.9rem;
            }
        }
    </style>
</head>
<body>
    <div class="admin-login-wrapper">
        <div class="admin-card">
            <div class="admin-logo-row">
                <div class="admin-logo-circle">
                    <i class="fas fa-shield-alt"></i>
                </div>
                <div>
                    <div class="admin-title">Emergency Admin</div>
                    <div class="admin-subtitle">Restricted access for authorized responders only.</div>
                </div>
            </div>

            <div class="security-banner">
                <i class="fas fa-lock"></i>
                <div>
                    <strong>Protected console.</strong> Multi-factor authentication and access logging are enforced on the server. Never share your credentials.
                </div>
            </div>

            <form id="admin-login-form" class="auth-form" autocomplete="off" novalidate>
                <div class="form-group">
                    <label for="admin_email">Work Email</label>
                    <div class="input-wrapper">
                        <span class="input-icon-left">
                            <i class="fas fa-envelope"></i>
                        </span>
                        <input type="email" id="admin_email" name="email" placeholder="you@agency.gov" required autocomplete="off">
                    </div>
                    <div id="email-error" class="error-text">Enter a valid work email address.</div>
                </div>

                <div class="form-group">
                    <label for="admin_password">Password</label>
                    <div class="input-wrapper">
                        <span class="input-icon-left">
                            <i class="fas fa-key"></i>
                        </span>
                        <input type="password" id="admin_password" name="password" placeholder="Minimum 12 characters" required minlength="12" autocomplete="new-password">
                        <button type="button" class="toggle-password-btn" aria-label="Toggle password visibility">
                            <i class="fas fa-eye-slash"></i>
                        </button>
                    </div>
                    <div class="password-meter">
                        <div id="password-meter-bar" class="password-meter-bar"></div>
                    </div>
                    <div class="input-helper">
                        Use <strong>12+ characters</strong> with upper/lowercase, numbers, and symbols.
                    </div>
                    <div id="password-error" class="error-text">Password is too weak. Please follow the recommended complexity.</div>
                </div>

                <div class="options-row">
                    <label class="remember-me">
                        <input type="checkbox" id="remember_device">
                        <span>Remember this device</span>
                    </label>
                    <a href="#" class="forgot-link">Forgot access?</a>
                </div>

                <button type="submit" class="btn btn-primary" id="admin-login-btn">
                    <span>Continue to console</span>
                    <i class="fas fa-arrow-right"></i>
                </button>

                <div class="tiny-note">
                    UI only: backend verification, rate limiting, and MFA checks will be wired to the API in deployment.
                </div>
            </form>

            <div class="footer-meta">
                <span>
                    <i class="fas fa-user-shield"></i>
                    Admin access monitored
                </span>
                <span>
                    <i class="fas fa-clock"></i>
                    Session auto-timeout enabled
                </span>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        const emailInput = document.getElementById('admin_email');
        const passwordInput = document.getElementById('admin_password');
        const emailError = document.getElementById('email-error');
        const passwordError = document.getElementById('password-error');
        const passwordMeterBar = document.getElementById('password-meter-bar');
        const togglePasswordBtn = document.querySelector('.toggle-password-btn');
        const loginForm = document.getElementById('admin-login-form');
        const loginBtn = document.getElementById('admin-login-btn');

        function evaluatePasswordStrength(password) {
            let score = 0;
            if (password.length >= 12) score += 1;
            if (/[A-Z]/.test(password)) score += 1;
            if (/[a-z]/.test(password)) score += 1;
            if (/[0-9]/.test(password)) score += 1;
            if (/[^A-Za-z0-9]/.test(password)) score += 1;
            return score; // 0–5
        }

        function updatePasswordMeter() {
            const pwd = passwordInput.value;
            const score = evaluatePasswordStrength(pwd);
            const percent = (score / 5) * 100;
            passwordMeterBar.style.width = percent + '%';
        }

        passwordInput.addEventListener('input', () => {
            passwordError.style.display = 'none';
            updatePasswordMeter();
        });

        emailInput.addEventListener('input', () => {
            emailError.style.display = 'none';
        });

        togglePasswordBtn.addEventListener('click', () => {
            const isHidden = passwordInput.type === 'password';
            passwordInput.type = isHidden ? 'text' : 'password';
            togglePasswordBtn.innerHTML = isHidden
                ? '<i class="fas fa-eye"></i>'
                : '<i class="fas fa-eye-slash"></i>';
        });

        // FRONT-END ONLY: basic validation + demo dialog, no real backend call yet.
        loginForm.addEventListener('submit', function (e) {
            e.preventDefault();

            const email = emailInput.value.trim();
            const password = passwordInput.value;
            let valid = true;

            // Simple work-email style check (front-end hint only)
            if (!email || !email.includes('@')) {
                emailError.style.display = 'block';
                valid = false;
            }

            const score = evaluatePasswordStrength(password);
            if (score < 3) {
                passwordError.style.display = 'block';
                valid = false;
            }

            if (!valid) {
                return;
            }

            loginBtn.disabled = true;
            loginBtn.innerHTML = '<span>Checking security</span><i class="fas fa-spinner fa-spin"></i>';

            // Simulate secure verification step without calling the API
            setTimeout(() => {
                Swal.fire({
                    icon: 'info',
                    title: 'Demo mode',
                    html: 'UI is ready. Backend validation, account lockout, and MFA verification will be connected to the admin API.',
                    confirmButtonText: 'Got it'
                }).then(() => {
                    loginBtn.disabled = false;
                    loginBtn.innerHTML = '<span>Continue to console</span><i class="fas fa-arrow-right"></i>';
                });
            }, 700);
        });
    </script>
</body>
</html>


