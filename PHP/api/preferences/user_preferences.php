<?php
header('Content-Type: application/json; charset=utf-8');

require_once '../shared/db_connect.php';

/** @var PDO $pdo */

// Handle different HTTP methods
$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'POST') {
        // Save user preferences
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);

        if (!is_array($data)) {
            apiResponse::error("Invalid JSON input.", 400);
        }

        $user_id = $data['user_id'] ?? null;
        $preferred_language = $data['preferred_language'] ?? 'en';
        $sms_notifications = $data['sms_notifications'] ?? true;
        $email_notifications = $data['email_notifications'] ?? true;
        $push_notifications = $data['push_notifications'] ?? true;
        $alert_categories = $data['alert_categories'] ?? null;

        // Validate required fields
        if (!$user_id) {
            apiResponse::error("Missing required fields: user_id", 400);
        }

        // Validate language
        $validLanguages = ['en', 'tl', 'ceb', 'war', 'hil', 'es', 'fr'];
        if (!in_array($preferred_language, $validLanguages)) {
            apiResponse::error("Invalid preferred_language. Must be one of: " . implode(', ', $validLanguages), 400);
        }

        // Convert boolean strings to actual booleans
        $sms_notifications = filter_var($sms_notifications, FILTER_VALIDATE_BOOLEAN);
        $email_notifications = filter_var($email_notifications, FILTER_VALIDATE_BOOLEAN);
        $push_notifications = filter_var($push_notifications, FILTER_VALIDATE_BOOLEAN);

        $query = "
            INSERT INTO user_preferences
            (user_id, preferred_language, sms_notifications, email_notifications, push_notifications, alert_categories, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE
                preferred_language = VALUES(preferred_language),
                sms_notifications = VALUES(sms_notifications),
                email_notifications = VALUES(email_notifications),
                push_notifications = VALUES(push_notifications),
                alert_categories = VALUES(alert_categories),
                updated_at = NOW()
        ";

        $stmt = $pdo->prepare($query);
        $stmt->execute([
            $user_id,
            $preferred_language,
            $sms_notifications ? 1 : 0,
            $email_notifications ? 1 : 0,
            $push_notifications ? 1 : 0,
            $alert_categories
        ]);

        apiResponse::success([
            'user_id' => $user_id,
            'preferred_language' => $preferred_language,
            'sms_notifications' => $sms_notifications,
            'email_notifications' => $email_notifications,
            'push_notifications' => $push_notifications,
            'alert_categories' => $alert_categories
        ], "User preferences saved successfully");

    } elseif ($method === 'GET') {
        // Get user preferences
        $userId = isset($_GET['user_id']) ? $_GET['user_id'] : null;

        if (!$userId) {
            apiResponse::error("Missing required parameter: user_id", 400);
        }

        $query = "
            SELECT
                user_id,
                preferred_language,
                sms_notifications,
                email_notifications,
                push_notifications,
                alert_categories,
                updated_at
            FROM user_preferences
            WHERE user_id = :user_id
        ";

        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        $preference = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$preference) {
            apiResponse::error("User preferences not found.", 404);
        }

        // Convert database integers to booleans
        $preference['sms_notifications'] = (bool)$preference['sms_notifications'];
        $preference['email_notifications'] = (bool)$preference['email_notifications'];
        $preference['push_notifications'] = (bool)$preference['push_notifications'];

        apiResponse::success($preference, "User preferences fetched successfully");

    } else {
        apiResponse::error("Invalid request method. Use GET or POST.", 405);
    }

} catch (PDOException $e) {
    error_log("User Preferences DB Error: " . $e->getMessage());
    apiResponse::error("Database error occurred.", 500);
} catch (Exception $e) {
    error_log("User Preferences Error: " . $e->getMessage());
    apiResponse::error("An unexpected error occurred.", 500);
}