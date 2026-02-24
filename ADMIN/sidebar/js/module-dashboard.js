(function () {
    'use strict';

    const AUTO_REFRESH_MS = 0; // Static snapshot mode; use manual refresh.
    const API_URL = document.body.getAttribute('data-dashboard-api') || '../api/dashboard.php';

    const state = {
        isLoading: false,
        charts: Object.create(null),
    };

    const moduleThemeByKey = {
        mass_notification: 'theme-sunset',
        two_way_communication: 'theme-rose',
        automated_warnings: 'theme-emerald',
        weather_monitoring: 'theme-ocean',
        earthquake_monitoring: 'theme-amber',
        citizen_subscriptions: 'theme-violet',
        multilingual_support: 'theme-teal',
        admin_approvals: 'theme-coral',
        audit_trail: 'theme-indigo',
    };

    function byId(id) {
        return document.getElementById(id);
    }

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function toNumber(value) {
        const n = Number(value);
        return Number.isFinite(n) ? n : 0;
    }

    function formatInt(value) {
        return toNumber(value).toLocaleString();
    }

    function setText(id, value, fallback) {
        const el = byId(id);
        if (!el) return;
        const output = value == null || value === '' ? (fallback ?? '') : value;
        el.textContent = String(output);
    }

    function setRefreshButtonState(isBusy) {
        const btn = byId('dashboardRefreshBtn');
        if (!btn) return;

        btn.disabled = !!isBusy;
        const icon = btn.querySelector('i');
        if (icon) {
            icon.classList.toggle('fa-spin', !!isBusy);
        }
    }

    function formatGeneratedAt(value) {
        if (!value) return 'Unavailable';
        const dt = new Date(value);
        if (Number.isNaN(dt.getTime())) {
            return String(value);
        }
        return dt.toLocaleString();
    }

    function updateGeneratedAt(value) {
        setText('dashboardGeneratedAt', formatGeneratedAt(value), 'Unavailable');
    }

    function sanitizeModuleStatus(status) {
        const key = String(status || '').toLowerCase();
        if (key === 'ok' || key === 'warning' || key === 'critical' || key === 'info') {
            return key;
        }
        return 'info';
    }

    function sanitizeRoute(route) {
        const raw = String(route || '').trim();
        if (!raw) return '#';
        if (/^javascript:/i.test(raw)) return '#';
        return raw;
    }

    function renderAnalytics(stats) {
        const payload = stats || {};
        const totalSubscribers = toNumber(payload.total_subscribers);
        const subscriberChange = toNumber(payload.subscriber_change);
        const notificationsToday = toNumber(payload.notifications_today);
        const successRate = Math.max(0, Math.min(100, toNumber(payload.success_rate)));
        const pendingMessages = toNumber(payload.pending_messages);

        setText('analyticsTotalSubscribers', formatInt(totalSubscribers), '0');
        setText('analyticsNotificationsToday', formatInt(notificationsToday), '0');
        setText('analyticsPendingMessages', formatInt(pendingMessages), '0');
        setText('analyticsSuccessRate', successRate + '%', '0%');

        setText(
            'analyticsSubscribersSub',
            (subscriberChange >= 0 ? '+' : '') + formatInt(subscriberChange) + ' this week',
            'Registered citizens'
        );

        if (notificationsToday === 0) {
            setText('analyticsNotificationsSub', 'No alerts sent yet', 'Alerts sent today');
        } else if (successRate >= 95) {
            setText('analyticsNotificationsSub', 'All delivered', 'Alerts sent today');
        } else if (successRate >= 80) {
            setText('analyticsNotificationsSub', 'Minor delivery issues', 'Alerts sent today');
        } else {
            setText('analyticsNotificationsSub', 'Delivery issues detected', 'Alerts sent today');
        }

        if (pendingMessages === 0) {
            setText('analyticsPendingSub', 'Queue clear', 'Need response');
        } else if (pendingMessages <= 5) {
            setText('analyticsPendingSub', 'Monitor response SLA', 'Need response');
        } else {
            setText('analyticsPendingSub', 'Backlog requires action', 'Need response');
        }

        if (successRate >= 95) {
            setText('analyticsSuccessSub', 'Excellent delivery health', 'Delivery health');
        } else if (successRate >= 80) {
            setText('analyticsSuccessSub', 'Stable but watch retries', 'Delivery health');
        } else {
            setText('analyticsSuccessSub', 'Needs reliability fixes', 'Delivery health');
        }
    }

    function renderModuleStatus(modules) {
        const container = byId('moduleStatusGrid');
        if (!container) return;

        if (!Array.isArray(modules) || modules.length === 0) {
            container.innerHTML = '<div class="module-status-empty">No module health data available.</div>';
            return;
        }

        container.innerHTML = modules.map(function (module) {
            const status = sanitizeModuleStatus(module && module.status);
            const metric = module && module.metric != null ? module.metric : 0;
            const metricLabel = module && module.metric_label ? module.metric_label : 'Metric';
            const secondary = module && module.secondary ? module.secondary : '';
            const route = sanitizeRoute(module && module.route ? module.route : '#');
            const icon = module && module.icon ? module.icon : 'fa-cube';
            const name = module && module.name ? module.name : 'Module';
            const key = module && module.key ? String(module.key) : '';
            const themeClass = moduleThemeByKey[key] || 'theme-fallback';

            return (
                '<article class="module-status-card status-' + escapeHtml(status) + ' ' + escapeHtml(themeClass) + '">' +
                    '<div class="module-status-head">' +
                        '<div class="module-status-name">' + escapeHtml(name) + '</div>' +
                        '<div class="module-status-icon"><i class="fas ' + escapeHtml(icon) + '"></i></div>' +
                    '</div>' +
                    '<div class="module-status-metric">' + escapeHtml(metric) + '</div>' +
                    '<div class="module-status-label">' + escapeHtml(metricLabel) + '</div>' +
                    '<div class="module-status-secondary">' + escapeHtml(secondary) + '</div>' +
                    '<div class="module-status-footer">' +
                        '<span class="module-status-chip">' + escapeHtml(status.toUpperCase()) + '</span>' +
                        '<a href="' + escapeHtml(route) + '" class="module-status-link">Open module</a>' +
                    '</div>' +
                '</article>'
            );
        }).join('');
    }

    function iconForActivityType(type) {
        const key = String(type || '').toLowerCase();
        if (key === 'notification') return 'fa-bell';
        if (key === 'message') return 'fa-comments';
        if (key === 'subscription') return 'fa-user-plus';
        if (key === 'alert') return 'fa-triangle-exclamation';
        if (key === 'integration') return 'fa-plug';
        return 'fa-circle';
    }

    function renderRecentActivity(activities) {
        const container = byId('recentActivity');
        if (!container) return;

        if (!Array.isArray(activities) || activities.length === 0) {
            container.innerHTML = '<div class="activity-item"><p class="activity-empty">No recent activity</p></div>';
            return;
        }

        container.innerHTML = activities.map(function (activity) {
            const iconClass = iconForActivityType(activity && activity.type);
            const title = activity && activity.title ? activity.title : 'Activity item';
            const time = activity && activity.time ? activity.time : 'Unknown time';

            return (
                '<div class="activity-item">' +
                    '<div class="activity-icon"><i class="fas ' + escapeHtml(iconClass) + '"></i></div>' +
                    '<div class="activity-content">' +
                        '<div class="activity-title">' + escapeHtml(title) + '</div>' +
                        '<div class="activity-time">' + escapeHtml(time) + '</div>' +
                    '</div>' +
                '</div>'
            );
        }).join('');
    }

    function renderChart(canvasId, type, data, options) {
        if (typeof Chart !== 'function') {
            return;
        }
        const canvas = byId(canvasId);
        if (!canvas) {
            return;
        }

        if (state.charts[canvasId]) {
            state.charts[canvasId].destroy();
        }

        state.charts[canvasId] = new Chart(canvas.getContext('2d'), {
            type: type,
            data: data,
            options: options,
        });
    }

    function chartDefaults() {
        return {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    labels: {
                        boxWidth: 12,
                        usePointStyle: true,
                    },
                },
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { precision: 0 },
                },
            },
        };
    }

    function renderNotificationsChart(payload) {
        const data = payload || {};
        renderChart('notificationsChart', 'line', {
            labels: Array.isArray(data.labels) ? data.labels : ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
            datasets: [{
                label: 'Notifications',
                data: Array.isArray(data.values) ? data.values : [0, 0, 0, 0, 0, 0, 0],
                borderColor: '#2563eb',
                backgroundColor: 'rgba(37, 99, 235, 0.12)',
                fill: true,
                tension: 0.35,
                pointRadius: 3,
            }],
        }, Object.assign(chartDefaults(), {
            plugins: { legend: { display: false } },
        }));
    }

    function renderChannelsChart(payload) {
        const data = payload || {};
        renderChart('channelsChart', 'doughnut', {
            labels: Array.isArray(data.labels) ? data.labels : ['SMS', 'Email', 'PA System'],
            datasets: [{
                data: Array.isArray(data.values) ? data.values : [0, 0, 0],
                backgroundColor: ['#2563eb', '#16a34a', '#ea580c'],
                borderWidth: 1,
            }],
        }, {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { boxWidth: 12, usePointStyle: true },
                },
            },
        });
    }

    function renderEndToEndChart(payload) {
        const data = payload || {};
        renderChart('endToEndChart', 'bar', {
            labels: Array.isArray(data.labels) ? data.labels : [],
            datasets: [{
                label: 'Flow volume',
                data: Array.isArray(data.values) ? data.values : [],
                backgroundColor: ['#0ea5e9', '#f59e0b', '#8b5cf6', '#10b981', '#22c55e'],
                borderRadius: 8,
            }],
        }, Object.assign(chartDefaults(), {
            indexAxis: 'y',
            plugins: { legend: { display: false } },
        }));
    }

    function renderIncidentTrendChart(payload) {
        const data = payload || {};
        renderChart('incidentTrendChart', 'bar', {
            labels: Array.isArray(data.labels) ? data.labels : ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
            datasets: [
                {
                    label: 'Weather',
                    data: Array.isArray(data.weather) ? data.weather : [0, 0, 0, 0, 0, 0, 0],
                    backgroundColor: 'rgba(14, 165, 233, 0.85)',
                    borderRadius: 6,
                },
                {
                    label: 'Earthquake',
                    data: Array.isArray(data.earthquake) ? data.earthquake : [0, 0, 0, 0, 0, 0, 0],
                    backgroundColor: 'rgba(234, 88, 12, 0.8)',
                    borderRadius: 6,
                },
            ],
        }, Object.assign(chartDefaults(), {
            scales: {
                x: { stacked: false },
                y: { beginAtZero: true, ticks: { precision: 0 } },
            },
        }));
    }

    function renderCharts(charts) {
        const payload = charts || {};
        renderNotificationsChart(payload.notifications);
        renderChannelsChart(payload.channels);
        renderEndToEndChart(payload.end_to_end);
        renderIncidentTrendChart(payload.incident_trend);
    }

    async function loadDashboardData(force) {
        if (state.isLoading && !force) {
            return;
        }

        state.isLoading = true;
        setRefreshButtonState(true);

        try {
            const response = await fetch(API_URL, {
                method: 'GET',
                headers: { 'Accept': 'application/json' },
                cache: 'no-store',
            });

            if (!response.ok) {
                throw new Error('HTTP ' + response.status);
            }

            const data = await response.json();
            if (!data || data.success !== true) {
                throw new Error((data && data.message) || 'Dashboard payload error');
            }

            renderAnalytics(data.stats);
            renderCharts(data.charts);
            renderRecentActivity(data.activity);
            renderModuleStatus(data.modules);
            updateGeneratedAt(data.generated_at);
        } catch (error) {
            console.error('Dashboard load failed:', error);
            updateGeneratedAt('Load failed');
        } finally {
            state.isLoading = false;
            setRefreshButtonState(false);
        }
    }

    function bindEvents() {
        const refreshBtn = byId('dashboardRefreshBtn');
        if (refreshBtn && !refreshBtn.hasAttribute('data-dashboard-refresh-bound')) {
            refreshBtn.setAttribute('data-dashboard-refresh-bound', 'true');
            refreshBtn.addEventListener('click', function () {
                loadDashboardData(true);
            });
        }
    }

    function init() {
        bindEvents();
        loadDashboardData(true);

        if (AUTO_REFRESH_MS > 0) {
            setInterval(function () {
                loadDashboardData(false);
            }, AUTO_REFRESH_MS);
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
