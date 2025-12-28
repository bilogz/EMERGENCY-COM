<?php
// db_connect.php
// Creates a PDO instance in $pdo. Adjust credentials as needed for your XAMPP setup.

// Report all errors to the log, but do NOT display them to the client
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Database connection configuration
// Try localhost first (if PHP and MySQL are on same server), then remote
$host = 'localhost'; // Changed from 'alertaraqc.com' - use localhost if PHP and MySQL are on same server
$port = 3306; // Default MySQL port
$db   = 'emer_comm_test';
$user = 'root';
// Try empty password first (XAMPP default), then custom password
$passAttempts = ['', 'YsqnXk6q#145'];
$charset = 'utf8mb4';

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
    PDO::ATTR_TIMEOUT            => 5, // 5 second timeout
];

$pdo = null;
$dbError = null;

// Try multiple connection methods
$connectionAttempts = [
    ['host' => 'localhost', 'port' => 3306],
    ['host' => '127.0.0.1', 'port' => 3306],
    ['host' => 'alertaraqc.com', 'port' => 3306],
];

foreach ($connectionAttempts as $attempt) {
    foreach ($passAttempts as $pass) {
        try {
            $dsn = "mysql:host={$attempt['host']};port={$attempt['port']};dbname=$db;charset=$charset";
            $pdo = new PDO($dsn, $user, $pass, $options);
            // Connection successful, break out of loops
            break 2;
        } catch (PDOException $e) {
            $dbError = $e->getMessage();
            // Continue to next attempt
            $pdo = null;
        }
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