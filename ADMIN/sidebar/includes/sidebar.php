<?php
/**
 * Reusable Sidebar Component
 * Include this file in your pages where you want a sidebar: <?php include 'sidebar/sidebar.php'; ?>
 * 
 * Features:
 * - Responsive design with mobile toggle
 * - Admin-style navigation
 * - Collapsible sections
 * - Dark mode support
 * - Multiple layout options
 */

// Determine base path for links based on current directory
$currentDir = basename(dirname($_SERVER['PHP_SELF']));
$sidebarBase = ($currentDir == 'multilingual-support') ? '../' : '';
?>

<!-- Sidebar Component -->
<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <div class="sidebar-brand">
            <div class="brand-logo">
                <img src="<?php echo $sidebarBase; ?>images/logo.svg" alt="" class="logo-img">
            </div>
        </div>
    </div>
    
    <div class="sidebar-content">
        <!-- Navigation Menu -->
        <nav class="sidebar-nav">
            <!-- Admin Section -->
            <div class="sidebar-section">
                <h3 class="sidebar-section-title">Admin</h3>
                <ul class="sidebar-menu">
                    <!-- Dashboard -->
                    <?php $isActive = basename($_SERVER['PHP_SELF']) == 'dashboard.php'; ?>
                    <li class="sidebar-menu-item">
                        <a href="<?php echo $sidebarBase; ?>dashboard.php" class="sidebar-link <?php echo $isActive ? 'active' : ''; ?>">
                            <i class="fas fa-home" style="margin-right: 0.5rem; color: <?php echo $isActive ? '#ffffff' : '#2563eb'; ?>;"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    
                    <!-- Users -->
                    <?php $isActive = basename($_SERVER['PHP_SELF']) == 'users.php'; ?>
                    <li class="sidebar-menu-item">
                        <a href="<?php echo $sidebarBase; ?>users.php" class="sidebar-link <?php echo $isActive ? 'active' : ''; ?>">
                            <i class="fas fa-users" style="margin-right: 0.5rem; color: <?php echo $isActive ? '#ffffff' : '#16a34a'; ?>;"></i>
                            <span>Users</span>
                        </a>
                    </li>
                    
                    <!-- Admin Approvals -->
                    <?php $isActive = basename($_SERVER['PHP_SELF']) == 'admin-approvals.php'; ?>
                    <li class="sidebar-menu-item">
                        <a href="<?php echo $sidebarBase; ?>admin-approvals.php" class="sidebar-link <?php echo $isActive ? 'active' : ''; ?>">
                            <i class="fas fa-user-check" style="margin-right: 0.5rem; color: <?php echo $isActive ? '#ffffff' : '#9333ea'; ?>;"></i>
                            <span>Admin Approvals</span>
                        </a>
                    </li>

                    <!-- My Profile -->
                    <?php $isActive = basename($_SERVER['PHP_SELF']) == 'profile.php'; ?>
                    <li class="sidebar-menu-item">
                        <a href="<?php echo $sidebarBase; ?>profile.php" class="sidebar-link <?php echo $isActive ? 'active' : ''; ?>">
                            <i class="fas fa-user-circle" style="margin-right: 0.5rem; color: <?php echo $isActive ? '#ffffff' : '#f97316'; ?>;"></i>
                            <span>My Profile</span>
                        </a>
                    </li>

                    <!-- Settings -->
                    <?php $isActive = basename($_SERVER['PHP_SELF']) == 'general-settings.php'; ?>
                    <li class="sidebar-menu-item">
                        <a href="<?php echo $sidebarBase; ?>general-settings.php" class="sidebar-link <?php echo $isActive ? 'active' : ''; ?>">
                            <i class="fas fa-cog" style="margin-right: 0.5rem; color: <?php echo $isActive ? '#ffffff' : '#64748b'; ?>;"></i>
                            <span>Settings</span>
                        </a>
                    </li>
                </ul>
            </div>
            
            <!-- Emergency Communication System Section -->
            <div class="sidebar-section">
                <h3 class="sidebar-section-title">Emergency Communication</h3>
                <ul class="sidebar-menu">
                    <!-- Mass Notification -->
                    <?php $isActive = basename($_SERVER['PHP_SELF']) == 'mass-notification.php'; ?>
                    <li class="sidebar-menu-item">
                        <a href="<?php echo $sidebarBase; ?>mass-notification.php" class="sidebar-link <?php echo $isActive ? 'active' : ''; ?>">
                            <i class="fas fa-broadcast-tower" style="margin-right: 0.5rem; color: <?php echo $isActive ? '#ffffff' : '#dc2626'; ?>;"></i>
                            <span>Mass Notification</span>
                        </a>
                    </li>
                    
                    <!-- Alert Categorization -->
                    <?php $isActive = basename($_SERVER['PHP_SELF']) == 'alert-categorization.php'; ?>
                    <li class="sidebar-menu-item">
                        <a href="<?php echo $sidebarBase; ?>alert-categorization.php" class="sidebar-link <?php echo $isActive ? 'active' : ''; ?>">
                            <i class="fas fa-tags" style="margin-right: 0.5rem; color: <?php echo $isActive ? '#ffffff' : '#0d9488'; ?>;"></i>
                            <span>Alert Categorization</span>
                        </a>
                    </li>
                    
                    <!-- Two-Way Communication -->
                    <?php $isActive = basename($_SERVER['PHP_SELF']) == 'two-way-communication.php'; ?>
                    <li class="sidebar-menu-item">
                        <a href="<?php echo $sidebarBase; ?>two-way-communication.php" class="sidebar-link <?php echo $isActive ? 'active' : ''; ?>">
                            <i class="fas fa-comments" style="margin-right: 0.5rem; color: <?php echo $isActive ? '#ffffff' : '#4f46e5'; ?>;"></i>
                            <span>Two-Way Communication</span>
                        </a>
                    </li>

                    <!-- Automated Warnings -->
                    <?php 
                    $isAutoWarningsActive = (basename($_SERVER['PHP_SELF']) == 'automated-warnings.php' || basename($_SERVER['PHP_SELF']) == 'weather-monitoring.php' || basename($_SERVER['PHP_SELF']) == 'earthquake-monitoring.php');
                    ?>
                    <li class="sidebar-menu-item">
                        <a href="<?php echo $sidebarBase; ?>automated-warnings.php" class="sidebar-link sidebar-submenu-toggle <?php echo $isAutoWarningsActive ? 'active' : ''; ?>">
                            <i class="fas fa-plug" style="margin-right: 0.5rem; color: <?php echo $isAutoWarningsActive ? '#ffffff' : '#d97706'; ?>;"></i>
                            <span>Automated Warnings</span>
                            <i class="fas fa-chevron-down submenu-icon"></i>
                        </a>
                        <ul class="sidebar-submenu <?php echo $isAutoWarningsActive ? 'sidebar-submenu-open' : ''; ?>">
                            <li class="sidebar-menu-item">
                                <a href="<?php echo $sidebarBase; ?>automated-warnings.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'automated-warnings.php' ? 'active' : ''; ?>">
                                    <i class="fas fa-cog" style="margin-right: 0.5rem;"></i>
                                    <span>Settings</span>
                                </a>
                            </li>
                            <li class="sidebar-menu-item">
                                <a href="<?php echo $sidebarBase; ?>weather-monitoring.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'weather-monitoring.php' ? 'active' : ''; ?>">
                                    <i class="fas fa-cloud-sun" style="margin-right: 0.5rem;"></i>
                                    <span>Weather Monitoring</span>
                                </a>
                            </li>
                            <li class="sidebar-menu-item">
                                <a href="<?php echo $sidebarBase; ?>earthquake-monitoring.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'earthquake-monitoring.php' ? 'active' : ''; ?>">
                                    <i class="fas fa-mountain" style="margin-right: 0.5rem;"></i>
                                    <span>Earthquake Monitoring</span>
                                </a>
                            </li>
                        </ul>
                    </li>
                    
                    <!-- Multilingual Support -->
                    <?php $isMultiLangActive = (strpos($_SERVER['PHP_SELF'], 'multilingual-support') !== false); ?>
                    <li class="sidebar-menu-item">
                        <a href="javascript:void(0)" class="sidebar-link sidebar-submenu-toggle <?php echo $isMultiLangActive ? 'active' : ''; ?>">
                            <i class="fas fa-language" style="margin-right: 0.5rem; color: <?php echo $isMultiLangActive ? '#ffffff' : '#db2777'; ?>;"></i>
                            <span>Multilingual Support</span>
                            <i class="fas fa-chevron-down submenu-icon"></i>
                        </a>
                        <ul class="sidebar-submenu <?php echo $isMultiLangActive ? 'sidebar-submenu-open' : ''; ?>">
                            <li class="sidebar-menu-item">
                                <a href="<?php echo $sidebarBase; ?>multilingual-support/overview.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'overview.php' ? 'active' : ''; ?>">
                                    <i class="fas fa-info-circle" style="margin-right: 0.5rem;"></i>
                                    <span>Overview</span>
                                </a>
                            </li>
                            <li class="sidebar-menu-item">
                                <a href="<?php echo $sidebarBase; ?>multilingual-support/language-management.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'language-management.php' ? 'active' : ''; ?>">
                                    <i class="fas fa-list" style="margin-right: 0.5rem;"></i>
                                    <span>Language Management</span>
                                </a>
                            </li>
                        </ul>
                    </li>
                    
                    <!-- Citizen Subscriptions -->
                    <?php $isActive = basename($_SERVER['PHP_SELF']) == 'citizen-subscriptions.php'; ?>
                    <li class="sidebar-menu-item">
                        <a href="<?php echo $sidebarBase; ?>citizen-subscriptions.php" class="sidebar-link <?php echo $isActive ? 'active' : ''; ?>">
                            <i class="fas fa-users" style="margin-right: 0.5rem; color: <?php echo $isActive ? '#ffffff' : '#64748b'; ?>;"></i>
                            <span>Citizen Subscriptions</span>
                        </a>
                    </li>
                    
                    <!-- Audit Trail -->
                    <?php $isActive = basename($_SERVER['PHP_SELF']) == 'audit-trail.php'; ?>
                    <li class="sidebar-menu-item">
                        <a href="<?php echo $sidebarBase; ?>audit-trail.php" class="sidebar-link <?php echo $isActive ? 'active' : ''; ?>">
                            <i class="fas fa-history" style="margin-right: 0.5rem; color: <?php echo $isActive ? '#ffffff' : '#64748b'; ?>;"></i>
                            <span>Audit Trail</span>
                        </a>
                    </li>
                </ul>
            </div>
            
        </nav>
    </div>
