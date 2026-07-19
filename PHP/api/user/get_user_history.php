<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../shared/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    apiResponse::error("Invalid request method. Use GET.", 405);
}

$user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;

if (!$user_id) {
    apiResponse::error("User ID is required.", 400);
}

try {
    // Get emergency reports count
    $reportsQuery = "
        SELECT 
            COUNT(*) as total_reports,
            COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_reports,
            COUNT(CASE WHEN status = 'resolved' THEN 1 END) as resolved_reports
        FROM incident_reports 
        WHERE user_id = :user_id
    ";
    
    $stmt = $pdo->prepare($reportsQuery);
    $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $reportsData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get emergency calls count (from user_activity_logs)
    $callsQuery = "
        SELECT 
            COUNT(*) as total_calls,
            COUNT(CASE WHEN activity_type = 'emergency_call' THEN 1 END) as emergency_calls
        FROM user_activity_logs 
        WHERE user_id = :user_id
    ";
    
    $stmt = $pdo->prepare($callsQuery);
    $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $callsData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get recent reports
    $recentReportsQuery = "
        SELECT 
            id,
            report_type,
            description,
            status,
            created_at
        FROM incident_reports 
        WHERE user_id = :user_id
        ORDER BY created_at DESC
        LIMIT 10
    ";
    
    $stmt = $pdo->prepare($recentReportsQuery);
    $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $recentReports = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get recent activity logs
    $recentActivityQuery = "
        SELECT 
            id,
            activity_type,
            description,
            status,
            created_at
        FROM user_activity_logs 
        WHERE user_id = :user_id
        ORDER BY created_at DESC
        LIMIT 10
    ";
    
    $stmt = $pdo->prepare($recentActivityQuery);
    $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $recentActivity = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $response = [
        'reports' => [
            'total' => (int)$reportsData['total_reports'],
            'pending' => (int)$reportsData['pending_reports'],
            'resolved' => (int)$reportsData['resolved_reports'],
            'recent' => $recentReports
        ],
        'calls' => [
            'total' => (int)$callsData['total_calls'],
            'emergency' => (int)$callsData['emergency_calls'],
            'recent' => $recentActivity
        ]
    ];
    
    apiResponse::success(['data' => $response], "User history retrieved successfully");

} catch (PDOException $e) {
    error_log("Get User History DB Error: " . $e->getMessage());
    apiResponse::error("Database error occurred.", 500);
} catch (Exception $e) {
    error_log("Get User History Error: " . $e->getMessage());
    apiResponse::error("An unexpected error occurred.", 500);
}
