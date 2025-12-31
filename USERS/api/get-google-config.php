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

// Try .env file first
$envFile = __DIR__ . '/.env';
$googleClientId = null;

if (file_exists($envFile)) {
    $envContent = file_get_contents($envFile);
    $lines = explode("\n", $envContent);
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line) || strpos($line, '#') === 0) {
            continue;
        }
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            $value = trim($value, '"\'');
            if ($key === 'GOOGLE_CLIENT_ID') {
                $googleClientId = $value;
                break;
            }
        }
    }
}

$configFile = __DIR__ . '/config.local.php';

try {
    // If not found in .env, try config.local.php
    if (empty($googleClientId) && file_exists($configFile)) {
        $config = require $configFile;
        if (is_array($config)) {
            $googleClientId = isset($config['GOOGLE_CLIENT_ID']) ? trim($config['GOOGLE_CLIENT_ID']) : null;
        }
    }

    if (!empty($googleClientId)) {
        echo json_encode([
            "success" => true,
            "client_id" => $googleClientId
        ]);
    } else {
        echo json_encode([
            "success" => false,
            "message" => "Google OAuth is not configured. GOOGLE_CLIENT_ID not found or empty.",
            "debug" => [
                "env_file_exists" => file_exists($envFile),
                "config_file_exists" => file_exists($configFile),
                "env_file_path" => $envFile,
                "config_file_path" => $configFile
            ]
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => "Error loading configuration: " . $e->getMessage(),
        "debug" => [
            "exception" => $e->getMessage(),
            "file" => $e->getFile(),
            "line" => $e->getLine()
        ]
    ]);
}
