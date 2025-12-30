<?php
/**
 * Check MySQL Status on SSH Server
 * This script helps diagnose MySQL connection issues on the remote server
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: text/html; charset=utf-8');

echo "<h1>MySQL Status Check on SSH Server</h1>";
echo "<style>body { font-family: monospace; padding: 20px; } .success { color: green; } .error { color: red; } .info { color: blue; } pre { background: #f5f5f5; padding: 10px; }</style>";

echo "<h2>Server Information</h2>";
echo "<pre>";
echo "Server Name: " . ($_SERVER['SERVER_NAME'] ?? 'unknown') . "\n";
echo "HTTP Host: " . ($_SERVER['HTTP_HOST'] ?? 'unknown') . "\n";
echo "Server Address: " . ($_SERVER['SERVER_ADDR'] ?? 'unknown') . "\n";
echo "Document Root: " . ($_SERVER['DOCUMENT_ROOT'] ?? 'unknown') . "\n";
echo "</pre>";

echo "<h2>Testing MySQL Connection</h2>";

$configs = [
    ['name' => 'localhost', 'host' => 'localhost', 'port' => 3306],
    ['name' => '127.0.0.1', 'host' => '127.0.0.1', 'port' => 3306],
];

$user = 'root';
$pass = 'YsqnXk6q#145';
$dbName = 'emer_comm_test';

$connected = false;

foreach ($configs as $config) {
    echo "<h3>Testing: {$config['name']}:{$config['port']}</h3>";
    
    try {
        $dsn = "mysql:host={$config['host']};port={$config['port']};charset=utf8mb4";
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 5
        ]);
        
        echo "<p class='success'>✓ Connected to MySQL server</p>";
        
        // Check if database exists
        $stmt = $pdo->query("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '$dbName'");
        if ($stmt->fetch()) {
            echo "<p class='success'>✓ Database '$dbName' exists</p>";
            
            // Connect to database
            $dsn = "mysql:host={$config['host']};port={$config['port']};dbname=$dbName;charset=utf8mb4";
            $pdo = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_TIMEOUT => 5
            ]);
            
            echo "<p class='success'>✓ Connected to database '$dbName'</p>";
            
            // Check admin_user table
            $stmt = $pdo->query("SHOW TABLES LIKE 'admin_user'");
            if ($stmt->rowCount() > 0) {
                echo "<p class='success'>✓ admin_user table exists</p>";
                
                // Check for the account
                $stmt = $pdo->prepare("SELECT id, name, email, role, status FROM admin_user WHERE email = ?");
                $stmt->execute(['joecel519@gmail.com']);
                $admin = $stmt->fetch();
                
                if ($admin) {
                    echo "<p class='success'>✓ Admin account found</p>";
                    echo "<pre>";
                    print_r($admin);
                    echo "</pre>";
                } else {
                    echo "<p class='error'>✗ Admin account NOT FOUND</p>";
                }
            } else {
                echo "<p class='info'>⚠ admin_user table does not exist</p>";
            }
            
            $connected = true;
            break;
        } else {
            echo "<p class='error'>✗ Database '$dbName' does NOT exist</p>";
            echo "<p class='info'>You may need to create the database first.</p>";
        }
        
    } catch (PDOException $e) {
        echo "<p class='error'>✗ Connection failed</p>";
        echo "<p class='error'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
        
        if (strpos($e->getMessage(), 'Connection refused') !== false || strpos($e->getMessage(), 'No connection') !== false) {
            echo "<p class='info'><strong>💡 Solution:</strong> MySQL service is not running on the server.</p>";
            echo "<p class='info'>On the SSH server, run: <code>sudo systemctl status mysql</code> or <code>sudo service mysql status</code></p>";
            echo "<p class='info'>To start MySQL: <code>sudo systemctl start mysql</code> or <code>sudo service mysql start</code></p>";
        } elseif (strpos($e->getMessage(), 'Access denied') !== false) {
            echo "<p class='info'><strong>💡 Solution:</strong> Wrong username or password. Check your config.local.php file.</p>";
        }
    }
}

if (!$connected) {
    echo "<hr>";
    echo "<h2>Next Steps</h2>";
    echo "<ol>";
    echo "<li><strong>Check if MySQL is running:</strong><br>";
    echo "   SSH into your server and run:<br>";
    echo "   <code>sudo systemctl status mysql</code><br>";
    echo "   or<br>";
    echo "   <code>sudo service mysql status</code></li>";
    echo "<li><strong>If MySQL is not running, start it:</strong><br>";
    echo "   <code>sudo systemctl start mysql</code><br>";
    echo "   or<br>";
    echo "   <code>sudo service mysql start</code></li>";
    echo "<li><strong>Check MySQL port:</strong><br>";
    echo "   <code>sudo netstat -tlnp | grep 3306</code></li>";
    echo "<li><strong>Check MySQL error log:</strong><br>";
    echo "   <code>sudo tail -f /var/log/mysql/error.log</code></li>";
    echo "</ol>";
} else {
    echo "<hr>";
    echo "<h2>✓ Connection Successful!</h2>";
    echo "<p><a href='check-and-update-password.php'>Update Password</a> | <a href='../login.php'>Back to Login</a></p>";
}

echo "<hr>";
echo "<p><a href='test-db-direct.php'>Back to Database Test</a></p>";
?>

