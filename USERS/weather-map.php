<?php
// Include centralized session configuration - MUST be first
require_once __DIR__ . '/../session-config.php';

$assetBase = '../ADMIN/header/';
$current = 'weather-map.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Weather Map</title>
    <link rel="icon" type="image/x-icon" href="<?= $assetBase ?>images/favicon.ico">
    <link rel="stylesheet" href="<?= $assetBase ?>css/global.css">
    <link rel="stylesheet" href="<?= $assetBase ?>css/buttons.css">
    <link rel="stylesheet" href="<?= $assetBase ?>css/hero.css">
    <link rel="stylesheet" href="../ADMIN/sidebar/css/global.css">
    <link rel="stylesheet" href="../ADMIN/sidebar/css/sidebar.css">
    <link rel="stylesheet" href="../ADMIN/sidebar/css/content.css">
    <link rel="stylesheet" href="../ADMIN/sidebar/css/admin-header.css">
    <link rel="stylesheet" href="../ADMIN/sidebar/css/buttons.css">
    <link rel="stylesheet" href="css/user.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <script src="js/translations.js"></script>
    <script src="js/language-manager.js"></script>
    <script src="js/global-translator.js"></script>
    <script src="js/language-selector-modal.js"></script>
    <script src="js/language-sync.js"></script>
    <script>
        // Ensure sidebar functions are available before translation scripts interfere
        (function() {
            if (typeof window.sidebarToggle !== 'function') {
                window.sidebarToggle = function() {
                    const sidebar = document.getElementById('sidebar');
                    const sidebarOverlay = document.getElementById('sidebarOverlay');
                    if (sidebar) {
                        sidebar.classList.toggle('sidebar-open');
                        if (sidebarOverlay) {
                            sidebarOverlay.classList.toggle('sidebar-overlay-open');
                        }
                        document.body.classList.toggle('sidebar-open');
                    }
                };
            }
            if (typeof window.sidebarClose !== 'function') {
                window.sidebarClose = function() {
                    const sidebar = document.getElementById('sidebar');
                    const sidebarOverlay = document.getElementById('sidebarOverlay');
                    if (sidebar) {
                        sidebar.classList.remove('sidebar-open');
                        if (sidebarOverlay) {
                            sidebarOverlay.classList.remove('sidebar-overlay-open');
                        }
                        document.body.classList.remove('sidebar-open');
                    }
                };
            }
        })();
    </script>
