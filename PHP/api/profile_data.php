<?php
// profile_data.php
header('Content-Type: application/json');

require_once 'db_connect.php';
/** @var PDO $pdo */

if (!isset($_GET['user_id'])) {
    apiResponse::error("User ID is required.", 400);
}

$userId = $_GET['user_id'];

function getFullUserProfile($pdo, $userId) {
    $profile = [];

    $stmt = $pdo->prepare("SELECT id, name, email, phone, user_type, created_at FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $profile['user'] = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$profile['user']) {
        return null;
    }

    $stmt = $pdo->prepare("SELECT theme, preferred_language, sms_notifications, email_notifications FROM user_preferences WHERE user_id = ?");
    $stmt->execute([$userId]);
    $profile['preferences'] = $stmt->fetch(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare("SELECT device_type, device_name, is_active FROM user_devices WHERE user_id = ?");
    $stmt->execute([$userId]);
    $profile['devices'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare("SELECT contact_name, contact_phone, relationship, is_primary FROM emergency_contacts WHERE user_id = ?");
    $stmt->execute([$userId]);
    $profile['emergency_contacts'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    return $profile;
}

try {
    $fullProfile = getFullUserProfile($pdo, $userId);

    if ($fullProfile) {
        apiResponse::success(['data' => $fullProfile], "Profile data fetched successfully.");
    } else {
        apiResponse::error("User not found.", 404);
    }

} catch (PDOException $e) {
    error_log("Profile data fetch error for user_id {$userId}: " . $e->getMessage());
    apiResponse::error("A server error occurred while fetching profile data.", 500, $e->getMessage());
} catch (Exception $e) {
    error_log("Profile data error: " . $e->getMessage());
    apiResponse::error("An unexpected error occurred.", 500, $e->getMessage());
}
?>
