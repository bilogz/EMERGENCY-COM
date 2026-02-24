<?php
/**
 * Emergency Communication System Dashboard
 * Refactored static snapshot layout with end-to-end graphs.
 */

session_start();

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
<body data-dashboard-api="../api/dashboard.php">
    <?php include 'includes/sidebar.php'; ?>
    <?php include 'includes/admin-header.php'; ?>

    <div class="main-content">
        <div class="main-container">
            <div class="title">
                <div class="dashboard-admin-chip">
                    <i class="fas fa-user-circle"></i>
                    <strong>Admin:</strong>
                    <span><?php echo htmlspecialchars($_SESSION['admin_username'] ?? 'Admin User'); ?></span>
                </div>
                <h1>
                    Dashboard
                    <span class="help-tooltip">
                        <i class="fas fa-question-circle"></i>
                        <span class="tooltip-text">Static operational snapshot with core performance and end-to-end flow graphs.</span>
                    </span>
                </h1>
                <p>Operations overview for emergency communication modules and delivery flow.</p>
            </div>

            <div class="sub-container">
                <div class="page-content">
                    <section class="dashboard-snapshot-panel" aria-label="Dashboard snapshot controls">
                        <div class="dashboard-snapshot-copy">
                            <div class="dashboard-snapshot-title">
                                <i class="fas fa-camera-retro"></i>
                                Static Snapshot Mode
                            </div>
                            <div class="dashboard-snapshot-meta">
                                Last generated:
                                <strong id="dashboardGeneratedAt">Waiting for data...</strong>
                            </div>
                        </div>
                        <button type="button" id="dashboardRefreshBtn" class="dashboard-refresh-btn">
                            <i class="fas fa-rotate-right"></i>
                            Refresh Snapshot
                        </button>
                    </section>

                    <div class="info-box">
                        <i class="fas fa-info-circle"></i>
                        <strong>How to use this view:</strong> Module Operations Monitor is now the primary KPI strip, styled as the dashboard's main analytics surface.
                    </div>

                    <div class="module-monitor-container">
                        <div class="chart-title">
                            <i class="fas fa-network-wired"></i> Module Operations Monitor
                            <span class="help-tooltip">
                                <i class="fas fa-question-circle"></i>
                                <span class="tooltip-text">Module cards combine health status, workload and integration readiness from current system data.</span>
                            </span>
                        </div>
                        <div id="moduleStatusGrid" class="module-status-grid">
                            <div class="module-status-empty">Loading module health...</div>
                        </div>
                    </div>

                    <section class="dashboard-graph-grid" aria-label="Operational graphs">
                        <div class="chart-container">
                            <div class="chart-title">
                                <i class="fas fa-chart-line"></i> Notifications (7 Days)
                                <span class="help-tooltip">
                                    <i class="fas fa-question-circle"></i>
                                    <span class="tooltip-text">Daily notification volume from audit logs.</span>
                                </span>
                            </div>
                            <canvas id="notificationsChart"></canvas>
                        </div>

                        <div class="chart-container">
                            <div class="chart-title">
                                <i class="fas fa-chart-pie"></i> Notification Channels
                                <span class="help-tooltip">
                                    <i class="fas fa-question-circle"></i>
                                    <span class="tooltip-text">SMS, Email and PA distribution from the latest snapshot.</span>
                                </span>
                            </div>
                            <canvas id="channelsChart"></canvas>
                        </div>

                        <div class="chart-container">
                            <div class="chart-title">
                                <i class="fas fa-chart-column"></i> End-to-End Flow
                                <span class="help-tooltip">
                                    <i class="fas fa-question-circle"></i>
                                    <span class="tooltip-text">Operational funnel from incoming reports to delivered alerts.</span>
                                </span>
                            </div>
                            <canvas id="endToEndChart"></canvas>
                        </div>

                        <div class="chart-container">
                            <div class="chart-title">
                                <i class="fas fa-wave-square"></i> Incident Trend (7 Days)
                                <span class="help-tooltip">
                                    <i class="fas fa-question-circle"></i>
                                    <span class="tooltip-text">Weather versus earthquake alert trend for the past week.</span>
                                </span>
                            </div>
                            <canvas id="incidentTrendChart"></canvas>
                        </div>
                    </section>

                    <div class="chart-container chart-container-actions">
                        <div class="chart-title">
                            <i class="fas fa-bolt"></i> Quick Actions
                            <span class="help-tooltip">
                                <i class="fas fa-question-circle"></i>
                                <span class="tooltip-text">Fast links to the most used operational modules.</span>
                            </span>
                        </div>
                        <div class="quick-actions">
                            <a href="mass-notification.php" class="quick-action-btn">
                                <i class="fas fa-paper-plane"></i>
                                <strong>Send Alert</strong>
                                <small>Issue emergency notification</small>
                            </a>
                            <a href="two-way-communication.php" class="quick-action-btn">
                                <i class="fas fa-comments"></i>
                                <strong>View Messages</strong>
                                <small>Review citizen threads</small>
                            </a>
                            <a href="citizen-subscriptions.php" class="quick-action-btn">
                                <i class="fas fa-users"></i>
                                <strong>Subscribers</strong>
                                <small>Manage enrollments</small>
                            </a>
                            <a href="automated-warnings.php" class="quick-action-btn">
                                <i class="fas fa-plug"></i>
                                <strong>Integrations</strong>
                                <small>Check warning pipelines</small>
                            </a>
                        </div>
                    </div>

                    <div class="recent-activity">
                        <div class="chart-title">
                            <i class="fas fa-history"></i> Recent Activity
                            <span class="help-tooltip">
                                <i class="fas fa-question-circle"></i>
                                <span class="tooltip-text">Latest actions and events captured in system logs.</span>
                            </span>
                        </div>
                        <div id="recentActivity"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="js/module-dashboard.js?v=<?php echo filemtime(__DIR__ . '/js/module-dashboard.js'); ?>"></script>
</body>
</html>
