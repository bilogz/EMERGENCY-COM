<?php
header('Content-Type: application/json; charset=utf-8');

require_once 'db_connect.php';
require_once dirname(__DIR__, 2) . '/PHP/api/shared/user_schema.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['admin_logged_in'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access denied.']);
    exit;
}

try {
    ensureUsersProfileColumns($pdo);
    $stmt = $pdo->query('SHOW COLUMNS FROM users');
    $columns = $stmt ? $stmt->fetchAll(PDO::FETCH_COLUMN, 0) : [];

    echo json_encode([
        'success' => true,
        'message' => 'Users profile columns are ready.',
        'has_nationality' => in_array('nationality', $columns, true),
        'columns_checked' => ['phone', 'nationality', 'district', 'barangay', 'house_number', 'street', 'address', 'profile_pic', 'email_verified', 'user_type']
    ]);
} catch (Throwable $e) {
    error_log('Users profile column setup failed: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Unable to prepare users profile columns.']);
}
