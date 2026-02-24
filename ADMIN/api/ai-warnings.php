<?php
/**
 * AI-Powered Auto Warning System API
 * Analyzes weather and earthquake data using Gemini AI to automatically send warnings
 * Automatically sends SMS/Email/Push notifications to subscribed users
 *
 * SETUP CRON JOBS for automatic checking:
 *
 * 1. For dangerous conditions alerts (every 30 minutes):
 *    Cron: 0,30 * * * * curl -s "https://emergency-comm.alertaraqc.com//EMERGENCY-COM/ADMIN/api/ai-warnings.php?action=check&cron=true" > /dev/null 2>&1
 *
 * 2. For weather analysis auto-send (every hour, or as configured):
 *    Cron: 0 * * * * curl -s "https://emergency-comm.alertaraqc.com//EMERGENCY-COM/ADMIN/api/ai-warnings.php?action=sendWeatherAnalysis&cron=true" > /dev/null 2>&1
 *
 * This will check for dangerous conditions and send weather analysis automatically.
 */

// Start output buffering to prevent any accidental output
ob_start();

// Set JSON header
header('Content-Type: application/json; charset=utf-8');

// Error handler to ensure JSON is always returned
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error !== NULL && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        ob_clean(); // Clear any output
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Fatal error: ' . $error['message'], 'file' => $error['file'], 'line' => $error['line']]);
        exit();
    }
});

try {
    require_once 'db_connect.php';
    require_once 'secure-api-config.php';
    require_once 'gemini-api-wrapper.php';  // NEW: Auto-rotation support
    require_once 'alert-translation-helper.php';
    require_once 'activity_logger.php';
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to load required files: ' . $e->getMessage()]);
    exit();
} catch (Error $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Fatal error loading files: ' . $e->getMessage()]);
    exit();
}

session_start();

// Check if user is logged in (except for automated cron jobs)
$isCronJob = isset($_GET['cron']) && $_GET['cron'] === 'true';
if (!$isCronJob && (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true)) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Handle action from GET, POST (form data), or JSON body
$action = $_GET['action'] ?? '';
if (empty($action) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if it's JSON POST
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    if (strpos($contentType, 'application/json') !== false) {
        $jsonData = json_decode(file_get_contents('php://input'), true);
        $action = $jsonData['action'] ?? '';
    } else {
        // Form data POST
        $action = $_POST['action'] ?? '';
    }
}

