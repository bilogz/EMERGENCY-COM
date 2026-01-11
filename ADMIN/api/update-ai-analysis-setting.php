<?php
/**
 * Update AI Analysis Setting API
 * Simple endpoint to enable/disable AI analysis globally
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

    // Get the enabled status from request
    $input = json_decode(file_get_contents('php://input'), true);
    $enabled = isset($input['enabled']) ? (bool)$input['enabled'] : false;

    // Ensure table exists
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

    // Check if settings exist
    $stmt = $pdo->query("SELECT id FROM ai_warning_settings ORDER BY id DESC LIMIT 1");
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existing) {
        // Update existing record
        $stmt = $pdo->prepare("UPDATE ai_warning_settings SET ai_enabled = ? WHERE id = ?");
        $stmt->execute([$enabled ? 1 : 0, $existing['id']]);
    } else {
        // Insert new record
        $stmt = $pdo->prepare("INSERT INTO ai_warning_settings (ai_enabled) VALUES (?)");
        $stmt->execute([$enabled ? 1 : 0]);
    }

    // Log activity
    if (isset($_SESSION['admin_user_id']) && function_exists('logAdminActivity')) {
        logAdminActivity(
            $_SESSION['admin_user_id'],
            'update_ai_analysis_setting',
            'AI analysis ' . ($enabled ? 'enabled' : 'disabled') . ' globally',
            ['ai_enabled' => $enabled]
        );
    }

    echo json_encode([
        'success' => true,
        'message' => 'AI analysis setting updated successfully',
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
