<?php
/**
 * Push Monitoring Status API
 * Receives current weather/seismic monitoring details and logs them as a dispatch to response teams.
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

session_start();
// Check if user is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/activity_logger.php';

$input = json_decode(file_get_contents('php://input'), true);
if (!$input || !is_array($input)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid JSON input.']);
    exit();
}

$source = trim((string)($input['source'] ?? ''));
$type = trim((string)($input['type'] ?? ''));
$data = $input['data'] ?? null;

if (empty($source) || empty($type)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing source or type parameters.']);
    exit();
}

$adminId = $_SESSION['admin_user_id'] ?? 0;
$adminUsername = $_SESSION['admin_username'] ?? 'Admin';

try {
    // 1. Ensure the monitoring_dispatches table exists
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS monitoring_dispatches (
            id INT AUTO_INCREMENT PRIMARY KEY,
            admin_id INT NOT NULL,
            source VARCHAR(100) NOT NULL,
            type VARCHAR(100) NOT NULL,
            payload LONGTEXT NOT NULL,
            pushed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_admin_id (admin_id),
            INDEX idx_source (source),
            INDEX idx_pushed_at (pushed_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    // 2. Insert dispatch record
    $payloadText = json_encode($data ? $data : ['source' => $source, 'type' => $type, 'timestamp' => time()]);
    $stmt = $pdo->prepare("
        INSERT INTO monitoring_dispatches (admin_id, source, type, payload, pushed_at)
        VALUES (?, ?, ?, ?, NOW())
    ");
    $stmt->execute([$adminId, $source, $type, $payloadText]);
    $dispatchId = $pdo->lastInsertId();

    // 3. Log administrative activity
    $logDescription = "Pushed monitoring status to Response Team: Source={$source}, Type={$type} (Dispatch #{$dispatchId})";
    logAdminActivity($adminId, 'push_monitoring_status', $logDescription, [
        'dispatch_id' => $dispatchId,
        'source' => $source,
        'type' => $type
    ]);

    // 4. Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Monitoring status successfully pushed to the Emergency Response Team.',
        'dispatch_id' => $dispatchId,
        'timestamp' => date('Y-m-d H:i:s')
    ]);

} catch (PDOException $e) {
    error_log("Push monitoring status database error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    error_log("Push monitoring status general error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}
