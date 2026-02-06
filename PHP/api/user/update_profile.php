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

// OPTIONAL: If you use sessions for auth, uncomment this
// session_start();
// $user_id = $_SESSION['user_id'] ?? null;

// TEMP: user_id from POST (NOT recommended for production)
$user_id = $_POST['user_id'] ?? null;

if (!$user_id) {
    echo json_encode([
        "success" => false,
        "message" => "User ID is required."
    ]);
    exit;
}

// Get text fields
$username = $_POST['username'] ?? null;
$email    = $_POST['email'] ?? null;
$phone    = $_POST['phone'] ?? null;

$profile_pic_path = null;

// ================= IMAGE UPLOAD =================
if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === UPLOAD_ERR_OK) {

    $upload_dir = "../uploads/profile_pics/";
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    $tmp_name = $_FILES['profile_pic']['tmp_name'];
    $file_name = $_FILES['profile_pic']['name'];
    $file_size = $_FILES['profile_pic']['size'];
    $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

    // Validate extension
    $allowed_ext = ["jpg", "jpeg", "png", "webp"];
    if (!in_array($file_ext, $allowed_ext)) {
        echo json_encode([
            "success" => false,
            "message" => "Invalid file type. Only JPG, PNG, and WEBP allowed."
        ]);
        exit;
    }

    // Validate MIME type
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime  = finfo_file($finfo, $tmp_name);
    finfo_close($finfo);

    $allowed_mime = ["image/jpeg", "image/png", "image/webp"];
    if (!in_array($mime, $allowed_mime)) {
        echo json_encode([
            "success" => false,
            "message" => "Invalid image file."
        ]);
        exit;
    }

    // Validate file size (2MB max)
    if ($file_size > 2 * 1024 * 1024) {
        echo json_encode([
            "success" => false,
            "message" => "File too large. Max size is 2MB."
        ]);
        exit;
    }

    // Create unique filename
    $new_name = "user_" . $user_id . "_" . time() . "." . $file_ext;
    $target_file = $upload_dir . $new_name;

    if (!move_uploaded_file($tmp_name, $target_file)) {
        echo json_encode([
            "success" => false,
            "message" => "Failed to upload image."
        ]);
        exit;
    }

    // Save relative path
    $profile_pic_path = "uploads/profile_pics/" . $new_name;
}

// ================= DATABASE UPDATE =================
$sql = "UPDATE users SET 
            name = COALESCE(?, name),
            email = COALESCE(?, email),
            phone = COALESCE(?, phone),
            profile_pic = COALESCE(?, profile_pic)
        WHERE id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param(
    "ssssi",
    $username,
    $email,
    $phone,
    $profile_pic_path,
    $user_id
);

if ($stmt->execute()) {
    echo json_encode([
        "success" => true,
        "message" => "Profile updated successfully."
    ]);
} else {
    echo json_encode([
        "success" => false,
        "message" => "Database error: " . $stmt->error
    ]);
}

$stmt->close();
$conn->close();
?>
