<?php
/**
 * Reusable Sidebar Component
 * Include this file in your pages where you want a sidebar: <?php include 'sidebar/sidebar.php'; ?>
 * 
 * Features:
 * - Responsive design with mobile toggle
 * - Admin-style navigation
 * - Collapsible sections
 * - Dark mode support
 * - Multiple layout options
 */

// Determine base path for links based on current directory
$currentDir = basename(dirname($_SERVER['PHP_SELF']));
$sidebarBase = ($currentDir == 'multilingual-support') ? '../' : '';

if (!function_exists('sidebarCurrentRoutePath')) {
    function sidebarCurrentRoutePath() {
        $requestUri = $_SERVER['REQUEST_URI'] ?? '';
        $path = parse_url($requestUri, PHP_URL_PATH);
        if (!is_string($path) || $path === '') {
            $path = $_SERVER['PHP_SELF'] ?? '';
        }
        return strtolower(rtrim($path, '/'));
    }
}

if (!function_exists('sidebarRouteContains')) {
    function sidebarRouteContains($needle) {
        return strpos(sidebarCurrentRoutePath(), strtolower($needle)) !== false;
    }
}
?>

<!-- Sidebar Component -->
<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <div class="sidebar-brand">
            <div class="brand-logo">
                <img src="<?php echo $sidebarBase; ?>images/logo.svg" alt="" class="logo-img">
            </div>
        </div>
    </div>
    
    <div class="sidebar-content">
        <!-- Navigation Menu -->
        <nav class="sidebar-nav">
            <!-- Admin Section -->
            <div class="sidebar-section">
                <h3 class="sidebar-section-title">Admin</h3>
                <ul class="sidebar-menu">
                    <!-- Dashboard -->
                    <?php $isActive = basename($_SERVER['PHP_SELF']) == 'dashboard.php'; ?>
                    <li class="sidebar-menu-item">
                        <a href="<?php echo $sidebarBase; ?>dashboard.php" class="sidebar-link sidebar-accent-dashboard <?php echo $isActive ? 'active' : ''; ?>">
                            <i class="fas fa-home sidebar-icon" aria-hidden="true"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    
                    <!-- Users -->
                    <?php $isActive = basename($_SERVER['PHP_SELF']) == 'users.php'; ?>
                    <li class="sidebar-menu-item">
                        <a href="<?php echo $sidebarBase; ?>users.php" class="sidebar-link sidebar-accent-users <?php echo $isActive ? 'active' : ''; ?>">
                            <i class="fas fa-users sidebar-icon" aria-hidden="true"></i>
                            <span>Users</span>
                        </a>
                    </li>
                    
                    <!-- Admin Approvals -->
                    <?php $isActive = basename($_SERVER['PHP_SELF']) == 'admin-approvals.php'; ?>
                    <li class="sidebar-menu-item">
                        <a href="<?php echo $sidebarBase; ?>admin-approvals.php" class="sidebar-link sidebar-accent-approvals <?php echo $isActive ? 'active' : ''; ?>">
                            <i class="fas fa-user-check sidebar-icon" aria-hidden="true"></i>
                            <span>Admin Approvals</span>
                        </a>
                    </li>

                    <!-- My Profile -->
                    <?php $isActive = basename($_SERVER['PHP_SELF']) == 'profile.php'; ?>
                    <li class="sidebar-menu-item">
                        <a href="<?php echo $sidebarBase; ?>profile.php" class="sidebar-link sidebar-accent-profile <?php echo $isActive ? 'active' : ''; ?>">
                            <i class="fas fa-user-circle sidebar-icon" aria-hidden="true"></i>
                            <span>My Profile</span>
                        </a>
                    </li>

                    <!-- Settings -->
                    <?php $isActive = basename($_SERVER['PHP_SELF']) == 'general-settings.php'; ?>
                    <li class="sidebar-menu-item">
                        <a href="<?php echo $sidebarBase; ?>general-settings.php" class="sidebar-link sidebar-accent-settings <?php echo $isActive ? 'active' : ''; ?>">
                            <i class="fas fa-cog sidebar-icon" aria-hidden="true"></i>
                            <span>Settings</span>
                        </a>
                    </li>
                </ul>
            </div>
            
            <!-- Emergency Communication System Section -->
            <div class="sidebar-section">
                <h3 class="sidebar-section-title">Emergency Communication</h3>
                <ul class="sidebar-menu">
                    <!-- Mass Notification -->
                    <?php $isActive = basename($_SERVER['PHP_SELF']) == 'mass-notification.php'; ?>
                    <li class="sidebar-menu-item">
                        <a href="<?php echo $sidebarBase; ?>mass-notification.php" class="sidebar-link sidebar-accent-mass <?php echo $isActive ? 'active' : ''; ?>">
                            <i class="fas fa-broadcast-tower sidebar-icon" aria-hidden="true"></i>
                            <span>Mass Notification</span>
                        </a>
                    </li>
                    
                    <!-- Alert Categorization -->
                    <?php $isActive = basename($_SERVER['PHP_SELF']) == 'alert-categorization.php'; ?>
                    <li class="sidebar-menu-item">
                        <a href="<?php echo $sidebarBase; ?>alert-categorization.php" class="sidebar-link sidebar-accent-categorization <?php echo $isActive ? 'active' : ''; ?>">
                            <i class="fas fa-tags sidebar-icon" aria-hidden="true"></i>
                            <span>Alert Categorization</span>
                        </a>
                    </li>
                    
                    <!-- Emergency Reports -->
                    <?php
                    $isReportsPage = (
                        basename($_SERVER['PHP_SELF']) == 'two-way-communication.php'
                        || basename($_SERVER['PHP_SELF']) == 'two-way-communication-new.php'
                        || sidebarRouteContains('/sidebar/two-way-comm/citizen')
                    );
                    $isGeneralEnquiriesPage = sidebarRouteContains('/sidebar/two-way-comm/general');
                    $isEmergencyCallPage = sidebarRouteContains('/sidebar/two-way-comm/call');
                    $isEmergencyReportsActive = $isReportsPage || $isGeneralEnquiriesPage || $isEmergencyCallPage;
                    ?>
                    <li class="sidebar-menu-item">
                        <a href="javascript:void(0)" class="sidebar-link sidebar-submenu-toggle sidebar-accent-2way <?php echo $isEmergencyReportsActive ? 'active' : ''; ?>">
                            <i class="fas fa-file-medical-alt sidebar-icon" aria-hidden="true"></i>
                            <span>Emergency Reports</span>
                            <i class="fas fa-chevron-down submenu-icon" aria-hidden="true"></i>
                        </a>
                        <ul class="sidebar-submenu <?php echo $isEmergencyReportsActive ? 'sidebar-submenu-open' : ''; ?>">
                            <li class="sidebar-menu-item">
                                <a href="<?php echo $sidebarBase; ?>two-way-comm/citizen/" class="sidebar-link sidebar-accent-2way <?php echo $isReportsPage ? 'active' : ''; ?>">
                                    <i class="fas fa-clipboard-list sidebar-icon" aria-hidden="true"></i>
                                    <span class="sidebar-link-label">Reports</span>
                                    <span class="sidebar-realtime-badge" id="sidebarReportsUnreadBadge"
                                        data-label-singular="new report" data-label-plural="new reports"
                                        data-active-module="<?php echo $isReportsPage ? '1' : '0'; ?>" hidden></span>
                                </a>
                            </li>
                            <li class="sidebar-menu-item">
                                <a href="<?php echo $sidebarBase; ?>two-way-comm/general/" class="sidebar-link sidebar-accent-2way <?php echo $isGeneralEnquiriesPage ? 'active' : ''; ?>">
                                    <i class="fas fa-comments sidebar-icon" aria-hidden="true"></i>
                                    <span class="sidebar-link-label">General Enquiries</span>
                                    <span class="sidebar-realtime-badge" id="sidebarGeneralUnreadBadge"
                                        data-label-singular="new enquiry" data-label-plural="new enquiries"
                                        data-active-module="<?php echo $isGeneralEnquiriesPage ? '1' : '0'; ?>" hidden></span>
                                </a>
                            </li>
                            <li class="sidebar-menu-item">
                                <a href="<?php echo $sidebarBase; ?>two-way-comm/call/" class="sidebar-link sidebar-accent-2way <?php echo $isEmergencyCallPage ? 'active' : ''; ?>">
                                    <i class="fas fa-phone-volume sidebar-icon" aria-hidden="true"></i>
                                    <span class="sidebar-link-label">Emergency Call</span>
                                    <span class="sidebar-realtime-badge" id="sidebarEmergencyCallBadge"
                                        data-label-singular="new call" data-label-plural="new calls"
                                        data-active-module="<?php echo $isEmergencyCallPage ? '1' : '0'; ?>" hidden></span>
                                </a>
                            </li>
                        </ul>
                    </li>
                    <!-- Automated Warnings -->
                    <?php 
                    $isAutoWarningsActive = (basename($_SERVER['PHP_SELF']) == 'automated-warnings.php' || basename($_SERVER['PHP_SELF']) == 'automated-warnings-analytics.php' || basename($_SERVER['PHP_SELF']) == 'weather-monitoring.php' || basename($_SERVER['PHP_SELF']) == 'earthquake-monitoring.php');
                    ?>
                    <li class="sidebar-menu-item">
                        <a href="<?php echo $sidebarBase; ?>automated-warnings.php" class="sidebar-link sidebar-submenu-toggle sidebar-accent-auto <?php echo $isAutoWarningsActive ? 'active' : ''; ?>">
                            <i class="fas fa-plug sidebar-icon" aria-hidden="true"></i>
                            <span>Automated Warnings</span>
                            <i class="fas fa-chevron-down submenu-icon" aria-hidden="true"></i>
                        </a>
                        <ul class="sidebar-submenu <?php echo $isAutoWarningsActive ? 'sidebar-submenu-open' : ''; ?>">
                            <li class="sidebar-menu-item">
                                <a href="<?php echo $sidebarBase; ?>automated-warnings.php" class="sidebar-link sidebar-accent-auto <?php echo basename($_SERVER['PHP_SELF']) == 'automated-warnings.php' ? 'active' : ''; ?>">
                                    <i class="fas fa-cog sidebar-icon" aria-hidden="true"></i>
                                    <span>Settings</span>
                                </a>
                            </li>
                            <li class="sidebar-menu-item">
                                <a href="<?php echo $sidebarBase; ?>automated-warnings-analytics.php" class="sidebar-link sidebar-accent-auto <?php echo basename($_SERVER['PHP_SELF']) == 'automated-warnings-analytics.php' ? 'active' : ''; ?>">
                                    <i class="fas fa-chart-line sidebar-icon" aria-hidden="true"></i>
                                    <span>Analytics</span>
                                </a>
                            </li>
                            <li class="sidebar-menu-item">
                                <a href="<?php echo $sidebarBase; ?>weather-monitoring.php" class="sidebar-link sidebar-accent-weather <?php echo basename($_SERVER['PHP_SELF']) == 'weather-monitoring.php' ? 'active' : ''; ?>">
                                    <i class="fas fa-cloud-sun sidebar-icon" aria-hidden="true"></i>
                                    <span>Weather Monitoring</span>
                                </a>
                            </li>
                            <li class="sidebar-menu-item">
                                <a href="<?php echo $sidebarBase; ?>earthquake-monitoring.php" class="sidebar-link sidebar-accent-earthquake <?php echo basename($_SERVER['PHP_SELF']) == 'earthquake-monitoring.php' ? 'active' : ''; ?>">
                                    <i class="fas fa-mountain sidebar-icon" aria-hidden="true"></i>
                                    <span>Earthquake Monitoring</span>
                                </a>
                            </li>
                        </ul>
                    </li>
                    
                    <!-- Multilingual Support -->
                    <?php $isMultiLangActive = (strpos($_SERVER['PHP_SELF'], 'multilingual-support') !== false); ?>
                    <li class="sidebar-menu-item">
                        <a href="javascript:void(0)" class="sidebar-link sidebar-submenu-toggle sidebar-accent-multilang <?php echo $isMultiLangActive ? 'active' : ''; ?>">
                            <i class="fas fa-language sidebar-icon" aria-hidden="true"></i>
                            <span>Multilingual Support</span>
                            <i class="fas fa-chevron-down submenu-icon" aria-hidden="true"></i>
                        </a>
                        <ul class="sidebar-submenu <?php echo $isMultiLangActive ? 'sidebar-submenu-open' : ''; ?>">
                            <li class="sidebar-menu-item">
                                <a href="<?php echo $sidebarBase; ?>multilingual-support/overview.php" class="sidebar-link sidebar-accent-overview <?php echo basename($_SERVER['PHP_SELF']) == 'overview.php' ? 'active' : ''; ?>">
                                    <i class="fas fa-info-circle sidebar-icon" aria-hidden="true"></i>
                                    <span>Overview</span>
                                </a>
                            </li>
                            <li class="sidebar-menu-item">
                                <a href="<?php echo $sidebarBase; ?>multilingual-support/language-management.php" class="sidebar-link sidebar-accent-language <?php echo basename($_SERVER['PHP_SELF']) == 'language-management.php' ? 'active' : ''; ?>">
                                    <i class="fas fa-list sidebar-icon" aria-hidden="true"></i>
                                    <span>Language Management</span>
                                </a>
                            </li>
                        </ul>
                    </li>
                    
                    <!-- Citizen Subscriptions -->
                    <?php $isActive = basename($_SERVER['PHP_SELF']) == 'citizen-subscriptions.php'; ?>
                    <li class="sidebar-menu-item">
                        <a href="<?php echo $sidebarBase; ?>citizen-subscriptions.php" class="sidebar-link sidebar-accent-citizen <?php echo $isActive ? 'active' : ''; ?>">
                            <i class="fas fa-users sidebar-icon" aria-hidden="true"></i>
                            <span>Citizen Subscriptions</span>
                        </a>
                    </li>
                    
                    <!-- Audit Trail -->
                    <?php $isActive = basename($_SERVER['PHP_SELF']) == 'audit-trail.php'; ?>
                    <li class="sidebar-menu-item">
                        <a href="<?php echo $sidebarBase; ?>audit-trail.php" class="sidebar-link sidebar-accent-audit <?php echo $isActive ? 'active' : ''; ?>">
                            <i class="fas fa-history sidebar-icon" aria-hidden="true"></i>
                            <span>Audit Trail</span>
                        </a>
                    </li>

                    <!-- Trash Bin -->
                    <?php $isActive = basename($_SERVER['PHP_SELF']) == 'trash-bin.php'; ?>
                    <li class="sidebar-menu-item">
                        <a href="<?php echo $sidebarBase; ?>trash-bin.php" class="sidebar-link sidebar-accent-audit <?php echo $isActive ? 'active' : ''; ?>">
                            <i class="fas fa-trash-alt sidebar-icon" aria-hidden="true"></i>
                            <span>Trash Bin</span>
                        </a>
                    </li>
                </ul>
            </div>
            
        </nav>
    </div>
