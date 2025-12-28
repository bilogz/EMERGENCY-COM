<?php
// Dedicated Home page for the user portal
$assetBase = 'ADMIN/header/';
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
    <link rel="stylesheet" href="ADMIN/sidebar/css/buttons.css">
    <link rel="stylesheet" href="USERS/css/user.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="USERS/js/translations.js"></script>
    <script src="USERS/js/language-manager.js"></script>
    <script src="USERS/js/language-selector-enhanced.js"></script>
</head>
<body>
    <?php 
    // Set base paths for sidebar when included from root
    $basePath = '';
    $isRootContext = true;  // Flag to indicate we're in root context
    $assetSidebar = 'ADMIN/sidebar/';
    include 'USERS/includes/sidebar.php'; 
    ?>

    <button class="sidebar-toggle-btn" aria-label="Toggle menu" onclick="window.sidebarToggle()">
        <i class="fas fa-bars"></i>
    </button>

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
                        <span data-translate="home.vision.text">A global mode of excellence in Disaster Risk Reduction and Management for its cohesive DRRM system fostering a Sustainable, Future-ready, and Resilient Quezon City.</span>
                    </p>
                </div>
            </div>
        </div>

        <div class="main-container">
            <div class="sub-container home-download-app">
                <h3 data-translate="home.download.title">Download Our Mobile App</h3>
                <p data-translate="home.download.desc">Get instant emergency alerts and notifications on your mobile device</p>
                <div class="app-download-buttons">
                    <div class="app-download-btn coming-soon-btn" id="apkDownloadBtn" aria-label="Coming Soon">
                        <i class="fas fa-mobile-alt"></i>
                        <div class="app-btn-text">
                            <span class="app-btn-large" data-translate="home.download.comingsoon">Coming Soon</span>
                            <span class="app-btn-small" data-translate="home.download.comingsoon.desc">Mobile app launching soon</span>
                        </div>
                        <span class="coming-soon-badge" data-translate="home.download.badge">SOON</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="main-container">
            <div class="sub-container home-emergency-cta">
                <a href="USERS/emergency-call.php" class="btn btn-primary emergency-call-btn">
                    <i class="fas fa-phone"></i>
                    <span data-translate="home.emergency.call">Call for Emergency</span>
                </a>
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
                    <h2 data-translate="home.services.title">Services</h2>
                    <div class="cards-grid">
                        <div class="card">
                            <h3 data-translate="home.services.mass">Mass Notifications</h3>
                            <p data-translate="home.services.mass.desc">City-wide alerts sent via SMS, email, and online channels for urgent incidents and advisories.</p>
                        </div>
                        <div class="card">
                            <h3 data-translate="home.services.twoWay">Two-Way Communication</h3>
                            <p data-translate="home.services.twoWay.desc">Residents can report incidents, request assistance, and send updates back to responders.</p>
                        </div>
                        <div class="card">
                            <h3 data-translate="home.services.automated">Automated Hazard Feeds</h3>
                            <p data-translate="home.services.automated.desc">Integrated updates from agencies such as PAGASA and PHIVOLCS for weather and seismic events.</p>
                        </div>
                        <div class="card">
                            <h3 data-translate="home.services.multilingual">Multilingual Alerts</h3>
                            <p data-translate="home.services.multilingual.desc">Important messages can be delivered in multiple languages to reach more communities.</p>
                        </div>
                    </div>
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
        // Enhanced language preference modal with improved design
        document.addEventListener('DOMContentLoaded', function () {
            const existingPreference = localStorage.getItem('preferredLanguage');
            const languageMap = {
                'English': 'en',
                'Filipino': 'fil',
                'Cebuano': 'ceb',
                'Ilocano': 'ilo',
                'Kapampangan': 'pam',
                'Bicolano': 'bcl',
                'Waray': 'war'
            };
            const languages = Object.keys(languageMap);

            // Show modal on first visit or if no preference is saved
            const hasShownModal = sessionStorage.getItem('languageModalShown');
            
            if (!hasShownModal || !existingPreference) {
                showLanguageModal();
                if (!hasShownModal) {
                    sessionStorage.setItem('languageModalShown', 'true');
                }
            } else if (existingPreference) {
                // Apply existing preference
                if (typeof window.setLanguage === 'function') {
                    window.setLanguage(existingPreference);
                } else {
                    document.documentElement.setAttribute('data-lang', existingPreference);
                }
            }

            function showLanguageModal() {
                const wrapper = document.createElement('div');
                wrapper.className = 'language-modal-backdrop';
                wrapper.innerHTML = `
                    <div class="language-modal">
                        <h2 data-translate="lang.select">Select Language</h2>
                        <p data-translate="lang.choose">Please choose your preferred language for alerts and content.</p>
                        <div class="language-buttons-row">
                            <button data-lang="en" data-translate="lang.english">English</button>
                            <button data-lang="fil" data-translate="lang.filipino">Filipino</button>
                        </div>
                        <div class="language-search">
                            <input type="text" id="languageSearchInput" data-translate-placeholder="lang.search.placeholder" placeholder="Search language...">
                            <ul id="languageSuggestions"></ul>
                        </div>
                    </div>
                `;
                document.body.appendChild(wrapper);

                const suggestionsList = wrapper.querySelector('#languageSuggestions');
                const searchInput = wrapper.querySelector('#languageSearchInput');

                function setLanguage(code, label) {
                    if (typeof window.setLanguage === 'function') {
                        window.setLanguage(code);
                    } else {
                        localStorage.setItem('preferredLanguage', code);
                        document.documentElement.setAttribute('data-lang', code);
                        if (typeof window.applyTranslations === 'function') {
                            window.applyTranslations();
                        }
                    }
                    wrapper.remove();
                }

                // Apply translations to modal if available
                if (typeof window.applyTranslations === 'function') {
                    setTimeout(() => window.applyTranslations(), 100);
                }

                wrapper.querySelectorAll('.language-buttons-row button').forEach(btn => {
                    btn.addEventListener('click', () => {
                        const code = btn.getAttribute('data-lang');
                        const label = btn.textContent.trim();
                        setLanguage(code, label);
                    });
                });

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
</body>
</html>