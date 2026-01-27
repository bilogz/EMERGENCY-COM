<?php
/**
 * Update Chat Status API (Admin)
 * Updates the status of a conversation (active/closed)
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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

if (!$pdo) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    $conversationId = $input['conversationId'] ?? $_POST['conversationId'] ?? null;
    $status = $input['status'] ?? $_POST['status'] ?? null;
    
    if (empty($conversationId) || empty($status)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Conversation ID and status are required']);
        exit;
    }

    if (!in_array($status, ['active', 'closed'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid status']);
        exit;
    }
    
    // Get admin name
    $adminName = $_SESSION['admin_username'] ?? $_SESSION['admin_name'] ?? 'Administrator';
    
    // Prepare update query
    $query = "UPDATE conversations SET status = ?, updated_at = NOW()";
    $params = [$status];

    // If closing, add last_message indicating who closed it
    if ($status === 'closed') {
        $query .= ", last_message = CONCAT('Closed by ', ?)";
        $params[] = $adminName;
        
        // Try to update closed_by if column exists (implicitly handled by ignore if fail, but better to just do it separate or try/catch if strictly needed. 
        // For simplicity, we stick to updating status. Logic from chat-close.php suggests closed_by might exist.)
    } else if ($status === 'active') {
         $query .= ", last_message = CONCAT('Re-opened by ', ?)";
         $params[] = $adminName;
    }

    $query .= " WHERE conversation_id = ?";
    $params[] = $conversationId;

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    
    echo json_encode([
        'success' => true,
        'message' => 'Conversation status updated successfully',
        'newStatus' => $status
    ]);
    
} catch (PDOException $e) {
    error_log('Admin chat update status error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to update conversation status']);
}
