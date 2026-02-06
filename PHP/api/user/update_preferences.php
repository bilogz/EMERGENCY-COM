<?php
header('Content-Type: application/json');
require_once '../db_connect.php';
/** @var PDO $pdo */

$input = json_decode(file_get_contents('php://input'), true);

$userId = isset($input['user_id']) ? (int)$input['user_id'] : 0;
if ($userId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Valid User ID is required.']);
    exit();
}

// Prepare data with defaults matching the schema
$shareLocation = isset($input['share_location']) ? (int)(bool)$input['share_location'] : 0;
$smsNotifications = isset($input['sms_notifications']) ? (int)(bool)$input['sms_notifications'] : 1;
$emailNotifications = isset($input['email_notifications']) ? (int)(bool)$input['email_notifications'] : 1;
$pushNotifications = isset($input['push_notifications']) ? (int)(bool)$input['push_notifications'] : 1;
$preferredLanguage = isset($input['preferred_language']) ? trim($input['preferred_language']) : 'en';
$alertPriority = isset($input['alert_priority']) ? trim($input['alert_priority']) : 'all';
$theme = isset($input['theme']) ? trim($input['theme']) : 'light';
$timezone = isset($input['timezone']) ? trim($input['timezone']) : 'Asia/Manila';
$profileVisibility = isset($input['profile_visibility']) ? trim($input['profile_visibility']) : 'private';
$alertCategories = isset($input['alert_categories']) ? trim($input['alert_categories']) : null;

try {
    // This query will INSERT a new row if the user_id doesn't exist,
    // or UPDATE the existing row if it does. This is very robust.
    $sql = "
        INSERT INTO user_preferences (user_id, share_location, sms_notifications, email_notifications, push_notifications, preferred_language, alert_priority, theme, timezone, profile_visibility, alert_categories)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            share_location = VALUES(share_location),
            sms_notifications = VALUES(sms_notifications),
            email_notifications = VALUES(email_notifications),
            push_notifications = VALUES(push_notifications),
            preferred_language = VALUES(preferred_language),
            alert_priority = VALUES(alert_priority),
            theme = VALUES(theme),
            timezone = VALUES(timezone),
            profile_visibility = VALUES(profile_visibility),
            alert_categories = VALUES(alert_categories),
            updated_at = CURRENT_TIMESTAMP
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$userId, $shareLocation, $smsNotifications, $emailNotifications, $pushNotifications, $preferredLanguage, $alertPriority, $theme, $timezone, $profileVisibility, $alertCategories]);

    echo json_encode(['success' => true, 'message' => 'Preferences updated successfully.']);

} catch (PDOException $e) {
    http_response_code(500);
    error_log("Update Prefs Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database update failed.']);
}
?>
