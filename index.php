<?php
// Dedicated Home page for the user portal
$assetBase = 'ADMIN/header/';

// Include centralized session configuration
require_once __DIR__ . '/session-config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Home</title>
    <link rel="icon" type="image/x-icon" href="<?= $assetBase ?>images/favicon.ico">
    <link rel="stylesheet" href="<?= $assetBase ?>css/global.css">
    <link rel="stylesheet" href="<?= $assetBase ?>css/buttons.css">
    <link rel="stylesheet" href="<?= $assetBase ?>css/hero.css">
    <link rel="stylesheet" href="ADMIN/sidebar/css/global.css">
    <link rel="stylesheet" href="ADMIN/sidebar/css/sidebar.css">
    <link rel="stylesheet" href="ADMIN/sidebar/css/content.css">
    <link rel="stylesheet" href="ADMIN/sidebar/css/admin-header.css">
    <link rel="stylesheet" href="ADMIN/sidebar/css/buttons.css">
    <link rel="stylesheet" href="USERS/css/user.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script>
        // Set global API base path for all JS files
        window.API_BASE_PATH = 'USERS/api/';
        window.IS_ROOT_CONTEXT = true;
    </script>
    <script src="USERS/js/translations.js"></script>
    <script src="USERS/js/language-manager.js"></script>
    <script src="USERS/js/language-selector-modal.js"></script>
    <script src="USERS/js/language-sync.js"></script>
    <script src="USERS/js/global-translator.js"></script>
    <script>
        // Ensure sidebar functions are available before translation scripts interfere
        // This runs immediately, before DOMContentLoaded
        (function() {
            if (typeof window.sidebarToggle !== 'function') {
                window.sidebarToggle = function() {
                    const sidebar = document.getElementById('sidebar');
                    const sidebarOverlay = document.getElementById('sidebarOverlay');
                    if (sidebar) {
                        sidebar.classList.toggle('sidebar-open');
                        if (sidebarOverlay) {
                            sidebarOverlay.classList.toggle('sidebar-overlay-open');
                        }
                        document.body.classList.toggle('sidebar-open');
                    }
                };
            }
            if (typeof window.sidebarClose !== 'function') {
                window.sidebarClose = function() {
                    const sidebar = document.getElementById('sidebar');
                    const sidebarOverlay = document.getElementById('sidebarOverlay');
                    if (sidebar) {
                        sidebar.classList.remove('sidebar-open');
                        if (sidebarOverlay) {
                            sidebarOverlay.classList.remove('sidebar-overlay-open');
                        }
                        document.body.classList.remove('sidebar-open');
                    }
                };
            }
        })();
        
        // Connect language selector button to modal
        document.addEventListener('DOMContentLoaded', function() {
            const langBtn = document.getElementById('languageSelectorBtn');
            if (langBtn && window.languageSelectorModal) {
                langBtn.addEventListener('click', function() {
                    window.languageSelectorModal.open();
                });
            }
            
            // Verify sidebar functions are still available after translation scripts run
            if (typeof window.sidebarToggle !== 'function') {
                console.error('CRITICAL: window.sidebarToggle was removed or overwritten!');
                // Restore it
                window.sidebarToggle = function() {
                    const sidebar = document.getElementById('sidebar');
                    const sidebarOverlay = document.getElementById('sidebarOverlay');
                    if (sidebar) {
                        sidebar.classList.toggle('sidebar-open');
                        if (sidebarOverlay) {
                            sidebarOverlay.classList.toggle('sidebar-overlay-open');
                        }
                        document.body.classList.toggle('sidebar-open');
                    }
                };
            }
            
            // Protect sidebar toggle buttons from translation interference
            const toggleButtons = document.querySelectorAll('.sidebar-toggle-btn');
            toggleButtons.forEach(function(btn) {
                // Ensure onclick is set correctly
                if (!btn.getAttribute('onclick') || !btn.getAttribute('onclick').includes('sidebarToggle')) {
                    btn.setAttribute('onclick', 'window.sidebarToggle()');
                }
                // Ensure data-no-translate is set
                if (!btn.hasAttribute('data-no-translate')) {
                    btn.setAttribute('data-no-translate', '');
                }
            });
        });
    </script>
