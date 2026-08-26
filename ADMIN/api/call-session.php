<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/db_connect.php';

function callSessionJson($payload, $status = 200) {
    http_response_code($status);
    echo json_encode($payload);
    exit;
}

function callSessionInput() {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw ?: '{}', true);
    return is_array($data) ? $data : [];
}

function callSessionAdminRequired() {
    if (empty($_SESSION['admin_logged_in'])) {
        callSessionJson(['success' => false, 'error' => 'Admin login required.'], 401);
    }
}

function callSessionAdminId() {
    return isset($_SESSION['admin_user_id']) ? (int) $_SESSION['admin_user_id'] : 0;
}

function callSessionAdminName() {
    return $_SESSION['admin_username'] ?? $_SESSION['admin_name'] ?? 'Admin';
}

function callSessionClean($value, $max = 255) {
    $value = trim((string) ($value ?? ''));
    if ($value === '') return '';
    return mb_substr($value, 0, $max);
}

function ensureCallSessionTable(PDO $pdo) {
    $pdo->exec("CREATE TABLE IF NOT EXISTS emergency_call_sessions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        call_id VARCHAR(128) NOT NULL UNIQUE,
        room VARCHAR(180) DEFAULT NULL,
        offer_payload LONGTEXT DEFAULT NULL,
        caller_user_id VARCHAR(100) DEFAULT NULL,
        caller_name VARCHAR(180) DEFAULT NULL,
        caller_phone VARCHAR(80) DEFAULT NULL,
        caller_type VARCHAR(40) DEFAULT 'guest',
        location_text VARCHAR(255) DEFAULT NULL,
        location_data LONGTEXT DEFAULT NULL,
        conversation_id INT DEFAULT NULL,
        status VARCHAR(40) NOT NULL DEFAULT 'open',
        assigned_admin_id INT DEFAULT NULL,
        assigned_admin_name VARCHAR(180) DEFAULT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        answered_at DATETIME DEFAULT NULL,
        transferred_at DATETIME DEFAULT NULL,
        ended_at DATETIME DEFAULT NULL,
        INDEX idx_status_updated (status, updated_at),
        INDEX idx_admin_status (assigned_admin_id, status),
        INDEX idx_conversation (conversation_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $columns = [];
    $stmt = $pdo->query("SHOW COLUMNS FROM emergency_call_sessions");
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $column) {
        $columns[strtolower($column['Field'] ?? '')] = true;
    }
    if (empty($columns['offer_payload'])) {
        $pdo->exec("ALTER TABLE emergency_call_sessions ADD COLUMN offer_payload LONGTEXT DEFAULT NULL AFTER room");
    }
}

function callSessionPayloadFields(array $data) {
    $caller = isset($data['caller']) && is_array($data['caller']) ? $data['caller'] : [];
    $location = isset($data['location']) && is_array($data['location']) ? $data['location'] : [];
    $locationText = callSessionClean($location['address'] ?? $data['locationText'] ?? $data['location_text'] ?? '', 255);
    if ($locationText === '' && isset($location['lat'], $location['lng'])) {
        $locationText = callSessionClean($location['lat'] . ', ' . $location['lng'], 255);
    }
    return [
        'call_id' => callSessionClean($data['callId'] ?? $data['call_id'] ?? '', 128),
        'room' => callSessionClean($data['room'] ?? '', 180),
        'offer_payload' => isset($data['offerPayload']) && is_array($data['offerPayload'])
            ? json_encode($data['offerPayload'])
            : (isset($data['offer_payload']) && is_array($data['offer_payload'])
                ? json_encode($data['offer_payload'])
                : (isset($data['sdp']) && is_array($data['sdp']) ? json_encode($data) : null)),
        'caller_user_id' => callSessionClean($caller['user_id'] ?? $caller['id'] ?? $data['callerUserId'] ?? '', 100),
        'caller_name' => callSessionClean($caller['name'] ?? $data['callerName'] ?? 'Emergency Call User', 180),
        'caller_phone' => callSessionClean($caller['phone'] ?? $data['callerPhone'] ?? '', 80),
        'caller_type' => callSessionClean($caller['type'] ?? ($caller ? 'registered' : 'guest'), 40) ?: 'guest',
        'location_text' => $locationText,
        'location_data' => $location ? json_encode($location) : null,
        'conversation_id' => isset($data['conversationId']) && $data['conversationId'] !== '' ? (int) $data['conversationId'] : null,
    ];
}

function fetchCallSession(PDO $pdo, $callId) {
    $stmt = $pdo->prepare('SELECT * FROM emergency_call_sessions WHERE call_id = ? LIMIT 1');
    $stmt->execute([$callId]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

function normalizeCallSessionRow($row) {
    if (!$row) return null;
    if (!empty($row['offer_payload'])) {
        $decoded = json_decode($row['offer_payload'], true);
        $row['offer_payload'] = is_array($decoded) ? $decoded : null;
    } else {
        $row['offer_payload'] = null;
    }
    if (!empty($row['location_data'])) {
        $decoded = json_decode($row['location_data'], true);
        $row['location_data'] = is_array($decoded) ? $decoded : null;
    } else {
        $row['location_data'] = null;
    }
    return $row;
}

if (!$pdo) {
    callSessionJson(['success' => false, 'error' => 'Database unavailable.'], 500);
}

try {
    ensureCallSessionTable($pdo);
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    $input = $method === 'GET' ? $_GET : callSessionInput();
    $action = callSessionClean($input['action'] ?? $_GET['action'] ?? 'list', 40);

    if ($action === 'upsert_open') {
        $fields = callSessionPayloadFields($input);
        if ($fields['call_id'] === '') callSessionJson(['success' => false, 'error' => 'callId is required.'], 422);
        $stmt = $pdo->prepare("INSERT INTO emergency_call_sessions
            (call_id, room, offer_payload, caller_user_id, caller_name, caller_phone, caller_type, location_text, location_data, conversation_id, status)
            VALUES (:call_id, :room, :offer_payload, :caller_user_id, :caller_name, :caller_phone, :caller_type, :location_text, :location_data, :conversation_id, 'open')
            ON DUPLICATE KEY UPDATE
                room = COALESCE(NULLIF(VALUES(room), ''), room),
                offer_payload = COALESCE(VALUES(offer_payload), offer_payload),
                caller_user_id = COALESCE(NULLIF(VALUES(caller_user_id), ''), caller_user_id),
                caller_name = COALESCE(NULLIF(VALUES(caller_name), ''), caller_name),
                caller_phone = COALESCE(NULLIF(VALUES(caller_phone), ''), caller_phone),
                caller_type = COALESCE(NULLIF(VALUES(caller_type), ''), caller_type),
                location_text = COALESCE(NULLIF(VALUES(location_text), ''), location_text),
                location_data = COALESCE(VALUES(location_data), location_data),
                conversation_id = COALESCE(VALUES(conversation_id), conversation_id),
                status = IF(status IN ('open','ringing'), 'open', status),
                updated_at = NOW()");
        $stmt->execute($fields);
        callSessionJson(['success' => true, 'call' => normalizeCallSessionRow(fetchCallSession($pdo, $fields['call_id']))]);
    }

    callSessionAdminRequired();

    if ($action === 'list') {
        // Auto-expire open calls older than 10 minutes (600 seconds)
        try {
            $pdo->query("UPDATE emergency_call_sessions SET status = 'ended', ended_at = NOW() WHERE status = 'open' AND updated_at < DATE_SUB(NOW(), INTERVAL 10 MINUTE)");
        } catch (PDOException $e) {
            error_log('Call session auto-expiry error: ' . $e->getMessage());
        }

        // Keep open calls persistent across Socket.IO/PM2 restarts. Calls close only by explicit end, decline, transfer, or completion.
        $stmt = $pdo->query("SELECT * FROM emergency_call_sessions WHERE status IN ('open','assigned','pending','completed','ended','declined') ORDER BY updated_at DESC LIMIT 200");
        $rows = array_map('normalizeCallSessionRow', $stmt->fetchAll(PDO::FETCH_ASSOC));
        callSessionJson([
            'success' => true,
            'open' => array_values(array_filter($rows, fn($r) => $r['status'] === 'open')),
            'assigned' => array_values(array_filter($rows, fn($r) => $r['status'] === 'assigned')),
            'pending' => array_values(array_filter($rows, fn($r) => $r['status'] === 'pending')),
            'completed' => array_values(array_filter($rows, fn($r) => $r['status'] === 'completed')),
            'all' => $rows,
        ]);
    }

    if ($action === 'claim') {
        $fields = callSessionPayloadFields($input);
        if ($fields['call_id'] === '') callSessionJson(['success' => false, 'error' => 'callId is required.'], 422);
        $adminId = callSessionAdminId();
        $adminName = callSessionAdminName();
        $pdo->beginTransaction();
        $busy = $pdo->prepare("SELECT call_id FROM emergency_call_sessions WHERE status = 'assigned' AND assigned_admin_id = ? AND call_id <> ? LIMIT 1");
        $busy->execute([$adminId, $fields['call_id']]);
        if ($busy->fetch()) {
            $pdo->rollBack();
            callSessionJson(['success' => false, 'error' => 'You already have an active call.'], 409);
        }
        $row = fetchCallSession($pdo, $fields['call_id']);
        if (!$row) {
            $insert = $pdo->prepare("INSERT INTO emergency_call_sessions
                (call_id, room, offer_payload, caller_user_id, caller_name, caller_phone, caller_type, location_text, location_data, conversation_id, status)
                VALUES (:call_id, :room, :offer_payload, :caller_user_id, :caller_name, :caller_phone, :caller_type, :location_text, :location_data, :conversation_id, 'open')");
            $insert->execute($fields);
            $row = fetchCallSession($pdo, $fields['call_id']);
        }
        if ($row && $row['status'] !== 'open' && ((int)($row['assigned_admin_id'] ?? 0) !== $adminId || $row['status'] !== 'assigned')) {
            $pdo->rollBack();
            callSessionJson(['success' => false, 'error' => 'This call is no longer open.'], 409);
        }
        $claim = $pdo->prepare("UPDATE emergency_call_sessions SET status='assigned', assigned_admin_id=?, assigned_admin_name=?, answered_at=COALESCE(answered_at, NOW()), updated_at=NOW() WHERE call_id=?");
        $claim->execute([$adminId, $adminName, $fields['call_id']]);

        try {
            if ($row && !empty($row['conversation_id'])) {
                $convStmt = $pdo->prepare("UPDATE conversations SET assigned_to = ?, status = 'assigned', updated_at = NOW() WHERE conversation_id = ?");
                $convStmt->execute([$adminId, (int)$row['conversation_id']]);
            } else if (!empty($fields['call_id'])) {
                $convStmt = $pdo->prepare("UPDATE conversations SET assigned_to = ?, status = 'assigned', updated_at = NOW() WHERE external_call_id = ? OR last_message LIKE ?");
                $convStmt->execute([$adminId, $fields['call_id'], '%' . $fields['call_id'] . '%']);
            }
        } catch (Throwable $e) {
            error_log('Call claim conversation sync notice: ' . $e->getMessage());
        }

        $pdo->commit();
        callSessionJson(['success' => true, 'call' => normalizeCallSessionRow(fetchCallSession($pdo, $fields['call_id']))]);
    }

    if ($action === 'mark') {
        $callId = callSessionClean($input['callId'] ?? $input['call_id'] ?? '', 128);
        $status = strtolower(callSessionClean($input['status'] ?? '', 40));
        $allowed = ['open', 'assigned', 'pending', 'completed', 'ended', 'declined'];
        if ($callId === '' || !in_array($status, $allowed, true)) {
            callSessionJson(['success' => false, 'error' => 'Valid callId and status are required.'], 422);
        }
        $timestampColumn = null;
        if ($status === 'pending') $timestampColumn = 'transferred_at';
        if ($status === 'ended' || $status === 'declined' || $status === 'completed') $timestampColumn = 'ended_at';
        $sql = "UPDATE emergency_call_sessions SET status=?, updated_at=NOW()";
        if ($timestampColumn) $sql .= ", {$timestampColumn}=COALESCE({$timestampColumn}, NOW())";
        $sql .= " WHERE call_id=?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$status, $callId]);

        try {
            $row = fetchCallSession($pdo, $callId);
            $convStatus = ($status === 'ended' || $status === 'declined' || $status === 'completed') ? 'closed' : $status;
            if ($row && !empty($row['conversation_id'])) {
                $convStmt = $pdo->prepare("UPDATE conversations SET status = ?, updated_at = NOW() WHERE conversation_id = ?");
                $convStmt->execute([$convStatus, (int)$row['conversation_id']]);
            } else if ($callId !== '') {
                $convStmt = $pdo->prepare("UPDATE conversations SET status = ?, updated_at = NOW() WHERE external_call_id = ? OR last_message LIKE ?");
                $convStmt->execute([$convStatus, $callId, '%' . $callId . '%']);
            }
        } catch (Throwable $e) {
            error_log('Call mark conversation sync notice: ' . $e->getMessage());
        }

        callSessionJson(['success' => true, 'call' => normalizeCallSessionRow(fetchCallSession($pdo, $callId))]);
    }

    callSessionJson(['success' => false, 'error' => 'Unknown action.'], 404);
} catch (Throwable $e) {
    if ($pdo && $pdo->inTransaction()) $pdo->rollBack();
    error_log('call-session.php error: ' . $e->getMessage());
    callSessionJson(['success' => false, 'error' => 'Call session error.'], 500);
}
