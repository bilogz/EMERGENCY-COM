/**
 * Language Sync - Ensures user-side languages stay in sync with admin-managed database
 * This script ensures that when admins add/update languages, users see them immediately
 */

if (typeof window.LanguageSync === 'undefined') {
window.LanguageSync = class LanguageSync {
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
            const url = `api/languages.php?action=check-updates${this.lastSyncTime ? '&last_update=' + encodeURIComponent(this.lastSyncTime) : ''}`;
            const apiPath = this.getApiPath(url);
            
            const response = await fetch(apiPath, {
                cache: 'no-cache',
                headers: {
                    'Cache-Control': 'no-cache',
                    'Pragma': 'no-cache'
                }
            });
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                throw new Error('Response is not JSON');
            }
            
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
        // Intentionally silent: language updates are applied in background
        // without showing popup notifications.
    }
    
    destroy() {
        if (this.syncInterval) {
            clearInterval(this.syncInterval);
            this.syncInterval = null;
        }
    }
    
    /**
     * Get correct API path based on current page context
     */
    getApiPath(relativePath) {
        // Use global config if available
        if (window.API_BASE_PATH && window.IS_ROOT_CONTEXT) {
            if (relativePath.startsWith('api/')) {
                return window.API_BASE_PATH + relativePath.substring(4);
            }
            return window.API_BASE_PATH + relativePath;
        }
        
        // Fallback to path detection
        const currentPath = window.location.pathname;
        const isInUsersFolder = currentPath.includes('/USERS/');
        
        if (relativePath.startsWith('api/')) {
            if (!isInUsersFolder) {
                return 'USERS/' + relativePath;
            }
        }
        
        return relativePath;
    }
}
}

// Initialize language sync
if (typeof window.languageSync === 'undefined') {
    window.languageSync = new window.LanguageSync();
}

// Export for use in other scripts
if (typeof module !== 'undefined' && module.exports) {
    module.exports = window.LanguageSync;
}

