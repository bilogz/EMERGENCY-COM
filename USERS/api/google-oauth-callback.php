<?php
/**
 * Google OAuth 2.0 Callback Handler
 * 
 * This file handles the OAuth callback from Google after user authorization.
 * 
 * OAuth Flow:
 * 1. User clicks "Sign in with Google" button
 * 2. User is redirected to Google authorization page
 * 3. User grants permission
 * 4. Google redirects back to this callback URL with authorization code
 * 5. This file exchanges the code for access token
 * 6. Gets user info from Google
 * 7. Logs user in or creates account
 * 8. Redirects to index.php
 * 
 * Configure this URL in Google Cloud Console:
 * http://localhost/EMERGENCY-COM/USERS/api/google-oauth-callback.php
 * (or your production domain)
 */

session_start();

// Include DB connection
if (file_exists(__DIR__ . '/db_connect.php')) {
    require_once __DIR__ . '/db_connect.php';
} else {
    require_once '../../ADMIN/api/db_connect.php';
}

// Load Google OAuth credentials
$googleClientId = null;
$googleClientSecret = null;

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
            } elseif ($key === 'GOOGLE_CLIENT_SECRET') {
                $googleClientSecret = $value;
            }
        }
    }
}

// If not found in .env, try config.local.php
if (empty($googleClientId) || empty($googleClientSecret)) {
    $configFile = __DIR__ . '/config.local.php';
    if (file_exists($configFile)) {
        $config = require $configFile;
        if (is_array($config)) {
            if (empty($googleClientId)) {
                $googleClientId = $config['GOOGLE_CLIENT_ID'] ?? null;
            }
            if (empty($googleClientSecret)) {
                $googleClientSecret = $config['GOOGLE_CLIENT_SECRET'] ?? null;
            }
        }
    }
}

// Check if credentials are configured
if (!$googleClientId || !$googleClientSecret) {
    $_SESSION['oauth_error'] = 'Google OAuth is not configured. Please contact administrator.';
    header('Location: ../login.php?error=oauth_not_configured');
    exit();
}

// Get the callback URL (this file)
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$redirectUri = $protocol . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . '/google-oauth-callback.php';

// Handle OAuth callback
$code = $_GET['code'] ?? null;
$error = $_GET['error'] ?? null;
$state = $_GET['state'] ?? null;

// Check for errors from Google
if ($error) {
    $errorDescription = $_GET['error_description'] ?? 'Unknown error';
    $_SESSION['oauth_error'] = 'Google OAuth error: ' . $errorDescription;
    header('Location: ../login.php?error=oauth_denied');
    exit();
}

// Validate state parameter (CSRF protection)
if (!$state || !isset($_SESSION['oauth_state']) || $state !== $_SESSION['oauth_state']) {
    $_SESSION['oauth_error'] = 'Invalid state parameter. Possible CSRF attack.';
    unset($_SESSION['oauth_state']);
    header('Location: ../login.php?error=invalid_state');
    exit();
}

// Clear the state from session after validation
unset($_SESSION['oauth_state']);

// Check if authorization code is present
if (!$code) {
    $_SESSION['oauth_error'] = 'No authorization code received from Google.';
    header('Location: ../login.php?error=no_code');
    exit();
}

