<?php
/**
 * Get Google OAuth Configuration (Client ID only)
 * Returns only the client ID (safe to expose to frontend)
 */

// Suppress any output before JSON
ob_start();

header('Content-Type: application/json');

$configFile = __DIR__ . '/config.local.php';

try {
    // Check if config file exists
    if (!file_exists($configFile)) {
        ob_end_clean();
        echo json_encode([
            "success" => false,
            "message" => "Config file not found.",
            "debug" => [
                "config_file" => $configFile,
                "file_exists" => false
            ]
        ]);
        exit();
    }

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

    // Clean any output buffer before checking
    $output = ob_get_clean();
    if (!empty($output)) {
        // If there was output, log it but continue
        error_log("Output buffer had content: " . $output);
    }

    // Check for GOOGLE_CLIENT_ID in config
    $clientId = null;
    if (isset($config['GOOGLE_CLIENT_ID'])) {
        $clientId = $config['GOOGLE_CLIENT_ID'];
    }

    // Debug information (safe for production - doesn't expose actual values)
    $debugInfo = [
        "config_file_exists" => file_exists($configFile),
        "config_is_array" => is_array($config),
        "config_keys_count" => is_array($config) ? count($config) : 0,
        "has_google_client_id_key" => isset($config['GOOGLE_CLIENT_ID']),
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
        if (!isset($config['GOOGLE_CLIENT_ID'])) {
            $errorMessage .= " GOOGLE_CLIENT_ID key not found in config.";
        } elseif (empty($config['GOOGLE_CLIENT_ID'])) {
            $errorMessage .= " GOOGLE_CLIENT_ID is empty.";
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

