<?php
/**
 * Secure API Configuration Helper
 * Centralized secure API key management
 * All modules should use this to get API keys
 */

/**
 * Get Gemini API Key securely
 * Checks: 1) Secure config file, 2) Database, 3) Environment variable
 * @param string $purpose Optional: 'analysis', 'translation', or 'default' - determines which key to use
 */
function getGeminiApiKey($purpose = 'default') {
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
                if ($purpose === 'analysis' && !empty($secureConfig['AI_API_KEY_ANALYSIS'])) {
                    error_log("Found AI_API_KEY_ANALYSIS in config");
                    return $secureConfig['AI_API_KEY_ANALYSIS'];
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
?>

