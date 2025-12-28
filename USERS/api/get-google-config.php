<?php
/**
 * Get Google OAuth Configuration (Client ID only)
 * Returns only the client ID (safe to expose to frontend)
 */

header('Content-Type: application/json');

$configFile = __DIR__ . '/config.local.php';
$config = file_exists($configFile) ? require $configFile : [];

$clientId = $config['GOOGLE_CLIENT_ID'] ?? null;

if ($clientId) {
    echo json_encode([
        "success" => true,
        "client_id" => $clientId
    ]);
} else {
    echo json_encode([
        "success" => false,
        "message" => "Google OAuth is not configured."
    ]);
}
?>

