<?php
/**
 * Multilingual Support Overview
 * Visual Guide & System Status (Read-Only)
 */

session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../../login.php');
    exit();
}

$pageTitle = 'Multilingual Support Overview';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <link rel="icon" type="image/x-icon" href="../images/favicon.ico">
    <link rel="stylesheet" href="../css/global.css?v=<?php echo filemtime(__DIR__ . '/../css/global.css'); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../css/sidebar.css?v=<?php echo filemtime(__DIR__ . '/../css/sidebar.css'); ?>">
    <link rel="stylesheet" href="../css/admin-header.css">
    <link rel="stylesheet" href="../css/buttons.css">
    <link rel="stylesheet" href="../css/hero.css">
        <link rel="stylesheet" href="../css/module-multilingual-overview.css?v=<?php echo filemtime(__DIR__ . '/../css/module-multilingual-overview.css'); ?>">
</head>
<body>
    <?php include '../includes/sidebar.php'; ?>
    <?php include '../includes/admin-header.php'; ?>
    
    <div class="main-content">
        <div class="overview-container">
            
            <!-- 1. Hero / Explainer -->
            <div class="explainer-section">
                <h2 class="explainer-title">How Multilingual Alert Translation Works</h2>
                
                <div class="process-flow">
                    <i class="fas fa-chevron-right arrow-icon arrow-1"></i>
                    <i class="fas fa-chevron-right arrow-icon arrow-2"></i>

                    <!-- Step 1 -->
                    <div class="process-step">
                        <div class="process-icon">
                            <i class="fas fa-paper-plane"></i>
                        </div>
                        <div class="process-label">Admin Sends Alert</div>
                        <div class="process-desc">You create one alert in the base language (English).</div>
                    </div>

                    <!-- Step 2 -->
                    <div class="process-step">
                        <div class="process-icon">
                            <i class="fas fa-brain"></i>
                        </div>
                        <div class="process-label">AI Translates</div>
                        <div class="process-desc">The system automatically translates content based on user preference.</div>
                    </div>

                    <!-- Step 3 -->
                    <div class="process-step">
                        <div class="process-icon">
                            <i class="fas fa-mobile-alt"></i>
                        </div>
                        <div class="process-label">Citizen Receives</div>
                        <div class="process-desc">Each user sees the alert in their own language.</div>
                    </div>
                </div>
            </div>

            <!-- 2. System State Cards -->
            <div class="status-grid">
                <!-- AI Status -->
                <div class="status-card">
                    <div class="status-icon-box" style="background: rgba(46, 204, 113, 0.1); color: #2ecc71;" id="aiStatusIcon">
                        <i class="fas fa-robot"></i>
                    </div>
                    <div class="status-info">
                        <h4>AI Translation</h4>
                        <div class="value" id="aiStatusText">Checking...</div>
                    </div>
                </div>

                <!-- Languages Count -->
                <div class="status-card">
                    <div class="status-icon-box" style="background: rgba(52, 152, 219, 0.1); color: #3498db;">
                        <i class="fas fa-globe"></i>
                    </div>
                    <div class="status-info">
                        <h4>Supported Languages</h4>
                        <div class="value" id="langCount">-</div>
                    </div>
                </div>

                <!-- Base Language -->
                <div class="status-card">
                    <div class="status-icon-box" style="background: rgba(155, 89, 182, 0.1); color: #9b59b6;">
                        <i class="fas fa-flag"></i>
                    </div>
                    <div class="status-info">
                        <h4>Base Language</h4>
                        <div class="value">English (US)</div>
                    </div>
                </div>
            </div>

            <!-- 3. Call to Action -->
            <div class="cta-section">
                <p class="cta-text">Need to add more languages or disable existing ones?</p>
                <a href="language-management.php" class="btn btn-primary btn-large">
                    <i class="fas fa-cog"></i> Manage Languages
                </a>
            </div>

        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Check AI Status
            fetch('../../api/ai-translation-service.php')
                .then(r => r.json())
                .then(data => {
                    const statusText = document.getElementById('aiStatusText');
                    const statusIcon = document.getElementById('aiStatusIcon');
                    if (data.available) {
                        statusText.textContent = 'Active';
                        statusText.style.color = '#2ecc71';
                        statusIcon.style.background = 'rgba(46, 204, 113, 0.1)';
                        statusIcon.style.color = '#2ecc71';
                    } else {
                        statusText.textContent = 'Disabled';
                        statusText.style.color = '#e74c3c';
                        statusIcon.style.background = 'rgba(231, 76, 60, 0.1)';
                        statusIcon.style.color = '#e74c3c';
                    }
                })
                .catch(() => {
                    document.getElementById('aiStatusText').textContent = 'Error';
                });

            // Get Language Count
            fetch('../../api/multilingual-alerts.php?action=languages')
                .then(r => r.json())
                .then(data => {
                    if (data.success && data.languages) {
                        document.getElementById('langCount').textContent = data.languages.length;
                    }
                });
        });
    </script>
</body>
</html>
