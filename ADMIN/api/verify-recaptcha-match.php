<?php
/**
 * Verify reCAPTCHA Keys Match
 * Checks if config keys match what's in Google console
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: text/html; charset=utf-8');

echo "<h1>Verify reCAPTCHA Key Match</h1>";
echo "<style>body { font-family: monospace; padding: 20px; } .success { color: green; } .error { color: red; } .info { color: blue; } .warning { color: orange; } pre { background: #f5f5f5; padding: 10px; } code { background: #f0f0f0; padding: 2px 6px; border-radius: 3px; }</style>";

require_once __DIR__ . '/config.env.php';

$configSiteKey = getSecureConfig('RECAPTCHA_SITE_KEY', '');
$configSecretKey = getSecureConfig('RECAPTCHA_SECRET_KEY', '');

echo "<h2>Keys in Your Config File</h2>";
echo "<p><strong>Site Key:</strong> <code>" . htmlspecialchars($configSiteKey) . "</code></p>";
echo "<p><strong>Secret Key:</strong> <code>" . htmlspecialchars($configSecretKey) . "</code></p>";

echo "<h2>Keys in Google Console (from your screenshot)</h2>";
echo "<p><strong>Secret Key shown:</strong> <code>6Le5bTosAAAAAHRmnr9W9TblfjPAMVgZ1HRF3osg</code></p>";

echo "<h2>Comparison</h2>";

$googleSecretKey = '6Le5bTosAAAAAHRmnr9W9TblfjPAMVgZ1HRF3osg';

if ($configSecretKey === $googleSecretKey) {
    echo "<p class='success'>✓ Secret keys MATCH!</p>";
} else {
    echo "<div style='background: #f8d7da; padding: 15px; border-left: 4px solid #dc3545; margin: 20px 0;'>";
    echo "<h3 class='error'>✗ Secret Keys DO NOT MATCH!</h3>";
    echo "<p><strong>Config file has:</strong> <code>" . htmlspecialchars($configSecretKey) . "</code></p>";
    echo "<p><strong>Google console shows:</strong> <code>" . htmlspecialchars($googleSecretKey) . "</code></p>";
    echo "<p><strong>Difference:</strong> The keys are different, which will cause verification to fail.</p>";
    echo "</div>";
    
    echo "<h3>Solution: Update Config File</h3>";
    echo "<p>You need to update <code>config.local.php</code> with the correct secret key from Google console.</p>";
    echo "<p>The correct secret key is: <code>" . htmlspecialchars($googleSecretKey) . "</code></p>";
    
    // Check if they want to update
    if (isset($_GET['update']) && $_GET['update'] === 'yes') {
        $configFile = __DIR__ . '/config.local.php';
        $content = file_get_contents($configFile);
        
        // Backup
        $backupFile = $configFile . '.backup.' . date('Y-m-d_H-i-s');
        copy($configFile, $backupFile);
        echo "<p class='success'>✓ Backup created: " . basename($backupFile) . "</p>";
        
        // Update secret key
        $oldPattern = "/'RECAPTCHA_SECRET_KEY'\s*=>\s*'[^']*'/";
        $newReplacement = "'RECAPTCHA_SECRET_KEY' => '" . addslashes($googleSecretKey) . "'";
        $content = preg_replace($oldPattern, $newReplacement, $content);
        
        if (file_put_contents($configFile, $content)) {
            echo "<p class='success'>✓ Config file updated successfully!</p>";
            echo "<p>Please try logging in again.</p>";
        } else {
            echo "<p class='error'>✗ Failed to update config file (permission denied?)</p>";
            echo "<p>Please update manually:</p>";
            echo "<pre>";
            echo "Change:\n";
            echo "'RECAPTCHA_SECRET_KEY' => '" . htmlspecialchars($configSecretKey) . "'\n";
            echo "To:\n";
            echo "'RECAPTCHA_SECRET_KEY' => '" . htmlspecialchars($googleSecretKey) . "'\n";
            echo "</pre>";
        }
    } else {
        echo "<p><a href='?update=yes' style='background: #dc3545; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Update Config File Now</a></p>";
    }
}

// Also check site key
echo "<h2>Site Key Check</h2>";
echo "<p>Config has: <code>" . htmlspecialchars($configSiteKey) . "</code></p>";
echo "<p class='info'>ℹ️ Make sure the Site Key in your config matches the one shown in Google console (usually visible on the same page as the secret key).</p>";

echo "<hr>";
echo "<h2>Next Steps</h2>";
if ($configSecretKey !== $googleSecretKey) {
    echo "<ol>";
    echo "<li>Update the secret key in config.local.php (use the button above or update manually)</li>";
    echo "<li>Verify the site key also matches</li>";
    echo "<li>Wait 1-2 minutes for changes to take effect</li>";
    echo "<li>Try logging in again</li>";
    echo "</ol>";
} else {
    echo "<p class='success'>✓ Keys match! If login still fails, check:</p>";
    echo "<ul>";
    echo "<li>Browser console for JavaScript errors</li>";
    echo "<li>Network tab to see if reCAPTCHA token is being sent</li>";
    echo "<li>Server error logs for detailed reCAPTCHA errors</li>";
    echo "</ul>";
}

echo "<hr>";
echo "<p><a href='../login.php?reset=attempts'>Go to Login</a> | <a href='test-recaptcha-verification.php'>Full Test</a></p>";
?>

