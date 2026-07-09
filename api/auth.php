<?php
/**
 * GENERIC DEPARTMENT API AUTHENTICATION & SECURITY LAYER
 * 
 * Verifies the incoming requests against the department_api_keys table.
 * Dynamically logs api activity to department_api_logs.
 */

require_once __DIR__ . '/config.php';

// Add production-grade CORS headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Department-API-Key, X-API-Key');

// Handle preflight OPTIONS requests immediately
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Prevent direct access
if (!isset($pdo) || $pdo === null) {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'message' => 'Database connection not available.']);
    exit();
}

// 1. Ensure table configurations are ready
try {
    // API Keys Table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS department_api_keys (
            id INT AUTO_INCREMENT PRIMARY KEY,
            department_name VARCHAR(100) NOT NULL UNIQUE COMMENT 'Name of the department using the key',
            api_key VARCHAR(64) NOT NULL UNIQUE COMMENT 'Department API key token',
            status VARCHAR(20) DEFAULT 'active' COMMENT 'active, suspended',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_api_key (api_key),
            INDEX idx_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    
    // Seed default keys if table is empty for quick start/test
    $count = $pdo->query("SELECT COUNT(*) FROM department_api_keys")->fetchColumn();
    if ($count == 0) {
        $seeds = [
            ['Default Department', 'DEFAULT-DEPT-SECURE-KEY-2026'],
            ['Health Department', 'HEALTH-DEPT-SECURE-KEY-2026'],
            ['Fire Department', 'FIRE-DEPT-SECURE-KEY-2026'],
            ['Disaster Management Agency', 'DISASTER-MGMT-SECURE-KEY-2026']
        ];
        $insertStmt = $pdo->prepare("INSERT INTO department_api_keys (department_name, api_key, status) VALUES (?, ?, 'active')");
        foreach ($seeds as $seed) {
            $insertStmt->execute($seed);
        }
    }
} catch (PDOException $e) {
    error_log('API Auth Table Initializer Note: ' . $e->getMessage());
}

// Helper: Standard JSON Responder
function sendJsonResponse(bool $success, string $message, array $data = [], int $httpCode = 200) {
    ob_clean();
    http_response_code($httpCode);
    header('Content-Type: application/json; charset=utf-8');
    
    $response = [
        'success' => $success,
        'message' => $message
    ];
    if (!empty($data)) {
        $response = array_merge($response, $data);
    }
    
    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit();
}

// Authentication configuration
// Set REQUIRE_API_KEY to true to enforce credential checks in production
if (!defined('REQUIRE_API_KEY')) {
    define('REQUIRE_API_KEY', true); 
}

// Master Integrated API Key for centralized query access
if (!defined('INTEGRATED_API_KEY')) {
    define('INTEGRATED_API_KEY', 'EMERGENCY-SYSTEM-INTEGRATED-KEY-2026');
}

// 2. Identify the incoming API key
$apiKey = null;

if (isset($_SERVER['HTTP_X_DEPARTMENT_API_KEY'])) {
    $apiKey = $_SERVER['HTTP_X_DEPARTMENT_API_KEY'];
} elseif (isset($_SERVER['HTTP_X_API_KEY'])) {
    $apiKey = $_SERVER['HTTP_X_API_KEY'];
} elseif (isset($_SERVER['HTTP_AUTHORIZATION'])) {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
        $apiKey = $matches[1];
    }
} elseif (isset($_GET['api_key'])) {
    $apiKey = $_GET['api_key'];
}

$deptName = 'Generic Department';

if (empty($apiKey)) {
    if (REQUIRE_API_KEY === true) {
        sendJsonResponse(false, 'Unauthorized: Missing API Key. Provide it via X-Department-API-Key or Authorization Bearer header.', [], 401);
    }
} else {
    // 1. Check against the Master Integrated Key first
    if ($apiKey === INTEGRATED_API_KEY) {
        $deptName = 'Integrated Central Access';
    } else {
        // 2. Validate key in Database
        try {
            $stmt = $pdo->prepare("SELECT department_name, status FROM department_api_keys WHERE api_key = ? LIMIT 1");
            $stmt->execute([$apiKey]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$result) {
                // Backup validation: check config.local.php ADMIN_API_KEY if configured
                $configApiKey = null;
                if (function_exists('getSecureConfig')) {
                    $configApiKey = getSecureConfig('ADMIN_API_KEY', null);
                }
                
                if (!empty($configApiKey) && $apiKey === $configApiKey) {
                    $deptName = 'Root Admin';
                } else {
                    if (REQUIRE_API_KEY === true) {
                        sendJsonResponse(false, 'Unauthorized: Invalid API Key.', [], 401);
                    }
                }
            } else {
                if ($result['status'] !== 'active') {
                    if (REQUIRE_API_KEY === true) {
                        sendJsonResponse(false, 'Forbidden: This department API Key has been suspended.', [], 403);
                    }
                } else {
                    $deptName = $result['department_name'];
                }
            }
        } catch (PDOException $e) {
            error_log('API Auth verification error: ' . $e->getMessage());
            if (REQUIRE_API_KEY === true) {
                sendJsonResponse(false, 'Internal Server Error during authentication.', [], 500);
            }
        }
    }
}

// 4. API Request Logger Function
function logApiAccess(PDO $pdo, string $department, string $endpoint, string $method, int $statusCode, ?string $details = null) {
    try {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS department_api_logs (
                id INT AUTO_INCREMENT PRIMARY KEY,
                department_name VARCHAR(100) NOT NULL,
                endpoint VARCHAR(255) NOT NULL,
                method VARCHAR(10) NOT NULL,
                status_code INT NOT NULL,
                ip_address VARCHAR(45) DEFAULT NULL,
                details TEXT DEFAULT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        
        $stmt = $pdo->prepare("
            INSERT INTO department_api_logs (department_name, endpoint, method, status_code, ip_address, details)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $department,
            $endpoint,
            $method,
            $statusCode,
            $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            $details
        ]);
    } catch (PDOException $e) {
        error_log('API Logs Helper Exception: ' . $e->getMessage());
    }
}
