<?php
/**
 * Admin Approvals Page
 * Manage pending admin account approvals
 */

session_start();

// Check if user is logged in as admin
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../login.php');
    exit();
}

$pageTitle = 'Admin Approvals - Emergency Communication System';
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
        .approvals-container {
            background: var(--card-bg-1);
            border-radius: 12px;
            border: 1px solid var(--border-color-1);
            padding: 2rem;
            margin-bottom: 2rem;
        }
        
        .approvals-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            flex-wrap: wrap;
            gap: 1rem;
        }
        
        .approvals-stats {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }
        
        .stat-card {
            background: var(--bg-color-1);
            border: 1px solid var(--border-color-1);
            border-radius: 8px;
            padding: 1rem 1.5rem;
            min-width: 150px;
        }
        
        .stat-label {
            font-size: 0.875rem;
            color: var(--text-secondary-1);
            margin-bottom: 0.5rem;
        }
        
        .stat-value {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--text-color-1);
        }
        
        .stat-value.pending {
            color: #ffc107;
        }
        
        .stat-value.active {
            color: #28a745;
        }
        
        .pending-admins-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1.5rem;
        }
        
        .pending-admins-table th,
        .pending-admins-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid var(--border-color-1);
        }
        
        .pending-admins-table th {
            background: var(--bg-color-1);
            font-weight: 600;
            color: var(--text-color-1);
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .pending-admins-table td {
            color: var(--text-color-1);
        }
        
        .pending-admins-table tr:hover {
            background: var(--bg-color-1);
        }
        
        .admin-info {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }
        
        .admin-name {
            font-weight: 600;
            color: var(--text-color-1);
        }
        
        .admin-email {
            font-size: 0.875rem;
            color: var(--text-secondary-1);
        }
        
        .admin-date {
            font-size: 0.875rem;
            color: var(--text-secondary-1);
        }
        
        .action-buttons {
            display: flex;
            gap: 0.5rem;
        }
        
        .btn-approve,
        .btn-reject {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 6px;
            font-size: 0.875rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .btn-approve {
            background: #28a745;
            color: white;
        }
        
        .btn-approve:hover {
            background: #218838;
            transform: translateY(-1px);
        }
        
        .btn-reject {
            background: #dc3545;
            color: white;
        }
        
        .btn-reject:hover {
            background: #c82333;
            transform: translateY(-1px);
        }
        
        .empty-state {
            text-align: center;
            padding: 3rem 2rem;
            color: var(--text-secondary-1);
        }
        
        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: var(--text-secondary-1);
            opacity: 0.5;
        }
        
        .empty-state h3 {
            margin-bottom: 0.5rem;
            color: var(--text-color-1);
        }
        
        .loading-spinner {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 0.8s linear infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        .refresh-btn {
            padding: 0.5rem 1rem;
            background: var(--primary-color-1);
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.875rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
        }
        
        .refresh-btn:hover {
            background: #4ca8a6;
        }
        
        @media (max-width: 768px) {
            .pending-admins-table {
                display: block;
                overflow-x: auto;
            }
            
            .action-buttons {
                flex-direction: column;
            }
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
                                <span>Dashboard</span>
                            </a>
                        </li>
                        <li class="breadcrumb-item active" aria-current="page">
                            <span>Admin Approvals</span>
                        </li>
                    </ol>
                </nav>
                <h1>Admin Approvals <span class="help-tooltip">
                    <i class="fas fa-question-circle"></i>
                    <span class="tooltip-text">Review and approve or reject pending admin account requests. New admin accounts require approval before they can log in.</span>
                </span></h1>
                <p>Manage pending admin account approvals. Approve or reject new admin account requests.</p>
            </div>
            
            <div class="sub-container">
                <div class="page-content">
                    <div class="approvals-container">
                        <div class="approvals-header">
                            <div class="approvals-stats" id="approvalsStats">
                                <div class="stat-card">
                                    <div class="stat-label">Pending Approvals</div>
                                    <div class="stat-value pending" id="pendingCount">-</div>
                                </div>
                                <div class="stat-card">
                                    <div class="stat-label">Active Admins</div>
                                    <div class="stat-value active" id="activeCount">-</div>
                                </div>
                            </div>
                            <button class="refresh-btn" id="refreshBtn" onclick="loadPendingAdmins()">
                                <i class="fas fa-sync-alt"></i>
                                Refresh
                            </button>
                        </div>
                        
                        <div id="pendingAdminsContainer">
                            <div class="empty-state">
                                <i class="fas fa-spinner fa-spin"></i>
                                <h3>Loading...</h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // Load pending admins on page load
        document.addEventListener('DOMContentLoaded', function() {
            loadPendingAdmins();
            loadStats();
        });

        // Load pending admin approvals
        async function loadPendingAdmins() {
            const container = document.getElementById('pendingAdminsContainer');
            const refreshBtn = document.getElementById('refreshBtn');
            
            // Show loading state
            container.innerHTML = `
                <div class="empty-state">
                    <i class="fas fa-spinner fa-spin"></i>
                    <h3>Loading...</h3>
                </div>
            `;
            
            refreshBtn.disabled = true;
            refreshBtn.innerHTML = '<div class="loading-spinner"></div> Refreshing...';
            
            try {
                const response = await fetch('../api/admin-approvals.php?action=list');
                const data = await response.json();
                
                if (data.success) {
                    displayPendingAdmins(data.data);
                    loadStats(); // Refresh stats
                } else {
                    container.innerHTML = `
                        <div class="empty-state">
                            <i class="fas fa-exclamation-triangle"></i>
                            <h3>Error</h3>
                            <p>${data.message || 'Failed to load pending approvals.'}</p>
                        </div>
                    `;
                }
            } catch (error) {
                console.error('Error loading pending admins:', error);
                container.innerHTML = `
                    <div class="empty-state">
                        <i class="fas fa-exclamation-triangle"></i>
                        <h3>Error</h3>
                        <p>Failed to load pending approvals. Please try again.</p>
                    </div>
                `;
            } finally {
                refreshBtn.disabled = false;
                refreshBtn.innerHTML = '<i class="fas fa-sync-alt"></i> Refresh';
            }
        }

        // Display pending admins in table
        function displayPendingAdmins(admins) {
            const container = document.getElementById('pendingAdminsContainer');
            
            if (!admins || admins.length === 0) {
                container.innerHTML = `
                    <div class="empty-state">
                        <i class="fas fa-check-circle"></i>
                        <h3>No Pending Approvals</h3>
                        <p>All admin accounts have been processed. There are no pending approvals at this time.</p>
                    </div>
                `;
                return;
            }
            
            let tableHTML = `
                <table class="pending-admins-table">
                    <thead>
                        <tr>
                            <th>Admin Name</th>
                            <th>Email</th>
                            <th>Requested</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
            `;
            
            admins.forEach(admin => {
                const date = new Date(admin.created_at);
                const formattedDate = date.toLocaleDateString() + ' ' + date.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
                
                tableHTML += `
                    <tr data-user-id="${admin.id}">
                        <td>
                            <div class="admin-info">
                                <span class="admin-name">${escapeHtml(admin.name)}</span>
                            </div>
                        </td>
                        <td>
                            <div class="admin-info">
                                <span class="admin-email">${escapeHtml(admin.email)}</span>
                            </div>
                        </td>
                        <td>
                            <div class="admin-date">${formattedDate}</div>
                        </td>
                        <td>
                            <div class="action-buttons">
                                <button class="btn-approve" onclick="approveAdmin(${admin.id}, '${escapeHtml(admin.name)}', '${escapeHtml(admin.email)}')">
                                    <i class="fas fa-check"></i>
                                    Approve
                                </button>
                                <button class="btn-reject" onclick="rejectAdmin(${admin.id}, '${escapeHtml(admin.name)}', '${escapeHtml(admin.email)}')">
                                    <i class="fas fa-times"></i>
                                    Reject
                                </button>
                            </div>
                        </td>
                    </tr>
                `;
            });
            
            tableHTML += `
                    </tbody>
                </table>
            `;
            
            container.innerHTML = tableHTML;
        }

        // Approve admin account
        async function approveAdmin(userId, name, email) {
            const result = await Swal.fire({
                title: 'Approve Admin Account?',
                html: `Are you sure you want to approve <strong>${escapeHtml(name)}</strong> (${escapeHtml(email)})?<br><br>They will be able to log in immediately after approval.`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, Approve',
                cancelButtonText: 'Cancel'
            });
            
            if (!result.isConfirmed) return;
            
            try {
                const response = await fetch('../api/admin-approvals.php?action=approve', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        user_id: userId,
                        action: 'approve'
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Approved!',
                        text: data.message,
                        timer: 2000,
                        showConfirmButton: false
                    });
                    
                    // Remove the row from table
                    const row = document.querySelector(`tr[data-user-id="${userId}"]`);
                    if (row) {
                        row.style.opacity = '0.5';
                        setTimeout(() => {
                            loadPendingAdmins();
                        }, 500);
                    } else {
                        loadPendingAdmins();
                    }
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: data.message || 'Failed to approve admin account.',
                        confirmButtonColor: '#dc3545'
                    });
                }
            } catch (error) {
                console.error('Error approving admin:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'A connection error occurred. Please try again.',
                    confirmButtonColor: '#dc3545'
                });
            }
        }

        // Reject admin account
        async function rejectAdmin(userId, name, email) {
            const result = await Swal.fire({
                title: 'Reject Admin Account?',
                html: `Are you sure you want to reject <strong>${escapeHtml(name)}</strong> (${escapeHtml(email)})?<br><br>This action cannot be undone. They will not be able to log in.`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, Reject',
                cancelButtonText: 'Cancel',
                input: 'text',
                inputPlaceholder: 'Reason (optional)',
                inputValidator: (value) => {
                    // Optional field, no validation needed
                    return null;
                }
            });
            
            if (!result.isConfirmed) return;
            
            try {
                const response = await fetch('../api/admin-approvals.php?action=approve', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        user_id: userId,
                        action: 'reject'
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Rejected!',
                        text: data.message,
                        timer: 2000,
                        showConfirmButton: false
                    });
                    
                    // Remove the row from table
                    const row = document.querySelector(`tr[data-user-id="${userId}"]`);
                    if (row) {
                        row.style.opacity = '0.5';
                        setTimeout(() => {
                            loadPendingAdmins();
                        }, 500);
                    } else {
                        loadPendingAdmins();
                    }
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: data.message || 'Failed to reject admin account.',
                        confirmButtonColor: '#dc3545'
                    });
                }
            } catch (error) {
                console.error('Error rejecting admin:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'A connection error occurred. Please try again.',
                    confirmButtonColor: '#dc3545'
                });
            }
        }

        // Load statistics
        async function loadStats() {
            try {
                const response = await fetch('../api/admin-approvals.php?action=stats');
                const data = await response.json();
                
                if (data.success) {
                    document.getElementById('pendingCount').textContent = data.stats.pending;
                    document.getElementById('activeCount').textContent = data.stats.active;
                }
            } catch (error) {
                console.error('Error loading stats:', error);
            }
        }

        // Escape HTML to prevent XSS
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    </script>
</body>
</html>


