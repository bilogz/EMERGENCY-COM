<?php
/**
 * Unified audit trail API.
 *
 * Normalizes notification, handover, deletion, and ERS transfer logs into one
 * paginated response so the admin audit page only needs one table.
 */

header('Content-Type: application/json; charset=utf-8');
require_once 'db_connect.php';

const AUDIT_TRAIL_TYPES = [
    'notifications',
    'handovers',
    'deletions',
    'ers_transfers',
];

function auditRespond(array $payload, int $statusCode = 200): void
{
    http_response_code($statusCode);
    echo json_encode($payload, JSON_INVALID_UTF8_SUBSTITUTE | JSON_UNESCAPED_SLASHES);
    exit;
}

function auditTableExists(PDO $pdo, string $tableName): bool
{
    static $cache = [];
    if (array_key_exists($tableName, $cache)) {
        return $cache[$tableName];
    }

    try {
        $stmt = $pdo->prepare(
            'SELECT COUNT(*) FROM information_schema.tables
             WHERE table_schema = DATABASE() AND table_name = ?'
        );
        $stmt->execute([$tableName]);
        $cache[$tableName] = ((int) $stmt->fetchColumn()) > 0;
    } catch (Throwable $e) {
        error_log('Audit table check failed for ' . $tableName . ': ' . $e->getMessage());
        $cache[$tableName] = false;
    }

    return $cache[$tableName];
}

function auditNotificationTable(PDO $pdo): ?string
{
    foreach (['notification_logs', 'notification_logs_runtime'] as $tableName) {
        if (auditTableExists($pdo, $tableName)) {
            return $tableName;
        }
    }

    return null;
}

function auditSource(PDO $pdo, string $trailType): ?array
{
    if ($trailType === 'notifications') {
        $tableName = auditNotificationTable($pdo);
        return $tableName === null ? null : [
            'table' => $tableName,
            'timestamp' => 'sent_at',
            'status' => "LOWER(COALESCE(status, ''))",
            'success' => "LOWER(COALESCE(status, '')) IN ('success', 'sent', 'completed', 'delivered')",
            'failed' => "LOWER(COALESCE(status, '')) IN ('failed', 'error', 'rejected')",
        ];
    }

    if ($trailType === 'handovers') {
        return auditTableExists($pdo, 'twc_assignment_audit') ? [
            'table' => 'twc_assignment_audit',
            'timestamp' => 'created_at',
            'status' => "LOWER(COALESCE(action, ''))",
            'success' => '1 = 1',
            'failed' => '1 = 0',
        ] : null;
    }

    if ($trailType === 'deletions') {
        return auditTableExists($pdo, 'twc_conversation_deletion_audit') ? [
            'table' => 'twc_conversation_deletion_audit',
            'timestamp' => 'created_at',
            'status' => "LOWER(COALESCE(action, ''))",
            'success' => '1 = 1',
            'failed' => '1 = 0',
        ] : null;
    }

    if ($trailType === 'ers_transfers') {
        return auditTableExists($pdo, 'transfer_call_audit') ? [
            'table' => 'transfer_call_audit',
            'timestamp' => 'created_at',
            'status' => "LOWER(COALESCE(NULLIF(response_status, ''), NULLIF(status, ''), 'pending'))",
            'success' => "(LOWER(COALESCE(NULLIF(response_status, ''), NULLIF(status, ''), '')) IN ('success', 'sent', 'completed', 'answered', 'accepted', 'transferred') OR integration_status BETWEEN 200 AND 299)",
            'failed' => "(LOWER(COALESCE(NULLIF(response_status, ''), NULLIF(status, ''), '')) IN ('failed', 'error', 'rejected') OR integration_status >= 400)",
        ] : null;
    }

    return null;
}

function auditBuildWhere(string $trailType, array $source): array
{
    $where = [];
    $params = [];
    $dateFrom = trim((string) ($_GET['date_from'] ?? ''));
    $dateTo = trim((string) ($_GET['date_to'] ?? ''));
    $channel = trim((string) ($_GET['channel'] ?? ''));
    $status = strtolower(trim((string) ($_GET['status'] ?? '')));

    if ($dateFrom !== '') {
        $where[] = $source['timestamp'] . ' >= ?';
        $params[] = $dateFrom . ' 00:00:00';
    }
    if ($dateTo !== '') {
        $where[] = $source['timestamp'] . ' < DATE_ADD(?, INTERVAL 1 DAY)';
        $params[] = $dateTo . ' 00:00:00';
    }
    if ($trailType === 'notifications' && $channel !== '') {
        $where[] = "LOWER(COALESCE(channel, '')) = ?";
        $params[] = strtolower($channel);
    }
    if ($status !== '') {
        $where[] = $source['status'] . ' = ?';
        $params[] = $status;
    }

    return [
        'sql' => $where ? ' WHERE ' . implode(' AND ', $where) : '',
        'params' => $params,
    ];
}

