<?php
/**
 * PHIVOLCS Earthquake Monitoring Page
 * Monitor earthquakes in the Philippines region using USGS data
 */

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
                <p>Monitor earthquakes in the Philippines region using real-time data from USGS Earthquake Hazards Program.</p>
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
                            <button id="refreshBtn" class="earthquake-control-btn" title="Refresh Earthquake Data">
                                <i class="fas fa-sync-alt"></i>
                                <span>Refresh</span>
                            </button>
                            <button id="filterBtn" class="earthquake-control-btn" title="Filter by Magnitude">
                                <i class="fas fa-filter"></i>
                                <span>Filter</span>
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
            const philippinesBounds = {
                minLat: 4.5,
                maxLat: 21.0,
                minLon: 116.0,
                maxLon: 127.0
            };
            
            // USGS Earthquake API - Last 30 days, magnitude 2.5+, Philippines region
            const startTime = new Date();
            startTime.setDate(startTime.getDate() - 30);
            const endTime = new Date();
            
            const url = `https://earthquake.usgs.gov/fdsnws/event/1/query?format=geojson&starttime=${startTime.toISOString().split('T')[0]}&endtime=${endTime.toISOString().split('T')[0]}&minmagnitude=${minMagnitude}&maxlatitude=${philippinesBounds.maxLat}&minlatitude=${philippinesBounds.minLat}&maxlongitude=${philippinesBounds.maxLon}&minlongitude=${philippinesBounds.minLon}`;
            
            fetch(url)
                .then(response => response.json())
                .then(data => {
                    // Clear existing markers
                    earthquakeMarkers.forEach(marker => {
                        if (map.hasLayer(marker)) {
                            map.removeLayer(marker);
                        }
                    });
                    earthquakeMarkers = [];
                    earthquakeData = [];
                    
                    if (data.features && data.features.length > 0) {
                        earthquakeData = data.features;
                        
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
                            
                            // Create custom icon
                            const iconSize = Math.max(20, Math.min(50, mag * 8));
                            const icon = L.divIcon({
                                className: 'earthquake-marker',
                                html: `<div style="background:${color};width:${iconSize}px;height:${iconSize}px;border-radius:50%;border:2px solid white;box-shadow:0 2px 4px rgba(0,0,0,0.3);"></div>`,
                                iconSize: [iconSize, iconSize],
                                iconAnchor: [iconSize/2, iconSize/2]
                            });
                            
                            // Create marker
                            const marker = L.marker([lat, lon], { icon: icon });
                            
                            // Popup with earthquake details
                            const timeAgo = getTimeAgo(time);
                            marker.bindPopup(`
                                <div style="min-width:200px;">
                                    <h3 style="margin:0 0 10px 0;color:${color};">
                                        <i class="fas fa-mountain"></i> Magnitude ${mag.toFixed(1)}
                                    </h3>
                                    <p style="margin:5px 0;"><strong>Location:</strong> ${place}</p>
                                    <p style="margin:5px 0;"><strong>Depth:</strong> ${depth.toFixed(1)} km</p>
                                    <p style="margin:5px 0;"><strong>Time:</strong> ${time.toLocaleString()}</p>
                                    <p style="margin:5px 0;font-size:0.9em;color:#666;">${timeAgo}</p>
                                    <p style="margin:10px 0 0 0;font-size:0.85em;color:#999;">
                                        <i class="fas fa-info-circle"></i> Data from USGS Earthquake Hazards Program
                                    </p>
                                </div>
                            `);
                            
                            marker.addTo(map);
                            earthquakeMarkers.push(marker);
                        });
                        
                        // Update statistics
                        updateStatistics(data.features);
                        
                        // Show info panel
                        showEarthquakeInfo(data.features.length);
                    } else {
                        alert('No recent earthquakes found in the Philippines region.');
                        updateStatistics([]);
                    }
                    
                    // Reset refresh button
                    if (refreshBtn) {
                        refreshBtn.innerHTML = '<i class="fas fa-sync-alt"></i> <span>Refresh</span>';
                        refreshBtn.disabled = false;
                    }
                })
                .catch(error => {
                    console.error('Error loading earthquake data:', error);
                    alert('Could not load earthquake data. Please check your internet connection.');
                    
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
            document.getElementById('lastUpdate').textContent = new Date().toLocaleTimeString();
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
        
        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            initMap();
        });
    </script>
</body>
</html>

