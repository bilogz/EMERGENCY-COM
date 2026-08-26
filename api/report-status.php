<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/config.php';
require_once dirname(__DIR__) . '/ADMIN/api/chat-logic.php';

function ensureIncidentReportStatusColumn(PDO $pdo): bool
{
    if (twc_column_exists($pdo, 'incident_reports', 'response_status')) {
        return true;
    }
    try {
        $pdo->exec("ALTER TABLE incident_reports ADD COLUMN response_status VARCHAR(40) NULL AFTER status");
        return twc_column_exists($pdo, 'incident_reports', 'response_status', true);
    } catch (Throwable $migrationError) {
        error_log('Incident report response-status migration skipped: ' . $migrationError->getMessage());
        return false;
    }
}

$localConfig = [];
$localConfigPath = dirname(__DIR__) . '/ADMIN/api/config.local.php';
if (is_file($localConfigPath)) {
    $loaded = require $localConfigPath;
    if (is_array($loaded)) {
        $localConfig = $loaded;
    }
}

$input = json_decode((string)file_get_contents('php://input'), true);
if (!is_array($input)) {
    $input = $_POST;
}

$expectedKey = trim((string)(getenv('RESPONSE_TEAM_TRANSFER_API_KEY')
    ?: ($localConfig['RESPONSE_TEAM_TRANSFER_API_KEY'] ?? 'ERS_API_2026_x10y24l')));
$providedKey = trim((string)(
    $_SERVER['HTTP_X_API_KEY']
    ?? $_SERVER['HTTP_X_ERS_API_KEY']
    ?? ($_GET['api_key'] ?? $_POST['api_key'] ?? $input['api_key'] ?? $input['apiKey'] ?? '')
));
if ($expectedKey !== '' && $providedKey !== '' && !hash_equals($expectedKey, $providedKey)) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Invalid integration key']);
    exit;
}

$rawStatus = strtolower(trim((string)($input['status'] ?? $input['responseStatus'] ?? $input['response_status'] ?? '')));
$statusMap = [
    'new' => 'received',
    'pending' => 'pending',
    'accepted' => 'received',
    'received' => 'received',
    'assigned' => 'dispatching',
    'acknowledged' => 'dispatching',
    'dispatching' => 'dispatching',
    'dispatched' => 'dispatching',
    'enroute' => 'ongoing_dispatch',
    'en_route' => 'ongoing_dispatch',
    'on_scene' => 'ongoing_dispatch',
    'ongoing' => 'ongoing_dispatch',
    'ongoing_dispatch' => 'ongoing_dispatch',
    'in_progress' => 'ongoing_dispatch',
    'resolved' => 'completed',
    'done' => 'completed',
    'action_completed' => 'completed',
    'complete' => 'completed',
    'completed' => 'completed',
    'rejected' => 'completed',
    'declined' => 'completed',
    'cancelled' => 'completed',
    'closed' => 'completed',
];
$status = $statusMap[str_replace(' ', '_', $rawStatus)] ?? $rawStatus;
if ($status === '') {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Unsupported or missing ERS status']);
    exit;
}

$transferId = trim((string)($input['transferId'] ?? $input['transfer_id'] ?? $input['callId'] ?? $input['call_id'] ?? ''));
$conversationId = (int)($input['conversationId'] ?? $input['conversation_id'] ?? 0);
$note = trim((string)($input['note'] ?? $input['message'] ?? ''));
$proofUrl = trim((string)(
    $input['proofUrl']
    ?? $input['proof_url']
    ?? $input['photoUrl']
    ?? $input['photo_url']
    ?? $input['proofPhoto']
    ?? $input['proof_photo']
    ?? ''
));
if ($proofUrl === '' && isset($input['attachments']) && is_array($input['attachments'])) {
    foreach ($input['attachments'] as $attachment) {
        if (!is_array($attachment)) {
            continue;
        }
        $candidate = trim((string)($attachment['url'] ?? $attachment['href'] ?? ''));
        if ($candidate !== '') {
            $proofUrl = $candidate;
            break;
        }
    }
}
$noteForStorage = $note;
if ($proofUrl !== '') {
    $noteForStorage = trim($noteForStorage . ($noteForStorage !== '' ? "\n" : '') . 'Proof photo: ' . $proofUrl);
}

