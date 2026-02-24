<?php
/**
 * Two-way chat logic helpers shared by ADMIN/USERS APIs.
 * Keeps status flow, filters, routing, and read behavior consistent.
 */

if (!function_exists('twc_active_statuses')) {
    function twc_active_statuses(): array {
        return ['active', 'open', 'in_progress', 'waiting_user'];
    }
}

if (!function_exists('twc_closed_statuses')) {
    function twc_closed_statuses(): array {
        return ['closed', 'resolved'];
    }
}

if (!function_exists('twc_is_closed_status')) {
    function twc_is_closed_status($status): bool {
        $status = strtolower(trim((string)$status));
        return in_array($status, twc_closed_statuses(), true);
    }
}

if (!function_exists('twc_is_active_status')) {
    function twc_is_active_status($status): bool {
        $status = strtolower(trim((string)$status));
        return in_array($status, twc_active_statuses(), true);
    }
}

if (!function_exists('twc_normalize_status_input')) {
    function twc_normalize_status_input($status): ?string {
        $status = strtolower(trim((string)$status));
        if ($status === '') return null;

        $map = [
            'active' => 'open',
            'open' => 'open',
            'in_progress' => 'in_progress',
            'waiting_user' => 'waiting_user',
            'resolved' => 'resolved',
            'closed' => 'closed',
            'reopen' => 'open',
            'reopened' => 'open',
        ];

        return $map[$status] ?? null;
    }
}

if (!function_exists('twc_table_exists')) {
    function twc_table_exists(PDO $pdo, string $tableName): bool {
        static $cache = [];
        $key = strtolower($tableName);
        if (array_key_exists($key, $cache)) {
            return $cache[$key];
        }
        try {
            $stmt = $pdo->prepare(
                'SELECT COUNT(*) FROM information_schema.tables
                 WHERE table_schema = DATABASE() AND table_name = ?'
            );
            $stmt->execute([$tableName]);
            $cache[$key] = ((int)$stmt->fetchColumn()) > 0;
            return $cache[$key];
        } catch (Throwable $e) {
            $cache[$key] = false;
            return false;
        }
    }
}

if (!function_exists('twc_table_usable')) {
    /**
     * Checks whether a table can actually be queried.
     * Handles "table exists but doesn't exist in engine" corruption cases.
     */
    function twc_table_usable(PDO $pdo, string $tableName): bool {
        static $cache = [];
        $key = strtolower($tableName);
        if (array_key_exists($key, $cache)) {
            return $cache[$key];
        }

        if (!twc_table_exists($pdo, $tableName)) {
            $cache[$key] = false;
            return false;
        }

        try {
            $pdo->query("SELECT 1 FROM `$tableName` LIMIT 1");
            $cache[$key] = true;
            return true;
        } catch (Throwable $e) {
            error_log("TWC table unusable ($tableName): " . $e->getMessage());
            $cache[$key] = false;
            return false;
        }
    }
}

if (!function_exists('twc_chat_storage_available')) {
    /**
     * Minimal table health check required by chat APIs.
     */
    function twc_chat_storage_available(PDO $pdo): bool {
        return twc_table_usable($pdo, 'conversations') && twc_table_usable($pdo, 'chat_messages');
    }
}

if (!function_exists('twc_column_exists')) {
    function twc_column_exists(PDO $pdo, string $tableName, string $columnName, bool $refresh = false): bool {
        static $cache = [];
        $key = strtolower($tableName . '.' . $columnName);
        if (!$refresh && array_key_exists($key, $cache)) {
            return $cache[$key];
        }
        try {
            $stmt = $pdo->prepare(
                'SELECT COUNT(*) FROM information_schema.columns
                 WHERE table_schema = DATABASE()
                   AND table_name = ?
                   AND column_name = ?'
            );
            $stmt->execute([$tableName, $columnName]);
            $cache[$key] = ((int)$stmt->fetchColumn()) > 0;
            return $cache[$key];
        } catch (Throwable $e) {
            $cache[$key] = false;
            return false;
        }
    }
}

