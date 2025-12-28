<?php
/**
 * Security Helper Functions
 * Provides security utilities for admin account creation and management
 */

/**
 * Generate CSRF token
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 */
function verifyCSRFToken($token) {
    if (!isset($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Check if current user is authorized to create admin accounts
 * Only super_admin can create admin accounts
 * @param PDO $pdo Database connection
 * @return array ['authorized' => bool, 'reason' => string, 'admin_data' => array|null]
 */
function checkAdminAuthorization($pdo) {
    try {
        // Check if admin_user table exists, if not, check users table (backward compatibility)
        $tableExists = false;
        try {
            $pdo->query("SELECT 1 FROM admin_user LIMIT 1");
            $tableExists = true;
        } catch (PDOException $e) {
            // Table doesn't exist, use users table
        }
        
        if ($tableExists) {
            // Use admin_user table
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM admin_user WHERE status = 'active'");
            $stmt->execute();
            $result = $stmt->fetch();
            $adminCount = (int)$result['count'];
            
            // If no admins exist, allow creation (initial setup)
            if ($adminCount === 0) {
                return ['authorized' => true, 'reason' => 'initial_setup', 'admin_data' => null];
            }
            
            // If admins exist, require logged-in super_admin
            if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
                return ['authorized' => false, 'reason' => 'not_logged_in', 'admin_data' => null];
            }
            
            // Verify the logged-in user is actually a super_admin
            if (!isset($_SESSION['admin_user_id'])) {
                return ['authorized' => false, 'reason' => 'invalid_session', 'admin_data' => null];
            }
            
            // Check in admin_user table - session stores admin_user.id
            $stmt = $pdo->prepare("SELECT id, user_id, name, email, role, status FROM admin_user WHERE id = ? AND status = 'active'");
            $stmt->execute([$_SESSION['admin_user_id']]);
            $admin = $stmt->fetch();
            
            if (!$admin) {
                return ['authorized' => false, 'reason' => 'not_admin', 'admin_data' => null];
            }
            
            // Only super_admin can create admin accounts
            if ($admin['role'] !== 'super_admin') {
                return ['authorized' => false, 'reason' => 'not_super_admin', 'admin_data' => $admin];
            }
            
            return ['authorized' => true, 'reason' => 'authorized_super_admin', 'admin_data' => $admin];
        } else {
            // Fallback to users table (backward compatibility)
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM users WHERE user_type = 'admin' AND status = 'active'");
            $stmt->execute();
            $result = $stmt->fetch();
            $adminCount = (int)$result['count'];
            
            // If no admins exist, allow creation (initial setup)
            if ($adminCount === 0) {
                return ['authorized' => true, 'reason' => 'initial_setup', 'admin_data' => null];
            }
            
            // If admins exist, require logged-in admin
            if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
                return ['authorized' => false, 'reason' => 'not_logged_in', 'admin_data' => null];
            }
            
            // Verify the logged-in user is actually an admin in the database
            if (!isset($_SESSION['admin_user_id'])) {
                return ['authorized' => false, 'reason' => 'invalid_session', 'admin_data' => null];
            }
            
            $stmt = $pdo->prepare("SELECT id, user_type, status FROM users WHERE id = ? AND user_type = 'admin' AND status = 'active'");
            $stmt->execute([$_SESSION['admin_user_id']]);
            $user = $stmt->fetch();
            
            if (!$user) {
                return ['authorized' => false, 'reason' => 'not_admin', 'admin_data' => null];
            }
            
            // For backward compatibility, allow first admin to create accounts
            // In production, migrate to admin_user table for proper role-based access
            return ['authorized' => true, 'reason' => 'authorized_admin', 'admin_data' => $user];
        }
        
    } catch (PDOException $e) {
        error_log('Admin authorization check error: ' . $e->getMessage());
        return ['authorized' => false, 'reason' => 'database_error', 'admin_data' => null];
    }
}

/**
 * Check if admin_user table exists and create it if needed
 * @param PDO $pdo Database connection
 * @return bool True if table exists or was created successfully
 */
function ensureAdminUserTable($pdo) {
    try {
        $pdo->query("SELECT 1 FROM admin_user LIMIT 1");
        return true; // Table exists
    } catch (PDOException $e) {
        // Table doesn't exist, create it
        try {
            $sql = file_get_contents(__DIR__ . '/create_admin_user_table.sql');
            if ($sql) {
                // Execute only the CREATE TABLE statement
                $createTableSQL = "CREATE TABLE IF NOT EXISTS admin_user (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT DEFAULT NULL COMMENT 'Optional reference to users table (NULL for standalone admin accounts)',
                    name VARCHAR(255) NOT NULL COMMENT 'Full name of the admin',
                    username VARCHAR(100) DEFAULT NULL COMMENT 'Username for login',
                    email VARCHAR(255) NOT NULL COMMENT 'Email address (unique)',
                    password VARCHAR(255) NOT NULL COMMENT 'Hashed password',
                    role VARCHAR(20) DEFAULT 'admin' COMMENT 'super_admin, admin, staff',
                    status VARCHAR(20) DEFAULT 'pending_approval' COMMENT 'active, inactive, suspended, pending_approval',
                    phone VARCHAR(20) DEFAULT NULL COMMENT 'Phone number',
                    created_by INT DEFAULT NULL COMMENT 'ID of admin who created this account',
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    last_login DATETIME DEFAULT NULL,
                    UNIQUE KEY unique_email (email),
                    UNIQUE KEY unique_username (username),
                    INDEX idx_user_id (user_id),
                    INDEX idx_role (role),
                    INDEX idx_status (status),
                    INDEX idx_created_by (created_by),
                    INDEX idx_created_at (created_at),
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
                    FOREIGN KEY (created_by) REFERENCES admin_user(id) ON DELETE SET NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
                
                $pdo->exec($createTableSQL);
                return true;
            }
        } catch (PDOException $e2) {
            error_log('Failed to create admin_user table: ' . $e2->getMessage());
            return false;
        }
    }
    return false;
}

/**
 * Rate limiting - Check if IP has exceeded attempt limit
 * @param PDO $pdo Database connection
 * @param string $ip IP address
 * @param int $maxAttempts Maximum attempts allowed
 * @param int $timeWindow Time window in seconds (default 1 hour)
 * @return array ['allowed' => bool, 'remaining' => int, 'reset_time' => int]
 */
function checkRateLimit($pdo, $ip, $maxAttempts = 5, $timeWindow = 3600) {
    try {
        // Create rate_limit table if it doesn't exist
        $pdo->exec("CREATE TABLE IF NOT EXISTS rate_limits (
            id INT AUTO_INCREMENT PRIMARY KEY,
            ip_address VARCHAR(45) NOT NULL,
            action VARCHAR(50) NOT NULL,
            attempts INT DEFAULT 1,
            first_attempt DATETIME DEFAULT CURRENT_TIMESTAMP,
            last_attempt DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_ip_action (ip_address, action),
            INDEX idx_last_attempt (last_attempt)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        
        $action = 'create_admin';
        $cutoffTime = date('Y-m-d H:i:s', time() - $timeWindow);
        
        // Get current attempts
        $stmt = $pdo->prepare("SELECT attempts, last_attempt FROM rate_limits WHERE ip_address = ? AND action = ?");
        $stmt->execute([$ip, $action]);
        $record = $stmt->fetch();
        
        if ($record) {
            $lastAttempt = strtotime($record['last_attempt']);
            $timeSinceLastAttempt = time() - $lastAttempt;
            
            // If outside time window, reset
            if ($timeSinceLastAttempt > $timeWindow) {
                $stmt = $pdo->prepare("UPDATE rate_limits SET attempts = 1, first_attempt = CURRENT_TIMESTAMP, last_attempt = CURRENT_TIMESTAMP WHERE ip_address = ? AND action = ?");
                $stmt->execute([$ip, $action]);
                return ['allowed' => true, 'remaining' => $maxAttempts - 1, 'reset_time' => time() + $timeWindow];
            }
            
            $attempts = (int)$record['attempts'];
            if ($attempts >= $maxAttempts) {
                $resetTime = $lastAttempt + $timeWindow;
                return ['allowed' => false, 'remaining' => 0, 'reset_time' => $resetTime];
            }
            
            // Increment attempts
            $stmt = $pdo->prepare("UPDATE rate_limits SET attempts = attempts + 1, last_attempt = CURRENT_TIMESTAMP WHERE ip_address = ? AND action = ?");
            $stmt->execute([$ip, $action]);
            
            return ['allowed' => true, 'remaining' => $maxAttempts - $attempts - 1, 'reset_time' => $resetTime ?? time() + $timeWindow];
        } else {
            // First attempt
            $stmt = $pdo->prepare("INSERT INTO rate_limits (ip_address, action, attempts) VALUES (?, ?, 1)");
            $stmt->execute([$ip, $action]);
            return ['allowed' => true, 'remaining' => $maxAttempts - 1, 'reset_time' => time() + $timeWindow];
        }
        
    } catch (PDOException $e) {
        error_log('Rate limit check error: ' . $e->getMessage());
        // On error, allow the request (fail open for availability)
        return ['allowed' => true, 'remaining' => $maxAttempts, 'reset_time' => time() + $timeWindow];
    }
}

/**
 * Validate password strength
 * @param string $password Password to validate
 * @return array ['valid' => bool, 'errors' => array]
 */
function validatePasswordStrength($password) {
    $errors = [];
    
    if (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters long.';
    }
    
    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = 'Password must contain at least one uppercase letter.';
    }
    
    if (!preg_match('/[a-z]/', $password)) {
        $errors[] = 'Password must contain at least one lowercase letter.';
    }
    
    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = 'Password must contain at least one number.';
    }
    
    if (!preg_match('/[^A-Za-z0-9]/', $password)) {
        $errors[] = 'Password must contain at least one special character.';
    }
    
    // Check against common weak passwords
    $commonPasswords = ['password', 'password123', 'admin123', '12345678', 'qwerty123', 'admin', 'letmein'];
    if (in_array(strtolower($password), $commonPasswords)) {
        $errors[] = 'Password is too common. Please choose a more secure password.';
    }
    
    return [
        'valid' => empty($errors),
        'errors' => $errors
    ];
}

