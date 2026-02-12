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
        
        // Handle JSON toggle action
        if ($isJson && isset($data['action']) && $data['action'] === 'toggle') {
            $source = $data['source'] ?? '';
            $enabled = $data['enabled'] ?? false;
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
        } else {
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
        }
    } catch (Exception $e) {
        ob_clean();
        error_log("POST Request Error: " . $e->getMessage());
        http_response_code(500);
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
        
        $stmt = $pdo->query("
            SELECT id, source, type, title, content, severity, status, received_at
            FROM automated_warnings
            ORDER BY received_at DESC
            LIMIT 100
        ");
        $warnings = $stmt->fetchAll();
        
        ob_clean();
        echo json_encode([
            'success' => true,
            'warnings' => $warnings
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
} elseif ($action === 'publish' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if ($pdo === null) {
            throw new Exception('Database connection not available');
        }

        $payload = json_decode(file_get_contents('php://input'), true);
        $warningId = (int)($payload['id'] ?? ($_POST['id'] ?? 0));
        if ($warningId <= 0) {
            throw new Exception('Invalid warning ID.');
        }

        $stmt = $pdo->prepare("SELECT id, status, title FROM automated_warnings WHERE id = ? LIMIT 1");
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

        $update = $pdo->prepare("UPDATE automated_warnings SET status = 'published' WHERE id = ?");
        $update->execute([$warningId]);

        $adminId = $_SESSION['admin_user_id'] ?? null;
        if ($adminId && function_exists('logAdminActivity')) {
            logAdminActivity($adminId, 'publish_warning', "Published automated warning ID {$warningId}: " . ($warning['title'] ?? ''));
        }

        ob_clean();
        echo json_encode([
            'success' => true,
            'message' => 'Warning published successfully.'
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
        // Determine title based on category
        $title = "$category: " . explode('.', $message)[0];
        
        // Check if incident_id column exists
        $stmt = $pdo->query("SHOW COLUMNS FROM alerts LIKE 'incident_id'");
        $hasIncidentId = $stmt->rowCount() > 0;
        
        // Check if category column exists (new category, not category_id)
        $stmt = $pdo->query("SHOW COLUMNS FROM alerts LIKE 'category'");
        $hasCategory = $stmt->rowCount() > 0;
        
        // Check if area column exists
        $stmt = $pdo->query("SHOW COLUMNS FROM alerts LIKE 'area'");
        $hasArea = $stmt->rowCount() > 0;
        
        if ($hasIncidentId && $hasCategory && $hasArea) {
            // Use new columns if they exist
            $stmt = $pdo->prepare("
                INSERT INTO alerts (incident_id, category, area, title, message, status, created_at)
                VALUES (?, ?, ?, ?, ?, 'active', NOW())
            ");
            $stmt->execute([$incidentId, $category, $area, $title, $message]);
        } else {
            // Fallback to existing structure
            $stmt = $pdo->prepare("
                INSERT INTO alerts (title, message, status, created_at)
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
?>

