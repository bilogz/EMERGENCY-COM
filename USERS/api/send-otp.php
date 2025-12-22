<?php
/**
 * Send OTP API
 * Sends verification code via SMS to user's phone number
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
if (!isset($data['phone']) || !isset($data['name'])) {
    echo json_encode(["success" => false, "message" => "Phone number and name are required."]);
    exit();
}

$phone = trim($data['phone']);
$name = trim($data['name']);

if (empty($phone) || empty($name)) {
    echo json_encode(["success" => false, "message" => "Phone number and name must not be empty."]);
    exit();
}

// Normalize phone (remove non-numeric characters except +)
$phoneNormalized = preg_replace('/[^0-9+]/', '', $phone);
if (!preg_match('/^[+]?\d{7,15}$/', $phoneNormalized)) {
    echo json_encode(["success" => false, "message" => "Invalid phone number format."]);
    exit();
}

try {
    // Check if 'phone' and 'name' columns exist in users table
    $stmt = $pdo->prepare("SHOW COLUMNS FROM users LIKE 'phone'");
    $stmt->execute();
    $phoneColumnExists = $stmt->rowCount() > 0;
    
    if (!$phoneColumnExists) {
        echo json_encode(["success" => false, "message" => "Phone verification requires phone field in database."]);
        exit();
    }
    
    // Check if user exists by phone and name (case-insensitive name)
    $stmt = $pdo->prepare("SELECT id, name, phone FROM users WHERE phone = ? AND LOWER(name) = LOWER(?)");
    $stmt->execute([$phoneNormalized, $name]);
    $user = $stmt->fetch();
    
    if (!$user) {
        echo json_encode(["success" => false, "message" => "User not found with this name and phone number. Please sign up first."]);
        exit();
    }
    
    // Generate 6-digit OTP
    $otp = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
    
    // Store OTP in database (expires in 10 minutes)
    $expiresAt = date('Y-m-d H:i:s', time() + (10 * 60)); // 10 minutes from now
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
    
    try {
        // Ensure otp_verifications has a 'phone' column; if not, attempt to add it (safe to ignore errors)
        $stmt = $pdo->prepare("SHOW COLUMNS FROM otp_verifications LIKE 'phone'");
        $stmt->execute();
        if ($stmt->rowCount() === 0) {
            try {
                $pdo->exec("ALTER TABLE otp_verifications ADD COLUMN phone VARCHAR(20) DEFAULT NULL AFTER user_id;");
                $pdo->exec("CREATE INDEX idx_phone ON otp_verifications (phone);");
            } catch (PDOException $innerEx) {
                // Not critical - continue and use session fallback
                error_log("Could not add phone column to otp_verifications: " . $innerEx->getMessage());
            }
        }
        
        // Invalidate any existing pending OTPs for this phone
        $stmt = $pdo->prepare("UPDATE otp_verifications SET status = 'expired' WHERE phone = ? AND status = 'pending'");
        $stmt->execute([$phoneNormalized]);
        
        // Insert new OTP (using phone column)
        $stmt = $pdo->prepare(
            "INSERT INTO otp_verifications (user_id, phone, otp_code, purpose, status, expires_at, ip_address, created_at)
            VALUES (?, ?, ?, 'login', 'pending', ?, ?, NOW())"
        );
        $stmt->execute([$user['id'], $phoneNormalized, $otp, $expiresAt, $ipAddress]);
        
        // Also store in session for quick access
        $_SESSION['otp_code'] = $otp;
        $_SESSION['otp_phone'] = $phoneNormalized;
        $_SESSION['otp_name'] = $name;
        $_SESSION['otp_user_id'] = $user['id'];
        $_SESSION['otp_expires'] = time() + (10 * 60);
        
    } catch (PDOException $e) {
        error_log("OTP Storage Error: " . $e->getMessage());
        // Continue with session-only storage as fallback
    }
    
    // Send OTP via SMS
    $smsMessage = "Your verification code is: $otp. This code is valid for 10 minutes. - Emergency Communication System";

    // Try to send via SMS helper if available
    $smsSent = false;
    $smsError = null;
    
    if (file_exists(__DIR__ . '/../lib/sms.php')) {
        require_once __DIR__ . '/../lib/sms.php';
        $smsSent = sendSMS($phoneNormalized, $smsMessage, $smsError);
    } else {
        // SMS library not available - log for later processing
        error_log("SMS Library not found. OTP for phone $phoneNormalized: $otp");
    }

    if ($smsSent) {
        echo json_encode([
            "success" => true,
            "message" => "Verification code sent to your phone.",
            "otp_sent" => true
        ]);
    } else {
        error_log("SMS sending unavailable for phone: $phoneNormalized");
        echo json_encode([
            "success" => true,
            "message" => "Verification code generated. (SMS service unavailable - using code for testing)",
            "otp_sent" => false,
            "debug_otp" => $otp // Remove this in production
        ]);
    }
    
} catch (PDOException $e) {
    error_log("Send OTP PDO Exception: " . $e->getMessage());
    echo json_encode([
        "success" => false,
        "message" => "Database error occurred. Please try again."
    ]);
} catch (Exception $e) {
    error_log("Send OTP General Exception: " . $e->getMessage());
    echo json_encode([
        "success" => false,
        "message" => "Server error occurred. Please try again."
    ]);
}


