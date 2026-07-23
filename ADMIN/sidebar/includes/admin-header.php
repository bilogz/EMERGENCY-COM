<?php
/**
 * Reusable Admin Header Component - Improved Design
 * Include this file in your pages: <?php include 'sidebar/admin-header.php'; ?>
 * 
 * Features:
 * - Responsive menu toggle
 * - Notification and message icons with badges (outlined style)
 * - User profile with avatar and info
 * - Dark mode support
 * - Clean, modern design
 */

// Ensure session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Fetch admin info from database if session variables are missing
if (!isset($_SESSION['admin_username']) || !isset($_SESSION['admin_email'])) {
    if (isset($_SESSION['admin_user_id']) && isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
        try {
            // Use relative path to db_connect.php
            $dbPath = __DIR__ . '/../../api/db_connect.php';
            if (!file_exists($dbPath)) {
                // Try alternative path
                $dbPath = __DIR__ . '/../api/db_connect.php';
            }
            
            if (file_exists($dbPath)) {
                require_once $dbPath;
                
                // Check if $pdo is available (it's set in db_connect.php)
                global $pdo;
                if (isset($pdo) && $pdo) {
                    // Load service classes
                    $servicePath = __DIR__ . '/../../app/services/AdminService.php';
                    if (file_exists($servicePath)) {
                        require_once $servicePath;
                        $adminService = new AdminService($pdo);
                        $adminId = $_SESSION['admin_user_id'];
                        $admin = $adminService->getNameAndEmailById($adminId);
                        
                        if ($admin) {
                            $_SESSION['admin_username'] = $admin['name'];
                            $_SESSION['admin_email'] = $admin['email'];
                        }
                    }
                }
            }
        } catch (Exception $e) {
            error_log("Error loading admin info in header: " . $e->getMessage());
        }
    }
}

// Set defaults if still not set
$adminUsername = $_SESSION['admin_username'] ?? 'Admin User';
$adminEmail = $_SESSION['admin_email'] ?? 'admin@example.com';

// Determine if we should show notifications based on current page
// Hide for Multilingual Support module pages
$currentScript = $_SERVER['PHP_SELF'];
$hideNotifications = (strpos($currentScript, '/multilingual-support/') !== false);

// Base path fix for pages inside /multilingual-support/*
$currentDirForAssets = basename(dirname($_SERVER['PHP_SELF']));
$headerBase = ($currentDirForAssets === 'multilingual-support') ? '../' : '';
?>

<link rel="stylesheet" href="<?php echo $headerBase; ?>css/notification-modal.css">
<link rel="stylesheet" href="<?php echo $headerBase; ?>css/message-modal.css">
<link rel="stylesheet" href="<?php echo $headerBase; ?>css/message-content-modal.css">
<!-- Emergency Alert System -->
<link rel="stylesheet" href="<?php echo $headerBase; ?>../header/css/emergency-alert.css">


<!-- Admin Header Component -->
<header class="admin-header">
    <div class="admin-header-left">
        <button class="menu-toggle" id="menuToggle" aria-label="Toggle menu">
            <i class="fas fa-bars"></i>
        </button>
        <div class="search-container">
            <i class="fas fa-search search-icon"></i>
            <input type="text" class="search-input" placeholder="Search...">
        </div>
    </div>
    
    <div class="admin-header-right">
        <!-- Date and Time Display -->
        <div class="datetime-display" id="headerDateTime">
            <span class="date-part"></span>
            <span class="time-separator">|</span>
            <span class="time-part"></span>
        </div>

        <div class="header-actions">
            <!-- Theme Toggle Buttons -->
            <div class="theme-toggle-container">
                <button class="theme-mode-btn" id="lightModeBtn" aria-label="Light Mode" title="Switch to Light Mode">
                    <i class="fas fa-sun"></i>
                    <span>Light</span>
                </button>
                <button class="theme-mode-btn" id="darkModeBtn" aria-label="Dark Mode" title="Switch to Dark Mode">
                    <i class="fas fa-moon"></i>
                    <span>Dark</span>
                </button>
            </div>

            <button class="report-export-btn" id="headerPdfExportBtn" type="button" aria-label="Download report as PDF" title="Download current report as PDF">
                <i class="fas fa-file-pdf"></i>
                <span>PDF</span>
            </button>
            
            <?php if (!$hideNotifications): ?>
            <div class="notification-item">
                <button class="notification-btn" id="headerNotificationBtn" aria-label="Notifications">
                    <i class="fas fa-bell"></i>
                    <span class="notification-badge" id="notificationBadge">0</span>
                </button>
            </div>
            <?php endif; ?>
            
            <div class="notification-item">
                <button class="notification-btn" id="headerMessageBtn" aria-label="Messages">
                    <i class="fas fa-envelope"></i>
                    <span class="notification-badge" id="messageBadge">0</span>
                </button>
            </div>
        </div>
        
        <div class="header-divider"></div>
        
        <div class="user-profile" id="userProfileBtn">
            <div class="user-info">
                <div class="user-name" id="adminDisplayName"><?php echo htmlspecialchars($adminUsername); ?></div>
                <div class="user-role">Administrator</div>
            </div>
            <div class="user-avatar">
                <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($adminUsername); ?>&background=4c8a89&color=fff&size=128" alt="<?php echo htmlspecialchars($adminUsername); ?>" class="avatar-img" id="adminAvatarImg">
            </div>
            <i class="fas fa-chevron-down dropdown-icon"></i>
        </div>
    </div>
