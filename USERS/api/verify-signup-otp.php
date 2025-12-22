<?php
/**
 * Verify Signup OTP API
 * Verifies the OTP code for signup
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
    $phoneNormalized = isset($_SESSION['signup_otp_phone']) ? $_SESSION['signup_otp_phone'] : null;
    
    $otpValid = false;
    $otpRecord = null;
    
    if ($phoneNormalized) {
        // Query OTP from database
        $stmt = $pdo->prepare("
            SELECT id, phone, otp_code, status, expires_at, attempts, max_attempts
            FROM otp_verifications
            WHERE phone = ? AND otp_code = ? AND purpose = 'signup' AND status = 'pending'
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
            
            // Check attempt limit (default max_attempts is 5)
            $maxAttempts = $otpRecord['max_attempts'] ?? 5;
            if ($otpRecord['attempts'] >= $maxAttempts) {
                echo json_encode(["success" => false, "message" => "Maximum verification attempts exceeded. Please request a new code."]);
                exit();
            }
            
            // Increment attempts
            $stmt = $pdo->prepare("UPDATE otp_verifications SET attempts = attempts + 1 WHERE id = ?");
            $stmt->execute([$otpRecord['id']]);
            
            $otpValid = true;
            
            // Mark OTP as verified (used)
            $stmt = $pdo->prepare("UPDATE otp_verifications SET status = 'verified' WHERE id = ?");
            $stmt->execute([$otpRecord['id']]);
        }
    }
    
    // Fallback to session verification if database check failed
    if (!$otpValid) {
        if (!isset($_SESSION['signup_otp_code']) || !isset($_SESSION['signup_otp_expires'])) {
            echo json_encode(["success" => false, "message" => "No verification code found. Please request a new code."]);
            exit();
        }
        
        if (time() > $_SESSION['signup_otp_expires']) {
            echo json_encode(["success" => false, "message" => "Verification code has expired. Please request a new code."]);
            exit();
        }
        
        if ($_SESSION['signup_otp_code'] !== $otp) {
            echo json_encode(["success" => false, "message" => "Invalid verification code. Please try again."]);
            exit();
        }
        
        $otpValid = true;
    }
    
    if ($otpValid) {
        // Mark OTP as verified in session
        $_SESSION['signup_otp_verified'] = true;
        
        echo json_encode([
            "success" => true,
            "message" => "Verification code is valid. Completing your account setup..."
        ]);
    } else {
        echo json_encode([
            "success" => false,
            "message" => "Invalid verification code. Please try again."
        ]);
    }
    
} catch (PDOException $e) {
    error_log("Verify Signup OTP PDO Exception: " . $e->getMessage());
    echo json_encode([
        "success" => false,
        "message" => "Database error occurred. Please try again."
    ]);
} catch (Exception $e) {
    error_log("Verify Signup OTP General Exception: " . $e->getMessage());
    echo json_encode([
        "success" => false,
        "message" => "Server error occurred. Please try again."
    ]);
}

?>
