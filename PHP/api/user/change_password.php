<?php
header("Content-Type: application/json; charset=UTF-8");
require_once "../db_connect.php";
/** @var PDO $pdo */

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    apiResponse::error("Invalid request method.", 405);
}

$data = json_decode(file_get_contents("php://input"), true);

if (
    empty($data['user_id']) ||
    empty($data['current_password']) ||
    empty($data['new_password'])
) {
    apiResponse::error("Missing required fields.", 400);
}

$user_id          = $data['user_id'];
$current_password = $data['current_password'];
$new_password     = $data['new_password'];

if (strlen($new_password) < 8) {
    apiResponse::error("New password must be at least 8 characters long.", 400);
}

try {
    $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ? LIMIT 1");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();

    if (!$user) {
        apiResponse::error("User not found.", 404);
    }

    if (!password_verify($current_password, $user['password'])) {
        apiResponse::error("Incorrect current password.", 401);
    }

    $new_hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

    $update_stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
    if ($update_stmt->execute([$new_hashed_password, $user_id])) {
        apiResponse::success(null, "Password changed successfully.");
    } else {
        apiResponse::error("Failed to update password.", 500);
    }

} catch (PDOException $e) {
    error_log("Change Password DB Error: " . $e->getMessage());
    apiResponse::error("Database error occurred.", 500, $e->getMessage());
} catch (Exception $e) {
    error_log("Change Password Error: " . $e->getMessage());
    apiResponse::error("An unexpected error occurred.", 500, $e->getMessage());
}
?>
