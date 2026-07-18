<?php
header('Content-Type: application/json; charset=utf-8');

require_once '../shared/db_connect.php';

// Allow POST only
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    apiResponse::error("Invalid request method. Use POST.", 405);
}

// Check if a file was uploaded
$fileKey = null;
if (isset($_FILES['media'])) {
    $fileKey = 'media';
} elseif (isset($_FILES['file'])) {
    $fileKey = 'file';
}

if ($fileKey === null) {
    apiResponse::error("No file uploaded. Please submit a file using key 'media' or 'file'.", 400);
}

$file = $_FILES[$fileKey];

// Check for PHP upload errors
if ($file['error'] !== UPLOAD_ERR_OK) {
    switch ($file['error']) {
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:
            apiResponse::error("The uploaded file exceeds the maximum allowed size.", 400);
            break;
        case UPLOAD_ERR_PARTIAL:
            apiResponse::error("The file was only partially uploaded.", 400);
            break;
        case UPLOAD_ERR_NO_FILE:
            apiResponse::error("No file was uploaded.", 400);
            break;
        default:
            apiResponse::error("An error occurred during file upload (Code: " . $file['error'] . ").", 400);
    }
}

// Validate file size (limit to 20MB)
$maxFileSize = 20 * 1024 * 1024; // 20 megabytes
if ($file['size'] > $maxFileSize) {
    apiResponse::error("File size exceeds the limit of 20MB.", 400);
}

// Validate mime type or extension
$allowedExtensions = [
    // Images
    'jpg', 'jpeg', 'png', 'gif', 'webp',
    // Videos
    'mp4', 'mov', 'avi', 'mkv', '3gp',
    // Audios
    'mp3', 'wav', 'm4a', 'aac', 'ogg'
];

$filename = $file['name'];
$ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

if (!in_array($ext, $allowedExtensions)) {
    apiResponse::error("Invalid file type. Allowed types: " . implode(', ', $allowedExtensions), 400);
}

// Create uploads directory if not exists
$uploadDir = __DIR__ . '/../uploads';
if (!file_exists($uploadDir)) {
    if (!mkdir($uploadDir, 0755, true)) {
        apiResponse::error("Failed to create uploads directory on the server.", 500);
    }
}

// Generate unique secure filename
$newFilename = uniqid('media_', true) . '.' . $ext;
$targetPath = $uploadDir . '/' . $newFilename;

// Move the file to the target directory
if (move_uploaded_file($file['tmp_name'], $targetPath)) {
    // Return relative URL from PHP/api/
    $mediaUrl = 'uploads/' . $newFilename;
    $fileSize = filesize($targetPath);
    $fileType = mime_content_type($targetPath);
    
    apiResponse::success([
        'file_url' => $mediaUrl,
        'file_path' => $targetPath,
        'file_size' => $fileSize,
        'file_type' => $fileType,
        'filename' => $newFilename
    ], "Media uploaded successfully", 200);
} else {
    apiResponse::error("Failed to save the uploaded file on the server.", 500);
}
?>
