<?php
/**
 * Setup PAGASA API Key
 * This script stores the OpenWeather API key as PAGASA's API key in the database
 */

header('Content-Type: application/json; charset=utf-8');
require_once 'db_connect.php';
require_once 'config.env.php';

if ($pdo !== null) {
    try {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS integration_settings (
                source VARCHAR(64) NOT NULL PRIMARY KEY,
                enabled TINYINT(1) NOT NULL DEFAULT 0,
                api_key VARCHAR(255) DEFAULT NULL,
                api_url VARCHAR(255) DEFAULT NULL,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    } catch (PDOException $e) {
        error_log("Ensure integration_settings table error: " . $e->getMessage());
    }
}

// Read OpenWeather key from request or secure config.
$pagasaApiKey = trim((string)($_POST['api_key'] ?? $_GET['api_key'] ?? ''));
if ($pagasaApiKey === '' && function_exists('getSecureConfig')) {
    $pagasaApiKey = trim((string)getSecureConfig('OPENWEATHER_API_KEY', ''));
}
if ($pagasaApiKey === '' && function_exists('getSecureConfig')) {
    $pagasaApiKey = trim((string)getSecureConfig('PAGASA_API_KEY', ''));
}
if ($pagasaApiKey === '' && function_exists('getSecureConfig')) {
    $pagasaApiKey = trim((string)getSecureConfig('WEATHER_API_KEY', ''));
}

if ($pagasaApiKey === '' || $pagasaApiKey === 'YOUR_OPENWEATHER_API_KEY' || $pagasaApiKey === 'f35609a701ba47952fba4fd4604c12c7') {
    echo json_encode([
        'success' => false,
        'message' => 'OpenWeather API key not configured. Set OPENWEATHER_API_KEY in config.local.php/.env or pass api_key.'
    ]);
    exit();
}

try {
    // Insert or update the PAGASA API key
    $stmt = $pdo->prepare("
        INSERT INTO integration_settings (source, enabled, api_key, api_url, updated_at)
        VALUES ('pagasa', 0, ?, 'https://api.openweathermap.org/data/2.5/', NOW())
        ON DUPLICATE KEY UPDATE api_key = ?, api_url = 'https://api.openweathermap.org/data/2.5/', updated_at = NOW()
    ");
    $stmt->execute([$pagasaApiKey, $pagasaApiKey]);
    
    echo json_encode([
        'success' => true,
        'message' => 'PAGASA API key stored successfully.'
    ]);
} catch (PDOException $e) {
    error_log("Setup PAGASA Key Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Database error occurred: ' . $e->getMessage()
    ]);
}
?>




















