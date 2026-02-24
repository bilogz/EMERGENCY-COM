<?php
/**
 * Automated Warning Integration API
 * Integrate with external warning feeds (PAGASA, PHIVOLCS)
 */

// Set error handling to prevent output before JSON
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Start output buffering to catch any unexpected output
ob_start();

header('Content-Type: application/json; charset=utf-8');

try {
    require_once 'db_connect.php';
    require_once 'activity_logger.php';
    require_once 'secure-api-config.php';
} catch (Exception $e) {
    ob_end_clean();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to load required files: ' . $e->getMessage()]);
    exit();
}

session_start();

$action = $_GET['action'] ?? 'status';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Check Content-Type to determine if JSON or FormData
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        $isJson = strpos($contentType, 'application/json') !== false;
        
        $data = null;
        if ($isJson) {
            $data = json_decode(file_get_contents('php://input'), true);
        }
        
        $effectiveAction = '';
        if (isset($_GET['action'])) {
            $effectiveAction = (string)$_GET['action'];
        } elseif (isset($_POST['action'])) {
            $effectiveAction = (string)$_POST['action'];
        }
        if ($isJson && is_array($data) && isset($data['action'])) {
            $effectiveAction = (string)$data['action'];
        }
        $effectiveAction = strtolower(trim($effectiveAction));

        // Handle JSON toggle action
        if ($effectiveAction === 'toggle') {
            $source = '';
            if ($isJson && is_array($data) && isset($data['source'])) {
                $source = (string)$data['source'];
            } elseif (isset($_POST['source'])) {
                $source = (string)$_POST['source'];
            }

            $enabled = false;
            if ($isJson && is_array($data) && isset($data['enabled'])) {
                $enabled = (bool)$data['enabled'];
            } elseif (isset($_POST['enabled'])) {
                $enabled = (bool)$_POST['enabled'];
            }
            $adminId = $_SESSION['admin_user_id'] ?? null;
            
            if ($pdo === null) {
                throw new Exception('Database connection not available');
            }
            if (function_exists('ensureIntegrationSettingsTableHealthy')) {
                ensureIntegrationSettingsTableHealthy($pdo);
            }
            
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO integration_settings (source, enabled, updated_at)
                    VALUES (?, ?, NOW())
                    ON DUPLICATE KEY UPDATE enabled = ?, updated_at = NOW()
                ");
                $stmt->execute([$source, $enabled ? 1 : 0, $enabled ? 1 : 0]);
                
                // Log admin activity
                if ($adminId && function_exists('logAdminActivity')) {
                    logAdminActivity($adminId, 'toggle_integration', 
                        ucfirst($source) . " integration " . ($enabled ? 'enabled' : 'disabled'));
                }
                
                ob_clean();
                echo json_encode([
                    'success' => true,
                    'message' => 'Integration toggled successfully.'
                ]);
            } catch (PDOException $e) {
                ob_clean();
                error_log("Toggle Integration Error: " . $e->getMessage());
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Database error occurred.']);
            }
        } elseif ($effectiveAction === 'mock_alert') {
            if ($pdo === null) {
                throw new Exception('Database connection not available');
            }
            if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
                throw new Exception('Unauthorized');
            }

            $mockType = 'weather';
            if ($isJson && is_array($data) && isset($data['type'])) {
                $mockType = strtolower(trim((string)$data['type']));
            } elseif (isset($_POST['type'])) {
                $mockType = strtolower(trim((string)$_POST['type']));
            } elseif (isset($_GET['type'])) {
                $mockType = strtolower(trim((string)$_GET['type']));
            }
            if (!in_array($mockType, ['weather', 'earthquake'], true)) {
                throw new Exception('Invalid mock alert type. Allowed: weather, earthquake');
            }

            $template = buildCriticalWarningTemplate($mockType, true);
            $warningsTable = resolveAutomatedWarningsTable($pdo);
            $adminId = $_SESSION['admin_user_id'] ?? null;

            $ins = $pdo->prepare("
                INSERT INTO {$warningsTable} (source, type, title, content, severity, status, received_at, published_at)
                VALUES (?, ?, ?, ?, 'critical', 'published', NOW(), NOW())
            ");
            $ins->execute([$template['source'], $template['type'], $template['title'], $template['content']]);
            $warningId = (int)$pdo->lastInsertId();

            $dispatchResult = safeAutoPublishCriticalWarningToCitizens($pdo, [
                'id' => $warningId,
                'source' => $template['source'],
                'type' => $template['type'],
                'title' => $template['title'],
                'content' => $template['content'],
                'severity' => 'critical',
                'status' => 'published'
            ], $adminId);

            $degraded = empty($dispatchResult['alert_id']);
            $messageText = strtoupper($mockType) . ' mock alert published and queued for citizen broadcast.';
            if ($degraded) {
                $messageText = strtoupper($mockType) . ' mock alert saved, but citizen delivery is degraded (alert feed or queue table issue).';
            }

            if ($adminId && function_exists('logAdminActivity')) {
                logAdminActivity($adminId, 'mock_critical_warning', strtoupper($mockType) . " mock warning created (ID: {$warningId})");
            }

            ob_clean();
            echo json_encode([
                'success' => true,
                'message' => $messageText,
                'degraded' => $degraded,
                'warning_id' => $warningId,
                'dispatch' => $dispatchResult
            ]);
        } elseif ($effectiveAction === 'ingest_critical') {
            if ($pdo === null) {
                throw new Exception('Database connection not available');
            }
            if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
                throw new Exception('Unauthorized');
            }

            $domain = 'weather';
            if ($isJson && is_array($data)) {
                $domain = strtolower(trim((string)($data['domain'] ?? $data['type'] ?? 'weather')));
            } elseif (isset($_POST['domain'])) {
                $domain = strtolower(trim((string)$_POST['domain']));
            } elseif (isset($_POST['type'])) {
                $domain = strtolower(trim((string)$_POST['type']));
            } elseif (isset($_GET['domain'])) {
                $domain = strtolower(trim((string)$_GET['domain']));
            } elseif (isset($_GET['type'])) {
                $domain = strtolower(trim((string)$_GET['type']));
            }
            if (!in_array($domain, ['weather', 'earthquake'], true)) {
                throw new Exception('Invalid critical domain. Allowed: weather, earthquake');
            }

            $template = buildCriticalWarningTemplate($domain, false);
            $title = '';
            $content = '';
            if ($isJson && is_array($data)) {
                $title = trim((string)($data['title'] ?? ''));
                $content = trim((string)($data['content'] ?? ''));
            } else {
                $title = trim((string)($_POST['title'] ?? $_GET['title'] ?? ''));
                $content = trim((string)($_POST['content'] ?? $_GET['content'] ?? ''));
            }
            if ($title === '') $title = $template['title'];
            if ($content === '') $content = $template['content'];

            $warningsTable = resolveAutomatedWarningsTable($pdo);
            $adminId = $_SESSION['admin_user_id'] ?? null;

            $ins = $pdo->prepare("
                INSERT INTO {$warningsTable} (source, type, title, content, severity, status, received_at, published_at)
                VALUES (?, ?, ?, ?, 'critical', 'published', NOW(), NOW())
            ");
            $ins->execute([$template['source'], $template['type'], $title, $content]);
            $warningId = (int)$pdo->lastInsertId();

            $dispatchResult = safeAutoPublishCriticalWarningToCitizens($pdo, [
                'id' => $warningId,
                'source' => $template['source'],
                'type' => $template['type'],
                'title' => $title,
                'content' => $content,
                'severity' => 'critical',
                'status' => 'published'
            ], $adminId);

            $degraded = empty($dispatchResult['alert_id']);
            $messageText = 'Critical event ingested and auto-broadcast queued.';
            if ($degraded) {
                $messageText = 'Critical event ingested, but citizen delivery is degraded (alert feed or queue table issue).';
            }

            ob_clean();
            echo json_encode([
                'success' => true,
                'message' => $messageText,
                'degraded' => $degraded,
                'warning_id' => $warningId,
                'dispatch' => $dispatchResult
            ]);
        } elseif ($effectiveAction === '' || $effectiveAction === 'save' || $effectiveAction === 'save_settings') {
            // Save settings from FormData
            $syncInterval = $_POST['sync_interval'] ?? 15;
            $autoPublish = isset($_POST['auto_publish']) ? 1 : 0;
            $channels = $_POST['channels'] ?? [];
            $adminId = $_SESSION['admin_user_id'] ?? null;
            
            if ($pdo === null) {
                throw new Exception('Database connection not available');
            }
            
            try {
                // Handle channels - can be array or string
                if (is_string($channels)) {
                    $channelsStr = $channels;
                } else if (is_array($channels)) {
                    $channelsStr = implode(',', $channels);
                } else {
                    $channelsStr = '';
                }
                
                // Get existing record to update, or insert new
                $stmt = $pdo->query("SELECT id FROM warning_settings ORDER BY id DESC LIMIT 1");
                $existing = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($existing) {
                    // Update existing record
                    $stmt = $pdo->prepare("
                        UPDATE warning_settings 
                        SET sync_interval = ?, auto_publish = ?, notification_channels = ?, updated_at = NOW()
                        WHERE id = ?
                    ");
                    $stmt->execute([$syncInterval, $autoPublish, $channelsStr, $existing['id']]);
                } else {
                    // Insert new record
                    $stmt = $pdo->prepare("
                        INSERT INTO warning_settings (sync_interval, auto_publish, notification_channels, updated_at)
                        VALUES (?, ?, ?, NOW())
                    ");
                    $stmt->execute([$syncInterval, $autoPublish, $channelsStr]);
                }
                
                // Log admin activity
                if ($adminId && function_exists('logAdminActivity')) {
                    $changes = [
                        "Sync Interval: {$syncInterval} minutes",
                        "Auto Publish: " . ($autoPublish ? 'Yes' : 'No'),
                        "Channels: {$channelsStr}"
                    ];
                    logAdminActivity($adminId, 'update_warning_settings', 'Updated warning settings: ' . implode(', ', $changes));
                }
                
                ob_clean();
                echo json_encode([
                    'success' => true,
                    'message' => 'Settings saved successfully.'
                ]);
            } catch (PDOException $e) {
                ob_clean();
                error_log("Save Settings Error: " . $e->getMessage());
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Database error occurred: ' . $e->getMessage()]);
            }
        } else {
            ob_clean();
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => "Unknown action: {$effectiveAction}"
            ]);
        }
    } catch (Exception $e) {
        ob_clean();
        error_log("POST Request Error: " . $e->getMessage());
        $isUnauthorized = stripos($e->getMessage(), 'unauthorized') !== false;
        http_response_code($isUnauthorized ? 401 : 500);
        echo json_encode(['success' => false, 'message' => 'Request error: ' . $e->getMessage()]);
    }
} elseif ($action === 'status') {
    try {
        // Check database connection first
        if ($pdo === null) {
            throw new Exception('Database connection not available');
        }

        if (function_exists('ensureIntegrationSettingsTableHealthy')) {
            ensureIntegrationSettingsTableHealthy($pdo);
        }

        $settings = [];
        try {
            $stmt = $pdo->query("SELECT source, enabled FROM integration_settings");
            $settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        } catch (Throwable $settingsEx) {
            error_log("Integration settings read error: " . $settingsEx->getMessage());
            $settings = [];
        }
        
        // Check Gemini status from AI settings and secure config
        $geminiEnabled = false;
        $geminiApiKeySet = false;
        $geminiStatusMessage = 'API Key Required';
        
        try {
            // Check secure config file first (most reliable)
            $secureConfigPath = __DIR__ . '/secure-api-config.php';
            $secureApiKey = null;
            if (file_exists($secureConfigPath)) {
                require_once $secureConfigPath;
                if (function_exists('getGeminiApiKey')) {
                    try {
                        $secureApiKey = getGeminiApiKey();
                    } catch (Exception $keyEx) {
                        error_log("Error getting Gemini API key: " . $keyEx->getMessage());
                        $secureApiKey = null;
                    }
                }
            }
            
            // Check AI warning settings table
            try {
                $aiStmt = $pdo->query("SELECT ai_enabled, gemini_api_key FROM ai_warning_settings ORDER BY id DESC LIMIT 1");
                $aiSettings = $aiStmt->fetch(PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                // Table might not exist yet - this is okay
                $aiSettings = null;
                error_log("AI warning settings table check: " . $e->getMessage());
            }
            
            if ($aiSettings) {
                $geminiEnabled = isset($aiSettings['ai_enabled']) && $aiSettings['ai_enabled'] == 1;
                // Check if API key is set in either table or secure config
                $dbApiKey = !empty($aiSettings['gemini_api_key']) ? $aiSettings['gemini_api_key'] : null;
                $geminiApiKeySet = !empty($dbApiKey) || !empty($secureApiKey);
                
                if ($geminiApiKeySet && $geminiEnabled) {
                    $geminiStatusMessage = 'AI Active and Monitoring';
                } elseif ($geminiApiKeySet && !$geminiEnabled) {
                    $geminiStatusMessage = 'API Key Set - Enable AI';
                } elseif (!$geminiApiKeySet) {
                    $geminiStatusMessage = 'API Key Required';
                }
            } else {
                // No settings in table, but check secure config
                if (!empty($secureApiKey)) {
                    $geminiApiKeySet = true;
                    $geminiEnabled = function_exists('isAIAnalysisEnabled') ? isAIAnalysisEnabled('weather') : false;
                    $geminiStatusMessage = $geminiEnabled ? 'AI Active and Monitoring' : 'API Key Found - Configure Settings';
                } else {
                    $geminiStatusMessage = 'API Key Required';
                }
            }
        } catch (Exception $e) {
            // Fallback: check secure config only
            error_log("Gemini status check error: " . $e->getMessage());
            $secureConfigPath = __DIR__ . '/secure-api-config.php';
            if (file_exists($secureConfigPath)) {
                try {
                    require_once $secureConfigPath;
                    if (function_exists('getGeminiApiKey')) {
                        $secureApiKey = getGeminiApiKey();
                        if (!empty($secureApiKey)) {
                            $geminiApiKeySet = true;
                            $geminiStatusMessage = 'API Key Found - Configure Settings';
                        }
                    }
                } catch (Exception $ex) {
                    error_log("Fallback Gemini key check error: " . $ex->getMessage());
                }
            }
        }
        
        // Clear any unexpected output before JSON
        ob_clean();
        
        echo json_encode([
            'success' => true,
            'pagasa' => [
                'enabled' => (isset($settings['pagasa']) && $settings['pagasa']) ||
                    (function_exists('getOpenWeatherApiKey') && !empty(getOpenWeatherApiKey(false)))
            ],
            'phivolcs' => ['enabled' => isset($settings['phivolcs']) && $settings['phivolcs']],
            'gemini' => [
                'enabled' => $geminiEnabled, 
                'api_key_set' => $geminiApiKeySet,
                'status_message' => $geminiStatusMessage
            ]
        ]);
    } catch (PDOException $e) {
        ob_clean();
        error_log("Get Status Error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error occurred.']);
    } catch (Exception $e) {
        ob_clean();
        error_log("Get Status Error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error occurred: ' . $e->getMessage()]);
    }
} elseif ($action === 'warnings') {
    try {
        if ($pdo === null) {
            throw new Exception('Database connection not available');
        }

        $warningsTable = resolveAutomatedWarningsTable($pdo);
        
        $stmt = $pdo->query("
            SELECT id, source, type, title, content, severity, status, received_at
            FROM {$warningsTable}
            ORDER BY received_at DESC
            LIMIT 100
        ");
        $warnings = $stmt->fetchAll();
        
        ob_clean();
        echo json_encode([
            'success' => true,
            'warnings' => $warnings,
            'meta' => ['table' => $warningsTable]
        ]);
    } catch (PDOException $e) {
        ob_clean();
        error_log("Get Warnings Error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error occurred.']);
    } catch (Exception $e) {
        ob_clean();
        error_log("Get Warnings Error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error occurred: ' . $e->getMessage()]);
    }
} elseif ($action === 'analytics') {
    try {
        if ($pdo === null) {
            throw new Exception('Database connection not available');
        }
        $warningsTable = resolveAutomatedWarningsTable($pdo);

        $sourceMap = [
            'weather' => ['pagasa'],
            'earthquake' => ['phivolcs']
        ];

        $overviewStmt = $pdo->query("
            SELECT 
                COUNT(*) AS total,
                SUM(CASE WHEN LOWER(COALESCE(status, '')) = 'published' THEN 1 ELSE 0 END) AS published,
                SUM(CASE WHEN LOWER(COALESCE(status, '')) = 'pending' THEN 1 ELSE 0 END) AS pending,
                SUM(CASE WHEN LOWER(COALESCE(severity, '')) = 'critical' THEN 1 ELSE 0 END) AS critical,
                SUM(CASE WHEN received_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR) THEN 1 ELSE 0 END) AS last_24h,
                SUM(CASE WHEN LOWER(COALESCE(source, '')) IN ('pagasa') THEN 1 ELSE 0 END) AS weather_total,
                SUM(CASE WHEN LOWER(COALESCE(source, '')) IN ('phivolcs') THEN 1 ELSE 0 END) AS earthquake_total
            FROM {$warningsTable}
        ");
        $overview = $overviewStmt ? ($overviewStmt->fetch(PDO::FETCH_ASSOC) ?: []) : [];

        $sourceStmt = $pdo->query("
            SELECT
                LOWER(COALESCE(source, 'unknown')) AS source_key,
                COUNT(*) AS total,
                SUM(CASE WHEN LOWER(COALESCE(status, '')) = 'published' THEN 1 ELSE 0 END) AS published,
                SUM(CASE WHEN LOWER(COALESCE(status, '')) = 'pending' THEN 1 ELSE 0 END) AS pending,
                SUM(CASE WHEN LOWER(COALESCE(severity, '')) = 'critical' THEN 1 ELSE 0 END) AS critical,
                SUM(CASE WHEN received_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR) THEN 1 ELSE 0 END) AS last_24h
            FROM {$warningsTable}
            GROUP BY LOWER(COALESCE(source, 'unknown'))
        ");
        $rawSourceRows = $sourceStmt ? $sourceStmt->fetchAll(PDO::FETCH_ASSOC) : [];

        $sourceAgg = [];
        foreach ($rawSourceRows as $row) {
            $sourceKey = strtolower((string)($row['source_key'] ?? 'unknown'));
            foreach ($sourceMap as $bucket => $sources) {
                if (in_array($sourceKey, $sources, true)) {
                    if (!isset($sourceAgg[$bucket])) {
                        $sourceAgg[$bucket] = ['total' => 0, 'published' => 0, 'pending' => 0, 'critical' => 0, 'last_24h' => 0];
                    }
                    $sourceAgg[$bucket]['total'] += (int)($row['total'] ?? 0);
                    $sourceAgg[$bucket]['published'] += (int)($row['published'] ?? 0);
                    $sourceAgg[$bucket]['pending'] += (int)($row['pending'] ?? 0);
                    $sourceAgg[$bucket]['critical'] += (int)($row['critical'] ?? 0);
                    $sourceAgg[$bucket]['last_24h'] += (int)($row['last_24h'] ?? 0);
                }
            }
        }

        foreach (array_keys($sourceMap) as $bucket) {
            if (!isset($sourceAgg[$bucket])) {
                $sourceAgg[$bucket] = ['total' => 0, 'published' => 0, 'pending' => 0, 'critical' => 0, 'last_24h' => 0];
            }
        }

        $severityStmt = $pdo->query("
            SELECT LOWER(COALESCE(severity, 'unknown')) AS severity_key, COUNT(*) AS total
            FROM {$warningsTable}
            GROUP BY LOWER(COALESCE(severity, 'unknown'))
        ");
        $severityRows = $severityStmt ? $severityStmt->fetchAll(PDO::FETCH_ASSOC) : [];
        $severity = ['low' => 0, 'medium' => 0, 'high' => 0, 'critical' => 0];
        foreach ($severityRows as $row) {
            $key = strtolower((string)($row['severity_key'] ?? ''));
            if (isset($severity[$key])) {
                $severity[$key] = (int)($row['total'] ?? 0);
            }
        }

        $statusStmt = $pdo->query("
            SELECT LOWER(COALESCE(status, 'unknown')) AS status_key, COUNT(*) AS total
            FROM {$warningsTable}
            GROUP BY LOWER(COALESCE(status, 'unknown'))
        ");
        $statusRows = $statusStmt ? $statusStmt->fetchAll(PDO::FETCH_ASSOC) : [];
        $status = ['pending' => 0, 'published' => 0, 'archived' => 0];
        foreach ($statusRows as $row) {
            $key = strtolower((string)($row['status_key'] ?? ''));
            if (isset($status[$key])) {
                $status[$key] = (int)($row['total'] ?? 0);
            }
        }

        $trendStmt = $pdo->query("
            SELECT 
                DATE(received_at) AS day,
                SUM(CASE WHEN LOWER(COALESCE(source, '')) IN ('pagasa') THEN 1 ELSE 0 END) AS weather,
                SUM(CASE WHEN LOWER(COALESCE(source, '')) IN ('phivolcs') THEN 1 ELSE 0 END) AS earthquake,
                COUNT(*) AS total
            FROM {$warningsTable}
            WHERE received_at >= DATE_SUB(CURDATE(), INTERVAL 13 DAY)
            GROUP BY DATE(received_at)
            ORDER BY day ASC
        ");
        $trendRows = $trendStmt ? $trendStmt->fetchAll(PDO::FETCH_ASSOC) : [];

        $topTypesStmt = $pdo->query("
            SELECT
                COALESCE(NULLIF(TRIM(type), ''), 'Unspecified') AS warning_type,
                LOWER(COALESCE(source, 'unknown')) AS source_key,
                COUNT(*) AS total
            FROM {$warningsTable}
            GROUP BY COALESCE(NULLIF(TRIM(type), ''), 'Unspecified'), LOWER(COALESCE(source, 'unknown'))
            ORDER BY total DESC, warning_type ASC
            LIMIT 8
        ");
        $topTypesRows = $topTypesStmt ? $topTypesStmt->fetchAll(PDO::FETCH_ASSOC) : [];

        $topTypes = array_map(function ($row) {
            $sourceKey = strtolower((string)($row['source_key'] ?? 'unknown'));
            $bucket = in_array($sourceKey, ['pagasa'], true) ? 'weather' : (in_array($sourceKey, ['phivolcs'], true) ? 'earthquake' : 'other');
            return [
                'type' => (string)($row['warning_type'] ?? 'Unspecified'),
                'source' => $bucket,
                'count' => (int)($row['total'] ?? 0)
            ];
        }, $topTypesRows);

        ob_clean();
        echo json_encode([
            'success' => true,
            'overview' => [
                'total' => (int)($overview['total'] ?? 0),
                'published' => (int)($overview['published'] ?? 0),
                'pending' => (int)($overview['pending'] ?? 0),
                'critical' => (int)($overview['critical'] ?? 0),
                'last_24h' => (int)($overview['last_24h'] ?? 0),
                'weather_total' => (int)($overview['weather_total'] ?? 0),
                'earthquake_total' => (int)($overview['earthquake_total'] ?? 0)
            ],
            'by_source' => $sourceAgg,
            'severity_breakdown' => $severity,
            'status_breakdown' => $status,
            'daily_trend' => $trendRows,
            'top_types' => $topTypes,
            'meta' => [
                'table' => $warningsTable,
                'table_available' => ($warningsTable === 'automated_warnings'),
                'message' => ($warningsTable === 'automated_warnings_runtime')
                    ? 'Primary warning table is unavailable. Using runtime fallback table.'
                    : 'Live data from primary warning table.'
            ]
        ]);
    } catch (PDOException $e) {
        ob_clean();
        error_log("Analytics Error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error occurred.']);
    } catch (Exception $e) {
        ob_clean();
        error_log("Analytics Error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error occurred: ' . $e->getMessage()]);
    }
} elseif ($action === 'dispatch_history') {
    try {
        if ($pdo === null) {
            throw new Exception('Database connection not available');
        }

        $limit = (int)($_GET['limit'] ?? 20);
        if ($limit < 1) $limit = 20;
        if ($limit > 100) $limit = 100;

        $result = getAutomatedWarningDispatchHistory($pdo, $limit);
        ob_clean();
        echo json_encode([
            'success' => true,
            'dispatches' => $result['rows'],
            'meta' => $result['meta']
        ]);
    } catch (PDOException $e) {
        ob_clean();
        error_log("Dispatch History Error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error occurred.']);
    } catch (Exception $e) {
        ob_clean();
        error_log("Dispatch History Error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error occurred: ' . $e->getMessage()]);
    }
} elseif ($action === 'publish' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if ($pdo === null) {
            throw new Exception('Database connection not available');
        }
        $warningsTable = resolveAutomatedWarningsTable($pdo);
        $adminId = $_SESSION['admin_user_id'] ?? null;

        $payload = json_decode(file_get_contents('php://input'), true);
        $warningId = (int)($payload['id'] ?? ($_POST['id'] ?? 0));
        if ($warningId <= 0) {
            throw new Exception('Invalid warning ID.');
        }

        $stmt = $pdo->prepare("SELECT id, source, type, title, content, severity, status FROM {$warningsTable} WHERE id = ? LIMIT 1");
        $stmt->execute([$warningId]);
        $warning = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$warning) {
            http_response_code(404);
            ob_clean();
            echo json_encode(['success' => false, 'message' => 'Warning not found.']);
            exit();
        }

        if (strtolower((string)$warning['status']) === 'published') {
            ob_clean();
            echo json_encode([
                'success' => true,
                'message' => 'Warning is already published.'
            ]);
            exit();
        }

        $update = $pdo->prepare("UPDATE {$warningsTable} SET status = 'published' WHERE id = ?");
        $update->execute([$warningId]);

        $dispatch = null;
        if (shouldAutoBroadcastCriticalWarning((string)($warning['type'] ?? ''), (string)($warning['severity'] ?? ''))) {
            $dispatch = safeAutoPublishCriticalWarningToCitizens($pdo, $warning, $adminId);
        }

        if ($adminId && function_exists('logAdminActivity')) {
            logAdminActivity($adminId, 'publish_warning', "Published automated warning ID {$warningId}: " . ($warning['title'] ?? ''));
        }

        ob_clean();
        echo json_encode([
            'success' => true,
            'message' => 'Warning published successfully.',
            'dispatch' => $dispatch
        ]);
    } catch (PDOException $e) {
        ob_clean();
        error_log("Publish Warning Error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error occurred.']);
    } catch (Exception $e) {
        ob_clean();
        error_log("Publish Warning Error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error occurred: ' . $e->getMessage()]);
    }
} elseif ($action === 'getSettings') {
    try {
        if ($pdo === null) {
            throw new Exception('Database connection not available');
        }
        
        $stmt = $pdo->query("SELECT * FROM warning_settings ORDER BY id DESC LIMIT 1");
        $settings = $stmt->fetch(PDO::FETCH_ASSOC);
        
        ob_clean();
        if (!$settings) {
            // Return default settings
            echo json_encode([
                'success' => true,
                'settings' => [
                    'sync_interval' => 15,
                    'auto_publish' => 0,
                    'notification_channels' => 'sms,email'
                ]
            ]);
        } else {
            echo json_encode(['success' => true, 'settings' => $settings]);
        }
    } catch (PDOException $e) {
        ob_clean();
        error_log("Get Settings Error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error occurred.']);
    } catch (Exception $e) {
        ob_clean();
        error_log("Get Settings Error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error occurred: ' . $e->getMessage()]);
    }
} elseif ($action === 'processIncident' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // Process incident and generate alerts based on severity
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input) {
            throw new Exception('Invalid JSON input');
        }
        
        // Validate required fields
        $required = ['type', 'severity', 'area'];
        foreach ($required as $field) {
            if (!isset($input[$field]) || empty($input[$field])) {
                throw new Exception("Missing required field: $field");
            }
        }
        
        // Validate and sanitize inputs
        $type = strtolower(trim($input['type']));
        $severity = strtoupper(trim($input['severity']));
        $area = trim($input['area']);
        $confidence = isset($input['confidence']) ? floatval($input['confidence']) : 100.00;
        $description = isset($input['description']) ? trim($input['description']) : null;
        $source = isset($input['source']) ? trim($input['source']) : 'manual';
        
        // Validate incident type
        $validTypes = ['flood', 'earthquake', 'fire', 'crime', 'typhoon'];
        if (!in_array($type, $validTypes)) {
            throw new Exception('Invalid incident type. Must be one of: ' . implode(', ', $validTypes));
        }
        
        // Validate severity
        $validSeverities = ['LOW', 'MODERATE', 'EXTREME'];
        if (!in_array($severity, $validSeverities)) {
            throw new Exception('Invalid severity. Must be one of: LOW, MODERATE, EXTREME');
        }
        
        // Validate confidence (0-100)
        if ($confidence < 0 || $confidence > 100) {
            throw new Exception('Confidence must be between 0 and 100');
        }
        
        // Apply confidence downgrade logic: if confidence < 60%, downgrade severity
        $originalSeverity = $severity;
        if ($confidence < 60) {
            $severity = downgradeSeverity($severity);
            error_log("Incident severity downgraded from $originalSeverity to $severity due to low confidence ($confidence%)");
        }
        
        // Insert incident into database
        $stmt = $pdo->prepare("
            INSERT INTO incidents (type, severity, area, confidence, description, source, status)
            VALUES (?, ?, ?, ?, ?, ?, 'active')
        ");
        
        $stmt->execute([$type, $severity, $area, $confidence, $description, $source]);
        $incidentId = $pdo->lastInsertId();
        
        // Determine if alert should be generated
        $alertGenerated = false;
        $alertId = null;
        $alertCategory = null;
        
        if ($severity === 'EXTREME') {
            // EXTREME → send alert immediately
            $alertCategory = 'Emergency Alert';
            $alertMessage = generateAlertMessage($type, $severity, $area, $description);
            $alertId = createIncidentAlert($pdo, $incidentId, $alertCategory, $alertMessage, $area);
            $alertGenerated = true;
            
        } elseif ($severity === 'MODERATE') {
            // MODERATE → send alert to affected area only
            $alertCategory = 'Warning';
            $alertMessage = generateAlertMessage($type, $severity, $area, $description);
            $alertId = createIncidentAlert($pdo, $incidentId, $alertCategory, $alertMessage, $area);
            $alertGenerated = true;
            
        } else {
            // LOW → log incident, no alert generated
            $alertGenerated = false;
            error_log("Incident #$incidentId logged (LOW severity, no alert generated): $type in $area");
        }
        
        ob_clean();
        echo json_encode([
            'success' => true,
            'message' => 'Incident processed successfully',
            'data' => [
                'incident_id' => $incidentId,
                'type' => $type,
                'severity' => $severity,
                'original_severity' => $originalSeverity,
                'area' => $area,
                'confidence' => $confidence,
                'alert_generated' => $alertGenerated,
                'alert_id' => $alertId,
                'alert_category' => $alertCategory
            ]
        ], JSON_UNESCAPED_UNICODE);
        
    } catch (Exception $e) {
        ob_clean();
        error_log("Incident processor error: " . $e->getMessage());
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    } catch (Error $e) {
        ob_clean();
        error_log("Incident processor fatal error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Internal server error'
        ]);
    }
} else {
    ob_clean();
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid action.']);
}

// End output buffering and send output
ob_end_flush();

/**
 * Downgrade severity by one level
 */
function downgradeSeverity($severity) {
    $levels = ['EXTREME' => 'MODERATE', 'MODERATE' => 'LOW', 'LOW' => 'LOW'];
    return $levels[$severity] ?? 'LOW';
}

/**
 * Generate alert message based on incident details
 */
function generateAlertMessage($type, $severity, $area, $description = null) {
    $typeNames = [
        'flood' => 'Flood',
        'earthquake' => 'Earthquake',
        'fire' => 'Fire',
        'crime' => 'Crime',
        'typhoon' => 'Typhoon'
    ];
    
    $typeName = $typeNames[$type] ?? ucfirst($type);
    
    $message = "$typeName alert in $area. ";
    
    if ($severity === 'EXTREME') {
        $message .= "EXTREME SEVERITY - Immediate action required. ";
    } elseif ($severity === 'MODERATE') {
        $message .= "Warning - Please take necessary precautions. ";
    }
    
    if ($description) {
        $message .= $description;
    } else {
        $message .= "Stay safe and follow official instructions.";
    }
    
    return $message;
}

/**
 * Create alert in database from incident
 */
function createIncidentAlert($pdo, $incidentId, $category, $message, $area) {
    try {
        $alertsTable = resolveAlertsTable($pdo);
        // Determine title based on category
        $title = "$category: " . explode('.', $message)[0];
        
        // Check if incident_id column exists
        $stmt = $pdo->query("SHOW COLUMNS FROM {$alertsTable} LIKE 'incident_id'");
        $hasIncidentId = $stmt->rowCount() > 0;
        
        // Check if category column exists (new category, not category_id)
        $stmt = $pdo->query("SHOW COLUMNS FROM {$alertsTable} LIKE 'category'");
        $hasCategory = $stmt->rowCount() > 0;
        
        // Check if area column exists
        $stmt = $pdo->query("SHOW COLUMNS FROM {$alertsTable} LIKE 'area'");
        $hasArea = $stmt->rowCount() > 0;
        
        if ($hasIncidentId && $hasCategory && $hasArea) {
            // Use new columns if they exist
            $stmt = $pdo->prepare("
                INSERT INTO {$alertsTable} (incident_id, category, area, title, message, status, created_at)
                VALUES (?, ?, ?, ?, ?, 'active', NOW())
            ");
            $stmt->execute([$incidentId, $category, $area, $title, $message]);
        } else {
            // Fallback to existing structure
            $stmt = $pdo->prepare("
                INSERT INTO {$alertsTable} (title, message, status, created_at)
                VALUES (?, ?, 'active', NOW())
            ");
            $stmt->execute([$title, $message]);
        }
        
        return $pdo->lastInsertId();
        
    } catch (Exception $e) {
        error_log("Error creating alert: " . $e->getMessage());
        throw $e;
    }
}

/**
 * Ensure automated_warnings exists and is queryable.
 * Attempts to self-heal common local XAMPP table-corruption state (error 1932).
 */
function ensureAutomatedWarningsTableHealthy(PDO $pdo): bool {
    $tableExists = false;
    try {
        $existsStmt = $pdo->query("SHOW TABLES LIKE 'automated_warnings'");
        $tableExists = (bool)($existsStmt && $existsStmt->fetchColumn());
    } catch (PDOException $e) {
        error_log("automated_warnings exists check failed: " . $e->getMessage());
    }

    if (!$tableExists) {
        return createAutomatedWarningsTable($pdo);
    }

    try {
        $pdo->query("SELECT 1 FROM automated_warnings LIMIT 1");
        return true;
    } catch (PDOException $e) {
        $msg = strtolower($e->getMessage());
        $isEngineCorruption = (strpos($msg, "doesn't exist in engine") !== false) || (strpos($msg, '1932') !== false);
        if (!$isEngineCorruption) {
            error_log("automated_warnings health check failed: " . $e->getMessage());
            return false;
        }

        error_log("automated_warnings table unhealthy (1932). Attempting rebuild.");
        try {
            $pdo->exec("DROP TABLE IF EXISTS automated_warnings");
        } catch (PDOException $dropEx) {
            error_log("automated_warnings drop failed during rebuild: " . $dropEx->getMessage());
        }
        return createAutomatedWarningsTable($pdo);
    }
}

function createAutomatedWarningsTable(PDO $pdo): bool {
    $sql = "
        CREATE TABLE IF NOT EXISTS automated_warnings (
            id INT(11) NOT NULL AUTO_INCREMENT,
            source VARCHAR(50) NOT NULL COMMENT 'pagasa, phivolcs',
            type VARCHAR(100) DEFAULT NULL,
            title VARCHAR(255) NOT NULL,
            content TEXT NOT NULL,
            severity VARCHAR(20) DEFAULT 'medium' COMMENT 'low, medium, high, critical',
            status VARCHAR(20) DEFAULT 'pending' COMMENT 'pending, published, archived',
            received_at DATETIME DEFAULT CURRENT_TIMESTAMP(),
            published_at DATETIME DEFAULT NULL,
            PRIMARY KEY (id),
            KEY idx_source (source),
            KEY idx_status (status),
            KEY idx_received_at (received_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";
    try {
        $pdo->exec($sql);
        return true;
    } catch (PDOException $e) {
        error_log("automated_warnings create failed: " . $e->getMessage());
        return false;
    }
}

function ensureAutomatedWarningsRuntimeTable(PDO $pdo): bool {
    $sql = "
        CREATE TABLE IF NOT EXISTS automated_warnings_runtime (
            id INT(11) NOT NULL AUTO_INCREMENT,
            source VARCHAR(50) NOT NULL COMMENT 'pagasa, phivolcs, ai',
            type VARCHAR(100) DEFAULT NULL,
            title VARCHAR(255) NOT NULL,
            content TEXT NOT NULL,
            severity VARCHAR(20) DEFAULT 'medium',
            status VARCHAR(20) DEFAULT 'pending',
            received_at DATETIME DEFAULT CURRENT_TIMESTAMP(),
            published_at DATETIME DEFAULT NULL,
            PRIMARY KEY (id),
            KEY idx_source (source),
            KEY idx_status (status),
            KEY idx_received_at (received_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";
    try {
        $pdo->exec($sql);
        return true;
    } catch (PDOException $e) {
        error_log("automated_warnings_runtime create failed: " . $e->getMessage());
        return false;
    }
}

function resolveAutomatedWarningsTable(PDO $pdo): string {
    if (ensureAutomatedWarningsTableHealthy($pdo)) {
        return 'automated_warnings';
    }
    ensureAutomatedWarningsRuntimeTable($pdo);
    return 'automated_warnings_runtime';
}

function shouldAutoBroadcastCriticalWarning(string $type, string $severity): bool {
    $sev = strtolower(trim($severity));
    if (!in_array($sev, ['high', 'critical', 'extreme'], true)) {
        return false;
    }

    $typeKey = strtolower(trim($type));
    $criticalTypes = [
        'earthquake', 'tsunami',
        'typhoon', 'heavy_rain', 'strong_winds', 'thunderstorm', 'flooding', 'landslide',
        'weather'
    ];
    return in_array($typeKey, $criticalTypes, true);
}

function buildCriticalWarningTemplate(string $domain, bool $isMock = false): array {
    $timestamp = date('Y-m-d H:i:s');
    if ($domain === 'earthquake') {
        return [
            'source' => 'phivolcs',
            'type' => 'earthquake',
            'title' => ($isMock ? '[MOCK] ' : '') . 'CRITICAL EARTHQUAKE ALERT',
            'content' => "Critical earthquake activity detected. This is an emergency safety broadcast to all citizens.\n\nAction steps:\n1) DROP, COVER, and HOLD.\n2) Move away from glass, shelves, and power lines.\n3) Evacuate damaged structures after shaking stops.\n4) Wait for LGU and rescue advisories.\n\nTimestamp: {$timestamp}"
        ];
    }

    return [
        'source' => 'pagasa',
        'type' => 'weather',
        'title' => ($isMock ? '[MOCK] ' : '') . 'CRITICAL WEATHER ALERT',
        'content' => "Critical weather conditions detected (extreme rain/wind risk). This is an emergency safety broadcast to all citizens.\n\nAction steps:\n1) Stay indoors unless evacuation is required.\n2) Avoid flood-prone and landslide-prone areas.\n3) Prepare go-bags and emergency contacts.\n4) Follow official LGU advisories immediately.\n\nTimestamp: {$timestamp}"
    ];
}

function ensureNotificationQueueTableForAutomatedWarnings(PDO $pdo): void {
    $sql = "
        CREATE TABLE IF NOT EXISTS notification_queue (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            log_id BIGINT UNSIGNED NOT NULL,
            recipient_id BIGINT UNSIGNED NULL,
            recipient_type VARCHAR(40) NOT NULL DEFAULT 'unknown',
            recipient_value VARCHAR(255) NOT NULL DEFAULT '',
            channel VARCHAR(20) NOT NULL DEFAULT 'push',
            title VARCHAR(255) NOT NULL DEFAULT '',
            message TEXT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'pending',
            delivery_status VARCHAR(20) NULL,
            error_message TEXT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            processed_at DATETIME NULL,
            delivered_at DATETIME NULL,
            INDEX idx_queue_status_created (status, created_at),
            INDEX idx_queue_log_id (log_id),
            INDEX idx_queue_channel_status (channel, status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";

    try {
        $pdo->exec($sql);
        $pdo->query("SELECT 1 FROM notification_queue LIMIT 1");
    } catch (Throwable $e) {
        $msg = strtolower($e->getMessage());
        $isEngineIssue = (strpos($msg, '1932') !== false)
            || (strpos($msg, '1813') !== false)
            || (strpos($msg, "doesn't exist in engine") !== false)
            || (strpos($msg, 'tablespace for table') !== false);

        if ($isEngineIssue) {
            try {
                $pdo->exec("DROP TABLE IF EXISTS notification_queue");
                $pdo->exec($sql);
                $pdo->query("SELECT 1 FROM notification_queue LIMIT 1");
                return;
            } catch (Throwable $rebuildEx) {
                error_log("notification_queue rebuild failed: " . $rebuildEx->getMessage());
                return;
            }
        }

        error_log("notification_queue health check failed: " . $e->getMessage());
    }
}

function ensureNotificationLogsTableForAutomatedWarnings(PDO $pdo, string $tableName): bool {
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $tableName)) {
        return false;
    }
    try {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS {$tableName} (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                channel VARCHAR(64) NOT NULL DEFAULT '',
                message TEXT NULL,
                recipient VARCHAR(255) NULL,
                recipients TEXT NULL,
                priority VARCHAR(32) NOT NULL DEFAULT 'medium',
                status VARCHAR(32) NOT NULL DEFAULT 'pending',
                sent_at DATETIME NULL,
                sent_by VARCHAR(120) NULL,
                ip_address VARCHAR(64) NULL,
                response LONGTEXT NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_status_sent_at (status, sent_at),
                INDEX idx_channel (channel)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $existingCols = [];
        try {
            $colsStmt = $pdo->query("SHOW COLUMNS FROM {$tableName}");
            $colRows = $colsStmt ? $colsStmt->fetchAll(PDO::FETCH_ASSOC) : [];
            foreach ($colRows as $colRow) {
                $field = strtolower((string)($colRow['Field'] ?? ''));
                if ($field !== '') {
                    $existingCols[$field] = true;
                }
            }
        } catch (Throwable $colEx) {
            error_log("{$tableName} column scan failed: " . $colEx->getMessage());
        }

        $columnPatches = [
            'recipients' => "ALTER TABLE {$tableName} ADD COLUMN recipients TEXT NULL AFTER recipient",
            'sent_by' => "ALTER TABLE {$tableName} ADD COLUMN sent_by VARCHAR(120) NULL AFTER sent_at",
            'created_at' => "ALTER TABLE {$tableName} ADD COLUMN created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP",
        ];
        foreach ($columnPatches as $columnName => $sql) {
            if (!isset($existingCols[$columnName])) {
                try {
                    $pdo->exec($sql);
                } catch (Throwable $patchEx) {
                    error_log("{$tableName} add column {$columnName} failed: " . $patchEx->getMessage());
                }
            }
        }

        $pdo->query("SELECT 1 FROM {$tableName} LIMIT 1");
        return true;
    } catch (Throwable $e) {
        error_log("{$tableName} health check failed: " . $e->getMessage());
        return false;
    }
}

function resolveNotificationLogsTableForAutomatedWarnings(PDO $pdo): string {
    if (ensureNotificationLogsTableForAutomatedWarnings($pdo, 'notification_logs')) {
        return 'notification_logs';
    }
    ensureNotificationLogsTableForAutomatedWarnings($pdo, 'notification_logs_runtime');
    return 'notification_logs_runtime';
}

function getAutomatedWarningChannels(PDO $pdo): array {
    $defaultChannels = ['sms', 'email', 'push', 'pa'];
    try {
        $stmt = $pdo->query("SELECT notification_channels FROM warning_settings ORDER BY id DESC LIMIT 1");
        $raw = (string)($stmt ? $stmt->fetchColumn() : '');
        if ($raw === '') {
            return $defaultChannels;
        }
        $channels = array_values(array_filter(array_map('trim', explode(',', strtolower($raw)))));
        $allowed = ['sms', 'email', 'push', 'pa'];
        $channels = array_values(array_intersect($channels, $allowed));
        return !empty($channels) ? $channels : $defaultChannels;
    } catch (Throwable $e) {
        return $defaultChannels;
    }
}

function resolveAlertCategoryId(PDO $pdo, string $type): ?int {
    $categoryName = 'General';
    if (in_array(strtolower($type), ['earthquake', 'tsunami'], true)) {
        $categoryName = 'Earthquake';
    } elseif (in_array(strtolower($type), ['weather', 'typhoon', 'heavy_rain', 'strong_winds', 'thunderstorm', 'flooding', 'landslide'], true)) {
        $categoryName = 'Weather';
    }

    try {
        $stmt = $pdo->prepare("SELECT id FROM alert_categories WHERE LOWER(name) = LOWER(?) LIMIT 1");
        $stmt->execute([$categoryName]);
        $id = $stmt->fetchColumn();
        return $id !== false ? (int)$id : null;
    } catch (Throwable $e) {
        return null;
    }
}

function autoPublishCriticalWarningToCitizens(PDO $pdo, array $warning, ?int $adminId = null): array {
    $channels = getAutomatedWarningChannels($pdo);
    ensureNotificationQueueTableForAutomatedWarnings($pdo);
    $logsTable = resolveNotificationLogsTableForAutomatedWarnings($pdo);

    $title = (string)($warning['title'] ?? 'Critical Warning');
    $message = buildCriticalCitizenMessage($warning);
    $severity = strtolower((string)($warning['severity'] ?? 'critical'));
    $type = (string)($warning['type'] ?? 'general');

    // Create alert row for feeds/translation.
    $alertsTable = resolveAlertsTable($pdo);
    $categoryId = resolveAlertCategoryId($pdo, $type);
    $hasSeverityCol = false;
    try {
        $hasSeverityCol = $pdo->query("SHOW COLUMNS FROM {$alertsTable} LIKE 'severity'")->rowCount() > 0;
    } catch (Throwable $e) {
        $hasSeverityCol = false;
    }

    $alertCols = ['title', 'message', 'content', 'category_id', 'status', 'created_at'];
    $alertVals = [$title, $message, $message, $categoryId, 'active'];
    $placeholders = ['?', '?', '?', '?', '?', 'NOW()'];

    $hasCategoryCol = false;
    $hasSourceCol = false;
    try {
        $hasCategoryCol = $pdo->query("SHOW COLUMNS FROM {$alertsTable} LIKE 'category'")->rowCount() > 0;
        $hasSourceCol = $pdo->query("SHOW COLUMNS FROM {$alertsTable} LIKE 'source'")->rowCount() > 0;
    } catch (Throwable $e) {
        $hasCategoryCol = false;
        $hasSourceCol = false;
    }

    if ($hasSeverityCol) {
        array_splice($alertCols, 5, 0, ['severity']);
        array_splice($alertVals, 5, 0, [ucfirst($severity)]);
        array_splice($placeholders, 5, 0, ['?']);
    }

    if ($hasCategoryCol) {
        $categoryLabel = in_array(strtolower($type), ['earthquake', 'tsunami'], true) ? 'Earthquake' : 'Weather';
        $alertCols[] = 'category';
        $alertVals[] = $categoryLabel;
        $placeholders[] = '?';
    }
    if ($hasSourceCol) {
        $alertCols[] = 'source';
        $alertVals[] = (string)($warning['source'] ?? 'automated_warning');
        $placeholders[] = '?';
    }

    $stmtAlert = $pdo->prepare("INSERT INTO {$alertsTable} (" . implode(', ', $alertCols) . ") VALUES (" . implode(', ', $placeholders) . ")");
    $stmtAlert->execute($alertVals);
    $alertId = (int)$pdo->lastInsertId();

    $recipientMeta = ['source' => 'users', 'error' => null];
    $recipients = [];
    try {
        // Load all active citizens from users table.
        $recipientsStmt = $pdo->query("
            SELECT u.id, u.email, u.phone, d.fcm_token
            FROM users u
            LEFT JOIN user_devices d ON d.user_id = u.id AND d.is_active = 1
            WHERE u.status = 'active'
        ");
        $recipients = $recipientsStmt ? $recipientsStmt->fetchAll(PDO::FETCH_ASSOC) : [];
    } catch (Throwable $e) {
        // Database is partially corrupted in this environment; do not hard-fail mock/critical flow.
        $recipientMeta['source'] = 'none';
        $recipientMeta['error'] = $e->getMessage();
        error_log('Critical warning recipient query failed: ' . $e->getMessage());
    }

    $logId = null;
    try {
        $logStmt = $pdo->prepare("
            INSERT INTO {$logsTable} (channel, message, recipients, priority, status, sent_at, sent_by, ip_address)
            VALUES (?, ?, 'all_active_citizens', ?, 'pending', NOW(), ?, ?)
        ");
        $logStmt->execute([
            implode(',', $channels),
            $message,
            $severity,
            $adminId ? ('admin_' . $adminId) : 'automated_warning_system',
            $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'
        ]);
        $logId = (int)$pdo->lastInsertId();
    } catch (Throwable $e) {
        error_log('Critical warning notification_logs insert failed: ' . $e->getMessage());
    }

    $queued = 0;
    foreach ($recipients as $recipient) {
        $recipientId = (int)($recipient['id'] ?? 0);
        foreach ($channels as $channel) {
            $recipientType = '';
            $recipientValue = '';
            if ($channel === 'sms' && !empty($recipient['phone'])) {
                $recipientType = 'phone';
                $recipientValue = (string)$recipient['phone'];
            } elseif ($channel === 'email' && !empty($recipient['email'])) {
                $recipientType = 'email';
                $recipientValue = (string)$recipient['email'];
            } elseif ($channel === 'push' && !empty($recipient['fcm_token'])) {
                $recipientType = 'fcm_token';
                $recipientValue = (string)$recipient['fcm_token'];
            } else {
                continue;
            }

            try {
                $qStmt = $pdo->prepare("
                    INSERT INTO notification_queue (log_id, recipient_id, recipient_type, recipient_value, channel, title, message, status)
                    VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')
                ");
                $qStmt->execute([(int)($logId ?? 0), $recipientId, $recipientType, $recipientValue, $channel, $title, $message]);
                $queued++;
            } catch (Throwable $e) {
                error_log('Critical warning queue insert failed: ' . $e->getMessage());
            }
        }
    }

    if (in_array('pa', $channels, true)) {
        try {
            $qStmt = $pdo->prepare("
                INSERT INTO notification_queue (log_id, recipient_id, recipient_type, recipient_value, channel, title, message, status)
                VALUES (?, NULL, 'system', 'pa_system', 'pa', ?, ?, 'pending')
            ");
            $qStmt->execute([(int)($logId ?? 0), $title, $message]);
            $queued++;
        } catch (Throwable $e) {
            error_log('Critical warning PA queue insert failed: ' . $e->getMessage());
        }
    }

    if ($logId) {
        try {
            $pdo->prepare("UPDATE {$logsTable} SET status = 'sent' WHERE id = ?")->execute([$logId]);
        } catch (Throwable $e) {
            error_log('Critical warning notification_logs update failed: ' . $e->getMessage());
        }
    }

    if ($adminId && function_exists('logAdminActivity')) {
        logAdminActivity($adminId, 'critical_warning_auto_publish', "Critical {$type} warning auto-published to all citizens. Warning ID: " . ($warning['id'] ?? 'n/a'));
    }

    return [
        'alert_id' => $alertId,
        'log_id' => $logId,
        'log_table' => $logsTable,
        'channels' => $channels,
        'recipients' => count($recipients),
        'queued_jobs' => $queued,
        'recipient_meta' => $recipientMeta
    ];
}

function safeAutoPublishCriticalWarningToCitizens(PDO $pdo, array $warning, ?int $adminId = null): array {
    try {
        return autoPublishCriticalWarningToCitizens($pdo, $warning, $adminId);
    } catch (Throwable $e) {
        // Keep warning publish successful even when recipient/log tables are unhealthy.
        error_log('Critical warning broadcast degraded mode: ' . $e->getMessage());
        return [
            'alert_id' => null,
            'log_id' => null,
            'channels' => getAutomatedWarningChannels($pdo),
            'recipients' => 0,
            'queued_jobs' => 0,
            'recipient_meta' => [
                'source' => 'none',
                'error' => $e->getMessage()
            ],
            'warning_message' => 'Warning published, but citizen broadcast is temporarily unavailable due to database table health issues.'
        ];
    }
}

function getAutomatedWarningDispatchHistory(PDO $pdo, int $limit = 20): array {
    $logsTable = resolveNotificationLogsTableForAutomatedWarnings($pdo);
    $queueReady = false;
    try {
        $chk = $pdo->query("SHOW TABLES LIKE 'notification_queue'");
        $queueReady = (bool)($chk && $chk->fetchColumn());
        if ($queueReady) {
            $pdo->query("SELECT 1 FROM notification_queue LIMIT 1");
        }
    } catch (Throwable $e) {
        $queueReady = false;
    }

    $query = $pdo->prepare("
        SELECT
            id,
            channel,
            message,
            recipients,
            priority,
            status,
            sent_at,
            created_at,
            sent_by
        FROM {$logsTable}
        WHERE
            recipients = 'all_active_citizens'
            OR sent_by = 'automated_warning_system'
            OR message LIKE 'EMERGENCY BULLETIN%'
        ORDER BY COALESCE(sent_at, created_at) DESC
        LIMIT ?
    ");
    $query->bindValue(1, $limit, PDO::PARAM_INT);
    $query->execute();
    $rows = $query->fetchAll(PDO::FETCH_ASSOC) ?: [];

    $queueByLog = [];
    $logIds = array_values(array_filter(array_map(function ($r) {
        return isset($r['id']) ? (int)$r['id'] : 0;
    }, $rows)));

    if ($queueReady && !empty($logIds)) {
        try {
            $placeholders = implode(',', array_fill(0, count($logIds), '?'));
            $q = $pdo->prepare("
                SELECT log_id, status, COUNT(*) AS cnt
                FROM notification_queue
                WHERE log_id IN ({$placeholders})
                GROUP BY log_id, status
            ");
            foreach ($logIds as $idx => $idVal) {
                $q->bindValue($idx + 1, $idVal, PDO::PARAM_INT);
            }
            $q->execute();
            while ($qr = $q->fetch(PDO::FETCH_ASSOC)) {
                $lid = (int)($qr['log_id'] ?? 0);
                if (!isset($queueByLog[$lid])) {
                    $queueByLog[$lid] = ['pending' => 0, 'sent' => 0, 'failed' => 0];
                }
                $st = strtolower((string)($qr['status'] ?? 'pending'));
                $cnt = (int)($qr['cnt'] ?? 0);
                if (!isset($queueByLog[$lid][$st])) {
                    $queueByLog[$lid][$st] = 0;
                }
                $queueByLog[$lid][$st] += $cnt;
            }
        } catch (Throwable $e) {
            $queueReady = false;
            error_log('Dispatch history queue metrics unavailable: ' . $e->getMessage());
        }
    }

    foreach ($rows as &$r) {
        $lid = (int)($r['id'] ?? 0);
        $stats = $queueByLog[$lid] ?? ['pending' => 0, 'sent' => 0, 'failed' => 0];
        $total = (int)($stats['pending'] + $stats['sent'] + $stats['failed']);
        $done = (int)($stats['sent'] + $stats['failed']);
        $r['queue_total'] = $total;
        $r['queue_sent'] = (int)$stats['sent'];
        $r['queue_failed'] = (int)$stats['failed'];
        $r['queue_pending'] = (int)$stats['pending'];
        $r['progress_pct'] = $total > 0 ? (int)round(($done / $total) * 100) : 0;
    }
    unset($r);

    return [
        'rows' => $rows,
        'meta' => [
            'logs_table' => $logsTable,
            'queue_available' => $queueReady
        ]
    ];
}

function buildCriticalCitizenMessage(array $warning): string {
    $type = strtolower(trim((string)($warning['type'] ?? 'general')));
    $base = trim((string)($warning['content'] ?? 'Critical warning detected.'));

    $prefix = "EMERGENCY BULLETIN\n";
    if (in_array($type, ['earthquake', 'tsunami'], true)) {
        $prefix .= "Type: Earthquake Emergency\n";
        $actions = "Actions: DROP, COVER, HOLD; move to safe open area after shaking; monitor LGU instructions.";
    } else {
        $prefix .= "Type: Severe Weather Emergency\n";
        $actions = "Actions: stay indoors if safe, avoid flood-prone zones, prepare go-bag, follow LGU evacuation advisories.";
    }

    return $prefix . "\n" . $base . "\n\n" . $actions;
}

function ensureAlertsTableHealthy(PDO $pdo): bool {
    $tableExists = false;
    try {
        $existsStmt = $pdo->query("SHOW TABLES LIKE 'alerts'");
        $tableExists = (bool)($existsStmt && $existsStmt->fetchColumn());
    } catch (PDOException $e) {
        error_log("alerts exists check failed: " . $e->getMessage());
    }

    if (!$tableExists) {
        return createAlertsTable($pdo, 'alerts');
    }

    try {
        $pdo->query("SELECT 1 FROM alerts LIMIT 1");
        return true;
    } catch (PDOException $e) {
        $msg = strtolower($e->getMessage());
        $isEngineCorruption = (strpos($msg, "doesn't exist in engine") !== false) || (strpos($msg, '1932') !== false);
        if (!$isEngineCorruption) {
            error_log("alerts health check failed: " . $e->getMessage());
            return false;
        }
        return false;
    }
}

function ensureAlertsRuntimeTableHealthy(PDO $pdo): bool {
    $tableExists = false;
    try {
        $existsStmt = $pdo->query("SHOW TABLES LIKE 'alerts_runtime'");
        $tableExists = (bool)($existsStmt && $existsStmt->fetchColumn());
    } catch (PDOException $e) {
        error_log("alerts_runtime exists check failed: " . $e->getMessage());
    }

    if (!$tableExists) {
        return createAlertsTable($pdo, 'alerts_runtime');
    }

    try {
        $pdo->query("SELECT 1 FROM alerts_runtime LIMIT 1");
        return true;
    } catch (PDOException $e) {
        $msg = strtolower($e->getMessage());
        $isEngineCorruption = (strpos($msg, "doesn't exist in engine") !== false)
            || (strpos($msg, '1932') !== false)
            || (strpos($msg, '1813') !== false)
            || (strpos($msg, 'tablespace for table') !== false);
        if (!$isEngineCorruption) {
            error_log("alerts_runtime health check failed: " . $e->getMessage());
            return false;
        }

        error_log("alerts_runtime table unhealthy. Attempting rebuild.");
        try {
            $pdo->exec("DROP TABLE IF EXISTS alerts_runtime");
        } catch (PDOException $dropEx) {
            error_log("alerts_runtime drop failed during rebuild: " . $dropEx->getMessage());
        }
        return createAlertsTable($pdo, 'alerts_runtime');
    }
}

function createAlertsTable(PDO $pdo, string $tableName): bool {
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $tableName)) {
        return false;
    }
    $sql = "
        CREATE TABLE IF NOT EXISTS {$tableName} (
            id INT(11) NOT NULL AUTO_INCREMENT,
            category_id INT(11) DEFAULT NULL,
            incident_id INT(11) DEFAULT NULL,
            title VARCHAR(255) NOT NULL,
            message TEXT NOT NULL,
            content TEXT DEFAULT NULL,
            category VARCHAR(100) DEFAULT NULL,
            area VARCHAR(255) DEFAULT NULL,
            location VARCHAR(255) DEFAULT NULL,
            latitude DECIMAL(10,8) DEFAULT NULL,
            longitude DECIMAL(11,8) DEFAULT NULL,
            source VARCHAR(100) DEFAULT NULL,
            severity VARCHAR(20) DEFAULT NULL,
            status VARCHAR(20) DEFAULT 'active',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP(),
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP() ON UPDATE CURRENT_TIMESTAMP(),
            PRIMARY KEY (id),
            KEY idx_status (status),
            KEY idx_created_at (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";
    try {
        $pdo->exec($sql);
        return true;
    } catch (PDOException $e) {
        $msg = strtolower($e->getMessage());
        $isTablespaceConflict = (strpos($msg, '1813') !== false) || (strpos($msg, 'tablespace for table') !== false);
        if ($isTablespaceConflict) {
            try {
                $pdo->exec("DROP TABLE IF EXISTS {$tableName}");
                $pdo->exec($sql);
                return true;
            } catch (PDOException $retryEx) {
                error_log("{$tableName} recreate failed: " . $retryEx->getMessage());
                return false;
            }
        }
        error_log("{$tableName} create failed: " . $e->getMessage());
        return false;
    }
}

function resolveAlertsTable(PDO $pdo): string {
    if (ensureAlertsTableHealthy($pdo)) {
        return 'alerts';
    }
    ensureAlertsRuntimeTableHealthy($pdo);
    return 'alerts_runtime';
}
?>

