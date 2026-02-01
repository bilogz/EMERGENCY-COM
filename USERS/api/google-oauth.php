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

// Load Google OAuth configuration
require_once __DIR__ . '/config.env.php';
$config = getApiConfig();
$googleClientId = $config['google_client_id'];
$googleClientSecret = $config['google_client_secret'];

// Debug: Log what we got
error_log("google-oauth.php: GOOGLE_CLIENT_ID = " . (empty($googleClientId) ? '(empty/false)' : substr($googleClientId, 0, 20) . '...'));
error_log("google-oauth.php: GOOGLE_CLIENT_SECRET = " . (empty($googleClientSecret) ? '(empty/false)' : '(set)'));

if (!$googleClientId || !$googleClientSecret) {
    $missing = [];
    if (empty($googleClientId)) {
        $missing[] = 'GOOGLE_CLIENT_ID';
    }
    if (empty($googleClientSecret)) {
        $missing[] = 'GOOGLE_CLIENT_SECRET';
    }
    echo json_encode([
        "success" => false,
        "message" => "Google OAuth is not configured. Missing: " . implode(', ', $missing) . ".",
        "error_code" => "GOOGLE_OAUTH_NOT_CONFIGURED"
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
            
            // Check if phone column exists and if it's required (NOT NULL)
            $checkStmt = $pdo->prepare("SHOW COLUMNS FROM users WHERE Field = 'phone'");
            $checkStmt->execute();
            $phoneColumn = $checkStmt->fetch(PDO::FETCH_ASSOC);
            $phoneColumnExists = $phoneColumn !== false;
            $phoneRequired = $phoneColumnExists && ($phoneColumn['Null'] === 'NO' || $phoneColumn['Null'] === '');
            
            // Check if email_verified column exists
            $checkStmt = $pdo->prepare("SHOW COLUMNS FROM users LIKE 'email_verified'");
            $checkStmt->execute();
            $emailVerifiedExists = $checkStmt->rowCount() > 0;
            
            // If phone is required but we're setting it to NULL, try to make it nullable
            if ($phoneRequired) {
                try {
                    $pdo->exec("ALTER TABLE users MODIFY COLUMN phone VARCHAR(20) DEFAULT NULL");
                    error_log("Modified phone column to allow NULL for Google OAuth users");
                    $phoneRequired = false; // Now it's nullable
                } catch (PDOException $e) {
                    error_log("Could not modify phone column: " . $e->getMessage());
                    // If we can't make it nullable, we'll use an empty string
                }
            }
            
            // Build INSERT query with all available columns
            // Note: Password is NOT required for Google OAuth users
            $insertColumns = ['name'];
            $insertValues = [$name];
            
            if ($emailColumnExists) {
                $insertColumns[] = 'email';
                $insertValues[] = $email;
            }
            
            if ($phoneColumnExists) {
                $insertColumns[] = 'phone';
                $insertValues[] = null; // Google OAuth doesn't provide phone
            }
            
            // Add google_id if available
            if ($googleIdColumnExists && !empty($googleId)) {
                $insertColumns[] = 'google_id';
                $insertValues[] = $googleId;
            }
            
            // Mark email as verified since Google emails are verified
            if ($emailVerifiedExists) {
                $insertColumns[] = 'email_verified';
                $insertValues[] = 1;
            }
            
            // Add created_at with NOW() function
            $insertColumns[] = 'created_at';
            
            // Build the SQL query - use NOW() for created_at instead of placeholder
            $placeholders = array_fill(0, count($insertValues), '?');
            $placeholders[] = 'NOW()'; // Add NOW() for created_at
            $sql = "INSERT INTO users (" . implode(', ', $insertColumns) . ") VALUES (" . implode(', ', $placeholders) . ")";
            
            try {
                $insertStmt = $pdo->prepare($sql);
                $insertStmt->execute($insertValues);
                $newUserId = $pdo->lastInsertId();
                
                if (!$newUserId) {
                    throw new PDOException("Failed to get last insert ID");
                }
            } catch (PDOException $e) {
                error_log("INSERT failed. SQL: $sql");
                error_log("Columns: " . implode(', ', $insertColumns));
                error_log("Values: " . print_r($insertValues, true));
                error_log("Error Code: " . $e->getCode());
                error_log("Error Message: " . $e->getMessage());
                error_log("Error Info: " . print_r($insertStmt->errorInfo() ?? [], true));
                throw $e; // Re-throw to be caught by outer catch block
            }
            
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

