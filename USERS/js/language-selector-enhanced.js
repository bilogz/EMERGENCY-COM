/**
 * Enhanced Language Selector Component
 * Real-time updates, device detection, and settings integration
 */

class LanguageSelectorEnhanced {
    constructor() {
        this.selectorElement = null;
        this.isOpen = false;
        this.init();
    }
    
    async init() {
        // Wait for language manager to initialize
        if (typeof window.languageManager === 'undefined') {
            await new Promise(resolve => {
                const checkInterval = setInterval(() => {
                    if (typeof window.languageManager !== 'undefined') {
                        clearInterval(checkInterval);
                        resolve();
                    }
                }, 100);
            });
        }
        
        this.createSelector();
        this.attachEventListeners();
        this.listenForUpdates();
    }
    
    createSelector() {
        const langManager = window.languageManager;
        const currentLang = langManager.currentLanguage;
        const langInfo = langManager.getLanguageInfo(currentLang);
        
        const selectorHTML = `
            <div id="languageSelectorEnhanced" class="language-selector-enhanced">
                <button class="language-selector-btn" id="languageBtnEnhanced" aria-label="Select Language" title="Change Language">
                    <i class="fas fa-globe"></i>
                    <span id="currentLangDisplayEnhanced">${langManager.getLanguageDisplay(currentLang)}</span>
                    <i class="fas fa-chevron-down"></i>
                </button>
                <div class="language-dropdown-enhanced" id="languageDropdownEnhanced" style="display: none;">
                    <div class="language-dropdown-header">
                        <h3><i class="fas fa-language"></i> Select Language</h3>
                        ${langManager.deviceLanguage && langManager.deviceLanguage !== currentLang ? `
                            <div class="device-language-hint">
                                <i class="fas fa-info-circle"></i>
                                Your device language (${langManager.getLanguageDisplay(langManager.deviceLanguage)}) detected
                            </div>
                        ` : ''}
                    </div>
                    <div class="language-search-enhanced">
                        <input type="text" id="languageSearchEnhanced" placeholder="Search language..." autocomplete="off">
                        <i class="fas fa-search"></i>
                    </div>
                    <div class="language-list-enhanced" id="languageListEnhanced">
                        ${this.generateLanguageList()}
                    </div>
                    <div class="language-dropdown-footer">
                        <a href="profile.php" class="language-settings-link">
                            <i class="fas fa-cog"></i> Language Settings
                        </a>
                    </div>
                </div>
            </div>
        `;
        
        // Create or update container
        let container = document.getElementById('languageSelectorContainerEnhanced');
        if (!container) {
            container = document.createElement('div');
            container.id = 'languageSelectorContainerEnhanced';
            container.style.cssText = 'position: fixed; top: 20px; right: 20px; z-index: 1000;';
            document.body.appendChild(container);
        }
        
        container.innerHTML = selectorHTML;
        this.selectorElement = container;
        this.addStyles();
    }
    
    generateLanguageList() {
        const langManager = window.languageManager;
        const currentLang = langManager.currentLanguage;
        
        return langManager.supportedLanguages.map(lang => {
            const isSelected = lang.language_code === currentLang ? 'selected' : '';
            const isDeviceLang = lang.language_code === langManager.deviceLanguage ? 'device-language' : '';
            const flag = lang.flag_emoji || '🌐';
            
            return `
                <div class="language-item-enhanced ${isSelected} ${isDeviceLang}" data-lang="${lang.language_code}">
                    <span class="language-flag">${flag}</span>
                    <div class="language-info">
                        <span class="language-name">${lang.language_name}</span>
                        ${lang.native_name && lang.native_name !== lang.language_name ? 
                            `<span class="language-native">${lang.native_name}</span>` : ''}
                    </div>
                    ${isSelected ? '<i class="fas fa-check"></i>' : ''}
                    ${isDeviceLang && !isSelected ? '<span class="device-badge">Device</span>' : ''}
                </div>
            `;
        }).join('');
    }
    
