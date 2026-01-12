<?php
/**
 * Enhanced Multilingual Support for Alerts API
 * Manage alert translations manually
 * 
 * NOTE: Alerts use translations from the alert_translations table.
 * This API is for viewing translation history and creating manual translations.
 */

session_start();
header('Content-Type: application/json; charset=utf-8');
require_once 'db_connect.php';
require_once 'activity_logger.php';

// Check admin authentication for write operations
function checkAdminAuth() {
    if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit();
    }
    return $_SESSION['admin_user_id'] ?? null;
}

$action = $_GET['action'] ?? 'list';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $adminId = checkAdminAuth();
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Support both JSON and form data
    $alertId = $input['alert_id'] ?? $_POST['alert_id'] ?? 0;
    $targetLanguage = $input['target_language'] ?? $_POST['target_language'] ?? '';
    $translatedTitle = $input['translated_title'] ?? $_POST['translated_title'] ?? '';
    $translatedContent = $input['translated_content'] ?? $_POST['translated_content'] ?? '';
    
    if (empty($alertId) || empty($targetLanguage)) {
        echo json_encode(['success' => false, 'message' => 'Alert ID and target language are required.']);
        exit;
    }
    
    // Manual translation only
    if (empty($translatedTitle) || empty($translatedContent)) {
        echo json_encode(['success' => false, 'message' => 'Translated title and content are required.']);
        exit;
    }
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO alert_translations (
                alert_id, target_language, translated_title, translated_content, 
                status, translated_at, translated_by_admin_id, translation_method
            )
            VALUES (?, ?, ?, ?, 'active', NOW(), ?, 'manual')
            ON DUPLICATE KEY UPDATE 
                translated_title = VALUES(translated_title),
                translated_content = VALUES(translated_content),
                translated_at = NOW(),
                translated_by_admin_id = VALUES(translated_by_admin_id),
                translation_method = 'manual'
        ");
        $stmt->execute([$alertId, $targetLanguage, $translatedTitle, $translatedContent, $adminId]);
        $translationId = $pdo->lastInsertId();
        
        // Log activity
        logAdminActivity($adminId, 'create_translation', "Created manual translation for alert #{$alertId} to {$targetLanguage}");
        
        $stmt = $pdo->prepare("
            INSERT INTO translation_activity_logs 
            (admin_id, action_type, alert_id, translation_id, target_language, translation_method, success, ip_address, user_agent)
            VALUES (?, 'create_translation', ?, ?, ?, 'manual', 1, ?, ?)
        ");
        $stmt->execute([
            $adminId,
            $alertId,
            $translationId,
            $targetLanguage,
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null
        ]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Translation saved successfully.',
            'translation_id' => $translationId,
            'method' => 'manual'
        ]);
    } catch (PDOException $e) {
        error_log("Multilingual Alert Error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error occurred: ' . $e->getMessage()]);
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $adminId = checkAdminAuth();
    $data = json_decode(file_get_contents('php://input'), true);
    $id = $data['id'] ?? $_GET['id'] ?? 0;
    
    if (empty($id)) {
        echo json_encode(['success' => false, 'message' => 'Translation ID is required.']);
        exit;
    }
    
    try {
        // Get translation info before deletion for logging
        $stmt = $pdo->prepare("SELECT alert_id, target_language FROM alert_translations WHERE id = ?");
        $stmt->execute([$id]);
        $translation = $stmt->fetch();
        
        $stmt = $pdo->prepare("DELETE FROM alert_translations WHERE id = ?");
        $stmt->execute([$id]);
        
        // Log activity
        if ($translation) {
            logAdminActivity($adminId, 'delete_translation', "Deleted translation #{$id} for alert #{$translation['alert_id']} ({$translation['target_language']})");
            
            $stmt = $pdo->prepare("
                INSERT INTO translation_activity_logs 
                (admin_id, action_type, alert_id, translation_id, target_language, success, ip_address, user_agent)
                VALUES (?, 'delete_translation', ?, ?, ?, 1, ?, ?)
            ");
            $stmt->execute([
                $adminId,
                $translation['alert_id'],
                $id,
                $translation['target_language'],
                $_SERVER['REMOTE_ADDR'] ?? null,
                $_SERVER['HTTP_USER_AGENT'] ?? null
            ]);
        }
        
        echo json_encode(['success' => true, 'message' => 'Translation deleted successfully.']);
    } catch (PDOException $e) {
        error_log("Delete Translation Error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error occurred.']);
    }
} elseif ($action === 'languages') {
    // Get supported languages
    try {
        $stmt = $pdo->query("
            SELECT language_code, language_name, native_name, flag_emoji, is_active, is_ai_supported, priority
            FROM supported_languages
            WHERE is_active = 1
            ORDER BY priority DESC, language_name ASC
        ");
        $languages = $stmt->fetchAll();
        
        echo json_encode([
            'success' => true,
            'languages' => $languages
        ]);
    } catch (PDOException $e) {
        // Fallback if table doesn't exist yet
        $languages = [
            ['language_code' => 'en', 'language_name' => 'English', 'native_name' => 'English', 'flag_emoji' => '🇺🇸', 'is_active' => 1, 'is_ai_supported' => 1],
            ['language_code' => 'fil', 'language_name' => 'Filipino', 'native_name' => 'Filipino', 'flag_emoji' => '🇵🇭', 'is_active' => 1, 'is_ai_supported' => 1],
            ['language_code' => 'ceb', 'language_name' => 'Cebuano', 'native_name' => 'Cebuano', 'flag_emoji' => '🇵🇭', 'is_active' => 1, 'is_ai_supported' => 1]
        ];
        echo json_encode([
            'success' => true,
            'languages' => $languages
        ]);
    }
} elseif ($action === 'activity') {
    // Get translation activity logs
    $adminId = checkAdminAuth();
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
    $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
    
    try {
        $stmt = $pdo->prepare("
            SELECT tal.*, u.name as admin_name, a.title as alert_title
            FROM translation_activity_logs tal
            LEFT JOIN users u ON u.id = tal.admin_id
            LEFT JOIN alerts a ON a.id = tal.alert_id
            WHERE tal.admin_id = ?
            ORDER BY tal.created_at DESC
            LIMIT ? OFFSET ?
        ");
        $stmt->execute([$adminId, $limit, $offset]);
        $logs = $stmt->fetchAll();
        
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM translation_activity_logs WHERE admin_id = ?");
        $stmt->execute([$adminId]);
        $total = $stmt->fetchColumn();
        
        echo json_encode([
            'success' => true,
            'logs' => $logs,
            'total' => $total
        ]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error occurred.']);
    }
} elseif ($action === 'list') {
    try {
        $alertId = isset($_GET['alert_id']) ? (int)$_GET['alert_id'] : null;
        
        $query = "
            SELECT t.*, 
                   a.title as original_title, 
                   a.message as original_content, 
                   'en' as original_language,
                   u.name as translated_by_name,
                   sl.language_name,
                   sl.native_name,
                   sl.flag_emoji
            FROM alert_translations t
            LEFT JOIN alerts a ON a.id = t.alert_id
            LEFT JOIN users u ON u.id = t.translated_by_admin_id
            LEFT JOIN supported_languages sl ON sl.language_code = t.target_language
        ";
        
        if ($alertId) {
            $query .= " WHERE t.alert_id = ?";
            $stmt = $pdo->prepare($query);
            $stmt->execute([$alertId]);
        } else {
            $query .= " ORDER BY t.translated_at DESC";
            $stmt = $pdo->query($query);
        }
        
        $translations = $stmt->fetchAll();
        
        echo json_encode([
            'success' => true,
            'translations' => $translations
        ]);
    } catch (PDOException $e) {
        error_log("List Translations Error: " . $e->getMessage());
        // Fallback query without supported_languages join
        try {
            $stmt = $pdo->query("
                SELECT t.*, a.title as original_title, a.message as original_content, 'en' as original_language
                FROM alert_translations t
                LEFT JOIN alerts a ON a.id = t.alert_id
                ORDER BY t.translated_at DESC
            ");
            $translations = $stmt->fetchAll();
            echo json_encode([
                'success' => true,
                'translations' => $translations
            ]);
        } catch (PDOException $e2) {
            echo json_encode(['success' => false, 'message' => 'Database error occurred.']);
        }
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid action.']);
}
?>

