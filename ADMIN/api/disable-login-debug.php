<?php
/**
 * Disable Login Debug Mode
 * Restores login-web.php to production mode
 */

$loginFile = __DIR__ . '/login-web.php';

if (!file_exists($loginFile)) {
    die("login-web.php not found");
}

// Find backup file
$backupFiles = glob($loginFile . '.backup.*');
if (empty($backupFiles)) {
    die("No backup file found. Debug mode may not be enabled.");
}

// Get most recent backup
usort($backupFiles, function($a, $b) {
    return filemtime($b) - filemtime($a);
});
$backupFile = $backupFiles[0];

// Restore from backup
copy($backupFile, $loginFile);

echo "<h1>Debug Mode Disabled</h1>";
echo "<div style='background: #d4edda; padding: 15px; border-left: 4px solid #28a745; margin: 20px 0;'>";
echo "<h3>✓ Debug mode disabled!</h3>";
echo "<p>login-web.php has been restored from backup: " . basename($backupFile) . "</p>";
echo "</div>";

echo "<p><a href='../login.php'>Go to Login</a></p>";
?>

