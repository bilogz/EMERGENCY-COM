<?php
// DEBUG: Show all errors for troubleshooting (remove in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// ADMIN/create-admin.php
require_once __DIR__ . '/api/db_connect.php';

// Check database connection
if ($pdo === null) {
    die('
    <!DOCTYPE html>
    <html>
    <head><title>Database Error</title></head>
    <body style="font-family: Arial; padding: 2rem;">
        <h1>Database Connection Error</h1>
        <p>The database connection failed. Please ensure:</p>
        <ul>
            <li>The database server is accessible</li>
            <li>You have run <a href="api/setup_remote_database.php">setup_remote_database.php</a> to create the database</li>
            <li>The database credentials are correct</li>
        </ul>
        <p><strong>Error:</strong> ' . htmlspecialchars($dbError ?? 'Unknown error') . '</p>
        <p><a href="api/setup_remote_database.php">Click here to set up the database</a></p>
    </body>
    </html>');
}

$message = '';
$success = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $role = $_POST['role'] ?? 'staff';

    // Basic validation
    if (!$full_name || !$username || !$email || !$password || !$confirm_password) {
        $message = 'Please fill in all fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'Please enter a valid email address.';
    } elseif (strlen($password) < 6) {
        $message = 'Password must be at least 6 characters.';
    } elseif ($password !== $confirm_password) {
        $message = 'Passwords do not match.';
    } else {
        // Check for duplicate username or email
        try {
            $checkStmt = $pdo->prepare("SELECT id FROM users WHERE (username = ? AND username IS NOT NULL AND username != '') OR email = ? LIMIT 1");
            $checkStmt->execute([$username, $email]);
            if ($checkStmt->fetch()) {
                $message = 'An account with this username or email already exists.';
            } else {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                try {
                    // Check if username column exists, if not use email as username
                    $stmt = $pdo->prepare("INSERT INTO users (name, username, email, password, status, user_type) VALUES (?, ?, ?, ?, ?, ?)");
                    $status = 'active';
                    $user_type = 'admin';
                    $stmt->execute([$full_name, $username, $email, $hashed_password, $status, $user_type]);
                    $message = 'Admin staff account created successfully!';
                    $success = true;
                } catch (PDOException $e) {
                    // If username column doesn't exist, try without it
                    if (strpos($e->getMessage(), 'username') !== false || strpos($e->getMessage(), 'Unknown column') !== false) {
                        try {
                            $stmt = $pdo->prepare("INSERT INTO users (name, email, password, status, user_type) VALUES (?, ?, ?, ?, ?)");
                            $stmt->execute([$full_name, $email, $hashed_password, $status, $user_type]);
                            $message = 'Admin staff account created successfully! (Note: username column not found, using email only)';
                            $success = true;
                        } catch (PDOException $e2) {
                            $message = 'Database error: ' . $e2->getMessage();
                        }
                    } else {
                        $message = 'Database error: ' . $e->getMessage();
                    }
                }
            }
        } catch (PDOException $e) {
            $message = 'Database error checking duplicates: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Create Admin Staff Account</title>
    <link rel="stylesheet" href="header/css/forms.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            background: var(--bg-color-1, #f7f9fa);
            padding: 1rem;
        }
        .login-container {
            width: 100%;
            max-width: 450px;
            background: var(--card-bg-1, #fff);
            border-radius: 16px;
            border: 1px solid var(--border-color-1, #e0e0e0);
            box-shadow: 0 8px 24px var(--shadow-1, rgba(76,138,137,0.08));
            padding: 3rem 2.5rem;
            animation: fadeInUp 0.5s ease;
        }
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .login-header { text-align: center; margin-bottom: 2.5rem; }
        .login-logo {
            width: 80px; height: 80px; margin: 0 auto 1.5rem;
            display: flex; align-items: center; justify-content: center;
            background: var(--primary-color-1, #3a7675);
            border-radius: 16px;
            box-shadow: 0 4px 12px rgba(76,138,137,0.3);
        }
        .login-logo img { width: 60px; height: 60px; object-fit: contain; }
        .login-title { font-size: 1.75rem; font-weight: 700; color: var(--text-color-1, #222); margin-bottom: 0.5rem; }
        .login-subtitle { font-size: 0.95rem; color: var(--text-secondary-1, #666); line-height: 1.5; }
        .form-group { margin-bottom: 1.5rem; position: relative; }
        .form-label { display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.75rem; font-weight: 500; color: var(--text-color-1, #222); font-size: 14px; }
        .form-label i { color: var(--primary-color-1, #3a7675); font-size: 16px; }
        .form-control { width: 100%; padding: 0.875rem 1rem; padding-left: 2.75rem; border: 2px solid var(--border-color-1, #e0e0e0); border-radius: 8px; font-size: 14px; font-family: var(--font-family-1, inherit); background-color: var(--bg-color-1, #f7f9fa); color: var(--text-color-1, #222); transition: all 0.3s ease; outline: none; }
        .form-control:focus { border-color: var(--primary-color-1, #3a7675); box-shadow: 0 0 0 3px rgba(76,138,137,0.1); }
        .form-control::placeholder { color: var(--text-secondary-1, #888); opacity: 0.6; }
        .input-icon { position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: var(--text-secondary-1, #888); font-size: 16px; pointer-events: none; transition: color 0.3s ease; }
        .form-control:focus + .input-icon,
        .form-group:has(.form-control:focus) .input-icon { color: var(--primary-color-1, #3a7675); }
        .btn-login {
            width: 100%; padding: 0.875rem 1.5rem; background-color: var(--primary-color-1, #3a7675); color: white; border: none; border-radius: 8px; font-size: 15px; font-weight: 600; cursor: pointer; transition: all 0.3s ease; display: flex; align-items: center; justify-content: center; gap: 0.5rem; margin-top: 1rem;
        }
        .btn-login:hover { background-color: #4ca8a6; transform: translateY(-1px); box-shadow: 0 4px 12px rgba(76,138,137,0.3); }
        .btn-login:active { transform: translateY(0); }
        .btn-login:disabled { opacity: 0.6; cursor: not-allowed; transform: none; }
        .btn-login i { font-size: 16px; }
        .form-text.success-text { color: #28a745; font-weight: 500; }
        .form-text.error-text { color: #dc3545; font-weight: 500; }
        .form-text { display: block; margin-top: 0.5rem; font-size: 12px; color: var(--text-secondary-1, #888); }
        @media (max-width: 480px) {
            .login-container { padding: 2rem 1.5rem; }
            .login-title { font-size: 1.5rem; }
            .login-logo { width: 70px; height: 70px; }
            .login-logo img { width: 50px; height: 50px; }
        }
    </style>
    <script src="header/js/admin-form-validation.js" defer></script>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <div class="login-logo">
                <img src="header/images/logo.svg" alt="Logo" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                <i class="fas fa-user-shield" style="display: none;"></i>
            </div>
            <h1 class="login-title">Create Admin</h1>
            <p class="login-subtitle">Emergency Communication System<br>Administrative Panel</p>
        </div>
        <?php if ($message): ?>
            <div class="form-text <?= $success ? 'success-text' : 'error-text' ?>" style="margin-bottom:1rem;">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>
        <?php if (!$success): ?>
        <form method="post" action="" autocomplete="off">
            <div class="form-group">
                <label for="full_name" class="form-label"><i class="fas fa-user"></i> Full Name</label>
                <input type="text" name="full_name" id="full_name" class="form-control" required value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>">
                <i class="fas fa-user input-icon"></i>
            </div>
            <div class="form-group">
                <label for="username" class="form-label"><i class="fas fa-user-tag"></i> Username</label>
                <input type="text" name="username" id="username" class="form-control" required value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
                <i class="fas fa-user-tag input-icon"></i>
            </div>
            <div class="form-group">
                <label for="email" class="form-label"><i class="fas fa-envelope"></i> Email Address</label>
                <input type="email" name="email" id="email" class="form-control" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" placeholder="admin@example.com">
                <i class="fas fa-envelope input-icon"></i>
            </div>
            <div class="form-group">
                <label for="password" class="form-label"><i class="fas fa-lock"></i> Password</label>
                <input type="password" name="password" id="password" class="form-control" required minlength="6" placeholder="Enter password">
                <i class="fas fa-lock input-icon"></i>
            </div>
            <div class="form-group">
                <label for="confirm_password" class="form-label"><i class="fas fa-lock"></i> Confirm Password</label>
                <input type="password" name="confirm_password" id="confirm_password" class="form-control" required minlength="6" placeholder="Confirm password">
                <i class="fas fa-lock input-icon"></i>
            </div>
            <div class="form-group">
                <label for="role" class="form-label"><i class="fas fa-user-cog"></i> Role</label>
                <select name="role" id="role" class="form-control">
                    <option value="staff" <?= (($_POST['role'] ?? '') === 'staff' ? 'selected' : '') ?>>Staff</option>
                    <option value="admin" <?= (($_POST['role'] ?? '') === 'admin' ? 'selected' : '') ?>>Admin</option>
                </select>
                <i class="fas fa-user-cog input-icon"></i>
            </div>
            <button type="submit" class="btn-login">
                <i class="fas fa-user-plus"></i>
                Create Admin
            </button>
        </form>
        <div style="margin-top:1.5rem;text-align:center;">
            <a href="login.php" class="login-footer-link">Cancel</a>
        </div>
        <?php else: ?>
            <a href="create-admin.php" class="login-footer-link">Create another admin account</a>
        <?php endif; ?>
    </div>
</body>
</html>
