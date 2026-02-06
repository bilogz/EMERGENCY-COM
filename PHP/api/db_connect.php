<?php
// db_connect.php
// Creates a PDO instance in $pdo.
// Tries to connect to Online (Hostinger) DB first, falls back to Local (XAMPP) DB.

// Report all errors to the log, but do NOT display them to the client
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Try to include apiResponse helper if present (case-insensitive filesystems differ)
if (file_exists(__DIR__ . '/apiResponse.php')) {
    require_once __DIR__ . '/apiResponse.php';
}

// --- Define Credentials ---

// 1. Online (Hostinger) Credentials
// vvv  REPLACE THESE WITH YOUR ACTUAL HOSTINGER CREDENTIALS vvv
$online_creds = [
    'host' => 'localhost',
    'db'   => 'emer_comm_test',
    'user' => 'root',
    'pass' => 'YsqnXk6q#145'
];
// ^^^ REPLACE THESE WITH YOUR ACTUAL HOSTINGER CREDENTIALS ^^^

// 2. Local (XAMPP) Credentials
$local_creds = [
    'host' => '127.0.0.1',
    'db'   => 'emer_comm_test',
    'user' => 'root',
    'pass' => ''
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

    // Use apiResponse if available
    if (class_exists('apiResponse')) {
        if (defined('DEBUG_MODE') && DEBUG_MODE === true) {
            apiResponse::error($msg, 500, $debug);
        } else {
            apiResponse::error($msg, 500);
        }
    } else {
        // Fallback JSON response
        http_response_code(500);
        $out = ['success' => false, 'message' => $msg];
        if (defined('DEBUG_MODE') && DEBUG_MODE === true && $debug) {
            $out['debug'] = $debug;
        }
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($out);
        exit();
    }
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
