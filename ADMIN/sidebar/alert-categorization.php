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
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
</head>
<body>
    <style>
        .preview-container {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 1.5rem;
            margin-top: 1rem;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 150px;
        }
        .category-preview-card {
            padding: 1rem 2rem;
            border-radius: 12px;
            color: white;
            display: flex;
            align-items: center;
            gap: 1rem;
            font-size: 1.25rem;
            font-weight: 600;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            transition: all 0.3s ease;
        }
        .category-preview-card i {
            font-size: 1.5rem;
        }
        .icon-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(50px, 1fr));
            gap: 0.5rem;
            margin-top: 0.5rem;
            max-height: 150px;
            overflow-y: auto;
            padding: 0.5rem;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .icon-option {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 40px;
            border: 1px solid #eee;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.2s;
        }
        .icon-option:hover {
            background: #f0f0f0;
            border-color: var(--primary-color-1);
        }
        .icon-option.selected {
            background: var(--primary-color-1);
            color: white;
            border-color: var(--primary-color-1);
        }
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        .status-active { background: #e8f5e9; color: #2e7d32; }
        .status-inactive { background: #ffebee; color: #c62828; }
        .muted-row { opacity: 0.6; filter: grayscale(0.5); }

        /* Analytics and Audit Log UI */
        .details-row { background: #fdfdfd !important; display: none; }
        .details-content { padding: 1.5rem; border: 1px solid #eee; border-top: none; }
        .analytics-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1rem; margin-bottom: 1.5rem; }
        .stat-box { background: white; padding: 1rem; border-radius: 8px; border: 1px solid #eee; box-shadow: 0 2px 5px rgba(0,0,0,0.02); }
        .stat-box .label { font-size: 0.75rem; color: #666; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 5px; }
        .stat-box .value { font-size: 1.25rem; font-weight: 700; color: var(--primary-color-1); }
        .audit-list { font-size: 0.85rem; max-height: 200px; overflow-y: auto; border: 1px solid #eee; border-radius: 4px; background: white; }
        .audit-item { padding: 8px 12px; border-bottom: 1px solid #f5f5f5; display: flex; justify-content: space-between; align-items: center; }
        .audit-item:last-child { border-bottom: none; }
        .audit-date { color: #999; font-size: 0.75rem; white-space: nowrap; margin-left: 10px; }
        .expand-btn { cursor: pointer; color: var(--primary-color-1); transition: transform 0.2s; }
        .expand-btn.active { transform: rotate(180deg); }
        .access-denied { opacity: 0.7; pointer-events: none; position: relative; }
        .access-denied::after { content: '\f023  Restricted'; font-family: 'Font Awesome 6 Free'; font-weight: 900; position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: rgba(0,0,0,0.8); color: white; padding: 5px 15px; border-radius: 20px; font-size: 0.8rem; z-index: 10; }

        /* Impact Warnings & AI Suggestions */
        .impact-warning {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            background: #fff3e0;
            color: #e65100;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 0.7rem;
            font-weight: 600;
            margin-left: 10px;
            border: 1px solid #ffe0b2;
        }
        .ai-suggestion-box {
            background: #f3e5f5;
            border-left: 4px solid #9c27b0;
            padding: 10px 15px;
            border-radius: 4px;
            margin: 10px 0;
            font-size: 0.85rem;
            color: #4a148c;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .ai-badge {
            background: #9c27b0;
            color: white;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 0.65rem;
            font-weight: 800;
            margin-right: 5px;
        }
        .chart-container {
            height: 180px;
            margin-top: 10px;
        }
        .export-actions {
            display: flex;
            gap: 10px;
            margin-bottom: 15px;
        }
    </style>

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
                            <a href="/" class="breadcrumb-link">
                                <span>Home</span>
                            </a>
                        </li>
                        <li class="breadcrumb-item">
                            <a href="#" class="breadcrumb-link">
                                <span>Emergency Communication</span>
                            </a>
                        </li>
                        <li class="breadcrumb-item active" aria-current="page">
                            <span>Alert Categorization</span>
                        </li>
                    </ol>
                </nav>
                <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                    <div>
                        <h1>Alert Categorization</h1>
                        <p>Organize and manage alert categories for effective emergency communication.</p>
                    </div>
                    <div style="text-align: right;">
                        <span class="badge" style="background: #e3f2fd; color: #1976d2; padding: 8px 15px; border-radius: 20px; font-weight: 600;">
                            <i class="fas fa-user-shield"></i> Role: <?php echo ucwords(str_replace('_', ' ', $adminRole)); ?>
                        </span>
                    </div>
                </div>
            </div>
            
            <div class="sub-container">
                <div class="page-content">
                    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1.5rem;">
                        <!-- Add New Category Form -->
                        <div class="module-card <?php echo !$canEdit ? 'access-denied' : ''; ?>">
                            <div class="module-card-header">
                                <h2 id="formTitle"><i class="fas fa-plus-circle"></i> Add New Category</h2>
                            </div>
                            <div class="module-card-content">
                                <form id="categoryForm">
                                    <input type="hidden" id="categoryId" name="id">
                                    <div class="form-row">
                                        <div class="form-group">
                                            <label for="categoryName">Category Name *</label>
                                            <input type="text" id="categoryName" name="name" placeholder="e.g. Flash Flood" required <?php echo !$canEdit ? 'disabled' : ''; ?>>
                                        </div>
                                        <div class="form-group">
                                            <label>Select Icon *</label>
                                            <input type="hidden" id="categoryIcon" name="icon" value="fa-exclamation-triangle">
                                            <div class="icon-grid" id="iconGrid">
                                                <!-- Icons will be populated by JS -->
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label for="categoryDescription">Description</label>
                                        <textarea id="categoryDescription" name="description" rows="2" placeholder="Briefly describe what this category covers..." <?php echo !$canEdit ? 'disabled' : ''; ?>></textarea>
                                    </div>
                                    <div class="form-row">
                                        <div class="form-group">
                                            <label for="categoryColor">Identity Color *</label>
                                            <input type="color" id="categoryColor" name="color" value="#4c8a89" <?php echo !$canEdit ? 'disabled' : ''; ?>>
                                        </div>
                                        <div class="form-group">
                                            <label for="categoryStatus">Status</label>
                                            <select id="categoryStatus" name="status" <?php echo !$canEdit ? 'disabled' : ''; ?>>
                                                <option value="active">Active</option>
                                                <option value="inactive">Inactive</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="form-actions">
                                        <?php if ($canEdit): ?>
                                        <button type="submit" class="btn btn-primary" id="submitBtn">
                                            <i class="fas fa-save"></i> Save Category
                                        </button>
                                        <button type="button" class="btn btn-secondary" id="resetBtn" style="display:none;">
                                            <i class="fas fa-times"></i> Cancel Edit
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <!-- Live Preview -->
                        <div class="module-card">
                            <div class="module-card-header">
                                <h2><i class="fas fa-eye"></i> Live Preview</h2>
                            </div>
                            <div class="module-card-content">
                                <p style="font-size: 0.85rem; color: #666; margin-bottom: 1rem;">Citizens see this visual style in their alert feed.</p>
                                <div class="preview-container">
                                    <div id="livePreview" class="category-preview-card">
                                        <i class="fas fa-exclamation-triangle" id="previewIcon"></i>
                                        <span id="previewName">Category Name</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Categories List -->
                    <div class="module-card" style="margin-top: 1.5rem;">
                        <div class="module-card-header">
                            <h2><i class="fas fa-list"></i> Managed Categories</h2>
                        </div>
                        <div class="module-card-content">
                            <table class="data-table" id="categoriesTable">
                                <thead>
                                    <tr>
                                        <th style="width: 40px;"></th>
                                        <th>Name</th>
                                        <th>Visual</th>
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

        function initIconGrid() {
            const grid = document.getElementById('iconGrid');
            const iconInput = document.getElementById('categoryIcon');
            
            icons.forEach(icon => {
                const div = document.createElement('div');
                div.className = `icon-option ${icon === iconInput.value ? 'selected' : ''}`;
                div.innerHTML = `<i class="fas ${icon}"></i>`;
                if (canEdit) {
                    div.onclick = () => {
                        document.querySelectorAll('.icon-option').forEach(opt => opt.classList.remove('selected'));
                        div.classList.add('selected');
                        iconInput.value = icon;
                        updatePreview();
                    };
                }
                grid.appendChild(div);
            });
        }

        function updatePreview() {
            const name = document.getElementById('categoryName').value || 'Category Name';
            const icon = document.getElementById('categoryIcon').value;
            const color = document.getElementById('categoryColor').value;
            
            const preview = document.getElementById('livePreview');
            const previewIcon = document.getElementById('previewIcon');
            const previewName = document.getElementById('previewName');
            
            preview.style.backgroundColor = color;
            previewIcon.className = `fas ${icon}`;
            previewName.textContent = name;
        }

        document.getElementById('categoryName').oninput = updatePreview;
        document.getElementById('categoryColor').oninput = updatePreview;

        document.getElementById('categoryForm').addEventListener('submit', function(e) {
            e.preventDefault();
            if (!canEdit) return;

            const formData = new FormData(this);
            const submitBtn = document.getElementById('submitBtn');
            const originalBtnHtml = submitBtn.innerHTML;
            
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
            submitBtn.disabled = true;
            
            fetch('../api/alert-categories.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    resetForm();
                    loadCategories();
                    // Brief delay before re-enabling
                    setTimeout(() => {
                        submitBtn.innerHTML = originalBtnHtml;
                        submitBtn.disabled = false;
                    }, 500);
                } else {
                    alert('Error: ' + data.message);
                    submitBtn.innerHTML = originalBtnHtml;
                    submitBtn.disabled = false;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while saving.');
                submitBtn.innerHTML = originalBtnHtml;
                submitBtn.disabled = false;
            });
        });

        function resetForm() {
            document.getElementById('categoryForm').reset();
            document.getElementById('categoryId').value = '';
            document.getElementById('formTitle').innerHTML = '<i class="fas fa-plus-circle"></i> Add New Category';
            document.getElementById('submitBtn').innerHTML = '<i class="fas fa-save"></i> Save Category';
            document.getElementById('resetBtn').style.display = 'none';
            document.getElementById('categoryIcon').value = 'fa-exclamation-triangle';
            document.querySelectorAll('.icon-option').forEach(opt => opt.classList.remove('selected'));
            document.querySelector('.icon-option').classList.add('selected');
            updatePreview();
        }

        document.getElementById('resetBtn').onclick = resetForm;

        function loadCategories() {
            fetch('../api/alert-categories.php?action=list')
                .then(response => response.json())
                .then(data => {
                    const tbody = document.querySelector('#categoriesTable tbody');
                    tbody.innerHTML = '';
                    
                    if (data.success && data.categories) {
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
                                    <div style="background:${cat.color}; color:white; padding:5px 12px; border-radius:15px; display:inline-flex; align-items:center; gap:8px; font-size:0.85rem;">
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
                                        <button class="btn btn-sm btn-primary" onclick='editCategory(${JSON.stringify(cat)})' title="Edit" ${!canEdit ? 'disabled' : ''}>
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
                <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                    <div style="flex: 1;">
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
                    <div style="flex: 1; margin-left: 20px;">
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

                <div class="module-card" style="box-shadow: none; border: 1px solid #eee; margin-top: 15px;">
                    <div class="module-card-header" style="padding: 10px 15px;">
                        <h4 style="margin:0; font-size:0.9rem;"><i class="fas fa-history"></i> Recent Audit Trail</h4>
                    </div>
                    <div class="audit-list">
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

        function editCategory(cat) {
            if (!canEdit) return;
            document.getElementById('categoryId').value = cat.id;
            document.getElementById('categoryName').value = cat.name;
            document.getElementById('categoryDescription').value = cat.description || '';
            document.getElementById('categoryColor').value = cat.color;
            document.getElementById('categoryIcon').value = cat.icon;
            document.getElementById('categoryStatus').value = cat.status || 'active';
            
            document.getElementById('formTitle').innerHTML = '<i class="fas fa-edit"></i> Edit Category';
            document.getElementById('submitBtn').innerHTML = '<i class="fas fa-check"></i> Update Category';
            document.getElementById('resetBtn').style.display = 'inline-block';
            
            document.querySelectorAll('.icon-option').forEach(opt => {
                opt.classList.toggle('selected', opt.innerHTML.includes(cat.icon));
            });
            
            updatePreview();
            window.scrollTo({ top: 0, behavior: 'smooth' });
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
            initIconGrid();
            loadCategories();
            updatePreview();
        });
    </script>
</body>
</html>

