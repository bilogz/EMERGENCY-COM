<?php
header('Content-Type: application/json');
require_once 'db_connect.php';

$response = ['success' => false, 'message' => ''];

try {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    $otp = $input['otp'] ?? '';
    
    // Validate OTP
    if (empty($otp) || strlen($otp) !== 6 || !is_numeric($otp)) {
        throw new Exception('Invalid verification code format');
    }
    
    session_start();
    $email = $_SESSION['signup_otp_email'] ?? '';
    
    if (empty($email)) {
        throw new Exception('Email not found. Please request a new verification code.');
    }
    
    // Check OTP in database first
    $query = "SELECT otp_code, expires_at, status, attempts FROM otp_verifications 
              WHERE email = ? AND status IN ('pending', 'verified') 
              ORDER BY created_at DESC LIMIT 1";
    
    $stmt = $pdo->prepare($query);
    if (!$stmt->execute([$email])) {
        throw new Exception('Database error occurred');
    }
    
    $record = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Fallback to session if database check fails
    if (!$record) {
        $sessionOtp = $_SESSION['signup_otp_code'] ?? '';
        $sessionExpires = $_SESSION['signup_otp_expires'] ?? 0;
        
        if ($sessionOtp === $otp && time() < $sessionExpires) {
            // OTP valid from session
            $_SESSION['signup_otp_verified'] = true;
            $response['success'] = true;
            $response['message'] = 'Email verified successfully';
        } else {
            throw new Exception('Invalid or expired verification code');
        }
    } else {
        // Validate using database record
        
        // Check if expired
        if (strtotime($record['expires_at']) < time()) {
            throw new Exception('Verification code has expired. Please request a new one.');
        }
        
        // Check attempts
        if ($record['attempts'] >= 5) {
            throw new Exception('Too many invalid attempts. Please request a new verification code.');
        }
        
        // Check if OTP matches
        if ($record['otp_code'] !== $otp) {
            // Increment attempts
            $updateQuery = "UPDATE otp_verifications SET attempts = attempts + 1 WHERE email = ? AND status = 'pending'";
            $updateStmt = $pdo->prepare($updateQuery);
            $updateStmt->execute([$email]);
            
            throw new Exception('Invalid verification code. Please try again.');
        }
        
        // OTP is valid - mark as verified in database
        $verifyQuery = "UPDATE otp_verifications SET status = 'verified' WHERE email = ? AND otp_code = ?";
        $verifyStmt = $pdo->prepare($verifyQuery);
        $verifyStmt->execute([$email, $otp]);
        
        // Set session flag
        $_SESSION['signup_otp_verified'] = true;
        
        $response['success'] = true;
        $response['message'] = 'Email verified successfully';
    }
    
} catch (Exception $e) {
    $response['success'] = false;
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
?>
