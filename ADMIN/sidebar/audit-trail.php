<?php
/**
 * Unified admin audit trail.
 */

session_start();
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
    <?php include 'includes/sidebar.php'; ?>
    <?php include 'includes/admin-header.php'; ?>

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
                <h1><i class="fas fa-history audit-title-icon"></i> Log and Audit Trail</h1>
                <p>Review notification activity, admin handovers, deleted reports and enquiries, and transfers sent to the Emergency Response System.</p>
            </div>

            <div class="sub-container">
                <div class="page-content">
                    <section class="module-card" aria-labelledby="auditAnalyticsTitle">
                        <div class="module-card-header">
                            <h2 id="auditAnalyticsTitle"><i class="fas fa-chart-line"></i> Audit Analytics</h2>
                        </div>
                        <div class="stat-grid">
                            <div class="stat-card">
                                <div class="stat-value audit-stat-total" id="totalAuditRecords">0</div>
                                <div class="stat-label" id="totalAuditLabel">Total Notifications</div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-value audit-stat-success" id="successfulAuditRecords">0</div>
                                <div class="stat-label" id="successfulAuditLabel">Successful</div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-value audit-stat-failed" id="failedAuditRecords">0</div>
                                <div class="stat-label" id="failedAuditLabel">Failed</div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-value audit-stat-today" id="todayAuditRecords">0</div>
                                <div class="stat-label" id="todayAuditLabel">Sent Today</div>
                            </div>
                        </div>
                    </section>

                    <section class="module-card" aria-labelledby="auditFiltersTitle">
                        <div class="module-card-header">
                            <h2 id="auditFiltersTitle"><i class="fas fa-filter"></i> Filters</h2>
                        </div>
                        <div class="module-card-content">
                            <div id="filterForm" class="filter-grid audit-filter-grid">
                                <div class="form-group audit-type-filter">
                                    <label for="filterTrailType">Audit Trail</label>
                                    <select id="filterTrailType" name="trail_type">
                                        <option value="notifications">Notification Trails</option>
                                        <option value="handovers">Admin Handover Trails</option>
                                        <option value="deletions">Deleted Reports and Enquiries</option>
                                        <option value="ers_transfers">Reports Transferred to ERS</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="filterDateFrom">Date From</label>
                                    <input type="date" id="filterDateFrom" name="date_from">
                                </div>
                                <div class="form-group">
                                    <label for="filterDateTo">Date To</label>
                                    <input type="date" id="filterDateTo" name="date_to">
                                </div>
                                <div class="form-group" id="filterChannelGroup">
                                    <label for="filterChannel">Channel</label>
                                    <select id="filterChannel" name="channel">
                                        <option value="">All Channels</option>
                                        <option value="sms">SMS</option>
                                        <option value="email">Email</option>
                                        <option value="pa">PA System</option>
                                        <option value="push">Push</option>
                                        <option value="chat_risk">Chat Risk</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="filterStatus" id="filterStatusLabel">Status</label>
                                    <select id="filterStatus" name="status"></select>
                                </div>

                            </div>
                        </div>
                    </section>

                    <section class="module-card" aria-labelledby="auditTableTitle">
                        <div class="module-card-header">
                            <div>
                                <h2 id="auditTableTitle"><i class="fas fa-bell"></i> Notification Trails</h2>
                                <p class="audit-table-description" id="auditTableDescription">System notification delivery and warning activity.</p>
                            </div>
                            <button class="btn btn-sm btn-primary" id="exportAuditTrailPdfBtn" type="button">
                                <i class="fas fa-file-pdf"></i> Export PDF
                            </button>
                        </div>
                        <div class="module-card-content table-responsive audit-table-wrapper" id="auditTableScroll">
                            <table class="data-table audit-unified-table" id="auditTrailTable">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Timestamp</th>
                                        <th>Trail</th>
                                        <th>Subject</th>
                                        <th>Activity</th>
                                        <th>Status / Action</th>
                                        <th>Admin / Source</th>
                                        <th>IP Address</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td colspan="9" class="audit-table-message">Loading audit records...</td>
                                    </tr>
                                </tbody>
                            </table>
                            <nav id="auditPagination" class="audit-pagination" aria-label="Audit trail pages"></nav>
                        </div>
                    </section>
                </div>
            </div>
        </div>
    </div>

    <div id="detailsModal" class="modal" style="display: none;" role="dialog" aria-modal="true" aria-labelledby="auditDetailsTitle">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="auditDetailsTitle">Audit Details</h2>
                <button class="modal-close" id="closeAuditDetails" type="button" aria-label="Close audit details">&times;</button>
            </div>
            <div class="modal-body" id="detailsContent"></div>
        </div>
    </div>

    <script>
        const auditTrailConfigs = {
            notifications: {
                title: 'Notification Trails',
                icon: 'fa-bell',
                description: 'System notification delivery and warning activity.',
                totalLabel: 'Total Notifications',
                successfulLabel: 'Successful',
                failedLabel: 'Failed',
                todayLabel: 'Sent Today',
                statusLabel: 'Status',
                statuses: [
                    ['', 'All Statuses'],
                    ['success', 'Success'],
                    ['sent', 'Sent'],
                    ['completed', 'Completed'],
                    ['failed', 'Failed'],
                    ['pending', 'Pending'],
                    ['queued', 'Queued']
                ]
            },
            handovers: {
                title: 'Admin Handover Trails',
                icon: 'fa-user-clock',
                description: 'Conversation claims, releases, and handovers between administrators.',
                totalLabel: 'Total Handovers',
                successfulLabel: 'Recorded Actions',
                failedLabel: 'Failed',
                todayLabel: 'Actions Today',
                statusLabel: 'Action',
                statuses: [
                    ['', 'All Actions'],
                    ['claimed', 'Claimed'],
                    ['claimed_on_reply', 'Claimed on Reply'],
                    ['released', 'Released']
                ]
            },
            deletions: {
                title: 'Deleted Reports and Enquiries Trails',
                icon: 'fa-trash-alt',
                description: 'Soft deletion, restoration, and permanent deletion activity.',
                totalLabel: 'Deletion Events',
                successfulLabel: 'Recorded Actions',
                failedLabel: 'Failed',
                todayLabel: 'Actions Today',
                statusLabel: 'Action',
                statuses: [
                    ['', 'All Actions'],
                    ['moved_to_trash', 'Moved to Trash'],
                    ['restored', 'Restored'],
                    ['permanently_deleted', 'Permanently Deleted']
                ]
            },
            ers_transfers: {
                title: 'Reports Transferred to ERS',
                icon: 'fa-share-from-square',
                description: 'Emergency reports and calls sent to the Emergency Response System.',
                totalLabel: 'Total Transfers',
                successfulLabel: 'Successful',
                failedLabel: 'Failed',
                todayLabel: 'Sent Today',
                statusLabel: 'Transfer Status',
                statuses: [
                    ['', 'All Statuses'],
                    ['prepared', 'Prepared'],
                    ['requested', 'Status Requested'],
                    ['pending', 'Pending'],
                    ['sent', 'Sent'],
                    ['accepted', 'Accepted'],
                    ['answered', 'Answered'],
                    ['completed', 'Completed'],
                    ['failed', 'Failed']
                ]
            }
        };

        let auditPage = 1;
        let auditTotalPages = 0;
        let auditTotalRows = 0;
        const auditPageSize = 10;
        let auditLoading = false;
        let auditRequestVersion = 0;
        let auditAbortController = null;
        const auditRows = new Map();

        function escapeAuditHtml(value) {
            const div = document.createElement('div');
            div.textContent = value == null ? '' : String(value);
            return div.innerHTML;
        }

        function humanizeAuditValue(value) {
            return String(value == null || value === '' ? 'N/A' : value)
                .replace(/_/g, ' ')
                .replace(/\b\w/g, character => character.toUpperCase());
        }

        function auditStatusClass(status) {
            const normalized = String(status || '').toLowerCase();
            if (['success', 'sent', 'completed', 'delivered', 'accepted', 'answered', 'transferred', 'restored', 'claimed', 'claimed_on_reply'].includes(normalized)) {
                return 'audit-status-good';
            }
            if (['failed', 'error', 'rejected', 'permanently_deleted'].includes(normalized)) {
                return 'audit-status-bad';
            }
            if (['pending', 'queued', 'prepared', 'moved_to_trash', 'released'].includes(normalized)) {
                return 'audit-status-waiting';
            }
            return 'audit-status-neutral';
        }

        function currentTrailType() {
            return document.getElementById('filterTrailType').value;
        }

        function updateTrailControls() {
            const trailType = currentTrailType();
            const config = auditTrailConfigs[trailType];
            const statusSelect = document.getElementById('filterStatus');
            const previousStatus = statusSelect.value;

            document.getElementById('filterChannelGroup').hidden = trailType !== 'notifications';
            if (trailType !== 'notifications') {
                document.getElementById('filterChannel').value = '';
            }

            statusSelect.innerHTML = config.statuses
                .map(option => `<option value="${escapeAuditHtml(option[0])}">${escapeAuditHtml(option[1])}</option>`)
                .join('');
            if (config.statuses.some(option => option[0] === previousStatus)) {
                statusSelect.value = previousStatus;
            }

            document.getElementById('filterStatusLabel').textContent = config.statusLabel;
            document.getElementById('auditTableTitle').innerHTML = `<i class="fas ${config.icon}"></i> ${escapeAuditHtml(config.title)}`;
            document.getElementById('auditTableDescription').textContent = config.description;
            document.getElementById('totalAuditLabel').textContent = config.totalLabel;
            document.getElementById('successfulAuditLabel').textContent = config.successfulLabel;
            document.getElementById('failedAuditLabel').textContent = config.failedLabel;
            document.getElementById('todayAuditLabel').textContent = config.todayLabel;
        }

        function getAuditFilters() {
            return {
                trail_type: currentTrailType(),
                date_from: document.getElementById('filterDateFrom').value,
                date_to: document.getElementById('filterDateTo').value,
                channel: document.getElementById('filterChannel').value,
                status: document.getElementById('filterStatus').value
            };
        }

        function setAuditTableMessage(message, isError = false) {
            const tbody = document.querySelector('#auditTrailTable tbody');
            tbody.innerHTML = `<tr><td colspan="9" class="audit-table-message${isError ? ' audit-table-error' : ''}">${escapeAuditHtml(message)}</td></tr>`;
        }

        function renderAuditRows(logs) {
            const tbody = document.querySelector('#auditTrailTable tbody');
            logs.forEach(log => {
                const rowKey = `${log.trail_type}:${log.id}`;
                auditRows.set(rowKey, log);
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${escapeAuditHtml(log.id)}</td>
                    <td><small>${escapeAuditHtml(log.timestamp || 'N/A')}</small></td>
                    <td><span class="audit-trail-chip">${escapeAuditHtml(log.trail_label || 'Audit')}</span></td>
                    <td><div class="audit-cell-subject" title="${escapeAuditHtml(log.subject || '')}">${escapeAuditHtml(log.subject || 'N/A')}</div></td>
                    <td><div class="audit-cell-activity" title="${escapeAuditHtml(log.activity || '')}">${escapeAuditHtml(log.activity || 'N/A')}</div></td>
                    <td><span class="badge ${auditStatusClass(log.status)}">${escapeAuditHtml(humanizeAuditValue(log.status))}</span></td>
                    <td>${escapeAuditHtml(log.admin || 'System')}</td>
                    <td><small>${escapeAuditHtml(log.ip_address || 'N/A')}</small></td>
                    <td>
                        <button class="btn btn-sm btn-primary audit-view-button" type="button" data-audit-key="${escapeAuditHtml(rowKey)}" title="View audit details" aria-label="View audit details">
                            <i class="fas fa-eye"></i>
                        </button>
                    </td>
                `;
                tbody.appendChild(row);
            });
        }

        function loadAuditTrail(page = 1) {
            const requestedPage = Math.max(1, Number(page) || 1);
            auditRequestVersion += 1;
            const requestVersion = auditRequestVersion;

            if (auditAbortController) {
                auditAbortController.abort();
            }
            auditAbortController = new AbortController();
            auditLoading = true;
            auditRows.clear();
            setAuditTableMessage('Loading audit records...');
            renderAuditPagination(0);

            const params = new URLSearchParams(getAuditFilters());
            params.set('page', String(requestedPage));
            params.set('limit', String(auditPageSize));

            return fetch(`../api/audit-trail.php?action=list&${params.toString()}`, {
                signal: auditAbortController.signal
            })
                .then(response => response.json())
                .then(data => {
                    if (requestVersion !== auditRequestVersion) return;
                    if (!data.success) throw new Error(data.message || 'Failed to load audit trail');

                    const tbody = document.querySelector('#auditTrailTable tbody');
                    const logs = Array.isArray(data.logs) ? data.logs : [];
                    const pagination = data.pagination || {};
                    tbody.innerHTML = '';

                    auditPage = Number(pagination.page || requestedPage);
                    auditTotalPages = Number(pagination.total_pages || 0);
                    auditTotalRows = Number(pagination.total || 0);

                    if (!logs.length) {
                        setAuditTableMessage('No audit records found for this trail.');
                        renderAuditPagination(0);
                        return;
                    }

                    renderAuditRows(logs);
                    renderAuditPagination(auditTotalPages);
                })
                .catch(error => {
                    if (error.name === 'AbortError' || requestVersion !== auditRequestVersion) return;
                    console.error('Audit trail load error:', error);
                    setAuditTableMessage('Failed to load audit records.', true);
                    renderAuditPagination(0);
                })
                .finally(() => {
                    if (requestVersion === auditRequestVersion) {
                        auditLoading = false;
                    }
                });
        }

        function renderAuditPagination(totalPages) {
            const container = document.getElementById('auditPagination');
            if (!container) return;

            if (totalPages <= 1) {
                container.innerHTML = '';
                container.hidden = true;
                return;
            }

            const pages = [];
            const startPage = Math.max(1, auditPage - 2);
            const endPage = Math.min(totalPages, auditPage + 2);
            if (startPage > 1) pages.push(1);
            if (startPage > 2) pages.push('ellipsis-start');
            for (let page = startPage; page <= endPage; page += 1) pages.push(page);
            if (endPage < totalPages - 1) pages.push('ellipsis-end');
            if (endPage < totalPages) pages.push(totalPages);

            const pageButtons = pages.map(page => {
                if (typeof page !== 'number') {
                    return '<span class="audit-page-ellipsis" aria-hidden="true">&hellip;</span>';
                }
                const active = page === auditPage;
                return `<button type="button" class="audit-page-btn${active ? ' active' : ''}" data-page="${page}" ${active ? 'aria-current="page"' : ''}>${page}</button>`;
            }).join('');

            container.innerHTML = `
                <button type="button" class="audit-page-btn audit-page-nav" data-page="${auditPage - 1}" ${auditPage <= 1 ? 'disabled' : ''} aria-label="Previous page">&lsaquo;</button>
                ${pageButtons}
                <button type="button" class="audit-page-btn audit-page-nav" data-page="${auditPage + 1}" ${auditPage >= totalPages ? 'disabled' : ''} aria-label="Next page">&rsaquo;</button>
            `;
            container.hidden = false;
        }

        function goToAuditPage(page) {
            const targetPage = Number(page);
            if (auditLoading || !Number.isInteger(targetPage) || targetPage < 1 || targetPage > auditTotalPages || targetPage === auditPage) {
                return;
            }

            loadAuditTrail(targetPage).then(() => {
                document.getElementById('auditTrailTable').scrollIntoView({ behavior: 'smooth', block: 'start' });
            });
        }
        function loadStatistics() {
            const params = new URLSearchParams(getAuditFilters());
            return fetch(`../api/audit-trail.php?action=statistics&${params.toString()}`)
                .then(response => response.json())
                .then(data => {
                    if (!data.success) throw new Error(data.message || 'Failed to load statistics');
                    document.getElementById('totalAuditRecords').textContent = data.total || 0;
                    document.getElementById('successfulAuditRecords').textContent = data.successful || 0;
                    document.getElementById('failedAuditRecords').textContent = data.failed || 0;
                    document.getElementById('todayAuditRecords').textContent = data.today || 0;
                })
                .catch(error => console.error('Audit statistics load error:', error));
        }

        function refreshSelectedAudit() {
            loadAuditTrail(1);
            loadStatistics();
        }

        function detailLabel(key) {
            return String(key).replace(/_/g, ' ').replace(/\b\w/g, character => character.toUpperCase());
        }

        function detailValue(value) {
            if (value == null || value === '') return 'N/A';
            if (typeof value === 'object') return JSON.stringify(value, null, 2);
            return String(value);
        }

        function openAuditDetails(row) {
            const details = row.details && typeof row.details === 'object' ? row.details : {};
            const detailRows = Object.entries(details).map(([key, value]) => {
                const formattedValue = detailValue(value);
                const usePre = typeof value === 'object' || formattedValue.length > 160 || formattedValue.includes('\n');
                return `
                    <div class="detail-item audit-detail-wide">
                        <span class="detail-label">${escapeAuditHtml(detailLabel(key))}</span>
                        ${usePre
                            ? `<pre class="audit-detail-pre">${escapeAuditHtml(formattedValue)}</pre>`
                            : `<span class="detail-value">${escapeAuditHtml(formattedValue)}</span>`}
                    </div>
                `;
            }).join('');

            document.getElementById('auditDetailsTitle').textContent = `${row.trail_label || 'Audit'} Details`;
            document.getElementById('detailsContent').innerHTML = `
                <div class="details-grid audit-details-summary">
                    <div class="detail-item">
                        <span class="detail-label">Audit ID</span>
                        <span class="detail-value">#${escapeAuditHtml(row.id)}</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Timestamp</span>
                        <span class="detail-value">${escapeAuditHtml(row.timestamp || 'N/A')}</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Subject</span>
                        <span class="detail-value">${escapeAuditHtml(row.subject || 'N/A')}</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Status / Action</span>
                        <span class="detail-value"><span class="badge ${auditStatusClass(row.status)}">${escapeAuditHtml(humanizeAuditValue(row.status))}</span></span>
                    </div>
                    ${detailRows}
                </div>
            `;
            document.getElementById('detailsModal').style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }

        function closeAuditDetails() {
            document.getElementById('detailsModal').style.display = 'none';
            document.body.style.overflow = '';
        }

        function exportAuditTrail() {
            const exportButton = document.getElementById('exportAuditTrailPdfBtn');
            if (window.AdminReportPdfExporter && typeof window.AdminReportPdfExporter.exportCurrentPage === 'function') {
                window.AdminReportPdfExporter.exportCurrentPage({
                    filenamePrefix: `audit-trail-${currentTrailType()}`,
                    targetSelector: '.main-content .main-container',
                    triggerButton: exportButton
                });
                return;
            }
            window.alert('PDF export is currently unavailable.');
        }

        document.addEventListener('DOMContentLoaded', function () {
            updateTrailControls();
            refreshSelectedAudit();

            document.getElementById('filterTrailType').addEventListener('change', function () {
                updateTrailControls();
                refreshSelectedAudit();
            });

            ['filterDateFrom', 'filterDateTo', 'filterChannel', 'filterStatus'].forEach(function (controlId) {
                document.getElementById(controlId).addEventListener('change', refreshSelectedAudit);
            });

            document.getElementById('auditPagination').addEventListener('click', function (event) {
                const button = event.target.closest('.audit-page-btn[data-page]');
                if (!button || button.disabled) return;
                goToAuditPage(button.dataset.page);
            });

            document.querySelector('#auditTrailTable tbody').addEventListener('click', function (event) {
                const button = event.target.closest('.audit-view-button');
                if (!button) return;
                const row = auditRows.get(button.dataset.auditKey);
                if (row) openAuditDetails(row);
            });

            document.getElementById('exportAuditTrailPdfBtn').addEventListener('click', exportAuditTrail);
            document.getElementById('closeAuditDetails').addEventListener('click', closeAuditDetails);
            document.getElementById('detailsModal').addEventListener('click', function (event) {
                if (event.target === this) closeAuditDetails();
            });
            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape') closeAuditDetails();
            });
        });
    </script>
</body>
</html>
