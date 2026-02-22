<?php
// User-facing sidebar, modeled after the admin sidebar
// Use provided $assetSidebar if set (from root), otherwise default to relative path (from USERS)
if (!isset($assetSidebar)) {
    $assetSidebar = '../ADMIN/sidebar/';
}
// Use provided $basePath if set (from root), otherwise default to empty (from USERS)
if (!isset($basePath)) {
    $basePath = '';
}
// Detect if we're in root context (explicitly set flag from root index.php)
if (!isset($isRootContext)) {
    $isRootContext = false;
}
$linkPrefix = $isRootContext ? 'USERS/' : '';
$current = basename($_SERVER['PHP_SELF']);

// Include centralized session configuration
$sessionConfigPath = $isRootContext ? __DIR__ . '/../../session-config.php' : __DIR__ . '/../../session-config.php';
if (file_exists($sessionConfigPath)) {
    require_once $sessionConfigPath;
} else {
    // Fallback: start session manually
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}
$isLoggedIn = isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] === true;
$isRegisteredUser = isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'registered';
$showProfile = $isLoggedIn && $isRegisteredUser;

// Include guest monitoring notice
include __DIR__ . '/guest-monitoring-notice.php';
?>
<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <div class="sidebar-brand">
            <div class="brand-logo">
                <?php if ($showProfile): ?>
                    <a href="<?= $basePath ?><?= $linkPrefix ?>profile.php" class="logo-link" title="View Profile">
                        <img src="<?= $assetSidebar ?>images/logo.svg" alt="Logo" class="logo-img">
                    </a>
                <?php else: ?>
                    <a href="<?= $isRootContext ? 'index.php' : '../index.php' ?>" class="logo-link">
                        <img src="<?= $assetSidebar ?>images/logo.svg" alt="Logo" class="logo-img">
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="sidebar-content">
        <nav class="sidebar-nav">
            <div class="sidebar-section">
                <h3 class="sidebar-section-title" data-translate="sidebar.user">User</h3>
                <ul class="sidebar-menu">
                    <!-- Home -->
                    <li class="sidebar-menu-item">
                        <a href="<?= $isRootContext ? 'index.php' : '../index.php' ?>" class="sidebar-link <?= ($current === 'index.php' || $current === 'home.php') ? 'active' : '' ?>">
                            <i class="fas fa-home"></i>
                            <span data-translate="nav.home">Home</span>
                        </a>
                    </li>

                    <!-- Alerts -->
                    <li class="sidebar-menu-item">
                        <a href="<?= $basePath ?><?= $linkPrefix ?>alerts.php" class="sidebar-link <?= $current === 'alerts.php' ? 'active' : '' ?>">
                            <i class="fas fa-bell"></i>
                            <span data-translate="nav.alerts">Alerts</span>
                        </a>
                    </li>

                    <!-- Support -->
                    <li class="sidebar-menu-item">
                        <a href="<?= $basePath ?><?= $linkPrefix ?>support.php" class="sidebar-link <?= $current === 'support.php' ? 'active' : '' ?>">
                            <i class="fas fa-life-ring"></i>
                            <span data-translate="nav.support">Support</span>
                        </a>
                    </li>

                    <!-- Weather Map -->
                    <li class="sidebar-menu-item">
                        <a href="<?= $basePath ?><?= $linkPrefix ?>weather-monitoring.php" class="sidebar-link <?= $current === 'weather-monitoring.php' ? 'active' : '' ?>">
                            <i class="fas fa-map-marked-alt"></i>
                            <span data-translate="nav.weatherMap">Weather Map</span>
                        </a>
                    </li>

                    <!-- Earthquake Monitoring -->
                    <li class="sidebar-menu-item">
                        <a href="<?= $basePath ?><?= $linkPrefix ?>earthquake-monitoring.php" class="sidebar-link <?= $current === 'earthquake-monitoring.php' ? 'active' : '' ?>">
                            <i class="fas fa-mountain"></i>
                            <span data-translate="nav.earthquakeMonitoring">Earthquake Monitoring</span>
                        </a>
                    </li>

                    <!-- Profile (for logged-in registered users) -->
                    <?php if ($showProfile): ?>
                        <li class="sidebar-menu-item">
                            <a href="<?= $basePath ?><?= $linkPrefix ?>profile.php" class="sidebar-link <?= $current === 'profile.php' ? 'active' : '' ?>">
                                <i class="fas fa-user-circle"></i>
                                <span data-translate="nav.profile">Profile</span>
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>

            <div class="sidebar-section">
                <h3 class="sidebar-section-title" data-translate="sidebar.emergency">Emergency</h3>
                <ul class="sidebar-menu">
                    <li class="sidebar-menu-item">
                        <a href="<?= $basePath ?><?= $linkPrefix ?>emergency-call.php" class="sidebar-link <?= $current === 'emergency-call.php' ? 'active' : '' ?>">
                            <i class="fas fa-phone-alt"></i>
                            <span data-translate="nav.emergency">Emergency Call</span>
                        </a>
                    </li>
                </ul>
            </div>
        </nav>
    </div>
</aside>

<div class="sidebar-overlay" id="sidebarOverlay"></div>

<div class="auth-icons">
    <div class="auth-weather-pill" id="authWeatherPill" aria-live="polite">
        <div class="auth-weather-icon" id="authWeatherIcon">
            <i class="fas fa-cloud-sun"></i>
        </div>
        <div class="auth-weather-info">
            <div class="auth-weather-temp" id="authWeatherTemp">--&deg;C</div>
            <div class="auth-weather-desc" id="authWeatherDesc">Loading weather...</div>
        </div>
    </div>
    <button class="auth-icon-link" id="languageSelectorBtn" title="Change Language" aria-label="Select Language">
        <i class="fas fa-globe"></i>
    </button>
    <button class="auth-icon-link" id="chatFab" title="Any concerns? Contact support" aria-label="Open chat">
        <i class="fas fa-comments"></i>
    </button>
    <?php if ($showProfile): ?>
        <div class="user-dropdown-container">
            <button class="auth-icon-link user-dropdown-trigger" id="userDropdownBtn" title="User Menu" aria-label="User Menu" aria-expanded="false">
                <i class="fas fa-user-circle"></i>
            </button>
            <div class="user-dropdown-menu" id="userDropdownMenu" style="display: none;">
                <div class="user-dropdown-header">
                    <div class="user-icon-large">
                        <i class="fas fa-user"></i>
                    </div>
                    <div class="user-info">
                        <div class="user-name"><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'User'); ?></div>
                        <div class="user-details">
                            <?php if (!empty($_SESSION['user_email'])): ?>
                                <span><?php echo htmlspecialchars($_SESSION['user_email']); ?></span>
                            <?php endif; ?>
                            <?php if (!empty($_SESSION['user_phone'])): ?>
                                <?php if (!empty($_SESSION['user_email'])): ?> / <?php endif; ?>
                                <span><?php echo htmlspecialchars($_SESSION['user_phone']); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="user-dropdown-actions" data-no-translate>
                    <a href="<?= $basePath ?><?= $linkPrefix ?>profile.php" class="user-dropdown-link">
                        <i class="fas fa-edit"></i> <span data-translate="sidebar.editInfo">Edit Information</span>
                    </a>
                    <button class="user-dropdown-link user-logout-btn" id="userLogoutBtn">
                        <span data-translate="sidebar.logOut">Log Out</span>
                        <i class="fas fa-arrow-right"></i>
                    </button>
                </div>
            </div>
        </div>
    <?php else: ?>
        <a href="<?= $basePath ?><?= $linkPrefix ?>login.php" class="auth-icon-link" title="Login / Sign Up">
            <i class="fas fa-user-circle"></i>
        </a>
    <?php endif; ?>
</div>

<div class="chat-modal" id="chatModal" aria-hidden="true">
    <div class="chat-modal-content">
        <div class="chat-modal-header">
            <h3 data-translate="chat.title">Quick Assistance</h3>
            <button class="chat-close-btn" id="chatModalCloseBtn" aria-label="Close chat">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="chat-modal-body">
            <!-- User Info Form (shown for anonymous/guest users) -->
            <div class="chat-user-info-form" id="chatUserInfoForm" style="display: none;">
                <p class="chat-hint" style="margin-bottom: 1.25rem;" data-translate="chat.hint">Please provide your information to start chatting</p>
                <form id="userInfoForm" class="chat-form">
                    <div class="chat-form-group">
                        <label for="userNameInput"><span data-translate="chat.fullName">Full Name</span> <span class="required-asterisk">*</span></label>
                        <input type="text" id="userNameInput" name="name" required data-translate-placeholder="form.enterName" placeholder="Enter your full name" class="chat-form-input">
                    </div>
                    <div class="chat-form-group">
                        <label for="userContactInput"><span data-translate="chat.contactNumber">Contact Number</span> <span class="required-asterisk">*</span></label>
                        <input type="tel" id="userContactInput" name="contact" required data-translate-placeholder="form.enterPhone" placeholder="09XX XXX XXXX" class="chat-form-input">
                    </div>
                    <div class="chat-form-group">
                        <label for="userLocationSearch"><span data-translate="chat.location">Location</span> <span class="required-asterisk">*</span></label>
                        <div class="searchable-select-wrapper">
                            <input type="text" id="userLocationSearch" class="chat-form-input searchable-select-input" data-translate-placeholder="form.select" placeholder="Search barangay, type manually, or use map..." autocomplete="off" aria-label="Location">
                            <input type="hidden" id="userLocationInput" name="location" required>
                            <div class="searchable-select-dropdown" id="locationDropdown" style="display: none;">
                                <div class="searchable-select-list">
                                </div>
                            </div>
                        </div>
                        <div class="chat-location-tools">
                            <button type="button" class="chat-location-tool-btn" id="chatUseCurrentLocationBtn">
                                <i class="fas fa-location-crosshairs"></i> Use Current Location
                            </button>
                            <button type="button" class="chat-location-tool-btn" id="chatPinOnMapBtn">
                                <i class="fas fa-map-pin"></i> Pin on Map
                            </button>
                        </div>
                        <div class="chat-location-map-wrap" id="chatLocationMapWrap" style="display: none;">
                            <div class="chat-location-map-header">
                                <span><i class="fas fa-map-marked-alt"></i> Select Location on Map</span>
                                <button type="button" class="chat-location-tool-btn compact" id="chatHideMapBtn">
                                    Hide Map
                                </button>
                            </div>
                            <div id="chatLocationMap" class="chat-location-map"></div>
                            <small id="chatLocationMapHint" class="chat-location-map-hint">Click map to pin your location.</small>
                        </div>
                    </div>
                    <div class="chat-form-group">
                        <label for="userConcernSelect"><span data-translate="chat.concern">What is your concern?</span> <span class="required-asterisk">*</span></label>
                        <select id="userConcernSelect" name="concern" required class="chat-form-select">
                            <option value="" data-translate="chat.selectConcern">Select a concern...</option>
                            <option value="emergency" data-translate="chat.emergency">Emergency</option>
                            <option value="medical" data-translate="chat.medical">Medical Assistance</option>
                            <option value="fire" data-translate="chat.fire">Fire Emergency</option>
                            <option value="police" data-translate="chat.police">Police Assistance</option>
                            <option value="disaster" data-translate="chat.disaster">Disaster/Weather</option>
                            <option value="general" data-translate="chat.general">General Inquiry</option>
                            <option value="complaint" data-translate="chat.complaint">Complaint</option>
                            <option value="other" data-translate="chat.other">Other</option>
                        </select>
                    </div>
                    <button type="submit" class="chat-form-submit" disabled data-translate="chat.startChat">Start Chat</button>
                </form>
            </div>

            <!-- Chat Interface (shown after user info is submitted) -->
            <div class="chat-interface" id="chatInterface" style="display: none;">
                <p class="chat-hint">
                    This is a demo chat panel. Describe your emergency or question and a responder can reply using this channel.
                </p>
                <div class="chat-status-indicator" id="chatStatusIndicator" style="display: none; padding: 0.75rem 1rem; margin-bottom: 1rem; background: rgba(255, 193, 7, 0.1); border-left: 4px solid #ffc107; border-radius: 4px; color: #856404; font-size: 0.9rem;">
                    <i class="fas fa-clock"></i> <span id="chatStatusText">Waiting for admin reply...</span>
                </div>
                <div class="chat-messages" id="chatMessages">
                    <div class="chat-message chat-message-system">
                        <strong>System:</strong> For life-threatening emergencies, call 911 or the Quezon City emergency hotlines immediately.
                    </div>
                </div>
                <div class="chat-input-row" id="chatForm">
                    <input type="text" id="chatInput" placeholder="Type your message..." autocomplete="off">
                    <button type="button" id="chatSendBtn" class="btn btn-primary">Send</button>
                    <button type="button" id="chatEndConversationBtn" class="btn btn-secondary" style="margin-left: 0.5rem;" title="Close this conversation">
                        <i class="fas fa-times"></i> Close Chat
                    </button>
                    <button type="button" id="startNewConversationBtn" class="btn btn-primary" style="margin-left: 0.5rem; display: none;" title="Start a new conversation">
                        <i class="fas fa-plus"></i> Start New Conversation
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Conversation Closed Notification Modal -->
<div class="conversation-closed-modal" id="conversationClosedModal" style="display: none;">
    <div class="conversation-closed-modal-content">
        <div class="conversation-closed-modal-header">
            <div class="conversation-closed-icon">
                <i class="fas fa-info-circle"></i>
            </div>
            <h3>Conversation Closed</h3>
        </div>
        <div class="conversation-closed-modal-body">
            <p id="conversationClosedMessage">
                This conversation was closed. If there's another concern, please start a new chat.
            </p>
        </div>
        <div class="conversation-closed-modal-footer">
            <button type="button" class="conversation-closed-btn" id="conversationClosedOkBtn">OK</button>
        </div>
    </div>
</div>

<!-- Chat Notice Modal -->
<div class="chat-notice-modal" id="chatNoticeModal" style="display: none;">
    <div class="chat-notice-modal-content">
        <div class="chat-notice-modal-header">
            <div class="chat-notice-icon"><i class="fas fa-info-circle"></i></div>
            <h3 id="chatNoticeTitle">Notice</h3>
        </div>
        <div class="chat-notice-modal-body">
            <p id="chatNoticeMessage">Message</p>
        </div>
        <div class="chat-notice-modal-footer">
            <button type="button" class="chat-notice-btn" id="chatNoticeOkBtn">OK</button>
        </div>
    </div>
