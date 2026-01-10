<?php
header('Content-Type: application/json');
require_once '../db_connect.php';

$input = json_decode(file_get_contents('php://input'), true);

$userId = isset($input['user_id']) ? (int)$input['user_id'] : 0;
if ($userId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Valid User ID is required.']);
    exit();
}

// Prepare data with defaults
$shareLocation = isset($input['share_location']) ? (int)(bool)$input['share_location'] : 1;
$receiveNotifications = isset($input['receive_notifications']) ? (int)(bool)$input['receive_notifications'] : 1;
$crimeAlerts = isset($input['crime_alerts']) ? (int)(bool)$input['crime_alerts'] : 1;
$disasterWarnings = isset($input['disaster_warnings']) ? (int)(bool)$input['disaster_warnings'] : 1;
$fireAlerts = isset($input['fire_alerts']) ? (int)(bool)$input['fire_alerts'] : 1;
$weatherAdvisories = isset($input['weather_advisories']) ? (int)(bool)$input['weather_advisories'] : 1;

try {
    // This query will INSERT a new row if the user_id doesn't exist,
    // or UPDATE the existing row if it does. This is very robust.
    $sql = "
        INSERT INTO user_preferences (user_id, share_location, receive_notifications, crime_alerts, disaster_warnings, fire_alerts, weather_advisories)
        VALUES (?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            share_location = VALUES(share_location),
            receive_notifications = VALUES(receive_notifications),
            crime_alerts = VALUES(crime_alerts),
            disaster_warnings = VALUES(disaster_warnings),
            fire_alerts = VALUES(fire_alerts),
            weather_advisories = VALUES(weather_advisories)
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$userId, $shareLocation, $receiveNotifications, $crimeAlerts, $disasterWarnings, $fireAlerts, $weatherAdvisories]);

    echo json_encode(['success' => true, 'message' => 'Preferences updated successfully.']);

} catch (PDOException $e) {
    http_response_code(500);
    error_log("Update Prefs Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database update failed.']);
}
?>
