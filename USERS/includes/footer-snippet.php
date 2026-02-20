<?php
// Shared footer snippet for user pages (extracted from index/dashboard)
// Use provided $basePath if set (from root), otherwise default to empty (from USERS)
if (!isset($basePath)) {
    $basePath = '';
}
// Detect if we're in root context (explicitly set flag from root index.php)
if (!isset($isRootContext)) {
    $isRootContext = false;
}
$linkPrefix = $isRootContext ? 'USERS/' : '';
// Use provided $assetBase if set, otherwise default
if (!isset($assetBase)) {
    $assetBase = '../ADMIN/header/';
}
?>
<footer class="footer">
    <div class="main-container">
        <div class="sub-container">
            <div class="footer-container">
                <div class="footer-bottom">
                    <div class="footer-copyright">
                        <p>&copy; <?= date('Y'); ?> LGU #4. All rights reserved.</p>
                    </div>
                    <div class="footer-legal">
                        <a href="<?= $basePath ?><?= $linkPrefix ?>privacy-policy.php" class="footer-link">Privacy Policy</a>
                        <a href="<?= $basePath ?><?= $linkPrefix ?>terms-of-service.php" class="footer-link">Terms of Service</a>
                        <a href="<?= $basePath ?><?= $linkPrefix ?>cookie-policy.php" class="footer-link">Cookie Policy</a>
                    </div>
                    <div class="theme-toggle">
                        <button class="theme-toggle-btn" data-theme="system" aria-label="System theme"><i class="fas fa-desktop"></i></button>
                        <button class="theme-toggle-btn" data-theme="light" aria-label="Light theme"><i class="fas fa-sun"></i></button>
                        <button class="theme-toggle-btn" data-theme="dark" aria-label="Dark theme"><i class="fas fa-moon"></i></button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</footer>

<script>
// User Dropdown Menu Toggle
document.addEventListener('DOMContentLoaded', function() {
    const userDropdownBtn = document.getElementById('userDropdownBtn');
    const userDropdownMenu = document.getElementById('userDropdownMenu');
    const userLogoutBtn = document.getElementById('userLogoutBtn');

    if (userDropdownMenu) {
        const seen = new Set();
        const items = Array.from(userDropdownMenu.querySelectorAll('.user-dropdown-actions .user-dropdown-link'));
        items.forEach((el) => {
            const href = (el.getAttribute('href') || '').trim();
            const txt = (el.textContent || '').replace(/\s+/g, ' ').trim();
            const key = `${href}::${txt}`;
            if (seen.has(key)) {
                el.remove();
            } else {
                seen.add(key);
            }
        });
    }
    
    // Toggle dropdown
    if (userDropdownBtn && userDropdownMenu) {
        userDropdownBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            const isVisible = userDropdownMenu.style.display === 'block';
            userDropdownMenu.style.display = isVisible ? 'none' : 'block';
            userDropdownBtn.setAttribute('aria-expanded', !isVisible);
        });
        
        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (!userDropdownBtn.contains(e.target) && !userDropdownMenu.contains(e.target)) {
                userDropdownMenu.style.display = 'none';
                userDropdownBtn.setAttribute('aria-expanded', 'false');
            }
        });
        
        // Close dropdown on escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && userDropdownMenu.style.display === 'block') {
                userDropdownMenu.style.display = 'none';
                userDropdownBtn.setAttribute('aria-expanded', 'false');
            }
        });
    }
    
    // Handle logout
    if (userLogoutBtn) {
        userLogoutBtn.addEventListener('click', async function(e) {
            e.preventDefault();
            
            // Check if SweetAlert2 is available
            if (typeof Swal === 'undefined') {
                if (confirm('Are you sure you want to log out?')) {
                    try {
                        const response = await fetch('api/logout.php');
                        const data = await response.json();
                        if (data.success) {
                            window.location.href = '<?= $basePath ?><?= $linkPrefix ?>login.php';
                        }
                    } catch (error) {
                        console.error('Logout error:', error);
                        alert('An error occurred while logging out. Please try again.');
                    }
                }
                return;
            }
            
            // Show confirmation
            const result = await Swal.fire({
                title: 'Log Out?',
                text: 'Are you sure you want to log out?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, Log Out',
                cancelButtonText: 'Cancel'
            });
            
            if (result.isConfirmed) {
                try {
                    const response = await fetch('api/logout.php');
                    const data = await response.json();
                    
                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Logged Out',
                            text: 'You have been logged out successfully.',
                            timer: 1500,
                            showConfirmButton: false
                        }).then(() => {
                            window.location.href = '<?= $basePath ?><?= $linkPrefix ?>login.php';
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: data.message || 'Failed to log out. Please try again.'
                        });
                    }
                } catch (error) {
                    console.error('Logout error:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'An error occurred while logging out. Please try again.'
                    });
                }
            }
        });
    }
});
</script>

<!-- Global Emergency Alert System (Citizen Pages) -->
<?php
$emergencyCss = $assetBase . 'css/emergency-alert.css';
$emergencyJs = $assetBase . 'js/emergency-alert.js';
?>
<link rel="stylesheet" href="<?= htmlspecialchars($emergencyCss); ?>">
<script src="<?= htmlspecialchars($emergencyJs); ?>?v=<?= file_exists(__DIR__ . '/../../ADMIN/header/js/emergency-alert.js') ? filemtime(__DIR__ . '/../../ADMIN/header/js/emergency-alert.js') : time(); ?>"></script>

