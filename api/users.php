<?php
/**
 * UNIFIED USERS ENDPOINT
 * 
 * GET: Retrieve list of registered users/citizens.
 * GET ?action=locations: Retrieve current location coordinates of users.
 * GET ?id=<id>: Retrieve detailed profile of a specific user.
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
    $action = $_GET['action'] ?? '';
    
    // Action 1: Get Active Locations
    if ($action === 'locations') {
        // Check if user_locations table exists
        $tableExists = false;
        try {
            $stmt = $pdo->query("SHOW TABLES LIKE 'user_locations'");
            $tableExists = $stmt && $stmt->fetch();
        } catch (Throwable $e) {}

        if (!$tableExists) {
            logApiAccess($pdo, $deptName, '/api/users.php?action=locations', 'GET', 404, "user_locations table not found");
            sendJsonResponse(false, 'Active locations feature is unavailable (user_locations table not found).', [], 404);
        }

        // Query active locations
        $query = "
            SELECT 
                ul.id as location_id,
                ul.latitude,
                ul.longitude,
                ul.address as location_address,
                ul.accuracy,
                ul.source as location_source,
                ul.created_at as tracked_at,
                u.id as user_id,
                u.name as user_name,
                u.phone as user_phone,
                u.barangay as user_barangay
            FROM user_locations ul
            INNER JOIN users u ON ul.user_id = u.id
            WHERE u.status = 'active'
        ";
        
        // Check if is_current column exists
        $hasIsCurrent = false;
        try {
            $stmt = $pdo->query("SHOW COLUMNS FROM user_locations LIKE 'is_current'");
            $hasIsCurrent = $stmt && $stmt->rowCount() > 0;
        } catch (Throwable $e) {}

        if ($hasIsCurrent) {
            $query .= " AND ul.is_current = 1";
        } else {
            // Group and subquery to fetch latest locations
            $query = "
                SELECT 
                    ul.id as location_id,
                    ul.latitude,
                    ul.longitude,
                    ul.address as location_address,
                    ul.accuracy,
                    ul.source as location_source,
                    ul.created_at as tracked_at,
                    u.id as user_id,
                    u.name as user_name,
                    u.phone as user_phone,
                    u.barangay as user_barangay
                FROM (
                    SELECT user_id, MAX(id) as max_id 
                    FROM user_locations 
                    GROUP BY user_id
                ) ulm
                INNER JOIN user_locations ul ON ul.id = ulm.max_id
                INNER JOIN users u ON ul.user_id = u.id
                WHERE u.status = 'active'
            ";
        }
        
        $stmt = $pdo->prepare($query);
        $stmt->execute();
        $locations = $stmt->fetchAll(PDO::FETCH_ASSOC);

        logApiAccess($pdo, $deptName, '/api/users.php?action=locations', 'GET', 200, "Retrieved " . count($locations) . " active user locations");
        sendJsonResponse(true, 'Active user locations fetched successfully.', ['locations' => $locations]);
    }

    // Action 2: Get Specific User Details
    elseif (isset($_GET['id']) && $_GET['id'] !== '') {
        $userId = (int)$_GET['id'];
        
        // Fetch all possible columns from users table dynamically
        $availableCols = [];
        try {
            $colStmt = $pdo->query("SHOW COLUMNS FROM users");
            while ($c = $colStmt->fetch(PDO::FETCH_ASSOC)) {
                $availableCols[] = $c['Field'];
            }
        } catch (Throwable $e) {}

        if (empty($availableCols)) {
            $availableCols = ['id', 'name', 'email', 'phone', 'status', 'barangay', 'user_type', 'created_at'];
        }

        $colList = implode(', ', array_map(function($c) { return "`$c`"; }, $availableCols));
        $stmt = $pdo->prepare("SELECT {$colList} FROM users WHERE id = ? LIMIT 1");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            logApiAccess($pdo, $deptName, "/api/users.php?id=$userId", 'GET', 404, "User ID $userId not found");
            sendJsonResponse(false, 'User not found.', [], 404);
        }

        // Fetch User Preferences (Citizen Subscriptions Preferences)
        $user['preferences'] = [
            'sms_notifications' => true,
            'email_notifications' => true,
            'push_notifications' => true,
            'alert_priority' => 'all',
            'preferred_language' => 'en',
            'theme' => 'light'
        ];
        try {
            $prefStmt = $pdo->prepare("SELECT sms_notifications, email_notifications, push_notifications, alert_categories, preferred_language, alert_priority, theme, timezone FROM user_preferences WHERE user_id = ? LIMIT 1");
            $prefStmt->execute([$userId]);
            $prefs = $prefStmt->fetch(PDO::FETCH_ASSOC);
            if ($prefs) {
                $user['preferences'] = $prefs;
            }
        } catch (Throwable $e) {}

        // Fetch User Subscription Category Topics (Citizen Subscriptions Topics)
        $user['subscriptions'] = [];
        try {
            // Check if user_subscriptions table has category name mapping
            $subStmt = $pdo->prepare("
                SELECT us.category_id, us.subscription_type, us.channels, us.is_active, ac.name as category_name
                FROM user_subscriptions us
                LEFT JOIN alert_categories ac ON us.category_id = ac.id
                WHERE us.user_id = ? AND us.is_active = 1
            ");
            $subStmt->execute([$userId]);
            $user['subscriptions'] = $subStmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            // Fallback to legacy subscriptions table if active
            try {
                $subStmt = $pdo->prepare("SELECT id, category_id, status FROM subscriptions WHERE user_id = ? AND status = 'active'");
                $subStmt->execute([$userId]);
                $user['subscriptions'] = $subStmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (Throwable $e2) {}
        }

        logApiAccess($pdo, $deptName, "/api/users.php?id=$userId", 'GET', 200, "Retrieved details, preferences, and subscriptions for user: " . $user['name']);
        sendJsonResponse(true, 'User details, preferences, and subscriptions fetched successfully.', ['user' => $user]);
    }

    // Action 3: List Paginated Users (Generic)
    else {
        $status = isset($_GET['status']) && $_GET['status'] !== '' ? trim($_GET['status']) : null;
        $barangay = isset($_GET['barangay']) && $_GET['barangay'] !== '' ? trim($_GET['barangay']) : null;
        $userType = isset($_GET['user_type']) && $_GET['user_type'] !== '' ? trim($_GET['user_type']) : null;
        
        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $limit = isset($_GET['limit']) ? min(100, max(1, (int)$_GET['limit'])) : 50;
        $offset = ($page - 1) * $limit;

        $query = "SELECT id, name, email, phone, status, barangay, user_type, created_at FROM users WHERE 1=1";
        $countQuery = "SELECT COUNT(*) FROM users WHERE 1=1";
        $params = [];

        if ($status) {
            $query .= " AND status = ?";
            $countQuery .= " AND status = ?";
            $params[] = $status;
        }
        if ($barangay) {
            $query .= " AND barangay = ?";
            $countQuery .= " AND barangay = ?";
            $params[] = $barangay;
        }
        if ($userType) {
            $query .= " AND user_type = ?";
            $countQuery .= " AND user_type = ?";
            $params[] = $userType;
        }

        // Fetch Total Counts
        $countStmt = $pdo->prepare($countQuery);
        $countStmt->execute($params);
        $totalRecords = (int)$countStmt->fetchColumn();

        // Fetch Data
        $query .= " ORDER BY id DESC LIMIT ? OFFSET ?";
        $stmt = $pdo->prepare($query);
        
        // Bind parameters sequentially
        $bindIdx = 1;
        foreach ($params as $paramVal) {
            $stmt->bindValue($bindIdx++, $paramVal, PDO::PARAM_STR);
        }
        $stmt->bindValue($bindIdx++, $limit, PDO::PARAM_INT);
        $stmt->bindValue($bindIdx++, $offset, PDO::PARAM_INT);
        
        $stmt->execute();
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $totalPages = ceil($totalRecords / $limit);

        logApiAccess($pdo, $deptName, '/api/users.php', 'GET', 200, "Listed " . count($users) . " users (page=$page)");
        sendJsonResponse(true, 'Users list fetched successfully.', [
            'users' => $users,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total_records' => $totalRecords,
                'total_pages' => $totalPages
            ]
        ]);
    }
} catch (PDOException $e) {
    logApiAccess($pdo, $deptName, '/api/users.php', 'GET', 500, "Database query exception: " . $e->getMessage());
    sendJsonResponse(false, 'Database query failed: ' . $e->getMessage(), [], 500);
}
