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
        .dispatch-form {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
        }
        .dispatch-section {
            background: var(--card-bg-1);
            padding: 1.5rem;
            border-radius: 8px;
            border: 1px solid var(--border-color-1);
        }
        .dispatch-section h3 {
            margin-bottom: 1.25rem;
            font-size: 1.1rem;
            border-bottom: 2px solid var(--primary-color-1);
            padding-bottom: 0.5rem;
            display: inline-block;
        }
        .channel-options {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
        }
        .channel-checkbox {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1rem;
            border: 1px solid var(--border-color-1);
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.2s;
        }
        .channel-checkbox:hover {
            background: #f8f9fa;
        }
        .channel-checkbox.selected {
            background: var(--primary-color-1);
            color: white;
            border-color: var(--primary-color-1);
        }
        .severity-options {
            display: flex;
            gap: 0.5rem;
        }
        .severity-radio {
            flex: 1;
            text-align: center;
            padding: 0.5rem;
            border: 1px solid var(--border-color-1);
            border-radius: 4px;
            cursor: pointer;
        }
        .severity-radio.low.selected { background: #4CAF50; color: white; border-color: #4CAF50; }
        .severity-radio.medium.selected { background: #2196F3; color: white; border-color: #2196F3; }
        .severity-radio.high.selected { background: #FF9800; color: white; border-color: #FF9800; }
        .severity-radio.critical.selected { background: #f44336; color: white; border-color: #f44336; }
        
        .char-counter {
            text-align: right;
            font-size: 0.8rem;
            color: #666;
            margin-top: 0.25rem;
        }
        .progress-container {
            width: 100%;
            background-color: #e0e0e0;
            border-radius: 4px;
            height: 10px;
            margin-top: 5px;
        }
        .progress-bar {
            height: 100%;
            background-color: var(--primary-color-1);
            border-radius: 4px;
            transition: width 0.3s;
        }

        /* Category Preview Styles */
        .preview-box {
            margin-top: 1rem;
            padding: 1rem;
            border: 2px dashed #ddd;
            border-radius: 8px;
            background: #fff;
        }
        .category-preview-pill {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            color: white;
            font-weight: 600;
            font-size: 0.9rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .select2-container--default .select2-selection--single {
            height: 45px;
            padding: 8px;
            border: 1px solid var(--border-color-1);
        }
        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 43px;
        }

        @media (max-width: 992px) {
            .dispatch-form { grid-template-columns: 1fr; }
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
                        <li class="breadcrumb-item"><a href="/" class="breadcrumb-link">Home</a></li>
                        <li class="breadcrumb-item">Emergency Communication</li>
                        <li class="breadcrumb-item active">Mass Notification</li>
                    </ol>
                </nav>
                <h1><i class="fas fa-broadcast-tower"></i> Unified Emergency Dispatch</h1>
                <p>Broadcast emergency alerts across multiple channels to targeted audiences.</p>
            </div>
            
            <div class="sub-container">
                <div class="page-content">
                    
                    <!-- Dispatch Console -->
                    <div class="module-card">
                        <div class="module-card-header">
                            <h2><i class="fas fa-paper-plane"></i> Dispatch Console</h2>
                        </div>
                        <form id="dispatchForm">
                            <div class="dispatch-form">
                                <!-- Target Section -->
                                <div class="dispatch-section">
                                    <h3>1. Target Audience</h3>
                                    <div class="form-group">
                                        <label for="audienceType">Audience Type</label>
                                        <select id="audienceType" name="audience_type" onchange="toggleAudienceFilters()">
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

                                    <h3 style="margin-top: 1.5rem;">2. Dispatch Channels</h3>
                                    <div class="channel-options">
                                        <label class="channel-checkbox" id="lbl-sms">
                                            <input type="checkbox" name="channels" value="sms" hidden onchange="this.parentElement.classList.toggle('selected', this.checked)">
                                            <i class="fas fa-sms"></i> SMS
                                        </label>
                                        <label class="channel-checkbox" id="lbl-email">
                                            <input type="checkbox" name="channels" value="email" hidden onchange="this.parentElement.classList.toggle('selected', this.checked)">
                                            <i class="fas fa-envelope"></i> Email
                                        </label>
                                        <label class="channel-checkbox" id="lbl-push">
                                            <input type="checkbox" name="channels" value="push" hidden onchange="this.parentElement.classList.toggle('selected', this.checked)">
                                            <i class="fas fa-mobile-alt"></i> Push
                                        </label>
                                        <label class="channel-checkbox" id="lbl-pa">
                                            <input type="checkbox" name="channels" value="pa" hidden onchange="this.parentElement.classList.toggle('selected', this.checked)">
                                            <i class="fas fa-bullhorn"></i> PA
                                        </label>
                                    </div>
                                </div>

                                <!-- Message Section -->
                                <div class="dispatch-section">
                                    <h3>3. Message Details</h3>
                                    
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
                                        <div class="char-counter"><span id="charCount">0</span> characters (approx. <span id="smsParts">0</span> SMS parts)</div>
                                    </div>

                                    <!-- Live Preview -->
                                    <div class="preview-box">
                                        <div style="font-size: 0.75rem; color: #999; margin-bottom: 0.5rem; text-transform: uppercase; font-weight: bold;">Live Preview</div>
                                        <div id="live-preview-pill" class="category-preview-pill" style="background: #95a5a6; display: none;">
                                            <i id="preview-icon" class="fas fa-exclamation-triangle"></i>
                                            <span id="preview-name">General</span>
                                        </div>
                                        <div id="preview-content" style="margin-top: 0.5rem; font-weight: bold; color: #333;">Enter a title to see preview...</div>
                                    </div>

                                    <div style="margin-top: 1rem;">
                                        <button type="button" class="btn btn-primary" style="width: 100%; padding: 1rem;" onclick="showPreview()">
                                            <i class="fas fa-eye"></i> Preview & Dispatch
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>

                    <!-- Recent Dispatch History -->
                    <div class="module-card">
                        <div class="module-card-header" style="display: flex; justify-content: space-between; align-items: center;">
                            <h2><i class="fas fa-history"></i> Dispatch History <span id="alert-nav-badge" class="badge" style="display:none; margin-left: 10px;"></span></h2>
                            <button class="btn btn-sm btn-secondary" onclick="loadNotifications()"><i class="fas fa-sync-alt"></i> Refresh</button>
                        </div>
                        <div>
                            <table class="data-table" id="notificationsTable">
                                <thead>
                                    <tr>
                                        <th>Log ID</th>
                                        <th>Target</th>
                                        <th>Channels</th>
                                        <th>Message</th>
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
                <div style="background: #fff3e0; padding: 1rem; border-radius: 6px; border-left: 4px solid #ff9800; margin-bottom: 1rem;">
                    <strong>Notice:</strong> This message will be queued for background delivery to the selected audience. This action is irreversible.
                </div>
                <table style="width: 100%; border-collapse: collapse;">
                    <tr><td style="padding: 0.5rem; color: #666;">Audience:</td><td id="pvAudience" style="padding: 0.5rem; font-weight: 600;"></td></tr>
                    <tr><td style="padding: 0.5rem; color: #666;">Channels:</td><td id="pvChannels" style="padding: 0.5rem; font-weight: 600;"></td></tr>
                    <tr><td style="padding: 0.5rem; color: #666;">Severity:</td><td id="pvSeverity" style="padding: 0.5rem; font-weight: 600;"></td></tr>
                    <tr><td style="padding: 0.5rem; color: #666;">Title:</td><td id="pvTitle" style="padding: 0.5rem; font-weight: 600;"></td></tr>
                </table>
                <div style="margin-top: 1rem; padding: 1rem; background: #f8f9fa; border-radius: 4px; font-style: italic;" id="pvBody"></div>
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
            const previewPill = document.getElementById('live-preview-pill');
            const previewName = document.getElementById('preview-name');
            const previewIcon = document.getElementById('preview-icon');
            const previewContent = document.getElementById('preview-content');

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
                                <td><small>${n.recipients}</small></td>
                                <td>${n.channel.split(',').map(c => `<i class="fas fa-${getIcon(c)}" title="${c}"></i>`).join(' ')}</td>
                                <td><div style="max-width:250px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">${n.message}</div></td>
                                <td>
                                    <span class="badge ${n.status}">${n.status.toUpperCase()}</span>
                                    <div class="progress-container"><div class="progress-bar" style="width: ${progress}%"></div></div>
                                </td>
                                <td><small>${n.sent_at}</small></td>
                                <td>
                                    ${n.status === 'completed' ? `<strong>${Math.round((stats.sent/stats.total)*100)}%</strong> <br><small>${stats.sent}/${stats.total}</small>` : '--'}
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