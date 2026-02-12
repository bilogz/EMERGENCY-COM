<?php
// login.php

header('Content-Type: application/json');
require_once 'db_connect.php';

/** @var PDO $pdo */

$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data) {
    apiResponse::error("Invalid JSON input.", 400);
}

// Check if this is a Google Login request
$isGoogleLogin = isset($data['google_token']) && !empty($data['google_token']);

// Determine the identifiers
$email = isset($data['email']) ? trim($data['email']) : '';
$phone = isset($data['phone']) ? trim($data['phone']) : '';
$google_id = isset($data['google_id']) ? trim($data['google_id']) : '';
$plainPassword = isset($data['password']) ? $data['password'] : '';

// Validation
if (!$isGoogleLogin && empty($email) && empty($phone)) {
    apiResponse::error("Email or Phone is required.", 400);
}

try {
    $user = null;
    $authenticated = false;

    if ($isGoogleLogin) {
        // --- Google Login Flow ---
        // Find user by Google ID or Email
        $stmt = $pdo->prepare("SELECT * FROM users WHERE google_id = ? OR email = ? LIMIT 1");
        $stmt->execute([$google_id, $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            // Auto-Register new Google user
            $name = isset($data['name']) ? trim($data['name']) : 'Google User';

            $insertStmt = $pdo->prepare("
                INSERT INTO users (name, email, google_id, status, user_type) 
                VALUES (?, ?, ?, 'active', 'citizen')
            ");
            $insertStmt->execute([$name, $email, $google_id]);

            $newId = $pdo->lastInsertId();
            $user = [
                'id' => $newId,
                'name' => $name,
                'email' => $email,
                'phone' => ''
            ];
        } else {
            // Link Google ID if it was missing
            if (empty($user['google_id'])) {
                $upd = $pdo->prepare("UPDATE users SET google_id = ? WHERE id = ?");
                $upd->execute([$google_id, $user['id']]);
            }
        }

        $authenticated = true;

    } else {
        // --- Standard Login Flow ---
        $identifier = !empty($email) ? $email : $phone;

        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? OR phone = ? LIMIT 1");
        $stmt->execute([$identifier, $identifier]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && !empty($user['password']) && password_verify($plainPassword, $user['password'])) {
            $authenticated = true;
        } else {
            apiResponse::error("Invalid email/phone or password.", 401);
            exit;
        }
    }

    if ($authenticated && $user) {
        // --- Device Registration ---
        $deviceId   = isset($data['device_id'])   ? trim($data['device_id'])   : null;
        $deviceType = isset($data['device_type']) ? trim($data['device_type']) : 'android';
        $deviceName = isset($data['device_name']) ? trim($data['device_name']) : null;
        $fcmToken   = isset($data['fcm_token']) ? trim($data['fcm_token']) : (isset($data['push_token']) ? trim($data['push_token']) : null);

        if (!empty($deviceId)) {
            $deviceStmt = $pdo->prepare("
                INSERT INTO user_devices 
                (user_id, device_id, device_type, device_name, fcm_token, is_active, last_active)
                VALUES (?, ?, ?, ?, ?, 1, NOW())
                ON DUPLICATE KEY UPDATE 
                    fcm_token = VALUES(fcm_token),
                    device_name = VALUES(device_name),
                    is_active = 1,
                    last_active = NOW()
            ");
            $deviceStmt->execute([
                $user['id'],
                $deviceId,
                $deviceType,
                $deviceName,
                $fcmToken
            ]);
        }

        apiResponse::success([
            "user_id" => (int)$user['id'],
            "username" => $user['name'],
            "email" => $user['email'],
            "phone" => $user['phone'] ?? '',
            "token" => bin2hex(random_bytes(16))
        ], "Login successful!");
    }

} catch (PDOException $e) {
    apiResponse::error("Database error.", 500, $e->getMessage());
}
?>
