// Translation system for the Emergency Communication Portal
const translations = {
    en: {
        // Navigation
        'nav.home': 'Home',
        'nav.alerts': 'Alerts',
        'nav.profile': 'Profile',
        'nav.support': 'Support',
        'nav.emergency': 'Emergency Call',
        'nav.login': 'Login / Sign Up',
        
        // Home page
        'home.title': 'QUEZON CITY EMERGENCY COMMUNICATION PORTAL',
        'home.mission': 'Mission:',
        'home.mission.text': 'To operationalize an effective, efficient, and inclusive DRRM system dedicated to Resilience-building in Quezon City communities.',
        'home.vision': 'Vision:',
        'home.vision.text': 'A global mode of excellence in Disaster Risk Reduction and Management for its cohesive DRRM system fostering a Sustainable, Future-ready, and Resilient Quezon City.',
        'home.emergency.call': 'Call for Emergency',
        'home.download.title': 'Download Our Mobile App',
        'home.download.desc': 'Get instant emergency alerts and notifications on your mobile device',
        'home.download.comingsoon': 'Coming Soon',
        'home.download.comingsoon.desc': 'Mobile app launching soon',
        'home.download.badge': 'SOON',
        'home.about.title': 'About Us',
        'home.about.text': 'The Quezon City Emergency Communication Portal connects residents, responders, and the local government through reliable, multi-channel emergency alerts and communication tools. Our goal is to help you receive critical information quickly and safely during disasters, incidents, and city-wide emergencies.',
        'home.services.title': 'Services',
        'home.services.mass': 'Mass Notifications',
        'home.services.mass.desc': 'City-wide alerts sent via SMS, email, and online channels for urgent incidents and advisories.',
        'home.services.twoWay': 'Two-Way Communication',
        'home.services.twoWay.desc': 'Residents can report incidents, request assistance, and send updates back to responders.',
        'home.services.automated': 'Automated Hazard Feeds',
        'home.services.automated.desc': 'Integrated updates from agencies such as PAGASA and PHIVOLCS for weather and seismic events.',
        'home.services.multilingual': 'Multilingual Alerts',
        'home.services.multilingual.desc': 'Important messages can be delivered in multiple languages to reach more communities.',
        'home.guide.title': 'Guide: How to Call for Emergency',
        'home.guide.1': 'Stay calm and move to a safe place.',
        'home.guide.2': 'Use the "Call for Emergency" button.',
        'home.guide.3': 'Prepare key details.',
        'home.guide.4': 'Follow instructions.',
        'home.guide.5': 'Keep lines open.',
        
        // Language Modal
        'lang.select': 'Select Language',
        'lang.choose': 'Please choose your preferred language for alerts and content.',
        'lang.search.placeholder': 'Search language...',
        'lang.english': 'English',
        'lang.filipino': 'Filipino',
        
        // Profile
        'profile.title': 'Profile & Preferences',
        'profile.subtitle': 'Manage your contact methods, preferred languages, and alert categories.',
        'profile.settings.title': 'Your Settings',
        'profile.contact.title': 'Contact Channels',
        'profile.contact.desc': 'Update phone, email, and notification channels.',
        'profile.contact.button': 'Manage Channels',
        'profile.alerts.title': 'Alert Preferences',
        'profile.alerts.desc': 'Choose categories: Weather, Earthquake, Bomb Threat, Health, and more.',
        'profile.alerts.button': 'Edit Preferences',
        'profile.language.title': 'Language Settings',
        'profile.language.desc': 'Choose your preferred language. This will be used for alerts and interface text where available.',
        'profile.language.label': 'Preferred Language',
        'profile.language.save': 'Save Language',
        'profile.language.saved': 'Language updated',
        'profile.language.saved.text': 'Your preferred language has been saved.',
        
        // Footer
        'footer.description': 'Building modern web applications with clean code, responsive design, and user-friendly interfaces.',
        'footer.navigation': 'Navigation',
        'footer.resources': 'Resources',
        'footer.resources.docs': 'Documentation',
        'footer.resources.faq': 'FAQ',
        'footer.company': 'Company',
        'footer.company.about': 'About Us',
        'footer.company.privacy': 'Privacy Policy',
        'footer.company.terms': 'Terms of Service',
        'footer.copyright': 'All rights reserved.',
        'footer.legal.cookie': 'Cookie Policy',
        
        // Common
        'common.save': 'Save',
        'common.cancel': 'Cancel',
        'common.close': 'Close',
        'common.loading': 'Loading...',
    },
    fil: {
        // Navigation
        'nav.home': 'Tahanan',
        'nav.alerts': 'Mga Alert',
        'nav.profile': 'Profile',
        'nav.support': 'Suporta',
        'nav.emergency': 'Tawag sa Emergency',
        'nav.login': 'Mag-login / Mag-sign Up',
        
        // Home page
        'home.title': 'QUEZON CITY EMERGENCY COMMUNICATION PORTAL',
        'home.mission': 'Misyon:',
        'home.mission.text': 'Upang mapaandar ang isang epektibo, mahusay, at inclusive na DRRM system na nakatuon sa pagbuo ng Resilience sa mga komunidad ng Quezon City.',
        'home.vision': 'Bisyon:',
        'home.vision.text': 'Isang pandaigdigang modelo ng kahusayan sa Disaster Risk Reduction and Management para sa magkakaugnay na DRRM system na nagtataguyod ng Sustainable, Future-ready, at Resilient na Quezon City.',
        'home.emergency.call': 'Tumawag para sa Emergency',
        'home.download.title': 'I-download ang Aming Mobile App',
        'home.download.desc': 'Kumuha ng instant emergency alerts at notifications sa inyong mobile device',
        'home.download.comingsoon': 'Darating Na',
        'home.download.comingsoon.desc': 'Malapit nang i-launch ang mobile app',
        'home.download.badge': 'DARATING',
        'home.about.title': 'Tungkol sa Amin',
        'home.about.text': 'Ang Quezon City Emergency Communication Portal ay nag-uugnay sa mga residente, responders, at lokal na pamahalaan sa pamamagitan ng maaasahang, multi-channel na emergency alerts at communication tools. Layunin naming tulungan kayong makatanggap ng mahahalagang impormasyon nang mabilis at ligtas sa panahon ng mga kalamidad, insidente, at city-wide na emergencies.',
        'home.services.title': 'Mga Serbisyo',
        'home.services.mass': 'Mass Notifications',
        'home.services.mass.desc': 'City-wide na mga alert na ipinapadala sa pamamagitan ng SMS, email, at online channels para sa mga urgent na insidente at advisories.',
        'home.services.twoWay': 'Two-Way Communication',
        'home.services.twoWay.desc': 'Maaaring mag-ulat ang mga residente ng mga insidente, humingi ng tulong, at magpadala ng updates pabalik sa responders.',
        'home.services.automated': 'Automated Hazard Feeds',
        'home.services.automated.desc': 'Integrated na updates mula sa mga ahensya tulad ng PAGASA at PHIVOLCS para sa weather at seismic events.',
        'home.services.multilingual': 'Multilingual Alerts',
        'home.services.multilingual.desc': 'Mahahalagang mensahe ay maaaring maihatid sa maraming wika upang maabot ang mas maraming komunidad.',
        'home.guide.title': 'Gabay: Paano Tumawag para sa Emergency',
        'home.guide.1': 'Manatiling kalmado at lumipat sa ligtas na lugar.',
        'home.guide.2': 'Gamitin ang "Call for Emergency" button.',
        'home.guide.3': 'Maghanda ng mahahalagang detalye.',
        'home.guide.4': 'Sundin ang mga tagubilin.',
        'home.guide.5': 'Panatilihing bukas ang linya.',
        
        // Language Modal
        'lang.select': 'Pumili ng Wika',
        'lang.choose': 'Mangyaring pumili ng iyong ginustong wika para sa mga alert at content.',
        'lang.search.placeholder': 'Maghanap ng wika...',
        'lang.english': 'English',
        'lang.filipino': 'Filipino',
        
        // Profile
        'profile.title': 'Profile & Preferences',
        'profile.subtitle': 'Pamahalaan ang iyong contact methods, ginustong mga wika, at alert categories.',
        'profile.settings.title': 'Ang Iyong Settings',
        'profile.contact.title': 'Contact Channels',
        'profile.contact.desc': 'I-update ang phone, email, at notification channels.',
        'profile.contact.button': 'Pamahalaan ang Channels',
        'profile.alerts.title': 'Alert Preferences',
        'profile.alerts.desc': 'Pumili ng categories: Weather, Earthquake, Bomb Threat, Health, at marami pa.',
        'profile.alerts.button': 'I-edit ang Preferences',
        'profile.language.title': 'Language Settings',
        'profile.language.desc': 'Pumili ng iyong ginustong wika. Gagamitin ito para sa mga alert at interface text kung saan available.',
        'profile.language.label': 'Ginustong Wika',
        'profile.language.save': 'I-save ang Wika',
        'profile.language.saved': 'Na-update ang wika',
        'profile.language.saved.text': 'Ang iyong ginustong wika ay nai-save na.',
        
        // Footer
        'footer.description': 'Pagbuo ng modernong web applications na may malinis na code, responsive design, at user-friendly interfaces.',
        'footer.navigation': 'Navigation',
        'footer.resources': 'Resources',
        'footer.resources.docs': 'Documentation',
        'footer.resources.faq': 'FAQ',
        'footer.company': 'Company',
        'footer.company.about': 'Tungkol sa Amin',
        'footer.company.privacy': 'Privacy Policy',
        'footer.company.terms': 'Terms of Service',
        'footer.copyright': 'Lahat ng karapatan ay nakalaan.',
        'footer.legal.cookie': 'Cookie Policy',
        
        // Common
        'common.save': 'I-save',
        'common.cancel': 'Kanselahin',
        'common.close': 'Isara',
        'common.loading': 'Naglo-load...',
    }
};

