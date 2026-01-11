<?php
/**
 * Update AI Analysis Setting API
 * Endpoint to enable/disable AI analysis by type (weather, earthquake, disaster_monitoring)
 */

header('Content-Type: application/json; charset=utf-8');

session_start();

// Check if user is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

require_once 'db_connect.php';
require_once 'activity_logger.php';

try {
    if ($pdo === null) {
        throw new Exception('Database connection failed');
    }

    // Get the enabled status and type from request
    $input = json_decode(file_get_contents('php://input'), true);
    $type = isset($input['type']) ? $input['type'] : 'all'; // 'weather', 'earthquake', 'disaster_monitoring', or 'all'
    $enabled = isset($input['enabled']) ? (bool)$input['enabled'] : false;

    // Determine which field to update
    $fieldName = 'ai_enabled'; // Default to global
    if ($type === 'weather') {
        $fieldName = 'ai_weather_enabled';
    } elseif ($type === 'earthquake') {
        $fieldName = 'ai_earthquake_enabled';
    } elseif ($type === 'disaster_monitoring') {
        $fieldName = 'ai_disaster_monitoring_enabled';
    } elseif ($type === 'translation') {
        $fieldName = 'ai_translation_enabled';
    }

    // Ensure table exists with new columns
    $pdo->exec("CREATE TABLE IF NOT EXISTS ai_warning_settings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        gemini_api_key VARCHAR(255) DEFAULT NULL,
        ai_enabled TINYINT(1) DEFAULT 0,
        ai_weather_enabled TINYINT(1) DEFAULT 1,
        ai_earthquake_enabled TINYINT(1) DEFAULT 1,
        ai_disaster_monitoring_enabled TINYINT(1) DEFAULT 1,
        ai_translation_enabled TINYINT(1) DEFAULT 1,
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

    // Add new columns if they don't exist
    $columnsToAdd = [
        'ai_weather_enabled' => "TINYINT(1) DEFAULT 1 AFTER ai_enabled",
        'ai_earthquake_enabled' => "TINYINT(1) DEFAULT 1 AFTER ai_weather_enabled",
        'ai_disaster_monitoring_enabled' => "TINYINT(1) DEFAULT 1 AFTER ai_earthquake_enabled",
        'ai_translation_enabled' => "TINYINT(1) DEFAULT 1 AFTER ai_disaster_monitoring_enabled"
    ];

    foreach ($columnsToAdd as $columnName => $definition) {
        try {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS 
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

    // Check if settings exist
    $stmt = $pdo->query("SELECT id FROM ai_warning_settings ORDER BY id DESC LIMIT 1");
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existing) {
        // Update existing record
        $stmt = $pdo->prepare("UPDATE ai_warning_settings SET {$fieldName} = ? WHERE id = ?");
        $stmt->execute([$enabled ? 1 : 0, $existing['id']]);
    } else {
        // Insert new record with all default values
        $stmt = $pdo->prepare("INSERT INTO ai_warning_settings ({$fieldName}) VALUES (?)");
        $stmt->execute([$enabled ? 1 : 0]);
    }

    // Log activity
    if (isset($_SESSION['admin_user_id']) && function_exists('logAdminActivity')) {
        $typeLabel = ucfirst(str_replace('_', ' ', $type));
        logAdminActivity(
            $_SESSION['admin_user_id'],
            'update_ai_analysis_setting',
            "AI {$typeLabel} analysis " . ($enabled ? 'enabled' : 'disabled'),
            ['type' => $type, 'enabled' => $enabled]
        );
    }

    echo json_encode([
        'success' => true,
        'message' => "AI {$type} analysis setting updated successfully",
        'type' => $type,
        'enabled' => $enabled
    ]);

} catch (PDOException $e) {
    error_log("Update AI Analysis Setting Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    error_log("Update AI Analysis Setting Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>
