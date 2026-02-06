// db_connect.php
// Creates a PDO instance in $pdo.
// Tries to connect to Online (Hostinger) DB first, falls back to Local (XAMPP) DB.

// Start output buffering to prevent random text/warnings from breaking JSON
ob_start();

// Report all errors to the log, but do NOT display them to the client
error_reporting(E_ALL);
ini_set('display_errors', 0);

require_once 'apiResponse.php';

// Define if we are in debug mode (show detailed errors in JSON)
// Set to false in production!
if (!defined('DEBUG_MODE')) {
    define('DEBUG_MODE', true);
}

// --- Define Credentials ---

// 1. Online (Hostinger) Credentials
// vvv  REPLACE THESE WITH YOUR ACTUAL HOSTINGER CREDENTIALS vvv
$online_creds = [
    'host' => 'localhost',
    'db'   => 'LGU',
    'user' => 'root',
    'pass' => 'YsqnXk6q#145'
];
// ^^^ REPLACE THESE WITH YOUR ACTUAL HOSTINGER CREDENTIALS ^^^

// 2. Local (XAMPP) Credentials
$local_creds = [
    'host' => '127.0.0.1',
    'db'   => 'LGU',
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

        // Send a generic, safe error message to the client
        apiResponse::error('A server error occurred during database connection.', 500, $e_local->getMessage());
    }
}

/** @var PDO $pdo */
