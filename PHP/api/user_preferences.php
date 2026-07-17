<?php
header('Content-Type: application/json; charset=utf-8');

require_once 'db_connect.php';

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
        $language = $data['language'] ?? null;
        $alert_crimes = $data['alert_crimes'] ?? true;
        $alert_emergencies = $data['alert_emergencies'] ?? true;
        $alert_community = $data['alert_community'] ?? true;
        $notification_email = $data['notification_email'] ?? false;
        $notification_sms = $data['notification_sms'] ?? false;

        // Validate required fields
        if (!$user_id || !$language) {
            apiResponse::error("Missing required fields: user_id, language", 400);
        }

        // Validate language
        $validLanguages = ['en', 'tl', 'ceb', 'war', 'hil', 'es', 'fr'];
        if (!in_array($language, $validLanguages)) {
            apiResponse::error("Invalid language. Must be one of: " . implode(', ', $validLanguages), 400);
        }

        // Convert boolean strings to actual booleans
        $alert_crimes = filter_var($alert_crimes, FILTER_VALIDATE_BOOLEAN);
        $alert_emergencies = filter_var($alert_emergencies, FILTER_VALIDATE_BOOLEAN);
        $alert_community = filter_var($alert_community, FILTER_VALIDATE_BOOLEAN);
        $notification_email = filter_var($notification_email, FILTER_VALIDATE_BOOLEAN);
        $notification_sms = filter_var($notification_sms, FILTER_VALIDATE_BOOLEAN);

        $query = "
            INSERT INTO user_preferences
            (user_id, language, alert_crimes, alert_emergencies, alert_community, notification_email, notification_sms, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE
                language = VALUES(language),
                alert_crimes = VALUES(alert_crimes),
                alert_emergencies = VALUES(alert_emergencies),
                alert_community = VALUES(alert_community),
                notification_email = VALUES(notification_email),
                notification_sms = VALUES(notification_sms),
                updated_at = NOW()
        ";

        $stmt = $pdo->prepare($query);
        $stmt->execute([
            $user_id,
            $language,
            $alert_crimes ? 1 : 0,
            $alert_emergencies ? 1 : 0,
            $alert_community ? 1 : 0,
            $notification_email ? 1 : 0,
            $notification_sms ? 1 : 0
        ]);

        apiResponse::success([
            'user_id' => $user_id,
            'language' => $language,
            'alert_crimes' => $alert_crimes,
            'alert_emergencies' => $alert_emergencies,
            'alert_community' => $alert_community,
            'notification_email' => $notification_email,
            'notification_sms' => $notification_sms
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
                language,
                alert_crimes,
                alert_emergencies,
                alert_community,
                notification_email,
                notification_sms,
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
        $preference['alert_crimes'] = (bool)$preference['alert_crimes'];
        $preference['alert_emergencies'] = (bool)$preference['alert_emergencies'];
        $preference['alert_community'] = (bool)$preference['alert_community'];
        $preference['notification_email'] = (bool)$preference['notification_email'];
        $preference['notification_sms'] = (bool)$preference['notification_sms'];

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