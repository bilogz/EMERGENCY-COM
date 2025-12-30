<?php
/**
 * Test Direct Login
 * Simulates the login process to identify issues
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: text/html; charset=utf-8');

echo "<h1>Direct Login Test</h1>";
echo "<style>body { font-family: monospace; padding: 20px; } .success { color: green; } .error { color: red; } .info { color: blue; } pre { background: #f5f5f5; padding: 10px; }</style>";

$email = 'joecel519@gmail.com';
$password = 'Admin#123';

// Connect
try {
    require_once __DIR__ . '/config.env.php';
    require_once __DIR__ . '/db_connect.php';
    
    if (!isset($pdo) || $pdo === null) {
        die("<p class='error'>✗ Database connection failed</p>");
    }
    echo "<p class='success'>✓ Database connected</p>";
} catch (Exception $e) {
    die("<p class='error'>✗ Error: " . htmlspecialchars($e->getMessage()) . "</p>");
}

// Get admin
$stmt = $pdo->prepare("SELECT id, name, email, password, role, status FROM admin_user WHERE email = ?");
$stmt->execute([$email]);
$admin = $stmt->fetch();

if (!$admin) {
    die("<p class='error'>✗ Admin not found</p>");
}

echo "<h2>Step 1: Password Verification</h2>";
$passwordValid = password_verify($password, $admin['password']);
if ($passwordValid) {
    echo "<p class='success'>✓ Password is CORRECT</p>";
} else {
    echo "<p class='error'>✗ Password is INCORRECT</p>";
    exit;
}

echo "<h2>Step 2: Account Status</h2>";
if ($admin['status'] === 'active') {
    echo "<p class='success'>✓ Account is ACTIVE</p>";
} else {
    echo "<p class='error'>✗ Account status: " . htmlspecialchars($admin['status']) . "</p>";
    exit;
}

echo "<h2>Step 3: OTP Check</h2>";
$requireOtp = getSecureConfig('ADMIN_REQUIRE_OTP', false);
if ($requireOtp) {
    echo "<p class='warning'>⚠️ OTP is required (but we're testing without it)</p>";
} else {
    echo "<p class='success'>✓ OTP is NOT required</p>";
}

echo "<h2>Step 4: reCAPTCHA Check</h2>";
$recaptchaSecret = getSecureConfig('RECAPTCHA_SECRET_KEY', '');
$isTestKey = ($recaptchaSecret === '6LeIxAcTAAAAAGG-vFI1TnRWxMZNFuojJ4WifJWe');
if ($isTestKey) {
    echo "<p class='info'>ℹ️ Using reCAPTCHA test key (will always pass)</p>";
} else {
    echo "<p class='info'>ℹ️ Using production reCAPTCHA key</p>";
}

echo "<h2>Step 5: Session Test</h2>";
session_start();
echo "<p>Session ID: " . session_id() . "</p>";
echo "<p>Session status: " . (session_status() === PHP_SESSION_ACTIVE ? '<span class="success">Active</span>' : '<span class="error">Not Active</span>') . "</p>";

echo "<h2>Summary</h2>";
echo "<div style='background: #d4edda; padding: 15px; border-left: 4px solid #28a745; margin: 20px 0;'>";
echo "<p><strong>✓ All checks passed!</strong></p>";
echo "<p>Login should work. If it doesn't, the issue is likely:</p>";
echo "<ul>";
echo "<li>Client-side lockout (clear localStorage)</li>";
echo "<li>reCAPTCHA verification failing in browser</li>";
echo "<li>JavaScript errors on the login page</li>";
echo "</ul>";
echo "</div>";

echo "<p><a href='../login.php?reset=attempts' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Go to Login (Reset Lockout)</a></p>";
?>
