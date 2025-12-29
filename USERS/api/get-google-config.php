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

    // Load config file
    // Use include which returns the value from the file's return statement
    $config = include $configFile;
    
    // If include returns 1, it means the file was included but didn't return anything
    // If it returns false, there was an error
    if ($config === false || $config === 1) {
        ob_end_clean();
        echo json_encode([
            "success" => false,
            "message" => "Error loading config file.",
            "debug" => [
                "include_returned" => $config,
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
        echo json_encode([
            "success" => false,
            "message" => "Google OAuth is not configured.",
            "debug" => $debugInfo
        ]);
    }
} catch (Exception $e) {
    ob_end_clean();
    echo json_encode([
        "success" => false,
        "message" => "Error loading configuration: " . $e->getMessage()
    ]);
}
?>