</div>
<!-- MySQL Chat System -->
<script>
// Determine correct path for chat-mysql.js based on current location
(function() {
    const currentPath = window.location.pathname;
    let scriptPath = '../js/chat-mysql.js'; // Default for USERS/includes/
    
    // If we're in root or different context, adjust path
    if (currentPath.includes('/index.php') || currentPath === '/' || currentPath.endsWith('/')) {
        scriptPath = 'USERS/js/chat-mysql.js';
    } else if (currentPath.includes('/USERS/')) {
        scriptPath = 'js/chat-mysql.js';
    }
    
    console.log('Loading chat-mysql.js from:', scriptPath);
    const script = document.createElement('script');
    script.src = scriptPath;
    script.onload = function() {
        console.log('chat-mysql.js loaded successfully from:', scriptPath);
        // Verify functions are available
        setTimeout(() => {
            if (window.sendChatMessageMySQL || window.initChatMySQL) {
                console.log('✓ chat-mysql.js functions are available');
            } else {
                console.warn('⚠ chat-mysql.js loaded but functions not available yet');
            }
        }, 100);
    };
    script.onerror = function() {
        console.error('Failed to load chat-mysql.js from:', scriptPath);
        // Try fallback paths
        const fallbackPaths = ['js/chat-mysql.js', '../USERS/js/chat-mysql.js', 'USERS/js/chat-mysql.js'];
        let fallbackIndex = 0;
        const tryFallback = () => {
            if (fallbackIndex < fallbackPaths.length) {
                const fallbackScript = document.createElement('script');
                fallbackScript.src = fallbackPaths[fallbackIndex];
                fallbackScript.onload = () => console.log('chat-mysql.js loaded from fallback:', fallbackPaths[fallbackIndex]);
                fallbackScript.onerror = () => {
                    fallbackIndex++;
                    tryFallback();
                };
                document.head.appendChild(fallbackScript);
            } else {
                console.error('Failed to load chat-mysql.js from all paths');
            }
        };
        tryFallback();
    };
    document.head.appendChild(script);
})();
</script>
<script>
// CRITICAL: Ensure sidebar toggle is available IMMEDIATELY (before any other scripts)
// This must run before translation scripts or any DOM manipulation
// Protect these functions from being overwritten or cleared
(function() {
    'use strict';
    
    // Create stable functions that always work by querying DOM each time
    // This ensures they work even if DOM elements are recreated or modified
    function sidebarToggleFn() {
        try {
            const sidebar = document.getElementById('sidebar');
            const sidebarOverlay = document.getElementById('sidebarOverlay');
            if (sidebar) {
                sidebar.classList.toggle('sidebar-open');
                if (sidebarOverlay) {
                    sidebarOverlay.classList.toggle('sidebar-overlay-open');
                }
                document.body.classList.toggle('sidebar-open');
            } else {
                console.warn('Sidebar element not found, retrying...');
                // Retry after a short delay in case DOM isn't ready
                setTimeout(function() {
                    const retrySidebar = document.getElementById('sidebar');
                    const retryOverlay = document.getElementById('sidebarOverlay');
                    if (retrySidebar) {
                        retrySidebar.classList.toggle('sidebar-open');
                        if (retryOverlay) {
                            retryOverlay.classList.toggle('sidebar-overlay-open');
                        }
                        document.body.classList.toggle('sidebar-open');
                    }
                }, 50);
            }
        } catch (e) {
            console.error('Error in sidebarToggle:', e);
        }
    }
    
    function sidebarCloseFn() {
        try {
            const sidebar = document.getElementById('sidebar');
            const sidebarOverlay = document.getElementById('sidebarOverlay');
            if (sidebar) {
                sidebar.classList.remove('sidebar-open');
                if (sidebarOverlay) {
                    sidebarOverlay.classList.remove('sidebar-overlay-open');
                }
                document.body.classList.remove('sidebar-open');
            }
        } catch (e) {
            console.error('Error in sidebarClose:', e);
        }
    }
    
    // Always set these functions immediately - protect them from being overwritten
    // Use Object.defineProperty to make them non-configurable and prevent deletion
    if (typeof window.sidebarToggle !== 'function') {
        window.sidebarToggle = sidebarToggleFn;
    }
    if (typeof window.sidebarClose !== 'function') {
        window.sidebarClose = sidebarCloseFn;
    }
    
    // Protect functions from being overwritten (but allow updates in DOMContentLoaded)
    // We'll re-protect them after DOMContentLoaded updates them
    Object.defineProperty(window, 'sidebarToggle', {
        value: sidebarToggleFn,
        writable: true,  // Allow updates in DOMContentLoaded
        configurable: true,  // Allow redefinition if needed
        enumerable: true
    });
    
    Object.defineProperty(window, 'sidebarClose', {
        value: sidebarCloseFn,
        writable: true,  // Allow updates in DOMContentLoaded
        configurable: true,  // Allow redefinition if needed
        enumerable: true
    });
    
    // Verify functions are set
    if (typeof window.sidebarToggle !== 'function') {
        console.error('CRITICAL: Failed to set window.sidebarToggle');
    }
    if (typeof window.sidebarClose !== 'function') {
        console.error('CRITICAL: Failed to set window.sidebarClose');
    }
})();

