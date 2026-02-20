<?php
// Include centralized session configuration
require_once __DIR__ . '/../session-config.php';

$assetBase = '../ADMIN/header/';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cookie & Cache Policy - LGU #4 Emergency Communication System</title>
    <link rel="icon" type="image/x-icon" href="<?= $assetBase ?>images/favicon.ico">
    <link rel="stylesheet" href="<?= $assetBase ?>css/global.css">
    <link rel="stylesheet" href="<?= $assetBase ?>css/buttons.css">
    <link rel="stylesheet" href="../ADMIN/sidebar/css/global.css">
    <link rel="stylesheet" href="../ADMIN/sidebar/css/sidebar.css">
    <link rel="stylesheet" href="../ADMIN/sidebar/css/content.css">
    <link rel="stylesheet" href="../ADMIN/sidebar/css/admin-header.css">
    <link rel="stylesheet" href="../ADMIN/sidebar/css/buttons.css">
    <link rel="stylesheet" href="css/user.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .policy-container {
            max-width: 900px;
            margin: 0 auto;
            padding: 2rem 1rem;
        }
        .policy-header {
            text-align: center;
            margin-bottom: 2rem;
            padding-bottom: 1.5rem;
            border-bottom: 2px solid rgba(76, 175, 80, 0.2);
        }
        .policy-header h1 {
            color: #2e7d32;
            margin-bottom: 0.5rem;
        }
        .policy-header p {
            color: var(--text-muted, #6b7280);
        }
        .policy-section {
            margin-bottom: 2rem;
        }
        .policy-section h2 {
            color: #2e7d32;
            font-size: 1.25rem;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .policy-section h2 i {
            color: #4caf50;
        }
        .policy-section p, .policy-section ul {
            line-height: 1.8;
            color: var(--text-color, #333);
        }
        .policy-section ul {
            padding-left: 1.5rem;
        }
        .policy-section li {
            margin-bottom: 0.5rem;
        }
        .highlight-box {
            background: rgba(76, 175, 80, 0.1);
            border-left: 4px solid #4caf50;
            padding: 1rem;
            margin: 1rem 0;
            border-radius: 0 8px 8px 0;
        }
        .cookie-table {
            width: 100%;
            border-collapse: collapse;
            margin: 1rem 0;
        }
        .cookie-table th,
        .cookie-table td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid rgba(0,0,0,0.1);
        }
        .cookie-table th {
            background: rgba(76, 175, 80, 0.1);
            color: #2e7d32;
            font-weight: 600;
        }
        .cookie-table tr:hover {
            background: rgba(76, 175, 80, 0.05);
        }
        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            margin-top: 2rem;
            padding: 0.75rem 1.5rem;
            background: #4caf50;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            transition: background 0.2s;
        }
        .back-btn:hover {
            background: #45a049;
        }
        .icon-box {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            background: rgba(76, 175, 80, 0.1);
            border-radius: 8px;
            margin-right: 0.5rem;
        }
        .icon-box i {
            color: #4caf50;
            font-size: 1.2rem;
        }
    </style>
</head>
<body class="user-admin-header">
    <?php include 'includes/user-global-header.php'; ?>

    <main class="main-content" style="padding-top: 60px;">
        <div class="main-container">
            <div class="sub-container content-main">
                <div class="policy-container">
                    <div class="policy-header">
                        <h1><i class="fas fa-cookie-bite"></i> Cookie & Cache Policy</h1>
                        <p>Effective Date: <?= date('F d, Y'); ?></p>
                    </div>

                    <div class="policy-section">
                        <h2><i class="fas fa-info-circle"></i> What Are Cookies?</h2>
                        <p>Cookies are small text files that are placed on your device when you visit our website. They help us provide you with a better experience by remembering your preferences, keeping you logged in, and understanding how you use our services. Cookies are widely used to make websites work more efficiently and provide information to website owners.</p>
                    </div>

                    <div class="policy-section">
                        <h2><i class="fas fa-cookie"></i> How We Use Cookies</h2>
                        <p>The LGU #4 Emergency Communication System uses cookies for the following purposes:</p>
                        
                        <table class="cookie-table">
                            <thead>
                                <tr>
                                    <th>Cookie Type</th>
                                    <th>Purpose</th>
                                    <th>Duration</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><strong>Session Cookies</strong></td>
                                    <td>Keep you logged in during your visit and maintain your session state</td>
                                    <td>Session (deleted when browser closes)</td>
                                </tr>
                                <tr>
                                    <td><strong>Authentication Cookies</strong></td>
                                    <td>Remember your login status and user preferences</td>
                                    <td>Up to 30 days</td>
                                </tr>
                                <tr>
                                    <td><strong>Language Preference</strong></td>
                                    <td>Store your selected language (English/Filipino)</td>
                                    <td>1 year</td>
                                </tr>
                                <tr>
                                    <td><strong>Theme Preference</strong></td>
                                    <td>Remember your light/dark mode preference</td>
                                    <td>1 year</td>
                                </tr>
                                <tr>
                                    <td><strong>Security Cookies</strong></td>
                                    <td>Help protect against fraudulent activity and ensure secure access</td>
                                    <td>Session</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="policy-section">
                        <h2><i class="fas fa-database"></i> What is Browser Cache?</h2>
                        <p>Browser cache is temporary storage on your device that saves copies of web pages, images, and other content. When you revisit our website, your browser can load these cached files instead of downloading them again, making the site load faster and reducing data usage.</p>
                    </div>

                    <div class="policy-section">
                        <h2><i class="fas fa-hdd"></i> How We Use Cache</h2>
                        <p>Our system uses caching to improve performance:</p>
                        <ul>
                            <li><strong>Static Assets:</strong> CSS, JavaScript, and image files are cached to speed up page loading</li>
                            <li><strong>Translation Files:</strong> Language files are cached locally to provide instant language switching</li>
                            <li><strong>Emergency Alerts:</strong> Recent alert data may be temporarily cached for offline viewing</li>
                            <li><strong>User Preferences:</strong> Your settings are cached for quick access</li>
                        </ul>
                        <div class="highlight-box">
                            <strong>Note:</strong> Cached emergency alert information is stored locally on your device only and is regularly updated when you have an internet connection.
                        </div>
                    </div>

                    <div class="policy-section">
                        <h2><i class="fas fa-shield-alt"></i> Your Privacy and Security</h2>
                        <p>We take your privacy seriously. Here's how we protect you:</p>
                        <ul>
                            <li>We do not use cookies to track your browsing activity on other websites</li>
                            <li>We do not sell or share cookie data with third parties</li>
                            <li>All cookies are encrypted and transmitted securely (HTTPS)</li>
                            <li>Session cookies are automatically deleted when you close your browser</li>
                            <li>Authentication cookies contain encrypted tokens, not your actual password</li>
                        </ul>
                    </div>

                    <div class="policy-section">
                        <h2><i class="fas fa-cog"></i> Managing Cookies and Cache</h2>
                        <p>You can control cookies and cache through your browser settings:</p>
                        
                        <h3 style="margin-top: 1rem; color: #2e7d32;">To Clear Cookies:</h3>
                        <ul>
                            <li><strong>Chrome:</strong> Settings → Privacy and security → Clear browsing data → Cookies</li>
                            <li><strong>Firefox:</strong> Settings → Privacy & Security → Cookies and Site Data → Clear Data</li>
                            <li><strong>Safari:</strong> Preferences → Privacy → Manage Website Data → Remove All</li>
                            <li><strong>Edge:</strong> Settings → Privacy, search, and services → Clear browsing data</li>
                        </ul>

                        <h3 style="margin-top: 1rem; color: #2e7d32;">To Clear Cache:</h3>
                        <ul>
                            <li><strong>Chrome:</strong> Press Ctrl+Shift+Delete (Windows) or Cmd+Shift+Delete (Mac)</li>
                            <li><strong>Firefox:</strong> Press Ctrl+Shift+Delete (Windows) or Cmd+Shift+Delete (Mac)</li>
                            <li><strong>Safari:</strong> Develop menu → Empty Caches</li>
                            <li><strong>Edge:</strong> Press Ctrl+Shift+Delete</li>
                        </ul>

                        <div class="highlight-box">
                            <strong>Important:</strong> Clearing cookies will log you out of the system. You will need to log in again after clearing cookies.
                        </div>
                    </div>

                    <div class="policy-section">
                        <h2><i class="fas fa-ban"></i> Disabling Cookies</h2>
                        <p>Most web browsers allow you to refuse cookies. However, please note that:</p>
                        <ul>
                            <li>Disabling cookies will prevent you from logging into the system</li>
                            <li>Language and theme preferences will not be saved</li>
                            <li>Some features may not function properly</li>
                            <li>You may need to re-enter information more frequently</li>
                        </ul>
                        <p>For emergency communication purposes, we recommend keeping cookies enabled to ensure you receive timely alerts and notifications.</p>
                    </div>

                    <div class="policy-section">
                        <h2><i class="fas fa-sync-alt"></i> Updates to This Policy</h2>
                        <p>We may update this Cookie & Cache Policy from time to time to reflect changes in technology, legislation, or our services. We will notify you of any significant changes by posting the updated policy on this page with a new effective date.</p>
                    </div>

                    <div class="policy-section">
                        <h2><i class="fas fa-envelope"></i> Contact Us</h2>
                        <p>If you have any questions about our Cookie & Cache Policy, please contact us:</p>
                        <p><strong>Email:</strong> support@lgu4-emergency.gov.ph</p>
                        <p><strong>Phone:</strong> (02) 8-XXX-XXXX</p>
                        <p><strong>Address:</strong> LGU #4 Emergency Communication System, Quezon City</p>
                    </div>

                    <a href="index.php" class="back-btn">
                        <i class="fas fa-arrow-left"></i> Back to Home
                    </a>
                </div>
            </div>
        </div>
    </main>

    <?php include 'includes/footer-snippet.php'; ?>
</body>
</html>
