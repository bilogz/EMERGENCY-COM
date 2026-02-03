<?php
// Simple health check for Socket.IO server
$host = '127.0.0.1';
$port = 3000;
$timeout = 3;

echo "Checking Socket.IO server at {$host}:{$port}...\n";

// Create socket
$socket = @fsockopen($host, $port, $errno, $errstr, $timeout);

if ($socket) {
    echo "✓ Socket.IO server is RUNNING and accepting connections\n";
    fclose($socket);
    
    // Try HTTP health check
    $healthUrl = "http://{$host}:{$port}/health";
    $context = stream_context_create([
        'http' => [
            'timeout' => 2,
            'method' => 'GET'
        ]
    ]);
    
    $response = @file_get_contents($healthUrl, false, $context);
    if ($response) {
        echo "✓ Health endpoint responded: " . trim($response) . "\n";
    } else {
        echo "⚠ Health endpoint not accessible (but socket is open)\n";
    }
} else {
    echo "✗ Socket.IO server is NOT running or not accessible\n";
    echo "  Error: $errno - $errstr\n";
    echo "\nTo start the server:\n";
    echo "  cd " . __DIR__ . "\n";
    echo "  node server.js\n";
    echo "\nOr run it in the background:\n";
    echo "  node server.js > socket.log 2>&1 &\n";
}

echo "\n---\n";
?>
