<?php
/**
 * Quick Login Error Checker
 * Shows what error is happening during login
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: text/html; charset=utf-8');

echo "<h1>Login Error Diagnostic</h1>";
echo "<style>body { font-family: monospace; padding: 20px; } .success { color: green; } .error { color: red; } .info { color: blue; } pre { background: #f5f5f5; padding: 10px; }</style>";

// Test 1: Check if login-web.php exists
echo "<h2>1. File Check</h2>";
$loginFile = __DIR__ . '/login-web.php';
if (file_exists($loginFile)) {
    echo "<p class='success'>✓ login-web.php exists</p>";
} else {
    echo "<p class='error'>✗ login-web.php NOT FOUND</p>";
    exit;
}

// Test 2: Check config files
echo "<h2>2. Config Files</h2>";
try {
    require_once __DIR__ . '/config.env.php';
    echo "<p class='success'>✓ config.env.php loaded</p>";
} catch (Exception $e) {
    echo "<p class='error'>✗ config.env.php error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

try {
    require_once __DIR__ . '/config.local.php';
    echo "<p class='success'>✓ config.local.php loaded</p>";
} catch (Exception $e) {
    echo "<p class='error'>✗ config.local.php error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

// Test 3: Database Connection
echo "<h2>3. Database Connection</h2>";
try {
    require_once __DIR__ . '/db_connect.php';
    
    if (isset($pdo) && $pdo !== null) {
        echo "<p class='success'>✓ Database connected</p>";
        
        // Test query
        $stmt = $pdo->query("SELECT 1");
        echo "<p class='success'>✓ Database query works</p>";
    } else {
        echo "<p class='error'>✗ Database connection failed - pdo is null</p>";
    }
} catch (Exception $e) {
    echo "<p class='error'>✗ Database error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}

// Test 4: Check admin_user table
echo "<h2>4. Admin User Table</h2>";
if (isset($pdo) && $pdo !== null) {
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE 'admin_user'");
        if ($stmt->rowCount() > 0) {
            echo "<p class='success'>✓ admin_user table exists</p>";
            
            // Check for the account
            $stmt = $pdo->prepare("SELECT id, email, name, role, status FROM admin_user WHERE email = ?");
            $stmt->execute(['joecel519@gmail.com']);
            $admin = $stmt->fetch();
            
            if ($admin) {
                echo "<p class='success'>✓ Admin account found</p>";
                echo "<pre>";
                print_r($admin);
                echo "</pre>";
            } else {
                echo "<p class='error'>✗ Admin account NOT FOUND</p>";
            }
        } else {
            echo "<p class='error'>✗ admin_user table does NOT exist</p>";
            echo "<p class='info'>💡 Solution: Import complete_database_schema.sql</p>";
        }
    } catch (Exception $e) {
        echo "<p class='error'>✗ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
}

// Test 5: Simulate login request
echo "<h2>5. Simulate Login Request</h2>";
echo "<p class='info'>Testing login with: joecel519@gmail.com</p>";

if (isset($pdo) && $pdo !== null) {
    try {
        $email = 'joecel519@gmail.com';
        $password = 'Admin#123';
        
        // Get admin user
        $stmt = $pdo->prepare("SELECT * FROM admin_user WHERE email = ? AND status = 'active'");
        $stmt->execute([$email]);
        $admin = $stmt->fetch();
        
        if (!$admin) {
            echo "<p class='error'>✗ Admin account not found or not active</p>";
        } else {
            echo "<p class='success'>✓ Admin account found</p>";
            
            // Verify password
            if (password_verify($password, $admin['password'])) {
                echo "<p class='success'>✓ Password is CORRECT</p>";
            } else {
                echo "<p class='error'>✗ Password is INCORRECT</p>";
                echo "<p class='info'>Stored hash: " . substr($admin['password'], 0, 20) . "...</p>";
            }
        }
    } catch (Exception $e) {
        echo "<p class='error'>✗ Login simulation error: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
}

// Test 6: Check OTP requirement
echo "<h2>6. OTP Configuration</h2>";
try {
    $requireOtp = getSecureConfig('ADMIN_REQUIRE_OTP', false);
    echo "<p>ADMIN_REQUIRE_OTP: " . ($requireOtp ? '<span class="error">TRUE (OTP required)</span>' : '<span class="success">FALSE (OTP not required)</span>') . "</p>";
    
    if (isset($pdo) && $pdo !== null) {
        $stmt = $pdo->query("SHOW TABLES LIKE 'otp_verifications'");
        if ($stmt->rowCount() > 0) {
            echo "<p class='success'>✓ otp_verifications table exists</p>";
        } else {
            echo "<p class='error'>✗ otp_verifications table MISSING</p>";
        }
    }
} catch (Exception $e) {
    echo "<p class='error'>✗ Error checking OTP config: " . htmlspecialchars($e->getMessage()) . "</p>";
}

// Test 7: Check PHP error log
echo "<h2>7. Recent PHP Errors</h2>";
$errorLog = ini_get('error_log');
if ($errorLog && file_exists($errorLog)) {
    $lines = file($errorLog);
    $recentErrors = array_slice($lines, -20);
    if (!empty($recentErrors)) {
        echo "<pre>";
        echo htmlspecialchars(implode('', $recentErrors));
        echo "</pre>";
    } else {
        echo "<p class='success'>✓ No recent errors in log</p>";
    }
} else {
    echo "<p class='info'>Error log: " . ($errorLog ?: 'Not configured') . "</p>";
}

// Test 8: Check session
echo "<h2>8. Session Check</h2>";
session_start();
echo "<p>Session ID: " . session_id() . "</p>";
echo "<p>Session status: " . (session_status() === PHP_SESSION_ACTIVE ? '<span class="success">Active</span>' : '<span class="error">Not Active</span>') . "</p>";

echo "<hr>";
echo "<h2>Next Steps</h2>";
echo "<ol>";
echo "<li>If database connection fails, check config.local.php DB_HOST and DB_PORT</li>";
echo "<li>If admin_user table missing, import complete_database_schema.sql</li>";
echo "<li>If password incorrect, update password hash in database</li>";
echo "<li>Check the full diagnostic: <a href='diagnose-server-issues.php'>diagnose-server-issues.php</a></li>";
echo "</ol>";

echo "<p><a href='../login.php'>Back to Login</a></p>";
?>

