<?php
/**
 * SECURE ENVIRONMENT CONFIGURATION LOADER
 * 
 * This file loads sensitive configuration from environment variables
 * or from a local config file that is NOT committed to Git.
 * 
 * SAFE TO COMMIT - Contains no actual credentials
 */

// Prevent direct access
if (!defined('SECURE_ACCESS')) {
    define('SECURE_ACCESS', true);
}

function loadRootEnv() {
    static $loaded = false;
    if ($loaded) {
        return;
    }

    $rootEnvPath = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . '.env';
    if (!file_exists($rootEnvPath)) {
        error_log("loadRootEnv: .env file not found at: " . $rootEnvPath);
        return;
    }
    
    $lines = file($rootEnvPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') === false) continue;
        
        list($key, $value) = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);
        
        // Remove quotes if present
        $first = substr($value, 0, 1);
        $last = substr($value, -1);
        if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")) {
            $value = substr($value, 1, -1);
        }

        $existing = getenv($key);
        if ($existing === false || $existing === '') {
            putenv($key . '=' . $value);
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
            // Debug: Log loaded variables
            if (strpos($key, 'GOOGLE_') === 0) {
                error_log("loadRootEnv: Loaded $key = " . (empty($value) ? '(empty)' : '(set)'));
            }
        }
    }
    $loaded = true;
}

/**
 * Get configuration value from environment or local config
 * Priority: Environment Variable > Local Config File > Default
 */
function getSecureConfig($key, $default = null) {
    static $localConfig = null;

    loadRootEnv();
    
    // First, try environment variable
    $envValue = getenv($key);
    if ($envValue !== false) {
        $trimmed = trim((string)$envValue);
        // Ignore blank/null-like env values so they don't override valid file config.
        if ($trimmed !== '' && strcasecmp($trimmed, 'null') !== 0) {
            return $envValue;
        }
    }
    
    // Second, try local config file (not committed to Git)
    if ($localConfig === null) {
        $userLocal = [];
        $adminLocal = [];

        $localConfigPath = __DIR__ . '/config.local.php';
        if (file_exists($localConfigPath)) {
            $loaded = require $localConfigPath;
            if (is_array($loaded)) {
                $userLocal = $loaded;
            }
        }

        $adminLocalConfigPath = dirname(__DIR__, 2) . '/ADMIN/api/config.local.php';
        if (file_exists($adminLocalConfigPath)) {
            $loaded = require $adminLocalConfigPath;
            if (is_array($loaded)) {
                $adminLocal = $loaded;
            }
        }

        // Merge so USERS overrides ADMIN when keys overlap.
        // Keep ADMIN values for critical chat storage keys when USERS-side override is blank.
        $localConfig = $adminLocal;
        $noBlankOverrideKeys = [
            'CHAT_IMAGE_STORAGE_DRIVER',
            'PG_IMG_URL',
            'PG_IMG_HOST',
            'PG_IMG_DB',
            'PG_IMG_USER',
        ];
        foreach ($userLocal as $cfgKey => $cfgValue) {
            if (
                in_array($cfgKey, $noBlankOverrideKeys, true) &&
                is_string($cfgValue) &&
                (trim($cfgValue) === '' || strcasecmp(trim($cfgValue), 'null') === 0)
            ) {
                continue;
            }
            $localConfig[$cfgKey] = $cfgValue;
        }
    }
    
    if (isset($localConfig[$key])) {
        return $localConfig[$key];
    }
    
    // Return default
    return $default;
}

/**
 * Database Configuration
 */
function getDatabaseConfig() {
    return [
        // Primary database (production/remote)
        'primary' => [
            'host' => getSecureConfig('DB_HOST', 'localhost'),
            'port' => (int) getSecureConfig('DB_PORT', 3306),
            'name' => getSecureConfig('DB_NAME', 'emer_comm_test'),
            'user' => getSecureConfig('DB_USER', 'root'),
            'pass' => getSecureConfig('DB_PASS', ''),
        ],
        // Fallback database (local development)
        'fallback' => [
            'host' => getSecureConfig('DB_FALLBACK_HOST', 'localhost'),
            'port' => (int) getSecureConfig('DB_FALLBACK_PORT', 3306),
            'name' => getSecureConfig('DB_FALLBACK_NAME', 'emer_comm_test'),
            'user' => getSecureConfig('DB_FALLBACK_USER', 'root'),
            'pass' => getSecureConfig('DB_FALLBACK_PASS', ''),
        ],
        'charset' => 'utf8mb4',
    ];
}

/**
 * API Keys Configuration
 */
function getApiConfig() {
    return [
        // AI/Analysis (not used for alert translation)
        'ai_provider' => getSecureConfig('AI_PROVIDER', 'argos'),
        'gemini_model' => getSecureConfig('GEMINI_MODEL', 'gemini-2.5-flash'),
        'ai_api_key' => getSecureConfig('AI_API_KEY', ''),
        'ai_api_key_analysis' => getSecureConfig('AI_API_KEY_ANALYSIS', ''),
        
        // Google OAuth
        'google_client_id' => getSecureConfig('GOOGLE_CLIENT_ID', ''),
        'google_client_secret' => getSecureConfig('GOOGLE_CLIENT_SECRET', '') ?: getSecureConfig('GOOGLE_SECRET', ''),
    ];
}

/**
 * Email/SMTP Configuration
 */
function getEmailConfig() {
    return [
        'smtp_host' => getSecureConfig('SMTP_HOST', 'smtp.gmail.com'),
        'smtp_port' => (int) getSecureConfig('SMTP_PORT', 587),
        'smtp_user' => getSecureConfig('SMTP_USER', ''),
        'smtp_pass' => getSecureConfig('SMTP_PASS', ''),
        'smtp_from' => getSecureConfig('SMTP_FROM', ''),
        'smtp_from_name' => getSecureConfig('SMTP_FROM_NAME', 'Emergency Alert System'),
    ];
}

/**
 * Check if running in production
 */
function isProduction() {
    return getSecureConfig('APP_ENV', 'development') === 'production';
}

/**
 * Check if running in development
 */
function isDevelopment() {
    return getSecureConfig('APP_ENV', 'development') === 'development';
}

