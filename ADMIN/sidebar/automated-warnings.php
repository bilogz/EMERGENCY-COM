<?php
/**
 * Automated Warning Integration Page
 * Integrate with external warning feeds like PAGASA and PHIVOLCS
 */

// Start session and check authentication
session_start();

// Check if user is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../login.php');
    exit();
}

$pageTitle = 'Automated Warning Integration';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <link rel="icon" type="image/x-icon" href="images/favicon.ico">
    <link rel="stylesheet" href="css/global.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/sidebar.css">
    <link rel="stylesheet" href="css/admin-header.css">
    <link rel="stylesheet" href="css/buttons.css">
    <link rel="stylesheet" href="css/forms.css">
    <link rel="stylesheet" href="css/datatables.css">
    <link rel="stylesheet" href="css/hero.css">
    <link rel="stylesheet" href="css/sidebar-footer.css">
    <link rel="stylesheet" href="css/modules.css">
</head>
<body>
    <!-- Include Sidebar Component -->
    <?php include 'includes/sidebar.php'; ?>

    <!-- Include Admin Header Component -->
    <?php include 'includes/admin-header.php'; ?>
    
    <!-- ===================================
       MAIN CONTENT - Automated Warning Integration
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
                <nav class="breadcrumb" aria-label="Breadcrumb">
                    <ol class="breadcrumb-list">
                        <li class="breadcrumb-item">
                            <a href="/" class="breadcrumb-link">
                                <span>Home</span>
                            </a>
                        </li>
                        <li class="breadcrumb-item">
                            <a href="/emergency-communication" class="breadcrumb-link">
                                <span>Emergency Communication</span>
                            </a>
                        </li>
                        <li class="breadcrumb-item active" aria-current="page">
                            <span>Automated Warning Integration</span>
                        </li>
                    </ol>
                </nav>
                <h1>Automated Warning Integration</h1>
                <p>Integrate with external warning feeds from PAGASA (weather) and PHIVOLCS (earthquake) for automated alert distribution.</p>
                <div class="info-box" style="background: #e3f2fd; border-left: 4px solid #2196f3; padding: 1rem; border-radius: 4px; margin-top: 1rem;">
                    <i class="fas fa-info-circle" style="color: #2196f3;"></i>
                    <strong>How to use:</strong> Monitor the integration status for PAGASA (weather) and PHIVOLCS (earthquake). Configure AI-powered warnings by entering your Gemini 2.5 API key and selecting the disaster types you want to monitor. Warnings will automatically sync and can be sent to subscribers.
                </div>
            </div>
            
            <div class="sub-container">
                <div class="page-content">
                    <!-- Integration Status -->
                    <div class="module-card">
                        <div class="module-card-header">
                            <h2><i class="fas fa-plug"></i> Integration Status</h2>
                        </div>
                        <div>
                            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem;">
                                <div class="channel-card" id="pagasaCard">
                                    <div>
                                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                                            <h3><i class="fas fa-cloud-sun" style="color: #4c8a89;"></i> PAGASA</h3>
                                            <span class="badge" id="pagasaStatus">Connecting...</span>
                                        </div>
                                        <p>Philippine Atmospheric, Geophysical and Astronomical Services Administration</p>
                                        <div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid rgba(255,255,255,0.1);">
                                            <small style="color: var(--text-secondary-1);">Status: <span id="pagasaStatusText">Checking...</span></small>
                                        </div>
                                    </div>
                                </div>
                                <div class="channel-card" id="phivolcsCard">
                                    <div>
                                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                                            <h3><i class="fas fa-mountain" style="color: #4c8a89;"></i> PHIVOLCS</h3>
                                            <span class="badge" id="phivolcsStatus">Connecting...</span>
                                        </div>
                                        <p>Philippine Institute of Volcanology and Seismology</p>
                                        <div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid rgba(255,255,255,0.1);">
                                            <small style="color: var(--text-secondary-1);">Status: <span id="phivolcsStatusText">Checking...</span></small>
                                        </div>
                                    </div>
                                </div>
                                <div class="channel-card" id="geminiCard">
                                    <div>
                                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                                            <h3><i class="fas fa-robot" style="color: #4c8a89;"></i> AI Powered (Gemini 2.5)</h3>
                                            <span class="badge" id="geminiStatus">Checking...</span>
                                        </div>
                                        <p>Google Gemini 2.5 AI Integration for Advanced Warning Analysis</p>
                                        <div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid rgba(255,255,255,0.1);">
                                            <small style="color: var(--text-secondary-1);">Status: <span id="geminiStatusText">API Key Required</span></small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Settings Cards -->
                    <div class="module-card">
                        <div class="module-card-header">
                            <h2><i class="fas fa-cog"></i> Settings</h2>
                        </div>
                        <div>
                            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.5rem;">
                                <div class="channel-card" style="cursor: pointer; transition: transform 0.2s;" onclick="openIntegrationSettingsModal()">
                                    <div>
                                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                                            <h3><i class="fas fa-sync-alt" style="color: #4c8a89;"></i> Integration Settings</h3>
                                            <i class="fas fa-chevron-right" style="color: var(--text-secondary-1);"></i>
                                        </div>
                                        <p>Configure sync intervals, auto-publish, and notification channels</p>
                                        <div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid rgba(255,255,255,0.1);">
                                            <small style="color: var(--text-secondary-1);">Click to configure</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="channel-card" style="cursor: pointer; transition: transform 0.2s;" onclick="openAISettingsModal()">
                                    <div>
                                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                                            <h3><i class="fas fa-robot" style="color: #4c8a89;"></i> AI Warning Settings</h3>
                                            <i class="fas fa-chevron-right" style="color: var(--text-secondary-1);"></i>
                                        </div>
                                        <p>Configure Gemini 2.5 API, disaster types, and AI thresholds</p>
                                        <div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid rgba(255,255,255,0.1);">
                                            <small style="color: var(--text-secondary-1);">Click to configure</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Warnings -->
                    <div class="module-card">
                        <div class="module-card-header">
                            <h2><i class="fas fa-exclamation-triangle"></i> Recent Automated Warnings</h2>
                        </div>
                        <div>
                            <table class="data-table" id="warningsTable">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Source</th>
                                        <th>Type</th>
                                        <th>Title</th>
                                        <th>Severity</th>
                                        <th>Status</th>
                                        <th>Received At</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Data will be loaded via API -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Integration Settings Modal -->
    <div id="integrationSettingsModal" class="settings-modal" style="display: none;">
        <div class="modal-overlay" onclick="closeIntegrationSettingsModal()"></div>
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-cog"></i> Integration Settings</h2>
                <button class="modal-close" onclick="closeIntegrationSettingsModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form id="integrationSettingsForm">
                    <div class="form-group">
                        <label for="syncInterval">Sync Interval (minutes)</label>
                        <input type="number" id="syncInterval" name="sync_interval" value="15" min="1" max="60">
                        <small>How often to check for new warnings from external sources</small>
                    </div>
                    <div class="form-group">
                        <label for="autoPublish">Auto-Publish Warnings</label>
                        <label class="switch">
                            <input type="checkbox" id="autoPublish" name="auto_publish">
                            <span class="slider"></span>
                        </label>
                        <small>Automatically publish warnings from external sources without manual review</small>
                    </div>
                    <div class="form-group">
                        <label for="notificationChannels">Notification Channels</label>
                        <div style="display: flex; gap: 1rem; margin-top: 0.5rem;">
                            <label><input type="checkbox" name="channels[]" value="sms" checked> SMS</label>
                            <label><input type="checkbox" name="channels[]" value="email" checked> Email</label>
                            <label><input type="checkbox" name="channels[]" value="pa"> PA System</label>
                        </div>
                    </div>
                    <div class="form-actions">
                        <button type="button" class="btn btn-secondary" onclick="closeIntegrationSettingsModal()">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Save Settings
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- AI Warning Settings Modal -->
    <div id="aiSettingsModal" class="settings-modal" style="display: none;">
        <div class="modal-overlay" onclick="closeAISettingsModal()"></div>
        <div class="modal-content modal-content-large">
            <div class="modal-header">
                <h2><i class="fas fa-robot"></i> AI-Powered Auto Warning System (Gemini 2.5)</h2>
                <button class="modal-close" onclick="closeAISettingsModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form id="aiWarningSettingsForm">
                    <div class="form-group">
                        <label for="geminiApiKey">Gemini 2.5 API Key</label>
                        <input type="password" id="geminiApiKey" name="gemini_api_key" placeholder="Enter your Gemini 2.5 API key" style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 4px;">
                        <small>Enter your Google Gemini 2.5 API key to enable AI-powered warning analysis</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="aiEnabled">Enable AI Auto Warnings</label>
                        <label class="switch">
                            <input type="checkbox" id="aiEnabled" name="ai_enabled">
                            <span class="slider"></span>
                        </label>
                        <small>Use AI to automatically detect dangerous weather conditions and send mass notifications</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="aiCheckInterval">AI Analysis Interval (minutes)</label>
                        <input type="number" id="aiCheckInterval" name="ai_check_interval" value="30" min="5" max="120">
                        <small>How often AI analyzes weather data for dangerous conditions</small>
                    </div>
                    
                    <div class="form-group">
                        <label>Danger Thresholds</label>
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem; margin-top: 0.5rem;">
                            <div>
                                <label for="windThreshold">Wind Speed (km/h)</label>
                                <input type="number" id="windThreshold" name="wind_threshold" value="60" min="0" step="5">
                                <small>Alert if wind exceeds this speed</small>
                            </div>
                            <div>
                                <label for="rainThreshold">Rainfall (mm/hour)</label>
                                <input type="number" id="rainThreshold" name="rain_threshold" value="20" min="0" step="1">
                                <small>Alert if rainfall exceeds this rate</small>
                            </div>
                            <div>
                                <label for="earthquakeThreshold">Earthquake Magnitude</label>
                                <input type="number" id="earthquakeThreshold" name="earthquake_threshold" value="5.0" min="0" step="0.1">
                                <small>Alert if earthquake magnitude exceeds this</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Possible Disaster Alert Types</label>
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 0.75rem; margin-top: 0.5rem;">
                            <label style="display: flex; align-items: center; gap: 0.5rem;">
                                <input type="checkbox" name="warning_types[]" value="heavy_rain" checked>
                                <span><i class="fas fa-cloud-rain" style="color: #2196F3;"></i> Heavy Raining</span>
                            </label>
                            <label style="display: flex; align-items: center; gap: 0.5rem;">
                                <input type="checkbox" name="warning_types[]" value="flooding" checked>
                                <span><i class="fas fa-water" style="color: #2196F3;"></i> Flooding</span>
                            </label>
                            <label style="display: flex; align-items: center; gap: 0.5rem;">
                                <input type="checkbox" name="warning_types[]" value="earthquake" checked>
                                <span><i class="fas fa-mountain" style="color: #E91E63;"></i> Earthquake</span>
                            </label>
                            <label style="display: flex; align-items: center; gap: 0.5rem;">
                                <input type="checkbox" name="warning_types[]" value="strong_winds" checked>
                                <span><i class="fas fa-wind" style="color: #9C27B0;"></i> Strong Winds</span>
                            </label>
                            <label style="display: flex; align-items: center; gap: 0.5rem;">
                                <input type="checkbox" name="warning_types[]" value="tsunami" checked>
                                <span><i class="fas fa-water" style="color: #00BCD4;"></i> Tsunami</span>
                            </label>
                            <label style="display: flex; align-items: center; gap: 0.5rem;">
                                <input type="checkbox" name="warning_types[]" value="landslide" checked>
                                <span><i class="fas fa-mountain" style="color: #FF9800;"></i> Landslide</span>
                            </label>
                            <label style="display: flex; align-items: center; gap: 0.5rem;">
                                <input type="checkbox" name="warning_types[]" value="thunderstorm" checked>
                                <span><i class="fas fa-bolt" style="color: #FFC107;"></i> Thunder Storm</span>
                            </label>
                            <label style="display: flex; align-items: center; gap: 0.5rem;">
                                <input type="checkbox" name="warning_types[]" value="ash_fall" checked>
                                <span><i class="fas fa-volcano" style="color: #795548;"></i> Ash Fall</span>
                            </label>
                            <label style="display: flex; align-items: center; gap: 0.5rem;">
                                <input type="checkbox" name="warning_types[]" value="fire_incident" checked>
                                <span><i class="fas fa-fire" style="color: #F44336;"></i> Fire Incident</span>
                            </label>
                            <label style="display: flex; align-items: center; gap: 0.5rem;">
                                <input type="checkbox" name="warning_types[]" value="typhoon" checked>
                                <span><i class="fas fa-hurricane" style="color: #F44336;"></i> Typhoon/Storm</span>
                            </label>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Monitored Areas</label>
                        <div style="margin-top: 0.5rem;">
                            <textarea id="monitoredAreas" name="monitored_areas" rows="4" style="width: 100%; padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px;" placeholder="Enter areas to monitor (one per line):&#10;Quezon City&#10;Manila&#10;Makati"></textarea>
                            <small>List areas to monitor for flooding/landslide risks (one per line)</small>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="aiNotificationChannels">AI Warning Channels</label>
                        <div style="display: flex; gap: 1rem; margin-top: 0.5rem;">
                            <label><input type="checkbox" name="ai_channels[]" value="sms" checked> SMS</label>
                            <label><input type="checkbox" name="ai_channels[]" value="email" checked> Email</label>
                            <label><input type="checkbox" name="ai_channels[]" value="pa" checked> PA System</label>
                        </div>
                    </div>
                    
                    <div style="margin: 2rem 0; padding: 1.5rem; background: rgba(142, 68, 173, 0.1); border-left: 4px solid #8e44ad; border-radius: 4px;">
                        <h3 style="margin-top: 0; color: #8e44ad; display: flex; align-items: center; gap: 0.5rem;">
                            <i class="fas fa-cloud-sun"></i> AI Weather Analysis Auto-Send
                        </h3>
                        <p style="margin: 0.5rem 0 1rem 0; color: var(--text-secondary-1);">Automatically send AI-powered weather analysis to users via mass notifications</p>
                        
                        <div class="form-group">
                            <label for="weatherAnalysisAutoSend">Enable AI Weather Analysis Auto-Send</label>
                            <label class="switch">
                                <input type="checkbox" id="weatherAnalysisAutoSend" name="weather_analysis_auto_send">
                                <span class="slider"></span>
                            </label>
                            <small>Automatically send AI weather analysis to subscribed users via SMS, Email, and PA System</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="weatherAnalysisInterval">Weather Analysis Send Interval (minutes)</label>
                            <input type="number" id="weatherAnalysisInterval" name="weather_analysis_interval" value="60" min="15" max="360">
                            <small>How often to send weather analysis to users (minimum 15 minutes)</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="weatherAnalysisVerificationKey">Verification Key</label>
                            <input type="text" id="weatherAnalysisVerificationKey" name="weather_analysis_verification_key" placeholder="Enter verification key (optional)" style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 4px;">
                            <small>Key to verify weather analysis authenticity (included in notifications)</small>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="button" class="btn btn-secondary" onclick="closeAISettingsModal()">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Save AI Settings
                        </button>
                        <button type="button" class="btn btn-info" onclick="testAIWarning()">
                            <i class="fas fa-vial"></i> Test AI Warning
                        </button>
                        <button type="button" class="btn btn-info" onclick="checkAIWarnings(event)">
                            <i class="fas fa-search"></i> Check Now
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <style>
        /* Settings Modal Styles */
        .settings-modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 2000;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .modal-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(4px);
        }

        .settings-modal .modal-content {
            position: relative;
            background-color: var(--card-bg-1);
            border-radius: 0.75rem;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            max-width: 600px;
            width: 90%;
            max-height: 90vh;
            display: flex;
            flex-direction: column;
            z-index: 2001;
            animation: modalSlideIn 0.3s ease-out;
        }

        .settings-modal .modal-content-large {
            max-width: 900px;
        }

        @keyframes modalSlideIn {
            from {
                opacity: 0;
                transform: translateY(-20px) scale(0.95);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        .settings-modal .modal-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1.5rem;
            border-bottom: 1px solid var(--border-color-1);
            background-color: var(--header-bg-1);
            border-radius: 0.75rem 0.75rem 0 0;
        }

        .settings-modal .modal-header h2 {
            margin: 0;
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--text-color-1);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .settings-modal .modal-close {
            background: none;
            border: none;
            color: var(--text-secondary-1);
            font-size: 1.5rem;
            cursor: pointer;
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            transition: all 0.2s ease;
            line-height: 1;
        }

        .settings-modal .modal-close:hover {
            background-color: var(--sidebar-hover-bg-1);
            color: var(--primary-color-1);
        }

        .settings-modal .modal-body {
            padding: 1.5rem;
            overflow-y: auto;
            flex: 1;
        }

        .settings-modal .modal-body::-webkit-scrollbar {
            width: 8px;
        }

        .settings-modal .modal-body::-webkit-scrollbar-track {
            background: var(--sidebar-bg-1);
            border-radius: 4px;
        }

        .settings-modal .modal-body::-webkit-scrollbar-thumb {
            background: var(--text-secondary-1);
            border-radius: 4px;
        }

        .settings-modal .modal-body::-webkit-scrollbar-thumb:hover {
            background: var(--primary-color-1);
        }

        .channel-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        @media (max-width: 768px) {
            .settings-modal .modal-content {
                width: 95%;
                max-height: 95vh;
            }

            .settings-modal .modal-content-large {
                max-width: 95%;
            }

            .settings-modal .modal-header {
                padding: 1rem;
            }

            .settings-modal .modal-body {
                padding: 1rem;
            }
        }
    </style>

    <script>
        function loadIntegrationStatus() {
            fetch('../api/automated-warnings.php?action=status')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        console.log('Integration Status:', data); // Debug log
                        // PAGASA Status
                        if (data.pagasa) {
                            const isConnected = data.pagasa.enabled;
                            document.getElementById('pagasaStatus').textContent = isConnected ? 'CONNECTED' : 'DISCONNECTED';
                            document.getElementById('pagasaStatus').className = 'badge ' + (isConnected ? 'success' : 'secondary');
                            document.getElementById('pagasaStatusText').textContent = isConnected ? 'Active and Syncing' : 'Not Connected';
                        } else {
                            document.getElementById('pagasaStatus').textContent = 'DISCONNECTED';
                            document.getElementById('pagasaStatus').className = 'badge secondary';
                            document.getElementById('pagasaStatusText').textContent = 'Not Configured';
                        }
                        
                        // PHIVOLCS Status
                        if (data.phivolcs) {
                            const isConnected = data.phivolcs.enabled;
                            document.getElementById('phivolcsStatus').textContent = isConnected ? 'CONNECTED' : 'DISCONNECTED';
                            document.getElementById('phivolcsStatus').className = 'badge ' + (isConnected ? 'success' : 'secondary');
                            document.getElementById('phivolcsStatusText').textContent = isConnected ? 'Active and Syncing' : 'Not Connected';
                        } else {
                            document.getElementById('phivolcsStatus').textContent = 'DISCONNECTED';
                            document.getElementById('phivolcsStatus').className = 'badge secondary';
                            document.getElementById('phivolcsStatusText').textContent = 'Not Configured';
                        }
                        
                        // Gemini Status
                        if (data.gemini) {
                            const isConnected = data.gemini.enabled && data.gemini.api_key_set;
                            const statusBadge = document.getElementById('geminiStatus');
                            const statusText = document.getElementById('geminiStatusText');
                            
                            if (isConnected) {
                                statusBadge.textContent = 'CONNECTED';
                                statusBadge.className = 'badge success';
                                statusText.textContent = data.gemini.status_message || 'AI Active and Monitoring';
                            } else if (data.gemini.api_key_set) {
                                statusBadge.textContent = 'READY';
                                statusBadge.className = 'badge warning';
                                statusText.textContent = data.gemini.status_message || 'API Key Set - Enable AI';
                            } else {
                                statusBadge.textContent = 'DISCONNECTED';
                                statusBadge.className = 'badge secondary';
                                statusText.textContent = data.gemini.status_message || 'API Key Required';
                            }
                        } else {
                            document.getElementById('geminiStatus').textContent = 'DISCONNECTED';
                            document.getElementById('geminiStatus').className = 'badge secondary';
                            document.getElementById('geminiStatusText').textContent = 'API Key Required';
                        }
                    }
                })
                .catch(error => {
                    console.error('Error loading integration status:', error);
                });
        }

        function loadWarnings() {
            fetch('../api/automated-warnings.php?action=warnings')
                .then(response => response.json())
                .then(data => {
                    const tbody = document.querySelector('#warningsTable tbody');
                    tbody.innerHTML = '';
                    
                    if (data.success && data.warnings) {
                        data.warnings.forEach(warning => {
                            const row = document.createElement('tr');
                            row.innerHTML = `
                                <td>${warning.id}</td>
                                <td><span class="badge">${warning.source.toUpperCase()}</span></td>
                                <td>${warning.type}</td>
                                <td>${warning.title}</td>
                                <td><span class="badge ${warning.severity}">${warning.severity}</span></td>
                                <td><span class="badge ${warning.status}">${warning.status}</span></td>
                                <td>${warning.received_at}</td>
                                <td>
                                    <button class="btn btn-sm btn-primary" onclick="viewWarning(${warning.id})">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="btn btn-sm btn-success" onclick="publishWarning(${warning.id})">
                                        <i class="fas fa-paper-plane"></i>
                                    </button>
                                </td>
                            `;
                            tbody.appendChild(row);
                        });
                    }
                });
        }

        // Modal Functions
        function openIntegrationSettingsModal() {
            document.getElementById('integrationSettingsModal').style.display = 'flex';
            document.body.style.overflow = 'hidden';
            loadIntegrationSettings();
        }

        function closeIntegrationSettingsModal() {
            document.getElementById('integrationSettingsModal').style.display = 'none';
            document.body.style.overflow = '';
        }

        function openAISettingsModal() {
            document.getElementById('aiSettingsModal').style.display = 'flex';
            document.body.style.overflow = 'hidden';
            loadAISettings();
        }

        function closeAISettingsModal() {
            document.getElementById('aiSettingsModal').style.display = 'none';
            document.body.style.overflow = '';
        }

        // Close modals on Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeIntegrationSettingsModal();
                closeAISettingsModal();
            }
        });

        function loadIntegrationSettings() {
            fetch('../api/automated-warnings.php?action=getSettings')
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.settings) {
                        const settings = data.settings;
                        document.getElementById('syncInterval').value = settings.sync_interval || 15;
                        document.getElementById('autoPublish').checked = settings.auto_publish || false;
                        
                        // Set notification channels
                        if (settings.notification_channels) {
                            const channels = settings.notification_channels.split(',');
                            document.querySelectorAll('input[name="channels[]"]').forEach(checkbox => {
                                checkbox.checked = channels.includes(checkbox.value);
                            });
                        }
                    }
                })
                .catch(error => {
                    console.error('Error loading integration settings:', error);
                });
        }

        document.getElementById('integrationSettingsForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            
            fetch('../api/automated-warnings.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Settings saved successfully!');
                    closeIntegrationSettingsModal();
                } else {
                    alert('Error: ' + data.message);
                }
            });
        });

        // AI Warning Settings Form Handler
        document.getElementById('aiWarningSettingsForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            
            // Check if API key is masked (contains asterisks), if so, don't send it
            const apiKeyInput = document.getElementById('geminiApiKey');
            const apiKeyValue = apiKeyInput.value;
            if (apiKeyValue.includes('*') || apiKeyValue.length <= 4) {
                // API key is masked, remove it from form data
                formData.delete('gemini_api_key');
            }
            
            fetch('../api/ai-warnings.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    return response.text().then(text => {
                        try {
                            const json = JSON.parse(text);
                            throw new Error(json.message || `HTTP ${response.status}: ${response.statusText}`);
                        } catch (e) {
                            if (e instanceof Error && e.message.includes('HTTP')) {
                                throw e;
                            }
                            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                        }
                    });
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    alert('AI Warning Settings saved successfully!');
                    loadAISettings();
                    // Reload integration status to update Gemini status
                    setTimeout(() => {
                        loadIntegrationStatus();
                    }, 500);
                    closeAISettingsModal();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error saving AI settings:', error);
                alert('Error saving settings: ' + error.message);
            });
        });

        // Load AI Settings
        function loadAISettings() {
            fetch('../api/ai-warnings.php?action=getSettings')
                .then(response => {
                    if (!response.ok) {
                        // If response is not ok, try to get error message
                        return response.text().then(text => {
                            // Handle empty response
                            if (!text || text.trim() === '') {
                                throw new Error(`HTTP ${response.status}: ${response.statusText} - Empty response`);
                            }
                            try {
                                const json = JSON.parse(text);
                                throw new Error(json.message || `HTTP ${response.status}: ${response.statusText}`);
                            } catch (e) {
                                if (e instanceof Error && (e.message.includes('HTTP') || e.message.includes('Empty'))) {
                                    throw e;
                                }
                                // If it's not JSON, return the text as error message
                                throw new Error(`HTTP ${response.status}: ${response.statusText} - ${text.substring(0, 100)}`);
                            }
                        });
                    }
                    // Check if response has content before parsing
                    const contentType = response.headers.get('content-type');
                    if (!contentType || !contentType.includes('application/json')) {
                        return response.text().then(text => {
                            throw new Error(`Invalid response format. Expected JSON but got: ${contentType}`);
                        });
                    }
                    return response.text().then(text => {
                        if (!text || text.trim() === '') {
                            throw new Error('Empty response from server');
                        }
                        try {
                            return JSON.parse(text);
                        } catch (e) {
                            throw new Error(`Invalid JSON response: ${text.substring(0, 100)}`);
                        }
                    });
                })
                .then(data => {
                    if (data.success && data.settings) {
                        const settings = data.settings;
                        // Only set API key if it's not masked (contains asterisks)
                        const apiKeyInput = document.getElementById('geminiApiKey');
                        if (apiKeyInput) {
                            if (settings.gemini_api_key && !settings.gemini_api_key.includes('*')) {
                                apiKeyInput.value = settings.gemini_api_key;
                            } else if (settings.gemini_api_key) {
                                // Keep masked value or empty
                                apiKeyInput.value = settings.gemini_api_key;
                            } else {
                                apiKeyInput.value = '';
                            }
                        }
                        const aiEnabled = document.getElementById('aiEnabled');
                        if (aiEnabled) aiEnabled.checked = settings.ai_enabled || false;
                        const aiCheckInterval = document.getElementById('aiCheckInterval');
                        if (aiCheckInterval) aiCheckInterval.value = settings.ai_check_interval || 30;
                        const windThreshold = document.getElementById('windThreshold');
                        if (windThreshold) windThreshold.value = settings.wind_threshold || 60;
                        const rainThreshold = document.getElementById('rainThreshold');
                        if (rainThreshold) rainThreshold.value = settings.rain_threshold || 20;
                        const earthquakeThreshold = document.getElementById('earthquakeThreshold');
                        if (earthquakeThreshold) earthquakeThreshold.value = settings.earthquake_threshold || 5.0;
                        const monitoredAreas = document.getElementById('monitoredAreas');
                        if (monitoredAreas) monitoredAreas.value = settings.monitored_areas || '';
                        
                        // Set warning types
                        if (settings.warning_types) {
                            const types = settings.warning_types.split(',');
                            document.querySelectorAll('input[name="warning_types[]"]').forEach(checkbox => {
                                checkbox.checked = types.includes(checkbox.value);
                            });
                        }
                        
                        // Set channels
                        if (settings.ai_channels) {
                            const channels = settings.ai_channels.split(',');
                            document.querySelectorAll('input[name="ai_channels[]"]').forEach(checkbox => {
                                checkbox.checked = channels.includes(checkbox.value);
                            });
                        }
                        
                        // Set weather analysis settings
                        const weatherAnalysisAutoSend = document.getElementById('weatherAnalysisAutoSend');
                        if (weatherAnalysisAutoSend) weatherAnalysisAutoSend.checked = settings.weather_analysis_auto_send || false;
                        const weatherAnalysisInterval = document.getElementById('weatherAnalysisInterval');
                        if (weatherAnalysisInterval) weatherAnalysisInterval.value = settings.weather_analysis_interval || 60;
                        const weatherAnalysisVerificationKey = document.getElementById('weatherAnalysisVerificationKey');
                        if (weatherAnalysisVerificationKey) weatherAnalysisVerificationKey.value = settings.weather_analysis_verification_key || '';
                    }
                })
                .catch(error => {
                    console.error('Error loading AI settings:', error);
                    // Don't show alert on page load, just log the error
                    // The error is likely due to missing database table or API key, which is expected on first use
                });
        }

        // Test AI Warning
        function testAIWarning() {
            if (confirm('This will send a test AI warning notification. Continue?')) {
                fetch('../api/ai-warnings.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        action: 'test'
                    })
                })
                .then(response => {
                    if (!response.ok) {
                        return response.text().then(text => {
                            try {
                                const json = JSON.parse(text);
                                throw new Error(json.message || `HTTP ${response.status}: ${response.statusText}`);
                            } catch (e) {
                                if (e instanceof Error && e.message.includes('HTTP')) {
                                    throw e;
                                }
                                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                            }
                        });
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        alert('Test warning sent successfully! Check mass notifications.');
                        loadWarnings(); // Refresh warnings table
                    } else {
                        alert('Error: ' + data.message);
                    }
                });
            }
        }

        // Check AI Warnings Now
        function checkAIWarnings() {
            const btn = event.target;
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Checking...';
            btn.disabled = true;
            
            fetch('../api/ai-warnings.php?action=check')
                .then(response => {
                    if (!response.ok) {
                        return response.text().then(text => {
                            // Handle empty response
                            if (!text || text.trim() === '') {
                                throw new Error(`HTTP ${response.status}: ${response.statusText} - Empty response`);
                            }
                            try {
                                const json = JSON.parse(text);
                                throw new Error(json.message || `HTTP ${response.status}: ${response.statusText}`);
                            } catch (e) {
                                if (e instanceof Error && (e.message.includes('HTTP') || e.message.includes('Empty'))) {
                                    throw e;
                                }
                                throw new Error(`HTTP ${response.status}: ${response.statusText} - ${text.substring(0, 100)}`);
                            }
                        });
                    }
                    // Check if response has content before parsing
                    const contentType = response.headers.get('content-type');
                    if (!contentType || !contentType.includes('application/json')) {
                        return response.text().then(text => {
                            throw new Error(`Invalid response format. Expected JSON but got: ${contentType}`);
                        });
                    }
                    return response.text().then(text => {
                        if (!text || text.trim() === '') {
                            throw new Error('Empty response from server');
                        }
                        try {
                            return JSON.parse(text);
                        } catch (e) {
                            throw new Error(`Invalid JSON response: ${text.substring(0, 100)}`);
                        }
                    });
                })
                .then(data => {
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                    
                    if (data.success) {
                        const count = data.warnings_generated || 0;
                        if (count > 0) {
                            alert(`AI Warning System checked conditions and generated ${count} warning(s). Check Recent Warnings table.`);
                        } else {
                            alert('AI Warning System checked conditions. No dangerous conditions detected at this time.');
                        }
                        loadWarnings(); // Refresh warnings table
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                    alert('Error checking warnings: ' + error.message);
                });
        }

        // Load data on page load
        document.addEventListener('DOMContentLoaded', function() {
            loadIntegrationStatus();
            loadWarnings();
            loadAISettings();
        });
    </script>
</body>
</html>


