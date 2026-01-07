<?php
/**
 * PHIVOLCS Earthquake Monitoring Page
 * Monitor earthquakes in the Philippines region using USGS data
 */

// Start session and check authentication
session_start();

// Check if user is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../login.php');
    exit();
}

$pageTitle = 'PHIVOLCS Earthquake Monitoring';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <link rel="icon" type="image/x-icon" href="images/favicon.ico">
    <link rel="stylesheet" href="css/global.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/sidebar.css">
    <link rel="stylesheet" href="css/admin-header.css">
    <link rel="stylesheet" href="css/buttons.css">
    <link rel="stylesheet" href="css/forms.css">
    <link rel="stylesheet" href="css/datatables.css">
    <link rel="stylesheet" href="css/hero.css">
    <link rel="stylesheet" href="css/sidebar-footer.css">
    <link rel="stylesheet" href="css/modules.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <style>
        .map-container {
            width: 100%;
            height: calc(100vh - 200px);
            min-height: 600px;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            position: relative;
        }
        
        #earthquakeMap {
            width: 100%;
            height: 100%;
        }
        
        .earthquake-controls {
            position: absolute;
            top: 20px;
            right: 20px;
            z-index: 1000;
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        
        .earthquake-control-btn {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 8px;
            padding: 0.75rem 1rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.9rem;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            color: var(--text-color-1);
            font-weight: 500;
        }
        
        .earthquake-control-btn:hover {
            background: rgba(255, 255, 255, 1);
            box-shadow: 0 6px 20px rgba(0,0,0,0.2);
            transform: translateY(-2px);
        }
        
        .earthquake-control-btn.active {
            background: linear-gradient(135deg, var(--primary-color-1), #6c5ce7);
            color: white;
            border-color: transparent;
            box-shadow: 0 4px 16px rgba(102, 126, 234, 0.4);
        }
        
        .earthquake-control-btn.active:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 24px rgba(102, 126, 234, 0.5);
        }
        
        .earthquake-info {
            position: absolute;
            top: 20px;
            left: 20px;
            background: var(--card-bg-1);
            backdrop-filter: blur(10px);
            border-radius: 8px;
            padding: 1rem;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            border: 1px solid var(--border-color-1);
            z-index: 1000;
            max-width: 300px;
            font-size: 0.9rem;
        }
        
        .earthquake-info-header {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 0.75rem;
            font-weight: 600;
            color: var(--primary-color-1);
        }
        
        .earthquake-info-content p {
            margin: 0.5rem 0;
            color: var(--text-color-1);
        }
        
        .earthquake-legend {
            margin-top: 0.75rem;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.5rem;
        }
        
        .legend-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.85rem;
        }
        
        .legend-color {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            border: 2px solid white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.3);
        }
        
        /* Custom earthquake marker styling */
        .earthquake-marker-custom {
            background: transparent !important;
            border: none !important;
            pointer-events: auto !important;
        }
        
        .earthquake-marker-custom div {
            pointer-events: auto !important;
            cursor: pointer !important;
            display: block !important;
            visibility: visible !important;
            opacity: 1 !important;
            background-color: inherit !important;
        }
        
        /* Override Leaflet default marker styles */
        .leaflet-marker-icon.earthquake-marker-custom {
            background: transparent !important;
            border: none !important;
        }
        
        /* Ensure markers are visible on map */
        .leaflet-marker-icon {
            z-index: 1000 !important;
        }
        
        /* Hide default Leaflet marker shadow for custom markers */
        .leaflet-marker-shadow.earthquake-marker-custom {
            display: none !important;
        }
        
        .earthquake-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .stat-card {
            background: var(--card-bg-1);
            padding: 1.25rem;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            border-left: 4px solid var(--primary-color-1);
            transition: transform 0.2s, box-shadow 0.2s;
            position: relative;
            overflow: hidden;
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, var(--primary-color-1), transparent);
            opacity: 0;
            transition: opacity 0.2s;
        }
        
        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(0,0,0,0.12);
        }
        
        .stat-card:hover::before {
            opacity: 1;
        }
        
        .stat-card h3 {
            margin: 0 0 0.75rem 0;
            font-size: 0.9rem;
            color: var(--text-secondary-1);
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .stat-card .stat-value {
            font-size: 2rem;
            font-weight: 600;
            color: var(--primary-color-1);
        }
        
        .stat-card-last-earthquake {
            border-left-color: #FF9800;
        }
        
        .stat-card-last-earthquake .stat-value {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--text-color-1);
        }
        
        .qc-risk-alert-panel {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 12px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.15);
            margin-bottom: 1.5rem;
            overflow: hidden;
            border: 2px solid rgba(255,255,255,0.2);
            animation: slideDown 0.5s ease-out;
        }
        
        .qc-risk-header {
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(10px);
            padding: 1.25rem 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: white;
        }
        
        .qc-risk-badge {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 700;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            background: rgba(255,255,255,0.2);
            backdrop-filter: blur(10px);
        }
        
        .qc-risk-badge.low { background: rgba(76, 175, 80, 0.3); }
        .qc-risk-badge.moderate { background: rgba(255, 193, 7, 0.3); }
        .qc-risk-badge.high { background: rgba(255, 152, 0, 0.3); }
        .qc-risk-badge.critical { background: rgba(244, 67, 54, 0.3); }
        
        .qc-risk-content {
            padding: 1.5rem;
            background: rgba(255,255,255,0.95);
            color: var(--text-color-1);
        }
        
        [data-theme="dark"] .qc-risk-content {
            background: rgba(30, 30, 40, 0.95);
            color: var(--text-color-1);
        }
        
        [data-theme="dark"] .qc-risk-item {
            background: rgba(40, 40, 50, 0.8);
            border-left-color: var(--primary-color-1);
        }
        
        [data-theme="dark"] .qc-risk-item-content h4 {
            color: var(--text-color-1);
        }
        
        [data-theme="dark"] .qc-risk-item-content p {
            color: var(--text-secondary-1);
        }
        
        [data-theme="dark"] .stat-card {
            background: var(--card-bg-1);
        }
        
        [data-theme="dark"] .stat-card-last-earthquake {
            background: var(--card-bg-1);
        }
        
        [data-theme="dark"] .earthquake-control-btn {
            background: rgba(40, 40, 50, 0.95);
            color: var(--text-color-1);
            border-color: rgba(255, 255, 255, 0.1);
        }
        
        [data-theme="dark"] .earthquake-control-btn:hover {
            background: rgba(50, 50, 60, 0.95);
        }
        
        [data-theme="dark"] .earthquake-info {
            background: var(--card-bg-1);
            color: var(--text-color-1);
        }
        
        [data-theme="dark"] .earthquake-info-content p {
            color: var(--text-color-1);
        }
        
        [data-theme="dark"] .ai-analytics-content {
            background: var(--card-bg-1);
            color: var(--text-color-1);
        }
        
        [data-theme="dark"] .hazard-item {
            background: rgba(40, 40, 50, 0.8);
            color: var(--text-color-1);
        }
        
        [data-theme="dark"] .recommendation-item {
            background: rgba(30, 58, 95, 0.8);
            color: var(--text-color-1);
        }
        
        [data-theme="dark"] .stat-card .stat-value {
            color: var(--primary-color-1);
        }
        
        [data-theme="dark"] .stat-card-last-earthquake .stat-value {
            color: var(--text-color-1);
        }
        
        [data-theme="dark"] .qc-risk-alert-panel {
            border-color: rgba(255, 255, 255, 0.3);
        }
        
        .qc-risk-item {
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            padding: 1rem;
            margin-bottom: 0.75rem;
            background: var(--card-bg-1);
            border-radius: 8px;
            border-left: 4px solid var(--primary-color-1);
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .qc-risk-item:hover {
            transform: translateX(4px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        .qc-risk-item-icon {
            font-size: 1.5rem;
            color: var(--primary-color-1);
            flex-shrink: 0;
        }
        
        .qc-risk-item-content h4 {
            margin: 0 0 0.5rem 0;
            font-size: 1rem;
            color: var(--text-color-1);
        }
        
        .qc-risk-item-content p {
            margin: 0;
            font-size: 0.9rem;
            color: var(--text-secondary-1);
            line-height: 1.6;
        }
        
        .travel-safety-badge {
            display: inline-block;
            padding: 0.4rem 0.8rem;
            border-radius: 6px;
            font-size: 0.85rem;
            font-weight: 600;
            margin-top: 0.5rem;
        }
        
        .travel-safety-badge.safe {
            background: #4CAF50;
            color: white;
        }
        
        .travel-safety-badge.caution {
            background: #FFC107;
            color: #333;
        }
        
        .travel-safety-badge.unsafe {
            background: #F44336;
            color: white;
        }
        
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        @keyframes pulse-glow {
            0%, 100% {
                box-shadow: 0 0 0 0 rgba(102, 126, 234, 0.7);
            }
            50% {
                box-shadow: 0 0 0 10px rgba(102, 126, 234, 0);
            }
        }
        
        .qc-risk-alert-panel.critical-alert {
            animation: pulse-glow 2s infinite;
            border-color: #F44336;
        }
        
        .ai-analytics-panel {
            background: var(--card-bg-1);
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            border: 1px solid var(--border-color-1);
            margin-bottom: 1.5rem;
            overflow: hidden;
        }
        
        .ai-analytics-header {
            background: linear-gradient(135deg, var(--primary-color-1), #6c5ce7);
            color: white;
            padding: 1rem 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .ai-analytics-content {
            padding: 1.5rem;
            max-height: 500px;
            overflow-y: auto;
        }
        
        .risk-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            margin-left: 0.5rem;
        }
        
        .risk-low { background: #4CAF50; color: white; }
        .risk-moderate { background: #FFC107; color: #333; }
        .risk-high { background: #FF9800; color: white; }
        .risk-critical { background: #F44336; color: white; }
        
        .hazard-item {
            background: var(--card-bg-1);
            border-left: 4px solid var(--primary-color-1);
            padding: 0.75rem 1rem;
            margin: 0.5rem 0;
            border-radius: 4px;
        }
        
        .impact-item {
            padding: 0.5rem 0;
            border-bottom: 1px solid var(--border-color-1);
        }
        
        .impact-item:last-child {
            border-bottom: none;
        }
        
        .recommendation-item {
            background: #e3f2fd;
            padding: 0.75rem;
            margin: 0.5rem 0;
            border-radius: 4px;
            border-left: 3px solid #2196F3;
        }
        
        [data-theme="dark"] .recommendation-item {
            background: #1e3a5f;
            border-left-color: #64b5f6;
        }
        
        /* Map always shows natural colors - not affected by dark mode */
        #earthquakeMap {
            filter: none !important;
        }
        
        .leaflet-container {
            background-color: #a3ccff !important; /* Light blue ocean background */
        }
        
        .leaflet-tile-container img {
            filter: none !important;
        }
        
        /* Prevent dark mode from affecting map */
        [data-theme="dark"] #earthquakeMap {
            filter: none !important;
        }
        
        [data-theme="dark"] .leaflet-container {
            background-color: #a3ccff !important;
        }
        
        [data-theme="dark"] .leaflet-tile-container img {
            filter: none !important;
        }
    </style>
</head>
<body>
    <!-- Include Sidebar Component -->
    <?php include 'includes/sidebar.php'; ?>

    <!-- Include Admin Header Component -->
    <?php include 'includes/admin-header.php'; ?>
    
    <!-- ===================================
       MAIN CONTENT - PHIVOLCS Earthquake Monitoring
       =================================== -->
    <div class="main-content">
        <div class="main-container">
            <div class="title">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                    <div style="font-size: 0.9rem; color: var(--text-secondary-1);">
                        <i class="fas fa-user-circle" style="margin-right: 0.5rem;"></i>
                        <strong>Admin:</strong> <?php echo htmlspecialchars($_SESSION['admin_username'] ?? 'Admin User'); ?>
                    </div>
                </div>
                <nav class="breadcrumb" aria-label="Breadcrumb">
                    <ol class="breadcrumb-list">
                        <li class="breadcrumb-item">
                            <a href="/" class="breadcrumb-link">
                                <span>Home</span>
                            </a>
                        </li>
                        <li class="breadcrumb-item">
                            <a href="automated-warnings.php" class="breadcrumb-link">
                                <span>Automated Warnings</span>
                            </a>
                        </li>
                        <li class="breadcrumb-item active" aria-current="page">
                            <span>PHIVOLCS Earthquake Monitoring</span>
                        </li>
                    </ol>
                </nav>
                <h1><i class="fas fa-mountain"></i> PHIVOLCS Earthquake Monitoring</h1>
                <p>Monitor earthquakes in the Philippines region using real-time data from USGS Earthquake Hazards Program. <strong>Note:</strong> This system monitors earthquakes (including volcano-tectonic earthquakes from volcanic activity). For comprehensive volcanic activity monitoring, please refer to PHIVOLCS official alerts.</p>
            </div>
            
            <div class="sub-container">
                <div class="page-content">
                    <!-- Earthquake Statistics -->
                    <div class="earthquake-stats" id="earthquakeStats">
                        <div class="stat-card">
                            <h3>Total Events (30 days)</h3>
                            <div class="stat-value" id="totalEvents">-</div>
                        </div>
                        <div class="stat-card">
                            <h3>Major Earthquakes (5.0+)</h3>
                            <div class="stat-value" id="majorEvents">-</div>
                        </div>
                        <div class="stat-card">
                            <h3>Latest Magnitude</h3>
                            <div class="stat-value" id="latestMagnitude">-</div>
                        </div>
                        <div class="stat-card stat-card-last-earthquake">
                            <h3><i class="fas fa-clock"></i> Last Earthquake</h3>
                            <div class="stat-value" style="font-size: 1.1rem;" id="lastEarthquakeTime">-</div>
                            <div style="font-size: 0.85rem; color: var(--text-secondary-1); margin-top: 0.5rem;" id="lastEarthquakeLocation">-</div>
                            <div style="font-size: 0.75rem; color: var(--text-secondary-1); margin-top: 0.25rem;" id="lastEarthquakeMagnitude">-</div>
                        </div>
                        <div class="stat-card">
                            <h3>Last Update</h3>
                            <div class="stat-value" style="font-size: 1rem;" id="lastUpdate">-</div>
                            <div style="font-size: 0.75rem; color: var(--text-secondary-1); margin-top: 0.25rem;" id="realtimeIndicator">
                                <i class="fas fa-circle" style="color: #4CAF50; font-size: 0.5rem;"></i> Real-time active
                            </div>
                        </div>
                    </div>
                    
                    <!-- Quezon City Risk Alert Panel -->
                    <div class="qc-risk-alert-panel" id="qcRiskAlertPanel">
                        <div class="qc-risk-header">
                            <div style="display: flex; align-items: center; gap: 0.75rem;">
                                <i class="fas fa-map-marker-alt" style="font-size: 1.5rem;"></i>
                                <div>
                                    <h3 style="margin: 0; font-size: 1.1rem;">Quezon City Risk Assessment</h3>
                                    <p style="margin: 0.25rem 0 0 0; font-size: 0.85rem; opacity: 0.9;">Automated real-time analysis</p>
                                </div>
                            </div>
                            <div class="qc-risk-badge" id="qcRiskBadge">
                                <span id="qcRiskLevel">ASSESSING...</span>
                            </div>
                        </div>
                        <div class="qc-risk-content" id="qcRiskContent">
                            <div style="text-align: center; padding: 1.5rem;">
                                <i class="fas fa-spinner fa-spin" style="font-size: 1.5rem; color: var(--primary-color-1);"></i>
                                <p style="margin-top: 0.5rem;">Analyzing Quezon City risk...</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- AI Analytics Panel -->
                    <div class="ai-analytics-panel" id="aiAnalyticsPanel" style="display: none;">
                        <div class="ai-analytics-header">
                            <div style="display: flex; align-items: center; gap: 0.5rem;">
                                <i class="fas fa-map-marker-alt" style="color: var(--primary-color-1);"></i>
                                <div>
                                    <h3 style="margin: 0;">AI Impact Analysis for Quezon City</h3>
                                    <p style="margin: 0.25rem 0 0 0; font-size: 0.8rem; opacity: 0.9;">Focused analysis on Quezon City area</p>
                                </div>
                            </div>
                            <button onclick="document.getElementById('aiAnalyticsPanel').style.display='none'" style="background:none;border:none;color:inherit;cursor:pointer;font-size:1.5rem;line-height:1;opacity:0.8;transition:opacity 0.2s;" onmouseover="this.style.opacity='1'" onmouseout="this.style.opacity='0.8'">×</button>
                        </div>
                        <div class="ai-analytics-content" id="aiAnalyticsContent">
                            <div style="text-align: center; padding: 2rem;">
                                <i class="fas fa-spinner fa-spin" style="font-size: 2rem; color: var(--primary-color-1);"></i>
                                <p>Analyzing earthquake impacts...</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Map Container -->
                    <div class="map-container">
                        <div id="earthquakeMap"></div>
                        
                        <!-- Quezon City Focus Status -->
                        <div id="quezonCityStatus" onclick="focusQuezonCity()" style="cursor: pointer; position: absolute; bottom: 20px; right: 20px; background: var(--primary-color-1); color: white; padding: 0.75rem 1rem; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.2); z-index: 1000; display: flex; align-items: center; gap: 0.5rem; font-weight: 500;" title="Click to focus on Quezon City">
                            <i class="fas fa-map-marker-alt"></i>
                            <span>Focused on Quezon City</span>
                        </div>
                        
                        <!-- Controls -->
                        <div class="earthquake-controls">
                            <button id="refreshBtn" class="earthquake-control-btn" title="Refresh Earthquake Data (Last 30 Days)">
                                <i class="fas fa-sync-alt"></i>
                                <span>Refresh</span>
                            </button>
                            <button id="checkNowBtn" class="earthquake-control-btn" title="Check for New Earthquakes Right Now" onclick="checkRecentEarthquakes()">
                                <i class="fas fa-search"></i>
                                <span>Check Now</span>
                            </button>
                            <button id="filterBtn" class="earthquake-control-btn" title="Filter by Magnitude">
                                <i class="fas fa-filter"></i>
                                <span>Filter</span>
                            </button>
                            <button id="realtimeToggleBtn" class="earthquake-control-btn active" title="Toggle Real-time Updates" onclick="toggleRealtime()">
                                <i class="fas fa-circle" style="color: #4CAF50; font-size: 0.7rem;"></i>
                                <span id="realtimeStatus">Real-time ON</span>
                            </button>
                        </div>
                        
                        <!-- Info Panel -->
                        <div class="earthquake-info" id="earthquakeInfo" style="display: none;">
                            <div class="earthquake-info-header">
                                <i class="fas fa-mountain"></i>
                                <span>PHIVOLCS Earthquake Monitoring</span>
                                <button onclick="document.getElementById('earthquakeInfo').style.display='none'" style="background:none;border:none;color:inherit;cursor:pointer;margin-left:auto;">×</button>
                            </div>
                            <div class="earthquake-info-content" id="earthquakeInfoContent">
                                <!-- Content will be populated by JavaScript -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        let map;
        let earthquakeMarkers = [];
        let earthquakeData = [];
        let minMagnitude = 2.5;
        let realtimeEnabled = true;
        let realtimeInterval = null;
        let recentCheckInterval = null;
        let lastUpdateTime = null;
        let lastEarthquakeCount = 0;
        let lastRecentEarthquakeIds = new Set();
        
        // Initialize map
        function initMap() {
            // Focus on Quezon City center
            map = L.map('earthquakeMap').setView([14.6488, 121.0509], 12);
            
            // Standard OpenStreetMap tiles (green land, blue ocean)
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
                maxZoom: 19
            }).addTo(map);
            
            // Load Quezon City boundary with vibrant border
            loadQuezonCityBoundary();
            
            // Load earthquake data
            loadEarthquakeData();
            
            // Setup button handlers
            document.getElementById('refreshBtn')?.addEventListener('click', loadEarthquakeData);
            document.getElementById('filterBtn')?.addEventListener('click', showFilterDialog);
            
            // Start real-time updates
            startRealtimeUpdates();
            
            // Initial Quezon City risk assessment (will be updated when data loads)
            setTimeout(() => {
                if (earthquakeData.length > 0) {
                    updateQuezonCityRisk(earthquakeData);
                }
            }, 2000);
            
            // Ensure Quezon City stays focused on resize
            window.addEventListener('resize', () => {
                setTimeout(() => {
                    if (map) {
                        map.invalidateSize();
                        if (!map.getBounds().contains([14.6488, 121.0509])) {
                            map.setView([14.6488, 121.0509], map.getZoom());
                        }
                    }
                }, 100);
            });
        }
        
        // Load Quezon City boundary with vibrant, visible border
        function loadQuezonCityBoundary() {
            fetch('../api/quezon-city.geojson')
                .then(response => response.json())
                .then(geojsonData => {
                    L.geoJSON(geojsonData, {
                        style: {
                            color: '#FF5722', // Vibrant orange-red color
                            weight: 5, // Thicker line for visibility
                            fillColor: '#4c8a89', // Teal fill
                            fillOpacity: 0.15, // Slight transparency
                            dashArray: '10, 5', // Dashed line pattern
                            opacity: 1.0 // Full opacity for vibrant appearance
                        }
                    }).addTo(map);
                })
                .catch(err => console.error('Error loading Quezon City boundary:', err));
        }
        
        // Load PHIVOLCS/USGS earthquake data for Philippines
        function loadEarthquakeData() {
            // Show loading state
            const refreshBtn = document.getElementById('refreshBtn');
            if (refreshBtn) {
                refreshBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> <span>Loading...</span>';
                refreshBtn.disabled = true;
            }
            
            // Philippines bounding box: lat 4.5 to 21.0, lon 116.0 to 127.0
            // Extended slightly to ensure we catch all earthquakes including those near borders
            const philippinesBounds = {
                minLat: 4.0,  // Extended south
                maxLat: 21.5, // Extended north
                minLon: 115.5, // Extended west
                maxLon: 127.5  // Extended east (includes Albay/Bicol region)
            };
            
            // USGS Earthquake API - Last 30 days with FULL TIMESTAMP (not just date)
            // This ensures we get ALL earthquakes including recent ones
            const startTime = new Date();
            startTime.setDate(startTime.getDate() - 30);
            const endTime = new Date();
            
            // Use FULL ISO timestamp (not just date) to get precise real-time data
            const url = `https://earthquake.usgs.gov/fdsnws/event/1/query?format=geojson&starttime=${startTime.toISOString()}&endtime=${endTime.toISOString()}&minmagnitude=${minMagnitude}&maxlatitude=${philippinesBounds.maxLat}&minlatitude=${philippinesBounds.minLat}&maxlongitude=${philippinesBounds.maxLon}&minlongitude=${philippinesBounds.minLon}`;
            
            console.log('Loading earthquake data from:', startTime.toISOString(), 'to', endTime.toISOString());
            
            fetch(url)
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    console.log('Earthquake data loaded:', data.metadata?.count || 0, 'earthquakes');
                    
                    // Only clear markers if we have new data to replace them
                    if (data.features && data.features.length > 0) {
                        console.log('Processing', data.features.length, 'earthquakes');
                        
                        // Clear existing markers BEFORE adding new ones
                        earthquakeMarkers.forEach(marker => {
                            if (map.hasLayer(marker)) {
                                map.removeLayer(marker);
                            }
                        });
                        earthquakeMarkers = [];
                        
                        // Store new data
                        earthquakeData = data.features;
                        
                        // Track earthquake IDs for real-time detection
                        // Use a more reliable ID generation method
                        lastRecentEarthquakeIds = new Set(data.features.map(f => {
                            // Try multiple ID sources for reliability
                            if (f.id) return String(f.id);
                            if (f.properties.code) return String(f.properties.code);
                            if (f.properties.ids) return String(f.properties.ids).split(',')[0]; // USGS sometimes provides comma-separated IDs
                            // Fallback: create unique ID from time + coordinates
                            const coords = f.geometry.coordinates;
                            return `${f.properties.time}_${coords[0].toFixed(4)}_${coords[1].toFixed(4)}_${(coords[2] || 0).toFixed(1)}`;
                        }));
                        console.log('Tracked', lastRecentEarthquakeIds.size, 'earthquake IDs for real-time detection');
                        
                        data.features.forEach(feature => {
                            const [lon, lat] = feature.geometry.coordinates;
                            const mag = feature.properties.mag || 0;
                            const place = feature.properties.place || 'Unknown';
                            const time = new Date(feature.properties.time);
                            const depth = feature.geometry.coordinates[2] || 0;
                            
                            // Determine marker color based on magnitude
                            let color = '#4CAF50'; // Green - minor (2.5-4.0)
                            if (mag >= 5.0) color = '#FF5722'; // Red - major (5.0+)
                            else if (mag >= 4.5) color = '#FF9800'; // Orange - moderate (4.5-5.0)
                            else if (mag >= 4.0) color = '#FFC107'; // Yellow - light (4.0-4.5)
                            
                            // Create custom icon with more visible styling
                            const iconSize = Math.max(25, Math.min(60, mag * 10)); // Larger default size
                            const borderWidth = mag >= 5.0 ? 4 : 3; // Thicker border for major earthquakes
                            
                            // Create custom DIV icon - MUST use divIcon to show custom colored circles
                            const iconHtml = `<div style="background-color:${color} !important;width:${iconSize}px !important;height:${iconSize}px !important;border-radius:50% !important;border:${borderWidth}px solid white !important;box-shadow:0 0 8px ${color}, 0 0 16px ${color}, 0 2px 8px rgba(0,0,0,0.5) !important;position:relative !important;z-index:1000 !important;display:block !important;margin:0 !important;padding:0 !important;"></div>`;
                            
                            const icon = L.divIcon({
                                className: 'earthquake-marker-custom',
                                html: iconHtml,
                                iconSize: [iconSize, iconSize],
                                iconAnchor: [iconSize/2, iconSize/2],
                                popupAnchor: [0, -iconSize/2]
                            });
                            
                            // Create marker - MUST explicitly set icon to prevent default triangle marker
                            const marker = L.marker([lat, lon], { 
                                icon: icon,
                                zIndexOffset: mag * 100,
                                riseOnHover: true
                            });
                            
                            // Force set icon again to ensure it's applied
                            marker.setIcon(icon);
                            
                            // Check if earthquake is near known volcanoes (especially Albay/Mayon)
                            const isNearVolcano = checkIfNearVolcano(lat, lon);
                            
                            // Popup with earthquake details
                            const timeAgo = getTimeAgo(time);
                            const volcanoWarning = isNearVolcano ? 
                                '<div style="background:#FF9800;color:white;padding:0.5rem;border-radius:4px;margin-bottom:10px;font-weight:bold;"><i class="fas fa-volcano"></i> Near Active Volcano - May Require Attention</div>' : '';
                            
                            marker.bindPopup(`
                                <div style="min-width:200px;">
                                    ${volcanoWarning}
                                    <h3 style="margin:0 0 10px 0;color:${color};">
                                        <i class="fas fa-mountain"></i> Magnitude ${mag.toFixed(1)}
                                    </h3>
                                    <p style="margin:5px 0;"><strong>Location:</strong> ${place}</p>
                                    <p style="margin:5px 0;"><strong>Depth:</strong> ${depth.toFixed(1)} km</p>
                                    <p style="margin:5px 0;"><strong>Time:</strong> ${time.toLocaleString()}</p>
                                    <p style="margin:5px 0;font-size:0.9em;color:#666;">${timeAgo}</p>
                                    ${isNearVolcano ? '<p style="margin:5px 0;font-size:0.85em;color:#FF9800;"><i class="fas fa-exclamation-triangle"></i> This earthquake may be related to volcanic activity</p>' : ''}
                                    <p style="margin:10px 0 0 0;font-size:0.85em;color:#999;">
                                        <i class="fas fa-info-circle"></i> Data from USGS Earthquake Hazards Program
                                    </p>
                                </div>
                            `);
                            
                            // Add marker to map and verify it was added
                            // Verify icon is set before adding to map
                            if (!marker.options.icon) {
                                console.error('ERROR: Marker icon not set!', marker);
                                marker.setIcon(icon);
                            }
                            
                            marker.addTo(map);
                            earthquakeMarkers.push(marker);
                            
                            // Verify marker was added correctly (only log if there's an actual mismatch)
                            setTimeout(() => {
                                const markerIcon = marker.options.icon;
                                if (markerIcon && markerIcon.options) {
                                    const iconHtml = markerIcon.options.html || '';
                                    // Extract color more accurately (handle !important and spaces)
                                    const colorMatch = iconHtml.match(/background-color:\s*([#\w]+)/);
                                    if (colorMatch) {
                                        const actualColor = colorMatch[1].trim();
                                        const expectedColor = color.trim();
                                        if (actualColor !== expectedColor) {
                                            console.warn(`⚠️ Color mismatch for marker ${earthquakeMarkers.length}: Expected ${expectedColor}, Got ${actualColor}`);
                                        }
                                    }
                                }
                            }, 100);
                            
                            // Debug: Log marker creation
                            if (earthquakeMarkers.length <= 5) { // Log first 5 for debugging
                                console.log(`✅ Marker ${earthquakeMarkers.length}: Mag ${mag.toFixed(1)} at ${lat.toFixed(4)}, ${lon.toFixed(4)} - Color: ${color}, Size: ${iconSize}px`);
                            }
                        });
                        
                        // Update statistics immediately
                        updateStatistics(data.features);
                        
                        // Show info panel
                        showEarthquakeInfo(data.features.length);
                        
                        // Trigger Quezon City risk update
                        updateQuezonCityRisk(data.features);
                        
                        // Debug: Verify markers are on map
                        console.log(`✅ Total markers created: ${earthquakeMarkers.length}`);
                        let markerCount = 0;
                        map.eachLayer(layer => {
                            if (layer instanceof L.Marker) {
                                markerCount++;
                            }
                        });
                        console.log(`✅ Markers on map: ${markerCount}`);
                        
                        // Show marker summary by color
                        const colorCounts = {
                            green: earthquakeMarkers.filter(m => {
                                const icon = m.options.icon;
                                return icon && icon.options.html.includes('#4CAF50');
                            }).length,
                            yellow: earthquakeMarkers.filter(m => {
                                const icon = m.options.icon;
                                return icon && icon.options.html.includes('#FFC107');
                            }).length,
                            orange: earthquakeMarkers.filter(m => {
                                const icon = m.options.icon;
                                return icon && icon.options.html.includes('#FF9800');
                            }).length,
                            red: earthquakeMarkers.filter(m => {
                                const icon = m.options.icon;
                                return icon && icon.options.html.includes('#FF5722');
                            }).length
                        };
                        console.log('Marker colors:', colorCounts);
                        
                        // Force map refresh to ensure markers are visible
                        setTimeout(() => {
                            if (map) {
                                map.invalidateSize();
                                // Pan slightly to trigger marker visibility
                                const currentCenter = map.getCenter();
                                map.setView(currentCenter, map.getZoom(), { animate: false });
                                
                                // Verify markers are still there after refresh
                                setTimeout(() => {
                                    let afterRefreshCount = 0;
                                    map.eachLayer(layer => {
                                        if (layer instanceof L.Marker) afterRefreshCount++;
                                    });
                                    console.log(`✅ Markers after refresh: ${afterRefreshCount}`);
                                    if (afterRefreshCount === 0 && earthquakeMarkers.length > 0) {
                                        console.error('❌ ERROR: Markers disappeared after refresh! Re-adding...');
                                        earthquakeMarkers.forEach(marker => {
                                            if (!map.hasLayer(marker)) {
                                                marker.addTo(map);
                                            }
                                        });
                                    }
                                }, 100);
                            }
                        }, 500);
                        
                        // Check for new earthquakes and trigger AI analysis if significant
                        checkForNewEarthquakes(data.features);
                        
                        // Auto-trigger AI analysis for significant earthquakes
                        const significantEarthquakes = data.features.filter(f => (f.properties.mag || 0) >= 4.0);
                        if (significantEarthquakes.length > 0 && realtimeEnabled) {
                            // Auto-analyze if there are significant earthquakes
                            setTimeout(() => analyzeEarthquakeImpact(data.features), 2000);
                        }
                    } else {
                        // No earthquakes found - but DON'T clear existing markers
                        console.warn('⚠️ No earthquakes found in response, keeping existing markers');
                        console.log(`Current markers on map: ${earthquakeMarkers.length}`);
                        if (earthquakeData.length === 0) {
                            // Only show alert if we have no data at all
                            updateStatistics([]);
                            console.error('❌ No earthquake data available. Check API connection.');
                        }
                    }
                    
                    // Reset refresh button
                    if (refreshBtn) {
                        refreshBtn.innerHTML = '<i class="fas fa-sync-alt"></i> <span>Refresh</span>';
                        refreshBtn.disabled = false;
                    }
                })
                .catch(error => {
                    console.error('Error loading earthquake data:', error);
                    console.error('Error details:', {
                        message: error.message,
                        stack: error.stack,
                        url: url
                    });
                    
                    // Show error notification instead of alert
                    const errorMsg = error.message || 'Could not load earthquake data. Please check your internet connection.';
                    showErrorNotification(errorMsg);
                    
                    // Reset refresh button
                    const refreshBtn = document.getElementById('refreshBtn');
                    if (refreshBtn) {
                        refreshBtn.innerHTML = '<i class="fas fa-sync-alt"></i> <span>Refresh</span>';
                        refreshBtn.disabled = false;
                    }
                });
        }
        
        // Update statistics
        function updateStatistics(features) {
            const totalEvents = features.length;
            const majorEvents = features.filter(f => (f.properties.mag || 0) >= 5.0).length;
            
            // Sort by time (most recent first) to get the latest earthquake
            const sortedByTime = [...features].sort((a, b) => (b.properties.time || 0) - (a.properties.time || 0));
            const latest = sortedByTime.length > 0 ? sortedByTime[0] : null;
            const latestMag = latest ? (latest.properties.mag || 0).toFixed(1) : '-';
            
            document.getElementById('totalEvents').textContent = totalEvents;
            document.getElementById('majorEvents').textContent = majorEvents;
            document.getElementById('latestMagnitude').textContent = latestMag;
            
            // Update Last Earthquake card
            if (latest) {
                const lastTime = new Date(latest.properties.time);
                const timeAgo = getTimeAgo(lastTime);
                const place = latest.properties.place || 'Unknown';
                
                document.getElementById('lastEarthquakeTime').textContent = timeAgo;
                document.getElementById('lastEarthquakeLocation').innerHTML = `<i class="fas fa-map-marker-alt"></i> ${place}`;
                document.getElementById('lastEarthquakeMagnitude').innerHTML = `<strong>Magnitude:</strong> ${latestMag}`;
            } else {
                document.getElementById('lastEarthquakeTime').textContent = 'No data';
                document.getElementById('lastEarthquakeLocation').textContent = '-';
                document.getElementById('lastEarthquakeMagnitude').textContent = '-';
            }
            
            const updateTime = new Date().toLocaleTimeString();
            document.getElementById('lastUpdate').textContent = realtimeEnabled ? `${updateTime} (Real-time)` : updateTime;
            
        }
        
        // Update Quezon City Risk Assessment
        function updateQuezonCityRisk(features) {
            const qcLat = 14.6488;
            const qcLon = 121.0509;
            
            // Find earthquakes within 200km of Quezon City
            const nearbyEarthquakes = features.filter(f => {
                const [lon, lat] = f.geometry.coordinates;
                const distance = calculateDistanceKm(lat, lon, qcLat, qcLon);
                return distance <= 200;
            });
            
            // Find the most significant nearby earthquake
            const significantNearby = nearbyEarthquakes
                .filter(f => (f.properties.mag || 0) >= 4.0)
                .sort((a, b) => (b.properties.mag || 0) - (a.properties.mag || 0));
            
            const riskPanel = document.getElementById('qcRiskAlertPanel');
            const riskContent = document.getElementById('qcRiskContent');
            const riskBadge = document.getElementById('qcRiskBadge');
            const riskLevel = document.getElementById('qcRiskLevel');
            
            if (significantNearby.length > 0) {
                const closest = significantNearby[0];
                const [lon, lat] = closest.geometry.coordinates;
                const distance = calculateDistanceKm(lat, lon, qcLat, qcLon);
                const mag = closest.properties.mag || 0;
                
                // Determine risk level
                let risk = 'low';
                let riskText = 'LOW RISK';
                let travelSafety = 'safe';
                let travelText = 'SAFE TO TRAVEL';
                
                if (distance < 50 && mag >= 5.0) {
                    risk = 'critical';
                    riskText = 'CRITICAL';
                    travelSafety = 'unsafe';
                    travelText = 'AVOID TRAVEL';
                } else if (distance < 100 && mag >= 4.5) {
                    risk = 'high';
                    riskText = 'HIGH RISK';
                    travelSafety = 'caution';
                    travelText = 'TRAVEL WITH CAUTION';
                } else if (distance < 150 && mag >= 4.0) {
                    risk = 'moderate';
                    riskText = 'MODERATE RISK';
                    travelSafety = 'caution';
                    travelText = 'TRAVEL WITH CAUTION';
                }
                
                riskBadge.className = `qc-risk-badge ${risk}`;
                riskLevel.textContent = riskText;
                
                // Determine landslide risk
                let landslideRisk = 'Low';
                if (distance < 50 && mag >= 5.0) {
                    landslideRisk = 'High - Possible landslides in hilly areas';
                } else if (distance < 100 && mag >= 4.5) {
                    landslideRisk = 'Moderate - Monitor hilly and elevated areas';
                } else {
                    landslideRisk = 'Low - Minimal landslide risk';
                }
                
                riskContent.innerHTML = `
                    <div class="qc-risk-item">
                        <div class="qc-risk-item-icon">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        <div class="qc-risk-item-content">
                            <h4>Current Risk Status</h4>
                            <p>Magnitude ${mag.toFixed(1)} earthquake detected ${distance.toFixed(1)} km from Quezon City. ${risk === 'critical' || risk === 'high' ? 'Immediate monitoring recommended.' : 'No immediate threat detected.'}</p>
                        </div>
                    </div>
                    <div class="qc-risk-item">
                        <div class="qc-risk-item-icon">
                            <i class="fas fa-mountain"></i>
                        </div>
                        <div class="qc-risk-item-content">
                            <h4>Landslide Risk Assessment</h4>
                            <p><strong>${landslideRisk}</strong></p>
                            ${risk === 'high' || risk === 'critical' ? '<p style="margin-top: 0.5rem; font-size: 0.85rem;">Avoid hilly areas, slopes, and elevated regions. Monitor for ground movement.</p>' : ''}
                        </div>
                    </div>
                    <div class="qc-risk-item">
                        <div class="qc-risk-item-icon">
                            <i class="fas fa-car"></i>
                        </div>
                        <div class="qc-risk-item-content">
                            <h4>Travel Safety</h4>
                            <p><span class="travel-safety-badge ${travelSafety}">${travelText}</span></p>
                            ${travelSafety === 'unsafe' ? '<p style="margin-top: 0.5rem; font-size: 0.85rem;">Avoid non-essential travel. Stay indoors if possible.</p>' : travelSafety === 'caution' ? '<p style="margin-top: 0.5rem; font-size: 0.85rem;">Exercise caution when traveling. Avoid bridges and elevated structures.</p>' : '<p style="margin-top: 0.5rem; font-size: 0.85rem;">Normal travel conditions. Stay alert for aftershocks.</p>'}
                        </div>
                    </div>
                    <div class="qc-risk-item">
                        <div class="qc-risk-item-icon">
                            <i class="fas fa-info-circle"></i>
                        </div>
                        <div class="qc-risk-item-content">
                            <h4>Recommendations</h4>
                            <p>${risk === 'critical' || risk === 'high' ? 'Stay indoors, avoid elevators, and prepare emergency supplies. Monitor official PHIVOLCS alerts.' : risk === 'moderate' ? 'Stay alert for aftershocks. Review emergency preparedness plans.' : 'Continue normal activities. Stay informed about seismic activity.'}</p>
                        </div>
                    </div>
                `;
                
                // Add critical alert animation if high risk
                if (risk === 'critical' || risk === 'high') {
                    riskPanel.classList.add('critical-alert');
                } else {
                    riskPanel.classList.remove('critical-alert');
                }
            } else {
                // No significant nearby earthquakes
                riskBadge.className = 'qc-risk-badge low';
                riskLevel.textContent = 'LOW RISK';
                riskContent.innerHTML = `
                    <div class="qc-risk-item">
                        <div class="qc-risk-item-icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="qc-risk-item-content">
                            <h4>Current Status: Safe</h4>
                            <p>No significant earthquakes detected within 200km of Quezon City in the last 30 days.</p>
                        </div>
                    </div>
                    <div class="qc-risk-item">
                        <div class="qc-risk-item-icon">
                            <i class="fas fa-mountain"></i>
                        </div>
                        <div class="qc-risk-item-content">
                            <h4>Landslide Risk</h4>
                            <p><strong>Low</strong> - No immediate landslide concerns.</p>
                        </div>
                    </div>
                    <div class="qc-risk-item">
                        <div class="qc-risk-item-icon">
                            <i class="fas fa-car"></i>
                        </div>
                        <div class="qc-risk-item-content">
                            <h4>Travel Safety</h4>
                            <p><span class="travel-safety-badge safe">SAFE TO TRAVEL</span></p>
                            <p style="margin-top: 0.5rem; font-size: 0.85rem;">Normal travel conditions. Continue regular activities.</p>
                        </div>
                    </div>
                `;
                riskPanel.classList.remove('critical-alert');
            }
        }
        
        // Show earthquake information panel
        function showEarthquakeInfo(count) {
            const infoBox = document.getElementById('earthquakeInfo');
            const infoContent = document.getElementById('earthquakeInfoContent');
            
            if (infoBox && infoContent) {
                infoBox.style.display = 'block';
                infoContent.innerHTML = `
                    <p><strong>Total Events:</strong> ${count} earthquakes (last 30 days)</p>
                    <p><strong>Magnitude Range:</strong> ${minMagnitude}+</p>
                    <p><strong>Region:</strong> Philippines</p>
                    <div class="earthquake-legend">
                        <div class="legend-item"><span class="legend-color" style="background:#4CAF50"></span> Minor (2.5-4.0)</div>
                        <div class="legend-item"><span class="legend-color" style="background:#FFC107"></span> Light (4.0-4.5)</div>
                        <div class="legend-item"><span class="legend-color" style="background:#FF9800"></span> Moderate (4.5-5.0)</div>
                        <div class="legend-item"><span class="legend-color" style="background:#FF5722"></span> Major (5.0+)</div>
                    </div>
                    <p style="margin-top:10px;font-size:0.85em;color:#666;">
                        <i class="fas fa-info-circle"></i> Data sourced from USGS Earthquake Hazards Program
                    </p>
                `;
            }
        }
        
        // Show filter dialog
        function showFilterDialog() {
            const newMinMag = prompt('Enter minimum magnitude (2.5-8.0):', minMagnitude);
            if (newMinMag !== null) {
                const mag = parseFloat(newMinMag);
                if (!isNaN(mag) && mag >= 2.5 && mag <= 8.0) {
                    minMagnitude = mag;
                    loadEarthquakeData();
                } else {
                    alert('Please enter a valid magnitude between 2.5 and 8.0');
                }
            }
        }
        
        // Check if earthquake is near known active volcanoes in Philippines
        function checkIfNearVolcano(lat, lon) {
            // Known active volcanoes in Philippines with their coordinates
            const volcanoes = [
                { name: 'Mayon Volcano', lat: 13.2571, lon: 123.6854, radius: 50 }, // Albay
                { name: 'Taal Volcano', lat: 14.0100, lon: 121.0000, radius: 30 },
                { name: 'Pinatubo', lat: 15.1429, lon: 120.3497, radius: 40 },
                { name: 'Kanlaon', lat: 10.4111, lon: 123.1322, radius: 30 },
                { name: 'Bulusan', lat: 12.7667, lon: 124.0500, radius: 30 },
                { name: 'Hibok-Hibok', lat: 9.2033, lon: 124.6733, radius: 25 }
            ];
            
            for (const volcano of volcanoes) {
                const distance = calculateDistanceKm(lat, lon, volcano.lat, volcano.lon);
                if (distance <= volcano.radius) {
                    console.log(`Earthquake detected near ${volcano.name} (${distance.toFixed(1)} km away)`);
                    return true;
                }
            }
            return false;
        }
        
        // Calculate distance between two coordinates in kilometers
        function calculateDistanceKm(lat1, lon1, lat2, lon2) {
            const R = 6371; // Earth's radius in km
            const dLat = (lat2 - lat1) * Math.PI / 180;
            const dLon = (lon2 - lon1) * Math.PI / 180;
            const a = Math.sin(dLat/2) * Math.sin(dLat/2) +
                      Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
                      Math.sin(dLon/2) * Math.sin(dLon/2);
            const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
            return R * c;
        }
        
        // Helper function to get time ago string
        function getTimeAgo(date) {
            const seconds = Math.floor((new Date() - date) / 1000);
            if (seconds < 60) return 'Just now';
            const minutes = Math.floor(seconds / 60);
            if (minutes < 60) return `${minutes} minute${minutes > 1 ? 's' : ''} ago`;
            const hours = Math.floor(minutes / 60);
            if (hours < 24) return `${hours} hour${hours > 1 ? 's' : ''} ago`;
            const days = Math.floor(hours / 24);
            return `${days} day${days > 1 ? 's' : ''} ago`;
        }
        
        // Focus on Quezon City
        function focusQuezonCity() {
            if (map) {
                map.flyTo([14.6488, 121.0509], 12, {
                    duration: 1.5,
                    easeLinearity: 0.25
                });
            }
        }
        
        // Real-time update functions
        function startRealtimeUpdates() {
            if (realtimeInterval) {
                clearInterval(realtimeInterval);
            }
            if (recentCheckInterval) {
                clearInterval(recentCheckInterval);
            }
            
            if (realtimeEnabled) {
                // Full data refresh every 2 minutes (120000 ms) - for historical data
                realtimeInterval = setInterval(() => {
                    console.log('Auto-refreshing full earthquake data...');
                    loadEarthquakeData();
                }, 120000); // 2 minutes
                
                // Check for NEW earthquakes every 30 seconds (30000 ms) - TRUE REAL-TIME
                recentCheckInterval = setInterval(() => {
                    console.log('Checking for new earthquakes (real-time)...');
                    checkRecentEarthquakes();
                }, 30000); // 30 seconds - TRUE REAL-TIME
                
                // Initial recent check - run immediately and then after 5 seconds
                checkRecentEarthquakes(); // Run immediately
                setTimeout(() => checkRecentEarthquakes(), 5000);
                
                updateRealtimeStatus(true);
            }
        }
        
        function stopRealtimeUpdates() {
            if (realtimeInterval) {
                clearInterval(realtimeInterval);
                realtimeInterval = null;
            }
            if (recentCheckInterval) {
                clearInterval(recentCheckInterval);
                recentCheckInterval = null;
            }
            updateRealtimeStatus(false);
        }
        
        // Check for recent earthquakes (last 2 hours) - TRUE REAL-TIME
        function checkRecentEarthquakes() {
            // Extended bounds to include Albay/Bicol region and surrounding areas
            const philippinesBounds = {
                minLat: 4.0,  // Extended south
                maxLat: 21.5, // Extended north
                minLon: 115.5, // Extended west
                maxLon: 127.5  // Extended east (includes Albay/Bicol region)
            };
            
            // Check last 2 hours for NEW earthquakes (extended window to catch all recent events)
            const startTime = new Date();
            startTime.setHours(startTime.getHours() - 2); // Extended to 2 hours
            const endTime = new Date();
            
            const url = `https://earthquake.usgs.gov/fdsnws/event/1/query?format=geojson&starttime=${startTime.toISOString()}&endtime=${endTime.toISOString()}&minmagnitude=${minMagnitude}&maxlatitude=${philippinesBounds.maxLat}&minlatitude=${philippinesBounds.minLat}&maxlongitude=${philippinesBounds.maxLon}&minlongitude=${philippinesBounds.minLon}`;
            
            console.log('Real-time check: Looking for earthquakes from', startTime.toISOString(), 'to', endTime.toISOString());
            
            fetch(url)
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    const count = data.metadata?.count || 0;
                    console.log(`Real-time check response: ${count} earthquake${count !== 1 ? 's' : ''} found in last 2 hours`);
                    
                    if (!data || !data.features) {
                        console.warn('No features in response:', data);
                        return;
                    }
                    
                    // Note: 0 earthquakes in last 2 hours is normal - the main display shows last 30 days
                    if (count === 0) {
                        console.log('ℹ️ No new earthquakes in the last 2 hours (this is normal - main display shows last 30 days)');
                        return;
                    }
                    if (data.features && data.features.length > 0) {
                        // Generate IDs using same method as main load
                        const currentIds = new Set(data.features.map(f => {
                            if (f.id) return String(f.id);
                            if (f.properties.code) return String(f.properties.code);
                            if (f.properties.ids) return String(f.properties.ids).split(',')[0];
                            const coords = f.geometry.coordinates;
                            return `${f.properties.time}_${coords[0].toFixed(4)}_${coords[1].toFixed(4)}_${(coords[2] || 0).toFixed(1)}`;
                        }));
                        
                        // Find new earthquakes
                        const newEarthquakes = data.features.filter(f => {
                            let id;
                            if (f.id) id = String(f.id);
                            else if (f.properties.code) id = String(f.properties.code);
                            else if (f.properties.ids) id = String(f.properties.ids).split(',')[0];
                            else {
                                const coords = f.geometry.coordinates;
                                id = `${f.properties.time}_${coords[0].toFixed(4)}_${coords[1].toFixed(4)}_${(coords[2] || 0).toFixed(1)}`;
                            }
                            const isNew = !lastRecentEarthquakeIds.has(id);
                            if (isNew) {
                                console.log('New earthquake detected:', {
                                    id: id,
                                    magnitude: f.properties.mag,
                                    place: f.properties.place,
                                    time: new Date(f.properties.time).toISOString()
                                });
                            }
                            return isNew;
                        });
                        
                        if (newEarthquakes.length > 0) {
                            console.log(`🚨 NEW EARTHQUAKES DETECTED: ${newEarthquakes.length}`);
                            
                            // Add new markers to map (DO NOT clear existing markers)
                            newEarthquakes.forEach(feature => {
                                // Check if marker already exists to avoid duplicates
                                const existingMarker = earthquakeMarkers.find(m => {
                                    const markerLat = m.getLatLng().lat;
                                    const markerLon = m.getLatLng().lng;
                                    const [eqLon, eqLat] = feature.geometry.coordinates;
                                    return Math.abs(markerLat - eqLat) < 0.001 && Math.abs(markerLon - eqLon) < 0.001;
                                });
                                
                                if (existingMarker) {
                                    console.log('Marker already exists for this earthquake, skipping');
                                    return;
                                }
                                const [lon, lat] = feature.geometry.coordinates;
                                const mag = feature.properties.mag || 0;
                                const place = feature.properties.place || 'Unknown';
                                const time = new Date(feature.properties.time);
                                const depth = feature.geometry.coordinates[2] || 0;
                                
                                // Determine marker color based on magnitude
                                let color = '#4CAF50'; // Green - minor (2.5-4.0)
                                if (mag >= 5.0) color = '#FF5722'; // Red - major (5.0+)
                                else if (mag >= 4.5) color = '#FF9800'; // Orange - moderate (4.5-5.0)
                                else if (mag >= 4.0) color = '#FFC107'; // Yellow - light (4.0-4.5)
                                
                                // Create custom icon with pulsing animation for new earthquakes
                                const iconSize = Math.max(25, Math.min(60, mag * 10)); // Larger size
                                const borderWidth = mag >= 5.0 ? 4 : 3;
                                const iconHtml = `<div style="background-color:${color} !important;width:${iconSize}px !important;height:${iconSize}px !important;border-radius:50% !important;border:${borderWidth}px solid white !important;box-shadow:0 0 12px ${color}, 0 0 24px ${color}, 0 2px 8px rgba(0,0,0,0.5) !important;animation:pulse 2s infinite !important;position:relative !important;z-index:1000 !important;display:block !important;"></div>`;
                                
                                const icon = L.divIcon({
                                    className: 'earthquake-marker-custom',
                                    html: iconHtml,
                                    iconSize: [iconSize, iconSize],
                                    iconAnchor: [iconSize/2, iconSize/2],
                                    popupAnchor: [0, -iconSize/2]
                                });
                                
                                // Create marker with higher z-index
                                const marker = L.marker([lat, lon], { 
                                    icon: icon,
                                    zIndexOffset: mag * 100,
                                    riseOnHover: true
                                });
                                
                                // Force set icon to ensure it's applied
                                marker.setIcon(icon);
                                
                                // Check if earthquake is near known volcanoes
                                const isNearVolcano = checkIfNearVolcano(lat, lon);
                                const volcanoWarning = isNearVolcano ? 
                                    '<div style="background:#FF9800;color:white;padding:0.5rem;border-radius:4px;margin-bottom:10px;font-weight:bold;"><i class="fas fa-volcano"></i> Near Active Volcano - May Require Attention</div>' : '';
                                
                                // Popup with earthquake details
                                const timeAgo = getTimeAgo(time);
                                marker.bindPopup(`
                                    <div style="min-width:200px;">
                                        <div style="background:${color};color:white;padding:0.5rem;border-radius:4px;margin-bottom:10px;font-weight:bold;">
                                            🆕 NEW EARTHQUAKE
                                        </div>
                                        ${volcanoWarning}
                                        <h3 style="margin:0 0 10px 0;color:${color};">
                                            <i class="fas fa-mountain"></i> Magnitude ${mag.toFixed(1)}
                                        </h3>
                                        <p style="margin:5px 0;"><strong>Location:</strong> ${place}</p>
                                        <p style="margin:5px 0;"><strong>Depth:</strong> ${depth.toFixed(1)} km</p>
                                        <p style="margin:5px 0;"><strong>Time:</strong> ${time.toLocaleString()}</p>
                                        <p style="margin:5px 0;font-size:0.9em;color:#666;">${timeAgo}</p>
                                        ${isNearVolcano ? '<p style="margin:5px 0;font-size:0.85em;color:#FF9800;"><i class="fas fa-exclamation-triangle"></i> This earthquake may be related to volcanic activity</p>' : ''}
                                        <p style="margin:10px 0 0 0;font-size:0.85em;color:#999;">
                                            <i class="fas fa-info-circle"></i> Data from USGS Earthquake Hazards Program
                                        </p>
                                    </div>
                                `);
                                
                                marker.addTo(map);
                                earthquakeMarkers.push(marker);
                                
                                // Add to earthquake data (avoid duplicates)
                                const existsInData = earthquakeData.some(eq => {
                                    const [eqLon, eqLat] = eq.geometry.coordinates;
                                    const [newLon, newLat] = feature.geometry.coordinates;
                                    return Math.abs(eqLat - newLat) < 0.001 && Math.abs(eqLon - newLon) < 0.001 && 
                                           Math.abs(eq.properties.time - feature.properties.time) < 1000;
                                });
                                
                                if (!existsInData) {
                                    earthquakeData.push(feature);
                                }
                                
                                // Auto-open popup for significant earthquakes
                                if (mag >= 4.0) {
                                    setTimeout(() => marker.openPopup(), 1000);
                                }
                            });
                            
                            // Update statistics with ALL earthquakes (existing + new)
                        updateStatistics(earthquakeData);
                        
                        // Update Quezon City risk assessment immediately
                        updateQuezonCityRisk(earthquakeData);
                        
                        // Update last earthquake info immediately
                        if (newEarthquakes.length > 0) {
                            const latestNew = newEarthquakes.sort((a, b) => (b.properties.time || 0) - (a.properties.time || 0))[0];
                            const lastTime = new Date(latestNew.properties.time);
                            const timeAgo = getTimeAgo(lastTime);
                            const place = latestNew.properties.place || 'Unknown';
                            const mag = (latestNew.properties.mag || 0).toFixed(1);
                            
                            document.getElementById('lastEarthquakeTime').textContent = timeAgo;
                            document.getElementById('lastEarthquakeLocation').innerHTML = `<i class="fas fa-map-marker-alt"></i> ${place}`;
                            document.getElementById('lastEarthquakeMagnitude').innerHTML = `<strong>Magnitude:</strong> ${mag}`;
                            
                            // Add visual highlight animation to last earthquake card
                            const lastEqCard = document.querySelector('.stat-card-last-earthquake');
                            if (lastEqCard) {
                                lastEqCard.style.animation = 'pulse-highlight 1s ease-out';
                                setTimeout(() => {
                                    lastEqCard.style.animation = '';
                                }, 1000);
                            }
                        }
                            
                            // Show notification
                            const significantNew = newEarthquakes.filter(f => (f.properties.mag || 0) >= 4.0);
                            if (significantNew.length > 0) {
                                showNewEarthquakeNotification(significantNew.length);
                                
                                // Auto-trigger AI analysis for significant new earthquakes
                                setTimeout(() => analyzeEarthquakeImpact(earthquakeData), 2000);
                            } else {
                                showNewEarthquakeNotification(newEarthquakes.length, false);
                            }
                            
                            // Update last update time
                            const updateEl = document.getElementById('lastUpdate');
                            if (updateEl) {
                                updateEl.textContent = new Date().toLocaleTimeString() + ' (Real-time)';
                            }
                        }
                        
                        // Update tracked IDs (merge with existing to preserve all tracked IDs)
                        currentIds.forEach(id => lastRecentEarthquakeIds.add(id));
                    }
                })
                .catch(error => {
                    console.error('Error checking recent earthquakes:', error);
                    console.error('Error details:', {
                        message: error.message,
                        stack: error.stack,
                        url: url
                    });
                });
        }
        
        function toggleRealtime() {
            realtimeEnabled = !realtimeEnabled;
            const btn = document.getElementById('realtimeToggleBtn');
            
            if (realtimeEnabled) {
                startRealtimeUpdates();
                btn.classList.add('active');
            } else {
                stopRealtimeUpdates();
                btn.classList.remove('active');
            }
        }
        
        function updateRealtimeStatus(enabled) {
            const statusEl = document.getElementById('realtimeStatus');
            const iconEl = document.querySelector('#realtimeToggleBtn i');
            const indicatorEl = document.getElementById('realtimeIndicator');
            
            if (enabled) {
                statusEl.textContent = 'Real-time ON';
                if (iconEl) {
                    iconEl.style.color = '#4CAF50';
                }
                if (indicatorEl) {
                    indicatorEl.innerHTML = '<i class="fas fa-circle" style="color: #4CAF50; font-size: 0.5rem;"></i> Real-time active (checks every 30s)';
                    indicatorEl.style.color = '#4CAF50';
                }
            } else {
                statusEl.textContent = 'Real-time OFF';
                if (iconEl) {
                    iconEl.style.color = '#999';
                }
                if (indicatorEl) {
                    indicatorEl.innerHTML = '<i class="fas fa-circle" style="color: #999; font-size: 0.5rem;"></i> Real-time disabled';
                    indicatorEl.style.color = '#999';
                }
            }
        }
        
        function checkForNewEarthquakes(features) {
            const currentCount = features.length;
            if (lastEarthquakeCount > 0 && currentCount > lastEarthquakeCount) {
                const newCount = currentCount - lastEarthquakeCount;
                console.log(`New earthquakes detected: ${newCount}`);
                
                // Show notification if significant
                const significantNew = features.slice(0, newCount).filter(f => (f.properties.mag || 0) >= 4.0);
                if (significantNew.length > 0) {
                    showNewEarthquakeNotification(significantNew.length);
                }
            }
            lastEarthquakeCount = currentCount;
            lastUpdateTime = new Date();
        }
        
        function showErrorNotification(message) {
            const notification = document.createElement('div');
            notification.className = 'earthquake-notification';
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: #F44336;
                color: white;
                padding: 1rem 1.5rem;
                border-radius: 8px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.3);
                z-index: 10000;
                animation: slideIn 0.3s ease-out;
                max-width: 300px;
            `;
            notification.innerHTML = `
                <div style="display: flex; align-items: center; gap: 0.5rem;">
                    <i class="fas fa-exclamation-circle" style="font-size: 1.5rem;"></i>
                    <div>
                        <strong>Error Loading Data</strong>
                        <div style="font-size: 0.9em; margin-top: 0.25rem;">${message}</div>
                    </div>
                </div>
            `;
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.style.animation = 'slideOut 0.3s ease-out';
                setTimeout(() => notification.remove(), 5000);
            }, 5000);
        }
        
        function showNewEarthquakeNotification(count, isSignificant = true) {
            // Remove any existing notifications
            const existing = document.querySelector('.earthquake-notification');
            if (existing) existing.remove();
            
            // Create a temporary notification
            const notification = document.createElement('div');
            notification.className = 'earthquake-notification';
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: ${isSignificant ? '#FF5722' : '#2196F3'};
                color: white;
                padding: 1rem 1.5rem;
                border-radius: 8px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.3);
                z-index: 10000;
                animation: slideIn 0.3s ease-out;
                cursor: pointer;
                max-width: 300px;
            `;
            notification.innerHTML = `
                <div style="display: flex; align-items: center; gap: 0.5rem;">
                    <i class="fas fa-${isSignificant ? 'exclamation-triangle' : 'info-circle'}" style="font-size: 1.5rem;"></i>
                    <div>
                        <strong>${count} New Earthquake${count > 1 ? 's' : ''} Detected!</strong>
                        <div style="font-size: 0.9em; margin-top: 0.25rem;">
                            ${isSignificant ? '🚨 Significant event - AI analysis available' : 'Real-time update'}
                        </div>
                        <div style="font-size: 0.8em; margin-top: 0.25rem; opacity: 0.9;">
                            <i class="fas fa-clock"></i> ${new Date().toLocaleTimeString()}
                        </div>
                    </div>
                </div>
            `;
            
            // Click to show AI analysis
            if (isSignificant) {
                notification.onclick = () => {
                    showAIAnalytics();
                    notification.remove();
                };
            }
            
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.style.animation = 'slideOut 0.3s ease-out';
                setTimeout(() => notification.remove(), isSignificant ? 8000 : 5000);
            }, isSignificant ? 8000 : 5000);
        }
        
        // AI Analytics Functions
        function showAIAnalytics() {
            const panel = document.getElementById('aiAnalyticsPanel');
            if (earthquakeData.length === 0) {
                alert('No earthquake data available for analysis. Please load earthquake data first.');
                return;
            }
            
            panel.style.display = 'block';
            analyzeEarthquakeImpact(earthquakeData);
        }
        
        function analyzeEarthquakeImpact(earthquakes) {
            const contentEl = document.getElementById('aiAnalyticsContent');
            contentEl.innerHTML = `
                <div style="text-align: center; padding: 2rem;">
                    <i class="fas fa-spinner fa-spin" style="font-size: 2rem; color: var(--primary-color-1);"></i>
                    <p>Analyzing earthquake impacts specifically for Quezon City...</p>
                    <p style="font-size: 0.9em; color: var(--text-secondary-1); margin-top: 0.5rem;">Focusing on earthquakes affecting Quezon City area</p>
                </div>
            `;
            
            // Quezon City coordinates
            const qcLat = 14.6488;
            const qcLon = 121.0509;
            
            // Filter and prioritize earthquakes near Quezon City (within 300km)
            // Sort by: 1) Distance from QC (closer first), 2) Magnitude (higher first)
            const earthquakesWithDistance = earthquakes.map(feature => {
                const [lon, lat] = feature.geometry.coordinates;
                const distance = calculateDistanceKm(lat, lon, qcLat, qcLon);
                return {
                    ...feature,
                    distanceFromQC: distance
                };
            });
            
            // Prioritize earthquakes within 300km of Quezon City
            const nearbyEarthquakes = earthquakesWithDistance
                .filter(eq => eq.distanceFromQC <= 300)
                .sort((a, b) => {
                    // First sort by distance (closer first), then by magnitude (higher first)
                    if (Math.abs(a.distanceFromQC - b.distanceFromQC) < 10) {
                        return (b.properties.mag || 0) - (a.properties.mag || 0);
                    }
                    return a.distanceFromQC - b.distanceFromQC;
                });
            
            // If we have nearby earthquakes, use them; otherwise use all but prioritize by magnitude
            const earthquakesToAnalyze = nearbyEarthquakes.length > 0 
                ? nearbyEarthquakes.slice(0, 15) // Top 15 nearby earthquakes
                : earthquakesWithDistance
                    .sort((a, b) => (b.properties.mag || 0) - (a.properties.mag || 0))
                    .slice(0, 10); // Top 10 by magnitude if none nearby
            
            // Prepare earthquake data for API - focusing on Quezon City impact
            const eqData = earthquakesToAnalyze.map(feature => ({
                lat: feature.geometry.coordinates[1],
                lon: feature.geometry.coordinates[0],
                magnitude: feature.properties.mag || 0,
                depth: feature.geometry.coordinates[2] || 0,
                place: feature.properties.place || 'Unknown',
                time: feature.properties.time,
                distanceFromQC: feature.distanceFromQC || calculateDistanceKm(
                    feature.geometry.coordinates[1],
                    feature.geometry.coordinates[0],
                    qcLat,
                    qcLon
                )
            }));
            
            fetch('../api/earthquake-ai-analytics.php?action=analyze', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    earthquakes: eqData
                })
            })
            .then(async response => {
                const contentType = response.headers.get('content-type');
                let data;
                
                if (!response.ok) {
                    // Try to parse error response as JSON
                    if (contentType && contentType.includes('application/json')) {
                        try {
                            data = await response.json();
                            throw new Error(data.message || `HTTP error! status: ${response.status}`);
                        } catch (e) {
                            if (e instanceof Error && e.message.includes('HTTP error')) {
                                throw e;
                            }
                            throw new Error(`HTTP error! status: ${response.status}`);
                        }
                    } else {
                        // Non-JSON error response
                        const text = await response.text();
                        throw new Error(`Server error (${response.status}): ${text.substring(0, 200)}`);
                    }
                }
                
                // Parse successful response
                if (contentType && contentType.includes('application/json')) {
                    data = await response.json();
                } else {
                    throw new Error('Invalid response format from server');
                }
                
                return data;
            })
            .then(data => {
                if (data.success && data.analysis) {
                    displayAIAnalysis(data.analysis);
                } else {
                    contentEl.innerHTML = `
                        <div style="padding: 1rem; color: var(--error-color, #F44336);">
                            <i class="fas fa-exclamation-circle"></i> ${data.message || 'Failed to generate analysis'}
                            <p style="font-size: 0.9em; margin-top: 0.5rem;">Please ensure Gemini API key is configured in Automated Warnings → AI Warning Settings.</p>
                        </div>
                    `;
                }
            })
            .catch(error => {
                console.error('AI Analysis Error:', error);
                const errorMessage = error.message || 'Unknown error occurred';
                contentEl.innerHTML = `
                    <div style="padding: 1rem; color: var(--error-color, #F44336);">
                        <i class="fas fa-exclamation-circle"></i> Error: ${errorMessage}
                        <p style="font-size: 0.9em; margin-top: 0.5rem;">AI analysis is optional. Earthquake data is still displayed correctly.</p>
                        <p style="font-size: 0.85em; margin-top: 0.5rem; color: var(--text-secondary-1);">
                            If this error persists, check the server logs or ensure the API endpoint is accessible.
                        </p>
                    </div>
                `;
            });
        }
        
        function displayAIAnalysis(analysis) {
            const contentEl = document.getElementById('aiAnalyticsContent');
            
            // Handle raw response if JSON parsing failed
            if (analysis.raw_response || typeof analysis === 'string') {
                contentEl.innerHTML = `
                    <div style="padding: 1rem;">
                        <h4 style="margin-top: 0;">Analysis:</h4>
                        <div style="white-space: pre-wrap; line-height: 1.6; background: var(--card-bg-1); padding: 1rem; border-radius: 4px;">${typeof analysis === 'string' ? analysis : (analysis.overall_assessment || 'No analysis available')}</div>
                    </div>
                `;
                return;
            }
            
            // Ensure analysis is an object
            if (!analysis || typeof analysis !== 'object') {
                contentEl.innerHTML = `
                    <div style="padding: 1rem; color: var(--error-color, #F44336);">
                        <i class="fas fa-exclamation-circle"></i> Invalid analysis format received.
                    </div>
                `;
                return;
            }
            
            const riskLevel = analysis.risk_level || 'moderate';
            const riskClass = `risk-${riskLevel}`;
            
            let html = `
                <div style="margin-bottom: 1.5rem;">
                    <h3 style="margin: 0 0 0.5rem 0; display: flex; align-items: center;">
                        Overall Assessment
                        <span class="risk-badge ${riskClass}">${riskLevel.toUpperCase()}</span>
                    </h3>
                    <p style="margin: 0; line-height: 1.6;">${analysis.overall_assessment || 'No assessment available'}</p>
                </div>
            `;
            
            if (analysis.immediate_impacts && analysis.immediate_impacts.length > 0) {
                html += `
                    <div style="margin-bottom: 1.5rem;">
                        <h4 style="margin: 0 0 0.75rem 0; color: var(--primary-color-1);">
                            <i class="fas fa-bolt"></i> Immediate Impacts
                        </h4>
                        <ul style="margin: 0; padding-left: 1.5rem;">
                            ${analysis.immediate_impacts.map(impact => `<li style="margin: 0.5rem 0;">${impact}</li>`).join('')}
                        </ul>
                    </div>
                `;
            }
            
            if (analysis.potential_hazards && analysis.potential_hazards.length > 0) {
                html += `
                    <div style="margin-bottom: 1.5rem;">
                        <h4 style="margin: 0 0 0.75rem 0; color: var(--primary-color-1);">
                            <i class="fas fa-exclamation-triangle"></i> Potential Hazards for Quezon City
                        </h4>
                        ${analysis.potential_hazards.map(hazard => `
                            <div class="hazard-item">
                                <i class="fas fa-circle" style="font-size: 0.5rem; margin-right: 0.5rem;"></i>
                                ${hazard}
                            </div>
                        `).join('')}
                    </div>
                `;
            }
            
            if (analysis.affected_areas && analysis.affected_areas.length > 0) {
                html += `
                    <div style="margin-bottom: 1.5rem;">
                        <h4 style="margin: 0 0 0.75rem 0; color: var(--primary-color-1);">
                            <i class="fas fa-map-marker-alt"></i> Potentially Affected Areas in Quezon City
                        </h4>
                        <ul style="margin: 0; padding-left: 1.5rem;">
                            ${analysis.affected_areas.map(area => `<li style="margin: 0.5rem 0;">${area}</li>`).join('')}
                        </ul>
                    </div>
                `;
            }
            
            if (analysis.recommendations && analysis.recommendations.length > 0) {
                html += `
                    <div style="margin-bottom: 1.5rem;">
                        <h4 style="margin: 0 0 0.75rem 0; color: var(--primary-color-1);">
                            <i class="fas fa-lightbulb"></i> Recommendations
                        </h4>
                        ${analysis.recommendations.map(rec => `
                            <div class="recommendation-item">
                                <i class="fas fa-check-circle" style="color: #2196F3; margin-right: 0.5rem;"></i>
                                ${rec}
                            </div>
                        `).join('')}
                    </div>
                `;
            }
            
            if (analysis.distance_analysis) {
                html += `
                    <div style="margin-bottom: 1.5rem; padding: 1rem; background: var(--card-bg-1); border-radius: 4px; border-left: 4px solid var(--primary-color-1);">
                        <h4 style="margin: 0 0 0.5rem 0; color: var(--primary-color-1);">
                            <i class="fas fa-ruler"></i> Distance Analysis
                        </h4>
                        <p style="margin: 0; line-height: 1.6;">${analysis.distance_analysis}</p>
                    </div>
                `;
            }
            
            if (analysis.landslide_risk) {
                const landslideRiskLevel = analysis.landslide_risk.toLowerCase();
                let riskColor = '#4CAF50';
                if (landslideRiskLevel.includes('high') || landslideRiskLevel.includes('critical')) {
                    riskColor = '#F44336';
                } else if (landslideRiskLevel.includes('moderate')) {
                    riskColor = '#FF9800';
                }
                
                html += `
                    <div style="margin-bottom: 1.5rem; padding: 1rem; background: var(--card-bg-1); border-radius: 4px; border-left: 4px solid ${riskColor};">
                        <h4 style="margin: 0 0 0.5rem 0; color: ${riskColor};">
                            <i class="fas fa-mountain"></i> Landslide Risk Assessment
                        </h4>
                        <p style="margin: 0; line-height: 1.6;"><strong>${analysis.landslide_risk}</strong></p>
                        ${landslideRiskLevel.includes('high') || landslideRiskLevel.includes('moderate') ? 
                            '<p style="margin-top: 0.5rem; font-size: 0.9em; color: var(--text-secondary-1);">Avoid hilly areas, slopes, and elevated regions. Monitor for ground movement and signs of instability.</p>' : ''}
                    </div>
                `;
            }
            
            if (analysis.travel_safety) {
                const travelSafety = analysis.travel_safety.toLowerCase();
                let safetyClass = 'safe';
                let safetyText = 'SAFE TO TRAVEL';
                let safetyColor = '#4CAF50';
                
                if (travelSafety === 'unsafe' || travelSafety.includes('avoid')) {
                    safetyClass = 'unsafe';
                    safetyText = 'AVOID TRAVEL';
                    safetyColor = '#F44336';
                } else if (travelSafety === 'caution' || travelSafety.includes('caution')) {
                    safetyClass = 'caution';
                    safetyText = 'TRAVEL WITH CAUTION';
                    safetyColor = '#FFC107';
                }
                
                html += `
                    <div style="margin-bottom: 1.5rem; padding: 1rem; background: var(--card-bg-1); border-radius: 4px; border-left: 4px solid ${safetyColor};">
                        <h4 style="margin: 0 0 0.5rem 0; color: ${safetyColor};">
                            <i class="fas fa-car"></i> Travel Safety Assessment
                        </h4>
                        <p style="margin: 0.5rem 0;">
                            <span class="travel-safety-badge ${safetyClass}">${safetyText}</span>
                        </p>
                        ${analysis.travel_safety_details ? 
                            `<p style="margin-top: 0.5rem; line-height: 1.6; font-size: 0.9em;">${analysis.travel_safety_details}</p>` : 
                            '<p style="margin-top: 0.5rem; line-height: 1.6; font-size: 0.9em;">' + 
                            (safetyClass === 'unsafe' ? 'Avoid non-essential travel. Stay indoors if possible.' : 
                             safetyClass === 'caution' ? 'Exercise caution when traveling. Avoid bridges and elevated structures.' : 
                             'Normal travel conditions. Stay alert for aftershocks.') + '</p>'}
                    </div>
                `;
            }
            
            if (analysis.magnitude_threshold) {
                html += `
                    <div style="margin-top: 1rem; padding: 0.75rem; background: #fff3cd; border-radius: 4px; border-left: 4px solid #ffc107;">
                        <strong><i class="fas fa-info-circle"></i> Note:</strong> ${analysis.magnitude_threshold}
                    </div>
                `;
            }
            
            contentEl.innerHTML = html;
        }
        
        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            initMap();
            
            // Add CSS animations
            const style = document.createElement('style');
            style.textContent = `
                @keyframes slideIn {
                    from { transform: translateX(100%); opacity: 0; }
                    to { transform: translateX(0); opacity: 1; }
                }
                @keyframes slideOut {
                    from { transform: translateX(0); opacity: 1; }
                    to { transform: translateX(100%); opacity: 0; }
                }
                @keyframes pulse {
                    0%, 100% { transform: scale(1); opacity: 1; }
                    50% { transform: scale(1.1); opacity: 0.8; }
                }
                @keyframes pulse-highlight {
                    0% { transform: scale(1); box-shadow: 0 4px 12px rgba(0,0,0,0.08); }
                    50% { transform: scale(1.02); box-shadow: 0 8px 24px rgba(255, 152, 0, 0.4); }
                    100% { transform: scale(1); box-shadow: 0 4px 12px rgba(0,0,0,0.08); }
                }
                .earthquake-marker {
                    animation: pulse 2s infinite;
                }
            `;
            document.head.appendChild(style);
        });
    </script>
</body>
</html>

