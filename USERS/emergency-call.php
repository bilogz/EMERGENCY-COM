<?php
// Include centralized session configuration - MUST be first
require_once __DIR__ . '/../session-config.php';
require_once __DIR__ . '/api/config.env.php';

$turnUrl = trim((string) getSecureConfig('WEBRTC_TURN_URL', ''));
$turnUsername = trim((string) getSecureConfig('WEBRTC_TURN_USERNAME', ''));
$turnCredential = trim((string) getSecureConfig('WEBRTC_TURN_CREDENTIAL', ''));

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
    <link rel="preconnect" href="https://cdn.socket.io" crossorigin>
    <link rel="preconnect" href="https://unpkg.com" crossorigin>
    <link rel="dns-prefetch" href="//nominatim.openstreetmap.org">
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

        .chat-other-report-label {
            color: var(--text-primary, #1f2937);
            font-size: 0.82rem;
            font-weight: 700;
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

        .chat-location-search-wrap {
            position: relative;
        }

        .chat-location-suggestions {
            position: absolute;
            top: calc(100% + 0.35rem);
            left: 0;
            right: 0;
            z-index: 1005;
            display: flex;
            flex-direction: column;
            max-height: 190px;
            overflow-y: auto;
            background: var(--bg-primary, #ffffff);
            border: 1px solid var(--border-secondary, #d1d5db);
            border-radius: 10px;
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.16);
        }

        .chat-location-suggestions[hidden] {
            display: none;
        }

        .chat-location-suggestion {
            border: 0;
            border-bottom: 1px solid var(--border-primary, #e5e7eb);
            background: transparent;
            color: var(--text-primary, #1f2937);
            cursor: pointer;
            font-size: 0.78rem;
            line-height: 1.35;
            padding: 0.65rem 0.75rem;
            text-align: left;
        }

        .chat-location-suggestion:last-child {
            border-bottom: 0;
        }

        .chat-location-suggestion:hover,
        .chat-location-suggestion:focus {
            background: var(--primary-light, rgba(142, 68, 173, 0.08));
            color: var(--primary-color, #8e44ad);
            outline: none;
        }

        .chat-location-map {
            width: 100%;
            height: 220px;
            min-height: 220px;
            border: 1px solid var(--border-secondary, #d1d5db);
            border-radius: 12px;
            overflow: hidden;
            background: var(--bg-secondary, #f8fafc);
        }

        .chat-location-selected {
            min-height: 34px;
            padding: 0.55rem 0.7rem;
            border-radius: 9px;
            background: var(--bg-secondary, #f8fafc);
            color: var(--text-secondary, #4b5563);
            border: 1px solid var(--border-primary, #e5e7eb);
            font-size: 0.78rem;
            line-height: 1.35;
        }

        .chat-location-selected.has-location {
            color: var(--text-primary, #1f2937);
            border-color: rgba(20, 184, 166, 0.4);
        }

        .chat-location-selected.is-outside {
            color: #b91c1c;
            border-color: rgba(239, 68, 68, 0.45);
            background: rgba(254, 226, 226, 0.85);
        }

        .chat-location-hint {
            color: var(--text-muted, #6b7280);
            font-size: 0.74rem;
            line-height: 1.3;
        }

        .leaflet-container {
            font-family: inherit;
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
                            <p>Start a voice call over WiFi or mobile data. Connect directly with the emergency response team.</p>
                            <button class="btn btn-primary" onclick="startInternetCall()">
                                <i class="fas fa-headset"></i> <span>Call for Emergency via Internet</span>
                            </button>
                        </div>
                        <div class="card">
                            <h3>Report Incident</h3>
                            <p>Submit incident details, photos, files, or related links so responders can review and act.</p>
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
    <div id="callOverlay" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.55); z-index:100000; overflow:auto; padding:18px;">
        <div style="position:relative; margin:auto; width:min(420px, 92vw); min-height:min(560px, 88vh); max-height:calc(100vh - 36px); background:#0f172a; border:1px solid rgba(255,255,255,0.12); border-radius:16px; padding:22px; color:#fff; box-shadow:0 20px 60px rgba(0,0,0,0.5); display:flex; flex-direction:column; overflow:hidden;">
            <div id="callActiveBanner" style="display:none; margin:-6px 0 12px; padding:8px 12px; border-radius:12px; background:rgba(220,38,38,0.18); border:1px solid rgba(220,38,38,0.45); color:#fecaca; font-weight:800; letter-spacing:0.6px; text-transform:uppercase; text-align:center;">CALL ON ACTIVE</div>
            <!-- Call Header -->
            <div style="display:flex; align-items:center; gap:12px; flex-shrink:0;">
                <div id="userLocalMicIndicator" title="Your microphone activity" style="width:44px; height:44px; border-radius:12px; background:rgba(76,138,137,0.2); display:flex; align-items:center; justify-content:center; transition:box-shadow .18s ease, background .18s ease;">
                    <i class="fas fa-headset" style="color:#4c8a89;"></i>
                </div>
                <div style="flex:1;">
                    <div style="font-weight:700; font-size:16px;">Emergency Call</div>
                    <div id="callStatus" style="opacity:0.85; font-size:13px;">Connecting…</div>
                </div>
                <div id="callTimer" style="font-variant-numeric:tabular-nums; font-weight:700;">00:00</div>
            </div>

            <div id="guestCallerFields" style="display:none; margin-top:14px; padding:12px; border:1px solid rgba(255,255,255,0.10); border-radius:12px; background:rgba(255,255,255,0.04);">
                <div style="font-size:12px; opacity:0.8; margin-bottom:8px;">Caller information</div>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:8px;">
                    <input id="guestCallerName" type="text" placeholder="Name" autocomplete="name" style="min-width:0; padding:10px 12px; border:1px solid rgba(255,255,255,0.18); border-radius:10px; background:rgba(255,255,255,0.08); color:#fff; outline:none;">
                    <input id="guestCallerPhone" type="tel" placeholder="Phone number" autocomplete="tel" style="min-width:0; padding:10px 12px; border:1px solid rgba(255,255,255,0.18); border-radius:10px; background:rgba(255,255,255,0.08); color:#fff; outline:none;">
                    <input id="guestCallerLocation" type="text" placeholder="Location or address" autocomplete="street-address" style="grid-column:1 / -1; min-width:0; padding:10px 12px; border:1px solid rgba(255,255,255,0.18); border-radius:10px; background:rgba(255,255,255,0.08); color:#fff; outline:none;">
                </div>
            </div>

            <div style="display:flex; gap:8px; margin-top:10px; font-size:12px; opacity:0.9;">
                <div id="userSpeakingLabel" style="display:flex; align-items:center; gap:6px; padding:6px 9px; border-radius:999px; background:rgba(255,255,255,0.06); transition:background .18s ease, color .18s ease;">
                    <i class="fas fa-microphone"></i><span>You</span>
                </div>
                <div id="adminSpeakingLabel" style="display:flex; align-items:center; gap:6px; padding:6px 9px; border-radius:999px; background:rgba(255,255,255,0.06); transition:background .18s ease, color .18s ease;">
                    <i class="fas fa-headset"></i><span>Emergency Admin</span>
                </div>
            </div>
            
            <!-- Messages Area -->
            <div id="callMessages" style="flex:1 1 160px; min-height:0; margin-top:16px; overflow-y:auto; border:1px solid rgba(255,255,255,0.1); border-radius:8px; padding:12px; background:rgba(0,0,0,0.2);">
                <div style="text-align:center; opacity:0.6; font-size:12px;">Messages will appear here</div>
            </div>
            
            <!-- Message Input -->
            <div id="callInputRow" style="margin-top:12px; display:flex; gap:10px; flex-shrink:0; align-items:center;"></div>
            
            <!-- Call Controls -->
            <div style="margin-top:14px; display:flex; gap:10px; justify-content:flex-end; flex-shrink:0; flex-wrap:wrap; width:100%;">
                <button id="cancelCallBtn" class="btn btn-secondary" style="min-height:44px; padding:10px 16px; flex:1 1 150px;"><i class="fas fa-ban"></i> Cancel Call</button>
                <button id="endCallBtn" class="btn btn-secondary" disabled style="opacity:0.6; pointer-events:none; min-height:44px; padding:10px 16px; flex:1 1 150px;"><i class="fas fa-phone-slash"></i> End Call</button>
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
                        <h3>Send Report</h3>
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
        const SOCKET_IO_PATH = '/socket.io';
        const SIGNALING_URL = (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1' || window.location.hostname.startsWith('192.168.'))
            ? `${window.location.protocol}//${window.location.hostname}:3000`
            : window.location.origin;
        const ROOT_API_BASE = '../api/';
        const transferApiUrl = () => `${ROOT_API_BASE}transfer-call.php`;
        const LEAFLET_CSS_URL = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
        const LEAFLET_JS_URL = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
        console.log('[call][user] signaling endpoint v3', `${SIGNALING_URL}${SOCKET_IO_PATH}`);
        let socket = null;
        let socketBound = false;
        let leafletLoadPromise = null;
        let callMessageLoggingDisabled = false;
        const CALL_LOBBY_ROOM = "emergency-lobby";
        const WEBRTC_ICE_SERVERS = [
            { urls: 'stun:stun.l.google.com:19302' },
            { urls: 'stun:global.stun.twilio.com:3478' }
            <?php if ($turnUrl !== ''): ?>,
            {
                urls: <?php echo json_encode($turnUrl, JSON_UNESCAPED_SLASHES); ?>,
                username: <?php echo json_encode($turnUsername); ?>,
                credential: <?php echo json_encode($turnCredential); ?>
            }
            <?php endif; ?>
        ];
        let activeCallRoom = null;
        let socketRetryCount = 0;
        const MAX_SOCKET_RETRIES = 5;

        function getCallRoom(id = callId) {
            return id ? `emergency-call-${id}` : CALL_LOBBY_ROOM;
        }

        function waitForSocketConnected(s, timeoutMs = 25000) {
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
                const onErr = () => {
                    if (callId) setStatus('Connecting to call service... retrying');
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
                transports: ['websocket', 'polling'],
                reconnection: true,
                reconnectionAttempts: MAX_SOCKET_RETRIES,
                reconnectionDelay: 500,
                reconnectionDelayMax: 2000,
                timeout: 15000

            };

            socket = window.io(SIGNALING_URL, socketOptions);
            bindSocketHandlers();
            return socket;
        }

        function loadLeafletAssets() {
            if (window.L) return Promise.resolve(true);
            if (leafletLoadPromise) return leafletLoadPromise;

            if (!document.getElementById('leaflet-css')) {
                const link = document.createElement('link');
                link.id = 'leaflet-css';
                link.rel = 'stylesheet';
                link.href = LEAFLET_CSS_URL;
                document.head.appendChild(link);
            }

            leafletLoadPromise = new Promise(resolve => {
                const existing = document.getElementById('leaflet-js');
                if (existing) {
                    existing.addEventListener('load', () => resolve(true), { once: true });
                    existing.addEventListener('error', () => resolve(false), { once: true });
                    return;
                }
                const script = document.createElement('script');
                script.id = 'leaflet-js';
                script.src = LEAFLET_JS_URL;
                script.async = true;
                script.onload = () => resolve(true);
                script.onerror = () => resolve(false);
                document.head.appendChild(script);
            });

            return leafletLoadPromise;
        }

        function bindSocketHandlers() {
            if (!socket || socketBound) return;
            socketBound = true;

            socket.on('connect', () => {
                console.log('[call][user] socket connected', socket.id);
                if (activeCallRoom) socket.emit('join', activeCallRoom);
                if (callId && activeCallRoom) {
                    socket.emit('resume-user-call', {
                        callId,
                        room: activeCallRoom,
                        accepted: !!callConnectedAt
                    });
                }
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

            socket.on("answer", async payload => {
                if (!signalingPayloadMatchesActiveCall(payload)) return;
                const isTransferAnswer = payload?.transferred === true || payload?.target === 'ers';
                if (
                    isTransferAnswer
                    && payload?.negotiationId
                    && transferNegotiationId
                    && String(payload.negotiationId) !== transferNegotiationId
                ) return;
                const description = normalizeRemoteAnswer(payload);
                if (!description) return;
                const candidates = [transferPc, pc].filter(Boolean);
                const targetPc = candidates.find(candidate => candidate.signalingState === 'have-local-offer') || candidates[0] || null;
                if (!targetPc) return;
                if (description.type === 'answer' && targetPc.signalingState !== 'have-local-offer') {
                    console.warn('[call][user] Ignoring stale answer because peer is already', targetPc.signalingState);
                    return;
                }
                try {
                    await targetPc.setRemoteDescription(description);
                    await flushPendingRemoteIceCandidates(targetPc);
                } catch (error) {
                    if (targetPc.signalingState === 'stable') {
                        console.warn('[call][user] Ignoring duplicate answer after connection became stable.', error);
                        return;
                    }
                    console.error(error);
                }
            });

            socket.on("candidate", payload => {
                if (!signalingPayloadMatchesActiveCall(payload)) return;
                const cand = payload && payload.candidate ? payload.candidate : payload;
                const isTransferCandidate = payload?.transferred === true || payload?.target === 'ers';
                if (
                    isTransferCandidate
                    && payload?.negotiationId
                    && transferNegotiationId
                    && String(payload.negotiationId) !== transferNegotiationId
                ) return;
                if (transferInProgress && transferPc && !isTransferCandidate) return;
                const targetPc = isTransferCandidate
                    ? (transferPc || pc)
                    : ((transferInProgress && transferPc) ? transferPc : pc);
                addRemoteIceCandidate(targetPc, cand);
            });

            const handlePeerHangup = payload => {
                if (!signalingPayloadMatchesActiveCall(payload)) return;
                if (callId) endCall(false);
            };
            socket.on('hangup', handlePeerHangup);
            socket.on('call-ended', handlePeerHangup);
            socket.on('call_ended', handlePeerHangup);

            socket.on('call-transfer', async payload => {
                if (!signalingPayloadMatchesActiveCall(payload)) return;
                if (!callId) return;
                if (payload && payload.room) activeCallRoom = payload.room;
                transferInProgress = true;
                setStatus('Transfer sent. Stay connected until the response team answers...');
                setEndEnabled(true);
                setCancelVisible(false);
            });

            socket.on('request-transfer-offer', async payload => {
                if (!signalingPayloadMatchesActiveCall(payload)) return;
                if (payload && payload.room) activeCallRoom = payload.room;
                await prepareTransferredCallOffer('response-team-request');
            });

            ['dispatcher-ready', 'call-accepted', 'accepted'].forEach(eventName => {
                socket.on(eventName, payload => {
                    if (!signalingPayloadMatchesActiveCall(payload)) return;
                    if (payload && payload.room) activeCallRoom = payload.room;
                });
            });

            socket.on('call-message', payload => {
                const incomingCallId = payload && payload.callId ? payload.callId : null;
                if (incomingCallId && incomingCallId !== callId) return;
                if (payload.text && payload.sender !== 'user') {
                    addMessage(payload.text, payload.sender || 'admin', payload.timestamp);
                }
            });

            socket.on('request-offer', payload => {
                if (!signalingPayloadMatchesActiveCall(payload)) return;
                if (payload?.room) activeCallRoom = payload.room;
                rebuildOfferForAdminResume().catch(error => {
                    console.error('[call][user] failed to rebuild offer for response team', error);
                    setStatus('Unable to reconnect to response team. You may cancel and call again.');
                    setEndEnabled(true);
                    setCancelVisible(true);
                });
            });

            socket.on('connect_error', () => {
                if (callId) {
                    setStatus('Connecting failed. Signaling server offline.');
                    setEndEnabled(true);
                }
            });
        }

        async function readApiResponse(response) {
            const raw = await response.text();
            let data = {};
            if (raw) {
                try {
                    data = JSON.parse(raw);
                } catch (e) {
                    data = { success: false, message: raw };
                }
            }
            if (!response.ok) {
                data.success = false;
                data.message = data.message || `HTTP ${response.status}`;
                data.integration = data.integration || {};
                data.integration.httpStatus = data.integration.httpStatus || response.status;
                data.integration.response = data.integration.response || raw;
            }
            return data;
        }

        function formatTransferError(result, fallback = 'Response team notification failed.') {
            if (!result) return fallback;
            const parts = [result.message || fallback];
            const integration = result.integration || {};
            if (integration.httpStatus) parts.push(`HTTP ${integration.httpStatus}`);
            if (integration.response && typeof integration.response === 'string') {
                parts.push(integration.response.slice(0, 160));
            }
            return parts.filter(Boolean).join(' - ');
        }

        function signalingPayloadMatchesActiveCall(payload) {
            if (!callId) return false;
            const incomingCallId = payload && (payload.callId || payload.call_id) ? String(payload.callId || payload.call_id) : '';
            const incomingRoom = payload && payload.room ? String(payload.room) : '';
            const currentCallId = String(callId);
            const currentRoom = activeCallRoom || getCallRoom(callId);

            if (incomingCallId && incomingCallId === currentCallId) return true;
            if (incomingRoom && currentRoom && incomingRoom === String(currentRoom)) return true;
            return !incomingCallId && !incomingRoom;
        }

        function normalizeRemoteAnswer(payload) {
            if (!payload) return null;
            if (payload.sdp && typeof payload.sdp === 'object') return payload.sdp;
            if (typeof payload.sdp === 'string') {
                return { type: payload.type || 'answer', sdp: payload.sdp };
            }
            if (typeof payload === 'object' && typeof payload.type === 'string' && typeof payload.sdp === 'string') {
                return payload;
            }
            return null;
        }

        async function addRemoteIceCandidate(targetPc, candidate) {
            if (!targetPc || !candidate) return;
            if (!targetPc.remoteDescription) {
                pendingRemoteIceCandidates.push({ pc: targetPc, candidate });
                return;
            }
            try {
                await targetPc.addIceCandidate(candidate);
            } catch (error) {
                console.warn('[call][user] Unable to add ICE candidate:', error);
            }
        }

        async function flushPendingRemoteIceCandidates(targetPc) {
            if (!targetPc || !targetPc.remoteDescription || !pendingRemoteIceCandidates.length) return;
            const remaining = [];
            const ready = [];
            pendingRemoteIceCandidates.forEach(item => {
                if (item.pc === targetPc) ready.push(item.candidate);
                else remaining.push(item);
            });
            pendingRemoteIceCandidates = remaining;
            for (const candidate of ready) {
                await addRemoteIceCandidate(targetPc, candidate);
            }
        }

        let pc = null;
        let transferPc = null;
        let localStream = null;
        let callId = null;
        let transferInProgress = false;
        let callStartedAt = null;
        let callConnectedAt = null;
        let autoTransferCompletedCallId = null;
        let autoTransferInFlight = false;
        let liveTransferSocketNotifiedCallId = null;
        let timerInterval = null;
        let peerDisconnectTimer = null;
        let locationData = null;
        let userProfile = null;
        let messages = [];
        let audioActivityMonitors = [];
        let pendingRemoteIceCandidates = [];
        let transferNegotiationId = '';
        let endingCall = false;

        function attachRemoteCallAudio(stream) {
            const remote = document.getElementById('remote');
            if (!remote || !stream) return;
            remote.srcObject = stream;
            remote.muted = false;
            remote.volume = 1;
            const playback = remote.play();
            if (playback && typeof playback.catch === 'function') {
                playback.catch(() => {
                    setStatus('Audio connected. Tap the call screen once to enable sound.');
                });
            }
        }

        function resumeRemoteCallAudio() {
            const remote = document.getElementById('remote');
            if (!remote || !remote.srcObject) return;
            remote.muted = false;
            remote.volume = 1;
            remote.play().catch(() => {});
        }

        document.addEventListener('pointerdown', resumeRemoteCallAudio, { passive: true });

        function setSpeakingIndicator(labelId, indicatorId, active) {
            const label = document.getElementById(labelId);
            if (label) {
                label.style.background = active ? 'rgba(76,138,137,0.32)' : 'rgba(255,255,255,0.06)';
                label.style.color = active ? '#dffdfc' : '#fff';
            }
            const indicator = indicatorId ? document.getElementById(indicatorId) : null;
            if (indicator) {
                indicator.style.background = active ? 'rgba(76,138,137,0.42)' : 'rgba(76,138,137,0.2)';
                indicator.style.boxShadow = active ? '0 0 0 6px rgba(76,138,137,0.22)' : 'none';
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

        function getGuestCallerInfo() {
            const name = document.getElementById('guestCallerName')?.value.trim()
                || localStorage.getItem('guest_name')
                || sessionStorage.getItem('user_name')
                || '';
            const phone = document.getElementById('guestCallerPhone')?.value.trim()
                || localStorage.getItem('guest_contact')
                || sessionStorage.getItem('user_phone')
                || '';
            const address = document.getElementById('guestCallerLocation')?.value.trim()
                || localStorage.getItem('guest_location')
                || sessionStorage.getItem('user_location')
                || '';
            return { name, phone, address, isGuest: true };
        }

        function updateGuestFieldsVisibility() {
            const el = document.getElementById('guestCallerFields');
            if (!el) return;
            el.style.display = userProfile && userProfile.id ? 'none' : 'block';
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
                    room: activeCallRoom || getCallRoom(),
                    sender: 'user',
                    senderName: userProfile?.name || 'User',
                    timestamp: Date.now()
                }, activeCallRoom || getCallRoom());
            }
            
            // Live-call text stays in the caller's private Socket.IO room.
            // It must not create an Emergency-Com Two-Way conversation.
        }

        async function loadUserProfile() {
            try {
                const response = await fetch('api/get-user-profile.php');
                const data = await response.json();
                if (data.success) {
                    const user = data.user || {};
                    userProfile = {
                        ...user,
                        id: user.id,
                        name: user.name || user.username,
                        username: user.username || user.name,
                        email: user.email,
                        phone: user.phone,
                        is_registered: true,
                        isGuest: false
                    };
                }
            } catch (e) {
                console.error('Failed to load user profile:', e);
            } finally {
                updateGuestFieldsVisibility();
            }
            return userProfile;
        }

        function formatTime(totalSeconds) {
            const m = String(Math.floor(totalSeconds / 60)).padStart(2, '0');
            const s = String(totalSeconds % 60).padStart(2, '0');
            return `${m}:${s}`;
        }

        function setOverlayVisible(visible) {
            document.getElementById('callOverlay').style.display = visible ? 'flex' : 'none';
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
            const s = ensureSocket();
            if (s && callId) {
                s.emit('hangup', { callId, room: activeCallRoom }, activeCallRoom || getCallRoom());
            }
            logCall('cancelled');
            setStatus('Call cancelled');
            setTimeout(() => {
                setOverlayVisible(false);
                cleanupCall();
            }, 250);
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
                    room: activeCallRoom || getCallRoom(),
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
            endingCall = false;
            transferInProgress = false;
            pendingRemoteIceCandidates = [];
            transferNegotiationId = '';
            if (peerDisconnectTimer) clearTimeout(peerDisconnectTimer);
            peerDisconnectTimer = null;
            stopTimer();
            stopAudioActivityMonitors();
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
            if (transferPc) {
                try { transferPc.close(); } catch (e) {}
                transferPc = null;
            }
            autoTransferInFlight = false;
            liveTransferSocketNotifiedCallId = null;
            callMessageLoggingDisabled = false;
            if (autoTransferCompletedCallId === callId) {
                autoTransferCompletedCallId = null;
            }
            callConnectedAt = null;
            callStartedAt = null;
            callId = null;
            activeCallRoom = null;
            const remote = document.getElementById('remote');
            if (remote) remote.srcObject = null;
            locationData = null;
            setTimer(0);
            setStartButtonsDisabled(false);
        }

        async function endCall(notifyPeer = true) {
            if (endingCall) return;
            endingCall = true;
            const durationSec = callConnectedAt ? Math.floor((Date.now() - callConnectedAt) / 1000) : null;
            if (notifyPeer && callId) {
                const s = ensureSocket();
                if (s) {
                    s.emit('hangup', { callId, room: activeCallRoom }, activeCallRoom || getCallRoom());
                }
            }
            logCall('ended', { durationSec });
            setStatus('Call ended');
            setTimeout(() => {
                setOverlayVisible(false);
                cleanupCall();
            }, 250);
        }

        async function prepareTransferredCallOffer(reason = 'transfer') {
            if (!callId) return;

            if (
                transferPc
                && !['failed', 'closed', 'disconnected'].includes(transferPc.connectionState)
            ) {
                // ERS may issue an immediate request plus a timeout fallback.
                // Do not destroy an active negotiation and replace it with a
                // second peer carrying different SDP and ICE candidates.
                return;
            }

            transferInProgress = true;
            setStatus('Transfer sent. Stay connected until the response team answers...');
            setEndEnabled(true);
            setCancelVisible(false);

            try {
                if (!localStream) {
                    localStream = await navigator.mediaDevices.getUserMedia({ audio: true });
                }

                if (transferPc) {
                    try { transferPc.close(); } catch (e) {}
                    transferPc = null;
                }

                const previousPc = pc;
                transferPc = new RTCPeerConnection({
                    iceServers: WEBRTC_ICE_SERVERS
                });
                transferNegotiationId = `ers_${callId}_${Date.now()}_${Math.random().toString(16).slice(2)}`;

                transferPc.ontrack = e => {
                    if (e.track && e.track.kind === 'audio') e.track.enabled = true;
                    const remoteStream = e.streams?.[0]
                        || (e.track ? new MediaStream([e.track]) : null);
                    if (!remoteStream) return;
                    attachRemoteCallAudio(remoteStream);
                    monitorAudioActivity(remoteStream, 'adminSpeakingLabel');
                };

                transferPc.onicecandidate = e => {
                    if (!e.candidate) return;
                    const s = ensureSocket();
                    if (s) {
                        s.emit('candidate', {
                            candidate: typeof e.candidate.toJSON === 'function' ? e.candidate.toJSON() : e.candidate,
                            callId,
                            room: activeCallRoom,
                            transferred: true,
                            target: 'ers',
                            negotiationId: transferNegotiationId
                        }, activeCallRoom || getCallRoom());
                    }
                };

                transferPc.onconnectionstatechange = () => {
                    if (!transferPc) return;
                    const state = transferPc.connectionState;
                    if (state === 'connected') {
                        if (previousPc && previousPc !== transferPc) {
                            try { previousPc.onconnectionstatechange = null; previousPc.close(); } catch (e) {}
                        }
                        pc = transferPc;
                        transferPc = null;
                        transferInProgress = false;
                        callConnectedAt = Date.now();
                        setStatus('Connected to Emergency Communication admin');
                        setEndEnabled(true);
                        setCallActiveBannerVisible(true);
                        startTimer();
                        logCall('transfer_connected_response_team');
                        return;
                    }
                    if (['failed', 'closed'].includes(state)) {
                        setStatus('Response team connection failed. Please stay connected while we retry.');
                        setEndEnabled(true);
                    }
                };

                localStream.getAudioTracks().forEach(track => { track.enabled = true; });
                localStream.getTracks().forEach(track => transferPc.addTrack(track, localStream));

                const s = ensureSocket();
                if (s) s.emit('join', activeCallRoom || getCallRoom());

                const offer = await transferPc.createOffer();
                await transferPc.setLocalDescription(offer);

                const guestCaller = getGuestCallerInfo();
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
                } : guestCaller;

                if (s) {
                    const transferRoom = activeCallRoom || getCallRoom();
                    s.emit("offer", {
                        sdp: offer,
                        callId,
                        room: transferRoom,
                        userId: userProfile?.id || null,
                        userName: userProfile?.name || guestCaller.name || null,
                        caller,
                        location: locationData || null,
                        transferred: true,
                        target: 'ers',
                        negotiationId: transferNegotiationId,
                        transferReason: reason
                    }, transferRoom);
                }

                logCall('transfer_waiting_for_response_team');
            } catch (e) {
                console.error('[call][user] failed to prepare transferred call', e);
                setStatus('Transfer failed. Please stay on this page or call again.');
                setEndEnabled(true);
                transferInProgress = false;
            }
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

        function currentCallCallerPayload() {
            if (userProfile) {
                return {
                    id: userProfile.id ?? null,
                    user_id: userProfile.id ?? null,
                    name: userProfile.name ?? null,
                    email: userProfile.email ?? null,
                    phone: userProfile.phone ?? null,
                    nationality: userProfile.nationality ?? null,
                    district: userProfile.district ?? null,
                    barangay: userProfile.barangay ?? null,
                    house_number: userProfile.house_number ?? null,
                    street: userProfile.street ?? null,
                    address: userProfile.address ?? null,
                    is_registered: true,
                    isGuest: false
                };
            }
            return getGuestCallerInfo();
        }

        function currentCallLocationPayload() {
            const payload = {
                ...(locationData || {})
            };
            const hasCoordinates = payload.lat !== undefined && payload.lat !== null
                && payload.lng !== undefined && payload.lng !== null;
            if (!payload.address && !hasCoordinates) {
                payload.address = 'Incident location pending from caller';
            }
            return payload;
        }

        function currentLiveCallPriority() {
            return {
                score: 90,
                priority: 'critical',
                level: 'critical',
                label: 'CRITICAL',
                color: 'red',
                breakdown: {
                    source: 'live_emergency_call',
                    reason: 'Live emergency call requires immediate Emergency Communication intake.'
                }
            };
        }

        async function persistOpenEmergencyCall(offerPayload) {
            if (!offerPayload || !offerPayload.callId) return false;
            try {
                const response = await fetch('../ADMIN/api/call-session.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    credentials: 'same-origin',
                    body: JSON.stringify({
                        action: 'upsert_open',
                        callId: offerPayload.callId,
                        room: offerPayload.room || getCallRoom(offerPayload.callId),
                        caller: offerPayload.caller || currentCallCallerPayload(),
                        location: offerPayload.location || currentCallLocationPayload(),
                        conversationId: offerPayload.conversationId || offerPayload.conversation_id || null,
                        offerPayload
                    })
                });
                const data = await response.json().catch(() => null);
                if (!response.ok || !data || data.success === false) {
                    throw new Error(data?.error || data?.message || 'Unable to save open emergency call.');
                }
                return true;
            } catch (error) {
                console.error('[call][user] failed to persist open call', error);
                return false;
            }
        }
        function emergencyComCallReference(callIdValue = callId) {
            const normalizedCallId = String(callIdValue || '').trim();
            return normalizedCallId ? `call-${normalizedCallId}` : '';
        }

        async function notifyErsSocketTransfer(transferPayload = {}, result = {}) {
            const s = ensureSocket();
            if (!s || !callId) throw new Error('Emergency-Com call service is unavailable.');
            const transferId = transferPayload.transferId || transferPayload.transfer_id || transferPayload.callId || callId;
            const emergencyComConversationId = String(
                transferPayload.emergencyComConversationId
                || transferPayload.emergency_com_conversation_id
                || transferPayload.conversationId
                || transferPayload.conversation_id
                || emergencyComCallReference(callId)
            ).trim();
            const notice = {
                ...(transferPayload || {}),
                event: 'emergency_call_transfer',
                transfer_type: 'live_call',
                transferType: 'live_call',
                callId,
                call_id: callId,
                call_id_external: callId,
                transferId,
                transfer_id: transferId,
                conversationId: emergencyComConversationId,
                conversation_id: emergencyComConversationId,
                emergencyComConversationId,
                emergency_com_conversation_id: emergencyComConversationId,
                room: activeCallRoom || getCallRoom(callId),
                socketUrl: SIGNALING_URL,
                socketPath: SOCKET_IO_PATH,
                caller: currentCallCallerPayload(),
                location: currentCallLocationPayload(),
                locationData: currentCallLocationPayload(),
                integration: result?.integration || null,
                route: 'emergency-com-call-relay',
                transferredAt: new Date().toISOString()
            };
            await waitForSocketConnected(s);
            return new Promise((resolve, reject) => {
                s.timeout(8000).emit('route-call-to-ers', notice, (error, response) => {
                    if (error || !response?.ok) {
                        reject(error || new Error(response?.reason || 'Emergency-Com could not route the call to ERS.'));
                        return;
                    }
                    resolve(response);
                });
            });
        }

        async function autoTransferCurrentCallToErs(attempt = 1) {
            if (!callId || autoTransferCompletedCallId === callId || autoTransferInFlight) return;
            const activeCallId = callId;
            autoTransferInFlight = true;

            try {
                const priority = currentLiveCallPriority();
                const caller = currentCallCallerPayload();
                const location = currentCallLocationPayload();
                setStatus(attempt > 1 ? `Retrying response team transfer (${attempt}/3)...` : 'Forwarding call to response team...');

                const transferPayload = {
                    action: 'transfer',
                    event: 'emergency_call_transfer',
                    transfer_type: 'live_call',
                    transferType: 'live_call',
                    callId: activeCallId,
                    call_id: activeCallId,
                    call_id_external: activeCallId,
                    transferId: activeCallId,
                    transfer_id: activeCallId,
                    conversationId: emergencyComCallReference(activeCallId),
                    conversation_id: emergencyComCallReference(activeCallId),
                    emergencyComConversationId: emergencyComCallReference(activeCallId),
                    emergency_com_conversation_id: emergencyComCallReference(activeCallId),
                    room: activeCallRoom || getCallRoom(activeCallId),
                    socketUrl: SIGNALING_URL,
                    socketPath: SOCKET_IO_PATH,
                    emergencyType: 'emergency_call',
                    type: 'emergency_call',
                    priority: priority.priority,
                    incidentPriority: priority,
                    description: 'Live emergency call waiting for ERS response team answer.',
                    latestMessage: '[CALL_STARTED] Emergency live call forwarded to ERS',
                    caller,
                    location,
                    locationData: location
                };

                const response = await fetch(transferApiUrl(), {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(transferPayload)
                });

                const data = await readApiResponse(response);
                if (!data.success) {
                    throw new Error(formatTransferError(data));
                }

                await notifyErsSocketTransfer({
                    ...transferPayload,
                    ...(data.data || {}),
                    callId: activeCallId,
                    call_id: activeCallId,
                    call_id_external: activeCallId,
                    transferId: transferPayload.transferId,
                    transfer_id: transferPayload.transfer_id,
                    room: activeCallRoom || getCallRoom(activeCallId),
                    socketUrl: SIGNALING_URL,
                    socketPath: SOCKET_IO_PATH
                }, data);
                autoTransferCompletedCallId = activeCallId;
                liveTransferSocketNotifiedCallId = activeCallId;
                setStatus('Emergency-Com forwarded this call to the response team.');
                await logCall('auto_transferred_to_response_team', {
                    room: activeCallRoom || getCallRoom(activeCallId),
                    socketUrl: SIGNALING_URL,
                    socketPath: SOCKET_IO_PATH
                });
            } catch (error) {
                console.error('[call][user] automatic ERS transfer failed', error);
                if (callId === activeCallId && attempt < 3) {
                    setStatus('Response team notification failed. Retrying...');
                    setTimeout(() => autoTransferCurrentCallToErs(attempt + 1), 3000);
                } else if (callId === activeCallId) {
                    setStatus('Response team notification failed. Keep this call open and try again.');
                    setEndEnabled(true);
                }
            } finally {
                if (callId === activeCallId) {
                    autoTransferInFlight = false;
                }
            }
        }

        async function rebuildOfferForAdminResume() {
            if (!callId || !activeCallRoom) return;
            if (peerDisconnectTimer) clearTimeout(peerDisconnectTimer);
            peerDisconnectTimer = null;
            setStatus('Response team requested a fresh connection. Restoring your call...');

            const previousPeer = pc;
            pc = null;
            if (previousPeer) {
                try { previousPeer.close(); } catch (e) {}
            }
            initPeer();

            const hasLiveAudio = localStream?.getAudioTracks?.().some(track => track.readyState === 'live');
            if (!hasLiveAudio) {
                localStream = await navigator.mediaDevices.getUserMedia({ audio: true });
                monitorAudioActivity(localStream, 'userSpeakingLabel', 'userLocalMicIndicator');
            }
            localStream.getTracks().forEach(track => pc.addTrack(track, localStream));

            const offer = await pc.createOffer({ iceRestart: true });
            await pc.setLocalDescription(offer);
            const s = ensureSocket();
            await waitForSocketConnected(s);
            const caller = currentCallCallerPayload();
            s.emit('offer', {
                sdp: offer,
                callId,
                room: activeCallRoom,
                userId: userProfile?.id || null,
                userName: caller?.name || null,
                caller,
                location: locationData || null,
                resumed: true
            }, activeCallRoom);
        }

        function initPeer() {
            if (pc) {
                try {
                    pc.ontrack = null;
                    pc.onicecandidate = null;
                    pc.onconnectionstatechange = null;
                    pc.close();
                } catch (e) {}
                pc = null;
            }

            pc = new RTCPeerConnection({
                iceServers: WEBRTC_ICE_SERVERS
            });
            pc.ontrack = e => {
                if (e.track && e.track.kind === 'audio') e.track.enabled = true;
                const remoteStream = e.streams?.[0]
                    || (e.track ? new MediaStream([e.track]) : null);
                if (!remoteStream) return;
                attachRemoteCallAudio(remoteStream);
                monitorAudioActivity(remoteStream, 'adminSpeakingLabel');
            };
            pc.onicecandidate = e => {
                if (!e.candidate) return;
                const s = ensureSocket();
                if (s) {
                    s.emit('candidate', {
                        candidate: typeof e.candidate.toJSON === 'function' ? e.candidate.toJSON() : e.candidate,
                        callId,
                        room: activeCallRoom
                    }, activeCallRoom || getCallRoom());
                }
            };
            pc.onconnectionstatechange = () => {
                if (!pc) return;
                if (pc.connectionState === 'connected') {
                    if (peerDisconnectTimer) clearTimeout(peerDisconnectTimer);
                    peerDisconnectTimer = null;
                    if (!callConnectedAt) callConnectedAt = Date.now();
                    setStatus('Connected to Emergency Communication admin');
                    setEndEnabled(true);
                    setCancelVisible(false);
                    setCallActiveBannerVisible(true);
                    startTimer();
                    logCall('connected');
                }
                if (['disconnected', 'failed', 'closed'].includes(pc.connectionState)) {
                    if (transferInProgress) return;
                    if (!callId || peerDisconnectTimer) return;
                    setStatus('Emergency Communication admin connection interrupted. Reconnecting...');
                    setCancelVisible(true);
                    peerDisconnectTimer = setTimeout(() => {
                        peerDisconnectTimer = null;
                        if (callId && pc && pc.connectionState !== 'connected') endCall();
                    }, 30000);
                }
            };
        }

        document.getElementById("call").onclick = async () => {
            if (callId) return;

            endingCall = false;
            transferInProgress = false;
            autoTransferInFlight = false;
            liveTransferSocketNotifiedCallId = null;
            pendingRemoteIceCandidates = [];

            setOverlayVisible(true);
            setStatus('Initializing call…');
            setTimer(0);
            setEndEnabled(false);
            setCancelVisible(true);
            setCallActiveBannerVisible(false);

            try {
                callId = (window.crypto && crypto.randomUUID) ? crypto.randomUUID() : `call_${Date.now()}_${Math.random().toString(16).slice(2)}`;
                activeCallRoom = getCallRoom(callId);
                callStartedAt = Date.now();
                setStartButtonsDisabled(true);

                // 1. Request microphone access with clear status feedback
                setStatus('Requesting microphone access…');
                localStream = await navigator.mediaDevices.getUserMedia({ audio: true });
                localStream.getAudioTracks().forEach(track => { track.enabled = true; });

                const activeCallId = callId;
                tryGetLocation().then((location) => {
                    if (callId !== activeCallId) return;
                    locationData = location;
                    const guestInfo = getGuestCallerInfo();
                    if (!userProfile && guestInfo.address && locationData) {
                        locationData.address = guestInfo.address;
                    }
                    const refreshedPayload = {
                        callId,
                        room: activeCallRoom,
                        conversationId: emergencyComCallReference(callId),
                        conversation_id: emergencyComCallReference(callId),
                        emergencyComConversationId: emergencyComCallReference(callId),
                        emergency_com_conversation_id: emergencyComCallReference(callId),
                        userId: userProfile?.id || null,
                        userName: userProfile?.name || guestInfo.name || null,
                        caller: currentCallCallerPayload(),
                        location: locationData || null
                    };
                    persistOpenEmergencyCall(refreshedPayload).catch(() => {});
                }).catch(() => {});

                // Emergency-Com answers this call first. The private room is announced
                // to the admin call lobby and can be manually transferred later.
                monitorAudioActivity(localStream, 'userSpeakingLabel', 'userLocalMicIndicator');

                const guestCaller = getGuestCallerInfo();
                const caller = currentCallCallerPayload();
                if (!userProfile && guestCaller.address && locationData) {
                    locationData.address = guestCaller.address;
                }

                const baseOfferPayload = {
                    callId,
                    room: activeCallRoom,
                    conversationId: emergencyComCallReference(callId),
                    conversation_id: emergencyComCallReference(callId),
                    emergencyComConversationId: emergencyComCallReference(callId),
                    emergency_com_conversation_id: emergencyComCallReference(callId),
                    userId: userProfile?.id || null,
                    userName: userProfile?.name || guestCaller.name || null,
                    caller,
                    location: locationData || null
                };

                await persistOpenEmergencyCall(baseOfferPayload);

                let offer = null;
                let offerError = null;
                for (let attempt = 0; attempt < 2; attempt += 1) {
                    try {
                        initPeer();
                        localStream.getTracks().forEach(track => pc.addTrack(track, localStream));
                        offer = await pc.createOffer({ iceRestart: attempt > 0 });
                        await pc.setLocalDescription(offer);
                        offerError = null;
                        break;
                    } catch (e) {
                        offerError = e;
                        console.warn('[call][user] failed to create local offer; rebuilding peer', e);
                        if (pc) {
                            try { pc.close(); } catch (closeError) {}
                            pc = null;
                        }
                    }
                }

                if (!offer) throw offerError || new Error('Failed to create emergency call offer.');

                console.log('[call][user] emitting offer', { callId, room: activeCallRoom });
                const offerPayload = {
                    ...baseOfferPayload,
                    sdp: offer
                };

                await persistOpenEmergencyCall(offerPayload);
                logCall('started');

                // 5. Connect and emit via Socket.IO
                setStatus('Connecting to emergency dispatcher…');
                const s = ensureSocket();
                if (s) {
                    try {
                        await waitForSocketConnected(s, 10000);
                        s.emit("join", activeCallRoom);
                        s.emit("offer", offerPayload, CALL_LOBBY_ROOM);
                    } catch (sockErr) {
                        console.warn('[call][user] Socket.io connect delay/notice:', sockErr);
                        // If socket connection is still attempting, join and emit on connect
                        if (s) {
                            s.once('connect', () => {
                                if (callId === activeCallId) {
                                    s.emit("join", activeCallRoom);
                                    s.emit("offer", offerPayload, CALL_LOBBY_ROOM);
                                }
                            });
                        }
                    }
                }

                setStatus('Waiting for Emergency Communication admin to answer...');
            } catch (e) {
                console.error('[call][user] call failed', e);
                let userFriendlyError = 'Call failed';
                if (e.name === 'NotAllowedError' || e.name === 'PermissionDeniedError') {
                    userFriendlyError = 'Microphone access blocked. Please allow microphone permissions in your browser to call.';
                } else if (e.name === 'NotFoundError' || e.name === 'DevicesNotFoundError') {
                    userFriendlyError = 'No microphone device found on this system.';
                } else if (e.name === 'NotReadableError' || e.name === 'TrackStartError') {
                    userFriendlyError = 'Microphone is currently in use by another application.';
                } else if (e.message && e.message.includes('Socket connect timeout')) {
                    userFriendlyError = 'Signaling network unreachable. Please check connection or call Hotline 122.';
                } else if (e.message) {
                    userFriendlyError = `Call error: ${e.message}`;
                }

                setStatus(userFriendlyError);
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
        let selectedLocationData = null;
        let selectedDetails = '';
        let selectedLink = '';
        let selectedAttachment = null;
        let activeIncidentConversationId = sessionStorage.getItem('active_incident_conversation_id') || null;
        let incidentChatPollInterval = null;
        let incidentChatPollInFlight = false;
        let lastIncidentMessageId = 0;
        let incidentLocationMap = null;
        let incidentLocationMarker = null;
        let incidentLocationSearchTimer = null;
        let incidentLocationSearchAbort = null;
        let incidentLocationOutsideClickBound = false;
        const DEFAULT_INCIDENT_LOCATION = { lat: 14.6760, lng: 121.0437, label: 'Quezon City, Philippines' };
        const QUEZON_CITY_SERVICE_BOUNDS = {
            south: 14.575,
            west: 120.945,
            north: 14.795,
            east: 121.145
        };

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
            selectedLocationData = null;
            selectedDetails = '';
            selectedLink = '';
            selectedAttachment = null;
            if (incidentLocationMap) {
                incidentLocationMap.remove();
                incidentLocationMap = null;
            }
            incidentLocationMarker = null;
            if (incidentLocationSearchTimer) clearTimeout(incidentLocationSearchTimer);
            incidentLocationSearchTimer = null;
            if (incidentLocationSearchAbort) incidentLocationSearchAbort.abort();
            incidentLocationSearchAbort = null;
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

            appendBotMessage("Hello! I am your Emergency Report Assistant. What would you like to report?");

            const optionsHtml = `
                <div class="chat-options-bubble" id="botIncidentTypeOptions">
                    <button class="chat-option-btn" onclick="selectIncidentType('Medical Needs', 'Medical Needs')">
                        <i class="fas fa-hospital" aria-hidden="true"></i>
                        Medical Needs
                    </button>
                    <button class="chat-option-btn" onclick="selectIncidentType('Fire Hazards', 'Fire Hazards')">
                        <i class="fas fa-fire" aria-hidden="true"></i>
                        Fire Hazards
                    </button>
                    <button class="chat-option-btn" onclick="selectIncidentType('Chemical / Hazardous Materials', 'Chemical / Hazardous Materials')">
                        <i class="fas fa-biohazard" aria-hidden="true"></i>
                        Chemical / Hazardous Materials
                    </button>
                    <button class="chat-option-btn" onclick="selectIncidentType('Traffic &amp; Vehicular Incidents', 'Traffic &amp; Vehicular Incidents')">
                        <i class="fas fa-car-crash" aria-hidden="true"></i>
                        Traffic &amp; Vehicular Incidents
                    </button>
                    <button class="chat-option-btn" onclick="selectIncidentType('Floods &amp; Natural Hazards', 'Floods &amp; Natural Hazards')">
                        <i class="fas fa-water" aria-hidden="true"></i>
                        Floods &amp; Natural Hazards
                    </button>
                    <button class="chat-option-btn" onclick="selectIncidentType('Crime &amp; Public Safety', 'Crime &amp; Public Safety')">
                        <i class="fas fa-shield-alt" aria-hidden="true"></i>
                        Crime &amp; Public Safety
                    </button>
                    <button class="chat-option-btn" onclick="selectIncidentType('Utility &amp; Infrastructure', 'Utility &amp; Infrastructure')">
                        <i class="fas fa-bolt" aria-hidden="true"></i>
                        Utility &amp; Infrastructure
                    </button>
                    <button class="chat-option-btn" onclick="selectIncidentType('Animal Rescue / Animal Concern', 'Animal Rescue / Animal Concern')">
                        <i class="fas fa-paw" aria-hidden="true"></i>
                        Animal Rescue / Animal Concern
                    </button>
                    <button class="chat-option-btn" onclick="selectIncidentType('Other Report', 'Other Report')">
                        <i class="fas fa-bullhorn" aria-hidden="true"></i>
                        Other Report
                    </button>
                </div>
            `;
            appendBotMessage(optionsHtml);
        }

        window.selectIncidentType = function(label, value) {
            const options = document.getElementById('botIncidentTypeOptions');
            if (options) options.remove();

            if (value === 'Other Report') {
                appendUserMessage(label);
                appendBotMessage('Please identify the report.');
                appendBotMessage(`
                    <div class="chat-location-input-bubble" id="botOtherReportBubble">
                        <label class="chat-other-report-label" for="botOtherReportInput">Identify the report</label>
                        <input
                            type="text"
                            id="botOtherReportInput"
                            placeholder="Identify the report"
                            maxlength="120"
                            autocomplete="off"
                            onkeydown="if (event.key === 'Enter') confirmOtherReportType()"
                        >
                        <button class="chat-location-btn primary" onclick="confirmOtherReportType()">
                            Continue <i class="fas fa-chevron-right"></i>
                        </button>
                    </div>
                `);
                setTimeout(() => document.getElementById('botOtherReportInput')?.focus(), 100);
                return;
            }

            selectedCategory = value;
            appendUserMessage(label);

            incidentReportStep = 2;
            setTimeout(() => {
                appendBotMessage("Got it. Where did the incident occur? Please enter the location or landmark below:");
                const locationBubbleHtml = `
                    <div class="chat-location-input-bubble" id="botLocationBubble">
                        <div class="chat-location-search-wrap">
                            <input type="text" id="botLocationInput" placeholder="Enter street, barangay, or landmark..." autocomplete="off">
                            <div class="chat-location-suggestions" id="botLocationSuggestions" hidden></div>
                        </div>
                        <div class="chat-location-map" id="botLocationMap"></div>
                        <div class="chat-location-selected" id="botLocationSelected">
                            Search a place, tap a suggestion, or click the map to drop the pin.
                        </div>
                        <div class="chat-location-hint">
                            Only Quezon City locations are accepted. Drag the pin or tap inside the boundary to refine it.
                        </div>
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
                setTimeout(initIncidentLocationPicker, 50);
            }, 500);
        };

        window.confirmOtherReportType = function() {
            const input = document.getElementById('botOtherReportInput');
            const customType = input ? input.value.trim() : '';

            if (!customType) {
                if (input) {
                    input.setCustomValidity('Please identify the report.');
                    input.reportValidity();
                    input.addEventListener('input', () => input.setCustomValidity(''), { once: true });
                }
                return;
            }

            const bubble = document.getElementById('botOtherReportBubble');
            if (bubble) bubble.remove();
            window.selectIncidentType(customType, `Other Report: ${customType}`);
        };

        function getLocationLabel(location) {
            if (!location) return '';
            return location.address || location.display_name || location.label || '';
        }

        function formatIncidentCoordinates(location) {
            if (!location || !Number.isFinite(Number(location.lat)) || !Number.isFinite(Number(location.lng))) return '';
            return `${Number(location.lat).toFixed(6)}, ${Number(location.lng).toFixed(6)}`;
        }

        function isWithinQuezonCityBounds(lat, lng) {
            const numericLat = Number(lat);
            const numericLng = Number(lng);
            if (!Number.isFinite(numericLat) || !Number.isFinite(numericLng)) return false;
            return numericLat >= QUEZON_CITY_SERVICE_BOUNDS.south
                && numericLat <= QUEZON_CITY_SERVICE_BOUNDS.north
                && numericLng >= QUEZON_CITY_SERVICE_BOUNDS.west
                && numericLng <= QUEZON_CITY_SERVICE_BOUNDS.east;
        }

        function getQuezonCitySearchViewbox() {
            return `${QUEZON_CITY_SERVICE_BOUNDS.west},${QUEZON_CITY_SERVICE_BOUNDS.north},${QUEZON_CITY_SERVICE_BOUNDS.east},${QUEZON_CITY_SERVICE_BOUNDS.south}`;
        }

        function getQuezonCityLeafletBounds() {
            if (!window.L) return null;
            return L.latLngBounds(
                [QUEZON_CITY_SERVICE_BOUNDS.south, QUEZON_CITY_SERVICE_BOUNDS.west],
                [QUEZON_CITY_SERVICE_BOUNDS.north, QUEZON_CITY_SERVICE_BOUNDS.east]
            );
        }

        function textLooksLikeQuezonCity(text = '') {
            return /\b(quezon city|lungsod quezon|city of quezon)\b/i.test(String(text));
        }

        function resultLooksQuezonCity(result) {
            if (!result || !isWithinQuezonCityBounds(result.lat, result.lon)) return false;
            const address = result.address || {};
            const addressText = [
                result.display_name,
                address.city,
                address.town,
                address.municipality,
                address.county,
                address.state_district,
                address.suburb
            ].filter(Boolean).join(' ');
            return textLooksLikeQuezonCity(addressText);
        }

        function updateIncidentLocationSelectedLabel(message) {
            const label = document.getElementById('botLocationSelected');
            if (!label) return;
            const address = message || getLocationLabel(selectedLocationData);
            const coords = formatIncidentCoordinates(selectedLocationData);
            if (!address && !coords) {
                label.classList.remove('has-location', 'is-outside');
                label.textContent = 'Search a place, tap a suggestion, or click the map to drop the pin.';
                return;
            }
            label.classList.remove('is-outside');
            label.classList.add('has-location');
            label.textContent = coords ? `${address || 'Pinned location'} (${coords})` : address;
        }

        function showIncidentLocationBoundaryWarning(message = 'Only Quezon City locations are accepted. Please choose a pin inside the boundary.') {
            const label = document.getElementById('botLocationSelected');
            if (!label) return;
            label.classList.remove('has-location');
            label.classList.add('is-outside');
            label.textContent = message;
        }

        function hideIncidentLocationSuggestions() {
            const suggestions = document.getElementById('botLocationSuggestions');
            if (!suggestions) return;
            suggestions.hidden = true;
            suggestions.innerHTML = '';
        }

        function setIncidentLocationPin(lat, lng, address = '', options = {}) {
            const numericLat = Number(lat);
            const numericLng = Number(lng);
            if (!Number.isFinite(numericLat) || !Number.isFinite(numericLng)) return;

            if (!isWithinQuezonCityBounds(numericLat, numericLng)) {
                if (incidentLocationMap && incidentLocationMarker) {
                    if (selectedLocationData && isWithinQuezonCityBounds(selectedLocationData.lat, selectedLocationData.lng)) {
                        incidentLocationMarker.setLatLng([selectedLocationData.lat, selectedLocationData.lng]);
                    } else {
                        incidentLocationMap.removeLayer(incidentLocationMarker);
                        incidentLocationMarker = null;
                    }
                }
                showIncidentLocationBoundaryWarning();
                return false;
            }

            selectedLocationData = {
                lat: numericLat,
                lng: numericLng,
                address: address || (options.reverse ? '' : getLocationLabel(selectedLocationData)) || ''
            };

            const input = document.getElementById('botLocationInput');
            if (input && selectedLocationData.address) input.value = selectedLocationData.address;

            if (incidentLocationMap && window.L) {
                const latLng = [numericLat, numericLng];
                if (!incidentLocationMarker) {
                    incidentLocationMarker = L.marker(latLng, { draggable: true }).addTo(incidentLocationMap);
                    incidentLocationMarker.on('dragend', () => {
                        const markerPosition = incidentLocationMarker.getLatLng();
                        setIncidentLocationPin(markerPosition.lat, markerPosition.lng, '', { reverse: true, keepView: true });
                    });
                } else {
                    incidentLocationMarker.setLatLng(latLng);
                }

                if (!options.keepView) {
                    incidentLocationMap.setView(latLng, Math.max(incidentLocationMap.getZoom(), 16));
                }
            }

            updateIncidentLocationSelectedLabel(options.reverse ? 'Finding nearest address...' : '');

            if (options.reverse) {
                reverseIncidentLocation(numericLat, numericLng).then(reverseAddress => {
                    if (reverseAddress && !textLooksLikeQuezonCity(reverseAddress)) {
                        if (incidentLocationMap && incidentLocationMarker) {
                            incidentLocationMap.removeLayer(incidentLocationMarker);
                            incidentLocationMarker = null;
                        }
                        selectedLocationData = null;
                        showIncidentLocationBoundaryWarning('That pin looks outside Quezon City. Please choose a location inside the boundary.');
                        return;
                    }
                    if (!reverseAddress) {
                        updateIncidentLocationSelectedLabel();
                        return;
                    }
                    selectedLocationData.address = reverseAddress;
                    const currentInput = document.getElementById('botLocationInput');
                    if (currentInput) currentInput.value = reverseAddress;
                    updateIncidentLocationSelectedLabel();
                }).catch(() => updateIncidentLocationSelectedLabel());
            }
            return true;
        }

        async function initIncidentLocationPicker() {
            const input = document.getElementById('botLocationInput');
            if (!input) return;

            input.focus();
            input.onkeydown = e => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    confirmIncidentLocation();
                }
            };
            input.oninput = () => {
                if (incidentLocationSearchTimer) clearTimeout(incidentLocationSearchTimer);
                incidentLocationSearchTimer = setTimeout(() => searchIncidentLocations(input.value.trim()), 350);
            };
            if (!incidentLocationOutsideClickBound) {
                document.addEventListener('click', event => {
                    const bubble = document.getElementById('botLocationBubble');
                    if (bubble && !bubble.contains(event.target)) hideIncidentLocationSuggestions();
                });
                incidentLocationOutsideClickBound = true;
            }

            const hasUsableCurrentLocation = locationData?.lat && locationData?.lng && isWithinQuezonCityBounds(locationData.lat, locationData.lng);
            const startLat = Number(hasUsableCurrentLocation ? locationData.lat : (selectedLocationData?.lat || DEFAULT_INCIDENT_LOCATION.lat));
            const startLng = Number(hasUsableCurrentLocation ? locationData.lng : (selectedLocationData?.lng || DEFAULT_INCIDENT_LOCATION.lng));
            const startAddress = hasUsableCurrentLocation ? getLocationLabel(locationData) : getLocationLabel(selectedLocationData);

            if (startAddress) input.value = startAddress;

            if (!window.L) {
                updateIncidentLocationSelectedLabel('Loading map...');
                const leafletReady = await loadLeafletAssets();
                if (!leafletReady || !window.L) {
                    updateIncidentLocationSelectedLabel('Map could not load. You can still type the location manually.');
                    return;
                }
            }

            if (!window.L) {
                updateIncidentLocationSelectedLabel('Map could not load. You can still type the location manually.');
                return;
            }

            const mapEl = document.getElementById('botLocationMap');
            if (!mapEl) return;
            const quezonCityBounds = getQuezonCityLeafletBounds();
            incidentLocationMap = L.map(mapEl, {
                zoomControl: true,
                attributionControl: true,
                maxBounds: quezonCityBounds ? quezonCityBounds.pad(0.18) : undefined,
                maxBoundsViscosity: 0.95,
                minZoom: 11
            }).setView([startLat, startLng], 14);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; OpenStreetMap contributors'
            }).addTo(incidentLocationMap);

            if (quezonCityBounds) {
                L.rectangle(quezonCityBounds, {
                    color: '#8e44ad',
                    weight: 2,
                    dashArray: '7 6',
                    fillColor: '#8e44ad',
                    fillOpacity: 0.05,
                    interactive: false
                }).addTo(incidentLocationMap);
                incidentLocationMap.fitBounds(quezonCityBounds, { padding: [12, 12] });
            }

            incidentLocationMap.on('click', event => {
                setIncidentLocationPin(event.latlng.lat, event.latlng.lng, '', { reverse: true });
            });

            if (hasUsableCurrentLocation) {
                setIncidentLocationPin(locationData.lat, locationData.lng, startAddress, { reverse: !startAddress });
            } else {
                updateIncidentLocationSelectedLabel();
            }

            setTimeout(() => incidentLocationMap.invalidateSize(), 120);
        }

        async function searchIncidentLocations(query) {
            const suggestions = document.getElementById('botLocationSuggestions');
            if (!suggestions) return;
            if (query.length < 3) {
                hideIncidentLocationSuggestions();
                return;
            }

            if (incidentLocationSearchAbort) incidentLocationSearchAbort.abort();
            incidentLocationSearchAbort = new AbortController();

            suggestions.hidden = false;
            suggestions.innerHTML = '<button type="button" class="chat-location-suggestion">Searching locations...</button>';

            const searchText = textLooksLikeQuezonCity(query) || /philippines|manila/i.test(query) ? query : `${query}, Quezon City, Philippines`;
            const url = `https://nominatim.openstreetmap.org/search?format=jsonv2&limit=8&addressdetails=1&countrycodes=ph&bounded=1&viewbox=${getQuezonCitySearchViewbox()}&q=${encodeURIComponent(searchText)}`;

            try {
                const response = await fetch(url, { signal: incidentLocationSearchAbort.signal });
                const rawResults = await response.json();
                const results = Array.isArray(rawResults) ? rawResults.filter(resultLooksQuezonCity).slice(0, 5) : [];
                if (!Array.isArray(results) || results.length === 0) {
                    suggestions.innerHTML = '<button type="button" class="chat-location-suggestion">No Quezon City places found. Try a nearby QC landmark.</button>';
                    return;
                }

                suggestions.innerHTML = '';
                results.forEach(result => {
                    const button = document.createElement('button');
                    button.type = 'button';
                    button.className = 'chat-location-suggestion';
                    button.textContent = result.display_name || 'Selected place';
                    button.onclick = () => {
                        const label = result.display_name || query;
                        const input = document.getElementById('botLocationInput');
                        if (input) input.value = label;
                        setIncidentLocationPin(result.lat, result.lon, label);
                        hideIncidentLocationSuggestions();
                    };
                    suggestions.appendChild(button);
                });
            } catch (error) {
                if (error.name === 'AbortError') return;
                suggestions.innerHTML = '<button type="button" class="chat-location-suggestion">Search unavailable. You can still type the location.</button>';
            }
        }

        async function geocodeIncidentLocation(query) {
            const searchText = textLooksLikeQuezonCity(query) || /philippines|manila/i.test(query) ? query : `${query}, Quezon City, Philippines`;
            const url = `https://nominatim.openstreetmap.org/search?format=jsonv2&limit=5&addressdetails=1&countrycodes=ph&bounded=1&viewbox=${getQuezonCitySearchViewbox()}&q=${encodeURIComponent(searchText)}`;
            const response = await fetch(url);
            const rawResults = await response.json();
            const results = Array.isArray(rawResults) ? rawResults.filter(resultLooksQuezonCity) : [];
            if (!Array.isArray(results) || !results[0]) return null;
            return {
                lat: Number(results[0].lat),
                lng: Number(results[0].lon),
                address: results[0].display_name || query
            };
        }

        async function reverseIncidentLocation(lat, lng) {
            const url = `https://nominatim.openstreetmap.org/reverse?format=jsonv2&zoom=18&addressdetails=1&lat=${encodeURIComponent(lat)}&lon=${encodeURIComponent(lng)}`;
            const response = await fetch(url);
            const data = await response.json();
            return data.display_name || '';
        }

        window.getBotCurrentLocation = async function() {
            const inp = document.getElementById('botLocationInput');
            if (inp) inp.value = "Fetching location...";
            try {
                const loc = await tryGetLocation();
                if (loc && Number.isFinite(Number(loc.lat)) && Number.isFinite(Number(loc.lng))) {
                    if (!isWithinQuezonCityBounds(loc.lat, loc.lng)) {
                        selectedLocationData = null;
                        if (incidentLocationMap && incidentLocationMarker) {
                            incidentLocationMap.removeLayer(incidentLocationMarker);
                            incidentLocationMarker = null;
                        }
                        if (inp) inp.value = '';
                        showIncidentLocationBoundaryWarning('Your current GPS is outside Quezon City. Please search or pin a QC location.');
                        return;
                    }
                    const address = loc.address || 'Current location';
                    if (inp) inp.value = address;
                    setIncidentLocationPin(loc.lat, loc.lng, address, { reverse: !loc.address });
                } else {
                    if (inp) inp.value = "Location not found, please type manually.";
                }
            } catch (err) {
                if (inp) inp.value = "Failed to get location, please type manually.";
            }
        };

        window.confirmIncidentLocation = async function() {
            const inp = document.getElementById('botLocationInput');
            const val = inp ? inp.value.trim() : '';
            if (!val && !selectedLocationData) {
                Swal.fire({ icon: 'warning', title: 'Location Required', text: 'Please enter a location.' });
                return;
            }

            if (val && (!selectedLocationData || getLocationLabel(selectedLocationData) !== val)) {
                try {
                    const geocoded = await geocodeIncidentLocation(val);
                    if (geocoded && Number.isFinite(geocoded.lat) && Number.isFinite(geocoded.lng)) {
                        selectedLocationData = geocoded;
                    } else if (getLocationLabel(selectedLocationData) && getLocationLabel(selectedLocationData) !== val) {
                        selectedLocationData = null;
                    }
                } catch (error) {
                    if (getLocationLabel(selectedLocationData) && getLocationLabel(selectedLocationData) !== val) {
                        selectedLocationData = null;
                    }
                }
            }

            selectedLocation = getLocationLabel(selectedLocationData) || val;
            if (!selectedLocation && selectedLocationData) selectedLocation = formatIncidentCoordinates(selectedLocationData);

            if (!selectedLocationData || !isWithinQuezonCityBounds(selectedLocationData.lat, selectedLocationData.lng)) {
                showIncidentLocationBoundaryWarning();
                Swal.fire({ icon: 'warning', title: 'Quezon City Only', text: 'Please choose an exact location inside Quezon City.' });
                return;
            }

            const bubble = document.getElementById('botLocationBubble');
            if (bubble) bubble.remove();

            const coords = formatIncidentCoordinates(selectedLocationData);
            appendUserMessage(coords ? `Location: ${selectedLocation} (${coords})` : `Location: ${selectedLocation}`);

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
            const coords = formatIncidentCoordinates(selectedLocationData);
            const lines = [
                `Incident Type: ${type}`,
                location ? `Location: ${location}` : '',
                coords ? `Coordinates: ${coords}` : '',
                selectedLocationData?.lat ? `Map: https://www.google.com/maps?q=${encodeURIComponent(`${selectedLocationData.lat},${selectedLocationData.lng}`)}` : '',
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
            if (selectedLocationData) {
                formData.append('locationAddress', selectedLocationData.address || selectedLocation);
                formData.append('locationLat', selectedLocationData.lat || '');
                formData.append('locationLng', selectedLocationData.lng || '');
            }
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
                
                appendBotMessage("Incident Report Submitted successfully! We have opened a live response thread. You can chat here in real time.");
                appendSystemMessage("Connected to response thread");
                
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
                if (incidentChatPollInFlight) return;
                incidentChatPollInFlight = true;
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
                } finally {
                    incidentChatPollInFlight = false;
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
