<?php
/**
 * Web Login Handler for Admin Panel
 * Sets PHP sessions after successful authentication
 */

session_start();
header('Content-Type: application/json');

// Include DB connection
require_once 'db_connect.php';
require_once 'activity_logger.php';

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

if (empty($email) || empty($plainPassword)) {
    echo json_encode(["success" => false, "message" => "Email and password are required."]);
    exit();
}

// Verify reCAPTCHA (skip if OTP is already verified, as we've done additional verification)
if (!$otpVerified && empty($recaptchaResponse)) {
    echo json_encode(["success" => false, "message" => "Please complete the reCAPTCHA verification."]);
    exit();
}

// Validate reCAPTCHA with Google (skip if OTP already verified)
$recaptchaValid = false;
if ($otpVerified) {
    // If OTP is verified, we can skip reCAPTCHA verification as additional security is already provided
    $recaptchaValid = true;
} else if (!empty($recaptchaResponse)) {
    $recaptchaSecretKey = '6LeXXjcsAAAAAMchkaNgXKDH32lXqc8-yDvPbzIN'; // Secret key for server-side verification
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
            'content' => http_build_query($recaptchaData)
        ]
    ];

    $recaptchaContext = stream_context_create($recaptchaOptions);
    $recaptchaResult = @file_get_contents($recaptchaUrl, false, $recaptchaContext);
    $recaptchaJson = json_decode($recaptchaResult, true);

    // Validate reCAPTCHA v2 response
    if (isset($recaptchaJson['success']) && $recaptchaJson['success']) {
        $recaptchaValid = true;
    } else {
        $errorCodes = $recaptchaJson['error-codes'] ?? [];
        error_log('reCAPTCHA verification failed. Errors: ' . implode(', ', $errorCodes));
        echo json_encode(["success" => false, "message" => "reCAPTCHA verification failed. Please try again."]);
        exit();
    }
}

if (!$recaptchaValid && !$otpVerified) {
    echo json_encode(["success" => false, "message" => "reCAPTCHA verification failed. Please try again."]);
    exit();
}

try {
    // Query user from database - check for admin user_type
    $stmt = $pdo->prepare("SELECT id, name, email, password, user_type, status FROM users WHERE email = ?");
    
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
    $user = $stmt->fetch();

    // Verify password
    if ($user && password_verify($plainPassword, $user['password'])) {
        // Check if user is admin
        if ($user['user_type'] !== 'admin') {
            echo json_encode(["success" => false, "message" => "Access denied. Admin account required."]);
            exit();
        }
        
        // Check account status
        if ($user['status'] === 'pending_approval') {
            echo json_encode([
                "success" => false, 
                "message" => "Your account is pending approval from an administrator. You will be able to log in once your account has been approved.",
                "pending_approval" => true
            ]);
            exit();
        }
        
        if ($user['status'] !== 'active') {
            echo json_encode(["success" => false, "message" => "Account is not active. Please contact administrator."]);
            exit();
        }
        
        // OTP verification is ALWAYS required for admin login
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
                "username" => $user['name']
            ]);
            exit();
        }
        
        // OTP verified (or skipped if not required), complete login
        // Set session variables
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_user_id'] = $user['id'];
        $_SESSION['admin_username'] = $user['name'];
        $_SESSION['admin_email'] = $user['email'];
        $_SESSION['admin_token'] = bin2hex(random_bytes(16));
        
        // Log successful login
        $loginLogId = logAdminLogin($user['id'], $email, 'success');
        if ($loginLogId) {
            $_SESSION['admin_login_log_id'] = $loginLogId;
        }
        
        // Log activity
        logAdminActivity($user['id'], 'login', 'Admin logged in successfully');
        
        // Clear OTP session after successful login
        unset($_SESSION['admin_login_otp_verified']);
        unset($_SESSION['admin_login_otp_email']);
        unset($_SESSION['admin_login_otp_code']);
        unset($_SESSION['admin_login_otp_expires']);

        echo json_encode([
            "success" => true,
            "message" => "Login successful!",
            "user_id" => $user['id'],
            "username" => $user['name'],
            "email" => $user['email']
        ]);
    } else {
        // Log failed login attempt
        if (isset($user) && $user) {
            logAdminLogin($user['id'], $email, 'failed');
            logAdminActivity($user['id'], 'login_failed', 'Failed login attempt - invalid password');
        } else {
            // User not found - log with email only (admin_id will be 0 or NULL)
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