</header>

<!-- User Profile Dropdown -->
<div class="user-profile-dropdown" id="userProfileDropdown">
    <div class="dropdown-header">
        <div class="dropdown-user-info">
            <div class="dropdown-user-avatar">
                <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($adminUsername); ?>&background=4c8a89&color=fff&size=128" alt="<?php echo htmlspecialchars($adminUsername); ?>" id="dropdownAdminAvatar">
            </div>
            <div class="dropdown-user-details">
                <div class="dropdown-user-name" id="dropdownAdminName"><?php echo htmlspecialchars($adminUsername); ?></div>
                <div class="dropdown-user-email" id="dropdownAdminEmail"><?php echo htmlspecialchars($adminEmail); ?></div>
            </div>
        </div>
    </div>
    
    <div class="dropdown-body">
        <a href="profile.php" class="dropdown-item" id="viewProfileBtn">
            <i class="fas fa-user"></i>
            <span>View Profile</span>
        </a>
        <a href="#" class="dropdown-item">
            <i class="fas fa-cog"></i>
            <span>Settings</span>
        </a>
    </div>
    
    <div class="dropdown-footer">
        <a href="#" class="dropdown-item logout-item" onclick="event.preventDefault(); handleLogout(); return false;">
            <i class="fas fa-sign-out-alt"></i>
            <span>Logout</span>
        </a>
    </div>
</div>

<?php if (!$hideNotifications): ?>
<!-- Notification Modal -->
<div class="notification-modal" id="notificationModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Notifications</h3>
            <button class="modal-close" onclick="closeModal('notificationModal')">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body" id="headerNotificationBody">
            <div class="notification-item notification-item--placeholder">
                <div class="notification-details">
                    <div class="notification-title">Loading notifications...</div>
                    <div class="notification-text">Please wait while data is fetched.</div>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="view-all-link" id="headerMarkNotificationsReadBtn">Mark all as read</button>
            <a href="two-way-communication.php" class="view-all-link">Open Queue</a>
        </div>
    </div>
</div>

<!-- Incident Report Pop Modal -->
<div class="header-incident-modal" id="headerIncidentModal" aria-hidden="true">
    <div class="header-incident-modal-card" role="dialog" aria-modal="true" aria-labelledby="headerIncidentModalTitle">
        <div class="header-incident-modal-head">
            <h4 id="headerIncidentModalTitle"><i class="fas fa-triangle-exclamation"></i> New Incident Report</h4>
            <button type="button" class="header-incident-modal-close" id="headerIncidentModalClose" aria-label="Close">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="header-incident-modal-body">
            <p class="header-incident-modal-message" id="headerIncidentModalMessage"></p>
            <p class="header-incident-modal-meta" id="headerIncidentModalMeta"></p>
        </div>
        <div class="header-incident-modal-actions">
            <button type="button" class="btn btn-secondary btn-sm" id="headerIncidentModalDismiss">Dismiss</button>
            <a href="two-way-communication.php" class="btn btn-primary btn-sm" id="headerIncidentModalOpen">
                <i class="fas fa-comments"></i> Open Queue
            </a>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Message Modal -->
<div class="notification-modal" id="messageModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Messages</h3>
            <button class="modal-close" onclick="closeModal('messageModal')">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body">
            <div class="message-item">
                <div class="message-avatar">
                    <img src="https://ui-avatars.com/api/?name=Sarah+Smith&background=4c8a89&color=fff&size=64" alt="Sarah Smith">
                </div>
                <div class="message-details">
                    <div class="message-title">Sarah Smith</div>
                    <div class="message-text">Hey, can you review the latest designs?</div>
                    <div class="message-time">30 minutes ago</div>
                </div>
                <div class="message-status unread"></div>
            </div>
            <div class="message-item">
                <div class="message-avatar">
                    <img src="https://ui-avatars.com/api/?name=Mike+Johnson&background=4c8a89&color=fff&size=64" alt="Mike Johnson">
                </div>
                <div class="message-details">
                    <div class="message-title">Mike Johnson</div>
                    <div class="message-text">Meeting scheduled for tomorrow at 2 PM</div>
                    <div class="message-time">2 hours ago</div>
                </div>
                <div class="message-status unread"></div>
            </div>
            <div class="message-item">
                <div class="message-avatar">
                    <img src="https://ui-avatars.com/api/?name=Emily+Brown&background=4c8a89&color=fff&size=64" alt="Emily Brown">
                </div>
                <div class="message-details">
                    <div class="message-title">Emily Brown</div>
                    <div class="message-text">Thanks for your help with the project!</div>
                    <div class="message-time">1 day ago</div>
                </div>
                <div class="message-status"></div>
            </div>
            <div class="message-item">
                <div class="message-avatar">
                    <img src="https://ui-avatars.com/api/?name=David+Lee&background=4c8a89&color=fff&size=64" alt="David Lee">
                </div>
                <div class="message-details">
                    <div class="message-title">David Lee</div>
                    <div class="message-text">Can you send me the report?</div>
                    <div class="message-time">2 days ago</div>
                </div>
                <div class="message-status"></div>
            </div>
            <div class="message-item">
                <div class="message-avatar">
                    <img src="https://ui-avatars.com/api/?name=Lisa+Wang&background=4c8a89&color=fff&size=64" alt="Lisa Wang">
                </div>
                <div class="message-details">
                    <div class="message-title">Lisa Wang</div>
                    <div class="message-text">Great job on the presentation!</div>
                    <div class="message-time">3 days ago</div>
                </div>
                <div class="message-status"></div>
            </div>
        </div>
        <div class="modal-footer">
            <a href="#" class="view-all-link">View All Messages</a>
        </div>
    </div>