</head>
<body class="user-home user-admin-header">
    <?php 
    // Set base paths for sidebar when included from root
    $basePath = '';
    $isRootContext = true;  // Flag to indicate we're in root context
    $assetSidebar = 'ADMIN/sidebar/';
    include 'USERS/includes/sidebar.php'; 
    ?>
    <?php include 'USERS/includes/admin-style-header.php'; ?>

    <button class="sidebar-toggle-btn" aria-label="Toggle menu" onclick="window.sidebarToggle()" data-no-translate>
        <i class="fas fa-bars"></i>
    </button>

    <!-- Safety fallback: define sidebarToggle if sidebar script failed to load -->
    <script>
        // Ensure sidebar functions are available immediately
        (function() {
            if (typeof window.sidebarToggle !== 'function') {
                window.sidebarToggle = function () {
                    const sidebar = document.getElementById('sidebar');
                    const sidebarOverlay = document.getElementById('sidebarOverlay');
                    if (!sidebar) {
                        console.error('Sidebar element not found (fallback toggle).');
                        return;
                    }
                    sidebar.classList.toggle('sidebar-open');
                    if (sidebarOverlay) {
                        sidebarOverlay.classList.toggle('sidebar-overlay-open');
                    }
                    document.body.classList.toggle('sidebar-open');
                };
            }
            if (typeof window.sidebarClose !== 'function') {
                window.sidebarClose = function () {
                    const sidebar = document.getElementById('sidebar');
                    const sidebarOverlay = document.getElementById('sidebarOverlay');
                    if (sidebar) {
                        sidebar.classList.remove('sidebar-open');
                        if (sidebarOverlay) {
                            sidebarOverlay.classList.remove('sidebar-overlay-open');
                        }
                        document.body.classList.remove('sidebar-open');
                    }
                };
            }
        })();
        
        // Verify and protect after DOM loads
        document.addEventListener('DOMContentLoaded', function() {
            // Verify sidebar functions are still available after translation scripts run
            if (typeof window.sidebarToggle !== 'function') {
                console.error('CRITICAL: window.sidebarToggle was removed or overwritten!');
                // Restore it
                window.sidebarToggle = function() {
                    const sidebar = document.getElementById('sidebar');
                    const sidebarOverlay = document.getElementById('sidebarOverlay');
                    if (sidebar) {
                        sidebar.classList.toggle('sidebar-open');
                        if (sidebarOverlay) {
                            sidebarOverlay.classList.toggle('sidebar-overlay-open');
                        }
                        document.body.classList.toggle('sidebar-open');
                    }
                };
            }
            
            // Protect sidebar toggle buttons from translation interference
            const toggleButtons = document.querySelectorAll('.sidebar-toggle-btn');
            toggleButtons.forEach(function(btn) {
                // Ensure onclick is set correctly
                if (!btn.getAttribute('onclick') || !btn.getAttribute('onclick').includes('sidebarToggle')) {
                    btn.setAttribute('onclick', 'window.sidebarToggle()');
                }
                // Ensure data-no-translate is set
                if (!btn.hasAttribute('data-no-translate')) {
                    btn.setAttribute('data-no-translate', '');
                }
            });
        });
    </script>

    <main class="main-content">
        <div class="hero-section home-hero" id="features">
            <div class="main-container">
                <div class="sub-container">
                    <h1 data-translate="home.title"><strong>QUEZON CITY EMERGENCY COMMUNICATION PORTAL</strong></h1>
                    <p class="hero-subtitle">
                        Quezon City Hall, Kalayaan Avenue, Diliman, Quezon City
                    </p>
                    <p class="hero-subtitle">
                        <strong data-translate="home.mission">Mission:</strong>
                        <span data-translate="home.mission.text">To operationalize an effective, efficient, and inclusive DRRM system dedicated to Resilience-building in Quezon City communities.</span>
                    </p>
                    <p class="hero-subtitle">
                        <strong data-translate="home.vision">Vision:</strong>
                        <span data-translate="home.vision.text">A global model of excellence in Disaster Risk Reduction and Management for its cohesive DRRM system fostering a Sustainable, Future-ready, and Resilient Quezon City.</span>
                    </p>
                </div>
            </div>
        </div>

        <div class="main-container">
            <div class="sub-container home-download-app">
                <h3 data-translate="home.download.title">Download Our Mobile App</h3>
                <p data-translate="home.download.desc">Get instant emergency alerts and notifications on your mobile device</p>
                <div class="app-download-buttons">
                    <a href="USERS/emergency-call.php" class="btn btn-primary emergency-call-btn">
                        <i class="fas fa-phone"></i>
                        <span data-translate="home.emergency.call">Call for Emergency</span>
                    </a>
                    <button type="button" class="btn emergency-call-btn report-incident-btn" id="reportIncidentBtn" aria-label="Report incident">
                        <i class="fas fa-triangle-exclamation"></i>
                        <span>Report Incident</span>
                    </button>
                    <?php $apkPath = __DIR__ . '/USERS/downloads/emergency-comms-app.apk'; $apkHref = 'USERS/downloads/emergency-comms-app.apk'; $apkVer = file_exists($apkPath) ? filemtime($apkPath) : time(); ?>
                    <a href="<?php echo $apkHref . '?v=' . $apkVer; ?>" class="app-download-btn" id="apkDownloadBtn" download="emergency-comms-app.apk" aria-label="Download APK">
                        <i class="fas fa-mobile-alt"></i>
                        <div class="app-btn-text">
                            <span class="app-btn-large" data-translate="home.download.download">Download APK</span>
                            <span class="app-btn-small" data-translate="home.download.apk.desc">Get the Android app now</span>
                        </div>
                        <i class="fas fa-download"></i>
                    </a>
                </div>
            </div>
        </div>

        <div class="main-container">
            <div class="sub-container">
                <section class="page-content">
                    <h2 data-translate="home.about.title">About Us</h2>
                    <p data-translate="home.about.text">
                        The Quezon City Emergency Communication Portal connects residents, responders, and the local government
                        through reliable, multi-channel emergency alerts and communication tools.
                        Our goal is to help you receive critical information quickly and safely during disasters, incidents,
                        and city-wide emergencies.
                    </p>
                </section>

                <section class="page-content">
                    <h2 data-translate="home.guide.title">Guide: How to Call for Emergency</h2>
                    <ol class="emergency-guide-list">
                        <li><strong data-translate="home.guide.1">Stay calm and move to a safe place.</strong> Ensure you are away from immediate danger before calling.</li>
                        <li><strong data-translate="home.guide.2">Use the "Call for Emergency" button.</strong> Click the button above or dial the hotlines on the Emergency Call page.</li>
                        <li><strong data-translate="home.guide.3">Prepare key details.</strong> Be ready to state your exact location, type of emergency, number of people involved, and visible hazards.</li>
                        <li><strong data-translate="home.guide.4">Follow instructions.</strong> Listen carefully to the dispatcher and follow their guidance while waiting for responders.</li>
                        <li><strong data-translate="home.guide.5">Keep lines open.</strong> Stay on the call or keep your phone available in case responders need more information.</li>
                    </ol>
                </section>
            </div>
        </div>
    </main>

    <?php 
    // Ensure variables are available for footer
    if (!isset($assetBase)) {
        $assetBase = 'ADMIN/header/';
    }
    if (!isset($basePath)) {
        $basePath = '';
    }
    if (!isset($isRootContext)) {
        $isRootContext = true;  // We're in root context
    }
    include 'USERS/includes/footer-snippet.php'; 
    ?>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="<?= $assetBase ?>js/mobile-menu.js"></script>
    <script src="<?= $assetBase ?>js/theme-toggle.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const reportIncidentBtn = document.getElementById('reportIncidentBtn');
            if (reportIncidentBtn && !reportIncidentBtn.hasAttribute('data-report-bound')) {
                reportIncidentBtn.setAttribute('data-report-bound', 'true');
                reportIncidentBtn.addEventListener('click', function (event) {
                    event.preventDefault();
                    if (typeof window.disableChatAssistantMode === 'function') {
                        window.disableChatAssistantMode();
                    } else {
                        window.chatAssistantMode = false;
                    }
                    if (typeof window.openChatForNewIncident === 'function') {
                        window.openChatForNewIncident();
                        return;
                    }
                    if (typeof window.openChat === 'function') {
                        window.openChat();
                        return;
                    }
                    const chatFab = document.getElementById('chatFab');
                    if (chatFab) {
                        chatFab.click();
                    }
                });
            }
        });

        // Enhanced language preference modal with improved design - Shows on landing page
        document.addEventListener('DOMContentLoaded', function () {
            // Function to get cookie value
            function getCookie(name) {
                const value = `; ${document.cookie}`;
                const parts = value.split(`; ${name}=`);
                if (parts.length === 2) return parts.pop().split(';').shift();
                return null;
            }
            
            // Check both localStorage and cookies for language preference
            let existingPreference = localStorage.getItem('preferredLanguage');
            if (!existingPreference) {
                existingPreference = getCookie('preferredLanguage');
                // If found in cookie but not localStorage, sync it
                if (existingPreference) {
                    localStorage.setItem('preferredLanguage', existingPreference);
                }
            }
            
            const languageMap = {
                'English': 'en',
                'Filipino': 'fil',
                'Spanish': 'es',
                'French': 'fr',
                'German': 'de',
                'Italian': 'it',
                'Portuguese': 'pt',
                'Russian': 'ru',
                'Chinese (Simplified)': 'zh',
                'Chinese (Traditional)': 'zh-TW',
                'Japanese': 'ja',
                'Korean': 'ko',
                'Arabic': 'ar',
                'Hindi': 'hi',
                'Thai': 'th',
                'Vietnamese': 'vi',
                'Indonesian': 'id',
                'Dutch': 'nl',
                'Polish': 'pl',
                'Turkish': 'tr',
                'Greek': 'el',
                'Hebrew': 'he',
                'Swedish': 'sv',
                'Norwegian': 'no',
                'Danish': 'da',
                'Finnish': 'fi',
                'Czech': 'cs',
                'Romanian': 'ro',
                'Hungarian': 'hu',
                'Malay': 'ms',
                'Tagalog': 'tl'
            };
            const languages = Object.keys(languageMap);

            // Show modal on landing page if no preference is saved
            if (!existingPreference) {
                // Check if auto-detect is enabled
                const autoDetectEnabled = localStorage.getItem('autoDetectLanguage') === 'true';
                if (autoDetectEnabled) {
                    // Auto-detect language immediately
                    const browserLang = (navigator.language || navigator.userLanguage || 'en').toLowerCase();
                    const langMap = {
                        'en': 'en', 'en-us': 'en', 'en-gb': 'en', 'en-au': 'en', 'en-ca': 'en',
                        'es': 'es', 'es-es': 'es', 'es-mx': 'es', 'es-ar': 'es', 'es-co': 'es',
                        'fr': 'fr', 'fr-fr': 'fr', 'fr-ca': 'fr', 'fr-be': 'fr', 'fr-ch': 'fr',
                        'de': 'de', 'de-de': 'de', 'de-at': 'de', 'de-ch': 'de',
                        'it': 'it', 'it-it': 'it', 'it-ch': 'it',
                        'pt': 'pt', 'pt-br': 'pt', 'pt-pt': 'pt',
                        'ru': 'ru', 'ru-ru': 'ru',
                        'zh': 'zh', 'zh-cn': 'zh', 'zh-tw': 'zh-TW', 'zh-hans': 'zh', 'zh-hant': 'zh-TW',
                        'ja': 'ja', 'ja-jp': 'ja',
                        'ko': 'ko', 'ko-kr': 'ko',
                        'ar': 'ar', 'ar-sa': 'ar', 'ar-ae': 'ar', 'ar-eg': 'ar',
                        'hi': 'hi', 'hi-in': 'hi',
                        'th': 'th', 'th-th': 'th',
                        'vi': 'vi', 'vi-vn': 'vi',
                        'id': 'id', 'id-id': 'id',
                        'nl': 'nl', 'nl-nl': 'nl', 'nl-be': 'nl',
                        'pl': 'pl', 'pl-pl': 'pl',
                        'tr': 'tr', 'tr-tr': 'tr',
                        'el': 'el', 'el-gr': 'el',
                        'he': 'he', 'he-il': 'he',
                        'sv': 'sv', 'sv-se': 'sv',
                        'no': 'no', 'nb': 'no', 'nn': 'no',
                        'da': 'da', 'da-dk': 'da',
                        'fi': 'fi', 'fi-fi': 'fi',
                        'cs': 'cs', 'cs-cz': 'cs',
                        'ro': 'ro', 'ro-ro': 'ro',
                        'hu': 'hu', 'hu-hu': 'hu',
                        'ms': 'ms', 'ms-my': 'ms',
                        'fil': 'fil', 'tl': 'fil', 'fil-ph': 'fil'
                    };
                    
                    let detectedLang = 'en';
                    if (langMap[browserLang]) {
                        detectedLang = langMap[browserLang];
                    } else {
                        const langPrefix = browserLang.split('-')[0];
                        if (langMap[langPrefix]) {
                            detectedLang = langMap[langPrefix];
                        }
                    }
                    
                    // Set language automatically
                    document.documentElement.setAttribute('data-lang', detectedLang);
                    localStorage.setItem('preferredLanguage', detectedLang);
                    const expiryDate = new Date();
                    expiryDate.setFullYear(expiryDate.getFullYear() + 1);
                    document.cookie = `preferredLanguage=${detectedLang}; expires=${expiryDate.toUTCString()}; path=/`;
                    
                    // Apply translations
                    if (typeof window.setLanguage === 'function') {
                        window.setLanguage(detectedLang);
                    }
                    if (typeof window.applyTranslations === 'function') {
                        setTimeout(() => window.applyTranslations(), 100);
                    }
                } else {
                    // Show modal if auto-detect is not enabled
                    showLanguageModal();
                }
            } else {
                // Apply existing preference and auto-translate
                document.documentElement.setAttribute('data-lang', existingPreference);
                
                // Apply translations automatically
                if (typeof window.setLanguage === 'function') {
                    window.setLanguage(existingPreference);
                }
                
                // Also call applyTranslations directly to ensure it runs
                if (typeof window.applyTranslations === 'function') {
                    setTimeout(() => {
                        window.applyTranslations();
                    }, 100);
                }
            }

            function showLanguageModal() {
                // Remove any existing modal first
                const existingModal = document.querySelector('.language-modal-backdrop');
                if (existingModal) {
                    existingModal.remove();
                }

                const wrapper = document.createElement('div');
                wrapper.className = 'language-modal-backdrop';
                wrapper.setAttribute('role', 'dialog');
                wrapper.setAttribute('aria-modal', 'true');
                wrapper.setAttribute('aria-labelledby', 'language-modal-title');
                wrapper.innerHTML = `
                    <div class="language-modal">
                        <h2 id="language-modal-title" data-translate="lang.select">Select Your Language</h2>
                        <p data-translate="lang.choose">Please choose your preferred language for alerts and content.</p>
                        <div class="language-buttons-row" style="display: flex; flex-direction: row; gap: 1rem; margin-bottom: 1rem;">
                            <button data-lang="en" data-translate="lang.english" style="flex: 1;">English</button>
                            <button data-lang="fil" data-translate="lang.filipino" style="flex: 1;">Filipino</button>
                        </div>
                        <div class="language-auto-detect" style="width: 100%;">
                            <button id="autoDetectBtn" style="width: 100%; padding: 1.5rem 2rem; font-size: 1.25rem; font-weight: 700; border-radius: 14px; cursor: pointer; transition: all 0.3s ease; border: 3px solid var(--card-border, #d1d5db); background: var(--card-bg, #ffffff); color: var(--text-color, #1f2937);">
                                <i class="fas fa-globe" style="margin-right: 0.5rem;"></i>
                                Auto Detect Language
                            </button>
                        </div>
                        <div class="language-search" style="margin-top: 1.5rem;">
                            <input type="text" id="languageSearchInput" data-translate-placeholder="lang.search.placeholder" placeholder="Search language...">
                            <ul id="languageSuggestions"></ul>
                        </div>
                    </div>
                `;
                document.body.appendChild(wrapper);
                
                // Prevent body scroll when modal is open
                document.body.style.overflow = 'hidden';
                
                // Focus on first button for accessibility
                const firstButton = wrapper.querySelector('.language-buttons-row button');
                if (firstButton) {
                    setTimeout(() => firstButton.focus(), 100);
                }

                const suggestionsList = wrapper.querySelector('#languageSuggestions');
                const searchInput = wrapper.querySelector('#languageSearchInput');

                function setLanguage(code, label) {
                    // Save to localStorage (browser cache)
                    localStorage.setItem('preferredLanguage', code);
                    
                    // Also save to cookie as backup
                    const expiryDate = new Date();
                    expiryDate.setFullYear(expiryDate.getFullYear() + 1); // 1 year expiry
                    document.cookie = `preferredLanguage=${code}; expires=${expiryDate.toUTCString()}; path=/`;
                    
                    // Set language attribute
                    document.documentElement.setAttribute('data-lang', code);
                    
                    // Apply translations automatically
                    if (typeof window.setLanguage === 'function') {
                        window.setLanguage(code);
                    }
                    
                    // Also call applyTranslations directly to ensure it runs
                    if (typeof window.applyTranslations === 'function') {
                        window.applyTranslations();
                    }
                    
                    // Restore body scroll
                    document.body.style.overflow = '';
                    wrapper.remove();
                }
                
                function autoDetectLanguage() {
                    // Get browser language
                    const browserLang = (navigator.language || navigator.userLanguage || 'en').toLowerCase();
                    let detectedLang = 'en'; // Default to English
                    
                    // Map browser language codes to our supported international languages
                    const langMap = {
                        'en': 'en', 'en-us': 'en', 'en-gb': 'en', 'en-au': 'en', 'en-ca': 'en',
                        'es': 'es', 'es-es': 'es', 'es-mx': 'es', 'es-ar': 'es', 'es-co': 'es',
                        'fr': 'fr', 'fr-fr': 'fr', 'fr-ca': 'fr', 'fr-be': 'fr', 'fr-ch': 'fr',
                        'de': 'de', 'de-de': 'de', 'de-at': 'de', 'de-ch': 'de',
                        'it': 'it', 'it-it': 'it', 'it-ch': 'it',
                        'pt': 'pt', 'pt-br': 'pt', 'pt-pt': 'pt',
                        'ru': 'ru', 'ru-ru': 'ru',
                        'zh': 'zh', 'zh-cn': 'zh', 'zh-tw': 'zh-TW', 'zh-hans': 'zh', 'zh-hant': 'zh-TW',
                        'ja': 'ja', 'ja-jp': 'ja',
                        'ko': 'ko', 'ko-kr': 'ko',
                        'ar': 'ar', 'ar-sa': 'ar', 'ar-ae': 'ar', 'ar-eg': 'ar',
                        'hi': 'hi', 'hi-in': 'hi',
                        'th': 'th', 'th-th': 'th',
                        'vi': 'vi', 'vi-vn': 'vi',
                        'id': 'id', 'id-id': 'id',
                        'nl': 'nl', 'nl-nl': 'nl', 'nl-be': 'nl',
                        'pl': 'pl', 'pl-pl': 'pl',
                        'tr': 'tr', 'tr-tr': 'tr',
                        'el': 'el', 'el-gr': 'el',
                        'he': 'he', 'he-il': 'he',
                        'sv': 'sv', 'sv-se': 'sv',
                        'no': 'no', 'nb': 'no', 'nn': 'no',
                        'da': 'da', 'da-dk': 'da',
                        'fi': 'fi', 'fi-fi': 'fi',
                        'cs': 'cs', 'cs-cz': 'cs',
                        'ro': 'ro', 'ro-ro': 'ro',
                        'hu': 'hu', 'hu-hu': 'hu',
                        'ms': 'ms', 'ms-my': 'ms',
                        'fil': 'fil', 'tl': 'fil', 'fil-ph': 'fil'
                    };
                    
                    // Check exact match first
                    if (langMap[browserLang]) {
                        detectedLang = langMap[browserLang];
                    } else {
                        // Check language prefix (e.g., 'es' from 'es-MX')
                        const langPrefix = browserLang.split('-')[0];
                        if (langMap[langPrefix]) {
                            detectedLang = langMap[langPrefix];
                        }
                    }
                    
                    setLanguage(detectedLang, 'Auto-detected');
                }

                // Apply translations to modal if available
                if (typeof window.applyTranslations === 'function') {
                    setTimeout(() => window.applyTranslations(), 100);
                }

                // English button (left)
                const englishBtn = wrapper.querySelector('button[data-lang="en"]');
                if (englishBtn) {
                    englishBtn.addEventListener('click', () => {
                        setLanguage('en', 'English');
                    });
                }
                
                // Filipino button (right)
                const filipinoBtn = wrapper.querySelector('button[data-lang="fil"]');
                if (filipinoBtn) {
                    filipinoBtn.addEventListener('click', () => {
                        setLanguage('fil', 'Filipino');
                    });
                }
                
                // Auto-detect button
                const autoDetectBtn = wrapper.querySelector('#autoDetectBtn');
                if (autoDetectBtn) {
                    autoDetectBtn.addEventListener('click', () => {
                        autoDetectLanguage();
                    });
                    
                    // Add hover effect
                    autoDetectBtn.addEventListener('mouseenter', function() {
                        this.style.background = 'var(--primary-color, #4c8a89)';
                        this.style.color = 'white';
                        this.style.borderColor = 'var(--primary-color, #4c8a89)';
                    });
                    autoDetectBtn.addEventListener('mouseleave', function() {
                        this.style.background = 'var(--card-bg, #ffffff)';
                        this.style.color = 'var(--text-color, #1f2937)';
                        this.style.borderColor = 'var(--card-border, #d1d5db)';
                    });
                }

                wrapper.addEventListener('click', (e) => {
                    if (e.target === wrapper) {
                        // Don't close on backdrop click - require language selection
                    }
                });

                if (searchInput && suggestionsList) {
                    searchInput.addEventListener('input', () => {
                        const q = searchInput.value.trim().toLowerCase();
                        suggestionsList.innerHTML = '';
                        
                        if (!q) {
                            suggestionsList.classList.remove('show');
                            return;
                        }

                        const matches = languages.filter(lang =>
                            lang.toLowerCase().includes(q)
                        );
                        
                        if (matches.length > 0) {
                            suggestionsList.classList.add('show');
                            matches.forEach(lang => {
                                const li = document.createElement('li');
                                li.textContent = lang;
                                li.addEventListener('click', () => {
                                    const code = languageMap[lang] || 'en';
                                    setLanguage(code, lang);
                                });
                                suggestionsList.appendChild(li);
                            });
                        } else {
                            suggestionsList.classList.remove('show');
                        }
                    });

                    // Hide suggestions when clicking outside
                    document.addEventListener('click', function(e) {
                        if (!wrapper.contains(e.target)) {
                            suggestionsList.classList.remove('show');
                        }
                    });
                }
            }
        });
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const tempEl = document.getElementById('topbarTemp');
            const conditionEl = document.getElementById('topbarCondition');
            const iconEl = document.getElementById('topbarWeatherIcon');
            const authTempEl = document.getElementById('authWeatherTemp');
            const authConditionEl = document.getElementById('authWeatherDesc');
            const authIconEl = document.getElementById('authWeatherIcon');

            const hasTopbar = tempEl && conditionEl && iconEl;
            const hasAuthPill = authTempEl && authConditionEl && authIconEl;

            if (!hasTopbar && !hasAuthPill) {
                return;
            }

            const lat = 14.6760;
            const lon = 121.0437;
            const apiUrl = `ADMIN/api/weather-monitoring.php?action=current&lat=${lat}&lon=${lon}`;

            fetch(apiUrl)
                .then(response => response.json())
                .then(data => {
                    if (!data || !data.success || !data.data) {
                        conditionEl.textContent = 'Weather unavailable';
                        return;
                    }

                    const weather = data.data;
                    const temp = Math.round(weather.main?.temp ?? 0);
                    const condition = weather.weather?.[0]?.description || 'Current conditions';
                    const icon = weather.weather?.[0]?.icon || '';

                    const formattedCondition = condition.charAt(0).toUpperCase() + condition.slice(1);

                    if (hasTopbar) {
                        tempEl.textContent = `${temp}°C`;
                        conditionEl.textContent = formattedCondition;
                        if (icon) {
                            iconEl.innerHTML = `<img src="https://openweathermap.org/img/wn/${icon}@2x.png" alt="${condition}" />`;
                        }
                    }

                    if (hasAuthPill) {
                        authTempEl.textContent = `${temp}°C`;
                        authConditionEl.textContent = formattedCondition;
                        if (icon) {
                            authIconEl.innerHTML = `<img src="https://openweathermap.org/img/wn/${icon}@2x.png" alt="${condition}" />`;
                        }
                    }
                })
                .catch(() => {
                    if (hasTopbar) {
                        conditionEl.textContent = 'Weather unavailable';
                    }
                    if (hasAuthPill) {
                        authConditionEl.textContent = 'Weather unavailable';
                    }
                });
        });
    </script>
</body>
</html>
