<?php
/**
 * UNIFIED DISASTER INDICATORS ENDPOINT
 * 
 * GET: Retrieve disaster monitoring events (weather alerts, earthquake seismic alerts, and integration statuses)
 */

error_reporting(E_ALL);
ini_set('display_errors', 0);
ob_start();

require_once __DIR__ . '/auth.php';

// Verification helpers
function getAlertsTableName(PDO $pdo): string {
    $candidates = ['alerts', 'alerts_runtime', 'alerts_runtime_fallback'];
    foreach ($candidates as $candidate) {
        try {
            $stmt = $pdo->query("SHOW TABLES LIKE " . $pdo->quote($candidate));
            if ($stmt && $stmt->fetch()) {
                $pdo->query("SELECT 1 FROM `{$candidate}` LIMIT 1");
                return $candidate;
            }
        } catch (Throwable $e) {}
    }
    return 'alerts';
}

function checkColumn(PDO $pdo, string $table, string $column): bool {
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM `{$table}` LIKE " . $pdo->quote($column));
        return $stmt && $stmt->rowCount() > 0;
    } catch (Throwable $e) {
        return false;
    }
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method !== 'GET') {
    sendJsonResponse(false, 'Method Not Allowed.', [], 405);
}

try {
    $type = isset($_GET['type']) ? strtolower(trim($_GET['type'])) : 'all';
    $limit = isset($_GET['limit']) ? min(100, max(1, (int)$_GET['limit'])) : 30;
    
    $alertsTable = getAlertsTableName($pdo);
    $hasSeverity = checkColumn($pdo, $alertsTable, 'severity');
    $hasSource = checkColumn($pdo, $alertsTable, 'source');
    $hasType = checkColumn($pdo, $alertsTable, 'type');
    $hasCategoryCol = checkColumn($pdo, $alertsTable, 'category');
    
    $hasCategoryTable = false;
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE 'alert_categories'");
        if ($stmt && $stmt->fetch()) {
            $hasCategoryTable = true;
        }
    } catch (Throwable $e) {}

    // Integration Setting check
    $integrations = [
        'weather_monitoring' => false,
        'earthquake_monitoring' => false,
        'ai_analysis' => false
    ];
    
    // Check if integration_settings table exists
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE 'integration_settings'");
        if ($stmt && $stmt->fetch()) {
            $checkStmt = $pdo->query("SELECT source, enabled FROM integration_settings");
            while ($row = $checkStmt->fetch(PDO::FETCH_ASSOC)) {
                $src = strtolower($row['source']);
                $enabled = (int)$row['enabled'] === 1;
                if (strpos($src, 'pagasa') !== false || strpos($src, 'openweather') !== false) {
                    $integrations['weather_monitoring'] = $integrations['weather_monitoring'] || $enabled;
                }
            }
        }
    } catch (Throwable $e) {}

    // Check environment configs
    if (function_exists('getSecureConfig')) {
        $owmKey = getSecureConfig('OPENWEATHER_API_KEY', '');
        $pagasaKey = getSecureConfig('PAGASA_API_KEY', '');
        $aiKey = getSecureConfig('AI_API_KEY', '') ?: getSecureConfig('AI_API_KEY_ANALYSIS', '');
        
        if (!empty($owmKey) || !empty($pagasaKey)) {
            $integrations['weather_monitoring'] = true;
        }
        if (!empty($aiKey)) {
            $integrations['ai_analysis'] = true;
        }
    }

    $responsePayload = [
        'integrations' => $integrations
    ];

    // Base query for disasters
    $query = "SELECT a.id, a.title, a.message, a.content, ";
    $query .= ($hasSeverity ? "a.severity" : "'' AS severity") . ", ";
    $query .= ($hasSource ? "a.source" : "'' AS source") . ", ";
    $query .= ($hasType ? "a.type" : "'' AS type") . ", ";
    $query .= ($hasCategoryCol ? "a.category" : "'' AS category") . ", ";
    $query .= "a.status, a.created_at ";
    
    if ($hasCategoryTable) {
        $query .= ", COALESCE(ac.name, 'General') as category_name,
                   COALESCE(ac.icon, 'fa-exclamation-triangle') as category_icon,
                   COALESCE(ac.color, '#95a5a6') as category_color
                   FROM {$alertsTable} a
                   LEFT JOIN alert_categories ac ON a.category_id = ac.id
                   WHERE a.status = 'active'";
    } else {
        $query .= ", 'General' as category_name
                   FROM {$alertsTable} a
                   WHERE a.status = 'active'";
    }

    // Build specific filters for disaster types
    $conditions = [];
    $params = [];

    $sourceWeatherCond = $hasSource ? " OR a.source = 'pagasa'" : "";
    $typeWeatherCond = $hasType ? " OR a.type = 'weather'" : "";
    $sourceQuakeCond = $hasSource ? " OR a.source = 'phivolcs'" : "";
    $typeQuakeCond = $hasType ? " OR a.type = 'earthquake'" : "";
    
    $sourceAllCond = $hasSource ? " OR a.source IN ('pagasa', 'phivolcs')" : "";
    $typeAllCond = $hasType ? " OR a.type IN ('weather', 'earthquake')" : "";

    if ($type === 'weather') {
        if ($hasCategoryTable) {
            $conditions[] = "(ac.name = 'Weather' OR a.category = 'Weather' OR a.title LIKE '%weather%' OR a.title LIKE '%flood%' OR a.title LIKE '%rain%' OR a.title LIKE '%storm%' OR a.title LIKE '%typhoon%'" . $sourceWeatherCond . $typeWeatherCond . ")";
        } else {
            $conditions[] = "(a.category = 'Weather' OR a.title LIKE '%weather%' OR a.title LIKE '%flood%' OR a.title LIKE '%rain%' OR a.title LIKE '%storm%' OR a.title LIKE '%typhoon%'" . $sourceWeatherCond . $typeWeatherCond . ")";
        }
    } elseif ($type === 'earthquake') {
        if ($hasCategoryTable) {
            $conditions[] = "(ac.name = 'Earthquake' OR a.category = 'Earthquake' OR a.title LIKE '%earthquake%' OR a.title LIKE '%seismic%'" . $sourceQuakeCond . $typeQuakeCond . ")";
        } else {
            $conditions[] = "(a.category = 'Earthquake' OR a.title LIKE '%earthquake%' OR a.title LIKE '%seismic%'" . $sourceQuakeCond . $typeQuakeCond . ")";
        }
    } else {
        // Fetch both weather and earthquake disaster categories
        if ($hasCategoryTable) {
            $conditions[] = "(ac.name IN ('Weather', 'Earthquake') OR a.category IN ('Weather', 'Earthquake') OR a.title LIKE '%weather%' OR a.title LIKE '%flood%' OR a.title LIKE '%typhoon%' OR a.title LIKE '%earthquake%' OR a.title LIKE '%seismic%'" . $typeAllCond . $sourceAllCond . ")";
        } else {
            $conditions[] = "(a.category IN ('Weather', 'Earthquake') OR a.title LIKE '%weather%' OR a.title LIKE '%flood%' OR a.title LIKE '%typhoon%' OR a.title LIKE '%earthquake%' OR a.title LIKE '%seismic%'" . $typeAllCond . $sourceAllCond . ")";
        }
    }

    if (!empty($conditions)) {
        $query .= " AND " . implode(" AND ", $conditions);
    }

    $query .= " ORDER BY a.id DESC LIMIT :limit";
    
    $stmt = $pdo->prepare($query);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    
    $disasters = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $responsePayload['events'] = $disasters;

    logApiAccess($pdo, $deptName, "/api/disaster.php?type=$type", 'GET', 200, "Retrieved " . count($disasters) . " disaster events");
    sendJsonResponse(true, 'Disaster events retrieved successfully.', $responsePayload);

} catch (PDOException $e) {
    logApiAccess($pdo, $deptName, '/api/disaster.php', 'GET', 500, "Database query exception: " . $e->getMessage());
    sendJsonResponse(false, 'Database query failed: ' . $e->getMessage(), [], 500);
}
