<?php
/**
 * Fix Users Table - Add Missing Columns
 * 
 * This script adds any missing columns to the users table that are required
 * for email OTP signup and other features.
 * 
 * Usage: Visit this file in your browser or run via command line
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

// Define all columns that might be needed
$columnsToAdd = [
    'email' => "VARCHAR(255) DEFAULT NULL COMMENT 'Email address'",
    'phone' => "VARCHAR(20) DEFAULT NULL COMMENT 'Mobile phone number'",
    'google_id' => "VARCHAR(255) NULL UNIQUE COMMENT 'Google OAuth user ID'",
    'barangay' => "VARCHAR(100) DEFAULT NULL COMMENT 'Barangay name'",
    'house_number' => "VARCHAR(50) DEFAULT NULL COMMENT 'House number'",
    'street' => "VARCHAR(255) DEFAULT NULL COMMENT 'Street name'",
    'district' => "VARCHAR(50) DEFAULT NULL COMMENT 'District name'",
    'nationality' => "VARCHAR(100) DEFAULT NULL COMMENT 'Nationality'",
    'address' => "VARCHAR(500) DEFAULT NULL COMMENT 'Full address'",
    'email_verified' => "TINYINT(1) DEFAULT 0 COMMENT 'Email verification status'",
    'verification_date' => "DATETIME DEFAULT NULL COMMENT 'Email verification date'"
];

$results = [];
$errors = [];

// Check and add each column
foreach ($columnsToAdd as $columnName => $columnDefinition) {
    try {
        // Check if column exists using INFORMATION_SCHEMA (compatible with MariaDB/MySQL)
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
                               WHERE TABLE_SCHEMA = DATABASE() 
                               AND TABLE_NAME = 'users' 
                               AND COLUMN_NAME = ?");
        $stmt->execute([$columnName]);
        $columnExists = $stmt->fetchColumn() > 0;
        
        if (!$columnExists) {
            try {
                $alterSql = "ALTER TABLE `users` ADD COLUMN `$columnName` $columnDefinition";
                $pdo->exec($alterSql);
                $results[] = "✓ Added column: $columnName";
                error_log("Added column $columnName to users table");
            } catch (PDOException $e) {
                $errorMsg = "Could not add column $columnName: " . $e->getMessage();
                $errors[] = $errorMsg;
                error_log($errorMsg);
            }
        } else {
            $results[] = "✓ Column already exists: $columnName";
        }
    } catch (PDOException $e) {
        $errorMsg = "Error checking column $columnName: " . $e->getMessage();
        $errors[] = $errorMsg;
        error_log($errorMsg);
    }
}

// Output results
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fix Users Table</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
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
        h1 {
            color: #333;
            margin-top: 0;
        }
        .success {
            background: #e8f5e9;
            border-left: 4px solid #4CAF50;
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
        .info {
            background: #e3f2fd;
            border-left: 4px solid #2196F3;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        ul {
            margin: 10px 0;
            padding-left: 20px;
        }
        li {
            margin: 5px 0;
            font-family: 'Courier New', monospace;
            font-size: 14px;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background: #2196F3;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            margin-top: 20px;
        }
        .btn:hover {
            background: #1976D2;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Fix Users Table</h1>
        
        <div class="info">
            <strong>Purpose:</strong> This script adds missing columns to the users table that are required for email OTP signup, Google OAuth, and other features.
        </div>

        <?php if (!empty($results)): ?>
            <h2>Results:</h2>
            <div class="success">
                <ul>
                    <?php foreach ($results as $result): ?>
                        <li><?php echo htmlspecialchars($result); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <h2>Errors:</h2>
            <div class="error">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if (empty($errors) && !empty($results)): ?>
            <div class="success">
                <strong>✓ Success!</strong> All required columns have been added to the users table. You can now use email OTP signup and Google OAuth login.
            </div>
        <?php endif; ?>

        <div style="margin-top: 30px;">
            <a href="../login.php" class="btn">← Back to Login</a>
            <a href="javascript:location.reload()" class="btn" style="background: #4CAF50;">🔄 Run Again</a>
        </div>

        <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd; color: #666; font-size: 12px;">
            <p><strong>Columns checked/added:</strong></p>
            <ul style="font-size: 11px;">
                <?php foreach (array_keys($columnsToAdd) as $col): ?>
                    <li><?php echo htmlspecialchars($col); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</body>
</html>
