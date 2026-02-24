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
    let assistantHistory = [];

    function isAssistantMode() {
        return window.chatAssistantMode === true;
    }

    function setAttachmentButtonForMode(assistantMode) {
        const attachmentBtn = document.getElementById('chatPhotoBtn');
        if (!attachmentBtn) {
            return;
        }

        if (assistantMode) {
            attachmentBtn.disabled = true;
            attachmentBtn.style.opacity = '0.55';
            attachmentBtn.title = 'Attachments are disabled in AI Assistant mode';
            if (typeof window.clearPendingChatAttachment === 'function') {
                window.clearPendingChatAttachment();
            }
        } else {
            attachmentBtn.disabled = false;
            attachmentBtn.style.opacity = '1';
            attachmentBtn.title = 'Attach photo, video, or email file';
        }
    }

    function assistantApiCandidates() {
        const candidates = [
            API_BASE + 'chatbot-assistant.php',
            'USERS/api/chatbot-assistant.php',
            'api/chatbot-assistant.php',
            '../USERS/api/chatbot-assistant.php',
            '../api/chatbot-assistant.php'
        ];
        return [...new Set(candidates)];
    }

    function applyAssistantUiState() {
        const chatInterface = document.getElementById('chatInterface');
        const userInfoForm = document.getElementById('chatUserInfoForm');
        if (chatInterface && userInfoForm) {
            userInfoForm.style.display = 'none';
            chatInterface.style.display = 'block';
        }

        const startNewBtn = document.getElementById('startNewConversationBtn');
        if (startNewBtn) {
            startNewBtn.style.display = 'none';
        }

        const closeBtn = document.getElementById('chatEndConversationBtn');
        if (closeBtn) {
            closeBtn.style.display = 'none';
        }

        const chatInput = document.getElementById('chatInput');
        if (chatInput) {
            chatInput.disabled = false;
            chatInput.placeholder = 'Ask the AI assistant...';
        }

        const chatSendBtn = document.getElementById('chatSendBtn');
        if (chatSendBtn) {
            chatSendBtn.disabled = false;
            chatSendBtn.textContent = 'Send';
        }

        const statusIndicator = document.getElementById('chatStatusIndicator');
        const statusText = document.getElementById('chatStatusText');
        if (statusIndicator && statusText) {
            statusIndicator.style.display = 'flex';
            statusIndicator.style.background = 'rgba(33, 150, 243, 0.1)';
            statusIndicator.style.borderLeftColor = '#2196f3';
            statusIndicator.style.color = '#1565c0';
            statusText.innerHTML = '<i class="fas fa-robot"></i> AI Assistant is online';
        }

        setAttachmentButtonForMode(true);
    }

    async function requestAssistantReply(message) {
        const payload = {
            message: message,
            history: assistantHistory.slice(-12),
            locale: navigator.language || 'en-US'
        };

        let lastError = null;
        for (const endpoint of assistantApiCandidates()) {
            try {
                const response = await fetch(endpoint, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const responseText = await response.text();
                let data = null;
                try {
                    data = JSON.parse(responseText);
                } catch (_) {
                    data = null;
                }

                if (response.ok && data && data.success && data.reply) {
                    return {
                        reply: String(data.reply),
                        emergencyDetected: data.emergencyDetected === true,
                        incidentType: typeof data.incidentType === 'string' ? data.incidentType : '',
                        incidentLabel: typeof data.incidentLabel === 'string' ? data.incidentLabel : '',
                        callLink: typeof data.callLink === 'string' ? data.callLink : ''
                    };
                }

                const apiMessage = (data && data.message) ? data.message : `HTTP ${response.status}`;
                lastError = new Error(apiMessage);
            } catch (error) {
                lastError = error;
            }
        }

        throw lastError || new Error('Unable to reach AI assistant endpoint.');
    }

    function resetChatMessagesForFreshLoad() {
        const chatMessages = document.querySelector('.chat-messages');
        if (!chatMessages) {
            return;
        }

        chatMessages.innerHTML = '';
        const systemMsg = document.createElement('div');
        systemMsg.className = 'chat-message chat-message-system';
        systemMsg.innerHTML = '<strong>System:</strong> For life-threatening emergencies, call Quezon City emergency hotline 122 immediately.';
        chatMessages.appendChild(systemMsg);
    }

    async function fetchConversationBootstrap(possiblePaths, queryParams) {
        let lastError = null;

        for (const basePath of possiblePaths) {
            const apiUrl = basePath + 'chat-get-conversation.php?' + new URLSearchParams(queryParams);
            console.log('Trying to fetch conversation from:', apiUrl);

            try {
                const response = await fetch(apiUrl);
                console.log('Response status:', response.status, 'from:', apiUrl);

                if (response.ok) {
                    const data = await response.json();
                    API_BASE = basePath;
                    console.log('API_BASE updated to:', API_BASE);
                    return { data, basePath, apiUrl };
                }

                if (response.status === 404) {
                    console.warn('404 error, trying next path...');
                    continue;
                }

                const errorText = await response.text();
                throw new Error(`HTTP error! status: ${response.status}${errorText ? `, message: ${errorText}` : ''}`);
            } catch (fetchError) {
                console.warn('Fetch error for', apiUrl, ':', fetchError.message);
                lastError = fetchError;
            }
        }

        if (lastError) {
            throw lastError;
        }
        return null;
    }
    
    // Initialize chat system
    async function initChat() {
        const persistedConversationId = sessionStorage.getItem('conversation_id') || window.currentConversationId || null;
        if (isInitialized) {
            const activeConversationId = conversationId || sessionStorage.getItem('conversation_id') || null;
            const sameConversation = activeConversationId && persistedConversationId
                ? String(activeConversationId) === String(persistedConversationId)
                : !persistedConversationId;
            if (sameConversation) {
                console.log('Chat already initialized');
                return true;
            }

            // Conversation context changed (for example, after incident reset).
            console.log('Detected conversation switch. Re-initializing chat runtime.');
            stopPolling();
            isInitialized = false;
            window.chatInitialized = false;
            lastMessageId = 0;
        }
        
        console.log('Initializing MySQL chat system...');

        if (isAssistantMode()) {
            console.log('Initializing AI assistant mode...');
            stopPolling();
            conversationId = null;
            sessionStorage.removeItem('conversation_id');
            window.currentConversationId = null;
            lastMessageId = 0;
            assistantHistory = [];

            resetChatMessagesForFreshLoad();
            applyAssistantUiState();

            // Starter message for first open.
            if (window.addMessageToChat) {
                window.addMessageToChat(
                    'Hello. I am your AI assistant. Describe your concern and I will guide you.',
                    'admin',
                    Date.now(),
                    null,
                    'AI Assistant'
                );
            }

            isInitialized = true;
            window.chatInitialized = true;
            return true;
        }
        
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

        setAttachmentButtonForMode(false);
        
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

            let data = null;
            const preferredConversationId = persistedConversationId ? String(persistedConversationId) : null;

            // 1) Prefer explicit conversation ID from session (created during start-chat proof upload).
            if (preferredConversationId) {
                try {
                    const preferredResult = await fetchConversationBootstrap(
                        possiblePaths,
                        { conversationId: preferredConversationId }
                    );
                    if (preferredResult && preferredResult.data && preferredResult.data.success) {
                        if (preferredResult.data.status === 'closed') {
                            console.log('Stored conversation is already closed. Clearing stale conversation_id.');
                            sessionStorage.removeItem('conversation_id');
                            if (window.currentConversationId && String(window.currentConversationId) === preferredConversationId) {
                                window.currentConversationId = null;
                            }
                        } else {
                            data = preferredResult.data;
                            console.log('Using stored conversation ID:', preferredConversationId);
                        }
                    }
                } catch (preferredError) {
                    console.warn('Unable to validate stored conversation ID, falling back to user lookup:', preferredError.message);
                }
            }

            // 2) Fallback: resolve by user profile (reuse active or create new).
            if (!data) {
                const fallbackResult = await fetchConversationBootstrap(possiblePaths, {
                    userId: userId,
                    userName: userName,
                    userEmail: userEmail || '',
                    userPhone: userPhone || '',
                    userLocation: userLocation || '',
                    userConcern: userConcern || '',
                    isGuest: isGuest ? '1' : '0'
                });

                if (!fallbackResult) {
                    throw new Error('Failed to fetch conversation from all configured API paths.');
                }
                data = fallbackResult.data;
            }

            console.log('Conversation response:', data);
            
            if (data && data.success) {
                const resolvedConversationId = data.conversationId ? String(data.conversationId) : null;
                if (!resolvedConversationId) {
                    throw new Error('Conversation API returned success without a conversation ID.');
                }

                conversationId = resolvedConversationId;
                sessionStorage.setItem('conversation_id', conversationId);
                window.currentConversationId = conversationId;
                
                // Clear closed handler flag since we have an active conversation
                window.conversationClosedHandled = false;
                
                console.log('Conversation ready:', conversationId);
                
                // Reset state for initial load to avoid carrying messages from previous threads.
                lastMessageId = 0;
                resetChatMessagesForFreshLoad();
                
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
                const chatCloseBtn = document.getElementById('chatEndConversationBtn');
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
            }

            console.error('Failed to get/create conversation:', data ? data.message : 'Unknown response');
            return false;
        } catch (error) {
            console.error('Error initializing chat:', error);
            alert('Failed to initialize chat: ' + error.message);
            return false;
        }
    }
    
    // Load messages
    async function loadMessages(isInitialLoad = false) {
        if (isAssistantMode()) {
            return;
        }

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
                                window.addMessageToChat(
                                    msg.text,
                                    msg.senderType,
                                    msg.timestamp,
                                    msg.id,
                                    adminName,
                                    {
                                        imageUrl: msg.imageUrl,
                                        attachmentMime: msg.attachmentMime || null,
                                        attachmentSize: msg.attachmentSize || null
                                    }
                                );
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
        if (isAssistantMode()) {
            return;
        }

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
    
    // Handle conversation closed by admin or citizen/user.
    function handleConversationClosed(closedByActor = null, closedByType = null) {
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
        
        const actorRaw = (closedByActor || '').toString().trim();
        const actorKey = actorRaw.toLowerCase();
        const inferredType = closedByType || (
            actorKey.includes('citizen') || actorKey.includes('user') || actorKey.includes('guest') || actorKey === 'you'
                ? 'citizen'
                : 'admin'
        );
        const closedLabel = inferredType === 'citizen' ? 'citizen/user' : 'administrator';

        if (closedModal && closedMessage) {
            // Set message text
            let messageText = `The chat was closed by the ${closedLabel}.`;
            if (actorRaw) {
                messageText = `The chat was closed by the ${closedLabel}: <strong>${escapeHtml(actorRaw)}</strong>.`;
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
            // Fallback: if modal doesn't exist, show alert and continue.
            let alertMessage = `The chat was closed by the ${closedLabel}.`;
            if (actorRaw) {
                alertMessage = `The chat was closed by the ${closedLabel}: ${actorRaw}.`;
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
                systemMsg.innerHTML = '<strong>System:</strong> For life-threatening emergencies, call Quezon City emergency hotline 122 immediately.';
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
        const chatCloseBtn = document.getElementById('chatEndConversationBtn');
        
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

            if (typeof window.clearInitialIncidentPhotoSelection === 'function') {
                window.clearInitialIncidentPhotoSelection();
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

        if (typeof window.updateUserInfoSubmitState === 'function') {
            window.updateUserInfoSubmitState();
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
        // Ensure previous thread is fully discarded before any new chat starts.
        window.conversationClosedHandled = false;
        conversationId = null;
        sessionStorage.removeItem('conversation_id');
        window.currentConversationId = null;
        lastMessageId = 0;
        isInitialized = false;
        window.chatInitialized = false;

        // Force new concern + new proof-photo flow.
        localStorage.removeItem('guest_concern');
        sessionStorage.removeItem('user_concern');

        const concernSelect = document.getElementById('userConcernSelect');
        if (concernSelect) {
            concernSelect.value = '';
        }

        if (typeof window.clearInitialIncidentPhotoSelection === 'function') {
            window.clearInitialIncidentPhotoSelection();
        }

        const chatMessages = document.querySelector('.chat-messages');
        if (chatMessages) {
            chatMessages.innerHTML = '';
            const systemMsgDiv = document.createElement('div');
            systemMsgDiv.className = 'chat-message chat-message-system';
            systemMsgDiv.innerHTML = '<strong>System:</strong> For life-threatening emergencies, call Quezon City emergency hotline 122 immediately.';
            chatMessages.appendChild(systemMsgDiv);
        }

        const statusIndicator = document.getElementById('chatStatusIndicator');
        const statusText = document.getElementById('chatStatusText');
        if (statusIndicator) {
            statusIndicator.style.display = 'none';
        }
        if (statusText) {
            statusText.textContent = '';
        }

        const chatInterface = document.getElementById('chatInterface');
        const userInfoForm = document.getElementById('chatUserInfoForm');
        if (chatInterface && userInfoForm) {
            chatInterface.style.display = 'none';
            userInfoForm.style.display = 'block';
        }

        const startNewBtn = document.getElementById('startNewConversationBtn');
        const chatCloseBtn = document.getElementById('chatEndConversationBtn');
        if (startNewBtn) {
            startNewBtn.style.display = 'none';
        }
        if (chatCloseBtn) {
            chatCloseBtn.style.display = 'none';
        }

        const chatInput = document.getElementById('chatInput');
        const chatSendBtn = document.getElementById('chatSendBtn');
        if (chatInput) {
            chatInput.disabled = true;
            chatInput.placeholder = 'Type your message...';
            chatInput.value = '';
        }
        if (chatSendBtn) {
            chatSendBtn.disabled = true;
            chatSendBtn.textContent = 'Send';
        }

        if (typeof window.updateUserInfoSubmitState === 'function') {
            window.updateUserInfoSubmitState();
        }

        setAttachmentButtonForMode(false);

        console.log('Prepared fresh conversation state; waiting for new form submission.');
    }
    
    // Reset chat for new conversation (kept for backward compatibility)
    function resetChatForNewConversation() {
        startNewConversation();
    }
    
    // Send message
    async function sendMessage(text, attachmentFile = null) {
        const normalizedText = typeof text === 'string' ? text.trim() : '';
        const pendingAttachment = (typeof window.getPendingChatAttachmentFile === 'function')
            ? window.getPendingChatAttachmentFile()
            : null;
        const fileToSend = attachmentFile instanceof File
            ? attachmentFile
            : (pendingAttachment instanceof File ? pendingAttachment : null);
        const hasAttachment = fileToSend instanceof File;
        if (!normalizedText && !hasAttachment) {
            console.warn('Cannot send empty message without attachment');
            return false;
        }

        if (isAssistantMode()) {
            if (hasAttachment) {
                throw new Error('Attachments are not supported in AI Assistant mode.');
            }

            if (window.addMessageToChat) {
                window.addMessageToChat(normalizedText, 'user', Date.now());
            }

            assistantHistory.push({ role: 'user', content: normalizedText });
            if (assistantHistory.length > 20) {
                assistantHistory = assistantHistory.slice(-20);
            }

            const replyPayload = await requestAssistantReply(normalizedText);
            const assistantReply = (replyPayload && replyPayload.reply != null ? String(replyPayload.reply) : '').trim();
            if (!assistantReply) {
                throw new Error('AI assistant returned an empty response.');
            }

            assistantHistory.push({ role: 'assistant', content: assistantReply });
            if (assistantHistory.length > 20) {
                assistantHistory = assistantHistory.slice(-20);
            }

            if (window.addMessageToChat) {
                window.addMessageToChat(
                    assistantReply,
                    'admin',
                    Date.now(),
                    null,
                    'AI Assistant'
                );
            }
            if (window.updateChatStatus) {
                if (replyPayload && replyPayload.emergencyDetected) {
                    window.updateChatStatus('assistant_emergency');
                } else {
                    window.updateChatStatus('admin');
                }
            }

            return true;
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
        
        console.log('Sending message:', {
            text: normalizedText,
            hasAttachment,
            conversationId,
            userId,
            userName
        });
        
        try {
            const formData = new FormData();
            formData.append('text', normalizedText);
            formData.append('userId', userId);
            formData.append('userName', userName);
            formData.append('userEmail', userEmail || '');
            formData.append('userPhone', userPhone || '');
            formData.append('userLocation', userLocation || '');
            formData.append('userConcern', userConcern || '');
            formData.append('isGuest', isGuest ? '1' : '0');
            formData.append('conversationId', conversationId);
            if (hasAttachment) {
                // Keep backward compatibility with the existing backend field.
                formData.append('attachment', fileToSend);
                formData.append('photo', fileToSend);
            }
            
            const apiUrl = API_BASE + 'chat-send.php';
            console.log('Sending to:', apiUrl);
                console.log('FormData contents:', {
                text: normalizedText,
                hasAttachment,
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
                    let immediateAttachmentUrl = data.imageUrl || (data.attachment && data.attachment.url) || null;
                    let immediateAttachmentMime = data.attachment ? data.attachment.mime : null;
                    let immediateAttachmentSize = data.attachment ? data.attachment.size : null;

                    // Render local media immediately after successful upload.
                    // This avoids broken previews when stream URL resolution is delayed.
                    if (hasAttachment && fileToSend instanceof File) {
                        const fileMime = String(fileToSend.type || '').toLowerCase();
                        const isPreviewableLocalMedia = fileMime.indexOf('image/') === 0 || fileMime.indexOf('video/') === 0;
                        if (isPreviewableLocalMedia && typeof URL !== 'undefined' && typeof URL.createObjectURL === 'function') {
                            try {
                                immediateAttachmentUrl = URL.createObjectURL(fileToSend);
                                immediateAttachmentMime = fileMime || immediateAttachmentMime;
                                immediateAttachmentSize = fileToSend.size || immediateAttachmentSize;
                                if (!Array.isArray(window.__chatBlobPreviewUrls)) {
                                    window.__chatBlobPreviewUrls = [];
                                }
                                window.__chatBlobPreviewUrls.push(immediateAttachmentUrl);
                            } catch (previewError) {
                                console.warn('Unable to create local attachment preview URL:', previewError);
                            }
                        }
                    }

                    window.addMessageToChat(
                        normalizedText,
                        'user',
                        timestamp,
                        data.messageId,
                        null,
                        {
                            imageUrl: immediateAttachmentUrl,
                            attachmentMime: immediateAttachmentMime,
                            attachmentSize: immediateAttachmentSize
                        }
                    );
                    console.log('Message added to chat UI immediately');
                } else {
                    console.warn('addMessageToChat function not available, message will appear after polling');
                }

                if (hasAttachment && typeof window.clearPendingChatAttachment === 'function') {
                    window.clearPendingChatAttachment();
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
        if (isAssistantMode()) {
            stopPolling();
            return;
        }

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
    function addMessageToChat(text, senderType, timestamp, messageId = null, senderName = null, attachment = null) {
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

        // Render plain URLs as safe clickable links inside message text.
        const linkifyMessageText = (rawText) => {
            const input = rawText == null ? '' : String(rawText);
            if (!input) return '';

            const urlRegex = /(https?:\/\/[^\s]+)/gi;
            let output = '';
            let lastIndex = 0;
            let match = null;

            while ((match = urlRegex.exec(input)) !== null) {
                const matchedUrl = match[0];
                output += escapeHtml(input.slice(lastIndex, match.index)).replace(/\n/g, '<br>');

                let urlText = matchedUrl;
                let trailing = '';
                while (urlText && /[.,!?)]$/.test(urlText)) {
                    trailing = urlText.slice(-1) + trailing;
                    urlText = urlText.slice(0, -1);
                }

                const safeUrl = sanitizeAttachmentUrl(urlText);
                if (safeUrl) {
                    output += `<a href="${safeUrl}" target="_blank" rel="noopener noreferrer" class="chat-inline-link" style="text-decoration: underline; word-break: break-all;">${escapeHtml(urlText)}</a>`;
                } else {
                    output += escapeHtml(matchedUrl);
                }

                if (trailing) {
                    output += escapeHtml(trailing);
                }

                lastIndex = match.index + matchedUrl.length;
            }

            output += escapeHtml(input.slice(lastIndex)).replace(/\n/g, '<br>');
            return output;
        };

        const appBasePath = (() => {
            const path = String(window.location.pathname || '').replace(/\\/g, '/');
            const lower = path.toLowerCase();
            const markers = ['/users/', '/admin/', '/php/'];
            for (const marker of markers) {
                const idx = lower.indexOf(marker);
                if (idx === 0) return '';
                if (idx > 0) return path.slice(0, idx).replace(/\/+$/, '');
            }
            const dir = path.replace(/\/[^/]*$/, '');
            if (!dir || dir === '/') return '';
            return dir.replace(/\/+$/, '');
        })();

        const sanitizeAttachmentUrl = (url) => {
            if (!url) return null;
            const raw = String(url).trim();
            if (!raw) return null;
            if (/^blob:/i.test(raw)) {
                return raw;
            }
            if (/^data:(image|video)\//i.test(raw)) {
                return raw;
            }
            if (raw.startsWith('/')) {
                if (
                    appBasePath &&
                    /^\/(USERS|ADMIN|PHP)\//i.test(raw) &&
                    raw.indexOf(appBasePath + '/') !== 0
                ) {
                    return appBasePath + raw;
                }
                return raw;
            }
            if (/^(USERS|ADMIN|PHP)\//i.test(raw)) {
                return appBasePath ? (appBasePath + '/' + raw) : ('/' + raw);
            }
            try {
                const parsed = new URL(raw, window.location.href);
                if (parsed.protocol === 'http:' || parsed.protocol === 'https:') {
                    return parsed.href;
                }
            } catch (_) {
                return null;
            }
            return null;
        };

        const normalizedText = (text == null ? '' : String(text)).trim();
        const attachmentUrl = sanitizeAttachmentUrl(attachment && (attachment.imageUrl || attachment.url));
        const attachmentMimeValue = attachment ? (attachment.attachmentMime || attachment.mime || '') : '';
        const attachmentMimeRaw = String(attachmentMimeValue == null ? '' : attachmentMimeValue).trim().toLowerCase();
        const attachmentMime = attachmentMimeRaw || null;
        const attachmentHintMatch = normalizedText.match(/^\[(photo|video|email|attachment)\]/i);
        const attachmentHint = attachmentHintMatch ? attachmentHintMatch[1].toLowerCase() : '';
        const isImageAttachment = !!(attachmentUrl && (
            (attachmentMime && attachmentMime.indexOf('image/') === 0) ||
            (!attachmentMime && (
                attachmentHint === 'photo' ||
                /\.(png|jpe?g|gif|webp|bmp|avif)(\?|$)/i.test(attachmentUrl)
            ))
        ));
        const isVideoAttachment = !!(attachmentUrl && (
            (attachmentMime && attachmentMime.indexOf('video/') === 0) ||
            (!attachmentMime && (
                attachmentHint === 'video' ||
                /\.(mp4|webm|ogv|mov|avi|mkv)(\?|$)/i.test(attachmentUrl)
            ))
        ));
        const isEmailAttachment = !!(attachmentUrl && (
            attachmentMime === 'message/rfc822' ||
            attachmentMime === 'application/eml' ||
            (!attachmentMime && attachmentHint === 'email') ||
            /\.eml(\?|$)/i.test(attachmentUrl)
        ));
        const hidePlaceholder = attachmentUrl && /^\[(photo|video|email|attachment)\]/i.test(normalizedText);

        const htmlParts = [];
        htmlParts.push(`<strong style="color: var(--text-color) !important;">${escapeHtml(displayName)}:</strong>`);
        if (normalizedText && !hidePlaceholder) {
            htmlParts.push(`<span style="color: var(--text-color) !important;">${linkifyMessageText(normalizedText)}</span>`);
        }
        if (attachmentUrl) {
            if (isVideoAttachment) {
                htmlParts.push(`<div class="chat-message-video-link"><video class="chat-message-video" controls preload="metadata" playsinline><source src="${attachmentUrl}"${attachmentMime ? ` type="${attachmentMime}"` : ''}>Your browser does not support video playback.</video></div>`);
            } else if (isImageAttachment) {
                htmlParts.push(`<a href="${attachmentUrl}" target="_blank" rel="noopener noreferrer" class="chat-message-image-link"><img src="${attachmentUrl}" class="chat-message-image" alt="Incident attachment"></a>`);
            } else {
                const fileLabel = isEmailAttachment ? 'Open email attachment (.eml)' : 'Open attachment';
                const fileIcon = isEmailAttachment ? 'fa-envelope-open-text' : 'fa-paperclip';
                htmlParts.push(`<a href="${attachmentUrl}" target="_blank" rel="noopener noreferrer" class="chat-message-file-link"><i class="fas ${fileIcon}"></i> ${fileLabel}</a>`);
            }
        }
        htmlParts.push(`<small style="display: block; font-size: 0.8em; opacity: 0.7; margin-top: 0.25rem; color: var(--text-muted) !important;">${time}</small>`);

        msg.innerHTML = htmlParts.join(' ');
        chatMessages.appendChild(msg);
        chatMessages.scrollTop = chatMessages.scrollHeight;
        
        console.log('Message added to UI:', {
            text: normalizedText,
            senderType,
            messageId,
            senderName: displayName,
            attachmentUrl,
            attachmentMime
        });
    }
    
    // Expose functions globally
    window.initChatMySQL = initChat;
    window.sendChatMessageMySQL = sendMessage;
    window.addMessageToChat = addMessageToChat;
    window.stopChatPolling = stopPolling;
    window.resetChatForNewConversation = resetChatForNewConversation;
    window.startNewConversation = startNewConversation;
    window.handleConversationClosed = handleConversationClosed;
    window.enableChatAssistantMode = function () {
        window.chatAssistantMode = true;
        stopPolling();
        conversationId = null;
        sessionStorage.removeItem('conversation_id');
        window.currentConversationId = null;
        window.chatInitialized = false;
        isInitialized = false;
    };
    window.disableChatAssistantMode = function () {
        window.chatAssistantMode = false;
        assistantHistory = [];
        setAttachmentButtonForMode(false);
        window.chatInitialized = false;
        isInitialized = false;
    };
    
    // Auto-initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            // Don't auto-init, wait for modal to open
        });
    }
    
})();

