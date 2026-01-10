<?php
/**
 * User Auto-Warning Preferences API
 * Allows users to enable/disable AI-powered automatic warnings
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../../ADMIN/api/db_connect.php';

session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized. Please log in.']);
    exit();
}

$userId = $_SESSION['user_id'];
$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    // Ensure user_preferences table has the necessary columns
    ensureAutoWarningColumns();
    
    switch ($action) {
        case 'get':
            getAutoWarningPreferences($userId);
            break;
        case 'update':
            updateAutoWarningPreferences($userId);
            break;
        case 'getCategories':
            getAvailableCategories();
            break;
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

function ensureAutoWarningColumns() {
    global $pdo;
    
    // Check if columns exist, add if they don't
    $columnsToAdd = [
        'auto_warning_enabled' => "TINYINT(1) DEFAULT 1 COMMENT 'Enable AI-powered automatic warnings'",
        'auto_warning_categories' => "TEXT DEFAULT NULL COMMENT 'Comma-separated: heavy_rain,flooding,earthquake,etc'",
        'auto_warning_frequency' => "VARCHAR(20) DEFAULT 'realtime' COMMENT 'realtime, hourly, daily'",
        'auto_warning_severity' => "VARCHAR(20) DEFAULT 'all' COMMENT 'all, high, critical'"
    ];
    
    foreach ($columnsToAdd as $columnName => $definition) {
        try {
            // Check if column exists
            $stmt = $pdo->query("SHOW COLUMNS FROM user_preferences LIKE '$columnName'");
            if ($stmt->rowCount() === 0) {
                // Add column
                $pdo->exec("ALTER TABLE user_preferences ADD COLUMN $columnName $definition");
                error_log("Added column $columnName to user_preferences table");
            }
        } catch (PDOException $e) {
            error_log("Error adding column $columnName: " . $e->getMessage());
        }
    }
}

function getAutoWarningPreferences($userId) {
    global $pdo;
    
    // Get or create user preferences
    $stmt = $pdo->prepare("SELECT auto_warning_enabled, auto_warning_categories, 
                                  auto_warning_frequency, auto_warning_severity,
                                  sms_notifications, email_notifications, push_notifications
                          FROM user_preferences WHERE user_id = ?");
    $stmt->execute([$userId]);
    $prefs = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$prefs) {
        // Create default preferences
        $createStmt = $pdo->prepare("INSERT INTO user_preferences 
                                    (user_id, auto_warning_enabled, auto_warning_categories, 
                                     auto_warning_frequency, auto_warning_severity) 
                                    VALUES (?, 1, NULL, 'realtime', 'all')");
        $createStmt->execute([$userId]);
        
        // Fetch the newly created preferences
        $stmt->execute([$userId]);
        $prefs = $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // Convert categories string to array
    $categories = !empty($prefs['auto_warning_categories']) 
        ? explode(',', $prefs['auto_warning_categories']) 
        : [];
    
    echo json_encode([
        'success' => true,
        'preferences' => [
            'enabled' => (bool)$prefs['auto_warning_enabled'],
            'categories' => $categories,
            'frequency' => $prefs['auto_warning_frequency'] ?? 'realtime',
            'severity' => $prefs['auto_warning_severity'] ?? 'all',
            'notification_channels' => [
                'sms' => (bool)($prefs['sms_notifications'] ?? 1),
                'email' => (bool)($prefs['email_notifications'] ?? 1),
                'push' => (bool)($prefs['push_notifications'] ?? 1)
            ]
        ]
    ]);
}

function updateAutoWarningPreferences($userId) {
    global $pdo;
    
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    
    $enabled = isset($input['enabled']) ? (int)$input['enabled'] : 1;
    $categories = isset($input['categories']) && is_array($input['categories']) 
        ? implode(',', $input['categories']) 
        : null;
    $frequency = $input['frequency'] ?? 'realtime';
    $severity = $input['severity'] ?? 'all';
    
    // Validate inputs
    $validFrequencies = ['realtime', 'hourly', 'daily'];
    $validSeverities = ['all', 'high', 'critical'];
    
    if (!in_array($frequency, $validFrequencies)) {
        throw new Exception('Invalid frequency value');
    }
    
    if (!in_array($severity, $validSeverities)) {
        throw new Exception('Invalid severity value');
    }
    
    // Check if preferences exist
    $checkStmt = $pdo->prepare("SELECT id FROM user_preferences WHERE user_id = ?");
    $checkStmt->execute([$userId]);
    
    if ($checkStmt->fetch()) {
        // Update existing preferences
        $stmt = $pdo->prepare("UPDATE user_preferences 
                              SET auto_warning_enabled = ?, 
                                  auto_warning_categories = ?, 
                                  auto_warning_frequency = ?, 
                                  auto_warning_severity = ?,
                                  updated_at = NOW()
                              WHERE user_id = ?");
        $stmt->execute([$enabled, $categories, $frequency, $severity, $userId]);
    } else {
        // Insert new preferences
        $stmt = $pdo->prepare("INSERT INTO user_preferences 
                              (user_id, auto_warning_enabled, auto_warning_categories, 
                               auto_warning_frequency, auto_warning_severity) 
                              VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$userId, $enabled, $categories, $frequency, $severity]);
    }
    
    // Log activity
    try {
        $activityStmt = $pdo->prepare("INSERT INTO user_activity_logs 
                                       (user_id, activity_type, ip_address, status, metadata) 
                                       VALUES (?, 'update_auto_warning_preferences', ?, 'success', ?)");
        $metadata = json_encode([
            'enabled' => $enabled,
            'frequency' => $frequency,
            'severity' => $severity,
            'categories_count' => !empty($categories) ? count(explode(',', $categories)) : 0
        ]);
        $activityStmt->execute([$userId, $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0', $metadata]);
    } catch (Exception $e) {
        error_log("Error logging activity: " . $e->getMessage());
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Auto-warning preferences updated successfully'
    ]);
}

function getAvailableCategories() {
    $categories = [
        'heavy_rain' => [
            'label' => 'Heavy Rain',
            'icon' => 'fa-cloud-rain',
            'color' => '#2196F3',
            'description' => 'Alerts for heavy rainfall and related risks'
        ],
        'flooding' => [
            'label' => 'Flooding',
            'icon' => 'fa-water',
            'color' => '#00BCD4',
            'description' => 'Flood warnings and water level alerts'
        ],
        'earthquake' => [
            'label' => 'Earthquake',
            'icon' => 'fa-mountain',
            'color' => '#E91E63',
            'description' => 'Earthquake and seismic activity alerts'
        ],
        'strong_winds' => [
            'label' => 'Strong Winds',
            'icon' => 'fa-wind',
            'color' => '#9C27B0',
            'description' => 'High wind speed warnings'
        ],
        'tsunami' => [
            'label' => 'Tsunami',
            'icon' => 'fa-water',
            'color' => '#00BCD4',
            'description' => 'Tsunami warnings and coastal alerts'
        ],
        'landslide' => [
            'label' => 'Landslide',
            'icon' => 'fa-mountain',
            'color' => '#FF9800',
            'description' => 'Landslide risk alerts'
        ],
        'thunderstorm' => [
            'label' => 'Thunderstorm',
            'icon' => 'fa-bolt',
            'color' => '#FFC107',
            'description' => 'Thunderstorm and lightning warnings'
        ],
        'ash_fall' => [
            'label' => 'Volcanic Ash Fall',
            'icon' => 'fa-volcano',
            'color' => '#795548',
            'description' => 'Volcanic ash fall alerts'
        ],
        'fire_incident' => [
            'label' => 'Fire Incident',
            'icon' => 'fa-fire',
            'color' => '#F44336',
            'description' => 'Fire and smoke alerts'
        ],
        'typhoon' => [
            'label' => 'Typhoon/Storm',
            'icon' => 'fa-hurricane',
            'color' => '#F44336',
            'description' => 'Typhoon and tropical storm warnings'
        ]
    ];
    
    echo json_encode([
        'success' => true,
        'categories' => $categories,
        'frequencies' => [
            'realtime' => 'Real-time (Immediate)',
            'hourly' => 'Hourly Summary',
            'daily' => 'Daily Summary'
        ],
        'severities' => [
            'all' => 'All Alerts',
            'high' => 'High Priority Only',
            'critical' => 'Critical Only'
        ]
    ]);
}

/**
 * Get all users with auto-warnings enabled (for admin use in sending notifications)
 */
function getUsersWithAutoWarningsEnabled($categories = null, $severity = 'all') {
    global $pdo;
    
    $query = "SELECT u.id, u.name, u.email, u.phone_number, 
                     up.auto_warning_categories, up.auto_warning_severity, up.auto_warning_frequency,
                     up.sms_notifications, up.email_notifications, up.push_notifications
              FROM users u
              INNER JOIN user_preferences up ON u.id = up.user_id
              WHERE up.auto_warning_enabled = 1 AND u.status = 'active'";
    
    $params = [];
    
    // Filter by severity if specified
    if ($severity !== 'all') {
        $query .= " AND (up.auto_warning_severity = ? OR up.auto_warning_severity = 'all')";
        $params[] = $severity;
    }
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Filter by categories if specified
    if ($categories !== null && is_array($categories)) {
        $users = array_filter($users, function($user) use ($categories) {
            if (empty($user['auto_warning_categories'])) {
                return true; // No category filter means all categories
            }
            $userCategories = explode(',', $user['auto_warning_categories']);
            return !empty(array_intersect($categories, $userCategories));
        });
    }
    
    return array_values($users);
}



