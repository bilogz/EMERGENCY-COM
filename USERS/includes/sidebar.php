<?php
// User-facing sidebar, modeled after the admin sidebar
// Use provided $assetSidebar if set (from root), otherwise default to relative path (from USERS)
if (!isset($assetSidebar)) {
    $assetSidebar = '../ADMIN/sidebar/';
}
// Use provided $basePath if set (from root), otherwise default to empty (from USERS)
if (!isset($basePath)) {
    $basePath = '';
}
// Detect if we're in root context (explicitly set flag from root index.php)
if (!isset($isRootContext)) {
    $isRootContext = false;
}
$linkPrefix = $isRootContext ? 'USERS/' : '';
$current = basename($_SERVER['PHP_SELF']);

// Check if user is logged in and is a registered user (not guest)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$isLoggedIn = isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] === true;
$isRegisteredUser = isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'registered';
$showProfile = $isLoggedIn && $isRegisteredUser;

// Include guest monitoring notice
include __DIR__ . '/guest-monitoring-notice.php';
?>
<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <div class="sidebar-brand">
            <div class="brand-logo">
                <?php if ($showProfile): ?>
                    <a href="<?= $basePath ?><?= $linkPrefix ?>profile.php" class="logo-link" title="View Profile">
                        <img src="<?= $assetSidebar ?>images/logo.svg" alt="Logo" class="logo-img">
                    </a>
                <?php else: ?>
                    <a href="<?= $isRootContext ? 'index.php' : '../index.php' ?>" class="logo-link">
                        <img src="<?= $assetSidebar ?>images/logo.svg" alt="Logo" class="logo-img">
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="sidebar-content">
        <nav class="sidebar-nav">
            <div class="sidebar-section">
                <h3 class="sidebar-section-title">User</h3>
                <ul class="sidebar-menu">
                    <li class="sidebar-menu-item">
                        <a href="<?= $isRootContext ? 'index.php' : '../index.php' ?>" class="sidebar-link <?= ($current === 'index.php' || $current === 'home.php') ? 'active' : '' ?>">
                            <i class="fas fa-home"></i>
                            <span>Home</span>
                        </a>
                    </li>
                    <li class="sidebar-menu-item">
                        <a href="<?= $basePath ?><?= $linkPrefix ?>alerts.php" class="sidebar-link <?= $current === 'alerts.php' ? 'active' : '' ?>">
                            <i class="fas fa-bell"></i>
                            <span>Alerts</span>
                        </a>
                    </li>
                    <?php if ($showProfile): ?>
                    <li class="sidebar-menu-item">
                        <a href="<?= $basePath ?><?= $linkPrefix ?>profile.php" class="sidebar-link <?= $current === 'profile.php' ? 'active' : '' ?>">
                            <i class="fas fa-user-cog"></i>
                            <span>Profile</span>
                        </a>
                    </li>
                    <?php endif; ?>
                    <li class="sidebar-menu-item">
                        <a href="<?= $basePath ?><?= $linkPrefix ?>support.php" class="sidebar-link <?= $current === 'support.php' ? 'active' : '' ?>">
                            <i class="fas fa-life-ring"></i>
                            <span>Support</span>
                        </a>
                    </li>
                </ul>
            </div>

            <div class="sidebar-section">
                <h3 class="sidebar-section-title">Emergency</h3>
                <ul class="sidebar-menu">
                    <li class="sidebar-menu-item">
                        <a href="<?= $basePath ?><?= $linkPrefix ?>emergency-call.php" class="sidebar-link <?= $current === 'emergency-call.php' ? 'active' : '' ?>">
                            <i class="fas fa-phone-alt"></i>
                            <span>Emergency Call</span>
                        </a>
                    </li>
                </ul>
            </div>
        </nav>
    </div>
</aside>

<div class="sidebar-overlay" id="sidebarOverlay"></div>

<div class="auth-icons">
    <button class="auth-icon-link" id="languageSelectorBtn" title="Change Language" aria-label="Select Language">
        <i class="fas fa-globe"></i>
    </button>
    <a href="<?= $basePath ?><?= $linkPrefix ?>login.php" class="auth-icon-link" title="Login / Sign Up">
        <i class="fas fa-user-circle"></i>
    </a>
</div>

<!-- Floating chat button and modal -->
<button class="chat-fab" id="chatFab" aria-label="Open chat" title="Any concerns? Contact support">
    <i class="fas fa-comments"></i>
    <span class="chat-tooltip" id="chatTooltip">Any concerns? Contact support</span>
</button>

