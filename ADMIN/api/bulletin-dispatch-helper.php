<?php

require_once dirname(__DIR__, 2) . '/PHP/api/device_registry.php';

function bulletinReadableColumns(PDO $pdo, string $table): array
{
    if (!preg_match('/^[A-Za-z0-9_]+$/', $table)) return [];
    try {
        $pdo->query("SELECT 1 FROM `{$table}` LIMIT 1");
        $stmt = $pdo->query("SHOW COLUMNS FROM `{$table}`");
        return array_fill_keys($stmt ? $stmt->fetchAll(PDO::FETCH_COLUMN) : [], true);
    } catch (Throwable $e) {
        return [];
    }
}

function bulletinEnsureDeliveryTables(PDO $pdo): void
{
    $pdo->exec("CREATE TABLE IF NOT EXISTS notification_logs (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        channel VARCHAR(100) NOT NULL DEFAULT 'push',
        message TEXT NULL,
        recipients VARCHAR(255) NULL,
        priority VARCHAR(20) NOT NULL DEFAULT 'high',
        status VARCHAR(20) NOT NULL DEFAULT 'pending',
        response TEXT NULL,
        sent_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        sent_by VARCHAR(100) NULL,
        ip_address VARCHAR(64) NULL,
        INDEX idx_status (status), INDEX idx_sent_at (sent_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $pdo->exec("CREATE TABLE IF NOT EXISTS notification_queue (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $logColumns = bulletinReadableColumns($pdo, 'notification_logs');
    if (!isset($logColumns['response'])) {
        $pdo->exec('ALTER TABLE notification_logs ADD COLUMN response TEXT NULL');
    }
    if (!isset($logColumns['status'])) {
        $pdo->exec("ALTER TABLE notification_logs ADD COLUMN status VARCHAR(20) NOT NULL DEFAULT 'pending'");
    }

    $queueColumns = bulletinReadableColumns($pdo, 'notification_queue');
    $required = [
        'alert_id' => 'BIGINT UNSIGNED NULL',
        'delivery_status' => 'VARCHAR(20) NULL',
        'error_message' => 'TEXT NULL',
        'processed_at' => 'DATETIME NULL',
        'delivered_at' => 'DATETIME NULL'
    ];
    foreach ($required as $column => $definition) {
        if (!isset($queueColumns[$column])) {
            $pdo->exec("ALTER TABLE notification_queue ADD COLUMN `{$column}` {$definition}");
        }
    }
}

function bulletinResolveAlertsTable(PDO $pdo): string
{
    if (!empty(bulletinReadableColumns($pdo, 'alerts'))) return 'alerts';
    foreach (['alerts_runtime', 'alerts_runtime_fallback'] as $table) {
        if (!empty(bulletinReadableColumns($pdo, $table))) return $table;
    }
    $pdo->exec("CREATE TABLE IF NOT EXISTS alerts_runtime (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        category_id INT NULL,
        title VARCHAR(255) NOT NULL,
        message TEXT NOT NULL,
        content TEXT NULL,
        category VARCHAR(100) NULL,
        severity VARCHAR(20) NOT NULL DEFAULT 'high',
        source VARCHAR(100) NULL,
        status VARCHAR(20) NOT NULL DEFAULT 'active',
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_status_created (status, created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    return 'alerts_runtime';
}

function bulletinFindCategoryId(PDO $pdo, string $category): ?int
{
    $needle = strtolower($category) === 'earthquake' ? 'earthquake' : 'weather';
    foreach (['alert_categories', 'alert_categories_catalog'] as $table) {
        if (empty(bulletinReadableColumns($pdo, $table))) continue;
        try {
            $stmt = $pdo->prepare("SELECT id FROM {$table} WHERE LOWER(name) LIKE ? ORDER BY id LIMIT 1");
            $stmt->execute(['%' . $needle . '%']);
            $id = (int)$stmt->fetchColumn();
            if ($id > 0) return $id;
        } catch (Throwable $e) {
        }
    }
    return null;
}

function bulletinLoadCitizens(PDO $pdo): array
{
    $columns = bulletinReadableColumns($pdo, 'users');
    if (empty($columns) || !isset($columns['id'])) return [];
    $name = isset($columns['name']) ? 'name' : (isset($columns['full_name']) ? 'full_name' : (isset($columns['username']) ? 'username' : null));
    $email = isset($columns['email']) ? 'email' : null;
    $phone = isset($columns['phone']) ? 'phone' : (isset($columns['phone_number']) ? 'phone_number' : null);
    $select = ['id'];
    $select[] = $name ? "COALESCE(`{$name}`, 'Citizen') AS name" : "'Citizen' AS name";
    $select[] = $email ? "COALESCE(`{$email}`, '') AS email" : "'' AS email";
    $select[] = $phone ? "COALESCE(`{$phone}`, '') AS phone" : "'' AS phone";
    $where = [];
    if (isset($columns['status'])) $where[] = "(`status` = 'active' OR `status` IS NULL)";
    if (isset($columns['user_type'])) $where[] = "LOWER(COALESCE(`user_type`, 'citizen')) = 'citizen'";
    $sql = 'SELECT ' . implode(', ', $select) . ' FROM users' . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . ' LIMIT 10000';
    return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
}

function bulletinLoadPushTokens(PDO $pdo, array $userIds): array
{
    $ids = array_values(array_unique(array_filter(array_map('intval', $userIds), fn($id) => $id > 0)));
    if (!$ids) return [];
    $table = resolveDeviceRegistryTable($pdo);
    $columns = bulletinReadableColumns($pdo, $table);
    if (!isset($columns['user_id'])) return [];
    $tokenColumns = array_values(array_filter(['fcm_token', 'push_token'], fn($column) => isset($columns[$column])));
    if (!$tokenColumns) return [];
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $active = isset($columns['is_active']) ? ' AND is_active = 1' : '';
    $stmt = $pdo->prepare('SELECT user_id, ' . implode(', ', $tokenColumns) . " FROM {$table} WHERE user_id IN ({$placeholders}){$active}");
    $stmt->execute($ids);
    $tokens = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        foreach ($tokenColumns as $column) {
            $token = trim((string)($row[$column] ?? ''));
            if ($token !== '') $tokens[(int)$row['user_id']][$token] = true;
        }
    }
    return array_map('array_keys', $tokens);
}

function bulletinInsertAlert(PDO $pdo, string $title, string $message, string $severity, string $source, string $category, ?int $categoryId): int
{
    $table = bulletinResolveAlertsTable($pdo);
    $columns = bulletinReadableColumns($pdo, $table);
    $values = [
        'title' => $title,
        'message' => $message,
        'content' => $message,
        'category_id' => $categoryId,
        'category' => ucfirst($category),
        'severity' => strtolower($severity),
        'source' => $source,
        'status' => 'active',
        'created_at' => date('Y-m-d H:i:s')
    ];
    $insertColumns = [];
    $params = [];
    foreach ($values as $column => $value) {
        if (isset($columns[$column])) {
            $insertColumns[] = "`{$column}`";
            $params[] = $value;
        }
    }
    $placeholders = implode(',', array_fill(0, count($params), '?'));
    $stmt = $pdo->prepare("INSERT INTO {$table} (" . implode(',', $insertColumns) . ") VALUES ({$placeholders})");
    $stmt->execute($params);
    return (int)$pdo->lastInsertId();
}

function bulletinPersistRecipients(PDO $pdo, int $alertId, array $recipients): void
{
    if ($alertId <= 0) return;
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS alert_recipients (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            alert_id BIGINT UNSIGNED NOT NULL,
            user_id BIGINT UNSIGNED NOT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_alert_user (alert_id, user_id),
            INDEX idx_user_id (user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $stmt = $pdo->prepare('INSERT IGNORE INTO alert_recipients (alert_id, user_id) VALUES (?, ?)');
        foreach ($recipients as $recipient) {
            $userId = (int)($recipient['id'] ?? 0);
            if ($userId > 0) $stmt->execute([$alertId, $userId]);
        }
    } catch (Throwable $e) {
        error_log('Bulletin recipient map unavailable: ' . $e->getMessage());
    }
}

function queueBulletinBroadcast(PDO $pdo, array $payload): array
{
    bulletinEnsureDeliveryTables($pdo);
    $title = trim((string)($payload['title'] ?? 'Emergency Bulletin'));
    $message = trim((string)($payload['message'] ?? 'Please monitor official advisories.'));
    $severity = strtolower((string)($payload['severity'] ?? 'high'));
    if (!in_array($severity, ['low', 'medium', 'high', 'critical'], true)) $severity = 'high';
    $source = strtolower(trim((string)($payload['source'] ?? 'official_bulletin')));
    $category = strtolower(trim((string)($payload['category'] ?? 'general')));
    $channels = array_values(array_intersect(
        array_unique(array_filter(array_map('trim', explode(',', (string)($payload['channels'] ?? 'push,email'))))),
        ['push', 'email', 'sms']
    ));
    if (!$channels) $channels = ['push', 'email'];

    $recipients = bulletinLoadCitizens($pdo);
    $categoryId = bulletinFindCategoryId($pdo, $category);
    $alertId = bulletinInsertAlert($pdo, $title, $message, $severity, $source, $category, $categoryId);
    bulletinPersistRecipients($pdo, $alertId, $recipients);

    $logStmt = $pdo->prepare("INSERT INTO notification_logs
        (channel, message, recipients, priority, status, sent_at, sent_by, ip_address)
        VALUES (?, ?, 'all_citizens', ?, 'queued', NOW(), ?, '127.0.0.1')");
    $logStmt->execute([implode(',', $channels), $message, $severity, $source . '_auto_bulletin']);
    $logId = (int)$pdo->lastInsertId();

    $pushTokens = in_array('push', $channels, true) ? bulletinLoadPushTokens($pdo, array_column($recipients, 'id')) : [];
    $queueStmt = $pdo->prepare("INSERT INTO notification_queue
        (log_id, alert_id, recipient_id, recipient_type, recipient_value, channel, title, message, status)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending')");
    $queued = 0;
    foreach ($recipients as $recipient) {
        $userId = (int)$recipient['id'];
        foreach ($channels as $channel) {
            if ($channel === 'email' && filter_var($recipient['email'] ?? '', FILTER_VALIDATE_EMAIL)) {
                $queueStmt->execute([$logId, $alertId, $userId, 'email', $recipient['email'], 'email', $title, $message]);
                $queued++;
            } elseif ($channel === 'sms' && trim((string)($recipient['phone'] ?? '')) !== '') {
                $queueStmt->execute([$logId, $alertId, $userId, 'phone', $recipient['phone'], 'sms', $title, $message]);
                $queued++;
            } elseif ($channel === 'push') {
                foreach ($pushTokens[$userId] ?? [] as $token) {
                    $queueStmt->execute([$logId, $alertId, $userId, 'push_token', $token, 'push', $title, $message]);
                    $queued++;
                }
            }
        }
    }
    if ($queued === 0) {
        $pdo->prepare("UPDATE notification_logs SET status = 'completed', response = ? WHERE id = ?")
            ->execute(['Citizen web alert created; no deliverable email, SMS, or push addresses were available.', $logId]);
    }
    return ['log_id' => $logId, 'alert_id' => $alertId, 'recipients' => count($recipients), 'queued_jobs' => $queued];
}
