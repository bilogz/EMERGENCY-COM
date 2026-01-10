<?php
/**
 * Setup PAGASA API Key
 * This script stores the OpenWeather API key as PAGASA's API key in the database
 */

header('Content-Type: application/json; charset=utf-8');
require_once 'db_connect.php';

// The OpenWeather API key (labeled as PAGASA)
$pagasaApiKey = 'f35609a701ba47952fba4fd4604c12c7';

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




















