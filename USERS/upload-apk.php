<?php
/**
 * APK Upload Script
 * 
 * ⚠️ SECURITY WARNING: DELETE THIS FILE AFTER UPLOADING THE APK! ⚠️
 * 
 * Instructions:
 * 1. Upload this file to: /EMERGENCY-COM/USERS/upload-apk.php
 * 2. Access it via: https://emergency-comm.alertaraqc.com/upload-apk.php
 * 3. Upload your APK file
 * 4. DELETE THIS FILE IMMEDIATELY AFTER UPLOAD!
 */

// Simple password protection (change this password!)
$UPLOAD_PASSWORD = 'ChangeThisPassword123!'; // CHANGE THIS!

// Check if password is provided
session_start();
if (isset($_POST['password'])) {
    if ($_POST['password'] === $UPLOAD_PASSWORD) {
        $_SESSION['authenticated'] = true;
    } else {
        $error = 'Incorrect password!';
    }
}

// Check authentication
if (!isset($_SESSION['authenticated']) || !$_SESSION['authenticated']) {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>APK Upload - Authentication</title>
        <style>
            body { font-family: Arial, sans-serif; max-width: 400px; margin: 100px auto; padding: 20px; }
            input[type="password"], button { width: 100%; padding: 10px; margin: 10px 0; }
            button { background: #007cba; color: white; border: none; cursor: pointer; }
            .error { color: red; }
        </style>
    </head>
    <body>
        <h2>APK Upload - Login Required</h2>
        <?php if (isset($error)) echo '<p class="error">' . htmlspecialchars($error) . '</p>'; ?>
        <form method="POST">
            <input type="password" name="password" placeholder="Enter password" required>
            <button type="submit">Login</button>
        </form>
        <p><small>Default password: ChangeThisPassword123!</small></p>
    </body>
    </html>
    <?php
    exit;
}

// Handle file upload
$uploadDir = __DIR__ . '/downloads/';
$targetFile = $uploadDir . 'emergency-comms-app.apk';
$uploadOk = true;
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['apkfile'])) {
    // Check if file was uploaded
    if ($_FILES['apkfile']['error'] !== UPLOAD_ERR_OK) {
        $message = 'Upload error: ' . $_FILES['apkfile']['error'];
        $uploadOk = false;
    } else {
        // Check file size (max 50MB)
        if ($_FILES['apkfile']['size'] > 50 * 1024 * 1024) {
            $message = 'File too large. Maximum size: 50MB';
            $uploadOk = false;
        }
        
        // Check if file is APK
        $fileExt = strtolower(pathinfo($_FILES['apkfile']['name'], PATHINFO_EXTENSION));
        if ($fileExt !== 'apk') {
            $message = 'Only APK files are allowed.';
            $uploadOk = false;
        }
        
        // Try to upload
        if ($uploadOk) {
            // Create directory if it doesn't exist
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            // Backup old file if exists
            if (file_exists($targetFile)) {
                $backupFile = $uploadDir . 'emergency-comms-app.apk.backup.' . date('Y-m-d_His');
                rename($targetFile, $backupFile);
                $message .= 'Old file backed up as: ' . basename($backupFile) . '<br>';
            }
            
            // Move uploaded file
            if (move_uploaded_file($_FILES['apkfile']['tmp_name'], $targetFile)) {
                $fileSize = filesize($targetFile);
                $fileSizeMB = round($fileSize / 1024 / 1024, 2);
                $message .= '✅ <strong>APK uploaded successfully!</strong><br>';
                $message .= 'File: ' . basename($targetFile) . '<br>';
                $message .= 'Size: ' . $fileSizeMB . ' MB (' . number_format($fileSize) . ' bytes)<br>';
                $message .= 'Date: ' . date('Y-m-d H:i:s') . '<br><br>';
                $message .= '<strong style="color: red;">⚠️ IMPORTANT: DELETE THIS UPLOAD SCRIPT NOW for security!</strong>';
            } else {
                $message = '❌ Error: Failed to save file. Check directory permissions.';
            }
        }
    }
}

// Get current file info
$currentFileSize = 0;
$currentFileDate = 'N/A';
if (file_exists($targetFile)) {
    $currentFileSize = filesize($targetFile);
    $currentFileDate = date('Y-m-d H:i:s', filemtime($targetFile));
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>APK Upload Tool</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 600px; margin: 50px auto; padding: 20px; background: #f5f5f5; }
        .container { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #333; border-bottom: 2px solid #007cba; padding-bottom: 10px; }
        .warning { background: #fff3cd; border: 1px solid #ffc107; padding: 15px; border-radius: 4px; margin: 20px 0; }
        .success { background: #d4edda; border: 1px solid #28a745; padding: 15px; border-radius: 4px; margin: 20px 0; }
        .error { background: #f8d7da; border: 1px solid #dc3545; padding: 15px; border-radius: 4px; margin: 20px 0; }
        .info { background: #d1ecf1; border: 1px solid #17a2b8; padding: 15px; border-radius: 4px; margin: 20px 0; }
        input[type="file"] { margin: 20px 0; }
        button { background: #007cba; color: white; padding: 12px 30px; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; }
        button:hover { background: #005a87; }
        .logout { float: right; background: #6c757d; padding: 8px 15px; font-size: 14px; }
        .logout:hover { background: #5a6268; }
        .current-file { margin: 20px 0; padding: 15px; background: #f8f9fa; border-radius: 4px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>📱 APK Upload Tool</h1>
        
        <div class="warning">
            <strong>⚠️ SECURITY WARNING:</strong> Delete this file immediately after uploading the APK!
        </div>
        
        <?php if ($message): ?>
            <div class="<?php echo $uploadOk ? 'success' : 'error'; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <div class="current-file">
            <h3>Current APK File:</h3>
            <?php if (file_exists($targetFile)): ?>
                <p><strong>File:</strong> emergency-comms-app.apk</p>
                <p><strong>Size:</strong> <?php echo round($currentFileSize / 1024 / 1024, 2); ?> MB</p>
                <p><strong>Last Modified:</strong> <?php echo $currentFileDate; ?></p>
                <p><a href="downloads/emergency-comms-app.apk" target="_blank">Download Current File</a></p>
            <?php else: ?>
                <p>No APK file found on server.</p>
            <?php endif; ?>
        </div>
        
        <form method="POST" enctype="multipart/form-data">
            <h3>Upload New APK:</h3>
            <input type="file" name="apkfile" accept=".apk" required>
            <br>
            <button type="submit">Upload APK</button>
            <a href="?logout=1" class="logout">Logout</a>
        </form>
        
        <?php if (isset($_GET['logout'])): ?>
            <?php
            session_destroy();
            header('Location: upload-apk.php');
            exit;
            ?>
        <?php endif; ?>
        
        <div class="info" style="margin-top: 30px;">
            <h4>Instructions:</h4>
            <ol>
                <li>Select the new APK file (should be ~16.2 MB)</li>
                <li>Click "Upload APK"</li>
                <li>Wait for upload to complete</li>
                <li><strong>DELETE THIS FILE IMMEDIATELY after successful upload!</strong></li>
                <li>Test the download: <a href="https://emergency-comm.alertaraqc.com/USERS/downloads/emergency-comms-app.apk" target="_blank">Download Link</a></li>
            </ol>
        </div>
    </div>
</body>
</html>

