/**
 * Language Sync - Ensures user-side languages stay in sync with admin-managed database
 * This script ensures that when admins add/update languages, users see them immediately
 */

class LanguageSync {
    constructor() {
        this.lastSyncTime = null;
        this.syncInterval = null;
        this.init();
    }
    
    init() {
        // Sync on page load
        this.syncLanguages();
        
        // Sync every 30 seconds to catch admin changes
        this.syncInterval = setInterval(() => {
            this.syncLanguages();
        }, 30000);
        
        // Sync when page becomes visible (user returns to tab)
        document.addEventListener('visibilitychange', () => {
            if (!document.hidden) {
                this.syncLanguages();
            }
        });
        
        // Sync when language manager is available
        if (window.languageManager) {
            document.addEventListener('languagesUpdated', () => {
                // Languages were updated, refresh modal if open
                if (window.languageSelectorModal && window.languageSelectorModal.modal) {
                    const isOpen = window.languageSelectorModal.modal.classList.contains('show');
                    if (isOpen) {
                        window.languageSelectorModal.loadLanguages(true).then(() => {
                            window.languageSelectorModal.filterLanguages();
                        });
                    }
                }
            });
        }
    }
    
    async syncLanguages() {
        try {
            // Check for updates from admin-managed database
            const response = await fetch(`api/languages.php?action=check-updates${this.lastSyncTime ? '&last_update=' + encodeURIComponent(this.lastSyncTime) : ''}`, {
                cache: 'no-cache',
                headers: {
                    'Cache-Control': 'no-cache',
                    'Pragma': 'no-cache'
                }
            });
            
            const data = await response.json();
            
            if (data.success) {
                if (data.updated) {
                    // Languages were updated by admin, reload them
                    console.log('Languages updated by admin, refreshing...');
                    
                    // Update language manager
                    if (window.languageManager) {
                        await window.languageManager.loadLanguages(true);
                    }
                    
                    // Update modal if open
                    if (window.languageSelectorModal) {
                        await window.languageSelectorModal.loadLanguages(true);
                    }
                    
                    // Show notification to user
                    this.showUpdateNotification();
                }
                
                // Update last sync time
                if (data.last_update) {
                    this.lastSyncTime = data.last_update;
                }
            }
        } catch (error) {
            console.error('Error syncing languages:', error);
        }
    }
    
    showUpdateNotification() {
        // Create or update notification
        let notification = document.getElementById('languageSyncNotification');
        if (!notification) {
            notification = document.createElement('div');
            notification.id = 'languageSyncNotification';
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: #4caf50;
                color: white;
                padding: 12px 20px;
                border-radius: 8px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                z-index: 10001;
                display: flex;
                align-items: center;
                gap: 10px;
                animation: slideIn 0.3s ease;
                max-width: 300px;
            `;
            document.body.appendChild(notification);
        }
        
        notification.innerHTML = `
            <i class="fas fa-sync-alt"></i>
            <span>New languages available! Click the globe icon to see them.</span>
            <button onclick="this.parentElement.remove()" style="background: none; border: none; color: white; cursor: pointer; margin-left: 10px;">
                <i class="fas fa-times"></i>
            </button>
        `;
        
        // Auto-remove after 5 seconds
        setTimeout(() => {
            if (notification.parentElement) {
                notification.style.animation = 'slideOut 0.3s ease';
                setTimeout(() => notification.remove(), 300);
            }
        }, 5000);
    }
    
    destroy() {
        if (this.syncInterval) {
            clearInterval(this.syncInterval);
            this.syncInterval = null;
        }
    }
}

// Initialize language sync
if (typeof window.languageSync === 'undefined') {
    window.languageSync = new LanguageSync();
}

// Export for use in other scripts
if (typeof module !== 'undefined' && module.exports) {
    module.exports = LanguageSync;
}

