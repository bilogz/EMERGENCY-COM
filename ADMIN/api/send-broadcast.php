<?php
/**
 * Send Broadcast Controller
 * Handles audience selection and inserts jobs into the notification queue
 */

// 1. Prevent any accidental output (warnings, notices) from breaking JSON
ob_start();
require_once dirname(__DIR__, 2) . '/PHP/api/device_registry.php';

// 2. Set strict JSON header
header('Content-Type: application/json; charset=utf-8');

/**
 * Normalize language code and map aliases.
 */
function normalizeDispatchLanguage($language): string {
    $lang = strtolower(trim((string)$language));
    if ($lang === 'tl') {
        $lang = 'fil';
    }
    if ($lang !== '' && !preg_match('/^[a-z0-9_-]{2,15}$/', $lang)) {
        return '';
    }
    return $lang;
}

function normalizeNotificationDispatchLanguage($language): string {
    $lang = strtolower(trim((string)$language));
    if ($lang === 'tagalog' || $lang === 'filipino' || $lang === 'tl') {
        return 'fil';
    }
    if ($lang === 'both') {
        return 'both';
    }
    return $lang === 'en' ? 'en' : '';
}

function dispatchSystemNotificationLanguage(string $preference): string {
    // Android/FCM must receive one user-visible notification. For Both, use
    // English as the system banner and keep Filipino attached to the alert ID.
    return $preference === 'fil' ? 'fil' : 'en';
}

function dispatchEnsureNotificationLanguageColumn(PDO $pdo): void {
    try {
        $exists = $pdo->query("SHOW TABLES LIKE 'user_preferences'");
        if (!$exists || !$exists->fetch()) return;
        $colsStmt = $pdo->query("SHOW COLUMNS FROM user_preferences");
        $cols = $colsStmt ? $colsStmt->fetchAll(PDO::FETCH_COLUMN) : [];
        if (!in_array('notification_language', $cols, true)) {
            $pdo->exec("ALTER TABLE user_preferences ADD COLUMN notification_language VARCHAR(10) NOT NULL DEFAULT 'en' AFTER preferred_language");
        }
    } catch (Throwable $e) {
        error_log('Unable to ensure notification_language preference column: ' . $e->getMessage());
    }
}

/**
 * Resolve recipient language using stored preferences.
 * Priority:
 * 1) user_preferences.notification_language (en, fil/tl, both)
 * 2) subscriptions.preferred_language
 * 3) user_preferences.preferred_language
 * 4) users.preferred_language
 * 5) fallback "en"
 */
function resolveRecipientLanguage(PDO $pdo, int $userId): string {
    static $cache = [];

    if ($userId <= 0) {
        return 'en';
    }
    if (isset($cache[$userId])) {
        return $cache[$userId];
    }

    $queries = [
        [
            "SELECT notification_language AS preferred_language
             FROM user_preferences
             WHERE user_id = ?
               AND notification_language IS NOT NULL
               AND notification_language <> ''
             ORDER BY id DESC
             LIMIT 1",
            [$userId]
        ],
        [
            "SELECT preferred_language
             FROM subscriptions
             WHERE user_id = ? AND status = 'active'
               AND preferred_language IS NOT NULL
               AND preferred_language <> ''
             ORDER BY id DESC
             LIMIT 1",
            [$userId]
        ],
        [
            "SELECT preferred_language
             FROM user_preferences
             WHERE user_id = ?
               AND preferred_language IS NOT NULL
               AND preferred_language <> ''
             ORDER BY id DESC
             LIMIT 1",
            [$userId]
        ],
        [
            "SELECT preferred_language
             FROM users
             WHERE id = ?
               AND preferred_language IS NOT NULL
               AND preferred_language <> ''
             LIMIT 1",
            [$userId]
        ],
    ];

    foreach ($queries as [$sql, $params]) {
        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row && isset($row['preferred_language'])) {
                $lang = normalizeNotificationDispatchLanguage($row['preferred_language']);
                if ($lang === '') {
                    $lang = normalizeDispatchLanguage($row['preferred_language']);
                }
                if ($lang !== '') {
                    $cache[$userId] = $lang;
                    return $lang;
                }
            }
        } catch (Throwable $e) {
            // Backward-compatible: continue to next lookup.
        }
    }

    $cache[$userId] = 'en';
    return 'en';
}

/**
 * Ensure notification_queue exists and has required columns.
 * Backward compatible: only adds missing columns/indexes.
 */
