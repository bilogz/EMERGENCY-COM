<?php
// Development-only DB diagnostic script
// WARNING: This prints database connection errors and should be removed or protected in production.
header('Content-Type: application/json');

$host = '127.0.0.1';
$port = 3000; // Change this if your MySQL is configured on another port
$db   = 'emergency_comm_db';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;port=$port;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    echo json_encode(["success" => true, "message" => "Connected to database successfully."]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Database connection failed.", "error" => $e->getMessage()]);
}
