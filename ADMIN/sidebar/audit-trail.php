<?php
/**
 * Log and Audit Trail for Sent Notifications Page
 * Track and audit all sent notifications for accountability and compliance
 */

// Start session and check authentication
session_start();

// Check if user is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../login.php');
    exit();
}

$pageTitle = 'Log and Audit Trail';
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
        /* Enhanced Audit Trail Styles */
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

        .module-card-content {
            padding: 1.5rem;
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

        .filter-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.25rem;
            align-items: flex-end;
        }

        .form-group {
            margin-bottom: 0;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--text-color-1);
            font-size: 0.9rem;
        }

        .form-group input, .form-group select {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid var(--border-color-1);
            border-radius: 8px;
            font-size: 0.95rem;
            background: var(--bg-color-1);
            color: var(--text-color-1);
            transition: border-color var(--transition-speed) ease;
        }

        .form-group input:focus, .form-group select:focus {
            outline: none;
            border-color: var(--primary-color-1);
            box-shadow: 0 0 0 3px rgba(76, 138, 137, 0.1);
        }

        .filter-actions {
            display: flex;
            gap: 0.75rem;
        }

        /* Modal Styles */
        .modal {
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(4px);
            z-index: 2000;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }

        .modal-content {
            background: var(--card-bg-1);
            border-radius: 12px;
            width: 100%;
            max-width: 700px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            border: 1px solid var(--border-color-1);
        }

        .modal-header {
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid var(--border-color-1);
            background: var(--bg-color-1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-body {
            padding: 1.5rem;
        }

        .details-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
        }

        .detail-item {
            display: flex;
            flex-direction: column;
            gap: 0.35rem;
        }

        .detail-label {
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            color: var(--text-secondary-1);
            letter-spacing: 0.5px;
        }

        .detail-value {
            font-size: 0.95rem;
            color: var(--text-color-1);
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
        
        .badge.success { background: rgba(46, 204, 113, 0.15); color: #2ecc71; }
        .badge.failed { background: rgba(231, 76, 60, 0.15); color: #e74c3c; }
        .badge.pending { background: rgba(243, 156, 18, 0.15); color: #f39c12; }

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

        @media (max-width: 768px) {
            .stat-grid, .filter-grid, .details-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- Include Sidebar Component -->
    <?php include 'includes/sidebar.php'; ?>

    <!-- Include Admin Header Component -->
    <?php include 'includes/admin-header.php'; ?>
    
    <!-- ===================================
       MAIN CONTENT - Log and Audit Trail
       =================================== -->
    <div class="main-content">
        <div class="main-container">
            <div class="title">
                <nav class="breadcrumb" aria-label="Breadcrumb">
                    <ol class="breadcrumb-list">
                        <li class="breadcrumb-item">
                            <a href="dashboard.php" class="breadcrumb-link">Dashboard</a>
                        </li>
                        <li class="breadcrumb-item active" aria-current="page">Log and Audit Trail</li>
                    </ol>
                </nav>
                <h1><i class="fas fa-history" style="color: var(--primary-color-1); margin-right: 0.5rem;"></i> Log and Audit Trail</h1>
                <p>Comprehensive logging and audit trail system to track all sent notifications for accountability, compliance, and system monitoring.</p>
            </div>
            
            <div class="sub-container">
                <div class="page-content">
                    <!-- Audit Statistics -->
                    <div class="module-card">
                        <div class="module-card-header">
                            <h2><i class="fas fa-chart-line"></i> Audit Statistics</h2>
                        </div>
                        <div class="stat-grid">
                            <div class="stat-card">
                                <div class="stat-value" id="totalNotifications" style="color: #4c8a89;">0</div>
                                <div class="stat-label">Total Notifications</div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-value" id="successfulNotifications" style="color: #2ecc71;">0</div>
                                <div class="stat-label">Successful</div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-value" id="failedNotifications" style="color: #e74c3c;">0</div>
                                <div class="stat-label">Failed</div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-value" id="todayNotifications" style="color: #3498db;">0</div>
                                <div class="stat-label">Sent Today</div>
                            </div>
                        </div>
                    </div>

                    <!-- Filters -->
                    <div class="module-card">
                        <div class="module-card-header">
                            <h2><i class="fas fa-filter"></i> Filters</h2>
                        </div>
                        <div class="module-card-content">
                            <form id="filterForm" class="filter-grid">
                                <div class="form-group">
                                    <label for="filterDateFrom">Date From</label>
                                    <input type="date" id="filterDateFrom" name="date_from">
                                </div>
                                <div class="form-group">
                                    <label for="filterDateTo">Date To</label>
                                    <input type="date" id="filterDateTo" name="date_to">
                                </div>
                                <div class="form-group">
                                    <label for="filterChannel">Channel</label>
                                    <select id="filterChannel" name="channel">
                                        <option value="">All Channels</option>
                                        <option value="sms">SMS</option>
                                        <option value="email">Email</option>
                                        <option value="pa">PA System</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="filterStatus">Status</label>
                                    <select id="filterStatus" name="status">
                                        <option value="">All Status</option>
                                        <option value="success">Success</option>
                                        <option value="failed">Failed</option>
                                        <option value="pending">Pending</option>
                                    </select>
                                </div>
                                <div class="form-group filter-actions">
                                    <button type="button" class="btn btn-primary" onclick="applyFilters()" style="flex: 2;">
                                        <i class="fas fa-search"></i> Apply
                                    </button>
                                    <button type="button" class="btn btn-secondary" onclick="resetFilters()" style="flex: 1;">
                                        <i class="fas fa-redo"></i> Reset
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Audit Trail Table -->
                    <div class="module-card">
                        <div class="module-card-header">
                            <h2><i class="fas fa-list"></i> Audit Trail</h2>
                            <button class="btn btn-sm btn-primary" onclick="exportAuditTrail()">
                                <i class="fas fa-download"></i> Export
                            </button>
                        </div>
                        <div class="module-card-content table-responsive">
                            <table class="data-table" id="auditTrailTable">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Timestamp</th>
                                        <th>Channel</th>
                                        <th>Recipient</th>
                                        <th>Message</th>
                                        <th>Status</th>
                                        <th>Sent By</th>
                                        <th>IP Address</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Data will be loaded via API -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- View Details Modal -->
    <div id="detailsModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Notification Details</h2>
                <button class="modal-close" onclick="closeDetailsModal()">&times;</button>
            </div>
            <div class="modal-body" id="detailsContent">
                <!-- Details will be loaded here -->
            </div>
        </div>
    </div>

    <script>
        function loadAuditTrail() {
            const filters = getFilters();
            const queryParams = new URLSearchParams(filters).toString();
            
            fetch(`../api/audit-trail.php?action=list&${queryParams}`)
                .then(response => response.json())
                .then(data => {
                    const tbody = document.querySelector('#auditTrailTable tbody');
                    tbody.innerHTML = '';
                    
                    if (data.success && data.logs) {
                        data.logs.forEach(log => {
                            const row = document.createElement('tr');
                            row.innerHTML = `
                                <td>${log.id}</td>
                                <td><small>${log.timestamp}</small></td>
                                <td><span class="badge" style="background: rgba(76, 138, 137, 0.1); color: var(--primary-color-1); font-weight: 700;">${log.channel.toUpperCase()}</span></td>
                                <td>${log.recipient}</td>
                                <td><div style="max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">${log.message}</div></td>
                                <td><span class="badge ${log.status}">${log.status}</span></td>
                                <td>${log.sent_by || 'System'}</td>
                                <td><small>${log.ip_address || 'N/A'}</small></td>
                                <td>
                                    <button class="btn btn-sm btn-primary" onclick="viewDetails(${log.id})">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </td>
                            `;
                            tbody.appendChild(row);
                        });
                    }
                });
        }

        function loadStatistics() {
            fetch('../api/audit-trail.php?action=statistics')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('totalNotifications').textContent = data.total || 0;
                        document.getElementById('successfulNotifications').textContent = data.successful || 0;
                        document.getElementById('failedNotifications').textContent = data.failed || 0;
                        document.getElementById('todayNotifications').textContent = data.today || 0;
                    }
                });
        }

        function getFilters() {
            return {
                date_from: document.getElementById('filterDateFrom').value,
                date_to: document.getElementById('filterDateTo').value,
                channel: document.getElementById('filterChannel').value,
                status: document.getElementById('filterStatus').value
            };
        }

        function applyFilters() {
            loadAuditTrail();
        }

        function resetFilters() {
            document.getElementById('filterForm').reset();
            loadAuditTrail();
        }

        function viewDetails(id) {
            fetch(`../api/audit-trail.php?action=get&id=${id}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.log) {
                        const log = data.log;
                        const content = document.getElementById('detailsContent');
                        content.innerHTML = `
                            <div class="details-grid">
                                <div class="detail-item">
                                    <span class="detail-label">Log ID</span>
                                    <span class="detail-value">#${log.id}</span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Timestamp</span>
                                    <span class="detail-value">${log.timestamp}</span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Channel</span>
                                    <span class="detail-value"><span class="badge" style="background: rgba(76, 138, 137, 0.1); color: var(--primary-color-1); font-weight: 700;">${log.channel.toUpperCase()}</span></span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Status</span>
                                    <span class="detail-value"><span class="badge ${log.status}">${log.status}</span></span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Recipient</span>
                                    <span class="detail-value">${log.recipient}</span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Sent By</span>
                                    <span class="detail-value">${log.sent_by || 'System'}</span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">IP Address</span>
                                    <span class="detail-value">${log.ip_address || 'N/A'}</span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Response Code</span>
                                    <span class="detail-value">${log.response || 'N/A'}</span>
                                </div>
                                <div class="detail-item" style="grid-column: span 2; margin-top: 1rem;">
                                    <span class="detail-label">Message Content</span>
                                    <div style="background: var(--bg-color-1); padding: 1rem; border-radius: 8px; border: 1px solid var(--border-color-1); margin-top: 0.5rem; line-height: 1.5;">${log.message}</div>
                                </div>
                                ${log.error_message ? `
                                <div class="detail-item" style="grid-column: span 2; margin-top: 1rem;">
                                    <span class="detail-label" style="color: #e74c3c;">Error Message</span>
                                    <div style="color: #e74c3c; font-weight: 500;">${log.error_message}</div>
                                </div>
                                ` : ''}
                            </div>
                        `;
                        document.getElementById('detailsModal').style.display = 'flex';
                        document.body.style.overflow = 'hidden';
                    }
                });
        }

        function closeDetailsModal() {
            document.getElementById('detailsModal').style.display = 'none';
            document.body.style.overflow = '';
        }

        function exportAuditTrail() {
            const filters = getFilters();
            const queryParams = new URLSearchParams({...filters, action: 'export'}).toString();
            window.location.href = `../api/audit-trail.php?${queryParams}`;
        }

        // Load data on page load
        document.addEventListener('DOMContentLoaded', function() {
            loadAuditTrail();
            loadStatistics();
        });
    </script>
</body>
</html>
