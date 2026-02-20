<?php
/**
 * User Login Endpoint
 * Handles user login using Full Name and Mobile Number
 * Mobile number acts as the authentication credential
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

// Validate required fields - Full Name and Phone Number
if (!isset($data['full_name']) || !isset($data['phone'])) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Full name and mobile number are required."]);
    exit();
}

$fullName = trim($data['full_name']);
$phone = trim($data['phone']);

if (empty($fullName) || empty($phone)) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Full name and mobile number must not be empty."]);
    exit();
}

try {
    // Fetch the user from the users table using full name and phone
    // Phone number acts as the password - must match exactly
    $stmt = $pdo->prepare("SELECT id, name, email, phone, password FROM users WHERE name = ? AND phone = ?");
    $stmt->execute([$fullName, $phone]);
    $user = $stmt->fetch();

    // Check if user exists
    if (!$user) {
        http_response_code(401);
        echo json_encode(["success" => false, "message" => "Invalid credentials. Please check your full name and mobile number."]);
        exit();
    }
    
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



