<?php
/**
 * User Language Preference API
 * Handles getting and setting user language preferences (en / fil)
 */

session_start();
header('Content-Type: application/json; charset=utf-8');
require_once 'db_connect.php';

function normalizeSupportedUserLanguage(string $language): string {
    $language = strtolower(trim($language));
    if ($language === 'fil' || $language === 'tl' || $language === 'tagalog' || $language === 'filipino') {
        return 'fil';
    }
    return 'en';
}

$action = $_GET['action'] ?? 'get';

// Get user language preference
if ($action === 'get') {
    $userId = $_SESSION['user_id'] ?? ($_GET['user_id'] ?? null);
    
    if ($userId && $pdo) {
        try {
            // First check users.language_preference table column
            $stmt = $pdo->prepare("SELECT language_preference FROM users WHERE id = ? LIMIT 1");
            $stmt->execute([$userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user && !empty($user['language_preference'])) {
                $lang = normalizeSupportedUserLanguage((string)$user['language_preference']);
                echo json_encode([
                    'success' => true,
                    'language' => $lang,
                    'language_preference' => $lang,
                    'user_type' => 'registered'
                ]);
                exit;
            }
        } catch (PDOException $e) {
            error_log("Error getting user language: " . $e->getMessage());
        }
    }
    
    // Check session for guest language preference
    if (isset($_SESSION['guest_language'])) {
        echo json_encode([
            'success' => true,
            'language' => normalizeSupportedUserLanguage((string)$_SESSION['guest_language']),
            'user_type' => 'guest'
        ]);
        exit;
    }
    
    // Default fallback
    echo json_encode([
        'success' => true,
        'language' => 'en',
        'language_preference' => 'en',
        'default' => true
    ]);
    exit;
}

// Set user language preference
if ($action === 'set' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input)) {
        $input = $_POST;
    }
    
    $rawLang = (string)($input['language_preference'] ?? $input['language'] ?? '');
    $language = normalizeSupportedUserLanguage($rawLang);
    $userId = $_SESSION['user_id'] ?? ($input['user_id'] ?? null);
    
    if ($userId && $pdo) {
        try {
            // Update users table language_preference column
            $stmt = $pdo->prepare("UPDATE users SET language_preference = ? WHERE id = ?");
            $stmt->execute([$language, $userId]);
            
            // Also update user_preferences table for backward compatibility if it exists
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO user_preferences (user_id, preferred_language, language_preference, updated_at)
                    VALUES (?, ?, ?, NOW())
                    ON DUPLICATE KEY UPDATE preferred_language = VALUES(preferred_language), language_preference = VALUES(language_preference), updated_at = NOW()
                ");
                $stmt->execute([$userId, $language, $language]);
            } catch (Throwable $e) {
                // Ignore if user_preferences table structure differs
            }
            
            echo json_encode([
                'success' => true,
                'message' => 'Language preference updated successfully',
                'language' => $language,
                'language_preference' => $language,
                'user_id' => $userId
            ]);
            exit;
        } catch (PDOException $e) {
            error_log("Error updating user language: " . $e->getMessage());
            echo json_encode([
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ]);
            exit;
        }
    }
    
    // Store in session if available for guest/anonymous users
    $_SESSION['guest_language'] = $language;
    
    echo json_encode([
        'success' => true,
        'message' => 'Language preference saved for session',
        'language' => $language,
        'language_preference' => $language
    ]);
    exit;
}

echo json_encode([
    'success' => false,
    'message' => 'Invalid action'
]);
