<?php
/**
 * Enable Login Debug Mode
 * Temporarily enables detailed error messages in login-web.php
 */

$loginFile = __DIR__ . '/login-web.php';

if (!file_exists($loginFile)) {
    die("login-web.php not found");
}

// Read the file
$content = file_get_contents($loginFile);

// Check if already enabled
if (strpos($content, "// DEBUG MODE ENABLED") !== false) {
    echo "<h1>Debug Mode Already Enabled</h1>";
    echo "<p>Debug mode is already active. Check the login response in Network tab.</p>";
    echo "<p><a href='../login.php?reset=attempts'>Go to Login</a></p>";
    exit;
}

// Backup original
$backupFile = $loginFile . '.backup.' . date('Y-m-d_H-i-s');
copy($loginFile, $backupFile);
echo "<p>✓ Backup created: " . basename($backupFile) . "</p>";

// Enable display_errors
$content = str_replace(
    "ini_set('display_errors', 0);",
    "ini_set('display_errors', 1); // DEBUG MODE ENABLED",
    $content
);

// Add detailed error logging before password verification
$debugCode = "
    // DEBUG: Log login attempt details
    error_log('=== LOGIN DEBUG ===');
    error_log('Email: ' . \$email);
    error_log('Password length: ' . strlen(\$plainPassword));
    error_log('Admin found: ' . (\$admin ? 'YES (ID: ' . \$admin['id'] . ')' : 'NO'));
    if (\$admin) {
        error_log('Admin status: ' . \$admin['status']);
        error_log('Password verify result: ' . (password_verify(\$plainPassword, \$admin['password']) ? 'SUCCESS' : 'FAILED'));
        error_log('Stored hash: ' . substr(\$admin['password'], 0, 30) . '...');
    }
    error_log('reCAPTCHA valid: ' . (\$recaptchaValid ? 'YES' : 'NO'));
    error_log('OTP verified: ' . (\$otpVerified ? 'YES' : 'NO'));
    error_log('OTP required: ' . (\$requireOtp ? 'YES' : 'NO'));
    error_log('==================');
";

// Insert debug code before password verification
$content = str_replace(
    "    \$stmt->execute([\$email]);\n    \$admin = \$stmt->fetch();\n\n    // Verify password",
    "    \$stmt->execute([\$email]);\n    \$admin = \$stmt->fetch();\n\n" . $debugCode . "\n    // Verify password",
    $content
);

// Write back
file_put_contents($loginFile, $content);

echo "<h1>Debug Mode Enabled</h1>";
echo "<div style='background: #d4edda; padding: 15px; border-left: 4px solid #28a745; margin: 20px 0;'>";
echo "<h3>✓ Debug mode activated!</h3>";
echo "<p>Now when you try to login:</p>";
echo "<ol>";
echo "<li>Open browser Developer Tools (F12)</li>";
echo "<li>Go to <strong>Network</strong> tab</li>";
echo "<li>Try to log in</li>";
echo "<li>Click on the <code>login-web.php</code> request</li>";
echo "<li>Check the <strong>Response</strong> tab - you'll see detailed error messages</li>";
echo "<li>Also check <strong>Console</strong> tab for any JavaScript errors</li>";
echo "</ol>";
echo "</div>";

echo "<div style='background: #fff3cd; padding: 15px; border-left: 4px solid #ffc107; margin: 20px 0;'>";
echo "<h3>⚠️ Important</h3>";
echo "<p>Debug mode will log detailed information. After debugging, run:</p>";
echo "<p><a href='disable-login-debug.php' style='background: #dc3545; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Disable Debug Mode</a></p>";
echo "</div>";

echo "<p><a href='../login.php?reset=attempts' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Go to Login Now</a></p>";
?>

