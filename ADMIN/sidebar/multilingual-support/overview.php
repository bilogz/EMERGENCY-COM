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
    <link rel="stylesheet" href="../css/global.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../css/sidebar.css">
    <link rel="stylesheet" href="../css/admin-header.css">
    <link rel="stylesheet" href="../css/buttons.css">
    <link rel="stylesheet" href="../css/hero.css">
    <style>
        /* Custom layout for Overview - Distinct from Settings Pages */
        .overview-container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 2rem 1rem;
        }

        /* Hero / Explainer Section */
        .explainer-section {
            text-align: center;
            margin-bottom: 3rem;
            background: #fff;
            padding: 2.5rem;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.05);
            border: 1px solid var(--border-color-1);
        }

        .explainer-title {
            font-size: 1.5rem;
            color: var(--primary-color-1);
            margin-bottom: 2rem;
            font-weight: 700;
        }

        .process-flow {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 2rem;
            position: relative;
        }

        .process-step {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            position: relative;
            z-index: 2;
        }

        .process-icon {
            width: 80px;
            height: 80px;
            background: var(--bg-color-2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            color: var(--primary-color-1);
            margin-bottom: 1rem;
            border: 2px solid var(--primary-color-1);
            transition: transform 0.3s ease;
        }

        .process-step:hover .process-icon {
            transform: scale(1.1);
            background: var(--primary-color-1);
            color: white;
        }

        .process-label {
            font-weight: 700;
            margin-bottom: 0.5rem;
            color: var(--text-color-1);
        }

        .process-desc {
            font-size: 0.9rem;
            color: var(--text-secondary-1);
            line-height: 1.4;
        }

        /* Connecting Arrows */
        .process-flow::before {
            content: '';
            position: absolute;
            top: 40px;
            left: 10%;
            right: 10%;
            height: 2px;
            background: #e0e0e0;
            z-index: 1;
        }
        
        .arrow-icon {
            position: absolute;
            top: 30px;
            font-size: 1.2rem;
            color: #ccc;
            z-index: 1;
        }
        
        .arrow-1 { left: 33%; }
        .arrow-2 { left: 66%; }

        /* Status Cards Section */
        .status-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 3rem;
        }

        .status-card {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            border: 1px solid var(--border-color-1);
            display: flex;
            align-items: center;
            gap: 1rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.03);
        }

        .status-icon-box {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
        }

        .status-info h4 {
            margin: 0 0 0.25rem 0;
            font-size: 0.85rem;
            color: var(--text-secondary-1);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-info .value {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--text-color-1);
        }

        /* CTA Section */
        .cta-section {
            text-align: center;
            padding: 2rem;
            background: rgba(76, 138, 137, 0.05);
            border-radius: 12px;
            border: 1px dashed var(--primary-color-1);
        }

        .cta-text {
            margin-bottom: 1.5rem;
            color: var(--text-secondary-1);
        }

        .btn-large {
            padding: 0.85rem 2.5rem;
            font-size: 1.1rem;
            border-radius: 30px;
            font-weight: 600;
            box-shadow: 0 4px 15px rgba(76, 138, 137, 0.3);
            transition: all 0.2s;
        }

        .btn-large:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(76, 138, 137, 0.4);
        }

        /* Hide Chat/Messages Panel for this Module */
        .notification-btn[aria-label="Messages"],
        .notification-btn[aria-label="Messages"] + .notification-badge,
        #messageModal,
        #messageContentModal {
            display: none !important;
        }
    </style>
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
