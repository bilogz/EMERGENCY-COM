<?php
header('Content-Type: application/json; charset=utf-8');
session_start();

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/db_connect.php';

try {
    $limit = 10;
    $transfers = [];
    $assignments = [];

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS transfer_call_audit (
            id INT AUTO_INCREMENT PRIMARY KEY,
            call_id VARCHAR(128) NULL,
            conversation_id INT NULL,
            emergency_type VARCHAR(80) NULL,
            caller_name VARCHAR(255) NULL,
            caller_phone VARCHAR(80) NULL,
            caller_address TEXT NULL,
            payload JSON NULL,
            integration_url TEXT NULL,
            integration_status INT NULL,
            integration_response MEDIUMTEXT NULL,
            status VARCHAR(30) NOT NULL DEFAULT 'prepared',
            response_status VARCHAR(80) NULL,
            response_status_note TEXT NULL,
            status_requested_at DATETIME NULL,
            status_updated_at DATETIME NULL,
            requested_by VARCHAR(255) NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    $stmt = $pdo->prepare("
        SELECT id, call_id, conversation_id, emergency_type, caller_name, caller_phone,
               status, response_status, response_status_note, requested_by, created_at
        FROM transfer_call_audit
        ORDER BY id DESC
        LIMIT ?
    ");
    $stmt->bindValue(1, $limit, PDO::PARAM_INT);
    $stmt->execute();
    $transfers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS twc_assignment_audit (
            id INT AUTO_INCREMENT PRIMARY KEY,
            conversation_id INT NOT NULL,
            action VARCHAR(40) NOT NULL,
            admin_id INT NULL,
            admin_name VARCHAR(255) NULL,
            previous_admin_id INT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    $stmt = $pdo->prepare("
        SELECT id, conversation_id, action, admin_id, admin_name, previous_admin_id, created_at
        FROM twc_assignment_audit
        ORDER BY id DESC
        LIMIT ?
    ");
    $stmt->bindValue(1, $limit, PDO::PARAM_INT);
    $stmt->execute();
    $assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'transfers' => $transfers,
        'assignments' => $assignments,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to load two-way audit summary']);
}