function ensureNotificationQueueTable(PDO $pdo): void {
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
                INDEX idx_queue_log_id (log_id),
                INDEX idx_queue_channel_status (channel, status)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    } catch (PDOException $e) {
        throw new Exception('Unable to initialize notification queue table: ' . $e->getMessage());
    }

    $requiredColumns = [
        'log_id' => "BIGINT UNSIGNED NOT NULL DEFAULT 0",
        'alert_id' => "BIGINT UNSIGNED NULL",
        'recipient_id' => "BIGINT UNSIGNED NULL",
        'recipient_type' => "VARCHAR(40) NOT NULL DEFAULT 'unknown'",
        'recipient_value' => "VARCHAR(255) NOT NULL DEFAULT ''",
        'channel' => "VARCHAR(20) NOT NULL DEFAULT 'push'",
        'title' => "VARCHAR(255) NOT NULL DEFAULT ''",
        'message' => "TEXT NULL",
        'status' => "VARCHAR(20) NOT NULL DEFAULT 'pending'",
        'delivery_status' => "VARCHAR(20) NULL",
        'error_message' => "TEXT NULL",
        'created_at' => "TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP",
        'processed_at' => "DATETIME NULL",
        'delivered_at' => "DATETIME NULL"
    ];

    try {
        $colsStmt = $pdo->query("SHOW COLUMNS FROM notification_queue");
        $existingCols = $colsStmt ? $colsStmt->fetchAll(PDO::FETCH_COLUMN) : [];
    } catch (PDOException $e) {
        throw new Exception('Unable to inspect notification queue schema: ' . $e->getMessage());
    }

    foreach ($requiredColumns as $name => $definition) {
        if (in_array($name, $existingCols, true)) {
            continue;
        }
        try {
            $pdo->exec("ALTER TABLE notification_queue ADD COLUMN {$name} {$definition}");
        } catch (PDOException $e) {
            throw new Exception("Missing notification queue column '{$name}' and failed to add it: " . $e->getMessage());
        }
    }
}

/** Return a column map only when a table is actually readable (SHOW TABLES can list broken tables). */
function dispatchReadableTableColumns(PDO $pdo, string $table): array {
    if (!preg_match('/^[A-Za-z0-9_]+$/', $table)) return [];
    try {
        $pdo->query("SELECT 1 FROM `{$table}` LIMIT 1");
        $stmt = $pdo->query("SHOW COLUMNS FROM `{$table}`");
        return array_fill_keys($stmt ? $stmt->fetchAll(PDO::FETCH_COLUMN) : [], true);
    } catch (Throwable $e) {
        error_log("Mass notification table unavailable ({$table}): " . $e->getMessage());
        return [];
    }
}

/** Load every usable active push token without duplicating SMS/email recipients. */
function dispatchLoadPushTokens(PDO $pdo, array $userIds): array {
    if (empty($userIds)) return [];
    $deviceTable = resolveDeviceRegistryTable($pdo);
    $columns = dispatchReadableTableColumns($pdo, $deviceTable);
    $tokenColumns = array_values(array_filter(['fcm_token', 'push_token'], fn($c) => isset($columns[$c])));
    if (!isset($columns['user_id']) || empty($tokenColumns)) return [];

    $ids = array_values(array_unique(array_filter(array_map('intval', $userIds), fn($id) => $id > 0)));
    if (empty($ids)) return [];
    $where = array_fill(0, count($ids), '?');
    $activeSql = isset($columns['is_active']) ? ' AND is_active = 1' : '';
    $select = implode(', ', array_map(fn($c) => "`{$c}`", $tokenColumns));
    $stmt = $pdo->prepare("SELECT user_id, {$select} FROM {$deviceTable} WHERE user_id IN (" . implode(',', $where) . "){$activeSql}");
    $stmt->execute($ids);

    $result = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $userId = (int)$row['user_id'];
        foreach ($tokenColumns as $column) {
            $token = trim((string)($row[$column] ?? ''));
            if ($token !== '') $result[$userId][$token] = true;
        }
    }
    return array_map('array_keys', $result);
}

function dispatchSecureConfigValue(string $key): string {
    if (function_exists('getSecureConfig')) {
        $value = getSecureConfig($key, '');
        if (is_scalar($value) && trim((string)$value) !== '') return trim((string)$value);
    }
    $value = getenv($key);
    return is_string($value) ? trim($value) : '';
}

