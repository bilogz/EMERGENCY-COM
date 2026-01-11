<?php
/**
 * Create Test/Dummy Alerts API
 * Generates sample alerts across different categories for testing purposes
 */

header('Content-Type: application/json; charset=utf-8');
require_once 'db_connect.php';
require_once __DIR__ . '/../repositories/AlertRepository.php';

session_start();

// For development/testing: Allow GET requests to easily trigger test alert creation
// For production: Require POST with admin authentication
$requireAuth = false; // Set to true in production

if ($requireAuth && !isset($_SESSION['admin_user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized. Admin login required.']);
    exit;
}

try {
    if ($pdo === null) {
        echo json_encode([
            'success' => false,
            'message' => 'Database connection failed',
            'alerts' => []
        ]);
        exit;
    }

    $alertRepository = new AlertRepository($pdo);

    // Get category IDs
    $categories = [];
    $categoryStmt = $pdo->query("SELECT id, name FROM alert_categories");
    while ($row = $categoryStmt->fetch(PDO::FETCH_ASSOC)) {
        $categories[$row['name']] = $row['id'];
    }

    // Define test alerts across different categories
    $testAlerts = [
        [
            'title' => 'Weather Alert: Heavy Rain Expected',
            'message' => 'Heavy rainfall is expected in Quezon City starting this afternoon. Please stay indoors and avoid low-lying areas.',
            'content' => 'PAGASA has issued a weather advisory for Quezon City. Heavy rainfall with possible flooding is expected between 2:00 PM and 8:00 PM today. Residents are advised to:\n\n• Stay indoors if possible\n• Avoid low-lying areas\n• Keep emergency supplies ready\n• Monitor weather updates\n\nStay safe and follow official updates.',
            'category' => 'Weather',
            'categoryId' => $categories['Weather'] ?? null
        ],
        [
            'title' => 'Earthquake Alert: Minor Tremor Detected',
            'message' => 'A minor earthquake (Magnitude 4.2) was detected near Quezon City at 10:30 AM. No immediate threat reported.',
            'content' => 'PHIVOLCS has detected a minor earthquake near Quezon City:\n\n• Magnitude: 4.2\n• Depth: 10 km\n• Location: 15 km east of Quezon City\n• Time: 10:30 AM\n\nNo tsunami warning issued. Some residents may have felt light shaking. If you felt the earthquake, report it to PHIVOLCS.\n\nFor safety tips, visit the official PHIVOLCS website.',
            'category' => 'Earthquake',
            'categoryId' => $categories['Earthquake'] ?? null
        ],
        [
            'title' => 'Fire Alert: Building Fire in Eastwood',
            'message' => 'Fire incident reported in a commercial building in Eastwood, Quezon City. Firefighters are on scene.',
            'content' => 'Emergency Response Alert:\n\n• Location: Commercial Building, Eastwood Avenue, Quezon City\n• Time Reported: 11:15 AM\n• Status: Firefighters on scene\n• Evacuation: Nearby buildings evacuated as precaution\n\nTraffic advisory: Eastwood Avenue temporarily closed. Please avoid the area and use alternate routes.\n\nIf you are in the area, follow instructions from emergency personnel.',
            'category' => 'Fire',
            'categoryId' => $categories['Fire'] ?? null
        ],
        [
            'title' => 'General Alert: Road Closure - EDSA Maintenance',
            'message' => 'EDSA will have partial lane closures for maintenance work tonight from 11:00 PM to 4:00 AM.',
            'content' => 'Traffic Advisory:\n\n• Route: EDSA, Quezon City segment\n• Duration: Tonight, 11:00 PM - 4:00 AM\n• Closure: Two lanes (outer lanes)\n• Reason: Scheduled road maintenance\n\nAlternate routes:\n• Use Commonwealth Avenue\n• Use Quezon Avenue\n• Use Aurora Boulevard\n\nPlan your travel accordingly. Thank you for your patience.',
            'category' => 'General',
            'categoryId' => $categories['General'] ?? null
        ],
        [
            'title' => 'Weather Alert: High Heat Index Warning',
            'message' => 'High heat index expected today. Stay hydrated and avoid prolonged sun exposure.',
            'content' => 'PAGASA Heat Index Advisory:\n\n• Expected Heat Index: 42°C\n• Time: 12:00 PM - 4:00 PM\n• Areas: Quezon City and surrounding areas\n\nHealth Tips:\n• Drink plenty of water\n• Wear light, loose-fitting clothing\n• Avoid outdoor activities during peak hours\n• Seek shade or air-conditioned areas\n• Watch for signs of heat exhaustion\n\nStay safe and keep cool!',
            'category' => 'Weather',
            'categoryId' => $categories['Weather'] ?? null
        ],
        [
            'title' => 'General Alert: Power Interruption Scheduled',
            'message' => 'Scheduled power interruption in parts of Quezon City tomorrow from 9:00 AM to 12:00 PM.',
            'content' => 'MERALCO Scheduled Maintenance:\n\n• Date: Tomorrow\n• Time: 9:00 AM - 12:00 PM (3 hours)\n• Affected Areas:\n  - Project 4\n  - Project 6\n  - Parts of Cubao\n\nPreparation Tips:\n• Charge all devices tonight\n• Prepare backup power if available\n• Store water (if using electric pump)\n• Keep flashlights ready\n\nService will resume as soon as maintenance is complete. Thank you for your understanding.',
            'category' => 'General',
            'categoryId' => $categories['General'] ?? null
        ],
        [
            'title' => 'Emergency Alert: Traffic Accident on Commonwealth',
            'message' => 'Traffic accident on Commonwealth Avenue causing major delays. Emergency services are on scene.',
            'content' => 'Traffic Incident Alert:\n\n• Location: Commonwealth Avenue, near SM Fairview\n• Time: Current\n• Status: Emergency services on scene\n• Impact: Heavy traffic, use alternate routes\n\nAlternate Routes:\n• Via Quirino Highway\n• Via Mindanao Avenue\n• Via Regalado Avenue\n\nPlease avoid the area if possible. Drive safely and follow traffic personnel instructions.',
            'category' => 'General',
            'categoryId' => $categories['General'] ?? null
        ],
        [
            'title' => 'Weather Alert: Strong Winds Expected',
            'message' => 'Strong winds expected this evening. Secure outdoor items and avoid outdoor activities.',
            'content' => 'PAGASA Wind Advisory:\n\n• Wind Speed: 40-60 km/h\n• Time: 6:00 PM - 10:00 PM tonight\n• Areas: Quezon City\n\nPrecautions:\n• Secure outdoor furniture and decorations\n• Bring in items from balconies\n• Avoid outdoor activities\n• Drive carefully, especially on bridges\n• Watch for falling objects\n\nStay indoors if possible. Monitor weather updates.',
            'category' => 'Weather',
            'categoryId' => $categories['Weather'] ?? null
        ]
    ];

    $createdAlerts = [];
    $errors = [];

    foreach ($testAlerts as $testAlert) {
        try {
            $alertId = $alertRepository->create(
                $testAlert['title'],
                $testAlert['message'],
                $testAlert['content'],
                $testAlert['categoryId'],
                'active'
            );

            if ($alertId) {
                $createdAlerts[] = [
                    'id' => $alertId,
                    'title' => $testAlert['title'],
                    'category' => $testAlert['category']
                ];
            } else {
                $errors[] = "Failed to create alert: " . $testAlert['title'];
            }
        } catch (Exception $e) {
            error_log("Error creating test alert: " . $e->getMessage());
            $errors[] = "Error creating alert '" . $testAlert['title'] . "': " . $e->getMessage();
        }
    }

    // Log activity if admin is logged in
    if (isset($_SESSION['admin_user_id']) && function_exists('logAdminActivity')) {
        try {
            require_once 'activity_logger.php';
            logAdminActivity(
                $_SESSION['admin_user_id'],
                'create_test_alerts',
                'Created ' . count($createdAlerts) . ' test alerts',
                ['alert_ids' => array_column($createdAlerts, 'id')]
            );
        } catch (Exception $e) {
            // Non-critical, continue
            error_log("Failed to log activity: " . $e->getMessage());
        }
    }

    echo json_encode([
        'success' => true,
        'message' => 'Test alerts created successfully',
        'created_count' => count($createdAlerts),
        'alerts' => $createdAlerts,
        'errors' => $errors
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (PDOException $e) {
    error_log("Create Test Alerts API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error occurred: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    error_log("Create Test Alerts API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error occurred: ' . $e->getMessage()
    ]);
}
?>