function auditSelectSql(string $trailType, string $tableName): string
{
    if ($trailType === 'notifications') {
        return "SELECT id, sent_at AS timestamp, channel, recipient, message,
                       status, sent_by, ip_address
                FROM `{$tableName}`";
    }

    if ($trailType === 'handovers') {
        return 'SELECT id, conversation_id, action, admin_id, admin_name,
                       previous_admin_id, created_at AS timestamp
                FROM twc_assignment_audit';
    }

    if ($trailType === 'deletions') {
        return 'SELECT id, conversation_id, trash_id, action, item_type,
                       deletion_reason, deletion_details, admin_id, admin_name,
                       item_name, item_location, last_message, ip_address,
                       user_agent, snapshot, created_at AS timestamp
                FROM twc_conversation_deletion_audit';
    }

    return 'SELECT id, call_id, conversation_id, emergency_type, caller_name,
                   caller_phone, caller_address, payload, integration_url,
                   integration_status, integration_response, status,
                   response_status, response_status_note, status_requested_at,
                   status_updated_at, requested_by, created_at AS timestamp
            FROM transfer_call_audit';
}

function auditFriendlyAction(string $value): string
{
    $value = trim(str_replace('_', ' ', $value));
    return $value === '' ? 'Recorded' : ucwords($value);
}

function auditNormalizeRow(string $trailType, array $row): array
{
    $details = $row;
    if ($trailType === 'ers_transfers') {
        if (!empty($details['integration_url'])) {
            $details['integration_url'] = preg_replace(
                '/([?&]api_key=)[^&]+/i',
                '$1[REDACTED]',
                (string) $details['integration_url']
            );
        }
        if (isset($details['payload']) && is_string($details['payload'])) {
            $decodedPayload = json_decode($details['payload'], true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $details['payload'] = $decodedPayload;
            }
        }
    }

    if ($trailType === 'notifications') {
        return [
            'id' => (int) $row['id'],
            'timestamp' => $row['timestamp'] ?? null,
            'trail_type' => $trailType,
            'trail_label' => 'Notification',
            'subject' => $row['recipient'] ?: 'All recipients',
            'activity' => $row['message'] ?: 'Notification sent',
            'status' => strtolower((string) ($row['status'] ?: 'pending')),
            'admin' => $row['sent_by'] ?: 'System',
            'ip_address' => $row['ip_address'] ?: 'N/A',
            'details' => $details,
        ];
    }

    if ($trailType === 'handovers') {
        $action = (string) ($row['action'] ?? 'recorded');
        $previousAdmin = !empty($row['previous_admin_id'])
            ? ' Previous admin ID: ' . $row['previous_admin_id'] . '.'
            : '';
        return [
            'id' => (int) $row['id'],
            'timestamp' => $row['timestamp'] ?? null,
            'trail_type' => $trailType,
            'trail_label' => 'Admin Handover',
            'subject' => 'Conversation #' . ($row['conversation_id'] ?? 'N/A'),
            'activity' => auditFriendlyAction($action) . '.' . $previousAdmin,
            'status' => strtolower($action),
            'admin' => $row['admin_name'] ?: 'Administrator',
            'ip_address' => 'N/A',
            'details' => $details,
        ];
    }

    if ($trailType === 'deletions') {
        $itemType = ($row['item_type'] ?? '') === 'general_enquiry'
            ? 'General Enquiry'
            : 'Report';
        $activityParts = [auditFriendlyAction((string) ($row['action'] ?? 'recorded'))];
        if (!empty($row['deletion_reason'])) {
            $activityParts[] = 'Reason: ' . auditFriendlyAction((string) $row['deletion_reason']);
        }
        if (!empty($row['deletion_details'])) {
            $activityParts[] = (string) $row['deletion_details'];
        }
        return [
            'id' => (int) $row['id'],
            'timestamp' => $row['timestamp'] ?? null,
            'trail_type' => $trailType,
            'trail_label' => 'Deleted Item',
            'subject' => $itemType . ' #' . ($row['conversation_id'] ?? 'N/A') . (!empty($row['item_name']) ? ' - ' . $row['item_name'] : ''),
            'activity' => implode(' | ', $activityParts),
            'status' => strtolower((string) ($row['action'] ?? 'recorded')),
            'admin' => $row['admin_name'] ?: 'Administrator',
            'ip_address' => $row['ip_address'] ?: 'N/A',
            'details' => $details,
        ];
    }

    $status = strtolower((string) ($row['response_status'] ?: ($row['status'] ?: 'pending')));
    $subject = $row['caller_name'] ?: ('Conversation #' . ($row['conversation_id'] ?? 'N/A'));
    $activityParts = [];
    if (!empty($row['emergency_type'])) {
        $activityParts[] = auditFriendlyAction((string) $row['emergency_type']);
    }
    if (!empty($row['caller_address'])) {
        $activityParts[] = (string) $row['caller_address'];
    }
    if (!empty($row['response_status_note'])) {
        $activityParts[] = (string) $row['response_status_note'];
    }

    return [
        'id' => (int) $row['id'],
        'timestamp' => $row['timestamp'] ?? null,
        'trail_type' => $trailType,
        'trail_label' => 'ERS Transfer',
        'subject' => $subject,
        'activity' => $activityParts ? implode(' | ', $activityParts) : 'Report transferred to the Emergency Response System.',
        'status' => $status,
        'admin' => $row['requested_by'] ?: 'System',
        'ip_address' => 'N/A',
        'details' => $details,
    ];
}

