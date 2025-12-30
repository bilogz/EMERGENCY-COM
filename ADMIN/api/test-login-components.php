<?php
/**
 * Test Login Components
 * This script tests each component needed for login to work
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: text/html; charset=utf-8');

echo "<h1>Login Components Test</h1>";
echo "<style>body { font-family: monospace; padding: 20px; } .success { color: green; } .error { color: red; } .info { color: blue; }</style>";

$errors = [];
$warnings = [];

// Test 1: config.env.php
echo "<h2>1. Testing config.env.php</h2>";
try {
    require_once __DIR__ . '/config.env.php';
    echo "<p class='success'>✓ config.env.php loaded</p>";
    
    if (function_exists('getSecureConfig')) {
        echo "<p class='success'>✓ getSecureConfig() function exists</p>";
    } else {
        $errors[] = "getSecureConfig() function not found";
        echo "<p class='error'>✗ getSecureConfig() function NOT FOUND</p>";
    }
} catch (Exception $e) {
    $errors[] = "config.env.php error: " . $e->getMessage();
    echo "<p class='error'>✗ config.env.php failed: " . htmlspecialchars($e->getMessage()) . "</p>";
}

// Test 2: Database connection
echo "<h2>2. Testing Database Connection</h2>";
try {
    require_once __DIR__ . '/db_connect.php';
    
    if (isset($pdo) && $pdo !== null) {
        echo "<p class='success'>✓ Database connection successful</p>";
        
        // Test query
        $stmt = $pdo->query("SELECT 1");
        echo "<p class='success'>✓ Database query test successful</p>";
    } else {
        $errors[] = "Database connection failed - pdo is null";
        echo "<p class='error'>✗ Database connection failed - pdo is null</p>";
    }
} catch (Exception $e) {
    $errors[] = "Database error: " . $e->getMessage();
    echo "<p class='error'>✗ Database connection failed: " . htmlspecialchars($e->getMessage()) . "</p>";
}

// Test 3: activity_logger.php
echo "<h2>3. Testing activity_logger.php</h2>";
try {
    require_once __DIR__ . '/activity_logger.php';
    echo "<p class='success'>✓ activity_logger.php loaded</p>";
    
    if (function_exists('logAdminLogin')) {
        echo "<p class='success'>✓ logAdminLogin() function exists</p>";
    } else {
        $warnings[] = "logAdminLogin() function not found (non-critical)";
        echo "<p class='info'>⚠ logAdminLogin() function not found (will use stub)</p>";
    }
} catch (Exception $e) {
    $warnings[] = "activity_logger.php error: " . $e->getMessage();
    echo "<p class='info'>⚠ activity_logger.php failed (non-critical): " . htmlspecialchars($e->getMessage()) . "</p>";
}

// Test 4: getSecureConfig values
echo "<h2>4. Testing getSecureConfig() Values</h2>";
if (function_exists('getSecureConfig')) {
    try {
        $dbHost = getSecureConfig('DB_HOST', 'localhost');
        echo "<p class='info'>DB_HOST: " . htmlspecialchars($dbHost) . "</p>";
        
        $dbName = getSecureConfig('DB_NAME', 'emer_comm_test');
        echo "<p class='info'>DB_NAME: " . htmlspecialchars($dbName) . "</p>";
        
        $adminApiKey = getSecureConfig('ADMIN_API_KEY', '');
        echo "<p class='info'>ADMIN_API_KEY: " . (empty($adminApiKey) ? "NOT SET (OK for dev)" : "SET") . "</p>";
        
        $requireOtp = getSecureConfig('ADMIN_REQUIRE_OTP', true);
        echo "<p class='info'>ADMIN_REQUIRE_OTP: " . ($requireOtp ? "true" : "false") . "</p>";
        
        $recaptchaSecret = getSecureConfig('RECAPTCHA_SECRET_KEY', '');
        echo "<p class='info'>RECAPTCHA_SECRET_KEY: " . (empty($recaptchaSecret) ? "NOT SET" : "SET") . "</p>";
    } catch (Exception $e) {
        $errors[] = "getSecureConfig() error: " . $e->getMessage();
        echo "<p class='error'>✗ getSecureConfig() failed: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
} else {
    echo "<p class='error'>✗ Cannot test getSecureConfig() - function not available</p>";
}

// Test 5: Admin user lookup
echo "<h2>5. Testing Admin User Lookup</h2>";
if (isset($pdo) && $pdo !== null) {
    try {
        // Check if admin_user table exists
        $stmt = $pdo->query("SHOW TABLES LIKE 'admin_user'");
        if ($stmt->rowCount() > 0) {
            echo "<p class='success'>✓ admin_user table exists</p>";
            
            $stmt = $pdo->prepare("SELECT id, name, email, role, status FROM admin_user WHERE email = ?");
            $stmt->execute(['joecel519@gmail.com']);
            $admin = $stmt->fetch();
            
            if ($admin) {
                echo "<p class='success'>✓ Admin account found</p>";
                echo "<pre>";
                print_r($admin);
                echo "</pre>";
            } else {
                $warnings[] = "Admin account not found with email joecel519@gmail.com";
                echo "<p class='error'>✗ Admin account NOT FOUND</p>";
            }
        } else {
            $warnings[] = "admin_user table does not exist";
            echo "<p class='info'>⚠ admin_user table does not exist (will use users table)</p>";
        }
    } catch (Exception $e) {
        $errors[] = "Admin user lookup error: " . $e->getMessage();
        echo "<p class='error'>✗ Admin user lookup failed: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
} else {
    echo "<p class='error'>✗ Cannot test admin user lookup - no database connection</p>";
}

// Summary
echo "<h2>Summary</h2>";
if (empty($errors)) {
    echo "<p class='success'><strong>✓ All critical components are working!</strong></p>";
    if (!empty($warnings)) {
        echo "<p class='info'>Warnings (non-critical):</p><ul>";
        foreach ($warnings as $warning) {
            echo "<li>" . htmlspecialchars($warning) . "</li>";
        }
        echo "</ul>";
    }
} else {
    echo "<p class='error'><strong>✗ Errors found:</strong></p><ul>";
    foreach ($errors as $error) {
        echo "<li>" . htmlspecialchars($error) . "</li>";
    }
    echo "</ul>";
}

echo "<hr>";
echo "<p><a href='debug-login-error.php'>Run Full Diagnostics</a> | <a href='check-and-update-password.php'>Update Password</a> | <a href='../login.php'>Back to Login</a></p>";
?>

