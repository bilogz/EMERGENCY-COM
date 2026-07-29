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
    <link rel="stylesheet" href="css/global.css?v=<?php echo filemtime(__DIR__ . '/css/global.css'); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/sidebar.css?v=<?php echo filemtime(__DIR__ . '/css/sidebar.css'); ?>">
    <link rel="stylesheet" href="css/admin-header.css">
    <link rel="stylesheet" href="css/buttons.css">
    <link rel="stylesheet" href="css/forms.css">
    <link rel="stylesheet" href="css/datatables.css">
    <link rel="stylesheet" href="css/hero.css">
    <link rel="stylesheet" href="css/sidebar-footer.css">
    <link rel="stylesheet" href="css/modules.css">
        <link rel="stylesheet" href="css/module-audit-trail.css?v=<?php echo filemtime(__DIR__ . '/css/module-audit-trail.css'); ?>">
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
                                <div class="stat-value" id="totalNotifications" style="color: #3a7675;">0</div>
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

                    <!-- Two-Way Communication Audit Panels -->
                    <div class="module-card">
                        <div class="module-card-header">
                            <h2><i class="fas fa-comments"></i> Messages and Calls Audit</h2>
                        </div>
                        <div class="module-card-content">
                            <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(420px, 1fr)); gap:1rem;">
                                <div class="table-responsive">
                                    <h3 style="margin:0 0 .75rem; font-size:1rem;"><i class="fas fa-share-from-square"></i> Response Transfers</h3>
                                    <table class="data-table" id="transferAuditTable">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Time</th>
                                                <th>Caller</th>
                                                <th>Status</th>
                                                <th>Response</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody><tr><td colspan="6">Loading...</td></tr></tbody>
                                    </table>
                                </div>
                                <div class="table-responsive">
                                    <h3 style="margin:0 0 .75rem; font-size:1rem;"><i class="fas fa-user-clock"></i> Admin Handovers</h3>
                                    <table class="data-table" id="assignmentAuditTable">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Time</th>
                                                <th>Conversation</th>
                                                <th>Action</th>
                                                <th>Admin</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody><tr><td colspan="6">Loading...</td></tr></tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Audit Trail Table -->
                    <div class="module-card">
                        <div class="module-card-header">
                            <h2><i class="fas fa-list"></i> Audit Trail</h2>
                            <button class="btn btn-sm btn-primary" id="exportAuditTrailPdfBtn" onclick="exportAuditTrail()">
                                <i class="fas fa-file-pdf"></i> Export PDF
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
                            <div id="auditLazyLoadSentinel" style="text-align:center; padding:0.85rem; color:var(--text-secondary-1); font-weight:700;">
                                Loading audit records...
                            </div>
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

    <div id="twcAuditDetailsModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="twcAuditDetailsTitle">Audit Details</h2>
                <button class="modal-close" onclick="closeTwcAuditDetailsModal()">&times;</button>
            </div>
            <div class="modal-body" id="twcAuditDetailsContent"></div>
        </div>
    </div>

    <script>
        let twcTransferAuditRows = [];
        let twcAssignmentAuditRows = [];
        let auditPage = 1;
        let auditTotalPages = 1;
        let auditTotalRows = 0;
        let auditPageSize = 25;
        let auditLoading = false;
        let auditHasMore = true;
        let auditLazyObserver = null;

        function escapeAuditHtml(value) {
            const div = document.createElement('div');
            div.textContent = value == null ? '' : String(value);
            return div.innerHTML;
        }

        function loadTwcAuditSummary() {
            fetch('../api/twc-audit-summary.php')
                .then(response => response.json())
                .then(data => {
                    if (!data.success) return;
                    twcTransferAuditRows = data.transfers || [];
                    twcAssignmentAuditRows = data.assignments || [];
                    renderTransferAuditRows();
                    renderAssignmentAuditRows();
                });
        }

        function renderTransferAuditRows() {
            const tbody = document.querySelector('#transferAuditTable tbody');
            if (!tbody) return;
            if (!twcTransferAuditRows.length) {
                tbody.innerHTML = '<tr><td colspan="6">No transfer audit records.</td></tr>';
                return;
            }
            tbody.innerHTML = twcTransferAuditRows.map(row => `
                <tr>
                    <td>${escapeAuditHtml(row.id)}</td>
                    <td><small>${escapeAuditHtml(row.created_at)}</small></td>
                    <td>${escapeAuditHtml(row.caller_name || 'Unknown')}</td>
                    <td><span class="badge ${escapeAuditHtml(row.status || '')}">${escapeAuditHtml(row.status || 'prepared')}</span></td>
                    <td>${escapeAuditHtml((row.response_status || 'pending').replace(/_/g, ' '))}</td>
                    <td><button class="btn btn-sm btn-primary" onclick="viewTwcTransferAudit(${Number(row.id)})"><i class="fas fa-eye"></i></button></td>
                </tr>
            `).join('');
        }

        function renderAssignmentAuditRows() {
            const tbody = document.querySelector('#assignmentAuditTable tbody');
            if (!tbody) return;
            if (!twcAssignmentAuditRows.length) {
                tbody.innerHTML = '<tr><td colspan="6">No handover audit records.</td></tr>';
                return;
            }
            tbody.innerHTML = twcAssignmentAuditRows.map(row => `
                <tr>
                    <td>${escapeAuditHtml(row.id)}</td>
                    <td><small>${escapeAuditHtml(row.created_at)}</small></td>
                    <td>#${escapeAuditHtml(row.conversation_id)}</td>
                    <td>${escapeAuditHtml(row.action)}</td>
                    <td>${escapeAuditHtml(row.admin_name || 'Admin')}</td>
                    <td><button class="btn btn-sm btn-primary" onclick="viewTwcAssignmentAudit(${Number(row.id)})"><i class="fas fa-eye"></i></button></td>
                </tr>
            `).join('');
        }

        function openTwcAuditDetailsModal(title, row) {
            document.getElementById('twcAuditDetailsTitle').textContent = title;
            document.getElementById('twcAuditDetailsContent').innerHTML = `
                <pre style="white-space:pre-wrap; background:var(--bg-color-1); border:1px solid var(--border-color-1); border-radius:8px; padding:1rem;">${escapeAuditHtml(JSON.stringify(row, null, 2))}</pre>
            `;
            document.getElementById('twcAuditDetailsModal').style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }

        function closeTwcAuditDetailsModal() {
            document.getElementById('twcAuditDetailsModal').style.display = 'none';
            document.body.style.overflow = '';
        }

        function viewTwcTransferAudit(id) {
            const row = twcTransferAuditRows.find(item => Number(item.id) === Number(id));
            if (row) openTwcAuditDetailsModal('Response Transfer Audit', row);
        }

        function viewTwcAssignmentAudit(id) {
            const row = twcAssignmentAuditRows.find(item => Number(item.id) === Number(id));
            if (row) openTwcAuditDetailsModal('Admin Handover Audit', row);
        }

        function loadAuditTrail(reset = true) {
            if (auditLoading) return Promise.resolve();
            auditLoading = true;
            const tbody = document.querySelector('#auditTrailTable tbody');
            if (reset) {
                auditPage = 1;
                auditHasMore = true;
                if (tbody) {
                    tbody.innerHTML = '<tr><td colspan="9" style="text-align:center; padding:1rem;">Loading audit records...</td></tr>';
                }
            } else if (!auditHasMore) {
                auditLoading = false;
                return Promise.resolve();
            } else {
                auditPage += 1;
            }

            updateAuditLazyLoadStatus('Loading audit records...');
            const params = new URLSearchParams(getFilters());
            params.set('page', String(auditPage));
            params.set('limit', String(auditPageSize));
            
            return fetch(`../api/audit-trail.php?action=list&${params.toString()}`)
                .then(response => response.json())
                .then(data => {
                    const targetBody = document.querySelector('#auditTrailTable tbody');
                    if (!targetBody) return;
                    if (reset) targetBody.innerHTML = '';
                    if (!data.success) throw new Error(data.message || 'Failed to load audit trail');

                    const logs = Array.isArray(data.logs) ? data.logs : [];
                    const pagination = data.pagination || {};
                    auditPage = Number(pagination.page || auditPage || 1);
                    auditTotalPages = Number(pagination.total_pages || 1);
                    auditTotalRows = Number(pagination.total || logs.length || 0);
                    auditHasMore = auditPage < auditTotalPages;

                    if (!logs.length && reset) {
                        targetBody.innerHTML = '<tr><td colspan="9" style="text-align:center; padding:1rem;">No audit records found.</td></tr>';
                        return;
                    }

                    logs.forEach(log => {
                        const row = document.createElement('tr');
                        row.innerHTML = `
                            <td>${escapeAuditHtml(log.id)}</td>
                            <td><small>${escapeAuditHtml(log.timestamp)}</small></td>
                            <td><span class="badge" style="background: rgba(58, 118, 117, 0.1); color: var(--primary-color-1); font-weight: 700;">${escapeAuditHtml(String(log.channel || '').toUpperCase())}</span></td>
                            <td>${escapeAuditHtml(log.recipient)}</td>
                            <td><div style="max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">${escapeAuditHtml(log.message)}</div></td>
                            <td><span class="badge ${escapeAuditHtml(log.status)}">${escapeAuditHtml(log.status)}</span></td>
                            <td>${escapeAuditHtml(log.sent_by || 'System')}</td>
                            <td><small>${escapeAuditHtml(log.ip_address || 'N/A')}</small></td>
                            <td>
                                <button class="btn btn-sm btn-primary" onclick="viewDetails(${Number(log.id)})">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </td>
                        `;
                        targetBody.appendChild(row);
                    });
                })
                .catch(error => {
                    console.error('Audit trail load error:', error);
                    if (tbody && reset) {
                        tbody.innerHTML = '<tr><td colspan="9" style="text-align:center; padding:1rem; color:#e74c3c;">Failed to load audit records.</td></tr>';
                    }
                })
                .finally(() => {
                    auditLoading = false;
                    updateAuditLazyLoadStatus();
                });
        }

        function loadMoreAuditTrail() {
            if (auditLoading || !auditHasMore) return;
            loadAuditTrail(false);
        }

        function setupAuditLazyLoader() {
            const sentinel = document.getElementById('auditLazyLoadSentinel');
            if (!sentinel || !('IntersectionObserver' in window)) return;
            if (auditLazyObserver) auditLazyObserver.disconnect();
            auditLazyObserver = new IntersectionObserver(entries => {
                if (entries.some(entry => entry.isIntersecting)) {
                    loadMoreAuditTrail();
                }
            }, { rootMargin: '260px 0px' });
            auditLazyObserver.observe(sentinel);
        }

        function updateAuditLazyLoadStatus(message = '') {
            const sentinel = document.getElementById('auditLazyLoadSentinel');
            if (!sentinel) return;
            if (auditLoading) {
                sentinel.style.display = 'block';
                sentinel.textContent = message || 'Loading audit records...';
                return;
            }
            if (auditHasMore) {
                sentinel.style.display = 'block';
                sentinel.textContent = `Showing up to ${Math.min(auditPage * auditPageSize, auditTotalRows || auditPage * auditPageSize)} of ${auditTotalRows || 'more'} records. Scroll to load more.`;
                return;
            }
            sentinel.style.display = auditTotalRows ? 'block' : 'none';
            sentinel.textContent = auditTotalRows ? `All ${auditTotalRows} audit records loaded.` : '';
        }

        window.loadMoreAuditTrail = loadMoreAuditTrail;

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
            loadAuditTrail(true);
        }

        function resetFilters() {
            document.getElementById('filterForm').reset();
            loadAuditTrail(true);
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
                                    <span class="detail-value"><span class="badge" style="background: rgba(58, 118, 117, 0.1); color: var(--primary-color-1); font-weight: 700;">${log.channel.toUpperCase()}</span></span>
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
            const exportButton = document.getElementById('exportAuditTrailPdfBtn');
            if (window.AdminReportPdfExporter && typeof window.AdminReportPdfExporter.exportCurrentPage === 'function') {
                window.AdminReportPdfExporter.exportCurrentPage({
                    filenamePrefix: 'audit-trail-report',
                    targetSelector: '.main-content .main-container',
                    triggerButton: exportButton
                });
                return;
            }

            alert('PDF export is currently unavailable.');
        }

        // Load data on page load
        document.addEventListener('DOMContentLoaded', function() {
            setupAuditLazyLoader();
            loadAuditTrail(true);
            loadStatistics();
            loadTwcAuditSummary();
        });
    </script>
</body>
</html>
