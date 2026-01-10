<?php
// update_location.php
// Updates the user's current GPS location
header('Content-Type: application/json');

require_once 'db_connect.php';

$data = json_decode(file_get_contents('php://input'), true);

// Validate input
if (!isset($data['user_id']) || !isset($data['latitude']) || !isset($data['longitude'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'User ID, latitude, and longitude are required.']);
    exit();
}

$userId = $data['user_id'];
$latitude = $data['latitude'];
$longitude = $data['longitude'];
$address = isset($data['address']) ? trim($data['address']) : null;
$accuracy = isset($data['accuracy']) ? $data['accuracy'] : null; // Accuracy in meters

try {
    // Start transaction to ensure data consistency
    $pdo->beginTransaction();

    // 1. Set all previous locations for this user to 'not current' (is_current = 0)
    // This keeps a history of where they have been, but marks only the latest one as active.
    $updateStmt = $pdo->prepare("UPDATE user_locations SET is_current = 0 WHERE user_id = ?");
    $updateStmt->execute([$userId]);

    // 2. Insert the new location as 'current' (is_current = 1)
    $insertStmt = $pdo->prepare("
        INSERT INTO user_locations (user_id, latitude, longitude, address, accuracy, is_current, created_at) 
        VALUES (?, ?, ?, ?, ?, 1, NOW())
    ");
    $insertStmt->execute([$userId, $latitude, $longitude, $address, $accuracy]);

    $pdo->commit();

    echo json_encode(['success' => true, 'message' => 'Location updated successfully.']);

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    error_log("Location update error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Failed to update location.']);
}
?>
