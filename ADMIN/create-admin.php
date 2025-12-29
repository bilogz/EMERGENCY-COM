<?php
/**
 * Create Admin Account Page
 * Only super_admin can access this page to create admin accounts
 * Accounts are stored in admin_user table
 */

session_start();
require_once __DIR__ . '/api/db_connect.php';
require_once __DIR__ . '/api/security-helpers.php';

// Check if admin_user table exists
$adminUserTableExists = false;
try {
    if ($pdo) {
        $pdo->query("SELECT 1 FROM admin_user LIMIT 1");
        $adminUserTableExists = true;
    }
} catch (PDOException $e) {
    // Table doesn't exist
    $adminUserTableExists = false;
}

// If database connection failed, show error
if ($pdo === null) {
    die('
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Database Connection Error - Create Admin Account</title>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
        <style>
            body {
                display: flex;
                align-items: center;
                justify-content: center;
                min-height: 100vh;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                padding: 1rem;
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            }
            .error-container {
                background: white;
                border-radius: 16px;
                padding: 3rem 2.5rem;
                max-width: 600px;
                width: 100%;
                text-align: center;
                box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            }
            .error-icon {
                width: 90px;
                height: 90px;
                margin: 0 auto 1.5rem;
                display: flex;
                align-items: center;
                justify-content: center;
                background: #fee;
                border-radius: 20px;
            }
            .error-icon i {
                font-size: 2.5rem;
                color: #dc3545;
            }
            .error-title {
                font-size: 1.75rem;
                font-weight: 700;
                color: #222;
                margin-bottom: 1rem;
            }
            .error-message {
                color: #666;
                margin-bottom: 2rem;
                line-height: 1.6;
                text-align: left;
            }
            .error-details {
                background: #f8f9fa;
                padding: 1rem;
                border-radius: 8px;
                margin: 1rem 0;
                text-align: left;
                font-family: monospace;
                font-size: 0.85rem;
                color: #dc3545;
                word-break: break-word;
            }
            .btn-setup {
                display: inline-block;
                padding: 1rem 2rem;
                background: linear-gradient(135deg, #3a7675 0%, #4ca8a6 100%);
                color: white;
                text-decoration: none;
                border-radius: 10px;
                font-weight: 600;
                transition: all 0.3s ease;
                margin: 0.5rem;
            }
            .btn-setup:hover {
                transform: translateY(-2px);
            }
        </style>
    </head>
    <body>
        <div class="error-container">
            <div class="error-icon">
                <i class="fas fa-database"></i>
            </div>
            <h1 class="error-title">Database Connection Failed</h1>
            <p class="error-message">
                Unable to connect to the database. Please check your database configuration.
            </p>
            <div class="error-details">' . htmlspecialchars($dbError ?? 'Unknown error') . '</div>
            <div style="margin-top: 2rem;">
                <a href="api/setup_remote_database.php" class="btn-setup">
                    <i class="fas fa-cog"></i> Run Database Setup
                </a>
                <a href="login.php" class="btn-setup" style="background: #6c757d;">
                    <i class="fas fa-arrow-left"></i> Back to Login
                </a>
            </div>
        </div>
    </body>
    </html>');
}

// If table doesn't exist, show setup message
if (!$adminUserTableExists) {
    die('
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Database Setup Required - Create Admin Account</title>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
        <style>
            body {
                display: flex;
                align-items: center;
                justify-content: center;
                min-height: 100vh;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                padding: 1rem;
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            }
            .setup-container {
                background: white;
                border-radius: 16px;
                padding: 3rem 2.5rem;
                max-width: 600px;
                width: 100%;
                text-align: center;
                box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            }
            .setup-icon {
                width: 90px;
                height: 90px;
                margin: 0 auto 1.5rem;
                display: flex;
                align-items: center;
                justify-content: center;
                background: linear-gradient(135deg, #3a7675 0%, #4ca8a6 100%);
                border-radius: 20px;
                box-shadow: 0 8px 20px rgba(58,118,117,0.3);
            }
            .setup-icon i {
                font-size: 2.5rem;
                color: white;
            }
            .setup-title {
                font-size: 1.75rem;
                font-weight: 700;
                color: #222;
                margin-bottom: 1rem;
            }
            .setup-message {
                color: #666;
                margin-bottom: 2rem;
                line-height: 1.6;
                text-align: left;
            }
            .setup-steps {
                background: #f8f9fa;
                padding: 1.5rem;
                border-radius: 10px;
                margin: 1.5rem 0;
                text-align: left;
            }
            .setup-steps ol {
                margin: 0;
                padding-left: 1.5rem;
            }
            .setup-steps li {
                margin: 0.75rem 0;
                color: #333;
            }
            .btn-setup {
                display: inline-block;
                padding: 1rem 2rem;
                background: linear-gradient(135deg, #3a7675 0%, #4ca8a6 100%);
                color: white;
                text-decoration: none;
                border-radius: 10px;
                font-weight: 600;
                transition: all 0.3s ease;
                margin: 0.5rem;
                box-shadow: 0 4px 15px rgba(58,118,117,0.3);
            }
            .btn-setup:hover {
                transform: translateY(-2px);
                box-shadow: 0 6px 20px rgba(58,118,117,0.4);
            }
            .btn-secondary {
                background: #6c757d;
                box-shadow: 0 4px 15px rgba(108,117,125,0.3);
            }
            .btn-secondary:hover {
                background: #5a6268;
            }
        </style>
    </head>
    <body>
        <div class="setup-container">
            <div class="setup-icon">
                <i class="fas fa-database"></i>
            </div>
            <h1 class="setup-title">Database Setup Required</h1>
            <p class="setup-message">
                The <strong>admin_user</strong> table needs to be created before you can create admin accounts. 
                This table separates admin accounts from regular user accounts for better security.
            </p>
            
            <div class="setup-steps">
                <strong>Quick Setup:</strong>
                <ol>
                    <li>Click the button below to run the database setup script</li>
                    <li>The script will create the admin_user table</li>
                    <li>Existing admin accounts will be migrated automatically</li>
                    <li>First admin will be set as super_admin</li>
                    <li>You can then create new admin accounts</li>
                </ol>
            </div>
            
            <div style="margin-top: 2rem;">
                <a href="api/setup_admin_user_database.php" class="btn-setup" target="_blank">
                    <i class="fas fa-cog"></i> Run Database Setup
                </a>
                <a href="login.php" class="btn-setup btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Login
                </a>
            </div>
            
            <p style="margin-top: 2rem; font-size: 0.875rem; color: #888;">
                <i class="fas fa-info-circle"></i> 
                After running the setup, refresh this page to continue.
            </p>
        </div>
    </body>
    </html>');
}

// Ensure admin_user table exists (double check)
ensureAdminUserTable($pdo);

// Check authorization - only super_admin can create accounts
$authCheck = checkAdminAuthorization($pdo);
$isAuthorized = $authCheck['authorized'];
$authReason = $authCheck['reason'] ?? '';

// If not authorized, show error page
if (!$isAuthorized && $authReason !== 'initial_setup') {
    $errorMessages = [
        'not_logged_in' => 'You must be logged in as a super administrator to create admin accounts.',
        'invalid_session' => 'Invalid session. Please log in again.',
        'not_admin' => 'Access denied. Super administrator privileges required.',
        'not_super_admin' => 'Access denied. Only super administrators can create admin accounts.',
        'database_error' => 'Database error occurred. Please contact system administrator.'
    ];
    
    $errorMessage = $errorMessages[$authReason] ?? 'Unauthorized access.';
    
    die('
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Access Denied - Create Admin Account</title>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
        <style>
            body {
                display: flex;
                align-items: center;
                justify-content: center;
                min-height: 100vh;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                padding: 1rem;
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            }
            .error-container {
                background: white;
                border-radius: 16px;
                padding: 3rem 2.5rem;
                max-width: 500px;
                width: 100%;
                text-align: center;
                box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            }
            .error-icon {
                width: 80px;
                height: 80px;
                margin: 0 auto 1.5rem;
                display: flex;
                align-items: center;
                justify-content: center;
                background: #fee;
                border-radius: 50%;
                color: #dc3545;
                font-size: 2.5rem;
            }
            .error-title {
                font-size: 1.75rem;
                font-weight: 700;
                color: #222;
                margin-bottom: 1rem;
            }
            .error-message {
                color: #666;
                margin-bottom: 2rem;
                line-height: 1.6;
            }
            .btn-back {
                display: inline-block;
                padding: 0.875rem 2rem;
                background: #3a7675;
                color: white;
                text-decoration: none;
                border-radius: 8px;
                font-weight: 600;
                transition: all 0.3s ease;
            }
            .btn-back:hover {
                background: #4ca8a6;
                transform: translateY(-2px);
                box-shadow: 0 4px 12px rgba(58,118,117,0.3);
            }
        </style>
    </head>
    <body>
        <div class="error-container">
            <div class="error-icon">
                <i class="fas fa-shield-alt"></i>
            </div>
            <h1 class="error-title">Access Denied</h1>
            <p class="error-message">' . htmlspecialchars($errorMessage) . '</p>
            <a href="login.php" class="btn-back">
                <i class="fas fa-arrow-left"></i> Back to Login
            </a>
        </div>
    </body>
    </html>');
}

// Generate CSRF token
$csrfToken = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Admin Account - Emergency Communication System</title>
    <link rel="stylesheet" href="header/css/forms.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            box-sizing: border-box;
        }
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 1rem;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            margin: 0;
        }
        .form-container {
            width: 100%;
            max-width: 550px;
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            padding: 3rem 2.5rem;
            animation: fadeInUp 0.5s ease;
        }
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .form-header {
            text-align: center;
            margin-bottom: 2.5rem;
        }
        .form-logo {
            width: 90px;
            height: 90px;
            margin: 0 auto 1.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #3a7675 0%, #4ca8a6 100%);
            border-radius: 20px;
            box-shadow: 0 8px 20px rgba(58,118,117,0.3);
        }
        .form-logo i {
            font-size: 2.5rem;
            color: white;
        }
        .form-title {
            font-size: 2rem;
            font-weight: 700;
            color: #222;
            margin-bottom: 0.5rem;
        }
        .form-subtitle {
            font-size: 0.95rem;
            color: #666;
            line-height: 1.6;
        }
        .alert {
            padding: 1rem 1.25rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 14px;
            animation: slideDown 0.3s ease;
        }
        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .alert i {
            font-size: 1.25rem;
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
            font-weight: 600;
            color: #222;
            font-size: 14px;
        }
        .form-label i {
            color: #3a7675;
            font-size: 16px;
        }
        .form-label .required {
            color: #dc3545;
            margin-left: 2px;
        }
        .form-control {
            width: 100%;
            padding: 0.875rem 1rem;
            padding-left: 2.75rem;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 14px;
            font-family: inherit;
            background-color: #f8f9fa;
            color: #222;
            transition: all 0.3s ease;
            outline: none;
        }
        .form-control:focus {
            border-color: #3a7675;
            background-color: white;
            box-shadow: 0 0 0 4px rgba(58,118,117,0.1);
        }
        .form-control.error {
            border-color: #dc3545;
        }
        .form-control.success {
            border-color: #28a745;
        }
        .input-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #888;
            font-size: 16px;
            pointer-events: none;
            transition: color 0.3s ease;
        }
        .form-control:focus ~ .input-icon,
        .form-group:has(.form-control:focus) .input-icon {
            color: #3a7675;
        }
        .password-strength {
            margin-top: 0.5rem;
            font-size: 12px;
        }
        .password-strength-bar {
            height: 4px;
            background: #e0e0e0;
            border-radius: 2px;
            margin-top: 0.5rem;
            overflow: hidden;
        }
        .password-strength-fill {
            height: 100%;
            width: 0%;
            transition: all 0.3s ease;
            border-radius: 2px;
        }
        .password-strength-fill.weak { background: #dc3545; width: 33%; }
        .password-strength-fill.medium { background: #ffc107; width: 66%; }
        .password-strength-fill.strong { background: #28a745; width: 100%; }
        .form-text {
            display: block;
            margin-top: 0.5rem;
            font-size: 12px;
            color: #666;
        }
        .form-text.error {
            color: #dc3545;
        }
        .form-text.success {
            color: #28a745;
        }
        .btn-submit {
            width: 100%;
            padding: 1rem 1.5rem;
            background: linear-gradient(135deg, #3a7675 0%, #4ca8a6 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            margin-top: 1.5rem;
            box-shadow: 0 4px 15px rgba(58,118,117,0.3);
        }
        .btn-submit:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(58,118,117,0.4);
        }
        .btn-submit:active:not(:disabled) {
            transform: translateY(0);
        }
        .btn-submit:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }
        .btn-submit i {
            font-size: 16px;
        }
        .form-footer {
            margin-top: 1.5rem;
            text-align: center;
        }
        .form-footer a {
            color: #3a7675;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }
        .form-footer a:hover {
            color: #4ca8a6;
            text-decoration: underline;
        }
        .loading-spinner {
            display: none;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255,255,255,0.3);
            border-top-color: white;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        .btn-submit.loading .loading-spinner {
            display: inline-block;
        }
        .btn-submit.loading .btn-text {
            display: none;
        }
        @media (max-width: 480px) {
            .form-container {
                padding: 2rem 1.5rem;
            }
            .form-title {
                font-size: 1.5rem;
            }
            .form-logo {
                width: 70px;
                height: 70px;
            }
            .form-logo i {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>
    <div class="form-container">
        <div class="form-header">
            <div class="form-logo">
                <i class="fas fa-user-shield"></i>
            </div>
            <h1 class="form-title">Create Admin Account</h1>
            <p class="form-subtitle">Emergency Communication System<br>Administrative Panel</p>
        </div>

        <div id="alertContainer"></div>

        <form id="createAdminForm" autocomplete="off">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
            
            <div class="form-group">
                <label for="name" class="form-label">
                    <i class="fas fa-user"></i> Full Name <span class="required">*</span>
                </label>
                <input type="text" name="name" id="name" class="form-control" required 
                       placeholder="Enter full name" minlength="2">
                <i class="fas fa-user input-icon"></i>
                <span class="form-text" id="nameError"></span>
            </div>

            <div class="form-group">
                <label for="username" class="form-label">
                    <i class="fas fa-user-tag"></i> Username <span class="required">*</span>
                </label>
                <input type="text" name="username" id="username" class="form-control" required 
                       placeholder="Enter username" minlength="3" pattern="[a-zA-Z0-9_]+">
                <i class="fas fa-user-tag input-icon"></i>
                <span class="form-text" id="usernameError"></span>
                <small class="form-text">Only letters, numbers, and underscores allowed</small>
            </div>

            <div class="form-group">
                <label for="email" class="form-label">
                    <i class="fas fa-envelope"></i> Email Address <span class="required">*</span>
                </label>
                <input type="email" name="email" id="email" class="form-control" required 
                       placeholder="admin@example.com">
                <i class="fas fa-envelope input-icon"></i>
                <span class="form-text" id="emailError"></span>
            </div>

            <div class="form-group">
                <label for="phone" class="form-label">
                    <i class="fas fa-phone"></i> Phone Number
                </label>
                <input type="tel" name="phone" id="phone" class="form-control" 
                       placeholder="+63XXXXXXXXXX">
                <i class="fas fa-phone input-icon"></i>
                <span class="form-text" id="phoneError"></span>
            </div>

            <div class="form-group">
                <label for="password" class="form-label">
                    <i class="fas fa-lock"></i> Password <span class="required">*</span>
                </label>
                <input type="password" name="password" id="password" class="form-control" required 
                       placeholder="Enter password" minlength="8">
                <i class="fas fa-lock input-icon"></i>
                <div class="password-strength">
                    <div class="password-strength-bar">
                        <div class="password-strength-fill" id="passwordStrength"></div>
                    </div>
                </div>
                <span class="form-text" id="passwordError"></span>
                <small class="form-text">Must be at least 8 characters with uppercase, lowercase, number, and special character</small>
            </div>

            <div class="form-group">
                <label for="confirm_password" class="form-label">
                    <i class="fas fa-lock"></i> Confirm Password <span class="required">*</span>
                </label>
                <input type="password" name="confirm_password" id="confirm_password" class="form-control" required 
                       placeholder="Confirm password" minlength="8">
                <i class="fas fa-lock input-icon"></i>
                <span class="form-text" id="confirmPasswordError"></span>
            </div>

            <div class="form-group">
                <label for="role" class="form-label">
                    <i class="fas fa-user-cog"></i> Role <span class="required">*</span>
                </label>
                <select name="role" id="role" class="form-control" required>
                    <option value="">Select role</option>
                    <option value="admin">Administrator</option>
                    <option value="staff">Staff</option>
                </select>
                <i class="fas fa-user-cog input-icon"></i>
                <span class="form-text" id="roleError"></span>
            </div>

            <button type="submit" class="btn-submit" id="submitBtn">
                <span class="btn-text">
                    <i class="fas fa-user-plus"></i> Create Admin Account
                </span>
                <div class="loading-spinner"></div>
            </button>
        </form>

        <div class="form-footer">
            <a href="sidebar/dashboard.php">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>
    </div>

    <script>
        // Password strength checker
        const passwordInput = document.getElementById('password');
        const passwordStrength = document.getElementById('passwordStrength');
        
        passwordInput.addEventListener('input', function() {
            const password = this.value;
            let strength = 0;
            
            if (password.length >= 8) strength++;
            if (/[a-z]/.test(password)) strength++;
            if (/[A-Z]/.test(password)) strength++;
            if (/[0-9]/.test(password)) strength++;
            if (/[^A-Za-z0-9]/.test(password)) strength++;
            
            passwordStrength.className = 'password-strength-fill';
            if (strength <= 2) {
                passwordStrength.classList.add('weak');
            } else if (strength <= 4) {
                passwordStrength.classList.add('medium');
            } else {
                passwordStrength.classList.add('strong');
            }
        });

        // Form validation and submission
        const form = document.getElementById('createAdminForm');
        const submitBtn = document.getElementById('submitBtn');
        const alertContainer = document.getElementById('alertContainer');

        function showAlert(message, type = 'error') {
            alertContainer.innerHTML = `
                <div class="alert alert-${type}">
                    <i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'}"></i>
                    <span>${message}</span>
                </div>
            `;
            alertContainer.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }

        function clearErrors() {
            document.querySelectorAll('.form-text.error').forEach(el => {
                el.textContent = '';
                el.className = 'form-text';
            });
            document.querySelectorAll('.form-control.error').forEach(el => {
                el.classList.remove('error');
            });
        }

        function showError(fieldId, message) {
            const field = document.getElementById(fieldId);
            const errorEl = document.getElementById(fieldId + 'Error');
            field.classList.add('error');
            if (errorEl) {
                errorEl.textContent = message;
                errorEl.className = 'form-text error';
            }
        }

        function validateForm() {
            clearErrors();
            let isValid = true;

            const name = document.getElementById('name').value.trim();
            if (name.length < 2) {
                showError('name', 'Name must be at least 2 characters');
                isValid = false;
            }

            const username = document.getElementById('username').value.trim();
            if (username.length < 3) {
                showError('username', 'Username must be at least 3 characters');
                isValid = false;
            } else if (!/^[a-zA-Z0-9_]+$/.test(username)) {
                showError('username', 'Username can only contain letters, numbers, and underscores');
                isValid = false;
            }

            const email = document.getElementById('email').value.trim();
            if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                showError('email', 'Please enter a valid email address');
                isValid = false;
            }

            const password = document.getElementById('password').value;
            if (password.length < 8) {
                showError('password', 'Password must be at least 8 characters');
                isValid = false;
            } else if (!/(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])(?=.*[^A-Za-z0-9])/.test(password)) {
                showError('password', 'Password must contain uppercase, lowercase, number, and special character');
                isValid = false;
            }

            const confirmPassword = document.getElementById('confirm_password').value;
            if (password !== confirmPassword) {
                showError('confirm_password', 'Passwords do not match');
                isValid = false;
            }

            const role = document.getElementById('role').value;
            if (!role) {
                showError('role', 'Please select a role');
                isValid = false;
            }

            return isValid;
        }

        form.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            if (!validateForm()) {
                showAlert('Please fix the errors in the form', 'error');
                return;
            }

            submitBtn.disabled = true;
            submitBtn.classList.add('loading');
            clearErrors();

            const formData = new FormData(form);
            const data = Object.fromEntries(formData);

            try {
                const response = await fetch('api/create-admin-account.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(data)
                });

                const result = await response.json();

                if (result.success) {
                    showAlert(result.message, 'success');
                    form.reset();
                    passwordStrength.className = 'password-strength-fill';
                    
                    // Redirect after 2 seconds
                    setTimeout(() => {
                        window.location.href = 'sidebar/dashboard.php';
                    }, 2000);
                } else {
                    showAlert(result.message || 'Failed to create admin account', 'error');
                }
            } catch (error) {
                showAlert('Network error. Please check your connection and try again.', 'error');
                console.error('Error:', error);
            } finally {
                submitBtn.disabled = false;
                submitBtn.classList.remove('loading');
            }
        });

        // Real-time validation
        ['name', 'username', 'email', 'password', 'confirm_password'].forEach(fieldId => {
            const field = document.getElementById(fieldId);
            field.addEventListener('blur', validateForm);
        });
    </script>
</body>
</html>
