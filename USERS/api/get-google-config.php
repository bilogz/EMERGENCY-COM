<?php
/**
 * Get Google OAuth Configuration (Client ID only)
 * Returns only the client ID (safe to expose to frontend)
 */

// Suppress any output before JSON
ob_start();

header('Content-Type: application/json');

$envFile = __DIR__ . '/.env';
$configFile = __DIR__ . '/config.local.php';

try {
    $clientId = null;
    $config = [];
    
    // First, try to load from .env file (preferred method)
    if (file_exists($envFile)) {
        $envContent = file_get_contents($envFile);
        $lines = explode("\n", $envContent);
        foreach ($lines as $line) {
            $line = trim($line);
            // Skip empty lines and comments
            if (empty($line) || strpos($line, '#') === 0) {
                continue;
            }
            // Parse KEY=VALUE format
            if (strpos($line, '=') !== false) {
                list($key, $value) = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);
                // Remove quotes if present
                $value = trim($value, '"\'');
                if ($key === 'GOOGLE_CLIENT_ID') {
                    $clientId = $value;
                }
            }
        }
    }
    
    // If not found in .env, try config.local.php
    if (empty($clientId) && file_exists($configFile)) {
        // Load config file - suppress warnings and capture return value
        $config = @include $configFile;
        
        // Check what include returned
        // If it returns 1, the file executed but didn't return a value
        // If it returns false, there was an error including the file
        if ($config === false) {
            ob_end_clean();
            echo json_encode([
                "success" => false,
                "message" => "Error: Could not include config file.",
                "debug" => [
                    "include_returned" => "false",
                    "file_readable" => is_readable($configFile),
                    "file_size" => filesize($configFile),
                    "file_exists" => file_exists($configFile),
                    "config_file_path" => $configFile
                ]
            ]);
            exit();
        }
        
        // If include returns 1, it means the file was executed but didn't return anything
        // This usually means there's output before the return statement
        if ($config === 1 || $config === true) {
            ob_end_clean();
            echo json_encode([
                "success" => false,
                "message" => "Config file executed but did not return an array. Check for output before return statement.",
                "debug" => [
                    "include_returned" => $config === 1 ? "1 (no return)" : "true",
                    "file_readable" => is_readable($configFile),
                    "file_size" => filesize($configFile)
                ]
            ]);
            exit();
        }

        // Check if config is an array
        if (!is_array($config)) {
            ob_end_clean();
            echo json_encode([
                "success" => false,
                "message" => "Config file did not return an array.",
                "debug" => [
                    "config_type" => gettype($config),
                    "config_value" => is_string($config) ? substr($config, 0, 100) : var_export($config, true)
                ]
            ]);
            exit();
        }

        // Check for GOOGLE_CLIENT_ID in config array (if loaded from config.local.php)
        if (empty($clientId) && is_array($config) && isset($config['GOOGLE_CLIENT_ID'])) {
            $clientId = $config['GOOGLE_CLIENT_ID'];
        }
    }

    // Clean any output buffer before checking
    $output = ob_get_clean();
    if (!empty($output)) {
        // If there was output, log it but continue
        error_log("Output buffer had content: " . $output);
    }

    // Debug information (safe for production - doesn't expose actual values)
    $debugInfo = [
        "env_file_exists" => file_exists($envFile),
        "config_file_exists" => file_exists($configFile),
        "config_is_array" => is_array($config),
        "config_keys_count" => is_array($config) ? count($config) : 0,
        "has_google_client_id_key" => is_array($config) && isset($config['GOOGLE_CLIENT_ID']),
        "google_client_id_empty" => empty($clientId),
        "client_id_length" => $clientId ? strlen($clientId) : 0
    ];

    if ($clientId && !empty(trim($clientId))) {
        echo json_encode([
            "success" => true,
            "client_id" => trim($clientId)
        ]);
    } else {
        // More detailed error for debugging
        $errorMessage = "Google OAuth is not configured.";
        if (is_array($config) && !isset($config['GOOGLE_CLIENT_ID'])) {
            $errorMessage .= " GOOGLE_CLIENT_ID key not found in config.";
        } elseif (is_array($config) && empty($config['GOOGLE_CLIENT_ID'])) {
            $errorMessage .= " GOOGLE_CLIENT_ID is empty.";
        } elseif (!file_exists($envFile) && !file_exists($configFile)) {
            $errorMessage .= " Neither .env nor config.local.php found.";
        }
        
        echo json_encode([
            "success" => false,
            "message" => $errorMessage,
            "debug" => $debugInfo
        ], JSON_PRETTY_PRINT);
    }
} catch (Exception $e) {
    ob_end_clean();
    echo json_encode([
        "success" => false,
        "message" => "Error loading configuration: " . $e->getMessage()
    ]);
}
?>

