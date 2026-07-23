<?php
/**
 * Notification Background Worker
 * Processes the notification_queue in batches
 */

require_once 'db_connect.php';

if (php_sapi_name() !== 'cli') {
    if (session_status() === PHP_SESSION_NONE) session_start();
    header('Content-Type: application/json; charset=utf-8');
    if (empty($_SESSION['admin_logged_in'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Unauthorized access denied.']);
        exit;
    }
}

// Batch size per run
$batchSize = 100;

/**
 * Ensure queue table exists for worker runs on fresh deployments.
 */
function ensureNotificationQueueTableForWorker(PDO $pdo): bool {
    try {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS notification_queue (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                log_id BIGINT UNSIGNED NOT NULL,
                alert_id BIGINT UNSIGNED NULL,
                recipient_id BIGINT UNSIGNED NULL,
                recipient_type VARCHAR(40) NOT NULL DEFAULT 'unknown',
                recipient_value VARCHAR(255) NOT NULL DEFAULT '',
                channel VARCHAR(20) NOT NULL DEFAULT 'push',
                title VARCHAR(255) NOT NULL DEFAULT '',
                message TEXT NULL,
                status VARCHAR(20) NOT NULL DEFAULT 'pending',
                delivery_status VARCHAR(20) NULL,
                error_message TEXT NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                processed_at DATETIME NULL,
                delivered_at DATETIME NULL,
                INDEX idx_queue_status_created (status, created_at),
                INDEX idx_queue_log_id (log_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        return true;
    } catch (PDOException $e) {
        error_log("Worker queue table ensure failed: " . $e->getMessage());
        return false;
    }
}

try {
    if (!ensureNotificationQueueTableForWorker($pdo)) {
        throw new PDOException('notification_queue table is unavailable.');
    }

    // Ensure minimal columns exist for progress tracking (best-effort, backward compatible)
    try {
        $logColsStmt = $pdo->query("SHOW COLUMNS FROM notification_logs");
        $logCols = $logColsStmt->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('response', $logCols, true)) {
            try { $pdo->exec("ALTER TABLE notification_logs ADD COLUMN response TEXT NULL"); } catch (PDOException $e) {}
        }
        if (!in_array('status', $logCols, true)) {
            try { $pdo->exec("ALTER TABLE notification_logs ADD COLUMN status VARCHAR(20) DEFAULT 'pending'"); } catch (PDOException $e) {}
        }
    } catch (PDOException $e) {
        // ignore
    }

    $queueCols = [];
    $queueHasCreatedAt = false;
    $queueHasDeliveryStatus = false;
    $queueHasErrorMessage = false;
    $queueHasProcessedAt = false;
    $queueHasDeliveredAt = false;
    try {
        $qColsStmt = $pdo->query("SHOW COLUMNS FROM notification_queue");
        $queueCols = $qColsStmt->fetchAll(PDO::FETCH_COLUMN);
        $queueHasCreatedAt = in_array('created_at', $queueCols, true);
        $queueHasDeliveryStatus = in_array('delivery_status', $queueCols, true);
        $queueHasErrorMessage = in_array('error_message', $queueCols, true);
        $queueHasProcessedAt = in_array('processed_at', $queueCols, true);
        $queueHasDeliveredAt = in_array('delivered_at', $queueCols, true);
        if (!in_array('alert_id', $queueCols, true)) {
            try { $pdo->exec("ALTER TABLE notification_queue ADD COLUMN alert_id BIGINT UNSIGNED NULL AFTER log_id"); } catch (PDOException $e) {}
        }
    } catch (PDOException $e) {
        $queueCols = [];
    }

    // Get pending messages
    $stmt = $pdo->prepare(
        "SELECT * FROM notification_queue 
        WHERE status = 'pending' 
        ORDER BY " . ($queueHasCreatedAt ? "created_at" : "id") . " ASC 
        LIMIT ?"
    );
    $stmt->bindValue(1, $batchSize, PDO::PARAM_INT);
    $stmt->execute();
    $jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($jobs)) {
        if (php_sapi_name() === 'cli') echo "No pending jobs.\n";
        else echo json_encode(['success' => true, 'processed' => 0]);
        return;
    }

    foreach ($jobs as $job) {
        $success = false;
        $error = null;

        // Atomically claim the job so overlapping web/cron workers cannot send it twice.
        $claim = $pdo->prepare("UPDATE notification_queue SET status = 'processing' WHERE id = ? AND status = 'pending'");
        $claim->execute([$job['id']]);
        if ($claim->rowCount() !== 1) continue;

        try {
            switch ($job['channel']) {
                case 'sms':
                    $success = sendSMS($job['recipient_value'], $job['message'], $error);
                    break;
                case 'email':
                    $success = sendEmail($job['recipient_value'], $job['title'], $job['message'], $error);
                    break;
                case 'push':
                    $alertMeta = getWorkerAlertMetadata($pdo, (int)($job['alert_id'] ?? 0));
                    $success = sendFCM($job['recipient_value'], [
                        'title' => $job['title'],
                        'body' => $job['message'],
                        'alert_id' => (string)($job['alert_id'] ?? ''),
                        'severity' => $alertMeta['severity'],
                        'category' => $alertMeta['category']
                    ], $error);
                    break;
                case 'pa':
                    $success = broadcastPA($job['message']);
                    break;
                default:
                    $error = "Unknown channel: " . $job['channel'];
            }
        } catch (Throwable $e) {
            $error = $e->getMessage();
        }

        // Update job status (only touch columns that exist)
        $status = $success ? 'sent' : 'failed';
        $set = ["status = ?"];
        $params = [$status];

        if ($queueHasDeliveryStatus) {
            $set[] = "delivery_status = ?";
            $params[] = $success ? 'delivered' : 'failed';
        }
        if ($queueHasErrorMessage) {
            $set[] = "error_message = ?";
            $params[] = $error;
        }
        if ($queueHasProcessedAt) {
            $set[] = "processed_at = NOW()";
        }
        if ($queueHasDeliveredAt) {
            $set[] = "delivered_at = " . ($success ? "NOW()" : "NULL");
        }

        $updateStmt = $pdo->prepare("UPDATE notification_queue SET " . implode(', ', $set) . " WHERE id = ?");
        $params[] = $job['id'];
        $updateStmt->execute($params);

        // Update master log progress
        updateLogProgress($pdo, $job['log_id']);
    }

    // Avoid breaking JSON callers; output is only for CLI usage.
    if (php_sapi_name() === 'cli') {
        echo "Processed " . count($jobs) . " jobs.\n";
    } else {
        echo json_encode(['success' => true, 'processed' => count($jobs)]);
    }

} catch (PDOException $e) {
    error_log("Worker Error: " . $e->getMessage());
    if (php_sapi_name() === 'cli') {
        echo "Worker Error: " . $e->getMessage() . "\n";
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Notification worker failed.']);
    }
}

/**
 * Update the master notification_logs entry based on queue progress
 */
function updateLogProgress($pdo, $logId) {
    $stmt = $pdo->prepare(
        "SELECT status, COUNT(*) as count 
        FROM notification_queue 
        WHERE log_id = ? 
        GROUP BY status"
    );
    $stmt->execute([$logId]);
    $stats = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    $pending = ($stats['pending'] ?? 0) + ($stats['processing'] ?? 0);
    $sent = $stats['sent'] ?? 0;
    $failed = $stats['failed'] ?? 0;
    $total = $pending + $sent + $failed;

    $status = 'completed';
    if ($pending > 0) {
        $status = 'sending';
    }

    // Build backward-compatible update (response may not exist)
    $logCols = [];
    try {
        $logColsStmt = $pdo->query("SHOW COLUMNS FROM notification_logs");
        $logCols = $logColsStmt->fetchAll(PDO::FETCH_COLUMN);
    } catch (PDOException $e) {
        $logCols = [];
    }
    
    $response = json_encode([
        'total' => $total,
        'sent' => $sent,
        'failed' => $failed,
        'pending' => $pending,
        'progress' => $total > 0 ? round((($sent + $failed) / $total) * 100) : 0
    ]);

    if (in_array('response', $logCols, true)) {
        $updateStmt = $pdo->prepare("UPDATE notification_logs SET status = ?, response = ? WHERE id = ?");
        $updateStmt->execute([$status, $response, $logId]);
    } else {
        $updateStmt = $pdo->prepare("UPDATE notification_logs SET status = ? WHERE id = ?");
        $updateStmt->execute([$status, $logId]);
    }
}

/**
 * PLACEHOLDER: SMS Dispatch
 */
function sendSMS($phone, $message, &$error = null) {
    $error = 'SMS gateway is not configured.';
    return false;
}

/**
 * PLACEHOLDER: Email Dispatch
 */
function sendEmail($email, $subject, $body, &$error = null) {
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid recipient email address.';
        return false;
    }
    require_once dirname(__DIR__, 2) . '/USERS/lib/mail.php';
    return sendSMTPMail($email, $subject, $body, false, $error);
}

function getWorkerAlertMetadata(PDO $pdo, int $alertId): array {
    static $cache = [];
    if ($alertId <= 0) return ['severity' => 'high', 'category' => 'Emergency Alert'];
    if (isset($cache[$alertId])) return $cache[$alertId];
    try {
        $stmt = $pdo->prepare("SELECT severity, category FROM alerts WHERE id = ? LIMIT 1");
        $stmt->execute([$alertId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        return $cache[$alertId] = [
            'severity' => strtolower((string)($row['severity'] ?? 'high')),
            'category' => (string)($row['category'] ?? 'Emergency Alert')
        ];
    } catch (Throwable $e) {
        return $cache[$alertId] = ['severity' => 'high', 'category' => 'Emergency Alert'];
    }
}

/** Load Firebase service-account credentials without exposing them to logs. */
function loadFirebaseServiceAccount(&$error = null): ?array {
    $error = null;
    $json = function_exists('getSecureConfig') ? getSecureConfig('FIREBASE_SERVICE_ACCOUNT_JSON', '') : getenv('FIREBASE_SERVICE_ACCOUNT_JSON');
    $path = function_exists('getSecureConfig') ? getSecureConfig('FIREBASE_SERVICE_ACCOUNT_PATH', '') : getenv('FIREBASE_SERVICE_ACCOUNT_PATH');
    if (is_string($json) && trim($json) !== '') {
        $decoded = json_decode($json, true);
    } elseif (is_string($path) && trim($path) !== '' && is_readable($path)) {
        $decoded = json_decode((string)file_get_contents($path), true);
    } else {
        return null;
    }
    if (!is_array($decoded) || empty($decoded['client_email']) || empty($decoded['private_key']) || empty($decoded['project_id'])) {
        $error = 'Firebase service-account configuration is incomplete.';
        return null;
    }
    return $decoded;
}

function getFirebaseAccessToken(array $serviceAccount, &$error = null): ?string {
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

/** Send an alert through the supported FCM HTTP v1 API. */
function sendFCM($token, $payload, &$error = null) {
    if (!function_exists('curl_init')) {
        $error = 'cURL is unavailable.';
        return false;
    }
    $serviceAccount = loadFirebaseServiceAccount($configError);
    if (!$serviceAccount) {
        $error = $configError ?: 'Firebase service account is not configured.';
        return false;
    }
    $accessToken = getFirebaseAccessToken($serviceAccount, $error);
    if (!$accessToken) return false;

    $severity = strtolower((string)($payload['severity'] ?? 'high'));
    $isCritical = in_array($severity, ['critical', 'high'], true);
    $data = [
        'type' => 'emergency_alert',
        'alert_id' => (string)($payload['alert_id'] ?? ''),
        'severity' => $severity,
        'category' => (string)($payload['category'] ?? 'Emergency Alert'),
        'title' => (string)($payload['title'] ?? 'Emergency Alert'),
        'body' => (string)($payload['body'] ?? ''),
        'click_action' => 'OPEN_EMERGENCY_ALERT'
    ];
    $message = ['message' => [
        'token' => (string)$token,
        'notification' => ['title' => $data['title'], 'body' => $data['body']],
        'data' => $data,
        'android' => [
            'priority' => 'HIGH',
            'ttl' => '86400s',
            'notification' => [
                'channel_id' => $isCritical ? 'emergency_critical_alerts' : 'emergency_alerts',
                'sound' => 'default',
                'default_vibrate_timings' => true,
                'visibility' => 'PUBLIC',
                'notification_priority' => $isCritical ? 'PRIORITY_MAX' : 'PRIORITY_HIGH',
                'click_action' => 'OPEN_EMERGENCY_ALERT'
            ]
        ],
        'apns' => [
            'headers' => ['apns-priority' => '10'],
            'payload' => ['aps' => ['sound' => 'default', 'content-available' => 1, 'interruption-level' => $isCritical ? 'time-sensitive' : 'active']]
        ]
    ]];
    $url = 'https://fcm.googleapis.com/v1/projects/' . rawurlencode($serviceAccount['project_id']) . '/messages:send';
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $accessToken, 'Content-Type: application/json; charset=utf-8'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POSTFIELDS => json_encode($message, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        CURLOPT_TIMEOUT => 25,
        CURLOPT_SSL_VERIFYPEER => true
    ]);
    $response = curl_exec($ch);
    $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if ($response === false) $error = curl_error($ch);
    curl_close($ch);
    $decoded = is_string($response) ? json_decode($response, true) : null;
    if ($httpCode >= 200 && $httpCode < 300 && !empty($decoded['name'])) return true;
    $error = $error ?: (string)($decoded['error']['message'] ?? "FCM HTTP {$httpCode}");
    return false;
}

/**
 * PLACEHOLDER: PA System Dispatch
 */
function broadcastPA($message) {
    // In production: Integration with IP-based PA system
    // error_log("PA Broadcast: $message");
    return true;
}
