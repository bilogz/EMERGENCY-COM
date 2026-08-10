<?php
header('Content-Type: application/json; charset=utf-8');

require_once 'db_connect.php';
require_once dirname(__DIR__, 2) . '/PHP/api/device_registry.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['admin_logged_in'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access denied.']);
    exit;
}

try {
    $table = ensureAppNotificationDevicesTable($pdo);
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
    if ($limit < 1) $limit = 20;
    if ($limit > 100) $limit = 100;

    $stmt = $pdo->prepare("SELECT id, user_id, device_id, device_type, device_name,
            LEFT(fcm_token, 40) AS fcm_preview,
            CASE WHEN fcm_token IS NOT NULL AND fcm_token <> '' THEN 1 ELSE 0 END AS has_fcm_token,
            notification_permission, is_active, last_active
        FROM {$table}
        ORDER BY last_active DESC
        LIMIT ?");
    $stmt->bindValue(1, $limit, PDO::PARAM_INT);
    $stmt->execute();

    echo json_encode([
        'success' => true,
        'devices' => $stmt->fetchAll(PDO::FETCH_ASSOC)
    ]);
} catch (Throwable $e) {
    error_log('FCM devices lookup failed: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Unable to load FCM devices.']);
}
