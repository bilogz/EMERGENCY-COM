<?php
/**
 * Update Admin Password Script
 * Updates the password for a specific admin account
 * 
 * Usage: Run this script via browser or command line
 * Example: http://localhost/EMERGENCY-COM/ADMIN/api/update-admin-password.php
 */

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/db_connect.php';

// Configuration
$targetEmail = 'joecel519@gmail.com';
$newPassword = 'Admin#123';

// Check if database connection exists
if (!isset($pdo) || $pdo === null) {
    die('<h2 style="color: red;">❌ Database connection failed!</h2><p>Please check your database configuration.</p>');
}

try {
    // Check if admin_user table exists
    $tableCheck = $pdo->query("SHOW TABLES LIKE 'admin_user'");
    if ($tableCheck->rowCount() === 0) {
        die('<h2 style="color: red;">❌ admin_user table does not exist!</h2><p>Please run the database setup first.</p>');
    }
    
    // Find the admin account
    $stmt = $pdo->prepare("SELECT id, name, email, role FROM admin_user WHERE email = ?");
    $stmt->execute([$targetEmail]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$admin) {
        die('<h2 style="color: red;">❌ Admin account not found!</h2><p>No account found with email: <strong>' . htmlspecialchars($targetEmail) . '</strong></p>');
    }
    
    // Hash the new password
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
    
    // Update the password
    $updateStmt = $pdo->prepare("UPDATE admin_user SET password = ?, updated_at = NOW() WHERE email = ?");
    $updateStmt->execute([$hashedPassword, $targetEmail]);
    
    // Verify the update
    $verifyStmt = $pdo->prepare("SELECT id, name, email, role FROM admin_user WHERE email = ?");
    $verifyStmt->execute([$targetEmail]);
    $updatedAdmin = $verifyStmt->fetch(PDO::FETCH_ASSOC);
    
    // Test password verification
    $testStmt = $pdo->prepare("SELECT password FROM admin_user WHERE email = ?");
    $testStmt->execute([$targetEmail]);
    $passwordData = $testStmt->fetch(PDO::FETCH_ASSOC);
    $passwordVerified = password_verify($newPassword, $passwordData['password']);
    
    // Display success message
    echo '<!DOCTYPE html>
<html>
<head>
    <title>Password Update Result</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h2 {
            color: #28a745;
            margin-top: 0;
        }
        .info-box {
            background: #e7f3ff;
            border-left: 4px solid #2196F3;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .success {
            color: #28a745;
            font-weight: bold;
        }
        .warning {
            color: #ff9800;
            font-weight: bold;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        table th, table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        table th {
            background-color: #f8f9fa;
            font-weight: bold;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            margin-top: 20px;
        }
        .btn:hover {
            background: #0056b3;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>✅ Password Updated Successfully!</h2>
        
        <div class="info-box">
            <p><strong>Account Details:</strong></p>
            <table>
                <tr>
                    <th>Field</th>
                    <th>Value</th>
                </tr>
                <tr>
                    <td>ID</td>
                    <td>' . htmlspecialchars($updatedAdmin['id']) . '</td>
                </tr>
                <tr>
                    <td>Name</td>
                    <td>' . htmlspecialchars($updatedAdmin['name']) . '</td>
                </tr>
                <tr>
                    <td>Email</td>
                    <td>' . htmlspecialchars($updatedAdmin['email']) . '</td>
                </tr>
                <tr>
                    <td>Role</td>
                    <td><strong>' . htmlspecialchars($updatedAdmin['role']) . '</strong></td>
                </tr>
            </table>
        </div>
        
        <div class="info-box">
            <p><strong>Password Information:</strong></p>
            <ul>
                <li>New Password: <strong>' . htmlspecialchars($newPassword) . '</strong></li>
                <li>Password Verified: <span class="' . ($passwordVerified ? 'success' : 'warning') . '">' . ($passwordVerified ? '✅ Yes' : '❌ No') . '</span></li>
            </ul>
        </div>
        
        <div class="warning" style="background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; border-radius: 4px; margin: 20px 0;">
            <strong>⚠️ Security Notice:</strong><br>
            Please delete this file after use for security reasons!
        </div>
        
        <a href="../login.php" class="btn">Go to Login Page</a>
    </div>
</body>
</html>';
    
} catch (PDOException $e) {
    echo '<h2 style="color: red;">❌ Database Error!</h2>';
    echo '<p><strong>Error:</strong> ' . htmlspecialchars($e->getMessage()) . '</p>';
    echo '<p>Please check your database connection and try again.</p>';
} catch (Exception $e) {
    echo '<h2 style="color: red;">❌ Error!</h2>';
    echo '<p><strong>Error:</strong> ' . htmlspecialchars($e->getMessage()) . '</p>';
}
?>

