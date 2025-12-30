<?php
/**
 * Server Diagnostic Tool
 * Diagnoses common issues on the SSH server
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: text/html; charset=utf-8');

echo "<h1>Server Diagnostic Tool</h1>";
echo "<style>body { font-family: monospace; padding: 20px; } .success { color: green; } .error { color: red; } .info { color: blue; } .warning { color: orange; } pre { background: #f5f5f5; padding: 10px; } table { border-collapse: collapse; width: 100%; } th, td { border: 1px solid #ddd; padding: 8px; text-align: left; } th { background-color: #f2f2f2; }</style>";

// Test 1: PHP Version
echo "<h2>1. PHP Version</h2>";
echo "<p>PHP Version: " . phpversion() . "</p>";
if (version_compare(phpversion(), '7.4.0', '>=')) {
    echo "<p class='success'>✓ PHP version is compatible</p>";
} else {
    echo "<p class='error'>✗ PHP version is too old (requires 7.4+)</p>";
}

// Test 2: Required Extensions
echo "<h2>2. PHP Extensions</h2>";
$requiredExtensions = ['pdo', 'pdo_mysql', 'json', 'mbstring', 'curl'];
$missing = [];
foreach ($requiredExtensions as $ext) {
    if (extension_loaded($ext)) {
        echo "<p class='success'>✓ $ext extension loaded</p>";
    } else {
        echo "<p class='error'>✗ $ext extension NOT loaded</p>";
        $missing[] = $ext;
    }
}

// Test 3: Config Files
echo "<h2>3. Configuration Files</h2>";
$configFiles = [
    'config.env.php' => __DIR__ . '/config.env.php',
    'config.local.php' => __DIR__ . '/config.local.php',
    'db_connect.php' => __DIR__ . '/db_connect.php',
];

foreach ($configFiles as $name => $path) {
    if (file_exists($path)) {
        echo "<p class='success'>✓ $name exists</p>";
        
        if ($name === 'config.local.php') {
            // Check if it's readable and has content
            $content = file_get_contents($path);
            if (strlen($content) > 100) {
                echo "<p class='success'>✓ $name has content</p>";
            } else {
                echo "<p class='warning'>⚠ $name seems empty or incomplete</p>";
            }
        }
    } else {
        echo "<p class='error'>✗ $name NOT FOUND</p>";
    }
}

// Test 4: Database Connection
echo "<h2>4. Database Connection</h2>";
try {
    require_once __DIR__ . '/config.env.php';
    require_once __DIR__ . '/db_connect.php';
    
    if (isset($pdo) && $pdo !== null) {
        echo "<p class='success'>✓ Database connection successful</p>";
        
        // Test query
        $stmt = $pdo->query("SELECT 1");
        echo "<p class='success'>✓ Database query test successful</p>";
        
        // Check database name
        $dbName = $pdo->query("SELECT DATABASE()")->fetchColumn();
        echo "<p class='info'>Current database: <strong>$dbName</strong></p>";
        
    } else {
        echo "<p class='error'>✗ Database connection failed - pdo is null</p>";
    }
} catch (Exception $e) {
    echo "<p class='error'>✗ Database connection error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

// Test 5: Required Tables
echo "<h2>5. Required Database Tables</h2>";
if (isset($pdo) && $pdo !== null) {
    $requiredTables = [
        'users',
        'admin_user',
        'otp_verifications',
        'admin_activity_logs',
        'admin_login_logs',
        'alert_categories',
        'alerts',
        'notification_logs',
        'rate_limits',
        'translation_cache',
    ];
    
    $missingTables = [];
    $existingTables = [];
    
    foreach ($requiredTables as $table) {
        try {
            $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
            if ($stmt->rowCount() > 0) {
                echo "<p class='success'>✓ $table table exists</p>";
                $existingTables[] = $table;
            } else {
                echo "<p class='error'>✗ $table table MISSING</p>";
                $missingTables[] = $table;
            }
        } catch (Exception $e) {
            echo "<p class='error'>✗ Error checking $table: " . htmlspecialchars($e->getMessage()) . "</p>";
            $missingTables[] = $table;
        }
    }
    
    if (empty($missingTables)) {
        echo "<p class='success'><strong>✓ All required tables exist!</strong></p>";
    } else {
        echo "<p class='error'><strong>✗ Missing tables: " . implode(', ', $missingTables) . "</strong></p>";
        echo "<p class='info'>💡 Solution: Import complete_database_schema.sql</p>";
    }
} else {
    echo "<p class='error'>Cannot check tables - no database connection</p>";
}

// Test 6: Admin Account
echo "<h2>6. Admin Account Check</h2>";
if (isset($pdo) && $pdo !== null) {
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE 'admin_user'");
        if ($stmt->rowCount() > 0) {
            $stmt = $pdo->prepare("SELECT id, name, email, role, status FROM admin_user WHERE email = ?");
            $stmt->execute(['joecel519@gmail.com']);
            $admin = $stmt->fetch();
            
            if ($admin) {
                echo "<p class='success'>✓ Admin account found</p>";
                echo "<table>";
                echo "<tr><th>Field</th><th>Value</th></tr>";
                echo "<tr><td>ID</td><td>" . htmlspecialchars($admin['id']) . "</td></tr>";
                echo "<tr><td>Name</td><td>" . htmlspecialchars($admin['name']) . "</td></tr>";
                echo "<tr><td>Email</td><td>" . htmlspecialchars($admin['email']) . "</td></tr>";
                echo "<tr><td>Role</td><td>" . htmlspecialchars($admin['role']) . "</td></tr>";
                echo "<tr><td>Status</td><td>" . htmlspecialchars($admin['status']) . "</td></tr>";
                echo "</table>";
                
                if ($admin['status'] !== 'active') {
                    echo "<p class='warning'>⚠ Account status is not 'active'</p>";
                }
            } else {
                echo "<p class='error'>✗ Admin account NOT FOUND</p>";
                echo "<p class='info'>💡 Solution: Create admin account using setup-admin-user-table.php</p>";
            }
        } else {
            echo "<p class='error'>✗ admin_user table does not exist</p>";
        }
    } catch (Exception $e) {
        echo "<p class='error'>✗ Error checking admin account: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
}

// Test 7: File Permissions
echo "<h2>7. File Permissions</h2>";
$importantFiles = [
    'config.local.php' => __DIR__ . '/config.local.php',
    'db_connect.php' => __DIR__ . '/db_connect.php',
];

foreach ($importantFiles as $name => $path) {
    if (file_exists($path)) {
        $perms = substr(sprintf('%o', fileperms($path)), -4);
        $readable = is_readable($path);
        echo "<p>$name: Permissions: $perms, Readable: " . ($readable ? '<span class="success">YES</span>' : '<span class="error">NO</span>') . "</p>";
    }
}

// Test 8: Error Log Check
echo "<h2>8. Recent PHP Errors</h2>";
$errorLog = ini_get('error_log');
if ($errorLog && file_exists($errorLog)) {
    echo "<p class='info'>Error log location: $errorLog</p>";
    $lines = file($errorLog);
    $recentErrors = array_slice($lines, -10);
    if (!empty($recentErrors)) {
        echo "<pre>";
        echo htmlspecialchars(implode('', $recentErrors));
        echo "</pre>";
    } else {
        echo "<p class='success'>✓ No recent errors in log</p>";
    }
} else {
    echo "<p class='info'>Error log not configured or not accessible</p>";
}

// Test 9: Server Information
echo "<h2>9. Server Information</h2>";
echo "<table>";
echo "<tr><th>Setting</th><th>Value</th></tr>";
echo "<tr><td>Server Name</td><td>" . ($_SERVER['SERVER_NAME'] ?? 'unknown') . "</td></tr>";
echo "<tr><td>HTTP Host</td><td>" . ($_SERVER['HTTP_HOST'] ?? 'unknown') . "</td></tr>";
echo "<tr><td>Document Root</td><td>" . ($_SERVER['DOCUMENT_ROOT'] ?? 'unknown') . "</td></tr>";
echo "<tr><td>PHP SAPI</td><td>" . php_sapi_name() . "</td></tr>";
echo "<tr><td>Memory Limit</td><td>" . ini_get('memory_limit') . "</td></tr>";
echo "<tr><td>Max Execution Time</td><td>" . ini_get('max_execution_time') . " seconds</td></tr>";
echo "</table>";

// Test 10: OTP Table Check
echo "<h2>10. OTP Verifications Table</h2>";
if (isset($pdo) && $pdo !== null) {
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE 'otp_verifications'");
        if ($stmt->rowCount() > 0) {
            echo "<p class='success'>✓ otp_verifications table exists</p>";
            
            // Check if email column exists
            $stmt = $pdo->query("SHOW COLUMNS FROM otp_verifications LIKE 'email'");
            if ($stmt->rowCount() > 0) {
                echo "<p class='success'>✓ email column exists</p>";
            } else {
                echo "<p class='error'>✗ email column MISSING</p>";
                echo "<p class='info'>💡 Solution: Run ALTER TABLE to add email column</p>";
            }
        } else {
            echo "<p class='error'>✗ otp_verifications table MISSING</p>";
            echo "<p class='info'>💡 Solution: Import complete_database_schema.sql</p>";
        }
    } catch (Exception $e) {
        echo "<p class='error'>✗ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
}

echo "<hr>";
echo "<h2>Summary & Recommendations</h2>";

if (isset($missingTables) && !empty($missingTables)) {
    echo "<div style='background: #fff3cd; padding: 15px; border-left: 4px solid #ffc107; margin: 20px 0;'>";
    echo "<h3 class='warning'>⚠ Action Required</h3>";
    echo "<p><strong>Missing Tables:</strong> " . implode(', ', $missingTables) . "</p>";
    echo "<p><strong>Solution:</strong> Import the complete database schema:</p>";
    echo "<pre>mysql -u root -p emer_comm_test < complete_database_schema.sql</pre>";
    echo "<p>Or via phpMyAdmin: Import → Select complete_database_schema.sql → Go</p>";
    echo "</div>";
}

if (isset($missing) && !empty($missing)) {
    echo "<div style='background: #f8d7da; padding: 15px; border-left: 4px solid #dc3545; margin: 20px 0;'>";
    echo "<h3 class='error'>✗ Missing PHP Extensions</h3>";
    echo "<p>Install missing extensions: " . implode(', ', $missing) . "</p>";
    echo "</div>";
}

echo "<hr>";
echo "<p><a href='../login.php'>Back to Login</a></p>";
?>

