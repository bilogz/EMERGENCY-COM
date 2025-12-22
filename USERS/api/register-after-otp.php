<?php
/**
 * Register After OTP Verification
 * Completes user registration after OTP has been verified
 */

session_start();
header('Content-Type: application/json');

// Include DB connection
require_once '../../ADMIN/api/db_connect.php';

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
$required = ['name', 'email', 'phone', 'barangay', 'house_number', 'address'];
foreach ($required as $field) {
    if (!isset($data[$field])) {
        echo json_encode(["success" => false, "message" => ucfirst($field) . " is required."]);
        exit();
    }
}

$name = trim($data['name']);
$email = trim($data['email']);
$phone = trim($data['phone']);
$barangay = trim($data['barangay']);
$houseNumber = trim($data['house_number']);
$address = trim($data['address']);

// Validation
if (empty($name) || empty($email) || empty($phone) || empty($barangay) || empty($houseNumber) || empty($address)) {
    echo json_encode(["success" => false, "message" => "All fields must be filled."]);
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
    
    // Generate a random password for emergency access (user logs in via OTP, not password)
    $password = bin2hex(random_bytes(8)); // Generate 16-character random password
    
    // Hash the password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    // Ensure optional columns exist
    $needed = ['email' => "VARCHAR(255) DEFAULT NULL", 'phone' => "VARCHAR(20) DEFAULT NULL", 'barangay' => "VARCHAR(100) DEFAULT NULL", 'house_number' => "VARCHAR(50) DEFAULT NULL", 'address' => "TEXT DEFAULT NULL"];
    foreach ($needed as $col => $def) {
        $stmt = $pdo->prepare("SHOW COLUMNS FROM `users` LIKE ?");
        $stmt->execute([$col]);
        $colExists = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$colExists) {
            try {
                $pdo->exec("ALTER TABLE `users` ADD COLUMN `$col` $def");
            } catch (PDOException $ae) {
                error_log("Could not add column $col: " . $ae->getMessage());
            }
        }
    }
    
    // Insert new user
    $insertSql = "INSERT INTO `users` (`name`, `email`, `phone`, `password`, `barangay`, `house_number`, `address`, `created_at`) VALUES (:name, :email, :phone, :password, :barangay, :house_number, :address, NOW())";
    $stmt = $pdo->prepare($insertSql);
    $params = [
        ':name' => $name,
        ':email' => $email,
        ':phone' => $phoneNormalized,
        ':password' => $hashedPassword,
        ':barangay' => $barangay,
        ':house_number' => $houseNumber,
        ':address' => $address
    ];
    
    if ($stmt->execute($params)) {
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
    error_log("Register After OTP PDO Exception: " . $e->getMessage());
    echo json_encode([
        "success" => false,
        "message" => "Database error occurred. Please try again."
    ]);
} catch (Exception $e) {
    error_log("Register After OTP General Exception: " . $e->getMessage());
    echo json_encode([
        "success" => false,
        "message" => "Server error occurred. Please try again."
    ]);
}

?>