// Language codes mapping - Extended with many languages
const languageCodes = {
    'English': 'en',
    'Filipino': 'fil',
    'Tagalog': 'tl',
    'Cebuano': 'ceb',
    'Ilocano': 'ilo',
    'Kapampangan': 'pam',
    'Bicolano': 'bcl',
    'Waray': 'war',
    'Spanish': 'es',
    'French': 'fr',
    'German': 'de',
    'Italian': 'it',
    'Portuguese': 'pt',
    'Chinese': 'zh',
    'Japanese': 'ja',
    'Korean': 'ko',
    'Arabic': 'ar',
    'Hindi': 'hi',
    'Thai': 'th',
    'Vietnamese': 'vi',
    'Indonesian': 'id',
    'Malay': 'ms',
    'Russian': 'ru',
    'Turkish': 'tr'
};

// Language display names with flags
const languageDisplayNames = {
    'en': '🇺🇸 English',
    'fil': '🇵🇭 Filipino',
    'tl': '🇵🇭 Tagalog',
    'ceb': '🇵🇭 Cebuano',
    'ilo': '🇵🇭 Ilocano',
    'pam': '🇵🇭 Kapampangan',
    'bcl': '🇵🇭 Bicolano',
    'war': '🇵🇭 Waray',
    'es': '🇪🇸 Spanish',
    'fr': '🇫🇷 French',
    'de': '🇩🇪 German',
    'it': '🇮🇹 Italian',
    'pt': '🇵🇹 Portuguese',
    'zh': '🇨🇳 Chinese',
    'ja': '🇯🇵 Japanese',
    'ko': '🇰🇷 Korean',
    'ar': '🇸🇦 Arabic',
    'hi': '🇮🇳 Hindi',
    'th': '🇹🇭 Thai',
    'vi': '🇻🇳 Vietnamese',
    'id': '🇮🇩 Indonesian',
    'ms': '🇲🇾 Malay',
    'ru': '🇷🇺 Russian',
    'tr': '🇹🇷 Turkish'
};

