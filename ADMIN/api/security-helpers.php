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
 * @param PDO $pdo Database connection
 * @return array ['authorized' => bool, 'reason' => string]
 */
function checkAdminAuthorization($pdo) {
    // Check if any admin exists in the system
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM users WHERE user_type = 'admin' AND status = 'active'");
        $stmt->execute();
        $result = $stmt->fetch();
        $adminCount = (int)$result['count'];
        
        // If no admins exist, allow creation (initial setup)
        if ($adminCount === 0) {
            return ['authorized' => true, 'reason' => 'initial_setup'];
        }
        
        // If admins exist, require logged-in admin
        if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
            return ['authorized' => false, 'reason' => 'not_logged_in'];
        }
        
        // Verify the logged-in user is actually an admin in the database
        if (!isset($_SESSION['admin_user_id'])) {
            return ['authorized' => false, 'reason' => 'invalid_session'];
        }
        
        $stmt = $pdo->prepare("SELECT id, user_type, status FROM users WHERE id = ? AND user_type = 'admin' AND status = 'active'");
        $stmt->execute([$_SESSION['admin_user_id']]);
        $user = $stmt->fetch();
        
        if (!$user) {
            return ['authorized' => false, 'reason' => 'not_admin'];
        }
        
        return ['authorized' => true, 'reason' => 'authorized_admin'];
        
    } catch (PDOException $e) {
        error_log('Admin authorization check error: ' . $e->getMessage());
        return ['authorized' => false, 'reason' => 'database_error'];
    }
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