if (!function_exists('twc_placeholders')) {
    function twc_placeholders(array $values): string {
        return implode(',', array_fill(0, count($values), '?'));
    }
}

if (!function_exists('twc_status_filter_clause')) {
    /**
     * Returns SQL filter clause for conversation status.
     * Appends bind params into $params.
     */
    function twc_status_filter_clause(string $statusFilter, array &$params, string $alias = 'c'): string {
        $statusFilter = strtolower(trim($statusFilter));
        if ($statusFilter === '' || $statusFilter === 'all') {
            return '';
        }

        if ($statusFilter === 'assigned') {
            $active = twc_active_statuses();
            $params = array_merge($params, $active);
            return " AND {$alias}.status IN (" . twc_placeholders($active) . ") AND COALESCE({$alias}.assigned_to, 0) > 0 ";
        }

        if ($statusFilter === 'active' || $statusFilter === 'open') {
            $active = twc_active_statuses();
            $params = array_merge($params, $active);
            return " AND {$alias}.status IN (" . twc_placeholders($active) . ") ";
        }

        if ($statusFilter === 'closed') {
            $closed = twc_closed_statuses();
            $params = array_merge($params, $closed);
            return " AND {$alias}.status IN (" . twc_placeholders($closed) . ") ";
        }

        $params[] = $statusFilter;
        return " AND {$alias}.status = ? ";
    }
}

if (!function_exists('twc_normalize_category')) {
    function twc_normalize_category($raw): string {
        $value = strtolower(trim((string)$raw));
        if ($value === '') return '';

        $value = str_replace(['-', ' '], '_', $value);
        $map = [
            'emergency' => 'emergency_response',
            'response' => 'emergency_response',
            'emergency_response' => 'emergency_response',
            'traffic' => 'traffic_transport',
            'traffic_transport' => 'traffic_transport',
            'transport' => 'traffic_transport',
            'community' => 'community_policing',
            'community_policing' => 'community_policing',
            'policing' => 'community_policing',
            'crime' => 'crime_analytics',
            'crime_analytics' => 'crime_analytics',
            'public_safety' => 'public_safety_campaign',
            'public_safety_campaign' => 'public_safety_campaign',
            'health' => 'health_inspection',
            'health_inspection' => 'health_inspection',
            'disaster' => 'disaster_preparedness',
            'disaster_preparedness' => 'disaster_preparedness',
            'incident' => 'incident_nlp',
            'incident_nlp' => 'incident_nlp',
            'nlp' => 'incident_nlp',
            'multilingual' => 'emergency_comm',
            'communication' => 'emergency_comm',
            'emergency_comm' => 'emergency_comm',
        ];

        return $map[$value] ?? $value;
    }
}

if (!function_exists('twc_normalize_priority')) {
    function twc_normalize_priority($rawPriority, string $message = '', string $category = ''): string {
        $priority = strtolower(trim((string)$rawPriority));
        if ($priority === 'urgent' || $priority === 'normal') {
            return $priority;
        }

        $hay = strtolower($message . ' ' . $category);
        if (preg_match('/\b(urgent|emergency|life.?threat|fire|flood|earthquake|bomb|critical|help now|accident)\b/', $hay)) {
            return 'urgent';
        }
        return 'normal';
    }
}

if (!function_exists('twc_priority_rank')) {
    function twc_priority_rank(string $priority): int {
        return strtolower(trim($priority)) === 'urgent' ? 0 : 1;
    }
}

