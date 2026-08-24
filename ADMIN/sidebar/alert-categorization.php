<?php
/**
 * Alert Categorization Page
 * Manage alert categories: Weather, Earthquake, Bomb Threat, etc.
 */

// Start session and check authentication
session_start();

// Check if user is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../login.php');
    exit();
}

// Role-Based Access Control (RBAC)
$adminRole = $_SESSION['admin_role'] ?? 'staff'; // Default to staff if role is not set
$canEdit = in_array($adminRole, ['super_admin', 'admin']);
$canDelete = ($adminRole === 'super_admin');

$pageTitle = 'Alert Categorization';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <link rel="icon" type="image/x-icon" href="images/favicon.ico">
    <link rel="stylesheet" href="css/global.css?v=<?php echo filemtime(__DIR__ . '/css/global.css'); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="css/sidebar.css?v=<?php echo filemtime(__DIR__ . '/css/sidebar.css'); ?>">
    <link rel="stylesheet" href="css/admin-header.css">
    <link rel="stylesheet" href="css/buttons.css">
    <link rel="stylesheet" href="css/forms.css">
    <link rel="stylesheet" href="css/datatables.css">
    <link rel="stylesheet" href="css/hero.css">
    <link rel="stylesheet" href="css/sidebar-footer.css">
    <link rel="stylesheet" href="css/modules.css">
    <link rel="stylesheet" href="css/module-alert-categorization.css?v=<?php echo filemtime(__DIR__ . '/css/module-alert-categorization.css'); ?>">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
