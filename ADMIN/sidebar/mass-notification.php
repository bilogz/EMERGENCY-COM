<?php
/**
 * Mass Notification System Page
 * Manage SMS, Email, and PA (Public Address) Systems for broad communication
 */

// Start session and check authentication
session_start();

// Check if user is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../login.php');
    exit();
}

$pageTitle = 'Mass Notification System';
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
    <!-- Emergency Alert System -->
    <link rel="stylesheet" href="../header/css/emergency-alert.css">
    <!-- Select2 for Rich Dropdowns -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
</head>
<body>
    <style>
        /* Enhanced Mass Notification Styles */
        :root {
            --card-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --card-shadow-hover: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --transition-speed: 0.2s;
        }

        .dispatch-grid {
            display: grid;
            grid-template-columns: 1.2fr 1fr;
            gap: 1.5rem;
            align-items: start;
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

        .module-card-body {
            padding: 1.5rem;
        }

        .dispatch-section {
            background: var(--bg-color-1);
            padding: 1.25rem;
            border-radius: 8px;
            border: 1px solid var(--border-color-1);
            margin-bottom: 1.5rem;
        }

        .dispatch-section h3 {
            margin-top: 0;
            margin-bottom: 1rem;
            font-size: 0.95rem;
            font-weight: 700;
            color: var(--text-color-1);
            display: flex;
            align-items: center;
            gap: 0.5rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .form-group {
            margin-bottom: 1.25rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--text-color-1);
            font-size: 0.9rem;
        }

        .channel-options {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(100px, 1fr));
            gap: 0.75rem;
        }

        .channel-checkbox {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 1rem 0.5rem;
            border: 1px solid var(--border-color-1);
            border-radius: 8px;
            cursor: pointer;
            transition: all var(--transition-speed) ease;
            background: var(--card-bg-1);
            text-align: center;
        }

        .channel-checkbox:hover {
            border-color: var(--primary-color-1);
            background: rgba(76, 138, 137, 0.05);
            transform: translateY(-2px);
        }

        .channel-checkbox.selected {
            background: var(--primary-color-1);
            color: white;
            border-color: var(--primary-color-1);
            box-shadow: 0 4px 6px rgba(76, 138, 137, 0.2);
        }

        .channel-checkbox i {
            font-size: 1.5rem;
            margin-bottom: 0.25rem;
        }

        .severity-options {
            display: flex;
            gap: 0.5rem;
            background: var(--bg-color-1);
            padding: 0.25rem;
            border-radius: 6px;
            border: 1px solid var(--border-color-1);
        }

        .severity-radio {
            flex: 1;
            text-align: center;
            padding: 0.6rem;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 600;
            font-size: 0.85rem;
            transition: all var(--transition-speed) ease;
            color: var(--text-secondary-1);
        }

        .severity-radio:hover {
            background: rgba(0,0,0,0.05);
        }

        .severity-radio.low.selected { background: #27ae60; color: white; }
        .severity-radio.medium.selected { background: #f39c12; color: white; }
        .severity-radio.high.selected { background: #e67e22; color: white; }
        .severity-radio.critical.selected { background: #e74c3c; color: white; }

        .preview-box {
            background: #f8f9fa;
            border: 1px solid var(--border-color-1);
            border-radius: 8px;
            padding: 1.25rem;
            margin-top: 1.5rem;
            position: relative;
        }
        
        [data-theme="dark"] .preview-box {
            background: rgba(255,255,255,0.05);
        }

        .preview-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            border-bottom: 1px dashed var(--border-color-1);
            padding-bottom: 0.75rem;
        }

        .preview-label {
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--text-secondary-1);
        }

        .category-preview-pill {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.35rem 0.75rem;
            border-radius: 20px;
            color: white;
            font-weight: 600;
            font-size: 0.8rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .preview-content {
            font-weight: 600;
            color: var(--text-color-1);
            font-size: 1rem;
            line-height: 1.4;
        }

        .preview-body-text {
            margin-top: 0.5rem;
            font-size: 0.9rem;
            color: var(--text-secondary-1);
            line-height: 1.5;
        }

        .char-counter {
            text-align: right;
            font-size: 0.75rem;
            color: var(--text-secondary-1);
            margin-top: 0.5rem;
            font-weight: 500;
        }

        .progress-container {
            width: 100%;
            background-color: var(--bg-color-1);
            border-radius: 10px;
            height: 6px;
            margin-top: 8px;
            overflow: hidden;
        }

        .progress-bar {
            height: 100%;
            background-color: var(--primary-color-1);
            border-radius: 10px;
            transition: width 0.5s ease;
        }

        /* Select2 Customization */
        .select2-container--default .select2-selection--single {
            height: 42px;
            padding: 6px;
            border: 1px solid var(--border-color-1);
            border-radius: 8px;
            background-color: var(--bg-color-1);
        }
        
        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 40px;
        }
        
        .select2-container--default .select2-selection--single .select2-selection__rendered {
            color: var(--text-color-1);
            line-height: 28px;
        }
        
        .select2-dropdown {
            background-color: var(--card-bg-1);
            border: 1px solid var(--border-color-1);
        }
        
        .select2-container--default .select2-results__option--highlighted[aria-selected] {
            background-color: var(--primary-color-1);
        }

        /* Data Table Improvements */
        .data-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .data-table th, .data-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid var(--border-color-1);
        }
        
        .data-table th {
            background-color: var(--bg-color-1);
            font-weight: 600;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--text-secondary-1);
        }
        
        .data-table tr:hover {
            background-color: rgba(0,0,0,0.02);
        }

        .badge {
            padding: 0.35rem 0.65rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            display: inline-block;
        }
        
        .badge.completed { background: rgba(46, 204, 113, 0.15); color: #2ecc71; }
        .badge.failed { background: rgba(231, 76, 60, 0.15); color: #e74c3c; }
        .badge.sending { background: rgba(243, 156, 18, 0.15); color: #f39c12; }

        @media (max-width: 992px) {
            .dispatch-grid { grid-template-columns: 1fr; }
        }
    </style>

    <!-- Include Sidebar Component -->
    <?php include 'includes/sidebar.php'; ?>

    <!-- Include Admin Header Component -->
    <?php include 'includes/admin-header.php'; ?>
    
    <div class="main-content">
        <div class="main-container">
            <div class="title">
                <nav class="breadcrumb" aria-label="Breadcrumb">
                    <ol class="breadcrumb-list">
                        <li class="breadcrumb-item"><a href="dashboard.php" class="breadcrumb-link">Dashboard</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Mass Notification</li>
                    </ol>
                </nav>
                <h1><i class="fas fa-broadcast-tower" style="color: var(--primary-color-1); margin-right: 0.5rem;"></i> Mass Notification System</h1>
                <p>Broadcast emergency alerts across multiple channels to targeted audiences.</p>
            </div>
            
            <div class="sub-container">
                <div class="page-content">
                    
                    <form id="dispatchForm">
                        <div class="dispatch-grid">
                            <!-- Left Column: Settings -->
                            <div class="dispatch-left">
                                <!-- Target Audience -->
                                <div class="module-card">
                                    <div class="module-card-header">
                                        <h2><i class="fas fa-users"></i> 1. Target Audience</h2>
                                    </div>
                                    <div class="module-card-body">
                                        <div class="form-group">
                                            <label for="audienceType">Who should receive this alert?</label>
                                            <select id="audienceType" name="audience_type" onchange="toggleAudienceFilters()" class="form-control">
                                                <option value="all">All Registered Citizens</option>
                                                <option value="barangay">Specific Barangay</option>
                                                <option value="role">Specific Role (Citizen, Responder, Admin)</option>
                                                <option value="topic">Subscribed Topic Users</option>
                                            </select>
                                        </div>
                                        
                                        <div id="barangayFilter" class="form-group" style="display:none;">
                                            <label for="barangay">Select Barangay</label>
                                            <select id="barangay" name="barangay" style="width: 100%;">
                                                <option value="">Loading...</option>
                                            </select>
                                        </div>

                                        <div id="roleFilter" class="form-group" style="display:none;">
                                            <label for="role">Select Role</label>
                                            <select id="role" name="role" style="width: 100%;">
                                                <option value="citizen">Citizens Only</option>
                                                <option value="responder">Responders Only</option>
                                                <option value="admin">Administrators Only</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <!-- Dispatch Channels -->
                                <div class="module-card">
                                    <div class="module-card-header">
                                        <h2><i class="fas fa-share-alt"></i> 2. Dispatch Channels</h2>
                                    </div>
                                    <div class="module-card-body">
                                        <div class="channel-options">
                                            <label class="channel-checkbox" id="lbl-sms">
                                                <input type="checkbox" name="channels" value="sms" hidden onchange="this.parentElement.classList.toggle('selected', this.checked)">
                                                <i class="fas fa-sms"></i>
                                                <span>SMS</span>
                                            </label>
                                            <label class="channel-checkbox" id="lbl-email">
                                                <input type="checkbox" name="channels" value="email" hidden onchange="this.parentElement.classList.toggle('selected', this.checked)">
                                                <i class="fas fa-envelope"></i>
                                                <span>Email</span>
                                            </label>
                                            <label class="channel-checkbox" id="lbl-push">
                                                <input type="checkbox" name="channels" value="push" hidden onchange="this.parentElement.classList.toggle('selected', this.checked)">
                                                <i class="fas fa-mobile-alt"></i>
                                                <span>Push</span>
                                            </label>
                                            <label class="channel-checkbox" id="lbl-pa">
                                                <input type="checkbox" name="channels" value="pa" hidden onchange="this.parentElement.classList.toggle('selected', this.checked)">
                                                <i class="fas fa-bullhorn"></i>
                                                <span>PA System</span>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Right Column: Message -->
                            <div class="dispatch-right">
                                <div class="module-card">
                                    <div class="module-card-header">
                                        <h2><i class="fas fa-pen"></i> 3. Message Details</h2>
                                    </div>
                                    <div class="module-card-body">
                                        <div class="form-group">
                                            <label for="category_id">Alert Category *</label>
                                            <select id="category_id" name="category_id" style="width: 100%;" required>
                                                <option value="">Loading Categories...</option>
                                            </select>
                                        </div>

                                        <div class="form-group">
                                            <label for="template">Use Template (Optional)</label>
                                            <select id="template" name="template" onchange="applyTemplate(this.value)" style="width: 100%;">
                                                <option value="">-- Select a Template --</option>
                                            </select>
                                        </div>

                                        <div class="form-group">
                                            <label>Severity Level</label>
                                            <div class="severity-options">
                                                <label class="severity-radio low"><input type="radio" name="severity" value="Low" hidden onchange="updateSeverityUI(this)"> Low</label>
                                                <label class="severity-radio medium selected"><input type="radio" name="severity" value="Medium" hidden checked onchange="updateSeverityUI(this)"> Medium</label>
                                                <label class="severity-radio high"><input type="radio" name="severity" value="High" hidden onchange="updateSeverityUI(this)"> High</label>
                                                <label class="severity-radio critical"><input type="radio" name="severity" value="Critical" hidden onchange="updateSeverityUI(this)"> Critical</label>
                                            </div>
                                        </div>

                                        <div class="form-group">
                                            <label for="message_title">Title / Headline *</label>
                                            <input type="text" id="message_title" name="title" required placeholder="e.g., FLASH FLOOD WARNING">
                                        </div>

                                        <div class="form-group">
                                            <label for="message_body">Message Body *</label>
                                            <textarea id="message_body" name="body" rows="4" required onkeyup="updateCharCount(this)"></textarea>
                                            <div class="char-counter">
                                                <i class="fas fa-info-circle"></i> <span id="charCount">0</span> chars (approx. <span id="smsParts">0</span> SMS parts)
                                            </div>
                                        </div>

                                        <!-- Live Preview -->
                                        <div class="preview-box">
                                            <div class="preview-header">
                                                <span class="preview-label"><i class="fas fa-eye"></i> Live Preview</span>
                                                <div id="live-preview-pill" class="category-preview-pill" style="background: #95a5a6; display: none;">
                                                    <i id="preview-icon" class="fas fa-exclamation-triangle"></i>
                                                    <span id="preview-name">General</span>
                                                </div>
                                            </div>
                                            <div id="preview-content" class="preview-content">Enter a title to see preview...</div>
                                            <div id="preview-body" class="preview-body-text"></div>
                                        </div>

                                        <div style="margin-top: 1.5rem;">
                                            <button type="button" class="btn btn-primary" style="width: 100%; padding: 1rem; font-size: 1rem;" onclick="showPreview()">
                                                <i class="fas fa-paper-plane" style="margin-right: 0.5rem;"></i> Preview & Dispatch
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>

                    <!-- Recent Dispatch History -->
                    <div class="module-card">
                        <div class="module-card-header">
                            <h2><i class="fas fa-history"></i> Dispatch History <span id="alert-nav-badge" class="badge" style="display:none; margin-left: 10px;"></span></h2>
                            <button class="btn btn-sm btn-secondary" onclick="loadNotifications()"><i class="fas fa-sync-alt"></i> Refresh</button>
                        </div>
                        <div style="overflow-x: auto;">
                            <table class="data-table" id="notificationsTable">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Target</th>
                                        <th>Channels</th>
                                        <th>Message Preview</th>
                                        <th>Status</th>
                                        <th>Sent At</th>
                                        <th>Success Rate</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Preview Modal -->
    <div id="previewModal" class="modal" style="display: none;">
        <div class="modal-content" style="max-width: 500px;">
            <div class="modal-header">
                <h2>Confirm Dispatch</h2>
                <button class="modal-close" onclick="closeModal('previewModal')">&times;</button>
            </div>
            <div class="modal-body">
                <div style="background: #fff3e0; padding: 1rem; border-radius: 6px; border-left: 4px solid #ff9800; margin-bottom: 1rem; font-size: 0.9rem;">
                    <i class="fas fa-exclamation-triangle" style="color: #f57c00; margin-right: 0.5rem;"></i> 
                    <strong>Attention:</strong> This message will be sent immediately to the selected audience.
                </div>
                <table style="width: 100%; border-collapse: collapse; margin-bottom: 1rem;">
                    <tr><td style="padding: 0.5rem 0; color: var(--text-secondary-1); font-size: 0.9rem;">Audience:</td><td id="pvAudience" style="padding: 0.5rem 0; font-weight: 600; text-align: right;"></td></tr>
                    <tr><td style="padding: 0.5rem 0; color: var(--text-secondary-1); font-size: 0.9rem;">Channels:</td><td id="pvChannels" style="padding: 0.5rem 0; font-weight: 600; text-align: right;"></td></tr>
                    <tr><td style="padding: 0.5rem 0; color: var(--text-secondary-1); font-size: 0.9rem;">Severity:</td><td id="pvSeverity" style="padding: 0.5rem 0; font-weight: 600; text-align: right;"></td></tr>
                </table>
                <div style="background: var(--bg-color-1); padding: 1rem; border-radius: 8px; border: 1px solid var(--border-color-1);">
                    <div id="pvTitle" style="font-weight: 700; color: var(--text-color-1); margin-bottom: 0.5rem;"></div>
                    <div id="pvBody" style="font-size: 0.95rem; color: var(--text-secondary-1); line-height: 1.5;"></div>
                </div>
            </div>
            <div class="modal-footer" style="padding: 1.5rem; display: flex; gap: 1rem;">
                <button class="btn btn-secondary" onclick="closeModal('previewModal')" style="flex: 1;">Cancel</button>
                <button class="btn btn-primary" onclick="submitDispatch()" id="confirmDispatchBtn" style="flex: 2;">
                    <i class="fas fa-check-circle"></i> Confirm & Dispatch
                </button>
            </div>
        </div>
    </div>

    <script>
        let templatesData = [];
        let categoriesData = [];

        function toggleAudienceFilters() {
            const type = document.getElementById('audienceType').value;
            document.getElementById('barangayFilter').style.display = type === 'barangay' ? 'block' : 'none';
            document.getElementById('roleFilter').style.display = type === 'role' ? 'block' : 'none';
            
            if (type === 'topic') {
                // Focus user attention on the category dropdown which now serves as the topic filter
                $('#category_id').select2('open');
            }
        }

        function updateSeverityUI(radio) {
            document.querySelectorAll('.severity-radio').forEach(lbl => lbl.classList.remove('selected'));
            radio.parentElement.classList.add('selected');
        }

        function updateCharCount(textarea) {
            const len = textarea.value.length;
            document.getElementById('charCount').textContent = len;
            document.getElementById('smsParts').textContent = Math.ceil(len / 160);
            
            // Also update preview body text
            const previewBody = document.getElementById('preview-body');
            if (previewBody) {
                previewBody.textContent = textarea.value;
            }
        }

        function closeModal(id) {
            document.getElementById(id).style.display = 'none';
            document.body.style.overflow = '';
        }

        function loadOptions() {
            fetch('../api/mass-notification.php?action=get_options')
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        // Populate Barangays
                        const bSel = document.getElementById('barangay');
                        bSel.innerHTML = data.barangays.map(b => `<option value="${b}">${b}</option>`).join('');
                        
                        // Populate Categories with metadata
                        categoriesData = data.categories;
                        const cSel = document.getElementById('category_id');
                        cSel.innerHTML = '<option value="">-- Select Category --</option>' + 
                            data.categories.map(c => `<option value="${c.id}" data-icon="${c.icon}" data-color="${c.color}">${c.name}</option>`).join('');
                        
                        // Initialize Select2 for Categories
                        initCategorySelect();

                        // Populate Templates
                        const tSel = document.getElementById('template');
                        templatesData = data.templates;
                        tSel.innerHTML = '<option value="">-- Select a Template --</option>' + 
                            data.templates.map(t => `<option value="${t.id}">${t.title} (${t.severity})</option>`).join('');
                    }
                });
        }

        function initCategorySelect() {
            function formatCategory(state) {
                if (!state.id) return state.text;
                const icon = $(state.element).data('icon') || 'fa-tag';
                const color = $(state.element).data('color') || '#95a5a6';
                return $(`<span><i class="fas ${icon}" style="color:${color}; width: 20px; text-align: center; margin-right: 8px;"></i>${state.text}</span>`);
            }

            $('#category_id').select2({
                templateResult: formatCategory,
                templateSelection: formatCategory,
                placeholder: "-- Select Category --",
                allowClear: true
            }).on('change', updateLivePreview);
        }

        function updateLivePreview() {
            const catId = $('#category_id').val();
            const title = document.getElementById('message_title').value;
            const body = document.getElementById('message_body').value;
            const previewPill = document.getElementById('live-preview-pill');
            const previewName = document.getElementById('preview-name');
            const previewIcon = document.getElementById('preview-icon');
            const previewContent = document.getElementById('preview-content');
            const previewBody = document.getElementById('preview-body');

            if (catId) {
                const cat = categoriesData.find(c => c.id == catId);
                if (cat) {
                    previewPill.style.display = 'inline-flex';
                    previewPill.style.background = cat.color;
                    previewIcon.className = `fas ${cat.icon}`;
                    previewName.textContent = cat.name;
                }
            } else {
                previewPill.style.display = 'none';
            }

            previewContent.textContent = title || "Enter a title to see preview...";
            previewContent.style.opacity = title ? "1" : "0.5";
            
            if (previewBody) {
                previewBody.textContent = body;
            }
        }

        // Add listener for title input to update preview
        if (document.getElementById('message_title')) {
            document.getElementById('message_title').addEventListener('input', updateLivePreview);
        }

        function applyTemplate(id) {
            const t = templatesData.find(tpl => tpl.id == id);
            if (t) {
                document.getElementById('message_title').value = t.title;
                document.getElementById('message_body').value = t.body;
                
                // Update Select2
                $('#category_id').val(t.category_id).trigger('change');

                // Update Severity
                const sevRadio = document.querySelector(`input[name="severity"][value="${t.severity}"]`);
                if (sevRadio) {
                    sevRadio.checked = true;
                    updateSeverityUI(sevRadio);
                }
                updateCharCount(document.getElementById('message_body'));
                updateLivePreview();
            }
        }

        function showPreview() {
            const form = document.getElementById('dispatchForm');
            const formData = new FormData(form);
            
            // Collect checked channels manually to be safe
            let channels = [];
            $('input[name="channels"]:checked').each(function() {
                channels.push($(this).val());
            });

            if (channels.length === 0) {
                alert('Please select at least one channel.');
                return;
            }

            if (!formData.get('category_id')) {
                alert('Please select a category.');
                return;
            }

            document.getElementById('pvAudience').textContent = document.getElementById('audienceType').options[document.getElementById('audienceType').selectedIndex].text;
            document.getElementById('pvChannels').textContent = channels.join(', ').toUpperCase();
            document.getElementById('pvSeverity').textContent = formData.get('severity');
            document.getElementById('pvTitle').textContent = document.getElementById('message_title').value;
            document.getElementById('pvBody').textContent = document.getElementById('message_body').value;

            document.getElementById('previewModal').style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }

        function submitDispatch() {
            const btn = document.getElementById('confirmDispatchBtn');
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Queuing...';

            // Properly gather data
            let channels = [];
            $('input[name="channels"]:checked').each(function() {
                channels.push($(this).val());
            });

            const data = {
                audience_type: $('#audienceType').val(),
                barangay: $('#barangay').val(),
                role: $('#role').val(),
                category_id: $('#category_id').val(),
                channels: channels,
                severity: $('input[name="severity"]:checked').val(),
                title: $('#message_title').val().trim(),
                body: $('#message_body').val().trim()
            };

            // Debug log
            console.log('Dispatching Data:', data);

            // Convert to FormData for backend compatibility if it expects it, 
            // but the prompt says send as JSON or form data.
            // Using jQuery ajax for simple data object transmission as requested.
            $.ajax({
                url: '../api/send-broadcast.php',
                type: 'POST',
                data: data,
                dataType: 'json'
            })
            .done(function(data) {
                // Feature improvement: Close modal immediately before showing alert
                closeModal('previewModal');
                
                if (data.success) {
                    alert(data.message);
                    document.getElementById('dispatchForm').reset();
                    document.querySelectorAll('.channel-checkbox').forEach(c => c.classList.remove('selected'));
                    $('#category_id').val(null).trigger('change');
                    loadNotifications();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .fail(function(xhr) {
                console.error('Dispatch Error:', xhr.responseText);
                closeModal('previewModal');
                alert('Connection or Server Error. Please check console.');
            })
            .always(function() {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-check-circle"></i> Confirm & Dispatch';
            });
        }

        function loadNotifications() {
            fetch('../api/mass-notification.php?action=list')
                .then(r => r.json())
                .then(data => {
                    if (!data.success) return;
                    const tbody = document.querySelector('#notificationsTable tbody');
                    tbody.innerHTML = data.notifications.map(n => {
                        const progress = n.progress || 0;
                        const stats = n.stats || {sent: 0, failed: 0, total: 0};
                        return `
                            <tr>
                                <td>#${n.id}</td>
                                <td><small style="color: var(--text-secondary-1); font-weight: 500;">${n.recipients}</small></td>
                                <td>${n.channel.split(',').map(c => `<i class="fas fa-${getIcon(c)}" title="${c}" style="color: var(--text-secondary-1); margin-right: 4px;"></i>`).join(' ')}</td>
                                <td><div style="max-width:250px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; font-size: 0.9rem;">${n.message}</div></td>
                                <td>
                                    <span class="badge ${n.status}">${n.status.toUpperCase()}</span>
                                    <div class="progress-container" title="${progress}% sent"><div class="progress-bar" style="width: ${progress}%"></div></div>
                                </td>
                                <td><small style="color: var(--text-secondary-1);">${n.sent_at}</small></td>
                                <td>
                                    ${n.status === 'completed' ? `<strong>${Math.round((stats.sent/stats.total)*100)}%</strong> <br><small style="color: var(--text-secondary-1);">${stats.sent}/${stats.total}</small>` : '--'}
                                </td>
                            </tr>
                        `;
                    }).join('');
                });
        }

        function getIcon(channel) {
            switch(channel) {
                case 'sms': return 'sms';
                case 'email': return 'envelope';
                case 'push': return 'mobile-alt';
                case 'pa': return 'bullhorn';
                default: return 'broadcast-tower';
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            loadOptions();
            loadNotifications();
            // Poll for updates every 10 seconds
            setInterval(loadNotifications, 10000);
        });
    </script>
    <script src="../USERS/js/alert-listener.js"></script>
</body>
</html>