/** Load every opted-in app installation, including guest-mode devices. */
function dispatchLoadBroadcastPushDevices(PDO $pdo, array $userIds): array {
    $devices = [];
    foreach (dispatchLoadPushTokens($pdo, $userIds) as $userId => $tokens) {
        foreach ($tokens as $token) {
            $devices[$token] = ['token' => $token, 'user_id' => (int)$userId, 'token_type' => 'fcm'];
        }
    }

    $table = ensureAppNotificationDevicesTable($pdo);
    $columns = dispatchReadableTableColumns($pdo, $table);
    $hasFcmToken = isset($columns['fcm_token']);
    $select = $hasFcmToken ? 'user_id, device_id, push_token, fcm_token, token_type' : 'user_id, device_id, push_token, token_type';
    $condition = $hasFcmToken
        ? "((fcm_token IS NOT NULL AND fcm_token <> '') OR (push_token IS NOT NULL AND push_token <> ''))"
        : "(push_token IS NOT NULL AND push_token <> '')";
    $stmt = $pdo->query("SELECT {$select}
        FROM {$table}
        WHERE is_active = 1
          AND notification_permission = 'granted'
          AND {$condition}");
    foreach ($stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [] as $row) {
        $fcmToken = trim((string)($row['fcm_token'] ?? ''));
        $expoToken = trim((string)($row['push_token'] ?? ''));
        $token = $fcmToken !== '' ? $fcmToken : $expoToken;
        if ($token === '') continue;
        $devices[$token] = [
            'token' => $token,
            'user_id' => !empty($row['user_id']) ? (int)$row['user_id'] : null,
            'device_id' => (string)($row['device_id'] ?? ''),
            'token_type' => $fcmToken !== '' ? 'fcm' : (string)($row['token_type'] ?? 'expo')
        ];
    }

    $testToken = dispatchSecureConfigValue('FCM_TEST_TOKEN');
    $testDeviceId = dispatchSecureConfigValue('FCM_TEST_DEVICE_ID');
    $testUserId = (int)dispatchSecureConfigValue('FCM_TEST_USER_ID');
    if ($testToken !== '' || $testDeviceId !== '' || $testUserId > 0) {
        $devices = array_filter($devices, function ($device) use ($testToken, $testDeviceId, $testUserId) {
            if ($testToken !== '' && hash_equals($testToken, (string)($device['token'] ?? ''))) return true;
            if ($testDeviceId !== '' && hash_equals($testDeviceId, (string)($device['device_id'] ?? ''))) return true;
            if ($testUserId > 0 && (int)($device['user_id'] ?? 0) === $testUserId) return true;
            return false;
        });
    }

    return array_values($devices);
}

/**
 * Normalize category name into a routing kind used by dispatch rules.
 */
function dispatchCategoryKindFromName(string $name): string {
    $n = strtolower(trim($name));
    if ($n === '') return 'general';
    if (strpos($n, 'fire') !== false || strpos($n, 'smoke') !== false || strpos($n, 'burn') !== false) return 'fire';
    if (strpos($n, 'earthquake') !== false || strpos($n, 'seismic') !== false || strpos($n, 'aftershock') !== false) return 'earthquake';
    if (strpos($n, 'weather') !== false || strpos($n, 'storm') !== false || strpos($n, 'typhoon') !== false || strpos($n, 'rain') !== false || strpos($n, 'flood') !== false) return 'weather';
    return 'general';
}

/**
 * Resolve category kind from category table by ID.
 */
function dispatchResolveCategoryKind(PDO $pdo, $categoryId): string {
    $cid = (int)$categoryId;
    if ($cid <= 0) {
        return 'general';
    }

    foreach (['alert_categories', 'alert_categories_catalog'] as $table) {
        try {
            $exists = $pdo->query("SHOW TABLES LIKE " . $pdo->quote($table));
            if (!$exists || !$exists->fetch()) {
                continue;
            }
            $stmt = $pdo->prepare("SELECT name FROM {$table} WHERE id = ? LIMIT 1");
            $stmt->execute([$cid]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row && isset($row['name'])) {
                return dispatchCategoryKindFromName((string)$row['name']);
            }
        } catch (Throwable $e) {
            // Backward-compatible fallback to general.
        }
    }

    return 'general';
}

/**
 * Normalize accepted severity values to low|medium|high|critical.
 */
function dispatchNormalizeSeverity($severity): string {
    $normalized = strtolower(trim((string)$severity));
    return in_array($normalized, ['low', 'medium', 'high', 'critical'], true)
        ? $normalized
        : 'medium';
}

/**
 * Fire policy:
 * - Fire level supports 1..5
 * - Fire severity is always high/critical
 * - Fire level >= 4 forces critical
 */
function dispatchEnforceFireRules(string $categoryKind, ?int $fireLevel, string $severity): array {
    $normalizedSeverity = dispatchNormalizeSeverity($severity);
    $isFireAlert = ($categoryKind === 'fire') || ($fireLevel !== null);

    if (!$isFireAlert) {
        return [$normalizedSeverity, $fireLevel, false];
    }

    $resolvedLevel = (int)($fireLevel ?? 5);
    if ($resolvedLevel < 1 || $resolvedLevel > 5) {
        $resolvedLevel = 5;
    }

    if ($resolvedLevel >= 4) {
        $normalizedSeverity = 'critical';
    } elseif ($normalizedSeverity !== 'high' && $normalizedSeverity !== 'critical') {
        $normalizedSeverity = 'high';
    }

    return [$normalizedSeverity, $resolvedLevel, true];
}

function dispatchParseEnumValues(string $columnType): array {
    $values = [];
    if (preg_match_all("/'([^']+)'/", $columnType, $matches)) {
        foreach (($matches[1] ?? []) as $raw) {
            $v = strtolower(trim((string)$raw));
            if ($v !== '') {
                $values[] = $v;
            }
        }
    }
    return array_values(array_unique($values));
}

/**
 * Resolve severity to match alerts.severity column constraints (when ENUM is used).
 */
function dispatchResolveSeverityForAlerts(PDO $pdo, string $severity): string {
    $desired = dispatchNormalizeSeverity($severity);

    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM alerts LIKE 'severity'");
        $row = $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : null;
        if (!$row || empty($row['Type'])) {
            return $desired;
        }

        $type = strtolower((string)$row['Type']);
        if (strpos($type, 'enum(') !== 0) {
            return $desired;
        }

        $allowed = dispatchParseEnumValues($type);
        if (empty($allowed)) {
            return $desired;
        }
        if (in_array($desired, $allowed, true)) {
            return $desired;
        }

        $fallbacks = [
            'critical' => ['critical', 'extreme', 'high', 'medium', 'moderate', 'low'],
            'high' => ['high', 'critical', 'extreme', 'medium', 'moderate', 'low'],
            'medium' => ['medium', 'moderate', 'high', 'critical', 'extreme', 'low'],
            'low' => ['low', 'medium', 'moderate', 'high', 'critical']
        ];
        foreach ($fallbacks[$desired] ?? [] as $candidate) {
            if (in_array($candidate, $allowed, true)) {
                return $candidate;
            }
        }

        return (string)$allowed[0];
    } catch (Throwable $e) {
        return $desired;
    }
}

