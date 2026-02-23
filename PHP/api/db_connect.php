    <?php
    // db_connect.php
    // Creates a PDO instance in $pdo.
    // Tries to connect to Online (Hostinger) DB first, falls back to Local (XAMPP) DB.

    // Report all errors to the log, but do NOT display them to the client
    error_reporting(E_ALL);
    ini_set('display_errors', 0);

    // Debug mode should be enabled only when explicitly configured.
    if (!defined('DEBUG_MODE')) {
        $debugEnv = getenv('APP_DEBUG');
        $isDebug = ($debugEnv === '1' || strtolower((string)$debugEnv) === 'true');
        define('DEBUG_MODE', $isDebug);
    }

    // Try to include apiResponse helper if present (case-insensitive filesystems differ)
    if (file_exists(__DIR__ . '/apiResponse.php')) {
        require_once __DIR__ . '/apiResponse.php';
    }

    // Load key=value pairs from PHP/api/.env if present.
    // Existing process-level env vars are preserved and take priority.
    function db_load_dotenv_if_present() {
        static $loaded = false;
        if ($loaded) {
            return;
        }
        $loaded = true;

        $envPath = __DIR__ . '/.env';
        if (!file_exists($envPath)) {
            return;
        }

        $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            return;
        }

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || $line[0] === '#') {
                continue;
            }

            $parts = explode('=', $line, 2);
            if (count($parts) !== 2) {
                continue;
            }

            $key = trim($parts[0]);
            $value = trim($parts[1]);
            if ($key === '') {
                continue;
            }

            $len = strlen($value);
            $isDoubleQuoted = $len >= 2 && $value[0] === '"' && $value[$len - 1] === '"';
            $isSingleQuoted = $len >= 2 && $value[0] === "'" && $value[$len - 1] === "'";
            if ($isDoubleQuoted || $isSingleQuoted) {
                $value = substr($value, 1, -1);
            }

            if (getenv($key) === false) {
                putenv($key . '=' . $value);
                $_ENV[$key] = $value;
            }
        }
    }

    function db_env($key, $default = '') {
        $value = getenv($key);
        if ($value === false) {
            return $default;
        }
        return $value;
    }

    db_load_dotenv_if_present();

    // --- Define Credentials ---

    // 1. Primary DB credentials (production/staging)
    $online_creds = [
        'host' => db_env('DB_HOST', 'localhost'),
        'db'   => db_env('DB_NAME', 'emer_comm_test'),
        'user' => db_env('DB_USER', 'root'),
        'pass' => db_env('DB_PASS', '')
    ];

    // 2. Local fallback DB credentials (for dev)
    $local_creds = [
        'host' => db_env('DB_LOCAL_HOST', '127.0.0.1'),
        'db'   => db_env('DB_LOCAL_NAME', $online_creds['db']),
        'user' => db_env('DB_LOCAL_USER', 'root'),
        'pass' => db_env('DB_LOCAL_PASS', '')
    ];

    $charset = 'utf8mb4';
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    $pdo = null;

    // helper to send error consistently
    function send_db_error($msg, $ex = null) {
        $debug = null;
        if ($ex instanceof Exception) {
            $debug = $ex->getMessage();
        } elseif (is_string($ex)) {
            $debug = $ex;
        }
        // Log always
        error_log('DB CONNECT ERROR: ' . $msg . ($debug ? ' | ' . $debug : ''));

        // Force JSON response while avoiding sensitive error leakage in production.
        if (ob_get_length()) ob_clean();
        http_response_code(500);
        $out = ['success' => false, 'message' => $msg];
        if ($debug && DEBUG_MODE === true) {
            $out['debug'] = $debug;
        }
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($out);
        exit();
    }

    try {
        // Attempt 1: Connect to Online Database
        $dsn = "mysql:host={$online_creds['host']};dbname={$online_creds['db']};charset=$charset";
        $pdo = new PDO($dsn, $online_creds['user'], $online_creds['pass'], $options);
        
    } catch (PDOException $e_online) {
        // Log the online connection failure
        error_log('Online DB Connection failed: ' . $e_online->getMessage() . '. Attempting fallback to Local DB.');

        try {
            // Attempt 2: Fallback to Local Database
            $dsn = "mysql:host={$local_creds['host']};dbname={$local_creds['db']};charset=$charset";
            $pdo = new PDO($dsn, $local_creds['user'], $local_creds['pass'], $options);
            
        } catch (PDOException $e_local) {
            // Both connections failed
            error_log('Local DB Fallback failed: ' . $e_local->getMessage());

            // Send a consistent error response (with debug when enabled)
            send_db_error('A server error occurred during database connection.', $e_local->getMessage());
        }
    }

    /** @var PDO $pdo */

    ?>
