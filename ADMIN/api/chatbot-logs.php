<?php
/**
 * Chatbot Logs API (Admin)
 * Lists chatbot interactions stored in Neon/PostgreSQL.
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/chat-logic.php';
require_once __DIR__ . '/../../USERS/api/config.env.php';

session_start();

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

/**
 * @param mixed $default
 * @return mixed
 */
function chatbot_logs_cfg(string $key, $default = null) {
    if (function_exists('getSecureConfig')) {
        return getSecureConfig($key, $default);
    }
    $env = getenv($key);
    if ($env !== false && trim((string)$env) !== '') {
        return $env;
    }
    return $default;
}

function chatbot_logs_neon_url(): string {
    $candidates = [
        trim((string)chatbot_logs_cfg('CHATBOT_NEON_URL', '')),
        trim((string)chatbot_logs_cfg('NEON_CHATBOT_URL', '')),
        trim((string)chatbot_logs_cfg('NEON_DATABASE_URL', '')),
        trim((string)chatbot_logs_cfg('NEON_TRANSLATION_CACHE_URL', '')),
        trim((string)chatbot_logs_cfg('PG_IMG_URL', '')),
    ];
    foreach ($candidates as $candidate) {
        if ($candidate !== '') {
            return $candidate;
        }
    }
    return '';
}

function chatbot_logs_table_name(): string {
    $raw = trim((string)chatbot_logs_cfg('CHATBOT_NEON_TABLE', 'chatbot_interactions'));
    $table = preg_replace('/[^a-zA-Z0-9_]/', '', $raw);
    if (strlen((string)$table) > 48) {
        $table = substr((string)$table, 0, 48);
    }
    return $table !== '' ? $table : 'chatbot_interactions';
}

function chatbot_logs_pdo(): ?PDO {
    static $attempted = false;
    static $pgPdo = null;

    if ($attempted) {
        return $pgPdo;
    }
    $attempted = true;

    if (!extension_loaded('pdo_pgsql')) {
        return null;
    }

    $host = trim((string)chatbot_logs_cfg('CHATBOT_NEON_HOST', chatbot_logs_cfg('PG_IMG_HOST', '')));
    $port = (int)chatbot_logs_cfg('CHATBOT_NEON_PORT', chatbot_logs_cfg('PG_IMG_PORT', 5432));
    $dbName = trim((string)chatbot_logs_cfg('CHATBOT_NEON_DB', chatbot_logs_cfg('PG_IMG_DB', '')));
    $user = trim((string)chatbot_logs_cfg('CHATBOT_NEON_USER', chatbot_logs_cfg('PG_IMG_USER', '')));
    $pass = (string)chatbot_logs_cfg('CHATBOT_NEON_PASS', chatbot_logs_cfg('PG_IMG_PASS', ''));
    $sslmode = trim((string)chatbot_logs_cfg('CHATBOT_NEON_SSLMODE', chatbot_logs_cfg('PG_IMG_SSLMODE', 'require')));
    $channelBinding = trim((string)chatbot_logs_cfg('CHATBOT_NEON_CHANNEL_BINDING', chatbot_logs_cfg('PG_IMG_CHANNEL_BINDING', '')));
    $libpqOptions = trim((string)chatbot_logs_cfg('CHATBOT_NEON_OPTIONS', chatbot_logs_cfg('PG_IMG_OPTIONS', '')));

    $url = chatbot_logs_neon_url();
    if ($url !== '') {
        $parts = @parse_url($url);
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
        }
    }

    if ($host === '' || $dbName === '' || $user === '') {
        if (function_exists('twc_postgres_image_pdo')) {
            $fallback = twc_postgres_image_pdo();
            if ($fallback instanceof PDO) {
                $pgPdo = $fallback;
                return $pgPdo;
            }
        }
        return null;
    }

    if ($libpqOptions === '' && stripos($host, '-pooler.') !== false) {
        $labels = explode('.', $host);
        $endpointLabel = trim((string)($labels[0] ?? ''));
        if ($endpointLabel !== '') {
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
        $dsnWithChannelBinding .= ';channel_binding=' . $normalizedChannelBinding;
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
        if (
            $dsnWithChannelBinding !== $dsn &&
            stripos($e->getMessage(), 'invalid connection option "channel_binding"') !== false
        ) {
            try {
                $pgPdo = new PDO($dsn, $user, $pass, $options);
                return $pgPdo;
            } catch (Throwable $retryError) {
                error_log('chatbot-logs API retry without channel_binding failed: ' . $retryError->getMessage());
                return null;
            }
        }
        error_log('chatbot-logs API connection failed: ' . $e->getMessage());
        return null;
    }
}

