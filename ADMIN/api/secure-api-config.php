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
    // Try multiple possible paths
    $possiblePaths = [
        __DIR__ . '/../../USERS/api/config.local.php',
        __DIR__ . '/../../../USERS/api/config.local.php',
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
            if (is_array($secureConfig)) {
                // Check for purpose-specific key first
                if ($purpose === 'analysis' && !empty($secureConfig['AI_API_KEY_ANALYSIS'])) {
                    return $secureConfig['AI_API_KEY_ANALYSIS'];
                }
                if ($purpose === 'translation' && !empty($secureConfig['AI_API_KEY_TRANSLATION'])) {
                    return $secureConfig['AI_API_KEY_TRANSLATION'];
                }
                // Fallback to default key
                if (!empty($secureConfig['AI_API_KEY'])) {
                    return $secureConfig['AI_API_KEY'];
                }
            }
        } catch (Exception $e) {
            error_log("Error loading secure config file: " . $e->getMessage() . " (File: " . $secureConfigFile . ")");
        } catch (Error $e) {
            error_log("Fatal error loading secure config file: " . $e->getMessage() . " (File: " . $secureConfigFile . ")");
        }
    } else {
        $checkedPaths = implode(', ', $possiblePaths);
        error_log("Config file not found. Checked paths: " . $checkedPaths);
    }
    
    // Priority 2: Database (for backward compatibility)
    global $pdo;
    if ($pdo !== null) {
        try {
            $stmt = $pdo->prepare("SELECT api_key FROM integration_settings WHERE source = 'gemini' OR source = 'google_ai' LIMIT 1");
            $stmt->execute();
            $result = $stmt->fetch();
            $apiKey = $result['api_key'] ?? null;
            if (!empty($apiKey)) {
                return $apiKey;
            }
        } catch (Exception $e) {
            error_log("Error getting Gemini key from database: " . $e->getMessage());
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
    // Try multiple possible paths
    $possiblePaths = [
        __DIR__ . '/../../USERS/api/config.local.php',
        __DIR__ . '/../../../USERS/api/config.local.php',
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

