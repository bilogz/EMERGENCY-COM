<?php
// db_connect.php
// Creates a PDO instance in $pdo. Adjust credentials as needed for your XAMPP setup.

// Report all errors to the log, but do NOT display them to the client
error_reporting(E_ALL);
ini_set('display_errors', 0);

$host = 'alertaraqc.com';
$port = 3306; // Default MySQL port
$db   = 'emer_comm_test';
$user = 'root';
$pass = 'YsqnXk6q#145';
$charset = 'utf8mb4';

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

$pdo = null;
$dbError = null;

try {
    // Connect using port 3000
    $dsn = "mysql:host=$host;port=$port;dbname=$db;charset=$charset";
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    $dbError = $e->getMessage();
    error_log('DB Connection failed: ' . $dbError);
    
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