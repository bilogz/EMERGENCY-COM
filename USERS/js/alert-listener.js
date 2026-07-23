/**
 * Citizen emergency-alert listener.
 * Provides an NDRRMC-style in-page warning plus optional browser notification.
 */
(function () {
    'use strict';

    // Citizen pages only. This guard prevents an accidental script include from
    // displaying public emergency popups in the admin console.
    const currentPath = String(window.location.pathname || '').replace(/\\/g, '/').toLowerCase();
    if (!currentPath.includes('/users/') || currentPath.includes('/admin/')) return;

    // Use a citizen-only key. The old shared key could be written while an
    // administrator previewed an alert and then suppress it on citizen pages.
    const STORAGE_KEY = 'citizen_last_processed_alert_id_v2';
    window.__citizenAlertListenerActive = true;
    const ALERT_FRESHNESS_MS = 6 * 60 * 60 * 1000;
    const pollInterval = 6000;
    let lastAlertId = Number(localStorage.getItem(STORAGE_KEY) || 0);
    let lastErrorLogAt = 0;
    let pollInFlight = false;

    function detectAppBase() {
        if (typeof window.APP_BASE_PATH === 'string') return window.APP_BASE_PATH;
        const path = (window.location.pathname || '').replace(/\\/g, '/');
        const adminIndex = path.toLowerCase().indexOf('/admin/');
        const usersIndex = path.toLowerCase().indexOf('/users/');
        const cutAt = adminIndex >= 0 ? adminIndex : usersIndex;
        return cutAt >= 0 ? path.substring(0, cutAt) : '';
    }

    function usersUrls(endpoint) {
        const base = detectAppBase().replace(/\/+$/, '');
        const clean = String(endpoint || '').replace(/^\/+/, '');
        return Array.from(new Set([
            base ? `${base}/USERS/api/${clean}` : '',
            base ? `${base}/users/api/${clean}` : '',
            `/USERS/api/${clean}`,
            `/users/api/${clean}`
        ].filter(Boolean)));
    }

    const checkUrls = usersUrls('check-new-alerts.php');
    const acknowledgeUrls = usersUrls('acknowledge-alert.php');

    async function fetchJson(urls, options) {
        let lastError;
        for (const url of urls) {
            try {
                const response = await fetch(url, options);
                const text = await response.text();
                if (!response.ok) throw new Error(`HTTP ${response.status}`);
                return JSON.parse(text);
            } catch (error) {
                lastError = error;
            }
        }
        throw lastError || new Error('Alert service unavailable');
    }

    function severityName(value) {
        const normalized = String(value || 'Medium').trim().toLowerCase();
        return normalized === 'critical' ? 'Critical'
            : normalized === 'high' ? 'High'
            : normalized === 'low' ? 'Low'
            : 'Medium';
    }

    function isFreshAlert(alert) {
        const rawTimestamp = alert?.issued_at || alert?.created_at || alert?.timestamp || alert?.updated_at;
        if (!rawTimestamp) return false;
        const timestamp = new Date(String(rawTimestamp).replace(' ', 'T')).getTime();
        if (!Number.isFinite(timestamp)) return false;
        const age = Date.now() - timestamp;
        return age >= -5 * 60 * 1000 && age <= ALERT_FRESHNESS_MS;
    }

    function severityMeta(value) {
        const severity = severityName(value);
        const map = {
            Critical: { color: '#b91c1c', label: 'EMERGENCY WARNING', instruction: 'ACT NOW', icon: 'fa-triangle-exclamation' },
            High: { color: '#c2410c', label: 'URGENT WARNING', instruction: 'TAKE ACTION', icon: 'fa-circle-exclamation' },
            Medium: { color: '#0369a1', label: 'PUBLIC ADVISORY', instruction: 'BE PREPARED', icon: 'fa-circle-info' },
            Low: { color: '#166534', label: 'SAFETY UPDATE', instruction: 'STAY INFORMED', icon: 'fa-bell' }
        };
        return { severity, ...map[severity] };
    }

    function ensureStyles() {
        if (document.getElementById('ecs-alert-styles')) return;
        const style = document.createElement('style');
        style.id = 'ecs-alert-styles';
        style.textContent = `
            .ecs-alert-overlay{position:fixed;inset:0;z-index:2147483000;display:grid;place-items:center;padding:18px;background:rgba(2,6,23,.76);backdrop-filter:blur(5px)}
            .ecs-alert-dialog{width:min(620px,100%);max-height:min(780px,calc(100vh - 36px));overflow:auto;border-radius:18px;background:#fff;color:#111827;box-shadow:0 30px 80px rgba(0,0,0,.5);border:4px solid var(--alert-color);font-family:Inter,system-ui,-apple-system,"Segoe UI",sans-serif;animation:ecsAlertIn .24s ease-out}
            .ecs-alert-dialog.is-critical{animation:ecsAlertIn .24s ease-out,ecsCriticalPulse 2s ease-in-out infinite}
            .ecs-alert-head{padding:18px 22px;background:var(--alert-color);color:#fff;display:flex;align-items:center;gap:14px}
            .ecs-alert-symbol{width:52px;height:52px;flex:0 0 52px;border-radius:50%;display:grid;place-items:center;background:rgba(255,255,255,.18);font-size:24px}
            .ecs-alert-kicker{font-size:12px;font-weight:900;letter-spacing:.11em}.ecs-alert-head h2{margin:3px 0 0;font-size:22px;line-height:1.2;color:#fff}
            .ecs-alert-body{padding:22px}.ecs-alert-category{display:inline-flex;padding:5px 10px;border-radius:999px;background:#f1f5f9;color:#334155;font-size:12px;font-weight:800;text-transform:uppercase}
            .ecs-alert-title{margin:14px 0 8px;font-size:24px;line-height:1.25;color:#0f172a}.ecs-alert-message{margin:0;color:#334155;font-size:17px;line-height:1.7;white-space:pre-wrap;overflow-wrap:anywhere}
            .ecs-alert-time{margin-top:16px;color:#64748b;font-size:13px}.ecs-alert-actions{display:grid;grid-template-columns:1fr 1.4fr;gap:10px;padding:0 22px 22px}
            .ecs-alert-btn{min-height:52px;border-radius:10px;border:2px solid #cbd5e1;background:#fff;color:#0f172a;font:800 15px/1.2 Inter,system-ui,sans-serif;cursor:pointer}.ecs-alert-btn.primary{border-color:var(--alert-color);background:var(--alert-color);color:#fff}
            .ecs-alert-btn:focus-visible{outline:4px solid #facc15;outline-offset:3px}.ecs-alert-browser{grid-column:1/-1;min-height:44px;border:0;background:#eff6ff;color:#1d4ed8;font-weight:800;cursor:pointer;border-radius:9px}
            @keyframes ecsAlertIn{from{opacity:0;transform:translateY(-24px) scale(.98)}to{opacity:1;transform:none}}@keyframes ecsCriticalPulse{50%{box-shadow:0 0 0 8px rgba(185,28,28,.18),0 30px 80px rgba(0,0,0,.5)}}
            @media(max-width:560px){.ecs-alert-overlay{padding:10px}.ecs-alert-head{padding:16px}.ecs-alert-body{padding:18px}.ecs-alert-title{font-size:21px}.ecs-alert-message{font-size:16px}.ecs-alert-actions{grid-template-columns:1fr;padding:0 18px 18px}.ecs-alert-browser{grid-column:auto}}
            @media(prefers-reduced-motion:reduce){.ecs-alert-dialog,.ecs-alert-dialog.is-critical{animation:none}}
        `;
        document.head.appendChild(style);
    }

    function formatMessage(message) {
        return String(message || '')
            .replace(/\r\n?/g, '\n')
            .replace(/[ \t]*•[ \t]*/g, '\n\n• ')
            .replace(/\n{3,}/g, '\n\n')
            .trim();
    }

    function notifyDevice(alert, meta) {
        if (meta.severity === 'Critical' || meta.severity === 'High') {
            try { navigator.vibrate?.(meta.severity === 'Critical' ? [500, 180, 500, 180, 900] : [350, 150, 350]); } catch (_) {}
        }
        if (!('Notification' in window) || Notification.permission !== 'granted') return;
        try {
            const notification = new Notification(`${meta.label}: ${alert.title}`, {
                body: formatMessage(alert.message || alert.content).slice(0, 240),
                icon: `${detectAppBase()}/ADMIN/header/images/logo.svg`,
                badge: `${detectAppBase()}/ADMIN/header/images/favicon.ico`,
                tag: `emergency-alert-${alert.id}`,
                renotify: true,
                requireInteraction: meta.severity === 'Critical',
                data: { alertId: alert.id, url: `${detectAppBase()}/USERS/alerts.php?alert_id=${encodeURIComponent(alert.id)}` }
            });
            notification.onclick = event => {
                event.preventDefault();
                window.focus();
                window.location.href = notification.data.url;
            };
        } catch (_) {}
    }

    function element(tag, className, text) {
        const node = document.createElement(tag);
        if (className) node.className = className;
        if (text !== undefined) node.textContent = text;
        return node;
    }

    function showAlert(alert) {
        ensureStyles();
        document.getElementById('global-alert-modal')?.remove();

        const meta = severityMeta(alert.severity);
        const overlay = element('div', 'ecs-alert-overlay');
        overlay.id = 'global-alert-modal';
        overlay.setAttribute('role', 'presentation');

        const dialog = element('section', `ecs-alert-dialog${meta.severity === 'Critical' ? ' is-critical' : ''}`);
        dialog.style.setProperty('--alert-color', meta.color);
        dialog.setAttribute('role', 'alertdialog');
        dialog.setAttribute('aria-modal', 'true');
        dialog.setAttribute('aria-labelledby', 'ecs-alert-title');
        dialog.setAttribute('aria-describedby', 'ecs-alert-message');

        const head = element('header', 'ecs-alert-head');
        const symbol = element('div', 'ecs-alert-symbol');
        const icon = element('i', `fas ${String(alert.category_icon || meta.icon).replace(/[^a-zA-Z0-9_-]/g, '')}`);
        symbol.appendChild(icon);
        const headCopy = element('div');
        headCopy.appendChild(element('div', 'ecs-alert-kicker', `${meta.label} • ${meta.instruction}`));
        headCopy.appendChild(element('h2', '', `${meta.severity.toUpperCase()} ALERT`));
        head.append(symbol, headCopy);

        const body = element('div', 'ecs-alert-body');
        body.appendChild(element('span', 'ecs-alert-category', alert.category_name || alert.category || 'Emergency Alert'));
        const title = element('h1', 'ecs-alert-title', alert.title || 'Emergency Alert');
        title.id = 'ecs-alert-title';
        const message = element('div', 'ecs-alert-message', formatMessage(alert.message || alert.content));
        message.id = 'ecs-alert-message';
        body.append(title, message);
        const issuedAt = alert.issued_at || alert.created_at;
        if (issuedAt) body.appendChild(element('div', 'ecs-alert-time', `Issued: ${new Date(String(issuedAt).replace(' ', 'T')).toLocaleString()}`));

        const actions = element('div', 'ecs-alert-actions');
        const details = element('button', 'ecs-alert-btn', 'View Active Alerts');
        details.type = 'button';
        details.onclick = () => { window.location.href = `${detectAppBase()}/USERS/alerts.php?alert_id=${encodeURIComponent(alert.id)}`; };
        const acknowledge = element('button', 'ecs-alert-btn primary', 'I Understand This Alert');
        acknowledge.type = 'button';
        acknowledge.id = 'ack-alert-btn';
        acknowledge.onclick = async () => {
            acknowledge.disabled = true;
            acknowledge.textContent = 'Acknowledging…';
            await acknowledgeAlert(alert.id);
            overlay.remove();
        };
        actions.append(details, acknowledge);

        if ('Notification' in window && Notification.permission === 'default') {
            const enable = element('button', 'ecs-alert-browser', 'Enable browser alerts, even when this tab is in the background');
            enable.type = 'button';
            enable.onclick = async () => {
                const permission = await Notification.requestPermission();
                enable.textContent = permission === 'granted' ? 'Browser alerts enabled' : 'Browser alerts were not enabled';
                if (permission === 'granted') notifyDevice(alert, meta);
            };
            actions.appendChild(enable);
        }

        dialog.append(head, body, actions);
        overlay.appendChild(dialog);
        document.body.appendChild(overlay);
        acknowledge.focus({ preventScroll: true });
        notifyDevice(alert, meta);

        if (meta.severity !== 'Critical') {
            overlay.addEventListener('click', event => { if (event.target === overlay) overlay.remove(); });
            document.addEventListener('keydown', function escape(event) {
                if (event.key === 'Escape' && overlay.isConnected) {
                    overlay.remove();
                    document.removeEventListener('keydown', escape);
                }
            });
        }
    }

    async function acknowledgeAlert(id) {
        for (const url of acknowledgeUrls) {
            try {
                const data = new FormData();
                data.append('alert_id', String(id));
                const response = await fetch(url, { method: 'POST', body: data, headers: { Accept: 'application/json' } });
                if (response.ok) return true;
            } catch (_) {}
        }
        return false;
    }

    function updateBadges(count, severity) {
        const meta = severityMeta(severity);
        document.querySelectorAll('.alert-badge, #alert-nav-badge').forEach(badge => {
            badge.textContent = count > 0 ? String(count) : '';
            badge.style.display = count > 0 ? 'inline-block' : 'none';
            badge.style.backgroundColor = meta.color;
        });
    }

    async function checkAlerts() {
        if (pollInFlight) return;
        pollInFlight = true;
        try {
            const data = await fetchJson(checkUrls, { method: 'GET', cache: 'no-store', headers: { Accept: 'application/json' } });
            if (!data?.success) return;
            updateBadges(Number(data.unread_count || 0), data.alert?.severity);
            const alertId = Number(data.alert?.id || 0);
            if (data.alert && isFreshAlert(data.alert) && alertId > 0 && alertId !== lastAlertId) {
                lastAlertId = alertId;
                localStorage.setItem(STORAGE_KEY, String(alertId));
                showAlert(data.alert);
            }
        } catch (error) {
            const now = Date.now();
            if (now - lastErrorLogAt > 30000) {
                console.warn('Alert polling error:', error);
                lastErrorLogAt = now;
            }
        } finally {
            pollInFlight = false;
        }
    }

    window.addEventListener('storage', event => {
        if (event.key === STORAGE_KEY) lastAlertId = Number(event.newValue || 0);
    });
    document.addEventListener('visibilitychange', () => { if (!document.hidden) checkAlerts(); });
    window.addEventListener('online', checkAlerts);
    window.setInterval(checkAlerts, pollInterval);
    checkAlerts();
})();
