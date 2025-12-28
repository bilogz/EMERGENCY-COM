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
        
        if ($userId && $pdo) {
            try {
                $stmt = $pdo->prepare("SELECT preferred_language FROM users WHERE id = ?");
                $stmt->execute([$userId]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($user && !empty($user['preferred_language'])) {
                    echo json_encode([
                        'success' => true,
                        'language' => $user['preferred_language']
                    ]);
                    exit;
                }
            } catch (PDOException $e) {
                // Database error, fall back to local storage
            }
        }
    }
    
    // Not logged in or no preference set
    echo json_encode([
        'success' => false,
        'message' => 'No preference found'
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
    
    // Check if user is logged in
    if (isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] === true) {
        $userId = $_SESSION['user_id'] ?? null;
        
        if ($userId && $pdo) {
            try {
                $stmt = $pdo->prepare("UPDATE users SET preferred_language = ? WHERE id = ?");
                $stmt->execute([$language, $userId]);
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Language preference updated'
                ]);
                exit;
            } catch (PDOException $e) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Database error: ' . $e->getMessage()
                ]);
                exit;
            }
        }
    }
    
    // Not logged in - still return success (will use localStorage)
    echo json_encode([
        'success' => true,
        'message' => 'Language preference saved locally'
    ]);
    exit;
}

// Invalid action
echo json_encode([
    'success' => false,
    'message' => 'Invalid action'
]);
?>

