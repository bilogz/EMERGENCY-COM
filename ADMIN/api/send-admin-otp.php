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
function sendAdminOTPEmail($to, $subject, $body, $fromEmail, $fromName, &$error = null) {
    $error = null;
    
    // Try PHPMailer if available
    $composerAutoload1 = __DIR__ . '/../../vendor/autoload.php';
    $composerAutoload2 = __DIR__ . '/../../VENDOR/autoload.php';
    if (file_exists($composerAutoload1)) {
        require_once $composerAutoload1;
    } elseif (file_exists($composerAutoload2)) {
        require_once $composerAutoload2;
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
            
            // Use alertaraqc.notification@gmail.com SMTP credentials for admin OTP emails
            // This ensures the From address matches the authenticated account
            $adminOTPEmail = 'alertaraqc.notification@gmail.com';
            
            // Load admin OTP specific config
            $adminMailConfigPath = __DIR__ . '/admin_otp_mail_config.php';
            
            if (file_exists($adminMailConfigPath)) {
                $adminCfg = include $adminMailConfigPath;
                
                // Configure SMTP with alertaraqc.notification@gmail.com credentials
                if (!empty($adminCfg['password'])) {
                    $mail->isSMTP();
                    $mail->Host = $adminCfg['host'] ?? 'smtp.gmail.com';
                    $mail->Port = $adminCfg['port'] ?? 587;
                    $mail->SMTPAuth = true;
                    $mail->Username = $adminOTPEmail;
                    $mail->Password = $adminCfg['password'];
                    $mail->SMTPSecure = $adminCfg['secure'] ?? 'tls';
                    
                    // Set From address - must match authenticated SMTP account
                    $mail->setFrom($fromEmail, $fromName);
                    $mail->addAddress($to);
                    $mail->Subject = $subject;
                    $mail->Body = $body;
                    $mail->isHTML(false);
                    
                    $mail->send();
                    return true;
                } else {
                    // Password not set in admin config - try using existing mail config as fallback
                    // but note: Gmail will send FROM the authenticated account, not from alertaraqc.notification@gmail.com
                    $mailLibPath = __DIR__ . '/../../USERS/lib/mail.php';
                    if (file_exists($mailLibPath)) {
                        require_once $mailLibPath;
                        $cfg = load_mail_config();
                        
                        if (!empty($cfg['host']) && !empty($cfg['password'])) {
                            $mail->isSMTP();
                            $mail->Host = $cfg['host'];
                            $mail->Port = $cfg['port'] ?? 587;
                            $mail->SMTPAuth = true;
                            $mail->Username = $cfg['username'];
                            $mail->Password = $cfg['password'];
                            $mail->SMTPSecure = $cfg['secure'] ?? 'tls';
                            
                            // WARNING: From address will be overridden by Gmail to match authenticated account
                            // To fix: Add password for alertaraqc.notification@gmail.com to admin_otp_mail_config.php
                            $mail->setFrom($fromEmail, $fromName);
                            $mail->addAddress($to);
                            $mail->Subject = $subject;
                            $mail->Body = $body;
                            $mail->isHTML(false);
                            
                            $mail->send();
                            error_log("WARNING: Admin OTP sent using fallback mail config. Email will be FROM: {$cfg['username']} instead of {$fromEmail}. Please configure admin_otp_mail_config.php");
                            return true;
                        }
                    }
                    
                    $error = 'Admin OTP mail config password not set. Please add Gmail App Password for alertaraqc.notification@gmail.com to admin_otp_mail_config.php';
                    error_log("Admin OTP email error: " . $error);
                }
            } else {
                $error = 'Admin OTP mail config file not found. Please create admin_otp_mail_config.php';
                error_log("Admin OTP email error: " . $error);
            }
        } catch (Exception $e) {
            $error = $e->getMessage();
            error_log("PHPMailer error: " . $error);
        }
    }
    
    // Fallback to PHP mail() function
    if (function_exists('mail')) {
        $headers = "From: {$fromName} <{$fromEmail}>\r\n";
        $headers .= "Reply-To: {$fromEmail}\r\n";
        $headers .= "X-Mailer: PHP/" . phpversion();
        $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
        
        $sent = @mail($to, $subject, $body, $headers);
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
        // Check if admin_user table exists
        $useAdminUserTable = false;
        try {
            $pdo->query("SELECT 1 FROM admin_user LIMIT 1");
            $useAdminUserTable = true;
        } catch (PDOException $e) {
            // admin_user table doesn't exist, use users table (backward compatibility)
        }
        
        if ($useAdminUserTable) {
            // Query from admin_user table
            $stmt = $pdo->prepare("SELECT id, name, email, role, status FROM admin_user WHERE email = ? LIMIT 1");
        } else {
            // Fallback to users table
            $stmt = $pdo->prepare("SELECT id, name, email, user_type, status FROM users WHERE email = ? AND user_type = 'admin' LIMIT 1");
        }
        
        $stmt->execute([$email]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$admin) {
            throw new Exception('No admin account found with this email address');
        }
        
        if ($admin['status'] !== 'active') {
            throw new Exception('Account is not active. Please contact administrator.');
        }
        
        $name = $admin['name']; // Use name from database
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
              VALUES (?, ?, ?, DATE_ADD(NOW(), INTERVAL 1 MINUTE), 'pending', 0, ?)";
    
    $stmt = $pdo->prepare($query);
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    if (!$stmt->execute([$email, $otp_code, $purpose_db, $ip_address])) {
        throw new Exception('Failed to generate verification code');
    }
    
    // Store in session for fallback
    $sessionKey = $purpose === 'login' ? 'admin_login_otp' : 'admin_create_otp';
    $_SESSION[$sessionKey . '_code'] = $otp_code;
    $_SESSION[$sessionKey . '_email'] = $email;
    $_SESSION[$sessionKey . '_expires'] = time() + 60; // 1 minute
    $_SESSION[$sessionKey . '_purpose'] = $purpose;
    
    // Prepare email content
    // OTP is sent TO the admin's email FROM alertaraqc.notification@gmail.com
    $senderEmail = 'alertaraqc.notification@gmail.com';
    $senderName = 'Emergency Communication System';
    $purposeText = $purpose === 'login' ? 'login' : 'account creation';
    $emailSubject = 'Admin ' . ucfirst($purposeText) . ' Verification Code - Emergency Communication System';
    
    // Email body personalized for the admin
    $emailBody = "Hello {$name},\n\n";
    $emailBody .= "Your verification code for admin {$purposeText} is: {$otp_code}\n\n";
    $emailBody .= "This code will expire in 1 minute.\n\n";
    $emailBody .= "If you did not request this code, please ignore this email and contact your system administrator.\n\n";
    $emailBody .= "Thank you,\n";
    $emailBody .= "Emergency Communication System\n";
    $emailBody .= "Administrative Panel";
    
    // Try to send email to admin's email from notification email
    $otp_sent = false;
    $error = null;
    $errorDetails = [];
    
    $otp_sent = sendAdminOTPEmail($email, $emailSubject, $emailBody, $senderEmail, $senderName, $error);
    if (!$otp_sent && $error) {
        $errorDetails[] = "Email Error: " . $error;
    }
    
    // Log detailed error information
    if (!$otp_sent && !empty($errorDetails)) {
        error_log("Email send failed for {$email} (from: {$senderEmail}): " . implode("; ", $errorDetails));
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

