<?php
/**
 * Get Google OAuth Configuration (Client ID only)
 * Returns only the client ID (safe to expose to frontend)
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$configFile = __DIR__ . '/config.local.php';

try {
    // Check if config file exists
    if (!file_exists($configFile)) {
        echo json_encode([
            "success" => false,
            "message" => "Config file not found.",
            "debug" => [
                "config_file_path" => $configFile,
                "file_exists" => false
            ]
        ]);
        exit();
    }

    // Load config file
    $config = require $configFile;
    
    // Verify it's an array
    if (!is_array($config)) {
        echo json_encode([
            "success" => false,
            "message" => "Config file did not return an array.",
            "debug" => [
                "config_type" => gettype($config)
            ]
        ]);
        exit();
    }

    // Get the Google Client ID
    $clientId = isset($config['GOOGLE_CLIENT_ID']) ? trim($config['GOOGLE_CLIENT_ID']) : null;

    if (!empty($clientId)) {
        echo json_encode([
            "success" => true,
            "client_id" => $clientId
        ]);
    } else {
        echo json_encode([
            "success" => false,
            "message" => "Google OAuth is not configured. GOOGLE_CLIENT_ID not found or empty.",
            "debug" => [
                "config_keys" => array_keys($config),
                "has_google_client_id" => isset($config['GOOGLE_CLIENT_ID'])
            ]
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => "Error loading configuration: " . $e->getMessage()
    ]);
}
