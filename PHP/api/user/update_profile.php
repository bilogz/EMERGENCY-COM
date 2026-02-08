<?php
header("Content-Type: application/json; charset=UTF-8");
require_once "db_connect.php"; // Adjust path if needed

/** @var PDO $pdo */

// Custom response helper if not using the one from db_connect
if (!class_exists('apiResponse')) {
    class apiResponse {
        public static function error($msg, $code = 400, $debug = null) {
            http_response_code($code);
            echo json_encode([
                "success" => false,
                "message" => $msg,
                "debug" => $debug
            ]);
            exit;
        }

        public static function success($data, $msg) {
            echo json_encode([
                "success" => true,
                "data" => $data,
                "message" => $msg
            ]);
            exit;
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    apiResponse::error("Invalid request method.", 405);
}

$user_id = $_POST['user_id'] ?? null;

if (!$user_id) {
    apiResponse::error("User ID is required.", 400);
}

$username = $_POST['username'] ?? null;
$email    = $_POST['email'] ?? null;
$phone    = $_POST['phone'] ?? null;

$profile_pic_path = null;

// Handle File Upload
if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === UPLOAD_ERR_OK) {

    $upload_dir = "uploads/profile_pics/";      // Public path
    $full_upload_path = "../" . $upload_dir;    // Server path

    if (!is_dir($full_upload_path)) {
        if (!mkdir($full_upload_path, 0755, true)) {
            apiResponse::error("Failed to create upload directory.", 500);
        }
    }

    $tmp_name = $_FILES['profile_pic']['tmp_name'];
    $file_name = basename($_FILES['profile_pic']['name']);
    $file_size = $_FILES['profile_pic']['size'];
    $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

    $allowed_ext = ["jpg", "jpeg", "png", "webp"];

    if (!in_array($file_ext, $allowed_ext)) {
        apiResponse::error("Invalid file type. Only JPG, PNG, and WEBP allowed.");
    }

    // Verify it's actually an image
    if (getimagesize($tmp_name) === false) {
        apiResponse::error("File is not an image.");
    }

    if ($file_size > 2 * 1024 * 1024) {
        apiResponse::error("File too large. Max size is 2MB.");
    }

    $new_name = "user_" . $user_id . "_" . time() . "." . $file_ext;
    $target_file = $full_upload_path . $new_name;

    if (!move_uploaded_file($tmp_name, $target_file)) {
        apiResponse::error("Failed to upload image to server folder.", 500);
    }

    $profile_pic_path = $upload_dir . $new_name;
}

try {

    // Prepare dynamic SQL based on provided fields
    $fields = [];
    $params = [];

    if ($username !== null) {
        $fields[] = "name = ?";
        $params[] = $username;
    }

    if ($email !== null) {
        $fields[] = "email = ?";
        $params[] = $email;
    }

    if ($phone !== null) {
        $fields[] = "phone = ?";
        $params[] = $phone;
    }

    if ($profile_pic_path !== null) {
        $fields[] = "profile_pic = ?";
        $params[] = $profile_pic_path;
    }

    if (empty($fields)) {
        apiResponse::error("No fields to update.", 400);
    }

    $sql = "UPDATE users SET " . implode(", ", $fields) . " WHERE id = ?";
    $params[] = $user_id;

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    // Return the new profile pic URL if uploaded
    $data = null;

    if ($profile_pic_path) {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http";
        $data = [
            "profile_pic_url" => $protocol . "://" . $_SERVER['HTTP_HOST'] . "/PHP/" . $profile_pic_path
        ];
    }

    apiResponse::success($data, "Profile updated successfully.");

} catch (PDOException $e) {
    apiResponse::error("Database error occurred.", 500, $e->getMessage());
} catch (Exception $e) {
    apiResponse::error("An unexpected error occurred.", 500, $e->getMessage());
}
?>
