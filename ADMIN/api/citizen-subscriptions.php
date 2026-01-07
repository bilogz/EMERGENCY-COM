<?php
/**
 * Citizen Subscription and Alert Preferences API
 * Manage citizen subscriptions and preferences
 */

header('Content-Type: application/json; charset=utf-8');
require_once 'db_connect.php';

$action = $_GET['action'] ?? 'list';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subscriberId = $_POST['subscriber_id'] ?? 0;
    $categories = $_POST['categories'] ?? [];
    $channels = $_POST['channels'] ?? [];
    $preferredLanguage = $_POST['preferred_language'] ?? 'en';
    $status = $_POST['status'] ?? 'active';
    
    if (empty($subscriberId)) {
        echo json_encode(['success' => false, 'message' => 'Subscriber ID is required.']);
        exit;
    }
    
    try {
        $categoriesStr = is_array($categories) ? implode(',', $categories) : '';
        $channelsStr = is_array($channels) ? implode(',', $channels) : '';
        
        $stmt = $pdo->prepare("
            UPDATE subscriptions
            SET categories = ?, channels = ?, preferred_language = ?, status = ?, updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$categoriesStr, $channelsStr, $preferredLanguage, $status, $subscriberId]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Subscription updated successfully.'
        ]);
    } catch (PDOException $e) {
        error_log("Update Subscription Error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error occurred.']);
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $data = json_decode(file_get_contents('php://input'), true);
    $id = $data['id'] ?? 0;
    
    if (empty($id)) {
        echo json_encode(['success' => false, 'message' => 'Subscription ID is required.']);
        exit;
    }
    
    try {
        $stmt = $pdo->prepare("DELETE FROM subscriptions WHERE id = ?");
        $stmt->execute([$id]);
        
        echo json_encode(['success' => true, 'message' => 'Subscription deleted successfully.']);
    } catch (PDOException $e) {
        error_log("Delete Subscription Error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error occurred.']);
    }
} elseif ($action === 'list') {
    try {
        // Pagination parameters
        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $limit = isset($_GET['limit']) ? min(100, max(10, (int)$_GET['limit'])) : 50; // Default 50, max 100
        $offset = ($page - 1) * $limit;
        
        // Get total count for pagination
        $totalCount = $pdo->query("SELECT COUNT(*) FROM subscriptions WHERE status = 'active'")->fetchColumn();
        
        // Enhanced query with comprehensive user data
        $stmt = $pdo->prepare("
            SELECT 
                s.*,
                u.id as user_id,
                u.name,
                u.email,
                u.phone,
                u.status as user_status,
                u.created_at as user_created_at,
                u.district,
                u.barangay,
                u.house_number,
                u.street,
                u.address,
                u.nationality,
                u.google_id,
                u.email_verified,
                -- Device info
                (SELECT COUNT(*) FROM user_devices WHERE user_id = u.id AND is_active = 1) as device_count,
                (SELECT device_type FROM user_devices WHERE user_id = u.id AND is_active = 1 ORDER BY last_active DESC LIMIT 1) as latest_device_type,
                (SELECT device_name FROM user_devices WHERE user_id = u.id AND is_active = 1 ORDER BY last_active DESC LIMIT 1) as latest_device_name,
                (SELECT last_active FROM user_devices WHERE user_id = u.id AND is_active = 1 ORDER BY last_active DESC LIMIT 1) as last_device_active,
                -- Location info
                (SELECT address FROM user_locations WHERE user_id = u.id AND is_current = 1 ORDER BY created_at DESC LIMIT 1) as current_location,
                (SELECT latitude FROM user_locations WHERE user_id = u.id AND is_current = 1 ORDER BY created_at DESC LIMIT 1) as current_latitude,
                (SELECT longitude FROM user_locations WHERE user_id = u.id AND is_current = 1 ORDER BY created_at DESC LIMIT 1) as current_longitude,
                -- Activity stats
                (SELECT COUNT(*) FROM user_activity_logs WHERE user_id = u.id) as activity_count,
                (SELECT MAX(created_at) FROM user_activity_logs WHERE user_id = u.id) as last_activity,
                -- Preferences
                (SELECT preferred_language FROM user_preferences WHERE user_id = u.id LIMIT 1) as user_preferred_language,
                (SELECT sms_notifications FROM user_preferences WHERE user_id = u.id LIMIT 1) as sms_enabled,
                (SELECT email_notifications FROM user_preferences WHERE user_id = u.id LIMIT 1) as email_enabled,
                (SELECT push_notifications FROM user_preferences WHERE user_id = u.id LIMIT 1) as push_enabled
            FROM subscriptions s
            LEFT JOIN users u ON u.id = s.user_id
            WHERE s.status = 'active'
            ORDER BY s.created_at DESC
            LIMIT ? OFFSET ?
        ");
        $stmt->execute([$limit, $offset]);
        $subscribers = $stmt->fetchAll();
        
        // Format subscribers with comprehensive data
        $formatted = [];
        foreach ($subscribers as $sub) {
            $formatted[] = [
                'id' => $sub['id'],
                'user_id' => $sub['user_id'],
                'name' => $sub['name'],
                'email' => $sub['email'],
                'phone' => $sub['phone'],
                'user_status' => $sub['user_status'],
                'user_created_at' => $sub['user_created_at'],
                'address' => [
                    'district' => $sub['district'],
                    'barangay' => $sub['barangay'],
                    'house_number' => $sub['house_number'],
                    'street' => $sub['street'],
                    'full_address' => $sub['address'],
                    'nationality' => $sub['nationality']
                ],
                'subscription' => [
                    'categories' => $sub['categories'] ? explode(',', $sub['categories']) : [],
                    'channels' => $sub['channels'] ? explode(',', $sub['channels']) : [],
                    'language' => $sub['preferred_language'],
                    'status' => $sub['status'],
                    'created_at' => $sub['created_at']
                ],
                'device' => [
                    'count' => (int)$sub['device_count'],
                    'latest_type' => $sub['latest_device_type'],
                    'latest_name' => $sub['latest_device_name'],
                    'last_active' => $sub['last_device_active']
                ],
                'location' => [
                    'address' => $sub['current_location'],
                    'latitude' => $sub['current_latitude'],
                    'longitude' => $sub['current_longitude']
                ],
                'activity' => [
                    'total_count' => (int)$sub['activity_count'],
                    'last_activity' => $sub['last_activity']
                ],
                'preferences' => [
                    'language' => $sub['user_preferred_language'] ?: $sub['preferred_language'],
                    'sms_enabled' => (bool)$sub['sms_enabled'],
                    'email_enabled' => (bool)$sub['email_enabled'],
                    'push_enabled' => (bool)$sub['push_enabled']
                ],
                'auth' => [
                    'google_id' => $sub['google_id'],
                    'email_verified' => (bool)$sub['email_verified']
                ]
            ];
        }
        
        echo json_encode([
            'success' => true,
            'subscribers' => $formatted,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => (int)$totalCount,
                'total_pages' => (int)ceil($totalCount / $limit)
            ]
        ]);
    } catch (PDOException $e) {
        error_log("List Subscribers Error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error occurred.']);
    }
} elseif ($action === 'get') {
    $id = $_GET['id'] ?? 0;
    
    try {
        // Get comprehensive subscriber data
        $stmt = $pdo->prepare("
            SELECT 
                s.*,
                u.id as user_id,
                u.name,
                u.email,
                u.phone,
                u.status as user_status,
                u.created_at as user_created_at,
                u.district,
                u.barangay,
                u.house_number,
                u.street,
                u.address,
                u.nationality,
                u.google_id,
                u.email_verified
            FROM subscriptions s
            LEFT JOIN users u ON u.id = s.user_id
            WHERE s.id = ?
        ");
        $stmt->execute([$id]);
        $subscriber = $stmt->fetch();
        
        if ($subscriber) {
            $userId = $subscriber['user_id'];
            
            // Get device information
            $deviceStmt = $pdo->prepare("
                SELECT device_type, device_name, last_active, is_active, created_at
                FROM user_devices
                WHERE user_id = ?
                ORDER BY last_active DESC
            ");
            $deviceStmt->execute([$userId]);
            $devices = $deviceStmt->fetchAll();
            
            // Get location information
            $locationStmt = $pdo->prepare("
                SELECT latitude, longitude, address, is_current, created_at
                FROM user_locations
                WHERE user_id = ?
                ORDER BY is_current DESC, created_at DESC
                LIMIT 5
            ");
            $locationStmt->execute([$userId]);
            $locations = $locationStmt->fetchAll();
            
            // Get recent activity logs
            $activityStmt = $pdo->prepare("
                SELECT activity_type, description, ip_address, status, created_at
                FROM user_activity_logs
                WHERE user_id = ?
                ORDER BY created_at DESC
                LIMIT 10
            ");
            $activityStmt->execute([$userId]);
            $activities = $activityStmt->fetchAll();
            
            // Get user preferences
            $prefStmt = $pdo->prepare("
                SELECT preferred_language, sms_notifications, email_notifications, push_notifications, 
                       alert_categories, alert_priority, theme, share_location
                FROM user_preferences
                WHERE user_id = ?
            ");
            $prefStmt->execute([$userId]);
            $preferences = $prefStmt->fetch();
            
            // Format response
            $formatted = [
                'id' => $subscriber['id'],
                'user_id' => $userId,
                'name' => $subscriber['name'],
                'email' => $subscriber['email'],
                'phone' => $subscriber['phone'],
                'user_status' => $subscriber['user_status'],
                'user_created_at' => $subscriber['user_created_at'],
                'address' => [
                    'district' => $subscriber['district'],
                    'barangay' => $subscriber['barangay'],
                    'house_number' => $subscriber['house_number'],
                    'street' => $subscriber['street'],
                    'full_address' => $subscriber['address'],
                    'nationality' => $subscriber['nationality']
                ],
                'subscription' => [
                    'categories' => $subscriber['categories'] ? explode(',', $subscriber['categories']) : [],
                    'channels' => $subscriber['channels'] ? explode(',', $subscriber['channels']) : [],
                    'language' => $subscriber['preferred_language'],
                    'status' => $subscriber['status'],
                    'created_at' => $subscriber['created_at'],
                    'updated_at' => $subscriber['updated_at']
                ],
                'devices' => $devices,
                'locations' => $locations,
                'activities' => $activities,
                'preferences' => $preferences ?: null,
                'auth' => [
                    'google_id' => $subscriber['google_id'],
                    'email_verified' => (bool)$subscriber['email_verified']
                ]
            ];
            
            echo json_encode([
                'success' => true,
                'subscriber' => $formatted
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Subscriber not found.'
            ]);
        }
    } catch (PDOException $e) {
        error_log("Get Subscriber Error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error occurred.']);
    }
} elseif ($action === 'statistics') {
    try {
        $total = $pdo->query("SELECT COUNT(*) FROM subscriptions")->fetchColumn();
        $active = $pdo->query("SELECT COUNT(*) FROM subscriptions WHERE status = 'active'")->fetchColumn();
        $weather = $pdo->query("SELECT COUNT(*) FROM subscriptions WHERE categories LIKE '%weather%' AND status = 'active'")->fetchColumn();
        $earthquake = $pdo->query("SELECT COUNT(*) FROM subscriptions WHERE categories LIKE '%earthquake%' AND status = 'active'")->fetchColumn();
        
        echo json_encode([
            'success' => true,
            'total' => $total,
            'active' => $active,
            'weather' => $weather,
            'earthquake' => $earthquake
        ]);
    } catch (PDOException $e) {
        error_log("Statistics Error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error occurred.']);
    }
} elseif ($action === 'export') {
    // Export functionality would go here
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="subscribers.csv"');
    // CSV export implementation
    echo "ID,Name,Email,Phone,Categories,Channels,Language,Status\n";
    // ... more export code
    exit;
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid action.']);
}
?>

