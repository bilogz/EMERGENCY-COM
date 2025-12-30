<?php
/**
 * Web Login Handler for Admin Panel
 * Sets PHP sessions after successful authentication
 */

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('log_errors', 1);
error_reporting(E_ALL);

// Set error handler to catch fatal errors
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error !== NULL && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        header('Content-Type: application/json');
        http_response_code(500);
        echo json_encode([
            "success" => false,
            "message" => "Fatal error: " . $error['message'] . " in " . $error['file'] . " on line " . $error['line']
        ]);
    }
});

session_start();
header('Content-Type: application/json');

// Include DB connection with error handling
try {
    require_once 'db_connect.php';
} catch (Throwable $e) {
    echo json_encode(["success" => false, "message" => "Database connection error: " . $e->getMessage()]);
    exit();
}

// Check if $pdo is available
if (!isset($pdo) || $pdo === null) {
    echo json_encode(["success" => false, "message" => "Database connection failed. Please check server configuration."]);
    exit();
}

// Include activity logger with error handling (non-critical)
try {
    require_once 'activity_logger.php';
} catch (Throwable $e) {
    // Activity logging is non-critical, continue without it
    error_log('Activity logger failed to load: ' . $e->getMessage());
    
    // Define stub functions if activity_logger failed
    if (!function_exists('logAdminLogin')) {
        function logAdminLogin($adminId, $email, $status = 'success') { return false; }
    }
    if (!function_exists('logAdminActivity')) {
        function logAdminActivity($adminId, $action, $description = null, $metadata = null) { return false; }
    }
}

// Helper: fetch a specific header value (works across PHP SAPIs)
function getHeaderValue($name) {
    $headers = function_exists('getallheaders') ? getallheaders() : [];
    foreach ($headers as $key => $value) {
        if (strtolower($key) === strtolower($name)) {
            return $value;
        }
    }
    $serverKey = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
    return $_SERVER[$serverKey] ?? '';
}

// Enforce shared admin API key when configured
$expectedApiKey = getSecureConfig('ADMIN_API_KEY', '');
if (!empty($expectedApiKey)) {
    $providedKey = getHeaderValue('X-Admin-Api-Key');
    if (empty($providedKey) || !hash_equals($expectedApiKey, $providedKey)) {
        http_response_code(401);
        echo json_encode(["success" => false, "message" => "Unauthorized request."]);
        exit();
    }
}

// Get POST data (can be JSON or form data)
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// If JSON decode failed, try form data
if ($data === null) {
    $data = $_POST;
}

// Validate required fields
if (!isset($data['email']) || !isset($data['password'])) {
    echo json_encode(["success" => false, "message" => "Missing email or password."]);
    exit();
}

$email = trim($data['email']);
$plainPassword = $data['password'];
$recaptchaResponse = $data['recaptcha_response'] ?? '';
$otpVerified = $data['otp_verified'] ?? false;
$requireOtp = filter_var(getSecureConfig('ADMIN_REQUIRE_OTP', true), FILTER_VALIDATE_BOOLEAN);

if (empty($email) || empty($plainPassword)) {
    echo json_encode(["success" => false, "message" => "Email and password are required."]);
    exit();
}

// Verify reCAPTCHA v3 (skip if OTP is already verified, as we've done additional verification)
if (!$otpVerified && empty($recaptchaResponse)) {
    echo json_encode(["success" => false, "message" => "Security verification failed. Please refresh the page and try again."]);
    exit();
}

