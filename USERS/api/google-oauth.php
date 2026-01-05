<?php
/**
 * Google OAuth Login/Registration Handler
 * Handles Google OAuth authentication flow
 */

session_start();
header('Content-Type: application/json');

// Include DB connection - try USERS first, then ADMIN
if (file_exists(__DIR__ . '/db_connect.php')) {
    require_once __DIR__ . '/db_connect.php';
} else {
    require_once '../../ADMIN/api/db_connect.php';
}

// Check if database connection was successful
if (!isset($pdo) || $pdo === null) {
    echo json_encode([
        "success" => false,
        "message" => "Database connection failed. Please check your database configuration.",
        "error_code" => "DB_CONNECTION_FAILED"
    ]);
    exit();
}

// Load Google OAuth credentials
// First try .env file (preferred), then fall back to config.local.php
$googleClientId = null;
$googleClientSecret = null;

// Try .env file first
$envFile = __DIR__ . '/.env';
if (file_exists($envFile)) {
    $envContent = file_get_contents($envFile);
    $lines = explode("\n", $envContent);
    foreach ($lines as $line) {
        $line = trim($line);
        // Skip empty lines and comments
        if (empty($line) || strpos($line, '#') === 0) {
            continue;
        }
        // Parse KEY=VALUE format
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            // Remove quotes if present
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
    $config = file_exists($configFile) ? require $configFile : [];
    if (empty($googleClientId)) {
        $googleClientId = $config['GOOGLE_CLIENT_ID'] ?? null;
    }
    if (empty($googleClientSecret)) {
        $googleClientSecret = $config['GOOGLE_CLIENT_SECRET'] ?? null;
    }
}

if (!$googleClientId || !$googleClientSecret) {
    echo json_encode([
        "success" => false,
        "message" => "Google OAuth is not configured. Please contact administrator."
    ]);
    exit();
}

// Get POST data
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if ($data === null) {
    $data = $_POST;
}

$action = isset($data['action']) ? $data['action'] : 'verify';

try {
    if ($action === 'verify') {
        // Get user info from OAuth2 userinfo endpoint response
        $userInfo = isset($data['user_info']) ? $data['user_info'] : null;
        
        if (!$userInfo || !is_array($userInfo)) {
            echo json_encode([
                "success" => false,
                "message" => "User information is required."
            ]);
            exit();
        }
        
        $googleId = $userInfo['id'] ?? null;
        $email = $userInfo['email'] ?? null;
        $name = $userInfo['name'] ?? 'Google User';
        $picture = $userInfo['picture'] ?? null;
        
        if (!$email) {
            echo json_encode([
                "success" => false,
                "message" => "Email is required for authentication."
            ]);
            exit();
        }
        
        // Check if google_id column exists BEFORE using it in query
        $checkStmt = $pdo->prepare("SHOW COLUMNS FROM users LIKE 'google_id'");
        $checkStmt->execute();
        $googleIdColumnExists = $checkStmt->rowCount() > 0;
        
        // Add google_id column if it doesn't exist
        if (!$googleIdColumnExists) {
            try {
                $pdo->exec("ALTER TABLE users ADD COLUMN google_id VARCHAR(255) NULL UNIQUE COMMENT 'Google OAuth user ID'");
                $googleIdColumnExists = true;
                error_log("Added google_id column to users table");
            } catch (PDOException $e) {
                error_log("Could not add google_id column: " . $e->getMessage());
            }
        }
        
        // Check if user exists by email or Google ID (only if column exists and googleId is provided)
        if ($googleIdColumnExists && !empty($googleId)) {
            $stmt = $pdo->prepare("SELECT id, name, email, phone, google_id FROM users WHERE email = ? OR google_id = ? LIMIT 1");
            $stmt->execute([$email, $googleId]);
        } else {
            $stmt = $pdo->prepare("SELECT id, name, email, phone FROM users WHERE email = ? LIMIT 1");
            $stmt->execute([$email]);
        }
        $user = $stmt->fetch();
        
        if ($user) {
            // Update Google ID if not set
            if (!empty($googleId) && $googleIdColumnExists && empty($user['google_id'] ?? null)) {
                try {
                    $updateStmt = $pdo->prepare("UPDATE users SET google_id = ? WHERE id = ?");
                    $updateStmt->execute([$googleId, $user['id']]);
                } catch (PDOException $e) {
                    error_log("Could not update google_id: " . $e->getMessage());
                }
            }
            
            // Set session variables
            session_start(); // Ensure session is started
            $_SESSION['user_logged_in'] = true;
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_phone'] = $user['phone'] ?? null;
            $_SESSION['user_type'] = 'registered';
            $_SESSION['login_method'] = 'google';
            $_SESSION['user_token'] = bin2hex(random_bytes(16));
            
            echo json_encode([
                "success" => true,
                "message" => "Login successful!",
                "user_id" => $user['id'],
                "username" => $user['name'],
                "email" => $user['email'],
                "phone" => $user['phone'] ?? null,
                "user_type" => "registered",
                "is_new_user" => false
            ]);
        } else {
            // New user - create account
            // Check if email column exists
            $checkStmt = $pdo->prepare("SHOW COLUMNS FROM users LIKE 'email'");
            $checkStmt->execute();
            $emailColumnExists = $checkStmt->rowCount() > 0;
            
            // google_id column already checked and added above if needed
            
            // Generate a random password (user won't need it for Google login)
            $randomPassword = bin2hex(random_bytes(16));
            $hashedPassword = password_hash($randomPassword, PASSWORD_DEFAULT);
            
            // Insert new user
            if ($emailColumnExists && $googleIdColumnExists) {
                $insertStmt = $pdo->prepare("
                    INSERT INTO users (name, email, password, google_id, created_at) 
                    VALUES (?, ?, ?, ?, NOW())
                ");
                $insertStmt->execute([$name, $email, $hashedPassword, $googleId]);
            } else if ($emailColumnExists) {
                $insertStmt = $pdo->prepare("
                    INSERT INTO users (name, email, password, created_at) 
                    VALUES (?, ?, ?, NOW())
                ");
                $insertStmt->execute([$name, $email, $hashedPassword]);
            } else {
                $insertStmt = $pdo->prepare("
                    INSERT INTO users (name, password, created_at) 
                    VALUES (?, ?, NOW())
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
            
            // Set session variables
            session_start(); // Ensure session is started
            $_SESSION['user_logged_in'] = true;
            $_SESSION['user_id'] = $newUserId;
            $_SESSION['user_name'] = $name;
            $_SESSION['user_email'] = $email;
            $_SESSION['user_phone'] = null;
            $_SESSION['user_type'] = 'registered';
            $_SESSION['login_method'] = 'google';
            $_SESSION['user_token'] = bin2hex(random_bytes(16));
            
            echo json_encode([
                "success" => true,
                "message" => "Account created and logged in successfully!",
                "user_id" => $newUserId,
                "username" => $name,
                "email" => $email,
                "phone" => null,
                "user_type" => "registered",
                "is_new_user" => true
            ]);
        }
    } else {
        echo json_encode([
            "success" => false,
            "message" => "Invalid action."
        ]);
    }
    
} catch (PDOException $e) {
    error_log("Google OAuth PDO Exception: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    // Show detailed error in development, generic message in production
    $errorMessage = "Database error occurred. Please try again.";
    if (defined('DEBUG_MODE') && DEBUG_MODE) {
        $errorMessage .= " Error: " . $e->getMessage();
    }
    echo json_encode([
        "success" => false,
        "message" => $errorMessage,
        "error_code" => "DB_ERROR"
    ]);
} catch (Exception $e) {
    error_log("Google OAuth General Exception: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    // Show detailed error in development, generic message in production
    $errorMessage = "Server error occurred. Please try again.";
    if (defined('DEBUG_MODE') && DEBUG_MODE) {
        $errorMessage .= " Error: " . $e->getMessage();
    }
    echo json_encode([
        "success" => false,
        "message" => $errorMessage,
        "error_code" => "SERVER_ERROR"
    ]);
}
?>

