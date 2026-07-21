<?php
/**
 * VoIP Room Status Update Endpoint
 * Updates the status of a VoIP call room (active/ended)
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, PUT');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../shared/db.php';
require_once '../shared/api_key.php';

// Validate API key
validateApiKey();

// Allow POST and PUT methods
if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $_SERVER['REQUEST_METHOD'] !== 'PUT') {
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
if (empty($input['room_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required field: room_id']);
    exit;
}

if (empty($input['status']) || !in_array($input['status'], ['active', 'ended'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid status. Must be "active" or "ended"']);
    exit;
}

try {
    $conn = getDbConnection();
    
    // Update room status
    $stmt = $conn->prepare("
        UPDATE voip_rooms 
        SET status = ?, updated_at = CURRENT_TIMESTAMP 
        WHERE id = ?
    ");
    
    $stmt->bind_param('si', $input['status'], $input['room_id']);
    $stmt->execute();
    
    if ($stmt->affected_rows === 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Room not found']);
        exit;
    }
    
    // Fetch the updated room
    $result = $conn->query("SELECT * FROM voip_rooms WHERE id = " . intval($input['room_id']));
    $room = $result->fetch_assoc();
    
    echo json_encode([
        'success' => true,
        'message' => 'Room status updated successfully',
        'data' => $room
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
