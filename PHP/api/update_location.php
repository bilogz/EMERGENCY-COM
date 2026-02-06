<?php
// update_location.php
header('Content-Type: application/json');

require_once 'db_connect.php';
/** @var PDO $pdo */

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['user_id']) || !isset($data['latitude']) || !isset($data['longitude'])) {
    apiResponse::error('User ID, latitude, and longitude are required.', 400);
}

$userId = $data['user_id'];
$latitude = $data['latitude'];
$longitude = $data['longitude'];
$address = isset($data['address']) ? trim($data['address']) : null;
$accuracy = isset($data['accuracy']) ? $data['accuracy'] : null;

try {
    $pdo->beginTransaction();

    $updateStmt = $pdo->prepare("UPDATE user_locations SET is_current = 0 WHERE user_id = ?");
    $updateStmt->execute([$userId]);

    $insertStmt = $pdo->prepare("
        INSERT INTO user_locations (user_id, latitude, longitude, address, accuracy, is_current, created_at) 
        VALUES (?, ?, ?, ?, ?, 1, NOW())
    ");
    $insertStmt->execute([$userId, $latitude, $longitude, $address, $accuracy]);

    $pdo->commit();

    apiResponse::success(null, 'Location updated successfully.');

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Location Update DB Error: " . $e->getMessage());
    apiResponse::error('Failed to update location.', 500, $e->getMessage());
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Location Update Error: " . $e->getMessage());
    apiResponse::error('An unexpected error occurred.', 500, $e->getMessage());
}
?>