function chatbot_logs_table_ready(PDO $pg, string $tableName): bool {
    static $cache = [];
    if (isset($cache[$tableName])) {
        return $cache[$tableName];
    }

    if (!preg_match('/^[a-zA-Z0-9_]+$/', $tableName)) {
        $cache[$tableName] = false;
        return false;
    }

    try {
        $pg->exec("
            CREATE TABLE IF NOT EXISTS {$tableName} (
                id BIGSERIAL PRIMARY KEY,
                session_key VARCHAR(120) NULL,
                user_id VARCHAR(120) NULL,
                conversation_id VARCHAR(80) NULL,
                request_text TEXT NOT NULL,
                response_text TEXT NOT NULL,
                incident_type VARCHAR(64) NULL,
                incident_label VARCHAR(120) NULL,
                emergency_detected BOOLEAN NOT NULL DEFAULT FALSE,
                language_code VARCHAR(16) NULL,
                locale VARCHAR(40) NULL,
                model_used VARCHAR(80) NULL,
                used_rule_fallback BOOLEAN NOT NULL DEFAULT FALSE,
                qc_scope VARCHAR(24) NULL,
                qc_barangays TEXT NULL,
                metadata JSONB NULL,
                created_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
            )
        ");
        $pg->exec("ALTER TABLE {$tableName} ADD COLUMN IF NOT EXISTS session_key VARCHAR(120) NULL");
        $pg->exec("ALTER TABLE {$tableName} ADD COLUMN IF NOT EXISTS user_id VARCHAR(120) NULL");
        $pg->exec("ALTER TABLE {$tableName} ADD COLUMN IF NOT EXISTS conversation_id VARCHAR(80) NULL");
        $pg->exec("ALTER TABLE {$tableName} ADD COLUMN IF NOT EXISTS request_text TEXT NOT NULL DEFAULT ''");
        $pg->exec("ALTER TABLE {$tableName} ADD COLUMN IF NOT EXISTS response_text TEXT NOT NULL DEFAULT ''");
        $pg->exec("ALTER TABLE {$tableName} ADD COLUMN IF NOT EXISTS incident_type VARCHAR(64) NULL");
        $pg->exec("ALTER TABLE {$tableName} ADD COLUMN IF NOT EXISTS incident_label VARCHAR(120) NULL");
        $pg->exec("ALTER TABLE {$tableName} ADD COLUMN IF NOT EXISTS emergency_detected BOOLEAN NOT NULL DEFAULT FALSE");
        $pg->exec("ALTER TABLE {$tableName} ADD COLUMN IF NOT EXISTS language_code VARCHAR(16) NULL");
        $pg->exec("ALTER TABLE {$tableName} ADD COLUMN IF NOT EXISTS locale VARCHAR(40) NULL");
        $pg->exec("ALTER TABLE {$tableName} ADD COLUMN IF NOT EXISTS model_used VARCHAR(80) NULL");
        $pg->exec("ALTER TABLE {$tableName} ADD COLUMN IF NOT EXISTS used_rule_fallback BOOLEAN NOT NULL DEFAULT FALSE");
        $pg->exec("ALTER TABLE {$tableName} ADD COLUMN IF NOT EXISTS qc_scope VARCHAR(24) NULL");
        $pg->exec("ALTER TABLE {$tableName} ADD COLUMN IF NOT EXISTS qc_barangays TEXT NULL");
        $pg->exec("ALTER TABLE {$tableName} ADD COLUMN IF NOT EXISTS metadata JSONB NULL");
        $pg->exec("ALTER TABLE {$tableName} ADD COLUMN IF NOT EXISTS created_at TIMESTAMPTZ NOT NULL DEFAULT NOW()");
        $pg->exec("CREATE INDEX IF NOT EXISTS idx_{$tableName}_created_at ON {$tableName}(created_at DESC)");
        $cache[$tableName] = true;
        return true;
    } catch (Throwable $e) {
        error_log('chatbot-logs API table ensure failed: ' . $e->getMessage());
        $cache[$tableName] = false;
        return false;
    }
}

function chatbot_logs_sanitize_key(string $raw, int $maxLen = 64): string {
    $value = trim($raw);
    if ($value === '') {
        return '';
    }
    if (strlen($value) > $maxLen) {
        $value = substr($value, 0, $maxLen);
    }
    return preg_replace('/[^a-zA-Z0-9_:-]/', '', $value);
}

function chatbot_logs_parse_date(string $raw, string $timeSuffix): ?string {
    $raw = trim($raw);
    if ($raw === '') {
        return null;
    }
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $raw) !== 1) {
        return null;
    }
    $ts = strtotime($raw . ' ' . $timeSuffix);
    if ($ts === false) {
        return null;
    }
    return date('Y-m-d H:i:s', $ts);
}

