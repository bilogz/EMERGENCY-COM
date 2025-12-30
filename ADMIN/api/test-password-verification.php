<?php
/**
 * Test Password Verification
 * Verifies if the password in database matches Admin#123
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: text/html; charset=utf-8');

echo "<h1>Password Verification Test</h1>";
echo "<style>body { font-family: monospace; padding: 20px; } .success { color: green; } .error { color: red; } .info { color: blue; } pre { background: #f5f5f5; padding: 10px; } table { border-collapse: collapse; width: 100%; } th, td { border: 1px solid #ddd; padding: 8px; text-align: left; } th { background-color: #f2f2f2; }</style>";

$adminEmail = 'joecel519@gmail.com';
$testPassword = 'Admin#123';

// Connect to database
echo "<h2>1. Database Connection</h2>";
try {
    require_once __DIR__ . '/config.env.php';
    require_once __DIR__ . '/db_connect.php';
    
    if (!isset($pdo) || $pdo === null) {
        echo "<p class='error'>✗ Database connection failed</p>";
        exit;
    }
    
    echo "<p class='success'>✓ Database connected</p>";
} catch (Exception $e) {
    echo "<p class='error'>✗ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    exit;
}

// Get admin account
echo "<h2>2. Get Admin Account</h2>";
try {
    $stmt = $pdo->prepare("SELECT id, name, email, password, role, status FROM admin_user WHERE email = ?");
    $stmt->execute([$adminEmail]);
    $admin = $stmt->fetch();
    
    if (!$admin) {
        echo "<p class='error'>✗ Admin account not found</p>";
        exit;
    }
    
    echo "<p class='success'>✓ Admin account found</p>";
    echo "<table>";
    echo "<tr><th>Field</th><th>Value</th></tr>";
    echo "<tr><td>ID</td><td>" . htmlspecialchars($admin['id']) . "</td></tr>";
    echo "<tr><td>Name</td><td>" . htmlspecialchars($admin['name']) . "</td></tr>";
    echo "<tr><td>Email</td><td>" . htmlspecialchars($admin['email']) . "</td></tr>";
    echo "<tr><td>Role</td><td>" . htmlspecialchars($admin['role']) . "</td></tr>";
    echo "<tr><td>Status</td><td>" . htmlspecialchars($admin['status']) . "</td></tr>";
    echo "<tr><td>Password Hash</td><td><code>" . htmlspecialchars(substr($admin['password'], 0, 30)) . "...</code></td></tr>";
    echo "</table>";
} catch (Exception $e) {
    echo "<p class='error'>✗ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    exit;
}

// Test password verification
echo "<h2>3. Password Verification</h2>";
echo "<p>Testing password: <strong>" . htmlspecialchars($testPassword) . "</strong></p>";
echo "<p>Stored hash: <code>" . htmlspecialchars(substr($admin['password'], 0, 30)) . "...</code></p>";

$verifyResult = password_verify($testPassword, $admin['password']);

if ($verifyResult) {
    echo "<p class='success'>✓ Password verification SUCCESSFUL!</p>";
    echo "<p class='success'>The password in the database matches 'Admin#123'</p>";
} else {
    echo "<p class='error'>✗ Password verification FAILED!</p>";
    echo "<p class='error'>The password in the database does NOT match 'Admin#123'</p>";
    
    // Generate a new hash
    echo "<h2>4. Generate New Password Hash</h2>";
    $newHash = password_hash($testPassword, PASSWORD_DEFAULT);
    echo "<p>New hash for 'Admin#123':</p>";
    echo "<pre>" . htmlspecialchars($newHash) . "</pre>";
    
    // Test the new hash
    $testNewHash = password_verify($testPassword, $newHash);
    if ($testNewHash) {
        echo "<p class='success'>✓ New hash verification test: SUCCESS</p>";
        
        // Offer to update
        echo "<h2>5. Update Password</h2>";
        if (isset($_GET['update']) && $_GET['update'] === 'yes') {
            try {
                $stmt = $pdo->prepare("UPDATE admin_user SET password = ?, updated_at = NOW() WHERE email = ?");
                $result = $stmt->execute([$newHash, $adminEmail]);
                
                if ($result && $stmt->rowCount() > 0) {
                    echo "<p class='success'>✓ Password updated successfully!</p>";
                    
                    // Verify again
                    $stmt = $pdo->prepare("SELECT password FROM admin_user WHERE email = ?");
                    $stmt->execute([$adminEmail]);
                    $updated = $stmt->fetch();
                    
                    if ($updated && password_verify($testPassword, $updated['password'])) {
                        echo "<p class='success'>✓ Verification after update: SUCCESS</p>";
                        echo "<p><a href='../login.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Go to Login</a></p>";
                    }
                } else {
                    echo "<p class='error'>✗ Update failed</p>";
                }
            } catch (Exception $e) {
                echo "<p class='error'>✗ Update error: " . htmlspecialchars($e->getMessage()) . "</p>";
            }
        } else {
            echo "<p class='info'>Click below to update the password with the correct hash:</p>";
            echo "<p><a href='?update=yes' style='background: #dc3545; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Update Password Now</a></p>";
        }
    }
}

// Check hash algorithm
echo "<h2>6. Hash Information</h2>";
$hashInfo = password_get_info($admin['password']);
echo "<table>";
echo "<tr><th>Property</th><th>Value</th></tr>";
echo "<tr><td>Algorithm</td><td>" . htmlspecialchars($hashInfo['algoName']) . "</td></tr>";
echo "<tr><td>Options</td><td><pre>" . print_r($hashInfo['options'], true) . "</pre></td></tr>";
echo "</table>";

// Check if OTP is required
echo "<h2>7. OTP Configuration</h2>";
try {
    $requireOtp = getSecureConfig('ADMIN_REQUIRE_OTP', false);
    echo "<p>ADMIN_REQUIRE_OTP: <strong>" . ($requireOtp ? '<span class="error">TRUE (OTP required)</span>' : '<span class="success">FALSE (OTP not required)</span>') . "</strong></p>";
    
    if ($requireOtp) {
        echo "<p class='info'>ℹ️ OTP is required. Make sure you've verified your email with OTP before logging in.</p>";
    }
} catch (Exception $e) {
    echo "<p class='error'>Could not check OTP config: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "<hr>";
echo "<p><a href='check-login-error.php'>Full Diagnostic</a> | <a href='../login.php'>Back to Login</a></p>";
?>

