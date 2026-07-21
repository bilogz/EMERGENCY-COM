<?php
/**
 * VoIP Room Registration Endpoint
 * Registers a new VoIP call room for emergency communication
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
$required_fields = ['room_name', 'hotline_id'];
foreach ($required_fields as $field) {
    if (empty($input[$field])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => "Missing required field: $field"]);
        exit;
    }
}

try {
    $conn = getDbConnection();
    
    // Create voip_rooms table if it doesn't exist
    $createTable = "
        CREATE TABLE IF NOT EXISTS voip_rooms (
            id INT AUTO_INCREMENT PRIMARY KEY,
            room_name VARCHAR(255) NOT NULL UNIQUE,
            hotline_id VARCHAR(50) NOT NULL,
            user_id INT DEFAULT NULL,
            user_name VARCHAR(255) DEFAULT NULL,
            latitude DECIMAL(10, 8) DEFAULT NULL,
            longitude DECIMAL(11, 8) DEFAULT NULL,
            status ENUM('active', 'ended') DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            responder_notified BOOLEAN DEFAULT FALSE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ";
    $conn->query($createTable);
    
    // Insert new room
    $stmt = $conn->prepare("
        INSERT INTO voip_rooms (room_name, hotline_id, user_id, user_name, latitude, longitude)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->bind_param(
        'ssisdd',
        $input['room_name'],
        $input['hotline_id'],
        $input['user_id'] ?? null,
        $input['user_name'] ?? null,
        $input['latitude'] ?? null,
        $input['longitude'] ?? null
    );
    
    $stmt->execute();
    $roomId = $conn->insert_id;
    
    // Fetch the created room
    $result = $conn->query("SELECT * FROM voip_rooms WHERE id = $roomId");
    $room = $result->fetch_assoc();
    
    echo json_encode([
        'success' => true,
        'message' => 'Room registered successfully',
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
