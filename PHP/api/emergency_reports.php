<?php
header('Content-Type: application/json; charset=utf-8');

require_once 'db_connect.php';

/** @var PDO $pdo */

// Handle different HTTP methods
$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'POST') {
        // Submit emergency report
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);

        if (!is_array($data)) {
            apiResponse::error("Invalid JSON input.", 400);
        }

        $type = $data['type'] ?? null;
        $description = $data['description'] ?? null;
        $location = $data['location'] ?? null;
        $latitude = $data['latitude'] ?? null;
        $longitude = $data['longitude'] ?? null;
        $severity = $data['severity'] ?? null;
        $contact_phone = $data['contact_phone'] ?? null;
        $user_id = $data['user_id'] ?? null;

        // Validate required fields
        if (!$type || !$description || !$location || !$severity) {
            apiResponse::error("Missing required fields: type, description, location, severity", 400);
        }

        // Validate type
        $validTypes = ['crime', 'fire', 'medical', 'traffic', 'natural_disaster', 'other'];
        if (!in_array($type, $validTypes)) {
            apiResponse::error("Invalid type. Must be one of: " . implode(', ', $validTypes), 400);
        }

        // Validate severity
        $validSeverities = ['low', 'medium', 'high', 'critical'];
        if (!in_array($severity, $validSeverities)) {
            apiResponse::error("Invalid severity. Must be one of: " . implode(', ', $validSeverities), 400);
        }

        // Validate coordinates if provided
        if ($latitude !== null && !is_numeric($latitude)) {
            apiResponse::error("Invalid latitude.", 400);
        }

        if ($longitude !== null && !is_numeric($longitude)) {
            apiResponse::error("Invalid longitude.", 400);
        }

        $query = "
            INSERT INTO incident_reports
            (type, description, location, latitude, longitude, severity, contact_phone, user_id, status, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW(), NOW())
        ";

        $stmt = $pdo->prepare($query);
        $stmt->execute([
            $type,
            $description,
            $location,
            $latitude,
            $longitude,
            $severity,
            $contact_phone,
            $user_id
        ]);

        $reportId = $pdo->lastInsertId();

        apiResponse::success([
            'id' => $reportId,
            'type' => $type,
            'description' => $description,
            'location' => $location,
            'severity' => $severity,
            'status' => 'pending'
        ], "Emergency report submitted successfully");

    } elseif ($method === 'GET') {
        // Get emergency reports
        $userId = isset($_GET['user_id']) ? $_GET['user_id'] : null;

        $sql = "
            SELECT
                id,
                type,
                description,
                location,
                latitude,
                longitude,
                severity,
                contact_phone,
                user_id,
                status,
                created_at
            FROM incident_reports
        ";

        if ($userId) {
            $sql .= " WHERE user_id = :user_id";
        }

        $sql .= " ORDER BY created_at DESC";

        $stmt = $pdo->prepare($sql);

        if ($userId) {
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        }

        $stmt->execute();
        $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);

        apiResponse::success(['reports' => $reports], "Emergency reports fetched successfully");

    } else {
        apiResponse::error("Invalid request method. Use GET or POST.", 405);
    }

} catch (PDOException $e) {
    error_log("Emergency Reports DB Error: " . $e->getMessage());
    apiResponse::error("Database error occurred.", 500);
} catch (Exception $e) {
    error_log("Emergency Reports Error: " . $e->getMessage());
    apiResponse::error("An unexpected error occurred.", 500);
}