// Get current language
function getCurrentLanguage() {
    return localStorage.getItem('preferredLanguage') || 'en';
}

// Export for global access
window.getCurrentLanguage = getCurrentLanguage;

// Set language
function setLanguage(code) {
    localStorage.setItem('preferredLanguage', code);
    localStorage.setItem('user_language_set', 'true');
    document.documentElement.setAttribute('data-lang', code);
    document.documentElement.setAttribute('lang', code);
    applyTranslations();
}

// Export for global access
window.setLanguage = setLanguage;

// Apply translations to the page
async function applyTranslations() {
    const lang = getCurrentLanguage();
    let translation = translations[lang];
    
    // If language is not in static translations, fetch from API
    if (!translation && lang !== 'en') {
        console.log(`🔄 Language ${lang} not in static translations, fetching from API...`);
        showTranslationLoading(true);
        
        try {
            const apiPath = getApiPath(`api/get-translations.php?lang=${encodeURIComponent(lang)}`);
            const response = await fetch(apiPath, {
                cache: 'no-cache',
                headers: {
                    'Cache-Control': 'no-cache'
                }
            });
            
            if (response.ok) {
                const data = await response.json();
                if (data.success && data.translations) {
                    // Store fetched translations
                    translations[lang] = data.translations;
                    translation = data.translations;
                    console.log(`✓ Loaded ${Object.keys(translation).length} translations for ${lang}`);
                    
                    if (data.auto_translated) {
                        console.log(`ℹ️ Translations were auto-generated using AI`);
                    }
                } else {
                    console.warn(`⚠️ API returned no translations for ${lang}, using English`);
                    translation = translations.en;
                }
            } else {
                console.error(`✗ Failed to fetch translations: ${response.status}`);
                translation = translations.en;
            }
        } catch (error) {
            console.error(`✗ Error fetching translations:`, error);
            translation = translations.en;
        } finally {
            showTranslationLoading(false);
        }
    }
    
    // Fallback to English if still no translation
    if (!translation) {
        translation = translations.en;
    }
    
    // Find all elements with data-translate attribute
    document.querySelectorAll('[data-translate]').forEach(element => {
        const key = element.getAttribute('data-translate');
        if (translation[key]) {
            // Store original text if not already stored
            if (!element.hasAttribute('data-original-text')) {
                element.setAttribute('data-original-text', element.textContent);
            }
            element.textContent = translation[key];
        }
    });
    
    // Find all elements with data-translate-html attribute (for HTML content)
    document.querySelectorAll('[data-translate-html]').forEach(element => {
        const key = element.getAttribute('data-translate-html');
        if (translation[key]) {
            if (!element.hasAttribute('data-original-html')) {
                element.setAttribute('data-original-html', element.innerHTML);
            }
            element.innerHTML = translation[key];
        }
    });
    
    // Find all elements with data-translate-placeholder attribute
    document.querySelectorAll('[data-translate-placeholder]').forEach(element => {
        const key = element.getAttribute('data-translate-placeholder');
        if (translation[key]) {
            if (!element.hasAttribute('data-original-placeholder')) {
                element.setAttribute('data-original-placeholder', element.placeholder);
            }
            element.placeholder = translation[key];
        }
    });
    
    // Update page title if needed
    const titleKey = document.documentElement.getAttribute('data-title-key');
    if (titleKey && translation[titleKey]) {
        document.title = translation[titleKey];
    }
    
    console.log(`✓ Translations applied for language: ${lang}`);
}