</aside>

<!-- Sidebar Overlay for mobile -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Sidebar functionality
    const sidebar = document.getElementById('sidebar');
    const sidebarOverlay = document.getElementById('sidebarOverlay');
    
    // Toggle sidebar
    function toggleSidebar() {
        sidebar.classList.toggle('sidebar-open');
        sidebarOverlay.classList.toggle('sidebar-overlay-open');
        document.body.classList.toggle('sidebar-open');
    }
    
    // Close sidebar
    function closeSidebar() {
        sidebar.classList.remove('sidebar-open');
        sidebarOverlay.classList.remove('sidebar-overlay-open');
        document.body.classList.remove('sidebar-open');
    }

    // Expose functions globally so other scripts
    // can trigger the sidebar without duplicating logic.
    window.sidebarToggle = toggleSidebar;
    window.sidebarClose = closeSidebar;
    
    // Close sidebar when clicking overlay
    if (sidebarOverlay) {
        sidebarOverlay.addEventListener('click', closeSidebar);
    }
    
    // Close sidebar on Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && sidebar.classList.contains('sidebar-open')) {
            closeSidebar();
        }
    });
    
    // Handle submenu toggles
    const submenuToggles = document.querySelectorAll('.sidebar-submenu-toggle');
    submenuToggles.forEach(toggle => {
        toggle.addEventListener('click', function(e) {
            e.preventDefault();
            const submenu = this.nextElementSibling;
            const icon = this.querySelector('.submenu-icon');
            
            if (submenu) {
                const isOpen = submenu.classList.contains('sidebar-submenu-open');
                submenu.classList.toggle('sidebar-submenu-open');
                this.classList.toggle('active', !isOpen);
                
                // Toggle icon based on new state
                if (icon) {
                    if (submenu.classList.contains('sidebar-submenu-open')) {
                        // Now open - show up chevron
                        icon.classList.remove('fa-chevron-down');
                        icon.classList.add('fa-chevron-up');
                    } else {
                        // Now closed - show down chevron
                        icon.classList.remove('fa-chevron-up');
                        icon.classList.add('fa-chevron-down');
                    }
                }
            }
        });
    });
    
    // Auto-open submenu if it contains active item
    const activeLinks = document.querySelectorAll('.sidebar-submenu .sidebar-link.active');
    activeLinks.forEach(activeLink => {
        const submenu = activeLink.closest('.sidebar-submenu');
        const toggle = submenu ? submenu.previousElementSibling : null;
        
        if (submenu && toggle && toggle.classList.contains('sidebar-submenu-toggle')) {
            submenu.classList.add('sidebar-submenu-open');
            toggle.classList.add('active');
            
            const icon = toggle.querySelector('.submenu-icon');
            if (icon) {
                icon.classList.remove('fa-chevron-down');
                icon.classList.add('fa-chevron-up');
            }
        }
    });
    
    // Also check if the parent link should be active (for automated-warnings submenu)
    const automatedWarningsToggle = document.querySelector('a[href="automated-warnings.php"].sidebar-submenu-toggle');
    if (automatedWarningsToggle) {
        const submenu = automatedWarningsToggle.nextElementSibling;
        if (submenu && submenu.classList.contains('sidebar-submenu')) {
            const hasActiveChild = submenu.querySelector('.sidebar-link.active');
            if (hasActiveChild) {
                submenu.classList.add('sidebar-submenu-open');
                automatedWarningsToggle.classList.add('active');
                const icon = automatedWarningsToggle.querySelector('.submenu-icon');
                if (icon) {
                    icon.classList.remove('fa-chevron-down');
                    icon.classList.add('fa-chevron-up');
                }
            }
        }
    }
});
</script>