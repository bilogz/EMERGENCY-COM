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
                    $servicePath = __DIR__ . '/../../services/AdminService.php';
                    if (!file_exists($servicePath)) {
                        $servicePath = __DIR__ . '/../services/AdminService.php';
                    }
                    
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
?>

<link rel="stylesheet" href="css/notification-modal.css">
<link rel="stylesheet" href="css/message-modal.css">
<link rel="stylesheet" href="css/message-content-modal.css">
<!-- Emergency Alert System -->
<link rel="stylesheet" href="../header/css/emergency-alert.css">
<style>
/* Date Time Display */
.datetime-display {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-right: 1.5rem;
    color: var(--text-color-1);
    font-size: 0.9rem;
    font-weight: 500;
    white-space: nowrap;
}

.datetime-display .date-part {
    color: var(--text-secondary-1);
}

.datetime-display .time-separator {
    color: var(--border-color-1);
    margin: 0 0.25rem;
}

.datetime-display .time-part {
    font-variant-numeric: tabular-nums;
    color: var(--primary-color-1);
    font-weight: 600;
}

@media (max-width: 1024px) {
    .datetime-display {
        display: none;
    }
}
</style>

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
            
            <?php if (!$hideNotifications): ?>
            <div class="notification-item">
                <button class="notification-btn" aria-label="Notifications">
                    <i class="fas fa-bell"></i>
                    <span class="notification-badge">3</span>
                </button>
            </div>
            <?php endif; ?>
            
            <div class="notification-item">
                <button class="notification-btn" aria-label="Messages">
                    <i class="fas fa-envelope"></i>
                    <span class="notification-badge">5</span>
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
        <div class="modal-body">
            <div class="notification-item">
                <div class="notification-icon">
                    <i class="fas fa-info-circle"></i>
                </div>
                <div class="notification-details">
                    <div class="notification-title">System Update</div>
                    <div class="notification-text">System will be updated tonight at 11 PM</div>
                    <div class="notification-time">2 hours ago</div>
                </div>
            </div>
            <div class="notification-item">
                <div class="notification-icon">
                    <i class="fas fa-user-plus"></i>
                </div>
                <div class="notification-details">
                    <div class="notification-title">New User Registered</div>
                    <div class="notification-text">John Doe joined the platform</div>
                    <div class="notification-time">5 hours ago</div>
                </div>
            </div>
            <div class="notification-item">
                <div class="notification-icon">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <div class="notification-details">
                    <div class="notification-title">Storage Warning</div>
                    <div class="notification-text">Disk space is running low (85% used)</div>
                    <div class="notification-time">1 day ago</div>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <a href="#" class="view-all-link">View All Notifications</a>
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
// Admin Header functionality
document.addEventListener('DOMContentLoaded', function() {
    const menuToggle = document.getElementById('menuToggle');
    const lightModeBtn = document.getElementById('lightModeBtn');
    const darkModeBtn = document.getElementById('darkModeBtn');
    
    // Initialize theme
    function initTheme() {
        const savedTheme = localStorage.getItem('theme') || 'light';
        const html = document.documentElement;
        
        // If system theme, detect preference
        if (savedTheme === 'system') {
            const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            html.setAttribute('data-theme', prefersDark ? 'dark' : 'light');
            updateThemeButtons(prefersDark ? 'dark' : 'light');
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
        if (localStorage.getItem('theme') === 'system') {
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
            fetch('../api/get-admin-profile.php')
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
    
    // Global Chat Notification System - Redirects to Two-Way Communication
    function initGlobalChatNotifications() {
        // Only initialize if Firebase is available and we're not already on chat pages
        if (typeof firebase === 'undefined') {
            // Load Firebase SDKs - Use compat version for non-module usage
            const firebaseAppScript = document.createElement('script');
            firebaseAppScript.src = 'https://www.gstatic.com/firebasejs/9.22.0/firebase-app-compat.js';
            document.head.appendChild(firebaseAppScript);
            
            const firebaseDatabaseScript = document.createElement('script');
            firebaseDatabaseScript.src = 'https://www.gstatic.com/firebasejs/9.22.0/firebase-database-compat.js';
            document.head.appendChild(firebaseDatabaseScript);
            
            // Wait for Firebase to load
            firebaseAppScript.onload = () => {
                firebaseDatabaseScript.onload = () => {
                    // Add small delay to ensure Firebase is fully initialized
                    setTimeout(() => {
                        if (typeof firebase !== 'undefined') {
                            setupChatNotifications();
                        } else {
                            console.warn('Firebase failed to load, chat notifications unavailable');
                        }
                    }, 100);
                };
            };
            
            // Error handling
            firebaseAppScript.onerror = () => {
                console.error('Failed to load Firebase App');
            };
            firebaseDatabaseScript.onerror = () => {
                console.error('Failed to load Firebase Database');
            };
        } else {
            setupChatNotifications();
        }
    }
    
    function setupChatNotifications() {
        try {
            const currentPage = window.location.pathname;
            const isChatPage = currentPage.includes('two-way-communication') || currentPage.includes('chat-queue');
            
            // Don't show notifications on chat pages
            if (isChatPage) return;
            
            // Verify Firebase is available
            if (typeof firebase === 'undefined') {
                console.warn('Firebase not available, skipping chat notifications');
                return;
            }
            
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
            
            // Listen for new chat queue items
            const chatQueueRef = database.ref('chat_queue').orderByChild('status').equalTo('pending');
            let lastNotificationTime = 0;
            const notificationCooldown = 5000; // 5 seconds between notifications
            
            chatQueueRef.on('child_added', (snapshot) => {
                const queueItem = snapshot.val();
                const now = Date.now();
                
                // Prevent duplicate notifications
                if (now - lastNotificationTime < notificationCooldown) {
                    return;
                }
                
                lastNotificationTime = now;
                showChatNotification(queueItem);
            });
            
            console.log('✅ Chat notifications initialized successfully');
        } catch (error) {
            console.error('❌ Error setting up chat notifications:', error);
            // Fail gracefully - don't break the page
        }
    }
    
    function showChatNotification(queueItem) {
        // Create notification element
        const notification = document.createElement('div');
        notification.className = 'global-chat-notification';
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.15);
            padding: 1.25rem;
            max-width: 380px;
            z-index: 10000;
            animation: slideInRight 0.3s ease;
            border-left: 4px solid #2196f3;
        `;
        
        const guestBadge = queueItem.isGuest ? '<span style="background: #ff9800; color: white; padding: 0.2rem 0.5rem; border-radius: 4px; font-size: 0.7rem; margin-left: 0.5rem;">GUEST</span>' : '';
        const concernBadge = queueItem.userConcern ? `<span style="background: #2196f3; color: white; padding: 0.2rem 0.5rem; border-radius: 4px; font-size: 0.7rem; margin-left: 0.5rem; text-transform: capitalize;">${queueItem.userConcern}</span>` : '';
        
        notification.innerHTML = `
            <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 0.75rem;">
                <div style="flex: 1;">
                    <h4 style="margin: 0 0 0.5rem 0; font-size: 1rem; color: #333;">
                        <i class="fas fa-comments" style="color: #2196f3; margin-right: 0.5rem;"></i>
                        New Message${guestBadge}${concernBadge}
                    </h4>
                    <p style="margin: 0 0 0.5rem 0; font-size: 0.9rem; color: #666; font-weight: 600;">
                        ${queueItem.userName || 'User'}
                    </p>
                    <p style="margin: 0; font-size: 0.85rem; color: #888; line-height: 1.4;">
                        ${queueItem.message || 'Sent a message'}
                    </p>
                    ${queueItem.userLocation ? `<p style="margin: 0.25rem 0 0 0; font-size: 0.8rem; color: #999;"><i class="fas fa-map-marker-alt"></i> ${queueItem.userLocation}</p>` : ''}
                </div>
                <button onclick="this.parentElement.parentElement.remove()" style="background: none; border: none; font-size: 1.25rem; cursor: pointer; color: #999; padding: 0; margin-left: 0.5rem;">&times;</button>
            </div>
            <div style="display: flex; gap: 0.5rem; margin-top: 1rem;">
                <a href="two-way-communication.php" class="btn btn-primary" style="flex: 1; text-align: center; padding: 0.6rem; text-decoration: none; border-radius: 6px; font-size: 0.9rem;">
                    <i class="fas fa-comments"></i> View Messages
                </a>
                <button onclick="this.parentElement.parentElement.remove()" class="btn btn-secondary" style="padding: 0.6rem 1rem; border-radius: 6px; font-size: 0.9rem;">
                    Dismiss
                </button>
            </div>
        `;
        
        document.body.appendChild(notification);
        
        // Auto-remove after 10 seconds
        setTimeout(() => {
            if (notification.parentElement) {
                notification.style.animation = 'slideOutRight 0.3s ease';
                setTimeout(() => notification.remove(), 300);
            }
        }, 10000);
    }
    
    // Add CSS animations
    if (!document.getElementById('global-chat-notification-styles')) {
        const style = document.createElement('style');
        style.id = 'global-chat-notification-styles';
        style.textContent = `
            @keyframes slideInRight {
                from {
                    transform: translateX(100%);
                    opacity: 0;
                }
                to {
                    transform: translateX(0);
                    opacity: 1;
                }
            }
            @keyframes slideOutRight {
                from {
                    transform: translateX(0);
                    opacity: 1;
                }
                to {
                    transform: translateX(100%);
                    opacity: 0;
                }
            }
            [data-theme="dark"] .global-chat-notification {
                background: #1f2228;
                border-left-color: #2196f3;
                color: #ffffff;
            }
            [data-theme="dark"] .global-chat-notification h4 {
                color: #ffffff;
            }
            [data-theme="dark"] .global-chat-notification p {
                color: #cccccc;
            }
        `;
        document.head.appendChild(style);
    }
    
    // Initialize on page load
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initGlobalChatNotifications);
    } else {
        initGlobalChatNotifications();
    }
});
</script>

<!-- Emergency Alert System -->
<script src="../header/js/emergency-alert.js"></script>
<script>
    // Set API endpoint for emergency alerts in admin context
    if (typeof window.API_BASE_PATH === 'undefined') {
        window.API_BASE_PATH = '../api/';
    }

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