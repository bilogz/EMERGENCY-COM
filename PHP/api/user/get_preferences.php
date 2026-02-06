<?php
header('Content-Type: application/json');
require_once '../db_connect.php';
/** @var PDO $pdo */

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
        $prefs['sms_notifications'] = (bool)$prefs['sms_notifications'];
        $prefs['email_notifications'] = (bool)$prefs['email_notifications'];
        $prefs['push_notifications'] = (bool)$prefs['push_notifications'];
        echo json_encode(['success' => true, 'preferences' => $prefs]);
    } else {
        // If no preferences found, return a default set matching the schema
        $defaults = [
            'user_id' => $userId, 
            'share_location' => false, 
            'sms_notifications' => true,
            'email_notifications' => true,
            'push_notifications' => true,
            'preferred_language' => 'en',
            'alert_priority' => 'all',
            'theme' => 'light',
            'timezone' => 'Asia/Manila',
            'profile_visibility' => 'private'
        ];
        echo json_encode(['success' => true, 'preferences' => $defaults]);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database query failed.']);
}
?>