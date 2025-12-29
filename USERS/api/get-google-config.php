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
    $config = @require $configFile;

    // Check if config is an array
    if (!is_array($config)) {
        ob_end_clean();
        echo json_encode([
            "success" => false,
            "message" => "Config file did not return an array.",
            "debug" => [
                "config_type" => gettype($config)
            ]
        ]);
        exit();
    }

    $clientId = isset($config['GOOGLE_CLIENT_ID']) ? $config['GOOGLE_CLIENT_ID'] : null;

    // Clean any output buffer
    ob_end_clean();

    if ($clientId && !empty($clientId)) {
        echo json_encode([
            "success" => true,
            "client_id" => $clientId
        ]);
    } else {
        echo json_encode([
            "success" => false,
            "message" => "Google OAuth is not configured.",
            "debug" => [
                "config_keys" => array_keys($config),
                "has_google_client_id" => isset($config['GOOGLE_CLIENT_ID']),
                "google_client_id_empty" => empty($config['GOOGLE_CLIENT_ID'] ?? null)
            ]
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

