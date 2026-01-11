<?php
/**
 * Create Alert and Notify All Users API
 * Creates an alert with customizable category and severity, then sends push notifications to all users
 * Requires confirmation before sending to all users
 */

header('Content-Type: application/json; charset=utf-8');
require_once 'db_connect.php';
require_once __DIR__ . '/../repositories/AlertRepository.php';
require_once 'push-notification-helper.php';

session_start();

// Require POST method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed. Use POST.']);
    exit;
}

// Require admin authentication
if (!isset($_SESSION['admin_user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized. Admin login required.']);
    exit;
}

try {
    if ($pdo === null) {
        echo json_encode([
            'success' => false,
            'message' => 'Database connection failed'
        ]);
        exit;
    }

    // Get POST parameters
    $title = trim($_POST['title'] ?? '');
    $message = trim($_POST['message'] ?? '');
    $content = trim($_POST['content'] ?? $message);
    $categoryId = isset($_POST['category_id']) ? (int)$_POST['category_id'] : null;
    $severity = strtoupper(trim($_POST['severity'] ?? 'MODERATE'));
    $confirmSendAll = isset($_POST['confirm_send_all']) && $_POST['confirm_send_all'] === 'true';

    // Validate required fields
    if (empty($title)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Title is required']);
        exit;
    }

    if (empty($message)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Message is required']);
        exit;
    }

    // Validate severity
    if (!in_array($severity, ['EXTREME', 'MODERATE'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Severity must be either EXTREME or MODERATE']);
        exit;
    }

    // Require confirmation before sending to all users
    if (!$confirmSendAll) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Confirmation required. Set confirm_send_all=true to send alert to all users.',
            'requires_confirmation' => true
        ]);
        exit;
    }

    $alertRepository = new AlertRepository($pdo);

    // Validate category if provided
    if ($categoryId !== null && $categoryId > 0) {
        $categoryStmt = $pdo->prepare("SELECT id, name FROM alert_categories WHERE id = ?");
        $categoryStmt->execute([$categoryId]);
        $category = $categoryStmt->fetch(PDO::FETCH_ASSOC);
        if (!$category) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid category ID']);
            exit;
        }
    } else {
        // Use default category if not specified
        $categoryId = $alertRepository->findOrGetDefaultCategoryId('General');
    }

    // Add severity prefix to title
    $severityPrefix = $severity === 'EXTREME' ? '[EXTREME]' : '[MODERATE]';
    $finalTitle = $severityPrefix . ' ' . $title;

    // Create alert in database
    $alertId = $alertRepository->create(
        $finalTitle,
        $message,
        $content,
        $categoryId,
        'active'
    );

    if (!$alertId) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Failed to create alert in database'
        ]);
        exit;
    }

    // Get all active users (non-admin users)
    $usersStmt = $pdo->prepare("
        SELECT DISTINCT id 
        FROM users 
        WHERE user_type != 'admin' 
        AND status = 'active'
    ");
    $usersStmt->execute();
    $allUsers = $usersStmt->fetchAll(PDO::FETCH_COLUMN);

    $pushNotificationStats = [
        'total_users' => count($allUsers),
        'notifications_sent' => 0,
        'notifications_failed' => 0
    ];

    // Send push notifications to all users
    if (!empty($allUsers)) {
        // Prepare notification data
        $notificationData = [
            'severity' => strtolower($severity),
            'category_id' => $categoryId,
            'alert_type' => 'emergency_alert'
        ];

        // Use bulk push notification function
        $pushNotificationStats['notifications_sent'] = sendBulkPushNotifications(
            $allUsers,
            $finalTitle,
            $message,
            $notificationData,
            $alertId
        );
        $pushNotificationStats['notifications_failed'] = $pushNotificationStats['total_users'] - $pushNotificationStats['notifications_sent'];
    }

    // Log activity
    if (function_exists('logAdminActivity')) {
        try {
            require_once 'activity_logger.php';
            logAdminActivity(
                $_SESSION['admin_user_id'],
                'create_alert_notify_all',
                "Created {$severity} alert and sent to all users",
                [
                    'alert_id' => $alertId,
                    'severity' => $severity,
                    'category_id' => $categoryId,
                    'push_notifications' => $pushNotificationStats
                ]
            );
        } catch (Exception $e) {
            error_log("Failed to log activity: " . $e->getMessage());
        }
    }

    echo json_encode([
        'success' => true,
        'message' => 'Alert created and notifications sent to all users successfully',
        'alert' => [
            'id' => $alertId,
            'title' => $finalTitle,
            'severity' => $severity,
            'category_id' => $categoryId
        ],
        'notifications' => $pushNotificationStats
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (PDOException $e) {
    error_log("Create Alert API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error occurred: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    error_log("Create Alert API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error occurred: ' . $e->getMessage()
    ]);
}
?>
