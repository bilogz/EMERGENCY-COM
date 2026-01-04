<?php
/**
 * Update Super Admin Password on Server
 * Changes password for joecel519@gmail.com to Admin#123
 * 
 * ⚠️ DELETE THIS FILE AFTER USE FOR SECURITY ⚠️
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: text/html; charset=utf-8');

echo "<h1>Update Super Admin Password</h1>";
echo "<style>body { font-family: monospace; padding: 20px; } .success { color: green; } .error { color: red; } .info { color: blue; } .warning { color: orange; } pre { background: #f5f5f5; padding: 10px; } table { border-collapse: collapse; width: 100%; } th, td { border: 1px solid #ddd; padding: 8px; text-align: left; } th { background-color: #f2f2f2; }</style>";

// Configuration
$adminEmail = 'joecel519@gmail.com';
$newPassword = 'Admin#123';

// Step 1: Connect to database
echo "<h2>Step 1: Database Connection</h2>";
try {
    require_once __DIR__ . '/config.env.php';
    require_once __DIR__ . '/db_connect.php';
    
    if (!isset($pdo) || $pdo === null) {
        echo "<p class='error'>✗ Database connection failed</p>";
        exit;
    }
    
    echo "<p class='success'>✓ Database connected successfully</p>";
} catch (Exception $e) {
    echo "<p class='error'>✗ Database connection error: " . htmlspecialchars($e->getMessage()) . "</p>";
    exit;
}

// Step 2: Check if admin_user table exists
echo "<h2>Step 2: Check Admin Table</h2>";
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'admin_user'");
    if ($stmt->rowCount() === 0) {
        echo "<p class='error'>✗ admin_user table does not exist</p>";
        echo "<p class='info'>💡 Solution: Import complete_database_schema.sql first</p>";
        exit;
    }
    echo "<p class='success'>✓ admin_user table exists</p>";
} catch (Exception $e) {
    echo "<p class='error'>✗ Error checking table: " . htmlspecialchars($e->getMessage()) . "</p>";
    exit;
}

// Step 3: Find the admin account
echo "<h2>Step 3: Find Admin Account</h2>";
try {
    $stmt = $pdo->prepare("SELECT id, name, email, role, status FROM admin_user WHERE email = ?");
    $stmt->execute([$adminEmail]);
    $admin = $stmt->fetch();
    
    if (!$admin) {
        echo "<p class='error'>✗ Admin account not found: $adminEmail</p>";
        echo "<p class='info'>💡 Solution: Create the admin account first</p>";
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
    echo "</table>";
    
    if ($admin['status'] !== 'active') {
        echo "<p class='warning'>⚠ Account status is not 'active'</p>";
    }
} catch (Exception $e) {
    echo "<p class='error'>✗ Error finding admin: " . htmlspecialchars($e->getMessage()) . "</p>";
    exit;
}

// Step 4: Hash the new password
echo "<h2>Step 4: Hash New Password</h2>";
$passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
echo "<p class='info'>Password: <strong>" . htmlspecialchars($newPassword) . "</strong></p>";
echo "<p class='info'>Hash: <code>" . substr($passwordHash, 0, 30) . "...</code></p>";
echo "<p class='success'>✓ Password hashed successfully</p>";

// Step 5: Verify password strength
echo "<h2>Step 5: Verify Password</h2>";
$hasUppercase = preg_match('/[A-Z]/', $newPassword);
$hasLowercase = preg_match('/[a-z]/', $newPassword);
$hasNumber = preg_match('/[0-9]/', $newPassword);
$hasSpecial = preg_match('/[^A-Za-z0-9]/', $newPassword);
$minLength = strlen($newPassword) >= 8;

if ($hasUppercase && $hasLowercase && $hasNumber && $hasSpecial && $minLength) {
    echo "<p class='success'>✓ Password meets security requirements</p>";
} else {
    echo "<p class='warning'>⚠ Password requirements:</p>";
    echo "<ul>";
    echo "<li>" . ($minLength ? "✓" : "✗") . " At least 8 characters</li>";
    echo "<li>" . ($hasUppercase ? "✓" : "✗") . " Contains uppercase letter</li>";
    echo "<li>" . ($hasLowercase ? "✓" : "✗") . " Contains lowercase letter</li>";
    echo "<li>" . ($hasNumber ? "✓" : "✗") . " Contains number</li>";
    echo "<li>" . ($hasSpecial ? "✓" : "✗") . " Contains special character</li>";
    echo "</ul>";
}

// Step 6: Update the password
echo "<h2>Step 6: Update Password in Database</h2>";

// Check if this is a confirmation request
$confirmed = isset($_GET['confirm']) && $_GET['confirm'] === 'yes';

if (!$confirmed) {
    echo "<div style='background: #fff3cd; padding: 15px; border-left: 4px solid #ffc107; margin: 20px 0;'>";
    echo "<h3 class='warning'>⚠ Confirmation Required</h3>";
    echo "<p>You are about to change the password for:</p>";
    echo "<ul>";
    echo "<li><strong>Email:</strong> " . htmlspecialchars($adminEmail) . "</li>";
    echo "<li><strong>New Password:</strong> " . htmlspecialchars($newPassword) . "</li>";
    echo "</ul>";
    echo "<p><strong>Are you sure you want to proceed?</strong></p>";
    echo "<p><a href='?confirm=yes' style='background: #dc3545; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin-right: 10px;'>Yes, Update Password</a>";
    echo "<a href='../login.php' style='background: #6c757d; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block;'>Cancel</a></p>";
    echo "</div>";
    exit;
}

try {
    $stmt = $pdo->prepare("UPDATE admin_user SET password = ?, updated_at = NOW() WHERE email = ?");
    $result = $stmt->execute([$passwordHash, $adminEmail]);
    
    if ($result && $stmt->rowCount() > 0) {
        echo "<p class='success'>✓ Password updated successfully!</p>";
        
        // Verify the update
        $stmt = $pdo->prepare("SELECT password FROM admin_user WHERE email = ?");
        $stmt->execute([$adminEmail]);
        $updated = $stmt->fetch();
        
        if ($updated && password_verify($newPassword, $updated['password'])) {
            echo "<p class='success'>✓ Password verification successful!</p>";
            
            echo "<hr>";
            echo "<h2>✅ Update Complete</h2>";
            echo "<div style='background: #d4edda; padding: 15px; border-left: 4px solid #28a745; margin: 20px 0;'>";
            echo "<h3 class='success'>Password Successfully Changed</h3>";
            echo "<table>";
            echo "<tr><th>Field</th><th>Value</th></tr>";
            echo "<tr><td>Email</td><td>" . htmlspecialchars($adminEmail) . "</td></tr>";
            echo "<tr><td>New Password</td><td>" . htmlspecialchars($newPassword) . "</td></tr>";
            echo "<tr><td>Status</td><td><span class='success'>✓ Active</span></td></tr>";
            echo "</table>";
            echo "</div>";
            
            echo "<p><strong>⚠️ IMPORTANT:</strong> Delete this file after use for security!</p>";
            echo "<p><a href='../login.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block;'>Go to Login</a></p>";
        } else {
            echo "<p class='error'>✗ Password verification failed</p>";
        }
    } else {
        echo "<p class='error'>✗ Password update failed (no rows affected)</p>";
    }
} catch (Exception $e) {
    echo "<p class='error'>✗ Error updating password: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}

echo "<hr>";
echo "<p><a href='check-login-error.php'>Run Diagnostic</a> | <a href='../login.php'>Back to Login</a></p>";
?>





