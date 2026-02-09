<?php
/**
 * Secure API Configuration Helper
 * Centralized secure API key management
 * All modules should use this to get API keys
 */

/**
 * Get Gemini API Key securely with auto-rotation support
 * Checks: 1) API Key Management Database, 2) Secure config file, 3) Database, 4) Environment variable
 * @param string $purpose Optional: 'analysis', 'translation', 'earthquake' or 'default' - determines which key to use
 * @param bool $tryRotation Whether to attempt auto-rotation if quota exceeded
 */
function getGeminiApiKey($purpose = 'default', $tryRotation = false) {
    global $pdo;
    
    // If $pdo is not set in global scope, try to get it from $GLOBALS
    if (!isset($pdo) || $pdo === null) {
        $pdo = $GLOBALS['pdo'] ?? null;
    }
    
    // Priority 0: API Key Management System (NEW)
    if ($pdo !== null) {
        try {
            // Map purpose to key name
            $keyName = 'AI_API_KEY';
            if ($purpose === 'earthquake') {
                $keyName = 'AI_API_KEY_EARTHQUAKE';
            } elseif ($purpose === 'analysis') {
                $keyName = 'AI_API_KEY_ANALYSIS';
            } elseif ($purpose === 'ai_message') {
                $keyName = 'AI_API_KEY_AI_MESSAGE';
            } elseif ($purpose === 'analysis_backup') {
                $keyName = 'AI_API_KEY_ANALYSIS_BACKUP';
            } elseif ($purpose === 'translation_backup') {
                $keyName = 'AI_API_KEY_TRANSLATION_BACKUP';
            } elseif ($purpose === 'translation') {
                $keyName = 'AI_API_KEY_TRANSLATION';
            }
            
            // Check if table exists
            $tableCheck = $pdo->query("SHOW TABLES LIKE 'api_keys_management'");
            if ($tableCheck && $tableCheck->rowCount() > 0) {
                $stmt = $pdo->prepare("SELECT key_value FROM api_keys_management WHERE key_name = ? AND is_active = 1 AND key_value IS NOT NULL AND key_value != ''");
                $stmt->execute([$keyName]);
                $apiKey = $stmt->fetchColumn();
                
                if (!empty($apiKey)) {
                    // Increment usage count
                    $pdo->prepare("UPDATE api_keys_management SET usage_count = usage_count + 1, last_used = NOW() WHERE key_name = ?")
                        ->execute([$keyName]);
                    
                    error_log("Found API key in management system: $keyName");
                    return $apiKey;
                }
            }
        } catch (PDOException $e) {
            error_log("PDO Error getting key from management system: " . $e->getMessage());
        } catch (Exception $e) {
            error_log("Error getting key from management system: " . $e->getMessage());
        }
    }
    // Priority 1: Secure config file (most secure, not in Git)
    // Try multiple possible paths - check ADMIN first, then USERS
    $baseDir = dirname(dirname(__DIR__)); // Go up from ADMIN/api to EMERGENCY-COM
    $possiblePaths = [
        __DIR__ . '/config.local.php', // ADMIN/api/config.local.php (current directory)
        __DIR__ . '/../../USERS/api/config.local.php',
        $baseDir . '/ADMIN/api/config.local.php',
        $baseDir . '/USERS/api/config.local.php',
        dirname($baseDir) . '/EMERGENCY-COM/ADMIN/api/config.local.php',
        dirname($baseDir) . '/EMERGENCY-COM/USERS/api/config.local.php',
        $_SERVER['DOCUMENT_ROOT'] . '/EMERGENCY-COM/ADMIN/api/config.local.php',
        $_SERVER['DOCUMENT_ROOT'] . '/EMERGENCY-COM/USERS/api/config.local.php',
        dirname(dirname(dirname(__DIR__))) . '/ADMIN/api/config.local.php',
        dirname(dirname(dirname(__DIR__))) . '/USERS/api/config.local.php'
    ];
    
    $secureConfigFile = null;
    foreach ($possiblePaths as $path) {
        $realPath = realpath($path);
        if ($realPath && file_exists($realPath)) {
            $secureConfigFile = $realPath;
            break;
        }
    }
    
    if ($secureConfigFile && file_exists($secureConfigFile)) {
        try {
            error_log("Loading config from: " . $secureConfigFile);
            // Suppress warnings and capture any output
            $secureConfig = @require $secureConfigFile;
            if (is_array($secureConfig)) {
                // Check for purpose-specific key first
                if ($purpose === 'earthquake' && !empty($secureConfig['AI_API_KEY_EARTHQUAKE'])) {
                    error_log("Found AI_API_KEY_EARTHQUAKE in config");
                    return $secureConfig['AI_API_KEY_EARTHQUAKE'];
                }
                if ($purpose === 'analysis_backup' && !empty($secureConfig['AI_API_KEY_ANALYSIS_BACKUP'])) {
                    error_log("Found AI_API_KEY_ANALYSIS_BACKUP in config");
                    return $secureConfig['AI_API_KEY_ANALYSIS_BACKUP'];
                }
                if ($purpose === 'ai_message' && !empty($secureConfig['AI_API_KEY_AI_MESSAGE'])) {
                    error_log("Found AI_API_KEY_AI_MESSAGE in config");
                    return $secureConfig['AI_API_KEY_AI_MESSAGE'];
                }
                if ($purpose === 'analysis' && !empty($secureConfig['AI_API_KEY_ANALYSIS'])) {
                    error_log("Found AI_API_KEY_ANALYSIS in config");
                    return $secureConfig['AI_API_KEY_ANALYSIS'];
                }
                if ($purpose === 'translation_backup' && !empty($secureConfig['AI_API_KEY_TRANSLATION_BACKUP'])) {
                    error_log("Found AI_API_KEY_TRANSLATION_BACKUP in config");
                    return $secureConfig['AI_API_KEY_TRANSLATION_BACKUP'];
                }
                if ($purpose === 'translation' && !empty($secureConfig['AI_API_KEY_TRANSLATION'])) {
                    error_log("Found AI_API_KEY_TRANSLATION in config");
                    return $secureConfig['AI_API_KEY_TRANSLATION'];
                }
                // Fallback to default key
                if (!empty($secureConfig['AI_API_KEY'])) {
                    error_log("Found AI_API_KEY in config (fallback)");
                    return $secureConfig['AI_API_KEY'];
                }
                error_log("Config file loaded but no matching key found for purpose: " . $purpose);
            } else {
                error_log("Config file did not return an array (returned: " . gettype($secureConfig) . ")");
            }
        } catch (ParseError $e) {
            error_log("Parse error loading secure config file: " . $e->getMessage() . " (File: " . $secureConfigFile . " on line " . $e->getLine() . ")");
        } catch (Exception $e) {
            error_log("Error loading secure config file: " . $e->getMessage() . " (File: " . $secureConfigFile . ")");
        } catch (Error $e) {
            error_log("Fatal error loading secure config file: " . $e->getMessage() . " (File: " . $secureConfigFile . ")");
        } catch (Throwable $e) {
            error_log("Throwable error loading secure config file: " . $e->getMessage() . " (File: " . $secureConfigFile . ")");
        }
    } else {
        // Only log if debug mode or if no config found at all (reduce log spam)
        if (empty($secureConfigFile)) {
            error_log("Config file not found. Checked " . count($possiblePaths) . " paths. Current __DIR__: " . __DIR__);
        }
    }
    
    // Priority 2: Database (for backward compatibility)
    global $pdo;
    // If $pdo is not set in global scope, try to get it from $GLOBALS
    if (!isset($pdo) || $pdo === null) {
        $pdo = $GLOBALS['pdo'] ?? null;
    }
    if ($pdo !== null) {
        try {
            // Check if table exists first to avoid errors
            $tableCheck = $pdo->query("SHOW TABLES LIKE 'integration_settings'");
            if ($tableCheck && $tableCheck->rowCount() > 0) {
                $stmt = $pdo->prepare("SELECT api_key FROM integration_settings WHERE source = 'gemini' OR source = 'google_ai' LIMIT 1");
                $stmt->execute();
                $result = $stmt->fetch();
                $apiKey = $result['api_key'] ?? null;
                if (!empty($apiKey)) {
                    error_log("Found Gemini API key in database");
                    return $apiKey;
                }
            }
        } catch (PDOException $e) {
            error_log("PDO Error getting Gemini key from database: " . $e->getMessage());
        } catch (Exception $e) {
            error_log("Error getting Gemini key from database: " . $e->getMessage());
        } catch (Error $e) {
            error_log("Fatal error getting Gemini key from database: " . $e->getMessage());
        }
    }
    
    // Priority 3: Environment variable
    if (!empty($_ENV['GEMINI_API_KEY'])) {
        return $_ENV['GEMINI_API_KEY'];
    }
    
    // Priority 4: GET parameter (for setup scripts only, not recommended)
    if (isset($_GET['api_key']) && !empty($_GET['api_key'])) {
        return $_GET['api_key'];
    }
    
    return null;
}

