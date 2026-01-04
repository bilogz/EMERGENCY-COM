/**
 * Chat System using PHP/MySQL
 * Replaces Firebase with AJAX calls to PHP API endpoints
 */

(function() {
    'use strict';
    
    // Get API base path - try to detect the correct path
    let API_BASE = 'api/';
    const pathname = window.location.pathname;
    
    // Determine correct API path based on current location
    if (pathname.includes('/USERS/') && !pathname.includes('/includes/')) {
        // We're in a USERS page (not includes)
        API_BASE = 'api/';
    } else if (pathname.includes('/USERS/includes/')) {
        // We're in includes directory
        API_BASE = '../api/';
    } else if (pathname === '/' || pathname === '/index.php' || pathname.endsWith('/index.php') || pathname.endsWith('/')) {
        // We're at root level (index.php)
        API_BASE = 'USERS/api/';
    } else if (pathname.includes('/ADMIN/')) {
        // We're in admin area
        API_BASE = '../USERS/api/';
    } else {
        // Default: assume we're at root or in USERS
        // Try to detect by checking if we can access the file
        API_BASE = 'USERS/api/';
    }
    
    console.log('API_BASE set to:', API_BASE, 'for pathname:', pathname);
    let conversationId = null;
    let lastMessageId = 0;
    let pollingInterval = null;
    let isInitialized = false;
    
    // Initialize chat system
    async function initChat() {
        if (isInitialized) {
            console.log('Chat already initialized');
            return true;
        }
        
        console.log('Initializing MySQL chat system...');
        
        // Get user info
        const userId = sessionStorage.getItem('user_id') || localStorage.getItem('guest_user_id') || 'guest_' + Date.now();
        const userName = sessionStorage.getItem('user_name') || localStorage.getItem('guest_name') || 'Guest User';
        const userEmail = sessionStorage.getItem('user_email') || null;
        const userPhone = sessionStorage.getItem('user_phone') || localStorage.getItem('guest_contact') || null;
        const userLocation = sessionStorage.getItem('user_location') || localStorage.getItem('guest_location') || null;
        const userConcern = sessionStorage.getItem('user_concern') || localStorage.getItem('guest_concern') || null;
        const isGuest = !sessionStorage.getItem('user_id') || userId.startsWith('guest_');
        
        // Store user ID if not set
        if (!sessionStorage.getItem('user_id')) {
            sessionStorage.setItem('user_id', userId);
            if (!localStorage.getItem('guest_user_id')) {
                localStorage.setItem('guest_user_id', userId);
            }
        }
        
        console.log('User info:', { userId, userName, isGuest });
        
        try {
            // Try different API paths if first one fails
            const possiblePaths = [
                API_BASE,
                'USERS/api/',
                'api/',
                '../USERS/api/',
                '../api/'
            ];
            
            let response = null;
            let apiUrl = null;
            let lastError = null;
            
            for (const basePath of possiblePaths) {
                apiUrl = basePath + 'chat-get-conversation.php?' + new URLSearchParams({
                    userId: userId,
                    userName: userName,
                    userEmail: userEmail || '',
                    userPhone: userPhone || '',
                    userLocation: userLocation || '',
                    userConcern: userConcern || '',
                    isGuest: isGuest ? '1' : '0'
                });
                
                console.log('Trying to fetch conversation from:', apiUrl);
                
                try {
                    response = await fetch(apiUrl);
                    console.log('Response status:', response.status, 'from:', apiUrl);
                    
                    if (response.ok) {
                        // Success! Update API_BASE for future calls
                        API_BASE = basePath;
                        console.log('API_BASE updated to:', API_BASE);
                        break;
                    } else if (response.status === 404) {
                        // Try next path
                        console.warn('404 error, trying next path...');
                        continue;
                    } else {
                        // Other error, but path might be correct
                        break;
                    }
                } catch (fetchError) {
                    console.warn('Fetch error for', apiUrl, ':', fetchError.message);
                    lastError = fetchError;
                    continue;
                }
            }
            
            if (!response) {
                throw new Error(`Failed to fetch from all API paths. Last error: ${lastError ? lastError.message : 'Unknown'}`);
            }
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const data = await response.json();
            console.log('Conversation response:', data);
            
            if (data.success) {
                conversationId = data.conversationId;
                sessionStorage.setItem('conversation_id', conversationId);
                window.currentConversationId = conversationId;
                
                console.log('Conversation ready:', conversationId);
                
                // Load existing messages (initial load)
                await loadMessages(true);
                
                // Start polling for new messages
                startPolling();
                
                isInitialized = true;
                window.chatInitialized = true;
                
                return true;
            } else {
                console.error('Failed to get/create conversation:', data.message);
                return false;
            }
        } catch (error) {
            console.error('Error initializing chat:', error);
            alert('Failed to initialize chat: ' + error.message);
            return false;
        }
    }
    
    // Load messages
    async function loadMessages(isInitialLoad = false) {
        if (!conversationId) {
            conversationId = sessionStorage.getItem('conversation_id');
            if (!conversationId) {
                console.log('No conversation ID for loading messages');
                return;
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
                // Track existing messages to avoid duplicates
                const chatMessages = document.querySelector('.chat-messages');
                if (chatMessages) {
                    // Track existing message IDs
                    const existingIds = new Set();
                    chatMessages.querySelectorAll('.chat-message').forEach(msg => {
                        const msgId = msg.getAttribute('data-message-id');
                        if (msgId) {
                            existingIds.add(parseInt(msgId));
                        }
                    });
                    
                    let newMessagesAdded = false;
                    data.messages.forEach(msg => {
                        // For initial load, load all messages. For polling, only load new ones.
                        const shouldAdd = isInitialLoad 
                            ? !existingIds.has(msg.id)  // Initial load: add if not already displayed
                            : (msg.id > lastMessageId && !existingIds.has(msg.id)); // Polling: only new messages
                        
                        if (shouldAdd) {
                            existingIds.add(msg.id);
                            if (window.addMessageToChat) {
                                window.addMessageToChat(msg.text, msg.senderType, msg.timestamp, msg.id);
                            }
                            lastMessageId = Math.max(lastMessageId, msg.id);
                            newMessagesAdded = true;
                        }
                    });
                    
                    // Only scroll if new messages were added
                    if (newMessagesAdded && chatMessages) {
                        chatMessages.scrollTop = chatMessages.scrollHeight;
                        console.log('Loaded', newMessagesAdded ? 'new' : 'no', 'messages. Total messages in conversation:', data.messages.length);
                    }
                } else {
                    console.warn('Chat messages container not found when loading messages');
                }
            } else if (data.success && (!data.messages || data.messages.length === 0)) {
                console.log('No messages in conversation yet');
            }
        } catch (error) {
            console.error('Error loading messages:', error);
        }
    }
    
    // Send message
    async function sendMessage(text) {
        if (!text || !text.trim()) {
            console.warn('Cannot send empty message');
            return false;
        }
        
        // Get conversation ID
        if (!conversationId) {
            conversationId = sessionStorage.getItem('conversation_id');
            if (!conversationId) {
                console.error('No conversation ID, initializing...');
                const success = await initChat();
                if (!success) {
                    console.error('Failed to initialize chat');
                    alert('Chat is not ready. Please try again.');
                    return false;
                }
                conversationId = sessionStorage.getItem('conversation_id');
                if (!conversationId) {
                    console.error('Still no conversation ID after initialization');
                    alert('Chat is not ready. Please try again.');
                    return false;
                }
            }
        }
        
        const userId = sessionStorage.getItem('user_id');
        const userName = sessionStorage.getItem('user_name') || 'Guest User';
        const userEmail = sessionStorage.getItem('user_email') || null;
        const userPhone = sessionStorage.getItem('user_phone') || null;
        const userLocation = sessionStorage.getItem('user_location') || null;
        const userConcern = sessionStorage.getItem('user_concern') || null;
        const isGuest = !sessionStorage.getItem('user_id') || userId.startsWith('guest_');
        
        console.log('Sending message:', { text, conversationId, userId, userName });
        
        try {
            const formData = new FormData();
            formData.append('text', text.trim());
            formData.append('userId', userId);
            formData.append('userName', userName);
            formData.append('userEmail', userEmail || '');
            formData.append('userPhone', userPhone || '');
            formData.append('userLocation', userLocation || '');
            formData.append('userConcern', userConcern || '');
            formData.append('isGuest', isGuest ? '1' : '0');
            formData.append('conversationId', conversationId);
            
            const apiUrl = API_BASE + 'chat-send.php';
            console.log('Sending to:', apiUrl);
            console.log('FormData contents:', {
                text: text.trim(),
                userId: userId,
                userName: userName,
                conversationId: conversationId
            });
            
            const response = await fetch(apiUrl, {
                method: 'POST',
                body: formData
            });
            
            console.log('Response status:', response.status);
            
            if (!response.ok) {
                const errorText = await response.text();
                console.error('HTTP error response:', errorText);
                throw new Error(`HTTP error! status: ${response.status}, message: ${errorText}`);
            }
            
            const responseText = await response.text();
            console.log('Response text:', responseText);
            
            let data;
            try {
                data = JSON.parse(responseText);
            } catch (e) {
                console.error('Failed to parse JSON response:', e);
                console.error('Response was:', responseText);
                throw new Error('Invalid JSON response from server: ' + responseText.substring(0, 100));
            }
            
            console.log('Response data:', data);
            
            if (data.success) {
                console.log('Message sent successfully, messageId:', data.messageId, 'conversationId:', data.conversationId);
                // Update conversation ID if returned
                if (data.conversationId) {
                    conversationId = data.conversationId;
                    sessionStorage.setItem('conversation_id', conversationId);
                }
                // Update last message ID
                if (data.messageId) {
                    lastMessageId = Math.max(lastMessageId, parseInt(data.messageId));
                }
                
                // Immediately add the sent message to the chat UI
                if (window.addMessageToChat) {
                    const timestamp = new Date().toISOString();
                    window.addMessageToChat(text.trim(), 'user', timestamp, data.messageId);
                    console.log('Message added to chat UI immediately');
                } else {
                    console.warn('addMessageToChat function not available, message will appear after polling');
                }
                
                return true;
            } else {
                console.error('Failed to send message:', data.message);
                // Don't show alert here, let the caller handle it
                return false;
            }
        } catch (error) {
            console.error('Error sending message:', error);
            // Don't show alert here, let the caller handle it
            throw error; // Re-throw so caller can handle
        }
    }
    
    // Start polling for new messages
    function startPolling() {
        if (pollingInterval) {
            clearInterval(pollingInterval);
        }
        
        // Poll every 2 seconds for new messages (not initial load)
        pollingInterval = setInterval(() => {
            if (conversationId) {
                loadMessages(false); // Not initial load
            }
        }, 2000);
        
        console.log('Started polling for messages');
    }
    
    // Stop polling
    function stopPolling() {
        if (pollingInterval) {
            clearInterval(pollingInterval);
            pollingInterval = null;
        }
    }
    
    // Add message to chat UI (this will be called from sidebar.php)
    function addMessageToChat(text, senderType, timestamp, messageId = null) {
        const chatMessages = document.querySelector('.chat-messages');
        if (!chatMessages) {
            console.warn('Chat messages container not found');
            return;
        }

        // Check for duplicate messages by ID
        if (messageId) {
            const existingMsg = chatMessages.querySelector(`[data-message-id="${messageId}"]`);
            if (existingMsg) {
                console.log('Message already displayed, skipping:', messageId);
                return;
            }
        }

        // Remove system message if this is the first real message
        const systemMsg = chatMessages.querySelector('.chat-message-system');
        if (systemMsg && (senderType === 'user' || senderType === 'admin')) {
            systemMsg.remove();
        }

        const msg = document.createElement('div');
        msg.className = `chat-message chat-message-${senderType}`;
        
        // Add message ID as data attribute to prevent duplicates
        if (messageId) {
            msg.setAttribute('data-message-id', messageId);
        }

        const time = timestamp ? new Date(timestamp).toLocaleTimeString() : new Date().toLocaleTimeString();
        const senderName = senderType === 'user' ? 'You' : 'Admin';

        // Escape HTML
        const escapeHtml = (str) => {
            const div = document.createElement('div');
            div.textContent = str;
            return div.innerHTML;
        };
        
        msg.innerHTML = `<strong style="color: var(--text-color) !important;">${escapeHtml(senderName)}:</strong> <span style="color: var(--text-color) !important;">${escapeHtml(text)}</span> <small style="display: block; font-size: 0.8em; opacity: 0.7; margin-top: 0.25rem; color: var(--text-muted) !important;">${time}</small>`;
        chatMessages.appendChild(msg);
        chatMessages.scrollTop = chatMessages.scrollHeight;
        
        console.log('Message added to UI:', { text, senderType, messageId });
    }
    
    // Expose functions globally
    window.initChatMySQL = initChat;
    window.sendChatMessageMySQL = sendMessage;
    window.addMessageToChat = addMessageToChat;
    window.stopChatPolling = stopPolling;
    
    // Auto-initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            // Don't auto-init, wait for modal to open
        });
    }
    
})();

