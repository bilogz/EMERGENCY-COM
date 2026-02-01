<?php
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
        document.addEventListener('DOMContentLoaded', function() {
            const langBtn = document.getElementById('languageSelectorBtn');
            if (langBtn && window.languageSelectorModal) {
                langBtn.addEventListener('click', function() {
                    window.languageSelectorModal.open();
                });
            }
        });
    </script>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <button class="sidebar-toggle-btn" aria-label="Toggle menu" onclick="window.sidebarToggle()" data-no-translate>
        <i class="fas fa-bars"></i>
    </button>

    <main class="main-content">
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
        <div style="position:absolute; left:50%; top:50%; transform:translate(-50%,-50%); width:min(420px, 92vw); background:#0f172a; border:1px solid rgba(255,255,255,0.12); border-radius:16px; padding:22px; color:#fff; box-shadow:0 20px 60px rgba(0,0,0,0.5);">
            <div style="display:flex; align-items:center; gap:12px;">
                <div style="width:44px; height:44px; border-radius:12px; background:rgba(76,138,137,0.2); display:flex; align-items:center; justify-content:center;">
                    <i class="fas fa-headset" style="color:#4c8a89;"></i>
                </div>
                <div style="flex:1;">
                    <div style="font-weight:700; font-size:16px;">Emergency Call</div>
                    <div id="callStatus" style="opacity:0.85; font-size:13px;">Connecting…</div>
                </div>
                <div id="callTimer" style="font-variant-numeric:tabular-nums; font-weight:700;">00:00</div>
            </div>
            <div style="margin-top:18px; display:flex; gap:10px; justify-content:flex-end;">
                <button id="endCallBtn" class="btn btn-secondary" disabled style="opacity:0.6; pointer-events:none;">End Call</button>
            </div>
        </div>
    </div>
    <audio id="remote" autoplay></audio>

    <script src="<?php echo (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http') . '://' . ($_SERVER['SERVER_NAME'] ?? 'localhost') . ':3000'; ?>/socket.io/socket.io.js"></script>
    <script>
        const SIGNALING_URL = `${window.location.protocol}//${window.location.hostname}:3000`;
        let socket = null;
        let socketBound = false;
        const room = "emergency-room";

        function ensureSocket() {
            if (socket) return socket;
            if (typeof window.io !== 'function') {
                return null;
            }
            socket = window.io(SIGNALING_URL);
            bindSocketHandlers();
            return socket;
        }

        function bindSocketHandlers() {
            if (!socket || socketBound) return;
            socketBound = true;

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
        let callStartedAt = null;
        let callConnectedAt = null;
        let timerInterval = null;
        let locationData = null;

        function formatTime(totalSeconds) {
            const m = String(Math.floor(totalSeconds / 60)).padStart(2, '0');
            const s = String(totalSeconds % 60).padStart(2, '0');
            return `${m}:${s}`;
        }

        function setOverlayVisible(visible) {
            document.getElementById('callOverlay').style.display = visible ? 'block' : 'none';
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

        document.getElementById('endCallBtn').onclick = endCall;

        function initPeer() {
            pc = new RTCPeerConnection({
                iceServers: [
                    { urls: 'stun:stun.l.google.com:19302' },
                    { urls: 'stun:global.stun.twilio.com:3478?transport=udp' }
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

            const s = ensureSocket();
            if (!s) {
                setStatus('Call service unavailable. Start the signaling server on port 3000.');
                setEndEnabled(true);
                return;
            }

            if (s && s.connected === false) {
                setStatus('Connecting to call service…');
            }

            try {
                callId = (window.crypto && crypto.randomUUID) ? crypto.randomUUID() : `call_${Date.now()}_${Math.random().toString(16).slice(2)}`;
                callStartedAt = Date.now();
                setStartButtonsDisabled(true);
                locationData = await tryGetLocation();
                await logCall('started');

                initPeer();
                s.emit("join", room);

                localStream = await navigator.mediaDevices.getUserMedia({ audio: true });
                localStream.getTracks().forEach(track => pc.addTrack(track, localStream));

                const offer = await pc.createOffer();
                await pc.setLocalDescription(offer);
                s.emit("offer", { sdp: offer, callId }, room);
            } catch (e) {
                setStatus('Call failed');
                setEndEnabled(true);
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
