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

        $report_type = $data['report_type'] ?? null;
        $description = $data['description'] ?? null;
        $latitude = $data['latitude'] ?? null;
        $longitude = $data['longitude'] ?? null;
        $user_id = $data['user_id'] ?? null;
        $media_url = $data['media_url'] ?? null;

        // Validate required fields
        if (!$report_type || !$description) {
            apiResponse::error("Missing required fields: report_type, description", 400);
        }

        // Validate type
        $validTypes = ['crime', 'fire', 'medical', 'traffic', 'natural_disaster', 'other'];
        if (!in_array($report_type, $validTypes)) {
            apiResponse::error("Invalid report_type. Must be one of: " . implode(', ', $validTypes), 400);
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
            (user_id, report_type, description, latitude, longitude, media_url, status)
            VALUES (?, ?, ?, ?, ?, ?, 'pending')
        ";

        $stmt = $pdo->prepare($query);
        $stmt->execute([
            $user_id,
            $report_type,
            $description,
            $latitude,
            $longitude,
            $media_url
        ]);

        $reportId = $pdo->lastInsertId();

        apiResponse::success([
            'id' => $reportId,
            'report_type' => $report_type,
            'description' => $description,
            'status' => 'pending'
        ], "Emergency report submitted successfully");

    } elseif ($method === 'GET') {
        // Get emergency reports
        $userId = isset($_GET['user_id']) ? $_GET['user_id'] : null;

        $sql = "
            SELECT
                id,
                user_id,
                report_type,
                description,
                latitude,
                longitude,
                status,
                media_url,
                admin_notes,
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