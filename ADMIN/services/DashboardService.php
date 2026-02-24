<?php
/**
 * Dashboard Service
 * Business logic for dashboard statistics
 * 
 * @package ADMIN\Services
 */

require_once __DIR__ . '/../repositories/DashboardRepository.php';

class DashboardService {
    private $repository;
    
    /**
     * Constructor
     * 
     * @param PDO $pdo Database connection
     */
    public function __construct($pdo) {
        $this->repository = new DashboardRepository($pdo);
    }
    
    /**
     * Get all dashboard statistics
     * 
     * @return array Dashboard statistics array
     */
    public function getStatistics() {
        return [
            'total_subscribers' => $this->repository->getTotalSubscribers(),
            'subscriber_change' => $this->repository->getSubscriberChange(),
            'notifications_today' => $this->repository->getNotificationsToday(),
            'success_rate' => $this->repository->getSuccessRate(),
            'weather_alerts' => $this->repository->getWeatherAlerts(),
            'earthquake_alerts' => $this->repository->getEarthquakeAlerts(),
            'pending_messages' => $this->repository->getPendingMessages()
        ];
    }
    
    /**
     * Get chart data for dashboard
     * 
     * @return array Chart data with notifications and channels
     */
    public function getChartData() {
        $notificationData = $this->repository->getNotificationChartData();
        $channelData = $this->repository->getChannelDistribution();
        
        return [
            'notifications' => [
                'labels' => $notificationData['labels'],
                'values' => $notificationData['values']
            ],
            'channels' => [
                'labels' => ['SMS', 'Email', 'PA System'],
                'values' => [$channelData['sms'], $channelData['email'], $channelData['pa']]
            ]
        ];
    }
    
    /**
     * Get recent activity with formatted time
     * 
     * @param int $limit Number of activities to return
     * @return array Recent activity array
     */
    public function getRecentActivity($limit = 10) {
        $activities = $this->repository->getRecentActivity($limit);
        
        // Format time using timeAgo function
        foreach ($activities as &$activity) {
            $activity['time'] = $this->timeAgo($activity['time']);
        }
        
        return $activities;
    }
    
    /**
     * Get complete dashboard data
     * 
     * @return array Complete dashboard data
     */
    public function getDashboardData() {
        return [
            'stats' => $this->getStatistics(),
            'charts' => $this->getChartData(),
            'activity' => $this->getRecentActivity(),
            'modules' => $this->repository->getModuleStatusOverview(),
            'generated_at' => date('Y-m-d H:i:s')
        ];
    }
    
    /**
     * Format datetime as "time ago" string
     * 
     * @param string $datetime Datetime string
     * @return string Formatted time ago string
     */
    private function timeAgo($datetime) {
        $timestamp = strtotime($datetime);
        $diff = time() - $timestamp;
        
        if ($diff < 60) {
            return 'Just now';
        } elseif ($diff < 3600) {
            $mins = floor($diff / 60);
            return $mins . ' minute' . ($mins > 1 ? 's' : '') . ' ago';
        } elseif ($diff < 86400) {
            $hours = floor($diff / 3600);
            return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
        } elseif ($diff < 604800) {
            $days = floor($diff / 86400);
            return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
        } else {
            return date('M d, Y', $timestamp);
        }
    }
}
