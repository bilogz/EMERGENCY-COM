<?php
/**
 * Database Connection Test
 * Use this to diagnose database connection issues
 * Access: http://localhost/EMERGENCY-COM/ADMIN/api/test-db-connection.php
 */

header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

$results = [];

// Test 1: Check if we can include db_connect.php
try {
    require_once 'db_connect.php';
    $results['db_connect_included'] = true;
} catch (Exception $e) {
    $results['db_connect_included'] = false;
    $results['db_connect_error'] = $e->getMessage();
}

// Test 2: Check if $pdo exists
$results['pdo_exists'] = isset($pdo) && $pdo !== null;

// Test 3: Try to query database
if ($results['pdo_exists']) {
    try {
        // Test connection with a simple query
        $stmt = $pdo->query("SELECT 1 as test");
        $test = $stmt->fetch();
        $results['connection_test'] = $test ? 'SUCCESS' : 'FAILED';
        
        // Check if users table exists
        $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
        $results['users_table_exists'] = $stmt->rowCount() > 0;
        
        // Check if activity tables exist
        $stmt = $pdo->query("SHOW TABLES LIKE 'admin_activity_logs'");
        $results['activity_logs_exists'] = $stmt->rowCount() > 0;
        
        $stmt = $pdo->query("SHOW TABLES LIKE 'admin_login_logs'");
        $results['login_logs_exists'] = $stmt->rowCount() > 0;
        
        // Try to create tables if they don't exist
        if (!$results['activity_logs_exists'] || !$results['login_logs_exists']) {
            try {
                $pdo->exec("
                    CREATE TABLE IF NOT EXISTS admin_activity_logs (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        admin_id INT NOT NULL,
                        action VARCHAR(100) NOT NULL,
                        description TEXT DEFAULT NULL,
                        ip_address VARCHAR(45) DEFAULT NULL,
                        user_agent TEXT DEFAULT NULL,
                        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                        INDEX idx_admin_id (admin_id),
                        INDEX idx_action (action),
                        INDEX idx_created_at (created_at)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
                ");
                
                $pdo->exec("
                    CREATE TABLE IF NOT EXISTS admin_login_logs (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        admin_id INT NOT NULL,
                        email VARCHAR(255) NOT NULL,
                        login_status VARCHAR(20) NOT NULL,
                        ip_address VARCHAR(45) DEFAULT NULL,
                        user_agent TEXT DEFAULT NULL,
                        login_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                        logout_at DATETIME DEFAULT NULL,
                        session_duration INT DEFAULT NULL,
                        INDEX idx_admin_id (admin_id),
                        INDEX idx_email (email),
                        INDEX idx_login_status (login_status),
                        INDEX idx_login_at (login_at)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
                ");
                
                $results['tables_created'] = true;
                $results['activity_logs_exists'] = true;
                $results['login_logs_exists'] = true;
            } catch (PDOException $e) {
                $results['table_creation_error'] = $e->getMessage();
            }
        }
        
    } catch (PDOException $e) {
        $results['connection_test'] = 'FAILED';
        $results['connection_error'] = $e->getMessage();
    }
} else {
    $results['connection_test'] = 'SKIPPED - No PDO connection';
    if (isset($dbError)) {
        $results['db_error'] = $dbError;
    }
}

// Test 4: Database configuration
$results['db_config'] = [
    'host' => $host ?? 'unknown',
    'port' => $port ?? 'unknown',
    'database' => $db ?? 'unknown',
    'user' => $user ?? 'unknown'
];

// Test 5: Check if MySQL is running on common ports
$commonPorts = [3306, 3000];
foreach ($commonPorts as $testPort) {
    $connection = @fsockopen('127.0.0.1', $testPort, $errno, $errstr, 1);
    if ($connection) {
        $results['port_' . $testPort . '_open'] = true;
        fclose($connection);
    } else {
        $results['port_' . $testPort . '_open'] = false;
    }
}

// Overall status
$results['overall_status'] = $results['pdo_exists'] && $results['connection_test'] === 'SUCCESS' ? 'READY' : 'ERROR';

// Recommendations
$recommendations = [];
if (!$results['pdo_exists']) {
    $recommendations[] = "Database connection failed. Check credentials in db_connect.php";
    $recommendations[] = "Verify MySQL is running (check XAMPP control panel)";
    $recommendations[] = "Check if the database 'emergency_comm_db' exists";
}

if (isset($results['port_3306_open']) && !$results['port_3306_open'] && isset($results['db_config']['port']) && $results['db_config']['port'] == 3000) {
    $recommendations[] = "MySQL typically runs on port 3306, not 3000. Consider changing the port in db_connect.php";
}

if (!$results['activity_logs_exists'] || !$results['login_logs_exists']) {
    $recommendations[] = "Activity log tables are missing or need to be created";
}

$results['recommendations'] = $recommendations;

// Output results
echo json_encode($results, JSON_PRETTY_PRINT);
?>








