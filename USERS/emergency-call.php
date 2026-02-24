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
                        <button class="btn btn-primary" onclick="startInternetCall()"><i class="fas fa-headset"></i> <span>Start Internet Call</span></button>
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
                            <h3>Web/VoIP Call</h3>
                            <p>Start a voice call over WiFi or mobile data. Connect directly with emergency dispatchers.</p>
                            <button class="btn btn-primary" onclick="startInternetCall()">
                                <i class="fas fa-headset"></i> <span>Start Internet Call</span>
                            </button>
                        </div>
                        <div class="card">
                            <h3>Two-Way Chat</h3>
                            <p>Send incident details and get dispatcher replies over data. Real-time communication with emergency services.</p>
                            <button class="btn btn-secondary" onclick="openEmergencyChat()">
                                <i class="fas fa-comments"></i> <span>Open Chat</span>
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

        function openEmergencyChat() {
            // Use the global function from sidebar if available
            if (typeof window.openChat === 'function') {
                window.openChat();
                // Initialize Firebase chat if not already done
                if (window.initFirebaseChat && !window.chatInitialized) {
                    setTimeout(() => {
                        window.initFirebaseChat();
                    }, 100);
                }
            } else {
                // Fallback: try to find and click the chat button
                const chatFab = document.getElementById('chatFab');
                if (chatFab) {
                    chatFab.click();
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Chat Not Available',
                        text: 'Chat feature is loading. Please wait a moment and try again.',
                        confirmButtonText: 'OK'
                    });
                }
            }
        }
    </script>
    
    <!-- Emergency Alert System -->
    <script src="../ADMIN/header/js/emergency-alert.js"></script>
</body>
</html>
