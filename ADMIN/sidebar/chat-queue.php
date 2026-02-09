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
    <link rel="stylesheet" href="css/global.css?v=<?php echo filemtime(__DIR__ . '/css/global.css'); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/sidebar.css?v=<?php echo filemtime(__DIR__ . '/css/sidebar.css'); ?>">
    <link rel="stylesheet" href="css/admin-header.css">
    <link rel="stylesheet" href="css/buttons.css">
    <link rel="stylesheet" href="css/forms.css">
    <link rel="stylesheet" href="css/modules.css">
        <link rel="stylesheet" href="css/module-chat-queue.css?v=<?php echo filemtime(__DIR__ . '/css/module-chat-queue.css'); ?>">
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    <?php include 'includes/admin-header.php'; ?>
    
    <div class="main-content">
        <div class="main-container">
            <div class="title">
                <nav class="breadcrumb" aria-label="Breadcrumb">
                    <ol class="breadcrumb-list">
                        <li class="breadcrumb-item"><a href="dashboard.php" class="breadcrumb-link">Dashboard</a></li>
                        <li class="breadcrumb-item active">Chat Queue</li>
                    </ol>
                </nav>
                <h1><i class="fas fa-comments" style="color: var(--primary-color-1); margin-right: 0.5rem;"></i> Chat Queue</h1>
                <p>Manage incoming chat requests from users in real-time.</p>
            </div>
            
            <div class="sub-container">
                <div class="page-content">
                    <div class="chat-queue-container">
                        <!-- Queue Sidebar -->
                        <div class="queue-sidebar">
                            <div class="queue-header-area">
                                <h3><i class="fas fa-inbox"></i> Incoming Requests</h3>
                                <span id="queueCount" class="badge" style="background: var(--primary-color-1); color: white;">0</span>
                            </div>
                            <div class="queue-list" id="queueList">
                                <div style="text-align: center; padding: 3rem; opacity: 0.5;">
                                    <i class="fas fa-comment-slash fa-3x" style="margin-bottom: 1rem;"></i>
                                    <p>No pending chats</p>
                                </div>
                            </div>
                        </div>

                        <!-- Chat Window -->
                        <div class="chat-window">
                            <div class="chat-header">
                                <h3 id="chatUserName">Select a chat request</h3>
                                <small id="chatUserStatus" style="color: var(--text-secondary-1); font-weight: 500;"></small>
                            </div>
                            <div class="chat-messages" id="chatMessages">
                                <div style="text-align: center; padding: 4rem; opacity: 0.3; display: flex; flex-direction: column; align-items: center; justify-content: center; height: 100%;">
                                    <i class="fas fa-comments fa-4x" style="margin-bottom: 1rem;"></i>
                                    <p>Select a chat from the queue to start messaging</p>
                                </div>
                            </div>
                            <div class="chat-input-area">
                                <input type="text" id="messageInput" placeholder="Type a response..." disabled>
                                <button class="btn btn-primary" id="sendButton" disabled style="padding: 0.8rem 1.2rem; border-radius: 30px;">
                                    <i class="fas fa-paper-plane"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Notification Toast -->
    <div class="notification-toast" id="notificationModal">
        <div class="notification-header">
            <span class="notification-title"><i class="fas fa-bell"></i> New Chat Request</span>
            <button onclick="closeNotification()" style="background: none; border: none; font-size: 1.25rem; cursor: pointer; color: var(--text-secondary-1);">&times;</button>
        </div>
        <div style="margin-bottom: 1.5rem;">
            <p id="notificationUserName" style="margin: 0; font-weight: 700; color: var(--text-color-1);"></p>
            <p id="notificationMessage" style="margin: 0.5rem 0 0 0; color: var(--text-secondary-1); font-size: 0.9rem; line-height: 1.4;"></p>
        </div>
        <div style="display: flex; gap: 0.75rem;">
            <button class="btn btn-primary" id="acceptChatBtn" style="flex: 2; font-weight: 700;">
                <i class="fas fa-check"></i> Accept Chat
            </button>
            <button class="btn btn-secondary" onclick="closeNotification()" style="flex: 1;">
                Dismiss
            </button>
        </div>
    </div>

    <!-- Firebase SDK - Using compat version for non-module usage -->
    <script src="https://www.gstatic.com/firebasejs/9.22.0/firebase-app-compat.js"></script>
    <script src="https://www.gstatic.com/firebasejs/9.22.0/firebase-database-compat.js"></script>
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
                const queueCount = document.getElementById('queueCount');
                queueList.innerHTML = '';
                
                if (!snapshot.exists()) {
                    queueList.innerHTML = '<div style="text-align: center; padding: 3rem; opacity: 0.5;"><i class="fas fa-comment-slash fa-3x" style="margin-bottom: 1rem;"></i><p>No pending chats</p></div>';
                    queueCount.textContent = '0';
                    return;
                }

                const queueItems = snapshot.val();
                const keys = Object.keys(queueItems);
                queueCount.textContent = keys.length;

                keys.forEach(queueId => {
                    const item = queueItems[queueId];
                    const queueItem = document.createElement('div');
                    queueItem.className = 'queue-item pending';
                    const guestBadge = item.isGuest ? '<span class="badge" style="background: #f39c12; color: white; margin-left: 0.5rem;">GUEST</span>' : '';
                    const concernBadge = item.userConcern ? `<span class="badge" style="background: rgba(33, 150, 243, 0.1); color: #2196f3; margin-left: 0.5rem; border: 1px solid rgba(33, 150, 243, 0.2);">${item.userConcern}</span>` : '';
                    
                    queueItem.innerHTML = `
                        <div class="queue-item-header">
                            <span class="queue-item-name">${item.userName}</span>
                            ${guestBadge}
                        </div>
                        <p style="margin: 0.5rem 0; font-size: 0.85rem; color: var(--text-secondary-1); overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">${item.message}</p>
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 0.75rem;">
                            ${concernBadge}
                            <small style="color: var(--text-secondary-1); font-size: 0.7rem; opacity: 0.7;">${new Date(item.timestamp).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}</small>
                        </div>
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
            const guestBadge = item.isGuest ? ' <span class="badge" style="background: #f39c12; color: white;">GUEST</span>' : '';
            userNameEl.innerHTML = item.userName + guestBadge;
            
            const userInfo = [];
            if (item.userLocation) userInfo.push(item.userLocation);
            if (item.userConcern) userInfo.push(item.userConcern);
            userStatusEl.textContent = userInfo.join(' • ') || 'Awaiting acceptance';
            
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
            
            const time = timestamp ? new Date(timestamp).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'}) : new Date().toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
            messageDiv.innerHTML = `
                <div class="message-content">${text}</div>
                <span class="message-time">${time}</span>
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

                document.getElementById('chatUserStatus').textContent = 'Conversation Active';
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
            
            document.getElementById('notificationUserName').innerHTML = userName + (isGuest ? ' (Guest)' : '');
            document.getElementById('notificationMessage').textContent = message;
            
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

