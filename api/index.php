<?php
/**
 * CENTRALIZED API GATEWAY ROUTER & UNIFIED FEED
 * 
 * Routes all incoming requests through a single entry point:
 * http://localhost/EMERGENCY-COM/api/
 * 
 * GET /api/ (or ?module=all): Retrieves a consolidated overview of ALL modules
 * GET /api/?module=<name>: Routes directly to the respective module
 */

// Enable error logging, prevent displaying output directly
error_reporting(E_ALL);
ini_set('display_errors', 0);
ob_start();

// 1. Resolve requested module
$module = $_GET['module'] ?? $_GET['resource'] ?? '';

if (empty($module)) {
    // Check PATH_INFO (e.g. /api/index.php/alerts)
    $pathInfo = $_SERVER['PATH_INFO'] ?? '';
    if (!empty($pathInfo)) {
        $parts = explode('/', trim($pathInfo, '/'));
        $module = $parts[0] ?? '';
    } else {
        // Fallback: parse from REQUEST_URI
        $requestUri = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
        $uriParts = explode('/api/', $requestUri);
        if (isset($uriParts[1]) && !empty($uriParts[1])) {
            $parts = explode('/', trim($uriParts[1], '/'));
            $module = $parts[0] ?? '';
            if ($module === 'index.php') {
                $module = $parts[1] ?? '';
            }
        }
    }
}

// Normalize module name
$module = strtolower(trim($module));

// Strip .php extension if supplied by the caller
if (substr($module, -4) === '.php') {
    $module = substr($module, 0, -4);
}

// Map of allowed modules to endpoints
$routes = [
    'alerts' => 'alerts.php',
    'users' => 'users.php',
    'calls' => 'calls.php',
    'disaster' => 'disaster.php',
    'chat' => 'chat.php',
    'audit' => 'audit.php'
];

// If requesting specific route, dispatch immediately
if (!empty($module) && isset($routes[$module])) {
    require_once __DIR__ . '/' . $routes[$module];
    exit();
}

// If requesting invalid module, and it is not an 'all' or dashboard request, throw 400
if (!empty($module) && !in_array($module, ['all', 'dashboard', 'centralized'])) {
    ob_clean();
    http_response_code(400);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => false,
        'message' => 'Bad Request: Invalid module parameter.',
        'directory' => [
            'Alerts Feed & Broadcasting' => '?module=alerts',
            'Citizen Profiles & Telemetry' => '?module=users',
            'Emergency Call Audits' => '?module=calls',
            'Disaster Weather & Earthquakes' => '?module=disaster',
            'Live Chat & Message Dispatch' => '?module=chat',
            'System Notification & API Auditing' => '?module=audit',
            'Consolidated System Overview' => '?module=all'
        ]
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit();
}

// =========================================================================
// CONSOLIDATED OVERVIEW GATEWAY (GET /api/ or ?module=all)
// =========================================================================

// Load DB connection & security checking
require_once __DIR__ . '/auth.php';

// Verification helpers
function getGatewayTableName(PDO $pdo, string $tableName): string {
    $candidates = [$tableName, $tableName . '_runtime', $tableName . '_runtime_fallback'];
    foreach ($candidates as $candidate) {
        try {
            $stmt = $pdo->query("SHOW TABLES LIKE " . $pdo->quote($candidate));
            if ($stmt && $stmt->fetch()) {
                $pdo->query("SELECT 1 FROM `{$candidate}` LIMIT 1");
                return $candidate;
            }
        } catch (Throwable $e) {}
    }
    return $tableName;
}

function checkGatewayColumn(PDO $pdo, string $table, string $column): bool {
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM `{$table}` LIKE " . $pdo->quote($column));
        return $stmt && $stmt->rowCount() > 0;
    } catch (Throwable $e) {
        return false;
    }
}

// Combine payloads from all modules
$centralData = [
    'timestamp' => date('c'),
    'department' => $deptName,
    'alerts' => [],
    'locations' => [],
    'disasters' => [],
    'calls' => [],
    'conversations' => []
];

// 1. Fetch Alerts
try {
    $alertsTable = getGatewayTableName($pdo, 'alerts');
    $hasSeverity = checkGatewayColumn($pdo, $alertsTable, 'severity');
    $hasCategoryCol = checkGatewayColumn($pdo, $alertsTable, 'category');
    
    $query = "SELECT id, title, message, " . ($hasSeverity ? "severity" : "'' AS severity") . ", " . ($hasCategoryCol ? "category" : "'' AS category") . ", status, created_at FROM {$alertsTable} WHERE status = 'active' ORDER BY id DESC LIMIT 5";
    $centralData['alerts'] = $pdo->query($query)->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $centralData['alerts_error'] = $e->getMessage();
}

// 2. Fetch Active Locations
try {
    $locTable = getGatewayTableName($pdo, 'user_locations');
    $hasIsCurrent = checkGatewayColumn($pdo, $locTable, 'is_current');
    
    $locQuery = "
        SELECT ul.latitude, ul.longitude, ul.address as location_address, u.name as user_name, u.phone as user_phone
        FROM {$locTable} ul
        INNER JOIN users u ON ul.user_id = u.id
        WHERE u.status = 'active'
    ";
    if ($hasIsCurrent) {
        $locQuery .= " AND ul.is_current = 1";
    }
    $locQuery .= " ORDER BY ul.id DESC LIMIT 10";
    
    $centralData['locations'] = $pdo->query($locQuery)->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $centralData['locations_error'] = $e->getMessage();
}

// 3. Fetch Disasters (Weather & Earthquake alerts)
try {
    $alertsTable = getGatewayTableName($pdo, 'alerts');
    $hasSeverity = checkGatewayColumn($pdo, $alertsTable, 'severity');
    $hasCategoryCol = checkGatewayColumn($pdo, $alertsTable, 'category');
    
    $disQuery = "
        SELECT id, title, message, " . ($hasSeverity ? "severity" : "'' AS severity") . ", " . ($hasCategoryCol ? "category" : "'' AS category") . ", status, created_at 
        FROM {$alertsTable} 
        WHERE status = 'active' 
        AND (category IN ('Weather', 'Earthquake') OR title LIKE '%weather%' OR title LIKE '%flood%' OR title LIKE '%typhoon%' OR title LIKE '%earthquake%' OR title LIKE '%seismic%')
        ORDER BY id DESC LIMIT 5
    ";
    $centralData['disasters'] = $pdo->query($disQuery)->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $centralData['disasters_error'] = $e->getMessage();
}

// 4. Fetch Emergency Call Logs
try {
    $centralData['calls'] = $pdo->query("SELECT call_id, role, event, duration_sec, created_at FROM call_logs ORDER BY id DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $centralData['calls_error'] = $e->getMessage();
}

// 5. Fetch Chat Conversations
try {
    $centralData['conversations'] = $pdo->query("SELECT conversation_id, user_name, user_concern, status, last_message, last_message_time FROM conversations WHERE status IN ('active', 'open', 'in_progress', 'waiting_user') ORDER BY last_message_time DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $centralData['conversations_error'] = $e->getMessage();
}

// Log access & return payload
logApiAccess($pdo, $deptName, '/api/ (Centralized Overview)', 'GET', 200, "Retrieved unified feed overview");
sendJsonResponse(true, 'Centralized system overview retrieved successfully.', $centralData);
