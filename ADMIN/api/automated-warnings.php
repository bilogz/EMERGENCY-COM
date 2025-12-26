<?php
/**
 * Automated Warning Integration API
 * Integrate with external warning feeds (PAGASA, PHIVOLCS)
 */

header('Content-Type: application/json; charset=utf-8');
require_once 'db_connect.php';

$action = $_GET['action'] ?? 'status';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (isset($data['action']) && $data['action'] === 'toggle') {
        $source = $data['source'] ?? '';
        $enabled = $data['enabled'] ?? false;
        $apiKey = $data['api_key'] ?? null;
        
        try {
            if ($apiKey !== null) {
                // Update with API key
                $stmt = $pdo->prepare("
                    INSERT INTO integration_settings (source, enabled, api_key, updated_at)
                    VALUES (?, ?, ?, NOW())
                    ON DUPLICATE KEY UPDATE enabled = ?, api_key = ?, updated_at = NOW()
                ");
                $stmt->execute([$source, $enabled ? 1 : 0, $apiKey, $enabled ? 1 : 0, $apiKey]);
            } else {
                // Update without changing API key
                $stmt = $pdo->prepare("
                    INSERT INTO integration_settings (source, enabled, updated_at)
                    VALUES (?, ?, NOW())
                    ON DUPLICATE KEY UPDATE enabled = ?, updated_at = NOW()
                ");
                $stmt->execute([$source, $enabled ? 1 : 0, $enabled ? 1 : 0]);
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
        
        try {
            $stmt = $pdo->prepare("
                INSERT INTO warning_settings (sync_interval, auto_publish, notification_channels, updated_at)
                VALUES (?, ?, ?, NOW())
                ON DUPLICATE KEY UPDATE sync_interval = ?, auto_publish = ?, notification_channels = ?, updated_at = NOW()
            ");
            $channelsStr = is_array($channels) ? implode(',', $channels) : '';
            $stmt->execute([$syncInterval, $autoPublish, $channelsStr, $syncInterval, $autoPublish, $channelsStr]);
            
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
        $stmt = $pdo->query("SELECT source, enabled, api_key FROM integration_settings");
        $settings = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $settings[$row['source']] = [
                'enabled' => (bool)$row['enabled'],
                'api_key' => $row['api_key']
            ];
        }
        
        echo json_encode([
            'success' => true,
            'pagasa' => [
                'enabled' => isset($settings['pagasa']) && $settings['pagasa']['enabled'],
                'api_key' => $settings['pagasa']['api_key'] ?? null
            ],
            'phivolcs' => [
                'enabled' => isset($settings['phivolcs']) && $settings['phivolcs']['enabled'],
                'api_key' => $settings['phivolcs']['api_key'] ?? null
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
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid action.']);
}
?>

