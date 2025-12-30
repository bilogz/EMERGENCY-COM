<?php
/**
 * Check and Update Password Script
 * This script checks the current password in the database and updates it if needed
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/db_connect.php';

$targetEmail = 'joecel519@gmail.com';
$newPassword = 'Admin#123';

if (!isset($pdo) || $pdo === null) {
    die('<h2 style="color: red;">❌ Database connection failed!</h2><p>Please check your database configuration and ensure MySQL is running.</p>');
}

try {
    // Check if admin_user table exists
    $tableCheck = $pdo->query("SHOW TABLES LIKE 'admin_user'");
    if ($tableCheck->rowCount() === 0) {
        die('<h2 style="color: red;">❌ admin_user table does not exist!</h2>');
    }
    
    // Get current admin info
    $stmt = $pdo->prepare("SELECT id, name, email, role, LEFT(password, 30) as password_preview FROM admin_user WHERE email = ?");
    $stmt->execute([$targetEmail]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$admin) {
        die('<h2 style="color: red;">❌ Admin account not found!</h2><p>No account found with email: <strong>' . htmlspecialchars($targetEmail) . '</strong></p>');
    }
    
    // Test current password
    $stmt = $pdo->prepare("SELECT password FROM admin_user WHERE email = ?");
    $stmt->execute([$targetEmail]);
    $passwordData = $stmt->fetch(PDO::FETCH_ASSOC);
    $currentPasswordWorks = password_verify($newPassword, $passwordData['password']);
    
    echo '<!DOCTYPE html>
<html>
<head>
    <title>Password Check & Update</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; background: #f5f5f5; }
        .container { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h2 { color: #28a745; margin-top: 0; }
        .info-box { background: #e7f3ff; border-left: 4px solid #2196F3; padding: 15px; margin: 20px 0; border-radius: 4px; }
        .success { color: #28a745; font-weight: bold; }
        .error { color: #dc3545; font-weight: bold; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        table th, table td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        table th { background-color: #f8f9fa; font-weight: bold; }
        .btn { display: inline-block; padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 4px; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Password Status Check</h2>
        
        <div class="info-box">
            <p><strong>Account Information:</strong></p>
            <table>
                <tr><th>Field</th><th>Value</th></tr>
                <tr><td>ID</td><td>' . htmlspecialchars($admin['id']) . '</td></tr>
                <tr><td>Name</td><td>' . htmlspecialchars($admin['name']) . '</td></tr>
                <tr><td>Email</td><td>' . htmlspecialchars($admin['email']) . '</td></tr>
                <tr><td>Role</td><td><strong>' . htmlspecialchars($admin['role']) . '</strong></td></tr>
                <tr><td>Password Hash Preview</td><td><code>' . htmlspecialchars($admin['password_preview']) . '...</code></td></tr>
            </table>
        </div>
        
        <div class="info-box">';
    
    if ($currentPasswordWorks) {
        echo '<p class="success">✅ Current password in database matches "Admin#123" - No update needed!</p>';
    } else {
        echo '<p class="error">❌ Current password does NOT match "Admin#123" - Updating now...</p>';
        
        // Update the password
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $updateStmt = $pdo->prepare("UPDATE admin_user SET password = ?, updated_at = NOW() WHERE email = ?");
        $updateStmt->execute([$hashedPassword, $targetEmail]);
        
        // Verify the update
        $verifyStmt = $pdo->prepare("SELECT password FROM admin_user WHERE email = ?");
        $verifyStmt->execute([$targetEmail]);
        $updatedPassword = $verifyStmt->fetch(PDO::FETCH_ASSOC);
        $passwordVerified = password_verify($newPassword, $updatedPassword['password']);
        
        if ($passwordVerified) {
            echo '<p class="success">✅ Password updated successfully! You can now log in with:</p>';
            echo '<ul><li>Email: <strong>' . htmlspecialchars($targetEmail) . '</strong></li>';
            echo '<li>Password: <strong>Admin#123</strong></li></ul>';
        } else {
            echo '<p class="error">❌ Password update failed verification. Please try again.</p>';
        }
    }
    
    echo '</div>
        <a href="../login.php" class="btn">Go to Login Page</a>
    </div>
</body>
</html>';
    
} catch (PDOException $e) {
    echo '<h2 style="color: red;">❌ Database Error!</h2>';
    echo '<p><strong>Error:</strong> ' . htmlspecialchars($e->getMessage()) . '</p>';
    echo '<p>Please check your database connection and try again.</p>';
}
?>

