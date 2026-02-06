<?php
// subscription_settings.php
// Handles fetching and updating notification category subscriptions
header('Content-Type: application/json');

require_once 'db_connect.php';
/** @var PDO $pdo */

$method = $_SERVER['REQUEST_METHOD'];

// 1. GET: Fetch all categories and their status for the user
if ($method === 'GET') {
    if (!isset($_GET['user_id'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'User ID is required.']);
        exit();
    }

    $userId = $_GET['user_id'];

    try {
        // We select ALL categories, and LEFT JOIN with subscriptions to see if the user has turned them off.
        // We assume '1' (Active) if no record exists yet.
        $sql = "
            SELECT 
                ac.id AS category_id,
                ac.name,
                ac.icon,
                ac.description,
                COALESCE(us.is_active, 1) AS is_subscribed
            FROM alert_categories ac
            LEFT JOIN user_subscriptions us 
                ON ac.id = us.category_id AND us.user_id = ?
            ORDER BY ac.id ASC
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$userId]);
        $categories = $stmt->fetchAll();

        echo json_encode(['success' => true, 'data' => $categories]);

    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to fetch subscriptions.']);
    }
}

// 2. POST: Update a specific subscription (Toggle On/Off)
elseif ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    if (!isset($data['user_id']) || !isset($data['category_id']) || !isset($data['is_active'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Missing required fields.']);
        exit();
    }

    $userId = $data['user_id'];
    $categoryId = $data['category_id'];
    $isActive = (int)$data['is_active']; // 1 for On, 0 for Off

    try {
        // Upsert: Insert if new, Update if exists
        $sql = "
            INSERT INTO user_subscriptions (user_id, category_id, is_active, updated_at)
            VALUES (?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE is_active = VALUES(is_active), updated_at = NOW()
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$userId, $categoryId, $isActive]);

        echo json_encode(['success' => true, 'message' => 'Subscription updated.']);

    } catch (PDOException $e) {
        http_response_code(500);
        error_log("Subscription update error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Failed to update subscription.']);
    }
}
?>