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

// Include guest monitoring notice
include __DIR__ . '/guest-monitoring-notice.php';
?>
<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <div class="sidebar-brand">
            <div class="brand-logo">
                <a href="<?= $isRootContext ? 'index.php' : '../index.php' ?>" class="logo-link">
                    <img src="<?= $assetSidebar ?>images/logo.svg" alt="Logo" class="logo-img">
                </a>
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
                    <li class="sidebar-menu-item">
                        <a href="<?= $basePath ?><?= $linkPrefix ?>profile.php" class="sidebar-link <?= $current === 'profile.php' ? 'active' : '' ?>">
                            <i class="fas fa-user-cog"></i>
                            <span>Profile</span>
                        </a>
                    </li>
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
<button class="chat-fab" id="chatFab" aria-label="Open chat">
    <i class="fas fa-comments"></i>
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
            <p class="chat-hint">
                This is a demo chat panel. Describe your emergency or question and a responder can reply using this channel.
            </p>
            <div class="chat-messages">
                <div class="chat-message chat-message-system">
                    <strong>System:</strong> For life-threatening emergencies, call 911 or the Quezon City emergency hotlines immediately.
                </div>
            </div>
            <form class="chat-input-row" id="chatForm">
                <input type="text" id="chatInput" placeholder="Type your message..." autocomplete="off">
                <button type="submit" class="btn btn-primary">Send</button>
            </form>
        </div>
    </div>
</div>

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

    // Chat modal behaviour
    function openChat() {
        if (!chatModal) return;
        chatModal.classList.add('chat-modal-open');
        chatModal.setAttribute('aria-hidden', 'false');
        if (chatInput) {
            chatInput.focus();
        }
    }

    function closeChat() {
        if (!chatModal) return;
        chatModal.classList.remove('chat-modal-open');
        chatModal.setAttribute('aria-hidden', 'true');
    }

    if (chatFab) {
        chatFab.addEventListener('click', openChat);
    }

    if (chatCloseBtn) {
        chatCloseBtn.addEventListener('click', closeChat);
    }

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && chatModal && chatModal.classList.contains('chat-modal-open')) {
            closeChat();
        }
    });

    if (chatModal) {
        chatModal.addEventListener('click', function (e) {
            if (e.target === chatModal) {
                closeChat();
            }
        });
    }

    if (chatForm && chatInput && chatMessages) {
        chatForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const text = chatInput.value.trim();
            if (!text) return;
            const msg = document.createElement('div');
            msg.className = 'chat-message chat-message-user';
            msg.innerHTML = '<strong>You:</strong> ' + text;
            chatMessages.appendChild(msg);
            chatMessages.scrollTop = chatMessages.scrollHeight;
            chatInput.value = '';
        });
    }
});
</script>

