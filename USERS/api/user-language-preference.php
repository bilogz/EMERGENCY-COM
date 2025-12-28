<?php
/**
 * User Language Preference API
 * Handles saving and retrieving user language preferences
 */

header('Content-Type: application/json; charset=utf-8');
require_once '../../ADMIN/api/db_connect.php';
require_once '../../ADMIN/api/security-helpers.php';

session_start();

$action = $_GET['action'] ?? ($_SERVER['REQUEST_METHOD'] === 'POST' ? 'set' : 'get');

try {
    if ($pdo === null) {
        echo json_encode([
            'success' => false,
            'message' => 'Database connection failed'
        ]);
        exit;
    }
    
    if ($action === 'set') {
        // Set user language preference
        $input = json_decode(file_get_contents('php://input'), true);
        $language = $input['language'] ?? $_POST['language'] ?? null;
        
        if (!$language) {
            echo json_encode([
                'success' => false,
                'message' => 'Language code is required'
            ]);
            exit;
        }
        
        // Validate language exists
        $stmt = $pdo->prepare("
            SELECT language_code FROM supported_languages 
            WHERE language_code = ? AND is_active = 1
        ");
        $stmt->execute([$language]);
        if (!$stmt->fetch()) {
            echo json_encode([
                'success' => false,
                'message' => 'Language not supported'
            ]);
            exit;
        }
        
        // Check if user is logged in
        $userId = $_SESSION['user_id'] ?? null;
        
        if ($userId) {
            // Save to user_preferences table
            $stmt = $pdo->prepare("
                INSERT INTO user_preferences (user_id, preferred_language, updated_at)
                VALUES (?, ?, NOW())
                ON DUPLICATE KEY UPDATE 
                    preferred_language = VALUES(preferred_language),
                    updated_at = NOW()
            ");
            $stmt->execute([$userId, $language]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Language preference saved',
                'language' => $language,
                'saved_to_account' => true
            ]);
        } else {
            // Guest user - just return success (will be saved in localStorage)
            echo json_encode([
                'success' => true,
                'message' => 'Language preference set',
                'language' => $language,
                'saved_to_account' => false
            ]);
        }
        
    } elseif ($action === 'get') {
        // Get user language preference
        $userId = $_SESSION['user_id'] ?? null;
        $language = 'en'; // Default
        
        if ($userId) {
            $stmt = $pdo->prepare("
                SELECT preferred_language FROM user_preferences 
                WHERE user_id = ?
            ");
            $stmt->execute([$userId]);
            $result = $stmt->fetch();
            
            if ($result && $result['preferred_language']) {
                $language = $result['preferred_language'];
            }
        }
        
        // Also check browser language if no preference set
        if ($language === 'en' && !$userId) {
            $acceptLanguage = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '';
            if ($acceptLanguage) {
                $langCode = strtolower(explode('-', explode(',', $acceptLanguage)[0])[0]);
                
                // Check if supported
                $stmt = $pdo->prepare("
                    SELECT language_code FROM supported_languages 
                    WHERE language_code = ? AND is_active = 1
                ");
                $stmt->execute([$langCode]);
                if ($stmt->fetch()) {
                    $language = $langCode;
                }
            }
        }
        
        echo json_encode([
            'success' => true,
            'language' => $language,
            'user_id' => $userId
        ]);
        
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid action'
        ]);
    }
    
} catch (PDOException $e) {
    error_log("User Language Preference Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Database error occurred'
    ]);
} catch (Exception $e) {
    error_log("User Language Preference Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Server error occurred'
    ]);
}
?>

