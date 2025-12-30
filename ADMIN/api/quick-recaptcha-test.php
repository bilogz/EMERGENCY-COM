<?php
/**
 * Quick reCAPTCHA Test
 * Tests if current keys work without needing a token
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: text/html; charset=utf-8');

echo "<h1>Quick reCAPTCHA Key Test</h1>";
echo "<style>body { font-family: monospace; padding: 20px; } .success { color: green; } .error { color: red; } .info { color: blue; } .warning { color: orange; } pre { background: #f5f5f5; padding: 10px; }</style>";

require_once __DIR__ . '/config.env.php';

$siteKey = getSecureConfig('RECAPTCHA_SITE_KEY', '');
$secretKey = getSecureConfig('RECAPTCHA_SECRET_KEY', '');

echo "<h2>Current Configuration</h2>";
echo "<p><strong>Site Key:</strong> <code>" . htmlspecialchars($siteKey) . "</code></p>";
echo "<p><strong>Secret Key:</strong> <code>" . htmlspecialchars(substr($secretKey, 0, 20)) . "...</code></p>";

// Test with an invalid token to see what error we get
echo "<h2>Testing Key Validity</h2>";
echo "<p class='info'>Testing with an invalid token to check if keys are configured correctly...</p>";

$testToken = 'invalid_test_token';
$recaptchaUrl = 'https://www.google.com/recaptcha/api/siteverify';
$recaptchaData = [
    'secret' => $secretKey,
    'response' => $testToken,
    'remoteip' => $_SERVER['REMOTE_ADDR'] ?? ''
];

$recaptchaOptions = [
    'http' => [
        'method' => 'POST',
        'header' => 'Content-Type: application/x-www-form-urlencoded',
        'content' => http_build_query($recaptchaData),
        'timeout' => 10
    ]
];

$recaptchaContext = stream_context_create($recaptchaOptions);
$recaptchaResult = @file_get_contents($recaptchaUrl, false, $recaptchaContext);

if ($recaptchaResult === false) {
    echo "<p class='error'>✗ Cannot reach Google reCAPTCHA API</p>";
    echo "<p class='info'>This might be a network/firewall issue</p>";
} else {
    $recaptchaJson = json_decode($recaptchaResult, true);
    
    echo "<pre>";
    print_r($recaptchaJson);
    echo "</pre>";
    
    $errorCodes = $recaptchaJson['error-codes'] ?? [];
    
    if (in_array('invalid-input-secret', $errorCodes)) {
        echo "<div style='background: #f8d7da; padding: 15px; border-left: 4px solid #dc3545; margin: 20px 0;'>";
        echo "<h3 class='error'>✗ INVALID SECRET KEY</h3>";
        echo "<p><strong>Problem:</strong> The secret key is invalid or doesn't exist.</p>";
        echo "<p><strong>Solution:</strong> You need to regenerate new reCAPTCHA keys.</p>";
        echo "<ol>";
        echo "<li>Go to <a href='https://www.google.com/recaptcha/admin' target='_blank'>Google reCAPTCHA Admin</a></li>";
        echo "<li>Create a new reCAPTCHA v3 site</li>";
        echo "<li>Add your domain: <code>emergency-comm.alertaraqc.com</code></li>";
        echo "<li>Copy the new Site Key and Secret Key</li>";
        echo "<li>Update <code>config.local.php</code> with the new keys</li>";
        echo "</ol>";
        echo "</div>";
    } elseif (in_array('invalid-input-response', $errorCodes)) {
        echo "<p class='success'>✓ Secret key is VALID (we got expected 'invalid-input-response' error for test token)</p>";
        echo "<p class='info'>The keys appear to be configured correctly. The issue might be:</p>";
        echo "<ul>";
        echo "<li>Domain not registered in reCAPTCHA console</li>";
        echo "<li>Token not being sent from frontend</li>";
        echo "<li>Token expired or already used</li>";
        echo "</ul>";
    } else {
        echo "<p class='warning'>⚠️ Unexpected response. Error codes: " . implode(', ', $errorCodes) . "</p>";
    }
}

echo "<hr>";
echo "<h2>Recommendation</h2>";

$isTestKey = ($secretKey === '6LeIxAcTAAAAAGG-vFI1TnRWxMZNFuojJ4WifJWe');
if ($isTestKey) {
    echo "<p class='info'>You're using Google's test key. This should always work.</p>";
} else {
    echo "<div style='background: #d1ecf1; padding: 15px; border-left: 4px solid #0c5460; margin: 20px 0;'>";
    echo "<h3>Should You Regenerate?</h3>";
    echo "<p><strong>YES, regenerate if:</strong></p>";
    echo "<ul>";
    echo "<li>You see 'invalid-input-secret' error above</li>";
    echo "<li>The keys are from a different project</li>";
    echo "<li>You want to ensure domain is properly registered</li>";
    echo "</ul>";
    echo "<p><strong>NO, don't regenerate if:</strong></p>";
    echo "<ul>";
    echo "<li>Keys are valid but domain isn't registered (just add domain in console)</li>";
    echo "<li>Keys work but score is too low (adjust threshold instead)</li>";
    echo "</ul>";
    echo "</div>";
}

echo "<hr>";
echo "<h2>How to Regenerate reCAPTCHA Keys</h2>";
echo "<ol>";
echo "<li>Visit <a href='https://www.google.com/recaptcha/admin' target='_blank'>Google reCAPTCHA Admin Console</a></li>";
echo "<li>Click <strong>+</strong> to create a new site</li>";
echo "<li>Choose <strong>reCAPTCHA v3</strong></li>";
echo "<li>Add your domain: <code>emergency-comm.alertaraqc.com</code></li>";
echo "<li>Also add: <code>alertaraqc.com</code> (for subdomains)</li>";
echo "<li>Accept terms and submit</li>";
echo "<li>Copy the <strong>Site Key</strong> and <strong>Secret Key</strong></li>";
echo "<li>Update <code>config.local.php</code> with new keys</li>";
echo "</ol>";

echo "<hr>";
echo "<p><a href='test-recaptcha-verification.php'>Full Test Page</a> | <a href='../login.php'>Back to Login</a></p>";
?>

