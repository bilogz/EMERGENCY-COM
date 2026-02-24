<?php
/**
 * Translation cache storage helper.
 *
 * Backends:
 * - mysql  : existing translation_cache table in MySQL
 * - neon   : PostgreSQL/Neon cache
 * - hybrid : read neon first then mysql, write to both
 */

if (!function_exists('translation_cache_cfg')) {
    function translation_cache_cfg($key, $default = null) {
        if (!function_exists('getSecureConfig')) {
            $secureConfigPath = __DIR__ . '/config.env.php';
            if (file_exists($secureConfigPath)) {
                require_once $secureConfigPath;
            }
        }

        if (function_exists('getSecureConfig')) {
            return getSecureConfig($key, $default);
        }

        $envValue = getenv($key);
        if ($envValue !== false && $envValue !== '') {
            return $envValue;
        }

        static $localConfigLoaded = false;
        static $localConfig = [];

        if (!$localConfigLoaded) {
            $localConfigLoaded = true;
            $userConfigPath = __DIR__ . '/config.local.php';
            $adminConfigPath = dirname(__DIR__, 2) . '/ADMIN/api/config.local.php';

            if (file_exists($adminConfigPath)) {
                $adminConfig = require $adminConfigPath;
                if (is_array($adminConfig)) {
                    $localConfig = array_merge($localConfig, $adminConfig);
                }
            }
            if (file_exists($userConfigPath)) {
                $userConfig = require $userConfigPath;
                if (is_array($userConfig)) {
                    $localConfig = array_merge($localConfig, $userConfig);
                }
            }
        }

        if (array_key_exists($key, $localConfig)) {
            return $localConfig[$key];
        }

        return $default;
    }
}

if (!function_exists('translation_cache_neon_url')) {
    function translation_cache_neon_url() {
        $url = trim((string)translation_cache_cfg('NEON_TRANSLATION_CACHE_URL', ''));
        if ($url !== '') {
            return $url;
        }

        // Backward-compatible fallback to shared PostgreSQL URL if already configured.
        return trim((string)translation_cache_cfg('PG_IMG_URL', ''));
    }
}

if (!function_exists('translation_cache_driver')) {
    function translation_cache_driver() {
        $raw = strtolower(trim((string)translation_cache_cfg('TRANSLATION_CACHE_DRIVER', '')));
        $allowed = ['mysql', 'neon', 'hybrid'];

        if ($raw === '') {
            return translation_cache_neon_url() !== '' ? 'hybrid' : 'mysql';
        }

        if (!in_array($raw, $allowed, true)) {
            return 'mysql';
        }

        return $raw;
    }
}

if (!function_exists('translation_cache_days')) {
    function translation_cache_days() {
        if (defined('TRANSLATION_CACHE_DAYS')) {
            return max(1, (int)TRANSLATION_CACHE_DAYS);
        }
        return max(1, (int)translation_cache_cfg('TRANSLATION_CACHE_DAYS', 30));
    }
}

if (!function_exists('translation_cache_backends')) {
    function translation_cache_backends() {
        $driver = translation_cache_driver();
        if ($driver === 'neon') {
            return ['neon'];
        }
        if ($driver === 'hybrid') {
            return ['neon', 'mysql'];
        }
        return ['mysql'];
    }
}

if (!function_exists('translation_cache_mysql_table')) {
    function translation_cache_mysql_table() {
        $raw = trim((string)translation_cache_cfg('TRANSLATION_CACHE_TABLE', 'translation_cache'));
        $table = preg_replace('/[^a-zA-Z0-9_]/', '', $raw);
        return $table !== '' ? $table : 'translation_cache';
    }
}

if (!function_exists('translation_cache_neon_table')) {
    function translation_cache_neon_table() {
        $raw = trim((string)translation_cache_cfg('NEON_TRANSLATION_CACHE_TABLE', ''));
        if ($raw === '') {
            $raw = translation_cache_mysql_table();
        }
        $table = preg_replace('/[^a-zA-Z0-9_]/', '', $raw);
        return $table !== '' ? $table : 'translation_cache';
    }
}

