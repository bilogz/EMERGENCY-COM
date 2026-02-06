<?php
header('Content-Type: application/json');
require_once '../db_connect.php';
/** @var PDO $pdo */

if (!isset($_GET['user_id'])) {
    apiResponse::error('User ID is required.', 400);
}

$userId = (int)$_GET['user_id'];

try {
    $stmt = $pdo->prepare("SELECT * FROM user_preferences WHERE user_id = ? LIMIT 1");
    $stmt->execute([$userId]);
    $prefs = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($prefs) {
        $prefs['share_location'] = (bool)$prefs['share_location'];
        $prefs['sms_notifications'] = (bool)$prefs['sms_notifications'];
        $prefs['email_notifications'] = (bool)$prefs['email_notifications'];
        $prefs['push_notifications'] = (bool)$prefs['push_notifications'];
        apiResponse::success(['preferences' => $prefs]);
    } else {
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
        apiResponse::success(['preferences' => $defaults]);
    }
} catch (PDOException $e) {
    error_log("Get Preferences DB Error: " . $e->getMessage());
    apiResponse::error('Database query failed.', 500, $e->getMessage());
} catch (Exception $e) {
    error_log("Get Preferences Error: " . $e->getMessage());
    apiResponse::error('An unexpected error occurred.', 500, $e->getMessage());
}
?>
