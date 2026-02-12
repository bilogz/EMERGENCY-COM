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
    <!-- Emergency Alert System -->
    <link rel="stylesheet" href="../header/css/emergency-alert.css">
    <!-- Select2 for Rich Dropdowns -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <!-- Leaflet (Map Picker) -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
</head>
<body class="mn-page">
        <link rel="stylesheet" href="css/module-mass-notification.css?v=<?php echo filemtime(__DIR__ . '/css/module-mass-notification.css'); ?>">

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
                    <div class="mn-mini-analytics" aria-label="Mass notification analytics">
                        <div class="mn-stat mn-stat--total">
                            <div class="mn-stat-top">
                                <div class="mn-stat-label">Total Dispatches</div>
                                <div class="mn-stat-icon" aria-hidden="true"><i class="fas fa-paper-plane"></i></div>
                            </div>
                            <div class="mn-stat-value"><span id="mnTotalDispatches">-</span></div>
                            <div class="mn-stat-sub">Loaded from dispatch history</div>
                        </div>
                        <div class="mn-stat mn-stat--done">
                            <div class="mn-stat-top">
                                <div class="mn-stat-label">Completed</div>
                                <div class="mn-stat-icon" aria-hidden="true"><i class="fas fa-circle-check"></i></div>
                            </div>
                            <div class="mn-stat-value"><span id="mnCompletedDispatches">-</span></div>
                            <div class="mn-stat-sub">Successful sends</div>
                        </div>
                        <div class="mn-stat mn-stat--progress">
                            <div class="mn-stat-top">
                                <div class="mn-stat-label">In Progress</div>
                                <div class="mn-stat-icon" aria-hidden="true"><i class="fas fa-rotate"></i></div>
                            </div>
                            <div class="mn-stat-value"><span id="mnInProgressDispatches">-</span></div>
                            <div class="mn-stat-sub">Sending / queued</div>
                        </div>
                        <div class="mn-stat mn-stat--rate">
                            <div class="mn-stat-top">
                                <div class="mn-stat-label">Success Rate</div>
                                <div class="mn-stat-icon" aria-hidden="true"><i class="fas fa-chart-line"></i></div>
                            </div>
                            <div class="mn-stat-value"><span id="mnSuccessRate">-</span><span style="font-size:0.95rem; color: var(--text-secondary-1); font-weight:800;">%</span></div>
                            <div class="mn-stat-sub" id="mnSuccessRateSub">Based on completed</div>
                        </div>
                    </div>

                    <div class="mn-process" aria-label="How mass notification works">
                        <div class="mn-process-title">How Mass Notification Works</div>
                        <div class="mn-process-track">
                            <div class="mn-process-step">
                                <div class="mn-process-icon" aria-hidden="true"><i class="fas fa-paper-plane"></i></div>
                                <h4>Admin Sends Alert</h4>
                                <p>Choose target, channels, category, and severity (QC-ready).</p>
                            </div>
                            <div class="mn-process-arrow" aria-hidden="true"><i class="fas fa-arrow-right"></i></div>
                            <div class="mn-process-step">
                                <div class="mn-process-icon" aria-hidden="true"><i class="fas fa-tower-broadcast"></i></div>
                                <h4>System Dispatches</h4>
                                <p>Queues and sends via SMS / Email / Push / PA with live preview.</p>
                            </div>
                            <div class="mn-process-arrow" aria-hidden="true"><i class="fas fa-arrow-right"></i></div>
                            <div class="mn-process-step">
                                <div class="mn-process-icon" aria-hidden="true"><i class="fas fa-mobile-screen-button"></i></div>
                                <h4>Citizens Receive</h4>
                                <p>Residents see the alert on their preferred channels and language.</p>
                            </div>
                        </div>
                    </div>

                    <div class="mn-cta" aria-label="Start dispatch wizard">
                        <div>
                            <div class="mn-cta-title">Create a New Alert (Recommended)</div>
                            <div class="mn-cta-sub">Use the guided wizard to avoid mistakes. Drafts auto-save if you refresh or your browser crashes.</div>
                        </div>
                        <button type="button" class="btn btn-primary" onclick="openDispatchWizard()">
                            <i class="fas fa-wand-magic" style="margin-right: 0.5rem;"></i> Start Dispatch Wizard
                        </button>
                    </div>

                    <div class="mn-templates" aria-label="Starter templates">
                        <div class="mn-templates-head">
                            <div class="mn-templates-title"><i class="fas fa-bolt" aria-hidden="true"></i> Starter Templates</div>
                            <div class="mn-stat-sub">Click “Use Template” to auto-fill the wizard. You can still edit before sending.</div>
                        </div>
                        <div class="mn-template-grid">
                            <div class="mn-template">
                                <div class="mn-template-name"><i class="fas fa-cloud-rain"></i> Weather Signal Alert</div>
                                <div class="mn-template-desc">Sets a Weather category + Signal 1–5, then generates a QC-ready draft message.</div>
                                <button type="button" class="btn btn-secondary btn-sm" onclick="mnApplyStarterTemplate('weather_signal')">Use Template</button>
                            </div>
                            <div class="mn-template">
                                <div class="mn-template-name"><i class="fas fa-fire"></i> Fire Alert Level</div>
                                <div class="mn-template-desc">Sets a Fire category + Level 1–3, then generates a QC-ready draft message.</div>
                                <button type="button" class="btn btn-secondary btn-sm" onclick="mnApplyStarterTemplate('fire_level')">Use Template</button>
                            </div>
                            <div class="mn-template">
                                <div class="mn-template-name"><i class="fas fa-bullhorn"></i> General Advisory</div>
                                <div class="mn-template-desc">Quick general advisory for any category (keeps wording clean and action-focused).</div>
                                <button type="button" class="btn btn-secondary btn-sm" onclick="mnApplyStarterTemplate('general')">Use Template</button>
                            </div>
                        </div>
                    </div>

                    <div class="ui-actions" style="justify-content: flex-end; margin-bottom: 1.25rem;">
                        <button type="button" class="btn btn-secondary" onclick="openDispatchWizard()">
                            <i class="fas fa-wand-magic" style="margin-right: 0.5rem;"></i> Open Wizard
                        </button>
                    </div>
                    
                    <div id="mnFormHost" style="display:none;">
                    <form id="dispatchForm" data-draft-key="admin-mn-dispatch" data-draft-status="#mnDraftStatus">
                        <div class="dispatch-grid">
                            <!-- Left Column: Settings -->
                            <div class="dispatch-left">
                                <!-- Target Audience -->
                                <div class="module-card" id="mnCardTarget" data-mn-step="1">
                                    <div class="module-card-header">
                                        <h2><i class="fas fa-users"></i> 1. Target Audience</h2>
                                    </div>
                                    <div class="module-card-body">
                                        <div class="form-group">
                                            <label for="audienceType">Who should receive this alert?</label>
                                            <select id="audienceType" name="audience_type" onchange="toggleAudienceFilters()" class="form-control">
                                                <option value="all">All Registered Citizens</option>
                                                <option value="barangay">Specific Barangay</option>
                                                <option value="location">Specific Location (Map)</option>
                                                <option value="role">Specific Role (Citizen, Responder, Admin)</option>
                                                <option value="topic">Subscribed Topic Users</option>
                                            </select>
                                            <div class="mn-help">Choose <strong>All</strong> for the fastest dispatch, or narrow to a Barangay/Role. “Topic” uses the category subscription.</div>
                                        </div>
                                        
                                        <div id="barangayFilter" class="form-group" style="display:none;">
                                            <label for="barangay">Select Barangay</label>
                                            <select id="barangay" name="barangay" style="width: 100%;">
                                                <option value="">Loading...</option>
                                            </select>
                                            <div style="display:flex; gap:0.6rem; align-items:center; margin-top:0.6rem; flex-wrap:wrap;">
                                                <button type="button" class="btn ui-btn-ghost" onclick="mnOpenMapPicker('barangay')">
                                                    <i class="fas fa-map-marker-alt"></i> Pick on Map
                                                </button>
                                                <small id="mnBarangayCoordsHint" style="color: var(--text-secondary-1);"></small>
                                            </div>
                                            <div class="mn-help">Only citizens in this barangay will receive the alert.</div>
                                        </div>

                                        <div id="locationFilter" class="form-group" style="display:none;">
                                            <label>Choose Location (Quezon City)</label>
                                            <div style="display:flex; gap:0.6rem; align-items:center; flex-wrap:wrap;">
                                                <button type="button" class="btn btn-secondary" onclick="mnOpenMapPicker('location')">
                                                    <i class="fas fa-map"></i> Open Map
                                                </button>
                                                <div style="display:flex; align-items:center; gap:0.4rem;">
                                                    <span style="color: var(--text-secondary-1); font-size: 0.9rem;">Radius</span>
                                                    <input id="mnRadiusM" name="radius_m" class="form-control" type="number" min="100" max="20000" step="50" value="1000" style="width: 140px;">
                                                    <span style="color: var(--text-secondary-1); font-size: 0.9rem;">meters</span>
                                                </div>
                                            </div>
                                            <input type="hidden" id="mnTargetLat" name="target_lat" value="">
                                            <input type="hidden" id="mnTargetLng" name="target_lng" value="">
                                            <input type="hidden" id="mnTargetAddress" name="target_address" value="">
                                            <div class="mn-help" style="margin-top:0.6rem;">
                                                Selected point: <strong id="mnTargetLabel">None</strong>
                                            </div>
                                            <div class="mn-help" id="mnTargetCoords" style="margin-top:0.15rem; color: var(--text-secondary-1);"></div>
                                            <div class="mn-help" id="mnTargetAddrText" style="margin-top:0.15rem; color: var(--text-secondary-1);"></div>
                                            <div style="display:flex; gap:0.6rem; align-items:center; margin-top:0.6rem; flex-wrap:wrap;">
                                                <input id="mnTargetAddressText" class="form-control" type="text" placeholder="Location details / address (auto-filled, editable)" style="flex:1; min-width: 260px;">
                                                <button type="button" class="btn ui-btn-ghost" id="mnLookupAddressBtn" onclick="mnLookupAddressFromWizard()">
                                                    <i class="fas fa-location-crosshairs"></i> Lookup
                                                </button>
                                            </div>
                                            <div class="mn-help">This targets citizens whose latest known location is within the radius.</div>
                                        </div>

                                        <div id="roleFilter" class="form-group" style="display:none;">
                                            <label for="role">Select Role</label>
                                            <select id="role" name="role" style="width: 100%;">
                                                <option value="citizen">Citizens Only</option>
                                                <option value="responder">Responders Only</option>
                                                <option value="admin">Administrators Only</option>
                                            </select>
                                            <div class="mn-help">Use this for internal announcements (e.g., responders only).</div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Dispatch Channels -->
                                <div class="module-card" id="mnCardChannels" data-mn-step="2">
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
                                        <div class="mn-preview-hint" id="mnChannelsHint">
                                            Pick at least one channel. Tip: SMS is best for urgent short messages; Email is best for detailed instructions.
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Right Column: Message -->
                            <div class="dispatch-right">
                                <div class="module-card" id="mnCardMessage" data-mn-step="3">
                                    <div class="module-card-header">
                                        <h2><i class="fas fa-pen"></i> 3. Message Details</h2>
                                    </div>
                                    <div class="module-card-body">
                                        <div class="form-group">
                                            <label for="category_id">Alert Category *</label>
                                            <select id="category_id" name="category_id" style="width: 100%;" required>
                                                <option value="">Loading Categories...</option>
                                            </select>
                                            <div class="mn-help">Category controls icons/colors in citizen feeds and determines “Topic” subscriptions.</div>
                                        </div>

                                        <div class="form-group">
                                            <label for="template">Use Template (Optional)</label>
                                            <select id="template" name="template" onchange="applyTemplate(this.value)" style="width: 100%;">
                                                <option value="">-- Select a Template --</option>
                                            </select>
                                        </div>

                                        <div class="form-group" id="mnFireLevelWrap" style="display:none;">
                                            <label for="mnFireLevel">Fire Alert Level (1â€“3)</label>
                                            <select id="mnFireLevel" name="fire_level" class="form-control">
                                                <option value="1">Level 1</option>
                                                <option value="2">Level 2</option>
                                                <option value="3">Level 3</option>
                                            </select>
                                            <div class="mn-help">Shown for fire categories. You can adjust the alert level before generating a draft.</div>
                                        </div>

                                        <div class="form-group">
                                            <div style="display:flex; align-items:center; justify-content:space-between; gap:0.75rem; flex-wrap:wrap;">
                                                <label style="margin:0;">AI Assisted Draft</label>
                                                <button type="button" class="btn btn-secondary btn-sm" id="mnAiAssistBtn" onclick="mnAiAssist()">
                                                    <svg class="gemini-logo" viewBox="0 0 100 100" aria-hidden="true" focusable="false">
                                                        <defs>
                                                            <linearGradient id="geminiGradient" x1="0%" y1="50%" x2="100%" y2="50%">
                                                                <stop offset="0%" stop-color="#F5C542"/>
                                                                <stop offset="35%" stop-color="#34A853"/>
                                                                <stop offset="70%" stop-color="#4285F4"/>
                                                                <stop offset="100%" stop-color="#EA4335"/>
                                                            </linearGradient>
                                                        </defs>
                                                        <path fill="url(#geminiGradient)" d="M50 6 C54 24 62 34 94 50 C62 66 54 76 50 94 C46 76 38 66 6 50 C38 34 46 24 50 6 Z"/>
                                                    </svg>
                                                    Suggest Message
                                                    <span class="mn-ai-spinner" aria-hidden="true"></span>
                                                </button>
                                            </div>
                                            <div class="mn-help">Generates a suggested title and message based on the selected category + severity (you can edit before sending).</div>
                                        </div>

                                        <div class="form-group">
                                            <label>Severity Level</label>
                                            <div class="severity-options">
                                                <label class="severity-radio low"><input type="radio" name="severity" value="Low" hidden onchange="updateSeverityUI(this)"> Low</label>
                                                <label class="severity-radio medium selected"><input type="radio" name="severity" value="Medium" hidden checked onchange="updateSeverityUI(this)"> Medium</label>
                                                <label class="severity-radio high"><input type="radio" name="severity" value="High" hidden onchange="updateSeverityUI(this)"> High</label>
                                                <label class="severity-radio critical"><input type="radio" name="severity" value="Critical" hidden onchange="updateSeverityUI(this)"> Critical</label>
                                            </div>
                                            <div class="mn-help">Severity affects how prominently the alert appears to users.</div>
                                        </div>

                                        <div class="form-group" id="mnWeatherSignalWrap" style="display:none;">
                                            <label for="mnWeatherSignal">Weather Signal (1–5)</label>
                                            <select id="mnWeatherSignal" name="weather_signal" class="form-control">
                                                <option value="1">Signal 1</option>
                                                <option value="2">Signal 2</option>
                                                <option value="3">Signal 3</option>
                                                <option value="4">Signal 4</option>
                                                <option value="5">Signal 5</option>
                                            </select>
                                            <div class="mn-help">Shown for weather/typhoon categories. You can adjust the signal level before generating a draft.</div>
                                        </div>

                                        <div class="form-group">
                                            <label for="message_title">Title / Headline *</label>
                                            <input type="text" id="message_title" name="title" required placeholder="e.g., FLASH FLOOD WARNING">
                                            <div class="mn-help">Keep it short and action-focused (e.g., “Evacuate now”).</div>
                                        </div>

                                        <div class="form-group">
                                            <label for="message_body">Message Body *</label>
                                            <textarea id="message_body" name="body" rows="4" required onkeyup="updateCharCount(this)"></textarea>
                                            <div class="char-counter">
                                                <i class="fas fa-info-circle"></i> <span id="charCount">0</span> chars (approx. <span id="smsParts">0</span> SMS parts)
                                            </div>
                                            <div class="mn-help">Include <strong>what</strong>, <strong>where</strong>, and <strong>what to do next</strong>. Avoid jargon.</div>
                                        </div>

                                        <div class="mn-preview-hint" style="margin-top: 1.25rem;">
                                            Live preview is shown on the right side of the wizard while you fill in the fields.
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                    </div>

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

    <!-- Dispatch Wizard Modal -->
    <div class="mn-modal-backdrop" id="mnDispatchWizardBackdrop" aria-hidden="true">
        <div class="mn-modal" role="dialog" aria-modal="true" aria-label="Dispatch wizard">
            <div class="mn-modal-header">
                <div>
                    <h2 class="mn-modal-title"><i class="fas fa-paper-plane" style="margin-right: 0.5rem; color: var(--primary-color-1);"></i> Dispatch Wizard</h2>
                    <div class="mn-modal-subtitle">Follow the steps to send a complete alert.</div>
                </div>
                <button class="mn-modal-close" type="button" onclick="closeDispatchWizard()" aria-label="Close wizard">&times;</button>
            </div>
            <div class="mn-modal-body">
                <div class="mn-modal-grid">
                    <div class="mn-modal-left">
                        <div class="mn-stepper" aria-label="Wizard steps">
                            <div class="mn-step is-active" id="mnWStep1"><span class="mn-step-num">1</span> Target</div>
                            <div class="mn-step-sep" aria-hidden="true"></div>
                            <div class="mn-step" id="mnWStep2"><span class="mn-step-num">2</span> Channels</div>
                            <div class="mn-step-sep" aria-hidden="true"></div>
                            <div class="mn-step" id="mnWStep3"><span class="mn-step-num">3</span> Message</div>
                        </div>
                        <div id="mnWizardHost"></div>
                    </div>

                    <aside class="mn-modal-right" aria-label="Live preview">
                        <div class="preview-box" style="margin-top: 0;">
                            <div class="preview-header">
                                <span class="preview-label"><i class="fas fa-eye"></i> Live Preview</span>
                                <div class="mn-preview-modes" aria-label="Preview mode">
                                    <button class="mn-preview-mode is-active" type="button" data-mode="sms">SMS</button>
                                    <button class="mn-preview-mode" type="button" data-mode="email">Email</button>
                                    <button class="mn-preview-mode" type="button" data-mode="push">Push</button>
                                    <button class="mn-preview-mode" type="button" data-mode="pa">PA</button>
                                </div>
                                <div id="live-preview-pill" class="category-preview-pill category-preview-pill--default" style="display: none;">
                                    <i id="preview-icon" class="fas fa-exclamation-triangle"></i>
                                    <span id="preview-name">General</span>
                                </div>
                            </div>
                            <div id="preview-content" class="preview-content">Enter a title to see preview...</div>
                            <div id="preview-body" class="preview-body-text"></div>
                            <div class="mn-help" id="mnPreviewFooter"></div>
                        </div>

                        <div class="mn-cta" style="margin-top: 1rem;">
                            <button type="button" class="btn btn-primary" id="mnPreviewDispatchBtn" style="width: 100%; padding: 1rem; font-size: 1rem;" onclick="showPreview()" disabled>
                                <i class="fas fa-paper-plane" style="margin-right: 0.5rem;"></i> Preview & Dispatch
                            </button>
                            <div class="mn-cta-reason is-visible" id="mnDispatchReason">Select a category, choose at least 1 channel, then add a title and message.</div>
                        </div>
                    </aside>
                </div>
            </div>
            <div class="mn-modal-footer">
                <div style="display:flex; gap:0.6rem; align-items:center; flex-wrap:wrap;">
                    <button type="button" class="btn btn-secondary" id="mnWizardBackBtn" onclick="mnWizardPrev()" disabled>Back</button>
                    <button type="button" class="btn ui-btn-ghost" onclick="closeDispatchWizard()">Close</button>
                    <span class="mn-draft-status" id="mnDraftStatus" aria-live="polite"></span>
                </div>
                <div style="display:flex; gap:0.6rem; align-items:center; flex-wrap:wrap;">
                    <button type="button" class="btn btn-primary" id="mnWizardNextBtn" onclick="mnWizardNext()">Next</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Confirm Modal -->
    <div class="mn-confirm-backdrop" id="mnConfirmBackdrop" aria-hidden="true">
        <div class="mn-confirm" role="dialog" aria-modal="true" aria-label="Confirm action">
            <div class="mn-confirm-header">
                <h3 class="mn-confirm-title" id="mnConfirmTitle">Confirm</h3>
                <button class="mn-confirm-close" type="button" onclick="mnCloseConfirm()">&times;</button>
            </div>
            <div class="mn-confirm-body" id="mnConfirmMessage">Are you sure?</div>
            <div class="mn-confirm-actions">
                <button type="button" class="btn btn-secondary" id="mnConfirmCancelBtn" onclick="mnCloseConfirm()">Cancel</button>
                <button type="button" class="btn btn-primary" id="mnConfirmOkBtn">OK</button>
            </div>
        </div>
    </div>

    <!-- Preview Modal -->
    <div id="previewModal" class="modal" aria-hidden="true">
        <div class="modal-content" style="max-width: 500px;">
            <div class="modal-header">
                <h2>Confirm Dispatch</h2>
                <button class="modal-close" type="button" onclick="mnCloseModal('previewModal')">&times;</button>
            </div>
            <div class="modal-body">
                <div style="background: color-mix(in srgb, var(--primary-color-1) 12%, transparent); padding: 1rem; border-radius: 10px; border-left: 4px solid var(--primary-color-1); margin-bottom: 1rem; font-size: 0.9rem; color: var(--text-color-1);">
                    <i class="fas fa-exclamation-triangle" style="color: var(--primary-color-1); margin-right: 0.5rem;"></i> 
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
                <button class="btn btn-secondary" type="button" onclick="mnCloseModal('previewModal')" style="flex: 1;">Cancel</button>
                <button class="btn btn-primary" type="button" onclick="submitDispatch()" id="confirmDispatchBtn" style="flex: 2;">
                    <i class="fas fa-check-circle"></i> Confirm & Dispatch
                </button>
            </div>
        </div>
    </div>

    <!-- Map Picker Modal -->
    <div id="mnMapModal" class="modal mn-map-modal" aria-hidden="true">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-map-marker-alt" style="color: var(--primary-color-1);"></i> Select Location</h2>
                <button class="modal-close" type="button" onclick="mnCloseModal('mnMapModal')">&times;</button>
            </div>
            <div class="modal-body">
                <div class="mn-help" style="margin-bottom: 0.75rem;">Search within <strong>Quezon City</strong>, or click on the map to drop a pin. Latitude/Longitude will be saved to your dispatch target.</div>
                <div class="mn-map-shell">
                    <div>
                        <div id="mnMap" aria-label="Map"></div>
                    </div>
                    <div>
                        <div class="form-group">
                            <label for="mnMapSearch">Search (QC)</label>
                            <div style="display:flex; gap:0.5rem; align-items:center;">
                                <input id="mnMapSearch" class="form-control" type="text" placeholder="e.g., Quezon Memorial Circle">
                                <button type="button" class="btn btn-primary" onclick="mnMapDoSearch()"><i class="fas fa-search"></i></button>
                            </div>
                            <div class="mn-help">Tip: You can type a landmark, street, or barangay name.</div>
                        </div>

                        <div class="form-group">
                            <label>Results</label>
                            <div id="mnMapResults" class="mn-map-results"></div>
                        </div>

                        <div class="form-group">
                            <label>Selected Coordinates</label>
                            <div class="mn-coords">
                                <div>Lat: <strong id="mnMapLat">--</strong></div>
                                <div>Lng: <strong id="mnMapLng">--</strong></div>
                            </div>
                            <div class="mn-help" id="mnMapSelectedLabel" style="margin-top:0.35rem;"></div>
                            <div class="mn-help" id="mnMapSelectedAddress" style="margin-top:0.15rem;"></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer" style="padding: 1.25rem; display:flex; gap:0.75rem; justify-content:flex-end;">
                <button type="button" class="btn btn-secondary" onclick="mnCloseModal('mnMapModal')">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="mnMapApplySelection()"><i class="fas fa-check"></i> Use This Location</button>
            </div>
        </div>
    </div>

    <script>
        let templatesData = [];
        let categoriesData = [];
        let previewMode = 'sms';
        let mnWizardStep = 1;
        let mnDispatchFormHome = null;
        let mnPendingDispatchPayload = null;
        let mnMap = null;
        let mnMapMarker = null;
        let mnMapRadiusCircle = null;
        let mnMapTargetMode = 'location'; // 'location' | 'barangay'
        let mnMapSelected = { lat: null, lng: null, label: '', address: '' };
        let mnQcGeojson = null;
        let mnQcLayer = null;
        let mnQcBounds = null;
        let mnReverseGeocodeTimer = null;
        let mnReverseGeocodeSeq = 0;

        function openDispatchWizard() {
            const backdrop = document.getElementById('mnDispatchWizardBackdrop');
            const host = document.getElementById('mnWizardHost');
            const form = document.getElementById('dispatchForm');
            const home = document.getElementById('mnFormHost');

            if (!backdrop || !host || !form || !home) return;

            mnDispatchFormHome = home;
            host.appendChild(form);
            form.classList.add('mn-in-wizard');

            backdrop.classList.add('show');
            backdrop.setAttribute('aria-hidden', 'false');
            document.body.classList.add('ui-modal-open');

            mnWizardGoTo(1);
            setTimeout(() => document.getElementById('audienceType')?.focus(), 0);
        }

        function closeDispatchWizard() {
            const backdrop = document.getElementById('mnDispatchWizardBackdrop');
            const form = document.getElementById('dispatchForm');
            if (backdrop) {
                backdrop.classList.remove('show');
                backdrop.setAttribute('aria-hidden', 'true');
            }
            document.body.classList.remove('ui-modal-open');
            // Release scroll lock if no other modals are open
            try {
                const anyModalOpen = !!document.querySelector('.modal.show') || !!document.getElementById('mnConfirmBackdrop')?.classList?.contains('show');
                if (!anyModalOpen) document.body.style.overflow = '';
            } catch (e) {}

            if (form && mnDispatchFormHome) {
                form.classList.remove('mn-in-wizard');
                // restore all cards visible on page
                document.querySelectorAll('#dispatchForm .module-card').forEach(c => c.classList.remove('mn-step-active'));
                mnDispatchFormHome.appendChild(form);
            }
        }

        function mnWizardGoTo(step) {
            mnWizardStep = step;
            const form = document.getElementById('dispatchForm');
            if (!form) return;

            // show only selected card
            document.querySelectorAll('#dispatchForm .module-card').forEach(c => c.classList.remove('mn-step-active'));
            if (step === 1) document.getElementById('mnCardTarget')?.classList.add('mn-step-active');
            if (step === 2) document.getElementById('mnCardChannels')?.classList.add('mn-step-active');
            if (step === 3) document.getElementById('mnCardMessage')?.classList.add('mn-step-active');

            const backBtn = document.getElementById('mnWizardBackBtn');
            const nextBtn = document.getElementById('mnWizardNextBtn');
            if (backBtn) backBtn.disabled = step === 1;
            if (nextBtn) nextBtn.textContent = step === 3 ? 'Finish' : 'Next';

            document.getElementById('mnWStep1')?.classList.toggle('is-active', step === 1);
            document.getElementById('mnWStep2')?.classList.toggle('is-active', step === 2);
            document.getElementById('mnWStep3')?.classList.toggle('is-active', step === 3);

            // done states based on current form
            const channels = getSelectedChannels();
            const title = document.getElementById('message_title')?.value?.trim() || '';
            const body = document.getElementById('message_body')?.value?.trim() || '';
            const catId = $('#category_id').val();
            document.getElementById('mnWStep1')?.classList.toggle('is-done', true);
            document.getElementById('mnWStep2')?.classList.toggle('is-done', channels.length > 0);
            document.getElementById('mnWStep3')?.classList.toggle('is-done', !!catId && !!title && !!body);

            if (step === 1) document.getElementById('audienceType')?.focus();
            if (step === 2) document.getElementById('lbl-sms')?.scrollIntoView({behavior:'smooth', block:'center'});
            if (step === 3) document.getElementById('message_title')?.focus();
        }

        function mnWizardNext() {
            if (mnWizardStep === 1) return mnWizardGoTo(2);
            if (mnWizardStep === 2) {
                if (getSelectedChannels().length === 0) {
                    alert('Please select at least one channel.');
                    return;
                }
                return mnWizardGoTo(3);
            }
            // step 3 finish: open preview/confirm modal (non-tech friendly)
            showPreview();
        }

        function mnWizardPrev() {
            if (mnWizardStep > 1) mnWizardGoTo(mnWizardStep - 1);
        }

        function setPreviewMode(mode) {
            previewMode = mode;
            document.querySelectorAll('.mn-preview-mode').forEach(btn => {
                btn.classList.toggle('is-active', btn.dataset.mode === mode);
            });
            updateLivePreview();
        }

        function toggleAudienceFilters() {
            const type = document.getElementById('audienceType').value;
            document.getElementById('barangayFilter').style.display = type === 'barangay' ? 'block' : 'none';
            document.getElementById('roleFilter').style.display = type === 'role' ? 'block' : 'none';
            document.getElementById('locationFilter').style.display = type === 'location' ? 'block' : 'none';
            
            if (type === 'topic') {
                // Focus user attention on the category dropdown which now serves as the topic filter
                $('#category_id').select2('open');
            }
        }

        function updateSeverityUI(radio) {
            document.querySelectorAll('.severity-radio').forEach(lbl => lbl.classList.remove('selected'));
            radio.parentElement.classList.add('selected');
            mnSyncWeatherSignalFromSeverity();
            mnRefreshAutoDraftFromContext();
            updateDispatchCTAState();
        }

        function mnShowConfirm(message, onOk, titleText = 'Confirm') {
            const backdrop = document.getElementById('mnConfirmBackdrop');
            const msg = document.getElementById('mnConfirmMessage');
            const title = document.getElementById('mnConfirmTitle');
            const okBtn = document.getElementById('mnConfirmOkBtn');
            const cancelBtn = document.getElementById('mnConfirmCancelBtn');
            if (!backdrop || !msg || !okBtn || !title) return;

            title.textContent = titleText;
            msg.textContent = message;
            okBtn.onclick = () => {
                mnCloseConfirm();
                if (typeof onOk === 'function') onOk();
            };
            if (cancelBtn) {
                cancelBtn.style.display = '';
                cancelBtn.textContent = 'Cancel';
            }
            okBtn.textContent = 'OK';

            backdrop.classList.add('show');
            backdrop.setAttribute('aria-hidden', 'false');
        }

        function mnShowNotice(message, titleText = 'Notice') {
            const backdrop = document.getElementById('mnConfirmBackdrop');
            const msg = document.getElementById('mnConfirmMessage');
            const title = document.getElementById('mnConfirmTitle');
            const okBtn = document.getElementById('mnConfirmOkBtn');
            const cancelBtn = document.getElementById('mnConfirmCancelBtn');
            if (!backdrop || !msg || !okBtn || !title) return;

            title.textContent = titleText;
            msg.textContent = message;
            okBtn.onclick = () => mnCloseConfirm();
            okBtn.textContent = 'OK';
            if (cancelBtn) cancelBtn.style.display = 'none';

            backdrop.classList.add('show');
            backdrop.setAttribute('aria-hidden', 'false');
        }

        function mnCloseConfirm() {
            const backdrop = document.getElementById('mnConfirmBackdrop');
            if (!backdrop) return;
            backdrop.classList.remove('show');
            backdrop.setAttribute('aria-hidden', 'true');
        }

        async function mnAiSuggestFromServer(ctx) {
            try {
                const res = await fetch('../api/ai-message-suggest.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(ctx)
                });
                const json = await res.json().catch(() => null);
                if (!res.ok || !json || !json.success || !json.data) return null;
                if (!json.data.title || !json.data.body) return null;
                return { title: String(json.data.title), body: String(json.data.body) };
            } catch (err) {
                console.warn('AI suggest failed:', err);
                return null;
            }
        }

        function mnBuildDraftContext() {
            const catId = $('#category_id').val();
            const cat = categoriesData.find(c => String(c.id) === String(catId));
            const severity = (document.querySelector('input[name="severity"]:checked')?.value || 'Medium').toLowerCase();
            const audienceType = document.getElementById('audienceType')?.value || 'all';
            const barangay = document.getElementById('barangay')?.value || '';
            const role = document.getElementById('role')?.value || '';
            const weatherSignal = document.getElementById('mnWeatherSignal')?.value || '';
            const fireLevel = document.getElementById('mnFireLevel')?.value || '';

            return {
                catName: cat?.name || 'General Alert',
                catDesc: cat?.description || '',
                severity,
                audienceType,
                barangay,
                role,
                weatherSignal,
                fireLevel
            };
        }

        function mnSetDraftFields(suggestion, markAuto = true) {
            const titleEl = document.getElementById('message_title');
            const bodyEl = document.getElementById('message_body');
            if (titleEl) {
                titleEl.value = suggestion.title;
                if (markAuto) titleEl.dataset.autoDraft = '1';
            }
            if (bodyEl) {
                bodyEl.value = suggestion.body;
                if (markAuto) bodyEl.dataset.autoDraft = '1';
            }
            if (bodyEl) updateCharCount(bodyEl);
            updateLivePreview();
            updateDispatchCTAState();
        }

        function mnRefreshAutoDraftFromContext() {
            const titleEl = document.getElementById('message_title');
            const bodyEl = document.getElementById('message_body');
            const isAuto = (titleEl?.dataset?.autoDraft === '1') || (bodyEl?.dataset?.autoDraft === '1');
            if (!isAuto) return;

            const ctx = mnBuildDraftContext();
            const suggestion = mnGenerateDraft(ctx);
            if (!suggestion) return;
            mnSetDraftFields(suggestion, true);
        }

        async function mnAiAssist() {
            const catId = $('#category_id').val();
            if (!catId) {
                alert('Select an Alert Category first.');
                return;
            }

            const ctx = mnBuildDraftContext();

            const btn = document.getElementById('mnAiAssistBtn');
            if (btn) btn.classList.add('is-loading');

            let suggestion = await mnAiSuggestFromServer(ctx);

            if (btn) btn.classList.remove('is-loading');
            if (!suggestion) {
                mnShowNotice('AI suggestion failed. Please check your API key or server logs, then try again.');
                return;
            }

            const titleEl = document.getElementById('message_title');
            const bodyEl = document.getElementById('message_body');
            const hasExisting = (titleEl?.value || '').trim() || (bodyEl?.value || '').trim();
            if (hasExisting) {
                const msg = 'Replace your current Title/Message with the AI suggested draft?';
                return mnShowConfirm(msg, () => {
                    mnSetDraftFields(suggestion, true);
                });
            }

            mnSetDraftFields(suggestion, true);
        }

        function mnFindCategoryIdByKind(kind) {
            if (!Array.isArray(categoriesData)) return null;
            const match = categoriesData.find(c => mnCategoryKindFromName(c?.name || '') === kind);
            return match?.id ?? null;
        }

        function mnEnsureWizardOpen() {
            const backdrop = document.getElementById('mnDispatchWizardBackdrop');
            const isOpen = backdrop && getComputedStyle(backdrop).display !== 'none';
            if (!isOpen) openDispatchWizard();
        }

        function mnApplyStarterTemplate(key) {
            mnEnsureWizardOpen();

            if (!Array.isArray(categoriesData) || categoriesData.length === 0) {
                alert('Categories are still loading. Please try again in a moment.');
                return;
            }

            const currentId = $('#category_id').val();
            const currentCat = categoriesData.find(c => String(c.id) === String(currentId));
            const currentKind = mnCategoryKindFromName(currentCat?.name || '');

            const desired = (() => {
                if (key === 'weather_signal') return { kind: 'weather', severity: 'Medium', weatherSignal: '3' };
                if (key === 'fire_level') return { kind: 'fire', severity: 'High', fireLevel: '2' };
                return { kind: null, severity: 'Medium' };
            })();

            let categoryId = currentId || '';
            if (desired.kind) {
                if (currentKind !== desired.kind) {
                    const found = mnFindCategoryIdByKind(desired.kind);
                    if (!found) {
                        alert(`No ${desired.kind} category found. Please add one in Alert Categorization first.`);
                        return;
                    }
                    categoryId = String(found);
                }
            } else {
                if (!categoryId) {
                    // Prefer any non-empty category as a starting point.
                    categoryId = String(categoriesData[0].id);
                }
            }

            // Apply category (Select2-safe).
            $('#category_id').val(categoryId).trigger('change');

            // Apply severity.
            const sevRadio = document.querySelector(`input[name="severity"][value="${desired.severity}"]`);
            if (sevRadio) {
                sevRadio.checked = true;
                updateSeverityUI(sevRadio);
            }

            // Ensure the dynamic level UI is updated for the newly selected category.
            mnUpdateWeatherSignalUI();

            // Apply Weather Signal / Fire Level (mark as user-set so severity changes don't override).
            if (desired.weatherSignal) {
                const sel = document.getElementById('mnWeatherSignal');
                if (sel) {
                    sel.value = String(desired.weatherSignal);
                    sel.dataset.userSet = '1';
                }
            }

            if (desired.fireLevel) {
                const sel = document.getElementById('mnFireLevel');
                if (sel) {
                    sel.value = String(desired.fireLevel);
                    sel.dataset.userSet = '1';
                }
            }

            const cat = categoriesData.find(c => String(c.id) === String(categoryId));
            const severity = (document.querySelector('input[name="severity"]:checked')?.value || 'Medium').toLowerCase();
            const audienceType = document.getElementById('audienceType')?.value || 'all';
            const barangay = document.getElementById('barangay')?.value || '';
            const role = document.getElementById('role')?.value || '';
            const weatherSignal = document.getElementById('mnWeatherSignal')?.value || '';
            const fireLevel = document.getElementById('mnFireLevel')?.value || '';

            const ctx = {
                catName: cat?.name || 'General Alert',
                catDesc: cat?.description || '',
                severity,
                audienceType,
                barangay,
                role,
                weatherSignal,
                fireLevel
            };

            const suggestion = mnGenerateDraft(ctx);
            if (!suggestion) return;

            const titleEl = document.getElementById('message_title');
            const bodyEl = document.getElementById('message_body');
            const hasExisting = (titleEl?.value || '').trim() || (bodyEl?.value || '').trim();
            if (hasExisting) {
                return mnShowConfirm('Replace your current Title/Message with the selected template?', () => {
                    mnSetDraftFields(suggestion, true);
                });
            }

            mnSetDraftFields(suggestion, true);
        }

        function mnGenerateDraft(ctx) {
            const name = String(ctx.catName || 'General').trim();
            const n = name.toLowerCase();
            const sev = String(ctx.severity || 'medium').toLowerCase();
            const bullet = '\u2022';

            const severityWord = (s) => {
                if (s === 'low') return 'Reminder';
                if (s === 'medium') return 'Warning';
                if (s === 'high') return 'Urgent Warning';
                if (s === 'critical') return 'Emergency Alert';
                return 'Warning';
            };

            const actionLead = (s) => {
                if (s === 'low') return 'This is a gentle reminder to stay aware.';
                if (s === 'medium') return 'Please stay aware and prepare if needed.';
                if (s === 'high') return 'Take action now and follow safety instructions.';
                if (s === 'critical') return 'ACT NOW for your safety.';
                return 'Please take precautions.';
            };

            const where = (() => {
                if (ctx.audienceType === 'barangay' && ctx.barangay) return `in ${ctx.barangay}, Quezon City`;
                if (ctx.audienceType === 'role' && ctx.role) return `for ${ctx.role} users in Quezon City`;
                return 'in Quezon City';
            })();

            const kind = mnCategoryKindFromName(n);

            const stamp = (() => {
                const d = new Date();
                return d.toLocaleString(undefined, { year: 'numeric', month: 'short', day: '2-digit', hour: '2-digit', minute: '2-digit' });
            })();

            const titlePrefix = severityWord(sev);

            // Special: Fire alert levels (1–3) mapped from severity
            const fireLevel = (() => {
                if (kind !== 'fire') return null;
                const provided = String(ctx.fireLevel || '').trim();
                if (provided && /^[1-3]$/.test(provided)) return Number(provided);
                return mnDefaultFireLevelFromSeverity(sev);
            })();

            // Special: Weather signal (1–5)
            const weatherSignal = (() => {
                if (kind !== 'weather') return null;
                const provided = String(ctx.weatherSignal || '').trim();
                if (provided && /^[1-5]$/.test(provided)) return Number(provided);
                return mnDefaultWeatherSignalFromSeverity(sev);
            })();

            const title =
                kind === 'fire' ? `Fire Alert Level ${fireLevel}: ${name}` :
                kind === 'weather' ? `Weather Signal ${weatherSignal}: ${name}` :
                `${titlePrefix}: ${name}`;

            const commonTail = sev === 'low'
                ? [
                    'Stay calm and be mindful of updates.',
                    'Follow LGU updates and official advisories only.'
                ]
                : [
                    'Stay calm and assist children, seniors, and persons with disabilities.',
                    'Follow LGU updates and official advisories only.'
                ];

            let bullets = [];
            if (kind === 'earthquake') {
                bullets = [
                    `${actionLead(sev)} Possible aftershocks ${where}.`,
                    'If indoors: DROP, COVER, and HOLD ON.',
                    'If outside: move to an open area away from buildings and wires.',
                    'Check for injuries and hazards (gas leaks, damaged lines).',
                    'Prepare a go-bag and keep phones charged.'
                ];
            } else if (kind === 'weather') {
                bullets = [
                    `${actionLead(sev)} Weather-related risk ${where}. (Signal ${weatherSignal})`,
                    'Secure loose items, check drainage, and keep emergency supplies ready.',
                    "Avoid crossing flooded roads; turn around, don't drown.",
                    'If asked to evacuate: move early to the nearest evacuation site.',
                    'Monitor updates and be ready for sudden changes.'
                ];
            } else if (kind === 'fire') {
                bullets = [
                    `${actionLead(sev)} Fire/smoke hazard reported ${where}. (Alert Level ${fireLevel})`,
                    'Evacuate calmly via the nearest safe exit; do not use elevators.',
                    'If there is smoke: stay low and cover nose/mouth with cloth.',
                    'Do not return until authorities declare the area safe.'
                ];
            } else if (kind === 'landslide') {
                bullets = [
                    `${actionLead(sev)} Landslide risk ${where}.`,
                    'Avoid steep slopes and watch for signs (cracks, falling rocks).',
                    'Move to a safer location if you notice unusual ground movement.',
                    'Prepare for possible evacuation.'
                ];
            } else if (kind === 'tsunami') {
                bullets = [
                    `${actionLead(sev)} Possible tsunami risk ${where}.`,
                    'Move immediately to higher ground away from coasts and rivers.',
                    'Do not return until the all-clear is given.'
                ];
            } else if (kind === 'power') {
                bullets = [
                    `${actionLead(sev)} Power interruption reported ${where}.`,
                    'Keep flashlights ready; avoid open flames indoors.',
                    'Unplug sensitive devices to prevent surge damage.',
                    'Report downed lines; keep a safe distance.'
                ];
            } else {
                bullets = [
                    `${actionLead(sev)} Please be guided ${where}.`,
                    sev === 'low' || sev === 'medium'
                        ? 'Keep contact numbers handy and monitor updates.'
                        : 'Follow official instructions and keep emergency contacts reachable.',
                    sev === 'low'
                        ? 'If you need help, contact the barangay/LGU hotline.'
                        : 'If you need help, contact the barangay/LGU hotline.'
                ];
            }

            const headerLine =
                kind === 'fire' ? `FIRE ALERT LEVEL ${fireLevel} ${bullet} ${stamp}` :
                kind === 'weather' ? `WEATHER SIGNAL ${weatherSignal} ${bullet} ${stamp}` :
                `${titlePrefix.toUpperCase()} ${bullet} ${stamp}`;

            const body = [
                headerLine,
                '',
                ...bullets.map(b => `${bullet} ${b}`),
                '',
                ...commonTail.map(t => `${bullet} ${t}`)
            ].join('\n');

            return { title, body };
        }

        function mnCategoryKindFromName(name) {
            const n = String(name || '').toLowerCase();
            if (n.includes('earthquake') || n.includes('aftershock') || n.includes('seismic')) return 'earthquake';
            if (n.includes('flood') || n.includes('typhoon') || n.includes('storm') || n.includes('rain') || n.includes('weather')) return 'weather';
            if (n.includes('fire') || n.includes('smoke') || n.includes('burn')) return 'fire';
            if (n.includes('landslide')) return 'landslide';
            if (n.includes('tsunami')) return 'tsunami';
            if (n.includes('power') || n.includes('outage')) return 'power';
            return 'general';
        }

        function mnDefaultFireLevelFromSeverity(sev) {
            const s = String(sev || '').toLowerCase();
            if (s === 'low') return 1;
            if (s === 'medium') return 2;
            return 3; // high + critical
        }

        function mnDefaultWeatherSignalFromSeverity(sev) {
            const s = String(sev || '').toLowerCase();
            if (s === 'low') return 1;
            if (s === 'medium') return 2;
            if (s === 'high') return 3;
            return 5; // critical
        }

        function mnUpdateWeatherSignalUI() {
            const catId = $('#category_id').val();
            const cat = categoriesData.find(c => c.id == catId);
            const kind = mnCategoryKindFromName(cat?.name || '');

            const weatherWrap = document.getElementById('mnWeatherSignalWrap');
            const weatherSel = document.getElementById('mnWeatherSignal');
            const fireWrap = document.getElementById('mnFireLevelWrap');
            const fireSel = document.getElementById('mnFireLevel');

            const showWeather = kind === 'weather';
            const showFire = kind === 'fire';

            if (weatherWrap) weatherWrap.style.display = showWeather ? 'block' : 'none';
            if (fireWrap) fireWrap.style.display = showFire ? 'block' : 'none';

            const sev = (document.querySelector('input[name="severity"]:checked')?.value || 'Medium').toLowerCase();

            if (showWeather && weatherSel) {
                if (weatherSel.dataset.userSet !== '1') {
                    const current = String(weatherSel.value || '').trim();
                    if (!/^[1-5]$/.test(current)) {
                        weatherSel.value = String(mnDefaultWeatherSignalFromSeverity(sev));
                    }
                }

                if (!weatherSel.dataset._bound) {
                    weatherSel.addEventListener('change', () => {
                        weatherSel.dataset.userSet = '1';
                        updateLivePreview();
                    });
                    weatherSel.dataset._bound = '1';
                }
            }

            if (showFire && fireSel) {
                if (fireSel.dataset.userSet !== '1') {
                    const current = String(fireSel.value || '').trim();
                    if (!/^[1-3]$/.test(current)) {
                        fireSel.value = String(mnDefaultFireLevelFromSeverity(sev));
                    }
                }

                if (!fireSel.dataset._bound) {
                    fireSel.addEventListener('change', () => {
                        fireSel.dataset.userSet = '1';
                        updateLivePreview();
                    });
                    fireSel.dataset._bound = '1';
                }
            }
        }

        function mnSyncWeatherSignalFromSeverity() {
            const sev = (document.querySelector('input[name="severity"]:checked')?.value || 'Medium').toLowerCase();

            let changed = false;

            const weatherWrap = document.getElementById('mnWeatherSignalWrap');
            const weatherSel = document.getElementById('mnWeatherSignal');
            if (weatherWrap && weatherSel && weatherWrap.style.display !== 'none' && weatherSel.dataset.userSet !== '1') {
                weatherSel.value = String(mnDefaultWeatherSignalFromSeverity(sev));
                changed = true;
            }

            const fireWrap = document.getElementById('mnFireLevelWrap');
            const fireSel = document.getElementById('mnFireLevel');
            if (fireWrap && fireSel && fireWrap.style.display !== 'none' && fireSel.dataset.userSet !== '1') {
                fireSel.value = String(mnDefaultFireLevelFromSeverity(sev));
                changed = true;
            }

            if (changed) updateLivePreview();
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

        function mnOpenModal(modalId) {
            const modal = document.getElementById(modalId);
            if (!modal) return;
            modal.classList.add('show');
            modal.setAttribute('aria-hidden', 'false');
            document.body.style.overflow = 'hidden';
        }

        function mnCloseModal(modalId) {
            const modal = document.getElementById(modalId);
            if (!modal) return;
            modal.classList.remove('show');
            modal.setAttribute('aria-hidden', 'true');

            // Only unlock body scroll if the wizard isn't open (it uses its own backdrop).
            const wizardOpen = document.getElementById('mnDispatchWizardBackdrop')?.classList?.contains('show');
            if (!wizardOpen) document.body.style.overflow = '';

            if (modalId === 'previewModal') {
                mnPendingDispatchPayload = null;
            }
        }

        function mnPayloadFromPreviewModal() {
            try {
                const channelsText = (document.getElementById('pvChannels')?.textContent || '').trim();
                const channels = channelsText
                    ? channelsText.split(',').map(s => s.trim().toLowerCase()).filter(Boolean)
                    : [];

                const payload = {
                    audience_type: $('#audienceType').val(),
                    barangay: $('#barangay').val(),
                    role: $('#role').val(),
                    category_id: $('#category_id').val(),
                    channels,
                    severity: (document.getElementById('pvSeverity')?.textContent || '').trim() || ($('input[name="severity"]:checked').val() || 'Medium'),
                    title: (document.getElementById('pvTitle')?.textContent || '').trim(),
                    body: (document.getElementById('pvBody')?.textContent || '').trim()
                };
                if (payload.audience_type === 'location') {
                    payload.target_lat = (document.getElementById('mnTargetLat')?.value || '').trim();
                    payload.target_lng = (document.getElementById('mnTargetLng')?.value || '').trim();
                    payload.radius_m = (document.getElementById('mnRadiusM')?.value || '').trim();
                }
                return payload;
            } catch (e) {
                return null;
            }
        }

        function mnBuildDispatchPayload() {
            const data = {
                audience_type: $('#audienceType').val(),
                barangay: $('#barangay').val(),
                role: $('#role').val(),
                category_id: $('#category_id').val(),
                channels: getSelectedChannels(),
                severity: $('input[name="severity"]:checked').val(),
                title: (document.getElementById('message_title')?.value || '').trim(),
                body: (document.getElementById('message_body')?.value || '').trim()
            };

            // Optional QC map target (location mode)
            try {
                const audienceType = data.audience_type;
                const lat = (document.getElementById('mnTargetLat')?.value || '').trim();
                const lng = (document.getElementById('mnTargetLng')?.value || '').trim();
                const radiusM = (document.getElementById('mnRadiusM')?.value || '').trim();
                const addr = (document.getElementById('mnTargetAddress')?.value || '').trim();

                if (audienceType === 'location') {
                    data.target_lat = lat;
                    data.target_lng = lng;
                    data.radius_m = radiusM;
                    if (addr) data.target_address = addr;
                } else if (audienceType === 'barangay') {
                    // Keep coords if admin picked a pin (useful for preview/audit; backend may ignore)
                    if (lat && lng) {
                        data.target_lat = lat;
                        data.target_lng = lng;
                        if (addr) data.target_address = addr;
                    }
                }
            } catch (e) {}

            // Include optional level fields when relevant (backend may ignore if unsupported)
            try {
                const cat = categoriesData.find(c => c.id == data.category_id);
                const kind = mnCategoryKindFromName(cat?.name || '');
                if (kind === 'weather') data.weather_signal = $('#mnWeatherSignal').val();
                if (kind === 'fire') data.fire_level = $('#mnFireLevel').val();
            } catch (e) {}

            return data;
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
                        if (Array.isArray(data.categories) && data.categories.length > 0) {
                            categoriesData = data.categories;
                            const cSel = document.getElementById('category_id');
                            cSel.innerHTML = '<option value="">-- Select Category --</option>' + 
                                data.categories.map(c => `<option value="${c.id}" data-icon="${c.icon}" data-color="${c.color}" data-description="${(c.description || '').replace(/\"/g,'&quot;')}">${c.name}</option>`).join('');
                            
                            // Initialize Select2 for Categories
                            initCategorySelect();
                        } else {
                            console.warn('Mass Notification: no categories returned from get_options; falling back to alert-categories list.');
                            loadCategoriesFallback();
                        }

                        // Populate Templates
                        const tSel = document.getElementById('template');
                        templatesData = data.templates;
                        tSel.innerHTML = '<option value="">-- Select a Template --</option>' + 
                            data.templates.map(t => `<option value="${t.id}">${t.title} (${t.severity})</option>`).join('');

                        // Re-apply any saved draft now that options are loaded (cookie/localStorage)
                        try { window.DraftPersist?.restoreForm(document.getElementById('dispatchForm')); } catch {}
                    } else {
                        console.error('Mass Notification: get_options failed:', data?.message || data);
                        loadCategoriesFallback();
                    }
                })
                .catch(err => {
                    console.error('Mass Notification: get_options fetch error:', err);
                    loadCategoriesFallback();
                });
        }

        function loadCategoriesFallback() {
            fetch('../api/alert-categories.php?action=list')
                .then(r => r.json())
                .then(data => {
                    if (!data || !data.success || !Array.isArray(data.categories)) {
                        console.error('Mass Notification: alert-categories list failed:', data?.message || data);
                        const cSel = document.getElementById('category_id');
                        if (cSel) cSel.innerHTML = '<option value="">-- No categories found --</option>';
                        categoriesData = [];
                        return;
                    }

                    // Normalize to the fields expected by preview + AI assist
                    categoriesData = data.categories.map(c => ({
                        id: c.id,
                        name: c.name,
                        icon: c.icon || 'fa-exclamation-triangle',
                        color: c.color || '#3a7675',
                        description: c.description || ''
                    }));

                    const cSel = document.getElementById('category_id');
                    cSel.innerHTML = '<option value="">-- Select Category --</option>' +
                        categoriesData.map(c => `<option value="${c.id}" data-icon="${c.icon}" data-color="${c.color}" data-description="${(c.description || '').replace(/\"/g,'&quot;')}">${c.name}</option>`).join('');

                    initCategorySelect();
                    try { window.DraftPersist?.restoreForm(document.getElementById('dispatchForm')); } catch {}
                })
                .catch(err => {
                    console.error('Mass Notification: category fallback error:', err);
                });
        }

        function initCategorySelect() {
            try {
                if ($('#category_id').hasClass('select2-hidden-accessible')) {
                    $('#category_id').select2('destroy');
                }
            } catch {}

            function formatCategory(state) {
                if (!state.id) return state.text;
                const icon = $(state.element).data('icon') || 'fa-tag';
                const color = $(state.element).data('color') || '#3a7675';
                return $(`<span><i class="fas ${icon}" style="color:${color}; width: 20px; text-align: center; margin-right: 8px;"></i>${state.text}</span>`);
            }

            $('#category_id').select2({
                templateResult: formatCategory,
                templateSelection: formatCategory,
                placeholder: "-- Select Category --",
                allowClear: true,
                dropdownParent: $('#mnDispatchWizardBackdrop .mn-modal')
            }).on('change', function () {
                mnUpdateWeatherSignalUI();
                updateLivePreview();
            });

            // Ensure signal UI state matches restored/initial category
            mnUpdateWeatherSignalUI();
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
            const previewFooter = document.getElementById('mnPreviewFooter');

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
                if (previewMode === 'sms') {
                    previewBody.textContent = body;
                    if (previewFooter) previewFooter.textContent = `SMS preview • ${Math.ceil((title.length + body.length) / 160)} part(s) approx.`;
                } else if (previewMode === 'email') {
                    previewBody.textContent = body ? `Hi,\n\n${body}\n\n- Emergency Communication System` : '';
                    if (previewFooter) previewFooter.textContent = 'Email preview • supports longer instructions.';
                } else if (previewMode === 'push') {
                    previewBody.textContent = body ? body.slice(0, 140) + (body.length > 140 ? '...' : '') : '';
                    if (previewFooter) previewFooter.textContent = 'Push preview • keep it short for lock screens.';
                } else if (previewMode === 'pa') {
                    previewBody.textContent = body ? body.toUpperCase() : '';
                    if (previewFooter) previewFooter.textContent = 'PA preview • uppercase for announcement clarity.';
                } else {
                    previewBody.textContent = body;
                    if (previewFooter) previewFooter.textContent = '';
                }
            }

            // Extra context: show weather signal when applicable
            if (previewFooter) {
                const cat = categoriesData.find(c => c.id == catId);
                const kind = mnCategoryKindFromName(cat?.name || '');
                if (kind === 'weather') {
                    const sig = document.getElementById('mnWeatherSignal')?.value;
                    if (sig) previewFooter.textContent = `${previewFooter.textContent} Signal ${sig}.`;
                } else if (kind === 'fire') {
                    const lvl = document.getElementById('mnFireLevel')?.value;
                    if (lvl) previewFooter.textContent = `${previewFooter.textContent} Level ${lvl}.`;
                }
            }

            updateDispatchCTAState();
        }

        function getSelectedChannels() {
            const channels = [];
            document.querySelectorAll('input[name="channels"]:checked').forEach(el => channels.push(el.value));
            return channels;
        }

        function updateDispatchCTAState() {
            const btn = document.getElementById('mnPreviewDispatchBtn');
            const reason = document.getElementById('mnDispatchReason');
            if (!btn || !reason) return;

            const audienceType = document.getElementById('audienceType')?.value || 'all';
            const title = document.getElementById('message_title').value.trim();
            const body = document.getElementById('message_body').value.trim();
            const catId = $('#category_id').val();
            const channels = getSelectedChannels();
            const lat = (document.getElementById('mnTargetLat')?.value || '').trim();
            const lng = (document.getElementById('mnTargetLng')?.value || '').trim();
            const radiusM = (document.getElementById('mnRadiusM')?.value || '').trim();

            let missing = [];
            if (!catId) missing.push('category');
            if (channels.length === 0) missing.push('channel');
            if (!title) missing.push('title');
            if (!body) missing.push('message');
            if (audienceType === 'location') {
                if (!lat || !lng) missing.push('location');
                const r = parseInt(radiusM || '0', 10);
                if (!Number.isFinite(r) || r <= 0) missing.push('radius');
            }

            const canProceed = missing.length === 0;
            btn.disabled = !canProceed;

            if (!canProceed) {
                reason.classList.add('is-visible');
                const map = {category:'Select a category', channel:'choose at least 1 channel', title:'add a title', message:'write a message', location:'pick a location on the map', radius:'set a valid radius'};
                reason.textContent = 'To continue: ' + missing.map(m => map[m]).join(', ') + '.';
            } else {
                reason.classList.remove('is-visible');
                reason.textContent = '';
            }

            // Stepper state
            const step1 = document.getElementById('mnStep1');
            const step2 = document.getElementById('mnStep2');
            const step3 = document.getElementById('mnStep3');
            if (step1 && step2 && step3) {
                step1.classList.add('is-done');
                step2.classList.toggle('is-done', channels.length > 0);
                step3.classList.toggle('is-done', !!catId && !!title && !!body);

                step1.classList.toggle('is-active', channels.length === 0);
                step2.classList.toggle('is-active', channels.length > 0 && !(catId && title && body));
                step3.classList.toggle('is-active', channels.length > 0 && (catId && title && body));
            }
        }

        // Bind interactions (safe if elements exist)
        document.addEventListener('DOMContentLoaded', function() {
            const titleInput = document.getElementById('message_title');
            const bodyInput = document.getElementById('message_body');
            if (titleInput) titleInput.addEventListener('input', () => {
                titleInput.dataset.autoDraft = '';
                updateLivePreview();
            });
            if (bodyInput) bodyInput.addEventListener('input', () => {
                bodyInput.dataset.autoDraft = '';
                updateLivePreview();
            });

            document.querySelectorAll('input[name="channels"]').forEach(ch => {
                ch.addEventListener('change', updateDispatchCTAState);
            });

            const audienceSel = document.getElementById('audienceType');
            if (audienceSel) audienceSel.addEventListener('change', updateDispatchCTAState);

            const radiusInput = document.getElementById('mnRadiusM');
            if (radiusInput) radiusInput.addEventListener('input', () => {
                updateDispatchCTAState();
                mnUpdateRadiusCircle();
            });

            const targetAddrInput = document.getElementById('mnTargetAddressText');
            if (targetAddrInput) targetAddrInput.addEventListener('input', () => {
                const v = (targetAddrInput.value || '').trim();
                const hidden = document.getElementById('mnTargetAddress');
                if (hidden) hidden.value = v;
                const txt = document.getElementById('mnTargetAddrText');
                if (txt) txt.textContent = v ? `Address: ${v}` : '';
            });

            document.querySelectorAll('.mn-preview-mode').forEach(btn => {
                btn.addEventListener('click', () => setPreviewMode(btn.dataset.mode));
            });

            const step1 = document.getElementById('mnStep1');
            const step2 = document.getElementById('mnStep2');
            const step3 = document.getElementById('mnStep3');
            if (step1) step1.addEventListener('click', () => document.getElementById('audienceType')?.focus());
            if (step2) step2.addEventListener('click', () => document.getElementById('lbl-sms')?.scrollIntoView({behavior:'smooth', block:'center'}));
            if (step3) step3.addEventListener('click', () => document.getElementById('message_title')?.focus());

            updateDispatchCTAState();
        });

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
            const data = mnBuildDispatchPayload();
            const channels = data.channels || [];

            if (channels.length === 0) {
                alert('Please select at least one channel.');
                return;
            }

            if (!data.category_id) {
                alert('Please select a category.');
                return;
            }

            if (!data.title || !data.body) {
                alert('Please enter a title and message body.');
                return;
            }

            if (data.audience_type === 'location') {
                if (!data.target_lat || !data.target_lng) {
                    alert('Please pick a location on the map first.');
                    return;
                }
                const r = parseInt(data.radius_m || '0', 10);
                if (!Number.isFinite(r) || r <= 0) {
                    alert('Please enter a valid radius in meters.');
                    return;
                }
            }

            document.getElementById('pvAudience').textContent = document.getElementById('audienceType').options[document.getElementById('audienceType').selectedIndex].text;
            document.getElementById('pvChannels').textContent = channels.join(', ').toUpperCase();
            document.getElementById('pvSeverity').textContent = data.severity || 'Medium';
            document.getElementById('pvTitle').textContent = data.title;
            document.getElementById('pvBody').textContent = data.body;

            // Use class-based modal show/hide (compatible with global modal helpers)
            mnOpenModal('previewModal');

            // Cache payload to avoid any DOM timing issues while confirming
            mnPendingDispatchPayload = data;
        }

        function submitDispatch() {
            const btn = document.getElementById('confirmDispatchBtn');
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Queuing...';

            let data = mnPendingDispatchPayload || mnBuildDispatchPayload();

            // Guardrails (avoid server-side "required fields missing" errors)
            const missing = [];
            if (!Array.isArray(data.channels) || data.channels.length === 0) missing.push('channels');
            if (!data.title) missing.push('title');
            if (!data.body) missing.push('body');
            if (missing.length > 0) {
                // Fallback: build payload from the already-rendered preview modal
                const fallback = mnPayloadFromPreviewModal();
                if (fallback) data = { ...data, ...fallback };

                const missing2 = [];
                if (!Array.isArray(data.channels) || data.channels.length === 0) missing2.push('channels');
                if (!data.title) missing2.push('title');
                if (!data.body) missing2.push('body');
                if (missing2.length > 0) {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-check-circle"></i> Confirm & Dispatch';
                    alert('Please complete required fields: ' + missing2.join(', '));
                    return;
                }
            }

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
                mnCloseModal('previewModal');
                 
                if (data.success) {
                    alert(data.message);
                    document.getElementById('dispatchForm').reset();
                    document.querySelectorAll('.channel-checkbox').forEach(c => c.classList.remove('selected'));
                    $('#category_id').val(null).trigger('change');
                    try { window.DraftPersist?.clearDraft('admin-mn-dispatch'); } catch {}
                    mnPendingDispatchPayload = null;
                    loadNotifications();

                    // Close wizard too to avoid trapping the user under overlays.
                    try { closeDispatchWizard(); } catch (e) {}

                    // Kick the worker once (local/dev friendly) so queued jobs actually send.
                    // Safe to ignore errors; deployments may run the worker via cron.
                    try {
                        fetch('../api/notification-worker.php', { cache: 'no-store' })
                            .then(() => setTimeout(loadNotifications, 800))
                            .catch(() => {});
                    } catch (e) {}
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .fail(function(xhr) {
                console.error('Dispatch Error:', xhr.responseText);
                mnCloseModal('previewModal');
                alert('Connection or Server Error. Please check console.');
            })
            .always(function() {
                btn.disabled = false;
                btn.classList.remove('is-loading');
                btn.innerHTML = '<i class="fas fa-check-circle"></i> Confirm & Dispatch';
            });
        }

        // Close preview modal on backdrop click + Escape for a smoother UX
        document.addEventListener('click', function(e) {
            const modal = document.getElementById('previewModal');
            if (!modal || !modal.classList.contains('show')) return;
            if (e.target === modal) mnCloseModal('previewModal');
        });

        document.addEventListener('keydown', function(e) {
            if (e.key !== 'Escape') return;
            const modal = document.getElementById('previewModal');
            if (modal && modal.classList.contains('show')) mnCloseModal('previewModal');
        });

        // --- Map Picker (Quezon City) ---
        function mnOpenMapPicker(mode) {
            mnMapTargetMode = mode === 'barangay' ? 'barangay' : 'location';

            if (!window.L) {
                alert('Map library failed to load. Please check your internet connection, then refresh the page.');
                return;
            }

            mnOpenModal('mnMapModal');
            setTimeout(() => {
                mnInitMapIfNeeded();
                try { mnMap.invalidateSize(); } catch (e) {}
                document.getElementById('mnMapSearch')?.focus();
                if (document.getElementById('mnMapResults') && !document.getElementById('mnMapResults').innerHTML.trim()) {
                    document.getElementById('mnMapResults').innerHTML = '<div class="mn-map-result" style="opacity:.7; cursor:default;">Search results will appear here.</div>';
                }
                mnUpdateRadiusCircle();
            }, 0);
        }

        function mnCssVar(name, fallback) {
            try {
                const v = getComputedStyle(document.documentElement).getPropertyValue(name).trim();
                return v || fallback;
            } catch (e) {
                return fallback;
            }
        }

        function mnGeoRingContainsPoint(ring, lng, lat) {
            // ring: array of [lng, lat] (GeoJSON order)
            let inside = false;
            for (let i = 0, j = ring.length - 1; i < ring.length; j = i++) {
                const xi = ring[i][0], yi = ring[i][1];
                const xj = ring[j][0], yj = ring[j][1];
                const intersect = ((yi > lat) !== (yj > lat)) && (lng < ((xj - xi) * (lat - yi)) / (yj - yi + 0.0) + xi);
                if (intersect) inside = !inside;
            }
            return inside;
        }

        function mnGeoPolygonContainsPoint(coords, lng, lat) {
            if (!Array.isArray(coords) || coords.length === 0) return false;
            const outer = coords[0];
            if (!mnGeoRingContainsPoint(outer, lng, lat)) return false;
            for (let i = 1; i < coords.length; i++) {
                if (mnGeoRingContainsPoint(coords[i], lng, lat)) return false;
            }
            return true;
        }

        function mnGeometryContainsPoint(geometry, lat, lng) {
            if (!geometry) return false;
            if (geometry.type === 'Polygon') {
                return mnGeoPolygonContainsPoint(geometry.coordinates, lng, lat);
            }
            if (geometry.type === 'MultiPolygon') {
                for (const poly of geometry.coordinates || []) {
                    if (mnGeoPolygonContainsPoint(poly, lng, lat)) return true;
                }
                return false;
            }
            return false;
        }

        function mnGeojsonContainsPoint(geojson, lat, lng) {
            try {
                if (!geojson) return false;

                // Supports FeatureCollection, Feature, or bare Geometry
                if (geojson.type === 'FeatureCollection') {
                    const features = geojson.features || [];
                    for (const f of features) {
                        if (mnGeometryContainsPoint(f?.geometry, lat, lng)) return true;
                    }
                    return false;
                }

                if (geojson.type === 'Feature') {
                    return mnGeometryContainsPoint(geojson.geometry, lat, lng);
                }

                if (geojson.type === 'Polygon' || geojson.type === 'MultiPolygon') {
                    return mnGeometryContainsPoint(geojson, lat, lng);
                }
            } catch (e) {}
            return false;
        }

        function mnLoadQcBoundary() {
            if (mnQcGeojson) return Promise.resolve(mnQcGeojson);
            return fetch('../api/quezon-city.geojson', { cache: 'force-cache' })
                .then(r => r.json())
                .then(data => {
                    mnQcGeojson = data;
                    return data;
                })
                .catch(err => {
                    console.error('Mass Notification: failed to load QC GeoJSON boundary:', err);
                    mnQcGeojson = null;
                    return null;
                });
        }

        function mnInitMapIfNeeded() {
            if (mnMap) return;

            const qcCenter = { lat: 14.6760, lng: 121.0437 };
            mnMap = L.map('mnMap', { zoomControl: true }).setView([qcCenter.lat, qcCenter.lng], 12);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; OpenStreetMap'
            }).addTo(mnMap);

            // Load and draw QC boundary GeoJSON (same file used by Weather/Earthquake monitoring)
            mnLoadQcBoundary().then((geo) => {
                if (!geo || !mnMap) return;
                if (mnQcLayer) return;

                const brand = mnCssVar('--primary-color-1', '#3a7675');
                mnQcLayer = L.geoJSON(geo, {
                    style: {
                        color: brand,
                        weight: 3,
                        fillColor: brand,
                        fillOpacity: 0.06,
                        dashArray: '10 6',
                        opacity: 0.95
                    }
                }).addTo(mnMap);

                try {
                    mnQcBounds = mnQcLayer.getBounds();
                    mnMap.setMaxBounds(mnQcBounds.pad(0.05));
                    mnMap.on('drag', function() {
                        try { mnMap.panInsideBounds(mnQcBounds.pad(0.05), { animate: false }); } catch (e) {}
                    });
                } catch (e) {}
            });

            mnMap.on('click', (e) => {
                if (!mnQcGeojson) {
                    alert('Quezon City boundary is still loading. Please try again in a moment.');
                    return;
                }
                if (!mnGeojsonContainsPoint(mnQcGeojson, e.latlng.lat, e.latlng.lng)) {
                    alert('Please pick a location within Quezon City.');
                    return;
                }
                mnSetMapSelection(e.latlng.lat, e.latlng.lng, 'Dropped pin');
            });
        }

        function mnUpdateRadiusCircle() {
            if (!mnMap || !window.L) return;
            if (mnMapTargetMode !== 'location') {
                if (mnMapRadiusCircle) {
                    try { mnMap.removeLayer(mnMapRadiusCircle); } catch (e) {}
                    mnMapRadiusCircle = null;
                }
                return;
            }

            const r = parseInt((document.getElementById('mnRadiusM')?.value || '0'), 10);
            if (!Number.isFinite(r) || r <= 0) return;
            if (mnMapSelected.lat === null || mnMapSelected.lng === null) return;

            const brand = mnCssVar('--primary-color-1', '#3a7675');
            if (!mnMapRadiusCircle) {
                mnMapRadiusCircle = L.circle([mnMapSelected.lat, mnMapSelected.lng], {
                    radius: r,
                    color: brand,
                    weight: 2,
                    opacity: 0.8,
                    fillColor: brand,
                    fillOpacity: 0.08
                }).addTo(mnMap);
            } else {
                mnMapRadiusCircle.setLatLng([mnMapSelected.lat, mnMapSelected.lng]);
                mnMapRadiusCircle.setRadius(r);
            }
        }

        function mnReverseGeocode(lat, lng) {
            // Best-effort reverse geocoding (Nominatim)
            // Keep it lightweight and resilient.
            const host = document.getElementById('mnMapSelectedAddress');
            if (host) host.textContent = 'Finding address...';

            const url = `../api/reverse-geocode.php?lat=${encodeURIComponent(lat)}&lng=${encodeURIComponent(lng)}`;
            return fetch(url, { headers: { 'Accept': 'application/json', 'Accept-Language': 'en' } })
                .then(r => r.json())
                .then(data => {
                    const name = (data && data.success && data.address) ? String(data.address) : '';
                    mnMapSelected.address = name;
                    const text = name ? `Address: ${name}` : 'Address: (not available)';
                    if (host) host.textContent = text;
                    return name;
                })
                .catch(() => {
                    if (host) host.textContent = 'Address: (lookup failed)';
                    mnMapSelected.address = '';
                    return '';
                });
        }

        function mnReverseGeocodeSafe(lat, lng) {
            // A safer reverse-geocoder: timeout + visible loading indicator.
            // Returns a string (may be empty).
            const seq = ++mnReverseGeocodeSeq;

            const mapHost = document.getElementById('mnMapSelectedAddress');
            if (mapHost) {
                mapHost.classList.add('is-loading');
                mapHost.textContent = 'Finding address...';
            }

            const latFixed = Number(lat).toFixed(6);
            const lngFixed = Number(lng).toFixed(6);
            const wLat = (document.getElementById('mnTargetLat')?.value || '').trim();
            const wLng = (document.getElementById('mnTargetLng')?.value || '').trim();
            const wizardMatches = !!wLat && !!wLng && wLat === latFixed && wLng === lngFixed;
            const wizardAddrText = document.getElementById('mnTargetAddrText');
            const wizardBtn = document.getElementById('mnLookupAddressBtn');
            if (wizardMatches && wizardAddrText) {
                wizardAddrText.classList.add('is-loading');
                wizardAddrText.textContent = 'Address: Looking up...';
            }
            if (wizardMatches && wizardBtn) {
                wizardBtn.classList.add('is-loading');
                wizardBtn.disabled = true;
            }

            const controller = new AbortController();
            const timeout = setTimeout(() => controller.abort(), 6500);
            const url = `../api/reverse-geocode.php?lat=${encodeURIComponent(lat)}&lng=${encodeURIComponent(lng)}`;

            return fetch(url, { headers: { 'Accept': 'application/json', 'Accept-Language': 'en' }, signal: controller.signal })
                .then((r) => {
                    if (!r.ok) throw new Error('HTTP ' + r.status);
                    return r.json();
                })
                .then((data) => {
                    if (seq !== mnReverseGeocodeSeq) return '';
                    const name = (data && data.success && data.address) ? String(data.address) : '';
                    if (mapHost) mapHost.textContent = name ? `Address: ${name}` : 'Address: (not available)';
                    return name;
                })
                .catch((err) => {
                    if (seq !== mnReverseGeocodeSeq) return '';
                    const timedOut = err && err.name === 'AbortError';
                    if (mapHost) mapHost.textContent = timedOut ? 'Address: (lookup timed out)' : 'Address: (lookup failed)';
                    return '';
                })
                .finally(() => {
                    clearTimeout(timeout);
                    if (seq !== mnReverseGeocodeSeq) return;
                    if (mapHost) mapHost.classList.remove('is-loading');
                    if (wizardMatches && wizardAddrText) wizardAddrText.classList.remove('is-loading');
                    if (wizardMatches && wizardBtn) {
                        wizardBtn.classList.remove('is-loading');
                        wizardBtn.disabled = false;
                    }
                });
        }

        function mnSetMapSelection(lat, lng, label) {
            mnMapSelected.lat = Number(lat);
            mnMapSelected.lng = Number(lng);
            mnMapSelected.label = label || '';

            // Guard: keep selection within QC boundary (GeoJSON)
            if (!mnQcGeojson) {
                alert('Quezon City boundary is still loading. Please try again in a moment.');
                return;
            }
            if (!mnGeojsonContainsPoint(mnQcGeojson, mnMapSelected.lat, mnMapSelected.lng)) {
                alert('Please pick a location within Quezon City.');
                return;
            }

            if (mnMap && window.L) {
                if (!mnMapMarker) {
                    mnMapMarker = L.marker([mnMapSelected.lat, mnMapSelected.lng], { draggable: true }).addTo(mnMap);
                    mnMapMarker.on('dragend', () => {
                        const p = mnMapMarker.getLatLng();
                        // Don’t allow dragging outside QC
                        if (!mnQcGeojson || !mnGeojsonContainsPoint(mnQcGeojson, p.lat, p.lng)) {
                            alert('Pin must stay within Quezon City.');
                            mnMapMarker.setLatLng([mnMapSelected.lat, mnMapSelected.lng]);
                            return;
                        }
                        mnSetMapSelection(p.lat, p.lng, 'Moved pin');
                    });
                } else {
                    mnMapMarker.setLatLng([mnMapSelected.lat, mnMapSelected.lng]);
                }
                try { mnMap.panTo([mnMapSelected.lat, mnMapSelected.lng]); } catch (e) {}
            }

            document.getElementById('mnMapLat').textContent = mnMapSelected.lat.toFixed(6);
            document.getElementById('mnMapLng').textContent = mnMapSelected.lng.toFixed(6);
            document.getElementById('mnMapSelectedLabel').textContent = mnMapSelected.label ? `Label: ${mnMapSelected.label}` : '';

            // Update radius preview + reverse geocode (debounced)
            mnUpdateRadiusCircle();
            if (mnReverseGeocodeTimer) clearTimeout(mnReverseGeocodeTimer);
            mnReverseGeocodeTimer = setTimeout(() => {
                mnReverseGeocodeSafe(mnMapSelected.lat, mnMapSelected.lng);
            }, 350);
        }

        async function mnMapDoSearch() {
            const q = (document.getElementById('mnMapSearch')?.value || '').trim();
            const resultsHost = document.getElementById('mnMapResults');
            if (!resultsHost) return;
            resultsHost.innerHTML = '<div class="mn-map-result" style="opacity:.7; cursor:default;">Searching...</div>';

            if (!q) {
                resultsHost.innerHTML = '<div class="mn-map-result" style="opacity:.7; cursor:default;">Type a search query above.</div>';
                return;
            }

            // Nominatim search limited to PH + Quezon City viewbox
            // viewbox = west, north, east, south
            let viewbox = '120.95,14.78,121.15,14.57';
            try {
                if (mnQcBounds) {
                    const sw = mnQcBounds.getSouthWest();
                    const ne = mnQcBounds.getNorthEast();
                    viewbox = `${sw.lng},${ne.lat},${ne.lng},${sw.lat}`;
                }
            } catch (e) {}
            const url = `https://nominatim.openstreetmap.org/search?format=json&limit=8&countrycodes=ph&bounded=1&viewbox=${encodeURIComponent(viewbox)}&q=${encodeURIComponent(q + ' Quezon City')}`;

            try {
                const resp = await fetch(url, { headers: { 'Accept': 'application/json' } });
                const data = await resp.json();

                if (!Array.isArray(data) || data.length === 0) {
                    resultsHost.innerHTML = '<div class="mn-map-result" style="opacity:.7; cursor:default;">No results found in Quezon City.</div>';
                    return;
                }

                resultsHost.innerHTML = data.map(item => {
                    const name = (item.display_name || '').split(',').slice(0, 3).join(', ');
                    const lat = Number(item.lat);
                    const lon = Number(item.lon);
                    const safeName = String(name).replace(/</g, '&lt;').replace(/>/g, '&gt;');
                    return `<div class="mn-map-result" role="button" tabindex="0" onclick="mnSetMapSelection(${lat}, ${lon}, ${JSON.stringify(name)})">${safeName}</div>`;
                }).join('');
            } catch (e) {
                resultsHost.innerHTML = '<div class="mn-map-result" style="opacity:.7; cursor:default;">Search failed. Please try again.</div>';
            }
        }

        function mnMapApplySelection() {
            if (mnMapSelected.lat === null || mnMapSelected.lng === null) {
                alert('Please click on the map or choose a search result first.');
                return;
            }

            const latStr = mnMapSelected.lat.toFixed(6);
            const lngStr = mnMapSelected.lng.toFixed(6);

            document.getElementById('mnTargetLat').value = latStr;
            document.getElementById('mnTargetLng').value = lngStr;

            const prettyAddr = (mnMapSelected.address || '').trim();
            const label = prettyAddr || (mnMapSelected.label ? mnMapSelected.label : `${latStr}, ${lngStr}`);
            const labelHost = document.getElementById('mnTargetLabel');
            if (labelHost) labelHost.textContent = label;

            const coordsHost = document.getElementById('mnTargetCoords');
            const radiusM = parseInt((document.getElementById('mnRadiusM')?.value || '0'), 10);
            const radiusLabel = Number.isFinite(radiusM) && radiusM > 0 ? ` \u2022 Radius: ${radiusM} m` : '';
            if (coordsHost) coordsHost.textContent = `Coordinates: ${latStr}, ${lngStr}${radiusLabel}`;

            const addrHidden = document.getElementById('mnTargetAddress');
            if (addrHidden) addrHidden.value = prettyAddr;

            const addrText = document.getElementById('mnTargetAddrText');
            const btn = document.getElementById('mnLookupAddressBtn');
            if (addrText) addrText.textContent = prettyAddr ? `Address: ${prettyAddr}` : 'Address: Looking up...';

            // Ensure "Looking up" never gets stuck visually (spinner + clear fallback)
            if (addrText) {
                addrText.classList.toggle('is-loading', !prettyAddr);
                if (!prettyAddr) addrText.textContent = 'Address: Looking up...';
            }

            const addrInput = document.getElementById('mnTargetAddressText');
            if (addrInput) addrInput.value = prettyAddr;

            if (mnMapTargetMode === 'barangay') {
                const hint = document.getElementById('mnBarangayCoordsHint');
                if (hint) hint.textContent = `Pin saved: ${latStr}, ${lngStr}`;
            }

            mnCloseModal('mnMapModal');
            updateDispatchCTAState();

            // If address hasn't resolved yet, look it up after closing the map modal
            if (!prettyAddr) {
                mnReverseGeocodeSafe(mnMapSelected.lat, mnMapSelected.lng).then((addr) => {
                    const resolved = (addr || '').trim();
                    if (!resolved) {
                        const aText = document.getElementById('mnTargetAddrText');
                        if (aText) aText.classList.remove('is-loading');
                        if (aText) aText.textContent = 'Address: (not available) - type it in the field above.';
                        return;
                    }
                    const aHidden = document.getElementById('mnTargetAddress');
                    if (aHidden) aHidden.value = resolved;
                    const aText = document.getElementById('mnTargetAddrText');
                    if (aText) aText.classList.remove('is-loading');
                    if (aText) aText.textContent = `Address: ${resolved}`;
                    const aInput = document.getElementById('mnTargetAddressText');
                    if (aInput && !aInput.value.trim()) aInput.value = resolved;
                    // Keep label friendly (do not overwrite a user-entered label)
                    const lHost = document.getElementById('mnTargetLabel');
                    if (lHost && lHost.textContent === 'Dropped pin') lHost.textContent = resolved;
                }).catch(() => {
                    const aText = document.getElementById('mnTargetAddrText');
                    if (aText) {
                        aText.classList.remove('is-loading');
                        aText.textContent = 'Address: (lookup failed) - type it in the field above.';
                    }
                });
            }
        }

                function mnLookupAddressFromWizard() {
            const lat = Number((document.getElementById('mnTargetLat')?.value || '').trim());
            const lng = Number((document.getElementById('mnTargetLng')?.value || '').trim());
            if (!Number.isFinite(lat) || !Number.isFinite(lng)) {
                alert('Pick a location on the map first.');
                return;
            }
            const addrText = document.getElementById('mnTargetAddrText');
            const btn = document.getElementById('mnLookupAddressBtn');
            if (addrText) {
                addrText.classList.add('is-loading');
                addrText.textContent = 'Address: Looking up...';
            }
            if (btn) {
                btn.classList.add('is-loading');
                btn.disabled = true;
            }
            mnReverseGeocodeSafe(lat, lng).then((addr) => {
                const resolved = (addr || '').trim();
                if (!resolved) {
                    if (addrText) addrText.textContent = 'Address: (not available) - you can type it in the field above.';
                    return;
                }
                const aHidden = document.getElementById('mnTargetAddress');
                if (aHidden) aHidden.value = resolved;
                if (addrText) addrText.textContent = `Address: ${resolved}`;
                const aInput = document.getElementById('mnTargetAddressText');
                if (aInput) aInput.value = resolved;
            }).catch(() => {
                if (addrText) addrText.textContent = 'Address: (lookup failed) - you can type it in the field above.';
            }).finally(() => {
                if (addrText) addrText.classList.remove('is-loading');
                if (btn) {
                    btn.classList.remove('is-loading');
                    btn.disabled = false;
                }
            });
        }

        // Close map modal on backdrop click + Escape
        document.addEventListener('click', function(e) {
            const modal = document.getElementById('mnMapModal');
            if (!modal || !modal.classList.contains('show')) return;
            if (e.target === modal) mnCloseModal('mnMapModal');
        });

        document.addEventListener('keydown', function(e) {
            const modal = document.getElementById('mnMapModal');
            if (!modal || !modal.classList.contains('show')) return;
            if (e.key === 'Escape') {
                mnCloseModal('mnMapModal');
                return;
            }
            if (e.key === 'Enter' && document.activeElement === document.getElementById('mnMapSearch')) {
                e.preventDefault();
                mnMapDoSearch();
            }
        });

        function loadNotifications() {
            fetch('../api/mass-notification.php?action=list')
                .then(r => r.json())
                .then(data => {
                    const tbody = document.querySelector('#notificationsTable tbody');
                    if (!tbody) return;
                    if (!data.success) {
                        tbody.innerHTML = '<tr><td colspan="7" style="text-align:center; color: var(--text-secondary-1);">Failed to load dispatch history.</td></tr>';
                        return;
                    }

                    const rows = Array.isArray(data.notifications) ? data.notifications : [];
                    if (rows.length === 0) {
                        tbody.innerHTML = '<tr><td colspan="7" style="text-align:center; color: var(--text-secondary-1);">No dispatch history yet.</td></tr>';
                        updateMnAnalytics([]);
                        return;
                    }

                    tbody.innerHTML = rows.map(n => {
                        const channelsRaw = (n.channel || '').toString();
                        const channels = channelsRaw
                            .split(',')
                            .map(c => c.trim())
                            .filter(Boolean);
                        const channelIcons = channels.length > 0
                            ? channels.map(c => `<i class="fas fa-${getIcon(c)}" title="${c}" style="color: var(--text-secondary-1); margin-right: 4px;"></i>`).join(' ')
                            : '<small style="color: var(--text-secondary-1);">N/A</small>';
                        const progress = n.progress || 0;
                        const stats = n.stats || {sent: 0, failed: 0, total: 0};
                        const status = (n.status || 'pending').toString().toLowerCase();
                        const sentAt = n.sent_at || '-';
                        const target = n.recipients || '-';
                        const message = (n.message || '').toString();
                        const successRate = (status === 'completed' && Number(stats.total) > 0)
                            ? `<strong>${Math.round((Number(stats.sent) / Number(stats.total)) * 100)}%</strong> <br><small style="color: var(--text-secondary-1);">${stats.sent}/${stats.total}</small>`
                            : '--';
                        return `
                            <tr>
                                <td>#${n.id}</td>
                                <td><small style="color: var(--text-secondary-1); font-weight: 500;">${target}</small></td>
                                <td>${channelIcons}</td>
                                <td><div style="max-width:250px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; font-size: 0.9rem;">${message}</div></td>
                                <td>
                                    <span class="badge ${status}">${status.toUpperCase()}</span>
                                    <div class="progress-container" title="${progress}% sent"><div class="progress-bar" style="width: ${progress}%"></div></div>
                                </td>
                                <td><small style="color: var(--text-secondary-1);">${sentAt}</small></td>
                                <td>
                                    ${successRate}
                                </td>
                            </tr>
                        `;
                    }).join('');
                    updateMnAnalytics(rows);
                })
                .catch((error) => {
                    console.error('Dispatch history load error:', error);
                    const tbody = document.querySelector('#notificationsTable tbody');
                    if (tbody) {
                        tbody.innerHTML = '<tr><td colspan="7" style="text-align:center; color: var(--text-secondary-1);">Unable to load dispatch history.</td></tr>';
                    }
                });
        }

        function updateMnAnalytics(notifications) {
            const total = notifications.length;
            const completed = notifications.filter(n => n.status === 'completed').length;
            const inProgress = notifications.filter(n => n.status === 'sending' || n.status === 'queued').length;

            let successSent = 0;
            let successTotal = 0;
            notifications.forEach(n => {
                if (n.status === 'completed' && n.stats && n.stats.total) {
                    successSent += Number(n.stats.sent || 0);
                    successTotal += Number(n.stats.total || 0);
                }
            });
            const rate = successTotal > 0 ? Math.round((successSent / successTotal) * 100) : 0;

            document.getElementById('mnTotalDispatches').textContent = total;
            document.getElementById('mnCompletedDispatches').textContent = completed;
            document.getElementById('mnInProgressDispatches').textContent = inProgress;
            document.getElementById('mnSuccessRate').textContent = rate;
            const sub = document.getElementById('mnSuccessRateSub');
            if (sub) sub.textContent = successTotal > 0 ? `${successSent}/${successTotal} delivered` : 'Based on completed';
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

            // Close wizard on backdrop click / escape
            const backdrop = document.getElementById('mnDispatchWizardBackdrop');
            if (backdrop) {
                backdrop.addEventListener('click', (e) => {
                    if (e.target === backdrop) closeDispatchWizard();
                });
            }
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') {
                    const bd = document.getElementById('mnDispatchWizardBackdrop');
                    if (bd && bd.classList.contains('show')) closeDispatchWizard();
                }
            });
        });
    </script>
    <script src="../../USERS/js/alert-listener.js"></script>
</body>
</html>







