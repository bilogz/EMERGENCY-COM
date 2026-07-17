<?php
header('Content-Type: application/json; charset=utf-8');

require_once '../shared/db_connect.php';

/** @var PDO $pdo */

// Handle different HTTP methods
$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'POST') {
        // Create or update session
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);

        if (!is_array($data)) {
            apiResponse::error("Invalid JSON input.", 400);
        }

        $user_id = $data['user_id'] ?? null;
        $session_token = $data['session_token'] ?? null;
        $ip_address = $data['ip_address'] ?? null;
        $user_agent = $data['user_agent'] ?? null;
        $device_type = $data['device_type'] ?? null;
        $location = $data['location'] ?? null;
        $expires_at = $data['expires_at'] ?? null;

        // Validate required fields
        if (!$user_id || !$session_token) {
            apiResponse::error("Missing required fields: user_id, session_token", 400);
        }

        // Set default expiration to 24 hours from now if not provided
        if (!$expires_at) {
            $expires_at = date('Y-m-d H:i:s', strtotime('+24 hours'));
        }

        // Check if session already exists
        $checkQuery = "SELECT id FROM user_sessions WHERE user_id = ? AND session_token = ?";
        $checkStmt = $pdo->prepare($checkQuery);
        $checkStmt->execute([$user_id, $session_token]);
        $existingSession = $checkStmt->fetch(PDO::FETCH_ASSOC);

        if ($existingSession) {
            // Update existing session
            $updateQuery = "
                UPDATE user_sessions
                SET ip_address = ?,
                    user_agent = ?,
                    device_type = ?,
                    location = ?,
                    status = 'active',
                    last_activity = NOW(),
                    expires_at = ?
                WHERE id = ?
            ";
            $updateStmt = $pdo->prepare($updateQuery);
            $updateStmt->execute([
                $ip_address,
                $user_agent,
                $device_type,
                $location,
                $expires_at,
                $existingSession['id']
            ]);

            apiResponse::success([
                'session_id' => $existingSession['id'],
                'action' => 'updated'
            ], "Session updated successfully");
        } else {
            // Insert new session
            $insertQuery = "
                INSERT INTO user_sessions
                (user_id, session_token, ip_address, user_agent, device_type, location, status, last_activity, expires_at)
                VALUES (?, ?, ?, ?, ?, ?, 'active', NOW(), ?)
            ";
            $insertStmt = $pdo->prepare($insertQuery);
            $insertStmt->execute([
                $user_id,
                $session_token,
                $ip_address,
                $user_agent,
                $device_type,
                $location,
                $expires_at
            ]);

            apiResponse::success([
                'session_id' => $pdo->lastInsertId(),
                'action' => 'created'
            ], "Session created successfully");
        }

    } elseif ($method === 'GET') {
        // Get user sessions
        $userId = isset($_GET['user_id']) ? $_GET['user_id'] : null;

        if (!$userId) {
            apiResponse::error("Missing required parameter: user_id", 400);
        }

        $query = "
            SELECT
                id,
                user_id,
                session_token,
                ip_address,
                user_agent,
                device_type,
                location,
                status,
                last_activity,
                expires_at,
                created_at
            FROM user_sessions
            WHERE user_id = :user_id
            ORDER BY last_activity DESC
        ";

        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        $sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);

        apiResponse::success($sessions, "User sessions fetched successfully");

    } elseif ($method === 'DELETE') {
        // End session
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);

        if (!is_array($data)) {
            apiResponse::error("Invalid JSON input.", 400);
        }

        $user_id = $data['user_id'] ?? null;
        $session_token = $data['session_token'] ?? null;

        if (!$user_id || !$session_token) {
            apiResponse::error("Missing required fields: user_id, session_token", 400);
        }

        $query = "
            UPDATE user_sessions
            SET status = 'inactive'
            WHERE user_id = ? AND session_token = ?
        ";

        $stmt = $pdo->prepare($query);
        $stmt->execute([$user_id, $session_token]);

        if ($stmt->rowCount() > 0) {
            apiResponse::success([
                'session_token' => $session_token,
                'action' => 'ended'
            ], "Session ended successfully");
        } else {
            apiResponse::error("Session not found", 404);
        }

    } else {
        apiResponse::error("Invalid request method. Use GET, POST, or DELETE.", 405);
    }

} catch (PDOException $e) {
    error_log("User Sessions DB Error: " . $e->getMessage());
    apiResponse::error("Database error occurred.", 500);
} catch (Exception $e) {
    error_log("User Sessions Error: " . $e->getMessage());
    apiResponse::error("An unexpected error occurred.", 500);
}
