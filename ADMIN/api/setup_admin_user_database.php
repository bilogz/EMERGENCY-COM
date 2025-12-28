<?php
/**
 * Setup Admin User Database
 * Creates admin_user table and migrates existing admin accounts
 * Run this script once to set up the admin_user table
 */

require_once __DIR__ . '/db_connect.php';

header('Content-Type: text/html; charset=utf-8');

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup Admin User Database</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            padding: 2rem; 
            max-width: 900px; 
            margin: 0 auto;
            background: #f5f5f5;
        }
        .container {
            background: white;
            border-radius: 10px;
            padding: 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 { 
            color: #333; 
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #3a7675;
        }
        .success { 
            color: #155724; 
            background: #d4edda; 
            padding: 1rem; 
            border-radius: 5px; 
            margin: 1rem 0;
            border-left: 4px solid #28a745;
        }
        .error { 
            color: #721c24; 
            background: #f8d7da; 
            padding: 1rem; 
            border-radius: 5px; 
            margin: 1rem 0;
            border-left: 4px solid #dc3545;
        }
        .info { 
            color: #004085; 
            background: #d1ecf1; 
            padding: 1rem; 
            border-radius: 5px; 
            margin: 1rem 0;
            border-left: 4px solid #17a2b8;
        }
        .warning {
            color: #856404;
            background: #fff3cd;
            padding: 1rem;
            border-radius: 5px;
            margin: 1rem 0;
            border-left: 4px solid #ffc107;
        }
        table { 
            width: 100%; 
            border-collapse: collapse; 
            margin: 1rem 0;
        }
        table th, table td { 
            padding: 0.75rem; 
            text-align: left; 
            border: 1px solid #dee2e6;
        }
        table th { 
            background: #3a7675; 
            color: white; 
            font-weight: 600;
        }
        table tr:nth-child(even) { 
            background: #f8f9fa; 
        }
        .badge {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            border-radius: 3px;
            font-size: 0.875rem;
            font-weight: 600;
        }
        .badge-success { background: #28a745; color: white; }
        .badge-warning { background: #ffc107; color: #333; }
        .badge-danger { background: #dc3545; color: white; }
        .badge-info { background: #17a2b8; color: white; }
        .step {
            margin: 1.5rem 0;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 5px;
        }
        .step-number {
            display: inline-block;
            width: 30px;
            height: 30px;
            background: #3a7675;
            color: white;
            border-radius: 50%;
            text-align: center;
            line-height: 30px;
            font-weight: bold;
            margin-right: 0.5rem;
        }
        pre { 
            background: #f4f4f4; 
            padding: 1rem; 
            border-radius: 5px; 
            overflow-x: auto;
            font-size: 0.875rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Admin User Database Setup</h1>
<?php

if ($pdo === null) {
    echo "<div class='error'><strong>❌ Database Connection Failed</strong><br>";
    echo "Please check your database configuration in <code>db_connect.php</code></div>";
    echo "</div></body></html>";
    exit();
}

try {
    echo "<div class='info'><strong>📋 Starting Setup...</strong><br>This will create the admin_user table and migrate existing admin accounts.</div>";
    
    // Step 1: Create admin_user table
    echo "<div class='step'>";
    echo "<span class='step-number'>1</span><strong>Creating admin_user table...</strong><br>";
    
    $createTableSQL = "
    CREATE TABLE IF NOT EXISTS admin_user (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL COMMENT 'Reference to users table',
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
        
        UNIQUE KEY unique_email (email),
        UNIQUE KEY unique_username (username),
        INDEX idx_user_id (user_id),
        INDEX idx_role (role),
        INDEX idx_status (status),
        INDEX idx_created_by (created_by),
        INDEX idx_created_at (created_at),
        
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (created_by) REFERENCES admin_user(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";
    
    try {
        $pdo->exec($createTableSQL);
        echo "<span class='success'>✓ Table created successfully!</span>";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'already exists') !== false) {
            echo "<span class='warning'>⚠ Table already exists. Continuing...</span>";
        } else {
            throw $e;
        }
    }
    echo "</div>";
    
    // Step 2: Check if users table exists
    echo "<div class='step'>";
    echo "<span class='step-number'>2</span><strong>Checking users table...</strong><br>";
    
    try {
        $pdo->query("SELECT 1 FROM users LIMIT 1");
        echo "<span class='success'>✓ Users table exists</span>";
    } catch (PDOException $e) {
        echo "<div class='error'>❌ Users table not found. Please create the users table first.</div>";
        echo "</div></div></body></html>";
        exit();
    }
    echo "</div>";
    
    // Step 3: Migrate existing admin accounts
    echo "<div class='step'>";
    echo "<span class='step-number'>3</span><strong>Migrating existing admin accounts...</strong><br>";
    
    // Check how many admins exist in users table
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE user_type = 'admin'");
    $result = $stmt->fetch();
    $adminCount = (int)$result['count'];
    
    echo "Found {$adminCount} admin account(s) in users table.<br>";
    
    if ($adminCount > 0) {
        // Migrate admins from users table to admin_user table
        $migrateSQL = "
        INSERT INTO admin_user (user_id, name, username, email, password, role, status, created_at)
        SELECT 
            id as user_id,
            name,
            username,
            email,
            password,
            'admin' as role,
            status,
            created_at
        FROM users 
        WHERE user_type = 'admin' 
        AND NOT EXISTS (
            SELECT 1 FROM admin_user WHERE admin_user.user_id = users.id
        )
        ";
        
        try {
            $pdo->exec($migrateSQL);
            $migratedCount = $pdo->query("SELECT COUNT(*) FROM admin_user")->fetchColumn();
            echo "<span class='success'>✓ Migrated {$migratedCount} admin account(s) to admin_user table</span>";
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                echo "<span class='warning'>⚠ Some accounts already exist in admin_user table</span>";
            } else {
                throw $e;
            }
        }
    } else {
        echo "<span class='info'>ℹ No admin accounts found in users table. You can create admin accounts after setup.</span>";
    }
    echo "</div>";
    
    // Step 4: Set first admin as super_admin
    echo "<div class='step'>";
    echo "<span class='step-number'>4</span><strong>Setting up super admin...</strong><br>";
    
    // Check if super_admin exists
    $stmt = $pdo->query("SELECT COUNT(*) FROM admin_user WHERE role = 'super_admin' AND status = 'active'");
    $superAdminCount = (int)$stmt->fetchColumn();
    
    if ($superAdminCount === 0) {
        // Get the first admin (oldest by created_at)
        $stmt = $pdo->query("SELECT id FROM admin_user ORDER BY created_at ASC LIMIT 1");
        $firstAdmin = $stmt->fetch();
        
        if ($firstAdmin) {
            $updateStmt = $pdo->prepare("UPDATE admin_user SET role = 'super_admin', status = 'active' WHERE id = ?");
            $updateStmt->execute([$firstAdmin['id']]);
            echo "<span class='success'>✓ First admin set as super_admin</span>";
        } else {
            echo "<span class='warning'>⚠ No admin accounts found. First admin created will be set as super_admin.</span>";
        }
    } else {
        echo "<span class='info'>ℹ Super admin already exists</span>";
    }
    echo "</div>";
    
    // Step 5: Verification
    echo "<div class='step'>";
    echo "<span class='step-number'>5</span><strong>Verification...</strong><br>";
    
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM admin_user");
        $result = $stmt->fetch();
        $totalAdmins = (int)$result['count'];
        
        echo "<div class='success'>";
        echo "<strong>✓ Setup Complete!</strong><br>";
        echo "✓ admin_user table exists<br>";
        echo "✓ Found {$totalAdmins} admin account(s) in admin_user table<br>";
        echo "</div>";
        
        // Show admin accounts
        if ($totalAdmins > 0) {
            $stmt = $pdo->query("
                SELECT id, user_id, name, username, email, role, status, created_at 
                FROM admin_user 
                ORDER BY created_at ASC
            ");
            $admins = $stmt->fetchAll();
            
            echo "<h2>📊 Admin Accounts</h2>";
            echo "<table>";
            echo "<tr>";
            echo "<th>ID</th><th>User ID</th><th>Name</th><th>Username</th><th>Email</th><th>Role</th><th>Status</th>";
            echo "</tr>";
            
            foreach ($admins as $admin) {
                $roleBadge = '';
                switch($admin['role']) {
                    case 'super_admin':
                        $roleBadge = '<span class="badge badge-danger">Super Admin</span>';
                        break;
                    case 'admin':
                        $roleBadge = '<span class="badge badge-info">Admin</span>';
                        break;
                    case 'staff':
                        $roleBadge = '<span class="badge badge-warning">Staff</span>';
                        break;
                }
                
                $statusBadge = '';
                switch($admin['status']) {
                    case 'active':
                        $statusBadge = '<span class="badge badge-success">Active</span>';
                        break;
                    case 'pending_approval':
                        $statusBadge = '<span class="badge badge-warning">Pending</span>';
                        break;
                    case 'inactive':
                        $statusBadge = '<span class="badge badge-danger">Inactive</span>';
                        break;
                }
                
                echo "<tr>";
                echo "<td>{$admin['id']}</td>";
                echo "<td>{$admin['user_id']}</td>";
                echo "<td>" . htmlspecialchars($admin['name']) . "</td>";
                echo "<td>" . htmlspecialchars($admin['username'] ?? '-') . "</td>";
                echo "<td>" . htmlspecialchars($admin['email']) . "</td>";
                echo "<td>{$roleBadge}</td>";
                echo "<td>{$statusBadge}</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
        
        // Show statistics
        $stmt = $pdo->query("SELECT role, COUNT(*) as count FROM admin_user GROUP BY role");
        $roleStats = $stmt->fetchAll();
        
        $stmt = $pdo->query("SELECT status, COUNT(*) as count FROM admin_user GROUP BY status");
        $statusStats = $stmt->fetchAll();
        
        echo "<h2>📈 Statistics</h2>";
        echo "<table>";
        echo "<tr><th>Role</th><th>Count</th></tr>";
        foreach ($roleStats as $stat) {
            echo "<tr><td>{$stat['role']}</td><td>{$stat['count']}</td></tr>";
        }
        echo "</table>";
        
        echo "<table style='margin-top: 1rem;'>";
        echo "<tr><th>Status</th><th>Count</th></tr>";
        foreach ($statusStats as $stat) {
            echo "<tr><td>{$stat['status']}</td><td>{$stat['count']}</td></tr>";
        }
        echo "</table>";
        
    } catch (PDOException $e) {
        echo "<div class='error'>Error verifying table: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
    echo "</div>";
    
    echo "<div class='success'>";
    echo "<strong>✅ Database Setup Complete!</strong><br><br>";
    echo "The admin_user table has been created and configured. You can now:<br>";
    echo "• Log in with admin accounts (they will authenticate from admin_user table)<br>";
    echo "• Create new admin accounts (super admin only)<br>";
    echo "• Manage admin approvals<br>";
    echo "<br>";
    echo "<strong>Next Steps:</strong><br>";
    echo "1. Test admin login to verify it works with admin_user table<br>";
    echo "2. Create new admin accounts using the create-admin.php page<br>";
    echo "3. Review admin accounts and roles<br>";
    echo "</div>";
    
} catch (PDOException $e) {
    echo "<div class='error'>";
    echo "<strong>❌ Database Error:</strong><br>";
    echo htmlspecialchars($e->getMessage());
    echo "<br><br>";
    echo "<strong>Error Code:</strong> " . $e->getCode() . "<br>";
    echo "<strong>SQL State:</strong> " . ($e->errorInfo[0] ?? 'N/A');
    echo "</div>";
} catch (Exception $e) {
    echo "<div class='error'>";
    echo "<strong>❌ Error:</strong><br>";
    echo htmlspecialchars($e->getMessage());
    echo "</div>";
}

?>
    </div>
</body>
</html>

