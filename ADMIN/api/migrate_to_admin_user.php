<?php
/**
 * Migration Script: Create admin_user table and migrate existing admin accounts
 * Run this script once to set up the admin_user table
 */

require_once __DIR__ . '/db_connect.php';

header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html>
<html>
<head>
    <title>Admin User Table Migration</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 2rem; max-width: 800px; margin: 0 auto; }
        .success { color: #28a745; background: #d4edda; padding: 1rem; border-radius: 5px; margin: 1rem 0; }
        .error { color: #dc3545; background: #f8d7da; padding: 1rem; border-radius: 5px; margin: 1rem 0; }
        .info { color: #004085; background: #d1ecf1; padding: 1rem; border-radius: 5px; margin: 1rem 0; }
        pre { background: #f4f4f4; padding: 1rem; border-radius: 5px; overflow-x: auto; }
    </style>
</head>
<body>
    <h1>Admin User Table Migration</h1>
";

if ($pdo === null) {
    echo "<div class='error'>Database connection failed. Please check your database configuration.</div>";
    echo "</body></html>";
    exit();
}

try {
    echo "<div class='info'>Starting migration...</div>";
    
    // Read and execute the SQL file
    $sqlFile = __DIR__ . '/create_admin_user_table.sql';
    
    if (!file_exists($sqlFile)) {
        throw new Exception("SQL file not found: {$sqlFile}");
    }
    
    $sql = file_get_contents($sqlFile);
    
    if (empty($sql)) {
        throw new Exception("SQL file is empty");
    }
    
    // Split SQL into individual statements
    $statements = array_filter(
        array_map('trim', explode(';', $sql)),
        function($stmt) {
            return !empty($stmt) && !preg_match('/^\s*--/', $stmt);
        }
    );
    
    $executed = 0;
    $errors = [];
    
    foreach ($statements as $statement) {
        try {
            // Skip comments and empty statements
            if (empty(trim($statement)) || preg_match('/^\s*--/', $statement)) {
                continue;
            }
            
            $pdo->exec($statement);
            $executed++;
        } catch (PDOException $e) {
            // Ignore "table already exists" errors
            if (strpos($e->getMessage(), 'already exists') === false) {
                $errors[] = $e->getMessage();
            }
        }
    }
    
    echo "<div class='success'>✓ Migration completed successfully!</div>";
    echo "<p>Executed {$executed} SQL statements.</p>";
    
    // Verify the table was created
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM admin_user");
        $result = $stmt->fetch();
        $adminCount = $result['count'];
        
        echo "<div class='info'>";
        echo "<strong>Verification:</strong><br>";
        echo "✓ admin_user table exists<br>";
        echo "✓ Found {$adminCount} admin account(s) in admin_user table";
        echo "</div>";
        
        // Show admin accounts
        if ($adminCount > 0) {
            $stmt = $pdo->query("SELECT id, name, email, role, status FROM admin_user ORDER BY created_at ASC");
            $admins = $stmt->fetchAll();
            
            echo "<h2>Admin Accounts:</h2>";
            echo "<table border='1' cellpadding='10' style='border-collapse: collapse; width: 100%;'>";
            echo "<tr><th>ID</th><th>Name</th><th>Email</th><th>Role</th><th>Status</th></tr>";
            foreach ($admins as $admin) {
                echo "<tr>";
                echo "<td>{$admin['id']}</td>";
                echo "<td>{$admin['name']}</td>";
                echo "<td>{$admin['email']}</td>";
                echo "<td>{$admin['role']}</td>";
                echo "<td>{$admin['status']}</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
        
    } catch (PDOException $e) {
        echo "<div class='error'>Error verifying table: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
    
    if (!empty($errors)) {
        echo "<div class='error'>";
        echo "<strong>Warnings:</strong><br>";
        foreach ($errors as $error) {
            echo htmlspecialchars($error) . "<br>";
        }
        echo "</div>";
    }
    
    echo "<div class='success'>";
    echo "<strong>Migration Complete!</strong><br>";
    echo "You can now use the admin account creation feature. Only super administrators can create new admin accounts.";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='error'>";
    echo "<strong>Migration Error:</strong><br>";
    echo htmlspecialchars($e->getMessage());
    echo "</div>";
}

echo "</body></html>";
?>