try {
    $pg = chatbot_logs_pdo();
    if (!$pg) {
        echo json_encode([
            'success' => true,
            'items' => [],
            'page' => 1,
            'pageSize' => 20,
            'total' => 0,
            'totalPages' => 0,
            'summary' => [
                'total' => 0,
                'emergency' => 0,
                'last24h' => 0,
                'ruleFallback' => 0,
            ],
            'incidentTypes' => [],
            'languages' => [],
            'message' => 'Neon/PostgreSQL connection is not configured.',
        ]);
        exit;
    }

    $tableName = chatbot_logs_table_name();
    if (!chatbot_logs_table_ready($pg, $tableName)) {
        echo json_encode([
            'success' => true,
            'items' => [],
            'page' => 1,
            'pageSize' => 20,
            'total' => 0,
            'totalPages' => 0,
            'summary' => [
                'total' => 0,
                'emergency' => 0,
                'last24h' => 0,
                'ruleFallback' => 0,
            ],
            'incidentTypes' => [],
            'languages' => [],
            'message' => 'Chatbot logs table is not available.',
        ]);
        exit;
    }

    $page = max(1, (int)($_GET['page'] ?? 1));
    $pageSize = (int)($_GET['pageSize'] ?? ($_GET['limit'] ?? 20));
    if ($pageSize < 10) {
        $pageSize = 10;
    } elseif ($pageSize > 100) {
        $pageSize = 100;
    }
    $offset = ($page - 1) * $pageSize;

    $search = trim((string)($_GET['search'] ?? $_GET['q'] ?? ''));
    if (strlen($search) > 200) {
        $search = substr($search, 0, 200);
    }
    $incidentType = chatbot_logs_sanitize_key((string)($_GET['incidentType'] ?? ''));
    $language = chatbot_logs_sanitize_key((string)($_GET['language'] ?? ''), 16);
    $scope = chatbot_logs_sanitize_key((string)($_GET['scope'] ?? ''), 24);
    $emergencyRaw = strtolower(trim((string)($_GET['emergency'] ?? 'all')));
    $dateFrom = chatbot_logs_parse_date((string)($_GET['dateFrom'] ?? ''), '00:00:00');
    $dateTo = chatbot_logs_parse_date((string)($_GET['dateTo'] ?? ''), '23:59:59');

    $where = [];
    $params = [];
    if ($search !== '') {
        $where[] = "(
            request_text ILIKE ?
            OR response_text ILIKE ?
            OR COALESCE(incident_type, '') ILIKE ?
            OR COALESCE(user_id, '') ILIKE ?
            OR COALESCE(conversation_id, '') ILIKE ?
            OR COALESCE(qc_barangays, '') ILIKE ?
        )";
        $like = '%' . $search . '%';
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
    }

    if ($incidentType !== '' && $incidentType !== 'all') {
        $where[] = 'COALESCE(incident_type, \'\') = ?';
        $params[] = $incidentType;
    }
    if ($language !== '' && $language !== 'all') {
        $where[] = 'COALESCE(language_code, \'\') = ?';
        $params[] = $language;
    }
    if ($scope !== '' && $scope !== 'all') {
        $where[] = 'COALESCE(qc_scope, \'\') = ?';
        $params[] = $scope;
    }
    if ($emergencyRaw === 'yes' || $emergencyRaw === 'true' || $emergencyRaw === '1') {
        $where[] = 'emergency_detected = TRUE';
    } elseif ($emergencyRaw === 'no' || $emergencyRaw === 'false' || $emergencyRaw === '0') {
        $where[] = 'emergency_detected = FALSE';
    }
    if ($dateFrom !== null) {
        $where[] = 'created_at >= ?';
        $params[] = $dateFrom;
    }
    if ($dateTo !== null) {
        $where[] = 'created_at <= ?';
        $params[] = $dateTo;
    }

    $whereSql = '';
    if (!empty($where)) {
        $whereSql = ' WHERE ' . implode(' AND ', $where);
    }

    $countSql = "SELECT COUNT(*) FROM {$tableName}{$whereSql}";
    $countStmt = $pg->prepare($countSql);
    $countStmt->execute($params);
    $total = (int)$countStmt->fetchColumn();

    $itemsSql = "
        SELECT
            id,
            session_key,
            user_id,
            conversation_id,
            request_text,
            response_text,
            incident_type,
            incident_label,
            emergency_detected,
            language_code,
            locale,
            model_used,
            used_rule_fallback,
            qc_scope,
            qc_barangays,
            metadata::text AS metadata_json,
            created_at
        FROM {$tableName}
        {$whereSql}
        ORDER BY created_at DESC, id DESC
        LIMIT ? OFFSET ?
    ";
    $itemParams = $params;
    $itemParams[] = $pageSize;
    $itemParams[] = $offset;
    $itemsStmt = $pg->prepare($itemsSql);
    $itemsStmt->execute($itemParams);
    $rows = $itemsStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    $items = array_map(static function (array $row): array {
        $metadata = null;
        if (!empty($row['metadata_json'])) {
            $decoded = json_decode((string)$row['metadata_json'], true);
            if (is_array($decoded)) {
                $metadata = $decoded;
            }
        }

        return [
            'id' => isset($row['id']) ? (int)$row['id'] : 0,
            'sessionKey' => (string)($row['session_key'] ?? ''),
            'userId' => (string)($row['user_id'] ?? ''),
            'conversationId' => (string)($row['conversation_id'] ?? ''),
            'requestText' => (string)($row['request_text'] ?? ''),
            'responseText' => (string)($row['response_text'] ?? ''),
            'incidentType' => (string)($row['incident_type'] ?? ''),
            'incidentLabel' => (string)($row['incident_label'] ?? ''),
            'emergencyDetected' => !empty($row['emergency_detected']),
            'languageCode' => (string)($row['language_code'] ?? ''),
            'locale' => (string)($row['locale'] ?? ''),
            'modelUsed' => (string)($row['model_used'] ?? ''),
            'usedRuleFallback' => !empty($row['used_rule_fallback']),
            'qcScope' => (string)($row['qc_scope'] ?? ''),
            'qcBarangays' => (string)($row['qc_barangays'] ?? ''),
            'metadata' => $metadata,
            'createdAt' => (string)($row['created_at'] ?? ''),
        ];
    }, $rows);

    $summarySql = "
        SELECT
            COUNT(*) AS total_count,
            COALESCE(SUM(CASE WHEN emergency_detected THEN 1 ELSE 0 END), 0) AS emergency_count,
            COALESCE(SUM(CASE WHEN used_rule_fallback THEN 1 ELSE 0 END), 0) AS fallback_count,
            COALESCE(SUM(CASE WHEN created_at >= (NOW() - INTERVAL '24 hours') THEN 1 ELSE 0 END), 0) AS last_24h_count
        FROM {$tableName}
        {$whereSql}
    ";
    $summaryStmt = $pg->prepare($summarySql);
    $summaryStmt->execute($params);
    $summaryRow = $summaryStmt->fetch(PDO::FETCH_ASSOC) ?: [];

    $incidentTypesStmt = $pg->query("
        SELECT COALESCE(incident_type, '') AS incident_type, COUNT(*) AS total
        FROM {$tableName}
        WHERE COALESCE(incident_type, '') <> ''
        GROUP BY incident_type
        ORDER BY total DESC, incident_type ASC
        LIMIT 50
    ");
    $incidentTypesRows = $incidentTypesStmt ? ($incidentTypesStmt->fetchAll(PDO::FETCH_ASSOC) ?: []) : [];
    $incidentTypes = array_values(array_filter(array_map(static function ($row) {
        $v = trim((string)($row['incident_type'] ?? ''));
        return $v !== '' ? $v : null;
    }, $incidentTypesRows)));

    $languagesStmt = $pg->query("
        SELECT COALESCE(language_code, '') AS language_code, COUNT(*) AS total
        FROM {$tableName}
        WHERE COALESCE(language_code, '') <> ''
        GROUP BY language_code
        ORDER BY total DESC, language_code ASC
        LIMIT 20
    ");
    $languagesRows = $languagesStmt ? ($languagesStmt->fetchAll(PDO::FETCH_ASSOC) ?: []) : [];
    $languages = array_values(array_filter(array_map(static function ($row) {
        $v = trim((string)($row['language_code'] ?? ''));
        return $v !== '' ? $v : null;
    }, $languagesRows)));

    $totalPages = $pageSize > 0 ? (int)ceil($total / $pageSize) : 0;

    echo json_encode([
        'success' => true,
        'items' => $items,
        'page' => $page,
        'pageSize' => $pageSize,
        'total' => $total,
        'totalPages' => $totalPages,
        'summary' => [
            'total' => (int)($summaryRow['total_count'] ?? 0),
            'emergency' => (int)($summaryRow['emergency_count'] ?? 0),
            'last24h' => (int)($summaryRow['last_24h_count'] ?? 0),
            'ruleFallback' => (int)($summaryRow['fallback_count'] ?? 0),
        ],
        'incidentTypes' => $incidentTypes,
        'languages' => $languages,
    ]);
} catch (Throwable $e) {
    error_log('chatbot-logs API error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to load chatbot logs.',
        'error' => $e->getMessage(),
    ]);
}
