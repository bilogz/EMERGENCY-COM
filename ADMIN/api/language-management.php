<?php
/**
 * Language Management API for Admin
 * Add, update, and manage supported languages
 */

session_start();
header('Content-Type: application/json; charset=utf-8');
require_once 'db_connect.php';
require_once 'activity_logger.php';

// Check admin authentication
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$adminId = $_SESSION['admin_user_id'] ?? null;
$action = $_GET['action'] ?? 'list';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'add') {
    // Add new language
    $input = json_decode(file_get_contents('php://input'), true);
    
    $languageCode = $input['language_code'] ?? '';
    $languageName = $input['language_name'] ?? '';
    $nativeName = $input['native_name'] ?? $languageName;
    $flagEmoji = $input['flag_emoji'] ?? '🌐';
    $isActive = isset($input['is_active']) ? (int)$input['is_active'] : 1;
    $isAISupported = isset($input['is_ai_supported']) ? (int)$input['is_ai_supported'] : 1;
    $priority = isset($input['priority']) ? (int)$input['priority'] : 0;
    
    if (empty($languageCode) || empty($languageName)) {
        echo json_encode(['success' => false, 'message' => 'Language code and name are required.']);
        exit;
    }
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO supported_languages 
            (language_code, language_name, native_name, flag_emoji, is_active, is_ai_supported, priority)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([$languageCode, $languageName, $nativeName, $flagEmoji, $isActive, $isAISupported, $priority]);
        
        // Log activity
        logAdminActivity($adminId, 'add_language', "Added language: {$languageName} ({$languageCode})");
        
        echo json_encode([
            'success' => true,
            'message' => 'Language added successfully.',
            'language_id' => $pdo->lastInsertId()
        ]);
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
            echo json_encode(['success' => false, 'message' => 'Language code already exists.']);
        } else {
            error_log("Add Language Error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Database error occurred.']);
        }
    }
    
} elseif ($_SERVER['REQUEST_METHOD'] === 'PUT' && $action === 'update') {
    // Update language
    $input = json_decode(file_get_contents('php://input'), true);
    $languageId = $input['id'] ?? $_GET['id'] ?? 0;
    
    if (empty($languageId)) {
        echo json_encode(['success' => false, 'message' => 'Language ID is required.']);
        exit;
    }
    
    $updates = [];
    $params = [];
    
    if (isset($input['language_name'])) {
        $updates[] = 'language_name = ?';
        $params[] = $input['language_name'];
    }
    if (isset($input['native_name'])) {
        $updates[] = 'native_name = ?';
        $params[] = $input['native_name'];
    }
    if (isset($input['flag_emoji'])) {
        $updates[] = 'flag_emoji = ?';
        $params[] = $input['flag_emoji'];
    }
    if (isset($input['is_active'])) {
        $updates[] = 'is_active = ?';
        $params[] = (int)$input['is_active'];
    }
    if (isset($input['is_ai_supported'])) {
        $updates[] = 'is_ai_supported = ?';
        $params[] = (int)$input['is_ai_supported'];
    }
    if (isset($input['priority'])) {
        $updates[] = 'priority = ?';
        $params[] = (int)$input['priority'];
    }
    
    if (empty($updates)) {
        echo json_encode(['success' => false, 'message' => 'No fields to update.']);
        exit;
    }
    
    $updates[] = 'updated_at = NOW()';
    $params[] = $languageId;
    
    try {
        $stmt = $pdo->prepare("
            UPDATE supported_languages 
            SET " . implode(', ', $updates) . "
            WHERE id = ?
        ");
        
        $stmt->execute($params);
        
        // Log activity
        logAdminActivity($adminId, 'update_language', "Updated language ID: {$languageId}");
        
        echo json_encode([
            'success' => true,
            'message' => 'Language updated successfully.'
        ]);
    } catch (PDOException $e) {
        error_log("Update Language Error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error occurred.']);
    }
    
} elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE' && $action === 'delete') {
    // Delete language (soft delete by setting is_active = 0)
    $input = json_decode(file_get_contents('php://input'), true);
    $languageId = $input['id'] ?? $_GET['id'] ?? 0;
    
    if (empty($languageId)) {
        echo json_encode(['success' => false, 'message' => 'Language ID is required.']);
        exit;
    }
    
    try {
        // Get language info before deletion
        $stmt = $pdo->prepare("SELECT language_code, language_name FROM supported_languages WHERE id = ?");
        $stmt->execute([$languageId]);
        $lang = $stmt->fetch();
        
        if (!$lang) {
            echo json_encode(['success' => false, 'message' => 'Language not found.']);
            exit;
        }
        
        // Soft delete (set is_active = 0)
        $stmt = $pdo->prepare("UPDATE supported_languages SET is_active = 0, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$languageId]);
        
        // Log activity
        logAdminActivity($adminId, 'delete_language', "Deleted language: {$lang['language_name']} ({$lang['language_code']})");
        
        echo json_encode([
            'success' => true,
            'message' => 'Language deactivated successfully.'
        ]);
    } catch (PDOException $e) {
        error_log("Delete Language Error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error occurred.']);
    }
    
} elseif ($action === 'list') {
    // List all languages
    try {
        $includeInactive = isset($_GET['include_inactive']) && $_GET['include_inactive'] === '1';
        
        $query = "
            SELECT id, language_code, language_name, native_name, flag_emoji, 
                   is_active, is_ai_supported, priority, created_at, updated_at
            FROM supported_languages
        ";
        
        if (!$includeInactive) {
            $query .= " WHERE is_active = 1";
        }
        
        $query .= " ORDER BY priority DESC, language_name ASC";
        
        $stmt = $pdo->query($query);
        $languages = $stmt->fetchAll();
        
        echo json_encode([
            'success' => true,
            'languages' => $languages,
            'count' => count($languages)
        ]);
    } catch (PDOException $e) {
        error_log("List Languages Error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error occurred.']);
    }
    
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid action.']);
}

