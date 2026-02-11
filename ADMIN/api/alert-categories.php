<?php
/**
 * Alert Categories API
 * Manage alert categories (Weather, Earthquake, Bomb Threat, etc.)
 */

header('Content-Type: application/json; charset=utf-8');
require_once 'db_connect.php';
require_once 'log_activity_helper.php';

function resolveAlertCategoriesTable(PDO $pdo): string {
    $candidates = ['alert_categories', 'alert_categories_catalog'];
    foreach ($candidates as $candidate) {
        try {
            $stmt = $pdo->query("SHOW TABLES LIKE " . $pdo->quote($candidate));
            if ($stmt && $stmt->fetch()) {
                $pdo->query("SELECT 1 FROM {$candidate} LIMIT 1");
                return $candidate;
            }
        } catch (PDOException $e) {
            // Try next candidate if current is broken.
        }
    }
    return 'alert_categories_catalog';
}

$tableName = 'alert_categories';

function ensureAlertCategoriesSchema(PDO $pdo, string $tableName): void {
    try {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS {$tableName} (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(120) NOT NULL,
                icon VARCHAR(120) NOT NULL DEFAULT 'fa-exclamation-triangle',
                description TEXT DEFAULT NULL,
                color VARCHAR(20) NOT NULL DEFAULT '#3a7675',
                status VARCHAR(20) NOT NULL DEFAULT 'active',
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    } catch (PDOException $e) {
        $message = strtolower($e->getMessage());
        if (strpos($message, 'tablespace') !== false || strpos($message, 'error 1813') !== false) {
            throw $e;
        }
        throw $e;
    }

    try {
        $cols = $pdo->query("SHOW COLUMNS FROM {$tableName}")->fetchAll(PDO::FETCH_COLUMN);
    } catch (PDOException $e) {
        $message = strtolower($e->getMessage());
        if (strpos($message, "doesn't exist in engine") !== false || strpos($message, "error 1932") !== false) {
            $pdo->exec("DROP TABLE IF EXISTS {$tableName}");
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS {$tableName} (
                    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    name VARCHAR(120) NOT NULL,
                    icon VARCHAR(120) NOT NULL DEFAULT 'fa-exclamation-triangle',
                    description TEXT DEFAULT NULL,
                    color VARCHAR(20) NOT NULL DEFAULT '#3a7675',
                    status VARCHAR(20) NOT NULL DEFAULT 'active',
                    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
            $cols = $pdo->query("SHOW COLUMNS FROM {$tableName}")->fetchAll(PDO::FETCH_COLUMN);
        } else {
            throw $e;
        }
    }
    if (!in_array('status', $cols, true)) {
        $pdo->exec("ALTER TABLE {$tableName} ADD COLUMN status VARCHAR(20) NOT NULL DEFAULT 'active' AFTER color");
    }
    if (!in_array('updated_at', $cols, true)) {
        $pdo->exec("ALTER TABLE {$tableName} ADD COLUMN updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP AFTER created_at");
    }

    $count = (int)$pdo->query("SELECT COUNT(*) FROM {$tableName}")->fetchColumn();
    if ($count === 0) {
        $seed = [
            ['Weather', 'fa-cloud-sun-rain', 'Weather advisories and rainfall alerts', '#3498db', 'active'],
            ['Earthquake', 'fa-mountain', 'Earthquake and aftershock notifications', '#e74c3c', 'active'],
            ['Fire', 'fa-fire', 'Fire incidents and evacuation notices', '#e67e22', 'active'],
            ['Flood', 'fa-water', 'Flood warnings and water level updates', '#1abc9c', 'active'],
            ['Bomb Threat', 'fa-bomb', 'Bomb threat and security alerts', '#9b59b6', 'active'],
            ['Health', 'fa-heartbeat', 'Health advisories and public health notices', '#2ecc71', 'active'],
            ['General', 'fa-bell', 'General advisories and announcements', '#3a7675', 'active'],
        ];
        $stmt = $pdo->prepare("
            INSERT INTO {$tableName} (name, icon, description, color, status, created_at)
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        foreach ($seed as $row) {
            $stmt->execute($row);
        }
    }
}

if (!isset($pdo) || !$pdo) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed.']);
    exit;
}

try {
    $tableName = resolveAlertCategoriesTable($pdo);
    try {
        ensureAlertCategoriesSchema($pdo, $tableName);
    } catch (PDOException $e) {
        $message = strtolower($e->getMessage());
        if (strpos($message, 'tablespace') !== false || strpos($message, 'error 1813') !== false) {
            $tableName = 'alert_categories_catalog';
            ensureAlertCategoriesSchema($pdo, $tableName);
        } else {
            throw $e;
        }
    }
} catch (Throwable $e) {
    error_log("Alert Categories Schema Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Category setup failed.']);
    exit;
}

$action = $_GET['action'] ?? 'list';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? '';
    $name = $_POST['name'] ?? '';
    $icon = $_POST['icon'] ?? 'fa-exclamation-triangle';
    $description = $_POST['description'] ?? '';
    $color = $_POST['color'] ?? '#3a7675';
    $status = $_POST['status'] ?? 'active';
    
    if (empty($name)) {
        echo json_encode(['success' => false, 'message' => 'Category name is required.']);
        exit;
    }
    
    try {
        if (!empty($id)) {
            // Check if status field exists in alert_categories
            $hasStatus = false;
            try {
                $checkStmt = $pdo->query("SHOW COLUMNS FROM alert_categories LIKE 'status'");
                $hasStatus = $checkStmt->rowCount() > 0;
            } catch (PDOException $e) {}

            if (!$hasStatus) {
                // Try to add the column if it doesn't exist for backward compatibility
                try {
                    $pdo->exec("ALTER TABLE alert_categories ADD COLUMN status VARCHAR(20) DEFAULT 'active' AFTER color");
                    $hasStatus = true;
                } catch (PDOException $e) {}
            }

            $sql = "UPDATE alert_categories SET name = ?, icon = ?, description = ?, color = ? " . ($hasStatus ? ", status = ?" : "") . " WHERE id = ?";
            $params = [$name, $icon, $description, $color];
            if ($hasStatus) $params[] = $status;
            $params[] = $id;

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            
            logActivity('update_alert_category', "Updated category: $name (ID: $id)");
            
            echo json_encode(['success' => true, 'message' => 'Category updated successfully.']);
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO {$tableName} (name, icon, description, color, status, created_at)
                VALUES (?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([$name, $icon, $description, $color, $status]);
            $newId = $pdo->lastInsertId();
            
            logActivity('create_alert_category', "Created category: $name (ID: $newId)");

            echo json_encode([
                'success' => true,
                'message' => 'Category added successfully.',
                'category_id' => $newId
            ]);
        }
    } catch (PDOException $e) {
        error_log("Alert Category Error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error occurred.']);
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $data = json_decode(file_get_contents('php://input'), true);
    $id = $data['id'] ?? 0;
    
    if (empty($id)) {
        echo json_encode(['success' => false, 'message' => 'Category ID is required.']);
        exit;
    }
    
    try {
        // Get name before delete for logging
        $nameStmt = $pdo->prepare("SELECT name FROM {$tableName} WHERE id = ?");
        $nameStmt->execute([$id]);
        $catName = $nameStmt->fetchColumn();

        $stmt = $pdo->prepare("DELETE FROM {$tableName} WHERE id = ?");
        $stmt->execute([$id]);
        
        logActivity('delete_alert_category', "Deleted category: $catName (ID: $id)");
        
        echo json_encode(['success' => true, 'message' => 'Category deleted successfully.']);
    } catch (PDOException $e) {
        error_log("Delete Category Error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error occurred.']);
    }
} elseif ($action === 'list') {
    try {
        // Try full join for alerts_count; fallback to simple list if alerts table missing.
        try {
            $sql = "SELECT c.*, COUNT(a.id) as alerts_count
                    FROM {$tableName} c
                    LEFT JOIN alerts a ON a.category_id = c.id
                    GROUP BY c.id
                    ORDER BY c.name";
            $stmt = $pdo->query($sql);
            $categories = $stmt->fetchAll();
        } catch (PDOException $e) {
            $stmt = $pdo->query("SELECT c.*, 0 as alerts_count FROM {$tableName} c ORDER BY c.name");
            $categories = $stmt->fetchAll();
        }
        
        echo json_encode([
            'success' => true,
            'categories' => $categories
        ]);
    } catch (PDOException $e) {
        error_log("List Categories Error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error occurred.']);
    }
} elseif ($action === 'analytics') {
    $id = $_GET['id'] ?? 0;
    if (empty($id)) {
        echo json_encode(['success' => false, 'message' => 'Category ID required']);
        exit;
    }

    try {
        // Get category name
        $nameStmt = $pdo->prepare("SELECT name FROM alert_categories WHERE id = ?");
        $nameStmt->execute([$id]);
        $catName = $nameStmt->fetchColumn();

        // 1. Total Alerts
        $alertsStmt = $pdo->prepare("SELECT COUNT(*) FROM alerts WHERE category_id = ?");
        $alertsStmt->execute([$id]);
        $totalAlerts = $alertsStmt->fetchColumn();

        // 2. Last used timestamp
        $lastUsedStmt = $pdo->prepare("SELECT MAX(created_at) FROM alerts WHERE category_id = ?");
        $lastUsedStmt->execute([$id]);
        $lastUsed = $lastUsedStmt->fetchColumn();

        // 3. Active Subscribers (from subscriptions table categories comma-separated field)
        // Note: Use catName as categories field stores category names or slugs
        $subsStmt = $pdo->prepare("SELECT COUNT(*) FROM subscriptions WHERE FIND_IN_SET(?, categories) > 0 AND status = 'active'");
        $subsStmt->execute([$catName]);
        $activeSubscribers = $subsStmt->fetchColumn();

        // 4. Trend Data (Daily alerts for last 7 days)
        $trendLabels = [];
        $trendValues = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-$i days"));
            $trendLabels[] = date('M d', strtotime("-$i days"));
            
            $dayStmt = $pdo->prepare("SELECT COUNT(*) FROM alerts WHERE category_id = ? AND DATE(created_at) = ?");
            $dayStmt->execute([$id, $date]);
            $trendValues[] = (int)$dayStmt->fetchColumn();
        }

        // 5. Audit Logs (from admin_activity_logs)
        $auditStmt = $pdo->prepare("
            SELECT a.action, a.description, a.created_at, u.name as admin_name
            FROM admin_activity_logs a
            LEFT JOIN users u ON a.admin_id = u.id
            WHERE a.description LIKE ?
            ORDER BY a.created_at DESC
            LIMIT 10
        ");
        $auditStmt->execute(["%ID: $id%"]);
        $auditLogs = $auditStmt->fetchAll();

        echo json_encode([
            'success' => true,
            'analytics' => [
                'total_alerts' => $totalAlerts,
                'last_used' => $lastUsed ?: 'Never',
                'active_subscribers' => $activeSubscribers,
                'trend' => [
                    'labels' => $trendLabels,
                    'values' => $trendValues
                ],
                'audit_logs' => $auditLogs
            ]
        ]);
    } catch (PDOException $e) {
        error_log("Analytics Error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error occurred.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid action.']);
}
?>