</head>
<body>
    <!-- Include Sidebar Component -->
    <?php include 'includes/sidebar.php'; ?>

    <!-- Include Admin Header Component -->
    <?php include 'includes/admin-header.php'; ?>
    
    <!-- ===================================
       MAIN CONTENT - Alert Categorization
       =================================== -->
    <div class="main-content">
        <div class="main-container">
            <div class="title">
                <nav class="breadcrumb" aria-label="Breadcrumb">
                    <ol class="breadcrumb-list">
                        <li class="breadcrumb-item">
                            <a href="dashboard.php" class="breadcrumb-link">
                                <span>Dashboard</span>
                            </a>
                        </li>
                        <li class="breadcrumb-item active" aria-current="page">
                            <span>Alert Categorization</span>
                        </li>
                    </ol>
                </nav>
                <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 1rem;">
                    <div>
                        <h1><i class="fas fa-tags" style="color: var(--primary-color-1); margin-right: 0.5rem;"></i> Alert Categorization</h1>
                        <p>Organize and manage alert categories for effective emergency communication.</p>
                    </div>
                    <div>
                        <span class="badge" style="background: rgba(52, 152, 219, 0.1); color: #3498db; padding: 0.5rem 1rem; border-radius: 20px; font-weight: 600; border: 1px solid rgba(52, 152, 219, 0.2);">
                            <i class="fas fa-user-shield"></i> Role: <?php echo ucwords(str_replace('_', ' ', $adminRole)); ?>
                        </span>
                    </div>
                </div>
            </div>
            
            <div class="sub-container">
                <div class="page-content">
                    <div class="ac-mini-analytics" id="acSummaryCards">
                        <div class="ac-stat ac-stat--total">
                            <div class="ac-stat-top">
                                <div class="ac-stat-label">Total Categories</div>
                                <div class="ac-stat-icon"><i class="fas fa-tags"></i></div>
                            </div>
                            <div class="ac-stat-value" id="acTotalCats">0</div>
                            <div class="ac-stat-sub" id="acTotalCatsSub">Loaded from category list</div>
                        </div>
                        <div class="ac-stat ac-stat--done">
                            <div class="ac-stat-top">
                                <div class="ac-stat-label">Active</div>
                                <div class="ac-stat-icon"><i class="fas fa-check-circle"></i></div>
                            </div>
                            <div class="ac-stat-value" id="acActiveCats">0</div>
                            <div class="ac-stat-sub" id="acActiveCatsSub">Currently enabled</div>
                        </div>
                        <div class="ac-stat ac-stat--progress">
                            <div class="ac-stat-top">
                                <div class="ac-stat-label">Inactive</div>
                                <div class="ac-stat-icon"><i class="fas fa-pause-circle"></i></div>
                            </div>
                            <div class="ac-stat-value" id="acInactiveCats">0</div>
                            <div class="ac-stat-sub" id="acInactiveCatsSub">Not shown to users</div>
                        </div>
                        <div class="ac-stat ac-stat--rate">
                            <div class="ac-stat-top">
                                <div class="ac-stat-label">High Load</div>
                                <div class="ac-stat-icon"><i class="fas fa-exclamation-circle"></i></div>
                            </div>
                            <div class="ac-stat-value" id="acHighLoadCats">0</div>
                            <div class="ac-stat-sub" id="acHighLoadCatsSub">Categories over 20 alerts</div>
                        </div>
                    </div>
                    <div class="ac-process" aria-label="How alert categorization works">
                        <div class="ac-process-title">How Alert Categorization Works</div>
                        <div class="ac-process-track">
                            <div class="ac-process-step">
                                <div class="ac-process-icon" aria-hidden="true"><i class="fas fa-tags"></i></div>
                                <h4>Define Categories</h4>
                                <p>Create clear labels, icons, and colors for each alert type.</p>
                            </div>
                            <div class="ac-process-arrow" aria-hidden="true"><i class="fas fa-arrow-right"></i></div>
                            <div class="ac-process-step">
                                <div class="ac-process-icon" aria-hidden="true"><i class="fas fa-shield-alt"></i></div>
                                <h4>System Uses Tags</h4>
                                <p>Alerts are organized by category for routing and analytics.</p>
                            </div>
                            <div class="ac-process-arrow" aria-hidden="true"><i class="fas fa-arrow-right"></i></div>
                            <div class="ac-process-step">
                                <div class="ac-process-icon" aria-hidden="true"><i class="fas fa-bell"></i></div>
                                <h4>Citizens Receive</h4>
                                <p>Users see consistent labels and colors across channels.</p>
                            </div>
                        </div>
                    </div>
                    <div class="ac-cta" aria-label="Add new category">
                        <div>
                            <div class="ac-cta-title">Create a New Alert Category</div>
                            <div class="ac-cta-sub">Use the guided modal to keep labels consistent and user-friendly.</div>
                        </div>
                        <button type="button" class="btn btn-primary" id="openCategoryModalBtn">
                            <i class="fas fa-plus-circle" style="margin-right: 0.5rem;"></i> Add New Category
                        </button>
                    </div>

                    <!-- Categories List -->
                    <div class="module-card" style="margin-top: 1.5rem;">
                        <div class="module-card-header">
                            <h2><i class="fas fa-list"></i> Managed Categories</h2>
                        </div>
                        <div class="module-card-content">
                            <div style="overflow-x: auto;">
                                <table class="data-table" id="categoriesTable">
                                    <thead>
                                        <tr>
                                            <th style="width: 40px;"></th>
                                            <th>Name</th>
                                            <th>Visual Identity</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <!-- Data loaded via API -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add/Edit Category Modal -->
    <div class="ac-modal-backdrop" id="acCategoryModalBackdrop" aria-hidden="true">
        <div class="ac-modal" role="dialog" aria-modal="true" aria-labelledby="acCategoryModalTitle">
            <div class="ac-modal-header">
                <div class="ac-modal-header-info">
                    <div class="ac-modal-header-icon" id="acModalHeaderIcon">
                        <i class="fas fa-layer-group"></i>
                    </div>
                    <div>
                        <h3 class="ac-modal-title" id="acCategoryModalTitle">Add New Category</h3>
                        <div class="ac-modal-subtitle" id="acCategoryModalSubtitle">Define a clear category name, icon, and color for citizen emergency alerts.</div>
                    </div>
                </div>
                <button class="ac-modal-close" type="button" id="acCloseCategoryModalBtn" aria-label="Close">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <div class="ac-modal-body">
                <form id="categoryFormModal">
                    <input type="hidden" id="categoryIdModal" name="id">
                    
                    <div class="ac-modal-grid">
                        <!-- Left Column: Form Controls -->
                        <div class="ac-modal-left <?php echo !$canEdit ? 'access-denied' : ''; ?>">
                            <!-- Quick Starter Templates -->
                            <div class="ac-quick-templates">
                                <div class="ac-section-label">
                                    <i class="fas fa-bolt" style="color: #f59e0b;"></i>
                                    <span>Quick Starter Presets</span>
                                    <small>(Click to auto-fill details)</small>
                                </div>
                                <div class="ac-template-chips" id="acTemplateChips">
                                    <!-- Populated via JS -->
                                </div>
                            </div>

                            <!-- Name & Status Row -->
                            <div class="form-row">
                                <div class="form-group" style="margin-bottom:0;">
                                    <label for="categoryNameModal">
                                        <i class="fas fa-tag" style="color: var(--primary-color-1); margin-right: 0.35rem;"></i> Category Name <span style="color: #dc3545;">*</span>
                                    </label>
                                    <input type="text" id="categoryNameModal" name="name" placeholder="e.g. Flash Flood, Fire Alert" required <?php echo !$canEdit ? 'disabled' : ''; ?>>
                                </div>
                                <div class="form-group" style="margin-bottom:0;">
                                    <label for="categoryStatusModal">
                                        <i class="fas fa-toggle-on" style="color: var(--primary-color-1); margin-right: 0.35rem;"></i> Status
                                    </label>
                                    <select id="categoryStatusModal" name="status" <?php echo !$canEdit ? 'disabled' : ''; ?>>
                                        <option value="active">Active (Enabled in System)</option>
                                        <option value="inactive">Inactive (Hidden from Feeds)</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Curated Color Swatches & Custom Picker -->
                            <div class="form-group" style="margin-top: 1.1rem; margin-bottom: 1.1rem;">
                                <label for="categoryColorModal">
                                    <i class="fas fa-palette" style="color: var(--primary-color-1); margin-right: 0.35rem;"></i> Identity Color & Alert Theme <span style="color: #dc3545;">*</span>
                                </label>
                                <div class="ac-color-picker-wrapper">
                                    <div class="ac-color-swatches" id="acColorSwatches">
                                        <!-- Swatches injected via JS -->
                                    </div>
                                    <div class="ac-custom-color-box">
                                        <input type="color" id="categoryColorModal" name="color" value="#3a7675" <?php echo !$canEdit ? 'disabled' : ''; ?>>
                                        <span class="ac-color-hex" id="acColorHexDisplay">#3a7675</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Searchable & Categorized Icon Picker -->
                            <div class="form-group" style="margin-bottom: 1.1rem;">
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                                    <label style="margin-bottom: 0;">
                                        <i class="fas fa-icons" style="color: var(--primary-color-1); margin-right: 0.35rem;"></i> Category Icon <span style="color: #dc3545;">*</span>
                                    </label>
                                    <span class="ac-selected-icon-badge" id="acSelectedIconBadge">
                                        <i class="fas fa-exclamation-triangle" id="acSelectedIconPreview"></i>
                                        <span id="acSelectedIconName">fa-exclamation-triangle</span>
                                    </span>
                                </div>
                                
                                <input type="hidden" id="categoryIconModal" name="icon" value="fa-exclamation-triangle">
                                
                                <div class="ac-icon-picker-box">
                                    <div class="ac-icon-toolbar">
                                        <div class="ac-icon-search-wrap">
                                            <i class="fas fa-search"></i>
                                            <input type="text" id="acIconSearchInput" placeholder="Search icon (e.g. fire, water, siren, medical, shield)...">
                                            <button type="button" id="acIconSearchClear" style="display:none;" title="Clear search"><i class="fas fa-times"></i></button>
                                        </div>
                                        <div class="ac-icon-tabs" id="acIconCategoryTabs">
                                            <button type="button" class="ac-icon-tab active" data-cat="all">All</button>
                                            <button type="button" class="ac-icon-tab" data-cat="weather">Weather & Nature</button>
                                            <button type="button" class="ac-icon-tab" data-cat="hazard">Disaster & Fire</button>
                                            <button type="button" class="ac-icon-tab" data-cat="health">Health & Medical</button>
                                            <button type="button" class="ac-icon-tab" data-cat="security">Security & Rescue</button>
                                        </div>
                                    </div>
                                    <div class="icon-grid" id="iconGridModal">
                                        <!-- Icons populated by JS -->
                                    </div>
                                </div>
                            </div>

                            <!-- Description Field -->
                            <div class="form-group" style="margin-bottom: 0;">
                                <label for="categoryDescriptionModal">
                                    <i class="fas fa-align-left" style="color: var(--primary-color-1); margin-right: 0.35rem;"></i> Description & Scope
                                </label>
                                <textarea id="categoryDescriptionModal" name="description" rows="2" placeholder="Briefly describe what situations and emergency scenarios this category covers..." <?php echo !$canEdit ? 'disabled' : ''; ?>></textarea>
                            </div>
                        </div>

                        <!-- Right Column: Real-World Citizen Preview -->
                        <div class="ac-modal-right">
                            <div class="ac-preview-panel">
                                <div class="ac-preview-header">
                                    <h4><i class="fas fa-mobile-alt"></i> Citizen App Live Preview</h4>
                                    <span class="ac-preview-tag">Real-time</span>
                                </div>
                                
                                <div class="ac-preview-body">
                                    <p class="ac-preview-hint">Citizens will see this category badge styling across mobile alert feeds, SMS headers, and broadcast notifications:</p>
                                    
                                    <!-- Citizen Alert Feed Card Preview -->
                                    <div class="ac-citizen-feed-card" id="citizenFeedPreviewCard">
                                        <div class="ac-citizen-card-header">
                                            <div class="ac-citizen-category-badge" id="citizenPreviewBadge">
                                                <i class="fas fa-exclamation-triangle" id="citizenPreviewIcon"></i>
                                                <span id="citizenPreviewCategoryName">Category Name</span>
                                            </div>
                                            <span class="ac-citizen-time-badge"><i class="fas fa-clock"></i> Just Now</span>
                                        </div>
                                        <div class="ac-citizen-card-body">
                                            <div class="ac-citizen-alert-title" id="citizenPreviewTitle">Urgent Emergency Notice</div>
                                            <div class="ac-citizen-alert-desc" id="citizenPreviewDesc">Briefly describe what this category covers to guide dispatchers and alert recipients.</div>
                                        </div>
                                        <div class="ac-citizen-card-footer">
                                            <span class="ac-citizen-channel"><i class="fas fa-bullhorn"></i> Alertara QC Broadcast</span>
                                            <span class="ac-citizen-status-indicator" id="citizenPreviewStatusDot"><i class="fas fa-circle"></i> Active</span>
                                        </div>
                                    </div>

                                    <!-- Compact Header / Table Pill Preview -->
                                    <div class="ac-preview-sub">
                                        <div class="ac-sub-label">Compact Tag / Badge View:</div>
                                        <div class="category-preview-card" id="livePreviewModal">
                                            <i class="fas fa-exclamation-triangle" id="previewIconModal"></i>
                                            <span id="previewNameModal">Category Name</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="ac-modal-note" style="margin: 0 1.15rem 1.15rem 1.15rem;">
                                    <i class="fas fa-lightbulb" style="color: #f59e0b; margin-right: 0.35rem;"></i>
                                    <strong>Best Practice:</strong> Keep category names concise and choose high-contrast colors so citizens recognize hazard urgency instantly.
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            <div class="ac-modal-footer">
                <div class="ac-modal-hint">
                    <i class="fas fa-shield-alt" style="color: var(--primary-color-1); margin-right: 0.35rem;"></i>
                    Changes are audited and logged for accountability.
                </div>
                <div class="ac-modal-actions">
                    <button type="button" class="btn btn-secondary" id="acCloseCategoryModalBtnFooter">
                        <i class="fas fa-times" style="margin-right: 0.35rem;"></i> Cancel
                    </button>
                    <?php if ($canEdit): ?>
                    <button type="button" class="btn btn-primary" id="acSaveFromFooterBtn">
                        <i class="fas fa-save" style="margin-right: 0.4rem;"></i> <span id="acSaveBtnText">Save Category</span>
                    </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Preset Alert Categories Templates
        const alertTemplates = [
            {
                name: 'Flood Alert',
                icon: 'fa-water',
                color: '#0284c7',
                description: 'Rising water levels, river cresting, low-lying inundation, and flash flood warnings.',
                chipLabel: 'Flood',
                chipIcon: 'fa-water'
            },
            {
                name: 'Typhoon / Storm',
                icon: 'fa-cloud-showers-heavy',
                color: '#0369a1',
                description: 'Severe weather systems, tropical cyclones, gale warnings, and torrential monsoon rains.',
                chipLabel: 'Typhoon',
                chipIcon: 'fa-cloud-showers-heavy'
            },
            {
                name: 'Earthquake Advisory',
                icon: 'fa-mountain',
                color: '#b91c1c',
                description: 'Ground shaking, tectonic tremors, faultline movement, and seismic aftershock advisories.',
                chipLabel: 'Earthquake',
                chipIcon: 'fa-mountain'
            },
            {
                name: 'Fire Emergency',
                icon: 'fa-fire',
                color: '#ea580c',
                description: 'Active residential/commercial structure fires, grassfire alerts, and smoke hazard notices.',
                chipLabel: 'Fire',
                chipIcon: 'fa-fire'
            },
            {
                name: 'Medical / Health',
                icon: 'fa-heartbeat',
                color: '#16a34a',
                description: 'Public health advisories, disease outbreaks, emergency medical directives, and triage notices.',
                chipLabel: 'Medical / Health',
                chipIcon: 'fa-heartbeat'
            },
            {
                name: 'Evacuation Notice',
                icon: 'fa-running',
                color: '#dc2626',
                description: 'Mandatory and preemptive evacuation directives, pickup points, and designated shelter locations.',
                chipLabel: 'Evacuation',
                chipIcon: 'fa-running'
            },
            {
                name: 'Public Safety',
                icon: 'fa-shield-alt',
                color: '#6366f1',
                description: 'Civil safety alerts, crowd management, road blockades, and community security notices.',
                chipLabel: 'Public Safety',
                chipIcon: 'fa-shield-alt'
            },
            {
                name: 'General Advisory',
                icon: 'fa-exclamation-triangle',
                color: '#3a7675',
                description: 'General public notices, municipal service advisories, and community announcements.',
                chipLabel: 'General Advisory',
                chipIcon: 'fa-bell'
            }
        ];

        // Curated Semantic Color Swatches
        const colorSwatches = [
            { color: '#dc2626', label: 'Critical Danger (Red)' },
            { color: '#ea580c', label: 'High Risk (Orange)' },
            { color: '#d97706', label: 'Warning (Amber)' },
            { color: '#0284c7', label: 'Weather / Flood (Blue)' },
            { color: '#16a34a', label: 'Health / Safe (Green)' },
            { color: '#9333ea', label: 'Special Threat (Purple)' },
            { color: '#4f46e5', label: 'Security (Indigo)' },
            { color: '#3a7675', label: 'Alertara Standard (Teal)' },
            { color: '#475569', label: 'General / Info (Slate)' }
        ];

        // Categorized & Searchable Icon Database
        const iconDatabase = [
            // Weather & Nature
            { icon: 'fa-water', name: 'Flood / Water Level', category: 'weather', tags: 'flood water river rain overflow tsunami sea' },
            { icon: 'fa-cloud-showers-heavy', name: 'Heavy Rain / Monsoon', category: 'weather', tags: 'rain storm typhoon thunderstorm monsoon downpour deluge' },
            { icon: 'fa-wind', name: 'Gale / Strong Wind', category: 'weather', tags: 'wind typhoon storm gust hurricane cyclone breeze' },
            { icon: 'fa-bolt', name: 'Lightning / Thunder', category: 'weather', tags: 'lightning storm electric thunder bolt power' },
            { icon: 'fa-mountain', name: 'Earthquake / Terrain', category: 'weather', tags: 'earthquake quake landslide tremor rockfall mountain seismic' },
            { icon: 'fa-sun', name: 'Heatwave / Drought', category: 'weather', tags: 'sun heat heatwave drought summer temperature dry' },
            { icon: 'fa-smog', name: 'Smog / Ashfall', category: 'weather', tags: 'smoke smog pollution ash air quality haze soot' },
            { icon: 'fa-cloud-rain', name: 'Rain Shower', category: 'weather', tags: 'rain weather precipitation wet drizzle overcast' },
            
            // Hazards & Disasters
            { icon: 'fa-fire', name: 'Fire / Blaze', category: 'hazard', tags: 'fire flame blaze burn wildfire smoke explosion bfp' },
            { icon: 'fa-fire-extinguisher', name: 'Fire Response', category: 'hazard', tags: 'extinguisher fire marshal bfp response suppression' },
            { icon: 'fa-exclamation-triangle', name: 'Hazard Warning', category: 'hazard', tags: 'warning danger caution emergency alert hazard risk' },
            { icon: 'fa-bomb', name: 'Bomb Threat', category: 'hazard', tags: 'bomb threat explosive blast suspicious package explosive eod' },
            { icon: 'fa-biohazard', name: 'Biohazard / Chemical', category: 'hazard', tags: 'biohazard virus contamination toxic chemical epidemic hazmat' },
            { icon: 'fa-radiation', name: 'Radiation Hazard', category: 'hazard', tags: 'radiation nuclear hazmat leak chemical toxic rays' },
            { icon: 'fa-car-crash', name: 'Vehicular Accident', category: 'hazard', tags: 'car crash accident collision traffic vehicular road vehicular' },
            { icon: 'fa-house-damage', name: 'Structural Damage', category: 'hazard', tags: 'building collapse structural damage house earthquake debris' },

            // Health & Medical
            { icon: 'fa-heartbeat', name: 'Medical Emergency', category: 'health', tags: 'medical health emergency pulse heart doctor aid triage hospital' },
            { icon: 'fa-first-aid', name: 'First Aid Kit', category: 'health', tags: 'medical first aid rescue ambulance clinic medic supply' },
            { icon: 'fa-ambulance', name: 'Ambulance / EMS', category: 'health', tags: 'ambulance hospital transport patient paramedic ems 911' },
            { icon: 'fa-hospital', name: 'Hospital / Clinic', category: 'health', tags: 'hospital clinic health center evacuation medical facility' },
            { icon: 'fa-user-md', name: 'Medical Personnel', category: 'health', tags: 'doctor physician nurse medic health worker triage' },
            { icon: 'fa-shield-virus', name: 'Disease Outbreak', category: 'health', tags: 'virus outbreak quarantine covid flu epidemic health contagion mask' },

            // Security & Public Safety
            { icon: 'fa-running', name: 'Evacuation Exit', category: 'security', tags: 'evacuation exit run escape relocate shelter leave route' },
            { icon: 'fa-shield-alt', name: 'Security Shield', category: 'security', tags: 'shield security police patrol protection law defense' },
            { icon: 'fa-user-shield', name: 'Law Enforcement', category: 'security', tags: 'police cop officer security guard marshal pnp tanod' },
            { icon: 'fa-bullhorn', name: 'Megaphone Broadcast', category: 'security', tags: 'bullhorn broadcast announcement siren alarm loud notice advisory' },
            { icon: 'fa-broadcast-tower', name: 'Broadcast Tower', category: 'security', tags: 'tower antenna signal radio station communication telecom' },
            { icon: 'fa-bell', name: 'Alert Bell', category: 'security', tags: 'bell alert notice chime notification ring reminder' },
            { icon: 'fa-traffic-light', name: 'Traffic Warning', category: 'security', tags: 'traffic road barrier light signal stop go closure' },
            { icon: 'fa-home', name: 'Evacuation Shelter', category: 'security', tags: 'shelter evacuation center gym building relief sanctuary' }
        ];

        const canEdit = <?php echo $canEdit ? 'true' : 'false'; ?>;
        const canDelete = <?php echo $canDelete ? 'true' : 'false'; ?>;
        const adminRole = '<?php echo $adminRole; ?>';
        let analyticsCache = {};
        let activeCharts = {};
        let currentIconFilter = 'all';
        let currentIconSearch = '';

        // Initialize Quick Starter Template Chips
        function initTemplateChips() {
            const container = document.getElementById('acTemplateChips');
            if (!container) return;
            container.innerHTML = '';
            
            alertTemplates.forEach(tpl => {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'ac-template-chip';
                btn.style.setProperty('--chip-color', tpl.color);
                btn.title = `Click to load ${tpl.name} template`;
                btn.innerHTML = `<i class="fas ${tpl.chipIcon}"></i> <span>${tpl.chipLabel}</span>`;
                
                if (canEdit) {
                    btn.addEventListener('click', () => {
                        applyTemplate(tpl);
                    });
                }
                container.appendChild(btn);
            });
        }

        function applyTemplate(tpl) {
            const nameInput = document.getElementById('categoryNameModal');
            const descInput = document.getElementById('categoryDescriptionModal');
            const colorInput = document.getElementById('categoryColorModal');
            const iconInput = document.getElementById('categoryIconModal');
            const statusInput = document.getElementById('categoryStatusModal');
            
            if (nameInput) nameInput.value = tpl.name;
            if (descInput) descInput.value = tpl.description;
            if (colorInput) {
                colorInput.value = tpl.color;
                selectColorSwatch(tpl.color);
            }
            if (iconInput) {
                iconInput.value = tpl.icon;
                selectIcon(tpl.icon);
            }
            if (statusInput) statusInput.value = 'active';
            
            updateLivePreview();
        }

        // Initialize Color Swatches
        function initColorSwatches() {
            const container = document.getElementById('acColorSwatches');
            const colorInput = document.getElementById('categoryColorModal');
            const hexDisplay = document.getElementById('acColorHexDisplay');
            if (!container || !colorInput) return;
            
            container.innerHTML = '';
            colorSwatches.forEach(swatch => {
                const div = document.createElement('div');
                div.className = `ac-color-swatch ${swatch.color.toLowerCase() === colorInput.value.toLowerCase() ? 'active' : ''}`;
                div.style.backgroundColor = swatch.color;
                div.title = swatch.label;
                div.setAttribute('data-color', swatch.color);
                
                if (canEdit) {
                    div.addEventListener('click', () => {
                        colorInput.value = swatch.color;
                        if (hexDisplay) hexDisplay.textContent = swatch.color.toUpperCase();
                        selectColorSwatch(swatch.color);
                        updateLivePreview();
                    });
                }
                container.appendChild(div);
            });

            colorInput.addEventListener('input', () => {
                if (hexDisplay) hexDisplay.textContent = colorInput.value.toUpperCase();
                selectColorSwatch(colorInput.value);
                updateLivePreview();
            });
        }

        function selectColorSwatch(colorVal) {
            const container = document.getElementById('acColorSwatches');
            const hexDisplay = document.getElementById('acColorHexDisplay');
            if (hexDisplay) hexDisplay.textContent = (colorVal || '#3a7675').toUpperCase();
            if (!container) return;
            
            const swatches = container.querySelectorAll('.ac-color-swatch');
            swatches.forEach(sw => {
                const match = sw.getAttribute('data-color').toLowerCase() === (colorVal || '').toLowerCase();
                sw.classList.toggle('active', match);
            });
        }

        // Initialize Categorized & Searchable Icon Grid
        function initIconGrid() {
            renderIconGrid();
            initIconFilterEvents();
        }

        function renderIconGrid() {
            const grid = document.getElementById('iconGridModal');
            const iconInput = document.getElementById('categoryIconModal');
            if (!grid || !iconInput) return;
            
            grid.innerHTML = '';
            const currentSelected = iconInput.value || 'fa-exclamation-triangle';
            
            const filtered = iconDatabase.filter(item => {
                const matchesCat = (currentIconFilter === 'all') || (item.category === currentIconFilter);
                const query = currentIconSearch.trim().toLowerCase();
                const matchesQuery = !query || 
                    item.name.toLowerCase().includes(query) || 
                    item.icon.toLowerCase().includes(query) || 
                    item.tags.toLowerCase().includes(query);
                return matchesCat && matchesQuery;
            });

            if (filtered.length === 0) {
                grid.innerHTML = `<div style="grid-column: 1/-1; text-align: center; padding: 1.25rem; font-size: 0.85rem; color: var(--text-secondary-1);">
                    <i class="fas fa-search" style="margin-bottom: 0.35rem; display: block; font-size: 1.2rem; opacity: 0.5;"></i>
                    No icons match "${currentIconSearch}".
                </div>`;
                return;
            }

            filtered.forEach(item => {
                const div = document.createElement('div');
                const isSelected = (item.icon === currentSelected);
                div.className = `icon-option ${isSelected ? 'selected' : ''}`;
                div.title = item.name;
                div.setAttribute('data-icon', item.icon);
                div.innerHTML = `<i class="fas ${item.icon}"></i>`;
                
                if (canEdit) {
                    div.addEventListener('click', () => {
                        selectIcon(item.icon, item.name);
                        updateLivePreview();
                    });
                }
                grid.appendChild(div);
            });
        }

        function selectIcon(iconClass, optName) {
            const iconInput = document.getElementById('categoryIconModal');
            if (iconInput) iconInput.value = iconClass;
            
            const grid = document.getElementById('iconGridModal');
            if (grid) {
                grid.querySelectorAll('.icon-option').forEach(opt => {
                    opt.classList.toggle('selected', opt.getAttribute('data-icon') === iconClass);
                });
            }

            const iconPreview = document.getElementById('acSelectedIconPreview');
            const iconNameDisplay = document.getElementById('acSelectedIconName');
            if (iconPreview) iconPreview.className = `fas ${iconClass}`;
            if (iconNameDisplay) {
                const match = iconDatabase.find(i => i.icon === iconClass);
                iconNameDisplay.textContent = optName || (match ? match.name : iconClass);
            }
        }

        function initIconFilterEvents() {
            const tabs = document.querySelectorAll('.ac-icon-tab');
            tabs.forEach(tab => {
                tab.addEventListener('click', () => {
                    tabs.forEach(t => t.classList.remove('active'));
                    tab.classList.add('active');
                    currentIconFilter = tab.getAttribute('data-cat') || 'all';
                    renderIconGrid();
                });
            });

            const searchInput = document.getElementById('acIconSearchInput');
            const clearBtn = document.getElementById('acIconSearchClear');
            if (searchInput) {
                searchInput.addEventListener('input', () => {
                    currentIconSearch = searchInput.value;
                    if (clearBtn) clearBtn.style.display = searchInput.value ? 'block' : 'none';
                    renderIconGrid();
                });
            }
            if (clearBtn) {
                clearBtn.addEventListener('click', () => {
                    if (searchInput) searchInput.value = '';
                    currentIconSearch = '';
                    clearBtn.style.display = 'none';
                    renderIconGrid();
                });
            }
        }

        // Live Preview Updater (Both Citizen Alert Feed Card + Header Tag)
        function updateLivePreview() {
            const name = document.getElementById('categoryNameModal')?.value.trim() || 'Category Name';
            const icon = document.getElementById('categoryIconModal')?.value || 'fa-exclamation-triangle';
            const color = document.getElementById('categoryColorModal')?.value || '#3a7675';
            const desc = document.getElementById('categoryDescriptionModal')?.value.trim() || 'Advisory and emergency response instructions will appear here for all broadcasted alerts in this category.';
            const status = document.getElementById('categoryStatusModal')?.value || 'active';

            // 1. Citizen Feed Card Elements
            const feedCard = document.getElementById('citizenFeedPreviewCard');
            const citizenBadge = document.getElementById('citizenPreviewBadge');
            const citizenIcon = document.getElementById('citizenPreviewIcon');
            const citizenName = document.getElementById('citizenPreviewCategoryName');
            const citizenTitle = document.getElementById('citizenPreviewTitle');
            const citizenDesc = document.getElementById('citizenPreviewDesc');
            const citizenStatusDot = document.getElementById('citizenPreviewStatusDot');

            if (feedCard) feedCard.style.setProperty('--preview-theme-color', color);
            if (citizenBadge) citizenBadge.style.backgroundColor = color;
            if (citizenIcon) citizenIcon.className = `fas ${icon}`;
            if (citizenName) citizenName.textContent = name;
            if (citizenTitle) citizenTitle.textContent = `${name} Advisory`;
            if (citizenDesc) citizenDesc.textContent = desc;
            if (citizenStatusDot) {
                const isActive = (status === 'active');
                citizenStatusDot.className = `ac-citizen-status-indicator ${isActive ? '' : 'inactive'}`;
                citizenStatusDot.innerHTML = `<i class="fas fa-circle"></i> ${isActive ? 'Active' : 'Inactive'}`;
            }

            // 2. Compact Badge / Tag Elements
            const compactPreview = document.getElementById('livePreviewModal');
            const compactIcon = document.getElementById('previewIconModal');
            const compactName = document.getElementById('previewNameModal');

            if (compactPreview) compactPreview.style.backgroundColor = color;
            if (compactIcon) compactIcon.className = `fas ${icon}`;
            if (compactName) compactName.textContent = name;

            // 3. Modal Header icon
            const modalHeaderIcon = document.getElementById('acModalHeaderIcon');
            if (modalHeaderIcon) {
                modalHeaderIcon.innerHTML = `<i class="fas ${icon}"></i>`;
                modalHeaderIcon.style.color = color;
                modalHeaderIcon.style.backgroundColor = `color-mix(in srgb, ${color} 18%, transparent)`;
                modalHeaderIcon.style.borderColor = `color-mix(in srgb, ${color} 35%, transparent)`;
            }
        }

        function resetFormModal() {
            const form = document.getElementById('categoryFormModal');
            if (!form) return;
            form.reset();
            document.getElementById('categoryIdModal').value = '';
            
            const titleEl = document.getElementById('acCategoryModalTitle');
            const subEl = document.getElementById('acCategoryModalSubtitle');
            const saveBtnText = document.getElementById('acSaveBtnText');
            
            if (titleEl) titleEl.textContent = 'Add New Category';
            if (subEl) subEl.textContent = 'Define a clear category name, icon, and color for citizen emergency alerts.';
            if (saveBtnText) saveBtnText.textContent = 'Save Category';

            document.getElementById('categoryIconModal').value = 'fa-exclamation-triangle';
            document.getElementById('categoryColorModal').value = '#3a7675';
            selectColorSwatch('#3a7675');
            selectIcon('fa-exclamation-triangle', 'Hazard Warning');
            
            const searchInput = document.getElementById('acIconSearchInput');
            if (searchInput) searchInput.value = '';
            currentIconSearch = '';
            currentIconFilter = 'all';
            document.querySelectorAll('.ac-icon-tab').forEach(t => t.classList.toggle('active', t.getAttribute('data-cat') === 'all'));
            renderIconGrid();
            
            updateLivePreview();
        }

        function loadCategories() {
            fetch('../api/alert-categories.php?action=list')
                .then(response => response.json())
                .then(data => {
                    const tbody = document.querySelector('#categoriesTable tbody');
                    tbody.innerHTML = '';
                    
                    if (data.success && data.categories) {
                        updateCategorySummary(data.categories);
                        data.categories.forEach(cat => {
                            const isInactive = cat.status === 'inactive';
                            const tr = document.createElement('tr');
                            if (isInactive) tr.className = 'muted-row';
                            tr.id = `cat-row-${cat.id}`;
                            
                            // Feature 2: Alert Load Impact Warnings
                            let impactWarning = '';
                            if (cat.alerts_count > 20) {
                                impactWarning = `<span class="impact-warning" title="High usage category - may cause alert fatigue"><i class="fas fa-exclamation-circle"></i> High Load</span>`;
                            }

                            tr.innerHTML = `
                                <td><i class="fas fa-chevron-down expand-btn" onclick="toggleDetails(${cat.id})"></i></td>
                                <td>
                                    <div style="display:flex; align-items:center;">
                                        <strong>${cat.name}</strong>
                                        ${impactWarning}
                                    </div>
                                </td>
                                <td>
                                    <div style="background:${cat.color}; color:white; padding:0.35rem 0.75rem; border-radius:50px; display:inline-flex; align-items:center; gap:0.5rem; font-size:0.8rem; font-weight: 600; text-transform: uppercase;">
                                        <i class="fas ${cat.icon}"></i> ${cat.name}
                                    </div>
                                </td>
                                <td>
                                    <span class="status-badge ${isInactive ? 'status-inactive' : 'status-active'}">
                                        ${isInactive ? 'Inactive' : 'Active'}
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <button class="btn btn-sm btn-primary" onclick='editCategoryModal(${JSON.stringify(cat)})' title="Edit" ${!canEdit ? 'disabled' : ''}>
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-sm btn-danger" onclick="deleteCategory(${cat.id}, '${cat.name}', ${cat.alerts_count || 0})" title="Delete" ${!canDelete ? 'disabled' : ''}>
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            `;
                            tbody.appendChild(tr);

                            // Hidden details row
                            const detailsTr = document.createElement('tr');
                            detailsTr.className = 'details-row';
                            detailsTr.id = `details-${cat.id}`;
                            detailsTr.innerHTML = `
                                <td colspan="5">
                                    <div class="details-content" id="details-content-${cat.id}">
                                        <div style="text-align:center; padding: 20px;">
                                            <i class="fas fa-spinner fa-spin"></i> Loading insights...
                                        </div>
                                    </div>
                                </td>
                            `;
                            tbody.appendChild(detailsTr);
                        });
                    } else {
                        tbody.innerHTML = `<tr><td colspan="5" style="text-align:center; padding:1.5rem; color:#c0392b;">${data.message || 'Failed to load categories.'}</td></tr>`;
                    }
                })
                .catch(() => {
                    const tbody = document.querySelector('#categoriesTable tbody');
                    tbody.innerHTML = `<tr><td colspan="5" style="text-align:center; padding:1.5rem; color:#c0392b;">Failed to load categories. Please refresh.</td></tr>`;
                });
        }

        function updateCategorySummary(categories) {
            const total = categories.length;
            const active = categories.filter(c => (c.status || 'active') === 'active').length;
            const inactive = total - active;
            const highLoad = categories.filter(c => (c.alerts_count || 0) > 20).length;

            const totalEl = document.getElementById('acTotalCats');
            const activeEl = document.getElementById('acActiveCats');
            const inactiveEl = document.getElementById('acInactiveCats');
            const highLoadEl = document.getElementById('acHighLoadCats');

            if (totalEl) totalEl.textContent = total;
            if (activeEl) activeEl.textContent = active;
            if (inactiveEl) inactiveEl.textContent = inactive;
            if (highLoadEl) highLoadEl.textContent = highLoad;

            const activeSub = document.getElementById('acActiveCatsSub');
            const inactiveSub = document.getElementById('acInactiveCatsSub');
            const highLoadSub = document.getElementById('acHighLoadCatsSub');
            if (activeSub) activeSub.textContent = total ? `${Math.round((active / total) * 100)}% of categories` : 'No categories yet';
            if (inactiveSub) inactiveSub.textContent = inactive ? `${inactive} pending review` : 'All active';
            if (highLoadSub) highLoadSub.textContent = highLoad ? `${highLoad} needs attention` : 'Healthy usage';
        }

        function toggleDetails(id) {
            const detailsRow = document.getElementById(`details-${id}`);
            const btn = document.querySelector(`#cat-row-${id} .expand-btn`);
            const isVisible = detailsRow.style.display === 'table-row';
            
            // Close other rows (optional, but cleaner)
            document.querySelectorAll('.details-row').forEach(row => row.style.display = 'none');
            document.querySelectorAll('.expand-btn').forEach(b => b.classList.remove('active'));

            if (!isVisible) {
                detailsRow.style.display = 'table-row';
                btn.classList.add('active');
                loadAnalytics(id);
            }
        }

        function loadAnalytics(id) {
            const container = document.getElementById(`details-content-${id}`);
            
            if (analyticsCache[id]) {
                renderAnalytics(id, analyticsCache[id]);
                return;
            }

            fetch(`../api/alert-categories.php?action=analytics&id=${id}`)
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        analyticsCache[id] = data.analytics;
                        renderAnalytics(id, data.analytics);
                    } else {
                        container.innerHTML = `<div class="alert alert-danger">Error: ${data.message}</div>`;
                    }
                });
        }

        function renderAnalytics(id, data) {
            const container = document.getElementById(`details-content-${id}`);
            
            let auditLogsHtml = data.audit_logs.length > 0 
                ? data.audit_logs.map(log => `
                    <div class="audit-item">
                        <span><strong>${log.admin_name}</strong> ${log.description.split(': ')[0]}</span>
                        <span class="audit-date">${new Date(log.created_at).toLocaleString()}</span>
                    </div>
                `).join('')
                : '<div style="padding:15px; text-align:center; color:#999;">No audit logs found.</div>';

            // Feature 3: AI-Assisted Category Suggestions
            let aiSuggestion = '';
            if (data.total_alerts > 15) {
                aiSuggestion = `
                    <div class="ai-suggestion-box">
                        <div>
                            <span class="ai-badge">AI SUGGESTION</span>
                            High activity detected. Consider creating sub-categories to avoid alert fatigue.
                        </div>
                        <button class="btn btn-sm btn-secondary" onclick="alert('Manual action required: Please create a specific sub-category for more targeted alerts.')">Act</button>
                    </div>
                `;
            }

            // Feature 4: Export Analytics & Audit Logs
            const canExport = adminRole === 'super_admin' || adminRole === 'admin';

            container.innerHTML = `
                <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 1.5rem;">
                    <div style="flex: 1; min-width: 300px;">
                        <div class="analytics-grid">
                            <div class="stat-box">
                                <div class="label">Total Alerts</div>
                                <div class="value">${data.total_alerts}</div>
                            </div>
                            <div class="stat-box">
                                <div class="label">Active Subscribers</div>
                                <div class="value">${data.active_subscribers}</div>
                            </div>
                            <div class="stat-box">
                                <div class="label">Last Used</div>
                                <div class="value" style="font-size: 0.9rem;">${data.last_used !== 'Never' ? new Date(data.last_used).toLocaleDateString() : 'Never'}</div>
                            </div>
                        </div>
                        ${aiSuggestion}
                    </div>
                    <div style="flex: 1; min-width: 300px;">
                        <div class="stat-box" style="height: auto;">
                            <div class="label">7-Day Usage Trend</div>
                            <div class="chart-container">
                                <canvas id="chart-${id}"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="export-actions" style="${!canExport ? 'display:none' : ''}">
                    <button class="btn btn-sm btn-secondary" onclick="exportCategoryData(${id}, 'csv')"><i class="fas fa-file-csv"></i> Export CSV</button>
                    <button class="btn btn-sm btn-secondary" onclick="exportCategoryData(${id}, 'pdf')"><i class="fas fa-file-pdf"></i> Export PDF</button>
                </div>

                <div class="module-card" style="box-shadow: none; border: 1px solid var(--border-color-1); margin-top: 1rem;">
                    <div class="module-card-header" style="padding: 0.75rem 1rem;">
                        <h4 style="margin:0; font-size:0.9rem; font-weight: 700;"><i class="fas fa-history"></i> Recent Audit Trail</h4>
                    </div>
                    <div class="audit-list" style="border: none;">
                        ${auditLogsHtml}
                    </div>
                </div>
            `;

            // Feature 1: Category Trend Charts
            setTimeout(() => initTrendChart(id, data.trend), 50);
        }

        function initTrendChart(id, trendData) {
            const ctx = document.getElementById(`chart-${id}`).getContext('2d');
            if (activeCharts[id]) activeCharts[id].destroy();

            activeCharts[id] = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: trendData.labels,
                    datasets: [{
                        label: 'Alerts',
                        data: trendData.values,
                        borderColor: '#3a7675',
                        backgroundColor: 'rgba(58, 118, 117, 0.1)',
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: {
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
                }
            });
        }

        function exportCategoryData(id, format) {
            const data = analyticsCache[id];
            if (!data) return;

            if (format === 'csv') {
                let csv = 'Action,Description,Date\n';
                data.audit_logs.forEach(log => {
                    csv += `"${log.action}","${log.description}","${log.created_at}"\n`;
                });
                const blob = new Blob([csv], { type: 'text/csv' });
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.setAttribute('hidden', '');
                a.setAttribute('href', url);
                a.setAttribute('download', `category_${id}_audit_log.csv`);
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
            } else {
                const element = document.getElementById(`details-content-${id}`);
                const opt = {
                    margin: 1,
                    filename: `category_${id}_report.pdf`,
                    image: { type: 'jpeg', quality: 0.98 },
                    html2canvas: { scale: 2 },
                    jsPDF: { unit: 'in', format: 'letter', orientation: 'portrait' }
                };
                html2pdf().set(opt).from(element).save();
            }
        }

        function editCategoryModal(cat) {
            if (!canEdit) return;
            document.getElementById('categoryIdModal').value = cat.id;
            document.getElementById('categoryNameModal').value = cat.name;
            document.getElementById('categoryDescriptionModal').value = cat.description || '';
            document.getElementById('categoryColorModal').value = cat.color;
            document.getElementById('categoryIconModal').value = cat.icon;
            document.getElementById('categoryStatusModal').value = cat.status || 'active';

            const titleEl = document.getElementById('acCategoryModalTitle');
            const subEl = document.getElementById('acCategoryModalSubtitle');
            const saveBtnText = document.getElementById('acSaveBtnText');
            
            if (titleEl) titleEl.textContent = 'Edit Category';
            if (subEl) subEl.textContent = `Updating settings and public visual identity for "${cat.name}".`;
            if (saveBtnText) saveBtnText.textContent = 'Update Category';

            selectColorSwatch(cat.color);
            selectIcon(cat.icon, cat.name);
            updateLivePreview();
            openCategoryModal();
        }

        function deleteCategory(id, name, count) {
            if (!canDelete) return;
            if (count > 0) {
                alert(`Deletion Blocked: "${name}" is linked to ${count} alerts.\n\nPlease disable it instead to preserve audit history.`);
                return;
            }

            if (confirm(`Permanently delete category "${name}"? This action is audited.`)) {
                fetch('../api/alert-categories.php', {
                    method: 'DELETE',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({id: id})
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        loadCategories();
                    } else {
                        alert('Error: ' + data.message);
                    }
                });
            }
        }

        // Initialize UI and load data on load
        document.addEventListener('DOMContentLoaded', () => {
            initTemplateChips();
            initColorSwatches();
            initIconGrid();
            loadCategories();
            updateLivePreview();
        });

        // Modal controls
        const modalBackdrop = document.getElementById('acCategoryModalBackdrop');
        const openBtn = document.getElementById('openCategoryModalBtn');
        const closeBtn = document.getElementById('acCloseCategoryModalBtn');
        const closeBtnFooter = document.getElementById('acCloseCategoryModalBtnFooter');
        const saveFooterBtn = document.getElementById('acSaveFromFooterBtn');
        const modalForm = document.getElementById('categoryFormModal');

        function openCategoryModal() {
            if (modalBackdrop) {
                modalBackdrop.classList.add('show');
                modalBackdrop.setAttribute('aria-hidden', 'false');
                document.body.style.overflow = 'hidden';
            }
        }

        function closeCategoryModal() {
            if (modalBackdrop) {
                modalBackdrop.classList.remove('show');
                modalBackdrop.setAttribute('aria-hidden', 'true');
                document.body.style.overflow = '';
            }
        }

        if (openBtn) openBtn.onclick = () => {
            resetFormModal();
            openCategoryModal();
        };
        if (closeBtn) closeBtn.onclick = closeCategoryModal;
        if (closeBtnFooter) closeBtnFooter.onclick = closeCategoryModal;
        if (saveFooterBtn && modalForm) {
            saveFooterBtn.onclick = () => modalForm.requestSubmit();
        }

        if (modalBackdrop) {
            modalBackdrop.addEventListener('click', (e) => {
                if (e.target === modalBackdrop) closeCategoryModal();
            });
        }

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && modalBackdrop && modalBackdrop.classList.contains('show')) {
                closeCategoryModal();
            }
        });

        const categoryNameModal = document.getElementById('categoryNameModal');
        const categoryColorModal = document.getElementById('categoryColorModal');
        const categoryDescModal = document.getElementById('categoryDescriptionModal');
        const categoryStatusModal = document.getElementById('categoryStatusModal');
        
        if (categoryNameModal) categoryNameModal.addEventListener('input', updateLivePreview);
        if (categoryColorModal) categoryColorModal.addEventListener('input', updateLivePreview);
        if (categoryDescModal) categoryDescModal.addEventListener('input', updateLivePreview);
        if (categoryStatusModal) categoryStatusModal.addEventListener('change', updateLivePreview);

        if (modalForm) {
            modalForm.addEventListener('submit', function(e) {
                e.preventDefault();
                if (!canEdit) return;

                const formData = new FormData(this);
                const isEdit = !!document.getElementById('categoryIdModal').value;
                const saveBtnText = document.getElementById('acSaveBtnText');
                
                if (saveFooterBtn) {
                    saveFooterBtn.disabled = true;
                    saveFooterBtn.innerHTML = '<i class="fas fa-spinner fa-spin" style="margin-right: 0.4rem;"></i> Saving...';
                }
                
                fetch('../api/alert-categories.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        resetFormModal();
                        loadCategories();
                        closeCategoryModal();
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while saving category.');
                })
                .finally(() => {
                    if (saveFooterBtn) {
                        saveFooterBtn.disabled = false;
                        saveFooterBtn.innerHTML = `<i class="fas fa-save" style="margin-right: 0.4rem;"></i> <span id="acSaveBtnText">${isEdit ? 'Update Category' : 'Save Category'}</span>`;
                    }
                });
            });
        }
    </script>
</body>
</html>


