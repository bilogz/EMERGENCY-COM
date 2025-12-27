<?php
/**
 * Simple MySQL Connection Checker
 * Tests different common configurations
 */

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>MySQL Connection Checker</title>
    <style>
        body { font-family: Arial; padding: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        .warning { color: orange; font-weight: bold; }
        .test { margin: 15px 0; padding: 15px; background: #f9f9f9; border-left: 4px solid #ddd; }
        pre { background: #222; color: #0f0; padding: 10px; overflow-x: auto; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 MySQL Connection Diagnostic</h1>
        
        <?php
        // Test configurations
        $configs = [
            ['host' => '127.0.0.1', 'port' => 3306, 'name' => 'Standard (Port 3306)'],
            ['host' => '127.0.0.1', 'port' => 3000, 'name' => 'Alternate (Port 3000)'],
            ['host' => 'localhost', 'port' => 3306, 'name' => 'Localhost (Port 3306)'],
            ['host' => 'localhost', 'port' => 3307, 'name' => 'Alternate (Port 3307)'],
        ];
        
        $db = 'emergency_comm_db';
        $user = 'root';
        $pass = '';
        
        $workingConfig = null;
        
        echo "<h2>Testing MySQL Connections...</h2>";
        
        foreach ($configs as $config) {
            echo "<div class='test'>";
            echo "<h3>Testing: {$config['name']}</h3>";
            echo "<p>Host: {$config['host']} | Port: {$config['port']}</p>";
            
            try {
                $dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$db};charset=utf8mb4";
                $pdo = new PDO($dsn, $user, $pass, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]);
                
                // Test query
                $stmt = $pdo->query("SELECT 1 as test");
                $result = $stmt->fetch();
                
                if ($result) {
                    echo "<p class='success'>✅ CONNECTION SUCCESSFUL!</p>";
                    $workingConfig = $config;
                    
                    // Check tables
                    $stmt = $pdo->query("SHOW TABLES");
                    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
                    echo "<p><strong>Tables found:</strong> " . count($tables) . "</p>";
                    if (count($tables) > 0) {
                        echo "<pre>" . implode("\n", $tables) . "</pre>";
                    }
                    
                    break; // Found working config
                }
            } catch (PDOException $e) {
                echo "<p class='error'>❌ FAILED</p>";
                echo "<p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
            }
            
            echo "</div>";
        }
        
        echo "<hr>";
        
        if ($workingConfig) {
            echo "<div style='background: #d4edda; padding: 20px; border-radius: 5px; margin: 20px 0;'>";
            echo "<h2 class='success'>✅ Solution Found!</h2>";
            echo "<p>Update your <code>db_connect.php</code> with these settings:</p>";
            echo "<pre>";
            echo "\$host = '{$workingConfig['host']}';\n";
            echo "\$port = {$workingConfig['port']};\n";
            echo "\$db   = '{$db}';\n";
            echo "\$user = '{$user}';\n";
            echo "\$pass = '';\n";
            echo "</pre>";
            echo "</div>";
        } else {
            echo "<div style='background: #f8d7da; padding: 20px; border-radius: 5px; margin: 20px 0;'>";
            echo "<h2 class='error'>❌ No Working Connection Found</h2>";
            echo "<h3>Please check:</h3>";
            echo "<ol>";
            echo "<li><strong>Is MySQL running?</strong> Check XAMPP Control Panel</li>";
            echo "<li><strong>Does the database exist?</strong> Open phpMyAdmin and look for 'emergency_comm_db'</li>";
            echo "<li><strong>Are credentials correct?</strong> Default is usually user='root' pass=''</li>";
            echo "</ol>";
            
            echo "<h3>How to fix:</h3>";
            echo "<ol>";
            echo "<li>Open <strong>XAMPP Control Panel</strong></li>";
            echo "<li>Make sure <strong>MySQL</strong> is started (green)</li>";
            echo "<li>Click <strong>Admin</strong> next to MySQL to open phpMyAdmin</li>";
            echo "<li>Check if database <code>emergency_comm_db</code> exists</li>";
            echo "<li>If not, create it: <code>CREATE DATABASE emergency_comm_db;</code></li>";
            echo "</ol>";
            echo "</div>";
        }
        
        // Port scanning
        echo "<h2>Port Scan Results</h2>";
        $portsToCheck = [3000, 3306, 3307, 3308];
        foreach ($portsToCheck as $port) {
            $connection = @fsockopen('127.0.0.1', $port, $errno, $errstr, 1);
            if ($connection) {
                echo "<p class='success'>✅ Port $port is OPEN</p>";
                fclose($connection);
            } else {
                echo "<p class='error'>❌ Port $port is CLOSED</p>";
            }
        }
        ?>
        
        <hr>
        <p><small>Diagnostic completed at <?php echo date('Y-m-d H:i:s'); ?></small></p>
    </div>
</body>
</html>



