<?php
/**
 * Get User Profile API
 * Returns current user's profile information
 */

session_start();
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    echo json_encode([
        "success" => false,
        "message" => "You must be logged in to view your profile."
    ]);
    exit();
}

// Check if user is a registered user (not guest)
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'registered') {
    echo json_encode([
        "success" => false,
        "message" => "Only registered users can view their profile."
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

try {
    // Build query to get all available user fields
    $query = "SELECT id, name, email, phone, status, user_type, created_at, updated_at";

    // Include optional columns if present (use SHOW COLUMNS for broader hosting compatibility)
    $optionalColumns = ['nationality', 'district', 'barangay', 'house_number', 'street', 'address', 'notification_sound'];
    $existingCols = [];
    try {
        $colsStmt = $pdo->query("SHOW COLUMNS FROM users");
        $existingCols = $colsStmt ? $colsStmt->fetchAll(PDO::FETCH_COLUMN, 0) : [];
    } catch (PDOException $e) {
        $existingCols = [];
        error_log('SHOW COLUMNS failed (users): ' . $e->getMessage());
    }

    foreach ($optionalColumns as $column) {
        if (!empty($existingCols) && in_array($column, $existingCols, true)) {
            $query .= ", $column";
        }
    }
    
    $query .= " FROM users WHERE id = ? LIMIT 1";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        echo json_encode([
            "success" => false,
            "message" => "User not found."
        ]);
        exit();
    }
    
    // Remove sensitive fields and format response
    unset($user['password']);
    
    echo json_encode([
        "success" => true,
        "user" => $user
    ]);
    
} catch (PDOException $e) {
    error_log("Get User Profile PDO Exception: " . $e->getMessage());
    echo json_encode([
        "success" => false,
        "message" => "Database error occurred. Please try again."
    ]);
} catch (Exception $e) {
    error_log("Get User Profile General Exception: " . $e->getMessage());
    echo json_encode([
        "success" => false,
        "message" => "An error occurred. Please try again."
    ]);
}
?>


