<?php
/**
 * Create Admin Account API
 * Only super_admin can create admin accounts
 * Accounts are stored in admin_user table
 */

session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/security-helpers.php';
require_once __DIR__ . '/activity_logger.php';

$response = ['success' => false, 'message' => ''];

try {
    // Ensure admin_user table exists
    ensureAdminUserTable($pdo);
    
    // Check authorization - only super_admin can create accounts
    $authCheck = checkAdminAuthorization($pdo);
    
    if (!$authCheck['authorized']) {
        $reasons = [
            'not_logged_in' => 'You must be logged in as a super admin to create admin accounts.',
            'invalid_session' => 'Invalid session. Please log in again.',
            'not_admin' => 'Access denied. Super admin privileges required.',
            'not_super_admin' => 'Access denied. Only super administrators can create admin accounts.',
            'database_error' => 'Database error occurred. Please try again.'
        ];
        
        $response['message'] = $reasons[$authCheck['reason']] ?? 'Unauthorized access.';
        echo json_encode($response);
        exit();
    }
    
    // Get JSON input or POST data
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        $input = $_POST;
    }
    
    // Validate CSRF token
    $csrfToken = $input['csrf_token'] ?? '';
    if (!verifyCSRFToken($csrfToken)) {
        $response['message'] = 'Invalid security token. Please refresh the page and try again.';
        echo json_encode($response);
        exit();
    }
    
    // Get and validate input
    $name = sanitizeInput($input['name'] ?? '', 'string');
    $username = sanitizeInput($input['username'] ?? '', 'string');
    $email = sanitizeInput($input['email'] ?? '', 'email');
    $password = $input['password'] ?? '';
    $confirmPassword = $input['confirm_password'] ?? '';
    $role = sanitizeInput($input['role'] ?? 'admin', 'string');
    $phone = sanitizeInput($input['phone'] ?? '', 'string');
    
    // Validation
    $errors = [];
    
    if (empty($name) || strlen($name) < 2) {
        $errors[] = 'Full name must be at least 2 characters.';
    }
    
    if (empty($username) || strlen($username) < 3) {
        $errors[] = 'Username must be at least 3 characters.';
    }
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }
    
    if (empty($password)) {
        $errors[] = 'Password is required.';
    } elseif (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters long.';
    }
    
    if ($password !== $confirmPassword) {
        $errors[] = 'Passwords do not match.';
    }
    
    // Validate password strength
    $passwordValidation = validatePasswordStrength($password);
    if (!$passwordValidation['valid']) {
        $errors = array_merge($errors, $passwordValidation['errors']);
    }
    
    // Validate role
    $allowedRoles = ['admin', 'staff'];
    if (!in_array($role, $allowedRoles)) {
        $errors[] = 'Invalid role selected.';
    }
    
    if (!empty($errors)) {
        $response['message'] = implode(' ', $errors);
        echo json_encode($response);
        exit();
    }
    
    // Check for duplicate email or username
    $checkStmt = $pdo->prepare("SELECT id FROM admin_user WHERE email = ? OR username = ? LIMIT 1");
    $checkStmt->execute([$email, $username]);
    if ($checkStmt->fetch()) {
        $response['message'] = 'An account with this email or username already exists.';
        echo json_encode($response);
        exit();
    }
    
    // Also check users table for email uniqueness
    $checkUsersStmt = $pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
    $checkUsersStmt->execute([$email]);
    if ($checkUsersStmt->fetch()) {
        $response['message'] = 'An account with this email already exists in the system.';
        echo json_encode($response);
        exit();
    }
    
    // Rate limiting check
    $ipAddress = getClientIP();
    $rateLimit = checkRateLimit($pdo, $ipAddress, 5, 3600);
    if (!$rateLimit['allowed']) {
        $resetTime = date('Y-m-d H:i:s', $rateLimit['reset_time']);
        $response['message'] = "Too many attempts. Please try again after {$resetTime}.";
        echo json_encode($response);
        exit();
    }
    
    // Hash password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    // Determine status - first admin is auto-approved, others need approval
    $statusCheck = $pdo->query("SELECT COUNT(*) as count FROM admin_user WHERE status = 'active'");
    $statusResult = $statusCheck->fetch();
    $activeAdminCount = (int)$statusResult['count'];
    
    $status = ($activeAdminCount === 0) ? 'active' : 'pending_approval';
    
    // Get creator ID
    $createdBy = null;
    if ($authCheck['admin_data'] && isset($authCheck['admin_data']['id'])) {
        $createdBy = $authCheck['admin_data']['id'];
    }
    
    // Create admin_user record ONLY (no duplication in users table)
    // user_id is set to NULL since admin accounts are separate from regular users
    $createAdminStmt = $pdo->prepare("INSERT INTO admin_user (user_id, name, username, email, password, role, status, phone, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $createAdminStmt->execute([null, $name, $username, $email, $hashedPassword, $role, $status, $phone, $createdBy]);
    $adminId = $pdo->lastInsertId();
    
    // Log the creation
    logAdminCreation($pdo, $adminId, $createdBy, $ipAddress);
    
    // Log activity
    if (function_exists('logActivity')) {
        logActivity(
            $pdo,
            $createdBy ? $_SESSION['admin_user_id'] : null,
            'admin_account_created',
            "Created admin account: {$name} ({$email})",
            $ipAddress
        );
    }
    
    $response['success'] = true;
    if ($status === 'active') {
        $response['message'] = "Admin account created successfully! The account is now active.";
    } else {
        $response['message'] = "Admin account created successfully! The account is pending approval from a super administrator.";
    }
    $response['admin_id'] = $adminId;
    $response['status'] = $status;
    
} catch (PDOException $e) {
    error_log("Create admin account database error: " . $e->getMessage());
    $response['message'] = 'Database error occurred. Please try again.';
} catch (Exception $e) {
    error_log("Create admin account error: " . $e->getMessage());
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
?>

