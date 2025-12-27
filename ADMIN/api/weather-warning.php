<?php
/**
 * Weather Warning API
 * Receives automated weather warnings and creates alerts
 */

header('Content-Type: application/json; charset=utf-8');
require_once 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['location']) || !isset($input['warnings'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit();
}

$location = $input['location'];
$lat = $input['lat'] ?? null;
$lon = $input['lon'] ?? null;
$warnings = $input['warnings'];
$timestamp = $input['timestamp'] ?? date('Y-m-d H:i:s');

try {
    // Get the most severe warning
    $primaryWarning = $warnings[0];
    $severity = $primaryWarning['severity'] === 'high' ? 'high' : 'medium';
    $warningType = $primaryWarning['type'];
    $message = $primaryWarning['message'];
    
    // Determine alert category based on warning type
    $categoryMap = [
        'extreme_heat' => 'Weather',
        'heat' => 'Weather',
        'heavy_rain' => 'Weather',
        'rain' => 'Weather',
        'strong_wind' => 'Weather',
        'wind' => 'Weather',
        'thunderstorm' => 'Weather',
        'heat_index' => 'Weather'
    ];
    
    $category = $categoryMap[$warningType] ?? 'Weather';
    
    // Create alert in database
    $stmt = $pdo->prepare("
        INSERT INTO alerts (
            title, 
            message, 
            category, 
            severity, 
            location, 
            latitude, 
            longitude, 
            source, 
            status, 
            created_at
        ) VALUES (
            :title,
            :message,
            :category,
            :severity,
            :location,
            :latitude,
            :longitude,
            :source,
            'active',
            NOW()
        )
    ");
    
    $title = "Weather Alert: " . ucfirst(str_replace('_', ' ', $warningType)) . " in " . $location;
    
    $stmt->execute([
        ':title' => $title,
        ':message' => $message,
        ':category' => $category,
        ':severity' => $severity,
        ':location' => $location,
        ':latitude' => $lat,
        ':longitude' => $lon,
        ':source' => 'Automated Weather Monitoring'
    ]);
    
    $alertId = $pdo->lastInsertId();
    
    // Log the warning
    error_log("Weather Warning Created: {$title} - {$message} (Alert ID: {$alertId})");
    
    // Optionally send notifications to subscribed users
    // This would integrate with your notification system
    
    echo json_encode([
        'success' => true,
        'message' => 'Weather warning created successfully',
        'alert_id' => $alertId,
        'warning' => $primaryWarning
    ]);
    
} catch (PDOException $e) {
    error_log("Weather Warning Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}

