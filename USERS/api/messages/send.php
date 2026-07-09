<?php
/**
 * Messages Send (Adapter for Mobile App)
 * Accepts: { conversation_id, user_id, content, nonce }
 * Inserts user message and updates conversation, queues for admin.
 * Response shape matches the app's MessageResponse.
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../api/db_connect.php';
require_once __DIR__ . '/../api/device_tracking.php';
require_once __DIR__ . '/../../../ADMIN/api/chat-logic.php';

if (!$pdo) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true) ?: [];

    $conversationId = isset($input['conversation_id']) ? (int)$input['conversation_id'] : 0;
    $userId = isset($input['user_id']) ? (int)$input['user_id'] : 0;
    $content = trim($input['content'] ?? '');

    if ($userId <= 0 || $content === '') {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'user_id and content are required']);
        exit;
    }

    // If no conversationId, create or reuse an active one for this user
    if ($conversationId <= 0) {
        $stmt = $pdo->prepare("
            SELECT conversation_id 
            FROM conversations 
            WHERE user_id = ? AND status = 'active' 
            ORDER BY updated_at DESC LIMIT 1
        ");
        $stmt->execute([$userId]);
        $conv = $stmt->fetch();
        if ($conv) {
            $conversationId = (int)$conv['conversation_id'];
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO conversations (user_id, user_name, status, created_at, updated_at) 
                VALUES (?, NULL, 'active', NOW(), NOW())
            ");
            $stmt->execute([$userId]);
            $conversationId = (int)$pdo->lastInsertId();
        }
    }

    // Ensure conversation is not closed
    $stmt = $pdo->prepare("SELECT status FROM conversations WHERE conversation_id = ?");
    $stmt->execute([$conversationId]);
    $row = $stmt->fetch();
    if (!$row || ($row['status'] ?? 'active') !== 'active') {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Conversation is closed']);
        exit;
    }

    $ipAddress = function_exists('getClientIP') ? getClientIP() : null;
    $deviceInfo = function_exists('formatDeviceInfoForDB') ? formatDeviceInfoForDB() : null;
    twc_ensure_incident_priority_columns($pdo);
    $hasIncidentPriority = twc_column_exists($pdo, 'conversations', 'incident_priority_score');
    $incidentPriority = twc_calculate_incident_priority(['message' => $content]);

    // Insert message
    $stmt = $pdo->prepare("
        INSERT INTO chat_messages (conversation_id, sender_id, sender_name, sender_type, message_text, ip_address, device_info, created_at)
        VALUES (?, ?, NULL, 'user', ?, ?, ?, NOW())
    ");
    $stmt->execute([$conversationId, $userId, $content, $ipAddress, $deviceInfo]);

    // Update conversation last message and queue for admin
    $updateSql = "
        UPDATE conversations 
        SET last_message = ?,
            last_message_time = NOW(),
            updated_at = NOW()";
    $updateParams = [$content];
    if ($hasIncidentPriority) {
        $updateSql .= ",
            incident_priority_score = CASE WHEN COALESCE(incident_priority_manual, 0) = 1 THEN incident_priority_score ELSE ? END,
            incident_priority_level = CASE WHEN COALESCE(incident_priority_manual, 0) = 1 THEN incident_priority_level ELSE ? END,
            incident_priority_color = CASE WHEN COALESCE(incident_priority_manual, 0) = 1 THEN incident_priority_color ELSE ? END,
            incident_priority_breakdown = CASE WHEN COALESCE(incident_priority_manual, 0) = 1 THEN incident_priority_breakdown ELSE ? END";
        $updateParams[] = $incidentPriority['score'];
        $updateParams[] = $incidentPriority['priority'];
        $updateParams[] = $incidentPriority['color'];
        $updateParams[] = json_encode($incidentPriority['breakdown']);
    }
    $updateSql .= " WHERE conversation_id = ?";
    $updateParams[] = $conversationId;
    $stmt = $pdo->prepare($updateSql);
    $stmt->execute($updateParams);

    $stmt = $pdo->prepare("
        INSERT INTO chat_queue (conversation_id, user_id, user_name, message, status, created_at)
        VALUES (?, ?, NULL, ?, 'pending', NOW())
        ON DUPLICATE KEY UPDATE message = VALUES(message), status = 'pending', created_at = NOW()
    ");
    $stmt->execute([$conversationId, $userId, $content]);

    $riskLevel = $incidentPriority['priority'] === 'critical'
        ? 'critical'
        : ($incidentPriority['priority'] === 'high' ? 'high' : 'normal');
    $riskNotification = twc_emit_chat_risk_notification($pdo, [
        'message' => $content,
        'category' => '',
        'priority' => $incidentPriority['priority'],
        'riskLevel' => $riskLevel,
        'conversationId' => $conversationId,
        'userName' => '',
        'userLocation' => '',
        'assignedTo' => null,
        'ipAddress' => (string)$ipAddress,
        'snippet' => $content,
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'Message sent',
        'incidentPriority' => $incidentPriority,
        'riskNotification' => $riskNotification,
    ]);
} catch (Throwable $e) {
    error_log('messages/send error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to send message']);
}



