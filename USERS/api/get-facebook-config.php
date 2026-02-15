<?php
/**
 * Get Facebook OAuth Configuration
 * Returns Facebook App ID for client-side use
 */

header('Content-Type: application/json');

// Load environment variables
$envFile = __DIR__ . '/../../.env';
$env = [];

if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            list($key, $value) = explode('=', $line, 2);
            $env[trim($key)] = trim($value);
        }
    }
}

$appId = $env['APP_ID'] ?? '';

if (empty($appId)) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Facebook App ID not configured',
        'debug' => 'APP_ID not found in .env file'
    ]);
    exit;
}

// Return only the App ID (keep App Secret server-side only)
echo json_encode([
    'success' => true,
    'app_id' => $appId
]);
