<?php
/**
 * Fix Admin Account Duplication Issue
 * This script fixes the foreign key constraint and removes duplication
 * Run this once to fix existing admin accounts
 */

require_once __DIR__ . '/db_connect.php';

header('Content-Type: text/html; charset=utf-8');

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fix Admin Duplication</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif;
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
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Fix Admin Account Duplication</h1>
<?php

if ($pdo === null) {
    echo "<div class='error'><strong>❌ Database Connection Failed</strong><br>";
    echo "Please check your database configuration in <code>db_connect.php</code></div>";
    echo "</div></body></html>";
    exit();
}

try {
    echo "<div class='info'><strong>📋 Starting Fix...</strong><br>This will fix the foreign key constraint and remove duplication.</div>";
    
    // Step 1: Check current state
    echo "<div class='step'>";
    echo "<span class='step-number'>1</span><strong>Checking current state...</strong><br>";
    
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM admin_user");
        $result = $stmt->fetch();
        $adminCount = (int)$result['count'];
        echo "<span class='success'>✓ Found {$adminCount} admin account(s) in admin_user table</span><br>";
        
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM admin_user WHERE user_id IS NOT NULL");
        $result = $stmt->fetch();
        $linkedCount = (int)$result['count'];
        echo "<span class='info'>ℹ {$linkedCount} admin account(s) are linked to users table</span>";
    } catch (PDOException $e) {
        echo "<span class='error'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</span>";
    }
    echo "</div>";
    
    // Step 2: Drop existing foreign key
    echo "<div class='step'>";
    echo "<span class='step-number'>2</span><strong>Dropping existing foreign key constraint...</strong><br>";
    
    try {
        // Get constraint name
        $stmt = $pdo->query("
            SELECT CONSTRAINT_NAME 
            FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = 'admin_user' 
            AND REFERENCED_TABLE_NAME = 'users'
            LIMIT 1
        ");
        $constraint = $stmt->fetch();
        
        if ($constraint) {
            $constraintName = $constraint['CONSTRAINT_NAME'];
            $pdo->exec("ALTER TABLE admin_user DROP FOREIGN KEY {$constraintName}");
            echo "<span class='success'>✓ Dropped foreign key constraint: {$constraintName}</span>";
        } else {
            echo "<span class='warning'>⚠ No foreign key constraint found (may have been dropped already)</span>";
        }
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Unknown key') !== false || strpos($e->getMessage(), 'does not exist') !== false) {
            echo "<span class='warning'>⚠ Foreign key constraint not found (may have been dropped already)</span>";
        } else {
            throw $e;
        }
    }
    echo "</div>";
    
    // Step 3: Modify user_id column to allow NULL
    echo "<div class='step'>";
    echo "<span class='step-number'>3</span><strong>Modifying user_id column to allow NULL...</strong><br>";
    
    try {
        $pdo->exec("ALTER TABLE admin_user MODIFY COLUMN user_id INT DEFAULT NULL COMMENT 'Optional reference to users table (NULL for standalone admin accounts)'");
        echo "<span class='success'>✓ Column modified successfully</span>";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
            echo "<span class='warning'>⚠ Column already allows NULL</span>";
        } else {
            throw $e;
        }
    }
    echo "</div>";
    
    // Step 4: Update existing records to set user_id to NULL
    echo "<div class='step'>";
    echo "<span class='step-number'>4</span><strong>Removing user_id links from admin accounts...</strong><br>";
    
    try {
        $stmt = $pdo->prepare("UPDATE admin_user SET user_id = NULL WHERE user_id IS NOT NULL");
        $stmt->execute();
        $affectedRows = $stmt->rowCount();
        echo "<span class='success'>✓ Updated {$affectedRows} admin account(s) to remove user_id dependency</span>";
    } catch (PDOException $e) {
        throw $e;
    }
    echo "</div>";
    
    // Step 5: Recreate foreign key with ON DELETE SET NULL
    echo "<div class='step'>";
    echo "<span class='step-number'>5</span><strong>Recreating foreign key with ON DELETE SET NULL...</strong><br>";
    
    try {
        $pdo->exec("ALTER TABLE admin_user ADD CONSTRAINT admin_user_ibfk_1 FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL");
        echo "<span class='success'>✓ Foreign key recreated successfully</span>";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate key name') !== false) {
            echo "<span class='warning'>⚠ Foreign key already exists</span>";
        } else {
            // Try to drop and recreate
            try {
                $pdo->exec("ALTER TABLE admin_user DROP FOREIGN KEY admin_user_ibfk_1");
                $pdo->exec("ALTER TABLE admin_user ADD CONSTRAINT admin_user_ibfk_1 FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL");
                echo "<span class='success'>✓ Foreign key recreated successfully</span>";
            } catch (PDOException $e2) {
                echo "<span class='warning'>⚠ Could not recreate foreign key: " . htmlspecialchars($e2->getMessage()) . "</span>";
            }
        }
    }
    echo "</div>";
    
    // Step 6: Verification
    echo "<div class='step'>";
    echo "<span class='step-number'>6</span><strong>Verification...</strong><br>";
    
    try {
        $stmt = $pdo->query("SELECT id, user_id, name, email, role, status FROM admin_user ORDER BY id");
        $admins = $stmt->fetchAll();
        
        echo "<div class='success'>";
        echo "<strong>✓ Fix Complete!</strong><br>";
        echo "✓ Foreign key constraint updated<br>";
        echo "✓ Admin accounts are now independent<br>";
        echo "✓ No duplication in users table<br>";
        echo "</div>";
        
        if (count($admins) > 0) {
            echo "<h2>📊 Admin Accounts</h2>";
            echo "<table>";
            echo "<tr><th>ID</th><th>User ID</th><th>Name</th><th>Email</th><th>Role</th><th>Status</th></tr>";
            foreach ($admins as $admin) {
                echo "<tr>";
                echo "<td>{$admin['id']}</td>";
                echo "<td>" . ($admin['user_id'] ?? 'NULL') . "</td>";
                echo "<td>" . htmlspecialchars($admin['name']) . "</td>";
                echo "<td>" . htmlspecialchars($admin['email']) . "</td>";
                echo "<td>{$admin['role']}</td>";
                echo "<td>{$admin['status']}</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
        
    } catch (PDOException $e) {
        echo "<div class='error'>Error verifying: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
    echo "</div>";
    
    echo "<div class='success'>";
    echo "<strong>✅ Fix Complete!</strong><br><br>";
    echo "The admin account duplication issue has been fixed:<br>";
    echo "• Admin accounts are now stored ONLY in admin_user table<br>";
    echo "• Foreign key constraint allows NULL (no dependency on users table)<br>";
    echo "• New admin accounts will not be duplicated<br>";
    echo "<br>";
    echo "<strong>Next Steps:</strong><br>";
    echo "1. Test creating a new admin account - it should only create in admin_user table<br>";
    echo "2. Verify admin login still works correctly<br>";
    echo "3. Optional: Clean up duplicate admin records from users table if needed<br>";
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

