<?php
/**
 * Add Google OAuth Credentials to Config File
 * SECURITY: Delete this file after use!
 */

header('Content-Type: text/html; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html><html><head><title>Add Google OAuth</title>";
echo "<style>body{font-family:Arial,sans-serif;max-width:800px;margin:50px auto;padding:20px;background:#1a1a1a;color:#fff;}";
echo ".success{background:#2d5a27;padding:15px;border-radius:5px;margin:10px 0;}";
echo ".error{background:#5a2727;padding:15px;border-radius:5px;margin:10px 0;}";
echo ".info{background:#2a2a3a;padding:15px;border-radius:5px;margin:10px 0;}";
echo "input{width:100%;padding:10px;margin:5px 0;background:#333;color:#fff;border:1px solid #555;border-radius:5px;}";
echo "button{background:#4285f4;color:#fff;padding:10px 20px;border:none;border-radius:5px;cursor:pointer;margin:5px;}";
echo "button:hover{background:#357ae8;}";
echo "h1{color:#4285f4;}</style></head><body>";
echo "<h1>🔐 Add Google OAuth Credentials</h1>";

$configPath = __DIR__ . '/config.local.php';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['client_id']) && isset($_POST['client_secret'])) {
    $clientId = trim($_POST['client_id']);
    $clientSecret = trim($_POST['client_secret']);
    
    if (empty($clientId) || empty($clientSecret)) {
        echo "<div class='error'>❌ Both Client ID and Client Secret are required!</div>";
    } else {
        // Load existing config
        $config = [];
        if (file_exists($configPath)) {
            $config = include $configPath;
            if (!is_array($config)) {
                $config = [];
            }
        }
        
        // Add/Update Google OAuth credentials
        $config['GOOGLE_CLIENT_ID'] = $clientId;
        $config['GOOGLE_CLIENT_SECRET'] = $clientSecret;
        
        // Build config file content
        $configContent = "<?php
/**
 * SECURE API CONFIGURATION
 * This file contains your actual API keys
 * DO NOT commit this file to Git!
 */

return [
";
        
        // Add all config values
        foreach ($config as $key => $value) {
            $configContent .= "    '" . addslashes($key) . "' => '" . addslashes($value) . "',\n";
        }
        
        $configContent .= "];
";
        
        // Write config file
        if (file_put_contents($configPath, $configContent)) {
            @chmod($configPath, 0600);
            echo "<div class='success'>";
            echo "✅ <strong>Google OAuth credentials added successfully!</strong><br><br>";
            echo "Client ID: <code>" . substr($clientId, 0, 30) . "...</code><br>";
            echo "Config file updated: <code>$configPath</code><br><br>";
            echo "<a href='test-config.php' style='color:#4285f4;'>→ Test the configuration</a><br>";
            echo "<a href='get-google-config.php' style='color:#4285f4;'>→ Check API endpoint</a><br><br>";
            echo "<strong style='color:#e74c3c;'>⚠️ IMPORTANT: Delete this add-google-oauth.php file for security!</strong>";
            echo "</div>";
        } else {
            echo "<div class='error'>";
            echo "❌ <strong>Failed to write config file!</strong><br>";
            echo "Error: " . (error_get_last()['message'] ?? 'Unknown error') . "<br>";
            echo "Please check file permissions for: <code>$configPath</code>";
            echo "</div>";
        }
    }
} else {
    // Show form
    $existingConfig = [];
    if (file_exists($configPath)) {
        $existingConfig = include $configPath;
        if (!is_array($existingConfig)) {
            $existingConfig = [];
        }
    }
    
    $hasGoogleId = isset($existingConfig['GOOGLE_CLIENT_ID']);
    $hasGoogleSecret = isset($existingConfig['GOOGLE_CLIENT_SECRET']);
    
    if ($hasGoogleId && $hasGoogleSecret) {
        echo "<div class='info'>";
        echo "ℹ️ Google OAuth credentials already exist in config file.<br>";
        echo "Client ID: <code>" . substr($existingConfig['GOOGLE_CLIENT_ID'], 0, 30) . "...</code><br><br>";
        echo "You can update them using the form below.";
        echo "</div>";
    }
    
    echo "<form method='POST'>";
    echo "<h2>Google OAuth Credentials</h2>";
    echo "<label>Google Client ID:</label>";
    echo "<input type='text' name='client_id' value='" . htmlspecialchars($existingConfig['GOOGLE_CLIENT_ID'] ?? '') . "' placeholder='1054819730704-xxxxx.apps.googleusercontent.com' required><br>";
    echo "<label>Google Client Secret:</label>";
    echo "<input type='text' name='client_secret' value='" . htmlspecialchars($existingConfig['GOOGLE_CLIENT_SECRET'] ?? '') . "' placeholder='GOCSPX-xxxxx' required><br>";
    echo "<button type='submit'>Add/Update Credentials</button>";
    echo "</form>";
    
    echo "<div class='info' style='margin-top:20px;'>";
    echo "<strong>Instructions:</strong><br>";
    echo "1. Enter your Google OAuth Client ID and Secret<br>";
    echo "2. Click 'Add/Update Credentials'<br>";
    echo "3. Test the configuration<br>";
    echo "4. <strong>Delete this file</strong> for security";
    echo "</div>";
}

echo "</body></html>";
?>

