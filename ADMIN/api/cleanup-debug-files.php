<?php
/**
 * Cleanup Debug and Test Files
 * This script removes all debug, test, and temporary files that are not needed in production
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: text/html; charset=utf-8');

echo "<h1>Cleanup Debug and Test Files</h1>";
echo "<style>body { font-family: monospace; padding: 20px; } .success { color: green; } .error { color: red; } .info { color: blue; } .warning { color: orange; } pre { background: #f5f5f5; padding: 10px; }</style>";

$baseDir = __DIR__;
$filesToDelete = [
    // Debug files
    'debug-login.php',
    'debug-login-error.php',
    'debug-login-request.php',
    
    // Test files
    'test-send-otp.php',
    'test-direct-login.php',
    'test-db-direct.php',
    'test-login-components.php',
    'test-db-connection.php',
    'test-gemini-simple.php',
    'test-gemini-key.php',
    'test_db_connection_options.php',
    'test_database_connection.php',
    'db_test.php',
    
    // Check/verification files (temporary)
    'check-and-update-password.php',
    'check-otp-setting.php',
    'check-mysql-on-server.php',
    'check-mysql.php',
    'verify-admin-account.php',
    
    // Setup/test scripts (one-time use)
    'setup-admin-user-table.php',
    'create-otp-table.php',
    'generate-password-hash.php',
    'reset-login-attempts.php',
    
    // SQL files (temporary)
    'update-password-joecel519.sql',
];

$deleted = [];
$notFound = [];
$errors = [];

echo "<h2>Files to Delete:</h2>";
echo "<ul>";
foreach ($filesToDelete as $file) {
    $filePath = $baseDir . '/' . $file;
    echo "<li>" . htmlspecialchars($file) . " - ";
    
    if (file_exists($filePath)) {
        if (unlink($filePath)) {
            echo "<span class='success'>✓ Deleted</span>";
            $deleted[] = $file;
        } else {
            echo "<span class='error'>✗ Failed to delete</span>";
            $errors[] = $file;
        }
    } else {
        echo "<span class='info'>⚠ Not found (already deleted or doesn't exist)</span>";
        $notFound[] = $file;
    }
    echo "</li>";
}
echo "</ul>";

echo "<hr>";
echo "<h2>Summary</h2>";
echo "<p class='success'>✓ Deleted: " . count($deleted) . " files</p>";
if (count($notFound) > 0) {
    echo "<p class='info'>⚠ Not found: " . count($notFound) . " files</p>";
}
if (count($errors) > 0) {
    echo "<p class='error'>✗ Errors: " . count($errors) . " files</p>";
    echo "<ul>";
    foreach ($errors as $error) {
        echo "<li>" . htmlspecialchars($error) . "</li>";
    }
    echo "</ul>";
}

echo "<hr>";
echo "<h2>Files Kept (Production Required)</h2>";
echo "<p>The following files are kept as they are needed for production:</p>";
echo "<ul>";
echo "<li>create-admin-account.php - For creating admin accounts</li>";
echo "<li>send-admin-otp.php - OTP sending functionality</li>";
echo "<li>verify-admin-otp.php - OTP verification</li>";
echo "<li>login-web.php - Login handler</li>";
echo "<li>All other API endpoints</li>";
echo "</ul>";

echo "<hr>";
echo "<p class='success'><strong>✓ Cleanup Complete!</strong></p>";
echo "<p>All debug and test files have been removed. The system is ready for production deployment.</p>";
?>

