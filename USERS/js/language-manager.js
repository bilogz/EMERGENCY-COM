/**
 * Language Manager - Real-time Language Support
 * Handles language detection, selection, and real-time updates
 */

class LanguageManager {
    constructor() {
        this.currentLanguage = 'en';
        this.supportedLanguages = [];
        this.lastUpdate = null;
        this.updateInterval = null;
        this.deviceLanguage = null;
        this.init();
    }
    
    async init() {
        // Detect device language first
        await this.detectDeviceLanguage();
        
        // Load user preference
        await this.loadUserPreference();
        
        // Load languages from server
        await this.loadLanguages();
        
        // Start real-time updates
        this.startRealTimeUpdates();
        
        // Apply language
        this.applyLanguage();
    }
    
    async detectDeviceLanguage() {
        try {
            const response = await fetch('api/languages.php?action=detect');
            const data = await response.json();
            
            if (data.success && data.detected_language) {
                this.deviceLanguage = data.detected_language;
                
                // If user hasn't set a preference, use device language
                if (!localStorage.getItem('user_language_set')) {
                    this.currentLanguage = this.deviceLanguage;
                    localStorage.setItem('preferredLanguage', this.deviceLanguage);
                }
                
                return this.deviceLanguage;
            }
        } catch (error) {
            console.error('Error detecting device language:', error);
        }
        
        // Fallback: detect from browser
        const browserLang = navigator.language || navigator.userLanguage || 'en';
        this.deviceLanguage = browserLang.split('-')[0].toLowerCase();
        return this.deviceLanguage;
    }
    
    async loadUserPreference() {
        try {
            const savedLang = localStorage.getItem('preferredLanguage');
            if (savedLang) {
                this.currentLanguage = savedLang;
                return;
            }
            
            // Try to get from server if logged in
            const response = await fetch('api/user-language.php?action=get');
            const data = await response.json();
            if (data.success && data.language) {
                this.currentLanguage = data.language;
                localStorage.setItem('preferredLanguage', data.language);
            }
        } catch (error) {
            console.error('Error loading user preference:', error);
        }
    }
    
    async loadLanguages(forceUpdate = false) {
        try {
            // Always fetch from admin-managed database
            const url = forceUpdate 
                ? 'api/languages.php?action=list&_=' + Date.now()
                : `api/languages.php?action=list${this.lastUpdate ? '&last_update=' + encodeURIComponent(this.lastUpdate) : ''}`;
            
            const response = await fetch(url, {
                cache: 'no-cache',
                headers: {
                    'Cache-Control': 'no-cache',
                    'Pragma': 'no-cache'
                }
            });
            const data = await response.json();
            
            if (data.success && data.languages) {
                const wasUpdated = this.supportedLanguages.length !== data.languages.length || 
                                  this.lastUpdate !== data.last_update ||
                                  JSON.stringify(this.supportedLanguages) !== JSON.stringify(data.languages);
                
                // Update from admin-managed database
                this.supportedLanguages = data.languages;
                this.lastUpdate = data.last_update;
                
                // Trigger update event if languages changed
                if (wasUpdated || forceUpdate) {
                    document.dispatchEvent(new CustomEvent('languagesUpdated', {
                        detail: {
                            languages: this.supportedLanguages,
                            count: data.count,
                            lastUpdate: this.lastUpdate,
                            source: 'admin_database'
                        }
                    }));
                }
                
                return this.supportedLanguages;
            }
        } catch (error) {
            console.error('Error loading languages:', error);
            // Use fallback languages only if API completely fails
            this.supportedLanguages = this.getFallbackLanguages();
        }
        
        return this.supportedLanguages;
    }
    
    async checkForUpdates() {
        try {
            if (!this.lastUpdate) {
                await this.loadLanguages(true);
                return;
            }
            
            const response = await fetch(`api/languages.php?action=check-updates&last_update=${encodeURIComponent(this.lastUpdate)}`);
            const data = await response.json();
            
            if (data.success && data.updated) {
                // Languages were updated, reload them
                await this.loadLanguages(true);
                
                // Show notification
                this.showUpdateNotification();
            }
        } catch (error) {
            console.error('Error checking for updates:', error);
        }
    }
    
