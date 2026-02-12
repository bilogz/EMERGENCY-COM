<?php
/**
 * Header Notifications API
 * Returns active alerts count and recent system notifications.
 */

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate, max-age=0');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

try {
    require_once __DIR__ . '/db_connect.php';
    if (!isset($pdo) || $pdo === null) {
        throw new Exception('Database connection failed');
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database connection failed'
    ]);
    exit();
}

function table_exists(PDO $pdo, string $table): bool {
    try {
        $stmt = $pdo->prepare("SHOW TABLES LIKE ?");
        $stmt->execute([$table]);
        return $stmt->rowCount() > 0;
    } catch (Throwable $e) {
        return false;
    }
}

$sinceParam = trim((string)($_GET['since'] ?? ''));
$sinceTs = null;
if ($sinceParam !== '') {
    if (ctype_digit($sinceParam)) {
        $raw = (int)$sinceParam;
        $sinceTs = ($raw > 1000000000000) ? (int)floor($raw / 1000) : $raw; // ms -> sec
    } else {
        $parsed = strtotime($sinceParam);
        if ($parsed !== false) {
            $sinceTs = $parsed;
        }
    }
}

$activeAlerts = 0;
$systemNotifications = [];
$systemUnread = 0;

try {
    if (table_exists($pdo, 'alerts')) {
        $activeAlerts = (int)$pdo->query("SELECT COUNT(*) FROM alerts WHERE status = 'active'")->fetchColumn();
    }
} catch (Throwable $e) {
    $activeAlerts = 0;
}

try {
    if (table_exists($pdo, 'notification_logs')) {
        $stmt = $pdo->prepare("
            SELECT id, channel, message, status, sent_at
            FROM notification_logs
            ORDER BY sent_at DESC
            LIMIT 10
        ");
        $stmt->execute();
        $systemNotifications = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        if ($sinceTs !== null) {
            $sinceDate = date('Y-m-d H:i:s', $sinceTs);
            $countStmt = $pdo->prepare("SELECT COUNT(*) FROM notification_logs WHERE sent_at > ?");
            $countStmt->execute([$sinceDate]);
            $systemUnread = (int)$countStmt->fetchColumn();
        } else {
            $systemUnread = (int)count($systemNotifications);
        }
    }
} catch (Throwable $e) {
    $systemNotifications = [];
    $systemUnread = 0;
}

echo json_encode([
    'success' => true,
    'active_alerts' => $activeAlerts,
    'system_unread' => $systemUnread,
    'system_notifications' => $systemNotifications,
    'server_time' => date('c')
]);