try {
    switch ($action) {
        case 'getSettings':
            try {
                ob_clean(); // Ensure clean output
                getAISettings();
                if (ob_get_level()) {
                    ob_end_flush(); // Flush output buffer
                }
            } catch (Exception $e) {
                ob_clean();
                error_log("Exception in getAISettings: " . $e->getMessage());
                error_log("Stack trace: " . $e->getTraceAsString());
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Error loading settings: ' . $e->getMessage()]);
                exit();
            } catch (Error $e) {
                ob_clean();
                error_log("Fatal error in getAISettings: " . $e->getMessage());
                error_log("Stack trace: " . $e->getTraceAsString());
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Fatal error loading settings: ' . $e->getMessage()]);
                exit();
            }
            break;

        case 'diagnostic':
            diagnosticCheck();
            break;

        case 'test':
            try {
                ob_clean();
                sendTestWarning();
                if (ob_get_level()) {
                    ob_end_flush();
                }
            } catch (Exception $e) {
                ob_clean();
                error_log("Exception in sendTestWarning: " . $e->getMessage());
                error_log("Stack trace: " . $e->getTraceAsString());
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Error sending test warning: ' . $e->getMessage()]);
                exit();
            } catch (Error $e) {
                ob_clean();
                error_log("Fatal error in sendTestWarning: " . $e->getMessage());
                error_log("Stack trace: " . $e->getTraceAsString());
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Fatal error sending test warning: ' . $e->getMessage()]);
                exit();
            }
            break;

        case 'check':
            checkAndSendWarnings();
            break;

        case 'sendWeatherAnalysis':
            try {
                ob_clean();
                sendWeatherAnalysisAuto();
                if (ob_get_level()) {
                    ob_end_flush();
                }
            } catch (Exception $e) {
                ob_clean();
                error_log("Exception in sendWeatherAnalysisAuto: " . $e->getMessage());
                error_log("Stack trace: " . $e->getTraceAsString());
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Error sending weather analysis: ' . $e->getMessage()]);
                exit();
            } catch (Error $e) {
                ob_clean();
                error_log("Fatal error in sendWeatherAnalysisAuto: " . $e->getMessage());
                error_log("Stack trace: " . $e->getTraceAsString());
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Fatal error sending weather analysis: ' . $e->getMessage()]);
                exit();
            }
            break;

        case 'getWeatherAnalysis':
            try {
                ob_clean(); // Ensure clean output
                getWeatherAnalysis();
                if (ob_get_level()) {
                    ob_end_flush(); // Flush output buffer
                }
            } catch (Exception $e) {
                ob_clean();
                error_log("Exception in getWeatherAnalysis: " . $e->getMessage());
                error_log("Stack trace: " . $e->getTraceAsString());
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Error loading analysis: ' . $e->getMessage()]);
                exit();
            } catch (Error $e) {
                ob_clean();
                error_log("Fatal error in getWeatherAnalysis: " . $e->getMessage());
                error_log("Stack trace: " . $e->getTraceAsString());
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Fatal error loading analysis: ' . $e->getMessage()]);
                exit();
            }
            break;

        default:
            try {
                ob_clean();
                saveAISettings();
                if (ob_get_level()) {
                    ob_end_flush();
                }
            } catch (Exception $e) {
                ob_clean();
                error_log("Exception in saveAISettings: " . $e->getMessage());
                error_log("Stack trace: " . $e->getTraceAsString());
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Error saving settings: ' . $e->getMessage()]);
                exit();
            } catch (Error $e) {
                ob_clean();
                error_log("Fatal error in saveAISettings: " . $e->getMessage());
                error_log("Stack trace: " . $e->getTraceAsString());
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Fatal error saving settings: ' . $e->getMessage()]);
                exit();
            }
            break;
    }
} catch (Exception $e) {
    error_log("AI Warnings API Error: " . $e->getMessage());
    error_log("AI Warnings API Stack Trace: " . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
    exit();
} catch (Error $e) {
    error_log("AI Warnings API Fatal Error: " . $e->getMessage());
    error_log("AI Warnings API Stack Trace: " . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Fatal error: ' . $e->getMessage()]);
    exit();
}

function getAISettings() {
    global $pdo;

    // Clear any previous output
    if (ob_get_level()) {
        ob_clean();
    }

    // Check secure config first (before database check, as it might not need DB)
    $secureApiKey = null;
    try {
        if (function_exists('getGeminiApiKey')) {
            $secureApiKey = getGeminiApiKey('default');
            // Also check for analysis key
            if (empty($secureApiKey)) {
                $secureApiKey = getGeminiApiKey('analysis');
            }
            if (!empty($secureApiKey)) {
                error_log("getAISettings: Found API key from config, length: " . strlen($secureApiKey));
            } else {
                error_log("getAISettings: No API key found in config");
            }
        } else {
            error_log("getAISettings: getGeminiApiKey function not found");
        }
    } catch (Exception $e) {
        error_log("Error getting Gemini API key in getAISettings: " . $e->getMessage());
    } catch (Error $e) {
        error_log("Fatal error getting Gemini API key in getAISettings: " . $e->getMessage());
    }

    // If database is not available, return default settings
    if ($pdo === null) {
        $defaultSettings = [
            'gemini_api_key' => $secureApiKey ? (str_repeat('*', max(0, strlen($secureApiKey) - 4)) . substr($secureApiKey, -4)) : '',
            'ai_enabled' => false,
            'ai_check_interval' => 30,
            'wind_threshold' => 60,
            'rain_threshold' => 20,
            'earthquake_threshold' => 5.0,
            'warning_types' => 'heavy_rain,flooding,earthquake,strong_winds,tsunami,landslide,thunderstorm,ash_fall,fire_incident,typhoon',
            'monitored_areas' => 'Quezon City\nManila\nMakati',
            'ai_channels' => 'sms,email,pa',
            'weather_analysis_auto_send' => false,
            'weather_analysis_interval' => 60,
            'weather_analysis_verification_key' => '',
            'api_key_source' => $secureApiKey ? 'secure_config' : 'none'
        ];
        try {
            echo json_encode(['success' => true, 'settings' => $defaultSettings], JSON_UNESCAPED_UNICODE);
        } catch (Exception $e) {
            error_log("JSON encoding error in getAISettings (no DB): " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error encoding settings']);
        }
        return;
    }

    $settings = false;
    try {
        // Try to check if table exists first
        $tableCheck = $pdo->query("SHOW TABLES LIKE 'ai_warning_settings'");
        if ($tableCheck->rowCount() > 0) {
            $stmt = $pdo->query("SELECT * FROM ai_warning_settings ORDER BY id DESC LIMIT 1");
            $settings = $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            // Table doesn't exist, use default settings
            $settings = false;
        }
    } catch (PDOException $e) {
        error_log("Database error in getAISettings: " . $e->getMessage());
        error_log("SQL Error Code: " . $e->getCode());
        // Table might not exist, return default settings
        $settings = false;
    } catch (Exception $e) {
        error_log("General error in getAISettings: " . $e->getMessage());
        error_log("Exception type: " . get_class($e));
        $settings = false;
    } catch (Error $e) {
        error_log("Fatal error in getAISettings: " . $e->getMessage());
        error_log("Error type: " . get_class($e));
        error_log("File: " . $e->getFile() . " Line: " . $e->getLine());
        $settings = false;
    }

    if (!$settings) {
        // Return default settings, but check if secure config has API key
        $defaultSettings = [
            'gemini_api_key' => $secureApiKey ? (str_repeat('*', max(0, strlen($secureApiKey) - 4)) . substr($secureApiKey, -4)) : '',
            'ai_enabled' => false,
            'ai_check_interval' => 30,
            'wind_threshold' => 60,
            'rain_threshold' => 20,
            'earthquake_threshold' => 5.0,
            'warning_types' => 'heavy_rain,flooding,earthquake,strong_winds,tsunami,landslide,thunderstorm,ash_fall,fire_incident,typhoon',
            'monitored_areas' => 'Quezon City\nManila\nMakati',
            'ai_channels' => 'sms,email,pa',
            'weather_analysis_auto_send' => false,
            'weather_analysis_interval' => 60,
            'weather_analysis_verification_key' => '',
            'ai_weather_enabled' => 1,
            'ai_earthquake_enabled' => 1,
            'ai_disaster_monitoring_enabled' => 1,
            'ai_translation_enabled' => 1,
            'api_key_source' => $secureApiKey ? 'secure_config' : 'none'
        ];
        try {
            echo json_encode(['success' => true, 'settings' => $defaultSettings], JSON_UNESCAPED_UNICODE);
        } catch (Exception $e) {
            error_log("JSON encoding error in getAISettings (default): " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error encoding settings']);
        }
    } else {
        // Ensure all required fields exist (in case table structure is old or values are NULL)
        if (!array_key_exists('weather_analysis_auto_send', $settings) || $settings['weather_analysis_auto_send'] === null) {
            $settings['weather_analysis_auto_send'] = 0;
        } else {
            $settings['weather_analysis_auto_send'] = (int)$settings['weather_analysis_auto_send'];
        }
        if (!array_key_exists('weather_analysis_interval', $settings) || $settings['weather_analysis_interval'] === null) {
            $settings['weather_analysis_interval'] = 60;
        } else {
            $settings['weather_analysis_interval'] = (int)$settings['weather_analysis_interval'];
        }
        if (!array_key_exists('weather_analysis_verification_key', $settings) || $settings['weather_analysis_verification_key'] === null) {
            $settings['weather_analysis_verification_key'] = '';
        } else {
            $settings['weather_analysis_verification_key'] = (string)$settings['weather_analysis_verification_key'];
        }
        
        // Ensure AI analysis enabled flags exist
        if (!array_key_exists('ai_weather_enabled', $settings) || $settings['ai_weather_enabled'] === null) {
            $settings['ai_weather_enabled'] = 1;
        } else {
            $settings['ai_weather_enabled'] = (int)$settings['ai_weather_enabled'];
        }
        if (!array_key_exists('ai_earthquake_enabled', $settings) || $settings['ai_earthquake_enabled'] === null) {
            $settings['ai_earthquake_enabled'] = 1;
        } else {
            $settings['ai_earthquake_enabled'] = (int)$settings['ai_earthquake_enabled'];
        }
        if (!array_key_exists('ai_disaster_monitoring_enabled', $settings) || $settings['ai_disaster_monitoring_enabled'] === null) {
            $settings['ai_disaster_monitoring_enabled'] = 1;
        } else {
            $settings['ai_disaster_monitoring_enabled'] = (int)$settings['ai_disaster_monitoring_enabled'];
        }
        if (!array_key_exists('ai_translation_enabled', $settings) || $settings['ai_translation_enabled'] === null) {
            $settings['ai_translation_enabled'] = 1;
        } else {
            $settings['ai_translation_enabled'] = (int)$settings['ai_translation_enabled'];
        }

        // If secure config has API key and database doesn't, use secure config
        if (!empty($secureApiKey) && empty($settings['gemini_api_key'])) {
            $settings['gemini_api_key'] = str_repeat('*', max(0, strlen($secureApiKey) - 4)) . substr($secureApiKey, -4);
            $settings['api_key_source'] = 'secure_config';
        } elseif (!empty($settings['gemini_api_key'])) {
            // Mask API key for security (only show last 4 characters)
            $apiKey = $settings['gemini_api_key'];
            $settings['gemini_api_key'] = str_repeat('*', max(0, strlen($apiKey) - 4)) . substr($apiKey, -4);
            $settings['api_key_source'] = 'database';
        } else {
            $settings['api_key_source'] = 'none';
        }

        // Ensure JSON encoding works properly
        try {
            $output = json_encode(['success' => true, 'settings' => $settings], JSON_UNESCAPED_UNICODE);
            if ($output === false) {
                throw new Exception('JSON encoding failed: ' . json_last_error_msg());
            }
            echo $output;
        } catch (Exception $e) {
            error_log("JSON encoding error in getAISettings: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error encoding settings: ' . $e->getMessage()]);
        }
    }

    // Safety check: if we reach here without outputting anything, output an error
    if (ob_get_level() && ob_get_length() == 0) {
        error_log("WARNING: getAISettings completed without outputting anything");
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Settings function completed without output']);
    }
}

function diagnosticCheck() {
    global $pdo;

    header('Content-Type: application/json; charset=utf-8');

    $result = [
        'success' => true,
        'checks' => []
    ];

    try {
        // Check database connection
        if ($pdo === null) {
            $result['checks']['database_connection'] = 'FAILED - PDO is null';
            $result['success'] = false;
        } else {
            $result['checks']['database_connection'] = 'OK';

            // Check if table exists
            try {
                $stmt = $pdo->query("SHOW TABLES LIKE 'ai_warning_settings'");
                if ($stmt->rowCount() > 0) {
                    $result['checks']['table_exists'] = 'OK';

                    // Get all columns
                    $stmt = $pdo->query("DESCRIBE ai_warning_settings");
                    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
                    $result['checks']['columns'] = $columns;

                    // Check for required columns
                    $requiredColumns = [
                        'weather_analysis_auto_send',
                        'weather_analysis_interval',
                        'weather_analysis_verification_key'
                    ];

                    $missingColumns = [];
                    foreach ($requiredColumns as $col) {
                        if (!in_array($col, $columns)) {
                            $missingColumns[] = $col;
                        }
                    }

                    if (empty($missingColumns)) {
                        $result['checks']['required_columns'] = 'OK - All columns exist';
                    } else {
                        $result['checks']['required_columns'] = 'MISSING: ' . implode(', ', $missingColumns);
                        $result['success'] = false;
                    }

                    // Try to fetch settings
                    try {
                        $stmt = $pdo->query("SELECT * FROM ai_warning_settings ORDER BY id DESC LIMIT 1");
                        $settings = $stmt->fetch(PDO::FETCH_ASSOC);
                        if ($settings) {
                            $result['checks']['query_test'] = 'OK';
                            $result['checks']['has_data'] = 'YES';
                            // Check if new columns exist in result
                            foreach ($requiredColumns as $col) {
                                $result['checks']['column_' . $col] = isset($settings[$col]) ? 'EXISTS (value: ' . var_export($settings[$col], true) . ')' : 'MISSING IN RESULT';
                            }
                        } else {
                            $result['checks']['query_test'] = 'OK';
                            $result['checks']['has_data'] = 'NO - Table is empty';
                        }
                    } catch (PDOException $e) {
                        $result['checks']['query_test'] = 'FAILED: ' . $e->getMessage();
                        $result['success'] = false;
                    }
                } else {
                    $result['checks']['table_exists'] = 'FAILED - Table does not exist';
                    $result['success'] = false;
                }
            } catch (PDOException $e) {
                $result['checks']['table_check'] = 'FAILED: ' . $e->getMessage();
                $result['success'] = false;
            }
        }

        // Check secure config
        if (function_exists('getGeminiApiKey')) {
            try {
                $key = getGeminiApiKey();
                $result['checks']['secure_config'] = $key ? 'OK (key found)' : 'OK (no key set)';
            } catch (Exception $e) {
                $result['checks']['secure_config'] = 'ERROR: ' . $e->getMessage();
            }
        } else {
            $result['checks']['secure_config'] = 'getGeminiApiKey() function not found';
        }

    } catch (Exception $e) {
        $result['success'] = false;
        $result['error'] = $e->getMessage();
        $result['error_file'] = $e->getFile();
        $result['error_line'] = $e->getLine();
    }

    echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}

function saveAISettings() {
    global $pdo;

    try {
        $adminId = $_SESSION['admin_user_id'] ?? null;

        // Check if database connection is available
        if ($pdo === null) {
            throw new Exception('Database connection not available');
        }

        // Create table if not exists
        try {
            $pdo->exec("CREATE TABLE IF NOT EXISTS ai_warning_settings (
                id INT AUTO_INCREMENT PRIMARY KEY,
                gemini_api_key VARCHAR(255) DEFAULT NULL,
                ai_enabled TINYINT(1) DEFAULT 0,
                ai_check_interval INT DEFAULT 30,
                wind_threshold DECIMAL(5,2) DEFAULT 60,
                rain_threshold DECIMAL(5,2) DEFAULT 20,
                earthquake_threshold DECIMAL(3,1) DEFAULT 5.0,
                warning_types TEXT DEFAULT NULL,
                monitored_areas TEXT DEFAULT NULL,
                ai_channels TEXT DEFAULT NULL,
                weather_analysis_auto_send TINYINT(1) DEFAULT 0,
                weather_analysis_interval INT DEFAULT 60,
                weather_analysis_verification_key VARCHAR(255) DEFAULT NULL,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        } catch (PDOException $e) {
            error_log("Error creating ai_warning_settings table: " . $e->getMessage());
            throw new Exception('Failed to create database table: ' . $e->getMessage());
        }

        // Add missing columns if they don't exist (for existing tables)
        // This handles cases where the table was created before all columns were added
        $columnsToAdd = [
            'gemini_api_key' => "VARCHAR(255) DEFAULT NULL FIRST",
            'weather_analysis_auto_send' => "TINYINT(1) DEFAULT 0 AFTER ai_channels",
            'weather_analysis_interval' => "INT DEFAULT 60 AFTER weather_analysis_auto_send",
            'weather_analysis_verification_key' => "VARCHAR(255) DEFAULT NULL AFTER weather_analysis_interval"
        ];

        foreach ($columnsToAdd as $columnName => $definition) {
            try {
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
                                       WHERE TABLE_SCHEMA = DATABASE()
                                       AND TABLE_NAME = 'ai_warning_settings'
                                       AND COLUMN_NAME = ?");
                $stmt->execute([$columnName]);
                $exists = $stmt->fetchColumn() > 0;

                if (!$exists) {
                    $pdo->exec("ALTER TABLE ai_warning_settings ADD COLUMN `{$columnName}` {$definition}");
                    error_log("Added missing column {$columnName} to ai_warning_settings table");
                }
            } catch (PDOException $e) {
                // Column might already exist or other error, continue
                error_log("Could not add column {$columnName}: " . $e->getMessage());
            }
        }

        $geminiApiKey = $_POST['gemini_api_key'] ?? '';
        $aiEnabled = isset($_POST['ai_enabled']) ? 1 : 0;
        $aiCheckInterval = intval($_POST['ai_check_interval'] ?? 30);
        $windThreshold = floatval($_POST['wind_threshold'] ?? 60);
        $rainThreshold = floatval($_POST['rain_threshold'] ?? 20);
        $earthquakeThreshold = floatval($_POST['earthquake_threshold'] ?? 5.0);
        $warningTypes = implode(',', $_POST['warning_types'] ?? []);
        $monitoredAreas = $_POST['monitored_areas'] ?? '';
        $aiChannels = implode(',', $_POST['ai_channels'] ?? []);
        $weatherAnalysisAutoSend = isset($_POST['weather_analysis_auto_send']) ? 1 : 0;
        $weatherAnalysisInterval = intval($_POST['weather_analysis_interval'] ?? 60);
        $weatherAnalysisVerificationKey = $_POST['weather_analysis_verification_key'] ?? '';

        // Check if settings exist
        try {
            $stmt = $pdo->query("SELECT id, gemini_api_key FROM ai_warning_settings ORDER BY id DESC LIMIT 1");
            $existing = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error querying ai_warning_settings: " . $e->getMessage());
            throw new Exception('Database query error: ' . $e->getMessage());
        }

        // Check secure config for API key if not provided
        $secureApiKey = null;
        try {
            if (function_exists('getGeminiApiKey')) {
                $secureApiKey = getGeminiApiKey('default');
                if (empty($secureApiKey)) {
                    $secureApiKey = getGeminiApiKey('analysis');
                }
            }
        } catch (Exception $e) {
            error_log("Error getting Gemini API key from secure config: " . $e->getMessage());
            // Continue without secure API key
        } catch (Error $e) {
            error_log("Fatal error getting Gemini API key: " . $e->getMessage());
            // Continue without secure API key
        }

        if ($existing) {
            // Only update API key if a new one is provided (not masked)
            $updateApiKey = $geminiApiKey;
            if (empty($geminiApiKey) || (strlen($geminiApiKey) <= 4 && strpos($geminiApiKey, '*') !== false)) {
                // API key is masked or empty, check secure config or keep existing
                if (!empty($secureApiKey)) {
                    $updateApiKey = $secureApiKey; // Use secure config API key
                } else {
                    $updateApiKey = $existing['gemini_api_key'] ?? ''; // Keep existing
                }
            }

            // If secure config has API key and database doesn't, use secure config
            if (empty($updateApiKey) && !empty($secureApiKey)) {
                $updateApiKey = $secureApiKey;
            }

            try {
                $stmt = $pdo->prepare("UPDATE ai_warning_settings SET
                    gemini_api_key = ?,
                    ai_enabled = ?,
                    ai_check_interval = ?,
                    wind_threshold = ?,
                    rain_threshold = ?,
                    earthquake_threshold = ?,
                    warning_types = ?,
                    monitored_areas = ?,
                    ai_channels = ?,
                    weather_analysis_auto_send = ?,
                    weather_analysis_interval = ?,
                    weather_analysis_verification_key = ?
                    WHERE id = ?");
                $stmt->execute([
                    $updateApiKey, $aiEnabled, $aiCheckInterval, $windThreshold, $rainThreshold,
                    $earthquakeThreshold, $warningTypes, $monitoredAreas, $aiChannels,
                    $weatherAnalysisAutoSend, $weatherAnalysisInterval, $weatherAnalysisVerificationKey,
                    $existing['id']
                ]);
            } catch (PDOException $e) {
                error_log("Error updating ai_warning_settings: " . $e->getMessage());
                throw new Exception('Failed to update settings: ' . $e->getMessage());
            }
        } else {
            // Use secure config API key if available and no key provided
            $insertApiKey = $geminiApiKey;
            if (empty($insertApiKey) && !empty($secureApiKey)) {
                $insertApiKey = $secureApiKey;
            }

            try {
                $stmt = $pdo->prepare("INSERT INTO ai_warning_settings
                    (gemini_api_key, ai_enabled, ai_check_interval, wind_threshold, rain_threshold,
                     earthquake_threshold, warning_types, monitored_areas, ai_channels,
                     weather_analysis_auto_send, weather_analysis_interval, weather_analysis_verification_key)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $insertApiKey, $aiEnabled, $aiCheckInterval, $windThreshold, $rainThreshold,
                    $earthquakeThreshold, $warningTypes, $monitoredAreas, $aiChannels,
                    $weatherAnalysisAutoSend, $weatherAnalysisInterval, $weatherAnalysisVerificationKey
                ]);
            } catch (PDOException $e) {
                error_log("Error inserting ai_warning_settings: " . $e->getMessage());
                throw new Exception('Failed to save settings: ' . $e->getMessage());
            }
        }

        // Log admin activity
        if ($adminId && function_exists('logAdminActivity')) {
            try {
                $changes = [];
                if (isset($_POST['ai_enabled'])) {
                    $changes[] = 'AI Enabled: ' . ($_POST['ai_enabled'] ? 'Yes' : 'No');
                }
                if (isset($_POST['ai_check_interval'])) {
                    $changes[] = 'Check Interval: ' . $_POST['ai_check_interval'] . ' minutes';
                }
                if (isset($_POST['warning_types'])) {
                    $changes[] = 'Warning Types: ' . implode(', ', $_POST['warning_types']);
                }
                if (isset($_POST['weather_analysis_auto_send'])) {
                    $changes[] = 'Weather Analysis Auto-Send: ' . ($_POST['weather_analysis_auto_send'] ? 'Yes' : 'No');
                }
                logAdminActivity($adminId, 'update_ai_warning_settings', 'Updated AI warning settings: ' . implode(', ', $changes));
            } catch (Exception $e) {
                error_log("Error logging admin activity: " . $e->getMessage());
                // Don't fail if logging fails
            }
        }

        ob_clean();
        echo json_encode(['success' => true, 'message' => 'AI settings saved successfully'], JSON_UNESCAPED_UNICODE);
        exit();
    } catch (Exception $e) {
        error_log("Error in saveAISettings: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
        ob_clean();
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error saving settings: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
        exit();
    } catch (Error $e) {
        error_log("Fatal error in saveAISettings: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
        ob_clean();
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Fatal error: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
        exit();
    }
}

function sendTestWarning() {
    global $pdo;

    // Check database connection
    if ($pdo === null) {
        throw new Exception('Database connection not available');
    }

    $adminId = $_SESSION['admin_user_id'] ?? null;

    // Create a test warning
    $title = "Test AI Warning - Dangerous Weather Detected";
    $content = "This is a test warning from the AI Auto Warning System. If you receive this, the system is working correctly.";

    try {
        $warningId = insertAutomatedWarningRecord($pdo, 'ai', 'test', $title, $content, 'high', 'published');

        // Log admin activity
        if ($adminId && function_exists('logAdminActivity')) {
            logAdminActivity($adminId, 'test_ai_warning', "Sent test AI warning (ID: {$warningId})");
        }

        echo json_encode([
            'success' => true,
            'message' => 'Test warning created successfully',
            'warning_id' => $warningId
        ]);
    } catch (PDOException $e) {
        error_log("Database error in sendTestWarning: " . $e->getMessage());
        throw new Exception('Database error: ' . $e->getMessage());
    }
}

function checkAndSendWarnings() {
    global $pdo;

    $adminId = $_SESSION['admin_user_id'] ?? null;
    $isCronJob = isset($_GET['cron']) && $_GET['cron'] === 'true';

    // Get AI settings
    $stmt = $pdo->query("SELECT * FROM ai_warning_settings WHERE ai_enabled = 1 ORDER BY id DESC LIMIT 1");
    $settings = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$settings || !$settings['ai_enabled']) {
        echo json_encode(['success' => false, 'message' => 'AI warnings are disabled']);
        return;
    }

    // Log activity if manual check (not cron)
    if ($adminId && !$isCronJob) {
        logAdminActivity($adminId, 'check_ai_warnings', 'Manually triggered AI warning check');
    }

    $warnings = [];

    // Use Gemini AI to analyze weather and earthquake conditions
    $warnings = array_merge($warnings, analyzeWithAI($settings));

    // Also check traditional thresholds as backup
    $warnings = array_merge($warnings, checkWeatherConditions($settings));
    $warnings = array_merge($warnings, checkEarthquakeConditions($settings));
    $warnings = array_merge($warnings, checkFloodingLandslideRisks($settings));

    // Remove duplicates based on type
    $uniqueWarnings = [];
    foreach ($warnings as $warning) {
        $key = $warning['type'] . '_' . md5($warning['title']);
        if (!isset($uniqueWarnings[$key])) {
            $uniqueWarnings[$key] = $warning;
        }
    }
    $warnings = array_values($uniqueWarnings);

    $sentCount = 0;
    $alertIds = [];

    // Save warnings to database and send notifications
    foreach ($warnings as $warning) {
        // Only send if severity is medium or higher
        if (!in_array($warning['severity'], ['medium', 'high', 'critical'])) {
            continue;
        }

        $warningId = insertAutomatedWarningRecord(
            $pdo,
            'ai',
            $warning['type'],
            $warning['title'],
            $warning['content'],
            $warning['severity'],
            'published'
        );
        $alertIds[] = $warningId;

        // Create alert entry for translation
        // First, get or create category
        $categoryName = mapWarningTypeToCategory($warning['type']);
        $stmt = $pdo->prepare("SELECT id FROM alert_categories WHERE name = ? LIMIT 1");
        $stmt->execute([$categoryName]);
        $category = $stmt->fetch();
        $categoryId = $category ? $category['id'] : null;

        // Insert alert
        $stmt = $pdo->prepare("INSERT INTO alerts
            (title, message, content, category_id, status, created_at)
            VALUES (?, ?, ?, ?, 'active', NOW())");
        $stmt->execute([
            $warning['title'],
            $warning['content'],
            $warning['content'],
            $categoryId
        ]);
        $alertId = $pdo->lastInsertId();

        // Critical weather/earthquake: broadcast to all active citizens.
        if (shouldBroadcastCriticalToAllCitizens($warning)) {
            $sentCount += queueCriticalWarningToAllCitizens($warning, $alertId, $settings, $adminId);
        } else {
            // Non-critical or non-weather/earthquake: subscriber-only distribution.
            $sentCount += sendNotificationsToSubscribers($alertId, $warning, $settings);
        }
    }

    // Log activity if warnings were generated
    if ($adminId && count($warnings) > 0) {
        $source = $isCronJob ? 'cron' : 'manual';
        logAdminActivity($adminId, 'ai_warnings_generated',
            "AI generated {$sentCount} notifications for " . count($warnings) . " warning(s) via {$source} check");
    }

    echo json_encode([
        'success' => true,
        'warnings_generated' => count($warnings),
        'notifications_sent' => $sentCount,
        'alert_ids' => $alertIds,
        'warnings' => $warnings
    ]);
}

/**
 * Use Gemini AI to analyze weather and earthquake data
 */
function analyzeWithAI($settings) {
    global $pdo;
    $warnings = [];

    $apiKey = getGeminiApiKey('analysis');
    if (empty($apiKey)) {
        $apiKey = getGeminiApiKey('default');
    }
    if (empty($apiKey)) {
        // Try to get from settings
        $apiKey = $settings['gemini_api_key'] ?? '';
    }

    if (empty($apiKey)) {
        error_log("Gemini API key not configured for AI analysis");
        return $warnings;
    }

    // Get weather data
    $weatherData = getWeatherData();
    if (empty($weatherData)) {
        return $warnings;
    }

    // Prepare prompt for Gemini AI
    $prompt = "Analyze the following weather and earthquake data for the Philippines. " .
              "Determine if there are any DANGEROUS conditions that require immediate emergency alerts. " .
              "Consider: heavy rain (>20mm/hour), strong winds (>60km/h), earthquakes (>5.0 magnitude), " .
              "flooding risks, landslide risks, typhoon conditions, thunderstorms, and other emergencies.\n\n" .
              "Weather Data:\n" . json_encode($weatherData, JSON_PRETTY_PRINT) . "\n\n" .
              "Warning Types Enabled: " . ($settings['warning_types'] ?? 'all') . "\n" .
              "Monitored Areas: " . ($settings['monitored_areas'] ?? 'Quezon City, Manila, Makati') . "\n\n" .
              "Respond in JSON format with this structure:\n" .
              "{\n" .
              "  \"warnings\": [\n" .
              "    {\n" .
              "      \"type\": \"typhoon|flooding|earthquake|landslide|heavy_rain|strong_winds|thunderstorm|fire_incident|tsunami|ash_fall\",\n" .
              "      \"title\": \"Brief alert title\",\n" .
              "      \"content\": \"Detailed warning message with location and recommendations\",\n" .
              "      \"severity\": \"medium|high|critical\",\n" .
              "      \"location\": \"City/Area name\",\n" .
              "      \"is_dangerous\": true\n" .
              "    }\n" .
              "  ]\n" .
              "}\n\n" .
              "Only include warnings where is_dangerous is true. Be conservative - only alert for real dangers.";

    try {
        $model = getGeminiModel();
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key=" . urlencode($apiKey);

        $data = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt]
                    ]
                ]
            ],
            'generationConfig' => [
                'temperature' => 0.3,
                'maxOutputTokens' => 2048,
                'topP' => 0.95,
                'topK' => 40
            ]
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            error_log("CURL Error in AI analysis: " . $curlError);
            return $warnings;
        }

        if ($httpCode === 200 && $response) {
            $responseData = json_decode($response, true);

            if (isset($responseData['candidates'][0]['content']['parts'][0]['text'])) {
                $aiResponse = trim($responseData['candidates'][0]['content']['parts'][0]['text']);

                // Extract JSON from response (might have markdown code blocks)
                if (preg_match('/```json\s*(.*?)\s*```/s', $aiResponse, $matches)) {
                    $aiResponse = $matches[1];
                } elseif (preg_match('/```\s*(.*?)\s*```/s', $aiResponse, $matches)) {
                    $aiResponse = $matches[1];
                }

                $aiData = json_decode($aiResponse, true);

                if (isset($aiData['warnings']) && is_array($aiData['warnings'])) {
                    foreach ($aiData['warnings'] as $warning) {
                        if (isset($warning['is_dangerous']) && $warning['is_dangerous']) {
                            $warnings[] = [
                                'type' => $warning['type'] ?? 'general',
                                'title' => $warning['title'] ?? 'Emergency Alert',
                                'content' => $warning['content'] ?? '',
                                'severity' => $warning['severity'] ?? 'medium',
                                'location' => $warning['location'] ?? 'Quezon City'
                            ];
                        }
                    }
                }
            }
        } else {
            // Handle API errors properly
            $errorData = json_decode($response, true);
            $errorMsg = $errorData['error']['message'] ?? "HTTP $httpCode";

            // Check for API key errors
            if ($httpCode === 401 || $httpCode === 403 ||
                strpos(strtolower($errorMsg), 'expired') !== false ||
                strpos(strtolower($errorMsg), 'invalid') !== false ||
                strpos(strtolower($errorMsg), 'api key') !== false) {
                error_log("Gemini API key error: $errorMsg (HTTP $httpCode)");
                // Return error in warnings array so it can be displayed
                $warnings[] = [
                    'type' => 'error',
                    'title' => 'API Key Error',
                    'content' => 'API key expired or invalid. Please update your Gemini API key in Automated Warnings → AI Warning Settings.',
                    'severity' => 'error',
                    'location' => 'System',
                    'is_error' => true
                ];
            } else {
                error_log("Gemini AI analysis failed: HTTP $httpCode - $errorMsg");
            }
        }
    } catch (Exception $e) {
        error_log("AI Analysis Error: " . $e->getMessage());
    }

    return $warnings;
}

/**
 * Get weather data from OpenWeatherMap
 */
function aiWeatherHttpJsonGet($url, $timeoutSeconds = 10) {
    $response = null;
    $httpCode = 0;
    $curlError = '';

    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeoutSeconds);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $response = curl_exec($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = (string)curl_error($ch);
        curl_close($ch);
    } else {
        $context = stream_context_create([
            'http' => ['timeout' => $timeoutSeconds]
        ]);
        $response = @file_get_contents($url, false, $context);
        if (isset($http_response_header[0]) && preg_match('#HTTP/\S+\s+(\d+)#', $http_response_header[0], $matches)) {
            $httpCode = (int)$matches[1];
        }
    }

    if ($response === false || $response === null || $response === '') {
        return ['success' => false, 'error' => $curlError !== '' ? $curlError : 'No weather provider response'];
    }

    $decoded = json_decode($response, true);
    if (!is_array($decoded)) {
        return ['success' => false, 'error' => 'Invalid weather provider JSON response'];
    }

    if ($httpCode >= 400) {
        return ['success' => false, 'error' => "HTTP {$httpCode}: {$response}"];
    }

    return ['success' => true, 'data' => $decoded];
}

function aiWeatherCodeToMeta($code, $isDay = 1) {
    $code = (int)$code;
    $isDayTime = ((int)$isDay) === 1;
    $iconDay = $isDayTime ? 'd' : 'n';

    if ($code === 0) return ['main' => 'Clear', 'description' => 'clear sky', 'icon' => '01' . $iconDay];
    if ($code === 1) return ['main' => 'Clouds', 'description' => 'mainly clear', 'icon' => '02' . $iconDay];
    if ($code === 2) return ['main' => 'Clouds', 'description' => 'partly cloudy', 'icon' => '03' . $iconDay];
    if ($code === 3) return ['main' => 'Clouds', 'description' => 'overcast clouds', 'icon' => '04' . $iconDay];
    if (in_array($code, [45, 48], true)) return ['main' => 'Mist', 'description' => 'fog', 'icon' => '50' . $iconDay];
    if (in_array($code, [51, 53, 55, 56, 57], true)) return ['main' => 'Drizzle', 'description' => 'drizzle', 'icon' => '09' . $iconDay];
    if (in_array($code, [61, 63, 65, 66, 67, 80, 81, 82], true)) return ['main' => 'Rain', 'description' => 'rain', 'icon' => '10' . $iconDay];
    if (in_array($code, [71, 73, 75, 77, 85, 86], true)) return ['main' => 'Snow', 'description' => 'snow', 'icon' => '13' . $iconDay];
    if (in_array($code, [95, 96, 99], true)) return ['main' => 'Thunderstorm', 'description' => 'thunderstorm', 'icon' => '11' . $iconDay];

    return ['main' => 'Clouds', 'description' => 'cloudy', 'icon' => '03' . $iconDay];
}

function aiFetchOpenMeteoCurrent($lat, $lon) {
    $url = "https://api.open-meteo.com/v1/forecast?latitude={$lat}&longitude={$lon}"
        . "&current=temperature_2m,relative_humidity_2m,apparent_temperature,precipitation,weather_code,wind_speed_10m,wind_direction_10m,wind_gusts_10m,is_day"
        . "&timezone=Asia%2FManila";

    $result = aiWeatherHttpJsonGet($url, 12);
    if (empty($result['success'])) {
        return ['error' => $result['error'] ?? 'Open-Meteo weather request failed'];
    }

    $current = $result['data']['current'] ?? null;
    if (!is_array($current)) {
        return ['error' => 'Open-Meteo response missing current weather'];
    }

    $code = (int)($current['weather_code'] ?? 0);
    $meta = aiWeatherCodeToMeta($code, (int)($current['is_day'] ?? 1));
    $timestamp = isset($current['time']) ? strtotime((string)$current['time']) : time();
    if ($timestamp === false) {
        $timestamp = time();
    }

    return [
        'coord' => ['lon' => (float)$lon, 'lat' => (float)$lat],
        'weather' => [[
            'id' => $code,
            'main' => $meta['main'],
            'description' => $meta['description'],
            'icon' => $meta['icon']
        ]],
        'main' => [
            'temp' => round((float)($current['temperature_2m'] ?? 0), 1),
            'feels_like' => round((float)($current['apparent_temperature'] ?? ($current['temperature_2m'] ?? 0)), 1),
            'humidity' => (int)round((float)($current['relative_humidity_2m'] ?? 0))
        ],
        'wind' => [
            'speed' => round(((float)($current['wind_speed_10m'] ?? 0)) / 3.6, 2), // OpenWeather format (m/s)
            'deg' => (int)round((float)($current['wind_direction_10m'] ?? 0)),
            'gust' => isset($current['wind_gusts_10m']) ? round(((float)$current['wind_gusts_10m']) / 3.6, 2) : null
        ],
        'rain' => ['1h' => round((float)($current['precipitation'] ?? 0), 2)],
        'dt' => $timestamp,
        'sys' => ['country' => 'PH']
    ];
}

function aiFetchWeatherCurrentForLocation($lat, $lon, $apiKey) {
    $apiKey = trim((string)$apiKey);
    if ($apiKey !== '') {
        $url = "https://api.openweathermap.org/data/2.5/weather?lat={$lat}&lon={$lon}&appid={$apiKey}&units=metric";
        $result = aiWeatherHttpJsonGet($url, 10);
        if (!empty($result['success']) && is_array($result['data'])) {
            return $result['data'];
        }
    }

    // Fallback path when key is missing/invalid or OpenWeather is unreachable.
    return aiFetchOpenMeteoCurrent($lat, $lon);
}

function getWeatherData() {
    global $pdo;

    $apiKey = function_exists('getOpenWeatherApiKey') ? getOpenWeatherApiKey(true) : null;

    $weatherData = [];
    $locations = [
        ['name' => 'Quezon City', 'lat' => 14.6488, 'lon' => 121.0509],
        ['name' => 'Manila', 'lat' => 14.5995, 'lon' => 120.9842],
        ['name' => 'Makati', 'lat' => 14.5547, 'lon' => 121.0244],
    ];

    foreach ($locations as $loc) {
        $data = aiFetchWeatherCurrentForLocation($loc['lat'], $loc['lon'], $apiKey);
        if (is_array($data) && !isset($data['error'])) {
            $data['name'] = $loc['name'];
            $weatherData[$loc['name']] = $data;
        }
    }

    return $weatherData;
}

/**
 * Send notifications to all subscribed users
 */
function sendNotificationsToSubscribers($alertId, $warning, $settings) {
    global $pdo;

    $channels = explode(',', $settings['ai_channels'] ?? 'sms,email');
    $channels = array_map('trim', $channels);

    // Get all active subscribers
    $categoryName = mapWarningTypeToCategory($warning['type']);
    $stmt = $pdo->prepare("
        SELECT DISTINCT s.user_id, s.categories, s.channels, s.preferred_language,
               u.name, u.email, u.phone
        FROM subscriptions s
        LEFT JOIN users u ON u.id = s.user_id
        WHERE s.status = 'active'
        AND (s.categories LIKE ? OR s.categories = 'all' OR s.categories LIKE '%weather%' OR s.categories LIKE '%earthquake%' OR s.categories LIKE '%general%')
    ");
    $categoryPattern = "%{$categoryName}%";
    $stmt->execute([$categoryPattern]);
    $subscribers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($subscribers)) {
        return 0;
    }

    $translationHelper = new AlertTranslationHelper($pdo);
    $sentCount = 0;

    foreach ($subscribers as $subscriber) {
        $userId = $subscriber['user_id'];
        $userChannels = explode(',', $subscriber['channels'] ?? '');
        $userChannels = array_map('trim', $userChannels);

        // Get translated alert for user's preferred language
        $userLanguage = strtolower(trim((string)($subscriber['preferred_language'] ?? 'en')));
        if ($userLanguage === 'tl') {
            $userLanguage = 'fil';
        }
        if ($userLanguage === '') {
            $userLanguage = 'en';
        }
        $translatedAlert = $translationHelper->getTranslatedAlert($alertId, $userLanguage, $warning['title'], $warning['content']);

        if (!$translatedAlert) {
            // Fallback to original warning
            $translatedAlert = [
                'title' => $warning['title'],
                'message' => $warning['content'],
                'language' => 'en'
            ];
        }

        $message = $translatedAlert['title'] . "\n\n" . $translatedAlert['message'];

        // Send via each enabled channel
        foreach ($channels as $channel) {
            if (!in_array($channel, $userChannels) && !empty($userChannels)) {
                continue; // User hasn't subscribed to this channel
            }

            if ($channel === 'sms' && !empty($subscriber['phone'])) {
                sendSMSNotification($subscriber['phone'], $message, $alertId);
                $sentCount++;
            } elseif ($channel === 'email' && !empty($subscriber['email'])) {
                sendEmailNotification($subscriber['email'], $subscriber['name'], $translatedAlert['title'], $translatedAlert['message'], $alertId);
                $sentCount++;
            } elseif ($channel === 'push') {
                // Send push notification to mobile app
                if (file_exists(__DIR__ . '/push-notification-helper.php')) {
                    require_once __DIR__ . '/push-notification-helper.php';
                    if (sendPushNotification($userId, $translatedAlert['title'], $translatedAlert['message'], ['alert_id' => $alertId], $alertId)) {
                        $sentCount++;
                    }
                }
            } elseif ($channel === 'pa') {
                // PA System notification (log only)
                logPANotification($message, $alertId);
                $sentCount++;
            }
        }
    }

    return $sentCount;
}

/**
 * Send SMS notification
 */
function sendSMSNotification($phone, $message, $alertId) {
    global $pdo;

    // Log notification
    $stmt = $pdo->prepare("
        INSERT INTO notification_logs (channel, message, recipient, recipients, priority, status, sent_at, sent_by, ip_address)
        VALUES ('sms', ?, ?, ?, 'high', 'pending', NOW(), 'ai_system', '127.0.0.1')
    ");
    $stmt->execute([$message, $phone, $phone]);

    // Try to send via SMS helper if available
    if (file_exists(__DIR__ . '/../../USERS/lib/sms.php')) {
        require_once __DIR__ . '/../../USERS/lib/sms.php';
        $smsError = null;
        $smsSent = sendSMS($phone, $message, $smsError);

        if ($smsSent) {
            $stmt = $pdo->prepare("UPDATE notification_logs SET status = 'success' WHERE id = ?");
            $stmt->execute([$pdo->lastInsertId()]);
        } else {
            error_log("SMS sending failed for $phone: " . ($smsError ?? 'Unknown error'));
        }
    } else {
        error_log("SMS notification queued for $phone (SMS library not available)");
    }
}

/**
 * Send Email notification
 */
function sendEmailNotification($email, $name, $subject, $body, $alertId) {
    global $pdo;

    // Log notification
    $stmt = $pdo->prepare("
        INSERT INTO notification_logs (channel, message, recipient, recipients, priority, status, sent_at, sent_by, ip_address)
        VALUES ('email', ?, ?, ?, 'high', 'pending', NOW(), 'ai_system', '127.0.0.1')
    ");
    $emailMessage = $subject . "\n\n" . $body;
    $stmt->execute([$emailMessage, $email, $email]);

    // Try to send email
    $emailSent = false;

    // Try PHPMailer if available
    if (file_exists(__DIR__ . '/../../USERS/lib/mail.php')) {
        require_once __DIR__ . '/../../USERS/lib/mail.php';
        $error = null;
        $emailSent = sendSMTPMail($email, $subject, $body, false, $error);
    } else {
        // Fallback to PHP mail()
        $headers = "From: noreply@emergency-com.local\r\n";
        $headers .= "Reply-To: support@emergency-com.local\r\n";
        $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
        $emailSent = @mail($email, $subject, $body, $headers);
    }

    if ($emailSent) {
        $stmt = $pdo->prepare("UPDATE notification_logs SET status = 'success' WHERE id = ?");
        $stmt->execute([$pdo->lastInsertId()]);
    } else {
        error_log("Email sending failed for $email");
    }
}

/**
 * Log PA System notification
 */
function logPANotification($message, $alertId) {
    global $pdo;

    $stmt = $pdo->prepare("
        INSERT INTO notification_logs (channel, message, recipient, recipients, priority, status, sent_at, sent_by, ip_address)
        VALUES ('pa', ?, 'pa_system', 'all', 'high', 'success', NOW(), 'ai_system', '127.0.0.1')
    ");
    $stmt->execute([$message]);
}

/**
 * Map warning type to alert category
 */
function mapWarningTypeToCategory($type) {
    $mapping = [
        'typhoon' => 'Weather',
        'heavy_rain' => 'Weather',
        'flooding' => 'Weather',
        'strong_winds' => 'Weather',
        'thunderstorm' => 'Weather',
        'earthquake' => 'Earthquake',
        'tsunami' => 'Earthquake',
        'landslide' => 'General',
        'fire_incident' => 'Fire',
        'ash_fall' => 'General'
    ];

    return $mapping[$type] ?? 'General';
}

function checkWeatherConditions($settings) {
    $warnings = [];

    $windThreshold = floatval($settings['wind_threshold'] ?? 60); // km/h
    $rainThreshold = floatval($settings['rain_threshold'] ?? 20); // mm/hour
    $warningTypes = explode(',', $settings['warning_types'] ?? '');

    $weatherByLocation = getWeatherData();
    if (empty($weatherByLocation) || !is_array($weatherByLocation)) {
        return $warnings;
    }

    $weatherData = $weatherByLocation['Quezon City'] ?? reset($weatherByLocation);
    if (!is_array($weatherData)) {
        return $warnings;
    }

    if (isset($weatherData['wind']['speed'])) {
        $windSpeedMs = floatval($weatherData['wind']['speed']); // m/s
        $windSpeedKmh = $windSpeedMs * 3.6; // Convert to km/h

        if ($windSpeedKmh >= $windThreshold && in_array('typhoon', $warningTypes)) {
            $warnings[] = [
                'type' => 'typhoon',
                'title' => "High Wind Warning - {$windSpeedKmh} km/h",
                'content' => "Dangerous wind speeds detected in Quezon City ({$windSpeedKmh} km/h). Take precautions and secure loose objects.",
                'severity' => $windSpeedKmh >= 100 ? 'critical' : ($windSpeedKmh >= 80 ? 'high' : 'medium')
            ];
        }
    }

    if (isset($weatherData['rain']['1h'])) {
        $rainfall = floatval($weatherData['rain']['1h']); // mm in last hour

        if ($rainfall >= $rainThreshold) {
            if (in_array('flooding', $warningTypes)) {
                $warnings[] = [
                    'type' => 'flooding',
                    'title' => "Heavy Rainfall Alert - {$rainfall}mm/hour",
                    'content' => "Heavy rainfall detected in Quezon City ({$rainfall}mm/hour). Risk of flooding in low-lying areas. Avoid flood-prone areas.",
                    'severity' => $rainfall >= 50 ? 'critical' : ($rainfall >= 30 ? 'high' : 'medium')
                ];
            }

            if (in_array('landslide', $warningTypes) && $rainfall >= 30) {
                $warnings[] = [
                    'type' => 'landslide',
                    'title' => "Landslide Risk Alert",
                    'content' => "Heavy rainfall ({$rainfall}mm/hour) increases landslide risk in hilly areas of Quezon City. Residents near slopes should be alert.",
                    'severity' => $rainfall >= 50 ? 'critical' : 'high'
                ];
            }
        }
    }

    return $warnings;
}

/**
 * Automatically send AI weather analysis to users via mass notifications
 */
function sendWeatherAnalysisAuto() {
    global $pdo;

    $adminId = $_SESSION['admin_user_id'] ?? null;
    $isCronJob = isset($_GET['cron']) && $_GET['cron'] === 'true';

    // Get AI settings
    try {
        $stmt = $pdo->query("SELECT * FROM ai_warning_settings ORDER BY id DESC LIMIT 1");
        $settings = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        ob_clean();
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
        return;
    }

    // Use default settings if none exist
    if (!$settings) {
        $settings = [
            'gemini_api_key' => '',
            'ai_channels' => 'sms,email,pa',
            'weather_analysis_auto_send' => 0,
            'weather_analysis_interval' => 60,
            'weather_analysis_verification_key' => ''
        ];
    }

    // Only check auto-send setting for cron jobs (manual sends are always allowed)
    if ($isCronJob && !$settings['weather_analysis_auto_send']) {
        ob_clean();
        echo json_encode(['success' => false, 'message' => 'Weather analysis auto-send is disabled'], JSON_UNESCAPED_UNICODE);
        return;
    }

    // Get API key - prefer analysis key for analysis functions
    $apiKey = getGeminiApiKey('analysis');
    if (empty($apiKey)) {
        $apiKey = getGeminiApiKey('default');
    }
    if (empty($apiKey)) {
        $apiKey = $settings['gemini_api_key'] ?? '';
    }

    if (empty($apiKey)) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Gemini API key not configured']);
        return;
    }

    // Get weather data for primary location (Quezon City)
    $weatherData = [];
    try {
        if (function_exists('getWeatherData')) {
            $weatherData = getWeatherData();
        }
    } catch (Exception $e) {
        error_log("Error getting weather data: " . $e->getMessage());
    }

    if (empty($weatherData) || !is_array($weatherData)) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Unable to fetch weather data from available providers (OpenWeather/Open-Meteo). Check internet/API configuration and try again.']);
        return;
    }

    // Use Quezon City as primary location
    $locationName = 'Quezon City';
    $weather = $weatherData[$locationName] ?? reset($weatherData);

    if (!$weather || !is_array($weather)) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'No weather data available for analysis']);
        return;
    }

    // Build disaster monitoring analysis prompt
    $temp = $weather['main']['temp'] ?? 0;
    $humidity = $weather['main']['humidity'] ?? 0;
    $condition = $weather['weather'][0]['description'] ?? 'Unknown';
    $windSpeed = isset($weather['wind']['speed']) ? round($weather['wind']['speed'] * 3.6, 1) : 0;

    $prompt = "You are an emergency weather analyst for {$locationName}, Philippines. Analyze:

CURRENT: Temp {$temp}°C, Humidity {$humidity}%, {$condition}, Wind {$windSpeed} km/h

Provide analysis in this format:

**SUMMARY:**
[1-2 sentence summary]

**RECOMMENDATIONS:**
[3-5 action items]

**RISK LEVEL:**
[LOW/MEDIUM/HIGH] - [Brief explanation]

Keep concise and actionable for public communication.";

    // Generate AI weather analysis
    try {
        $model = getGeminiModel();
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key=" . urlencode($apiKey);

        $data = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt]
                    ]
                ]
            ],
            'generationConfig' => [
                'temperature' => 0.7,
                'maxOutputTokens' => 2048,
                'topP' => 0.95,
                'topK' => 40
            ]
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError || $httpCode !== 200) {
            echo json_encode(['success' => false, 'message' => 'Failed to generate weather analysis']);
            return;
        }

        $responseData = json_decode($response, true);

        if (!isset($responseData['candidates'][0]['content']['parts'][0]['text'])) {
            echo json_encode(['success' => false, 'message' => 'Invalid response from AI']);
            return;
        }

        $analysis = trim($responseData['candidates'][0]['content']['parts'][0]['text']);

        // Add verification key if set
        $verificationKey = $settings['weather_analysis_verification_key'] ?? '';
        if (!empty($verificationKey)) {
            $analysis .= "\n\nVerification Key: " . $verificationKey;
        }

        // Create alert title and content
        $alertTitle = "AI Weather Analysis - {$locationName}";
        $alertContent = "Current Weather Status:\n" . $analysis;

        // Get or create Weather category
        $stmt = $pdo->prepare("SELECT id FROM alert_categories WHERE name = 'Weather' LIMIT 1");
        $stmt->execute();
        $category = $stmt->fetch();
        $categoryId = $category ? $category['id'] : null;

        // Insert alert
        $stmt = $pdo->prepare("INSERT INTO alerts
            (title, message, content, category_id, status, created_at)
            VALUES (?, ?, ?, ?, 'active', NOW())");
        $stmt->execute([
            $alertTitle,
            $alertContent,
            $alertContent,
            $categoryId
        ]);
        $alertId = $pdo->lastInsertId();

        // Send via mass notification channels
        $channels = explode(',', $settings['ai_channels'] ?? 'sms,email');
        $channels = array_map('trim', $channels);

        // Get all active subscribers for weather alerts
        $stmt = $pdo->prepare("
            SELECT DISTINCT s.user_id, s.channels, s.preferred_language,
                   u.name, u.email, u.phone
            FROM subscriptions s
            LEFT JOIN users u ON u.id = s.user_id
            WHERE s.status = 'active'
            AND (s.categories LIKE '%Weather%' OR s.categories = 'all' OR s.categories LIKE '%general%')
        ");
        $stmt->execute();
        $subscribers = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $sentCount = 0;
        $translationHelper = new AlertTranslationHelper($pdo);

        foreach ($subscribers as $subscriber) {
            $userId = $subscriber['user_id'];
            $userChannels = explode(',', $subscriber['channels'] ?? '');
            $userChannels = array_map('trim', $userChannels);

            // Get translated alert for user's preferred language
            $userLanguage = strtolower(trim((string)($subscriber['preferred_language'] ?? 'en')));
            if ($userLanguage === 'tl') {
                $userLanguage = 'fil';
            }
            if ($userLanguage === '') {
                $userLanguage = 'en';
            }
            $translatedAlert = $translationHelper->getTranslatedAlert($alertId, $userLanguage, $alertTitle, $alertContent);

            if (!$translatedAlert) {
                $translatedAlert = [
                    'title' => $alertTitle,
                    'message' => $alertContent,
                    'language' => 'en'
                ];
            }

            $message = $translatedAlert['title'] . "\n\n" . $translatedAlert['message'];

            // Send via each enabled channel
            foreach ($channels as $channel) {
                if (!empty($userChannels) && !in_array($channel, $userChannels)) {
                    continue;
                }

                if ($channel === 'sms' && !empty($subscriber['phone'])) {
                    sendSMSNotification($subscriber['phone'], $message, $alertId);
                    $sentCount++;
                } elseif ($channel === 'email' && !empty($subscriber['email'])) {
                    sendEmailNotification($subscriber['email'], $subscriber['name'], $translatedAlert['title'], $translatedAlert['message'], $alertId);
                    $sentCount++;
                } elseif ($channel === 'push') {
                    // Send push notification to mobile app
                    if (file_exists(__DIR__ . '/push-notification-helper.php')) {
                        require_once __DIR__ . '/push-notification-helper.php';
                        if (sendPushNotification($userId, $translatedAlert['title'], $translatedAlert['message'], ['alert_id' => $alertId], $alertId)) {
                            $sentCount++;
                        }
                    }
                } elseif ($channel === 'pa') {
                    logPANotification($message, $alertId);
                    $sentCount++;
                }
            }
        }

        // Log activity
        if ($adminId) {
            $source = $isCronJob ? 'cron' : 'manual';
            logAdminActivity($adminId, 'weather_analysis_sent',
                "AI Weather Analysis sent to {$sentCount} recipients via {$source}");
        }

        ob_clean();
        echo json_encode([
            'success' => true,
            'message' => 'Weather analysis sent successfully',
            'recipients' => count($subscribers),
            'notifications_sent' => $sentCount,
            'alert_id' => $alertId
        ], JSON_UNESCAPED_UNICODE);

    } catch (Exception $e) {
        error_log("Error sending weather analysis: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
        ob_clean();
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
    } catch (Error $e) {
        error_log("Fatal error sending weather analysis: " . $e->getMessage());
        ob_clean();
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Fatal error: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }
}

/**
 * Get AI disaster monitoring analysis for display (without sending)
 */
function getWeatherAnalysis() {
    global $pdo;

    // Clear any previous output
    if (ob_get_level()) {
        ob_clean();
    }

    // Check if AI disaster monitoring is enabled
    require_once __DIR__ . '/secure-api-config.php';
    if (!isAIAnalysisEnabled('disaster_monitoring')) {
        ob_clean();
        http_response_code(403);
        echo json_encode([
            'success' => false, 
            'message' => 'AI disaster monitoring analysis is currently disabled. Please enable it in General Settings → AI Analysis Settings to use this feature.'
        ], JSON_UNESCAPED_UNICODE);
        if (ob_get_level()) {
            ob_end_flush();
        }
        exit();
    }

    try {
        // Ensure we have a database connection
        if ($pdo === null) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Database connection not available']);
            return;
        }

        // Get AI settings
        $settings = null;
        try {
            // Check if table exists first
            $tableCheck = $pdo->query("SHOW TABLES LIKE 'ai_warning_settings'");
            if ($tableCheck->rowCount() > 0) {
                $stmt = $pdo->query("SELECT * FROM ai_warning_settings ORDER BY id DESC LIMIT 1");
                $settings = $stmt->fetch(PDO::FETCH_ASSOC);
            }
        } catch (PDOException $e) {
            error_log("Error loading AI settings in getWeatherAnalysis: " . $e->getMessage());
            // Continue with null settings - will use defaults
        } catch (Exception $e) {
            error_log("General error loading AI settings: " . $e->getMessage());
            // Continue with null settings
        }

        // Get API key - prefer analysis key for analysis functions
        $apiKey = null;
        $backupApiKey = null;
        try {
            if (function_exists('getGeminiApiKey')) {
                $apiKey = getGeminiApiKey('analysis');
                if (!empty($apiKey)) {
                    error_log("Found AI_API_KEY_ANALYSIS from config");
                } else {
                    // Fallback to default key if analysis key not available
                    $apiKey = getGeminiApiKey('default');
                    if (!empty($apiKey)) {
                        error_log("Found AI_API_KEY from config (fallback)");
                    }
                }
                // Get backup key for quota exceeded scenarios
                $backupApiKey = getGeminiApiKey('analysis_backup');
                if (!empty($backupApiKey)) {
                    error_log("Found AI_API_KEY_ANALYSIS_BACKUP from config");
                }
            } else {
                error_log("getGeminiApiKey function not found!");
            }
        } catch (Exception $e) {
            error_log("Error getting Gemini API key: " . $e->getMessage());
        } catch (Error $e) {
            error_log("Fatal error getting Gemini API key: " . $e->getMessage());
        }

        // Fallback to database if secure config doesn't have it
        if (empty($apiKey) && $settings && !empty($settings['gemini_api_key'])) {
            $apiKey = $settings['gemini_api_key'];
            error_log("Using API key from database");
        }

        if (empty($apiKey)) {
            error_log("No Gemini API key found in config or database");
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Gemini API key not configured. Please check your API key configuration in AI Warning Settings.']);
            return;
        }

        error_log("API key found, length: " . strlen($apiKey));

        // Get weather data for primary location (Quezon City)
        $weatherData = [];
        try {
            if (function_exists('getWeatherData')) {
                $weatherData = getWeatherData();
            }
        } catch (Exception $e) {
            error_log("Error getting weather data: " . $e->getMessage());
        }

        if (empty($weatherData) || !is_array($weatherData)) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Unable to fetch weather data from available providers (OpenWeather/Open-Meteo). Check internet/API configuration and try again.']);
            return;
        }

        // Use Quezon City as primary location
        $locationName = 'Quezon City';
        $weather = $weatherData[$locationName] ?? reset($weatherData);

        if (!$weather || !is_array($weather)) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'No weather data available for analysis']);
            return;
        }

        // Build disaster monitoring analysis prompt
        $temp = $weather['main']['temp'] ?? 0;
        $humidity = $weather['main']['humidity'] ?? 0;
        $condition = $weather['weather'][0]['description'] ?? 'Unknown';
        $windSpeed = isset($weather['wind']['speed']) ? round($weather['wind']['speed'] * 3.6, 1) : 0;
        $feelsLike = $weather['main']['feels_like'] ?? $temp;
        $pressure = $weather['main']['pressure'] ?? 0;
        $visibility = isset($weather['visibility']) ? round($weather['visibility'] / 1000, 1) : 0;

        $prompt = "You are a disaster monitoring analyst for {$locationName}, Philippines. Analyze the current weather conditions and potential disaster risks, providing a comprehensive disaster monitoring analysis.

