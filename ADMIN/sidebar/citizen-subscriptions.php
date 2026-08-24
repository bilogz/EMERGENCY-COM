<?php
/**
 * Citizen Subscription and Alert Preferences Page
 * Manage citizen subscriptions, alert categories, channels, and preferences
 */

// Start session and check authentication
session_start();

// Check if user is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../login.php');
    exit();
}

$pageTitle = 'Citizen Subscription and Alert Preferences';
$adminRole = $_SESSION['admin_role'] ?? 'staff';
$canEdit = in_array($adminRole, ['super_admin', 'admin']);
$canDelete = ($adminRole === 'super_admin');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <link rel="icon" type="image/x-icon" href="images/favicon.ico">
    <link rel="stylesheet" href="css/global.css?v=<?php echo filemtime(__DIR__ . '/css/global.css'); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="css/sidebar.css?v=<?php echo filemtime(__DIR__ . '/css/sidebar.css'); ?>">
    <link rel="stylesheet" href="css/admin-header.css">
    <link rel="stylesheet" href="css/buttons.css">
    <link rel="stylesheet" href="css/forms.css">
    <link rel="stylesheet" href="css/datatables.css">
    <link rel="stylesheet" href="css/hero.css">
    <link rel="stylesheet" href="css/sidebar-footer.css">
    <link rel="stylesheet" href="css/modules.css">
    <link rel="stylesheet" href="css/module-citizen-subscriptions.css?v=<?php echo filemtime(__DIR__ . '/css/module-citizen-subscriptions.css'); ?>">
