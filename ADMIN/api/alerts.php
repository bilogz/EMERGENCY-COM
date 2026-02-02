<?php
/**
 * Emergency Alerts API
 * Returns active emergency alerts for the frontend listener
 */

// Disable error display to prevent HTML pollution in JSON response
ini_set('display_errors', 0);
// Log errors instead
ini_set('log_errors', 1);

header('Content-Type: application/json; charset=utf-8');

try {
    // Attempt to include DB connection if available, but don't fail hard if not needed for static fallback
    $pdo = null;
    if (file_exists('db_connect.php')) {
        require_once 'db_connect.php';
    }

    $alerts = [];

    if ($pdo) {
        // Fetch active alerts from DB
        // Assuming 'alerts' table exists with columns: id, title, message, category, severity, status, created_at
        // Adjust column names based on your actual schema
        $stmt = $pdo->prepare("SELECT * FROM alerts WHERE status = 'active' ORDER BY created_at DESC LIMIT 10");
        $stmt->execute();
        $alerts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        // Fallback static data for testing if DB is unavailable
        $alerts = [
            [
                "id" => 1,
                "title" => "Flood Watch",
                "message" => "Heavy rain expected in low-lying areas. Exercise caution.",
                "category_name" => "Weather",
                "severity" => "high",
                "status" => "active",
                "created_at" => date('c')
            ],
            [
                "id" => 2,
                "title" => "[EXTREME] Road Closure",
                "message" => "Main St closed due to landslide. Use alternate routes.",
                "category_name" => "Traffic",
                "severity" => "critical",
                "status" => "active",
                "created_at" => date('c')
            ]
        ];
    }

    echo json_encode([
        'success' => true,
        'alerts' => $alerts
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>