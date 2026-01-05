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
        
        // If concern is missing, don't initialize - let the form handle it
        if (isGuest && !userConcern) {
            console.log('Concern/category not provided - form should be shown');
            return false;
        }
        
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
                
                // Clear closed handler flag since we have a new active conversation
                window.conversationClosedHandled = false;
                
                console.log('Conversation ready:', conversationId);
                
                // Reset lastMessageId for initial load to get all messages
                lastMessageId = 0;
                
                // Load existing messages (initial load)
                await loadMessages(true);
                
                // Start polling for new messages
                startPolling();
                
                isInitialized = true;
                window.chatInitialized = true;
                
                // Ensure chat interface is shown (not form)
                const chatInterface = document.getElementById('chatInterface');
                const userInfoForm = document.getElementById('chatUserInfoForm');
                if (chatInterface && userInfoForm) {
                    userInfoForm.style.display = 'none';
                    chatInterface.style.display = 'block';
                }
                
                // Ensure close button is always visible and enabled
                const chatCloseBtn = document.getElementById('chatCloseBtn');
                if (chatCloseBtn) {
                    chatCloseBtn.style.display = 'inline-flex';
                    chatCloseBtn.style.visibility = 'visible';
                    chatCloseBtn.style.pointerEvents = 'auto';
                    chatCloseBtn.style.cursor = 'pointer';
                    chatCloseBtn.style.opacity = '1';
                    chatCloseBtn.disabled = false;
                    chatCloseBtn.removeAttribute('disabled');
                    
                    // Attach close button handler
                    if (window.attachCloseButtonHandler) {
                        setTimeout(() => {
                            window.attachCloseButtonHandler();
                        }, 100);
                    }
                }
                
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
            
            // Check if conversation is closed - check this FIRST before processing messages
            if (data.conversationStatus === 'closed') {
                console.log('Conversation is closed (detected in loadMessages), refreshing chat interface immediately');
                handleConversationClosed(data.closedBy);
                return;
            }
            
            // Also check if success is false but status indicates closed
            if (!data.success && data.conversationStatus === 'closed') {
                console.log('Conversation is closed (detected via API error), refreshing chat interface');
                handleConversationClosed(data.closedBy);
                return;
            }
            
            if (data.success && data.messages && data.messages.length > 0) {
                console.log(`Loading ${data.messages.length} messages (isInitialLoad: ${isInitialLoad}, lastMessageId: ${lastMessageId})`);
                
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
                                // Pass admin name if available (for admin messages)
                                const adminName = (msg.senderType === 'admin' && msg.senderName) ? msg.senderName : null;
                                console.log(`Adding message: ${msg.senderType} - "${msg.text}" (ID: ${msg.id}, Admin: ${adminName || 'N/A'})`);
                                window.addMessageToChat(msg.text, msg.senderType, msg.timestamp, msg.id, adminName);
                            } else {
                                console.warn('addMessageToChat function not available');
                            }
                            lastMessageId = Math.max(lastMessageId, msg.id);
                            newMessagesAdded = true;
                        } else {
                            console.log(`Skipping message ${msg.id} (already displayed or not new)`);
                        }
                    });
                    
                    // Only scroll if new messages were added
                    if (newMessagesAdded && chatMessages) {
                        chatMessages.scrollTop = chatMessages.scrollHeight;
                        console.log(`Loaded ${newMessagesAdded ? 'new' : 'no'} messages. Total messages in conversation: ${data.messages.length}, lastMessageId now: ${lastMessageId}`);
                        
                        // Ensure close button handler is attached after admin replies
                        if (window.attachCloseButtonHandler) {
                            setTimeout(() => {
                                window.attachCloseButtonHandler();
                            }, 100);
                        }
                    } else {
                        console.log('No new messages to display');
                    }
                } else {
                    console.warn('Chat messages container not found when loading messages');
                }
            } else if (data.success && (!data.messages || data.messages.length === 0)) {
                console.log('No messages in conversation yet');
                // Even if no messages, check status in case conversation was closed
                if (data.conversationStatus === 'closed') {
                    console.log('Conversation is closed (detected during empty message check)');
                    handleConversationClosed(data.closedBy);
                    return;
                }
            } else if (!data.success) {
                console.error('Failed to load messages:', data.message || 'Unknown error');
            } else if (!data.success) {
                // API returned error - check if conversation is closed
                if (data.conversationStatus === 'closed') {
                    console.log('Conversation is closed (detected via API error)');
                    handleConversationClosed(data.closedBy);
                    return;
                }
                // For other errors, also check status directly
                checkConversationStatus();
            }
        } catch (error) {
            console.error('Error loading messages:', error);
            // On error, try to check conversation status directly
            if (conversationId) {
                checkConversationStatus();
            }
        }
    }
    
    // Check conversation status directly
    async function checkConversationStatus() {
        if (!conversationId) {
            conversationId = sessionStorage.getItem('conversation_id');
            if (!conversationId) {
                return;
            }
        }
        
        // Skip if already handling closed
        if (window.conversationClosedHandled) {
            return;
        }
        
        try {
            const response = await fetch(API_BASE + 'chat-get-conversation.php?' + new URLSearchParams({
                conversationId: conversationId
            }));
            
            if (response.ok) {
                const data = await response.json();
                if (data.success && data.status === 'closed') {
                    console.log('Conversation status check: closed - refreshing immediately');
                    handleConversationClosed(data.closedBy);
                } else if (data.success && data.status === 'active') {
                    // Reset the closed handler flag if conversation is active again
                    window.conversationClosedHandled = false;
                }
            } else if (response.status === 404 || response.status === 400) {
                // Conversation not found - might be closed or deleted
                console.log('Conversation not found - treating as closed');
                handleConversationClosed(null);
            }
        } catch (error) {
            console.error('Error checking conversation status:', error);
        }
    }
    
    // Handle conversation closed by admin
    function handleConversationClosed(closedByAdmin = null) {
        // Prevent multiple calls
        if (window.conversationClosedHandled) {
            console.log('Conversation close already handled, skipping');
            return;
        }
        window.conversationClosedHandled = true;
        
        console.log('Handling conversation closed - showing notification modal');
        
        // Stop polling
        stopPolling();
        
        // Clear conversation ID
        const oldConversationId = conversationId;
        conversationId = null;
        sessionStorage.removeItem('conversation_id');
        window.currentConversationId = null;
        
        // Reset last message ID
        lastMessageId = 0;
        
        // Reset initialization flags
        isInitialized = false;
        window.chatInitialized = false;
        
        // Helper function for escaping HTML
        function escapeHtml(str) {
            if (!str) return '';
            const div = document.createElement('div');
            div.textContent = str;
            return div.innerHTML;
        }
        
        // Show notification modal instead of in-chat message
        const closedModal = document.getElementById('conversationClosedModal');
        const closedMessage = document.getElementById('conversationClosedMessage');
        const closedOkBtn = document.getElementById('conversationClosedOkBtn');
        
        if (closedModal && closedMessage) {
            // Set message text
            let messageText = 'The chat was closed by the administrator.';
            if (closedByAdmin) {
                messageText = `The chat was closed by the administrator: <strong>${escapeHtml(closedByAdmin)}</strong>.`;
            }
            messageText += ' If there\'s another concern, please start a new chat.';
            
            closedMessage.innerHTML = messageText;
            
            // Function to hide modal and show form
            function hideModalAndShowForm() {
                closedModal.classList.remove('show');
                setTimeout(() => {
                    closedModal.style.display = 'none';
                }, 300);
                showUserInfoFormAfterClose();
            }
            
            // Show modal
            closedModal.style.display = 'flex';
            setTimeout(() => {
                closedModal.classList.add('show');
            }, 10);
            
            // Handle OK button click
            if (closedOkBtn) {
                // Remove old listeners
                const newBtn = closedOkBtn.cloneNode(true);
                closedOkBtn.parentNode.replaceChild(newBtn, closedOkBtn);
                const freshOkBtn = document.getElementById('conversationClosedOkBtn');
                
                freshOkBtn.onclick = function() {
                    hideModalAndShowForm();
                };
            }
            
            // Close modal when clicking outside (on backdrop)
            closedModal.onclick = function(e) {
                if (e.target === closedModal) {
                    hideModalAndShowForm();
                }
            };
            
            // Close modal on Escape key
            const escapeHandler = function(e) {
                if (e.key === 'Escape' && closedModal.classList.contains('show')) {
                    hideModalAndShowForm();
                    document.removeEventListener('keydown', escapeHandler);
                }
            };
            document.addEventListener('keydown', escapeHandler);
        } else {
            // Fallback: if modal doesn't exist, show alert and continue
            let alertMessage = 'The chat was closed by the administrator.';
            if (closedByAdmin) {
                alertMessage = `The chat was closed by the administrator: ${closedByAdmin}.`;
            }
            alertMessage += ' If there\'s another concern, please start a new chat.';
            alert(alertMessage);
            showUserInfoFormAfterClose();
        }
        
        // Function to show user info form after modal is closed
        function showUserInfoFormAfterClose() {
            // Clear and refresh chat messages (without closed message)
            const chatMessages = document.querySelector('.chat-messages');
            if (chatMessages) {
                // Clear ALL existing messages completely
                chatMessages.innerHTML = '';
                
                // Add only system message
                const systemMsg = document.createElement('div');
                systemMsg.className = 'chat-message chat-message-system';
                systemMsg.innerHTML = '<strong>System:</strong> For life-threatening emergencies, call 911 or the Quezon City emergency hotlines immediately.';
                chatMessages.appendChild(systemMsg);
            }
            
            // Clear status indicator
        const statusIndicator = document.getElementById('chatStatusIndicator');
        if (statusIndicator) {
            statusIndicator.style.display = 'none';
        }
        const statusText = document.getElementById('chatStatusText');
        if (statusText) {
            statusText.textContent = '';
        }
        
        // Automatically show user info form to select category again
        const chatInterface = document.getElementById('chatInterface');
        const userInfoForm = document.getElementById('chatUserInfoForm');
        const startNewBtn = document.getElementById('startNewConversationBtn');
        const chatCloseBtn = document.getElementById('chatCloseBtn');
        
        // Hide chat interface and show user info form
        if (userInfoForm && chatInterface) {
            chatInterface.style.display = 'none';
            userInfoForm.style.display = 'block';
            
            // Clear stored concern/category to force re-selection
            localStorage.removeItem('guest_concern');
            sessionStorage.removeItem('user_concern');
            
            // Clear the concern select dropdown
            const concernSelect = document.getElementById('userConcernSelect');
            if (concernSelect) {
                concernSelect.value = '';
            }
            
            // Optionally clear all fields to force complete re-entry
            // Or keep name/contact/location and only clear concern
            // For now, we'll keep other fields but clear concern
            const nameInput = document.getElementById('userNameInput');
            const contactInput = document.getElementById('userContactInput');
            const locationInput = document.getElementById('userLocationInput');
            
            // Keep name, contact, and location if they exist, but clear concern
            // This way user only needs to select category again
            if (nameInput && !nameInput.value) {
                const storedName = localStorage.getItem('guest_name') || sessionStorage.getItem('user_name');
                if (storedName) nameInput.value = storedName;
            }
            if (contactInput && !contactInput.value) {
                const storedContact = localStorage.getItem('guest_contact') || sessionStorage.getItem('user_phone');
                if (storedContact) contactInput.value = storedContact;
            }
            if (locationInput && !locationInput.value) {
                const storedLocation = localStorage.getItem('guest_location') || sessionStorage.getItem('user_location');
                if (storedLocation) locationInput.value = storedLocation;
            }
            
            console.log('Showing user info form - user must select category again');
        }
        
        // Hide buttons
        if (chatCloseBtn) {
            chatCloseBtn.style.display = 'none';
        }
        if (startNewBtn) {
            startNewBtn.style.display = 'none';
        }
        
        // Disable input and send button (they're hidden anyway)
        const chatInput = document.getElementById('chatInput');
        const chatSendBtn = document.getElementById('chatSendBtn');
        if (chatInput) {
            chatInput.disabled = true;
            chatInput.value = '';
        }
        if (chatSendBtn) {
            chatSendBtn.disabled = true;
        }
        
        // Allow modal to be closed after refresh - enable header close button
        const chatModal = document.getElementById('chatModal');
        const modalHeaderCloseBtn = chatModal ? chatModal.querySelector('.chat-modal-header .chat-close-btn') : null;
        if (modalHeaderCloseBtn) {
            modalHeaderCloseBtn.style.display = 'block';
            modalHeaderCloseBtn.style.pointerEvents = 'auto';
            modalHeaderCloseBtn.style.cursor = 'pointer';
            modalHeaderCloseBtn.style.opacity = '1';
            modalHeaderCloseBtn.disabled = false;
            
            // Ensure close handler is attached
            if (!modalHeaderCloseBtn.hasAttribute('data-close-handler-attached')) {
                modalHeaderCloseBtn.setAttribute('data-close-handler-attached', 'true');
                modalHeaderCloseBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    if (window.closeChat || window.closeChatWithFlag) {
                        (window.closeChatWithFlag || window.closeChat)();
                    } else {
                        // Fallback close
                        if (chatModal) {
                            chatModal.style.display = 'none';
                            chatModal.classList.remove('chat-modal-open');
                            document.body.style.overflow = '';
                        }
                    }
                });
            }
        }
        
        // Reset chat initialization flag to allow new conversation
        isInitialized = false;
        window.chatInitialized = false;
        
        console.log('Conversation closed - chat interface refreshed, ready for new conversation');
        }
    }
    
    // Start new conversation
    function startNewConversation() {
        // Clear the closed handler flag
        window.conversationClosedHandled = false;
        
        // Clear conversation ID
        conversationId = null;
        sessionStorage.removeItem('conversation_id');
        window.currentConversationId = null;
        
        // Reset last message ID
        lastMessageId = 0;
        
        // Reset initialization flags
        isInitialized = false;
        window.chatInitialized = false;
        
        // Clear chat messages (except system message)
        const chatMessages = document.querySelector('.chat-messages');
        if (chatMessages) {
            // Remove all messages except system message
            const messages = chatMessages.querySelectorAll('.chat-message:not(.chat-message-system)');
            messages.forEach(msg => msg.remove());
            
            // Remove closed message if exists
            const closedMsg = chatMessages.querySelector('.chat-message-closed');
            if (closedMsg) {
                closedMsg.remove();
            }
            
            // Ensure system message exists
            const systemMsg = chatMessages.querySelector('.chat-message-system');
            if (!systemMsg) {
                const systemMsgDiv = document.createElement('div');
                systemMsgDiv.className = 'chat-message chat-message-system';
                systemMsgDiv.innerHTML = '<strong>System:</strong> For life-threatening emergencies, call 911 or the Quezon City emergency hotlines immediately.';
                chatMessages.appendChild(systemMsgDiv);
            }
        }
        
        // Hide "Start New Conversation" button and show close button
        const startNewBtn = document.getElementById('startNewConversationBtn');
        const chatCloseBtn = document.getElementById('chatCloseBtn');
        if (startNewBtn) {
            startNewBtn.style.display = 'none';
        }
        if (chatCloseBtn) {
            chatCloseBtn.style.display = 'inline-flex';
        }
        
        // Re-enable input and send button
        const chatInput = document.getElementById('chatInput');
        const chatSendBtn = document.getElementById('chatSendBtn');
        if (chatInput) {
            chatInput.disabled = false;
            chatInput.placeholder = 'Type your message...';
            chatInput.value = '';
        }
        if (chatSendBtn) {
            chatSendBtn.disabled = false;
        }
        
        // Reset initialization flag
        isInitialized = false;
        window.chatInitialized = false;
        
        // Re-initialize chat to create new conversation
        initChat().then((success) => {
            if (success) {
                console.log('New conversation started');
            }
        });
        
        console.log('Starting new conversation');
    }
    
    // Reset chat for new conversation (kept for backward compatibility)
    function resetChatForNewConversation() {
        startNewConversation();
    }
    
    // Send message
    async function sendMessage(text) {
        if (!text || !text.trim()) {
            console.warn('Cannot send empty message');
            return false;
        }
        
        // Check if we need to reset for new conversation (if previous was closed)
        const currentConvId = sessionStorage.getItem('conversation_id');
        if (currentConvId && conversationId !== currentConvId) {
            // Conversation ID changed, reset chat
            resetChatForNewConversation();
        }
        
        // Get conversation ID
        if (!conversationId) {
            conversationId = sessionStorage.getItem('conversation_id');
            if (!conversationId) {
                console.log('No conversation ID, initializing new conversation...');
                // Reset chat first to clear any closed conversation state
                resetChatForNewConversation();
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
                // Update last message ID - but don't skip admin messages that might have been sent
                if (data.messageId) {
                    const newMessageId = parseInt(data.messageId);
                    lastMessageId = Math.max(lastMessageId, newMessageId);
                    console.log('Updated lastMessageId to:', lastMessageId);
                }
                
                // Immediately add the sent message to the chat UI
                if (window.addMessageToChat) {
                    const timestamp = new Date().toISOString();
                    window.addMessageToChat(text.trim(), 'user', timestamp, data.messageId);
                    console.log('Message added to chat UI immediately');
                } else {
                    console.warn('addMessageToChat function not available, message will appear after polling');
                }
                
                // After sending, do a quick reload to get any admin messages that might have arrived
                setTimeout(() => {
                    loadMessages(false);
                }, 500);
                
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
        
        // Poll every 1.5 seconds for new messages and status (not initial load)
        // More frequent polling to detect closed conversations faster
        pollingInterval = setInterval(() => {
            if (conversationId) {
                // Always check status first, then load messages
                checkConversationStatus();
                loadMessages(false); // Not initial load - this will check status too
            } else {
                // If no conversation ID, try to check status anyway
                checkConversationStatus();
            }
        }, 1500); // Poll every 1.5 seconds for faster detection
        
        console.log('Started polling for messages and conversation status');
    }
    
    // Stop polling
    function stopPolling() {
        if (pollingInterval) {
            clearInterval(pollingInterval);
            pollingInterval = null;
        }
    }
    
    // Add message to chat UI (this will be called from sidebar.php)
    function addMessageToChat(text, senderType, timestamp, messageId = null, senderName = null) {
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
        
        // Get sender name - for admin messages, use provided admin name or default to "Admin"
        let displayName = senderType === 'user' ? 'You' : (senderName || 'Admin');

        // Escape HTML
        const escapeHtml = (str) => {
            if (!str) return '';
            const div = document.createElement('div');
            div.textContent = str;
            return div.innerHTML;
        };
        
        msg.innerHTML = `<strong style="color: var(--text-color) !important;">${escapeHtml(displayName)}:</strong> <span style="color: var(--text-color) !important;">${escapeHtml(text)}</span> <small style="display: block; font-size: 0.8em; opacity: 0.7; margin-top: 0.25rem; color: var(--text-muted) !important;">${time}</small>`;
        chatMessages.appendChild(msg);
        chatMessages.scrollTop = chatMessages.scrollHeight;
        
        console.log('Message added to UI:', { text, senderType, messageId, senderName: displayName });
    }
    
    // Expose functions globally
    window.initChatMySQL = initChat;
    window.sendChatMessageMySQL = sendMessage;
    window.addMessageToChat = addMessageToChat;
    window.stopChatPolling = stopPolling;
    window.resetChatForNewConversation = resetChatForNewConversation;
    window.startNewConversation = startNewConversation;
    window.handleConversationClosed = handleConversationClosed;
    
    // Auto-initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            // Don't auto-init, wait for modal to open
        });
    }
    
})();