    attachEventListeners() {
        const btn = document.getElementById('languageBtnEnhanced');
        const dropdown = document.getElementById('languageDropdownEnhanced');
        const searchInput = document.getElementById('languageSearchEnhanced');
        
        // Toggle dropdown
        if (btn) {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                this.toggleDropdown();
            });
        }
        
        // Close dropdown when clicking outside
        document.addEventListener('click', (e) => {
            if (!e.target.closest('#languageSelectorEnhanced')) {
                this.closeDropdown();
            }
        });
        
        // Search functionality
        if (searchInput) {
            searchInput.addEventListener('input', (e) => {
                this.filterLanguages(e.target.value);
            });
        }
        
        // Language selection
        document.querySelectorAll('.language-item-enhanced').forEach(item => {
            item.addEventListener('click', () => {
                const langCode = item.dataset.lang;
                this.selectLanguage(langCode);
            });
        });
    }
    
    async selectLanguage(languageCode) {
        const langManager = window.languageManager;
        await langManager.setLanguage(languageCode);
        
        // Update UI
        this.updateDisplay();
        this.closeDropdown();
        
        // Show success message
        this.showSuccessMessage(languageCode);
    }
    
    toggleDropdown() {
        const dropdown = document.getElementById('languageDropdownEnhanced');
        if (!dropdown) return;
        
        this.isOpen = !this.isOpen;
        dropdown.style.display = this.isOpen ? 'block' : 'none';
        
        if (this.isOpen) {
            const searchInput = document.getElementById('languageSearchEnhanced');
            if (searchInput) {
                setTimeout(() => searchInput.focus(), 100);
            }
        }
    }
    
    closeDropdown() {
        const dropdown = document.getElementById('languageDropdownEnhanced');
        if (dropdown) {
            dropdown.style.display = 'none';
            this.isOpen = false;
        }
    }
    
    filterLanguages(searchTerm) {
        const items = document.querySelectorAll('.language-item-enhanced');
        const term = searchTerm.toLowerCase();
        
        items.forEach(item => {
            const text = item.textContent.toLowerCase();
            item.style.display = text.includes(term) ? 'flex' : 'none';
        });
    }
    
    updateDisplay() {
        const langManager = window.languageManager;
        const currentLang = langManager.currentLanguage;
        const display = document.getElementById('currentLangDisplayEnhanced');
        
        if (display) {
            display.textContent = langManager.getLanguageDisplay(currentLang);
        }
        
        // Update selected state
        document.querySelectorAll('.language-item-enhanced').forEach(item => {
            item.classList.remove('selected');
            if (item.dataset.lang === currentLang) {
                item.classList.add('selected');
            }
        });
    }
    
    listenForUpdates() {
        // Listen for language updates from language manager
        document.addEventListener('languagesUpdated', (event) => {
            this.refreshLanguageList();
        });
        
        // Listen for language changes
        document.addEventListener('languageChanged', (event) => {
            this.updateDisplay();
        });
    }
    
    refreshLanguageList() {
        const list = document.getElementById('languageListEnhanced');
        if (list) {
            list.innerHTML = this.generateLanguageList();
            // Reattach event listeners
            document.querySelectorAll('.language-item-enhanced').forEach(item => {
                item.addEventListener('click', () => {
                    const langCode = item.dataset.lang;
                    this.selectLanguage(langCode);
                });
            });
        }
    }
    
    showSuccessMessage(languageCode) {
        const langManager = window.languageManager;
        const langInfo = langManager.getLanguageInfo(languageCode);
        
        // Create temporary success message
        const message = document.createElement('div');
        message.style.cssText = `
            position: fixed;
            top: 80px;
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
        `;
        message.innerHTML = `
            <i class="fas fa-check-circle"></i>
            <span>Language changed to ${langInfo ? langInfo.language_name : languageCode}</span>
        `;
        document.body.appendChild(message);
        
        setTimeout(() => {
            message.style.animation = 'slideOut 0.3s ease';
            setTimeout(() => message.remove(), 300);
        }, 2000);
    }
    
    addStyles() {
        if (document.getElementById('languageSelectorEnhancedStyles')) return;
        
        const style = document.createElement('style');
        style.id = 'languageSelectorEnhancedStyles';
        style.textContent = `
            @keyframes slideIn {
                from { transform: translateX(100%); opacity: 0; }
                to { transform: translateX(0); opacity: 1; }
            }
            @keyframes slideOut {
                from { transform: translateX(0); opacity: 1; }
                to { transform: translateX(100%); opacity: 0; }
            }
            
            .language-selector-enhanced {
                position: relative;
                display: inline-block;
            }
            
            .language-selector-btn {
                display: flex;
                align-items: center;
                gap: 8px;
                padding: 10px 16px;
                background: #fff;
                border: 2px solid #e0e0e0;
                border-radius: 10px;
                cursor: pointer;
                font-size: 14px;
                font-weight: 500;
                transition: all 0.3s ease;
                box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            }
            
            .language-selector-btn:hover {
                background: #f5f5f5;
                border-color: #2196f3;
                box-shadow: 0 4px 12px rgba(33,150,243,0.2);
                transform: translateY(-2px);
            }
            
            .language-selector-btn i.fa-globe {
                color: #2196f3;
                font-size: 16px;
            }
            
            .language-dropdown-enhanced {
                position: absolute;
                top: calc(100% + 10px);
                right: 0;
                background: #fff;
                border: 2px solid #e0e0e0;
                border-radius: 12px;
                box-shadow: 0 8px 24px rgba(0,0,0,0.15);
                min-width: 320px;
                max-width: 400px;
                max-height: 500px;
                overflow: hidden;
                z-index: 1001;
                animation: slideIn 0.2s ease;
            }
            
            .language-dropdown-header {
                padding: 16px;
                border-bottom: 1px solid #e0e0e0;
                background: #f8f9fa;
            }
            
            .language-dropdown-header h3 {
                margin: 0 0 8px 0;
                font-size: 16px;
                color: #333;
                display: flex;
                align-items: center;
                gap: 8px;
            }
            
            .device-language-hint {
                font-size: 12px;
                color: #666;
                display: flex;
                align-items: center;
                gap: 6px;
                margin-top: 8px;
            }
            
            .device-language-hint i {
                color: #2196f3;
            }
            
            .language-search-enhanced {
                position: relative;
                padding: 12px;
                border-bottom: 1px solid #e0e0e0;
            }
            
            .language-search-enhanced input {
                width: 100%;
                padding: 10px 36px 10px 12px;
                border: 2px solid #e0e0e0;
                border-radius: 8px;
                font-size: 14px;
                transition: border-color 0.2s ease;
            }
            
            .language-search-enhanced input:focus {
                outline: none;
                border-color: #2196f3;
                box-shadow: 0 0 0 3px rgba(33,150,243,0.1);
            }
            
            .language-search-enhanced i {
                position: absolute;
                right: 24px;
                top: 50%;
                transform: translateY(-50%);
                color: #999;
            }
            
            .language-list-enhanced {
                max-height: 350px;
                overflow-y: auto;
                padding: 8px 0;
            }
            
            .language-item-enhanced {
                display: flex;
                align-items: center;
                gap: 12px;
                padding: 12px 16px;
                cursor: pointer;
                transition: background 0.2s ease;
                border-bottom: 1px solid #f5f5f5;
                position: relative;
            }
            
            .language-item-enhanced:hover {
                background: #f5f5f5;
            }
            
            .language-item-enhanced.selected {
                background: #e3f2fd;
                font-weight: 600;
            }
            
            .language-item-enhanced.selected .language-name {
                color: #2196f3;
            }
            
            .language-item-enhanced.device-language {
                border-left: 3px solid #4caf50;
            }
            
            .language-flag {
                font-size: 24px;
                width: 32px;
                text-align: center;
                flex-shrink: 0;
            }
            
            .language-info {
                flex: 1;
                display: flex;
                flex-direction: column;
                gap: 2px;
            }
            
            .language-name {
                font-size: 14px;
                color: #333;
            }
            
            .language-native {
                font-size: 12px;
                color: #666;
                font-style: italic;
            }
            
            .language-item-enhanced i.fa-check {
                color: #2196f3;
                margin-left: auto;
                font-size: 16px;
            }
            
            .device-badge {
                background: #4caf50;
                color: white;
                font-size: 10px;
                padding: 2px 6px;
                border-radius: 4px;
                margin-left: auto;
                font-weight: 600;
            }
            
            .language-dropdown-footer {
                padding: 12px 16px;
                border-top: 1px solid #e0e0e0;
                background: #f8f9fa;
            }
            
            .language-settings-link {
                display: flex;
                align-items: center;
                gap: 8px;
                color: #2196f3;
                text-decoration: none;
                font-size: 13px;
                transition: color 0.2s ease;
            }
            
            .language-settings-link:hover {
                color: #1976d2;
                text-decoration: underline;
            }
            
            @media (max-width: 768px) {
                #languageSelectorContainerEnhanced {
                    top: 10px !important;
                    right: 10px !important;
                }
                
                .language-selector-btn {
                    padding: 8px 12px;
                    font-size: 12px;
                }
                
                .language-dropdown-enhanced {
                    min-width: 280px;
                    max-width: 320px;
                    right: -10px;
                }
            }
        `;
        document.head.appendChild(style);
    }
}

// Initialize on page load
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        window.languageSelectorEnhanced = new LanguageSelectorEnhanced();
    });
} else {
    window.languageSelectorEnhanced = new LanguageSelectorEnhanced();
}

