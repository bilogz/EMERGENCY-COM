<?php
/**
 * UNIFIED AUDIT TRAIL ENDPOINT
 * 
 * GET: Retrieve list of audit logs (notification broadcasts and API request logs)
 */

error_reporting(E_ALL);
ini_set('display_errors', 0);
ob_start();

require_once __DIR__ . '/auth.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method !== 'GET') {
    sendJsonResponse(false, 'Method Not Allowed.', [], 405);
}

try {
    $type = isset($_GET['type']) ? strtolower(trim($_GET['type'])) : 'notifications';
    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $limit = isset($_GET['limit']) ? min(100, max(1, (int)$_GET['limit'])) : 50;
    $offset = ($page - 1) * $limit;

    $params = [];
    $query = "";
    $countQuery = "";

    // Action 1: System Notification Broadcast Logs Audit Trail
    if ($type === 'notifications') {
        // Ensure table exists
        try {
            $pdo->query("SELECT 1 FROM notification_logs LIMIT 1");
        } catch (Throwable $e) {
            logApiAccess($pdo, $deptName, '/api/audit.php?type=notifications', 'GET', 404, "notification_logs table not found");
            sendJsonResponse(false, 'Notification logs audit trail is currently empty or not found.', [], 404);
        }

        $query = "SELECT id, channel, message, recipients, priority, status, sent_at, sent_by, ip_address FROM notification_logs";
        $countQuery = "SELECT COUNT(*) FROM notification_logs";
        
        $channel = isset($_GET['channel']) && $_GET['channel'] !== '' ? trim($_GET['channel']) : null;
        $status = isset($_GET['status']) && $_GET['status'] !== '' ? trim($_GET['status']) : null;
        $where = [];

        if ($channel) {
            $where[] = "channel LIKE ?";
            $params[] = "%$channel%";
        }
        if ($status) {
            $where[] = "status = ?";
            $params[] = $status;
        }

        if (!empty($where)) {
            $clause = " WHERE " . implode(" AND ", $where);
            $query .= $clause;
            $countQuery .= $clause;
        }

        $query .= " ORDER BY sent_at DESC LIMIT ? OFFSET ?";
        
    } 
    // Action 2: Connected Departments API request logs Audit Trail
    elseif ($type === 'api' || $type === 'requests') {
        try {
            $pdo->query("SELECT 1 FROM department_api_logs LIMIT 1");
        } catch (Throwable $e) {
            sendJsonResponse(true, 'API logs audit trail is currently empty.', ['logs' => []]);
        }

        $query = "SELECT id, department_name, endpoint, method, status_code, ip_address, details, created_at FROM department_api_logs";
        $countQuery = "SELECT COUNT(*) FROM department_api_logs";

        $reqDept = isset($_GET['req_department']) && $_GET['req_department'] !== '' ? trim($_GET['req_department']) : null;
        $status = isset($_GET['status_code']) && $_GET['status_code'] !== '' ? (int)$_GET['status_code'] : null;
        $where = [];

        if ($reqDept) {
            $where[] = "department_name = ?";
            $params[] = $reqDept;
        }
        if ($status) {
            $where[] = "status_code = ?";
            $params[] = $status;
        }

        if (!empty($where)) {
            $clause = " WHERE " . implode(" AND ", $where);
            $query .= $clause;
            $countQuery .= $clause;
        }

        $query .= " ORDER BY id DESC LIMIT ? OFFSET ?";
    } else {
        sendJsonResponse(false, 'Bad Request: Invalid type parameter. Use notifications or api.', [], 400);
    }

    // Get Total Count
    $countStmt = $pdo->prepare($countQuery);
    $countStmt->execute($params);
    $totalRecords = (int)$countStmt->fetchColumn();

    // Get Data
    $stmt = $pdo->prepare($query);
    $bindIdx = 1;
    foreach ($params as $val) {
        $stmt->bindValue($bindIdx++, $val, PDO::PARAM_STR);
    }
    $stmt->bindValue($bindIdx++, $limit, PDO::PARAM_INT);
    $stmt->bindValue($bindIdx++, $offset, PDO::PARAM_INT);
    $stmt->execute();
    
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $totalPages = ceil($totalRecords / $limit);

    logApiAccess($pdo, $deptName, "/api/audit.php?type=$type", 'GET', 200, "Retrieved " . count($logs) . " audit logs");
    sendJsonResponse(true, 'Audit logs retrieved successfully.', [
        'logs' => $logs,
        'pagination' => [
            'page' => $page,
            'limit' => $limit,
            'total_records' => $totalRecords,
            'total_pages' => $totalPages
        ]
    ]);

} catch (PDOException $e) {
    logApiAccess($pdo, $deptName, '/api/audit.php', 'GET', 500, "Database query failed: " . $e->getMessage());
    sendJsonResponse(false, 'Database query failed: ' . $e->getMessage(), [], 500);
}
