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

                    <!-- API Key Management -->
                    <div class="module-card">
                        <div class="module-card-header">
                            <h2><i class="fas fa-key"></i> API Key Management</h2>
                        </div>
                        <div>
                            <div class="setting-item">
                                <div class="setting-info">
                                    <div class="setting-title">Manage API Keys</div>
                                    <div class="setting-description">Configure and manage all API keys with OTP security and auto-rotation</div>
                                </div>
                                <button type="button" class="btn btn-primary" onclick="openApiKeyManagementModal()">
                                    <i class="fas fa-key"></i> Open API Key Management
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- API Key Management Modal -->
    <div id="apiKeyManagementModal" class="settings-modal" style="display: none;">
        <div class="modal-overlay" onclick="closeApiKeyManagementModal()"></div>
        <div class="modal-content modal-content-xlarge">
            <div class="modal-header">
                <h2><i class="fas fa-key"></i> API Key Management</h2>
                <button class="modal-close" onclick="closeApiKeyManagementModal()">&times;</button>
            </div>
            <div class="modal-body">
                <!-- Security Notice -->
                <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 1.5rem; border-radius: 8px; margin-bottom: 1.5rem; color: white;">
                    <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 0.75rem;">
                        <i class="fas fa-shield-alt" style="font-size: 2rem;"></i>
                        <div>
                            <h3 style="margin: 0; font-size: 1.1rem;">Secure API Key Management</h3>
                            <p style="margin: 0.25rem 0 0 0; opacity: 0.9; font-size: 0.9rem;">All changes require email OTP verification</p>
                        </div>
                    </div>
                </div>

                <!-- Config File Sync Info -->
                <div style="background: #e3f2fd; border-left: 4px solid #2196f3; padding: 1rem; border-radius: 4px; margin-bottom: 1.5rem;">
                    <div style="display: flex; align-items: start; gap: 0.75rem;">
                        <i class="fas fa-info-circle" style="color: #2196f3; font-size: 1.2rem; margin-top: 0.2rem;"></i>
                        <div style="flex: 1;">
                            <strong style="color: #1976d2; display: block; margin-bottom: 0.5rem;">About Config File Changes</strong>
                            <p style="margin: 0; color: #424242; font-size: 0.9rem; line-height: 1.5;">
                                <strong>Priority Order:</strong> Database → Config File → Environment Variables<br>
                                If you manually edit <code>config.local.php</code> on the server, click <strong>"Sync from Config File"</strong> 
                                to import those changes into the database. The database takes priority, so manual config edits won't be used 
                                until synced.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Key Categories -->
                <div id="apiKeysContainer" style="display: grid; gap: 1.5rem;">
                    <!-- Keys will be loaded here dynamically -->
                    <div style="text-align: center; padding: 2rem; color: var(--text-secondary-1);">
                        <i class="fas fa-spinner fa-spin" style="font-size: 2rem; margin-bottom: 1rem;"></i>
                        <p>Loading API keys...</p>
                    </div>
                </div>

                <!-- Actions -->
                <div class="form-actions" style="margin-top: 2rem; border-top: 1px solid var(--border-color-1); padding-top: 1.5rem;">
                    <button type="button" class="btn btn-secondary" onclick="closeApiKeyManagementModal()">Cancel</button>
                    <button type="button" class="btn btn-info" onclick="loadApiKeys()">
                        <i class="fas fa-sync"></i> Refresh
                    </button>
                    <button type="button" class="btn btn-warning" onclick="syncFromConfigFile()" title="Import keys from config.local.php">
                        <i class="fas fa-file-import"></i> Sync from Config File
                    </button>
                    <button type="button" class="btn btn-primary" onclick="requestOTPForKeyChange()">
                        <i class="fas fa-save"></i> Save Changes (Requires OTP)
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- OTP Verification Modal -->
    <div id="otpVerificationModal" class="settings-modal" style="display: none;">
        <div class="modal-overlay" onclick="closeOtpVerificationModal()"></div>
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-shield-alt"></i> OTP Verification Required</h2>
                <button class="modal-close" onclick="closeOtpVerificationModal()">&times;</button>
            </div>
            <div class="modal-body">
                <div style="text-align: center; margin-bottom: 1.5rem;">
                    <div style="width: 80px; height: 80px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem;">
                        <i class="fas fa-envelope" style="font-size: 2rem; color: white;"></i>
                    </div>
                    <p style="color: var(--text-color-1); font-size: 1.1rem; margin-bottom: 0.5rem;">Verification code sent to your email</p>
                    <p style="color: var(--text-secondary-1); font-size: 0.9rem;" id="otpEmailDisplay"></p>
                </div>

                <div class="form-group">
                    <label for="otpCode">Enter 6-digit OTP Code</label>
                    <input type="text" id="otpCode" maxlength="6" placeholder="000000" 
                           style="width: 100%; padding: 1rem; font-size: 1.5rem; text-align: center; letter-spacing: 0.5rem; border: 2px solid var(--border-color-1); border-radius: 8px;"
                           onkeypress="if(event.key === 'Enter') verifyOTPAndSaveKeys()">
                    <small>Check your email for the verification code. Code expires in 10 minutes.</small>
                </div>

                <div id="otpError" style="display: none; background: #ffebee; border-left: 4px solid #f44336; padding: 1rem; border-radius: 4px; margin-top: 1rem; color: #c62828;">
                    <i class="fas fa-exclamation-triangle"></i> <span id="otpErrorMessage"></span>
                </div>

                <div class="form-actions" style="margin-top: 1.5rem;">
                    <button type="button" class="btn btn-secondary" onclick="closeOtpVerificationModal()">Cancel</button>
                    <button type="button" class="btn btn-link" onclick="requestOTPForKeyChange(true)">
                        <i class="fas fa-redo"></i> Resend OTP
                    </button>
                    <button type="button" class="btn btn-primary" onclick="verifyOTPAndSaveKeys()">
                        <i class="fas fa-check"></i> Verify & Save
                    </button>
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
        });
        
        // Listen for theme changes from header
        window.addEventListener('storage', function(e) {
            if (e.key === 'theme') {
                const newTheme = e.newValue || 'light';
                updateThemeButtons(newTheme);
            }
        });

        // ===================================
        // API KEY MANAGEMENT FUNCTIONS
        // ===================================
        
        let apiKeysData = [];
        let pendingChanges = [];

        function openApiKeyManagementModal() {
            document.getElementById('apiKeyManagementModal').style.display = 'flex';
            document.body.style.overflow = 'hidden';
            loadApiKeys();
        }

        function closeApiKeyManagementModal() {
            document.getElementById('apiKeyManagementModal').style.display = 'none';
            document.body.style.overflow = '';
            pendingChanges = [];
        }

        function closeOtpVerificationModal() {
            document.getElementById('otpVerificationModal').style.display = 'none';
            document.getElementById('otpCode').value = '';
            document.getElementById('otpError').style.display = 'none';
        }

        function loadApiKeys() {
            fetch('../api/api-key-management.php?action=getKeys')
                .then(response => {
                    // Check if response is ok (status 200-299)
                    if (!response.ok) {
                        // Try to get error message from response
                        return response.text().then(text => {
                            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                        });
                    }
                    // Check content type to ensure it's JSON
                    const contentType = response.headers.get('content-type');
                    if (!contentType || !contentType.includes('application/json')) {
                        return response.text().then(text => {
                            throw new Error('Server returned non-JSON response. This might be a 404 error page.');
                        });
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        apiKeysData = data.keys;
                        displayApiKeys(data.keys, data.categories);
                    } else {
                        throw new Error(data.message || 'Failed to load API keys');
                    }
                })
                .catch(error => {
                    console.error('Error loading API keys:', error);
                    document.getElementById('apiKeysContainer').innerHTML = `
                        <div style="text-align: center; padding: 2rem; color: #f44336;">
                            <i class="fas fa-exclamation-triangle" style="font-size: 2rem; margin-bottom: 1rem;"></i>
                            <p>Error loading API keys: ${error.message}</p>
                            <p style="font-size: 0.9rem; margin-top: 0.5rem; color: var(--text-secondary-1);">
                                Please check that the API endpoint exists and is accessible.
                            </p>
                        </div>
                    `;
                });
        }

        function displayApiKeys(keys, categories) {
            const container = document.getElementById('apiKeysContainer');
            
            // Group keys by category
            const grouped = {};
            keys.forEach(key => {
                if (!grouped[key.key_category]) {
                    grouped[key.key_category] = [];
                }
                grouped[key.key_category].push(key);
            });

            let html = '';
            
            // Display each category
            Object.keys(categories).forEach(categoryKey => {
                if (grouped[categoryKey] && grouped[categoryKey].length > 0) {
                    html += `
                        <div class="category-section">
                            <div class="category-header">
                                <i class="fas fa-folder-open" style="color: var(--primary-color-1);"></i>
                                <h3>${categories[categoryKey]}</h3>
                                <span class="key-status-badge" style="margin-left: auto;">${grouped[categoryKey].length} key(s)</span>
                            </div>
                            <div style="display: grid; gap: 1rem;">
                    `;
                    
                    grouped[categoryKey].forEach(key => {
                        html += generateKeyCard(key);
                    });
                    
                    html += `
                            </div>
                        </div>
                    `;
                }
            });

            container.innerHTML = html;
        }

        function generateKeyCard(key) {
            const hasKey = key.has_key == 1;
            const statusClass = key.is_active ? 'key-status-active' : 'key-status-inactive';
            const statusText = key.is_active ? 'Active' : 'Inactive';
            
            return `
                <div class="api-key-card" data-key-name="${key.key_name}">
                    <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 1rem;">
                        <div style="flex: 1;">
                            <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 0.5rem;">
                                <h4 style="margin: 0; color: var(--text-color-1);">${key.key_label}</h4>
                                <span class="key-status-badge ${statusClass}">
                                    <i class="fas fa-circle" style="font-size: 0.5rem;"></i>
                                    ${statusText}
                                </span>
                            </div>
                            <p style="margin: 0; color: var(--text-secondary-1); font-size: 0.85rem; font-family: 'Courier New', monospace;">${key.key_name}</p>
                        </div>
                        <button type="button" class="btn btn-sm btn-info" onclick="testKey('${key.key_name}')" 
                                ${!hasKey ? 'disabled' : ''} title="Test API Key">
                            <i class="fas fa-vial"></i>
                        </button>
                    </div>

                    <div class="form-group" style="margin-bottom: 1rem;">
                        <label for="key_${key.key_name}">API Key Value</label>
                        <div style="display: flex; gap: 0.5rem;">
                            <input type="password" id="key_${key.key_name}" 
                                   class="api-key-input" 
                                   placeholder="${hasKey ? key.key_preview : 'Enter API key...'}" 
                                   value=""
                                   style="flex: 1; padding: 0.75rem; border: 1px solid var(--border-color-1); border-radius: 4px; background: var(--card-bg-1); color: var(--text-color-1);"
                                   onchange="markKeyChanged('${key.key_name}')">
                            <button type="button" class="btn btn-secondary" onclick="toggleKeyVisibility('${key.key_name}')">
                                <i class="fas fa-eye" id="eye_${key.key_name}"></i>
                            </button>
                        </div>
                        <small>Leave empty to keep current key${hasKey ? ' (' + key.key_preview + ')' : ''}</small>
                    </div>

                    <div style="display: flex; align-items: center; gap: 1rem; padding: 0.75rem; background: rgba(0,0,0,0.02); border-radius: 4px;">
                        <label style="display: flex; align-items: center; gap: 0.5rem; margin: 0; cursor: pointer;">
                            <input type="checkbox" id="autorotate_${key.key_name}" 
                                   ${key.auto_rotate ? 'checked' : ''}
                                   onchange="markKeyChanged('${key.key_name}')">
                            <span style="font-size: 0.9rem;">Enable Auto-Rotation</span>
                        </label>
                        <span style="margin-left: auto; font-size: 0.75rem; color: var(--text-secondary-1);">
                            <i class="fas fa-info-circle"></i> Automatically switch to backup when quota exceeded
                        </span>
                    </div>

                    ${key.usage_count > 0 || key.quota_exceeded_count > 0 ? `
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 0.5rem; margin-top: 1rem; padding-top: 1rem; border-top: 1px solid var(--border-color-1);">
                            <div class="stat-item">
                                <i class="fas fa-chart-line" style="color: #2196F3;"></i>
                                <span>Used: ${key.usage_count} times</span>
                            </div>
                            ${key.quota_exceeded_count > 0 ? `
                                <div class="stat-item">
                                    <i class="fas fa-exclamation-triangle" style="color: #ff9800;"></i>
                                    <span>Quota exceeded: ${key.quota_exceeded_count}x</span>
                                </div>
                            ` : ''}
                            ${key.last_used ? `
                                <div class="stat-item">
                                    <i class="fas fa-clock" style="color: #4caf50;"></i>
                                    <span>Last used: ${new Date(key.last_used).toLocaleDateString()}</span>
                                </div>
                            ` : ''}
                        </div>
                    ` : ''}
                </div>
            `;
        }

        function toggleKeyVisibility(keyName) {
            const input = document.getElementById('key_' + keyName);
            const eye = document.getElementById('eye_' + keyName);
            
            if (input.type === 'password') {
                input.type = 'text';
                eye.className = 'fas fa-eye-slash';
            } else {
                input.type = 'password';
                eye.className = 'fas fa-eye';
            }
        }

        function markKeyChanged(keyName) {
            if (!pendingChanges.includes(keyName)) {
                pendingChanges.push(keyName);
            }
        }

        function testKey(keyName) {
            const input = document.getElementById('key_' + keyName);
            const keyValue = input.value.trim();
            
            if (!keyValue) {
                alert('Please enter an API key to test');
                return;
            }

            const btn = event.target.closest('button');
            const originalHTML = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            btn.disabled = true;

            fetch('../api/api-key-management.php?action=testKey', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ key_value: keyValue })
            })
            .then(response => {
                if (!response.ok) {
                    return response.text().then(text => {
                        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                    });
                }
                const contentType = response.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    return response.text().then(text => {
                        throw new Error('Server returned non-JSON response');
                    });
                }
                return response.json();
            })
            .then(data => {
                btn.innerHTML = originalHTML;
                btn.disabled = false;
                
                if (data.success) {
                    alert('✅ API Key is valid and working!');
                } else {
                    alert('❌ API Key test failed: ' + data.message);
                }
            })
            .catch(error => {
                btn.innerHTML = originalHTML;
                btn.disabled = false;
                alert('Error testing key: ' + error.message);
            });
        }

        function requestOTPForKeyChange(isResend = false) {
            if (!isResend && pendingChanges.length === 0) {
                alert('No changes detected. Please modify at least one API key.');
                return;
            }

            const btn = event.target;
            const originalHTML = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending OTP...';
            btn.disabled = true;

            fetch('../api/api-key-management.php?action=requestOTP', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' }
            })
            .then(response => {
                if (!response.ok) {
                    return response.text().then(text => {
                        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                    });
                }
                const contentType = response.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    return response.text().then(text => {
                        throw new Error('Server returned non-JSON response');
                    });
                }
                return response.json();
            })
            .then(data => {
                btn.innerHTML = originalHTML;
                btn.disabled = false;
                
                if (data.success) {
                    // Show OTP modal
                    document.getElementById('otpVerificationModal').style.display = 'flex';
                    document.getElementById('otpEmailDisplay').textContent = '<?php echo $_SESSION['admin_email'] ?? 'your email'; ?>';
                    document.getElementById('otpCode').value = '';
                    document.getElementById('otpCode').focus();
                    
                    if (data.debug_otp) {
                        console.log('DEBUG OTP:', data.debug_otp);
                        alert('Development mode: OTP sent! Check console for debug code.');
                    } else {
                        if (!isResend) {
                            alert('✅ OTP sent to your email! Please check your inbox.');
                        } else {
                            alert('✅ New OTP sent!');
                        }
                    }
                } else {
                    alert('❌ Failed to send OTP: ' + data.message);
                }
            })
            .catch(error => {
                btn.innerHTML = originalHTML;
                btn.disabled = false;
                alert('Error: ' + error.message);
            });
        }

        function verifyOTPAndSaveKeys() {
            const otp = document.getElementById('otpCode').value.trim();
            
            if (otp.length !== 6) {
                showOtpError('Please enter a 6-digit OTP code');
                return;
            }

            const btn = event.target;
            const originalHTML = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Verifying...';
            btn.disabled = true;

            // Collect changed keys
            const keys = [];
            pendingChanges.forEach(keyName => {
                const input = document.getElementById('key_' + keyName);
                const autoRotate = document.getElementById('autorotate_' + keyName);
                const keyValue = input.value.trim();
                
                if (keyValue) { // Only include if value is provided
                    keys.push({
                        key_name: keyName,
                        key_value: keyValue,
                        auto_rotate: autoRotate.checked ? 1 : 0
                    });
                } else {
                    // Include auto-rotate change even without key change
                    const originalKey = apiKeysData.find(k => k.key_name === keyName);
                    if (originalKey && originalKey.auto_rotate != autoRotate.checked) {
                        keys.push({
                            key_name: keyName,
                            key_value: '', // Empty means keep existing
                            auto_rotate: autoRotate.checked ? 1 : 0
                        });
                    }
                }
            });

            fetch('../api/api-key-management.php?action=verifyAndSaveKeys', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    otp: otp,
                    keys: keys,
                    change_action: 'update'
                })
            })
            .then(response => {
                if (!response.ok) {
                    return response.text().then(text => {
                        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                    });
                }
                const contentType = response.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    return response.text().then(text => {
                        throw new Error('Server returned non-JSON response');
                    });
                }
                return response.json();
            })
            .then(data => {
                btn.innerHTML = originalHTML;
                btn.disabled = false;
                
                if (data.success) {
                    alert('✅ API keys updated successfully!');
                    closeOtpVerificationModal();
                    closeApiKeyManagementModal();
                    pendingChanges = [];
                } else {
                    showOtpError(data.message);
                }
            })
            .catch(error => {
                btn.innerHTML = originalHTML;
                btn.disabled = false;
                showOtpError('Error: ' + error.message);
            });
        }

        function showOtpError(message) {
            const errorDiv = document.getElementById('otpError');
            const errorMsg = document.getElementById('otpErrorMessage');
            errorMsg.textContent = message;
            errorDiv.style.display = 'block';
            
            setTimeout(() => {
                errorDiv.style.display = 'none';
            }, 5000);
        }

        function syncFromConfigFile() {
            if (!confirm('This will import all API keys from config.local.php into the database.\n\n' +
                         'Keys that exist in both will be updated.\n' +
                         'New keys from config will be added.\n\n' +
                         'Continue?')) {
                return;
            }

            const btn = event.target;
            const originalHTML = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Syncing...';
            btn.disabled = true;

            fetch('../api/api-key-management.php?action=syncFromConfig', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' }
            })
            .then(response => {
                if (!response.ok) {
                    return response.text().then(text => {
                        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                    });
                }
                const contentType = response.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    return response.text().then(text => {
                        throw new Error('Server returned non-JSON response');
                    });
                }
                return response.json();
            })
            .then(data => {
                btn.innerHTML = originalHTML;
                btn.disabled = false;
                
                if (data.success) {
                    let message = '✅ Successfully synced ' + data.total_synced + ' key(s) from config file!\n\n';
                    if (data.synced_keys.length > 0) {
                        message += 'Updated keys:\n' + data.synced_keys.join('\n');
                    }
                    if (data.skipped_keys.length > 0) {
                        message += '\n\nSkipped (unchanged):\n' + data.skipped_keys.join('\n');
                    }
                    alert(message);
                    
                    // Reload keys to show updated values
                    loadApiKeys();
                } else {
                    alert('❌ Failed to sync: ' + data.message);
                }
            })
            .catch(error => {
                btn.innerHTML = originalHTML;
                btn.disabled = false;
                alert('❌ Error syncing: ' + error.message);
            });
        }
    </script>
</body>
</html>