<div class="chat-modal" id="chatModal" aria-hidden="true">
    <div class="chat-modal-content">
        <div class="chat-modal-header">
            <h3>Quick Assistance</h3>
            <button class="chat-close-btn" id="chatCloseBtn" aria-label="Close chat">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="chat-modal-body">
            <!-- User Info Form (shown for anonymous/guest users) -->
            <div class="chat-user-info-form" id="chatUserInfoForm" style="display: none;">
                <h4 style="margin: 0 0 1rem 0; font-size: 1rem;">Please provide your information to start chatting</h4>
                <form id="userInfoForm">
                    <div class="form-group" style="margin-bottom: 0.75rem;">
                        <label for="userNameInput" style="display: block; margin-bottom: 0.25rem; font-size: 0.85rem; font-weight: 500;">Full Name *</label>
                        <input type="text" id="userNameInput" name="name" required placeholder="Enter your full name" style="width: 100%; padding: 0.5rem 0.65rem; border-radius: 8px; border: 1px solid var(--card-border);">
                    </div>
                    <div class="form-group" style="margin-bottom: 0.75rem;">
                        <label for="userContactInput" style="display: block; margin-bottom: 0.25rem; font-size: 0.85rem; font-weight: 500;">Contact Number *</label>
                        <input type="tel" id="userContactInput" name="contact" required placeholder="09XX XXX XXXX" style="width: 100%; padding: 0.5rem 0.65rem; border-radius: 8px; border: 1px solid var(--card-border);">
                    </div>
                    <div class="form-group" style="margin-bottom: 0.75rem;">
                        <label for="userLocationInput" style="display: block; margin-bottom: 0.25rem; font-size: 0.85rem; font-weight: 500;">Location *</label>
                        <input type="text" id="userLocationInput" name="location" required placeholder="Your current location or address" style="width: 100%; padding: 0.5rem 0.65rem; border-radius: 8px; border: 1px solid var(--card-border);">
                    </div>
                    <div class="form-group" style="margin-bottom: 1rem;">
                        <label for="userConcernSelect" style="display: block; margin-bottom: 0.25rem; font-size: 0.85rem; font-weight: 500;">What is your concern? *</label>
                        <select id="userConcernSelect" name="concern" required style="width: 100%; padding: 0.5rem 0.65rem; border-radius: 8px; border: 1px solid var(--card-border);">
                            <option value="">Select a concern...</option>
                            <option value="emergency">Emergency</option>
                            <option value="medical">Medical Assistance</option>
                            <option value="fire">Fire Emergency</option>
                            <option value="police">Police Assistance</option>
                            <option value="disaster">Disaster/Weather</option>
                            <option value="general">General Inquiry</option>
                            <option value="complaint">Complaint</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary" style="width: 100%; padding: 0.65rem;">Start Chat</button>
                </form>
            </div>

            <!-- Chat Interface (shown after user info is submitted) -->
            <div class="chat-interface" id="chatInterface" style="display: none;">
                <p class="chat-hint">
                    This is a demo chat panel. Describe your emergency or question and a responder can reply using this channel.
                </p>
                <div class="chat-status-indicator" id="chatStatusIndicator" style="display: none; padding: 0.75rem 1rem; margin-bottom: 1rem; background: rgba(255, 193, 7, 0.1); border-left: 4px solid #ffc107; border-radius: 4px; color: #856404; font-size: 0.9rem;">
                    <i class="fas fa-clock"></i> <span id="chatStatusText">Waiting for admin reply...</span>
                </div>
                <div class="chat-messages" id="chatMessages">
                    <div class="chat-message chat-message-system">
                        <strong>System:</strong> For life-threatening emergencies, call 911 or the Quezon City emergency hotlines immediately.
                    </div>
                </div>
                <div class="chat-input-row" id="chatForm">
                    <input type="text" id="chatInput" placeholder="Type your message..." autocomplete="off">
                    <button type="button" id="chatSendBtn" class="btn btn-primary">Send</button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Firebase SDK -->