document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.getElementById('sidebar');
    const sidebarOverlay = document.getElementById('sidebarOverlay');
    const chatFab = document.getElementById('chatFab');
    const chatModal = document.getElementById('chatModal');
    const chatModalCloseBtn = document.getElementById('chatModalCloseBtn');
    const chatForm = document.getElementById('chatForm');
    const chatInput = document.getElementById('chatInput');
    const chatMessages = document.querySelector('.chat-messages');
    const chatNoticeModal = document.getElementById('chatNoticeModal');
    const chatNoticeTitle = document.getElementById('chatNoticeTitle');
    const chatNoticeMessage = document.getElementById('chatNoticeMessage');
    const chatNoticeOkBtn = document.getElementById('chatNoticeOkBtn');
    
    // Function to ensure chat-mysql.js is loaded
    function ensureChatScriptLoaded() {
        return new Promise((resolve) => {
            // Check if already loaded
            if (window.sendChatMessageMySQL || window.initChatMySQL) {
                console.log('chat-mysql.js already loaded');
                resolve(true);
                return;
            }
            
            // Wait a bit for script to load
            let attempts = 0;
            const checkInterval = setInterval(() => {
                attempts++;
                if (window.sendChatMessageMySQL || window.initChatMySQL) {
                    console.log('chat-mysql.js loaded after', attempts * 100, 'ms');
                    clearInterval(checkInterval);
                    resolve(true);
                } else if (attempts > 50) { // 5 seconds max
                    console.error('chat-mysql.js did not load after 5 seconds');
                    clearInterval(checkInterval);
                    resolve(false);
                }
            }, 100);
        });
    }

    function showChatNoticeModal(message, title = 'Notice') {
        if (!chatNoticeModal || !chatNoticeMessage || !chatNoticeTitle) {
            alert(message);
            return;
        }

        chatNoticeTitle.textContent = title;
        chatNoticeMessage.textContent = message;
        chatNoticeModal.style.display = 'flex';
        setTimeout(() => chatNoticeModal.classList.add('show'), 10);
    }

    function hideChatNoticeModal() {
        if (!chatNoticeModal) return;
        chatNoticeModal.classList.remove('show');
        setTimeout(() => {
            if (!chatNoticeModal.classList.contains('show')) {
                chatNoticeModal.style.display = 'none';
            }
        }, 220);
    }

    if (chatNoticeOkBtn && !chatNoticeOkBtn.hasAttribute('data-notice-bound')) {
        chatNoticeOkBtn.setAttribute('data-notice-bound', 'true');
        chatNoticeOkBtn.addEventListener('click', hideChatNoticeModal);
    }
    if (chatNoticeModal && !chatNoticeModal.hasAttribute('data-notice-backdrop-bound')) {
        chatNoticeModal.setAttribute('data-notice-backdrop-bound', 'true');
        chatNoticeModal.addEventListener('click', function(e) {
            if (e.target === chatNoticeModal) hideChatNoticeModal();
        });
    }
    function resolveUserChatApiPath(endpointFile) {
        const path = window.location.pathname || '';
        const candidates = [];

        if (path.includes('/USERS/') && !path.includes('/includes/')) {
            candidates.push(`api/${endpointFile}`);
        } else if (path.includes('/USERS/includes/')) {
            candidates.push(`../api/${endpointFile}`);
        } else {
            candidates.push(`USERS/api/${endpointFile}`);
        }

        candidates.push(`USERS/api/${endpointFile}`);
        candidates.push(`api/${endpointFile}`);
        candidates.push(`../USERS/api/${endpointFile}`);
        candidates.push(`../api/${endpointFile}`);

        const unique = [];
        candidates.forEach((c) => {
            if (!unique.includes(c)) unique.push(c);
        });
        return unique;
    }

    function resolveReverseGeocodePath() {
        const path = window.location.pathname || '';
        const candidates = [];

        if (path.includes('/USERS/') && !path.includes('/includes/')) {
            candidates.push('../ADMIN/api/reverse-geocode.php');
        } else if (path.includes('/USERS/includes/')) {
            candidates.push('../../ADMIN/api/reverse-geocode.php');
        } else {
            candidates.push('ADMIN/api/reverse-geocode.php');
        }

        candidates.push('ADMIN/api/reverse-geocode.php');
        candidates.push('../ADMIN/api/reverse-geocode.php');
        candidates.push('../../ADMIN/api/reverse-geocode.php');
        return [...new Set(candidates)];
    }

    function resolveQcGeoJsonPath() {
        const path = window.location.pathname || '';
        const candidates = [];

        if (path.includes('/USERS/') && !path.includes('/includes/')) {
            candidates.push('../ADMIN/api/quezon-city.geojson');
        } else if (path.includes('/USERS/includes/')) {
            candidates.push('../../ADMIN/api/quezon-city.geojson');
        } else {
            candidates.push('ADMIN/api/quezon-city.geojson');
        }

        candidates.push('ADMIN/api/quezon-city.geojson');
        candidates.push('../ADMIN/api/quezon-city.geojson');
        candidates.push('../../ADMIN/api/quezon-city.geojson');
        return [...new Set(candidates)];
    }

    let chatLocationMap = null;
    let chatLocationMarker = null;
    let chatLeafletLoadingPromise = null;
    let chatQcGeoJsonData = null;
    let chatQcBoundaryLayer = null;
    let chatQcBounds = null;

    async function ensureLeafletLoaded() {
        if (window.L && typeof window.L.map === 'function') {
            return true;
        }

        if (chatLeafletLoadingPromise) {
            return chatLeafletLoadingPromise;
        }

        chatLeafletLoadingPromise = new Promise((resolve, reject) => {
            const cssHref = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
            if (!document.querySelector(`link[href="${cssHref}"]`)) {
                const link = document.createElement('link');
                link.rel = 'stylesheet';
                link.href = cssHref;
                document.head.appendChild(link);
            }

            const existingScript = document.querySelector('script[data-chat-leaflet="true"]');
            if (existingScript) {
                const waiter = setInterval(() => {
                    if (window.L && typeof window.L.map === 'function') {
                        clearInterval(waiter);
                        resolve(true);
                    }
                }, 100);
                setTimeout(() => {
                    clearInterval(waiter);
                    if (window.L && typeof window.L.map === 'function') {
                        resolve(true);
                    } else {
                        reject(new Error('Leaflet failed to initialize.'));
                    }
                }, 7000);
                return;
            }

            const script = document.createElement('script');
            script.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
            script.dataset.chatLeaflet = 'true';
            script.onload = () => resolve(true);
            script.onerror = () => reject(new Error('Unable to load Leaflet library.'));
            document.head.appendChild(script);
        });

        return chatLeafletLoadingPromise;
    }

    function setUserLocationValue(locationText) {
        const locationSearch = document.getElementById('userLocationSearch');
        const locationInput = document.getElementById('userLocationInput');
        if (locationSearch) locationSearch.value = locationText || '';
        if (locationInput) {
            locationInput.value = locationText || '';
            locationInput.dispatchEvent(new Event('change', { bubbles: true }));
        }
    }

    async function reverseGeocodeLocation(lat, lng) {
        const paths = resolveReverseGeocodePath();
        let lastError = null;

        for (const path of paths) {
            try {
                const res = await fetch(`${path}?lat=${encodeURIComponent(lat)}&lng=${encodeURIComponent(lng)}`);
                const data = await res.json();
                if (res.ok && data && data.success && data.address) {
                    return data.address;
                }
                lastError = new Error(data && data.error ? data.error : `HTTP ${res.status}`);
            } catch (err) {
                lastError = err;
            }
        }

        throw lastError || new Error('Reverse geocoding failed.');
    }

    async function loadQcGeoJson() {
        if (chatQcGeoJsonData) return chatQcGeoJsonData;

        const paths = resolveQcGeoJsonPath();
        let lastError = null;
        for (const path of paths) {
            try {
                const res = await fetch(path);
                const data = await res.json();
                if (res.ok && data && (data.type === 'FeatureCollection' || data.type === 'Feature')) {
                    chatQcGeoJsonData = data;
                    return data;
                }
                lastError = new Error(`Failed to load QC geojson from ${path}`);
            } catch (err) {
                lastError = err;
            }
        }
        throw lastError || new Error('QC GeoJSON unavailable.');
    }

    function pointInRing(lng, lat, ringCoords) {
        let inside = false;
        for (let i = 0, j = ringCoords.length - 1; i < ringCoords.length; j = i++) {
            const xi = ringCoords[i][0], yi = ringCoords[i][1];
            const xj = ringCoords[j][0], yj = ringCoords[j][1];
            const intersects = ((yi > lat) !== (yj > lat)) &&
                (lng < (xj - xi) * (lat - yi) / ((yj - yi) || 1e-12) + xi);
            if (intersects) inside = !inside;
        }
        return inside;
    }

    function pointInPolygonCoords(lng, lat, polygonCoords) {
        if (!polygonCoords || !polygonCoords.length) return false;
        if (!pointInRing(lng, lat, polygonCoords[0])) return false;
        for (let i = 1; i < polygonCoords.length; i++) {
            if (pointInRing(lng, lat, polygonCoords[i])) return false;
        }
        return true;
    }

    function pointInGeoJson(lng, lat, geojson) {
        if (!geojson) return false;
        const features = geojson.type === 'FeatureCollection' ? geojson.features : [geojson];

        for (const feature of features) {
            if (!feature || !feature.geometry) continue;
            const geom = feature.geometry;

            if (geom.type === 'Polygon' && pointInPolygonCoords(lng, lat, geom.coordinates)) {
                return true;
            }

            if (geom.type === 'MultiPolygon') {
                for (const poly of geom.coordinates) {
                    if (pointInPolygonCoords(lng, lat, poly)) return true;
                }
            }
        }
        return false;
    }

    function isPointInsideQc(lat, lng) {
        if (!chatQcGeoJsonData) return true;
        return pointInGeoJson(lng, lat, chatQcGeoJsonData);
    }

    function applyPinnedLocation(lat, lng, addressText = '') {
        const mapHint = document.getElementById('chatLocationMapHint');
        const locationText = addressText && addressText.trim() !== ''
            ? addressText
            : `Pinned location (${Number(lat).toFixed(5)}, ${Number(lng).toFixed(5)})`;

        setUserLocationValue(locationText);

        if (mapHint) {
            mapHint.textContent = `Pinned: ${locationText}`;
        }
    }

    async function initChatLocationMap() {
        const mapWrap = document.getElementById('chatLocationMapWrap');
        const mapEl = document.getElementById('chatLocationMap');
        if (!mapWrap || !mapEl) return;

        mapWrap.style.display = 'block';
        await ensureLeafletLoaded();

        if (!chatLocationMap) {
            chatLocationMap = L.map('chatLocationMap').setView([14.676, 121.0437], 12);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '� OpenStreetMap contributors',
                maxZoom: 19
                        }).addTo(chatLocationMap);

            try {
                const qcGeoJson = await loadQcGeoJson();
                chatQcBoundaryLayer = L.geoJSON(qcGeoJson, {
                    style: function() {
                        return {
                            color: '#0ea5e9',
                            weight: 2,
                            opacity: 0.95,
                            fillColor: '#7dd3fc',
                            fillOpacity: 0.12
                        };
                    }
                }).addTo(chatLocationMap);

                chatQcBounds = chatQcBoundaryLayer.getBounds();
                if (chatQcBounds && chatQcBounds.isValid()) {
                    chatLocationMap.fitBounds(chatQcBounds, { padding: [16, 16] });
                    chatLocationMap.setMaxBounds(chatQcBounds.pad(0.12));
                    chatLocationMap.setMinZoom(11);
                }
            } catch (qcErr) {
                console.warn('QC GeoJSON not loaded for chat map:', qcErr);
            }

            chatLocationMap.on('click', async function(e) {
                const { lat, lng } = e.latlng;
                if (!isPointInsideQc(lat, lng)) {
                    const mapHint = document.getElementById('chatLocationMapHint');
                    if (mapHint) {
                        mapHint.textContent = 'Please pin a location inside Quezon City only.';
                    }
                    return;
                }

                if (!chatLocationMarker) {
                    chatLocationMarker = L.marker([lat, lng], { draggable: true }).addTo(chatLocationMap);
                    chatLocationMarker.on('dragend', async function(evt) {
                        const markerPos = evt.target.getLatLng();
                        if (!isPointInsideQc(markerPos.lat, markerPos.lng)) {
                            const fallback = chatQcBounds && chatQcBounds.isValid()
                                ? chatQcBounds.getCenter()
                                : { lat: 14.676, lng: 121.0437 };
                            evt.target.setLatLng([fallback.lat, fallback.lng]);
                            const mapHint = document.getElementById('chatLocationMapHint');
                            if (mapHint) {
                                mapHint.textContent = 'Pinned location must stay within Quezon City.';
                            }
                            return;
                        }
                        try {
                            const address = await reverseGeocodeLocation(markerPos.lat, markerPos.lng);
                            applyPinnedLocation(markerPos.lat, markerPos.lng, address);
                        } catch (err) {
                            applyPinnedLocation(markerPos.lat, markerPos.lng);
                        }
                    });
                } else {
                    chatLocationMarker.setLatLng([lat, lng]);
                }

                try {
                    const address = await reverseGeocodeLocation(lat, lng);
                    applyPinnedLocation(lat, lng, address);
                } catch (err) {
                    applyPinnedLocation(lat, lng);
                }
            });
        }

        setTimeout(() => {
            if (chatLocationMap) chatLocationMap.invalidateSize();
            if (chatLocationMap && chatQcBounds && chatQcBounds.isValid()) {
                chatLocationMap.fitBounds(chatQcBounds, { padding: [16, 16] });
            }
        }, 80);
    }

    async function closeConversationEndToEnd() {
        const endBtn = document.getElementById('chatEndConversationBtn');
        const activeConversationId = sessionStorage.getItem('conversation_id') || window.currentConversationId;

        if (!activeConversationId) {
            alert('No active conversation to close.');
            return false;
        }

        if (!confirm('Are you sure you want to close this conversation? This will close the conversation on both your side and the admin side.')) {
            return false;
        }

        const originalBtnHtml = endBtn ? endBtn.innerHTML : '';
        if (endBtn) {
            endBtn.disabled = true;
            endBtn.textContent = 'Closing...';
        }

        let closeResult = null;
        let lastError = null;

        try {
            const apiCandidates = resolveUserChatApiPath('chat-close.php');
            for (const apiUrl of apiCandidates) {
                try {
                    const response = await fetch(apiUrl, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ conversationId: activeConversationId })
                    });

                    const data = await response.json();
                    if (response.ok && data && data.success) {
                        closeResult = data;
                        break;
                    }

                    lastError = new Error((data && data.message) ? data.message : `HTTP ${response.status}`);
                } catch (candidateError) {
                    lastError = candidateError;
                }
            }

            if (!closeResult) {
                throw lastError || new Error('Failed to close conversation.');
            }

            if (window.stopChatPolling) {
                window.stopChatPolling();
            }
            if (window.handleConversationClosed) {
                window.handleConversationClosed('Citizen/User', 'citizen');
            } else {
                sessionStorage.removeItem('conversation_id');
                window.currentConversationId = null;
                const chatInterface = document.getElementById('chatInterface');
                const userInfoForm = document.getElementById('chatUserInfoForm');
                if (chatInterface) chatInterface.style.display = 'none';
                if (userInfoForm) userInfoForm.style.display = 'block';
            }
            return true;
        } catch (error) {
            console.error('Close conversation failed:', error);
            alert('Failed to close conversation: ' + (error && error.message ? error.message : 'Unknown error'));
            if (endBtn) {
                endBtn.disabled = false;
                endBtn.innerHTML = originalBtnHtml || '<i class="fas fa-times"></i> Close Chat';
            }
            return false;
        }
    }

    // Capture-phase delegated handler: survives DOM cloning/rebinding of the close button.
    if (!document.body.hasAttribute('data-chat-close-e2e-bound')) {
        document.body.setAttribute('data-chat-close-e2e-bound', 'true');
        document.addEventListener('click', async function(e) {
            const clickedClose = e.target && (e.target.id === 'chatEndConversationBtn' || e.target.closest('#chatEndConversationBtn'));
            if (!clickedClose) return;

            e.preventDefault();
            e.stopPropagation();
            e.stopImmediatePropagation();
            await closeConversationEndToEnd();
        }, true);
    }
    
    // Global event delegation for send button as ultimate fallback
    // This will catch clicks even if other handlers fail
    document.addEventListener('click', async function(e) {
        const target = e.target;
        
        // IMPORTANT: Don't interfere with chat button (chatFab) - let it work normally
        if (target && (target.id === 'chatFab' || target.closest('#chatFab'))) {
            return; // Let chat button handler work normally
        }
        
        // Only handle send button clicks
        if (target && (target.id === 'chatSendBtn' || target.closest('#chatSendBtn'))) {
            const sendBtn = target.id === 'chatSendBtn' ? target : target.closest('#chatSendBtn');
            if (sendBtn && !sendBtn.disabled) {
                console.log('GLOBAL HANDLER: Send button clicked via delegation');
                e.preventDefault();
                e.stopPropagation();
                
                // Get input value
                const input = document.getElementById('chatInput');
                const text = input ? input.value.trim() : '';
                
                if (!text) {
                    console.warn('GLOBAL HANDLER: No text to send');
                    return;
                }
                
                // Ensure script is loaded first
                const scriptLoaded = await ensureChatScriptLoaded();
                if (!scriptLoaded) {
                    console.error('GLOBAL HANDLER: chat-mysql.js script not loaded');
                    alert('Chat system script failed to load. Please refresh the page.');
                    return;
                }
                
                // Log available functions for debugging
                console.log('GLOBAL HANDLER: Available functions:', {
                    sendChatMessageMySQL: typeof window.sendChatMessageMySQL,
                    sendChatMessage: typeof window.sendChatMessage,
                    sendMessage: typeof window.sendMessage,
                    initChatMySQL: typeof window.initChatMySQL
                });
                
                // Try to initialize chat if not already done
                if (!window.sendChatMessageMySQL && window.initChatMySQL) {
                    console.log('GLOBAL HANDLER: Initializing chat first...');
                    sendBtn.disabled = true;
                    sendBtn.textContent = 'Initializing...';
                    try {
                        const initSuccess = await window.initChatMySQL();
                        if (initSuccess && window.sendChatMessageMySQL) {
                            console.log('GLOBAL HANDLER: Chat initialized, sending message');
                            sendBtn.textContent = 'Sending...';
                            const success = await window.sendChatMessageMySQL(text);
                            if (success && input) {
                                input.value = '';
                            }
                            sendBtn.disabled = false;
                            sendBtn.textContent = 'Send';
                            return;
                        }
                    } catch (err) {
                        console.error('GLOBAL HANDLER: Error initializing:', err);
                        sendBtn.disabled = false;
                        sendBtn.textContent = 'Send';
                        alert('Failed to initialize chat. Please refresh the page.');
                        return;
                    }
                }
                
                // Call send function if available
                if (window.sendChatMessageMySQL) {
                    console.log('GLOBAL HANDLER: Calling sendChatMessageMySQL');
                    sendBtn.disabled = true;
                    sendBtn.textContent = 'Sending...';
                    try {
                        const success = await window.sendChatMessageMySQL(text);
                        if (success && input) {
                            input.value = '';
                        }
                        sendBtn.disabled = false;
                        sendBtn.textContent = 'Send';
                    } catch (err) {
                        console.error('GLOBAL HANDLER: Error sending:', err);
                        sendBtn.disabled = false;
                        sendBtn.textContent = 'Send';
                        alert('Failed to send message: ' + err.message);
                    }
                } else if (window.sendChatMessage) {
                    console.log('GLOBAL HANDLER: Calling sendChatMessage');
                    sendBtn.disabled = true;
                    sendBtn.textContent = 'Sending...';
                    try {
                        await window.sendChatMessage();
                        sendBtn.disabled = false;
                        sendBtn.textContent = 'Send';
                    } catch (err) {
                        console.error('GLOBAL HANDLER: Error in sendChatMessage:', err);
                        sendBtn.disabled = false;
                        sendBtn.textContent = 'Send';
                    }
                } else {
                    console.error('GLOBAL HANDLER: No send function available');
                    console.error('GLOBAL HANDLER: Please check if chat-mysql.js is loaded');
                    alert('Chat system is not ready. Please refresh the page or check the console for errors.');
                }
            }
        }
    }, false); // Use bubble phase, not capture, to avoid interfering with chat button
    
    // Update global functions to use cached DOM elements for better performance
    // The IIFE versions above already work, but these cached versions are faster
    // We update them here so they use the DOM elements we've already queried
    function toggleSidebar() {
        if (sidebar) {
            sidebar.classList.toggle('sidebar-open');
            if (sidebarOverlay) {
                sidebarOverlay.classList.toggle('sidebar-overlay-open');
            }
            document.body.classList.toggle('sidebar-open');
        }
    }
    
    function closeSidebar() {
        if (sidebar) {
            sidebar.classList.remove('sidebar-open');
            if (sidebarOverlay) {
                sidebarOverlay.classList.remove('sidebar-overlay-open');
            }
            document.body.classList.remove('sidebar-open');
        }
    }
    
    // Update the global functions to use cached DOM elements for better performance
    // The IIFE versions above already work, but these cached versions are faster
    // We update them here so they use the DOM elements we've already queried
    try {
        // Update the functions to use cached DOM elements
        window.sidebarToggle = toggleSidebar;
        window.sidebarClose = closeSidebar;
        
        // Re-protect them after update
        Object.defineProperty(window, 'sidebarToggle', {
            value: toggleSidebar,
            writable: true,
            configurable: true,
            enumerable: true
        });
        
        Object.defineProperty(window, 'sidebarClose', {
            value: closeSidebar,
            writable: true,
            configurable: true,
            enumerable: true
        });
    } catch (e) {
        console.error('Error updating sidebar functions:', e);
        // Ensure functions still exist even if update fails
        if (typeof window.sidebarToggle !== 'function') {
            window.sidebarToggle = function() {
                const s = document.getElementById('sidebar');
                const so = document.getElementById('sidebarOverlay');
                if (s) {
                    s.classList.toggle('sidebar-open');
                    if (so) so.classList.toggle('sidebar-overlay-open');
                    document.body.classList.toggle('sidebar-open');
                }
            };
        }
        if (typeof window.sidebarClose !== 'function') {
            window.sidebarClose = function() {
                const s = document.getElementById('sidebar');
                const so = document.getElementById('sidebarOverlay');
                if (s) {
                    s.classList.remove('sidebar-open');
                    if (so) so.classList.remove('sidebar-overlay-open');
                    document.body.classList.remove('sidebar-open');
                }
            };
        }
    }
    
    // Verify functions are available after update
    if (typeof window.sidebarToggle !== 'function') {
        console.error('CRITICAL: window.sidebarToggle is not a function after DOMContentLoaded setup!');
    } else {
        console.log('Sidebar toggle function verified and ready');
    }
    if (typeof window.sidebarClose !== 'function') {
        console.error('CRITICAL: window.sidebarClose is not a function after DOMContentLoaded setup!');
    }
    
    // Add event delegation as ultimate fallback for sidebar toggle button
    // This ensures the toggle works even if onclick attribute is removed or button is replaced
    // This is especially important if translation system modifies the DOM
    if (!window.sidebarToggleDelegateAttached) {
        // Use capture phase to catch clicks early, before any other handlers
        document.addEventListener('click', function(e) {
            const toggleBtn = e.target.closest('.sidebar-toggle-btn');
            if (toggleBtn) {
                // Always prevent default and stop propagation to avoid conflicts
                e.preventDefault();
                e.stopPropagation();
                e.stopImmediatePropagation();
                
                // Call the function if it exists
                if (typeof window.sidebarToggle === 'function') {
                    window.sidebarToggle();
                } else {
                    console.error('window.sidebarToggle is not a function when button clicked!');
                    // Emergency fallback
                    const sidebar = document.getElementById('sidebar');
                    const sidebarOverlay = document.getElementById('sidebarOverlay');
                    if (sidebar) {
                        sidebar.classList.toggle('sidebar-open');
                        if (sidebarOverlay) {
                            sidebarOverlay.classList.toggle('sidebar-overlay-open');
                        }
                        document.body.classList.toggle('sidebar-open');
                    }
                }
            }
        }, true); // Capture phase - runs before other handlers
        window.sidebarToggleDelegateAttached = true;
        console.log('Sidebar toggle event delegation attached');
    }
    
    // Setup overlay click handler (only once)
    if (sidebarOverlay && !sidebarOverlay.dataset.handlerAttached) {
        sidebarOverlay.addEventListener('click', closeSidebar);
        sidebarOverlay.dataset.handlerAttached = 'true';
    }
    
    // Setup escape key handler (only once per page load)
    if (!window.sidebarEscapeHandlerAttached) {
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && sidebar && sidebar.classList.contains('sidebar-open')) {
                closeSidebar();
            }
        });
        window.sidebarEscapeHandlerAttached = true;
    }
    
    // Add functionality to sidebar navigation links
    // Use a flag to prevent duplicate setup
    let sidebarNavigationSetup = false;
    
    function setupSidebarNavigation() {
        // Prevent duplicate setup
        if (sidebarNavigationSetup) {
            console.log('Sidebar navigation already setup, skipping');
            return;
        }
        
        const sidebarLinks = document.querySelectorAll('.sidebar-link');
        console.log(`Setting up ${sidebarLinks.length} sidebar navigation links`);
        
        sidebarLinks.forEach((link) => {
            // Remove any existing event listeners by cloning the node
            const newLink = link.cloneNode(true);
            link.parentNode.replaceChild(newLink, link);
            const cleanLink = newLink;
            
            // Ensure link is clickable
            cleanLink.style.pointerEvents = 'auto';
            cleanLink.style.cursor = 'pointer';
            cleanLink.style.touchAction = 'manipulation';
            
            // Add click handler to close sidebar on mobile/tablet
            cleanLink.addEventListener('click', function(e) {
                // Don't prevent default - let the link navigate normally
                // But close sidebar if it's open (especially on mobile)
                if (sidebar && sidebar.classList.contains('sidebar-open')) {
                    // Check if we're on mobile/tablet (screen width < 1024px)
                    const isMobile = window.innerWidth < 1024;
                    
                    if (isMobile) {
                        // On mobile, close sidebar immediately when link is clicked
                        setTimeout(() => {
                            closeSidebar();
                        }, 100); // Small delay to allow navigation to start
                    } else {
                        // On desktop, close sidebar after a short delay for better UX
                        setTimeout(() => {
                            closeSidebar();
                        }, 200);
                    }
                }
                
                // Add active state visual feedback
                cleanLink.classList.add('clicked');
                setTimeout(() => {
                    cleanLink.classList.remove('clicked');
                }, 300);
            });
            
            // Add hover effect for better UX
            cleanLink.addEventListener('mouseenter', function() {
                if (!cleanLink.classList.contains('active')) {
                    cleanLink.style.opacity = '0.8';
                }
            });
            
            cleanLink.addEventListener('mouseleave', function() {
                cleanLink.style.opacity = '';
            });
        });
        
        sidebarNavigationSetup = true;
        console.log('Sidebar navigation links configured successfully');
    }
    
    // Setup sidebar navigation once after DOM is ready
    setupSidebarNavigation();

    // Chat modal behaviour - keep it open until user closes it
    function openChat() {
        // Always get fresh reference to modal
        let modal = document.getElementById('chatModal');
        if (!modal) {
            console.error('Chat modal not found in DOM');
            alert('Chat is not available. Please refresh the page.');
            return;
        }
        
        // Anonymous mode - always allow chat, just check if info is provided
        const hasStoredName = localStorage.getItem('guest_name') || sessionStorage.getItem('user_name');
        const hasStoredContact = localStorage.getItem('guest_contact') || sessionStorage.getItem('user_phone');
        const hasStoredLocation = localStorage.getItem('guest_location') || sessionStorage.getItem('user_location');
        const hasStoredConcern = localStorage.getItem('guest_concern') || sessionStorage.getItem('user_concern');
        const hasAllRequiredInfo = hasStoredName && hasStoredContact && hasStoredLocation && hasStoredConcern;
        
        if (!hasAllRequiredInfo) {
            // Show form and prevent chat
            checkAndShowUserInfoForm();
            console.log('User info required - showing form');
        }
        
        console.log('Opening chat modal...', modal);
        
        // Force show the modal with all necessary styles
        modal.style.cssText = `
            display: flex !important;
            visibility: visible !important;
            opacity: 1 !important;
            pointer-events: auto !important;
            z-index: 99999 !important;
        `;
        modal.classList.add('chat-modal-open');
        modal.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden'; // Prevent background scrolling
        
        // Ensure content is visible
        const modalContent = modal.querySelector('.chat-modal-content');
        if (modalContent) {
            modalContent.style.cssText = `
                pointer-events: auto !important;
                z-index: 100000 !important;
                display: flex !important;
            `;
        }
        
        // Check if user info form should be shown
        checkAndShowUserInfoForm();
        
        // Attach form handler if needed (for anonymous users)
        attachUserInfoFormHandler();
        
        // Initialize MySQL chat if not already done AND form is not showing
        const userInfoForm = document.getElementById('chatUserInfoForm');
        const isFormShowing = userInfoForm && userInfoForm.style.display !== 'none' && userInfoForm.style.display !== '';
        
        if (!isFormShowing && window.initChatMySQL && !window.chatInitialized) {
            window.initChatMySQL().then((success) => {
                if (success) {
                    console.log('MySQL chat initialized');
                }
                // Attach send button handlers after initialization
                setTimeout(() => {
                    if (window.attachSendButtonHandlers) {
                        window.attachSendButtonHandlers();
                    }
                }, 200);
            }).catch(err => {
                console.error('Failed to initialize MySQL chat:', err);
            });
        } else if (!isFormShowing && window.attachSendButtonHandlers) {
            // If already initialized, attach handlers immediately
            setTimeout(() => {
                window.attachSendButtonHandlers();
            }, 100);
        } else if (isFormShowing) {
            console.log('Form is showing, skipping auto-initialization to prevent loop');
        }
        
        // Close modal when clicking outside (on backdrop) - only add once
        if (!chatModal.hasAttribute('data-backdrop-handler')) {
            chatModal.setAttribute('data-backdrop-handler', 'true');
            chatModal.addEventListener('click', function(e) {
                if (e.target === chatModal) {
                    closeChatWithFlag();
                }
            });
        }
        
        // Re-attach button handlers when modal opens
        setTimeout(() => {
            // Use the centralized attachSendButtonHandlers function instead of duplicating
            if (window.attachSendButtonHandlers) {
                window.attachSendButtonHandlers();
            }
            
            const input = document.getElementById('chatInput');
            if (input) {
                input.focus();
            }
        }, 150);
    }

    function closeChat() {
        if (!chatModal) return;
        chatModal.classList.remove('chat-modal-open');
        chatModal.setAttribute('aria-hidden', 'true');
        chatModal.style.display = 'none';
        chatModal.style.visibility = 'hidden';
        chatModal.style.opacity = '0';
        chatModal.style.pointerEvents = 'none';
        document.body.style.overflow = ''; // Restore scrolling
        
        // Stop polling when modal is closed
        if (window.stopChatPolling) {
            window.stopChatPolling();
        }
    }
    
    // Store original closeChat before overriding
    const originalCloseChat = closeChat;
    let chatClosingIntentionally = false;
    
    // Override closeChat to set flag
    function closeChatWithFlag() {
        chatClosingIntentionally = true;
        originalCloseChat();
        setTimeout(() => {
            chatClosingIntentionally = false;
        }, 100);
    }
    
    // Function to check and show user info form for anonymous users
    function checkAndShowUserInfoForm() {
        // Don't check if form was just submitted (prevents loop)
        if (window.formJustSubmitted) {
            console.log('Form just submitted, skipping form check to prevent loop');
            return;
        }
        
        const userInfoForm = document.getElementById('chatUserInfoForm');
        const chatInterface = document.getElementById('chatInterface');
        
        if (!userInfoForm || !chatInterface) {
            console.warn('User info form or chat interface not found');
            return;
        }
        
        // Don't show form if chat is already initialized and has an active conversation
        const conversationId = sessionStorage.getItem('conversation_id');
        if (window.chatInitialized && conversationId && !window.conversationClosedHandled) {
            console.log('Chat already initialized with active conversation, not showing form');
            // Ensure chat interface is shown
            userInfoForm.style.display = 'none';
            chatInterface.style.display = 'block';
            return;
        }
        
        // Don't show form if conversation was just closed (handleConversationClosed handles this)
        if (window.conversationClosedHandled) {
            console.log('Conversation closed handler is active, not showing form via checkAndShowUserInfoForm');
            return;
        }
        
        // Anonymous mode - check if guest has provided ALL required info (name, contact, location, concern)
        const hasStoredName = localStorage.getItem('guest_name') || sessionStorage.getItem('user_name');
        const hasStoredContact = localStorage.getItem('guest_contact') || sessionStorage.getItem('user_phone');
        const hasStoredLocation = localStorage.getItem('guest_location') || sessionStorage.getItem('user_location');
        const hasStoredConcern = localStorage.getItem('guest_concern') || sessionStorage.getItem('user_concern');
        
        // All fields are required for anonymous users
        const hasAllRequiredInfo = hasStoredName && hasStoredContact && hasStoredLocation && hasStoredConcern;
        
        console.log('Anonymous mode - hasAllRequiredInfo:', hasAllRequiredInfo);
        
        if (!hasAllRequiredInfo) {
            // Anonymous user without ALL required info - show form (REQUIRED)
            userInfoForm.style.display = 'block';
            chatInterface.style.display = 'none';
            console.log('Showing user info form (anonymous - REQUIRED)');
            
            // Initialize searchable barangay dropdown and form validation
            setTimeout(() => {
                initSearchableBarangay();
                setupFormValidation();
            }, 150);
            
            // Disable chat input if form is not filled
            const chatInput = document.getElementById('chatInput');
            const chatSendBtn = document.getElementById('chatSendBtn');
            if (chatInput) chatInput.disabled = true;
            if (chatSendBtn) chatSendBtn.disabled = true;
        } else {
            // Guest has provided all required info - show chat interface
            userInfoForm.style.display = 'none';
            chatInterface.style.display = 'block';
            console.log('Showing chat interface (anonymous user with all required info)');
            
            // Enable chat input
            const chatInput = document.getElementById('chatInput');
            const chatSendBtn = document.getElementById('chatSendBtn');
            if (chatInput) chatInput.disabled = false;
            if (chatSendBtn) chatSendBtn.disabled = false;
        }
    }
    
    // Function to attach user info form handler
    // Barangay list for Quezon City
    const barangayList = [
        'Alicia', 'Amihan', 'Apolonio Samson', 'Bagong Pag-asa', 'Bagong Silangan',
        'Bagumbayan', 'Bagumbuhay', 'Bahay Toro', 'Balingasa', 'Balintawak',
        'Balumbato', 'Batasan Hills', 'Bayanihan', 'Blue Ridge A', 'Blue Ridge B',
        'Botocan', 'Bungad', 'Camp Aguinaldo', 'Capri', 'Central',
        'Claro', 'Commonwealth', 'Culiat', 'Damar', 'Damayan',
        'Damayang Lagi', 'Del Monte', 'Diliman', 'Dioquino Zobel', 'Don Manuel',
        'Doña Aurora', 'Doña Imelda', 'Doña Josefa', 'Duyan-duyan', 'E. Rodriguez',
        'East Kamias', 'Escopa I', 'Escopa II', 'Escopa III', 'Escopa IV',
        'Fairview', 'Greater Lagro', 'Gulod', 'Holy Spirit', 'Horseshoe',
        'Immaculate Concepcion', 'Kaligayahan', 'Kalusugan', 'Kamuning', 'Katipunan',
        'Kaunlaran', 'Kristong Hari', 'Krus na Ligas', 'Laging Handa', 'Libis',
        'Lourdes', 'Loyola Heights', 'Maharlika', 'Malaya', 'Mangga',
        'Manresa', 'Mariana', 'Mariblo', 'Marilag', 'Masagana',
        'Masambong', 'Matandang Balara', 'Milagrosa', 'Nagkaisang Nayon', 'Nayon Kaunlaran',
        'New Era', 'Novaliches Proper', 'N.S. Amoranto', 'Obrero', 'Old Capitol Site',
        'Paang Bundok', 'Pag-ibig sa Nayon', 'Paligsahan', 'Paltok', 'Pansol',
        'Paraiso', 'Pasong Putik Proper', 'Pasong Tamo', 'Payatas', 'Phil-Am',
        'Pinyahan', 'Project 6', 'Quirino 2-A', 'Quirino 2-B', 'Quirino 2-C',
        'Quirino 3-A', 'Quirino 3-B', 'Ramon Magsaysay', 'Roxas', 'Sacred Heart',
        'Saint Ignatius', 'Saint Peter', 'Salvacion', 'San Agustin', 'San Antonio',
        'San Bartolome', 'San Isidro', 'San Isidro Galas', 'San Jose', 'San Martin de Porres',
        'San Roque', 'San Vicente', 'Sangandaan', 'Santa Cruz', 'Santa Lucia',
        'Santa Monica', 'Santa Teresita', 'Santo Cristo', 'Santo Domingo', 'Santo Niño',
        'Santol', 'Sauyo', 'Sienna', 'Sikatuna Village', 'Silangan',
        'Socorro', 'South Triangle', 'St. Ignatius', 'Tagumpay', 'Talayan',
        'Talipapa', 'Tandang Sora', 'Tatalon', 'Teachers Village East', 'Teachers Village West',
        'Ugong Norte', 'Unang Sigaw', 'UP Campus', 'UP Village', 'Valencia',
        'Vasra', 'Veterans Village', 'Villa Maria Clara', 'West Kamias', 'West Triangle',
        'White Plains'
    ];

    // Initialize searchable barangay dropdown
    function initSearchableBarangay() {
        const searchInput = document.getElementById('userLocationSearch');
        const hiddenInput = document.getElementById('userLocationInput');
        const dropdown = document.getElementById('locationDropdown');
        const dropdownList = dropdown ? dropdown.querySelector('.searchable-select-list') : null;
        const useCurrentBtn = document.getElementById('chatUseCurrentLocationBtn');
        const pinMapBtn = document.getElementById('chatPinOnMapBtn');
        const hideMapBtn = document.getElementById('chatHideMapBtn');
        const mapWrap = document.getElementById('chatLocationMapWrap');
        
        if (!searchInput || !hiddenInput || !dropdown || !dropdownList) {
            // Retry after a short delay if elements aren't ready
            setTimeout(initSearchableBarangay, 100);
            return;
        }
        
        // Clear existing items if already initialized
        if (dropdownList.querySelector('.searchable-select-item')) {
            return; // Already initialized
        }
        
        // Populate dropdown with barangays
        barangayList.forEach(barangay => {
            const item = document.createElement('div');
            item.className = 'searchable-select-item';
            item.textContent = barangay;
            item.dataset.value = barangay;
            dropdownList.appendChild(item);
        });
        
        // Filter function
        function filterBarangays(searchTerm) {
            const items = dropdownList.querySelectorAll('.searchable-select-item');
            const term = searchTerm.toLowerCase().trim();
            let hasResults = false;
            
            items.forEach(item => {
                const text = item.textContent.toLowerCase();
                if (text.includes(term)) {
                    item.style.display = 'block';
                    hasResults = true;
                } else {
                    item.style.display = 'none';
                }
            });
            
            return hasResults;
        }
        
        // Show dropdown
        function showDropdown() {
            dropdown.style.display = 'block';
            const searchTerm = searchInput.value.trim();
            if (searchTerm) {
                filterBarangays(searchTerm);
            } else {
                // Show all items if no search term
                const items = dropdownList.querySelectorAll('.searchable-select-item');
                items.forEach(item => {
                    item.style.display = 'block';
                });
            }
        }
        
        // Hide dropdown
        function hideDropdown() {
            setTimeout(() => {
                dropdown.style.display = 'none';
            }, 200);
        }
        
        // Select barangay
        function selectBarangay(barangay) {
            searchInput.value = barangay;
            hiddenInput.value = barangay;
            hideDropdown();
            // Trigger validation after selection
            hiddenInput.dispatchEvent(new Event('change', { bubbles: true }));
        }
        
        // Event listeners
        searchInput.addEventListener('focus', showDropdown);
        searchInput.addEventListener('input', function(e) {
            const term = e.target.value;
            if (term.trim()) {
                hiddenInput.value = term.trim();
                hiddenInput.dispatchEvent(new Event('change', { bubbles: true }));
                showDropdown();
            } else {
                hiddenInput.value = '';
            }
        });

        searchInput.addEventListener('blur', function() {
            const typed = searchInput.value.trim();
            if (typed && !hiddenInput.value.trim()) {
                hiddenInput.value = typed;
                hiddenInput.dispatchEvent(new Event('change', { bubbles: true }));
            }
        });
        
        // Click on dropdown item
        dropdownList.addEventListener('click', function(e) {
            const item = e.target.closest('.searchable-select-item');
            if (item) {
                selectBarangay(item.dataset.value);
            }
        });
        
        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (!searchInput.contains(e.target) && !dropdown.contains(e.target)) {
                hideDropdown();
            }
        });
        
        // Handle keyboard navigation
        searchInput.addEventListener('keydown', function(e) {
            const visibleItems = Array.from(dropdownList.querySelectorAll('.searchable-select-item:not([style*="display: none"])'));
            const currentIndex = visibleItems.findIndex(item => item.classList.contains('selected'));
            
            if (e.key === 'ArrowDown') {
                e.preventDefault();
                const nextIndex = currentIndex < visibleItems.length - 1 ? currentIndex + 1 : 0;
                visibleItems.forEach(item => item.classList.remove('selected'));
                if (visibleItems[nextIndex]) {
                    visibleItems[nextIndex].classList.add('selected');
                    visibleItems[nextIndex].scrollIntoView({ block: 'nearest' });
                }
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                const prevIndex = currentIndex > 0 ? currentIndex - 1 : visibleItems.length - 1;
                visibleItems.forEach(item => item.classList.remove('selected'));
                if (visibleItems[prevIndex]) {
                    visibleItems[prevIndex].classList.add('selected');
                    visibleItems[prevIndex].scrollIntoView({ block: 'nearest' });
                }
            } else if (e.key === 'Enter' && currentIndex >= 0) {
                e.preventDefault();
                selectBarangay(visibleItems[currentIndex].dataset.value);
            } else if (e.key === 'Escape') {
                hideDropdown();
            }
        });

        if (useCurrentBtn && !useCurrentBtn.hasAttribute('data-handler-attached')) {
            useCurrentBtn.setAttribute('data-handler-attached', 'true');
            useCurrentBtn.addEventListener('click', function() {
                if (!navigator.geolocation) {
                    alert('Geolocation is not supported in this browser.');
                    return;
                }

                useCurrentBtn.disabled = true;
                useCurrentBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Getting location...';

                navigator.geolocation.getCurrentPosition(async function(position) {
                    try {
                        const lat = position.coords.latitude;
                        const lng = position.coords.longitude;
                        if (!isPointInsideQc(lat, lng)) {
                            showChatNoticeModal('Your current location appears outside Quezon City. Please pin or enter a Quezon City location.', 'Location Required');
                            return;
                        }
                        const address = await reverseGeocodeLocation(lat, lng);
                        setUserLocationValue(address);
                        if (mapWrap) {
                            await initChatLocationMap();
                            if (chatLocationMap) chatLocationMap.setView([lat, lng], 15);
                            if (!chatLocationMarker) {
                                chatLocationMarker = L.marker([lat, lng], { draggable: true }).addTo(chatLocationMap);
                            } else {
                                chatLocationMarker.setLatLng([lat, lng]);
                            }
                        }
                    } catch (err) {
                        showChatNoticeModal('Unable to resolve your location address. You can still type it manually.', 'Location Notice');
                    } finally {
                        useCurrentBtn.disabled = false;
                        useCurrentBtn.innerHTML = '<i class="fas fa-location-crosshairs"></i> Use Current Location';
                    }
                }, function(error) {
                    let msg = 'Unable to get current location.';
                    if (error && error.code === 1) msg = 'Location permission denied.';
                    if (error && error.code === 2) msg = 'Location unavailable.';
                    if (error && error.code === 3) msg = 'Location request timed out.';
                    showChatNoticeModal(msg, 'Location Notice');
                    useCurrentBtn.disabled = false;
                    useCurrentBtn.innerHTML = '<i class="fas fa-location-crosshairs"></i> Use Current Location';
                }, {
                    enableHighAccuracy: true,
                    timeout: 12000,
                    maximumAge: 15000
                });
            });
        }

        if (pinMapBtn && !pinMapBtn.hasAttribute('data-handler-attached')) {
            pinMapBtn.setAttribute('data-handler-attached', 'true');
            pinMapBtn.addEventListener('click', async function() {
                try {
                    await initChatLocationMap();
                    pinMapBtn.classList.add('active');
                } catch (err) {
                    alert('Unable to load map picker right now.');
                }
            });
        }

        if (hideMapBtn && !hideMapBtn.hasAttribute('data-handler-attached')) {
            hideMapBtn.setAttribute('data-handler-attached', 'true');
            hideMapBtn.addEventListener('click', function() {
                if (mapWrap) {
                    mapWrap.style.display = 'none';
                }
                if (pinMapBtn) {
                    pinMapBtn.classList.remove('active');
                }
            });
        }
    }
    
    // Setup form validation to enable/disable submit button
    function setupFormValidation() {
        const nameInput = document.getElementById('userNameInput');
        const contactInput = document.getElementById('userContactInput');
        const locationInput = document.getElementById('userLocationInput');
        const concernSelect = document.getElementById('userConcernSelect');
        const submitBtn = document.querySelector('.chat-form-submit');
        
        if (!nameInput || !contactInput || !locationInput || !concernSelect || !submitBtn) {
            setTimeout(setupFormValidation, 100);
            return;
        }
        
        function validateForm() {
            const name = nameInput.value.trim();
            const contact = contactInput.value.trim();
            const location = locationInput.value.trim();
            const concern = concernSelect.value;
            
            if (name && contact && location && concern) {
                submitBtn.disabled = false;
                submitBtn.style.opacity = '1';
                submitBtn.style.cursor = 'pointer';
            } else {
                submitBtn.disabled = true;
                submitBtn.style.opacity = '0.6';
                submitBtn.style.cursor = 'not-allowed';
            }
        }
        
        // Initial validation
        validateForm();
        
        // Add event listeners
        nameInput.addEventListener('input', validateForm);
        contactInput.addEventListener('input', validateForm);
        locationInput.addEventListener('change', validateForm);
        concernSelect.addEventListener('change', validateForm);
        
        // Also listen to location search input changes
        const locationSearch = document.getElementById('userLocationSearch');
        if (locationSearch) {
            locationSearch.addEventListener('input', function() {
                // Validate when location is selected
                setTimeout(validateForm, 100);
            });
        }
    }

    function attachUserInfoFormHandler() {
        const form = document.getElementById('userInfoForm');
        if (!form) {
            console.log('User info form not found, may not be needed');
            return;
        }
        
        // Remove old handler if exists
        const newForm = form.cloneNode(true);
        form.parentNode.replaceChild(newForm, form);
        const freshForm = document.getElementById('userInfoForm');
        
        if (freshForm && !freshForm.hasAttribute('data-handler-attached')) {
            freshForm.setAttribute('data-handler-attached', 'true');
            
            freshForm.addEventListener('submit', async function(e) {
                e.preventDefault();
                e.stopPropagation();
                e.stopImmediatePropagation();
                
                const name = document.getElementById('userNameInput').value.trim();
                const contact = document.getElementById('userContactInput').value.trim();
                const location = document.getElementById('userLocationInput').value.trim();
                const concern = document.getElementById('userConcernSelect').value;
                
                if (!name || !contact || !location || !concern) {
                    alert('Please fill in all required fields.');
                    return false;
                }
                
                console.log('Form submitted:', { name, contact, location, concern });
                
                // Store user info in both sessionStorage and localStorage
                sessionStorage.setItem('user_name', name);
                sessionStorage.setItem('user_phone', contact);
                sessionStorage.setItem('user_location', location);
                sessionStorage.setItem('user_concern', concern);
                
                // Save to localStorage for persistence
                localStorage.setItem('guest_info_provided', 'true');
                localStorage.setItem('guest_name', name);
                localStorage.setItem('guest_contact', contact);
                localStorage.setItem('guest_location', location);
                localStorage.setItem('guest_concern', concern);
                
                // Hide form and show chat interface
                const userInfoForm = document.getElementById('chatUserInfoForm');
                const chatInterface = document.getElementById('chatInterface');
                if (userInfoForm && chatInterface) {
                    userInfoForm.style.display = 'none';
                    chatInterface.style.display = 'block';
                }
                
                // Enable chat input and send button
                const chatInput = document.getElementById('chatInput');
                const chatSendBtn = document.getElementById('chatSendBtn');
                if (chatInput) chatInput.disabled = false;
                if (chatSendBtn) {
                    chatSendBtn.disabled = false;
                    chatSendBtn.textContent = 'Send';
                }
                
                // Clear any old conversation ID before initializing new chat
                sessionStorage.removeItem('conversation_id');
                if (window.stopChatPolling) {
                    window.stopChatPolling();
                }
                
                // Reset chat initialization flags
                if (window.chatInitialized !== undefined) {
                    window.chatInitialized = false;
                }
                
                // Set a flag to prevent form from showing again after submission
                window.formJustSubmitted = true;
                // Clear the closed handler flag since we're starting fresh
                window.conversationClosedHandled = false;
                
                // Initialize MySQL chat with the provided info
                if (window.initChatMySQL) {
                    try {
                        const success = await window.initChatMySQL();
                        if (success) {
                            console.log('Chat initialized after form submission - new conversation created');
                            // Clear form submission flag after successful initialization
                            setTimeout(() => {
                                window.formJustSubmitted = false;
                            }, 1000);
                            // Attach send button handlers
                            setTimeout(() => {
                                if (window.attachSendButtonHandlers) {
                                    window.attachSendButtonHandlers();
                                }
                                // Focus input after handlers are attached
                                if (chatInput) {
                                    chatInput.focus();
                                }
                            }, 200);
                        } else {
                            console.error('Failed to initialize chat');
                            alert('Failed to initialize chat. Please try again.');
                        }
                    } catch (err) {
                        console.error('Failed to initialize chat:', err);
                        alert('Failed to initialize chat. Please try again.');
                    }
                } else {
                    console.error('initChatMySQL function not available');
                    alert('Chat system is not ready. Please refresh the page.');
                }
                
                return false;
            });
        }
    }
    
    // Expose openChat globally so it can be called from other pages
    window.openChat = openChat;
    window.closeChat = closeChatWithFlag;
    
    // Also expose a simple test function
    window.testChatModal = function() {
        const modal = document.getElementById('chatModal');
        if (modal) {
            console.log('Modal found, testing display...');
            modal.style.cssText = 'display: flex !important; visibility: visible !important; opacity: 1 !important; pointer-events: auto !important; z-index: 99999 !important; position: fixed !important; inset: 0 !important;';
            modal.classList.add('chat-modal-open');
            document.body.style.overflow = 'hidden';
            console.log('Modal should be visible now');
            return true;
        } else {
            console.error('Modal not found in DOM');
            return false;
        }
    };

    // Chat button is now in auth-icons, ensure it's clickable
    // Use event delegation for more reliability
    function setupChatButton() {
        const chatButton = document.getElementById('chatFab');
        if (!chatButton) {
            console.error('Chat button (#chatFab) not found in DOM');
            // Retry after a short delay
            setTimeout(setupChatButton, 500);
            return;
        }
        
        console.log('Chat button found, setting up...');
        
        // Remove all existing listeners by cloning
        const newButton = chatButton.cloneNode(true);
        chatButton.parentNode.replaceChild(newButton, chatButton);
        const freshButton = document.getElementById('chatFab');
        
        if (!freshButton) {
            console.error('Failed to get fresh button after cloning');
            return;
        }
        
        freshButton.type = 'button';
        freshButton.style.pointerEvents = 'auto';
        freshButton.style.cursor = 'pointer';
        freshButton.style.touchAction = 'manipulation';
        
        // Simple, direct click handler
        freshButton.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            e.stopImmediatePropagation();
            console.log('=== CHAT BUTTON CLICKED ===');
            
            // Get modal directly
            const modal = document.getElementById('chatModal');
            console.log('Modal element:', modal);
            
            if (!modal) {
                console.error('Modal not found!');
                alert('Chat modal not found. Please refresh the page.');
                return;
            }

            const useBottomRightChatLayout =
                document.body.classList.contains('user-admin-header') ||
                document.body.classList.contains('user-admin-ui');
            const modalAlignItems = useBottomRightChatLayout ? 'flex-end' : 'center';
            const modalJustifyContent = useBottomRightChatLayout ? 'flex-end' : 'center';
             
            // Force show modal
            console.log('Showing modal...');
            modal.style.cssText = `
                position: fixed !important;
                top: 0 !important;
                left: 0 !important;
                right: 0 !important;
                bottom: 0 !important;
                display: flex !important;
                visibility: visible !important;
                opacity: 1 !important;
                pointer-events: auto !important;
                z-index: 99999 !important;
                background: rgba(0,0,0,0.5) !important;
                align-items: ${modalAlignItems} !important;
                justify-content: ${modalJustifyContent} !important;
                padding: 1rem !important;
            `;
            modal.classList.add('chat-modal-open');
            modal.setAttribute('aria-hidden', 'false');
            document.body.style.overflow = 'hidden';
            
            // Ensure content is visible
            const modalContentEl = modal.querySelector('.chat-modal-content');
            if (modalContentEl) {
                modalContentEl.style.cssText = `
                    pointer-events: auto !important;
                    z-index: 100000 !important;
                    display: flex !important;
                `;
            }
            
            // Check and show user info form
            checkAndShowUserInfoForm();
            attachUserInfoFormHandler();
            
            // Initialize searchable dropdown and validation after modal opens
            setTimeout(() => {
                initSearchableBarangay();
                setupFormValidation();
            }, 200);
            
            // Initialize MySQL chat if needed
            if (window.initChatMySQL && !window.chatInitialized) {
                window.initChatMySQL().then((success) => {
                    if (success) {
                        console.log('MySQL chat initialized');
                    }
                    // Attach send button handlers after initialization
                    setTimeout(() => {
                        if (window.attachSendButtonHandlers) {
                            window.attachSendButtonHandlers();
                        }
                    }, 200);
                }).catch(err => {
                    console.error('Failed to initialize MySQL chat:', err);
                });
            } else if (window.attachSendButtonHandlers) {
                // If already initialized, attach handlers immediately
                setTimeout(() => {
                    window.attachSendButtonHandlers();
                }, 100);
            }
            
            console.log('Modal should be visible now');
        }, true); // Use capture phase
        
        // Touch handler for mobile
        freshButton.addEventListener('touchend', function(e) {
            e.preventDefault();
            e.stopPropagation();
            console.log('Chat button touched');
            freshButton.click(); // Trigger click
        }, { passive: false, capture: true });
        
        // Keyboard support
        freshButton.setAttribute('tabindex', '0');
        freshButton.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                freshButton.click();
            }
        });
        
        console.log('Chat button setup complete');
    }
    
    // Setup chat button
    setupChatButton();
    
    // Also try to setup after a delay in case DOM wasn't ready
    setTimeout(setupChatButton, 1000);

    if (chatModalCloseBtn) {
        chatModalCloseBtn.addEventListener('click', closeChatWithFlag);
    }
    
    // Add MutationObserver to prevent modal from closing unexpectedly
    if (chatModal) {
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
                    // If modal class is being removed but we didn't call closeChat, restore it
                    if (!chatModal.classList.contains('chat-modal-open') && 
                        document.body.style.overflow === 'hidden' &&
                        !chatClosingIntentionally) {
                        // Modal was closed unexpectedly, reopen it
                        setTimeout(() => {
                            if (chatModal && !chatClosingIntentionally) {
                                chatModal.classList.add('chat-modal-open');
                                chatModal.setAttribute('aria-hidden', 'false');
                                chatModal.style.pointerEvents = 'auto';
                                chatModal.style.zIndex = '9999';
                            }
                        }, 10);
                    } else if (chatModal.classList.contains('chat-modal-open')) {
                        // Ensure pointer events are enabled when modal is open
                        chatModal.style.pointerEvents = 'auto';
                        chatModal.style.zIndex = '9999';
                        const chatContent = chatModal.querySelector('.chat-modal-content');
                        if (chatContent) {
                            chatContent.style.pointerEvents = 'auto';
                            chatContent.style.zIndex = '10000';
                        }
                    }
                }
            });
        });
        
        observer.observe(chatModal, {
            attributes: true,
            attributeFilter: ['class']
        });
    }

    // Only close on Escape key or close button click - keep modal open otherwise
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && chatModal && chatModal.classList.contains('chat-modal-open')) {
            closeChatWithFlag();
        }
    });

    // Don't close when clicking outside - only close button
    // Removed the click-outside-to-close functionality to keep modal open

    // Initialize Firebase Chat
    let chatInitialized = false;
    
    async function initFirebaseChat() {
        if (chatInitialized || window.chatInitialized) {
            console.log('Chat already initialized, skipping...');
            return;
        }
        
        console.log('Starting Firebase chat initialization...');
        
        // Load Firebase SDKs using compat version (works without ES modules)
        if (typeof firebase === 'undefined') {
            // Use Firebase compat build which works with regular script tags
            const firebaseAppScript = document.createElement('script');
            firebaseAppScript.src = 'https://www.gstatic.com/firebasejs/9.23.0/firebase-app-compat.js';
            document.head.appendChild(firebaseAppScript);
            
            const firebaseDatabaseScript = document.createElement('script');
            firebaseDatabaseScript.src = 'https://www.gstatic.com/firebasejs/9.23.0/firebase-database-compat.js';
            document.head.appendChild(firebaseDatabaseScript);
            
            // Wait for Firebase to load
            await new Promise((resolve, reject) => {
                let attempts = 0;
                const maxAttempts = 50; // 5 seconds max
                const checkFirebase = setInterval(() => {
                    attempts++;
                    if (typeof firebase !== 'undefined' && firebase.database) {
                        clearInterval(checkFirebase);
                        resolve();
                    } else if (attempts >= maxAttempts) {
                        clearInterval(checkFirebase);
                        console.error('Firebase failed to load after 5 seconds');
                        reject(new Error('Firebase loading timeout'));
                    }
                }, 100);
            });
        }
        
        // Initialize Firebase
        const firebaseConfig = {
            apiKey: "AIzaSyAvfyPTCsBp0dL76VsEVkiIrIsQkko91os",
            authDomain: "emergencycommunicationsy-eb828.firebaseapp.com",
            databaseURL: "https://emergencycommunicationsy-eb828-default-rtdb.asia-southeast1.firebasedatabase.app",
            projectId: "emergencycommunicationsy-eb828",
            storageBucket: "emergencycommunicationsy-eb828.firebasestorage.app",
            messagingSenderId: "201064241540",
            appId: "1:201064241540:web:4f6d026cd355404ec365d1",
            measurementId: "G-ESQ63CMP9B"
        };
        
        // Check if firebase is available before initializing
        if (typeof firebase === 'undefined') {
            console.error('Firebase SDK not loaded. Cannot initialize Firebase.');
            return;
        }
        
        if (!window.firebaseApp) {
            try {
                window.firebaseApp = firebase.initializeApp(firebaseConfig);
            } catch (error) {
                console.error('Error initializing Firebase:', error);
                return;
            }
        }
        
        try {
            const database = firebase.database();
            // Make database globally accessible
            window.chatDatabase = database;
        } catch (error) {
            console.error('Error getting Firebase database:', error);
            return;
        }
        
        // Get user info from PHP session or localStorage
        let userId = sessionStorage.getItem('user_id');
        let userName = sessionStorage.getItem('user_name');
        let userEmail = sessionStorage.getItem('user_email');
        let userPhone = sessionStorage.getItem('user_phone');
        let isGuest = false;
        
        // Try to get from PHP session if available
        <?php 
        // Session should already be started, but check to be safe
        if (session_status() === PHP_SESSION_NONE) {
            if (!headers_sent()) {
                session_start();
            } else {
                @session_start();
            }
        }
        if (isset($_SESSION['user_id'])): 
        ?>
        userId = '<?php echo $_SESSION['user_id']; ?>';
        sessionStorage.setItem('user_id', userId);
        <?php endif; ?>
        
        <?php if (isset($_SESSION['user_name'])): ?>
        userName = '<?php echo addslashes($_SESSION['user_name']); ?>';
        sessionStorage.setItem('user_name', userName);
        <?php endif; ?>
        
        <?php if (isset($_SESSION['user_email'])): ?>
        userEmail = '<?php echo addslashes($_SESSION['user_email']); ?>';
        sessionStorage.setItem('user_email', userEmail);
        <?php endif; ?>
        
        <?php if (isset($_SESSION['user_phone'])): ?>
        userPhone = '<?php echo addslashes($_SESSION['user_phone']); ?>';
        sessionStorage.setItem('user_phone', userPhone);
        <?php endif; ?>
        
        // Fallback to guest if no user info
        if (!userId || userId === 'null' || userId === 'undefined') {
            // Generate a persistent guest ID based on browser fingerprint
            let guestId = localStorage.getItem('guest_user_id');
            if (!guestId) {
                guestId = 'guest_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
                localStorage.setItem('guest_user_id', guestId);
            }
            userId = guestId;
            sessionStorage.setItem('user_id', userId);
            isGuest = true;
        }
        if (!userName || userName === 'null' || userName === 'undefined') {
            userName = 'Guest User';
            sessionStorage.setItem('user_name', userName);
        }
        
        // Store user type
        sessionStorage.setItem('is_guest', isGuest ? 'true' : 'false');
        
        // Check if guest user has already provided info
        const guestInfoProvided = localStorage.getItem('guest_info_provided') === 'true';
        const storedGuestName = localStorage.getItem('guest_name');
        const storedGuestContact = localStorage.getItem('guest_contact');
        const storedGuestLocation = localStorage.getItem('guest_location');
        const storedGuestConcern = localStorage.getItem('guest_concern');
        
        // If guest and info not provided, show form first
        if (isGuest && !guestInfoProvided) {
            const userInfoForm = document.getElementById('chatUserInfoForm');
            const chatInterface = document.getElementById('chatInterface');
            if (userInfoForm && chatInterface) {
                userInfoForm.style.display = 'block';
                chatInterface.style.display = 'none';
                
                // Initialize searchable barangay dropdown and form validation
            setTimeout(() => {
                initSearchableBarangay();
                setupFormValidation();
            }, 150);
            
            // Handle form submission
                const form = document.getElementById('userInfoForm');
                if (form) {
                    form.addEventListener('submit', async function(e) {
                        e.preventDefault();
                        
                        const name = document.getElementById('userNameInput').value.trim();
                        const contact = document.getElementById('userContactInput').value.trim();
                        const location = document.getElementById('userLocationInput').value.trim();
                        const concern = document.getElementById('userConcernSelect').value;
                        
                        if (!name || !contact || !location || !concern) {
                            alert('Please fill in all required fields.');
                            return;
                        }
                        
                        // Store user info
                        userName = name;
                        userPhone = contact;
                        const userLocation = location;
                        const userConcern = concern;
                        
                        // Save to localStorage
                        localStorage.setItem('guest_info_provided', 'true');
                        localStorage.setItem('guest_name', name);
                        localStorage.setItem('guest_contact', contact);
                        localStorage.setItem('guest_location', location);
                        localStorage.setItem('guest_concern', concern);
                        
                        // Update sessionStorage
                        sessionStorage.setItem('user_name', name);
                        sessionStorage.setItem('user_phone', contact);
                        sessionStorage.setItem('user_location', location);
                        sessionStorage.setItem('user_concern', concern);
                        
                        // Hide form and show chat interface
                        userInfoForm.style.display = 'none';
                        chatInterface.style.display = 'block';
                        
                        // Initialize chat with user info
                        await initializeChatWithUserInfo(database, userId, userName, userEmail, userPhone, isGuest, userLocation, userConcern);
                    });
                }
                return; // Don't initialize chat yet, wait for form submission
            }
        } else if (isGuest && guestInfoProvided) {
            // Guest has provided info before, use stored values
            if (storedGuestName) userName = storedGuestName;
            if (storedGuestContact) userPhone = storedGuestContact;
            sessionStorage.setItem('user_name', userName);
            sessionStorage.setItem('user_phone', userPhone);
            if (storedGuestLocation) sessionStorage.setItem('user_location', storedGuestLocation);
            if (storedGuestConcern) sessionStorage.setItem('user_concern', storedGuestConcern);
        }
        
        // Get user location and concern from sessionStorage if available
        const userLocation = sessionStorage.getItem('user_location') || localStorage.getItem('guest_location') || null;
        const userConcern = sessionStorage.getItem('user_concern') || localStorage.getItem('guest_concern') || null;
        
        // Get or create conversation - use persistent guest ID for guests
        const conversationsRef = database.ref('conversations');
        const userConversationsRef = conversationsRef.orderByChild('userId').equalTo(userId);
        
        let conversationId;
        const snapshot = await userConversationsRef.once('value');
        if (snapshot.exists()) {
            const conversations = snapshot.val();
            // Get the most recent active conversation
            const convEntries = Object.entries(conversations);
            const activeConv = convEntries.find(([key, val]) => val.status === 'active');
            if (activeConv) {
                conversationId = activeConv[0];
            } else {
                conversationId = convEntries[0][0]; // Use first conversation if no active
            }
        } else {
            const newConversationRef = conversationsRef.push({
                userId: userId,
                userName: userName,
                userEmail: userEmail || null,
                userPhone: userPhone || null,
                userLocation: userLocation || null,
                userConcern: userConcern || null,
                isGuest: isGuest,
                status: 'active',
                createdAt: firebase.database.ServerValue.TIMESTAMP,
                updatedAt: firebase.database.ServerValue.TIMESTAMP
            });
            conversationId = newConversationRef.key;
        }
        
        // Store conversation ID for later use
        sessionStorage.setItem('conversation_id', conversationId);
        window.currentConversationId = conversationId;
        
        // Continue with chat initialization
        continueChatInitialization(database, conversationId, userId, userName, userEmail, userPhone, isGuest, userLocation, userConcern);
        
        // Function to initialize chat with user info (called after form submission)
        async function initializeChatWithUserInfo(database, userId, userName, userEmail, userPhone, isGuest, userLocation, userConcern) {
            const conversationsRef = database.ref('conversations');
            const userConversationsRef = conversationsRef.orderByChild('userId').equalTo(userId);
            
            let conversationId;
            const snapshot = await userConversationsRef.once('value');
            if (snapshot.exists()) {
                const conversations = snapshot.val();
                const convEntries = Object.entries(conversations);
                const activeConv = convEntries.find(([key, val]) => val.status === 'active');
                if (activeConv) {
                    conversationId = activeConv[0];
                    // Update existing conversation with new info
                    await database.ref(`conversations/${conversationId}`).update({
                        userName: userName,
                        userPhone: userPhone,
                        userLocation: userLocation,
                        userConcern: userConcern,
                        updatedAt: firebase.database.ServerValue.TIMESTAMP
                    });
                } else {
                    conversationId = convEntries[0][0];
                }
            } else {
                const newConversationRef = conversationsRef.push({
                    userId: userId,
                    userName: userName,
                    userEmail: userEmail || null,
                    userPhone: userPhone || null,
                    userLocation: userLocation || null,
                    userConcern: userConcern || null,
                    isGuest: isGuest,
                    status: 'active',
                    createdAt: firebase.database.ServerValue.TIMESTAMP,
                    updatedAt: firebase.database.ServerValue.TIMESTAMP
                });
                conversationId = newConversationRef.key;
            }
            
            // Store conversation ID
            sessionStorage.setItem('conversation_id', conversationId);
            
            // Continue with chat initialization (load messages, etc.)
            continueChatInitialization(database, conversationId, userId, userName, userEmail, userPhone, isGuest, userLocation, userConcern);
        }
        
        // Function to continue chat initialization (load messages, setup listeners, etc.)
        function continueChatInitialization(database, conversationId, userId, userName, userEmail, userPhone, isGuest, userLocation, userConcern) {
            // Show chat interface and hide form if it exists
            const userInfoForm = document.getElementById('chatUserInfoForm');
            const chatInterface = document.getElementById('chatInterface');
            if (userInfoForm && chatInterface) {
                userInfoForm.style.display = 'none';
                chatInterface.style.display = 'block';
            }
        
            // Track loaded message IDs to prevent duplicates
            const loadedMessageIds = new Set();
            let lastMessageSenderType = null;
        
            // Load existing messages
            const messagesRef = database.ref(`messages/${conversationId}`);
            messagesRef.once('value', (snapshot) => {
            if (snapshot.exists()) {
                const messages = snapshot.val();
                const sortedMessages = Object.entries(messages).sort((a, b) => 
                    (a[1].timestamp || 0) - (b[1].timestamp || 0)
                );
                
                sortedMessages.forEach(([msgId, msg]) => {
                    loadedMessageIds.add(msgId);
                    addMessageToChat(msg.text, msg.senderType, msg.timestamp);
                    lastMessageSenderType = msg.senderType;
                });
                
                // Update status based on last message
                updateChatStatus(lastMessageSenderType);
            }
        });
        
            // Listen for new messages only
            messagesRef.on('child_added', (snapshot) => {
                const messageId = snapshot.key;
                if (!loadedMessageIds.has(messageId)) {
                    loadedMessageIds.add(messageId);
                    const message = snapshot.val();
                    addMessageToChat(message.text, message.senderType, message.timestamp);
                    lastMessageSenderType = message.senderType;
                    
                    // Update status when new message arrives
                    updateChatStatus(message.senderType);
                }
            });
            
            // Listen for conversation status updates
            const conversationRef = database.ref(`conversations/${conversationId}`);
            conversationRef.on('value', (snapshot) => {
                const conversation = snapshot.val();
                if (conversation) {
                    // Check if admin has accepted/assigned
                    if (conversation.assignedTo) {
                        updateChatStatus('admin_assigned');
                    } else if (conversation.status === 'active') {
                        // Check last message to determine status
                        if (lastMessageSenderType === 'user') {
                            updateChatStatus('waiting');
                        } else if (lastMessageSenderType === 'admin') {
                            updateChatStatus('admin_replied');
                        }
                    }
                }
            });
            
            // Update chat form to send via Firebase - use button click instead of form submit
            // Wait for DOM to be ready
            const chatSendBtn = document.getElementById('chatSendBtn');
            const chatInput = document.getElementById('chatInput');
            
            // Function to send message using MySQL
            async function sendChatMessage() {
            // Anonymous mode - check if all required info is provided
            const hasStoredName = localStorage.getItem('guest_name') || sessionStorage.getItem('user_name');
            const hasStoredContact = localStorage.getItem('guest_contact') || sessionStorage.getItem('user_phone');
            const hasStoredLocation = localStorage.getItem('guest_location') || sessionStorage.getItem('user_location');
            const hasStoredConcern = localStorage.getItem('guest_concern') || sessionStorage.getItem('user_concern');
            const hasAllRequiredInfo = hasStoredName && hasStoredContact && hasStoredLocation && hasStoredConcern;
            
            if (!hasAllRequiredInfo) {
                alert('Please fill in all required information (Name, Contact, Location, and Concern) before sending a message.');
                // Show the form
                checkAndShowUserInfoForm();
                return;
            }
            
            // Check if conversation is closed - if so, reset for new conversation
            const chatInput = document.getElementById('chatInput');
            if (chatInput && chatInput.disabled && chatInput.placeholder.includes('closed')) {
                // Reset chat for new conversation
                if (window.resetChatForNewConversation) {
                    window.resetChatForNewConversation();
                }
            }
            
            const text = chatInput ? chatInput.value.trim() : '';
            if (!text) {
                console.warn('Cannot send empty message');
                return;
            }
            
            // Disable send button temporarily
            if (chatSendBtn) {
                chatSendBtn.disabled = true;
                chatSendBtn.textContent = 'Sending...';
            }
            
            try {
                // Ensure modal stays open
                if (chatModal) {
                    chatModal.classList.add('chat-modal-open');
                    chatModal.setAttribute('aria-hidden', 'false');
                    document.body.style.overflow = 'hidden';
                }
                
                // Show waiting status immediately
                if (window.updateChatStatus) {
                    updateChatStatus('waiting');
                }
                
                // Get text from input
                const text = chatInput ? chatInput.value.trim() : '';
                if (!text) {
                    console.warn('No text to send');
                    return;
                }
                
                // Clear input immediately for better UX
                if (chatInput) {
                    chatInput.value = '';
                }
                
                // Add message to UI immediately
                if (window.addMessageToChat) {
                    window.addMessageToChat(text, 'user', Date.now());
                }
                
                // Use MySQL chat system - try both function names
                let success = false;
                let errorMessage = null;
                
                try {
                    if (window.sendChatMessageMySQL) {
                        console.log('Calling sendChatMessageMySQL with text:', text);
                        success = await window.sendChatMessageMySQL(text);
                        console.log('sendChatMessageMySQL result:', success);
                    } else if (window.sendMessage && typeof window.sendMessage === 'function') {
                        console.log('Calling sendMessage (fallback)');
                        success = await window.sendMessage(text);
                    } else {
                        console.error('MySQL chat system not available');
                        console.log('Available functions:', {
                            sendChatMessage: typeof window.sendChatMessage,
                            sendChatMessageMySQL: typeof window.sendChatMessageMySQL,
                            sendMessage: typeof window.sendMessage
                        });
                        errorMessage = 'Chat system is not ready. Please refresh the page.';
                    }
                } catch (error) {
                    console.error('Error calling send function:', error);
                    errorMessage = 'Error sending message: ' + error.message;
                    success = false;
                }
                
                if (success) {
                    console.log('Message sent successfully');
                    // Update status
                    if (window.updateChatStatus) {
                        window.updateChatStatus('waiting');
                    }
                } else {
                    console.error('Failed to send message', errorMessage);
                    // Remove the message from UI if send failed
                    const messages = document.querySelectorAll('.chat-message');
                    if (messages.length > 0) {
                        const lastMessage = messages[messages.length - 1];
                        // Only remove if it's the message we just added
                        if (lastMessage.textContent.includes(text)) {
                            lastMessage.remove();
                        }
                    }
                    if (errorMessage) {
                        alert(errorMessage);
                    } else {
                        alert('Failed to send message. Please try again.');
                    }
                    // Restore input text
                    if (chatInput) {
                        chatInput.value = text;
                    }
                }
            } catch (error) {
                console.error('Error sending message:', error);
                alert('Failed to send message. Please try again.');
            } finally {
                // Re-enable send button (check if conversation is closed)
                const conversationId = sessionStorage.getItem('conversation_id');
                if (chatSendBtn) {
                    if (conversationId) {
                        // Quick check if conversation is closed
                        fetch('../USERS/api/chat-get-conversation.php?conversationId=' + conversationId)
                            .then(res => res.json())
                            .then(data => {
                                if (data.success && data.status === 'closed') {
                                    chatSendBtn.disabled = true;
                                    chatSendBtn.textContent = 'Closed';
                                    if (chatInput) {
                                        chatInput.disabled = true;
                                        chatInput.placeholder = 'This conversation is closed';
                                    }
                                } else {
                                    chatSendBtn.disabled = false;
                                    chatSendBtn.textContent = 'Send';
                                    setTimeout(() => {
                                        if (chatInput) {
                                            chatInput.focus();
                                        }
                                    }, 100);
                                }
                            })
                            .catch(() => {
                                // If check fails, just re-enable
                                chatSendBtn.disabled = false;
                                chatSendBtn.textContent = 'Send';
                                setTimeout(() => {
                                    if (chatInput) {
                                        chatInput.focus();
                                    }
                                }, 100);
                            });
                    } else {
                        chatSendBtn.disabled = false;
                        chatSendBtn.textContent = 'Send';
                        setTimeout(() => {
                            if (chatInput) {
                                chatInput.focus();
                            }
                        }, 100);
                    }
                }
            }
            }
            
            // Make sendChatMessage available globally
            const originalSendChatMessage = sendChatMessage;
            window.sendChatMessage = async function() {
                // Check if user info is required and filled (for anonymous users)
                const userId = sessionStorage.getItem('user_id');
                const isLoggedIn = userId && 
                                  userId !== 'null' &&
                                  userId !== 'undefined' &&
                                  !userId.startsWith('guest_');
                
                if (!isLoggedIn) {
                    // Check if all required info is provided
                    const hasStoredName = localStorage.getItem('guest_name') || sessionStorage.getItem('user_name');
                    const hasStoredContact = localStorage.getItem('guest_contact') || sessionStorage.getItem('user_phone');
                    const hasStoredLocation = localStorage.getItem('guest_location') || sessionStorage.getItem('user_location');
                    const hasStoredConcern = localStorage.getItem('guest_concern') || sessionStorage.getItem('user_concern');
                    const hasAllRequiredInfo = hasStoredName && hasStoredContact && hasStoredLocation && hasStoredConcern;
                    
                    if (!hasAllRequiredInfo) {
                        alert('Please fill in all required information (Name, Contact, Location, and Concern) before sending a message.');
                        // Show the form
                        checkAndShowUserInfoForm();
                        return false;
                    }
                }
                
                // Call original function
                return await originalSendChatMessage();
            };
            
            // Also ensure sendChatMessageMySQL is available as an alias
            if (!window.sendChatMessageMySQL) {
                window.sendChatMessageMySQL = async function(text) {
                    console.log('sendChatMessageMySQL wrapper called with text:', text);
                    // If text is provided, use it; otherwise get from input
                    if (!text) {
                        const input = document.getElementById('chatInput');
                        text = input ? input.value.trim() : '';
                    }
                    if (!text) {
                        console.warn('No text to send');
                        return false;
                    }
                    // Call the sendChatMessage function which will handle validation
                    return await window.sendChatMessage();
                };
            }
            
            // Make attachSendButtonHandlers available globally
            window.attachSendButtonHandlers = attachSendButtonHandlers;
            
            // Make attachCloseButtonHandler available globally
            window.attachCloseButtonHandler = attachCloseButtonHandler;
            
            // Function to attach send button handlers
            function attachSendButtonHandlers() {
                console.log('=== ATTACHING SEND BUTTON HANDLERS ===');
                const sendBtn = document.getElementById('chatSendBtn');
                const input = document.getElementById('chatInput');
                
                if (!sendBtn) {
                    console.error('Chat send button not found!');
                    console.log('Available elements:', {
                        chatForm: !!document.getElementById('chatForm'),
                        chatInterface: !!document.getElementById('chatInterface'),
                        chatModal: !!document.getElementById('chatModal')
                    });
                    // Retry after a short delay
                    setTimeout(() => {
                        if (document.getElementById('chatSendBtn')) {
                            console.log('Retrying attachSendButtonHandlers...');
                            attachSendButtonHandlers();
                        }
                    }, 300);
                    return false;
                }
                
                if (!input) {
                    console.error('Chat input not found!');
                    return false;
                }
                
                console.log('Send button found:', sendBtn);
                console.log('Input found:', input);
                console.log('Button current state:', {
                    disabled: sendBtn.disabled,
                    display: window.getComputedStyle(sendBtn).display,
                    visibility: window.getComputedStyle(sendBtn).visibility,
                    pointerEvents: window.getComputedStyle(sendBtn).pointerEvents
                });
                
                // Ensure button is clickable BEFORE cloning
                sendBtn.style.pointerEvents = 'auto';
                sendBtn.style.cursor = 'pointer';
                sendBtn.style.touchAction = 'manipulation';
                sendBtn.style.zIndex = '10001';
                sendBtn.style.position = 'relative';
                sendBtn.disabled = false;
                sendBtn.type = 'button'; // Ensure it's a button, not submit
                
                // Remove old listeners by cloning
                const newBtn = sendBtn.cloneNode(true);
                sendBtn.parentNode.replaceChild(newBtn, sendBtn);
                const freshBtn = document.getElementById('chatSendBtn');
                
                if (!freshBtn) {
                    console.error('Failed to get fresh button after cloning');
                    return false;
                }
                
                console.log('Fresh button obtained:', freshBtn);
                
                // Ensure fresh button is clickable
                freshBtn.style.pointerEvents = 'auto !important';
                freshBtn.style.cursor = 'pointer !important';
                freshBtn.style.touchAction = 'manipulation';
                freshBtn.style.zIndex = '10001';
                freshBtn.style.position = 'relative';
                freshBtn.disabled = false;
                freshBtn.type = 'button';
                
                // Attach click handler - use addEventListener for better compatibility
                console.log('Attaching click handler to send button');
                
                // Remove any existing click handlers first
                const handleSendClick = async function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    e.stopImmediatePropagation();
                    console.log('=== SEND BUTTON CLICKED ===');
                    
                    // Check if button is disabled
                    if (freshBtn.disabled) {
                        console.warn('Button is disabled, ignoring click');
                        return false;
                    }
                    
                    const input = document.getElementById('chatInput');
                    const text = input ? input.value.trim() : '';
                    
                    if (!text) {
                        console.warn('No text to send');
                        return false;
                    }
                    
                    // Disable button while sending
                    freshBtn.disabled = true;
                    freshBtn.textContent = 'Sending...';
                    
                    try {
                        // Directly call sendChatMessageMySQL if available
                        if (window.sendChatMessageMySQL) {
                            console.log('Calling sendChatMessageMySQL with text:', text);
                            const success = await window.sendChatMessageMySQL(text);
                            console.log('sendChatMessageMySQL result:', success);
                            
                            if (success) {
                                console.log('Message sent successfully');
                                // Clear input on success
                                if (input) {
                                    input.value = '';
                                }
                            } else {
                                console.error('Failed to send message');
                                alert('Failed to send message. Please try again.');
                                // Restore input on failure
                                if (input) {
                                    input.value = text;
                                }
                            }
                        } else if (window.sendChatMessage) {
                            console.log('Using sendChatMessage wrapper');
                            // Call the wrapper function
                            await window.sendChatMessage();
                        } else {
                            console.error('No send function available');
                            console.log('Available functions:', {
                                sendChatMessage: typeof window.sendChatMessage,
                                sendChatMessageMySQL: typeof window.sendChatMessageMySQL,
                                sendMessage: typeof window.sendMessage
                            });
                            alert('Chat system is not ready. Please refresh the page.');
                            // Restore input
                            if (input) {
                                input.value = text;
                            }
                        }
                    } catch (error) {
                        console.error('Error in send button handler:', error);
                        alert('Error sending message: ' + error.message);
                        // Restore input
                        if (input) {
                            input.value = text;
                        }
                    } finally {
                        // Always re-enable button (unless conversation is closed)
                        const conversationId = sessionStorage.getItem('conversation_id');
                        if (conversationId) {
                            // Quick check if conversation is closed
                            try {
                                const res = await fetch('../USERS/api/chat-get-conversation.php?conversationId=' + conversationId);
                                const data = await res.json();
                                if (data.success && data.status === 'closed') {
                                    freshBtn.disabled = true;
                                    freshBtn.textContent = 'Closed';
                                    if (input) {
                                        input.disabled = true;
                                        input.placeholder = 'This conversation is closed';
                                    }
                                } else {
                                    freshBtn.disabled = false;
                                    freshBtn.textContent = 'Send';
                                }
                            } catch (err) {
                                // If check fails, just re-enable
                                freshBtn.disabled = false;
                                freshBtn.textContent = 'Send';
                            }
                        } else {
                            freshBtn.disabled = false;
                            freshBtn.textContent = 'Send';
                        }
                    }
                    
                    return false;
                };
                
                // Use multiple methods to ensure the handler works
                // Method 1: addEventListener
                freshBtn.addEventListener('click', handleSendClick, { capture: false, passive: false });
                
                // Method 2: Direct onclick as fallback
                freshBtn.onclick = handleSendClick;
                
                // Method 3: Also attach touchend for mobile devices
                freshBtn.addEventListener('touchend', async function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    console.log('Send button touched');
                    handleSendClick(e);
                }, { passive: false });
                
                // Method 4: Use event delegation on parent as ultimate fallback
                const chatForm = document.getElementById('chatForm');
                if (chatForm && !chatForm.hasAttribute('data-send-delegation')) {
                    chatForm.setAttribute('data-send-delegation', 'true');
                    chatForm.addEventListener('click', function(e) {
                        if (e.target && e.target.id === 'chatSendBtn') {
                            console.log('Send button clicked via delegation');
                            handleSendClick(e);
                        }
                    }, { capture: true });
                }
                
                // Clone and reattach input handlers
                const newInput = input.cloneNode(true);
                input.parentNode.replaceChild(newInput, input);
                const freshInput = document.getElementById('chatInput');
                
                freshInput.style.pointerEvents = 'auto';
                freshInput.style.cursor = 'text';
                
                freshInput.addEventListener('keypress', async function(e) {
                    if (e.key === 'Enter' && !e.shiftKey) {
                        e.preventDefault();
                        e.stopPropagation();
                        console.log('Enter key pressed');
                        
                        const text = this.value.trim();
                        if (!text) {
                            return;
                        }
                        
                        // Try sendChatMessage first, then sendChatMessageMySQL
                        if (window.sendChatMessage) {
                            window.sendChatMessage();
                        } else if (window.sendChatMessageMySQL) {
                            try {
                                const success = await window.sendChatMessageMySQL(text);
                                if (!success) {
                                    console.error('Failed to send message');
                                }
                            } catch (error) {
                                console.error('Error calling sendChatMessageMySQL:', error);
                            }
                        } else {
                            console.error('No send function available');
                            alert('Chat system is not ready. Please refresh the page.');
                        }
                    }
                });
                
                freshInput.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter' && !e.shiftKey) {
                        e.preventDefault();
                    }
                });
                
                // Don't prevent default on mousedown/touchstart as it can interfere with click events
                // Just log for debugging
                freshBtn.addEventListener('mousedown', function(e) {
                    console.log('Send button mousedown');
                });
                
                freshBtn.addEventListener('touchstart', function(e) {
                    console.log('Send button touchstart');
                });
                
                // Test if button is actually clickable
                console.log('Send button styles:', {
                    pointerEvents: window.getComputedStyle(freshBtn).pointerEvents,
                    cursor: window.getComputedStyle(freshBtn).cursor,
                    zIndex: window.getComputedStyle(freshBtn).zIndex,
                    disabled: freshBtn.disabled,
                    type: freshBtn.type,
                    display: window.getComputedStyle(freshBtn).display,
                    visibility: window.getComputedStyle(freshBtn).visibility,
                    opacity: window.getComputedStyle(freshBtn).opacity
                });
                
                // Add a simple test handler to verify button is clickable
                freshBtn.addEventListener('mousedown', function(e) {
                    console.log('TEST: Send button mousedown detected!', e);
                });
                
                freshBtn.addEventListener('mouseup', function(e) {
                    console.log('TEST: Send button mouseup detected!', e);
                });
                
                // Verify handler was attached
                const hasClickHandler = freshBtn.onclick !== null || 
                    (freshBtn.addEventListener && true);
                console.log('Button has click handler:', hasClickHandler);
                console.log('Button onclick type:', typeof freshBtn.onclick);
                
                // Force button to be visible and clickable
                freshBtn.style.display = 'block';
                freshBtn.style.visibility = 'visible';
                freshBtn.style.opacity = '1';
                freshBtn.style.pointerEvents = 'auto';
                freshBtn.style.cursor = 'pointer';
                freshBtn.removeAttribute('disabled');
                freshBtn.disabled = false;
                
                console.log('Chat send button handlers attached successfully');
                console.log('Final button state:', {
                    disabled: freshBtn.disabled,
                    onclick: typeof freshBtn.onclick,
                    style: freshBtn.style.cssText
                });
                
                return true;
            }
            
            // Function to attach close button handler
            function attachCloseButtonHandler() {
                const closeBtn = document.getElementById('chatEndConversationBtn');
                if (!closeBtn) {
                    console.log('Close button not found');
                    return false;
                }
                
                // Remove old listeners by cloning
                const newBtn = closeBtn.cloneNode(true);
                closeBtn.parentNode.replaceChild(newBtn, closeBtn);
                const freshBtn = document.getElementById('chatEndConversationBtn');
                
                if (!freshBtn) {
                    console.error('Failed to get fresh close button');
                    return false;
                }
                
                // Always ensure button is visible and enabled
                freshBtn.style.display = 'inline-flex';
                freshBtn.style.visibility = 'visible';
                freshBtn.style.pointerEvents = 'auto';
                freshBtn.style.cursor = 'pointer';
                freshBtn.style.touchAction = 'manipulation';
                freshBtn.style.opacity = '1';
                freshBtn.disabled = false;
                freshBtn.removeAttribute('disabled');
                
                freshBtn.onclick = async function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    e.stopImmediatePropagation();
                    await closeConversationEndToEnd();
                    return false;
                };
                
                console.log('Close button handler attached');
                return true;
            }
            
            // Make attachCloseButtonHandler globally available
            window.attachCloseButtonHandler = attachCloseButtonHandler;
            
            // Function to ensure close button is always available
            function ensureCloseButtonAvailable() {
                const closeBtn = document.getElementById('chatEndConversationBtn');
                if (closeBtn) {
                    // Always ensure button is visible and enabled
                    closeBtn.style.display = 'inline-flex';
                    closeBtn.style.visibility = 'visible';
                    closeBtn.style.pointerEvents = 'auto';
                    closeBtn.style.cursor = 'pointer';
                    closeBtn.style.opacity = '1';
                    closeBtn.disabled = false;
                    closeBtn.removeAttribute('disabled');
                    
                    // Re-attach handler if needed
                    if (window.attachCloseButtonHandler) {
                        window.attachCloseButtonHandler();
                    }
                }
            }
            
            // Make ensureCloseButtonAvailable globally available
            window.ensureCloseButtonAvailable = ensureCloseButtonAvailable;
            
            // Attach handlers immediately
            attachSendButtonHandlers();
            attachCloseButtonHandler();
            
            // Periodically ensure close button is available (in case it gets disabled)
            setInterval(() => {
                const chatInterface = document.getElementById('chatInterface');
                if (chatInterface && chatInterface.style.display !== 'none') {
                    ensureCloseButtonAvailable();
                }
            }, 2000);
            
            // Initialize "Start New Conversation" button
            const startNewBtn = document.getElementById('startNewConversationBtn');
            if (startNewBtn) {
                startNewBtn.onclick = function() {
                    if (window.startNewConversation) {
                        window.startNewConversation();
                    } else if (window.resetChatForNewConversation) {
                        window.resetChatForNewConversation();
                    }
                };
            }
            
            // Also attach when modal opens (in case elements weren't ready)
            if (chatModal) {
                const modalObserver = new MutationObserver(function() {
                    if (chatModal.classList.contains('chat-modal-open')) {
                        setTimeout(() => {
                            if (!attachSendButtonHandlers()) {
                                // Retry after a short delay
                                setTimeout(attachSendButtonHandlers, 200);
                            }
                            attachCloseButtonHandler();
                        }, 50);
                    }
                });
                modalObserver.observe(chatModal, { attributes: true, attributeFilter: ['class'] });
            }
            
            // Prevent any form submission if form still exists
            if (chatForm) {
                chatForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    e.stopImmediatePropagation();
                    if (window.sendChatMessage) {
                        window.sendChatMessage();
                    }
                    return false;
                });
            }
            
            // Listen to chat queue to know when admin accepts (real-time updates)
            const queueRef = database.ref('chat_queue').orderByChild('conversationId').equalTo(conversationId);
            queueRef.on('value', (snapshot) => {
                if (snapshot.exists()) {
                    const queueItems = snapshot.val();
                    const pendingItems = Object.values(queueItems).filter(item => item.status === 'pending');
                    const acceptedItems = Object.values(queueItems).filter(item => item.status === 'accepted');
                    
                    if (acceptedItems.length > 0) {
                        updateChatStatus('admin_assigned');
                    } else if (pendingItems.length > 0 && lastMessageSenderType === 'user') {
                        updateChatStatus('waiting');
                    }
                } else if (lastMessageSenderType === 'user') {
                    // No queue items but user sent last message - show waiting
                    updateChatStatus('waiting');
                }
            });
            
            // Function to update chat status indicator
            function updateChatStatus(status) {
                const statusIndicator = document.getElementById('chatStatusIndicator');
                const statusText = document.getElementById('chatStatusText');
                
                if (!statusIndicator || !statusText) return;
                
                switch(status) {
                    case 'waiting':
                    case 'user':
                        statusIndicator.style.display = 'flex';
                        statusIndicator.style.background = 'rgba(255, 193, 7, 0.1)';
                        statusIndicator.style.borderLeftColor = '#ffc107';
                        statusIndicator.style.color = '#856404';
                        statusText.innerHTML = '<i class="fas fa-clock"></i> Waiting for admin reply...';
                        break;
                    case 'admin_assigned':
                        statusIndicator.style.display = 'flex';
                        statusIndicator.style.background = 'rgba(33, 150, 243, 0.1)';
                        statusIndicator.style.borderLeftColor = '#2196f3';
                        statusIndicator.style.color = '#1565c0';
                        statusText.innerHTML = '<i class="fas fa-user-check"></i> Admin is reviewing your message...';
                        break;
                    case 'admin_replied':
                    case 'admin':
                        statusIndicator.style.display = 'flex';
                        statusIndicator.style.background = 'rgba(76, 175, 80, 0.1)';
                        statusIndicator.style.borderLeftColor = '#4caf50';
                        statusIndicator.style.color = '#2e7d32';
                        statusText.innerHTML = '<i class="fas fa-check-circle"></i> Admin has replied';
                        // Hide after 3 seconds
                        setTimeout(() => {
                            if (statusIndicator) {
                                statusIndicator.style.display = 'none';
                            }
                        }, 3000);
                        // Ensure close button is always available after admin replies
                        if (window.attachCloseButtonHandler) {
                            setTimeout(() => {
                                window.attachCloseButtonHandler();
                            }, 100);
                        }
                        break;
                    default:
                        statusIndicator.style.display = 'none';
                }
            }
            
            // Make updateChatStatus available globally
            window.updateChatStatus = updateChatStatus;
            
            chatInitialized = true;
            window.chatInitialized = true;
            
            console.log('Firebase chat initialization complete');
            console.log('Conversation ID:', conversationId);
            console.log('Database available:', !!database);
            console.log('Global database available:', !!window.chatDatabase);
            
            // Re-attach button handlers after initialization
            setTimeout(() => {
                attachSendButtonHandlers();
            }, 100);
        }
    }
    
    // Expose initFirebaseChat globally
    window.initFirebaseChat = initFirebaseChat;
    
    function addMessageToChat(text, senderType, timestamp, messageId = null) {
        if (!chatMessages) return;
        
        // Check if message already exists (by ID)
        if (messageId) {
            const existing = chatMessages.querySelector(`.chat-message[data-message-id="${messageId}"]`);
            if (existing) {
                return; // Message already exists, don't add again
            }
        }
        
        // Remove system message if it exists and this is the first real message
        const systemMsg = chatMessages.querySelector('.chat-message-system');
        if (systemMsg && (senderType === 'user' || senderType === 'admin')) {
            systemMsg.remove();
        }
        
        const msg = document.createElement('div');
        msg.className = `chat-message chat-message-${senderType}`;
        if (messageId) {
            msg.setAttribute('data-message-id', messageId);
        }
        
        const time = timestamp ? new Date(timestamp).toLocaleTimeString() : new Date().toLocaleTimeString();
        const senderName = senderType === 'user' ? 'You' : 'Admin';
        
        // Escape HTML to prevent XSS
        const escapeHtml = (str) => {
            const div = document.createElement('div');
            div.textContent = str;
            return div.innerHTML;
        };
        
        msg.innerHTML = `<strong style="color: var(--text-color) !important;">${escapeHtml(senderName)}:</strong> <span style="color: var(--text-color) !important;">${escapeHtml(text)}</span> <small style="display: block; font-size: 0.8em; opacity: 0.7; margin-top: 0.25rem; color: var(--text-muted) !important;">${time}</small>`;
        chatMessages.appendChild(msg);
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }
    
    // Make openChat function globally available for emergency-call.php
    window.openEmergencyChat = function() {
        if (chatFab) {
            chatFab.click();
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Chat Not Available',
                text: 'Chat feature is not available. Please refresh the page.',
                confirmButtonText: 'OK'
            });
        }
    };
});
</script>











