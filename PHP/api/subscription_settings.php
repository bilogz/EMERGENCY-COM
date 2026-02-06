<?php
// subscription_settings.php
header('Content-Type: application/json');

require_once 'db_connect.php';
/** @var PDO $pdo */

$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'GET') {
        if (!isset($_GET['user_id'])) {
            apiResponse::error('User ID is required.', 400);
        }

        $userId = $_GET['user_id'];

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

        apiResponse::success(['data' => $categories]);
    }

    elseif ($method === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);

        if (!isset($data['user_id']) || !isset($data['category_id']) || !isset($data['is_active'])) {
            apiResponse::error('Missing required fields.', 400);
        }

        $userId = $data['user_id'];
        $categoryId = $data['category_id'];
        $isActive = (int)$data['is_active'];

        $sql = "
            INSERT INTO user_subscriptions (user_id, category_id, is_active, updated_at)
            VALUES (?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE is_active = VALUES(is_active), updated_at = NOW()
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$userId, $categoryId, $isActive]);

        apiResponse::success(null, 'Subscription updated.');
    }
    
    else {
        apiResponse::error('Method not allowed.', 405);
    }

} catch (PDOException $e) {
    error_log("Subscription Error: " . $e->getMessage());
    apiResponse::error('A database error occurred.', 500, $e->getMessage());
} catch (Exception $e) {
    error_log("Subscription General Error: " . $e->getMessage());
    apiResponse::error('An unexpected error occurred.', 500, $e->getMessage());
}
?>
