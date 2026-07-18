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
$assetBaseUrl = $assetBaseUrl ?? '';
$adminUsername = $_SESSION['admin_username'] ?? 'Admin';
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
            transition: all 0.2s ease;
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
                        <li class="breadcrumb-item active" aria-current="page">Two-Way Communication</li>
                    </ol>
                </nav>
                <h1><i class="fas fa-comments" style="color: var(--primary-color-1); margin-right: 0.5rem;"></i> <?php echo htmlspecialchars($pageHeading); ?></h1>
                <p><?php echo htmlspecialchars($pageDescription); ?></p>
            </div>

            <div class="twc-primary-switch" id="twcPrimarySwitch" aria-label="Two-way communication views">
                <button type="button" class="twc-primary-chip active" data-twc-view="conversations">
                    <i class="fas fa-comments"></i>
                    <span>Conversations</span>
                </button>
                <button type="button" class="twc-primary-chip" data-twc-view="chatbotLogs">
                    <i class="fas fa-robot"></i>
                    <span>Chatbot Logs</span>
                </button>
                <button type="button" class="twc-primary-chip" data-twc-view="transfers">
                    <i class="fas fa-share-from-square"></i>
                    <span>Transferred</span>
                </button>
            </div>

            <div id="twcConversationsShell">

            
            <div class="sub-container">
                <div class="page-content">
                    <div class="communication-container" id="communicationContainer">
                        <!-- Conversations List Container -->
                        <div class="conversations-list-container">
                            <div class="chat-tabs">
                                <div class="chat-tab active" onclick="switchTab('open')">
                                    <i class="fas fa-inbox"></i> Open <span id="openCount" class="badge"></span>
                                </div>
                                <div class="chat-tab" onclick="switchTab('assigned')">
                                    <i class="fas fa-user-check"></i> Assigned
                                </div>
                                <div class="chat-tab" onclick="switchTab('closed')">
                                    <i class="fas fa-hourglass-half"></i> Pending Status
                                </div>
                            </div>
                            <div class="chat-filters">

                                <label for="priorityFilter">Priority</label>
                                <select id="priorityFilter">
                                    <option value="all">All Priorities</option>
                                    <?php if ($pageMode === 'citizen_reports'): ?>
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
                            <div class="conversations-list-table-wrapper" id="scrollableList" style="flex: 1; overflow-y: auto; overflow-x: auto; padding: 0.75rem;">
                                <div id="incomingEmergencyCallRow" style="display:none;"></div>
                                <table class="twc-table">
                                    <thead>
                                        <tr>
                                            <th>Citizen</th>
                                            <th>Location</th>
                                            <th>Last Message</th>
                                            <th>Priority</th>
                                            <th>Status</th>
                                            <th style="text-align: right;">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody id="conversationsList">
                                        <!-- Conversations will be loaded here -->
                                    </tbody>
                                </table>
                                <div id="loadMoreContainer" class="load-more-container" style="display: none; padding: 1rem; text-align: center;">
                                    <button class="btn-load-more" onclick="loadMoreConversations()">
                                        Load More
                                    </button>
                                </div>
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
                                    <?php if ($pageMode === 'citizen_reports'): ?>
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
                                     <button class="btn btn-sm btn-secondary" id="transferConversationBtn" style="display: none;">
                                         <i class="fas fa-share-from-square"></i> Transfer
                                     </button>
                                     <button class="btn btn-sm btn-secondary" id="releaseConversationBtn" style="display: none;">
                                         <i class="fas fa-user-clock"></i> Hand Over to Other Admin
                                     </button>
                                     <button class="btn btn-sm btn-secondary" id="toggleStatusBtn" style="display: none;">
                                         <i class="fas fa-check"></i> Close Chat
                                     </button>
                                     <button onclick="closeChatPanel()" class="btn btn-sm btn-secondary" style="display: inline-flex; align-items: center; justify-content: center; width: 32px; height: 32px; padding: 0; border-radius: 6px; border: 1px solid var(--border-color-1); background: var(--bg-color-1); color: var(--text-color-2); cursor: pointer; margin-left: 0.5rem;" title="Hide Chat">
                                         <i class="fas fa-times"></i>
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
        const transferApiUrl = (suffix = '') => `${ROOT_API_BASE}transfer-call.php${suffix}`;
        const ADMIN_USERNAME = <?php echo json_encode($adminUsername); ?>;
        const ADMIN_ID = <?php echo json_encode($_SESSION['admin_user_id'] ?? null); ?>;
        const ADMIN_AVATAR = `https://ui-avatars.com/api/?name=${encodeURIComponent(ADMIN_USERNAME)}&background=4c8a89&color=fff&size=128`;
        const PAGE_MODE = <?php echo json_encode($pageMode); ?>;
        
        // State Management
        let currentStatus = 'open';
        let currentConversationId = null;
        let currentConversationData = null;
        let lastMessageId = 0;
        let currentPage = 1;
        const pageLimit = 20; // Load 20 at a time for speed
        let isLoading = false;
        let hasMore = true;
        let lastDisplayedDate = null; // Track the last date shown in the chat
        let currentDept = 'all';
        let currentTopic = 'all';
        let lastUnreadCount = 0;
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
        let pollInterval = null;
        let messageInterval = null;

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
            if (currentStatus === status) return;
            currentStatus = status;
            
            // UI Update
            document.querySelectorAll('.chat-tab').forEach(tab => {
                const onclick = tab.getAttribute('onclick') || '';
                tab.classList.toggle('active', onclick.includes(`'${status}'`));
            });

            // Reset List
            currentPage = 1;
            hasMore = true;
            document.getElementById('conversationsList').innerHTML = '';
            document.getElementById('loadMoreContainer').style.display = 'none';
            
            loadConversations(true);
        }
        
        function closeMobileChat() {
            document.getElementById('communicationContainer').classList.remove('chat-active');
            // Allow polling to refresh list again if needed, but keep current ID active in background
        }

        function closeChatPanel() {
            if (currentConversationId && currentConversationData && Number(currentConversationData.assignedTo || 0) === Number(ADMIN_ID || 0)) {
                showTransferModalNotice(
                    { userName: currentConversationData.userName || 'Assigned report', category: currentConversationData.category || 'Report', userLocation: currentConversationData.userLocation || '-' },
                    'This report is assigned to you. Use Hand Over to Other Admin before leaving it.',
                    'error'
                );
                return;
            }
            closeMobileChat();
            document.querySelectorAll('.conversation-item').forEach(i => i.classList.remove('active'));
            currentConversationId = null;
            currentConversationData = null;
            const transferBtn = document.getElementById('transferConversationBtn');
            if (transferBtn) transferBtn.style.display = 'none';
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
                const res = await fetch(transferApiUrl('?limit=50'));
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

        async function updateTransferEmergencyStatus(transferId) {
            const allowed = 'requested, received, fake_call, rescue_ongoing, responders_dispatched, arrived_on_scene, resolved, cancelled, unable_to_locate, duplicate';
            const responseStatus = prompt(`Enter emergency status:\n${allowed}`, 'rescue_ongoing');
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
            document.getElementById('loadMoreContainer').style.display = 'none';
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
            return {
                level: labels[level] ? level : 'low',
                label: labels[level] || 'LOW',
                score: Number.isFinite(score) ? score : 0,
                manual: Boolean(p.manual ?? convOrPriority?.incidentPriorityManual)
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
            if (PAGE_MODE !== 'citizen_reports') return '';
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
            const sorted = PAGE_MODE === 'citizen_reports' ? sortCitizenReports(conversations) : sortConversationsNewest(conversations);

            sorted.forEach(conv => {
                const convId = String(conv.id);
                if (existingIds.has(convId)) return;
                listContainer.appendChild(createConversationElement(conv));
                existingIds.add(convId);
            });
        }

        async function loadConversations(isInitial = false, append = false, silent = false) {
            if (isLoading) return;
            isLoading = true;
            
            const listContainer = document.getElementById('conversationsList');
            const spinner = document.getElementById('loadingSpinner');
            const loadMoreBtn = document.getElementById('loadMoreContainer');
            
            if (isInitial && !append && !silent) {
                spinner.style.display = 'block';
                listContainer.innerHTML = ''; // Clear for initial load
            } else if (append) {
                spinner.style.display = 'block';
                loadMoreBtn.style.display = 'none';
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
                if (currentStatus === 'assigned') {
                    params.set('assigned_to_me', '1');
                }
                if (PAGE_MODE === 'citizen_reports') {
                    params.set('scope', 'citizen_reports');
                }
                if (currentDept !== 'all') {
                    params.set('category', currentDept);
                }
                if (currentPriority !== 'all') {
                    params.set('priority', currentPriority);
                }
                
                const response = await fetch(`${API_BASE}chat-get-conversations.php?${params}`);
                const data = await response.json();
                
                if (!silent) {
                    spinner.style.display = 'none';
                }
                
                if (!data.success) throw new Error(data.message);
                
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
                
                // Handle Empty State
                if (conversations.length === 0) {
                    hasMore = false;
                    if (isInitial && !append) {
                        const suffix = currentDept === 'all' ? '' : ' for this department';
                        const topicSuffix = currentTopic === 'all' ? '' : ' for this topic';
                        listContainer.innerHTML = `<p style="text-align: center; color: var(--text-secondary-1); padding: 2rem;">No ${currentStatus} conversations${suffix}${topicSuffix}</p>`;
                    }
                    return;
                }
                
                // Check if we have more pages
                if (conversations.length < pageLimit) {
                    hasMore = false;
                } else {
                    loadMoreBtn.style.display = 'block';
                }
                
                // Render Items (grouped by department)
                renderGroupedConversations(conversations, append);
                tryOpenConversationFromQuery(conversations);
                
            } catch (error) {
                console.error('Error loading conversations:', error);
                if (isInitial && !silent) listContainer.innerHTML = '<p style="color: #e74c3c; text-align: center; padding: 1rem;">Failed to load data</p>';
            } finally {
                if (!silent) {
                    spinner.style.display = 'none';
                }
                isLoading = false;
            }
        }
        
        function loadMoreConversations() {
            if (hasMore && !isLoading) {
                currentPage++;
                loadConversations(false, true);
            }
        }
        
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

        async function pollUpdates() {
            // 1. Update Badge & Unread Count
            try {
                const response = await fetch(API_BASE + 'chat-get-unread-count.php');
                const data = await response.json();
                if (data.success) {
                    const count = data.unreadCount;
                    if (!hasUnreadBaseline) {
                        lastUnreadCount = count;
                        hasUnreadBaseline = true;
                    } else if (count > lastUnreadCount) {
                        const diff = count - lastUnreadCount;
                        showToast('New message', diff === 1 ? '1 new conversation update' : `${diff} new conversation updates`);
                        lastUnreadCount = count;
                    } else if (count < lastUnreadCount) {
                        lastUnreadCount = count;
                    }
                    // Sidebar Badge
                    const sidebarLinks = document.querySelectorAll('.sidebar-menu li a');
                    sidebarLinks.forEach(link => {
                        if (link.href.includes('two-way-communication.php')) {
                            let badge = link.querySelector('.sidebar-badge');
                            if (!badge) {
                                badge = document.createElement('span');
                                badge.className = 'sidebar-badge';
                                badge.style.cssText = 'background: #ff5252; color: white; padding: 2px 6px; border-radius: 10px; font-size: 0.7rem; margin-left: auto; display: none;';
                                link.appendChild(badge);
                                link.style.display = 'flex';
                                link.style.alignItems = 'center';
                            }
                            badge.textContent = count;
                            badge.style.display = count > 0 ? 'inline-block' : 'none';
                        }
                    });
                }
            } catch (e) {}

            if (currentMainView !== 'conversations') {
                return;
            }

            // 2. Silent list refresh for first page only (stable re-render, no manual DOM shuffling)
            const listEl = document.getElementById('scrollableList');
            if (!isLoading && currentPage === 1 && listEl && listEl.scrollTop < 50 && (currentStatus === 'open' || currentStatus === 'active')) {
                await loadConversations(false, false, true);
            }
        }

        // --- DOM Helpers ---

        function createConversationElement(conv) {
            const item = document.createElement('tr');
            item.className = 'conversation-item conversation-row-item';
            if (PAGE_MODE === 'citizen_reports') {
                item.classList.add(`incident-row-priority-${incidentPriorityMeta(conv).level}`);
            }
            if (currentStatus === 'closed') item.classList.add('closed');
            if (String(conv.id) === String(currentConversationId)) item.classList.add('active');
            
            item.setAttribute('data-conversation-id', conv.id);
            item._conversationData = conv;
            
            item.innerHTML = getConversationHTML(conv);
            
            item.addEventListener('click', function(event) {
                if (event.target.closest('.transfer-report-btn')) {
                    event.stopPropagation();
                    transferConversationReport(this._conversationData || conv);
                    return;
                }
                openConversation(conv.id, this._conversationData, this);
            });
            
            return item;
        }
        
        function getConversationHTML(conv) {
            const guestBadge = conv.isGuest ? '<span class="list-chip list-chip-guest" style="background: #e67e22; color: white; padding: 2px 6px; border-radius: 4px; font-size: 0.7rem; font-weight: 700; margin-left: 0.25rem;">GUEST</span>' : '';
            const concernBadge = conv.userConcern ? `<span class="list-chip list-chip-concern" style="background: #2ecc71; color: white; padding: 2px 6px; border-radius: 4px; font-size: 0.7rem; font-weight: 700; margin-left: 0.25rem;">${conv.userConcern}</span>` : '';
            const callBadge = conv.hasCall ? '<span class="list-chip list-chip-call" style="background: #3498db; color: white; padding: 2px 6px; border-radius: 4px; font-size: 0.7rem; font-weight: 700; margin-left: 0.25rem;"><i class="fas fa-phone"></i> Call</span>' : '';
            const unreadBadge = conv.unreadCount > 0 ? `<span class="list-chip list-chip-unread" style="background: #e74c3c; color: white; padding: 2px 6px; border-radius: 4px; font-size: 0.7rem; font-weight: 700; margin-left: 0.25rem;">${conv.unreadCount}</span>` : '';
            const workflowRaw = (conv.workflowStatus || '').toLowerCase();
            const workflowLabelMap = {
                open: 'Open',
                active: 'Open',
                in_progress: 'In Progress',
                waiting_user: 'Waiting User',
                resolved: 'Resolved',
                closed: 'Closed'
            };
            const workflowLabel = workflowLabelMap[workflowRaw] || 'Open';
            const workflowClassMap = {
                open: 'workflow-open',
                active: 'workflow-open',
                in_progress: 'workflow-progress',
                waiting_user: 'workflow-waiting',
                resolved: 'workflow-resolved',
                closed: 'workflow-closed'
            };
            const workflowClass = workflowClassMap[workflowRaw] || 'workflow-open';
            const statusBadge = `<span class="workflow-pill ${workflowClass}">${workflowLabel}</span>`;
            const incidentBadge = incidentPriorityBadgeHtml(conv);
            const statusDot = `<span class="status-dot"></span>`;

            const timestamp = getConversationTimestamp(conv);
            const displayTime = timestamp
                ? `${new Date(timestamp).toLocaleDateString([], { month: 'short', day: '2-digit' })} ${new Date(timestamp).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}`
                : '';

            const location = conv.userLocation || '<span style="opacity:0.5;">Not specified</span>';
            const lastMsg = conv.lastMessage || '<span style="opacity:0.5;font-style:italic;">No messages</span>';

            return `
                <td style="padding: 0.85rem 0.75rem; vertical-align: middle;">
                    <div style="display: flex; align-items: center; gap: 0.35rem;">
                        ${statusDot}
                        <strong>${conv.userName || 'Unknown'}</strong>
                        ${guestBadge} ${concernBadge} ${callBadge} ${unreadBadge}
                    </div>
                    ${conv.userPhone ? `<div style="font-size: 0.75rem; opacity: 0.6; margin-top: 0.15rem;"><i class="fas fa-phone" style="font-size:0.7rem;"></i> ${conv.userPhone}</div>` : ''}
                </td>
                <td style="padding: 0.85rem 0.75rem; vertical-align: middle; max-width: 150px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                    <i class="fas fa-map-marker-alt" style="color: var(--primary-color-1); font-size:0.8rem;"></i> ${location}
                </td>
                <td style="padding: 0.85rem 0.75rem; vertical-align: middle; max-width: 220px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                    ${lastMsg}
                    <div style="font-size: 0.7rem; opacity: 0.5; margin-top: 0.15rem;">${displayTime}</div>
                </td>
                <td style="padding: 0.85rem 0.75rem; vertical-align: middle;">
                    ${incidentBadge}
                </td>
                <td style="padding: 0.85rem 0.75rem; vertical-align: middle;">
                    ${statusBadge}
                </td>
                <td style="padding: 0.85rem 0.75rem; vertical-align: middle; text-align: right;">
                    <button class="btn btn-secondary transfer-report-btn" data-conversation-id="${conv.id}" style="padding: 0.35rem 0.65rem; font-size: 0.75rem; border-radius: 4px; cursor: pointer; margin-right:0.35rem;">
                        <i class="fas fa-share-from-square"></i> Transfer
                    </button>
                    <button class="btn btn-primary respond-btn" style="padding: 0.35rem 0.65rem; font-size: 0.75rem; border-radius: 4px; cursor: pointer; background: var(--primary-color-1); color: white; border: none;">
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
                transferBtn.style.display = data ? 'inline-flex' : 'none';
                transferBtn.disabled = !data;
            }
            if (!control || !badge || !button || !menu) return;

            if (PAGE_MODE !== 'citizen_reports' || !data) {
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
        }

        function setTransferModalMessage(message, state = '') {
            const el = document.getElementById('twcTransferMessage');
            if (!el) return;
            el.textContent = message || '';
            el.className = `twc-transfer-modal__message ${state}`.trim();
        }

        function closeTransferModal() {
            const modal = document.getElementById('twcTransferModal');
            if (!modal) return;
            modal.classList.remove('active');
            modal.setAttribute('aria-hidden', 'true');
            setTransferModalBusy(false);
        }

        function openTransferModal(data) {
            const modal = document.getElementById('twcTransferModal');
            if (!modal) return Promise.resolve(false);

            const citizenEl = document.getElementById('twcTransferCitizen');
            const typeEl = document.getElementById('twcTransferType');
            const locationEl = document.getElementById('twcTransferLocation');
            if (citizenEl) citizenEl.textContent = data?.userName || data?.caller?.name || 'Guest User';
            if (typeEl) typeEl.textContent = data?.category || data?.department || data?.userConcern || 'Emergency report';
            if (locationEl) locationEl.textContent = data?.userLocation || data?.caller?.address || 'Not specified';

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
                const onConfirm = () => cleanup(true);
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
                setTimeout(() => confirmBtn?.focus(), 0);
            });
        }

        function showTransferModalNotice(data, message, state = '') {
            const modal = document.getElementById('twcTransferModal');
            if (!modal) return;
            const citizenEl = document.getElementById('twcTransferCitizen');
            const typeEl = document.getElementById('twcTransferType');
            const locationEl = document.getElementById('twcTransferLocation');
            if (citizenEl) citizenEl.textContent = data?.userName || 'Notice';
            if (typeEl) typeEl.textContent = data?.category || '-';
            if (locationEl) locationEl.textContent = data?.userLocation || '-';
            setTransferModalBusy(false);
            setTransferModalMessage(message, state);
            modal.classList.add('active');
            modal.setAttribute('aria-hidden', 'false');
            setTimeout(closeTransferModal, 1200);
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
                showTransferModalNotice({ userName: 'No report selected', category: '-', userLocation: '-' }, 'No report selected to transfer.', 'error');
                return;
            }
            const confirmed = await openTransferModal(data);
            if (!confirmed) return;
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
                priority: priorityMeta.level,
                incidentPriority: {
                    score: priorityMeta.score,
                    priority: priorityMeta.level,
                    label: priorityMeta.label,
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
                    setConversationLocked(true, data.message || 'Locked by another admin');
                    return false;
                }
                if (currentConversationData && String(currentConversationData.id) === String(conversationId)) {
                    currentConversationData.assignedTo = data.assignedTo || ADMIN_ID;
                    currentConversationData.workflowStatus = 'in_progress';
                }
                setConversationLocked(false);
                return true;
            } catch (e) {
                setConversationLocked(true, 'Unable to claim report');
                return false;
            }
        }

        async function releaseConversationForOtherAdmin() {
            if (!currentConversationId) return;
            const confirmed = await openTransferModal({
                userName: currentConversationData?.userName || 'Current report',
                category: currentConversationData?.category || currentConversationData?.userConcern || 'Report',
                userLocation: currentConversationData?.userLocation || 'Not specified'
            });
            if (!confirmed) return;
            try {
                setTransferModalBusy(true);
                setTransferModalMessage('Handing over this report to the admin queue...');
                const res = await fetch(API_BASE + 'chat-claim.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ conversationId: currentConversationId, action: 'release' })
                });
                const data = await res.json().catch(() => ({}));
                if (!data.success) {
                    setTransferModalBusy(false);
                    setTransferModalMessage(data.message || 'Failed to hand over report.', 'error');
                    return;
                }
                setTransferModalMessage('Report handed over to other admins.', 'success');
                if (currentConversationData) currentConversationData.assignedTo = null;
                closeChatPanel();
                resetConversationsAndReload();
                setTimeout(closeTransferModal, 1000);
            } catch (e) {
                setTransferModalBusy(false);
                setTransferModalMessage('Failed to hand over report.', 'error');
            }
        }

        async function updateIncidentPriorityManual(level) {
            if (!currentConversationId || PAGE_MODE !== 'citizen_reports') return;
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

        function openConversation(id, data, element) {
            if (
                currentConversationId &&
                String(currentConversationId) !== String(id) &&
                currentConversationData &&
                Number(currentConversationData.assignedTo || 0) === Number(ADMIN_ID || 0)
            ) {
                showTransferModalNotice(
                    { userName: currentConversationData.userName || 'Assigned report', category: currentConversationData.category || 'Report', userLocation: currentConversationData.userLocation || '-' },
                    'This report is assigned to you. Use Hand Over to Other Admin before opening another report.',
                    'error'
                );
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
                   if (parts.length) devStr = parts.join(' • ');
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
            if (!isClosed) {
                claimConversationForAdmin(id);
            }

            // Load Messages
            loadMessages(id, true);
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
            if (PAGE_MODE === 'citizen_reports') {
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
                                appendMessage(msg);
                                lastMessageId = Math.max(lastMessageId, msg.id);
                                added = true;
                            }
                        });
                        
                        if (added) scrollToBottom();
                    }
                } catch (e) { console.error(e); }
            };
            
            await fetchMsgs(initial); // Initial call with passed state
            messageInterval = setInterval(() => fetchMsgs(false), 1500); // Poll faster for real-time responsiveness
        }
        
        function appendMessage(msg) {
            const container = document.getElementById('chatMessages');
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
                        displayText = `Call ended • ${durationMatch[1]}`;
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
                return;
            }
            
            const div = document.createElement('div');
            const type = (msg.senderType === 'admin' || msg.senderType === 'sent') ? 'admin' : 'user';
            div.className = `message ${type}`;
            div.dataset.id = msg.id;
            
            const name = type === 'admin' ? ADMIN_USERNAME : (msg.senderName || 'User');
            const avatar = type === 'admin' ? ADMIN_AVATAR : `https://ui-avatars.com/api/?name=${encodeURIComponent(name)}&background=6c757d&color=fff&size=64`;
            
            const timeStr = msgDate.toLocaleTimeString('en-US', { 
                hour: 'numeric', 
                minute: '2-digit', 
                hour12: true 
            });
            const fullStamp = `${msgDate.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })} • ${timeStr}`;
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
            const text = input.value.trim();
            if (!text || !currentConversationId) return;
            
            input.value = '';
            input.focus();
            
            // Optimistic UI
            const tempId = Date.now(); // Temp ID
            appendMessage({
                id: tempId,
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
                const d = await res.json();
                
                if (d.success) {
                    // Update temp message with real ID if needed, or just let polling handle sync
                    if (d.messageId) lastMessageId = Math.max(lastMessageId, d.messageId);
                } else {
                    if (d.locked) setConversationLocked(true, d.message || 'Locked by another admin');
                    alert(d.message || 'Failed to send');
                }
            } catch (e) {
                alert('Send error');
            }
        }
        
        // Listeners
        document.getElementById('sendButton').onclick = sendMessage;
        document.getElementById('messageInput').onkeypress = e => { if(e.key === 'Enter') sendMessage(); };
        
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
            const transferConversationBtn = document.getElementById('transferConversationBtn');
            if (transferConversationBtn) {
                transferConversationBtn.addEventListener('click', () => transferConversationReport());
            }
            const releaseConversationBtn = document.getElementById('releaseConversationBtn');
            if (releaseConversationBtn) {
                releaseConversationBtn.addEventListener('click', releaseConversationForOtherAdmin);
            }
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

            const initialView = new URLSearchParams(window.location.search).get('view');
            if (initialView === 'chatbotLogs' || initialView === 'transfers') {
                setPrimaryView(initialView, false);
            } else {
                setPrimaryView('conversations', false);
            }

            loadConversations(true);
            pollInterval = setInterval(pollUpdates, 3000); // Poll faster for real-time list updates
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
                        <div id="callerName" style="font-weight:700;">—</div>

                        <div style="opacity:0.7;">Phone</div>
                        <div id="callerPhone" style="font-weight:700;">—</div>

                        <div style="opacity:0.7;">Address</div>
                        <div id="callerAddress" style="font-weight:600; opacity:0.95;">—</div>

                        <div style="opacity:0.7;">Location</div>
                        <div id="callerCoords" style="font-weight:600; opacity:0.95;">—</div>
                    </div>

                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:8px; padding:10px; border:1px solid rgba(255,255,255,0.10); border-radius:12px; background:rgba(255,255,255,0.04);">
                        <input id="callerNameInput" type="text" placeholder="Admin edit: caller name" autocomplete="off" style="min-width:0; padding:8px 10px; border:1px solid rgba(255,255,255,0.14); border-radius:9px; background:rgba(255,255,255,0.07); color:#fff; outline:none; font-weight:700;">
                        <input id="callerPhoneInput" type="tel" inputmode="numeric" maxlength="11" pattern="[0-9]{11}" placeholder="09XXXXXXXXX" autocomplete="off" style="min-width:0; padding:8px 10px; border:1px solid rgba(255,255,255,0.14); border-radius:9px; background:rgba(255,255,255,0.07); color:#fff; outline:none; font-weight:700;">
                        <input id="callerAddressInput" type="text" placeholder="Type location or address" style="grid-column:1 / -1; min-width:0; padding:8px 10px; border:1px solid rgba(255,255,255,0.14); border-radius:9px; background:rgba(255,255,255,0.07); color:#fff; outline:none; font-weight:600;">
                    </div>

                    <div style="border-top:1px solid rgba(255,255,255,0.10); padding-top:12px; display:flex; flex-direction:column; gap:10px;">
                        <label style="font-size:12px; opacity:0.8; margin:0;">Emergency Type</label>
                        <select id="emergencyTypeSelect" style="width:100%; padding:10px 12px; border-radius:10px; border:1px solid rgba(255,255,255,0.14); background:rgba(255,255,255,0.08); color:#fff; outline:none;">
                            <option value="" selected>Choose type…</option>
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
                            <div id="callStatus" style="opacity:0.85; font-size:13px;">Connecting…</div>
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
    const SIGNALING_URL = window.location.origin;
    const SOCKET_HEALTH_URL = `${SIGNALING_URL}${SOCKET_IO_PATH}/?EIO=4&transport=polling`;
    console.log('[call][admin] signaling endpoint v3', `${SIGNALING_URL}${SOCKET_IO_PATH}`);
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
            socket.emit('join', CALL_LOBBY_ROOM);
            if (activeCallRoom) socket.emit('join', activeCallRoom);
            if (pendingCallRoom) socket.emit('join', pendingCallRoom);
            if (restoringAdminCall && callId) requestAdminCallResume(socket);
            socketRetryCount = 0; // Reset retry count on successful connection
        });

        socket.on('disconnect', (reason) => {
            console.warn('[socket] Disconnected:', reason);
            if (callId) {
                setStatus('Connection lost. Attempting to reconnect…');
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
    const ADMIN_CALL_LOCK_KEY = `alertaraqc_active_call_${ADMIN_ID || ADMIN_USERNAME || 'admin'}`;
    const ADMIN_CALL_OWNER_KEY = String(ADMIN_ID || ADMIN_USERNAME || 'admin');
    const ADMIN_CALL_RESUME_TIMEOUT_MS = 25000;
    let restoringAdminCall = false;
    let adminCallResumeTimer = null;

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
                setStatus(result?.reason || 'The previous call is no longer available.');
                clearAdminCallLock(callId);
                setTimeout(() => {
                    setOverlayVisible(false);
                    cleanupCall();
                }, 900);
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
            ...(caller.address ? { address: caller.address } : {})
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
                ${senderName} • ${time}
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

        if (nameEl) nameEl.textContent = callerInfo?.name || '—';
        if (phoneEl) phoneEl.textContent = callerInfo?.phone || '—';

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
        
        if (addrEl) addrEl.textContent = address || '—';

        if (addressInput && !addressInput.value) addressInput.value = address || '';

        const lat = callerLocation?.lat;
        const lng = callerLocation?.lng;
        if (coordsEl) coordsEl.textContent = (lat != null && lng != null) ? `${lat}, ${lng}` : '—';
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
        const el = document.getElementById('incomingEmergencyCallRow');
        if (!el) return;
        el.style.display = visible ? 'block' : 'none';
    }

    function renderIncomingEmergencyCallRow() {
        const host = document.getElementById('incomingEmergencyCallRow');
        if (!host) return;

        if (!pendingOffer || !pendingCallId) {
            host.innerHTML = '';
            setIncomingEmergencyCallRowVisible(false);
            return;
        }

        host.innerHTML = `
            <div class="conversation-item" data-conversation-id="emergency-call" style="border:1px solid rgba(220,38,38,0.45); background: rgba(220,38,38,0.06);">
                <div style="display:flex; align-items:center; gap:10px;">
                    <div style="width:36px; height:36px; border-radius:10px; background:rgba(220,38,38,0.18); border:1px solid rgba(220,38,38,0.35); display:flex; align-items:center; justify-content:center; flex:0 0 auto;">
                        <i class="fas fa-phone-alt" style="color:#dc2626;"></i>
                    </div>
                    <div style="flex:1; min-width:0;">
                        <div style="font-weight:900; letter-spacing:0.4px;">Emergency Call</div>
                        <div style="font-size:12px; opacity:0.9;">Incoming call request</div>
                    </div>
                    <div style="display:flex; gap:8px; flex:0 0 auto;">
                        <button id="emergencyCallDeclineBtn" class="btn btn-sm btn-secondary" style="padding:0.4rem 0.65rem;">Decline</button>
                        <button id="emergencyCallAcceptBtn" class="btn btn-sm btn-primary" style="padding:0.4rem 0.65rem;">Accept</button>
                    </div>
                </div>
            </div>
        `;

        setIncomingEmergencyCallRowVisible(true);

        const acceptBtn = document.getElementById('emergencyCallAcceptBtn');
        if (acceptBtn) acceptBtn.onclick = () => {
            if (typeof window.acceptIncomingEmergencyCall === 'function') window.acceptIncomingEmergencyCall();
        };

        const declineBtn = document.getElementById('emergencyCallDeclineBtn');
        if (declineBtn) declineBtn.onclick = () => {
            if (typeof window.declineIncomingEmergencyCall === 'function') window.declineIncomingEmergencyCall();
        };
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
        try {
            if (statusEl) statusEl.textContent = 'Preparing pending transfer report...';
            const transferConversationId = await ensureCallConversationForTransfer(callerPayload, incidentDescription, priorityMetric);
            if (statusEl) statusEl.textContent = 'Starting transfer…';
            const res = await fetch(transferApiUrl(), {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    callId,
                    room: activeCallRoom || getCallRoom(),
                    socketUrl: SIGNALING_URL,
                    socketPath: SOCKET_IO_PATH,
                    emergencyType: document.getElementById('emergencyTypeSelect')?.value || '',
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
                incidentPriority: transferPriority
            });
        } catch (e) {}

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
                    // Switch to closed tab since call ended conversations are closed
                    if (typeof switchTab === 'function') {
                        switchTab('closed');
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

    function initPeer() {
        pc = new RTCPeerConnection({
            iceServers: [
                { urls: 'stun:stun.l.google.com:19302' },
                { urls: 'stun:global.stun.twilio.com:3478' }
            ]
        });

        pc.ontrack = e => {
            const remote = document.getElementById('remote');
            const remoteStream = e.streams[0];
            if (remote) remote.srcObject = remoteStream;
            monitorAudioActivity(remoteStream, 'userSpeakingLabel');
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
            if (['disconnected', 'failed', 'closed'].includes(pc.connectionState)) {
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

    async function acceptIncomingEmergencyCall() {
        if (!pendingOffer || !pendingCallId) return;
        if (callId && pendingCallId !== callId) return;
        if (adminHasActiveCall(pendingCallId)) {
            setIncomingCallModalText('You already have an active call. Finish or transfer it before taking another call.');
            renderIncomingEmergencyCallRow();
            return;
        }

        const wasRestoring = restoringAdminCall && callId === pendingCallId;
        const signalingSocket = ensureSocket();
        if (!signalingSocket?.connected) {
            setIncomingCallModalText('Call service is reconnecting. Please try again.');
            return;
        }
        const claimResult = await new Promise(resolve => {
            const timer = setTimeout(() => resolve({ ok: false, reason: 'Call claim timed out.' }), 6000);
            signalingSocket.emit('claim-call', {
                callId: pendingCallId,
                room: pendingCallRoom || getCallRoom(pendingCallId),
                adminKey: ADMIN_CALL_OWNER_KEY
            }, result => {
                clearTimeout(timer);
                resolve(result || { ok: false });
            });
        });
        if (!claimResult?.ok) {
            setIncomingCallModalText(claimResult?.reason || 'This call is no longer available.');
            pendingOffer = null;
            pendingCallId = null;
            pendingCallRoom = null;
            pendingCandidates = [];
            renderIncomingEmergencyCallRow();
            _stopAlertSound();
            if (wasRestoring) cleanupCall();
            return;
        }

        callId = pendingCallId;
        activeCallRoom = pendingCallRoom || getCallRoom(callId);
        setAdminCallLock(callId, {
            room: activeCallRoom,
            callerInfo,
            callerLocation,
            conversationId: callConversationId,
            connectedAt: callConnectedAt
        });
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
        setIncomingEmergencyCallRowVisible(false);
        setOverlayVisible(true);
        setCallActiveBannerVisible(true);
        setStatus('Connecting…');
        setTimer(0);
        setEndEnabled(false);

        try {
            if (!pc) initPeer();
            await pc.setRemoteDescription(pendingOffer);

            localStream = await navigator.mediaDevices.getUserMedia({ audio: true });
            localStream.getTracks().forEach(track => pc.addTrack(track, localStream));
            monitorAudioActivity(localStream, 'adminSpeakingLabel', 'adminLocalMicIndicator');

            const answer = await pc.createAnswer();
            await pc.setLocalDescription(answer);
            const s = ensureSocket();
            if (s) s.emit('answer', { sdp: answer, callId, room: activeCallRoom }, activeCallRoom);

            if (Array.isArray(pendingCandidates) && pendingCandidates.length) {
                for (const cand of pendingCandidates) {
                    try { if (pc && cand) await pc.addIceCandidate(cand); } catch (e) {}
                }
            }
        } catch (e) {
            setStatus('Call failed');
            setEndEnabled(true);
            endCall(true);
        } finally {
            pendingOffer = null;
            pendingCandidates = [];
            renderIncomingEmergencyCallRow();
        }
    }

    async function declineIncomingEmergencyCall() {
        if (!pendingCallId) {
            setIncomingEmergencyCallRowVisible(false);
            _stopAlertSound();
            return;
        }

        try {
            await logCall('declined', { callId: pendingCallId });
        } catch (e) {}

        try {
            await fetch('../api/save-completed-call.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    callId: pendingCallId,
                    event: 'declined',
                    conversationId: callConversationId || null,
                    userId: callerInfo?.user_id || callerInfo?.id || null,
                    userName: callerInfo?.name || 'Emergency Call User',
                    userPhone: callerInfo?.phone || null,
                    userLocation: callerInfo?.address || callerLocation?.address || null,
                    location: callerLocation || null,
                    endedAt: Math.floor(Date.now() / 1000)
                })
            });
        } catch (e) {
            console.warn('Failed to save declined call report:', e);
        }

        const s = ensureSocket();
        if (s) s.emit('hangup', { callId: pendingCallId, room: pendingCallRoom || getCallRoom(pendingCallId) }, pendingCallRoom || getCallRoom(pendingCallId));
        pendingOffer = null;
        pendingCallId = null;
        pendingCallRoom = null;
        pendingCandidates = [];
        renderIncomingEmergencyCallRow();
        _stopAlertSound();
    }

    window.acceptIncomingEmergencyCall = acceptIncomingEmergencyCall;
    window.declineIncomingEmergencyCall = declineIncomingEmergencyCall;

    let callSocketListenersBoundFor = null;
    function bindCallSocketListeners() {
        const s = ensureSocket();
        if (!s) return;
        if (callSocketListenersBoundFor === s) return;
        callSocketListenersBoundFor = s;

        s.on('offer', async payload => {
            const sdp = payload && payload.sdp ? payload.sdp : payload;
            const incomingCallId = payload && payload.callId ? payload.callId : null;
            if (!incomingCallId) return;
            if (payload && payload.transferred) return;
            const shouldAutoResume = restoringAdminCall && callId === incomingCallId;
            if (adminHasActiveCall(incomingCallId)) return;
            if (callId && incomingCallId !== callId) return;
            if (pendingCallId && pendingCallId !== incomingCallId) return;
            if (pendingCallId === incomingCallId && pendingOffer) return;

            callConversationId = payload && payload.conversationId ? payload.conversationId : null;
            callerInfo = payload && payload.caller ? payload.caller : null;
            callerLocation = payload && payload.location ? payload.location : null;
            renderCallerDetails(); // Now async, will fetch address from database

            // If no conversation ID, try to find or create one for this user
            // Note: This will be done when the call is accepted, not here
            // We'll create/find the conversation when saving the completed call

            pendingCallId = incomingCallId;
            pendingCallRoom = payload && payload.room ? payload.room : getCallRoom(incomingCallId);
            s.emit('join', pendingCallRoom);
            pendingOffer = sdp;
            pendingCandidates = [];

            try {
                if (typeof switchTab === 'function') switchTab('open');
            } catch (e) {}

            if (!shouldAutoResume) _startAlertSound(notificationSound);
            locationData = await tryGetLocation();
            await logCall(shouldAutoResume ? 'resume_offer_received' : 'incoming');
            renderIncomingEmergencyCallRow();
            if (shouldAutoResume) {
                setIncomingCallModalText('Restoring your active emergency call...');
                setTimeout(() => acceptIncomingEmergencyCall(), 0);
            }
        });

        s.on('call-claimed', payload => {
            const claimedCallId = payload?.callId || null;
            if (!claimedCallId || claimedCallId !== pendingCallId) return;
            if (String(payload?.adminKey || '') === ADMIN_CALL_OWNER_KEY) return;
            pendingOffer = null;
            pendingCallId = null;
            pendingCallRoom = null;
            pendingCandidates = [];
            renderIncomingEmergencyCallRow();
            setIncomingCallModalVisible(false);
            _stopAlertSound();
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
            const incomingCallId = payload && payload.callId ? payload.callId : null;
            if (incomingCallId && callId && incomingCallId !== callId) return;
            if (incomingCallId && pendingCallId && incomingCallId !== pendingCallId) return;
            if (transferInProgress) return;

            if (!pc || !callId) {
                if (cand && incomingCallId && pendingCallId === incomingCallId) pendingCandidates.push(cand);
                return;
            }

            if (pc && cand) pc.addIceCandidate(cand);
        });

        s.on('hangup', payload => {
            const incomingCallId = payload && payload.callId ? payload.callId : null;
            if (incomingCallId && callId && incomingCallId !== callId) return;
            if (incomingCallId && pendingCallId && incomingCallId !== pendingCallId) return;

            if (pendingCallId && incomingCallId === pendingCallId && !callId) {
                pendingOffer = null;
                pendingCallId = null;
                pendingCandidates = [];
                renderIncomingEmergencyCallRow();
                _stopAlertSound();
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
        } else {
            setStatus('Call signaling unavailable (socket server offline).');
        }
    });

    // Keep trying quietly so page can recover if socket server starts later.
    setInterval(() => {
        if (socket && socket.connected) return;
        checkSocketServerAvailability().then((available) => {
            if (available) {
                bindCallSocketListeners();
            }
        });
    }, 15000);
</script>

</body>
</html>