    startRealTimeUpdates() {
        // Check for updates every 30 seconds to reflect admin changes
        this.updateInterval = setInterval(() => {
            this.checkForUpdates();
        }, 30000); // 30 seconds
        
        // Also check when page becomes visible (admin may have added languages)
        document.addEventListener('visibilitychange', () => {
            if (!document.hidden) {
                this.checkForUpdates();
            }
        });
        
        // Listen for admin language changes (if using websockets or events)
        // For now, periodic checks handle this
    }
    
    stopRealTimeUpdates() {
        if (this.updateInterval) {
            clearInterval(this.updateInterval);
            this.updateInterval = null;
        }
    }
    
    async setLanguage(languageCode) {
        // Verify language is supported
        const lang = this.supportedLanguages.find(l => l.language_code === languageCode);
        if (!lang && languageCode !== 'en') {
            console.warn(`Language ${languageCode} not supported, falling back to English`);
            languageCode = 'en';
        }
        
        this.currentLanguage = languageCode;
        localStorage.setItem('preferredLanguage', languageCode);
        localStorage.setItem('user_language_set', 'true');
        
        // Save to server if logged in
        try {
            await fetch('api/user-language.php?action=set', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({language: languageCode})
            });
        } catch (error) {
            console.error('Error saving language preference:', error);
        }
        
        // Apply language
        this.applyLanguage();
        
        // Trigger event
        document.dispatchEvent(new CustomEvent('languageChanged', {
            detail: {
                language: languageCode,
                languageInfo: lang
            }
        }));
    }
    
    applyLanguage() {
        // Update HTML attributes
        document.documentElement.setAttribute('lang', this.currentLanguage);
        document.documentElement.setAttribute('data-lang', this.currentLanguage);
        
        // Apply translations if available
        if (typeof setLanguage === 'function') {
            setLanguage(this.currentLanguage);
        } else if (typeof applyTranslations === 'function') {
            localStorage.setItem('preferredLanguage', this.currentLanguage);
            applyTranslations();
        }
    }
    
    getLanguageDisplay(langCode) {
        const lang = this.supportedLanguages.find(l => l.language_code === langCode);
        if (lang) {
            return lang.flag_emoji ? `${lang.flag_emoji} ${lang.language_name}` : lang.language_name;
        }
        return langCode.toUpperCase();
    }
    
    getLanguageInfo(langCode) {
        return this.supportedLanguages.find(l => l.language_code === langCode);
    }
    
    showUpdateNotification() {
        // Create or update notification element
        let notification = document.getElementById('languageUpdateNotification');
        if (!notification) {
            notification = document.createElement('div');
            notification.id = 'languageUpdateNotification';
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: #4caf50;
                color: white;
                padding: 12px 20px;
                border-radius: 8px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                z-index: 10000;
                display: flex;
                align-items: center;
                gap: 10px;
                animation: slideIn 0.3s ease;
            `;
            document.body.appendChild(notification);
        }
        
        notification.innerHTML = `
            <i class="fas fa-check-circle"></i>
            <span>New languages available! Refresh to see updates.</span>
            <button onclick="this.parentElement.remove()" style="background: none; border: none; color: white; cursor: pointer; margin-left: 10px;">
                <i class="fas fa-times"></i>
            </button>
        `;
        
        // Auto-remove after 5 seconds
        setTimeout(() => {
            if (notification.parentElement) {
                notification.remove();
            }
        }, 5000);
    }
    
    getFallbackLanguages() {
        return [
            {language_code: 'en', language_name: 'English', native_name: 'English', flag_emoji: '🇺🇸', is_active: 1, is_ai_supported: 1},
            {language_code: 'fil', language_name: 'Filipino', native_name: 'Filipino', flag_emoji: '🇵🇭', is_active: 1, is_ai_supported: 1},
            {language_code: 'es', language_name: 'Spanish', native_name: 'Español', flag_emoji: '🇪🇸', is_active: 1, is_ai_supported: 1}
        ];
    }
}

// Initialize global language manager
if (typeof window.languageManager === 'undefined') {
    window.languageManager = new LanguageManager();
}

// Export for use in other scripts
if (typeof module !== 'undefined' && module.exports) {
    module.exports = LanguageManager;
}

