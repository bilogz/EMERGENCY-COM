<?php
/**
 * Register Facebook User
 * Creates a new user account with Facebook OAuth data
 */

session_start();
header('Content-Type: application/json');

// Check if request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit;
}

// Required fields
$facebookId = $input['facebook_id'] ?? '';
$name = $input['name'] ?? '';
$email = $input['email'] ?? '';
$phone = $input['phone'] ?? '';
$barangay = $input['barangay'] ?? '';
$district = $input['district'] ?? '';
$houseNumber = $input['house_number'] ?? '';
$street = $input['street'] ?? '';
$nationality = $input['nationality'] ?? 'Filipino';

// Validate required fields
if (empty($facebookId) || empty($name) || empty($phone)) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

// Include database connection
require_once 'db_connect.php';

try {
    // Check if Facebook ID already exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE facebook_id = ?");
    $stmt->execute([$facebookId]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Facebook account already registered']);
        exit;
    }
    
    // Check if phone already exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE phone = ?");
    $stmt->execute([$phone]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Phone number already registered']);
        exit;
    }
    
    // Check if email already exists (if provided)
    if ($email) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Email already registered']);
            exit;
        }
    }
    
    // Insert new user
    $stmt = $pdo->prepare("INSERT INTO users (
        name, email, phone, facebook_id, oauth_provider,
        barangay, house_number, address, 
        status, email_verified, phone_verified, user_type,
        created_at, updated_at
    ) VALUES (
        :name, :email, :phone, :facebook_id, 'facebook',
        :barangay, :house_number, :address,
        'active', 1, 1, 'citizen',
        NOW(), NOW()
    )");
    
    $address = '';
    if ($houseNumber && $street && $barangay) {
        $address = "{$houseNumber} {$street}, {$barangay}, Quezon City";
    }
    
    $stmt->execute([
        ':name' => $name,
        ':email' => $email ?: null,
        ':phone' => $phone,
        ':facebook_id' => $facebookId,
        ':barangay' => $barangay,
        ':house_number' => $houseNumber,
        ':address' => $address
    ]);
    
    $userId = $pdo->lastInsertId();
    
    // Create user preferences
    $stmt = $pdo->prepare("INSERT INTO user_preferences (user_id) VALUES (?)");
    $stmt->execute([$userId]);
    
    // Log the registration
    $stmt = $pdo->prepare("INSERT INTO user_activity_logs (user_id, activity_type, description, status) VALUES (?, 'registration', 'User registered via Facebook', 'success')");
    $stmt->execute([$userId]);
    
    // Set session
    $_SESSION['user_id'] = $userId;
    $_SESSION['user_name'] = $name;
    $_SESSION['user_email'] = $email;
    $_SESSION['login_method'] = 'facebook';
    
    echo json_encode([
        'success' => true,
        'message' => 'Registration successful',
        'user_id' => $userId,
        'user_name' => $name
    ]);
    
} catch (PDOException $e) {
    error_log('Facebook registration error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
}
