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
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
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
        
        .leaflet-marker-icon.earthquake-marker-custom {
            background: transparent !important;
            border: none !important;
        }
        
        .leaflet-marker-icon {
            z-index: 1000 !important;
        }
        
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

        .qc-risk-alert-panel.minimized .qc-risk-content {
            display: none;
        }
        
        .qc-risk-header {
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(10px);
            padding: 1.25rem 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: white;
            cursor: pointer;
            user-select: none;
            transition: background 0.3s;
        }
        
        .qc-risk-header:hover {
            background: rgba(255,255,255,0.15);
        }
        
        .qc-risk-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-weight: 700;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            background: rgba(255,255,255,0.2);
            backdrop-filter: blur(10px);
        }
        
        .qc-risk-badge.low { background: rgba(76, 175, 80, 0.6); }
        .qc-risk-badge.moderate { background: rgba(255, 193, 7, 0.6); color: #333; }
        .qc-risk-badge.high { background: rgba(255, 152, 0, 0.6); }
        .qc-risk-badge.critical { background: rgba(244, 67, 54, 0.6); }
        
        .qc-risk-content {
            padding: 1.5rem;
            background: var(--card-bg-1);
            color: var(--text-color-1);
        }
        
        #qcRiskChevron {
            transition: transform 0.3s ease;
        }
        
        .qc-risk-alert-panel.minimized #qcRiskChevron {
            transform: rotate(-90deg);
        }

        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        @keyframes pulse-glow {
            0%, 100% { box-shadow: 0 0 0 0 rgba(244, 67, 54, 0.7); }
            50% { box-shadow: 0 0 0 10px rgba(244, 67, 54, 0); }
        }
        
        .qc-risk-alert-panel.critical-alert {
            animation: pulse-glow 2s infinite;
            border-color: #F44336;
        }

        /* AI Analytics Panel (Top briefing) */
        .ai-analytics-panel {
            background: var(--card-bg-1);
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            border: 1px solid var(--border-color-1);
            margin-bottom: 1.5rem;
            overflow: hidden;
            transition: all 0.3s ease;
        }
        
        .ai-analytics-panel.minimized .ai-analytics-content {
            display: none;
        }
        
        .ai-analytics-header {
            background: linear-gradient(135deg, var(--primary-color-1), #6c5ce7);
            color: white;
            padding: 1rem 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            cursor: pointer;
        }
        
        .ai-analytics-content {
            padding: 1.5rem;
            max-height: 500px;
            overflow-y: auto;
        }

        .recommendation-item {
            background: rgba(33, 150, 243, 0.1);
            padding: 0.75rem;
            border-radius: 6px;
            border-left: 3px solid #2196F3;
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
        }

        [data-theme="dark"] .qc-risk-content { background: #1e1e2d; }

        /* Feature 1: Severity-Based Marker Animations */
        @keyframes pulse-ring {
            0% { transform: scale(1); box-shadow: 0 0 0 0 rgba(244, 67, 54, 0.7); }
            70% { transform: scale(1); box-shadow: 0 0 0 15px rgba(244, 67, 54, 0); }
            100% { transform: scale(1); box-shadow: 0 0 0 0 rgba(244, 67, 54, 0); }
        }
        
        .quake-pulse {
            animation: pulse-ring 2s infinite;
        }
        
        @keyframes subtle-glow {
            0%, 100% { box-shadow: 0 0 5px 1px rgba(255, 152, 0, 0.4); }
            50% { box-shadow: 0 0 15px 3px rgba(255, 152, 0, 0.7); }
        }

        .quake-glow {
            animation: subtle-glow 4s ease-in-out infinite;
        }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    <?php include 'includes/admin-header.php'; ?>
    
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
                        <li class="breadcrumb-item"><a href="/" class="breadcrumb-link">Home</a></li>
                        <li class="breadcrumb-item"><a href="automated-warnings.php" class="breadcrumb-link">Automated Warnings</a></li>
                        <li class="breadcrumb-item active">PHIVOLCS Earthquake Monitoring</li>
                    </ol>
                </nav>
                <h1><i class="fas fa-mountain"></i> PHIVOLCS Earthquake Monitoring</h1>
                <p>Monitor real-time seismic activity in the Philippines region with AI-driven risk assessment for Quezon City.</p>
            </div>
            
            <div class="sub-container">
                <div class="page-content">
                    <!-- AI Analyst – City Safety Briefing (Top) -->
                    <div class="ai-analytics-panel" id="aiAnalyticsPanel" style="display: none;">
                        <div class="ai-analytics-header" onclick="toggleAIPanelMinimize()">
                            <div style="display: flex; align-items: center; gap: 0.5rem;">
                                <i class="fas fa-robot"></i>
                                <div>
                                    <h3 style="margin: 0; font-size: 1rem;">AI Analyst – City Safety Briefing</h3>
                                    <p style="margin: 0; font-size: 0.75rem; opacity: 0.9;" id="aiPanelSubtitle">LGU Decision Support</p>
                                </div>
                            </div>
                            <div>
                                <button class="earthquake-control-btn" style="padding: 0.25rem 0.5rem; font-size: 0.75rem;" onclick="event.stopPropagation(); downloadReport()">PDF</button>
                                <i class="fas fa-minus" style="margin-left: 0.5rem;"></i>
                            </div>
                        </div>
                        <div class="ai-analytics-content" id="aiAnalyticsContent">
                            <p><i class="fas fa-spinner fa-spin"></i> Initializing briefing...</p>
                        </div>
                    </div>

                    <!-- Statistics Grid -->
                    <div class="earthquake-stats" id="earthquakeStats">
                        <div class="stat-card">
                            <h3>Total Events (30d)</h3>
                            <div class="stat-value" id="totalEvents">-</div>
                        </div>
                        <div class="stat-card">
                            <h3>Major (5.0+)</h3>
                            <div class="stat-value" id="majorEvents">-</div>
                        </div>
                        <div class="stat-card">
                            <h3>Latest Magnitude</h3>
                            <div class="stat-value" id="latestMagnitude">-</div>
                        </div>
                        <div class="stat-card stat-card-last-earthquake">
                            <h3>Last Update</h3>
                            <div class="stat-value" style="font-size: 1rem;" id="lastUpdate">-</div>
                            <div id="realtimeIndicator" style="font-size: 0.7rem; color: #4CAF50; margin-top: 0.25rem;">
                                <i class="fas fa-circle"></i> Real-time Active
                            </div>
                        </div>
                    </div>
                    
                    <!-- IMPROVED: Quezon City AI Risk Analysis & Prediction Panel -->
                    <div class="qc-risk-alert-panel minimized" id="qcRiskAlertPanel">
                        <div class="qc-risk-header" onclick="toggleQCRiskMinimize()" tabindex="0">
                            <div style="display: flex; align-items: center; gap: 0.75rem;">
                                <i class="fas fa-shield-alt" style="font-size: 1.5rem;"></i>
                                <div>
                                    <h3 style="margin: 0; font-size: 1.1rem;">Quezon City AI Risk Analysis & Prediction</h3>
                                    <div style="display: flex; align-items: center; gap: 0.5rem; margin-top: 0.25rem;">
                                        <span class="qc-risk-badge" id="qcRiskBadge">INITIALIZING</span>
                                        <span style="font-size: 0.8rem; opacity: 0.8;" id="aiRiskTimestamp">AI analysis pending...</span>
                                    </div>
                                </div>
                            </div>
                            <div style="display: flex; align-items: center; gap: 1rem;">
                                <button class="earthquake-control-btn" style="padding: 0.35rem 0.75rem; font-size: 0.8rem; background: rgba(255,255,255,0.2); color: white; border: none;" onclick="event.stopPropagation(); generateAIRiskAssessment(earthquakeData, true);">
                                    <i class="fas fa-sync-alt"></i> Re-analyze
                                </button>
                                <i class="fas fa-chevron-down" id="qcRiskChevron"></i>
                            </div>
                        </div>
                        
                        <div class="qc-risk-content" id="qcRiskContent">
                            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem;">
                                <!-- Summary Section -->
                                <div class="risk-section">
                                    <h4 style="color: var(--primary-color-1); margin-top: 0; display: flex; align-items: center; gap: 0.5rem;">
                                        <i class="fas fa-info-circle"></i> Seismic Summary
                                    </h4>
                                    <div id="aiSummaryText" style="line-height: 1.6;">
                                        Analyzing recent seismic patterns near Quezon City...
                                    </div>
                                </div>

                                <!-- Predictive Section -->
                                <div class="risk-section">
                                    <h4 style="color: #9C27B0; margin-top: 0; display: flex; align-items: center; gap: 0.5rem;">
                                        <i class="fas fa-crystal-ball"></i> Predictive Outlook (7-14 Days)
                                    </h4>
                                    <div id="aiPredictionText" style="line-height: 1.6; background: rgba(156, 39, 176, 0.05); padding: 1rem; border-radius: 8px; border-left: 3px solid #9C27B0;">
                                        Waiting for AI trend analysis...
                                    </div>
                                </div>

                                <!-- Recommendations Section -->
                                <div class="risk-section" style="grid-column: 1 / -1;">
                                    <h4 style="color: #2196F3; margin-top: 0; display: flex; align-items: center; gap: 0.5rem;">
                                        <i class="fas fa-clipboard-check"></i> Actionable Recommendations
                                    </h4>
                                    <div id="aiRecommendations" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem;">
                                        <!-- Recommendations injected here -->
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Visual Analytics Section -->
                    <div class="analytics-section" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem; margin-bottom: 1.5rem;">
                        <div class="stat-card" style="grid-column: span 2;">
                            <h3><i class="fas fa-chart-line"></i> Seismic Trend (30 Days)</h3>
                            <div style="height: 250px;"><canvas id="trendChart"></canvas></div>
                        </div>
                        <div class="stat-card">
                            <h3><i class="fas fa-chart-pie"></i> Severity Distribution</h3>
                            <div style="height: 250px;"><canvas id="severityChart"></canvas></div>
                        </div>
                    </div>

                    <!-- Table Section -->
                    <div class="stat-card" style="margin-bottom: 1.5rem;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                            <h3><i class="fas fa-list"></i> Recent Seismic Activity</h3>
                            <button class="earthquake-control-btn" onclick="downloadFullReport()">
                                <i class="fas fa-file-pdf"></i> Full Report
                            </button>
                        </div>
                        <div style="overflow-x: auto;">
                            <table id="earthquakeTable" class="data-table" style="width: 100%;">
                                <thead>
                                    <tr>
                                        <th>Date & Time</th>
                                        <th>Magnitude</th>
                                        <th>Depth</th>
                                        <th>Location</th>
                                        <th>Distance (QC)</th>
                                        <th>Risk</th>
                                    </tr>
                                </thead>
                                <tbody id="earthquakeTableBody">
                                    <tr><td colspan="6" style="text-align: center;">Loading data...</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <!-- Map Container -->
                    <div class="map-container">
                        <div id="earthquakeMap"></div>
                        <div class="earthquake-info">
                            <div class="earthquake-info-header">
                                <i class="fas fa-layer-group"></i> <span>Seismic Legend</span>
                            </div>
                            <div class="earthquake-legend">
                                <div class="legend-item">
                                    <div class="legend-color quake-pulse" style="background: #F44336;"></div>
                                    <span>Critical (≥5.0)</span>
                                </div>
                                <div class="legend-item">
                                    <div class="legend-color quake-glow" style="background: #FF9800;"></div>
                                    <span>Moderate (3.0-4.9)</span>
                                </div>
                                <div class="legend-item">
                                    <div class="legend-color" style="background: #4CAF50;"></div>
                                    <span>Minor (<3.0)</span>
                                </div>
                            </div>
                        </div>
                        <div id="quezonCityStatus" onclick="focusQuezonCity()" style="cursor: pointer; position: absolute; bottom: 20px; right: 20px; background: var(--primary-color-1); color: white; padding: 0.75rem 1rem; border-radius: 8px; z-index: 1000; display: flex; align-items: center; gap: 0.5rem; font-weight: 500;">
                            <i class="fas fa-map-marker-alt"></i> <span>Focus QC</span>
                        </div>
                        <div class="earthquake-controls">
                            <button id="refreshBtn" class="earthquake-control-btn"><i class="fas fa-sync-alt"></i> <span>Refresh</span></button>
                            <button id="filterBtn" class="earthquake-control-btn"><i class="fas fa-filter"></i> <span>Filter</span></button>
                            <button id="snapshotBtn" class="earthquake-control-btn" onclick="downloadSnapshot()"><i class="fas fa-camera"></i> <span>Snapshot</span></button>
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
        let lastAIAnalysisTime = 0;
        let trendChartInstance = null;
        let severityChartInstance = null;
        let initialZoomDone = false;

        // Initialize map
        function initMap() {
            map = L.map('earthquakeMap').setView([14.6488, 121.0509], 12);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '© OpenStreetMap' }).addTo(map);
            
            loadQuezonCityBoundary();
            loadEarthquakeData();
            
            document.getElementById('refreshBtn').onclick = loadEarthquakeData;
            document.getElementById('filterBtn').onclick = showFilterDialog;
        }

        function loadQuezonCityBoundary() {
            fetch('../api/quezon-city.geojson')
                .then(r => r.json())
                .then(data => {
                    L.geoJSON(data, {
                        style: { color: '#FF5722', weight: 3, fillColor: '#FF5722', fillOpacity: 0.1 }
                    }).addTo(map);
                });
        }

        function loadEarthquakeData() {
            const refreshBtn = document.getElementById('refreshBtn');
            refreshBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading...';
            
            const startTime = new Date();
            startTime.setDate(startTime.getDate() - 30);
            
            const url = `https://earthquake.usgs.gov/fdsnws/event/1/query?format=geojson&starttime=${startTime.toISOString()}&minmagnitude=${minMagnitude}&maxlatitude=21.5&minlatitude=4.0&maxlongitude=127.5&minlongitude=115.5`;
            
            fetch(url)
                .then(r => r.json())
                .then(data => {
                    earthquakeData = data.features;
                    updateMarkers(data.features);
                    updateStatistics(data.features);
                    updateCharts(data.features);
                    updateTable(data.features);
                    updateQuezonCityRisk(data.features);
                    
                    // Feature 2: Auto-Zoom to Critical Events (Initial Load Only)
                    if (!initialZoomDone) {
                        const critical = data.features.find(f => f.properties.mag >= 5.0);
                        if (critical) {
                            const [lon, lat] = critical.geometry.coordinates;
                            map.flyTo([lat, lon], 8, { animate: true, duration: 1.5 });
                        }
                        initialZoomDone = true;
                    }
                    
                    refreshBtn.innerHTML = '<i class="fas fa-sync-alt"></i> Refresh';
                });
        }

        function updateMarkers(features) {
            earthquakeMarkers.forEach(m => map.removeLayer(m));
            earthquakeMarkers = [];
            
            features.forEach(f => {
                const [lon, lat] = f.geometry.coordinates;
                const mag = f.properties.mag || 0;
                
                // Feature 3: Sync Marker Colors with AI Risk Assessment
                let color;
                // Check if AI risk data is available (Feature 3 Priority: AI Risk > Magnitude)
                const aiRisk = f.properties.ai_risk; // Assuming backend might populate this
                
                if (aiRisk === 'High' || aiRisk === 'Critical') color = '#F44336';
                else if (aiRisk === 'Moderate') color = '#FF9800';
                else if (aiRisk === 'Low') color = '#4CAF50';
                else {
                    // Fallback to Magnitude logic
                    if (mag >= 5.0) color = '#F44336';
                    else if (mag >= 3.0) color = '#FF9800'; // Moderate 3.0 - 4.9
                    else color = '#4CAF50'; // Minor < 3.0
                }
                
                // Feature 1: Severity-Based Marker Pulse Animation
                let animClass = '';
                if (mag >= 5.0) animClass = 'quake-pulse';
                else if (mag >= 3.0 && mag <= 4.9) animClass = 'quake-glow';
                
                const icon = L.divIcon({
                    className: 'earthquake-marker-custom',
                    html: `<div class="${animClass}" style="background:${color}; width:20px; height:20px; border-radius:50%; border:2px solid white; box-shadow:0 0 5px rgba(0,0,0,0.3);"></div>`,
                    iconSize: [20, 20]
                });
                
                const marker = L.marker([lat, lon], { icon }).addTo(map)
                    .bindPopup(`<b>Magnitude ${mag}</b><br>${f.properties.place}<br>${new Date(f.properties.time).toLocaleString()}`);
                earthquakeMarkers.push(marker);
            });
        }

        function updateStatistics(features) {
            document.getElementById('totalEvents').textContent = features.length;
            document.getElementById('majorEvents').textContent = features.filter(f => f.properties.mag >= 5).length;
            const latest = features[0]?.properties.mag || '-';
            document.getElementById('latestMagnitude').textContent = typeof latest === 'number' ? latest.toFixed(1) : latest;
            document.getElementById('lastUpdate').textContent = new Date().toLocaleTimeString();
        }

        function updateQuezonCityRisk(features) {
            renderStaticRiskAssessment(features);
            generateAIRiskAssessment(features, false);
        }

        function renderStaticRiskAssessment(features) {
            const qcLat = 14.6488, qcLon = 121.0509;
            const nearby = features.filter(f => {
                const [lon, lat] = f.geometry.coordinates;
                return calculateDistanceKm(lat, lon, qcLat, qcLon) <= 200;
            });
            
            const significant = nearby.sort((a,b) => b.properties.mag - a.properties.mag)[0];
            const badge = document.getElementById('qcRiskBadge');
            const summary = document.getElementById('aiSummaryText');
            const recs = document.getElementById('aiRecommendations');
            const panel = document.getElementById('qcRiskAlertPanel');
            
            let risk = 'low', text = 'LOW';
            if (significant) {
                const mag = significant.properties.mag;
                const dist = calculateDistanceKm(significant.geometry.coordinates[1], significant.geometry.coordinates[0], qcLat, qcLon);
                if (mag >= 5 && dist < 50) { risk = 'critical'; text = 'CRITICAL'; }
                else if (mag >= 4.5 && dist < 100) { risk = 'high'; text = 'HIGH'; }
                else if (mag >= 4) { risk = 'moderate'; text = 'MODERATE'; }
            }
            
            badge.className = `qc-risk-badge ${risk}`;
            badge.textContent = text;
            if (risk === 'critical' || risk === 'high') panel.classList.add('critical-alert');
            else panel.classList.remove('critical-alert');
            
            summary.innerHTML = significant ? 
                `Detected magnitude <b>${significant.properties.mag}</b> earthquake within range. Base risk is <b>${text}</b>.` : 
                "No significant seismic activity within 200km of Quezon City.";
                
            recs.innerHTML = `<div class="recommendation-item">Monitor official PHIVOLCS updates for real-time local alerts.</div>`;
        }

        function generateAIRiskAssessment(features, force = false) {
            const now = Date.now();
            if (!force && now - lastAIAnalysisTime < 300000) return;
            
            const timestamp = document.getElementById('aiRiskTimestamp');
            timestamp.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i> Analyzing...';
            
            const relevant = features.slice(0, 15).map(f => ({
                magnitude: f.properties.mag,
                distanceFromQC: calculateDistanceKm(f.geometry.coordinates[1], f.geometry.coordinates[0], 14.6488, 121.0509),
                time: f.properties.time
            }));

            fetch('../api/earthquake-ai-analytics.php?action=assess_risk', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ earthquakes: relevant })
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    lastAIAnalysisTime = Date.now();
                    renderAIRiskContent(data.analysis, data.timestamp);
                } else {
                    timestamp.textContent = "AI Unavailable";
                }
            })
            .catch(() => { timestamp.textContent = "AI Connection Failed"; });
        }

        function renderAIRiskContent(analysis, time) {
            document.getElementById('aiRiskTimestamp').textContent = `Updated: ${new Date(time).toLocaleTimeString()}`;
            const badge = document.getElementById('qcRiskBadge');
            const level = analysis.ai_risk_level?.toLowerCase() || 'low';
            
            badge.className = `qc-risk-badge ${level}`;
            badge.textContent = `AI: ${level.toUpperCase()}`;
            
            document.getElementById('aiSummaryText').innerHTML = analysis.risk_summary;
            document.getElementById('aiPredictionText').innerHTML = analysis.prediction;
            
            const recs = analysis.recommendations || [];
            document.getElementById('aiRecommendations').innerHTML = recs.map(r => `<div class="recommendation-item">${r}</div>`).join('');
            
            const panel = document.getElementById('qcRiskAlertPanel');
            if (level === 'high' || level === 'critical') panel.classList.add('critical-alert');
            else panel.classList.remove('critical-alert');
        }

        function toggleQCRiskMinimize() {
            const panel = document.getElementById('qcRiskAlertPanel');
            panel.classList.toggle('minimized');
        }

        function calculateDistanceKm(lat1, lon1, lat2, lon2) {
            const R = 6371;
            const dLat = (lat2 - lat1) * Math.PI / 180;
            const dLon = (lon2 - lon1) * Math.PI / 180;
            const a = Math.sin(dLat/2) ** 2 + Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) * Math.sin(dLon/2) ** 2;
            return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
        }

        function updateCharts(features) {
            // Severity Distribution
            const counts = [0, 0, 0]; // Low, Moderate, High
            features.forEach(f => {
                const m = f.properties.mag;
                if (m >= 5) counts[2]++;
                else if (m >= 4) counts[1]++;
                else counts[0]++;
            });

            if (severityChartInstance) severityChartInstance.destroy();
            severityChartInstance = new Chart(document.getElementById('severityChart'), {
                type: 'doughnut',
                data: {
                    labels: ['Minor (<4.0)', 'Moderate (4.0-4.9)', 'Major (5.0+)'],
                    datasets: [{ data: counts, backgroundColor: ['#4CAF50', '#FF9800', '#F44336'] }]
                },
                options: { maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } }
            });

            // Trend Chart (Last 30 Days)
            const dailyCounts = new Array(30).fill(0);
            const labels = [];
            const now = new Date();
            for (let i = 29; i >= 0; i--) {
                const d = new Date();
                d.setDate(now.getDate() - i);
                labels.push(d.toLocaleDateString(undefined, { month: 'short', day: 'numeric' }));
            }

            features.forEach(f => {
                const dayDiff = Math.floor((now - new Date(f.properties.time)) / (1000 * 60 * 60 * 24));
                if (dayDiff >= 0 && dayDiff < 30) {
                    dailyCounts[29 - dayDiff]++;
                }
            });

            if (trendChartInstance) trendChartInstance.destroy();
            trendChartInstance = new Chart(document.getElementById('trendChart'), {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Earthquakes',
                        data: dailyCounts,
                        borderColor: '#667eea',
                        backgroundColor: 'rgba(102, 126, 234, 0.1)',
                        fill: true,
                        tension: 0.4
                    }]
                },
                options: { maintainAspectRatio: false, plugins: { legend: { display: false } } }
            });
        }

        function downloadFullReport() {
            const reportContainer = document.createElement('div');
            reportContainer.style.padding = '20px';
            reportContainer.style.background = 'white';
            reportContainer.innerHTML = `
                <h1 style="color: #333; border-bottom: 2px solid #667eea; padding-bottom: 10px;">Seismic Activity Report: Quezon City</h1>
                <p>Generated on ${new Date().toLocaleString()}</p>
                <div style="margin: 20px 0; padding: 15px; background: #f8f9fa; border-left: 4px solid #667eea;">
                    <h3>AI Risk Assessment Summary</h3>
                    <p>${document.getElementById('aiSummaryText').innerHTML}</p>
                    <p><strong>Predictive Outlook:</strong> ${document.getElementById('aiPredictionText').innerHTML}</p>
                </div>
                <h3>Recent Seismic Events</h3>
                <table style="width: 100%; border-collapse: collapse; font-size: 12px;">
                    <thead>
                        <tr style="background: #eee;">
                            <th style="border: 1px solid #ddd; padding: 8px;">Date</th>
                            <th style="border: 1px solid #ddd; padding: 8px;">Mag</th>
                            <th style="border: 1px solid #ddd; padding: 8px;">Location</th>
                            <th style="border: 1px solid #ddd; padding: 8px;">Dist (QC)</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${earthquakeData.slice(0, 20).map(f => `
                            <tr>
                                <td style="border: 1px solid #ddd; padding: 8px;">${new Date(f.properties.time).toLocaleString()}</td>
                                <td style="border: 1px solid #ddd; padding: 8px;">${f.properties.mag.toFixed(1)}</td>
                                <td style="border: 1px solid #ddd; padding: 8px;">${f.properties.place}</td>
                                <td style="border: 1px solid #ddd; padding: 8px;">${calculateDistanceKm(f.geometry.coordinates[1], f.geometry.coordinates[0], 14.6488, 121.0509).toFixed(1)} km</td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
            `;
            
            html2pdf().from(reportContainer).set({
                margin: 0.5,
                filename: `QC_Seismic_Report_${new Date().toISOString().slice(0,10)}.pdf`,
                html2canvas: { scale: 2 },
                jsPDF: { unit: 'in', format: 'letter', orientation: 'portrait' }
            }).save();
        }

        function updateTable(features) {
            const body = document.getElementById('earthquakeTableBody');
            body.innerHTML = features.slice(0, 10).map(f => {
                const dist = calculateDistanceKm(f.geometry.coordinates[1], f.geometry.coordinates[0], 14.6488, 121.0509).toFixed(1);
                return `<tr>
                    <td>${new Date(f.properties.time).toLocaleString()}</td>
                    <td>${f.properties.mag.toFixed(1)}</td>
                    <td>${f.geometry.coordinates[2].toFixed(1)} km</td>
                    <td>${f.properties.place}</td>
                    <td>${dist} km</td>
                    <td>${f.properties.mag >= 5 ? 'High' : 'Normal'}</td>
                </tr>`;
            }).join('');
        }

        function showFilterDialog() {
            const val = prompt("Enter min magnitude (2.5-8.0):", minMagnitude);
            if (val && !isNaN(val)) { minMagnitude = parseFloat(val); loadEarthquakeData(); }
        }

        function focusQuezonCity() {
            map.flyTo([14.6488, 121.0509], 12);
        }

        function toggleAIPanelMinimize() {
            document.getElementById('aiAnalyticsPanel').classList.toggle('minimized');
        }

        function downloadSnapshot() {
            const container = document.querySelector('.map-container');
            const btn = document.getElementById('snapshotBtn');
            const originalContent = btn.innerHTML;
            
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            btn.style.pointerEvents = 'none';

            // Feature 4: Export Map Snapshot
            // Check if html2canvas is available (usually bundled with html2pdf)
            if (typeof html2canvas !== 'undefined') {
                html2canvas(container, {
                    useCORS: true,
                    allowTaint: true
                }).then(canvas => {
                    const link = document.createElement('a');
                    link.download = `Map_Snapshot_${new Date().toISOString().slice(0,19).replace(/[:T]/g,'-')}.png`;
                    link.href = canvas.toDataURL('image/png');
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                    
                    btn.innerHTML = originalContent;
                    btn.style.pointerEvents = 'auto';
                }).catch(err => {
                    console.error('Snapshot failed:', err);
                    alert('Could not generate snapshot. Please try again.');
                    btn.innerHTML = originalContent;
                    btn.style.pointerEvents = 'auto';
                });
            } else {
                alert('Snapshot capability requires html2canvas library.');
                btn.innerHTML = originalContent;
                btn.style.pointerEvents = 'auto';
            }
        }

        document.addEventListener('DOMContentLoaded', initMap);
    </script>
</body>
</html>