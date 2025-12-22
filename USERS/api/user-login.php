<?php
/**
 * User Login API
 * Handles user login via phone number (name is automatically retrieved from database) and guest login
 */

session_start();
header('Content-Type: application/json');

// Include DB connection
require_once '../../ADMIN/api/db_connect.php';

// Get POST data
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// If JSON decode failed, try form data
if ($data === null) {
    $data = $_POST;
}

// Check login type
$loginType = isset($data['login_type']) ? $data['login_type'] : 'standard';

try {
    if ($loginType === 'guest') {
        // Guest login - create anonymous guest session
        $guestId = 'guest_' . bin2hex(random_bytes(8));
        $guestName = 'Anonymous Guest';
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        $sessionToken = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', time() + (24 * 60 * 60)); // 24 hours
        
        // Log guest activity
        try {
            // Log guest login activity
            $stmt = $pdo->prepare("
                INSERT INTO user_activity_logs (user_id, activity_type, description, ip_address, user_agent, status, metadata)
                VALUES (NULL, 'guest_login', 'Anonymous guest access granted', ?, ?, 'success', ?)
            ");
            $metadata = json_encode([
                'guest_id' => $guestId,
                'session_token' => $sessionToken,
                'agreement_accepted' => isset($data['agreement_accepted']) ? $data['agreement_accepted'] : false
            ]);
            $stmt->execute([$ipAddress, $userAgent, $metadata]);
            
            // Create guest session record if table exists
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO user_sessions (user_id, session_token, ip_address, user_agent, device_type, status, expires_at, created_at)
                    VALUES (NULL, ?, ?, ?, 'web', 'active', ?, NOW())
                ");
                $deviceType = 'web';
                if (preg_match('/mobile|android|iphone|ipad/i', $userAgent)) {
                    $deviceType = 'mobile';
                }
                $stmt->execute([$sessionToken, $ipAddress, $userAgent, $deviceType, $expiresAt]);
            } catch (PDOException $e) {
                // Table might not exist, continue without it
                error_log("Guest session table not available: " . $e->getMessage());
            }
        } catch (PDOException $e) {
            error_log("Guest activity logging error: " . $e->getMessage());
            // Continue even if logging fails
        }
        
        // Set session variables
        $_SESSION['user_logged_in'] = true;
        $_SESSION['user_id'] = $guestId;
        $_SESSION['user_name'] = $guestName;
        $_SESSION['user_type'] = 'guest';
        $_SESSION['user_phone'] = null;
        $_SESSION['user_email'] = null;
        $_SESSION['user_token'] = $sessionToken;
        $_SESSION['guest_agreement_accepted'] = isset($data['agreement_accepted']) ? true : false;
        $_SESSION['guest_login_time'] = time();
        $_SESSION['guest_monitoring'] = true; // Flag for monitoring
        
        echo json_encode([
            "success" => true,
            "message" => "Guest access granted. Your session is anonymous but monitored for security.",
            "user_id" => $guestId,
            "username" => $guestName,
            "user_type" => "guest",
            "monitoring_notice" => true
        ]);
        exit();
    }
    
    // Standard login - validate required fields
    if (!isset($data['phone'])) {
        echo json_encode(["success" => false, "message" => "Phone number is required."]);
        exit();
    }
    
    $phone = trim($data['phone']);
    
    if (empty($phone)) {
        echo json_encode(["success" => false, "message" => "Phone number is required."]);
        exit();
    }
    
    // Normalize phone number (remove spaces, dashes, etc., but keep +)
    $phoneNormalized = preg_replace('/[^0-9+]/', '', $phone);
    
    // Query user from database by phone number only
    // Check if phone column exists
    $stmt = $pdo->prepare("SHOW COLUMNS FROM users LIKE 'phone'");
    $stmt->execute();
    $phoneColumnExists = $stmt->rowCount() > 0;
    
    if ($phoneColumnExists) {
        // Query by phone number - try exact match first, then normalized format
        // This handles different phone number formats (with/without spaces, dashes, etc.)
        $stmt = $pdo->prepare("SELECT id, name, email, phone FROM users WHERE phone = ? OR REPLACE(REPLACE(REPLACE(REPLACE(phone, ' ', ''), '-', ''), '(', ''), ')', '') = ?");
        $stmt->execute([$phone, $phoneNormalized]);
    } else {
        // If phone column doesn't exist, return error
        echo json_encode(["success" => false, "message" => "Phone number login requires phone field in database. Please contact administrator."]);
        exit();
    }
    
    $user = $stmt->fetch();
    
    if ($user) {
        // Set session variables - name is automatically retrieved from database
        $_SESSION['user_logged_in'] = true;
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name']; // Name retrieved automatically from database
        $_SESSION['user_email'] = isset($user['email']) ? $user['email'] : null;
        $_SESSION['user_phone'] = isset($user['phone']) ? $user['phone'] : $phone;
        $_SESSION['user_type'] = 'registered';
        $_SESSION['user_token'] = bin2hex(random_bytes(16));
        
        echo json_encode([
            "success" => true,
            "message" => "Login successful!",
            "user_id" => $user['id'],
            "username" => $user['name'], // Name included automatically
            "email" => isset($user['email']) ? $user['email'] : null,
            "phone" => isset($user['phone']) ? $user['phone'] : $phone,
            "user_type" => "registered"
        ]);
    } else {
        echo json_encode(["success" => false, "message" => "User not found with this phone number. Please check your number or sign up for a new account."]);
    }
    
} catch (PDOException $e) {
    error_log("User Login PDO Exception: " . $e->getMessage());
    echo json_encode([
        "success" => false, 
        "message" => "Database error occurred. Please try again."
    ]);
} catch (Exception $e) {
    error_log("User Login General Exception: " . $e->getMessage());
    echo json_encode([
        "success" => false, 
        "message" => "Server error occurred. Please try again."
    ]);
}
?>

