<?php
/**
 * Weather Monitoring Page
 * Real-time weather monitoring with map, AI analysis, and Google Weather style display
 */

session_start();

// Check if user is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../login.php');
    exit();
}

$pageTitle = 'Weather Monitoring';
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
    <link rel="stylesheet" href="css/hero.css">
    <link rel="stylesheet" href="css/sidebar-footer.css">
    <link rel="stylesheet" href="css/modules.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <style>
        :root {
            --primary-color-1: #3498db;
            --text-color-1: #2c3e50;
            --text-secondary-1: #7f8c8d;
            --card-bg-1: #ffffff;
            --bg-color-1: #f8f9fa;
            --border-color-1: #e0e0e0;
        }
        
        [data-theme="dark"] {
            --text-color-1: #ecf0f1;
            --text-secondary-1: #bdc3c7;
            --card-bg-1: #2c3e50;
            --bg-color-1: #34495e;
            --border-color-1: #4a5568;
        }
        
        .weather-container {
            display: flex;
            gap: 1.5rem;
            height: calc(100vh - 120px);
            padding: 1rem;
            min-height: 600px;
        }
        
        @media (max-width: 1200px) {
            .weather-container {
                flex-direction: column;
                height: auto;
            }
            
            .map-section {
                height: 500px;
            }
            
            .weather-sidebar {
                width: 100%;
            }
        }
        
        .map-section {
            flex: 1;
            position: relative;
            background: var(--card-bg-1);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            border: 1px solid var(--border-color-1);
            min-width: 0;
        }
        
        #weatherMap {
            width: 100%;
            height: 100%;
        }
        
        .weather-sidebar {
            width: 420px;
            min-width: 350px;
            max-width: 450px;
            display: flex;
            flex-direction: column;
            gap: 1rem;
            overflow-y: auto;
            padding-right: 0.5rem;
        }
        
        .weather-sidebar::-webkit-scrollbar {
            width: 6px;
        }
        
        .weather-sidebar::-webkit-scrollbar-track {
            background: transparent;
        }
        
        .weather-sidebar::-webkit-scrollbar-thumb {
            background: var(--border-color-1);
            border-radius: 3px;
        }
        
        .weather-card {
            background: var(--card-bg-1);
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            border: 1px solid var(--border-color-1);
        }
        
        .weather-card h3 {
            margin: 0 0 1rem 0;
            font-size: 1.1rem;
            color: var(--text-color-1);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        /* Google Weather Style */
        .google-weather-card {
            background: linear-gradient(180deg, #1a1a2e 0%, #16213e 100%);
            color: white;
            border: none;
            padding: 0;
            overflow: visible;
            min-height: 200px;
        }
        
        .google-weather-display {
            padding: 1.5rem;
            overflow: visible;
            word-wrap: break-word;
        }
        
        .gw-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
            gap: 1rem;
            flex-wrap: wrap;
        }
        
        .gw-left {
            flex: 1;
            min-width: 200px;
        }
        
        .gw-main-temp {
            display: flex;
            align-items: flex-start;
            gap: 0.5rem;
            flex-wrap: wrap;
        }
        
        .gw-temp-value {
            font-size: 4rem;
            font-weight: 300;
            line-height: 1;
            white-space: nowrap;
        }
        
        .gw-temp-unit {
            font-size: 1.5rem;
            opacity: 0.8;
            margin-top: 0.5rem;
        }
        
        .gw-icon {
            width: 64px;
            height: 64px;
            flex-shrink: 0;
        }
        
        .gw-details {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            font-size: 0.9rem;
            opacity: 0.9;
            margin-top: 1rem;
        }
        
        .gw-detail-item {
            display: flex;
            align-items: center;
            gap: 0.3rem;
            white-space: nowrap;
        }
        
        .gw-right {
            text-align: right;
            min-width: 150px;
            flex-shrink: 0;
        }
        
        .gw-day {
            font-size: 1.2rem;
            font-weight: 500;
            white-space: nowrap;
        }
        
        .gw-condition {
            opacity: 0.8;
            font-size: 0.95rem;
            margin-top: 0.25rem;
            word-break: break-word;
        }
        
        .gw-location {
            font-size: 0.8rem;
            opacity: 0.7;
            margin-top: 0.5rem;
            word-break: break-word;
        }
        
        /* Weather Tabs */
        .weather-tabs {
            display: flex;
            border-bottom: 1px solid var(--border-color-1);
            margin-bottom: 1rem;
        }
        
        .weather-tab {
            flex: 1;
            padding: 0.75rem;
            background: none;
            border: none;
            color: var(--text-secondary-1);
            cursor: pointer;
            font-size: 0.9rem;
            font-weight: 500;
            transition: all 0.2s;
            position: relative;
        }
        
        .weather-tab:hover {
            color: var(--text-color-1);
        }
        
        .weather-tab.active {
            color: var(--primary-color-1);
        }
        
        .weather-tab.active::after {
            content: '';
            position: absolute;
            bottom: -1px;
            left: 0;
            right: 0;
            height: 2px;
            background: var(--primary-color-1);
        }
        
        .hourly-graph {
            padding: 0.5rem;
            min-height: 120px;
        }
        
        /* Weekly Forecast */
        .weekly-forecast {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        
        .forecast-day-row {
            display: grid;
            grid-template-columns: 70px 50px 1fr 70px;
            align-items: center;
            padding: 0.75rem;
            border-radius: 8px;
            transition: background 0.2s;
            gap: 0.75rem;
            min-width: 0;
        }
        
        @media (max-width: 450px) {
            .forecast-day-row {
                grid-template-columns: 60px 40px 1fr 50px;
                gap: 0.5rem;
                padding: 0.5rem;
            }
        }
        
        .forecast-day-row:hover {
            background: var(--bg-color-1);
        }
        
        .forecast-day-name {
            font-weight: 500;
            color: var(--text-color-1);
        }
        
        .forecast-day-name.today {
            color: var(--primary-color-1);
        }
        
        .forecast-day-icon {
            width: 36px;
            height: 36px;
        }
        
        .forecast-temp-bar {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .forecast-temp-min {
            color: var(--text-secondary-1);
            font-size: 0.9rem;
            min-width: 30px;
            text-align: right;
        }
        
        .forecast-temp-max {
            color: var(--text-color-1);
            font-weight: 600;
            font-size: 0.9rem;
            min-width: 30px;
        }
        
        .forecast-bar-container {
            flex: 1;
            height: 6px;
            background: var(--bg-color-1);
            border-radius: 3px;
            overflow: hidden;
        }
        
        .forecast-bar {
            height: 100%;
            border-radius: 3px;
            background: linear-gradient(90deg, #3498db, #f39c12);
        }
        
        .forecast-precip {
            display: flex;
            align-items: center;
            gap: 0.25rem;
            font-size: 0.8rem;
            color: #3498db;
        }
        
        /* Map Controls */
        .map-controls {
            position: absolute;
            top: 10px;
            right: 10px;
            z-index: 1000;
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        
        .map-control-btn {
            background: white;
            border: 1px solid #ddd;
            border-radius: 6px;
            padding: 0.5rem 0.75rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.85rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: all 0.2s;
        }
        
        .map-control-btn:hover {
            background: #f5f5f5;
        }
        
        .map-control-btn.active {
            background: var(--primary-color-1);
            color: white;
            border-color: var(--primary-color-1);
        }
        
        /* Weather Markers */
        .weather-marker-icon {
            background: rgba(255, 255, 255, 0.75);
            border-radius: 50%;
            padding: 0.5rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.25rem;
            min-width: 80px;
            transition: all 0.3s ease;
            opacity: 0.5;
            backdrop-filter: blur(4px);
        }
        
        .weather-marker-icon:hover {
            opacity: 0.9;
            background: rgba(255, 255, 255, 0.95);
        }
        
        .weather-marker-icon img {
            width: 40px;
            height: 40px;
            opacity: 0.8;
        }
        
        .weather-marker-temp {
            font-size: 1rem;
            font-weight: 600;
            color: #2c3e50;
        }
        
        .weather-marker-city {
            font-size: 0.7rem;
            color: #7f8c8d;
            white-space: nowrap;
        }
        
        /* AI Analysis */
        .ai-analysis-card {
            background: linear-gradient(135deg, #1a1a2e 0%, #2d1b4e 100%);
            border: 1px solid #8e44ad;
        }
        
        .ai-status {
            font-size: 0.7rem;
            padding: 0.2rem 0.5rem;
            border-radius: 10px;
            background: #27ae60;
            color: white;
            margin-left: 0.5rem;
            font-weight: normal;
        }
        
        .ai-status.loading {
            background: #f39c12;
            animation: pulse 1s infinite;
        }
        
        .ai-status.error {
            background: #e74c3c;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
        
        .ai-analyze-btn {
            width: 100%;
            padding: 1rem;
            background: linear-gradient(135deg, #8e44ad, #9b59b6);
            border: none;
            border-radius: 8px;
            color: white;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            transition: all 0.3s;
        }
        
        .ai-analyze-btn:hover {
            background: linear-gradient(135deg, #9b59b6, #8e44ad);
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(142, 68, 173, 0.4);
        }
        
        .ai-result {
            background: rgba(255,255,255,0.05);
            border-radius: 8px;
            padding: 1rem;
            margin-top: 1rem;
        }
        
        .ai-result-section {
            margin-bottom: 1rem;
        }
        
        .ai-result-title {
            font-weight: 600;
            color: #9b59b6;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .ai-result-content {
            color: rgba(255,255,255,0.9);
            font-size: 0.9rem;
            line-height: 1.6;
        }
        
        .ai-alert-item {
            background: rgba(231, 76, 60, 0.2);
            border-left: 3px solid #e74c3c;
            padding: 0.75rem;
            margin-bottom: 0.5rem;
            border-radius: 0 6px 6px 0;
        }
        
        .ai-recommendation {
            display: flex;
            align-items: flex-start;
            gap: 0.5rem;
            padding: 0.5rem 0;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .ai-recommendation:last-child {
            border-bottom: none;
        }
        
        .loading {
            text-align: center;
            padding: 2rem;
            color: var(--text-secondary-1);
        }
        
        .error-message {
            color: #e74c3c;
            padding: 1rem;
            text-align: center;
        }
        
        /* Zoom Indicator */
        #zoomIndicator {
            position: absolute;
            bottom: 10px;
            left: 10px;
            z-index: 1000;
            background: rgba(0,0,0,0.75);
            color: white;
            padding: 0.4rem 0.75rem;
            border-radius: 6px;
            font-size: 0.75rem;
            font-family: monospace;
            backdrop-filter: blur(4px);
        }
        
        /* Quezon City Focus Status */
        .quezon-city-status {
            position: absolute;
            bottom: 10px;
            right: 10px;
            z-index: 1000;
            background: linear-gradient(135deg, #f39c12, #e67e22);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-size: 0.85rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            box-shadow: 0 4px 12px rgba(243, 156, 18, 0.4);
            animation: pulseFocus 2s ease-in-out infinite;
        }
        
        .quezon-city-status i {
            font-size: 1rem;
            animation: bounceMarker 2s ease-in-out infinite;
        }
        
        @keyframes pulseFocus {
            0%, 100% { 
                transform: scale(1);
                box-shadow: 0 4px 12px rgba(243, 156, 18, 0.4);
            }
            50% { 
                transform: scale(1.02);
                box-shadow: 0 6px 16px rgba(243, 156, 18, 0.6);
            }
        }
        
        @keyframes bounceMarker {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-3px); }
        }
        
        /* Wind Flow Canvas */
        .leaflet-zoom-animated {
            pointer-events: none;
            z-index: 500;
        }
        
        canvas.leaflet-zoom-animated {
            position: absolute;
            top: 0;
            left: 0;
        }
        
        /* Dark Mode - Removed filter for better visibility */
        .map-dark-mode {
            /* No filter - tiles are already dark themed */
        }
        
        /* Automated Warning Status */
        .auto-warning-status {
            position: absolute;
            top: 10px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 1001;
            background: linear-gradient(135deg, #e74c3c, #c0392b);
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(231, 76, 60, 0.4);
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-weight: 600;
            animation: pulseWarning 2s infinite;
            max-width: 90%;
            text-align: center;
        }
        
        .auto-warning-status.warning {
            background: linear-gradient(135deg, #f39c12, #e67e22);
            box-shadow: 0 4px 12px rgba(243, 156, 18, 0.4);
        }
        
        .auto-warning-status.info {
            background: linear-gradient(135deg, #3498db, #2980b9);
            box-shadow: 0 4px 12px rgba(52, 152, 219, 0.4);
        }
        
        @keyframes pulseWarning {
            0%, 100% { transform: translateX(-50%) scale(1); }
            50% { transform: translateX(-50%) scale(1.02); }
        }
        
        .auto-warning-status i {
            font-size: 1.2rem;
            animation: rotateWarning 3s linear infinite;
        }
        
        @keyframes rotateWarning {
            0% { transform: rotate(0deg); }
            25% { transform: rotate(-10deg); }
            50% { transform: rotate(0deg); }
            75% { transform: rotate(10deg); }
            100% { transform: rotate(0deg); }
        }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    <?php include 'includes/admin-header.php'; ?>
    
    <div class="main-content">
        <div class="main-container">
            <div class="title">
                <h1><i class="fas fa-cloud-sun-rain"></i> Weather Monitoring</h1>
                <p>Real-time weather data with AI-powered analysis</p>
            </div>
            
            <div class="weather-container">
                <!-- Map Section -->
                <div class="map-section">
                    <div id="weatherMap"></div>
                    
                    <div id="mapLoading" style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); z-index: 1000; background: rgba(255,255,255,0.9); padding: 1rem; border-radius: 8px; display: none;">
                        <i class="fas fa-spinner fa-spin"></i> Loading map...
                    </div>
                    
                    <div id="zoomIndicator">Zoom: 11</div>
                    
                    <!-- Quezon City Focus Status -->
                    <div id="quezonCityStatus" class="quezon-city-status" onclick="focusQuezonCity()" style="cursor: pointer;" title="Click to focus on Quezon City">
                        <i class="fas fa-map-marker-alt"></i>
                        <span>Focused on Quezon City</span>
                    </div>
                    
                    <!-- Map Controls -->
                    <div class="map-controls">
                        <button id="darkModeBtn" class="map-control-btn" title="Toggle Dark Mode">
                            <i class="fas fa-moon"></i>
                            <span>Dark Mode</span>
                        </button>
                        <button id="windFlowBtn" class="map-control-btn" title="Toggle Wind Flow Animation">
                            <i class="fas fa-wind"></i>
                            <span>Wind Flow</span>
                        </button>
                        <button id="radarBtn" class="map-control-btn" title="Toggle Precipitation Radar">
                            <i class="fas fa-cloud-rain"></i>
                            <span>Radar</span>
                        </button>
                        <button id="humidityBtn" class="map-control-btn" title="Toggle Humidity Layer">
                            <i class="fas fa-tint"></i>
                            <span>Humidity</span>
                        </button>
                        <button id="temperatureBtn" class="map-control-btn" title="Toggle Temperature Layer">
                            <i class="fas fa-thermometer-half"></i>
                            <span>Temperature</span>
                        </button>
                        <button id="windSpeedBtn" class="map-control-btn" title="Toggle Wind Speed Layer">
                            <i class="fas fa-wind"></i>
                            <span>Wind Speed</span>
                        </button>
                        <button id="cloudsBtn" class="map-control-btn" title="Toggle Cloud Cover">
                            <i class="fas fa-cloud"></i>
                            <span>Clouds</span>
                        </button>
                    </div>
                    
                    <!-- Automated Warning Status -->
                    <div id="autoWarningStatus" class="auto-warning-status" style="display: none;">
                        <i class="fas fa-exclamation-triangle"></i>
                        <span id="warningMessage"></span>
                    </div>
                </div>
                
                <!-- Sidebar Section -->
                <div class="weather-sidebar">
                    <!-- Current Weather - Google Style -->
                    <div class="weather-card google-weather-card">
                        <div id="currentWeatherGoogle" class="google-weather-display">
                            <div class="loading"><i class="fas fa-spinner fa-spin"></i> Loading...</div>
                        </div>
                    </div>
                    
                    <!-- Hourly Chart -->
                    <div class="weather-card">
                        <div class="weather-tabs">
                            <button class="weather-tab active" data-tab="temperature">Temperature</button>
                            <button class="weather-tab" data-tab="precipitation">Precipitation</button>
                            <button class="weather-tab" data-tab="wind">Wind</button>
                        </div>
                        <div id="hourlyGraph" class="hourly-graph">
                            <canvas id="hourlyTempChart" height="120"></canvas>
                        </div>
                    </div>
                    
                    <!-- 7-Day Forecast -->
                    <div class="weather-card">
                        <h3><i class="fas fa-calendar-week"></i> 7-Day Forecast</h3>
                        <div id="weeklyForecast" class="weekly-forecast">
                            <div class="loading"><i class="fas fa-spinner fa-spin"></i> Loading...</div>
                        </div>
                    </div>
                    
                    <!-- AI Analysis -->
                    <div class="weather-card ai-analysis-card">
                        <h3>
                            <i class="fas fa-robot" style="color: #8e44ad;"></i> AI Weather Analysis
                            <span id="aiStatus" class="ai-status">Ready</span>
                        </h3>
                        <div id="aiAnalysis" class="ai-analysis-content">
                            <button onclick="getAIWeatherAnalysis()" class="ai-analyze-btn">
                                <i class="fas fa-brain"></i> Analyze Weather
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // API Keys - Will be loaded from server
        let GEMINI_API_KEY = null;
        const GEMINI_API_URL = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent';
        
        // Load Gemini API key from server
        fetch('../api/get-gemini-key.php')
            .then(response => response.json())
            .then(data => {
                if (data.success && data.apiKey) {
                    GEMINI_API_KEY = data.apiKey;
                } else {
                    console.warn('Gemini API key not configured:', data.message);
                }
            })
            .catch(error => {
                console.error('Error loading Gemini API key:', error);
            });
        
        let map;
        let markers = [];
        let mapInitialized = false;
        let windFlowEnabled = false;
        let radarEnabled = false;
        let humidityEnabled = false;
        let temperatureEnabled = false;
        let windSpeedEnabled = false;
        let cloudsEnabled = false;
        let darkModeEnabled = false;
        
        // Weather layers
        let radarLayer = null;
        let humidityLayer = null;
        let temperatureLayer = null;
        let windSpeedLayer = null;
        let cloudsLayer = null;
        let windFlowCanvas = null;
        let windFlowCtx = null;
        let windParticles = [];
        let animationFrameId = null;
        
        // Map tile layers
        let lightTileLayer = null;
        let darkTileLayer = null;
        let currentTileLayer = null;
        
        // Automated warning system
        let autoWarningInterval = null;
        let lastWarningTime = null;
        
        // Initialize map
        function initMap() {
            // Focus on Quezon City with smooth animation
            map = L.map('weatherMap').setView([14.6488, 121.0509], 12);
            
            // Light mode tiles (default)
            lightTileLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap contributors',
                maxZoom: 19
            });
            
            // Dark mode tiles (Stamen Toner Lite - better visibility)
            darkTileLayer = L.tileLayer('https://stamen-tiles-{s}.a.ssl.fastly.net/toner-lite/{z}/{x}/{y}{r}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors | Map tiles by <a href="http://stamen.com">Stamen Design</a>',
                subdomains: 'abcd',
                maxZoom: 20,
                opacity: 0.9
            });
            
            // Add default light layer
            currentTileLayer = lightTileLayer;
            currentTileLayer.addTo(map);
            
            // Smooth fly to Quezon City center
            setTimeout(() => {
                map.flyTo([14.6488, 121.0509], 12, {
                    duration: 1.5,
                    easeLinearity: 0.25
                });
            }, 300);
            
            // Ensure Quezon City stays in view on resize
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
            
            // Load Quezon City boundary
            loadQuezonCityBoundary();
            
            // Update marker visibility on zoom
            map.on('zoomend', function() {
                updateMarkerVisibility();
                const zoom = map.getZoom();
                const indicator = document.getElementById('zoomIndicator');
                if (indicator) {
                    indicator.textContent = `Zoom: ${zoom} ${zoom > 12 ? '(Icons hidden)' : ''}`;
                }
                if (windFlowEnabled && windFlowCanvas) {
                    createWindParticles();
                    if (animationFrameId) {
                        drawWindFlow();
                    }
                }
                updateQuezonCityStatus();
            });
            
            map.on('moveend', function() {
                if (windFlowEnabled && windFlowCanvas) {
                    createWindParticles();
                    if (animationFrameId) {
                        drawWindFlow();
                    }
                }
                updateQuezonCityStatus();
            });
            
            // Initial status update
            updateQuezonCityStatus();
            
            mapInitialized = true;
            
            // Initialize weather layers
            initWeatherLayers();
            
            // Initialize wind flow canvas
            initWindFlowCanvas();
            
            // Load weather data
            setTimeout(() => {
                loadWeatherDetails(14.6488, 121.0509, 'Quezon City');
                loadWeatherForecast(14.6488, 121.0509, 'Quezon City');
                loadMapData();
                
                // Start automated warnings after initial load
                setTimeout(() => {
                    checkWeatherConditions();
                }, 2000);
            }, 500);
        }
        
        // Initialize weather layers
        function initWeatherLayers() {
            // RainViewer Radar will be initialized when needed (no need to create it here)
            radarLayer = null;
            
            // OpenWeatherMap Humidity Layer (requires API key)
            humidityLayer = L.tileLayer('https://tile.openweathermap.org/map/humidity_new/{z}/{x}/{y}.png?appid={apiKey}', {
                attribution: 'OpenWeatherMap',
                opacity: 0.6,
                apiKey: 'YOUR_OPENWEATHER_API_KEY' // Will be fetched from API
            });
            
            // OpenWeatherMap Temperature Layer
            temperatureLayer = L.tileLayer('https://tile.openweathermap.org/map/temp_new/{z}/{x}/{y}.png?appid={apiKey}', {
                attribution: 'OpenWeatherMap',
                opacity: 0.6,
                apiKey: 'YOUR_OPENWEATHER_API_KEY'
            });
            
            // OpenWeatherMap Wind Speed Layer
            windSpeedLayer = L.tileLayer('https://tile.openweathermap.org/map/wind_new/{z}/{x}/{y}.png?appid={apiKey}', {
                attribution: 'OpenWeatherMap',
                opacity: 0.6,
                apiKey: 'YOUR_OPENWEATHER_API_KEY'
            });
            
            // OpenWeatherMap Clouds Layer
            cloudsLayer = L.tileLayer('https://tile.openweathermap.org/map/clouds_new/{z}/{x}/{y}.png?appid={apiKey}', {
                attribution: 'OpenWeatherMap',
                opacity: 0.5,
                apiKey: 'YOUR_OPENWEATHER_API_KEY'
            });
            
            // Setup button handlers
            setupLayerButtons();
            
            // Start automated weather warnings
            startAutomatedWarnings();
        }
        
        // Setup layer toggle buttons
        function setupLayerButtons() {
            document.getElementById('darkModeBtn')?.addEventListener('click', toggleDarkMode);
            document.getElementById('windFlowBtn')?.addEventListener('click', toggleWindFlow);
            document.getElementById('radarBtn')?.addEventListener('click', toggleRadar);
            document.getElementById('humidityBtn')?.addEventListener('click', toggleHumidity);
            document.getElementById('temperatureBtn')?.addEventListener('click', toggleTemperature);
            document.getElementById('windSpeedBtn')?.addEventListener('click', toggleWindSpeed);
            document.getElementById('cloudsBtn')?.addEventListener('click', toggleClouds);
        }
        
        // Toggle dark mode
        function toggleDarkMode() {
            darkModeEnabled = !darkModeEnabled;
            const btn = document.getElementById('darkModeBtn');
            const icon = btn.querySelector('i');
            
            if (darkModeEnabled) {
                btn.classList.add('active');
                icon.className = 'fas fa-sun';
                btn.querySelector('span').textContent = 'Light Mode';
                
                // Switch to dark tiles
                map.removeLayer(currentTileLayer);
                currentTileLayer = darkTileLayer;
                currentTileLayer.addTo(map);
                
                // No need for filter - tiles are already styled
                document.getElementById('weatherMap').classList.add('map-dark-mode');
            } else {
                btn.classList.remove('active');
                icon.className = 'fas fa-moon';
                btn.querySelector('span').textContent = 'Dark Mode';
                
                // Switch to light tiles
                map.removeLayer(currentTileLayer);
                currentTileLayer = lightTileLayer;
                currentTileLayer.addTo(map);
                
                // Remove dark mode class
                document.getElementById('weatherMap').classList.remove('map-dark-mode');
            }
        }
        
        // Helper function to disable all other map modes
        function disableAllMapModes() {
            // Disable all modes
            windFlowEnabled = false;
            radarEnabled = false;
            humidityEnabled = false;
            temperatureEnabled = false;
            windSpeedEnabled = false;
            cloudsEnabled = false;
            
            // Remove all layers
            if (radarLayer && map.hasLayer(radarLayer)) {
                map.removeLayer(radarLayer);
            }
            if (humidityLayer && map.hasLayer(humidityLayer)) {
                map.removeLayer(humidityLayer);
            }
            if (temperatureLayer && map.hasLayer(temperatureLayer)) {
                map.removeLayer(temperatureLayer);
            }
            if (windSpeedLayer && map.hasLayer(windSpeedLayer)) {
                map.removeLayer(windSpeedLayer);
            }
            if (cloudsLayer && map.hasLayer(cloudsLayer)) {
                map.removeLayer(cloudsLayer);
            }
            stopWindFlowAnimation();
            
            // Remove active class from all buttons
            document.querySelectorAll('.map-control-btn').forEach(btn => {
                if (btn.id !== 'darkModeBtn') {
                    btn.classList.remove('active');
                }
            });
        }
        
        // Toggle functions - Only one mode can be active at a time
        function toggleWindFlow() {
            const btn = document.getElementById('windFlowBtn');
            if (windFlowEnabled) {
                // Turn off
                windFlowEnabled = false;
                btn.classList.remove('active');
                stopWindFlowAnimation();
            } else {
                // Turn off all other modes first
                disableAllMapModes();
                
                // Turn on wind flow
                windFlowEnabled = true;
                btn.classList.add('active');
                if (markers.length > 0) {
                    startWindFlowAnimation();
                } else {
                    // Wait for markers to load
                    setTimeout(() => {
                        if (markers.length > 0) {
                            startWindFlowAnimation();
                        }
                    }, 500);
                }
            }
        }
        
        function toggleRadar() {
            const btn = document.getElementById('radarBtn');
            if (radarEnabled) {
                // Turn off
                radarEnabled = false;
                btn.classList.remove('active');
                if (radarLayer && map.hasLayer(radarLayer)) {
                    map.removeLayer(radarLayer);
                }
            } else {
                // Turn off all other modes first
                disableAllMapModes();
                
                // Turn on radar
                radarEnabled = true;
                btn.classList.add('active');
                
                // Use RainViewer API for radar (free, no API key needed)
                if (!radarLayer) {
                    // Get current timestamp for latest radar image
                    const timestamp = Math.floor(Date.now() / 1000);
                    radarLayer = L.tileLayer(`https://tilecache.rainviewer.com/v2/radar/${timestamp}/256/{z}/{x}/{y}/2/1_1.png`, {
                        attribution: 'RainViewer &copy; <a href="https://www.rainviewer.com">RainViewer.com</a>',
                        opacity: 0.6,
                        zIndex: 500
                    });
                }
                
                radarLayer.addTo(map);
                
                // Update radar every 10 minutes
                if (window.radarUpdateInterval) {
                    clearInterval(window.radarUpdateInterval);
                }
                window.radarUpdateInterval = setInterval(() => {
                    if (radarEnabled && radarLayer) {
                        const timestamp = Math.floor(Date.now() / 1000);
                        radarLayer.setUrl(`https://tilecache.rainviewer.com/v2/radar/${timestamp}/256/{z}/{x}/{y}/2/1_1.png`);
                    }
                }, 600000); // 10 minutes
            }
        }
        
        function toggleHumidity() {
            const btn = document.getElementById('humidityBtn');
            if (humidityEnabled) {
                // Turn off
                humidityEnabled = false;
                btn.classList.remove('active');
                if (humidityLayer && map.hasLayer(humidityLayer)) {
                    map.removeLayer(humidityLayer);
                }
            } else {
                // Turn off all other modes first
                disableAllMapModes();
                
                // Turn on humidity
                humidityEnabled = true;
                btn.classList.add('active');
                loadOpenWeatherLayer('humidity', humidityLayer);
            }
        }
        
        function toggleTemperature() {
            const btn = document.getElementById('temperatureBtn');
            if (temperatureEnabled) {
                // Turn off
                temperatureEnabled = false;
                btn.classList.remove('active');
                if (temperatureLayer && map.hasLayer(temperatureLayer)) {
                    map.removeLayer(temperatureLayer);
                }
            } else {
                // Turn off all other modes first
                disableAllMapModes();
                
                // Turn on temperature
                temperatureEnabled = true;
                btn.classList.add('active');
                loadOpenWeatherLayer('temp', temperatureLayer);
            }
        }
        
        function toggleWindSpeed() {
            const btn = document.getElementById('windSpeedBtn');
            if (windSpeedEnabled) {
                // Turn off
                windSpeedEnabled = false;
                btn.classList.remove('active');
                if (windSpeedLayer && map.hasLayer(windSpeedLayer)) {
                    map.removeLayer(windSpeedLayer);
                }
            } else {
                // Turn off all other modes first
                disableAllMapModes();
                
                // Turn on wind speed
                windSpeedEnabled = true;
                btn.classList.add('active');
                loadOpenWeatherLayer('wind', windSpeedLayer);
            }
        }
        
        function toggleClouds() {
            const btn = document.getElementById('cloudsBtn');
            if (cloudsEnabled) {
                // Turn off
                cloudsEnabled = false;
                btn.classList.remove('active');
                if (cloudsLayer && map.hasLayer(cloudsLayer)) {
                    map.removeLayer(cloudsLayer);
                }
            } else {
                // Turn off all other modes first
                disableAllMapModes();
                
                // Turn on clouds
                cloudsEnabled = true;
                btn.classList.add('active');
                loadOpenWeatherLayer('clouds', cloudsLayer);
            }
        }
        
        // Load OpenWeather layer with API key
        function loadOpenWeatherLayer(type, layer) {
            fetch('../api/weather-monitoring.php?action=getApiKey')
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.apiKey) {
                        const url = layer.options.urlTemplate || `https://tile.openweathermap.org/map/${type}_new/{z}/{x}/{y}.png?appid={apiKey}`;
                        layer.setUrl(url.replace('{apiKey}', data.apiKey));
                        layer.addTo(map);
                    } else {
                        alert('OpenWeatherMap API key not configured. Please set it up in Automated Warnings.');
                    }
                })
                .catch(error => {
                    console.error('Error loading API key:', error);
                    alert('Could not load weather layer. API key may not be configured.');
                });
        }
        
        // Update radar time for animation (no longer needed - handled in toggleRadar)
        function updateRadarTime() {
            if (radarEnabled && radarLayer) {
                const timestamp = Math.floor(Date.now() / 1000);
                radarLayer.setUrl(`https://tilecache.rainviewer.com/v2/radar/${timestamp}/256/{z}/{x}/{y}/2/1_1.png`);
            }
        }
        
        // Initialize wind flow canvas
        function initWindFlowCanvas() {
            if (!map) return;
            
            // Create custom canvas overlay for wind flow
            const WindFlowOverlay = L.Layer.extend({
                onAdd: function(map) {
                    this._map = map;
                    this._canvas = L.DomUtil.create('canvas', 'leaflet-zoom-animated');
                    const ctx = this._canvas.getContext('2d');
                    windFlowCtx = ctx;
                    
                    const size = map.getSize();
                    this._canvas.width = size.x;
                    this._canvas.height = size.y;
                    this._canvas.style.position = 'absolute';
                    this._canvas.style.top = '0';
                    this._canvas.style.left = '0';
                    this._canvas.style.pointerEvents = 'none';
                    
                    // Add to overlay pane (above markers, below popups)
                    map.getPanes().overlayPane.appendChild(this._canvas);
                    
                    // Update on map events
                    map.on('viewreset', this._reset, this);
                    map.on('moveend', this._update, this);
                    map.on('zoomend', this._reset, this);
                    map.on('resize', this._reset, this);
                    
                    this._reset();
                },
                
                onRemove: function(map) {
                    if (this._canvas && this._canvas.parentNode) {
                        map.getPanes().overlayPane.removeChild(this._canvas);
                    }
                    map.off('viewreset', this._reset, this);
                    map.off('moveend', this._update, this);
                    map.off('zoomend', this._reset, this);
                    map.off('resize', this._reset, this);
                },
                
                _reset: function() {
                    const size = this._map.getSize();
                    this._canvas.width = size.x;
                    this._canvas.height = size.y;
                    this._update();
                },
                
                _update: function() {
                    if (windFlowEnabled && markers.length > 0) {
                        createWindParticles();
                    }
                }
            });
            
            windFlowCanvas = new WindFlowOverlay();
            windFlowCanvas.addTo(map);
        }
        
        function resizeWindCanvas() {
            if (windFlowCanvas && windFlowCanvas._canvas && windFlowEnabled) {
                const size = map.getSize();
                windFlowCanvas._canvas.width = size.x;
                windFlowCanvas._canvas.height = size.y;
                createWindParticles();
            }
        }
        
        // Create wind particles from marker data
        function createWindParticles() {
            windParticles = [];
            if (!markers || markers.length === 0) return;
            
            const bounds = map.getBounds();
            const zoom = map.getZoom();
            
            // Create dense particles for better visualization (like Zoom Earth)
            // More particles for better coverage
            const particleCount = Math.min(800, Math.max(300, zoom * 40));
            const gridSpacing = Math.max(0.03, 0.25 / zoom); // Adaptive spacing based on zoom
            
            // Create grid-based particles for consistent coverage
            const latStep = (bounds.getNorth() - bounds.getSouth()) / Math.sqrt(particleCount);
            const lonStep = (bounds.getEast() - bounds.getWest()) / Math.sqrt(particleCount);
            
            for (let lat = bounds.getSouth(); lat < bounds.getNorth(); lat += latStep) {
                for (let lon = bounds.getWest(); lon < bounds.getEast(); lon += lonStep) {
                    // Add some randomness
                    const finalLat = lat + (Math.random() - 0.5) * latStep * 0.5;
                    const finalLon = lon + (Math.random() - 0.5) * lonStep * 0.5;
                    
                    // Find nearest marker for wind data
                    let nearestMarker = markers[0];
                    let minDist = Infinity;
                    markers.forEach(m => {
                        const dist = Math.sqrt(Math.pow(m.lat - finalLat, 2) + Math.pow(m.lon - finalLon, 2));
                        if (dist < minDist) {
                            minDist = dist;
                            nearestMarker = m;
                        }
                    });
                    
                    if (nearestMarker && nearestMarker.windDeg !== undefined && nearestMarker.windSpeed !== undefined && nearestMarker.windSpeed > 0) {
                        // Convert wind direction (meteorological: where wind comes FROM)
                        // To display direction (where wind is GOING)
                        const windDirection = (nearestMarker.windDeg + 180) % 360;
                        const radians = (windDirection * Math.PI) / 180;
                        
                        // Wind speed is already in km/h
                        const speed = nearestMarker.windSpeed || 0;
                        
                        windParticles.push({
                            x: finalLon,
                            y: finalLat,
                            vx: Math.sin(radians) * speed * 0.00005, // Wind going TO direction (adjusted for km/h)
                            vy: -Math.cos(radians) * speed * 0.00005,
                            speed: speed,
                            direction: windDirection,
                            opacity: Math.min(0.6 + (speed / 25) * 0.4, 1), // Higher base opacity for better visibility (0.6-1.0)
                            age: Math.random() * 100 // Random age for animation offset
                        });
                    }
                }
            }
        }
        
        // Draw wind flow - Zoom Earth style
        function drawWindFlow() {
            if (!windFlowEnabled || !windFlowCtx || !windFlowCanvas || windParticles.length === 0) return;
            
            const canvas = windFlowCanvas._canvas;
            if (!canvas) return;
            
            // Clear with slight fade for trailing effect (lighter for better visibility)
            windFlowCtx.fillStyle = 'rgba(0, 0, 0, 0.05)';
            windFlowCtx.fillRect(0, 0, canvas.width, canvas.height);
            
            const currentTime = Date.now() * 0.001; // Time in seconds
            
            windParticles.forEach(particle => {
                const point = map.latLngToContainerPoint([particle.y, particle.x]);
                
                if (point.x >= -50 && point.x <= canvas.width + 50 && 
                    point.y >= -50 && point.y <= canvas.height + 50) {
                    
                    // Calculate streak length based on wind speed (like Zoom Earth)
                    const baseLength = Math.min(particle.speed * 2, 50);
                    const length = Math.max(baseLength, 10); // Minimum length
                    
                    // Animated offset for flowing effect
                    const animationSpeed = particle.speed * 0.15;
                    const animationOffset = (currentTime * animationSpeed + particle.age) % (length * 2.5);
                    
                    // Calculate direction in radians
                    const angle = particle.direction * Math.PI / 180;
                    const cosAngle = Math.cos(angle);
                    const sinAngle = Math.sin(angle);
                    
                    // Calculate end point
                    const endX = point.x + sinAngle * length;
                    const endY = point.y - cosAngle * length;
                    
                    // Start point with animation offset (creates flowing effect)
                    const startX = point.x - sinAngle * animationOffset;
                    const startY = point.y + cosAngle * animationOffset;
                    
                    // Enhanced visibility - brighter white with glow effect
                    const baseOpacity = Math.min(particle.opacity * 1.5, 1);
                    const lineWidth = Math.max(2, Math.min(particle.speed / 3, 4));
                    
                    // Draw glow/shadow for better visibility
                    windFlowCtx.shadowBlur = 3;
                    windFlowCtx.shadowColor = 'rgba(255, 255, 255, 0.5)';
                    
                    // Main stroke - bright white
                    windFlowCtx.strokeStyle = `rgba(255, 255, 255, ${baseOpacity})`;
                    windFlowCtx.lineWidth = lineWidth;
                    windFlowCtx.lineCap = 'round';
                    windFlowCtx.lineJoin = 'round';
                    
                    // Draw the streak with gradient for better visibility
                    const gradient = windFlowCtx.createLinearGradient(startX, startY, endX, endY);
                    gradient.addColorStop(0, `rgba(255, 255, 255, ${baseOpacity * 0.6})`);
                    gradient.addColorStop(0.5, `rgba(255, 255, 255, ${baseOpacity})`);
                    gradient.addColorStop(1, `rgba(255, 255, 255, ${baseOpacity * 0.8})`);
                    
                    windFlowCtx.strokeStyle = gradient;
                    
                    // Draw the streak
                    windFlowCtx.beginPath();
                    windFlowCtx.moveTo(startX, startY);
                    windFlowCtx.lineTo(endX, endY);
                    windFlowCtx.stroke();
                    
                    // Reset shadow
                    windFlowCtx.shadowBlur = 0;
                    
                    // Add arrowhead for stronger winds (more visible)
                    if (particle.speed > 3) {
                        const arrowLength = Math.min(8, particle.speed / 2.5);
                        const arrowAngle = Math.PI / 4.5; // Wider arrow angle
                        
                        windFlowCtx.strokeStyle = `rgba(255, 255, 255, ${baseOpacity})`;
                        windFlowCtx.lineWidth = lineWidth * 1.2;
                        
                        windFlowCtx.beginPath();
                        windFlowCtx.moveTo(endX, endY);
                        windFlowCtx.lineTo(
                            endX - arrowLength * Math.cos(angle - arrowAngle),
                            endY - arrowLength * Math.sin(angle - arrowAngle)
                        );
                        windFlowCtx.moveTo(endX, endY);
                        windFlowCtx.lineTo(
                            endX - arrowLength * Math.cos(angle + arrowAngle),
                            endY - arrowLength * Math.sin(angle + arrowAngle)
                        );
                        windFlowCtx.stroke();
                    }
                }
            });
        }
        
        // Start wind flow animation
        function startWindFlowAnimation() {
            if (!windFlowCanvas) {
                // Re-initialize if needed
                initWindFlowCanvas();
            }
            if (markers.length > 0) {
                createWindParticles();
                animateWindFlow();
            } else {
                // Wait for markers
                setTimeout(() => {
                    if (markers.length > 0 && windFlowEnabled) {
                        createWindParticles();
                        animateWindFlow();
                    }
                }, 1000);
            }
        }
        
        // Stop wind flow animation
        function stopWindFlowAnimation() {
            if (animationFrameId) {
                cancelAnimationFrame(animationFrameId);
                animationFrameId = null;
            }
            if (windFlowCtx && windFlowCanvas && windFlowCanvas._canvas) {
                windFlowCtx.clearRect(0, 0, windFlowCanvas._canvas.width, windFlowCanvas._canvas.height);
            }
            windParticles = [];
        }
        
        // Animate wind flow - smooth continuous animation
        function animateWindFlow() {
            if (!windFlowEnabled) {
                if (animationFrameId) {
                    cancelAnimationFrame(animationFrameId);
                    animationFrameId = null;
                }
                return;
            }
            
            drawWindFlow();
            
            // Update particle ages for animation (particles don't move, streaks animate)
            windParticles.forEach(particle => {
                particle.age += 0.5; // Increment age for animation
                
                // Reset age periodically for continuous flow
                if (particle.age > 200) {
                    particle.age = 0;
                }
            });
            
            animationFrameId = requestAnimationFrame(animateWindFlow);
        }
        
        // Load Quezon City boundary
        function loadQuezonCityBoundary() {
            fetch('../api/quezon-city.geojson')
                .then(response => response.json())
                .then(geojsonData => {
                    L.geoJSON(geojsonData, {
                        style: {
                            color: '#f39c12',
                            weight: 3,
                            fillColor: '#4c8a89',
                            fillOpacity: 0.08,
                            dashArray: '10, 5'
                        }
                    }).addTo(map);
                })
                .catch(err => console.error('Error loading boundary:', err));
        }
        
        // Focus on Quezon City
        function focusQuezonCity() {
            if (!map) return;
            map.flyTo([14.6488, 121.0509], 12, {
                duration: 1.5,
                easeLinearity: 0.25
            });
        }
        
        // Update Quezon City focus status
        function updateQuezonCityStatus() {
            const statusDiv = document.getElementById('quezonCityStatus');
            if (!statusDiv || !map) return;
            
            const bounds = map.getBounds();
            const quezonCityLat = 14.6488;
            const quezonCityLon = 121.0509;
            
            // Check if Quezon City is in view
            const isInView = bounds.contains([quezonCityLat, quezonCityLon]);
            const center = map.getCenter();
            const distance = Math.sqrt(
                Math.pow(center.lat - quezonCityLat, 2) + 
                Math.pow(center.lng - quezonCityLon, 2)
            );
            
            if (isInView && distance < 0.1) {
                // Quezon City is focused
                statusDiv.style.display = 'flex';
                statusDiv.innerHTML = '<i class="fas fa-map-marker-alt"></i><span>Focused on Quezon City</span>';
                statusDiv.style.background = 'linear-gradient(135deg, #f39c12, #e67e22)';
            } else if (isInView) {
                // Quezon City is visible but not centered
                statusDiv.style.display = 'flex';
                statusDiv.innerHTML = '<i class="fas fa-eye"></i><span>Quezon City in view - Click to focus</span>';
                statusDiv.style.background = 'linear-gradient(135deg, #3498db, #2980b9)';
            } else {
                // Quezon City is not in view
                statusDiv.style.display = 'flex';
                statusDiv.innerHTML = '<i class="fas fa-map-marker-alt"></i><span>Click to focus on Quezon City</span>';
                statusDiv.style.background = 'linear-gradient(135deg, #95a5a6, #7f8c8d)';
            }
        }
        
        // Update marker visibility
        function updateMarkerVisibility() {
            if (!map) return;
            const zoom = map.getZoom();
            const hideThreshold = 12;
            
            document.querySelectorAll('.weather-marker-icon').forEach(markerEl => {
                if (zoom > hideThreshold) {
                    markerEl.style.opacity = '0';
                    markerEl.style.pointerEvents = 'none';
                    markerEl.style.transform = 'scale(0.5)';
                } else {
                    markerEl.style.opacity = '1';
                    markerEl.style.pointerEvents = 'auto';
                    markerEl.style.transform = 'scale(1)';
                }
            });
        }
        
        // Load weather details
        function loadWeatherDetails(lat, lon, name) {
            const container = document.getElementById('currentWeatherGoogle');
            if (!container) return;
            
            container.innerHTML = '<div class="loading"><i class="fas fa-spinner fa-spin"></i> Loading...</div>';
            
            fetch(`../api/weather-monitoring.php?action=current&lat=${lat}&lon=${lon}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.data) {
                        renderGoogleWeatherDisplay(data.data, name);
                        window.currentWeatherData = data.data;
                        window.currentLocationName = name;
                    } else {
                        container.innerHTML = `<div class="error-message">${data.message || 'Failed to load'}</div>`;
                    }
                })
                .catch(error => {
                    container.innerHTML = `<div class="error-message">Error: ${error.message}</div>`;
                });
        }
        
        // Render Google Weather display
        function renderGoogleWeatherDisplay(weather, name) {
            const container = document.getElementById('currentWeatherGoogle');
            if (!container) return;
            
            const temp = Math.round(weather.main.temp);
            const icon = weather.weather[0].icon;
            const condition = weather.weather[0].description;
            const humidity = weather.main.humidity;
            const windSpeed = (weather.wind.speed * 3.6).toFixed(1);
            const precip = weather.rain ? weather.rain['1h'] || 0 : weather.clouds?.all || 0;
            
            const days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
            const today = days[new Date().getDay()];
            
            container.innerHTML = `
                <div class="gw-header">
                    <div class="gw-left">
                        <div class="gw-main-temp">
                            <img src="https://openweathermap.org/img/wn/${icon}@4x.png" alt="${condition}" class="gw-icon">
                            <span class="gw-temp-value">${temp}</span>
                            <span class="gw-temp-unit">°C</span>
                        </div>
                        <div class="gw-details">
                            <div class="gw-detail-item">
                                <i class="fas fa-tint" style="color: #3498db;"></i>
                                Precipitation: ${precip > 0 && precip < 100 ? precip + 'mm' : precip + '%'}
                            </div>
                            <div class="gw-detail-item">
                                <i class="fas fa-water" style="color: #1abc9c;"></i>
                                Humidity: ${humidity}%
                            </div>
                            <div class="gw-detail-item">
                                <i class="fas fa-wind" style="color: #95a5a6;"></i>
                                Wind: ${windSpeed} km/h
                            </div>
                        </div>
                    </div>
                    <div class="gw-right">
                        <div class="gw-day">${today}</div>
                        <div class="gw-condition">${condition.charAt(0).toUpperCase() + condition.slice(1)}</div>
                        <div class="gw-location">${name}</div>
                    </div>
                </div>
            `;
        }
        
        // Load forecast
        function loadWeatherForecast(lat, lon, name) {
            fetch(`../api/weather-monitoring.php?action=forecast&lat=${lat}&lon=${lon}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const forecast = data.forecast || [];
                        window.forecastData = forecast;
                        
                        renderHourlyChart(forecast);
                        renderWeeklyForecast(forecast);
                        
                        // Auto-trigger AI analysis
                        setTimeout(() => {
                            if (window.currentWeatherData) {
                                getAIWeatherAnalysis();
                            }
                        }, 1000);
                    }
                })
                .catch(error => console.error('Forecast error:', error));
        }
        
        // Render hourly chart
        function renderHourlyChart(forecastData) {
            const ctx = document.getElementById('hourlyTempChart');
            if (!ctx || !forecastData || forecastData.length === 0) return;
            
            const hourlyData = forecastData.slice(0, 8);
            const labels = hourlyData.map(item => {
                const date = new Date(item.datetime);
                return date.getHours() + ':00';
            });
            const temps = hourlyData.map(item => Math.round(item.temp));
            
            if (window.hourlyChart) {
                window.hourlyChart.destroy();
            }
            
            window.hourlyChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        data: temps,
                        borderColor: '#f39c12',
                        backgroundColor: 'rgba(243, 156, 18, 0.1)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.4,
                        pointRadius: 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: { callbacks: { label: (ctx) => ctx.raw + '°C' } }
                    },
                    scales: {
                        x: { grid: { display: false } },
                        y: { grid: { color: 'rgba(0,0,0,0.05)' }, ticks: { callback: (val) => val + '°' } }
                    }
                }
            });
        }
        
        // Render weekly forecast
        function renderWeeklyForecast(forecastData) {
            const container = document.getElementById('weeklyForecast');
            if (!container || !forecastData) return;
            
            const dailyData = {};
            forecastData.forEach(item => {
                const date = new Date(item.datetime);
                const dayKey = date.toDateString();
                if (!dailyData[dayKey]) {
                    dailyData[dayKey] = { temps: [], icons: [], precip: 0 };
                }
                dailyData[dayKey].temps.push(item.temp);
                dailyData[dayKey].icons.push(item.icon);
                dailyData[dayKey].precip += item.rain || 0;
            });
            
            const days = Object.keys(dailyData).slice(0, 7);
            const dayNames = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
            const today = new Date().toDateString();
            
            let overallMin = Infinity, overallMax = -Infinity;
            days.forEach(day => {
                const d = dailyData[day];
                overallMin = Math.min(overallMin, Math.min(...d.temps));
                overallMax = Math.max(overallMax, Math.max(...d.temps));
            });
            
            let html = '';
            days.forEach((day, index) => {
                const d = dailyData[day];
                const date = new Date(day);
                const dayName = index === 0 && day === today ? 'Today' : dayNames[date.getDay()];
                const minTemp = Math.round(Math.min(...d.temps));
                const maxTemp = Math.round(Math.max(...d.temps));
                const icon = d.icons[Math.floor(d.icons.length / 2)];
                const precip = d.precip > 0 ? Math.round(d.precip) : 0;
                
                const range = overallMax - overallMin || 1;
                const barStart = ((minTemp - overallMin) / range) * 100;
                const barWidth = ((maxTemp - minTemp) / range) * 100;
                
                html += `
                    <div class="forecast-day-row">
                        <div class="forecast-day-name ${day === today ? 'today' : ''}">${dayName}</div>
                        <img src="https://openweathermap.org/img/wn/${icon}@2x.png" alt="" class="forecast-day-icon">
                        <div class="forecast-temp-bar">
                            <span class="forecast-temp-min">${minTemp}°</span>
                            <div class="forecast-bar-container">
                                <div class="forecast-bar" style="margin-left: ${barStart}%; width: ${Math.max(barWidth, 10)}%;"></div>
                            </div>
                            <span class="forecast-temp-max">${maxTemp}°</span>
                        </div>
                        <div class="forecast-precip">${precip > 0 ? `<i class="fas fa-tint"></i> ${precip}%` : ''}</div>
                    </div>
                `;
            });
            
            container.innerHTML = html;
        }
        
        // Load map data
        function loadMapData() {
            fetch('../api/weather-monitoring.php?action=map')
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.data) {
                        data.data.forEach(point => {
                            if (point.temp !== null) {
                                addWeatherMarker(
                                    point.lat, 
                                    point.lon, 
                                    point.name, 
                                    point.temp, 
                                    point.condition, 
                                    point.icon,
                                    point.windSpeed,
                                    point.windDeg
                                );
                            }
                        });
                        
                        // Create wind particles if wind flow is enabled
                        if (windFlowEnabled && markers.length > 0 && windFlowCanvas) {
                            createWindParticles();
                            if (!animationFrameId) {
                                startWindFlowAnimation();
                            }
                        }
                    }
                })
                .catch(error => console.error('Map data error:', error));
        }
        
        // Add weather marker
        function addWeatherMarker(lat, lon, name, temp, condition, icon, windSpeed, windDeg) {
            const iconHtml = `
                <div class="weather-marker-icon">
                    <img src="https://openweathermap.org/img/wn/${icon}@2x.png" alt="${condition}">
                    <div class="weather-marker-temp">${temp}°C</div>
                    <div class="weather-marker-city">${name}</div>
                </div>
            `;
            
            const customIcon = L.divIcon({
                html: iconHtml,
                className: '',
                iconSize: [80, 100],
                iconAnchor: [40, 50]
            });
            
            const marker = L.marker([lat, lon], { icon: customIcon }).addTo(map);
            
            // Convert wind speed from m/s to km/h for visualization
            // API returns m/s, we need km/h for better visualization
            const windSpeedKmh = (windSpeed || 0) * 3.6;
            
            markers.push({ 
                marker, 
                lat, 
                lon, 
                windSpeed: windSpeedKmh, // Store in km/h
                windDeg: windDeg || 0 
            });
        }
        
        // AI Analysis
        async function getAIWeatherAnalysis() {
            const container = document.getElementById('aiAnalysis');
            const statusBadge = document.getElementById('aiStatus');
            
            if (!window.currentWeatherData) {
                container.innerHTML = '<p style="color: #e74c3c;">Please wait for weather data to load.</p>';
                return;
            }
            
            // Check if API key is loaded
            if (!GEMINI_API_KEY) {
                // Try to load it again
                try {
                    const keyResponse = await fetch('../api/get-gemini-key.php');
                    const keyData = await keyResponse.json();
                    if (keyData.success && keyData.apiKey) {
                        GEMINI_API_KEY = keyData.apiKey;
                    } else {
                        container.innerHTML = '<p style="color: #e74c3c;">Google AI API key not configured. Please run setup-gemini-key.php to configure it.</p>';
                        statusBadge.textContent = 'Error';
                        statusBadge.className = 'ai-status error';
                        return;
                    }
                } catch (error) {
                    container.innerHTML = '<p style="color: #e74c3c;">Error loading API key. Please check setup.</p>';
                    statusBadge.textContent = 'Error';
                    statusBadge.className = 'ai-status error';
                    return;
                }
            }
            
            statusBadge.textContent = 'Analyzing...';
            statusBadge.className = 'ai-status loading';
            container.innerHTML = `
                <div style="text-align: center; padding: 2rem;">
                    <i class="fas fa-spinner fa-spin" style="font-size: 2rem; color: #8e44ad;"></i>
                    <p style="margin-top: 1rem; color: rgba(255,255,255,0.7);">AI is analyzing...</p>
                </div>
            `;
            
            try {
                const weather = window.currentWeatherData;
                const forecast = window.forecastData || [];
                const locationName = window.currentLocationName || 'Quezon City';
                
                const prompt = buildWeatherPrompt(weather, forecast, locationName);
                
                // Use PHP proxy to avoid CORS issues
                const response = await fetch('../api/gemini-proxy.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        apiKey: GEMINI_API_KEY,
                        prompt: prompt
                    })
                });
                
                if (!response.ok) {
                    const errorText = await response.text();
                    throw new Error(`API Error: ${response.status} - ${errorText}`);
                }
                
                const data = await response.json();
                
                if (!data.success) {
                    throw new Error(data.message || 'AI analysis failed');
                }
                
                const aiResponse = data.response;
                
                if (aiResponse) {
                    displayAIAnalysis(aiResponse);
                    statusBadge.textContent = 'Complete';
                    statusBadge.className = 'ai-status';
                } else {
                    throw new Error('No response from AI');
                }
            } catch (error) {
                console.error('AI Error:', error);
                statusBadge.textContent = 'Error';
                statusBadge.className = 'ai-status error';
                container.innerHTML = `
                    <div style="color: #e74c3c; padding: 1rem;">
                        <i class="fas fa-exclamation-triangle"></i> Error: ${error.message}
                    </div>
                    <button onclick="getAIWeatherAnalysis()" class="ai-analyze-btn" style="margin-top: 1rem;">
                        <i class="fas fa-redo"></i> Retry
                    </button>
                `;
            }
        }
        
        function buildWeatherPrompt(weather, forecast, locationName) {
            const temp = weather.main.temp;
            const humidity = weather.main.humidity;
            const condition = weather.weather[0].description;
            const windSpeed = (weather.wind.speed * 3.6).toFixed(1);
            
            let forecastSummary = '';
            if (forecast.length > 0) {
                const next24h = forecast.slice(0, 8);
                const maxTemp = Math.max(...next24h.map(f => f.temp));
                const minTemp = Math.min(...next24h.map(f => f.temp));
                forecastSummary = `Next 24 hours: ${Math.round(minTemp)}°C - ${Math.round(maxTemp)}°C.`;
            }
            
            return `You are an emergency weather analyst for ${locationName}, Philippines. Analyze:

CURRENT: Temp ${temp}°C, Humidity ${humidity}%, ${condition}, Wind ${windSpeed} km/h
${forecastSummary}

Provide analysis in this format:

**SUMMARY:**
[1-2 sentence summary]

**ALERTS:**
[List alerts or "None"]

**RECOMMENDATIONS:**
[3-5 action items]

**RISK LEVEL:**
[LOW/MEDIUM/HIGH] - [Brief explanation]

Keep concise and actionable.`;
        }
        
        function displayAIAnalysis(aiResponse) {
            const container = document.getElementById('aiAnalysis');
            const sections = parseAIResponse(aiResponse);
            
            let html = '<div class="ai-result">';
            
            if (sections.summary) {
                html += `
                    <div class="ai-result-section">
                        <div class="ai-result-title"><i class="fas fa-cloud-sun"></i> Summary</div>
                        <div class="ai-result-content">${sections.summary}</div>
                    </div>
                `;
            }
            
            if (sections.alerts && sections.alerts.toLowerCase() !== 'none') {
                html += `
                    <div class="ai-result-section">
                        <div class="ai-result-title"><i class="fas fa-exclamation-triangle"></i> Alerts</div>
                        <div class="ai-alert-item">${sections.alerts}</div>
                    </div>
                `;
            }
            
            if (sections.recommendations) {
                const recs = sections.recommendations.split(/[-•\n]/).filter(r => r.trim());
                html += `
                    <div class="ai-result-section">
                        <div class="ai-result-title"><i class="fas fa-tasks"></i> Recommendations</div>
                        <div class="ai-result-content">
                            ${recs.map(r => `
                                <div class="ai-recommendation">
                                    <i class="fas fa-check-circle"></i>
                                    <span>${r.trim()}</span>
                                </div>
                            `).join('')}
                        </div>
                    </div>
                `;
            }
            
            if (sections.riskLevel) {
                const riskColor = sections.riskLevel.includes('HIGH') ? '#e74c3c' : 
                                  sections.riskLevel.includes('MEDIUM') ? '#f39c12' : '#27ae60';
                html += `
                    <div class="ai-result-section">
                        <div class="ai-result-title"><i class="fas fa-shield-alt"></i> Risk Assessment</div>
                        <div class="ai-result-content" style="color: ${riskColor}; font-weight: 600;">
                            ${sections.riskLevel}
                        </div>
                    </div>
                `;
            }
            
            html += '</div>';
            html += `<button onclick="getAIWeatherAnalysis()" class="ai-analyze-btn" style="margin-top: 1rem;">
                <i class="fas fa-sync-alt"></i> Refresh Analysis
            </button>`;
            
            container.innerHTML = html;
        }
        
        function parseAIResponse(text) {
            const sections = { summary: '', alerts: '', recommendations: '', riskLevel: '' };
            const summaryMatch = text.match(/\*\*SUMMARY:\*\*\s*([\s\S]*?)(?=\*\*|$)/i);
            const alertsMatch = text.match(/\*\*ALERTS:\*\*\s*([\s\S]*?)(?=\*\*|$)/i);
            const recsMatch = text.match(/\*\*RECOMMENDATIONS:\*\*\s*([\s\S]*?)(?=\*\*|$)/i);
            const riskMatch = text.match(/\*\*RISK LEVEL:\*\*\s*([\s\S]*?)(?=\*\*|$)/i);
            
            if (summaryMatch) sections.summary = summaryMatch[1].trim();
            if (alertsMatch) sections.alerts = alertsMatch[1].trim();
            if (recsMatch) sections.recommendations = recsMatch[1].trim();
            if (riskMatch) sections.riskLevel = riskMatch[1].trim();
            
            return sections;
        }
        
        // Automated Weather Warning System
        function startAutomatedWarnings() {
            // Check weather every 5 minutes
            checkWeatherConditions();
            autoWarningInterval = setInterval(checkWeatherConditions, 5 * 60 * 1000); // 5 minutes
        }
        
        async function checkWeatherConditions() {
            try {
                // Get current weather for Quezon City
                const response = await fetch('../api/weather-monitoring.php?action=current&lat=14.6488&lon=121.0509');
                const data = await response.json();
                
                if (!data.success || !data.data) {
                    return;
                }
                
                const weather = data.data;
                const warnings = [];
                
                // Check for extreme heat
                if (weather.main.temp >= 35) {
                    warnings.push({
                        type: 'extreme_heat',
                        severity: 'high',
                        message: `Extreme Heat Alert: ${weather.main.temp.toFixed(1)}°C in Quezon City. Stay hydrated and avoid outdoor activities.`,
                        temp: weather.main.temp
                    });
                } else if (weather.main.temp >= 32) {
                    warnings.push({
                        type: 'heat',
                        severity: 'warning',
                        message: `High Temperature: ${weather.main.temp.toFixed(1)}°C in Quezon City. Take precautions.`,
                        temp: weather.main.temp
                    });
                }
                
                // Check for heavy rain
                if (weather.rain && weather.rain['1h'] > 10) {
                    warnings.push({
                        type: 'heavy_rain',
                        severity: 'high',
                        message: `Heavy Rain Alert: ${weather.rain['1h'].toFixed(1)}mm in the last hour in Quezon City. Risk of flooding.`,
                        rain: weather.rain['1h']
                    });
                } else if (weather.rain && weather.rain['1h'] > 5) {
                    warnings.push({
                        type: 'rain',
                        severity: 'warning',
                        message: `Rain Alert: ${weather.rain['1h'].toFixed(1)}mm in the last hour in Quezon City.`,
                        rain: weather.rain['1h']
                    });
                }
                
                // Check for strong winds
                const windSpeedKmh = (weather.wind.speed || 0) * 3.6;
                if (windSpeedKmh >= 50) {
                    warnings.push({
                        type: 'strong_wind',
                        severity: 'high',
                        message: `Strong Wind Alert: ${windSpeedKmh.toFixed(1)} km/h in Quezon City. Secure outdoor items.`,
                        windSpeed: windSpeedKmh
                    });
                } else if (windSpeedKmh >= 30) {
                    warnings.push({
                        type: 'wind',
                        severity: 'warning',
                        message: `Windy Conditions: ${windSpeedKmh.toFixed(1)} km/h in Quezon City.`,
                        windSpeed: windSpeedKmh
                    });
                }
                
                // Check for thunderstorms
                if (weather.weather && weather.weather[0].main === 'Thunderstorm') {
                    warnings.push({
                        type: 'thunderstorm',
                        severity: 'high',
                        message: `Thunderstorm Alert in Quezon City. Seek shelter immediately.`,
                        condition: weather.weather[0].description
                    });
                }
                
                // Check for high humidity + heat (heat index)
                if (weather.main.humidity >= 80 && weather.main.temp >= 30) {
                    const heatIndex = calculateHeatIndex(weather.main.temp, weather.main.humidity);
                    if (heatIndex >= 40) {
                        warnings.push({
                            type: 'heat_index',
                            severity: 'high',
                            message: `Dangerous Heat Index: ${heatIndex.toFixed(1)}°C in Quezon City. Extreme caution advised.`,
                            heatIndex: heatIndex
                        });
                    }
                }
                
                // Display warnings
                if (warnings.length > 0) {
                    displayWeatherWarning(warnings[0]); // Show most severe warning
                    sendWeatherWarning(warnings);
                } else {
                    hideWeatherWarning();
                }
                
            } catch (error) {
                console.error('Error checking weather conditions:', error);
            }
        }
        
        // Calculate Heat Index
        function calculateHeatIndex(tempC, humidity) {
            // Convert to Fahrenheit for calculation
            const tempF = (tempC * 9/5) + 32;
            
            // Heat Index formula (Rothfusz equation)
            const hi = -42.379 + 
                       2.04901523 * tempF + 
                       10.14333127 * humidity - 
                       0.22475541 * tempF * humidity - 
                       6.83783e-3 * tempF * tempF - 
                       5.481717e-2 * humidity * humidity + 
                       1.22874e-3 * tempF * tempF * humidity + 
                       8.5282e-4 * tempF * humidity * humidity - 
                       1.99e-6 * tempF * tempF * humidity * humidity;
            
            // Convert back to Celsius
            return (hi - 32) * 5/9;
        }
        
        // Display weather warning on map
        function displayWeatherWarning(warning) {
            const statusDiv = document.getElementById('autoWarningStatus');
            const messageSpan = document.getElementById('warningMessage');
            
            if (!statusDiv || !messageSpan) return;
            
            statusDiv.className = `auto-warning-status ${warning.severity === 'high' ? '' : 'warning'}`;
            messageSpan.textContent = warning.message;
            statusDiv.style.display = 'flex';
            
            // Auto-hide after 10 seconds, but keep checking
            setTimeout(() => {
                if (statusDiv) {
                    statusDiv.style.opacity = '0.7';
                }
            }, 10000);
        }
        
        // Hide weather warning
        function hideWeatherWarning() {
            const statusDiv = document.getElementById('autoWarningStatus');
            if (statusDiv) {
                statusDiv.style.display = 'none';
                statusDiv.style.opacity = '1';
            }
        }
        
        // Send weather warning to server
        async function sendWeatherWarning(warnings) {
            // Prevent duplicate warnings (only send once per hour per type)
            const now = Date.now();
            const warningKey = warnings[0].type;
            
            if (lastWarningTime && lastWarningTime[warningKey] && (now - lastWarningTime[warningKey]) < 3600000) {
                return; // Already sent warning for this type in the last hour
            }
            
            if (!lastWarningTime) {
                lastWarningTime = {};
            }
            lastWarningTime[warningKey] = now;
            
            try {
                const response = await fetch('../api/weather-warning.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        location: 'Quezon City',
                        lat: 14.6488,
                        lon: 121.0509,
                        warnings: warnings,
                        timestamp: new Date().toISOString()
                    })
                });
                
                const result = await response.json();
                if (result.success) {
                    console.log('Weather warning sent successfully');
                }
            } catch (error) {
                console.error('Error sending weather warning:', error);
            }
        }
        
        // Tab switching
        document.addEventListener('DOMContentLoaded', function() {
            initMap();
            
            document.querySelectorAll('.weather-tab').forEach(tab => {
                tab.addEventListener('click', function() {
                    document.querySelectorAll('.weather-tab').forEach(t => t.classList.remove('active'));
                    this.classList.add('active');
                    
                    const tabType = this.getAttribute('data-tab');
                    if (window.forecastData && window.forecastData.length > 0) {
                        renderChartByType(tabType, window.forecastData);
                    }
                });
            });
        });
        
        function renderChartByType(type, forecastData) {
            const ctx = document.getElementById('hourlyTempChart');
            if (!ctx || !forecastData || forecastData.length === 0) return;
            
            const hourlyData = forecastData.slice(0, 8);
            const labels = hourlyData.map(item => {
                const date = new Date(item.datetime);
                return date.getHours() + ':00';
            });
            
            let data, borderColor, backgroundColor, label;
            
            switch(type) {
                case 'precipitation':
                    data = hourlyData.map(item => item.rain || 0);
                    borderColor = '#3498db';
                    backgroundColor = 'rgba(52, 152, 219, 0.2)';
                    label = 'mm';
                    break;
                case 'wind':
                    data = hourlyData.map(item => (item.wind_speed || 0) * 3.6);
                    borderColor = '#95a5a6';
                    backgroundColor = 'rgba(149, 165, 166, 0.2)';
                    label = 'km/h';
                    break;
                default:
                    data = hourlyData.map(item => Math.round(item.temp));
                    borderColor = '#f39c12';
                    backgroundColor = 'rgba(243, 156, 18, 0.1)';
                    label = '°C';
            }
            
            if (window.hourlyChart) {
                window.hourlyChart.destroy();
            }
            
            window.hourlyChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        data: data,
                        borderColor: borderColor,
                        backgroundColor: backgroundColor,
                        borderWidth: 2,
                        fill: true,
                        tension: 0.4,
                        pointRadius: 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: { callbacks: { label: (ctx) => ctx.raw + ' ' + label } }
                    },
                    scales: {
                        x: { grid: { display: false } },
                        y: { beginAtZero: type === 'precipitation', grid: { color: 'rgba(0,0,0,0.05)' } }
                    }
                }
            });
        }
    </script>
    
    <?php include 'includes/admin-footer.php'; ?>
</body>
</html>