/**
 * Get Gemini Model (defaults to Gemini 2.5 Flash)
 */
function getGeminiModel() {
    // Try multiple possible paths - check ADMIN first, then USERS
    $possiblePaths = [
        __DIR__ . '/config.local.php', // ADMIN/api/config.local.php (current directory)
        __DIR__ . '/../../USERS/api/config.local.php',
        __DIR__ . '/../../../USERS/api/config.local.php',
        dirname(dirname(dirname(__DIR__))) . '/ADMIN/api/config.local.php',
        dirname(dirname(dirname(__DIR__))) . '/USERS/api/config.local.php'
    ];
    
    $secureConfigFile = null;
    foreach ($possiblePaths as $path) {
        if (file_exists($path)) {
            $secureConfigFile = $path;
            break;
        }
    }
    
    if ($secureConfigFile && file_exists($secureConfigFile)) {
        try {
            $secureConfig = require $secureConfigFile;
            if (is_array($secureConfig) && isset($secureConfig['GEMINI_MODEL'])) {
                return $secureConfig['GEMINI_MODEL'];
            }
        } catch (Exception $e) {
            error_log("Error loading secure config file for model: " . $e->getMessage());
        } catch (Error $e) {
            error_log("Fatal error loading secure config file for model: " . $e->getMessage());
        }
    }
    return 'gemini-2.5-flash';
}

