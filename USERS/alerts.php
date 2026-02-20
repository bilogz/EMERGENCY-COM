<?php
$assetBase = '../ADMIN/header/';
$current = 'alerts.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alerts</title>
    <link rel="icon" type="image/x-icon" href="<?= $assetBase ?>images/favicon.ico">
    <link rel="stylesheet" href="<?= $assetBase ?>css/global.css">
    <link rel="stylesheet" href="<?= $assetBase ?>css/buttons.css">
    <link rel="stylesheet" href="<?= $assetBase ?>css/hero.css">
    <link rel="stylesheet" href="../ADMIN/sidebar/css/global.css">
    <link rel="stylesheet" href="../ADMIN/sidebar/css/sidebar.css">
    <link rel="stylesheet" href="../ADMIN/sidebar/css/content.css">
    <link rel="stylesheet" href="../ADMIN/sidebar/css/admin-header.css">
    <link rel="stylesheet" href="../ADMIN/sidebar/css/buttons.css">
    <link rel="stylesheet" href="css/user.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <!-- Emergency Alert System -->
    <link rel="stylesheet" href="../ADMIN/header/css/emergency-alert.css">
    <script>
        // Set global API base path for all JS files
        window.API_BASE_PATH = 'api/';
        window.IS_ROOT_CONTEXT = false;
    </script>
    <script src="../ADMIN/header/js/emergency-alert.js"></script>
    <script src="js/translations.js"></script>
    <script src="js/language-manager.js"></script>
    <script src="js/language-selector-modal.js"></script>
    <script src="js/language-sync.js"></script>
    <script src="js/global-translator.js"></script>
    <script>
        // Ensure sidebar functions are available before translation scripts interfere
        // This runs immediately, before DOMContentLoaded
        (function() {
            if (typeof window.sidebarToggle !== 'function') {
                window.sidebarToggle = function() {
                    const sidebar = document.getElementById('sidebar');
                    const sidebarOverlay = document.getElementById('sidebarOverlay');
                    if (sidebar) {
                        sidebar.classList.toggle('sidebar-open');
                        if (sidebarOverlay) {
                            sidebarOverlay.classList.toggle('sidebar-overlay-open');
                        }
                        document.body.classList.toggle('sidebar-open');
                    }
                };
            }
            if (typeof window.sidebarClose !== 'function') {
                window.sidebarClose = function() {
                    const sidebar = document.getElementById('sidebar');
                    const sidebarOverlay = document.getElementById('sidebarOverlay');
                    if (sidebar) {
                        sidebar.classList.remove('sidebar-open');
                        if (sidebarOverlay) {
                            sidebarOverlay.classList.remove('sidebar-overlay-open');
                        }
                        document.body.classList.remove('sidebar-open');
                    }
                };
            }
        })();
        
        document.addEventListener('DOMContentLoaded', function() {
            const langBtn = document.getElementById('languageSelectorBtn');
            if (langBtn && window.languageSelectorModal) {
                langBtn.addEventListener('click', function() {
                    window.languageSelectorModal.open();
                });
            }
            
            // Verify sidebar functions are still available after translation scripts run
            if (typeof window.sidebarToggle !== 'function') {
                console.error('CRITICAL: window.sidebarToggle was removed or overwritten!');
                // Restore it
                window.sidebarToggle = function() {
                    const sidebar = document.getElementById('sidebar');
                    const sidebarOverlay = document.getElementById('sidebarOverlay');
                    if (sidebar) {
                        sidebar.classList.toggle('sidebar-open');
                        if (sidebarOverlay) {
                            sidebarOverlay.classList.toggle('sidebar-overlay-open');
                        }
                        document.body.classList.toggle('sidebar-open');
                    }
                };
            }
            
            // Protect sidebar toggle buttons from translation interference
            const toggleButtons = document.querySelectorAll('.sidebar-toggle-btn');
            toggleButtons.forEach(function(btn) {
                // Ensure onclick is set correctly
                if (!btn.getAttribute('onclick') || !btn.getAttribute('onclick').includes('sidebarToggle')) {
                    btn.setAttribute('onclick', 'window.sidebarToggle()');
                }
                // Ensure data-no-translate is set
                if (!btn.hasAttribute('data-no-translate')) {
                    btn.setAttribute('data-no-translate', '');
                }
            });
        });
    </script>
