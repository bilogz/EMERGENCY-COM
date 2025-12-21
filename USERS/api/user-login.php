<?php
/**
 * User Login API
 * Handles user login via phone number (with or without name) and guest login
 */

session_start();
header('Content-Type: application/json');

// Include DB connection
require_once '../../ADMIN/api/db_connect.php';

// Get POST data
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// If JSON decode failed, try form data
if ($data === null) {
    $data = $_POST;
}

// Check login type
$loginType = isset($data['login_type']) ? $data['login_type'] : 'standard';

try {
    if ($loginType === 'guest') {
        // Guest login - create temporary guest session
        $guestId = 'guest_' . bin2hex(random_bytes(8));
        $guestName = 'Guest User';
        
        $_SESSION['user_logged_in'] = true;
        $_SESSION['user_id'] = $guestId;
        $_SESSION['user_name'] = $guestName;
        $_SESSION['user_type'] = 'guest';
        $_SESSION['user_phone'] = null;
        
        echo json_encode([
            "success" => true,
            "message" => "Guest login successful!",
            "user_id" => $guestId,
            "username" => $guestName,
            "user_type" => "guest"
        ]);
        exit();
    }
    
    // Standard login - validate required fields
    if (!isset($data['phone'])) {
        echo json_encode(["success" => false, "message" => "Phone number is required."]);
        exit();
    }
    
    $phone = trim($data['phone']);
    $fullName = isset($data['full_name']) ? trim($data['full_name']) : null;
    
    if (empty($phone)) {
        echo json_encode(["success" => false, "message" => "Phone number is required."]);
        exit();
    }
    
    // Normalize phone number (remove spaces, dashes, etc.)
    $phone = preg_replace('/[^0-9+]/', '', $phone);
    
    // Query user from database by phone number
    // Check if phone column exists, if not, we'll need to handle it differently
    $stmt = $pdo->prepare("SHOW COLUMNS FROM users LIKE 'phone'");
    $stmt->execute();
    $phoneColumnExists = $stmt->rowCount() > 0;
    
    if ($phoneColumnExists) {
        // If phone column exists, query by phone
        if ($fullName) {
            // Login with name and phone
            $stmt = $pdo->prepare("SELECT id, name, email, phone FROM users WHERE phone = ? AND name = ?");
            $stmt->execute([$phone, $fullName]);
        } else {
            // Login with phone only
            $stmt = $pdo->prepare("SELECT id, name, email, phone FROM users WHERE phone = ?");
            $stmt->execute([$phone]);
        }
    } else {
        // If phone column doesn't exist, try to find by name only (fallback)
        if ($fullName) {
            $stmt = $pdo->prepare("SELECT id, name, email FROM users WHERE name = ?");
            $stmt->execute([$fullName]);
        } else {
            echo json_encode(["success" => false, "message" => "Phone number login requires phone field in database. Please contact administrator."]);
            exit();
        }
    }
    
    $user = $stmt->fetch();
    
    if ($user) {
        // Set session variables
        $_SESSION['user_logged_in'] = true;
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_email'] = isset($user['email']) ? $user['email'] : null;
        $_SESSION['user_phone'] = isset($user['phone']) ? $user['phone'] : $phone;
        $_SESSION['user_type'] = 'registered';
        $_SESSION['user_token'] = bin2hex(random_bytes(16));
        
        echo json_encode([
            "success" => true,
            "message" => "Login successful!",
            "user_id" => $user['id'],
            "username" => $user['name'],
            "email" => isset($user['email']) ? $user['email'] : null,
            "phone" => isset($user['phone']) ? $user['phone'] : $phone,
            "user_type" => "registered"
        ]);
    } else {
        echo json_encode(["success" => false, "message" => "User not found. Please check your phone number and name, or sign up for a new account."]);
    }
    
} catch (PDOException $e) {
    error_log("User Login PDO Exception: " . $e->getMessage());
    echo json_encode([
        "success" => false, 
        "message" => "Database error occurred. Please try again."
    ]);
} catch (Exception $e) {
    error_log("User Login General Exception: " . $e->getMessage());
    echo json_encode([
        "success" => false, 
        "message" => "Server error occurred. Please try again."
    ]);
}
?>