/**
 * Auto-rotate API key when quota exceeded
 * @param string $keyName The key that hit quota limit
 * @param string $errorMessage The error message from API
 * @return string|null The backup key if rotation successful, null otherwise
 */
function rotateApiKeyOnQuotaExceeded($keyName, $errorMessage = '') {
    global $pdo;
    
    if ($pdo === null) {
        error_log("Cannot rotate key - no database connection");
        return null;
    }
    
    try {
        // Check if table exists
        $tableCheck = $pdo->query("SHOW TABLES LIKE 'api_keys_management'");
        if (!$tableCheck || $tableCheck->rowCount() === 0) {
            error_log("API key management table does not exist");
            return null;
        }
        
        // Get key settings
        $stmt = $pdo->prepare("SELECT * FROM api_keys_management WHERE key_name = ? AND auto_rotate = 1");
        $stmt->execute([$keyName]);
        $keySettings = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$keySettings) {
            error_log("Auto-rotation not enabled for key: $keyName");
            return null;
        }
        
        // Find backup key based on key name
        $backupKeyName = null;
        if ($keyName === 'AI_API_KEY_ANALYSIS') {
            $backupKeyName = 'AI_API_KEY_ANALYSIS_BACKUP';
        } elseif ($keyName === 'AI_API_KEY_TRANSLATION') {
            $backupKeyName = 'AI_API_KEY_TRANSLATION_BACKUP';
        } elseif ($keyName === 'AI_API_KEY') {
            $backupKeyName = 'AI_API_KEY_TRANSLATION'; // Fallback to translation key
        }
        
        if (!$backupKeyName) {
            error_log("No backup key defined for: $keyName");
            return null;
        }
        
        // Get backup key
        $backupStmt = $pdo->prepare("SELECT key_value FROM api_keys_management WHERE key_name = ? AND is_active = 1 AND key_value IS NOT NULL AND key_value != ''");
        $backupStmt->execute([$backupKeyName]);
        $backupKey = $backupStmt->fetchColumn();
        
        if (!$backupKey) {
            error_log("Backup key not available or not configured: $backupKeyName");
            return null;
        }
        
        // Log the rotation
        $pdo->prepare("UPDATE api_keys_management SET quota_exceeded_count = quota_exceeded_count + 1, last_rotated = NOW() WHERE key_name = ?")
            ->execute([$keyName]);
        
        // Log the change
        $pdo->prepare("INSERT INTO api_key_change_logs (key_name, action, admin_id, admin_email, notes) 
                      VALUES (?, 'rotate', 0, 'system@auto-rotation', ?)")
            ->execute([$keyName, "Auto-rotated from $keyName to $backupKeyName. Reason: $errorMessage"]);
        
        error_log("✅ Auto-rotated API key from $keyName to $backupKeyName");
        
        // Send notification email to admins
        notifyAdminsOfKeyRotation($keyName, $backupKeyName, $errorMessage);
        
        return $backupKey;
        
    } catch (Exception $e) {
        error_log("Error during key rotation: " . $e->getMessage());
        return null;
    }
}

/**
 * Notify admins when a key is auto-rotated
 */
