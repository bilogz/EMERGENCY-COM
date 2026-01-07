<?php
/**
 * Check Users Table Structure
 * Diagnostic script to check the users table structure and identify issues
 */

// Include DB connection
if (file_exists(__DIR__ . '/db_connect.php')) {
    require_once __DIR__ . '/db_connect.php';
} else {
    require_once '../../ADMIN/api/db_connect.php';
}

// Check if database connection was successful
if (!isset($pdo) || $pdo === null) {
    die("Database connection failed. Please check your database configuration.");
}

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Check Users Table Structure</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            max-width: 1200px;
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
        h1 {
            color: #333;
            margin-top: 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background: #f8f9fa;
            font-weight: 600;
        }
        .warning {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin: 10px 0;
            border-radius: 4px;
        }
        .error {
            background: #ffebee;
            border-left: 4px solid #f44336;
            padding: 15px;
            margin: 10px 0;
            border-radius: 4px;
        }
        .success {
            background: #e8f5e9;
            border-left: 4px solid #4CAF50;
            padding: 15px;
            margin: 10px 0;
            border-radius: 4px;
        }
        .info {
            background: #e3f2fd;
            border-left: 4px solid #2196F3;
            padding: 15px;
            margin: 10px 0;
            border-radius: 4px;
        }
        code {
            background: #f5f5f5;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Users Table Structure Check</h1>
        
        <?php
        try {
            // Check if users table exists
            $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
            $tableExists = $stmt->rowCount() > 0;
            
            if (!$tableExists) {
                echo '<div class="error"><strong>❌ Error:</strong> The users table does not exist!</div>';
                exit;
            }
            
            echo '<div class="success"><strong>✓ Success:</strong> The users table exists.</div>';
            
            // Get table structure
            $stmt = $pdo->query("DESCRIBE users");
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo '<h2>Table Structure</h2>';
            echo '<table>';
            echo '<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>';
            
            $issues = [];
            $requiredColumns = ['name', 'email', 'phone', 'google_id'];
            
            foreach ($columns as $column) {
                $field = $column['Field'];
                $null = $column['Null'];
                $type = $column['Type'];
                $default = $column['Default'];
                
                // Check for potential issues
                if ($field === 'phone' && $null === 'NO' && $default === null) {
                    $issues[] = "Phone column is NOT NULL but has no default value. This will cause issues with Google OAuth.";
                }
                
                $rowClass = '';
                if (in_array($field, $requiredColumns)) {
                    $rowClass = ' style="background: #e8f5e9;"';
                }
                
                echo "<tr$rowClass>";
                echo '<td><strong>' . htmlspecialchars($field) . '</strong></td>';
                echo '<td>' . htmlspecialchars($type) . '</td>';
                echo '<td>' . htmlspecialchars($null) . '</td>';
                echo '<td>' . htmlspecialchars($column['Key']) . '</td>';
                echo '<td>' . ($default !== null ? htmlspecialchars($default) : '<em>NULL</em>') . '</td>';
                echo '<td>' . htmlspecialchars($column['Extra']) . '</td>';
                echo '</tr>';
            }
            echo '</table>';
            
            // Show issues
            if (!empty($issues)) {
                echo '<h2>⚠️ Potential Issues</h2>';
                foreach ($issues as $issue) {
                    echo '<div class="warning">' . htmlspecialchars($issue) . '</div>';
                }
                
                echo '<h2>🔧 Recommended Fix</h2>';
                echo '<div class="info">';
                echo '<p><strong>To fix the phone column issue, run this SQL:</strong></p>';
                echo '<code>ALTER TABLE users MODIFY COLUMN phone VARCHAR(20) DEFAULT NULL;</code>';
                echo '<p style="margin-top: 10px;">Or click the button below to fix it automatically:</p>';
                echo '<form method="POST" style="margin-top: 10px;">';
                echo '<button type="submit" name="fix_phone" style="padding: 10px 20px; background: #2196F3; color: white; border: none; border-radius: 4px; cursor: pointer;">Fix Phone Column</button>';
                echo '</form>';
                echo '</div>';
            } else {
                echo '<div class="success"><strong>✓ No issues found!</strong> The table structure looks good.</div>';
            }
            
            // Handle fix request
            if (isset($_POST['fix_phone'])) {
                try {
                    $pdo->exec("ALTER TABLE users MODIFY COLUMN phone VARCHAR(20) DEFAULT NULL");
                    echo '<div class="success"><strong>✓ Fixed!</strong> Phone column is now nullable.</div>';
                    echo '<script>setTimeout(function(){ location.reload(); }, 2000);</script>';
                } catch (PDOException $e) {
                    echo '<div class="error"><strong>❌ Error:</strong> ' . htmlspecialchars($e->getMessage()) . '</div>';
                }
            }
            
            // Check for required columns
            echo '<h2>Required Columns Check</h2>';
            $existingColumns = array_column($columns, 'Field');
            $missingColumns = [];
            
            foreach ($requiredColumns as $reqCol) {
                if (!in_array($reqCol, $existingColumns)) {
                    $missingColumns[] = $reqCol;
                }
            }
            
            if (empty($missingColumns)) {
                echo '<div class="success"><strong>✓ All required columns exist.</strong></div>';
            } else {
                echo '<div class="warning">';
                echo '<strong>⚠️ Missing columns:</strong> ' . implode(', ', $missingColumns);
                echo '<p>Run <a href="fix-users-table.php">fix-users-table.php</a> to add missing columns.</p>';
                echo '</div>';
            }
            
        } catch (PDOException $e) {
            echo '<div class="error"><strong>❌ Database Error:</strong> ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
        ?>
        
        <div style="margin-top: 30px;">
            <a href="fix-users-table.php" style="display: inline-block; padding: 10px 20px; background: #4CAF50; color: white; text-decoration: none; border-radius: 4px;">Run Fix Script</a>
            <a href="javascript:location.reload()" style="display: inline-block; padding: 10px 20px; background: #2196F3; color: white; text-decoration: none; border-radius: 4px; margin-left: 10px;">🔄 Refresh</a>
        </div>
    </div>
</body>
</html>



