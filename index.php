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
    <script src="USERS/js/language-selector-modal.js"></script>
    <script src="USERS/js/language-sync.js"></script>
    <script>
        // Connect language selector button to modal
        document.addEventListener('DOMContentLoaded', function() {
            const langBtn = document.getElementById('languageSelectorBtn');
            if (langBtn && window.languageSelectorModal) {
                langBtn.addEventListener('click', function() {
                    window.languageSelectorModal.open();
                });
            }
        });
    </script>
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
                    <a href="USERS/downloads/emergency-com.apk" class="app-download-btn" id="apkDownloadBtn" download aria-label="Download APK">
                        <i class="fas fa-mobile-alt"></i>
                        <div class="app-btn-text">
                            <span class="app-btn-large" data-translate="home.download.download">Download APK</span>
                            <span class="app-btn-small" data-translate="home.download.desc">Get the Android app now</span>
                        </div>
                        <i class="fas fa-download"></i>
                    </a>
                </div>
            </div>
        </div>

        <!-- Emergency Hotlines Section -->
        <div class="main-container">
            <div class="sub-container">
                <section class="page-content emergency-hotlines-section">
                    <h2>Quezon City Emergency Hotlines</h2>
                    <p>Save these numbers for quick access during emergencies</p>
                    <div class="cards-grid">
                        <div class="card emergency-card">
                            <div class="emergency-card-header">
                                <h3>QC HELPLINE</h3>
                            </div>
                            <div class="emergency-number-large">DIAL 122</div>
                            <a href="tel:122" class="btn btn-primary">
                                <i class="fas fa-phone"></i> Call 122
                            </a>
                        </div>
                        <div class="card emergency-card">
                            <div class="emergency-card-header">
                                <h3>Emergency Operations Center (EOC)</h3>
                            </div>
                            <div class="emergency-numbers">
                                <p><strong>0977 031 2892</strong> (GLOBE)</p>
                                <p><strong>0947 885 9929</strong> (SMART)</p>
                                <p><strong>8988 4242</strong> local 7245</p>
                            </div>
                            <div class="action-buttons">
                                <a href="tel:+639770312892" class="btn btn-primary">Call Globe</a>
                                <a href="tel:+639478859929" class="btn btn-secondary">Call Smart</a>
                            </div>
                        </div>
                        <div class="card emergency-card">
                            <div class="emergency-card-header">
                                <h3>Emergency Medical Services / Urban Search and Rescue</h3>
                            </div>
                            <div class="emergency-numbers">
                                <p><strong>0947 884 7498</strong> (SMART)</p>
                                <p><strong>8928 4396</strong></p>
                            </div>
                            <div class="action-buttons">
                                <a href="tel:+639478847498" class="btn btn-primary">Call EMS</a>
                                <a href="tel:0289284396" class="btn btn-secondary">Call 8928-4396</a>
                            </div>
                        </div>
                        <div class="card emergency-card">
                            <div class="emergency-card-header">
                                <h3>QCDRRMO Landline</h3>
                            </div>
                            <div class="emergency-numbers">
                                <p><strong>8927-5914</strong></p>
                                <p><strong>8928-4396</strong></p>
                            </div>
                            <div class="action-buttons">
                                <a href="tel:0289275914" class="btn btn-primary">Call 8927-5914</a>
                                <a href="tel:0289284396" class="btn btn-secondary">Call 8928-4396</a>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </div>

        <div class="main-container">
            <div class="sub-container home-emergency-cta">
                <div class="emergency-actions">
                    <a href="USERS/emergency-call.php" class="btn btn-primary emergency-call-btn">
                        <i class="fas fa-phone"></i>
                        <span data-translate="home.emergency.call">Call for Emergency</span>
                    </a>
                    <button id="guestLoginBtn" class="btn btn-secondary guest-login-btn">
                        <i class="fas fa-user-secret"></i>
                        <span>Continue as Guest (Emergency Only)</span>
                    </button>
                </div>
                <p class="guest-notice" style="margin-top: 1rem; font-size: 0.9rem; color: #666;">
                    <i class="fas fa-info-circle"></i> Guest access is limited to emergency calls only. 
                    <a href="USERS/login.php">Login</a> or <a href="USERS/signup.php">Sign Up</a> for full access.
                </p>
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
        // Guest Login Handler
        document.addEventListener('DOMContentLoaded', function() {
            const guestLoginBtn = document.getElementById('guestLoginBtn');
            if (guestLoginBtn) {
                guestLoginBtn.addEventListener('click', async function() {
                    const result = await Swal.fire({
                        title: 'Continue as Guest?',
                        html: `
                            <p>Guest access is limited to emergency calls only.</p>
                            <p><strong>You will be able to:</strong></p>
                            <ul style="text-align: left; margin: 1rem 0;">
                                <li>Access emergency hotlines</li>
                                <li>Make emergency calls</li>
                            </ul>
                            <p><strong>You will NOT be able to:</strong></p>
                            <ul style="text-align: left; margin: 1rem 0;">
                                <li>Receive personalized alerts</li>
                                <li>Access your profile</li>
                                <li>Manage preferences</li>
                            </ul>
                            <p style="margin-top: 1rem;"><small>Your session will be monitored for security purposes.</small></p>
                        `,
                        icon: 'info',
                        showCancelButton: true,
                        confirmButtonText: 'Continue as Guest',
                        cancelButtonText: 'Cancel',
                        confirmButtonColor: '#28a745',
                        cancelButtonColor: '#6c757d'
                    });

                    if (result.isConfirmed) {
                        try {
                            const response = await fetch('USERS/api/user-login.php', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                },
                                body: JSON.stringify({
                                    login_type: 'guest',
                                    agreement_accepted: true
                                })
                            });

                            const data = await response.json();
                            
                            if (data.success) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Guest Access Granted',
                                    text: 'Redirecting to emergency services...',
                                    timer: 1500,
                                    showConfirmButton: false
                                }).then(() => {
                                    window.location.href = 'USERS/emergency-call.php';
                                });
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: data.message || 'Failed to grant guest access. Please try again.'
                                });
                            }
                        } catch (error) {
                            console.error('Guest login error:', error);
                            Swal.fire({
                                icon: 'error',
                                title: 'Connection Error',
                                text: 'Please check your internet connection and try again.'
                            });
                        }
                    }
                });
            }
        });
    </script>
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