function auditBindAndExecute(PDOStatement $stmt, array $params, ?int $limit = null, ?int $offset = null): void
{
    $position = 1;
    foreach ($params as $value) {
        $stmt->bindValue($position++, $value, PDO::PARAM_STR);
    }
    if ($limit !== null) {
        $stmt->bindValue($position++, $limit, PDO::PARAM_INT);
    }
    if ($offset !== null) {
        $stmt->bindValue($position, $offset, PDO::PARAM_INT);
    }
    $stmt->execute();
}

function auditList(PDO $pdo, string $trailType): void
{
    $page = max(1, (int) ($_GET['page'] ?? 1));
    $limit = 10;
    $offset = ($page - 1) * $limit;
    $source = auditSource($pdo, $trailType);

    if ($source === null) {
        auditRespond([
            'success' => true,
            'logs' => [],
            'pagination' => ['page' => $page, 'limit' => $limit, 'total' => 0, 'total_pages' => 0],
        ]);
    }

    $filters = auditBuildWhere($trailType, $source);
    $countStmt = $pdo->prepare('SELECT COUNT(*) FROM `' . $source['table'] . '`' . $filters['sql']);
    auditBindAndExecute($countStmt, $filters['params']);
    $total = (int) $countStmt->fetchColumn();

    $query = auditSelectSql($trailType, $source['table'])
        . $filters['sql']
        . ' ORDER BY ' . $source['timestamp'] . ' DESC, id DESC LIMIT ? OFFSET ?';
    $stmt = $pdo->prepare($query);
    auditBindAndExecute($stmt, $filters['params'], $limit, $offset);
    $rows = array_map(
        static fn(array $row): array => auditNormalizeRow($trailType, $row),
        $stmt->fetchAll(PDO::FETCH_ASSOC)
    );

    auditRespond([
        'success' => true,
        'logs' => $rows,
        'pagination' => [
            'page' => $page,
            'limit' => $limit,
            'total' => $total,
            'total_pages' => $total > 0 ? (int) ceil($total / $limit) : 0,
        ],
    ]);
}

function auditStatistics(PDO $pdo, string $trailType): void
{
    $source = auditSource($pdo, $trailType);
    if ($source === null) {
        auditRespond(['success' => true, 'total' => 0, 'successful' => 0, 'failed' => 0, 'today' => 0]);
    }

    $filters = auditBuildWhere($trailType, $source);
    $query = 'SELECT COUNT(*) AS total,
                     COALESCE(SUM(CASE WHEN ' . $source['success'] . ' THEN 1 ELSE 0 END), 0) AS successful,
                     COALESCE(SUM(CASE WHEN ' . $source['failed'] . ' THEN 1 ELSE 0 END), 0) AS failed,
                     COALESCE(SUM(CASE WHEN ' . $source['timestamp'] . ' >= CURDATE()
                                      AND ' . $source['timestamp'] . ' < DATE_ADD(CURDATE(), INTERVAL 1 DAY)
                                      THEN 1 ELSE 0 END), 0) AS today
              FROM `' . $source['table'] . '`' . $filters['sql'];
    $stmt = $pdo->prepare($query);
    auditBindAndExecute($stmt, $filters['params']);
    $statistics = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

    auditRespond([
        'success' => true,
        'total' => (int) ($statistics['total'] ?? 0),
        'successful' => (int) ($statistics['successful'] ?? 0),
        'failed' => (int) ($statistics['failed'] ?? 0),
        'today' => (int) ($statistics['today'] ?? 0),
    ]);
}

$action = (string) ($_GET['action'] ?? 'list');
$trailType = (string) ($_GET['trail_type'] ?? 'notifications');
if (!in_array($trailType, AUDIT_TRAIL_TYPES, true)) {
    auditRespond(['success' => false, 'message' => 'Invalid audit trail type.'], 422);
}

try {
    if ($action === 'list') {
        auditList($pdo, $trailType);
    }
    if ($action === 'statistics') {
        auditStatistics($pdo, $trailType);
    }
    auditRespond(['success' => false, 'message' => 'Invalid action.'], 400);
} catch (Throwable $e) {
    error_log('Unified Audit Trail Error: ' . $e->getMessage());
    auditRespond(['success' => false, 'message' => 'Unable to load audit records.'], 500);
}
