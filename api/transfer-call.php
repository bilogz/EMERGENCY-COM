<?php
$sessionConfigPath = dirname(__DIR__) . '/session-config.php';
if (is_file($sessionConfigPath)) {
    require_once $sessionConfigPath;
} elseif (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json; charset=utf-8');

$isAdminSession = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
$isUserSession = isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] === true;

if (!$isAdminSession && !$isUserSession) {
    require_once __DIR__ . '/auth.php';
} else {
    require_once __DIR__ . '/config.php';
    $deptName = $isAdminSession ? 'Admin Session' : 'User Emergency Call';
}

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
$chatLogicPath = dirname(__DIR__) . '/ADMIN/api/chat-logic.php';
if (is_file($chatLogicPath)) {
    require_once $chatLogicPath;
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

function responseTeamActionFromUrl(string $targetUrl): string {
    $query = parse_url($targetUrl, PHP_URL_QUERY);
    if (!is_string($query) || $query === '') {
        return '';
    }
    parse_str($query, $params);
    return trim((string)($params['action'] ?? ''));
}

function responseTeamAcceptedResponse($body): bool {
    $decoded = json_decode((string)$body, true);
    if (!is_array($decoded)) {
        return false;
    }
    foreach (['success', 'ok'] as $key) {
        if (!array_key_exists($key, $decoded)) {
            continue;
        }
        return $decoded[$key] === true
            || $decoded[$key] === 1
            || $decoded[$key] === '1'
            || strtolower((string)$decoded[$key]) === 'true';
    }
    return false;
}

function responseTeamFormPayload(array $payload, string $apiKey, string $action): array {
    $caller = is_array($payload['caller'] ?? null) ? $payload['caller'] : [];
    $location = is_array($payload['locationData'] ?? null)
        ? $payload['locationData']
        : (is_array($payload['location'] ?? null) ? $payload['location'] : []);
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
    $incidentPriority = is_array($payload['incidentPriority'] ?? null) ? $payload['incidentPriority'] : [];
    $priorityLevel = strtolower(trim((string)($incidentPriority['priority'] ?? $incidentPriority['level'] ?? $payload['priority'] ?? 'high')));
    $priorityScore = (int)($incidentPriority['score'] ?? 0);
    $locationText = trim((string)($payload['location_address'] ?? ($caller['address'] ?? ($location['address'] ?? ''))));
    if ($locationText === '' && isset($location['lat'], $location['lng'])) {
        $locationText = trim((string)$location['lat']) . ', ' . trim((string)$location['lng']);
    }
    if ($locationText === '') {
        $locationText = 'Location pending from transferred emergency';
    }
    $transferType = strtolower(trim((string)($payload['transfer_type'] ?? $payload['transferType'] ?? '')));
    if ($transferType === '') {
        $transferType = trim((string)($payload['callId'] ?? '')) !== '' && trim((string)($payload['room'] ?? '')) !== '' ? 'live_call' : 'report';
    }
    $event = trim((string)($payload['event'] ?? ''));
    if ($event === '') {
        $event = $transferType === 'live_call' ? 'emergency_call_transfer' : 'emergency_report_transfer';
    }

    return [
        'api_key' => $apiKey,
        'action' => $action !== '' ? $action : ($payload['action'] ?? 'create_incident'),
        'event' => $event,
        'transfer_type' => $transferType,
        'transferType' => $transferType,
        'call_id' => $payload['callId'] ?? '',
        'callId' => $payload['callId'] ?? '',
        'transfer_id' => $payload['transferId'] ?? ($payload['callId'] ?? ($payload['conversationId'] ?? '')),
        'source_system' => $payload['source_system'] ?? 'AlertaraQC Emergency Communication',
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
        'priority' => $priorityLevel,
        'incident_priority' => $priorityLevel,
        'incident_priority_level' => $priorityLevel,
        'incident_priority_score' => $priorityScore,
        'incident_priority_json' => json_encode($incidentPriority, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        'status' => 'new',
        'title' => 'Transferred emergency from AlertaraQC',
        'description' => $description,
        'caller_name' => $caller['name'] ?? '',
        'caller_phone' => $caller['phone'] ?? '',
        'caller_address' => $locationText,
        'location' => $locationText,
        'location_address' => $locationText,
        'latitude' => $location['lat'] ?? '',
        'longitude' => $location['lng'] ?? '',
        'transferred_at' => $payload['transferredAt'] ?? gmdate('c'),
        'payload_json' => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
    ];
}

function postResponseTeamPayload(string $targetUrl, array $payload, string $apiKey): array {
    $action = responseTeamActionFromUrl($targetUrl);
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
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_USERAGENT => 'AlertaraQC-EmergencyCom/1.0',
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT => 12,
    ]);
    $body = curl_exec($ch);
    $error = curl_error($ch);
    $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $jsonOk = $body !== false
        && $error === ''
        && $status >= 200
        && $status < 300
        && responseTeamAcceptedResponse($body);
    if ($jsonOk) {
        return [
            'format' => 'json',
            'httpStatus' => $status,
            'response' => $body,
            'error' => $error,
            'ok' => true,
        ];
    }

    $formHeaders = [
        'Accept: application/json',
        'Content-Type: application/x-www-form-urlencoded',
    ];
    if ($apiKey !== '') {
        $formHeaders[] = 'Authorization: Bearer ' . $apiKey;
        $formHeaders[] = 'X-API-Key: ' . $apiKey;
        $formHeaders[] = 'X-ERS-API-Key: ' . $apiKey;
        $formHeaders[] = 'X-ERS-Client: emergency-comm-alertaraqc';
    }
    $formBody = http_build_query(responseTeamFormPayload($payload, $apiKey, $action));
    $ch = curl_init($targetUrl);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => $formHeaders,
        CURLOPT_POSTFIELDS => $formBody,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_USERAGENT => 'AlertaraQC-EmergencyCom/1.0',
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT => 12,
    ]);
    $formResponse = curl_exec($ch);
    $formError = curl_error($ch);
    $formStatus = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    $formOk = $formResponse !== false
        && $formError === ''
        && $formStatus >= 200
        && $formStatus < 300
        && responseTeamAcceptedResponse($formResponse);

    return [
        'format' => 'form',
        'httpStatus' => $formStatus,
        'response' => $formResponse,
        'error' => $formError,
        'ok' => $formOk,
        'jsonAttempt' => [
            'httpStatus' => $status,
            'response' => $body,
            'error' => $error,
        ],
    ];
}

