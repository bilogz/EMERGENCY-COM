<?php
// login.php

// Return JSON response
header('Content-Type: application/json');

// Include DB connection (contains $pdo and apiResponse)
require_once 'db_connect.php';

/** @var PDO $pdo */

// Get raw JSON input from the app
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// Check if this is a Google Login request
$isGoogleLogin = isset($data['google_token']) && !empty($data['google_token']);

// Validate required fields
if (!$isGoogleLogin && ((!isset($data['email']) && !isset($data['phone'])) || !isset($data['password']))) {
    apiResponse::error("Email/Phone and password are required for standard login.", 400);
}

// Determine the identifier
$email = isset($data['email']) ? trim($data['email']) : '';
$phone = isset($data['phone']) ? trim($data['phone']) : '';
$identifier = !empty($email) ? $email : $phone;
$plainPassword = isset($data['password']) ? $data['password'] : '';

try {
    $user = null;
    $authenticated = false;

    if ($isGoogleLogin) {
        // --- Google Login Flow ---
        $stmt = $pdo->prepare("SELECT id, name, email, phone FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            // Auto-Registration for new Google users
            $name = isset($data['name']) ? trim($data['name']) : 'Google User';
            $randomPass = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);
            
            $insertStmt = $pdo->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
            $insertStmt->execute([$name, $email, $randomPass]);
            
            $newUserId = (int)$pdo->lastInsertId();
            $user = [
                'id' => $newUserId,
                'name' => $name,
                'email' => $email,
                'phone' => ''
            ];
        }
        $authenticated = true; 
        
    } else {
        // --- Standard Login Flow ---
        $stmt = $pdo->prepare("SELECT id, name, email, phone, password FROM users WHERE email = ? OR phone = ?");
        $stmt->execute([$identifier, $identifier]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($plainPassword, $user['password'])) {
            $authenticated = true;
        }
    }

    if ($authenticated && $user) {
        // --- Device Registration (Upsert) ---
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

        // Generate session token
        $token = bin2hex(random_bytes(16));

        apiResponse::success([
            "user_id" => (int)$user['id'],
            "username" => $user['name'],
            "email" => $user['email'],
            "phone" => $user['phone'] ?? '',
            "token" => $token
        ], "Login successful!");
        
    } else {
        apiResponse::error("Invalid credentials.", 401);
    }

} catch (PDOException $e) {
    error_log("Login DB Error: " . $e->getMessage());
    apiResponse::error("A database error occurred during login.", 500, $e->getMessage());
} catch (Exception $e) {
    error_log("Login Error: " . $e->getMessage());
    apiResponse::error("An unexpected error occurred.", 500, $e->getMessage());
}
?>
