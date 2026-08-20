<?php
header('Content-Type: application/json');
require_once 'db_connect.php';
require_once '../lib/mail.php';

$response = ['success' => false, 'message' => ''];

// Check if database connection was successful
if (!isset($pdo) || $pdo === null) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed. Please check your database configuration and ensure MySQL is running.']);
    exit();
}

try {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    $email = $input['email'] ?? '';
    $name = $input['name'] ?? '';
    $phone = $input['phone'] ?? '';
    
    // Validate inputs
    if (empty($email) || empty($name)) {
        throw new Exception('Email and name are required');
    }
    
    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Invalid email address format');
    }
    
    // Normalize phone
    if (!empty($phone)) {
        $phone = preg_replace('/[^0-9+]/', '', $phone);
    }
    
    // Generate 6-digit OTP
    $otp_code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    
    // Store OTP in database with email
    $query = "INSERT INTO otp_verifications (email, otp_code, expires_at, status, attempts) 
              VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 1 MINUTE), 'pending', 0)
              ON DUPLICATE KEY UPDATE 
              otp_code = VALUES(otp_code), 
              expires_at = VALUES(expires_at), 
              status = 'pending',
              attempts = 0";
    
    $stmt = $pdo->prepare($query);
    if (!$stmt->execute([$email, $otp_code])) {
        throw new Exception('Failed to generate verification code');
    }
    
    // Store in session for fallback
    session_start();
    $_SESSION['signup_otp_code'] = $otp_code;
    $_SESSION['signup_otp_email'] = $email;
    $_SESSION['signup_otp_expires'] = time() + 60; // 1 minute
    
    // Try to send email
    $otp_sent = false;
    $error = null;
    
    // Prepare email content
    $emailSubject = 'Email Verification Code - Emergency Communication System';
    $emailBodyHtml = function_exists('buildOTPEmailTemplate')
        ? buildOTPEmailTemplate($name, $otp_code, 'Citizen Registration', 1)
        : "<p>Hello " . htmlspecialchars($name) . ",</p><p>Your verification code is: <strong>{$otp_code}</strong> (valid for 1 minute).</p>";
    $emailBodyText = "Hello {$name},\n\nYour email verification code is: {$otp_code}\n\nThis code will expire in 1 minute.\n\nThank you,\nEmergency Communication System";
    
    // Try sending via PHPMailer first
    if (function_exists('sendSMTPMail')) {
        $otp_sent = sendSMTPMail($email, $emailSubject, $emailBodyHtml, true, $error);
    }
    
    // If PHPMailer fails or not available, fallback to mail()
    if (!$otp_sent && function_exists('mail')) {
        $headers = "From: alertaraqc.notification@gmail.com\r\n";
        $headers .= "Reply-To: alertaraqc.notification@gmail.com\r\n";
        $headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        
        $otp_sent = @mail($email, $emailSubject, $emailBodyHtml, $headers);
    }
    
    $response['success'] = true;
    $response['message'] = $otp_sent ? 'Verification code sent to email' : 'Verification code generated (email not sent - use debug OTP)';
    $response['otp_sent'] = $otp_sent;
    
    // ALWAYS include debug OTP for testing
    $response['debug_otp'] = $otp_code;
    $response['debug_message'] = 'If email not received, use this OTP code: ' . $otp_code;
    
    // Log the attempt
    error_log("Email OTP attempt for {$email}. Sent via email: " . ($otp_sent ? 'YES' : 'NO') . ". Debug OTP: {$otp_code}");
    
} catch (Exception $e) {
    $response['success'] = false;
    $response['message'] = $e->getMessage();
    error_log("Send signup email OTP error: " . $e->getMessage());
}

echo json_encode($response);
?>
