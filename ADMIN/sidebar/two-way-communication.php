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

$pageTitle = 'Two-Way Communication Interface';
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
    <link rel="stylesheet" href="css/hero.css">
    <link rel="stylesheet" href="css/sidebar-footer.css">
    <link rel="stylesheet" href="css/modules.css">
    <style>
        .communication-container {
            display: grid;
            grid-template-columns: 300px 1fr;
            gap: 1.5rem;
            height: calc(100vh - 300px);
        }
        .conversations-list {
            background: var(--card-bg);
            border-radius: 8px;
            padding: 1rem;
            overflow-y: auto;
        }
        .conversation-item {
            padding: 1rem;
            border-bottom: 1px solid var(--border-color);
            cursor: pointer;
            transition: background 0.2s;
        }
        .conversation-item:hover {
            background: var(--hover-bg);
        }
        .conversation-item.active {
            background: var(--primary-color);
            color: white;
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
        .message.sent {
            align-self: flex-end;
            flex-direction: row-reverse;
        }
        .message.received {
            align-self: flex-start;
        }
        .message-content {
            padding: 0.75rem 1rem;
            border-radius: 12px;
            background: var(--border-color);
        }
        .message.sent .message-content {
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
    </style>
</head>
<body>
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
                <nav class="breadcrumb" aria-label="Breadcrumb">
                    <ol class="breadcrumb-list">
                        <li class="breadcrumb-item">
                            <a href="/" class="breadcrumb-link">
                                <span>Home</span>
                            </a>
                        </li>
                        <li class="breadcrumb-item">
                            <a href="/emergency-communication" class="breadcrumb-link">
                                <span>Emergency Communication</span>
                            </a>
                        </li>
                        <li class="breadcrumb-item active" aria-current="page">
                            <span>Two-Way Communication</span>
                        </li>
                    </ol>
                </nav>
                <h1>Two-Way Communication Interface</h1>
                <p>Interactive communication platform allowing administrators and citizens to exchange messages in real-time.</p>
                <div class="info-box" style="background: #e3f2fd; border-left: 4px solid #2196f3; padding: 1rem; border-radius: 4px; margin-top: 1rem;">
                    <i class="fas fa-info-circle" style="color: #2196f3;"></i>
                    <strong>How to use:</strong> Click on a conversation from the left panel to view messages. Type your response in the message box and click "Send" to reply to citizens.
                </div>
            </div>
            
            <div class="sub-container">
                <div class="page-content">
                    <div class="communication-container">
                        <!-- Conversations List -->
                        <div class="conversations-list">
                            <h3 style="margin-bottom: 1rem;">Conversations</h3>
                            <div id="conversationsList">
                                <!-- Conversations will be loaded here -->
                            </div>
                        </div>

                        <!-- Chat Window -->
                        <div class="chat-window">
                            <div class="chat-header">
                                <h3 id="chatUserName">Select a conversation</h3>
                                <small id="chatUserStatus"></small>
                            </div>
                            <div class="chat-messages" id="chatMessages">
                                <p style="text-align: center; color: var(--text-secondary); padding: 2rem;">
                                    Select a conversation to start messaging
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
        const database = firebase.database();
        
        let currentConversationId = null;
        let messagesListener = null;

        function loadConversations() {
            const conversationsRef = database.ref('conversations');
            const list = document.getElementById('conversationsList');
            
            console.log('Loading conversations...');
            
            conversationsRef.on('value', (snapshot) => {
                list.innerHTML = '';
                
                if (!snapshot.exists()) {
                    console.log('No conversations found');
                    list.innerHTML = '<p style="text-align: center; color: var(--text-secondary); padding: 2rem;">No conversations yet</p>';
                    return;
                }
                
                const conversations = snapshot.val();
                console.log('Found conversations:', Object.keys(conversations).length);
                
                // Sort by updatedAt (most recent first), fallback to createdAt
                const sortedConversations = Object.entries(conversations)
                    .sort((a, b) => {
                        const timeA = a[1].updatedAt || a[1].createdAt || 0;
                        const timeB = b[1].updatedAt || b[1].createdAt || 0;
                        return timeB - timeA;
                    });
                
                sortedConversations.forEach(([convId, conv]) => {
                    const item = document.createElement('div');
                    item.className = 'conversation-item';
                    const guestBadge = conv.isGuest ? '<span style="background: #ff9800; color: white; padding: 0.2rem 0.5rem; border-radius: 4px; font-size: 0.7rem; margin-left: 0.5rem;">GUEST</span>' : '';
                    const concernBadge = conv.userConcern ? `<span style="background: #2196f3; color: white; padding: 0.2rem 0.5rem; border-radius: 4px; font-size: 0.7rem; margin-left: 0.5rem; text-transform: capitalize;">${conv.userConcern}</span>` : '';
                    const userInfo = [];
                    if (conv.userPhone) userInfo.push(`<i class="fas fa-phone"></i> ${conv.userPhone}`);
                    if (conv.userLocation) userInfo.push(`<i class="fas fa-map-marker-alt"></i> ${conv.userLocation}`);
                    const userInfoHtml = userInfo.length > 0 ? `<div style="margin-top: 0.5rem; font-size: 0.8rem; color: var(--text-secondary);">${userInfo.join(' | ')}</div>` : '';
                    item.innerHTML = `
                        <div style="display: flex; align-items: center; margin-bottom: 0.5rem;">
                            <strong>${conv.userName || 'Unknown User'}</strong>${guestBadge}${concernBadge}
                        </div>
                        <p style="margin: 0.5rem 0 0 0; font-size: 0.9rem; color: var(--text-secondary);">
                            ${conv.lastMessage || 'No messages yet'}
                        </p>
                        ${userInfoHtml}
                        <small style="color: var(--text-secondary); display: block; margin-top: 0.5rem;">
                            ${conv.lastMessageTime ? new Date(conv.lastMessageTime).toLocaleString() : ''}
                        </small>
                    `;
                    item.addEventListener('click', function() {
                        openConversation(convId, conv, this);
                    });
                    list.appendChild(item);
                });
            });
        }

        function openConversation(conversationId, conversation, element) {
            currentConversationId = conversationId;
            const userName = typeof conversation === 'string' ? conversation : (conversation.userName || 'Unknown User');
            const userNameEl = document.getElementById('chatUserName');
            const userStatusEl = document.getElementById('chatUserStatus');
            
            const guestBadge = (typeof conversation === 'object' && conversation.isGuest) ? ' <span style="background: #ff9800; color: white; padding: 0.2rem 0.5rem; border-radius: 4px; font-size: 0.7rem;">GUEST</span>' : '';
            const concernBadge = (typeof conversation === 'object' && conversation.userConcern) ? ` <span style="background: #2196f3; color: white; padding: 0.2rem 0.5rem; border-radius: 4px; font-size: 0.7rem; text-transform: capitalize;">${conversation.userConcern}</span>` : '';
            userNameEl.innerHTML = userName + guestBadge + concernBadge;
            
            // Show user info
            const userInfo = [];
            if (typeof conversation === 'object') {
                if (conversation.userEmail) userInfo.push(`Email: ${conversation.userEmail}`);
                if (conversation.userPhone) userInfo.push(`Phone: ${conversation.userPhone}`);
                if (conversation.userLocation) userInfo.push(`Location: ${conversation.userLocation}`);
                if (conversation.userConcern) userInfo.push(`Concern: ${conversation.userConcern}`);
            }
            userStatusEl.textContent = userInfo.length > 0 ? userInfo.join(' | ') : 'Online';
            
            document.getElementById('messageInput').disabled = false;
            document.getElementById('sendButton').disabled = false;
            
            // Update active conversation
            document.querySelectorAll('.conversation-item').forEach(item => {
                item.classList.remove('active');
            });
            if (element) {
                element.classList.add('active');
            }
            
            loadMessages(conversationId);
        }

        function loadMessages(conversationId) {
            // Remove previous listener
            if (messagesListener) {
                messagesListener.off();
            }
            
            const messagesRef = database.ref(`messages/${conversationId}`);
            const messagesDiv = document.getElementById('chatMessages');
            messagesDiv.innerHTML = '';
            
            const loadedMessageIds = new Set();
            
            // Load existing messages
            messagesRef.once('value', (snapshot) => {
                if (snapshot.exists()) {
                    const messages = snapshot.val();
                    // Sort messages by timestamp
                    const sortedMessages = Object.entries(messages).sort((a, b) => {
                        const timeA = a[1].timestamp || 0;
                        const timeB = b[1].timestamp || 0;
                        return timeA - timeB;
                    });
                    
                    sortedMessages.forEach(([msgId, msg]) => {
                        loadedMessageIds.add(msgId);
                        addMessageToChat(msg.text, msg.senderType, msg.timestamp);
                    });
                }
            });
            
            // Listen for new messages (real-time)
            messagesListener = messagesRef.on('child_added', (snapshot) => {
                const messageId = snapshot.key;
                if (!loadedMessageIds.has(messageId)) {
                    loadedMessageIds.add(messageId);
                    const message = snapshot.val();
                    addMessageToChat(message.text, message.senderType, message.timestamp);
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

        document.getElementById('sendButton').addEventListener('click', sendMessage);
        document.getElementById('messageInput').addEventListener('keypress', function(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                sendMessage();
            }
        });

        async function sendMessage() {
            const messageInput = document.getElementById('messageInput');
            const message = messageInput.value.trim();
            if (!message || !currentConversationId) return;

            try {
                // Add to UI immediately
                addMessageToChat(message, 'admin', Date.now());
                messageInput.value = '';

                // Send to Firebase
                await database.ref(`messages/${currentConversationId}`).push({
                    text: message,
                    senderId: 'admin',
                    senderName: 'Admin',
                    senderType: 'admin',
                    timestamp: firebase.database.ServerValue.TIMESTAMP,
                    read: false
                });

                // Update conversation
                await database.ref(`conversations/${currentConversationId}`).update({
                    lastMessage: message,
                    lastMessageTime: firebase.database.ServerValue.TIMESTAMP,
                    updatedAt: firebase.database.ServerValue.TIMESTAMP,
                    status: 'active'
                });
            } catch (error) {
                console.error('Error sending message:', error);
                alert('Error sending message. Please try again.');
            }
        }

        // Load conversations on page load
        document.addEventListener('DOMContentLoaded', loadConversations);
    </script>
</body>
</html>

