<?php
/** Push notification helper using Firebase Cloud Messaging HTTP v1. */

require_once dirname(__DIR__, 2) . '/PHP/api/device_registry.php';

function pushHelperConfig(string $key): string {
    if (function_exists('getSecureConfig')) {
        $value = getSecureConfig($key, '');
        if (is_scalar($value) && trim((string)$value) !== '') return trim((string)$value);
    }
    if (file_exists(__DIR__ . '/config.local.php')) {
        $config = require __DIR__ . '/config.local.php';
        if (isset($config[$key]) && is_scalar($config[$key]) && trim((string)$config[$key]) !== '') {
            return trim((string)$config[$key]);
        }
    }
    $value = getenv($key);
    return is_string($value) ? trim($value) : '';
}

function pushHelperLoadFirebaseServiceAccount(?string &$error = null): ?array {
    $error = null;
    $json = pushHelperConfig('FIREBASE_SERVICE_ACCOUNT_JSON');
    $path = pushHelperConfig('FIREBASE_SERVICE_ACCOUNT_PATH');
    if ($json !== '') {
        $decoded = json_decode($json, true);
    } elseif ($path !== '' && is_readable($path)) {
        $decoded = json_decode((string)file_get_contents($path), true);
    } else {
        $error = 'Firebase service account is not configured.';
        return null;
    }
    if (!is_array($decoded) || empty($decoded['client_email']) || empty($decoded['private_key']) || empty($decoded['project_id'])) {
        $error = 'Firebase service-account configuration is incomplete.';
        return null;
    }
    return $decoded;
}

function pushHelperFirebaseAccessToken(array $serviceAccount, ?string &$error = null): ?string {
    static $cached = null;
    if (is_array($cached) && ($cached['expires_at'] ?? 0) > time() + 60) return $cached['token'];

    $autoload = dirname(__DIR__, 2) . '/VENDOR/autoload.php';
    if (!is_readable($autoload)) {
        $error = 'Firebase JWT dependency is unavailable.';
        return null;
    }
    require_once $autoload;

    $now = time();
    $claims = [
        'iss' => $serviceAccount['client_email'],
        'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
        'aud' => 'https://oauth2.googleapis.com/token',
        'iat' => $now,
        'exp' => $now + 3600
    ];
    try {
        $jwt = Firebase\JWT\JWT::encode($claims, $serviceAccount['private_key'], 'RS256', $serviceAccount['private_key_id'] ?? null);
    } catch (Throwable $e) {
        $error = 'Unable to sign Firebase access token.';
        return null;
    }

    $ch = curl_init('https://oauth2.googleapis.com/token');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POSTFIELDS => http_build_query(['grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer', 'assertion' => $jwt]),
        CURLOPT_TIMEOUT => 20,
        CURLOPT_SSL_VERIFYPEER => true
    ]);
    $response = curl_exec($ch);
    $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if ($response === false) $error = curl_error($ch);
    curl_close($ch);

    $decoded = is_string($response) ? json_decode($response, true) : null;
    if ($httpCode !== 200 || empty($decoded['access_token'])) {
        $error = $error ?: 'Firebase OAuth authentication failed.';
        return null;
    }
    $cached = ['token' => $decoded['access_token'], 'expires_at' => $now + (int)($decoded['expires_in'] ?? 3600)];
    return $cached['token'];
}

function pushHelperSendExpo(string $token, string $title, string $message, array $data, ?string &$error = null): bool {
    $payload = [
        'to' => $token,
        'sound' => 'default',
        'priority' => 'high',
        'channelId' => 'emergency-alerts-v2',
        'title' => $title,
        'body' => $message,
        'data' => $data
    ];
    $ch = curl_init('https://exp.host/--/api/v2/push/send');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => ['Accept: application/json', 'Content-Type: application/json'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        CURLOPT_TIMEOUT => 25,
        CURLOPT_SSL_VERIFYPEER => true
    ]);
    $response = curl_exec($ch);
    $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if ($response === false) $error = curl_error($ch);
    curl_close($ch);
    $decoded = is_string($response) ? json_decode($response, true) : null;
    $ticket = $decoded['data'] ?? null;
    if ($httpCode >= 200 && $httpCode < 300 && is_array($ticket) && ($ticket['status'] ?? '') === 'ok') return true;
    $error = $error ?: (string)($ticket['message'] ?? $decoded['errors'][0]['message'] ?? "Expo Push HTTP {$httpCode}");
    return false;
}

