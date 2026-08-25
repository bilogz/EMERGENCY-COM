<?php
/**
 * Two-Way Communication Interface Page
 * Manage interactive communication between administrators and citizens
 */

// Start session and check authentication
session_start();

// Check if user is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../login.php');
    exit();
}

$pageTitle = $pageTitle ?? 'Two-Way Communication Interface';
$pageHeading = $pageHeading ?? 'Two-Way Communication Interface';
$pageDescription = $pageDescription ?? 'Interactive communication platform allowing administrators and citizens to exchange messages in real-time.';
$pageMode = $pageMode ?? 'citizen_reports';
$isReportTableMode = in_array($pageMode, ['citizen_reports', 'emergency_calls'], true);
$assetBaseUrl = $assetBaseUrl ?? '';
$adminUsername = $_SESSION['admin_username'] ?? 'Admin';

$turnUrl = '';
$turnUsername = '';
$turnCredential = '';
$adminConfigPath = __DIR__ . '/../api/config.local.php';
if (is_file($adminConfigPath)) {
    $adminConfig = @require $adminConfigPath;
    if (is_array($adminConfig)) {
        $turnUrl = trim((string)($adminConfig['WEBRTC_TURN_URL'] ?? ''));
        $turnUsername = trim((string)($adminConfig['WEBRTC_TURN_USERNAME'] ?? ''));
        $turnCredential = trim((string)($adminConfig['WEBRTC_TURN_CREDENTIAL'] ?? ''));
    }
}
if (($turnUrl === '' || $turnUsername === '' || $turnCredential === '') && file_exists(__DIR__ . '/../../USERS/api/config.env.php')) {
    require_once __DIR__ . '/../../USERS/api/config.env.php';
    if (function_exists('getSecureConfig')) {
        $turnUrl = $turnUrl !== '' ? $turnUrl : trim((string) getSecureConfig('WEBRTC_TURN_URL', ''));
        $turnUsername = $turnUsername !== '' ? $turnUsername : trim((string) getSecureConfig('WEBRTC_TURN_USERNAME', ''));
        $turnCredential = $turnCredential !== '' ? $turnCredential : trim((string) getSecureConfig('WEBRTC_TURN_CREDENTIAL', ''));
    }
}

