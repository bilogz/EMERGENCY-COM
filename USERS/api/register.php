<?php
/**
 * Mobile App Registration Endpoint
 * Handles user registration for mobile app (with password, matching web-based signup fields)
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

// Get POST data
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if ($data === null) {
    $data = $_POST;
}

// Validate required fields (password is optional - users can sign up with Google OAuth or phone OTP)
$required = ['name', 'email', 'phone'];
foreach ($required as $field) {
    if (!isset($data[$field]) || empty(trim($data[$field]))) {
        echo json_encode(["success" => false, "message" => ucfirst($field) . " is required."]);
        exit();
    }
}

$name = trim($data['name']);
$email = trim($data['email']);
$phone = trim($data['phone']);
$password = isset($data['password']) ? trim($data['password']) : null; // Password is optional
$nationality = trim($data['nationality'] ?? '');
$district = trim($data['district'] ?? '');
$barangay = trim($data['barangay'] ?? '');
$houseNumber = trim($data['house_number'] ?? '');
$street = trim($data['street'] ?? '');

// Optional location data
$latitude = isset($data['latitude']) ? (float)$data['latitude'] : null;
$longitude = isset($data['longitude']) ? (float)$data['longitude'] : null;
$address = isset($data['address']) ? trim($data['address']) : null;

// Validation
if (empty($name) || empty($email) || empty($phone)) {
    echo json_encode(["success" => false, "message" => "Name, email, and phone are required."]);
    exit();
}

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(["success" => false, "message" => "Invalid email address format."]);
    exit();
}

// Validate password length only if password is provided
if ($password !== null && $password !== '' && strlen($password) < 6) {
    echo json_encode(["success" => false, "message" => "Password must be at least 6 characters long."]);
    exit();
}

// Normalize phone
$phoneNormalized = preg_replace('/[^0-9+]/', '', $phone);
if (!preg_match('/^[+]?\d{7,15}$/', $phoneNormalized)) {
    echo json_encode(["success" => false, "message" => "Invalid phone number format."]);
    exit();
}

try {
    // Check if email already exists
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetchColumn() > 0) {
        echo json_encode(["success" => false, "message" => "Email already registered."]);
        exit();
    }
    
    // Check if phone already exists
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE phone = ?");
    $stmt->execute([$phoneNormalized]);
    if ($stmt->fetchColumn() > 0) {
        echo json_encode(["success" => false, "message" => "Phone number already registered."]);
        exit();
    }
    
    // Ensure optional columns exist
    $needed = [
        'email' => "VARCHAR(255) DEFAULT NULL COMMENT 'Email address'", 
        'phone' => "VARCHAR(20) DEFAULT NULL COMMENT 'Mobile phone number'", 
        'barangay' => "VARCHAR(100) DEFAULT NULL COMMENT 'Barangay name'", 
        'house_number' => "VARCHAR(50) DEFAULT NULL COMMENT 'House number'", 
        'street' => "VARCHAR(255) DEFAULT NULL COMMENT 'Street name'",
        'district' => "VARCHAR(50) DEFAULT NULL COMMENT 'District name'",
        'nationality' => "VARCHAR(100) DEFAULT NULL COMMENT 'Nationality'",
        'address' => "VARCHAR(500) DEFAULT NULL COMMENT 'Full address'",
        'password' => "VARCHAR(255) DEFAULT NULL COMMENT 'Password hash'"
    ];
    
    foreach ($needed as $col => $def) {
        try {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
                                   WHERE TABLE_SCHEMA = DATABASE() 
                                   AND TABLE_NAME = 'users' 
                                   AND COLUMN_NAME = ?");
            $stmt->execute([$col]);
            $colExists = $stmt->fetchColumn() > 0;
            
            if (!$colExists) {
                try {
                    $pdo->exec("ALTER TABLE `users` ADD COLUMN `$col` $def");
                    error_log("Added column $col to users table");
                } catch (PDOException $ae) {
                    error_log("Could not add column $col: " . $ae->getMessage());
                }
            }
        } catch (PDOException $e) {
            error_log("Error checking column $col: " . $e->getMessage());
        }
    }
    
    // Build address string from components
    if (empty($address) && !empty($houseNumber) && !empty($street) && !empty($barangay)) {
        $address = trim($houseNumber . ' ' . $street . ', ' . $barangay . ', Quezon City');
    }
    
    // Hash password only if provided
    $hashedPassword = null;
    if ($password !== null && $password !== '') {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    }
    
    // Build insert query dynamically based on available columns
    $insertFields = ['name', 'email', 'phone', 'status', 'created_at'];
    $insertValues = [':name', ':email', ':phone', "'active'", 'NOW()'];
    $params = [
        ':name' => $name,
        ':email' => $email,
        ':phone' => $phoneNormalized
    ];
    
    // Add password only if provided
    if ($hashedPassword !== null) {
        $insertFields[] = 'password';
        $insertValues[] = ':password';
        $params[':password'] = $hashedPassword;
    }
    
    // Add optional fields if provided
    if (!empty($barangay)) {
        $insertFields[] = 'barangay';
        $insertValues[] = ':barangay';
        $params[':barangay'] = $barangay;
    }
    
    if (!empty($houseNumber)) {
        $insertFields[] = 'house_number';
        $insertValues[] = ':house_number';
        $params[':house_number'] = $houseNumber;
    }
    
    if (!empty($street)) {
        $insertFields[] = 'street';
        $insertValues[] = ':street';
        $params[':street'] = $street;
    }
    
    if (!empty($address)) {
        $insertFields[] = 'address';
        $insertValues[] = ':address';
        $params[':address'] = $address;
    }
    
    // Check if district column exists and add it
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
                               WHERE TABLE_SCHEMA = DATABASE() 
                               AND TABLE_NAME = 'users' 
                               AND COLUMN_NAME = 'district'");
        $stmt->execute();
        $hasDistrict = $stmt->fetchColumn() > 0;
        if ($hasDistrict && !empty($district)) {
            $insertFields[] = 'district';
            $insertValues[] = ':district';
            $params[':district'] = $district;
        }
    } catch (PDOException $e) {
        error_log("Error checking district column: " . $e->getMessage());
    }
    
    // Check if nationality column exists and add it
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
                               WHERE TABLE_SCHEMA = DATABASE() 
                               AND TABLE_NAME = 'users' 
                               AND COLUMN_NAME = 'nationality'");
        $stmt->execute();
        $hasNationality = $stmt->fetchColumn() > 0;
        if ($hasNationality && !empty($nationality)) {
            $insertFields[] = 'nationality';
            $insertValues[] = ':nationality';
            $params[':nationality'] = $nationality;
        }
    } catch (PDOException $e) {
        error_log("Error checking nationality column: " . $e->getMessage());
    }
    
    // Device information (optional - used for device tracking)
    $deviceId = isset($data['device_id']) ? trim($data['device_id']) : null;
    $deviceType = isset($data['device_type']) ? trim($data['device_type']) : 'android';
    $deviceName = isset($data['device_name']) ? trim($data['device_name']) : null;
    $pushToken = isset($data['push_token']) ? trim($data['push_token']) : null;
    
    // Start transaction
    $pdo->beginTransaction();
    
    // Insert user
    $insertSql = "INSERT INTO `users` (`" . implode("`, `", $insertFields) . "`) VALUES (" . implode(", ", $insertValues) . ")";
    $stmt = $pdo->prepare($insertSql);
    
    if (!$stmt->execute($params)) {
        throw new PDOException("Failed to insert user");
    }
    
    $userId = $pdo->lastInsertId();
    
    // Register device if provided
    if (!empty($deviceId)) {
        try {
            $deviceStmt = $pdo->prepare("
                INSERT INTO user_devices (user_id, device_id, device_type, device_name, push_token, is_active, last_active) 
                VALUES (?, ?, ?, ?, ?, 1, NOW()) 
                ON DUPLICATE KEY UPDATE push_token = VALUES(push_token), device_name = VALUES(device_name), is_active = 1, last_active = NOW()
            ");
            $deviceStmt->execute([$userId, $deviceId, $deviceType, $deviceName, $pushToken]);
        } catch (PDOException $e) {
            error_log("Could not register device: " . $e->getMessage());
            // Continue even if device registration fails
        }
    }
    
    // Save initial location if provided
    if ($latitude !== null && $longitude !== null) {
        try {
            $locStmt = $pdo->prepare("
                INSERT INTO user_locations (user_id, latitude, longitude, address, is_current, created_at) 
                VALUES (?, ?, ?, ?, 1, NOW())
                ON DUPLICATE KEY UPDATE latitude = VALUES(latitude), longitude = VALUES(longitude), address = VALUES(address), is_current = 1, updated_at = NOW()
            ");
            $locStmt->execute([$userId, $latitude, $longitude, $address]);
        } catch (PDOException $e) {
            error_log("Could not save location: " . $e->getMessage());
            // Continue even if location save fails
        }
    }
    
    // Commit transaction
    $pdo->commit();
    
    // Generate token for mobile app
    $token = bin2hex(random_bytes(16));
    
    echo json_encode([
        "success" => true,
        "message" => "Registration successful!",
        "user_id" => $userId,
        "username" => $name,
        "email" => $email,
        "phone" => $phoneNormalized,
        "token" => $token
    ]);
    
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Register PDO Exception: " . $e->getMessage());
    echo json_encode([
        "success" => false,
        "message" => "Database error occurred. Please try again."
    ]);
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Register General Exception: " . $e->getMessage());
    echo json_encode([
        "success" => false,
        "message" => "Server error occurred. Please try again."
    ]);
}
?>

