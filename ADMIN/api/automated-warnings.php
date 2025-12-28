<?php
/**
 * Automated Warning Integration API
 * Integrate with external warning feeds (PAGASA, PHIVOLCS)
 */

header('Content-Type: application/json; charset=utf-8');
require_once 'db_connect.php';
require_once 'activity_logger.php';

session_start();

$action = $_GET['action'] ?? 'status';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (isset($data['action']) && $data['action'] === 'toggle') {
        $source = $data['source'] ?? '';
        $enabled = $data['enabled'] ?? false;
        $adminId = $_SESSION['admin_user_id'] ?? null;
        
        try {
            $stmt = $pdo->prepare("
                INSERT INTO integration_settings (source, enabled, updated_at)
                VALUES (?, ?, NOW())
                ON DUPLICATE KEY UPDATE enabled = ?, updated_at = NOW()
            ");
            $stmt->execute([$source, $enabled ? 1 : 0, $enabled ? 1 : 0]);
            
            // Log admin activity
            if ($adminId) {
                logAdminActivity($adminId, 'toggle_integration', 
                    ucfirst($source) . " integration " . ($enabled ? 'enabled' : 'disabled'));
            }
            
            echo json_encode([
                'success' => true,
                'message' => 'Integration toggled successfully.'
            ]);
        } catch (PDOException $e) {
            error_log("Toggle Integration Error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Database error occurred.']);
        }
    } else {
        // Save settings
        $syncInterval = $_POST['sync_interval'] ?? 15;
        $autoPublish = isset($_POST['auto_publish']) ? 1 : 0;
        $channels = $_POST['channels'] ?? [];
        $adminId = $_SESSION['admin_user_id'] ?? null;
        
        try {
            $stmt = $pdo->prepare("
                INSERT INTO warning_settings (sync_interval, auto_publish, notification_channels, updated_at)
                VALUES (?, ?, ?, NOW())
                ON DUPLICATE KEY UPDATE sync_interval = ?, auto_publish = ?, notification_channels = ?, updated_at = NOW()
            ");
            $channelsStr = is_array($channels) ? implode(',', $channels) : '';
            $stmt->execute([$syncInterval, $autoPublish, $channelsStr, $syncInterval, $autoPublish, $channelsStr]);
            
            // Log admin activity
            if ($adminId) {
                $changes = [
                    "Sync Interval: {$syncInterval} minutes",
                    "Auto Publish: " . ($autoPublish ? 'Yes' : 'No'),
                    "Channels: {$channelsStr}"
                ];
                logAdminActivity($adminId, 'update_warning_settings', 'Updated warning settings: ' . implode(', ', $changes));
            }
            
            echo json_encode([
                'success' => true,
                'message' => 'Settings saved successfully.'
            ]);
        } catch (PDOException $e) {
            error_log("Save Settings Error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Database error occurred.']);
        }
    }
} elseif ($action === 'status') {
    try {
        $stmt = $pdo->query("SELECT source, enabled FROM integration_settings");
        $settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        
        // Check Gemini status from AI settings and secure config
        $geminiEnabled = false;
        $geminiApiKeySet = false;
        $geminiStatusMessage = 'API Key Required';
        
        try {
            // Check secure config file first (most reliable)
            require_once 'secure-api-config.php';
            $secureApiKey = getGeminiApiKey();
            
            // Check AI warning settings table
            try {
                $aiStmt = $pdo->query("SELECT ai_enabled, gemini_api_key FROM ai_warning_settings ORDER BY id DESC LIMIT 1");
                $aiSettings = $aiStmt->fetch(PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                // Table might not exist yet
                $aiSettings = null;
            }
            
            if ($aiSettings) {
                $geminiEnabled = $aiSettings['ai_enabled'] == 1;
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
                    $geminiStatusMessage = 'API Key Found - Configure Settings';
                } else {
                    $geminiStatusMessage = 'API Key Required';
                }
            }
        } catch (Exception $e) {
            // Fallback: check secure config only
            if (file_exists(__DIR__ . '/secure-api-config.php')) {
                require_once 'secure-api-config.php';
                $secureApiKey = getGeminiApiKey();
                if (!empty($secureApiKey)) {
                    $geminiApiKeySet = true;
                    $geminiStatusMessage = 'API Key Found - Configure Settings';
                }
            }
        }
        
        echo json_encode([
            'success' => true,
            'pagasa' => ['enabled' => isset($settings['pagasa']) && $settings['pagasa']],
            'phivolcs' => ['enabled' => isset($settings['phivolcs']) && $settings['phivolcs']],
            'gemini' => [
                'enabled' => $geminiEnabled, 
                'api_key_set' => $geminiApiKeySet,
                'status_message' => $geminiStatusMessage
            ]
        ]);
    } catch (PDOException $e) {
        error_log("Get Status Error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error occurred.']);
    }
} elseif ($action === 'warnings') {
    try {
        $stmt = $pdo->query("
            SELECT id, source, type, title, severity, status, received_at
            FROM automated_warnings
            ORDER BY received_at DESC
            LIMIT 100
        ");
        $warnings = $stmt->fetchAll();
        
        echo json_encode([
            'success' => true,
            'warnings' => $warnings
        ]);
    } catch (PDOException $e) {
        error_log("Get Warnings Error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error occurred.']);
    }
} elseif ($action === 'getSettings') {
    try {
        $stmt = $pdo->query("SELECT * FROM warning_settings ORDER BY id DESC LIMIT 1");
        $settings = $stmt->fetch(PDO::FETCH_ASSOC);
        
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
        error_log("Get Settings Error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error occurred.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid action.']);
}
?>

