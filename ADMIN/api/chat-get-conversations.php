<?php
/**
 * Get Conversations API (Admin)
 * Retrieves all conversations for admin view
 */

header('Content-Type: application/json');
require_once __DIR__ . '/db_connect.php';

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
    $status = $_GET['status'] ?? 'active';
    
    // Pagination parameters
    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $limit = isset($_GET['limit']) ? min(100, max(10, (int)$_GET['limit'])) : 50; // Default 50, max 100
    $offset = ($page - 1) * $limit;
    
    // Get total count for pagination
    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM conversations WHERE status = ?");
    $countStmt->execute([$status]);
    $totalCount = $countStmt->fetchColumn();
    
    $stmt = $pdo->prepare("
        SELECT 
            c.conversation_id,
            c.user_id,
            c.user_name,
            c.user_email,
            c.user_phone,
            c.user_location,
            c.user_concern,
            c.is_guest,
            c.device_info,
            c.ip_address,
            c.user_agent,
            c.status,
            c.assigned_to,
            c.created_at,
            c.updated_at,
            COALESCE(m.message_text, c.last_message) as last_message,
            COALESCE(m.created_at, c.last_message_time) as last_message_time
        FROM conversations c
        LEFT JOIN (
            SELECT m1.conversation_id, m1.message_text, m1.created_at
            FROM chat_messages m1
            INNER JOIN (
                SELECT conversation_id, MAX(created_at) as max_created_at
                FROM chat_messages
                GROUP BY conversation_id
            ) m2 ON m1.conversation_id = m2.conversation_id AND m1.created_at = m2.max_created_at
        ) m ON c.conversation_id = m.conversation_id
        WHERE c.status = ?
        ORDER BY c.updated_at DESC
        LIMIT ? OFFSET ?
    ");
    $stmt->execute([$status, $limit, $offset]);
    $conversations = $stmt->fetchAll();
    
    // Format conversations
    $formattedConversations = array_map(function($conv) {
        // Parse device info if available
        $deviceInfoParsed = null;
        if (!empty($conv['device_info'])) {
            $deviceInfoParsed = json_decode($conv['device_info'], true);
        }
        
        return [
            'id' => $conv['conversation_id'],
            'userId' => $conv['user_id'],
            'userName' => $conv['user_name'],
            'userEmail' => $conv['user_email'],
            'userPhone' => $conv['user_phone'],
            'userLocation' => $conv['user_location'],
            'userConcern' => $conv['user_concern'],
            'isGuest' => (bool)$conv['is_guest'],
            'deviceInfo' => $deviceInfoParsed,
            'ipAddress' => $conv['ip_address'] ?? null,
            'userAgent' => $conv['user_agent'] ?? null,
            'status' => $conv['status'],
            'lastMessage' => $conv['last_message'],
            'lastMessageTime' => $conv['last_message_time'] ? strtotime($conv['last_message_time']) * 1000 : null,
            'assignedTo' => $conv['assigned_to'],
            'createdAt' => strtotime($conv['created_at']) * 1000,
            'updatedAt' => strtotime($conv['updated_at']) * 1000
        ];
    }, $conversations);
    
    echo json_encode([
        'success' => true,
        'conversations' => $formattedConversations,
        'pagination' => [
            'page' => $page,
            'limit' => $limit,
            'total' => (int)$totalCount,
            'total_pages' => (int)ceil($totalCount / $limit)
        ]
    ]);
    
} catch (PDOException $e) {
    error_log('Admin chat get conversations error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to retrieve conversations']);
}