function transferNonEmptyValues(array $values): array {
    $clean = [];
    foreach ($values as $key => $value) {
        if ($value === null) {
            continue;
        }
        if (is_string($value) && trim($value) === '') {
            continue;
        }
        $clean[$key] = $value;
    }
    return $clean;
}

function transferBuildUserAddress(array $user): string {
    $address = trim((string)($user['address'] ?? ''));
    if ($address !== '') {
        return $address;
    }

    $parts = [];
    foreach (['house_number', 'street', 'barangay', 'district'] as $field) {
        $part = trim((string)($user[$field] ?? ''));
        if ($part !== '') {
            $parts[] = $part;
        }
    }
    return implode(', ', $parts);
}

function transferLoadSessionCaller(PDO $pdo): array {
    $userId = $_SESSION['user_id'] ?? null;
    $userType = strtolower(trim((string)($_SESSION['user_type'] ?? '')));
    $isRegistered = $userType === 'registered' && is_numeric($userId);

    $caller = [
        'id' => $userId,
        'user_id' => $userId,
        'name' => $_SESSION['user_name'] ?? null,
        'email' => $_SESSION['user_email'] ?? null,
        'phone' => $_SESSION['user_phone'] ?? null,
        'is_registered' => $isRegistered,
        'isGuest' => !$isRegistered,
    ];

    if (!$isRegistered) {
        return transferNonEmptyValues($caller);
    }

    try {
        $available = [];
        $columnsStmt = $pdo->query('SHOW COLUMNS FROM users');
        foreach (($columnsStmt ? $columnsStmt->fetchAll(PDO::FETCH_COLUMN, 0) : []) as $column) {
            $available[$column] = true;
        }

        $wanted = ['id', 'name', 'email', 'phone', 'nationality', 'district', 'barangay', 'house_number', 'street', 'address'];
        $select = array_values(array_filter($wanted, static function ($column) use ($available) {
            return isset($available[$column]);
        }));
        if (!empty($select)) {
            $quotedSelect = array_map(static function ($column) {
                return "`{$column}`";
            }, $select);
            $stmt = $pdo->prepare('SELECT ' . implode(', ', $quotedSelect) . ' FROM users WHERE id = ? LIMIT 1');
            $stmt->execute([(int)$userId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
            if (!empty($row)) {
                $caller = array_merge($caller, $row);
                $caller['id'] = $row['id'] ?? $userId;
                $caller['user_id'] = $row['id'] ?? $userId;
                $caller['is_registered'] = true;
                $caller['isGuest'] = false;
                $address = transferBuildUserAddress($row);
                if ($address !== '') {
                    $caller['address'] = $address;
                }
            }
        }
    } catch (Throwable $e) {
        error_log('transfer-call session caller lookup failed: ' . $e->getMessage());
    }

    return transferNonEmptyValues($caller);
}

ensureTransferAuditTable($pdo);

if ($isUserSession && !$isAdminSession && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJsonResponse(false, 'Forbidden: user sessions can only submit emergency transfers.', [], 403);
}

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

if ($isUserSession) {
    $sessionCaller = transferLoadSessionCaller($pdo);
    $clientCaller = is_array($input['caller'] ?? null) ? $input['caller'] : [];
    $sessionIsRegistered = !empty($sessionCaller['is_registered']);
    $input['caller'] = $sessionIsRegistered
        ? array_merge($clientCaller, $sessionCaller)
        : array_merge($sessionCaller, transferNonEmptyValues($clientCaller));
    if (!isset($input['userId']) && isset($sessionCaller['user_id'])) {
        $input['userId'] = $sessionCaller['user_id'];
    }
    if (!isset($input['location']) || !is_array($input['location'])) {
        $input['location'] = [];
    }
    if (empty($input['location']['address']) && !empty($input['caller']['address'])) {
        $input['location']['address'] = $input['caller']['address'];
    }
}

$action = strtolower(trim((string)($input['action'] ?? 'transfer')));

if ($isUserSession && !$isAdminSession && $action !== 'transfer') {
    sendJsonResponse(false, 'Forbidden: user sessions can only submit emergency transfers.', [], 403);
}

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
    $stmt = $pdo->prepare("SELECT conversation_id FROM transfer_call_audit WHERE id = ? LIMIT 1");
    $stmt->execute([$transferId]);
    $linkedConversationId = (int)($stmt->fetchColumn() ?: 0);
    if ($linkedConversationId > 0 && in_array($responseStatus, ['fake_call', 'resolved', 'cancelled', 'duplicate', 'unable_to_locate'], true)) {
        try {
            $pdo->prepare("UPDATE conversations SET status = 'resolved', updated_at = NOW() WHERE conversation_id = ?")
                ->execute([$linkedConversationId]);
        } catch (Throwable $e) {}
    }
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

$providedIncidentPriority = is_array($input['incidentPriority'] ?? null) ? $input['incidentPriority'] : [];
$descriptionInput = trim((string)($input['description'] ?? ($input['details'] ?? $latestMessage)));
$emergencyTypeInput = trim((string)($input['emergencyType'] ?? ''));
$requestedTransferType = strtolower(trim((string)($input['transfer_type'] ?? $input['transferType'] ?? '')));
$hasLiveTransferRoom = $callId !== '' && trim((string)($input['room'] ?? '')) !== '';
if ($requestedTransferType === '') {
    $requestedTransferType = $hasLiveTransferRoom ? 'live_call' : 'report';
}
if (!in_array($requestedTransferType, ['live_call', 'report'], true)) {
    $requestedTransferType = $hasLiveTransferRoom ? 'live_call' : 'report';
}
$transferEvent = trim((string)($input['event'] ?? ''));
if ($transferEvent === '') {
    $transferEvent = $requestedTransferType === 'live_call' ? 'emergency_call_transfer' : 'emergency_report_transfer';
}
$incidentPriority = [];
if (!empty($providedIncidentPriority) && isset($providedIncidentPriority['score'])) {
    $incidentPriority = $providedIncidentPriority;
    $incidentPriority['priority'] = strtolower((string)($incidentPriority['priority'] ?? $incidentPriority['level'] ?? 'low'));
    if (function_exists('twc_incident_priority_config')) {
        $meta = twc_incident_priority_config()[$incidentPriority['priority']] ?? null;
        if ($meta) {
            $incidentPriority['label'] = $incidentPriority['label'] ?? $meta['label'];
            $incidentPriority['color'] = $incidentPriority['color'] ?? $meta['color'];
            $incidentPriority['hex'] = $incidentPriority['hex'] ?? $meta['hex'];
        }
    }
} elseif (function_exists('twc_calculate_incident_priority')) {
    $incidentPriority = twc_calculate_incident_priority([
        'incident_type' => $emergencyTypeInput,
        'description' => $descriptionInput,
        'message' => $descriptionInput,
        'last_message' => $latestMessage,
    ]);
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
    'event' => $transferEvent,
    'transfer_type' => $requestedTransferType,
    'transferType' => $requestedTransferType,
    'callId' => $callId !== '' ? $callId : null,
    'transferId' => trim((string)($input['transferId'] ?? $input['transfer_id'] ?? '')) !== ''
        ? trim((string)($input['transferId'] ?? $input['transfer_id']))
        : ($callId !== '' ? $callId : ($conversationId ? 'conversation-' . $conversationId . '-' . time() : null)),
    'source_system' => 'AlertaraQC Emergency Communication',
    'room' => trim((string)($input['room'] ?? '')),
    'socketUrl' => trim((string)($input['socketUrl'] ?? '')),
    'socketPath' => trim((string)($input['socketPath'] ?? '/socket.io')),
    'emergencyType' => $emergencyTypeInput !== '' ? $emergencyTypeInput : null,
    'priority' => $input['priority'] ?? ($incidentPriority['priority'] ?? 'high'),
    'incidentPriority' => $incidentPriority,
    'description' => $descriptionInput !== ''
        ? $descriptionInput
        : ($latestMessage !== '' ? $latestMessage : 'Transferred emergency call/report from AlertaraQC two-way communication.'),
    'latestMessage' => $latestMessage,
    'caller' => is_array($input['caller'] ?? null) ? $input['caller'] : null,
    'locationData' => is_array($input['location'] ?? null) ? $input['location'] : null,
    'conversationId' => $conversationId,
    'messages' => $messages,
    'requestedBy' => [
        'source' => $isAdminSession ? 'admin_session' : ($isUserSession ? 'user_session' : 'department_api'),
        'department' => $deptName ?? null,
        'adminId' => $_SESSION['admin_user_id'] ?? null,
        'adminUsername' => $_SESSION['admin_username'] ?? null,
    ],
    'transferredAt' => gmdate('c'),
];
$payload['transfer_id'] = $payload['transferId'];

$payloadCaller = is_array($payload['caller'] ?? null) ? $payload['caller'] : [];
$payloadLocation = is_array($payload['locationData'] ?? null) ? $payload['locationData'] : [];
$payloadLocationText = trim((string)($payloadCaller['address'] ?? ($payloadLocation['address'] ?? '')));
if ($payloadLocationText === '' && isset($payloadLocation['lat'], $payloadLocation['lng'])) {
    $payloadLocationText = trim((string)$payloadLocation['lat']) . ', ' . trim((string)$payloadLocation['lng']);
}
if ($payloadLocationText === '') {
    $payloadLocationText = 'Location pending from transferred emergency';
}
$payload['action'] = responseTeamActionFromUrl($targetUrl) ?: 'create_incident';
$payload['createIncidentAction'] = 'create_incident';
$payload['type'] = $payload['emergencyType'] ?: 'emergency';
$payload['incident_type'] = $payload['emergencyType'] ?: 'emergency';
$payload['caller_name'] = $payloadCaller['name'] ?? '';
$payload['caller_phone'] = $payloadCaller['phone'] ?? '';
$payload['location'] = $payloadLocationText;
$payload['location_address'] = $payloadLocationText;
$payload['latitude'] = $payloadLocation['lat'] ?? '';
$payload['longitude'] = $payloadLocation['lng'] ?? '';
$payload['title'] = 'Transferred emergency from AlertaraQC';
$payload['status'] = 'new';
$payload['incident_priority'] = $incidentPriority['priority'] ?? $payload['priority'];
$payload['incident_priority_score'] = $incidentPriority['score'] ?? 0;

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
    $location = $payload['locationData'] ?: [];
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
if ($status === 'failed') {
    $logResponse = is_string($responseBody) ? substr($responseBody, 0, 1000) : '';
    $logJsonAttempt = '';
    if (isset($transferResult['jsonAttempt']) && is_array($transferResult['jsonAttempt'])) {
        $jsonAttemptResponse = $transferResult['jsonAttempt']['response'] ?? '';
        $logJsonAttempt = ' json_http=' . (int)($transferResult['jsonAttempt']['httpStatus'] ?? 0)
            . ' json_error=' . (string)($transferResult['jsonAttempt']['error'] ?? '')
            . ' json_response=' . (is_string($jsonAttemptResponse) ? substr($jsonAttemptResponse, 0, 500) : '');
    }
    error_log(
        'AlertaraQC transfer failed: format=' . (string)($transferResult['format'] ?? 'unknown')
        . ' http=' . $httpStatus
        . ' curl_error=' . (string)$curlError
        . ' response=' . $logResponse
        . $logJsonAttempt
    );
}

$auditPayload = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
$caller = $payload['caller'] ?: [];
$location = $payload['locationData'] ?: [];
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

if (empty($transferResult['ok']) || $responseBody === false || $curlError !== '' || $httpStatus < 200 || $httpStatus >= 300) {
    sendJsonResponse(
        false,
        $curlError !== '' ? 'Transfer notification failed: ' . $curlError : 'Transfer notification failed with HTTP ' . $httpStatus,
        $result,
        502
    );
}

sendJsonResponse(true, 'Transfer notification sent.', $result);
