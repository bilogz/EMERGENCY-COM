<?php
header('Content-Type: application/json; charset=utf-8');

// CORS headers for React Native
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Skip db_connect for media upload since we don't need database
// Skip authentication for media upload - public endpoint

// Allow POST only
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Invalid request method. Use POST.']);
    exit;
}

// Wrap entire script in try-catch for error handling
try {
  // Check if a file was uploaded
  $fileKey = null;
  if (isset($_FILES['media'])) {
    $fileKey = 'media';
  } elseif (isset($_FILES['file'])) {
    $fileKey = 'file';
  }

  if ($fileKey === null) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'No file uploaded. Please submit a file using key \'media\' or \'file\'.']);
    exit;
  }

  $file = $_FILES[$fileKey];

  // Check for PHP upload errors
  if ($file['error'] !== UPLOAD_ERR_OK) {
    switch ($file['error']) {
      case UPLOAD_ERR_INI_SIZE:
      case UPLOAD_ERR_FORM_SIZE:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'The uploaded file exceeds the maximum allowed size.']);
        exit;
      case UPLOAD_ERR_PARTIAL:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'The file was only partially uploaded.']);
        exit;
      case UPLOAD_ERR_NO_FILE:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'No file was uploaded.']);
        exit;
      default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'An error occurred during file upload (Code: ' . $file['error'] . ').']);
        exit;
    }
  }

  // Validate file size (limit to 20MB)
  $maxFileSize = 20 * 1024 * 1024; // 20 megabytes
  if ($file['size'] > $maxFileSize) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'File size exceeds the limit of 20MB.']);
    exit;
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
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid file type. Allowed types: ' . implode(', ', $allowedExtensions)]);
    exit;
  }

  // Create uploads directory if not exists
  // Use absolute path to PHP/uploads (outside api folder)
  $uploadDir = dirname(__DIR__) . '/uploads';
  if (!file_exists($uploadDir)) {
    if (!mkdir($uploadDir, 0755, true)) {
      http_response_code(500);
      echo json_encode(['success' => false, 'message' => 'Failed to create uploads directory on the server. Path: ' . $uploadDir]);
      exit;
    }
  }

  // Generate unique secure filename
  $newFilename = uniqid('media_', true) . '.' . $ext;
  $targetPath = $uploadDir . '/' . $newFilename;

  // Move the file to the target directory
  if (move_uploaded_file($file['tmp_name'], $targetPath)) {
    // Return relative URL from PHP/
    $mediaUrl = 'uploads/' . $newFilename;
    $fileSize = filesize($targetPath);
    
    // Try to get mime type, fallback if function not available
    $fileType = 'application/octet-stream';
    if (function_exists('mime_content_type')) {
      $fileType = mime_content_type($targetPath);
    } else {
      // Fallback to basic mime type based on extension
      $mimeTypes = [
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif',
        'webp' => 'image/webp',
        'mp4' => 'video/mp4',
        'mov' => 'video/quicktime',
        'avi' => 'video/x-msvideo',
        'mkv' => 'video/x-matroska',
        'mp3' => 'audio/mpeg',
        'wav' => 'audio/wav',
        'm4a' => 'audio/mp4',
        'aac' => 'audio/aac',
        'ogg' => 'audio/ogg'
      ];
      $fileType = isset($mimeTypes[$ext]) ? $mimeTypes[$ext] : 'application/octet-stream';
    }
    
    echo json_encode([
      'success' => true,
      'message' => 'Media uploaded successfully',
      'file_url' => $mediaUrl,
      'file_path' => $targetPath,
      'file_size' => $fileSize,
      'file_type' => $fileType,
      'filename' => $newFilename
    ]);
  } else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to save the uploaded file on the server.']);
  }
} catch (Exception $e) {
  http_response_code(500);
  echo json_encode([
    'success' => false, 
    'message' => 'Server error: ' . $e->getMessage(),
    'error' => $e->getMessage()
  ]);
} catch (Error $e) {
  http_response_code(500);
  echo json_encode([
    'success' => false, 
    'message' => 'Server error: ' . $e->getMessage(),
    'error' => $e->getMessage()
  ]);
}
?>
