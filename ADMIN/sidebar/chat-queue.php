<?php
/**
 * Chat Queue Page for Admin
 * Shows incoming chat requests and allows admins to accept and respond
 */

session_start();

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../login.php');
    exit();
}

$pageTitle = 'Chat Queue';
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
    <style>
        .chat-queue-container {
            display: grid;
            grid-template-columns: 350px 1fr;
            gap: 1.5rem;
            height: calc(100vh - 200px);
        }
        .queue-list {
            background: var(--card-bg);
            border-radius: 8px;
            padding: 1rem;
            overflow-y: auto;
        }
        .queue-item {
            padding: 1rem;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            margin-bottom: 1rem;
            cursor: pointer;
            transition: all 0.2s;
            background: var(--card-bg);
        }
        .queue-item:hover {
            border-color: var(--primary-color);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .queue-item.pending {
            border-left: 4px solid #ff9800;
        }
        .queue-item.accepted {
            border-left: 4px solid #4caf50;
        }
        .queue-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5rem;
        }
        .queue-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        .badge-pending {
            background: #fff3cd;
            color: #856404;
        }
        .chat-window {
            display: flex;
            flex-direction: column;
            background: var(--card-bg);
            border-radius: 8px;
            overflow: hidden;
        }
        .chat-header {
            padding: 1rem;
            border-bottom: 1px solid var(--border-color);
            background: var(--primary-color);
            color: white;
        }
        .chat-messages {
            flex: 1;
            padding: 1rem;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        .message {
            display: flex;
            gap: 0.5rem;
            max-width: 70%;
        }
        .message.user {
            align-self: flex-start;
        }
        .message.admin {
            align-self: flex-end;
            flex-direction: row-reverse;
        }
        .message-content {
            padding: 0.75rem 1rem;
            border-radius: 12px;
            background: var(--border-color);
        }
        .message.admin .message-content {
            background: var(--primary-color);
            color: white;
        }
        .chat-input {
            padding: 1rem;
            border-top: 1px solid var(--border-color);
            display: flex;
            gap: 0.5rem;
        }
        .chat-input input {
            flex: 1;
            padding: 0.75rem;
            border: 1px solid var(--border-color);
            border-radius: 8px;
        }
        .notification-modal {
            display: none;
            position: fixed;
            top: 20px;
            right: 20px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            padding: 1.5rem;
            max-width: 400px;
            z-index: 10000;
            animation: slideIn 0.3s ease;
        }
        .notification-modal.show {
            display: block;
        }
        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    <?php include 'includes/admin-header.php'; ?>
    
    <div class="main-content">
        <div class="main-container">
            <div class="title">
                <h1>Chat Queue</h1>
                <p>Manage incoming chat requests from users</p>
            </div>
            
            <div class="sub-container">
                <div class="page-content">
                    <div class="chat-queue-container">
                        <!-- Queue List -->
                        <div class="queue-list">
                            <h3 style="margin-bottom: 1rem;">Incoming Chats</h3>
                            <div id="queueList">
                                <p style="text-align: center; color: var(--text-secondary); padding: 2rem;">
                                    No pending chats
                                </p>
                            </div>
                        </div>

                        <!-- Chat Window -->
                        <div class="chat-window">
                            <div class="chat-header">
                                <h3 id="chatUserName">Select a chat from queue</h3>
                                <small id="chatUserStatus"></small>
                            </div>
                            <div class="chat-messages" id="chatMessages">
                                <p style="text-align: center; color: var(--text-secondary); padding: 2rem;">
                                    Select a chat to start messaging
                                </p>
                            </div>
                            <div class="chat-input">
                                <input type="text" id="messageInput" placeholder="Type a message..." disabled>
                                <button class="btn btn-primary" id="sendButton" disabled>
                                    <i class="fas fa-paper-plane"></i> Send
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Notification Modal -->
    <div class="notification-modal" id="notificationModal">
        <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 1rem;">
            <div>
                <h4 style="margin: 0 0 0.5rem 0;">New Chat Request</h4>
                <p id="notificationUserName" style="margin: 0; font-weight: 600;"></p>
                <p id="notificationMessage" style="margin: 0.5rem 0 0 0; color: var(--text-secondary);"></p>
            </div>
            <button onclick="closeNotification()" style="background: none; border: none; font-size: 1.5rem; cursor: pointer;">&times;</button>
        </div>
        <div style="display: flex; gap: 0.5rem;">
            <button class="btn btn-primary" id="acceptChatBtn" style="flex: 1;">
                <i class="fas fa-check"></i> Accept
            </button>
            <button class="btn btn-secondary" onclick="closeNotification()" style="flex: 1;">
                <i class="fas fa-times"></i> Dismiss
            </button>
        </div>
    </div>

    <!-- Firebase SDK -->
    <script src="https://www.gstatic.com/firebasejs/12.7.0/firebase-app.js"></script>
    <script src="https://www.gstatic.com/firebasejs/12.7.0/firebase-database.js"></script>
    <script>
        const firebaseConfig = {
            apiKey: "AIzaSyAvfyPTCsBp0dL76VsEVkiIrIsQkko91os",
            authDomain: "emergencycommunicationsy-eb828.firebaseapp.com",
            databaseURL: "https://emergencycommunicationsy-eb828-default-rtdb.asia-southeast1.firebasedatabase.app",
            projectId: "emergencycommunicationsy-eb828",
            storageBucket: "emergencycommunicationsy-eb828.firebasestorage.app",
            messagingSenderId: "201064241540",
            appId: "1:201064241540:web:4f6d026cd355404ec365d1",
            measurementId: "G-ESQ63CMP9B"
        };
        
        if (!window.firebaseApp) {
            window.firebaseApp = firebase.initializeApp(firebaseConfig);
        }
        window.firebaseDatabase = firebase.database();
    </script>
    
    <script src="js/admin-chat-firebase.js"></script>
    <script>
        let currentQueueId = null;
        let currentConversationId = null;
        let adminChat = null;

        // Initialize Firebase chat
        document.addEventListener('DOMContentLoaded', function() {
            // Wait for Firebase to load
            const initInterval = setInterval(() => {
                if (window.firebase && window.firebaseDatabase) {
                    clearInterval(initInterval);
                    
                    // Initialize admin chat
                    if (window.adminChatFirebase) {
                        window.adminChatFirebase.init().then(() => {
                            adminChat = window.adminChatFirebase;
                            setupEventListeners();
                            loadChatQueue();
                        });
                    } else {
                        setupEventListeners();
                        loadChatQueue();
                    }
                }
            }, 100);
        });

        function setupEventListeners() {
            // New chat notification
            window.addEventListener('newChatNotification', function(e) {
                const { queueId, conversationId, userName, userEmail, userPhone, isGuest, message, userLocation, userConcern } = e.detail;
                showNotification(queueId, conversationId, userName, userEmail, userPhone, isGuest, message, userLocation, userConcern);
            });

            // Chat queue update
            window.addEventListener('chatQueueUpdate', function(e) {
                loadChatQueue();
            });

            // New message received
            window.addEventListener('newMessageReceived', function(e) {
                const { message } = e.detail;
                addMessageToChat(message.text, 'user', message.timestamp);
            });

            // Send button
            document.getElementById('sendButton').addEventListener('click', sendMessage);
            document.getElementById('messageInput').addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    sendMessage();
                }
            });

            // Accept chat button
            document.getElementById('acceptChatBtn').addEventListener('click', function() {
                if (currentQueueId && currentConversationId) {
                    acceptChat(currentQueueId, currentConversationId);
                }
            });
        }

        function loadChatQueue() {
            if (!window.firebaseDatabase) return;

            const queueRef = window.firebaseDatabase.ref('chat_queue').orderByChild('status').equalTo('pending');
            
            queueRef.on('value', (snapshot) => {
                const queueList = document.getElementById('queueList');
                queueList.innerHTML = '';
                
                if (!snapshot.exists()) {
                    queueList.innerHTML = '<p style="text-align: center; color: var(--text-secondary); padding: 2rem;">No pending chats</p>';
                    return;
                }

                const queueItems = snapshot.val();
                Object.keys(queueItems).forEach(queueId => {
                    const item = queueItems[queueId];
                    const queueItem = document.createElement('div');
                    queueItem.className = 'queue-item pending';
                    const guestBadge = item.isGuest ? '<span style="background: #ff9800; color: white; padding: 0.2rem 0.5rem; border-radius: 4px; font-size: 0.7rem; margin-left: 0.5rem;">GUEST</span>' : '';
                    const userInfo = [];
                    if (item.userEmail) userInfo.push(`<i class="fas fa-envelope"></i> ${item.userEmail}`);
                    if (item.userPhone) userInfo.push(`<i class="fas fa-phone"></i> ${item.userPhone}`);
                    if (item.userLocation) userInfo.push(`<i class="fas fa-map-marker-alt"></i> ${item.userLocation}`);
                    const userInfoHtml = userInfo.length > 0 ? `<div style="margin-top: 0.5rem; font-size: 0.85rem; color: var(--text-secondary);">${userInfo.join(' | ')}</div>` : '';
                    const concernBadge = item.userConcern ? `<span style="background: #2196f3; color: white; padding: 0.2rem 0.5rem; border-radius: 4px; font-size: 0.7rem; margin-left: 0.5rem; text-transform: capitalize;">${item.userConcern}</span>` : '';
                    queueItem.innerHTML = `
                        <div class="queue-header">
                            <div>
                                <strong>${item.userName}</strong>${guestBadge}${concernBadge}
                            </div>
                            <span class="queue-badge badge-pending">Pending</span>
                        </div>
                        <p style="margin: 0.5rem 0; font-size: 0.9rem;">${item.message}</p>
                        ${userInfoHtml}
                        <small style="color: var(--text-secondary);">${new Date(item.timestamp).toLocaleString()}</small>
                    `;
                    queueItem.addEventListener('click', () => {
                        selectQueueItem(queueId, item);
                    });
                    queueList.appendChild(queueItem);
                });
            });
        }

        function selectQueueItem(queueId, item) {
            currentQueueId = queueId;
            currentConversationId = item.conversationId;
            
            const userNameEl = document.getElementById('chatUserName');
            const userStatusEl = document.getElementById('chatUserStatus');
            const guestBadge = item.isGuest ? ' <span style="background: #ff9800; color: white; padding: 0.2rem 0.5rem; border-radius: 4px; font-size: 0.7rem;">GUEST</span>' : '';
            const concernBadge = item.userConcern ? ` <span style="background: #2196f3; color: white; padding: 0.2rem 0.5rem; border-radius: 4px; font-size: 0.7rem; text-transform: capitalize;">${item.userConcern}</span>` : '';
            userNameEl.innerHTML = item.userName + guestBadge + concernBadge;
            
            // Also try to get from conversation if not in item
            if (window.firebaseDatabase && item.conversationId) {
                window.firebaseDatabase.ref(`conversations/${item.conversationId}`).once('value', (snapshot) => {
                    const conversation = snapshot.val();
                    if (conversation) {
                        const userInfo = [];
                        if (conversation.userEmail || item.userEmail) userInfo.push(`Email: ${conversation.userEmail || item.userEmail}`);
                        if (conversation.userPhone || item.userPhone) userInfo.push(`Phone: ${conversation.userPhone || item.userPhone}`);
                        if (conversation.userLocation || item.userLocation) userInfo.push(`Location: ${conversation.userLocation || item.userLocation}`);
                        if (conversation.userConcern || item.userConcern) userInfo.push(`Concern: ${conversation.userConcern || item.userConcern}`);
                        if (conversation.userId || item.userId) userInfo.push(`ID: ${conversation.userId || item.userId}`);
                        userStatusEl.textContent = userInfo.length > 0 ? userInfo.join(' | ') : 'Pending';
                    }
                });
            }
            
            const userInfo = [];
            if (item.userEmail) userInfo.push(`Email: ${item.userEmail}`);
            if (item.userPhone) userInfo.push(`Phone: ${item.userPhone}`);
            if (item.userLocation) userInfo.push(`Location: ${item.userLocation}`);
            if (item.userConcern) userInfo.push(`Concern: ${item.userConcern}`);
            if (item.userId) userInfo.push(`ID: ${item.userId}`);
            if (userInfo.length > 0) {
                userStatusEl.textContent = userInfo.join(' | ');
            }
            
            document.getElementById('messageInput').disabled = false;
            document.getElementById('sendButton').disabled = false;
            
            // Load messages
            loadMessages(item.conversationId);
        }

        function loadMessages(conversationId) {
            if (!window.firebaseDatabase) return;

            const messagesRef = window.firebaseDatabase.ref(`messages/${conversationId}`);
            const messagesDiv = document.getElementById('chatMessages');
            messagesDiv.innerHTML = '';

            messagesRef.on('value', (snapshot) => {
                messagesDiv.innerHTML = '';
                if (snapshot.exists()) {
                    const messages = snapshot.val();
                    Object.values(messages).forEach(msg => {
                        addMessageToChat(msg.text, msg.senderType, msg.timestamp);
                    });
                }
            });
        }

        function addMessageToChat(text, senderType, timestamp) {
            const messagesDiv = document.getElementById('chatMessages');
            const messageDiv = document.createElement('div');
            messageDiv.className = `message ${senderType}`;
            
            const time = timestamp ? new Date(timestamp).toLocaleTimeString() : new Date().toLocaleTimeString();
            messageDiv.innerHTML = `
                <div class="message-content">${text}</div>
                <small>${time}</small>
            `;
            messagesDiv.appendChild(messageDiv);
            messagesDiv.scrollTop = messagesDiv.scrollHeight;
        }

        async function acceptChat(queueId, conversationId) {
            if (!window.firebaseDatabase) return;

            try {
                // Update queue status
                await window.firebaseDatabase.ref(`chat_queue/${queueId}`).update({
                    status: 'accepted',
                    acceptedAt: Date.now()
                });

                // Update conversation
                await window.firebaseDatabase.ref(`conversations/${conversationId}`).update({
                    status: 'active',
                    assignedTo: sessionStorage.getItem('admin_id') || 'admin'
                });

                document.getElementById('chatUserStatus').textContent = 'Active';
                closeNotification();
                loadChatQueue();
            } catch (error) {
                console.error('Error accepting chat:', error);
            }
        }

        async function sendMessage() {
            const messageInput = document.getElementById('messageInput');
            const text = messageInput.value.trim();
            if (!text || !currentConversationId) return;

            // Add to UI
            addMessageToChat(text, 'admin', Date.now());
            messageInput.value = '';

            // Send to Firebase
            if (window.firebaseDatabase && adminChat) {
                await adminChat.sendMessage(currentConversationId, text);
            }
        }

        function showNotification(queueId, conversationId, userName, userEmail, userPhone, isGuest, message, userLocation, userConcern) {
            currentQueueId = queueId;
            currentConversationId = conversationId;
            
            const guestBadge = isGuest ? ' <span style="background: #ff9800; color: white; padding: 0.2rem 0.5rem; border-radius: 4px; font-size: 0.7rem;">GUEST</span>' : '';
            const concernBadge = userConcern ? ` <span style="background: #2196f3; color: white; padding: 0.2rem 0.5rem; border-radius: 4px; font-size: 0.7rem; text-transform: capitalize;">${userConcern}</span>` : '';
            document.getElementById('notificationUserName').innerHTML = userName + guestBadge + concernBadge;
            document.getElementById('notificationMessage').textContent = message;
            
            // Show user info if available
            const userInfo = [];
            if (userEmail) userInfo.push(`Email: ${userEmail}`);
            if (userPhone) userInfo.push(`Phone: ${userPhone}`);
            const userLocation = e.detail.userLocation || null;
            const userConcern = e.detail.userConcern || null;
            if (userLocation) userInfo.push(`Location: ${userLocation}`);
            if (userConcern) userInfo.push(`Concern: ${userConcern}`);
            if (userInfo.length > 0) {
                const infoEl = document.createElement('small');
                infoEl.style.display = 'block';
                infoEl.style.marginTop = '0.5rem';
                infoEl.style.color = 'var(--text-secondary)';
                infoEl.textContent = userInfo.join(' | ');
                const notificationMessage = document.getElementById('notificationMessage');
                if (notificationMessage.nextSibling) {
                    notificationMessage.parentNode.insertBefore(infoEl, notificationMessage.nextSibling);
                } else {
                    notificationMessage.parentNode.appendChild(infoEl);
                }
            }
            
            const modal = document.getElementById('notificationModal');
            modal.classList.add('show');
            
            // Auto-close after 10 seconds
            setTimeout(() => {
                closeNotification();
            }, 10000);
        }

        function closeNotification() {
            document.getElementById('notificationModal').classList.remove('show');
        }
    </script>
</body>
</html>

