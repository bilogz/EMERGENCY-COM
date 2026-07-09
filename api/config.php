<?php
/**
 * UNIFIED DATABASE CONFIGURATION LOADER
 * 
 * Reuses existing system DB credentials and connection logic
 * from ADMIN/api/db_connect.php.
 */

// Define access flag for configuration
if (!defined('SECURE_ACCESS')) {
    define('SECURE_ACCESS', true);
}

// Establish paths relative to this file
$adminApiDir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'ADMIN' . DIRECTORY_SEPARATOR . 'api';
$usersApiDir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'USERS' . DIRECTORY_SEPARATOR . 'api';

// Check if ADMIN db_connect exists
if (file_exists($adminApiDir . DIRECTORY_SEPARATOR . 'db_connect.php')) {
    // Temporarily disable direct exit of db_connect for inclusion checks
    $isIncluded = true;
    require_once $adminApiDir . DIRECTORY_SEPARATOR . 'db_connect.php';
} elseif (file_exists($usersApiDir . DIRECTORY_SEPARATOR . 'db_connect.php')) {
    require_once $usersApiDir . DIRECTORY_SEPARATOR . 'db_connect.php';
} else {
    // Direct PDO fallback connection if other config loaders fail
    try {
        $host = getenv('DB_HOST') ?: 'localhost';
        $db = getenv('DB_NAME') ?: 'emer_comm_test';
        $user = getenv('DB_USER') ?: 'root';
        $pass = getenv('DB_PASS') ?: '';
        $port = getenv('DB_PORT') ?: '3306';
        $charset = 'utf8mb4';
        
        $dsn = "mysql:host=$host;port=$port;dbname=$db;charset=$charset";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        $pdo = new PDO($dsn, $user, $pass, $options);
    } catch (PDOException $e) {
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => 'Database connection failed: ' . $e->getMessage()]);
        exit();
    }
}

// Verify that PDO is active
if (!isset($pdo) || $pdo === null) {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'message' => 'Database connection could not be established.']);
    exit();
}