</aside>

<!-- Sidebar Overlay for mobile -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Sidebar functionality
    const sidebar = document.getElementById('sidebar');
    const sidebarOverlay = document.getElementById('sidebarOverlay');
    
    // Toggle sidebar
    function toggleSidebar() {
        sidebar.classList.toggle('sidebar-open');
        sidebarOverlay.classList.toggle('sidebar-overlay-open');
        document.body.classList.toggle('sidebar-open');
    }
    
    // Close sidebar
    function closeSidebar() {
        sidebar.classList.remove('sidebar-open');
        sidebarOverlay.classList.remove('sidebar-overlay-open');
        document.body.classList.remove('sidebar-open');
    }

    // Expose functions globally so other scripts
    // can trigger the sidebar without duplicating logic.
    window.sidebarToggle = toggleSidebar;
    window.sidebarClose = closeSidebar;
    
    // Close sidebar when clicking overlay
    if (sidebarOverlay) {
        sidebarOverlay.addEventListener('click', closeSidebar);
    }
    
    // Close sidebar on Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && sidebar.classList.contains('sidebar-open')) {
            closeSidebar();
        }
    });
    
    // Handle submenu toggles
    const submenuToggles = document.querySelectorAll('.sidebar-submenu-toggle');
    submenuToggles.forEach(toggle => {
        toggle.addEventListener('click', function(e) {
            e.preventDefault();
            const submenu = this.nextElementSibling;
            const icon = this.querySelector('.submenu-icon');
            const submenuKey = this.getAttribute('data-sidebar-dropdown') || '';
            
            if (submenu) {
                const isOpen = submenu.classList.contains('sidebar-submenu-open');
                submenu.classList.toggle('sidebar-submenu-open');
                this.classList.toggle('active', !isOpen);
                this.setAttribute('aria-expanded', submenu.classList.contains('sidebar-submenu-open') ? 'true' : 'false');
                
                // Toggle icon based on new state
                if (icon) {
                    if (submenu.classList.contains('sidebar-submenu-open')) {
                        // Now open - show up chevron
                        icon.classList.remove('fa-chevron-down');
                        icon.classList.add('fa-chevron-up');
                    } else {
                        // Now closed - show down chevron
                        icon.classList.remove('fa-chevron-up');
                        icon.classList.add('fa-chevron-down');
                    }
                }

                if (submenuKey === 'twc') {
                    try {
                        localStorage.setItem('admin_sidebar_twc_open', submenu.classList.contains('sidebar-submenu-open') ? '1' : '0');
                    } catch (error) {}
                }
            }
        });
    });
    
    // Auto-open submenu if it contains active item
    const activeLinks = document.querySelectorAll('.sidebar-submenu .sidebar-link.active');
    activeLinks.forEach(activeLink => {
        const submenu = activeLink.closest('.sidebar-submenu');
        const toggle = submenu ? submenu.previousElementSibling : null;
        
        if (submenu && toggle && toggle.classList.contains('sidebar-submenu-toggle')) {
            submenu.classList.add('sidebar-submenu-open');
            toggle.classList.add('active');
            
            const icon = toggle.querySelector('.submenu-icon');
            if (icon) {
                icon.classList.remove('fa-chevron-down');
                icon.classList.add('fa-chevron-up');
            }
        }
    });

    // Restore the TWC dropdown state unless a child route is already active.
    const twcToggle = document.querySelector('a[data-sidebar-dropdown="twc"]');
    if (twcToggle) {
        const twcSubmenu = twcToggle.nextElementSibling;
        if (twcSubmenu && twcSubmenu.classList.contains('sidebar-submenu')) {
            const twcHasActiveChild = twcSubmenu.querySelector('.sidebar-link.active');
            let twcShouldOpen = twcHasActiveChild;
            if (!twcShouldOpen) {
                try {
                    twcShouldOpen = localStorage.getItem('admin_sidebar_twc_open') === '1';
                } catch (error) {
                    twcShouldOpen = false;
                }
            }

            if (twcShouldOpen) {
                twcSubmenu.classList.add('sidebar-submenu-open');
                twcToggle.classList.add('active');
                twcToggle.setAttribute('aria-expanded', 'true');
                const icon = twcToggle.querySelector('.submenu-icon');
                if (icon) {
                    icon.classList.remove('fa-chevron-down');
                    icon.classList.add('fa-chevron-up');
                }
            } else {
                twcToggle.setAttribute('aria-expanded', 'false');
            }
        }
    }

    // Also check if the parent link should be active (for automated-warnings submenu)
    const automatedWarningsToggle = document.querySelector('a[href="automated-warnings.php"].sidebar-submenu-toggle');
    if (automatedWarningsToggle) {
        const submenu = automatedWarningsToggle.nextElementSibling;
        if (submenu && submenu.classList.contains('sidebar-submenu')) {
            const hasActiveChild = submenu.querySelector('.sidebar-link.active');
            if (hasActiveChild) {
                submenu.classList.add('sidebar-submenu-open');
                automatedWarningsToggle.classList.add('active');
                const icon = automatedWarningsToggle.querySelector('.submenu-icon');
                if (icon) {
                    icon.classList.remove('fa-chevron-down');
                    icon.classList.add('fa-chevron-up');
                }
            }
        }
    }
});
</script>


