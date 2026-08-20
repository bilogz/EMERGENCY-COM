<?php
header('Content-Type: application/json');

// Include centralized session configuration
require_once __DIR__ . '/../../session-config.php';

require_once 'db_connect.php';

$response = ['success' => false, 'message' => ''];

try {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input)) {
        $input = $_POST;
    }

    $email = strtolower(trim($input['email'] ?? ''));
    $password = (string)($input['password'] ?? '');

    if ($email === '') {
        throw new Exception('Email is required');
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Please enter a valid email address');
    }

    if ($password === '') {
        throw new Exception('Password is required');
    }

    $query = "SELECT id as user_id, name as full_name, email, phone, password, status FROM users WHERE email = ? LIMIT 1";
    $stmt = $pdo->prepare($query);

    if (!$stmt->execute([$email])) {
        throw new Exception('Database query failed');
    }

    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || empty($user['password']) || !password_verify($password, $user['password'])) {
        throw new Exception('Invalid email or password.');
    }

    if (isset($user['status']) && $user['status'] !== null && strtolower((string)$user['status']) !== 'active') {
        throw new Exception('Your account is not active. Please contact support.');
    }

    // Login successful - session already started by session-config.php
    $_SESSION['user_id'] = $user['user_id'];
    $_SESSION['user_name'] = $user['full_name'] ?? 'User';
    $_SESSION['user_email'] = $user['email'] ?? $email;
    $_SESSION['user_phone'] = $user['phone'] ?? null;
    $_SESSION['phone'] = $user['phone'] ?? null; // Legacy session key used by older pages.
    $_SESSION['user_logged_in'] = true;
    $_SESSION['user_type'] = 'registered';

    // Optional: Log login activity
    try {
        $logQuery = "INSERT INTO login_history (user_id, login_time, ip_address) VALUES (?, NOW(), ?)";
        $logStmt = $pdo->prepare($logQuery);
        $logStmt->execute([$user['user_id'], $_SERVER['REMOTE_ADDR'] ?? '']);
    } catch (Exception $e) {
        // Silently ignore if table doesn't exist
    }

    $response['success'] = true;
    $response['message'] = 'Login successful';
    $response['user_name'] = $user['full_name'] ?? 'User';
    $response['user_id'] = $user['user_id'];
    $response['email'] = $user['email'] ?? $email;
    $response['phone'] = $user['phone'] ?? null;

} catch (Exception $e) {
    $response['success'] = false;
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
?>