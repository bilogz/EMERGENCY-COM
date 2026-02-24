<?php
/**
 * Dashboard Analytics API
 * Provides analytics data for the dashboard
 */

header('Content-Type: application/json; charset=utf-8');
require_once 'db_connect.php';
require_once __DIR__ . '/../services/DashboardService.php';

try {
    $dashboardService = new DashboardService($pdo);
    $dashboardData = $dashboardService->getDashboardData();
    
    echo json_encode([
        'success' => true,
        'stats' => $dashboardData['stats'],
        'charts' => $dashboardData['charts'],
        'activity' => $dashboardData['activity'],
        'modules' => $dashboardData['modules'] ?? [],
        'generated_at' => $dashboardData['generated_at'] ?? null
    ]);
    
} catch (Throwable $e) {
    error_log("Dashboard API Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error loading dashboard data',
        'stats' => [
            'total_subscribers' => 0,
            'subscriber_change' => 0,
            'notifications_today' => 0,
            'success_rate' => 0,
            'weather_alerts' => 0,
            'earthquake_alerts' => 0,
            'pending_messages' => 0
        ],
        'charts' => [
            'notifications' => [
                'labels' => ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                'values' => [0, 0, 0, 0, 0, 0, 0]
            ],
            'channels' => [
                'labels' => ['SMS', 'Email', 'PA System'],
                'values' => [0, 0, 0]
            ],
            'incident_trend' => [
                'labels' => ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                'weather' => [0, 0, 0, 0, 0, 0, 0],
                'earthquake' => [0, 0, 0, 0, 0, 0, 0]
            ],
            'end_to_end' => [
                'labels' => [
                    'Citizen reports (24h)',
                    'Queue backlog',
                    'In-progress conversations',
                    'Alerts sent today',
                    'Estimated delivered'
                ],
                'values' => [0, 0, 0, 0, 0]
            ],
        ],
        'activity' => [],
        'modules' => [],
        'generated_at' => null
    ]);
}
?>

