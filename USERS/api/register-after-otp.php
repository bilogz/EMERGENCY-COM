<?php
/**
 * Register After OTP Verification
 * Completes user registration after OTP has been verified
 */

session_start();
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

// Verify that OTP was actually verified
if (!isset($_SESSION['signup_otp_verified']) || $_SESSION['signup_otp_verified'] !== true) {
    echo json_encode(["success" => false, "message" => "Please verify your OTP first."]);
    exit();
}

// Get POST data
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if ($data === null) {
    $data = $_POST;
}

// Validate required fields
$required = ['name', 'email', 'phone', 'barangay', 'house_number', 'street'];
foreach ($required as $field) {
    if (!isset($data[$field])) {
        echo json_encode(["success" => false, "message" => ucfirst($field) . " is required."]);
        exit();
    }
}

$name = trim($data['name']);
$email = trim($data['email']);
$phone = trim($data['phone']);
$nationality = trim($data['nationality'] ?? '');
$district = trim($data['district'] ?? '');
$barangay = trim($data['barangay']);
$houseNumber = trim($data['house_number']);
$street = trim($data['street']);

// Validation
if (empty($name) || empty($email) || empty($phone) || empty($barangay) || empty($houseNumber) || empty($street)) {
    echo json_encode(["success" => false, "message" => "All required fields must be filled."]);
    exit();
}

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(["success" => false, "message" => "Invalid email address format."]);
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
    
    // Ensure optional columns exist (removed password - not needed for citizen accounts)
    $needed = [
        'email' => "VARCHAR(255) DEFAULT NULL", 
        'phone' => "VARCHAR(20) DEFAULT NULL", 
        'barangay' => "VARCHAR(100) DEFAULT NULL", 
        'house_number' => "VARCHAR(50) DEFAULT NULL", 
        'street' => "VARCHAR(255) DEFAULT NULL",
        'district' => "VARCHAR(50) DEFAULT NULL",
        'nationality' => "VARCHAR(100) DEFAULT NULL"
    ];
    foreach ($needed as $col => $def) {
        // Check if column exists using INFORMATION_SCHEMA (compatible with MariaDB)
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
    }
    
    // Build address string from components
    $address = trim($houseNumber . ' ' . $street . ', ' . $barangay . ', Quezon City');
    
    // Log which database we're using
    try {
        $dbCheck = $pdo->query("SELECT DATABASE() as db, @@hostname as hostname");
        $dbInfo = $dbCheck->fetch();
        error_log("Registering user in database: {$dbInfo['db']} on host: {$dbInfo['hostname']}");
    } catch (Exception $e) {
        error_log("Could not get database info: " . $e->getMessage());
    }
    
    // Insert new user (citizens only - login with phone + CAPTCHA)
    $insertSql = "INSERT INTO `users` (`name`, `email`, `phone`, `nationality`, `district`, `barangay`, `house_number`, `street`, `address`, `status`, `created_at`) VALUES (:name, :email, :phone, :nationality, :district, :barangay, :house_number, :street, :address, 'active', NOW())";
    $stmt = $pdo->prepare($insertSql);
    $params = [
        ':name' => $name,
        ':email' => $email,
        ':phone' => $phoneNormalized,
        ':nationality' => $nationality ?: null,
        ':district' => $district ?: null,
        ':barangay' => $barangay,
        ':house_number' => $houseNumber,
        ':street' => $street,
        ':address' => $address
    ];
    
    error_log("Attempting to register user: $name ($email) with phone: $phoneNormalized");
    
    if ($stmt->execute($params)) {
        $userId = $pdo->lastInsertId();
        error_log("User registered successfully! ID: $userId");
        // Clear session OTP data after successful registration
        unset($_SESSION['signup_otp_code']);
        unset($_SESSION['signup_otp_email']);
        unset($_SESSION['signup_otp_name']);
        unset($_SESSION['signup_otp_expires']);
        unset($_SESSION['signup_otp_verified']);
        
        echo json_encode([
            "success" => true,
            "message" => "Account created successfully! You can now log in with your email/phone and CAPTCHA."
        ]);
    } else {
        $errInfo = $stmt->errorInfo();
        error_log("Registration execute failed: " . json_encode($errInfo));
        echo json_encode([
            "success" => false,
            "message" => "Failed to create account. Please try again."
        ]);
    }
    
} catch (PDOException $e) {
    $errorMsg = $e->getMessage();
    error_log("Register After OTP PDO Exception: " . $errorMsg);
    
    // Provide more helpful error messages
    if (strpos($errorMsg, "doesn't exist") !== false) {
        echo json_encode([
            "success" => false,
            "message" => "Database table error. Please run the database setup: /USERS/api/fix-users-table.php"
        ]);
    } elseif (strpos($errorMsg, "Unknown column") !== false) {
        echo json_encode([
            "success" => false,
            "message" => "Database column missing. Please run: /USERS/api/fix-users-table.php"
        ]);
    } elseif (strpos($errorMsg, "Duplicate entry") !== false) {
        echo json_encode([
            "success" => false,
            "message" => "Email or phone number already registered."
        ]);
    } else {
        echo json_encode([
            "success" => false,
            "message" => "Database error: " . $errorMsg
        ]);
    }
} catch (Exception $e) {
    error_log("Register After OTP General Exception: " . $e->getMessage());
    echo json_encode([
        "success" => false,
        "message" => "Server error: " . $e->getMessage()
    ]);
}

?>
