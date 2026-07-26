<?php
/** Resilient mobile device-token registry shared by web and mobile APIs. */

function deviceRegistryTableReadable(PDO $pdo, string $table): bool {
    if (!preg_match('/^[A-Za-z0-9_]+$/', $table)) return false;
    try {
        $pdo->query("SELECT 1 FROM `{$table}` LIMIT 1");
        return true;
    } catch (Throwable $e) {
        return false;
    }
}

function resolveDeviceRegistryTable(PDO $pdo): string {
    if (deviceRegistryTableReadable($pdo, 'user_devices')) return 'user_devices';
    $table = 'user_devices_runtime';
    $pdo->exec("CREATE TABLE IF NOT EXISTS {$table} (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id BIGINT UNSIGNED NOT NULL,
        device_id VARCHAR(255) NOT NULL,
        device_type VARCHAR(40) NOT NULL DEFAULT 'android',
        device_name VARCHAR(255) NULL,
        fcm_token TEXT NULL,
        push_token TEXT NULL,
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        last_active DATETIME NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uniq_user_device (user_id, device_id),
        INDEX idx_user_active (user_id, is_active)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    return $table;
}

function registerDeviceToken(PDO $pdo, int $userId, string $deviceId, string $deviceType, ?string $deviceName, ?string $token): bool {
    if ($userId <= 0 || trim($deviceId) === '') return false;
    $table = resolveDeviceRegistryTable($pdo);
    $stmt = $pdo->prepare("INSERT INTO {$table}
        (user_id, device_id, device_type, device_name, fcm_token, push_token, is_active, last_active)
        VALUES (?, ?, ?, ?, ?, ?, 1, NOW())
        ON DUPLICATE KEY UPDATE device_type = VALUES(device_type), device_name = VALUES(device_name),
            fcm_token = VALUES(fcm_token), push_token = VALUES(push_token), is_active = 1, last_active = NOW()");
    return $stmt->execute([$userId, trim($deviceId), trim($deviceType) ?: 'android', $deviceName, $token, $token]);
}

/**
 * Device-level notification registry. Unlike user_devices, this table also
 * supports an app installation that is being used in guest mode.
 */
function ensureAppNotificationDevicesTable(PDO $pdo): string {
    $table = 'app_notification_devices';
    $pdo->exec("CREATE TABLE IF NOT EXISTS {$table} (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id BIGINT UNSIGNED NULL,
        device_id VARCHAR(255) NOT NULL,
        device_type VARCHAR(40) NOT NULL DEFAULT 'android',
        device_name VARCHAR(255) NULL,
        push_token VARCHAR(512) NOT NULL,
        token_type VARCHAR(20) NOT NULL DEFAULT 'expo',
        notification_permission VARCHAR(20) NOT NULL DEFAULT 'granted',
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        last_active DATETIME NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uniq_app_notification_device (device_id),
        UNIQUE KEY uniq_app_notification_token (push_token),
        INDEX idx_app_notification_active (is_active, notification_permission),
        INDEX idx_app_notification_user (user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    return $table;
}

function registerAppNotificationDevice(
    PDO $pdo,
    ?int $userId,
    string $deviceId,
    string $deviceType,
    ?string $deviceName,
    string $pushToken,
    string $tokenType = 'expo',
    string $permission = 'granted'
): bool {
    $deviceId = trim($deviceId);
    $pushToken = trim($pushToken);
    if ($deviceId === '' || $pushToken === '') return false;
    $table = ensureAppNotificationDevicesTable($pdo);
    $resolvedUserId = $userId !== null && $userId > 0 ? $userId : null;
    $stmt = $pdo->prepare("INSERT INTO {$table}
        (user_id, device_id, device_type, device_name, push_token, token_type, notification_permission, is_active, last_active)
        VALUES (?, ?, ?, ?, ?, ?, ?, 1, NOW())
        ON DUPLICATE KEY UPDATE user_id = VALUES(user_id), device_type = VALUES(device_type),
            device_name = VALUES(device_name), push_token = VALUES(push_token), token_type = VALUES(token_type),
            notification_permission = VALUES(notification_permission), is_active = 1, last_active = NOW()");
    return $stmt->execute([
        $resolvedUserId,
        $deviceId,
        trim($deviceType) ?: 'android',
        $deviceName,
        $pushToken,
        trim($tokenType) ?: 'expo',
        trim($permission) ?: 'granted'
    ]);
}
