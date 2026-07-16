<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

$isAdminSession = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;

if (!$isAdminSession) {
    require_once __DIR__ . '/auth.php';
} else {
    require_once __DIR__ . '/config.php';
    $deptName = 'Admin Session';
    if (!function_exists('sendJsonResponse')) {
        function sendJsonResponse(bool $success, string $message, array $data = [], int $httpCode = 200) {
            http_response_code($httpCode);
            header('Content-Type: application/json; charset=utf-8');
            $response = ['success' => $success, 'message' => $message];
            if (!empty($data)) {
                $response = array_merge($response, $data);
            }
            echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit();
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

function ensureTransferAuditTable(PDO $pdo): void {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS transfer_call_audit (
            id INT AUTO_INCREMENT PRIMARY KEY,
            call_id VARCHAR(128) NULL,
            conversation_id INT NULL,
            emergency_type VARCHAR(80) NULL,
            caller_name VARCHAR(255) NULL,
            caller_phone VARCHAR(80) NULL,
            caller_address TEXT NULL,
            payload JSON NULL,
            integration_url TEXT NULL,
            integration_status INT NULL,
            integration_response MEDIUMTEXT NULL,
            status VARCHAR(30) NOT NULL DEFAULT 'prepared',
            response_status VARCHAR(80) NULL,
            response_status_note TEXT NULL,
            status_requested_at DATETIME NULL,
            status_updated_at DATETIME NULL,
            requested_by VARCHAR(255) NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_call_id (call_id),
            INDEX idx_conversation_id (conversation_id),
            INDEX idx_created_at (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    $columns = [
        'response_status' => "ALTER TABLE transfer_call_audit ADD COLUMN response_status VARCHAR(80) NULL AFTER status",
        'response_status_note' => "ALTER TABLE transfer_call_audit ADD COLUMN response_status_note TEXT NULL AFTER response_status",
        'status_requested_at' => "ALTER TABLE transfer_call_audit ADD COLUMN status_requested_at DATETIME NULL AFTER response_status_note",
        'status_updated_at' => "ALTER TABLE transfer_call_audit ADD COLUMN status_updated_at DATETIME NULL AFTER status_requested_at",
    ];
    foreach ($columns as $column => $sql) {
        $stmt = $pdo->prepare("
            SELECT COUNT(*)
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'transfer_call_audit'
              AND COLUMN_NAME = ?
        ");
        $stmt->execute([$column]);
        if ((int)$stmt->fetchColumn() === 0) {
            $pdo->exec($sql);
        }
    }
}

function responseTeamFormPayload(array $payload, string $apiKey): array {
    $caller = is_array($payload['caller'] ?? null) ? $payload['caller'] : [];
    $location = is_array($payload['location'] ?? null) ? $payload['location'] : [];
    $description = trim((string)($payload['description'] ?? ''));
    if ($description === '') {
        $description = trim((string)($payload['latestMessage'] ?? ''));
    }
    if ($description === '') {
        $description = 'Transferred emergency call/report from AlertaraQC two-way communication.';
    }
    $incidentType = trim((string)($payload['emergencyType'] ?? ''));
    if ($incidentType === '') {
        $incidentType = 'emergency';
    }

    return [
        'api_key' => $apiKey,
        'action' => 'create_incident',
        'event' => $payload['event'] ?? 'emergency_call_transfer',
        'call_id' => $payload['callId'] ?? '',
        'callId' => $payload['callId'] ?? '',
        'room' => $payload['room'] ?? '',
        'socket_url' => $payload['socketUrl'] ?? '',
        'socketUrl' => $payload['socketUrl'] ?? '',
        'socket_path' => $payload['socketPath'] ?? '/socket.io',
        'socketPath' => $payload['socketPath'] ?? '/socket.io',
        'conversation_id' => $payload['conversationId'] ?? '',
        'conversationId' => $payload['conversationId'] ?? '',
        'type' => $incidentType,
        'incident_type' => $incidentType,
        'emergency_type' => $incidentType,
        'emergencyType' => $incidentType,
        'priority' => $payload['priority'] ?? 'high',
        'status' => 'new',
        'title' => 'Transferred emergency from AlertaraQC',
        'description' => $description,
        'caller_name' => $caller['name'] ?? '',
        'caller_phone' => $caller['phone'] ?? '',
        'caller_address' => $caller['address'] ?? ($location['address'] ?? ''),
        'location_address' => $caller['address'] ?? ($location['address'] ?? ''),
        'latitude' => $location['lat'] ?? '',
        'longitude' => $location['lng'] ?? '',
        'transferred_at' => $payload['transferredAt'] ?? gmdate('c'),
        'payload_json' => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
    ];
}

function postResponseTeamPayload(string $targetUrl, array $payload, string $apiKey): array {
    $headers = [
        'Content-Type: application/json',
        'Accept: application/json',
    ];
    if ($apiKey !== '') {
        $headers[] = 'Authorization: Bearer ' . $apiKey;
        $headers[] = 'X-API-Key: ' . $apiKey;
        $headers[] = 'X-ERS-API-Key: ' . $apiKey;
        $headers[] = 'X-ERS-Client: emergency-comm-alertaraqc';
    }

    $jsonBody = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $ch = curl_init($targetUrl);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_POSTFIELDS => $jsonBody,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT => 12,
    ]);
    $body = curl_exec($ch);
    $error = curl_error($ch);
    $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $jsonOk = $body !== false && $error === '' && $status >= 200 && $status < 300;
    $decoded = json_decode((string)$body, true);
    if (is_array($decoded) && array_key_exists('success', $decoded) && !$decoded['success']) {
        $jsonOk = false;
    }
    if ($jsonOk) {
        return [
            'format' => 'json',
            'httpStatus' => $status,
            'response' => $body,
            'error' => $error,
            'ok' => true,
        ];
    }

    $formHeaders = ['Accept: application/json'];
    if ($apiKey !== '') {
        $formHeaders[] = 'Authorization: Bearer ' . $apiKey;
        $formHeaders[] = 'X-API-Key: ' . $apiKey;
        $formHeaders[] = 'X-ERS-API-Key: ' . $apiKey;
        $formHeaders[] = 'X-ERS-Client: emergency-comm-alertaraqc';
    }
    $formBody = http_build_query(responseTeamFormPayload($payload, $apiKey));
    $ch = curl_init($targetUrl);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => $formHeaders,
        CURLOPT_POSTFIELDS => $formBody,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT => 12,
    ]);
    $formResponse = curl_exec($ch);
    $formError = curl_error($ch);
    $formStatus = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return [
        'format' => 'form',
        'httpStatus' => $formStatus,
        'response' => $formResponse,
        'error' => $formError,
        'ok' => $formResponse !== false && $formError === '' && $formStatus >= 200 && $formStatus < 300,
        'jsonAttempt' => [
            'httpStatus' => $status,
            'response' => $body,
            'error' => $error,
        ],
    ];
}

ensureTransferAuditTable($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $limit = isset($_GET['limit']) ? min(100, max(1, (int)$_GET['limit'])) : 50;
    $stmt = $pdo->prepare("SELECT * FROM transfer_call_audit ORDER BY id DESC LIMIT ?");
    $stmt->bindValue(1, $limit, PDO::PARAM_INT);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as &$row) {
        $row['payload'] = json_decode((string)($row['payload'] ?? ''), true);
    }
    sendJsonResponse(true, 'Transferred calls/messages retrieved.', ['transfers' => $rows]);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJsonResponse(false, 'Method not allowed. Use GET or POST.', [], 405);
}

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    sendJsonResponse(false, 'Invalid JSON', [], 400);
}

