<?php
header("Content-Type: application/json; charset=UTF-8");
include_once "db_connect.php";

$response = [];

// Allow POST only
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        "success" => false,
        "message" => "Invalid request method."
    ]);
    exit;
}

// Read JSON input
$data = json_decode(file_get_contents("php://input"), true);

// Validate required fields
if (
    empty($data['user_id']) ||
    empty($data['current_password']) ||
    empty($data['new_password'])
) {
    echo json_encode([
        "success" => false,
        "message" => "Missing required fields."
    ]);
    exit;
}

$user_id          = $data['user_id'];
$current_password = $data['current_password'];
$new_password     = $data['new_password'];

// Optional: enforce minimum password length
if (strlen($new_password) < 8) {
    echo json_encode([
        "success" => false,
        "message" => "New password must be at least 8 characters long."
    ]);
    exit;
}

// ================= FETCH CURRENT PASSWORD =================
$sql = "SELECT password FROM users WHERE id = ? LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode([
        "success" => false,
        "message" => "User not found."
    ]);
    exit;
}

$row = $result->fetch_assoc();
$hashed_password = $row['password'];

// ================= VERIFY CURRENT PASSWORD =================
if (!password_verify($current_password, $hashed_password)) {
    echo json_encode([
        "success" => false,
        "message" => "Incorrect current password."
    ]);
    exit;
}

// ================= UPDATE PASSWORD =================
$new_hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

$update_sql = "UPDATE users SET password = ? WHERE id = ?";
$update_stmt = $conn->prepare($update_sql);
$update_stmt->bind_param("si", $new_hashed_password, $user_id);

if ($update_stmt->execute()) {
    echo json_encode([
        "success" => true,
        "message" => "Password changed successfully."
    ]);
} else {
    echo json_encode([
        "success" => false,
        "message" => "Database error."
    ]);
}

$stmt->close();
$update_stmt->close();
$conn->close();
?>
