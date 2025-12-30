<?php
/**
 * Setup Admin User Table
 * Creates the admin_user table and sets up the admin account
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: text/html; charset=utf-8');

require_once __DIR__ . '/db_connect.php';

echo "<h1>Setup Admin User Table</h1>";
echo "<style>body { font-family: monospace; padding: 20px; } .success { color: green; } .error { color: red; } .info { color: blue; } pre { background: #f5f5f5; padding: 10px; }</style>";

if (!isset($pdo) || $pdo === null) {
    die("<p class='error'>❌ Database connection failed!</p>");
}

try {
    // Step 1: Create admin_user table
    echo "<h2>Step 1: Creating admin_user table</h2>";
    
    $createTableSQL = "
    CREATE TABLE IF NOT EXISTS admin_user (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT DEFAULT NULL COMMENT 'Optional reference to users table (NULL for standalone admin accounts)',
        name VARCHAR(255) NOT NULL COMMENT 'Full name of the admin',
        username VARCHAR(100) DEFAULT NULL COMMENT 'Username for login',
        email VARCHAR(255) NOT NULL COMMENT 'Email address (unique)',
        password VARCHAR(255) NOT NULL COMMENT 'Hashed password',
        role VARCHAR(20) DEFAULT 'admin' COMMENT 'super_admin, admin, staff',
        status VARCHAR(20) DEFAULT 'pending_approval' COMMENT 'active, inactive, suspended, pending_approval',
        phone VARCHAR(20) DEFAULT NULL COMMENT 'Phone number',
        created_by INT DEFAULT NULL COMMENT 'ID of admin who created this account',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        last_login DATETIME DEFAULT NULL,
        
        -- Indexes
        UNIQUE KEY unique_email (email),
        UNIQUE KEY unique_username (username),
        INDEX idx_user_id (user_id),
        INDEX idx_role (role),
        INDEX idx_status (status),
        INDEX idx_created_by (created_by),
        INDEX idx_created_at (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    
    $pdo->exec($createTableSQL);
    echo "<p class='success'>✓ admin_user table created successfully</p>";
    
    // Step 2: Check if admin account exists
    echo "<h2>Step 2: Checking for admin account</h2>";
    
    $stmt = $pdo->prepare("SELECT id, name, email, role, status FROM admin_user WHERE email = ?");
    $stmt->execute(['joecel519@gmail.com']);
    $admin = $stmt->fetch();
    
    if ($admin) {
        echo "<p class='info'>⚠ Admin account already exists</p>";
        echo "<pre>";
        print_r($admin);
        echo "</pre>";
        
        // Step 3: Update password
        echo "<h2>Step 3: Updating password</h2>";
        $newPassword = 'Admin#123';
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        
        $updateStmt = $pdo->prepare("UPDATE admin_user SET password = ?, updated_at = NOW() WHERE email = ?");
        $updateStmt->execute([$hashedPassword, 'joecel519@gmail.com']);
        
        // Verify update
        $verifyStmt = $pdo->prepare("SELECT password FROM admin_user WHERE email = ?");
        $verifyStmt->execute(['joecel519@gmail.com']);
        $passwordData = $verifyStmt->fetch();
        $passwordVerified = password_verify($newPassword, $passwordData['password']);
        
        if ($passwordVerified) {
            echo "<p class='success'>✓ Password updated successfully!</p>";
        } else {
            echo "<p class='error'>✗ Password update verification failed</p>";
        }
        
        // Step 4: Ensure account is active and super_admin
        echo "<h2>Step 4: Ensuring account is active and super_admin</h2>";
        $updateRoleStmt = $pdo->prepare("UPDATE admin_user SET role = 'super_admin', status = 'active' WHERE email = ?");
        $updateRoleStmt->execute(['joecel519@gmail.com']);
        echo "<p class='success'>✓ Account set to super_admin and active</p>";
        
    } else {
        echo "<p class='info'>⚠ Admin account does not exist - creating it</p>";
        
        // Step 3: Create admin account
        echo "<h2>Step 3: Creating admin account</h2>";
        
        $newPassword = 'Admin#123';
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        
        $insertStmt = $pdo->prepare("
            INSERT INTO admin_user (user_id, name, username, email, password, role, status, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $insertStmt->execute([
            null,  // user_id
            'Super Administrator',  // name
            'admin',  // username
            'joecel519@gmail.com',  // email
            $hashedPassword,  // password
            'super_admin',  // role
            'active'  // status
        ]);
        
        $adminId = $pdo->lastInsertId();
        echo "<p class='success'>✓ Admin account created successfully (ID: $adminId)</p>";
        
        // Verify
        $verifyStmt = $pdo->prepare("SELECT id, name, email, role, status FROM admin_user WHERE id = ?");
        $verifyStmt->execute([$adminId]);
        $newAdmin = $verifyStmt->fetch();
        
        echo "<pre>";
        print_r($newAdmin);
        echo "</pre>";
    }
    
    // Final verification
    echo "<h2>Final Verification</h2>";
    $finalStmt = $pdo->prepare("SELECT id, name, email, role, status FROM admin_user WHERE email = ?");
    $finalStmt->execute(['joecel519@gmail.com']);
    $finalAdmin = $finalStmt->fetch();
    
    if ($finalAdmin) {
        echo "<p class='success'>✓ Admin account verified</p>";
        echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
        echo "<tr><th>Field</th><th>Value</th></tr>";
        echo "<tr><td>ID</td><td>" . htmlspecialchars($finalAdmin['id']) . "</td></tr>";
        echo "<tr><td>Name</td><td>" . htmlspecialchars($finalAdmin['name']) . "</td></tr>";
        echo "<tr><td>Email</td><td>" . htmlspecialchars($finalAdmin['email']) . "</td></tr>";
        echo "<tr><td>Role</td><td><strong>" . htmlspecialchars($finalAdmin['role']) . "</strong></td></tr>";
        echo "<tr><td>Status</td><td><strong>" . htmlspecialchars($finalAdmin['status']) . "</strong></td></tr>";
        echo "</table>";
        
        echo "<h2>Login Credentials</h2>";
        echo "<p><strong>Email:</strong> joecel519@gmail.com</p>";
        echo "<p><strong>Password:</strong> Admin#123</p>";
    }
    
    echo "<hr>";
    echo "<p class='success'><strong>✓ Setup Complete!</strong></p>";
    echo "<p><a href='../login.php'>Go to Login Page</a></p>";
    
} catch (PDOException $e) {
    echo "<p class='error'>❌ Database Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}
?>