$action = strtolower(trim((string)($input['action'] ?? 'transfer')));

$callId = trim((string)($input['callId'] ?? ''));
$conversationId = isset($input['conversationId']) ? (int)$input['conversationId'] : null;
if ($action === 'update_status') {
    $transferId = (int)($input['transferId'] ?? $input['id'] ?? 0);
    $responseStatus = strtolower(trim((string)($input['responseStatus'] ?? $input['status'] ?? '')));
    $allowedStatuses = ['requested', 'received', 'fake_call', 'rescue_ongoing', 'responders_dispatched', 'arrived_on_scene', 'resolved', 'cancelled', 'unable_to_locate', 'duplicate'];
    if ($transferId <= 0 || $responseStatus === '') {
        sendJsonResponse(false, 'Missing transferId or responseStatus', [], 400);
    }
    if (!in_array($responseStatus, $allowedStatuses, true)) {
        sendJsonResponse(false, 'Invalid responseStatus', ['allowedStatuses' => $allowedStatuses], 400);
    }
    $stmt = $pdo->prepare("
        UPDATE transfer_call_audit
        SET response_status = ?, response_status_note = ?, status_updated_at = NOW()
        WHERE id = ?
    ");
    $stmt->execute([$responseStatus, trim((string)($input['note'] ?? '')), $transferId]);
    sendJsonResponse(true, 'Transfer emergency status updated.', [
        'transferId' => $transferId,
        'responseStatus' => $responseStatus,
    ]);
}

if ($action === 'request_status') {
    $transferId = (int)($input['transferId'] ?? $input['id'] ?? 0);
    if ($transferId <= 0) {
        sendJsonResponse(false, 'Missing transferId', [], 400);
    }
}

if ($action === 'transfer' && $callId === '' && !$conversationId) {
    sendJsonResponse(false, 'Missing callId or conversationId', [], 400);
}

$messages = [];
if ($conversationId) {
    try {
        $stmt = $pdo->prepare("
            SELECT id, sender_name, sender_type, message_text, created_at
            FROM chat_messages
            WHERE conversation_id = ?
            ORDER BY id ASC
        ");
        $stmt->execute([$conversationId]);
        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        $messages = [];
    }
}

$latestMessage = '';
if (!empty($messages)) {
    $lastMessage = end($messages);
    if (is_array($lastMessage)) {
        $latestMessage = trim((string)($lastMessage['message_text'] ?? ''));
    }
    reset($messages);
}

$config = [];
$configCandidates = [
    dirname(__DIR__) . '/ADMIN/api/config.local.php',
    dirname(__DIR__) . '/USERS/api/config.local.php',
    dirname(__DIR__) . '/config.local.php',
];
foreach ($configCandidates as $configPath) {
    if (!is_file($configPath)) {
        continue;
    }
    $loaded = require $configPath;
    if (is_array($loaded)) {
        $config = $loaded;
        break;
    }
}

$targetUrl = trim((string)(
    getenv('RESPONSE_TEAM_TRANSFER_URL')
    ?: ($config['RESPONSE_TEAM_TRANSFER_URL'] ?? '')
));
$apiKey = trim((string)(
    getenv('RESPONSE_TEAM_TRANSFER_API_KEY')
    ?: ($config['RESPONSE_TEAM_TRANSFER_API_KEY'] ?? '')
));
$statusUrl = trim((string)(
    getenv('RESPONSE_TEAM_STATUS_URL')
    ?: ($config['RESPONSE_TEAM_STATUS_URL'] ?? '')
));

if ($action === 'request_status') {
    $transferId = (int)($input['transferId'] ?? $input['id'] ?? 0);
    $stmt = $pdo->prepare("SELECT * FROM transfer_call_audit WHERE id = ? LIMIT 1");
    $stmt->execute([$transferId]);
    $transfer = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$transfer) {
        sendJsonResponse(false, 'Transfer record not found', [], 404);
    }

    $requestPayload = [
        'event' => 'emergency_transfer_status_request',
        'transferId' => $transferId,
        'callId' => $transfer['call_id'] ?? null,
        'conversationId' => $transfer['conversation_id'] ?? null,
        'requestedAt' => gmdate('c'),
    ];
    $externalStatus = null;
    $externalResponse = null;
    $externalHttpStatus = null;

    if ($statusUrl !== '' && filter_var($statusUrl, FILTER_VALIDATE_URL) && function_exists('curl_init')) {
        $headers = ['Content-Type: application/json', 'Accept: application/json'];
        if ($apiKey !== '') {
            $headers[] = 'Authorization: Bearer ' . $apiKey;
            $headers[] = 'X-API-Key: ' . $apiKey;
            $headers[] = 'X-ERS-API-Key: ' . $apiKey;
            $headers[] = 'X-ERS-Client: emergency-comm-alertaraqc';
        }
        $ch = curl_init($statusUrl);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_POSTFIELDS => json_encode($requestPayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 12,
        ]);
        $externalResponse = curl_exec($ch);
        $externalHttpStatus = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        $decoded = json_decode((string)$externalResponse, true);
        if (is_array($decoded)) {
            $externalStatus = strtolower(trim((string)($decoded['responseStatus'] ?? $decoded['status'] ?? '')));
        }
    }

    $nextStatus = $externalStatus ?: 'requested';
    $stmt = $pdo->prepare("
        UPDATE transfer_call_audit
        SET response_status = ?, status_requested_at = NOW(), status_updated_at = IF(? = 'requested', status_updated_at, NOW()), integration_status = COALESCE(?, integration_status), integration_response = COALESCE(?, integration_response)
        WHERE id = ?
    ");
    $stmt->execute([$nextStatus, $nextStatus, $externalHttpStatus, is_string($externalResponse) ? $externalResponse : null, $transferId]);

    sendJsonResponse(true, 'Response-team status requested.', [
        'transferId' => $transferId,
        'responseStatus' => $nextStatus,
        'integration' => [
            'configured' => $statusUrl !== '',
            'url' => $statusUrl !== '' ? $statusUrl : null,
            'httpStatus' => $externalHttpStatus,
            'response' => $externalResponse,
        ],
    ]);
}

$payload = [
    'event' => 'emergency_call_transfer',
    'callId' => $callId !== '' ? $callId : null,
    'room' => trim((string)($input['room'] ?? '')),
    'socketUrl' => trim((string)($input['socketUrl'] ?? '')),
    'socketPath' => trim((string)($input['socketPath'] ?? '/socket.io')),
    'emergencyType' => $input['emergencyType'] ?? null,
    'priority' => $input['priority'] ?? 'high',
    'description' => $input['description'] ?? ($input['details'] ?? $latestMessage),
    'latestMessage' => $latestMessage,
    'caller' => is_array($input['caller'] ?? null) ? $input['caller'] : null,
    'location' => is_array($input['location'] ?? null) ? $input['location'] : null,
    'conversationId' => $conversationId,
    'messages' => $messages,
    'requestedBy' => [
        'source' => $isAdminSession ? 'admin_session' : 'department_api',
        'department' => $deptName ?? null,
        'adminId' => $_SESSION['admin_user_id'] ?? null,
        'adminUsername' => $_SESSION['admin_username'] ?? null,
    ],
    'transferredAt' => gmdate('c'),
];

$payloadCaller = is_array($payload['caller'] ?? null) ? $payload['caller'] : [];
$payloadLocation = is_array($payload['location'] ?? null) ? $payload['location'] : [];
$payload['action'] = 'create_incident';
$payload['type'] = $payload['emergencyType'] ?: 'emergency';
$payload['incident_type'] = $payload['emergencyType'] ?: 'emergency';
$payload['caller_name'] = $payloadCaller['name'] ?? '';
$payload['caller_phone'] = $payloadCaller['phone'] ?? '';
$payload['location_address'] = $payloadCaller['address'] ?? ($payloadLocation['address'] ?? '');
$payload['latitude'] = $payloadLocation['lat'] ?? '';
$payload['longitude'] = $payloadLocation['lng'] ?? '';
$payload['title'] = 'Transferred emergency from AlertaraQC';
$payload['status'] = 'new';

$result = [
    'data' => $payload,
    'integration' => [
        'configured' => $targetUrl !== '',
        'url' => $targetUrl !== '' ? $targetUrl : null,
        'httpStatus' => null,
        'response' => null,
    ],
];

if ($targetUrl === '') {
    $auditPayload = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $caller = $payload['caller'] ?: [];
    $location = $payload['location'] ?: [];
    $stmt = $pdo->prepare("
        INSERT INTO transfer_call_audit
        (call_id, conversation_id, emergency_type, caller_name, caller_phone, caller_address, payload, integration_url, integration_status, integration_response, status, requested_by)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $payload['callId'],
        $conversationId,
        $payload['emergencyType'],
        $caller['name'] ?? null,
        $caller['phone'] ?? null,
        $caller['address'] ?? ($location['address'] ?? null),
        $auditPayload,
        null,
        null,
        null,
        'prepared',
        $payload['requestedBy']['adminUsername'] ?? $payload['requestedBy']['department'] ?? null,
    ]);
    if ($conversationId) {
        $pdo->prepare("UPDATE conversations SET status = 'closed', last_message = '[TRANSFERRED] Report transferred to response team', updated_at = NOW() WHERE conversation_id = ?")->execute([$conversationId]);
    }
    sendJsonResponse(
        true,
        'Transfer payload prepared. Configure RESPONSE_TEAM_TRANSFER_URL to notify the response team system.',
        $result
    );
}

