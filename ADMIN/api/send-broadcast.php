<?php
/**
 * Send Broadcast Controller
 * Handles audience selection and inserts jobs into the notification queue
 */

// 1. Prevent any accidental output (warnings, notices) from breaking JSON
ob_start();

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

/**
 * Resolve recipient language using stored preferences.
 * Priority:
 * 1) subscriptions.preferred_language
 * 2) user_preferences.preferred_language
 * 3) users.preferred_language
 * 4) fallback "en"
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
                $lang = normalizeDispatchLanguage($row['preferred_language']);
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
    $audienceType = $_POST['audience_type'] ?? 'all';
    $barangay = $_POST['barangay'] ?? '';
    $role = $_POST['role'] ?? '';
    $categoryId = $_POST['category_id'] ?? null;
    $targetLatRaw = $_POST['target_lat'] ?? null;
    $targetLngRaw = $_POST['target_lng'] ?? null;
    $radiusMRaw = $_POST['radius_m'] ?? null;
    $targetAddress = trim((string)($_POST['target_address'] ?? ''));
    
    $channels = $_POST['channels'] ?? []; 
    if (is_string($channels)) {
        $channels = explode(',', $channels);
    }
    $channels = array_filter(array_map('trim', $channels));

    $severity = $_POST['severity'] ?? 'Medium';
    $title = trim($_POST['title'] ?? '');
    $body  = trim($_POST['body'] ?? '');
    $weatherSignalRaw = $_POST['weather_signal'] ?? null;
    $fireLevelRaw = $_POST['fire_level'] ?? null;

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
        if ($fireLevel < 1 || $fireLevel > 3) $fireLevel = null;
    }

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

    if (empty($channels) || empty($title) || empty($body)) {
        $missing = [];
        if (empty($channels)) $missing[] = "channels";
        if (empty($title)) $missing[] = "title";
        if (empty($body)) $missing[] = "body";
        throw new Exception('Required fields missing: ' . implode(', ', $missing));
    }

    // 5. Build Recipient Query
    $baseSelect = "SELECT u.id, u.name, u.email, u.phone, d.fcm_token";
    $baseFrom = " FROM users u 
            LEFT JOIN user_devices d ON u.id = d.user_id AND d.is_active = 1";
    $baseWhere = " WHERE u.status = 'active'";
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
        $baseWhere .= " AND u.id IN (SELECT user_id FROM user_subscriptions WHERE category_id = ? AND is_active = 1)";
        $params[] = $categoryId;
    }

    $sql = $baseSelect . $baseFrom . $join . $baseWhere . $having;

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $recipients = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // If PA is not selected, we need at least one recipient
    if (empty($recipients) && !in_array('pa', $channels)) {
        throw new Exception('No active recipients found for the selected audience.');
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
        strtolower($severity),
        'admin_' . $adminId,
        $_SERVER['REMOTE_ADDR'] ?? 'unknown'
    ]);
    $logId = $pdo->lastInsertId();

    // 7. Create entry in alerts table for translation-aware dispatch + user feeds
    $hasSeverityCol = false;
    $hasWeatherSignalCol = false;
    $hasFireLevelCol = false;
    try {
        $hasSeverityCol = $pdo->query("SHOW COLUMNS FROM alerts LIKE 'severity'")->rowCount() > 0;
        $hasWeatherSignalCol = $pdo->query("SHOW COLUMNS FROM alerts LIKE 'weather_signal'")->rowCount() > 0;
        $hasFireLevelCol = $pdo->query("SHOW COLUMNS FROM alerts LIKE 'fire_level'")->rowCount() > 0;
    } catch (PDOException $e) {
        $hasSeverityCol = false;
        $hasWeatherSignalCol = false;
        $hasFireLevelCol = false;
    }

    $alertCols = ['title', 'message', 'content', 'category_id', 'status'];
    $alertVals = [$title, $body, $body, $categoryId, 'active'];
    $alertPlaceholders = array_fill(0, count($alertVals), '?');

    if ($hasSeverityCol) {
        $alertCols[] = 'severity';
        $alertVals[] = $severity;
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

    $alertCols[] = 'created_at';
    $alertPlaceholders[] = 'NOW()';

    $aStmt = $pdo->prepare("INSERT INTO alerts (" . implode(', ', $alertCols) . ") VALUES (" . implode(', ', $alertPlaceholders) . ")");
    $aStmt->execute($alertVals);
    $alertId = (int)$pdo->lastInsertId();

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
        if ($recipientLanguage !== 'en') {
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

        if ($translationHelper && $recipientLanguage !== 'en') {
            $translatedAlert = $translationHelper->getTranslatedAlert($alertId, $recipientLanguage, $title, $body);
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
            } elseif ($channel === 'push' && !empty($recipient['fcm_token'])) {
                $value = $recipient['fcm_token'];
                $type = 'fcm_token';
            }

            if (!empty($value)) {
                $qStmt = $pdo->prepare("
                    INSERT INTO notification_queue (log_id, recipient_id, recipient_type, recipient_value, channel, title, message, status)
                    VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')
                ");
                $qStmt->execute([$logId, $recipientId, $type, $value, $channel, $localizedTitle, $localizedBody]);
                $queueCount++;
            }
        }
    }

    // Handle Public Address System (single message, no per-user language)
    if (in_array('pa', $channels, true)) {
        $qStmt = $pdo->prepare("
            INSERT INTO notification_queue (log_id, recipient_id, recipient_type, recipient_value, channel, title, message, status)
            VALUES (?, NULL, 'system', 'pa_system', 'pa', ?, ?, 'pending')
        ");
        $qStmt->execute([$logId, $title, $body]);
        $queueCount++;
    }

    // 10. Update log status to 'sent' (queued successfully)
    // Note: 'updated_at' is omitted as it does not exist in the schema.
    $updateStmt = $pdo->prepare("UPDATE notification_logs SET status = 'sent' WHERE id = ?");
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
        'queued_jobs' => $queueCount,
        'alert_id' => $alertId,
        'translated_languages' => array_values(array_keys($uniqueTargetLanguages))
    ]);
    exit;

} catch (Exception $e) {
    // Attempt to update log status to 'failed' if logId was created
    if (isset($logId) && $logId) {
        try {
            $pdo->prepare("UPDATE notification_logs SET status = 'failed' WHERE id = ?")->execute([$logId]);
        } catch (PDOException $innerEx) {
            // Silence inner exception
        }
    }

    // Discard any accidental buffered output
    if (ob_get_length()) ob_end_clean();
    
    echo json_encode([
        'success' => false,
        'message' => 'Dispatch error: ' . $e->getMessage()
    ]);
    exit;
}
