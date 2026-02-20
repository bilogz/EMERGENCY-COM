<?php
/**
 * Automated Warnings Analytics Page
 * SaaS-style analytics for weather and earthquake warning streams.
 */

session_start();

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../login.php');
    exit();
}

// Avoid stale HTML when testing through tunnels/browsers.
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

$pageTitle = 'Automated Warnings Analytics';
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
    <link rel="stylesheet" href="css/datatables.css">
    <link rel="stylesheet" href="css/hero.css">
    <link rel="stylesheet" href="css/sidebar-footer.css">
    <link rel="stylesheet" href="css/modules.css">
    <link rel="stylesheet" href="css/module-automated-warnings-analytics.css?v=<?php echo filemtime(__DIR__ . '/css/module-automated-warnings-analytics.css'); ?>">
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    <?php include 'includes/admin-header.php'; ?>

    <div class="main-content">
        <div class="main-container">
            <div class="title">
                <nav class="breadcrumb" aria-label="Breadcrumb">
                    <ol class="breadcrumb-list">
                        <li class="breadcrumb-item">
                            <a href="dashboard.php" class="breadcrumb-link">Dashboard</a>
                        </li>
                        <li class="breadcrumb-item">
                            <a href="automated-warnings.php" class="breadcrumb-link">Automated Warnings</a>
                        </li>
                        <li class="breadcrumb-item active" aria-current="page">Analytics</li>
                    </ol>
                </nav>
                <h1><i class="fas fa-chart-pie" style="color: var(--primary-color-1); margin-right: 0.5rem;"></i> Automated Warnings Analytics</h1>
                <p>End-to-end analytics for weather and earthquake warning ingestion, severity, and publishing performance.</p>
                <div class="info-box" id="analyticsHealthNotice" style="display: none;">
                    <i class="fas fa-circle-info"></i>
                    <div id="analyticsHealthText">Analytics data source status.</div>
                </div>
                <div style="display:flex; gap:0.75rem; margin-top:0.75rem; flex-wrap:wrap;">
                    <button type="button" class="btn" onclick="sendMockCriticalAlert('weather')" style="background:#f59e0b; border:1px solid #f59e0b; color:#fff; padding:0.7rem 1rem;">
                        <i class="fas fa-cloud-showers-heavy"></i> Mock Critical Weather
                    </button>
                    <button type="button" class="btn" onclick="sendMockCriticalAlert('earthquake')" style="background:#dc2626; border:1px solid #dc2626; color:#fff; padding:0.7rem 1rem;">
                        <i class="fas fa-house-crack"></i> Mock Critical Earthquake
                    </button>
                </div>
            </div>

            <div class="sub-container">
                <div class="page-content">
                    <section class="module-analytics-strip" aria-label="Overview cards">
                        <div class="module-analytics-title">Automated Warning Analytics</div>
                        <div class="module-analytics-grid">
                            <article class="module-analytics-card tone-a">
                                <div class="module-analytics-head">
                                    <span class="module-analytics-label">Total Warnings</span>
                                    <span class="module-analytics-icon"><i class="fas fa-wave-square"></i></span>
                                </div>
                                <div class="module-analytics-value" id="kpiTotal">0</div>
                                <div class="module-analytics-sub">All weather + earthquake records</div>
                            </article>
                            <article class="module-analytics-card tone-b">
                                <div class="module-analytics-head">
                                    <span class="module-analytics-label">Published</span>
                                    <span class="module-analytics-icon"><i class="fas fa-paper-plane"></i></span>
                                </div>
                                <div class="module-analytics-value" id="kpiPublished">0</div>
                                <div class="module-analytics-sub">Warnings pushed to distribution</div>
                            </article>
                            <article class="module-analytics-card tone-c">
                                <div class="module-analytics-head">
                                    <span class="module-analytics-label">Critical</span>
                                    <span class="module-analytics-icon"><i class="fas fa-triangle-exclamation"></i></span>
                                </div>
                                <div class="module-analytics-value" id="kpiCritical">0</div>
                                <div class="module-analytics-sub">High-priority detection events</div>
                            </article>
                            <article class="module-analytics-card tone-d">
                                <div class="module-analytics-head">
                                    <span class="module-analytics-label">Last 24 Hours</span>
                                    <span class="module-analytics-icon"><i class="fas fa-clock"></i></span>
                                </div>
                                <div class="module-analytics-value" id="kpiLast24">0</div>
                                <div class="module-analytics-sub">New warnings ingested today</div>
                            </article>
                        </div>
                    </section>

                    <section class="aw-source-grid" aria-label="Source analytics">
                        <article class="aw-source-card">
                            <div class="aw-source-header">
                                <h3><i class="fas fa-cloud-rain"></i> Weather Stream (PAGASA)</h3>
                            </div>
                            <div class="aw-source-metrics">
                                <div><span>Total</span><strong id="weatherTotal">0</strong></div>
                                <div><span>Published</span><strong id="weatherPublished">0</strong></div>
                                <div><span>Pending</span><strong id="weatherPending">0</strong></div>
                                <div><span>Critical</span><strong id="weatherCritical">0</strong></div>
                            </div>
                        </article>
                        <article class="aw-source-card">
                            <div class="aw-source-header">
                                <h3><i class="fas fa-mountain"></i> Earthquake Stream (PHIVOLCS)</h3>
                            </div>
                            <div class="aw-source-metrics">
                                <div><span>Total</span><strong id="quakeTotal">0</strong></div>
                                <div><span>Published</span><strong id="quakePublished">0</strong></div>
                                <div><span>Pending</span><strong id="quakePending">0</strong></div>
                                <div><span>Critical</span><strong id="quakeCritical">0</strong></div>
                            </div>
                        </article>
                    </section>

                    <section class="aw-chart-grid" aria-label="Trend and distribution charts">
                        <article class="aw-chart-card aw-chart-wide">
                            <div class="aw-card-header">
                                <h3><i class="fas fa-chart-line"></i> 14-Day Intake Trend</h3>
                            </div>
                            <div class="aw-chart-body">
                                <canvas id="trendChart" height="120"></canvas>
                            </div>
                        </article>
                        <article class="aw-chart-card">
                            <div class="aw-card-header">
                                <h3><i class="fas fa-signal"></i> Severity Breakdown</h3>
                            </div>
                            <div class="aw-chart-body">
                                <canvas id="severityChart" height="180"></canvas>
                            </div>
                        </article>
                        <article class="aw-chart-card">
                            <div class="aw-card-header">
                                <h3><i class="fas fa-layer-group"></i> Status Breakdown</h3>
                            </div>
                            <div class="aw-chart-body">
                                <canvas id="statusChart" height="180"></canvas>
                            </div>
                        </article>
                    </section>

                    <section class="aw-table-card" aria-label="Top warning types">
                        <div class="aw-card-header">
                            <h3><i class="fas fa-ranking-star"></i> Top Warning Types</h3>
                            <div class="aw-refresh-meta">
                                <small id="analyticsLastUpdated">Last updated: --</small>
                                <button class="btn btn-secondary" id="refreshAnalyticsBtn"><i class="fas fa-rotate"></i> Refresh</button>
                            </div>
                        </div>
                        <div class="aw-table-wrap">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Type</th>
                                        <th>Source</th>
                                        <th>Count</th>
                                    </tr>
                                </thead>
                                <tbody id="topTypesTableBody">
                                    <tr><td colspan="3">Loading analytics...</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </section>

                    <section class="aw-table-card" aria-label="Automated warning dispatch history">
                        <div class="aw-card-header">
                            <h3><i class="fas fa-tower-broadcast"></i> Automated Dispatch Monitor</h3>
                            <div class="aw-refresh-meta">
                                <small id="dispatchLastUpdated">Last updated: --</small>
                                <button class="btn btn-secondary" id="refreshDispatchBtn"><i class="fas fa-rotate"></i> Refresh</button>
                                <a class="btn btn-secondary" href="mass-notification.php"><i class="fas fa-arrow-up-right-from-square"></i> Open Mass Notification</a>
                            </div>
                        </div>
                        <div class="aw-table-wrap">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Channels</th>
                                        <th>Status</th>
                                        <th>Queue Progress</th>
                                        <th>Sent At</th>
                                    </tr>
                                </thead>
                                <tbody id="dispatchTableBody">
                                    <tr><td colspan="5">Loading dispatch monitor...</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </section>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        let trendChart = null;
        let severityChart = null;
        let statusChart = null;

        function fmtInt(value) {
            const n = Number(value || 0);
            return Number.isFinite(n) ? n.toLocaleString() : '0';
        }

        function toTitleCase(text) {
            return String(text || '')
                .replace(/_/g, ' ')
                .replace(/\b\w/g, function(c) { return c.toUpperCase(); });
        }

        function setText(id, value) {
            const el = document.getElementById(id);
            if (el) el.textContent = value;
        }

        function destroyChart(instance) {
            if (instance) instance.destroy();
        }

        function renderTrendChart(rows) {
            const ctx = document.getElementById('trendChart');
            if (!ctx) return;

            const labels = rows.map(function(row) { return row.day; });
            const weather = rows.map(function(row) { return Number(row.weather || 0); });
            const earthquake = rows.map(function(row) { return Number(row.earthquake || 0); });

            destroyChart(trendChart);
            trendChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: 'Weather',
                            data: weather,
                            borderColor: '#2aa7ff',
                            backgroundColor: 'rgba(42, 167, 255, 0.14)',
                            fill: true,
                            tension: 0.35
                        },
                        {
                            label: 'Earthquake',
                            data: earthquake,
                            borderColor: '#f97316',
                            backgroundColor: 'rgba(249, 115, 22, 0.14)',
                            fill: true,
                            tension: 0.35
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { position: 'bottom' } },
                    scales: {
                        y: { beginAtZero: true, ticks: { precision: 0 } }
                    }
                }
            });
        }

        function renderSeverityChart(data) {
            const ctx = document.getElementById('severityChart');
            if (!ctx) return;
            destroyChart(severityChart);

            severityChart = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: ['Low', 'Medium', 'High', 'Critical'],
                    datasets: [{
                        data: [
                            Number(data.low || 0),
                            Number(data.medium || 0),
                            Number(data.high || 0),
                            Number(data.critical || 0)
                        ],
                        backgroundColor: ['#60a5fa', '#22c55e', '#f59e0b', '#ef4444']
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { position: 'bottom' } }
                }
            });
        }

        function renderStatusChart(data) {
            const ctx = document.getElementById('statusChart');
            if (!ctx) return;
            destroyChart(statusChart);

            statusChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: ['Pending', 'Published', 'Archived'],
                    datasets: [{
                        label: 'Warnings',
                        data: [
                            Number(data.pending || 0),
                            Number(data.published || 0),
                            Number(data.archived || 0)
                        ],
                        borderRadius: 8,
                        backgroundColor: ['#94a3b8', '#10b981', '#64748b']
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: { beginAtZero: true, ticks: { precision: 0 } }
                    }
                }
            });
        }

        function renderTopTypes(rows) {
            const tbody = document.getElementById('topTypesTableBody');
            if (!tbody) return;

            if (!rows || rows.length === 0) {
                tbody.innerHTML = '<tr><td colspan="3">No warning data yet.</td></tr>';
                return;
            }

            tbody.innerHTML = rows.map(function(row) {
                const sourceLabel = row.source === 'weather' ? 'Weather' : (row.source === 'earthquake' ? 'Earthquake' : 'Other');
                const badgeClass = row.source === 'weather' ? 'aw-badge weather' : (row.source === 'earthquake' ? 'aw-badge quake' : 'aw-badge');
                return `
                    <tr>
                        <td>${toTitleCase(row.type)}</td>
                        <td><span class="${badgeClass}">${sourceLabel}</span></td>
                        <td>${fmtInt(row.count)}</td>
                    </tr>
                `;
            }).join('');
        }

        function renderDispatchHistory(rows) {
            const tbody = document.getElementById('dispatchTableBody');
            if (!tbody) return;

            if (!rows || rows.length === 0) {
                tbody.innerHTML = '<tr><td colspan="5">No automated dispatch history yet.</td></tr>';
                return;
            }

            tbody.innerHTML = rows.map(function(row) {
                const id = Number(row.id || 0);
                const channels = String(row.channel || '').trim() || 'n/a';
                const status = String(row.status || 'pending').toLowerCase();
                const sentAt = row.sent_at || row.created_at || '--';
                const queueTotal = Number(row.queue_total || 0);
                const queueSent = Number(row.queue_sent || 0);
                const queuePending = Number(row.queue_pending || 0);
                const queueFailed = Number(row.queue_failed || 0);
                const progress = Number(row.progress_pct || 0);

                const statusCls = status === 'sent' || status === 'completed'
                    ? 'aw-badge weather'
                    : (status === 'failed' ? 'aw-badge quake' : 'aw-badge');

                const progressText = queueTotal > 0
                    ? `${queueSent}/${queueTotal} sent, ${queuePending} pending, ${queueFailed} failed`
                    : `${progress}%`;

                return `
                    <tr>
                        <td>#${id}</td>
                        <td>${channels}</td>
                        <td><span class="${statusCls}">${status.toUpperCase()}</span></td>
                        <td>${progressText}</td>
                        <td>${sentAt}</td>
                    </tr>
                `;
            }).join('');
        }

        function applyData(payload) {
            const overview = payload.overview || {};
            const bySource = payload.by_source || {};
            const weather = bySource.weather || {};
            const earthquake = bySource.earthquake || {};
            const meta = payload.meta || {};

            setText('kpiTotal', fmtInt(overview.total));
            setText('kpiPublished', fmtInt(overview.published));
            setText('kpiCritical', fmtInt(overview.critical));
            setText('kpiLast24', fmtInt(overview.last_24h));

            setText('weatherTotal', fmtInt(weather.total));
            setText('weatherPublished', fmtInt(weather.published));
            setText('weatherPending', fmtInt(weather.pending));
            setText('weatherCritical', fmtInt(weather.critical));

            setText('quakeTotal', fmtInt(earthquake.total));
            setText('quakePublished', fmtInt(earthquake.published));
            setText('quakePending', fmtInt(earthquake.pending));
            setText('quakeCritical', fmtInt(earthquake.critical));

            renderTrendChart(payload.daily_trend || []);
            renderSeverityChart(payload.severity_breakdown || {});
            renderStatusChart(payload.status_breakdown || {});
            renderTopTypes(payload.top_types || []);

            const healthNotice = document.getElementById('analyticsHealthNotice');
            const healthText = document.getElementById('analyticsHealthText');
            if (healthNotice && healthText) {
                if (meta.table_available === false) {
                    healthNotice.style.display = 'flex';
                    healthText.textContent = meta.message || 'Automated warning table is unavailable. Showing empty state.';
                } else {
                    healthNotice.style.display = 'none';
                }
            }
        }

        async function loadAnalytics() {
            try {
                const response = await fetch('../api/automated-warnings.php?action=analytics');
                const rawText = await response.text();
                let data = null;
                try {
                    data = JSON.parse(rawText);
                } catch (parseErr) {
                    throw new Error('Analytics endpoint returned invalid JSON');
                }
                if (!response.ok || !data.success) {
                    throw new Error(data.message || 'Failed to load analytics');
                }
                applyData(data);
                const ts = new Date();
                setText('analyticsLastUpdated', 'Last updated: ' + ts.toLocaleString());
            } catch (error) {
                console.error('Automated warning analytics error:', error);
                const tbody = document.getElementById('topTypesTableBody');
                if (tbody) {
                    tbody.innerHTML = '<tr><td colspan="3">Failed to load analytics. Please refresh.</td></tr>';
                }
                setText('analyticsLastUpdated', 'Last updated: failed');
            }
        }

        async function loadDispatchHistory() {
            try {
                const response = await fetch('../api/automated-warnings.php?action=dispatch_history&limit=20');
                const rawText = await response.text();
                let data = null;
                try {
                    data = JSON.parse(rawText);
                } catch (parseErr) {
                    throw new Error('Dispatch history endpoint returned invalid JSON');
                }
                if (!response.ok || !data.success) {
                    throw new Error(data.message || 'Failed to load dispatch history');
                }
                renderDispatchHistory(data.dispatches || []);
                const ts = new Date();
                setText('dispatchLastUpdated', 'Last updated: ' + ts.toLocaleString());
            } catch (error) {
                console.error('Dispatch history error:', error);
                const tbody = document.getElementById('dispatchTableBody');
                if (tbody) tbody.innerHTML = '<tr><td colspan="5">Failed to load dispatch history.</td></tr>';
                setText('dispatchLastUpdated', 'Last updated: failed');
            }
        }

        function sendMockCriticalAlert(type) {
            const pretty = type === 'earthquake' ? 'Earthquake' : 'Weather';
            if (!confirm(`Send MOCK CRITICAL ${pretty.toUpperCase()} alert and queue broadcast to all active citizens?`)) {
                return;
            }
            fetch('../api/automated-warnings.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'mock_alert', type: type })
            })
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    throw new Error(data.message || 'Failed to send mock alert');
                }
                alert(data.message || 'Mock alert sent.');
                loadAnalytics();
                loadDispatchHistory();
            })
            .catch(error => {
                console.error('Mock alert error:', error);
                alert('Mock alert failed: ' + error.message);
            });
        }

        document.addEventListener('DOMContentLoaded', function() {
            const refreshBtn = document.getElementById('refreshAnalyticsBtn');
            if (refreshBtn) {
                refreshBtn.addEventListener('click', loadAnalytics);
            }
            const dispatchRefreshBtn = document.getElementById('refreshDispatchBtn');
            if (dispatchRefreshBtn) {
                dispatchRefreshBtn.addEventListener('click', loadDispatchHistory);
            }
            loadAnalytics();
            loadDispatchHistory();
            // Keep dashboard dynamic without manual refresh.
            setInterval(loadAnalytics, 30000);
            setInterval(loadDispatchHistory, 30000);
        });
    </script>
</body>
</html>
