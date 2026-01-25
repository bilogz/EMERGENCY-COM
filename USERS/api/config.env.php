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
    $loaded = true;

    $rootEnvPath = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . '.env';
    if (!file_exists($rootEnvPath)) {
        return;
    }

    $lines = @file($rootEnvPath, FILE_IGNORE_NEW_LINES);
    if (!is_array($lines)) {
        return;
    }

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || strpos($line, '#') === 0) {
            continue;
        }
        if (strpos($line, 'export ') === 0) {
            $line = trim(substr($line, 7));
        }
        if (strpos($line, '=') === false) {
            continue;
        }
        list($key, $value) = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);
        if ($key === '') {
            continue;
        }
        $value = trim($value, " \t\n\r\0\x0B");
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
        }
    }
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
        return $envValue;
    }
    
    // Second, try local config file (not committed to Git)
    if ($localConfig === null) {
        $localConfigPath = __DIR__ . '/config.local.php';
        if (file_exists($localConfigPath)) {
            $localConfig = require $localConfigPath;
        } else {
            $localConfig = [];
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
        'ai_provider' => getSecureConfig('AI_PROVIDER', 'libretranslate'),
        'gemini_model' => getSecureConfig('GEMINI_MODEL', 'gemini-2.5-flash'),
        'ai_api_key' => getSecureConfig('AI_API_KEY', ''),
        'ai_api_key_analysis' => getSecureConfig('AI_API_KEY_ANALYSIS', ''),
        
        // Google OAuth
        'google_client_id' => getSecureConfig('GOOGLE_CLIENT_ID', ''),
        'google_client_secret' => getSecureConfig('GOOGLE_CLIENT_SECRET', ''),
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

