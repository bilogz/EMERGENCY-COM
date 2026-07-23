<?php
/**
 * Header Notifications API
 * Returns bell notifications (system + incident reports) for admin header.
 */

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate, max-age=0');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized'
    ]);
    exit;
}

try {
    require_once __DIR__ . '/db_connect.php';
    require_once __DIR__ . '/chat-logic.php';
    if (!isset($pdo) || !($pdo instanceof PDO)) {
        throw new RuntimeException('Database connection failed');
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database connection failed'
    ]);
    exit;
}

function header_table_exists(PDO $pdo, string $table): bool {
    try {
        $stmt = $pdo->prepare("SHOW TABLES LIKE ?");
        $stmt->execute([$table]);
        return $stmt->rowCount() > 0;
    } catch (Throwable $e) {
        return false;
    }
}

function header_column_exists(PDO $pdo, string $table, string $column): bool {
    try {
        $stmt = $pdo->prepare("
            SELECT COUNT(*)
            FROM information_schema.columns
            WHERE table_schema = DATABASE()
              AND table_name = ?
              AND column_name = ?
        ");
        $stmt->execute([$table, $column]);
        return ((int)$stmt->fetchColumn()) > 0;
    } catch (Throwable $e) {
        return false;
    }
}

function header_parse_since_ts(string $raw): ?int {
    $raw = trim($raw);
    if ($raw === '') {
        return null;
    }
    if (ctype_digit($raw)) {
        $numeric = (int)$raw;
        return ($numeric > 1000000000000) ? (int)floor($numeric / 1000) : $numeric;
    }
    $parsed = strtotime($raw);
    if ($parsed === false) {
        return null;
    }
    return $parsed;
}

function header_format_channel_title(string $channel, string $status, string $type): string {
    if ($type === 'incident') {
        if ($channel === 'chat_risk') {
            return 'High-Risk Incident Report';
        }
        return 'New Incident Report';
    }

    $base = trim($channel) !== ''
        ? ucwords(str_replace('_', ' ', $channel))
        : 'System Notification';
    if ($status !== '') {
        return $base . ' (' . strtoupper($status) . ')';
    }
    return $base;
}

function header_sort_notifications(array $items): array {
    usort($items, static function (array $a, array $b): int {
        $aTs = strtotime((string)($a['sent_at'] ?? '')) ?: 0;
        $bTs = strtotime((string)($b['sent_at'] ?? '')) ?: 0;
        if ($aTs === $bTs) {
            $aId = (string)($a['id'] ?? '');
            $bId = (string)($b['id'] ?? '');
            return strcmp($bId, $aId);
        }
        return $bTs <=> $aTs;
    });
    return $items;
}

$sinceTs = header_parse_since_ts((string)($_GET['since'] ?? ''));
$sinceDate = $sinceTs !== null ? date('Y-m-d H:i:s', $sinceTs) : null;

$activeAlerts = 0;
$systemUnread = 0;
$incidentUnread = 0;
$messageUnread = 0;
$systemNotifications = [];
$incidentNotifications = [];

try {
    if (header_table_exists($pdo, 'alerts') && header_column_exists($pdo, 'alerts', 'status')) {
        $activeAlerts = (int)$pdo->query("SELECT COUNT(*) FROM alerts WHERE status = 'active'")->fetchColumn();
    }
} catch (Throwable $e) {
    $activeAlerts = 0;
}

// Pull from notification logs (with runtime table fallback when available).
try {
    $logsTable = null;
    if (function_exists('twc_notification_logs_table')) {
        $logsTable = twc_notification_logs_table($pdo);
    }
    if ($logsTable === null && header_table_exists($pdo, 'notification_logs')) {
        $logsTable = 'notification_logs';
    } elseif ($logsTable === null && header_table_exists($pdo, 'notification_logs_runtime')) {
        $logsTable = 'notification_logs_runtime';
    }

    if (is_string($logsTable) && $logsTable !== '') {
        $logsTable = preg_replace('/[^a-zA-Z0-9_]/', '', $logsTable);
        if ($logsTable === '') {
            throw new RuntimeException('Invalid logs table name');
        }

        $logsColumns = function_exists('twc_notification_logs_columns')
            ? twc_notification_logs_columns($pdo, $logsTable)
            : [];

        $hasChannel = isset($logsColumns['channel']) || header_column_exists($pdo, $logsTable, 'channel');
        $hasMessage = isset($logsColumns['message']) || header_column_exists($pdo, $logsTable, 'message');
        $hasStatus = isset($logsColumns['status']) || header_column_exists($pdo, $logsTable, 'status');
        $hasPriority = isset($logsColumns['priority']) || header_column_exists($pdo, $logsTable, 'priority');
        $hasSentAt = isset($logsColumns['sent_at']) || header_column_exists($pdo, $logsTable, 'sent_at');
        $hasCreatedAt = isset($logsColumns['created_at']) || header_column_exists($pdo, $logsTable, 'created_at');
        $hasId = isset($logsColumns['id']) || header_column_exists($pdo, $logsTable, 'id');

        $timeColumn = $hasSentAt ? 'sent_at' : ($hasCreatedAt ? 'created_at' : '');

        if ($hasMessage) {
            $selectParts = [
                $hasId ? 'id' : 'NULL AS id',
                $hasChannel ? 'channel' : "'system' AS channel",
                'message',
                $hasStatus ? 'status' : "'' AS status",
                $hasPriority ? 'priority' : "'' AS priority",
                $timeColumn !== '' ? "{$timeColumn} AS event_time" : "NOW() AS event_time",
            ];
            $orderBy = $timeColumn !== '' ? $timeColumn : ($hasId ? 'id' : 'NOW()');

            $stmt = $pdo->query("
                SELECT " . implode(', ', $selectParts) . "
                FROM `{$logsTable}`
                ORDER BY {$orderBy} DESC
                LIMIT 20
            ");
            $rows = $stmt ? ($stmt->fetchAll(PDO::FETCH_ASSOC) ?: []) : [];

            foreach ($rows as $row) {
                $channel = strtolower(trim((string)($row['channel'] ?? 'system')));
                $message = trim((string)($row['message'] ?? ''));
                if ($message === '') {
                    continue;
                }
                $status = strtolower(trim((string)($row['status'] ?? '')));
                $priority = strtolower(trim((string)($row['priority'] ?? '')));
                $eventTime = (string)($row['event_time'] ?? date('Y-m-d H:i:s'));
                $isIncident = ($channel === 'chat_risk' || strpos($channel, 'incident') !== false);

                $conversationId = null;
                if (preg_match('/\[CID:(\d+)\]/', $message, $m) === 1) {
                    $conversationId = (int)$m[1];
                }

                $item = [
                    'id' => $hasId ? ('log_' . (string)($row['id'] ?? '0')) : ('log_' . md5($message . $eventTime)),
                    'type' => $isIncident ? 'incident' : 'system',
                    'channel' => $channel !== '' ? $channel : 'system',
                    'title' => header_format_channel_title($channel, $status, $isIncident ? 'incident' : 'system'),
                    'message' => $message,
                    'status' => $status,
                    'priority' => $priority,
                    'sent_at' => $eventTime,
                    'conversation_id' => $conversationId,
                    'source' => 'notification_logs',
                ];

                if ($isIncident) {
                    $incidentNotifications[] = $item;
                } else {
                    $systemNotifications[] = $item;
                }
            }

            if ($sinceDate !== null && $timeColumn !== '' && $hasChannel) {
                $systemUnreadStmt = $pdo->prepare("
                    SELECT COUNT(*)
                    FROM `{$logsTable}`
                    WHERE {$timeColumn} > ?
                      AND LOWER(COALESCE(channel, '')) <> 'chat_risk'
                ");
                $systemUnreadStmt->execute([$sinceDate]);
                $systemUnread = (int)$systemUnreadStmt->fetchColumn();

                $incidentFromLogsStmt = $pdo->prepare("
                    SELECT COUNT(*)
                    FROM `{$logsTable}`
                    WHERE {$timeColumn} > ?
                      AND LOWER(COALESCE(channel, '')) = 'chat_risk'
                ");
                $incidentFromLogsStmt->execute([$sinceDate]);
                $incidentUnread += (int)$incidentFromLogsStmt->fetchColumn();
            } else {
                $systemUnread = count($systemNotifications);
                $incidentUnread += count($incidentNotifications);
            }
        }
    }
} catch (Throwable $e) {
    error_log('header-notifications logs error: ' . $e->getMessage());
}

// Pull unread Two-Way Communication messages/reports directly from chat storage.
// Incident reports and citizen messages share the same conversations/chat_messages stream.
try {
    if (function_exists('twc_chat_storage_available') && twc_chat_storage_available($pdo)) {
        $activeStatuses = twc_active_statuses();
        $statusPlaceholders = twc_placeholders($activeStatuses);

        $messageUnreadStmt = $pdo->prepare("
            SELECT COUNT(*)
            FROM conversations c
            JOIN chat_messages m ON c.conversation_id = m.conversation_id
            WHERE c.status IN ($statusPlaceholders)
              AND COALESCE(m.is_read, 0) = 0
              AND LOWER(COALESCE(m.sender_type, '')) <> 'admin'
        ");
        $messageUnreadStmt->execute($activeStatuses);
        $messageUnread = (int)$messageUnreadStmt->fetchColumn();

        $hasIncidentPriorityLevel = header_column_exists($pdo, 'conversations', 'incident_priority_level');
        $hasIncidentPriorityScore = header_column_exists($pdo, 'conversations', 'incident_priority_score');
        $prioritySelect = $hasIncidentPriorityLevel
            ? 'c.incident_priority_level'
            : "'' AS incident_priority_level";
        $scoreSelect = $hasIncidentPriorityScore
            ? 'c.incident_priority_score'
            : '0 AS incident_priority_score';

        $latestUnreadStmt = $pdo->prepare("
            SELECT
                c.conversation_id,
                c.user_name,
                c.user_location,
                c.user_concern,
                {$prioritySelect},
                {$scoreSelect},
                lm.message_id,
                lm.message_text,
                lm.sender_name,
                lm.created_at
            FROM conversations c
            JOIN (
                SELECT m1.*
                FROM chat_messages m1
                JOIN (
                    SELECT conversation_id, MAX(message_id) AS max_message_id
                    FROM chat_messages
                    WHERE COALESCE(is_read, 0) = 0
                      AND LOWER(COALESCE(sender_type, '')) <> 'admin'
                    GROUP BY conversation_id
                ) latest ON latest.max_message_id = m1.message_id
            ) lm ON lm.conversation_id = c.conversation_id
            WHERE c.status IN ($statusPlaceholders)
            ORDER BY lm.created_at DESC, lm.message_id DESC
            LIMIT 12
        ");
        $latestUnreadStmt->execute($activeStatuses);
        $messageRows = $latestUnreadStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        foreach ($messageRows as $row) {
            $messageText = trim((string)($row['message_text'] ?? ''));
            if ($messageText === '') {
                continue;
            }

            $concern = strtolower(trim((string)($row['user_concern'] ?? '')));
            $isIncidentReport = $concern === 'incident_report'
                || stripos($messageText, 'Incident Type:') !== false
                || stripos($messageText, '[CALL_') === 0;

            $priority = strtolower(trim((string)($row['incident_priority_level'] ?? '')));
            if (!in_array($priority, ['critical', 'high', 'urgent', 'moderate', 'low'], true)) {
                $calculated = twc_calculate_incident_priority([
                    'category' => $concern,
                    'userConcern' => $concern,
                    'message' => $messageText,
                ]);
                $priority = strtolower((string)($calculated['priority'] ?? 'low'));
            }

            $senderName = trim((string)($row['sender_name'] ?? ''));
            if ($senderName === '') {
                $senderName = trim((string)($row['user_name'] ?? 'Citizen'));
            }
            if ($senderName === '') {
                $senderName = 'Citizen';
            }

            $snippet = preg_replace('/\s+/', ' ', $messageText) ?? $messageText;
            if (strlen($snippet) > 140) {
                $snippet = substr($snippet, 0, 137) . '...';
            }

            $incidentNotifications[] = [
                'id' => 'msg_' . (string)($row['message_id'] ?? md5($messageText)),
                'type' => 'incident',
                'channel' => $isIncidentReport ? 'incident_report' : 'two_way_message',
                'title' => $isIncidentReport ? 'New Incident Report' : 'New Message Report',
                'message' => $senderName . ': ' . $snippet,
                'status' => 'unread',
                'priority' => $priority,
                'sent_at' => (string)($row['created_at'] ?? date('Y-m-d H:i:s')),
                'conversation_id' => (int)($row['conversation_id'] ?? 0),
                'location' => (string)($row['user_location'] ?? ''),
                'source' => 'chat_messages',
            ];
        }

        $incidentUnread = max($incidentUnread, $messageUnread);
    }
} catch (Throwable $e) {
    error_log('header-notifications chat messages error: ' . $e->getMessage());
}

// De-duplicate incidents by conversation id when available, keeping latest item.
$incidentByKey = [];
foreach ($incidentNotifications as $item) {
    $convId = isset($item['conversation_id']) ? (int)$item['conversation_id'] : 0;
    $key = $convId > 0 ? ('conv_' . $convId) : ('item_' . (string)($item['id'] ?? md5(json_encode($item))));
    if (!isset($incidentByKey[$key])) {
        $incidentByKey[$key] = $item;
        continue;
    }
    $existingTs = strtotime((string)($incidentByKey[$key]['sent_at'] ?? '')) ?: 0;
    $candidateTs = strtotime((string)($item['sent_at'] ?? '')) ?: 0;
    if ($candidateTs >= $existingTs) {
        $incidentByKey[$key] = $item;
    }
}
$incidentNotifications = array_values($incidentByKey);

$systemNotifications = header_sort_notifications($systemNotifications);
$incidentNotifications = header_sort_notifications($incidentNotifications);

$notifications = header_sort_notifications(array_merge($incidentNotifications, $systemNotifications));
if (count($notifications) > 20) {
    $notifications = array_slice($notifications, 0, 20);
}
if (count($systemNotifications) > 10) {
    $systemNotifications = array_slice($systemNotifications, 0, 10);
}
if (count($incidentNotifications) > 10) {
    $incidentNotifications = array_slice($incidentNotifications, 0, 10);
}

$notificationUnread = max(0, $systemUnread + $incidentUnread);

echo json_encode([
    'success' => true,
    'active_alerts' => $activeAlerts,
    'system_unread' => $systemUnread,
    'incident_unread' => $incidentUnread,
    'message_unread' => $messageUnread,
    'notification_unread' => $notificationUnread,
    'system_notifications' => $systemNotifications,
    'incident_notifications' => $incidentNotifications,
    'notifications' => $notifications,
    'server_time' => date('c'),
]);