// Validate reCAPTCHA v3 with Google (skip if OTP already verified)
$recaptchaValid = false;
$recaptchaScore = 0;
if ($otpVerified) {
    // If OTP is verified, we can skip reCAPTCHA verification as additional security is already provided
    $recaptchaValid = true;
} else if (!empty($recaptchaResponse)) {
    // Load reCAPTCHA secret key from config
    $recaptchaSecretKey = getSecureConfig('RECAPTCHA_SECRET_KEY', '');
    if (empty($recaptchaSecretKey)) {
        echo json_encode(["success" => false, "message" => "Security verification is not configured. Please contact an administrator."]);
        exit();
    }
    $recaptchaUrl = 'https://www.google.com/recaptcha/api/siteverify';
    $recaptchaData = [
        'secret' => $recaptchaSecretKey,
        'response' => $recaptchaResponse,
        'remoteip' => $_SERVER['REMOTE_ADDR'] ?? ''
    ];

    $recaptchaOptions = [
        'http' => [
            'method' => 'POST',
            'header' => 'Content-Type: application/x-www-form-urlencoded',
            'content' => http_build_query($recaptchaData),
            'timeout' => 10
        ]
    ];

    $recaptchaContext = stream_context_create($recaptchaOptions);
    $recaptchaResult = @file_get_contents($recaptchaUrl, false, $recaptchaContext);
    $recaptchaJson = json_decode($recaptchaResult, true);

    // Validate reCAPTCHA v3 response
    // v3 returns a score from 0.0 to 1.0 (1.0 = very likely human, 0.0 = very likely bot)
    if (isset($recaptchaJson['success']) && $recaptchaJson['success']) {
        $recaptchaScore = $recaptchaJson['score'] ?? 0;
        $recaptchaAction = $recaptchaJson['action'] ?? '';
        
        // Minimum score threshold (0.5 is recommended, lower = more permissive)
        $minScore = 0.3;
        
        if ($recaptchaScore >= $minScore) {
            $recaptchaValid = true;
            error_log("reCAPTCHA v3 passed - Score: {$recaptchaScore}, Action: {$recaptchaAction}");
        } else {
            error_log("reCAPTCHA v3 score too low - Score: {$recaptchaScore}, Action: {$recaptchaAction}");
            echo json_encode(["success" => false, "message" => "Security verification failed. Please try again or contact support."]);
            exit();
        }
    } else {
        $errorCodes = $recaptchaJson['error-codes'] ?? [];
        error_log('reCAPTCHA v3 verification failed. Errors: ' . implode(', ', $errorCodes));
        echo json_encode(["success" => false, "message" => "Security verification failed. Please refresh and try again."]);
        exit();
    }
}

if (!$recaptchaValid && !$otpVerified) {
    echo json_encode(["success" => false, "message" => "Security verification failed. Please try again."]);
    exit();
}

