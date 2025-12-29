<?php
/**
 * SECURE DATABASE CONNECTION
 * 
 * Loads credentials from secure config (environment variables or config.local.php)
 * SAFE TO COMMIT - Contains no actual credentials
 */

// Report all errors to the log, but do NOT display them to the client
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Load secure configuration
require_once __DIR__ . '/config.env.php';

// Get database configuration
$dbConfig = getDatabaseConfig();

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
    PDO::ATTR_TIMEOUT            => 10, // 10 second timeout
];

$pdo = null;
$dbError = null;

// Build connection attempts from config
$connectionAttempts = [
    $dbConfig['primary'],
    $dbConfig['fallback'],
];

foreach ($connectionAttempts as $attempt) {
    try {
        // Connect without database first to check/create it
        $dsn = "mysql:host={$attempt['host']};port={$attempt['port']};charset={$dbConfig['charset']}";
        $tempPdo = new PDO($dsn, $attempt['user'], $attempt['pass'], $options);
        
        // Check if database exists
        $dbName = $attempt['name'];
        $stmt = $tempPdo->query("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '$dbName'");
        $dbExists = $stmt->fetch();
        
        if (!$dbExists) {
            // Create the database if it doesn't exist
            $tempPdo->exec("CREATE DATABASE IF NOT EXISTS `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            error_log("USERS: Created database '$dbName' on {$attempt['host']}");
        }
        
        // Now connect to the actual database
        $dsn = "mysql:host={$attempt['host']};port={$attempt['port']};dbname=$dbName;charset={$dbConfig['charset']}";
        $pdo = new PDO($dsn, $attempt['user'], $attempt['pass'], $options);
        error_log("USERS: ✓ Connected to database on {$attempt['host']}");
        
        // Connection successful, break out of loop
        break;
    } catch (PDOException $e) {
        $dbError = $e->getMessage();
        error_log("USERS: Failed to connect to {$attempt['host']}: " . $dbError);
        $pdo = null;
        // Continue to next attempt
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
