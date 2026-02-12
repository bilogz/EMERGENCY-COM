/**
 * Global Alert Listener
 * AJAX Polling for real-time emergency alerts
 */

(function() {
    let lastAlertId = localStorage.getItem('last_processed_alert_id') || 0;
    const pollInterval = 12000; // 12 seconds
    let lastErrorLogAt = 0;

    function detectAppBase() {
        if (typeof window.APP_BASE_PATH === 'string') {
            return window.APP_BASE_PATH;
        }

        const path = (window.location.pathname || '').replace(/\\/g, '/');
        const adminIndex = path.toLowerCase().indexOf('/admin/');
        const usersIndex = path.toLowerCase().indexOf('/users/');
        const cutAt = adminIndex >= 0 ? adminIndex : usersIndex;
        return cutAt >= 0 ? path.substring(0, cutAt) : '';
    }

    function buildUsersApiCandidates(endpoint) {
        const cleanEndpoint = String(endpoint || '').replace(/^\/+/, '');
        const appBase = detectAppBase().replace(/\/+$/, '');
        const upper = `/USERS/api/${cleanEndpoint}`;
        const lower = `/users/api/${cleanEndpoint}`;
        const candidates = [];

        if (appBase) {
            candidates.push(`${appBase}${upper}`);
            candidates.push(`${appBase}${lower}`);
        }
        candidates.push(upper);
        candidates.push(lower);

        return Array.from(new Set(candidates));
    }

    const checkAlertsUrls = buildUsersApiCandidates('check-new-alerts.php');
    const acknowledgeUrls = buildUsersApiCandidates('acknowledge-alert.php');

    async function fetchJsonFromCandidates(urls, options) {
        let lastErr = null;
        for (const url of urls) {
            try {
                const response = await fetch(url, options);
                const body = await response.text();
                if (!response.ok) {
                    lastErr = new Error(`HTTP ${response.status} @ ${url}: ${body.slice(0, 120)}`);
                    continue;
                }

                try {
                    return JSON.parse(body);
                } catch (e) {
                    lastErr = new Error(`Invalid JSON @ ${url}: ${body.slice(0, 120)}`);
                }
            } catch (err) {
                lastErr = err;
            }
        }
        throw (lastErr || new Error('All alert endpoints failed'));
    }

    async function checkAlerts() {
        try {
            const data = await fetchJsonFromCandidates(checkAlertsUrls, {
                method: 'GET',
                cache: 'no-store',
                headers: {
                    'Accept': 'application/json'
                }
            });

            if (!data || !data.success) return;

            // Update Badges
            updateBadges(data.unread_count, data.alert ? data.alert.severity : null);

            if (data.alert && data.alert.id != lastAlertId) {
                handleNewAlert(data.alert);
            }
        } catch (err) {
            const now = Date.now();
            if (now - lastErrorLogAt > 30000) {
                console.warn('Alert Polling Error:', err);
                lastErrorLogAt = now;
            }
        }
    }

    function updateBadges(count, severity) {
        const badges = document.querySelectorAll('.alert-badge, #alert-nav-badge');
        badges.forEach(badge => {
            badge.textContent = count > 0 ? count : '';
            badge.style.display = count > 0 ? 'inline-block' : 'none';
            
            // Severity-based color
            if (severity === 'Critical') badge.style.backgroundColor = '#f44336';
            else if (severity === 'High') badge.style.backgroundColor = '#ff9800';
            else if (severity === 'Medium') badge.style.backgroundColor = '#2196f3';
            else badge.style.backgroundColor = '#4caf50';
        });
    }

    function handleNewAlert(alert) {
        // Only show modal for Moderate -> Critical
        if (alert.severity === 'Low') {
            console.log('Low priority alert received:', alert.title);
            return;
        }

        lastAlertId = alert.id;
        localStorage.setItem('last_processed_alert_id', alert.id);

        createAlertModal(alert);
    }

    function createAlertModal(alert) {
        // Remove existing modal if any
        const existing = document.getElementById('global-alert-modal');
        if (existing) existing.remove();

        const modalOverlay = document.createElement('div');
        modalOverlay.id = 'global-alert-modal';
        modalOverlay.style = `
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.7); display: flex; align-items: center;
            justify-content: center; z-index: 9999; backdrop-filter: blur(5px);
        `;

        const isCritical = alert.severity === 'Critical';
        const isHigh = alert.severity === 'High';

        const modalContent = document.createElement('div');
        modalContent.style = `
            background: white; width: 90%; max-width: 500px;
            border-radius: 12px; overflow: hidden; box-shadow: 0 20px 40px rgba(0,0,0,0.4);
            animation: ${isCritical ? 'pulse-border 2s infinite' : 'slide-down 0.3s ease-out'};
            background: white; border: 3px solid ${getSeverityColor(alert.severity)};
        `;

        modalContent.innerHTML = `
            <div style="background: ${getSeverityColor(alert.severity)}; color: white; padding: 1.5rem; text-align: center;">
                <i class="fas ${alert.category_icon || 'fa-exclamation-triangle'}" style="font-size: 3rem; margin-bottom: 1rem;"></i>
                <h2 style="margin: 0; font-size: 1.5rem;">${alert.severity.toUpperCase()} ALERT</h2>
                <div style="text-transform: uppercase; font-size: 0.8rem; opacity: 0.9; margin-top: 0.25rem;">${alert.category_name}</div>
            </div>
            <div style="padding: 2rem; text-align: center;">
                <h3 style="margin-top: 0; color: #333;">${alert.title}</h3>
                <p style="color: #666; line-height: 1.6;">${alert.message}</p>
                <div style="margin-top: 2rem;">
                    <button id="ack-alert-btn" style="
                        background: ${getSeverityColor(alert.severity)}; color: white;
                        border: none; padding: 0.75rem 2rem; border-radius: 25px;
                        font-weight: 600; cursor: pointer; transition: transform 0.2s;
                        width: 100%; font-size: 1.1rem;
                    ">Acknowledge Alert</button>
                </div>
            </div>
        `;

        modalOverlay.appendChild(modalContent);
        document.body.appendChild(modalOverlay);

        // Animation Styles
        if (!document.getElementById('alert-animations')) {
            const style = document.createElement('style');
            style.id = 'alert-animations';
            style.innerHTML = `
                @keyframes pulse-border {
                    0% { transform: scale(1); box-shadow: 0 0 0 0 rgba(244, 67, 54, 0.7); }
                    70% { transform: scale(1.02); box-shadow: 0 0 0 20px rgba(244, 67, 54, 0); }
                    100% { transform: scale(1); box-shadow: 0 0 0 0 rgba(244, 67, 54, 0); }
                }
                @keyframes slide-down {
                    from { transform: translateY(-50px); opacity: 0; }
                    to { transform: translateY(0); opacity: 1; }
                }
            `;
            document.head.appendChild(style);
        }

        document.getElementById('ack-alert-btn').onclick = function() {
            acknowledgeAlert(alert.id);
            modalOverlay.remove();
        };
    }

    function acknowledgeAlert(id) {
        (async () => {
            let lastErr = null;
            for (const url of acknowledgeUrls) {
                try {
                    const formData = new FormData();
                    formData.append('alert_id', id);
                    const response = await fetch(url, {
                        method: 'POST',
                        body: formData
                    });
                    if (response.ok) return;
                    lastErr = new Error(`HTTP ${response.status} @ ${url}`);
                } catch (err) {
                    lastErr = err;
                }
            }

            const now = Date.now();
            if (now - lastErrorLogAt > 30000) {
                console.warn('Alert acknowledge error:', lastErr);
                lastErrorLogAt = now;
            }
        })();
    }

    function getSeverityColor(sev) {
        switch(sev) {
            case 'Critical': return '#f44336';
            case 'High': return '#ff9800';
            case 'Medium': return '#2196f3';
            default: return '#4caf50';
        }
    }

    // Start Polling
    setInterval(checkAlerts, pollInterval);
    checkAlerts(); // Initial check
})();
