<?php
/**
 * Send Login OTP via Email
 * Sends OTP to user's email for login authentication
 */

session_start();
header('Content-Type: application/json');

// Include DB connection
if (file_exists(__DIR__ . '/db_connect.php')) {
    require_once __DIR__ . '/db_connect.php';
} else {
    require_once '../../ADMIN/api/db_connect.php';
}

// Include mail function
if (file_exists(__DIR__ . '/../lib/mail.php')) {
    require_once __DIR__ . '/../lib/mail.php';
}

// Get POST data
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if ($data === null) {
    $data = $_POST;
}

// Validate required fields
if (!isset($data['email'])) {
    echo json_encode(["success" => false, "message" => "Email address is required."]);
    exit();
}

$email = trim($data['email']);

if (empty($email)) {
    echo json_encode(["success" => false, "message" => "Email address is required."]);
    exit();
}

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(["success" => false, "message" => "Invalid email address format."]);
    exit();
}

try {
    // Check if user exists by email
    $stmt = $pdo->prepare("SELECT id, name, email FROM users WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if (!$user) {
        echo json_encode(["success" => false, "message" => "No account found with this email address. Please sign up first."]);
        exit();
    }
    
    // Generate 6-digit OTP
    $otp = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
    
    // Store OTP in database (expires in 10 minutes)
    $expiresAt = date('Y-m-d H:i:s', time() + (10 * 60)); // 10 minutes from now
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
    
    try {
        // Check if otp_verifications table has email column
        $stmt = $pdo->prepare("SHOW COLUMNS FROM otp_verifications LIKE 'email'");
        $stmt->execute();
        $emailColumnExists = $stmt->rowCount() > 0;
        
        if (!$emailColumnExists) {
            try {
                $pdo->exec("ALTER TABLE otp_verifications ADD COLUMN email VARCHAR(255) DEFAULT NULL AFTER user_id");
                $pdo->exec("CREATE INDEX idx_email ON otp_verifications (email)");
            } catch (PDOException $e) {
                error_log("Could not add email column: " . $e->getMessage());
            }
        }
        
        // Invalidate any existing pending OTPs for this email
        $stmt = $pdo->prepare("UPDATE otp_verifications SET status = 'expired' WHERE email = ? AND status = 'pending'");
        $stmt->execute([$email]);
        
        // Insert new OTP
        $stmt = $pdo->prepare(
            "INSERT INTO otp_verifications (user_id, email, otp_code, purpose, status, expires_at, ip_address, created_at)
            VALUES (?, ?, ?, 'login', 'pending', ?, ?, NOW())"
        );
        $stmt->execute([$user['id'], $email, $otp, $expiresAt, $ipAddress]);
        
        // Also store in session for quick access
        $_SESSION['login_otp_code'] = $otp;
        $_SESSION['login_otp_email'] = $email;
        $_SESSION['login_otp_user_id'] = $user['id'];
        $_SESSION['login_otp_expires'] = time() + (10 * 60);
        
    } catch (PDOException $e) {
        error_log("OTP Storage Error: " . $e->getMessage());
        // Continue with session-only storage as fallback
    }
    
    // Send OTP via Email
    $emailSubject = 'Login Verification Code - Emergency Communication System';
    $emailBody = "Hello {$user['name']},\n\n";
    $emailBody .= "Your login verification code is: {$otp}\n\n";
    $emailBody .= "This code is valid for 10 minutes.\n\n";
    $emailBody .= "If you did not request this code, please ignore this email.\n\n";
    $emailBody .= "Thank you,\n";
    $emailBody .= "Emergency Communication System";

    // Try to send email
    $emailSent = false;
    $error = null;
    
    // Try PHPMailer if available
    if (function_exists('sendSMTPMail')) {
        $emailSent = sendSMTPMail($email, $emailSubject, $emailBody, false, $error);
    }
    
    // Fallback to mail() if PHPMailer not available or failed
    if (!$emailSent && function_exists('mail')) {
        $headers = "From: noreply@emergency-com.local\r\n";
        $headers .= "Reply-To: support@emergency-com.local\r\n";
        $headers .= "X-Mailer: PHP/" . phpversion();
        $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
        
        $emailSent = @mail($email, $emailSubject, $emailBody, $headers);
    }

    if ($emailSent) {
        echo json_encode([
            "success" => true,
            "message" => "Verification code sent to your email.",
            "otp_sent" => true
        ]);
    } else {
        error_log("Email sending unavailable for: $email");
        echo json_encode([
            "success" => true,
            "message" => "Verification code generated. (Email service unavailable - using code for testing)",
            "otp_sent" => false,
            "debug_otp" => $otp // For testing only
        ]);
    }
    
} catch (PDOException $e) {
    error_log("Send Login Email OTP PDO Exception: " . $e->getMessage());
    echo json_encode([
        "success" => false,
        "message" => "Database error occurred. Please try again."
    ]);
} catch (Exception $e) {
    error_log("Send Login Email OTP General Exception: " . $e->getMessage());
    echo json_encode([
        "success" => false,
        "message" => "Server error occurred. Please try again."
    ]);
}
?>

