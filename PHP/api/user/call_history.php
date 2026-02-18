<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../db_connect.php';
/** @var PDO $pdo */

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') {
    apiResponse::error('Invalid request method. Use GET.', 405);
}

$userId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
if ($userId <= 0) {
    apiResponse::error('Valid user_id is required.', 400);
}

$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
$offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;

if ($limit <= 0) {
    $limit = 50;
}
if ($limit > 200) {
    $limit = 200;
}
if ($offset < 0) {
    $offset = 0;
}

try {
    $stmt = $pdo->prepare("
        SELECT
            id,
            call_id,
            user_id,
            role,
            event,
            timestamp,
            duration_sec,
            location_data,
            room,
            metadata,
            ip_address,
            user_agent,
            created_at
        FROM call_logs
        WHERE user_id = :user_id
        ORDER BY timestamp DESC, id DESC
        LIMIT :limit OFFSET :offset
    ");
    $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rows as &$row) {
        if (isset($row['location_data']) && is_string($row['location_data'])) {
            $decoded = json_decode($row['location_data'], true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $row['location_data'] = $decoded;
            }
        }

        if (isset($row['metadata']) && is_string($row['metadata'])) {
            $decoded = json_decode($row['metadata'], true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $row['metadata'] = $decoded;
            }
        }
    }
    unset($row);

    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM call_logs WHERE user_id = :user_id");
    $countStmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
    $countStmt->execute();
    $total = (int)$countStmt->fetchColumn();

    apiResponse::success([
        'call_history' => $rows,
        'pagination' => [
            'limit' => $limit,
            'offset' => $offset,
            'count' => count($rows),
            'total' => $total
        ]
    ], 'Call history fetched successfully.');

} catch (PDOException $e) {
    error_log('Call History DB Error: ' . $e->getMessage());
    apiResponse::error('Database query failed.', 500, $e->getMessage());
} catch (Exception $e) {
    error_log('Call History Error: ' . $e->getMessage());
    apiResponse::error('An unexpected error occurred.', 500, $e->getMessage());
}
?>
