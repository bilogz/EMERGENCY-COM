<?php
require_once __DIR__ . '/../session-config.php';

$assetBase = '../ADMIN/header/';
$current = 'earthquake-monitoring.php';
$pageTitle = 'Earthquake Bulletins';
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
                    <div class="bulletin-eyebrow"><i class="fas fa-tower-broadcast"></i> Official monitoring</div>
                    <h1>PHIVOLCS Earthquake Bulletins</h1>
                    <p>Recent Philippine earthquake reports presented as clear public bulletins. Check the magnitude, location, depth, and recommended safety action before sharing information.</p>
                </div>
                <div class="bulletin-live"><span class="bulletin-live-dot"></span> Live updates active</div>
            </header>

            <section class="bulletin-metrics" aria-label="Earthquake summary">
                <div class="bulletin-metric"><span>Total bulletins</span><strong id="totalEvents">--</strong></div>
                <div class="bulletin-metric"><span>Major (5.0+)</span><strong id="majorEvents">--</strong></div>
                <div class="bulletin-metric"><span>Latest magnitude</span><strong id="latestMagnitude">--</strong></div>
                <div class="bulletin-metric"><span>Last checked</span><strong id="lastUpdate" style="font-size:1rem">--</strong></div>
            </section>

            <section class="bulletin-board" aria-labelledby="earthquakeBoardTitle">
                <div class="bulletin-toolbar">
                    <div class="bulletin-toolbar-title">
                        <i class="fas fa-newspaper"></i>
                        <div>
                            <h2 id="earthquakeBoardTitle">Earthquake Bulletin Board</h2>
                            <small id="earthquakeSource">Source: PHIVOLCS</small>
                        </div>
                    </div>
                    <div class="bulletin-actions">
                        <button class="bulletin-button" id="refreshEarthquakes" type="button"><i class="fas fa-rotate"></i> Refresh</button>
                        <a class="bulletin-button danger" href="https://earthquake.phivolcs.dost.gov.ph/" target="_blank" rel="noopener"><i class="fas fa-arrow-up-right-from-square"></i> Official PHIVOLCS</a>
                    </div>
                </div>

                <div class="bulletin-filter">
                    <label for="earthquakeDate"><i class="fas fa-calendar-day"></i> Show date</label>
                    <input id="earthquakeDate" type="date">
                    <button class="bulletin-button" id="clearEarthquakeDate" type="button" hidden>Clear date</button>
                </div>

                <div class="bulletin-feed" id="earthquakeBulletinFeed" aria-live="polite">
                    <div class="bulletin-loading"><i class="fas fa-circle-notch fa-spin"></i>Loading the latest PHIVOLCS bulletins...</div>
                </div>
            </section>

            <section class="bulletin-map-panel" aria-labelledby="seismicMapTitle">
                <div class="bulletin-toolbar">
                    <div class="bulletin-toolbar-title">
                        <i class="fas fa-map-location-dot"></i>
                        <div><h2 id="seismicMapTitle">Seismic Activity Map</h2><small>Tap a marker to read the event details.</small></div>
                    </div>
                    <button class="bulletin-button" id="focusQuezonCity" type="button"><i class="fas fa-crosshairs"></i> Focus Quezon City</button>
                </div>
                <div id="earthquakeMap" class="bulletin-map-canvas" aria-label="Map of recent Philippine earthquakes"></div>
                <div class="bulletin-map-note"><i class="fas fa-circle-info"></i> Red markers are magnitude 5.0 or higher; orange markers are magnitude 3.0–4.9.</div>
            </section>

            <section class="bulletin-safety-panel" aria-labelledby="earthquakeSafetyTitle">
                <div class="bulletin-toolbar">
                    <div class="bulletin-toolbar-title"><i class="fas fa-shield-heart"></i><h2 id="earthquakeSafetyTitle">If You Feel Shaking</h2></div>
                </div>
                <div class="bulletin-safety-grid">
                    <div class="bulletin-safety-item"><i class="fas fa-person-falling"></i><strong>Drop, cover, and hold on.</strong> Protect your head and stay away from glass.</div>
                    <div class="bulletin-safety-item"><i class="fas fa-person-walking-arrow-right"></i><strong>When shaking stops,</strong> move carefully to a safe open area.</div>
                    <div class="bulletin-safety-item"><i class="fas fa-radio"></i><strong>Expect aftershocks.</strong> Follow PHIVOLCS and Quezon City advisories.</div>
                </div>
            </section>
        </div>
    </main>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        (() => {
            'use strict';

            const QC = [14.6488, 121.0509];
            const feed = document.getElementById('earthquakeBulletinFeed');
            const refreshButton = document.getElementById('refreshEarthquakes');
            const dateInput = document.getElementById('earthquakeDate');
            const clearDateButton = document.getElementById('clearEarthquakeDate');
            let bulletins = [];
            let markers = [];

            const map = L.map('earthquakeMap', { scrollWheelZoom: false }).setView([12.8797, 121.7740], 6);
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

            function distanceKm(lat1, lon1, lat2, lon2) {
                const radius = 6371;
                const dLat = (lat2 - lat1) * Math.PI / 180;
                const dLon = (lon2 - lon1) * Math.PI / 180;
                const a = Math.sin(dLat / 2) ** 2 + Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) * Math.sin(dLon / 2) ** 2;
                return radius * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
            }

            function parseDate(value) {
                const match = String(value || '').match(/\d{1,2}\s+[A-Za-z]+\s+\d{4}/);
                const parsed = new Date(match ? match[0] : value);
                return Number.isNaN(parsed.getTime()) ? null : parsed;
            }

            function severity(magnitude) {
                if (magnitude >= 5) return { label: 'Major', color: '#c7443e', advice: 'Strong shaking may be felt. Check for damage and prepare for aftershocks.' };
                if (magnitude >= 3) return { label: 'Moderate', color: '#d97706', advice: 'Light to moderate shaking may be felt near the epicenter. Stay alert.' };
                return { label: 'Minor', color: '#2f855a', advice: 'Usually weak or not felt. Continue normal monitoring.' };
            }

            function updateMap(items) {
                markers.forEach(marker => map.removeLayer(marker));
                markers = items.map(item => {
                    const level = severity(Number(item.magnitude));
                    const marker = L.circleMarker([Number(item.latitude), Number(item.longitude)], {
                        radius: Math.max(6, Math.min(14, Number(item.magnitude) * 2)),
                        color: '#fff', weight: 2, fillColor: level.color, fillOpacity: .9
                    }).addTo(map);
                    marker.bindPopup(`<strong>Magnitude ${Number(item.magnitude).toFixed(1)}</strong><br>${escapeHtml(item.location)}<br><small>${escapeHtml(item.date_time)} · ${escapeHtml(item.depth_km)} km deep</small>`);
                    return marker;
                });
            }

            function render() {
                const selectedDate = dateInput.value;
                clearDateButton.hidden = !selectedDate;
                const shown = selectedDate ? bulletins.filter(item => {
                    const date = parseDate(item.date_time);
                    if (!date) return false;
                    const local = `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;
                    return local === selectedDate;
                }) : bulletins;

                if (!shown.length) {
                    feed.innerHTML = '<div class="bulletin-empty"><i class="fas fa-circle-check"></i>No earthquake bulletins match this date.</div>';
                    return;
                }

                feed.innerHTML = shown.slice(0, 20).map(item => {
                    const magnitude = Number(item.magnitude) || 0;
                    const level = severity(magnitude);
                    const distance = distanceKm(Number(item.latitude), Number(item.longitude), QC[0], QC[1]);
                    const officialLink = item.bulletin_link
                        ? `<a class="bulletin-button" href="${escapeHtml(item.bulletin_link)}" target="_blank" rel="noopener"><i class="fas fa-file-lines"></i> Official bulletin</a>` : '';
                    return `<article class="bulletin-card" style="--bulletin-accent:${level.color}">
                        <div class="bulletin-card-head">
                            <div><div class="bulletin-source"><i class="fas fa-wave-square"></i> PHIVOLCS earthquake bulletin</div><h3>Magnitude ${magnitude.toFixed(1)} · ${escapeHtml(item.location)}</h3></div>
                            <div class="bulletin-issued"><i class="far fa-clock"></i> ${escapeHtml(item.date_time)}</div>
                        </div>
                        <div class="bulletin-card-body">
                            <p class="bulletin-description">An earthquake was recorded at a depth of ${escapeHtml(item.depth_km)} km, approximately ${Math.round(distance)} km from Quezon City.</p>
                            <div class="bulletin-impact">
                                <div class="bulletin-impact-head"><span class="bulletin-impact-title"><i class="fas fa-location-crosshairs"></i> Public safety guidance</span><span class="bulletin-badge">${level.label}</span></div>
                                <p>${level.advice}</p>
                                <ul class="bulletin-steps"><li>Drop, cover, and hold on if shaking is felt.</li><li>Check for hazards only after the shaking stops.</li><li>Use official sources before sharing earthquake information.</li></ul>
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
                    const response = await fetch('../ADMIN/api/phivolcs-scraper.php', { cache: 'no-store' });
                    const data = await response.json();
                    if (!response.ok || !data.success || !Array.isArray(data.earthquakes)) throw new Error(data.message || 'Bulletin service unavailable.');
                    bulletins = data.earthquakes;
                    document.getElementById('totalEvents').textContent = bulletins.length;
                    document.getElementById('majorEvents').textContent = bulletins.filter(item => Number(item.magnitude) >= 5).length;
                    document.getElementById('latestMagnitude').textContent = bulletins.length ? Number(bulletins[0].magnitude).toFixed(1) : '--';
                    document.getElementById('lastUpdate').textContent = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
                    document.getElementById('earthquakeSource').textContent = data.is_cached ? 'Source: PHIVOLCS · cached copy' : 'Source: PHIVOLCS · live feed';
                    // Keep the public map responsive on phones when PHIVOLCS returns a large archive.
                    updateMap(bulletins.slice(0, 300));
                    render();
                } catch (error) {
                    feed.innerHTML = `<div class="bulletin-error"><i class="fas fa-triangle-exclamation"></i><strong>Bulletins could not be loaded.</strong><br>${escapeHtml(error.message)}<div style="margin-top:1rem"><button class="bulletin-button" type="button" onclick="document.getElementById('refreshEarthquakes').click()">Try again</button></div></div>`;
                } finally {
                    refreshButton.disabled = false;
                    refreshButton.innerHTML = '<i class="fas fa-rotate"></i> Refresh';
                }
            }

            refreshButton.addEventListener('click', loadBulletins);
            dateInput.addEventListener('change', render);
            clearDateButton.addEventListener('click', () => { dateInput.value = ''; render(); });
            document.getElementById('focusQuezonCity').addEventListener('click', () => map.flyTo(QC, 12));
            loadBulletins();
            window.setInterval(loadBulletins, 120000);
        })();
    </script>
</body>
</html>
