<?php
// User dashboard for emergency calling options (SIM and Internet/WiFi)
$assetBase = '../ADMIN/header/';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Emergency Dashboard</title>
    <link rel="icon" type="image/x-icon" href="<?= $assetBase ?>images/favicon.ico">
    <link rel="stylesheet" href="<?= $assetBase ?>css/global.css">
    <link rel="stylesheet" href="<?= $assetBase ?>css/buttons.css">
    <link rel="stylesheet" href="<?= $assetBase ?>css/hero.css">
    <link rel="stylesheet" href="../ADMIN/sidebar/css/global.css">
    <link rel="stylesheet" href="../ADMIN/sidebar/css/sidebar.css">
    <link rel="stylesheet" href="../ADMIN/sidebar/css/content.css">
    <link rel="stylesheet" href="../ADMIN/sidebar/css/buttons.css">
    <link rel="stylesheet" href="css/user.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <!-- Emergency Alert System -->
    <link rel="stylesheet" href="../ADMIN/header/css/emergency-alert.css">
    <script>
        // Set global API base path for all JS files
        window.API_BASE_PATH = 'api/';
    </script>
    <script src="js/translations.js"></script>
    <script src="js/language-manager.js"></script>
    <script src="js/global-translator.js"></script>
    <script src="js/language-selector-modal.js"></script>
    <script src="js/language-sync.js"></script>
    <script>
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
    <?php include 'includes/sidebar.php'; ?>

    <button class="sidebar-toggle-btn" aria-label="Toggle menu" onclick="window.sidebarToggle()" data-no-translate>
        <i class="fas fa-bars"></i>
    </button>

    <main class="main-content">
        <div class="hero-section" id="call-now">
            <div class="main-container">
                <div class="sub-container">
                    <h1 data-translate="emergency.title">Call for Emergency</h1>
                    <p data-translate="emergency.subtitle">Use data or WiFi to connect with responders via Internet calling.</p>
                    <div class="hero-buttons action-buttons">
                        <button class="btn btn-primary" onclick="startInternetCall()"><i class="fas fa-headset"></i> <span>Start Internet Call</span></button>
                    </div>
                </div>
            </div>
        </div>

        <div class="main-container">
            <div class="sub-container content-main">
                <section id="internet-call" class="page-content">
                    <h2>Call Using Internet/WiFi</h2>
                    <p>Use data or WiFi when cellular signal is weak. Connect with emergency responders via VoIP or web-based calling.</p>
                    <div class="cards-grid">
                        <div class="card">
                            <h3>Web/VoIP Call</h3>
                            <p>Start a voice call over WiFi or mobile data. Connect directly with emergency dispatchers.</p>
                            <button class="btn btn-primary" onclick="startInternetCall()">
                                <i class="fas fa-headset"></i> <span>Start Internet Call</span>
                            </button>
                        </div>
                        <div class="card">
                            <h3>Two-Way Chat</h3>
                            <p>Send incident details and get dispatcher replies over data. Real-time communication with emergency services.</p>
                            <button class="btn btn-secondary" onclick="openEmergencyChat()">
                                <i class="fas fa-comments"></i> <span>Open Chat</span>
                            </button>
                        </div>
                    </div>
                </section>

                <section id="contacts" class="page-content emergency-hotlines-section">
                    <h2 data-translate="emergency.hotlines.title">Quezon City Emergency Hotlines</h2>
                    <p style="font-size: 1.1rem; margin-bottom: 2rem;" data-translate="emergency.hotlines.desc">Official QCDRRMO and Quezon City emergency numbers. Save these for quick access.</p>
                    <div class="cards-grid">
                        <div class="card emergency-card">
                            <div class="emergency-card-header">
                                <h3>QC HELPLINE</h3>
                            </div>
                            <div class="emergency-number-large">DIAL 122</div>
                            <a href="tel:122" class="btn btn-primary" data-no-translate>
                                <i class="fas fa-phone"></i> <span data-translate="home.hotlines.call122">Call 122</span>
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
                                <a href="tel:+639770312892" class="btn btn-primary" data-no-translate><span data-translate="home.hotlines.callGlobe">Call Globe</span></a>
                                <a href="tel:+639478859929" class="btn btn-secondary" data-no-translate><span data-translate="home.hotlines.callSmart">Call Smart</span></a>
                                <a href="tel:0289884242,,7245" class="btn btn-secondary" data-no-translate>Call Local 7245</a>
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
                                <a href="tel:+639478847498" class="btn btn-primary" data-no-translate><span data-translate="home.hotlines.callEMS">Call EMS</span></a>
                                <a href="tel:0289284396" class="btn btn-secondary" data-no-translate>Call 8928-4396</a>
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
                                <a href="tel:0289275914" class="btn btn-primary" data-no-translate>Call 8927-5914</a>
                                <a href="tel:0289284396" class="btn btn-secondary" data-no-translate>Call 8928-4396</a>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </main>

    <?php include 'includes/footer-snippet.php'; ?>

    <!-- Firebase SDK (for chat) - Loaded dynamically by sidebar.php to avoid conflicts -->
    
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="<?= $assetBase ?>js/mobile-menu.js"></script>
    <script src="<?= $assetBase ?>js/theme-toggle.js"></script>
    <script>
        function startInternetCall() {
            // TODO: Integrate with your VoIP/web-call service
            // Example: window.location.href = 'your-voip-service-url';
            // Or: initiateWebRTC call, etc.
            
            Swal.fire({
                icon: 'info',
                title: 'Internet Call',
                html: `
                    <p>This feature will connect you to emergency services via Internet calling.</p>
                    <p><strong>To implement:</strong></p>
                    <ul style="text-align: left; margin: 1rem 0;">
                        <li>Integrate with your VoIP service (Twilio, Vonage, etc.)</li>
                        <li>Or implement WebRTC for browser-based calling</li>
                        <li>Connect to your emergency dispatch system</li>
                    </ul>
                `,
                confirmButtonText: 'OK',
                footer: '<small>This is a placeholder. Connect to your VoIP/web-call service.</small>'
            });
        }

        function openEmergencyChat() {
            // Use the global function from sidebar if available
            if (typeof window.openChat === 'function') {
                window.openChat();
                // Initialize Firebase chat if not already done
                if (window.initFirebaseChat && !window.chatInitialized) {
                    setTimeout(() => {
                        window.initFirebaseChat();
                    }, 100);
                }
            } else {
                // Fallback: try to find and click the chat button
                const chatFab = document.getElementById('chatFab');
                if (chatFab) {
                    chatFab.click();
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Chat Not Available',
                        text: 'Chat feature is loading. Please wait a moment and try again.',
                        confirmButtonText: 'OK'
                    });
                }
            }
        }
    </script>
    
    <!-- Emergency Alert System -->
    <script src="../ADMIN/header/js/emergency-alert.js"></script>
</body>
</html>
