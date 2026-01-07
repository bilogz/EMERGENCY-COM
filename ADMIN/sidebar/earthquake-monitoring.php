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
            background: var(--card-bg-1);
            backdrop-filter: blur(10px);
            border: 1px solid var(--border-color-1);
            border-radius: 6px;
            padding: 0.75rem 1rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.9rem;
            transition: all 0.2s;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            color: var(--text-color-1);
        }
        
        .earthquake-control-btn:hover {
            background: var(--card-bg-1);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }
        
        .earthquake-control-btn.active {
            background: var(--primary-color-1);
            color: white;
            border-color: var(--primary-color-1);
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
        
        .earthquake-marker {
            background: transparent !important;
            border: none !important;
            pointer-events: auto !important;
        }
        
        .earthquake-marker div {
            pointer-events: auto !important;
            cursor: pointer !important;
            display: block !important;
            visibility: visible !important;
            opacity: 1 !important;
        }
        
        /* Ensure markers are visible on map */
        .leaflet-marker-icon {
            z-index: 1000 !important;
        }
        
        .earthquake-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .stat-card {
            background: var(--card-bg-1);
            padding: 1rem;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            border-left: 4px solid var(--primary-color-1);
        }
        
        .stat-card h3 {
            margin: 0 0 0.5rem 0;
            font-size: 0.9rem;
            color: var(--text-secondary-1);
        }
        
        .stat-card .stat-value {
            font-size: 2rem;
            font-weight: 600;
            color: var(--primary-color-1);
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
                        <div class="stat-card">
                            <h3>Last Update</h3>
                            <div class="stat-value" style="font-size: 1rem;" id="lastUpdate">-</div>
                            <div style="font-size: 0.75rem; color: var(--text-secondary-1); margin-top: 0.25rem;" id="realtimeIndicator">
                                <i class="fas fa-circle" style="color: #4CAF50; font-size: 0.5rem;"></i> Real-time active
                            </div>
                        </div>
                    </div>
                    
                    <!-- AI Analytics Panel -->
                    <div class="ai-analytics-panel" id="aiAnalyticsPanel" style="display: none;">
                        <div class="ai-analytics-header">
                            <div style="display: flex; align-items: center; gap: 0.5rem;">
                                <i class="fas fa-robot" style="color: var(--primary-color-1);"></i>
                                <h3 style="margin: 0;">AI Impact Analysis for Quezon City</h3>
                            </div>
                            <button onclick="document.getElementById('aiAnalyticsPanel').style.display='none'" style="background:none;border:none;color:inherit;cursor:pointer;font-size:1.5rem;line-height:1;">×</button>
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
                            <button id="aiAnalyticsBtn" class="earthquake-control-btn" title="AI Impact Analysis" onclick="showAIAnalytics()">
                                <i class="fas fa-robot"></i>
                                <span>AI Analysis</span>
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
                            
                            const icon = L.divIcon({
                                className: 'earthquake-marker',
                                html: `<div style="background:${color};width:${iconSize}px;height:${iconSize}px;border-radius:50%;border:${borderWidth}px solid white;box-shadow:0 0 8px ${color}, 0 0 16px ${color}, 0 2px 8px rgba(0,0,0,0.5);position:relative;z-index:1000;"></div>`,
                                iconSize: [iconSize, iconSize],
                                iconAnchor: [iconSize/2, iconSize/2],
                                popupAnchor: [0, -iconSize/2]
                            });
                            
                            // Create marker with higher z-index
                            const marker = L.marker([lat, lon], { 
                                icon: icon,
                                zIndexOffset: mag * 100 // Higher z-index for larger magnitudes
                            });
                            
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
                            marker.addTo(map);
                            earthquakeMarkers.push(marker);
                            
                            // Debug: Log marker creation
                            if (earthquakeMarkers.length <= 5) { // Log first 5 for debugging
                                console.log(`Marker added: Mag ${mag.toFixed(1)} at ${lat.toFixed(4)}, ${lon.toFixed(4)} - Color: ${color}, Size: ${iconSize}px`);
                            }
                        });
                        
                        // Update statistics
                        updateStatistics(data.features);
                        
                        // Show info panel
                        showEarthquakeInfo(data.features.length);
                        
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
            const latest = features.length > 0 ? features[0] : null;
            const latestMag = latest ? (latest.properties.mag || 0).toFixed(1) : '-';
            
            document.getElementById('totalEvents').textContent = totalEvents;
            document.getElementById('majorEvents').textContent = majorEvents;
            document.getElementById('latestMagnitude').textContent = latestMag;
            const updateTime = new Date().toLocaleTimeString();
            document.getElementById('lastUpdate').textContent = realtimeEnabled ? `${updateTime} (Real-time)` : updateTime;
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
                    console.log('Real-time check response:', data.metadata?.count || 0, 'earthquakes found');
                    
                    if (!data || !data.features) {
                        console.warn('No features in response:', data);
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
                                const icon = L.divIcon({
                                    className: 'earthquake-marker',
                                    html: `<div style="background:${color};width:${iconSize}px;height:${iconSize}px;border-radius:50%;border:${borderWidth}px solid white;box-shadow:0 0 12px ${color}, 0 0 24px ${color}, 0 2px 8px rgba(0,0,0,0.5);animation:pulse 2s infinite;position:relative;z-index:1000;"></div>`,
                                    iconSize: [iconSize, iconSize],
                                    iconAnchor: [iconSize/2, iconSize/2],
                                    popupAnchor: [0, -iconSize/2]
                                });
                                
                                // Create marker with higher z-index
                                const marker = L.marker([lat, lon], { 
                                    icon: icon,
                                    zIndexOffset: mag * 100 // Higher z-index for larger magnitudes
                                });
                                
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
                    <p>Analyzing earthquake impacts on Quezon City...</p>
                    <p style="font-size: 0.9em; color: var(--text-secondary-1); margin-top: 0.5rem;">This may take a few seconds</p>
                </div>
            `;
            
            // Prepare earthquake data for API
            const eqData = earthquakes.map(feature => ({
                lat: feature.geometry.coordinates[1],
                lon: feature.geometry.coordinates[0],
                magnitude: feature.properties.mag || 0,
                depth: feature.geometry.coordinates[2] || 0,
                place: feature.properties.place || 'Unknown',
                time: feature.properties.time
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
            .then(response => response.json())
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
                contentEl.innerHTML = `
                    <div style="padding: 1rem; color: var(--error-color, #F44336);">
                        <i class="fas fa-exclamation-circle"></i> Error: ${error.message}
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
                .earthquake-marker {
                    animation: pulse 2s infinite;
                }
            `;
            document.head.appendChild(style);
        });
    </script>
</body>
</html>

