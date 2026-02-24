/**
 * Global Emergency Alert System (Citizen Pages)
 * Mandatory modal + sound for:
 * - Automated warnings with high/critical severity
 * - Mass notifications
 */

(function () {
    'use strict';

    // Prevent duplicate initialization when script is included more than once.
    if (window.__emergencyAlertInitialized) {
        return;
    }
    window.__emergencyAlertInitialized = true;

    const CONFIG = {
        checkIntervalMs: 3000,
        maxAlarmMs: 60000,
        beepIntervalMs: 900,
        modalZIndex: 10000
    };

    const state = {
        apiEndpoint: '',
        shownIds: new Set(),
        acknowledgedIds: new Set(),
        lastHandledAlertId: 0,
        modalOpen: false,
        currentAlertId: null,
        alarmActive: false,
        alarmLoopTimer: null,
        alarmAutoStopTimer: null
    };

    function resolveApiEndpoint() {
        if (typeof window.API_BASE_PATH === 'string' && window.API_BASE_PATH.trim() !== '') {
            return window.API_BASE_PATH.replace(/\/+$/, '') + '/get-alerts.php';
        }
        const path = window.location.pathname;
        if (path.includes('/USERS/')) {
            return 'api/get-alerts.php';
        }
        return 'USERS/api/get-alerts.php';
    }

    function shouldRunForCurrentPage() {
        const path = String(window.location.pathname || '').toLowerCase();
        return !path.includes('/admin/');
    }

    function loadAcknowledgedIds() {
        state.acknowledgedIds.clear();
        try {
            const lastHandled = localStorage.getItem('last_handled_mandatory_alert_id')
                || localStorage.getItem('last_handled_critical_alert_id');
            state.lastHandledAlertId = lastHandled ? Number(lastHandled) || 0 : 0;
        } catch (e) {
            // Ignore storage errors.
        }
    }

    function persistAcknowledgedIds() {
        try {
            localStorage.setItem('last_handled_mandatory_alert_id', String(state.lastHandledAlertId || 0));
            // Backward compatibility with previous key.
            localStorage.setItem('last_handled_critical_alert_id', String(state.lastHandledAlertId || 0));
        } catch (e) {
            // Ignore storage errors.
        }
    }

    function playSingleBeep() {
        try {
            const audioContext = new (window.AudioContext || window.webkitAudioContext)();
            const oscillator = audioContext.createOscillator();
            const gainNode = audioContext.createGain();
            oscillator.connect(gainNode);
            gainNode.connect(audioContext.destination);
            oscillator.type = 'square';
            oscillator.frequency.setValueAtTime(920, audioContext.currentTime);
            gainNode.gain.setValueAtTime(0.0001, audioContext.currentTime);
            gainNode.gain.exponentialRampToValueAtTime(0.2, audioContext.currentTime + 0.02);
            gainNode.gain.exponentialRampToValueAtTime(0.0001, audioContext.currentTime + 0.35);
            oscillator.start(audioContext.currentTime);
            oscillator.stop(audioContext.currentTime + 0.4);
        } catch (e) {
            // Audio may fail until user interaction; keep modal behavior anyway.
        }
    }

    function stopAlarmLoop() {
        state.alarmActive = false;
        if (state.alarmLoopTimer) {
            clearInterval(state.alarmLoopTimer);
            state.alarmLoopTimer = null;
        }
        if (state.alarmAutoStopTimer) {
            clearTimeout(state.alarmAutoStopTimer);
            state.alarmAutoStopTimer = null;
        }

        const stopBtn = document.getElementById('emergencyAlertStopAlarm');
        if (stopBtn) {
            stopBtn.innerHTML = '<i class="fas fa-volume-mute"></i> Alarm Stopped';
            stopBtn.disabled = true;
        }
        const status = document.getElementById('emergencyAlertAlarmStatus');
        if (status) {
            status.textContent = 'Alarm stopped';
        }
    }

    function startAlarmLoop() {
        stopAlarmLoop();
        state.alarmActive = true;
        playSingleBeep();

        state.alarmLoopTimer = setInterval(function () {
            if (!state.alarmActive) return;
            playSingleBeep();
        }, CONFIG.beepIntervalMs);

        state.alarmAutoStopTimer = setTimeout(function () {
            stopAlarmLoop();
        }, CONFIG.maxAlarmMs);

        const stopBtn = document.getElementById('emergencyAlertStopAlarm');
        if (stopBtn) {
            stopBtn.innerHTML = '<i class="fas fa-volume-xmark"></i> Stop Alarm';
            stopBtn.disabled = false;
        }
        const status = document.getElementById('emergencyAlertAlarmStatus');
        if (status) {
            status.textContent = 'Alarm active (auto-stop in 1 min)';
        }
    }

    function ensureModalExists() {
        if (document.getElementById('emergencyAlertModal')) {
            return;
        }

        const modal = document.createElement('div');
        modal.id = 'emergencyAlertModal';
        modal.className = 'emergency-alert-modal';
        modal.style.display = 'none';
        modal.style.zIndex = String(CONFIG.modalZIndex);
        modal.innerHTML = [
            '<div class="emergency-alert-overlay"></div>',
            '<div class="emergency-alert-content" role="dialog" aria-modal="true" aria-labelledby="emergencyAlertTitle">',
            '  <div class="emergency-alert-header">',
            '    <div id="emergencyAlertHeaderIcon" class="emergency-alert-icon"><i class="fas fa-triangle-exclamation"></i></div>',
            '    <h2 id="emergencyAlertHeaderTitle" class="emergency-alert-title">MANDATORY EMERGENCY ALERT</h2>',
            '  </div>',
            '  <div class="emergency-alert-body">',
            '    <h3 id="emergencyAlertTitle" class="emergency-alert-title-text"></h3>',
            '    <p id="emergencyAlertMessage" class="emergency-alert-message"></p>',
            '    <div id="emergencyAlertContent" class="emergency-alert-content-text" style="display:none;"></div>',
            '    <div id="emergencyAlertCategory" class="emergency-alert-category" style="display:none;"></div>',
            '    <div id="emergencyAlertAlarmStatus" style="margin-top:0.75rem; font-weight:700; color:#b91c1c;">Alarm active (auto-stop in 1 min)</div>',
            '  </div>',
            '  <div class="emergency-alert-footer" style="display:flex; gap:0.75rem; justify-content:center; flex-wrap:wrap;">',
            '    <button id="emergencyAlertStopAlarm" class="emergency-alert-btn" style="background:#111827; color:#fff;">',
            '      <i class="fas fa-volume-xmark"></i> Stop Alarm',
            '    </button>',
            '    <button id="emergencyAlertAcknowledge" class="emergency-alert-btn emergency-alert-btn-primary">',
            '      <i class="fas fa-check"></i> Acknowledge & Close',
            '    </button>',
            '  </div>',
            '</div>'
        ].join('');

        document.body.appendChild(modal);

        const overlay = modal.querySelector('.emergency-alert-overlay');
        const stopBtn = document.getElementById('emergencyAlertStopAlarm');
        const ackBtn = document.getElementById('emergencyAlertAcknowledge');

        // Mandatory modal: overlay click does not dismiss.
        if (overlay) {
            overlay.addEventListener('click', function (e) {
                e.preventDefault();
            });
        }
        if (stopBtn) {
            stopBtn.addEventListener('click', function () {
                stopAlarmLoop();
            });
        }
        if (ackBtn) {
            ackBtn.addEventListener('click', function () {
                acknowledgeAndCloseModal();
            });
        }

        // Prevent ESC close for mandatory alerts.
        document.addEventListener('keydown', function (e) {
            const m = document.getElementById('emergencyAlertModal');
            if (m && m.style.display !== 'none' && e.key === 'Escape') {
                e.preventDefault();
                e.stopPropagation();
            }
        }, true);
    }

    function normalizeText(value) {
        return String(value || '').trim().toLowerCase();
    }

    function isMassNotificationAlert(alert) {
        const source = normalizeText(alert.source);
        if (!source) return false;
        return source.includes('mass_notification')
            || source === 'mass'
            || source.includes('mass-notification')
            || source.includes('mass_notification');
    }

    function isAutomatedHighPriorityAlert(alert) {
        const severity = normalizeText(alert.severity);
        const source = normalizeText(alert.source);
        const message = normalizeText(alert.message);
        const automatedSources = ['pagasa', 'phivolcs', 'automated_warning', 'automated', 'ai_warning', 'ai'];
        const isAutomatedSource = automatedSources.includes(source);
        const hasPrioritySeverity = severity === 'high' || severity === 'critical' || severity === 'extreme';
        const emergencyBulletinPattern = message.includes('emergency bulletin');

        return hasPrioritySeverity && (isAutomatedSource || emergencyBulletinPattern);
    }

    function shouldTriggerMandatoryModal(alert) {
        return isMassNotificationAlert(alert) || isAutomatedHighPriorityAlert(alert);
    }

    function getModalPreset(alert) {
        const severity = normalizeText(alert.severity);
        if (isMassNotificationAlert(alert)) {
            return {
                headerTitle: 'MASS NOTIFICATION',
                headerColor: '#1d4ed8',
                iconClass: 'fas fa-bullhorn'
            };
        }

        if (severity === 'high') {
            return {
                headerTitle: 'HIGH AUTOMATED WARNING',
                headerColor: '#d97706',
                iconClass: 'fas fa-triangle-exclamation'
            };
        }

        return {
            headerTitle: 'CRITICAL AUTOMATED WARNING',
            headerColor: '#dc2626',
            iconClass: 'fas fa-triangle-exclamation'
        };
    }

    function showCriticalModal(alert) {
        ensureModalExists();

        const modal = document.getElementById('emergencyAlertModal');
        const header = modal ? modal.querySelector('.emergency-alert-header') : null;
        const headerTitleEl = document.getElementById('emergencyAlertHeaderTitle');
        const headerIconEl = document.getElementById('emergencyAlertHeaderIcon');
        const titleEl = document.getElementById('emergencyAlertTitle');
        const messageEl = document.getElementById('emergencyAlertMessage');
        const contentEl = document.getElementById('emergencyAlertContent');
        const categoryEl = document.getElementById('emergencyAlertCategory');
        const statusEl = document.getElementById('emergencyAlertAlarmStatus');
        const preset = getModalPreset(alert);

        if (!modal || !titleEl || !messageEl || !contentEl || !categoryEl) {
            return;
        }

        if (header) {
            header.style.background = 'linear-gradient(135deg, ' + preset.headerColor + ' 0%, ' + preset.headerColor + ' 100%)';
        }
        if (headerTitleEl) {
            headerTitleEl.textContent = preset.headerTitle;
        }
        if (headerIconEl) {
            headerIconEl.innerHTML = '<i class="' + preset.iconClass + '"></i>';
        }
        if (titleEl) {
            titleEl.style.color = preset.headerColor;
        }
        if (statusEl) {
            statusEl.style.color = preset.headerColor;
        }

        titleEl.textContent = alert.title || 'Critical Emergency Alert';
        messageEl.textContent = alert.message || '';

        if (alert.content) {
            contentEl.innerHTML = String(alert.content).replace(/\n/g, '<br>');
            contentEl.style.display = 'block';
        } else {
            contentEl.style.display = 'none';
        }

        if (alert.category_name) {
            const icon = alert.category_icon || 'fas fa-triangle-exclamation';
            const color = alert.category_color || '#e74c3c';
            categoryEl.innerHTML = '<i class="' + icon + '" style="color:' + color + ';"></i> ' + alert.category_name;
            categoryEl.style.display = 'flex';
        } else {
            categoryEl.style.display = 'none';
        }

        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
        state.modalOpen = true;
        state.currentAlertId = String(alert.id || '');
        state.shownIds.add(state.currentAlertId);

        startAlarmLoop();
    }

    function acknowledgeAndCloseModal() {
        const modal = document.getElementById('emergencyAlertModal');
        if (modal) {
            modal.style.display = 'none';
        }
        document.body.style.overflow = '';
        state.modalOpen = false;
        stopAlarmLoop();

        if (state.currentAlertId) {
            state.acknowledgedIds.add(state.currentAlertId);
            const currentIdNum = Number(state.currentAlertId) || 0;
            if (currentIdNum > state.lastHandledAlertId) {
                state.lastHandledAlertId = currentIdNum;
            }
            persistAcknowledgedIds();
            state.currentAlertId = null;
        }
    }

    async function checkForCriticalAlerts() {
        if (!state.apiEndpoint) return;
        if (state.modalOpen) return;

        try {
            const sep = state.apiEndpoint.includes('?') ? '&' : '?';
            const url = state.apiEndpoint + sep + 'status=active&limit=20';

            const response = await fetch(url, {
                cache: 'no-cache',
                headers: {
                    'Cache-Control': 'no-cache',
                    'Pragma': 'no-cache'
                }
            });
            if (!response.ok) return;

            const data = await response.json();
            if (!data || !data.success || !Array.isArray(data.alerts)) return;

            const candidates = data.alerts
                .filter(shouldTriggerMandatoryModal)
                .filter(function (a) {
                    const id = String(a.id || '');
                    const idNum = Number(id) || 0;
                    if (!id) return false;
                    if (state.acknowledgedIds.has(id)) return false;
                    if (state.shownIds.has(id)) return false;
                    if (idNum <= state.lastHandledAlertId) return false;
                    return true;
                })
                .sort(function (a, b) {
                    const ta = new Date(a.created_at || 0).getTime();
                    const tb = new Date(b.created_at || 0).getTime();
                    return tb - ta;
                });

            if (candidates.length > 0) {
                // Only show the newest unseen critical alert to avoid backlog loops.
                showCriticalModal(candidates[0]);
            }
        } catch (e) {
            // Silent fail to avoid noisy UX.
        }
    }

    function init() {
        if (!shouldRunForCurrentPage()) {
            return;
        }
        state.apiEndpoint = resolveApiEndpoint();
        loadAcknowledgedIds();
        ensureModalExists();
        checkForCriticalAlerts();
        setInterval(checkForCriticalAlerts, CONFIG.checkIntervalMs);
        document.addEventListener('visibilitychange', function () {
            if (!document.hidden) checkForCriticalAlerts();
        });
        window.closeEmergencyModal = acknowledgeAndCloseModal;
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
