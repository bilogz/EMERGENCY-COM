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

// Try project root .env via config.env.php first
if (file_exists(__DIR__ . '/config.env.php')) {
    require_once __DIR__ . '/config.env.php';
    if (function_exists('getApiConfig')) {
        $apiCfg = getApiConfig();
        if (is_array($apiCfg)) {
            $googleClientId = $apiCfg['google_client_id'] ?? null;
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

// Get the callback URL - construct it reliably
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'];
$scriptPath = $_SERVER['SCRIPT_NAME']; // Full path to current script
$scriptDir = dirname($scriptPath); // Directory containing the script
$callbackPath = rtrim($scriptDir, '/') . '/google-oauth-callback.php';
$redirectUri = $protocol . '://' . $host . $callbackPath;

// Remove any double slashes (except after protocol)
$redirectUri = preg_replace('#([^:])//+#', '$1/', $redirectUri);

// Log the redirect URI for debugging (remove in production)
error_log("Google OAuth Redirect URI: " . $redirectUri);

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