// Export for global access
window.applyTranslations = applyTranslations;

// Initialize translations on page load
document.addEventListener('DOMContentLoaded', function() {
    console.log('🌍 Translation system initializing...');
    const currentLang = getCurrentLanguage();
    console.log(`Current language: ${currentLang}`);
    document.documentElement.setAttribute('data-lang', currentLang);
    document.documentElement.setAttribute('lang', currentLang);
    
    // Apply translations immediately
    applyTranslations();
    
    // Also apply after a short delay to catch dynamically loaded content
    setTimeout(() => {
        applyTranslations();
    }, 500);
});

// Listen for language changes and apply translations
document.addEventListener('languageChanged', function(event) {
    console.log('🔄 Language changed event received');
    const lang = event.detail?.language || getCurrentLanguage();
    console.log(`Switching to language: ${lang}`);
    localStorage.setItem('preferredLanguage', lang);
    localStorage.setItem('user_language_set', 'true');
    
    // Update HTML attributes
    document.documentElement.setAttribute('lang', lang);
    document.documentElement.setAttribute('data-lang', lang);
    
    // Apply translations
    applyTranslations();
});

// Also listen for languagesUpdated event
document.addEventListener('languagesUpdated', function() {
    console.log('📋 Languages list updated');
    // Refresh translations if needed
    applyTranslations();
});

