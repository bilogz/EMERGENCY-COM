<?php
/**
 * Admin Profile Page
 * View and manage admin account details and activity history
 */

// Start session and check authentication
session_start();

// Check if user is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../login.php');
    exit();
}

$pageTitle = 'My Profile';
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
    <style>
        /* Enhanced Profile Styles */
        :root {
            --card-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --transition-speed: 0.2s;
        }

        .profile-container {
            display: grid;
            grid-template-columns: 350px 1fr;
            gap: 1.5rem;
            align-items: flex-start;
        }

        @media (max-width: 992px) {
            .profile-container { grid-template-columns: 1fr; }
        }

        .profile-sidebar {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .profile-header-card {
            background: var(--card-bg-1);
            border-radius: 12px;
            padding: 2.5rem 1.5rem;
            text-align: center;
            border: 1px solid var(--border-color-1);
            box-shadow: var(--card-shadow);
        }

        .profile-avatar-container {
            position: relative;
            width: 120px;
            height: 120px;
            margin: 0 auto 1.5rem auto;
        }

        .profile-avatar {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid var(--bg-color-1);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        .profile-name {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--text-color-1);
            margin-bottom: 0.25rem;
        }

        .profile-role {
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--primary-color-1);
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 1.25rem;
            display: block;
        }

        .profile-stats {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            padding-top: 1.5rem;
            border-top: 1px solid var(--border-color-1);
        }

        .profile-stat-item {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }

        .profile-stat-label { font-size: 0.75rem; color: var(--text-secondary-1); font-weight: 600; text-transform: uppercase; }
        .profile-stat-value { font-size: 1.1rem; font-weight: 700; color: var(--text-color-1); }

        .module-card {
            background: var(--card-bg-1);
            border-radius: 12px;
            border: 1px solid var(--border-color-1);
            box-shadow: var(--card-shadow);
            overflow: hidden;
            margin-bottom: 1.5rem;
        }

        .module-card-header {
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid var(--border-color-1);
            background: var(--bg-color-1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .module-card-header h2 {
            margin: 0;
            font-size: 1rem;
            font-weight: 700;
            color: var(--text-color-1);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .info-grid {
            display: grid;
            gap: 1.25rem;
            padding: 1.5rem;
        }

        .info-item {
            display: flex;
            flex-direction: column;
            gap: 0.35rem;
        }

        .info-label {
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            color: var(--text-secondary-1);
            letter-spacing: 0.5px;
        }

        .info-value {
            font-size: 0.95rem;
            color: var(--text-color-1);
            font-weight: 500;
        }

        .tab-nav {
            display: flex;
            gap: 1.5rem;
            padding: 0 1.5rem;
            border-bottom: 1px solid var(--border-color-1);
            background: var(--card-bg-1);
        }

        .tab-btn {
            padding: 1rem 0;
            background: none;
            border: none;
            border-bottom: 3px solid transparent;
            cursor: pointer;
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--text-secondary-1);
            transition: all var(--transition-speed) ease;
        }

        .tab-btn:hover { color: var(--primary-color-1); }
        .tab-btn.active { color: var(--primary-color-1); border-bottom-color: var(--primary-color-1); }

        .badge {
            padding: 0.35rem 0.65rem;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
        }
        
        .badge.success { background: rgba(46, 204, 113, 0.15); color: #2ecc71; }
        .badge.failed { background: rgba(231, 76, 60, 0.15); color: #e74c3c; }

        .table-responsive { overflow-x: auto; width: 100%; }
        .data-table th { background: var(--bg-color-1); font-size: 0.8rem; text-transform: uppercase; }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    <?php include 'includes/admin-header.php'; ?>
    
    <div class="main-content">
        <div class="main-container">
            <div class="title">
                <nav class="breadcrumb" aria-label="Breadcrumb">
                    <ol class="breadcrumb-list">
                        <li class="breadcrumb-item"><a href="dashboard.php" class="breadcrumb-link">Dashboard</a></li>
                        <li class="breadcrumb-item active">My Profile</li>
                    </ol>
                </nav>
                <h1><i class="fas fa-user-circle" style="color: var(--primary-color-1); margin-right: 0.5rem;"></i> My Profile</h1>
                <p>Manage your account settings and view your activity history.</p>
            </div>
            
            <div class="sub-container">
                <div class="page-content">
                    <div class="profile-container">
                        <!-- Sidebar -->
                        <div class="profile-sidebar">
                            <div class="profile-header-card">
                                <div class="profile-avatar-container">
                                    <img id="profileAvatar" src="https://ui-avatars.com/api/?name=Admin&background=4c8a89&color=fff&size=256" class="profile-avatar" alt="Avatar">
                                </div>
                                <h2 id="profileNameDisplay" class="profile-name">Loading...</h2>
                                <span id="profileRoleDisplay" class="profile-role">ADMINISTRATOR</span>
                                
                                <div class="profile-stats">
                                    <div class="profile-stat-item">
                                        <span class="profile-stat-label">Total Logins</span>
                                        <span id="statTotalLogins" class="profile-stat-value">0</span>
                                    </div>
                                    <div class="profile-stat-item">
                                        <span class="profile-stat-label">Status</span>
                                        <span id="profileStatusBadge" class="badge success">Active</span>
                                    </div>
                                </div>
                            </div>

                            <div class="module-card">
                                <div class="module-card-header">
                                    <h2><i class="fas fa-info-circle"></i> Account Info</h2>
                                </div>
                                <div class="info-grid">
                                    <div class="info-item">
                                        <span class="info-label">Email Address</span>
                                        <span id="infoEmail" class="info-value">Loading...</span>
                                    </div>
                                    <div class="info-item">
                                        <span class="info-label">Username</span>
                                        <span id="infoUsername" class="info-value">Loading...</span>
                                    </div>
                                    <div class="info-item">
                                        <span class="info-label">Phone Number</span>
                                        <span id="infoPhone" class="info-value">Not set</span>
                                    </div>
                                    <div class="info-item">
                                        <span class="info-label">Account Created</span>
                                        <span id="infoCreatedAt" class="info-value">Loading...</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Main Content -->
                        <div class="profile-main">
                            <div class="module-card">
                                <div class="tab-nav">
                                    <button class="tab-btn active" onclick="switchTab('activity')">Recent Activity</button>
                                    <button class="tab-btn" onclick="switchTab('logins')">Login History</button>
                                    <button class="tab-btn" onclick="switchTab('security')">Security Settings</button>
                                </div>

                                <!-- Activity Tab -->
                                <div id="tab-activity" class="tab-content">
                                    <div class="table-responsive" style="padding: 0;">
                                        <table class="data-table" id="activityTable">
                                            <thead>
                                                <tr>
                                                    <th>Date & Time</th>
                                                    <th>Action</th>
                                                    <th>Description</th>
                                                    <th>IP Address</th>
                                                </tr>
                                            </thead>
                                            <tbody id="activityTableBody">
                                                <tr><td colspan="4" style="text-align: center; padding: 3rem;">Loading activity...</td></tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                                <!-- Login Logs Tab -->
                                <div id="tab-logins" class="tab-content" style="display: none;">
                                    <div class="table-responsive" style="padding: 0;">
                                        <table class="data-table" id="loginTable">
                                            <thead>
                                                <tr>
                                                    <th>Login Time</th>
                                                    <th>Logout Time</th>
                                                    <th>Duration</th>
                                                    <th>IP Address</th>
                                                    <th>Status</th>
                                                </tr>
                                            </thead>
                                            <tbody id="loginTableBody">
                                                <tr><td colspan="5" style="text-align: center; padding: 3rem;">Loading login history...</td></tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                                <!-- Security Tab -->
                                <div id="tab-security" class="tab-content" style="display: none; padding: 2rem;">
                                    <div style="max-width: 500px;">
                                        <h3 style="margin-bottom: 1.5rem; color: var(--text-color-1);">Update Password</h3>
                                        <form id="passwordForm">
                                            <div class="form-group">
                                                <label>Current Password</label>
                                                <input type="password" name="current_password" class="form-control" required>
                                            </div>
                                            <div class="form-group">
                                                <label>New Password</label>
                                                <input type="password" name="new_password" class="form-control" required minlength="8">
                                            </div>
                                            <div class="form-group">
                                                <label>Confirm New Password</label>
                                                <input type="password" name="confirm_password" class="form-control" required>
                                            </div>
                                            <button type="submit" class="btn btn-primary" style="margin-top: 1rem;">
                                                <i class="fas fa-key"></i> Update Password
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        async function loadProfile() {
            try {
                const response = await fetch('../api/profile.php?action=profile');
                const data = await response.json();
                if (data.success) {
                    const p = data.profile;
                    const stats = data.login_stats;
                    
                    document.getElementById('profileNameDisplay').textContent = p.name;
                    document.getElementById('profileRoleDisplay').textContent = p.role || 'ADMINISTRATOR';
                    document.getElementById('profileAvatar').src = `https://ui-avatars.com/api/?name=${encodeURIComponent(p.name)}&background=4c8a89&color=fff&size=256`;
                    
                    document.getElementById('infoEmail').textContent = p.email;
                    document.getElementById('infoUsername').textContent = p.username || p.email.split('@')[0];
                    document.getElementById('infoPhone').textContent = p.phone || 'Not provided';
                    document.getElementById('infoCreatedAt').textContent = new Date(p.created_at).toLocaleDateString(undefined, {year: 'numeric', month: 'long', day: 'numeric'});
                    
                    document.getElementById('statTotalLogins').textContent = stats.total_logins || 0;
                    
                    loadActivityLogs();
                    loadLoginLogs();
                }
            } catch (error) {
                console.error('Error loading profile:', error);
            }
        }

        async function loadActivityLogs() {
            try {
                const response = await fetch('../api/profile.php?action=activity_logs&limit=10');
                const data = await response.json();
                const tbody = document.getElementById('activityTableBody');
                
                if (data.success && data.activities.length > 0) {
                    tbody.innerHTML = data.activities.map(log => `
                        <tr>
                            <td><small>${new Date(log.created_at).toLocaleString()}</small></td>
                            <td><span class="badge" style="background: rgba(76, 138, 137, 0.1); color: var(--primary-color-1); font-weight: 700;">${log.action.replace('_', ' ').toUpperCase()}</span></td>
                            <td><div style="max-width: 300px; font-size: 0.9rem;">${log.description}</div></td>
                            <td><small>${log.ip_address}</small></td>
                        </tr>
                    `).join('');
                } else {
                    tbody.innerHTML = '<tr><td colspan="4" style="text-align: center; padding: 3rem; opacity: 0.5;">No activity logs found.</td></tr>';
                }
            } catch (error) {
                console.error('Error loading activity:', error);
            }
        }

        async function loadLoginLogs() {
            try {
                const response = await fetch('../api/profile.php?action=login_logs&limit=10');
                const data = await response.json();
                const tbody = document.getElementById('loginTableBody');
                
                if (data.success && data.logins.length > 0) {
                    tbody.innerHTML = data.logins.map(log => {
                        const duration = log.session_duration ? 
                            Math.floor(log.session_duration / 60) + ' min' : 'Active';
                        return `
                            <tr>
                                <td><small>${new Date(log.login_at).toLocaleString()}</small></td>
                                <td><small>${log.logout_at ? new Date(log.logout_at).toLocaleString() : '---'}</small></td>
                                <td>${duration}</td>
                                <td><small>${log.ip_address}</small></td>
                                <td><span class="badge ${log.login_status === 'success' ? 'success' : 'failed'}">${log.login_status}</span></td>
                            </tr>
                        `;
                    }).join('');
                } else {
                    tbody.innerHTML = '<tr><td colspan="5" style="text-align: center; padding: 3rem; opacity: 0.5;">No login history found.</td></tr>';
                }
            } catch (error) {
                console.error('Error loading logins:', error);
            }
        }

        function switchTab(tabId) {
            document.querySelectorAll('.tab-content').forEach(t => t.style.display = 'none');
            document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
            
            document.getElementById('tab-' + tabId).style.display = 'block';
            event.target.classList.add('active');
        }

        document.getElementById('passwordForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(e.target);
            
            if (formData.get('new_password') !== formData.get('confirm_password')) {
                alert('New passwords do not match!');
                return;
            }

            alert('Password update functionality is handled by the security service. This form is a UI shell for this module.');
        });

        document.addEventListener('DOMContentLoaded', loadProfile);
    </script>
</body>
</html>
