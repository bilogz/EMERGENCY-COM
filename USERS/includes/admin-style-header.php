<?php
/**
 * User-facing header styled like admin header.
 * Frontend-only: no backend/service logic.
 */

if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}

$isLoggedIn = isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] === true;
$currentPath = str_replace('\\', '/', $_SERVER['PHP_SELF'] ?? '');
$isUsersPage = strpos($currentPath, '/USERS/') !== false;
$loginHref = $isUsersPage ? 'login.php?method=facebook' : 'USERS/login.php?method=facebook';

$userName = $_SESSION['user_name'] ?? ($isLoggedIn ? 'User' : 'Guest');
$userRole = $isLoggedIn ? 'Resident' : 'Sign in';
$avatarUrl = 'https://ui-avatars.com/api/?name=' . urlencode($userName) . '&background=4c8a89&color=fff&size=128';
?>

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
        <div class="datetime-display" id="headerDateTime">
            <span class="date-part"></span>
            <span class="time-separator">|</span>
            <span class="time-part"></span>
        </div>

        <div class="header-actions">
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

            <div class="notification-item">
                <button class="notification-btn" id="headerLanguageBtn" aria-label="Language" title="Select Language">
                    <i class="fas fa-globe"></i>
                </button>
            </div>

            <div class="notification-item">
                <button class="notification-btn" aria-label="Notifications">
                    <i class="fas fa-bell"></i>
                    <span class="notification-badge">3</span>
                </button>
            </div>

            <div class="notification-item">
                <button class="notification-btn" aria-label="Messages">
                    <i class="fas fa-envelope"></i>
                    <span class="notification-badge">5</span>
                </button>
            </div>
        </div>

        <div class="header-divider"></div>

        <?php if ($isLoggedIn): ?>
        <div class="user-profile">
            <div class="user-info">
                <div class="user-name"><?php echo htmlspecialchars($userName); ?></div>
                <div class="user-role"><?php echo htmlspecialchars($userRole); ?></div>
            </div>
            <div class="user-avatar">
                <img src="<?php echo htmlspecialchars($avatarUrl); ?>" alt="<?php echo htmlspecialchars($userName); ?>" class="avatar-img">
            </div>
            <i class="fas fa-chevron-down dropdown-icon"></i>
        </div>
        <?php else: ?>
        <a class="user-profile user-profile-login-link" href="<?php echo htmlspecialchars($loginHref); ?>" title="Login with Facebook or enter details">
            <div class="user-info">
                <div class="user-name"><?php echo htmlspecialchars($userName); ?></div>
                <div class="user-role"><?php echo htmlspecialchars($userRole); ?></div>
            </div>
            <div class="user-avatar">
                <img src="<?php echo htmlspecialchars($avatarUrl); ?>" alt="<?php echo htmlspecialchars($userName); ?>" class="avatar-img">
            </div>
            <i class="fas fa-chevron-right dropdown-icon"></i>
        </a>
        <?php endif; ?>
    </div>
</header>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const menuToggle = document.getElementById('menuToggle');
    const lightModeBtn = document.getElementById('lightModeBtn');
    const darkModeBtn = document.getElementById('darkModeBtn');
    const headerLanguageBtn = document.getElementById('headerLanguageBtn');

    function updateHeaderTime() {
        const now = new Date();
        const dateStr = now.toLocaleDateString('en-US', {
            weekday: 'short',
            month: 'short',
            day: 'numeric',
            year: 'numeric'
        });
        const timeStr = now.toLocaleTimeString('en-US', {
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit',
            hour12: true
        });
        const container = document.getElementById('headerDateTime');
        if (container) {
            const dateEl = container.querySelector('.date-part');
            const timeEl = container.querySelector('.time-part');
            if (dateEl) dateEl.textContent = dateStr;
            if (timeEl) timeEl.textContent = timeStr;
        }
    }

    function setTheme(theme) {
        document.documentElement.setAttribute('data-theme', theme);
        localStorage.setItem('theme', theme);
        if (lightModeBtn && darkModeBtn) {
            lightModeBtn.classList.toggle('active', theme !== 'dark');
            darkModeBtn.classList.toggle('active', theme === 'dark');
        }
    }

    const savedTheme = localStorage.getItem('theme') || 'light';
    setTheme(savedTheme === 'dark' ? 'dark' : 'light');

    if (lightModeBtn) {
        lightModeBtn.addEventListener('click', function() {
            setTheme('light');
        });
    }

    if (darkModeBtn) {
        darkModeBtn.addEventListener('click', function() {
            setTheme('dark');
        });
    }

    if (headerLanguageBtn) {
        headerLanguageBtn.addEventListener('click', function() {
            if (window.languageSelectorModal && typeof window.languageSelectorModal.open === 'function') {
                window.languageSelectorModal.open();
                return;
            }

            const legacyLanguageBtn = document.getElementById('languageSelectorBtn');
            if (legacyLanguageBtn) {
                legacyLanguageBtn.click();
            }
        });
    }

    if (menuToggle) {
        menuToggle.addEventListener('click', function() {
            if (typeof window.sidebarToggle === 'function') {
                window.sidebarToggle();
            }
        });
    }

    updateHeaderTime();
    setInterval(updateHeaderTime, 1000);
});
</script>
