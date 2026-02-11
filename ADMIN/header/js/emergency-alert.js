/**
 * Global Emergency Alert System
 * Displays emergency alerts with sound across all pages
 */

(function() {
    'use strict';

    // Configuration
    const CONFIG = {
        checkInterval: 10000, // Check every 10 seconds
        apiEndpoint: null, // Will be set based on context
        shownAlerts: new Set(), // Track shown alerts to avoid duplicates
        soundEnabled: true,
        modalZIndex: 10000
    };

    // Initialize API endpoint based on context
    if (typeof window.API_BASE_PATH !== 'undefined') {
        CONFIG.apiEndpoint = window.API_BASE_PATH + 'alerts.php';
    } else {
        // Try to detect context
        const path = window.location.pathname;
        if (path.includes('/ADMIN/')) {
            CONFIG.apiEndpoint = '../api/alerts.php?status=active&emergency_only=true';
        } else if (path.includes('/USERS/')) {
            CONFIG.apiEndpoint = 'api/get-alerts.php';
        } else {
            CONFIG.apiEndpoint = 'USERS/api/get-alerts.php';
        }
    }

    // Create emergency sound (using Web Audio API for compatibility)
    function createEmergencySound() {
        try {
            const audioContext = new (window.AudioContext || window.webkitAudioContext)();
            const oscillator = audioContext.createOscillator();
            const gainNode = audioContext.createGain();

            oscillator.connect(gainNode);
            gainNode.connect(audioContext.destination);

            oscillator.frequency.value = 800;
            oscillator.type = 'sine';

            gainNode.gain.setValueAtTime(0.3, audioContext.currentTime);
            gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.5);

            oscillator.start(audioContext.currentTime);
            oscillator.stop(audioContext.currentTime + 0.5);
        } catch (e) {
            console.warn('Could not play emergency sound:', e);
        }
    }

    // Play emergency sound (multiple beeps)
    function playEmergencySound() {
        if (!CONFIG.soundEnabled) return;
        
        createEmergencySound();
        setTimeout(() => createEmergencySound(), 300);
        setTimeout(() => createEmergencySound(), 600);
    }

    // Create emergency modal HTML
    function createEmergencyModal() {
        const modalHTML = `
            <div id="emergencyAlertModal" class="emergency-alert-modal" style="display: none;">
                <div class="emergency-alert-overlay"></div>
                <div class="emergency-alert-content">
                    <div class="emergency-alert-header">
                        <div class="emergency-alert-icon">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        <h2 class="emergency-alert-title">EMERGENCY ALERT</h2>
                    </div>
                    <div class="emergency-alert-body">
                        <h3 id="emergencyAlertTitle" class="emergency-alert-title-text"></h3>
                        <p id="emergencyAlertMessage" class="emergency-alert-message"></p>
                        <div id="emergencyAlertContent" class="emergency-alert-content-text"></div>
                        <div class="emergency-alert-category" id="emergencyAlertCategory"></div>
                    </div>
                    <div class="emergency-alert-footer">
                        <button id="emergencyAlertAcknowledge" class="emergency-alert-btn emergency-alert-btn-primary">
                            <i class="fas fa-check"></i> Acknowledge
                        </button>
                    </div>
                </div>
            </div>
        `;

        const modalContainer = document.createElement('div');
        modalContainer.innerHTML = modalHTML;
        document.body.appendChild(modalContainer.firstElementChild);

        // Add event listeners
        const modal = document.getElementById('emergencyAlertModal');
        const acknowledgeBtn = document.getElementById('emergencyAlertAcknowledge');
        const overlay = modal.querySelector('.emergency-alert-overlay');

        acknowledgeBtn.addEventListener('click', closeEmergencyModal);
        overlay.addEventListener('click', closeEmergencyModal);

        // Prevent modal from closing on Escape (emergency alerts should be acknowledged)
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && modal.style.display !== 'none') {
                e.preventDefault();
            }
        });
    }

    // Show emergency alert modal
    function showEmergencyModal(alert) {
        const modal = document.getElementById('emergencyAlertModal');
        if (!modal) {
            createEmergencyModal();
        }

        const titleEl = document.getElementById('emergencyAlertTitle');
        const messageEl = document.getElementById('emergencyAlertMessage');
        const contentEl = document.getElementById('emergencyAlertContent');
        const categoryEl = document.getElementById('emergencyAlertCategory');

        // Set alert data
        titleEl.textContent = alert.title || 'Emergency Alert';
        messageEl.textContent = alert.message || '';
        
        if (alert.content) {
            contentEl.innerHTML = alert.content.replace(/\n/g, '<br>');
            contentEl.style.display = 'block';
        } else {
            contentEl.style.display = 'none';
        }

        if (alert.category_name) {
            categoryEl.innerHTML = `<i class="${alert.category_icon || 'fas fa-exclamation-triangle'}" style="color: ${alert.category_color || '#e74c3c'};"></i> ${alert.category_name}`;
            categoryEl.style.display = 'flex';
        } else {
            categoryEl.style.display = 'none';
        }

        // Show modal
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';

        // Play emergency sound
        playEmergencySound();

        // Mark as shown
        CONFIG.shownAlerts.add(alert.id);
    }

    // Close emergency modal
    function closeEmergencyModal() {
        const modal = document.getElementById('emergencyAlertModal');
        if (modal) {
            modal.style.display = 'none';
            document.body.style.overflow = '';
        }
    }

    // Check for emergency alerts
    async function checkForEmergencyAlerts() {
        try {
            // Build API URL
            let apiUrl = CONFIG.apiEndpoint;
            const separator = apiUrl.includes('?') ? '&' : '?';
            apiUrl += `${separator}status=active&limit=10&time_filter=recent`;

            const response = await fetch(apiUrl, {
                cache: 'no-cache',
                headers: {
                    'Cache-Control': 'no-cache',
                    'Pragma': 'no-cache'
                }
            });

            if (!response.ok) {
                console.warn('Failed to fetch emergency alerts');
                return;
            }

            const data = await response.json();
            
            if (data.success && data.alerts && Array.isArray(data.alerts)) {
                // Filter for emergency alerts (EXTREME severity or [EXTREME] in title)
                const emergencyAlerts = data.alerts.filter(alert => {
                    const title = alert.title || '';
                    const isExtreme = title.includes('[EXTREME]') || 
                                     title.toLowerCase().includes('extreme') ||
                                     alert.severity === 'extreme' ||
                                     alert.severity === 'critical';
                    const notShown = !CONFIG.shownAlerts.has(alert.id);
                    return isExtreme && notShown;
                });

                // Show the most recent emergency alert
                if (emergencyAlerts.length > 0) {
                    // Sort by created_at (most recent first)
                    emergencyAlerts.sort((a, b) => {
                        const timeA = new Date(a.created_at || 0);
                        const timeB = new Date(b.created_at || 0);
                        return timeB - timeA;
                    });

                    showEmergencyModal(emergencyAlerts[0]);
                }
            }
        } catch (error) {
            console.error('Error checking for emergency alerts:', error);
        }
    }

    // Initialize emergency alert system
    function init() {
        // Create modal HTML
        createEmergencyModal();

        // Check immediately on page load
        checkForEmergencyAlerts();

        // Check periodically
        setInterval(checkForEmergencyAlerts, CONFIG.checkInterval);

        // Also check when page becomes visible (user switches back to tab)
        document.addEventListener('visibilitychange', function() {
            if (!document.hidden) {
                checkForEmergencyAlerts();
            }
        });
    }

    // Wait for DOM to be ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    // Expose close function globally (in case needed)
    window.closeEmergencyModal = closeEmergencyModal;
})();
