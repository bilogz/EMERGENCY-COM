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
    <button class="auth-icon-link" id="chatFab" title="Any concerns? Contact support" aria-label="Open chat">
        <i class="fas fa-comments"></i>
    </button>
    <a href="<?= $basePath ?><?= $linkPrefix ?>login.php" class="auth-icon-link" title="Login / Sign Up">
        <i class="fas fa-user-circle"></i>
    </a>
</div>

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
                <p class="chat-hint" style="margin-bottom: 1.25rem;">Please provide your information to start chatting</p>
                <form id="userInfoForm" class="chat-form">
                    <div class="chat-form-group">
                        <label for="userNameInput">Full Name <span class="required-asterisk">*</span></label>
                        <input type="text" id="userNameInput" name="name" required placeholder="Enter your full name" class="chat-form-input">
                    </div>
                    <div class="chat-form-group">
                        <label for="userContactInput">Contact Number <span class="required-asterisk">*</span></label>
                        <input type="tel" id="userContactInput" name="contact" required placeholder="09XX XXX XXXX" class="chat-form-input">
                    </div>
                    <div class="chat-form-group">
                        <label for="userLocationInput">Location <span class="required-asterisk">*</span></label>
                        <div class="searchable-select-wrapper">
                            <input type="text" id="userLocationSearch" class="chat-form-input searchable-select-input" placeholder="Search barangay..." autocomplete="off">
                            <input type="hidden" id="userLocationInput" name="location" required>
                            <div class="searchable-select-dropdown" id="locationDropdown" style="display: none;">
                                <div class="searchable-select-list">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="chat-form-group">
                        <label for="userConcernSelect">What is your concern? <span class="required-asterisk">*</span></label>
                        <select id="userConcernSelect" name="concern" required class="chat-form-select">
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
                    <button type="submit" class="chat-form-submit" disabled>Start Chat</button>
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
                    <button type="button" id="chatCloseBtn" class="btn btn-secondary" style="margin-left: 0.5rem;" title="Close this conversation">
                        <i class="fas fa-times"></i> Close Chat
                    </button>
                    <button type="button" id="startNewConversationBtn" class="btn btn-primary" style="margin-left: 0.5rem; display: none;" title="Start a new conversation">
                        <i class="fas fa-plus"></i> Start New Conversation
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- MySQL Chat System -->
<script>
// Determine correct path for chat-mysql.js based on current location
(function() {
    const currentPath = window.location.pathname;
    let scriptPath = '../js/chat-mysql.js'; // Default for USERS/includes/
    
    // If we're in root or different context, adjust path
    if (currentPath.includes('/index.php') || currentPath === '/' || currentPath.endsWith('/')) {
        scriptPath = 'USERS/js/chat-mysql.js';
    } else if (currentPath.includes('/USERS/')) {
        scriptPath = 'js/chat-mysql.js';
    }
    
    console.log('Loading chat-mysql.js from:', scriptPath);
    const script = document.createElement('script');
    script.src = scriptPath;
    script.onload = function() {
        console.log('chat-mysql.js loaded successfully from:', scriptPath);
        // Verify functions are available
        setTimeout(() => {
            if (window.sendChatMessageMySQL || window.initChatMySQL) {
                console.log('✓ chat-mysql.js functions are available');
            } else {
                console.warn('⚠ chat-mysql.js loaded but functions not available yet');
            }
        }, 100);
    };
    script.onerror = function() {
        console.error('Failed to load chat-mysql.js from:', scriptPath);
        // Try fallback paths
        const fallbackPaths = ['js/chat-mysql.js', '../USERS/js/chat-mysql.js', 'USERS/js/chat-mysql.js'];
        let fallbackIndex = 0;
        const tryFallback = () => {
            if (fallbackIndex < fallbackPaths.length) {
                const fallbackScript = document.createElement('script');
                fallbackScript.src = fallbackPaths[fallbackIndex];
                fallbackScript.onload = () => console.log('chat-mysql.js loaded from fallback:', fallbackPaths[fallbackIndex]);
                fallbackScript.onerror = () => {
                    fallbackIndex++;
                    tryFallback();
                };
                document.head.appendChild(fallbackScript);
            } else {
                console.error('Failed to load chat-mysql.js from all paths');
            }
        };
        tryFallback();
    };
    document.head.appendChild(script);
})();
</script>
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
    
    // Function to ensure chat-mysql.js is loaded
    function ensureChatScriptLoaded() {
        return new Promise((resolve) => {
            // Check if already loaded
            if (window.sendChatMessageMySQL || window.initChatMySQL) {
                console.log('chat-mysql.js already loaded');
                resolve(true);
                return;
            }
            
            // Wait a bit for script to load
            let attempts = 0;
            const checkInterval = setInterval(() => {
                attempts++;
                if (window.sendChatMessageMySQL || window.initChatMySQL) {
                    console.log('chat-mysql.js loaded after', attempts * 100, 'ms');
                    clearInterval(checkInterval);
                    resolve(true);
                } else if (attempts > 50) { // 5 seconds max
                    console.error('chat-mysql.js did not load after 5 seconds');
                    clearInterval(checkInterval);
                    resolve(false);
                }
            }, 100);
        });
    }
    
    // Global event delegation for send button as ultimate fallback
    // This will catch clicks even if other handlers fail
    document.addEventListener('click', async function(e) {
        const target = e.target;
        
        // IMPORTANT: Don't interfere with chat button (chatFab) - let it work normally
        if (target && (target.id === 'chatFab' || target.closest('#chatFab'))) {
            return; // Let chat button handler work normally
        }
        
        // Only handle send button clicks
        if (target && (target.id === 'chatSendBtn' || target.closest('#chatSendBtn'))) {
            const sendBtn = target.id === 'chatSendBtn' ? target : target.closest('#chatSendBtn');
            if (sendBtn && !sendBtn.disabled) {
                console.log('GLOBAL HANDLER: Send button clicked via delegation');
                e.preventDefault();
                e.stopPropagation();
                
                // Get input value
                const input = document.getElementById('chatInput');
                const text = input ? input.value.trim() : '';
                
                if (!text) {
                    console.warn('GLOBAL HANDLER: No text to send');
                    return;
                }
                
                // Ensure script is loaded first
                const scriptLoaded = await ensureChatScriptLoaded();
                if (!scriptLoaded) {
                    console.error('GLOBAL HANDLER: chat-mysql.js script not loaded');
                    alert('Chat system script failed to load. Please refresh the page.');
                    return;
                }
                
                // Log available functions for debugging
                console.log('GLOBAL HANDLER: Available functions:', {
                    sendChatMessageMySQL: typeof window.sendChatMessageMySQL,
                    sendChatMessage: typeof window.sendChatMessage,
                    sendMessage: typeof window.sendMessage,
                    initChatMySQL: typeof window.initChatMySQL
                });
                
                // Try to initialize chat if not already done
                if (!window.sendChatMessageMySQL && window.initChatMySQL) {
                    console.log('GLOBAL HANDLER: Initializing chat first...');
                    sendBtn.disabled = true;
                    sendBtn.textContent = 'Initializing...';
                    try {
                        const initSuccess = await window.initChatMySQL();
                        if (initSuccess && window.sendChatMessageMySQL) {
                            console.log('GLOBAL HANDLER: Chat initialized, sending message');
                            sendBtn.textContent = 'Sending...';
                            const success = await window.sendChatMessageMySQL(text);
                            if (success && input) {
                                input.value = '';
                            }
                            sendBtn.disabled = false;
                            sendBtn.textContent = 'Send';
                            return;
                        }
                    } catch (err) {
                        console.error('GLOBAL HANDLER: Error initializing:', err);
                        sendBtn.disabled = false;
                        sendBtn.textContent = 'Send';
                        alert('Failed to initialize chat. Please refresh the page.');
                        return;
                    }
                }
                
                // Call send function if available
                if (window.sendChatMessageMySQL) {
                    console.log('GLOBAL HANDLER: Calling sendChatMessageMySQL');
                    sendBtn.disabled = true;
                    sendBtn.textContent = 'Sending...';
                    try {
                        const success = await window.sendChatMessageMySQL(text);
                        if (success && input) {
                            input.value = '';
                        }
                        sendBtn.disabled = false;
                        sendBtn.textContent = 'Send';
                    } catch (err) {
                        console.error('GLOBAL HANDLER: Error sending:', err);
                        sendBtn.disabled = false;
                        sendBtn.textContent = 'Send';
                        alert('Failed to send message: ' + err.message);
                    }
                } else if (window.sendChatMessage) {
                    console.log('GLOBAL HANDLER: Calling sendChatMessage');
                    sendBtn.disabled = true;
                    sendBtn.textContent = 'Sending...';
                    try {
                        await window.sendChatMessage();
                        sendBtn.disabled = false;
                        sendBtn.textContent = 'Send';
                    } catch (err) {
                        console.error('GLOBAL HANDLER: Error in sendChatMessage:', err);
                        sendBtn.disabled = false;
                        sendBtn.textContent = 'Send';
                    }
                } else {
                    console.error('GLOBAL HANDLER: No send function available');
                    console.error('GLOBAL HANDLER: Please check if chat-mysql.js is loaded');
                    alert('Chat system is not ready. Please refresh the page or check the console for errors.');
                }
            }
        }
    }, false); // Use bubble phase, not capture, to avoid interfering with chat button
    
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
        // Always get fresh reference to modal
        let modal = document.getElementById('chatModal');
        if (!modal) {
            console.error('Chat modal not found in DOM');
            alert('Chat is not available. Please refresh the page.');
            return;
        }
        
        // Anonymous mode - always allow chat, just check if info is provided
        const hasStoredName = localStorage.getItem('guest_name') || sessionStorage.getItem('user_name');
        const hasStoredContact = localStorage.getItem('guest_contact') || sessionStorage.getItem('user_phone');
        const hasStoredLocation = localStorage.getItem('guest_location') || sessionStorage.getItem('user_location');
        const hasStoredConcern = localStorage.getItem('guest_concern') || sessionStorage.getItem('user_concern');
        const hasAllRequiredInfo = hasStoredName && hasStoredContact && hasStoredLocation && hasStoredConcern;
        
        if (!hasAllRequiredInfo) {
            // Show form and prevent chat
            checkAndShowUserInfoForm();
            console.log('User info required - showing form');
        }
        
        console.log('Opening chat modal...', modal);
        
        // Force show the modal with all necessary styles
        modal.style.cssText = `
            display: flex !important;
            visibility: visible !important;
            opacity: 1 !important;
            pointer-events: auto !important;
            z-index: 99999 !important;
        `;
        modal.classList.add('chat-modal-open');
        modal.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden'; // Prevent background scrolling
        
        // Ensure content is visible
        const modalContent = modal.querySelector('.chat-modal-content');
        if (modalContent) {
            modalContent.style.cssText = `
                pointer-events: auto !important;
                z-index: 100000 !important;
                display: flex !important;
            `;
        }
        
        // Check if user info form should be shown
        checkAndShowUserInfoForm();
        
        // Attach form handler if needed (for anonymous users)
        attachUserInfoFormHandler();
        
        // Initialize MySQL chat if not already done AND form is not showing
        const userInfoForm = document.getElementById('chatUserInfoForm');
        const isFormShowing = userInfoForm && userInfoForm.style.display !== 'none' && userInfoForm.style.display !== '';
        
        if (!isFormShowing && window.initChatMySQL && !window.chatInitialized) {
            window.initChatMySQL().then((success) => {
                if (success) {
                    console.log('MySQL chat initialized');
                }
                // Attach send button handlers after initialization
                setTimeout(() => {
                    if (window.attachSendButtonHandlers) {
                        window.attachSendButtonHandlers();
                    }
                }, 200);
            }).catch(err => {
                console.error('Failed to initialize MySQL chat:', err);
            });
        } else if (!isFormShowing && window.attachSendButtonHandlers) {
            // If already initialized, attach handlers immediately
            setTimeout(() => {
                window.attachSendButtonHandlers();
            }, 100);
        } else if (isFormShowing) {
            console.log('Form is showing, skipping auto-initialization to prevent loop');
        }
        
        // Close modal when clicking outside (on backdrop) - only add once
        if (!chatModal.hasAttribute('data-backdrop-handler')) {
            chatModal.setAttribute('data-backdrop-handler', 'true');
            chatModal.addEventListener('click', function(e) {
                if (e.target === chatModal) {
                    closeChatWithFlag();
                }
            });
        }
        
        // Re-attach button handlers when modal opens
        setTimeout(() => {
            // Use the centralized attachSendButtonHandlers function instead of duplicating
            if (window.attachSendButtonHandlers) {
                window.attachSendButtonHandlers();
            }
            
            const input = document.getElementById('chatInput');
            if (input) {
                input.focus();
            }
        }, 150);
    }

    function closeChat() {
        if (!chatModal) return;
        chatModal.classList.remove('chat-modal-open');
        chatModal.setAttribute('aria-hidden', 'true');
        chatModal.style.display = 'none';
        chatModal.style.visibility = 'hidden';
        chatModal.style.opacity = '0';
        chatModal.style.pointerEvents = 'none';
        document.body.style.overflow = ''; // Restore scrolling
        
        // Stop polling when modal is closed
        if (window.stopChatPolling) {
            window.stopChatPolling();
        }
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
    
    // Function to check and show user info form for anonymous users
    function checkAndShowUserInfoForm() {
        // Don't check if form was just submitted (prevents loop)
        if (window.formJustSubmitted) {
            console.log('Form just submitted, skipping form check to prevent loop');
            return;
        }
        
        const userInfoForm = document.getElementById('chatUserInfoForm');
        const chatInterface = document.getElementById('chatInterface');
        
        if (!userInfoForm || !chatInterface) {
            console.warn('User info form or chat interface not found');
            return;
        }
        
        // Don't show form if chat is already initialized and has an active conversation
        const conversationId = sessionStorage.getItem('conversation_id');
        if (window.chatInitialized && conversationId && !window.conversationClosedHandled) {
            console.log('Chat already initialized with active conversation, not showing form');
            // Ensure chat interface is shown
            userInfoForm.style.display = 'none';
            chatInterface.style.display = 'block';
            return;
        }
        
        // Don't show form if conversation was just closed (handleConversationClosed handles this)
        if (window.conversationClosedHandled) {
            console.log('Conversation closed handler is active, not showing form via checkAndShowUserInfoForm');
            return;
        }
        
        // Anonymous mode - check if guest has provided ALL required info (name, contact, location, concern)
        const hasStoredName = localStorage.getItem('guest_name') || sessionStorage.getItem('user_name');
        const hasStoredContact = localStorage.getItem('guest_contact') || sessionStorage.getItem('user_phone');
        const hasStoredLocation = localStorage.getItem('guest_location') || sessionStorage.getItem('user_location');
        const hasStoredConcern = localStorage.getItem('guest_concern') || sessionStorage.getItem('user_concern');
        
        // All fields are required for anonymous users
        const hasAllRequiredInfo = hasStoredName && hasStoredContact && hasStoredLocation && hasStoredConcern;
        
        console.log('Anonymous mode - hasAllRequiredInfo:', hasAllRequiredInfo);
        
        if (!hasAllRequiredInfo) {
            // Anonymous user without ALL required info - show form (REQUIRED)
            userInfoForm.style.display = 'block';
            chatInterface.style.display = 'none';
            console.log('Showing user info form (anonymous - REQUIRED)');
            
            // Initialize searchable barangay dropdown and form validation
            setTimeout(() => {
                initSearchableBarangay();
                setupFormValidation();
            }, 150);
            
            // Disable chat input if form is not filled
            const chatInput = document.getElementById('chatInput');
            const chatSendBtn = document.getElementById('chatSendBtn');
            if (chatInput) chatInput.disabled = true;
            if (chatSendBtn) chatSendBtn.disabled = true;
        } else {
            // Guest has provided all required info - show chat interface
            userInfoForm.style.display = 'none';
            chatInterface.style.display = 'block';
            console.log('Showing chat interface (anonymous user with all required info)');
            
            // Enable chat input
            const chatInput = document.getElementById('chatInput');
            const chatSendBtn = document.getElementById('chatSendBtn');
            if (chatInput) chatInput.disabled = false;
            if (chatSendBtn) chatSendBtn.disabled = false;
        }
    }
    
    // Function to attach user info form handler
    // Barangay list for Quezon City
    const barangayList = [
        'Alicia', 'Amihan', 'Apolonio Samson', 'Bagong Pag-asa', 'Bagong Silangan',
        'Bagumbayan', 'Bagumbuhay', 'Bahay Toro', 'Balingasa', 'Balintawak',
        'Balumbato', 'Batasan Hills', 'Bayanihan', 'Blue Ridge A', 'Blue Ridge B',
        'Botocan', 'Bungad', 'Camp Aguinaldo', 'Capri', 'Central',
        'Claro', 'Commonwealth', 'Culiat', 'Damar', 'Damayan',
        'Damayang Lagi', 'Del Monte', 'Diliman', 'Dioquino Zobel', 'Don Manuel',
        'Doña Aurora', 'Doña Imelda', 'Doña Josefa', 'Duyan-duyan', 'E. Rodriguez',
        'East Kamias', 'Escopa I', 'Escopa II', 'Escopa III', 'Escopa IV',
        'Fairview', 'Greater Lagro', 'Gulod', 'Holy Spirit', 'Horseshoe',
        'Immaculate Concepcion', 'Kaligayahan', 'Kalusugan', 'Kamuning', 'Katipunan',
        'Kaunlaran', 'Kristong Hari', 'Krus na Ligas', 'Laging Handa', 'Libis',
        'Lourdes', 'Loyola Heights', 'Maharlika', 'Malaya', 'Mangga',
        'Manresa', 'Mariana', 'Mariblo', 'Marilag', 'Masagana',
        'Masambong', 'Matandang Balara', 'Milagrosa', 'Nagkaisang Nayon', 'Nayon Kaunlaran',
        'New Era', 'Novaliches Proper', 'N.S. Amoranto', 'Obrero', 'Old Capitol Site',
        'Paang Bundok', 'Pag-ibig sa Nayon', 'Paligsahan', 'Paltok', 'Pansol',
        'Paraiso', 'Pasong Putik Proper', 'Pasong Tamo', 'Payatas', 'Phil-Am',
        'Pinyahan', 'Project 6', 'Quirino 2-A', 'Quirino 2-B', 'Quirino 2-C',
        'Quirino 3-A', 'Quirino 3-B', 'Ramon Magsaysay', 'Roxas', 'Sacred Heart',
        'Saint Ignatius', 'Saint Peter', 'Salvacion', 'San Agustin', 'San Antonio',
        'San Bartolome', 'San Isidro', 'San Isidro Galas', 'San Jose', 'San Martin de Porres',
        'San Roque', 'San Vicente', 'Sangandaan', 'Santa Cruz', 'Santa Lucia',
        'Santa Monica', 'Santa Teresita', 'Santo Cristo', 'Santo Domingo', 'Santo Niño',
        'Santol', 'Sauyo', 'Sienna', 'Sikatuna Village', 'Silangan',
        'Socorro', 'South Triangle', 'St. Ignatius', 'Tagumpay', 'Talayan',
        'Talipapa', 'Tandang Sora', 'Tatalon', 'Teachers Village East', 'Teachers Village West',
        'Ugong Norte', 'Unang Sigaw', 'UP Campus', 'UP Village', 'Valencia',
        'Vasra', 'Veterans Village', 'Villa Maria Clara', 'West Kamias', 'West Triangle',
        'White Plains'
    ];

    // Initialize searchable barangay dropdown
    function initSearchableBarangay() {
        const searchInput = document.getElementById('userLocationSearch');
        const hiddenInput = document.getElementById('userLocationInput');
        const dropdown = document.getElementById('locationDropdown');
        const dropdownList = dropdown ? dropdown.querySelector('.searchable-select-list') : null;
        
        if (!searchInput || !hiddenInput || !dropdown || !dropdownList) {
            // Retry after a short delay if elements aren't ready
            setTimeout(initSearchableBarangay, 100);
            return;
        }
        
        // Clear existing items if already initialized
        if (dropdownList.querySelector('.searchable-select-item')) {
            return; // Already initialized
        }
        
        // Populate dropdown with barangays
        barangayList.forEach(barangay => {
            const item = document.createElement('div');
            item.className = 'searchable-select-item';
            item.textContent = barangay;
            item.dataset.value = barangay;
            dropdownList.appendChild(item);
        });
        
        // Filter function
        function filterBarangays(searchTerm) {
            const items = dropdownList.querySelectorAll('.searchable-select-item');
            const term = searchTerm.toLowerCase().trim();
            let hasResults = false;
            
            items.forEach(item => {
                const text = item.textContent.toLowerCase();
                if (text.includes(term)) {
                    item.style.display = 'block';
                    hasResults = true;
                } else {
                    item.style.display = 'none';
                }
            });
            
            return hasResults;
        }
        
        // Show dropdown
        function showDropdown() {
            dropdown.style.display = 'block';
            const searchTerm = searchInput.value.trim();
            if (searchTerm) {
                filterBarangays(searchTerm);
            } else {
                // Show all items if no search term
                const items = dropdownList.querySelectorAll('.searchable-select-item');
                items.forEach(item => {
                    item.style.display = 'block';
                });
            }
        }
        
        // Hide dropdown
        function hideDropdown() {
            setTimeout(() => {
                dropdown.style.display = 'none';
            }, 200);
        }
        
        // Select barangay
        function selectBarangay(barangay) {
            searchInput.value = barangay;
            hiddenInput.value = barangay;
            hideDropdown();
            // Trigger validation after selection
            hiddenInput.dispatchEvent(new Event('change', { bubbles: true }));
        }
        
        // Event listeners
        searchInput.addEventListener('focus', showDropdown);
        searchInput.addEventListener('input', function(e) {
            const term = e.target.value;
            if (term.trim()) {
                showDropdown();
            } else {
                hiddenInput.value = '';
            }
        });
        
        // Click on dropdown item
        dropdownList.addEventListener('click', function(e) {
            const item = e.target.closest('.searchable-select-item');
            if (item) {
                selectBarangay(item.dataset.value);
            }
        });
        
        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (!searchInput.contains(e.target) && !dropdown.contains(e.target)) {
                hideDropdown();
            }
        });
        
        // Handle keyboard navigation
        searchInput.addEventListener('keydown', function(e) {
            const visibleItems = Array.from(dropdownList.querySelectorAll('.searchable-select-item:not([style*="display: none"])'));
            const currentIndex = visibleItems.findIndex(item => item.classList.contains('selected'));
            
            if (e.key === 'ArrowDown') {
                e.preventDefault();
                const nextIndex = currentIndex < visibleItems.length - 1 ? currentIndex + 1 : 0;
                visibleItems.forEach(item => item.classList.remove('selected'));
                if (visibleItems[nextIndex]) {
                    visibleItems[nextIndex].classList.add('selected');
                    visibleItems[nextIndex].scrollIntoView({ block: 'nearest' });
                }
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                const prevIndex = currentIndex > 0 ? currentIndex - 1 : visibleItems.length - 1;
                visibleItems.forEach(item => item.classList.remove('selected'));
                if (visibleItems[prevIndex]) {
                    visibleItems[prevIndex].classList.add('selected');
                    visibleItems[prevIndex].scrollIntoView({ block: 'nearest' });
                }
            } else if (e.key === 'Enter' && currentIndex >= 0) {
                e.preventDefault();
                selectBarangay(visibleItems[currentIndex].dataset.value);
            } else if (e.key === 'Escape') {
                hideDropdown();
            }
        });
    }
    
    // Setup form validation to enable/disable submit button
    function setupFormValidation() {
        const nameInput = document.getElementById('userNameInput');
        const contactInput = document.getElementById('userContactInput');
        const locationInput = document.getElementById('userLocationInput');
        const concernSelect = document.getElementById('userConcernSelect');
        const submitBtn = document.querySelector('.chat-form-submit');
        
        if (!nameInput || !contactInput || !locationInput || !concernSelect || !submitBtn) {
            setTimeout(setupFormValidation, 100);
            return;
        }
        
        function validateForm() {
            const name = nameInput.value.trim();
            const contact = contactInput.value.trim();
            const location = locationInput.value.trim();
            const concern = concernSelect.value;
            
            if (name && contact && location && concern) {
                submitBtn.disabled = false;
                submitBtn.style.opacity = '1';
                submitBtn.style.cursor = 'pointer';
            } else {
                submitBtn.disabled = true;
                submitBtn.style.opacity = '0.6';
                submitBtn.style.cursor = 'not-allowed';
            }
        }
        
        // Initial validation
        validateForm();
        
        // Add event listeners
        nameInput.addEventListener('input', validateForm);
        contactInput.addEventListener('input', validateForm);
        locationInput.addEventListener('change', validateForm);
        concernSelect.addEventListener('change', validateForm);
        
        // Also listen to location search input changes
        const locationSearch = document.getElementById('userLocationSearch');
        if (locationSearch) {
            locationSearch.addEventListener('input', function() {
                // Validate when location is selected
                setTimeout(validateForm, 100);
            });
        }
    }

    function attachUserInfoFormHandler() {
        const form = document.getElementById('userInfoForm');
        if (!form) {
            console.log('User info form not found, may not be needed');
            return;
        }
        
        // Remove old handler if exists
        const newForm = form.cloneNode(true);
        form.parentNode.replaceChild(newForm, form);
        const freshForm = document.getElementById('userInfoForm');
        
        if (freshForm && !freshForm.hasAttribute('data-handler-attached')) {
            freshForm.setAttribute('data-handler-attached', 'true');
            
            freshForm.addEventListener('submit', async function(e) {
                e.preventDefault();
                e.stopPropagation();
                e.stopImmediatePropagation();
                
                const name = document.getElementById('userNameInput').value.trim();
                const contact = document.getElementById('userContactInput').value.trim();
                const location = document.getElementById('userLocationInput').value.trim();
                const concern = document.getElementById('userConcernSelect').value;
                
                if (!name || !contact || !location || !concern) {
                    alert('Please fill in all required fields.');
                    return false;
                }
                
                console.log('Form submitted:', { name, contact, location, concern });
                
                // Store user info in both sessionStorage and localStorage
                sessionStorage.setItem('user_name', name);
                sessionStorage.setItem('user_phone', contact);
                sessionStorage.setItem('user_location', location);
                sessionStorage.setItem('user_concern', concern);
                
                // Save to localStorage for persistence
                localStorage.setItem('guest_info_provided', 'true');
                localStorage.setItem('guest_name', name);
                localStorage.setItem('guest_contact', contact);
                localStorage.setItem('guest_location', location);
                localStorage.setItem('guest_concern', concern);
                
                // Hide form and show chat interface
                const userInfoForm = document.getElementById('chatUserInfoForm');
                const chatInterface = document.getElementById('chatInterface');
                if (userInfoForm && chatInterface) {
                    userInfoForm.style.display = 'none';
                    chatInterface.style.display = 'block';
                }
                
                // Enable chat input and send button
                const chatInput = document.getElementById('chatInput');
                const chatSendBtn = document.getElementById('chatSendBtn');
                if (chatInput) chatInput.disabled = false;
                if (chatSendBtn) {
                    chatSendBtn.disabled = false;
                    chatSendBtn.textContent = 'Send';
                }
                
                // Clear any old conversation ID before initializing new chat
                sessionStorage.removeItem('conversation_id');
                if (window.stopChatPolling) {
                    window.stopChatPolling();
                }
                
                // Reset chat initialization flags
                if (window.chatInitialized !== undefined) {
                    window.chatInitialized = false;
                }
                
                // Set a flag to prevent form from showing again after submission
                window.formJustSubmitted = true;
                // Clear the closed handler flag since we're starting fresh
                window.conversationClosedHandled = false;
                
                // Initialize MySQL chat with the provided info
                if (window.initChatMySQL) {
                    try {
                        const success = await window.initChatMySQL();
                        if (success) {
                            console.log('Chat initialized after form submission - new conversation created');
                            // Clear form submission flag after successful initialization
                            setTimeout(() => {
                                window.formJustSubmitted = false;
                            }, 1000);
                            // Attach send button handlers
                            setTimeout(() => {
                                if (window.attachSendButtonHandlers) {
                                    window.attachSendButtonHandlers();
                                }
                                // Focus input after handlers are attached
                                if (chatInput) {
                                    chatInput.focus();
                                }
                            }, 200);
                        } else {
                            console.error('Failed to initialize chat');
                            alert('Failed to initialize chat. Please try again.');
                        }
                    } catch (err) {
                        console.error('Failed to initialize chat:', err);
                        alert('Failed to initialize chat. Please try again.');
                    }
                } else {
                    console.error('initChatMySQL function not available');
                    alert('Chat system is not ready. Please refresh the page.');
                }
                
                return false;
            });
        }
    }
    
    // Expose openChat globally so it can be called from other pages
    window.openChat = openChat;
    window.closeChat = closeChatWithFlag;
    
    // Also expose a simple test function
    window.testChatModal = function() {
        const modal = document.getElementById('chatModal');
        if (modal) {
            console.log('Modal found, testing display...');
            modal.style.cssText = 'display: flex !important; visibility: visible !important; opacity: 1 !important; pointer-events: auto !important; z-index: 99999 !important; position: fixed !important; inset: 0 !important;';
            modal.classList.add('chat-modal-open');
            document.body.style.overflow = 'hidden';
            console.log('Modal should be visible now');
            return true;
        } else {
            console.error('Modal not found in DOM');
            return false;
        }
    };

    // Chat button is now in auth-icons, ensure it's clickable
    // Use event delegation for more reliability
    function setupChatButton() {
        const chatButton = document.getElementById('chatFab');
        if (!chatButton) {
            console.error('Chat button (#chatFab) not found in DOM');
            // Retry after a short delay
            setTimeout(setupChatButton, 500);
            return;
        }
        
        console.log('Chat button found, setting up...');
        
        // Remove all existing listeners by cloning
        const newButton = chatButton.cloneNode(true);
        chatButton.parentNode.replaceChild(newButton, chatButton);
        const freshButton = document.getElementById('chatFab');
        
        if (!freshButton) {
            console.error('Failed to get fresh button after cloning');
            return;
        }
        
        freshButton.type = 'button';
        freshButton.style.pointerEvents = 'auto';
        freshButton.style.cursor = 'pointer';
        freshButton.style.touchAction = 'manipulation';
        
        // Simple, direct click handler
        freshButton.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            e.stopImmediatePropagation();
            console.log('=== CHAT BUTTON CLICKED ===');
            
            // Get modal directly
            const modal = document.getElementById('chatModal');
            console.log('Modal element:', modal);
            
            if (!modal) {
                console.error('Modal not found!');
                alert('Chat modal not found. Please refresh the page.');
                return;
            }
            
            // Force show modal
            console.log('Showing modal...');
            modal.style.cssText = `
                position: fixed !important;
                top: 0 !important;
                left: 0 !important;
                right: 0 !important;
                bottom: 0 !important;
                display: flex !important;
                visibility: visible !important;
                opacity: 1 !important;
                pointer-events: auto !important;
                z-index: 99999 !important;
                background: rgba(0,0,0,0.5) !important;
                align-items: center !important;
                justify-content: center !important;
                padding: 1rem !important;
            `;
            modal.classList.add('chat-modal-open');
            modal.setAttribute('aria-hidden', 'false');
            document.body.style.overflow = 'hidden';
            
            // Ensure content is visible
            const modalContentEl = modal.querySelector('.chat-modal-content');
            if (modalContentEl) {
                modalContentEl.style.cssText = `
                    pointer-events: auto !important;
                    z-index: 100000 !important;
                    display: flex !important;
                `;
            }
            
            // Check and show user info form
            checkAndShowUserInfoForm();
            attachUserInfoFormHandler();
            
            // Initialize searchable dropdown and validation after modal opens
            setTimeout(() => {
                initSearchableBarangay();
                setupFormValidation();
            }, 200);
            
            // Initialize MySQL chat if needed
            if (window.initChatMySQL && !window.chatInitialized) {
                window.initChatMySQL().then((success) => {
                    if (success) {
                        console.log('MySQL chat initialized');
                    }
                    // Attach send button handlers after initialization
                    setTimeout(() => {
                        if (window.attachSendButtonHandlers) {
                            window.attachSendButtonHandlers();
                        }
                    }, 200);
                }).catch(err => {
                    console.error('Failed to initialize MySQL chat:', err);
                });
            } else if (window.attachSendButtonHandlers) {
                // If already initialized, attach handlers immediately
                setTimeout(() => {
                    window.attachSendButtonHandlers();
                }, 100);
            }
            
            console.log('Modal should be visible now');
        }, true); // Use capture phase
        
        // Touch handler for mobile
        freshButton.addEventListener('touchend', function(e) {
            e.preventDefault();
            e.stopPropagation();
            console.log('Chat button touched');
            freshButton.click(); // Trigger click
        }, { passive: false, capture: true });
        
        // Keyboard support
        freshButton.setAttribute('tabindex', '0');
        freshButton.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                freshButton.click();
            }
        });
        
        console.log('Chat button setup complete');
    }
    
    // Setup chat button
    setupChatButton();
    
    // Also try to setup after a delay in case DOM wasn't ready
    setTimeout(setupChatButton, 1000);

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
                        const chatContent = chatModal.querySelector('.chat-modal-content');
                        if (chatContent) {
                            chatContent.style.pointerEvents = 'auto';
                            chatContent.style.zIndex = '10000';
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
        // Make database globally accessible
        window.chatDatabase = database;
        
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
                
                // Initialize searchable barangay dropdown and form validation
            setTimeout(() => {
                initSearchableBarangay();
                setupFormValidation();
            }, 150);
            
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
        window.currentConversationId = conversationId;
        
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
            
            // Function to send message using MySQL
            async function sendChatMessage() {
            // Anonymous mode - check if all required info is provided
            const hasStoredName = localStorage.getItem('guest_name') || sessionStorage.getItem('user_name');
            const hasStoredContact = localStorage.getItem('guest_contact') || sessionStorage.getItem('user_phone');
            const hasStoredLocation = localStorage.getItem('guest_location') || sessionStorage.getItem('user_location');
            const hasStoredConcern = localStorage.getItem('guest_concern') || sessionStorage.getItem('user_concern');
            const hasAllRequiredInfo = hasStoredName && hasStoredContact && hasStoredLocation && hasStoredConcern;
            
            if (!hasAllRequiredInfo) {
                alert('Please fill in all required information (Name, Contact, Location, and Concern) before sending a message.');
                // Show the form
                checkAndShowUserInfoForm();
                return;
            }
            
            // Check if conversation is closed - if so, reset for new conversation
            const chatInput = document.getElementById('chatInput');
            if (chatInput && chatInput.disabled && chatInput.placeholder.includes('closed')) {
                // Reset chat for new conversation
                if (window.resetChatForNewConversation) {
                    window.resetChatForNewConversation();
                }
            }
            
            const text = chatInput ? chatInput.value.trim() : '';
            if (!text) {
                console.warn('Cannot send empty message');
                return;
            }
            
            // Disable send button temporarily
            if (chatSendBtn) {
                chatSendBtn.disabled = true;
                chatSendBtn.textContent = 'Sending...';
            }
            
            try {
                // Ensure modal stays open
                if (chatModal) {
                    chatModal.classList.add('chat-modal-open');
                    chatModal.setAttribute('aria-hidden', 'false');
                    document.body.style.overflow = 'hidden';
                }
                
                // Show waiting status immediately
                if (window.updateChatStatus) {
                    updateChatStatus('waiting');
                }
                
                // Get text from input
                const text = chatInput ? chatInput.value.trim() : '';
                if (!text) {
                    console.warn('No text to send');
                    return;
                }
                
                // Clear input immediately for better UX
                if (chatInput) {
                    chatInput.value = '';
                }
                
                // Add message to UI immediately
                if (window.addMessageToChat) {
                    window.addMessageToChat(text, 'user', Date.now());
                }
                
                // Use MySQL chat system - try both function names
                let success = false;
                let errorMessage = null;
                
                try {
                    if (window.sendChatMessageMySQL) {
                        console.log('Calling sendChatMessageMySQL with text:', text);
                        success = await window.sendChatMessageMySQL(text);
                        console.log('sendChatMessageMySQL result:', success);
                    } else if (window.sendMessage && typeof window.sendMessage === 'function') {
                        console.log('Calling sendMessage (fallback)');
                        success = await window.sendMessage(text);
                    } else {
                        console.error('MySQL chat system not available');
                        console.log('Available functions:', {
                            sendChatMessage: typeof window.sendChatMessage,
                            sendChatMessageMySQL: typeof window.sendChatMessageMySQL,
                            sendMessage: typeof window.sendMessage
                        });
                        errorMessage = 'Chat system is not ready. Please refresh the page.';
                    }
                } catch (error) {
                    console.error('Error calling send function:', error);
                    errorMessage = 'Error sending message: ' + error.message;
                    success = false;
                }
                
                if (success) {
                    console.log('Message sent successfully');
                    // Update status
                    if (window.updateChatStatus) {
                        window.updateChatStatus('waiting');
                    }
                } else {
                    console.error('Failed to send message', errorMessage);
                    // Remove the message from UI if send failed
                    const messages = document.querySelectorAll('.chat-message');
                    if (messages.length > 0) {
                        const lastMessage = messages[messages.length - 1];
                        // Only remove if it's the message we just added
                        if (lastMessage.textContent.includes(text)) {
                            lastMessage.remove();
                        }
                    }
                    if (errorMessage) {
                        alert(errorMessage);
                    } else {
                        alert('Failed to send message. Please try again.');
                    }
                    // Restore input text
                    if (chatInput) {
                        chatInput.value = text;
                    }
                }
            } catch (error) {
                console.error('Error sending message:', error);
                alert('Failed to send message. Please try again.');
            } finally {
                // Re-enable send button (check if conversation is closed)
                const conversationId = sessionStorage.getItem('conversation_id');
                if (chatSendBtn) {
                    if (conversationId) {
                        // Quick check if conversation is closed
                        fetch('../USERS/api/chat-get-conversation.php?conversationId=' + conversationId)
                            .then(res => res.json())
                            .then(data => {
                                if (data.success && data.status === 'closed') {
                                    chatSendBtn.disabled = true;
                                    chatSendBtn.textContent = 'Closed';
                                    if (chatInput) {
                                        chatInput.disabled = true;
                                        chatInput.placeholder = 'This conversation is closed';
                                    }
                                } else {
                                    chatSendBtn.disabled = false;
                                    chatSendBtn.textContent = 'Send';
                                    setTimeout(() => {
                                        if (chatInput) {
                                            chatInput.focus();
                                        }
                                    }, 100);
                                }
                            })
                            .catch(() => {
                                // If check fails, just re-enable
                                chatSendBtn.disabled = false;
                                chatSendBtn.textContent = 'Send';
                                setTimeout(() => {
                                    if (chatInput) {
                                        chatInput.focus();
                                    }
                                }, 100);
                            });
                    } else {
                        chatSendBtn.disabled = false;
                        chatSendBtn.textContent = 'Send';
                        setTimeout(() => {
                            if (chatInput) {
                                chatInput.focus();
                            }
                        }, 100);
                    }
                }
            }
            }
            
            // Make sendChatMessage available globally
            const originalSendChatMessage = sendChatMessage;
            window.sendChatMessage = async function() {
                // Check if user info is required and filled (for anonymous users)
                const userId = sessionStorage.getItem('user_id');
                const isLoggedIn = userId && 
                                  userId !== 'null' &&
                                  userId !== 'undefined' &&
                                  !userId.startsWith('guest_');
                
                if (!isLoggedIn) {
                    // Check if all required info is provided
                    const hasStoredName = localStorage.getItem('guest_name') || sessionStorage.getItem('user_name');
                    const hasStoredContact = localStorage.getItem('guest_contact') || sessionStorage.getItem('user_phone');
                    const hasStoredLocation = localStorage.getItem('guest_location') || sessionStorage.getItem('user_location');
                    const hasStoredConcern = localStorage.getItem('guest_concern') || sessionStorage.getItem('user_concern');
                    const hasAllRequiredInfo = hasStoredName && hasStoredContact && hasStoredLocation && hasStoredConcern;
                    
                    if (!hasAllRequiredInfo) {
                        alert('Please fill in all required information (Name, Contact, Location, and Concern) before sending a message.');
                        // Show the form
                        checkAndShowUserInfoForm();
                        return false;
                    }
                }
                
                // Call original function
                return await originalSendChatMessage();
            };
            
            // Also ensure sendChatMessageMySQL is available as an alias
            if (!window.sendChatMessageMySQL) {
                window.sendChatMessageMySQL = async function(text) {
                    console.log('sendChatMessageMySQL wrapper called with text:', text);
                    // If text is provided, use it; otherwise get from input
                    if (!text) {
                        const input = document.getElementById('chatInput');
                        text = input ? input.value.trim() : '';
                    }
                    if (!text) {
                        console.warn('No text to send');
                        return false;
                    }
                    // Call the sendChatMessage function which will handle validation
                    return await window.sendChatMessage();
                };
            }
            
            // Make attachSendButtonHandlers available globally
            window.attachSendButtonHandlers = attachSendButtonHandlers;
            
            // Make attachCloseButtonHandler available globally
            window.attachCloseButtonHandler = attachCloseButtonHandler;
            
            // Function to attach send button handlers
            function attachSendButtonHandlers() {
                console.log('=== ATTACHING SEND BUTTON HANDLERS ===');
                const sendBtn = document.getElementById('chatSendBtn');
                const input = document.getElementById('chatInput');
                
                if (!sendBtn) {
                    console.error('Chat send button not found!');
                    console.log('Available elements:', {
                        chatForm: !!document.getElementById('chatForm'),
                        chatInterface: !!document.getElementById('chatInterface'),
                        chatModal: !!document.getElementById('chatModal')
                    });
                    // Retry after a short delay
                    setTimeout(() => {
                        if (document.getElementById('chatSendBtn')) {
                            console.log('Retrying attachSendButtonHandlers...');
                            attachSendButtonHandlers();
                        }
                    }, 300);
                    return false;
                }
                
                if (!input) {
                    console.error('Chat input not found!');
                    return false;
                }
                
                console.log('Send button found:', sendBtn);
                console.log('Input found:', input);
                console.log('Button current state:', {
                    disabled: sendBtn.disabled,
                    display: window.getComputedStyle(sendBtn).display,
                    visibility: window.getComputedStyle(sendBtn).visibility,
                    pointerEvents: window.getComputedStyle(sendBtn).pointerEvents
                });
                
                // Ensure button is clickable BEFORE cloning
                sendBtn.style.pointerEvents = 'auto';
                sendBtn.style.cursor = 'pointer';
                sendBtn.style.touchAction = 'manipulation';
                sendBtn.style.zIndex = '10001';
                sendBtn.style.position = 'relative';
                sendBtn.disabled = false;
                sendBtn.type = 'button'; // Ensure it's a button, not submit
                
                // Remove old listeners by cloning
                const newBtn = sendBtn.cloneNode(true);
                sendBtn.parentNode.replaceChild(newBtn, sendBtn);
                const freshBtn = document.getElementById('chatSendBtn');
                
                if (!freshBtn) {
                    console.error('Failed to get fresh button after cloning');
                    return false;
                }
                
                console.log('Fresh button obtained:', freshBtn);
                
                // Ensure fresh button is clickable
                freshBtn.style.pointerEvents = 'auto !important';
                freshBtn.style.cursor = 'pointer !important';
                freshBtn.style.touchAction = 'manipulation';
                freshBtn.style.zIndex = '10001';
                freshBtn.style.position = 'relative';
                freshBtn.disabled = false;
                freshBtn.type = 'button';
                
                // Attach click handler - use addEventListener for better compatibility
                console.log('Attaching click handler to send button');
                
                // Remove any existing click handlers first
                const handleSendClick = async function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    e.stopImmediatePropagation();
                    console.log('=== SEND BUTTON CLICKED ===');
                    
                    // Check if button is disabled
                    if (freshBtn.disabled) {
                        console.warn('Button is disabled, ignoring click');
                        return false;
                    }
                    
                    const input = document.getElementById('chatInput');
                    const text = input ? input.value.trim() : '';
                    
                    if (!text) {
                        console.warn('No text to send');
                        return false;
                    }
                    
                    // Disable button while sending
                    freshBtn.disabled = true;
                    freshBtn.textContent = 'Sending...';
                    
                    try {
                        // Directly call sendChatMessageMySQL if available
                        if (window.sendChatMessageMySQL) {
                            console.log('Calling sendChatMessageMySQL with text:', text);
                            const success = await window.sendChatMessageMySQL(text);
                            console.log('sendChatMessageMySQL result:', success);
                            
                            if (success) {
                                console.log('Message sent successfully');
                                // Clear input on success
                                if (input) {
                                    input.value = '';
                                }
                            } else {
                                console.error('Failed to send message');
                                alert('Failed to send message. Please try again.');
                                // Restore input on failure
                                if (input) {
                                    input.value = text;
                                }
                            }
                        } else if (window.sendChatMessage) {
                            console.log('Using sendChatMessage wrapper');
                            // Call the wrapper function
                            await window.sendChatMessage();
                        } else {
                            console.error('No send function available');
                            console.log('Available functions:', {
                                sendChatMessage: typeof window.sendChatMessage,
                                sendChatMessageMySQL: typeof window.sendChatMessageMySQL,
                                sendMessage: typeof window.sendMessage
                            });
                            alert('Chat system is not ready. Please refresh the page.');
                            // Restore input
                            if (input) {
                                input.value = text;
                            }
                        }
                    } catch (error) {
                        console.error('Error in send button handler:', error);
                        alert('Error sending message: ' + error.message);
                        // Restore input
                        if (input) {
                            input.value = text;
                        }
                    } finally {
                        // Always re-enable button (unless conversation is closed)
                        const conversationId = sessionStorage.getItem('conversation_id');
                        if (conversationId) {
                            // Quick check if conversation is closed
                            try {
                                const res = await fetch('../USERS/api/chat-get-conversation.php?conversationId=' + conversationId);
                                const data = await res.json();
                                if (data.success && data.status === 'closed') {
                                    freshBtn.disabled = true;
                                    freshBtn.textContent = 'Closed';
                                    if (input) {
                                        input.disabled = true;
                                        input.placeholder = 'This conversation is closed';
                                    }
                                } else {
                                    freshBtn.disabled = false;
                                    freshBtn.textContent = 'Send';
                                }
                            } catch (err) {
                                // If check fails, just re-enable
                                freshBtn.disabled = false;
                                freshBtn.textContent = 'Send';
                            }
                        } else {
                            freshBtn.disabled = false;
                            freshBtn.textContent = 'Send';
                        }
                    }
                    
                    return false;
                };
                
                // Use multiple methods to ensure the handler works
                // Method 1: addEventListener
                freshBtn.addEventListener('click', handleSendClick, { capture: false, passive: false });
                
                // Method 2: Direct onclick as fallback
                freshBtn.onclick = handleSendClick;
                
                // Method 3: Also attach touchend for mobile devices
                freshBtn.addEventListener('touchend', async function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    console.log('Send button touched');
                    handleSendClick(e);
                }, { passive: false });
                
                // Method 4: Use event delegation on parent as ultimate fallback
                const chatForm = document.getElementById('chatForm');
                if (chatForm && !chatForm.hasAttribute('data-send-delegation')) {
                    chatForm.setAttribute('data-send-delegation', 'true');
                    chatForm.addEventListener('click', function(e) {
                        if (e.target && e.target.id === 'chatSendBtn') {
                            console.log('Send button clicked via delegation');
                            handleSendClick(e);
                        }
                    }, { capture: true });
                }
                
                // Clone and reattach input handlers
                const newInput = input.cloneNode(true);
                input.parentNode.replaceChild(newInput, input);
                const freshInput = document.getElementById('chatInput');
                
                freshInput.style.pointerEvents = 'auto';
                freshInput.style.cursor = 'text';
                
                freshInput.addEventListener('keypress', async function(e) {
                    if (e.key === 'Enter' && !e.shiftKey) {
                        e.preventDefault();
                        e.stopPropagation();
                        console.log('Enter key pressed');
                        
                        const text = this.value.trim();
                        if (!text) {
                            return;
                        }
                        
                        // Try sendChatMessage first, then sendChatMessageMySQL
                        if (window.sendChatMessage) {
                            window.sendChatMessage();
                        } else if (window.sendChatMessageMySQL) {
                            try {
                                const success = await window.sendChatMessageMySQL(text);
                                if (!success) {
                                    console.error('Failed to send message');
                                }
                            } catch (error) {
                                console.error('Error calling sendChatMessageMySQL:', error);
                            }
                        } else {
                            console.error('No send function available');
                            alert('Chat system is not ready. Please refresh the page.');
                        }
                    }
                });
                
                freshInput.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter' && !e.shiftKey) {
                        e.preventDefault();
                    }
                });
                
                // Don't prevent default on mousedown/touchstart as it can interfere with click events
                // Just log for debugging
                freshBtn.addEventListener('mousedown', function(e) {
                    console.log('Send button mousedown');
                });
                
                freshBtn.addEventListener('touchstart', function(e) {
                    console.log('Send button touchstart');
                });
                
                // Test if button is actually clickable
                console.log('Send button styles:', {
                    pointerEvents: window.getComputedStyle(freshBtn).pointerEvents,
                    cursor: window.getComputedStyle(freshBtn).cursor,
                    zIndex: window.getComputedStyle(freshBtn).zIndex,
                    disabled: freshBtn.disabled,
                    type: freshBtn.type,
                    display: window.getComputedStyle(freshBtn).display,
                    visibility: window.getComputedStyle(freshBtn).visibility,
                    opacity: window.getComputedStyle(freshBtn).opacity
                });
                
                // Add a simple test handler to verify button is clickable
                freshBtn.addEventListener('mousedown', function(e) {
                    console.log('TEST: Send button mousedown detected!', e);
                });
                
                freshBtn.addEventListener('mouseup', function(e) {
                    console.log('TEST: Send button mouseup detected!', e);
                });
                
                // Verify handler was attached
                const hasClickHandler = freshBtn.onclick !== null || 
                    (freshBtn.addEventListener && true);
                console.log('Button has click handler:', hasClickHandler);
                console.log('Button onclick type:', typeof freshBtn.onclick);
                
                // Force button to be visible and clickable
                freshBtn.style.display = 'block';
                freshBtn.style.visibility = 'visible';
                freshBtn.style.opacity = '1';
                freshBtn.style.pointerEvents = 'auto';
                freshBtn.style.cursor = 'pointer';
                freshBtn.removeAttribute('disabled');
                freshBtn.disabled = false;
                
                console.log('Chat send button handlers attached successfully');
                console.log('Final button state:', {
                    disabled: freshBtn.disabled,
                    onclick: typeof freshBtn.onclick,
                    style: freshBtn.style.cssText
                });
                
                return true;
            }
            
            // Function to attach close button handler
            function attachCloseButtonHandler() {
                const closeBtn = document.getElementById('chatCloseBtn');
                if (!closeBtn) {
                    console.log('Close button not found');
                    return false;
                }
                
                // Remove old listeners by cloning
                const newBtn = closeBtn.cloneNode(true);
                closeBtn.parentNode.replaceChild(newBtn, closeBtn);
                const freshBtn = document.getElementById('chatCloseBtn');
                
                if (!freshBtn) {
                    console.error('Failed to get fresh close button');
                    return false;
                }
                
                freshBtn.style.pointerEvents = 'auto';
                freshBtn.style.cursor = 'pointer';
                freshBtn.style.touchAction = 'manipulation';
                freshBtn.disabled = false;
                
                freshBtn.onclick = async function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    e.stopImmediatePropagation();
                    
                    if (!confirm('Are you sure you want to close this conversation? This will close the conversation on both your side and the admin side. You will need to start a new conversation to continue chatting.')) {
                        return false;
                    }
                    
                    const conversationId = sessionStorage.getItem('conversation_id');
                    if (!conversationId) {
                        alert('No active conversation to close.');
                        return false;
                    }
                    
                    try {
                        freshBtn.disabled = true;
                        freshBtn.textContent = 'Closing...';
                        
                        const response = await fetch('../USERS/api/chat-close.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({
                                conversationId: conversationId
                            })
                        });
                        
                        const data = await response.json();
                        
                        if (data.success) {
                            alert('Conversation closed successfully. The conversation has been closed on both your side and the admin side. Please select a category to start a new conversation.');
                            
                            // Use the handleConversationClosed function to properly refresh and show form
                            if (window.handleConversationClosed) {
                                window.handleConversationClosed();
                            } else {
                                // Fallback: Show user info form
                                const chatInterface = document.getElementById('chatInterface');
                                const userInfoForm = document.getElementById('chatUserInfoForm');
                                if (userInfoForm && chatInterface) {
                                    chatInterface.style.display = 'none';
                                    userInfoForm.style.display = 'block';
                                    
                                    // Initialize searchable barangay dropdown and form validation
                                    setTimeout(() => {
                                        initSearchableBarangay();
                                        setupFormValidation();
                                    }, 150);
                                    
                                    // Clear concern to force re-selection
                                    localStorage.removeItem('guest_concern');
                                    sessionStorage.removeItem('user_concern');
                                    const concernSelect = document.getElementById('userConcernSelect');
                                    if (concernSelect) {
                                        concernSelect.value = '';
                                    }
                                }
                                
                                // Disable input and send button
                                const chatInput = document.getElementById('chatInput');
                                const chatSendBtn = document.getElementById('chatSendBtn');
                                if (chatInput) {
                                    chatInput.disabled = true;
                                }
                                if (chatSendBtn) {
                                    chatSendBtn.disabled = true;
                                }
                                freshBtn.style.display = 'none';
                            }
                            
                            // Stop polling
                            if (window.stopChatPolling) {
                                window.stopChatPolling();
                            }
                        } else {
                            alert('Failed to close conversation: ' + (data.message || 'Unknown error'));
                            freshBtn.disabled = false;
                            freshBtn.innerHTML = '<i class="fas fa-times"></i> Close Chat';
                        }
                    } catch (error) {
                        console.error('Error closing conversation:', error);
                        alert('Error closing conversation. Please try again.');
                        freshBtn.disabled = false;
                        freshBtn.innerHTML = '<i class="fas fa-times"></i> Close Chat';
                    }
                    
                    return false;
                };
                
                console.log('Close button handler attached');
                return true;
            }
            
            // Attach handlers immediately
            attachSendButtonHandlers();
            attachCloseButtonHandler();
            
            // Initialize "Start New Conversation" button
            const startNewBtn = document.getElementById('startNewConversationBtn');
            if (startNewBtn) {
                startNewBtn.onclick = function() {
                    if (window.startNewConversation) {
                        window.startNewConversation();
                    } else if (window.resetChatForNewConversation) {
                        window.resetChatForNewConversation();
                    }
                };
            }
            
            // Also attach when modal opens (in case elements weren't ready)
            if (chatModal) {
                const modalObserver = new MutationObserver(function() {
                    if (chatModal.classList.contains('chat-modal-open')) {
                        setTimeout(() => {
                            if (!attachSendButtonHandlers()) {
                                // Retry after a short delay
                                setTimeout(attachSendButtonHandlers, 200);
                            }
                            attachCloseButtonHandler();
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
            console.log('Conversation ID:', conversationId);
            console.log('Database available:', !!database);
            console.log('Global database available:', !!window.chatDatabase);
            
            // Re-attach button handlers after initialization
            setTimeout(() => {
                attachSendButtonHandlers();
            }, 100);
        }
    }
    
    // Expose initFirebaseChat globally
    window.initFirebaseChat = initFirebaseChat;
    
    function addMessageToChat(text, senderType, timestamp, messageId = null) {
        if (!chatMessages) return;
        
        // Check if message already exists (by ID)
        if (messageId) {
            const existing = chatMessages.querySelector(`.chat-message[data-message-id="${messageId}"]`);
            if (existing) {
                return; // Message already exists, don't add again
            }
        }
        
        // Remove system message if it exists and this is the first real message
        const systemMsg = chatMessages.querySelector('.chat-message-system');
        if (systemMsg && (senderType === 'user' || senderType === 'admin')) {
            systemMsg.remove();
        }
        
        const msg = document.createElement('div');
        msg.className = `chat-message chat-message-${senderType}`;
        if (messageId) {
            msg.setAttribute('data-message-id', messageId);
        }
        
        const time = timestamp ? new Date(timestamp).toLocaleTimeString() : new Date().toLocaleTimeString();
        const senderName = senderType === 'user' ? 'You' : 'Admin';
        
        // Escape HTML to prevent XSS
        const escapeHtml = (str) => {
            const div = document.createElement('div');
            div.textContent = str;
            return div.innerHTML;
        };
        
        msg.innerHTML = `<strong style="color: var(--text-color) !important;">${escapeHtml(senderName)}:</strong> <span style="color: var(--text-color) !important;">${escapeHtml(text)}</span> <small style="display: block; font-size: 0.8em; opacity: 0.7; margin-top: 0.25rem; color: var(--text-muted) !important;">${time}</small>`;
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

