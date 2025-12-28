/**
 * Language Selector Modal Component
 * User-friendly searchable language selector
 */

class LanguageSelectorModal {
    constructor() {
        this.modal = null;
        this.languages = [];
        this.filteredLanguages = [];
        this.currentLanguage = 'en';
        this.searchTerm = '';
        this.init();
    }
    
    async init() {
        await this.loadLanguages();
        this.createModal();
        this.attachEventListeners();
    }
    
    async loadLanguages(forceRefresh = false) {
        try {
            // Always fetch fresh from admin-managed database
            const url = forceRefresh 
                ? 'api/languages.php?action=list&_=' + Date.now()
                : 'api/languages.php?action=list';
            
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
                const text = await response.text();
                console.error('API returned non-JSON:', text.substring(0, 200));
                throw new Error('Response is not JSON');
            }
            
            const data = await response.json();
            
            if (data.success && data.languages) {
                // Update languages from admin-managed database
                this.languages = data.languages;
                this.filteredLanguages = [...this.languages];
                
                // Trigger update event
                document.dispatchEvent(new CustomEvent('languagesLoaded', {
                    detail: {
                        languages: this.languages,
                        count: data.count,
                        lastUpdate: data.last_update
                    }
                }));
            } else {
                throw new Error(data.message || 'Failed to load languages');
            }
        } catch (error) {
            console.error('Error loading languages:', error);
            // Use fallback only if API completely fails
            this.languages = this.getFallbackLanguages();
            this.filteredLanguages = [...this.languages];
        }
        
