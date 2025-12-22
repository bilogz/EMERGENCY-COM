<?php
/**
 * Verify OTP API
 * Verifies the OTP code and completes user login
 */

session_start();
header('Content-Type: application/json');

// Include DB connection
require_once '../../ADMIN/api/db_connect.php';

// Get POST data
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if ($data === null) {
    $data = $_POST;
}

// Validate required fields
if (!isset($data['otp'])) {
    echo json_encode(["success" => false, "message" => "OTP code is required."]);
    exit();
}

$otp = trim($data['otp']);

if (empty($otp)) {
    echo json_encode(["success" => false, "message" => "OTP code is required."]);
    exit();
}

try {
    // First, try to verify from database
    $phoneNormalized = isset($_SESSION['otp_phone']) ? $_SESSION['otp_phone'] : null;
    $userId = isset($_SESSION['otp_user_id']) ? $_SESSION['otp_user_id'] : null;
    
    $otpValid = false;
    $otpRecord = null;
    
    if ($phoneNormalized) {
        // Query OTP from database
        $stmt = $pdo->prepare("
            SELECT id, user_id, phone, otp_code, status, expires_at, attempts, max_attempts
            FROM otp_verifications
            WHERE phone = ? AND otp_code = ? AND status = 'pending'
            ORDER BY created_at DESC
            LIMIT 1
        ");
        $stmt->execute([$phoneNormalized, $otp]);
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
            if ($otpRecord['attempts'] >= $otpRecord['max_attempts']) {
                echo json_encode(["success" => false, "message" => "Maximum verification attempts exceeded. Please request a new code."]);
                exit();
            }
            
            // Increment attempts
            $stmt = $pdo->prepare("UPDATE otp_verifications SET attempts = attempts + 1 WHERE id = ?");
            $stmt->execute([$otpRecord['id']]);
            
            $otpValid = true;
            $userId = $otpRecord['user_id'];
        }
    }
    
    // Fallback to session verification if database check failed
    if (!$otpValid) {
        if (!isset($_SESSION['otp_code']) || !isset($_SESSION['otp_expires'])) {
            echo json_encode(["success" => false, "message" => "No verification code found. Please request a new code."]);
            exit();
        }
        
        if (time() > $_SESSION['otp_expires']) {
            echo json_encode(["success" => false, "message" => "Verification code has expired. Please request a new code."]);
            exit();
        }
        
        if ($_SESSION['otp_code'] !== $otp) {
            echo json_encode(["success" => false, "message" => "Invalid verification code. Please try again."]);
            exit();
        }
        
        $otpValid = true;
        $userId = $_SESSION['otp_user_id'];
        $phoneNormalized = $_SESSION['otp_phone'];
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
    
    // Get email and name from session or database
    $email = $emailNormalized;
    $name = isset($_SESSION['otp_name']) ? $_SESSION['otp_name'] : null;
    
    // Get full user details from database
    $stmt = $pdo->prepare("SELECT id, name, email, phone FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    
    if (!$user) {
        echo json_encode(["success" => false, "message" => "User not found."]);
        exit();
    }
    
    // Clear OTP session data
    unset($_SESSION['otp_code']);
    unset($_SESSION['otp_email']);
    unset($_SESSION['otp_name']);
    unset($_SESSION['otp_user_id']);
    unset($_SESSION['otp_expires']);
    
    // Mark user's email as verified (if applicable)
    try {
        $stmt = $pdo->prepare("UPDATE users SET email_verified = 1, verification_date = NOW() WHERE id = ?");
        $stmt->execute([$user['id']]);
    } catch (PDOException $e) {
        error_log("Failed to update user email_verified: " . $e->getMessage());
    }

    // Set user session variables
    $_SESSION['user_logged_in'] = true;
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_name'] = $user['name'];
    $_SESSION['user_email'] = isset($user['email']) ? $user['email'] : $email;
    $_SESSION['user_phone'] = isset($user['phone']) ? $user['phone'] : null;
    $_SESSION['user_type'] = 'registered';
    $_SESSION['user_token'] = bin2hex(random_bytes(16));
    
    echo json_encode([
        "success" => true,
        "message" => "Email verified successfully!",
        "user_id" => $user['id'],
        "username" => $user['name'],
        "email" => isset($user['email']) ? $user['email'] : $email,
        "phone" => isset($user['phone']) ? $user['phone'] : null,
        "user_type" => "registered"
    ]);
    
} catch (PDOException $e) {
    error_log("Verify OTP PDO Exception: " . $e->getMessage());
    echo json_encode([
        "success" => false,
        "message" => "Database error occurred. Please try again."
    ]);
} catch (Exception $e) {
    error_log("Verify OTP General Exception: " . $e->getMessage());
    echo json_encode([
        "success" => false,
        "message" => "Server error occurred. Please try again."
    ]);
}
?>

