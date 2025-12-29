<?php
/**
 * Create Admin Account Page
 * SECURE: Only the super admin email can create accounts
 * Super Admin: joecelgarcia1@gmail.com
 */

// Enable error display for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

// ============================================
// SECURITY: SUPER ADMIN EMAIL (ONLY THIS EMAIL CAN CREATE ACCOUNTS)
// ============================================
define('SUPER_ADMIN_EMAIL', 'joecelgarcia1@gmail.com');

// Function to mask email for display (e.g., joe***@gmail.com)
function maskEmail($email) {
    $parts = explode('@', $email);
    if (count($parts) !== 2) return '***@***.***';
    
    $name = $parts[0];
    $domain = $parts[1];
    
    // Show first 3 characters of name, mask the rest
    $visibleChars = min(3, strlen($name));
    $maskedName = substr($name, 0, $visibleChars) . str_repeat('*', max(0, strlen($name) - $visibleChars));
    
    // Mask domain partially
    $domainParts = explode('.', $domain);
    $maskedDomain = substr($domainParts[0], 0, 2) . '***.' . end($domainParts);
    
    return $maskedName . '@' . $maskedDomain;
}

define('SUPER_ADMIN_EMAIL_MASKED', maskEmail(SUPER_ADMIN_EMAIL));

// Auto-detect environment
$isProduction = isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], 'alertaraqc.com') !== false;

// Database credentials based on environment
if ($isProduction) {
    $dbHost = 'localhost';
    $dbName = 'emer_comm_test';
    $dbUser = 'root';
    $dbPass = 'YsqnXk6q#145';
} else {
    $dbHost = 'localhost';
    $dbName = 'emer_comm_test';
    $dbUser = 'root';
    $dbPass = '';
}

// Connect to database
$pdo = null;
$dbError = null;

try {
    $dsn = "mysql:host=$dbHost;dbname=$dbName;charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    $pdo = new PDO($dsn, $dbUser, $dbPass, $options);
} catch (PDOException $e) {
    $dbError = $e->getMessage();
}

// Create admin_user table if it doesn't exist
if ($pdo) {
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS admin_user (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            username VARCHAR(100) DEFAULT NULL,
            email VARCHAR(255) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            role VARCHAR(20) DEFAULT 'admin',
            status VARCHAR(20) DEFAULT 'active',
            phone VARCHAR(20) DEFAULT NULL,
            created_by INT DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            last_login DATETIME DEFAULT NULL,
            INDEX idx_email (email),
            INDEX idx_username (username),
            INDEX idx_role (role),
            INDEX idx_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    } catch (PDOException $e) {
        // Table might already exist, ignore
    }
}

// Generate CSRF token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_token'];

// Check if user is authorized (logged in as super admin)
$isAuthorized = false;
$currentUserEmail = null;

if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    // Check if logged in user is the super admin
    if (isset($_SESSION['admin_email'])) {
        $currentUserEmail = $_SESSION['admin_email'];
        if (strtolower($currentUserEmail) === strtolower(SUPER_ADMIN_EMAIL)) {
            $isAuthorized = true;
        }
    }
    
    // Also check by admin_user_id if email not in session
    if (!$isAuthorized && isset($_SESSION['admin_user_id']) && $pdo) {
        try {
            $stmt = $pdo->prepare("SELECT email FROM admin_user WHERE id = ?");
            $stmt->execute([$_SESSION['admin_user_id']]);
            $admin = $stmt->fetch();
            if ($admin && strtolower($admin['email']) === strtolower(SUPER_ADMIN_EMAIL)) {
                $isAuthorized = true;
                $currentUserEmail = $admin['email'];
            }
        } catch (PDOException $e) {
            // Ignore
        }
    }
}

// Check if this is initial setup (no admins exist yet)
$isInitialSetup = false;
if ($pdo) {
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM admin_user");
        $result = $stmt->fetch();
        if ((int)$result['count'] === 0) {
            $isInitialSetup = true;
            $isAuthorized = true; // Allow first admin creation
        }
    } catch (PDOException $e) {
        $isInitialSetup = true;
        $isAuthorized = true;
    }
}

