<?php
/**
 * Web Login Handler for Admin Panel
 * Sets PHP sessions after successful authentication
 */

session_start();
header('Content-Type: application/json');

// Include DB connection
require_once 'db_connect.php';

// Get POST data (can be JSON or form data)
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// If JSON decode failed, try form data
if ($data === null) {
    $data = $_POST;
}

// Validate required fields
if (!isset($data['email']) || !isset($data['password'])) {
    echo json_encode(["success" => false, "message" => "Missing email or password."]);
    exit();
}

$email = trim($data['email']);
$plainPassword = $data['password'];

if (empty($email) || empty($plainPassword)) {
    echo json_encode(["success" => false, "message" => "Email and password are required."]);
    exit();
}

try {
    // Query user from database
    $stmt = $pdo->prepare("SELECT id, name, email, password FROM users WHERE email = ?");
    
    if (!$stmt) {
        $errorInfo = $pdo->errorInfo();
        error_log("PDO Prepare Error: " . $errorInfo[2]);
        echo json_encode([
            "success" => false,
            "message" => "Database prepare error: " . $errorInfo[2]
        ]);
        exit();
    }

    $stmt->execute([$email]);
    $user = $stmt->fetch();

    // Verify password
    if ($user && password_verify($plainPassword, $user['password'])) {
        // Set session variables
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_user_id'] = $user['id'];
        $_SESSION['admin_username'] = $user['name'];
        $_SESSION['admin_email'] = $user['email'];
        $_SESSION['admin_token'] = bin2hex(random_bytes(16));

        echo json_encode([
            "success" => true,
            "message" => "Login successful!",
            "user_id" => $user['id'],
            "username" => $user['name'],
            "email" => $user['email']
        ]);
    } else {
        echo json_encode(["success" => false, "message" => "Invalid email or password."]);
    }

} catch (PDOException $e) {
    error_log("Login PDO Exception: " . $e->getMessage());
    echo json_encode([
        "success" => false, 
        "message" => "Database error occurred. Please try again."
    ]);
} catch (Exception $e) {
    error_log("Login General Exception: " . $e->getMessage());
    echo json_encode([
        "success" => false, 
        "message" => "Server error occurred. Please try again."
    ]);
}
?>









