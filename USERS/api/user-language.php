<?php
/**
 * User Language Preference API
 * Handles getting and setting user language preferences
 */

session_start();
header('Content-Type: application/json; charset=utf-8');
require_once 'db_connect.php';

$action = $_GET['action'] ?? 'get';

// Get user language preference
if ($action === 'get') {
    // Check if user is logged in
    if (isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] === true) {
        $userId = $_SESSION['user_id'] ?? null;
        $userType = $_SESSION['user_type'] ?? 'registered';
        
        // Handle guest users
        if ($userType === 'guest' && $userId && strpos($userId, 'guest_') === 0) {
            // Check session for guest language preference
            if (isset($_SESSION['guest_language'])) {
                echo json_encode([
                    'success' => true,
                    'language' => $_SESSION['guest_language'],
                    'user_type' => 'guest'
                ]);
                exit;
            }
            
            // Try to detect from browser
            $acceptLanguage = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '';
            if ($acceptLanguage) {
                $langCode = strtolower(explode('-', explode(',', $acceptLanguage)[0])[0]);
                if (strlen($langCode) === 2) {
                    // Store in session for future use
                    $_SESSION['guest_language'] = $langCode;
                    echo json_encode([
                        'success' => true,
                        'language' => $langCode,
                        'user_type' => 'guest',
                        'detected' => true
                    ]);
                    exit;
                }
            }
            
            // Default for guests
            echo json_encode([
                'success' => true,
                'language' => 'en',
                'user_type' => 'guest',
                'default' => true
            ]);
            exit;
        }
        
        // Handle registered users
        if ($userId && $pdo && $userType === 'registered') {
            try {
                // First check user_preferences table
                $stmt = $pdo->prepare("SELECT preferred_language FROM user_preferences WHERE user_id = ?");
                $stmt->execute([$userId]);
                $pref = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($pref && !empty($pref['preferred_language'])) {
                    echo json_encode([
                        'success' => true,
                        'language' => $pref['preferred_language'],
                        'user_type' => 'registered'
                    ]);
                    exit;
                }
                
                // Fallback to users table
                $stmt = $pdo->prepare("SELECT preferred_language FROM users WHERE id = ?");
                $stmt->execute([$userId]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($user && !empty($user['preferred_language'])) {
                    echo json_encode([
                        'success' => true,
                        'language' => $user['preferred_language'],
                        'user_type' => 'registered'
                    ]);
                    exit;
                }
            } catch (PDOException $e) {
                // Database error, fall back to local storage
                error_log("Error getting user language: " . $e->getMessage());
            }
        }
    }
    
    // Not logged in or no preference set - try browser detection
    $acceptLanguage = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '';
    if ($acceptLanguage) {
        $langCode = strtolower(explode('-', explode(',', $acceptLanguage)[0])[0]);
        if (strlen($langCode) === 2) {
            echo json_encode([
                'success' => true,
                'language' => $langCode,
                'user_type' => 'anonymous',
                'detected' => true
            ]);
            exit;
        }
    }
    
    // Default fallback
    echo json_encode([
        'success' => false,
        'message' => 'No preference found',
        'language' => 'en',
        'default' => true
    ]);
    exit;
}

// Set user language preference
if ($action === 'set' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $language = $input['language'] ?? '';
    
    if (empty($language)) {
        echo json_encode([
            'success' => false,
            'message' => 'Language code is required'
        ]);
        exit;
    }
    
    // Validate language code format
    if (strlen($language) < 2 || strlen($language) > 10) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid language code format'
        ]);
        exit;
    }
    
    // Check if user is logged in
    if (isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] === true) {
        $userId = $_SESSION['user_id'] ?? null;
        $userType = $_SESSION['user_type'] ?? 'registered';
        
        // Handle guest users
        if ($userType === 'guest' && $userId && strpos($userId, 'guest_') === 0) {
            // Store guest language preference in session
            $_SESSION['guest_language'] = $language;
            
            echo json_encode([
                'success' => true,
                'message' => 'Guest language preference saved',
                'user_type' => 'guest',
                'language' => $language
            ]);
            exit;
        }
        
        // Handle registered users
        if ($userId && $pdo && $userType === 'registered') {
            try {
                // Try to update user_preferences table first
                $stmt = $pdo->prepare("
                    INSERT INTO user_preferences (user_id, preferred_language, updated_at)
                    VALUES (?, ?, NOW())
                    ON DUPLICATE KEY UPDATE preferred_language = VALUES(preferred_language), updated_at = NOW()
                ");
                $stmt->execute([$userId, $language]);
                
                // Also update users table for backward compatibility
                $stmt = $pdo->prepare("UPDATE users SET preferred_language = ? WHERE id = ?");
                $stmt->execute([$language, $userId]);
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Language preference updated',
                    'user_type' => 'registered',
                    'language' => $language
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
    }
    
    // Not logged in - still return success (will use localStorage)
    // Also store in session if available for guest detection
    if (session_status() === PHP_SESSION_ACTIVE) {
        $_SESSION['guest_language'] = $language;
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Language preference saved locally',
        'user_type' => 'anonymous',
        'language' => $language
    ]);
    exit;
}

// Invalid action
echo json_encode([
    'success' => false,
    'message' => 'Invalid action'
]);
?>