try {
    $hasResponseStatus = ensureIncidentReportStatusColumn($pdo);
    $audit = null;
    if ($transferId !== '') {
        $stmt = $pdo->prepare("
            SELECT *
            FROM transfer_call_audit
            WHERE call_id = ?
               OR JSON_UNQUOTE(JSON_EXTRACT(payload, '$.transferId')) = ?
            ORDER BY id DESC
            LIMIT 1
        ");
        $stmt->execute([$transferId, $transferId]);
        $audit = $stmt->fetch(PDO::FETCH_ASSOC);
    }
    if (!$audit && $conversationId > 0) {
        $stmt = $pdo->prepare('SELECT * FROM transfer_call_audit WHERE conversation_id = ? ORDER BY id DESC LIMIT 1');
        $stmt->execute([$conversationId]);
        $audit = $stmt->fetch(PDO::FETCH_ASSOC);
    }
    if (!$audit) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Transfer record not found']);
        exit;
    }

    $conversationId = (int)($audit['conversation_id'] ?? $conversationId);
    $pdo->beginTransaction();
    $pdo->prepare("
        UPDATE transfer_call_audit
        SET response_status = ?,
            response_status_note = ?,
            status = ?,
            status_updated_at = NOW()
        WHERE id = ?
    ")->execute([
        $status,
        $noteForStorage !== '' ? $noteForStorage : null,
        $status === 'completed' ? 'completed' : 'sent',
        (int)$audit['id'],
    ]);

    $reportId = 0;
    if ($conversationId > 0) {
        $stmt = $pdo->prepare('SELECT device_info FROM conversations WHERE conversation_id = ? LIMIT 1');
        $stmt->execute([$conversationId]);
        $deviceInfo = json_decode((string)$stmt->fetchColumn(), true);
        $reportId = is_array($deviceInfo) ? (int)($deviceInfo['incident_report_id'] ?? 0) : 0;

        $label = ucwords(str_replace('_', ' ', $status));
        $workflow = $status === 'completed' ? 'resolved' : 'waiting_user';
        $message = '[ERS_STATUS]' . $label . ($noteForStorage !== '' ? ': ' . $noteForStorage : '');
        $pdo->prepare("
            UPDATE conversations
            SET status = ?, last_message = ?, last_message_time = NOW(), updated_at = NOW()
            WHERE conversation_id = ?
        ")->execute([twc_status_for_db($pdo, $workflow), $message, $conversationId]);

        $lastStatus = $pdo->prepare("
            SELECT message_text
            FROM chat_messages
            WHERE conversation_id = ? AND sender_type = 'system'
            ORDER BY id DESC
            LIMIT 1
        ");
        $lastStatus->execute([$conversationId]);
        if ((string)$lastStatus->fetchColumn() !== $message) {
            $hasProofAttachment = $proofUrl !== '' && twc_ensure_chat_attachment_columns($pdo);
            if ($hasProofAttachment) {
                $pdo->prepare("
                    INSERT INTO chat_messages
                        (conversation_id, sender_id, sender_name, sender_type, message_text, attachment_url, ip_address, device_info, is_read, created_at)
                    VALUES (?, 'ers', 'Emergency Response System', 'system', ?, ?, '', 'response_team_status', 0, NOW())
                ")->execute([$conversationId, $message, $proofUrl]);
            } else {
                $pdo->prepare("
                    INSERT INTO chat_messages
                        (conversation_id, sender_id, sender_name, sender_type, message_text, ip_address, device_info, is_read, created_at)
                    VALUES (?, 'ers', 'Emergency Response System', 'system', ?, '', 'response_team_status', 0, NOW())
                ")->execute([$conversationId, $message]);
            }
        }
    }

    if ($reportId > 0) {
        $legacyStatus = match ($status) {
            'received' => 'pending',
            'dispatching', 'ongoing_dispatch' => 'in_progress',
            'completed' => 'resolved',
            default => $status,
        };
        if ($hasResponseStatus) {
            $pdo->prepare('UPDATE incident_reports SET status = ?, response_status = ?, admin_notes = ? WHERE id = ?')
                ->execute([$legacyStatus, $status, $noteForStorage !== '' ? $noteForStorage : null, $reportId]);
        } else {
            $pdo->prepare('UPDATE incident_reports SET status = ?, admin_notes = ? WHERE id = ?')
                ->execute([$legacyStatus, $noteForStorage !== '' ? $noteForStorage : null, $reportId]);
        }
    }
    $pdo->commit();

    echo json_encode([
        'success' => true,
        'transferId' => (int)$audit['id'],
        'conversationId' => $conversationId,
        'reportId' => $reportId,
        'status' => $status,
        'archived' => $status === 'completed',
    ], JSON_UNESCAPED_SLASHES);
} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('ERS report status callback failed: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Unable to synchronize report status']);
}