</head>
<body>
    <!-- Include Sidebar Component -->
    <?php include 'includes/sidebar.php'; ?>

    <!-- Include Admin Header Component -->
    <?php include 'includes/admin-header.php'; ?>
    
    <!-- ===================================
       MAIN CONTENT - Citizen Subscriptions
       =================================== -->
    <div class="main-content">
        <div class="main-container">
            <div class="title">
                <nav class="breadcrumb" aria-label="Breadcrumb">
                    <ol class="breadcrumb-list">
                        <li class="breadcrumb-item">
                            <a href="dashboard.php" class="breadcrumb-link">
                                <span>Dashboard</span>
                            </a>
                        </li>
                        <li class="breadcrumb-item active" aria-current="page">
                            <span>Citizen Subscriptions</span>
                        </li>
                    </ol>
                </nav>
                <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 1rem;">
                    <div>
                        <h1><i class="fas fa-users-cog" style="color: var(--primary-color-1); margin-right: 0.5rem;"></i> Citizen Subscriptions</h1>
                        <p>Real-time citizen alert preferences, hazard opt-ins, push notification devices, and communication channels.</p>
                    </div>
                    <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                        <?php if ($canEdit): ?>
                        <button type="button" class="btn btn-primary" onclick="openAddSubscriberModal()">
                            <i class="fas fa-user-plus" style="margin-right: 0.35rem;"></i> Add Subscriber
                        </button>
                        <?php endif; ?>
                        <button type="button" class="btn btn-secondary" onclick="exportSubscribersCsv()">
                            <i class="fas fa-file-csv" style="margin-right: 0.35rem;"></i> Export CSV
                        </button>
                        <button type="button" class="btn btn-secondary" id="exportSubscribersPdfBtn" onclick="exportSubscribersPdf()">
                            <i class="fas fa-file-pdf" style="margin-right: 0.35rem;"></i> Export PDF
                        </button>
                    </div>
                </div>
            </div>
            
            <div class="sub-container">
                <div class="page-content">
                    
                    <!-- Unified KPI Dashboard -->
                    <div class="cs-kpi-grid">
                        <!-- Total Subscribers -->
                        <div class="cs-kpi-card" style="--kpi-accent: #3a7675; --kpi-icon-bg: rgba(58, 118, 117, 0.12);">
                            <div class="cs-kpi-header">
                                <span class="cs-kpi-title">Total Citizens</span>
                                <div class="cs-kpi-icon"><i class="fas fa-users"></i></div>
                            </div>
                            <div class="cs-kpi-value" id="kpiTotalSubscribers">0</div>
                            <div class="cs-kpi-subtext">
                                <span class="cs-kpi-badge success" id="kpiActivePercent">100% Active</span>
                                <span>Enrolled in City Alerts</span>
                            </div>
                        </div>

                        <!-- Active Subscriptions -->
                        <div class="cs-kpi-card" style="--kpi-accent: #0284c7; --kpi-icon-bg: rgba(2, 132, 199, 0.12);">
                            <div class="cs-kpi-header">
                                <span class="cs-kpi-title">Active Reach</span>
                                <div class="cs-kpi-icon"><i class="fas fa-broadcast-tower"></i></div>
                            </div>
                            <div class="cs-kpi-value" id="kpiActiveSubscribers">0</div>
                            <div class="cs-kpi-subtext">
                                <span id="kpiInactiveCount">0 inactive</span>
                                <span>• Ready for Broadcast</span>
                            </div>
                        </div>

                        <!-- Hazard Category Opt-ins -->
                        <div class="cs-kpi-card" style="--kpi-accent: #ea580c; --kpi-icon-bg: rgba(234, 88, 12, 0.12);">
                            <div class="cs-kpi-header">
                                <span class="cs-kpi-title">Hazard Opt-ins</span>
                                <div class="cs-kpi-icon"><i class="fas fa-layer-group"></i></div>
                            </div>
                            <div class="cs-kpi-breakdown">
                                <span class="cs-kpi-pill"><i class="fas fa-cloud-showers-heavy" style="color:#0284c7;"></i> Weather: <strong id="kpiWeatherCount">0</strong></span>
                                <span class="cs-kpi-pill"><i class="fas fa-mountain" style="color:#b91c1c;"></i> Quake: <strong id="kpiEarthquakeCount">0</strong></span>
                                <span class="cs-kpi-pill"><i class="fas fa-fire" style="color:#ea580c;"></i> Fire: <strong id="kpiFireCount">0</strong></span>
                                <span class="cs-kpi-pill"><i class="fas fa-heart-pulse" style="color:#16a34a;"></i> Medical: <strong id="kpiMedicalCount">0</strong></span>
                            </div>
                        </div>

                        <!-- Multi-Channel Delivery -->
                        <div class="cs-kpi-card" style="--kpi-accent: #9333ea; --kpi-icon-bg: rgba(147, 51, 234, 0.12);">
                            <div class="cs-kpi-header">
                                <span class="cs-kpi-title">Delivery Channels</span>
                                <div class="cs-kpi-icon"><i class="fas fa-paper-plane"></i></div>
                            </div>
                            <div class="cs-channel-reach-row">
                                <div class="cs-channel-stat">
                                    <span class="cs-channel-stat-num" id="kpiPushReach">0</span>
                                    <span class="cs-channel-stat-lbl"><i class="fas fa-mobile-alt" style="color:#0284c7;"></i> Push</span>
                                </div>
                                <div class="cs-channel-stat">
                                    <span class="cs-channel-stat-num" id="kpiEmailReach">0</span>
                                    <span class="cs-channel-stat-lbl"><i class="fas fa-envelope" style="color:#16a34a;"></i> Email</span>
                                </div>
                                <div class="cs-channel-stat">
                                    <span class="cs-channel-stat-num" id="kpiSmsReach">0</span>
                                    <span class="cs-channel-stat-lbl"><i class="fas fa-comment-sms" style="color:#ea580c;"></i> SMS</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Search & Multi-Filter Control Toolbar -->
                    <div class="cs-toolbar-card">
                        <div class="cs-toolbar-top">
                            <div class="cs-search-box">
                                <i class="fas fa-search"></i>
                                <input type="text" id="searchSubscribers" class="cs-search-input" placeholder="Search by name, email, phone, or barangay...">
                                <button type="button" id="searchClearBtn" class="cs-search-clear" title="Clear Search">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                            <div class="cs-toolbar-actions">
                                <button type="button" class="btn btn-secondary" onclick="refreshSubscriberData()" title="Reload table and metrics">
                                    <i class="fas fa-sync-alt" id="refreshBtnIcon"></i> Refresh
                                </button>
                            </div>
                        </div>
                        <div class="cs-toolbar-bottom">
                            <!-- Status Filter Pills -->
                            <div class="cs-filter-pills">
                                <span style="font-size: 0.82rem; font-weight: 700; color: var(--text-secondary-1); margin-right: 0.35rem;">Status:</span>
                                <button type="button" class="cs-filter-pill active" data-status="" onclick="setFilterStatus('')">
                                    All <span id="filterCountAll" class="cs-count-tag" style="padding: 0.1rem 0.4rem; font-size: 0.7rem;">0</span>
                                </button>
                                <button type="button" class="cs-filter-pill" data-status="active" onclick="setFilterStatus('active')">
                                    <i class="fas fa-circle" style="color: #16a34a; font-size: 0.55rem;"></i> Active
                                </button>
                                <button type="button" class="cs-filter-pill" data-status="inactive" onclick="setFilterStatus('inactive')">
                                    <i class="fas fa-circle" style="color: #94a3b8; font-size: 0.55rem;"></i> Inactive
                                </button>
                            </div>

                            <!-- Dropdown Filters -->
                            <div class="cs-filter-selects">
                                <select id="filterCategory" class="cs-select-compact" onchange="applyFilters()">
                                    <option value="">All Categories</option>
                                    <option value="weather">Weather & Rain</option>
                                    <option value="earthquake">Earthquake</option>
                                    <option value="fire">Fire Emergency</option>
                                    <option value="flood">Flood Alert</option>
                                    <option value="medical">Medical / Health</option>
                                    <option value="general">General Advisory</option>
                                </select>

                                <select id="filterChannel" class="cs-select-compact" onchange="applyFilters()">
                                    <option value="">All Channels</option>
                                    <option value="push">Mobile Push</option>
                                    <option value="email">Email</option>
                                    <option value="sms">SMS</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Subscribers List Table -->
                    <div class="cs-table-card">
                        <div class="cs-table-header">
                            <h3>
                                <i class="fas fa-list-check" style="color: var(--primary-color-1);"></i>
                                Subscribed Citizens
                                <span class="cs-count-tag" id="tableRecordCountBadge">0 Total</span>
                            </h3>
                            <div id="tableLoadingIndicator" style="display: none; font-size: 0.85rem; color: var(--primary-color-1); font-weight: 600;">
                                <i class="fas fa-spinner fa-spin"></i> Updating...
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="data-table" id="subscribersTable">
                                <thead>
                                    <tr>
                                        <th style="width: 50px;">ID</th>
                                        <th>Citizen Identity</th>
                                        <th>Contact Details</th>
                                        <th>Barangay / Address</th>
                                        <th>Device & Activity</th>
                                        <th>Alert Categories</th>
                                        <th>Channels</th>
                                        <th>Lang</th>
                                        <th>Status</th>
                                        <th style="width: 120px; text-align: center;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Dynamic rows loaded via API -->
                                </tbody>
                            </table>
                        </div>
                        <div id="subscribersLazyLoadSentinel" style="text-align:center; padding:1rem; color:var(--text-secondary-1); font-weight:600; font-size:0.85rem;">
                            Loading subscribers...
                        </div>
                    </div>

                    <!-- ============================================
                         View/Edit Subscriber Modal
                         ============================================ -->
                    <div id="subscriptionModal" class="modal" style="display: none;">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h2 id="modalTitle">
                                    <i class="fas fa-user-shield" style="color: var(--primary-color-1);"></i> Subscriber Details & Preferences
                                </h2>
                                <button class="modal-close" type="button" onclick="closeSubscriptionModal()" aria-label="Close">&times;</button>
                            </div>

                            <!-- Profile Header Glass Banner -->
                            <div class="cs-modal-profile-header">
                                <div class="cs-modal-profile-left">
                                    <div class="cs-modal-avatar" id="modalUserAvatar">JD</div>
                                    <div class="cs-modal-user-title">
                                        <h3 id="modalUserName">Citizen Name</h3>
                                        <div class="cs-modal-user-meta">
                                            <span class="cs-meta-pill" id="modalUserEmail"><i class="fas fa-envelope" style="color: var(--primary-color-1);"></i> email@example.com</span>
                                            <span class="cs-meta-pill" id="modalUserPhone"><i class="fas fa-phone" style="color: var(--primary-color-1);"></i> +639000000000</span>
                                        </div>
                                    </div>
                                </div>
                                <div>
                                    <span class="badge active" id="modalUserStatusBadge">Active</span>
                                </div>
                            </div>
                            
                            <div class="modal-body">
                                <!-- Modal Navigation Tabs -->
                                <div class="modal-tabs">
                                    <button type="button" class="tab-btn active" onclick="switchTab('user-info')">
                                        <i class="fas fa-id-card"></i> Citizen Profile
                                    </button>
                                    <button type="button" class="tab-btn" onclick="switchTab('subscription')">
                                        <i class="fas fa-bell"></i> Alert Preferences
                                    </button>
                                    <button type="button" class="tab-btn" onclick="switchTab('devices')">
                                        <i class="fas fa-mobile-alt"></i> Registered Devices
                                    </button>
                                    <button type="button" class="tab-btn" onclick="switchTab('location')">
                                        <i class="fas fa-map-marker-alt"></i> Location History
                                    </button>
                                    <button type="button" class="tab-btn" onclick="switchTab('activity')">
                                        <i class="fas fa-history"></i> Activity Logs
                                    </button>
                                </div>
                                
                                <!-- Tab 1: User Information -->
                                <div id="tab-user-info" class="tab-content">
                                    <div id="userInfoDetails" class="info-grid">
                                        <!-- Injected via JS -->
                                    </div>
                                </div>
                                
                                <!-- Tab 2: Subscription Preferences Form -->
                                <div id="tab-subscription" class="tab-content" style="display: none;">
                                    <form id="subscriptionForm">
                                        <input type="hidden" id="subscriberId" name="subscriber_id">
                                        
                                        <!-- Alert Categories Grid -->
                                        <div class="form-group" style="margin-bottom: 1.25rem;">
                                            <label style="font-weight: 700; margin-bottom: 0.35rem; display: block;">
                                                <i class="fas fa-tags" style="color: var(--primary-color-1); margin-right: 0.35rem;"></i>
                                                Opted Alert Categories
                                            </label>
                                            <div class="cs-category-toggle-grid">
                                                <label class="cs-category-toggle-card">
                                                    <input type="checkbox" name="categories[]" value="weather">
                                                    <span><i class="fas fa-cloud-showers-heavy" style="color: #0284c7;"></i> Weather & Storm</span>
                                                </label>
                                                <label class="cs-category-toggle-card">
                                                    <input type="checkbox" name="categories[]" value="earthquake">
                                                    <span><i class="fas fa-mountain" style="color: #b91c1c;"></i> Earthquake</span>
                                                </label>
                                                <label class="cs-category-toggle-card">
                                                    <input type="checkbox" name="categories[]" value="fire">
                                                    <span><i class="fas fa-fire" style="color: #ea580c;"></i> Fire Emergency</span>
                                                </label>
                                                <label class="cs-category-toggle-card">
                                                    <input type="checkbox" name="categories[]" value="flood">
                                                    <span><i class="fas fa-water" style="color: #0ea5e9;"></i> Flash Flood</span>
                                                </label>
                                                <label class="cs-category-toggle-card">
                                                    <input type="checkbox" name="categories[]" value="medical">
                                                    <span><i class="fas fa-heart-pulse" style="color: #16a34a;"></i> Medical / Health</span>
                                                </label>
                                                <label class="cs-category-toggle-card">
                                                    <input type="checkbox" name="categories[]" value="general">
                                                    <span><i class="fas fa-bullhorn" style="color: #3a7675;"></i> General Advisory</span>
                                                </label>
                                            </div>
                                        </div>
                                        
                                        <!-- Notification Channels -->
                                        <div class="form-group" style="margin-bottom: 1.25rem;">
                                            <label style="font-weight: 700; margin-bottom: 0.35rem; display: block;">
                                                <i class="fas fa-paper-plane" style="color: var(--primary-color-1); margin-right: 0.35rem;"></i>
                                                Active Notification Channels
                                            </label>
                                            <div class="cs-channel-toggle-grid">
                                                <label class="cs-channel-toggle-card">
                                                    <input type="checkbox" name="channels[]" value="push">
                                                    <span><i class="fas fa-mobile-alt" style="color: #0284c7;"></i> Mobile App Push</span>
                                                </label>
                                                <label class="cs-channel-toggle-card">
                                                    <input type="checkbox" name="channels[]" value="email">
                                                    <span><i class="fas fa-envelope" style="color: #16a34a;"></i> Email Broadcast</span>
                                                </label>
                                                <label class="cs-channel-toggle-card">
                                                    <input type="checkbox" name="channels[]" value="sms">
                                                    <span><i class="fas fa-comment-sms" style="color: #ea580c;"></i> SMS Alerts</span>
                                                </label>
                                            </div>
                                        </div>

                                        <div class="form-row">
                                            <div class="form-group">
                                                <label for="preferredLanguage"><i class="fas fa-language" style="color: var(--primary-color-1);"></i> Preferred Language</label>
                                                <select id="preferredLanguage" name="preferred_language">
                                                    <option value="en">🇺🇸 English</option>
                                                    <option value="tl">🇵🇭 Filipino (Tagalog)</option>
                                                </select>
                                            </div>
                                            <div class="form-group">
                                                <label for="subscriptionStatus"><i class="fas fa-toggle-on" style="color: var(--primary-color-1);"></i> Subscription Status</label>
                                                <select id="subscriptionStatus" name="status">
                                                    <option value="active">Active (Receives Broadcasts)</option>
                                                    <option value="inactive">Inactive (Muted)</option>
                                                    <option value="suspended">Suspended</option>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="form-actions" style="margin-top: 1.5rem; display: flex; justify-content: flex-end; gap: 0.5rem;">
                                            <button type="button" class="btn btn-secondary" onclick="closeSubscriptionModal()">Cancel</button>
                                            <?php if ($canEdit): ?>
                                            <button type="submit" class="btn btn-primary" id="saveSubscriptionBtn">
                                                <i class="fas fa-save" style="margin-right: 0.35rem;"></i> Save Preferences
                                            </button>
                                            <?php endif; ?>
                                        </div>
                                    </form>
                                </div>
                                
                                <!-- Tab 3: Devices -->
                                <div id="tab-devices" class="tab-content" style="display: none;">
                                    <div id="devicesList">
                                        <!-- Injected via JS -->
                                    </div>
                                </div>
                                
                                <!-- Tab 4: Location -->
                                <div id="tab-location" class="tab-content" style="display: none;">
                                    <div id="locationsList">
                                        <!-- Injected via JS -->
                                    </div>
                                </div>
                                
                                <!-- Tab 5: Activity -->
                                <div id="tab-activity" class="tab-content" style="display: none;">
                                    <div id="activitiesList">
                                        <!-- Injected via JS -->
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ============================================
                         Quick Add Subscriber Modal
                         ============================================ -->
                    <?php if ($canEdit): ?>
                    <div id="addSubscriberModal" class="modal" style="display: none;">
                        <div class="modal-content" style="max-width: 600px; border-radius: 16px;">
                            <div class="modal-header" style="display: flex; justify-content: space-between; align-items: center;">
                                <h2 style="margin:0; font-size:1.15rem;">
                                    <i class="fas fa-user-plus" style="color: var(--primary-color-1); margin-right: 0.35rem;"></i> Register Citizen Subscriber
                                </h2>
                                <button class="modal-close" onclick="closeAddSubscriberModal()" style="background:none; border:none; font-size:1.4rem; cursor:pointer; color:var(--text-secondary-1);">&times;</button>
                            </div>
                            <div class="modal-body" style="padding: 1.5rem;">
                                <form id="addSubscriberForm">
                                    <div class="form-group">
                                        <label for="addName">Citizen Full Name <span style="color:#ef4444;">*</span></label>
                                        <input type="text" id="addName" name="name" placeholder="e.g. Juan Dela Cruz" required>
                                    </div>
                                    <div class="form-row">
                                        <div class="form-group">
                                            <label for="addPhone">Mobile Phone <span style="color:#ef4444;">*</span></label>
                                            <input type="text" id="addPhone" name="phone" placeholder="+639171234567" required>
                                        </div>
                                        <div class="form-group">
                                            <label for="addEmail">Email Address</label>
                                            <input type="email" id="addEmail" name="email" placeholder="juan@example.com">
                                        </div>
                                    </div>
                                    <div class="form-row">
                                        <div class="form-group">
                                            <label for="addBarangay">Barangay</label>
                                            <input type="text" id="addBarangay" name="barangay" placeholder="e.g. Central, Amihan">
                                        </div>
                                        <div class="form-group">
                                            <label for="addAddress">Full Address</label>
                                            <input type="text" id="addAddress" name="address" placeholder="House #, Street name">
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label for="addLanguage">Preferred Language</label>
                                        <select id="addLanguage" name="preferred_language">
                                            <option value="en">🇺🇸 English</option>
                                            <option value="tl">🇵🇭 Filipino (Tagalog)</option>
                                        </select>
                                    </div>
                                    <div class="form-actions" style="margin-top: 1.5rem; display: flex; justify-content: flex-end; gap: 0.5rem;">
                                        <button type="button" class="btn btn-secondary" onclick="closeAddSubscriberModal()">Cancel</button>
                                        <button type="submit" class="btn btn-primary" id="submitAddSubscriberBtn">
                                            <i class="fas fa-check" style="margin-right: 0.35rem;"></i> Register Citizen
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script>
        const SUBSCRIBERS_PAGE_SIZE = 25;
        const canEdit = <?php echo $canEdit ? 'true' : 'false'; ?>;
        const canDelete = <?php echo $canDelete ? 'true' : 'false'; ?>;
        
        let subscribersPage = 1;
        let subscribersTotalPages = 1;
        let subscribersTotal = 0;
        let subscribersLoading = false;
        let subscribersHasMore = true;
        let subscriberSearchTimer = null;
        let currentStatusFilter = '';

        // Generate Avatar Initials
        function getInitials(name) {
            if (!name) return 'CS';
            const parts = name.trim().split(/\s+/);
            if (parts.length === 1) return parts[0].substring(0, 2).toUpperCase();
            return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
        }

        // Relative time helper
        function formatRelativeTime(dateStr) {
            if (!dateStr) return 'Never';
            const date = new Date(dateStr);
            if (isNaN(date.getTime())) return 'Recently';
            const now = new Date();
            const diffSec = Math.floor((now - date) / 1000);
            if (diffSec < 60) return 'Just now';
            if (diffSec < 3600) return `${Math.floor(diffSec / 60)}m ago`;
            if (diffSec < 86400) return `${Math.floor(diffSec / 3600)}h ago`;
            if (diffSec < 604800) return `${Math.floor(diffSec / 86400)}d ago`;
            return date.toLocaleDateString();
        }

        // Format Category Badges
        function renderCategoryBadges(categories) {
            if (!categories || !categories.length) {
                return '<span class="cs-cat-badge">None</span>';
            }
            const catMap = {
                weather: { icon: 'fa-cloud-showers-heavy', label: 'Weather', cls: 'weather' },
                earthquake: { icon: 'fa-mountain', label: 'Quake', cls: 'earthquake' },
                fire: { icon: 'fa-fire', label: 'Fire', cls: 'fire' },
                flood: { icon: 'fa-water', label: 'Flood', cls: 'flood' },
                medical: { icon: 'fa-heart-pulse', label: 'Medical', cls: 'medical' },
                general: { icon: 'fa-bullhorn', label: 'General', cls: 'general' }
            };

            return categories.map(c => {
                const key = c.toLowerCase().trim();
                const meta = catMap[key] || { icon: 'fa-tag', label: c, cls: 'general' };
                return `<span class="cs-cat-badge ${meta.cls}"><i class="fas ${meta.icon}"></i> ${meta.label}</span>`;
            }).join(' ');
        }

        // Format Channel Badges
        function renderChannelChips(channels) {
            const hasSms = channels && channels.includes('sms');
            const hasEmail = channels && channels.includes('email');
            const hasPush = channels && channels.includes('push');

            return `
                <div class="cs-channels-cell">
                    <span class="cs-channel-chip ${hasPush ? 'active push' : ''}" title="${hasPush ? 'Push Notification Active' : 'Push Notification Off'}">
                        <i class="fas fa-mobile-alt"></i>
                    </span>
                    <span class="cs-channel-chip ${hasEmail ? 'active email' : ''}" title="${hasEmail ? 'Email Broadcast Active' : 'Email Off'}">
                        <i class="fas fa-envelope"></i>
                    </span>
                    <span class="cs-channel-chip ${hasSms ? 'active sms' : ''}" title="${hasSms ? 'SMS Active' : 'SMS Off'}">
                        <i class="fas fa-comment-sms"></i>
                    </span>
                </div>
            `;
        }

        // Load Subscribers with Filtering
        async function loadSubscribers(reset = true) {
            if (subscribersLoading) return;

            const tbody = document.querySelector('#subscribersTable tbody');
            const search = document.getElementById('searchSubscribers')?.value.trim() || '';
            const category = document.getElementById('filterCategory')?.value || '';
            const channel = document.getElementById('filterChannel')?.value || '';
            const loadingIndicator = document.getElementById('tableLoadingIndicator');
            
            if (!tbody) return;

            if (reset) {
                subscribersPage = 1;
                subscribersTotalPages = 1;
                subscribersTotal = 0;
                subscribersHasMore = true;
                tbody.innerHTML = '<tr><td colspan="10" style="text-align:center; padding: 2rem;"><i class="fas fa-spinner fa-spin" style="font-size:1.5rem; color:var(--primary-color-1);"></i><br><span style="margin-top:0.5rem; display:inline-block;">Loading subscribers...</span></td></tr>';
            }

            subscribersLoading = true;
            if (loadingIndicator) loadingIndicator.style.display = 'block';
            updateSubscribersLazyLoadStatus();

            try {
                const params = new URLSearchParams({
                    action: 'list',
                    page: String(subscribersPage),
                    limit: String(SUBSCRIBERS_PAGE_SIZE)
                });
                if (search) params.set('q', search);
                if (currentStatusFilter) params.set('status', currentStatusFilter);
                if (category) params.set('category', category);
                if (channel) params.set('channel', channel);

                const response = await fetch('../api/citizen-subscriptions.php?' + params.toString());
                const data = await response.json();
                
                if (!response.ok || !data.success) {
                    throw new Error(data.message || 'Unable to load subscribers.');
                }

                if (reset) tbody.innerHTML = '';
                const rows = Array.isArray(data.subscribers) ? data.subscribers : [];
                rows.forEach(renderSubscriberRow);

                const pagination = data.pagination || {};
                subscribersPage = Number(pagination.page || subscribersPage);
                subscribersTotalPages = Math.max(1, Number(pagination.total_pages || 1));
                subscribersTotal = Number(pagination.total || rows.length);
                subscribersHasMore = subscribersPage < subscribersTotalPages;

                const countBadge = document.getElementById('tableRecordCountBadge');
                if (countBadge) countBadge.textContent = `${subscribersTotal} Total`;

                if (tbody.children.length === 0) {
                    tbody.innerHTML = `
                        <tr>
                            <td colspan="10">
                                <div class="cs-empty-state">
                                    <i class="fas fa-user-slash"></i>
                                    <h4>No subscribers found</h4>
                                    <p>${search ? `No citizen matches query "${search}".` : 'No subscribers match the current filter selection.'}</p>
                                </div>
                            </td>
                        </tr>
                    `;
                }
            } catch (error) {
                console.error('Failed to load subscribers:', error);
                if (reset) {
                    tbody.innerHTML = `<tr><td colspan="10" style="text-align:center; color:#dc3545; padding: 2rem;"><i class="fas fa-exclamation-triangle" style="font-size:1.5rem;"></i><br>Error loading subscribers: ${error.message}</td></tr>`;
                }
                subscribersHasMore = false;
            } finally {
                subscribersLoading = false;
                if (loadingIndicator) loadingIndicator.style.display = 'none';
                updateSubscribersLazyLoadStatus();
            }
        }

        // Render Individual Table Row
        function renderSubscriberRow(sub) {
            const tbody = document.querySelector('#subscribersTable tbody');
            if (!tbody) return;

            const row = document.createElement('tr');
            row.id = `sub-row-${sub.id}`;
            const address = sub.address
                ? (`${sub.address.house_number ? sub.address.house_number + ' ' : ''}${sub.address.barangay ? 'Brgy. ' + sub.address.barangay : ''}`.trim()
                    || sub.address.full_address || 'Quezon City')
                : 'Quezon City';
            
            const deviceType = sub.device?.latest_type || 'Mobile';
            const deviceIcon = deviceType.toLowerCase().includes('ios') || deviceType.toLowerCase().includes('iphone') || deviceType.toLowerCase().includes('apple')
                ? 'fa-apple'
                : (deviceType.toLowerCase().includes('android') ? 'fa-android' : 'fa-laptop');
            
            const lastActive = formatRelativeTime(sub.device?.last_active);
            const isSubActive = (sub.subscription?.status === 'active');
            const langCode = (sub.subscription?.language || 'en').toUpperCase();
            const initials = getInitials(sub.name);

            row.innerHTML = `
                <td><strong>#${sub.id}</strong></td>
                <td>
                    <div class="cs-user-cell">
                        <div class="cs-avatar">${initials}</div>
                        <div class="cs-user-info">
                            <span class="cs-user-name">${sub.name || 'Citizen'}</span>
                            <span class="cs-user-id"><i class="fas fa-id-badge"></i> User #${sub.user_id}</span>
                        </div>
                    </div>
                </td>
                <td>
                    <div class="cs-contact-cell">
                        <span class="cs-contact-item" title="Email Address"><i class="fas fa-envelope"></i> ${sub.email || 'None'}</span>
                        <span class="cs-contact-item" title="Phone Number"><i class="fas fa-phone"></i> ${sub.phone || 'None'}</span>
                    </div>
                </td>
                <td>
                    <span style="font-weight:600; color:var(--text-color-1); font-size:0.85rem;" title="${address}">
                        <i class="fas fa-map-marker-alt" style="color:var(--primary-color-1); margin-right:0.25rem;"></i>
                        ${address}
                    </span>
                </td>
                <td>
                    <div style="display:flex; flex-direction:column; gap:0.25rem;">
                        <span class="cs-device-pill" title="${sub.device?.latest_name || 'Device'}">
                            <i class="fab ${deviceIcon}"></i> ${deviceType}
                        </span>
                        <span style="font-size:0.72rem; color:var(--text-secondary-1);">
                            <i class="fas fa-clock"></i> ${lastActive}
                        </span>
                    </div>
                </td>
                <td>
                    <div class="cs-category-badge-list">
                        ${renderCategoryBadges(sub.subscription?.categories)}
                    </div>
                </td>
                <td>
                    ${renderChannelChips(sub.subscription?.channels)}
                </td>
                <td>
                    <span class="cs-cat-badge general" style="text-transform:uppercase;">
                        ${langCode === 'TL' ? '🇵🇭 TL' : (langCode === 'CEB' ? '🇵🇭 CEB' : '🇺🇸 ' + langCode)}
                    </span>
                </td>
                <td>
                    <span class="cs-status-pill ${isSubActive ? 'active' : 'inactive'}" onclick="toggleSubscriberStatus(${sub.id})" title="Click to toggle status">
                        <i class="fas fa-circle" style="font-size:0.55rem;"></i>
                        <span>${isSubActive ? 'Active' : 'Inactive'}</span>
                    </span>
                </td>
                <td>
                    <div class="cs-action-btns">
                        <button type="button" class="cs-btn-action" onclick="viewSubscription(${sub.id})" title="View Complete Profile & Logs">
                            <i class="fas fa-eye"></i>
                        </button>
                        ${canEdit ? `
                        <button type="button" class="cs-btn-action" onclick="editSubscriptionPreferences(${sub.id})" title="Edit Alert Preferences">
                            <i class="fas fa-pen"></i>
                        </button>
                        ` : ''}
                        ${canDelete ? `
                        <button type="button" class="cs-btn-action danger" onclick="deleteSubscription(${sub.id}, '${(sub.name || 'Citizen').replace(/'/g, "\\'")}')" title="Delete Subscriber">
                            <i class="fas fa-trash"></i>
                        </button>
                        ` : ''}
                    </div>
                </td>
            `;
            tbody.appendChild(row);
        }

        // Toggle Status via AJAX
        async function toggleSubscriberStatus(id) {
            if (!canEdit) return;
            try {
                const res = await fetch(`../api/citizen-subscriptions.php?action=toggle_status&id=${id}`);
                const data = await res.json();
                if (data.success) {
                    loadSubscribers(false);
                    loadStatistics();
                } else {
                    alert('Error: ' + data.message);
                }
            } catch (e) {
                console.error(e);
            }
        }

        // Filter Handlers
        function setFilterStatus(status) {
            currentStatusFilter = status;
            document.querySelectorAll('.cs-filter-pill').forEach(pill => {
                pill.classList.toggle('active', pill.getAttribute('data-status') === status);
            });
            loadSubscribers(true);
        }

        function applyFilters() {
            loadSubscribers(true);
        }

        function refreshSubscriberData() {
            const icon = document.getElementById('refreshBtnIcon');
            if (icon) icon.classList.add('fa-spin');
            loadSubscribers(true);
            loadStatistics();
            setTimeout(() => {
                if (icon) icon.classList.remove('fa-spin');
            }, 600);
        }

        // Load KPI Statistics
        function loadStatistics() {
            fetch('../api/citizen-subscriptions.php?action=statistics')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const total = data.total || 0;
                        const active = data.active || 0;
                        const inactive = data.inactive || 0;
                        const activePercent = total > 0 ? Math.round((active / total) * 100) : 0;

                        document.getElementById('kpiTotalSubscribers').textContent = total;
                        document.getElementById('kpiActivePercent').textContent = `${activePercent}% Active`;
                        document.getElementById('kpiActiveSubscribers').textContent = active;
                        document.getElementById('kpiInactiveCount').textContent = `${inactive} inactive`;

                        document.getElementById('kpiWeatherCount').textContent = data.weather || 0;
                        document.getElementById('kpiEarthquakeCount').textContent = data.earthquake || 0;
                        document.getElementById('kpiFireCount').textContent = data.fire || 0;
                        document.getElementById('kpiMedicalCount').textContent = data.medical || 0;

                        if (data.channels) {
                            document.getElementById('kpiPushReach').textContent = data.channels.push || 0;
                            document.getElementById('kpiEmailReach').textContent = data.channels.email || 0;
                            document.getElementById('kpiSmsReach').textContent = data.channels.sms || 0;
                        }

                        const filterAllCount = document.getElementById('filterCountAll');
                        if (filterAllCount) filterAllCount.textContent = total;
                    }
                })
                .catch(err => console.error('Statistics error:', err));
        }

        // Infinite Scroll / Lazy Load
        function loadMoreSubscribers() {
            if (subscribersLoading || !subscribersHasMore) return;
            subscribersPage += 1;
            loadSubscribers(false);
        }

        function setupSubscribersLazyLoader() {
            const sentinel = document.getElementById('subscribersLazyLoadSentinel');
            if (!sentinel || !('IntersectionObserver' in window)) return;

            const observer = new IntersectionObserver(entries => {
                if (entries.some(entry => entry.isIntersecting)) loadMoreSubscribers();
            }, { rootMargin: '240px 0px' });
            observer.observe(sentinel);
        }

        function updateSubscribersLazyLoadStatus() {
            const sentinel = document.getElementById('subscribersLazyLoadSentinel');
            if (!sentinel) return;

            if (subscribersLoading) {
                sentinel.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading more citizens...';
            } else if (subscribersHasMore) {
                const shown = Math.min(subscribersPage * SUBSCRIBERS_PAGE_SIZE, subscribersTotal);
                sentinel.innerHTML = `<button type="button" class="btn btn-sm btn-secondary" onclick="loadMoreSubscribers()"><i class="fas fa-chevron-down"></i> Load more citizens</button> <span style="margin-left:0.5rem;">(${shown} of ${subscribersTotal})</span>`;
            } else {
                sentinel.textContent = subscribersTotal > 0
                    ? `Showing all ${subscribersTotal} citizen subscribers`
                    : '';
            }
        }

        // Modal Tab Switcher
        function switchTab(tabName) {
            document.querySelectorAll('.tab-content').forEach(tab => tab.style.display = 'none');
            document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
            
            const target = document.getElementById('tab-' + tabName);
            if (target) target.style.display = 'block';
            
            const activeBtn = Array.from(document.querySelectorAll('.tab-btn')).find(b => b.getAttribute('onclick')?.includes(tabName));
            if (activeBtn) activeBtn.classList.add('active');
        }

        // View Subscription Details Modal
        function viewSubscription(id, defaultTab = 'user-info') {
            ensureLanguagesLoaded().then(() => {
                fetch(`../api/citizen-subscriptions.php?action=get&id=${id}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success && data.subscriber) {
                            const sub = data.subscriber;
                            document.getElementById('subscriberId').value = sub.id;
                            
                            // Set Header
                            document.getElementById('modalUserName').textContent = sub.name || 'Citizen Subscriber';
                            document.getElementById('modalUserEmail').innerHTML = `<i class="fas fa-envelope"></i> ${sub.email || 'N/A'}`;
                            document.getElementById('modalUserPhone').innerHTML = `<i class="fas fa-phone"></i> ${sub.phone || 'N/A'}`;
                            document.getElementById('modalUserAvatar').textContent = getInitials(sub.name);
                            
                            const statusBadge = document.getElementById('modalUserStatusBadge');
                            if (statusBadge) {
                                const isActive = (sub.subscription?.status === 'active');
                                statusBadge.className = `badge ${isActive ? 'active' : 'inactive'}`;
                                statusBadge.textContent = isActive ? 'Active' : 'Inactive';
                            }

                            // Tab 1: Profile Details Cards
                            const userInfoHtml = `
                                <div class="info-item">
                                    <div class="info-label"><i class="fas fa-user"></i> Full Name</div>
                                    <div class="info-value">${sub.name || 'N/A'}</div>
                                </div>
                                <div class="info-item">
                                    <div class="info-label"><i class="fas fa-envelope"></i> Email Address</div>
                                    <div class="info-value">${sub.email || 'N/A'}</div>
                                </div>
                                <div class="info-item">
                                    <div class="info-label"><i class="fas fa-phone"></i> Mobile Phone</div>
                                    <div class="info-value">${sub.phone || 'N/A'}</div>
                                </div>
                                <div class="info-item">
                                    <div class="info-label"><i class="fas fa-shield-alt"></i> Account Status</div>
                                    <div class="info-value"><span class="badge ${sub.user_status}">${(sub.user_status || 'active').toUpperCase()}</span></div>
                                </div>
                                <div class="info-item">
                                    <div class="info-label"><i class="fas fa-map-marker-alt"></i> Barangay</div>
                                    <div class="info-value">${sub.address?.barangay || 'Quezon City'}</div>
                                </div>
                                <div class="info-item">
                                    <div class="info-label"><i class="fas fa-home"></i> Full Address</div>
                                    <div class="info-value">${sub.address?.full_address || 'Quezon City'}</div>
                                </div>
                                <div class="info-item">
                                    <div class="info-label"><i class="fas fa-id-badge"></i> Citizen User ID</div>
                                    <div class="info-value">#${sub.user_id || sub.id}</div>
                                </div>
                                <div class="info-item">
                                    <div class="info-label"><i class="fas fa-calendar-alt"></i> Registered Since</div>
                                    <div class="info-value">${sub.user_created_at ? new Date(sub.user_created_at).toLocaleString() : 'Recently'}</div>
                                </div>
                            `;
                            document.getElementById('userInfoDetails').innerHTML = userInfoHtml;
                            
                            // Tab 2: Subscription Preferences Form
                            document.querySelectorAll('#subscriptionForm input[name="categories[]"]').forEach(cb => {
                                cb.checked = sub.subscription?.categories ? sub.subscription.categories.includes(cb.value) : false;
                            });
                            document.querySelectorAll('#subscriptionForm input[name="channels[]"]').forEach(cb => {
                                cb.checked = sub.subscription?.channels ? sub.subscription.channels.includes(cb.value) : false;
                            });
                            const langSelect = document.getElementById('preferredLanguage');
                            if (langSelect) langSelect.value = sub.subscription?.language || 'en';
                            const statusSelect = document.getElementById('subscriptionStatus');
                            if (statusSelect) statusSelect.value = sub.subscription?.status || 'active';
                            
                            // Tab 3: Devices List
                            let devicesHtml = '<div class="cs-empty-state"><i class="fas fa-mobile-alt"></i><h4>No registered devices</h4><p>Device will register on first citizen app sign-in.</p></div>';
                            if (sub.devices && sub.devices.length > 0) {
                                devicesHtml = `<div class="cs-list-container">` + sub.devices.map(d => {
                                    const isApple = (d.device_type || '').toLowerCase().includes('ios') || (d.device_type || '').toLowerCase().includes('apple');
                                    const isAndroid = (d.device_type || '').toLowerCase().includes('android');
                                    const devIcon = isApple ? 'fa-apple' : (isAndroid ? 'fa-android' : 'fa-laptop');
                                    return `
                                        <div class="cs-list-card">
                                            <div class="cs-list-card-left">
                                                <div class="cs-list-card-icon"><i class="fab ${devIcon}"></i></div>
                                                <div class="cs-list-card-info">
                                                    <h4>${d.device_name || 'Citizen Mobile Device'}</h4>
                                                    <div class="cs-list-card-meta">
                                                        <span><i class="fas fa-microchip"></i> OS: ${d.device_type || 'Mobile'}</span>
                                                        <span><i class="fas fa-clock"></i> Last Active: ${d.last_active ? new Date(d.last_active).toLocaleString() : 'Recently'}</span>
                                                    </div>
                                                </div>
                                            </div>
                                            <span class="badge ${d.is_active ? 'active' : 'inactive'}">${d.is_active ? 'Connected' : 'Offline'}</span>
                                        </div>
                                    `;
                                }).join('') + `</div>`;
                            }
                            document.getElementById('devicesList').innerHTML = devicesHtml;
                            
                            // Tab 4: Location List
                            let locHtml = '<div class="cs-empty-state"><i class="fas fa-map-marker-alt"></i><h4>No GPS history available</h4><p>Coordinates update upon citizen mobile location sharing.</p></div>';
                            if (sub.locations && sub.locations.length > 0) {
                                locHtml = `<div class="cs-list-container">` + sub.locations.map(loc => `
                                    <div class="cs-list-card">
                                        <div class="cs-list-card-left">
                                            <div class="cs-list-card-icon"><i class="fas fa-map-marker-alt"></i></div>
                                            <div class="cs-list-card-info">
                                                <h4>${loc.address || 'Resolved Citizen Location'}</h4>
                                                <div class="cs-list-card-meta">
                                                    <span><i class="fas fa-crosshairs"></i> GPS: ${loc.latitude || '14.6760'}, ${loc.longitude || '121.0437'}</span>
                                                    <span><i class="fas fa-clock"></i> ${loc.created_at ? new Date(loc.created_at).toLocaleString() : 'Recent'}</span>
                                                </div>
                                            </div>
                                        </div>
                                        ${loc.is_current ? '<span class="badge active"><i class="fas fa-check-circle"></i> Current</span>' : ''}
                                    </div>
                                `).join('') + `</div>`;
                            }
                            document.getElementById('locationsList').innerHTML = locHtml;
                            
                            // Tab 5: Activity Logs
                            let actHtml = '<div class="cs-empty-state"><i class="fas fa-history"></i><h4>No activity recorded yet</h4></div>';
                            if (sub.activities && sub.activities.length > 0) {
                                actHtml = `<div class="cs-list-container">` + sub.activities.map(act => `
                                    <div class="cs-list-card">
                                        <div class="cs-list-card-left">
                                            <div class="cs-list-card-icon"><i class="fas fa-bell"></i></div>
                                            <div class="cs-list-card-info">
                                                <h4>${act.activity_type || 'System Event'}</h4>
                                                <div class="cs-list-card-meta">
                                                    <span>${act.description || 'Notification recorded'}</span>
                                                    <span><i class="fas fa-clock"></i> ${act.created_at ? new Date(act.created_at).toLocaleString() : ''}</span>
                                                </div>
                                            </div>
                                        </div>
                                        <span class="badge active">${act.status || 'Success'}</span>
                                    </div>
                                `).join('') + `</div>`;
                            }
                            document.getElementById('activitiesList').innerHTML = actHtml;
                            
                            switchTab(defaultTab);
                            const subModal = document.getElementById('subscriptionModal');
                            if (subModal) {
                                subModal.classList.add('show');
                                subModal.style.display = 'flex';
                            }
                        }
                    })
                    .catch(err => alert('Failed to fetch subscriber details: ' + err.message));
            });
        }

        function editSubscriptionPreferences(id) {
            viewSubscription(id, 'subscription');
        }

        function closeSubscriptionModal() {
            document.getElementById('subscriptionModal').style.display = 'none';
            document.getElementById('subscriptionForm').reset();
        }

        // Submit Subscription Preferences Form
        const subForm = document.getElementById('subscriptionForm');
        if (subForm) {
            subForm.addEventListener('submit', function(e) {
                e.preventDefault();
                if (!canEdit) return;

                const saveBtn = document.getElementById('saveSubscriptionBtn');
                const origHtml = saveBtn ? saveBtn.innerHTML : '';
                if (saveBtn) {
                    saveBtn.disabled = true;
                    saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
                }

                const formData = new FormData(this);
                fetch('../api/citizen-subscriptions.php', {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        closeSubscriptionModal();
                        loadSubscribers(false);
                        loadStatistics();
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(err => alert('Error saving preferences: ' + err.message))
                .finally(() => {
                    if (saveBtn) {
                        saveBtn.disabled = false;
                        saveBtn.innerHTML = origHtml;
                    }
                });
            });
        }

        // Delete Subscription
        function deleteSubscription(id, name) {
            if (!canDelete) return;
            if (confirm(`Permanently remove subscription for citizen "${name}"?`)) {
                fetch('../api/citizen-subscriptions.php', {
                    method: 'DELETE',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: id })
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        loadSubscribers(true);
                        loadStatistics();
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(err => alert('Error deleting: ' + err.message));
            }
        }

        // Add Subscriber Modal Controls
        function openAddSubscriberModal() {
            ensureLanguagesLoaded().then(() => {
                const modal = document.getElementById('addSubscriberModal');
                if (modal) {
                    document.getElementById('addSubscriberForm')?.reset();
                    modal.classList.add('show');
                    modal.style.display = 'flex';
                }
            });
        }

        function closeAddSubscriberModal() {
            const modal = document.getElementById('addSubscriberModal');
            if (modal) {
                modal.classList.remove('show');
                modal.style.display = 'none';
            }
        }

        const addForm = document.getElementById('addSubscriberForm');
        if (addForm) {
            addForm.addEventListener('submit', function(e) {
                e.preventDefault();
                if (!canEdit) return;

                const submitBtn = document.getElementById('submitAddSubscriberBtn');
                const origHtml = submitBtn ? submitBtn.innerHTML : '';
                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Registering...';
                }

                const formData = new FormData(this);
                fetch('../api/citizen-subscriptions.php?action=add', {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        closeAddSubscriberModal();
                        loadSubscribers(true);
                        loadStatistics();
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(err => alert('Registration error: ' + err.message))
                .finally(() => {
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = origHtml;
                    }
                });
            });
        }

        // Export Functions
        function exportSubscribersCsv() {
            window.location.href = '../api/citizen-subscriptions.php?action=export';
        }

        function exportSubscribersPdf() {
            const exportButton = document.getElementById('exportSubscribersPdfBtn');
            if (window.AdminReportPdfExporter && typeof window.AdminReportPdfExporter.exportCurrentPage === 'function') {
                window.AdminReportPdfExporter.exportCurrentPage({
                    filenamePrefix: 'citizen-subscriptions-report',
                    targetSelector: '.main-content .main-container',
                    triggerButton: exportButton
                });
                return;
            }
            window.print();
        }

        // Live Search Input Event with Debounce
        const searchInput = document.getElementById('searchSubscribers');
        const clearBtn = document.getElementById('searchClearBtn');
        if (searchInput) {
            searchInput.addEventListener('input', function() {
                if (clearBtn) clearBtn.style.display = this.value ? 'block' : 'none';
                clearTimeout(subscriberSearchTimer);
                subscriberSearchTimer = setTimeout(() => loadSubscribers(true), 250);
            });
        }
        if (clearBtn) {
            clearBtn.addEventListener('click', function() {
                if (searchInput) searchInput.value = '';
                this.style.display = 'none';
                loadSubscribers(true);
            });
        }

        // Language Catalog Loader
        let cachedLanguages = null;
        async function loadLanguagesForSelect() {
            try {
                const res = await fetch('../api/language-management.php?action=list');
                const data = await res.json();
                if (data.success && Array.isArray(data.languages)) {
                    cachedLanguages = data.languages;
                    const selects = [document.getElementById('preferredLanguage'), document.getElementById('addLanguage')];
                    selects.forEach(select => {
                        if (!select) return;
                        select.innerHTML = '';
                        data.languages.forEach(lang => {
                            const opt = document.createElement('option');
                            opt.value = lang.language_code;
                            opt.textContent = (lang.flag_emoji ? (lang.flag_emoji + ' ') : '') + (lang.language_name || lang.language_code);
                            select.appendChild(opt);
                        });
                        if (!select.value && select.querySelector('option[value="en"]')) {
                            select.value = 'en';
                        }
                    });
                }
            } catch (e) {
                console.error('Failed to load languages', e);
            }
        }

        async function ensureLanguagesLoaded() {
            if (!cachedLanguages) {
                await loadLanguagesForSelect();
            }
        }

        // Initialize Page
        document.addEventListener('DOMContentLoaded', function() {
            loadSubscribers(true);
            setupSubscribersLazyLoader();
            loadStatistics();
            loadLanguagesForSelect();
        });

        // Close modals on backdrop click
        window.addEventListener('click', function(e) {
            const subModal = document.getElementById('subscriptionModal');
            const addModal = document.getElementById('addSubscriberModal');
            if (e.target === subModal) closeSubscriptionModal();
            if (e.target === addModal) closeAddSubscriberModal();
        });
    </script>
</body>
</html>