if (!function_exists('translation_cache_neon_pdo')) {
    function translation_cache_neon_pdo() {
        static $attempted = false;
        static $pgPdo = null;

        if ($attempted) {
            return $pgPdo;
        }
        $attempted = true;

        if (!extension_loaded('pdo_pgsql')) {
            error_log('Translation cache Neon backend skipped: pdo_pgsql extension is not loaded');
            return null;
        }

        $host = trim((string)translation_cache_cfg('NEON_TRANSLATION_CACHE_HOST', translation_cache_cfg('PG_IMG_HOST', '')));
        $port = (int)translation_cache_cfg('NEON_TRANSLATION_CACHE_PORT', translation_cache_cfg('PG_IMG_PORT', 5432));
        $dbName = trim((string)translation_cache_cfg('NEON_TRANSLATION_CACHE_DB', translation_cache_cfg('PG_IMG_DB', '')));
        $user = trim((string)translation_cache_cfg('NEON_TRANSLATION_CACHE_USER', translation_cache_cfg('PG_IMG_USER', '')));
        $pass = (string)translation_cache_cfg('NEON_TRANSLATION_CACHE_PASS', translation_cache_cfg('PG_IMG_PASS', ''));
        $sslmode = trim((string)translation_cache_cfg('NEON_TRANSLATION_CACHE_SSLMODE', translation_cache_cfg('PG_IMG_SSLMODE', 'require')));
        $channelBinding = trim((string)translation_cache_cfg('NEON_TRANSLATION_CACHE_CHANNEL_BINDING', translation_cache_cfg('PG_IMG_CHANNEL_BINDING', '')));
        $libpqOptions = trim((string)translation_cache_cfg('NEON_TRANSLATION_CACHE_OPTIONS', translation_cache_cfg('PG_IMG_OPTIONS', '')));

        $pgUrl = translation_cache_neon_url();
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
                error_log('Translation cache Neon backend: invalid NEON_TRANSLATION_CACHE_URL');
            }
        }

        if ($host === '' || $dbName === '' || $user === '') {
            error_log('Translation cache Neon backend config incomplete (need host, db, user)');
            return null;
        }

        // Neon pooler fallback for environments without SNI support.
        if ($libpqOptions === '' && stripos($host, '-pooler.') !== false) {
            $labels = explode('.', $host);
            $endpointLabel = trim((string)($labels[0] ?? ''));
            if ($endpointLabel !== '' && substr($endpointLabel, -7) === '-pooler') {
                $endpointLabel = substr($endpointLabel, 0, -7);
            }
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
                    error_log('Translation cache Neon retry without channel_binding failed: ' . $retryError->getMessage());
                    return null;
                }
            }

            error_log('Translation cache Neon connection failed: ' . $e->getMessage());
            return null;
        }
    }
}