function pushHelperSendFcmV1(string $token, string $title, string $message, array $data, ?string &$error = null): bool {
    if (preg_match('/^(ExponentPushToken|ExpoPushToken)\[[^\]]+\]$/', $token)) {
        return pushHelperSendExpo($token, $title, $message, $data, $error);
    }
    if (!function_exists('curl_init')) {
        $error = 'cURL is unavailable.';
        return false;
    }
    $serviceAccount = pushHelperLoadFirebaseServiceAccount($error);
    if (!$serviceAccount) return false;
    $accessToken = pushHelperFirebaseAccessToken($serviceAccount, $error);
    if (!$accessToken) return false;

    $stringData = [];
    foreach ($data as $key => $value) {
        $stringData[(string)$key] = is_scalar($value) ? (string)$value : json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
    $payload = ['message' => [
        'token' => $token,
        'notification' => ['title' => $title, 'body' => $message],
        'data' => $stringData,
        'android' => [
            'priority' => 'HIGH',
            'ttl' => '86400s',
            'notification' => [
                'channel_id' => 'emergency-alerts-v2',
                'sound' => 'default',
                'default_vibrate_timings' => true,
                'visibility' => 'PUBLIC',
                'notification_priority' => 'PRIORITY_HIGH',
                'click_action' => 'OPEN_EMERGENCY_ALERT'
            ]
        ],
        'apns' => [
            'headers' => ['apns-priority' => '10'],
            'payload' => ['aps' => ['sound' => 'default', 'content-available' => 1, 'interruption-level' => 'time-sensitive']]
        ]
    ]];

    $url = 'https://fcm.googleapis.com/v1/projects/' . rawurlencode($serviceAccount['project_id']) . '/messages:send';
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $accessToken, 'Content-Type: application/json; charset=utf-8'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        CURLOPT_TIMEOUT => 25,
        CURLOPT_SSL_VERIFYPEER => true
    ]);
    $response = curl_exec($ch);
    $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if ($response === false) $error = curl_error($ch);
    curl_close($ch);
    $decoded = is_string($response) ? json_decode($response, true) : null;
    if ($httpCode >= 200 && $httpCode < 300 && !empty($decoded['name'])) return true;
    $status = (string)($decoded['error']['status'] ?? '');
    $messageText = (string)($decoded['error']['message'] ?? "FCM HTTP {$httpCode}");
    $error = $error ?: trim($status . ': ' . $messageText, ': ');
    return false;
}

function sendPushNotification($userId, $title, $message, $data = [], $alertId = null) {
    global $pdo;
    if ($pdo === null) return false;

    try {
        $devices = [];
        $deviceTable = resolveDeviceRegistryTable($pdo);
        $stmt = $pdo->prepare("SELECT device_id, fcm_token, push_token FROM {$deviceTable} WHERE user_id = ? AND is_active = 1 AND ((fcm_token IS NOT NULL AND fcm_token <> '') OR (push_token IS NOT NULL AND push_token <> ''))");
        $stmt->execute([(int)$userId]);
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $token = trim((string)($row['fcm_token'] ?: $row['push_token']));
            if ($token !== '') $devices[$token] = (string)($row['device_id'] ?? '');
        }

        $appTable = ensureAppNotificationDevicesTable($pdo);
        $stmt = $pdo->prepare("SELECT device_id, fcm_token, push_token FROM {$appTable} WHERE user_id = ? AND is_active = 1 AND notification_permission = 'granted' AND ((fcm_token IS NOT NULL AND fcm_token <> '') OR (push_token IS NOT NULL AND push_token <> ''))");
        $stmt->execute([(int)$userId]);
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $token = trim((string)($row['fcm_token'] ?: $row['push_token']));
            if ($token !== '') $devices[$token] = (string)($row['device_id'] ?? '');
        }

        if (!$devices) return false;
        $successCount = 0;
        $payloadData = array_merge(['alert_id' => (string)$alertId, 'type' => 'alert', 'click_action' => 'OPEN_EMERGENCY_ALERT'], $data);
        foreach ($devices as $token => $deviceId) {
            $error = null;
            if (pushHelperSendFcmV1($token, (string)$title, (string)$message, $payloadData, $error)) {
                $successCount++;
                logPushNotification($userId, $deviceId, $title, $message, $alertId, 'success');
            } else {
                error_log("Push Notification: Failed for device {$deviceId}: " . (string)$error);
                logPushNotification($userId, $deviceId, $title, $message, $alertId, 'failed');
            }
        }
        return $successCount > 0;
    } catch (Throwable $e) {
        error_log('Push Notification Error: ' . $e->getMessage());
        return false;
    }
}

function sendBulkPushNotifications($userIds, $title, $message, $data = [], $alertId = null) {
    $successCount = 0;
    foreach ($userIds as $userId) {
        if (sendPushNotification($userId, $title, $message, $data, $alertId)) $successCount++;
    }
    return $successCount;
}

function logPushNotification($userId, $deviceId, $title, $message, $alertId, $status) {
    global $pdo;
    if ($pdo === null) return;
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE 'notification_logs'");
        if ($stmt->rowCount() === 0) return;
        $stmt = $pdo->prepare("INSERT INTO notification_logs (channel, message, recipient, recipients, priority, status, sent_at, sent_by, ip_address) VALUES ('push', ?, ?, ?, 'high', ?, NOW(), 'system', '127.0.0.1')");
        $recipient = "User $userId (Device: $deviceId)";
        $stmt->execute([$message, $recipient, $recipient, $status]);
    } catch (Throwable $e) {
        error_log('Failed to log push notification: ' . $e->getMessage());
    }
}
?>


