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
            </div>
        </div>
    </div>

    <script>
        let map;
        let earthquakeMarkers = [];
        let phivolcsData = [];

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

        function loadPhivolcsBulletins() {
            const container = document.getElementById('earthquakeBulletinFeed');
            
            fetch('../api/phivolcs-scraper.php')
                .then(r => r.json())
                .then(data => {
                    if (data.success && data.earthquakes && data.earthquakes.length > 0) {
                        phivolcsData = data.earthquakes;
                        isCachedData = !!data.is_cached;
                        filterAndRenderBulletins();
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
            const parts = dateStr.split('-');
            if (parts.length === 0) return null;
            const datePart = parts[0].trim(); // "12 July 2026"
            const d = new Date(datePart);
            return isNaN(d.getTime()) ? null : d;
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

        // Auto-refresh every 2 minutes
        document.addEventListener('DOMContentLoaded', () => {
            loadPhivolcsBulletins();
            setInterval(() => {
                loadPhivolcsBulletins();
            }, 120000);
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
        .eq-live-dot { width:7px; height:7px; border-radius:50%; background:#27ae60; display:inline-block; animation:eqPulse 2s infinite; }
        @keyframes eqPulse {
            0% { box-shadow:0 0 0 0 rgba(39,174,96,0.7); }
            70% { box-shadow:0 0 0 5px rgba(39,174,96,0); }
            100% { box-shadow:0 0 0 0 rgba(39,174,96,0); }
        }
    </style>
</body>
</html>