</head>
<body class="user-admin-header">
    <?php include 'includes/user-global-header.php'; ?>

    <main class="main-content">
        <div class="hero-section" id="alerts">
            <div class="main-container">
                <div class="sub-container">
                    <h1 data-translate="alerts.title">Live & Recent Alerts</h1>
                    <p data-translate="alerts.subtitle">View and respond to critical alerts with clear categories and actions.</p>
                </div>
            </div>
        </div>

        <div class="main-container">
            <div class="sub-container content-main">
                <!-- Live Status Indicator with Filters -->
                <div class="live-status" style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 1rem; padding: 0.75rem 1rem; background: rgba(76, 175, 80, 0.1); border-radius: 8px; border-left: 4px solid #4caf50; flex-wrap: wrap;">
                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                        <span class="live-dot" style="width: 10px; height: 10px; background: #4caf50; border-radius: 50%; animation: pulse 2s infinite;"></span>
                        <span style="font-weight: 600; color: #2e7d32;">Live Updates Active</span>
                    </div>
                    
                    <!-- Time Filter Dropdown -->
                    <div class="filter-dropdown" style="position: relative;">
                        <button class="filter-btn time-dropdown-toggle" id="timeDropdownToggle" data-no-translate style="padding: 0.5rem 0.75rem; font-size: 0.875rem; background: transparent; border: 1px solid rgba(76, 138, 137, 0.5); color: #2e7d32;">
                            <i class="fas fa-filter"></i> <span id="timeDropdownLabel">24 Hours Ago</span> <i class="fas fa-chevron-down" style="margin-left: 0.5rem; font-size: 0.75rem;"></i>
                        </button>
                        <div class="filter-dropdown-menu" id="timeDropdownMenu" style="display: none; position: absolute; top: 100%; right: 0; min-width: 180px; background: var(--card-bg, #fff); border: 1px solid var(--card-border, #d1d5db); border-radius: 8px; box-shadow: 0 10px 30px rgba(0,0,0,0.15); z-index: 100; margin-top: 0.25rem;">
                            <div class="filter-dropdown-item time-filter-option active" data-time-filter="24h" data-label="24 Hours Ago">
                                <i class="fas fa-clock"></i> <span>24 Hours Ago</span>
                            </div>
                            <div class="filter-dropdown-item time-filter-option" data-time-filter="week" data-label="This Week">
                                <i class="fas fa-calendar-week"></i> <span>This Week</span>
                            </div>
                            <div class="filter-dropdown-item time-filter-option" data-time-filter="month" data-label="This Month">
                                <i class="fas fa-calendar-alt"></i> <span>This Month</span>
                            </div>
                            <div class="filter-dropdown-item time-filter-option" data-time-filter="year" data-label="This Year">
                                <i class="fas fa-calendar"></i> <span>This Year</span>
                            </div>
                            <div class="filter-dropdown-item time-filter-option" data-time-filter="all" data-label="All Time">
                                <i class="fas fa-infinity"></i> <span>All Time</span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Category Filter Dropdown -->
                    <div class="filter-dropdown" style="position: relative;">
                        <button class="filter-btn category-dropdown-toggle" id="categoryDropdownToggle" data-no-translate style="padding: 0.5rem 0.75rem; font-size: 0.875rem; background: transparent; border: 1px solid rgba(76, 138, 137, 0.5); color: #2e7d32;">
                            <i class="fas fa-filter"></i> <span id="categoryDropdownLabel">All Categories</span> <i class="fas fa-chevron-down" style="margin-left: 0.5rem; font-size: 0.75rem;"></i>
                        </button>
                        <div class="filter-dropdown-menu" id="categoryDropdownMenu" style="display: none; position: absolute; top: 100%; right: 0; min-width: 180px; background: var(--card-bg, #fff); border: 1px solid var(--card-border, #d1d5db); border-radius: 8px; box-shadow: 0 10px 30px rgba(0,0,0,0.15); z-index: 100; margin-top: 0.25rem;">
                            <div class="filter-dropdown-item category-filter-option active" data-category="all" data-label="All Categories">
                                <i class="fas fa-list"></i> <span>All Categories</span>
                            </div>
                            <div class="filter-dropdown-item category-filter-option" data-category="Weather" data-label="Weather">
                                <i class="fas fa-cloud-rain"></i> <span>Weather</span>
                            </div>
                            <div class="filter-dropdown-item category-filter-option" data-category="Earthquake" data-label="Earthquake">
                                <i class="fas fa-mountain"></i> <span>Earthquake</span>
                            </div>
                            <div class="filter-dropdown-item category-filter-option" data-category="Bomb Threat" data-label="Bomb Threat">
                                <i class="fas fa-bomb"></i> <span>Bomb Threat</span>
                            </div>
                            <div class="filter-dropdown-item category-filter-option" data-category="Fire" data-label="Fire">
                                <i class="fas fa-fire"></i> <span>Fire</span>
                            </div>
                            <div class="filter-dropdown-item category-filter-option" data-category="General" data-label="General">
                                <i class="fas fa-exclamation-triangle"></i> <span>General</span>
                            </div>
                        </div>
                    </div>
                    
                    <span id="lastUpdateTime" style="font-size: 0.875rem; color: #666; margin-left: auto;">Loading...</span>
                </div>

                <section class="page-content">
                    <h2 data-translate="alerts.active.title">Active Alerts</h2>
                    <div id="alertsContainer" class="cards-grid">
                        <div class="loading-alerts" style="text-align: center; padding: 3rem;">
                            <i class="fas fa-spinner fa-spin" style="font-size: 2rem; color: #4c8a89;"></i>
                            <p style="margin-top: 1rem; color: #666;">Loading alerts...</p>
                        </div>
                    </div>
                    <div id="noAlerts" class="no-alerts" style="display: none; text-align: center; padding: 3rem;">
                        <i class="fas fa-check-circle" style="font-size: 3rem; color: #4caf50; margin-bottom: 1rem;"></i>
                        <h3>No Active Alerts</h3>
                        <p style="color: #666;">There are currently no active alerts in Quezon City.</p>
                    </div>
                </section>
            </div>
        </div>
    </main>

    <?php include 'includes/footer-snippet.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="<?= $assetBase ?>js/mobile-menu.js"></script>
    <script src="<?= $assetBase ?>js/theme-toggle.js"></script>
    <script>
        // Live Alerts System - Real-time Updates
        let currentCategory = 'all';
        let currentTimeFilter = '24h'; // 24h, week, month, year, all
        let currentSeverityFilter = null; // null (all), emergency_only, warnings_only
        let lastAlertId = 0;
        let lastUpdateTime = null;
        let refreshInterval = null;
        let isInitialLoad = true;
        let readAlerts = new Set(); // Track read alert IDs
        let alertsCache = new Map(); // Cache alert data for quick access
        let consecutiveFailures = 0; // Track consecutive API failures
        let isRetrying = false; // Prevent multiple simultaneous retries
        const API_BASE = window.API_BASE_PATH || 'api/';
        const REFRESH_INTERVAL = 5000; // Refresh every 5 seconds for near real-time updates
        const MAX_CONSECUTIVE_FAILURES = 3; // Stop retrying after 3 consecutive failures
        const BACKOFF_MULTIPLIER = 2; // Exponential backoff multiplier
        
        // Category icons and colors mapping
        const categoryConfig = {
            'Weather': { icon: 'fa-cloud-rain', color: '#3498db', bgColor: 'rgba(52, 152, 219, 0.1)' },
            'Earthquake': { icon: 'fa-mountain', color: '#e74c3c', bgColor: 'rgba(231, 76, 60, 0.1)' },
            'Bomb Threat': { icon: 'fa-bomb', color: '#c0392b', bgColor: 'rgba(192, 57, 43, 0.1)' },
            'Fire': { icon: 'fa-fire', color: '#e67e22', bgColor: 'rgba(230, 126, 34, 0.1)' },
            'General': { icon: 'fa-exclamation-triangle', color: '#95a5a6', bgColor: 'rgba(149, 165, 166, 0.1)' }
        };
        
        /**
         * Auto-detect device language and set preference if not already set
         * Returns the detected/current language code
         */
        function autoDetectDeviceLanguage() {
            // Check if user has already manually set a language preference
            const existingPreference = localStorage.getItem('preferredLanguage');
            const userSetLanguage = localStorage.getItem('user_language_set');
            
            // If user has explicitly set a language, don't auto-detect
            if (userSetLanguage === 'true' && existingPreference) {
                return existingPreference;
            }
            
            // Detect device language
            const deviceLang = navigator.language || navigator.userLanguage || 'en';
            const langCode = deviceLang.toLowerCase().split('-')[0]; // Extract base language code (e.g., "es-ES" -> "es")
            
            // Map common browser languages to supported codes
            const langMap = {
                'en': 'en',
                'fil': 'fil',
                'tl': 'fil',  // Tagalog -> Filipino
                'es': 'es',
                'fr': 'fr',
                'de': 'de',
                'it': 'it',
                'pt': 'pt',
                'ja': 'ja',
                'ko': 'ko',
                'zh': 'zh',
                'ar': 'ar',
                'hi': 'hi',
                'th': 'th',
                'vi': 'vi',
                'id': 'id',
                'ms': 'ms',
                'ru': 'ru',
                'tr': 'tr'
            };
            
            const detectedLang = langMap[langCode] || 'en';
            
            // Only auto-set if language is different from English
            if (detectedLang !== 'en') {
                // Set language preference if not already set or if it was English
                if (!existingPreference || existingPreference === 'en') {
                    localStorage.setItem('preferredLanguage', detectedLang);
                    console.log(`ðŸŒ Auto-detected device language: ${deviceLang} -> ${detectedLang}`);
                    return detectedLang;
                }
            }
            
            // Return existing preference or English
            return existingPreference || 'en';
        }
        
        document.addEventListener('DOMContentLoaded', function() {
            // Auto-detect and set language preference before initializing alerts
            const detectedLang = autoDetectDeviceLanguage();
            
            // Initialize alerts system
            initializeAlerts();
            setupFilters();
            startAutoRefresh();
            
            // Reload alerts when language changes
            document.addEventListener('languageChanged', function(event) {
                console.log('Language changed, reloading alerts with new language...');
                // Reset last alert ID so we get all alerts in the new language
                lastAlertId = 0;
                lastUpdateTime = null;
                // Reload alerts with new language
                loadAlerts(false);
            });
        });
        
        function initializeAlerts() {
            loadReadAlerts();
            loadAlerts(false);
        }
        
        async function loadAlerts(showNewOnly = false) {
            // Prevent retry loops - stop if too many consecutive failures
            if (consecutiveFailures >= MAX_CONSECUTIVE_FAILURES && showNewOnly) {
                // Silently skip refresh attempts after max failures
                return;
            }
            
            // Prevent multiple simultaneous requests
            if (isRetrying) {
                return;
            }
            
            try {
                const category = currentCategory === 'all' ? '' : currentCategory;
                let url = `${API_BASE}get-alerts.php?status=active&limit=50`;
                
                if (category) {
                    url += `&category=${encodeURIComponent(category)}`;
                }
                
                // Add time filter (only for initial loads, not incremental updates)
                if (!showNewOnly && currentTimeFilter) {
                    url += `&time_filter=${encodeURIComponent(currentTimeFilter)}`;
                }
                
                // Add severity filter (only for initial loads, not incremental updates)
                if (!showNewOnly && currentSeverityFilter && currentSeverityFilter !== 'all') {
                    url += `&severity_filter=${encodeURIComponent(currentSeverityFilter)}`;
                }
                
                // Get current language preference (from localStorage, set by autoDetectDeviceLanguage() or user selection)
                // The backend will resolve language with proper priority:
                // 1. Query parameter (UI selector from localStorage - CURRENT session selection) - this value
                // 2. Logged-in user's DB preference (persistent preference)
                // 3. Browser language detection (for guests)
                // 4. Default English
                let currentLanguage = localStorage.getItem('preferredLanguage') || 'en';
                
                // Always send language parameter so backend knows the UI selector value
                // Backend will use DB preference first for logged-in users, then this value
                url += `&lang=${encodeURIComponent(currentLanguage)}`;
                
                // For incremental updates, use last_id
                if (showNewOnly && lastAlertId > 0) {
                    url += `&last_id=${lastAlertId}`;
                } else if (showNewOnly && lastUpdateTime) {
                    url += `&last_update=${encodeURIComponent(lastUpdateTime)}`;
                }
                
                const response = await fetch(url, {
                    cache: 'no-cache',
                    headers: {
                        'Cache-Control': 'no-cache',
                        'Pragma': 'no-cache'
                    }
                });
                
                if (!response.ok) {
                    // Check if it's a server error (5xx)
                    if (response.status >= 500) {
                        consecutiveFailures++;
                        throw new Error(`Server error: ${response.status}`);
                    } else {
                        // Client errors (4xx) - don't count as consecutive failures for retry logic
                        throw new Error(`Failed to fetch alerts: ${response.status}`);
                    }
                }
                
                const data = await response.json();
                
                // Check if response indicates failure
                if (!data || (data.success === false && !data.alerts)) {
                    consecutiveFailures++;
                    throw new Error(data?.message || 'Invalid response from server');
                }
                
                // Reset failure counter on success
                consecutiveFailures = 0;
                isRetrying = false;
                
                // Debug: Log translation status
                if (data.language && data.language !== 'en') {
                    console.log(`Translation requested: ${data.language}, Applied: ${data.translation_applied || false}`);
                    if (data.debug) {
                        console.log('Translation debug info:', {
                            target_language: data.debug.target_language,
                            alerts_count: data.debug.alerts_count,
                            translation_attempted: data.debug.translation_attempted,
                            translation_success: data.debug.translation_success,
                            ai_service_available: data.debug.ai_service_available,
                            translation_helper_available: data.translation_helper_available
                        });
                    }
                    if (!data.translation_applied && data.debug) {
                        console.warn('âš ï¸ Translations not applied. Check:', {
                            ai_service_available: data.debug.ai_service_available,
                            translation_attempted: data.debug.translation_attempted,
                            translation_success: data.debug.translation_success
                        });
                    }
                }
                
                if (data.success && data.alerts) {
                    if (showNewOnly && data.alerts.length > 0) {
                        // Show notification for new alerts
                        showNewAlertsNotification(data.alerts.length);
                        // Add animation to new alerts
                        displayAlerts(data.alerts, true, true);
                    } else if (!showNewOnly) {
                        // Initial load or category change
                        displayAlerts(data.alerts, false, false);
                    }
                    
                    updateLastAlertId(data.alerts);
                    updateLastUpdateTime(data.timestamp);
                    isInitialLoad = false;
                } else if (!showNewOnly) {
                    // Only show "no alerts" on initial load, not on refresh
                    showNoAlerts();
                }
            } catch (error) {
                // Only log error once per failure sequence, not on every retry
                if (consecutiveFailures === 1 || !showNewOnly) {
                    console.error('Error loading alerts:', error);
                }
                
                // Show error only on initial load
                if (isInitialLoad) {
                    document.getElementById('alertsContainer').innerHTML = `
                        <div style="text-align: center; padding: 2rem; color: #e74c3c;">
                            <i class="fas fa-exclamation-triangle" style="font-size: 2rem; margin-bottom: 1rem;"></i>
                            <p>Failed to load alerts. Please refresh the page.</p>
                            ${consecutiveFailures >= MAX_CONSECUTIVE_FAILURES ? '<p style="font-size: 0.9rem; color: #95a5a6; margin-top: 0.5rem;">Connection issues detected. Retrying automatically...</p>' : ''}
                        </div>
                    `;
                }
                
                // If we've hit max failures, implement exponential backoff
                if (consecutiveFailures >= MAX_CONSECUTIVE_FAILURES && !isRetrying) {
                    isRetrying = true;
                    const backoffDelay = REFRESH_INTERVAL * Math.pow(BACKOFF_MULTIPLIER, consecutiveFailures - MAX_CONSECUTIVE_FAILURES);
                    const maxBackoff = 60000; // Max 60 seconds
                    const delay = Math.min(backoffDelay, maxBackoff);
                    
                    // Reset after backoff period
                    setTimeout(() => {
                        consecutiveFailures = 0;
                        isRetrying = false;
                        // Retry once after backoff
                        if (!isInitialLoad) {
                            loadAlerts(false);
                        }
                    }, delay);
                }
            }
        }
        
        function displayAlerts(alerts, append = false, isNew = false) {
            const container = document.getElementById('alertsContainer');
            const noAlerts = document.getElementById('noAlerts');
            
            if (alerts.length === 0 && !append) {
                showNoAlerts();
                return;
            }
            
            noAlerts.style.display = 'none';
            
            if (!append) {
                container.innerHTML = '';
            }
            
            alerts.forEach((alert, index) => {
                const category = alert.category_name || 'General';
                const config = categoryConfig[category] || categoryConfig['General'];
                // Cache alert data for quick access
                alertsCache.set(parseInt(alert.id), alert);
                const alertCard = createAlertCard(alert, config, isNew && index === 0);
                
                if (append) {
                    container.insertBefore(alertCard, container.firstChild);
                    // Scroll to top smoothly if new alert
                    if (isNew && index === 0) {
                        setTimeout(() => {
                            alertCard.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                        }, 100);
                    }
                } else {
                    container.appendChild(alertCard);
                }
            });
        }
        
        function createAlertCard(alert, config, isNew = false) {
            const card = document.createElement('div');
            card.className = 'card alert-card';
            card.dataset.alertId = alert.id;
            
            // Check if alert is read
            const isRead = readAlerts.has(parseInt(alert.id));
            if (!isRead) {
                card.classList.add('unread-alert');
            }
            
            // Determine severity color based on category (Emergency Alert/Warning/Advisory)
            let severityColor = config.color;
            let severityBgColor = config.bgColor || config.color + '15';
            
            const severityRaw = String(alert.severity || '').toLowerCase();
            const isCriticalSeverity = severityRaw === 'critical' || severityRaw === 'extreme';

            if (alert.category === 'Emergency Alert' || isCriticalSeverity) {
                // EXTREME severity â†’ red
                severityColor = '#e74c3c';
                severityBgColor = 'rgba(231, 76, 60, 0.1)';
                card.style.borderLeft = '4px solid #e74c3c';
            } else if (alert.category === 'Warning') {
                // MODERATE severity â†’ yellow
                severityColor = '#f39c12';
                severityBgColor = 'rgba(243, 156, 18, 0.1)';
                card.style.borderLeft = '4px solid #f39c12';
            } else {
                // Default to category color
                card.style.borderLeft = `4px solid ${config.color}`;
            }
            
            // Add new alert animation class
            if (isNew) {
                card.classList.add('new-alert');
                card.style.animation = 'slideInFromTop 0.5s ease';
            }
            
            // Determine severity/urgency based on category or severity category
            const isUrgent = isCriticalSeverity || alert.category === 'Emergency Alert' || ['Bomb Threat', 'Fire', 'Earthquake'].includes(alert.category_name);
            const urgencyBadge = isUrgent ? '<span class="urgent-badge" style="background: #e74c3c; color: white; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.75rem; font-weight: 700; margin-left: 0.5rem;">URGENT</span>' : '';
            
            // Read/unread indicator
            const readIndicator = isRead ? '' : '<span class="unread-indicator" style="width: 8px; height: 8px; background: #4caf50; border-radius: 50%; display: inline-block; margin-right: 0.5rem; animation: pulse 2s infinite;"></span>';
            
            const messageText = String(alert.message || '').trim();
            const contentText = String(alert.content || '').trim();
            const normalizedMessage = messageText.replace(/\s+/g, ' ').trim();
            const normalizedContent = contentText.replace(/\s+/g, ' ').trim();
            const isDuplicateBody = !!normalizedMessage && !!normalizedContent && normalizedMessage === normalizedContent;
            const previewRaw = messageText || contentText || '';
            const previewText = previewRaw.length > 280 ? (previewRaw.slice(0, 277) + '...') : previewRaw;

            card.innerHTML = `
                <div class="alert-header" style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 0.75rem;">
                    <div class="alert-category-badge" style="display: flex; align-items: center; gap: 0.5rem; padding: 0.5rem 1rem; background: ${severityBgColor}; border-radius: 20px; color: ${severityColor}; font-weight: 600; font-size: 0.875rem;">
                        ${readIndicator}
                        <i class="fas ${config.icon}"></i>
                        <span>${alert.category_name || 'General'}</span>
                        ${alert.category ? `<span style="margin-left: 0.5rem; font-size: 0.75rem; opacity: 0.9;">(${alert.category})</span>` : ''}
                        ${urgencyBadge}
                    </div>
                    <span class="alert-time" style="margin-left: auto; font-size: 0.875rem; color: #666; display: flex; align-items: center; gap: 0.25rem;">
                        <i class="fas fa-clock" style="font-size: 0.75rem;"></i>
                        ${alert.time_ago || 'Just now'}
                    </span>
                </div>
                <h4 style="margin: 0 0 0.5rem 0; color: #1f2937; font-size: 1.25rem; font-weight: ${isRead ? '600' : '700'};">${escapeHtml(alert.title)}</h4>
                <p style="margin: 0 0 1rem 0; color: #4b5563; line-height: 1.6;">${escapeHtml(previewText).replace(/\n/g, '<br>')}</p>
                ${(alert.content && !isDuplicateBody) ? `<div class="alert-content" style="margin-bottom: 1rem; padding: 1rem; background: ${severityBgColor}; border-radius: 8px; color: #374151; border-left: 3px solid ${severityColor};">${formatAlertContent(alert.content)}</div>` : ''}
                <div class="alert-actions" style="display: flex; gap: 0.5rem;">
                    <button class="btn btn-primary btn-sm" onclick="viewAlertDetails(${alert.id})" data-no-translate>
                        <i class="fas fa-info-circle"></i> <span>View Details</span>
                    </button>
                    ${isUrgent ? '<button class="btn btn-secondary btn-sm" onclick="shareAlert(' + alert.id + ')" data-no-translate><i class="fas fa-share"></i> <span>Share</span></button>' : ''}
                </div>
            `;
            
            // Mark as read when clicked (but not on button clicks)
            card.addEventListener('click', function(e) {
                if (!e.target.closest('button')) {
                    markAlertAsRead(parseInt(alert.id));
                }
            });
            
            // Translate alert card content if needed (client-side fallback)
            // Use setTimeout to avoid blocking and prevent recursion issues
            setTimeout(() => {
                translateAlertCard(card, alert).catch(err => {
                    console.debug(`Translation failed for alert #${alert.id}:`, err);
                });
            }, 100);
            
            return card;
        }
        
        // Track translation in progress to prevent duplicate calls
        const translatingCards = new Set();
        
        /**
         * Translate alert card content client-side (fallback if backend translation fails)
         * @param {HTMLElement} card - The alert card element
         * @param {Object} alert - The alert data object
         */
        async function translateAlertCard(card, alert) {
            const currentLang = localStorage.getItem('preferredLanguage') || 'en';
            
            // Skip if English or if translation already applied by backend
            if (currentLang === 'en' || !currentLang) {
                return;
            }
            
            // Check if card already has translated content (indicated by data attribute)
            if (card.dataset.translated === 'true') {
                return;
            }
            
            // Prevent duplicate translation calls for the same card
            const cardId = `alert-${alert.id}`;
            if (translatingCards.has(cardId)) {
                return;
            }
            translatingCards.add(cardId);
            
            // Get text elements that need translation
            const titleElement = card.querySelector('h4');
            const messageElement = card.querySelector('p');
            const contentElement = card.querySelector('.alert-content');
            
            // Store original texts if not already stored
            if (titleElement && !titleElement.dataset.originalText) {
                titleElement.dataset.originalText = titleElement.textContent.trim();
            }
            if (messageElement && !messageElement.dataset.originalText) {
                messageElement.dataset.originalText = messageElement.textContent.trim();
            }
            if (contentElement && !contentElement.dataset.originalHtml) {
                contentElement.dataset.originalHtml = contentElement.innerHTML;
            }
            
            // Check if texts are already in English (might be translated by backend)
            // If backend translation failed, we'll see English text and need to translate
            const titleText = titleElement ? titleElement.dataset.originalText : '';
            const messageText = messageElement ? messageElement.dataset.originalText : '';
            const contentHtml = contentElement ? contentElement.dataset.originalHtml : '';
            
            // Only translate if we have text to translate
            if (!titleText && !messageText && !contentHtml) {
                return;
            }
            
            try {
                // Use the translation API to translate alert content
                const textsToTranslate = {};
                if (titleText) textsToTranslate['title'] = titleText;
                if (messageText) textsToTranslate['message'] = messageText;
                if (contentHtml) {
                    // Extract text from HTML for translation
                    const tempDiv = document.createElement('div');
                    tempDiv.innerHTML = contentHtml;
                    const contentText = tempDiv.textContent || tempDiv.innerText || '';
                    if (contentText.trim()) {
                        textsToTranslate['content'] = contentText.trim();
                    }
                }
                
                if (Object.keys(textsToTranslate).length === 0) {
                    return;
                }
                
                // Call translation API
                const apiPath = getApiPathForAlerts(`api/translate-alert-text.php`);
                console.log(`ðŸ”„ Translating alert #${alert.id} to ${currentLang}...`);
                
                const response = await fetch(apiPath, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Cache-Control': 'no-cache'
                    },
                    body: JSON.stringify({
                        texts: textsToTranslate,
                        target_language: currentLang,
                        source_language: 'en'
                    })
                });
                
                // Read response body once
                const responseText = await response.text();
                
                if (response.ok) {
                    try {
                        const data = JSON.parse(responseText);
                        console.log(`ðŸ” Translation response for alert #${alert.id}:`, {
                            success: data.success,
                            translations: data.translations,
                            target_language: data.target_language,
                            errors: data.errors || null,
                            message: data.message || null
                        });
                        
                        // Log errors if any
                        if (data.errors && Object.keys(data.errors).length > 0) {
                            console.error(`âŒ Translation errors for alert #${alert.id}:`, data.errors);
                            console.error(`   Message: ${data.message || 'No details available'}`);
                        }
                        
                        if (data.translations) {
                            let translationsApplied = 0;
                            
                            // Apply translations (only if they're different from original)
                            if (titleElement && data.translations.title) {
                                const originalText = titleElement.dataset.originalText || titleElement.textContent.trim();
                                const translatedText = data.translations.title.trim();
                                if (translatedText !== originalText && translatedText.length > 0) {
                                    titleElement.textContent = translatedText;
                                    console.log(`  âœ“ Title translated: "${originalText}" â†’ "${translatedText}"`);
                                    translationsApplied++;
                                } else {
                                    console.warn(`  âš ï¸ Title translation skipped: same as original or empty`);
                                }
                            } else if (titleElement) {
                                console.warn(`  âš ï¸ Title element found but no translation for 'title' key`);
                            }
                            
                            if (messageElement && data.translations.message) {
                                const originalText = messageElement.dataset.originalText || messageElement.textContent.trim();
                                const translatedText = data.translations.message.trim();
                                if (translatedText !== originalText && translatedText.length > 0) {
                                    messageElement.textContent = translatedText;
                                    console.log(`  âœ“ Message translated: "${originalText.substring(0, 50)}..." â†’ "${translatedText.substring(0, 50)}..."`);
                                    translationsApplied++;
                                } else {
                                    console.warn(`  âš ï¸ Message translation skipped: same as original or empty`);
                                }
                            } else if (messageElement) {
                                console.warn(`  âš ï¸ Message element found but no translation for 'message' key`);
                            }
                            
                            if (contentElement && data.translations.content) {
                                // For content, we need to preserve HTML structure
                                // Simple approach: replace the text content
                                const originalHtml = contentElement.innerHTML;
                                const tempDiv = document.createElement('div');
                                tempDiv.innerHTML = contentElement.dataset.originalHtml;
                                const textNodes = [];
                                const walker = document.createTreeWalker(
                                    tempDiv,
                                    NodeFilter.SHOW_TEXT,
                                    null,
                                    false
                                );
                                let node;
                                while (node = walker.nextNode()) {
                                    if (node.textContent.trim()) {
                                        textNodes.push(node);
                                    }
                                }
                                // Replace first text node with translated content
                                if (textNodes.length > 0) {
                                    textNodes[0].textContent = data.translations.content;
                                    contentElement.innerHTML = tempDiv.innerHTML;
                                    console.log(`  âœ“ Content translated`);
                                    translationsApplied++;
                                }
                            } else if (contentElement) {
                                console.warn(`  âš ï¸ Content element found but no translation for 'content' key`);
                            }
                            
                            // Mark as translated
                            card.dataset.translated = 'true';
                            console.log(`âœ“ Translated alert card #${alert.id} to ${currentLang} (${translationsApplied} translations applied)`);
                        } else {
                            console.warn(`âš ï¸ Translation API returned success=false for alert #${alert.id}:`, data.message || 'Unknown error');
                            console.warn(`  Response data:`, data);
                        }
                    } catch (parseError) {
                        console.error(`âœ— Failed to parse translation response for alert #${alert.id}:`, parseError);
                        console.error(`  Response:`, responseText.substring(0, 200));
                    }
                } else {
                    // Handle error response
                    let errorMessage = `HTTP ${response.status}`;
                    try {
                        const errorData = JSON.parse(responseText);
                        errorMessage = errorData.message || errorData.error || errorMessage;
                        console.error(`âœ— Translation API error for alert #${alert.id}:`, errorMessage);
                        if (errorData.error) {
                            console.error(`  Details:`, errorData.error);
                        }
                        if (errorData.file && errorData.line) {
                            console.error(`  Location: ${errorData.file}:${errorData.line}`);
                        }
                    } catch (e) {
                        // If JSON parsing fails, show raw response
                        console.error(`âœ— Translation API error for alert #${alert.id}: ${errorMessage}`);
                        console.error(`  Response:`, responseText.substring(0, 300));
                    }
                }
            } catch (error) {
                // Log error but don't break the UI
                console.error(`âœ— Translation failed for alert #${alert.id}:`, error.message || error);
            } finally {
                // Remove from translating set
                translatingCards.delete(cardId);
            }
        }
        
        /**
         * Get API path helper (uses global function if available, otherwise local fallback)
         */
        function getApiPathForAlerts(relativePath) {
            // Use global getApiPath if available (from translations.js)
            if (typeof window.getApiPath === 'function') {
                return window.getApiPath(relativePath);
            }
            // Fallback path detection
            const currentPath = window.location.pathname;
            const isInUsersFolder = currentPath.includes('/USERS/');
            if (relativePath.startsWith('api/')) {
                if (!isInUsersFolder) {
                    return 'USERS/' + relativePath;
                }
            }
            return relativePath;
        }
        
        function markAlertAsRead(alertId) {
            readAlerts.add(alertId);
            // Save to localStorage for persistence
            try {
                const readArray = Array.from(readAlerts);
                localStorage.setItem('readAlerts', JSON.stringify(readArray));
            } catch (e) {
                console.error('Failed to save read alerts:', e);
            }
            
            // Update UI
            const card = document.querySelector(`[data-alert-id="${alertId}"]`);
            if (card) {
                card.classList.remove('unread-alert');
                const indicator = card.querySelector('.unread-indicator');
                if (indicator) {
                    indicator.remove();
                }
                const title = card.querySelector('h4');
                if (title) {
                    title.style.fontWeight = '600';
                }
            }
        }
        
        function loadReadAlerts() {
            try {
                const saved = localStorage.getItem('readAlerts');
                if (saved) {
                    const readArray = JSON.parse(saved).map(id => parseInt(id));
                    readAlerts = new Set(readArray);
                }
            } catch (e) {
                console.error('Failed to load read alerts:', e);
                readAlerts = new Set();
            }
        }
        
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        function formatAlertContent(text) {
            if (!text) return '';
            // Escape HTML first to prevent XSS
            const escaped = escapeHtml(text);
            // Remove literal /n or \n sequences if they appear as text
            let cleaned = escaped.replace(/\/n/g, '').replace(/\\n/g, '\n');
            // Convert newlines to <br> tags and format properly
            let formatted = cleaned.split('\n').map(line => {
                const trimmed = line.trim();
                if (!trimmed) return ''; // Skip empty lines
                
                // Check if line is a bullet point (starts with â€¢, -, or *)
                if (trimmed.startsWith('â€¢')) {
                    const content = trimmed.substring(1).trim();
                    return content ? `<div style="margin: 0.4rem 0; padding-left: 1.25rem; position: relative; line-height: 1.6;">
                        <span style="position: absolute; left: 0.5rem; color: #374151;">â€¢</span>
                        <span>${content}</span>
                    </div>` : '';
                } else if (trimmed.startsWith('-') && trimmed.length > 1 && trimmed[1] === ' ') {
                    const content = trimmed.substring(2).trim();
                    return content ? `<div style="margin: 0.4rem 0; padding-left: 1.25rem; position: relative; line-height: 1.6;">
                        <span style="position: absolute; left: 0.5rem; color: #374151;">â€¢</span>
                        <span>${content}</span>
                    </div>` : '';
                } else if (trimmed.startsWith('* ') && trimmed.length > 2) {
                    const content = trimmed.substring(2).trim();
                    return content ? `<div style="margin: 0.4rem 0; padding-left: 1.25rem; position: relative; line-height: 1.6;">
                        <span style="position: absolute; left: 0.5rem; color: #374151;">â€¢</span>
                        <span>${content}</span>
                    </div>` : '';
                } else {
                    // Regular text line
                    return `<div style="margin: 0.5rem 0; line-height: 1.6;">${trimmed}</div>`;
                }
            }).filter(line => line.length > 0).join('');
            return formatted;
        }
        
        function setupFilters() {
            // Category dropdown functionality
            const categoryDropdownToggle = document.getElementById('categoryDropdownToggle');
            const categoryDropdownMenu = document.getElementById('categoryDropdownMenu');
            const categoryDropdownLabel = document.getElementById('categoryDropdownLabel');
            const categoryFilterOptions = document.querySelectorAll('.category-filter-option');
            
            if (categoryDropdownToggle && categoryDropdownMenu) {
                // Toggle dropdown
                categoryDropdownToggle.addEventListener('click', function(e) {
                    e.stopPropagation();
                    const isOpen = categoryDropdownMenu.style.display === 'block';
                    categoryDropdownMenu.style.display = isOpen ? 'none' : 'block';
                    categoryDropdownToggle.classList.toggle('open', !isOpen);
                });
                
                // Handle option selection
                categoryFilterOptions.forEach(option => {
                    option.addEventListener('click', function(e) {
                        e.stopPropagation();
                        
                        // Update active state
                        categoryFilterOptions.forEach(opt => opt.classList.remove('active'));
                        this.classList.add('active');
                        
                        // Update filter value
                        currentCategory = this.dataset.category;
                        
                        // Update toggle button label
                        categoryDropdownLabel.textContent = this.dataset.label;
                        
                        // Close dropdown
                        categoryDropdownMenu.style.display = 'none';
                        categoryDropdownToggle.classList.remove('open');
                        
                        // Reload alerts
                        lastAlertId = 0;
                        loadAlerts(false);
                    });
                });
                
                // Close dropdown when clicking outside
                document.addEventListener('click', function() {
                    categoryDropdownMenu.style.display = 'none';
                    categoryDropdownToggle.classList.remove('open');
                });
            }
            
            // Time dropdown functionality
            const timeDropdownToggle = document.getElementById('timeDropdownToggle');
            const timeDropdownMenu = document.getElementById('timeDropdownMenu');
            const timeDropdownLabel = document.getElementById('timeDropdownLabel');
            const timeFilterOptions = document.querySelectorAll('.time-filter-option');
            
            if (timeDropdownToggle && timeDropdownMenu) {
                // Toggle dropdown
                timeDropdownToggle.addEventListener('click', function(e) {
                    e.stopPropagation();
                    const isOpen = timeDropdownMenu.style.display === 'block';
                    timeDropdownMenu.style.display = isOpen ? 'none' : 'block';
                    timeDropdownToggle.classList.toggle('open', !isOpen);
                });
                
                // Handle option selection
                timeFilterOptions.forEach(option => {
                    option.addEventListener('click', function(e) {
                        e.stopPropagation();
                        
                        // Update active state
                        timeFilterOptions.forEach(opt => opt.classList.remove('active'));
                        this.classList.add('active');
                        
                        // Update filter value
                        currentTimeFilter = this.dataset.timeFilter;
                        
                        // Update toggle button label
                        timeDropdownLabel.textContent = this.dataset.label;
                        
                        // Close dropdown
                        timeDropdownMenu.style.display = 'none';
                        timeDropdownToggle.classList.remove('open');
                        
                        // Reload alerts
                        lastAlertId = 0;
                        loadAlerts(false);
                    });
                });
                
                // Close dropdown when clicking outside
                document.addEventListener('click', function() {
                    timeDropdownMenu.style.display = 'none';
                    timeDropdownToggle.classList.remove('open');
                });
            }
            
            // Severity filters
            const severityFilters = document.querySelectorAll('.severity-filter');
            severityFilters.forEach(btn => {
                btn.addEventListener('click', function() {
                    severityFilters.forEach(b => b.classList.remove('active'));
                    this.classList.add('active');
                    currentSeverityFilter = this.dataset.severityFilter === 'all' ? null : this.dataset.severityFilter;
                    lastAlertId = 0;
                    loadAlerts(false);
                });
            });
        }
        
        function startAutoRefresh() {
            // Refresh every 5 seconds for real-time updates
            refreshInterval = setInterval(() => {
                loadAlerts(true); // Load only new alerts
            }, REFRESH_INTERVAL);
            
            // Also refresh when page becomes visible
            document.addEventListener('visibilitychange', function() {
                if (!document.hidden) {
                    loadAlerts(true);
                }
            });
            
            // Refresh on focus
            window.addEventListener('focus', function() {
                loadAlerts(true);
            });
        }
        
        function updateLastAlertId(alerts) {
            if (alerts.length > 0) {
                const maxId = Math.max(...alerts.map(a => parseInt(a.id)));
                if (maxId > lastAlertId) {
                    lastAlertId = maxId;
                }
            }
        }
        
        function updateLastUpdateTime(timestamp = null) {
            const timeEl = document.getElementById('lastUpdateTime');
            if (timeEl) {
                if (timestamp) {
                    lastUpdateTime = timestamp;
                }
                const now = new Date();
                timeEl.textContent = `Last updated: ${now.toLocaleTimeString()}`;
            }
        }
        
        function showNoAlerts() {
            document.getElementById('alertsContainer').innerHTML = '';
            document.getElementById('noAlerts').style.display = 'block';
        }
        
        function getNotificationText(count) {
            const lang = localStorage.getItem('preferredLanguage') || 'en';
            const translations = {
                'en': {
                    alert: count === 1 ? 'New Alert' : 'New Alerts',
                    update: 'Real-time update from Quezon City'
                },
                'es': {
                    alert: count === 1 ? 'Nueva Alerta' : 'Nuevas Alertas',
                    update: 'ActualizaciÃ³n en tiempo real de Quezon City'
                },
                'fil': {
                    alert: count === 1 ? 'Bagong Alert' : 'Bagong mga Alert',
                    update: 'Real-time update mula sa Quezon City'
                },
                'tl': {
                    alert: count === 1 ? 'Bagong Alert' : 'Bagong mga Alert',
                    update: 'Real-time update mula sa Quezon City'
                }
            };
            const t = translations[lang] || translations['en'];
            return {
                title: `${count} ${t.alert}`,
                subtitle: t.update
            };
        }
        
        function showNewAlertsNotification(count) {
            // Show prominent notification for new alerts
            const notification = document.createElement('div');
            notification.style.cssText = 'position: fixed; top: 20px; right: 20px; background: linear-gradient(135deg, #4caf50 0%, #45a049 100%); color: white; padding: 1rem 1.5rem; border-radius: 12px; box-shadow: 0 8px 24px rgba(76, 175, 80, 0.4); z-index: 2000; animation: slideInRight 0.4s ease; display: flex; align-items: center; gap: 0.75rem; min-width: 250px;';
            const text = getNotificationText(count);
            notification.innerHTML = `
                <div style="background: rgba(255,255,255,0.2); border-radius: 50%; width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;">
                    <i class="fas fa-bell" style="font-size: 1.2rem;"></i>
                </div>
                <div>
                    <div style="font-weight: 700; font-size: 1rem;">${text.title}</div>
                    <div style="font-size: 0.875rem; opacity: 0.9;">${text.subtitle}</div>
                </div>
            `;
            
            document.body.appendChild(notification);
            
            // Play notification sound if allowed
            try {
                const audio = new Audio('data:audio/wav;base64,UklGRnoGAABXQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YQoGAACBhYqFbF1fdJivrJBhNjVgodDbq2EcBj+a2/LDciUFLIHO8tiJNwgZaLvt559NEAxQp+PwtmMcBjiR1/LMeSwFJHfH8N2QQAoUXrTp66hVFApGn+DyvmwhBSuBzvLZiTYIG2m98OSfTQ8MUKjj8LZjHAY4kdfyzHksBSR3x/DdkEAKFF606euoVRQKRp/g8r5sIQUrgc7y2Yk2CBtpvfDkn00PDFCo4/C2YxwGOJHX8sx5LAUkd8fw3ZBAC');
                audio.volume = 0.3;
                audio.play().catch(() => {}); // Ignore errors if autoplay is blocked
            } catch (e) {}
            
            setTimeout(() => {
                notification.style.animation = 'slideOutRight 0.4s ease';
                setTimeout(() => notification.remove(), 400);
            }, 4000);
        }
        
        function viewAlertDetails(alertId) {
            // Mark as read when viewing details
            markAlertAsRead(parseInt(alertId));
            
            // Try to get alert from cache first
            const alert = alertsCache.get(parseInt(alertId));
            
            if (alert) {
                // Use cached alert data
                showAlertModal(alert);
            } else {
                // If not in cache, fetch from API
                const currentLanguage = localStorage.getItem('preferredLanguage') || 'en';
                let url = `${API_BASE}get-alerts.php?status=active&limit=50`;
                if (currentLanguage && currentLanguage !== 'en') {
                    url += `&lang=${encodeURIComponent(currentLanguage)}`;
                }
                
                fetch(url)
                    .then(res => res.json())
                    .then(data => {
                        const foundAlert = data.alerts?.find(a => parseInt(a.id) == parseInt(alertId));
                        if (foundAlert) {
                            alertsCache.set(parseInt(foundAlert.id), foundAlert);
                            showAlertModal(foundAlert);
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: 'Alert not found.'
                            });
                        }
                    })
                    .catch(() => {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Failed to load alert details.'
                        });
                    });
            }
        }
        
        function showAlertModal(alert) {
            const config = categoryConfig[alert.category_name] || categoryConfig['General'];
            Swal.fire({
                title: `<i class="fas ${config.icon}" style="color: ${config.color};"></i> ${escapeHtml(alert.title)}`,
                html: `
                    <div style="text-align: left;">
                        <div style="margin-bottom: 1rem;">
                            <strong style="color: ${config.color};">Category:</strong> ${escapeHtml(alert.category_name || 'General')}<br>
                            <strong>Time:</strong> ${escapeHtml(alert.time_ago || 'Just now')}<br>
                            <strong>Date:</strong> ${new Date(alert.created_at).toLocaleString()}
                        </div>
                        <div style="margin-bottom: 1rem; padding: 1rem; background: #f9fafb; border-radius: 8px;">
                            <strong>Message:</strong><br>
                            <div style="margin-top: 0.5rem;">${escapeHtml(alert.message)}</div>
                        </div>
                        ${alert.content ? `<div style="padding: 1rem; background: ${config.bgColor}; border-radius: 8px; border-left: 3px solid ${config.color};">
                            <strong>What to do:</strong>
                            <div style="margin-top: 0.75rem;">${formatAlertContent(alert.content)}</div>
                        </div>` : ''}
                    </div>
                `,
                icon: null,
                showConfirmButton: true,
                confirmButtonText: 'Close',
                confirmButtonColor: config.color,
                width: '700px'
            });
        }
        
        function shareAlert(alertId) {
            if (navigator.share) {
                navigator.share({
                    title: 'Quezon City Emergency Alert',
                    text: 'Check this emergency alert from Quezon City',
                    url: window.location.href
                }).catch(() => {});
            } else {
                // Fallback: copy to clipboard
                const url = window.location.href + '#alert-' + alertId;
                navigator.clipboard.writeText(url).then(() => {
                    Swal.fire({
                        icon: 'success',
                        title: 'Link Copied',
                        text: 'Alert link copied to clipboard!',
                        timer: 2000,
                        showConfirmButton: false
                    });
                });
            }
        }
        
        // Add CSS animations
        const style = document.createElement('style');
        style.textContent = `
            @keyframes pulse {
                0%, 100% { opacity: 1; }
                50% { opacity: 0.5; }
            }
            @keyframes slideIn {
                from { transform: translateX(100%); opacity: 0; }
                to { transform: translateX(0); opacity: 1; }
            }
            @keyframes slideOut {
                from { transform: translateX(0); opacity: 1; }
                to { transform: translateX(100%); opacity: 0; }
            }
            @keyframes slideInRight {
                from { transform: translateX(100%); opacity: 0; }
                to { transform: translateX(0); opacity: 1; }
            }
            @keyframes slideOutRight {
                from { transform: translateX(0); opacity: 1; }
                to { transform: translateX(100%); opacity: 0; }
            }
            @keyframes slideInFromTop {
                from { transform: translateY(-20px); opacity: 0; }
                to { transform: translateY(0); opacity: 1; }
            }
            .new-alert {
                animation: slideInFromTop 0.5s ease, pulseHighlight 2s ease;
            }
            @keyframes pulseHighlight {
                0%, 100% { box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1); }
                50% { box-shadow: 0 8px 24px rgba(76, 175, 80, 0.3); }
            }
            @keyframes pulse {
                0%, 100% { opacity: 1; transform: scale(1); }
                50% { opacity: 0.5; transform: scale(1.2); }
            }
            .live-dot {
                animation: pulse 2s infinite;
            }
            .unread-alert {
                background: linear-gradient(to right, rgba(76, 175, 80, 0.05) 0%, var(--card-bg, #ffffff) 3%);
            }
            .unread-alert:hover {
                background: linear-gradient(to right, rgba(76, 175, 80, 0.08) 0%, var(--card-bg, #ffffff) 3%);
            }
            .filter-btn {
                padding: 0.75rem 1.25rem;
                border: 2px solid var(--card-border, #e5e7eb);
                background: var(--card-bg, #ffffff);
                color: var(--text-color, #1f2937);
                border-radius: 8px;
                cursor: pointer;
                transition: all 0.3s ease;
                font-weight: 500;
                display: flex;
                align-items: center;
                gap: 0.5rem;
            }
            .filter-btn:hover {
                border-color: var(--primary-color, #4c8a89);
                background: var(--primary-color, #4c8a89);
                color: white;
            }
            .filter-btn.active {
                background: var(--primary-color, #4c8a89);
                color: white;
                border-color: var(--primary-color, #4c8a89);
            }
            .alert-card {
                transition: transform 0.2s ease, box-shadow 0.2s ease;
            }
            .alert-card:hover {
                transform: translateY(-2px);
                box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
            }
            .btn-sm {
                padding: 0.5rem 1rem;
                font-size: 0.875rem;
            }
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>


