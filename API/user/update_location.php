<?php
// api/user/update_location.php
// Receives location data from the client app and saves it to the database.
header('Content-Type: application/json');

require_once '../db_connect.php';

// Get the data from the client's POST request
$input = json_decode(file_get_contents('php://input'), true);

// --- Validation ---
$userId = isset($input['user_id']) ? (int)$input['user_id'] : 0;
$latitude = isset($input['latitude']) ? (float)$input['latitude'] : 0;
$longitude = isset($input['longitude']) ? (float)$input['longitude'] : 0;

if ($userId <= 0 || $latitude == 0 || $longitude == 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Valid user_id, latitude, and longitude are required.']);
    exit();
}

// Optional: You can get more details from the client
$accuracy = isset($input['accuracy']) ? (float)$input['accuracy'] : null;
$source = isset($input['source']) ? $input['source'] : 'gps'; // default to gps

try {
    // For this app, we might want to deactivate old locations when a new one arrives.
    // This sets the `is_current` flag to 0 for all previous locations for this user.
    $updateStmt = $pdo->prepare("UPDATE user_locations SET is_current = 0 WHERE user_id = ?");
    $updateStmt->execute([$userId]);

    // Now, insert the new location and mark it as the current one.
    $sql = "
        INSERT INTO user_locations (user_id, latitude, longitude, accuracy, source, is_current)
        VALUES (?, ?, ?, ?, ?, 1)
    ";

    $insertStmt = $pdo->prepare($sql);
    $insertStmt->execute([$userId, $latitude, $longitude, $accuracy, $source]);

    http_response_code(201); // 201 Created
    echo json_encode(['success' => true, 'message' => 'Location updated successfully.']);

} catch (PDOException $e) {
    http_response_code(500);
    error_log("Update Location Error for user_id {$userId}: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database update failed.']);
}
?>