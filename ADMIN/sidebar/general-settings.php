<?php
/**
 * General Settings Page
 * Manage system settings including dark mode
 */

// Start session and check authentication
session_start();

// Check if user is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../login.php');
    exit();
}

$pageTitle = 'General Settings';

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
    <link rel="stylesheet" href="css/hero.css">
    <link rel="stylesheet" href="css/sidebar-footer.css">
    <link rel="stylesheet" href="css/modules.css">
    <style>
        .settings-section h2 {
            margin-bottom: 1.5rem;
            color: var(--text-color-1);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .setting-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.5rem 0;
            border-bottom: 1px solid var(--border-color-1);
        }
        .setting-item:last-child {
            border-bottom: none;
        }
        .setting-info {
            flex: 1;
        }
        .setting-title {
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: var(--text-color-1);
        }
        .setting-description {
            font-size: 0.9rem;
            color: var(--text-secondary-1);
        }
        .theme-options {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }
        .theme-option {
            flex: 1;
            min-width: 120px;
            padding: 1rem;
            border: 2px solid var(--border-color-1);
            border-radius: 8px;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s;
            background: var(--card-bg-1);
        }
        .theme-option:hover {
            border-color: var(--primary-color-1);
            transform: translateY(-2px);
        }
        .theme-option.active {
            border-color: var(--primary-color-1);
            background: var(--primary-color-1);
            color: white;
        }
        .theme-option i {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
            display: block;
        }
        .switch {
            position: relative;
            display: inline-block;
            width: 60px;
            height: 34px;
        }
        .switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
            border-radius: 34px;
        }
        .slider:before {
            position: absolute;
            content: "";
            height: 26px;
            width: 26px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }
        input:checked + .slider {
            background-color: var(--primary-color-1);
        }
        input:checked + .slider:before {
            transform: translateX(26px);
        }
        .info-box {
            background: #e3f2fd;
            border-left: 4px solid #2196f3;
            padding: 1rem;
            border-radius: 4px;
            margin-bottom: 1.5rem;
        }
        [data-theme="dark"] .info-box {
            background: rgba(33, 150, 243, 0.1);
            border-left-color: #2196f3;
        }
        .info-box i {
            color: #2196f3;
            margin-right: 0.5rem;
        }
        
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

        .modal-content-xlarge {
            max-width: 1100px;
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

        .api-key-card {
            background: var(--card-bg-1);
            border: 1px solid var(--border-color-1);
            border-radius: 8px;
            padding: 1.5rem;
            transition: all 0.3s ease;
        }

        .api-key-card:hover {
            border-color: var(--primary-color-1);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .api-key-input {
            font-family: 'Courier New', monospace;
            font-size: 0.9rem;
        }

        .key-status-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            padding: 0.25rem 0.75rem;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .key-status-active {
            background: #e8f5e9;
            color: #2e7d32;
        }

        [data-theme="dark"] .key-status-active {
            background: rgba(46, 125, 50, 0.2);
            color: #81c784;
        }

        .key-status-inactive {
            background: #ffebee;
            color: #c62828;
        }

        [data-theme="dark"] .key-status-inactive {
            background: rgba(198, 40, 40, 0.2);
            color: #e57373;
        }

        .category-header {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem 1rem;
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%);
            border-radius: 8px;
            margin-bottom: 1rem;
            border-left: 4px solid var(--primary-color-1);
        }

        .category-header h3 {
            margin: 0;
            font-size: 1rem;
            color: var(--text-color-1);
        }

        .stat-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem;
            background: rgba(0, 0, 0, 0.02);
            border-radius: 4px;
            font-size: 0.85rem;
        }

        [data-theme="dark"] .stat-item {
            background: rgba(255, 255, 255, 0.02);
        }

        @media (max-width: 768px) {
            .settings-modal .modal-content {
                width: 95%;
                max-height: 95vh;
            }

            .modal-content-xlarge {
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
</head>
<body>
    <!-- Include Sidebar Component -->
    <?php include 'includes/sidebar.php'; ?>

    <!-- Include Admin Header Component -->
    <?php include 'includes/admin-header.php'; ?>
    
    <!-- ===================================
       MAIN CONTENT - General Settings
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
                            <a href="dashboard.php" class="breadcrumb-link">
                                <span>Home</span>
                            </a>
                        </li>
                        <li class="breadcrumb-item active" aria-current="page">
                            <span>General Settings</span>
                        </li>
                    </ol>
                </nav>
                <h1>General Settings</h1>
                <p>Manage your system preferences and appearance settings.</p>
            </div>
            
            <div class="sub-container">
                <div class="page-content">
                    <div class="info-box">
                        <i class="fas fa-info-circle"></i>
                        <strong>Tip:</strong> Your theme preference is saved automatically and will be remembered when you return.
                    </div>

                    <!-- Appearance Settings -->
                    <div class="module-card">
                        <div class="module-card-header">
                            <h2><i class="fas fa-palette"></i> Appearance</h2>
                        </div>
                        <div>
                            <div class="setting-item">
                                <div class="setting-info">
                                    <div class="setting-title">Theme</div>
                                    <div class="setting-description">Choose your preferred color theme. System theme follows your device settings.</div>
                                </div>
                                <div class="theme-options">
                                    <div class="theme-option" data-theme="light" onclick="setTheme('light')">
                                        <i class="fas fa-sun"></i>
                                        <div>Light</div>
                                    </div>
                                    <div class="theme-option" data-theme="dark" onclick="setTheme('dark')">
                                        <i class="fas fa-moon"></i>
                                        <div>Dark</div>
                                    </div>
                                    <div class="theme-option" data-theme="system" onclick="setTheme('system')">
                                        <i class="fas fa-desktop"></i>
                                        <div>System</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- System Settings -->
                    <div class="module-card">
                        <div class="module-card-header">
                            <h2><i class="fas fa-cog"></i> System</h2>
                        </div>
                        <div>
                            <div class="setting-item">
                                <div class="setting-info">
                                    <div class="setting-title">Auto-refresh Dashboard</div>
                                    <div class="setting-description">Automatically refresh dashboard data every 5 minutes</div>
                                </div>
                                <label class="switch">
                                    <input type="checkbox" id="autoRefresh" checked>
                                    <span class="slider"></span>
                                </label>
                            </div>

                            <div class="setting-item">
                                <div class="setting-info">
                                    <div class="setting-title">Email Notifications</div>
                                    <div class="setting-description">Receive email notifications for important system events</div>
                                </div>
                                <label class="switch">
                                    <input type="checkbox" id="emailNotifications" checked>
                                    <span class="slider"></span>
                                </label>
                            </div>

                            <div class="setting-item">
                                <div class="setting-info">
                                    <div class="setting-title">AI Analysis - Weather Monitoring</div>
                                    <div class="setting-description">Enable AI-powered weather analysis. Disable to prevent AI API usage for weather monitoring.</div>
                                </div>
                                <label class="switch">
                                    <input type="checkbox" id="aiWeatherAnalysisEnabled">
                                    <span class="slider"></span>
                                </label>
                            </div>

                            <div class="setting-item">
                                <div class="setting-info">
                                    <div class="setting-title">AI Analysis - Earthquake Monitoring</div>
                                    <div class="setting-description">Enable AI-powered earthquake analysis. Disable to prevent AI API usage for earthquake monitoring.</div>
                                </div>
                                <label class="switch">
                                    <input type="checkbox" id="aiEarthquakeAnalysisEnabled">
                                    <span class="slider"></span>
                                </label>
                            </div>

                            <div class="setting-item">
                                <div class="setting-info">
                                    <div class="setting-title">AI Analysis - Disaster Monitoring</div>
                                    <div class="setting-description">Enable AI-powered disaster monitoring analysis (automated warnings). Disable to prevent AI API usage for disaster monitoring.</div>
                                </div>
                                <label class="switch">
                                    <input type="checkbox" id="aiDisasterMonitoringEnabled">
                                    <span class="slider"></span>
                                </label>
                            </div>

                            <div class="setting-item">
                                <div class="setting-info">
                                    <div class="setting-title">AI Translation API</div>
                                    <div class="setting-description">Enable AI-powered translation service for multilingual alerts. Disable to prevent AI API usage for translations.</div>
                                </div>
                                <label class="switch">
                                    <input type="checkbox" id="aiTranslationEnabled">
                                    <span class="slider"></span>
                                </label>
                            </div>

                            <div class="setting-item">
                                <div class="setting-info">
                                    <div class="setting-title">Sound Alerts</div>
                                    <div class="setting-description">Play sound when new messages or alerts arrive</div>
                                </div>
                                <label class="switch">
                                    <input type="checkbox" id="soundAlerts">
                                    <span class="slider"></span>
                                </label>
                            </div>
                            
                            <!-- Sound Notification Customization -->
                            <div class="setting-item" id="soundCustomization" style="display: none; flex-direction: column; align-items: flex-start; gap: 1rem;">
                                <div style="width: 100%;">
                                    <label for="soundFile" style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Notification Sound:</label>
                                    <select id="soundFile" style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-color-1); border-radius: 8px; background: var(--card-bg-1);">
                                        <option value="default">Default</option>
                                        <option value="bell">Bell</option>
                                        <option value="chime">Chime</option>
                                        <option value="notification">Notification</option>
                                        <option value="alert">Alert</option>
                                    </select>
                                </div>
                                <div style="width: 100%;">
                                    <label for="soundVolume" style="display: block; margin-bottom: 0.5rem; font-weight: 600;">
                                        Volume: <span id="volumeValue">50%</span>
                                    </label>
                                    <input type="range" id="soundVolume" min="0" max="100" value="50" style="width: 100%;">
                                </div>
                                <button type="button" id="testSoundBtn" class="btn btn-secondary" style="width: 100%;">
                                    <i class="fas fa-volume-up"></i> Test Sound
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Notification Settings -->
                    <div class="module-card">
                        <div class="module-card-header">
                            <h2><i class="fas fa-bell"></i> Notifications</h2>
                        </div>
                        <div>
                            <div class="setting-item">
                                <div class="setting-info">
                                    <div class="setting-title">Show Notification Badges</div>
                                    <div class="setting-description">Display notification count badges in the header</div>
                                </div>
                                <label class="switch">
                                    <input type="checkbox" id="showBadges" checked>
                                    <span class="slider"></span>
                                </label>
                            </div>

                            <div class="setting-item">
                                <div class="setting-info">
                                    <div class="setting-title">Desktop Notifications</div>
                                    <div class="setting-description">Show browser notifications for new alerts</div>
                                </div>
                                <label class="switch">
                                    <input type="checkbox" id="desktopNotifications">
                                    <span class="slider"></span>
                                </label>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>


    <script>
        // Load saved settings
        function loadSettings() {
            const savedTheme = localStorage.getItem('theme') || 'system';
            updateThemeButtons(savedTheme);
            
            // Load other settings
            document.getElementById('autoRefresh').checked = localStorage.getItem('autoRefresh') !== 'false';
            document.getElementById('emailNotifications').checked = localStorage.getItem('emailNotifications') !== 'false';
            document.getElementById('soundAlerts').checked = localStorage.getItem('soundAlerts') === 'true';
            document.getElementById('showBadges').checked = localStorage.getItem('showBadges') !== 'false';
            document.getElementById('desktopNotifications').checked = localStorage.getItem('desktopNotifications') === 'true';
            
            // Load AI Analysis settings from server
            loadAIAnalysisSettings();
        }

        // Load AI Analysis settings from database
        function loadAIAnalysisSettings() {
            fetch('../api/ai-warnings.php?action=getSettings')
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.settings) {
                        const settings = data.settings;
                        
                        // Load weather analysis setting
                        const weatherCheckbox = document.getElementById('aiWeatherAnalysisEnabled');
                        if (weatherCheckbox) {
                            weatherCheckbox.checked = settings.ai_weather_enabled === 1 || settings.ai_weather_enabled === true || 
                                                       (settings.ai_weather_enabled === undefined && (settings.ai_enabled === 1 || settings.ai_enabled === true));
                        }
                        
                        // Load earthquake analysis setting
                        const earthquakeCheckbox = document.getElementById('aiEarthquakeAnalysisEnabled');
                        if (earthquakeCheckbox) {
                            earthquakeCheckbox.checked = settings.ai_earthquake_enabled === 1 || settings.ai_earthquake_enabled === true ||
                                                          (settings.ai_earthquake_enabled === undefined && (settings.ai_enabled === 1 || settings.ai_enabled === true));
                        }
                        
                        // Load disaster monitoring setting
                        const disasterCheckbox = document.getElementById('aiDisasterMonitoringEnabled');
                        if (disasterCheckbox) {
                            disasterCheckbox.checked = settings.ai_disaster_monitoring_enabled === 1 || settings.ai_disaster_monitoring_enabled === true ||
                                                        (settings.ai_disaster_monitoring_enabled === undefined && (settings.ai_enabled === 1 || settings.ai_enabled === true));
                        }
                        
                        // Load translation setting
                        const translationCheckbox = document.getElementById('aiTranslationEnabled');
                        if (translationCheckbox) {
                            translationCheckbox.checked = settings.ai_translation_enabled === 1 || settings.ai_translation_enabled === true ||
                                                          (settings.ai_translation_enabled === undefined && (settings.ai_enabled === 1 || settings.ai_enabled === true));
                        }
                    }
                })
                .catch(error => {
                    console.error('Error loading AI Analysis settings:', error);
                    // Default to unchecked if error
                    ['aiWeatherAnalysisEnabled', 'aiEarthquakeAnalysisEnabled', 'aiDisasterMonitoringEnabled', 'aiTranslationEnabled'].forEach(id => {
                        const checkbox = document.getElementById(id);
                        if (checkbox) {
                            checkbox.checked = false;
                        }
                    });
                });
        }

        // Save AI Analysis setting to database
        function saveAIAnalysisSetting(type, enabled) {
            fetch('../api/update-ai-analysis-setting.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ type: type, enabled: enabled })
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        console.log(`AI ${type} analysis setting updated:`, enabled ? 'enabled' : 'disabled');
                    } else {
                        console.error('Error updating AI Analysis setting:', data.message);
                        // Revert checkbox on error
                        const checkboxId = type === 'weather' ? 'aiWeatherAnalysisEnabled' : 
                                          type === 'earthquake' ? 'aiEarthquakeAnalysisEnabled' : 
                                          type === 'disaster_monitoring' ? 'aiDisasterMonitoringEnabled' :
                                          type === 'translation' ? 'aiTranslationEnabled' :
                                          'aiDisasterMonitoringEnabled';
                        const checkbox = document.getElementById(checkboxId);
                        if (checkbox) {
                            checkbox.checked = !enabled;
                        }
                    }
                })
                .catch(error => {
                    console.error('Error saving AI Analysis setting:', error);
                    // Revert checkbox on error
                    const checkboxId = type === 'weather' ? 'aiWeatherAnalysisEnabled' : 
                                      type === 'earthquake' ? 'aiEarthquakeAnalysisEnabled' : 
                                      type === 'disaster_monitoring' ? 'aiDisasterMonitoringEnabled' :
                                      type === 'translation' ? 'aiTranslationEnabled' :
                                      'aiDisasterMonitoringEnabled';
                    const checkbox = document.getElementById(checkboxId);
                    if (checkbox) {
                        checkbox.checked = !enabled;
                    }
                });
        }

        function setTheme(theme) {
            const html = document.documentElement;
            html.setAttribute('data-theme', theme);
            localStorage.setItem('theme', theme);
            updateThemeButtons(theme);
            
            // Update header buttons if they exist
            const lightBtn = document.getElementById('lightModeBtn');
            const darkBtn = document.getElementById('darkModeBtn');
            if (lightBtn && darkBtn) {
                if (theme === 'dark') {
                    lightBtn.classList.remove('active');
                    darkBtn.classList.add('active');
                } else if (theme === 'light') {
                    lightBtn.classList.add('active');
                    darkBtn.classList.remove('active');
                } else {
                    // System theme - detect current
                    const currentTheme = html.getAttribute('data-theme');
                    if (currentTheme === 'dark') {
                        lightBtn.classList.remove('active');
                        darkBtn.classList.add('active');
                    } else {
                        lightBtn.classList.add('active');
                        darkBtn.classList.remove('active');
                    }
                }
            }
            
            if (theme === 'system') {
                applySystemTheme();
            }
        }

        function updateThemeButtons(theme) {
            document.querySelectorAll('.theme-option').forEach(option => {
                option.classList.remove('active');
                if (option.getAttribute('data-theme') === theme) {
                    option.classList.add('active');
                }
            });
        }

        function applySystemTheme() {
            const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            document.documentElement.setAttribute('data-theme', prefersDark ? 'dark' : 'light');
        }

        // Listen for system theme changes
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
            if (localStorage.getItem('theme') === 'system') {
                applySystemTheme();
            }
        });

        // Listen for theme changes from header buttons
        document.addEventListener('DOMContentLoaded', function() {
            loadSettings();
            const savedTheme = localStorage.getItem('theme') || 'light';
            
            // Sync with header buttons
            const lightBtn = document.getElementById('lightModeBtn');
            const darkBtn = document.getElementById('darkModeBtn');
            if (lightBtn && darkBtn) {
                const currentTheme = document.documentElement.getAttribute('data-theme') || savedTheme;
                if (currentTheme === 'dark') {
                    lightBtn.classList.remove('active');
                    darkBtn.classList.add('active');
                } else {
                    lightBtn.classList.add('active');
                    darkBtn.classList.remove('active');
                }
            }
            
            if (savedTheme === 'system') {
                applySystemTheme();
            }
            
            // Save settings - moved inside DOMContentLoaded to ensure elements exist
            const autoRefresh = document.getElementById('autoRefresh');
            const emailNotifications = document.getElementById('emailNotifications');
            const soundAlerts = document.getElementById('soundAlerts');
            const showBadges = document.getElementById('showBadges');
            const desktopNotifications = document.getElementById('desktopNotifications');
            
            if (autoRefresh) {
                autoRefresh.addEventListener('change', function() {
                    localStorage.setItem('autoRefresh', this.checked);
                });
            }
            
            if (emailNotifications) {
                emailNotifications.addEventListener('change', function() {
                    localStorage.setItem('emailNotifications', this.checked);
                });
            }
            
            if (soundAlerts) {
                const soundCustomization = document.getElementById('soundCustomization');
                soundAlerts.addEventListener('change', function() {
                    localStorage.setItem('soundAlerts', this.checked);
                    if (soundCustomization) {
                        soundCustomization.style.display = this.checked ? 'flex' : 'none';
                    }
                });
                
                // Show customization if sound alerts is enabled
                if (soundAlerts.checked && soundCustomization) {
                    soundCustomization.style.display = 'flex';
                }
            }
            
            // Sound customization handlers
            const soundFile = document.getElementById('soundFile');
            const soundVolume = document.getElementById('soundVolume');
            const volumeValue = document.getElementById('volumeValue');
            const testSoundBtn = document.getElementById('testSoundBtn');
            
            // Load saved settings
            const chatNotificationSettings = JSON.parse(localStorage.getItem('chatNotificationSettings') || '{}');
            if (soundFile) {
                soundFile.value = chatNotificationSettings.soundFile || 'default';
            }
            if (soundVolume) {
                soundVolume.value = (chatNotificationSettings.soundVolume || 0.5) * 100;
                if (volumeValue) {
                    volumeValue.textContent = soundVolume.value + '%';
                }
            }
            
            // Update volume display
            if (soundVolume && volumeValue) {
                soundVolume.addEventListener('input', function() {
                    volumeValue.textContent = this.value + '%';
                    saveSoundSettings();
                });
            }
            
            // Save sound file selection
            if (soundFile) {
                soundFile.addEventListener('change', saveSoundSettings);
            }
            
            // Test sound button
            if (testSoundBtn) {
                testSoundBtn.addEventListener('click', function() {
                    const soundPath = `sounds/${soundFile.value}.mp3`;
                    const testSound = new Audio(soundPath);
                    testSound.volume = soundVolume.value / 100;
                    testSound.play().catch(err => {
                        // Fallback to default
                        const defaultSound = new Audio('sounds/default.mp3');
                        defaultSound.volume = soundVolume.value / 100;
                        defaultSound.play();
                    });
                });
            }
            
            function saveSoundSettings() {
                const settings = {
                    soundEnabled: document.getElementById('soundAlerts').checked,
                    soundFile: soundFile ? soundFile.value : 'default',
                    soundVolume: soundVolume ? soundVolume.value / 100 : 0.5
                };
                localStorage.setItem('chatNotificationSettings', JSON.stringify(settings));
                
                // Notify admin chat system if loaded
                if (window.adminChatFirebase) {
                    window.adminChatFirebase.updateNotificationSettings(settings);
                }
            }
            
            if (showBadges) {
                showBadges.addEventListener('change', function() {
                    localStorage.setItem('showBadges', this.checked);
                });
            }
            
            if (desktopNotifications) {
                desktopNotifications.addEventListener('change', function() {
                    localStorage.setItem('desktopNotifications', this.checked);
                });
            }
            
            // AI Analysis settings
            const aiWeatherAnalysisEnabled = document.getElementById('aiWeatherAnalysisEnabled');
            if (aiWeatherAnalysisEnabled) {
                aiWeatherAnalysisEnabled.addEventListener('change', function() {
                    saveAIAnalysisSetting('weather', this.checked);
                });
            }
            
            const aiEarthquakeAnalysisEnabled = document.getElementById('aiEarthquakeAnalysisEnabled');
            if (aiEarthquakeAnalysisEnabled) {
                aiEarthquakeAnalysisEnabled.addEventListener('change', function() {
                    saveAIAnalysisSetting('earthquake', this.checked);
                });
            }
            
            const aiDisasterMonitoringEnabled = document.getElementById('aiDisasterMonitoringEnabled');
            if (aiDisasterMonitoringEnabled) {
                aiDisasterMonitoringEnabled.addEventListener('change', function() {
                    saveAIAnalysisSetting('disaster_monitoring', this.checked);
                });
            }
            
            const aiTranslationEnabled = document.getElementById('aiTranslationEnabled');
            if (aiTranslationEnabled) {
                aiTranslationEnabled.addEventListener('change', function() {
                    saveAIAnalysisSetting('translation', this.checked);
                });
            }
        });
        
        // Listen for theme changes from header
        window.addEventListener('storage', function(e) {
            if (e.key === 'theme') {
                const newTheme = e.newValue || 'light';
                updateThemeButtons(newTheme);
            }
        });

    </script>
</body>
</html>
