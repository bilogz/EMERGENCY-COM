<?php
header("Content-Type: application/json; charset=UTF-8");
require_once "../db_connect.php";
/** @var PDO $pdo */

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

if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === UPLOAD_ERR_OK) {
    $upload_dir = "../uploads/profile_pics/";
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    $tmp_name = $_FILES['profile_pic']['tmp_name'];
    $file_name = $_FILES['profile_pic']['name'];
    $file_size = $_FILES['profile_pic']['size'];
    $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

    $allowed_ext = ["jpg", "jpeg", "png", "webp"];
    if (!in_array($file_ext, $allowed_ext)) {
        apiResponse::error("Invalid file type. Only JPG, PNG, and WEBP allowed.");
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime  = finfo_file($finfo, $tmp_name);
    finfo_close($finfo);

    $allowed_mime = ["image/jpeg", "image/png", "image/webp"];
    if (!in_array($mime, $allowed_mime)) {
        apiResponse::error("Invalid image file.");
    }

    if ($file_size > 2 * 1024 * 1024) {
        apiResponse::error("File too large. Max size is 2MB.");
    }

    $new_name = "user_" . $user_id . "_" . time() . "." . $file_ext;
    $target_file = $upload_dir . $new_name;

    if (!move_uploaded_file($tmp_name, $target_file)) {
        apiResponse::error("Failed to upload image.", 500);
    }

    $profile_pic_path = "uploads/profile_pics/" . $new_name;
}

try {
    // Note: 'profile_pic' column must exist in your 'users' table.
    // Based on emer_comm_test.sql, it might be missing. 
    // Please add it if you intend to store profile pictures.
    $sql = "UPDATE users SET 
                name = COALESCE(?, name),
                email = COALESCE(?, email),
                phone = COALESCE(?, phone)
            WHERE id = ?";
    
    // If you add the column, use this query instead:
    /*
    $sql = "UPDATE users SET 
                name = COALESCE(?, name),
                email = COALESCE(?, email),
                phone = COALESCE(?, phone),
                profile_pic = COALESCE(?, profile_pic)
            WHERE id = ?";
    */

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$username, $email, $phone, $user_id]);

    apiResponse::success(null, "Profile updated successfully.");

} catch (PDOException $e) {
    error_log("Profile Update DB Error: " . $e->getMessage());
    apiResponse::error("Database error occurred.", 500, $e->getMessage());
} catch (Exception $e) {
    error_log("Profile Update Error: " . $e->getMessage());
    apiResponse::error("An unexpected error occurred.", 500, $e->getMessage());
}
?>
