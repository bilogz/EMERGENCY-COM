<?php
// login.php

// --- Production Error Handling ---
error_reporting(0);
ini_set('display_errors', 0);
// ---------------------------------

// Return JSON response
header('Content-Type: application/json');

// Include DB connection (contains $pdo)
require_once 'db_connect.php';

// Get raw JSON input from the app
$data = json_decode(file_get_contents('php://input'), true);

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

    // Verify password against the stored hash
    if ($user && password_verify($plainPassword, $user['password'])) {

        // --- Device Registration (Upsert) ---
        // Capture device info from request
        $deviceId   = isset($data['device_id'])   ? trim($data['device_id'])   : null;
        $deviceType = isset($data['device_type']) ? trim($data['device_type']) : 'android';
        $deviceName = isset($data['device_name']) ? trim($data['device_name']) : null;
        $pushToken  = isset($data['push_token'])  ? trim($data['push_token'])  : null;

        if (!empty($deviceId)) {
            $deviceStmt = $pdo->prepare("
                INSERT INTO user_devices (user_id, device_id, device_type, device_name, push_token, is_active, last_active) 
                VALUES (?, ?, ?, ?, ?, 1, NOW()) 
                ON DUPLICATE KEY UPDATE push_token = VALUES(push_token), device_name = VALUES(device_name), is_active = 1, last_active = NOW()
            ");
            $deviceStmt->execute([$user['id'], $deviceId, $deviceType, $deviceName, $pushToken]);
        }

        // The Android app expects a flat JSON structure and a token.
        // We will generate a simple random token for now.
        $token = bin2hex(random_bytes(16));

        http_response_code(200);
        echo json_encode([
            "success" => true,
            "message" => "Login successful!",
            "user_id" => $user['id'],
            "username" => $user['name'], // The app expects 'username'
            "email" => $user['email'],
            "phone" => $user['phone'],
            "token" => $token
        ]);
    } else {
        // Use a generic message for security to prevent email enumeration

        // Debugging: Log the specific reason for failure to the server error log
        if (!$user) {
            error_log("Login Failed: User not found for identifier: " . $identifier);
        } else {
            error_log("Login Failed: Password verification failed for user: " . $user['email']);
        }

        http_response_code(401); // Unauthorized
        echo json_encode(["success" => false, "message" => "Invalid credentials."]);
    }

} catch (PDOException $e) {
    http_response_code(500); // Internal Server Error
    // Log the real error for the developer
    error_log("Login Error: " . $e->getMessage());
    // Send a generic message to the client
    echo json_encode(["success" => false, "message" => "An error occurred during login."]);
}
?>