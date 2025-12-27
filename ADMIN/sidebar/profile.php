<?php
/**
 * Admin Profile Page
 * View account details and activity logs
 */

session_start();

// Check if user is logged in as admin
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../login.php');
    exit();
}

$pageTitle = 'My Profile - Emergency Communication System';
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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <style>
        .profile-container {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 2rem;
            margin-bottom: 2rem;
        }
        
        @media (max-width: 992px) {
            .profile-container {
                grid-template-columns: 1fr;
            }
        }
        
        .profile-card {
            background: var(--card-bg-1);
            border-radius: 12px;
            border: 1px solid var(--border-color-1);
            padding: 2rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .profile-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            margin: 0 auto 1rem;
            border: 4px solid var(--primary-color-1);
            overflow: hidden;
        }
        
        .profile-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .profile-name {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-color-1);
            margin-bottom: 0.25rem;
        }
        
        .profile-email {
            color: var(--text-secondary-1);
            font-size: 0.95rem;
        }
        
        .profile-badge {
            display: inline-block;
            background: var(--primary-color-1);
            color: white;
            padding: 0.35rem 0.75rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            margin-top: 0.5rem;
        }
        
        .profile-info {
            margin-top: 1.5rem;
        }
        
        .info-item {
            display: flex;
            justify-content: space-between;
            padding: 0.75rem 0;
            border-bottom: 1px solid var(--border-color-1);
        }
        
        .info-item:last-child {
            border-bottom: none;
        }
        
        .info-label {
            color: var(--text-secondary-1);
            font-size: 0.9rem;
        }
        
        .info-value {
            color: var(--text-color-1);
            font-weight: 600;
            font-size: 0.9rem;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .stat-box {
            background: var(--bg-color-1);
            border: 1px solid var(--border-color-1);
            border-radius: 8px;
            padding: 1rem;
            text-align: center;
        }
        
        .stat-value {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--primary-color-1);
            margin-bottom: 0.25rem;
        }
        
        .stat-label {
            font-size: 0.85rem;
            color: var(--text-secondary-1);
        }
        
        .tab-container {
            background: var(--card-bg-1);
            border-radius: 12px;
            border: 1px solid var(--border-color-1);
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .tab-buttons {
            display: flex;
            background: var(--bg-color-1);
            border-bottom: 1px solid var(--border-color-1);
            overflow-x: auto;
        }
        
        .tab-button {
            flex: 1;
            padding: 1rem 1.5rem;
            background: transparent;
            border: none;
            border-bottom: 3px solid transparent;
            color: var(--text-secondary-1);
            font-size: 0.95rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            white-space: nowrap;
        }
        
        .tab-button:hover {
            background: var(--card-bg-1);
            color: var(--text-color-1);
        }
        
        .tab-button.active {
            color: var(--primary-color-1);
            border-bottom-color: var(--primary-color-1);
            background: var(--card-bg-1);
        }
        
        .tab-content {
            display: none;
            padding: 2rem;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .activity-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .activity-table th,
        .activity-table td {
            padding: 0.875rem;
            text-align: left;
            border-bottom: 1px solid var(--border-color-1);
        }
        
        .activity-table th {
            background: var(--bg-color-1);
            font-weight: 600;
            color: var(--text-color-1);
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .activity-table td {
            color: var(--text-color-1);
            font-size: 0.9rem;
        }
        
        .activity-table tr:hover {
            background: var(--bg-color-1);
        }
        
        .activity-badge {
            display: inline-block;
            padding: 0.25rem 0.625rem;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .activity-badge.login { background: #d4edda; color: #155724; }
        .activity-badge.logout { background: #d1ecf1; color: #0c5460; }
        .activity-badge.notification { background: #fff3cd; color: #856404; }
        .activity-badge.user { background: #e7e7ff; color: #383874; }
        .activity-badge.default { background: #e2e3e5; color: #383d41; }
        
        .status-badge {
            display: inline-block;
            padding: 0.25rem 0.625rem;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .status-badge.success { background: #d4edda; color: #155724; }
        .status-badge.failed { background: #f8d7da; color: #721c24; }
        .status-badge.active { background: #d4edda; color: #155724; }
        
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 0.5rem;
            margin-top: 1.5rem;
            flex-wrap: wrap;
        }
        
        .pagination button {
            padding: 0.5rem 1rem;
            background: var(--bg-color-1);
            border: 1px solid var(--border-color-1);
            border-radius: 6px;
            color: var(--text-color-1);
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .pagination button:hover:not(:disabled) {
            background: var(--primary-color-1);
            color: white;
            border-color: var(--primary-color-1);
        }
        
        .pagination button:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        .pagination .page-info {
            color: var(--text-secondary-1);
            font-size: 0.9rem;
        }
        
        .loading-state {
            text-align: center;
            padding: 3rem 2rem;
            color: var(--text-secondary-1);
        }
        
        .loading-state i {
            font-size: 2rem;
            margin-bottom: 1rem;
        }
        
        .empty-state {
            text-align: center;
            padding: 3rem 2rem;
            color: var(--text-secondary-1);
        }
        
        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }
        
        .filter-section {
            display: flex;
            gap: 1rem;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
        }
        
        .filter-select {
            padding: 0.5rem 1rem;
            background: var(--bg-color-1);
            border: 1px solid var(--border-color-1);
            border-radius: 6px;
            color: var(--text-color-1);
            font-size: 0.9rem;
            cursor: pointer;
        }
        
        .section-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--text-color-1);
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
    </style>
</head>
<body>
    <!-- Include Sidebar Component -->
    <?php include 'includes/sidebar.php'; ?>

    <!-- Include Admin Header Component -->
    <?php include 'includes/admin-header.php'; ?>
    
    <!-- Main Content -->
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
                            <span>My Profile</span>
                        </li>
                    </ol>
                </nav>
                <h1><i class="fas fa-user-circle"></i> My Profile</h1>
                <p>View your account details and activity history</p>
            </div>
            
            <div class="sub-container">
                <div class="page-content">
                    <!-- Profile Overview -->
                    <div class="profile-container">
                        <!-- Profile Info Card -->
                        <div class="profile-card">
                            <div class="profile-header">
                                <div class="profile-avatar" id="profileAvatarContainer">
                                    <img src="https://ui-avatars.com/api/?name=Admin+User&background=4c8a89&color=fff&size=256" alt="Profile" id="profileAvatar">
                                </div>
                                <div class="profile-name" id="profileName">Loading...</div>
                                <div class="profile-email" id="profileEmail">-</div>
                                <span class="profile-badge" id="profileRole">Administrator</span>
                            </div>
                            
                            <div class="profile-info">
                                <div class="info-item">
                                    <span class="info-label">Status</span>
                                    <span class="info-value" id="profileStatus">-</span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Account Created</span>
                                    <span class="info-value" id="profileCreated">-</span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Last Login</span>
                                    <span class="info-value" id="profileLastLogin">-</span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Stats Card -->
                        <div>
                            <div class="section-title">
                                <i class="fas fa-chart-bar"></i> Account Statistics
                            </div>
                            <div class="stats-grid" id="statsGrid">
                                <div class="stat-box">
                                    <div class="stat-value" id="statTotalLogins">-</div>
                                    <div class="stat-label">Total Logins</div>
                                </div>
                                <div class="stat-box">
                                    <div class="stat-value" id="statSuccessLogins">-</div>
                                    <div class="stat-label">Successful Logins</div>
                                </div>
                                <div class="stat-box">
                                    <div class="stat-value" id="statAvgSession">-</div>
                                    <div class="stat-label">Avg Session</div>
                                </div>
                            </div>
                            
                            <div class="section-title" style="margin-top: 1.5rem;">
                                <i class="fas fa-tasks"></i> Top Activities
                            </div>
                            <div id="topActivities" style="margin-top: 1rem;">
                                <div class="loading-state">
                                    <i class="fas fa-spinner fa-spin"></i>
                                    <p>Loading statistics...</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Activity Tabs -->
                    <div class="tab-container">
                        <div class="tab-buttons">
                            <button class="tab-button active" data-tab="activity">
                                <i class="fas fa-history"></i> Activity Log
                            </button>
                            <button class="tab-button" data-tab="logins">
                                <i class="fas fa-sign-in-alt"></i> Login History
                            </button>
                        </div>
                        
                        <!-- Activity Log Tab -->
                        <div class="tab-content active" id="activityTab">
                            <div class="filter-section">
                                <select class="filter-select" id="activityFilter">
                                    <option value="all">All Activities</option>
                                    <option value="login">Login</option>
                                    <option value="logout">Logout</option>
                                    <option value="notification">Notifications</option>
                                    <option value="user_management">User Management</option>
                                </select>
                            </div>
                            
                            <div id="activityContent">
                                <div class="loading-state">
                                    <i class="fas fa-spinner fa-spin"></i>
                                    <p>Loading activity log...</p>
                                </div>
                            </div>
                            
                            <div class="pagination" id="activityPagination"></div>
                        </div>
                        
                        <!-- Login History Tab -->
                        <div class="tab-content" id="loginsTab">
                            <div id="loginsContent">
                                <div class="loading-state">
                                    <i class="fas fa-spinner fa-spin"></i>
                                    <p>Loading login history...</p>
                                </div>
                            </div>
                            
                            <div class="pagination" id="loginsPagination"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        let currentActivityPage = 1;
        let currentLoginsPage = 1;
        
        // Load profile data on page load
        document.addEventListener('DOMContentLoaded', function() {
            loadProfile();
            loadActivityLogs();
            initTabs();
            
            // Activity filter change
            document.getElementById('activityFilter').addEventListener('change', function() {
                currentActivityPage = 1;
                loadActivityLogs();
            });
        });
        
        // Initialize tabs
        function initTabs() {
            const tabButtons = document.querySelectorAll('.tab-button');
            tabButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const tabName = this.dataset.tab;
                    switchTab(tabName);
                });
            });
        }
        
        // Switch tabs
        function switchTab(tabName) {
            // Update buttons
            document.querySelectorAll('.tab-button').forEach(btn => {
                btn.classList.remove('active');
            });
            document.querySelector(`[data-tab="${tabName}"]`).classList.add('active');
            
            // Update content
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.remove('active');
            });
            document.getElementById(`${tabName}Tab`).classList.add('active');
            
            // Load data if needed
            if (tabName === 'logins' && currentLoginsPage === 1) {
                loadLoginLogs();
            }
        }
        
        // Load profile data
        async function loadProfile() {
            try {
                const response = await fetch('../api/profile.php?action=profile');
                const data = await response.json();
                
                if (data.success) {
                    const profile = data.profile;
                    const loginStats = data.login_stats;
                    const activityStats = data.activity_stats;
                    
                    // Update profile info
                    document.getElementById('profileName').textContent = profile.name;
                    document.getElementById('profileEmail').textContent = profile.email;
                    document.getElementById('profileStatus').textContent = capitalizeFirst(profile.status);
                    document.getElementById('profileCreated').textContent = formatDate(profile.created_at);
                    
                    // Update avatar
                    const avatarUrl = `https://ui-avatars.com/api/?name=${encodeURIComponent(profile.name)}&background=4c8a89&color=fff&size=256`;
                    document.getElementById('profileAvatar').src = avatarUrl;
                    
                    // Update stats
                    document.getElementById('statTotalLogins').textContent = loginStats.total_logins || 0;
                    document.getElementById('statSuccessLogins').textContent = loginStats.successful_logins || 0;
                    
                    // Format average session duration
                    const avgSession = loginStats.avg_session_duration || 0;
                    document.getElementById('statAvgSession').textContent = formatDuration(avgSession);
                    
                    // Update last login
                    if (loginStats.last_login) {
                        document.getElementById('profileLastLogin').textContent = formatDateTime(loginStats.last_login);
                    } else {
                        document.getElementById('profileLastLogin').textContent = 'Never';
                    }
                    
                    // Display top activities
                    displayTopActivities(activityStats);
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: data.message || 'Failed to load profile data.'
                    });
                }
            } catch (error) {
                console.error('Error loading profile:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Failed to load profile data. Please refresh the page.'
                });
            }
        }
        
        // Display top activities
        function displayTopActivities(activities) {
            const container = document.getElementById('topActivities');
            
            if (!activities || activities.length === 0) {
                container.innerHTML = '<p style="color: var(--text-secondary-1);">No activity data available</p>';
                return;
            }
            
            let html = '<div style="display: flex; flex-direction: column; gap: 0.75rem;">';
            activities.forEach(activity => {
                const actionLabel = formatActionLabel(activity.action);
                html += `
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.75rem; background: var(--bg-color-1); border-radius: 6px;">
                        <span style="color: var(--text-color-1); font-weight: 500;">${actionLabel}</span>
                        <span style="background: var(--primary-color-1); color: white; padding: 0.25rem 0.625rem; border-radius: 12px; font-size: 0.85rem; font-weight: 600;">${activity.count}</span>
                    </div>
                `;
            });
            html += '</div>';
            
            container.innerHTML = html;
        }
        
        // Load activity logs
        async function loadActivityLogs(page = 1) {
            const container = document.getElementById('activityContent');
            const filter = document.getElementById('activityFilter').value;
            
            container.innerHTML = `
                <div class="loading-state">
                    <i class="fas fa-spinner fa-spin"></i>
                    <p>Loading activity log...</p>
                </div>
            `;
            
            try {
                const response = await fetch(`../api/profile.php?action=activity_logs&page=${page}&limit=20&filter=${filter}`);
                const data = await response.json();
                
                if (data.success) {
                    displayActivityLogs(data.activities);
                    displayPagination('activity', data.pagination);
                    currentActivityPage = page;
                } else {
                    container.innerHTML = `
                        <div class="empty-state">
                            <i class="fas fa-exclamation-triangle"></i>
                            <h3>Error</h3>
                            <p>${data.message || 'Failed to load activity logs.'}</p>
                        </div>
                    `;
                }
            } catch (error) {
                console.error('Error loading activity logs:', error);
                container.innerHTML = `
                    <div class="empty-state">
                        <i class="fas fa-exclamation-triangle"></i>
                        <h3>Error</h3>
                        <p>Failed to load activity logs. Please try again.</p>
                    </div>
                `;
            }
        }
        
        // Display activity logs
        function displayActivityLogs(activities) {
            const container = document.getElementById('activityContent');
            
            if (!activities || activities.length === 0) {
                container.innerHTML = `
                    <div class="empty-state">
                        <i class="fas fa-history"></i>
                        <h3>No Activity</h3>
                        <p>No activity logs found.</p>
                    </div>
                `;
                return;
            }
            
            let html = `
                <table class="activity-table">
                    <thead>
                        <tr>
                            <th>Date & Time</th>
                            <th>Action</th>
                            <th>Description</th>
                            <th>IP Address</th>
                        </tr>
                    </thead>
                    <tbody>
            `;
            
            activities.forEach(activity => {
                const badgeClass = getBadgeClass(activity.action);
                const actionLabel = formatActionLabel(activity.action);
                
                html += `
                    <tr>
                        <td>${formatDateTime(activity.created_at)}</td>
                        <td><span class="activity-badge ${badgeClass}">${actionLabel}</span></td>
                        <td>${escapeHtml(activity.description || '-')}</td>
                        <td>${escapeHtml(activity.ip_address || '-')}</td>
                    </tr>
                `;
            });
            
            html += `
                    </tbody>
                </table>
            `;
            
            container.innerHTML = html;
        }
        
        // Load login logs
        async function loadLoginLogs(page = 1) {
            const container = document.getElementById('loginsContent');
            
            container.innerHTML = `
                <div class="loading-state">
                    <i class="fas fa-spinner fa-spin"></i>
                    <p>Loading login history...</p>
                </div>
            `;
            
            try {
                const response = await fetch(`../api/profile.php?action=login_logs&page=${page}&limit=20`);
                const data = await response.json();
                
                if (data.success) {
                    displayLoginLogs(data.logins);
                    displayPagination('logins', data.pagination);
                    currentLoginsPage = page;
                } else {
                    container.innerHTML = `
                        <div class="empty-state">
                            <i class="fas fa-exclamation-triangle"></i>
                            <h3>Error</h3>
                            <p>${data.message || 'Failed to load login logs.'}</p>
                        </div>
                    `;
                }
            } catch (error) {
                console.error('Error loading login logs:', error);
                container.innerHTML = `
                    <div class="empty-state">
                        <i class="fas fa-exclamation-triangle"></i>
                        <h3>Error</h3>
                        <p>Failed to load login logs. Please try again.</p>
                    </div>
                `;
            }
        }
        
        // Display login logs
        function displayLoginLogs(logins) {
            const container = document.getElementById('loginsContent');
            
            if (!logins || logins.length === 0) {
                container.innerHTML = `
                    <div class="empty-state">
                        <i class="fas fa-sign-in-alt"></i>
                        <h3>No Login History</h3>
                        <p>No login logs found.</p>
                    </div>
                `;
                return;
            }
            
            let html = `
                <table class="activity-table">
                    <thead>
                        <tr>
                            <th>Login Time</th>
                            <th>Status</th>
                            <th>IP Address</th>
                            <th>Session Duration</th>
                            <th>Logout Time</th>
                        </tr>
                    </thead>
                    <tbody>
            `;
            
            logins.forEach(login => {
                const statusClass = login.login_status === 'success' ? 'success' : 'failed';
                const statusLabel = capitalizeFirst(login.login_status);
                const duration = login.session_duration ? formatDuration(login.session_duration) : '-';
                const logoutTime = login.logout_at ? formatDateTime(login.logout_at) : 'Still Active';
                
                html += `
                    <tr>
                        <td>${formatDateTime(login.login_at)}</td>
                        <td><span class="status-badge ${statusClass}">${statusLabel}</span></td>
                        <td>${escapeHtml(login.ip_address || '-')}</td>
                        <td>${duration}</td>
                        <td>${logoutTime}</td>
                    </tr>
                `;
            });
            
            html += `
                    </tbody>
                </table>
            `;
            
            container.innerHTML = html;
        }
        
        // Display pagination
        function displayPagination(type, pagination) {
            const container = document.getElementById(`${type}Pagination`);
            
            if (pagination.total_pages <= 1) {
                container.innerHTML = '';
                return;
            }
            
            let html = `
                <button ${pagination.page <= 1 ? 'disabled' : ''} onclick="${type === 'activity' ? 'loadActivityLogs' : 'loadLoginLogs'}(${pagination.page - 1})">
                    <i class="fas fa-chevron-left"></i> Previous
                </button>
                <span class="page-info">Page ${pagination.page} of ${pagination.total_pages}</span>
                <button ${pagination.page >= pagination.total_pages ? 'disabled' : ''} onclick="${type === 'activity' ? 'loadActivityLogs' : 'loadLoginLogs'}(${pagination.page + 1})">
                    Next <i class="fas fa-chevron-right"></i>
                </button>
            `;
            
            container.innerHTML = html;
        }
        
        // Helper functions
        function formatDate(dateString) {
            const date = new Date(dateString);
            return date.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
        }
        
        function formatDateTime(dateString) {
            const date = new Date(dateString);
            return date.toLocaleString('en-US', { 
                year: 'numeric', 
                month: 'short', 
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        }
        
        function formatDuration(seconds) {
            if (!seconds || seconds <= 0) return '-';
            
            const hours = Math.floor(seconds / 3600);
            const minutes = Math.floor((seconds % 3600) / 60);
            
            if (hours > 0) {
                return `${hours}h ${minutes}m`;
            } else if (minutes > 0) {
                return `${minutes}m`;
            } else {
                return `${seconds}s`;
            }
        }
        
        function capitalizeFirst(str) {
            if (!str) return '';
            return str.charAt(0).toUpperCase() + str.slice(1).replace(/_/g, ' ');
        }
        
        function formatActionLabel(action) {
            return action.split('_').map(word => word.charAt(0).toUpperCase() + word.slice(1)).join(' ');
        }
        
        function getBadgeClass(action) {
            if (action.includes('login')) return 'login';
            if (action.includes('logout')) return 'logout';
            if (action.includes('notification') || action.includes('alert')) return 'notification';
            if (action.includes('user') || action.includes('admin')) return 'user';
            return 'default';
        }
        
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    </script>
</body>
</html>


