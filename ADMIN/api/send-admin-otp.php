<?php
/**
 * Send Admin OTP for Account Creation or Login
 * Sends 6-digit OTP code via email
 */

header('Content-Type: application/json');
require_once __DIR__ . '/db_connect.php';

// Include mail helper (try to use USERS lib, or create simple version)
$mailLibPath = __DIR__ . '/../../USERS/lib/mail.php';
if (file_exists($mailLibPath)) {
    require_once $mailLibPath;
} else {
    // Simple mail function fallback
    function sendSMTPMail($to, $subject, $body, $isHtml = false, &$error = null) {
        $error = null;
        $headers = "From: noreply@emergency-com.local\r\n";
        $headers .= "Reply-To: support@emergency-com.local\r\n";
        $headers .= "X-Mailer: PHP/" . phpversion();
        $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
        
        $sent = @mail($to, $subject, $body, $headers);
        if (!$sent) {
            $error = 'Mail function failed';
        }
        return $sent;
    }
}

session_start();

$response = ['success' => false, 'message' => ''];

try {
    // Get JSON input or POST data
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        $input = $_POST;
    }
    
    $email = trim($input['email'] ?? '');
    $name = trim($input['name'] ?? '');
    $purpose = $input['purpose'] ?? 'create'; // 'create' or 'login'
    
    // Validate inputs
    if (empty($email)) {
        throw new Exception('Email address is required');
    }
    
    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Invalid email address format');
    }
    
    // For login, check if user exists
    if ($purpose === 'login') {
        $stmt = $pdo->prepare("SELECT id, name, email, user_type, status FROM users WHERE email = ? AND user_type = 'admin' LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            throw new Exception('No admin account found with this email address');
        }
        
        if ($user['status'] !== 'active') {
            throw new Exception('Account is not active. Please contact administrator.');
        }
        
        $name = $user['name']; // Use name from database
    }
    
    // Generate 6-digit OTP
    $otp_code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    
    // Store OTP in database (using existing otp_verifications table)
    // Table structure already exists from USERS module
    $purpose_db = ($purpose === 'create') ? 'admin_create' : 'admin_login';
    
    // Delete old pending OTPs for this email and purpose
    $deleteQuery = "DELETE FROM otp_verifications WHERE email = ? AND purpose = ? AND status = 'pending'";
    $deleteStmt = $pdo->prepare($deleteQuery);
    $deleteStmt->execute([$email, $purpose_db]);
    
    // Insert new OTP
    $query = "INSERT INTO otp_verifications (email, otp_code, purpose, expires_at, status, attempts, ip_address) 
              VALUES (?, ?, ?, DATE_ADD(NOW(), INTERVAL 10 MINUTE), 'pending', 0, ?)";
    
    $stmt = $pdo->prepare($query);
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    if (!$stmt->execute([$email, $otp_code, $purpose_db, $ip_address])) {
        throw new Exception('Failed to generate verification code');
    }
    
    // Store in session for fallback
    $sessionKey = $purpose === 'login' ? 'admin_login_otp' : 'admin_create_otp';
    $_SESSION[$sessionKey . '_code'] = $otp_code;
    $_SESSION[$sessionKey . '_email'] = $email;
    $_SESSION[$sessionKey . '_expires'] = time() + 600; // 10 minutes
    $_SESSION[$sessionKey . '_purpose'] = $purpose;
    
    // Prepare email content
    $purposeText = $purpose === 'login' ? 'login' : 'account creation';
    $emailSubject = 'Admin ' . ucfirst($purposeText) . ' Verification Code - Emergency Communication System';
    $emailBody = "Hello {$name},\n\n";
    $emailBody .= "Your verification code for admin {$purposeText} is: {$otp_code}\n\n";
    $emailBody .= "This code will expire in 10 minutes.\n\n";
    $emailBody .= "If you did not request this code, please ignore this email and contact your system administrator.\n\n";
    $emailBody .= "Thank you,\n";
    $emailBody .= "Emergency Communication System\n";
    $emailBody .= "Administrative Panel";
    
    // Try to send email
    $otp_sent = false;
    $error = null;
    $errorDetails = [];
    
    // Try sending via SMTP/PHPMailer first (if configured)
    if (function_exists('sendSMTPMail')) {
        $otp_sent = sendSMTPMail($email, $emailSubject, $emailBody, false, $error);
        if (!$otp_sent && $error) {
            $errorDetails[] = "SMTP Error: " . $error;
        }
    } else {
        $errorDetails[] = "sendSMTPMail function not available (mail_config.php not configured)";
    }
    
    // If SMTP fails, try PHP's mail() function as fallback
    if (!$otp_sent && function_exists('mail')) {
        $headers = "From: noreply@emergency-com.local\r\n";
        $headers .= "Reply-To: support@emergency-com.local\r\n";
        $headers .= "X-Mailer: PHP/" . phpversion();
        $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
        
        $otp_sent = @mail($email, $emailSubject, $emailBody, $headers);
        if (!$otp_sent) {
            $errorDetails[] = "PHP mail() function failed (not configured on this server)";
        }
    } else if (!$otp_sent) {
        $errorDetails[] = "PHP mail() function not available";
    }
    
    // Log detailed error information
    if (!$otp_sent && !empty($errorDetails)) {
        error_log("Email send failed for {$email}: " . implode("; ", $errorDetails));
    }
    
    $response['success'] = true;
    $response['message'] = $otp_sent 
        ? "Verification code sent to {$email}" 
        : "Verification code generated (email not sent - use debug OTP)";
    $response['otp_sent'] = $otp_sent;
    
    // ALWAYS include debug OTP for testing (remove in production)
    $response['debug_otp'] = $otp_code;
    $response['debug_message'] = 'If email not received, use this OTP code: ' . $otp_code;
    
    // Log the attempt
    error_log("Admin OTP attempt for {$email} (purpose: {$purpose}). Sent via email: " . ($otp_sent ? 'YES' : 'NO') . ". Debug OTP: {$otp_code}");
    
} catch (Exception $e) {
    $response['success'] = false;
    $response['message'] = $e->getMessage();
    error_log("Send admin OTP error: " . $e->getMessage());
} catch (PDOException $e) {
    $response['success'] = false;
    $response['message'] = 'Database error occurred. Please try again.';
    error_log("Send admin OTP database error: " . $e->getMessage());
}

echo json_encode($response);
?>

