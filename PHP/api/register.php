<?php
// register.php
header('Content-Type: application/json');

require_once 'db_connect.php';
/** @var PDO $pdo */

$data = json_decode(file_get_contents('php://input'), true);

// --- Server-Side Validation ---
if (!isset($data['name']) || !isset($data['email']) || !isset($data['password']) || !isset($data['phone'])) {
    apiResponse::error('Name, email, password, and phone are required.', 400);
}

$name = trim($data['name']);
$email = trim($data['email']);
$phone = trim($data['phone']);
$plainPassword = $data['password'];
$shareLocation = isset($data['share_location']) ? (bool)$data['share_location'] : false;

$deviceId   = isset($data['device_id'])   ? trim($data['device_id'])   : null;
$deviceType = isset($data['device_type']) ? trim($data['device_type']) : 'android';
$deviceName = isset($data['device_name']) ? trim($data['device_name']) : null;
$fcmToken   = isset($data['fcm_token']) ? trim($data['fcm_token']) : (isset($data['push_token']) ? trim($data['push_token']) : null);

$latitude  = isset($data['latitude'])  ? (float)$data['latitude']  : 0.0;
$longitude = isset($data['longitude']) ? (float)$data['longitude'] : 0.0;
$address   = isset($data['address'])   ? trim($data['address'])    : null;

if (empty($name) || empty($email) || empty($plainPassword) || empty($phone)) {
    apiResponse::error('All fields must be filled.', 400);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    apiResponse::error('Invalid email format.', 400);
}

if (strlen($plainPassword) < 6) {
    apiResponse::error('Password must be at least 6 characters long.', 400);
}

// --- Database Operations ---
try {
    // Check if email already exists
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetchColumn() > 0) {
        apiResponse::error('This email address is already registered.', 409);
    }

    $pdo->beginTransaction();

    // 1. Insert user
    $hashedPassword = password_hash($plainPassword, PASSWORD_DEFAULT);
    $userStmt = $pdo->prepare("INSERT INTO users (name, email, password, phone) VALUES (?, ?, ?, ?)");
    $userStmt->execute([$name, $email, $hashedPassword, $phone]);
    
    $userId = $pdo->lastInsertId();

    // 2. Preferences
    $prefsStmt = $pdo->prepare("INSERT INTO user_preferences (user_id, share_location) VALUES (?, ?)");
    $prefsStmt->execute([$userId, (int)$shareLocation]);

    // 3. Device
    if (!empty($deviceId)) {
        $deviceStmt = $pdo->prepare("INSERT INTO user_devices (user_id, device_id, device_type, device_name, fcm_token, is_active) VALUES (?, ?, ?, ?, ?, 1)");
        $deviceStmt->execute([$userId, $deviceId, $deviceType, $deviceName, $fcmToken]);
    }

    // 4. Location
    if ($latitude != 0 && $longitude != 0) {
        $locStmt = $pdo->prepare("INSERT INTO user_locations (user_id, latitude, longitude, address, is_current) VALUES (?, ?, ?, ?, 1)");
        $locStmt->execute([$userId, $latitude, $longitude, $address]);
    }

    $pdo->commit();

    apiResponse::success(['user_id' => (int)$userId], 'Registration successful!', 201);

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Registration DB Error: " . $e->getMessage());
    apiResponse::error('DB Error: ' . $e->getMessage(), 500);
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Registration Error: " . $e->getMessage());
    apiResponse::error('An unexpected error occurred.', 500, $e->getMessage());
}
?>
