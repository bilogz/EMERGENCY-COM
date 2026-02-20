<?php
// Include centralized session configuration - MUST be first
require_once __DIR__ . '/../session-config.php';

$assetBase = '../ADMIN/header/';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Privacy Policy - LGU #4 Emergency Communication System</title>
    <link rel="icon" type="image/x-icon" href="<?= $assetBase ?>images/favicon.ico">
    <link rel="stylesheet" href="<?= $assetBase ?>css/global.css">
    <link rel="stylesheet" href="<?= $assetBase ?>css/buttons.css">
    <link rel="stylesheet" href="../ADMIN/sidebar/css/global.css">
    <link rel="stylesheet" href="../ADMIN/sidebar/css/sidebar.css">
    <link rel="stylesheet" href="../ADMIN/sidebar/css/content.css">
    <link rel="stylesheet" href="../ADMIN/sidebar/css/admin-header.css">
    <link rel="stylesheet" href="css/user.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .policy-container {
            max-width: 900px;
            margin: 0 auto;
            padding: 2rem;
            background: var(--card-bg, #ffffff);
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
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
        .contact-info {
            background: rgba(76, 175, 80, 0.05);
            padding: 1.5rem;
            border-radius: 8px;
            margin-top: 2rem;
        }
        .contact-info h3 {
            color: #2e7d32;
            margin-bottom: 1rem;
        }
        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1rem;
            color: #4caf50;
            text-decoration: none;
            font-weight: 500;
        }
        .back-btn:hover {
            color: #2e7d32;
        }
        [data-theme="dark"] .policy-container {
            background: #1a1a1a;
        }
        [data-theme="dark"] .policy-section p,
        [data-theme="dark"] .policy-section ul {
            color: #e0e0e0;
        }
    </style>
</head>
<body class="user-admin-header">
    <?php include 'includes/user-global-header.php'; ?>

    <main class="main-content" style="padding-top: 60px;">
        <div class="main-container">
            <div class="sub-container content-main">
                <a href="javascript:history.back()" class="back-btn">
                    <i class="fas fa-arrow-left"></i> Back
                </a>
                
                <div class="policy-container">
                    <div class="policy-header">
                        <h1><i class="fas fa-shield-alt"></i> Privacy Policy</h1>
                        <p>LGU #4 Emergency Communication System</p>
                        <p><strong>Effective Date:</strong> February 13, 2026</p>
                    </div>

                    <div class="policy-section">
                        <h2><i class="fas fa-info-circle"></i> Introduction</h2>
                        <p>Welcome to the LGU #4 Emergency Communication System. We are committed to protecting your personal information and your right to privacy. This Privacy Policy explains how we collect, use, store, and protect your personal data in accordance with the Data Privacy Act of 2012 (Republic Act No. 10173).</p>
                    </div>

                    <div class="policy-section">
                        <h2><i class="fas fa-database"></i> Information We Collect</h2>
                        <p>We collect the following personal information to provide emergency communication services:</p>
                        <ul>
                            <li><strong>Full Name</strong> - For identification and communication purposes</li>
                            <li><strong>Email Address</strong> - For account verification and emergency notifications</li>
                            <li><strong>Mobile Number</strong> - Primary contact for SMS alerts and emergency calls</li>
                            <li><strong>Address Information</strong> - Barangay, district, house number, and street for location-based emergency response</li>
                            <li><strong>Nationality</strong> - For demographic purposes</li>
                            <li><strong>Location Data</strong> - When you use location-based features</li>
                        </ul>
                    </div>

                    <div class="policy-section">
                        <h2><i class="fas fa-bullseye"></i> How We Use Your Information</h2>
                        <p>Your personal information is used solely for the following purposes:</p>
                        <ul>
                            <li>Sending emergency alerts and public safety notifications</li>
                            <li>Coordinating emergency response efforts</li>
                            <li>Verifying your identity for account security</li>
                            <li>Improving our emergency communication services</li>
                            <li>Complying with legal obligations</li>
                        </ul>
                        <div class="highlight-box">
                            <strong>Important:</strong> We will never use your personal information for commercial purposes or share it with third parties for marketing.
                        </div>
                    </div>

                    <div class="policy-section">
                        <h2><i class="fas fa-lock"></i> Data Security</h2>
                        <p>We implement strict security measures to protect your personal information:</p>
                        <ul>
                            <li>Encryption of sensitive data in transit and at rest</li>
                            <li>Secure database with access controls</li>
                            <li>Regular security audits and vulnerability assessments</li>
                            <li>Limited access to authorized personnel only</li>
                            <li>Compliance with government data security standards</li>
                        </ul>
                    </div>

                    <div class="policy-section">
                        <h2><i class="fas fa-share-alt"></i> Information Sharing</h2>
                        <p>We only share your personal information in the following circumstances:</p>
                        <ul>
                            <li>With emergency response agencies during actual emergencies</li>
                            <li>When required by law or court order</li>
                            <li>To protect the rights, property, or safety of our users or the public</li>
                        </ul>
                    </div>

                    <div class="policy-section">
                        <h2><i class="fas fa-user-shield"></i> Your Rights</h2>
                        <p>Under the Data Privacy Act of 2012, you have the following rights:</p>
                        <ul>
                            <li><strong>Right to Access</strong> - Request a copy of your personal data</li>
                            <li><strong>Right to Correction</strong> - Request corrections to inaccurate or incomplete data</li>
                            <li><strong>Right to Erasure</strong> - Request deletion of your personal data</li>
                            <li><strong>Right to Object</strong> - Object to the processing of your data</li>
                            <li><strong>Right to Withdraw Consent</strong> - Withdraw your consent at any time</li>
                        </ul>
                    </div>

                    <div class="policy-section">
                        <h2><i class="fas fa-clock"></i> Data Retention</h2>
                        <p>We retain your personal information for as long as necessary to fulfill the purposes outlined in this Privacy Policy, unless a longer retention period is required by law. When you delete your account, we will securely delete or anonymize your personal data within 30 days.</p>
                    </div>

                    <div class="policy-section">
                        <h2><i class="fas fa-cookie-bite"></i> Cookies and Tracking</h2>
                        <p>We use cookies and similar technologies to:</p>
                        <ul>
                            <li>Maintain your session and login status</li>
                            <li>Remember your preferences (language, theme)</li>
                            <li>Analyze system performance and improve user experience</li>
                        </ul>
                        <p>You can control cookie settings through your browser preferences.</p>
                    </div>

                    <div class="policy-section">
                        <h2><i class="fas fa-sync-alt"></i> Updates to This Policy</h2>
                        <p>We may update this Privacy Policy from time to time. We will notify you of any changes by posting the new Privacy Policy on this page and updating the effective date. We encourage you to review this Privacy Policy periodically.</p>
                    </div>

                    <div class="contact-info">
                        <h3><i class="fas fa-envelope"></i> Contact Us</h3>
                        <p>If you have any questions about this Privacy Policy or wish to exercise your data privacy rights, please contact our Data Protection Officer:</p>
                        <p><strong>Email:</strong> dpo@lgu4-emergency.gov.ph</p>
                        <p><strong>Phone:</strong> (02) 8-XXX-XXXX</p>
                        <p><strong>Address:</strong> LGU #4 Emergency Communication Center, Quezon City Hall</p>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php include 'includes/footer-snippet.php'; ?>
</body>
</html>
