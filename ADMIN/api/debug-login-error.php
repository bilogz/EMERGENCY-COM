<?php
/**
 * Debug Login Error
 * This script helps identify what's causing the 500 error in login-web.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Login Error Diagnostics</h2>";
echo "<pre>";

// Test 1: Check if config.env.php exists and loads
echo "=== Test 1: Config Files ===\n";
$configEnvPath = __DIR__ . '/config.env.php';
$configLocalPath = __DIR__ . '/config.local.php';

echo "config.env.php exists: " . (file_exists($configEnvPath) ? "YES" : "NO") . "\n";
echo "config.local.php exists: " . (file_exists($configLocalPath) ? "YES" : "NO") . "\n";

if (file_exists($configEnvPath)) {
    try {
        require_once $configEnvPath;
        echo "config.env.php loaded: SUCCESS\n";
        
        // Check if getSecureConfig function exists
        if (function_exists('getSecureConfig')) {
            echo "getSecureConfig() function: EXISTS\n";
        } else {
            echo "getSecureConfig() function: MISSING!\n";
        }
    } catch (Exception $e) {
        echo "config.env.php error: " . $e->getMessage() . "\n";
    }
} else {
    echo "ERROR: config.env.php not found!\n";
}

// Test 2: Check config.local.php syntax
if (file_exists($configLocalPath)) {
    echo "\n=== Test 2: config.local.php Syntax ===\n";
    $output = [];
    $return_var = 0;
    exec("php -l " . escapeshellarg($configLocalPath) . " 2>&1", $output, $return_var);
    if ($return_var === 0) {
        echo "config.local.php syntax: VALID\n";
    } else {
        echo "config.local.php syntax: ERROR!\n";
        echo implode("\n", $output) . "\n";
    }
}

// Test 3: Database connection
echo "\n=== Test 3: Database Connection ===\n";
try {
    require_once __DIR__ . '/db_connect.php';
    if (isset($pdo) && $pdo !== null) {
        echo "Database connection: SUCCESS\n";
        
        // Test query
        $stmt = $pdo->query("SELECT 1");
        echo "Database query test: SUCCESS\n";
    } else {
        echo "Database connection: FAILED (pdo is null)\n";
    }
} catch (Exception $e) {
    echo "Database connection: ERROR - " . $e->getMessage() . "\n";
}

// Test 4: Check if getSecureConfig works
echo "\n=== Test 4: getSecureConfig Function ===\n";
if (function_exists('getSecureConfig')) {
    try {
        $dbHost = getSecureConfig('DB_HOST', 'default');
        echo "getSecureConfig('DB_HOST'): " . $dbHost . "\n";
        
        $adminApiKey = getSecureConfig('ADMIN_API_KEY', '');
        echo "getSecureConfig('ADMIN_API_KEY'): " . (empty($adminApiKey) ? "EMPTY (OK)" : "SET") . "\n";
        
        $requireOtp = getSecureConfig('ADMIN_REQUIRE_OTP', true);
        echo "getSecureConfig('ADMIN_REQUIRE_OTP'): " . ($requireOtp ? "true" : "false") . "\n";
    } catch (Exception $e) {
        echo "getSecureConfig() error: " . $e->getMessage() . "\n";
    }
} else {
    echo "ERROR: getSecureConfig() function not available!\n";
}

// Test 5: Check activity_logger.php
echo "\n=== Test 5: Activity Logger ===\n";
$activityLoggerPath = __DIR__ . '/activity_logger.php';
if (file_exists($activityLoggerPath)) {
    try {
        require_once $activityLoggerPath;
        echo "activity_logger.php: LOADED\n";
        
        if (function_exists('logAdminLogin')) {
            echo "logAdminLogin() function: EXISTS\n";
        } else {
            echo "logAdminLogin() function: MISSING\n";
        }
    } catch (Exception $e) {
        echo "activity_logger.php error: " . $e->getMessage() . "\n";
    }
} else {
    echo "activity_logger.php: NOT FOUND\n";
}

// Test 6: Check admin_user table
echo "\n=== Test 6: Admin User Table ===\n";
if (isset($pdo) && $pdo !== null) {
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE 'admin_user'");
        if ($stmt->rowCount() > 0) {
            echo "admin_user table: EXISTS\n";
            
            // Check for the email
            $stmt = $pdo->prepare("SELECT id, name, email, role FROM admin_user WHERE email = ?");
            $stmt->execute(['joecel519@gmail.com']);
            $admin = $stmt->fetch();
            
            if ($admin) {
                echo "Account found: YES\n";
                echo "  ID: " . $admin['id'] . "\n";
                echo "  Name: " . $admin['name'] . "\n";
                echo "  Email: " . $admin['email'] . "\n";
                echo "  Role: " . $admin['role'] . "\n";
            } else {
                echo "Account found: NO (email: joecel519@gmail.com)\n";
            }
        } else {
            echo "admin_user table: NOT FOUND\n";
        }
    } catch (Exception $e) {
        echo "admin_user table check error: " . $e->getMessage() . "\n";
    }
} else {
    echo "Cannot check table (no database connection)\n";
}

echo "\n=== End of Diagnostics ===\n";
echo "</pre>";

echo "<h3>Next Steps:</h3>";
echo "<ul>";
echo "<li>If config.local.php has syntax errors, fix them</li>";
echo "<li>If database connection fails, check MySQL is running and credentials are correct</li>";
echo "<li>If getSecureConfig() is missing, check config.env.php</li>";
echo "</ul>";

echo "<p><a href='check-and-update-password.php'>Update Password</a> | <a href='../login.php'>Back to Login</a></p>";
?>

