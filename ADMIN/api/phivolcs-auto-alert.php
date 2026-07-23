<?php

date_default_timezone_set('Asia/Manila');

header('Content-Type: application/json; charset=utf-8');

$isCli = PHP_SAPI === 'cli';
$cliArgs = $isCli ? array_slice($argv ?? [], 1) : [];
$isDryRun = in_array('--dry-run', $cliArgs, true);

if (!$isCli) {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (empty($_SESSION['admin_logged_in'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }
}

require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/bulletin-dispatch-helper.php';

if (!$pdo instanceof PDO) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

const PHIVOLCS_INTERVAL_MINUTES = 360;
const PHIVOLCS_FRESHNESS_HOURS = 6;

function ensurePhivolcsAutoTables(PDO $pdo): void
{
    $pdo->exec("CREATE TABLE IF NOT EXISTS phivolcs_auto_alert_settings (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        setting_key VARCHAR(100) NOT NULL UNIQUE,
        setting_value TEXT NOT NULL,
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $pdo->exec("CREATE TABLE IF NOT EXISTS phivolcs_auto_alert_log (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        event_hash VARCHAR(64) NOT NULL,
        event_time DATETIME NOT NULL,
        magnitude DECIMAL(4,1) NOT NULL,
        depth_km DECIMAL(8,2) NULL,
        latitude DECIMAL(10,7) NULL,
        longitude DECIMAL(10,7) NULL,
        location VARCHAR(500) NOT NULL DEFAULT '',
        severity VARCHAR(20) NOT NULL DEFAULT 'high',
        distance_from_qc_km DECIMAL(10,2) NULL,
        recipients_count INT UNSIGNED NOT NULL DEFAULT 0,
        queued_jobs INT UNSIGNED NOT NULL DEFAULT 0,
        dispatch_log_id BIGINT UNSIGNED NULL,
        alert_id BIGINT UNSIGNED NULL,
        status VARCHAR(20) NOT NULL DEFAULT 'sent',
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uniq_event_hash (event_hash),
        INDEX idx_created_at (created_at), INDEX idx_event_time (event_time)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $defaults = [
        'enabled' => '0',
        'check_interval_minutes' => (string)PHIVOLCS_INTERVAL_MINUTES,
        'channels' => 'push,email',
        'last_check_at' => '',
        'last_event_hash' => ''
    ];
    $stmt = $pdo->prepare("INSERT IGNORE INTO phivolcs_auto_alert_settings (setting_key, setting_value) VALUES (?, ?)");
    foreach ($defaults as $key => $value) $stmt->execute([$key, $value]);
    $pdo->exec("UPDATE phivolcs_auto_alert_settings SET setting_value = '360' WHERE setting_key = 'check_interval_minutes'");
}

function phivolcsSetting(PDO $pdo, string $key, string $default = ''): string
{
    $stmt = $pdo->prepare('SELECT setting_value FROM phivolcs_auto_alert_settings WHERE setting_key = ? LIMIT 1');
    $stmt->execute([$key]);
    $value = $stmt->fetchColumn();
    return $value === false ? $default : (string)$value;
}

function setPhivolcsSetting(PDO $pdo, string $key, string $value): void
{
    $stmt = $pdo->prepare("INSERT INTO phivolcs_auto_alert_settings (setting_key, setting_value) VALUES (?, ?)
        ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = NOW()");
    $stmt->execute([$key, $value]);
}

function fetchPhivolcsAutoFeed(): array
{
    $command = [PHP_BINARY, __DIR__ . '/phivolcs-scraper.php'];
    $pipes = [];
    $process = proc_open($command, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, __DIR__);
    if (!is_resource($process)) throw new RuntimeException('Unable to start the PHIVOLCS feed reader.');
    $stdout = stream_get_contents($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    $exitCode = proc_close($process);
    $data = json_decode((string)$stdout, true);
    if ($exitCode !== 0 || !is_array($data) || empty($data['success']) || !isset($data['earthquakes'])) {
        throw new RuntimeException('PHIVOLCS feed unavailable. ' . trim((string)$stderr));
    }
    return $data;
}

function parsePhivolcsEventTime(string $value): ?int
{
    $clean = preg_replace('/\s+-\s+/', ' ', trim($value), 1);
    $timestamp = strtotime((string)$clean);
    return $timestamp === false ? null : $timestamp;
}

function phivolcsDistanceKm(float $lat1, float $lon1, float $lat2 = 14.6488, float $lon2 = 121.0509): float
{
    $radius = 6371;
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);
    $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) ** 2;
    return $radius * 2 * atan2(sqrt($a), sqrt(1 - $a));
}

function normalizePhivolcsEvents(array $events): array
{
    $normalized = [];
    foreach ($events as $event) {
        $timestamp = parsePhivolcsEventTime((string)($event['date_time'] ?? ''));
        $magnitude = (float)($event['magnitude'] ?? 0);
        $latitude = (float)($event['latitude'] ?? 0);
        $longitude = (float)($event['longitude'] ?? 0);
        if (!$timestamp || $magnitude <= 0 || ($latitude == 0.0 && $longitude == 0.0)) continue;
        $event['event_timestamp'] = $timestamp;
        $event['magnitude'] = $magnitude;
        $event['latitude'] = $latitude;
        $event['longitude'] = $longitude;
        $event['distance_from_qc_km'] = phivolcsDistanceKm($latitude, $longitude);
        $event['event_hash'] = hash('sha256', implode('|', [
            date('c', $timestamp), number_format($latitude, 5, '.', ''), number_format($longitude, 5, '.', ''),
            number_format($magnitude, 1, '.', ''), (string)($event['depth_km'] ?? ''), trim((string)($event['location'] ?? ''))
        ]));
        $normalized[] = $event;
    }
    usort($normalized, fn($a, $b) => $b['event_timestamp'] <=> $a['event_timestamp']);
    return $normalized;
}

function qualifyingPhivolcsEvent(array $event): bool
{
    // PHIVOLCS bulletin visibility covers the full severity scale. Freshness,
    // deduplication, and the six-hour scheduler prevent stale/repeated popups;
    // severity controls how strongly the citizen UI presents the event.
    return (float)$event['magnitude'] > 0;
}

function phivolcsSeverity(array $event): string
{
    $magnitude = (float)$event['magnitude'];
    $distance = (float)$event['distance_from_qc_km'];
    if ($magnitude >= 6.0 || ($magnitude >= 5.0 && $distance <= 150.0)) return 'critical';
    if ($magnitude >= 5.0 || ($magnitude >= 4.0 && $distance <= 200.0)) return 'high';
    if ($magnitude >= 3.0 || $distance <= 200.0) return 'medium';
    return 'low';
}

function phivolcsMessage(array $event): array
{
    $magnitude = number_format((float)$event['magnitude'], 1);
    $depth = number_format((float)($event['depth_km'] ?? 0), 1);
    $distance = number_format((float)$event['distance_from_qc_km'], 0);
    $location = trim((string)($event['location'] ?? 'Philippines'));
    $severity = phivolcsSeverity($event);
    $titleType = in_array($severity, ['high', 'critical'], true) ? 'Alert' : 'Bulletin';
    $title = "PHIVOLCS Earthquake {$titleType}: Magnitude {$magnitude}";
    $message = "PHIVOLCS recorded a magnitude {$magnitude} earthquake near {$location}.\n\n"
        . "Depth: {$depth} km\nApproximate distance from Quezon City: {$distance} km\n"
        . 'Issued: ' . date('M j, Y g:i A', (int)$event['event_timestamp']) . "\n\n"
        . "Safety actions:\n• If shaking is felt: DROP, COVER, and HOLD ON.\n"
        . "• Stay away from windows and damaged structures.\n"
        . "• Expect aftershocks and follow PHIVOLCS and Quezon City advisories.";
    return [$title, $message];
}

ensurePhivolcsAutoTables($pdo);

$action = $isCli ? ($isDryRun ? 'dry-run' : 'check') : strtolower((string)($_GET['action'] ?? $_POST['action'] ?? 'status'));

try {
    if ($action === 'status') {
        $last = $pdo->query('SELECT * FROM phivolcs_auto_alert_log ORDER BY created_at DESC LIMIT 1')->fetch(PDO::FETCH_ASSOC) ?: null;
        echo json_encode([
            'success' => true,
            'enabled' => phivolcsSetting($pdo, 'enabled', '0') === '1',
            'check_interval_minutes' => PHIVOLCS_INTERVAL_MINUTES,
            'channels' => phivolcsSetting($pdo, 'channels', 'push,email'),
            'last_check_at' => phivolcsSetting($pdo, 'last_check_at', ''),
            'last_alert' => $last
        ]);
        exit;
    }

    if ($action === 'toggle') {
        $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
        setPhivolcsSetting($pdo, 'enabled', !empty($input['enabled']) ? '1' : '0');
        if (!empty($input['channels'])) setPhivolcsSetting($pdo, 'channels', (string)$input['channels']);
        echo json_encode(['success' => true, 'enabled' => phivolcsSetting($pdo, 'enabled') === '1', 'check_interval_minutes' => 360]);
        exit;
    }

    if ($action === 'history') {
        $limit = min(50, max(1, (int)($_GET['limit'] ?? 20)));
        $stmt = $pdo->prepare('SELECT * FROM phivolcs_auto_alert_log ORDER BY created_at DESC LIMIT ?');
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->execute();
        echo json_encode(['success' => true, 'logs' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        exit;
    }

    $feedData = fetchPhivolcsAutoFeed();
    $events = normalizePhivolcsEvents($feedData['earthquakes']);
    $freshCutoff = time() - (PHIVOLCS_FRESHNESS_HOURS * 3600);
    $fresh = array_values(array_filter($events, fn($event) => $event['event_timestamp'] >= $freshCutoff && $event['event_timestamp'] <= time() + 300));
    $qualifying = array_values(array_filter($fresh, 'qualifyingPhivolcsEvent'));

    if ($action === 'dry-run') {
        echo json_encode([
            'success' => true, 'dry_run' => true, 'feed_total' => count($events),
            'fresh_events' => count($fresh), 'qualifying_events' => count($qualifying),
            'candidate' => $qualifying[0] ?? null, 'cached_feed' => !empty($feedData['is_cached'])
        ]);
        exit;
    }

    $lastCheck = phivolcsSetting($pdo, 'last_check_at', '');
    $lastCheckTs = $lastCheck !== '' ? strtotime($lastCheck) : false;
    if ($lastCheckTs && $lastCheckTs + PHIVOLCS_INTERVAL_MINUTES * 60 > time()) {
        echo json_encode([
            'success' => true, 'alerted' => false, 'message' => 'Automatic PHIVOLCS checks run every 6 hours.',
            'last_check_at' => $lastCheck,
            'next_check_at' => date('Y-m-d H:i:s', $lastCheckTs + PHIVOLCS_INTERVAL_MINUTES * 60)
        ]);
        exit;
    }
    setPhivolcsSetting($pdo, 'last_check_at', date('Y-m-d H:i:s'));

    if (phivolcsSetting($pdo, 'enabled', '0') !== '1') {
        echo json_encode(['success' => true, 'alerted' => false, 'message' => 'PHIVOLCS automatic alerts are disabled.']);
        exit;
    }
    if (!$qualifying) {
        echo json_encode(['success' => true, 'alerted' => false, 'message' => 'No fresh PHIVOLCS event requires a citizen bulletin.', 'fresh_events' => count($fresh)]);
        exit;
    }

    $event = $qualifying[0];
    $dupe = $pdo->prepare('SELECT id FROM phivolcs_auto_alert_log WHERE event_hash = ? LIMIT 1');
    $dupe->execute([$event['event_hash']]);
    if ($dupe->fetchColumn()) {
        echo json_encode(['success' => true, 'alerted' => false, 'message' => 'The latest qualifying PHIVOLCS event was already sent.']);
        exit;
    }

    [$title, $message] = phivolcsMessage($event);
    $severity = phivolcsSeverity($event);
    $dispatch = queueBulletinBroadcast($pdo, [
        'title' => $title, 'message' => $message, 'severity' => $severity,
        'source' => 'phivolcs', 'category' => 'earthquake',
        'channels' => phivolcsSetting($pdo, 'channels', 'push,email')
    ]);
    $stmt = $pdo->prepare("INSERT INTO phivolcs_auto_alert_log
        (event_hash, event_time, magnitude, depth_km, latitude, longitude, location, severity, distance_from_qc_km,
         recipients_count, queued_jobs, dispatch_log_id, alert_id, status)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'sent')");
    $stmt->execute([
        $event['event_hash'], date('Y-m-d H:i:s', $event['event_timestamp']), $event['magnitude'], $event['depth_km'] ?? null,
        $event['latitude'], $event['longitude'], $event['location'] ?? '', $severity, $event['distance_from_qc_km'],
        $dispatch['recipients'], $dispatch['queued_jobs'], $dispatch['log_id'], $dispatch['alert_id']
    ]);
    setPhivolcsSetting($pdo, 'last_event_hash', $event['event_hash']);

    echo json_encode(['success' => true, 'alerted' => true, 'event' => $event, 'severity' => $severity, 'dispatch' => $dispatch]);
} catch (Throwable $e) {
    error_log('PHIVOLCS auto-alert error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'alerted' => false, 'message' => $e->getMessage()]);
}
