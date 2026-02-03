<?php
/**
 * Update User Profile API
 * Handles updating user information from profile page
 */

session_start();
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    echo json_encode([
        "success" => false,
        "message" => "You must be logged in to update your profile."
    ]);
    exit();
}

// Check if user is a registered user (not guest)
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'registered') {
    echo json_encode([
        "success" => false,
        "message" => "Only registered users can update their profile."
    ]);
    exit();
}

$userId = $_SESSION['user_id'] ?? null;
if (!$userId) {
    echo json_encode([
        "success" => false,
        "message" => "User ID not found in session."
    ]);
    exit();
}

// Include DB connection
if (file_exists(__DIR__ . '/db_connect.php')) {
    require_once __DIR__ . '/db_connect.php';
} else {
    require_once '../../ADMIN/api/db_connect.php';
}

// Ensure database connection is available
if (!isset($pdo) || $pdo === null) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database connection failed. Please check your database configuration.'
    ]);
    exit();
}

// Get POST data
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if ($data === null) {
    $data = $_POST;
}

try {
    // Validate required fields
    $name = trim($data['name'] ?? '');
    $email = trim($data['email'] ?? '');
    $phone = trim($data['phone'] ?? '');
    
    if (empty($name)) {
        echo json_encode([
            "success" => false,
            "message" => "Name is required."
        ]);
        exit();
    }
    
    // Validate email format if provided
    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode([
            "success" => false,
            "message" => "Invalid email address format."
        ]);
        exit();
    }
    
    // Normalize phone number
    $phoneNormalized = !empty($phone) ? preg_replace('/[^0-9+]/', '', $phone) : null;
    
    // Optional address fields
    $barangay = trim($data['barangay'] ?? '');
    $houseNumber = trim($data['house_number'] ?? '');
    $street = trim($data['street'] ?? '');
    $district = trim($data['district'] ?? '');
    $nationality = trim($data['nationality'] ?? '');
    
    // Build address string if components are provided
    $address = null;
    if (!empty($houseNumber) || !empty($street) || !empty($barangay)) {
        $addressParts = array_filter([$houseNumber, $street, $barangay]);
        $address = !empty($addressParts) ? implode(', ', $addressParts) . ', Quezon City' : null;
    }
    
    // Check if email already exists for another user
    if (!empty($email)) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt->execute([$email, $userId]);
        if ($stmt->fetch()) {
            echo json_encode([
                "success" => false,
                "message" => "Email address is already registered to another account."
            ]);
            exit();
        }
    }
    
    // Check if phone already exists for another user
    if (!empty($phoneNormalized)) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE phone = ? AND id != ?");
        $stmt->execute([$phoneNormalized, $userId]);
        if ($stmt->fetch()) {
            echo json_encode([
                "success" => false,
                "message" => "Phone number is already registered to another account."
            ]);
            exit();
        }
    }
    
    // Discover available columns once (more reliable than INFORMATION_SCHEMA on shared hosting)
    $existingCols = [];
    try {
        $colsStmt = $pdo->query("SHOW COLUMNS FROM users");
        $existingCols = $colsStmt ? $colsStmt->fetchAll(PDO::FETCH_COLUMN, 0) : [];
    } catch (PDOException $e) {
        $existingCols = [];
        error_log('SHOW COLUMNS failed (users): ' . $e->getMessage());
    }

    $colExistsCache = [];
    $columnExists = function ($col) use ($pdo, &$colExistsCache, $existingCols) {
        if (!empty($existingCols)) {
            return in_array($col, $existingCols, true);
        }

        if (array_key_exists($col, $colExistsCache)) {
            return $colExistsCache[$col];
        }

        try {
            $stmt = $pdo->prepare("SHOW COLUMNS FROM users LIKE ?");
            $stmt->execute([$col]);
            $exists = $stmt->rowCount() > 0;
            $colExistsCache[$col] = $exists;
            return $exists;
        } catch (PDOException $e) {
            $colExistsCache[$col] = false;
            error_log('SHOW COLUMNS LIKE failed (users.' . $col . '): ' . $e->getMessage());
            return false;
        }
    };

    // Build update query dynamically based on available columns
    $updateFields = ['name = ?'];
    $updateValues = [$name];

    $columnsToSet = [
        'email' => $email,
        'phone' => $phoneNormalized,
        'barangay' => $barangay,
        'house_number' => $houseNumber,
        'street' => $street,
        'district' => $district,
        'nationality' => $nationality,
        'address' => $address,
        'notification_sound' => $data['notification_sound'] ?? null
    ];

    foreach ($columnsToSet as $column => $value) {
        if (!$columnExists($column)) {
            continue;
        }
        $updateFields[] = "$column = ?";
        $updateValues[] = ($value === '' ? null : $value);
    }
    
    // Add updated_at
    $updateFields[] = "updated_at = NOW()";
    $updateValues[] = $userId;
    
    // Execute update
    $updateQuery = "UPDATE users SET " . implode(", ", $updateFields) . " WHERE id = ?";
    $stmt = $pdo->prepare($updateQuery);
    
    if ($stmt->execute($updateValues)) {
        // Update session variables
        $_SESSION['user_name'] = $name;
        if (!empty($email)) {
            $_SESSION['user_email'] = $email;
        }
        if (!empty($phoneNormalized)) {
            $_SESSION['user_phone'] = $phoneNormalized;
        }
        
        // Log activity
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        try {
            $activityStmt = $pdo->prepare("
                INSERT INTO user_activity_logs (user_id, activity_type, description, ip_address, user_agent, status, created_at)
                VALUES (?, 'profile_update', 'User updated profile information', ?, ?, 'success', NOW())
            ");
            $activityStmt->execute([$userId, $ipAddress, $userAgent]);
        } catch (PDOException $e) {
            error_log("Could not log user activity: " . $e->getMessage());
        }
        
        echo json_encode([
            "success" => true,
            "message" => "Profile updated successfully!",
            "user" => [
                "id" => $userId,
                "name" => $name,
                "email" => $email,
                "phone" => $phoneNormalized
            ]
        ]);
    } else {
        $errorInfo = $stmt->errorInfo();
        error_log("Profile update failed: " . json_encode($errorInfo));
        echo json_encode([
            "success" => false,
            "message" => "Failed to update profile. Please try again."
        ]);
    }
    
} catch (PDOException $e) {
    error_log("Update Profile PDO Exception: " . $e->getMessage());
    echo json_encode([
        "success" => false,
        "message" => "Database error occurred. Please try again."
    ]);
} catch (Exception $e) {
    error_log("Update Profile General Exception: " . $e->getMessage());
    echo json_encode([
        "success" => false,
        "message" => "An error occurred. Please try again."
    ]);
}
?>


