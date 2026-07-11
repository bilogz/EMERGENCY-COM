<?php
// Include centralized session configuration - MUST be first
require_once __DIR__ . '/../session-config.php';

// User dashboard for emergency calling options (SIM and Internet/WiFi)
$assetBase = '../ADMIN/header/';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Emergency Dashboard</title>
    <link rel="icon" type="image/x-icon" href="<?= $assetBase ?>images/favicon.ico">
    <link rel="stylesheet" href="<?= $assetBase ?>css/global.css">
    <link rel="stylesheet" href="<?= $assetBase ?>css/buttons.css">
    <link rel="stylesheet" href="<?= $assetBase ?>css/hero.css">
    <link rel="stylesheet" href="../ADMIN/sidebar/css/global.css">
    <link rel="stylesheet" href="../ADMIN/sidebar/css/sidebar.css">
    <link rel="stylesheet" href="../ADMIN/sidebar/css/content.css">
    <link rel="stylesheet" href="../ADMIN/sidebar/css/admin-header.css">
    <link rel="stylesheet" href="../ADMIN/sidebar/css/buttons.css">
    <link rel="stylesheet" href="css/user.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <!-- Emergency Alert System -->
    <link rel="stylesheet" href="../ADMIN/header/css/emergency-alert.css">
    <style>
        .incident-report-modal {
            display: none;
            position: fixed;
            inset: 0;
            z-index: 100002;
            background: rgba(15, 23, 42, 0.65);
            backdrop-filter: blur(8px);
            padding: 1rem;
            transition: all 0.3s ease;
        }

        .incident-report-card {
            width: min(560px, 94vw);
            max-height: min(720px, 92vh);
            margin: 5vh auto 0;
            background: var(--bg-primary, #ffffff);
            color: var(--text-primary, #1f2937);
            border: 1px solid var(--border-primary, #e5e7eb);
            border-radius: 16px;
            box-shadow: var(--shadow-xl, 0 20px 25px rgba(0, 0, 0, 0.15));
            display: flex;
            flex-direction: column;
            overflow: hidden;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .incident-report-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            padding: 1rem 1.25rem;
            background: var(--bg-secondary, #f8fafc);
            border-bottom: 1px solid var(--border-primary, #e5e7eb);
        }

        .chat-header-info {
            display: flex;
            align-items: center;
            gap: 0.85rem;
        }

        .chat-header-avatar {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            background: linear-gradient(135deg, #ef4444 0%, #b91c1c 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #ffffff;
            font-size: 1.15rem;
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.25);
            position: relative;
        }

        .chat-header-avatar::after {
            content: '';
            position: absolute;
            bottom: 1px;
            right: 1px;
            width: 10px;
            height: 10px;
            background: var(--success-color, #10b981);
            border: 2px solid var(--bg-secondary, #f8fafc);
            border-radius: 50%;
            animation: pulse-green 2s infinite;
        }

        @keyframes pulse-green {
            0% { box-shadow: 0 0 0 0 rgba(34, 197, 94, 0.7); }
            70% { box-shadow: 0 0 0 6px rgba(34, 197, 94, 0); }
            100% { box-shadow: 0 0 0 0 rgba(34, 197, 94, 0); }
        }

        .chat-header-info h3 {
            margin: 0;
            font-size: 1.05rem;
            font-weight: 800;
            color: var(--text-primary, #1f2937);
        }

        .chat-header-info small {
            font-size: 0.8rem;
            color: var(--success-color, #10b981);
            font-weight: 600;
        }

        .incident-report-close {
            border: 0;
            background: transparent;
            color: var(--text-muted, #6b7280);
            cursor: pointer;
            width: 2.2rem;
            height: 2.2rem;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
        }

        .incident-report-close:hover {
            background: var(--bg-tertiary, #f1f5f9);
            color: var(--text-primary, #1f2937);
        }

        .incident-report-body {
            flex: 1;
            padding: 1.25rem;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 1rem;
            background: var(--bg-primary, #ffffff);
            scroll-behavior: smooth;
        }

        /* Messenger bubbles styling */
        .chat-bubble {
            max-width: 82%;
            padding: 0.75rem 1.05rem;
            border-radius: 18px;
            font-size: 0.92rem;
            line-height: 1.45;
            word-break: break-word;
            position: relative;
            box-shadow: var(--shadow-sm, 0 1px 2px rgba(0, 0, 0, 0.05));
            animation: fadeInBubble 0.28s cubic-bezier(0.4, 0, 0.2, 1);
        }

        @keyframes fadeInBubble {
            from { opacity: 0; transform: translateY(8px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .chat-bubble.bot {
            align-self: flex-start;
            background: var(--bg-secondary, #f8fafc);
            color: var(--text-primary, #1f2937);
            border-bottom-left-radius: 4px;
            border: 1px solid var(--border-primary, #e5e7eb);
        }

        .chat-bubble.user {
            align-self: flex-end;
            background: linear-gradient(135deg, var(--primary-color, #8e44ad) 0%, #764ba2 100%);
            color: #ffffff;
            border-bottom-right-radius: 4px;
            box-shadow: 0 4px 12px rgba(142, 68, 173, 0.25);
        }

        .chat-system-message {
            align-self: center;
            background: var(--bg-tertiary, #f1f5f9);
            color: var(--text-secondary, #4b5563);
            font-size: 0.76rem;
            font-weight: 700;
            padding: 0.35rem 0.85rem;
            border-radius: 20px;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            margin: 0.5rem 0;
            box-shadow: var(--shadow-sm, 0 1px 2px rgba(0, 0, 0, 0.05));
            border: 1px solid var(--border-primary, #e5e7eb);
        }

        /* Option items (rendered inside bot bubbles) */
        .chat-options-bubble {
            display: flex;
            flex-direction: column;
            gap: 0.55rem;
            margin-top: 0.6rem;
            width: 100%;
            min-width: 220px;
        }

        .chat-options-bubble .chat-option-btn {
            width: 100%;
            padding: 0.72rem 1.1rem;
            background: var(--bg-primary, #ffffff);
            border: 1px solid var(--border-secondary, #d1d5db);
            color: var(--text-primary, #1f2937);
            border-radius: 12px;
            text-align: left;
            font-size: 0.88rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            align-items: center;
            gap: 0.6rem;
            box-shadow: var(--shadow-sm, 0 1px 2px rgba(0, 0, 0, 0.05));
        }

        .chat-options-bubble .chat-option-btn i {
            font-size: 1rem;
            width: 1.25rem;
            text-align: center;
        }

        .chat-options-bubble .chat-option-btn:hover {
            background: var(--primary-light, rgba(142, 68, 173, 0.08));
            border-color: var(--primary-color, #8e44ad);
            color: var(--primary-color, #8e44ad);
            transform: translateX(4px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.05);
        }

        /* Location custom interface inside bot bubbles */
        .chat-location-input-bubble {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            margin-top: 0.6rem;
            width: 100%;
            min-width: 240px;
        }

        .chat-location-input-bubble input {
            width: 100%;
            padding: 0.65rem 0.9rem;
            border: 1px solid var(--border-secondary, #d1d5db);
            border-radius: 10px;
            background: var(--bg-primary, #ffffff);
            color: var(--text-primary, #1f2937);
            font-size: 0.88rem;
            outline: none;
            transition: all 0.2s ease;
        }

        .chat-location-input-bubble input:focus {
            border-color: var(--primary-color, #8e44ad);
            box-shadow: 0 0 0 3px var(--primary-light, rgba(142, 68, 173, 0.15));
        }

        .chat-location-buttons {
            display: flex;
            gap: 0.5rem;
        }

        .chat-location-btn {
            flex: 1;
            padding: 0.55rem 0.85rem;
            border-radius: 8px;
            font-size: 0.82rem;
            font-weight: 700;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.35rem;
            transition: all 0.2s ease;
        }

        .chat-location-btn.primary {
            background: var(--primary-color, #8e44ad);
            color: #ffffff;
            border: none;
            box-shadow: 0 3px 8px rgba(142, 68, 173, 0.2);
        }

        .chat-location-btn.primary:hover {
            background: var(--primary-hover, #7d3d9a);
            transform: translateY(-1px);
        }

        .chat-location-btn.secondary {
            background: var(--bg-secondary, #f8fafc);
            color: var(--text-primary, #1f2937);
            border: 1px solid var(--border-secondary, #d1d5db);
        }

        .chat-location-btn.secondary:hover {
            background: var(--bg-tertiary, #f1f5f9);
            transform: translateY(-1px);
        }

        /* Input Area styles */
        .incident-chat-input-area {
            padding: 0.85rem 1.25rem;
            background: var(--bg-secondary, #f8fafc);
            border-top: 1px solid var(--border-primary, #e5e7eb);
            display: flex;
            flex-direction: column;
            gap: 0.55rem;
        }

        .incident-chat-input-row {
            display: flex;
            align-items: flex-end;
            gap: 0.65rem;
        }

        .chat-action-btn {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            border: 1px solid var(--border-secondary, #d1d5db);
            background: var(--bg-primary, #ffffff);
            color: var(--text-secondary, #4b5563);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
            flex-shrink: 0;
            margin-bottom: 2px;
        }

        .chat-action-btn:hover {
            background: var(--bg-tertiary, #f1f5f9);
            color: var(--primary-color, #8e44ad);
            border-color: var(--primary-color, #8e44ad);
        }

        .chat-action-btn.active {
            background: var(--primary-color, #8e44ad);
            color: #ffffff;
            border-color: var(--primary-color, #8e44ad);
        }

        #incidentChatInput {
            flex: 1;
            min-height: 42px;
            max-height: 140px;
            padding: 0.6rem 1.1rem;
            border: 1px solid var(--border-secondary, #d1d5db);
            border-radius: 22px;
            background: var(--bg-primary, #ffffff);
            color: var(--text-primary, #1f2937);
            font-size: 0.92rem;
            resize: none;
            line-height: 1.4;
            outline: none;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
            box-sizing: border-box;
            scrollbar-width: none;
        }

        #incidentChatInput::-webkit-scrollbar {
            display: none;
        }

        #incidentChatInput:focus {
            border-color: var(--primary-color, #8e44ad);
            box-shadow: 0 0 0 3px var(--primary-light, rgba(142, 68, 173, 0.12));
        }

        .chat-send-btn {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            border: none;
            background: var(--primary-color, #8e44ad);
            color: #ffffff;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            flex-shrink: 0;
            margin-bottom: 2px;
        }

        .chat-send-btn:hover:not(:disabled) {
            background: var(--primary-hover, #7d3d9a);
            transform: scale(1.05);
        }

        .chat-send-btn:disabled {
            background: var(--border-secondary, #d1d5db);
            color: var(--text-muted, #6b7280);
            cursor: not-allowed;
            transform: none;
        }

        /* Link Input Drawer */
        .incident-link-drawer {
            display: flex;
            gap: 0.5rem;
            padding: 0.5rem 0.75rem;
            background: var(--bg-primary, #ffffff);
            border-radius: 10px;
            border: 1px solid var(--border-primary, #e5e7eb);
            margin-bottom: 0.25rem;
            animation: slideDown 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        }

        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-8px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .incident-link-drawer input {
            flex: 1;
            padding: 0.45rem 0.75rem;
            border: 1px solid var(--border-secondary, #d1d5db);
            border-radius: 8px;
            font-size: 0.85rem;
            background: var(--bg-secondary, #f8fafc);
            color: var(--text-primary, #1f2937);
            outline: none;
        }

        .incident-link-drawer input:focus {
            border-color: var(--primary-color, #8e44ad);
        }

        /* Attachment Preview */
        .incident-attachment-preview {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.45rem 0.85rem;
            background: var(--primary-light, rgba(142, 68, 173, 0.06));
            border: 1px solid var(--primary-color, #8e44ad);
            border-radius: 10px;
            margin-top: 0.25rem;
            animation: slideDown 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .attachment-name {
            font-size: 0.84rem;
            font-weight: 700;
            color: var(--primary-color, #8e44ad);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 82%;
            display: flex;
            align-items: center;
            gap: 0.4rem;
        }

        .remove-attachment-btn {
            border: none;
            background: transparent;
            color: var(--primary-color, #8e44ad);
            cursor: pointer;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            transition: background 0.2s ease;
        }

        .remove-attachment-btn:hover {
            background: rgba(142, 68, 173, 0.12);
        }

        /* Custom Scrollbar for modern feel */
        .incident-report-body::-webkit-scrollbar {
            width: 6px;
        }

        .incident-report-body::-webkit-scrollbar-track {
            background: transparent;
        }

        .incident-report-body::-webkit-scrollbar-thumb {
            background-color: var(--border-secondary, #d1d5db);
            border-radius: 10px;
        }

        .incident-report-body::-webkit-scrollbar-thumb:hover {
            background-color: var(--text-muted, #6b7280);
        }

        @media (max-width: 480px) {
            .incident-report-modal {
                padding: 0.5rem;
            }
            .incident-report-card {
                margin: 2vh auto 0;
                width: 100%;
                max-height: 96vh;
                border-radius: 14px;
            }
            .incident-report-body {
                padding: 0.85rem;
                gap: 0.85rem;
            }
            .chat-bubble {
                max-width: 90%;
                font-size: 0.88rem;
                padding: 0.65rem 0.9rem;
            }
            .incident-chat-input-area {
                padding: 0.65rem 0.85rem;
            }
            #incidentChatInput {
                min-height: 38px;
                padding: 0.55rem 0.95rem;
            }
            .chat-action-btn, .chat-send-btn {
                width: 36px;
                height: 36px;
            }
        }
    </style>
    <script>
        // Set global API base path for all JS files
        window.API_BASE_PATH = 'api/';
    </script>
    <script src="js/translations.js"></script>
    <script src="js/language-manager.js"></script>
    <script src="js/global-translator.js"></script>
    <script src="js/language-selector-modal.js"></script>
    <script src="js/language-sync.js"></script>
    <script>
        // Ensure sidebar functions are available before translation scripts interfere
        // This runs immediately, before DOMContentLoaded
        (function() {
            if (typeof window.sidebarToggle !== 'function') {
                window.sidebarToggle = function() {
                    const sidebar = document.getElementById('sidebar');
                    const sidebarOverlay = document.getElementById('sidebarOverlay');
                    if (sidebar) {
                        sidebar.classList.toggle('sidebar-open');
                        if (sidebarOverlay) {
                            sidebarOverlay.classList.toggle('sidebar-overlay-open');
                        }
                        document.body.classList.toggle('sidebar-open');
                    }
                };
            }
            if (typeof window.sidebarClose !== 'function') {
                window.sidebarClose = function() {
                    const sidebar = document.getElementById('sidebar');
                    const sidebarOverlay = document.getElementById('sidebarOverlay');
                    if (sidebar) {
                        sidebar.classList.remove('sidebar-open');
                        if (sidebarOverlay) {
                            sidebarOverlay.classList.remove('sidebar-overlay-open');
                        }
                        document.body.classList.remove('sidebar-open');
                    }
                };
            }
        })();
        
        document.addEventListener('DOMContentLoaded', function() {
            const langBtn = document.getElementById('languageSelectorBtn');
            if (langBtn && window.languageSelectorModal) {
                langBtn.addEventListener('click', function() {
                    window.languageSelectorModal.open();
                });
            }
            
            // Verify sidebar functions are still available after translation scripts run
            if (typeof window.sidebarToggle !== 'function') {
                console.error('CRITICAL: window.sidebarToggle was removed or overwritten!');
                // Restore it
                window.sidebarToggle = function() {
                    const sidebar = document.getElementById('sidebar');
                    const sidebarOverlay = document.getElementById('sidebarOverlay');
                    if (sidebar) {
                        sidebar.classList.toggle('sidebar-open');
                        if (sidebarOverlay) {
                            sidebarOverlay.classList.toggle('sidebar-overlay-open');
                        }
                        document.body.classList.toggle('sidebar-open');
                    }
                };
            }
            
            // Protect sidebar toggle buttons from translation interference
            const toggleButtons = document.querySelectorAll('.sidebar-toggle-btn');
            toggleButtons.forEach(function(btn) {
                // Ensure onclick is set correctly
                if (!btn.getAttribute('onclick') || !btn.getAttribute('onclick').includes('sidebarToggle')) {
                    btn.setAttribute('onclick', 'window.sidebarToggle()');
                }
                // Ensure data-no-translate is set
                if (!btn.hasAttribute('data-no-translate')) {
                    btn.setAttribute('data-no-translate', '');
                }
            });
        });
    </script>
</head>
<body class="user-admin-header">
    <?php include 'includes/user-global-header.php'; ?>

    <main class="main-content" style="padding-top: 60px;">
        <div class="hero-section" id="call-now">
            <div class="main-container">
                <div class="sub-container">
                    <h1 data-translate="emergency.title">Call for Emergency</h1>
                    <p data-translate="emergency.subtitle">Use data or WiFi to connect with responders via Internet calling.</p>
                    <div class="hero-buttons action-buttons">
                        <button class="btn btn-primary" onclick="startInternetCall()"><i class="fas fa-headset"></i> <span>Call for Emergency via Internet</span></button>
                    </div>
                </div>
            </div>
        </div>

        <div class="main-container">
            <div class="sub-container content-main">
                <section id="internet-call" class="page-content">
                    <h2>Call Using Internet/WiFi</h2>
                    <p>Use data or WiFi when cellular signal is weak. Connect with emergency responders via VoIP or web-based calling.</p>
                    <div class="cards-grid">
                        <div class="card">
                            <h3>Call for Emergency or Report</h3>
                            <p>Start a voice call over WiFi or mobile data. Connect directly with emergency dispatchers.</p>
                            <button class="btn btn-primary" onclick="startInternetCall()">
                                <i class="fas fa-headset"></i> <span>Call for Emergency via Internet</span>
                            </button>
                        </div>
                        <div class="card">
                            <h3>Report Incident</h3>
                            <p>Submit incident details, photos, files, or related links so dispatchers can review and respond.</p>
                            <button class="btn btn-secondary" onclick="openIncidentReport()">
                                <i class="fas fa-triangle-exclamation"></i> <span>Report Incident</span>
                            </button>
                        </div>
                    </div>
                </section>

            </div>
        </div>
    </main>

    <?php include 'includes/footer-snippet.php'; ?>

    <!-- Firebase SDK (for chat) - Loaded dynamically by sidebar.php to avoid conflicts -->
    
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="<?= $assetBase ?>js/mobile-menu.js"></script>
    <script src="<?= $assetBase ?>js/theme-toggle.js"></script>
    
    <!-- Emergency Call Button and Audio -->
    <button id="call" style="display: none;">Emergency Call</button>
    <div id="callOverlay" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.55); z-index:100000;">
        <div style="position:absolute; left:50%; top:50%; transform:translate(-50%,-50%); width:min(420px, 92vw); height:min(600px, 85vh); background:#0f172a; border:1px solid rgba(255,255,255,0.12); border-radius:16px; padding:22px; color:#fff; box-shadow:0 20px 60px rgba(0,0,0,0.5); display:flex; flex-direction:column;">
            <div id="callActiveBanner" style="display:none; margin:-6px 0 12px; padding:8px 12px; border-radius:12px; background:rgba(220,38,38,0.18); border:1px solid rgba(220,38,38,0.45); color:#fecaca; font-weight:800; letter-spacing:0.6px; text-transform:uppercase; text-align:center;">CALL ON ACTIVE</div>
            <!-- Call Header -->
            <div style="display:flex; align-items:center; gap:12px; flex-shrink:0;">
                <div style="width:44px; height:44px; border-radius:12px; background:rgba(76,138,137,0.2); display:flex; align-items:center; justify-content:center;">
                    <i class="fas fa-headset" style="color:#4c8a89;"></i>
                </div>
                <div style="flex:1;">
                    <div style="font-weight:700; font-size:16px;">Emergency Call</div>
                    <div id="callStatus" style="opacity:0.85; font-size:13px;">Connecting…</div>
                </div>
                <div id="callTimer" style="font-variant-numeric:tabular-nums; font-weight:700;">00:00</div>
            </div>
            
            <!-- Messages Area -->
            <div id="callMessages" style="flex:1; margin-top:16px; overflow-y:auto; border:1px solid rgba(255,255,255,0.1); border-radius:8px; padding:12px; background:rgba(0,0,0,0.2); min-height:200px;">
                <div style="text-align:center; opacity:0.6; font-size:12px;">Messages will appear here</div>
            </div>
            
            <!-- Message Input -->
            <div id="callInputRow" style="margin-top:12px; display:flex; gap:10px; flex-shrink:0; align-items:center;"></div>
            
            <!-- Call Controls -->
            <div style="margin-top:14px; display:flex; gap:10px; justify-content:flex-end; flex-shrink:0;">
                <button id="cancelCallBtn" class="btn btn-secondary" style="min-height:44px; padding:10px 16px;">Cancel</button>
                <button id="endCallBtn" class="btn btn-secondary" disabled style="opacity:0.6; pointer-events:none; min-height:44px; padding:10px 16px;">End Call</button>
            </div>
        </div>
    </div>
    <div class="incident-report-modal" id="incidentReportModal" aria-hidden="true">
        <div class="incident-report-card">
            <!-- Chat Header -->
            <div class="incident-report-head">
                <div class="chat-header-info">
                    <div class="chat-header-avatar">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <div>
                        <h3>Emergency Response Chat</h3>
                        <small id="chatConnectionStatus">Active Connection</small>
                    </div>
                </div>
                <button type="button" class="incident-report-close" onclick="closeIncidentReport()" aria-label="Close">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <!-- Chat Message Log -->
            <div class="incident-report-body" id="incidentChatMessages">
                <!-- Dynamic chat bubbles go here -->
            </div>

            <!-- Chat Input Area -->
            <div class="incident-chat-input-area" id="incidentChatInputArea">
                <div class="incident-chat-input-row">
                    <button type="button" class="chat-action-btn" id="incidentLinkBtn" title="Add related link">
                        <i class="fas fa-link"></i>
                    </button>
                    <button type="button" class="chat-action-btn" id="incidentFileBtn" title="Attach file or photo">
                        <i class="fas fa-paperclip"></i>
                    </button>
                    <input type="file" id="incidentFileFile" style="display:none;" accept="image/*,video/*,.pdf,.doc,.docx,.txt,.eml">
                    <textarea id="incidentChatInput" placeholder="Describe the incident details..." rows="1" required></textarea>
                    <button type="button" class="chat-send-btn" id="incidentSendBtn" onclick="onIncidentSendClick()">
                        <i class="fas fa-paper-plane"></i>
                    </button>
                </div>
                
                <!-- Link input drawer -->
                <div class="incident-link-drawer" id="incidentLinkDrawer" style="display:none;">
                    <input type="url" id="incidentLinkInput" placeholder="Paste related link (e.g. Facebook/TikTok)...">
                    <button type="button" class="btn btn-sm btn-primary" onclick="saveIncidentLink()">Add</button>
                </div>
                
                <!-- Attachment preview -->
                <div class="incident-attachment-preview" id="incidentAttachmentPreview" style="display:none;">
                    <span class="attachment-name" id="incidentAttachmentName">file.jpg</span>
                    <button type="button" class="remove-attachment-btn" onclick="clearIncidentAttachment()"><i class="fas fa-times"></i></button>
                </div>
            </div>
        </div>
    </div>
    <audio id="remote" autoplay></audio>

    <script src="https://cdn.socket.io/4.7.5/socket.io.min.js"></script>
    <script>
        const IS_LOCAL = ['localhost', '127.0.0.1'].includes(window.location.hostname);
        const SOCKET_IO_PATH = '/socket.io';
        const LOCAL_SOCKET_PORT = 3000;
        const SIGNALING_HOST = window.location.hostname === 'localhost' ? '127.0.0.1' : window.location.hostname;
        const SIGNALING_URL = IS_LOCAL ? `${window.location.protocol}//${SIGNALING_HOST}` + ':' + LOCAL_SOCKET_PORT : null;
        let socket = null;
        let socketBound = false;
        const room = "emergency-room";
        let socketRetryCount = 0;
        const MAX_SOCKET_RETRIES = 5;

        function waitForSocketConnected(s, timeoutMs = 8000) {
            return new Promise((resolve, reject) => {
                if (!s) return reject(new Error('No socket'));
                if (s.connected) return resolve(true);
                const t = setTimeout(() => {
                    cleanup();
                    reject(new Error('Socket connect timeout'));
                }, timeoutMs);
                const onConnect = () => {
                    cleanup();
                    resolve(true);
                };
                const onErr = (err) => {
                    cleanup();
                    reject(err || new Error('Socket connect error'));
                };
                const cleanup = () => {
                    clearTimeout(t);
                    s.off('connect', onConnect);
                    s.off('connect_error', onErr);
                };
                s.on('connect', onConnect);
                s.on('connect_error', onErr);
            });
        }

        function ensureSocket() {
            if (socket && socket.connected) return socket;
            if (typeof window.io !== 'function') {
                console.error('[socket] Socket.IO library not loaded');
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

            socket = IS_LOCAL
                ? window.io(SIGNALING_URL, socketOptions)
                : window.io(socketOptions);
            bindSocketHandlers();
            return socket;
        }

        function bindSocketHandlers() {
            if (!socket || socketBound) return;
            socketBound = true;

            socket.on('connect', () => {
                console.log('[call][user] socket connected', socket.id);
                socket.emit('join', room);
                socketRetryCount = 0; // Reset retry count on successful connection
            });

            socket.on('disconnect', (reason) => {
                console.warn('[call][user] socket disconnected', reason);
                if (callId) {
                    setStatus('Connection lost. Attempting to reconnect…');
                }
            });

            socket.on('connect_error', (error) => {
                console.error('[call][user] socket connection error:', error);
                socketRetryCount++;
                if (socketRetryCount >= MAX_SOCKET_RETRIES) {
                    console.error('[call][user] Max retries reached. Giving up.');
                    if (callId) {
                        setStatus('Connection failed. Please refresh the page.');
                        setEndEnabled(true);
                    }
                } else {
                    console.log(`[call][user] Retry ${socketRetryCount}/${MAX_SOCKET_RETRIES}`);
                    if (callId) {
                        setStatus(`Connecting... (attempt ${socketRetryCount}/${MAX_SOCKET_RETRIES})`);
                    }
                }
            });

            socket.on("answer", payload => {
                const sdp = payload && payload.sdp ? payload.sdp : payload;
                const incomingCallId = payload && payload.callId ? payload.callId : null;
                if (incomingCallId && incomingCallId !== callId) return;
                if (pc) pc.setRemoteDescription(sdp);
            });

            socket.on("candidate", payload => {
                const cand = payload && payload.candidate ? payload.candidate : payload;
                const incomingCallId = payload && payload.callId ? payload.callId : null;
                if (incomingCallId && incomingCallId !== callId) return;
                if (pc && cand) pc.addIceCandidate(cand);
            });

            socket.on('hangup', payload => {
                const incomingCallId = payload && payload.callId ? payload.callId : null;
                if (incomingCallId && incomingCallId !== callId) return;
                if (callId) endCall(false);
            });

            socket.on('call-message', payload => {
                const incomingCallId = payload && payload.callId ? payload.callId : null;
                if (incomingCallId && incomingCallId !== callId) return;
                if (payload.text && payload.sender !== 'user') {
                    addMessage(payload.text, payload.sender || 'admin', payload.timestamp);
                }
            });

            socket.on('connect_error', () => {
                if (callId) {
                    setStatus('Connecting failed. Signaling server offline.');
                    setEndEnabled(true);
                }
            });
        }

        let pc = null;
        let localStream = null;
        let callId = null;
        let callConversationId = null;
        let callStartedAt = null;
        let callConnectedAt = null;
        let timerInterval = null;
        let locationData = null;
        let userProfile = null;
        let messages = [];

        async function ensureCallConversationId() {
            if (callConversationId) return callConversationId;
            if (!userProfile || !userProfile.id) return null;

            try {
                const params = new URLSearchParams({
                    userId: String(userProfile.id),
                    userName: userProfile.name || userProfile.username || 'User',
                    userEmail: userProfile.email || '',
                    userPhone: userProfile.phone || '',
                    userLocation: '',
                    userConcern: 'emergency',
                    isGuest: '0'
                });
                const res = await fetch(`api/chat-get-conversation.php?${params.toString()}`);
                const data = await res.json();
                if (data && data.success && data.conversationId) {
                    callConversationId = data.conversationId;
                    return callConversationId;
                }
            } catch (e) {}

            return null;
        }

        // Messaging functions
        function addMessage(text, sender = 'user', timestamp = Date.now()) {
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
                background: ${sender === 'user' ? 'rgba(59, 130, 246, 0.2)' : 'rgba(34, 197, 94, 0.2)'};
                border-left: 3px solid ${sender === 'user' ? '#3b82f6' : '#22c55e'};
                font-size: 13px;
                line-height: 1.4;
            `;
            
            const time = new Date(timestamp).toLocaleTimeString('en-US', { 
                hour: 'numeric', 
                minute: '2-digit', 
                hour12: true 
            });
            
            const senderName = sender === 'user' ? 
                (userProfile?.name || 'You') : 
                'Emergency Services';
            
            messageDiv.innerHTML = `
                <div style="font-weight: 600; margin-bottom: 2px; font-size: 11px; opacity: 0.8;">
                    ${senderName} • ${time}
                </div>
                <div>${text}</div>
            `;
            
            messagesContainer.appendChild(messageDiv);
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
            
            messages.push({ text, sender, timestamp, callId });
        }

        async function sendCallMessage() {
            const input = document.getElementById('callMessageInput');
            if (!input) return;
            const text = input.value.trim();
            if (!text || !callId) return;
            
            input.value = '';
            
            // Add to local UI immediately
            addMessage(text, 'user');
            
            // Send via socket
            const s = ensureSocket();
            if (s) {
                s.emit('call-message', {
                    text,
                    callId,
                    sender: 'user',
                    senderName: userProfile?.name || 'User',
                    timestamp: Date.now()
                }, room);
            }
            
            // Log to database using existing chat-send structure
            try {
                const convId = await ensureCallConversationId();
                const formData = new FormData();
                formData.append('text', text);
                formData.append('userId', userProfile?.id || 'guest');
                formData.append('userName', userProfile?.name || 'Guest User');
                formData.append('userEmail', userProfile?.email || '');
                formData.append('userPhone', userProfile?.phone || '');
                if (convId) formData.append('conversationId', convId);
                
                const response = await fetch('api/chat-send.php', {
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

        async function loadUserProfile() {
            try {
                const response = await fetch('api/get-user-profile.php');
                const data = await response.json();
                if (data.success) {
                    userProfile = {
                        id: data.user.id,
                        name: data.user.name || data.user.username,
                        username: data.user.username,
                        email: data.user.email,
                        phone: data.user.phone
                    };
                }
            } catch (e) {
                console.error('Failed to load user profile:', e);
            }
        }

        function formatTime(totalSeconds) {
            const m = String(Math.floor(totalSeconds / 60)).padStart(2, '0');
            const s = String(totalSeconds % 60).padStart(2, '0');
            return `${m}:${s}`;
        }

        function setOverlayVisible(visible) {
            document.getElementById('callOverlay').style.display = visible ? 'block' : 'none';
            if (visible) {
                try { bindCallOverlayUi(); } catch (e) {}
            }
        }

        function setCallActiveBannerVisible(visible) {
            const el = document.getElementById('callActiveBanner');
            if (!el) return;
            el.style.display = visible ? 'block' : 'none';
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

        function setCancelVisible(visible) {
            const btn = document.getElementById('cancelCallBtn');
            if (!btn) return;
            btn.style.display = visible ? 'inline-block' : 'none';
        }

        async function cancelCall() {
            if (!callId) return;
            
            await logCall('cancelled');
            const s = ensureSocket();
            if (s && callId) {
                s.emit('hangup', { callId }, room);
            }
            setStatus('Call cancelled');
            setTimeout(() => {
                setOverlayVisible(false);
                cleanupCall();
            }, 800);
        }

        function setStartButtonsDisabled(disabled) {
            document.querySelectorAll('button[onclick="startInternetCall()"]')
                .forEach(b => { b.disabled = disabled; });
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
                const payload = {
                    callId,
                    userId: userProfile?.id || null,
                    room,
                    role: 'user',
                    event,
                    location: locationData,
                    ...extra
                };
                await fetch('api/call-log.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
            } catch (e) {}
        }

        function startTimer() {
            if (!callConnectedAt) return;
            if (timerInterval) clearInterval(timerInterval);
            timerInterval = setInterval(() => {
                const seconds = Math.max(0, Math.floor((Date.now() - callConnectedAt) / 1000));
                setTimer(seconds);
            }, 1000);
        }

        function stopTimer() {
            if (timerInterval) clearInterval(timerInterval);
            timerInterval = null;
        }

        function cleanupCall() {
            stopTimer();
            setEndEnabled(false);
            setCancelVisible(false);
            setCallActiveBannerVisible(false);
            
            // Clear messages
            messages = [];
            const messagesContainer = document.getElementById('callMessages');
            if (messagesContainer) {
                messagesContainer.innerHTML = '<div style="text-align:center; opacity:0.6; font-size:12px;">Messages will appear here</div>';
            }
            
            // Clear message input
            const callMessageInput = document.getElementById('callMessageInput');
            if (callMessageInput) {
                callMessageInput.value = '';
            }
            
            if (localStream) {
                localStream.getTracks().forEach(t => t.stop());
                localStream = null;
            }
            if (pc) {
                try { pc.close(); } catch (e) {}
                pc = null;
            }
            callConnectedAt = null;
            callStartedAt = null;
            callId = null;
            locationData = null;
            setTimer(0);
            setStartButtonsDisabled(false);
        }

        async function endCall(notifyPeer = true) {
            const durationSec = callConnectedAt ? Math.floor((Date.now() - callConnectedAt) / 1000) : null;
            await logCall('ended', { durationSec });
            if (notifyPeer && callId) {
                const s = ensureSocket();
                if (s) {
                    s.emit('hangup', { callId }, room);
                }
            }
            setStatus('Call ended');
            setTimeout(() => {
                setOverlayVisible(false);
                cleanupCall();
            }, 800);
        }

        function renderCallInputRow() {
            const row = document.getElementById('callInputRow');
            if (!row) return;

            row.innerHTML = `
                <input type="text" id="callMessageInput" placeholder="Type a message..." style="flex:1; width:100%; padding:10px 12px; border:1px solid rgba(255,255,255,0.18); border-radius:10px; background:rgba(255,255,255,0.08); color:#fff; outline:none;">
                <button id="callSendMessageBtn" class="btn btn-primary" type="button" style="padding:10px 16px; background:#4c8a89; border:1px solid rgba(255,255,255,0.12); color:#fff; border-radius:10px; font-weight:800; min-height:44px;">Send</button>
            `;
        }

        function bindCallOverlayUi() {
            renderCallInputRow();

            const endBtn = document.getElementById('endCallBtn');
            if (endBtn) endBtn.onclick = () => endCall(true);

            const cancelBtn = document.getElementById('cancelCallBtn');
            if (cancelBtn) cancelBtn.onclick = () => cancelCall();

            const sendBtn = document.getElementById('callSendMessageBtn');
            if (sendBtn) sendBtn.onclick = () => sendCallMessage();

            const input = document.getElementById('callMessageInput');
            if (input) {
                input.onkeypress = (e) => {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        sendCallMessage();
                    }
                };
            }
        }

        // Load user profile and bind UI when page loads
        document.addEventListener('DOMContentLoaded', () => {
            console.log('[call][user] emergency-call overlay script loaded v2');
            loadUserProfile();
            bindCallOverlayUi();
        });

        function initPeer() {
            pc = new RTCPeerConnection({
                iceServers: [
                    { urls: 'stun:stun.l.google.com:19302' },
                    { urls: 'stun:global.stun.twilio.com:3478' }
                ]
            });
            pc.ontrack = e => {
                document.getElementById("remote").srcObject = e.streams[0];
            };
            pc.onicecandidate = e => {
                if (!e.candidate) return;
                const s = ensureSocket();
                if (s) {
                    s.emit('candidate', { candidate: e.candidate, callId }, room);
                }
            };
            pc.onconnectionstatechange = () => {
                if (!pc) return;
                if (pc.connectionState === 'connected' && !callConnectedAt) {
                    callConnectedAt = Date.now();
                    setStatus('Connected');
                    setEndEnabled(true);
                    setCancelVisible(false);
                    setCallActiveBannerVisible(true);
                    startTimer();
                    logCall('connected');
                }
                if (['disconnected', 'failed', 'closed'].includes(pc.connectionState)) {
                    if (callId) endCall();
                }
            };
        }

        document.getElementById("call").onclick = async () => {
            if (callId) return;

            setOverlayVisible(true);
            setStatus('Connecting…');
            setTimer(0);
            setEndEnabled(false);
            setCancelVisible(true);
            setCallActiveBannerVisible(false);

            const s = ensureSocket();
            if (!s) {
                setStatus('Call service unavailable. Start the signaling server on port 3000.');
                setEndEnabled(true);
                setCancelVisible(false);
                return;
            }

            if (s && s.connected === false) setStatus('Connecting to call service…');

            try {
                await waitForSocketConnected(s);
                callId = (window.crypto && crypto.randomUUID) ? crypto.randomUUID() : `call_${Date.now()}_${Math.random().toString(16).slice(2)}`;
                callStartedAt = Date.now();
                setStartButtonsDisabled(true);
                locationData = await tryGetLocation();
                await logCall('started');

                await ensureCallConversationId();

                initPeer();
                s.emit("join", room);

                localStream = await navigator.mediaDevices.getUserMedia({ audio: true });
                localStream.getTracks().forEach(track => pc.addTrack(track, localStream));

                const offer = await pc.createOffer();
                await pc.setLocalDescription(offer);
                console.log('[call][user] emitting offer', { callId, room });
                const caller = userProfile ? {
                    id: userProfile.id ?? null,
                    name: userProfile.name ?? null,
                    email: userProfile.email ?? null,
                    phone: userProfile.phone ?? null,
                    nationality: userProfile.nationality ?? null,
                    district: userProfile.district ?? null,
                    barangay: userProfile.barangay ?? null,
                    house_number: userProfile.house_number ?? null,
                    street: userProfile.street ?? null,
                    address: userProfile.address ?? null
                } : null;

                s.emit("offer", {
                    sdp: offer,
                    callId,
                    conversationId: callConversationId,
                    userId: userProfile?.id || null,
                    userName: userProfile?.name || null,
                    caller,
                    location: locationData || null
                }, room);
            } catch (e) {
                console.error('[call][user] call failed', e);
                setStatus('Call failed');
                setEndEnabled(true);
                setCancelVisible(false);
                cleanupCall();
            }
        };

        function startInternetCall() {
            document.getElementById("call").click();
        }

        let incidentReportStep = 1;
        let selectedCategory = '';
        let selectedLocation = '';
        let selectedDetails = '';
        let selectedLink = '';
        let selectedAttachment = null;
        let activeIncidentConversationId = sessionStorage.getItem('active_incident_conversation_id') || null;
        let incidentChatPollInterval = null;
        let lastIncidentMessageId = 0;

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        function linkify(text) {
            const urlPattern = /(\b(https?|ftp|file):\/\/[-A-Z0-9+&@#\/%?=~_|!:,.;]*[-A-Z0-9+&@#\/%=~_|])/ig;
            return text.replace(urlPattern, '<a href="$1" target="_blank" rel="noopener noreferrer" style="color: #38bdf8; text-decoration: underline;">$1</a>');
        }

        function appendBotMessage(html) {
            const container = document.getElementById('incidentChatMessages');
            if (!container) return;
            const bubble = document.createElement('div');
            bubble.className = 'chat-bubble bot';
            bubble.innerHTML = html;
            container.appendChild(bubble);
            container.scrollTop = container.scrollHeight;
        }

        function appendUserMessage(text) {
            const container = document.getElementById('incidentChatMessages');
            if (!container) return;
            const bubble = document.createElement('div');
            bubble.className = 'chat-bubble user';
            bubble.textContent = text;
            container.appendChild(bubble);
            container.scrollTop = container.scrollHeight;
        }

        function appendSystemMessage(text) {
            const container = document.getElementById('incidentChatMessages');
            if (!container) return;
            const bubble = document.createElement('div');
            bubble.className = 'chat-system-message';
            bubble.textContent = text;
            container.appendChild(bubble);
            container.scrollTop = container.scrollHeight;
        }

        function startIncidentBot() {
            const container = document.getElementById('incidentChatMessages');
            if (!container) return;
            container.innerHTML = '';
            
            incidentReportStep = 1;
            selectedCategory = '';
            selectedLocation = '';
            selectedDetails = '';
            selectedLink = '';
            selectedAttachment = null;
            clearIncidentAttachment();
            
            const chatInput = document.getElementById('incidentChatInput');
            if (chatInput) {
                chatInput.value = '';
                chatInput.style.height = '';
                chatInput.disabled = true;
                chatInput.placeholder = "Please follow the chat bot prompts...";
            }
            
            document.getElementById('incidentSendBtn').disabled = true;
            document.getElementById('incidentLinkBtn').disabled = true;
            document.getElementById('incidentFileBtn').disabled = true;

            appendBotMessage("Hello! I am your Emergency Chat Assistant. Please select the type of incident you want to report:");

            const optionsHtml = `
                <div class="chat-options-bubble" id="botIncidentTypeOptions">
                    <button class="chat-option-btn" onclick="selectIncidentType('Medical Emergency', 'Medical Emergency')">
                        <i class="fas fa-heartbeat"></i> Medical Emergency
                    </button>
                    <button class="chat-option-btn" onclick="selectIncidentType('Fire Emergency', 'Fire Emergency')">
                        <i class="fas fa-fire"></i> Fire Emergency
                    </button>
                    <button class="chat-option-btn" onclick="selectIncidentType('Vehicular Accident', 'Vehicular Accident')">
                        <i class="fas fa-car-crash"></i> Vehicular Accident
                    </button>
                    <button class="chat-option-btn" onclick="selectIncidentType('Flood or Weather', 'Flood or Weather Incident')">
                        <i class="fas fa-cloud-showers-heavy"></i> Flood or Weather
                    </button>
                    <button class="chat-option-btn" onclick="selectIncidentType('Crime/Public Safety', 'Crime or Public Safety')">
                        <i class="fas fa-shield-alt"></i> Crime/Public Safety
                    </button>
                    <button class="chat-option-btn" onclick="selectIncidentType('Other Emergency', 'Other Incident')">
                        <i class="fas fa-exclamation-triangle"></i> Other Incident
                    </button>
                </div>
            `;
            appendBotMessage(optionsHtml);
        }

        window.selectIncidentType = function(label, value) {
            const options = document.getElementById('botIncidentTypeOptions');
            if (options) options.remove();

            selectedCategory = value;
            appendUserMessage(label);

            incidentReportStep = 2;
            setTimeout(() => {
                appendBotMessage("Got it. Where did the incident occur? Please enter the location or landmark below:");
                const locationBubbleHtml = `
                    <div class="chat-location-input-bubble" id="botLocationBubble">
                        <input type="text" id="botLocationInput" placeholder="Enter street, barangay, or landmark...">
                        <div class="chat-location-buttons">
                            <button class="chat-location-btn primary" onclick="confirmIncidentLocation()">
                                <i class="fas fa-check"></i> Confirm
                            </button>
                            <button class="chat-location-btn secondary" onclick="getBotCurrentLocation()">
                                <i class="fas fa-location-arrow"></i> Use Current
                            </button>
                        </div>
                    </div>
                `;
                appendBotMessage(locationBubbleHtml);
                setTimeout(() => {
                    const inp = document.getElementById('botLocationInput');
                    if (inp) {
                        if (locationData && locationData.address) {
                            inp.value = locationData.address;
                        }
                        inp.focus();
                        inp.onkeypress = e => { if (e.key === 'Enter') confirmIncidentLocation(); };
                    }
                }, 50);
            }, 500);
        };

        window.getBotCurrentLocation = async function() {
            const inp = document.getElementById('botLocationInput');
            if (inp) inp.value = "Fetching location...";
            try {
                const loc = await tryGetLocation();
                if (loc && loc.address) {
                    if (inp) inp.value = loc.address;
                } else {
                    if (inp) inp.value = "Location not found, please type manually.";
                }
            } catch (err) {
                if (inp) inp.value = "Failed to get location, please type manually.";
            }
        };

        window.confirmIncidentLocation = function() {
            const inp = document.getElementById('botLocationInput');
            const val = inp ? inp.value.trim() : '';
            if (!val) {
                Swal.fire({ icon: 'warning', title: 'Location Required', text: 'Please enter a location.' });
                return;
            }
            selectedLocation = val;

            const bubble = document.getElementById('botLocationBubble');
            if (bubble) bubble.remove();

            appendUserMessage(`Location: ${val}`);

            incidentReportStep = 3;
            setTimeout(() => {
                appendBotMessage("Understood. Finally, please describe the incident in detail in the chat input below. You can attach a photo/file or paste a link using the buttons next to the input, then click Send to submit.");
                const chatInput = document.getElementById('incidentChatInput');
                if (chatInput) {
                    chatInput.disabled = false;
                    chatInput.placeholder = "Describe the incident details...";
                    chatInput.focus();
                }
                document.getElementById('incidentSendBtn').disabled = false;
                document.getElementById('incidentLinkBtn').disabled = false;
                document.getElementById('incidentFileBtn').disabled = false;
            }, 500);
        };

        window.saveIncidentLink = function() {
            const input = document.getElementById('incidentLinkInput');
            const val = input ? input.value.trim() : '';
            if (val) {
                selectedLink = val;
                appendSystemMessage(`Added related link: ${val}`);
            }
            document.getElementById('incidentLinkDrawer').style.display = 'none';
            document.getElementById('incidentLinkBtn').classList.remove('active');
        };

        window.clearIncidentAttachment = function() {
            selectedAttachment = null;
            document.getElementById('incidentFileFile').value = '';
            const preview = document.getElementById('incidentAttachmentPreview');
            if (preview) preview.style.display = 'none';
        };

        window.onIncidentSendClick = async function() {
            const input = document.getElementById('incidentChatInput');
            const text = input ? input.value.trim() : '';

            if (incidentReportStep === 3) {
                if (!text && !selectedAttachment) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Details required',
                        text: 'Please describe the incident or attach a photo/file.'
                    });
                    return;
                }
                selectedDetails = text;
                input.value = '';
                input.style.height = '';

                appendUserMessage(text);
                
                document.getElementById('incidentChatInput').disabled = true;
                document.getElementById('incidentSendBtn').disabled = true;
                document.getElementById('incidentLinkBtn').disabled = true;
                document.getElementById('incidentFileBtn').disabled = true;
                
                appendBotMessage("Submitting your incident report. Please wait...");
                
                const success = await sendIncidentReportData();
                if (success) {
                    incidentReportStep = 4;
                    document.getElementById('incidentChatInput').disabled = false;
                    document.getElementById('incidentSendBtn').disabled = false;
                    document.getElementById('incidentLinkBtn').disabled = false;
                    document.getElementById('incidentFileBtn').disabled = false;
                    document.getElementById('incidentChatInput').placeholder = "Type message to responder...";
                    document.getElementById('incidentChatInput').focus();
                } else {
                    document.getElementById('incidentChatInput').disabled = false;
                    document.getElementById('incidentSendBtn').disabled = false;
                    document.getElementById('incidentLinkBtn').disabled = false;
                    document.getElementById('incidentFileBtn').disabled = false;
                }
            } else if (incidentReportStep === 4) {
                if (!text && !selectedAttachment) return;
                input.value = '';
                input.style.height = '';
                
                appendUserMessage(text);
                
                await sendLiveChatMessage(text, selectedAttachment);
                clearIncidentAttachment();
            }
        };

        function buildIncidentReportText(type, details, location, relatedLink) {
            const lines = [
                `Incident Type: ${type}`,
                location ? `Location: ${location}` : '',
                '',
                details,
                relatedLink ? `Related Link: ${relatedLink}` : ''
            ].filter(line => line !== '');
            return lines.join('\n');
        }

        async function sendIncidentReportData() {
            const reportText = buildIncidentReportText(selectedCategory, selectedDetails, selectedLocation, selectedLink);
            const formData = new FormData();
            formData.append('text', reportText);
            formData.append('userId', userProfile?.id || 'guest');
            formData.append('userName', userProfile?.name || 'Guest User');
            formData.append('userEmail', userProfile?.email || '');
            formData.append('userPhone', userProfile?.phone || '');
            formData.append('userLocation', selectedLocation);
            formData.append('userConcern', 'incident_report');
            formData.append('category', selectedCategory);
            formData.append('forceNewConversation', '1');
            formData.append('isGuest', userProfile?.id ? '0' : '1');
            if (selectedAttachment) {
                formData.append('attachment', selectedAttachment);
            }

            try {
                const response = await fetch('api/chat-send.php', {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin'
                });
                const data = await response.json().catch(() => ({}));
                if (!response.ok || !data.success) {
                    throw new Error(data.message || 'Failed to submit incident report.');
                }

                activeIncidentConversationId = data.conversationId;
                sessionStorage.setItem('active_incident_conversation_id', data.conversationId);
                
                clearIncidentAttachment();
                selectedLink = '';
                
                appendBotMessage("Incident Report Submitted successfully! We have opened a live connection to a dispatcher. You can chat here in real time.");
                appendSystemMessage("Connected to Dispatcher");
                
                startIncidentChatPolling(activeIncidentConversationId);
                return true;
            } catch (error) {
                appendBotMessage(`Error: ${error.message || 'Submission failed. Please try again.'}`);
                return false;
            }
        }

        async function sendLiveChatMessage(text, attachment) {
            if (!activeIncidentConversationId) return;
            const formData = new FormData();
            formData.append('text', text);
            formData.append('conversationId', activeIncidentConversationId);
            formData.append('userId', userProfile?.id || 'guest');
            formData.append('userName', userProfile?.name || 'Guest User');
            formData.append('userEmail', userProfile?.email || '');
            formData.append('userPhone', userProfile?.phone || '');
            formData.append('userConcern', 'incident_report');
            formData.append('category', selectedCategory);
            if (attachment) {
                formData.append('attachment', attachment);
            }

            try {
                const response = await fetch('api/chat-send.php', {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin'
                });
                const data = await response.json().catch(() => ({}));
                if (!response.ok || !data.success) {
                    throw new Error(data.message || 'Failed to send message.');
                }
                if (data.messageId) {
                    lastIncidentMessageId = Math.max(lastIncidentMessageId, data.messageId);
                }
            } catch (error) {
                appendSystemMessage(`Failed to send: ${error.message}`);
            }
        }

        function startIncidentChatPolling(conversationId) {
            if (incidentChatPollInterval) clearInterval(incidentChatPollInterval);
            lastIncidentMessageId = 0;
            
            const fetchMessages = async () => {
                if (activeIncidentConversationId !== conversationId) return;
                try {
                    const response = await fetch(`api/chat-get-messages.php?conversationId=${conversationId}&lastMessageId=${lastIncidentMessageId}`);
                    const data = await response.json();
                    if (data.success && Array.isArray(data.messages)) {
                        data.messages.forEach(msg => {
                            if (msg.id > lastIncidentMessageId) {
                                lastIncidentMessageId = Math.max(lastIncidentMessageId, msg.id);
                                if (msg.senderType === 'admin' || msg.senderType === 'sent') {
                                    appendBotMessage(linkify(escapeHtml(msg.text)));
                                }
                            }
                        });
                    }
                } catch (e) {
                    console.error('Error polling messages:', e);
                }
            };
            
            fetchMessages();
            incidentChatPollInterval = setInterval(fetchMessages, 3000);
        }

        async function loadActiveIncidentMessages(conversationId) {
            try {
                const response = await fetch(`api/chat-get-messages.php?conversationId=${conversationId}&lastMessageId=0`);
                const data = await response.json();
                if (data.success && Array.isArray(data.messages)) {
                    data.messages.forEach(msg => {
                        lastIncidentMessageId = Math.max(lastIncidentMessageId, msg.id);
                        if (msg.senderType === 'admin' || msg.senderType === 'sent') {
                            appendBotMessage(linkify(escapeHtml(msg.text)));
                        } else {
                            appendUserMessage(msg.text);
                        }
                    });
                }
            } catch (e) {
                console.error('Error loading previous messages:', e);
            }
            startIncidentChatPolling(conversationId);
        }

        function openIncidentReport() {
            const modal = document.getElementById('incidentReportModal');
            if (!modal) return;
            modal.style.display = 'block';
            modal.setAttribute('aria-hidden', 'false');
            
            tryGetLocation().then(loc => {
                if (loc) locationData = loc;
            });

            activeIncidentConversationId = sessionStorage.getItem('active_incident_conversation_id') || null;
            if (activeIncidentConversationId) {
                incidentReportStep = 4;
                const container = document.getElementById('incidentChatMessages');
                if (container) container.innerHTML = '';
                appendSystemMessage("Resumed Active Connection");
                const chatInput = document.getElementById('incidentChatInput');
                if (chatInput) {
                    chatInput.disabled = false;
                    chatInput.placeholder = "Type message to responder...";
                }
                document.getElementById('incidentSendBtn').disabled = false;
                document.getElementById('incidentLinkBtn').disabled = false;
                document.getElementById('incidentFileBtn').disabled = false;
                loadActiveIncidentMessages(activeIncidentConversationId);
            } else {
                startIncidentBot();
            }
        }

        function closeIncidentReport() {
            const modal = document.getElementById('incidentReportModal');
            if (!modal) return;
            modal.style.display = 'none';
            modal.setAttribute('aria-hidden', 'true');
            if (incidentChatPollInterval) {
                clearInterval(incidentChatPollInterval);
                incidentChatPollInterval = null;
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            const linkBtn = document.getElementById('incidentLinkBtn');
            if (linkBtn) {
                linkBtn.onclick = () => {
                    const drawer = document.getElementById('incidentLinkDrawer');
                    if (!drawer) return;
                    const isHidden = drawer.style.display === 'none';
                    drawer.style.display = isHidden ? 'flex' : 'none';
                    linkBtn.classList.toggle('active', isHidden);
                    if (isHidden) {
                        document.getElementById('incidentLinkInput').focus();
                    }
                };
            }

            const fileBtn = document.getElementById('incidentFileBtn');
            if (fileBtn) {
                fileBtn.onclick = () => {
                    document.getElementById('incidentFileFile').click();
                };
            }

            const fileInput = document.getElementById('incidentFileFile');
            if (fileInput) {
                fileInput.onchange = (e) => {
                    const file = e.target.files?.[0];
                    if (file) {
                        selectedAttachment = file;
                        const preview = document.getElementById('incidentAttachmentPreview');
                        const name = document.getElementById('incidentAttachmentName');
                        if (preview && name) {
                            name.textContent = file.name;
                            preview.style.display = 'flex';
                        }
                    }
                };
            }

            const chatInput = document.getElementById('incidentChatInput');
            if (chatInput) {
                chatInput.onkeypress = e => { 
                    if (e.key === 'Enter' && !e.shiftKey) {
                        e.preventDefault();
                        onIncidentSendClick(); 
                    }
                };
                
                // Auto-resize chat input as user types
                chatInput.addEventListener('input', function() {
                    this.style.height = 'auto';
                    this.style.height = this.scrollHeight + 'px';
                });
            }

            const modal = document.getElementById('incidentReportModal');
            if (modal) {
                modal.addEventListener('click', (event) => {
                    if (event.target && event.target.id === 'incidentReportModal') {
                        closeIncidentReport();
                    }
                });
            }
        });
    </script>
    
    <!-- Emergency Alert System -->
    <script src="../ADMIN/header/js/emergency-alert.js"></script>
</body>
</html>
