<?php
/**
 * Verify Login Email OTP
 * Verifies the OTP code sent to email and completes user login
 */

session_start();
header('Content-Type: application/json');

// Include DB connection
if (file_exists(__DIR__ . '/db_connect.php')) {
    require_once __DIR__ . '/db_connect.php';
} else {
    require_once '../../ADMIN/api/db_connect.php';
}

// Get POST data
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if ($data === null) {
    $data = $_POST;
}

// Validate required fields
if (!isset($data['otp']) || !isset($data['email'])) {
    echo json_encode(["success" => false, "message" => "OTP code and email are required."]);
    exit();
}

$otp = trim($data['otp']);
$email = trim($data['email']);

if (empty($otp) || empty($email)) {
    echo json_encode(["success" => false, "message" => "OTP code and email are required."]);
    exit();
}

try {
    // First, try to verify from database
    $otpValid = false;
    $otpRecord = null;
    $userId = null;
    
    // Query OTP from database
    $stmt = $pdo->prepare("
        SELECT id, user_id, email, otp_code, status, expires_at, attempts, max_attempts
        FROM otp_verifications
        WHERE email = ? AND otp_code = ? AND status = 'pending'
        ORDER BY created_at DESC
        LIMIT 1
    ");
    $stmt->execute([$email, $otp]);
    $otpRecord = $stmt->fetch();
    
    if ($otpRecord) {
        // Check if expired
        if (strtotime($otpRecord['expires_at']) < time()) {
            // Mark as expired
            $stmt = $pdo->prepare("UPDATE otp_verifications SET status = 'expired' WHERE id = ?");
            $stmt->execute([$otpRecord['id']]);
            
            echo json_encode(["success" => false, "message" => "Verification code has expired. Please request a new code."]);
            exit();
        }
        
        // Check attempt limit
        if ($otpRecord['attempts'] >= ($otpRecord['max_attempts'] ?? 5)) {
            echo json_encode(["success" => false, "message" => "Maximum verification attempts exceeded. Please request a new code."]);
            exit();
        }
        
        // Increment attempts
        $stmt = $pdo->prepare("UPDATE otp_verifications SET attempts = attempts + 1 WHERE id = ?");
        $stmt->execute([$otpRecord['id']]);
        
        $otpValid = true;
        $userId = $otpRecord['user_id'];
    }
    
    // Fallback to session verification if database check failed
    if (!$otpValid) {
        if (!isset($_SESSION['login_otp_code']) || !isset($_SESSION['login_otp_expires']) || !isset($_SESSION['login_otp_email'])) {
            echo json_encode(["success" => false, "message" => "No verification code found. Please request a new code."]);
            exit();
        }
        
        if ($_SESSION['login_otp_email'] !== $email) {
            echo json_encode(["success" => false, "message" => "Email mismatch. Please use the email you requested the code for."]);
            exit();
        }
        
        if (time() > $_SESSION['login_otp_expires']) {
            echo json_encode(["success" => false, "message" => "Verification code has expired. Please request a new code."]);
            exit();
        }
        
        if ($_SESSION['login_otp_code'] !== $otp) {
            echo json_encode(["success" => false, "message" => "Invalid verification code. Please try again."]);
            exit();
        }
        
        $otpValid = true;
        $userId = $_SESSION['login_otp_user_id'];
    }
    
    if (!$otpValid) {
        echo json_encode(["success" => false, "message" => "Invalid verification code. Please try again."]);
        exit();
    }
    
    // Mark OTP as verified in database
    if ($otpRecord) {
        $stmt = $pdo->prepare("UPDATE otp_verifications SET status = 'verified', verified_at = NOW() WHERE id = ?");
        $stmt->execute([$otpRecord['id']]);
    }
    
    // Get full user details from database
    $stmt = $pdo->prepare("SELECT id, name, email, phone FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    
    if (!$user) {
        echo json_encode(["success" => false, "message" => "User not found."]);
        exit();
    }
    
    // Clear OTP session data
    unset($_SESSION['login_otp_code']);
    unset($_SESSION['login_otp_email']);
    unset($_SESSION['login_otp_user_id']);
    unset($_SESSION['login_otp_expires']);
    
    // Set login session variables
    $_SESSION['user_logged_in'] = true;
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_name'] = $user['name'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_phone'] = $user['phone'] ?? null;
    $_SESSION['user_type'] = 'registered';
    $_SESSION['login_method'] = 'email_otp';
    
    // Mark user's email as verified
    try {
        $stmt = $pdo->prepare("UPDATE users SET email_verified = 1, verification_date = NOW() WHERE id = ?");
        $stmt->execute([$user['id']);
    } catch (PDOException $e) {
        error_log("Failed to update user email_verified: " . $e->getMessage());
    }
    
    echo json_encode([
        "success" => true,
        "message" => "Login successful!",
        "user_id" => $user['id'],
        "user_name" => $user['name'],
        "email" => $user['email'],
        "phone" => $user['phone'] ?? null,
        "user_type" => "registered"
    ]);
    
} catch (PDOException $e) {
    error_log("Verify Login Email OTP PDO Exception: " . $e->getMessage());
    echo json_encode([
        "success" => false,
        "message" => "Database error occurred. Please try again."
    ]);
} catch (Exception $e) {
    error_log("Verify Login Email OTP General Exception: " . $e->getMessage());
    echo json_encode([
        "success" => false,
        "message" => "Server error occurred. Please try again."
    ]);
}
?>