if (!function_exists('translation_cache_neon_table_ready')) {
    function translation_cache_neon_table_ready() {
        static $ready = null;
        if ($ready !== null) {
            return $ready;
        }

        $pg = translation_cache_neon_pdo();
        if (!$pg) {
            $ready = false;
            return $ready;
        }

        $table = translation_cache_neon_table();
        try {
            $pg->exec("
                CREATE TABLE IF NOT EXISTS {$table} (
                    id BIGSERIAL PRIMARY KEY,
                    cache_key VARCHAR(64) NOT NULL UNIQUE,
                    source_text TEXT NOT NULL,
                    source_lang VARCHAR(16) NOT NULL,
                    target_lang VARCHAR(16) NOT NULL,
                    translated_text TEXT NOT NULL,
                    translation_method VARCHAR(50) NULL,
                    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
                    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
                )
            ");
            $pg->exec("CREATE INDEX IF NOT EXISTS idx_{$table}_target_lang ON {$table}(target_lang)");
            $pg->exec("CREATE INDEX IF NOT EXISTS idx_{$table}_created_at ON {$table}(created_at)");
            $ready = true;
            return $ready;
        } catch (Throwable $e) {
            error_log('Translation cache Neon table ensure failed: ' . $e->getMessage());
            $ready = false;
            return $ready;
        }
    }
}

if (!function_exists('translation_cache_mysql_read')) {
    function translation_cache_mysql_read($mysqlPdo, $cacheKey, $maxAgeDays) {
        if (!($mysqlPdo instanceof PDO)) {
            return null;
        }

        $table = translation_cache_mysql_table();
        try {
            $stmt = $mysqlPdo->prepare("
                SELECT translated_text
                FROM {$table}
                WHERE cache_key = ?
                  AND TIMESTAMPDIFF(DAY, created_at, NOW()) < ?
                LIMIT 1
            ");
            $stmt->execute([$cacheKey, max(1, (int)$maxAgeDays)]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row && isset($row['translated_text']) ? (string)$row['translated_text'] : null;
        } catch (Throwable $e) {
            error_log('Translation cache MySQL read failed: ' . $e->getMessage());
            return null;
        }
    }
}

if (!function_exists('translation_cache_neon_read')) {
    function translation_cache_neon_read($cacheKey, $maxAgeDays) {
        if (!translation_cache_neon_table_ready()) {
            return null;
        }

        $pg = translation_cache_neon_pdo();
        if (!$pg) {
            return null;
        }

        $table = translation_cache_neon_table();
        try {
            $stmt = $pg->prepare("
                SELECT translated_text
                FROM {$table}
                WHERE cache_key = ?
                  AND created_at >= (NOW() - (CAST(? AS INTEGER) * INTERVAL '1 day'))
                ORDER BY id DESC
                LIMIT 1
            ");
            $stmt->execute([$cacheKey, max(1, (int)$maxAgeDays)]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row && isset($row['translated_text']) ? (string)$row['translated_text'] : null;
        } catch (Throwable $e) {
            error_log('Translation cache Neon read failed: ' . $e->getMessage());
            return null;
        }
    }
}

if (!function_exists('translation_cache_read')) {
    function translation_cache_read($cacheKey, $maxAgeDays = null, $mysqlPdo = null) {
        $days = $maxAgeDays === null ? translation_cache_days() : max(1, (int)$maxAgeDays);

        foreach (translation_cache_backends() as $backend) {
            if ($backend === 'neon') {
                $value = translation_cache_neon_read($cacheKey, $days);
                if ($value !== null) {
                    return $value;
                }
                continue;
            }

            if ($backend === 'mysql') {
                $value = translation_cache_mysql_read($mysqlPdo, $cacheKey, $days);
                if ($value !== null) {
                    return $value;
                }
            }
        }

        return null;
    }
}

if (!function_exists('translation_cache_mysql_write')) {
    function translation_cache_mysql_write($mysqlPdo, $cacheKey, $sourceText, $sourceLang, $targetLang, $translatedText, $translationMethod) {
        if (!($mysqlPdo instanceof PDO)) {
            return false;
        }

        $table = translation_cache_mysql_table();
        try {
            $stmt = $mysqlPdo->prepare("
                INSERT INTO {$table}
                    (cache_key, source_text, source_lang, target_lang, translated_text, translation_method)
                VALUES
                    (?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    translated_text = VALUES(translated_text),
                    translation_method = VALUES(translation_method),
                    updated_at = NOW()
            ");
            $stmt->execute([$cacheKey, $sourceText, $sourceLang, $targetLang, $translatedText, $translationMethod]);
            return true;
        } catch (Throwable $e) {
            error_log('Translation cache MySQL write failed: ' . $e->getMessage());
            return false;
        }
    }
}

if (!function_exists('translation_cache_neon_write')) {
    function translation_cache_neon_write($cacheKey, $sourceText, $sourceLang, $targetLang, $translatedText, $translationMethod) {
        if (!translation_cache_neon_table_ready()) {
            return false;
        }

        $pg = translation_cache_neon_pdo();
        if (!$pg) {
            return false;
        }

        $table = translation_cache_neon_table();
        try {
            $stmt = $pg->prepare("
                INSERT INTO {$table}
                    (cache_key, source_text, source_lang, target_lang, translated_text, translation_method, created_at, updated_at)
                VALUES
                    (?, ?, ?, ?, ?, ?, NOW(), NOW())
                ON CONFLICT (cache_key) DO UPDATE SET
                    source_text = EXCLUDED.source_text,
                    source_lang = EXCLUDED.source_lang,
                    target_lang = EXCLUDED.target_lang,
                    translated_text = EXCLUDED.translated_text,
                    translation_method = EXCLUDED.translation_method,
                    updated_at = NOW()
            ");
            $stmt->execute([$cacheKey, $sourceText, $sourceLang, $targetLang, $translatedText, $translationMethod]);
            return true;
        } catch (Throwable $e) {
            error_log('Translation cache Neon write failed: ' . $e->getMessage());
            return false;
        }
    }
}

if (!function_exists('translation_cache_write')) {
    function translation_cache_write($cacheKey, $sourceText, $sourceLang, $targetLang, $translatedText, $translationMethod = 'argos', $mysqlPdo = null) {
        if (trim((string)$translatedText) === '' || trim((string)$translatedText) === trim((string)$sourceText)) {
            return ['mysql' => false, 'neon' => false];
        }

        $result = ['mysql' => false, 'neon' => false];
        foreach (translation_cache_backends() as $backend) {
            if ($backend === 'neon') {
                $result['neon'] = translation_cache_neon_write(
                    $cacheKey,
                    $sourceText,
                    $sourceLang,
                    $targetLang,
                    $translatedText,
                    $translationMethod
                );
                continue;
            }

            if ($backend === 'mysql') {
                $result['mysql'] = translation_cache_mysql_write(
                    $mysqlPdo,
                    $cacheKey,
                    $sourceText,
                    $sourceLang,
                    $targetLang,
                    $translatedText,
                    $translationMethod
                );
            }
        }

        return $result;
    }
}

if (!function_exists('translation_cache_clear')) {
    function translation_cache_clear($targetLang = null, $mysqlPdo = null) {
        $deleted = ['mysql' => null, 'neon' => null];
        $errors = ['mysql' => null, 'neon' => null];
        $normalizedLang = $targetLang !== null ? trim((string)$targetLang) : null;
        if ($normalizedLang === '') {
            $normalizedLang = null;
        }

        foreach (translation_cache_backends() as $backend) {
            if ($backend === 'mysql') {
                if (!($mysqlPdo instanceof PDO)) {
                    $deleted['mysql'] = 0;
                    continue;
                }
                $table = translation_cache_mysql_table();
                try {
                    if ($normalizedLang === null) {
                        $affected = $mysqlPdo->exec("DELETE FROM {$table}");
                        $deleted['mysql'] = (int)($affected === false ? 0 : $affected);
                    } else {
                        $stmt = $mysqlPdo->prepare("DELETE FROM {$table} WHERE target_lang = ?");
                        $stmt->execute([$normalizedLang]);
                        $deleted['mysql'] = (int)$stmt->rowCount();
                    }
                } catch (Throwable $e) {
                    $deleted['mysql'] = 0;
                    $errors['mysql'] = $e->getMessage();
                    error_log('Translation cache MySQL clear failed: ' . $e->getMessage());
                }
                continue;
            }

            if ($backend === 'neon') {
                if (!translation_cache_neon_table_ready()) {
                    $deleted['neon'] = 0;
                    continue;
                }
                $pg = translation_cache_neon_pdo();
                if (!$pg) {
                    $deleted['neon'] = 0;
                    continue;
                }
                $table = translation_cache_neon_table();
                try {
                    if ($normalizedLang === null) {
                        $stmt = $pg->prepare("DELETE FROM {$table}");
                        $stmt->execute();
                        $deleted['neon'] = (int)$stmt->rowCount();
                    } else {
                        $stmt = $pg->prepare("DELETE FROM {$table} WHERE target_lang = ?");
                        $stmt->execute([$normalizedLang]);
                        $deleted['neon'] = (int)$stmt->rowCount();
                    }
                } catch (Throwable $e) {
                    $deleted['neon'] = 0;
                    $errors['neon'] = $e->getMessage();
                    error_log('Translation cache Neon clear failed: ' . $e->getMessage());
                }
            }
        }

        return [
            'deleted' => $deleted,
            'errors' => $errors,
            'driver' => translation_cache_driver(),
            'backends' => translation_cache_backends(),
        ];
    }
}

if (!function_exists('translation_cache_status')) {
    function translation_cache_status($mysqlPdo = null) {
        $entries = [];
        $errors = [];

        foreach (translation_cache_backends() as $backend) {
            if ($backend === 'mysql') {
                if (!($mysqlPdo instanceof PDO)) {
                    continue;
                }
                $table = translation_cache_mysql_table();
                try {
                    $stmt = $mysqlPdo->query("
                        SELECT
                            target_lang,
                            COUNT(*) AS count,
                            translation_method,
                            MAX(created_at) AS last_cached
                        FROM {$table}
                        GROUP BY target_lang, translation_method
                        ORDER BY target_lang
                    ");
                    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    foreach ($rows as $row) {
                        $row['backend'] = 'mysql';
                        $entries[] = $row;
                    }
                } catch (Throwable $e) {
                    $errors['mysql'] = $e->getMessage();
                    error_log('Translation cache MySQL status failed: ' . $e->getMessage());
                }
                continue;
            }

            if ($backend === 'neon') {
                if (!translation_cache_neon_table_ready()) {
                    continue;
                }
                $pg = translation_cache_neon_pdo();
                if (!$pg) {
                    continue;
                }
                $table = translation_cache_neon_table();
                try {
                    $stmt = $pg->query("
                        SELECT
                            target_lang,
                            COUNT(*) AS count,
                            COALESCE(translation_method, 'unknown') AS translation_method,
                            MAX(created_at) AS last_cached
                        FROM {$table}
                        GROUP BY target_lang, translation_method
                        ORDER BY target_lang
                    ");
                    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    foreach ($rows as $row) {
                        $row['backend'] = 'neon';
                        $entries[] = $row;
                    }
                } catch (Throwable $e) {
                    $errors['neon'] = $e->getMessage();
                    error_log('Translation cache Neon status failed: ' . $e->getMessage());
                }
            }
        }

        return [
            'entries' => $entries,
            'errors' => $errors,
            'driver' => translation_cache_driver(),
            'backends' => translation_cache_backends(),
        ];
    }
}

