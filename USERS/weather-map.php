<?php
require_once __DIR__ . '/../session-config.php';

$assetBase = '../ADMIN/header/';
$current = 'weather-map.php';
$pageTitle = 'Weather Bulletins and Map';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <link rel="icon" type="image/x-icon" href="<?= $assetBase ?>images/favicon.ico">
    <link rel="stylesheet" href="../ADMIN/sidebar/css/global.css">
    <link rel="stylesheet" href="../ADMIN/sidebar/css/sidebar.css">
    <link rel="stylesheet" href="../ADMIN/sidebar/css/content.css">
    <link rel="stylesheet" href="../ADMIN/sidebar/css/admin-header.css">
    <link rel="stylesheet" href="../ADMIN/sidebar/css/buttons.css">
    <link rel="stylesheet" href="css/user.css">
    <link rel="stylesheet" href="css/environment-bulletins.css?v=<?= filemtime(__DIR__ . '/css/environment-bulletins.css') ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <script src="js/translations.js"></script>
    <script src="js/language-manager.js"></script>
    <script src="js/global-translator.js"></script>
    <script src="js/language-selector-modal.js"></script>
    <script src="js/language-sync.js"></script>
</head>
<body class="user-admin-header">
    <?php include 'includes/user-global-header.php'; ?>

    <main class="main-content bulletin-page">
        <div class="main-container">
            <header class="bulletin-hero">
                <div>
                    <div class="bulletin-eyebrow"><i class="fas fa-satellite-dish"></i> PAGASA monitoring</div>
                    <h1>Weather Bulletins and Live Map</h1>
                    <p>Official weather advisories and current Quezon City conditions in one easy-to-read bulletin. Read the safety guidance first, then use the map to check rain, wind, clouds, or temperature.</p>
                </div>
                <div class="bulletin-live"><span class="bulletin-live-dot"></span> Live updates active</div>
            </header>

            <section class="bulletin-metrics" aria-label="Current Quezon City weather">
                <div class="bulletin-metric"><span>Temperature</span><strong id="weatherTemperature">--°C</strong></div>
                <div class="bulletin-metric"><span>Humidity</span><strong id="weatherHumidity">--%</strong></div>
                <div class="bulletin-metric"><span>Wind</span><strong id="weatherWind">-- km/h</strong></div>
                <div class="bulletin-metric"><span>Condition</span><strong id="weatherCondition" style="font-size:1rem">Loading...</strong></div>
            </section>

            <section class="bulletin-board" aria-labelledby="weatherBoardTitle">
                <div class="bulletin-toolbar">
                    <div class="bulletin-toolbar-title">
                        <i class="fas fa-bullhorn"></i>
                        <div>
                            <h2 id="weatherBoardTitle">PAGASA Weather Bulletin Board</h2>
                            <small id="weatherLastUpdate">Checking for active advisories...</small>
                        </div>
                    </div>
                    <div class="bulletin-actions">
                        <button class="bulletin-button" id="refreshWeather" type="button"><i class="fas fa-rotate"></i> Refresh</button>
                        <a class="bulletin-button primary" href="https://www.pagasa.dost.gov.ph/" target="_blank" rel="noopener"><i class="fas fa-arrow-up-right-from-square"></i> Official PAGASA</a>
                    </div>
                </div>
                <div class="bulletin-feed" id="weatherBulletinFeed" aria-live="polite">
                    <div class="bulletin-loading"><i class="fas fa-circle-notch fa-spin"></i>Retrieving the latest PAGASA advisories...</div>
                </div>
            </section>

            <section class="bulletin-map-panel" aria-labelledby="weatherMapTitle">
                <div class="bulletin-toolbar">
                    <div class="bulletin-toolbar-title">
                        <i class="fas fa-map-location-dot"></i>
                        <div><h2 id="weatherMapTitle">Quezon City Weather Map</h2><small>Select one layer at a time for a clearer view.</small></div>
                    </div>
                    <div class="bulletin-layer-actions weather-map-actions" role="group" aria-label="Weather map layers">
                        <button class="bulletin-button active" type="button" data-layer="temp"><i class="fas fa-temperature-half"></i> Temperature</button>
                        <button class="bulletin-button" type="button" data-layer="precip"><i class="fas fa-cloud-showers-heavy"></i> Rain</button>
                        <button class="bulletin-button" type="button" data-layer="wind"><i class="fas fa-wind"></i> Wind</button>
                        <button class="bulletin-button" type="button" data-layer="clouds"><i class="fas fa-cloud"></i> Clouds</button>
                    </div>
                </div>
                <div id="userWeatherMap" class="bulletin-map-canvas" aria-label="Live weather map of Quezon City"></div>
                <div class="bulletin-map-note" id="weatherMapNote"><i class="fas fa-circle-info"></i> Map centered on Quezon City. Weather overlays require an active OpenWeather configuration.</div>
            </section>

            <section class="bulletin-safety-panel" aria-labelledby="weatherSafetyTitle">
                <div class="bulletin-toolbar">
                    <div class="bulletin-toolbar-title"><i class="fas fa-shield-heart"></i><h2 id="weatherSafetyTitle">Weather Safety Reminders</h2></div>
                </div>
                <div class="bulletin-safety-grid">
                    <div class="bulletin-safety-item"><i class="fas fa-mobile-screen-button"></i><strong>Keep alerts available.</strong> Charge your phone and keep mobile data or a radio ready.</div>
                    <div class="bulletin-safety-item"><i class="fas fa-water"></i><strong>Avoid floodwater.</strong> Never walk or drive through a flooded road.</div>
                    <div class="bulletin-safety-item"><i class="fas fa-house"></i><strong>Prepare early.</strong> Secure loose items and know your nearest evacuation site.</div>
                </div>
            </section>
        </div>
    </main>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        (() => {
            'use strict';

            const feed = document.getElementById('weatherBulletinFeed');
            const refreshButton = document.getElementById('refreshWeather');
            const updateLabel = document.getElementById('weatherLastUpdate');
            const layerButtons = [...document.querySelectorAll('.weather-map-actions [data-layer]')];
            const map = L.map('userWeatherMap', { scrollWheelZoom: false }).setView([14.6760, 121.0437], 11);
            const overlays = {};
            let activeOverlay = null;

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 18,
                attribution: '&copy; OpenStreetMap contributors'
            }).addTo(map);

            fetch('../ADMIN/api/quezon-city.geojson', { cache: 'force-cache' })
                .then(response => response.json())
                .then(data => L.geoJSON(data, { style: { color: '#3e7f7d', weight: 3, fillOpacity: .06 } }).addTo(map))
                .catch(() => {});

            function escapeHtml(value) {
                return String(value ?? '').replace(/[&<>'"]/g, char => ({
                    '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#039;', '"': '&quot;'
                })[char]);
            }

            function severityStyle(value) {
                const severity = String(value || 'Medium').toLowerCase();
                if (severity === 'critical') return { color: '#b42318', label: 'Critical' };
                if (severity === 'high') return { color: '#c2410c', label: 'High' };
                if (severity === 'medium') return { color: '#b7791f', label: 'Moderate' };
                return { color: '#2f855a', label: 'Advisory' };
            }

            function citizenActions(severity) {
                const actions = [
                    'Monitor PAGASA and Quezon City government updates.',
                    'Charge phones and prepare water, medicine, and a flashlight.'
                ];
                if (['high', 'critical'].includes(String(severity).toLowerCase())) {
                    actions.push('Stay away from waterways and be ready to move when officials advise evacuation.');
                } else {
                    actions.push('Plan travel carefully and bring rain protection when needed.');
                }
                return actions;
            }

            function renderBulletins(data) {
                updateLabel.textContent = `Last checked ${new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}`;

                // Never display the parser's demonstration fallback as a real public warning.
                if (data.is_mock) {
                    feed.innerHTML = '<div class="bulletin-error"><i class="fas fa-satellite-dish"></i><strong>The live PAGASA feed is temporarily unavailable.</strong><br>No demonstration or sample warning is being shown. Please check the official PAGASA website.</div>';
                    return;
                }

                if (!Array.isArray(data.bulletins) || data.bulletins.length === 0) {
                    feed.innerHTML = '<div class="bulletin-empty"><i class="fas fa-sun"></i><strong>No active tropical cyclone bulletin.</strong><br>Continue checking for local rain and thunderstorm advisories.</div>';
                    return;
                }

                feed.innerHTML = data.bulletins.map(item => {
                    const impact = item.quezon_city_impact || {};
                    const level = severityStyle(impact.severity);
                    const actions = citizenActions(impact.severity);
                    const officialLink = item.link
                        ? `<a class="bulletin-button" href="${escapeHtml(item.link)}" target="_blank" rel="noopener"><i class="fas fa-file-pdf"></i> View official bulletin</a>` : '';
                    return `<article class="bulletin-card" style="--bulletin-accent:${level.color}">
                        <div class="bulletin-card-head">
                            <div><div class="bulletin-source"><i class="fas fa-satellite-dish"></i> PAGASA weather bulletin</div><h3>${escapeHtml(item.title || 'Weather Advisory')}</h3></div>
                            <div class="bulletin-issued"><i class="far fa-clock"></i> ${escapeHtml(item.issued_at || 'Issue time unavailable')}</div>
                        </div>
                        <div class="bulletin-card-body">
                            <p class="bulletin-description">${escapeHtml(item.description || 'Please open the official bulletin for complete details.')}</p>
                            <div class="bulletin-impact">
                                <div class="bulletin-impact-head"><span class="bulletin-impact-title"><i class="fas fa-location-dot"></i> What this means for Quezon City</span><span class="bulletin-badge">${level.label}</span></div>
                                <p>${escapeHtml(impact.summary || 'Monitor local announcements for conditions affecting Quezon City.')}</p>
                                <ul class="bulletin-steps">${actions.map(action => `<li>${escapeHtml(action)}</li>`).join('')}</ul>
                            </div>
                            <div class="bulletin-card-actions">${officialLink}</div>
                        </div>
                    </article>`;
                }).join('');
            }

            async function loadBulletins() {
                refreshButton.disabled = true;
                refreshButton.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i> Checking';
                try {
                    const response = await fetch('../ADMIN/api/pagasa-bulletin-parser.php', { cache: 'no-store' });
                    const data = await response.json();
                    if (!response.ok || !data.success) throw new Error(data.message || 'PAGASA bulletin service unavailable.');
                    renderBulletins(data);
                } catch (error) {
                    feed.innerHTML = `<div class="bulletin-error"><i class="fas fa-triangle-exclamation"></i><strong>Weather bulletins could not be loaded.</strong><br>${escapeHtml(error.message)}<div style="margin-top:1rem"><button class="bulletin-button" type="button" onclick="document.getElementById('refreshWeather').click()">Try again</button></div></div>`;
                    updateLabel.textContent = 'Live bulletin feed unavailable';
                } finally {
                    refreshButton.disabled = false;
                    refreshButton.innerHTML = '<i class="fas fa-rotate"></i> Refresh';
                }
            }

            async function loadCurrentWeather() {
                try {
                    const response = await fetch('../ADMIN/api/weather-monitoring.php?action=current&lat=14.6760&lon=121.0437');
                    const result = await response.json();
                    if (!result.success || !result.data) throw new Error('Current weather unavailable');
                    const weather = result.data;
                    document.getElementById('weatherTemperature').textContent = `${Math.round(weather.main?.temp ?? 0)}°C`;
                    document.getElementById('weatherHumidity').textContent = `${Math.round(weather.main?.humidity ?? 0)}%`;
                    document.getElementById('weatherWind').textContent = `${Math.round((weather.wind?.speed ?? 0) * 3.6)} km/h`;
                    const condition = weather.weather?.[0]?.description || 'Current conditions';
                    document.getElementById('weatherCondition').textContent = condition.charAt(0).toUpperCase() + condition.slice(1);
                } catch (_) {
                    document.getElementById('weatherCondition').textContent = 'Unavailable';
                }
            }

            async function initializeLayers() {
                try {
                    const response = await fetch('../ADMIN/api/weather-monitoring.php?action=getApiKey');
                    const result = await response.json();
                    if (!result.success || !result.apiKey) throw new Error('Weather overlays are not configured.');
                    const types = { temp: 'temp_new', precip: 'precipitation_new', wind: 'wind_new', clouds: 'clouds_new' };
                    Object.entries(types).forEach(([key, type]) => {
                        overlays[key] = L.tileLayer(`https://tile.openweathermap.org/map/${type}/{z}/{x}/{y}.png?appid=${result.apiKey}`, { maxZoom: 18, opacity: .72 });
                    });
                    activeOverlay = overlays.temp.addTo(map);
                    layerButtons.forEach(button => button.addEventListener('click', () => {
                        if (activeOverlay) map.removeLayer(activeOverlay);
                        activeOverlay = overlays[button.dataset.layer].addTo(map);
                        layerButtons.forEach(item => item.classList.toggle('active', item === button));
                    }));
                } catch (error) {
                    document.getElementById('weatherMapNote').innerHTML = `<i class="fas fa-circle-info"></i> ${escapeHtml(error.message)} The base map remains available.`;
                    layerButtons.forEach(button => { button.disabled = true; });
                }
            }

            refreshButton.addEventListener('click', () => { loadBulletins(); loadCurrentWeather(); });
            loadBulletins();
            loadCurrentWeather();
            initializeLayers();
            window.setInterval(loadBulletins, 60000);
            window.setInterval(loadCurrentWeather, 300000);
        })();
    </script>
</body>
</html>
