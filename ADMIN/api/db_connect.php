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
$pdo = null;
$dbError = null;
$dbConfig = null;

try {
    if (!file_exists(__DIR__ . '/config.env.php')) {
        throw new Exception('config.env.php file not found');
    }
    require_once __DIR__ . '/config.env.php';
    
    // Check if getDatabaseConfig function exists
    if (!function_exists('getDatabaseConfig')) {
        throw new Exception('getDatabaseConfig function not found in config.env.php');
    }
    
    // Get database configuration
    $dbConfig = getDatabaseConfig();
    
    // Validate config structure
    if (!is_array($dbConfig) || !isset($dbConfig['primary']) || !isset($dbConfig['fallback'])) {
        throw new Exception('Invalid database configuration structure');
    }
} catch (Exception $configError) {
    error_log('ADMIN: Config loading error: ' . $configError->getMessage());
    $pdo = null;
    $dbError = 'Configuration error: ' . $configError->getMessage();
    $dbConfig = null;
    // Don't exit here - let the calling script handle it
}

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
    PDO::ATTR_TIMEOUT            => 5, // 5 second timeout
];

// Only attempt connection if config was loaded successfully
if ($dbConfig !== null) {
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
            error_log("ADMIN: Created database '$dbName' on {$attempt['host']}");
        }
        
        // Now connect to the actual database
        $dsn = "mysql:host={$attempt['host']};port={$attempt['port']};dbname=$dbName;charset={$dbConfig['charset']}";
        $pdo = new PDO($dsn, $attempt['user'], $attempt['pass'], $options);
        
        // Connection successful, break out of loop
        break;
    } catch (PDOException $e) {
        $dbError = $e->getMessage();
        error_log("ADMIN: Failed to connect to {$attempt['host']}: " . $dbError);
        $pdo = null;
        // Continue to next attempt
    }
    }
}

// If all attempts failed, log the error
if ($pdo === null) {
    error_log('DB Connection failed after all attempts: ' . $dbError);
    
    // Check if this file is being called directly (not included)
    // Compare the script being executed with this file
    $isIncluded = false;
    if (isset($_SERVER['SCRIPT_FILENAME'])) {
        $scriptFile = $_SERVER['SCRIPT_FILENAME'];
        $thisFile = __FILE__;
        
        // Try to get real paths for more accurate comparison
        $scriptFileReal = @realpath($scriptFile);
        $thisFileReal = @realpath($thisFile);
        
        if ($scriptFileReal && $thisFileReal) {
            // Use real paths if available
            $scriptFile = $scriptFileReal;
            $thisFile = $thisFileReal;
        }
        
        // Normalize paths for comparison (handle Windows/Unix differences)
        // Convert to lowercase and normalize separators
        $scriptFile = strtolower(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $scriptFile));
        $thisFile = strtolower(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $thisFile));
        
        // If the script file is different from this file, it means this file was included
        if ($scriptFile !== $thisFile) {
            $isIncluded = true;
        }
    } else {
        // If SCRIPT_FILENAME is not set, assume it's included (safer default)
        $isIncluded = true;
    }
    
    // Only exit for direct API calls (not when included), allow pages to handle error gracefully
    if (!$isIncluded && php_sapi_name() !== 'cli' && strpos($_SERVER['REQUEST_URI'] ?? '', '/api/') !== false) {
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Database connection failed.']);
        exit();
    }
    // For included files or non-API pages, $pdo will be null and pages can check for it
}