/**
 * Get correct API path based on current page context
 */
function getApiPath(relativePath) {
    const currentPath = window.location.pathname;
    const isRootContext = currentPath === '/index.php' || 
                          currentPath === '/EMERGENCY-COM/index.php' ||
                          (currentPath.endsWith('/index.php') && !currentPath.includes('/USERS/'));
    const isUsersContext = currentPath.includes('/USERS/');
    
    if (isRootContext && !isUsersContext) {
        if (relativePath.startsWith('api/')) {
            return 'USERS/' + relativePath;
        }
        return relativePath;
    } else {
        if (relativePath.startsWith('USERS/api/')) {
            return relativePath.replace('USERS/', '');
        }
        return relativePath;
    }
}

/**
 * Show/hide translation loading indicator
 */
function showTranslationLoading(show) {
    let indicator = document.getElementById('translationLoadingIndicator');
    
    if (show) {
        if (!indicator) {
            indicator = document.createElement('div');
            indicator.id = 'translationLoadingIndicator';
            indicator.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: linear-gradient(135deg, #4c8a89 0%, #5ba3a2 100%);
                color: white;
                padding: 12px 20px;
                border-radius: 8px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                z-index: 10000;
                display: flex;
                align-items: center;
                gap: 10px;
                font-family: Arial, sans-serif;
                font-size: 14px;
                animation: slideInRight 0.3s ease;
            `;
            indicator.innerHTML = `
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10" opacity="0.3"/>
                    <path d="M12 2 A10 10 0 0 1 22 12" stroke-linecap="round">
                        <animateTransform attributeName="transform" type="rotate" from="0 12 12" to="360 12 12" dur="1s" repeatCount="indefinite"/>
                    </path>
                </svg>
                <span>Translating...</span>
            `;
            document.body.appendChild(indicator);
            
            // Add animation keyframes
            if (!document.getElementById('translationAnimations')) {
                const style = document.createElement('style');
                style.id = 'translationAnimations';
                style.textContent = `
                    @keyframes slideInRight {
                        from {
                            opacity: 0;
                            transform: translateX(100px);
                        }
                        to {
                            opacity: 1;
                            transform: translateX(0);
                        }
                    }
                    @keyframes slideOutRight {
                        from {
                            opacity: 1;
                            transform: translateX(0);
                        }
                        to {
                            opacity: 0;
                            transform: translateX(100px);
                        }
                    }
                `;
                document.head.appendChild(style);
            }
        }
        indicator.style.display = 'flex';
    } else {
        if (indicator) {
            indicator.style.animation = 'slideOutRight 0.3s ease';
            setTimeout(() => {
                if (indicator.parentElement) {
                    indicator.remove();
                }
            }, 300);
        }
    }
}

// Debug helper - expose translation function globally
window.debugTranslations = function() {
    console.log('Current language:', getCurrentLanguage());
    console.log('Available translations:', Object.keys(translations));
    console.log('Elements with data-translate:', document.querySelectorAll('[data-translate]').length);
    applyTranslations();
};

