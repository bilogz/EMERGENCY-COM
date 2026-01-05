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
            conversation_id,
            user_id,
            user_name,
            user_email,
            user_phone,
            user_location,
            user_concern,
            is_guest,
            device_info,
            ip_address,
            user_agent,
            status,
            last_message,
            last_message_time,
            assigned_to,
            created_at,
            updated_at
        FROM conversations 
        WHERE status = ?
        ORDER BY updated_at DESC
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

