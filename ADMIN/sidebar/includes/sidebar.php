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
                        <a href="<?php echo $sidebarBase; ?>dashboard.php" class="sidebar-link sidebar-accent-dashboard <?php echo $isActive ? 'active' : ''; ?>">
                            <i class="fas fa-home sidebar-icon" aria-hidden="true"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    
                    <!-- Users -->
                    <?php $isActive = basename($_SERVER['PHP_SELF']) == 'users.php'; ?>
                    <li class="sidebar-menu-item">
                        <a href="<?php echo $sidebarBase; ?>users.php" class="sidebar-link sidebar-accent-users <?php echo $isActive ? 'active' : ''; ?>">
                            <i class="fas fa-users sidebar-icon" aria-hidden="true"></i>
                            <span>Users</span>
                        </a>
                    </li>
                    
                    <!-- Admin Approvals -->
                    <?php $isActive = basename($_SERVER['PHP_SELF']) == 'admin-approvals.php'; ?>
                    <li class="sidebar-menu-item">
                        <a href="<?php echo $sidebarBase; ?>admin-approvals.php" class="sidebar-link sidebar-accent-approvals <?php echo $isActive ? 'active' : ''; ?>">
                            <i class="fas fa-user-check sidebar-icon" aria-hidden="true"></i>
                            <span>Admin Approvals</span>
                        </a>
                    </li>

                    <!-- My Profile -->
                    <?php $isActive = basename($_SERVER['PHP_SELF']) == 'profile.php'; ?>
                    <li class="sidebar-menu-item">
                        <a href="<?php echo $sidebarBase; ?>profile.php" class="sidebar-link sidebar-accent-profile <?php echo $isActive ? 'active' : ''; ?>">
                            <i class="fas fa-user-circle sidebar-icon" aria-hidden="true"></i>
                            <span>My Profile</span>
                        </a>
                    </li>

                    <!-- Settings -->
                    <?php $isActive = basename($_SERVER['PHP_SELF']) == 'general-settings.php'; ?>
                    <li class="sidebar-menu-item">
                        <a href="<?php echo $sidebarBase; ?>general-settings.php" class="sidebar-link sidebar-accent-settings <?php echo $isActive ? 'active' : ''; ?>">
                            <i class="fas fa-cog sidebar-icon" aria-hidden="true"></i>
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
                        <a href="<?php echo $sidebarBase; ?>mass-notification.php" class="sidebar-link sidebar-accent-mass <?php echo $isActive ? 'active' : ''; ?>">
                            <i class="fas fa-broadcast-tower sidebar-icon" aria-hidden="true"></i>
                            <span>Mass Notification</span>
                        </a>
                    </li>
                    
                    <!-- Alert Categorization -->
                    <?php $isActive = basename($_SERVER['PHP_SELF']) == 'alert-categorization.php'; ?>
                    <li class="sidebar-menu-item">
                        <a href="<?php echo $sidebarBase; ?>alert-categorization.php" class="sidebar-link sidebar-accent-categorization <?php echo $isActive ? 'active' : ''; ?>">
                            <i class="fas fa-tags sidebar-icon" aria-hidden="true"></i>
                            <span>Alert Categorization</span>
                        </a>
                    </li>
                    
                    <!-- Two-Way Communication -->
                    <?php
                    $isTwoWayPage = basename($_SERVER['PHP_SELF']) == 'two-way-communication.php';
                    $currentDept = isset($_GET['dept']) ? $_GET['dept'] : '';
                    $isTwoWayActive = $isTwoWayPage;
                    ?>
                    <li class="sidebar-menu-item">
                        <a href="<?php echo $sidebarBase; ?>two-way-communication.php" class="sidebar-link sidebar-submenu-toggle sidebar-accent-2way <?php echo $isTwoWayActive ? 'active' : ''; ?>">
                            <i class="fas fa-comments sidebar-icon" aria-hidden="true"></i>
                            <span>Two-Way Communication</span>
                            <i class="fas fa-chevron-down submenu-icon" aria-hidden="true"></i>
                        </a>
                        <ul class="sidebar-submenu <?php echo $isTwoWayActive ? 'sidebar-submenu-open' : ''; ?>">
                            <li class="sidebar-menu-item">
                                <a href="<?php echo $sidebarBase; ?>two-way-communication.php" class="sidebar-link sidebar-accent-2way <?php echo ($isTwoWayPage && $currentDept === '') ? 'active' : ''; ?>">
                                    <i class="fas fa-layer-group sidebar-icon" aria-hidden="true"></i>
                                    <span>Centralized View</span>
                                </a>
                            </li>
                            <li class="sidebar-menu-item">
                                <a href="<?php echo $sidebarBase; ?>two-way-communication.php?dept=incident_nlp" class="sidebar-link sidebar-accent-2way <?php echo ($isTwoWayPage && $currentDept === 'incident_nlp') ? 'active' : ''; ?>">
                                    <i class="fas fa-microscope sidebar-icon" aria-hidden="true"></i>
                                    <span>Incident & NLP Investigation</span>
                                </a>
                            </li>
                            <li class="sidebar-menu-item">
                                <a href="<?php echo $sidebarBase; ?>two-way-communication.php?dept=traffic_transport" class="sidebar-link sidebar-accent-2way <?php echo ($isTwoWayPage && $currentDept === 'traffic_transport') ? 'active' : ''; ?>">
                                    <i class="fas fa-traffic-light sidebar-icon" aria-hidden="true"></i>
                                    <span>Traffic & Transport</span>
                                </a>
                            </li>
                            <li class="sidebar-menu-item">
                                <a href="<?php echo $sidebarBase; ?>two-way-communication.php?dept=emergency_response" class="sidebar-link sidebar-accent-2way <?php echo ($isTwoWayPage && $currentDept === 'emergency_response') ? 'active' : ''; ?>">
                                    <i class="fas fa-ambulance sidebar-icon" aria-hidden="true"></i>
                                    <span>Emergency Response</span>
                                </a>
                            </li>
                            <li class="sidebar-menu-item">
                                <a href="<?php echo $sidebarBase; ?>two-way-communication.php?dept=community_policing" class="sidebar-link sidebar-accent-2way <?php echo ($isTwoWayPage && $currentDept === 'community_policing') ? 'active' : ''; ?>">
                                    <i class="fas fa-shield-alt sidebar-icon" aria-hidden="true"></i>
                                    <span>Community Policing</span>
                                </a>
                            </li>
                            <li class="sidebar-menu-item">
                                <a href="<?php echo $sidebarBase; ?>two-way-communication.php?dept=crime_analytics" class="sidebar-link sidebar-accent-2way <?php echo ($isTwoWayPage && $currentDept === 'crime_analytics') ? 'active' : ''; ?>">
                                    <i class="fas fa-chart-line sidebar-icon" aria-hidden="true"></i>
                                    <span>Crime Analytics</span>
                                </a>
                            </li>
                            <li class="sidebar-menu-item">
                                <a href="<?php echo $sidebarBase; ?>two-way-communication.php?dept=public_safety_campaign" class="sidebar-link sidebar-accent-2way <?php echo ($isTwoWayPage && $currentDept === 'public_safety_campaign') ? 'active' : ''; ?>">
                                    <i class="fas fa-bullhorn sidebar-icon" aria-hidden="true"></i>
                                    <span>Public Safety Campaign</span>
                                </a>
                            </li>
                            <li class="sidebar-menu-item">
                                <a href="<?php echo $sidebarBase; ?>two-way-communication.php?dept=health_inspection" class="sidebar-link sidebar-accent-2way <?php echo ($isTwoWayPage && $currentDept === 'health_inspection') ? 'active' : ''; ?>">
                                    <i class="fas fa-notes-medical sidebar-icon" aria-hidden="true"></i>
                                    <span>Health & Safety Inspection</span>
                                </a>
                            </li>
                            <li class="sidebar-menu-item">
                                <a href="<?php echo $sidebarBase; ?>two-way-communication.php?dept=disaster_preparedness" class="sidebar-link sidebar-accent-2way <?php echo ($isTwoWayPage && $currentDept === 'disaster_preparedness') ? 'active' : ''; ?>">
                                    <i class="fas fa-hard-hat sidebar-icon" aria-hidden="true"></i>
                                    <span>Disaster Preparedness</span>
                                </a>
                            </li>
                            <li class="sidebar-menu-item">
                                <a href="<?php echo $sidebarBase; ?>two-way-communication.php?dept=emergency_comm" class="sidebar-link sidebar-accent-2way <?php echo ($isTwoWayPage && $currentDept === 'emergency_comm') ? 'active' : ''; ?>">
                                    <i class="fas fa-broadcast-tower sidebar-icon" aria-hidden="true"></i>
                                    <span>Emergency Communication</span>
                                </a>
                            </li>
                        </ul>
                    </li>

                    <!-- Automated Warnings -->
                    <?php 
                    $isAutoWarningsActive = (basename($_SERVER['PHP_SELF']) == 'automated-warnings.php' || basename($_SERVER['PHP_SELF']) == 'weather-monitoring.php' || basename($_SERVER['PHP_SELF']) == 'earthquake-monitoring.php');
                    ?>
                    <li class="sidebar-menu-item">
                        <a href="<?php echo $sidebarBase; ?>automated-warnings.php" class="sidebar-link sidebar-submenu-toggle sidebar-accent-auto <?php echo $isAutoWarningsActive ? 'active' : ''; ?>">
                            <i class="fas fa-plug sidebar-icon" aria-hidden="true"></i>
                            <span>Automated Warnings</span>
                            <i class="fas fa-chevron-down submenu-icon" aria-hidden="true"></i>
                        </a>
                        <ul class="sidebar-submenu <?php echo $isAutoWarningsActive ? 'sidebar-submenu-open' : ''; ?>">
                            <li class="sidebar-menu-item">
                                <a href="<?php echo $sidebarBase; ?>automated-warnings.php" class="sidebar-link sidebar-accent-auto <?php echo basename($_SERVER['PHP_SELF']) == 'automated-warnings.php' ? 'active' : ''; ?>">
                                    <i class="fas fa-cog sidebar-icon" aria-hidden="true"></i>
                                    <span>Settings</span>
                                </a>
                            </li>
                            <li class="sidebar-menu-item">
                                <a href="<?php echo $sidebarBase; ?>weather-monitoring.php" class="sidebar-link sidebar-accent-weather <?php echo basename($_SERVER['PHP_SELF']) == 'weather-monitoring.php' ? 'active' : ''; ?>">
                                    <i class="fas fa-cloud-sun sidebar-icon" aria-hidden="true"></i>
                                    <span>Weather Monitoring</span>
                                </a>
                            </li>
                            <li class="sidebar-menu-item">
                                <a href="<?php echo $sidebarBase; ?>earthquake-monitoring.php" class="sidebar-link sidebar-accent-earthquake <?php echo basename($_SERVER['PHP_SELF']) == 'earthquake-monitoring.php' ? 'active' : ''; ?>">
                                    <i class="fas fa-mountain sidebar-icon" aria-hidden="true"></i>
                                    <span>Earthquake Monitoring</span>
                                </a>
                            </li>
                        </ul>
                    </li>
                    
                    <!-- Multilingual Support -->
                    <?php $isMultiLangActive = (strpos($_SERVER['PHP_SELF'], 'multilingual-support') !== false); ?>
                    <li class="sidebar-menu-item">
                        <a href="javascript:void(0)" class="sidebar-link sidebar-submenu-toggle sidebar-accent-multilang <?php echo $isMultiLangActive ? 'active' : ''; ?>">
                            <i class="fas fa-language sidebar-icon" aria-hidden="true"></i>
                            <span>Multilingual Support</span>
                            <i class="fas fa-chevron-down submenu-icon" aria-hidden="true"></i>
                        </a>
                        <ul class="sidebar-submenu <?php echo $isMultiLangActive ? 'sidebar-submenu-open' : ''; ?>">
                            <li class="sidebar-menu-item">
                                <a href="<?php echo $sidebarBase; ?>multilingual-support/overview.php" class="sidebar-link sidebar-accent-overview <?php echo basename($_SERVER['PHP_SELF']) == 'overview.php' ? 'active' : ''; ?>">
                                    <i class="fas fa-info-circle sidebar-icon" aria-hidden="true"></i>
                                    <span>Overview</span>
                                </a>
                            </li>
                            <li class="sidebar-menu-item">
                                <a href="<?php echo $sidebarBase; ?>multilingual-support/language-management.php" class="sidebar-link sidebar-accent-language <?php echo basename($_SERVER['PHP_SELF']) == 'language-management.php' ? 'active' : ''; ?>">
                                    <i class="fas fa-list sidebar-icon" aria-hidden="true"></i>
                                    <span>Language Management</span>
                                </a>
                            </li>
                        </ul>
                    </li>
                    
                    <!-- Citizen Subscriptions -->
                    <?php $isActive = basename($_SERVER['PHP_SELF']) == 'citizen-subscriptions.php'; ?>
                    <li class="sidebar-menu-item">
                        <a href="<?php echo $sidebarBase; ?>citizen-subscriptions.php" class="sidebar-link sidebar-accent-citizen <?php echo $isActive ? 'active' : ''; ?>">
                            <i class="fas fa-users sidebar-icon" aria-hidden="true"></i>
                            <span>Citizen Subscriptions</span>
                        </a>
                    </li>
                    
                    <!-- Audit Trail -->
                    <?php $isActive = basename($_SERVER['PHP_SELF']) == 'audit-trail.php'; ?>
                    <li class="sidebar-menu-item">
                        <a href="<?php echo $sidebarBase; ?>audit-trail.php" class="sidebar-link sidebar-accent-audit <?php echo $isActive ? 'active' : ''; ?>">
                            <i class="fas fa-history sidebar-icon" aria-hidden="true"></i>
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

<script src="<?php echo $sidebarBase; ?>../shared/js/draft-persist.js?v=<?php echo filemtime(__DIR__ . '/../../shared/js/draft-persist.js'); ?>"></script>
