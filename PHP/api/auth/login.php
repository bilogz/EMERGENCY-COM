<?php
header("Content-Type: application/json; charset=UTF-8");
require_once '../shared/db_connect.php';

/** @var PDO $pdo */

// Allow POST only
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    apiResponse::error("Invalid request method.", 405);
}

// Get the POST data (JSON)
$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (!is_array($data)) {
    apiResponse::error("Invalid JSON input.", 400);
}

$email = $data['email'] ?? null;
$password = $data['password'] ?? null;

// Validate required fields
if (!$email || !$password) {
    apiResponse::error("Missing required fields: email, password", 400);
}

try {
    $query = "
        SELECT 
            id,
            name,
            email,
            phone,
            status,
            user_type,
            profile_pic
        FROM users
        WHERE email = :email AND status = 'active'
    ";

    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':email', $email);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        apiResponse::error("Invalid email or password.", 401);
    }

    // Verify password (assuming passwords are hashed with password_verify)
    // For now, we'll do a simple comparison - you may need to adjust this based on your password hashing
    $query = "SELECT password FROM users WHERE email = :email";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':email', $email);
    $stmt->execute();
    $passwordData = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$passwordData || !password_verify($password, $passwordData['password'])) {
        apiResponse::error("Invalid email or password.", 401);
    }

    // Return user with correct field names for mobile app
    $userResponse = [
        'id' => $user['id'],
        'name' => $user['name'],
        'email' => $user['email'],
        'phone' => $user['phone'],
        'status' => $user['status'],
        'user_type' => $user['user_type'],
        'profile_pic' => $user['profile_pic']
    ];
    
    apiResponse::success(['user' => $userResponse], "Login successful");

} catch (PDOException $e) {
    error_log("Login DB Error: " . $e->getMessage());
    apiResponse::error("Database error occurred.", 500);
} catch (Exception $e) {
    error_log("Login Error: " . $e->getMessage());
    apiResponse::error("An unexpected error occurred.", 500);
}
