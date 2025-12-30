<?php
/**
 * Update OTP Setting on Server
 * This script updates ADMIN_REQUIRE_OTP in config.local.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: text/html; charset=utf-8');

echo "<h1>Update OTP Setting</h1>";
echo "<style>body { font-family: monospace; padding: 20px; } .success { color: green; } .error { color: red; } .info { color: blue; } pre { background: #f5f5f5; padding: 10px; }</style>";

$configFile = __DIR__ . '/config.local.php';

if (!file_exists($configFile)) {
    echo "<p class='error'>✗ config.local.php not found at: $configFile</p>";
    exit;
}

// Read the current file
$content = file_get_contents($configFile);

// Check current value
if (preg_match("/'ADMIN_REQUIRE_OTP'\s*=>\s*(true|false)/i", $content, $matches)) {
    $currentValue = strtolower($matches[1]);
    echo "<p>Current ADMIN_REQUIRE_OTP value: <strong>" . ($currentValue === 'true' ? 'TRUE' : 'FALSE') . "</strong></p>";
    
    if ($currentValue === 'true') {
        echo "<p class='success'>✓ OTP is already enabled!</p>";
        echo "<p><a href='../login.php'>Go to Login</a></p>";
        exit;
    }
}

// Update both production and development sections
$updated = false;

// Update production section (first occurrence)
$content = preg_replace(
    "/('ADMIN_REQUIRE_OTP'\s*=>\s*)(false|true)/i",
    "$1true",
    $content,
    1,
    $count1
);

// Update development section (second occurrence)
$content = preg_replace(
    "/('ADMIN_REQUIRE_OTP'\s*=>\s*)(false|true)/i",
    "$1true",
    $content,
    1,
    $count2
);

if ($count1 > 0 || $count2 > 0) {
    // Backup the original file
    $backupFile = $configFile . '.backup.' . date('Y-m-d_H-i-s');
    if (copy($configFile, $backupFile)) {
        echo "<p class='success'>✓ Backup created: " . basename($backupFile) . "</p>";
    }
    
    // Write the updated content
    if (file_put_contents($configFile, $content)) {
        echo "<p class='success'>✓ config.local.php updated successfully!</p>";
        echo "<p>Updated " . ($count1 + $count2) . " occurrence(s) of ADMIN_REQUIRE_OTP</p>";
        
        // Verify the change
        if (preg_match("/'ADMIN_REQUIRE_OTP'\s*=>\s*(true|false)/i", $content, $matches)) {
            $newValue = strtolower($matches[1]);
            echo "<p>New ADMIN_REQUIRE_OTP value: <strong>" . ($newValue === 'true' ? 'TRUE' : 'FALSE') . "</strong></p>";
        }
        
        echo "<hr>";
        echo "<h2>Next Steps</h2>";
        echo "<ol>";
        echo "<li>Clear any PHP opcode cache (if using OPcache): <code>opcache_reset()</code> or restart PHP-FPM</li>";
        echo "<li>Test the login: <a href='../login.php'>Go to Login</a></li>";
        echo "<li>OTP should now be required for login</li>";
        echo "</ol>";
        
        echo "<p><strong>Note:</strong> If OTP still doesn't appear, you may need to:</p>";
        echo "<ul>";
        echo "<li>Restart PHP-FPM: <code>sudo systemctl restart php-fpm</code> or <code>sudo service php8.3-fpm restart</code></li>";
        echo "<li>Clear browser cache and cookies</li>";
        echo "<li>Check that SMTP is configured for sending OTP emails</li>";
        echo "</ul>";
        
    } else {
        echo "<p class='error'>✗ Failed to write config.local.php (permission denied?)</p>";
        echo "<p class='info'>You may need to update it manually via SSH:</p>";
        echo "<pre>nano " . $configFile . "</pre>";
        echo "<p>Change <code>'ADMIN_REQUIRE_OTP' => false</code> to <code>'ADMIN_REQUIRE_OTP' => true</code></p>";
    }
} else {
    echo "<p class='error'>✗ Could not find ADMIN_REQUIRE_OTP in config.local.php</p>";
    echo "<p class='info'>The file structure may be different. Please update manually.</p>";
}

echo "<hr>";
echo "<p><a href='check-login-error.php'>Run Diagnostic Again</a> | <a href='../login.php'>Back to Login</a></p>";
?>

