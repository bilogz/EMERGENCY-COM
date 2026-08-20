<?php
require_once __DIR__ . '/../session-config.php';
header('Content-Type: text/html; charset=UTF-8');

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
                <div class="bulletin-metric"><span>Temperature</span><strong id="weatherTemperature">--&deg;C</strong></div>
                <div class="bulletin-metric"><span>Humidity</span><strong id="weatherHumidity">--%</strong></div>
                <div class="bulletin-metric"><span>Wind</span><strong id="weatherWind">-- km/h</strong></div>
                <div class="bulletin-metric"><span>Condition</span><strong id="weatherCondition" style="font-size:1rem">Loading...</strong></div>
            </section>

            <section class="forecast-panel" aria-labelledby="forecastTitle">
                <div class="forecast-card">
                    <div class="forecast-current">
                        <div>
                            <div class="bulletin-eyebrow"><i class="fas fa-cloud-sun-rain"></i> Forecast source: Open-Meteo</div>
                            <h2 id="forecastTitle">Quezon City Weather</h2>
                            <div class="forecast-temp-line"><i id="forecastCurrentIcon" class="fas fa-cloud"></i><strong id="forecastCurrentTemp">--&deg;C</strong></div>
                            <p id="forecastCurrentCondition">Loading forecast...</p>
                        </div>
                        <dl class="forecast-current-stats">
                            <div><dt>Feels like</dt><dd id="forecastFeelsLike">--&deg;C</dd></div>
                            <div><dt>Rain chance</dt><dd id="forecastRainChance">--%</dd></div>
                            <div><dt>Rainfall</dt><dd id="forecastRainfall">-- mm</dd></div>
                            <div><dt>Wind gust</dt><dd id="forecastWindGust">-- km/h</dd></div>
                            <div><dt>Visibility</dt><dd id="forecastVisibility">-- km</dd></div>
                        </dl>
                    </div>
                    <div class="forecast-tabs" role="tablist" aria-label="Forecast chart metric">
                        <button class="forecast-tab active" type="button" data-forecast-tab="temperature">Temperature</button>
                        <button class="forecast-tab" type="button" data-forecast-tab="rain">Rain</button>
                        <button class="forecast-tab" type="button" data-forecast-tab="wind">Wind</button>
                    </div>
                    <div class="forecast-chart-wrap">
                        <svg id="forecastChart" class="forecast-chart" viewBox="0 0 720 140" role="img" aria-label="Hourly forecast chart"></svg>
                    </div>
                    <div id="hourlyForecastStrip" class="hourly-forecast-strip" aria-label="Hourly forecast"></div>
                    <div class="daily-forecast-block">
                        <h3>7-Day Forecast</h3>
                        <div id="dailyForecastList" class="daily-forecast-list"></div>
                    </div>
                </div>
            </section>

            <section class="flood-watch-panel" aria-labelledby="floodWatchTitle">
                <div class="bulletin-toolbar">
                    <div class="bulletin-toolbar-title">
                        <i class="fas fa-water"></i>
                        <div>
                            <h2 id="floodWatchTitle">Flood Watch - Quezon City</h2>
                            <small>Official flood advisories are separated from forecast-based risk.</small>
                        </div>
                    </div>
                    <a class="bulletin-button primary" href="https://www.pagasa.dost.gov.ph/flood#flood-information" target="_blank" rel="noopener"><i class="fas fa-arrow-up-right-from-square"></i> PAGASA Flood Information</a>
                </div>
                <div class="flood-watch-grid">
                    <article class="flood-watch-card official">
                        <span class="flood-watch-kicker">Official source: DOST-PAGASA</span>
                        <h3 id="officialFloodStatus">Checking official flood information...</h3>
                        <p id="officialFloodDetails">If the official source is unavailable, Alertara will not fabricate a flood warning.</p>
                    </article>
                    <article class="flood-watch-card forecast">
                        <span class="flood-watch-kicker">Forecast source: Open-Meteo</span>
                        <h3 id="forecastFloodStatus">Forecast risk loading...</h3>
                        <p id="forecastFloodDetails">Rainfall totals and probability are calculated from forecast data only.</p>
                    </article>
                </div>
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

            const forecastTabs = [...document.querySelectorAll('[data-forecast-tab]')];
            let forecastMetric = 'temperature';
            let currentForecast = [];

            function formatNumber(value, digits = 0) {
                const number = Number(value);
                return Number.isFinite(number) ? number.toFixed(digits) : '--';
            }

            function iconForCondition(condition) {
                const text = String(condition || '').toLowerCase();
                if (text.includes('thunder')) return 'fa-cloud-bolt';
                if (text.includes('rain') || text.includes('drizzle')) return 'fa-cloud-showers-heavy';
                if (text.includes('clear')) return 'fa-sun';
                if (text.includes('mist') || text.includes('fog')) return 'fa-smog';
                if (text.includes('cloud')) return 'fa-cloud';
                return 'fa-cloud-sun';
            }

            function aggregateDailyForecast(forecast) {
                const grouped = new Map();
                forecast.forEach(item => {
                    const date = new Date((item.timestamp || 0) * 1000);
                    const key = date.toLocaleDateString('en-CA', { timeZone: 'Asia/Manila' });
                    if (!grouped.has(key)) {
                        grouped.set(key, { date, temps: [], feels: [], pops: [], rain: 0, wind: [], conditions: new Map(), peakRain: null });
                    }
                    const day = grouped.get(key);
                    day.temps.push(Number(item.temp || 0));
                    day.feels.push(Number(item.feels_like || item.temp || 0));
                    day.pops.push(Number(item.pop || 0));
                    day.rain += Number(item.rain || 0);
                    day.wind.push(Number(item.wind_speed || 0) * 3.6);
                    const condition = item.description || item.condition || 'Current conditions';
                    day.conditions.set(condition, (day.conditions.get(condition) || 0) + 1);
                    if (!day.peakRain || Number(item.rain || 0) > day.peakRain.rain) {
                        day.peakRain = { rain: Number(item.rain || 0), time: item.time || '' };
                    }
                });
                return [...grouped.values()].slice(0, 7).map(day => {
                    const condition = [...day.conditions.entries()].sort((a, b) => b[1] - a[1])[0]?.[0] || 'Current conditions';
                    return {
                        date: day.date,
                        min: Math.min(...day.temps),
                        max: Math.max(...day.temps),
                        pop: Math.max(...day.pops),
                        rain: day.rain,
                        wind: Math.max(...day.wind),
                        condition,
                        peakRain: day.peakRain
                    };
                });
            }

            function renderForecastChart(forecast) {
                const chart = document.getElementById('forecastChart');
                const points = forecast.slice(0, 8).map((item, index) => {
                    const value = forecastMetric === 'rain'
                        ? Number(item.pop || 0)
                        : forecastMetric === 'wind'
                            ? Number(item.wind_speed || 0) * 3.6
                            : Number(item.temp || 0);
                    const suffix = forecastMetric === 'rain' ? '%' : (forecastMetric === 'wind' ? ' km/h' : ' C');
                    return { index, value, label: `${Math.round(value)}${suffix}` };
                });
                if (!points.length) {
                    chart.innerHTML = '<text x="360" y="76" text-anchor="middle" class="forecast-empty-text">Forecast chart unavailable</text>';
                    return;
                }
                const values = points.map(point => point.value);
                const min = Math.min(...values);
                const max = Math.max(...values);
                const range = Math.max(1, max - min);
                const path = points.map((point, index) => {
                    const x = 36 + (index * (648 / Math.max(1, points.length - 1)));
                    const y = 108 - (((point.value - min) / range) * 72);
                    return { ...point, x, y };
                });
                const d = path.map((point, index) => `${index === 0 ? 'M' : 'L'} ${point.x.toFixed(1)} ${point.y.toFixed(1)}`).join(' ');
                chart.innerHTML = `
                    <path d="M 36 120 H 684" class="forecast-axis"></path>
                    <path d="${d}" class="forecast-line"></path>
                    ${path.map(point => `<circle cx="${point.x}" cy="${point.y}" r="4" class="forecast-dot"></circle><text x="${point.x}" y="${Math.max(18, point.y - 12)}" text-anchor="middle" class="forecast-point-label">${escapeHtml(point.label)}</text>`).join('')}
                `;
            }

            function renderForecast(forecast) {
                currentForecast = Array.isArray(forecast) ? forecast : [];
                const first = currentForecast[0] || {};
                const daily = aggregateDailyForecast(currentForecast);
                document.getElementById('forecastCurrentTemp').innerHTML = `${Math.round(first.temp ?? 0)}&deg;C`;
                document.getElementById('forecastCurrentCondition').textContent = first.description || first.condition || 'Current conditions';
                document.getElementById('forecastCurrentIcon').className = `fas ${iconForCondition(first.description || first.condition)}`;
                document.getElementById('forecastFeelsLike').innerHTML = `${formatNumber(first.feels_like)}&deg;C`;
                document.getElementById('forecastRainChance').textContent = `${formatNumber(first.pop)}%`;
                document.getElementById('forecastRainfall').textContent = `${formatNumber(first.rain, 1)} mm`;
                document.getElementById('forecastWindGust').textContent = first.wind_gust ? `${formatNumber(Number(first.wind_gust) * 3.6, 0)} km/h` : `${formatNumber(Number(first.wind_speed || 0) * 3.6, 0)} km/h`;
                document.getElementById('forecastVisibility').textContent = first.visibility ? `${formatNumber(Number(first.visibility) / 1000, 1)} km` : '-- km';

                document.getElementById('hourlyForecastStrip').innerHTML = currentForecast.slice(0, 8).map(item => `
                    <article class="hourly-forecast-card">
                        <span>${escapeHtml(item.time || '')}</span>
                        <i class="fas ${iconForCondition(item.description || item.condition)}"></i>
                        <strong>${Math.round(item.temp ?? 0)}&deg;</strong>
                        <small><i class="fas fa-droplet"></i> ${formatNumber(item.pop)}%</small>
                    </article>
                `).join('');

                document.getElementById('dailyForecastList').innerHTML = daily.map((day, index) => {
                    const name = index === 0 ? 'Today' : day.date.toLocaleDateString([], { weekday: 'short' });
                    const peak = day.peakRain?.time ? `Peak rain: ${day.peakRain.time}` : 'Peak rain unavailable';
                    return `<article class="daily-forecast-row">
                        <span class="daily-name">${escapeHtml(name)}</span>
                        <i class="fas ${iconForCondition(day.condition)}"></i>
                        <span class="daily-condition">${escapeHtml(day.condition)}</span>
                        <span class="daily-temp">${Math.round(day.max)}&deg; / ${Math.round(day.min)}&deg;</span>
                        <span class="daily-rain"><i class="fas fa-droplet"></i> ${Math.round(day.pop)}%</span>
                        <small>${escapeHtml(formatNumber(day.rain, 1))} mm &middot; ${escapeHtml(peak)}</small>
                    </article>`;
                }).join('');
                renderForecastChart(currentForecast);
            }

            function renderFloodWatch(risk) {
                document.getElementById('officialFloodStatus').textContent = 'Official PAGASA flood status unavailable in this page feed';
                document.getElementById('officialFloodDetails').textContent = 'Use the PAGASA Flood Information link for official advisories. Alertara will not mark Quezon City as under an official flood warning unless a verified PAGASA source says so.';
                const flood = (risk?.risks || []).find(item => item.key === 'flood_risk');
                const metrics = risk?.metrics || {};
                const level = flood?.level || 'normal';
                document.getElementById('forecastFloodStatus').textContent = `${flood?.label || 'Forecast Flood Risk'}: ${level.charAt(0).toUpperCase() + level.slice(1)}`;
                document.getElementById('forecastFloodDetails').textContent = `${flood?.summary || 'Forecast-based flood risk is currently unavailable.'} Rain chance up to ${metrics.max_precipitation_probability ?? '--'}%, wind gusts up to ${metrics.max_gust_kmh ?? '--'} km/h.`;
            }

            async function loadForecastWeather() {
                try {
                    const [forecastResponse, riskResponse] = await Promise.all([
                        fetch('../ADMIN/api/weather-monitoring.php?action=forecast&lat=14.6760&lon=121.0437', { cache: 'no-store' }),
                        fetch('../ADMIN/api/weather-monitoring.php?action=risk&lat=14.6760&lon=121.0437', { cache: 'no-store' })
                    ]);
                    const forecastData = await forecastResponse.json();
                    const riskData = await riskResponse.json();
                    if (!forecastData.success || !Array.isArray(forecastData.forecast)) throw new Error(forecastData.message || 'Forecast unavailable');
                    renderForecast(forecastData.forecast);
                    renderFloodWatch(riskData.success ? riskData.data : null);
                } catch (error) {
                    document.getElementById('forecastCurrentCondition').textContent = error.message || 'Forecast unavailable';
                    document.getElementById('hourlyForecastStrip').innerHTML = '<div class="bulletin-error">Forecast data could not be loaded.</div>';
                    document.getElementById('dailyForecastList').innerHTML = '';
                    document.getElementById('forecastFloodStatus').textContent = 'Forecast flood risk unavailable';
                    document.getElementById('forecastFloodDetails').textContent = 'Alertara could not retrieve structured Open-Meteo risk data right now.';
                }
            }

            async function loadCurrentWeather() {
                try {
                    const response = await fetch('../ADMIN/api/weather-monitoring.php?action=current&lat=14.6760&lon=121.0437', { cache: 'no-store' });
                    const result = await response.json();
                    if (!result.success || !result.data) throw new Error('Current weather unavailable');
                    const weather = result.data;
document.getElementById('weatherTemperature').innerHTML = `${Math.round(weather.main?.temp ?? 0)}&deg;C`;
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

            forecastTabs.forEach(button => {
                button.addEventListener('click', () => {
                    forecastMetric = button.dataset.forecastTab || 'temperature';
                    forecastTabs.forEach(tab => tab.classList.toggle('active', tab === button));
                    renderForecastChart(currentForecast);
                });
            });
            refreshButton.addEventListener('click', () => { loadBulletins(); loadCurrentWeather(); loadForecastWeather(); });
            loadBulletins();
            loadCurrentWeather();
            loadForecastWeather();
            initializeLayers();
            window.setInterval(loadBulletins, 60000);
            window.setInterval(loadCurrentWeather, 300000);
            window.setInterval(loadForecastWeather, 900000);
        })();
    </script>
</body>
</html>