function ensureAlertRecipientsTable(PDO $pdo): void {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS alert_recipients (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            alert_id BIGINT UNSIGNED NOT NULL,
            user_id BIGINT UNSIGNED NOT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_alert_user (alert_id, user_id),
            INDEX idx_alert_id (alert_id),
            INDEX idx_user_id (user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
}

/**
 * Persist exact user targeting map for an alert.
 */
function persistAlertRecipients(PDO $pdo, int $alertId, array $recipients): int {
    if ($alertId <= 0 || empty($recipients)) {
        return 0;
    }
    try {
        ensureAlertRecipientsTable($pdo);
        $stmt = $pdo->prepare("INSERT IGNORE INTO alert_recipients (alert_id, user_id) VALUES (?, ?)");

        $inserted = 0;
        $seen = [];
        foreach ($recipients as $recipient) {
            $userId = (int)($recipient['id'] ?? $recipient['user_id'] ?? 0);
            if ($userId <= 0 || isset($seen[$userId])) {
                continue;
            }
            $seen[$userId] = true;
            $stmt->execute([$alertId, $userId]);
            $inserted++;
        }

        return $inserted;
    } catch (Throwable $e) {
        error_log('persistAlertRecipients degraded mode: ' . $e->getMessage());
        return 0;
    }
}

try {
    require_once 'db_connect.php';
    require_once 'activity_logger.php';

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // 3. Authentication check
    if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
        throw new Exception('Unauthorized access denied.');
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method.');
    }

    $adminId = $_SESSION['admin_user_id'] ?? 0;

    // 4. Gather and Sanitize Data
    // Mass notifications are always city-wide. Client-provided targeting is
    // intentionally ignored so the policy is enforced server-side.
    $audienceType = 'all';
    $barangay = $_POST['barangay'] ?? '';
    $role = $_POST['role'] ?? '';
    $categoryId = $_POST['category_id'] ?? null;
    $targetLatRaw = $_POST['target_lat'] ?? null;
    $targetLngRaw = $_POST['target_lng'] ?? null;
    $radiusMRaw = $_POST['radius_m'] ?? null;
    $targetAddress = trim((string)($_POST['target_address'] ?? ''));
    $alertLatRaw = $_POST['alert_latitude'] ?? null;
    $alertLngRaw = $_POST['alert_longitude'] ?? null;
    $alertLocationName = trim((string)($_POST['alert_location_name'] ?? $_POST['alert_location'] ?? ''));
    
    $channels = $_POST['channels'] ?? []; 
    if (is_string($channels)) {
        $channels = explode(',', $channels);
    }
    $channels = array_values(array_unique(array_map('strtolower', array_filter(array_map('trim', $channels)))));
    if (!in_array('push', $channels, true)) {
        $channels[] = 'push';
    }
    $invalidChannels = array_diff($channels, ['sms', 'email', 'push', 'pa']);
    if (!empty($invalidChannels)) {
        throw new Exception('Unsupported notification channel: ' . implode(', ', $invalidChannels));
    }

    $severity = $_POST['severity'] ?? 'Medium';
    $title = trim($_POST['title'] ?? '');
    $body  = trim($_POST['body'] ?? '');
    $weatherSignalRaw = $_POST['weather_signal'] ?? null;
    $fireLevelRaw = $_POST['fire_level'] ?? null;

    $categoryId = ($categoryId !== null && $categoryId !== '') ? (int)$categoryId : null;
    if ($categoryId !== null && $categoryId <= 0) {
        $categoryId = null;
    }

    $severityAllowed = ['Low', 'Medium', 'High', 'Critical'];
    if (!in_array($severity, $severityAllowed, true)) {
        $severity = 'Medium';
    }

    $weatherSignal = null;
    if ($weatherSignalRaw !== null && $weatherSignalRaw !== '') {
        $weatherSignal = (int)$weatherSignalRaw;
        if ($weatherSignal < 1 || $weatherSignal > 5) $weatherSignal = null;
    }

    $fireLevel = null;
    if ($fireLevelRaw !== null && $fireLevelRaw !== '') {
        $fireLevel = (int)$fireLevelRaw;
        if ($fireLevel < 1 || $fireLevel > 5) $fireLevel = null;
    }

    $categoryKind = dispatchResolveCategoryKind($pdo, $categoryId);
    [$severityNormalized, $fireLevel, $isFireAlert] = dispatchEnforceFireRules($categoryKind, $fireLevel, (string)$severity);
    $severity = ucfirst($severityNormalized);

    $targetLat = null;
    $targetLng = null;
    $radiusM = null;
    if ($audienceType === 'location') {
        if ($targetLatRaw === null || $targetLngRaw === null) {
            throw new Exception('Required fields missing: target_lat, target_lng');
        }
        if (!is_numeric($targetLatRaw) || !is_numeric($targetLngRaw)) {
            throw new Exception('Invalid location coordinates.');
        }
        $targetLat = (float)$targetLatRaw;
        $targetLng = (float)$targetLngRaw;
        if ($targetLat < -90 || $targetLat > 90 || $targetLng < -180 || $targetLng > 180) {
            throw new Exception('Invalid location coordinates.');
        }

        $radiusM = is_numeric($radiusMRaw) ? (int)$radiusMRaw : 0;
        if ($radiusM <= 0 || $radiusM > 20000) {
            throw new Exception('Invalid radius. Please set a radius between 1 and 20000 meters.');
        }
    }

    $alertLat = null;
    $alertLng = null;
    if ($alertLatRaw !== null && $alertLatRaw !== '' && $alertLngRaw !== null && $alertLngRaw !== '') {
        if (!is_numeric($alertLatRaw) || !is_numeric($alertLngRaw)) {
            throw new Exception('Invalid alert location coordinates.');
        }
        $alertLat = (float)$alertLatRaw;
        $alertLng = (float)$alertLngRaw;
        if ($alertLat < -90 || $alertLat > 90 || $alertLng < -180 || $alertLng > 180) {
            throw new Exception('Invalid alert location coordinates.');
        }
    }

    if (empty($channels) || empty($title) || empty($body)) {
        $missing = [];
        if (empty($channels)) $missing[] = "channels";
        if (empty($title)) $missing[] = "title";
        if (empty($body)) $missing[] = "body";
        throw new Exception('Required fields missing: ' . implode(', ', $missing));
    }

    // Ensure queue and preference schema exists before we insert dispatch jobs.
    ensureNotificationQueueTable($pdo);
    dispatchEnsureNotificationLanguageColumn($pdo);

    // 5. Build Recipient Query
    $baseSelect = "SELECT u.id, u.name, u.email, u.phone";
    $baseFrom = " FROM users u";
    // Every public-facing audience represents citizens. The explicit role
    // audience is the only path allowed to target responders/admins.
    $baseWhere = " WHERE u.status = 'active'";
    if ($audienceType !== 'role') {
        $baseWhere .= " AND u.user_type = 'citizen'";
    }
    $params = [];

    $join = "";
    $having = "";

    if ($audienceType === 'location') {
        // Target by latest known location within radius (meters)
        $tblExists = $pdo->query("SHOW TABLES LIKE 'user_locations'")->rowCount() > 0;
        if (!$tblExists) {
            throw new Exception('Location targeting is unavailable: user_locations table not found.');
        }

        $hasIsCurrent = $pdo->query("SHOW COLUMNS FROM user_locations LIKE 'is_current'")->rowCount() > 0;
        if ($hasIsCurrent) {
            $join .= " INNER JOIN user_locations ul ON ul.user_id = u.id AND ul.is_current = 1";
        } else {
            // fallback: latest by id
            $join .= " INNER JOIN (SELECT user_id, MAX(id) AS max_id FROM user_locations GROUP BY user_id) ulm ON ulm.user_id = u.id
                      INNER JOIN user_locations ul ON ul.id = ulm.max_id";
        }

        // Haversine distance (meters)
        $distanceSql = " (6371000 * 2 * ASIN(SQRT(
            POWER(SIN(RADIANS(ul.latitude - ?)/2), 2) +
            COS(RADIANS(?)) * COS(RADIANS(ul.latitude)) *
            POWER(SIN(RADIANS(ul.longitude - ?)/2), 2)
        ))) ";

        $baseSelect .= ", ul.latitude, ul.longitude, {$distanceSql} AS distance_m";
        $params[] = $targetLat;
        $params[] = $targetLat;
        $params[] = $targetLng;
        $having = " HAVING distance_m <= ?";
        $params[] = $radiusM;

    } elseif ($audienceType === 'barangay' && !empty($barangay)) {
        $baseWhere .= " AND u.barangay = ?";
        $params[] = $barangay;
    } elseif ($audienceType === 'role' && !empty($role)) {
        $baseWhere .= " AND u.user_type = ?";
        $params[] = $role;
    } elseif ($audienceType === 'topic' && !empty($categoryId)) {
        if (empty(dispatchReadableTableColumns($pdo, 'user_subscriptions'))) {
            throw new Exception('Topic targeting is unavailable because the subscription table is not readable.');
        }
        $baseWhere .= " AND u.id IN (SELECT user_id FROM user_subscriptions WHERE category_id = ? AND is_active = 1)";
        $params[] = $categoryId;
    }

    $sql = $baseSelect . $baseFrom . $join . $baseWhere . $having;

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $recipients = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $pushDevices = in_array('push', $channels, true)
        ? dispatchLoadBroadcastPushDevices($pdo, array_column($recipients, 'id'))
        : [];

    $deliverableCount = in_array('pa', $channels, true) ? 1 : 0;
    foreach ($recipients as $recipient) {
        if (in_array('sms', $channels, true) && trim((string)($recipient['phone'] ?? '')) !== '') $deliverableCount++;
        if (in_array('email', $channels, true) && filter_var($recipient['email'] ?? '', FILTER_VALIDATE_EMAIL)) $deliverableCount++;
    }
    if (in_array('push', $channels, true)) $deliverableCount += count($pushDevices);

    // If PA is not selected, we need at least one recipient
    if (empty($recipients) && empty($pushDevices) && !in_array('pa', $channels)) {
        throw new Exception('No active recipients found for the selected audience.');
    }
    if ($deliverableCount === 0) {
        throw new Exception('No selected recipients have a valid address or active device for the chosen channels.');
    }

    // 6. Insert Pending Log Entry
    $channelStr = implode(',', $channels);
    $audienceStr = $audienceType
        . ($barangay ? ": $barangay" : "")
        . ($role ? ": $role" : "")
        . ($categoryId ? ": Cat $categoryId" : "");
    if ($audienceType === 'location' && $targetLat !== null && $targetLng !== null) {
        $audienceStr .= ": within {$radiusM}m of {$targetLat},{$targetLng}";
        if ($targetAddress !== '') $audienceStr .= " ($targetAddress)";
    }
    
    $logStmt = $pdo->prepare("
        INSERT INTO notification_logs (channel, message, recipients, priority, status, sent_at, sent_by, ip_address)
        VALUES (?, ?, ?, ?, 'pending', NOW(), ?, ?)
    ");
    $logStmt->execute([
        $channelStr,
        $body,
        $audienceStr,
        $severityNormalized,
        'admin_' . $adminId,
        $_SERVER['REMOTE_ADDR'] ?? 'unknown'
    ]);
    $logId = $pdo->lastInsertId();

    // 7. Create entry in alerts table for translation-aware dispatch + user feeds
    $hasSeverityCol = false;
    $hasWeatherSignalCol = false;
    $hasFireLevelCol = false;
    $hasSourceCol = false;
    $hasLocationCol = false;
    $hasLatitudeCol = false;
    $hasLongitudeCol = false;
    try {
        $hasSeverityCol = $pdo->query("SHOW COLUMNS FROM alerts LIKE 'severity'")->rowCount() > 0;
        $hasWeatherSignalCol = $pdo->query("SHOW COLUMNS FROM alerts LIKE 'weather_signal'")->rowCount() > 0;
        $hasFireLevelCol = $pdo->query("SHOW COLUMNS FROM alerts LIKE 'fire_level'")->rowCount() > 0;
        $hasSourceCol = $pdo->query("SHOW COLUMNS FROM alerts LIKE 'source'")->rowCount() > 0;
        $hasLocationCol = $pdo->query("SHOW COLUMNS FROM alerts LIKE 'location'")->rowCount() > 0;
        $hasLatitudeCol = $pdo->query("SHOW COLUMNS FROM alerts LIKE 'latitude'")->rowCount() > 0;
        $hasLongitudeCol = $pdo->query("SHOW COLUMNS FROM alerts LIKE 'longitude'")->rowCount() > 0;
    } catch (PDOException $e) {
        $hasSeverityCol = false;
        $hasWeatherSignalCol = false;
        $hasFireLevelCol = false;
        $hasSourceCol = false;
        $hasLocationCol = false;
        $hasLatitudeCol = false;
        $hasLongitudeCol = false;
    }

    $alertCols = ['title', 'message', 'content', 'category_id', 'status'];
    $alertVals = [$title, $body, $body, $categoryId, 'active'];
    $alertPlaceholders = array_fill(0, count($alertVals), '?');

    if ($hasSeverityCol) {
        $severityForDb = dispatchResolveSeverityForAlerts($pdo, $severityNormalized);
        $alertCols[] = 'severity';
        $alertVals[] = $severityForDb;
        $alertPlaceholders[] = '?';
    }

    if ($hasWeatherSignalCol) {
        $alertCols[] = 'weather_signal';
        $alertVals[] = $weatherSignal;
        $alertPlaceholders[] = '?';
    }
    if ($hasFireLevelCol) {
        $alertCols[] = 'fire_level';
        $alertVals[] = $fireLevel;
        $alertPlaceholders[] = '?';
    }
    if ($hasSourceCol) {
        $alertCols[] = 'source';
        $alertVals[] = 'mass_notification';
        $alertPlaceholders[] = '?';
    }
    $storedAlertLat = $alertLat ?? $targetLat;
    $storedAlertLng = $alertLng ?? $targetLng;
    $storedAlertLocation = $alertLocationName !== '' ? $alertLocationName : $targetAddress;
    if ($storedAlertLat !== null && $storedAlertLng !== null) {
        if ($hasLatitudeCol) {
            $alertCols[] = 'latitude';
            $alertVals[] = $storedAlertLat;
            $alertPlaceholders[] = '?';
        }
        if ($hasLongitudeCol) {
            $alertCols[] = 'longitude';
            $alertVals[] = $storedAlertLng;
            $alertPlaceholders[] = '?';
        }
        if ($hasLocationCol) {
            $alertCols[] = 'location';
            $alertVals[] = ($storedAlertLocation !== '' ? $storedAlertLocation : ($storedAlertLat . ',' . $storedAlertLng));
            $alertPlaceholders[] = '?';
        }
    } elseif ($storedAlertLocation !== '' && $hasLocationCol) {
        $alertCols[] = 'location';
        $alertVals[] = $storedAlertLocation;
        $alertPlaceholders[] = '?';
    }

    $alertCols[] = 'created_at';
    // MySQL is the application's canonical clock. Subtracting a server-timezone
    // offset made brand-new alerts fall outside the six-hour citizen window.
    $alertPlaceholders[] = 'NOW()';

    $aStmt = $pdo->prepare("INSERT INTO alerts (" . implode(', ', $alertCols) . ") VALUES (" . implode(', ', $alertPlaceholders) . ")");
    $aStmt->execute($alertVals);
    $alertId = (int)$pdo->lastInsertId();

    // Save exact recipients for feed visibility filtering (nearby/location-safe targeting).
    $targetedRecipientCount = persistAlertRecipients($pdo, $alertId, $recipients);

    // 8. Prepare translation service and recipient language map
    $translationHelper = null;
    if (file_exists(__DIR__ . '/alert-translation-helper.php')) {
        require_once __DIR__ . '/alert-translation-helper.php';
        if (class_exists('AlertTranslationHelper')) {
            $translationHelper = new AlertTranslationHelper($pdo);
        }
    }

    $recipientLanguages = [];
    $uniqueTargetLanguages = [];
    foreach ($recipients as $recipient) {
        $recipientId = (int)($recipient['id'] ?? 0);
        $recipientLanguage = resolveRecipientLanguage($pdo, $recipientId);
        $recipientLanguages[$recipientId] = $recipientLanguage;
        if ($recipientLanguage === 'both') {
            $uniqueTargetLanguages['fil'] = true;
        } elseif ($recipientLanguage !== 'en') {
            $uniqueTargetLanguages[$recipientLanguage] = true;
        }
    }

    // Warm translation cache once per language for this alert payload.
    if ($translationHelper && !empty($uniqueTargetLanguages)) {
        $translationHelper->preGenerateTranslations($alertId, $title, $body, array_keys($uniqueTargetLanguages));
    }

    // 9. Queue dispatch jobs (message translated per recipient language preference)
    $queueCount = 0;
    foreach ($recipients as $recipient) {
        $recipientId = (int)($recipient['id'] ?? 0);
        $recipientLanguage = $recipientLanguages[$recipientId] ?? 'en';

        $localizedTitle = $title;
        $localizedBody = $body;

        $deliveryLanguage = dispatchSystemNotificationLanguage($recipientLanguage);
        if ($translationHelper && $deliveryLanguage !== 'en') {
            $translatedAlert = $translationHelper->getTranslatedAlert($alertId, $deliveryLanguage, $title, $body);
            if (is_array($translatedAlert) && !empty($translatedAlert['title']) && !empty($translatedAlert['message'])) {
                $localizedTitle = $translatedAlert['title'];
                $localizedBody = $translatedAlert['message'];
            }
        }

        foreach ($channels as $channel) {
            $value = '';
            $type = '';
            
            if ($channel === 'sms' && !empty($recipient['phone'])) {
                $value = $recipient['phone'];
                $type = 'phone';
            } elseif ($channel === 'email' && !empty($recipient['email'])) {
                $value = $recipient['email'];
                $type = 'email';
            } elseif ($channel === 'push') {
                // Push is queued once per opted-in installation below. This
                // includes guest devices and avoids duplicates for signed-in users.
                continue;
            }

            if (!empty($value)) {
                $qStmt = $pdo->prepare("
                    INSERT INTO notification_queue (log_id, alert_id, recipient_id, recipient_type, recipient_value, channel, title, message, status)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending')
                ");
                $qStmt->execute([$logId, $alertId, $recipientId, $type, $value, $channel, $localizedTitle, $localizedBody]);
                $queueCount++;
            }
        }
    }

    if (in_array('push', $channels, true)) {
        foreach ($pushDevices as $device) {
            $pushUserId = !empty($device['user_id']) ? (int)$device['user_id'] : null;
            $localizedTitle = $title;
            $localizedBody = $body;
            if ($pushUserId && $translationHelper) {
                $language = dispatchSystemNotificationLanguage($recipientLanguages[$pushUserId] ?? 'en');
                if ($language !== 'en') {
                    $translatedAlert = $translationHelper->getTranslatedAlert($alertId, $language, $title, $body);
                    if (is_array($translatedAlert) && !empty($translatedAlert['title']) && !empty($translatedAlert['message'])) {
                        $localizedTitle = $translatedAlert['title'];
                        $localizedBody = $translatedAlert['message'];
                    }
                }
            }
            $qStmt = $pdo->prepare("INSERT INTO notification_queue
                (log_id, alert_id, recipient_id, recipient_type, recipient_value, channel, title, message, status)
                VALUES (?, ?, ?, 'push_token', ?, 'push', ?, ?, 'pending')");
            $qStmt->execute([$logId, $alertId, $pushUserId, $device['token'], $localizedTitle, $localizedBody]);
            $queueCount++;
        }
    }

    // Handle Public Address System (single message, no per-user language)
    if (in_array('pa', $channels, true)) {
        $qStmt = $pdo->prepare("
            INSERT INTO notification_queue (log_id, alert_id, recipient_id, recipient_type, recipient_value, channel, title, message, status)
            VALUES (?, ?, NULL, 'system', 'pa_system', 'pa', ?, ?, 'pending')
        ");
        $qStmt->execute([$logId, $alertId, $title, $body]);
        $queueCount++;
    }

    // 10. Mark as queued; the worker owns delivery status transitions.
    // Note: 'updated_at' is omitted as it does not exist in the schema.
    $updateStmt = $pdo->prepare("UPDATE notification_logs SET status = 'queued' WHERE id = ?");
    $updateStmt->execute([$logId]);

    // 11. Audit activity
    logAdminActivity($adminId, 'mass_notification_queued', "Queued $queueCount messages for $audienceStr. Log ID: $logId");

    // 12. Final clean output
    ob_end_clean();
    echo json_encode([
        'success' => true,
        'message' => 'Notification successfully queued.',
        'log_id' => $logId,
        'recipients' => count($recipients),
        'push_devices' => count($pushDevices),
        'targeted_recipients' => $targetedRecipientCount,
        'queued_jobs' => $queueCount,
        'alert_id' => $alertId,
        'severity' => $severityNormalized,
        'fire_level' => $fireLevel,
        'is_fire_alert' => $isFireAlert,
        'translated_languages' => array_values(array_keys($uniqueTargetLanguages))
    ]);
    exit;

} catch (Throwable $e) {
    // Attempt to update log status to 'failed' if logId was created
    if (isset($logId) && $logId) {
        try {
            $pdo->prepare("UPDATE notification_logs SET status = 'failed' WHERE id = ?")->execute([$logId]);
        } catch (Throwable $innerEx) {
            // Silence inner exception
        }
    }

    // Discard any accidental buffered output
    if (ob_get_length()) ob_end_clean();
    
    http_response_code(strpos($e->getMessage(), 'Unauthorized') !== false ? 401 : 400);
    echo json_encode([
        'success' => false,
        'message' => 'Dispatch error: ' . $e->getMessage()
    ]);
    exit;
}