// Handle form submission
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $message = 'Invalid security token. Please refresh and try again.';
        $messageType = 'error';
    } elseif (!$isAuthorized) {
        $message = 'Access denied. Only the authorized super admin can create admin accounts.';
        $messageType = 'error';
    } elseif ($pdo === null) {
        $message = 'Database connection failed.';
        $messageType = 'error';
    } else {
        $name = trim($_POST['name'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        $role = $_POST['role'] ?? 'admin';
        
        // Validation
        $errors = [];
        
        if (strlen($name) < 2) {
            $errors[] = 'Name must be at least 2 characters.';
        }
        
        if (strlen($username) < 3) {
            $errors[] = 'Username must be at least 3 characters.';
        } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
            $errors[] = 'Username can only contain letters, numbers, and underscores.';
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please enter a valid email address.';
        }
        
        if (strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters.';
        }
        
        if ($password !== $confirmPassword) {
            $errors[] = 'Passwords do not match.';
        }
        
        if (!in_array($role, ['super_admin', 'admin', 'staff'])) {
            $errors[] = 'Invalid role selected.';
        }
        
        // Check if email already exists
        if (empty($errors)) {
            try {
                $stmt = $pdo->prepare("SELECT id FROM admin_user WHERE email = ?");
                $stmt->execute([$email]);
                if ($stmt->fetch()) {
                    $errors[] = 'An account with this email already exists.';
                }
            } catch (PDOException $e) {
                $errors[] = 'Database error checking email.';
            }
        }
        
        // Check if username already exists
        if (empty($errors) && !empty($username)) {
            try {
                $stmt = $pdo->prepare("SELECT id FROM admin_user WHERE username = ?");
                $stmt->execute([$username]);
                if ($stmt->fetch()) {
                    $errors[] = 'This username is already taken.';
                }
            } catch (PDOException $e) {
                $errors[] = 'Database error checking username.';
            }
        }
        
        if (!empty($errors)) {
            $message = implode('<br>', $errors);
            $messageType = 'error';
        } else {
            // Create the account
            try {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                
                // If this is initial setup and email matches super admin, make them super_admin
                if ($isInitialSetup && strtolower($email) === strtolower(SUPER_ADMIN_EMAIL)) {
                    $role = 'super_admin';
                }
                
                $stmt = $pdo->prepare("INSERT INTO admin_user (name, username, email, password, role, status, phone, created_at) VALUES (?, ?, ?, ?, ?, 'active', ?, NOW())");
                $stmt->execute([$name, $username ?: null, $email, $hashedPassword, $role, $phone ?: null]);
                
                $message = 'Admin account created successfully!';
                $messageType = 'success';
                
                // Clear form
                $_POST = [];
                
            } catch (PDOException $e) {
                $message = 'Failed to create account: ' . $e->getMessage();
                $messageType = 'error';
            }
        }
    }
    
    // Regenerate CSRF token after form submission
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    $csrfToken = $_SESSION['csrf_token'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Admin Account - Emergency Communication System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
        }
        .container {
            width: 100%;
            max-width: 480px;
        }
        .card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.3);
            overflow: hidden;
        }
        .card-header {
            background: linear-gradient(135deg, #e94560 0%, #ff6b6b 100%);
            padding: 2rem;
            text-align: center;
            color: white;
        }
        .card-header .icon {
            width: 70px;
            height: 70px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            font-size: 1.75rem;
        }
        .card-header h1 {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        .card-header p {
            font-size: 0.9rem;
            opacity: 0.9;
        }
        .card-body {
            padding: 2rem;
        }
        .alert {
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
            font-size: 0.9rem;
            line-height: 1.5;
        }
        .alert i {
            margin-top: 2px;
        }
        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border-left: 4px solid #dc2626;
        }
        .alert-success {
            background: #dcfce7;
            color: #166534;
            border-left: 4px solid #22c55e;
        }
        .alert-warning {
            background: #fef3c7;
            color: #92400e;
            border-left: 4px solid #f59e0b;
        }
        .alert-info {
            background: #dbeafe;
            color: #1e40af;
            border-left: 4px solid #3b82f6;
        }
        .form-group {
            margin-bottom: 1.25rem;
        }
        .form-label {
            display: block;
            font-weight: 600;
            color: #374151;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
        }
        .form-label .required {
            color: #dc2626;
        }
        .form-control {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.2s;
            background: #f9fafb;
        }
        .form-control:focus {
            outline: none;
            border-color: #e94560;
            background: white;
            box-shadow: 0 0 0 3px rgba(233, 69, 96, 0.1);
        }
        select.form-control {
            cursor: pointer;
        }
        .btn {
            width: 100%;
            padding: 1rem;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }
        .btn-primary {
            background: linear-gradient(135deg, #e94560 0%, #ff6b6b 100%);
            color: white;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(233, 69, 96, 0.3);
        }
        .btn-primary:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }
        .form-footer {
            text-align: center;
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid #e5e7eb;
        }
        .form-footer a {
            color: #e94560;
            text-decoration: none;
            font-weight: 500;
        }
        .form-footer a:hover {
            text-decoration: underline;
        }
        .security-badge {
            background: #fef3c7;
            border: 1px solid #fcd34d;
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 1.5rem;
            font-size: 0.85rem;
            color: #92400e;
        }
        .security-badge i {
            margin-right: 0.5rem;
        }
        .db-error {
            background: #fee2e2;
            border: 1px solid #fecaca;
            border-radius: 10px;
            padding: 1.5rem;
            text-align: center;
        }
        .db-error i {
            font-size: 2rem;
            color: #dc2626;
            margin-bottom: 1rem;
        }
        .db-error h3 {
            color: #991b1b;
            margin-bottom: 0.5rem;
        }
        .db-error p {
            color: #7f1d1d;
            font-size: 0.9rem;
        }
        .access-denied {
            text-align: center;
            padding: 2rem;
        }
        .access-denied .icon {
            width: 80px;
            height: 80px;
            background: #fee2e2;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            font-size: 2rem;
            color: #dc2626;
        }
        .access-denied h2 {
            color: #991b1b;
            margin-bottom: 1rem;
        }
        .access-denied p {
            color: #666;
            margin-bottom: 1.5rem;
        }
        .password-hint {
            font-size: 0.8rem;
            color: #6b7280;
            margin-top: 0.25rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="card-header">
                <div class="icon">
                    <i class="fas fa-user-shield"></i>
                </div>
                <h1>Create Admin Account</h1>
                <p>Emergency Communication System</p>
            </div>
            
            <div class="card-body">
                <?php if ($pdo === null): ?>
                    <!-- Database Error -->
                    <div class="db-error">
                        <i class="fas fa-database"></i>
                        <h3>Database Connection Failed</h3>
                        <p><?= htmlspecialchars($dbError ?? 'Unknown error') ?></p>
                        <br>
                        <a href="api/setup_remote_database.php" class="btn btn-primary" style="width: auto; display: inline-flex;">
                            <i class="fas fa-cog"></i> Run Database Setup
                        </a>
                    </div>
                    
                <?php elseif (!$isAuthorized && !$isInitialSetup): ?>
                    <!-- Access Denied -->
                    <div class="access-denied">
                        <div class="icon">
                            <i class="fas fa-lock"></i>
                        </div>
                        <h2>Access Denied</h2>
                        <p>Only the super administrator can create admin accounts.</p>
                        <p><strong>Authorized email:</strong><br><?= htmlspecialchars(SUPER_ADMIN_EMAIL_MASKED) ?></p>
                        <?php if ($currentUserEmail): ?>
                            <p style="color: #dc2626; font-size: 0.9rem;">
                                <i class="fas fa-info-circle"></i> You are logged in as: <?= htmlspecialchars($currentUserEmail) ?>
                            </p>
                        <?php endif; ?>
                        <a href="login.php" class="btn btn-primary" style="margin-top: 1rem;">
                            <i class="fas fa-sign-in-alt"></i> Login as Super Admin
                        </a>
                    </div>
                    
                <?php else: ?>
                    <!-- Show Form -->
                    
                    <?php if ($isInitialSetup): ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i>
                            <div>
                                <strong>Initial Setup</strong><br>
                                No admin accounts exist yet. Create the first admin account to get started.
                                <?php if (strtolower($_POST['email'] ?? '') !== strtolower(SUPER_ADMIN_EMAIL)): ?>
                                    <br><br><strong>Tip:</strong> Use the authorized super admin email to become the super admin.
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="security-badge">
                            <i class="fas fa-shield-alt"></i>
                            <strong>Secure Mode:</strong> Only authorized super admin can create accounts.
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($message): ?>
                        <div class="alert alert-<?= $messageType ?>">
                            <i class="fas <?= $messageType === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle' ?>"></i>
                            <div><?= $message ?></div>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                        
                        <div class="form-group">
                            <label class="form-label">
                                Full Name <span class="required">*</span>
                            </label>
                            <input type="text" name="name" class="form-control" required 
                                   value="<?= htmlspecialchars($_POST['name'] ?? '') ?>"
                                   placeholder="Enter full name">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">
                                Username <span class="required">*</span>
                            </label>
                            <input type="text" name="username" class="form-control" required 
                                   value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                                   placeholder="Enter username" pattern="[a-zA-Z0-9_]+">
                            <div class="password-hint">Only letters, numbers, and underscores</div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">
                                Email Address <span class="required">*</span>
                            </label>
                            <input type="email" name="email" class="form-control" required 
                                   value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                                   placeholder="admin@example.com">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Phone Number</label>
                            <input type="tel" name="phone" class="form-control" 
                                   value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>"
                                   placeholder="+63XXXXXXXXXX">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">
                                Password <span class="required">*</span>
                            </label>
                            <input type="password" name="password" class="form-control" required 
                                   placeholder="Enter password" minlength="8">
                            <div class="password-hint">Minimum 8 characters</div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">
                                Confirm Password <span class="required">*</span>
                            </label>
                            <input type="password" name="confirm_password" class="form-control" required 
                                   placeholder="Confirm password" minlength="8">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">
                                Role <span class="required">*</span>
                            </label>
                            <select name="role" class="form-control" required>
                                <option value="admin" <?= ($_POST['role'] ?? '') === 'admin' ? 'selected' : '' ?>>Administrator</option>
                                <option value="staff" <?= ($_POST['role'] ?? '') === 'staff' ? 'selected' : '' ?>>Staff</option>
                                <?php if ($isInitialSetup): ?>
                                    <option value="super_admin" <?= ($_POST['role'] ?? '') === 'super_admin' ? 'selected' : '' ?>>Super Admin</option>
                                <?php endif; ?>
                            </select>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-user-plus"></i> Create Admin Account
                        </button>
                    </form>
                    
                    <div class="form-footer">
                        <a href="login.php"><i class="fas fa-arrow-left"></i> Back to Login</a>
                        <?php if (!$isInitialSetup): ?>
                            &nbsp;|&nbsp;
                            <a href="sidebar/dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
