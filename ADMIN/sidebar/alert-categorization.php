<?php
/**
 * Alert Categorization Page
 * Manage alert categories: Weather, Earthquake, Bomb Threat, etc.
 */

// Start session and check authentication
session_start();

// Check if user is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../login.php');
    exit();
}

// Role-Based Access Control (RBAC)
$adminRole = $_SESSION['admin_role'] ?? 'staff'; // Default to staff if role is not set
$canEdit = in_array($adminRole, ['super_admin', 'admin']);
$canDelete = ($adminRole === 'super_admin');

$pageTitle = 'Alert Categorization';
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
    <link rel="stylesheet" href="css/module-alert-categorization.css?v=<?php echo filemtime(__DIR__ . '/css/module-alert-categorization.css'); ?>">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
</head>
<body>
    <!-- Include Sidebar Component -->
    <?php include 'includes/sidebar.php'; ?>

    <!-- Include Admin Header Component -->
    <?php include 'includes/admin-header.php'; ?>
    
    <!-- ===================================
       MAIN CONTENT - Alert Categorization
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
                            <span>Alert Categorization</span>
                        </li>
                    </ol>
                </nav>
                <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 1rem;">
                    <div>
                        <h1><i class="fas fa-tags" style="color: var(--primary-color-1); margin-right: 0.5rem;"></i> Alert Categorization</h1>
                        <p>Organize and manage alert categories for effective emergency communication.</p>
                    </div>
                    <div>
                        <span class="badge" style="background: rgba(52, 152, 219, 0.1); color: #3498db; padding: 0.5rem 1rem; border-radius: 20px; font-weight: 600; border: 1px solid rgba(52, 152, 219, 0.2);">
                            <i class="fas fa-user-shield"></i> Role: <?php echo ucwords(str_replace('_', ' ', $adminRole)); ?>
                        </span>
                    </div>
                </div>
            </div>
            
            <div class="sub-container">
                <div class="page-content">
                    <div class="ac-mini-analytics" id="acSummaryCards">
                        <div class="ac-stat ac-stat--total">
                            <div class="ac-stat-top">
                                <div class="ac-stat-label">Total Categories</div>
                                <div class="ac-stat-icon"><i class="fas fa-tags"></i></div>
                            </div>
                            <div class="ac-stat-value" id="acTotalCats">0</div>
                            <div class="ac-stat-sub" id="acTotalCatsSub">Loaded from category list</div>
                        </div>
                        <div class="ac-stat ac-stat--done">
                            <div class="ac-stat-top">
                                <div class="ac-stat-label">Active</div>
                                <div class="ac-stat-icon"><i class="fas fa-check-circle"></i></div>
                            </div>
                            <div class="ac-stat-value" id="acActiveCats">0</div>
                            <div class="ac-stat-sub" id="acActiveCatsSub">Currently enabled</div>
                        </div>
                        <div class="ac-stat ac-stat--progress">
                            <div class="ac-stat-top">
                                <div class="ac-stat-label">Inactive</div>
                                <div class="ac-stat-icon"><i class="fas fa-pause-circle"></i></div>
                            </div>
                            <div class="ac-stat-value" id="acInactiveCats">0</div>
                            <div class="ac-stat-sub" id="acInactiveCatsSub">Not shown to users</div>
                        </div>
                        <div class="ac-stat ac-stat--rate">
                            <div class="ac-stat-top">
                                <div class="ac-stat-label">High Load</div>
                                <div class="ac-stat-icon"><i class="fas fa-exclamation-circle"></i></div>
                            </div>
                            <div class="ac-stat-value" id="acHighLoadCats">0</div>
                            <div class="ac-stat-sub" id="acHighLoadCatsSub">Categories over 20 alerts</div>
                        </div>
                    </div>
                    <div class="ac-process" aria-label="How alert categorization works">
                        <div class="ac-process-title">How Alert Categorization Works</div>
                        <div class="ac-process-track">
                            <div class="ac-process-step">
                                <div class="ac-process-icon" aria-hidden="true"><i class="fas fa-tags"></i></div>
                                <h4>Define Categories</h4>
                                <p>Create clear labels, icons, and colors for each alert type.</p>
                            </div>
                            <div class="ac-process-arrow" aria-hidden="true"><i class="fas fa-arrow-right"></i></div>
                            <div class="ac-process-step">
                                <div class="ac-process-icon" aria-hidden="true"><i class="fas fa-shield-alt"></i></div>
                                <h4>System Uses Tags</h4>
                                <p>Alerts are organized by category for routing and analytics.</p>
                            </div>
                            <div class="ac-process-arrow" aria-hidden="true"><i class="fas fa-arrow-right"></i></div>
                            <div class="ac-process-step">
                                <div class="ac-process-icon" aria-hidden="true"><i class="fas fa-bell"></i></div>
                                <h4>Citizens Receive</h4>
                                <p>Users see consistent labels and colors across channels.</p>
                            </div>
                        </div>
                    </div>
                    <div class="ac-cta" aria-label="Add new category">
                        <div>
                            <div class="ac-cta-title">Create a New Alert Category</div>
                            <div class="ac-cta-sub">Use the guided modal to keep labels consistent and user-friendly.</div>
                        </div>
                        <button type="button" class="btn btn-primary" id="openCategoryModalBtn">
                            <i class="fas fa-plus-circle" style="margin-right: 0.5rem;"></i> Add New Category
                        </button>
                    </div>

                    <!-- Categories List -->
                    <div class="module-card" style="margin-top: 1.5rem;">
                        <div class="module-card-header">
                            <h2><i class="fas fa-list"></i> Managed Categories</h2>
                        </div>
                        <div class="module-card-content">
                            <div style="overflow-x: auto;">
                                <table class="data-table" id="categoriesTable">
                                    <thead>
                                        <tr>
                                            <th style="width: 40px;"></th>
                                            <th>Name</th>
                                            <th>Visual Identity</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <!-- Data loaded via API -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add/Edit Category Modal -->
    <div class="ac-modal-backdrop" id="acCategoryModalBackdrop" aria-hidden="true">
        <div class="ac-modal" role="dialog" aria-modal="true" aria-labelledby="acCategoryModalTitle">
            <div class="ac-modal-header">
                <div>
                    <h3 class="ac-modal-title" id="acCategoryModalTitle">Add New Category</h3>
                    <div class="ac-modal-subtitle">Define a clear category name, icon, and color for alerts.</div>
                </div>
                <button class="ac-modal-close" type="button" id="acCloseCategoryModalBtn" aria-label="Close">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="ac-modal-body">
                <div class="ac-modal-grid">
                    <div class="ac-modal-left">
                        <div class="module-card <?php echo !$canEdit ? 'access-denied' : ''; ?>">
                            <div class="module-card-header">
                                <h2 id="formTitleModal"><i class="fas fa-plus-circle"></i> Add New Category</h2>
                            </div>
                            <div class="module-card-content">
                                <form id="categoryFormModal">
                                    <input type="hidden" id="categoryIdModal" name="id">
                                    <div class="form-row">
                                        <div class="form-group">
                                            <label for="categoryNameModal">Category Name *</label>
                                            <input type="text" id="categoryNameModal" name="name" placeholder="e.g. Flash Flood" required <?php echo !$canEdit ? 'disabled' : ''; ?>>
                                        </div>
                                        <div class="form-group">
                                            <label>Select Icon *</label>
                                            <input type="hidden" id="categoryIconModal" name="icon" value="fa-exclamation-triangle">
                                            <div class="icon-grid" id="iconGridModal">
                                                <!-- Icons will be populated by JS -->
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label for="categoryDescriptionModal">Description</label>
                                        <textarea id="categoryDescriptionModal" name="description" rows="2" placeholder="Briefly describe what this category covers..." <?php echo !$canEdit ? 'disabled' : ''; ?>></textarea>
                                    </div>
                                    <div class="form-row">
                                        <div class="form-group">
                                            <label for="categoryColorModal">Identity Color *</label>
                                            <input type="color" id="categoryColorModal" name="color" value="#4c8a89" style="height: 42px; padding: 0.25rem;" <?php echo !$canEdit ? 'disabled' : ''; ?>>
                                        </div>
                                        <div class="form-group">
                                            <label for="categoryStatusModal">Status</label>
                                            <select id="categoryStatusModal" name="status" <?php echo !$canEdit ? 'disabled' : ''; ?>>
                                                <option value="active">Active</option>
                                                <option value="inactive">Inactive</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="form-actions">
                                        <?php if ($canEdit): ?>
                                        <button type="submit" class="btn btn-primary" id="submitBtnModal" style="padding: 0.75rem 1.5rem;">
                                            <i class="fas fa-save"></i> Save Category
                                        </button>
                                        <button type="button" class="btn btn-secondary" id="resetBtnModal" style="display:none; padding: 0.75rem 1.5rem;">
                                            <i class="fas fa-times"></i> Cancel Edit
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    <div class="ac-modal-right">
                        <div class="module-card">
                            <div class="module-card-header">
                                <h2><i class="fas fa-eye"></i> Live Preview</h2>
                            </div>
                            <div class="module-card-content">
                                <p style="font-size: 0.9rem; color: var(--text-secondary-1); margin-bottom: 1rem;">Citizens see this visual style in their alert feed.</p>
                                <div class="preview-container">
                                    <div id="livePreviewModal" class="category-preview-card">
                                        <i class="fas fa-exclamation-triangle" id="previewIconModal"></i>
                                        <span id="previewNameModal">Category Name</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="ac-modal-note">
                            Tip: Keep names short and action-focused for clarity during emergencies.
                        </div>
                    </div>
                </div>
            </div>
            <div class="ac-modal-footer">
                <div class="ac-modal-hint">Changes are audited for accountability.</div>
                <div class="ac-modal-actions">
                    <button type="button" class="btn btn-secondary" id="acCloseCategoryModalBtnFooter">Close</button>
                    <button type="button" class="btn btn-primary" id="acSaveFromFooterBtn">
                        <i class="fas fa-save" style="margin-right: 0.4rem;"></i> Save Category
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        const icons = [
            'fa-exclamation-triangle', 'fa-cloud-rain', 'fa-mountain', 'fa-bomb', 
            'fa-fire', 'fa-tornado', 'fa-biohazard', 'fa-radiation', 'fa-wind',
            'fa-water', 'fa-user-shield', 'fa-first-aid', 'fa-bullhorn', 'fa-broadcast-tower',
            'fa-car-crash', 'fa-hospital', 'fa-search-location', 'fa-shuttle-van', 'fa-bolt'
        ];

        const canEdit = <?php echo $canEdit ? 'true' : 'false'; ?>;
        const canDelete = <?php echo $canDelete ? 'true' : 'false'; ?>;
        const adminRole = '<?php echo $adminRole; ?>';
        let analyticsCache = {};
        let activeCharts = {};

        function initIconGrid(gridId, inputId, canEditLocal) {
            const grid = document.getElementById(gridId);
            const iconInput = document.getElementById(inputId);
            if (!grid || !iconInput) return;
            grid.innerHTML = '';
            
            icons.forEach(icon => {
                const div = document.createElement('div');
                div.className = `icon-option ${icon === iconInput.value ? 'selected' : ''}`;
                div.innerHTML = `<i class="fas ${icon}"></i>`;
                if (canEditLocal) {
                    div.onclick = () => {
                        grid.querySelectorAll('.icon-option').forEach(opt => opt.classList.remove('selected'));
                        div.classList.add('selected');
                        iconInput.value = icon;
                        updatePreviewModal(inputId);
                    };
                }
                grid.appendChild(div);
            });
        }

        function updatePreviewModal(inputId) {
            const isModal = inputId === 'categoryIconModal';
            const name = document.getElementById(isModal ? 'categoryNameModal' : 'categoryName')?.value || 'Category Name';
            const icon = document.getElementById(isModal ? 'categoryIconModal' : 'categoryIcon')?.value || 'fa-exclamation-triangle';
            const color = document.getElementById(isModal ? 'categoryColorModal' : 'categoryColor')?.value || '#4c8a89';

            const preview = document.getElementById(isModal ? 'livePreviewModal' : 'livePreview');
            const previewIcon = document.getElementById(isModal ? 'previewIconModal' : 'previewIcon');
            const previewName = document.getElementById(isModal ? 'previewNameModal' : 'previewName');

            if (preview) preview.style.backgroundColor = color;
            if (previewIcon) previewIcon.className = `fas ${icon}`;
            if (previewName) previewName.textContent = name;
        }

        function resetFormModal() {
            const form = document.getElementById('categoryFormModal');
            if (!form) return;
            form.reset();
            document.getElementById('categoryIdModal').value = '';
            document.getElementById('formTitleModal').innerHTML = '<i class="fas fa-plus-circle"></i> Add New Category';
            document.getElementById('submitBtnModal').innerHTML = '<i class="fas fa-save"></i> Save Category';
            document.getElementById('resetBtnModal').style.display = 'none';
            document.getElementById('categoryIconModal').value = 'fa-exclamation-triangle';
            document.querySelectorAll('#iconGridModal .icon-option').forEach(opt => opt.classList.remove('selected'));
            const firstIcon = document.querySelector('#iconGridModal .icon-option');
            if (firstIcon) firstIcon.classList.add('selected');
            updatePreviewModal('categoryIconModal');
        }

        function loadCategories() {
            fetch('../api/alert-categories.php?action=list')
                .then(response => response.json())
                .then(data => {
                    const tbody = document.querySelector('#categoriesTable tbody');
                    tbody.innerHTML = '';
                    
                    if (data.success && data.categories) {
                        updateCategorySummary(data.categories);
                        data.categories.forEach(cat => {
                            const isInactive = cat.status === 'inactive';
                            const tr = document.createElement('tr');
                            if (isInactive) tr.className = 'muted-row';
                            tr.id = `cat-row-${cat.id}`;
                            
                            // Feature 2: Alert Load Impact Warnings
                            let impactWarning = '';
                            if (cat.alerts_count > 20) {
                                impactWarning = `<span class="impact-warning" title="High usage category - may cause alert fatigue"><i class="fas fa-exclamation-circle"></i> High Load</span>`;
                            }

                            tr.innerHTML = `
                                <td><i class="fas fa-chevron-down expand-btn" onclick="toggleDetails(${cat.id})"></i></td>
                                <td>
                                    <div style="display:flex; align-items:center;">
                                        <strong>${cat.name}</strong>
                                        ${impactWarning}
                                    </div>
                                </td>
                                <td>
                                    <div style="background:${cat.color}; color:white; padding:0.35rem 0.75rem; border-radius:50px; display:inline-flex; align-items:center; gap:0.5rem; font-size:0.8rem; font-weight: 600; text-transform: uppercase;">
                                        <i class="fas ${cat.icon}"></i> ${cat.name}
                                    </div>
                                </td>
                                <td>
                                    <span class="status-badge ${isInactive ? 'status-inactive' : 'status-active'}">
                                        ${isInactive ? 'Inactive' : 'Active'}
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <button class="btn btn-sm btn-primary" onclick='editCategoryModal(${JSON.stringify(cat)})' title="Edit" ${!canEdit ? 'disabled' : ''}>
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-sm btn-danger" onclick="deleteCategory(${cat.id}, '${cat.name}', ${cat.alerts_count || 0})" title="Delete" ${!canDelete ? 'disabled' : ''}>
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            `;
                            tbody.appendChild(tr);

                            // Hidden details row
                            const detailsTr = document.createElement('tr');
                            detailsTr.className = 'details-row';
                            detailsTr.id = `details-${cat.id}`;
                            detailsTr.innerHTML = `
                                <td colspan="5">
                                    <div class="details-content" id="details-content-${cat.id}">
                                        <div style="text-align:center; padding: 20px;">
                                            <i class="fas fa-spinner fa-spin"></i> Loading insights...
                                        </div>
                                    </div>
                                </td>
                            `;
                            tbody.appendChild(detailsTr);
                        });
                    }
                });
        }

        function updateCategorySummary(categories) {
            const total = categories.length;
            const active = categories.filter(c => (c.status || 'active') === 'active').length;
            const inactive = total - active;
            const highLoad = categories.filter(c => (c.alerts_count || 0) > 20).length;

            const totalEl = document.getElementById('acTotalCats');
            const activeEl = document.getElementById('acActiveCats');
            const inactiveEl = document.getElementById('acInactiveCats');
            const highLoadEl = document.getElementById('acHighLoadCats');

            if (totalEl) totalEl.textContent = total;
            if (activeEl) activeEl.textContent = active;
            if (inactiveEl) inactiveEl.textContent = inactive;
            if (highLoadEl) highLoadEl.textContent = highLoad;

            const activeSub = document.getElementById('acActiveCatsSub');
            const inactiveSub = document.getElementById('acInactiveCatsSub');
            const highLoadSub = document.getElementById('acHighLoadCatsSub');
            if (activeSub) activeSub.textContent = total ? `${Math.round((active / total) * 100)}% of categories` : 'No categories yet';
            if (inactiveSub) inactiveSub.textContent = inactive ? `${inactive} pending review` : 'All active';
            if (highLoadSub) highLoadSub.textContent = highLoad ? `${highLoad} needs attention` : 'Healthy usage';
        }

        function toggleDetails(id) {
            const detailsRow = document.getElementById(`details-${id}`);
            const btn = document.querySelector(`#cat-row-${id} .expand-btn`);
            const isVisible = detailsRow.style.display === 'table-row';
            
            // Close other rows (optional, but cleaner)
            document.querySelectorAll('.details-row').forEach(row => row.style.display = 'none');
            document.querySelectorAll('.expand-btn').forEach(b => b.classList.remove('active'));

            if (!isVisible) {
                detailsRow.style.display = 'table-row';
                btn.classList.add('active');
                loadAnalytics(id);
            }
        }

        function loadAnalytics(id) {
            const container = document.getElementById(`details-content-${id}`);
            
            if (analyticsCache[id]) {
                renderAnalytics(id, analyticsCache[id]);
                return;
            }

            fetch(`../api/alert-categories.php?action=analytics&id=${id}`)
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        analyticsCache[id] = data.analytics;
                        renderAnalytics(id, data.analytics);
                    } else {
                        container.innerHTML = `<div class="alert alert-danger">Error: ${data.message}</div>`;
                    }
                });
        }

        function renderAnalytics(id, data) {
            const container = document.getElementById(`details-content-${id}`);
            
            let auditLogsHtml = data.audit_logs.length > 0 
                ? data.audit_logs.map(log => `
                    <div class="audit-item">
                        <span><strong>${log.admin_name}</strong> ${log.description.split(': ')[0]}</span>
                        <span class="audit-date">${new Date(log.created_at).toLocaleString()}</span>
                    </div>
                `).join('')
                : '<div style="padding:15px; text-align:center; color:#999;">No audit logs found.</div>';

            // Feature 3: AI-Assisted Category Suggestions
            let aiSuggestion = '';
            if (data.total_alerts > 15) {
                aiSuggestion = `
                    <div class="ai-suggestion-box">
                        <div>
                            <span class="ai-badge">AI SUGGESTION</span>
                            High activity detected. Consider creating sub-categories to avoid alert fatigue.
                        </div>
                        <button class="btn btn-sm btn-secondary" onclick="alert('Manual action required: Please create a specific sub-category for more targeted alerts.')">Act</button>
                    </div>
                `;
            }

            // Feature 4: Export Analytics & Audit Logs
            const canExport = adminRole === 'super_admin' || adminRole === 'admin';

            container.innerHTML = `
                <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 1.5rem;">
                    <div style="flex: 1; min-width: 300px;">
                        <div class="analytics-grid">
                            <div class="stat-box">
                                <div class="label">Total Alerts</div>
                                <div class="value">${data.total_alerts}</div>
                            </div>
                            <div class="stat-box">
                                <div class="label">Active Subscribers</div>
                                <div class="value">${data.active_subscribers}</div>
                            </div>
                            <div class="stat-box">
                                <div class="label">Last Used</div>
                                <div class="value" style="font-size: 0.9rem;">${data.last_used !== 'Never' ? new Date(data.last_used).toLocaleDateString() : 'Never'}</div>
                            </div>
                        </div>
                        ${aiSuggestion}
                    </div>
                    <div style="flex: 1; min-width: 300px;">
                        <div class="stat-box" style="height: auto;">
                            <div class="label">7-Day Usage Trend</div>
                            <div class="chart-container">
                                <canvas id="chart-${id}"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="export-actions" style="${!canExport ? 'display:none' : ''}">
                    <button class="btn btn-sm btn-secondary" onclick="exportCategoryData(${id}, 'csv')"><i class="fas fa-file-csv"></i> Export CSV</button>
                    <button class="btn btn-sm btn-secondary" onclick="exportCategoryData(${id}, 'pdf')"><i class="fas fa-file-pdf"></i> Export PDF</button>
                </div>

                <div class="module-card" style="box-shadow: none; border: 1px solid var(--border-color-1); margin-top: 1rem;">
                    <div class="module-card-header" style="padding: 0.75rem 1rem;">
                        <h4 style="margin:0; font-size:0.9rem; font-weight: 700;"><i class="fas fa-history"></i> Recent Audit Trail</h4>
                    </div>
                    <div class="audit-list" style="border: none;">
                        ${auditLogsHtml}
                    </div>
                </div>
            `;

            // Feature 1: Category Trend Charts
            setTimeout(() => initTrendChart(id, data.trend), 50);
        }

        function initTrendChart(id, trendData) {
            const ctx = document.getElementById(`chart-${id}`).getContext('2d');
            if (activeCharts[id]) activeCharts[id].destroy();

            activeCharts[id] = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: trendData.labels,
                    datasets: [{
                        label: 'Alerts',
                        data: trendData.values,
                        borderColor: '#4c8a89',
                        backgroundColor: 'rgba(76, 138, 137, 0.1)',
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: {
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
                }
            });
        }

        function exportCategoryData(id, format) {
            const data = analyticsCache[id];
            if (!data) return;

            if (format === 'csv') {
                let csv = 'Action,Description,Date\n';
                data.audit_logs.forEach(log => {
                    csv += `"${log.action}","${log.description}","${log.created_at}"\n`;
                });
                const blob = new Blob([csv], { type: 'text/csv' });
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.setAttribute('hidden', '');
                a.setAttribute('href', url);
                a.setAttribute('download', `category_${id}_audit_log.csv`);
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
            } else {
                const element = document.getElementById(`details-content-${id}`);
                const opt = {
                    margin: 1,
                    filename: `category_${id}_report.pdf`,
                    image: { type: 'jpeg', quality: 0.98 },
                    html2canvas: { scale: 2 },
                    jsPDF: { unit: 'in', format: 'letter', orientation: 'portrait' }
                };
                html2pdf().set(opt).from(element).save();
            }
        }

        function editCategoryModal(cat) {
            if (!canEdit) return;
            document.getElementById('categoryIdModal').value = cat.id;
            document.getElementById('categoryNameModal').value = cat.name;
            document.getElementById('categoryDescriptionModal').value = cat.description || '';
            document.getElementById('categoryColorModal').value = cat.color;
            document.getElementById('categoryIconModal').value = cat.icon;
            document.getElementById('categoryStatusModal').value = cat.status || 'active';

            document.getElementById('formTitleModal').innerHTML = '<i class="fas fa-edit"></i> Edit Category';
            document.getElementById('submitBtnModal').innerHTML = '<i class="fas fa-check"></i> Update Category';
            document.getElementById('resetBtnModal').style.display = 'inline-block';

            document.querySelectorAll('#iconGridModal .icon-option').forEach(opt => {
                opt.classList.toggle('selected', opt.innerHTML.includes(cat.icon));
            });

            updatePreviewModal('categoryIconModal');
            openCategoryModal();
        }

        function deleteCategory(id, name, count) {
            if (!canDelete) return;
            if (count > 0) {
                alert(`Deletion Blocked: "${name}" is linked to ${count} alerts.\n\nPlease disable it instead to preserve audit history.`);
                return;
            }

            if (confirm(`Permanently delete category "${name}"? This action is audited.`)) {
                fetch('../api/alert-categories.php', {
                    method: 'DELETE',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({id: id})
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        loadCategories();
                    } else {
                        alert('Error: ' + data.message);
                    }
                });
            }
        }

        // Initialize grid and load data
        document.addEventListener('DOMContentLoaded', () => {
            initIconGrid('iconGridModal', 'categoryIconModal', canEdit);
            loadCategories();
            updatePreviewModal('categoryIconModal');
        });

        // Modal controls
        const modalBackdrop = document.getElementById('acCategoryModalBackdrop');
        const openBtn = document.getElementById('openCategoryModalBtn');
        const closeBtn = document.getElementById('acCloseCategoryModalBtn');
        const closeBtnFooter = document.getElementById('acCloseCategoryModalBtnFooter');
        const saveFooterBtn = document.getElementById('acSaveFromFooterBtn');
        const modalForm = document.getElementById('categoryFormModal');

        function openCategoryModal() {
            if (modalBackdrop) {
                modalBackdrop.classList.add('show');
                modalBackdrop.setAttribute('aria-hidden', 'false');
                document.body.style.overflow = 'hidden';
            }
        }

        function closeCategoryModal() {
            if (modalBackdrop) {
                modalBackdrop.classList.remove('show');
                modalBackdrop.setAttribute('aria-hidden', 'true');
                document.body.style.overflow = '';
            }
        }

        if (openBtn) openBtn.onclick = () => {
            resetFormModal();
            openCategoryModal();
        };
        if (closeBtn) closeBtn.onclick = closeCategoryModal;
        if (closeBtnFooter) closeBtnFooter.onclick = closeCategoryModal;
        if (saveFooterBtn && modalForm) {
            saveFooterBtn.onclick = () => modalForm.requestSubmit();
        }

        if (modalBackdrop) {
            modalBackdrop.addEventListener('click', (e) => {
                if (e.target === modalBackdrop) closeCategoryModal();
            });
        }

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && modalBackdrop && modalBackdrop.classList.contains('show')) {
                closeCategoryModal();
            }
        });

        const categoryNameModal = document.getElementById('categoryNameModal');
        const categoryColorModal = document.getElementById('categoryColorModal');
        if (categoryNameModal) categoryNameModal.oninput = () => updatePreviewModal('categoryIconModal');
        if (categoryColorModal) categoryColorModal.oninput = () => updatePreviewModal('categoryIconModal');

        if (modalForm) {
            modalForm.addEventListener('submit', function(e) {
                e.preventDefault();
                if (!canEdit) return;

                const formData = new FormData(this);
                const submitBtn = document.getElementById('submitBtnModal');
                const originalBtnHtml = submitBtn.innerHTML;
                
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
                submitBtn.disabled = true;
                if (saveFooterBtn) {
                    saveFooterBtn.disabled = true;
                    saveFooterBtn.innerHTML = '<i class="fas fa-spinner fa-spin" style="margin-right: 0.4rem;"></i> Saving';
                }
                
                fetch('../api/alert-categories.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        resetFormModal();
                        loadCategories();
                        closeCategoryModal();
                        setTimeout(() => {
                            submitBtn.innerHTML = originalBtnHtml;
                            submitBtn.disabled = false;
                            if (saveFooterBtn) {
                                saveFooterBtn.disabled = false;
                                saveFooterBtn.innerHTML = '<i class="fas fa-save" style="margin-right: 0.4rem;"></i> Save Category';
                            }
                        }, 500);
                    } else {
                        alert('Error: ' + data.message);
                        submitBtn.innerHTML = originalBtnHtml;
                        submitBtn.disabled = false;
                        if (saveFooterBtn) {
                            saveFooterBtn.disabled = false;
                            saveFooterBtn.innerHTML = '<i class="fas fa-save" style="margin-right: 0.4rem;"></i> Save Category';
                        }
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while saving.');
                    submitBtn.innerHTML = originalBtnHtml;
                    submitBtn.disabled = false;
                    if (saveFooterBtn) {
                        saveFooterBtn.disabled = false;
                        saveFooterBtn.innerHTML = '<i class="fas fa-save" style="margin-right: 0.4rem;"></i> Save Category';
                    }
                });
            });
        }
    </script>
</body>
</html>

