<?php
header("Content-Type: application/json; charset=UTF-8");
require_once '../shared/db_connect.php';

/** @var PDO $pdo */

// Allow POST only
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    apiResponse::error("Invalid request method.", 405);
}

// Get the POST data (JSON) - logout doesn't require any data
$json = file_get_contents('php://input');
$data = json_decode($json, true);

// Allow empty requests for logout
if ($json !== '' && !is_array($data)) {
    apiResponse::error("Invalid JSON input.", 400);
}

try {
    // For now, logout is mainly a client-side operation
    // You might want to invalidate tokens or update user sessions here
    // For simplicity, we'll just return success
    
    apiResponse::success(null, "Logout successful");

} catch (PDOException $e) {
    error_log("Logout DB Error: " . $e->getMessage());
    apiResponse::error("Database error occurred.", 500);
} catch (Exception $e) {
    error_log("Logout Error: " . $e->getMessage());
    apiResponse::error("An unexpected error occurred.", 500);
}
