<?php
/**
 * Send Admin OTP for Account Creation or Login
 * Sends 6-digit OTP code via email
 */

header('Content-Type: application/json');
require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/config.env.php';

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

// Custom function to send email with specific sender
function sendAdminOTPEmail($to, $subject, $bodyHtml, $fromEmail, $fromName, &$error = null, $plainText = '') {
    $error = null;
    
    // Try PHPMailer if available
    $composerAutoload1 = __DIR__ . '/../../vendor/autoload.php';
    $composerAutoload2 = __DIR__ . '/../../VENDOR/autoload.php';
    if (PHP_VERSION_ID >= 80200) {
        try {
            if (file_exists($composerAutoload1)) {
                require_once $composerAutoload1;
            } elseif (file_exists($composerAutoload2)) {
                require_once $composerAutoload2;
            }
        } catch (Throwable $e) {
            error_log("Composer autoload unavailable for admin OTP mailer: " . $e->getMessage());
        }
    } else {
        error_log("Skipping Composer autoload for admin OTP mailer: PHP " . PHP_VERSION . " is below Composer platform requirement");
    }
    
    // Also try direct path to PHPMailer-master
    if (!class_exists('PHPMailer\PHPMailer\PHPMailer', false)) {
        $phpmailerPath = __DIR__ . '/../../VENDOR/PHPMailer-master/src/PHPMailer.php';
        if (file_exists($phpmailerPath)) {
            require_once __DIR__ . '/../../VENDOR/PHPMailer-master/src/Exception.php';
            require_once __DIR__ . '/../../VENDOR/PHPMailer-master/src/PHPMailer.php';
            require_once __DIR__ . '/../../VENDOR/PHPMailer-master/src/SMTP.php';
        }
    }
    
    if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
        try {
            $mail = new PHPMailer\PHPMailer\PHPMailer(true);
            
            $adminOTPEmail = 'alertaraqc.notification@gmail.com';
            $adminMailConfigPath = __DIR__ . '/admin_otp_mail_config.php';
            
            $smtpHost = 'smtp.gmail.com';
            $smtpPort = 587;
            $smtpPass = 'gatbylpxrgmcolqm';
            $smtpSecure = 'tls';

            if (file_exists($adminMailConfigPath)) {
                $adminCfg = include $adminMailConfigPath;
                if (is_array($adminCfg) && !empty($adminCfg['password'])) {
                    $smtpHost = $adminCfg['host'] ?? $smtpHost;
                    $smtpPort = $adminCfg['port'] ?? $smtpPort;
                    $smtpPass = $adminCfg['password'];
                    $smtpSecure = $adminCfg['secure'] ?? $smtpSecure;
                }
            }
            
            $mail->isSMTP();
            $mail->Host = $smtpHost;
            $mail->Port = $smtpPort;
            $mail->SMTPAuth = true;
            $mail->Username = $adminOTPEmail;
            $mail->Password = $smtpPass;
            $mail->SMTPSecure = $smtpSecure;
            
            $mail->setFrom($fromEmail, $fromName);
            $mail->addAddress($to);
            $mail->Subject = $subject;
            $mail->isHTML(true);
            $mail->Body = $bodyHtml;
            $mail->AltBody = !empty($plainText) ? $plainText : strip_tags($bodyHtml);
            
            $mail->send();
            return true;
        } catch (Exception $e) {
            $error = $e->getMessage();
            error_log("PHPMailer error: " . $error);
        }
    }
    
    // Fallback to PHP mail() function
    if (function_exists('mail')) {
        $headers = "From: {$fromName} <{$fromEmail}>\r\n";
        $headers .= "Reply-To: {$fromEmail}\r\n";
        $headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        
        $sent = @mail($to, $subject, $bodyHtml, $headers);
        if (!$sent) {
            $error = 'PHP mail() function failed';
        }
        return $sent;
    }
    
    $error = 'No mailer available';
    return false;
}

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
    
    // For login, check if admin exists in admin_user table
    if ($purpose === 'login') {
        $admin = null;

        try {
            $stmt = $pdo->prepare("SELECT id, name, email, role, status FROM admin_user WHERE email = ? LIMIT 1");
            $stmt->execute([$email]);
            $admin = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('Admin OTP admin_user lookup failed: ' . $e->getMessage());
        }

        if (!$admin) {
            try {
                $stmt = $pdo->prepare("SELECT id, name, email, user_type, status FROM users WHERE email = ? AND user_type = 'admin' LIMIT 1");
                $stmt->execute([$email]);
                $admin = $stmt->fetch(PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                error_log('Admin OTP legacy users lookup failed: ' . $e->getMessage());
            }
        }
        
        if (!$admin) {
            throw new Exception('Admin account not found with this email address');
        }
        
        if ($admin['status'] !== 'active') {
            throw new Exception('Admin account is not active. Please contact system administrator.');
        }
        
        $name = $admin['name'];
    }
    
    // Generate 6-digit OTP
    $otp_code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    
    $purpose_db = ($purpose === 'create') ? 'admin_create' : 'admin_login';
    
    // Delete old pending OTPs for this email and purpose
    $deleteQuery = "DELETE FROM otp_verifications WHERE email = ? AND purpose = ? AND status = 'pending'";
    $deleteStmt = $pdo->prepare($deleteQuery);
    $deleteStmt->execute([$email, $purpose_db]);
    
    // Insert new OTP with 1 minute validity
    $query = "INSERT INTO otp_verifications (email, otp_code, purpose, expires_at, status, attempts, ip_address) 
              VALUES (?, ?, ?, DATE_ADD(NOW(), INTERVAL 1 MINUTE), 'pending', 0, ?)";
    
    $stmt = $pdo->prepare($query);
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    if (!$stmt->execute([$email, $otp_code, $purpose_db, $ip_address])) {
        throw new Exception('Failed to generate verification code');
    }
    
    // Store in session for fallback (1 minute)
    $sessionKey = $purpose === 'login' ? 'admin_login_otp' : 'admin_create_otp';
    $_SESSION[$sessionKey . '_code'] = $otp_code;
    $_SESSION[$sessionKey . '_email'] = $email;
    $_SESSION[$sessionKey . '_expires'] = time() + 60; // 1 minute
    $_SESSION[$sessionKey . '_purpose'] = $purpose;
    
    // Prepare email content
    $senderEmail = 'alertaraqc.notification@gmail.com';
    $senderName = 'Emergency Communication System';
    $purposeText = $purpose === 'login' ? 'Admin Login' : 'Admin Account Creation';
    $emailSubject = 'Admin Verification Code - Emergency Communication System';
    
    // Require mail helper for HTML template
    $mailLibPath = __DIR__ . '/../../USERS/lib/mail.php';
    if (file_exists($mailLibPath)) {
        require_once $mailLibPath;
    }
    
    if (function_exists('buildOTPEmailTemplate')) {
        $emailBodyHtml = buildOTPEmailTemplate($name, $otp_code, $purposeText, 1);
    } else {
        $emailBodyHtml = "<p>Hello {$name},</p><p>Your verification code is: <strong>{$otp_code}</strong></p><p>Valid for 1 minute.</p>";
    }

    $plainText = "Hello {$name},\n\nYour verification code for {$purposeText} is: {$otp_code}\n\nThis code is valid for 1 minute.\n\nThank you,\nEmergency Communication System";
    
    // Try to send email
    $otp_sent = false;
    $error = null;
    
    $otp_sent = sendAdminOTPEmail($email, $emailSubject, $emailBodyHtml, $senderEmail, $senderName, $error, $plainText);
    
    if (!$otp_sent && $error) {
        error_log("Email send failed for {$email} (from: {$senderEmail}): " . $error);
    }
    
    $response['success'] = true;
    $response['message'] = $otp_sent 
        ? "Verification code sent to {$email}" 
        : "Verification code generated (email not sent - use debug OTP)";
    $response['otp_sent'] = $otp_sent;
    
    // Include debug OTP only in non-production environments
    if (!isProduction()) {
        $response['debug_otp'] = $otp_code;
        $response['debug_message'] = 'If email not received, use this OTP code: ' . $otp_code;
    }
    
    // Log the attempt
    error_log("Admin OTP attempt for {$email} (purpose: {$purpose}). Sent from {$senderEmail}: " . ($otp_sent ? 'YES' : 'NO') . ". Debug OTP: {$otp_code}");
    
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

