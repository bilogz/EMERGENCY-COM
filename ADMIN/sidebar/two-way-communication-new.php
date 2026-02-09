<?php
/**
 * Admin Two-Way Communication with Emergency Call Interface
 * Includes messaging interface and call persistence
 */

session_start();
if (!isset($_SESSION['admin_user_id'])) {
    header('Location: ../login.php');
    exit;
}

// Include database connection
require_once 'api/db_connect.php';

// Get admin profile
$admin_id = $_SESSION['admin_user_id'];
$stmt = $pdo->prepare("SELECT username FROM admin_users WHERE id = ?");
$stmt->execute([$admin_id]);
$admin = $stmt->fetch(PDO::FETCH_ASSOC);
$admin_username = $admin['username'] ?? 'Admin';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Emergency Communication - Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        <link rel="stylesheet" href="css/module-two-way-communication-new.css?v=<?php echo filemtime(__DIR__ . '/css/module-two-way-communication-new.css'); ?>">
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Emergency Communication System</h1>
            <p>Welcome, <?php echo htmlspecialchars($admin_username); ?></p>
        </div>
        
        <div class="comm-container">
            <div class="conversations-panel">
                <div class="tabs">
                    <button class="tab active" data-tab="active">Active</button>
                    <button class="tab" data-tab="closed">Closed</button>
                </div>
                <div class="conversations-list" id="conversationsList">
                    <!-- Conversations will be loaded here -->
                </div>
            </div>
            
            <div class="chat-panel">
                <div class="chat-header">
                    <div id="chatUserName">Select a conversation</div>
                    <div id="chatUserStatus"></div>
                </div>
                <div class="chat-messages" id="chatMessages">
                    <div style="display: flex; align-items: center; justify-content: center; height: 100%; color: #94a3b8;">
                        <div style="text-align: center;">
                            <i class="fas fa-comments" style="font-size: 48px; margin-bottom: 16px; opacity: 0.3;"></i>
                            <p>Select a conversation from the list to start messaging</p>
                        </div>
                    </div>
                </div>
                <div class="chat-input">
                    <input type="text" id="messageInput" placeholder="Type a message..." disabled>
                    <button class="btn btn-primary" id="sendButton" disabled>Send</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Emergency Call Overlay -->
    <div id="callOverlay">
        <div class="call-container">
            <div id="callActiveBanner" style="display: none; margin: -6px 0 12px; padding: 8px 12px; border-radius: 12px; background: rgba(220,38,38,0.18); border: 1px solid rgba(220,38,38,0.45); color: #fecaca; font-weight: 800; letter-spacing: 0.6px; text-transform: uppercase; text-align: center;">CALL ON ACTIVE</div>
            
            <div class="call-header">
                <div class="call-icon">
                    <i class="fas fa-headset" style="color: #4c8a89;"></i>
                </div>
                <div class="call-info">
                    <div class="call-title">Emergency Call</div>
                    <div id="callStatus" class="call-status">Connecting…</div>
                </div>
                <div id="callTimer" class="call-timer">00:00</div>
            </div>
            
            <div id="callMessages" class="call-messages">
                <div style="text-align: center; opacity: 0.6; font-size: 12px;">Messages will appear here</div>
            </div>
            
            <div class="call-input">
                <input type="text" id="callMessageInput" placeholder="Type a message...">
                <button class="btn btn-primary" id="callSendMessageBtn">Send</button>
            </div>
            
            <div class="call-controls">
                <button id="endCallBtn" class="btn btn-secondary" disabled style="opacity: 0.6; pointer-events: none;">End Call</button>
            </div>
        </div>
    </div>
    
    <audio id="remoteAudio" autoplay></audio>
    
    <script src="https://cdn.socket.io/4.7.5/socket.io.min.js"></script>
    <script>
        // Configuration
        const API_BASE = './api/';
        const IS_LOCAL = ['localhost', '127.0.0.1'].includes(window.location.hostname);
        const SOCKET_IO_PATH = '/socket.io';
        const LOCAL_SOCKET_PORT = 3000;
        const SIGNALING_HOST = window.location.hostname === 'localhost' ? '127.0.0.1' : window.location.hostname;
        const SIGNALING_URL = IS_LOCAL ? `${window.location.protocol}//${SIGNALING_HOST}` + ':' + LOCAL_SOCKET_PORT : null;
        const room = "emergency-room";
        const ADMIN_USERNAME = "<?php echo htmlspecialchars($admin_username); ?>";
        const ADMIN_AVATAR = `https://ui-avatars.com/api/?name=${encodeURIComponent(ADMIN_USERNAME)}&background=3b82f6&color=fff&size=64`;
        
        // State
        let currentConversationId = null;
        let currentStatus = 'active';
        let socket = null;
        let messageInterval = null;
        let pollInterval = null;
        let lastMessageId = 0;
        
        // Call state
        let pc = null;
        let localStream = null;
        let callId = null;
        let callConnectedAt = null;
        let timerInterval = null;
        let locationData = null;
        let callMessages = [];
        let pendingOffer = null;
        let pendingCallId = null;
        let pendingCandidates = [];
        
        // Initialize
        document.addEventListener('DOMContentLoaded', () => {
            loadConversations(true);
            pollUpdates();
            initCallSystem();
        });
        
        // Tab switching
        document.querySelectorAll('.tab').forEach(tab => {
            tab.addEventListener('click', () => {
                document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
                tab.classList.add('active');
                currentStatus = tab.dataset.tab;
                loadConversations(true);
            });
        });
        
        // Conversation management
        async function loadConversations(initial = false) {
            try {
                const response = await fetch(`${API_BASE}chat-get-conversations.php?status=${currentStatus}`);
                const data = await response.json();
                
                if (data.success && Array.isArray(data.conversations)) {
                    renderConversations(data.conversations);
                }
            } catch (e) {
                console.error('Failed to load conversations:', e);
            }
        }
        
        function renderConversations(conversations) {
            const container = document.getElementById('conversationsList');
            
            if (conversations.length === 0) {
                container.innerHTML = `<p style="text-align: center; color: #94a3b8; padding: 2rem;">No ${currentStatus} conversations</p>`;
                return;
            }
            
            container.innerHTML = conversations.map(conv => {
                const statusClass = conv.status === 'active' ? 'status-active' : 'status-closed';
                const isEmergencyCall = conv.last_message && conv.last_message.includes('Emergency call completed');
                const emergencyClass = isEmergencyCall ? 'emergency-call-item' : '';
                
                return `
                    <div class="conversation-item ${emergencyClass}" data-id="${conv.id}" onclick="openConversation(${conv.id})">
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <div style="width: 36px; height: 36px; border-radius: 50%; background: #e2e8f0; display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-user" style="color: #64748b;"></i>
                            </div>
                            <div style="flex: 1; min-width: 0;">
                                <div style="font-weight: 600; margin-bottom: 2px;">${htmlspecialchars(conv.user_name || 'Unknown User')}</div>
                                <div style="font-size: 12px; opacity: 0.7; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">${htmlspecialchars(conv.last_message || 'No messages')}</div>
                            </div>
                            <div style="text-align: right;">
                                <span class="status-badge ${statusClass}">${conv.status}</span>
                                <div style="font-size: 11px; opacity: 0.6; margin-top: 4px;">${formatTimeAgo(conv.last_message_time)}</div>
                            </div>
                        </div>
                    </div>
                `;
            }).join('');
        }
        
        async function openConversation(id) {
            currentConversationId = id;
            
            // Update UI
            document.querySelectorAll('.conversation-item').forEach(item => {
                item.classList.remove('active');
            });
            document.querySelector(`[data-id="${id}"]`).classList.add('active');
            
            // Enable input
            document.getElementById('messageInput').disabled = false;
            document.getElementById('sendButton').disabled = false;
            
            // Load conversation details
            try {
                const response = await fetch(`${API_BASE}chat-get-conversation.php?id=${id}`);
                const data = await response.json();
                
                if (data.success && data.conversation) {
                    const conv = data.conversation;
                    document.getElementById('chatUserName').textContent = conv.user_name || 'Unknown User';
                    document.getElementById('chatUserStatus').textContent = conv.status === 'active' ? 'Active' : 'Closed';
                }
            } catch (e) {
                console.error('Failed to load conversation:', e);
            }
            
            // Load messages
            loadMessages(id, true);
        }
        
        async function loadMessages(id, initial = false) {
            const container = document.getElementById('chatMessages');
            if (initial) {
                container.innerHTML = '<div style="display: flex; justify-content: center; padding: 2rem;"><i class="fas fa-spinner fa-spin"></i></div>';
            }
            
            // Clear polling
            if (messageInterval) clearInterval(messageInterval);
            
            const fetchMessages = async () => {
                try {
                    const response = await fetch(`${API_BASE}chat-get-messages.php?conversationId=${id}&lastMessageId=${lastMessageId}`);
                    const data = await response.json();
                    
                    if (data.success && Array.isArray(data.messages)) {
                        if (container.querySelector('.fa-spinner')) {
                            container.innerHTML = '';
                        }
                        
                        if (data.messages.length === 0 && initial) {
                            container.innerHTML = '<p style="text-align: center; color: #94a3b8; padding: 2rem;">No messages yet.</p>';
                        }
                        
                        data.messages.forEach(msg => {
                            appendMessage(msg);
                            lastMessageId = Math.max(lastMessageId, msg.id);
                        });
                        
                        scrollToBottom();
                    }
                } catch (e) {
                    console.error('Failed to load messages:', e);
                }
            };
            
            await fetchMessages();
            messageInterval = setInterval(fetchMessages, 3000);
        }
        
        function appendMessage(msg) {
            const container = document.getElementById('chatMessages');
            
            // Remove placeholders
            const placeholder = container.querySelector('p');
            if (placeholder) placeholder.remove();
            
            const messageDiv = document.createElement('div');
            const isAdmin = msg.sender_type === 'admin';
            messageDiv.className = `message ${isAdmin ? 'admin' : 'user'}`;
            
            const senderName = isAdmin ? ADMIN_USERNAME : (msg.sender_name || 'User');
            const avatar = isAdmin ? ADMIN_AVATAR : `https://ui-avatars.com/api/?name=${encodeURIComponent(senderName)}&background=6c757d&color=fff&size=64`;
            
            const time = new Date(msg.timestamp).toLocaleTimeString('en-US', { 
                hour: 'numeric', 
                minute: '2-digit', 
                hour12: true 
            });
            
            messageDiv.innerHTML = `
                <div class="message-avatar" style="background-image: url('${avatar}'); background-size: cover; background-position: center;"></div>
                <div class="message-content">
                    <div>${htmlspecialchars(msg.text)}</div>
                    <div class="message-meta">${senderName} • ${time}</div>
                </div>
            `;
            
            container.appendChild(messageDiv);
            scrollToBottom();
        }
        
        async function sendMessage() {
            const input = document.getElementById('messageInput');
            const text = input.value.trim();
            if (!text || !currentConversationId) return;
            
            input.value = '';
            
            try {
                const formData = new FormData();
                formData.append('text', text);
                formData.append('conversationId', currentConversationId);
                
                const response = await fetch(`${API_BASE}chat-send.php`, {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                if (!data.success) {
                    alert('Failed to send message');
                }
            } catch (e) {
                console.error('Failed to send message:', e);
                alert('Send error');
            }
        }
        
        // Call System
        function initCallSystem() {
            const socketOptions = {
                path: SOCKET_IO_PATH,
                transports: ['websocket', 'polling'],
                reconnection: true,
                timeout: 8000

            };

            socket = IS_LOCAL
                ? io(SIGNALING_URL, socketOptions)
                : io(socketOptions);
            socket.emit('join', room);
            
            socket.on('offer', handleIncomingCall);
            socket.on('candidate', handleIceCandidate);
            socket.on('hangup', handleHangup);
            socket.on('call-message', handleCallMessage);
            
            // Event listeners
            document.getElementById('callSendMessageBtn').onclick = sendCallMessage;
            document.getElementById('callMessageInput').onkeypress = (e) => {
                if (e.key === 'Enter') sendCallMessage();
            };
            document.getElementById('endCallBtn').onclick = endCall;
        }
        
        function handleIncomingCall(payload) {
            const incomingCallId = payload.callId || payload.sdp?.callId;
            if (!incomingCallId) return;
            
            pendingCallId = incomingCallId;
            pendingOffer = payload.sdp || payload;
            pendingCandidates = [];
            
            // Show incoming call in active list
            showIncomingCallInList();
            
            // Play notification sound
            playNotificationSound();
        }
        
        function showIncomingCallInList() {
            const container = document.getElementById('conversationsList');
            const existingEmergencyCall = container.querySelector('.emergency-call-item');
            
            if (existingEmergencyCall) return; // Already showing
            
            const emergencyCallHtml = `
                <div class="conversation-item emergency-call-item" data-call-id="${pendingCallId}" style="margin-bottom: 12px;">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <div style="width: 36px; height: 36px; border-radius: 10px; background: rgba(220,38,38,0.18); border: 1px solid rgba(220,38,38,0.35); display: flex; align-items: center; justify-content: center;">
                            <i class="fas fa-phone-alt" style="color: #dc2626;"></i>
                        </div>
                        <div style="flex: 1; min-width: 0;">
                            <div style="font-weight: 900; letter-spacing: 0.4px;">Emergency Call</div>
                            <div style="font-size: 12px; opacity: 0.9;">Incoming call request</div>
                        </div>
                        <div style="display: flex; gap: 8px;">
                            <button class="btn btn-sm btn-secondary" onclick="declineEmergencyCall()">Decline</button>
                            <button class="btn btn-sm btn-primary" onclick="acceptEmergencyCall()">Accept</button>
                        </div>
                    </div>
                </div>
            `;
            
            container.insertAdjacentHTML('afterbegin', emergencyCallHtml);
        }
        
        window.acceptEmergencyCall = async function() {
            if (!pendingOffer || !pendingCallId) return;
            
            callId = pendingCallId;
            
            // Remove from list
            const emergencyItem = document.querySelector(`[data-call-id="${pendingCallId}"]`);
            if (emergencyItem) emergencyItem.remove();
            
            // Show call overlay
            document.getElementById('callOverlay').style.display = 'block';
            document.getElementById('callActiveBanner').style.display = 'block';
            document.getElementById('callStatus').textContent = 'Connecting…';
            
            try {
                initPeerConnection();
                await pc.setRemoteDescription(pendingOffer);
                
                localStream = await navigator.mediaDevices.getUserMedia({ audio: true });
                localStream.getTracks().forEach(track => pc.addTrack(track, localStream));
                
                const answer = await pc.createAnswer();
                await pc.setLocalDescription(answer);
                
                socket.emit('answer', { sdp: answer, callId }, room);
                
                // Handle pending candidates
                for (const candidate of pendingCandidates) {
                    await pc.addIceCandidate(candidate);
                }
            } catch (e) {
                console.error('Failed to accept call:', e);
                endCall();
            }
            
            pendingOffer = null;
            pendingCandidates = [];
        };
        
        window.declineEmergencyCall = function() {
            if (!pendingCallId) return;
            
            socket.emit('hangup', { callId: pendingCallId }, room);
            
            // Remove from list
            const emergencyItem = document.querySelector(`[data-call-id="${pendingCallId}"]`);
            if (emergencyItem) emergencyItem.remove();
            
            pendingCallId = null;
            pendingOffer = null;
            pendingCandidates = [];
            
            stopNotificationSound();
        };
        
        function initPeerConnection() {
            pc = new RTCPeerConnection({
                iceServers: [
                    { urls: 'stun:stun.l.google.com:19302' },
                    { urls: 'stun:global.stun.twilio.com:3478' }
                ]
            });
            
            pc.ontrack = (e) => {
                document.getElementById('remoteAudio').srcObject = e.streams[0];
            };
            
            pc.onicecandidate = (e) => {
                if (!e.candidate) return;
                socket.emit('candidate', { candidate: e.candidate, callId }, room);
            };
            
            pc.onconnectionstatechange = () => {
                if (pc.connectionState === 'connected' && !callConnectedAt) {
                    callConnectedAt = Date.now();
                    document.getElementById('callStatus').textContent = 'Connected';
                    document.getElementById('endCallBtn').disabled = false;
                    document.getElementById('endCallBtn').style.opacity = '1';
                    document.getElementById('endCallBtn').style.pointerEvents = 'auto';
                    startCallTimer();
                    stopNotificationSound();
                }
                
                if (['disconnected', 'failed', 'closed'].includes(pc.connectionState)) {
                    endCall();
                }
            };
        }
        
        function handleIceCandidate(payload) {
            const candidate = payload.candidate;
            const incomingCallId = payload.callId;
            
            if (incomingCallId && incomingCallId !== callId && incomingCallId !== pendingCallId) {
                return;
            }
            
            if (!pc || !callId) {
                if (candidate) pendingCandidates.push(candidate);
                return;
            }
            
            if (pc && candidate) {
                pc.addIceCandidate(candidate);
            }
        }
        
        function handleHangup(payload) {
            const incomingCallId = payload.callId;
            
            if (incomingCallId === pendingCallId && !callId) {
                // Call was declined before acceptance
                window.declineEmergencyCall();
                return;
            }
            
            if (incomingCallId === callId) {
                endCall();
            }
        }
        
        function handleCallMessage(payload) {
            const incomingCallId = payload.callId;
            
            if (incomingCallId !== callId) return;
            
            if (payload.text && payload.sender !== 'admin') {
                addCallMessage(payload.text, 'user', payload.timestamp);
            }
        }
        
        function addCallMessage(text, sender = 'admin', timestamp = Date.now()) {
            const messagesContainer = document.getElementById('callMessages');
            
            // Clear placeholder
            if (callMessages.length === 0) {
                messagesContainer.innerHTML = '';
            }
            
            const messageDiv = document.createElement('div');
            messageDiv.className = `call-message ${sender}`;
            
            const time = new Date(timestamp).toLocaleTimeString('en-US', { 
                hour: 'numeric', 
                minute: '2-digit', 
                hour12: true 
            });
            
            const senderName = sender === 'admin' ? 'Emergency Services' : 'User';
            
            messageDiv.innerHTML = `
                <div class="call-message-header">${senderName} • ${time}</div>
                <div>${htmlspecialchars(text)}</div>
            `;
            
            messagesContainer.appendChild(messageDiv);
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
            
            callMessages.push({ text, sender, timestamp, callId });
        }
        
        function sendCallMessage() {
            const input = document.getElementById('callMessageInput');
            const text = input.value.trim();
            if (!text || !callId) return;
            
            input.value = '';
            
            // Add to local UI
            addCallMessage(text, 'admin');
            
            // Send via socket
            socket.emit('call-message', {
                text,
                callId,
                sender: 'admin',
                senderName: 'Emergency Services',
                timestamp: Date.now()
            }, room);
            
            // Log to database
            logCallMessage(text);
        }
        
        async function logCallMessage(text) {
            try {
                const formData = new FormData();
                formData.append('text', text);
                formData.append('userId', 'admin');
                formData.append('userName', 'Emergency Services');
                formData.append('conversationId', `call_${callId}`);
                
                await fetch(`${API_BASE}chat-send.php`, {
                    method: 'POST',
                    body: formData
                });
            } catch (e) {
                console.error('Failed to log call message:', e);
            }
        }
        
        function startCallTimer() {
            if (!callConnectedAt) return;
            
            timerInterval = setInterval(() => {
                const seconds = Math.floor((Date.now() - callConnectedAt) / 1000);
                const minutes = Math.floor(seconds / 60);
                const remainingSeconds = seconds % 60;
                document.getElementById('callTimer').textContent = 
                    `${String(minutes).padStart(2, '0')}:${String(remainingSeconds).padStart(2, '0')}`;
            }, 1000);
        }
        
        async function endCall() {
            const durationSec = callConnectedAt ? Math.floor((Date.now() - callConnectedAt) / 1000) : null;
            
            // Save completed call to database
            if (callId && durationSec) {
                try {
                    const response = await fetch('./api/save-completed-call.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            callId: callId,
                            userId: null,
                            userName: 'Emergency Call User',
                            userPhone: null,
                            duration: durationSec,
                            endedAt: Date.now()
                        })
                    });
                    
                    if (response.ok) {
                        // Refresh conversations list
                        loadConversations(true);
                    }
                } catch (e) {
                    console.error('Failed to save completed call:', e);
                }
            }
            
            // Notify peer
            if (callId) {
                socket.emit('hangup', { callId }, room);
            }
            
            // Cleanup
            if (timerInterval) clearInterval(timerInterval);
            if (localStream) {
                localStream.getTracks().forEach(track => track.stop());
                localStream = null;
            }
            if (pc) {
                pc.close();
                pc = null;
            }
            
            callId = null;
            callConnectedAt = null;
            callMessages = [];
            
            // Hide overlay
            document.getElementById('callOverlay').style.display = 'none';
            document.getElementById('callMessages').innerHTML = '<div style="text-align: center; opacity: 0.6; font-size: 12px;">Messages will appear here</div>';
            document.getElementById('callMessageInput').value = '';
            document.getElementById('callTimer').textContent = '00:00';
            document.getElementById('callStatus').textContent = 'Connecting…';
            document.getElementById('endCallBtn').disabled = true;
            document.getElementById('endCallBtn').style.opacity = '0.6';
            document.getElementById('endCallBtn').style.pointerEvents = 'none';
        }
        
        // Utility functions
        function scrollToBottom() {
            const container = document.getElementById('chatMessages');
            container.scrollTop = container.scrollHeight;
        }
        
        function formatTimeAgo(timestamp) {
            const now = new Date();
            const time = new Date(timestamp);
            const diff = Math.floor((now - time) / 1000);
            
            if (diff < 60) return 'Just now';
            if (diff < 3600) return `${Math.floor(diff / 60)}m ago`;
            if (diff < 86400) return `${Math.floor(diff / 3600)}h ago`;
            return time.toLocaleDateString();
        }
        
        function htmlspecialchars(str) {
            const div = document.createElement('div');
            div.textContent = str;
            return div.innerHTML;
        }
        
        function playNotificationSound() {
            // Simple beep sound
            const audioContext = new (window.AudioContext || window.webkitAudioContext)();
            const oscillator = audioContext.createOscillator();
            const gainNode = audioContext.createGain();
            
            oscillator.connect(gainNode);
            gainNode.connect(audioContext.destination);
            
            oscillator.frequency.value = 800;
            oscillator.type = 'sine';
            
            gainNode.gain.setValueAtTime(0.3, audioContext.currentTime);
            gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.5);
            
            oscillator.start(audioContext.currentTime);
            oscillator.stop(audioContext.currentTime + 0.5);
        }
        
        function stopNotificationSound() {
            // Sound stops automatically
        }
        
        // Message sending
        document.getElementById('sendButton').onclick = sendMessage;
        document.getElementById('messageInput').onkeypress = (e) => {
            if (e.key === 'Enter') sendMessage();
        };
        
        // Polling
        function pollUpdates() {
            pollInterval = setInterval(() => {
                loadConversations(false);
            }, 5000);
        }
    </script>
</body>
</html>