try {
    // Check if admin_user table exists, otherwise fall back to users table
    $useAdminUserTable = false;
    try {
        $pdo->query("SELECT 1 FROM admin_user LIMIT 1");
        $useAdminUserTable = true;
    } catch (PDOException $e) {
        // admin_user table doesn't exist, use users table (backward compatibility)
    }
    
    if ($useAdminUserTable) {
        // Query from admin_user table
        $stmt = $pdo->prepare("SELECT id, user_id, name, email, password, role, status FROM admin_user WHERE email = ?");
    } else {
        // Fallback to users table for backward compatibility
        $stmt = $pdo->prepare("SELECT id, name, email, password, user_type, status FROM users WHERE email = ? AND user_type = 'admin'");
    }
    
    if (!$stmt) {
        $errorInfo = $pdo->errorInfo();
        error_log("PDO Prepare Error: " . $errorInfo[2]);
        echo json_encode([
            "success" => false,
            "message" => "Database prepare error: " . $errorInfo[2]
        ]);
        exit();
    }

    $stmt->execute([$email]);
    $admin = $stmt->fetch();

    // Verify password
    if ($admin && password_verify($plainPassword, $admin['password'])) {
        // Check account status
        if ($admin['status'] === 'pending_approval') {
            echo json_encode([
                "success" => false, 
                "message" => "Your account is pending approval from an administrator. You will be able to log in once your account has been approved.",
                "pending_approval" => true
            ]);
            exit();
        }
        
        if ($admin['status'] !== 'active') {
            echo json_encode(["success" => false, "message" => "Account is not active. Please contact administrator."]);
            exit();
        }
        
        if ($requireOtp) {
            // Check if OTP verification flag is passed or exists in session
            $otpVerifiedFlag = $data['otp_verified'] ?? false;
            $sessionOtpVerified = $_SESSION['admin_login_otp_verified'] ?? false;
            $sessionOtpEmail = $_SESSION['admin_login_otp_email'] ?? '';
            
            // OTP must be verified either via session or explicit flag
            $isOtpVerified = ($otpVerifiedFlag === true) || ($sessionOtpVerified === true && $sessionOtpEmail === $email);
            
            if (!$isOtpVerified) {
                // Credentials valid but OTP not verified yet - require OTP
                echo json_encode([
                    "success" => false,
                    "message" => "Email verification required. Please verify your email with the OTP code.",
                    "requires_otp" => true,
                    "email" => $email,
                    "username" => $admin['name']
                ]);
                exit();
            }
        }
        
        // Update last_login timestamp in admin_user table
        if ($useAdminUserTable) {
            try {
                $updateStmt = $pdo->prepare("UPDATE admin_user SET last_login = NOW() WHERE id = ?");
                $updateStmt->execute([$admin['id']]);
            } catch (PDOException $e) {
                error_log('Failed to update last_login: ' . $e->getMessage());
            }
        }
        
        // OTP verified (or skipped if not required), complete login
        // Set session variables - use admin_user.id (not user_id)
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_user_id'] = $useAdminUserTable ? $admin['id'] : $admin['id']; // admin_user.id
        $_SESSION['admin_user_table_id'] = $useAdminUserTable ? $admin['id'] : null; // Store admin_user.id separately
        $_SESSION['admin_username'] = $admin['name'];
        $_SESSION['admin_email'] = $admin['email'];
        $_SESSION['admin_role'] = $useAdminUserTable ? ($admin['role'] ?? 'admin') : 'admin';
        $_SESSION['admin_token'] = bin2hex(random_bytes(16));
        
        // Log successful login - use admin_user.id for admin_user table, otherwise use user id
        $adminIdForLog = $useAdminUserTable ? $admin['id'] : ($admin['id'] ?? 0);
        $loginLogId = logAdminLogin($adminIdForLog, $email, 'success');
        if ($loginLogId) {
            $_SESSION['admin_login_log_id'] = $loginLogId;
        }
        
        // Log activity
        logAdminActivity($adminIdForLog, 'login', 'Admin logged in successfully');
        
        // Clear OTP session after successful login
        unset($_SESSION['admin_login_otp_verified']);
        unset($_SESSION['admin_login_otp_email']);
        unset($_SESSION['admin_login_otp_code']);
        unset($_SESSION['admin_login_otp_expires']);

        echo json_encode([
            "success" => true,
            "message" => "Login successful!",
            "user_id" => $admin['id'],
            "username" => $admin['name'],
            "email" => $admin['email'],
            "role" => $useAdminUserTable ? ($admin['role'] ?? 'admin') : 'admin'
        ]);
    } else {
        // Log failed login attempt
        if (isset($admin) && $admin) {
            $adminIdForLog = $useAdminUserTable ? $admin['id'] : ($admin['id'] ?? 0);
            logAdminLogin($adminIdForLog, $email, 'failed');
            logAdminActivity($adminIdForLog, 'login_failed', 'Failed login attempt - invalid password');
        } else {
            // Admin not found - log with email only (admin_id will be 0)
            try {
                $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
                $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
                $stmt = $pdo->prepare("
                    INSERT INTO admin_login_logs (admin_id, email, login_status, ip_address, user_agent)
                    VALUES (0, ?, 'failed', ?, ?)
                ");
                $stmt->execute([$email, $ipAddress, $userAgent]);
            } catch (PDOException $e) {
                error_log('Failed login log error: ' . $e->getMessage());
            }
        }
        echo json_encode(["success" => false, "message" => "Invalid email or password."]);
    }

} catch (PDOException $e) {
    error_log("Login PDO Exception: " . $e->getMessage());
    echo json_encode([
        "success" => false, 
        "message" => "Database error occurred. Please try again."
    ]);
} catch (Exception $e) {
    error_log("Login General Exception: " . $e->getMessage());
    echo json_encode([
        "success" => false, 
        "message" => "Server error occurred. Please try again."
    ]);
}
?>









