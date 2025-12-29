<?php
/**
 * User Management Portal - Modern Responsive Version
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
        /* ===================================
           USER MANAGEMENT RESPONSIVE STYLES
           =================================== */
        
        .user-management-container {
            padding: 1rem 0;
        }
        
        /* Stats Cards - Responsive Grid */
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
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        
        .stat-card.admin { border-left-color: #3498db; }
        .stat-card.staff { border-left-color: #2ecc71; }
        .stat-card.pending { border-left-color: #f39c12; }
        .stat-card.inactive { border-left-color: #e74c3c; }
        
        .stat-card .stat-value {
            font-size: 2rem;
            font-weight: bold;
            color: var(--primary-color-1);
            margin-bottom: 0.5rem;
        }
        
        .stat-card .stat-label {
            color: var(--text-secondary-1);
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        /* Action Bar - Responsive */
        .action-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
            gap: 1rem;
        }
        
        .search-filter-container {
            display: flex;
            gap: 1rem;
            flex: 1;
            min-width: 0;
            flex-wrap: wrap;
        }
        
        .search-box {
            display: flex;
            align-items: center;
            background: var(--card-bg-1);
            border: 1px solid var(--border-color-1);
            border-radius: 8px;
            padding: 0.75rem 1rem;
            flex: 1;
            min-width: 200px;
            max-width: 400px;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }
        
        .search-box:focus-within {
            border-color: var(--primary-color-1);
            box-shadow: 0 0 0 3px rgba(76, 138, 137, 0.1);
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
            margin-right: 0.75rem;
            font-size: 1rem;
        }
        
        .filter-buttons {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }
        
        .filter-btn {
            padding: 0.75rem 1.25rem;
            border: 1px solid var(--border-color-1);
            background: var(--card-bg-1);
            border-radius: 8px;
            cursor: pointer;
            font-size: 0.875rem;
            color: var(--text-color-1);
            transition: all 0.2s ease;
            font-weight: 500;
            white-space: nowrap;
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
        
        .btn-add {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            white-space: nowrap;
        }
        
        /* Users Table - Desktop View */
        .users-table-container {
            background: var(--card-bg-1);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            border: 1px solid var(--border-color-1);
            overflow-x: auto;
        }
        
        .users-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 800px;
        }
        
        .users-table th,
        .users-table td {
            padding: 1.25rem 1rem;
            text-align: left;
            border-bottom: 1px solid var(--border-color-1);
        }
        
        .users-table th {
            background: var(--bg-color-1);
            font-weight: 600;
            color: var(--text-color-1);
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            position: sticky;
            top: 0;
            z-index: 1;
        }
        
        .users-table tr:hover {
            background: var(--bg-color-1);
        }
        
        .users-table tr:last-child td {
            border-bottom: none;
        }
        
        /* User Cards - Mobile View */
        .users-cards-container {
            display: none;
            gap: 1rem;
        }
        
        .user-card {
            background: var(--card-bg-1);
            border-radius: 12px;
            padding: 1.5rem;
            border: 1px solid var(--border-color-1);
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        
        .user-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        
        .user-card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--border-color-1);
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .user-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: var(--primary-color-1);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 1.125rem;
            flex-shrink: 0;
        }
        
        .user-details .user-name {
            font-weight: 600;
            color: var(--text-color-1);
            font-size: 1.125rem;
            margin-bottom: 0.25rem;
        }
        
        .user-details .user-email {
            font-size: 0.875rem;
            color: var(--text-secondary-1);
        }
        
        .user-card-body {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-bottom: 1rem;
        }
        
        .user-card-field {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }
        
        .user-card-field-label {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--text-secondary-1);
            font-weight: 600;
        }
        
        .user-card-field-value {
            font-size: 0.9375rem;
            color: var(--text-color-1);
        }
        
        .user-card-footer {
            display: flex;
            justify-content: flex-end;
            gap: 0.75rem;
            padding-top: 1rem;
            border-top: 1px solid var(--border-color-1);
        }
        
        /* Badges */
        .role-badge, .status-badge {
            padding: 0.375rem 0.875rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: inline-block;
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
        
        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 0.5rem;
        }
        
        .action-btn {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
            font-size: 1rem;
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
        
        .action-btn.edit:hover {
            background: #3498db;
            color: white;
        }
        
        .action-btn.delete:hover {
            background: #e74c3c;
            color: white;
        }
        
        /* Modal Styles - Responsive */
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
            padding: 1rem;
            opacity: 0;
            transition: opacity 0.2s ease;
            overflow-y: auto;
        }

        .modal-overlay.show {
            display: flex;
            opacity: 1;
        }

        .modal-content {
            background: var(--card-bg-1);
            border-radius: 16px;
            width: 100%;
            max-width: 500px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
            position: relative;
            margin: auto;
            animation: modalSlideIn 0.3s ease;
        }
        
        @keyframes modalSlideIn {
            from {
                transform: translateY(-20px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .modal-header {
            padding: 1.5rem;
            border-bottom: 1px solid var(--border-color-1);
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            background: var(--card-bg-1);
            z-index: 10;
        }

        .modal-header h2 {
            margin: 0;
            font-size: 1.5rem;
            color: var(--text-color-1);
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .modal-close {
            width: 40px;
            height: 40px;
            background: var(--bg-color-1);
            border: 1px solid var(--border-color-1);
            border-radius: 8px;
            font-size: 1.5rem;
            cursor: pointer;
            color: var(--text-secondary-1);
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
            flex-shrink: 0;
            position: relative;
            z-index: 10001;
            pointer-events: auto;
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
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.75rem;
            font-weight: 600;
            color: var(--text-color-1);
            font-size: 0.9375rem;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 0.875rem 1rem;
            border: 1px solid var(--border-color-1);
            border-radius: 8px;
            font-size: 0.9375rem;
            background: var(--bg-color-1);
            color: var(--text-color-1);
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: var(--primary-color-1);
            box-shadow: 0 0 0 3px rgba(76, 138, 137, 0.1);
        }

        .form-group small {
            display: block;
            margin-top: 0.5rem;
            color: var(--text-secondary-1);
            font-size: 0.8125rem;
        }

        .modal-footer {
            padding: 1.25rem 1.5rem;
            border-top: 1px solid var(--border-color-1);
            display: flex;
            justify-content: flex-end;
            gap: 0.75rem;
            position: sticky;
            bottom: 0;
            background: var(--card-bg-1);
        }

        .btn {
            padding: 0.875rem 1.75rem;
            border-radius: 8px;
            font-size: 0.9375rem;
            font-weight: 600;
            cursor: pointer;
            border: none;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            position: relative;
            z-index: 10001;
            pointer-events: auto;
        }

        .btn:active {
            transform: scale(0.98);
        }

        .btn-primary {
            background: var(--primary-color-1);
            color: white;
        }

        .btn-primary:hover {
            background: #3d7a79;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(76, 138, 137, 0.3);
        }

        .btn-secondary {
            background: var(--bg-color-1);
            color: var(--text-color-1);
            border: 1px solid var(--border-color-1);
        }

        .btn-secondary:hover {
            background: var(--border-color-1);
            transform: translateY(-1px);
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 3rem 1.5rem;
            color: var(--text-secondary-1);
        }
        
        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }
        
        .empty-state p {
            font-size: 1.125rem;
            margin: 0;
        }
        
        .access-denied {
            text-align: center;
            padding: 3rem 1.5rem;
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
            margin-bottom: 1.5rem;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .user-stats {
                grid-template-columns: repeat(2, 1fr);
                gap: 0.75rem;
            }
            
            .stat-card {
                padding: 1rem;
            }
            
            .stat-card .stat-value {
                font-size: 1.5rem;
            }
            
            .action-bar {
                flex-direction: column;
                align-items: stretch;
            }
            
            .search-filter-container {
                flex-direction: column;
            }
            
            .search-box {
                max-width: 100%;
            }
            
            .filter-buttons {
                width: 100%;
            }
            
            .filter-btn {
                flex: 1;
                text-align: center;
                padding: 0.625rem 0.875rem;
                font-size: 0.8125rem;
            }
            
            .btn-add {
                width: 100%;
                justify-content: center;
            }
            
            /* Hide table on mobile, show cards */
            .users-table-container {
                display: none;
            }
            
            .users-cards-container {
                display: grid;
            }
            
            .user-card-body {
                grid-template-columns: 1fr;
            }
            
            .user-card-footer {
                flex-direction: column;
            }
            
            .user-card-footer .btn {
                width: 100%;
            }
            
            .modal-content {
                max-width: 100%;
                margin: 0;
                border-radius: 16px 16px 0 0;
                max-height: 95vh;
            }
            
            .modal-header {
                padding: 1.25rem;
            }
            
            .modal-header h2 {
                font-size: 1.25rem;
            }
            
            .modal-body {
                padding: 1.25rem;
            }
            
            .modal-footer {
                padding: 1rem 1.25rem;
                flex-direction: column-reverse;
            }
            
            .modal-footer .btn {
                width: 100%;
            }
        }
        
        @media (max-width: 480px) {
            .user-stats {
                grid-template-columns: 1fr;
            }
            
            .user-info {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.75rem;
            }
            
            .user-avatar {
                width: 40px;
                height: 40px;
                font-size: 1rem;
            }
        }
        
        @media (min-width: 769px) {
            .users-cards-container {
                display: none !important;
            }
            
            .users-table-container {
                display: block;
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
                                <div class="stat-label">
                                    <i class="fas fa-user-shield"></i> Administrators
                                </div>
                            </div>
                            <div class="stat-card staff">
                                <div class="stat-value" id="totalStaff">0</div>
                                <div class="stat-label">
                                    <i class="fas fa-user-tie"></i> Staff Members
                                </div>
                            </div>
                            <div class="stat-card pending">
                                <div class="stat-value" id="totalPending">0</div>
                                <div class="stat-label">
                                    <i class="fas fa-clock"></i> Pending Approval
                                </div>
                            </div>
                            <div class="stat-card inactive">
                                <div class="stat-value" id="totalInactive">0</div>
                                <div class="stat-label">
                                    <i class="fas fa-user-slash"></i> Inactive
                                </div>
                            </div>
                        </div>
                        
                        <!-- Action Bar -->
                        <div class="action-bar">
                            <div class="search-filter-container">
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
                            </div>
                            
                            <button class="btn btn-primary btn-add" onclick="openCreateModal()">
                                <i class="fas fa-plus"></i> Add User
                            </button>
                        </div>
                        
                        <!-- Users Table (Desktop) -->
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
                        
                        <!-- Users Cards (Mobile) -->
                        <div class="users-cards-container" id="usersCardsContainer">
                            <!-- Cards will be inserted here by JavaScript -->
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
                <button type="button" class="modal-close" id="modalCloseBtn" onclick="closeModal(); return false;" aria-label="Close">&times;</button>
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
                <button type="button" class="btn btn-secondary" id="cancelBtn" onclick="closeModal(); return false;">Cancel</button>
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

            // Modal overlay click handler
            const modalOverlay = document.getElementById('userModal');
            if (modalOverlay) {
                modalOverlay.addEventListener('click', function(e) {
                    if (e.target === modalOverlay) {
                        closeModal();
                    }
                });
            }

            // Prevent modal content clicks from closing the modal (but allow button clicks)
            const modalContent = document.querySelector('#userModal .modal-content');
            if (modalContent) {
                modalContent.addEventListener('click', function(e) {
                    // Don't stop propagation for buttons
                    const isButton = e.target.closest('button');
                    if (!isButton) {
                        e.stopPropagation();
                    }
                });
            }
            
            // Add direct event listeners to close and cancel buttons for reliability
            const closeBtn = document.getElementById('modalCloseBtn');
            const cancelBtn = document.getElementById('cancelBtn');
            
            if (closeBtn) {
                closeBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    if (typeof closeModal === 'function') {
                        closeModal();
                    } else if (typeof window.closeModal === 'function') {
                        window.closeModal();
                    }
                    return false;
                });
            }
            
            if (cancelBtn) {
                cancelBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    if (typeof closeModal === 'function') {
                        closeModal();
                    } else if (typeof window.closeModal === 'function') {
                        window.closeModal();
                    }
                    return false;
                });
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
            // Render table (desktop)
            renderUsersTable(usersToRender);
            // Render cards (mobile)
            renderUsersCards(usersToRender);
        }
        
        function renderUsersTable(usersToRender) {
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
                            <button class="action-btn edit" onclick="editUser(${user.id})" title="Edit User" aria-label="Edit User">
                                <i class="fas fa-edit"></i>
                            </button>
                            ${user.role !== 'super_admin' ? `
                            <button class="action-btn delete" onclick="deleteUser(${user.id}, '${escapeHtml(user.name)}')" title="Delete User" aria-label="Delete User">
                                <i class="fas fa-trash"></i>
                            </button>
                            ` : ''}
                        </div>
                    </td>
                </tr>
            `).join('');
        }
        
        function renderUsersCards(usersToRender) {
            const container = document.getElementById('usersCardsContainer');
            
            if (usersToRender.length === 0) {
                container.innerHTML = `
                    <div class="empty-state">
                        <i class="fas fa-users"></i>
                        <p>No users found</p>
                    </div>
                `;
                return;
            }
            
            container.innerHTML = usersToRender.map(user => `
                <div class="user-card" data-id="${user.id}">
                    <div class="user-card-header">
                        <div class="user-info">
                            <div class="user-avatar">${getInitials(user.name)}</div>
                            <div class="user-details">
                                <div class="user-name">${escapeHtml(user.name)}</div>
                                <div class="user-email">${escapeHtml(user.email)}</div>
                            </div>
                        </div>
                    </div>
                    <div class="user-card-body">
                        <div class="user-card-field">
                            <span class="user-card-field-label">Role</span>
                            <span class="user-card-field-value">
                                <span class="role-badge ${user.role}">${formatRole(user.role)}</span>
                            </span>
                        </div>
                        <div class="user-card-field">
                            <span class="user-card-field-label">Status</span>
                            <span class="user-card-field-value">
                                <span class="status-badge ${user.status}">${formatStatus(user.status)}</span>
                            </span>
                        </div>
                        <div class="user-card-field">
                            <span class="user-card-field-label">Created</span>
                            <span class="user-card-field-value">${formatDate(user.created_at)}</span>
                        </div>
                        <div class="user-card-field">
                            <span class="user-card-field-label">Last Login</span>
                            <span class="user-card-field-value">${user.last_login ? formatDate(user.last_login) : 'Never'}</span>
                        </div>
                    </div>
                    <div class="user-card-footer">
                        <button class="btn btn-secondary" onclick="editUser(${user.id})">
                            <i class="fas fa-edit"></i> Edit
                        </button>
                        ${user.role !== 'super_admin' ? `
                        <button class="btn btn-primary" style="background: #e74c3c;" onclick="deleteUser(${user.id}, '${escapeHtml(user.name)}')">
                            <i class="fas fa-trash"></i> Delete
                        </button>
                        ` : ''}
                    </div>
                </div>
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
            document.body.style.overflow = 'hidden';
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
            document.body.style.overflow = 'hidden';
        }
        
        function closeModal() {
            const modal = document.getElementById('userModal');
            if (modal) {
                modal.classList.remove('show');
                document.body.style.overflow = '';
                // Reset the form
                const form = document.getElementById('userForm');
                if (form) {
                    form.reset();
                }
                // Clear hidden userId field
                const userIdField = document.getElementById('userId');
                if (userIdField) {
                    userIdField.value = '';
                }
            }
        }
        
        // Close modal on escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' || e.keyCode === 27) {
                const modal = document.getElementById('userModal');
                if (modal && modal.classList.contains('show')) {
                    closeModal();
                }
            }
        });
        
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
            document.getElementById('usersCardsContainer').innerHTML = `
                <div class="empty-state">
                    <i class="fas fa-exclamation-triangle" style="color: #e74c3c;"></i>
                    <p>${message}</p>
                </div>
            `;
        }
        
        // Make functions globally accessible BEFORE PHP conditional ends
        window.closeModal = closeModal;
        window.openCreateModal = openCreateModal;
        window.editUser = editUser;
        window.saveUser = saveUser;
        window.deleteUser = deleteUser;
        <?php endif; ?>
        
        // Ensure closeModal is always accessible (even outside PHP conditional)
        if (typeof window.closeModal === 'undefined') {
            window.closeModal = function() {
                const modal = document.getElementById('userModal');
                if (modal) {
                    modal.classList.remove('show');
                    document.body.style.overflow = '';
                }
            };
        }
    </script>
</body>
</html>

