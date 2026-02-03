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
        /* Enhanced Communication Interface Styles */
        :root {
            --chat-sidebar-width: 320px;
            --message-radius: 18px;
            --transition-speed: 0.2s;
            --card-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }

        .communication-container {
            display: grid;
            grid-template-columns: var(--chat-sidebar-width) 1fr;
            gap: 1.5rem;
            height: calc(100vh - 240px);
            min-height: 600px;
            position: relative;
        }

        /* Sidebar / Conversations List */
        .conversations-list-container {
            display: flex;
            flex-direction: column;
            background: var(--card-bg-1);
            border-radius: 12px;
            box-shadow: var(--card-shadow);
            border: 1px solid var(--border-color-1);
            overflow: hidden;
            height: 100%;
        }

        .chat-tabs {
            display: flex;
            border-bottom: 1px solid var(--border-color-1);
            background: var(--bg-color-1);
            flex-shrink: 0;
        }

        .chat-tab {
            flex: 1;
            padding: 1rem;
            text-align: center;
            cursor: pointer;
            font-weight: 600;
            font-size: 0.9rem;
            color: var(--text-secondary-1);
            transition: all var(--transition-speed) ease;
            border-bottom: 3px solid transparent;
            user-select: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .chat-tab:hover {
            color: var(--primary-color-1);
            background: rgba(76, 138, 137, 0.05);
        }

        .chat-tab.active {
            color: var(--primary-color-1);
            border-bottom-color: var(--primary-color-1);
            background: var(--card-bg-1);
        }

        .conversations-list {
            flex: 1;
            padding: 0.75rem;
            overflow-y: auto;
            -webkit-overflow-scrolling: touch;
        }

        /* Custom Scrollbar */
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

        .conversation-item {
            padding: 1rem;
            border-radius: 8px;
            cursor: pointer;
            transition: all var(--transition-speed) ease;
            margin-bottom: 0.5rem;
            color: var(--text-color-1);
            border: 1px solid transparent;
            position: relative;
        }

        .conversation-item:hover {
            background: rgba(76, 138, 137, 0.05);
            border-color: rgba(76, 138, 137, 0.1);
        }

        .conversation-item.active {
            background: var(--primary-color-1);
            color: white;
            box-shadow: 0 4px 12px rgba(76, 138, 137, 0.3);
            border-color: var(--primary-color-1);
        }

        .conversation-item.active * {
            color: white !important;
            opacity: 1 !important;
        }

        .conversation-item.closed {
            opacity: 0.7;
            background: var(--bg-color-1);
            border-color: var(--border-color-1);
        }

        .status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #2ecc71;
            display: inline-block;
            margin-right: 0.5rem;
            flex-shrink: 0;
        }

        .conversation-item.closed .status-dot {
            background: #95a5a6;
        }

        /* Main Chat Window */
        .chat-window {
            display: flex;
            flex-direction: column;
            background: var(--card-bg-1);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: var(--card-shadow);
            border: 1px solid var(--border-color-1);
            height: 100%;
        }

        .chat-header {
            padding: 1rem 1.5rem;
            border-bottom: 1px solid var(--border-color-1);
            background: var(--card-bg-1);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-shrink: 0;
            z-index: 10;
        }

        .chat-header-info {
            flex: 1;
            min-width: 0;
        }

        .chat-header-info h3 {
            margin: 0;
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--text-color-1);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .chat-header-info small {
            display: block;
            margin-top: 0.25rem;
            color: var(--text-secondary-1);
            font-size: 0.85rem;
            font-weight: 500;
        }

        .chat-messages {
            flex: 1;
            padding: 1.5rem;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 1rem;
            background: var(--bg-color-1);
            scroll-behavior: smooth;
        }

        /* Message Bubbles */
        .message {
            display: flex;
            gap: 0.75rem;
            max-width: 80%;
            animation: slideInUp 0.3s ease;
        }

        @keyframes slideInUp {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .message.admin {
            align-self: flex-end;
            flex-direction: row-reverse;
        }

        .message.user {
            align-self: flex-start;
        }

        .message-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            flex-shrink: 0;
            object-fit: cover;
            border: 2px solid white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .message-content {
            padding: 0.75rem 1rem;
            border-radius: var(--message-radius);
            position: relative;
            font-size: 0.95rem;
            line-height: 1.5;
            box-shadow: 0 1px 2px rgba(0,0,0,0.05);
            word-wrap: break-word;
        }

        .message.user .message-content {
            background: white;
            color: var(--text-color-1);
            border-top-left-radius: 4px;
            border: 1px solid var(--border-color-1);
        }

        .message.admin .message-content {
            background: var(--primary-color-1);
            color: white;
            border-top-right-radius: 4px;
        }

        .message-meta {
            font-size: 0.7rem;
            margin-top: 4px;
            opacity: 0.7;
            text-align: right;
        }

        .message.user .message-meta {
            text-align: left;
            color: var(--text-secondary-1);
        }

        .date-separator {
            display: flex;
            align-items: center;
            margin: 1rem 0;
            color: var(--text-secondary-1);
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .date-separator::before,
        .date-separator::after {
            content: '';
            flex: 1;
            border-bottom: 1px solid var(--border-color-1);
        }

        .date-separator::before { margin-right: 1rem; }
        .date-separator::after { margin-left: 1rem; }

        .chat-input {
            padding: 1rem;
            border-top: 1px solid var(--border-color-1);
            display: flex;
            gap: 0.75rem;
            background: var(--card-bg-1);
            align-items: center;
            flex-shrink: 0;
        }

        .chat-input input {
            flex: 1;
            padding: 0.85rem 1.25rem;
            border: 1px solid var(--border-color-1);
            border-radius: 24px;
            background: var(--bg-color-1);
            color: var(--text-color-1);
            font-size: 0.95rem;
            transition: all 0.2s ease;
        }

        .chat-input input:focus {
            outline: none;
            border-color: var(--primary-color-1);
            background: var(--card-bg-1);
            box-shadow: 0 0 0 3px rgba(76, 138, 137, 0.1);
        }

        .btn-load-more {
            background: var(--bg-color-1);
            border: 1px solid var(--border-color-1);
            color: var(--text-secondary-1);
            padding: 0.5rem 1rem;
            border-radius: 20px;
            cursor: pointer;
            font-size: 0.8rem;
            font-weight: 600;
            transition: all 0.2s ease;
            margin: 1rem auto;
            display: block;
        }

        .btn-load-more:hover {
            background: var(--card-bg-1);
            color: var(--primary-color-1);
            border-color: var(--primary-color-1);
        }

        /* Mobile Responsive */
        .mobile-back-btn {
            display: none;
            background: none;
            border: none;
            color: var(--text-secondary-1);
            font-size: 1.2rem;
            margin-right: 0.75rem;
            cursor: pointer;
            padding: 0.25rem;
        }

        @media (max-width: 992px) {
            .communication-container {
                grid-template-columns: 280px 1fr;
            }
        }

        @media (max-width: 768px) {
            .communication-container {
                grid-template-columns: 1fr;
                height: calc(100vh - 140px);
                overflow: hidden;
            }
            
            .conversations-list-container {
                width: 100%;
                position: absolute;
                top: 0;
                left: 0;
                bottom: 0;
                z-index: 10;
                transition: transform 0.3s ease;
            }
            
            .chat-window {
                width: 100%;
                position: absolute;
                top: 0;
                left: 0;
                bottom: 0;
                z-index: 20;
                transform: translateX(100%);
                transition: transform 0.3s ease;
            }
            
            .communication-container.chat-active .conversations-list-container {
                transform: translateX(-20%);
                opacity: 0;
                pointer-events: none;
            }
            
            .communication-container.chat-active .chat-window {
                transform: translateX(0);
            }
            
            .mobile-back-btn {
                display: block;
            }
            
            .message {
                max-width: 90%;
            }
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
                <nav aria-label="Breadcrumb">
                    <ol class="breadcrumb-list">
                        <li class="breadcrumb-item">
                            <a href="dashboard.php" class="breadcrumb-link">Dashboard</a>
                        </li>
                        <li class="breadcrumb-item active" aria-current="page">Two-Way Communication</li>
                    </ol>
                </nav>
                <h1><i class="fas fa-comments" style="color: var(--primary-color-1); margin-right: 0.5rem;"></i> Two-Way Communication Interface</h1>
                <p>Interactive communication platform allowing administrators and citizens to exchange messages in real-time.</p>
            </div>
            
            <div class="sub-container">
                <div class="page-content">
                    <div class="communication-container" id="communicationContainer">
                        <!-- Conversations List Container -->
                        <div class="conversations-list-container">
                            <div class="chat-tabs">
                                <div class="chat-tab active" onclick="switchTab('active')">
                                    <i class="fas fa-inbox"></i> Active <span id="activeCount" class="badge"></span>
                                </div>
                                <div class="chat-tab" onclick="switchTab('closed')">
                                    <i class="fas fa-check-circle"></i> Closed
                                </div>
                            </div>
                            <div class="conversations-list" id="scrollableList">
                                <div id="incomingEmergencyCallRow" style="display:none;"></div>
                                <div id="conversationsList">
                                    <!-- Conversations will be loaded here -->
                                </div>
                                <div id="loadMoreContainer" class="load-more-container" style="display: none;">
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
                                    <button class="btn btn-sm btn-secondary" id="toggleStatusBtn" style="display: none;">
                                        <i class="fas fa-check"></i> Close Chat
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
    </div>

    <!-- MySQL Chat System -->
    <script>
        const API_BASE = '../api/';
        const ADMIN_USERNAME = <?php echo json_encode($adminUsername); ?>;
        const ADMIN_AVATAR = `https://ui-avatars.com/api/?name=${encodeURIComponent(ADMIN_USERNAME)}&background=4c8a89&color=fff&size=128`;
        
        // State Management
        let currentStatus = 'active';
        let currentConversationId = null;
        let lastMessageId = 0;
        let currentPage = 1;
        const pageLimit = 20; // Load 20 at a time for speed
        let isLoading = false;
        let hasMore = true;
        let lastDisplayedDate = null; // Track the last date shown in the chat
        
        // Polling Intervals
        let pollInterval = null;
        let messageInterval = null;

        // --- View Management ---
        
        function switchTab(status) {
            if (currentStatus === status) return;
            currentStatus = status;
            
            // UI Update
            document.querySelectorAll('.chat-tab').forEach(tab => {
                tab.classList.toggle('active', tab.textContent.trim().toLowerCase().includes(status));
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

        // --- Data Loading ---

        async function loadConversations(isInitial = false, append = false) {
            if (isLoading) return;
            isLoading = true;
            
            const listContainer = document.getElementById('conversationsList');
            const spinner = document.getElementById('loadingSpinner');
            const loadMoreBtn = document.getElementById('loadMoreContainer');
            
            if (isInitial && !append) {
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
                
                const response = await fetch(`${API_BASE}chat-get-conversations.php?${params}`);
                const data = await response.json();
                
                spinner.style.display = 'none';
                
                if (!data.success) throw new Error(data.message);
                
                const conversations = data.conversations || [];
                
                // Handle Empty State
                if (conversations.length === 0) {
                    hasMore = false;
                    if (isInitial && !append) {
                        listContainer.innerHTML = `<p style="text-align: center; color: var(--text-secondary-1); padding: 2rem;">No ${currentStatus} conversations</p>`;
                    }
                    return;
                }
                
                // Check if we have more pages
                if (conversations.length < pageLimit) {
                    hasMore = false;
                } else {
                    loadMoreBtn.style.display = 'block';
                }
                
                // Render Items
                conversations.forEach(conv => {
                    const el = createConversationElement(conv);
                    listContainer.appendChild(el);
                });
                
            } catch (error) {
                console.error('Error loading conversations:', error);
                if (isInitial) listContainer.innerHTML = '<p style="color: #e74c3c; text-align: center; padding: 1rem;">Failed to load data</p>';
            } finally {
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
        
        async function pollUpdates() {
            // 1. Update Badge & Unread Count
            try {
                const response = await fetch(API_BASE + 'chat-get-unread-count.php');
                const data = await response.json();
                if (data.success) {
                    const count = data.unreadCount;
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

            // 2. Refresh List (Page 1 Only) - Silent Update
            // We only do this if we aren't loading, and user is viewing top of list
            // to avoid jumping.
            const listEl = document.getElementById('scrollableList');
            if (!isLoading && listEl.scrollTop < 50 && currentStatus === 'active') {
                try {
                    const params = new URLSearchParams({ status: 'active', page: 1, limit: pageLimit });
                    const res = await fetch(`${API_BASE}chat-get-conversations.php?${params}`);
                    const data = await res.json();
                    
                    if (data.success && data.conversations) {
                        data.conversations.forEach(conv => {
                            const existing = document.querySelector(`.conversation-item[data-conversation-id="${conv.id}"]`);
                            
                            // If exists, update content and move to top if new message
                            // Ideally we check timestamps, but for simplicity:
                            // If it's not the first item, move it to top.
                            const list = document.getElementById('conversationsList');
                            const firstItem = list.firstElementChild;
                            
                            // Create new element content
                            const tempDiv = document.createElement('div');
                            tempDiv.innerHTML = getConversationHTML(conv);
                            
                            if (existing) {
                                // Update content
                                existing.innerHTML = tempDiv.innerHTML;
                                existing._conversationData = conv; // Update data
                                
                                // Move to top if timestamp is newer than current top
                                // or simply if it's not already top
                                if (existing !== firstItem) {
                                    list.insertBefore(existing, firstItem);
                                }
                            } else {
                                // New item! Prepend.
                                const newEl = createConversationElement(conv);
                                list.insertBefore(newEl, firstItem);
                            }
                        });
                    }
                } catch (e) { console.error('Poll error', e); }
            }
        }

        // --- DOM Helpers ---

        function createConversationElement(conv) {
            const item = document.createElement('div');
            item.className = 'conversation-item';
            if (currentStatus === 'closed') item.classList.add('closed');
            if (String(conv.id) === String(currentConversationId)) item.classList.add('active');
            
            item.setAttribute('data-conversation-id', conv.id);
            item._conversationData = conv;
            
            item.innerHTML = getConversationHTML(conv);
            
            item.addEventListener('click', function() {
                openConversation(conv.id, this._conversationData, this);
            });
            
            return item;
        }
        
        function getConversationHTML(conv) {
            const guestBadge = conv.isGuest ? '<span style="background: #ff9800; color: white; padding: 0.1rem 0.4rem; border-radius: 4px; font-size: 0.65rem; margin-left: 0.5rem; vertical-align: middle; font-weight: 700;">GUEST</span>' : '';
            const concernBadge = conv.userConcern ? `<span style="background: rgba(33, 150, 243, 0.15); color: #2196f3; padding: 0.1rem 0.4rem; border-radius: 4px; font-size: 0.65rem; margin-left: 0.5rem; text-transform: capitalize; vertical-align: middle; font-weight: 600;">${conv.userConcern}</span>` : '';
            const statusDot = `<span class="status-dot"></span>`;
            
            const time = conv.lastMessageTime ? new Date(conv.lastMessageTime).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'}) : '';
            const date = conv.lastMessageTime ? new Date(conv.lastMessageTime).toLocaleDateString() : '';
            const displayTime = time ? `<small style="float: right; opacity: 0.7; font-size: 0.75rem;">${time}</small>` : '';

            // User Info Line
            const userInfo = [];
            // Prioritize User Name/Email/Phone in bold
            // Secondary info in small text
            
            if (conv.userPhone) userInfo.push(`<i class="fas fa-phone"></i>`);
            if (conv.userLocation) userInfo.push(`<i class="fas fa-map-marker-alt"></i>`);
            if (conv.ipAddress) userInfo.push(`<i class="fas fa-network-wired"></i>`);
            
            return `
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.35rem;">
                    <div style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis; padding-right: 0.5rem; font-size: 0.95rem;">
                        ${statusDot}
                        <strong>${conv.userName || 'Unknown'}</strong>${guestBadge}${concernBadge}
                    </div>
                    ${displayTime}
                </div>
                <p style="margin: 0; font-size: 0.85rem; opacity: 0.8; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                    ${conv.lastMessage || 'No messages'}
                </p>
                <div style="margin-top: 0.5rem; font-size: 0.75rem; opacity: 0.6;">
                    ${userInfo.join(' &nbsp; ')} &nbsp; ${conv.userLocation || ''}
                </div>
            `;
        }

        // --- Chat Interaction ---

        function openConversation(id, data, element) {
            currentConversationId = id;
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
            
            const guestBadge = data.isGuest ? ' <span style="font-size: 0.7rem; background: #ff9800; color: white; padding: 2px 6px; border-radius: 4px; vertical-align: middle;">GUEST</span>' : '';
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
            
            // Input/Button State
            const isClosed = (data.status === 'closed');
            setupInputState(isClosed);
            setupCloseButton(isClosed);

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
            messageInterval = setInterval(() => fetchMsgs(false), 3000); // Poll with false
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
            
            div.innerHTML = `
                <img src="${avatar}" class="message-avatar" alt="">
                <div class="message-content">
                    ${escapeHtml(msg.text)}
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
                    alert('Failed to send');
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
            loadConversations(true);
            pollInterval = setInterval(pollUpdates, 5000);
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
            <div style="margin-top:12px; display:flex; gap:8px; flex-shrink:0;">
                <input type="text" id="messageInput" placeholder="Type a message..." style="flex:1; padding:8px 12px; border:1px solid rgba(255,255,255,0.2); border-radius:6px; background:rgba(255,255,255,0.1); color:#fff; outline:none;">
                <button id="sendMessageBtn" class="btn btn-primary" style="padding:8px 16px;">Send</button>
            </div>
            
            <!-- Call Controls -->
            <div style="margin-top:16px; display:flex; gap:10px; justify-content:flex-end; flex-shrink:0;">
                <button id="endCallBtn" class="btn btn-secondary" disabled style="opacity:0.6; pointer-events:none;">End Call</button>
            </div>
        </div>
    </div>
    <audio id="remote" autoplay></audio>

    <script src="https://cdn.socket.io/4.7.5/socket.io.min.js"></script>
    <script>
    const SIGNALING_URL = `${window.location.protocol}//${window.location.hostname}:3000`;
    const room = "emergency-room";

    let socket = null;
    let socketBound = false;
    let notificationSound = 'siren';

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

    function ensureSocket() {
        if (socket) return socket;
        if (typeof window.io !== 'function') return null;
        socket = window.io(SIGNALING_URL);
        bindSocketHandlers();
        return socket;
    }

    function bindSocketHandlers() {
        if (!socket || socketBound) return;
        socketBound = true;

        socket.on('connect', () => {
            socket.emit('join', room);
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

        socket.on('connect_error', () => {
            if (callId) {
                setStatus('Connecting failed. Signaling server offline.');
                setEndEnabled(true);
            }
        });
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
    }

    let pc = null;
    let localStream = null;
    let callId = null;
    let callConnectedAt = null;
    let timerInterval = null;
    let locationData = null;
    let messages = [];

    let pendingOffer = null;
    let pendingCallId = null;
    let pendingCandidates = [];

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
            background: ${sender === 'admin' ? 'rgba(34, 197, 94, 0.2)' : 'rgba(59, 130, 246, 0.2)'};
            border-left: 3px solid ${sender === 'admin' ? '#22c55e' : '#3b82f6'};
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
    }

    async function sendMessage() {
        const input = document.getElementById('messageInput');
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
                sender: 'admin',
                senderName: 'Emergency Services',
                timestamp: Date.now()
            }, room);
        }
        
        // Log to database using existing chat-send structure
        try {
            const formData = new FormData();
            formData.append('text', text);
            formData.append('userId', 'admin');
            formData.append('userName', 'Emergency Services');
            formData.append('conversationId', `call_${callId}`);
            
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
            const payload = {
                callId,
                room,
                role: 'admin',
                event,
                location: locationData,
                ...extra
            };
            await fetch('../api/call-log.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
        } catch (e) {}
    }

    function cleanupCall() {
        stopTimer();
        setEndEnabled(false);
        setCallActiveBannerVisible(false);
        setIncomingCallModalVisible(false);

        // Clear messages
        messages = [];
        const messagesContainer = document.getElementById('callMessages');
        if (messagesContainer) {
            messagesContainer.innerHTML = '<div style="text-align:center; opacity:0.6; font-size:12px;">Messages will appear here</div>';
        }
        
        // Clear message input
        const messageInput = document.getElementById('messageInput');
        if (messageInput) {
            messageInput.value = '';
        }

        pendingOffer = null;
        pendingCallId = null;
        pendingCandidates = [];
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

        async function acceptIncomingEmergencyCall() {
            if (!pendingOffer || !pendingCallId) return;
            if (callId && pendingCallId !== callId) return;

            callId = pendingCallId;
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

                const answer = await pc.createAnswer();
                await pc.setLocalDescription(answer);
                s.emit('answer', { sdp: answer, callId }, room);

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

            s.emit('hangup', { callId: pendingCallId }, room);
            pendingOffer = null;
            pendingCallId = null;
            pendingCandidates = [];
            renderIncomingEmergencyCallRow();
            _stopAlertSound();
        }

        window.acceptIncomingEmergencyCall = acceptIncomingEmergencyCall;
        window.declineIncomingEmergencyCall = declineIncomingEmergencyCall;

        s.on('offer', async payload => {
            const sdp = payload && payload.sdp ? payload.sdp : payload;
            const incomingCallId = payload && payload.callId ? payload.callId : null;
            if (!incomingCallId) return;
            if (callId && incomingCallId !== callId) return;

            pendingCallId = incomingCallId;
            pendingOffer = sdp;
            pendingCandidates = [];

            try {
                if (typeof switchTab === 'function') switchTab('active');
            } catch (e) {}

            _startAlertSound(notificationSound);
            locationData = await tryGetLocation();
            await logCall('incoming');
            renderIncomingEmergencyCallRow();
        });

        s.on('candidate', payload => {
            const cand = payload && payload.candidate ? payload.candidate : payload;
            const incomingCallId = payload && payload.callId ? payload.callId : null;
            if (incomingCallId && callId && incomingCallId !== callId) return;
            if (incomingCallId && pendingCallId && incomingCallId !== pendingCallId) return;

            if (!pc || !callId) {
                if (cand) pendingCandidates.push(cand);
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
    })();
</script>

</body>
</html>