if (!filter_var($targetUrl, FILTER_VALIDATE_URL)) {
    sendJsonResponse(false, 'Invalid RESPONSE_TEAM_TRANSFER_URL configuration', [], 500);
}

if (!function_exists('curl_init')) {
    sendJsonResponse(false, 'cURL is not available on this server', [], 500);
}

$transferResult = postResponseTeamPayload($targetUrl, $payload, $apiKey);
$responseBody = $transferResult['response'];
$curlError = $transferResult['error'];
$httpStatus = (int)$transferResult['httpStatus'];

$result['integration']['httpStatus'] = $httpStatus;
$result['integration']['response'] = $responseBody;
$result['integration']['format'] = $transferResult['format'] ?? null;
if (isset($transferResult['jsonAttempt'])) {
    $result['integration']['jsonAttempt'] = $transferResult['jsonAttempt'];
}
$status = !empty($transferResult['ok']) ? 'sent' : 'failed';

$auditPayload = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
$caller = $payload['caller'] ?: [];
$location = $payload['location'] ?: [];
$stmt = $pdo->prepare("
    INSERT INTO transfer_call_audit
    (call_id, conversation_id, emergency_type, caller_name, caller_phone, caller_address, payload, integration_url, integration_status, integration_response, status, requested_by)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");
$stmt->execute([
    $payload['callId'],
    $conversationId,
    $payload['emergencyType'],
    $caller['name'] ?? null,
    $caller['phone'] ?? null,
    $caller['address'] ?? ($location['address'] ?? null),
    $auditPayload,
    $targetUrl,
    $httpStatus ?: null,
    is_string($responseBody) ? $responseBody : null,
    $status,
    $payload['requestedBy']['adminUsername'] ?? $payload['requestedBy']['department'] ?? null,
]);

if ($conversationId && $status === 'sent') {
    $pdo->prepare("UPDATE conversations SET status = 'closed', last_message = '[TRANSFERRED] Report transferred to response team', updated_at = NOW() WHERE conversation_id = ?")->execute([$conversationId]);
}

if ($responseBody === false || $curlError !== '' || $httpStatus < 200 || $httpStatus >= 300) {
    sendJsonResponse(
        false,
        $curlError !== '' ? 'Transfer notification failed: ' . $curlError : 'Transfer notification failed with HTTP ' . $httpStatus,
        $result,
        502
    );
}

sendJsonResponse(true, 'Transfer notification sent.', $result);
