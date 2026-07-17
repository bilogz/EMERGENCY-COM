<?php
header('Content-Type: application/json; charset=utf-8');

require_once '../shared/db_connect.php';

/** @var PDO $pdo */

// Handle different HTTP methods
$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'POST') {
        // Log user activity
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);

        if (!is_array($data)) {
            apiResponse::error("Invalid JSON input.", 400);
        }

        $user_id = $data['user_id'] ?? null;
        $activity_type = $data['activity_type'] ?? null;
        $description = $data['description'] ?? null;
        $ip_address = $data['ip_address'] ?? null;
        $user_agent = $data['user_agent'] ?? null;
        $status = $data['status'] ?? 'success';
        $metadata = $data['metadata'] ?? null;

        // Validate required fields
        if (!$user_id || !$activity_type) {
            apiResponse::error("Missing required fields: user_id, activity_type", 400);
        }

        // Encode metadata as JSON if provided
        $metadataJson = null;
        if ($metadata && is_array($metadata)) {
            $metadataJson = json_encode($metadata);
        } elseif ($metadata && is_string($metadata)) {
            $metadataJson = $metadata;
        }

        $insertQuery = "
            INSERT INTO user_activity_logs
            (user_id, activity_type, description, ip_address, user_agent, status, metadata, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
        ";
        $insertStmt = $pdo->prepare($insertQuery);
        $insertStmt->execute([
            $user_id,
            $activity_type,
            $description,
            $ip_address,
            $user_agent,
            $status,
            $metadataJson
        ]);

        apiResponse::success([
            'log_id' => $pdo->lastInsertId(),
            'action' => 'logged'
        ], "Activity logged successfully");

    } elseif ($method === 'GET') {
        // Get user activity logs
        $userId = isset($_GET['user_id']) ? $_GET['user_id'] : null;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
        $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
        $activityType = isset($_GET['activity_type']) ? $_GET['activity_type'] : null;

        if (!$userId) {
            apiResponse::error("Missing required parameter: user_id", 400);
        }

        $query = "
            SELECT
                id,
                user_id,
                activity_type,
                description,
                ip_address,
                user_agent,
                status,
                metadata,
                created_at
            FROM user_activity_logs
            WHERE user_id = :user_id
        ";

        if ($activityType) {
            $query .= " AND activity_type = :activity_type";
        }

        $query .= " ORDER BY created_at DESC LIMIT :limit OFFSET :offset";

        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        
        if ($activityType) {
            $stmt->bindParam(':activity_type', $activityType, PDO::PARAM_STR);
        }
        
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Decode metadata JSON for each log
        $logs = array_map(function($log) {
            if ($log['metadata']) {
                $decoded = json_decode($log['metadata'], true);
                if (is_array($decoded)) {
                    $log['metadata'] = $decoded;
                }
            }
            return $log;
        }, $logs);

        apiResponse::success($logs, "User activity logs fetched successfully");

    } else {
        apiResponse::error("Invalid request method. Use GET or POST.", 405);
    }

} catch (PDOException $e) {
    error_log("User Activity Logs DB Error: " . $e->getMessage());
    apiResponse::error("Database error occurred.", 500);
} catch (Exception $e) {
    error_log("User Activity Logs Error: " . $e->getMessage());
    apiResponse::error("An unexpected error occurred.", 500);
}
