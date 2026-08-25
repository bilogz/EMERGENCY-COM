<?php
/**
 * Chat Escalation Endpoint
 * Handles escalation from AI to human operators
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../shared/db.php';
require_once '../shared/api_key.php';

// Validate API key
validateApiKey();

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid JSON input']);
    exit;
}

// Validate required fields
$required_fields = ['conversation_id'];
foreach ($required_fields as $field) {
    if (empty($input[$field])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => "Missing required field: $field"]);
        exit;
    }
}

try {
    $conn = getDbConnection();
    
    // Update conversation status to indicate escalation
    $stmt = $conn->prepare("
        UPDATE chat_conversations 
        SET status = 'escalated', 
            escalated_at = NOW(),
            emergency_type = ?,
            severity = ?,
            requires_immediate_action = ?,
            ai_intent = ?,
            ai_confidence = ?
        WHERE conversation_id = ?
    ");
    
    $stmt->bind_param(
        'ssisid',
        $input['emergency_type'] ?? null,
        $input['severity'] ?? null,
        $input['requires_immediate_action'] ?? 0,
        $input['ai_intent'] ?? null,
        $input['ai_confidence'] ?? null,
        $input['conversation_id']
    );
    
    $stmt->execute();
    
    // Add system message about escalation
    $systemMessage = "Conversation escalated to human operator. ";
    if (!empty($input['emergency_type'])) {
        $systemMessage .= "Emergency type: " . $input['emergency_type'] . ". ";
    }
    if (!empty($input['severity'])) {
        $systemMessage .= "Severity: " . $input['severity'] . ". ";
    }
    if (!empty($input['ai_intent'])) {
        $systemMessage .= "AI detected intent: " . $input['ai_intent'] . ". ";
    }
    
    $stmt = $conn->prepare("
        INSERT INTO chat_messages (conversation_id, sender_id, sender_name, sender_type, message_text, is_read, created_at)
        VALUES (?, 'system', 'Alertara System', 'system', ?, 1, NOW())
    ");
    
    $stmt->bind_param('is', $input['conversation_id'], $systemMessage);
    $stmt->execute();
    
    // If immediate action required, notify operators
    if (!empty($input['requires_immediate_action']) && $input['requires_immediate_action']) {
        // Here you would implement operator notification logic
        // This could be via WebSocket, email, SMS, etc.
        error_log("IMMEDIATE ESCALATION: Conversation {$input['conversation_id']} requires immediate operator attention");
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Conversation escalated successfully',
        'conversation_id' => $input['conversation_id'],
        'emergency_type' => $input['emergency_type'] ?? null,
        'severity' => $input['severity'] ?? null,
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
} finally {
    if (isset($conn)) $conn->close();
}
