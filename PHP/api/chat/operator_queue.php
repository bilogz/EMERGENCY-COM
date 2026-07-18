<?php
// operator_queue.php
// Operator queue management endpoint for handling chat assignments

require_once __DIR__ . '/../shared/db_connect.php';

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];

// GET: Get queue information
if ($method === 'GET') {
    $action = $_GET['action'] ?? 'queue';
    $operatorId = $_GET['operator_id'] ?? null;
    
    try {
        if ($action === 'queue') {
            // Get pending conversations in queue
            $sql = "SELECT 
                q.queue_id,
                q.conversation_id,
                q.user_id,
                q.user_name,
                q.user_email,
                q.user_phone,
                q.user_location,
                q.user_concern,
                q.message,
                q.status,
                q.created_at,
                q.assigned_to,
                c.incident_priority_score,
                c.incident_priority_level,
                c.incident_priority_color
            FROM chat_queue q
            LEFT JOIN conversations c ON q.conversation_id = c.conversation_id
            WHERE q.status = 'pending'
            ORDER BY 
                c.incident_priority_score DESC,
                q.created_at ASC";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            $queue = $stmt->fetchAll();
            
            apiResponse::success(['queue' => $queue], 'Queue retrieved successfully');
            
        } elseif ($action === 'operator_conversations') {
            // Get conversations assigned to specific operator
            if (!$operatorId) {
                apiResponse::error("Missing required parameter: operator_id", 400);
            }
            
            $sql = "SELECT 
                q.queue_id,
                q.conversation_id,
                q.user_id,
                q.user_name,
                q.user_email,
                q.user_phone,
                q.user_location,
                q.user_concern,
                q.message,
                q.status,
                q.created_at,
                q.assigned_to,
                c.incident_priority_score,
                c.incident_priority_level,
                c.incident_priority_color
            FROM chat_queue q
            LEFT JOIN conversations c ON q.conversation_id = c.conversation_id
            WHERE q.assigned_to = ? AND q.status IN ('accepted', 'pending')
            ORDER BY q.created_at DESC";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$operatorId]);
            $conversations = $stmt->fetchAll();
            
            apiResponse::success(['conversations' => $conversations], 'Operator conversations retrieved successfully');
            
        } elseif ($action === 'stats') {
            // Get queue statistics
            $statsSql = "SELECT 
                COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_count,
                COUNT(CASE WHEN status = 'accepted' THEN 1 END) as active_count,
                COUNT(CASE WHEN status = 'closed' THEN 1 END) as closed_count,
                COUNT(CASE WHEN assigned_to IS NOT NULL THEN 1 END) as assigned_count
            FROM chat_queue";
            
            $stmt = $pdo->prepare($statsSql);
            $stmt->execute();
            $stats = $stmt->fetch();
            
            // Get operator availability
            $operatorSql = "SELECT 
                COUNT(CASE WHEN is_active = 1 THEN 1 END) as active_operators,
                COUNT(*) as total_operators
            FROM admin_user WHERE role = 'operator'";
            
            $opStmt = $pdo->prepare($operatorSql);
            $opStmt->execute();
            $operatorStats = $opStmt->fetch();
            
            apiResponse::success([
                'queue_stats' => $stats,
                'operator_stats' => $operatorStats
            ], 'Statistics retrieved successfully');
        }
        
    } catch (PDOException $e) {
        error_log('Queue retrieval failed: ' . $e->getMessage());
        apiResponse::error('Failed to retrieve queue information', 500);
    }
}

