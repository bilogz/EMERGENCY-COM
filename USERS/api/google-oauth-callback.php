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

// Get the callback URL (this file) - must match exactly what was sent in the authorization request
// Use the same construction method as google-oauth-init.php to ensure they match
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'];
$scriptPath = $_SERVER['SCRIPT_NAME']; // Full path to current script
$scriptDir = dirname($scriptPath); // Directory containing the script
$callbackPath = rtrim($scriptDir, '/') . '/google-oauth-callback.php';
$redirectUri = $protocol . '://' . $host . $callbackPath;

// Remove any double slashes (except after protocol)
$redirectUri = preg_replace('#([^:])//+#', '$1/', $redirectUri);

// Log the redirect URI for debugging (remove in production)
error_log("Google OAuth Callback - Redirect URI: " . $redirectUri);

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

    // Step 3: Check if google_id column exists, create if not
    $checkStmt = $pdo->prepare("SHOW COLUMNS FROM users LIKE 'google_id'");
    $checkStmt->execute();
    $googleIdColumnExists = $checkStmt->rowCount() > 0;
    
    if (!$googleIdColumnExists) {
        try {
            $pdo->exec("ALTER TABLE users ADD COLUMN google_id VARCHAR(255) NULL UNIQUE");
            error_log("Added google_id column to users table");
        } catch (PDOException $e) {
            error_log("Could not add google_id column: " . $e->getMessage());
        }
    }

    // Step 4: Check if user exists in database
    $query = "SELECT id, name, email, phone";
    if ($googleIdColumnExists) {
        $query .= ", google_id";
    }
    $query .= " FROM users WHERE email = ?";
    if ($googleIdColumnExists && !empty($googleId)) {
        $query .= " OR google_id = ?";
    }
    $query .= " LIMIT 1";
    
    $stmt = $pdo->prepare($query);
    if ($googleIdColumnExists && !empty($googleId)) {
        $stmt->execute([$email, $googleId]);
    } else {
        $stmt->execute([$email]);
    }
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        // Existing user - update Google ID and email if needed
        $updateFields = [];
        $updateValues = [];
        
        if (!empty($googleId) && $googleIdColumnExists && empty($user['google_id'])) {
            $updateFields[] = "google_id = ?";
            $updateValues[] = $googleId;
        }
        
        // Update email if it's missing but we have it from Google
        if (empty($user['email']) && !empty($email)) {
            $updateFields[] = "email = ?";
            $updateValues[] = $email;
        }
        
        // Update name if it's different (use Google's name as it's more current)
        if (!empty($name) && $name !== $user['name']) {
            $updateFields[] = "name = ?";
            $updateValues[] = $name;
        }
        
        if (!empty($updateFields)) {
            $updateValues[] = $user['id'];
            $updateQuery = "UPDATE users SET " . implode(", ", $updateFields) . ", updated_at = NOW() WHERE id = ?";
            try {
                $updateStmt = $pdo->prepare($updateQuery);
                $updateStmt->execute($updateValues);
            } catch (PDOException $e) {
                error_log("Could not update user: " . $e->getMessage());
            }
        }
        
        // Log user activity
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        try {
            $activityStmt = $pdo->prepare("
                INSERT INTO user_activity_logs (user_id, activity_type, description, ip_address, user_agent, status, created_at)
                VALUES (?, 'login', ?, ?, ?, 'success', NOW())
            ");
            $activityStmt->execute([
                $user['id'],
                "User logged in via Google OAuth",
                $ipAddress,
                $userAgent
            ]);
        } catch (PDOException $e) {
            error_log("Could not log user activity: " . $e->getMessage());
        }
        
        // Set session variables for existing user
        $_SESSION['user_logged_in'] = true;
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $name; // Use updated name from Google
        $_SESSION['user_email'] = $email; // Use email from Google
        $_SESSION['user_phone'] = $user['phone'] ?? null;
        $_SESSION['user_type'] = 'registered';
        $_SESSION['login_method'] = 'google';
        $_SESSION['user_token'] = bin2hex(random_bytes(16));
        
        // Redirect to index.php
        header('Location: ../index.php?login=success');
        exit();
        
    } else {
        // New user - create account in users table
        // Check if email column exists
        $checkStmt = $pdo->prepare("SHOW COLUMNS FROM users LIKE 'email'");
        $checkStmt->execute();
        $emailColumnExists = $checkStmt->rowCount() > 0;
        
        // Insert new user (users table doesn't have password column)
        $insertFields = ['name', 'status', 'user_type'];
        $insertValues = [$name, 'active', 'citizen'];
        $placeholders = ['?', '?', '?'];
        
        if ($emailColumnExists && !empty($email)) {
            $insertFields[] = 'email';
            $insertValues[] = $email;
            $placeholders[] = '?';
        }
        
        if ($googleIdColumnExists && !empty($googleId)) {
            $insertFields[] = 'google_id';
            $insertValues[] = $googleId;
            $placeholders[] = '?';
        }
        
        // Build insert query - created_at uses NOW() directly
        $insertQuery = "INSERT INTO users (" . implode(", ", $insertFields) . ", created_at) VALUES (" . implode(", ", $placeholders) . ", NOW())";
        
        try {
            $insertStmt = $pdo->prepare($insertQuery);
            $insertStmt->execute($insertValues);
            $newUserId = $pdo->lastInsertId();
        } catch (PDOException $e) {
            error_log("Could not insert new user: " . $e->getMessage());
            $_SESSION['oauth_error'] = 'Failed to create user account. Please try again.';
            header('Location: ../login.php?error=user_creation_failed');
            exit();
        }
        
        // Log user activity
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        try {
            $activityStmt = $pdo->prepare("
                INSERT INTO user_activity_logs (user_id, activity_type, description, ip_address, user_agent, status, created_at)
                VALUES (?, 'signup', ?, ?, ?, 'success', NOW())
            ");
            $activityStmt->execute([
                $newUserId,
                "New user registered via Google OAuth",
                $ipAddress,
                $userAgent
            ]);
        } catch (PDOException $e) {
            error_log("Could not log user activity: " . $e->getMessage());
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

