<?php
declare(strict_types=1);

$rawInput = (string)file_get_contents('php://input');
$transferInput = json_decode($rawInput, true);
if (!is_array($transferInput)) {
    $transferInput = $_POST;
}

function ensureTransferredReportStatusColumn(PDO $pdo): bool
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

function syncTransferredReportWorkflow(PDO $pdo, array $input): void
{
    $action = strtolower(trim((string)($input['action'] ?? 'transfer')));
    $conversationId = (int)($input['conversationId'] ?? $input['conversation_id'] ?? 0);
    $transferAuditId = (int)($input['transferId'] ?? $input['id'] ?? 0);

    if ($transferAuditId > 0) {
        $stmt = $pdo->prepare('SELECT id, conversation_id, response_status, response_status_note, status FROM transfer_call_audit WHERE id = ? LIMIT 1');
        $stmt->execute([$transferAuditId]);
    } elseif ($conversationId > 0) {
        $stmt = $pdo->prepare('SELECT id, conversation_id, response_status, response_status_note, status FROM transfer_call_audit WHERE conversation_id = ? ORDER BY id DESC LIMIT 1');
        $stmt->execute([$conversationId]);
    } else {
        return;
    }
    $audit = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$audit) {
        return;
    }
    if ($action === 'transfer' && strtolower((string)($audit['status'] ?? '')) !== 'sent') {
        return;
    }

    $conversationId = (int)($audit['conversation_id'] ?? $conversationId);
    $auditId = (int)($audit['id'] ?? $transferAuditId);
    if ($conversationId <= 0) {
        return;
    }
    $rawStatus = strtolower(trim((string)($audit['response_status'] ?? '')));
    if ($action === 'transfer' && $rawStatus === '') {
        $rawStatus = 'pending';
    }
    $statusMap = [
        'new' => 'received',
        'pending' => 'pending',
        'requested' => 'pending',
        'received' => 'received',
        'assigned' => 'dispatching',
        'acknowledged' => 'dispatching',
        'dispatching' => 'dispatching',
        'dispatched' => 'dispatching',
        'enroute' => 'ongoing_dispatch',
        'on_scene' => 'ongoing_dispatch',
        'ongoing_dispatch' => 'ongoing_dispatch',
        'in_progress' => 'ongoing_dispatch',
        'resolved' => 'resolved',
        'complete' => 'completed',
        'completed' => 'completed',
    ];
    $status = $statusMap[str_replace(' ', '_', $rawStatus)] ?? 'received';
    $note = trim((string)($audit['response_status_note'] ?? ''));
    $label = $status === 'pending' ? 'Pending ERS Status' : ucwords(str_replace('_', ' ', $status));
    $message = '[ERS_STATUS]' . $label . ($note !== '' ? ': ' . $note : '');
    $workflow = $status === 'completed' ? 'resolved' : 'waiting_user';
    $hasResponseStatus = ensureTransferredReportStatusColumn($pdo);

    $pdo->beginTransaction();
    $pdo->prepare("
        UPDATE conversations
        SET status = ?, assigned_to = NULL, last_message = ?, last_message_time = NOW(), updated_at = NOW()
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
        $pdo->prepare("
            INSERT INTO chat_messages
                (conversation_id, sender_id, sender_name, sender_type, message_text, ip_address, device_info, is_read, created_at)
            VALUES (?, 'ers', 'Emergency Response System', 'system', ?, '', 'response_team_status', 0, NOW())
        ")->execute([$conversationId, $message]);
    }

    $deviceStmt = $pdo->prepare('SELECT device_info FROM conversations WHERE conversation_id = ? LIMIT 1');
    $deviceStmt->execute([$conversationId]);
    $deviceInfo = json_decode((string)$deviceStmt->fetchColumn(), true);
    $reportId = is_array($deviceInfo) ? (int)($deviceInfo['incident_report_id'] ?? 0) : 0;
    if ($reportId > 0) {
        $legacyStatus = match ($status) {
            'pending' => 'pending',
            'received' => 'pending',
            'dispatching', 'ongoing_dispatch' => 'in_progress',
            'completed' => 'resolved',
            default => $status,
        };
        if ($hasResponseStatus) {
            $pdo->prepare('UPDATE incident_reports SET status = ?, response_status = ?, admin_notes = ? WHERE id = ?')
                ->execute([$legacyStatus, $status, $note !== '' ? $note : null, $reportId]);
        } else {
            $pdo->prepare('UPDATE incident_reports SET status = ?, admin_notes = ? WHERE id = ?')
                ->execute([$legacyStatus, $note !== '' ? $note : null, $reportId]);
        }
    }
    if ($status === 'completed' && $auditId > 0) {
        $pdo->prepare("UPDATE transfer_call_audit SET status = 'completed', status_updated_at = NOW() WHERE id = ?")
            ->execute([$auditId]);
    }
    $pdo->commit();
}

register_shutdown_function(static function () use ($transferInput): void {
    global $pdo;
    if (!isset($pdo) || !($pdo instanceof PDO)) {
        return;
    }
    try {
        require_once dirname(__DIR__) . '/ADMIN/api/chat-logic.php';
        syncTransferredReportWorkflow($pdo, $transferInput);
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('Transferred report workflow sync failed: ' . $e->getMessage());
    }
});

require __DIR__ . '/transfer-call.php';
