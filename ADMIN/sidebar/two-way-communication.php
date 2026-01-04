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
$adminUsername = $_SESSION['admin_username'] ?? 'Admin';
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
            background: var(--card-bg-1);
            border-radius: 12px;
            padding: 1rem;
            overflow-y: auto;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }
        .conversations-list::-webkit-scrollbar {
            width: 6px;
        }
        .conversations-list::-webkit-scrollbar-track {
            background: transparent;
        }
        .conversations-list::-webkit-scrollbar-thumb {
            background: var(--border-color-1);
            border-radius: 3px;
        }
        .conversations-list::-webkit-scrollbar-thumb:hover {
            background: var(--text-secondary-1);
        }
        .conversation-item {
            padding: 1rem;
            border-bottom: 1px solid var(--border-color-1);
            cursor: pointer;
            transition: all 0.2s ease;
            border-radius: 8px;
            margin-bottom: 0.5rem;
            color: var(--text-color-1);
        }
        .conversation-item:hover {
            background: rgba(76, 138, 137, 0.1);
            transform: translateX(4px);
        }
        [data-theme="dark"] .conversation-item:hover {
            background: rgba(76, 138, 137, 0.2);
        }
        .conversation-item.active {
            background: var(--primary-color-1);
            color: white;
            box-shadow: 0 2px 8px rgba(76, 138, 137, 0.3);
        }
        .chat-window {
            display: flex;
            flex-direction: column;
            background: var(--card-bg-1);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }
        .chat-header {
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid var(--border-color-1);
            background: var(--primary-color-1);
            color: white;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }
        .chat-header h3 {
            margin: 0;
            font-size: 1.1rem;
            font-weight: 600;
        }
        .chat-header small {
            display: block;
            margin-top: 0.25rem;
            opacity: 0.9;
            font-size: 0.85rem;
        }
        .chat-messages {
            flex: 1;
            padding: 1.5rem;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            background: linear-gradient(to bottom, var(--bg-color-1) 0%, var(--card-bg-1) 100%);
            scroll-behavior: smooth;
        }
        .chat-messages::-webkit-scrollbar {
            width: 6px;
        }
        .chat-messages::-webkit-scrollbar-track {
            background: transparent;
        }
        .chat-messages::-webkit-scrollbar-thumb {
            background: var(--border-color-1);
            border-radius: 3px;
        }
        .chat-messages::-webkit-scrollbar-thumb:hover {
            background: var(--text-secondary-1);
        }
        .message {
            display: flex;
            gap: 0.75rem;
            max-width: 75%;
            margin-bottom: 1rem;
            animation: fadeIn 0.3s ease-in;
        }
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        .message.admin {
            align-self: flex-end;
            flex-direction: row-reverse;
        }
        .message.user {
            align-self: flex-start;
        }
        .message-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            flex-shrink: 0;
            object-fit: cover;
            border: 2px solid var(--border-color-1);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        .message.admin .message-avatar {
            border-color: var(--primary-color-1);
        }
        .message-content-wrapper {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }
        .message-content {
            padding: 0.875rem 1.125rem;
            border-radius: 18px;
            background: var(--border-color-1);
            color: var(--text-color-1);
            word-wrap: break-word;
            line-height: 1.5;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
            position: relative;
        }
        .message.admin .message-content {
            background: var(--primary-color-1);
            color: white;
            border-bottom-right-radius: 4px;
        }
        .message.user .message-content {
            background: var(--card-bg-1);
            color: var(--text-color-1);
            border: 1px solid var(--border-color-1);
            border-bottom-left-radius: 4px;
        }
        /* Ensure text visibility in dark mode for user messages */
        [data-theme="dark"] .message.user .message-content {
            background: var(--card-bg-1);
            color: var(--text-color-1);
            border-color: var(--border-color-1);
        }
        /* Ensure text visibility in light mode for user messages */
        [data-theme="light"] .message.user .message-content {
            background: #f5f5f5;
            color: #171717;
            border-color: var(--border-color-1);
        }
        .message-time {
            font-size: 0.75rem;
            color: var(--text-secondary-1);
            padding: 0 0.5rem;
            margin-top: 0.25rem;
        }
        .message.admin .message-time {
            text-align: right;
        }
        .message.user .message-time {
            text-align: left;
        }
        .chat-input {
            padding: 1.25rem;
            border-top: 1px solid var(--border-color-1);
            display: flex;
            gap: 0.75rem;
            background: var(--card-bg-1);
            align-items: center;
        }
        .chat-input input {
            flex: 1;
            padding: 0.875rem 1.125rem;
            border: 2px solid var(--border-color-1);
            border-radius: 24px;
            background: var(--bg-color-1);
            color: var(--text-color-1);
            font-size: 0.95rem;
            transition: all 0.2s ease;
        }
        .chat-input input:focus {
            outline: none;
            border-color: var(--primary-color-1);
            box-shadow: 0 0 0 3px rgba(76, 138, 137, 0.1);
        }
        .chat-input input:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        .chat-input .btn {
            border-radius: 24px;
            padding: 0.875rem 1.5rem;
            font-weight: 500;
            transition: all 0.2s ease;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        .chat-input .btn:hover:not(:disabled) {
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
        }
        .chat-input .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
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
                                <p style="text-align: center; color: var(--text-secondary-1); padding: 2rem;">
                                    Select a conversation to start messaging
                                </p>
                            </div>
                            <div class="chat-input">
                                <input type="text" id="messageInput" placeholder="Type a message..." disabled>
                                <button class="btn btn-primary" id="sendButton" disabled>
                                    <i class="fas fa-paper-plane"></i> Send
                                </button>
                                <button class="btn btn-secondary" id="closeConversationBtn" style="display: none;" title="Close this conversation">
                                    <i class="fas fa-times"></i> Close
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- MySQL Chat System -->
    <script>
        const API_BASE = '../api/';
        const ADMIN_USERNAME = <?php echo json_encode($adminUsername); ?>;
        const ADMIN_AVATAR = `https://ui-avatars.com/api/?name=${encodeURIComponent(ADMIN_USERNAME)}&background=4c8a89&color=fff&size=128`;
        let currentConversationId = null;
        let lastMessageId = 0;
        let conversationPollingInterval = null;
        let messagePollingInterval = null;

        async function loadConversations(isInitialLoad = false) {
            const list = document.getElementById('conversationsList');
            
            // Only show loading on initial load
            if (isInitialLoad) {
                const existingItems = list.querySelectorAll('.conversation-item');
                if (existingItems.length === 0) {
                    list.innerHTML = '<p style="text-align: center; color: var(--text-secondary-1); padding: 2rem;">Loading...</p>';
                }
            }
            
            console.log('Loading conversations from MySQL...');
            
            try {
                const response = await fetch(API_BASE + 'chat-get-conversations.php?status=active');
                const data = await response.json();
                
                if (!data.success || !data.conversations || data.conversations.length === 0) {
                    // Only show "No conversations" on initial load
                    if (isInitialLoad) {
                        list.innerHTML = '<p style="text-align: center; color: var(--text-secondary-1); padding: 2rem;">No conversations yet</p>';
                    }
                    return;
                }
                
                console.log('Found conversations:', data.conversations.length);
                
                // Track existing conversation IDs
                const existingIds = new Set();
                const existingItems = list.querySelectorAll('.conversation-item');
                existingItems.forEach(item => {
                    const convId = item.getAttribute('data-conversation-id');
                    if (convId) {
                        existingIds.add(convId);
                    }
                });
                
                // Remove loading message if it exists
                const loadingMsg = list.querySelector('p');
                if (loadingMsg && loadingMsg.textContent.includes('Loading')) {
                    loadingMsg.remove();
                }
                
                // Track which conversations we've processed in this update
                const processedIds = new Set();
                
                // Update or create conversation items
                data.conversations.forEach(conv => {
                    const convId = String(conv.id || conv.conversation_id);
                    processedIds.add(convId);
                    
                    let item = list.querySelector(`.conversation-item[data-conversation-id="${convId}"]`);
                    
                    if (!item) {
                        // Create new item
                        item = document.createElement('div');
                        item.className = 'conversation-item';
                        item.setAttribute('data-conversation-id', convId);
                        item.style.cursor = 'pointer';
                        item.style.pointerEvents = 'auto';
                        item.style.touchAction = 'manipulation';
                        
                        // Add click handler
                        item.addEventListener('click', function(e) {
                            e.preventDefault();
                            e.stopPropagation();
                            e.stopImmediatePropagation();
                            console.log('Conversation clicked:', convId, conv);
                            openConversation(convId, conv, this);
                            return false;
                        });
                        
                        // Also handle touch events
                        item.addEventListener('touchend', function(e) {
                            e.preventDefault();
                            e.stopPropagation();
                            console.log('Conversation touched:', convId);
                            openConversation(convId, conv, this);
                        }, { passive: false });
                        
                        list.appendChild(item);
                    }
                    
                    // Update item content (only if changed to avoid flicker)
                    const guestBadge = conv.isGuest ? '<span style="background: #ff9800; color: white; padding: 0.2rem 0.5rem; border-radius: 4px; font-size: 0.7rem; margin-left: 0.5rem;">GUEST</span>' : '';
                    const concernBadge = conv.userConcern ? `<span style="background: #2196f3; color: white; padding: 0.2rem 0.5rem; border-radius: 4px; font-size: 0.7rem; margin-left: 0.5rem; text-transform: capitalize;">${conv.userConcern}</span>` : '';
                    const userInfo = [];
                    if (conv.userPhone) userInfo.push(`<i class="fas fa-phone"></i> ${conv.userPhone}`);
                    if (conv.userLocation) userInfo.push(`<i class="fas fa-map-marker-alt"></i> ${conv.userLocation}`);
                    const userInfoHtml = userInfo.length > 0 ? `<div style="margin-top: 0.5rem; font-size: 0.8rem; color: var(--text-secondary-1);">${userInfo.join(' | ')}</div>` : '';
                    
                    const newContent = `
                        <div style="display: flex; align-items: center; margin-bottom: 0.5rem;">
                            <strong>${conv.userName || 'Unknown User'}</strong>${guestBadge}${concernBadge}
                        </div>
                        <p style="margin: 0.5rem 0 0 0; font-size: 0.9rem; color: var(--text-secondary-1);">
                            ${conv.lastMessage || 'No messages yet'}
                        </p>
                        ${userInfoHtml}
                        <small style="color: var(--text-secondary-1); display: block; margin-top: 0.5rem;">
                            ${conv.lastMessageTime ? new Date(conv.lastMessageTime).toLocaleString() : ''}
                        </small>
                    `;
                    
                    // Only update if content changed
                    if (item.innerHTML.trim() !== newContent.trim()) {
                        item.innerHTML = newContent;
                    }
                });
                
                // Remove conversations that no longer exist (only if not currently active)
                if (!isInitialLoad) {
                    existingItems.forEach(item => {
                        const convId = item.getAttribute('data-conversation-id');
                        if (convId && !processedIds.has(convId)) {
                            // Don't remove if it's the active conversation
                            if (!item.classList.contains('active')) {
                                item.remove();
                            }
                        }
                    });
                }
                
                // Start polling for new conversations (only if not already polling)
                if (!conversationPollingInterval) {
                    conversationPollingInterval = setInterval(() => {
                        loadConversations(false); // Not initial load
                    }, 5000); // Poll every 5 seconds
                }
            } catch (error) {
                console.error('Error loading conversations:', error);
                // Only show error on initial load
                if (isInitialLoad) {
                    list.innerHTML = '<p style="text-align: center; color: red; padding: 2rem;">Error loading conversations</p>';
                }
            }
        }

        function openConversation(conversationId, conversation, element) {
            console.log('Opening conversation:', conversationId, conversation);
            currentConversationId = conversationId;
            lastMessageId = 0; // Reset message ID when opening new conversation
            
            const userName = typeof conversation === 'string' ? conversation : (conversation.userName || 'Unknown User');
            const userNameEl = document.getElementById('chatUserName');
            const userStatusEl = document.getElementById('chatUserStatus');
            
            if (!userNameEl || !userStatusEl) {
                console.error('Chat header elements not found');
                return;
            }
            
            const guestBadge = (typeof conversation === 'object' && conversation.isGuest) ? ' <span style="background: #ff9800; color: white; padding: 0.2rem 0.5rem; border-radius: 4px; font-size: 0.7rem;">GUEST</span>' : '';
            const concernBadge = (typeof conversation === 'object' && conversation.userConcern) ? ` <span style="background: #2196f3; color: white; padding: 0.2rem 0.5rem; border-radius: 4px; font-size: 0.7rem; text-transform: capitalize;">${conversation.userConcern}</span>` : '';
            userNameEl.innerHTML = userName + guestBadge + concernBadge;
            
            // Show user info including device and IP
            const userInfo = [];
            if (typeof conversation === 'object') {
                if (conversation.userEmail) userInfo.push(`Email: ${conversation.userEmail}`);
                if (conversation.userPhone) userInfo.push(`Phone: ${conversation.userPhone}`);
                if (conversation.userLocation) userInfo.push(`Location: ${conversation.userLocation}`);
                if (conversation.userConcern) userInfo.push(`Concern: ${conversation.userConcern}`);
                
                // Add device info
                if (conversation.deviceInfo) {
                    const deviceParts = [];
                    if (conversation.deviceInfo.device_type) deviceParts.push(conversation.deviceInfo.device_type);
                    if (conversation.deviceInfo.os) deviceParts.push(conversation.deviceInfo.os);
                    if (conversation.deviceInfo.browser) deviceParts.push(conversation.deviceInfo.browser);
                    if (deviceParts.length > 0) {
                        userInfo.push(`Device: ${deviceParts.join(' - ')}`);
                    }
                }
                
                // Add IP address
                if (conversation.ipAddress) {
                    userInfo.push(`IP: ${conversation.ipAddress}`);
                }
            }
            userStatusEl.textContent = userInfo.length > 0 ? userInfo.join(' | ') : 'Online';
            
            const messageInput = document.getElementById('messageInput');
            const sendButton = document.getElementById('sendButton');
            const closeBtn = document.getElementById('closeConversationBtn');
            
            // Check if conversation is closed
            const isClosed = typeof conversation === 'object' && conversation.status === 'closed';
            
            if (messageInput) {
                messageInput.disabled = isClosed;
                messageInput.style.pointerEvents = isClosed ? 'none' : 'auto';
                messageInput.style.cursor = isClosed ? 'not-allowed' : 'text';
                if (isClosed) {
                    messageInput.placeholder = 'This conversation is closed';
                } else {
                    messageInput.placeholder = 'Type a message...';
                }
            }
            
            if (sendButton) {
                sendButton.disabled = isClosed;
                sendButton.style.pointerEvents = isClosed ? 'none' : 'auto';
                sendButton.style.cursor = isClosed ? 'not-allowed' : 'pointer';
            }
            
            // Show/hide close button based on status
            if (closeBtn) {
                if (isClosed) {
                    closeBtn.style.display = 'inline-flex';
                    closeBtn.disabled = true;
                    closeBtn.innerHTML = '<i class="fas fa-check"></i> Closed';
                } else {
                    closeBtn.style.display = 'inline-flex';
                    closeBtn.disabled = false;
                    closeBtn.innerHTML = '<i class="fas fa-times"></i> Close';
                    attachCloseButtonHandler();
                }
            }
            
            // Update active conversation
            document.querySelectorAll('.conversation-item').forEach(item => {
                item.classList.remove('active');
            });
            if (element) {
                element.classList.add('active');
            }
            
            // Load messages (initial load)
            lastMessageId = 0; // Reset for new conversation
            loadMessages(conversationId, true);
        }
        
        function attachCloseButtonHandler() {
            const closeBtn = document.getElementById('closeConversationBtn');
            if (!closeBtn) return;
            
            // Remove old listeners
            const newCloseBtn = closeBtn.cloneNode(true);
            closeBtn.parentNode.replaceChild(newCloseBtn, closeBtn);
            const freshCloseBtn = document.getElementById('closeConversationBtn');
            
            if (!freshCloseBtn) return;
            
            freshCloseBtn.style.display = 'inline-flex';
            freshCloseBtn.style.pointerEvents = 'auto';
            freshCloseBtn.style.cursor = 'pointer';
            freshCloseBtn.disabled = false;
            
            freshCloseBtn.onclick = async function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                if (!confirm('Are you sure you want to close this conversation? The user will not be able to send messages after closing.')) {
                    return false;
                }
                
                try {
                    freshCloseBtn.disabled = true;
                    freshCloseBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Closing...';
                    
                    const response = await fetch(API_BASE + 'chat-close.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            conversationId: currentConversationId
                        })
                    });
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        alert('Conversation closed successfully.');
                        // Disable input and send button
                        const messageInput = document.getElementById('messageInput');
                        const sendButton = document.getElementById('sendButton');
                        if (messageInput) {
                            messageInput.disabled = true;
                            messageInput.placeholder = 'This conversation is closed';
                        }
                        if (sendButton) {
                            sendButton.disabled = true;
                        }
                        freshCloseBtn.disabled = true;
                        freshCloseBtn.innerHTML = '<i class="fas fa-check"></i> Closed';
                        
                        // Reload conversations to update status
                        loadConversations(false);
                    } else {
                        alert('Failed to close conversation: ' + (data.message || 'Unknown error'));
                        freshCloseBtn.disabled = false;
                        freshCloseBtn.innerHTML = '<i class="fas fa-times"></i> Close';
                    }
                } catch (error) {
                    console.error('Error closing conversation:', error);
                    alert('Error closing conversation. Please try again.');
                    freshCloseBtn.disabled = false;
                    freshCloseBtn.innerHTML = '<i class="fas fa-times"></i> Close';
                }
                
                return false;
            };
        }

        async function loadMessages(conversationId, isInitialLoad = false) {
            const messagesDiv = document.getElementById('chatMessages');
            if (!messagesDiv) {
                console.error('Messages div not found');
                return;
            }
            
            // Only show loading on initial load when there are no messages
            if (isInitialLoad && messagesDiv.querySelectorAll('.message').length === 0) {
                const placeholder = messagesDiv.querySelector('p');
                if (!placeholder || !placeholder.textContent.includes('Select a conversation')) {
                    messagesDiv.innerHTML = '<p style="text-align: center; color: var(--text-secondary-1); padding: 2rem;">Loading messages...</p>';
                }
            }
            
            try {
                const response = await fetch(API_BASE + 'chat-get-messages.php?' + new URLSearchParams({
                    conversationId: conversationId,
                    lastMessageId: lastMessageId
                }));
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const data = await response.json();
                
                if (data.success && data.messages && data.messages.length > 0) {
                    // Remove loading/placeholder messages only on initial load
                    if (isInitialLoad) {
                        const loadingMsg = messagesDiv.querySelector('p');
                        if (loadingMsg && (loadingMsg.textContent.includes('Loading') || loadingMsg.textContent.includes('Select a conversation'))) {
                            loadingMsg.remove();
                        }
                    }
                    
                    // Track existing message IDs to avoid duplicates
                    const existingIds = new Set();
                    messagesDiv.querySelectorAll('.message').forEach(msg => {
                        const msgId = msg.getAttribute('data-message-id');
                        if (msgId) {
                            existingIds.add(parseInt(msgId));
                        }
                    });
                    
                    let newMessagesAdded = false;
                    data.messages.forEach(msg => {
                        // Only add if message ID is greater than lastMessageId and not already displayed
                        if (msg.id > lastMessageId && !existingIds.has(msg.id)) {
                            existingIds.add(msg.id);
                            addMessageToChat(msg.text, msg.senderType, msg.timestamp, msg.id, msg.senderName);
                            lastMessageId = Math.max(lastMessageId, msg.id);
                            newMessagesAdded = true;
                        }
                    });
                    
                    // Only scroll if new messages were added
                    if (newMessagesAdded) {
                        messagesDiv.scrollTop = messagesDiv.scrollHeight;
                    }
                    
                    // Start polling for new messages (only if not already polling)
                    if (!messagePollingInterval && currentConversationId) {
                        messagePollingInterval = setInterval(() => {
                            if (currentConversationId) {
                                loadMessages(currentConversationId, false); // Not initial load
                            }
                        }, 2000); // Poll every 2 seconds
                    }
                } else if (data.success && (!data.messages || data.messages.length === 0)) {
                    // Only show "No messages" on initial load if div is empty
                    if (isInitialLoad && messagesDiv.querySelectorAll('.message').length === 0) {
                        const loadingMsg = messagesDiv.querySelector('p');
                        if (loadingMsg && loadingMsg.textContent.includes('Loading')) {
                            loadingMsg.textContent = 'No messages yet';
                            loadingMsg.style.color = 'var(--text-secondary-1)';
                        } else if (!loadingMsg) {
                            messagesDiv.innerHTML = '<p style="text-align: center; color: var(--text-secondary-1); padding: 2rem;">No messages yet</p>';
                        }
                    }
                } else {
                    console.error('Failed to load messages:', data.message);
                }
            } catch (error) {
                console.error('Error loading messages:', error);
                // Only show error on initial load if no messages exist
                if (isInitialLoad && messagesDiv.querySelectorAll('.message').length === 0) {
                    const loadingMsg = messagesDiv.querySelector('p');
                    if (loadingMsg && loadingMsg.textContent.includes('Loading')) {
                        loadingMsg.textContent = 'Error loading messages';
                        loadingMsg.style.color = 'red';
                    }
                }
            }
        }

        function addMessageToChat(text, senderType, timestamp, messageId = null, senderName = null) {
            const messagesDiv = document.getElementById('chatMessages');
            
            // Remove placeholder messages only if this is a new message being added
            const placeholders = messagesDiv.querySelectorAll('p');
            placeholders.forEach(p => {
                if (p.textContent.includes('Select a conversation') || (p.textContent.includes('Loading') && messagesDiv.querySelectorAll('.message').length > 0)) {
                    p.remove();
                }
            });
            
            // Check if message already exists (by ID or by content+time)
            if (messageId) {
                const existing = messagesDiv.querySelector(`.message[data-message-id="${messageId}"]`);
                if (existing) {
                    return; // Message already exists, don't add again
                }
            }
            
            const messageDiv = document.createElement('div');
            // Normalize senderType: 'admin' maps to 'admin', everything else maps to 'user'
            const normalizedType = (senderType === 'admin' || senderType === 'sent') ? 'admin' : 'user';
            messageDiv.className = `message ${normalizedType}`;
            if (messageId) {
                messageDiv.setAttribute('data-message-id', messageId);
            }
            
            // Escape HTML to prevent XSS
            const escapeHtml = (str) => {
                const div = document.createElement('div');
                div.textContent = str;
                return div.innerHTML;
            };
            
            // Format time
            const time = timestamp ? new Date(timestamp).toLocaleTimeString('en-US', { 
                hour: 'numeric', 
                minute: '2-digit',
                hour12: true 
            }) : new Date().toLocaleTimeString('en-US', { 
                hour: 'numeric', 
                minute: '2-digit',
                hour12: true 
            });
            
            // Get avatar - admin uses ADMIN_AVATAR, user uses sender name or conversation name
            let avatarUrl;
            let avatarName;
            if (normalizedType === 'admin') {
                avatarUrl = ADMIN_AVATAR;
                avatarName = ADMIN_USERNAME;
            } else {
                // Use senderName from API if available, otherwise try to get from chat header
                if (senderName) {
                    avatarName = senderName;
                } else {
                    const chatUserName = document.getElementById('chatUserName');
                    avatarName = chatUserName ? chatUserName.textContent.replace(/GUEST|Emergency/gi, '').trim() : 'User';
                }
                avatarUrl = `https://ui-avatars.com/api/?name=${encodeURIComponent(avatarName)}&background=6c757d&color=fff&size=128`;
            }
            
            messageDiv.innerHTML = `
                <img src="${avatarUrl}" alt="${escapeHtml(avatarName)}" class="message-avatar">
                <div class="message-content-wrapper">
                    <div class="message-content">${escapeHtml(text)}</div>
                    <div class="message-time">${time}</div>
                </div>
            `;
            messagesDiv.appendChild(messageDiv);
            messagesDiv.scrollTop = messagesDiv.scrollHeight;
        }

        // Attach send button handlers with better event handling
        const sendButton = document.getElementById('sendButton');
        const messageInput = document.getElementById('messageInput');
        
        if (sendButton) {
            // Ensure button is clickable
            sendButton.style.pointerEvents = 'auto';
            sendButton.style.cursor = 'pointer';
            sendButton.style.touchAction = 'manipulation';
            sendButton.disabled = false;
            
            // Remove old listeners by cloning
            const newBtn = sendButton.cloneNode(true);
            sendButton.parentNode.replaceChild(newBtn, sendButton);
            const freshBtn = document.getElementById('sendButton');
            
            if (freshBtn) {
                freshBtn.style.pointerEvents = 'auto';
                freshBtn.style.cursor = 'pointer';
                freshBtn.style.touchAction = 'manipulation';
                freshBtn.disabled = false;
                
                freshBtn.onclick = function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    console.log('Admin send button clicked');
                    sendMessage();
                    return false;
                };
                
                freshBtn.addEventListener('touchend', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    console.log('Admin send button touched');
                    sendMessage();
                }, { passive: false });
            }
        }
        
        if (messageInput) {
            messageInput.style.pointerEvents = 'auto';
            messageInput.style.cursor = 'text';
            
            messageInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    e.stopPropagation();
                    console.log('Admin Enter key pressed');
                    sendMessage();
                }
            });
            
            messageInput.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                }
            });
        }

        async function sendMessage() {
            const messageInput = document.getElementById('messageInput');
            const message = messageInput.value.trim();
            
            if (!message) {
                console.warn('Cannot send: message is empty');
                return;
            }
            
            if (!currentConversationId) {
                console.warn('Cannot send: no conversation selected');
                alert('Please select a conversation first.');
                return;
            }

            try {
                // Disable input and button while sending
                messageInput.disabled = true;
                const sendBtn = document.getElementById('sendButton');
                if (sendBtn) {
                    sendBtn.disabled = true;
                    sendBtn.textContent = 'Sending...';
                }
                
                // Add to UI immediately
                addMessageToChat(message, 'admin', Date.now(), null, ADMIN_USERNAME);
                messageInput.value = '';

                // Send to MySQL via API
                const formData = new FormData();
                formData.append('text', message);
                formData.append('conversationId', currentConversationId);
                
                console.log('Sending admin message:', message, 'to conversation:', currentConversationId);
                
                const response = await fetch(API_BASE + 'chat-send.php', {
                    method: 'POST',
                    body: formData
                });
                
                console.log('Response status:', response.status);
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const data = await response.json();
                console.log('Response data:', data);
                
                if (data.success) {
                    console.log('Admin message sent successfully');
                    // Update lastMessageId if messageId is returned
                    if (data.messageId) {
                        lastMessageId = Math.max(lastMessageId, data.messageId);
                    }
                    // Reload conversations to update last message (silently, not initial load)
                    loadConversations(false);
                    // Don't reload messages - the message is already in UI
                    // Polling will pick up any other new messages
                } else {
                    console.error('Failed to send message:', data.message);
                    alert('Failed to send message: ' + (data.message || 'Unknown error'));
                    // Remove the message from UI if send failed
                    const messagesDiv = document.getElementById('chatMessages');
                    const messages = messagesDiv.querySelectorAll('.message');
                    if (messages.length > 0) {
                        messages[messages.length - 1].remove();
                    }
                }
            } catch (error) {
                console.error('Error sending message:', error);
                alert('Error sending message: ' + error.message);
                // Remove the message from UI if send failed
                const messagesDiv = document.getElementById('chatMessages');
                const messages = messagesDiv.querySelectorAll('.message');
                if (messages.length > 0) {
                    messages[messages.length - 1].remove();
                }
            } finally {
                // Re-enable input and button
                messageInput.disabled = false;
                messageInput.focus();
                const sendBtn = document.getElementById('sendButton');
                if (sendBtn) {
                    sendBtn.disabled = false;
                    sendBtn.textContent = 'Send';
                }
            }
        }

        // Load conversations on page load
        document.addEventListener('DOMContentLoaded', function() {
            loadConversations(true); // Initial load
        });
    </script>
</body>
</html>