<style>
.sidebar-call-toast {
    position: fixed;
    right: 22px;
    bottom: 24px;
    width: min(340px, calc(100vw - 32px));
    z-index: 99999;
    background: #0f1b2d;
    color: #fff;
    border: 1px solid rgba(81, 169, 166, 0.45);
    border-left: 5px solid #dc2626;
    border-radius: 12px;
    box-shadow: 0 18px 50px rgba(0,0,0,.28);
    padding: 14px;
    display: none;
    gap: 12px;
    align-items: flex-start;
}
.sidebar-call-toast.show { display: flex; animation: sidebarCallToastIn .18s ease-out; }
.sidebar-call-toast__icon {
    width: 38px;
    height: 38px;
    border-radius: 10px;
    background: rgba(220,38,38,.18);
    display: grid;
    place-items: center;
    color: #fecaca;
    flex: 0 0 auto;
}
.sidebar-call-toast__body { flex: 1; min-width: 0; }
.sidebar-call-toast__title { font-weight: 900; margin-bottom: 3px; }
.sidebar-call-toast__text { font-size: 12px; opacity: .82; line-height: 1.35; }
.sidebar-call-toast__actions { margin-top: 10px; display: flex; gap: 8px; }
.sidebar-call-toast__actions a,
.sidebar-call-toast__actions button {
    border: 0;
    border-radius: 8px;
    padding: 8px 10px;
    font-weight: 800;
    cursor: pointer;
    text-decoration: none;
    font-size: 12px;
}
.sidebar-call-toast__actions a { background: #4f9592; color: #fff; }
.sidebar-call-toast__actions button { background: rgba(255,255,255,.1); color: #fff; }
@keyframes sidebarCallToastIn { from { transform: translateY(10px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
</style>
<div class="sidebar-call-toast" id="sidebarEmergencyCallToast" role="status" aria-live="polite">
    <div class="sidebar-call-toast__icon"><i class="fas fa-phone-alt"></i></div>
    <div class="sidebar-call-toast__body">
        <div class="sidebar-call-toast__title">Incoming emergency call</div>
        <div class="sidebar-call-toast__text" id="sidebarEmergencyCallToastText">A caller is waiting in the emergency call queue.</div>
        <div class="sidebar-call-toast__actions">
            <a href="<?php echo $sidebarBase; ?>two-way-comm/call/">Open Call Queue</a>
            <button type="button" id="sidebarEmergencyCallToastDismiss">Dismiss</button>
        </div>
    </div>
</div>
<script>
(function() {
    const badge = document.getElementById('sidebarEmergencyCallBadge');
    const toast = document.getElementById('sidebarEmergencyCallToast');
    const toastText = document.getElementById('sidebarEmergencyCallToastText');
    const dismiss = document.getElementById('sidebarEmergencyCallToastDismiss');
    if (!badge) return;
    const isCallPage = badge.dataset.activeModule === '1';
    const seenKey = 'alertaraqc_seen_emergency_call_toasts';
    const ownerPrefix = 'alertaraqc_active_call_';
    let seenCalls = new Set();
    try { seenCalls = new Set(JSON.parse(sessionStorage.getItem(seenKey) || '[]')); } catch (e) {}

    function adminHasActiveCallLock() {
        try {
            const now = Date.now();
            for (let i = 0; i < localStorage.length; i += 1) {
                const key = localStorage.key(i) || '';
                if (!key.startsWith(ownerPrefix)) continue;
                const item = JSON.parse(localStorage.getItem(key) || '{}');
                const startedAt = Number(item.startedAt || 0);
                if (item.callId && startedAt && now - startedAt < 4 * 60 * 60 * 1000) return true;
            }
        } catch (e) {}
        return false;
    }

    function setBadge(count) {
        const total = Number(count || 0);
        if (isCallPage || total <= 0) {
            badge.hidden = true;
            badge.textContent = '';
            return;
        }
        badge.hidden = false;
        badge.textContent = total + ' ' + (total === 1 ? badge.dataset.labelSingular : badge.dataset.labelPlural);
    }

    function showCallToast(call) {
        if (isCallPage || adminHasActiveCallLock() || !toast || !call || !call.callId) return;
        if (seenCalls.has(call.callId)) return;
        seenCalls.add(call.callId);
        try { sessionStorage.setItem(seenKey, JSON.stringify(Array.from(seenCalls).slice(-100))); } catch (e) {}
        const callerName = call.caller && call.caller.name ? call.caller.name : 'Emergency Call User';
        const location = call.location && (call.location.address || call.location.formatted || call.location.text) ? (call.location.address || call.location.formatted || call.location.text) : 'Location pending';
        if (toastText) toastText.textContent = callerName + ' is waiting. ' + location;
        toast.classList.add('show');
        try {
            const AudioCtx = window.AudioContext || window.webkitAudioContext;
            if (AudioCtx) {
                const ctx = new AudioCtx();
                const osc = ctx.createOscillator();
                const gain = ctx.createGain();
                osc.type = 'sine';
                osc.frequency.value = 880;
                gain.gain.value = 0.035;
                osc.connect(gain);
                gain.connect(ctx.destination);
                osc.start();
                setTimeout(() => { try { osc.stop(); ctx.close(); } catch (e) {} }, 160);
            }
        } catch (e) {}
    }

    if (dismiss && toast) dismiss.addEventListener('click', () => toast.classList.remove('show'));

    function connectCallLobby() {
        if (!window.io) return;
        const socket = window.io(window.location.origin, { path: '/socket.io', transports: ['websocket', 'polling'], timeout: 5000, reconnection: true });
        socket.on('connect', () => socket.emit('join', 'emergency-lobby'));
        socket.on('call-queue', payload => setBadge(Array.isArray(payload && payload.open) ? payload.open.length : 0));
        socket.on('call-created', payload => {
            const call = payload && payload.call ? payload.call : null;
            if (call) showCallToast(call);
        });
    }

    if (window.io) {
        connectCallLobby();
    } else {
        const script = document.createElement('script');
        script.src = '/socket.io/socket.io.js';
        script.onload = connectCallLobby;
        script.onerror = function() {};
        document.head.appendChild(script);
    }
})();
</script>
<script src="<?php echo $sidebarBase; ?>../assets/shared/js/draft-persist.js?v=<?php echo filemtime(__DIR__ . '/../../assets/shared/js/draft-persist.js'); ?>"></script>

