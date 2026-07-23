<?php
/**
 * Acknowledge Alert Endpoint
 * Marks an alert as seen by the user
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../ADMIN/api/db_connect.php';

session_start();

function resolveAcknowledgeMarksTable(PDO $pdo): string {
    foreach (['user_alert_marks', 'user_alert_marks_runtime'] as $table) {
        try {
            $pdo->query("SELECT 1 FROM {$table} LIMIT 1");
            return $table;
        } catch (Throwable $e) {}
    }
    $pdo->exec("CREATE TABLE IF NOT EXISTS user_alert_marks_runtime (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id BIGINT UNSIGNED NOT NULL,
        alert_id BIGINT UNSIGNED NOT NULL,
        acknowledged_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uniq_user_alert (user_id, alert_id),
        INDEX idx_alert_id (alert_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    return 'user_alert_marks_runtime';
}

$userId = $_SESSION['user_id'] ?? $_SESSION['admin_user_id'] ?? null;
$alertId = $_POST['alert_id'] ?? null;

if (!$userId || !$alertId) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

try {
    $marksTable = resolveAcknowledgeMarksTable($pdo);
    $stmt = $pdo->prepare("INSERT IGNORE INTO {$marksTable} (user_id, alert_id) VALUES (?, ?)");
    $stmt->execute([$userId, $alertId]);
    
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