try {
    // Step 1: Exchange authorization code for access token
    $tokenUrl = 'https://oauth2.googleapis.com/token';
    $tokenData = [
        'code' => $code,
        'client_id' => $googleClientId,
        'client_secret' => $googleClientSecret,
        'redirect_uri' => $redirectUri,
        'grant_type' => 'authorization_code'
    ];

    $ch = curl_init($tokenUrl);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($tokenData));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
    
    $tokenResponse = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        error_log("Google OAuth token exchange failed. HTTP Code: $httpCode. Response: $tokenResponse");
        $_SESSION['oauth_error'] = 'Failed to exchange authorization code for access token.';
        header('Location: ../login.php?error=token_exchange_failed');
        exit();
    }

    $tokenData = json_decode($tokenResponse, true);
    
    if (!isset($tokenData['access_token'])) {
        error_log("Google OAuth token response missing access_token. Response: $tokenResponse");
        $_SESSION['oauth_error'] = 'Invalid response from Google. Access token not received.';
        header('Location: ../login.php?error=no_access_token');
        exit();
    }

    $accessToken = $tokenData['access_token'];

    // Step 2: Get user info from Google
    $userInfoUrl = 'https://www.googleapis.com/oauth2/v2/userinfo?access_token=' . urlencode($accessToken);
    
    $ch = curl_init($userInfoUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json']);
    
    $userInfoResponse = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        error_log("Google user info fetch failed. HTTP Code: $httpCode. Response: $userInfoResponse");
        $_SESSION['oauth_error'] = 'Failed to fetch user information from Google.';
        header('Location: ../login.php?error=user_info_failed');
        exit();
    }

    $userInfo = json_decode($userInfoResponse, true);
    
    if (!$userInfo || !isset($userInfo['email'])) {
        error_log("Google user info invalid. Response: $userInfoResponse");
        $_SESSION['oauth_error'] = 'Invalid user information received from Google.';
        header('Location: ../login.php?error=invalid_user_info');
        exit();
    }

    // Extract user information
    $googleId = $userInfo['id'] ?? null;
    $email = $userInfo['email'];
    $name = $userInfo['name'] ?? 'Google User';
    $picture = $userInfo['picture'] ?? null;
    $verifiedEmail = $userInfo['verified_email'] ?? false;

    // Step 3: Check if user exists in database
    $stmt = $pdo->prepare("SELECT id, name, email, phone, google_id FROM users WHERE email = ? OR google_id = ? LIMIT 1");
    $stmt->execute([$email, $googleId]);
    $user = $stmt->fetch();

    if ($user) {
        // Existing user - update Google ID if needed
        if (!empty($googleId)) {
            // Check if google_id column exists
            $checkStmt = $pdo->prepare("SHOW COLUMNS FROM users LIKE 'google_id'");
            $checkStmt->execute();
            $googleIdColumnExists = $checkStmt->rowCount() > 0;
            
            if ($googleIdColumnExists && empty($user['google_id'])) {
                try {
                    $updateStmt = $pdo->prepare("UPDATE users SET google_id = ? WHERE id = ?");
                    $updateStmt->execute([$googleId, $user['id']]);
                } catch (PDOException $e) {
                    error_log("Could not update google_id: " . $e->getMessage());
                }
            }
        }
        
        // Set session variables for existing user
        $_SESSION['user_logged_in'] = true;
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_phone'] = $user['phone'] ?? null;
        $_SESSION['user_type'] = 'registered';
        $_SESSION['login_method'] = 'google';
        $_SESSION['user_token'] = bin2hex(random_bytes(16));
        
        // Redirect to index.php
        header('Location: ../index.php?login=success');
        exit();
        
    } else {
        // New user - create account
        // Check if email and google_id columns exist
        $checkStmt = $pdo->prepare("SHOW COLUMNS FROM users LIKE 'email'");
        $checkStmt->execute();
        $emailColumnExists = $checkStmt->rowCount() > 0;
        
        $checkStmt = $pdo->prepare("SHOW COLUMNS FROM users LIKE 'google_id'");
        $checkStmt->execute();
        $googleIdColumnExists = $checkStmt->rowCount() > 0;
        
        // Add google_id column if it doesn't exist
        if (!$googleIdColumnExists) {
            try {
                $pdo->exec("ALTER TABLE users ADD COLUMN google_id VARCHAR(255) NULL UNIQUE");
            } catch (PDOException $e) {
                error_log("Could not add google_id column: " . $e->getMessage());
            }
        }
        
        // Generate a random password (user won't need it for Google login)
        $randomPassword = bin2hex(random_bytes(16));
        $hashedPassword = password_hash($randomPassword, PASSWORD_DEFAULT);
        
        // Insert new user
        if ($emailColumnExists && $googleIdColumnExists) {
            $insertStmt = $pdo->prepare("
                INSERT INTO users (name, email, password, google_id, status, created_at) 
                VALUES (?, ?, ?, ?, 'active', NOW())
            ");
            $insertStmt->execute([$name, $email, $hashedPassword, $googleId]);
        } else if ($emailColumnExists) {
            $insertStmt = $pdo->prepare("
                INSERT INTO users (name, email, password, status, created_at) 
                VALUES (?, ?, ?, 'active', NOW())
            ");
            $insertStmt->execute([$name, $email, $hashedPassword]);
        } else {
            $insertStmt = $pdo->prepare("
                INSERT INTO users (name, password, status, created_at) 
                VALUES (?, ?, 'active', NOW())
            ");
            $insertStmt->execute([$name, $hashedPassword]);
        }
        
        $newUserId = $pdo->lastInsertId();
        
        // Update google_id if column exists and googleId is available
        if (!empty($googleId) && $googleIdColumnExists) {
            try {
                $updateStmt = $pdo->prepare("UPDATE users SET google_id = ? WHERE id = ?");
                $updateStmt->execute([$googleId, $newUserId]);
            } catch (PDOException $e) {
                error_log("Could not update google_id for new user: " . $e->getMessage());
            }
        }
        
        // Set session variables for new user
        $_SESSION['user_logged_in'] = true;
        $_SESSION['user_id'] = $newUserId;
        $_SESSION['user_name'] = $name;
        $_SESSION['user_email'] = $email;
        $_SESSION['user_phone'] = null;
        $_SESSION['user_type'] = 'registered';
        $_SESSION['login_method'] = 'google';
        $_SESSION['user_token'] = bin2hex(random_bytes(16));
        
        // Redirect to index.php with welcome message
        header('Location: ../index.php?signup=success&welcome=1');
        exit();
    }
    
} catch (PDOException $e) {
    error_log("Google OAuth Callback PDO Exception: " . $e->getMessage());
    $_SESSION['oauth_error'] = 'Database error occurred. Please try again.';
    header('Location: ../login.php?error=database_error');
    exit();
} catch (Exception $e) {
    error_log("Google OAuth Callback General Exception: " . $e->getMessage());
    $_SESSION['oauth_error'] = 'An error occurred during authentication. Please try again.';
    header('Location: ../login.php?error=general_error');
    exit();
}
?>

