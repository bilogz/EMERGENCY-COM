<?php
/**
 * Verify Admin OTP for Account Creation or Login
 * Validates 6-digit OTP code
 */

header('Content-Type: application/json');
require_once __DIR__ . '/db_connect.php';

session_start();

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
        echo json_encode(['success' => false, 'message' => 'Unauthorized request.']);
        exit();
    }
}

$response = ['success' => false, 'message' => ''];

try {
    // Get JSON input or POST data
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        $input = $_POST;
    }
    
    $otp = trim($input['otp'] ?? '');
    $email = trim($input['email'] ?? '');
    $purpose = $input['purpose'] ?? 'create'; // 'create' or 'login'
    
    // Validate OTP format
    if (empty($otp) || strlen($otp) !== 6 || !is_numeric($otp)) {
        throw new Exception('Invalid verification code format. Please enter a 6-digit code.');
    }
    
    // Get email from session if not provided
    if (empty($email)) {
        $sessionKey = $purpose === 'login' ? 'admin_login_otp' : 'admin_create_otp';
        $email = $_SESSION[$sessionKey . '_email'] ?? '';
    }
    
    if (empty($email)) {
        throw new Exception('Email not found. Please request a new verification code.');
    }
    
    // Check OTP in database first
    $purpose_db = ($purpose === 'create') ? 'admin_create' : 'admin_login';
    $query = "SELECT otp_code, expires_at, status, attempts FROM otp_verifications 
              WHERE email = ? AND purpose = ? AND status IN ('pending', 'verified') 
              ORDER BY created_at DESC LIMIT 1";
    
    $stmt = $pdo->prepare($query);
    if (!$stmt->execute([$email, $purpose_db])) {
        throw new Exception('Database error occurred');
    }
    
    $record = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Fallback to session if database check fails
    if (!$record) {
        $sessionKey = $purpose === 'login' ? 'admin_login_otp' : 'admin_create_otp';
        $sessionOtp = $_SESSION[$sessionKey . '_code'] ?? '';
        $sessionExpires = $_SESSION[$sessionKey . '_expires'] ?? 0;
        
        if ($sessionOtp === $otp && time() < $sessionExpires) {
            // OTP valid from session
            $_SESSION[$sessionKey . '_verified'] = true;
            $response['success'] = true;
            $response['message'] = 'Email verified successfully';
        } else {
            throw new Exception('Invalid or expired verification code. Please request a new one.');
        }
    } else {
        // Validate using database record
        
        // Check if expired
        if (strtotime($record['expires_at']) < time()) {
            // Mark as expired
            $updateQuery = "UPDATE otp_verifications SET status = 'expired' WHERE email = ? AND purpose = ? AND status = 'pending'";
            $updateStmt = $pdo->prepare($updateQuery);
            $updateStmt->execute([$email, $purpose_db]);
            
            throw new Exception('Verification code has expired. Please request a new one.');
        }
        
        // Check attempts
        if ($record['attempts'] >= 5) {
            throw new Exception('Too many invalid attempts. Please request a new verification code.');
        }
        
        // Check if OTP matches
        if ($record['otp_code'] !== $otp) {
            // Increment attempts
            $updateQuery = "UPDATE otp_verifications SET attempts = attempts + 1 WHERE email = ? AND purpose = ? AND status = 'pending'";
            $updateStmt = $pdo->prepare($updateQuery);
            $updateStmt->execute([$email, $purpose_db]);
            
            $remainingAttempts = 5 - ($record['attempts'] + 1);
            $errorMsg = 'Invalid verification code.';
            if ($remainingAttempts > 0) {
                $errorMsg .= " {$remainingAttempts} attempt(s) remaining.";
            }
            throw new Exception($errorMsg);
        }
        
        // OTP is valid - mark as verified in database
        $verifyQuery = "UPDATE otp_verifications SET status = 'verified', verified_at = NOW() WHERE email = ? AND purpose = ? AND otp_code = ?";
        $verifyStmt = $pdo->prepare($verifyQuery);
        $verifyStmt->execute([$email, $purpose_db, $otp]);
        
        // Set session flag
        $sessionKey = $purpose === 'login' ? 'admin_login_otp' : 'admin_create_otp';
        $_SESSION[$sessionKey . '_verified'] = true;
        $_SESSION[$sessionKey . '_email'] = $email;
        
        $response['success'] = true;
        $response['message'] = 'Email verified successfully';
    }
    
} catch (Exception $e) {
    $response['success'] = false;
    $response['message'] = $e->getMessage();
    error_log("Verify admin OTP error: " . $e->getMessage());
} catch (PDOException $e) {
    $response['success'] = false;
    $response['message'] = 'Database error occurred. Please try again.';
    error_log("Verify admin OTP database error: " . $e->getMessage());
}

echo json_encode($response);
?>