$turnUrls = [];
if (preg_match('/^turns?:/i', $turnUrl) && $turnUsername !== '' && $turnCredential !== '') {
    $turnUrls = [$turnUrl];
    if (preg_match('/^turns?:(?:\/\/)?([^:\/?]+)/i', $turnUrl, $hostMatch)) {
        $turnHost = strtolower($hostMatch[1]);
        if (strpos($turnHost, 'metered.ca') !== false) {
            $turnUrls = [
                'turn:' . $turnHost . ':80',
                'turn:' . $turnHost . ':80?transport=tcp',
                'turn:' . $turnHost . ':443',
                'turns:' . $turnHost . ':443?transport=tcp',
            ];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <?php if (!empty($assetBaseUrl)): ?>
    <base href="<?php echo htmlspecialchars($assetBaseUrl, ENT_QUOTES, 'UTF-8'); ?>">
    <?php endif; ?>
    <link rel="icon" type="image/x-icon" href="images/favicon.ico">
    <link rel="stylesheet" href="css/global.css?v=<?php echo filemtime(__DIR__ . '/css/global.css'); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/sidebar.css?v=<?php echo filemtime(__DIR__ . '/css/sidebar.css'); ?>">
    <link rel="stylesheet" href="css/admin-header.css">
    <link rel="stylesheet" href="css/buttons.css">
    <link rel="stylesheet" href="css/forms.css">
    <link rel="stylesheet" href="css/hero.css">
    <link rel="stylesheet" href="css/sidebar-footer.css">
    <link rel="stylesheet" href="css/modules.css">
        <link rel="stylesheet" href="css/module-two-way-communication.css?v=<?php echo filemtime(__DIR__ . '/css/module-two-way-communication.css'); ?>">
    <style>
        /* Table Layout and Responsive Columns for Desktop */
        @media (min-width: 769px) {
            .communication-container:not(.chat-active) {
                grid-template-columns: 1fr !important;
            }
            .communication-container:not(.chat-active) .chat-window {
                display: none !important;
            }
            .communication-container.chat-active {
                grid-template-columns: 1.25fr 1fr !important;
            }
            .communication-container.chat-active .chat-window {
                display: flex !important;
            }
        }

        /* Unified Table Styling */
        .twc-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            font-size: 0.9rem;
            color: var(--text-color-1);
        }
        .twc-table th {
            padding: 0.85rem 0.75rem;
            font-weight: 700;
            color: var(--text-secondary-1);
            background: var(--bg-color-2);
            border-bottom: 2px solid var(--border-color-1);
            font-size: 0.78rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .twc-table td {
            padding: 0.85rem 0.75rem;
            border-bottom: 1px solid var(--border-color-1);
            vertical-align: middle;
        }
        .twc-table tr.conversation-item {
            cursor: pointer;
        }
        .twc-table tr.conversation-item td {
            transition: background-color 0.2s ease;
        }
        .twc-table tr.conversation-item:hover td {
            background: rgba(76, 138, 137, 0.05);
        }
        .twc-table tr.conversation-item.active td {
            background: color-mix(in srgb, var(--primary-color-1) 12%, var(--card-bg-1)) !important;
            border-bottom-color: color-mix(in srgb, var(--primary-color-1) 25%, var(--border-color-1)) !important;
        }
        .twc-table tr.conversation-item.active td strong {
            color: var(--primary-color-1) !important;
        }

        /* Pulsing Status Dot */
        .twc-table .status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            display: inline-block;
            background-color: #2ecc71;
            box-shadow: 0 0 0 rgba(46, 204, 113, 0.4);
            animation: pulseStatus 2s infinite;
        }
        .twc-table tr.closed .status-dot {
            background-color: #95a5a6;
            animation: none;
            box-shadow: none;
        }
        @keyframes pulseStatus {
            0% { box-shadow: 0 0 0 0 rgba(46, 204, 113, 0.7); }
            70% { box-shadow: 0 0 0 6px rgba(46, 204, 113, 0); }
            100% { box-shadow: 0 0 0 0 rgba(46, 204, 113, 0); }
        }

        /* Premium Status Pills */
        .workflow-pill {
            display: inline-flex;
            align-items: center;
            padding: 3px 10px;
            font-size: 0.72rem;
            font-weight: 700;
            border-radius: 9999px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .workflow-pill.workflow-open {
            background-color: rgba(59, 130, 246, 0.1);
            color: #3b82f6;
        }
        .workflow-pill.workflow-completed, .workflow-pill.workflow-resolved {
            background-color: rgba(16, 185, 129, 0.1);
            color: #10b981;
        }
        .workflow-pill.workflow-closed {
            background-color: rgba(107, 114, 128, 0.1);
            color: #6b7280;
        }
        .workflow-pill.workflow-pending {
            background-color: rgba(245, 158, 11, 0.1);
            color: #f59e0b;
        }

        .twc-modal-backdrop {
            position: fixed;
            inset: 0;
            z-index: 10050;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 1rem;
            background: rgba(15, 23, 42, 0.62);
            backdrop-filter: blur(6px);
        }
        .twc-modal-backdrop.active {
            display: flex;
        }
        .twc-transfer-modal {
            width: min(520px, 94vw);
            border: 1px solid color-mix(in srgb, var(--primary-color-1) 28%, var(--border-color-1));
            border-radius: 8px;
            background: var(--card-bg-1);
            color: var(--text-color-1);
            box-shadow: 0 24px 70px rgba(0, 0, 0, 0.28);
            overflow: hidden;
        }
        .twc-transfer-modal__head {
            display: flex;
            align-items: center;
            gap: 0.8rem;
            padding: 1rem 1.15rem;
            border-bottom: 1px solid var(--border-color-1);
            background: color-mix(in srgb, var(--primary-color-1) 9%, var(--card-bg-1));
        }
        .twc-transfer-modal__icon {
            width: 42px;
            height: 42px;
            border-radius: 8px;
            display: grid;
            place-items: center;
            color: #fff;
            background: var(--primary-color-1);
        }
        .twc-transfer-modal__head h3 {
            margin: 0;
            font-size: 1rem;
            font-weight: 800;
        }
        .twc-transfer-modal__head p {
            margin: 0.15rem 0 0;
            color: var(--text-secondary-1);
            font-size: 0.82rem;
        }
        .twc-transfer-modal__body {
            padding: 1rem 1.15rem;
        }
        .twc-transfer-summary {
            display: grid;
            gap: 0.55rem;
            padding: 0.85rem;
            border: 1px solid var(--border-color-1);
            border-radius: 8px;
            background: var(--bg-color-2);
        }
        .twc-transfer-summary div {
            display: flex;
            justify-content: space-between;
            gap: 1rem;
            font-size: 0.86rem;
        }
        .twc-transfer-summary span:first-child {
            color: var(--text-secondary-1);
        }
        .twc-transfer-summary span:last-child {
            text-align: right;
            font-weight: 700;
        }
        .twc-transfer-modal__message {
            margin-top: 0.85rem;
            min-height: 1.3rem;
            color: var(--text-secondary-1);
            font-size: 0.88rem;
        }
        .twc-transfer-modal__message.error {
            color: #dc2626;
            font-weight: 700;
        }
        .twc-transfer-modal__message.success {
            color: #15803d;
            font-weight: 700;
        }
        .twc-transfer-description { margin-top: 0.9rem; }
        .twc-transfer-description label {
            display: block;
            margin-bottom: 0.4rem;
            color: var(--text-color-1);
            font-size: 0.86rem;
            font-weight: 700;
        }
        .twc-transfer-description label span { color: #dc2626; }
        .twc-transfer-description textarea {
            width: 100%;
            min-height: 96px;
            padding: 0.7rem 0.8rem;
            resize: vertical;
            box-sizing: border-box;
            border: 1px solid var(--border-color-1);
            border-radius: 8px;
            background: var(--bg-color-1);
            color: var(--text-color-1);
            font: inherit;
            line-height: 1.45;
        }
        .twc-transfer-description textarea:focus {
            outline: none;
            border-color: var(--primary-color-1);
            box-shadow: 0 0 0 3px color-mix(in srgb, var(--primary-color-1) 18%, transparent);
        }
        .twc-transfer-description textarea[aria-invalid="true"] { border-color: #dc2626; }
        .twc-transfer-description small {
            display: block;
            margin-top: 0.35rem;
            color: var(--text-secondary-1);
            font-size: 0.75rem;
        }
        .twc-transfer-modal__actions {
            display: flex;
            justify-content: flex-end;
            gap: 0.65rem;
            padding: 0.9rem 1.15rem 1.1rem;
            border-top: 1px solid var(--border-color-1);
        }
        .twc-transfer-modal__actions .btn {
            min-width: 108px;
            justify-content: center;
        }
    </style>
</head>
<body class="twc-page">
    <!-- Include Sidebar Component -->
    <?php include 'includes/sidebar.php'; ?>

    <!-- Include Admin Header Component -->
    <?php include 'includes/admin-header.php'; ?>
    
    <!-- ===================================
       MAIN CONTENT - Two-Way Communication
       =================================== -->
    <div class="main-content">
        <div class="main-container">
        <div class="title">
            <nav aria-label="Breadcrumb">
                <ol class="breadcrumb-list">
                    <li class="breadcrumb-item">
                        <a href="dashboard.php" class="breadcrumb-link">Dashboard</a>
                        </li>
                        <li class="breadcrumb-item active" aria-current="page"><?php echo htmlspecialchars($pageHeading); ?></li>
                    </ol>
                </nav>
                <h1><i class="fas fa-comments" style="color: var(--primary-color-1); margin-right: 0.5rem;"></i> <?php echo htmlspecialchars($pageHeading); ?></h1>
                <p><?php echo htmlspecialchars($pageDescription); ?></p>
            </div>

            <div class="twc-new-message-notice" id="twcNewMessageNotice" role="status" aria-live="polite" hidden>
                <div class="twc-new-message-notice__content">
                    <i class="fas fa-comment-dots" aria-hidden="true"></i>
                    <div>
                        <strong><?php echo $pageMode === 'general_enquiries' ? 'New general enquiry' : 'New message/report'; ?></strong>
                        <span id="twcNewMessageNoticeText"><?php echo $pageMode === 'general_enquiries' ? 'A new mobile enquiry was added to Open.' : 'A new citizen message was added to Open.'; ?></span>
                    </div>
                </div>
                <div class="twc-new-message-notice__actions">
                    <button type="button" class="btn btn-primary btn-sm" id="twcViewOpenMessagesBtn">
                        <i class="fas fa-inbox" aria-hidden="true"></i> View Open
                    </button>
                    <button type="button" class="twc-icon-button" id="twcDismissNewMessageBtn" aria-label="Dismiss notification" title="Dismiss">
                        <i class="fas fa-times" aria-hidden="true"></i>
                    </button>
                </div>
            </div>

            <div id="twcConversationsShell">

            
            <div class="sub-container">
                <div class="page-content">
                    <div class="communication-container" id="communicationContainer">
                        <!-- Conversations List Container -->
                        <div class="conversations-list-container">
                            <div class="chat-tabs">
                                <?php if ($pageMode === 'emergency_calls'): ?>
                                <div class="chat-tab active" onclick="switchTab('open')">
                                    <i class="fas fa-inbox"></i> Open <span id="openCount" class="badge"></span>
                                </div>
                                <div class="chat-tab" onclick="switchTab('assigned')">
                                    <i class="fas fa-user-check"></i> Assigned
                                </div>
                                <div class="chat-tab" onclick="switchTab('unanswered')">
                                    <i class="fas fa-phone-slash"></i> Unanswered
                                </div>
                                <div class="chat-tab" onclick="switchTab('completed')">
                                    <i class="fas fa-circle-check"></i> Completed
                                </div>
                                <?php else: ?>
                                <div class="chat-tab active" onclick="switchTab('open')">
                                    <i class="fas fa-inbox"></i> Open <span id="openCount" class="badge"></span>
                                </div>
                                <div class="chat-tab" onclick="switchTab('assigned')">
                                    <i class="fas fa-user-check"></i> Assigned
                                </div>
                                <?php if ($isReportTableMode): ?>
                                <div class="chat-tab" onclick="switchTab('pending')">
                                    <i class="fas fa-hourglass-half"></i> Pending Status
                                </div>
                                <div class="chat-tab" onclick="switchTab('completed')">
                                    <i class="fas fa-circle-check"></i> Completed
                                </div>
                                <?php else: ?>
                                <div class="chat-tab" onclick="switchTab('closed')">
                                    <i class="fas fa-circle-check"></i> Closed
                                </div>
                                <?php endif; ?>
                                <?php endif; ?>
                            </div>
                            <?php if ($isReportTableMode): ?><div class="chat-filters">

                                <label for="priorityFilter">Priority</label>
                                <select id="priorityFilter">
                                    <option value="all">All Priorities</option>
                                    <?php if ($isReportTableMode): ?>
                                    <option value="critical">Critical</option>
                                    <option value="high">High</option>
                                    <option value="urgent">Urgent</option>
                                    <option value="moderate">Moderate</option>
                                    <option value="low">Low</option>
                                    <?php else: ?>
                                    <option value="urgent">Urgent</option>
                                    <option value="normal">Normal</option>
                                    <?php endif; ?>
                                </select>
                            </div>
                            <?php endif; ?><div class="conversations-list-table-wrapper" id="scrollableList" style="flex: 1; overflow-y: auto; overflow-x: auto; padding: 0.75rem;">
                                <table class="twc-table">
                                    <thead>
                                        <tr>
                                            <th>Citizen</th>
                                            <th>Location</th>
                                            <th>Last Message</th>
                                            <?php if ($isReportTableMode): ?><th>Priority</th><?php endif; ?>
                                            <th>Admin Assigned</th>
                                            <th>Status</th>
                                            <th style="text-align: right;">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody id="conversationsList">
                                        <!-- Conversations will be loaded here -->
                                    </tbody>
                                </table>
                                <nav id="paginationContainer" class="twc-pagination" aria-label="Conversation pages" style="display: none;"></nav>
                                <div id="loadingSpinner" style="text-align: center; padding: 1rem; display: none;">
                                    <i class="fas fa-spinner fa-spin" style="color: var(--primary-color-1);"></i>
                                </div>
                            </div>
                        </div>

                        <!-- Chat Window -->
                        <div class="chat-window">
                            <div class="chat-header">
                                <div style="display: flex; align-items: center; overflow: hidden;">
                                    <button class="mobile-back-btn" onclick="closeMobileChat()">
                                        <i class="fas fa-arrow-left"></i>
                                    </button>
                                    <div class="chat-header-info">
                                        <h3 id="chatUserName">Select a conversation</h3>
                                        <small id="chatUserStatus"></small>
                                    </div>
                                </div>
                                <div class="chat-actions">
                                    <?php if ($isReportTableMode): ?>
                                    <div class="incident-priority-control" id="incidentPriorityControl" style="display:none;">
                                        <button type="button" class="incident-priority-button" id="incidentPriorityButton" aria-haspopup="menu" aria-expanded="false">
                                            <span id="incidentPriorityBadge" class="incident-priority-badge incident-priority-low">LOW 0</span>
                                            <i class="fas fa-chevron-down"></i>
                                        </button>
                                        <div class="incident-priority-menu" id="incidentPriorityMenu" role="menu" hidden>
                                            <button type="button" role="menuitem" data-priority="critical">
                                                <span class="incident-priority-badge incident-priority-critical">CRITICAL</span>
                                                <small>90-110</small>
                                            </button>
                                            <button type="button" role="menuitem" data-priority="high">
                                                <span class="incident-priority-badge incident-priority-high">HIGH</span>
                                                <small>70-89</small>
                                            </button>
                                            <button type="button" role="menuitem" data-priority="urgent">
                                                <span class="incident-priority-badge incident-priority-urgent">URGENT</span>
                                                <small>45-69</small>
                                            </button>
                                            <button type="button" role="menuitem" data-priority="moderate">
                                                <span class="incident-priority-badge incident-priority-moderate">MODERATE</span>
                                                <small>20-44</small>
                                            </button>
                                            <button type="button" role="menuitem" data-priority="low">
                                                <span class="incident-priority-badge incident-priority-low">LOW</span>
                                                <small>0-19</small>
                                            </button>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                     <?php if ($isReportTableMode): ?>
                                     <button class="btn btn-sm btn-secondary" id="transferConversationBtn" style="display: none;">
                                         <i class="fas fa-share-from-square"></i> Transfer
                                     </button>
                                     <?php endif; ?>
                                     <button class="btn btn-sm btn-secondary" id="releaseConversationBtn" style="display: none;">
                                         <i class="fas fa-door-open"></i> Hand Over
                                     </button>
                                     <button class="btn btn-sm btn-danger" id="deleteConversationBtn" style="display: none;">
                                         <i class="fas fa-trash-alt"></i> Delete
                                     </button>                                     <button class="btn btn-sm btn-secondary" id="toggleStatusBtn" style="display: none;">
                                         <i class="fas fa-check"></i> Close Chat
                                     </button>
                                 </div>
                            </div>
                            <div class="chat-messages" id="chatMessages">
                                <div style="text-align: center; color: var(--text-secondary-1); padding: 3rem; display: flex; flex-direction: column; align-items: center; justify-content: center; height: 100%;">
                                    <div style="font-size: 3rem; opacity: 0.2; margin-bottom: 1rem;"><i class="fas fa-comments"></i></div>
                                    <p>Select a conversation from the list to start messaging</p>
                                </div>
                            </div>
                            <div class="chat-input">
                                <input type="text" id="messageInput" placeholder="Type a message..." disabled>
                                <button class="btn btn-primary" id="sendButton" disabled style="padding: 0.8rem 1rem; border-radius: 50%;">
                                    <i class="fas fa-paper-plane"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            </div>

            <div class="twc-chatbot-logs-shell" id="twcTransferredShell" hidden>
                <div class="twc-logs-intro">
                    <h3><i class="fas fa-share-from-square"></i> Transferred Calls and Reports</h3>
                    <p>Confirmed call/message transfers sent from two-way communication to the response team system.</p>
                </div>
                <div class="twc-logs-table-wrap">
                    <table class="twc-table">
                        <thead>
                            <tr>
                                <th>Transferred At</th>
                                <th>Caller</th>
                                <th>Type</th>
                                <th>Conversation</th>
                                <th>Status</th>
                                <th>Emergency Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody id="twcTransferredBody">
                            <tr><td colspan="7" class="twc-logs-empty">No transferred records loaded.</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>


            <div class="twc-modal-backdrop" id="twcTransferModal" aria-hidden="true">
                <div class="twc-transfer-modal" role="dialog" aria-modal="true" aria-labelledby="twcTransferModalTitle">
                    <div class="twc-transfer-modal__head">
                        <div class="twc-transfer-modal__icon">
                            <i class="fas fa-share-from-square"></i>
                        </div>
                        <div>
                            <h3 id="twcTransferModalTitle">Transfer to Response Team</h3>
                            <p id="twcTransferModalSubtitle">Send this report and message history to ERS.</p>
                        </div>
                    </div>
                    <div class="twc-transfer-modal__body">
                        <div class="twc-transfer-summary">
                            <div><span>Citizen</span><span id="twcTransferCitizen">-</span></div>
                            <div><span>Emergency Type</span><span id="twcTransferType">-</span></div>
                            <div><span>Location</span><span id="twcTransferLocation">-</span></div>
                            <div><span>Priority</span><span id="twcTransferPriority" class="incident-priority-badge incident-priority-low">LOW 0</span></div>
                        </div>
                        <div class="twc-transfer-description" id="reportBarangaySelectorGroup" style="position:relative; margin-top:0.9rem;">
                            <label for="reportBarangaySearch">Incident Barangay <span aria-hidden="true" style="color:#dc2626;">*</span></label>
                            <input id="reportBarangaySearch" type="text" placeholder="Search Quezon City barangay..." autocomplete="off" style="width:100%; padding:8px 10px; border:1px solid var(--border-color-1, rgba(255,255,255,0.14)); border-radius:9px; background:var(--card-bg-1, rgba(255,255,255,0.07)); color:var(--text-color-1, #fff); font-weight:700; outline:none;">
                            <div id="reportBarangaySelected" style="font-size:12px; opacity:0.8; font-weight:600; margin-top:4px;">No barangay selected</div>
                            <div id="reportBarangayResults" style="display:none; position:absolute; left:0; right:0; top:68px; max-height:180px; overflow:auto; border:1px solid var(--border-color-1, rgba(255,255,255,0.16)); border-radius:10px; background:#111827; box-shadow:0 16px 34px rgba(0,0,0,.35); z-index:20;"></div>
                        </div>
                        <div class="twc-transfer-description" id="twcTransferDescriptionGroup">
                            <label for="twcTransferDescription">Description <span aria-hidden="true">*</span></label>
                            <textarea id="twcTransferDescription" rows="4" maxlength="1000" placeholder="Describe the incident and important details for the response team." required></textarea>
                            <small>Include the situation, hazards, injuries, or other details responders should know.</small>
                        </div>
                        <div class="twc-transfer-modal__message" id="twcTransferMessage">
                            Confirm transfer to the response team system.
                        </div>
                    </div>
                    <div class="twc-transfer-modal__actions">
                        <button type="button" class="btn btn-secondary" id="twcTransferCancelBtn">Cancel</button>
                        <button type="button" class="btn btn-primary" id="twcTransferConfirmBtn">
                            <i class="fas fa-share-from-square"></i> Transfer
                        </button>
                    </div>
                </div>
            </div>


            <div class="twc-modal-backdrop" id="twcDeleteModal" aria-hidden="true">
                <div class="twc-transfer-modal" role="dialog" aria-modal="true" aria-labelledby="twcDeleteModalTitle">
                    <div class="twc-transfer-modal__head">
                        <div class="twc-transfer-modal__icon" style="background:#dc2626;">
                            <i class="fas fa-trash-alt"></i>
                        </div>
                        <div>
                            <h3 id="twcDeleteModalTitle">Delete conversation</h3>
                            <p id="twcDeleteModalSubtitle">This item will be moved to Trash Bin.</p>
                        </div>
                    </div>
                    <div class="twc-transfer-modal__body">
                        <div class="twc-transfer-description" style="margin-top:0;">
                            <label for="twcDeleteReason">Reason <span aria-hidden="true">*</span></label>
                            <select id="twcDeleteReason" required>
                                <option value="">Choose a reason...</option>
                                <option value="duplicate">Duplicate</option>
                                <option value="false_report">False report</option>
                                <option value="spam">Spam or irrelevant</option>
                                <option value="test_report">Test submission</option>
                                <option value="resolved_elsewhere">Resolved elsewhere</option>
                                <option value="privacy_request">Privacy request</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div class="twc-transfer-description">
                            <label for="twcDeleteDetails">Additional details</label>
                            <textarea id="twcDeleteDetails" rows="3" maxlength="500" placeholder="Add context for the audit trail."></textarea>
                            <small>Details are required when Other is selected.</small>
                        </div>
                        <div class="twc-transfer-modal__message" id="twcDeleteMessage">Select a reason to continue.</div>
                    </div>
                    <div class="twc-transfer-modal__actions">
                        <button type="button" class="btn btn-secondary" id="twcDeleteCancelBtn">Cancel</button>
                        <button type="button" class="btn btn-danger" id="twcDeleteConfirmBtn">
                            <i class="fas fa-trash-alt"></i> Move to Trash
                        </button>
                    </div>
                </div>
            </div>
            <div class="twc-chatbot-logs-shell" id="twcChatbotLogsShell" hidden>
                <div class="twc-logs-intro">
                    <h3><i class="fas fa-robot"></i> Chatbot Interaction Logs</h3>
                    <p>Review what the AI assistant received, how it responded, and whether emergency routing was triggered.</p>
                </div>

                <div class="twc-logs-summary" id="twcChatbotLogsSummary">
                    <div class="twc-logs-stat">
                        <div class="twc-logs-stat-label">Total Logs</div>
                        <div class="twc-logs-stat-value" id="twcLogsStatTotal">0</div>
                    </div>
                    <div class="twc-logs-stat twc-logs-stat--danger">
                        <div class="twc-logs-stat-label">Emergency Detected</div>
                        <div class="twc-logs-stat-value" id="twcLogsStatEmergency">0</div>
                    </div>
                    <div class="twc-logs-stat">
                        <div class="twc-logs-stat-label">Last 24 Hours</div>
                        <div class="twc-logs-stat-value" id="twcLogsStatLast24h">0</div>
                    </div>
                    <div class="twc-logs-stat">
                        <div class="twc-logs-stat-label">Rule Fallback</div>
                        <div class="twc-logs-stat-value" id="twcLogsStatFallback">0</div>
                    </div>
                </div>

                <div class="twc-logs-filters">
                    <div class="twc-logs-filter twc-logs-filter--search">
                        <label for="twcLogsSearch">Search</label>
                        <input type="text" id="twcLogsSearch" placeholder="Search request, response, user, conversation..." autocomplete="off">
                    </div>
                    <div class="twc-logs-filter">
                        <label for="twcLogsIncidentType">Incident Type</label>
                        <select id="twcLogsIncidentType">
                            <option value="all">All Incident Types</option>
                        </select>
                    </div>
                    <div class="twc-logs-filter">
                        <label for="twcLogsLanguage">Language</label>
                        <select id="twcLogsLanguage">
                            <option value="all">All Languages</option>
                        </select>
                    </div>
                    <div class="twc-logs-filter">
                        <label for="twcLogsEmergency">Emergency</label>
                        <select id="twcLogsEmergency">
                            <option value="all">All</option>
                            <option value="yes">Emergency Only</option>
                            <option value="no">Non-Emergency</option>
                        </select>
                    </div>
                    <div class="twc-logs-filter">
                        <label for="twcLogsScope">QC Scope</label>
                        <select id="twcLogsScope">
                            <option value="all">All</option>
                            <option value="qc">QC</option>
                            <option value="outside_qc">Outside QC</option>
                            <option value="unknown">Unknown</option>
                        </select>
                    </div>
                    <div class="twc-logs-filter">
                        <label for="twcLogsDateFrom">Date From</label>
                        <input type="date" id="twcLogsDateFrom">
                    </div>
                    <div class="twc-logs-filter">
                        <label for="twcLogsDateTo">Date To</label>
                        <input type="date" id="twcLogsDateTo">
                    </div>
                    <div class="twc-logs-filter twc-logs-filter--actions">
                        <button type="button" class="btn btn-secondary" id="twcLogsResetBtn">
                            <i class="fas fa-rotate-left"></i> Reset
                        </button>
                        <button type="button" class="btn btn-primary" id="twcLogsRefreshBtn">
                            <i class="fas fa-sync-alt"></i> Refresh
                        </button>
                    </div>
                </div>

                <div class="twc-logs-table-shell">
                    <div class="twc-logs-table-head">
                        <strong>Recent Chatbot Responses</strong>
                        <span id="twcLogsMeta">Showing 0 logs</span>
                    </div>
                    <div class="twc-logs-table-wrap">
                        <table class="twc-logs-table">
                            <thead>
                                <tr>
                                    <th>Time</th>
                                    <th>Incident</th>
                                    <th>User / Conversation</th>
                                    <th>Request</th>
                                    <th>Response</th>
                                    <th>Flags</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody id="twcChatbotLogsBody">
                                <tr>
                                    <td colspan="7" class="twc-logs-empty">Loading chatbot logs...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="twc-logs-pagination">
                        <button type="button" class="btn btn-secondary btn-sm" id="twcLogsPrevBtn">
                            <i class="fas fa-chevron-left"></i> Prev
                        </button>
                        <span id="twcLogsPageLabel">Page 1 of 1</span>
                        <button type="button" class="btn btn-secondary btn-sm" id="twcLogsNextBtn">
                            Next <i class="fas fa-chevron-right"></i>
                        </button>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <div class="twc-log-modal" id="twcLogModal" hidden>
        <div class="twc-log-modal-backdrop" id="twcLogModalBackdrop"></div>
        <div class="twc-log-modal-card" role="dialog" aria-modal="true" aria-labelledby="twcLogModalTitle">
            <div class="twc-log-modal-head">
                <h4 id="twcLogModalTitle">Chatbot Log Detail</h4>
                <button type="button" class="twc-log-modal-close" id="twcLogModalClose" aria-label="Close">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="twc-log-modal-body">
                <div class="twc-log-modal-meta" id="twcLogModalMeta"></div>
                <div class="twc-log-modal-block">
                    <label>User Request</label>
                    <pre id="twcLogModalRequest"></pre>
                </div>
                <div class="twc-log-modal-block">
                    <label>Assistant Response</label>
                    <pre id="twcLogModalResponse"></pre>
                </div>
                <div class="twc-log-modal-block" id="twcLogModalMetadataWrap">
                    <label>Metadata</label>
                    <pre id="twcLogModalMetadata"></pre>
                </div>
            </div>
        </div>
    </div>

    <!-- MySQL Chat System -->
    <script>
        const API_BASE = '../api/';
        const APP_ROOT = (() => {
            const marker = '/ADMIN/';
            const path = window.location.pathname || '';
            const index = path.indexOf(marker);
            return index >= 0 ? path.slice(0, index) : '';
        })();
        const ROOT_API_BASE = `${APP_ROOT}/api/`;
        const transferApiUrl = (suffix = '') => `${ROOT_API_BASE}transfer-report.php${suffix}`;
        const ADMIN_USERNAME = <?php echo json_encode($adminUsername); ?>;
        const ADMIN_ID = <?php echo json_encode($_SESSION['admin_user_id'] ?? null); ?>;
        const ADMIN_AVATAR = `https://ui-avatars.com/api/?name=${encodeURIComponent(ADMIN_USERNAME)}&background=4c8a89&color=fff&size=128`;
        const PAGE_MODE = <?php echo json_encode($pageMode); ?>;
        const REPORT_TABLE_MODE = <?php echo json_encode($isReportTableMode); ?>;
        const EMERGENCY_COM_CALL_INTAKE_ENABLED = PAGE_MODE === 'emergency_calls';
        const incomingCallQueue = new Map();
        let lastDbCallSessions = { open: [], assigned: [], completed: [], all: [] };
        
        // State Management
        let currentStatus = 'open';
        let currentConversationId = null;
        let currentConversationData = null;
        let lastMessageId = 0;
        let currentPage = 1;
        const pageLimit = 10; // Show up to 10 conversations per table page
        let isLoading = false;
        let hasMore = true;
        let lastDisplayedDate = null; // Track the last date shown in the chat
        let currentDept = 'all';
        let currentTopic = 'all';
        let lastUnreadCount = 0;
        let lastUnreadMessageId = 0;
        let hasUnreadBaseline = false;
        let topicSet = new Set();
        let currentPriority = 'all';
        let currentMainView = 'conversations';
        const queryParams = new URLSearchParams(window.location.search);
        let conversationIdFromQuery = parseInt(queryParams.get('conversationId') || '0', 10);
        if (!Number.isFinite(conversationIdFromQuery) || conversationIdFromQuery <= 0) {
            conversationIdFromQuery = 0;
        }
        let conversationFromQueryOpened = false;
        let chatbotLogsSearchTimer = null;
        let chatbotLogsRealtimeTimer = null;
        const CHATBOT_LOGS_REFRESH_MS = 3500;
        const chatbotLogsState = {
            page: 1,
            pageSize: 20,
            total: 0,
            totalPages: 1,
            items: [],
            filterOptionsLoaded: false,
            filters: {
                search: '',
                incidentType: 'all',
                language: 'all',
                emergency: 'all',
                scope: 'all',
                dateFrom: '',
                dateTo: ''
            }
        };
        const DEPARTMENT_KEYS = [
            'incident_nlp',
            'traffic_transport',
            'emergency_response',
            'community_policing',
            'crime_analytics',
            'public_safety_campaign',
            'health_inspection',
            'disaster_preparedness',
            'emergency_comm'
        ];
        
        // Polling Intervals
        const MESSAGE_POLL_MS = 10000;
        const FALLBACK_POLL_MS = 10000;
        let pollInterval = null;
        let messageInterval = null;
        let twcRealtimeSource = null;
        let twcRealtimeReconnectTimer = null;
        let fallbackPollInFlight = false;
        let messagePollInFlight = false;
        let conversationLoadController = null;
        let conversationLoadSequence = 0;
        let newMessageNoticeCount = 0;
        let pendingDeleteConversation = null;
        let chatSendInFlight = false;

        function isTwoWayRealtimeOpen() {
            return !!(twcRealtimeSource && twcRealtimeSource.readyState === 1);
        }

        async function readApiResponse(response) {
            const raw = await response.text();
            let data = {};
            if (raw) {
                try {
                    data = JSON.parse(raw);
                } catch (e) {
                    data = {
                        success: false,
                        message: raw
                    };
                }
            }
            if (!response.ok) {
                data.success = false;
                if (!data.message) {
                    data.message = `HTTP ${response.status}`;
                }
                data.integration = data.integration || {};
                data.integration.httpStatus = data.integration.httpStatus || response.status;
                data.integration.response = data.integration.response || raw;
            }
            return data;
        }

        // --- View Management ---
        
        function switchTab(status) {
            currentStatus = status;

            document.querySelectorAll('.chat-tab').forEach(tab => {
                const onclick = tab.getAttribute('onclick') || '';
                tab.classList.toggle('active', onclick.includes(`'${status}'`));
            });

            currentPage = 1;
            hasMore = true;
            document.getElementById('paginationContainer').style.display = 'none';
            if (status === 'open') {
                hideNewMessageNotice();
            }

            // A second click is also a manual refresh. The loader aborts any stale
            // request so rapid tab changes cannot leave the table blank.
            loadConversations(true, false, false);
        }
        
        function closeMobileChat() {
            document.getElementById('communicationContainer').classList.remove('chat-active');
            // Allow polling to refresh list again if needed, but keep current ID active in background
        }

        function closeChatPanel() {
            if (currentConversationId && currentConversationData && Number(currentConversationData.assignedTo || 0) === Number(ADMIN_ID || 0)) {
                showToast('Conversation still active', 'Use Hand Over to leave it for another admin.');
                return;
            }
            closeMobileChat();
            document.querySelectorAll('.conversation-item').forEach(i => i.classList.remove('active'));
            currentConversationId = null;
            currentConversationData = null;
            const transferBtn = document.getElementById('transferConversationBtn');
            if (transferBtn) transferBtn.style.display = 'none';
            const deleteBtn = document.getElementById('deleteConversationBtn');
            if (deleteBtn) deleteBtn.style.display = 'none';
        }

        function clearConversationIdQueryParam() {
            const url = new URL(window.location.href);
            if (!url.searchParams.has('conversationId')) {
                return;
            }
            url.searchParams.delete('conversationId');
            window.history.replaceState({}, '', url.toString());
        }

        function tryOpenConversationFromQuery(conversations) {
            if (conversationFromQueryOpened || conversationIdFromQuery <= 0) {
                return;
            }
            if (!Array.isArray(conversations) || conversations.length === 0) {
                return;
            }

            const target = conversations.find((conv) => Number(conv && conv.id) === conversationIdFromQuery);
            if (!target) {
                return;
            }

            const element = document.querySelector(`.conversation-item[data-conversation-id="${conversationIdFromQuery}"]`);
            if (!element) {
                return;
            }

            openConversation(target.id, element._conversationData || target, element);
            conversationFromQueryOpened = true;
            clearConversationIdQueryParam();
        }

        function updateMainViewQueryParam(view) {
            const url = new URL(window.location.href);
            if (view === 'chatbotLogs' || view === 'transfers') {
                url.searchParams.set('view', view);
            } else {
                url.searchParams.delete('view');
            }
            window.history.replaceState({}, '', url.toString());
        }

        function setPrimaryView(view, updateUrl = true) {
            const normalized = view === 'chatbotLogs' || view === 'transfers' ? view : 'conversations';
            currentMainView = normalized;

            const conversationShell = document.getElementById('twcConversationsShell');
            const chatbotLogsShell = document.getElementById('twcChatbotLogsShell');
            const transferredShell = document.getElementById('twcTransferredShell');
            if (conversationShell) {
                conversationShell.hidden = normalized !== 'conversations';
            }
            if (chatbotLogsShell) {
                chatbotLogsShell.hidden = normalized !== 'chatbotLogs';
            }
            if (transferredShell) {
                transferredShell.hidden = normalized !== 'transfers';
            }

            document.querySelectorAll('.twc-primary-chip').forEach((chip) => {
                chip.classList.toggle('active', chip.getAttribute('data-twc-view') === normalized);
            });

            if (updateUrl) {
                updateMainViewQueryParam(normalized);
            }

            if (normalized === 'chatbotLogs') {
                loadChatbotLogs(false);
                startChatbotLogsRealtime();
            } else if (normalized === 'transfers') {
                stopChatbotLogsRealtime();
                loadTransferredRecords();
            } else {
                stopChatbotLogsRealtime();
            }
        }

        async function loadTransferredRecords() {
            const body = document.getElementById('twcTransferredBody');
            if (!body) return;
            body.innerHTML = '<tr><td colspan="7" class="twc-logs-empty">Loading transferred records...</td></tr>';
            try {
                const res = await fetch(transferApiUrl('?limit=25'));
                const data = await readApiResponse(res);
                const rows = Array.isArray(data.transfers) ? data.transfers : [];
                if (!data.success || rows.length === 0) {
                    body.innerHTML = '<tr><td colspan="7" class="twc-logs-empty">No transferred calls or reports yet.</td></tr>';
                    return;
                }
                body.innerHTML = rows.map(row => {
                    const payload = row.payload || {};
                    const created = row.created_at ? new Date(row.created_at).toLocaleString() : '';
                    const callerName = row.caller_name || payload.caller?.name || 'Unknown';
                    const callerPhone = row.caller_phone || payload.caller?.phone || '';
                    const responseStatus = row.response_status || 'not_requested';
                    const responseNote = row.response_status_note || '';
                    return `
                        <tr>
                            <td>${escapeHtml(created)}</td>
                            <td><strong>${escapeHtml(callerName)}</strong>${callerPhone ? `<div style="font-size:12px;opacity:.7;">${escapeHtml(callerPhone)}</div>` : ''}</td>
                            <td>${escapeHtml(row.emergency_type || payload.emergencyType || 'n/a')}</td>
                            <td>${escapeHtml(String(row.conversation_id || payload.conversationId || 'n/a'))}</td>
                            <td>${escapeHtml(row.status || 'prepared')} ${row.integration_status ? `(${escapeHtml(String(row.integration_status))})` : ''}</td>
                            <td><strong>${escapeHtml(responseStatus.replace(/_/g, ' '))}</strong>${responseNote ? `<div style="font-size:12px;opacity:.7;">${escapeHtml(responseNote)}</div>` : ''}</td>
                            <td>
                                <button type="button" class="btn btn-secondary btn-sm" onclick="requestTransferEmergencyStatus(${Number(row.id)})">Request Status</button>
                                <button type="button" class="btn btn-primary btn-sm" onclick="updateTransferEmergencyStatus(${Number(row.id)})">Update</button>
                            </td>
                        </tr>
                    `;
                }).join('');
            } catch (e) {
                body.innerHTML = '<tr><td colspan="7" class="twc-logs-empty">Failed to load transferred records.</td></tr>';
            }
        }

        async function requestTransferEmergencyStatus(transferId) {
            try {
                const res = await fetch(transferApiUrl(), {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'request_status', transferId })
                });
                const data = await readApiResponse(res);
                alert(data.message || (data.success ? 'Status requested.' : 'Status request failed.'));
                loadTransferredRecords();
            } catch (e) {
                alert('Status request failed.');
            }
        }

        let transferStatusSyncing = false;
        async function syncPendingTransferStatuses() {
            if (transferStatusSyncing || document.hidden) return;
            transferStatusSyncing = true;
            try {
                const listResponse = await fetch(transferApiUrl('?limit=25'));
                const listData = await readApiResponse(listResponse);
                const rows = Array.isArray(listData.transfers) ? listData.transfers : [];
                const pendingReports = rows.filter((row) => {
                    const payload = row.payload || {};
                    const transferType = String(payload.transfer_type || payload.transferType || '').toLowerCase();
                    const status = String(row.response_status || '').toLowerCase();
                    return transferType !== 'live_call' && !['completed'].includes(status);
                }).slice(0, 8);
                await Promise.allSettled(pendingReports.map((row) =>
                    fetch(transferApiUrl(), {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ action: 'request_status', transferId: Number(row.id) })
                    })
                ));
            } catch (error) {
                console.warn('Automatic ERS status sync skipped:', error);
            } finally {
                transferStatusSyncing = false;
            }
        }

        async function updateTransferEmergencyStatus(transferId) {
            const allowed = 'received, dispatching, ongoing_dispatch, resolved, completed';
            const responseStatus = prompt(`Enter emergency status:\n${allowed}`, 'received');
            if (!responseStatus) return;
            const note = prompt('Optional status note:', '') || '';
            try {
                const res = await fetch(transferApiUrl(), {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'update_status', transferId, responseStatus, note })
                });
                const data = await readApiResponse(res);
                alert(data.message || (data.success ? 'Status updated.' : 'Status update failed.'));
                loadTransferredRecords();
            } catch (e) {
                alert('Status update failed.');
            }
        }

        window.requestTransferEmergencyStatus = requestTransferEmergencyStatus;
        window.updateTransferEmergencyStatus = updateTransferEmergencyStatus;
        window.setTimeout(syncPendingTransferStatuses, 1200);
        window.setInterval(syncPendingTransferStatuses, 15000);

        function stopChatbotLogsRealtime() {
            if (chatbotLogsRealtimeTimer) {
                clearInterval(chatbotLogsRealtimeTimer);
                chatbotLogsRealtimeTimer = null;
            }
        }

        function startChatbotLogsRealtime() {
            stopChatbotLogsRealtime();
            chatbotLogsRealtimeTimer = setInterval(() => {
                if (currentMainView !== 'chatbotLogs') return;
                if (document.hidden) return;
                loadChatbotLogs(false, { silent: true });
            }, CHATBOT_LOGS_REFRESH_MS);
        }

        function formatChatbotLogDate(rawValue) {
            const date = new Date(rawValue);
            if (Number.isNaN(date.getTime())) {
                return 'Unknown date';
            }
            return date.toLocaleString('en-US', {
                month: 'short',
                day: 'numeric',
                year: 'numeric',
                hour: 'numeric',
                minute: '2-digit',
                hour12: true
            });
        }

        function chatbotLogTypeLabel(value) {
            const raw = String(value || '').trim();
            if (!raw) return 'General';
            return raw.replace(/[_-]+/g, ' ').replace(/\b\w/g, function (c) { return c.toUpperCase(); });
        }

        function chatbotLogTrim(value, maxLen = 120) {
            const text = String(value || '').replace(/\s+/g, ' ').trim();
            if (text.length <= maxLen) return text;
            return text.slice(0, Math.max(0, maxLen - 3)) + '...';
        }

        function chatbotLogBadge(text, variant) {
            const safeText = escapeHtml(String(text || '').trim());
            const cls = variant ? ` twc-log-badge--${variant}` : ' twc-log-badge--neutral';
            return `<span class="twc-log-badge${cls}">${safeText}</span>`;
        }

        function renderChatbotLogsLoading() {
            const body = document.getElementById('twcChatbotLogsBody');
            if (!body) return;
            body.innerHTML = '<tr><td colspan="7" class="twc-logs-empty">Loading chatbot logs...</td></tr>';
        }

        function renderChatbotLogsEmpty(message) {
            const body = document.getElementById('twcChatbotLogsBody');
            if (!body) return;
            body.innerHTML = `<tr><td colspan="7" class="twc-logs-empty">${escapeHtml(message || 'No chatbot logs found for the selected filters.')}</td></tr>`;
        }

        function updateChatbotLogsMeta(total, page, totalPages, note) {
            const meta = document.getElementById('twcLogsMeta');
            if (!meta) return;
            const safeTotal = Number.isFinite(total) ? total : 0;
            const safePage = Number.isFinite(page) ? page : 1;
            const safeTotalPages = Number.isFinite(totalPages) ? totalPages : 1;
            const base = `Showing ${safeTotal} log${safeTotal === 1 ? '' : 's'} | Page ${safePage}/${Math.max(1, safeTotalPages)}`;
            meta.textContent = note ? `${base} | ${note}` : base;
        }

        function updateChatbotLogsSummary(summary) {
            const safe = summary || {};
            const map = {
                twcLogsStatTotal: safe.total || 0,
                twcLogsStatEmergency: safe.emergency || 0,
                twcLogsStatLast24h: safe.last24h || 0,
                twcLogsStatFallback: safe.ruleFallback || 0
            };
            Object.keys(map).forEach((id) => {
                const node = document.getElementById(id);
                if (node) node.textContent = String(map[id]);
            });
        }

        function setChatbotSelectOptions(selectId, values, defaultLabel, formatter) {
            const select = document.getElementById(selectId);
            if (!select) return;

            const currentValue = String(select.value || 'all');
            const uniqueValues = Array.from(new Set((values || []).map((value) => String(value || '').trim()).filter(Boolean)));
            let optionsHtml = `<option value="all">${escapeHtml(defaultLabel)}</option>`;
            uniqueValues.forEach((value) => {
                const label = formatter ? formatter(value) : value;
                optionsHtml += `<option value="${escapeHtml(value)}">${escapeHtml(label)}</option>`;
            });
            select.innerHTML = optionsHtml;

            if (currentValue !== 'all' && uniqueValues.includes(currentValue)) {
                select.value = currentValue;
            } else if (chatbotLogsState.filters && chatbotLogsState.filters[selectId === 'twcLogsIncidentType' ? 'incidentType' : 'language']) {
                const fallback = chatbotLogsState.filters[selectId === 'twcLogsIncidentType' ? 'incidentType' : 'language'];
                if (fallback !== 'all' && uniqueValues.includes(fallback)) {
                    select.value = fallback;
                }
            }
        }

        function renderChatbotLogsRows(items) {
            const body = document.getElementById('twcChatbotLogsBody');
            if (!body) return;

            if (!Array.isArray(items) || items.length === 0) {
                renderChatbotLogsEmpty('No chatbot logs found for the selected filters.');
                return;
            }

            const rowsHtml = items.map((item) => {
                const requestText = String(item.requestText || '');
                const responseText = String(item.responseText || '');
                const incidentType = String(item.incidentType || '');
                const incidentLabel = String(item.incidentLabel || '');
                const languageCode = String(item.languageCode || '');
                const userId = String(item.userId || '');
                const conversationId = String(item.conversationId || '');
                const scope = String(item.qcScope || 'unknown');

                const badges = [
                    item.emergencyDetected
                        ? chatbotLogBadge('Emergency', 'danger')
                        : chatbotLogBadge('Non-Emergency', 'ok'),
                    chatbotLogBadge(languageCode || 'n/a', 'neutral'),
                    chatbotLogBadge(scope || 'unknown', 'neutral')
                ];
                if (item.usedRuleFallback) {
                    badges.push(chatbotLogBadge('Rule Fallback', 'warn'));
                }

                const safeId = Number(item.id || 0);
                const incidentPrimary = incidentLabel || chatbotLogTypeLabel(incidentType);

                return `
                    <tr class="twc-logs-row">
                        <td>
                            ${escapeHtml(formatChatbotLogDate(item.createdAt))}
                            <div class="twc-logs-meta-small">${escapeHtml(item.modelUsed || 'model:n/a')}</div>
                        </td>
                        <td>
                            <strong>${escapeHtml(incidentPrimary)}</strong>
                            <div class="twc-logs-meta-small">${escapeHtml(incidentType || 'general')}</div>
                        </td>
                        <td>
                            <strong>${escapeHtml(userId || 'anonymous')}</strong>
                            <div class="twc-logs-meta-small">conv: ${escapeHtml(conversationId || 'n/a')}</div>
                        </td>
                        <td>
                            <div class="twc-logs-snippet" title="${escapeHtml(chatbotLogTrim(requestText, 320))}">${escapeHtml(chatbotLogTrim(requestText, 130))}</div>
                        </td>
                        <td>
                            <div class="twc-logs-snippet" title="${escapeHtml(chatbotLogTrim(responseText, 320))}">${escapeHtml(chatbotLogTrim(responseText, 130))}</div>
                        </td>
                        <td>${badges.join('')}</td>
                        <td>
                            <button type="button" class="btn btn-secondary btn-sm twc-log-open-btn" data-log-id="${safeId}">
                                View
                            </button>
                        </td>
                    </tr>
                `;
            }).join('');

            body.innerHTML = rowsHtml;
        }

        function updateChatbotLogsPagination() {
            const prevBtn = document.getElementById('twcLogsPrevBtn');
            const nextBtn = document.getElementById('twcLogsNextBtn');
            const label = document.getElementById('twcLogsPageLabel');
            const page = chatbotLogsState.page;
            const totalPages = Math.max(1, chatbotLogsState.totalPages || 1);

            if (label) {
                label.textContent = `Page ${page} of ${totalPages}`;
            }
            if (prevBtn) {
                prevBtn.disabled = page <= 1;
            }
            if (nextBtn) {
                nextBtn.disabled = page >= totalPages;
            }
        }

        function readChatbotLogsFiltersFromUi() {
            const searchInput = document.getElementById('twcLogsSearch');
            const incidentTypeInput = document.getElementById('twcLogsIncidentType');
            const languageInput = document.getElementById('twcLogsLanguage');
            const emergencyInput = document.getElementById('twcLogsEmergency');
            const scopeInput = document.getElementById('twcLogsScope');
            const dateFromInput = document.getElementById('twcLogsDateFrom');
            const dateToInput = document.getElementById('twcLogsDateTo');

            chatbotLogsState.filters = {
                search: searchInput ? String(searchInput.value || '').trim() : '',
                incidentType: incidentTypeInput ? String(incidentTypeInput.value || 'all') : 'all',
                language: languageInput ? String(languageInput.value || 'all') : 'all',
                emergency: emergencyInput ? String(emergencyInput.value || 'all') : 'all',
                scope: scopeInput ? String(scopeInput.value || 'all') : 'all',
                dateFrom: dateFromInput ? String(dateFromInput.value || '') : '',
                dateTo: dateToInput ? String(dateToInput.value || '') : ''
            };
        }

        function fillChatbotLogsFiltersUi() {
            const filters = chatbotLogsState.filters;
            const pairs = [
                ['twcLogsSearch', filters.search],
                ['twcLogsIncidentType', filters.incidentType],
                ['twcLogsLanguage', filters.language],
                ['twcLogsEmergency', filters.emergency],
                ['twcLogsScope', filters.scope],
                ['twcLogsDateFrom', filters.dateFrom],
                ['twcLogsDateTo', filters.dateTo]
            ];

            pairs.forEach((pair) => {
                const node = document.getElementById(pair[0]);
                if (node && typeof pair[1] !== 'undefined') {
                    node.value = pair[1];
                }
            });
        }

        function applyChatbotLogsFilters(resetPage = true) {
            readChatbotLogsFiltersFromUi();
            if (resetPage) {
                chatbotLogsState.page = 1;
            }
            loadChatbotLogs(false);
        }

        function resetChatbotLogsFilters() {
            chatbotLogsState.filters = {
                search: '',
                incidentType: 'all',
                language: 'all',
                emergency: 'all',
                scope: 'all',
                dateFrom: '',
                dateTo: ''
            };
            chatbotLogsState.page = 1;
            fillChatbotLogsFiltersUi();
            loadChatbotLogs(false);
        }

        async function loadChatbotLogs(forceResetPage, options = {}) {
            const silent = !!(options && options.silent);
            if (forceResetPage) {
                chatbotLogsState.page = 1;
            }
            readChatbotLogsFiltersFromUi();
            if (!silent) {
                renderChatbotLogsLoading();
            }

            const params = new URLSearchParams({
                page: String(chatbotLogsState.page),
                pageSize: String(chatbotLogsState.pageSize)
            });
            const filters = chatbotLogsState.filters || {};
            if (filters.search) params.set('search', filters.search);
            if (filters.incidentType && filters.incidentType !== 'all') params.set('incidentType', filters.incidentType);
            if (filters.language && filters.language !== 'all') params.set('language', filters.language);
            if (filters.emergency && filters.emergency !== 'all') params.set('emergency', filters.emergency);
            if (filters.scope && filters.scope !== 'all') params.set('scope', filters.scope);
            if (filters.dateFrom) params.set('dateFrom', filters.dateFrom);
            if (filters.dateTo) params.set('dateTo', filters.dateTo);

            let note = '';
            try {
                const response = await fetch(`${API_BASE}chatbot-logs.php?${params.toString()}`);
                const data = await response.json();
                if (!data || !data.success) {
                    throw new Error((data && data.message) ? data.message : 'Failed to load chatbot logs.');
                }

                chatbotLogsState.page = Number(data.page || chatbotLogsState.page || 1);
                chatbotLogsState.total = Number(data.total || 0);
                chatbotLogsState.totalPages = Math.max(1, Number(data.totalPages || 1));
                chatbotLogsState.items = Array.isArray(data.items) ? data.items : [];

                updateChatbotLogsSummary(data.summary || {});
                renderChatbotLogsRows(chatbotLogsState.items);
                updateChatbotLogsPagination();

                setChatbotSelectOptions('twcLogsIncidentType', data.incidentTypes || [], 'All Incident Types', chatbotLogTypeLabel);
                setChatbotSelectOptions('twcLogsLanguage', data.languages || [], 'All Languages', function (value) {
                    return String(value || '').toUpperCase();
                });

                if (data.message) {
                    note = String(data.message);
                }
            } catch (error) {
                console.error('Error loading chatbot logs:', error);
                if (!silent) {
                    renderChatbotLogsEmpty(error && error.message ? error.message : 'Failed to load chatbot logs.');
                    updateChatbotLogsPagination();
                    note = 'Request failed';
                }
            }

            updateChatbotLogsMeta(chatbotLogsState.total, chatbotLogsState.page, chatbotLogsState.totalPages, note);
        }

        function openChatbotLogModalById(logId) {
            const safeId = Number(logId || 0);
            if (!safeId) return;
            const item = (chatbotLogsState.items || []).find((entry) => Number(entry.id || 0) === safeId);
            if (!item) return;

            const modal = document.getElementById('twcLogModal');
            const meta = document.getElementById('twcLogModalMeta');
            const request = document.getElementById('twcLogModalRequest');
            const response = document.getElementById('twcLogModalResponse');
            const metadataWrap = document.getElementById('twcLogModalMetadataWrap');
            const metadata = document.getElementById('twcLogModalMetadata');
            if (!modal || !meta || !request || !response || !metadataWrap || !metadata) return;

            const metaParts = [
                `Time: ${formatChatbotLogDate(item.createdAt)}`,
                `Incident: ${item.incidentLabel || chatbotLogTypeLabel(item.incidentType)}`,
                `Emergency: ${item.emergencyDetected ? 'Yes' : 'No'}`,
                `Language: ${item.languageCode || 'n/a'}`,
                `Scope: ${item.qcScope || 'unknown'}`,
                `User: ${item.userId || 'anonymous'}`,
                `Conversation: ${item.conversationId || 'n/a'}`,
                `Model: ${item.modelUsed || 'n/a'}`
            ];
            if (item.usedRuleFallback) {
                metaParts.push('Routing: Rule fallback used');
            }
            if (item.qcBarangays) {
                metaParts.push(`Matched barangays: ${item.qcBarangays}`);
            }

            meta.textContent = metaParts.join(' | ');
            request.textContent = String(item.requestText || '').trim() || '(empty request)';
            response.textContent = String(item.responseText || '').trim() || '(empty response)';

            if (item.metadata && typeof item.metadata === 'object') {
                metadataWrap.style.display = '';
                metadata.textContent = JSON.stringify(item.metadata, null, 2);
            } else {
                metadataWrap.style.display = 'none';
                metadata.textContent = '';
            }

            modal.hidden = false;
            document.body.style.overflow = 'hidden';
        }

        function closeChatbotLogModal() {
            const modal = document.getElementById('twcLogModal');
            if (!modal) return;
            modal.hidden = true;
            document.body.style.overflow = '';
        }

        window.openChatbotLogModalById = openChatbotLogModalById;

        // --- Data Loading ---

        function normalizeDeptKey(value) {
            return String(value || '').trim().toLowerCase();
        }

        function mapConversationDept(conv) {
            if (conv.department) return normalizeDeptKey(conv.department);
            const concern = normalizeDeptKey(conv.userConcern);
            const msg = normalizeDeptKey(conv.lastMessage);
            const hay = `${concern} ${msg}`;

            if (/(incident|investigation|case|nlp)/.test(hay)) return 'incident_nlp';
            if (/(traffic|transport|violation|road)/.test(hay)) return 'traffic_transport';
            if (/(emergency response|response|recovery|incident logging|resource)/.test(hay)) return 'emergency_response';
            if (/(police|policing|surveillance|cctv)/.test(hay)) return 'community_policing';
            if (/(crime|hotspot|geospatial|analytics)/.test(hay)) return 'crime_analytics';
            if (/(public safety|campaign|awareness)/.test(hay)) return 'public_safety_campaign';
            if (/(health|inspection|safety|compliance)/.test(hay)) return 'health_inspection';
            if (/(disaster|preparedness|training|simulation)/.test(hay)) return 'disaster_preparedness';
            if (/(alert|warning|multilingual|communication)/.test(hay)) return 'emergency_comm';
            return '';
        }

        function mapConversationTopic(conv) {
            if (conv.topic) return normalizeDeptKey(conv.topic);
            if (conv.userConcern) return normalizeDeptKey(conv.userConcern);
            return '';
        }

        function topicLabel(key) {
            if (!key) return '';
            return key.replace(/[_-]+/g, ' ').replace(/\b\w/g, c => c.toUpperCase());
        }

        function updateTopicFilterOptions() {
            const topicFilter = document.getElementById('topicFilter');
            if (!topicFilter) return;
            const current = topicFilter.value || 'all';
            const options = Array.from(topicSet).sort();
            topicFilter.innerHTML = '<option value="all">All Topics</option>' +
                options.map(t => `<option value="${t}">${topicLabel(t)}</option>`).join('');
            if (options.includes(current)) topicFilter.value = current;
        }

        function deptLabel(key) {
            const map = {
                incident_nlp: 'Incident & NLP',
                traffic_transport: 'Traffic & Transport',
                emergency_response: 'Emergency Response',
                community_policing: 'Policing & CCTV',
                crime_analytics: 'Crime Analytics',
                public_safety_campaign: 'Public Safety',
                health_inspection: 'Health Inspection',
                disaster_preparedness: 'Disaster Training',
                emergency_comm: 'Emergency Comms',
                unassigned: 'Unassigned'
            };
            return map[key] || '';
        }

        function deptOrder() {
            return [
                'incident_nlp',
                'traffic_transport',
                'emergency_response',
                'community_policing',
                'crime_analytics',
                'public_safety_campaign',
                'health_inspection',
                'disaster_preparedness',
                'emergency_comm',
                'unassigned'
            ];
        }

        function setActiveDepartmentNav(key) {
            const normalizedKey = normalizeDeptKey(key || 'all') || 'all';
            document.querySelectorAll('.dept-nav-chip').forEach(chip => {
                chip.classList.toggle('active', chip.getAttribute('data-dept') === normalizedKey);
            });
        }

        function updateDepartmentNavCounts(conversations) {
            const counts = { all: conversations.length };
            DEPARTMENT_KEYS.forEach(key => {
                counts[key] = 0;
            });

            conversations.forEach(conv => {
                const key = mapConversationDept(conv);
                if (key && Object.prototype.hasOwnProperty.call(counts, key)) {
                    counts[key] += 1;
                }
            });

            document.querySelectorAll('.dept-nav-count').forEach(node => {
                const key = node.getAttribute('data-dept-count') || 'all';
                const value = counts[key] || 0;
                node.textContent = String(value);
                node.style.display = value > 0 || key === 'all' ? 'inline-flex' : 'none';
            });
        }

        function updateDepartmentQueryParam(dept) {
            const url = new URL(window.location.href);
            const normalizedDept = normalizeDeptKey(dept || 'all');

            if (normalizedDept && normalizedDept !== 'all') {
                url.searchParams.set('dept', normalizedDept);
            } else {
                url.searchParams.delete('dept');
            }

            window.history.replaceState({}, '', url.toString());
        }

        function resetConversationsAndReload() {
            currentPage = 1;
            hasMore = true;
            document.getElementById('conversationsList').innerHTML = '';
            document.getElementById('paginationContainer').style.display = 'none';
            loadConversations(true);
        }

        function getConversationTimestamp(conv) {
            const raw = conv.lastMessageTime ?? conv.lastMessageAt ?? conv.updatedAt ?? conv.createdAt ?? 0;
            const ts = Number(raw);
            return Number.isFinite(ts) ? ts : 0;
        }

        function sortConversationsNewest(conversations) {
            return [...conversations].sort((a, b) => {
                const diff = getConversationTimestamp(b) - getConversationTimestamp(a);
                if (diff !== 0) return diff;
                return Number(b.id || 0) - Number(a.id || 0);
            });
        }

        function incidentPriorityMeta(convOrPriority) {
            const p = convOrPriority?.incidentPriority || convOrPriority || {};
            const level = String(p.priority || p.level || convOrPriority?.incidentPriorityLevel || 'low').toLowerCase();
            const score = Number(p.score ?? convOrPriority?.incidentPriorityScore ?? 0);
            const labels = {
                critical: 'CRITICAL',
                high: 'HIGH',
                urgent: 'URGENT',
                moderate: 'MODERATE',
                low: 'LOW'
            };
            const colors = {
                critical: { name: 'red', hex: '#dc2626' },
                high: { name: 'orange', hex: '#f97316' },
                urgent: { name: 'yellow', hex: '#eab308' },
                moderate: { name: 'blue', hex: '#2563eb' },
                low: { name: 'green', hex: '#16a34a' }
            };
            const safeLevel = labels[level] ? level : 'low';
            return {
                level: safeLevel,
                label: labels[level] || 'LOW',
                score: Number.isFinite(score) ? score : 0,
                manual: Boolean(p.manual ?? convOrPriority?.incidentPriorityManual),
                color: colors[safeLevel].name,
                hex: colors[safeLevel].hex
            };
        }

        function incidentPriorityFromScore(score) {
            const cleanScore = Math.max(0, Math.min(110, Number(score) || 0));
            let priority = 'low';
            if (cleanScore >= 90) priority = 'critical';
            else if (cleanScore >= 70) priority = 'high';
            else if (cleanScore >= 45) priority = 'urgent';
            else if (cleanScore >= 20) priority = 'moderate';
            const meta = incidentPriorityMeta({ priority, score: cleanScore });
            return { ...meta, priority: meta.level, level: meta.level };
        }

        function scoreIncidentTextByPatterns(text, rules, fallback) {
            const hay = String(text || '').toLowerCase();
            for (const rule of rules) {
                for (const pattern of rule.patterns) {
                    if (pattern.test(hay)) return rule.score;
                }
            }
            return fallback;
        }

        function calculateIncidentPriority(data = {}) {
            const text = [
                data.incident_type,
                data.type,
                data.category,
                data.userConcern,
                data.user_concern,
                data.message,
                data.text,
                data.last_message,
                data.description,
                data.severity,
                data.threat,
                data.verification
            ].filter(value => String(value || '').trim() !== '').join(' ').toLowerCase();

            const incidentType = scoreIncidentTextByPatterns(text, [
                { score: 40, patterns: [/\bbomb\b/, /active\s+shooter/, /gunman/, /shooting/] },
                { score: 38, patterns: [/structural\s+fire/, /building\s+fire/, /major\s+fire/, /building\s+collapse/, /collapsed?\s+building/] },
                { score: 35, patterns: [/chemical\s+spill/, /hazardous\s+material/, /hazmat/, /earthquake/] },
                { score: 33, patterns: [/landslide/] },
                { score: 32, patterns: [/flash\s+flood/, /flood/] },
                { score: 30, patterns: [/typhoon/, /storm\s+damage/, /gas\s+leak/] },
                { score: 28, patterns: [/medical/, /heart\s+attack/, /stroke/, /unconscious/, /injur/] },
                { score: 25, patterns: [/vehicular/, /vehicle/, /car\s+accident/, /collision/, /crash/] },
                { score: 20, patterns: [/missing\s+person/, /missing\s+child/] },
                { score: 10, patterns: [/animal\s+rescue/, /stray\s+animal/] },
                { score: 8, patterns: [/power\s+outage/, /blackout/] },
                { score: 3, patterns: [/noise/, /minor\s+disturbance/, /disturbance/] }
            ], 3);
            const threat = scoreIncidentTextByPatterns(text, [
                { score: 30, patterns: [/multiple\s+lives/, /many\s+people.*danger/, /immediate\s+danger/, /life.?threat/] },
                { score: 25, patterns: [/trapped/, /seriously\s+injured/, /critical\s+injur/] },
                { score: 15, patterns: [/nearby\s+people/, /possible\s+danger/, /risk\s+to\s+people/] },
                { score: 0, patterns: [/false\s+alarm/, /hoax/] }
            ], 5);
            const severity = scoreIncidentTextByPatterns(text, [
                { score: 20, patterns: [/catastrophic/, /massive/, /destroyed/, /severe/] },
                { score: 15, patterns: [/major/, /large/, /serious/] },
                { score: 10, patterns: [/moderate/] },
                { score: 2, patterns: [/very\s+minor/] }
            ], 5);
            const population = scoreIncidentTextByPatterns(text, [
                { score: 10, patterns: [/(more\s+than\s+)?500\+?\s+(people|persons|residents)/, /hundreds\s+of\s+people/] },
                { score: 8, patterns: [/\b[1-4]\d\d\s+(people|persons|residents)/, /100\s*-\s*500/] },
                { score: 6, patterns: [/\b[2-9]\d\s+(people|persons|residents)/, /20\s*-\s*99/] },
                { score: 4, patterns: [/\b(5|6|7|8|9|1\d)\s+(people|persons|residents)/, /5\s*-\s*19/] }
            ], 2);
            const verification = scoreIncidentTextByPatterns(text, [
                { score: 10, patterns: [/verified/, /official\s+source/, /emergency\s+personnel/, /cctv/] },
                { score: 8, patterns: [/multiple\s+witness/, /many\s+witness/] },
                { score: 5, patterns: [/identified\s+witness/, /reported\s+by\s+.*witness/] },
                { score: 0, patterns: [/confirmed\s+false/, /false\s+report/] }
            ], 2);
            const score = incidentType + threat + severity + population + verification;
            return {
                ...incidentPriorityFromScore(score),
                score,
                breakdown: {
                    incident_type: incidentType,
                    threat_to_life: threat,
                    severity,
                    population_affected: population,
                    verification
                }
            };
        }

        function sortCitizenReports(conversations) {
            return [...conversations].sort((a, b) => {
                const scoreDiff = incidentPriorityMeta(b).score - incidentPriorityMeta(a).score;
                if (scoreDiff !== 0) return scoreDiff;
                return getConversationTimestamp(b) - getConversationTimestamp(a);
            });
        }

        function incidentPriorityBadgeHtml(conv) {
            if (!REPORT_TABLE_MODE) return '';
            const meta = incidentPriorityMeta(conv);
            return `<span class="incident-priority-badge incident-priority-${meta.level}">${meta.label} ${meta.score}</span>`;
        }

        function orderedDeptKeysByRecency(grouped) {
            const fallbackOrder = deptOrder();
            return Object.keys(grouped).sort((a, b) => {
                const aTopTs = grouped[a]?.[0] ? getConversationTimestamp(grouped[a][0]) : 0;
                const bTopTs = grouped[b]?.[0] ? getConversationTimestamp(grouped[b][0]) : 0;
                if (aTopTs !== bTopTs) return bTopTs - aTopTs;

                const aIdx = fallbackOrder.indexOf(a);
                const bIdx = fallbackOrder.indexOf(b);
                return (aIdx === -1 ? 999 : aIdx) - (bIdx === -1 ? 999 : bIdx);
            });
        }

        function ensureDeptSection(listContainer, key) {
            const id = `dept-${key}`;
            let section = document.getElementById(id);
            if (section) return section;

            section = document.createElement('div');
            section.className = 'dept-section';
            section.id = id;
            section.innerHTML = `
                <div class="dept-section-title">
                    <span class="dept-toggle"><i class="fas fa-chevron-down dept-caret"></i> ${deptLabel(key) || 'Unassigned'}</span>
                    <span id="${id}-count">0</span>
                </div>
                <div class="dept-section-list"></div>
            `;
            section.querySelector('.dept-section-title').addEventListener('click', () => {
                section.classList.toggle('collapsed');
            });
            listContainer.appendChild(section);
            return section;
        }

        function renderGroupedConversations(conversations, append) {
            const listContainer = document.getElementById('conversationsList');
            if (!listContainer) return;

            if (!append) listContainer.innerHTML = '';

            const existingIds = new Set(
                Array.from(listContainer.querySelectorAll('.conversation-item')).map(node => String(node.getAttribute('data-conversation-id')))
            );

            // Sort conversations (by priority if citizen reports, or by recency)
            const sorted = REPORT_TABLE_MODE ? sortCitizenReports(conversations) : sortConversationsNewest(conversations);

            sorted.forEach(conv => {
                const convId = String(conv.id);
                if (existingIds.has(convId)) return;
                listContainer.appendChild(createConversationElement(conv));
                existingIds.add(convId);
            });
        }

        async function loadConversations(isInitial = false, append = false, silent = false) {
            if (typeof EMERGENCY_COM_CALL_INTAKE_ENABLED !== 'undefined' && EMERGENCY_COM_CALL_INTAKE_ENABLED) {
                const spinner = document.getElementById('loadingSpinner');
                const pagination = document.getElementById('paginationContainer');
                if (spinner) spinner.style.display = 'none';
                if (pagination) pagination.style.display = 'none';
                if (typeof renderCallTableForStatus === 'function') {
                    renderCallTableForStatus();
                } else if (typeof renderIncomingCallTableRows === 'function') {
                    renderIncomingCallTableRows();
                }
                return;
            }
            const requestSequence = ++conversationLoadSequence;
            if (conversationLoadController) {
                conversationLoadController.abort();
            }
            const controller = new AbortController();
            conversationLoadController = controller;
            isLoading = true;

            const listContainer = document.getElementById('conversationsList');
            const spinner = document.getElementById('loadingSpinner');
            if (isInitial && !append && !silent) {
                if (spinner) spinner.style.display = 'block';
                listContainer.innerHTML = '';
            }

            try {
                const params = new URLSearchParams({
                    status: currentStatus,
                    page: currentPage,
                    limit: pageLimit
                });
                if (currentStatus === 'open') {
                    params.set('unassigned_only', '1');
                }
                if (PAGE_MODE === 'citizen_reports') {
                    params.set('scope', 'citizen_reports');
                } else if (PAGE_MODE === 'emergency_calls') {
                    params.set('scope', 'emergency_calls');
                } else if (PAGE_MODE === 'general_enquiries') {
                    params.set('scope', 'general_enquiries');
                }
                if (!conversationFromQueryOpened && conversationIdFromQuery > 0) {
                    params.set('conversationId', String(conversationIdFromQuery));
                }
                if (currentDept !== 'all') {
                    params.set('category', currentDept);
                }
                if (currentPriority !== 'all') {
                    params.set('priority', currentPriority);
                }

                const response = await fetch(`${API_BASE}chat-get-conversations.php?${params}`, {
                    signal: controller.signal
                });
                const data = await response.json();
                if (requestSequence !== conversationLoadSequence) return;
                if (!data.success) throw new Error(data.message || 'Failed to load conversations.');

                let conversations = data.conversations || [];
                updateDepartmentNavCounts(conversations);
                const openBadge = document.getElementById('openCount');
                if (openBadge && (currentStatus === 'open' || currentStatus === 'active')) {
                    const totalOpen = (data.pagination && typeof data.pagination.total === 'number')
                        ? data.pagination.total
                        : conversations.length;
                    openBadge.textContent = totalOpen > 0 ? String(totalOpen) : '';
                    openBadge.style.display = totalOpen > 0 ? 'inline-block' : 'none';
                }
                conversations.forEach(c => {
                    const t = mapConversationTopic(c);
                    if (t) topicSet.add(t);
                });
                updateTopicFilterOptions();
                if (currentDept !== 'all') {
                    conversations = conversations.filter(conv => mapConversationDept(conv) === currentDept);
                }
                if (currentTopic !== 'all') {
                    conversations = conversations.filter(conv => mapConversationTopic(conv) === currentTopic);
                }

                if (conversations.length === 0) {
                    hasMore = false;
                    if (!append) {
                        const suffix = currentDept === 'all' ? '' : ' for this department';
                        const topicSuffix = currentTopic === 'all' ? '' : ' for this topic';
                        listContainer.innerHTML = `<p style="text-align: center; color: var(--text-secondary-1); padding: 2rem;">No ${currentStatus} conversations${suffix}${topicSuffix}</p>`;
                        renderIncomingCallTableRows();
                    }
                    renderConversationPagination(0);
                    return;
                }

                const totalPages = Math.max(1, Number(data.pagination?.total_pages) || 1);
                hasMore = currentPage < totalPages;
                renderConversationPagination(totalPages);
                renderGroupedConversations(conversations, false);
                renderIncomingCallTableRows();
                tryOpenConversationFromQuery(conversations);
            } catch (error) {
                if (error && error.name === 'AbortError') return;
                console.error('Error loading conversations:', error);
                if (requestSequence === conversationLoadSequence && !silent) {
                    listContainer.innerHTML = '<p style="color: #e74c3c; text-align: center; padding: 1rem;">Failed to load data</p>';
                }
            } finally {
                if (requestSequence === conversationLoadSequence) {
                    if (spinner) spinner.style.display = 'none';
                    if (conversationLoadController === controller) {
                        conversationLoadController = null;
                    }
                    isLoading = false;
                }
            }
        }
        
        function renderConversationPagination(totalPages) {
            const container = document.getElementById('paginationContainer');
            if (!container) return;

            if (totalPages <= 1) {
                container.innerHTML = '';
                container.style.display = 'none';
                return;
            }

            const pages = [];
            const start = Math.max(1, currentPage - 2);
            const end = Math.min(totalPages, currentPage + 2);
            if (start > 1) pages.push(1);
            if (start > 2) pages.push('ellipsis-start');
            for (let page = start; page <= end; page++) pages.push(page);
            if (end < totalPages - 1) pages.push('ellipsis-end');
            if (end < totalPages) pages.push(totalPages);

            const pageButtons = pages.map(page => {
                if (typeof page !== 'number') return '<span class="twc-page-ellipsis" aria-hidden="true">&hellip;</span>';
                const active = page === currentPage;
                return `<button type="button" class="twc-page-btn${active ? ' active' : ''}" data-conversation-page="${page}" ${active ? 'aria-current="page"' : ''}>${page}</button>`;
            }).join('');

            container.innerHTML = `
                <button type="button" class="twc-page-btn twc-page-nav" data-conversation-page="${currentPage - 1}" ${currentPage <= 1 ? 'disabled' : ''} aria-label="Previous page">&lsaquo;</button>
                ${pageButtons}
                <button type="button" class="twc-page-btn twc-page-nav" data-conversation-page="${currentPage + 1}" ${currentPage >= totalPages ? 'disabled' : ''} aria-label="Next page">&rsaquo;</button>
            `;
            container.style.display = 'flex';
        }

        function goToConversationPage(page) {
            const targetPage = Number(page);
            if (isLoading || !Number.isInteger(targetPage) || targetPage < 1 || targetPage === currentPage) return;
            currentPage = targetPage;
            loadConversations(true, false).then(() => {
                document.getElementById('scrollableList')?.scrollTo({ top: 0, behavior: 'smooth' });
            });
        }

        document.getElementById('paginationContainer')?.addEventListener('click', (event) => {
            const button = event.target.closest('[data-conversation-page]');
            if (!button || button.disabled) return;
            goToConversationPage(Number(button.dataset.conversationPage));
        });
        
        // --- Real-time Polling ---
        
        function ensureToastContainer() {
            let container = document.querySelector('.tw-toast-container');
            if (!container) {
                container = document.createElement('div');
                container.className = 'tw-toast-container';
                document.body.appendChild(container);
            }
            return container;
        }

        function showToast(title, message) {
            const container = ensureToastContainer();
            const toast = document.createElement('div');
            toast.className = 'tw-toast';
            toast.innerHTML = `
                <i class="fas fa-comment-dots"></i>
                <div>
                    <strong>${title}</strong><br/>
                    <small>${message}</small>
                </div>
            `;
            container.appendChild(toast);
            setTimeout(() => { toast.remove(); }, 3500);
        }

        function formatMessageReportCount(diff) {
            if (PAGE_MODE === 'general_enquiries') {
                return diff === 1 ? '1 new general enquiry' : `${diff} new general enquiries`;
            }
            return diff === 1 ? '1 new message/report' : `${diff} new message/reports`;
        }

        function hideNewMessageNotice() {
            const notice = document.getElementById('twcNewMessageNotice');
            if (notice) notice.hidden = true;
            newMessageNoticeCount = 0;
        }

        function showNewMessageNotice(diff = 1) {
            const notice = document.getElementById('twcNewMessageNotice');
            const message = document.getElementById('twcNewMessageNoticeText');
            if (!notice || !message) return;
            newMessageNoticeCount += Math.max(1, Number(diff) || 1);
            message.textContent = `${formatMessageReportCount(newMessageNoticeCount)} received. The conversation list has been updated.`;
            notice.hidden = false;
        }

        function updateTwoWayBadges(count) {
            if (typeof window.updateHeaderBadges === 'function') {
                window.updateHeaderBadges({ messages: count });
            }
        }

        function handleUnreadCount(rawCount, latestMessageId = null) {
            const count = parseInt(rawCount, 10);
            if (!Number.isFinite(count)) return;
            const latestId = parseInt(latestMessageId || 0, 10);
            const hasLatestId = Number.isFinite(latestId) && latestId > 0;

            if (!hasUnreadBaseline) {
                lastUnreadCount = count;
                if (hasLatestId) lastUnreadMessageId = latestId;
                hasUnreadBaseline = true;
            } else if (count > lastUnreadCount || (hasLatestId && latestId > lastUnreadMessageId)) {
                const diff = count > lastUnreadCount ? count - lastUnreadCount : 1;
                showToast(PAGE_MODE === 'general_enquiries' ? 'New general enquiry' : 'New message/report', formatMessageReportCount(diff));
                showNewMessageNotice(diff);
                lastUnreadCount = count;
            } else if (count < lastUnreadCount) {
                lastUnreadCount = count;
            }
            if (hasLatestId && latestId > lastUnreadMessageId) {
                lastUnreadMessageId = latestId;
            }

            updateTwoWayBadges(count);
        }

        async function refreshConversationListRealtime() {
            if (currentMainView !== 'conversations') return;
            if (currentPage === 1 && ['open', 'active', 'assigned', 'pending', 'completed', 'closed'].includes(currentStatus)) {
                await loadConversations(false, false, true);
            }
        }

        function closeTwoWayRealtimeSource() {
            if (twcRealtimeSource) {
                twcRealtimeSource.close();
                twcRealtimeSource = null;
            }
        }

        function scheduleTwoWayRealtimeReconnect(delay = 1800) {
            if (!('EventSource' in window)) return;
            if (twcRealtimeReconnectTimer) clearTimeout(twcRealtimeReconnectTimer);
            twcRealtimeReconnectTimer = setTimeout(() => {
                twcRealtimeReconnectTimer = null;
                connectTwoWayRealtime();
            }, delay);
        }

        function connectTwoWayRealtime() {
            if (document.hidden || !('EventSource' in window) || twcRealtimeSource) return;
            const realtimeUrl = new URL(API_BASE + 'realtime.php', window.location.href);
            realtimeUrl.searchParams.set('scope', PAGE_MODE);
            if (currentConversationId) {
                realtimeUrl.searchParams.set('conversationId', currentConversationId);
                realtimeUrl.searchParams.set('lastMessageId', lastMessageId);
            }
            twcRealtimeSource = new EventSource(realtimeUrl.toString());

            const readEventData = (event) => {
                try { return JSON.parse(event.data || '{}'); } catch (e) { return {}; }
            };

            twcRealtimeSource.addEventListener('ready', (event) => {
                const data = readEventData(event);
                handleUnreadCount(data.unreadMessageCount ?? data.unreadCount, data.latestMessageId);
                refreshConversationListRealtime();
            });

            twcRealtimeSource.addEventListener('message:new', (event) => {
                const data = readEventData(event);
                const messageId = Number(data.id || 0);
                if (!currentConversationId || Number(data.conversationId || 0) !== Number(currentConversationId)) return;
                if (!messageId || messageId <= lastMessageId) return;
                const senderType = data.senderRole === 'staff' ? 'admin' : 'user';
                if (senderType === 'admin' && finalizeMatchingPendingMessage(data.body || '', messageId)) {
                    lastMessageId = messageId;
                    scrollToBottom();
                    return;
                }
                appendMessage({
                    id: messageId,
                    text: data.body || '',
                    senderType,
                    senderName: data.senderName || '',
                    timestamp: Number(data.createdAt || Date.now())
                });
                lastMessageId = messageId;
                scrollToBottom();
            });

            twcRealtimeSource.addEventListener('conversation:unread', (event) => {
                const data = readEventData(event);
                handleUnreadCount(data.unreadMessageCount ?? data.unreadCount, data.latestMessageId);
                refreshConversationListRealtime();
            });

            twcRealtimeSource.addEventListener('end', () => {
                closeTwoWayRealtimeSource();
                scheduleTwoWayRealtimeReconnect(900);
            });

            twcRealtimeSource.onerror = () => {
                closeTwoWayRealtimeSource();
                scheduleTwoWayRealtimeReconnect(3000);
            };
        }

        async function pollUpdates() {
            if (document.hidden || fallbackPollInFlight || isTwoWayRealtimeOpen()) return;
            fallbackPollInFlight = true;
            try {
                const response = await fetch(API_BASE + 'chat-get-unread-count.php?scope=' + encodeURIComponent(PAGE_MODE));
                const data = await response.json();
                if (data.success) {
                    handleUnreadCount(data.unreadMessageCount ?? data.unreadCount, data.latestMessageId);
                }
            } catch (e) {}

            try {
                await refreshConversationListRealtime();
            } finally {
                fallbackPollInFlight = false;
            }
        }

        // --- DOM Helpers ---

        function createConversationElement(conv) {
            const item = document.createElement('tr');
            item.className = 'conversation-item conversation-row-item';
            if (REPORT_TABLE_MODE) {
                item.classList.add(`incident-row-priority-${incidentPriorityMeta(conv).level}`);
            }
            if (currentStatus === 'closed' || currentStatus === 'completed') item.classList.add('closed');
            if (String(conv.id) === String(currentConversationId)) item.classList.add('active');
            
            item.setAttribute('data-conversation-id', conv.id);
            item._conversationData = conv;
            
            item.innerHTML = getConversationHTML(conv);
            
            item.addEventListener('click', function(event) {
                const rowData = this._conversationData || conv;
                const assignedToOther = Number(rowData?.assignedTo || 0) > 0
                    && Number(rowData.assignedTo) !== Number(ADMIN_ID || 0);
                if (assignedToOther) {
                    event.stopPropagation();
                    showToast('Conversation in use', `${rowData.assignedAdminName || 'Another admin'} is handling this conversation.`);
                    return;
                }
                if (event.target.closest('.transfer-report-btn')) {
                    event.stopPropagation();
                    transferConversationReport(rowData);
                    return;
                }
                if (event.target.closest('.delete-conversation-btn')) {
                    event.stopPropagation();
                    openDeleteConversationModal(rowData);
                    return;
                }
                openConversation(conv.id, rowData, this);
            });
            
            return item;
        }
        
        function getConversationHTML(conv) {
            const guestBadge = conv.isGuest
                ? '<span class="list-chip list-chip-guest" style="background:#e67e22;color:white;padding:2px 6px;border-radius:4px;font-size:0.7rem;font-weight:700;margin-left:0.25rem;">GUEST</span>'
                : '';
            const concernLabel = PAGE_MODE === 'general_enquiries' ? 'GENERAL' : String(conv.userConcern || '');
            const concernBadge = concernLabel
                ? `<span class="list-chip list-chip-concern" style="background:#2ecc71;color:white;padding:2px 6px;border-radius:4px;font-size:0.7rem;font-weight:700;margin-left:0.25rem;">${escapeHtml(concernLabel)}</span>`
                : '';
            const callBadge = conv.hasCall
                ? '<span class="list-chip list-chip-call" style="background:#3498db;color:white;padding:2px 6px;border-radius:4px;font-size:0.7rem;font-weight:700;margin-left:0.25rem;"><i class="fas fa-phone"></i> Call</span>'
                : '';
            const unreadBadge = conv.unreadCount > 0
                ? `<span class="list-chip list-chip-unread" style="background:#e74c3c;color:white;padding:2px 6px;border-radius:4px;font-size:0.7rem;font-weight:700;margin-left:0.25rem;">${conv.unreadCount}</span>`
                : '';
            const workflowRaw = (conv.workflowStatus || '').toLowerCase();
            const workflowLabelMap = {
                open: 'Open', active: 'Open', in_progress: 'Assigned', waiting_user: 'Pending',
                pending: 'Pending', resolved: 'Completed', completed: 'Completed', closed: 'Closed'
            };
            const workflowClassMap = {
                open: 'workflow-open', active: 'workflow-open', in_progress: 'workflow-progress',
                waiting_user: 'workflow-waiting', pending: 'workflow-waiting',
                resolved: 'workflow-resolved', completed: 'workflow-resolved', closed: 'workflow-closed'
            };
            const workflowLabel = workflowLabelMap[workflowRaw] || 'In Queue';
            const workflowClass = workflowClassMap[workflowRaw] || 'workflow-open';
            const statusBadge = `<span class="workflow-pill ${workflowClass}">${workflowLabel}</span>`;
            const assignedAdmin = conv.assignedAdminName
                ? `<span class="assigned-admin-pill"><i class="fas fa-user-shield"></i> ${escapeHtml(conv.assignedAdminName)}</span>`
                : '<span class="assigned-admin-empty">Unassigned</span>';
            const timestamp = getConversationTimestamp(conv);
            const displayTime = timestamp
                ? `${new Date(timestamp).toLocaleDateString([], { month: 'short', day: '2-digit' })} ${new Date(timestamp).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}`
                : '';
            const location = conv.userLocation
                ? escapeHtml(conv.userLocation)
                : '<span style="opacity:0.5;">Not specified</span>';
            const lastMsg = conv.lastMessage
                ? escapeHtml(conv.lastMessage)
                : '<span style="opacity:0.5;font-style:italic;">No messages</span>';
            const priorityCell = REPORT_TABLE_MODE
                ? `<td style="padding:0.85rem 0.75rem;vertical-align:middle;">${incidentPriorityBadgeHtml(conv)}</td>`
                : '';
            const canTransferReport = REPORT_TABLE_MODE
                && !['waiting_user', 'pending', 'resolved', 'completed', 'closed'].includes(workflowRaw);
            const transferAction = canTransferReport
                ? `<button class="btn btn-secondary transfer-report-btn" data-conversation-id="${conv.id}" style="padding:0.35rem 0.65rem;font-size:0.75rem;border-radius:4px;cursor:pointer;margin-right:0.35rem;">
                       <i class="fas fa-share-from-square"></i> Transfer
                   </button>`
                : '';

            return `
                <td style="padding:0.85rem 0.75rem;vertical-align:middle;">
                    <div style="display:flex;align-items:center;gap:0.35rem;">
                        <span class="status-dot"></span>
                        <strong>${escapeHtml(conv.userName || 'Unknown')}</strong>
                        ${guestBadge} ${concernBadge} ${callBadge} ${unreadBadge}
                    </div>
                    ${conv.userPhone ? `<div style="font-size:0.75rem;opacity:0.6;margin-top:0.15rem;"><i class="fas fa-phone" style="font-size:0.7rem;"></i> ${escapeHtml(conv.userPhone)}</div>` : ''}
                </td>
                <td style="padding:0.85rem 0.75rem;vertical-align:middle;max-width:150px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                    <i class="fas fa-map-marker-alt" style="color:var(--primary-color-1);font-size:0.8rem;"></i> ${location}
                </td>
                <td style="padding:0.85rem 0.75rem;vertical-align:middle;max-width:220px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                    ${lastMsg}
                    <div style="font-size:0.7rem;opacity:0.5;margin-top:0.15rem;">${displayTime}</div>
                </td>
                ${priorityCell}
                <td style="padding:0.85rem 0.75rem;vertical-align:middle;">${assignedAdmin}</td>
                <td style="padding:0.85rem 0.75rem;vertical-align:middle;">${statusBadge}</td>
                <td style="padding:0.85rem 0.75rem;vertical-align:middle;text-align:right;">
                    ${transferAction}
                    <button class="btn btn-secondary delete-conversation-btn" title="Move to Trash Bin" style="padding:0.35rem 0.55rem;font-size:0.75rem;border-radius:4px;cursor:pointer;margin-right:0.35rem;">
                        <i class="fas fa-trash-alt"></i> Delete
                    </button>
                    <button class="btn btn-primary respond-btn" style="padding:0.35rem 0.65rem;font-size:0.75rem;border-radius:4px;cursor:pointer;background:var(--primary-color-1);color:white;border:none;">
                        <i class="fas fa-reply"></i> Open Chat
                    </button>
                </td>
            `;
        }
        function updateIncidentPriorityControl(data) {
            const control = document.getElementById('incidentPriorityControl');
            const badge = document.getElementById('incidentPriorityBadge');
            const button = document.getElementById('incidentPriorityButton');
            const menu = document.getElementById('incidentPriorityMenu');
            const transferBtn = document.getElementById('transferConversationBtn');
            if (transferBtn) {
                transferBtn.style.display = REPORT_TABLE_MODE && data ? 'inline-flex' : 'none';
                transferBtn.disabled = !data;
            }
            if (!control || !badge || !button || !menu) return;

            if (!REPORT_TABLE_MODE || !data) {
                control.style.display = 'none';
                menu.hidden = true;
                button.setAttribute('aria-expanded', 'false');
                return;
            }

            const meta = incidentPriorityMeta(data);
            badge.className = `incident-priority-badge incident-priority-${meta.level}`;
            badge.textContent = `${meta.label} ${meta.score}`;
            button.dataset.priority = meta.level;
            button.disabled = false;
            control.style.display = 'inline-flex';
        }

        function setTransferModalBusy(busy) {
            const confirmBtn = document.getElementById('twcTransferConfirmBtn');
            const cancelBtn = document.getElementById('twcTransferCancelBtn');
            if (confirmBtn) {
                confirmBtn.disabled = busy;
                confirmBtn.innerHTML = busy
                    ? '<i class="fas fa-spinner fa-spin"></i> Sending...'
                    : '<i class="fas fa-share-from-square"></i> Transfer';
            }
            if (cancelBtn) cancelBtn.disabled = busy;
            const descriptionEl = document.getElementById('twcTransferDescription');
            if (descriptionEl) descriptionEl.disabled = busy;
        }

        function setTransferModalMessage(message, state = '') {
            const el = document.getElementById('twcTransferMessage');
            if (!el) return;
            el.textContent = message || '';
            el.className = `twc-transfer-modal__message ${state}`.trim();
        }

        async function fetchTransferConversationMessages(conversationId) {
            try {
                const res = await fetch(`${API_BASE}chat-get-messages.php?conversationId=${encodeURIComponent(conversationId)}&lastMessageId=0`, {
                    cache: 'no-store'
                });
                const result = await res.json();
                return result && result.success && Array.isArray(result.messages) ? result.messages : [];
            } catch (error) {
                console.warn('Unable to load messages for transfer summary:', error);
                return [];
            }
        }

        function cleanTransferSummaryText(value) {
            return String(value || '')
                .replace(/\[(CALL_ENDED|CALL_DECLINED|CALL_TRANSFERRED|TRANSFERRED_PENDING|AUTO_TRANSFERRED_TO_ERS|ERS_STATUS)\]/gi, '')
                .replace(/<[^>]*>/g, ' ')
                .replace(/\s+/g, ' ')
                .trim();
        }

        function buildTransferConversationSummary(data = {}) {
            const priorityMeta = incidentPriorityMeta(data);
            const lines = [
                'Emergency report conversation summary',
                `Citizen: ${cleanTransferSummaryText(data.userName || data.caller?.name || 'Guest User')}`,
                `Phone: ${cleanTransferSummaryText(data.userPhone || data.caller?.phone || 'Not provided')}`,
                `Emergency type: ${cleanTransferSummaryText(data.category || data.department || data.userConcern || 'Emergency report')}`,
                `Location: ${cleanTransferSummaryText(data.userLocation || data.caller?.address || 'Not specified')}`,
                `Priority: ${priorityMeta.label} ${priorityMeta.score}`
            ];
            const messages = Array.isArray(data.transferMessages) ? data.transferMessages : [];
            const usefulMessages = messages
                .map(msg => ({
                    speaker: cleanTransferSummaryText(msg.senderName || msg.senderType || 'Unknown'),
                    text: cleanTransferSummaryText(msg.text || msg.message_text || msg.message || ''),
                    time: Number(msg.timestamp || 0) > 0 ? new Date(Number(msg.timestamp)).toLocaleString() : ''
                }))
                .filter(msg => msg.text !== '')
                .slice(-14);
            if (usefulMessages.length) {
                lines.push('', 'Conversation history:');
                usefulMessages.forEach(msg => {
                    const stamp = msg.time ? ` (${msg.time})` : '';
                    lines.push(`- ${msg.speaker}${stamp}: ${msg.text}`);
                });
            } else if (data.lastMessage) {
                lines.push('', `Latest message: ${cleanTransferSummaryText(data.lastMessage)}`);
            }
            return lines.filter(line => line !== null && line !== undefined).join('\n').trim();
        }
        function closeTransferModal() {
            const modal = document.getElementById('twcTransferModal');
            if (!modal) return;
            modal.classList.remove('active');
            modal.setAttribute('aria-hidden', 'true');
            setTransferModalBusy(false);
        }

        let selectedReportBarangay = '';

        function getSelectedReportBarangay() {
            return String(selectedReportBarangay || '').trim();
        }

        function setReportBarangaySelection(value) {
            selectedReportBarangay = String(value || '').trim();
            const input = document.getElementById('reportBarangaySearch');
            const selected = document.getElementById('reportBarangaySelected');
            const results = document.getElementById('reportBarangayResults');
            if (input) input.value = selectedReportBarangay;
            if (selected) selected.textContent = selectedReportBarangay ? `Selected: ${selectedReportBarangay}` : 'No barangay selected';
            if (results) results.style.display = 'none';
        }

        function renderReportBarangayResults(query = '') {
            const results = document.getElementById('reportBarangayResults');
            if (!results) return;
            const needle = String(query || '').trim().toLowerCase();
            const matches = QC_CALL_BARANGAYS
                .filter(name => !needle || name.toLowerCase().includes(needle));
            if (!matches.length) {
                results.innerHTML = '<div style="padding:10px 12px; font-size:12px; opacity:.75;">No Quezon City barangay found.</div>';
                results.style.display = 'block';
                return;
            }
            results.innerHTML = matches.map(name => `
                <button type="button" class="report-barangay-option" data-barangay="${String(name).replace(/"/g, '&quot;')}" style="width:100%; border:0; border-bottom:1px solid rgba(255,255,255,.08); padding:9px 12px; background:transparent; color:#fff; text-align:left; font-weight:700; cursor:pointer;">
                    ${name}
                </button>
            `).join('');
            results.style.display = 'block';
            results.querySelectorAll('.report-barangay-option').forEach(btn => {
                btn.addEventListener('click', () => setReportBarangaySelection(btn.dataset.barangay || ''));
            });
        }

        function bindReportBarangaySelector() {
            const input = document.getElementById('reportBarangaySearch');
            if (!input || input.dataset.bound === '1') return;
            input.dataset.bound = '1';
            input.addEventListener('focus', () => renderReportBarangayResults(input.value));
            input.addEventListener('input', () => {
                selectedReportBarangay = '';
                const selected = document.getElementById('reportBarangaySelected');
                if (selected) selected.textContent = 'Choose a barangay from the search results.';
                renderReportBarangayResults(input.value);
            });
            document.addEventListener('click', (event) => {
                const wrapper = document.getElementById('reportBarangaySelectorGroup');
                const results = document.getElementById('reportBarangayResults');
                if (wrapper && results && !wrapper.contains(event.target)) results.style.display = 'none';
            });
        }

        function openTransferModal(data) {
            const modal = document.getElementById('twcTransferModal');
            if (!modal) return Promise.resolve(false);

            setReportBarangaySelection('');
            bindReportBarangaySelector();

            const citizenEl = document.getElementById('twcTransferCitizen');
            const typeEl = document.getElementById('twcTransferType');
            const locationEl = document.getElementById('twcTransferLocation');
            const priorityEl = document.getElementById('twcTransferPriority');
            const descriptionGroup = document.getElementById('twcTransferDescriptionGroup');
            const descriptionEl = document.getElementById('twcTransferDescription');
            const priorityMeta = incidentPriorityMeta(data);
            if (citizenEl) citizenEl.textContent = data?.userName || data?.caller?.name || 'Guest User';
            if (typeEl) typeEl.textContent = data?.category || data?.department || data?.userConcern || 'Emergency report';
            if (locationEl) locationEl.textContent = data?.userLocation || data?.caller?.address || 'Not specified';
            if (priorityEl) {
                priorityEl.className = `incident-priority-badge incident-priority-${priorityMeta.level}`;
                priorityEl.textContent = `${priorityMeta.label} ${priorityMeta.score}`;
            }
            if (descriptionGroup) descriptionGroup.hidden = false;
            if (descriptionEl) {
                descriptionEl.value = buildTransferConversationSummary(data) || String(data?.description || data?.lastMessage || '').trim();
                descriptionEl.setAttribute('aria-invalid', 'false');
            }

            setTransferModalMessage('Confirm transfer to the response team system.');
            setTransferModalBusy(false);
            modal.classList.add('active');
            modal.setAttribute('aria-hidden', 'false');

            return new Promise(resolve => {
                const confirmBtn = document.getElementById('twcTransferConfirmBtn');
                const cancelBtn = document.getElementById('twcTransferCancelBtn');
                const cleanup = (value) => {
                    if (confirmBtn) confirmBtn.removeEventListener('click', onConfirm);
                    if (cancelBtn) cancelBtn.removeEventListener('click', onCancel);
                    modal.removeEventListener('click', onBackdrop);
                    document.removeEventListener('keydown', onKeydown);
                    if (!value) closeTransferModal();
                    resolve(value);
                };
                const onConfirm = () => {
                    const incidentBarangay = getSelectedReportBarangay();
                    if (!incidentBarangay) {
                        setTransferModalMessage('Please select the incident barangay before transferring.', 'error');
                        return;
                    }
                    if (!isSanAgustinBarangay(incidentBarangay)) {
                        setTransferModalMessage('Emergency Response System integration is not yet available for this barangay.', 'error');
                        return;
                    }
                    const description = String(descriptionEl?.value || '').trim();
                    if (!description) {
                        descriptionEl?.setAttribute('aria-invalid', 'true');
                        setTransferModalMessage('Please enter a description before transferring.', 'error');
                        descriptionEl?.focus();
                        return;
                    }
                    cleanup({ description, incidentBarangay });
                };
                const onCancel = () => cleanup(false);
                const onBackdrop = (event) => {
                    if (event.target === modal) cleanup(false);
                };
                const onKeydown = (event) => {
                    if (event.key === 'Escape') cleanup(false);
                };
                if (confirmBtn) confirmBtn.addEventListener('click', onConfirm);
                if (cancelBtn) cancelBtn.addEventListener('click', onCancel);
                modal.addEventListener('click', onBackdrop);
                document.addEventListener('keydown', onKeydown);
                setTimeout(() => descriptionEl?.focus(), 0);
            });
        }

        function formatTransferError(result, fallback = 'Transfer failed.') {
            const parts = [];
            const compact = (value, max = 180) => {
                let text = String(value || '').replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim();
                return text.length > max ? text.slice(0, max) + '...' : text;
            };
            if (result?.message) parts.push(compact(result.message));
            const integration = result?.integration || {};
            if (integration.httpStatus) parts.push(`HTTP ${integration.httpStatus}`);
            if (integration.response) {
                let responseText = String(integration.response);
                try {
                    const decoded = JSON.parse(responseText);
                    responseText = decoded.message || decoded.error || JSON.stringify(decoded);
                } catch (e) {}
                parts.push(compact(responseText));
            }
            return parts.filter(Boolean).join(' | ') || fallback;
        }

        async function transferConversationReport(conversationData = null) {
            const data = conversationData || currentConversationData;
            const conversationId = data?.id || currentConversationId;
            if (!conversationId) {
                showToast('Transfer unavailable', 'Select a report before transferring.');
                return;
            }
            const transferData = { ...data, transferMessages: await fetchTransferConversationMessages(conversationId) };
            const transferForm = await openTransferModal(transferData);
            if (!transferForm) return;
            const priorityMeta = incidentPriorityMeta(data);
            const reportTransferId = `conversation-${conversationId}-${Date.now()}`;

            const payload = {
                event: 'emergency_report_transfer',
                transferType: 'report',
                transfer_type: 'report',
                transferId: reportTransferId,
                transfer_id: reportTransferId,
                callId: null,
                conversationId,
                room: null,
                socketUrl: null,
                socketPath: null,
                emergencyType: data?.category || data?.department || data?.userConcern || '',
                incidentBarangay: transferForm.incidentBarangay,
                barangay: transferForm.incidentBarangay,
                description: transferForm.description,
                priority: priorityMeta.level,
                priorityColor: priorityMeta.color,
                incidentPriority: {
                    score: priorityMeta.score,
                    priority: priorityMeta.level,
                    label: priorityMeta.label,
                    color: priorityMeta.color,
                    hex: priorityMeta.hex,
                    manual: priorityMeta.manual
                },
                caller: {
                    id: data?.userId || null,
                    name: data?.userName || null,
                    phone: data?.userPhone || null,
                    email: data?.userEmail || null,
                    address: data?.userLocation || null,
                    isGuest: !!data?.isGuest
                },
                location: {
                    address: data?.userLocation || null
                }
            };

            try {
                setTransferModalBusy(true);
                setTransferModalMessage('Sending report to response team...');
                const res = await fetch(transferApiUrl(), {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const result = await readApiResponse(res);
                if (!result.success) {
                    setTransferModalBusy(false);
                    setTransferModalMessage(formatTransferError(result), 'error');
                    return;
                }
                notifyErsReportTransfer(result.data || payload, result);
                setTransferModalMessage(result.integration?.configured ? 'Transfer notification sent.' : 'Transfer payload prepared.', 'success');
                if (currentConversationData && String(currentConversationData.id) === String(conversationId)) {
                    currentConversationData.assignedTo = null;
                }
                resetConversationsAndReload();
                if (currentMainView === 'transfers') {
                    loadTransferredRecords();
                }
                if (String(currentConversationId) === String(conversationId)) {
                    closeChatPanel();
                }
                setTimeout(closeTransferModal, 1100);
            } catch (e) {
                setTransferModalBusy(false);
                setTransferModalMessage('Transfer failed.', 'error');
            }
        }

        function parseTransferIntegrationResponse(result) {
            const responseText = result?.integration?.response;
            if (!responseText || typeof responseText !== 'string') return {};
            try {
                const decoded = JSON.parse(responseText);
                return decoded && typeof decoded === 'object' ? decoded : {};
            } catch (e) {
                return {};
            }
        }

        function notifyErsReportTransfer(transferPayload, result = {}) {
            const s = ensureSocket();
            if (!s) return;
            const responseData = parseTransferIntegrationResponse(result);
            const caller = transferPayload?.caller || {};
            const location = transferPayload?.locationData || transferPayload?.location || {};
            const notice = {
                ...(transferPayload || {}),
                event: 'emergency_report_transfer',
                transfer_type: 'report',
                transferType: 'report',
                transferId: transferPayload?.transferId || transferPayload?.transfer_id || responseData.transfer_id || responseData.reference_no || '',
                transfer_id: transferPayload?.transfer_id || transferPayload?.transferId || responseData.transfer_id || responseData.reference_no || '',
                callId: '',
                call_id: '',
                room: '',
                socketUrl: '',
                socketPath: '',
                incident_id: responseData.incident_id || null,
                reference_no: responseData.reference_no || '',
                incident_status: responseData.status || 'pending',
                caller_name: transferPayload?.caller_name || caller.name || '',
                caller_phone: transferPayload?.caller_phone || caller.phone || '',
                location: transferPayload?.location || transferPayload?.location_address || location.address || '',
                transferredAt: transferPayload?.transferredAt || new Date().toISOString()
            };
            if (s.connected) {
                s.emit('ers-transfer-notify', notice);
            } else {
                s.once('connect', () => s.emit('ers-transfer-notify', notice));
            }
        }

        function setConversationLocked(locked, message = '') {
            const input = document.getElementById('messageInput');
            const sendBtn = document.getElementById('sendButton');
            if (input) {
                input.disabled = locked;
                input.placeholder = locked ? (message || 'Locked by another admin') : 'Type a message...';
                input.style.cursor = locked ? 'not-allowed' : 'text';
            }
            if (sendBtn) sendBtn.disabled = locked;
            const releaseBtn = document.getElementById('releaseConversationBtn');
            if (releaseBtn) releaseBtn.style.display = locked ? 'none' : 'inline-flex';
        }

        async function claimConversationForAdmin(conversationId) {
            if (!conversationId) return false;
            try {
                const res = await fetch(API_BASE + 'chat-claim.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ conversationId, action: 'claim' })
                });
                const data = await res.json().catch(() => ({}));
                if (!data.success) {
                    if (currentConversationData && String(currentConversationData.id) === String(conversationId)) {
                        currentConversationData.assignedTo = null;
                    }
                    setConversationLocked(true, data.message || 'Locked by another admin');
                    return false;
                }
                if (currentConversationData && String(currentConversationData.id) === String(conversationId)) {
                    currentConversationData.assignedTo = data.assignedTo || ADMIN_ID;
                    currentConversationData.assignedAdminName = data.adminName || ADMIN_USERNAME;
                    currentConversationData.workflowStatus = 'in_progress';
                }
                setConversationLocked(false);
                return true;
            } catch (e) {
                if (currentConversationData && String(currentConversationData.id) === String(conversationId)) {
                    currentConversationData.assignedTo = null;
                }
                setConversationLocked(true, 'Unable to claim report');
                return false;
            }
        }

        async function releaseConversationForOtherAdmin() {
            if (!currentConversationId) return;
            const releaseBtn = document.getElementById('releaseConversationBtn');
            const conversationId = currentConversationId;
            try {
                if (releaseBtn) {
                    releaseBtn.disabled = true;
                    releaseBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Leaving...';
                }
                const res = await fetch(API_BASE + 'chat-claim.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ conversationId, action: 'release' })
                });
                const data = await res.json().catch(() => ({}));
                if (!data.success) {
                    throw new Error(data.message || 'Failed to leave conversation.');
                }
                if (currentConversationData) currentConversationData.assignedTo = null;
                closeChatPanel();
                resetConversationsAndReload();
                showToast('Conversation released', 'Another admin can now take this conversation.');
            } catch (e) {
                showToast('Unable to leave conversation', e.message || 'Please try again.');
            } finally {
                if (releaseBtn) {
                    releaseBtn.disabled = false;
                    releaseBtn.innerHTML = '<i class="fas fa-door-open"></i> Hand Over';
                }
            }
        }

        function openDeleteConversationModal(conversation = null) {
            const target = conversation || currentConversationData;
            if (!target || !target.id) return;

            pendingDeleteConversation = target;
            const noun = PAGE_MODE === 'general_enquiries' ? 'enquiry' : 'report';
            document.getElementById('twcDeleteModalTitle').textContent =
                'Are you sure you want to delete this ' + noun + '?';
            document.getElementById('twcDeleteModalSubtitle').textContent =
                'This ' + noun + ' and its messages will move to Trash Bin.';
            document.getElementById('twcDeleteReason').value = '';
            document.getElementById('twcDeleteDetails').value = '';
            const message = document.getElementById('twcDeleteMessage');
            message.className = 'twc-transfer-modal__message';
            message.textContent = 'Select a reason to continue.';

            const modal = document.getElementById('twcDeleteModal');
            modal.classList.add('active');
            modal.setAttribute('aria-hidden', 'false');
            document.body.style.overflow = 'hidden';
            document.getElementById('twcDeleteReason').focus();
        }

        function closeDeleteConversationModal() {
            const modal = document.getElementById('twcDeleteModal');
            modal.classList.remove('active');
            modal.setAttribute('aria-hidden', 'true');
            document.body.style.overflow = '';
            pendingDeleteConversation = null;
        }

        async function confirmDeleteConversation() {
            if (!pendingDeleteConversation || !pendingDeleteConversation.id) return;

            const reason = document.getElementById('twcDeleteReason').value;
            const details = document.getElementById('twcDeleteDetails').value.trim();
            const message = document.getElementById('twcDeleteMessage');
            const button = document.getElementById('twcDeleteConfirmBtn');

            if (!reason) {
                message.className = 'twc-transfer-modal__message error';
                message.textContent = 'Choose a deletion reason.';
                document.getElementById('twcDeleteReason').focus();
                return;
            }
            if (reason === 'other' && !details) {
                message.className = 'twc-transfer-modal__message error';
                message.textContent = 'Add details when Other is selected.';
                document.getElementById('twcDeleteDetails').focus();
                return;
            }

            const conversationId = Number(pendingDeleteConversation.id);
            button.disabled = true;
            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Deleting...';

            try {
                const response = await fetch(API_BASE + 'chat-trash.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'trash', conversationId, reason, details })
                });
                const data = await readApiResponse(response);
                if (!data.success) {
                    throw new Error(data.message || 'Unable to delete this conversation.');
                }

                closeDeleteConversationModal();
                if (String(currentConversationId || '') === String(conversationId)) {
                    if (currentConversationData) currentConversationData.assignedTo = null;
                    currentConversationId = null;
                    currentConversationData = null;
                    closeMobileChat();
                    document.getElementById('chatUserName').textContent = 'Select a conversation';
                    document.getElementById('chatUserStatus').textContent = '';
                    document.getElementById('chatMessages').innerHTML =
                        '<div style="text-align:center;color:var(--text-secondary-1);padding:3rem;"><i class="fas fa-comments" style="font-size:3rem;opacity:.2;"></i><p>Select a conversation from the list to start messaging</p></div>';
                    setupInputState(true);
                    setupCloseButton(true);
                    updateIncidentPriorityControl(null);
                    const deleteBtn = document.getElementById('deleteConversationBtn');
                    if (deleteBtn) deleteBtn.style.display = 'none';
                    const releaseBtn = document.getElementById('releaseConversationBtn');
                    if (releaseBtn) releaseBtn.style.display = 'none';
                }

                resetConversationsAndReload();
                showToast('Moved to Trash Bin', data.message || 'Conversation deleted.');
            } catch (error) {
                message.className = 'twc-transfer-modal__message error';
                message.textContent = error.message || 'Unable to delete this conversation.';
            } finally {
                button.disabled = false;
                button.innerHTML = '<i class="fas fa-trash-alt"></i> Move to Trash';
            }
        }
        async function updateIncidentPriorityManual(level) {
            if (!currentConversationId || !REPORT_TABLE_MODE) return;
            const button = document.getElementById('incidentPriorityButton');
            const menu = document.getElementById('incidentPriorityMenu');
            if (button) button.disabled = true;
            if (menu) {
                menu.hidden = true;
                button?.setAttribute('aria-expanded', 'false');
            }
            try {
                const res = await fetch(API_BASE + 'chat-update-incident-priority.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({ conversationId: currentConversationId, priority: level })
                });
                const d = await res.json();
                if (!d.success) throw new Error(d.message || 'Priority update failed');

                const item = document.querySelector(`.conversation-item[data-conversation-id="${currentConversationId}"]`);
                if (item && item._conversationData) {
                    item._conversationData.incidentPriority = d.incidentPriority;
                    item._conversationData.incidentPriorityScore = d.incidentPriority.score;
                    item._conversationData.incidentPriorityLevel = d.incidentPriority.priority;
                    item._conversationData.incidentPriorityColor = d.incidentPriority.color;
                    item._conversationData.incidentPriorityManual = true;
                    item.classList.remove(
                        'incident-row-priority-critical',
                        'incident-row-priority-high',
                        'incident-row-priority-urgent',
                        'incident-row-priority-moderate',
                        'incident-row-priority-low'
                    );
                    item.classList.add(`incident-row-priority-${d.incidentPriority.priority}`);
                    item.innerHTML = getConversationHTML(item._conversationData);
                    updateIncidentPriorityControl(item._conversationData);
                } else {
                    updateIncidentPriorityControl({ incidentPriority: d.incidentPriority });
                }
                resetConversationsAndReload();
            } catch (e) {
                console.error(e);
                alert('Failed to update incident priority');
            } finally {
                if (button) button.disabled = false;
            }
        }

        function toggleIncidentPriorityMenu(forceOpen = null) {
            const button = document.getElementById('incidentPriorityButton');
            const menu = document.getElementById('incidentPriorityMenu');
            if (!button || !menu) return;
            const shouldOpen = forceOpen === null ? menu.hidden : Boolean(forceOpen);
            menu.hidden = !shouldOpen;
            button.setAttribute('aria-expanded', shouldOpen ? 'true' : 'false');
        }

        // --- Chat Interaction ---

        async function openConversation(id, data, element) {
            if (
                currentConversationId &&
                String(currentConversationId) !== String(id) &&
                currentConversationData &&
                Number(currentConversationData.assignedTo || 0) === Number(ADMIN_ID || 0)
            ) {
                showToast('Finish the active conversation', 'Click Hand Over before opening another conversation.');
                return;
            }
            currentConversationId = id;
            currentConversationData = data || null;
            lastMessageId = 0;
            
            // UI Selection
            document.querySelectorAll('.conversation-item').forEach(i => i.classList.remove('active'));
            if (element) element.classList.add('active');
            else {
                // Try finding it if element not passed
                const found = document.querySelector(`.conversation-item[data-conversation-id="${id}"]`);
                if (found) found.classList.add('active');
            }
            
            // Mobile View Toggle
            document.getElementById('communicationContainer').classList.add('chat-active');
            
            // Header Info
            const nameEl = document.getElementById('chatUserName');
            const statusEl = document.getElementById('chatUserStatus');
            
            const guestBadge = data.isGuest ? ' <span class="list-chip list-chip-guest">GUEST</span>' : '';
            nameEl.innerHTML = (data.userName || 'Unknown') + guestBadge;
            
            // Detailed Info for Status Bar
            const details = [];
            if (data.userPhone) details.push(data.userPhone);
            if (data.userLocation) details.push(data.userLocation);
            if (data.ipAddress) details.push(data.ipAddress);
            
            // Device Info Parsing
            let devStr = '';
            if (data.deviceInfo) {
               let d = data.deviceInfo;
               if (typeof d === 'string') try { d = JSON.parse(d); } catch(e){}
               if (d && typeof d === 'object') {
                   const parts = [d.device_type, d.os, d.browser].filter(Boolean);
                   if (parts.length) devStr = parts.join(' - ');
               }
            }
            if (devStr) details.push(devStr);
            
            statusEl.textContent = details.join(' | ') || 'Online';
            updateIncidentPriorityControl(data);
            
            // Input/Button State
            const isClosed = (data.status === 'closed');
            setupInputState(isClosed);
            setupCloseButton(isClosed);
            const releaseBtn = document.getElementById('releaseConversationBtn');
            if (releaseBtn) releaseBtn.style.display = isClosed ? 'none' : 'inline-flex';
            const deleteBtn = document.getElementById('deleteConversationBtn');
            if (deleteBtn) deleteBtn.style.display = 'inline-flex';
            if (!isClosed) {
                const claimed = await claimConversationForAdmin(id);
                if (!claimed) {
                    return;
                }
                if (currentStatus === 'open' || currentStatus === 'active') {
                    switchTab('assigned');
                } else {
                    loadConversations(false, false, true);
                }
            }

            // Load Messages
            await loadMessages(id, true);
            closeTwoWayRealtimeSource();
            connectTwoWayRealtime();
        }
        
        function setupInputState(isClosed) {
            const input = document.getElementById('messageInput');
            const btn = document.getElementById('sendButton');
            
            if (input) {
                input.disabled = isClosed;
                input.placeholder = isClosed ? 'Conversation closed' : 'Type a message...';
                input.style.cursor = isClosed ? 'not-allowed' : 'text';
            }
            if (btn) {
                btn.disabled = isClosed;
            }
        }
        
        function setupCloseButton(isClosed) {
            const btn = document.getElementById('toggleStatusBtn');
            if (!btn) return;
            if (REPORT_TABLE_MODE) {
                btn.style.display = 'none';
                return;
            }
            
            btn.style.display = 'inline-flex';
            btn.className = isClosed ? 'btn btn-sm btn-primary' : 'btn btn-sm btn-success';
            btn.innerHTML = isClosed ? '<i class="fas fa-undo"></i> Re-open' : '<i class="fas fa-check"></i> Close';
            
            // Clean listener
            const newBtn = btn.cloneNode(true);
            btn.parentNode.replaceChild(newBtn, btn);
            const freshBtn = document.getElementById('toggleStatusBtn');
            
            freshBtn.onclick = async () => {
                if (!confirm(isClosed ? 'Re-open this chat?' : 'Close this chat?')) return;
                
                freshBtn.disabled = true;
                try {
                    const newStatus = isClosed ? 'active' : 'closed';
                    const res = await fetch(API_BASE + 'chat-update-status.php', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/json'},
                        body: JSON.stringify({ conversationId: currentConversationId, status: newStatus })
                    });
                    const d = await res.json();
                    
                    if (d.success) {
                        // Remove item from current list immediately
                        const item = document.querySelector(`.conversation-item[data-conversation-id="${currentConversationId}"]`);
                        if (item) {
                            item.style.opacity = '0';
                            item.style.height = '0';
                            item.style.margin = '0';
                            item.style.padding = '0';
                            
                            // Remove after animation
                            setTimeout(() => {
                                item.remove();
                                // Handle empty list state
                                const list = document.getElementById('conversationsList');
                                if (list && list.children.length === 0) {
                                    list.innerHTML = `<p style="text-align: center; color: var(--text-secondary-1); padding: 2rem;">No ${currentStatus} conversations</p>`;
                                }
                            }, 300);
                        }

                        // Clear chat window and reset state
                        document.getElementById('chatMessages').innerHTML = '<div style="text-align: center; color: var(--text-secondary-1); padding: 3rem; display: flex; flex-direction: column; align-items: center; justify-content: center; height: 100%;"><div style="font-size: 3rem; opacity: 0.2; margin-bottom: 1rem;"><i class="fas fa-comments"></i></div><p>Select a conversation from the list to start messaging</p></div>';
                        document.getElementById('chatUserName').textContent = 'Select a conversation';
                        document.getElementById('chatUserStatus').textContent = '';
                        updateIncidentPriorityControl(null);
                        document.getElementById('messageInput').disabled = true;
                        document.getElementById('messageInput').placeholder = 'Type a message...';
                        document.getElementById('sendButton').disabled = true;
                        freshBtn.style.display = 'none';
                        
                        // Clear active ID
                        currentConversationId = null;

                        // Close mobile chat view if open
                        closeMobileChat();
                    } else {
                        alert('Error updating status');
                    }
                } catch(e) {
                    console.error(e);
                    alert('Network error');
                } finally {
                    freshBtn.disabled = false;
                }
            };
        }
        
        function linkify(text) {
            const urlPattern = /(\b(https?|ftp|file):\/\/[-A-Z0-9+&@#\/%?=~_|!:,.;]*[-A-Z0-9+&@#\/%=~_|])/ig;
            return text.replace(urlPattern, '<a href="$1" target="_blank" rel="noopener noreferrer" style="color: #4c8a89; text-decoration: underline;">$1</a>');
        }

        async function loadMessages(id, initial = false) {
            const container = document.getElementById('chatMessages');
            if (initial) {
                container.innerHTML = '<div style="display:flex; justify-content:center; padding:2rem;"><i class="fas fa-spinner fa-spin"></i></div>';
                lastDisplayedDate = null; // Reset date tracking when opening new conversation
            }
            
            // Clear polling
            if (messageInterval) clearInterval(messageInterval);
            
            const fetchMsgs = async (isFirstLoad) => {
                if (currentConversationId !== id) return;
                if (!isFirstLoad && (document.hidden || messagePollInFlight)) return;
                messagePollInFlight = true;
                try {
                    const res = await fetch(`${API_BASE}chat-get-messages.php?conversationId=${id}&lastMessageId=${lastMessageId}`);
                    const data = await res.json();
                    
                    if (data.success && Array.isArray(data.messages)) {
                        // Remove spinner on first load
                        if (container.querySelector('.fa-spinner')) container.innerHTML = '';
                        
                        // Fix: Only show "No messages yet" if this is the FIRST load and the list is truly empty
                        // This prevents polling from overwriting existing messages with "No messages yet"
                        if (isFirstLoad && data.messages.length === 0) {
                            container.innerHTML = '<p style="text-align:center; color:#999; padding:2rem;">No messages yet.</p>';
                        }
                        
                        let added = false;
                        const existingIds = new Set(Array.from(container.querySelectorAll('.message')).map(el => parseInt(el.dataset.id)));
                        
                        data.messages.forEach(msg => {
                            if (msg.id > lastMessageId && !existingIds.has(msg.id)) {
                                if ((msg.senderType === 'admin' || msg.senderType === 'sent') && finalizeMatchingPendingMessage(msg.text || '', msg.id)) {
                                    lastMessageId = Math.max(lastMessageId, msg.id);
                                    added = true;
                                    return;
                                }
                                appendMessage(msg);
                                lastMessageId = Math.max(lastMessageId, msg.id);
                                added = true;
                            }
                        });
                        
                        if (added) scrollToBottom();
                    }
                } catch (e) { console.error(e); }
                finally { messagePollInFlight = false; }
            };
            
            await fetchMsgs(initial); // Initial call with passed state
            messageInterval = setInterval(() => fetchMsgs(false), MESSAGE_POLL_MS);
        }
        
        function appendMessage(msg) {
            const container = document.getElementById('chatMessages');
            if (!container || !msg) return null;
            const numericMessageId = Number(msg.id || 0);
            if (numericMessageId > 0) {
                const existingMessage = container.querySelector(`.message[data-id="${numericMessageId}"]`);
                if (existingMessage) return existingMessage;
            }
            // Remove placeholders
            const p = container.querySelector('p');
            if (p) p.remove();
            // Remove center container placeholders if any
            if (container.children.length === 1 && container.children[0].style.textAlign === 'center') {
                container.innerHTML = '';
            }

            const msgDate = new Date(msg.timestamp);
            const dateStr = msgDate.toLocaleDateString('en-US', { 
                year: 'numeric', 
                month: 'long', 
                day: 'numeric' 
            });

            // Show date separator if the date has changed
            if (lastDisplayedDate !== dateStr) {
                const separator = document.createElement('div');
                separator.className = 'date-separator';
                separator.textContent = dateStr;
                container.appendChild(separator);
                lastDisplayedDate = dateStr;
            }
            
            // Check if this is a system message (like "Call ended")
            const isSystemMessage = msg.senderType === 'system' || (msg.text && msg.text.startsWith('[CALL_ENDED]'));
            
            if (isSystemMessage) {
                // Render as system message (like Messenger's call ended style)
                const div = document.createElement('div');
                div.className = 'message system-message';
                div.dataset.id = msg.id;
                if (msg.clientId) div.dataset.clientId = String(msg.clientId);
                if (msg.pending) div.dataset.pending = '1';
                
                // Extract the actual message text (remove [CALL_ENDED] prefix)
                let messageText = msg.text || '';
                let isCallEnded = false;
                if (messageText.startsWith('[CALL_ENDED]')) {
                    messageText = messageText.replace('[CALL_ENDED]', '').trim();
                    isCallEnded = true;
                }
                
                // Determine the display text
                let displayText = 'Call ended';
                if (isCallEnded) {
                    // Extract duration if present
                    const durationMatch = messageText.match(/Duration:\s*([^\s]+)/);
                    if (durationMatch) {
                        displayText = `Call ended - ${durationMatch[1]}`;
                    } else {
                        displayText = 'Call ended';
                    }
                } else {
                    displayText = messageText || 'System message';
                }
                
                const timeStr = msgDate.toLocaleTimeString('en-US', { 
                    hour: 'numeric', 
                    minute: '2-digit', 
                    hour12: true 
                });
                
                div.innerHTML = `
                    <div class="system-message-content">
                        <div class="system-message-header">
                            <div class="system-message-icon">
                                <i class="fas fa-phone-slash"></i>
                            </div>
                            <span class="system-message-text">${escapeHtml(displayText)}</span>
                        </div>
                        <div class="system-message-meta">${timeStr}</div>
                    </div>
                `;
                container.appendChild(div);
                return div;
            }
            
            const div = document.createElement('div');
            const type = (msg.senderType === 'admin' || msg.senderType === 'sent') ? 'admin' : 'user';
            div.className = `message ${type}`;
            div.dataset.id = msg.id;
            if (msg.clientId) div.dataset.clientId = String(msg.clientId);
            if (msg.pending) div.dataset.pending = '1';
            
            const name = type === 'admin' ? ADMIN_USERNAME : (msg.senderName || 'User');
            const avatar = type === 'admin' ? ADMIN_AVATAR : `https://ui-avatars.com/api/?name=${encodeURIComponent(name)}&background=6c757d&color=fff&size=64`;
            
            const timeStr = msgDate.toLocaleTimeString('en-US', { 
                hour: 'numeric', 
                minute: '2-digit', 
                hour12: true 
            });
            const fullStamp = `${msgDate.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })} - ${timeStr}`;
            const attachmentUrl = sanitizeAttachmentUrl(msg.imageUrl || msg.attachmentUrl || null);
            const normalizedText = (msg.text || '').toString().trim();
            const attachmentMimeRaw = (msg.attachmentMime || msg.attachment_mime || '').toString().trim().toLowerCase();
            const attachmentMime = attachmentMimeRaw || null;
            const attachmentHintMatch = normalizedText.match(/^\[(photo|video|email|attachment)\]/i);
            const attachmentHint = attachmentHintMatch ? attachmentHintMatch[1].toLowerCase() : '';
            const isImageAttachment = !!(attachmentUrl && (
                (attachmentMime && attachmentMime.indexOf('image/') === 0) ||
                (!attachmentMime && (
                    attachmentHint === 'photo' ||
                    /\.(png|jpe?g|gif|webp|bmp|avif)(\?|$)/i.test(attachmentUrl)
                ))
            ));
            const isVideoAttachment = !!(attachmentUrl && (
                (attachmentMime && attachmentMime.indexOf('video/') === 0) ||
                (!attachmentMime && (
                    attachmentHint === 'video' ||
                    /\.(mp4|webm|ogv|mov|avi|mkv)(\?|$)/i.test(attachmentUrl)
                ))
            ));
            const isEmailAttachment = !!(attachmentUrl && (
                attachmentMime === 'message/rfc822' ||
                attachmentMime === 'application/eml' ||
                (!attachmentMime && attachmentHint === 'email') ||
                /\.eml(\?|$)/i.test(attachmentUrl)
            ));
            const hideAttachmentPlaceholder = attachmentUrl && /^\[(photo|video|email|attachment)\]/i.test(normalizedText);

            let bodyHtml = '';
            if (normalizedText && !hideAttachmentPlaceholder) {
                bodyHtml += `<div class="message-text">${linkify(escapeHtml(normalizedText))}</div>`;
            }
            if (attachmentUrl) {
                if (isVideoAttachment) {
                    bodyHtml += `
                        <div class="message-attachment-link">
                            <video class="message-attachment-image" controls preload="metadata" playsinline>
                                <source src="${attachmentUrl}"${attachmentMime ? ` type="${attachmentMime}"` : ''}>
                                Your browser does not support video playback.
                            </video>
                        </div>
                    `;
                } else if (isImageAttachment) {
                    bodyHtml += `
                        <a href="${attachmentUrl}" target="_blank" rel="noopener noreferrer" class="message-attachment-link">
                            <img src="${attachmentUrl}" alt="Incident attachment" class="message-attachment-image">
                        </a>
                    `;
                } else {
                    const fileLabel = isEmailAttachment ? 'Open email attachment (.eml)' : 'Open attachment';
                    const fileIcon = isEmailAttachment ? 'fa-envelope-open-text' : 'fa-paperclip';
                    bodyHtml += `
                        <a href="${attachmentUrl}" target="_blank" rel="noopener noreferrer" class="message-attachment-link">
                            <span class="message-attachment-file"><i class="fas ${fileIcon}"></i> ${fileLabel}</span>
                        </a>
                    `;
                }
            }
            if (!bodyHtml) {
                bodyHtml = `<div class="message-text">${linkify(escapeHtml(normalizedText || 'Attachment'))}</div>`;
            }
            
            div.innerHTML = `
                <img src="${avatar}" class="message-avatar" alt="">
                <div class="message-content">
                    ${bodyHtml}
                    <div class="message-meta">
                        ${fullStamp}
                    </div>
                </div>
            `;
            container.appendChild(div);
            return div;
        }

        function finalizeMatchingPendingMessage(text, serverMessageId) {
            const container = document.getElementById('chatMessages');
            const id = Number(serverMessageId || 0);
            if (!container || !id) return false;
            const existing = container.querySelector(`.message[data-id="${id}"]`);
            if (existing) return true;
            const normalized = String(text || '').trim();
            const pending = Array.from(container.querySelectorAll('.message.admin[data-pending="1"]')).find(el => {
                const pendingText = (el.querySelector('.message-text')?.textContent || '').trim();
                return pendingText === normalized;
            });
            if (!pending) return false;
            pending.dataset.id = String(id);
            delete pending.dataset.pending;
            return true;
        }

        function removePendingMessage(clientId) {
            if (!clientId) return;
            const container = document.getElementById('chatMessages');
            if (!container) return;
            const escapedClientId = window.CSS && typeof CSS.escape === 'function'
                ? CSS.escape(String(clientId))
                : String(clientId).replace(/[\\"]/g, '\\$&');
            const pending = container.querySelector(`.message[data-client-id="${escapedClientId}"]`);
            if (pending) pending.remove();
        }
        
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        function sanitizeAttachmentUrl(url) {
            if (!url) return null;
            const raw = String(url).trim();
            if (!raw) return null;
            if (/^blob:/i.test(raw)) {
                return raw;
            }
            if (/^data:(image|video)\//i.test(raw)) {
                return raw;
            }
            const path = String(window.location.pathname || '').replace(/\\/g, '/');
            const lower = path.toLowerCase();
            let appBasePath = '';
            let markerMatched = false;
            for (const marker of ['/users/', '/admin/', '/php/']) {
                const idx = lower.indexOf(marker);
                if (idx === 0) {
                    markerMatched = true;
                    appBasePath = '';
                    break;
                }
                if (idx > 0) {
                    markerMatched = true;
                    appBasePath = path.slice(0, idx).replace(/\/+$/, '');
                    break;
                }
            }
            if (!markerMatched) {
                const dir = path.replace(/\/[^/]*$/, '');
                if (dir && dir !== '/') {
                    appBasePath = dir.replace(/\/+$/, '');
                }
            }

            if (/^[A-Za-z0-9_-]{24,80}$/.test(raw)) {
                return (appBasePath ? appBasePath : '') + '/USERS/api/chat-attachment.php?id=' + encodeURIComponent(raw);
            }
            if (!/^https?:\/\//i.test(raw) && /chat-attachment\.php/i.test(raw)) {
                const idFromPathMatch = raw.match(/chat-attachment\.php\/([A-Za-z0-9_-]{12,80})\/?$/i);
                const queryIndex = raw.indexOf('?');
                const queryText = queryIndex >= 0 ? raw.slice(queryIndex + 1).trim() : '';
                const query = queryText || (idFromPathMatch ? ('id=' + encodeURIComponent(idFromPathMatch[1])) : '');
                return (appBasePath ? appBasePath : '') + '/USERS/api/chat-attachment.php' + (query ? ('?' + query) : '');
            }

            if (raw.startsWith('/')) {
                if (
                    appBasePath &&
                    /^\/(USERS|ADMIN|PHP)\//i.test(raw) &&
                    raw.indexOf(appBasePath + '/') !== 0
                ) {
                    return appBasePath + raw;
                }
                return raw;
            }
            if (/^(USERS|ADMIN|PHP)\//i.test(raw)) {
                return appBasePath ? (appBasePath + '/' + raw) : ('/' + raw);
            }
            try {
                const parsed = new URL(raw, window.location.href);
                if (parsed.protocol === 'http:' || parsed.protocol === 'https:') {
                    return parsed.href;
                }
            } catch (e) {
                return null;
            }
            return null;
        }
        
        function scrollToBottom() {
            const c = document.getElementById('chatMessages');
            c.scrollTop = c.scrollHeight;
        }

        // --- Sending ---
        
        async function sendMessage() {
            const input = document.getElementById('messageInput');
            const sendButton = document.getElementById('sendButton');
            const text = input.value.trim();
            if (!text || !currentConversationId || chatSendInFlight) return;

            chatSendInFlight = true;
            if (sendButton) sendButton.disabled = true;
            input.value = '';
            input.focus();

            const clientId = `admin-${currentConversationId}-${Date.now()}-${Math.random().toString(36).slice(2)}`;
            appendMessage({
                id: 0,
                clientId,
                pending: true,
                text: text,
                senderType: 'admin',
                timestamp: Date.now(),
                senderName: ADMIN_USERNAME
            });
            scrollToBottom();

            try {
                const fd = new FormData();
                fd.append('text', text);
                fd.append('conversationId', currentConversationId);

                const res = await fetch(API_BASE + 'chat-send.php', { method: 'POST', body: fd });
                const raw = await res.text();
                let d = {};
                try { d = raw ? JSON.parse(raw) : {}; } catch (parseError) {
                    console.warn('Invalid chat-send response:', raw);
                    await loadMessages(currentConversationId, false);
                    return;
                }

                if (d.success) {
                    if (d.messageId) {
                        finalizeMatchingPendingMessage(text, d.messageId);
                        lastMessageId = Math.max(lastMessageId, Number(d.messageId));
                    }
                    refreshConversationListRealtime();
                } else {
                    removePendingMessage(clientId);
                    input.value = text;
                    if (d.locked) setConversationLocked(true, d.message || 'Locked by another admin');
                    alert(d.message || 'Failed to send');
                }
            } catch (e) {
                console.error('Send error', e);
                await loadMessages(currentConversationId, false);
            } finally {
                chatSendInFlight = false;
                if (sendButton) sendButton.disabled = false;
            }
        }
        
        // Listeners
        document.getElementById('sendButton').onclick = sendMessage;
        document.getElementById('messageInput').onkeydown = e => {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                sendMessage();
            }
        };
        
        // Init
        document.addEventListener('DOMContentLoaded', () => {
            const deptFilter = document.getElementById('deptFilter');
            const deptTopNav = document.getElementById('departmentTopNav');
            const primarySwitch = document.getElementById('twcPrimarySwitch');
            if (primarySwitch) {
                primarySwitch.addEventListener('click', (event) => {
                    const chip = event.target.closest('.twc-primary-chip');
                    if (!chip) return;
                    const view = chip.getAttribute('data-twc-view') || 'conversations';
                    setPrimaryView(view);
                });
            }

            fillChatbotLogsFiltersUi();

            const logsSearch = document.getElementById('twcLogsSearch');
            if (logsSearch) {
                logsSearch.addEventListener('input', () => {
                    if (chatbotLogsSearchTimer) {
                        clearTimeout(chatbotLogsSearchTimer);
                    }
                    chatbotLogsSearchTimer = setTimeout(() => {
                        applyChatbotLogsFilters(true);
                    }, 320);
                });
            }

            [
                'twcLogsIncidentType',
                'twcLogsLanguage',
                'twcLogsEmergency',
                'twcLogsScope',
                'twcLogsDateFrom',
                'twcLogsDateTo'
            ].forEach((id) => {
                const node = document.getElementById(id);
                if (!node) return;
                node.addEventListener('change', () => {
                    applyChatbotLogsFilters(true);
                });
            });

            const logsResetBtn = document.getElementById('twcLogsResetBtn');
            if (logsResetBtn) {
                logsResetBtn.addEventListener('click', resetChatbotLogsFilters);
            }

            const logsRefreshBtn = document.getElementById('twcLogsRefreshBtn');
            if (logsRefreshBtn) {
                logsRefreshBtn.addEventListener('click', () => {
                    loadChatbotLogs(false);
                });
            }

            const logsPrevBtn = document.getElementById('twcLogsPrevBtn');
            if (logsPrevBtn) {
                logsPrevBtn.addEventListener('click', () => {
                    if (chatbotLogsState.page <= 1) return;
                    chatbotLogsState.page -= 1;
                    loadChatbotLogs(false);
                });
            }

            const logsNextBtn = document.getElementById('twcLogsNextBtn');
            if (logsNextBtn) {
                logsNextBtn.addEventListener('click', () => {
                    if (chatbotLogsState.page >= chatbotLogsState.totalPages) return;
                    chatbotLogsState.page += 1;
                    loadChatbotLogs(false);
                });
            }

            const logsBody = document.getElementById('twcChatbotLogsBody');
            if (logsBody) {
                logsBody.addEventListener('click', (event) => {
                    const button = event.target.closest('.twc-log-open-btn');
                    if (!button) return;
                    const logId = Number(button.getAttribute('data-log-id') || 0);
                    if (logId > 0) {
                        openChatbotLogModalById(logId);
                    }
                });
            }

            const modalCloseBtn = document.getElementById('twcLogModalClose');
            if (modalCloseBtn) {
                modalCloseBtn.addEventListener('click', closeChatbotLogModal);
            }

            const modalBackdrop = document.getElementById('twcLogModalBackdrop');
            if (modalBackdrop) {
                modalBackdrop.addEventListener('click', closeChatbotLogModal);
            }

            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape') {
                    closeChatbotLogModal();
                }
            });

            document.addEventListener('visibilitychange', () => {
                if (!document.hidden && currentMainView === 'chatbotLogs') {
                    loadChatbotLogs(false, { silent: true });
                }
                if (!document.hidden) {
                    connectTwoWayRealtime();
                    pollUpdates();
                    if (currentConversationId) loadMessages(currentConversationId, false);
                }
            });

            window.addEventListener('beforeunload', () => {
                stopChatbotLogsRealtime();
            });

            if (deptFilter) {
                const urlDept = new URLSearchParams(window.location.search).get('dept');
                if (urlDept && Array.from(deptFilter.options).some(o => o.value === urlDept)) {
                    deptFilter.value = urlDept;
                    currentDept = urlDept;
                }
                deptFilter.addEventListener('change', () => {
                    currentDept = deptFilter.value || 'all';
                    setActiveDepartmentNav(currentDept);
                    updateDepartmentQueryParam(currentDept);
                    resetConversationsAndReload();
                });
            }
            if (deptTopNav) {
                deptTopNav.addEventListener('click', (event) => {
                    const chip = event.target.closest('.dept-nav-chip');
                    if (!chip) return;

                    const selectedDept = chip.getAttribute('data-dept') || 'all';
                    if (normalizeDeptKey(selectedDept) === normalizeDeptKey(currentDept)) return;

                    currentDept = selectedDept;
                    if (deptFilter) deptFilter.value = selectedDept;
                    setActiveDepartmentNav(currentDept);
                    updateDepartmentQueryParam(currentDept);
                    resetConversationsAndReload();
                });
            }
            setActiveDepartmentNav(currentDept);
            const topicFilter = document.getElementById('topicFilter');
            if (topicFilter) {
                topicFilter.addEventListener('change', () => {
                    currentTopic = topicFilter.value || 'all';
                    resetConversationsAndReload();
                });
            }
            const priorityFilter = document.getElementById('priorityFilter');
            if (priorityFilter) {
                priorityFilter.addEventListener('change', () => {
                    currentPriority = priorityFilter.value || 'all';
                    resetConversationsAndReload();
                });
            }
            const viewOpenMessagesBtn = document.getElementById('twcViewOpenMessagesBtn');
            if (viewOpenMessagesBtn) {
                viewOpenMessagesBtn.addEventListener('click', () => switchTab('open'));
            }
            const dismissNewMessageBtn = document.getElementById('twcDismissNewMessageBtn');
            if (dismissNewMessageBtn) {
                dismissNewMessageBtn.addEventListener('click', hideNewMessageNotice);
            }

            const transferConversationBtn = document.getElementById('transferConversationBtn');
            if (transferConversationBtn) {
                transferConversationBtn.addEventListener('click', () => transferConversationReport());
            }
            const releaseConversationBtn = document.getElementById('releaseConversationBtn');
            if (releaseConversationBtn) {
                releaseConversationBtn.addEventListener('click', releaseConversationForOtherAdmin);
            }
            const deleteConversationBtn = document.getElementById('deleteConversationBtn');
            if (deleteConversationBtn) {
                deleteConversationBtn.addEventListener('click', () => openDeleteConversationModal());
            }
            document.getElementById('twcDeleteCancelBtn')?.addEventListener('click', closeDeleteConversationModal);
            document.getElementById('twcDeleteConfirmBtn')?.addEventListener('click', confirmDeleteConversation);
            document.getElementById('twcDeleteModal')?.addEventListener('click', (event) => {
                if (event.target.id === 'twcDeleteModal') closeDeleteConversationModal();
            });
            const incidentPriorityButton = document.getElementById('incidentPriorityButton');
            const incidentPriorityMenu = document.getElementById('incidentPriorityMenu');
            if (incidentPriorityButton && incidentPriorityMenu) {
                incidentPriorityButton.addEventListener('click', (event) => {
                    event.stopPropagation();
                    toggleIncidentPriorityMenu();
                });
                incidentPriorityMenu.addEventListener('click', (event) => {
                    const option = event.target.closest('[data-priority]');
                    if (!option) return;
                    event.stopPropagation();
                    updateIncidentPriorityManual(option.getAttribute('data-priority'));
                });
                document.addEventListener('click', (event) => {
                    const control = document.getElementById('incidentPriorityControl');
                    if (!control || control.contains(event.target)) return;
                    toggleIncidentPriorityMenu(false);
                });
            }

            setPrimaryView('conversations', true);

            loadConversations(true);
            connectTwoWayRealtime();
            pollInterval = setInterval(pollUpdates, FALLBACK_POLL_MS); // Fallback when SSE is unavailable/interrupted.
        });
        
    </script>

    <div id="incomingCallModal" style="display:none; position:fixed; right:18px; top:18px; z-index:100001; width:min(420px, 92vw); background:#0f172a; border:1px solid rgba(220,38,38,0.55); border-radius:16px; padding:16px; color:#fff; box-shadow:0 20px 60px rgba(0,0,0,0.55);">
        <div style="display:flex; align-items:flex-start; gap:12px;">
            <div style="width:44px; height:44px; border-radius:12px; background:rgba(220,38,38,0.18); border:1px solid rgba(220,38,38,0.45); display:flex; align-items:center; justify-content:center; flex:0 0 auto;">
                <i class="fas fa-phone-alt" style="color:#fecaca;"></i>
            </div>
            <div style="flex:1; min-width:0;">
                <div style="font-weight:900; letter-spacing:0.6px; text-transform:uppercase; color:#fecaca;">Incoming Emergency Call</div>
                <div id="incomingCallText" style="opacity:0.9; font-size:13px; margin-top:4px;">Someone is calling for emergency assistance.</div>
            </div>
        </div>
        <div style="display:flex; gap:10px; justify-content:flex-end; margin-top:12px;">
            <button id="incomingDeclineBtn" class="btn btn-secondary">Decline</button>
            <button id="incomingAnswerBtn" class="btn btn-primary">Answer</button>
        </div>
    </div>

    <div id="callOverlay" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.55); z-index:100000;">
        <div style="position:absolute; left:50%; top:50%; transform:translate(-50%,-50%); width:min(1400px, 98vw); height:min(900px, 95vh); background:#0f172a; border:1px solid rgba(255,255,255,0.12); border-radius:18px; padding:24px; color:#fff; box-shadow:0 20px 60px rgba(0,0,0,0.5); display:flex; flex-direction:column;">
            <div id="callActiveBanner" style="display:none; margin:-6px 0 12px; padding:8px 12px; border-radius:12px; background:rgba(220,38,38,0.18); border:1px solid rgba(220,38,38,0.45); color:#fecaca; font-weight:800; letter-spacing:0.6px; text-transform:uppercase; text-align:center;">CALL ON ACTIVE</div>

            <div style="display:flex; gap:20px; flex:1; min-height:0;">
                <div style="width:420px; max-width:40%; min-width:380px; border:1px solid rgba(255,255,255,0.10); border-radius:14px; padding:18px; background:rgba(0,0,0,0.18); display:flex; flex-direction:column; gap:14px; overflow-y:auto;">
                    <div style="display:flex; align-items:center; gap:12px;">
                        <div style="width:44px; height:44px; border-radius:12px; background:rgba(58, 118, 117,0.2); display:flex; align-items:center; justify-content:center; flex:0 0 auto;">
                            <i class="fas fa-user" style="color:#3a7675;"></i>
                        </div>
                        <div style="flex:1; min-width:0;">
                            <div style="font-weight:900; letter-spacing:0.4px;">Caller Details</div>
                            <div style="opacity:0.75; font-size:12px;">Account + location info</div>
                        </div>
                    </div>

                    <div style="display:grid; grid-template-columns:110px 1fr; gap:8px 10px; font-size:13px;">
                        <div style="opacity:0.7;">Name</div>
                        <div id="callerName" style="font-weight:700;">-</div>

                        <div style="opacity:0.7;">Phone</div>
                        <div id="callerPhone" style="font-weight:700;">-</div>

                        <div style="opacity:0.7;">Address</div>
                        <div id="callerAddress" style="font-weight:600; opacity:0.95;">-</div>

                        <div style="opacity:0.7;">Location</div>
                        <div id="callerCoords" style="font-weight:600; opacity:0.95;">-</div>
                    </div>

                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:8px; padding:10px; border:1px solid rgba(255,255,255,0.10); border-radius:12px; background:rgba(255,255,255,0.04);">
                        <input id="callerNameInput" type="text" placeholder="Admin edit: caller name" autocomplete="off" style="min-width:0; padding:8px 10px; border:1px solid rgba(255,255,255,0.14); border-radius:9px; background:rgba(255,255,255,0.07); color:#fff; outline:none; font-weight:700;">
                        <input id="callerPhoneInput" type="tel" inputmode="numeric" maxlength="11" pattern="[0-9]{11}" placeholder="09XXXXXXXXX" autocomplete="off" style="min-width:0; padding:8px 10px; border:1px solid rgba(255,255,255,0.14); border-radius:9px; background:rgba(255,255,255,0.07); color:#fff; outline:none; font-weight:700;">
                        <input id="callerAddressInput" type="text" placeholder="Type location or address" style="grid-column:1 / -1; min-width:0; padding:8px 10px; border:1px solid rgba(255,255,255,0.14); border-radius:9px; background:rgba(255,255,255,0.07); color:#fff; outline:none; font-weight:600;">
                        <div id="callBarangaySelector" style="grid-column:1 / -1; display:flex; flex-direction:column; gap:6px; position:relative;">
                            <label for="callBarangaySearch" style="font-size:12px; opacity:0.82;">Incident Barangay</label>
                            <input id="callBarangaySearch" type="text" placeholder="Search Quezon City barangay..." autocomplete="off" style="min-width:0; padding:8px 10px; border:1px solid rgba(255,255,255,0.14); border-radius:9px; background:rgba(255,255,255,0.07); color:#fff; outline:none; font-weight:700;">
                            <div id="callBarangaySelected" style="font-size:12px; opacity:0.76;">No barangay selected</div>
                            <div id="callBarangayResults" style="display:none; position:absolute; left:0; right:0; top:72px; max-height:180px; overflow:auto; border:1px solid rgba(255,255,255,0.16); border-radius:10px; background:#111827; box-shadow:0 16px 34px rgba(0,0,0,.35); z-index:20;"></div>
                        </div>
                    </div>

                    <div style="border-top:1px solid rgba(255,255,255,0.10); padding-top:12px; display:flex; flex-direction:column; gap:10px;">
                        <label style="font-size:12px; opacity:0.8; margin:0;">Emergency Type</label>
                        <select id="emergencyTypeSelect" style="width:100%; padding:10px 12px; border-radius:10px; border:1px solid rgba(255,255,255,0.14); background:rgba(255,255,255,0.08); color:#fff; outline:none;">
                            <option value="" selected>Choose type...</option>
                            <option value="fire">Fire</option>
                            <option value="flood">Flood</option>
                            <option value="rescue">Rescue Assistance</option>
                            <option value="police">Police</option>
                            <option value="medical">Medical</option>
                            <option value="earthquake">Earthquake</option>
                            <option value="other">Other</option>
                        </select>
                        <label style="font-size:12px; opacity:0.8; margin:0;">Incident Description</label>
                        <textarea id="callIncidentDescription" rows="4" placeholder="Write the emergency context, visible hazards, injuries, people affected, or caller notes..." style="width:100%; resize:vertical; min-height:86px; padding:10px 12px; border-radius:10px; border:1px solid rgba(255,255,255,0.14); background:rgba(255,255,255,0.08); color:#fff; outline:none; font-weight:600; line-height:1.35;"></textarea>
                        <div style="display:flex; align-items:center; justify-content:space-between; gap:10px;">
                            <span style="font-size:12px; opacity:0.8;">Auto Priority</span>
                            <span id="callPriorityBadge" class="incident-priority-badge incident-priority-low">LOW 0</span>
                        </div>

                        <div style="display:flex; gap:10px;">
                            <button id="transferCallBtn" class="btn btn-primary" style="flex:1; padding:12px 14px; min-height:48px;">
                                <i class="fas fa-share-from-square"></i> Transfer Call
                            </button>
                        </div>

                        <div id="dispatchStatus" style="font-size:12px; opacity:0.85; min-height:18px;"></div>
                    </div>
                </div>

                <div style="flex:1; min-width:0; display:flex; flex-direction:column;">
                    <!-- Call Header -->
                    <div style="display:flex; align-items:center; gap:12px; flex-shrink:0;">
                        <div id="adminLocalMicIndicator" title="Your microphone activity" style="width:64px; height:64px; border-radius:16px; background:rgba(58, 118, 117,0.28); display:flex; align-items:center; justify-content:center; transition:box-shadow .18s ease, background .18s ease, transform .18s ease; border:1px solid rgba(255,255,255,0.16);">
                            <i class="fas fa-microphone" style="color:#e8fffe; font-size:28px;"></i>
                        </div>
                        <div style="flex:1;">
                            <div style="font-weight:700; font-size:16px;">Emergency Call</div>
                            <div id="callStatus" style="opacity:0.85; font-size:13px;">Connecting...</div>
                        </div>
                        <div id="callTimer" style="font-variant-numeric:tabular-nums; font-weight:700;">00:00</div>
                    </div>

                    <div style="display:flex; gap:10px; margin-top:10px; font-size:13px; opacity:1;">
                        <div id="adminSpeakingLabel" style="display:flex; align-items:center; gap:8px; padding:9px 13px; border-radius:999px; background:rgba(255,255,255,0.08); transition:background .18s ease, color .18s ease, box-shadow .18s ease; font-weight:800;">
                            <i class="fas fa-microphone" style="font-size:18px;"></i><span>You</span>
                        </div>
                        <div id="userSpeakingLabel" style="display:flex; align-items:center; gap:8px; padding:9px 13px; border-radius:999px; background:rgba(255,255,255,0.08); transition:background .18s ease, color .18s ease, box-shadow .18s ease; font-weight:800;">
                            <i class="fas fa-microphone-lines" style="font-size:18px;"></i><span>Caller</span>
                        </div>
                    </div>

                    <!-- Messages Area -->
                    <div id="callMessages" style="flex:1; margin-top:16px; overflow-y:auto; border:1px solid rgba(255,255,255,0.1); border-radius:12px; padding:12px; background:rgba(0,0,0,0.2); min-height:200px;">
                        <div style="text-align:center; opacity:0.6; font-size:12px;">Messages will appear here</div>
                    </div>

                    <!-- Message Input -->
                    <div style="margin-top:12px; display:flex; gap:10px; flex-shrink:0; align-items:center;">
                        <input type="text" id="callMessageInput" placeholder="Type a message..." style="flex:1; padding:10px 12px; border:1px solid rgba(255,255,255,0.18); border-radius:10px; background:rgba(255,255,255,0.08); color:#fff; outline:none;">
                        <button id="callSendMessageBtn" class="btn btn-primary" style="padding:10px 16px; min-height:44px;">Send</button>
                    </div>

                    <!-- Call Controls -->
                    <div style="margin-top:14px; display:flex; gap:10px; justify-content:flex-end; flex-shrink:0;">
                        <button id="endCallBtn" class="btn btn-secondary" disabled style="opacity:0.6; pointer-events:none; min-height:44px;">End Call</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <audio id="remote" autoplay></audio>

    <script src="https://cdn.socket.io/4.7.5/socket.io.min.js"></script>
    <script>
    const SOCKET_IO_PATH = '/socket.io';
    const SIGNALING_URL = (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1' || window.location.hostname.startsWith('192.168.'))
        ? `${window.location.protocol}//${window.location.hostname}:3000`
        : window.location.origin;
    const SOCKET_HEALTH_URL = `${SIGNALING_URL}${SOCKET_IO_PATH}/?EIO=4&transport=polling`;
    console.log('[call][admin] signaling endpoint v3', `${SIGNALING_URL}${SOCKET_IO_PATH}`);

    const WEBRTC_ICE_SERVERS = [
        { urls: 'stun:stun.l.google.com:19302' },
        { urls: 'stun:global.stun.twilio.com:3478' }
        <?php if (!empty($turnUrls)): ?>,
        {
            urls: <?php echo json_encode($turnUrls, JSON_UNESCAPED_SLASHES); ?>,
            username: <?php echo json_encode($turnUsername); ?>,
            credential: <?php echo json_encode($turnCredential); ?>
        }
        <?php endif; ?>
    ];
    const CALL_LOBBY_ROOM = "emergency-lobby";
    let activeCallRoom = null;
    let pendingCallRoom = null;

    function getCallRoom(id = callId) {
        return id ? `emergency-call-${id}` : CALL_LOBBY_ROOM;
    }

    let socket = null;
    let socketBound = false;
    let notificationSound = 'siren';
    let socketRetryCount = 0;
    const MAX_SOCKET_RETRIES = 5;
    let socketServerChecked = false;
    let socketServerAvailable = false;
    let socketServerCheckPromise = null;
    let socketServerLastCheckAt = 0;
    let socketUnavailableNoticeShown = false;

    let _soundCtx = null;
    let _soundOsc = null;
    let _soundGain = null;
    let _soundTimer = null;

    (function primeAudioContext() {
        let primed = false;
        const prime = () => {
            if (primed) return;
            primed = true;
            try {
                const AudioContext = window.AudioContext || window.webkitAudioContext;
                if (!AudioContext) return;
                if (!_soundCtx) _soundCtx = new AudioContext();
                if (_soundCtx && _soundCtx.state === 'suspended') {
                    _soundCtx.resume();
                }
            } catch (e) {}
        };
        document.addEventListener('click', prime, { once: true });
        document.addEventListener('keydown', prime, { once: true });
        document.addEventListener('touchstart', prime, { once: true });
    })();

    async function checkSocketServerAvailability(force = false) {
        const now = Date.now();
        if (!force && socketServerChecked && socketServerAvailable) {
            return true;
        }
        if (!force && socketServerChecked && !socketServerAvailable && (now - socketServerLastCheckAt) < 10000) {
            return false;
        }
        if (socketServerCheckPromise) {
            return socketServerCheckPromise;
        }

        socketServerCheckPromise = (async () => {
            let reachable = false;
            try {
                const controller = new AbortController();
                const timer = setTimeout(() => controller.abort(), 1800);
                const healthUrl = `${SOCKET_HEALTH_URL}&t=${Date.now()}`;
                const response = await fetch(healthUrl, {
                    method: 'GET',
                    mode: 'cors',
                    cache: 'no-store',
                    signal: controller.signal
                });
                clearTimeout(timer);
                if (response.ok) {
                    reachable = true;
                } else {
                    reachable = false;
                }
            } catch (e) {
                reachable = false;
            } finally {
                socketServerChecked = true;
                socketServerLastCheckAt = Date.now();
            }

            socketServerAvailable = reachable;
            if (!socketServerAvailable) {
                if (!socketUnavailableNoticeShown) {
                    socketUnavailableNoticeShown = true;
                    console.warn('[socket] Signaling server is unavailable at', SOCKET_HEALTH_URL);
                }
            } else {
                socketUnavailableNoticeShown = false;
            }

            return socketServerAvailable;
        })();

        try {
            return await socketServerCheckPromise;
        } finally {
            socketServerCheckPromise = null;
        }
    }

    function ensureSocket() {
        if (socket && socket.connected) return socket;
        if (typeof window.io !== 'function') {
            console.error('[socket] Socket.IO library not loaded');
            return null;
        }
        if (!socketServerAvailable) {
            // Probe in the background and avoid noisy websocket errors while server is down.
            checkSocketServerAvailability();
            return null;
        }
        
        // Reset socket if it exists but is disconnected
        if (socket && !socket.connected) {
            socket.disconnect();
            socket = null;
            socketBound = false;
        }
        
        const socketOptions = {
            path: SOCKET_IO_PATH,
            // Prefer polling transport to avoid websocket upgrade failures behind strict proxies.
            transports: ['polling'],
            reconnection: true,
            reconnectionAttempts: MAX_SOCKET_RETRIES,
            reconnectionDelayMax: 2000,
            timeout: 8000
        };

        socket = window.io(SIGNALING_URL, socketOptions);
        
        bindSocketHandlers();
        return socket;
    }

    function bindSocketHandlers() {
        if (!socket || socketBound) return;
        socketBound = true;

        socket.on('connect', () => {
            console.log('[socket] Connected to signaling server');
            if (EMERGENCY_COM_CALL_INTAKE_ENABLED) socket.emit('join', CALL_LOBBY_ROOM);
            if (activeCallRoom) socket.emit('join', activeCallRoom);
            if (pendingCallRoom) socket.emit('join', pendingCallRoom);
            if (callId) {
                if (restoringAdminCall) {
                    requestAdminCallResume(socket);
                } else {
                    socket.emit('resume-admin-call', {
                        callId,
                        room: activeCallRoom || getCallRoom(callId),
                        adminKey: ADMIN_CALL_OWNER_KEY
                    }, result => {
                        console.log('[socket] re-registered admin for active call on reconnect:', result);
                    });
                }
                // Don't run restoreCallSessionsFromDatabase when already in an active call —
                // it would re-queue open sessions and send duplicate offers causing hangups.
            } else if (EMERGENCY_COM_CALL_INTAKE_ENABLED && typeof restoreCallSessionsFromDatabase === 'function') {
                restoreCallSessionsFromDatabase(true);
            }

            socketRetryCount = 0; // Reset retry count on successful connection
        });

        socket.on('disconnect', (reason) => {
            console.warn('[socket] Disconnected:', reason);
            if (callId) {
                setStatus('Connection lost. Attempting to reconnect...');
            }
        });

        socket.on('connect_error', (error) => {
            console.error('[socket] Connection error:', error);
            socketServerAvailable = false;
            socketServerChecked = true;
            socketServerLastCheckAt = Date.now();

            if (socket) {
                socket.disconnect();
                socket = null;
                socketBound = false;
                callSocketListenersBoundFor = null;
            }

            if (callId) {
                socketRetryCount++;
                if (socketRetryCount >= MAX_SOCKET_RETRIES) {
                    setStatus('Connection failed. Please refresh the page.');
                    setEndEnabled(true);
                } else {
                    setStatus(`Connecting... (attempt ${socketRetryCount}/${MAX_SOCKET_RETRIES})`);
                }
            }
        });

        (async function initNotificationSoundPref() {
            try {
                const res = await fetch('../api/profile.php?action=notification_sound_get');
                const data = await res.json();
                if (data && data.success && data.notification_sound) {
                    notificationSound = data.notification_sound;
                }
            } catch (e) {}
        })();
    }

    function _stopAlertSound() {
        try {
            if (_soundTimer) clearInterval(_soundTimer);
            _soundTimer = null;
            if (_soundGain) _soundGain.gain.value = 0;
            if (_soundOsc) {
                try { _soundOsc.stop(); } catch (e) {}
                _soundOsc.disconnect();
            }
        } catch (e) {}
        _soundOsc = null;
        _soundGain = null;
    }

    function _startAlertSound(type) {
        if (type === 'silent') return;
        const AudioContext = window.AudioContext || window.webkitAudioContext;
        if (!AudioContext) return;
        if (!_soundCtx) _soundCtx = new AudioContext();
        const ctx = _soundCtx;

        try {
            if (ctx && ctx.state === 'suspended') ctx.resume();
        } catch (e) {}

        _stopAlertSound();

        const gain = ctx.createGain();
        gain.gain.value = 0;
        gain.connect(ctx.destination);

        const osc = ctx.createOscillator();
        osc.type = 'sine';
        osc.frequency.value = 800;
        osc.connect(gain);
        osc.start();

        _soundOsc = osc;
        _soundGain = gain;

        const setOn = (on) => {
            if (!_soundGain) return;
            _soundGain.gain.value = on ? 0.22 : 0;
        };

        if (type === 'beep') {
            let on = false;
            _soundTimer = setInterval(() => {
                on = !on;
                osc.frequency.value = 880;
                setOn(on);
            }, 260);
            setOn(true);
            return;
        }

        if (type === 'pulse') {
            let step = 0;
            _soundTimer = setInterval(() => {
                step++;
                const on = step % 6 === 0;
                osc.frequency.value = 950;
                setOn(on);
            }, 130);
            return;
        }

        if (type === 'siren') {
            let high = false;
            _soundTimer = setInterval(() => {
                high = !high;
                osc.frequency.value = high ? 1100 : 700;
                setOn(true);
            }, 260);
            setOn(true);
            return;
        }

        setOn(true);
    }

    let pc = null;
    let localStream = null;
    let callId = null;
    let transferInProgress = false;
    let callConversationId = null;
    let callerInfo = null;
    let callerLocation = null;
    let callConnectedAt = null;
    let timerInterval = null;
    let peerDisconnectTimer = null;
    let locationData = null;
    let messages = [];
    let audioActivityMonitors = [];

    let pendingOffer = null;
    let pendingCallId = null;
    let pendingCandidates = [];
    let acceptingCallId = null;
    // Mutex: prevents concurrent WebRTC answer negotiations from fighting each other.
    // When true, any new call to completeAdminCallAnswerFromOffer() returns immediately.
    let callAnswerInFlight = false;
    // Freeze table re-renders while the user is actively hovering/clicking the call buttons.
    // Any DOM replacement during a click causes the event to be lost and the button to jitter.
    let callTableInteractionFrozen = false;
    let callTableInteractionTimer = null;
    const notifiedIncomingCallIds = new Set();
    const ADMIN_CALL_LOCK_KEY = `alertaraqc_active_call_${ADMIN_ID || ADMIN_USERNAME || 'admin'}`;
    const ADMIN_CALL_OWNER_KEY = String(ADMIN_ID || ADMIN_USERNAME || 'admin');
    const ADMIN_CALL_RESUME_TIMEOUT_MS = 25000;
    let restoringAdminCall = false;
    let adminCallResumeTimer = null;
    let queueIncomingOfferFromSocket = null;
    let restoringCallSessionsFromDb = false;
    let lastCallSessionRestoreAt = 0;
    const CALL_OPEN_MAX_AGE_MS = 2 * 60 * 60 * 1000;

    function readAdminCallLock() {
        try {
            const raw = localStorage.getItem(ADMIN_CALL_LOCK_KEY);
            if (!raw) return null;
            const lock = JSON.parse(raw);
            if (!lock || !lock.callId) return null;
            if (Date.now() - Number(lock.startedAt || 0) > 4 * 60 * 60 * 1000) {
                localStorage.removeItem(ADMIN_CALL_LOCK_KEY);
                return null;
            }
            return lock;
        } catch (e) {
            return null;
        }
    }

    function adminHasActiveCall(otherThanCallId = null) {
        if (callId && (!otherThanCallId || callId !== otherThanCallId)) return true;
        const lock = readAdminCallLock();
        return !!(lock && (!otherThanCallId || lock.callId !== otherThanCallId));
    }

    function setAdminCallLock(activeCallId, state = {}) {
        if (!activeCallId) return;
        try {
            const existing = readAdminCallLock();
            localStorage.setItem(ADMIN_CALL_LOCK_KEY, JSON.stringify({
                callId: activeCallId,
                adminId: ADMIN_ID || null,
                adminUsername: ADMIN_USERNAME || 'Admin',
                startedAt: existing?.callId === activeCallId ? Number(existing.startedAt || Date.now()) : Date.now(),
                room: state.room || activeCallRoom || getCallRoom(activeCallId),
                callerInfo: state.callerInfo || callerInfo || existing?.callerInfo || null,
                callerLocation: state.callerLocation || callerLocation || existing?.callerLocation || null,
                conversationId: state.conversationId || callConversationId || existing?.conversationId || null,
                connectedAt: state.connectedAt || callConnectedAt || existing?.connectedAt || null,
                accepted: state.accepted !== false,
                version: 2
            }));
        } catch (e) {}
    }

    function clearAdminCallLock(activeCallId = null) {
        const lock = readAdminCallLock();
        if (!lock) return;
        if (activeCallId && lock.callId !== activeCallId) return;
        try { localStorage.removeItem(ADMIN_CALL_LOCK_KEY); } catch (e) {}
    }

    function restoreAdminCallState() {
        const lock = readAdminCallLock();
        if (!lock?.callId) return false;
        if (lock.accepted !== true || Number(lock.version || 0) < 2) {
            clearAdminCallLock(lock.callId);
            return false;
        }
        callId = lock.callId;
        activeCallRoom = lock.room || getCallRoom(callId);
        callerInfo = lock.callerInfo || null;
        callerLocation = lock.callerLocation || null;
        callConversationId = lock.conversationId || null;
        callConnectedAt = Number(lock.connectedAt || lock.startedAt || Date.now());
        restoringAdminCall = true;
        setOverlayVisible(true);
        setCallActiveBannerVisible(true);
        setStatus('Restoring call connection...');
        setEndEnabled(true);
        renderCallerDetails();
        startTimer();
        return true;
    }

    function requestAdminCallResume(s = socket) {
        if (!restoringAdminCall || !callId || !s?.connected) return;
        s.emit('resume-admin-call', {
            callId,
            room: activeCallRoom || getCallRoom(callId),
            adminKey: ADMIN_CALL_OWNER_KEY
        }, result => {
            if (!result?.ok) {
                // The socket server may have lost state (e.g. PM2 restart) but the
                // database claim is the source of truth. Log a warning but keep the
                // overlay open so the caller can replay their offer into the room.
                console.warn('[call][admin] resume-admin-call not acknowledged cleanly:', result?.reason);
                setStatus('Waiting for caller to reconnect...');
                if (adminCallResumeTimer) clearTimeout(adminCallResumeTimer);
                adminCallResumeTimer = setTimeout(() => {
                    if (!restoringAdminCall) return;
                    setStatus('Caller did not reconnect. You can receive new calls now.');
                    clearAdminCallLock(callId);
                    setTimeout(() => {
                        setOverlayVisible(false);
                        cleanupCall();
                    }, 1200);
                }, ADMIN_CALL_RESUME_TIMEOUT_MS);
                return;
            }
            setStatus('Reconnecting to caller...');
            if (adminCallResumeTimer) clearTimeout(adminCallResumeTimer);
            adminCallResumeTimer = setTimeout(() => {
                if (!restoringAdminCall) return;
                setStatus('Caller did not reconnect. You can receive new calls now.');
                clearAdminCallLock(callId);
                setTimeout(() => {
                    setOverlayVisible(false);
                    cleanupCall();
                }, 1200);
            }, ADMIN_CALL_RESUME_TIMEOUT_MS);
        });
    }

    function setSpeakingIndicator(labelId, indicatorId, active) {
        const label = document.getElementById(labelId);
        if (label) {
            label.style.background = active ? 'rgba(20,184,166,0.35)' : 'rgba(255,255,255,0.08)';
            label.style.color = active ? '#e8fffe' : '#fff';
            label.style.boxShadow = active ? '0 0 0 4px rgba(20,184,166,0.18), 0 0 22px rgba(20,184,166,0.38)' : 'none';
        }
        const indicator = indicatorId ? document.getElementById(indicatorId) : null;
        if (indicator) {
            indicator.style.background = active ? 'rgba(20,184,166,0.48)' : 'rgba(58, 118, 117,0.28)';
            indicator.style.boxShadow = active ? '0 0 0 8px rgba(20,184,166,0.20), 0 0 34px rgba(20,184,166,0.55)' : 'none';
            indicator.style.transform = active ? 'scale(1.08)' : 'scale(1)';
        }
    }

    function monitorAudioActivity(stream, labelId, indicatorId = null) {
        if (!stream || !stream.getAudioTracks || stream.getAudioTracks().length === 0) return;
        try {
            const AudioContext = window.AudioContext || window.webkitAudioContext;
            if (!AudioContext) return;
            const ctx = new AudioContext();
            const source = ctx.createMediaStreamSource(stream);
            const analyser = ctx.createAnalyser();
            analyser.fftSize = 512;
            source.connect(analyser);
            const data = new Uint8Array(analyser.frequencyBinCount);
            let stopped = false;
            const tick = () => {
                if (stopped) return;
                analyser.getByteTimeDomainData(data);
                let sum = 0;
                for (const value of data) {
                    const diff = value - 128;
                    sum += diff * diff;
                }
                const rms = Math.sqrt(sum / data.length);
                setSpeakingIndicator(labelId, indicatorId, rms > 7);
                requestAnimationFrame(tick);
            };
            tick();
            audioActivityMonitors.push(() => {
                stopped = true;
                setSpeakingIndicator(labelId, indicatorId, false);
                try { source.disconnect(); } catch (e) {}
                try { ctx.close(); } catch (e) {}
            });
        } catch (e) {}
    }

    function stopAudioActivityMonitors() {
        audioActivityMonitors.forEach(stop => {
            try { stop(); } catch (e) {}
        });
        audioActivityMonitors = [];
    }

    function normalizePhPhone(value) {
        let digits = String(value || '').replace(/\D/g, '');
        if (digits.startsWith('63') && digits.length >= 12) {
            digits = `0${digits.slice(2)}`;
        } else if (digits.startsWith('9') && digits.length === 10) {
            digits = `0${digits}`;
        }
        return digits.slice(0, 11);
    }

    function isValidPhPhone(value) {
        return /^\d{11}$/.test(String(value || ''));
    }

    function getCallIncidentDescription() {
        return document.getElementById('callIncidentDescription')?.value.trim() || '';
    }

    function currentCallPriority() {
        const recentMessages = messages.slice(-8).map(message => message.text).join(' ');
        return calculateIncidentPriority({
            incident_type: document.getElementById('emergencyTypeSelect')?.value || '',
            description: getCallIncidentDescription(),
            message: recentMessages,
            last_message: recentMessages,
            userConcern: callerInfo?.concern || callerInfo?.emergency_type || '',
            text: [callerInfo?.address, callerLocation?.address].filter(Boolean).join(' ')
        });
    }

    function updateCallPriorityBadge() {
        const badge = document.getElementById('callPriorityBadge');
        if (!badge) return currentCallPriority();
        const priority = currentCallPriority();
        badge.className = `incident-priority-badge incident-priority-${priority.level}`;
        badge.textContent = `${priority.label} ${priority.score}`;
        badge.title = 'Auto priority from emergency type, description, messages, and caller context';
        return priority;
    }

    const QC_CALL_BARANGAYS = [
        'Alicia','Amihan','Apolonio Samson','Baesa','Bagbag','Bagong Lipunan ng Crame','Bagong Pag-asa','Bagong Silangan','Bagumbayan','Bagumbuhay','Bahay Toro','Balingasa','Balong Bato','Batasan Hills','Bayanihan','Blue Ridge A','Blue Ridge B','Botocan','Bungad','Camp Aguinaldo','Capri','Central','Claro','Commonwealth','Culiat','Damar','Damayan','Damayang Lagi','Del Monte','Dioquino Zobel','Doña Aurora','Doña Imelda','Doña Josefa','Duyan-Duyan','E. Rodriguez','East Kamias','Escopa I','Escopa II','Escopa III','Escopa IV','Fairview','Greater Lagro','Gulod','Holy Spirit','Horseshoe','Immaculate Concepcion','Kaligayahan','Kalusugan','Kamuning','Katipunan','Kaunlaran','Krus na Ligas','Laging Handa','Libis','Lourdes','Loyola Heights','Maharlika','Malaya','Mangga','Manresa','Mariana','Mariblo','Marilag','Masagana','Masambong','Matandang Balara','Milagrosa','N.S. Amoranto','Nagkaisang Nayon','Nayong Kanluran','New Era','North Fairview','Novaliches Proper','Obrero','Old Capitol Site','Paang Bundok','Pag-ibig sa Nayon','Paligsahan','Paltok','Pansol','Paraiso','Pasong Putik Proper','Pasong Tamo','Payatas','Phil-Am','Pinagkaisahan','Pinyahan','Project 6','Quirino 2-A','Quirino 2-B','Quirino 2-C','Quirino 3-A','Ramon Magsaysay','Roxas','Sacred Heart','Saint Ignatius','Salvacion','San Agustin','San Antonio','San Bartolome','San Isidro','San Isidro Labrador','San Jose','San Martin de Porres','San Roque','Sangandaan','Santa Cruz','Santa Lucia','Santa Monica','Santa Teresita','Santo Cristo','Santo Domingo','Santo Niño','Sauyo','Siena','Sikatuna Village','Silangan','Socorro','South Triangle','Tagumpay','Talayan','Talipapa','Tandang Sora','Tatalon','Teachers Village East','Teachers Village West','U.P. Campus','U.P. Village','Ugong Norte','Unang Sigaw','Valencia','Vasra','Veterans Village','Villa Maria Clara','West Kamias','West Triangle','White Plains'
    ];
    let selectedCallBarangay = '';

    function getSelectedCallBarangay() {
        return String(selectedCallBarangay || '').trim();
    }

    function isSanAgustinBarangay(value) {
        return String(value || '').trim().toLowerCase() === 'san agustin';
    }

    function setCallBarangaySelection(value) {
        selectedCallBarangay = String(value || '').trim();
        const input = document.getElementById('callBarangaySearch');
        const selected = document.getElementById('callBarangaySelected');
        const results = document.getElementById('callBarangayResults');
        if (input) input.value = selectedCallBarangay;
        if (selected) selected.textContent = selectedCallBarangay ? `Selected: ${selectedCallBarangay}` : 'No barangay selected';
        if (results) results.style.display = 'none';
    }

    function renderCallBarangayResults(query = '') {
        const results = document.getElementById('callBarangayResults');
        if (!results) return;
        const needle = String(query || '').trim().toLowerCase();
        const matches = QC_CALL_BARANGAYS
            .filter(name => !needle || name.toLowerCase().includes(needle));
        if (!matches.length) {
            results.innerHTML = '<div style="padding:10px 12px; font-size:12px; opacity:.75;">No Quezon City barangay found.</div>';
            results.style.display = 'block';
            return;
        }
        results.innerHTML = matches.map(name => `
            <button type="button" class="call-barangay-option" data-barangay="${String(name).replace(/"/g, '&quot;')}" style="width:100%; border:0; border-bottom:1px solid rgba(255,255,255,.08); padding:9px 12px; background:transparent; color:#fff; text-align:left; font-weight:700; cursor:pointer;">
                ${name}
            </button>
        `).join('');
        results.style.display = 'block';
        results.querySelectorAll('.call-barangay-option').forEach(btn => {
            btn.addEventListener('click', () => setCallBarangaySelection(btn.dataset.barangay || ''));
        });
    }

    function bindCallBarangaySelector() {
        const input = document.getElementById('callBarangaySearch');
        if (!input || input.dataset.bound === '1') return;
        input.dataset.bound = '1';
        input.addEventListener('focus', () => renderCallBarangayResults(input.value));
        input.addEventListener('input', () => {
            selectedCallBarangay = '';
            const selected = document.getElementById('callBarangaySelected');
            if (selected) selected.textContent = 'Choose a barangay from the search results.';
            renderCallBarangayResults(input.value);
        });
        document.addEventListener('click', (event) => {
            const wrapper = document.getElementById('callBarangaySelector');
            const results = document.getElementById('callBarangayResults');
            if (wrapper && results && !wrapper.contains(event.target)) results.style.display = 'none';
        });
    }

    function clearCallBarangaySelection() {
        setCallBarangaySelection('');
    }
    function getManualCallerInfo() {
        const phoneInput = document.getElementById('callerPhoneInput');
        if (phoneInput) phoneInput.value = normalizePhPhone(phoneInput.value);
        const manual = {
            name: document.getElementById('callerNameInput')?.value.trim() || '',
            phone: phoneInput?.value.trim() || '',
            address: document.getElementById('callerAddressInput')?.value.trim() || ''
        };
        return {
            ...(callerInfo || {}),
            ...(manual.name ? { name: manual.name } : {}),
            ...(manual.phone && isValidPhPhone(manual.phone) ? { phone: manual.phone } : {}),
            ...(manual.address ? { address: manual.address } : {})
        };
    }

    function getTransferLocationPayload() {
        const caller = getManualCallerInfo();
        return {
            ...(callerLocation || {}),
            ...(caller.address ? { address: caller.address } : {}),
            ...(getSelectedCallBarangay() ? { barangay: getSelectedCallBarangay(), incidentBarangay: getSelectedCallBarangay() } : {})
        };
    }

    async function ensureCallConversationForTransfer(callerPayload, incidentDescription, priorityMetric) {
        if (callConversationId) return callConversationId;
        if (!callId) return null;

        try {
            const durationSec = callConnectedAt ? Math.floor((Date.now() - callConnectedAt) / 1000) : 0;
            const response = await fetch('../api/save-completed-call.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    callId,
                    event: 'transferred',
                    userId: callerPayload?.user_id || callerPayload?.id || null,
                    userName: callerPayload?.name || 'Emergency Call User',
                    userPhone: callerPayload?.phone || null,
                    userLocation: callerPayload?.address || callerLocation?.address || null,
                    location: callerLocation || null,
                    emergencyType: document.getElementById('emergencyTypeSelect')?.value || '',
                    description: incidentDescription,
                    incidentPriority: {
                        score: priorityMetric.score,
                        priority: priorityMetric.level,
                        label: priorityMetric.label,
                        breakdown: priorityMetric.breakdown
                    },
                    duration: durationSec,
                    endedAt: Math.floor(Date.now() / 1000)
                })
            });
            const data = await readApiResponse(response);
            if (data && data.success && data.conversationId) {
                callConversationId = data.conversationId;
                return callConversationId;
            }
            console.warn('[call][admin] Pending report save failed; continuing response-team transfer.', data);
        } catch (error) {
            console.warn('[call][admin] Pending report save error; continuing response-team transfer.', error);
        }
        return null;
    }

    // Messaging functions for admin
    function addMessage(text, sender = 'admin', timestamp = Date.now()) {
        const messagesContainer = document.getElementById('callMessages');
        if (!messagesContainer) return;
        
        // Clear placeholder text if this is the first message
        if (messages.length === 0) {
            messagesContainer.innerHTML = '';
        }
        
        const messageDiv = document.createElement('div');
        messageDiv.style.cssText = `
            margin-bottom: 8px;
            padding: 8px 12px;
            border-radius: 8px;
            background: ${sender === 'admin'
                ? 'color-mix(in srgb, var(--primary-color-1) 22%, transparent)'
                : 'color-mix(in srgb, var(--secondary-color-1) 18%, transparent)'};
            border-left: 3px solid ${sender === 'admin' ? 'var(--primary-color-1)' : 'var(--secondary-color-1)'};
            font-size: 13px;
            line-height: 1.4;
        `;
        
        const time = new Date(timestamp).toLocaleTimeString('en-US', { 
            hour: 'numeric', 
            minute: '2-digit', 
            hour12: true 
        });
        
        const senderName = sender === 'admin' ? 
            'Emergency Services' : 
            'User';
        
        messageDiv.innerHTML = `
            <div style="font-weight: 600; margin-bottom: 2px; font-size: 11px; opacity: 0.8;">
                ${senderName} - ${time}
            </div>
            <div>${text}</div>
        `;
        
        messagesContainer.appendChild(messageDiv);
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
        
        messages.push({ text, sender, timestamp, callId });
        updateCallPriorityBadge();
    }

    async function sendCallMessage() {
        const input = document.getElementById('callMessageInput');
        const text = input.value.trim();
        if (!text || !callId) return;
        
        input.value = '';
        
        // Add to local UI immediately
        addMessage(text, 'admin');
        
        // Send via socket
        const s = ensureSocket();
        if (s) {
            s.emit('call-message', {
                text,
                callId,
                room: activeCallRoom || getCallRoom(),
                sender: 'admin',
                senderName: 'Emergency Services',
                timestamp: Date.now()
            }, activeCallRoom || getCallRoom());
        }
        
        // Log to database using existing chat-send structure
        try {
            const formData = new FormData();
            formData.append('text', text);
            if (callConversationId) formData.append('conversationId', callConversationId);
            
            const response = await fetch('../api/chat-send.php', {
                method: 'POST',
                body: formData
            });
            
            if (!response.ok) {
                console.error('Failed to log message to database');
            }
        } catch (e) {
            console.error('Failed to log message:', e);
        }
    }

    function formatTime(totalSeconds) {
        const m = String(Math.floor(totalSeconds / 60)).padStart(2, '0');
        const s = String(totalSeconds % 60).padStart(2, '0');
        return `${m}:${s}`;
    }

    function setOverlayVisible(visible) {
        document.getElementById('callOverlay').style.display = visible ? 'block' : 'none';
    }

    async function renderCallerDetails() {
        const nameEl = document.getElementById('callerName');
        const phoneEl = document.getElementById('callerPhone');
        const addrEl = document.getElementById('callerAddress');
        const coordsEl = document.getElementById('callerCoords');
        const nameInput = document.getElementById('callerNameInput');
        const phoneInput = document.getElementById('callerPhoneInput');
        const addressInput = document.getElementById('callerAddressInput');

        if (nameEl) nameEl.textContent = callerInfo?.name || '-';
        if (phoneEl) phoneEl.textContent = callerInfo?.phone || '-';

        if (nameInput && !nameInput.value) nameInput.value = callerInfo?.name || '';
        if (phoneInput && !phoneInput.value) phoneInput.value = normalizePhPhone(callerInfo?.phone || '');

        // Fetch address from database if we have user_id or phone
        let address = callerInfo?.address || '';
        if (!address && (callerInfo?.user_id || callerInfo?.phone)) {
            try {
                const userId = callerInfo?.user_id || null;
                const phone = callerInfo?.phone || null;
                
                if (userId || phone) {
                    const params = new URLSearchParams();
                    if (userId) params.append('user_id', userId);
                    if (phone) params.append('phone', phone);
                    
                    const response = await fetch(`${API_BASE}get-caller-address.php?${params.toString()}`);
                    const data = await response.json();
                    
                    if (data.success && data.address) {
                        address = data.address;
                        // Update callerInfo for future use
                        if (callerInfo) callerInfo.address = address;
                    }
                }
            } catch (e) {
                console.error('Error fetching address:', e);
            }
        }
        
        // Fallback: build address from components if still empty
        if (!address) {
            const parts = [];
            if (callerInfo?.house_number) parts.push(callerInfo.house_number);
            if (callerInfo?.street) parts.push(callerInfo.street);
            if (callerInfo?.barangay) parts.push(callerInfo.barangay);
            if (callerInfo?.district) parts.push(callerInfo.district);
            const fallback = parts.filter(Boolean).join(', ');
            if (fallback) address = fallback;
        }
        
        if (addrEl) addrEl.textContent = address || '-';

        if (addressInput && !addressInput.value) addressInput.value = address || '';

        const lat = callerLocation?.lat;
        const lng = callerLocation?.lng;
        if (coordsEl) coordsEl.textContent = (lat != null && lng != null) ? `${lat}, ${lng}` : '-';
        updateCallPriorityBadge();
    }

    function setCallActiveBannerVisible(visible) {
        const el = document.getElementById('callActiveBanner');
        if (!el) return;
        el.style.display = visible ? 'block' : 'none';
    }

    function setIncomingCallModalVisible(visible) {
        const el = document.getElementById('incomingCallModal');
        if (!el) return;
        el.style.display = visible ? 'block' : 'none';
    }

    function setIncomingCallModalText(text) {
        const el = document.getElementById('incomingCallText');
        if (el) el.textContent = text;
    }

    function setIncomingEmergencyCallRowVisible(visible) {
        // The emergency call queue is rendered directly in the Open list; no separate hidden row is used.
        return;
    }

    function callTimestampMs(value) {
        if (!value) return 0;
        const parsed = Date.parse(String(value).replace(' ', 'T'));
        return Number.isFinite(parsed) ? parsed : 0;
    }

    function isFreshIncomingCallTimestamp(timestamp) {
        if (!timestamp) return true;
        return Date.now() - Number(timestamp) <= CALL_OPEN_MAX_AGE_MS;
    }

    function isQueuedCallFresh(call = {}) {
        return isFreshIncomingCallTimestamp(Number(call.updatedAt || call.createdAt || 0));
    }

    function isCallSessionFresh(session = {}) {
        return isFreshIncomingCallTimestamp(callTimestampMs(session.updated_at || session.created_at));
    }
    function pruneStaleIncomingCalls() {
        const closedStatuses = new Set(['assigned', 'accepted', 'pending', 'transferred', 'completed', 'ended', 'declined', 'cancelled', 'canceled']);
        incomingCallQueue.forEach((call, id) => {
            const status = String(call?.status || '').toLowerCase();
            if (closedStatuses.has(status)) incomingCallQueue.delete(id);
        });
        if (!incomingCallQueue.size) _stopAlertSound();
    }
    function normalizeQueuedCall(payload = {}, sdp = null) {
        const incomingCallId = payload && payload.callId ? String(payload.callId) : null;
        if (!incomingCallId) return null;
        return {
            callId: incomingCallId,
            room: payload && payload.room ? payload.room : getCallRoom(incomingCallId),
            sdp: sdp || (payload && payload.sdp ? payload.sdp : payload),
            caller: payload && payload.caller ? payload.caller : null,
            location: payload && payload.location ? payload.location : null,
            conversationId: payload && payload.conversationId ? payload.conversationId : null,
            pendingCandidates: [],
            createdAt: Number(payload.createdAt || payload.created_at || Date.now()),
            updatedAt: Number(payload.updatedAt || payload.updated_at || Date.now()),
            status: 'open'
        };
    }

    function callSessionPayload(extra = {}) {
        return {
            callId: extra.callId || pendingCallId || callId,
            room: extra.room || pendingCallRoom || activeCallRoom || (extra.callId ? getCallRoom(extra.callId) : null),
            caller: extra.caller || callerInfo || null,
            location: extra.location || callerLocation || null,
            conversationId: extra.conversationId || callConversationId || null,
            ...extra
        };
    }

    function callSessionToQueuedOffer(session = {}) {
        const persistedOffer = session.offer_payload && typeof session.offer_payload === 'object' ? session.offer_payload : {};
        const sessionCallId = String(session.call_id || session.callId || persistedOffer.callId || persistedOffer.call_id || '');
        if (!sessionCallId) return null;
        const sessionCreatedAt = callTimestampMs(session.created_at) || Number(persistedOffer.createdAt || persistedOffer.created_at || 0) || Date.now();
        const sessionUpdatedAt = callTimestampMs(session.updated_at) || Number(persistedOffer.updatedAt || persistedOffer.updated_at || 0) || sessionCreatedAt;
        const sessionRoom = session.room || persistedOffer.room || getCallRoom(sessionCallId);
        const sessionCaller = persistedOffer.caller || {
            id: session.caller_user_id || null,
            user_id: session.caller_user_id || null,
            name: session.caller_name || 'Emergency Call User',
            phone: session.caller_phone || '',
            type: session.caller_type || 'guest'
        };
        const sessionLocation = persistedOffer.location || session.location_data || (session.location_text ? { address: session.location_text } : null);
        return {
            ...persistedOffer,
            callId: sessionCallId,
            call_id: sessionCallId,
            room: sessionRoom,
            caller: sessionCaller,
            location: sessionLocation,
            conversationId: session.conversation_id || persistedOffer.conversationId || persistedOffer.conversation_id || null,
            conversation_id: session.conversation_id || persistedOffer.conversation_id || persistedOffer.conversationId || null,
            sdp: persistedOffer.sdp || session.sdp || null,
            createdAt: sessionCreatedAt,
            updatedAt: sessionUpdatedAt,
            restored: true
        };
    }

    async function restoreCallSessionsFromDatabase(force = false) {
        if (!EMERGENCY_COM_CALL_INTAKE_ENABLED || restoringCallSessionsFromDb) return;
        if (!force && Date.now() - lastCallSessionRestoreAt < 3000) return;
        restoringCallSessionsFromDb = true;
        lastCallSessionRestoreAt = Date.now();
        try {
            const response = await fetch(`${API_BASE}call-session.php?action=list`, { credentials: 'same-origin' });
            const data = await response.json().catch(() => null);
            if (!response.ok || !data || data.success === false) return;
            lastDbCallSessions = data;
            const s = ensureSocket();
            const openSessions = Array.isArray(data.open) ? data.open : [];
            // Only populate incoming call queue when admin has no active call.
            // If a call is already active, do not re-queue open sessions — doing so
            // triggers duplicate WebRTC offers which cause the server to emit hangup
            // events that terminate the current call after ~1 second.
            if (!callId) {
                openSessions.forEach(session => {
                    const source = callSessionToQueuedOffer(session);
                    if (!source || incomingCallQueue.has(source.callId)) return;
                    if (pendingCallId === source.callId) return;
                    if (!s || !s.connected) {
                        if (typeof queueIncomingOfferFromSocket === 'function') {
                            queueIncomingOfferFromSocket(source, source.sdp || null, false);
                        }
                    }
                    if (!source.sdp && s?.connected) {
                        s.emit('request-offer', { callId: source.callId, room: source.room, reason: 'admin-db-restore-open' }, source.room);
                    }
                });
            }

            const assignedSessions = Array.isArray(data.assigned) ? data.assigned : [];
            const ownedSession = (ADMIN_ID && assignedSessions.length)
                ? assignedSessions.find(session => session.assigned_admin_id && String(session.assigned_admin_id) === String(ADMIN_ID))
                : null;
            if (ownedSession && !callId) {
                const source = callSessionToQueuedOffer(ownedSession);
                if (source) {
                    callId = source.callId;
                    activeCallRoom = source.room || getCallRoom(callId);
                    callerInfo = source.caller || null;
                    callerLocation = source.location || null;
                    callConversationId = source.conversationId || null;
                    callConnectedAt = Date.parse(ownedSession.answered_at || ownedSession.updated_at || '') || Date.now();
                    restoringAdminCall = true;
                    setAdminCallLock(callId, {
                        room: activeCallRoom,
                        callerInfo,
                        callerLocation,
                        conversationId: callConversationId,
                        connectedAt: callConnectedAt
                    });
                    setOverlayVisible(true);
                    setCallActiveBannerVisible(true);
                    setStatus('Restoring call connection...');
                    setEndEnabled(true);
                    renderCallerDetails();
                    startTimer();
                    if (s?.connected) {
                        s.emit('join', activeCallRoom);
                        requestAdminCallResume(s);
                    }
                }
            } else if (!ownedSession && !callId) {
                localStorage.removeItem(ADMIN_CALL_LOCK_KEY);
            }
        } finally {
            restoringCallSessionsFromDb = false;
            renderIncomingEmergencyCallRow();
        }
    }

    async function syncCallSession(action, payload = {}) {
        try {
            const response = await fetch(`${API_BASE}call-session.php`, {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action, ...payload })
            });
            const data = await response.json().catch(() => null);
            if (!response.ok || !data || data.success === false) {
                return { success: false, error: data?.error || data?.message || `Call session ${action} failed.` };
            }
            return data;
        } catch (e) {
            return { success: false, error: e.message || String(e) };
        }
    }

    function applyQueuedCallToPending(queued) {
        if (!queued) return;
        pendingCallId = queued.callId;
        pendingCallRoom = queued.room || getCallRoom(queued.callId);
        pendingOffer = queued.sdp || null;
        pendingCandidates = queued.pendingCandidates || [];
        callConversationId = queued.conversationId || null;
        callerInfo = queued.caller || null;
        callerLocation = queued.location || null;
        renderCallerDetails();
    }

    function removeQueuedCall(targetCallId) {
        if (!targetCallId) return;
        incomingCallQueue.delete(String(targetCallId));
        if (String(pendingCallId) === String(targetCallId) && !callId) {
            pendingOffer = null;
            pendingCallId = null;
            pendingCallRoom = null;
            pendingCandidates = [];
        }
        renderIncomingEmergencyCallRow();
        renderIncomingCallTableRows();
    }
    function queuedCallTableRowHtml(queued, index = 0, totalCalls = 1) {
        const callerName = queued.caller?.name || 'Emergency Call User';
        const callerPhone = queued.caller?.phone || '';
        const locationText = queued.location?.address || queued.location?.formatted || queued.location?.text || 'Location pending';
        const lastMessage = queued.sdp ? 'Incoming live emergency call' : 'Incoming call - waiting for caller connection';
        const busy = adminHasActiveCall() && queued.callId !== callId;
        const statusText = queued.status === 'pending'
            ? 'Pending'
            : (queued.status === 'assigned' || queued.status === 'accepted')
                ? 'Assigned'
                : 'Open';
        const actionLabel = busy ? 'Finish active call' : 'Answer Call';
        const actionDisabled = busy ? 'disabled' : '';
        const priorityCell = REPORT_TABLE_MODE
            ? '<td style="padding:0.85rem 0.75rem;vertical-align:middle;"><span class="incident-priority-badge incident-priority-critical">LIVE 100</span></td>'
            : '';
        return `
            <tr class="conversation-item emergency-call-table-row incident-row-priority-critical" data-call-id="${escapeHtml(queued.callId)}">
                <td style="padding:0.85rem 0.75rem;vertical-align:middle;">
                    <div style="display:flex;align-items:center;gap:0.35rem;">
                        <span class="status-dot" style="background:#ef4444;box-shadow:0 0 0 4px rgba(239,68,68,.12);"></span>
                        <strong>${escapeHtml(callerName)}</strong>
                        <span class="list-chip list-chip-call" style="background:#ef4444;color:white;padding:2px 6px;border-radius:4px;font-size:0.7rem;font-weight:800;margin-left:0.25rem;"><i class="fas fa-phone-volume"></i> Live Call</span>
                        ${index > 0 ? `<span class="list-chip" style="background:#f59e0b;color:white;padding:2px 6px;border-radius:4px;font-size:0.7rem;font-weight:800;margin-left:0.25rem;">Queue ${index + 1}</span>` : ''}
                    </div>
                    ${callerPhone ? `<div style="font-size:0.75rem;opacity:0.65;margin-top:0.15rem;"><i class="fas fa-phone" style="font-size:0.7rem;"></i> ${escapeHtml(callerPhone)}</div>` : ''}
                </td>
                <td style="padding:0.85rem 0.75rem;vertical-align:middle;max-width:150px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><i class="fas fa-map-marker-alt" style="color:var(--primary-color-1);font-size:0.8rem;"></i> ${escapeHtml(locationText)}</td>
                <td style="padding:0.85rem 0.75rem;vertical-align:middle;max-width:220px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">${escapeHtml(lastMessage)}<div style="font-size:0.7rem;opacity:0.5;margin-top:0.15rem;">Live now</div></td>
                ${priorityCell}
                <td style="padding:0.85rem 0.75rem;vertical-align:middle;"><span class="assigned-admin-empty">Unassigned</span></td>
                <td style="padding:0.85rem 0.75rem;vertical-align:middle;"><span class="workflow-pill workflow-open">${statusText}</span></td>
                <td style="padding:0.85rem 0.75rem;vertical-align:middle;text-align:right;">
                    <button class="btn btn-secondary emergency-call-decline-btn" data-call-id="${escapeHtml(queued.callId)}" style="padding:0.35rem 0.55rem;font-size:0.75rem;border-radius:4px;cursor:pointer;margin-right:0.35rem;"><i class="fas fa-phone-slash"></i> Decline</button>
                    <button class="btn btn-primary emergency-call-accept-btn" data-call-id="${escapeHtml(queued.callId)}" ${actionDisabled} style="padding:0.35rem 0.65rem;font-size:0.75rem;border-radius:4px;cursor:pointer;background:var(--primary-color-1);color:white;border:none;opacity:${busy ? '.55' : '1'};"><i class="fas fa-phone"></i> ${actionLabel}</button>
                </td>
            </tr>
        `;
    }

    let emergencyCallDelegationBound = false;
    function bindEmergencyCallTableButtons(container) {
        if (!container) return;
        const list = document.getElementById('conversationsList');
        if (list && !emergencyCallDelegationBound) {
            emergencyCallDelegationBound = true;
            list.addEventListener('mouseenter', () => {
                // Freeze DOM re-renders while the mouse is inside the table.
                if (callTableInteractionTimer) clearTimeout(callTableInteractionTimer);
                callTableInteractionFrozen = true;
            }, true);
            list.addEventListener('mouseleave', () => {
                // Unfreeze after a brief delay to allow click events to complete.
                if (callTableInteractionTimer) clearTimeout(callTableInteractionTimer);
                callTableInteractionTimer = setTimeout(() => {
                    callTableInteractionFrozen = false;
                    callTableInteractionTimer = null;
                    // Flush any pending render now that interaction is done.
                    lastRenderedTableStateKey = '';
                    renderCallTableForStatus();
                }, 600);
            }, true);
            list.addEventListener('click', (event) => {
                const acceptBtn = event.target.closest('.emergency-call-accept-btn');
                if (acceptBtn) {
                    event.preventDefault();
                    event.stopPropagation();
                    const callId = acceptBtn.getAttribute('data-call-id') || acceptBtn.dataset.callId;
                    console.log('[call-btn-delegated] Accept clicked for call:', callId);
                    if (typeof window.acceptIncomingEmergencyCall === 'function') {
                        window.acceptIncomingEmergencyCall(callId);
                    }
                    return;
                }
                const declineBtn = event.target.closest('.emergency-call-decline-btn');
                if (declineBtn) {
                    event.preventDefault();
                    event.stopPropagation();
                    const callId = declineBtn.getAttribute('data-call-id') || declineBtn.dataset.callId;
                    console.log('[call-btn-delegated] Decline clicked for call:', callId);
                    if (typeof window.declineIncomingEmergencyCall === 'function') {
                        window.declineIncomingEmergencyCall(callId);
                    }
                    return;
                }
            });
        }
    }

    function loggedCallTableRowHtml(session, statusGroup) {
        const callerName = session.caller_name || session.caller?.name || 'Emergency Call User';
        const callerPhone = session.caller_phone || session.caller?.phone || '';
        const locationText = session.location_text || session.location?.address || session.location?.formatted || session.location?.text || 'Location pending';
        
        let priorityCell = '';
        if (REPORT_TABLE_MODE) {
            let offerPayload = {};
            try {
                offerPayload = typeof session.offer_payload === 'string' 
                    ? JSON.parse(session.offer_payload) 
                    : (session.offer_payload || {});
            } catch (e) {}
            const priority = offerPayload.incidentPriority || { level: 'low', label: 'LOW', score: 0 };
            priorityCell = `<td style="padding:0.85rem 0.75rem;vertical-align:middle;"><span class="incident-priority-badge incident-priority-${priority.level || 'low'}">${priority.label || 'LOW'} ${priority.score || 0}</span></td>`;
        }

        const adminName = session.assigned_admin_name || '<span class="assigned-admin-empty">None</span>';
        
        let displayStatus = 'Ended';
        let statusClass = 'workflow-closed';
        if (statusGroup === 'unanswered') {
            displayStatus = 'Unanswered';
            statusClass = 'workflow-pending';
        } else if (statusGroup === 'assigned') {
            displayStatus = 'Active';
            statusClass = 'workflow-open';
        } else if (statusGroup === 'completed') {
            displayStatus = 'Completed';
            statusClass = 'workflow-completed';
        }
        
        let durationText = '';
        if (session.answered_at && session.ended_at) {
            const start = Date.parse(session.answered_at.replace(' ', 'T'));
            const end = Date.parse(session.ended_at.replace(' ', 'T'));
            if (!isNaN(start) && !isNaN(end)) {
                const durationSec = Math.max(0, Math.floor((end - start) / 1000));
                durationText = ` • ${formatTime(durationSec)}`;
            }
        }

        const dateStr = session.updated_at ? new Date(session.updated_at.replace(' ', 'T')).toLocaleString() : '';

        return `
            <tr class="conversation-item emergency-call-table-row">
                <td style="padding:0.85rem 0.75rem;vertical-align:middle;">
                    <div style="display:flex;align-items:center;gap:0.35rem;">
                        <strong>${escapeHtml(callerName)}</strong>
                        <span class="list-chip list-chip-call" style="background:#64748b;color:white;padding:2px 6px;border-radius:4px;font-size:0.7rem;font-weight:800;margin-left:0.25rem;"><i class="fas fa-phone"></i> Call Log</span>
                    </div>
                    ${callerPhone ? `<div style="font-size:0.75rem;opacity:0.65;margin-top:0.15rem;"><i class="fas fa-phone" style="font-size:0.7rem;"></i> ${escapeHtml(callerPhone)}</div>` : ''}
                </td>
                <td style="padding:0.85rem 0.75rem;vertical-align:middle;max-width:150px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><i class="fas fa-map-marker-alt" style="color:var(--primary-color-1);font-size:0.8rem;"></i> ${escapeHtml(locationText)}</td>
                <td style="padding:0.85rem 0.75rem;vertical-align:middle;max-width:220px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">${displayStatus}${durationText}<div style="font-size:0.7rem;opacity:0.5;margin-top:0.15rem;">${dateStr}</div></td>
                ${priorityCell}
                <td style="padding:0.85rem 0.75rem;vertical-align:middle;">${adminName}</td>
                <td style="padding:0.85rem 0.75rem;vertical-align:middle;"><span class="workflow-pill ${statusClass}">${displayStatus}</span></td>
                <td style="padding:0.85rem 0.75rem;vertical-align:middle;text-align:right;"></td>
            </tr>
        `;
    }

    let lastRenderedTableStateKey = '';

    function renderCallTableForStatus() {
        if (!EMERGENCY_COM_CALL_INTAKE_ENABLED) return;
        const list = document.getElementById('conversationsList');
        if (!list) return;

        const openBadge = document.getElementById('openCount');
        let newHtml = '';

        if (currentStatus === 'open') {
            pruneStaleIncomingCalls();
            const queuedCalls = Array.from(incomingCallQueue.values()).filter(item => item && item.status !== 'assigned');
            if (openBadge) {
                openBadge.textContent = String(queuedCalls.length);
                openBadge.style.display = 'inline-block';
            }
            if (!queuedCalls.length) {
                newHtml = '<tr class="empty-call-row"><td colspan="7" style="text-align:center; padding:28px; color:#587071;">No open emergency calls</td></tr>';
            } else {
                newHtml = queuedCalls.map((queued, index) => queuedCallTableRowHtml(queued, index, queuedCalls.length)).join('');
            }
        } else if (currentStatus === 'assigned') {
            const assignedSessions = Array.isArray(lastDbCallSessions?.assigned) ? lastDbCallSessions.assigned : [];
            if (!assignedSessions.length) {
                newHtml = '<tr class="empty-call-row"><td colspan="7" style="text-align:center; padding:28px; color:#587071;">No assigned emergency calls</td></tr>';
            } else {
                newHtml = assignedSessions.map(session => loggedCallTableRowHtml(session, 'assigned')).join('');
            }
        } else if (currentStatus === 'unanswered') {
            const unansweredSessions = Array.isArray(lastDbCallSessions?.all) ? lastDbCallSessions.all.filter(session => {
                return !session.answered_at && ['ended', 'declined', 'cancelled'].includes(String(session.status).toLowerCase());
            }) : [];
            if (!unansweredSessions.length) {
                newHtml = '<tr class="empty-call-row"><td colspan="7" style="text-align:center; padding:28px; color:#587071;">No unanswered calls found</td></tr>';
            } else {
                newHtml = unansweredSessions.map(session => loggedCallTableRowHtml(session, 'unanswered')).join('');
            }
        } else if (currentStatus === 'completed') {
            const completedSessions = Array.isArray(lastDbCallSessions?.all) ? lastDbCallSessions.all.filter(session => {
                return session.answered_at && String(session.status).toLowerCase() === 'ended';
            }) : [];
            if (!completedSessions.length) {
                newHtml = '<tr class="empty-call-row"><td colspan="7" style="text-align:center; padding:28px; color:#587071;">No completed emergency calls</td></tr>';
            } else {
                newHtml = completedSessions.map(session => loggedCallTableRowHtml(session, 'completed')).join('');
            }
        }

        const currentStateKey = currentStatus + '::' + newHtml;
        if (lastRenderedTableStateKey === currentStateKey && list.children.length > 0) {
            return;
        }
        // Do not replace the DOM while the user is hovering over or clicking buttons.
        // Destroying and re-creating nodes mid-hover resets the browser's hover state and
        // swallows click events, causing the Answer/Decline buttons to appear to do nothing.
        if (callTableInteractionFrozen) return;
        lastRenderedTableStateKey = currentStateKey;
        list.innerHTML = newHtml;
        bindEmergencyCallTableButtons(list);
    }

    function renderIncomingCallTableRows() {
        renderCallTableForStatus();
    }

    function renderIncomingEmergencyCallRow() {
        renderCallTableForStatus();
        return;
    }

    function setStatus(text) {
        const el = document.getElementById('callStatus');
        if (el) el.textContent = text;
    }

    function setTimer(seconds) {
        const el = document.getElementById('callTimer');
        if (el) el.textContent = formatTime(seconds);
    }

    function setEndEnabled(enabled) {
        const btn = document.getElementById('endCallBtn');
        if (!btn) return;
        btn.disabled = !enabled;
        btn.style.opacity = enabled ? '1' : '0.6';
        btn.style.pointerEvents = enabled ? 'auto' : 'none';
    }

    function startTimer() {
        if (!callConnectedAt) return;
        stopTimer();
        timerInterval = setInterval(() => {
            const seconds = Math.max(0, Math.floor((Date.now() - callConnectedAt) / 1000));
            setTimer(seconds);
        }, 1000);
    }

    function stopTimer() {
        if (timerInterval) clearInterval(timerInterval);
        timerInterval = null;
    }

    async function tryGetLocation() {
        return new Promise(resolve => {
            if (!navigator.geolocation) return resolve(null);
            navigator.geolocation.getCurrentPosition(
                p => resolve({
                    lat: p.coords.latitude,
                    lng: p.coords.longitude,
                    accuracy: p.coords.accuracy
                }),
                () => resolve(null),
                { enableHighAccuracy: true, timeout: 5000, maximumAge: 0 }
            );
        });
    }

    async function logCall(event, extra = {}) {
        try {
            // Only log if we have a callId
            if (!callId) {
                console.warn('Cannot log call event: callId is missing');
                return;
            }
            
            const payload = {
                callId: callId,
                room: activeCallRoom || pendingCallRoom || getCallRoom(callId),
                role: 'admin',
                event: event,
                location: locationData || null,
                ...extra
            };
            
            const response = await fetch('../api/call-log.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            
            if (!response.ok) {
                const errorText = await response.text();
                console.warn('Call log response not OK:', response.status, errorText);
            }
        } catch (e) {
            // Log call errors are non-critical, just log a warning
            console.warn('Failed to log call event:', e);
        }
    }

    function cleanupCall() {
        const finishedCallId = callId || pendingCallId;
        if (adminCallResumeTimer) clearTimeout(adminCallResumeTimer);
        adminCallResumeTimer = null;
        restoringAdminCall = false;
        if (peerDisconnectTimer) clearTimeout(peerDisconnectTimer);
        peerDisconnectTimer = null;
        stopTimer();
        stopAudioActivityMonitors();
        setEndEnabled(false);
        setCallActiveBannerVisible(false);
        setIncomingCallModalVisible(false);

        messages = [];
        const messagesContainer = document.getElementById('callMessages');
        if (messagesContainer) {
            messagesContainer.innerHTML = '<div style="text-align:center; opacity:0.6; font-size:12px;">Messages will appear here</div>';
        }

        const messageInput = document.getElementById('callMessageInput');
        if (messageInput) messageInput.value = '';
        const transferBtn = document.getElementById('transferCallBtn');
        if (transferBtn) {
            transferBtn.disabled = false;
            transferBtn.style.opacity = '1';
        }

        pendingOffer = null;
        pendingCallId = null;
        pendingCallRoom = null;
        pendingCandidates = [];
        callConversationId = null;
        callerInfo = null;
        callerLocation = null;
        ['callerNameInput', 'callerPhoneInput', 'callerAddressInput', 'callIncidentDescription'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.value = '';
        });
        clearCallBarangaySelection();
        updateCallPriorityBadge();
        renderCallerDetails();
        renderIncomingEmergencyCallRow();

        _stopAlertSound();

        if (localStream) {
            localStream.getTracks().forEach(t => t.stop());
            localStream = null;
        }
        if (pc) {
            try { pc.close(); } catch (e) {}
            pc = null;
        }
        callConnectedAt = null;
        callId = null;
        activeCallRoom = null;
        locationData = null;
        setTimer(0);
        clearAdminCallLock(finishedCallId);
    }

    document.getElementById('endCallBtn').onclick = () => endCall(true);
    document.getElementById('callSendMessageBtn').onclick = () => sendCallMessage();
    document.getElementById('callMessageInput').onkeypress = (e) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            sendCallMessage();
        }
    };
    document.getElementById('incomingAnswerBtn').onclick = () => acceptIncomingEmergencyCall();
    document.getElementById('incomingDeclineBtn').onclick = () => declineIncomingEmergencyCall();
    document.getElementById('callerPhoneInput')?.addEventListener('input', (event) => {
        event.target.value = normalizePhPhone(event.target.value);
    });
    document.getElementById('emergencyTypeSelect')?.addEventListener('change', updateCallPriorityBadge);
    document.getElementById('callIncidentDescription')?.addEventListener('input', updateCallPriorityBadge);
    bindCallBarangaySelector();

    document.getElementById('transferCallBtn').onclick = async () => {
        const statusEl = document.getElementById('dispatchStatus');
        if (statusEl) statusEl.textContent = '';
        if (!callId) {
            if (statusEl) statusEl.textContent = 'No active call.';
            return;
        }
        const rawPhone = document.getElementById('callerPhoneInput')?.value.trim() || '';
        const callerPayload = getManualCallerInfo();
        if (rawPhone && !isValidPhPhone(rawPhone)) {
            if (statusEl) statusEl.textContent = 'Phone number must be exactly 11 digits.';
            return;
        }
        const incidentDescription = getCallIncidentDescription();
        const priorityMetric = currentCallPriority();
        const incidentBarangay = getSelectedCallBarangay();
        if (!incidentBarangay) {
            if (statusEl) statusEl.textContent = 'Select the incident barangay before transferring.';
            return;
        }
        if (!isSanAgustinBarangay(incidentBarangay)) {
            if (statusEl) statusEl.textContent = 'Emergency Response System integration is not yet available for this barangay.';
            return;
        }
        try {
            if (statusEl) statusEl.textContent = 'Preparing pending transfer report...';
            const transferConversationId = await ensureCallConversationForTransfer(callerPayload, incidentDescription, priorityMetric);
            if (statusEl) statusEl.textContent = 'Starting transfer...';
            const res = await fetch(transferApiUrl(), {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    callId,
                    room: activeCallRoom || getCallRoom(),
                    socketUrl: SIGNALING_URL,
                    socketPath: SOCKET_IO_PATH,
                    emergencyType: document.getElementById('emergencyTypeSelect')?.value || '',
                    incidentBarangay,
                    barangay: incidentBarangay,
                    priority: priorityMetric.level,
                    incidentPriority: {
                        score: priorityMetric.score,
                        priority: priorityMetric.level,
                        label: priorityMetric.label,
                        breakdown: priorityMetric.breakdown
                    },
                    description: incidentDescription,
                    caller: callerPayload,
                    location: getTransferLocationPayload(),
                    conversationId: transferConversationId || callConversationId
                })
            });
            const data = await readApiResponse(res);
            if (data && data.success) {
                if (statusEl) statusEl.textContent = data.integration?.configured ? 'Transfer notification sent.' : 'Transfer payload prepared.';
                completeActiveCallTransfer(data.data || null);
            } else {
                if (statusEl) statusEl.textContent = formatTransferError(data, 'Transfer failed.');
            }
        } catch (e) {
            if (statusEl) statusEl.textContent = `Transfer failed: ${e.message || e}`;
        }
    };

    async function completeActiveCallTransfer(transferPayload = null) {
        if (!callId) return;

        transferInProgress = true;
        const activeCallId = callId;
        const s = ensureSocket();
        if (s) {
            s.emit('call-transfer', {
                callId: activeCallId,
                room: activeCallRoom || getCallRoom(activeCallId),
                socketUrl: SIGNALING_URL,
                socketPath: SOCKET_IO_PATH,
                transfer: transferPayload || null,
                transferredBy: (typeof ADMIN_USERNAME !== 'undefined' ? ADMIN_USERNAME : 'Admin'),
                incidentBarangay: getSelectedCallBarangay(),
                barangay: getSelectedCallBarangay(),
                transferredAt: new Date().toISOString()
            }, activeCallRoom || getCallRoom(activeCallId));
        }

        try {
            const transferPriority = currentCallPriority();
            await logCall('transferred', {
                room: activeCallRoom || getCallRoom(activeCallId),
                socketUrl: SIGNALING_URL,
                socketPath: SOCKET_IO_PATH,
                conversationId: callConversationId || null,
                description: getCallIncidentDescription(),
                emergencyType: document.getElementById('emergencyTypeSelect')?.value || '',
                incidentBarangay: getSelectedCallBarangay(),
                incidentPriority: transferPriority
            });
        } catch (e) {}

        await syncCallSession('mark', { callId: activeCallId, status: 'pending' });
        setStatus('Transfer sent. Stay on the call until the response team answers.');
        setEndEnabled(true);
        const transferBtn = document.getElementById('transferCallBtn');
        if (transferBtn) {
            transferBtn.disabled = true;
            transferBtn.style.opacity = '0.65';
        }
    }

    function completeResponseTeamAnswerHandoff() {
        if (!transferInProgress) return;
        setStatus('Response team answered. Call released from admin.');
        stopTimer();
        stopAudioActivityMonitors();
        setEndEnabled(false);

        if (localStream) {
            localStream.getTracks().forEach(t => t.stop());
            localStream = null;
        }
        if (pc) {
            try { pc.onconnectionstatechange = null; pc.close(); } catch (e) {}
            pc = null;
        }

        setTimeout(() => {
            setOverlayVisible(false);
            cleanupCall();
            transferInProgress = false;
            const transferBtn = document.getElementById('transferCallBtn');
            if (transferBtn) {
                transferBtn.disabled = false;
                transferBtn.style.opacity = '1';
            }
        }, 900);
    }

    async function endCall(notifyPeer = true) {
        const durationSec = callConnectedAt ? Math.floor((Date.now() - callConnectedAt) / 1000) : 0;
        
        // Log call end event (non-blocking)
        try {
            await logCall('ended', { durationSec });
        } catch (e) {
            console.warn('Failed to log call end event:', e);
        }

        // Save call to conversation with proper user information
        if (callId) {
            try {
                // Get user information from callerInfo or use defaults
                const callerPayload = getManualCallerInfo();
                const userId = callerPayload?.user_id || callerPayload?.id || null;
                const userName = callerPayload?.name || 'Emergency Call User';
                const userPhone = callerPayload?.phone || null;
                const endedCallPriority = currentCallPriority();
                
                const saveResponse = await fetch('../api/save-completed-call.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        callId: callId,
                        userId: userId,
                        userName: userName,
                        userPhone: userPhone,
                        userLocation: callerPayload?.address || callerLocation?.address || null,
                        location: callerLocation || null,
                        emergencyType: document.getElementById('emergencyTypeSelect')?.value || '',
                        description: getCallIncidentDescription(),
                        incidentPriority: {
                            score: endedCallPriority.score,
                            priority: endedCallPriority.level,
                            label: endedCallPriority.label,
                            breakdown: endedCallPriority.breakdown
                        },
                        duration: durationSec || 0,
                        endedAt: Math.floor(Date.now() / 1000), // Unix timestamp in seconds
                        conversationId: callConversationId || null // Pass existing conversation ID if available
                    })
                });
                
                if (!saveResponse.ok) {
                    const errorText = await saveResponse.text();
                    console.error('Save call response not OK:', saveResponse.status, errorText);
                    throw new Error(`HTTP ${saveResponse.status}: ${errorText}`);
                }
                
                const saveData = await saveResponse.json();
                if (saveData.success) {
                    if (saveData.conversationId) {
                        // Update callConversationId if we got a new one
                        callConversationId = saveData.conversationId;
                        console.log('Call saved to conversation:', saveData.conversationId);
                    }
                    
                    // Refresh conversations list to show the new/updated conversation
                    // Keep ended calls visible in Open as report follow-ups until handled/transferred.
                    if (typeof switchTab === 'function') {
                        switchTab('open');
                    }
                    if (typeof loadConversations === 'function') {
                        setTimeout(() => {
                            loadConversations(true);
                        }, 1000);
                    }
                } else {
                    console.error('Failed to save call:', saveData.message || saveData.error || 'Unknown error');
                }
            } catch (e) {
                console.error('Error saving call:', e);
                // Don't block the call end process if saving fails
            }
        } else {
            console.warn('Cannot save call: callId is missing');
        }

        if (callId) {
            await syncCallSession('mark', { callId, status: 'ended' });
        }

        if (notifyPeer && callId) {
            const s = ensureSocket();
            if (s) s.emit('hangup', { callId, room: activeCallRoom || getCallRoom() }, activeCallRoom || getCallRoom());
        }

        setStatus('Call ended');
        setTimeout(() => {
            setOverlayVisible(false);
            cleanupCall();
        }, 800);
    }

    // Timer reference for ICE connection timeout (stuck in 'connecting' state).
    let callConnectingTimer = null;

    function initPeer() {
        pc = new RTCPeerConnection({
            iceServers: WEBRTC_ICE_SERVERS
        });

        pc.ontrack = e => {
            const remote = document.getElementById('remote');
            const remoteStream = (e.streams && e.streams[0])
                || (e.track ? new MediaStream([e.track]) : null);
            if (remote && remoteStream) {
                remote.srcObject = remoteStream;
                remote.play().catch(err => console.warn('[call][admin] audio play notice:', err));
            }
            if (remoteStream) monitorAudioActivity(remoteStream, 'userSpeakingLabel');
        };

        pc.onicecandidate = e => {
            if (!e.candidate) return;
            const s = ensureSocket();
            if (s && callId) s.emit('candidate', { candidate: e.candidate, callId, room: activeCallRoom || getCallRoom() }, activeCallRoom || getCallRoom());
        };

        pc.onconnectionstatechange = () => {
            if (!pc) return;
            if (pc.connectionState === 'connected') {
                const resumed = restoringAdminCall;
                if (peerDisconnectTimer) clearTimeout(peerDisconnectTimer);
                peerDisconnectTimer = null;
                if (callConnectingTimer) clearTimeout(callConnectingTimer);
                callConnectingTimer = null;
                if (!callConnectedAt) callConnectedAt = Date.now();
                restoringAdminCall = false;
                if (adminCallResumeTimer) clearTimeout(adminCallResumeTimer);
                adminCallResumeTimer = null;
                setStatus('Connected');
                setEndEnabled(true);
                startTimer();
                setAdminCallLock(callId, { connectedAt: callConnectedAt });
                logCall(resumed ? 'reconnected' : 'connected');
                _stopAlertSound();
                setIncomingCallModalVisible(false);
            }
            if (pc.connectionState === 'connecting' || pc.connectionState === 'new') {
                // Start a timeout so the call is not permanently stuck on 'Connecting to caller audio...'.
                // If ICE never completes within 30 seconds, close the peer and let the caller retry.
                if (callConnectingTimer) return; // timer already running
                callConnectingTimer = setTimeout(() => {
                    callConnectingTimer = null;
                    if (!pc || pc.connectionState === 'connected') return;
                    console.warn('[call][admin] ICE connection timed out in state:', pc.connectionState, '– closing peer so caller can retry.');
                    setStatus('Audio connection timed out. Waiting for caller to retry...');
                    try { pc.close(); } catch (e) {}
                    pc = null;
                    // Do not endCall – keep the overlay open and wait for a new offer from the caller.
                }, 30000);
            }
            if (['disconnected', 'failed', 'closed'].includes(pc.connectionState)) {
                if (callConnectingTimer) { clearTimeout(callConnectingTimer); callConnectingTimer = null; }
                if (transferInProgress) return;
                if (!callId || peerDisconnectTimer) return;
                setStatus('Connection interrupted. Waiting for caller to reconnect...');
                peerDisconnectTimer = setTimeout(() => {
                    peerDisconnectTimer = null;
                    if (callId && pc && pc.connectionState !== 'connected') endCall(false);
                }, 20000);
            }
        };
    }

    /**
     * Normalize an SDP value from a socket payload into the plain object
     * { type: string, sdp: string } that RTCPeerConnection.setRemoteDescription() accepts.
     * Accepts an RTCSessionDescription, a plain {type,sdp} object, or returns null
     * if the value is not a valid SDP descriptor.
     */
    function normalizeSdpDescriptor(value) {
        if (!value) return null;
        // If it's already a proper RTCSessionDescription or plain {type,sdp}
        if (typeof value === 'object' && typeof value.type === 'string' && typeof value.sdp === 'string') {
            return { type: value.type, sdp: value.sdp };
        }
        // If it's the raw socket payload that nested the SDP
        if (typeof value === 'object' && value.sdp && typeof value.sdp === 'object') {
            return normalizeSdpDescriptor(value.sdp);
        }
        return null;
    }

    async function completeAdminCallAnswerFromOffer() {
        if (!callId || !activeCallRoom || !pendingOffer) return false;
        // Mutex: if an answer negotiation is already in flight, skip this invocation.
        // Concurrent calls cause InvalidStateError because the peer connection gets
        // closed and re-created mid-negotiation by the second caller.
        if (callAnswerInFlight) {
            console.warn('[call][admin] completeAdminCallAnswerFromOffer already in flight – skipping duplicate invocation.');
            return false;
        }
        // Guard: if the peer connection is already fully established there is nothing to do.
        if (pc && pc.signalingState === 'stable' && pc.connectionState === 'connected') {
            console.warn('[call][admin] completeAdminCallAnswerFromOffer called but connection already established – ignoring.');
            pendingOffer = null;
            pendingCandidates = [];
            return true;
        }
        // Normalize the SDP before touching the peer connection so we fail fast
        // with a clear message rather than an obscure 'Failed to parse SessionDescription'.
        const sdpDescriptor = normalizeSdpDescriptor(pendingOffer);
        if (!sdpDescriptor) {
            console.error('[call][admin] completeAdminCallAnswerFromOffer: pendingOffer is not a valid SDP descriptor:', pendingOffer);
            pendingOffer = null;
            pendingCandidates = [];
            return false;
        }
        callAnswerInFlight = true;
        try {
            if (!pc || pc.signalingState === 'closed') initPeer();
            if (pc.signalingState !== 'stable') {
                try { pc.close(); } catch (e) {}
                pc = null;
                initPeer();
            }

            if (!localStream) {
                try {
                    localStream = await navigator.mediaDevices.getUserMedia({ audio: true });
                    monitorAudioActivity(localStream, 'adminSpeakingLabel', 'adminLocalMicIndicator');
                } catch (micErr) {
                    console.warn('[call][admin] microphone capture unavailable, continuing in listen-only mode:', micErr);
                    if (typeof showToast === 'function') {
                        showToast('Listen-Only Mode', 'No active microphone found or permission blocked. You can hear the caller.');
                    }
                }
            }
            if (localStream && localStream.getTracks) {
                localStream.getTracks().forEach(track => {
                    const alreadyAdded = pc.getSenders && pc.getSenders().some(sender => sender.track === track);
                    if (!alreadyAdded) pc.addTrack(track, localStream);
                });
            }

            await pc.setRemoteDescription(sdpDescriptor);

            const answer = await pc.createAnswer();
            await pc.setLocalDescription(answer);
            const s = ensureSocket();
            if (s) s.emit('answer', { sdp: answer, callId, room: activeCallRoom }, activeCallRoom);

            if (Array.isArray(pendingCandidates) && pendingCandidates.length) {
                for (const cand of pendingCandidates) {
                    try {
                        if (pc && cand) {
                            const iceCand = typeof cand === 'object' && cand.candidate ? cand : { candidate: cand };
                            await pc.addIceCandidate(new RTCIceCandidate(iceCand));
                        }
                    } catch (e) {
                        console.warn('[call][admin] Error adding pending ICE candidate:', e);
                    }
                }
            }
            pendingOffer = null;
            pendingCandidates = [];
            setStatus('Connecting to caller audio...');
            return true;
        } catch (e) {
            console.error('[call][admin] accept call failed', e);
            acceptingCallId = null;
            let userFriendlyError = 'Call failed';
            if (e.name === 'NotAllowedError' || e.name === 'PermissionDeniedError') {
                userFriendlyError = 'Microphone access blocked. Please enable microphone permissions in your browser.';
            } else if (e.name === 'NotFoundError' || e.name === 'DevicesNotFoundError') {
                userFriendlyError = 'No microphone device found on this system.';
            } else if (e.message) {
                userFriendlyError = `Call error: ${e.message}`;
            }
            setStatus(userFriendlyError);
            setEndEnabled(true);
            endCall(true);
            return false;
        } finally {
            callAnswerInFlight = false;
            renderIncomingEmergencyCallRow();
            renderIncomingCallTableRows();
        }
    }
    async function acceptIncomingEmergencyCall(targetCallId = null) {
        let selectedCallId = targetCallId ? String(targetCallId) : (pendingCallId ? String(pendingCallId) : null);
        if (!selectedCallId && incomingCallQueue.size) {
            selectedCallId = String(Array.from(incomingCallQueue.keys())[0]);
        }
        if (selectedCallId && incomingCallQueue.has(selectedCallId)) {
            applyQueuedCallToPending(incomingCallQueue.get(selectedCallId));
        }
        if (!pendingCallId && selectedCallId && incomingCallQueue.has(selectedCallId)) {
            applyQueuedCallToPending(incomingCallQueue.get(selectedCallId));
        }
        if (!pendingCallId) {
            acceptingCallId = null;
            renderIncomingCallTableRows();
            return;
        }
        if (callId && pendingCallId !== callId) return;
        acceptingCallId = String(pendingCallId || '');
        if (adminHasActiveCall(pendingCallId)) {
            acceptingCallId = null;
            setIncomingCallModalText('You already have an active call. Finish or transfer it before taking another call.');
            renderIncomingEmergencyCallRow();
            return;
        }

        const selectedPendingCallId = String(pendingCallId);
        callId = selectedPendingCallId;
        activeCallRoom = pendingCallRoom || getCallRoom(callId);
        setAdminCallLock(callId, {
            room: activeCallRoom,
            callerInfo,
            callerLocation,
            conversationId: callConversationId,
            connectedAt: callConnectedAt
        });
        incomingCallQueue.delete(selectedPendingCallId);
        setIncomingEmergencyCallRowVisible(false);
        setIncomingCallModalVisible(false);
        setOverlayVisible(true);
        setCallActiveBannerVisible(true);
        setStatus('Claiming call...');
        setTimer(0);
        setEndEnabled(false);
        renderIncomingEmergencyCallRow();
        renderIncomingCallTableRows();

        const wasRestoring = restoringAdminCall && callId === pendingCallId;
        const dbClaim = await syncCallSession('claim', callSessionPayload({ callId: pendingCallId, room: pendingCallRoom }));
        if (!dbClaim?.success) {
            const claimError = dbClaim?.error || 'This call is no longer available.';
            console.error('[call] syncCallSession claim failed:', claimError);
            if (typeof showToast === 'function') showToast('Call Answer Failed', claimError);
            alert('Could not answer call: ' + claimError);
            setIncomingCallModalText(claimError);
            clearAdminCallLock();
            callId = null;
            activeCallRoom = null;
            acceptingCallId = null;
            setOverlayVisible(false);
            setCallActiveBannerVisible(false);
            removeQueuedCall(selectedPendingCallId);
            _stopAlertSound();
            return;
        }
        const signalingSocket = ensureSocket();
        if (!signalingSocket?.connected) {
            setStatus('Call service is reconnecting. Keep this call window open...');
        }
        const claimResult = signalingSocket?.connected ? await new Promise(resolve => {
            const timer = setTimeout(() => resolve({ ok: true, degraded: true, reason: 'Socket claim timed out; database claim already succeeded.' }), 2500);
            signalingSocket.emit('join', activeCallRoom);
            signalingSocket.emit('claim-call', {
                callId,
                room: activeCallRoom,
                adminKey: ADMIN_CALL_OWNER_KEY
            }, result => {
                clearTimeout(timer);
                resolve(result || { ok: true, degraded: true });
            });
        }) : { ok: true, degraded: true, reason: 'Socket reconnecting; database claim already succeeded.' };
        if (claimResult && claimResult.ok === false) {
            // The database claim is the source of truth. If the socket room lost state after a PM2 restart,
            // continue opening the admin call modal and let the caller replay the offer into the room.
            console.warn('[call][admin] socket claim did not acknowledge cleanly', claimResult);
        }
        try {
            if (wasRestoring) {
                await logCall('reconnecting', {
                    adminUsername: (typeof ADMIN_USERNAME !== 'undefined' ? ADMIN_USERNAME : null)
                });
            } else {
            await logCall('accepted', {
                adminUsername: (typeof ADMIN_USERNAME !== 'undefined' ? ADMIN_USERNAME : null)
            });
            }
        } catch (e) {}
        acceptingCallId = null;
        setStatus('Connecting...');
        const signalingSocketForOffer = ensureSocket();
        if (signalingSocketForOffer?.connected) {
            signalingSocketForOffer.emit('join', activeCallRoom);
        }

        if (pendingOffer) {
            // Keep acceptingCallId set until negotiation completes so concurrent answer
            // attempts are blocked. acceptingCallId is cleared inside completeAdminCallAnswerFromOffer.
            acceptingCallId = String(callId);
            await completeAdminCallAnswerFromOffer();
            acceptingCallId = null;
        } else {
            setStatus('Waiting for caller connection...');
            setEndEnabled(true);
            if (signalingSocketForOffer?.connected) {
                signalingSocketForOffer.emit('request-offer', {
                    callId,
                    room: activeCallRoom,
                    reason: 'admin-answer-needs-offer'
                }, activeCallRoom);
            }
            renderIncomingEmergencyCallRow();
            renderIncomingCallTableRows();
        }
    }

    async function declineIncomingEmergencyCall(targetCallId = null) {
        const selectedCallId = targetCallId ? String(targetCallId) : (pendingCallId ? String(pendingCallId) : null);
        if (selectedCallId && incomingCallQueue.has(selectedCallId)) {
            applyQueuedCallToPending(incomingCallQueue.get(selectedCallId));
        }

        if (!pendingCallId) {
            setIncomingEmergencyCallRowVisible(false);
            if (!incomingCallQueue.size) _stopAlertSound();
            return;
        }

        const declinedCallId = String(pendingCallId);
        const declinedRoom = pendingCallRoom || getCallRoom(declinedCallId);
        const declinedCallerInfo = callerInfo || null;
        const declinedCallerLocation = callerLocation || null;
        const declinedConversationId = callConversationId || null;

        try {
            await logCall('declined', { callId: declinedCallId });
        } catch (e) {}

        try {
            await fetch('../api/save-completed-call.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    callId: declinedCallId,
                    event: 'declined',
                    conversationId: declinedConversationId,
                    userId: declinedCallerInfo?.user_id || declinedCallerInfo?.id || null,
                    userName: declinedCallerInfo?.name || 'Emergency Call User',
                    userPhone: declinedCallerInfo?.phone || null,
                    userLocation: declinedCallerInfo?.address || declinedCallerLocation?.address || null,
                    location: declinedCallerLocation || null,
                    endedAt: Math.floor(Date.now() / 1000)
                })
            });
        } catch (e) {
            console.warn('Failed to save declined call report:', e);
        }

        await syncCallSession('mark', { callId: declinedCallId, status: 'declined' });
        const s = ensureSocket();
        if (s) s.emit('hangup', { callId: declinedCallId, room: declinedRoom }, declinedRoom);
        removeQueuedCall(declinedCallId);
        if (!incomingCallQueue.size) _stopAlertSound();
    }
    window.acceptIncomingEmergencyCall = acceptIncomingEmergencyCall;
    window.declineIncomingEmergencyCall = declineIncomingEmergencyCall;

    let callSocketListenersBoundFor = null;
    function bindCallSocketListeners() {
        const s = ensureSocket();
        if (!s) return;
        if (callSocketListenersBoundFor === s) return;
        callSocketListenersBoundFor = s;

        function queueIncomingOffer(payload, rawSdp = null, notify = true) {
            if (!payload || payload.transferred) return null;
            const sdp = rawSdp || (payload && payload.sdp ? payload.sdp : payload);
            const queued = normalizeQueuedCall(payload, sdp);
            if (!queued) return null;
            const existing = incomingCallQueue.get(queued.callId) || {};
            const isNew = !incomingCallQueue.has(queued.callId);
            const merged = {
                ...existing,
                ...queued,
                sdp: queued.sdp || existing.sdp || null,
                pendingCandidates: existing.pendingCandidates || queued.pendingCandidates || [],
                status: existing.status || queued.status || 'open',
                lastUpsertTime: existing.lastUpsertTime || 0
            };
            incomingCallQueue.set(queued.callId, merged);
            const signalingSocket = ensureSocket();
            if (signalingSocket) signalingSocket.emit('join', merged.room || getCallRoom(merged.callId));
            
            // Rate limit database session sync (upsert_open): only run if new or more than 10 seconds since last upsert
            const now = Date.now();
            if (isNew || (now - merged.lastUpsertTime > 10000)) {
                merged.lastUpsertTime = now;
                const persistedOfferPayload = {
                    ...(payload && typeof payload === 'object' ? payload : {}),
                    sdp: merged.sdp,
                    callId: merged.callId,
                    call_id: merged.callId,
                    room: merged.room || getCallRoom(merged.callId),
                    caller: merged.caller || null,
                    location: merged.location || null,
                    conversationId: merged.conversationId || null,
                    conversation_id: merged.conversationId || null
                };
                syncCallSession('upsert_open', callSessionPayload({
                    callId: merged.callId,
                    room: merged.room,
                    caller: merged.caller,
                    location: merged.location,
                    conversationId: merged.conversationId,
                    offerPayload: persistedOfferPayload
                }));
            }

            if (notify && !adminHasActiveCall(merged.callId) && !notifiedIncomingCallIds.has(merged.callId)) {
                notifiedIncomingCallIds.add(merged.callId);
                // Keep the fresh emergency call visible in the Open queue without forcing a floating modal notification.
                setIncomingCallModalVisible(false);
                _startAlertSound(notificationSound);
            }
            if (isNew) {
                renderIncomingEmergencyCallRow();
                renderIncomingCallTableRows();
            }
            return merged;
        }
        queueIncomingOfferFromSocket = queueIncomingOffer;

        s.on('call-queue', payload => {
            const openCalls = Array.isArray(payload?.open) ? payload.open : [];
            openCalls.forEach(call => {
                const source = call?.offer || call || null;
                queueIncomingOffer(source, source?.sdp || null, false);
            });
            // Do not prune local offers from a queue refresh. A transient empty socket snapshot can arrive
            // before the user's offer replay, which made the answer button disappear on the intake page.
            renderIncomingEmergencyCallRow();
            renderIncomingCallTableRows();
        });

        s.on('call-created', payload => {
            const source = payload?.call?.offer || payload?.call || null;
            queueIncomingOffer(source, source?.sdp || null, true);
            restoreCallSessionsFromDatabase(false);
        });

        s.on('call-updated', payload => {
            const status = payload?.call?.status || '';
            const updatedCallId = payload?.call?.callId || null;
            if (!updatedCallId) return;
            if (status === 'accepted' && String(payload?.call?.adminKey || '') === ADMIN_CALL_OWNER_KEY) return;
            if (String(updatedCallId) === String(acceptingCallId || '')) return;
            const claimedByOtherAdmin = status === 'accepted' && String(payload?.call?.adminKey || '') !== ADMIN_CALL_OWNER_KEY;
            const terminalCallStatus = ['ended', 'completed', 'declined'].includes(status);
            if (claimedByOtherAdmin || terminalCallStatus) {
                removeQueuedCall(updatedCallId);
                if (!incomingCallQueue.size) _stopAlertSound();
            } else {
                renderIncomingCallTableRows();
            }
        });

        let lastOfferWarnTime = 0;
        s.on('offer', async payload => {
            if (!EMERGENCY_COM_CALL_INTAKE_ENABLED) return;
            const incomingCallId = payload && payload.callId ? String(payload.callId) : null;
            if (!incomingCallId || (payload && payload.transferred)) return;
            const shouldAutoResume = restoringAdminCall && callId === incomingCallId;
            const isNewOffer = !incomingCallQueue.has(incomingCallId);
            const queued = queueIncomingOffer(payload, payload && payload.sdp ? payload.sdp : payload, !shouldAutoResume);
            if (!queued) return;
            
            // Only trigger location checks & conversation reloads on NEW call offers to avoid network flooding
            if (isNewOffer) {
                locationData = await tryGetLocation();
                if (typeof resetConversationsAndReload === 'function') resetConversationsAndReload();
            }

            if (callId === incomingCallId) {
                pendingOffer = queued.sdp || null;
                pendingCandidates = queued.pendingCandidates || [];
                // If an answer is already in flight, do not start a second concurrent negotiation.
                // The in-flight negotiation will use pendingOffer as set above.
                if (!callAnswerInFlight) {
                    await completeAdminCallAnswerFromOffer();
                } else {
                    const now = Date.now();
                    if (now - lastOfferWarnTime > 5000) {
                        lastOfferWarnTime = now;
                        console.warn('[call][admin] New offer arrived while answer already in flight – pendingOffer updated.');
                    }
                }
            } else if (shouldAutoResume) {
                setIncomingCallModalText('Restoring your active emergency call...');
                setTimeout(() => acceptIncomingEmergencyCall(incomingCallId), 0);
            }
        });
        s.on('call-claimed', payload => {
            const claimedCallId = payload?.callId ? String(payload.callId) : null;
            if (!claimedCallId) return;
            if (String(payload?.adminKey || '') === ADMIN_CALL_OWNER_KEY) return;
            removeQueuedCall(claimedCallId);
            if (pendingCallId === claimedCallId && !callId) {
                pendingOffer = null;
                pendingCallId = null;
                pendingCallRoom = null;
                pendingCandidates = [];
            }
            if (!incomingCallQueue.size) {
                setIncomingCallModalVisible(false);
                _stopAlertSound();
            }
        });

        s.on('answer', payload => {
            const incomingCallId = payload && payload.callId ? payload.callId : null;
            if (incomingCallId && callId && incomingCallId !== callId) return;
            if (transferInProgress && callId) {
                completeResponseTeamAnswerHandoff();
            }
        });

        s.on('candidate', payload => {
            const cand = payload && payload.candidate ? payload.candidate : payload;
            const incomingCallId = payload && payload.callId ? String(payload.callId) : null;
            if (incomingCallId && callId && incomingCallId !== callId) return;
            if (transferInProgress) return;

            if (!pc || !pc.remoteDescription || !callId) {
                if (cand && incomingCallId) {
                    const queued = incomingCallQueue.get(incomingCallId);
                    if (queued) {
                        queued.pendingCandidates = queued.pendingCandidates || [];
                        queued.pendingCandidates.push(cand);
                    }
                    if (pendingCallId === incomingCallId || callId === incomingCallId) pendingCandidates.push(cand);
                }
                return;
            }

            if (pc && pc.remoteDescription && cand) {
                try {
                    const iceCand = typeof cand === 'object' && cand.candidate ? cand : { candidate: cand };
                    pc.addIceCandidate(new RTCIceCandidate(iceCand)).catch(err => {
                        console.warn('[call][admin] addIceCandidate failed:', err);
                    });
                } catch (e) {
                    console.warn('[call][admin] Invalid ICE candidate object:', e);
                }
            }
        });

        s.on('hangup', payload => {
            const incomingCallId = payload && (payload.callId || payload.call_id) ? String(payload.callId || payload.call_id) : null;
            if (callId && incomingCallId !== callId) return;

            if (incomingCallId && !callId) {
                removeQueuedCall(incomingCallId);
                if (!incomingCallQueue.size) _stopAlertSound();
                if (typeof syncCallSession === 'function') {
                    syncCallSession('mark', { callId: incomingCallId, status: 'ended' });
                }
                return;
            }

            if (callId) endCall(false);
        });

        s.on('call-message', payload => {
            const incomingCallId = payload && payload.callId ? payload.callId : null;
            if (incomingCallId && callId && incomingCallId !== callId) return;
            if (incomingCallId && pendingCallId && incomingCallId !== pendingCallId) return;
            if (payload.text && payload.sender !== 'admin') {
                addMessage(payload.text, payload.sender || 'user', payload.timestamp);
            }
        });
    }

    restoreAdminCallState();

    checkSocketServerAvailability(true).then((available) => {
        if (available) {
            bindCallSocketListeners();
            restoreCallSessionsFromDatabase(true);
        } else {
            setStatus('Call signaling unavailable (socket server offline).');
        }
    });

    if (EMERGENCY_COM_CALL_INTAKE_ENABLED) {
        setInterval(() => {
            restoreCallSessionsFromDatabase(true);
        }, 2500);
    }
    // Keep trying quietly so page can recover if socket server starts later.
    setInterval(() => {
        if (socket && socket.connected) return;
        checkSocketServerAvailability().then((available) => {
            if (available) {
                bindCallSocketListeners();
                restoreCallSessionsFromDatabase(true);
            }
        });
    }, 15000);
</script>

</body>
</html>










