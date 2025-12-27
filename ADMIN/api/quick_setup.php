<?php
/**
 * Quick Database Setup - Creates database and all tables
 * Run this if tables are missing
 */

header('Content-Type: text/html; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', 1);

$user = 'root';
$pass = 'YsqnXk6q#145';
$dbName = 'emer_comm_test';
$charset = 'utf8mb4';

echo "<h1>Quick Database Setup</h1>";
echo "<pre>";

// Try multiple connection methods
$connectionAttempts = [
    ['host' => 'localhost', 'port' => 3306],
    ['host' => '127.0.0.1', 'port' => 3306],
    ['host' => 'alertaraqc.com', 'port' => 3306],
];

$pdo = null;

foreach ($connectionAttempts as $attempt) {
    try {
        $dsn = "mysql:host={$attempt['host']};port={$attempt['port']};charset=$charset";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::ATTR_TIMEOUT            => 10,
        ];
        
        $pdo = new PDO($dsn, $user, $pass, $options);
        echo "✓ Connected to MySQL server at {$attempt['host']}:{$attempt['port']}\n";
        break;
    } catch (PDOException $e) {
        echo "✗ Failed to connect to {$attempt['host']}:{$attempt['port']}\n";
    }
}

if ($pdo === null) {
    echo "\n✗ Could not connect to MySQL server.\n";
    echo "Please check your MySQL server is running.\n";
    echo "</pre>";
    exit;
}