</div>

<!-- Message Content Modal -->
<div class="message-content-modal" id="messageContentModal">
    <div class="modal-content">
        <div class="modal-header">
            <div class="message-header-info">
                <img id="messageUserAvatar" src="" alt="" class="message-user-avatar">
                <div class="message-user-info">
                    <h3 id="messageUserName"></h3>
                    <span id="messageUserStatus"></span>
                </div>
            </div>
            <button class="modal-close" onclick="closeModal('messageContentModal')">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body message-chat-body">
            <div id="messageContent"></div>
        </div>
        <div class="modal-footer message-reply-footer">
            <div class="message-reply-box">
                <input type="text" id="messageReplyInput" placeholder="Type a message..." class="message-input">
                <button class="send-message-btn">
                    <i class="fas fa-paper-plane"></i>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Build stable API base path for all ADMIN pages (works for nested routes)
(function () {
    const normalizedPath = window.location.pathname.replace(/\\/g, '/');
    const adminIndex = normalizedPath.toLowerCase().indexOf('/admin/');
    const appBase = adminIndex >= 0 ? normalizedPath.substring(0, adminIndex) : '';

    if (typeof window.APP_BASE_PATH === 'undefined') {
        window.APP_BASE_PATH = appBase;
    }
    if (typeof window.API_BASE_PATH === 'undefined') {
        window.API_BASE_PATH = appBase + '/ADMIN/api/';
    }
})();

