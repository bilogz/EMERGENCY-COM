<?php
/**
 * Facebook OAuth Callback Handler
 * Handles Facebook login/signup callback and user authentication
 */

session_start();

// Load environment variables
$envFile = __DIR__ . '/../../.env';
$env = [];
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            list($key, $value) = explode('=', $line, 2);
            $env[trim($key)] = trim($value);
        }
    }
}

$appId = $env['APP_ID'] ?? '';
$appSecret = $env['APP_SECRET'] ?? '';

// Facebook OAuth endpoints
$redirectUri = 'http://' . $_SERVER['HTTP_HOST'] . '/EMERGENCY-COM/USERS/api/facebook-callback.php';

// Handle OAuth callback
if (isset($_GET['code'])) {
    $code = $_GET['code'];
    
    // Exchange code for access token
    $tokenUrl = 'https://graph.facebook.com/v18.0/oauth/access_token';
    $tokenParams = [
        'client_id' => $appId,
        'client_secret' => $appSecret,
        'redirect_uri' => $redirectUri,
        'code' => $code
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $tokenUrl . '?' . http_build_query($tokenParams));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $tokenResponse = curl_exec($ch);
    curl_close($ch);
    
    $tokenData = json_decode($tokenResponse, true);
    
    if (!isset($tokenData['access_token'])) {
        error_log('Facebook token error: ' . $tokenResponse);
        header('Location: ../login.php?error=facebook_auth_failed');
        exit;
    }
    
    $accessToken = $tokenData['access_token'];
    
    // Get user info from Facebook
    $userInfoUrl = 'https://graph.facebook.com/v18.0/me';
    $userInfoParams = [
        'access_token' => $accessToken,
        'fields' => 'id,name,email,picture'
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $userInfoUrl . '?' . http_build_query($userInfoParams));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $userResponse = curl_exec($ch);
    curl_close($ch);
    
    $userData = json_decode($userResponse, true);
    
    if (!isset($userData['id'])) {
        error_log('Facebook user info error: ' . $userResponse);
        header('Location: ../login.php?error=facebook_user_info_failed');
        exit;
    }
    
    // Store Facebook data in session for processing
    $_SESSION['facebook_user'] = [
        'id' => $userData['id'],
        'name' => $userData['name'] ?? '',
        'email' => $userData['email'] ?? '',
        'picture' => $userData['picture']['data']['url'] ?? ''
    ];
    
    // Redirect to processing page
    header('Location: facebook-process.php');
    exit;
    
} elseif (isset($_GET['error'])) {
    // User denied permission
    $error = $_GET['error'];
    $errorDescription = $_GET['error_description'] ?? 'Unknown error';
    error_log("Facebook OAuth error: $error - $errorDescription");
    header('Location: ../login.php?error=facebook_denied');
    exit;
} else {
    // Invalid request
    header('Location: ../login.php?error=invalid_request');
    exit;
}
