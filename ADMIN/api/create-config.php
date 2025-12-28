<?php
/**
 * Create Config File Script
 * This script helps create the config.local.php file on your server
 * 
 * SECURITY WARNING: Delete this file after use!
 */

header('Content-Type: text/html; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html><html><head><title>Create Config File</title>";
echo "<style>body{font-family:Arial,sans-serif;max-width:800px;margin:50px auto;padding:20px;background:#1a1a1a;color:#fff;}";
echo ".success{background:#2d5a27;padding:15px;border-radius:5px;margin:10px 0;}";
echo ".error{background:#5a2727;padding:15px;border-radius:5px;margin:10px 0;}";
echo ".info{background:#2a2a3a;padding:15px;border-radius:5px;margin:10px 0;}";
echo "input,textarea{width:100%;padding:10px;margin:5px 0;background:#333;color:#fff;border:1px solid #555;border-radius:5px;}";
echo "button{background:#8e44ad;color:#fff;padding:10px 20px;border:none;border-radius:5px;cursor:pointer;}";
echo "button:hover{background:#9b59b6;}";
echo "h1{color:#8e44ad;}</style></head><body>";
echo "<h1>🔧 Create Gemini API Config File</h1>";

$configPath = __DIR__ . '/../../USERS/api/config.local.php';
$configDir = dirname($configPath);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['api_key'])) {
    $apiKey = trim($_POST['api_key']);
    $model = trim($_POST['model'] ?? 'gemini-1.5-flash');
    
    if (empty($apiKey)) {
        echo "<div class='error'>❌ API Key is required!</div>";
    } else {
        // Create directory if it doesn't exist
        if (!is_dir($configDir)) {
            if (mkdir($configDir, 0755, true)) {
                echo "<div class='success'>✅ Created directory: <code>$configDir</code></div>";
            } else {
                echo "<div class='error'>❌ Failed to create directory: <code>$configDir</code></div>";
            }
        }
        
        // Create config file
        $configContent = "<?php
/**
 * SECURE API CONFIGURATION
 * This file contains your actual API keys
 * DO NOT commit this file to Git!
 */

return [
    'AI_PROVIDER' => 'gemini',
    'AI_API_KEY' => '" . addslashes($apiKey) . "',
    'GEMINI_MODEL' => '" . addslashes($model) . "',
];
";
        
        // Try to create the file
        $fileCreated = false;
        $errorMsg = '';
        
        // First, try to make directory writable temporarily
        $originalPerms = null;
        if (is_dir($configDir)) {
            $originalPerms = substr(sprintf('%o', fileperms($configDir)), -4);
            @chmod($configDir, 0755);
        }
        
        if (file_put_contents($configPath, $configContent)) {
            // Set secure permissions on the file
            @chmod($configPath, 0600);
            $fileCreated = true;
        } else {
            $errorMsg = error_get_last()['message'] ?? 'Unknown error';
        }
        
        // Restore original permissions if we changed them
        if ($originalPerms !== null) {
            @chmod($configDir, octdec($originalPerms));
        }
        
        if ($fileCreated) {
            echo "<div class='success'>";
            echo "✅ <strong>Config file created successfully!</strong><br><br>";
            echo "Location: <code>$configPath</code><br>";
            echo "API Key: <code>" . substr($apiKey, 0, 20) . "...</code><br>";
            echo "Model: <code>$model</code><br><br>";
            echo "<a href='test-gemini-simple.php' style='color:#8e44ad;'>→ Test the configuration now</a><br><br>";
            echo "<strong style='color:#e74c3c;'>⚠️ IMPORTANT: Delete this create-config.php file for security!</strong>";
            echo "</div>";
        } else {
            echo "<div class='error'>";
            echo "❌ <strong>Failed to create config file!</strong><br><br>";
            echo "Error: <code>$errorMsg</code><br><br>";
            echo "<strong>Solution Options:</strong><br><br>";
            echo "<strong>Option 1: Fix permissions via SSH</strong><br>";
            echo "Run these commands on your server:<br>";
            echo "<pre style='background:#000;padding:10px;border-radius:5px;'>";
            echo "cd /var/www/html/emergency_communication_alertaraqc\n";
            echo "sudo chmod 755 USERS/api\n";
            echo "sudo chown www-data:www-data USERS/api\n";
            echo "</pre><br>";
            echo "<strong>Option 2: Create file manually</strong><br>";
            echo "1. Use FTP/cPanel File Manager<br>";
            echo "2. Navigate to: <code>USERS/api/</code><br>";
            echo "3. Create file: <code>config.local.php</code><br>";
            echo "4. Paste this content:<br>";
            echo "<pre style='background:#000;padding:10px;border-radius:5px;max-height:200px;overflow:auto;'>";
            echo htmlspecialchars($configContent);
            echo "</pre><br>";
            echo "<strong>Option 3: Copy from local</strong><br>";
            echo "If you have the file locally, upload it via FTP to:<br>";
            echo "<code>$configPath</code><br>";
            echo "</div>";
        }
    }
}

// Check if config already exists
if (file_exists($configPath)) {
    echo "<div class='info'>";
    echo "⚠️ Config file already exists at: <code>$configPath</code><br>";
    echo "If you want to update it, you can edit it directly or delete it first.<br>";
    echo "</div>";
}

// Show form
echo "<div class='info'>";
echo "<h2>Create Configuration File</h2>";
echo "<form method='POST'>";
echo "<label>Gemini API Key:</label>";
echo "<input type='text' name='api_key' placeholder='AIzaSy...' required>";
echo "<br><br>";
echo "<label>Model Name (default: gemini-1.5-flash):</label>";
echo "<input type='text' name='model' value='gemini-1.5-flash' placeholder='gemini-1.5-flash'>";
echo "<small style='color:#999;'>Common models: gemini-1.5-flash, gemini-1.5-pro, gemini-pro</small>";
echo "<br><br>";
echo "<button type='submit'>Create Config File</button>";
echo "</form>";
echo "</div>";

// Show current path info
echo "<div class='info'>";
echo "<h2>Path Information</h2>";
echo "Config file will be created at:<br>";
echo "<code>$configPath</code><br><br>";
echo "Directory exists: " . (is_dir($configDir) ? "✅ Yes" : "❌ No") . "<br>";
echo "Directory writable: " . (is_writable($configDir) ? "✅ Yes" : "❌ No") . "<br>";
if (file_exists($configPath)) {
    echo "File exists: ✅ Yes<br>";
    echo "File readable: " . (is_readable($configPath) ? "✅ Yes" : "❌ No") . "<br>";
}
echo "</div>";

echo "</body></html>";
?>

