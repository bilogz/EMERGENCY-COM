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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
        <link rel="stylesheet" href="css/module-earthquake-monitoring.css?v=<?php echo filemtime(__DIR__ . '/css/module-earthquake-monitoring.css'); ?>">
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    <?php include 'includes/admin-header.php'; ?>
    
    <div class="main-content">
        <div class="main-container">
            <div class="title">
                <nav class="breadcrumb" aria-label="Breadcrumb">
                    <ol class="breadcrumb-list">
                        <li class="breadcrumb-item"><a href="dashboard.php" class="breadcrumb-link">Dashboard</a></li>
                        <li class="breadcrumb-item active">Earthquake Monitoring</li>
                    </ol>
                </nav>
                <h1><i class="fas fa-mountain" style="color: var(--primary-color-1); margin-right: 0.5rem;"></i> PHIVOLCS Earthquake Monitoring</h1>
                <p>Monitor real-time seismic activity in the Philippines region with AI-driven risk assessment for Quezon City.</p>
            </div>
            
            <div class="sub-container">
                <div class="page-content">
                    <!-- Statistics Grid -->
                    <div class="stat-grid">
                        <div class="stat-card">
                            <div class="stat-label">Events (30d)</div>
                            <div class="stat-value" id="totalEvents">-</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-label">Major (5.0+)</div>
                            <div class="stat-value" id="majorEvents" style="color: #e74c3c;">-</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-label">Latest Magnitude</div>
                            <div class="stat-value" id="latestMagnitude" style="color: var(--primary-color-1);">-</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-label">Last Updated</div>
                            <div class="stat-value" id="lastUpdate" style="font-size: 1.25rem; padding-top: 0.5rem;">-</div>
                            <small id="realtimeIndicator" style="color: #27ae60; font-weight: 600;"><i class="fas fa-circle fa-xs"></i> LIVE</small>
                        </div>
                    </div>
                    
                    <!-- NEW AI Risk Panel -->
                    <div class="ai-glass-panel" id="qcRiskAlertPanel">
                        <div class="qc-risk-header" onclick="toggleQCRiskMinimize()">
                            <div style="display: flex; align-items: center; gap: 1rem;">
                                <div style="background: rgba(139, 92, 246, 0.2); padding: 0.5rem; border-radius: 8px;">
                                    <i class="fas fa-robot" style="font-size: 1.25rem; color: #c4b5fd;"></i>
                                </div>
                                <div>
                                    <h3 style="margin: 0; font-size: 1rem; font-weight: 600; color: #fff;">AI Risk Assessment: Quezon City</h3>
                                    <div style="display: flex; align-items: center; gap: 0.75rem; margin-top: 0.25rem;">
                                        <div class="qc-risk-badge">
                                            <span class="live-pulse"></span>
                                            <span id="qcRiskBadgeText">MONITORING</span>
                                        </div>
                                        <span style="font-size: 0.75rem; color: #9ca3af;" id="aiRiskTimestamp">Waiting for data...</span>
                                    </div>
                                </div>
                            </div>
                            <div style="display: flex; align-items: center; gap: 1rem;">
                                <button class="earthquake-control-btn" style="background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2); color: white; padding: 0.4rem 0.8rem; font-size: 0.75rem;" onclick="event.stopPropagation(); generateAIRiskAssessment(earthquakeData, true);">
                                    <i class="fas fa-sync-alt"></i> RE-ANALYZE
                                </button>
                                <i class="fas fa-chevron-down" id="qcRiskChevron" style="color: #9ca3af;"></i>
                            </div>
                        </div>
                        
                        <div class="qc-risk-content" id="qcRiskContent">
                            <!-- Seismic Summary -->
                            <div class="risk-section">
                                <h4><i class="fas fa-wave-square"></i> Seismic Summary</h4>
                                <div class="risk-info-box ai-mono-text" id="aiSummaryText">
                                    <i class="fas fa-circle-notch fa-spin"></i> Initializing AI model...
                                </div>
                            </div>

                            <!-- Predictive Outlook -->
                            <div class="risk-section">
                                <h4><i class="fas fa-crystal-ball"></i> Predictive Outlook (7 Days)</h4>
                                <div class="risk-info-box ai-mono-text" id="aiPredictionText" style="border-left: 2px solid #8b5cf6;">
                                    <span style="opacity: 0.5;">Waiting for seismic data stream...</span>
                                </div>
                            </div>

                            <!-- Recommendations -->
                            <div class="risk-section" style="grid-column: 1 / -1;">
                                <h4><i class="fas fa-shield-alt"></i> Actionable Recommendations</h4>
                                <div id="aiRecommendations" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 0.75rem;">
                                    <!-- Recommendations injected here -->
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Analytics Grid -->
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem; margin-bottom: 1.5rem;">
                        <div class="module-card">
                            <div class="module-card-header"><h2><i class="fas fa-chart-area"></i> 30-Day Trend</h2></div>
                            <div style="padding: 1.5rem; height: 250px;"><canvas id="trendChart"></canvas></div>
                        </div>
                        <div class="module-card">
                            <div class="module-card-header"><h2><i class="fas fa-chart-pie"></i> Severity Split</h2></div>
                            <div style="padding: 1.5rem; height: 250px;"><canvas id="severityChart"></canvas></div>
                        </div>
                    </div>

                    <!-- Map Module -->
                    <div class="module-card">
                        <div class="module-card-header">
                            <h2><i class="fas fa-map-marked-alt"></i> Seismic Map</h2>
                            <div class="btn-group">
                                <button class="btn btn-sm btn-secondary" onclick="loadEarthquakeData()"><i class="fas fa-sync"></i></button>
                                <button class="btn btn-sm btn-primary" onclick="focusQuezonCity()"><i class="fas fa-crosshairs"></i> QC</button>
                            </div>
                        </div>
                        <div class="map-wrapper">
                            <div id="earthquakeMap"></div>
                            <div class="map-overlay-controls">
                                <button id="filterBtn" class="earthquake-control-btn"><i class="fas fa-filter"></i> <span>Filter</span></button>
                                <button id="snapshotBtn" class="earthquake-control-btn" onclick="downloadSnapshot()"><i class="fas fa-camera"></i> <span>Snapshot</span></button>
                            </div>
                            <div class="map-legend">
                                <div class="legend-title">Seismic Activity</div>
                                <div class="legend-item"><div class="legend-color" style="background: #e74c3c;"></div> Critical (≥5.0)</div>
                                <div class="legend-item"><div class="legend-color" style="background: #f39c12;"></div> Moderate (3.0-4.9)</div>
                                <div class="legend-item"><div class="legend-color" style="background: #2ecc71;"></div> Minor (<3.0)</div>
                            </div>
                        </div>
                    </div>

                    <!-- History Module -->
                    <div class="module-card">
                        <div class="module-card-header">
                            <h2><i class="fas fa-table"></i> Recent Activity Log</h2>
                            <button class="btn btn-sm btn-primary" onclick="downloadFullReport()"><i class="fas fa-file-pdf"></i> Report</button>
                        </div>
                        <div class="module-card-content table-responsive" style="padding: 0;">
                            <table id="earthquakeTable" class="data-table">
                                <thead>
                                    <tr>
                                        <th>Date & Time</th>
                                        <th>Mag</th>
                                        <th>Depth</th>
                                        <th>Location</th>
                                        <th>Dist (QC)</th>
                                        <th>Alert</th>
                                    </tr>
                                </thead>
                                <tbody id="earthquakeTableBody">
                                    <tr><td colspan="6" style="text-align: center; padding: 2rem;">Loading seismic data...</td></tr>
                                </tbody>
                            </table>
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
            
            document.getElementById('filterBtn').onclick = showFilterDialog;
        }

        function loadQuezonCityBoundary() {
            fetch('../api/quezon-city.geojson')
                .then(r => r.json())
                .then(data => {
                    L.geoJSON(data, {
                        style: { color: '#e74c3c', weight: 3, fillColor: '#e74c3c', fillOpacity: 0.05 }
                    }).addTo(map);
                });
        }

        function loadEarthquakeData() {
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
                    
                    if (!initialZoomDone) {
                        const critical = data.features.find(f => f.properties.mag >= 5.0);
                        if (critical) {
                            const [lon, lat] = critical.geometry.coordinates;
                            map.flyTo([lat, lon], 8, { animate: true, duration: 1.5 });
                        }
                        initialZoomDone = true;
                    }
                });
        }

        function updateMarkers(features) {
            earthquakeMarkers.forEach(m => map.removeLayer(m));
            earthquakeMarkers = [];
            
            features.forEach(f => {
                const [lon, lat] = f.geometry.coordinates;
                const mag = f.properties.mag || 0;
                
                let color;
                if (mag >= 5.0) color = '#e74c3c';
                else if (mag >= 3.0) color = '#f39c12';
                else color = '#2ecc71';
                
                let animClass = '';
                if (mag >= 5.0) animClass = 'quake-pulse';
                
                const icon = L.divIcon({
                    className: 'earthquake-marker-custom',
                    html: `<div class="${animClass}" style="background:${color}; width:16px; height:16px; border-radius:50%; border:2px solid white; box-shadow:0 2px 4px rgba(0,0,0,0.3); position:relative;"></div>`,
                    iconSize: [16, 16]
                });
                
                const marker = L.marker([lat, lon], { icon }).addTo(map)
                    .bindPopup(`<strong>Magnitude ${mag}</strong><br>${f.properties.place}<br><small>${new Date(f.properties.time).toLocaleString()}</small>`);
                earthquakeMarkers.push(marker);
            });
        }

        function updateStatistics(features) {
            document.getElementById('totalEvents').textContent = features.length;
            document.getElementById('majorEvents').textContent = features.filter(f => f.properties.mag >= 5).length;
            const latest = features[0]?.properties.mag || '-';
            document.getElementById('latestMagnitude').textContent = typeof latest === 'number' ? latest.toFixed(1) : latest;
            document.getElementById('lastUpdate').textContent = new Date().toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
        }

        function updateQuezonCityRisk(features) {
            // Initial simple assessment before AI kicks in
            renderStaticRiskAssessment(features);
            // Trigger AI Analysis
            generateAIRiskAssessment(features, false);
        }

        function renderStaticRiskAssessment(features) {
            const qcLat = 14.6488, qcLon = 121.0509;
            const nearby = features.filter(f => {
                const [lon, lat] = f.geometry.coordinates;
                return calculateDistanceKm(lat, lon, qcLat, qcLon) <= 200;
            });
            
            const significant = nearby.sort((a,b) => b.properties.mag - a.properties.mag)[0];
            const badgeText = document.getElementById('qcRiskBadgeText');
            
            if (significant) {
                const mag = significant.properties.mag;
                const dist = calculateDistanceKm(significant.geometry.coordinates[1], significant.geometry.coordinates[0], qcLat, qcLon);
                if (mag >= 5 && dist < 50) badgeText.textContent = "CRITICAL";
                else if (mag >= 4.5 && dist < 100) badgeText.textContent = "HIGH";
                else if (mag >= 4) badgeText.textContent = "MODERATE";
                else badgeText.textContent = "MONITORING";
            } else {
                badgeText.textContent = "STABLE";
            }
        }

        // --- NEW TYPEWRITER EFFECT HELPER ---
        function typeWriter(elementId, text, speed = 20) {
            const el = document.getElementById(elementId);
            if (!el) return;
            el.innerHTML = '';
            let i = 0;
            function type() {
                if (i < text.length) {
                    el.innerHTML += text.charAt(i);
                    i++;
                    setTimeout(type, speed);
                }
            }
            type();
        }

        function generateAIRiskAssessment(features, force = false) {
            const now = Date.now();
            if (!force && now - lastAIAnalysisTime < 300000) return;
            
            // Set loading state in UI
            const timestamp = document.getElementById('aiRiskTimestamp');
            timestamp.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i> Analyzing...';
            document.getElementById('aiSummaryText').innerHTML = '<span class="loading-dots">Analyzing seismic data stream...</span>';
            document.getElementById('aiPredictionText').innerHTML = '<span class="loading-dots">Computing predictive model...</span>';
            
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
                    document.getElementById('aiSummaryText').textContent = "AI Analysis Unavailable. System will retry automatically.";
                    timestamp.textContent = "Connection Failed";
                }
            })
            .catch(() => { 
                document.getElementById('aiSummaryText').textContent = "AI Service Unreachable. Checking connection...";
                timestamp.textContent = "Offline"; 
            });
        }

        function renderAIRiskContent(analysis, time) {
            document.getElementById('aiRiskTimestamp').textContent = `LIVE | Updated: ${new Date(time).toLocaleTimeString()}`;
            
            // Defensive coding to prevent "undefined" and handle new API structure
            // New keys: seismic_summary, predictive_outlook, risk_level, actionable_recommendations
            const summary = analysis.seismic_summary || analysis.risk_summary || "No significant seismic anomalies detected in the current data stream.";
            const prediction = analysis.predictive_outlook || analysis.prediction || "Predictive models show stable seismic activity for the next 7 days.";
            const riskLevel = (analysis.risk_level || analysis.ai_risk_level || "LOW").toUpperCase();

            // Update Badge
            const badgeText = document.getElementById('qcRiskBadgeText');
            badgeText.textContent = `AI: ${riskLevel}`;
            
            // Apply visual alerts if high risk
            const panel = document.getElementById('qcRiskAlertPanel');
            if (riskLevel === 'HIGH' || riskLevel === 'CRITICAL') {
                panel.classList.add('critical-alert');
            } else {
                panel.classList.remove('critical-alert');
            }

            // Stream text using typewriter effect
            typeWriter('aiSummaryText', summary, 15);
            
            // Delay prediction typing slightly for effect
            setTimeout(() => {
                typeWriter('aiPredictionText', prediction, 15);
            }, 500);
            
            // Handle recommendations: New API returns a string, old returned array
            let recs = [];
            if (analysis.actionable_recommendations) {
                // If it's a string (new format), wrap in array. If it's already array (unlikely but safe), use it.
                recs = Array.isArray(analysis.actionable_recommendations) ? analysis.actionable_recommendations : [analysis.actionable_recommendations];
            } else if (analysis.recommendations) {
                // Fallback for old format
                recs = analysis.recommendations;
            } else {
                recs = ["Continue monitoring standard channels."];
            }

            document.getElementById('aiRecommendations').innerHTML = recs.map(r => 
                `<div class="recommendation-item"><i class="fas fa-check-circle"></i> ${r}</div>`
            ).join('');
        }

        function toggleQCRiskMinimize() {
            document.getElementById('qcRiskAlertPanel').classList.toggle('minimized');
            document.getElementById('qcRiskChevron').classList.toggle('fa-chevron-up');
            document.getElementById('qcRiskChevron').classList.toggle('fa-chevron-down');
        }

        function calculateDistanceKm(lat1, lon1, lat2, lon2) {
            const R = 6371;
            const dLat = (lat2 - lat1) * Math.PI / 180;
            const dLon = (lon2 - lon1) * Math.PI / 180;
            const a = Math.sin(dLat/2) ** 2 + Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) * Math.sin(dLon/2) ** 2;
            return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
        }

        function updateCharts(features) {
            const counts = [0, 0, 0];
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
                    labels: ['Minor', 'Moderate', 'Major'],
                    datasets: [{ data: counts, backgroundColor: ['#2ecc71', '#f39c12', '#e74c3c'] }]
                },
                options: { maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } }
            });

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
                if (dayDiff >= 0 && dayDiff < 30) dailyCounts[29 - dayDiff]++;
            });

            if (trendChartInstance) trendChartInstance.destroy();
            trendChartInstance = new Chart(document.getElementById('trendChart'), {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Seismic Events',
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
            reportContainer.style.padding = '40px';
            reportContainer.style.background = 'white';
            reportContainer.innerHTML = `
                <h1 style="color: #333; border-bottom: 2px solid #667eea; padding-bottom: 10px;">QC Seismic Activity Report</h1>
                <p>Generated: ${new Date().toLocaleString()}</p>
                <div style="margin: 20px 0; padding: 20px; background: #f8f9fa; border-radius: 8px;">
                    <h3>AI Risk Analysis</h3>
                    <p>${document.getElementById('aiSummaryText').innerHTML}</p>
                </div>
                <h3>Recent Significant Events</h3>
                <table style="width: 100%; border-collapse: collapse; margin-top: 20px;">
                    <thead><tr style="background: #eee;">
                        <th style="padding: 10px; border: 1px solid #ddd;">Date</th>
                        <th style="padding: 10px; border: 1px solid #ddd;">Mag</th>
                        <th style="padding: 10px; border: 1px solid #ddd;">Location</th>
                    </tr></thead>
                    <tbody>${earthquakeData.slice(0, 15).map(f => `
                        <tr>
                            <td style="padding: 10px; border: 1px solid #ddd;">${new Date(f.properties.time).toLocaleString()}</td>
                            <td style="padding: 10px; border: 1px solid #ddd;">${f.properties.mag.toFixed(1)}</td>
                            <td style="padding: 10px; border: 1px solid #ddd;">${f.properties.place}</td>
                        </tr>`).join('')}
                    </tbody>
                </table>
            `;
            html2pdf().from(reportContainer).set({ filename: 'QC_Seismic_Report.pdf' }).save();
        }

        function updateTable(features) {
            const body = document.getElementById('earthquakeTableBody');
            body.innerHTML = features.slice(0, 15).map(f => {
                const dist = calculateDistanceKm(f.geometry.coordinates[1], f.geometry.coordinates[0], 14.6488, 121.0509).toFixed(1);
                const alertClass = f.properties.mag >= 5 ? 'danger' : 'normal';
                return `<tr>
                    <td><small>${new Date(f.properties.time).toLocaleString()}</small></td>
                    <td><strong>${f.properties.mag.toFixed(1)}</strong></td>
                    <td>${f.geometry.coordinates[2].toFixed(1)} km</td>
                    <td><div style="max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">${f.properties.place}</div></td>
                    <td>${dist} km</td>
                    <td><span class="badge ${alertClass}">${f.properties.mag >= 5 ? 'Critical' : 'Normal'}</span></td>
                </tr>`;
            }).join('');
        }

        function showFilterDialog() {
            const val = prompt("Enter min magnitude:", minMagnitude);
            if (val && !isNaN(val)) { minMagnitude = parseFloat(val); loadEarthquakeData(); }
        }

        function focusQuezonCity() { map.flyTo([14.6488, 121.0509], 12); }

        function downloadSnapshot() {
            alert('Snapshot feature: Use browser screenshot or Print to PDF.');
        }

        document.addEventListener('DOMContentLoaded', initMap);
    </script>
</body>
</html>
