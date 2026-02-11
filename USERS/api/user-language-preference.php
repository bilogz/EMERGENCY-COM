<?php
/**
 * User Language Preference API
 * Handles saving and retrieving user language preferences
 */

header('Content-Type: application/json; charset=utf-8');
require_once '../../ADMIN/api/db_connect.php';
require_once '../../ADMIN/api/security-helpers.php';

session_start();

function resolvePreferenceLanguagesTable(PDO $pdo): ?string {
    $candidates = ['supported_languages', 'supported_languages_catalog', 'emergency_comm_supported_languages'];
    foreach ($candidates as $table) {
        try {
            $stmt = $pdo->query("SHOW TABLES LIKE " . $pdo->quote($table));
            if (!$stmt || !$stmt->fetch()) {
                continue;
            }
            $pdo->query("SELECT 1 FROM {$table} LIMIT 1");
            return $table;
        } catch (PDOException $e) {
            // Try next table.
        }
    }
    return null;
}

$action = $_GET['action'] ?? ($_SERVER['REQUEST_METHOD'] === 'POST' ? 'set' : 'get');

try {
    if ($pdo === null) {
        echo json_encode([
            'success' => false,
            'message' => 'Database connection failed'
        ]);
        exit;
    }

    $languagesTable = resolvePreferenceLanguagesTable($pdo);
    
    if ($action === 'set') {
        // Set user language preference
        $input = json_decode(file_get_contents('php://input'), true);
        $language = $input['language'] ?? $_POST['language'] ?? null;
        $autoTranslate = $input['auto_translate_enabled'] ?? null;
        
        if (!$language && $autoTranslate === null) {
            echo json_encode([
                'success' => false,
                'message' => 'Language code or auto-translate preference is required'
            ]);
            exit;
        }
        
        // Validate language exists if provided
        if ($language) {
            if ($languagesTable === null) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Language catalog unavailable'
                ]);
                exit;
            }
            $stmt = $pdo->prepare("
                SELECT language_code FROM {$languagesTable} 
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
        }
        
        // Check if user is logged in
        $userId = $_SESSION['user_id'] ?? null;
        
        if ($userId) {
            // Build dynamic query based on what's being updated
            if ($language && $autoTranslate !== null) {
                // Update both language and auto-translate preference
                $stmt = $pdo->prepare("
                    INSERT INTO user_preferences (user_id, preferred_language, auto_translate_enabled, updated_at)
                    VALUES (?, ?, ?, NOW())
                    ON DUPLICATE KEY UPDATE 
                        preferred_language = VALUES(preferred_language),
                        auto_translate_enabled = VALUES(auto_translate_enabled),
                        updated_at = NOW()
                ");
                $stmt->execute([$userId, $language, $autoTranslate ? 1 : 0]);
            } elseif ($language) {
                // Update only language
                $stmt = $pdo->prepare("
                    INSERT INTO user_preferences (user_id, preferred_language, updated_at)
                    VALUES (?, ?, NOW())
                    ON DUPLICATE KEY UPDATE 
                        preferred_language = VALUES(preferred_language),
                        updated_at = NOW()
                ");
                $stmt->execute([$userId, $language]);
            } elseif ($autoTranslate !== null) {
                // Update only auto-translate preference
                $stmt = $pdo->prepare("
                    INSERT INTO user_preferences (user_id, auto_translate_enabled, updated_at)
                    VALUES (?, ?, NOW())
                    ON DUPLICATE KEY UPDATE 
                        auto_translate_enabled = VALUES(auto_translate_enabled),
                        updated_at = NOW()
                ");
                $stmt->execute([$userId, $autoTranslate ? 1 : 0]);
            }
            
            echo json_encode([
                'success' => true,
                'message' => 'Preferences saved successfully',
                'language' => $language,
                'auto_translate_enabled' => $autoTranslate,
                'saved_to_account' => true
            ]);
        } else {
            // Guest user - just return success (will be saved in localStorage)
            echo json_encode([
                'success' => true,
                'message' => 'Preferences set (guest mode)',
                'language' => $language,
                'auto_translate_enabled' => $autoTranslate,
                'saved_to_account' => false
            ]);
        }
        
    } elseif ($action === 'get') {
        // Get user language preference
        $userId = $_SESSION['user_id'] ?? null;
        $language = 'en'; // Default
        $autoTranslate = true; // Default enabled
        
        if ($userId) {
            $stmt = $pdo->prepare("
                SELECT preferred_language, auto_translate_enabled FROM user_preferences 
                WHERE user_id = ?
            ");
            $stmt->execute([$userId]);
            $result = $stmt->fetch();
            
            if ($result) {
                if ($result['preferred_language']) {
                    $language = $result['preferred_language'];
                }
                // Check if column exists and get value
                if (isset($result['auto_translate_enabled'])) {
                    $autoTranslate = (bool)$result['auto_translate_enabled'];
                }
            }
        }
        
        // Also check browser language if no preference set
        if ($language === 'en' && !$userId) {
            $acceptLanguage = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '';
            if ($acceptLanguage) {
                $langCode = strtolower(explode('-', explode(',', $acceptLanguage)[0])[0]);
                
                // Check if supported
                if ($languagesTable !== null) {
                    $stmt = $pdo->prepare("
                        SELECT language_code FROM {$languagesTable} 
                        WHERE language_code = ? AND is_active = 1
                    ");
                    $stmt->execute([$langCode]);
                    if ($stmt->fetch()) {
                        $language = $langCode;
                    }
                }
            }
        }
        
        echo json_encode([
            'success' => true,
            'language' => $language,
            'auto_translate_enabled' => $autoTranslate,
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