if (!function_exists('twc_pick_assignee')) {
    /**
     * Picks an active admin/staff as fallback assignee.
     * Returns admin_user.id or null if not available.
     */
    function twc_pick_assignee(PDO $pdo): ?int {
        if (!twc_table_exists($pdo, 'admin_user')) {
            return null;
        }
        try {
            $stmt = $pdo->query("
                SELECT id
                FROM admin_user
                WHERE status = 'active'
                  AND role IN ('staff', 'admin', 'super_admin')
                ORDER BY FIELD(role, 'staff', 'admin', 'super_admin'), COALESCE(last_login, '1970-01-01') DESC, id ASC
                LIMIT 1
            ");
            $id = $stmt->fetchColumn();
            return $id ? (int)$id : null;
        } catch (Throwable $e) {
            return null;
        }
    }
}

if (!function_exists('twc_ui_conversation_status')) {
    /**
     * Backward-compatible status for older UI that only understands active/closed.
     */
    function twc_ui_conversation_status($rawStatus): string {
        return twc_is_closed_status($rawStatus) ? 'closed' : 'active';
    }
}

if (!function_exists('twc_safe_int')) {
    function twc_safe_int($value): ?int {
        if ($value === null || $value === '') return null;
        return is_numeric($value) ? (int)$value : null;
    }
}

if (!function_exists('twc_status_for_db')) {
    /**
     * Maps workflow status to a status value supported by DB schema.
     * If conversations.status is ENUM and does not contain workflow values,
     * this safely falls back to active/closed.
     */
    function twc_status_for_db(PDO $pdo, string $desiredStatus): string {
        $desiredStatus = strtolower(trim($desiredStatus));
        if ($desiredStatus === '') {
            return 'active';
        }

        static $enumValues = null;
        if ($enumValues === null) {
            $enumValues = [];
            try {
                $stmt = $pdo->query("SHOW COLUMNS FROM conversations LIKE 'status'");
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($row && isset($row['Type']) && stripos((string)$row['Type'], 'enum(') === 0) {
                    $type = (string)$row['Type'];
                    if (preg_match("/^enum\\((.*)\\)$/i", $type, $m)) {
                        $raw = $m[1];
                        $parts = str_getcsv($raw, ',', "'");
                        $enumValues = array_map('strtolower', array_map('trim', $parts));
                    }
                }
            } catch (Throwable $e) {
                $enumValues = [];
            }
        }

        // Non-enum status column: assume workflow values are accepted.
        if (empty($enumValues)) {
            return $desiredStatus;
        }

        if (in_array($desiredStatus, $enumValues, true)) {
            return $desiredStatus;
        }
        if (twc_is_closed_status($desiredStatus) && in_array('closed', $enumValues, true)) {
            return 'closed';
        }
        if (twc_is_active_status($desiredStatus) && in_array('active', $enumValues, true)) {
            return 'active';
        }
        if (in_array('active', $enumValues, true)) {
            return 'active';
        }
        return $desiredStatus;
    }
}

if (!function_exists('twc_ensure_chat_attachment_columns')) {
    /**
     * Adds attachment columns to chat_messages if they do not exist yet.
     */
    function twc_ensure_chat_attachment_columns(PDO $pdo): bool {
        if (!twc_table_exists($pdo, 'chat_messages')) {
            return false;
        }

        $columns = [
            'attachment_url' => "ALTER TABLE chat_messages ADD COLUMN attachment_url VARCHAR(512) NULL AFTER message_text",
            'attachment_mime' => "ALTER TABLE chat_messages ADD COLUMN attachment_mime VARCHAR(100) NULL AFTER attachment_url",
            'attachment_size' => "ALTER TABLE chat_messages ADD COLUMN attachment_size INT UNSIGNED NULL AFTER attachment_mime",
        ];

        foreach ($columns as $name => $ddl) {
            if (twc_column_exists($pdo, 'chat_messages', $name)) {
                continue;
            }
            try {
                $pdo->exec($ddl);
                twc_column_exists($pdo, 'chat_messages', $name, true);
            } catch (Throwable $e) {
                error_log("TWC attachment column ensure warning ($name): " . $e->getMessage());
            }
        }

        return twc_column_exists($pdo, 'chat_messages', 'attachment_url', true);
    }
}

if (!function_exists('twc_chat_upload_dir')) {
    function twc_chat_upload_dir(): string {
        return dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'USERS' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'chat';
    }
}

if (!function_exists('twc_app_base_url')) {
    function twc_app_base_url(): string {
        $scriptName = str_replace('\\', '/', (string)($_SERVER['SCRIPT_NAME'] ?? ''));
        if ($scriptName === '') {
            return '';
        }

        foreach (['/USERS/api/', '/ADMIN/api/', '/PHP/api/'] as $marker) {
            $pos = stripos($scriptName, $marker);
            if ($pos !== false) {
                return rtrim(substr($scriptName, 0, $pos), '/');
            }
        }

        $dir = rtrim(dirname($scriptName), '/');
        return $dir === '.' ? '' : $dir;
    }
}

if (!function_exists('twc_chat_upload_url')) {
    function twc_chat_upload_url(string $filename): string {
        $base = twc_app_base_url();
        return $base . '/USERS/uploads/chat/' . rawurlencode($filename);
    }
}

if (!function_exists('twc_normalize_public_url')) {
    /**
     * Normalizes app-local URLs for deployments hosted in a subdirectory
     * (e.g. /EMERGENCY-COM) while preserving absolute http(s) URLs.
     */
    function twc_normalize_public_url($url): ?string {
        if ($url === null) {
            return null;
        }
        $raw = trim((string)$url);
        if ($raw === '') {
            return null;
        }
        if (preg_match('/^https?:\\/\\//i', $raw) === 1) {
            return $raw;
        }

        $raw = str_replace('\\', '/', $raw);

        // Compatibility for legacy attachment URL shapes seen in older rows:
        // - chat-attachment.php?id=...
        // - /.../chat-attachment.php/<attachment_id>
        // - raw attachment_id only
        if (preg_match('/^[A-Za-z0-9_-]{24,80}$/', $raw) === 1) {
            $raw = '/USERS/api/chat-attachment.php?id=' . rawurlencode($raw);
        } else {
            $rawPath = parse_url($raw, PHP_URL_PATH);
            if (is_string($rawPath) && stripos($rawPath, 'chat-attachment.php') !== false) {
                $rawQuery = (string)(parse_url($raw, PHP_URL_QUERY) ?? '');
                if ($rawQuery === '' && preg_match('#chat-attachment\\.php/([A-Za-z0-9_-]{12,80})/?$#i', $rawPath, $m) === 1) {
                    $rawQuery = 'id=' . rawurlencode($m[1]);
                }
                $raw = '/USERS/api/chat-attachment.php' . ($rawQuery !== '' ? ('?' . $rawQuery) : '');
            }
        }

        $base = trim((string)twc_app_base_url());
        if ($base === '' || $base === '/') {
            return $raw;
        }
        $base = '/' . trim($base, '/');

        if (strpos($raw, $base . '/') === 0) {
            return $raw;
        }
        if ($raw[0] !== '/') {
            return $base . '/' . ltrim($raw, '/');
        }
        if (preg_match('#^/(USERS|ADMIN|PHP)/#i', $raw) === 1) {
            return $base . $raw;
        }
        return $raw;
    }
}

if (!function_exists('twc_secure_cfg')) {
    /**
     * Config helper that works even when config.env.php is not explicitly loaded.
     */
    function twc_secure_cfg(string $key, $default = null) {
        if (function_exists('getSecureConfig')) {
            return getSecureConfig($key, $default);
        }
        $value = getenv($key);
        if ($value !== false && $value !== '') {
            return $value;
        }
        return $default;
    }
}

if (!function_exists('twc_chat_image_storage_driver')) {
    /**
     * Supported values: filesystem (default), postgres.
     */
    function twc_chat_image_storage_driver(): string {
        $raw = strtolower(trim((string)twc_secure_cfg('CHAT_IMAGE_STORAGE_DRIVER', 'filesystem')));
        return in_array($raw, ['filesystem', 'postgres'], true) ? $raw : 'filesystem';
    }
}

if (!function_exists('twc_postgres_image_storage_enabled')) {
    function twc_postgres_image_storage_enabled(): bool {
        return twc_chat_image_storage_driver() === 'postgres';
    }
}

if (!function_exists('twc_postgres_image_pdo')) {
    /**
     * Returns a PostgreSQL PDO connection for image storage, or null when unavailable.
     */
    function twc_postgres_image_pdo(): ?PDO {
        static $attempted = false;
        static $pgPdo = null;

        if ($attempted) {
            return $pgPdo;
        }
        $attempted = true;

        if (!extension_loaded('pdo_pgsql')) {
            error_log('TWC PostgreSQL image storage disabled: pdo_pgsql extension not loaded');
            return null;
        }

        $host = trim((string)twc_secure_cfg('PG_IMG_HOST', ''));
        $port = (int)twc_secure_cfg('PG_IMG_PORT', 5432);
        $dbName = trim((string)twc_secure_cfg('PG_IMG_DB', ''));
        $user = trim((string)twc_secure_cfg('PG_IMG_USER', ''));
        $pass = (string)twc_secure_cfg('PG_IMG_PASS', '');
        $sslmode = trim((string)twc_secure_cfg('PG_IMG_SSLMODE', 'prefer'));
        $channelBinding = trim((string)twc_secure_cfg('PG_IMG_CHANNEL_BINDING', ''));
        $libpqOptions = trim((string)twc_secure_cfg('PG_IMG_OPTIONS', ''));

        // Optional full URL override, e.g.:
        // postgresql://user:pass@host:5432/dbname?sslmode=require&channel_binding=require
        $pgUrl = trim((string)twc_secure_cfg('PG_IMG_URL', ''));
        if (!twc_postgres_image_storage_enabled() && $pgUrl === '') {
            // Driver is not postgres and no explicit PG URL is configured.
            // Keep default behavior (no PG connection) unless PG config is provided.
            return null;
        }
        if ($pgUrl !== '') {
            $parts = @parse_url($pgUrl);
            if (is_array($parts)) {
                if (!empty($parts['host'])) {
                    $host = (string)$parts['host'];
                }
                if (!empty($parts['port'])) {
                    $port = (int)$parts['port'];
                }
                if (!empty($parts['path'])) {
                    $dbName = ltrim((string)$parts['path'], '/');
                }
                if (isset($parts['user'])) {
                    $user = rawurldecode((string)$parts['user']);
                }
                if (isset($parts['pass'])) {
                    $pass = rawurldecode((string)$parts['pass']);
                }
                if (!empty($parts['query'])) {
                    $query = [];
                    parse_str((string)$parts['query'], $query);
                    if (!empty($query['sslmode'])) {
                        $sslmode = (string)$query['sslmode'];
                    }
                    if (!empty($query['channel_binding'])) {
                        $channelBinding = (string)$query['channel_binding'];
                    }
                    if (!empty($query['options'])) {
                        $libpqOptions = (string)$query['options'];
                    }
                }
            } else {
                error_log('TWC PostgreSQL image storage: invalid PG_IMG_URL');
            }
        }

        if ($host === '' || $dbName === '' || $user === '') {
            error_log('TWC PostgreSQL image storage config incomplete (need PG_IMG_HOST, PG_IMG_DB, PG_IMG_USER)');
            return null;
        }

        // Neon pooler + older libpq may require endpoint via options when SNI is unavailable.
        if ($libpqOptions === '' && stripos($host, '-pooler.') !== false) {
            $labels = explode('.', $host);
            $endpointLabel = trim((string)($labels[0] ?? ''));
            if ($endpointLabel !== '') {
                // Keep full pooler label to match Neon SNI project inference.
                // Stripping "-pooler" can trigger "Inconsistent project name inferred from SNI" errors.
                $libpqOptions = 'endpoint=' . $endpointLabel;
            }
        }

        $dsn = "pgsql:host={$host};port={$port};dbname={$dbName};sslmode={$sslmode}";
        if ($libpqOptions !== '') {
            $dsn .= ';options=' . $libpqOptions;
        }
        $normalizedChannelBinding = strtolower($channelBinding);
        $dsnWithChannelBinding = $dsn;
        if (in_array($normalizedChannelBinding, ['require', 'prefer', 'disable'], true)) {
            $dsnWithChannelBinding .= ";channel_binding={$normalizedChannelBinding}";
        }
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_TIMEOUT => 10,
        ];

        try {
            $pgPdo = new PDO($dsnWithChannelBinding, $user, $pass, $options);
            return $pgPdo;
        } catch (Throwable $e) {
            $message = $e->getMessage();
            // Older libpq/PHP builds may not support channel_binding; retry without it.
            if (
                $dsnWithChannelBinding !== $dsn &&
                stripos($message, 'invalid connection option "channel_binding"') !== false
            ) {
                try {
                    $pgPdo = new PDO($dsn, $user, $pass, $options);
                    return $pgPdo;
                } catch (Throwable $retryError) {
                    error_log('TWC PostgreSQL image storage retry without channel_binding failed: ' . $retryError->getMessage());
                    $pgPdo = null;
                    return null;
                }
            }
            error_log('TWC PostgreSQL image storage connection failed: ' . $e->getMessage());
            $pgPdo = null;
            return null;
        }
    }
}