<script src="https://www.gstatic.com/firebasejs/12.7.0/firebase-app.js"></script>
<script src="https://www.gstatic.com/firebasejs/12.7.0/firebase-database.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.getElementById('sidebar');
    const sidebarOverlay = document.getElementById('sidebarOverlay');
    const chatFab = document.getElementById('chatFab');
    const chatModal = document.getElementById('chatModal');
    const chatCloseBtn = document.getElementById('chatCloseBtn');
    const chatForm = document.getElementById('chatForm');
    const chatInput = document.getElementById('chatInput');
    const chatMessages = document.querySelector('.chat-messages');
    
    function toggleSidebar() {
        sidebar.classList.toggle('sidebar-open');
        sidebarOverlay.classList.toggle('sidebar-overlay-open');
        document.body.classList.toggle('sidebar-open');
    }
    
    function closeSidebar() {
        sidebar.classList.remove('sidebar-open');
        sidebarOverlay.classList.remove('sidebar-overlay-open');
        document.body.classList.remove('sidebar-open');
    }

    window.sidebarToggle = toggleSidebar;
    window.sidebarClose = closeSidebar;
    
    if (sidebarOverlay) {
        sidebarOverlay.addEventListener('click', closeSidebar);
    }
    
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && sidebar.classList.contains('sidebar-open')) {
            closeSidebar();
        }
    });

    // Chat modal behaviour - keep it open until user closes it
    function openChat() {
        if (!chatModal) {
            console.error('Chat modal not found');
            return;
        }
        
        chatModal.classList.add('chat-modal-open');
        chatModal.setAttribute('aria-hidden', 'false');
        chatModal.style.pointerEvents = 'auto';
        chatModal.style.zIndex = '9999';
        document.body.style.overflow = 'hidden'; // Prevent background scrolling
        
        // Ensure content is clickable
        const content = chatModal.querySelector('.chat-modal-content');
        if (content) {
            content.style.pointerEvents = 'auto';
            content.style.zIndex = '10000';
        }
        
        // Initialize Firebase chat if not already done
        if (window.initFirebaseChat && !window.chatInitialized) {
            window.initFirebaseChat().then(() => {
                console.log('Firebase chat initialized');
            }).catch(err => {
                console.error('Failed to initialize Firebase chat:', err);
            });
        }
        
        // Re-attach button handlers when modal opens
        setTimeout(() => {
            const sendBtn = document.getElementById('chatSendBtn');
            const input = document.getElementById('chatInput');
            if (sendBtn && input && window.sendChatMessage) {
                // Remove old listeners
                const newBtn = sendBtn.cloneNode(true);
                sendBtn.parentNode.replaceChild(newBtn, sendBtn);
                const freshBtn = document.getElementById('chatSendBtn');
                
                freshBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    if (window.sendChatMessage) {
                        window.sendChatMessage();
                    }
                });
                
                input.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter' && !e.shiftKey) {
                        e.preventDefault();
                        if (window.sendChatMessage) {
                            window.sendChatMessage();
                        }
                    }
                });
            }
            
            if (input) {
                input.focus();
            }
        }, 150);
    }

    function closeChat() {
        if (!chatModal) return;
        chatModal.classList.remove('chat-modal-open');
        chatModal.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = ''; // Restore scrolling
    }
    
    // Store original closeChat before overriding
    const originalCloseChat = closeChat;
    let chatClosingIntentionally = false;
    
    // Override closeChat to set flag
    function closeChatWithFlag() {
        chatClosingIntentionally = true;
        originalCloseChat();
        setTimeout(() => {
            chatClosingIntentionally = false;
        }, 100);
    }
    
    // Expose openChat globally so it can be called from other pages
    window.openChat = openChat;
    window.closeChat = closeChatWithFlag;

    if (chatFab) {
        // Ensure button is clickable
        chatFab.style.pointerEvents = 'auto';
        chatFab.style.position = 'fixed';
        chatFab.style.zIndex = '1350';
        
        // Use direct event listener without cloning to avoid issues
        chatFab.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            e.stopImmediatePropagation();
            console.log('Chat button clicked');
            openChat();
        }, true); // Use capture phase to ensure it fires
        
        // Also add touch event for mobile
        chatFab.addEventListener('touchend', function(e) {
            e.preventDefault();
            e.stopPropagation();
            e.stopImmediatePropagation();
            console.log('Chat button touched');
            openChat();
        }, true);
        
        // Fallback: ensure button is accessible
        chatFab.setAttribute('tabindex', '0');
        chatFab.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                openChat();
            }
        });
    }

    if (chatCloseBtn) {
        chatCloseBtn.addEventListener('click', closeChatWithFlag);
    }
    
    // Add MutationObserver to prevent modal from closing unexpectedly
    if (chatModal) {
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
                    // If modal class is being removed but we didn't call closeChat, restore it
                    if (!chatModal.classList.contains('chat-modal-open') && 
                        document.body.style.overflow === 'hidden' &&
                        !chatClosingIntentionally) {
                        // Modal was closed unexpectedly, reopen it
                        setTimeout(() => {
                            if (chatModal && !chatClosingIntentionally) {
                                chatModal.classList.add('chat-modal-open');
                                chatModal.setAttribute('aria-hidden', 'false');
                                chatModal.style.pointerEvents = 'auto';
                                chatModal.style.zIndex = '9999';
                            }
                        }, 10);
                    } else if (chatModal.classList.contains('chat-modal-open')) {
                        // Ensure pointer events are enabled when modal is open
                        chatModal.style.pointerEvents = 'auto';
                        chatModal.style.zIndex = '9999';
                        const content = chatModal.querySelector('.chat-modal-content');
                        if (content) {
                            content.style.pointerEvents = 'auto';
                            content.style.zIndex = '10000';
                        }
                    }
                }
            });
        });
        
        observer.observe(chatModal, {
            attributes: true,
            attributeFilter: ['class']
        });
    }

    // Only close on Escape key or close button click - keep modal open otherwise
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && chatModal && chatModal.classList.contains('chat-modal-open')) {
            closeChatWithFlag();
        }
    });

    // Don't close when clicking outside - only close button
    // Removed the click-outside-to-close functionality to keep modal open

    // Initialize Firebase Chat
    let chatInitialized = false;
    
    async function initFirebaseChat() {
        if (chatInitialized || window.chatInitialized) {
            console.log('Chat already initialized, skipping...');
            return;
        }
        
        console.log('Starting Firebase chat initialization...');
        
        // Load Firebase SDKs
        if (typeof firebase === 'undefined') {
            // Load Firebase scripts
            const firebaseAppScript = document.createElement('script');
            firebaseAppScript.type = 'module';
            firebaseAppScript.src = 'https://www.gstatic.com/firebasejs/12.7.0/firebase-app.js';
            document.head.appendChild(firebaseAppScript);
            
            const firebaseDatabaseScript = document.createElement('script');
            firebaseDatabaseScript.type = 'module';
            firebaseDatabaseScript.src = 'https://www.gstatic.com/firebasejs/12.7.0/firebase-database.js';
            document.head.appendChild(firebaseDatabaseScript);
            
            // Wait for Firebase to load
            await new Promise((resolve) => {
                const checkFirebase = setInterval(() => {
                    if (typeof firebase !== 'undefined' && firebase.database) {
                        clearInterval(checkFirebase);
                        resolve();
                    }
                }, 100);
            });
        }
        
        // Initialize Firebase
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
        
        // Get user info from PHP session or localStorage
        let userId = sessionStorage.getItem('user_id');
        let userName = sessionStorage.getItem('user_name');
        let userEmail = sessionStorage.getItem('user_email');
        let userPhone = sessionStorage.getItem('user_phone');
        let isGuest = false;
        
        // Try to get from PHP session if available
        <?php 
        session_start();
        if (isset($_SESSION['user_id'])): 
        ?>
        userId = '<?php echo $_SESSION['user_id']; ?>';
        sessionStorage.setItem('user_id', userId);
        <?php endif; ?>
        
        <?php if (isset($_SESSION['user_name'])): ?>
        userName = '<?php echo addslashes($_SESSION['user_name']); ?>';
        sessionStorage.setItem('user_name', userName);
        <?php endif; ?>
        
        <?php if (isset($_SESSION['user_email'])): ?>
        userEmail = '<?php echo addslashes($_SESSION['user_email']); ?>';
        sessionStorage.setItem('user_email', userEmail);
        <?php endif; ?>
        
        <?php if (isset($_SESSION['user_phone'])): ?>
        userPhone = '<?php echo addslashes($_SESSION['user_phone']); ?>';
        sessionStorage.setItem('user_phone', userPhone);
        <?php endif; ?>
        
        // Fallback to guest if no user info
        if (!userId || userId === 'null' || userId === 'undefined') {
            // Generate a persistent guest ID based on browser fingerprint
            let guestId = localStorage.getItem('guest_user_id');
            if (!guestId) {
                guestId = 'guest_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
                localStorage.setItem('guest_user_id', guestId);
            }
            userId = guestId;
            sessionStorage.setItem('user_id', userId);
            isGuest = true;
        }
        if (!userName || userName === 'null' || userName === 'undefined') {
            userName = 'Guest User';
            sessionStorage.setItem('user_name', userName);
        }
        
        // Store user type
        sessionStorage.setItem('is_guest', isGuest ? 'true' : 'false');
        
        // Check if guest user has already provided info
        const guestInfoProvided = localStorage.getItem('guest_info_provided') === 'true';
        const storedGuestName = localStorage.getItem('guest_name');
        const storedGuestContact = localStorage.getItem('guest_contact');
        const storedGuestLocation = localStorage.getItem('guest_location');
        const storedGuestConcern = localStorage.getItem('guest_concern');
        
        // If guest and info not provided, show form first
        if (isGuest && !guestInfoProvided) {
            const userInfoForm = document.getElementById('chatUserInfoForm');
            const chatInterface = document.getElementById('chatInterface');
            if (userInfoForm && chatInterface) {
                userInfoForm.style.display = 'block';
                chatInterface.style.display = 'none';
                
                // Handle form submission
                const form = document.getElementById('userInfoForm');
                if (form) {
                    form.addEventListener('submit', async function(e) {
                        e.preventDefault();
                        
                        const name = document.getElementById('userNameInput').value.trim();
                        const contact = document.getElementById('userContactInput').value.trim();
                        const location = document.getElementById('userLocationInput').value.trim();
                        const concern = document.getElementById('userConcernSelect').value;
                        
                        if (!name || !contact || !location || !concern) {
                            alert('Please fill in all required fields.');
                            return;
                        }
                        
                        // Store user info
                        userName = name;
                        userPhone = contact;
                        const userLocation = location;
                        const userConcern = concern;
                        
                        // Save to localStorage
                        localStorage.setItem('guest_info_provided', 'true');
                        localStorage.setItem('guest_name', name);
                        localStorage.setItem('guest_contact', contact);
                        localStorage.setItem('guest_location', location);
                        localStorage.setItem('guest_concern', concern);
                        
                        // Update sessionStorage
                        sessionStorage.setItem('user_name', name);
                        sessionStorage.setItem('user_phone', contact);
                        sessionStorage.setItem('user_location', location);
                        sessionStorage.setItem('user_concern', concern);
                        
                        // Hide form and show chat interface
                        userInfoForm.style.display = 'none';
                        chatInterface.style.display = 'block';
                        
                        // Initialize chat with user info
                        await initializeChatWithUserInfo(database, userId, userName, userEmail, userPhone, isGuest, userLocation, userConcern);
                    });
                }
                return; // Don't initialize chat yet, wait for form submission
            }
        } else if (isGuest && guestInfoProvided) {
            // Guest has provided info before, use stored values
            if (storedGuestName) userName = storedGuestName;
            if (storedGuestContact) userPhone = storedGuestContact;
            sessionStorage.setItem('user_name', userName);
            sessionStorage.setItem('user_phone', userPhone);
            if (storedGuestLocation) sessionStorage.setItem('user_location', storedGuestLocation);
            if (storedGuestConcern) sessionStorage.setItem('user_concern', storedGuestConcern);
        }
        
        // Get user location and concern from sessionStorage if available
        const userLocation = sessionStorage.getItem('user_location') || localStorage.getItem('guest_location') || null;
        const userConcern = sessionStorage.getItem('user_concern') || localStorage.getItem('guest_concern') || null;
        
        // Get or create conversation - use persistent guest ID for guests
        const conversationsRef = database.ref('conversations');
        const userConversationsRef = conversationsRef.orderByChild('userId').equalTo(userId);
        
        let conversationId;
        const snapshot = await userConversationsRef.once('value');
        if (snapshot.exists()) {
            const conversations = snapshot.val();
            // Get the most recent active conversation
            const convEntries = Object.entries(conversations);
            const activeConv = convEntries.find(([key, val]) => val.status === 'active');
            if (activeConv) {
                conversationId = activeConv[0];
            } else {
                conversationId = convEntries[0][0]; // Use first conversation if no active
            }
        } else {
            const newConversationRef = conversationsRef.push({
                userId: userId,
                userName: userName,
                userEmail: userEmail || null,
                userPhone: userPhone || null,
                userLocation: userLocation || null,
                userConcern: userConcern || null,
                isGuest: isGuest,
                status: 'active',
                createdAt: firebase.database.ServerValue.TIMESTAMP,
                updatedAt: firebase.database.ServerValue.TIMESTAMP
            });
            conversationId = newConversationRef.key;
        }
        
        // Store conversation ID for later use
        sessionStorage.setItem('conversation_id', conversationId);
        
        // Continue with chat initialization
        continueChatInitialization(database, conversationId, userId, userName, userEmail, userPhone, isGuest, userLocation, userConcern);
        
        // Function to initialize chat with user info (called after form submission)
        async function initializeChatWithUserInfo(database, userId, userName, userEmail, userPhone, isGuest, userLocation, userConcern) {
            const conversationsRef = database.ref('conversations');
            const userConversationsRef = conversationsRef.orderByChild('userId').equalTo(userId);
            
            let conversationId;
            const snapshot = await userConversationsRef.once('value');
            if (snapshot.exists()) {
                const conversations = snapshot.val();
                const convEntries = Object.entries(conversations);
                const activeConv = convEntries.find(([key, val]) => val.status === 'active');
                if (activeConv) {
                    conversationId = activeConv[0];
                    // Update existing conversation with new info
                    await database.ref(`conversations/${conversationId}`).update({
                        userName: userName,
                        userPhone: userPhone,
                        userLocation: userLocation,
                        userConcern: userConcern,
                        updatedAt: firebase.database.ServerValue.TIMESTAMP
                    });
                } else {
                    conversationId = convEntries[0][0];
                }
            } else {
                const newConversationRef = conversationsRef.push({
                    userId: userId,
                    userName: userName,
                    userEmail: userEmail || null,
                    userPhone: userPhone || null,
                    userLocation: userLocation || null,
                    userConcern: userConcern || null,
                    isGuest: isGuest,
                    status: 'active',
                    createdAt: firebase.database.ServerValue.TIMESTAMP,
                    updatedAt: firebase.database.ServerValue.TIMESTAMP
                });
                conversationId = newConversationRef.key;
            }
            
            // Store conversation ID
            sessionStorage.setItem('conversation_id', conversationId);
            
            // Continue with chat initialization (load messages, etc.)
            continueChatInitialization(database, conversationId, userId, userName, userEmail, userPhone, isGuest, userLocation, userConcern);
        }
        
        // Function to continue chat initialization (load messages, setup listeners, etc.)
        function continueChatInitialization(database, conversationId, userId, userName, userEmail, userPhone, isGuest, userLocation, userConcern) {
            // Show chat interface and hide form if it exists
            const userInfoForm = document.getElementById('chatUserInfoForm');
            const chatInterface = document.getElementById('chatInterface');
            if (userInfoForm && chatInterface) {
                userInfoForm.style.display = 'none';
                chatInterface.style.display = 'block';
            }
        
            // Track loaded message IDs to prevent duplicates
            const loadedMessageIds = new Set();
            let lastMessageSenderType = null;
        
            // Load existing messages
            const messagesRef = database.ref(`messages/${conversationId}`);
            messagesRef.once('value', (snapshot) => {
            if (snapshot.exists()) {
                const messages = snapshot.val();
                const sortedMessages = Object.entries(messages).sort((a, b) => 
                    (a[1].timestamp || 0) - (b[1].timestamp || 0)
                );
                
                sortedMessages.forEach(([msgId, msg]) => {
                    loadedMessageIds.add(msgId);
                    addMessageToChat(msg.text, msg.senderType, msg.timestamp);
                    lastMessageSenderType = msg.senderType;
                });
                
                // Update status based on last message
                updateChatStatus(lastMessageSenderType);
            }
        });
        
            // Listen for new messages only
            messagesRef.on('child_added', (snapshot) => {
                const messageId = snapshot.key;
                if (!loadedMessageIds.has(messageId)) {
                    loadedMessageIds.add(messageId);
                    const message = snapshot.val();
                    addMessageToChat(message.text, message.senderType, message.timestamp);
                    lastMessageSenderType = message.senderType;
                    
                    // Update status when new message arrives
                    updateChatStatus(message.senderType);
                }
            });
            
            // Listen for conversation status updates
            const conversationRef = database.ref(`conversations/${conversationId}`);
            conversationRef.on('value', (snapshot) => {
                const conversation = snapshot.val();
                if (conversation) {
                    // Check if admin has accepted/assigned
                    if (conversation.assignedTo) {
                        updateChatStatus('admin_assigned');
                    } else if (conversation.status === 'active') {
                        // Check last message to determine status
                        if (lastMessageSenderType === 'user') {
                            updateChatStatus('waiting');
                        } else if (lastMessageSenderType === 'admin') {
                            updateChatStatus('admin_replied');
                        }
                    }
                }
            });
            
            // Update chat form to send via Firebase - use button click instead of form submit
            // Wait for DOM to be ready
            const chatSendBtn = document.getElementById('chatSendBtn');
            const chatInput = document.getElementById('chatInput');
            
            // Function to send message
            async function sendChatMessage() {
            const text = chatInput ? chatInput.value.trim() : '';
            if (!text) {
                console.warn('Cannot send empty message');
                return;
            }
            
            // Check if conversationId exists, if not try to get from sessionStorage or initialize
            if (!conversationId) {
                conversationId = sessionStorage.getItem('conversation_id');
                if (!conversationId) {
                    console.error('Cannot send message: conversationId missing. Initializing Firebase...');
                    // Try to initialize if not done
                    if (window.initFirebaseChat && !window.chatInitialized) {
                        try {
                            await window.initFirebaseChat();
                            conversationId = sessionStorage.getItem('conversation_id');
                            if (!conversationId) {
                                console.error('Still no conversationId after initialization');
                                alert('Chat is not ready. Please wait a moment and try again.');
                                return;
                            }
                        } catch (err) {
                            console.error('Failed to initialize Firebase:', err);
                            alert('Failed to initialize chat. Please refresh the page.');
                            return;
                        }
                    } else {
                        alert('Chat is not ready. Please wait a moment and try again.');
                        return;
                    }
                }
            }
            
            // Disable send button temporarily
            if (chatSendBtn) {
                chatSendBtn.disabled = true;
                chatSendBtn.textContent = 'Sending...';
            }
            
            try {
                // CRITICAL: Ensure modal stays open - force it to remain open
                if (chatModal) {
                    chatModal.classList.add('chat-modal-open');
                    chatModal.setAttribute('aria-hidden', 'false');
                    document.body.style.overflow = 'hidden';
                }
                
                // Show waiting status immediately
                updateChatStatus('waiting');
                lastMessageSenderType = 'user';
                
                // Add message to UI immediately
                addMessageToChat(text, 'user', Date.now());
                if (chatInput) {
                    chatInput.value = '';
                }
                
                // Get user location and concern from sessionStorage
                const currentUserLocation = sessionStorage.getItem('user_location') || userLocation || null;
                const currentUserConcern = sessionStorage.getItem('user_concern') || userConcern || null;
                
                // Send to Firebase with all user info for admin visibility
                const messageData = {
                    text: text,
                    senderId: userId,
                    senderName: userName,
                    senderEmail: userEmail || null,
                    senderPhone: userPhone || null,
                    senderLocation: currentUserLocation,
                    senderConcern: currentUserConcern,
                    isGuest: isGuest,
                    senderType: 'user',
                    timestamp: firebase.database.ServerValue.TIMESTAMP,
                    read: false
                };
                
                const messageRef = database.ref(`messages/${conversationId}`).push(messageData);
                
                // Update conversation with user info
                database.ref(`conversations/${conversationId}`).update({
                    lastMessage: text,
                    lastMessageTime: firebase.database.ServerValue.TIMESTAMP,
                    updatedAt: firebase.database.ServerValue.TIMESTAMP,
                    status: 'active',
                    userId: userId,
                    userName: userName,
                    userEmail: userEmail || null,
                    userPhone: userPhone || null,
                    userLocation: currentUserLocation,
                    userConcern: currentUserConcern,
                    isGuest: isGuest
                });
                
                // Add to chat queue for admin with full user info
                database.ref('chat_queue').push({
                    conversationId: conversationId,
                    userId: userId,
                    userName: userName,
                    userEmail: userEmail || null,
                    userPhone: userPhone || null,
                    userLocation: currentUserLocation,
                    userConcern: currentUserConcern,
                    isGuest: isGuest,
                    message: text,
                    timestamp: firebase.database.ServerValue.TIMESTAMP,
                    status: 'pending'
                });
                
                console.log('Message sent successfully:', messageRef.key);
            } catch (error) {
                console.error('Error sending message:', error);
                alert('Failed to send message. Please try again.');
            } finally {
                // Re-enable send button
                if (chatSendBtn) {
                    chatSendBtn.disabled = false;
                    chatSendBtn.textContent = 'Send';
                }
                
                // Keep focus on input and ensure modal stays open
                setTimeout(() => {
                    if (chatInput) {
                        chatInput.focus();
                    }
                    // Double-check modal is still open
                    if (chatModal && !chatModal.classList.contains('chat-modal-open')) {
                        chatModal.classList.add('chat-modal-open');
                        chatModal.setAttribute('aria-hidden', 'false');
                        document.body.style.overflow = 'hidden';
                    }
                }, 100);
            }
            }
            
            // Make sendChatMessage available globally
            window.sendChatMessage = sendChatMessage;
            
            // Function to attach send button handlers
            function attachSendButtonHandlers() {
                const sendBtn = document.getElementById('chatSendBtn');
                const input = document.getElementById('chatInput');
                
                if (sendBtn && input) {
                    // Remove old listeners by cloning
                    const newBtn = sendBtn.cloneNode(true);
                    sendBtn.parentNode.replaceChild(newBtn, sendBtn);
                    const freshBtn = document.getElementById('chatSendBtn');
                    
                    // Attach click handler
                    freshBtn.addEventListener('click', function(e) {
                        e.preventDefault();
                        e.stopPropagation();
                        e.stopImmediatePropagation();
                        if (window.sendChatMessage) {
                            window.sendChatMessage();
                        } else {
                            console.error('sendChatMessage function not available');
                        }
                    });
                    
                    // Attach Enter key handler
                    input.addEventListener('keypress', function(e) {
                        if (e.key === 'Enter' && !e.shiftKey) {
                            e.preventDefault();
                            e.stopPropagation();
                            if (window.sendChatMessage) {
                                window.sendChatMessage();
                            }
                        }
                    });
                    
                    console.log('Chat send button handlers attached');
                    return true;
                } else {
                    console.warn('Chat send button or input not found, will retry...', { sendBtn, input });
                    return false;
                }
            }
            
            // Attach handlers immediately
            attachSendButtonHandlers();
            
            // Also attach when modal opens (in case elements weren't ready)
            if (chatModal) {
                const modalObserver = new MutationObserver(function() {
                    if (chatModal.classList.contains('chat-modal-open')) {
                        setTimeout(() => {
                            if (!attachSendButtonHandlers()) {
                                // Retry after a short delay
                                setTimeout(attachSendButtonHandlers, 200);
                            }
                        }, 50);
                    }
                });
                modalObserver.observe(chatModal, { attributes: true, attributeFilter: ['class'] });
            }
            
            // Prevent any form submission if form still exists
            if (chatForm) {
                chatForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    e.stopImmediatePropagation();
                    if (window.sendChatMessage) {
                        window.sendChatMessage();
                    }
                    return false;
                });
            }
            
            // Listen to chat queue to know when admin accepts (real-time updates)
            const queueRef = database.ref('chat_queue').orderByChild('conversationId').equalTo(conversationId);
            queueRef.on('value', (snapshot) => {
                if (snapshot.exists()) {
                    const queueItems = snapshot.val();
                    const pendingItems = Object.values(queueItems).filter(item => item.status === 'pending');
                    const acceptedItems = Object.values(queueItems).filter(item => item.status === 'accepted');
                    
                    if (acceptedItems.length > 0) {
                        updateChatStatus('admin_assigned');
                    } else if (pendingItems.length > 0 && lastMessageSenderType === 'user') {
                        updateChatStatus('waiting');
                    }
                } else if (lastMessageSenderType === 'user') {
                    // No queue items but user sent last message - show waiting
                    updateChatStatus('waiting');
                }
            });
            
            // Function to update chat status indicator
            function updateChatStatus(status) {
                const statusIndicator = document.getElementById('chatStatusIndicator');
                const statusText = document.getElementById('chatStatusText');
                
                if (!statusIndicator || !statusText) return;
                
                switch(status) {
                    case 'waiting':
                    case 'user':
                        statusIndicator.style.display = 'flex';
                        statusIndicator.style.background = 'rgba(255, 193, 7, 0.1)';
                        statusIndicator.style.borderLeftColor = '#ffc107';
                        statusIndicator.style.color = '#856404';
                        statusText.innerHTML = '<i class="fas fa-clock"></i> Waiting for admin reply...';
                        break;
                    case 'admin_assigned':
                        statusIndicator.style.display = 'flex';
                        statusIndicator.style.background = 'rgba(33, 150, 243, 0.1)';
                        statusIndicator.style.borderLeftColor = '#2196f3';
                        statusIndicator.style.color = '#1565c0';
                        statusText.innerHTML = '<i class="fas fa-user-check"></i> Admin is reviewing your message...';
                        break;
                    case 'admin_replied':
                    case 'admin':
                        statusIndicator.style.display = 'flex';
                        statusIndicator.style.background = 'rgba(76, 175, 80, 0.1)';
                        statusIndicator.style.borderLeftColor = '#4caf50';
                        statusIndicator.style.color = '#2e7d32';
                        statusText.innerHTML = '<i class="fas fa-check-circle"></i> Admin has replied';
                        // Hide after 3 seconds
                        setTimeout(() => {
                            if (statusIndicator) {
                                statusIndicator.style.display = 'none';
                            }
                        }, 3000);
                        break;
                    default:
                        statusIndicator.style.display = 'none';
                }
            }
            
            // Make updateChatStatus available globally
            window.updateChatStatus = updateChatStatus;
            
            chatInitialized = true;
            window.chatInitialized = true;
            
            console.log('Firebase chat initialization complete');
            
            // Re-attach button handlers after initialization
            setTimeout(() => {
                attachSendButtonHandlers();
            }, 100);
        }
    
    // Expose initFirebaseChat globally
    window.initFirebaseChat = initFirebaseChat;
    
    function addMessageToChat(text, senderType, timestamp) {
        if (!chatMessages) return;
        
        // Remove system message if it exists and this is the first real message
        const systemMsg = chatMessages.querySelector('.chat-message-system');
        if (systemMsg && (senderType === 'user' || senderType === 'admin')) {
            systemMsg.remove();
        }
        
        const msg = document.createElement('div');
        msg.className = `chat-message chat-message-${senderType}`;
        
        const time = timestamp ? new Date(timestamp).toLocaleTimeString() : new Date().toLocaleTimeString();
        const senderName = senderType === 'user' ? 'You' : 'Admin';
        
        msg.innerHTML = `<strong>${senderName}:</strong> ${text} <small style="display: block; font-size: 0.8em; opacity: 0.7; margin-top: 0.25rem;">${time}</small>`;
        chatMessages.appendChild(msg);
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }
    
    // Make openChat function globally available for emergency-call.php
    window.openEmergencyChat = function() {
        if (chatFab) {
            chatFab.click();
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Chat Not Available',
                text: 'Chat feature is not available. Please refresh the page.',
                confirmButtonText: 'OK'
            });
        }
    };
});
</script>