try {
    // Create database if it doesn't exist
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "✓ Database '$dbName' created or already exists\n";
    
    // Switch to the database
    $pdo->exec("USE `$dbName`");
    echo "✓ Switched to database '$dbName'\n";
    
    // Read schema file
    $schemaFile = __DIR__ . '/database_schema.sql';
    if (!file_exists($schemaFile)) {
        throw new Exception("Schema file not found: $schemaFile");
    }
    
    $schema = file_get_contents($schemaFile);
    
    // Remove comments and split by semicolons
    $statements = array_filter(
        array_map('trim', explode(';', $schema)),
        function($stmt) {
            $stmt = trim($stmt);
            return !empty($stmt) && 
                   !preg_match('/^\s*--/', $stmt) && 
                   !preg_match('/^\s*\/\*/', $stmt) &&
                   !preg_match('/^\s*\*/', $stmt);
        }
    );
    
    $executed = 0;
    $errors = [];
    
    foreach ($statements as $statement) {
        $statement = trim($statement);
        if (empty($statement)) continue;
        
        try {
            $pdo->exec($statement);
            $executed++;
        } catch (PDOException $e) {
            // Ignore "already exists" errors
            if (strpos($e->getMessage(), 'already exists') === false && 
                strpos($e->getMessage(), 'Duplicate') === false) {
                $errors[] = [
                    'statement' => substr($statement, 0, 100),
                    'error' => $e->getMessage()
                ];
            }
        }
    }
    
    echo "✓ Executed $executed SQL statements\n";
    
    if (!empty($errors)) {
        echo "\n⚠ Warnings:\n";
        foreach ($errors as $error) {
            echo "  - " . $error['error'] . "\n";
        }
    }
    
    // Verify users table exists
    echo "\n--- Verifying Tables ---\n";
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($tables)) {
        echo "⚠ No tables found! Creating essential tables manually...\n";
        
        // Create users table manually
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL COMMENT 'Full name of the user',
                username VARCHAR(100) DEFAULT NULL COMMENT 'Username for login',
                email VARCHAR(255) DEFAULT NULL COMMENT 'Email address (optional)',
                phone VARCHAR(20) DEFAULT NULL COMMENT 'Mobile phone number',
                password VARCHAR(255) DEFAULT NULL COMMENT 'Hashed password',
                status VARCHAR(20) DEFAULT 'active' COMMENT 'active, inactive, suspended, pending_approval',
                user_type VARCHAR(20) DEFAULT 'citizen' COMMENT 'citizen, admin, guest',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_email (email),
                INDEX idx_phone (phone),
                INDEX idx_username (username),
                INDEX idx_status (status)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        echo "✓ Created users table\n";
        
        // Create other essential tables
        $essentialTables = [
            "CREATE TABLE IF NOT EXISTS notification_logs (
                id INT AUTO_INCREMENT PRIMARY KEY,
                channel VARCHAR(50) NOT NULL COMMENT 'sms, email, pa',
                message TEXT NOT NULL,
                recipient VARCHAR(255) DEFAULT NULL,
                recipients TEXT DEFAULT NULL COMMENT 'Comma-separated list of recipients',
                priority VARCHAR(20) DEFAULT 'medium' COMMENT 'low, medium, high, critical',
                status VARCHAR(20) DEFAULT 'pending' COMMENT 'pending, success, failed',
                sent_at DATETIME NOT NULL,
                sent_by VARCHAR(100) DEFAULT 'system',
                ip_address VARCHAR(45) DEFAULT NULL,
                response TEXT DEFAULT NULL,
                error_message TEXT DEFAULT NULL,
                INDEX idx_channel (channel),
                INDEX idx_status (status),
                INDEX idx_sent_at (sent_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            
            "CREATE TABLE IF NOT EXISTS alert_categories (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL UNIQUE,
                icon VARCHAR(50) DEFAULT 'fa-exclamation-triangle',
                description TEXT DEFAULT NULL,
                color VARCHAR(7) DEFAULT '#4c8a89',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_name (name)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            
            "CREATE TABLE IF NOT EXISTS alerts (
                id INT AUTO_INCREMENT PRIMARY KEY,
                category_id INT DEFAULT NULL,
                title VARCHAR(255) NOT NULL,
                message TEXT NOT NULL,
                content TEXT DEFAULT NULL,
                status VARCHAR(20) DEFAULT 'active',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_category (category_id),
                INDEX idx_status (status)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            
            "CREATE TABLE IF NOT EXISTS conversations (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                status VARCHAR(20) DEFAULT 'active' COMMENT 'active, closed, archived',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_user (user_id),
                INDEX idx_status (status)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            
            "CREATE TABLE IF NOT EXISTS messages (
                id INT AUTO_INCREMENT PRIMARY KEY,
                conversation_id INT NOT NULL,
                message TEXT NOT NULL,
                sender_type VARCHAR(20) NOT NULL COMMENT 'admin, citizen, system',
                sent_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                read_at DATETIME DEFAULT NULL,
                INDEX idx_conversation (conversation_id),
                INDEX idx_sent_at (sent_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            
            "CREATE TABLE IF NOT EXISTS integration_settings (
                id INT AUTO_INCREMENT PRIMARY KEY,
                source VARCHAR(50) NOT NULL UNIQUE COMMENT 'pagasa, phivolcs',
                enabled TINYINT(1) DEFAULT 0,
                api_key VARCHAR(255) DEFAULT NULL,
                api_url VARCHAR(255) DEFAULT NULL,
                last_sync DATETIME DEFAULT NULL,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_source (source)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            
            "CREATE TABLE IF NOT EXISTS warning_settings (
                id INT AUTO_INCREMENT PRIMARY KEY,
                sync_interval INT DEFAULT 15 COMMENT 'Minutes',
                auto_publish TINYINT(1) DEFAULT 0,
                notification_channels TEXT DEFAULT NULL COMMENT 'Comma-separated: sms,email,pa',
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            
            "CREATE TABLE IF NOT EXISTS automated_warnings (
                id INT AUTO_INCREMENT PRIMARY KEY,
                source VARCHAR(50) NOT NULL COMMENT 'pagasa, phivolcs',
                type VARCHAR(100) DEFAULT NULL,
                title VARCHAR(255) NOT NULL,
                content TEXT NOT NULL,
                severity VARCHAR(20) DEFAULT 'medium' COMMENT 'low, medium, high, critical',
                status VARCHAR(20) DEFAULT 'pending' COMMENT 'pending, published, archived',
                received_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                published_at DATETIME DEFAULT NULL,
                INDEX idx_source (source),
                INDEX idx_status (status),
                INDEX idx_received_at (received_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            
            "CREATE TABLE IF NOT EXISTS alert_translations (
                id INT AUTO_INCREMENT PRIMARY KEY,
                alert_id INT NOT NULL,
                target_language VARCHAR(10) NOT NULL COMMENT 'en, tl, ceb, etc.',
                translated_title VARCHAR(255) NOT NULL,
                translated_content TEXT NOT NULL,
                status VARCHAR(20) DEFAULT 'active',
                translated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_language (target_language),
                INDEX idx_status (status)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            
            "CREATE TABLE IF NOT EXISTS subscriptions (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                categories TEXT DEFAULT NULL COMMENT 'Comma-separated: weather,earthquake,bomb,fire,general',
                channels TEXT DEFAULT NULL COMMENT 'Comma-separated: sms,email,push',
                preferred_language VARCHAR(10) DEFAULT 'en',
                status VARCHAR(20) DEFAULT 'active' COMMENT 'active, inactive, suspended',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_user (user_id),
                INDEX idx_status (status)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        ];
        
        foreach ($essentialTables as $tableSql) {
            try {
                $pdo->exec($tableSql);
            } catch (PDOException $e) {
                echo "⚠ Error creating table: " . $e->getMessage() . "\n";
            }
        }
        
        // Insert default data
        try {
            $pdo->exec("
                INSERT INTO alert_categories (name, icon, description, color) VALUES
                ('Weather', 'fa-cloud-rain', 'Weather-related alerts including storms, floods, and typhoons', '#3498db'),
                ('Earthquake', 'fa-mountain', 'Seismic activity and earthquake warnings', '#e74c3c'),
                ('Bomb Threat', 'fa-bomb', 'Security threats and bomb alerts', '#c0392b'),
                ('Fire', 'fa-fire', 'Fire emergencies and fire safety alerts', '#e67e22'),
                ('General', 'fa-exclamation-triangle', 'General emergency alerts and announcements', '#95a5a6')
                ON DUPLICATE KEY UPDATE name=name
            ");
            echo "✓ Inserted default alert categories\n";
        } catch (PDOException $e) {
            // Ignore duplicate errors
        }
        
        try {
            $pdo->exec("
                INSERT INTO integration_settings (source, enabled) VALUES
                ('pagasa', 0),
                ('phivolcs', 0)
                ON DUPLICATE KEY UPDATE source=source
            ");
            echo "✓ Inserted default integration settings\n";
        } catch (PDOException $e) {
            // Ignore duplicate errors
        }
        
        try {
            $pdo->exec("
                INSERT INTO warning_settings (sync_interval, auto_publish, notification_channels) VALUES
                (15, 0, 'sms,email')
                ON DUPLICATE KEY UPDATE id=id
            ");
            echo "✓ Inserted default warning settings\n";
        } catch (PDOException $e) {
            // Ignore duplicate errors
        }
    }
    
    // List all tables
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "\n✓ Database setup complete!\n";
    echo "\nTables in database:\n";
    foreach ($tables as $table) {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM `$table`");
        $count = $stmt->fetch()['count'];
        echo "  ✓ $table ($count rows)\n";
    }
    
    // Verify users table specifically
    echo "\n--- Users Table Verification ---\n";
    $stmt = $pdo->query("DESCRIBE users");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Users table columns:\n";
    foreach ($columns as $column) {
        echo "  - " . $column['Field'] . " (" . $column['Type'] . ")\n";
    }
    
    echo "\n✅ Database is ready! You can now use create-admin.php\n";
    
} catch (PDOException $e) {
    echo "\n✗ Database Error: " . $e->getMessage() . "\n";
    echo "Error Code: " . $e->getCode() . "\n";
} catch (Exception $e) {
    echo "\n✗ Error: " . $e->getMessage() . "\n";
}

echo "</pre>";
?>

