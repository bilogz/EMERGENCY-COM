<?php
/**
 * Mobile App Login Endpoint
 * Handles user login for mobile app (email/phone + password)
 * Note: Users who signed up via Google OAuth or phone OTP don't have passwords
 * and should use google-oauth-mobile.php or verify-otp.php instead
 */

header('Content-Type: application/json');

// Include DB connection - try local first, then admin
if (file_exists(__DIR__ . '/db_connect.php')) {
    require_once __DIR__ . '/db_connect.php';
} else {
    require_once '../../ADMIN/api/db_connect.php';
}

// Check if database connection was successful
if (!isset($pdo) || $pdo === null) {
    echo json_encode(["success" => false, "message" => "Database connection failed. Please check your database configuration."]);
    exit();
}

// Get raw JSON input from the app
$data = json_decode(file_get_contents('php://input'), true);

if ($data === null) {
    $data = $_POST;
}

// Validate required fields
if ((!isset($data['email']) && !isset($data['phone'])) || !isset($data['password'])) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Email/Phone and password are required."]);
    exit();
}

// Determine the login identifier (email or phone)
$email = isset($data['email']) ? trim($data['email']) : '';
$phone = isset($data['phone']) ? trim($data['phone']) : '';
$identifier = !empty($email) ? $email : $phone;
$plainPassword = $data['password'];

if (empty($identifier) || empty($plainPassword)) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Identifier and password must not be empty."]);
    exit();
}

try {
    // Fetch the user from the correct 'users' table using either email or phone
    $stmt = $pdo->prepare("SELECT id, name, email, phone, password FROM users WHERE email = ? OR phone = ?");
    $stmt->execute([$identifier, $identifier]);
    $user = $stmt->fetch();

    // Check if user exists and has a password
    if (!$user) {
        http_response_code(401);
        echo json_encode(["success" => false, "message" => "Invalid credentials."]);
        exit();
    }
    
    // Check if user has a password (users who signed up via Google OAuth or phone OTP may not have passwords)
    if (empty($user['password'])) {
        http_response_code(401);
        echo json_encode([
            "success" => false, 
            "message" => "This account was created without a password. Please use Google Sign-In or phone OTP to login."
        ]);
        exit();
    }

    // Verify password against the stored hash
    if (password_verify($plainPassword, $user['password'])) {
        // --- Device Registration (Upsert) ---
        // Capture device info from request
        $deviceId   = isset($data['device_id'])   ? trim($data['device_id'])   : null;
        $deviceType = isset($data['device_type']) ? trim($data['device_type']) : 'android';
        $deviceName = isset($data['device_name']) ? trim($data['device_name']) : null;
        $pushToken  = isset($data['push_token'])  ? trim($data['push_token'])  : null;

        if (!empty($deviceId)) {
            try {
                $deviceStmt = $pdo->prepare("
                    INSERT INTO user_devices (user_id, device_id, device_type, device_name, push_token, is_active, last_active) 
                    VALUES (?, ?, ?, ?, ?, 1, NOW()) 
                    ON DUPLICATE KEY UPDATE push_token = VALUES(push_token), device_name = VALUES(device_name), is_active = 1, last_active = NOW()
                ");
                $deviceStmt->execute([$user['id'], $deviceId, $deviceType, $deviceName, $pushToken]);
            } catch (PDOException $e) {
                error_log("Device registration error: " . $e->getMessage());
                // Continue even if device registration fails
            }
        }

        // Generate token for mobile app
        $token = bin2hex(random_bytes(16));

        http_response_code(200);
        echo json_encode([
            "success" => true,
            "message" => "Login successful!",
            "user_id" => $user['id'],
            "username" => $user['name'],
            "email" => $user['email'],
            "phone" => $user['phone'],
            "token" => $token
        ]);
    } else {
        // Password verification failed
        http_response_code(401);
        echo json_encode(["success" => false, "message" => "Invalid credentials."]);
    }

} catch (PDOException $e) {
    http_response_code(500);
    error_log("Login Error: " . $e->getMessage());
    echo json_encode(["success" => false, "message" => "An error occurred during login."]);
} catch (Exception $e) {
    http_response_code(500);
    error_log("Login General Error: " . $e->getMessage());
    echo json_encode(["success" => false, "message" => "An error occurred during login."]);
}
?>



