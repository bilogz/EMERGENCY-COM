<?php
/**
 * Google OAuth Initialization
 * 
 * This file initiates the OAuth flow by redirecting the user to Google's authorization page.
 * 
 * Usage:
 * Redirect user to: USERS/api/google-oauth-init.php
 * 
 * The user will be redirected to Google, then back to google-oauth-callback.php
 */

session_start();

// Load Google OAuth Client ID
$googleClientId = null;

// Try .env file first
$envFile = __DIR__ . '/.env';
if (file_exists($envFile)) {
    $envContent = file_get_contents($envFile);
    $lines = explode("\n", $envContent);
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line) || strpos($line, '#') === 0) {
            continue;
        }
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            $value = trim($value, '"\'');
            if ($key === 'GOOGLE_CLIENT_ID') {
                $googleClientId = $value;
                break;
            }
        }
    }
}

// If not found in .env, try config.local.php
if (empty($googleClientId)) {
    $configFile = __DIR__ . '/config.local.php';
    if (file_exists($configFile)) {
        $config = require $configFile;
        if (is_array($config)) {
            $googleClientId = $config['GOOGLE_CLIENT_ID'] ?? null;
        }
    }
}

// Check if Client ID is configured
if (!$googleClientId) {
    $_SESSION['oauth_error'] = 'Google OAuth is not configured. Please contact administrator.';
    header('Location: ../login.php?error=oauth_not_configured');
    exit();
}

// Get the callback URL
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$redirectUri = $protocol . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . '/google-oauth-callback.php';

// Generate state parameter for CSRF protection
$state = bin2hex(random_bytes(16));
$_SESSION['oauth_state'] = $state;

// Build Google OAuth authorization URL
$scope = 'email profile';
$authUrl = 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query([
    'client_id' => $googleClientId,
    'redirect_uri' => $redirectUri,
    'response_type' => 'code',
    'scope' => $scope,
    'state' => $state,
    'access_type' => 'online',
    'prompt' => 'consent' // Force consent screen to ensure we get refresh token if needed
]);

// Redirect to Google authorization page
header('Location: ' . $authUrl);
exit();
?>

