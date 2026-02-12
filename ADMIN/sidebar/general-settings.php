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
    <link rel="stylesheet" href="css/global.css?v=<?php echo filemtime(__DIR__ . '/css/global.css'); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/sidebar.css?v=<?php echo filemtime(__DIR__ . '/css/sidebar.css'); ?>">
    <link rel="stylesheet" href="css/admin-header.css">
    <link rel="stylesheet" href="css/buttons.css">
    <link rel="stylesheet" href="css/forms.css">
    <link rel="stylesheet" href="css/hero.css">
    <link rel="stylesheet" href="css/sidebar-footer.css">
    <link rel="stylesheet" href="css/modules.css">
        <link rel="stylesheet" href="css/module-general-settings.css?v=<?php echo filemtime(__DIR__ . '/css/module-general-settings.css'); ?>">
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
                    const volume = soundVolume ? soundVolume.value / 100 : 0.5;
                    const type = soundFile ? soundFile.value : 'default';
                    playPreviewTone(type, volume);
                });
            }

            let _previewCtx = null;
            function playPreviewTone(type, volume) {
                try {
                    const AudioCtx = window.AudioContext || window.webkitAudioContext;
                    if (!AudioCtx) return;
                    if (!_previewCtx) _previewCtx = new AudioCtx();
                    if (_previewCtx.state === 'suspended') {
                        _previewCtx.resume();
                    }

                    const ctx = _previewCtx;
                    const gain = ctx.createGain();
                    gain.gain.value = Math.max(0, Math.min(1, volume));
                    gain.connect(ctx.destination);

                    const osc = ctx.createOscillator();
                    osc.type = 'sine';
                    osc.connect(gain);

                    const now = ctx.currentTime;
                    const sequences = {
                        default: [880, 0.12],
                        bell: [660, 0.18],
                        chime: [784, 0.12, 988, 0.12],
                        notification: [880, 0.08, 880, 0.08, 880, 0.08],
                        alert: [520, 0.12, 520, 0.12, 520, 0.12, 520, 0.12]
                    };
                    const seq = sequences[type] || sequences.default;

                    let t = now;
                    gain.gain.setValueAtTime(0, t);
                    for (let i = 0; i < seq.length; i += 2) {
                        const freq = seq[i];
                        const dur = seq[i + 1] || 0.12;
                        osc.frequency.setValueAtTime(freq, t);
                        gain.gain.setValueAtTime(Math.max(0.08, volume), t);
                        t += dur;
                        gain.gain.setValueAtTime(0, t);
                        t += 0.04;
                    }

                    osc.start(now);
                    osc.stop(t);
                } catch (e) {
                    console.warn('Sound preview unavailable:', e);
                }
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
