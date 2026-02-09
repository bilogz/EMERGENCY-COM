<?php
/**
 * Emergency Communication System Dashboard
 * User-friendly analytics dashboard for non-technical administrators
 */

// Start session and check authentication
session_start();

// Check if user is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../login.php');
    exit();
}

$pageTitle = 'Dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <link rel="icon" type="image/x-icon" href="images/favicon.ico">
    <link rel="stylesheet" href="css/global.css?v=<?php echo filemtime(__DIR__ . '/css/global.css'); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/sidebar.css?v=<?php echo filemtime(__DIR__ . '/css/sidebar.css'); ?>">
    <link rel="stylesheet" href="css/admin-header.css">
    <link rel="stylesheet" href="css/buttons.css">
    <link rel="stylesheet" href="css/hero.css">
    <link rel="stylesheet" href="css/sidebar-footer.css">
    <link rel="stylesheet" href="css/modules.css">
        <link rel="stylesheet" href="css/module-dashboard.css?v=<?php echo filemtime(__DIR__ . '/css/module-dashboard.css'); ?>">
</head>
<body>
    <!-- Include Sidebar Component -->
    <?php include 'includes/sidebar.php'; ?>

    <!-- Include Admin Header Component -->
    <?php include 'includes/admin-header.php'; ?>
    
    <!-- ===================================
       MAIN CONTENT - Dashboard
       =================================== -->
    <div class="main-content">
        <div class="main-container">
            <div class="title">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                    <div style="font-size: 0.9rem; color: var(--text-secondary-1);">
                        <i class="fas fa-user-circle" style="margin-right: 0.5rem;"></i>
                        <strong>Admin:</strong> <?php echo htmlspecialchars($_SESSION['admin_username'] ?? 'Admin User'); ?>
                    </div>
                </div>
                <h1>Dashboard <span class="help-tooltip">
                    <i class="fas fa-question-circle"></i>
                    <span class="tooltip-text">Your main control center. Here you can see all important statistics and quickly access key features.</span>
                </span></h1>
                <p>Welcome back! Here's an overview of your Emergency Communication System.</p>
            </div>
            
            <div class="sub-container">
                <div class="page-content">
                    <!-- Info Box for New Users -->
                    <div class="info-box">
                        <i class="fas fa-info-circle"></i>
                        <strong>Getting Started:</strong> This dashboard shows you everything at a glance. Click on any card or button to learn more about that feature.
                    </div>

                    <!-- Key Statistics -->
                    <div class="dashboard-grid">
                        <div class="stat-card subscribers">
                            <div class="stat-label">
                                <i class="fas fa-users"></i> Total Subscribers
                                <span class="help-tooltip">
                                    <i class="fas fa-question-circle"></i>
                                    <span class="tooltip-text">Total number of citizens registered to receive emergency alerts</span>
                                </span>
                            </div>
                            <div class="stat-value" id="totalSubscribers">0</div>
                            <div class="stat-change positive">
                                <i class="fas fa-arrow-up"></i>
                                <span id="subscriberChange">+0 this week</span>
                            </div>
                        </div>

                        <div class="stat-card notifications">
                            <div class="stat-label">
                                <i class="fas fa-bell"></i> Notifications Sent Today
                                <span class="help-tooltip">
                                    <i class="fas fa-question-circle"></i>
                                    <span class="tooltip-text">Number of emergency alerts sent today through all channels (SMS, Email, PA)</span>
                                </span>
                            </div>
                            <div class="stat-value" id="notificationsToday">0</div>
                            <div class="stat-change positive">
                                <i class="fas fa-check-circle"></i>
                                <span id="notificationStatus">All delivered</span>
                            </div>
                        </div>

                        <div class="stat-card success">
                            <div class="stat-label">
                                <i class="fas fa-check-circle"></i> Success Rate
                                <span class="help-tooltip">
                                    <i class="fas fa-question-circle"></i>
                                    <span class="tooltip-text">Percentage of notifications successfully delivered to recipients</span>
                                </span>
                            </div>
                            <div class="stat-value" id="successRate">0%</div>
                            <div class="stat-change positive">
                                <i class="fas fa-arrow-up"></i>
                                <span>Excellent</span>
                            </div>
                        </div>

                        <div class="stat-card weather">
                            <div class="stat-label">
                                <i class="fas fa-cloud-rain"></i> Weather Alerts
                                <span class="help-tooltip">
                                    <i class="fas fa-question-circle"></i>
                                    <span class="tooltip-text">Active weather-related alerts from PAGASA</span>
                                </span>
                            </div>
                            <div class="stat-value" id="weatherAlerts">0</div>
                            <div class="stat-change" id="weatherStatus">
                                <i class="fas fa-info-circle"></i>
                                <span>No active alerts</span>
                            </div>
                        </div>

                        <div class="stat-card earthquake">
                            <div class="stat-label">
                                <i class="fas fa-mountain"></i> Earthquake Alerts
                                <span class="help-tooltip">
                                    <i class="fas fa-question-circle"></i>
                                    <span class="tooltip-text">Active earthquake warnings from PHIVOLCS</span>
                                </span>
                            </div>
                            <div class="stat-value" id="earthquakeAlerts">0</div>
                            <div class="stat-change" id="earthquakeStatus">
                                <i class="fas fa-info-circle"></i>
                                <span>No active alerts</span>
                            </div>
                        </div>

                        <div class="stat-card pending">
                            <div class="stat-label">
                                <i class="fas fa-clock"></i> Pending Messages
                                <span class="help-tooltip">
                                    <i class="fas fa-question-circle"></i>
                                    <span class="tooltip-text">Messages from citizens waiting for your response</span>
                                </span>
                            </div>
                            <div class="stat-value" id="pendingMessages">0</div>
                            <div class="stat-change">
                                <i class="fas fa-comments"></i>
                                <span><a href="two-way-communication.php" style="color: var(--primary-color-1);">View conversations</a></span>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="chart-container">
                        <div class="chart-title">
                            <i class="fas fa-bolt"></i> Quick Actions
                            <span class="help-tooltip">
                                <i class="fas fa-question-circle"></i>
                                <span class="tooltip-text">Fast access to the most commonly used features</span>
                            </span>
                        </div>
                        <div class="quick-actions">
                            <a href="mass-notification.php" class="quick-action-btn">
                                <i class="fas fa-paper-plane"></i>
                                <strong>Send Alert</strong>
                                <small>Send emergency notification</small>
                            </a>
                            <a href="two-way-communication.php" class="quick-action-btn">
                                <i class="fas fa-comments"></i>
                                <strong>View Messages</strong>
                                <small>Check citizen messages</small>
                            </a>
                            <a href="citizen-subscriptions.php" class="quick-action-btn">
                                <i class="fas fa-users"></i>
                                <strong>Manage Subscribers</strong>
                                <small>View and edit subscriptions</small>
                            </a>
                            <a href="automated-warnings.php" class="quick-action-btn">
                                <i class="fas fa-plug"></i>
                                <strong>Check Integrations</strong>
                                <small>PAGASA & PHIVOLCS status</small>
                            </a>
                        </div>
                    </div>

                    <!-- Charts Row -->
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
                        <!-- Notifications Chart -->
                        <div class="chart-container">
                            <div class="chart-title">
                                <i class="fas fa-chart-line"></i> Notifications This Week
                                <span class="help-tooltip">
                                    <i class="fas fa-question-circle"></i>
                                    <span class="tooltip-text">Daily breakdown of notifications sent over the past 7 days</span>
                                </span>
                            </div>
                            <canvas id="notificationsChart" height="200"></canvas>
                        </div>

                        <!-- Channel Distribution -->
                        <div class="chart-container">
                            <div class="chart-title">
                                <i class="fas fa-chart-pie"></i> Notification Channels
                                <span class="help-tooltip">
                                    <i class="fas fa-question-circle"></i>
                                    <span class="tooltip-text">Distribution of notifications by channel (SMS, Email, PA System)</span>
                                </span>
                            </div>
                            <canvas id="channelsChart" height="200"></canvas>
                        </div>
                    </div>

                    <!-- Recent Activity -->
                    <div class="recent-activity">
                        <div class="chart-title">
                            <i class="fas fa-history"></i> Recent Activity
                            <span class="help-tooltip">
                                <i class="fas fa-question-circle"></i>
                                <span class="tooltip-text">Latest actions and events in your system</span>
                            </span>
                        </div>
                        <div id="recentActivity">
                            <!-- Activity items will be loaded here -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Load dashboard data (prevent multiple simultaneous requests)
        let isLoading = false;
        function loadDashboardData() {
            // Prevent multiple simultaneous requests
            if (isLoading) {
                return;
            }
            
            isLoading = true;
            
            fetch('../api/dashboard.php')
                .then(response => response.json())
                .then(data => {
                    isLoading = false;
                    
                    if (data.success) {
                        // Update statistics only if elements exist
                        const elements = {
                            totalSubscribers: document.getElementById('totalSubscribers'),
                            subscriberChange: document.getElementById('subscriberChange'),
                            notificationsToday: document.getElementById('notificationsToday'),
                            successRate: document.getElementById('successRate'),
                            weatherAlerts: document.getElementById('weatherAlerts'),
                            earthquakeAlerts: document.getElementById('earthquakeAlerts'),
                            pendingMessages: document.getElementById('pendingMessages'),
                            weatherStatus: document.getElementById('weatherStatus'),
                            earthquakeStatus: document.getElementById('earthquakeStatus')
                        };
                        
                        if (elements.totalSubscribers) elements.totalSubscribers.textContent = data.stats.total_subscribers || 0;
                        if (elements.subscriberChange) elements.subscriberChange.textContent = `+${data.stats.subscriber_change || 0} this week`;
                        if (elements.notificationsToday) elements.notificationsToday.textContent = data.stats.notifications_today || 0;
                        if (elements.successRate) elements.successRate.textContent = (data.stats.success_rate || 0) + '%';
                        if (elements.weatherAlerts) elements.weatherAlerts.textContent = data.stats.weather_alerts || 0;
                        if (elements.earthquakeAlerts) elements.earthquakeAlerts.textContent = data.stats.earthquake_alerts || 0;
                        if (elements.pendingMessages) elements.pendingMessages.textContent = data.stats.pending_messages || 0;

                        // Update weather status
                        if (elements.weatherStatus && data.stats.weather_alerts > 0) {
                            elements.weatherStatus.innerHTML = '<i class="fas fa-exclamation-triangle"></i> <span style="color: #e74c3c;">Active alerts</span>';
                        }

                        // Update earthquake status
                        if (elements.earthquakeStatus && data.stats.earthquake_alerts > 0) {
                            elements.earthquakeStatus.innerHTML = '<i class="fas fa-exclamation-triangle"></i> <span style="color: #e74c3c;">Active alerts</span>';
                        }

                        // Load charts
                        if (data.charts) {
                            if (data.charts.notifications) loadNotificationsChart(data.charts.notifications);
                            if (data.charts.channels) loadChannelsChart(data.charts.channels);
                        }

                        // Load recent activity
                        if (data.activity) {
                            loadRecentActivity(data.activity);
                        }
                    }
                })
                .catch(error => {
                    isLoading = false;
                    console.error('Error loading dashboard data:', error);
                });
        }

        let notificationsChartInstance = null;
        let channelsChartInstance = null;
        
        function loadNotificationsChart(data) {
            const ctx = document.getElementById('notificationsChart');
            if (!ctx) return;
            
            // Destroy existing chart if it exists
            if (notificationsChartInstance) {
                notificationsChartInstance.destroy();
            }
            
            notificationsChartInstance = new Chart(ctx.getContext('2d'), {
                type: 'line',
                data: {
                    labels: data.labels || ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                    datasets: [{
                        label: 'Notifications',
                        data: data.values || [0, 0, 0, 0, 0, 0, 0],
                        borderColor: '#4c8a89',
                        backgroundColor: 'rgba(76, 138, 137, 0.1)',
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        }

        function loadChannelsChart(data) {
            const ctx = document.getElementById('channelsChart');
            if (!ctx) return;
            
            // Destroy existing chart if it exists
            if (channelsChartInstance) {
                channelsChartInstance.destroy();
            }
            
            channelsChartInstance = new Chart(ctx.getContext('2d'), {
                type: 'doughnut',
                data: {
                    labels: data.labels || ['SMS', 'Email', 'PA System'],
                    datasets: [{
                        data: data.values || [0, 0, 0],
                        backgroundColor: ['#3498db', '#2ecc71', '#f39c12']
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });
        }

        function loadRecentActivity(activities) {
            const container = document.getElementById('recentActivity');
            container.innerHTML = '';

            if (!activities || activities.length === 0) {
                container.innerHTML = '<div class="activity-item"><p style="color: var(--text-secondary-1);">No recent activity</p></div>';
                return;
            }

            activities.forEach(activity => {
                const item = document.createElement('div');
                item.className = 'activity-item';
                
                const iconClass = {
                    'notification': 'fa-bell',
                    'message': 'fa-comments',
                    'subscription': 'fa-user-plus',
                    'alert': 'fa-exclamation-triangle',
                    'integration': 'fa-plug'
                }[activity.type] || 'fa-circle';

                item.innerHTML = `
                    <div class="activity-icon">
                        <i class="fas ${iconClass}"></i>
                    </div>
                    <div class="activity-content">
                        <div class="activity-title">${activity.title}</div>
                        <div class="activity-time">${activity.time}</div>
                    </div>
                `;
                container.appendChild(item);
            });
        }

        // Load data on page load (wait for DOM to be ready)
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', loadDashboardData);
        } else {
            // DOM is already ready
            loadDashboardData();
        }

        // Refresh data every 5 minutes (only update data, don't reload page)
        setInterval(function() {
            if (!isLoading) {
                loadDashboardData();
            }
        }, 300000);
    </script>
</body>
</html>

