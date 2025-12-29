<?php
/**
 * Activity Logger Helper
 * Logs admin activities and login attempts
 */

// Only include db_connect if $pdo is not already set
if (!isset($pdo) || $pdo === null) {
    require_once 'db_connect.php';
}

// Auto-create tables if they don't exist
function initializeActivityTables() {
    global $pdo;
    
    if (!$pdo) {
        return false;
    }
    
    try {
        // Create admin_activity_logs table if not exists
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS admin_activity_logs (
                id INT AUTO_INCREMENT PRIMARY KEY,
                admin_id INT NOT NULL,
                action VARCHAR(100) NOT NULL,
                description TEXT DEFAULT NULL,
                ip_address VARCHAR(45) DEFAULT NULL,
                user_agent TEXT DEFAULT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_admin_id (admin_id),
                INDEX idx_action (action),
                INDEX idx_created_at (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        
        // Create admin_login_logs table if not exists
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS admin_login_logs (
                id INT AUTO_INCREMENT PRIMARY KEY,
                admin_id INT NOT NULL,
                email VARCHAR(255) NOT NULL,
                login_status VARCHAR(20) NOT NULL,
                ip_address VARCHAR(45) DEFAULT NULL,
                user_agent TEXT DEFAULT NULL,
                login_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                logout_at DATETIME DEFAULT NULL,
                session_duration INT DEFAULT NULL,
                INDEX idx_admin_id (admin_id),
                INDEX idx_email (email),
                INDEX idx_login_status (login_status),
                INDEX idx_login_at (login_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        
        return true;
    } catch (PDOException $e) {
        error_log('Failed to initialize activity tables: ' . $e->getMessage());
        return false;
    }
}

// Initialize tables on first load
initializeActivityTables();

/**
 * Log admin activity
 * @param int $adminId Admin user ID
 * @param string $action Action performed (e.g., 'login', 'logout', 'send_notification', 'ai_translation', 'create_translation')
 * @param string|null $description Optional description
 * @param array|null $metadata Optional metadata (JSON)
 * @return bool Success status
 */
function logAdminActivity($adminId, $action, $description = null, $metadata = null) {
    global $pdo;
    
    if (!$pdo) {
        error_log('Activity Logger: Database connection not available');
        return false;
    }
    
    try {
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
        
        // Check if admin_activity_logs table has metadata column
        $hasMetadata = false;
        try {
            $checkStmt = $pdo->query("SHOW COLUMNS FROM admin_activity_logs LIKE 'metadata'");
            $hasMetadata = $checkStmt->rowCount() > 0;
        } catch (PDOException $e) {
            // Table might not exist or column doesn't exist, continue without metadata
        }
        
        if ($hasMetadata && $metadata !== null) {
            $stmt = $pdo->prepare("
                INSERT INTO admin_activity_logs (admin_id, action, description, ip_address, user_agent, metadata)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            return $stmt->execute([$adminId, $action, $description, $ipAddress, $userAgent, json_encode($metadata)]);
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO admin_activity_logs (admin_id, action, description, ip_address, user_agent)
                VALUES (?, ?, ?, ?, ?)
            ");
            return $stmt->execute([$adminId, $action, $description, $ipAddress, $userAgent]);
        }
    } catch (PDOException $e) {
        error_log('Activity Logger Error: ' . $e->getMessage());
        return false;
    }
}

/**
 * Log multilingual/translation activity specifically
 * @param int $adminId Admin user ID
 * @param string $actionType Action type (e.g., 'ai_translate', 'create_translation', 'update_translation')
 * @param int|null $alertId Alert ID
 * @param int|null $translationId Translation ID
 * @param string|null $sourceLanguage Source language code
 * @param string|null $targetLanguage Target language code
 * @param string|null $translationMethod Translation method (manual, ai, hybrid)
 * @param bool $success Whether the action succeeded
 * @param string|null $errorMessage Error message if failed
 * @param array|null $metadata Additional metadata
 * @return bool Success status
 */
function logTranslationActivity($adminId, $actionType, $alertId = null, $translationId = null, 
                                $sourceLanguage = null, $targetLanguage = null, $translationMethod = null,
                                $success = true, $errorMessage = null, $metadata = null) {
    global $pdo;
    
    if (!$pdo) {
        error_log('Translation Activity Logger: Database connection not available');
        return false;
    }
    
    try {
        // Check if translation_activity_logs table exists
        $tableExists = false;
        try {
            $checkStmt = $pdo->query("SHOW TABLES LIKE 'translation_activity_logs'");
            $tableExists = $checkStmt->rowCount() > 0;
        } catch (PDOException $e) {
            // Table doesn't exist, fall back to regular activity log
        }
        
        if ($tableExists) {
            $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
            
            $stmt = $pdo->prepare("
                INSERT INTO translation_activity_logs 
                (admin_id, action_type, alert_id, translation_id, source_language, target_language, 
                 translation_method, success, error_message, metadata, ip_address, user_agent)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            return $stmt->execute([
                $adminId,
                $actionType,
                $alertId,
                $translationId,
                $sourceLanguage,
                $targetLanguage,
                $translationMethod,
                $success ? 1 : 0,
                $errorMessage,
                $metadata ? json_encode($metadata) : null,
                $ipAddress,
                $userAgent
            ]);
        } else {
            // Fall back to regular activity log
            $description = "Translation activity: {$actionType}";
            if ($alertId) $description .= " (Alert #{$alertId})";
            if ($targetLanguage) $description .= " → {$targetLanguage}";
            if (!$success && $errorMessage) $description .= " - Error: {$errorMessage}";
            
            return logAdminActivity($adminId, $actionType, $description, $metadata);
        }
    } catch (PDOException $e) {
        error_log('Translation Activity Logger Error: ' . $e->getMessage());
        // Fall back to regular activity log
        return logAdminActivity($adminId, $actionType, "Translation activity: {$actionType}");
    }
}

/**
 * Log admin login attempt
 * @param int $adminId Admin user ID
 * @param string $email Admin email
 * @param string $status Login status ('success', 'failed', 'blocked')
 * @return int|false Login log ID on success, false on failure
 */
function logAdminLogin($adminId, $email, $status = 'success') {
    global $pdo;
    
    if (!$pdo) {
        error_log('Login Logger: Database connection not available');
        return false;
    }
    
    try {
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
        
        $stmt = $pdo->prepare("
            INSERT INTO admin_login_logs (admin_id, email, login_status, ip_address, user_agent)
            VALUES (?, ?, ?, ?, ?)
        ");
        
        if ($stmt->execute([$adminId, $email, $status, $ipAddress, $userAgent])) {
            return $pdo->lastInsertId();
        }
        return false;
    } catch (PDOException $e) {
        error_log('Login Logger Error: ' . $e->getMessage());
        return false;
    }
}

/**
 * Update login log with logout time and session duration
 * @param int $loginLogId Login log ID
 * @return bool Success status
 */
function updateLoginLogout($loginLogId) {
    global $pdo;
    
    if (!$pdo) {
        return false;
    }
    
    try {
        // Get login time
        $stmt = $pdo->prepare("SELECT login_at FROM admin_login_logs WHERE id = ?");
        $stmt->execute([$loginLogId]);
        $log = $stmt->fetch();
        
        if (!$log) {
            return false;
        }
        
        $loginTime = strtotime($log['login_at']);
        $logoutTime = time();
        $sessionDuration = $logoutTime - $loginTime;
        
        $stmt = $pdo->prepare("
            UPDATE admin_login_logs 
            SET logout_at = NOW(), session_duration = ?
            WHERE id = ?
        ");
        
        return $stmt->execute([$sessionDuration, $loginLogId]);
    } catch (PDOException $e) {
        error_log('Update Logout Error: ' . $e->getMessage());
        return false;
    }
}



