<?php
$assetBase = '../ADMIN/header/';
$current = 'support.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Support & Resources</title>
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
    <script src="js/translations.js"></script>
    <script src="js/language-manager.js"></script>
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

    <button class="sidebar-toggle-btn" aria-label="Toggle menu" onclick="window.sidebarToggle()">
        <i class="fas fa-bars"></i>
    </button>

    <main class="main-content">
        <div class="hero-section" id="support">
            <div class="main-container">
                <div class="sub-container">
                    <h1 data-translate="support.title">Support & Resources</h1>
                    <p data-translate="support.subtitle">Get guidance on responding to alerts and requesting assistance.</p>
                </div>
            </div>
        </div>

        <div class="main-container">
            <div class="sub-container content-main">
                <section class="page-content">
                    <h2 data-translate="support.help.title">Help & Guidance</h2>
                    <div class="cards-grid">
                        <div class="card">
                            <h4 data-translate="support.respond.title">How to Respond</h4>
                            <p data-translate="support.respond.desc">Step-by-step instructions for common alert types.</p>
                            <button class="btn btn-primary" data-translate="support.respond.btn">View Guide</button>
                        </div>
                        <div class="card">
                            <h4 data-translate="support.dispatch.title">Contact Dispatch</h4>
                            <p data-translate="support.dispatch.desc">Reach emergency dispatch or your local incident commander.</p>
                            <button class="btn btn-secondary" data-translate="support.dispatch.btn">Contact Now</button>
                        </div>
                        <div class="card">
                            <h4 data-translate="support.audit.title">Audit & History</h4>
                            <p data-translate="support.audit.desc">See what was sent and when for transparency.</p>
                            <button class="btn btn-secondary" data-translate="support.audit.btn">Open Log</button>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </main>

    <?php include 'includes/footer-snippet.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="<?= $assetBase ?>js/mobile-menu.js"></script>
    <script src="<?= $assetBase ?>js/theme-toggle.js"></script>
</body>
</html>