function notifyAdminsOfKeyRotation($originalKey, $backupKey, $reason) {
    global $pdo;
    
    if ($pdo === null) return;
    
    try {
        // Get all active admin emails
        $stmt = $pdo->query("SELECT email, name FROM admin_user WHERE status = 'active'");
        $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($admins)) return;
        
        $subject = '⚠️ API Key Auto-Rotation Alert - Emergency Communication System';
        $body = "AUTOMATIC KEY ROTATION NOTIFICATION\n\n";
        $body .= "An API key has been automatically rotated due to quota limits:\n\n";
        $body .= "Original Key: $originalKey\n";
        $body .= "Rotated To: $backupKey\n";
        $body .= "Reason: $reason\n";
        $body .= "Time: " . date('Y-m-d H:i:s') . "\n\n";
        $body .= "ACTION REQUIRED:\n";
        $body .= "1. Check the API quota limits in Google Cloud Console\n";
        $body .= "2. Consider upgrading your API plan or adding more quota\n";
        $body .= "3. Review the backup key usage to ensure continuity\n\n";
        $body .= "You can manage API keys at:\n";
        $body .= "https://emergency-comm.alertaraqc.com/ADMIN/sidebar/automated-warnings.php\n\n";
        $body .= "This is an automated message from the Emergency Communication System.";
        
        // Send to each admin
        foreach ($admins as $admin) {
            @mail($admin['email'], $subject, $body, "From: noreply@emergency-com.local\r\n");
        }
        
    } catch (Exception $e) {
        error_log("Error notifying admins of key rotation: " . $e->getMessage());
    }
}

/**
 * Store Gemini API Key in database (for backward compatibility)
 */
function storeGeminiApiKeyInDatabase($apiKey) {
    global $pdo;
    if ($pdo === null || empty($apiKey)) {
        return false;
    }
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO integration_settings (source, enabled, api_key, api_url, updated_at)
            VALUES ('gemini', 0, ?, 'https://generativelanguage.googleapis.com/v1beta/', NOW())
            ON DUPLICATE KEY UPDATE 
                api_key = VALUES(api_key),
                api_url = VALUES(api_url),
                updated_at = NOW()
        ");
        return $stmt->execute([$apiKey]);
    } catch (Exception $e) {
        error_log("Error storing Gemini key in database: " . $e->getMessage());
        return false;
    }
}

/**
 * Check if AI analysis is enabled for a specific type
 * This function checks the specific AI enabled setting in ai_warning_settings table
 * @param string $type The type of AI analysis: 'weather', 'earthquake', 'disaster_monitoring', 'translation', or 'all' for global check
 * @return bool True if AI analysis is enabled for the specified type, false otherwise
 */
function isAIAnalysisEnabled($type = 'all') {
    global $pdo;
    
    // If $pdo is not set in global scope, try to get it from $GLOBALS
    if (!isset($pdo) || $pdo === null) {
        $pdo = $GLOBALS['pdo'] ?? null;
    }
    
    if ($pdo === null) {
        // If no database connection, default to disabled for safety
        return false;
    }
    
    try {
        // Check if table exists
        $tableCheck = $pdo->query("SHOW TABLES LIKE 'ai_warning_settings'");
        if (!$tableCheck || $tableCheck->rowCount() === 0) {
            // Table doesn't exist, default to disabled
            return false;
        }
        
        // Determine which field to check based on type
        $fieldName = 'ai_enabled'; // Default to global
        if ($type === 'weather') {
            $fieldName = 'ai_weather_enabled';
        } elseif ($type === 'earthquake') {
            $fieldName = 'ai_earthquake_enabled';
        } elseif ($type === 'disaster_monitoring') {
            $fieldName = 'ai_disaster_monitoring_enabled';
        } elseif ($type === 'translation') {
            $fieldName = 'ai_translation_enabled';
        }
        
        // First check if the specific field exists, if not fall back to global ai_enabled
        $stmt = $pdo->query("SHOW COLUMNS FROM ai_warning_settings LIKE '$fieldName'");
        $columnExists = $stmt->rowCount() > 0;
        
        if (!$columnExists && $type !== 'all') {
            // If specific column doesn't exist, check global ai_enabled
            $fieldName = 'ai_enabled';
        }
        
        // Get the setting from the most recent record
        $stmt = $pdo->query("SELECT $fieldName FROM ai_warning_settings ORDER BY id DESC LIMIT 1");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result && isset($result[$fieldName])) {
            return (bool)$result[$fieldName];
        }
        
        // Default to disabled if no setting found
        return false;
    } catch (PDOException $e) {
        error_log("Error checking AI analysis enabled status ($type): " . $e->getMessage());
        // On error, default to disabled for safety
        return false;
    } catch (Exception $e) {
        error_log("Error checking AI analysis enabled status ($type): " . $e->getMessage());
        return false;
    }
}
?>