// Admin Header functionality
document.addEventListener('DOMContentLoaded', function() {
    const menuToggle = document.getElementById('menuToggle');
    const lightModeBtn = document.getElementById('lightModeBtn');
    const darkModeBtn = document.getElementById('darkModeBtn');
    const disableAutoDarkMode = document.body && document.body.getAttribute('data-disable-auto-darkmode') === 'true';
    
    // Initialize theme
    function initTheme() {
        const savedTheme = localStorage.getItem('theme') || 'light';
        const html = document.documentElement;
        
        // If system theme, detect preference
        if (savedTheme === 'system' && !disableAutoDarkMode) {
            const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            html.setAttribute('data-theme', prefersDark ? 'dark' : 'light');
            updateThemeButtons(prefersDark ? 'dark' : 'light');
        } else if (savedTheme === 'system' && disableAutoDarkMode) {
            // Page-level override: keep light theme when auto-system theme is disabled.
            html.setAttribute('data-theme', 'light');
            updateThemeButtons('light');
        } else {
            html.setAttribute('data-theme', savedTheme);
            updateThemeButtons(savedTheme);
        }
    }
    
    function updateThemeButtons(theme) {
        if (lightModeBtn && darkModeBtn) {
            if (theme === 'dark') {
                lightModeBtn.classList.remove('active');
                darkModeBtn.classList.add('active');
            } else {
                lightModeBtn.classList.add('active');
                darkModeBtn.classList.remove('active');
            }
        }
    }
    
    // Light mode button
    if (lightModeBtn) {
        lightModeBtn.addEventListener('click', function() {
            const html = document.documentElement;
            html.setAttribute('data-theme', 'light');
            localStorage.setItem('theme', 'light');
            updateThemeButtons('light');
        });
    }
    
    // Dark mode button
    if (darkModeBtn) {
        darkModeBtn.addEventListener('click', function() {
            const html = document.documentElement;
            html.setAttribute('data-theme', 'dark');
            localStorage.setItem('theme', 'dark');
            updateThemeButtons('dark');
        });
    }
    
    // Listen for system theme changes
    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
        if (localStorage.getItem('theme') === 'system' && !disableAutoDarkMode) {
            initTheme();
        }
    });
    
    // Initialize theme on load
    initTheme();
    
    // Toggle sidebar from header menu button
    if (menuToggle) {
        menuToggle.addEventListener('click', function() {
            // Use the global sidebarToggle function exposed by sidebar.php
            if (typeof window.sidebarToggle === 'function') {
                window.sidebarToggle();
            } else {
                console.warn('Sidebar toggle function not found. Make sure sidebar.php is included before admin-header.php');
            }
        });
    }
    
    // Search functionality
    const searchInput = document.querySelector('.search-input');
    const searchBtn = document.querySelector('.search-btn');
    
    if (searchBtn) {
        searchBtn.addEventListener('click', function() {
            if (searchInput) {
                const searchTerm = searchInput.value.trim();
                if (searchTerm) {
                    console.log('Searching for:', searchTerm);
                }
            }
        });
    }
    
    if (searchInput) {
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                const searchTerm = searchInput.value.trim();
                if (searchTerm) {
                    console.log('Searching for:', searchTerm);
                }
            }
        });
    }
    
    // Notification button interactions
    const notificationBtns = document.querySelectorAll('.admin-header .notification-btn');
    notificationBtns.forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            const ariaLabel = this.getAttribute('aria-label');
            
            if (ariaLabel === 'Notifications') {
                const modal = document.getElementById('notificationModal');
                const messageModal = document.getElementById('messageModal');
                const messageContentModal = document.getElementById('messageContentModal');
                const messageBtn = document.querySelector('.notification-btn[aria-label="Messages"]');
                
                // Remove active class from message button
                if (messageBtn) messageBtn.classList.remove('active');
                
                // Close other modals first
                if (messageModal) messageModal.classList.remove('show');
                if (messageContentModal) messageContentModal.classList.remove('show');
                
                // Toggle notification modal and active state
                if (modal.classList.contains('show')) {
                    modal.classList.remove('show');
                    this.classList.remove('active');
                    document.body.style.overflow = '';
                } else {
                    modal.classList.add('show');
                    this.classList.add('active');
                    document.body.style.overflow = '';
                    markHeaderNotificationsRead(true);
                    closeIncidentReportModal();
                }
            } else if (ariaLabel === 'Messages') {
                const modal = document.getElementById('messageModal');
                const notificationModal = document.getElementById('notificationModal');
                const messageContentModal = document.getElementById('messageContentModal');
                const notificationBtn = document.querySelector('.notification-btn[aria-label="Notifications"]');
                
                // Remove active class from notification button
                if (notificationBtn) notificationBtn.classList.remove('active');
                
                // Close other modals first
                if (notificationModal) notificationModal.classList.remove('show');
                if (messageContentModal) messageContentModal.classList.remove('show');
                
                // Toggle message modal and active state
                if (modal.classList.contains('show')) {
                    modal.classList.remove('show');
                    this.classList.remove('active');
                    document.body.style.overflow = '';
                } else {
                    modal.classList.add('show');
                    this.classList.add('active');
                    document.body.style.overflow = '';
                }
            }
        });
    });
    
    // User profile dropdown functionality
    const userProfileBtn = document.getElementById('userProfileBtn');
    const userProfileDropdown = document.getElementById('userProfileDropdown');
    
    if (userProfileBtn && userProfileDropdown) {
        userProfileBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            // Close all modals first (except message content modal)
            const notificationModal = document.getElementById('notificationModal');
            const messageModal = document.getElementById('messageModal');
            const messageContentModal = document.getElementById('messageContentModal');
            
            if (notificationModal) notificationModal.classList.remove('show');
            if (messageModal) messageModal.classList.remove('show');
            // Don't close messageContentModal - let it stay open like Facebook chat
            
            // Remove active states from notification buttons
            const notificationBtn = document.querySelector('.notification-btn[aria-label="Notifications"]');
            const messageBtn = document.querySelector('.notification-btn[aria-label="Messages"]');
            if (notificationBtn) notificationBtn.classList.remove('active');
            if (messageBtn) messageBtn.classList.remove('active');
            
            // Toggle user profile dropdown and active state
            const isOpen = userProfileDropdown.classList.contains('show');
            userProfileDropdown.classList.toggle('show');
            userProfileBtn.classList.toggle('active', !isOpen);
        });
    }
    
    // Close modals when clicking outside
    document.addEventListener('click', function(e) {
        const notificationModal = document.getElementById('notificationModal');
        const messageModal = document.getElementById('messageModal');
        const messageContentModal = document.getElementById('messageContentModal');
        const userProfileDropdown = document.getElementById('userProfileDropdown');
        const notificationBtn = document.querySelector('.notification-btn[aria-label="Notifications"]');
        const messageBtn = document.querySelector('.notification-btn[aria-label="Messages"]');
        
        // Close notification modal when clicking outside
        if (notificationModal && notificationModal.classList.contains('show')) {
            if (!notificationModal.contains(e.target) && !e.target.closest('.notification-btn[aria-label="Notifications"]')) {
                notificationModal.classList.remove('show');
                if (notificationBtn) notificationBtn.classList.remove('active');
                document.body.style.overflow = '';
            }
        }
        
        // Close message modal when clicking outside
        if (messageModal && messageModal.classList.contains('show')) {
            if (!messageModal.contains(e.target) && !e.target.closest('.notification-btn[aria-label="Messages"]')) {
                messageModal.classList.remove('show');
                if (messageBtn) messageBtn.classList.remove('active');
                document.body.style.overflow = '';
            }
        }
        
        // Close user profile dropdown when clicking outside
        if (userProfileDropdown && userProfileDropdown.classList.contains('show')) {
            if (!userProfileDropdown.contains(e.target) && !e.target.closest('#userProfileBtn')) {
                userProfileDropdown.classList.remove('show');
                userProfileBtn.classList.remove('active');
            }
        }
        
        // Message content modal stays open when clicking outside (don't close it)
    });
    
    // Close modals on Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeAllModals();
        }
    });
    
    // Modal functions
    function openModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.add('show');
            // Don't hide body scroll for message content modal (Facebook style)
            if (modalId !== 'messageContentModal') {
                document.body.style.overflow = 'hidden';
            }
        }
    }
    
    function closeModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.remove('show');
            document.body.style.overflow = '';
        }
    }
    
    function closeAllModals() {
        const modals = document.querySelectorAll('.notification-modal, .message-content-modal');
        modals.forEach(modal => {
            modal.classList.remove('show');
        });
        closeIncidentReportModal();
        document.body.style.overflow = '';
    }
    
    // Message item interactions
    const messageItems = document.querySelectorAll('.message-item');
    messageItems.forEach(item => {
        item.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const userName = this.querySelector('.message-title').textContent;
            const userAvatar = this.querySelector('.message-avatar img').src;
            const messageText = this.querySelector('.message-text').textContent;
            const messageTime = this.querySelector('.message-time').textContent;
            
            // Remove active state from message button when opening chat
            const messageBtn = document.querySelector('.notification-btn[aria-label="Messages"]');
            if (messageBtn) messageBtn.classList.remove('active');
            
            // Close message dropdown modal
            const messageModal = document.getElementById('messageModal');
            if (messageModal) messageModal.classList.remove('show');
            
            // Open message content modal
            openMessageContent(userName, userAvatar, messageText, messageTime);
            
            // Remove unread status
            const statusDot = this.querySelector('.message-status.unread');
            if (statusDot) {
                statusDot.classList.remove('unread');
            }
        });
    });
    
    // Message content functions
    function openMessageContent(userName, userAvatar, lastMessage, messageTime) {
        const modal = document.getElementById('messageContentModal');
        const nameElement = document.getElementById('messageUserName');
        const avatarElement = document.getElementById('messageUserAvatar');
        const contentElement = document.getElementById('messageContent');
        const statusElement = document.getElementById('messageUserStatus');
        
        // Set user info
        nameElement.textContent = userName;
        avatarElement.src = userAvatar;
        avatarElement.alt = userName;
        statusElement.textContent = 'Active now';
        
        // Create conversation HTML
        contentElement.innerHTML = `
            <div class="chat-message received">
                <div class="message-bubble">${lastMessage}</div>
                <div class="message-time">${messageTime}</div>
            </div>
            <div class="chat-message sent">
                <div class="message-bubble">Thanks for reaching out! I'll get back to you soon.</div>
                <div class="message-time">Just now</div>
            </div>
        `;
        
        // Close message modal and open content modal
        closeModal('messageModal');
        modal.classList.add('show');
        // Don't hide body scroll for Facebook-style chat
        document.body.style.overflow = '';
    }
    
    // Send message functionality
    const sendBtn = document.querySelector('.send-message-btn');
    const messageInput = document.getElementById('messageReplyInput');
    
    if (sendBtn && messageInput) {
        sendBtn.addEventListener('click', sendMessage);
        messageInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                sendMessage();
            }
        });
    }
    
    function sendMessage() {
        const message = messageInput.value.trim();
        if (message) {
            const contentElement = document.getElementById('messageContent');
            const newMessage = document.createElement('div');
            newMessage.className = 'chat-message sent';
            newMessage.innerHTML = `
                <div class="message-bubble">${message}</div>
                <div class="message-time">Just now</div>
            `;
            contentElement.appendChild(newMessage);
            messageInput.value = '';
            
            // Scroll to bottom
            contentElement.scrollTop = contentElement.scrollHeight;
        }
    }
    
    // Make functions globally accessible
    window.openModal = openModal;
    window.closeModal = closeModal;
    window.closeAllModals = closeAllModals;
    
    // User profile interaction
    const userProfile = document.querySelector('.admin-header .user-profile');
    if (userProfile) {
        userProfile.addEventListener('click', function() {
            console.log('User profile clicked');
        });
    }
    
    // Logout handler
    function handleLogout() {
        // Clear localStorage
        localStorage.removeItem('admin_remember');
        localStorage.removeItem('admin_email');
        localStorage.removeItem('admin_login_attempts');
        localStorage.removeItem('admin_account_locked');
        localStorage.removeItem('admin_lockout_time');
        
        // Redirect to logout page (all sidebar pages are in sidebar/ directory)
        window.location.href = '../logout.php';
    }
    
    // Make logout handler globally accessible
    window.handleLogout = handleLogout;
    
    // Load admin profile info dynamically if not set
    function loadAdminProfile() {
        const adminName = document.getElementById('adminDisplayName');
        const adminEmail = document.getElementById('dropdownAdminEmail');
        const adminNameDropdown = document.getElementById('dropdownAdminName');
        const adminAvatar = document.getElementById('adminAvatarImg');
        const dropdownAvatar = document.getElementById('dropdownAdminAvatar');
        
        // Check if admin info is missing or default
        if (adminName && (adminName.textContent === 'Admin User' || !adminName.textContent.trim())) {
            fetch(window.API_BASE_PATH + 'get-admin-profile.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.profile) {
                        const profile = data.profile;
                        const name = profile.name || profile.username || 'Admin User';
                        const email = profile.email || 'admin@example.com';
                        
                        // Update header
                        if (adminName) adminName.textContent = name;
                        if (adminNameDropdown) adminNameDropdown.textContent = name;
                        if (adminEmail) adminEmail.textContent = email;
                        
                        // Update avatars
                        const avatarUrl = `https://ui-avatars.com/api/?name=${encodeURIComponent(name)}&background=4c8a89&color=fff&size=128`;
                        if (adminAvatar) adminAvatar.src = avatarUrl;
                        if (dropdownAvatar) dropdownAvatar.src = avatarUrl;
                    }
                })
                .catch(error => {
                    console.error('Error loading admin profile:', error);
                });
        }
    }
    
    // Load admin profile on page load
    loadAdminProfile();

    // Header badges (notifications + messages)
    function shouldShowHeaderBadges() {
        return localStorage.getItem('showBadges') !== 'false';
    }

    function setHeaderBadgeCount(el, count) {
        if (!el) return;
        const safeCount = Math.max(0, parseInt(count || 0, 10));
        const show = shouldShowHeaderBadges() && safeCount > 0;
        el.textContent = String(safeCount);
        el.style.display = show ? 'inline-flex' : 'none';
    }

    window.updateHeaderBadges = function updateHeaderBadges({ notifications, messages } = {}) {
        const notificationBadge = document.getElementById('notificationBadge');
        const messageBadge = document.getElementById('messageBadge');
        if (typeof notifications !== 'undefined') setHeaderBadgeCount(notificationBadge, notifications);
        if (typeof messages !== 'undefined') setHeaderBadgeCount(messageBadge, messages);
    };

    // Initialize badges as hidden unless counts are provided
    window.updateHeaderBadges({ notifications: 0, messages: 0 });

    // React to showBadges toggles
    window.addEventListener('storage', (e) => {
        if (e.key === 'showBadges') {
            const notificationBadge = document.getElementById('notificationBadge');
            const messageBadge = document.getElementById('messageBadge');
            if (notificationBadge) setHeaderBadgeCount(notificationBadge, notificationBadge.textContent);
            if (messageBadge) setHeaderBadgeCount(messageBadge, messageBadge.textContent);
        }
    });

    const HEADER_NOTIFICATION_POLL_MS = 8000;
    let lastIncidentModalKey = sessionStorage.getItem('header_last_incident_modal_key') || '';

    function escapeHtml(value) {
        const text = value == null ? '' : String(value);
        return text
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function formatTimeAgo(value) {
        if (!value) return 'Recently';
        const ts = typeof value === 'string' ? Date.parse(value) : Number(value);
        if (!ts || Number.isNaN(ts)) return 'Recently';
        const diff = Date.now() - ts;
        if (diff < 60000) return 'Just now';
        if (diff < 3600000) return Math.floor(diff / 60000) + ' minutes ago';
        if (diff < 86400000) return Math.floor(diff / 3600000) + ' hours ago';
        if (diff < 604800000) return Math.floor(diff / 86400000) + ' days ago';
        return new Date(ts).toLocaleDateString();
    }

    function buildNotificationIcon(item) {
        const type = String(item && item.type ? item.type : 'system').toLowerCase();
        const channel = String(item && item.channel ? item.channel : '').toLowerCase();
        const status = String(item && item.status ? item.status : '').toLowerCase();
        const priority = String(item && item.priority ? item.priority : '').toLowerCase();

        if (type === 'incident') {
            if (priority === 'critical' || channel === 'chat_risk') {
                return { icon: 'fa-triangle-exclamation', className: 'notification-icon--incident' };
            }
            return { icon: 'fa-bell', className: 'notification-icon--incident' };
        }
        if (status === 'failed') {
            return { icon: 'fa-circle-xmark', className: 'notification-icon--failed' };
        }
        if (channel.indexOf('weather') !== -1 || channel.indexOf('earthquake') !== -1) {
            return { icon: 'fa-cloud-bolt', className: 'notification-icon--warn' };
        }
        return { icon: 'fa-circle-info', className: 'notification-icon--system' };
    }

    function buildNotificationTarget(item) {
        const type = String(item && item.type ? item.type : '').toLowerCase();
        if (type !== 'incident') {
            return null;
        }
        const convId = parseInt(item && item.conversation_id ? item.conversation_id : 0, 10);
        if (convId > 0) {
            return `two-way-communication.php?conversationId=${encodeURIComponent(convId)}`;
        }
        return 'two-way-communication.php';
    }

    function renderSystemNotifications(items) {
        const body = document.getElementById('headerNotificationBody');
        if (!body) return;

        if (!Array.isArray(items) || items.length === 0) {
            body.innerHTML = '<div class="notification-item notification-item--placeholder"><div class="notification-details"><div class="notification-title">No notifications</div><div class="notification-text">You are all caught up.</div></div></div>';
            return;
        }

        body.innerHTML = items.map((item) => {
            const safeItem = item || {};
            const type = String(safeItem.type || 'system').toLowerCase();
            const title = String(safeItem.title || (type === 'incident' ? 'Incident Report' : 'System Notification')).trim();
            const message = String(safeItem.message || '').trim();
            const location = String(safeItem.location || '').trim();
            const timeText = formatTimeAgo(safeItem.sent_at || safeItem.created_at || safeItem.event_time);
            const iconMeta = buildNotificationIcon(safeItem);
            const itemClass = type === 'incident' ? 'notification-item--incident' : 'notification-item--system';
            const target = buildNotificationTarget(safeItem);
            const actionHtml = target
                ? `<a href="${escapeHtml(target)}" class="notification-action-link"><i class="fas fa-arrow-up-right-from-square"></i> Open</a>`
                : '';

            return `
                <div class="notification-item ${itemClass}" data-notification-type="${escapeHtml(type)}">
                    <div class="notification-icon ${escapeHtml(iconMeta.className)}">
                        <i class="fas ${escapeHtml(iconMeta.icon)}"></i>
                    </div>
                    <div class="notification-details">
                        <div class="notification-title">${escapeHtml(title)}</div>
                        <div class="notification-text">${escapeHtml(message || 'Notification received.')}</div>
                        ${location ? `<div class="notification-text notification-location"><i class="fas fa-location-dot"></i> ${escapeHtml(location)}</div>` : ''}
                        <div class="notification-time">${escapeHtml(timeText)}</div>
                        ${actionHtml}
                    </div>
                </div>
            `;
        }).join('');
    }

    function incidentModalElements() {
        return {
            shell: document.getElementById('headerIncidentModal'),
            message: document.getElementById('headerIncidentModalMessage'),
            meta: document.getElementById('headerIncidentModalMeta'),
            open: document.getElementById('headerIncidentModalOpen'),
            close: document.getElementById('headerIncidentModalClose'),
            dismiss: document.getElementById('headerIncidentModalDismiss'),
        };
    }

    function closeIncidentReportModal() {
        const { shell } = incidentModalElements();
        if (!shell) return;
        shell.classList.remove('show');
        shell.setAttribute('aria-hidden', 'true');
    }

    function openIncidentReportModal(item) {
        const elements = incidentModalElements();
        if (!elements.shell || !elements.message || !elements.meta) {
            return;
        }

        const title = String(item && item.title ? item.title : 'Incident Report').trim();
        const message = String(item && item.message ? item.message : 'A new incident was reported.').trim();
        const location = String(item && item.location ? item.location : '').trim();
        const sentAt = item && (item.sent_at || item.created_at || item.event_time) ? (item.sent_at || item.created_at || item.event_time) : '';
        const timeText = formatTimeAgo(sentAt);

        elements.message.textContent = `${title}: ${message}`;
        const metaParts = [];
        if (location) metaParts.push(`Location: ${location}`);
        if (timeText) metaParts.push(timeText);
        elements.meta.textContent = metaParts.join(' | ');

        if (elements.open) {
            const target = buildNotificationTarget(item) || 'two-way-communication.php';
            elements.open.setAttribute('href', target);
        }

        elements.shell.classList.add('show');
        elements.shell.setAttribute('aria-hidden', 'false');
    }

    function buildIncidentItemKey(item) {
        if (!item || typeof item !== 'object') return '';
        const id = item.id ? String(item.id) : '';
        const conv = item.conversation_id ? String(item.conversation_id) : '';
        const ts = item.sent_at ? String(item.sent_at) : '';
        const msg = item.message ? String(item.message).slice(0, 80) : '';
        return [id, conv, ts, msg].join('|');
    }

    function maybeShowIncidentReportModal(incidentItems) {
        if (!Array.isArray(incidentItems) || incidentItems.length === 0) {
            return;
        }
        const latest = incidentItems[0];
        const key = buildIncidentItemKey(latest);
        if (!key || key === lastIncidentModalKey) {
            return;
        }
        lastIncidentModalKey = key;
        sessionStorage.setItem('header_last_incident_modal_key', key);
        openIncidentReportModal(latest);
    }

    function markHeaderNotificationsRead(reload = false) {
        localStorage.setItem('systemNotificationsLastRead', String(Date.now()));
        window.updateHeaderBadges({ notifications: 0 });
        if (reload) {
            loadHeaderNotifications({ suppressIncidentModal: true });
        }
    }

    function loadHeaderNotifications(options = {}) {
        const suppressIncidentModal = !!(options && options.suppressIncidentModal);
        const bellBtn = document.getElementById('headerNotificationBtn');
        if (!bellBtn || !window.API_BASE_PATH) return;

        let lastRead = parseInt(localStorage.getItem('systemNotificationsLastRead') || '0', 10);
        if (!Number.isFinite(lastRead) || lastRead <= 0) {
            lastRead = Date.now();
            localStorage.setItem('systemNotificationsLastRead', String(lastRead));
        }

        const url = `${window.API_BASE_PATH}header-notifications.php?since=${encodeURIComponent(String(lastRead))}`;
        fetch(url)
            .then(res => res.json())
            .then(data => {
                if (!data || !data.success) return;

                const notificationUnread = parseInt(data.notification_unread || 0, 10);
                const messageUnreadRaw = Object.prototype.hasOwnProperty.call(data, 'message_unread')
                    ? data.message_unread
                    : (data.incident_unread || 0);
                const messageUnread = parseInt(messageUnreadRaw || 0, 10);
                window.updateHeaderBadges({
                    notifications: Number.isFinite(notificationUnread) ? notificationUnread : 0,
                    messages: Number.isFinite(messageUnread) ? messageUnread : 0
                });

                const list = Array.isArray(data.notifications)
                    ? data.notifications
                    : (Array.isArray(data.system_notifications) ? data.system_notifications : []);
                renderSystemNotifications(list);

                if (!suppressIncidentModal && !document.hidden) {
                    maybeShowIncidentReportModal(Array.isArray(data.incident_notifications) ? data.incident_notifications : []);
                }
            })
            .catch((error) => {
                console.warn('header notifications fetch failed:', error);
            });
    }

    const markReadBtn = document.getElementById('headerMarkNotificationsReadBtn');
    if (markReadBtn) {
        markReadBtn.addEventListener('click', function () {
            markHeaderNotificationsRead(true);
        });
    }

    const incidentModalEls = incidentModalElements();
    if (incidentModalEls.close) {
        incidentModalEls.close.addEventListener('click', closeIncidentReportModal);
    }
    if (incidentModalEls.dismiss) {
        incidentModalEls.dismiss.addEventListener('click', closeIncidentReportModal);
    }
    if (incidentModalEls.shell) {
        incidentModalEls.shell.addEventListener('click', function (event) {
            if (event.target === incidentModalEls.shell) {
                closeIncidentReportModal();
            }
        });
    }

    // Initial fetch + refresh
    loadHeaderNotifications();
    setInterval(() => loadHeaderNotifications(), HEADER_NOTIFICATION_POLL_MS);
    // Two-way communication notifications are handled by header-notifications.php polling.
    function initGlobalChatNotifications() {}
    
    // Initialize on page load
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initGlobalChatNotifications);
    } else {
        initGlobalChatNotifications();
    }
});
</script>

