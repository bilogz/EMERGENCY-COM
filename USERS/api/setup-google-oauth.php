<!DOCTYPE html>
<html>
<head>
    <title>Setup Google OAuth</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; background: #f5f5f5; }
        .container { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #333; }
        .form-group { margin: 20px 0; }
        label { display: block; margin-bottom: 5px; font-weight: bold; color: #555; }
        input { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; font-size: 14px; }
        button { background: #4285f4; color: white; padding: 12px 24px; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; }
        button:hover { background: #357ae8; }
        .info { background: #e3f2fd; padding: 15px; border-radius: 5px; margin: 20px 0; }
        .success { background: #e8f5e9; padding: 15px; border-radius: 5px; margin: 20px 0; color: #2e7d32; }
        .error { background: #ffebee; padding: 15px; border-radius: 5px; margin: 20px 0; color: #c62828; }
        .step { margin: 20px 0; padding: 15px; background: #f9f9f9; border-left: 4px solid #4285f4; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Setup Google OAuth</h1>
        
        <div class="info">
            <strong>Instructions:</strong>
            <ol>
                <li>Go to <a href="https://console.cloud.google.com/" target="_blank">Google Cloud Console</a></li>
                <li>Create a project or select an existing one</li>
                <li>Enable Google+ API</li>
                <li>Go to "Credentials" → "Create Credentials" → "OAuth client ID"</li>
                <li>Select "Web application"</li>
                <li>Add authorized JavaScript origins: <code>http://localhost</code> (and your domain)</li>
                <li>Add authorized redirect URIs: <code>http://localhost/USERS/api/google-oauth.php</code></li>
                <li>Copy your Client ID and Client Secret below</li>
            </ol>
        </div>

        <form method="POST" action="">
            <div class="form-group">
                <label for="client_id">Google Client ID:</label>
                <input type="text" id="client_id" name="client_id" placeholder="xxxxx.apps.googleusercontent.com" required>
            </div>
            
            <div class="form-group">
                <label for="client_secret">Google Client Secret:</label>
                <input type="text" id="client_secret" name="client_secret" placeholder="GOCSPX-xxxxx" required>
            </div>
            
            <div class="form-group">
                <label>Storage Method:</label>
                <select name="storage_method" id="storage_method">
                    <option value="env">.env file (Recommended)</option>
                    <option value="config">config.local.php</option>
                </select>
            </div>
            
            <button type="submit">Save Configuration</button>
        </form>

        <div id="result"></div>
    </div>

    <?php
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $clientId = trim($_POST['client_id'] ?? '');
        $clientSecret = trim($_POST['client_secret'] ?? '');
        $storageMethod = $_POST['storage_method'] ?? 'env';
        
        if (empty($clientId) || empty($clientSecret)) {
            echo '<div class="error">Please fill in both Client ID and Client Secret.</div>';
        } else {
            $success = false;
            $message = '';
            
            if ($storageMethod === 'env') {
                // Save to .env file
                $envFile = __DIR__ . '/.env';
                $lines = [];
                
                if (file_exists($envFile)) {
                    $existing = file_get_contents($envFile);
                    $lines = explode("\n", $existing);
                    $updated = false;
                    
                    foreach ($lines as &$line) {
                        if (strpos(trim($line), 'GOOGLE_CLIENT_ID=') === 0) {
                            $line = "GOOGLE_CLIENT_ID={$clientId}";
                            $updated = true;
                        } elseif (strpos(trim($line), 'GOOGLE_CLIENT_SECRET=') === 0) {
                            $line = "GOOGLE_CLIENT_SECRET={$clientSecret}";
                            $updated = true;
                        }
                    }
                    
                    if (!$updated) {
                        $lines[] = "GOOGLE_CLIENT_ID={$clientId}";
                        $lines[] = "GOOGLE_CLIENT_SECRET={$clientSecret}";
                    }
                } else {
                    $lines = [
                        "GOOGLE_CLIENT_ID={$clientId}",
                        "GOOGLE_CLIENT_SECRET={$clientSecret}"
                    ];
                }
                
                if (file_put_contents($envFile, implode("\n", $lines))) {
                    $success = true;
                    $message = "Configuration saved to .env file successfully!";
                } else {
                    $message = "Failed to write .env file. Check file permissions.";
                }
            } else {
                // Save to config.local.php
                $configFile = __DIR__ . '/config.local.php';
                $config = [];
                
                if (file_exists($configFile)) {
                    $config = require $configFile;
                }
                
                $config['GOOGLE_CLIENT_ID'] = $clientId;
                $config['GOOGLE_CLIENT_SECRET'] = $clientSecret;
                
                $phpContent = "<?php\n/**\n * Google OAuth Configuration\n * Generated automatically - DO NOT EDIT MANUALLY\n */\n\nreturn " . var_export($config, true) . ";\n";
                
                if (file_put_contents($configFile, $phpContent)) {
                    $success = true;
                    $message = "Configuration saved to config.local.php successfully!";
                } else {
                    $message = "Failed to write config.local.php. Check file permissions.";
                }
            }
            
            if ($success) {
                echo '<div class="success"><strong>✓ Success!</strong><br>' . $message . '<br><br>';
                echo '<a href="test-google-oauth.php">Test Configuration</a> | ';
                echo '<a href="../signup.php">Go to Signup Page</a></div>';
            } else {
                echo '<div class="error"><strong>✗ Error:</strong><br>' . $message . '</div>';
            }
        }
    }
    ?>
</body>
</html>

