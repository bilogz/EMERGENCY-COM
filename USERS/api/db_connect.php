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

// Include port in DSN (useful if XAMPP/MySQL uses a non-standard port)
$dsn = "mysql:host=$host;port=$port;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    error_log('DB Connection failed: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed.']);
    exit();
}

?>
