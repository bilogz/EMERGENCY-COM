<?php
header('Content-Type: application/json; charset=utf-8');

require_once '../shared/db_connect.php';
require_once __DIR__ . '/../../../ADMIN/api/chat-logic.php';

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
        $user_name = trim((string)($data['user_name'] ?? 'Guest User'));
        $user_email = trim((string)($data['user_email'] ?? ''));
        $user_phone = trim((string)($data['user_phone'] ?? ''));
        $user_location = trim((string)($data['user_location'] ?? ''));
        $severity = strtolower(trim((string)($data['severity'] ?? 'medium')));

        // Allow anonymous reports (guest mode) with user_id = 0
        if ($user_id === null || $user_id === '') {
            $user_id = 0;
        } elseif (!is_numeric($user_id)) {
            apiResponse::error("Invalid user_id.", 400);
        } else {
            $user_id = (int) $user_id;
        }

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

        // Handle media_url - if it's a base64 data URI, truncate or handle appropriately
        // For now, we'll store it as-is but the database column needs to be TEXT type
        if ($media_url && strlen($media_url) > 255) {
            // Log warning but still try to insert
            error_log("media_url is too long for VARCHAR(255): " . strlen($media_url) . " chars");
        }

        if (!in_array($severity, ['low', 'medium', 'high'], true)) {
            $severity = 'medium';
        }

        // Never trust editable client identity fields for a registered user.
        // Pull the caller's current account details from the shared users table.
        if ($user_id > 0) {
            $userStmt = $pdo->prepare('SELECT name, email, phone FROM users WHERE id = ? LIMIT 1');
            $userStmt->execute([$user_id]);
            $registeredUser = $userStmt->fetch(PDO::FETCH_ASSOC);
            if (!$registeredUser) {
                apiResponse::error('Registered user was not found.', 404);
            }
            $user_name = trim((string)$registeredUser['name']);
            $user_email = trim((string)($registeredUser['email'] ?? ''));
            $user_phone = trim((string)($registeredUser['phone'] ?? ''));
        } else {
            $user_name = $user_name !== '' ? $user_name : 'Guest User';
        }

        $reportTypeLabels = [
            'medical' => 'Medical Emergency',
            'fire' => 'Fire Emergency',
            'traffic' => 'Vehicular Accident',
            'natural_disaster' => 'Flood or Weather Incident',
            'crime' => 'Crime or Public Safety',
            'other' => 'Other Incident',
        ];
        $categoryLabel = $reportTypeLabels[$report_type] ?? 'Other Incident';
        $priorityBySeverity = [
            'low' => ['score' => 17, 'level' => 'low', 'color' => 'green'],
            'medium' => ['score' => 55, 'level' => 'urgent', 'color' => 'yellow'],
            'high' => ['score' => 90, 'level' => 'critical', 'color' => 'red'],
        ];
        $priority = $priorityBySeverity[$severity];

        $coordinates = '';
        if ($latitude !== null && $longitude !== null) {
            $coordinates = number_format((float)$latitude, 6, '.', '') . ', ' . number_format((float)$longitude, 6, '.', '');
        }
        $reportLines = [
            'Incident Type: ' . $categoryLabel,
            $user_location !== '' ? 'Location: ' . $user_location : '',
            $coordinates !== '' ? 'Coordinates: ' . $coordinates : '',
            $coordinates !== '' ? 'Map: https://www.google.com/maps?q=' . rawurlencode($coordinates) : '',
            'Severity: ' . ucfirst($severity),
            '',
            trim((string)$description),
        ];
        $conversationMessage = implode("\n", array_values(array_filter(
            $reportLines,
            static fn($line, $index) => $line !== '' || $index === 5,
            ARRAY_FILTER_USE_BOTH
        )));

        $pdo->beginTransaction();

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

        // Create the admin-visible Two-Way Communication thread in the same
        // transaction. A report cannot succeed without also reaching the queue.
        $conversationColumns = [
            'user_id', 'user_name', 'user_email', 'user_phone', 'user_location',
            'user_concern', 'is_guest', 'status', 'last_message', 'last_message_time',
            'device_info', 'ip_address', 'user_agent', 'created_at', 'updated_at'
        ];
        $deviceInfo = json_encode([
            'platform' => 'mobile',
            'source' => 'alertara_mobile',
            'incident_report_id' => (int)$reportId,
        ]);
        $ipAddress = trim((string)($_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? ''));
        if (strpos($ipAddress, ',') !== false) {
            $ipAddress = trim(explode(',', $ipAddress)[0]);
        }
        $conversationValues = [
            $user_id > 0 ? $user_id : null,
            $user_name,
            $user_email !== '' ? $user_email : null,
            $user_phone !== '' ? $user_phone : null,
            $user_location !== '' ? $user_location : ($coordinates !== '' ? $coordinates : null),
            'incident_report',
            $user_id > 0 ? 0 : 1,
            twc_status_for_db($pdo, 'open'),
            substr($conversationMessage, 0, 255),
            date('Y-m-d H:i:s'),
            $deviceInfo,
            $ipAddress,
            $_SERVER['HTTP_USER_AGENT'] ?? 'Alertara Mobile',
            date('Y-m-d H:i:s'),
            date('Y-m-d H:i:s'),
        ];
        $optionalPriorityValues = [
            'incident_priority_score' => $priority['score'],
            'incident_priority_level' => $priority['level'],
            'incident_priority_color' => $priority['color'],
            'incident_priority_breakdown' => json_encode(['source' => 'mobile_report', 'severity' => $severity]),
            'incident_priority_manual' => 0,
        ];
        foreach ($optionalPriorityValues as $column => $value) {
            if (twc_column_exists($pdo, 'conversations', $column)) {
                $conversationColumns[] = $column;
                $conversationValues[] = $value;
            }
        }
        if (twc_column_exists($pdo, 'conversations', 'category')) {
            $conversationColumns[] = 'category';
            $conversationValues[] = $categoryLabel;
        }
        if (twc_column_exists($pdo, 'conversations', 'priority')) {
            $conversationColumns[] = 'priority';
            $conversationValues[] = $severity === 'low' ? 'normal' : 'urgent';
        }
        $conversationSql = 'INSERT INTO conversations (' . implode(', ', $conversationColumns) . ') VALUES ('
            . implode(', ', array_fill(0, count($conversationValues), '?')) . ')';
        $conversationStmt = $pdo->prepare($conversationSql);
        $conversationStmt->execute($conversationValues);
        $conversationId = (int)$pdo->lastInsertId();

        $attachmentMime = null;
        if ($media_url) {
            $extension = strtolower((string)pathinfo(parse_url($media_url, PHP_URL_PATH) ?? '', PATHINFO_EXTENSION));
            $attachmentMime = in_array($extension, ['mp4', 'mov', 'webm'], true) ? 'video/' . $extension : 'image/' . ($extension ?: 'jpeg');
        }
        $messageColumns = [
            'conversation_id', 'sender_id', 'sender_name', 'sender_type',
            'message_text', 'ip_address', 'device_info', 'is_read', 'created_at'
        ];
        $messageValues = [
            $conversationId,
            $user_id > 0 ? $user_id : 'guest',
            $user_name,
            'user',
            $conversationMessage,
            $ipAddress,
            $deviceInfo,
            0,
            date('Y-m-d H:i:s'),
        ];
        if (twc_column_exists($pdo, 'chat_messages', 'attachment_url')) {
            $messageColumns[] = 'attachment_url';
            $messageValues[] = $media_url ?: null;
        }
        if (twc_column_exists($pdo, 'chat_messages', 'attachment_mime')) {
            $messageColumns[] = 'attachment_mime';
            $messageValues[] = $attachmentMime;
        }
        $messageSql = 'INSERT INTO chat_messages (' . implode(', ', $messageColumns) . ') VALUES ('
            . implode(', ', array_fill(0, count($messageValues), '?')) . ')';
        $messageStmt = $pdo->prepare($messageSql);
        $messageStmt->execute($messageValues);

        $selectQuery = "
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
            WHERE id = ?
        ";
        $selectStmt = $pdo->prepare($selectQuery);
        $selectStmt->execute([$reportId]);
        $report = $selectStmt->fetch(PDO::FETCH_ASSOC);

        $pdo->commit();
        $report['conversation_id'] = $conversationId;

        apiResponse::success($report, "Emergency report submitted successfully");

    } elseif ($method === 'GET') {
        // Get emergency reports
        $userId = isset($_GET['user_id']) ? $_GET['user_id'] : null;

        $sql = "
            SELECT
                ir.id,
                ir.user_id,
                ir.report_type,
                ir.description,
                ir.latitude,
                ir.longitude,
                ir.status,
                ir.media_url,
                ir.admin_notes,
                ir.created_at,
                (
                    SELECT c.conversation_id
                    FROM conversations c
                    WHERE JSON_VALID(c.device_info)
                      AND JSON_UNQUOTE(JSON_EXTRACT(c.device_info, '$.incident_report_id')) = CAST(ir.id AS CHAR)
                    ORDER BY c.conversation_id DESC
                    LIMIT 1
                ) AS conversation_id
            FROM incident_reports ir
        ";

        if ($userId) {
            $sql .= " WHERE ir.user_id = :user_id";
        }

        $sql .= " ORDER BY ir.created_at DESC";

        $stmt = $pdo->prepare($sql);

        if ($userId) {
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        }

        $stmt->execute();
        $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);

        apiResponse::success(['reports' => $reports], "Emergency reports fetched successfully");

    } elseif ($method === 'PUT' || $method === 'PATCH') {
        // Update incident status
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);

        if (!is_array($data)) {
            apiResponse::error("Invalid JSON input.", 400);
        }

        $report_id = $data['report_id'] ?? null;
        $status = $data['status'] ?? null;
        $admin_notes = $data['admin_notes'] ?? null;

        // Validate required fields
        if (!$report_id || !$status) {
            apiResponse::error("Missing required fields: report_id, status", 400);
        }

        // Validate status
        $validStatuses = ['pending', 'received', 'in_progress', 'resolved', 'rejected'];
        if (!in_array($status, $validStatuses)) {
            apiResponse::error("Invalid status. Must be one of: " . implode(', ', $validStatuses), 400);
        }

        // Check if report exists
        $checkQuery = "SELECT id FROM incident_reports WHERE id = ?";
        $checkStmt = $pdo->prepare($checkQuery);
        $checkStmt->execute([$report_id]);
        $existingReport = $checkStmt->fetch(PDO::FETCH_ASSOC);

        if (!$existingReport) {
            apiResponse::error("Report not found", 404);
        }

        // Update status
        $updateQuery = "
            UPDATE incident_reports
            SET status = ?,
                admin_notes = ?
            WHERE id = ?
        ";

        $updateStmt = $pdo->prepare($updateQuery);
        $updateStmt->execute([$status, $admin_notes, $report_id]);

        apiResponse::success([
            'report_id' => $report_id,
            'status' => $status,
            'admin_notes' => $admin_notes
        ], "Incident status updated successfully");

    } else {
        apiResponse::error("Invalid request method. Use GET, POST, PUT, or PATCH.", 405);
    }

} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Emergency Reports DB Error: " . $e->getMessage());
    error_log("Emergency Reports DB Error Code: " . $e->getCode());
    error_log("Emergency Reports DB Error Trace: " . $e->getTraceAsString());
    apiResponse::error("Unable to submit or update the emergency report.", 500);
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Emergency Reports Error: " . $e->getMessage());
    apiResponse::error("An unexpected error occurred while processing the emergency report.", 500);
}
