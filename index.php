<?php
// Themed public landing page for EMERGENCY-COM using the system's shared styles
$assetBase = 'ADMIN/header/';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EMERGENCY-COM | Community Emergency Communication System</title>

    <link rel="icon" type="image/x-icon" href="<?= $assetBase ?>images/favicon.ico">
    <!-- Shared theme styles -->
    <link rel="stylesheet" href="<?= $assetBase ?>css/global.css">
    <link rel="stylesheet" href="<?= $assetBase ?>css/buttons.css">
    <link rel="stylesheet" href="<?= $assetBase ?>css/hero.css">
    <!-- Reuse admin/user theme tokens and cards -->
    <link rel="stylesheet" href="ADMIN/sidebar/css/global.css">
    <link rel="stylesheet" href="USERS/css/user.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
</head>
<body>
    <!-- Top navigation themed like the system header -->
    <header class="header">
        <div class="header-container">
            <a href="index.php" class="logo">
                <img src="<?= $assetBase ?>images/logo.svg" alt="EMERGENCY-COM logo" class="logo-img">
                <span>EMERGENCY-COM</span>
            </a>

            <nav class="nav-center">
                <ul class="nav-menu">
                    <li><a href="#top" class="nav-link active">Overview</a></li>
                    <li><a href="#features" class="nav-link">Key Features</a></li>
                    <li><a href="#how-it-works" class="nav-link">How it works</a></li>
                    <li><a href="#support" class="nav-link">Support</a></li>
                </ul>
            </nav>

            <div class="nav-actions">
                <a href="USERS/login.php" class="btn btn-secondary">User Login</a>
                <a href="ADMIN/admin-login.php" class="btn btn-primary">Admin Login</a>
                <button class="mobile-menu-toggle" aria-label="Toggle mobile menu">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
        </div>

        <!-- Mobile Navigation -->
        <div class="mobile-nav">
            <div class="mobile-nav-header">
                <a href="index.php" class="mobile-nav-logo">
                    <img src="<?= $assetBase ?>images/logo.svg" alt="EMERGENCY-COM logo" class="logo-img">
                    <span>EMERGENCY-COM</span>
                </a>
                <button class="mobile-nav-close" aria-label="Close mobile menu">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <ul class="mobile-nav-menu">
                <li><a href="#top" class="mobile-nav-link active">Overview</a></li>
                <li><a href="#features" class="mobile-nav-link">Key Features</a></li>
                <li><a href="#how-it-works" class="mobile-nav-link">How it works</a></li>
                <li><a href="#support" class="mobile-nav-link">Support</a></li>
                <li class="mobile-nav-divider"></li>
                <li><a href="USERS/login.php" class="mobile-nav-link">User Login</a></li>
                <li><a href="ADMIN/admin-login.php" class="mobile-nav-link">Admin Login</a></li>
            </ul>
        </div>

        <!-- Mobile Navigation Overlay -->
        <div class="mobile-nav-overlay"></div>
    </header>

    <main class="main-content" id="top">
        <!-- Hero section reusing system hero styles -->
        <div class="hero-section">
            <div class="main-container">
                <div class="sub-container">
                    <h1>Emergency Communication System</h1>
                    <p>
                        Stay informed with real-time alerts, multilingual support, and two-way communication
                        across web, SMS, email, and PA systems.
                    </p>
                    <div class="hero-buttons">
                        <a href="USERS/login.php" class="btn btn-primary">Login as User</a>
                        <a href="#features" class="btn btn-secondary">Explore Features</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- System overview cards styled like user portal -->
        <div class="main-container">
            <div class="sub-container">
                <section id="features" class="page-content">
                    <h2>What EMERGENCY-COM provides</h2>
                    <p>
                        This system centralizes emergency alerts and communication so citizens and responders
                        can act quickly and confidently.
                    </p>
                    <div class="cards-grid">
                        <div class="card">
                            <h3>Real-Time Alerts</h3>
                            <p>Receive critical notifications from integrated sources like PAGASA and PHIVOLCS.</p>
                        </div>
                        <div class="card">
                            <h3>Two-Way Communication</h3>
                            <p>Citizens can acknowledge alerts, request help, and share on-ground updates.</p>
                        </div>
                        <div class="card">
                            <h3>Multichannel Delivery</h3>
                            <p>Alerts can be delivered via web, SMS, email, and public address systems.</p>
                        </div>
                        <div class="card">
                            <h3>Subscriptions & Preferences</h3>
                            <p>Users can subscribe to categories and choose preferred languages.</p>
                        </div>
                    </div>
                </section>

                <section id="how-it-works" class="page-content">
                    <h2>How it works for guests and users</h2>
                    <p>
                        As a guest you can learn about the platform. To receive alerts and manage your
                        preferences you need a user account.
                    </p>
                    <div class="cards-grid">
                        <div class="card">
                            <h4>Guests</h4>
                            <p>View public information about the system and available emergency channels.</p>
                        </div>
                        <div class="card">
                            <h4>Registered Users</h4>
                            <p>Access personalized dashboards, current alerts, and your subscription settings.</p>
                            <a href="USERS/login.php" class="btn btn-primary">Go to User Login</a>
                        </div>
                        <div class="card">
                            <h4>Administrators</h4>
                            <p>Configure alerts, manage subscribers, and review system activity.</p>
                            <a href="ADMIN/admin-login.php" class="btn btn-secondary">Admin Login</a>
                        </div>
                    </div>
                </section>

                <section id="support" class="page-content">
                    <h2>Support & emergency guidance</h2>
                    <p>
                        This platform is designed to complement, not replace, your local emergency hotlines.
                        In life-threatening situations, always call your local emergency number first.
                    </p>
                    <div class="cards-grid">
                        <div class="card">
                            <h4>Emergency Numbers</h4>
                            <p>Contact your local emergency dispatch or LGU hotlines immediately for urgent help.</p>
                        </div>
                        <div class="card">
                            <h4>User Support</h4>
                            <p>Once logged in, you can access detailed guides and support materials.</p>
                            <a href="USERS/support.php" class="btn btn-secondary">View Support Page</a>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </main>

    <?php include 'USERS/includes/footer-snippet.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="<?= $assetBase ?>js/mobile-menu.js"></script>
    <script src="<?= $assetBase ?>js/theme-toggle.js"></script>
</body>
</html>