// POST: Assign conversation to operator
elseif ($method === 'POST') {
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    
    if (!is_array($data)) {
        apiResponse::error("Invalid JSON input.", 400);
    }
    
    $action = $data['action'] ?? 'assign';
    $queueId = $data['queue_id'] ?? null;
    $operatorId = $data['operator_id'] ?? null;
    $conversationId = $data['conversation_id'] ?? null;
    
    if ($action === 'assign') {
        if (!$queueId || !$operatorId) {
            apiResponse::error("Missing required fields: queue_id, operator_id", 400);
        }
        
        try {
            $pdo->beginTransaction();
            
            // Update queue assignment
            $updateSql = "UPDATE chat_queue 
                        SET assigned_to = ?, status = 'accepted', updated_at = NOW()
                        WHERE queue_id = ?";
            $stmt = $pdo->prepare($updateSql);
            $stmt->execute([$operatorId, $queueId]);
            
            // Update conversation assignment
            $convSql = "UPDATE conversations 
                       SET assigned_to = ?, updated_at = NOW()
                       WHERE conversation_id = (SELECT conversation_id FROM chat_queue WHERE queue_id = ?)";
            $convStmt = $pdo->prepare($convSql);
            $convStmt->execute([$operatorId, $queueId]);
            
            $pdo->commit();
            
            apiResponse::success(['status' => 'assigned'], 'Conversation assigned successfully');
            
        } catch (PDOException $e) {
            $pdo->rollBack();
            error_log('Assignment failed: ' . $e->getMessage());
            apiResponse::error('Failed to assign conversation', 500);
        }
    }
}

// PUT: Update queue status or reassign
elseif ($method === 'PUT') {
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    
    if (!is_array($data)) {
        apiResponse::error("Invalid JSON input.", 400);
    }
    
    $action = $data['action'] ?? 'update_status';
    $queueId = $data['queue_id'] ?? null;
    $conversationId = $data['conversation_id'] ?? null;
    $status = $data['status'] ?? null;
    $newOperatorId = $data['new_operator_id'] ?? null;
    
    if ($action === 'update_status') {
        if (!$queueId || !$status) {
            apiResponse::error("Missing required fields: queue_id, status", 400);
        }
        
        $validStatuses = ['pending', 'accepted', 'closed'];
        if (!in_array($status, $validStatuses)) {
            apiResponse::error("Invalid status. Must be one of: " . implode(', ', $validStatuses), 400);
        }
        
        try {
            $pdo->beginTransaction();
            
            // Update queue status
            $updateSql = "UPDATE chat_queue 
                        SET status = ?, updated_at = NOW()
                        WHERE queue_id = ?";
            $stmt = $pdo->prepare($updateSql);
            $stmt->execute([$status, $queueId]);
            
            // Update conversation status if closing
            if ($status === 'closed') {
                $convSql = "UPDATE conversations 
                           SET status = 'closed', updated_at = NOW()
                           WHERE conversation_id = (SELECT conversation_id FROM chat_queue WHERE queue_id = ?)";
                $convStmt = $pdo->prepare($convSql);
                $convStmt->execute([$queueId]);
            }
            
            $pdo->commit();
            
            apiResponse::success(['status' => $status], 'Queue status updated successfully');
            
        } catch (PDOException $e) {
            $pdo->rollBack();
            error_log('Status update failed: ' . $e->getMessage());
            apiResponse::error('Failed to update queue status', 500);
        }
    }
    
    elseif ($action === 'reassign') {
        if (!$queueId || !$newOperatorId) {
            apiResponse::error("Missing required fields: queue_id, new_operator_id", 400);
        }
        
        try {
            $pdo->beginTransaction();
            
            // Reassign in queue
            $updateSql = "UPDATE chat_queue 
                        SET assigned_to = ?, status = 'accepted', updated_at = NOW()
                        WHERE queue_id = ?";
            $stmt = $pdo->prepare($updateSql);
            $stmt->execute([$newOperatorId, $queueId]);
            
            // Reassign conversation
            $convSql = "UPDATE conversations 
                       SET assigned_to = ?, updated_at = NOW()
                       WHERE conversation_id = (SELECT conversation_id FROM chat_queue WHERE queue_id = ?)";
            $convStmt = $pdo->prepare($convSql);
            $convStmt->execute([$newOperatorId, $queueId]);
            
            $pdo->commit();
            
            apiResponse::success(['status' => 'reassigned'], 'Conversation reassigned successfully');
            
        } catch (PDOException $e) {
            $pdo->rollBack();
            error_log('Reassignment failed: ' . $e->getMessage());
            apiResponse::error('Failed to reassign conversation', 500);
        }
    }
}

else {
    apiResponse::error('Method not allowed', 405);
}
?>