if (!function_exists('twc_postgres_image_table_ready')) {
    function twc_postgres_image_table_ready(): bool {
        static $ready = null;
        if ($ready !== null) {
            return $ready;
        }

        $pg = twc_postgres_image_pdo();
        if (!$pg) {
            $ready = false;
            return $ready;
        }

        try {
            $pg->exec("
                CREATE TABLE IF NOT EXISTS chat_attachments (
                    attachment_id VARCHAR(64) PRIMARY KEY,
                    mime_type VARCHAR(100) NOT NULL,
                    byte_size INTEGER NOT NULL,
                    original_name TEXT NULL,
                    image_data BYTEA NOT NULL,
                    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
                )
            ");
            $ready = true;
            return $ready;
        } catch (Throwable $e) {
            error_log('TWC PostgreSQL image storage table ensure failed: ' . $e->getMessage());
            $ready = false;
            return $ready;
        }
    }
}

if (!function_exists('twc_postgres_attachment_url')) {
    function twc_postgres_attachment_url(string $attachmentId): string {
        $base = twc_app_base_url();
        return $base . '/USERS/api/chat-attachment.php?id=' . rawurlencode($attachmentId);
    }
}

if (!function_exists('twc_store_attachment_in_postgres')) {
    /**
     * Stores uploaded image bytes in PostgreSQL and returns attachment metadata.
     */
    function twc_store_attachment_in_postgres(string $tmpFile, string $mimeType, int $byteSize, ?string $originalName = null): ?array {
        if (!twc_postgres_image_table_ready()) {
            return null;
        }

        $pg = twc_postgres_image_pdo();
        if (!$pg) {
            return null;
        }

        $binary = @file_get_contents($tmpFile);
        if ($binary === false) {
            return null;
        }

        try {
            $attachmentId = bin2hex(random_bytes(16));
        } catch (Throwable $e) {
            $attachmentId = sha1(uniqid('att_', true));
        }

        try {
            $stmt = $pg->prepare("
                INSERT INTO chat_attachments
                (attachment_id, mime_type, byte_size, original_name, image_data, created_at)
                VALUES (?, ?, ?, ?, ?, NOW())
            ");
            $stmt->bindValue(1, $attachmentId, PDO::PARAM_STR);
            $stmt->bindValue(2, $mimeType, PDO::PARAM_STR);
            $stmt->bindValue(3, $byteSize, PDO::PARAM_INT);
            $stmt->bindValue(4, $originalName, PDO::PARAM_STR);
            $stmt->bindValue(5, $binary, PDO::PARAM_LOB);
            $stmt->execute();

            return [
                'id' => $attachmentId,
                'url' => twc_postgres_attachment_url($attachmentId),
                'mime' => $mimeType,
                'size' => $byteSize,
                'storage' => 'postgres',
            ];
        } catch (Throwable $e) {
            error_log('TWC PostgreSQL image storage insert failed: ' . $e->getMessage());
            return null;
        }
    }
}

if (!function_exists('twc_fetch_attachment_from_postgres')) {
    /**
     * Fetches attachment bytes from PostgreSQL.
     */
    function twc_fetch_attachment_from_postgres(string $attachmentId): ?array {
        if ($attachmentId === '' || !preg_match('/^[A-Za-z0-9_-]{12,80}$/', $attachmentId)) {
            return null;
        }
        if (!twc_postgres_image_table_ready()) {
            return null;
        }

        $pg = twc_postgres_image_pdo();
        if (!$pg) {
            return null;
        }

        try {
            $stmt = $pg->prepare("
                SELECT attachment_id, mime_type, byte_size, image_data
                FROM chat_attachments
                WHERE attachment_id = ?
                LIMIT 1
            ");
            $stmt->execute([$attachmentId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$row) {
                return null;
            }

            $data = $row['image_data'];
            if (is_resource($data)) {
                $data = stream_get_contents($data);
            }
            if (!is_string($data) || $data === '') {
                return null;
            }
            // Some PostgreSQL/PDO setups return BYTEA as hex string prefixed with \x
            if (strpos($data, '\\x') === 0) {
                $decoded = @hex2bin(substr($data, 2));
                if ($decoded !== false) {
                    $data = $decoded;
                }
            }

            return [
                'id' => (string)$row['attachment_id'],
                'mime' => (string)$row['mime_type'],
                'size' => (int)$row['byte_size'],
                'data' => $data,
            ];
        } catch (Throwable $e) {
            error_log('TWC PostgreSQL image storage fetch failed: ' . $e->getMessage());
            return null;
        }
    }
}

if (!function_exists('twc_incident_cache_key')) {
    /**
     * Stable hash key for incident classification cache.
     */
    function twc_incident_cache_key(string $message, string $rawCategory = '', string $rawPriority = ''): ?string {
        $normalizedMessage = trim((string)preg_replace('/\s+/u', ' ', strtolower($message)));
        if ($normalizedMessage === '') {
            return null;
        }
        $normalizedCategory = trim((string)preg_replace('/\s+/u', ' ', strtolower($rawCategory)));
        $normalizedPriority = trim((string)preg_replace('/\s+/u', ' ', strtolower($rawPriority)));
        return hash('sha256', $normalizedMessage . '|' . $normalizedCategory . '|' . $normalizedPriority);
    }
}

if (!function_exists('twc_incident_cache_ttl_seconds')) {
    function twc_incident_cache_ttl_seconds(): int {
        $raw = (int)twc_secure_cfg('CHAT_INCIDENT_CACHE_TTL_SECONDS', 2592000); // 30 days
        if ($raw < 60) {
            return 60;
        }
        if ($raw > 31536000) {
            return 31536000;
        }
        return $raw;
    }
}

if (!function_exists('twc_postgres_incident_cache_table_ready')) {
    function twc_postgres_incident_cache_table_ready(): bool {
        static $attempted = false;
        static $ready = false;

        if ($attempted) {
            return $ready;
        }
        $attempted = true;

        $pg = twc_postgres_image_pdo();
        if (!$pg) {
            return false;
        }

        try {
            $pg->exec("
                CREATE TABLE IF NOT EXISTS chat_incident_cache (
                    cache_key VARCHAR(64) PRIMARY KEY,
                    normalized_input TEXT NOT NULL,
                    raw_category VARCHAR(120) NULL,
                    raw_priority VARCHAR(40) NULL,
                    category VARCHAR(120) NOT NULL,
                    priority VARCHAR(20) NOT NULL,
                    hit_count INTEGER NOT NULL DEFAULT 1,
                    created_at TIMESTAMP WITHOUT TIME ZONE NOT NULL DEFAULT NOW(),
                    updated_at TIMESTAMP WITHOUT TIME ZONE NOT NULL DEFAULT NOW()
                )
            ");
            $pg->exec("CREATE INDEX IF NOT EXISTS idx_chat_incident_cache_updated_at ON chat_incident_cache(updated_at DESC)");
            $ready = true;
        } catch (Throwable $e) {
            $ready = false;
            error_log('TWC PostgreSQL incident cache table ensure failed: ' . $e->getMessage());
        }

        return $ready;
    }
}

if (!function_exists('twc_postgres_incident_cache_fetch')) {
    /**
     * Returns cached ['category' => string, 'priority' => string] or null.
     */
    function twc_postgres_incident_cache_fetch(string $message, string $rawCategory = '', string $rawPriority = ''): ?array {
        $cacheKey = twc_incident_cache_key($message, $rawCategory, $rawPriority);
        if ($cacheKey === null || !twc_postgres_incident_cache_table_ready()) {
            return null;
        }

        $pg = twc_postgres_image_pdo();
        if (!$pg) {
            return null;
        }

        try {
            $ttl = twc_incident_cache_ttl_seconds();
            $stmt = $pg->prepare("
                SELECT category, priority
                FROM chat_incident_cache
                WHERE cache_key = ?
                  AND updated_at >= (NOW() - (? * INTERVAL '1 second'))
                LIMIT 1
            ");
            $stmt->execute([$cacheKey, $ttl]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$row) {
                return null;
            }

            // Best effort stats update; ignore failures.
            try {
                $touch = $pg->prepare("
                    UPDATE chat_incident_cache
                    SET hit_count = hit_count + 1, updated_at = NOW()
                    WHERE cache_key = ?
                ");
                $touch->execute([$cacheKey]);
            } catch (Throwable $_) {
            }

            $category = trim((string)($row['category'] ?? ''));
            $priority = strtolower(trim((string)($row['priority'] ?? '')));
            if ($category === '' || !in_array($priority, ['urgent', 'normal'], true)) {
                return null;
            }

            return [
                'category' => $category,
                'priority' => $priority,
            ];
        } catch (Throwable $e) {
            error_log('TWC PostgreSQL incident cache fetch failed: ' . $e->getMessage());
            return null;
        }
    }
}

if (!function_exists('twc_postgres_incident_cache_store')) {
    function twc_postgres_incident_cache_store(
        string $message,
        string $rawCategory,
        string $rawPriority,
        string $category,
        string $priority
    ): bool {
        $cacheKey = twc_incident_cache_key($message, $rawCategory, $rawPriority);
        if ($cacheKey === null || !twc_postgres_incident_cache_table_ready()) {
            return false;
        }

        $normalizedMessage = trim((string)preg_replace('/\s+/u', ' ', strtolower($message)));
        $safeCategory = trim((string)$category);
        $safePriority = strtolower(trim((string)$priority));
        if ($normalizedMessage === '' || $safeCategory === '' || !in_array($safePriority, ['urgent', 'normal'], true)) {
            return false;
        }

        $pg = twc_postgres_image_pdo();
        if (!$pg) {
            return false;
        }

        try {
            $stmt = $pg->prepare("
                INSERT INTO chat_incident_cache
                    (cache_key, normalized_input, raw_category, raw_priority, category, priority, hit_count, created_at, updated_at)
                VALUES
                    (?, ?, ?, ?, ?, ?, 1, NOW(), NOW())
                ON CONFLICT (cache_key) DO UPDATE
                SET
                    normalized_input = EXCLUDED.normalized_input,
                    raw_category = EXCLUDED.raw_category,
                    raw_priority = EXCLUDED.raw_priority,
                    category = EXCLUDED.category,
                    priority = EXCLUDED.priority,
                    hit_count = chat_incident_cache.hit_count + 1,
                    updated_at = NOW()
            ");
            $stmt->execute([
                $cacheKey,
                $normalizedMessage,
                $rawCategory !== '' ? $rawCategory : null,
                $rawPriority !== '' ? $rawPriority : null,
                $safeCategory,
                $safePriority,
            ]);
            return true;
        } catch (Throwable $e) {
            error_log('TWC PostgreSQL incident cache store failed: ' . $e->getMessage());
            return false;
        }
    }
}