CURRENT CONDITIONS:
- Temperature: {$temp}°C (Feels like: {$feelsLike}°C)
- Humidity: {$humidity}%
- Condition: {$condition}
- Wind Speed: {$windSpeed} km/h
- Pressure: {$pressure} hPa
- Visibility: {$visibility} km

Provide your analysis in this EXACT JSON format (no markdown, no code blocks, just valid JSON):

{
  \"summary\": \"[1-2 sentence summary of current conditions and next 24 hours forecast]\",
  \"recommendations\": [
    \"[First recommendation]\",
    \"[Second recommendation]\",
    \"[Third recommendation]\",
    \"[Fourth recommendation]\"
  ],
  \"risk_assessment\": {
    \"level\": \"LOW|MEDIUM|HIGH\",
    \"description\": \"[Brief explanation of risk level and why]\"
  }
}

Keep recommendations practical and actionable for public safety. Risk level should be based on actual danger (heat stress, flooding risk, strong winds, etc.).";

        // Generate AI weather analysis
        try {
        $model = getGeminiModel();
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key=" . urlencode($apiKey);

        $data = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt]
                    ]
                ]
            ],
            'generationConfig' => [
                'temperature' => 0.7,
                'maxOutputTokens' => 2048,
                'topP' => 0.95,
                'topK' => 40
            ]
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            error_log("CURL Error in getWeatherAnalysis: " . $curlError);
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Network error: ' . $curlError]);
            return;
        }

        if ($httpCode !== 200) {
            $errorData = json_decode($response, true);
            $errorMsg = 'Unknown error';
            if (isset($errorData['error']['message'])) {
                $errorMsg = $errorData['error']['message'];
            } elseif (!empty($response)) {
                $errorMsg = "HTTP $httpCode: " . substr($response, 0, 100);
            } else {
                $errorMsg = "HTTP $httpCode: Empty response from server";
            }
            
            // Check if error is quota-related and retry with backup key
            // "overloaded" is Google's way of saying the free tier is rate-limited
            $isQuotaError = stripos($errorMsg, 'quota') !== false || 
                          stripos($errorMsg, 'exceeded') !== false ||
                          stripos($errorMsg, 'billing') !== false ||
                          stripos($errorMsg, 'overloaded') !== false ||
                          stripos($errorMsg, 'rate limit') !== false ||
                          stripos($errorMsg, 'resource_exhausted') !== false ||
                          $httpCode === 429;
            
            if ($isQuotaError && !empty($backupApiKey) && $apiKey !== $backupApiKey) {
                error_log("Quota exceeded detected, retrying with backup API key");
                
                // Retry with backup key
                $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key=" . urlencode($backupApiKey);
                $ch = curl_init($url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Content-Type: application/json'
                ]);
                curl_setopt($ch, CURLOPT_TIMEOUT, 30);
                
                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $curlError = curl_error($ch);
                curl_close($ch);
                
                if ($curlError) {
                    error_log("CURL Error with backup key: " . $curlError);
                    http_response_code(500);
                    echo json_encode(['success' => false, 'message' => 'Network error: ' . $curlError]);
                    return;
                }
                
                if ($httpCode !== 200) {
                    $errorData = json_decode($response, true);
                    $errorMsg = 'Unknown error';
                    if (isset($errorData['error']['message'])) {
                        $errorMsg = $errorData['error']['message'];
                    } elseif (!empty($response)) {
                        $errorMsg = "HTTP $httpCode: " . substr($response, 0, 100);
                    } else {
                        $errorMsg = "HTTP $httpCode: Empty response from server";
                    }
                    error_log("Gemini API error with backup key: HTTP $httpCode - $errorMsg");
                    http_response_code(500);
                    echo json_encode(['success' => false, 'message' => 'Failed to generate analysis: ' . $errorMsg]);
                    return;
                }
                
                error_log("Successfully used backup API key after quota exceeded");
            } else {
                error_log("Gemini API error in getWeatherAnalysis: HTTP $httpCode - $errorMsg");
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Failed to generate analysis: ' . $errorMsg]);
                return;
            }
        }

        $responseData = json_decode($response, true);

        if (!isset($responseData['candidates'][0]['content']['parts'][0]['text'])) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Invalid response from AI']);
            return;
        }

        $aiResponse = trim($responseData['candidates'][0]['content']['parts'][0]['text']);

        // Extract JSON from response (might have markdown code blocks)
        if (preg_match('/```json\s*(.*?)\s*```/s', $aiResponse, $matches)) {
            $aiResponse = $matches[1];
        } elseif (preg_match('/```\s*(.*?)\s*```/s', $aiResponse, $matches)) {
            $aiResponse = $matches[1];
        }

        $analysis = json_decode($aiResponse, true);

        if (!$analysis || !isset($analysis['summary'])) {
            // Fallback: try to parse the response as plain text
            $analysis = [
                'summary' => $aiResponse,
                'recommendations' => [],
                'risk_assessment' => [
                    'level' => 'MEDIUM',
                    'description' => 'Unable to parse detailed analysis'
                ]
            ];
        }

        echo json_encode([
            'success' => true,
            'analysis' => $analysis,
            'location' => $locationName,
            'weather' => [
                'temp' => $temp,
                'humidity' => $humidity,
                'condition' => $condition,
                'wind_speed' => $windSpeed
            ],
            'timestamp' => date('Y-m-d H:i:s')
        ], JSON_UNESCAPED_UNICODE);

        } catch (Exception $e) {
            error_log("Error getting disaster monitoring analysis: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        } catch (Error $e) {
            error_log("Fatal error getting disaster monitoring analysis: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Fatal error: ' . $e->getMessage()]);
        }
    } catch (Exception $e) {
        error_log("Error in getWeatherAnalysis: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    } catch (Error $e) {
        error_log("Fatal error in getWeatherAnalysis: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Fatal error: ' . $e->getMessage()]);
    }
}

function checkEarthquakeConditions($settings) {
    $warnings = [];
    $threshold = floatval($settings['earthquake_threshold'] ?? 5.0);

    // Check recent earthquakes from USGS API
    $url = "https://earthquake.usgs.gov/fdsnws/event/1/query?format=geojson&starttime=" .
           date('Y-m-d', strtotime('-1 day')) .
           "&minmagnitude=" . $threshold .
           "&maxlatitude=21.0&minlatitude=4.5&maxlongitude=127.0&minlongitude=116.0";

    $response = @file_get_contents($url);
    if ($response) {
        $data = json_decode($response, true);
        if (isset($data['features']) && count($data['features']) > 0) {
            foreach ($data['features'] as $feature) {
                $mag = floatval($feature['properties']['mag'] ?? 0);
                if ($mag >= $threshold) {
                    $place = $feature['properties']['place'] ?? 'Philippines';
                    $warnings[] = [
                        'type' => 'earthquake',
                        'title' => "Earthquake Alert - Magnitude {$mag}",
                        'content' => "A magnitude {$mag} earthquake was detected near {$place}. Please take necessary precautions.",
                        'severity' => $mag >= 6.0 ? 'critical' : ($mag >= 5.5 ? 'high' : 'medium')
                    ];
                }
            }
        }
    }

    return $warnings;
}

function checkFloodingLandslideRisks($settings) {
    $warnings = [];
    $monitoredAreas = explode("\n", $settings['monitored_areas'] ?? '');
    $warningTypes = explode(',', $settings['warning_types'] ?? '');

    $rainThreshold = floatval($settings['rain_threshold'] ?? 20);

    // OpenWeather key is optional because aiFetchWeatherCurrentForLocation includes no-key fallback.
    $apiKey = function_exists('getOpenWeatherApiKey') ? getOpenWeatherApiKey(true) : null;
    $weatherByLocation = getWeatherData();

    // Area coordinates (simplified - in production, use geocoding)
    $areaCoords = [
        'Quezon City' => ['lat' => 14.6488, 'lon' => 121.0509],
        'Manila' => ['lat' => 14.5995, 'lon' => 120.9842],
        'Makati' => ['lat' => 14.5547, 'lon' => 121.0244],
    ];

    foreach ($monitoredAreas as $area) {
        $area = trim($area);
        if (empty($area) || !isset($areaCoords[$area])) continue;

        $weatherData = (is_array($weatherByLocation) && isset($weatherByLocation[$area]) && is_array($weatherByLocation[$area]))
            ? $weatherByLocation[$area]
            : aiFetchWeatherCurrentForLocation($areaCoords[$area]['lat'], $areaCoords[$area]['lon'], $apiKey);

        if (!is_array($weatherData) || isset($weatherData['error'])) {
            continue;
        }

        if (isset($weatherData['rain']['1h'])) {
            $rainfall = floatval($weatherData['rain']['1h']);

            if ($rainfall >= $rainThreshold) {
                if (in_array('flooding', $warningTypes)) {
                    $warnings[] = [
                        'type' => 'flooding',
                        'title' => "Flooding Risk Alert - {$area}",
                        'content' => "Heavy rainfall detected in {$area} ({$rainfall}mm/hour). Risk of flooding in low-lying areas. Residents should prepare for possible evacuation.",
                        'severity' => $rainfall >= 50 ? 'critical' : ($rainfall >= 30 ? 'high' : 'medium')
                    ];
                }

                if (in_array('landslide', $warningTypes) && $rainfall >= 30) {
                    $warnings[] = [
                        'type' => 'landslide',
                        'title' => "Landslide Risk Alert - {$area}",
                        'content' => "Heavy rainfall ({$rainfall}mm/hour) increases landslide risk in hilly areas of {$area}. Residents near slopes and embankments should be alert and consider evacuation if necessary.",
                        'severity' => $rainfall >= 50 ? 'critical' : 'high'
                    ];
                }
            }
        }
    }

    return $warnings;
}

/**
 * Insert automated warning into primary table, with runtime fallback when primary is corrupted/unavailable.
 */
function insertAutomatedWarningRecord(PDO $pdo, string $source, string $type, string $title, string $content, string $severity, string $status): int {
    $table = resolveAutomatedWarningsWriteTable($pdo);
    $stmt = $pdo->prepare("
        INSERT INTO {$table} (source, type, title, content, severity, status)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([$source, $type, $title, $content, $severity, $status]);
    return (int)$pdo->lastInsertId();
}

function resolveAutomatedWarningsWriteTable(PDO $pdo): string {
    if (ensureAutomatedWarningsWriteTable($pdo, 'automated_warnings')) {
        return 'automated_warnings';
    }

    ensureAutomatedWarningsWriteTable($pdo, 'automated_warnings_runtime');
    return 'automated_warnings_runtime';
}

function ensureAutomatedWarningsWriteTable(PDO $pdo, string $tableName): bool {
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $tableName)) {
        return false;
    }

    try {
        $pdo->query("SELECT 1 FROM {$tableName} LIMIT 1");
        return true;
    } catch (PDOException $e) {
        // Continue to create/recreate below.
    }

    $sql = "
        CREATE TABLE IF NOT EXISTS {$tableName} (
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
        error_log("Failed to ensure {$tableName}: " . $e->getMessage());
        return false;
    }
}

function shouldBroadcastCriticalToAllCitizens(array $warning): bool {
    $severity = strtolower(trim((string)($warning['severity'] ?? '')));
    if ($severity !== 'critical') {
        return false;
    }

    $type = strtolower(trim((string)($warning['type'] ?? '')));
    $autoAllTypes = [
        'earthquake', 'tsunami',
        'weather', 'typhoon', 'heavy_rain', 'strong_winds', 'thunderstorm', 'flooding', 'landslide'
    ];
    return in_array($type, $autoAllTypes, true);
}

function ensureNotificationQueueTableForAI(PDO $pdo): void {
    $pdo->exec("
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
    ");
}

function queueCriticalWarningToAllCitizens(array $warning, int $alertId, array $settings, $adminId = null): int {
    global $pdo;
    if (!isset($pdo) || !$pdo) {
        return 0;
    }

    ensureNotificationQueueTableForAI($pdo);

    $channels = explode(',', (string)($settings['ai_channels'] ?? 'sms,email,push,pa'));
    $channels = array_values(array_intersect(array_map('trim', array_map('strtolower', $channels)), ['sms', 'email', 'push', 'pa']));
    if (empty($channels)) {
        $channels = ['sms', 'email', 'push', 'pa'];
    }

    $title = (string)($warning['title'] ?? 'Critical Alert');
    $message = formatCriticalBroadcastMessage($warning);
    $severity = strtolower((string)($warning['severity'] ?? 'critical'));

    $recipientsStmt = $pdo->query("
        SELECT u.id, u.email, u.phone, d.fcm_token
        FROM users u
        LEFT JOIN user_devices d ON d.user_id = u.id AND d.is_active = 1
        WHERE u.status = 'active'
    ");
    $recipients = $recipientsStmt ? $recipientsStmt->fetchAll(PDO::FETCH_ASSOC) : [];

    $logStmt = $pdo->prepare("
        INSERT INTO notification_logs (channel, message, recipients, priority, status, sent_at, sent_by, ip_address)
        VALUES (?, ?, 'all_active_citizens', ?, 'pending', NOW(), ?, ?)
    ");
    $logStmt->execute([
        implode(',', $channels),
        $message,
        $severity,
        $adminId ? ('admin_' . $adminId) : 'ai_system',
        $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'
    ]);
    $logId = (int)$pdo->lastInsertId();

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

            $qStmt = $pdo->prepare("
                INSERT INTO notification_queue (log_id, recipient_id, recipient_type, recipient_value, channel, title, message, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')
            ");
            $qStmt->execute([$logId, $recipientId, $recipientType, $recipientValue, $channel, $title, $message]);
            $queued++;
        }
    }

    if (in_array('pa', $channels, true)) {
        $qStmt = $pdo->prepare("
            INSERT INTO notification_queue (log_id, recipient_id, recipient_type, recipient_value, channel, title, message, status)
            VALUES (?, NULL, 'system', 'pa_system', 'pa', ?, ?, 'pending')
        ");
        $qStmt->execute([$logId, $title, $message]);
        $queued++;
    }

    $pdo->prepare("UPDATE notification_logs SET status = 'sent' WHERE id = ?")->execute([$logId]);
    return $queued;
}

function formatCriticalBroadcastMessage(array $warning): string {
    $type = strtolower(trim((string)($warning['type'] ?? 'general')));
    $content = trim((string)($warning['content'] ?? 'Critical conditions detected.'));
    $header = "EMERGENCY BULLETIN\n";

    if (in_array($type, ['earthquake', 'tsunami'], true)) {
        $header .= "Type: Earthquake Emergency\n";
        $actions = "Actions: DROP, COVER, HOLD; after shaking, evacuate unsafe structures and monitor official advisories.";
    } else {
        $header .= "Type: Severe Weather Emergency\n";
        $actions = "Actions: avoid flood-prone zones, keep emergency kit ready, and follow LGU evacuation guidance.";
    }

    return $header . "\n" . $content . "\n\n" . $actions;
}

