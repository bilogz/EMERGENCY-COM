<?php
// register.php
header('Content-Type: application/json');

require_once 'db_connect.php';
/** @var PDO $pdo */

$data = json_decode(file_get_contents('php://input'), true);

// --- Server-Side Validation ---
if (!isset($data['name']) || !isset($data['email']) || !isset($data['password']) || !isset($data['phone'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Name, email, password, and phone are required.']);
    exit();
}

$name = trim($data['name']);
$email = trim($data['email']);
$phone = trim($data['phone']);
$plainPassword = $data['password'];
// The 'share_location' checkbox from the frontend determines this value. Default to false if not provided.
$shareLocation = isset($data['share_location']) ? (bool)$data['share_location'] : false;

// Device information (optional - used for device tracking; push_token can be null if not used)
$deviceId   = isset($data['device_id'])   ? trim($data['device_id'])   : null;
$deviceType = isset($data['device_type']) ? trim($data['device_type']) : 'android';
$deviceName = isset($data['device_name']) ? trim($data['device_name']) : null;
$pushToken  = isset($data['push_token'])  ? trim($data['push_token'])  : null;

// Location information (optional - to set initial location upon signup)
$latitude  = isset($data['latitude'])  ? (float)$data['latitude']  : 0.0;
$longitude = isset($data['longitude']) ? (float)$data['longitude'] : 0.0;
$address   = isset($data['address'])   ? trim($data['address'])    : null;

if (empty($name) || empty($email) || empty($plainPassword) || empty($phone)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'All fields must be filled.']);
    exit();
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid email format.']);
    exit();
}

if (strlen($plainPassword) < 6) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters long.']);
    exit();
}

// --- Database Operations ---
try {
    // Check if email already exists in the users table
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetchColumn() > 0) {
        http_response_code(409); // Conflict
        echo json_encode(['success' => false, 'message' => 'This email address is already registered.']);
        exit();
    }

    // Start a transaction
    $pdo->beginTransaction();

    // 1. Insert the new user
    $hashedPassword = password_hash($plainPassword, PASSWORD_DEFAULT);
    $userStmt = $pdo->prepare("INSERT INTO users (name, email, password, phone) VALUES (?, ?, ?, ?)");
    $userStmt->execute([$name, $email, $hashedPassword, $phone]);
    
    // Get the ID of the user we just created
    $userId = $pdo->lastInsertId();

    // 2. Create their default preferences
    $prefsStmt = $pdo->prepare(
        "INSERT INTO user_preferences (user_id, share_location) VALUES (?, ?)"
    );
    $prefsStmt->execute([$userId, (int)$shareLocation]);

    // 3. Register the device (if provided)
    if (!empty($deviceId)) {
        $deviceStmt = $pdo->prepare(
            "INSERT INTO user_devices (user_id, device_id, device_type, device_name, push_token, is_active) VALUES (?, ?, ?, ?, ?, 1)"
        );
        $deviceStmt->execute([$userId, $deviceId, $deviceType, $deviceName, $pushToken]);
    }

    // 4. Save initial location (if provided)
    if ($latitude != 0 && $longitude != 0) {
        $locStmt = $pdo->prepare(
            "INSERT INTO user_locations (user_id, latitude, longitude, address, is_current) VALUES (?, ?, ?, ?, 1)"
        );
        $locStmt->execute([$userId, $latitude, $longitude, $address]);
    }

    // If we get here, both queries were successful. Commit the transaction.
    $pdo->commit();

    http_response_code(201); // Created
    echo json_encode([
        'success' => true,
        'message' => 'Registration successful!',
        'user_id' => $userId
    ]);

} catch (PDOException $e) {
    // If anything goes wrong, roll back the transaction
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    http_response_code(500); // Internal Server Error
    error_log("User registration error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred during registration.']);
}
?>
