<?php
header('Content-Type: application/json');
require_once '../db_connect.php';

// User ID must be provided to know whose preferences to fetch
if (!isset($_GET['user_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'User ID is required.']);
    exit();
}

$userId = (int)$_GET['user_id'];

try {
    $stmt = $pdo->prepare("SELECT * FROM user_preferences WHERE user_id = ? LIMIT 1");
    $stmt->execute([$userId]);
    $prefs = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($prefs) {
        // Coerce boolean values for JSON consistency
        $prefs['share_location'] = (bool)$prefs['share_location'];
        $prefs['receive_notifications'] = (bool)$prefs['receive_notifications'];
        $prefs['crime_alerts'] = (bool)$prefs['crime_alerts'];
        $prefs['disaster_warnings'] = (bool)$prefs['disaster_warnings'];
        $prefs['fire_alerts'] = (bool)$prefs['fire_alerts'];
        $prefs['weather_advisories'] = (bool)$prefs['weather_advisories'];
        echo json_encode(['success' => true, 'preferences' => $prefs]);
    } else {
        // If no preferences found, return a default set
        $defaults = [
            'user_id' => $userId, 'share_location' => true, 'receive_notifications' => true, 
            'crime_alerts' => true, 'disaster_warnings' => true, 'fire_alerts' => true, 
            'weather_advisories' => true
        ];
        echo json_encode(['success' => true, 'preferences' => $defaults]);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database query failed.']);
}
?>