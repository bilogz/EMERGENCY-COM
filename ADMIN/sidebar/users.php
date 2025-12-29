<?php
/**
 * User Management Portal
 * Super Admin can create and manage Admin and Staff accounts
 */

// Start session and check authentication
session_start();

// Check if user is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../login.php');
    exit();
}

// Check if user is super_admin
$userRole = $_SESSION['admin_role'] ?? 'admin';
$isSuperAdmin = ($userRole === 'super_admin');

$pageTitle = 'User Management';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?> - Emergency Communication System</title>
    <link rel="icon" type="image/x-icon" href="images/favicon.ico">
    <link rel="stylesheet" href="css/global.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/sidebar.css">
    <link rel="stylesheet" href="css/admin-header.css">
    <link rel="stylesheet" href="css/buttons.css">
    <link rel="stylesheet" href="css/hero.css">
    <link rel="stylesheet" href="css/sidebar-footer.css">
    <link rel="stylesheet" href="css/forms.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <style>
        .user-management-container {
            padding: 1rem 0;
        }
        
        .user-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: var(--card-bg-1);
            border-radius: 12px;
            padding: 1.5rem;
            border-left: 4px solid var(--primary-color-1);
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .stat-card.admin { border-left-color: #3498db; }
        .stat-card.staff { border-left-color: #2ecc71; }
        .stat-card.pending { border-left-color: #f39c12; }
        .stat-card.inactive { border-left-color: #e74c3c; }
        
        .stat-card .stat-value {
            font-size: 2rem;
            font-weight: bold;
            color: var(--primary-color-1);
        }
        
        .stat-card .stat-label {
            color: var(--text-secondary-1);
            font-size: 0.9rem;
        }
        
        .action-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
            gap: 1rem;
        }
        
        .search-box {
            display: flex;
            align-items: center;
            background: var(--card-bg-1);
            border: 1px solid var(--border-color-1);
            border-radius: 8px;
            padding: 0.5rem 1rem;
            flex: 1;
            max-width: 400px;
        }
        
        .search-box input {
            border: none;
            background: transparent;
            outline: none;
            width: 100%;
            color: var(--text-color-1);
            font-size: 0.95rem;
        }
        
        .search-box i {
            color: var(--text-secondary-1);
            margin-right: 0.5rem;
        }
        
        .filter-buttons {
            display: flex;
            gap: 0.5rem;
        }
        
        .filter-btn {
            padding: 0.5rem 1rem;
            border: 1px solid var(--border-color-1);
            background: var(--card-bg-1);
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.85rem;
            color: var(--text-color-1);
            transition: all 0.15s ease;
        }
        
        .filter-btn:hover {
            background: var(--primary-color-1);
            border-color: var(--primary-color-1);
            color: white;
            transform: translateY(-1px);
        }
        
        .filter-btn.active {
            background: var(--primary-color-1);
            border-color: var(--primary-color-1);
            color: white;
        }
        
        .filter-btn:active {
            transform: translateY(0) scale(0.98);
        }
        
        .users-table-container {
            background: var(--card-bg-1);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            border: 1px solid var(--border-color-1);
        }
        
        .users-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .users-table th,
        .users-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid var(--border-color-1);
        }
        
        .users-table th {
            background: var(--bg-color-1);
            font-weight: 600;
            color: var(--text-color-1);
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .users-table tr:hover {
            background: var(--bg-color-1);
        }
        
        .users-table tr:last-child td {
            border-bottom: none;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--primary-color-1);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 1rem;
        }
        
        .user-details .user-name {
            font-weight: 600;
            color: var(--text-color-1);
        }
        
        .user-details .user-email {
            font-size: 0.85rem;
            color: var(--text-secondary-1);
        }
        
        .role-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .role-badge.super_admin {
            background: rgba(155, 89, 182, 0.15);
            color: #9b59b6;
        }
        
        .role-badge.admin {
            background: rgba(52, 152, 219, 0.15);
            color: #3498db;
        }
        
        .role-badge.staff {
            background: rgba(46, 204, 113, 0.15);
            color: #2ecc71;
        }
        
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .status-badge.active {
            background: rgba(46, 204, 113, 0.15);
            color: #2ecc71;
        }
        
        .status-badge.inactive {
            background: rgba(231, 76, 60, 0.15);
            color: #e74c3c;
        }
        
        .status-badge.pending_approval {
            background: rgba(243, 156, 18, 0.15);
            color: #f39c12;
        }
        
        .action-buttons {
            display: flex;
            gap: 0.5rem;
        }
        
        .action-btn {
            width: 36px;
            height: 36px;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.15s ease;
        }
        
        .action-btn.edit {
            background: rgba(52, 152, 219, 0.15);
            color: #3498db;
        }
        
        .action-btn.delete {
            background: rgba(231, 76, 60, 0.15);
            color: #e74c3c;
        }
        
        .action-btn:hover {
            transform: scale(1.1);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        
        .action-btn:active {
            transform: scale(0.95);
            box-shadow: none;
        }
        
        .action-btn.edit:hover {
            background: #3498db;
            color: white;
        }
        
        .action-btn.delete:hover {
            background: #e74c3c;
            color: white;
        }
        
        /* Modal Styles */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
            z-index: 10000;
            align-items: center;
            justify-content: center;
        }

        .modal-overlay.show {
            display: flex;
        }

        .modal-content {
            background: var(--card-bg-1);
            border-radius: 16px;
            width: 90%;
            max-width: 500px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
            z-index: 10001;
            position: relative;
            pointer-events: auto;
        }
        
        .modal-header {
            padding: 1.5rem;
            border-bottom: 1px solid var(--border-color-1);
            display: flex;
            justify-content: space-between;
            align-items: center;
            pointer-events: auto;
            position: relative;
            z-index: 10004;
        }
        
        .modal-header h2 {
            margin: 0;
            font-size: 1.25rem;
            color: var(--text-color-1);
        }
        
        .modal-close {
            width: 36px;
            height: 36px;
            background: var(--bg-color-1);
            border: 1px solid var(--border-color-1);
            border-radius: 8px;
            font-size: 1.25rem;
            cursor: pointer;
            color: var(--text-secondary-1);
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
            pointer-events: auto !important;
            position: relative;
            z-index: 10003 !important;
            flex-shrink: 0;
            user-select: none;
            -webkit-user-select: none;
            -moz-user-select: none;
            -ms-user-select: none;
            touch-action: manipulation;
        }
        
        .modal-close:hover {
            background: #e74c3c;
            border-color: #e74c3c;
            color: white;
            transform: scale(1.05);
        }
        
        .modal-close:active {
            transform: scale(0.95);
        }
        
        .modal-body {
            padding: 1.5rem;
            pointer-events: auto;
            position: relative;
            z-index: 10002;
        }
        
        .form-group {
            margin-bottom: 1.25rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--text-color-1);
        }
        
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid var(--border-color-1);
            border-radius: 8px;
            font-size: 0.95rem;
            background: var(--bg-color-1);
            color: var(--text-color-1);
            transition: border-color 0.2s;
        }
        
        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: var(--primary-color-1);
        }
        
        .form-group small {
            display: block;
            margin-top: 0.25rem;
            color: var(--text-secondary-1);
            font-size: 0.8rem;
        }
        
        .modal-footer {
            padding: 1rem 1.5rem;
            border-top: 1px solid var(--border-color-1);
            display: flex;
            justify-content: flex-end;
            gap: 0.75rem;
            pointer-events: auto;
            position: relative;
            z-index: 10005;
        }
        
        .btn {
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-size: 0.95rem;
            font-weight: 500;
            cursor: pointer;
            border: none;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            pointer-events: auto !important;
            position: relative;
            z-index: 10006;
        }
        
        .btn:active {
            transform: scale(0.95);
        }
        
        .btn-primary {
            background: var(--primary-color-1);
            color: white;
            pointer-events: auto !important;
            position: relative;
            z-index: 10007;
        }
        
        .btn-primary:hover {
            background: #3d7a79;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(76, 138, 137, 0.3);
        }
        
        .btn-primary:active {
            transform: translateY(0) scale(0.98);
            box-shadow: none;
        }
        
        .btn-secondary {
            background: var(--bg-color-1);
            color: var(--text-color-1);
            border: 1px solid var(--border-color-1);
            pointer-events: auto !important;
            position: relative;
            z-index: 10007;
        }
        
        .btn-secondary:hover {
            background: var(--border-color-1);
            transform: translateY(-1px);
        }
        
        .btn-secondary:active {
            transform: translateY(0) scale(0.98);
        }
        
        .btn-add {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .empty-state {
            text-align: center;
            padding: 3rem;
            color: var(--text-secondary-1);
        }
        
        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }
        
        .access-denied {
            text-align: center;
            padding: 3rem;
            background: var(--card-bg-1);
            border-radius: 12px;
            border: 1px solid var(--border-color-1);
        }
        
        .access-denied i {
            font-size: 4rem;
            color: #e74c3c;
            margin-bottom: 1rem;
        }
        
        .access-denied h2 {
            color: var(--text-color-1);
            margin-bottom: 0.5rem;
        }
        
        .access-denied p {
            color: var(--text-secondary-1);
        }
        
        @media (max-width: 768px) {
            .action-bar {
                flex-direction: column;
                align-items: stretch;
            }

            .search-box {
                max-width: none;
            }

            .filter-buttons {
                flex-wrap: wrap;
            }

            .users-table {
                display: block;
                overflow-x: auto;
            }

            .modal-content {
                width: 95%;
                max-width: none;
                margin: 1rem;
                max-height: 95vh;
            }

            .modal-header,
            .modal-body,
            .modal-footer {
                padding: 1rem;
            }

            .modal-footer {
                flex-direction: column;
                gap: 0.75rem;
            }

            .modal-footer .btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <!-- Include Sidebar Component -->
    <?php include 'includes/sidebar.php'; ?>

    <!-- Include Admin Header Component -->
    <?php include 'includes/admin-header.php'; ?>
    
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
                            <span>User Management</span>
                        </li>
                    </ol>
                </nav>
                <h1><i class="fas fa-users-cog"></i> User Management</h1>
                <p>Create and manage administrator and staff accounts for the Emergency Communication System.</p>
            </div>
            
            <div class="sub-container">
                <div class="page-content">
                    <?php if (!$isSuperAdmin): ?>
                    <!-- Access Denied for non-super admins -->
                    <div class="access-denied">
                        <i class="fas fa-lock"></i>
                        <h2>Access Restricted</h2>
                        <p>Only Super Administrators can access the User Management portal.</p>
                        <p>Your current role: <strong><?php echo htmlspecialchars(ucfirst($userRole)); ?></strong></p>
                        <br>
                        <a href="dashboard.php" class="btn btn-primary">
                            <i class="fas fa-arrow-left"></i> Back to Dashboard
                        </a>
                    </div>
                    <?php else: ?>
                    <!-- User Management Content -->
                    <div class="user-management-container">
                        <!-- Stats Cards -->
                        <div class="user-stats">
                            <div class="stat-card admin">
                                <div class="stat-value" id="totalAdmins">0</div>
                                <div class="stat-label"><i class="fas fa-user-shield"></i> Administrators</div>
                            </div>
                            <div class="stat-card staff">
                                <div class="stat-value" id="totalStaff">0</div>
                                <div class="stat-label"><i class="fas fa-user-tie"></i> Staff Members</div>
                            </div>
                            <div class="stat-card pending">
                                <div class="stat-value" id="totalPending">0</div>
                                <div class="stat-label"><i class="fas fa-clock"></i> Pending Approval</div>
                            </div>
                            <div class="stat-card inactive">
                                <div class="stat-value" id="totalInactive">0</div>
                                <div class="stat-label"><i class="fas fa-user-slash"></i> Inactive</div>
                            </div>
                        </div>
                        
                        <!-- Action Bar -->
                        <div class="action-bar">
                            <div class="search-box">
                                <i class="fas fa-search"></i>
                                <input type="text" id="searchInput" placeholder="Search users by name or email...">
                            </div>
                            
                            <div class="filter-buttons">
                                <button class="filter-btn active" data-filter="all">All</button>
                                <button class="filter-btn" data-filter="super_admin">Super Admin</button>
                                <button class="filter-btn" data-filter="admin">Admin</button>
                                <button class="filter-btn" data-filter="staff">Staff</button>
                            </div>
                            
                            <button class="btn btn-primary btn-add" onclick="openCreateModal()">
                                <i class="fas fa-plus"></i> Add User
                            </button>
                        </div>
                        
                        <!-- Users Table -->
                        <div class="users-table-container">
                            <table class="users-table">
                                <thead>
                                    <tr>
                                        <th>User</th>
                                        <th>Role</th>
                                        <th>Status</th>
                                        <th>Created</th>
                                        <th>Last Login</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="usersTableBody">
                                    <tr>
                                        <td colspan="6">
                                            <div class="empty-state">
                                                <i class="fas fa-spinner fa-spin"></i>
                                                <p>Loading users...</p>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Create/Edit User Modal -->
    <div class="modal-overlay" id="userModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle"><i class="fas fa-user-plus"></i> Create New User</h2>
                <button type="button" class="modal-close" id="modalCloseBtn" onclick="event.stopPropagation(); closeModal(); return false;">&times;</button>
            </div>
            <div class="modal-body">
                <form id="userForm">
                    <input type="hidden" id="userId" name="id">
                    
                    <div class="form-group">
                        <label for="userName"><i class="fas fa-user"></i> Full Name *</label>
                        <input type="text" id="userName" name="name" required placeholder="Enter full name">
                    </div>
                    
                    <div class="form-group">
                        <label for="userEmail"><i class="fas fa-envelope"></i> Email Address *</label>
                        <input type="email" id="userEmail" name="email" required placeholder="Enter email address">
                    </div>
                    
                    <div class="form-group" id="passwordGroup">
                        <label for="userPassword"><i class="fas fa-lock"></i> Password *</label>
                        <input type="password" id="userPassword" name="password" placeholder="Enter password (min 8 characters)">
                        <small>Leave blank to keep existing password (when editing)</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="userRole"><i class="fas fa-user-tag"></i> Role *</label>
                        <select id="userRole" name="role" required>
                            <option value="">Select a role</option>
                            <option value="admin">Administrator</option>
                            <option value="staff">Staff</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="userStatus"><i class="fas fa-toggle-on"></i> Status *</label>
                        <select id="userStatus" name="status" required>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                            <option value="pending_approval">Pending Approval</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="event.stopPropagation(); closeModal(); return false;">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="saveUser()">
                    <i class="fas fa-save"></i> Save User
                </button>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        <?php if ($isSuperAdmin): ?>
        let users = [];
        let currentFilter = 'all';
        
        // Load users on page load
        document.addEventListener('DOMContentLoaded', function() {
            loadUsers();
            
            // Search functionality
            document.getElementById('searchInput').addEventListener('input', filterUsers);
            
            // Filter buttons
            document.querySelectorAll('.filter-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
                    this.classList.add('active');
                    currentFilter = this.dataset.filter;
                    filterUsers();
                });
            });

            // Prevent modal content clicks from closing the modal
            const modalContent = document.querySelector('.modal-content');
            if (modalContent) {
                modalContent.addEventListener('click', function(e) {
                    e.stopPropagation();
                });
            }

            // Set up overlay click handler
            const modalOverlay = document.getElementById('userModal');
            if (modalOverlay) {
                modalOverlay.addEventListener('click', function(e) {
                    // Only close if clicking directly on the overlay background, not on modal content
                    if (e.target === this) {
                        closeModal();
                    }
                });
            }
        });

        // Simplified close button handler - the onclick in HTML should work, but this is backup
        document.addEventListener('click', function(e) {
            // Handle close button clicks
            if (e.target.closest('.modal-close') || e.target.classList.contains('modal-close')) {
                e.stopPropagation();
                e.preventDefault();
                closeModal();
                return false;
            }
        });
        
        function loadUsers() {
            fetch('../api/user-management.php?action=list')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        users = data.users;
                        updateStats(data.stats);
                        renderUsers(users);
                    } else {
                        showError(data.message || 'Failed to load users');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showError('Failed to load users. Please try again.');
                });
        }
        
        function updateStats(stats) {
            document.getElementById('totalAdmins').textContent = stats.admins || 0;
            document.getElementById('totalStaff').textContent = stats.staff || 0;
            document.getElementById('totalPending').textContent = stats.pending || 0;
            document.getElementById('totalInactive').textContent = stats.inactive || 0;
        }
        
        function renderUsers(usersToRender) {
            const tbody = document.getElementById('usersTableBody');
            
            if (usersToRender.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="6">
                            <div class="empty-state">
                                <i class="fas fa-users"></i>
                                <p>No users found</p>
                            </div>
                        </td>
                    </tr>
                `;
                return;
            }
            
            tbody.innerHTML = usersToRender.map(user => `
                <tr data-id="${user.id}">
                    <td>
                        <div class="user-info">
                            <div class="user-avatar">${getInitials(user.name)}</div>
                            <div class="user-details">
                                <div class="user-name">${escapeHtml(user.name)}</div>
                                <div class="user-email">${escapeHtml(user.email)}</div>
                            </div>
                        </div>
                    </td>
                    <td><span class="role-badge ${user.role}">${formatRole(user.role)}</span></td>
                    <td><span class="status-badge ${user.status}">${formatStatus(user.status)}</span></td>
                    <td>${formatDate(user.created_at)}</td>
                    <td>${user.last_login ? formatDate(user.last_login) : '<span style="color: var(--text-secondary-1);">Never</span>'}</td>
                    <td>
                        <div class="action-buttons">
                            <button class="action-btn edit" onclick="editUser(${user.id})" title="Edit User">
                                <i class="fas fa-edit"></i>
                            </button>
                            ${user.role !== 'super_admin' ? `
                            <button class="action-btn delete" onclick="deleteUser(${user.id}, '${escapeHtml(user.name)}')" title="Delete User">
                                <i class="fas fa-trash"></i>
                            </button>
                            ` : ''}
                        </div>
                    </td>
                </tr>
            `).join('');
        }
        
        function filterUsers() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            
            let filtered = users.filter(user => {
                const matchesSearch = user.name.toLowerCase().includes(searchTerm) || 
                                     user.email.toLowerCase().includes(searchTerm);
                const matchesFilter = currentFilter === 'all' || user.role === currentFilter;
                return matchesSearch && matchesFilter;
            });
            
            renderUsers(filtered);
        }
        
        function openCreateModal() {
            document.getElementById('modalTitle').innerHTML = '<i class="fas fa-user-plus"></i> Create New User';
            document.getElementById('userForm').reset();
            document.getElementById('userId').value = '';
            document.getElementById('userPassword').required = true;
            document.getElementById('userModal').classList.add('show');
        }
        
        function editUser(id) {
            const user = users.find(u => u.id === id);
            if (!user) return;
            
            document.getElementById('modalTitle').innerHTML = '<i class="fas fa-user-edit"></i> Edit User';
            document.getElementById('userId').value = user.id;
            document.getElementById('userName').value = user.name;
            document.getElementById('userEmail').value = user.email;
            document.getElementById('userPassword').value = '';
            document.getElementById('userPassword').required = false;
            document.getElementById('userRole').value = user.role;
            document.getElementById('userStatus').value = user.status;
            document.getElementById('userModal').classList.add('show');
        }
        
        function closeModal() {
            document.getElementById('userModal').classList.remove('show');
        }
        
        function saveUser() {
            const form = document.getElementById('userForm');
            const formData = new FormData(form);
            
            const data = {
                action: formData.get('id') ? 'update' : 'create',
                id: formData.get('id'),
                name: formData.get('name'),
                email: formData.get('email'),
                password: formData.get('password'),
                role: formData.get('role'),
                status: formData.get('status')
            };
            
            // Validation
            if (!data.name || !data.email || !data.role || !data.status) {
                Swal.fire('Error', 'Please fill in all required fields', 'error');
                return;
            }
            
            if (!data.id && !data.password) {
                Swal.fire('Error', 'Password is required for new users', 'error');
                return;
            }
            
            if (data.password && data.password.length < 8) {
                Swal.fire('Error', 'Password must be at least 8 characters', 'error');
                return;
            }
            
            fetch('../api/user-management.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    // Show success notification and reload users
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: result.message,
                        timer: 2000,
                        showConfirmButton: false,
                        toast: true,
                        position: 'top-end'
                    });
                    loadUsers();

                    // Close modal after a brief delay to show the success message
                    setTimeout(() => {
                        closeModal();
                    }, 500);
                } else {
                    Swal.fire('Error', result.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire('Error', 'Failed to save user. Please try again.', 'error');
            });
        }
        
        function deleteUser(id, name) {
            Swal.fire({
                title: 'Delete User?',
                html: `Are you sure you want to delete <strong>${escapeHtml(name)}</strong>?<br>This action cannot be undone.`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#e74c3c',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, delete',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch('../api/user-management.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ action: 'delete', id: id })
                    })
                    .then(response => response.json())
                    .then(result => {
                        if (result.success) {
                            Swal.fire('Deleted!', result.message, 'success');
                            loadUsers();
                        } else {
                            Swal.fire('Error', result.message, 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        Swal.fire('Error', 'Failed to delete user', 'error');
                    });
                }
            });
        }
        
        // Helper functions
        function getInitials(name) {
            return name.split(' ').map(n => n[0]).join('').toUpperCase().substring(0, 2);
        }
        
        function formatRole(role) {
            const roles = {
                'super_admin': 'Super Admin',
                'admin': 'Administrator',
                'staff': 'Staff'
            };
            return roles[role] || role;
        }
        
        function formatStatus(status) {
            const statuses = {
                'active': 'Active',
                'inactive': 'Inactive',
                'pending_approval': 'Pending'
            };
            return statuses[status] || status;
        }
        
        function formatDate(dateStr) {
            if (!dateStr) return 'N/A';
            const date = new Date(dateStr);
            return date.toLocaleDateString('en-US', { 
                year: 'numeric', 
                month: 'short', 
                day: 'numeric' 
            });
        }
        
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        function showError(message) {
            document.getElementById('usersTableBody').innerHTML = `
                <tr>
                    <td colspan="6">
                        <div class="empty-state">
                            <i class="fas fa-exclamation-triangle" style="color: #e74c3c;"></i>
                            <p>${message}</p>
                        </div>
                    </td>
                </tr>
            `;
        }
        
        // Close modal on escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') closeModal();
        });
        <?php endif; ?>
    </script>
</body>
</html>
