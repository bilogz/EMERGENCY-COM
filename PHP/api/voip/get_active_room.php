<?php
/**
 * VoIP Active Room Retrieval Endpoint
 * Fetches the currently active VoIP room for a user
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../shared/db.php';
require_once '../shared/api_key.php';

// Validate API key
validateApiKey();

// Only allow GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Validate required parameter
if (empty($_GET['user_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required parameter: user_id']);
    exit;
}

try {
    $conn = getDbConnection();
    
    // Fetch active room for user
    $stmt = $conn->prepare("
        SELECT * FROM voip_rooms 
        WHERE user_id = ? AND status = 'active' 
        ORDER BY created_at DESC 
        LIMIT 1
    ");
    
    $stmt->bind_param('i', $_GET['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode([
            'success' => true,
            'message' => 'No active room found',
            'data' => null
        ]);
        exit;
    }
    
    $room = $result->fetch_assoc();
    
    echo json_encode([
        'success' => true,
        'message' => 'Active room retrieved successfully',
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
