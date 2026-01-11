/**
 * Incident Alert Dashboard JavaScript
 * Handles real-time alert fetching and display
 */

(function() {
    'use strict';
    
    // Configuration
    const CONFIG = {
        apiUrl: '../api/get-alerts-by-area.php',
        refreshInterval: 30000, // 30 seconds
        animationDuration: 300
    };
    
    // State
    let refreshTimer = null;
    let isRefreshing = false;
    
    // DOM Elements
    const alertsContainer = document.getElementById('alertsContainer');
    const emptyState = document.getElementById('emptyState');
    const refreshBtn = document.getElementById('refreshBtn');
    const lastUpdateEl = document.getElementById('lastUpdate');
    
    /**
     * Initialize the dashboard
     */
    function init() {
        // Load alerts on page load
        loadAlerts();
        
        // Set up refresh button
        if (refreshBtn) {
            refreshBtn.addEventListener('click', () => {
                loadAlerts(true);
            });
        }
        
        // Set up auto-refresh
        startAutoRefresh();
    }
    
    /**
     * Load alerts from API
     */
    function loadAlerts(manualRefresh = false) {
        if (isRefreshing && !manualRefresh) {
            return; // Prevent multiple simultaneous requests
        }
        
        isRefreshing = true;
        
        // Show loading state
        if (manualRefresh) {
            showLoading(true);
        } else {
            showLoading(false);
        }
        
        // Get area from URL parameter or use null for all alerts
        const urlParams = new URLSearchParams(window.location.search);
        const area = urlParams.get('area') || null;
        
        // Build API URL
        let apiUrl = CONFIG.apiUrl;
        if (area) {
            apiUrl += `?area=${encodeURIComponent(area)}`;
        }
        
        // Fetch alerts
        fetch(apiUrl)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    displayAlerts(data.data);
                    updateLastUpdateTime();
                } else {
                    showError(data.message || 'Failed to load alerts');
                }
            })
            .catch(error => {
                console.error('Error loading alerts:', error);
                showError('Failed to load alerts. Please try again.');
            })
            .finally(() => {
                isRefreshing = false;
                if (refreshBtn) {
                    refreshBtn.querySelector('i').classList.remove('fa-spin');
                }
            });
    }
    
    /**
     * Display alerts in the container
     */
    function displayAlerts(alerts) {
        if (!alerts || alerts.length === 0) {
            showEmptyState();
            return;
        }
        
        hideEmptyState();
        showLoading(false);
        
        // Clear container
        alertsContainer.innerHTML = '';
        
        // Create alert cards
        alerts.forEach(alert => {
            const card = createAlertCard(alert);
            alertsContainer.appendChild(card);
        });
    }
    
    /**
     * Create an alert card element
     */
    function createAlertCard(alert) {
        const card = document.createElement('div');
        card.className = `alert-card ${getCategoryClass(alert.category)}`;
        
        const categoryClass = getCategoryClass(alert.category);
        const categoryIcon = getCategoryIcon(alert.category);
        
        card.innerHTML = `
            <div class="alert-header">
                <div style="flex: 1;">
                    <span class="alert-category ${categoryClass}">
                        <i class="${categoryIcon}"></i> ${alert.category || 'Alert'}
                    </span>
                    <h3 class="alert-title">${escapeHtml(alert.title || 'Emergency Alert')}</h3>
                </div>
            </div>
            
            <div class="alert-meta">
                ${alert.area ? `<div class="alert-meta-item">
                    <i class="fas fa-map-marker-alt"></i>
                    <span>${escapeHtml(alert.area)}</span>
                </div>` : ''}
                
                ${alert.incident_type ? `<div class="alert-meta-item">
                    <i class="fas fa-tag"></i>
                    <span>${escapeHtml(alert.incident_type)}</span>
                </div>` : ''}
                
                ${alert.severity ? `<div class="alert-meta-item">
                    <i class="fas fa-exclamation-circle"></i>
                    <span>Severity: ${escapeHtml(alert.severity)}</span>
                </div>` : ''}
            </div>
            
            <div class="alert-message">
                ${escapeHtml(alert.message)}
            </div>
            
            <div class="alert-footer">
                <span>
                    <i class="fas fa-clock"></i>
                    ${formatTimestamp(alert.created_at)}
                </span>
                ${alert.incident_id ? `<span>Incident #${alert.incident_id}</span>` : ''}
            </div>
        `;
        
        return card;
    }
    
    /**
     * Get CSS class for alert category
     */
    function getCategoryClass(category) {
        if (!category) return 'advisory';
        
        const cat = category.toLowerCase();
        if (cat.includes('emergency')) return 'emergency';
        if (cat.includes('warning')) return 'warning';
        return 'advisory';
    }
    
    /**
     * Get icon for alert category
     */
    function getCategoryIcon(category) {
        if (!category) return 'fas fa-info-circle';
        
        const cat = category.toLowerCase();
        if (cat.includes('emergency')) return 'fas fa-exclamation-triangle';
        if (cat.includes('warning')) return 'fas fa-exclamation-circle';
        return 'fas fa-info-circle';
    }
    
    /**
     * Show loading state
     */
    function showLoading(showButton = false) {
        if (showButton && refreshBtn) {
            refreshBtn.querySelector('i').classList.add('fa-spin');
        }
        
        if (alertsContainer) {
            const existingSpinner = alertsContainer.querySelector('.loading-spinner');
            if (!existingSpinner) {
                alertsContainer.innerHTML = `
                    <div class="loading-spinner">
                        <i class="fas fa-spinner fa-spin"></i>
                        <p>Loading alerts...</p>
                    </div>
                `;
            }
        }
        
        hideEmptyState();
    }
    
    /**
     * Show empty state
     */
    function showEmptyState() {
        if (emptyState) {
            emptyState.style.display = 'block';
        }
        if (alertsContainer) {
            alertsContainer.innerHTML = '';
        }
    }
    
    /**
     * Hide empty state
     */
    function hideEmptyState() {
        if (emptyState) {
            emptyState.style.display = 'none';
        }
    }
    
    /**
     * Show error message
     */
    function showError(message) {
        if (alertsContainer) {
            alertsContainer.innerHTML = `
                <div class="alert-card emergency">
                    <div class="alert-header">
                        <span class="alert-category emergency">
                            <i class="fas fa-exclamation-triangle"></i> Error
                        </span>
                    </div>
                    <div class="alert-message">
                        ${escapeHtml(message)}
                    </div>
                </div>
            `;
        }
        hideEmptyState();
    }
    
    /**
     * Update last update time
     */
    function updateLastUpdateTime() {
        if (lastUpdateEl) {
            const now = new Date();
            lastUpdateEl.textContent = `Last updated: ${formatTime(now)}`;
        }
    }
    
    /**
     * Format timestamp
     */
    function formatTimestamp(timestamp) {
        if (!timestamp) return 'Unknown';
        
        const date = new Date(timestamp);
        const now = new Date();
        const diffMs = now - date;
        const diffMins = Math.floor(diffMs / 60000);
        const diffHours = Math.floor(diffMs / 3600000);
        const diffDays = Math.floor(diffMs / 86400000);
        
        if (diffMins < 1) return 'Just now';
        if (diffMins < 60) return `${diffMins} minute${diffMins > 1 ? 's' : ''} ago`;
        if (diffHours < 24) return `${diffHours} hour${diffHours > 1 ? 's' : ''} ago`;
        if (diffDays < 7) return `${diffDays} day${diffDays > 1 ? 's' : ''} ago`;
        
        return date.toLocaleDateString() + ' ' + date.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
    }
    
    /**
     * Format time
     */
    function formatTime(date) {
        return date.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit', second:'2-digit'});
    }
    
    /**
     * Escape HTML to prevent XSS
     */
    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    /**
     * Start auto-refresh timer
     */
    function startAutoRefresh() {
        if (refreshTimer) {
            clearInterval(refreshTimer);
        }
        refreshTimer = setInterval(() => {
            loadAlerts();
        }, CONFIG.refreshInterval);
    }
    
    /**
     * Stop auto-refresh timer
     */
    function stopAutoRefresh() {
        if (refreshTimer) {
            clearInterval(refreshTimer);
            refreshTimer = null;
        }
    }
    
    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
    
    // Cleanup on page unload
    window.addEventListener('beforeunload', () => {
        stopAutoRefresh();
    });
    
})();