/**
 * Log admin account creation for audit trail
 * @param PDO $pdo Database connection
 * @param int $createdUserId ID of the newly created user
 * @param int|null $createdByUserId ID of the admin who created the account (null for initial setup)
 * @param string $ipAddress IP address of the creator
 */
function logAdminCreation($pdo, $createdUserId, $createdByUserId, $ipAddress) {
    try {
        // Create audit_log table if it doesn't exist
        $pdo->exec("CREATE TABLE IF NOT EXISTS audit_log (
            id INT AUTO_INCREMENT PRIMARY KEY,
            action VARCHAR(100) NOT NULL,
            user_id INT DEFAULT NULL,
            performed_by INT DEFAULT NULL,
            ip_address VARCHAR(45) DEFAULT NULL,
            details TEXT DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_user (user_id),
            INDEX idx_performed_by (performed_by),
            INDEX idx_action (action),
            INDEX idx_created_at (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        
        $stmt = $pdo->prepare("INSERT INTO audit_log (action, user_id, performed_by, ip_address, details) VALUES (?, ?, ?, ?, ?)");
        $details = json_encode([
            'action' => 'admin_account_created',
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        $stmt->execute(['admin_account_created', $createdUserId, $createdByUserId, $ipAddress, $details]);
        
    } catch (PDOException $e) {
        error_log('Audit log error: ' . $e->getMessage());
        // Don't fail the operation if logging fails
    }
}

/**
 * Sanitize and validate input
 */
function sanitizeInput($input, $type = 'string') {
    $input = trim($input);
    
    switch ($type) {
        case 'email':
            return filter_var($input, FILTER_SANITIZE_EMAIL);
        case 'string':
        default:
            // Remove any HTML tags and encode special characters
            return htmlspecialchars(strip_tags($input), ENT_QUOTES, 'UTF-8');
    }
}

/**
 * Get client IP address
 */
function getClientIP() {
    $ipKeys = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR'];
    foreach ($ipKeys as $key) {
        if (array_key_exists($key, $_SERVER) === true) {
            foreach (explode(',', $_SERVER[$key]) as $ip) {
                $ip = trim($ip);
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                    return $ip;
                }
            }
        }
    }
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

?>
