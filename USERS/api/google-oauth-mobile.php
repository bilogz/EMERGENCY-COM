<?php
/**
 * Google OAuth Mobile App Endpoint
 * Handles Google OAuth authentication for mobile app (returns token format)
 */

header('Content-Type: application/json');

// Include DB connection - try local first, then admin
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
        // Get user info from mobile app (Google Sign-In returns user info)
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
        
        // Check if google_id column exists
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
        
        // Check if user exists by email or Google ID
        if ($googleIdColumnExists && !empty($googleId)) {
            $stmt = $pdo->prepare("SELECT id, name, email, phone, google_id FROM users WHERE email = ? OR google_id = ? LIMIT 1");
            $stmt->execute([$email, $googleId]);
        } else {
            $stmt = $pdo->prepare("SELECT id, name, email, phone FROM users WHERE email = ? LIMIT 1");
            $stmt->execute([$email]);
        }
        $user = $stmt->fetch();
        
        // Device information (optional)
        $deviceId = isset($data['device_id']) ? trim($data['device_id']) : null;
        $deviceType = isset($data['device_type']) ? trim($data['device_type']) : 'android';
        $deviceName = isset($data['device_name']) ? trim($data['device_name']) : null;
        $pushToken = isset($data['push_token']) ? trim($data['push_token']) : null;
        
        if ($user) {
            // Existing user - update Google ID if not set
            if (!empty($googleId) && $googleIdColumnExists && empty($user['google_id'] ?? null)) {
                try {
                    $updateStmt = $pdo->prepare("UPDATE users SET google_id = ? WHERE id = ?");
                    $updateStmt->execute([$googleId, $user['id']]);
                } catch (PDOException $e) {
                    error_log("Could not update google_id: " . $e->getMessage());
                }
            }
            
            // Register/update device
            if (!empty($deviceId)) {
                try {
                    $deviceStmt = $pdo->prepare("
                        INSERT INTO user_devices (user_id, device_id, device_type, device_name, push_token, is_active, last_active) 
                        VALUES (?, ?, ?, ?, ?, 1, NOW()) 
                        ON DUPLICATE KEY UPDATE push_token = VALUES(push_token), device_name = VALUES(device_name), is_active = 1, last_active = NOW()
                    ");
                    $deviceStmt->execute([$user['id'], $deviceId, $deviceType, $deviceName, $pushToken]);
                } catch (PDOException $e) {
                    error_log("Could not register device: " . $e->getMessage());
                }
            }
            
            // Generate token for mobile app
            $token = bin2hex(random_bytes(16));
            
            echo json_encode([
                "success" => true,
                "message" => "Login successful!",
                "user_id" => $user['id'],
                "username" => $user['name'],
                "email" => $user['email'],
                "phone" => $user['phone'] ?? null,
                "token" => $token,
                "is_new_user" => false
            ]);
        } else {
            // New user - create account
            // Check if phone column exists and if it's required
            $checkStmt = $pdo->prepare("SHOW COLUMNS FROM users WHERE Field = 'phone'");
            $checkStmt->execute();
            $phoneColumn = $checkStmt->fetch(PDO::FETCH_ASSOC);
            $phoneColumnExists = $phoneColumn !== false;
            $phoneRequired = $phoneColumnExists && ($phoneColumn['Null'] === 'NO' || $phoneColumn['Null'] === '');
            
            // Check if email_verified column exists
            $checkStmt = $pdo->prepare("SHOW COLUMNS FROM users LIKE 'email_verified'");
            $checkStmt->execute();
            $emailVerifiedExists = $checkStmt->rowCount() > 0;
            
            // If phone is required, make it nullable
            if ($phoneRequired) {
                try {
                    $pdo->exec("ALTER TABLE users MODIFY COLUMN phone VARCHAR(20) DEFAULT NULL");
                    error_log("Modified phone column to allow NULL for Google OAuth users");
                    $phoneRequired = false;
                } catch (PDOException $e) {
                    error_log("Could not modify phone column: " . $e->getMessage());
                }
            }
            
            // Build INSERT query
            $insertColumns = ['name'];
            $insertValues = [$name];
            
            $insertColumns[] = 'email';
            $insertValues[] = $email;
            
            if ($phoneColumnExists) {
                $insertColumns[] = 'phone';
                $insertValues[] = null;
            }
            
            // Add google_id if available
            if ($googleIdColumnExists && !empty($googleId)) {
                $insertColumns[] = 'google_id';
                $insertValues[] = $googleId;
            }
            
            // Mark email as verified
            if ($emailVerifiedExists) {
                $insertColumns[] = 'email_verified';
                $insertValues[] = 1;
            }
            
            $insertColumns[] = 'created_at';
            
            $placeholders = array_fill(0, count($insertValues), '?');
            $placeholders[] = 'NOW()';
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
                error_log("Error: " . $e->getMessage());
                throw $e;
            }
            
            // Register device if provided
            if (!empty($deviceId)) {
                try {
                    $deviceStmt = $pdo->prepare("
                        INSERT INTO user_devices (user_id, device_id, device_type, device_name, push_token, is_active, last_active) 
                        VALUES (?, ?, ?, ?, ?, 1, NOW())
                    ");
                    $deviceStmt->execute([$newUserId, $deviceId, $deviceType, $deviceName, $pushToken]);
                } catch (PDOException $e) {
                    error_log("Could not register device: " . $e->getMessage());
                }
            }
            
            // Generate token for mobile app
            $token = bin2hex(random_bytes(16));
            
            echo json_encode([
                "success" => true,
                "message" => "Account created and logged in successfully!",
                "user_id" => $newUserId,
                "username" => $name,
                "email" => $email,
                "phone" => null,
                "token" => $token,
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
    error_log("Google OAuth Mobile PDO Exception: " . $e->getMessage());
    echo json_encode([
        "success" => false,
        "message" => "Database error occurred. Please try again.",
        "error_code" => "DB_ERROR"
    ]);
} catch (Exception $e) {
    error_log("Google OAuth Mobile General Exception: " . $e->getMessage());
    echo json_encode([
        "success" => false,
        "message" => "Server error occurred. Please try again.",
        "error_code" => "SERVER_ERROR"
    ]);
}
?>

