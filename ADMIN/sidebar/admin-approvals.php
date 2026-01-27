<?php
/**
 * Admin Approvals Management Page
 * Review and approve/reject pending admin accounts
 */

// Start session and check authentication
session_start();

// Check if user is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../login.php');
    exit();
}

$pageTitle = 'Admin Approvals';
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
        /* Enhanced Admin Approvals Styles */
        :root {
            --card-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --card-shadow-hover: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --transition-speed: 0.2s;
        }

        .module-card {
            background: var(--card-bg-1);
            border-radius: 10px;
            box-shadow: var(--card-shadow);
            border: 1px solid var(--border-color-1);
            overflow: hidden;
            margin-bottom: 1.5rem;
            transition: box-shadow var(--transition-speed) ease;
        }

        .module-card:hover {
            box-shadow: var(--card-shadow-hover);
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
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--text-color-1);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .stat-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            padding: 1.5rem;
        }

        .stat-card {
            background: var(--card-bg-1);
            padding: 1.5rem;
            border-radius: 10px;
            border: 1px solid var(--border-color-1);
            text-align: center;
            transition: transform var(--transition-speed) ease;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        .stat-card:hover {
            transform: translateY(-3px);
            border-color: var(--primary-color-1);
        }

        .stat-value {
            font-size: 2.25rem;
            font-weight: 700;
            margin: 0 0 0.5rem 0;
            line-height: 1.2;
        }

        .stat-label {
            font-size: 0.9rem;
            color: var(--text-secondary-1);
            font-weight: 500;
        }

        .badge {
            padding: 0.35rem 0.65rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            display: inline-block;
        }
        
        .badge.pending { background: rgba(243, 156, 18, 0.15); color: #f39c12; }
        .badge.active { background: rgba(46, 204, 113, 0.15); color: #2ecc71; }
        .badge.inactive { background: rgba(231, 76, 60, 0.15); color: #e74c3c; }

        .table-responsive {
            overflow-x: auto;
            width: 100%;
        }

        .data-table th {
            background: var(--bg-color-1);
            color: var(--text-secondary-1);
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .info-box {
            background-color: rgba(76, 138, 137, 0.1);
            border-left: 4px solid var(--primary-color-1);
            color: var(--text-color-1);
            padding: 1.25rem;
            border-radius: 8px;
            margin-top: 1.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .info-box i {
            color: var(--primary-color-1);
            font-size: 1.25rem;
        }

        .btn-group {
            display: flex;
            gap: 0.5rem;
        }

        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: var(--text-secondary-1);
        }

        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.2;
        }
    </style>
</head>
<body>
    <!-- Include Sidebar Component -->
    <?php include 'includes/sidebar.php'; ?>

    <!-- Include Admin Header Component -->
    <?php include 'includes/admin-header.php'; ?>
    
    <!-- ===================================
       MAIN CONTENT - Admin Approvals
       =================================== -->
    <div class="main-content">
        <div class="main-container">
            <div class="title">
                <nav class="breadcrumb" aria-label="Breadcrumb">
                    <ol class="breadcrumb-list">
                        <li class="breadcrumb-item">
                            <a href="dashboard.php" class="breadcrumb-link">Dashboard</a>
                        </li>
                        <li class="breadcrumb-item active" aria-current="page">Admin Approvals</li>
                    </ol>
                </nav>
                <h1><i class="fas fa-user-shield" style="color: var(--primary-color-1); margin-right: 0.5rem;"></i> Admin Approvals</h1>
                <p>Review and process pending administrator account requests.</p>
                <div class="info-box">
                    <i class="fas fa-info-circle"></i>
                    <div>
                        <strong>Pending Approvals:</strong> New administrator accounts require approval from an existing active admin before they can access the system.
                    </div>
                </div>
            </div>
            
            <div class="sub-container">
                <div class="page-content">
                    <!-- Approval Statistics -->
                    <div class="stat-grid">
                        <div class="stat-card">
                            <div class="stat-label">Pending Approval</div>
                            <div class="stat-value" id="pendingCount" style="color: #f39c12;">0</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-label">Active Admins</div>
                            <div class="stat-value" id="activeCount" style="color: #2ecc71;">0</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-label">Inactive/Rejected</div>
                            <div class="stat-value" id="inactiveCount" style="color: #e74c3c;">0</div>
                        </div>
                    </div>

                    <!-- Pending Approvals Table -->
                    <div class="module-card">
                        <div class="module-card-header">
                            <h2><i class="fas fa-clock"></i> Pending Requests</h2>
                            <button class="btn btn-sm btn-secondary" onclick="loadPendingAdmins()">
                                <i class="fas fa-sync"></i> Refresh
                            </button>
                        </div>
                        <div class="module-card-content table-responsive" style="padding: 0;">
                            <div id="loadingState" style="text-align: center; padding: 3rem; color: var(--text-secondary-1);">
                                <i class="fas fa-spinner fa-spin" style="font-size: 2rem; margin-bottom: 1rem;"></i>
                                <p>Loading pending requests...</p>
                            </div>
                            <table class="data-table" id="approvalsTable" style="display: none;">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Role</th>
                                        <th>Requested Date</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="approvalsTableBody">
                                    <!-- Data will be loaded via API -->
                                </tbody>
                            </table>
                            <div id="emptyState" class="empty-state" style="display: none;">
                                <i class="fas fa-user-check"></i>
                                <h3>No Pending Requests</h3>
                                <p>All administrator account requests have been processed.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        async function loadStats() {
            try {
                const response = await fetch('../api/admin-approvals.php?action=stats');
                const data = await response.json();
                if (data.success) {
                    document.getElementById('pendingCount').textContent = data.stats.pending;
                    document.getElementById('activeCount').textContent = data.stats.active;
                    document.getElementById('inactiveCount').textContent = data.stats.inactive;
                }
            } catch (error) {
                console.error('Error loading stats:', error);
            }
        }

        async function loadPendingAdmins() {
            const loadingState = document.getElementById('loadingState');
            const table = document.getElementById('approvalsTable');
            const emptyState = document.getElementById('emptyState');
            const tbody = document.getElementById('approvalsTableBody');

            loadingState.style.display = 'block';
            table.style.display = 'none';
            emptyState.style.display = 'none';

            try {
                const response = await fetch('../api/admin-approvals.php?action=list');
                const result = await response.json();
                
                loadingState.style.display = 'none';

                if (result.success && result.data.length > 0) {
                    tbody.innerHTML = '';
                    result.data.forEach(admin => {
                        const row = document.createElement('tr');
                        row.innerHTML = `
                            <td>#${admin.id}</td>
                            <td><strong>${admin.name}</strong></td>
                            <td>${admin.email}</td>
                            <td><span class="badge" style="background: rgba(76, 138, 137, 0.1); color: var(--primary-color-1);">${admin.role || 'Admin'}</span></td>
                            <td><small>${new Date(admin.created_at).toLocaleString()}</small></td>
                            <td><span class="badge pending">Pending</span></td>
                            <td>
                                <div class="btn-group">
                                    <button class="btn btn-sm btn-success" onclick="processApproval(${admin.id}, 'approve')" title="Approve">
                                        <i class="fas fa-check"></i> Approve
                                    </button>
                                    <button class="btn btn-sm btn-danger" onclick="processApproval(${admin.id}, 'reject')" title="Reject">
                                        <i class="fas fa-times"></i> Reject
                                    </button>
                                </div>
                            </td>
                        `;
                        tbody.appendChild(row);
                    });
                    table.style.display = 'table';
                } else {
                    emptyState.style.display = 'block';
                }
            } catch (error) {
                console.error('Error loading pending admins:', error);
                loadingState.innerHTML = '<span style="color: #e74c3c;"><i class="fas fa-exclamation-triangle"></i> Error loading data.</span>';
            }
        }

        async function processApproval(userId, action) {
            const confirmMsg = action === 'approve' ? 
                'Are you sure you want to approve this administrator account?' : 
                'Are you sure you want to reject this administrator account?';
            
            if (!confirm(confirmMsg)) return;

            try {
                const response = await fetch('../api/admin-approvals.php?action=approve', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ user_id: userId, action: action })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    alert(result.message);
                    loadPendingAdmins();
                    loadStats();
                } else {
                    alert('Error: ' + result.message);
                }
            } catch (error) {
                console.error('Error processing approval:', error);
                alert('An error occurred. Please try again.');
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            loadStats();
            loadPendingAdmins();
        });
    </script>
</body>
</html>
