<?php
header("Content-Type: application/json; charset=UTF-8");
require_once 'db_connect.php';

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

$name = $data['name'] ?? null;
$email = $data['email'] ?? null;
$password = $data['password'] ?? null;
$phone = $data['phone'] ?? null;
$nationality = $data['nationality'] ?? null;
$district = $data['district'] ?? null;
$barangay = $data['barangay'] ?? null;
$house_unit = $data['house_unit'] ?? null;
$street = $data['street'] ?? null;

// Validate required fields
if (!$name || !$email || !$password) {
    apiResponse::error("Missing required fields: name, email, password", 400);
}

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    apiResponse::error("Invalid email format.", 400);
}

// Validate password strength (minimum 6 characters)
if (strlen($password) < 6) {
    apiResponse::error("Password must be at least 6 characters.", 400);
}

try {
    // Check if email already exists
    $checkQuery = "SELECT id FROM users WHERE email = :email";
    $checkStmt = $pdo->prepare($checkQuery);
    $checkStmt->bindParam(':email', $email);
    $checkStmt->execute();
    
    if ($checkStmt->fetch(PDO::FETCH_ASSOC)) {
        apiResponse::error("Email already registered.", 409);
    }

    // Hash password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // Insert new user
    $query = "
        INSERT INTO users
        (name, email, password, phone, nationality, district, barangay, house_number, street, status, user_type, email_verified)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', 'citizen', 0)
    ";

    $stmt = $pdo->prepare($query);
    $stmt->execute([
        $name,
        $email,
        $hashedPassword,
        $phone,
        $nationality,
        $district,
        $barangay,
        $house_unit,
        $street
    ]);

    $userId = $pdo->lastInsertId();

    // Get the created user
    $selectQuery = "
        SELECT 
            id,
            name,
            email,
            phone,
            status,
            user_type,
            profile_pic
        FROM users
        WHERE id = :id
    ";

    $selectStmt = $pdo->prepare($selectQuery);
    $selectStmt->bindParam(':id', $userId);
    $selectStmt->execute();
    $user = $selectStmt->fetch(PDO::FETCH_ASSOC);

    apiResponse::success(['user' => $user], "Registration successful");

} catch (PDOException $e) {
    error_log("Register DB Error: " . $e->getMessage());
    apiResponse::error("Database error occurred.", 500);
} catch (Exception $e) {
    error_log("Register Error: " . $e->getMessage());
    apiResponse::error("An unexpected error occurred.", 500);
}