        // Get current language
        this.currentLanguage = localStorage.getItem('preferredLanguage') || 'en';
    }
    
    createModal() {
        // Remove existing modal if any
        const existing = document.getElementById('languageSelectorModal');
        if (existing) existing.remove();
        
        // Create modal structure
        this.modal = document.createElement('div');
        this.modal.id = 'languageSelectorModal';
        this.modal.className = 'language-modal';
        this.modal.innerHTML = `
            <div class="language-modal-overlay"></div>
            <div class="language-modal-content">
                <div class="language-modal-header">
                    <h2>
                        <i class="fas fa-globe"></i>
                        <span>Select Language</span>
                    </h2>
                    <button class="language-modal-close" aria-label="Close">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                
                <div class="language-modal-search">
                    <div class="search-input-wrapper">
                        <i class="fas fa-search"></i>
                        <input 
                            type="text" 
                            id="languageSearchInput" 
                            placeholder="Search languages..." 
                            autocomplete="off"
                        >
                        <button class="search-clear" id="clearSearchBtn" style="display: none;">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <div class="search-info" id="searchInfo">
                        <span id="resultCount">${this.filteredLanguages.length} languages available</span>
                    </div>
                </div>
                
                <div class="language-modal-body">
                    <div class="language-list" id="languageList">
                        ${this.renderLanguageList()}
                    </div>
                </div>
                
                <div class="language-modal-footer">
                    <button class="btn-secondary" id="cancelBtn">Cancel</button>
                    <button class="btn-primary" id="applyBtn" disabled>
                        <i class="fas fa-check"></i> Apply
                    </button>
                </div>
            </div>
        `;
        
        document.body.appendChild(this.modal);
        this.addStyles();
    }
    
    renderLanguageList() {
        if (this.filteredLanguages.length === 0) {
            return `
                <div class="no-results">
                    <i class="fas fa-search"></i>
                    <p>No languages found matching "${this.searchTerm}"</p>
                    <button class="btn-link" onclick="this.closest('.language-modal').querySelector('#clearSearchBtn').click()">
                        Clear search
                    </button>
                </div>
            `;
        }
        
        return this.filteredLanguages.map(lang => {
            const isSelected = lang.language_code === this.currentLanguage;
            const isAISupported = lang.is_ai_supported ? '<span class="ai-badge" title="AI Translation Available"><i class="fas fa-robot"></i></span>' : '';
            
            return `
                <div class="language-item ${isSelected ? 'selected' : ''}" 
                     data-lang-code="${lang.language_code}"
                     role="button"
                     tabindex="0">
                    <div class="language-item-content">
                        <div class="language-flag">${lang.flag_emoji || '🌐'}</div>
                        <div class="language-info">
                            <div class="language-name">${lang.language_name}</div>
                            ${lang.native_name && lang.native_name !== lang.language_name ? 
                                `<div class="language-native">${lang.native_name}</div>` : ''}
                        </div>
                        ${isAISupported}
                        ${isSelected ? '<i class="fas fa-check-circle selected-icon"></i>' : ''}
                    </div>
                </div>
            `;
        }).join('');
    }
    
    attachEventListeners() {
        const overlay = this.modal.querySelector('.language-modal-overlay');
        const closeBtn = this.modal.querySelector('.language-modal-close');
        const cancelBtn = this.modal.querySelector('#cancelBtn');
        const applyBtn = this.modal.querySelector('#applyBtn');
        const searchInput = this.modal.querySelector('#languageSearchInput');
        const clearBtn = this.modal.querySelector('#clearSearchBtn');
        const languageList = this.modal.querySelector('#languageList');
        
        // Close modal
        [overlay, closeBtn, cancelBtn].forEach(el => {
            el.addEventListener('click', () => this.close());
        });
        
        // Search functionality
        searchInput.addEventListener('input', (e) => {
            this.searchTerm = e.target.value.toLowerCase().trim();
            this.filterLanguages();
            clearBtn.style.display = this.searchTerm ? 'block' : 'none';
        });
        
        clearBtn.addEventListener('click', () => {
            searchInput.value = '';
            this.searchTerm = '';
            this.filterLanguages();
            clearBtn.style.display = 'none';
            searchInput.focus();
        });
        
        // Keyboard shortcuts
        searchInput.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                this.close();
            } else if (e.key === 'Enter' && this.selectedLanguage) {
                this.applyLanguage();
            }
        });
        
        // Language selection
        languageList.addEventListener('click', (e) => {
            const item = e.target.closest('.language-item');
            if (item) {
                this.selectLanguage(item.dataset.langCode);
            }
        });
        
        // Keyboard navigation
        languageList.addEventListener('keydown', (e) => {
            const items = Array.from(languageList.querySelectorAll('.language-item'));
            const currentIndex = items.indexOf(e.target.closest('.language-item'));
            
            if (e.key === 'ArrowDown') {
                e.preventDefault();
                const next = items[currentIndex + 1];
                if (next) next.focus();
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                const prev = items[currentIndex - 1];
                if (prev) prev.focus();
            } else if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                const item = e.target.closest('.language-item');
                if (item) {
                    this.selectLanguage(item.dataset.langCode);
                    this.applyLanguage();
                }
            }
        });
        
        // Apply button
        applyBtn.addEventListener('click', () => this.applyLanguage());
        
        // Focus search on open
        setTimeout(() => searchInput.focus(), 100);
    }
    
    filterLanguages() {
        if (!this.searchTerm) {
            this.filteredLanguages = [...this.languages];
        } else {
            this.filteredLanguages = this.languages.filter(lang => {
                const name = lang.language_name.toLowerCase();
                const native = (lang.native_name || '').toLowerCase();
                const code = lang.language_code.toLowerCase();
                const search = this.searchTerm;
                
                return name.includes(search) || 
                       native.includes(search) || 
                       code.includes(search);
            });
        }
        
        this.updateLanguageList();
    }
    
    selectLanguage(langCode) {
        this.selectedLanguage = langCode;
        
        // Update UI
        const items = this.modal.querySelectorAll('.language-item');
        items.forEach(item => {
            if (item.dataset.langCode === langCode) {
                item.classList.add('selected');
            } else {
                item.classList.remove('selected');
            }
        });
        
        // Enable apply button
        const applyBtn = this.modal.querySelector('#applyBtn');
        applyBtn.disabled = false;
    }
    
    async applyLanguage() {
        if (!this.selectedLanguage) return;
        
        const applyBtn = this.modal.querySelector('#applyBtn');
        applyBtn.disabled = true;
        applyBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Applying...';
        
        try {
            // Save to language manager if available
            if (window.languageManager) {
                await window.languageManager.setLanguage(this.selectedLanguage);
            } else {
                // Fallback: save directly
                localStorage.setItem('preferredLanguage', this.selectedLanguage);
                localStorage.setItem('user_language_set', 'true');
                
                // Save to server
                try {
                    const apiPath = this.getApiPath('api/user-language-preference.php');
                    await fetch(apiPath, {
                        method: 'POST',
                        headers: {'Content-Type': 'application/json'},
                        body: JSON.stringify({language: this.selectedLanguage})
                    });
                } catch (e) {
                    console.error('Error saving language preference:', e);
                }
            }
            
            // Apply translations immediately
            if (typeof applyTranslations === 'function') {
                applyTranslations();
            }
            
            // Trigger language change event
            document.dispatchEvent(new CustomEvent('languageChanged', {
                detail: {language: this.selectedLanguage}
            }));
            
            // Show success message
            this.showSuccessMessage();
            
            // Close modal after short delay
            setTimeout(() => this.close(), 500);
            
        } catch (error) {
            console.error('Error applying language:', error);
            applyBtn.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Error';
            setTimeout(() => {
                applyBtn.disabled = false;
                applyBtn.innerHTML = '<i class="fas fa-check"></i> Apply';
            }, 2000);
        }
    }
    
    showSuccessMessage() {
        const successMsg = document.createElement('div');
        successMsg.className = 'language-success-message';
        successMsg.innerHTML = `
            <i class="fas fa-check-circle"></i>
            <span>Language changed successfully!</span>
        `;
        document.body.appendChild(successMsg);
        
        setTimeout(() => {
            successMsg.classList.add('show');
        }, 10);
        
        setTimeout(() => {
            successMsg.classList.remove('show');
            setTimeout(() => successMsg.remove(), 300);
        }, 2000);
    }
    
    updateLanguageList() {
        const languageList = this.modal.querySelector('#languageList');
        const resultCount = this.modal.querySelector('#resultCount');
        
        languageList.innerHTML = this.renderLanguageList();
        resultCount.textContent = `${this.filteredLanguages.length} language${this.filteredLanguages.length !== 1 ? 's' : ''} available`;
        
        // Re-attach click handlers
        languageList.querySelectorAll('.language-item').forEach(item => {
            item.addEventListener('click', () => {
                this.selectLanguage(item.dataset.langCode);
            });
        });
    }
    
    async open() {
        if (!this.modal) {
            this.createModal();
            this.attachEventListeners();
        }
        
        // Always refresh languages from admin-managed database when opening
        await this.loadLanguages(true);
        
        this.modal.classList.add('show');
        document.body.style.overflow = 'hidden';
        
        // Update the language list display
        this.filterLanguages();
    }
    
    close() {
        if (this.modal) {
            this.modal.classList.remove('show');
            document.body.style.overflow = '';
            
            // Reset selection
            this.selectedLanguage = null;
            const applyBtn = this.modal.querySelector('#applyBtn');
            if (applyBtn) applyBtn.disabled = true;
        }
    }
    
    getFallbackLanguages() {
        return [
            {language_code: 'en', language_name: 'English', native_name: 'English', flag_emoji: '🇺🇸', is_active: 1, is_ai_supported: 1},
            {language_code: 'fil', language_name: 'Filipino', native_name: 'Filipino', flag_emoji: '🇵🇭', is_active: 1, is_ai_supported: 1},
            {language_code: 'es', language_name: 'Spanish', native_name: 'Español', flag_emoji: '🇪🇸', is_active: 1, is_ai_supported: 1}
        ];
    }
    
    /**
     * Get correct API path based on current page context
     */
    getApiPath(relativePath) {
        // Check if we're in root context (index.php) or USERS folder
        const currentPath = window.location.pathname;
        const currentUrl = window.location.href;
        
        // Check if we're accessing from root (index.php) or from USERS folder
        const isRootContext = currentPath === '/index.php' || 
                              currentPath === '/EMERGENCY-COM/index.php' ||
                              currentPath.endsWith('/index.php') && !currentPath.includes('/USERS/');
        
        // Also check if URL contains /USERS/ in path
        const isUsersContext = currentPath.includes('/USERS/') || currentUrl.includes('/USERS/');
        
        if (isRootContext && !isUsersContext) {
            // From root, API is at USERS/api/
            if (relativePath.startsWith('api/')) {
                return 'USERS/' + relativePath;
            }
            return relativePath;
        } else {
            // From USERS folder, API is at api/ (relative)
            if (relativePath.startsWith('USERS/api/')) {
                return relativePath.replace('USERS/', '');
            }
            return relativePath;
        }
    }
    
    addStyles() {
        if (document.getElementById('languageSelectorModalStyles')) return;
        
        const style = document.createElement('style');
        style.id = 'languageSelectorModalStyles';
        style.textContent = `
            .language-modal {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                z-index: 10000;
                display: flex;
                align-items: center;
                justify-content: center;
                opacity: 0;
                visibility: hidden;
                transition: opacity 0.3s ease, visibility 0.3s ease;
            }
            
            .language-modal.show {
                opacity: 1;
                visibility: visible;
            }
            
            .language-modal-overlay {
                position: absolute;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.5);
                backdrop-filter: blur(4px);
            }
            
            .language-modal-content {
                position: relative;
                background: white;
                border-radius: 16px;
                width: 90%;
                max-width: 600px;
                max-height: 90vh;
                display: flex;
                flex-direction: column;
                box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
                transform: scale(0.9);
                transition: transform 0.3s ease;
            }
            
            .language-modal.show .language-modal-content {
                transform: scale(1);
            }
            
            .language-modal-header {
                display: flex;
                align-items: center;
                justify-content: space-between;
                padding: 24px;
                border-bottom: 1px solid #e0e0e0;
            }
            
            .language-modal-header h2 {
                margin: 0;
                font-size: 24px;
                font-weight: 600;
                color: #333;
                display: flex;
                align-items: center;
                gap: 12px;
            }
            
            .language-modal-header h2 i {
                color: #2196f3;
            }
            
            .language-modal-close {
                background: none;
                border: none;
                font-size: 24px;
                color: #666;
                cursor: pointer;
                padding: 8px;
                border-radius: 8px;
                transition: background 0.2s;
            }
            
            .language-modal-close:hover {
                background: #f0f0f0;
            }
            
            .language-modal-search {
                padding: 20px 24px;
                border-bottom: 1px solid #e0e0e0;
            }
            
            .search-input-wrapper {
                position: relative;
                display: flex;
                align-items: center;
            }
            
            .search-input-wrapper i {
                position: absolute;
                left: 16px;
                color: #999;
            }
            
            .search-input-wrapper input {
                width: 100%;
                padding: 12px 16px 12px 48px;
                border: 2px solid #e0e0e0;
                border-radius: 12px;
                font-size: 16px;
                transition: border-color 0.2s;
            }
            
            .search-input-wrapper input:focus {
                outline: none;
                border-color: #2196f3;
            }
            
            .search-clear {
                position: absolute;
                right: 12px;
                background: none;
                border: none;
                color: #999;
                cursor: pointer;
                padding: 4px;
                border-radius: 4px;
            }
            
            .search-clear:hover {
                color: #333;
                background: #f0f0f0;
            }
            
            .search-info {
                margin-top: 12px;
                font-size: 14px;
                color: #666;
            }
            
            .language-modal-body {
                flex: 1;
                overflow-y: auto;
                padding: 8px;
            }
            
            .language-list {
                display: flex;
                flex-direction: column;
                gap: 4px;
            }
            
            .language-item {
                padding: 16px;
                border-radius: 12px;
                cursor: pointer;
                transition: background 0.2s, transform 0.1s;
                border: 2px solid transparent;
            }
            
            .language-item:hover {
                background: #f5f5f5;
                transform: translateX(4px);
            }
            
            .language-item.selected {
                background: #e3f2fd;
                border-color: #2196f3;
            }
            
            .language-item-content {
                display: flex;
                align-items: center;
                gap: 16px;
            }
            
            .language-flag {
                font-size: 32px;
                width: 48px;
                text-align: center;
            }
            
            .language-info {
                flex: 1;
            }
            
            .language-name {
                font-size: 16px;
                font-weight: 600;
                color: #333;
                margin-bottom: 4px;
            }
            
            .language-native {
                font-size: 14px;
                color: #666;
            }
            
            .ai-badge {
                background: #4caf50;
                color: white;
                padding: 4px 8px;
                border-radius: 6px;
                font-size: 12px;
                display: flex;
                align-items: center;
                gap: 4px;
            }
            
            .selected-icon {
                color: #2196f3;
                font-size: 20px;
            }
            
            .no-results {
                text-align: center;
                padding: 60px 20px;
                color: #999;
            }
            
            .no-results i {
                font-size: 48px;
                margin-bottom: 16px;
                opacity: 0.5;
            }
            
            .language-modal-footer {
                display: flex;
                gap: 12px;
                padding: 20px 24px;
                border-top: 1px solid #e0e0e0;
                justify-content: flex-end;
            }
            
            .btn-primary, .btn-secondary {
                padding: 12px 24px;
                border-radius: 8px;
                font-size: 16px;
                font-weight: 600;
                cursor: pointer;
                border: none;
                transition: all 0.2s;
            }
            
            .btn-primary {
                background: #2196f3;
                color: white;
            }
            
            .btn-primary:hover:not(:disabled) {
                background: #1976d2;
            }
            
            .btn-primary:disabled {
                opacity: 0.5;
                cursor: not-allowed;
            }
            
            .btn-secondary {
                background: #f5f5f5;
                color: #333;
            }
            
            .btn-secondary:hover {
                background: #e0e0e0;
            }
            
            .btn-link {
                background: none;
                border: none;
                color: #2196f3;
                cursor: pointer;
                text-decoration: underline;
                margin-top: 12px;
            }
            
            .language-success-message {
                position: fixed;
                top: 20px;
                right: 20px;
                background: #4caf50;
                color: white;
                padding: 16px 24px;
                border-radius: 12px;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
                display: flex;
                align-items: center;
                gap: 12px;
                z-index: 10001;
                transform: translateX(400px);
                transition: transform 0.3s ease;
            }
            
            .language-success-message.show {
                transform: translateX(0);
            }
            
            @media (max-width: 768px) {
                .language-modal-content {
                    width: 95%;
                    max-height: 95vh;
                }
                
                .language-modal-header {
                    padding: 20px;
                }
                
                .language-modal-header h2 {
                    font-size: 20px;
                }
                
                .language-flag {
                    font-size: 24px;
                    width: 40px;
                }
                
                .language-name {
                    font-size: 14px;
                }
            }
        `;
        
        document.head.appendChild(style);
    }
}

// Initialize global instance
if (typeof window.languageSelectorModal === 'undefined') {
    window.languageSelectorModal = new LanguageSelectorModal();
}

// Export for use in other scripts
if (typeof module !== 'undefined' && module.exports) {
    module.exports = LanguageSelectorModal;
}

