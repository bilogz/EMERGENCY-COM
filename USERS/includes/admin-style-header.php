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
            <input type="text" class="search-input" id="headerSearchInput" placeholder="Search pages..." autocomplete="off">
            <div class="search-dropdown" id="searchDropdown"></div>
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
    const searchInput = document.getElementById('headerSearchInput');
    const searchDropdown = document.getElementById('searchDropdown');

    // Searchable pages configuration
    const searchablePages = [
        { name: 'Home', url: 'index.php', keywords: ['home', 'main', 'dashboard'] },
        { name: 'Alerts', url: 'alerts.php', keywords: ['alerts', 'notifications', 'warnings'] },
        { name: 'Support', url: 'support.php', keywords: ['support', 'help', 'contact'] },
        { name: 'Weather Map', url: 'weather-map.php', keywords: ['weather', 'map', 'forecast'] },
        { name: 'Weather Monitoring', url: 'weather-monitoring.php', keywords: ['weather', 'monitoring', 'forecast'] },
        { name: 'Earthquake Monitoring', url: 'earthquake-monitoring.php', keywords: ['earthquake', 'seismic', 'quake'] },
        { name: 'Emergency Call', url: 'emergency-call.php', keywords: ['emergency', 'call', 'hotline', '911'] },
        { name: 'Profile', url: 'profile.php', keywords: ['profile', 'account', 'settings'] },
        { name: 'Login', url: 'login.php', keywords: ['login', 'signin', 'auth'] },
        { name: 'Signup', url: 'signup.php', keywords: ['signup', 'register', 'create account'] }
    ];

    // Realtime search functionality
    if (searchInput && searchDropdown) {
        let selectedIndex = -1;
        let filteredResults = [];

        searchInput.addEventListener('input', function() {
            const query = this.value.trim().toLowerCase();
            selectedIndex = -1;

            if (query.length === 0) {
                searchDropdown.style.display = 'none';
                searchDropdown.innerHTML = '';
                return;
            }

            // Filter pages based on query
            filteredResults = searchablePages.filter(page => {
                const matchName = page.name.toLowerCase().includes(query);
                const matchKeywords = page.keywords.some(kw => kw.includes(query));
                return matchName || matchKeywords;
            });

            if (filteredResults.length > 0) {
                searchDropdown.innerHTML = filteredResults.map((page, index) => `
                    <div class="search-result-item" data-url="${page.url}" data-index="${index}">
                        <i class="fas fa-file-alt"></i>
                        <span>${page.name}</span>
                    </div>
                `).join('');
                searchDropdown.style.display = 'block';

                // Add click handlers to results
                searchDropdown.querySelectorAll('.search-result-item').forEach(item => {
                    item.addEventListener('click', function() {
                        const url = this.getAttribute('data-url');
                        window.location.href = url;
                    });
                });
            } else {
                searchDropdown.innerHTML = '<div class="search-result-item no-results"><i class="fas fa-times-circle"></i><span>No results found</span></div>';
                searchDropdown.style.display = 'block';
            }
        });

        // Keyboard navigation
        searchInput.addEventListener('keydown', function(e) {
            const items = searchDropdown.querySelectorAll('.search-result-item:not(.no-results)');
            
            if (e.key === 'ArrowDown') {
                e.preventDefault();
                selectedIndex = Math.min(selectedIndex + 1, items.length - 1);
                updateSelection(items);
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                selectedIndex = Math.max(selectedIndex - 1, -1);
                updateSelection(items);
            } else if (e.key === 'Enter') {
                e.preventDefault();
                if (selectedIndex >= 0 && filteredResults[selectedIndex]) {
                    window.location.href = filteredResults[selectedIndex].url;
                } else if (filteredResults.length > 0) {
                    window.location.href = filteredResults[0].url;
                }
            } else if (e.key === 'Escape') {
                searchDropdown.style.display = 'none';
                searchInput.blur();
            }
        });

        function updateSelection(items) {
            items.forEach((item, index) => {
                item.classList.toggle('selected', index === selectedIndex);
            });
        }

        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (!searchInput.contains(e.target) && !searchDropdown.contains(e.target)) {
                searchDropdown.style.display = 'none';
            }
        });

        // Focus search input on '/' key
        document.addEventListener('keydown', function(e) {
            if (e.key === '/' && document.activeElement !== searchInput) {
                e.preventDefault();
                searchInput.focus();
            }
        });
    }

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
