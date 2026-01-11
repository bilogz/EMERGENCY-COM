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
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const langBtn = document.getElementById('languageSelectorBtn');
            if (langBtn && window.languageSelectorModal) {
                langBtn.addEventListener('click', function() {
                    window.languageSelectorModal.open();
                });
            }
        });
    </script>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <button class="sidebar-toggle-btn" aria-label="Toggle menu" onclick="window.sidebarToggle()">
        <i class="fas fa-bars"></i>
    </button>

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
                <!-- Time & Severity Filters -->
                <div class="alert-filters" style="margin-bottom: 1rem; display: flex; gap: 0.5rem; flex-wrap: wrap; align-items: center;">
                    <span style="font-weight: 600; color: var(--text-color, #1f2937); margin-right: 0.5rem;">Time:</span>
                    <button class="filter-btn time-filter active" data-time-filter="recent">
                        <i class="fas fa-clock"></i> Recent (24h)
                    </button>
                    <button class="filter-btn time-filter" data-time-filter="older">
                        <i class="fas fa-history"></i> Older
                    </button>
                    <span style="font-weight: 600; color: var(--text-color, #1f2937); margin-left: 1rem; margin-right: 0.5rem;">Type:</span>
                    <button class="filter-btn severity-filter" data-severity-filter="emergency_only">
                        <i class="fas fa-exclamation-circle"></i> Emergency Only
                    </button>
                    <button class="filter-btn severity-filter" data-severity-filter="warnings_only">
                        <i class="fas fa-exclamation-triangle"></i> Warnings Only
                    </button>
                    <button class="filter-btn severity-filter active" data-severity-filter="all">
                        <i class="fas fa-list"></i> All Types
                    </button>
                </div>
                
                <!-- Category Filters -->
                <div class="alert-filters" style="margin-bottom: 2rem; display: flex; gap: 0.5rem; flex-wrap: wrap;">
                    <button class="filter-btn category-filter active" data-category="all">
                        <i class="fas fa-list"></i> All Categories
                    </button>
                    <button class="filter-btn category-filter" data-category="Weather">
                        <i class="fas fa-cloud-rain"></i> Weather
                    </button>
                    <button class="filter-btn category-filter" data-category="Earthquake">
                        <i class="fas fa-mountain"></i> Earthquake
                    </button>
                    <button class="filter-btn category-filter" data-category="Bomb Threat">
                        <i class="fas fa-bomb"></i> Bomb Threat
                    </button>
                    <button class="filter-btn category-filter" data-category="Fire">
                        <i class="fas fa-fire"></i> Fire
                    </button>
                    <button class="filter-btn category-filter" data-category="General">
                        <i class="fas fa-exclamation-triangle"></i> General
                    </button>
                </div>

                <!-- Live Status Indicator -->
                <div class="live-status" style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 1rem; padding: 0.75rem 1rem; background: rgba(76, 175, 80, 0.1); border-radius: 8px; border-left: 4px solid #4caf50;">
                    <span class="live-dot" style="width: 10px; height: 10px; background: #4caf50; border-radius: 50%; animation: pulse 2s infinite;"></span>
                    <span style="font-weight: 600; color: #2e7d32;">Live Updates Active</span>
                    <span id="lastUpdateTime" style="margin-left: auto; font-size: 0.875rem; color: #666;">Loading...</span>
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
        let currentTimeFilter = 'recent'; // recent, older, all
        let currentSeverityFilter = null; // null (all), emergency_only, warnings_only
        let lastAlertId = 0;
        let lastUpdateTime = null;
        let refreshInterval = null;
        let isInitialLoad = true;
        let readAlerts = new Set(); // Track read alert IDs
        let alertsCache = new Map(); // Cache alert data for quick access
        const API_BASE = window.API_BASE_PATH || 'api/';
        const REFRESH_INTERVAL = 5000; // Refresh every 5 seconds for near real-time updates
        
        // Category icons and colors mapping
        const categoryConfig = {
            'Weather': { icon: 'fa-cloud-rain', color: '#3498db', bgColor: 'rgba(52, 152, 219, 0.1)' },
            'Earthquake': { icon: 'fa-mountain', color: '#e74c3c', bgColor: 'rgba(231, 76, 60, 0.1)' },
            'Bomb Threat': { icon: 'fa-bomb', color: '#c0392b', bgColor: 'rgba(192, 57, 43, 0.1)' },
            'Fire': { icon: 'fa-fire', color: '#e67e22', bgColor: 'rgba(230, 126, 34, 0.1)' },
            'General': { icon: 'fa-exclamation-triangle', color: '#95a5a6', bgColor: 'rgba(149, 165, 166, 0.1)' }
        };
        
        document.addEventListener('DOMContentLoaded', function() {
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
                
                // Get current language preference from localStorage or detect from browser
                let currentLanguage = localStorage.getItem('preferredLanguage');
                if (!currentLanguage) {
                    // Detect browser language for guests
                    const browserLang = (navigator.language || navigator.userLanguage || 'en').toLowerCase().split('-')[0];
                    // Map common browser languages
                    const langMap = { 'en': 'en', 'fil': 'fil', 'tl': 'fil', 'es': 'es', 'fr': 'fr', 'de': 'de', 'it': 'it', 'pt': 'pt' };
                    currentLanguage = langMap[browserLang] || 'en';
                    localStorage.setItem('preferredLanguage', currentLanguage);
                }
                
                if (currentLanguage && currentLanguage !== 'en') {
                    url += `&lang=${encodeURIComponent(currentLanguage)}`;
                }
                
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
                    throw new Error('Failed to fetch alerts');
                }
                
                const data = await response.json();
                
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
                console.error('Error loading alerts:', error);
                if (!isInitialLoad) {
                    // Don't show error on refresh, just log it
                    return;
                }
                document.getElementById('alertsContainer').innerHTML = `
                    <div style="text-align: center; padding: 2rem; color: #e74c3c;">
                        <i class="fas fa-exclamation-triangle" style="font-size: 2rem; margin-bottom: 1rem;"></i>
                        <p>Failed to load alerts. Please refresh the page.</p>
                    </div>
                `;
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
            
            if (alert.category === 'Emergency Alert') {
                // EXTREME severity → red
                severityColor = '#e74c3c';
                severityBgColor = 'rgba(231, 76, 60, 0.1)';
                card.style.borderLeft = '4px solid #e74c3c';
            } else if (alert.category === 'Warning') {
                // MODERATE severity → yellow
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
            const isUrgent = alert.category === 'Emergency Alert' || ['Bomb Threat', 'Fire', 'Earthquake'].includes(alert.category_name);
            const urgencyBadge = isUrgent ? '<span class="urgent-badge" style="background: #e74c3c; color: white; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.75rem; font-weight: 700; margin-left: 0.5rem;">URGENT</span>' : '';
            
            // Read/unread indicator
            const readIndicator = isRead ? '' : '<span class="unread-indicator" style="width: 8px; height: 8px; background: #4caf50; border-radius: 50%; display: inline-block; margin-right: 0.5rem; animation: pulse 2s infinite;"></span>';
            
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
                <p style="margin: 0 0 1rem 0; color: #4b5563; line-height: 1.6;">${escapeHtml(alert.message)}</p>
                ${alert.content ? `<div class="alert-content" style="margin-bottom: 1rem; padding: 1rem; background: ${severityBgColor}; border-radius: 8px; color: #374151; border-left: 3px solid ${severityColor};">${formatAlertContent(alert.content)}</div>` : ''}
                <div class="alert-actions" style="display: flex; gap: 0.5rem;">
                    <button class="btn btn-primary btn-sm" onclick="viewAlertDetails(${alert.id})">
                        <i class="fas fa-info-circle"></i> View Details
                    </button>
                    ${isUrgent ? '<button class="btn btn-secondary btn-sm" onclick="shareAlert(' + alert.id + ')"><i class="fas fa-share"></i> Share</button>' : ''}
                </div>
            `;
            
            // Mark as read when clicked (but not on button clicks)
            card.addEventListener('click', function(e) {
                if (!e.target.closest('button')) {
                    markAlertAsRead(parseInt(alert.id));
                }
            });
            
            return card;
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
                
                // Check if line is a bullet point (starts with •, -, or *)
                if (trimmed.startsWith('•')) {
                    const content = trimmed.substring(1).trim();
                    return content ? `<div style="margin: 0.4rem 0; padding-left: 1.25rem; position: relative; line-height: 1.6;">
                        <span style="position: absolute; left: 0.5rem; color: #374151;">•</span>
                        <span>${content}</span>
                    </div>` : '';
                } else if (trimmed.startsWith('-') && trimmed.length > 1 && trimmed[1] === ' ') {
                    const content = trimmed.substring(2).trim();
                    return content ? `<div style="margin: 0.4rem 0; padding-left: 1.25rem; position: relative; line-height: 1.6;">
                        <span style="position: absolute; left: 0.5rem; color: #374151;">•</span>
                        <span>${content}</span>
                    </div>` : '';
                } else if (trimmed.startsWith('* ') && trimmed.length > 2) {
                    const content = trimmed.substring(2).trim();
                    return content ? `<div style="margin: 0.4rem 0; padding-left: 1.25rem; position: relative; line-height: 1.6;">
                        <span style="position: absolute; left: 0.5rem; color: #374151;">•</span>
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
            // Category filters
            const categoryFilters = document.querySelectorAll('.category-filter');
            categoryFilters.forEach(btn => {
                btn.addEventListener('click', function() {
                    categoryFilters.forEach(b => b.classList.remove('active'));
                    this.classList.add('active');
                    currentCategory = this.dataset.category;
                    lastAlertId = 0;
                    loadAlerts(false);
                });
            });
            
            // Time filters
            const timeFilters = document.querySelectorAll('.time-filter');
            timeFilters.forEach(btn => {
                btn.addEventListener('click', function() {
                    timeFilters.forEach(b => b.classList.remove('active'));
                    this.classList.add('active');
                    currentTimeFilter = this.dataset.timeFilter;
                    lastAlertId = 0;
                    loadAlerts(false);
                });
            });
            
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
            // Refresh every 10 seconds for real-time updates
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
        
        function showNewAlertsNotification(count) {
            // Show prominent notification for new alerts
            const notification = document.createElement('div');
            notification.style.cssText = 'position: fixed; top: 20px; right: 20px; background: linear-gradient(135deg, #4caf50 0%, #45a049 100%); color: white; padding: 1rem 1.5rem; border-radius: 12px; box-shadow: 0 8px 24px rgba(76, 175, 80, 0.4); z-index: 2000; animation: slideInRight 0.4s ease; display: flex; align-items: center; gap: 0.75rem; min-width: 250px;';
            notification.innerHTML = `
                <div style="background: rgba(255,255,255,0.2); border-radius: 50%; width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;">
                    <i class="fas fa-bell" style="font-size: 1.2rem;"></i>
                </div>
                <div>
                    <div style="font-weight: 700; font-size: 1rem;">${count} New Alert${count > 1 ? 's' : ''}</div>
                    <div style="font-size: 0.875rem; opacity: 0.9;">Real-time update from Quezon City</div>
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

