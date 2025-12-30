<?php
/**
 * Test reCAPTCHA Verification
 * Diagnoses reCAPTCHA configuration issues
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: text/html; charset=utf-8');

echo "<h1>reCAPTCHA Verification Test</h1>";
echo "<style>body { font-family: monospace; padding: 20px; } .success { color: green; } .error { color: red; } .info { color: blue; } .warning { color: orange; } pre { background: #f5f5f5; padding: 10px; }</style>";

require_once __DIR__ . '/config.env.php';

$siteKey = getSecureConfig('RECAPTCHA_SITE_KEY', '');
$secretKey = getSecureConfig('RECAPTCHA_SECRET_KEY', '');

echo "<h2>1. reCAPTCHA Configuration</h2>";
echo "<p>Site Key: <code>" . htmlspecialchars(substr($siteKey, 0, 30)) . "...</code></p>";
echo "<p>Secret Key: <code>" . htmlspecialchars(substr($secretKey, 0, 30)) . "...</code></p>";

$isTestKey = ($secretKey === '6LeIxAcTAAAAAGG-vFI1TnRWxMZNFuojJ4WifJWe');
if ($isTestKey) {
    echo "<p class='info'>ℹ️ Using Google test key (will always pass)</p>";
} else {
    echo "<p class='warning'>⚠️ Using production reCAPTCHA key</p>";
}

if (empty($siteKey) || empty($secretKey)) {
    echo "<p class='error'>✗ reCAPTCHA keys are missing!</p>";
    exit;
}

echo "<h2>2. Test reCAPTCHA Verification</h2>";
echo "<p class='info'>To test, you need a valid reCAPTCHA token from the frontend.</p>";
echo "<p>You can get one by:</p>";
echo "<ol>";
echo "<li>Opening the login page</li>";
echo "<li>Opening browser console (F12)</li>";
echo "<li>Running: <code>grecaptcha.execute('{$siteKey}', {action: 'admin_login'}).then(console.log)</code></li>";
echo "<li>Copy the token and paste it below</li>";
echo "</ol>";

if (isset($_POST['test_token'])) {
    $testToken = $_POST['test_token'];
    
    echo "<h3>Testing Token</h3>";
    echo "<p>Token: <code>" . htmlspecialchars(substr($testToken, 0, 30)) . "...</code></p>";
    
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
        echo "<p class='error'>✗ Failed to reach Google reCAPTCHA API</p>";
        echo "<p class='info'>This could be a network issue or firewall blocking the request.</p>";
    } else {
        $recaptchaJson = json_decode($recaptchaResult, true);
        
        echo "<h3>Verification Result</h3>";
        echo "<pre>";
        print_r($recaptchaJson);
        echo "</pre>";
        
        if (isset($recaptchaJson['success']) && $recaptchaJson['success']) {
            $score = $recaptchaJson['score'] ?? 0;
            $action = $recaptchaJson['action'] ?? '';
            
            echo "<p class='success'>✓ reCAPTCHA verification SUCCESSFUL!</p>";
            echo "<p>Score: <strong>{$score}</strong> (1.0 = human, 0.0 = bot)</p>";
            echo "<p>Action: <strong>{$action}</strong></p>";
            
            $minScore = 0.1;
            if ($score >= $minScore) {
                echo "<p class='success'>✓ Score is above minimum threshold ({$minScore})</p>";
            } else {
                echo "<p class='error'>✗ Score is below minimum threshold ({$minScore})</p>";
            }
        } else {
            $errorCodes = $recaptchaJson['error-codes'] ?? [];
            echo "<p class='error'>✗ reCAPTCHA verification FAILED</p>";
            echo "<p>Error codes: " . implode(', ', $errorCodes) . "</p>";
            
            if (in_array('invalid-input-secret', $errorCodes)) {
                echo "<p class='error'><strong>Problem:</strong> Invalid secret key. The secret key doesn't match the site key or is incorrect.</p>";
            } elseif (in_array('invalid-input-response', $errorCodes)) {
                echo "<p class='error'><strong>Problem:</strong> Invalid token. The token may be expired or invalid.</p>";
            } elseif (in_array('timeout-or-duplicate', $errorCodes)) {
                echo "<p class='error'><strong>Problem:</strong> Token expired or already used.</p>";
            }
        }
    }
}

echo "<hr>";
echo "<h2>3. Test Token Form</h2>";
echo "<form method='POST'>";
echo "<p><label>Paste reCAPTCHA token here:</label></p>";
echo "<p><textarea name='test_token' rows='3' cols='80' placeholder='Paste token from browser console'></textarea></p>";
echo "<p><button type='submit' style='background: #28a745; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;'>Test Verification</button></p>";
echo "</form>";

echo "<hr>";
echo "<h2>4. Common Issues & Solutions</h2>";
echo "<div style='background: #fff3cd; padding: 15px; border-left: 4px solid #ffc107;'>";
echo "<h3>Issue: Invalid secret key</h3>";
echo "<p><strong>Solution:</strong> Make sure the secret key in config.local.php matches the one in Google reCAPTCHA console.</p>";
echo "<p>Check: <a href='https://www.google.com/recaptcha/admin' target='_blank'>Google reCAPTCHA Admin</a></p>";
echo "</div>";

echo "<div style='background: #fff3cd; padding: 15px; border-left: 4px solid #ffc107; margin-top: 10px;'>";
echo "<h3>Issue: Domain mismatch</h3>";
echo "<p><strong>Solution:</strong> Make sure your domain (emergency-comm.alertaraqc.com) is registered in Google reCAPTCHA console.</p>";
echo "</div>";

echo "<div style='background: #fff3cd; padding: 15px; border-left: 4px solid #ffc107; margin-top: 10px;'>";
echo "<h3>Issue: Score too low</h3>";
echo "<p><strong>Solution:</strong> The score threshold is currently set to 0.1 (very low). If it's still failing, there may be an issue with the reCAPTCHA keys.</p>";
echo "</div>";

echo "<hr>";
echo "<p><a href='../login.php?reset=attempts'>Go to Login</a></p>";
?>

