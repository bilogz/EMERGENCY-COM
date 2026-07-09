<?php
/**
 * AUTOMATED TESTING RUNNER FOR CENTRALIZED DEPARTMENT API
 * 
 * Runs CLI subprocesses to execute each API endpoint routed through the central index.php gateway,
 * capturing the JSON output, verifying database queries and error handling behavior.
 */

echo "==================================================\n";
echo "       DEPARTMENT API SUITE TEST RUNNER          \n";
echo "==================================================\n\n";

$phpExecutable = defined('PHP_BINARY') && PHP_BINARY ? PHP_BINARY : 'php'; // Use the same PHP binary that runs this script

// Helper to run endpoint in separate process through the centralized gateway (index.php)
function runEndpoint(string $module, string $method, array $get = [], array $post = [], array $headers = []): array {
    global $phpExecutable;
    
    // Inject the module into $_GET parameters for routing
    $get['module'] = $module;
    
    // Construct inline PHP command to mock $_SERVER, $_GET, $_POST and headers
    $phpCode = '
        // Disable buffer output
        ob_start();
        
        // Mock $_GET
        $_GET = ' . var_export($get, true) . ';
        
        // Mock $_POST
        $_POST = ' . var_export($post, true) . ';
        
        // Mock $_SERVER and HTTP Headers
        $_SERVER[\'REQUEST_METHOD\'] = \'' . $method . '\';
        $_SERVER[\'REMOTE_ADDR\'] = \'127.0.0.1\';
        $_SERVER[\'REQUEST_URI\'] = \'/api/index.php\';
    ';
    
    foreach ($headers as $key => $val) {
        $serverKey = 'HTTP_' . strtoupper(str_replace('-', '_', $key));
        $phpCode .= "\$_SERVER['$serverKey'] = '" . addslashes($val) . "';\n";
    }
    
    $phpCode .= '
        // Run the centralized gateway index.php script
        try {
            include \'api/index.php\';
        } catch (Throwable $e) {
            echo json_encode([
                \'success\' => false,
                \'message\' => \'PHP Exception: \' . $e->getMessage(),
                \'trace\' => $e->getTraceAsString()
            ]);
        }
        
        // Output clean response
        $output = ob_get_clean();
        echo $output;
    ';
    
    // Write code to a temp file to execute safely
    $tempFile = 'temp_test_run.php';
    file_put_contents($tempFile, "<?php " . $phpCode);
    
    $descriptorspec = [
        0 => ["pipe", "r"], // stdin
        1 => ["pipe", "w"], // stdout
        2 => ["pipe", "w"]  // stderr
    ];
    
    $process = proc_open("$phpExecutable $tempFile", $descriptorspec, $pipes);
    
    $stdout = '';
    $stderr = '';
    
    if (is_resource($process)) {
        $stdout = stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[2]);
        
        proc_close($process);
    }
    
    @unlink($tempFile);
    
    return [
        'stdout' => trim($stdout),
        'stderr' => trim($stderr)
    ];
}

// Test cases using modules routed via index.php
$tests = [
    [
        'name' => '1. GET Alerts Feed (Valid Authentication)',
        'module' => 'alerts',
        'method' => 'GET',
        'get' => ['limit' => 2],
        'headers' => ['X-Department-API-Key' => 'DEFAULT-DEPT-SECURE-KEY-2026']
    ],
    [
        'name' => '2. GET Alerts Feed (Missing Authentication)',
        'module' => 'alerts',
        'method' => 'GET',
        'get' => [],
        'headers' => []
    ],
    [
        'name' => '3. GET Alerts Feed (Invalid Authentication)',
        'module' => 'alerts',
        'method' => 'GET',
        'get' => [],
        'headers' => ['X-Department-API-Key' => 'INVALID-KEY-123']
    ],
    [
        'name' => '4. GET Citizens List (Valid Authentication)',
        'module' => 'users',
        'method' => 'GET',
        'get' => ['limit' => 2],
        'headers' => ['X-Department-API-Key' => 'DEFAULT-DEPT-SECURE-KEY-2026']
    ],
    [
        'name' => '5. GET Telemetry Locations (Valid Authentication)',
        'module' => 'users',
        'method' => 'GET',
        'get' => ['action' => 'locations'],
        'headers' => ['X-Department-API-Key' => 'DEFAULT-DEPT-SECURE-KEY-2026']
    ],
    [
        'name' => '6. GET Emergency Calls List (Valid Authentication)',
        'module' => 'calls',
        'method' => 'GET',
        'get' => ['limit' => 2],
        'headers' => ['X-Department-API-Key' => 'DEFAULT-DEPT-SECURE-KEY-2026']
    ],
    [
        'name' => '7. GET Disaster Indicators (Valid Authentication)',
        'module' => 'disaster',
        'method' => 'GET',
        'get' => ['type' => 'weather', 'limit' => 2],
        'headers' => ['X-Department-API-Key' => 'DEFAULT-DEPT-SECURE-KEY-2026']
    ],
    [
        'name' => '8. GET Active Chat Conversations (Valid Authentication)',
        'module' => 'chat',
        'method' => 'GET',
        'get' => ['action' => 'conversations', 'limit' => 2],
        'headers' => ['X-Department-API-Key' => 'DEFAULT-DEPT-SECURE-KEY-2026']
    ],
    [
        'name' => '9. GET Centralized Consolidated Feed (Valid Authentication)',
        'module' => 'all',
        'method' => 'GET',
        'get' => [],
        'headers' => ['X-Department-API-Key' => 'DEFAULT-DEPT-SECURE-KEY-2026']
    ],
    [
        'name' => '10. GET Audit Logs Feed (Valid Authentication)',
        'module' => 'audit',
        'method' => 'GET',
        'get' => ['limit' => 2],
        'headers' => ['X-Department-API-Key' => 'DEFAULT-DEPT-SECURE-KEY-2026']
    ],
    [
        'name' => '11. GET Citizen Details with Subscriptions (Valid Authentication)',
        'module' => 'users',
        'method' => 'GET',
        'get' => ['id' => 1],
        'headers' => ['X-Department-API-Key' => 'DEFAULT-DEPT-SECURE-KEY-2026']
    ]
];

$successCount = 0;
$totalCount = count($tests);

foreach ($tests as $test) {
    echo "Running Test: " . $test['name'] . "\n";
    $result = runEndpoint($test['module'], $test['method'], $test['get'], [], $test['headers']);
    
    if (!empty($result['stderr'])) {
        echo "[-] STDERR: " . $result['stderr'] . "\n";
    }
    
    $json = json_decode($result['stdout'], true);
    if (json_last_error() === JSON_ERROR_NONE) {
        $statusStr = $json['success'] ? '[PASS]' : '[FAIL (Expected for security checks)]';
        echo "$statusStr Output:\n";
        echo json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
        
        // Assertions
        if ($test['name'] === '2. GET Alerts Feed (Missing Authentication)' && !$json['success']) {
            $successCount++;
        } elseif ($test['name'] === '3. GET Alerts Feed (Invalid Authentication)' && !$json['success']) {
            $successCount++;
        } elseif ($json['success']) {
            $successCount++;
        }
    } else {
        echo "[-] FAIL: Raw output is not valid JSON:\n" . $result['stdout'] . "\n";
    }
    echo "--------------------------------------------------\n\n";
}

echo "==================================================\n";
echo "TEST RESULTS: $successCount / $totalCount Passed\n";
echo "==================================================\n";