<!-- Emergency Alert System -->
<script src="<?php echo $headerBase; ?>../header/js/emergency-alert.js"></script>
<!-- Replace browser alert() with themed modal -->
<script src="<?php echo $headerBase; ?>../assets/shared/js/ui-alert.js?v=<?php echo filemtime(__DIR__ . '/../../assets/shared/js/ui-alert.js'); ?>"></script>
<script src="<?php echo $headerBase; ?>../assets/shared/js/module-analytics-strip.js?v=<?php echo filemtime(__DIR__ . '/../../assets/shared/js/module-analytics-strip.js'); ?>"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
<script src="<?php echo $headerBase; ?>js/report-pdf-export.js?v=<?php echo filemtime(__DIR__ . '/../js/report-pdf-export.js'); ?>"></script>
<script>
    // API base is initialized in the header script above.

    // Date Time Update
    document.addEventListener('DOMContentLoaded', function() {
        function updateHeaderTime() {
            const now = new Date();
            const dateOptions = { weekday: 'short', month: 'short', day: 'numeric', year: 'numeric' };
            const timeOptions = { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true };
            
            const dateStr = now.toLocaleDateString('en-US', dateOptions);
            const timeStr = now.toLocaleTimeString('en-US', timeOptions);
            
            const container = document.getElementById('headerDateTime');
            if (container) {
                container.querySelector('.date-part').textContent = dateStr;
                container.querySelector('.time-part').textContent = timeStr;
            }
        }
    
        setInterval(updateHeaderTime, 1000);
        updateHeaderTime(); // Initial call
    });
</script>
