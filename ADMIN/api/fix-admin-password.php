<?php
/**
 * Fix Admin Password - Quick Fix
 * This will verify and update the password if needed
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: text/html; charset=utf-8');

echo "<h1>Fix Admin Password</h1>";
echo "<style>body { font-family: monospace; padding: 20px; } .success { color: green; } .error { color: red; } .info { color: blue; } .warning { color: orange; } pre { background: #f5f5f5; padding: 10px; }</style>";

$adminEmail = 'joecel519@gmail.com';
$correctPassword = 'Admin#123';

// Connect
try {
    require_once __DIR__ . '/config.env.php';
    require_once __DIR__ . '/db_connect.php';
    
    if (!isset($pdo) || $pdo === null) {
        die("<p class='error'>✗ Database connection failed</p>");
    }
    
    echo "<p class='success'>✓ Connected to database</p>";
} catch (Exception $e) {
    die("<p class='error'>✗ Error: " . htmlspecialchars($e->getMessage()) . "</p>");
}

// Get current password hash
$stmt = $pdo->prepare("SELECT id, email, password, status FROM admin_user WHERE email = ?");
$stmt->execute([$adminEmail]);
$admin = $stmt->fetch();

if (!$admin) {
    die("<p class='error'>✗ Admin account not found</p>");
}

echo "<p class='success'>✓ Admin account found (ID: {$admin['id']})</p>";

// Test current password
$currentHash = $admin['password'];
$passwordWorks = password_verify($correctPassword, $currentHash);

echo "<h2>Password Test</h2>";
echo "<p>Testing password: <strong>$correctPassword</strong></p>";

if ($passwordWorks) {
    echo "<p class='success'>✓ Current password hash is CORRECT!</p>";
    echo "<p class='info'>The password should work. If login still fails, check:</p>";
    echo "<ul>";
    echo "<li>OTP requirement (visit: <a href='test-password-verification.php'>test-password-verification.php</a>)</li>";
    echo "<li>Account status: " . htmlspecialchars($admin['status']) . "</li>";
    echo "<li>reCAPTCHA verification</li>";
    echo "</ul>";
} else {
    echo "<p class='error'>✗ Current password hash is INCORRECT!</p>";
    echo "<p class='warning'>Updating password now...</p>";
    
    // Generate new hash
    $newHash = password_hash($correctPassword, PASSWORD_DEFAULT);
    
    // Update password
    $updateStmt = $pdo->prepare("UPDATE admin_user SET password = ?, updated_at = NOW() WHERE email = ?");
    $result = $updateStmt->execute([$newHash, $adminEmail]);
    
    if ($result) {
        echo "<p class='success'>✓ Password updated successfully!</p>";
        
        // Verify the update
        $verifyStmt = $pdo->prepare("SELECT password FROM admin_user WHERE email = ?");
        $verifyStmt->execute([$adminEmail]);
        $updated = $verifyStmt->fetch();
        
        if ($updated && password_verify($correctPassword, $updated['password'])) {
            echo "<p class='success'>✓ Verification: Password now works!</p>";
            echo "<p class='success'><strong>You can now log in with:</strong></p>";
            echo "<ul>";
            echo "<li>Email: <strong>$adminEmail</strong></li>";
            echo "<li>Password: <strong>$correctPassword</strong></li>";
            echo "</ul>";
        } else {
            echo "<p class='error'>✗ Verification failed after update</p>";
        }
    } else {
        echo "<p class='error'>✗ Update failed</p>";
    }
}

// Check account status
echo "<h2>Account Status</h2>";
if ($admin['status'] === 'active') {
    echo "<p class='success'>✓ Account status: ACTIVE</p>";
} else {
    echo "<p class='error'>✗ Account status: " . htmlspecialchars($admin['status']) . "</p>";
    echo "<p class='warning'>Account must be 'active' to login. Updating status...</p>";
    
    $statusStmt = $pdo->prepare("UPDATE admin_user SET status = 'active' WHERE email = ?");
    if ($statusStmt->execute([$adminEmail])) {
        echo "<p class='success'>✓ Account status updated to 'active'</p>";
    }
}

// Check OTP requirement
echo "<h2>OTP Configuration</h2>";
try {
    $requireOtp = getSecureConfig('ADMIN_REQUIRE_OTP', false);
    if ($requireOtp) {
        echo "<p class='warning'>⚠️ OTP is REQUIRED</p>";
        echo "<p class='info'>You must verify your email with OTP before logging in.</p>";
        echo "<p class='info'>After entering password, you'll be prompted for OTP code.</p>";
    } else {
        echo "<p class='success'>✓ OTP is NOT required</p>";
    }
} catch (Exception $e) {
    echo "<p class='error'>Could not check OTP config</p>";
}

echo "<hr>";
echo "<p><a href='../login.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Try Login Now</a></p>";
echo "<p><a href='test-password-verification.php'>Full Diagnostic</a></p>";
?>