</head>
<body class="user-admin-header">
    <?php include 'includes/user-global-header.php'; ?>

    <main class="main-content" style="padding-top: 60px;">
        <div class="main-container">
            <div class="sub-container weather-map-card">
                <div class="weather-map-header">
                    <div>
                        <h1><i class="fas fa-cloud-sun-rain"></i> Weather Map</h1>
                        <p>Live weather overlays for Quezon City and nearby areas.</p>
                    </div>
                    <div class="weather-map-actions">
                        <button type="button" class="btn btn-secondary" data-layer="temp"><i class="fas fa-thermometer-half"></i> Temperature</button>
                        <button type="button" class="btn btn-secondary" data-layer="precip"><i class="fas fa-cloud-showers-heavy"></i> Precipitation</button>
                        <button type="button" class="btn btn-secondary" data-layer="wind"><i class="fas fa-wind"></i> Wind</button>
                        <button type="button" class="btn btn-secondary" data-layer="clouds"><i class="fas fa-cloud"></i> Clouds</button>
                        <button type="button" class="btn btn-secondary" data-layer="quakes"><i class="fas fa-mountain"></i> Earthquakes</button>
                    </div>
                </div>
                <div id="userWeatherMap" class="weather-map-canvas"></div>
                <div class="weather-map-note" id="weatherMapNote">Tip: toggle layers to see different weather conditions.</div>

                <div class="weather-analytics-grid weather-analytics-layout">
                    <div class="weather-analytics-stack">
                    <div class="weather-analytics-card">
                        <h3><i class="fas fa-cloud-sun"></i> Current Weather</h3>
                        <div class="analytics-row">
                            <div class="analytics-metric">
                                <span class="metric-label">Temperature</span>
                                <span class="metric-value" id="waTemp">--&deg;C</span>
                            </div>
                            <div class="analytics-metric">
                                <span class="metric-label">Humidity</span>
                                <span class="metric-value" id="waHumidity">--%</span>
                            </div>
                            <div class="analytics-metric">
                                <span class="metric-label">Wind</span>
                                <span class="metric-value" id="waWind">-- km/h</span>
                            </div>
                        </div>
                        <div class="analytics-sub" id="waCondition">Loading weather data...</div>
                    </div>

                    <div class="weather-analytics-card">
                        <h3><i class="fas fa-mountain"></i> Earthquake Activity</h3>
                        <div class="analytics-row">
                            <div class="analytics-metric">
                                <span class="metric-label">Events (7d)</span>
                                <span class="metric-value" id="eqCount">--</span>
                            </div>
                            <div class="analytics-metric">
                                <span class="metric-label">Max Magnitude</span>
                                <span class="metric-value" id="eqMax">--</span>
                            </div>
                            <div class="analytics-metric">
                                <span class="metric-label">Latest</span>
                                <span class="metric-value" id="eqLatest">--</span>
                            </div>
                        </div>
                        <div class="analytics-sub" id="eqSummary">Loading earthquake data...</div>
                    </div>

                    </div>

                    <div class="weather-analytics-card weather-analytics-ai">
                        <h3><i class="fas fa-robot"></i> AI Analytics</h3>
                        <p class="analytics-sub" id="aiSummary">Run AI analysis to get a concise risk summary for Quezon City.</p>
                        <button type="button" class="btn btn-primary" id="runAiSummary"><i class="fas fa-bolt"></i> Generate AI Summary</button>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const map = L.map('userWeatherMap').setView([14.6760, 121.0437], 11);
            const baseLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 18,
                attribution: '&copy; OpenStreetMap contributors'
            });
            baseLayer.addTo(map);

            const layerButtons = document.querySelectorAll('.weather-map-actions [data-layer]');
            const overlays = {};
            let activeOverlay = null;
            const quakeLayer = L.layerGroup();
            let quakeLoaded = false;
            let latestWeather = null;
            let earthquakeStats = null;

            function setActiveButton(target) {
                layerButtons.forEach(btn => btn.classList.remove('active'));
                if (target) target.classList.add('active');
            }

            function loadOverlay(type, apiKey) {
                const key = type;
                const mapType = {
                    temp: 'temp_new',
                    precip: 'precipitation_new',
                    wind: 'wind_new',
                    clouds: 'clouds_new'
                }[type];

                if (!mapType || !apiKey) return null;

                if (!overlays[key]) {
                    overlays[key] = L.tileLayer(`https://tile.openweathermap.org/map/${mapType}/{z}/{x}/{y}.png?appid=${apiKey}`, {
                        maxZoom: 18,
                        opacity: 0.75
                    });
                }
                return overlays[key];
            }

            function loadQuezonBoundary() {
                fetch('../ADMIN/api/quezon-city.geojson', { cache: 'force-cache' })
                    .then(res => res.json())
                    .then(geojson => {
                        L.geoJSON(geojson, {
                            style: {
                                color: '#3a7675',
                                weight: 3,
                                fillOpacity: 0.05
                            }
                        }).addTo(map);
                    })
                    .catch(() => {
                        const note = document.getElementById('weatherMapNote');
                        if (note) {
                            note.textContent = 'Boundary data unavailable. Map still shows weather layers.';
                        }
                    });
            }

            function updateWeatherAnalytics(weather) {
                latestWeather = weather;
                const tempEl = document.getElementById('waTemp');
                const humidityEl = document.getElementById('waHumidity');
                const windEl = document.getElementById('waWind');
                const conditionEl = document.getElementById('waCondition');
                if (!weather || !tempEl || !humidityEl || !windEl || !conditionEl) return;

                const temp = Math.round(weather.main?.temp ?? 0);
                const humidity = Math.round(weather.main?.humidity ?? 0);
                const wind = Math.round((weather.wind?.speed ?? 0) * 3.6);
                const description = weather.weather?.[0]?.description || 'Current conditions';

                tempEl.textContent = `${temp}°C`;
                humidityEl.textContent = `${humidity}%`;
                windEl.textContent = `${wind} km/h`;
                conditionEl.textContent = description.charAt(0).toUpperCase() + description.slice(1);
            }

            function loadWeatherSummary() {
                fetch('../ADMIN/api/weather-monitoring.php?action=current&lat=14.6760&lon=121.0437')
                    .then(res => res.json())
                    .then(data => {
                        if (data && data.success && data.data) {
                            updateWeatherAnalytics(data.data);
                        }
                    })
                    .catch(() => {});
            }

            function updateEarthquakeAnalytics(stats) {
                earthquakeStats = stats;
                const countEl = document.getElementById('eqCount');
                const maxEl = document.getElementById('eqMax');
                const latestEl = document.getElementById('eqLatest');
                const summaryEl = document.getElementById('eqSummary');
                if (!countEl || !maxEl || !latestEl || !summaryEl) return;

                countEl.textContent = stats.count ?? '--';
                maxEl.textContent = stats.maxMag ?? '--';
                latestEl.textContent = stats.latestTime ?? '--';
                summaryEl.textContent = stats.summary ?? 'No recent earthquakes detected.';
            }

            function loadEarthquakes() {
                if (quakeLoaded) return;
                const endTime = new Date();
                const startTime = new Date(endTime.getTime() - 7 * 24 * 60 * 60 * 1000);
                const url = `https://earthquake.usgs.gov/fdsnws/event/1/query?format=geojson&starttime=${startTime.toISOString()}&minmagnitude=3&maxlatitude=21.5&minlatitude=4.0&maxlongitude=127.5&minlongitude=115.5`;

                fetch(url)
                    .then(res => res.json())
                    .then(data => {
                        const features = data.features || [];
                        let maxMag = 0;
                        let latest = null;
                        features.forEach(feature => {
                            const coords = feature.geometry?.coordinates || [];
                            const mag = feature.properties?.mag || 0;
                            const time = feature.properties?.time || 0;
                            const place = feature.properties?.place || 'Unknown location';
                            const lat = coords[1];
                            const lon = coords[0];
                            if (mag > maxMag) maxMag = mag;
                            if (!latest || time > latest.time) latest = { time, place, mag };

                            if (lat && lon) {
                                const marker = L.circleMarker([lat, lon], {
                                    radius: Math.max(4, Math.min(12, mag * 2)),
                                    color: '#ff6b6b',
                                    fillColor: '#ff8787',
                                    fillOpacity: 0.8,
                                    weight: 1
                                }).bindPopup(`<strong>M ${mag.toFixed(1)}</strong><br>${place}`);
                                quakeLayer.addLayer(marker);
                            }
                        });

                        quakeLoaded = true;
                        const latestTime = latest ? new Date(latest.time).toLocaleString() : '--';
                        updateEarthquakeAnalytics({
                            count: features.length,
                            maxMag: maxMag ? maxMag.toFixed(1) : '--',
                            latestTime: latestTime,
                            summary: latest ? `Latest M ${latest.mag?.toFixed(1)} at ${latest.place}.` : 'No recent earthquakes detected.'
                        });
                    })
                    .catch(() => {
                        updateEarthquakeAnalytics({
                            count: '--',
                            maxMag: '--',
                            latestTime: '--',
                            summary: 'Unable to load earthquake data.'
                        });
                    });
            }

            function runAiAnalytics() {
                const aiSummary = document.getElementById('aiSummary');
                const btn = document.getElementById('runAiSummary');
                if (!aiSummary || !btn) return;

                btn.disabled = true;
                aiSummary.textContent = 'Analyzing current conditions...';

                const weatherDesc = latestWeather
                    ? `Weather: ${latestWeather.weather?.[0]?.description || 'clear'}, ${Math.round(latestWeather.main?.temp ?? 0)}°C, humidity ${latestWeather.main?.humidity ?? 0}%, wind ${(latestWeather.wind?.speed ?? 0) * 3.6} km/h.`
                    : 'Weather data unavailable.';
                const quakeDesc = earthquakeStats
                    ? `Earthquakes (7d): ${earthquakeStats.count} events, max magnitude ${earthquakeStats.maxMag}, latest ${earthquakeStats.latestTime}.`
                    : 'Earthquake data unavailable.';

                const prompt = `You are an emergency risk analyst. Provide a concise, calm 3-4 sentence summary for Quezon City based on:\n${weatherDesc}\n${quakeDesc}\nInclude any suggested precautions if needed.`;

                fetch('../ADMIN/api/gemini-proxy.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ prompt })
                })
                    .then(res => res.json())
                    .then(data => {
                        if (data && data.success && data.response) {
                            aiSummary.textContent = data.response.trim();
                        } else {
                            aiSummary.textContent = data?.message || 'AI analysis unavailable.';
                        }
                    })
                    .catch(() => {
                        aiSummary.textContent = 'AI analysis unavailable.';
                    })
                    .finally(() => {
                        btn.disabled = false;
                    });
            }

            loadQuezonBoundary();
            loadWeatherSummary();

            const aiBtn = document.getElementById('runAiSummary');
            if (aiBtn) {
                aiBtn.addEventListener('click', runAiAnalytics);
            }

            fetch('../ADMIN/api/weather-monitoring.php?action=getApiKey')
                .then(response => response.json())
                .then(data => {
                    if (!data || !data.success || !data.apiKey) {
                        const note = document.getElementById('weatherMapNote');
                        if (note) {
                            note.textContent = 'Weather layers unavailable. API key not configured.';
                        }
                        return;
                    }

                    layerButtons.forEach(btn => {
                        btn.addEventListener('click', function() {
                            const type = btn.getAttribute('data-layer');
                            if (type === 'quakes') {
                                if (activeOverlay && map.hasLayer(activeOverlay)) {
                                    map.removeLayer(activeOverlay);
                                }
                                map.addLayer(quakeLayer);
                                activeOverlay = quakeLayer;
                                setActiveButton(btn);
                                loadEarthquakes();
                                return;
                            }

                            const overlay = loadOverlay(type, data.apiKey);
                            if (!overlay) return;

                            if (activeOverlay && map.hasLayer(activeOverlay)) {
                                map.removeLayer(activeOverlay);
                            }
                            overlay.addTo(map);
                            activeOverlay = overlay;
                            setActiveButton(btn);
                        });
                    });

                    const defaultBtn = document.querySelector('.weather-map-actions [data-layer="temp"]');
                    if (defaultBtn) defaultBtn.click();
                })
                .catch(() => {
                    const note = document.getElementById('weatherMapNote');
                    if (note) {
                        note.textContent = 'Weather layers unavailable. Please try again later.';
                    }
                });
        });
    </script>
</body>
</html>
