<?php
/**
 * Database Duplication Script
 * Creates a copy of emer_comm_test database
 * Usage: Run this script to create a backup/duplicate of the database
 */

header('Content-Type: text/html; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', 1);

$host = 'alertaraqc.com';
$port = 3306;
$user = 'root';
$pass = 'YsqnXk6q#145';
$sourceDb = 'emer_comm_test';
$targetDb = 'emer_comm_test_backup';
$charset = 'utf8mb4';

echo "<h1>Database Duplication Script</h1>";
echo "<pre>";

try {
    // Connect to MySQL server
    $dsn = "mysql:host=$host;port=$port;charset=$charset";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    
    $pdo = new PDO($dsn, $user, $pass, $options);
    echo "✓ Connected to MySQL server\n";
    
    // Check if source database exists
    $stmt = $pdo->query("SHOW DATABASES LIKE '$sourceDb'");
    if ($stmt->rowCount() === 0) {
        throw new Exception("Source database '$sourceDb' does not exist. Please run setup_remote_database.php first.");
    }
    echo "✓ Source database '$sourceDb' found\n";
    
    // Drop target database if it exists
    $pdo->exec("DROP DATABASE IF EXISTS `$targetDb`");
    echo "✓ Dropped existing backup database (if any)\n";
    
    // Create target database
    $pdo->exec("CREATE DATABASE `$targetDb` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "✓ Created target database '$targetDb'\n";
    
    // Get all tables from source database
    $pdo->exec("USE `$sourceDb`");
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "✓ Found " . count($tables) . " tables to copy\n";
    
    // Copy each table
    $copied = 0;
    foreach ($tables as $table) {
        try {
            // Get table structure
            $stmt = $pdo->query("SHOW CREATE TABLE `$table`");
            $createTable = $stmt->fetch();
            $createSql = $createTable['Create Table'];
            
            // Create table in target database
            $pdo->exec("USE `$targetDb`");
            $pdo->exec($createSql);
            
            // Copy data
            $pdo->exec("USE `$sourceDb`");
            $stmt = $pdo->query("SELECT * FROM `$table`");
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (count($rows) > 0) {
                // Get column names
                $columns = array_keys($rows[0]);
                $columnList = '`' . implode('`, `', $columns) . '`';
                $placeholders = ':' . implode(', :', $columns);
                
                $pdo->exec("USE `$targetDb`");
                $insertSql = "INSERT INTO `$table` ($columnList) VALUES ($placeholders)";
                $insertStmt = $pdo->prepare($insertSql);
                
                foreach ($rows as $row) {
                    $insertStmt->execute($row);
                }
            }
            
            $copied++;
            echo "  ✓ Copied table: $table (" . count($rows) . " rows)\n";
            
        } catch (PDOException $e) {
            echo "  ✗ Error copying table $table: " . $e->getMessage() . "\n";
        }
    }
    
    echo "\n✓ Database duplication complete!\n";
    echo "✓ Copied $copied tables from '$sourceDb' to '$targetDb'\n";
    
    // Verify target database
    $pdo->exec("USE `$targetDb`");
    $stmt = $pdo->query("SHOW TABLES");
    $targetTables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "\n✓ Target database '$targetDb' contains " . count($targetTables) . " tables:\n";
    foreach ($targetTables as $table) {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM `$table`");
        $count = $stmt->fetch()['count'];
        echo "  - $table ($count rows)\n";
    }
    
} catch (PDOException $e) {
    echo "\n✗ Database Error: " . $e->getMessage() . "\n";
    echo "Error Code: " . $e->getCode() . "\n";
} catch (Exception $e) {
    echo "\n✗ Error: " . $e->getMessage() . "\n";
}

echo "</pre>";
?>

