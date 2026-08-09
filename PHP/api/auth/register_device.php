<?php
header('Content-Type: application/json; charset=utf-8');

require_once '../shared/db_connect.php';
require_once '../device_registry.php';

/** @var PDO $pdo */

$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'POST') {
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);

        if (!is_array($data)) {
            apiResponse::error("Invalid JSON input.", 400);
        }

        $user_id = isset($data['user_id']) && (int)$data['user_id'] > 0 ? (int)$data['user_id'] : null;
        $device_id = $data['device_id'] ?? null;
        $device_type = $data['device_type'] ?? 'mobile';
        $device_name = $data['device_name'] ?? null;
        $expo_push_token = trim((string)($data['push_token'] ?? ''));
        $native_fcm_token = trim((string)($data['fcm_token'] ?? ''));
        $primary_push_token = $expo_push_token !== '' ? $expo_push_token : $native_fcm_token;
        $fcm_token = $native_fcm_token !== '' ? $native_fcm_token : $primary_push_token;
        $token_type = $native_fcm_token !== '' ? 'fcm' : trim((string)($data['token_type'] ?? 'expo'));
        $permission = trim((string)($data['notification_permission'] ?? 'granted'));
        $notification_channel = trim((string)($data['notification_channel'] ?? 'alertara-emergency-default-v3'));
        $notification_sound = trim((string)($data['notification_sound'] ?? 'emergency'));

        if (!$device_id || (!$user_id && $primary_push_token === '')) {
            apiResponse::error("Missing required fields: device_id and either user_id or push_token", 400);
        }

        if ($primary_push_token !== '') {
            registerAppNotificationDevice(
                $pdo,
                $user_id,
                (string)$device_id,
                (string)$device_type,
                $device_name,
                $primary_push_token,
                $token_type,
                $permission,
                $native_fcm_token !== '' ? $native_fcm_token : null,
                $notification_channel,
                $notification_sound
            );
        }

        if (!$user_id) {
            apiResponse::success([
                'device_id' => $device_id,
                'action' => 'registered',
                'guest' => true
            ], "Guest notification device registered successfully");
        }

        $deviceTable = resolveDeviceRegistryTable($pdo);
        $checkQuery = "SELECT id FROM {$deviceTable} WHERE user_id = ? AND device_id = ?";
        $checkStmt = $pdo->prepare($checkQuery);
        $checkStmt->execute([$user_id, $device_id]);
        $existingDevice = $checkStmt->fetch(PDO::FETCH_ASSOC);

        if ($existingDevice) {
            $updateQuery = "
                UPDATE {$deviceTable}
                SET device_type = ?,
                    device_name = ?,
                    fcm_token = ?,
                    push_token = ?,
                    is_active = 1,
                    last_active = NOW()
                WHERE id = ?
            ";
            $updateStmt = $pdo->prepare($updateQuery);
            $updateStmt->execute([
                $device_type,
                $device_name,
                $fcm_token,
                $primary_push_token,
                $existingDevice['id']
            ]);

            apiResponse::success([
                'device_id' => $device_id,
                'action' => 'updated'
            ], "Device updated successfully");
        } else {
            $insertQuery = "
                INSERT INTO {$deviceTable}
                (user_id, device_id, device_type, device_name, fcm_token, push_token, is_active, last_active)
                VALUES (?, ?, ?, ?, ?, ?, 1, NOW())
            ";
            $insertStmt = $pdo->prepare($insertQuery);
            $insertStmt->execute([
                $user_id,
                $device_id,
                $device_type,
                $device_name,
                $fcm_token,
                $primary_push_token
            ]);

            apiResponse::success([
                'device_id' => $device_id,
                'action' => 'registered'
            ], "Device registered successfully");
        }

    } elseif ($method === 'GET') {
        $userId = isset($_GET['user_id']) ? $_GET['user_id'] : null;

        if (!$userId) {
            apiResponse::error("Missing required parameter: user_id", 400);
        }

        $deviceTable = resolveDeviceRegistryTable($pdo);
        $query = "
            SELECT
                id,
                device_id,
                device_type,
                device_name,
                fcm_token,
                push_token,
                is_active,
                last_active,
                created_at
            FROM {$deviceTable}
            WHERE user_id = :user_id
            ORDER BY last_active DESC
        ";

        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        $devices = $stmt->fetchAll(PDO::FETCH_ASSOC);

        apiResponse::success($devices, "User devices fetched successfully");

    } elseif ($method === 'DELETE') {
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);

        if (!is_array($data)) {
            apiResponse::error("Invalid JSON input.", 400);
        }

        $user_id = $data['user_id'] ?? null;
        $device_id = $data['device_id'] ?? null;

        if (!$user_id || !$device_id) {
            apiResponse::error("Missing required fields: user_id, device_id", 400);
        }

        $deviceTable = resolveDeviceRegistryTable($pdo);
        $query = "
            UPDATE {$deviceTable}
            SET is_active = 0
            WHERE user_id = ? AND device_id = ?
        ";

        $stmt = $pdo->prepare($query);
        $stmt->execute([$user_id, $device_id]);

        if ($stmt->rowCount() > 0) {
            apiResponse::success([
                'device_id' => $device_id,
                'action' => 'deactivated'
            ], "Device deactivated successfully");
        } else {
            apiResponse::error("Device not found", 404);
        }

    } else {
        apiResponse::error("Invalid request method. Use GET, POST, or DELETE.", 405);
    }

} catch (PDOException $e) {
    error_log("User Devices DB Error: " . $e->getMessage());
    apiResponse::error("Database error occurred.", 500);
} catch (Exception $e) {
    error_log("User Devices Error: " . $e->getMessage());
    apiResponse::error("An unexpected error occurred.", 500);
}


