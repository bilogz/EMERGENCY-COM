<?php
/**
 * PAGASA Auto-Alert System
 * 
 * Checks the PAGASA weather bulletin feed for new/updated posts.
 * When a new bulletin is detected, it automatically queues a mass
 * notification to all subscribed citizens via the existing broadcast system.
 *
 * Can be called:
 *  - Via AJAX from the admin panel (action=check, action=status, action=toggle, action=history)
 *  - Via cron job: php pagasa-auto-alert.php --cron
 */

date_default_timezone_set('Asia/Manila');

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Detect CLI (cron) mode
$isCron = (php_sapi_name() === 'cli');
$isDryRun = $isCron && in_array('--dry-run', $argv ?? [], true);

if (!$isCron) {
    session_start();
    if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit();
    }
}

require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/bulletin-dispatch-helper.php';

if ($pdo === null) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

// ============================================================
// Schema: Ensure tables exist
// ============================================================

function ensurePagasaAutoAlertTables(PDO $pdo): void {
    // Settings table for auto-alert toggle + config
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS pagasa_auto_alert_settings (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            setting_key VARCHAR(100) NOT NULL UNIQUE,
            setting_value TEXT NOT NULL,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    // Log of sent auto-alerts (dedup + history)
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS pagasa_auto_alert_log (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            bulletin_hash VARCHAR(64) NOT NULL,
            bulletin_title VARCHAR(500) NOT NULL DEFAULT '',
            bulletin_summary TEXT NULL,
            bulletin_link VARCHAR(500) NULL,
            severity VARCHAR(20) NOT NULL DEFAULT 'medium',
            recipients_count INT UNSIGNED NOT NULL DEFAULT 0,
            channels VARCHAR(100) NOT NULL DEFAULT 'push',
            status VARCHAR(20) NOT NULL DEFAULT 'sent',
            dispatch_log_id BIGINT UNSIGNED NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_hash (bulletin_hash),
            INDEX idx_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    // Alter table just in case it already exists without the column
    try {
        $pdo->exec("ALTER TABLE pagasa_auto_alert_log ADD COLUMN IF NOT EXISTS bulletin_link VARCHAR(500) NULL AFTER bulletin_summary");
    } catch (Throwable $e) {}

    // Seed default settings if empty
    $count = (int)$pdo->query("SELECT COUNT(*) FROM pagasa_auto_alert_settings")->fetchColumn();
    if ($count === 0) {
        $defaults = [
            ['enabled', '0'],
            ['check_interval_minutes', '360'],
            ['channels', 'push,email'],
            ['last_check_at', ''],
            ['last_bulletin_hash', ''],
        ];
        $stmt = $pdo->prepare("INSERT IGNORE INTO pagasa_auto_alert_settings (setting_key, setting_value) VALUES (?, ?)");
        foreach ($defaults as $row) {
            $stmt->execute($row);
        }
    }
    // Automatic PAGASA checks use one fixed six-hour interval.
    try {
        $pdo->exec("UPDATE pagasa_auto_alert_settings SET setting_value = '360' WHERE setting_key = 'check_interval_minutes' AND setting_value <> '360'");
    } catch (Throwable $e) {}
}

ensurePagasaAutoAlertTables($pdo);

// ============================================================
// Helpers
// ============================================================

function getSetting(PDO $pdo, string $key, string $default = ''): string {
    $stmt = $pdo->prepare("SELECT setting_value FROM pagasa_auto_alert_settings WHERE setting_key = ? LIMIT 1");
    $stmt->execute([$key]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ? (string)$row['setting_value'] : $default;
}

function setSetting(PDO $pdo, string $key, string $value): void {
    $stmt = $pdo->prepare("
        INSERT INTO pagasa_auto_alert_settings (setting_key, setting_value) VALUES (?, ?)
        ON DUPLICATE KEY UPDATE setting_value = ?, updated_at = NOW()
    ");
    $stmt->execute([$key, $value, $value]);
}

function isPagasaBulletinFresh(array $bulletin, int $hours = 6): bool {
    $issuedAt = strtotime((string)($bulletin['pubDate'] ?? ''));
    if ($issuedAt === false) {
        return false;
    }
    $ageSeconds = time() - $issuedAt;
    return $ageSeconds >= -300 && $ageSeconds <= ($hours * 3600);
}

function fetchPagasaBulletins(): ?array {
    $urls = [
        'https://pubfiles.pagasa.dost.gov.ph/tamss/weather/bulletin.xml',
        'https://www.pagasa.dost.gov.ph/weather/bulletin-rss.xml'
    ];

    foreach ($urls as $url) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 6,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) EmergencyCom/1.0'
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($response && $httpCode === 200 && strpos($response, '<rss') !== false) {
            $oldLoader = libxml_disable_entity_loader(true);
            $xml = @simplexml_load_string($response, 'SimpleXMLElement', LIBXML_NOCDATA);
            libxml_disable_entity_loader($oldLoader);

            if ($xml === false) continue;

            $bulletins = [];
            $channel = $xml->channel;
            if (isset($channel->item)) {
                foreach ($channel->item as $item) {
                    $title = trim((string)$item->title);
                    $description = trim((string)$item->description);
                    $pubDate = trim((string)$item->pubDate);
                    $link = trim((string)$item->link);

                    $descLower = strtolower($description);
                    $severity = 'medium';
                    if (strpos($descLower, 'signal no. 3') !== false || strpos($descLower, 'signal no. 4') !== false || strpos($descLower, 'signal no. 5') !== false) {
                        $severity = 'critical';
                    } elseif (strpos($descLower, 'signal no. 2') !== false || strpos($descLower, 'heavy') !== false) {
                        $severity = 'high';
                    }

                    $bulletins[] = [
                        'title' => $title,
                        'description' => $description,
                        'pubDate' => $pubDate,
                        'link' => $link,
                        'severity' => $severity,
                        'hash' => md5($title . '|' . $description)
                    ];
                }
            }
            if (!empty($bulletins)) {
                return $bulletins;
            }
        }
    }

    // FALLBACK 1: Scrape Directory Index
    try {
        $dirUrl = 'https://pubfiles.pagasa.dost.gov.ph/tamss/weather/bulletin/';
        $ch = curl_init($dirUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 6,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_USERAGENT      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) EmergencyCom/1.0'
        ]);
        $html = curl_exec($ch);
        curl_close($ch);

        if ($html) {
            preg_match_all('/<a\s+href="([^"]+)">([^<]+)<\/a>\s+([\d\-a-zA-Z\s:]+)/i', $html, $matches, PREG_SET_ORDER);
            $dirBulletins = [];
            foreach ($matches as $m) {
                $href = $m[1];
                $name = trim($m[2]);
                $dateStrRaw = trim($m[3]);
                
                if (strpos($name, 'TCB#') !== 0) continue;
                
                if (!preg_match('/(\d{2}-[a-zA-Z]{3}-\d{4}\s+\d{2}:\d{2})/', $dateStrRaw, $dm)) {
                    continue;
                }
                
                $time = strtotime($dm[1]);
                if (!$time) continue;
                
                $cycloneName = 'Unknown';
                $bulletinNumber = 0;
                
                if (preg_match('/TCB#(\d+)_([a-zA-Z]+)\.pdf/i', $name, $parts)) {
                    $bulletinNumber = (int)$parts[1];
                    $cycloneName = ucfirst(strtolower($parts[2]));
                } else {
                    continue;
                }
                
                $dirBulletins[] = [
                    'name' => $name,
                    'href' => $dirUrl . $href,
                    'time' => $time,
                    'date_str' => date('D, d M Y h:i A', $time),
                    'bulletin_number' => $bulletinNumber,
                    'cyclone_name' => $cycloneName
                ];
            }

            if (!empty($dirBulletins)) {
                usort($dirBulletins, function($a, $b) {
                    return $b['time'] - $a['time'];
                });
                
                $latest = $dirBulletins[0];
                $title = "TROPICAL CYCLONE BULLETIN NR. " . $latest['bulletin_number'] . " (" . $latest['cyclone_name'] . ")";
                $description = "Tropical Cyclone Bulletin Nr. " . $latest['bulletin_number'] . " for " . $latest['cyclone_name'] . " has been officially issued by PAGASA. Please view the official PDF to review the center coordinates, wind speed, forecast track, and tropical cyclone wind signals (TCWS) in effect.";
                
                return [[
                    'title' => $title,
                    'description' => $description,
                    'pubDate' => $latest['date_str'],
                    'link' => $latest['href'],
                    'severity' => 'high',
                    'hash' => md5($title . '|' . $description)
                ]];
            }
        }
    } catch (Throwable $e) {}

    return null;
}

