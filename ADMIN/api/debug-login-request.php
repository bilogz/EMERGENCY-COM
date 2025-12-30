<?php
/**
 * Debug Login Request
 * Shows what's happening during login attempts
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Enable logging
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/login-debug.log');

header('Content-Type: text/html; charset=utf-8');

echo "<h1>Login Request Debug</h1>";
echo "<style>body { font-family: monospace; padding: 20px; } .success { color: green; } .error { color: red; } .info { color: blue; } .warning { color: orange; } pre { background: #f5f5f5; padding: 10px; overflow-x: auto; }</style>";

echo "<h2>1. Request Method</h2>";
echo "<p>Method: <strong>" . $_SERVER['REQUEST_METHOD'] . "</strong></p>";

echo "<h2>2. POST Data</h2>";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postData = $_POST;
    echo "<pre>";
    print_r($postData);
    echo "</pre>";
} else {
    echo "<p class='info'>This page expects POST data. Use the login form to send data here.</p>";
    echo "<p>To test, you can simulate a login request from the browser console:</p>";
    echo "<pre>";
    echo "fetch('login-web.php', {\n";
    echo "    method: 'POST',\n";
    echo "    headers: { 'Content-Type': 'application/json' },\n";
    echo "    body: JSON.stringify({\n";
    echo "        email: 'joecel519@gmail.com',\n";
    echo "        password: 'Admin#123',\n";
    echo "        recaptcha_response: 'test'\n";
    echo "    })\n";
    echo "}).then(r => r.json()).then(console.log);\n";
    echo "</pre>";
}

echo "<h2>3. Session Data</h2>";
session_start();
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

echo "<h2>4. reCAPTCHA Configuration</h2>";
try {
    require_once __DIR__ . '/config.env.php';
    $recaptchaSecret = getSecureConfig('RECAPTCHA_SECRET_KEY', '');
    $recaptchaSite = getSecureConfig('RECAPTCHA_SITE_KEY', '');
    
    echo "<p>Site Key: <code>" . htmlspecialchars(substr($recaptchaSite, 0, 20)) . "...</code></p>";
    echo "<p>Secret Key: <code>" . htmlspecialchars(substr($recaptchaSecret, 0, 20)) . "...</code></p>";
    
    $isTestKey = ($recaptchaSecret === '6LeIxAcTAAAAAGG-vFI1TnRWxMZNFuojJ4WifJWe');
    if ($isTestKey) {
        echo "<p class='info'>ℹ️ Using reCAPTCHA test key</p>";
    } else {
        echo "<p class='warning'>⚠️ Using production reCAPTCHA key</p>";
        echo "<p class='info'>If reCAPTCHA verification fails, login will fail even with correct password.</p>";
    }
} catch (Exception $e) {
    echo "<p class='error'>Error loading config: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "<h2>5. Recent Error Log</h2>";
$logFile = __DIR__ . '/login-debug.log';
if (file_exists($logFile)) {
    $lines = file($logFile);
    $recent = array_slice($lines, -20);
    if (!empty($recent)) {
        echo "<pre>";
        echo htmlspecialchars(implode('', $recent));
        echo "</pre>";
    } else {
        echo "<p class='info'>No recent log entries</p>";
    }
} else {
    echo "<p class='info'>Log file not created yet</p>";
}

echo "<h2>6. Browser Console Check</h2>";
echo "<div style='background: #fff3cd; padding: 15px; border-left: 4px solid #ffc107;'>";
echo "<h3>How to Debug Login Issues:</h3>";
echo "<ol>";
echo "<li>Open browser Developer Tools (F12)</li>";
echo "<li>Go to <strong>Network</strong> tab</li>";
echo "<li>Try to log in</li>";
echo "<li>Look for the request to <code>login-web.php</code></li>";
echo "<li>Click on it and check:</li>";
echo "   <ul>";
echo "   <li><strong>Request Payload</strong> - What data was sent</li>";
echo "   <li><strong>Response</strong> - What error message was returned</li>";
echo "   </ul>";
echo "<li>Go to <strong>Console</strong> tab and check for JavaScript errors</li>";
echo "</ol>";
echo "</div>";

echo "<hr>";
echo "<p><a href='../login.php?reset=attempts'>Go to Login (Reset Lockout)</a></p>";
echo "<p><a href='test-direct-login.php'>Back to Direct Login Test</a></p>";
?>
