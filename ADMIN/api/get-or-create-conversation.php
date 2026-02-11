<?php
/**
 * Get or Create Conversation API
 * Finds existing conversation for a user or creates a new one
 */

header('Content-Type: application/json');
require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/chat-logic.php';

session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if (!$pdo) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

try {
    $userId = $_GET['user_id'] ?? null;
    $phone = $_GET['phone'] ?? null;
    
    if (!$userId && !$phone) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'user_id or phone is required']);
        exit;
    }
    
    $conversationId = null;
    
    // Try to find existing active conversation
    if ($userId) {
        $activeStatuses = twc_active_statuses();
        $stmt = $pdo->prepare("
            SELECT conversation_id FROM conversations 
            WHERE user_id = ? AND status IN (" . twc_placeholders($activeStatuses) . ")
            ORDER BY updated_at DESC LIMIT 1
        ");
        $params = [$userId];
        $params = array_merge($params, $activeStatuses);
        $stmt->execute($params);
        $existing = $stmt->fetch();
        if ($existing) {
            $conversationId = $existing['conversation_id'];
        }
    }
    
    // If not found by user_id, try by phone
    if (!$conversationId && $phone) {
        $activeStatuses = twc_active_statuses();
        $stmt = $pdo->prepare("
            SELECT conversation_id FROM conversations 
            WHERE user_phone = ? AND status IN (" . twc_placeholders($activeStatuses) . ")
            ORDER BY updated_at DESC LIMIT 1
        ");
        $params = [$phone];
        $params = array_merge($params, $activeStatuses);
        $stmt->execute($params);
        $existing = $stmt->fetch();
        if ($existing) {
            $conversationId = $existing['conversation_id'];
        }
    }
    
    // If still no conversation, create a new one
    if (!$conversationId) {
        // Get user info if we have user_id
        $userName = 'Emergency Call User';
        $userEmail = null;
        $isGuest = 1;
        
        if ($userId) {
            $stmt = $pdo->prepare("SELECT name, email FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch();
            if ($user) {
                $userName = $user['name'];
                $userEmail = $user['email'];
                $isGuest = 0;
            }
        } elseif ($phone) {
            $stmt = $pdo->prepare("SELECT name, email FROM users WHERE phone = ?");
            $stmt->execute([$phone]);
            $user = $stmt->fetch();
            if ($user) {
                $userName = $user['name'];
                $userEmail = $user['email'];
                $isGuest = 0;
                $userId = $user['id'] ?? null;
            }
        }
        
        $stmt = $pdo->prepare("
            INSERT INTO conversations (
                user_id, user_name, user_email, user_phone, is_guest, status,
                last_message, last_message_time, created_at, updated_at
            ) VALUES (?, ?, ?, ?, ?, 'active', 'Emergency call started', NOW(), NOW(), NOW())
        ");
        
        $stmt->execute([
            $userId ?: null,
            $userName,
            $userEmail,
            $phone ?: null,
            $isGuest
        ]);
        
        $conversationId = $pdo->lastInsertId();
    }
    
    echo json_encode([
        'success' => true,
        'conversationId' => $conversationId
    ]);
    
} catch (PDOException $e) {
    error_log('Get or create conversation error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
?>