/**
 * Count active citizens for notification audience.
 */
function countActiveCitizens(PDO $pdo): int {
    // Try subscriptions first, fallback to users table
    foreach (['subscriptions', 'users'] as $table) {
        try {
            $exists = $pdo->query("SHOW TABLES LIKE " . $pdo->quote($table));
            if (!$exists || !$exists->fetch()) continue;

            if ($table === 'subscriptions') {
                return (int)$pdo->query("SELECT COUNT(DISTINCT user_id) FROM subscriptions WHERE status = 'active'")->fetchColumn();
            } else {
                return (int)$pdo->query("SELECT COUNT(*) FROM users WHERE status = 'active' OR status IS NULL")->fetchColumn();
            }
        } catch (Throwable $e) {
            continue;
        }
    }
    return 0;
}

/**
 * Queue a mass notification broadcast for a PAGASA bulletin.
 * Re-uses the existing send-broadcast infrastructure.
 */
function queuePagasaBroadcast(PDO $pdo, array $bulletin, string $channels): ?int {
    $title = '⚠️ PAGASA Weather Alert: ' . ($bulletin['title'] ?? 'New Bulletin');
    $message = $bulletin['description'] ?? 'A new weather bulletin has been issued by PAGASA. Please check official channels for details.';
    $severity = $bulletin['severity'] ?? 'medium';

    // Ensure dispatch_logs table exists
    try {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS dispatch_logs (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                title VARCHAR(500) NOT NULL DEFAULT '',
                message TEXT NULL,
                category_id INT UNSIGNED NULL,
                severity VARCHAR(20) NOT NULL DEFAULT 'medium',
                channels VARCHAR(100) NOT NULL DEFAULT 'push',
                target_audience VARCHAR(50) NOT NULL DEFAULT 'all',
                target_area VARCHAR(255) NULL,
                fire_level INT NULL,
                status VARCHAR(20) NOT NULL DEFAULT 'pending',
                total_recipients INT UNSIGNED NOT NULL DEFAULT 0,
                sent_count INT UNSIGNED NOT NULL DEFAULT 0,
                failed_count INT UNSIGNED NOT NULL DEFAULT 0,
                created_by VARCHAR(100) NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                completed_at DATETIME NULL,
                INDEX idx_status (status),
                INDEX idx_created (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    } catch (Throwable $e) {
        // Table might already exist
    }

    // Ensure notification_queue table exists
    try {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS notification_queue (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                log_id BIGINT UNSIGNED NOT NULL,
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
    } catch (Throwable $e) {
        // Table might already exist
    }

    // Find a weather category ID
    $categoryId = null;
    foreach (['alert_categories', 'alert_categories_catalog'] as $catTable) {
        try {
            $exists = $pdo->query("SHOW TABLES LIKE " . $pdo->quote($catTable));
            if (!$exists || !$exists->fetch()) continue;
            $catStmt = $pdo->prepare("SELECT id FROM {$catTable} WHERE LOWER(name) LIKE '%weather%' LIMIT 1");
            $catStmt->execute();
            $catRow = $catStmt->fetch(PDO::FETCH_ASSOC);
            if ($catRow) {
                $categoryId = (int)$catRow['id'];
                break;
            }
        } catch (Throwable $e) {
            continue;
        }
    }

    // Insert dispatch log
    $channelList = $channels ?: 'push';
    $recipientCount = countActiveCitizens($pdo);

    // Use the actual publish time so old entries stay in history naturally.
    $alertTime = date('Y-m-d H:i:s');

    $stmt = $pdo->prepare("
        INSERT INTO dispatch_logs (title, message, category_id, severity, channels, target_audience, status, total_recipients, created_by, created_at)
        VALUES (?, ?, ?, ?, ?, 'all', 'sending', ?, 'PAGASA Auto-Alert', ?)
    ");
    $stmt->execute([$title, $message, $categoryId, $severity, $channelList, $recipientCount, $alertTime]);
    $logId = (int)$pdo->lastInsertId();

    // Also insert into alerts table so it is visible to citizens on alerts.php page
    try {
        $alertStmt = $pdo->prepare("
            INSERT INTO alerts (title, message, content, category_id, severity, source, status, created_at)
            VALUES (?, ?, ?, ?, ?, 'pagasa', 'active', ?)
        ");
        $alertStmt->execute([$title, $message, $message, $categoryId, $severity, $alertTime]);
    } catch (Throwable $e) {
        error_log("Failed to insert PAGASA broadcast into alerts table: " . $e->getMessage());
    }

    // Queue individual notifications for each citizen
    $channelArr = array_filter(array_map('trim', explode(',', $channelList)));
    $queued = 0;

    // Get recipients from subscriptions or users
    $recipients = [];
    try {
        $subExists = $pdo->query("SHOW TABLES LIKE 'subscriptions'");
        if ($subExists && $subExists->fetch()) {
            $rStmt = $pdo->query("
                SELECT DISTINCT s.user_id AS id, 
                       COALESCE(u.email, '') AS email, 
                       COALESCE(u.phone, u.phone_number, '') AS phone,
                       COALESCE(u.full_name, u.name, u.username, 'Citizen') AS name
                FROM subscriptions s
                LEFT JOIN users u ON s.user_id = u.id
                WHERE s.status = 'active'
                LIMIT 10000
            ");
            $recipients = $rStmt->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (Throwable $e) {
        // Fallback to users table
    }

    if (empty($recipients)) {
        try {
            $uExists = $pdo->query("SHOW TABLES LIKE 'users'");
            if ($uExists && $uExists->fetch()) {
                $rStmt = $pdo->query("
                    SELECT id, 
                           COALESCE(email, '') AS email, 
                           COALESCE(phone, phone_number, '') AS phone,
                           COALESCE(full_name, name, username, 'Citizen') AS name
                    FROM users
                    WHERE (status = 'active' OR status IS NULL)
                    LIMIT 10000
                ");
                $recipients = $rStmt->fetchAll(PDO::FETCH_ASSOC);
            }
        } catch (Throwable $e) {
            // No recipients available
        }
    }

    $queueStmt = $pdo->prepare("
        INSERT INTO notification_queue (log_id, recipient_id, recipient_type, recipient_value, channel, title, message, status, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', ?)
    ");

    foreach ($recipients as $recipient) {
        $userId = (int)($recipient['id'] ?? 0);
        foreach ($channelArr as $ch) {
            $recipientValue = '';
            $recipientType = 'user';
            if ($ch === 'email' && !empty($recipient['email'])) {
                $recipientValue = $recipient['email'];
                $recipientType = 'email';
            } elseif ($ch === 'sms' && !empty($recipient['phone'])) {
                $recipientValue = $recipient['phone'];
                $recipientType = 'phone';
            } elseif ($ch === 'push') {
                $recipientValue = (string)$userId;
                $recipientType = 'push';
            } else {
                continue;
            }
            try {
                $queueStmt->execute([$logId, $userId, $recipientType, $recipientValue, $ch, $title, $message, $alertTime]);
                $queued++;
            } catch (Throwable $e) {
                // Skip duplicate or error
            }
        }
    }

    // Update dispatch log with actual count
    $pdo->prepare("UPDATE dispatch_logs SET total_recipients = ?, status = 'completed', completed_at = NOW() WHERE id = ?")
        ->execute([$queued, $logId]);

    return $logId;
}

// ============================================================
// Action Router
// ============================================================

$action = $isCron ? ($isDryRun ? 'dry-run' : 'check') : ($_GET['action'] ?? $_POST['action'] ?? 'status');

switch ($action) {

    // ----------------------------------------------------------
    // STATUS: Get current auto-alert config + last check info
    // ----------------------------------------------------------
    case 'status':
        $enabled = getSetting($pdo, 'enabled', '0') === '1';
        $interval = 360;
        setSetting($pdo, 'check_interval_minutes', '360');
        $channels = getSetting($pdo, 'channels', 'push,email');
        $lastCheck = getSetting($pdo, 'last_check_at', '');
        $lastHash = getSetting($pdo, 'last_bulletin_hash', '');

        // Get recent alert count
        $recentCount = 0;
        try {
            $recentCount = (int)$pdo->query("SELECT COUNT(*) FROM pagasa_auto_alert_log WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)")->fetchColumn();
        } catch (Throwable $e) {}

        echo json_encode([
            'success' => true,
            'enabled' => $enabled,
            'check_interval_minutes' => $interval,
            'channels' => $channels,
            'last_check_at' => $lastCheck,
            'last_bulletin_hash' => $lastHash,
            'alerts_last_24h' => $recentCount
        ]);
        break;

    // ----------------------------------------------------------
    // TOGGLE: Enable/disable auto-alerts
    // ----------------------------------------------------------
    case 'toggle':
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $enabled = !empty($input['enabled']) ? '1' : '0';
        $channels = isset($input['channels']) ? (string)$input['channels'] : null;
        $interval = 360;

        setSetting($pdo, 'enabled', $enabled);
        if ($channels !== null) {
            setSetting($pdo, 'channels', $channels);
        }
        setSetting($pdo, 'check_interval_minutes', '360');

        echo json_encode([
            'success' => true,
            'message' => $enabled === '1' ? 'PAGASA auto-alerts enabled.' : 'PAGASA auto-alerts disabled.',
            'enabled' => $enabled === '1'
        ]);
        break;

    // ----------------------------------------------------------
    // CHECK: Poll PAGASA feed and send alert if new bulletin found
    // ----------------------------------------------------------
    case 'dry-run':
    case 'check':
        $lastCheck = getSetting($pdo, 'last_check_at', '');
        $lastCheckTs = $lastCheck !== '' ? strtotime($lastCheck) : false;
        $nextCheckTs = $lastCheckTs !== false ? $lastCheckTs + (6 * 3600) : 0;
        if ($action === 'check' && $nextCheckTs > time()) {
            echo json_encode([
                'success' => true,
                'message' => 'Automatic PAGASA checks run every 6 hours.',
                'alerted' => false,
                'checked_at' => $lastCheck,
                'next_check_at' => date('Y-m-d H:i:s', $nextCheckTs),
                'check_interval_minutes' => 360
            ]);
            break;
        }

        // Fetch bulletins
        $bulletins = fetchPagasaBulletins();
        if ($action === 'check') {
            setSetting($pdo, 'last_check_at', date('Y-m-d H:i:s'));
        }

        if ($bulletins === null || empty($bulletins)) {
            echo json_encode([
                'success' => true,
                'message' => 'No bulletins available from PAGASA feed.',
                'alerted' => false,
                'checked_at' => date('Y-m-d H:i:s')
            ]);
            break;
        }

        $latestBulletin = $bulletins[0]; // Most recent
        if ($action === 'dry-run') {
            echo json_encode([
                'success' => true,
                'dry_run' => true,
                'feed_total' => count($bulletins),
                'fresh' => isPagasaBulletinFresh($latestBulletin, 6),
                'candidate' => $latestBulletin,
                'enabled' => getSetting($pdo, 'enabled', '0') === '1',
                'check_interval_minutes' => 360
            ]);
            break;
        }

        if (!isPagasaBulletinFresh($latestBulletin, 6)) {
            echo json_encode([
                'success' => true,
                'message' => 'The latest PAGASA bulletin is older than 6 hours, so no automatic alert was sent.',
                'alerted' => false,
                'bulletin_title' => $latestBulletin['title'] ?? '',
                'checked_at' => date('Y-m-d H:i:s')
            ]);
            break;
        }

        $currentHash = $latestBulletin['hash'];
        $severity = strtolower($latestBulletin['severity'] ?? 'medium');
        $enabled = getSetting($pdo, 'enabled', '0');

        // Automatic dispatch must always respect the operator's enable switch.
        $shouldSend = ($enabled === '1');

        if (!$shouldSend) {
            echo json_encode([
                'success' => true,
                'message' => 'Auto-alerts are disabled for low/medium severity bulletins.',
                'alerted' => false,
                'checked_at' => date('Y-m-d H:i:s')
            ]);
            break;
        }

        $lastHash = getSetting($pdo, 'last_bulletin_hash', '');

        if ($currentHash === $lastHash) {
            echo json_encode([
                'success' => true,
                'message' => 'No new bulletins detected.',
                'alerted' => false,
                'bulletin_title' => $latestBulletin['title'],
                'checked_at' => date('Y-m-d H:i:s')
            ]);
            break;
        }

        // Check if we already sent an alert for this hash
        $dupeCheck = $pdo->prepare("SELECT id FROM pagasa_auto_alert_log WHERE bulletin_hash = ? LIMIT 1");
        $dupeCheck->execute([$currentHash]);
        if ($dupeCheck->fetch()) {
            // Already alerted, just update hash
            setSetting($pdo, 'last_bulletin_hash', $currentHash);
            echo json_encode([
                'success' => true,
                'message' => 'Bulletin already alerted previously.',
                'alerted' => false,
                'checked_at' => date('Y-m-d H:i:s')
            ]);
            break;
        }

        // NEW BULLETIN DETECTED — Send mass notification
        $channels = getSetting($pdo, 'channels', 'push,email');
        $dispatch = queueBulletinBroadcast($pdo, [
            'title' => 'PAGASA Weather Alert: ' . ($latestBulletin['title'] ?? 'New Bulletin'),
            'message' => $latestBulletin['description'] ?? 'PAGASA has issued a new weather bulletin. Monitor official updates and follow local instructions.',
            'severity' => $latestBulletin['severity'] ?? 'medium',
            'source' => 'pagasa',
            'category' => 'weather',
            'channels' => $channels,
        ]);
        $logId = (int)$dispatch['log_id'];
        $recipientCount = (int)$dispatch['recipients'];

        // Record in alert log
        $alertStmt = $pdo->prepare("
            INSERT INTO pagasa_auto_alert_log (bulletin_hash, bulletin_title, bulletin_summary, bulletin_link, severity, recipients_count, channels, status, dispatch_log_id)
            VALUES (?, ?, ?, ?, ?, ?, ?, 'sent', ?)
        ");
        $alertStmt->execute([
            $currentHash,
            $latestBulletin['title'],
            mb_substr($latestBulletin['description'], 0, 1000),
            $latestBulletin['link'] ?? null,
            $latestBulletin['severity'],
            $recipientCount,
            $channels,
            $logId
        ]);

        // Update last known hash
        setSetting($pdo, 'last_bulletin_hash', $currentHash);

        // Run notification worker to process the queued alerts immediately
        try {
            @include_once __DIR__ . '/notification-worker.php';
        } catch (Throwable $workerEx) {
            // Ignore/log error
        }

        echo json_encode([
            'success' => true,
            'message' => 'New PAGASA bulletin detected! Mass notification queued.',
            'alerted' => true,
            'bulletin_title' => $latestBulletin['title'],
            'severity' => $latestBulletin['severity'],
            'recipients' => $recipientCount,
            'dispatch_log_id' => $logId,
            'checked_at' => date('Y-m-d H:i:s')
        ]);
        break;

    // ----------------------------------------------------------
    // HISTORY: Get recent auto-alert log entries
    // ----------------------------------------------------------
    case 'history':
        $limit = min(50, max(1, (int)($_GET['limit'] ?? 20)));
        $stmt = $pdo->prepare("
            SELECT id, bulletin_hash, bulletin_title, bulletin_summary, severity, recipients_count, channels, status, dispatch_log_id, created_at
            FROM pagasa_auto_alert_log
            ORDER BY created_at DESC
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => true,
            'logs' => $logs,
            'count' => count($logs)
        ]);
        break;

    // ----------------------------------------------------------
    // FORCE: Manually trigger a check + alert regardless of hash
    // ----------------------------------------------------------
    case 'force':
        $bulletins = fetchPagasaBulletins();
        setSetting($pdo, 'last_check_at', date('Y-m-d H:i:s'));

        if ($bulletins === null || empty($bulletins)) {
            echo json_encode(['success' => false, 'message' => 'No bulletins available from PAGASA.']);
            break;
        }

        $latestBulletin = $bulletins[0];
        $channels = getSetting($pdo, 'channels', 'push,email');
        $dispatch = queueBulletinBroadcast($pdo, [
            'title' => 'PAGASA Weather Alert: ' . ($latestBulletin['title'] ?? 'New Bulletin'),
            'message' => $latestBulletin['description'] ?? 'PAGASA has issued a new weather bulletin. Monitor official updates and follow local instructions.',
            'severity' => $latestBulletin['severity'] ?? 'medium',
            'source' => 'pagasa',
            'category' => 'weather',
            'channels' => $channels,
        ]);
        $logId = (int)$dispatch['log_id'];
        $recipientCount = (int)$dispatch['recipients'];
        $currentHash = $latestBulletin['hash'];

        $alertStmt = $pdo->prepare("
            INSERT INTO pagasa_auto_alert_log (bulletin_hash, bulletin_title, bulletin_summary, bulletin_link, severity, recipients_count, channels, status, dispatch_log_id)
            VALUES (?, ?, ?, ?, ?, ?, ?, 'sent', ?)
        ");
        $alertStmt->execute([
            $currentHash,
            $latestBulletin['title'],
            mb_substr($latestBulletin['description'], 0, 1000),
            $latestBulletin['link'] ?? null,
            $latestBulletin['severity'],
            $recipientCount,
            $channels,
            $logId
        ]);

        setSetting($pdo, 'last_bulletin_hash', $currentHash);

        // Run notification worker to process the queued alerts immediately
        try {
            @include_once __DIR__ . '/notification-worker.php';
        } catch (Throwable $workerEx) {
            // Ignore/log error
        }

        echo json_encode([
            'success' => true,
            'message' => 'Force alert sent!',
            'alerted' => true,
            'bulletin_title' => $latestBulletin['title'],
            'severity' => $latestBulletin['severity'],
            'recipients' => $recipientCount,
            'dispatch_log_id' => $logId,
            'checked_at' => date('Y-m-d H:i:s')
        ]);
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Unknown action: ' . $action]);
        break;
}
