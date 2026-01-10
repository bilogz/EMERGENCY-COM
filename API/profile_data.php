<?php
// profile_data.php
// Fetches a complete user profile including related data from other tables.
header('Content-Type: application/json');

require_once 'db_connect.php';

// Use a GET request to fetch data. It's more conventional for read operations.
// Example URL: /profile_data.php?user_id=1
if (!isset($_GET['user_id'])) {
    http_response_code(400); // Bad Request
    echo json_encode(["success" => false, "message" => "User ID is required."]);
    exit();
}

$userId = $_GET['user_id'];

// --- Main function to fetch all profile data ---
function getFullUserProfile($pdo, $userId) {
    $profile = [];

    // 1. Fetch core user data from the `users` table
    $stmt = $pdo->prepare("SELECT id, name, email, phone, user_type, created_at FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $profile['user'] = $stmt->fetch(PDO::FETCH_ASSOC);

    // If user doesn't exist, we can stop here.
    if (!$profile['user']) {
        return null;
    }

    // 2. Fetch user preferences (one-to-one relationship)
    // Here, we use the user_id to find the matching row in `user_preferences`.
    $stmt = $pdo->prepare("SELECT theme, preferred_language, sms_notifications, email_notifications FROM user_preferences WHERE user_id = ?");
    $stmt->execute([$userId]);
    $profile['preferences'] = $stmt->fetch(PDO::FETCH_ASSOC);

    // 3. Fetch user devices (one-to-many relationship)
    // Here, we get all devices linked to this user_id.
    $stmt = $pdo->prepare("SELECT device_type, device_name, is_active FROM user_devices WHERE user_id = ?");
    $stmt->execute([$userId]);
    $profile['devices'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 4. Fetch emergency contacts (one-to-many relationship)
    // And here, we get all contacts linked to this user_id.
    $stmt = $pdo->prepare("SELECT contact_name, contact_phone, relationship, is_primary FROM emergency_contacts WHERE user_id = ?");
    $stmt->execute([$userId]);
    $profile['emergency_contacts'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    return $profile;
}

try {
    $fullProfile = getFullUserProfile($pdo, $userId);

    if ($fullProfile) {
        http_response_code(200); // OK
        echo json_encode([
            "success" => true,
            "message" => "Profile data fetched successfully.",
            "data" => $fullProfile
        ]);
    } else {
        http_response_code(404); // Not Found
        echo json_encode(["success" => false, "message" => "User not found."]);
    }

} catch (PDOException $e) {
    http_response_code(500); // Internal Server Error
    // Log the detailed error for the developer, but don't show it to the public.
    error_log("Profile data fetch error for user_id {$userId}: " . $e->getMessage());
    echo json_encode(["success" => false, "message" => "A server error occurred while fetching profile data."]);
}
?>