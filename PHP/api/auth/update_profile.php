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

// Validate required fields
$user_id = $data['user_id'] ?? null;
if (!$user_id) {
    apiResponse::error("Missing required field: user_id", 400);
}

try {
    // Build update query dynamically based on provided fields
    $updateFields = [];
    $params = [':user_id' => $user_id];
    
    $allowedFields = ['name', 'email', 'phone', 'nationality', 'district', 'barangay', 'house_unit', 'street'];
    
    foreach ($allowedFields as $field) {
        if (isset($data[$field]) && $data[$field] !== '') {
            $updateFields[] = "$field = :$field";
            $params[":$field"] = $data[$field];
        }
    }
    
    if (empty($updateFields)) {
        apiResponse::error("No fields to update", 400);
    }
    
    // Add updated_at timestamp
    $updateFields[] = "updated_at = NOW()";
    
    $query = "UPDATE users SET " . implode(', ', $updateFields) . " WHERE id = :user_id";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    
    // Get updated user data
    $selectQuery = "
        SELECT id, name, email, phone, nationality, district, barangay, house_unit, street, status, user_type, profile_pic
        FROM users
        WHERE id = :user_id
    ";
    
    $stmt = $pdo->prepare($selectQuery);
    $stmt->execute([':user_id' => $user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        apiResponse::error("User not found", 404);
    }
    
    apiResponse::success(['user' => $user], "Profile updated successfully");

} catch (PDOException $e) {
    error_log("Update Profile DB Error: " . $e->getMessage());
    apiResponse::error("Database error occurred.", 500);
} catch (Exception $e) {
    error_log("Update Profile Error: " . $e->getMessage());
    apiResponse::error("An unexpected error occurred.", 500);
}
