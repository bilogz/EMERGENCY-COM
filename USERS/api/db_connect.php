<?php
// db_connect.php
// Creates a PDO instance in $pdo. Same configuration as ADMIN module.

// Report all errors to the log, but do NOT display them to the client
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Database connection configuration
// Connect to remote database (same as ADMIN module)
$host = 'alertaraqc.com';
$port = 3306; // Default MySQL port
$db   = 'emer_comm_test';
$user = 'root';
$pass = 'YsqnXk6q#145'; // Remote database password
$charset = 'utf8mb4';

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
    PDO::ATTR_TIMEOUT            => 10, // Increased timeout for remote connection
];

$pdo = null;
$dbError = null;

// Try remote database FIRST with remote credentials
// IMPORTANT: Remote MySQL must allow connections from your IP
// If connection fails, check REMOTE_DB_SETUP.md for configuration
try {
    // First attempt: Remote database
    $dsn = "mysql:host=alertaraqc.com;port=3306;dbname=$db;charset=$charset";
    $pdo = new PDO($dsn, 'root', 'YsqnXk6q#145', $options);
    error_log("USERS: ✓ Connected to REMOTE database (alertaraqc.com)");
} catch (PDOException $e) {
    $dbError = $e->getMessage();
    error_log("USERS: ❌ FAILED to connect to remote database (alertaraqc.com): " . $dbError);
    error_log("USERS: Remote MySQL is refusing connections. Check REMOTE_DB_SETUP.md for solutions.");
    $pdo = null;
    
    // Fallback to localhost ONLY for development/testing
    // WARNING: Accounts will be saved locally, not on remote server!
    try {
        error_log("USERS: ⚠ Attempting fallback to localhost (DEVELOPMENT ONLY)");
        $dsn = "mysql:host=localhost;port=3306;dbname=$db;charset=$charset";
        $pdo = new PDO($dsn, 'root', '', $options);
        error_log("USERS: ⚠⚠⚠ WARNING - Using LOCAL database! Accounts saved locally, NOT on remote server!");
        error_log("USERS: ⚠⚠⚠ To fix: Configure remote MySQL to allow connections (see REMOTE_DB_SETUP.md)");
    } catch (PDOException $e2) {
        $dbError = $e2->getMessage();
        error_log("USERS: Localhost fallback also failed: " . $dbError);
        $pdo = null;
    }
}

// If all attempts failed, log the error
if ($pdo === null) {
    error_log('DB Connection failed after all attempts: ' . $dbError);
    
    // Only exit for API calls, allow pages to handle error gracefully
    if (php_sapi_name() !== 'cli' && strpos($_SERVER['REQUEST_URI'] ?? '', '/api/') !== false) {
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Database connection failed.']);
        exit();
    }
    // For non-API pages, $pdo will be null and pages can check for it
}

?>
