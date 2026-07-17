<?php
header('Content-Type: application/json; charset=utf-8');

require_once '../shared/db_connect.php';

/** @var PDO $pdo */

// Handle different HTTP methods
$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'POST') {
        // Create or update location
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);

        if (!is_array($data)) {
            apiResponse::error("Invalid JSON input.", 400);
        }

        $user_id = $data['user_id'] ?? null;
        $latitude = $data['latitude'] ?? null;
        $longitude = $data['longitude'] ?? null;
        $address = $data['address'] ?? null;
        $accuracy = $data['accuracy'] ?? null;
        $source = $data['source'] ?? 'gps';
        $is_current = $data['is_current'] ?? 1;

        // Validate required fields
        if (!$user_id || !$latitude || !$longitude) {
            apiResponse::error("Missing required fields: user_id, latitude, longitude", 400);
        }

        // If this is marked as current, set all other locations to not current
        if ($is_current) {
            $resetCurrentQuery = "UPDATE user_locations SET is_current = 0 WHERE user_id = ?";
            $resetStmt = $pdo->prepare($resetCurrentQuery);
            $resetStmt->execute([$user_id]);
        }

        // Check if location already exists (same coordinates)
        $checkQuery = "SELECT id FROM user_locations WHERE user_id = ? AND latitude = ? AND longitude = ?";
        $checkStmt = $pdo->prepare($checkQuery);
        $checkStmt->execute([$user_id, $latitude, $longitude]);
        $existingLocation = $checkStmt->fetch(PDO::FETCH_ASSOC);

        if ($existingLocation) {
            // Update existing location
            $updateQuery = "
                UPDATE user_locations
                SET address = ?,
                    accuracy = ?,
                    source = ?,
                    is_current = ?,
                    created_at = NOW()
                WHERE id = ?
            ";
            $updateStmt = $pdo->prepare($updateQuery);
            $updateStmt->execute([
                $address,
                $accuracy,
                $source,
                $is_current,
                $existingLocation['id']
            ]);

            apiResponse::success([
                'location_id' => $existingLocation['id'],
                'action' => 'updated'
            ], "Location updated successfully");
        } else {
            // Insert new location
            $insertQuery = "
                INSERT INTO user_locations
                (user_id, latitude, longitude, address, accuracy, source, is_current, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
            ";
            $insertStmt = $pdo->prepare($insertQuery);
            $insertStmt->execute([
                $user_id,
                $latitude,
                $longitude,
                $address,
                $accuracy,
                $source,
                $is_current
            ]);

            apiResponse::success([
                'location_id' => $pdo->lastInsertId(),
                'action' => 'created'
            ], "Location created successfully");
        }

    } elseif ($method === 'GET') {
        // Get user locations
        $userId = isset($_GET['user_id']) ? $_GET['user_id'] : null;
        $currentOnly = isset($_GET['current_only']) ? $_GET['current_only'] : '0';

        if (!$userId) {
            apiResponse::error("Missing required parameter: user_id", 400);
        }

        $query = "
            SELECT
                id,
                user_id,
                latitude,
                longitude,
                address,
                accuracy,
                source,
                is_current,
                created_at
            FROM user_locations
            WHERE user_id = :user_id
        ";

        if ($currentOnly === '1') {
            $query .= " AND is_current = 1";
        }

        $query .= " ORDER BY created_at DESC";

        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        $locations = $stmt->fetchAll(PDO::FETCH_ASSOC);

        apiResponse::success($locations, "User locations fetched successfully");

    } elseif ($method === 'DELETE') {
        // Delete location
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);

        if (!is_array($data)) {
            apiResponse::error("Invalid JSON input.", 400);
        }

        $user_id = $data['user_id'] ?? null;
        $location_id = $data['location_id'] ?? null;

        if (!$user_id || !$location_id) {
            apiResponse::error("Missing required fields: user_id, location_id", 400);
        }

        $query = "DELETE FROM user_locations WHERE user_id = ? AND id = ?";

        $stmt = $pdo->prepare($query);
        $stmt->execute([$user_id, $location_id]);

        if ($stmt->rowCount() > 0) {
            apiResponse::success([
                'location_id' => $location_id,
                'action' => 'deleted'
            ], "Location deleted successfully");
        } else {
            apiResponse::error("Location not found", 404);
        }

    } else {
        apiResponse::error("Invalid request method. Use GET, POST, or DELETE.", 405);
    }

} catch (PDOException $e) {
    error_log("User Locations DB Error: " . $e->getMessage());
    apiResponse::error("Database error occurred.", 500);
} catch (Exception $e) {
    error_log("User Locations Error: " . $e->getMessage());
    apiResponse::error("An unexpected error occurred.", 500);
}
