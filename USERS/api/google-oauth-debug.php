<?php
/**
 * Google OAuth Redirect URI Debug Tool
 * 
 * This file helps you identify the exact redirect URI to configure in Google Cloud Console.
 * 
 * Usage:
 * 1. Visit this file in your browser: http://localhost/EMERGENCY-COM/USERS/api/google-oauth-debug.php
 * 2. Copy the redirect URI shown below
 * 3. Add it to Google Cloud Console → Credentials → OAuth 2.0 Client ID → Authorized redirect URIs
 */

// Get the callback URL - using the same logic as google-oauth-init.php
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'];
$scriptPath = $_SERVER['SCRIPT_NAME']; // Full path to current script
$scriptDir = dirname($scriptPath); // Directory containing the script
$callbackPath = rtrim($scriptDir, '/') . '/google-oauth-callback.php';
$redirectUri = $protocol . '://' . $host . $callbackPath;

// Remove any double slashes (except after protocol)
$redirectUri = preg_replace('#([^:])//+#', '$1/', $redirectUri);

// Also get the actual callback file path
$callbackFile = __DIR__ . '/google-oauth-callback.php';
$callbackExists = file_exists($callbackFile);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Google OAuth Redirect URI Debug</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            margin-top: 0;
        }
        .info-box {
            background: #e3f2fd;
            border-left: 4px solid #2196F3;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .success-box {
            background: #e8f5e9;
            border-left: 4px solid #4CAF50;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .error-box {
            background: #ffebee;
            border-left: 4px solid #f44336;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .uri-display {
            background: #f5f5f5;
            padding: 15px;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
            font-size: 14px;
            word-break: break-all;
            margin: 15px 0;
            border: 2px solid #ddd;
        }
        .copy-btn {
            background: #2196F3;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            margin-top: 10px;
        }
        .copy-btn:hover {
            background: #1976D2;
        }
        .steps {
            background: #fff3e0;
            border-left: 4px solid #ff9800;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .steps ol {
            margin: 10px 0;
            padding-left: 20px;
        }
        .steps li {
            margin: 8px 0;
        }
        code {
            background: #f5f5f5;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Google OAuth Redirect URI Debug Tool</h1>
        
        <div class="info-box">
            <strong>Purpose:</strong> This tool shows you the exact redirect URI that needs to be configured in Google Cloud Console.
        </div>

        <h2>Your Redirect URI</h2>
        <div class="uri-display" id="redirectUri"><?php echo htmlspecialchars($redirectUri); ?></div>
        <button class="copy-btn" onclick="copyToClipboard()">📋 Copy to Clipboard</button>

        <h2>Server Information</h2>
        <table style="width: 100%; border-collapse: collapse;">
            <tr style="border-bottom: 1px solid #ddd;">
                <td style="padding: 8px; font-weight: bold;">Protocol:</td>
                <td style="padding: 8px;"><code><?php echo htmlspecialchars($protocol); ?></code></td>
            </tr>
            <tr style="border-bottom: 1px solid #ddd;">
                <td style="padding: 8px; font-weight: bold;">Host:</td>
                <td style="padding: 8px;"><code><?php echo htmlspecialchars($host); ?></code></td>
            </tr>
            <tr style="border-bottom: 1px solid #ddd;">
                <td style="padding: 8px; font-weight: bold;">Script Path:</td>
                <td style="padding: 8px;"><code><?php echo htmlspecialchars($scriptPath); ?></code></td>
            </tr>
            <tr style="border-bottom: 1px solid #ddd;">
                <td style="padding: 8px; font-weight: bold;">Callback File:</td>
                <td style="padding: 8px;">
                    <code><?php echo htmlspecialchars($callbackFile); ?></code>
                    <?php if ($callbackExists): ?>
                        <span style="color: green;">✓ Exists</span>
                    <?php else: ?>
                        <span style="color: red;">✗ Not Found</span>
                    <?php endif; ?>
                </td>
            </tr>
        </table>

        <?php if (!$callbackExists): ?>
            <div class="error-box">
                <strong>⚠️ Warning:</strong> The callback file <code>google-oauth-callback.php</code> was not found. Make sure it exists in the same directory as this file.
            </div>
        <?php endif; ?>

        <div class="steps">
            <h3>📝 How to Fix "redirect_uri_mismatch" Error</h3>
            <ol>
                <li>Copy the redirect URI shown above (click the "Copy to Clipboard" button)</li>
                <li>Go to <a href="https://console.cloud.google.com/apis/credentials" target="_blank">Google Cloud Console → Credentials</a></li>
                <li>Click on your OAuth 2.0 Client ID</li>
                <li>Scroll down to <strong>"Authorized redirect URIs"</strong></li>
                <li>Click <strong>"+ ADD URI"</strong></li>
                <li>Paste the redirect URI you copied (it must match <strong>exactly</strong>)</li>
                <li>Click <strong>"SAVE"</strong></li>
                <li>Wait a few seconds for changes to propagate</li>
                <li>Try logging in with Google again</li>
            </ol>
        </div>

        <div class="info-box">
            <strong>💡 Important Notes:</strong>
            <ul style="margin: 10px 0; padding-left: 20px;">
                <li>The redirect URI must match <strong>exactly</strong> (including http vs https, port numbers, trailing slashes, etc.)</li>
                <li>If you're using <code>localhost</code>, make sure it matches exactly (not <code>127.0.0.1</code>)</li>
                <li>If you're using a port (e.g., <code>:8080</code>), it must be included in the redirect URI</li>
                <li>Changes in Google Cloud Console may take a few seconds to propagate</li>
                <li>You can add multiple redirect URIs if you're testing on different environments</li>
            </ul>
        </div>

        <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd; color: #666; font-size: 12px;">
            <p><strong>Current URL:</strong> <code><?php echo htmlspecialchars($protocol . '://' . $host . $_SERVER['REQUEST_URI']); ?></code></p>
            <p><strong>Callback URL:</strong> <code><?php echo htmlspecialchars($redirectUri); ?></code></p>
        </div>
    </div>

    <script>
        function copyToClipboard() {
            const uri = document.getElementById('redirectUri').textContent;
            navigator.clipboard.writeText(uri).then(function() {
                const btn = event.target;
                const originalText = btn.textContent;
                btn.textContent = '✓ Copied!';
                btn.style.background = '#4CAF50';
                setTimeout(function() {
                    btn.textContent = originalText;
                    btn.style.background = '#2196F3';
                }, 2000);
            }).catch(function(err) {
                alert('Failed to copy. Please copy manually: ' + uri);
            });
        }
    </script>
</body>
</html>


