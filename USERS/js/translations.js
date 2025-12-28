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

// Set language
function setLanguage(code) {
    localStorage.setItem('preferredLanguage', code);
    document.documentElement.setAttribute('data-lang', code);
    applyTranslations();
}

// Apply translations to the page
function applyTranslations() {
    const lang = getCurrentLanguage();
    const translation = translations[lang] || translations.en;
    
    // Find all elements with data-translate attribute
    document.querySelectorAll('[data-translate]').forEach(element => {
        const key = element.getAttribute('data-translate');
        if (translation[key]) {
            element.textContent = translation[key];
        }
    });
    
    // Find all elements with data-translate-html attribute (for HTML content)
    document.querySelectorAll('[data-translate-html]').forEach(element => {
        const key = element.getAttribute('data-translate-html');
        if (translation[key]) {
            element.innerHTML = translation[key];
        }
    });
    
    // Find all elements with data-translate-placeholder attribute
    document.querySelectorAll('[data-translate-placeholder]').forEach(element => {
        const key = element.getAttribute('data-translate-placeholder');
        if (translation[key]) {
            element.placeholder = translation[key];
        }
    });
    
    // Update page title if needed
    const titleKey = document.documentElement.getAttribute('data-title-key');
    if (titleKey && translation[titleKey]) {
        document.title = translation[titleKey];
    }
}

// Initialize translations on page load
document.addEventListener('DOMContentLoaded', function() {
    const currentLang = getCurrentLanguage();
    document.documentElement.setAttribute('data-lang', currentLang);
    applyTranslations();
});

