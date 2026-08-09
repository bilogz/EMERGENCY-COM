<?php
/**
 * PHIVOLCS Earthquake Monitoring Page
 * Monitor earthquakes in the Philippines region using PHIVOLCS data
 */

// Start session and check authentication
session_start();

$publicView = isset($_GET['public']) && $_GET['public'] == '1';

// Check if user is logged in (skip for public view)
if (!$publicView && (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true)) {
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
    <link rel="stylesheet" href="css/global.css?v=<?php echo filemtime(__DIR__ . '/css/global.css'); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/sidebar.css?v=<?php echo filemtime(__DIR__ . '/css/sidebar.css'); ?>">
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
    <link rel="stylesheet" href="css/module-earthquake-monitoring.css?v=<?php echo filemtime(__DIR__ . '/css/module-earthquake-monitoring.css'); ?>">
    <?php if ($publicView): ?>
    <style>
        body.public-view .main-content {
            margin-left: 0;
            padding-top: 2rem;
        }
        body.public-view .main-container {
            max-width: 1200px;
        }
    </style>
    <?php endif; ?>
</head>
<body class="<?php echo $publicView ? 'public-view' : ''; ?>">
    <?php if (!$publicView): ?>
        <?php include 'includes/sidebar.php'; ?>
        <?php include 'includes/admin-header.php'; ?>
    <?php endif; ?>
    
    <div class="main-content">
        <div class="main-container">
            <div class="title" style="margin-bottom: 1.5rem;">
                <nav class="breadcrumb" aria-label="Breadcrumb">
                    <ol class="breadcrumb-list">
                        <li class="breadcrumb-item"><a href="dashboard.php" class="breadcrumb-link">Dashboard</a></li>
                        <li class="breadcrumb-item active">Earthquake Monitoring</li>
                    </ol>
                </nav>
                <h1 style="font-size: 1.8rem; font-weight: 700; color: var(--text-color-1); margin-top: 0.5rem; display: flex; align-items: center; gap: 0.5rem;">
                    <i class="fas fa-mountain" style="color: #e74c3c;"></i> PHIVOLCS Earthquake Monitoring
                </h1>
                <p style="color: var(--text-secondary-1); font-size: 0.95rem; margin-top: 0.25rem;">Real-time earthquake bulletins from the Philippine Institute of Volcanology and Seismology.</p>
            </div>
            
            <div class="sub-container">
                <div class="page-content" style="padding: 0;">
                    <div class="module-analytics-strip" style="display: none;" aria-hidden="true"></div>

                    <div class="earthquake-layout-grid">
                        <div class="earthquake-main-column">

                    <!-- Statistics Grid -->
                    <div class="stat-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1rem; margin-bottom: 1.5rem;">
                        <div class="stat-card" style="background: var(--card-bg-1); padding: 1.25rem; border-radius: 8px; border: 1px solid var(--border-color-1); text-align: center;">
                            <div class="stat-label" style="font-size: 0.8rem; text-transform: uppercase; color: var(--text-secondary-1); font-weight: 600;">Total Bulletins</div>
                            <div class="stat-value" id="totalEvents" style="font-size: 2rem; font-weight: 800; color: var(--text-color-1); margin-top: 0.25rem;">-</div>
                        </div>
                        <div class="stat-card" style="background: var(--card-bg-1); padding: 1.25rem; border-radius: 8px; border: 1px solid var(--border-color-1); text-align: center;">
                            <div class="stat-label" style="font-size: 0.8rem; text-transform: uppercase; color: var(--text-secondary-1); font-weight: 600;">Major (5.0+)</div>
                            <div class="stat-value" id="majorEvents" style="font-size: 2rem; font-weight: 800; color: #e74c3c; margin-top: 0.25rem;">-</div>
                        </div>
                        <div class="stat-card" style="background: var(--card-bg-1); padding: 1.25rem; border-radius: 8px; border: 1px solid var(--border-color-1); text-align: center;">
                            <div class="stat-label" style="font-size: 0.8rem; text-transform: uppercase; color: var(--text-secondary-1); font-weight: 600;">Latest Magnitude</div>
                            <div class="stat-value" id="latestMagnitude" style="font-size: 2rem; font-weight: 800; color: #3498db; margin-top: 0.25rem;">-</div>
                        </div>
                        <div class="stat-card" style="background: var(--card-bg-1); padding: 1.25rem; border-radius: 8px; border: 1px solid var(--border-color-1); text-align: center; display: flex; flex-direction: column; justify-content: center; align-items: center;">
                            <div class="stat-label" style="font-size: 0.8rem; text-transform: uppercase; color: var(--text-secondary-1); font-weight: 600;">Last Updated</div>
                            <div class="stat-value" id="lastUpdate" style="font-size: 1.25rem; font-weight: 700; color: var(--text-color-1); margin-top: 0.25rem;">-</div>
                            <small style="color: #27ae60; font-weight: 700; display: inline-flex; align-items: center; gap: 0.25rem; margin-top: 0.25rem;">
                                <span class="eq-live-dot"></span> LIVE
                            </small>
                        </div>
                    </div>

                    <!-- PHIVOLCS Earthquake Bulletin Board -->
                    <div class="module-card" style="background: var(--card-bg-1); border-radius: 8px; border: 1px solid var(--border-color-1); overflow: hidden; margin-bottom: 1.5rem;">
                        <div class="module-card-header" style="padding: 1rem 1.25rem; border-bottom: 1px solid var(--border-color-1); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.75rem; background: var(--bg-color-2);">
                            <div style="display: flex; align-items: center; gap: 0.6rem;">
                                <h2 style="display: flex; align-items: center; gap: 0.5rem; margin: 0; font-size: 1.15rem; font-weight: 700; color: var(--text-color-1);"><i class="fas fa-newspaper" style="color: #e74c3c;"></i> PHIVOLCS Earthquake Bulletin Board</h2>
                            </div>
                            <div style="display: flex; align-items: center; gap: 0.6rem; flex-wrap: wrap;">
                                <span id="eqLastRefresh" style="font-size: 0.72rem; color: var(--text-secondary-1);">Updated just now</span>
                                <button onclick="loadPhivolcsBulletins()" style="background: var(--bg-color-1); border: 1px solid var(--border-color-1); color: var(--text-color-1); padding: 0.45rem 0.8rem; border-radius: 6px; cursor: pointer; display: inline-flex; align-items: center; gap: 0.35rem; font-size: 0.78rem; font-weight: 600;"><i class="fas fa-sync-alt"></i> Refresh</button>
                                <button onclick="openSeismicMapModal()" style="background: linear-gradient(135deg, #c0392b, #e74c3c); color: white; padding: 0.45rem 0.95rem; border-radius: 6px; border: none; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 0.4rem; font-size: 0.78rem; box-shadow: 0 4px 6px rgba(0,0,0,0.1);"><i class="fas fa-map-marked-alt"></i> Open Seismic Map</button>
                                <a href="https://earthquake.phivolcs.dost.gov.ph/" target="_blank" style="background: var(--bg-color-1); border: 1px solid var(--border-color-1); color: var(--text-color-1); padding: 0.45rem 0.8rem; border-radius: 6px; text-decoration: none; display: inline-flex; align-items: center; gap: 0.35rem; font-size: 0.78rem; font-weight: 600;"><i class="fas fa-external-link-alt"></i> PHIVOLCS Web</a>
                            </div>
                        </div>
                        
                        <!-- Date Filter Bar -->
                        <div style="padding: 0.75rem 1.25rem; border-bottom: 1px solid var(--border-color-1); display: flex; align-items: center; gap: 1rem; background: var(--bg-color-1); flex-wrap: wrap;">
                            <div style="display: flex; align-items: center; gap: 0.5rem;">
                                <label for="eqDateFilter" style="font-size: 0.82rem; font-weight: 600; color: var(--text-color-1); display: flex; align-items: center; gap: 0.35rem;"><i class="fas fa-calendar-alt" style="color: #e74c3c;"></i> Filter by Date:</label>
                                <input type="date" id="eqDateFilter" onchange="filterAndRenderBulletins()" style="padding: 0.35rem 0.5rem; border-radius: 5px; border: 1px solid var(--border-color-1); background: var(--bg-color-2); color: var(--text-color-1); font-size: 0.8rem; outline: none;">
                            </div>
                            <button id="eqClearFilterBtn" onclick="clearDateFilter()" style="display: none; background: transparent; border: none; color: #e74c3c; cursor: pointer; font-size: 0.8rem; font-weight: 600; align-items: center; gap: 0.25rem;"><i class="fas fa-times-circle"></i> Clear Filter</button>
                        </div>

                        <div id="earthquakeBulletinFeed" style="padding: 1.25rem; max-height: 800px; overflow-y: auto; background: var(--card-bg-1);">
                            <div style="text-align: center; padding: 3rem; opacity: 0.7;">
                                <i class="fas fa-circle-notch fa-spin" style="font-size: 2rem; margin-bottom: 0.75rem; color: #e74c3c;"></i>
                                <p style="font-size: 1rem; font-weight: 500; color: var(--text-color-1);">Fetching bulletins from PHIVOLCS...</p>
                            </div>
                        </div>

                        <!-- Pagination Bar -->
                        <div id="eqPaginationBar" style="padding: 0.75rem 1.25rem; border-top: 1px solid var(--border-color-1); display: flex; justify-content: space-between; align-items: center; background: var(--bg-color-2); flex-wrap: wrap; gap: 0.5rem;">
                            <span id="eqPaginationInfo" style="font-size: 0.8rem; color: var(--text-secondary-1); font-weight: 600;">Showing 0-0 of 0 bulletins</span>
                            <div style="display: flex; gap: 0.5rem;">
                                <button id="eqPrevPageBtn" onclick="changePage(-1)" style="background: var(--bg-color-1); border: 1px solid var(--border-color-1); color: var(--text-color-1); padding: 0.35rem 0.75rem; border-radius: 5px; cursor: pointer; font-size: 0.78rem; font-weight: 600; display: inline-flex; align-items: center; gap: 0.25rem;"><i class="fas fa-chevron-left"></i> Prev</button>
                                <button id="eqNextPageBtn" onclick="changePage(1)" style="background: var(--bg-color-1); border: 1px solid var(--border-color-1); color: var(--text-color-1); padding: 0.35rem 0.75rem; border-radius: 5px; cursor: pointer; font-size: 0.78rem; font-weight: 600; display: inline-flex; align-items: center; gap: 0.25rem;">Next <i class="fas fa-chevron-right"></i></button>
                            </div>
                        </div>
                    </div>

                        </div>
                        <aside class="earthquake-sidebar">
                    <div class="eq-analytics-card">
                        <div class="eq-card-heading">
                            <h3><i class="fas fa-chart-line"></i> Seismic Analytics</h3>
                            <span class="eq-live-pill"><span class="eq-live-dot"></span> Live</span>
                        </div>
                        <div class="eq-analytics-grid">
                            <div class="eq-metric-tile"><span>Average Mag</span><strong id="eqAverageMagnitude">-</strong></div>
                            <div class="eq-metric-tile"><span>Nearest QC</span><strong id="eqNearestDistance">-</strong></div>
                            <div class="eq-metric-tile"><span>Last 6h</span><strong id="eqSixHourCount">-</strong></div>
                            <div class="eq-metric-tile"><span>Risk Level</span><strong id="eqRiskLevel">-</strong></div>
                        </div>
                    </div>

                    <div class="eq-analytics-card">
                        <div class="eq-card-heading"><h3><i class="fas fa-wave-square"></i> Magnitude Frequency</h3></div>
                        <canvas id="eqMagnitudeChart" height="150"></canvas>
                    </div>

                    <div class="eq-analytics-card">
                        <div class="eq-card-heading"><h3><i class="fas fa-layer-group"></i> Intensity Mix</h3></div>
                        <canvas id="eqIntensityChart" height="150"></canvas>
                    </div>

                    <div class="eq-analytics-card">
                        <div class="eq-card-heading"><h3><i class="fas fa-clock"></i> Bulletin Frequency</h3></div>
                        <div id="eqFrequencyRows" class="eq-frequency-rows">
                            <div class="eq-frequency-empty">Waiting for PHIVOLCS data...</div>
                        </div>
                    </div>
                    <!-- AI Earthquake Analysis + Auto Alerts -->
                    <div class="module-card" style="background:linear-gradient(135deg, #241238, #132f3c); border-radius:8px; border:1px solid rgba(255,255,255,0.12); overflow:hidden; margin-bottom:1.5rem; color:white;">
                        <div style="padding:1rem 1.25rem; display:flex; justify-content:space-between; align-items:center; gap:0.75rem; flex-wrap:wrap; border-bottom:1px solid rgba(255,255,255,0.12);">
                            <h2 style="margin:0; font-size:1.05rem; font-weight:800; display:flex; align-items:center; gap:0.5rem;"><i class="fas fa-robot" style="color:#ff7675;"></i> AI Earthquake Analysis</h2>
                            <span id="eqAiStatus" style="background:#14532d; color:#dcfce7; border:1px solid rgba(255,255,255,0.18); border-radius:999px; padding:0.2rem 0.65rem; font-size:0.7rem; font-weight:800; text-transform:uppercase;">Ready</span>
                        </div>
                        <div style="padding:1rem 1.25rem; display:flex; flex-direction:column; gap:1rem; align-items:stretch;">
                            <div id="eqAiAnalysis" style="min-height:90px; background:rgba(255,255,255,0.08); border:1px solid rgba(255,255,255,0.12); border-radius:7px; padding:0.85rem; font-size:0.86rem; line-height:1.55; color:rgba(255,255,255,0.92);">
                                Select Analyze Earthquake to summarize the latest PHIVOLCS bulletin for Quezon City response planning.
                            </div>
                            <div style="display:flex; flex-direction:column; gap:0.65rem;">
<button onclick="getAIEarthquakeAnalysis()" style="background:linear-gradient(135deg,#8e44ad,#9b59b6); color:white; border:none; border-radius:7px; padding:0.75rem 1rem; font-weight:800; cursor:pointer; display:flex; align-items:center; justify-content:center; gap:0.45rem;"><i class="fas fa-brain"></i> Analyze Earthquake</button>
                                <button onclick="sendEarthquakeAlert()" style="background:linear-gradient(135deg,#27ae60,#229954); color:white; border:none; border-radius:7px; padding:0.75rem 1rem; font-weight:800; cursor:pointer; display:flex; align-items:center; justify-content:center; gap:0.45rem;"><i class="fas fa-paper-plane"></i> Send Alert</button>
                            </div>
                        </div>
                    </div>
                        </aside>
                    </div>

                </div>
            </div>
        </div>
    </div>


    <script>
        let map;
        let earthquakeMarkers = [];
        let phivolcsData = [];
        let eqMagnitudeChart = null;
        let eqIntensityChart = null;

        function initMap() {
            map = L.map('earthquakeMap').setView([12.8797, 121.7740], 6);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '© OpenStreetMap' }).addTo(map);
            
            fetch('../api/quezon-city.geojson')
                .then(r => r.json())
                .then(data => {
                    L.geoJSON(data, {
                        style: { color: '#e74c3c', weight: 3, fillColor: '#e74c3c', fillOpacity: 0.05 }
                    }).addTo(map);
                }).catch(() => {});

            if (phivolcsData.length > 0) {
                plotMarkers(phivolcsData);
            }
        }

        function plotMarkers(quakes) {
            if (!map) return;
            earthquakeMarkers.forEach(m => map.removeLayer(m));
            earthquakeMarkers = [];

            quakes.forEach(q => {
                const mag = q.magnitude;
                let color = '#2ecc71';
                if (mag >= 5.0) color = '#e74c3c';
                else if (mag >= 3.0) color = '#f39c12';

                const icon = L.divIcon({
                    className: 'earthquake-marker-custom',
                    html: `<div style="background:${color}; width:14px; height:14px; border-radius:50%; border:2px solid white; box-shadow:0 2px 4px rgba(0,0,0,0.3);"></div>`,
                    iconSize: [14, 14]
                });

                const marker = L.marker([q.latitude, q.longitude], { icon }).addTo(map)
                    .bindPopup(`<strong>M ${mag.toFixed(1)}</strong><br>${q.location}<br><small>${q.date_time}</small><br><small>Depth: ${q.depth_km} km</small>`);
                earthquakeMarkers.push(marker);
            });
        }

        function calculateDistanceKm(lat1, lon1, lat2, lon2) {
            const R = 6371;
            const dLat = (lat2 - lat1) * Math.PI / 180;
            const dLon = (lon2 - lon1) * Math.PI / 180;
            const a = Math.sin(dLat/2) ** 2 + Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) * Math.sin(dLon/2) ** 2;
            return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
        }

        let isCachedData = false;
        let phivolcsAutoAlertInFlight = false;

        function loadPhivolcsBulletins() {
            const container = document.getElementById('earthquakeBulletinFeed');
            
            fetch('../api/phivolcs-scraper.php')
                .then(r => r.json())
                .then(data => {
                    if (data.success && data.earthquakes && data.earthquakes.length > 0) {
                        phivolcsData = data.earthquakes;
                        isCachedData = !!data.is_cached;
                        filterAndRenderBulletins();
                        maybeAutoSendEarthquakeAlert();
                    } else {
                        container.innerHTML = `<div style="text-align:center; padding:2.5rem;"><i class="fas fa-exclamation-triangle" style="font-size:2.5rem; color:#e67e22; margin-bottom:0.75rem; display:block;"></i><p style="font-size:1rem; font-weight:600; color:var(--text-color-1);">Unable to fetch PHIVOLCS data</p><p style="font-size:0.85rem; color:var(--text-secondary-1);">${data.message || 'Please try again later.'}</p><button onclick="loadPhivolcsBulletins()" style="margin-top:0.75rem; background:#e74c3c; color:white; border:none; padding:0.5rem 1rem; border-radius:5px; cursor:pointer; font-weight:600;"><i class="fas fa-redo"></i> Retry</button></div>`;
                    }

                    document.getElementById('eqLastRefresh').textContent = 'Updated ' + new Date().toLocaleTimeString([], {hour:'2-digit', minute:'2-digit'});
                })
                .catch(err => {
                    console.error('PHIVOLCS fetch error:', err);
                    container.innerHTML = `<div style="text-align:center; padding:2.5rem;"><i class="fas fa-wifi" style="font-size:2.5rem; color:#e74c3c; margin-bottom:0.75rem; display:block;"></i><p style="font-size:1rem; font-weight:600; color:var(--text-color-1);">Connection Error</p><p style="font-size:0.85rem; color:var(--text-secondary-1);">Could not reach the PHIVOLCS scraper. Check your network.</p><button onclick="loadPhivolcsBulletins()" style="margin-top:0.75rem; background:#e74c3c; color:white; border:none; padding:0.5rem 1rem; border-radius:5px; cursor:pointer; font-weight:600;"><i class="fas fa-redo"></i> Retry</button></div>`;
                });
        }

        function parsePhivolcsDate(dateStr) {
            const parts = String(dateStr || '').split('-');
            if (parts.length === 0) return null;
            const datePart = parts[0].trim(); // "12 July 2026"
            const d = new Date(datePart);
            return isNaN(d.getTime()) ? null : d;
        }

        function parsePhivolcsDateTime(dateStr) {
            const cleaned = String(dateStr || '').replace(/\s+-\s+/, ' ').trim();
            const parsed = new Date(cleaned);
            return isNaN(parsed.getTime()) ? parsePhivolcsDate(dateStr) : parsed;
        }
        function isSameDay(d, dateInputStr) {
            if (!d || !dateInputStr) return false;
            const parts = dateInputStr.split('-');
            if (parts.length !== 3) return false;
            const y = parseInt(parts[0], 10);
            const m = parseInt(parts[1], 10) - 1;
            const day = parseInt(parts[2], 10);
            return d.getFullYear() === y && d.getMonth() === m && d.getDate() === day;
        }

        let currentPage = 1;
        const itemsPerPage = 10;

        function filterAndRenderBulletins(resetPage = true) {
            if (resetPage) {
                currentPage = 1;
            }
            
            const dateFilterVal = document.getElementById('eqDateFilter').value;
            const clearBtn = document.getElementById('eqClearFilterBtn');
            
            let filtered = phivolcsData;
            
            if (dateFilterVal) {
                clearBtn.style.display = 'inline-flex';
                filtered = phivolcsData.filter(q => {
                    const qDate = parsePhivolcsDate(q.date_time);
                    return isSameDay(qDate, dateFilterVal);
                });
            } else {
                clearBtn.style.display = 'none';
            }
            
            const totalItems = filtered.length;
            const totalPages = Math.ceil(totalItems / itemsPerPage) || 1;
            
            // Adjust currentPage if out of bounds
            if (currentPage > totalPages) currentPage = totalPages;
            if (currentPage < 1) currentPage = 1;
            
            // Slice the data for the current page
            const startIndex = (currentPage - 1) * itemsPerPage;
            const endIndex = startIndex + itemsPerPage;
            const limited = filtered.slice(startIndex, endIndex);
            
            // Render bulletins
            renderBulletins(limited, isCachedData);
            updateStats(filtered); // stats reflect the total filtered set
            updateEarthquakeAnalytics(filtered);
            
            // Update pagination UI
            updatePaginationControls(totalItems, currentPage, totalPages);
            
            // Plot markers of current page
            if (eqMapInitialized && map) {
                plotMarkers(limited);
            }
        }

        function updatePaginationControls(totalItems, page, totalPages) {
            const prevBtn = document.getElementById('eqPrevPageBtn');
            const nextBtn = document.getElementById('eqNextPageBtn');
            const infoSpan = document.getElementById('eqPaginationInfo');
            
            if (!prevBtn || !nextBtn || !infoSpan) return;
            
            const from = totalItems === 0 ? 0 : (page - 1) * itemsPerPage + 1;
            const to = Math.min(page * itemsPerPage, totalItems);
            
            infoSpan.textContent = `Showing ${from}-${to} of ${totalItems} bulletins`;
            
            prevBtn.disabled = (page === 1);
            nextBtn.disabled = (page === totalPages);
            
            // Apply visual styling for disabled states
            prevBtn.style.opacity = (page === 1) ? '0.5' : '1';
            prevBtn.style.cursor = (page === 1) ? 'not-allowed' : 'pointer';
            nextBtn.style.opacity = (page === totalPages) ? '0.5' : '1';
            nextBtn.style.cursor = (page === totalPages) ? 'not-allowed' : 'pointer';
        }

        function changePage(direction) {
            currentPage += direction;
            filterAndRenderBulletins(false);
        }

        function clearDateFilter() {
            document.getElementById('eqDateFilter').value = '';
            filterAndRenderBulletins(true);
        }

        function updateStats(quakes) {
            document.getElementById('totalEvents').textContent = quakes.length;
            document.getElementById('majorEvents').textContent = quakes.filter(q => q.magnitude >= 5).length;
            const latest = quakes[0]?.magnitude || '-';
            document.getElementById('latestMagnitude').textContent = typeof latest === 'number' ? latest.toFixed(1) : latest;
            document.getElementById('lastUpdate').textContent = new Date().toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
        }

        function earthquakeSeverityLabel(mag) {
            if (mag >= 5.0) return 'Critical';
            if (mag >= 4.0) return 'Moderate';
            if (mag >= 3.0) return 'Light';
            return 'Minor';
        }

        function updateEarthquakeAnalytics(quakes) {
            const safeQuakes = Array.isArray(quakes) ? quakes.filter(q => Number(q.magnitude) > 0) : [];
            const avgEl = document.getElementById('eqAverageMagnitude');
            const nearEl = document.getElementById('eqNearestDistance');
            const sixEl = document.getElementById('eqSixHourCount');
            const riskEl = document.getElementById('eqRiskLevel');
            if (!avgEl || !nearEl || !sixEl || !riskEl) return;

            if (!safeQuakes.length) {
                avgEl.textContent = '-'; nearEl.textContent = '-'; sixEl.textContent = '-'; riskEl.textContent = '-';
                renderMagnitudeChart([0, 0, 0, 0]);
                renderIntensityChart([0, 0, 0, 0]);
                renderFrequencyRows([]);
                return;
            }

            const average = safeQuakes.reduce((sum, q) => sum + Number(q.magnitude || 0), 0) / safeQuakes.length;
            const distances = safeQuakes.map(q => calculateDistanceKm(Number(q.latitude || 0), Number(q.longitude || 0), 14.6488, 121.0509)).filter(Number.isFinite);
            const nearest = distances.length ? Math.min(...distances) : null;
            const now = Date.now();
            const sixHourCount = safeQuakes.filter(q => {
                const parsed = parsePhivolcsDateTime(q.date_time);
                return parsed && (now - parsed.getTime()) <= 6 * 60 * 60 * 1000;
            }).length;
            const strongest = Math.max(...safeQuakes.map(q => Number(q.magnitude || 0)));
            let risk = 'Low';
            if (strongest >= 5 || (nearest !== null && nearest <= 100)) risk = 'High';
            else if (strongest >= 4 || (nearest !== null && nearest <= 200)) risk = 'Moderate';

            avgEl.textContent = average.toFixed(1);
            nearEl.textContent = nearest !== null ? `${nearest.toFixed(0)} km` : '-';
            sixEl.textContent = String(sixHourCount);
            riskEl.textContent = risk;

            const buckets = { minor: 0, light: 0, moderate: 0, critical: 0 };
            safeQuakes.forEach(q => {
                const mag = Number(q.magnitude || 0);
                if (mag >= 5) buckets.critical++;
                else if (mag >= 4) buckets.moderate++;
                else if (mag >= 3) buckets.light++;
                else buckets.minor++;
            });
            renderMagnitudeChart([buckets.minor, buckets.light, buckets.moderate, buckets.critical]);
            renderIntensityChart([buckets.minor, buckets.light, buckets.moderate, buckets.critical]);
            renderFrequencyRows(safeQuakes);
        }

        function chartThemeColors() {
            return {
                text: getComputedStyle(document.documentElement).getPropertyValue('--text-secondary-1').trim() || '#64748b',
                grid: getComputedStyle(document.documentElement).getPropertyValue('--border-color-1').trim() || 'rgba(148,163,184,0.25)'
            };
        }

        function renderMagnitudeChart(values) {
            const canvas = document.getElementById('eqMagnitudeChart');
            if (!canvas || typeof Chart === 'undefined') return;
            const theme = chartThemeColors();
            const data = { labels: ['<3.0', '3.0-3.9', '4.0-4.9', '5.0+'], datasets: [{ label: 'Events', data: values, backgroundColor: ['#2ecc71', '#f39c12', '#e67e22', '#e74c3c'], borderRadius: 6 }] };
            if (eqMagnitudeChart) { eqMagnitudeChart.data = data; eqMagnitudeChart.update('none'); return; }
            eqMagnitudeChart = new Chart(canvas, { type: 'bar', data, options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { x: { ticks: { color: theme.text }, grid: { display: false } }, y: { beginAtZero: true, ticks: { color: theme.text, precision: 0 }, grid: { color: theme.grid } } } } });
        }

        function renderIntensityChart(values) {
            const canvas = document.getElementById('eqIntensityChart');
            if (!canvas || typeof Chart === 'undefined') return;
            const data = { labels: ['Minor', 'Light', 'Moderate', 'Critical'], datasets: [{ data: values, backgroundColor: ['#2ecc71', '#f39c12', '#e67e22', '#e74c3c'], borderWidth: 0 }] };
            if (eqIntensityChart) { eqIntensityChart.data = data; eqIntensityChart.update('none'); return; }
            eqIntensityChart = new Chart(canvas, { type: 'doughnut', data, options: { responsive: true, maintainAspectRatio: false, cutout: '62%', plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, usePointStyle: true } } } } });
        }

        function renderFrequencyRows(quakes) {
            const container = document.getElementById('eqFrequencyRows');
            if (!container) return;
            if (!quakes.length) { container.innerHTML = '<div class="eq-frequency-empty">Waiting for PHIVOLCS data...</div>'; return; }
            const groups = new Map();
            quakes.forEach(q => {
                const parsed = parsePhivolcsDate(q.date_time);
                const key = parsed ? parsed.toLocaleDateString([], { month: 'short', day: 'numeric' }) : 'Unknown';
                groups.set(key, (groups.get(key) || 0) + 1);
            });
            const max = Math.max(...groups.values());
            container.innerHTML = Array.from(groups.entries()).slice(0, 7).map(([label, count]) => {
                const pct = max ? Math.max(8, (count / max) * 100) : 0;
                return `<div class="eq-frequency-row"><span>${label}</span><div><b style="width:${pct}%"></b></div><strong>${count}</strong></div>`;
            }).join('');
        }
        function renderBulletins(quakes, isCached) {
            const container = document.getElementById('earthquakeBulletinFeed');
            if (!container) return;

            if (quakes.length === 0) {
                container.innerHTML = '<div style="text-align:center; padding:3rem; opacity:0.7;"><i class="fas fa-search" style="font-size:2.5rem; color:var(--text-secondary-1); margin-bottom:0.75rem; display:block;"></i><p style="font-size:1rem; font-weight:600; color:var(--text-color-1);">No Bulletins Found</p><p style="font-size:0.85rem; color:var(--text-secondary-1);">No earthquake records found for the selected date.</p></div>';
                return;
            }

            let html = '';

            if (isCached) {
                html += '<div style="background:#7f8c8d; color:white; font-size:0.7rem; font-weight:700; padding:0.25rem 0.6rem; border-radius:4px; display:inline-block; margin-bottom:0.75rem; text-transform:uppercase; letter-spacing:0.04em;"><i class="fas fa-info-circle"></i> Showing cached data (PHIVOLCS temporarily unreachable)</div>';
            }

            quakes.forEach((q, i) => {
                const mag = q.magnitude;
                const dist = calculateDistanceKm(q.latitude, q.longitude, 14.6488, 121.0509).toFixed(0);

                let magBg = '#2ecc71', magLabel = 'Minor';
                if (mag >= 5.0) { magBg = '#e74c3c'; magLabel = 'Critical'; }
                else if (mag >= 4.0) { magBg = '#e67e22'; magLabel = 'Moderate'; }
                else if (mag >= 3.0) { magBg = '#f39c12'; magLabel = 'Light'; }

                let locDisplay = q.location || 'Philippines Region';
                locDisplay = locDisplay.replace(/^km\s+/, '');

                html += `
                <div style="display:flex; align-items:center; gap:0.85rem; padding:0.85rem 1rem; margin-bottom:0.5rem; border-radius:7px; background:var(--bg-color-2); border:1px solid var(--border-color-1); border-left:3px solid ${magBg}; transition:transform 0.15s ease;" onmouseover="this.style.transform='translateX(2px)'" onmouseout="this.style.transform='none'">
                    <div style="width:46px; height:46px; border-radius:50%; background:${magBg}; color:#fff; display:flex; align-items:center; justify-content:center; flex-direction:column; flex-shrink:0;">
                        <span style="font-size:1.05rem; font-weight:900; line-height:1;">${mag.toFixed(1)}</span>
                        <span style="font-size:0.45rem; text-transform:uppercase; opacity:0.85; letter-spacing:0.4px;">mag</span>
                    </div>
                    <div style="flex:1; min-width:0;">
                        <div style="font-weight:600; font-size:0.88rem; color:var(--text-color-1); margin-bottom:0.15rem; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">${locDisplay}</div>
                        <div style="display:flex; gap:0.6rem; flex-wrap:wrap; font-size:0.74rem; color:var(--text-secondary-1);">
                            <span><i class="fas fa-clock" style="color:${magBg}; margin-right:0.15rem;"></i>${q.date_time}</span>
                            <span><i class="fas fa-arrow-down" style="color:#3498db; margin-right:0.15rem;"></i>${q.depth_km} km deep</span>
                            <span><i class="fas fa-location-dot" style="margin-right:0.15rem;"></i>${dist} km from QC</span>
                        </div>
                    </div>
                    <div style="text-align:right; flex-shrink:0; display:flex; flex-direction:column; align-items:flex-end; gap:0.25rem;">
                        <span style="background:${magBg}15; color:${magBg}; border:1px solid ${magBg}44; font-weight:700; padding:0.15rem 0.45rem; font-size:0.65rem; border-radius:3px; text-transform:uppercase;">${magLabel}</span>
                        ${q.bulletin_link ? '<a href="'+q.bulletin_link+'" target="_blank" style="font-size:0.65rem; color:#3498db; text-decoration:none; font-weight:600;"><i class="fas fa-file-alt"></i> Bulletin Page</a>' : ''}
                    </div>
                </div>`;
            });

            container.innerHTML = html;
        }

        // Map modal
        let eqMapInitialized = false;

        function openSeismicMapModal() {
            const modal = document.getElementById('seismicMapModal');
            if (modal) {
                modal.style.display = 'flex';
                if (!eqMapInitialized) {
                    initMap();
                    eqMapInitialized = true;
                } else if (map) {
                    setTimeout(() => { map.invalidateSize(); }, 100);
                }
            }
        }

        function closeSeismicMapModal() {
            const modal = document.getElementById('seismicMapModal');
            if (modal) modal.style.display = 'none';
        }

        function focusQuezonCity() { if (map) map.flyTo([14.6488, 121.0509], 12); }

        function latestEarthquakeForAnalysis() {
            return phivolcsData && phivolcsData.length ? phivolcsData[0] : null;
        }

        function latestEarthquakeForAlert() {
            return phivolcsData && phivolcsData.length ? phivolcsData.find(q => Number(q.magnitude || 0) >= 4.5) || null : null;
        }

        function earthquakeLocalAnalysis(q) {
            if (!q) return 'No PHIVOLCS bulletin is loaded yet.';
            const distance = calculateDistanceKm(q.latitude, q.longitude, 14.6488, 121.0509).toFixed(0);
            let level = 'Low';
            if (q.magnitude >= 5 || distance <= 100) level = 'High';
            else if (q.magnitude >= 4 || distance <= 200) level = 'Moderate';
            return `<strong>Latest event:</strong> Magnitude ${Number(q.magnitude).toFixed(1)} near ${q.location || 'the Philippines'}<br><strong>QC distance:</strong> about ${distance} km<br><strong>Suggested level:</strong> ${level}<br><strong>Actions:</strong> Monitor PHIVOLCS updates, prepare public advisory, remind citizens to Drop, Cover, and Hold On if shaking is felt, and check buildings for visible damage.`;
        }

        async function getAIEarthquakeAnalysis() {
            const container = document.getElementById('eqAiAnalysis');
            const status = document.getElementById('eqAiStatus');
            const q = latestEarthquakeForAnalysis();
            if (!q) {
                container.innerHTML = 'Please wait for PHIVOLCS bulletins to load.';
                return;
            }
            status.textContent = 'Analyzing...';
            status.style.background = '#7c2d12';
            container.innerHTML = '<i class="fas fa-spinner fa-spin"></i> AI is analyzing the latest earthquake bulletin...';
            try {
                const prompt = `Analyze this PHIVOLCS earthquake bulletin for Quezon City emergency communications. Keep it concise and practical. Include risk level, possible impact, recommended LGU actions, and citizen alert wording. Data: ${JSON.stringify(q)}`;
                const response = await fetch('../api/gemini-proxy.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ prompt })
                });
                const data = await response.json();
                if (data.success && data.response) {
                    container.innerHTML = String(data.response).replace(/\n/g, '<br>');
                    status.textContent = 'Complete';
                    status.style.background = '#14532d';
                    return;
                }
                throw new Error(data.message || 'AI response unavailable');
            } catch (error) {
                console.error('Earthquake AI analysis error:', error);
                container.innerHTML = earthquakeLocalAnalysis(q);
                status.textContent = 'Local';
                status.style.background = '#334155';
            }
        }

        function showEarthquakeSendModal(message, onConfirm) {
            let modal = document.getElementById('earthquakeSendModal');
            if (!modal) {
                modal = document.createElement('div');
                modal.id = 'earthquakeSendModal';
                modal.className = 'alertara-action-modal';
                modal.innerHTML = `
                    <div class="alertara-action-dialog">
                        <div class="alertara-action-icon"><i class="fas fa-mountain"></i></div>
                        <div class="alertara-action-copy">
                            <h3>Send Earthquake Alert</h3>
                            <p id="earthquakeSendModalMessage"></p>
                        </div>
                        <div class="alertara-action-actions">
                            <button type="button" class="alertara-modal-secondary" data-action="cancel">Cancel</button>
                            <button type="button" class="alertara-modal-primary" data-action="confirm"><i class="fas fa-paper-plane"></i> Send Alert</button>
                        </div>
                    </div>`;
                document.body.appendChild(modal);
            }
            modal.querySelector('#earthquakeSendModalMessage').textContent = message;
            modal.style.display = 'flex';
            const close = () => { modal.style.display = 'none'; };
            modal.querySelector('[data-action="cancel"]').onclick = close;
            modal.querySelector('[data-action="confirm"]').onclick = () => { close(); onConfirm(); };
            modal.onclick = (event) => { if (event.target === modal) close(); };
        }

        function showEarthquakeSendResult(title, message, isError = false) {
            let modal = document.getElementById('earthquakeResultModal');
            if (!modal) {
                modal = document.createElement('div');
                modal.id = 'earthquakeResultModal';
                modal.className = 'alertara-action-modal';
                modal.innerHTML = `
                    <div class="alertara-action-dialog">
                        <div class="alertara-action-icon"><i class="fas fa-bell"></i></div>
                        <div class="alertara-action-copy">
                            <h3 id="earthquakeResultTitle"></h3>
                            <p id="earthquakeResultMessage"></p>
                        </div>
                        <div class="alertara-action-actions single">
                            <button type="button" class="alertara-modal-primary" data-action="ok">OK</button>
                        </div>
                    </div>`;
                document.body.appendChild(modal);
            }
            modal.classList.toggle('is-error', isError);
            modal.querySelector('#earthquakeResultTitle').textContent = title;
            modal.querySelector('#earthquakeResultMessage').textContent = message;
            modal.style.display = 'flex';
            const close = () => { modal.style.display = 'none'; };
            modal.querySelector('[data-action="ok"]').onclick = close;
            modal.onclick = (event) => { if (event.target === modal) close(); };
        }


        function earthquakeEventAutoKey(event) {
            if (!event) return '';
            return [
                event.event_hash || event.bulletin_link || '',
                event.date_time || '',
                Number(event.magnitude || 0).toFixed(1),
                event.location || '',
                event.depth_km || ''
            ].join('|');
        }

        async function maybeAutoSendEarthquakeAlert() {
            const latest = latestEarthquakeForAlert();
            if (isCachedData || !latest || Number(latest.magnitude || 0) < 4.5 || phivolcsAutoAlertInFlight) return;

            const key = earthquakeEventAutoKey(latest);
            if (key && localStorage.getItem('phivolcsAutoAlertLastKey') === key) return;

            phivolcsAutoAlertInFlight = true;
            try {
                const response = await fetch('../api/phivolcs-auto-alert.php?action=realtime', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ event: latest })
                });
                const data = await response.json().catch(() => ({}));
                if (data && data.success && key && (data.alerted || /already sent|already alerted/i.test(String(data.message || '')))) {
                    localStorage.setItem('phivolcsAutoAlertLastKey', key);
                }
                if (data && data.alerted && typeof loadNotificationStats === 'function') {
                    loadNotificationStats();
                }
            } catch (error) {
                console.warn('PHIVOLCS realtime auto-alert check failed:', error);
            } finally {
                phivolcsAutoAlertInFlight = false;
            }
        }
        async function sendEarthquakeAlert() {
            showEarthquakeSendModal('Send the latest PHIVOLCS earthquake activity above magnitude 4.5 to users by push notification and email?', async () => {
                const status = document.getElementById('eqAiStatus');
                status.textContent = 'Sending...';
                status.style.background = '#7c2d12';
                try {
                    const latest = latestEarthquakeForAlert();
                    if (!latest) {
                        status.textContent = 'Ready';
                        status.style.background = '#334155';
                        showEarthquakeSendResult('No Alert Needed', 'No latest PHIVOLCS earthquake activity above magnitude 4.5 is available to send.', false);
                        return;
                    }
                    const response = await fetch('../api/phivolcs-auto-alert.php?action=force', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ event: latest })
                    });
                    const text = await response.text();
                    let data = {};
                    try { data = text ? JSON.parse(text) : {}; } catch (_) { data = { success: false, message: text || 'Invalid server response.' }; }
                    if (!response.ok || !data.success) throw new Error(data.message || 'Unable to send earthquake alert.');
                    status.textContent = data.alerted ? 'Sent' : 'Ready';
                    status.style.background = data.alerted ? '#14532d' : '#334155';
                    const queued = data.dispatch && typeof data.dispatch.queued_jobs !== 'undefined' ? ` Queued jobs: ${data.dispatch.queued_jobs}.` : '';
                    showEarthquakeSendResult(data.alerted ? 'Earthquake Alert Queued' : 'No Alert Needed', (data.message || (data.alerted ? 'Earthquake alert queued.' : 'No qualifying event to send.')) + queued, false);
                } catch (error) {
                    console.error('PHIVOLCS manual send error:', error);
                    status.textContent = 'Error';
                    status.style.background = '#991b1b';
                    showEarthquakeSendResult('Earthquake Alert Failed', error.message, true);
                }
            });
        }
        // Auto-refresh frequently so qualifying 4.5+ PHIVOLCS events are broadcast quickly.
        document.addEventListener('DOMContentLoaded', () => {
            loadPhivolcsBulletins();
            setInterval(() => {
                loadPhivolcsBulletins();
            }, 30000);
        });
    </script>

    <!-- Seismic Map Modal -->
    <div id="seismicMapModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; z-index:99999; background:rgba(0,0,0,0.8); align-items:center; justify-content:center;">
        <div onclick="closeSeismicMapModal()" style="position:absolute; width:100%; height:100%; cursor:pointer; z-index:99998;"></div>
        <div style="position:relative; width:90%; max-width:1000px; height:80%; background:var(--card-bg-1); border-radius:12px; overflow:hidden; display:flex; flex-direction:column; border:1px solid var(--border-color-1); box-shadow:0 20px 25px -5px rgba(0,0,0,0.3); z-index:99999;">
            <div style="padding:0.85rem 1.25rem; display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid var(--border-color-1); background:var(--bg-color-2);">
                <h2 style="margin:0; font-size:1.15rem; font-weight:700; color:var(--text-color-1); display:flex; align-items:center; gap:0.5rem;"><i class="fas fa-map-marked-alt" style="color:#e74c3c;"></i> PHIVOLCS Seismic Map</h2>
                <div style="display:flex; align-items:center; gap:0.6rem;">
                    <button onclick="focusQuezonCity()" style="background:var(--bg-color-1); border:1px solid var(--border-color-1); color:var(--text-color-1); padding:0.35rem 0.65rem; border-radius:5px; cursor:pointer; font-size:0.78rem; font-weight:600;"><i class="fas fa-crosshairs"></i> QC Focus</button>
                    <button onclick="closeSeismicMapModal()" style="background:none; border:none; font-size:1.75rem; cursor:pointer; color:var(--text-color-2); line-height:1; padding:0;">&times;</button>
                </div>
            </div>
            <div style="flex:1; position:relative; background:#e5e5e5; display:flex; flex-direction:column; min-height:400px;">
                <div id="earthquakeMap" style="width:100%; height:100%; flex:1; z-index:1;"></div>
                <div style="position:absolute; top:15px; right:15px; z-index:1000; background:rgba(0,0,0,0.75); color:white; padding:0.45rem 0.65rem; border-radius:6px; font-size:0.7rem;">
                    <div style="font-weight:700; margin-bottom:0.25rem;">Magnitude</div>
                    <div style="display:flex; align-items:center; gap:0.3rem; margin-bottom:0.1rem;"><span style="width:8px;height:8px;border-radius:50%;background:#e74c3c;display:inline-block;"></span> ≥5.0 Major</div>
                    <div style="display:flex; align-items:center; gap:0.3rem; margin-bottom:0.1rem;"><span style="width:8px;height:8px;border-radius:50%;background:#f39c12;display:inline-block;"></span> 3.0-4.9</div>
                    <div style="display:flex; align-items:center; gap:0.3rem;"><span style="width:8px;height:8px;border-radius:50%;background:#2ecc71;display:inline-block;"></span> <3.0</div>
                </div>
            </div>
        </div>
    </div>

    <style>
        .earthquake-layout-grid { display:grid; grid-template-columns:minmax(0, 1fr) 420px; gap:1.5rem; align-items:start; }
        .earthquake-main-column { min-width:0; }
        .earthquake-sidebar { position:sticky; top:5.25rem; max-height:calc(100vh - 6.25rem); overflow-y:auto; padding-right:0.35rem; scrollbar-width:thin; }
        #earthquakeBulletinFeed { max-height:calc(100vh - 360px) !important; min-height:460px; }
        .eq-analytics-card { background:var(--card-bg-1); border:1px solid var(--border-color-1); border-radius:8px; padding:1.05rem; margin-bottom:1rem; box-shadow:0 8px 20px rgba(15,23,42,0.08); }
        .eq-card-heading { display:flex; align-items:center; justify-content:space-between; gap:0.75rem; margin-bottom:0.85rem; }
        .eq-card-heading h3 { margin:0; font-size:0.95rem; font-weight:800; color:var(--text-color-1); display:flex; align-items:center; gap:0.45rem; }
        .eq-card-heading i { color:#e74c3c; }
        .eq-live-pill { border:1px solid rgba(39,174,96,0.32); background:rgba(39,174,96,0.11); color:#16a34a; border-radius:999px; padding:0.18rem 0.55rem; font-size:0.68rem; font-weight:800; display:inline-flex; align-items:center; gap:0.3rem; text-transform:uppercase; }
        .eq-analytics-grid { display:grid; grid-template-columns:repeat(2, minmax(0, 1fr)); gap:0.65rem; }
        .eq-metric-tile { background:var(--bg-color-2); border:1px solid var(--border-color-1); border-radius:7px; padding:0.75rem; min-height:72px; display:flex; flex-direction:column; justify-content:space-between; }
        .eq-metric-tile span { color:var(--text-secondary-1); font-size:0.7rem; text-transform:uppercase; font-weight:800; letter-spacing:0.03em; }
        .eq-metric-tile strong { color:var(--text-color-1); font-size:1.25rem; font-weight:900; line-height:1.1; }
        .eq-analytics-card canvas { width:100% !important; max-height:180px; }
        .eq-frequency-rows { display:flex; flex-direction:column; gap:0.55rem; }
        .eq-frequency-row { display:grid; grid-template-columns:70px 1fr 32px; align-items:center; gap:0.55rem; color:var(--text-color-1); font-size:0.78rem; font-weight:700; }
        .eq-frequency-row div { height:9px; background:var(--bg-color-2); border-radius:999px; overflow:hidden; border:1px solid var(--border-color-1); }
        .eq-frequency-row b { height:100%; display:block; background:linear-gradient(90deg,#f39c12,#e74c3c); border-radius:999px; }
        .eq-frequency-row strong { text-align:right; color:var(--text-secondary-1); }
        .eq-frequency-empty { color:var(--text-secondary-1); border:1px dashed var(--border-color-1); border-radius:7px; padding:1rem; text-align:center; font-size:0.82rem; }
        .alertara-action-modal { position:fixed; inset:0; z-index:100000; display:none; align-items:center; justify-content:center; padding:1.25rem; background:rgba(4,15,20,0.62); backdrop-filter:blur(4px); }
        .alertara-action-dialog { width:min(470px, 100%); background:var(--card-bg-1); color:var(--text-color-1); border:1px solid var(--border-color-1); border-radius:10px; box-shadow:0 22px 60px rgba(0,0,0,0.32); padding:1.25rem; display:grid; grid-template-columns:auto 1fr; gap:1rem; }
        .alertara-action-icon { width:48px; height:48px; border-radius:10px; display:flex; align-items:center; justify-content:center; color:#fff; background:linear-gradient(135deg,#c0392b,#e74c3c); font-size:1.25rem; }
        .alertara-action-copy h3 { margin:0 0 0.4rem; font-size:1rem; font-weight:800; }
        .alertara-action-copy p { margin:0; color:var(--text-secondary-1); line-height:1.45; font-size:0.9rem; }
        .alertara-action-actions { grid-column:1 / -1; display:flex; justify-content:flex-end; gap:0.7rem; margin-top:0.6rem; }
        .alertara-action-actions.single { justify-content:flex-end; }
        .alertara-modal-primary, .alertara-modal-secondary { border:0; border-radius:8px; padding:0.7rem 1rem; font-weight:800; cursor:pointer; display:inline-flex; align-items:center; gap:0.4rem; }
        .alertara-modal-primary { background:#4f9a97; color:#fff; }
        .alertara-modal-secondary { background:var(--bg-color-2); color:var(--text-color-1); border:1px solid var(--border-color-1); }
        .alertara-action-modal.is-error .alertara-action-icon { background:linear-gradient(135deg,#991b1b,#ef4444); }
        @media (max-width: 1100px) { .earthquake-layout-grid { grid-template-columns:1fr; } .earthquake-sidebar { position:static; } }
        .eq-live-dot { width:7px; height:7px; border-radius:50%; background:#27ae60; display:inline-block; animation:eqPulse 2s infinite; }
        @keyframes eqPulse {
            0% { box-shadow:0 0 0 0 rgba(39,174,96,0.7); }
            70% { box-shadow:0 0 0 5px rgba(39,174,96,0); }
            100% { box-shadow:0 0 0 0 rgba(39,174,96,0); }
        }
    </style>
</body>
</html>
