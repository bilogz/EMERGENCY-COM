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
    <title>Terms of Service - LGU #4 Emergency Communication System</title>
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
        .warning-box {
            background: rgba(244, 67, 54, 0.1);
            border-left: 4px solid #f44336;
            padding: 1rem;
            margin: 1rem 0;
            border-radius: 0 8px 8px 0;
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
                        <h1><i class="fas fa-file-contract"></i> Terms of Service</h1>
                        <p>LGU #4 Emergency Communication System</p>
                        <p><strong>Effective Date:</strong> February 13, 2026</p>
                    </div>

                    <div class="policy-section">
                        <h2><i class="fas fa-handshake"></i> Acceptance of Terms</h2>
                        <p>By accessing or using the LGU #4 Emergency Communication System, you agree to be bound by these Terms of Service. If you do not agree to these terms, please do not use our services. These terms constitute a legally binding agreement between you and the Local Government Unit of District 4, Quezon City.</p>
                    </div>

                    <div class="policy-section">
                        <h2><i class="fas fa-bullseye"></i> Purpose of Service</h2>
                        <p>The LGU #4 Emergency Communication System is a public service platform designed to:</p>
                        <ul>
                            <li>Provide timely emergency alerts and public safety notifications</li>
                            <li>Facilitate communication between citizens and emergency responders</li>
                            <li>Coordinate disaster preparedness and response efforts</li>
                            <li>Enable two-way communication during emergencies</li>
                        </ul>
                        <div class="highlight-box">
                            <strong>Note:</strong> This system is intended for residents of Barangays in District 4, Quezon City. Service availability may vary based on location and network conditions.
                        </div>
                    </div>

                    <div class="policy-section">
                        <h2><i class="fas fa-user-check"></i> Eligibility</h2>
                        <p>To use this service, you must:</p>
                        <ul>
                            <li>Be at least 18 years of age or have parental/guardian consent</li>
                            <li>Be a resident of District 4, Quezon City, or have legitimate business in the area</li>
                            <li>Provide accurate and truthful information during registration</li>
                            <li>Have a valid mobile phone number registered in the Philippines</li>
                        </ul>
                    </div>

                    <div class="policy-section">
                        <h2><i class="fas fa-user-circle"></i> User Accounts</h2>
                        <p>When you create an account, you agree to:</p>
                        <ul>
                            <li>Provide accurate, current, and complete information</li>
                            <li>Maintain the security of your account credentials</li>
                            <li>Promptly update your information if it changes</li>
                            <li>Accept responsibility for all activities under your account</li>
                            <li>Notify us immediately of any unauthorized access</li>
                        </ul>
                        <div class="warning-box">
                            <strong>Important:</strong> You are responsible for maintaining the confidentiality of your account. Do not share your login credentials with others.
                        </div>
                    </div>

                    <div class="policy-section">
                        <h2><i class="fas fa-gavel"></i> Acceptable Use</h2>
                        <p>You agree to use the Emergency Communication System responsibly and lawfully. The following activities are strictly prohibited:</p>
                        <ul>
                            <li>Providing false or misleading information</li>
                            <li>Impersonating any person or entity</li>
                            <li>Using the system for non-emergency purposes in a way that interferes with emergency services</li>
                            <li>Transmitting spam, viruses, or malicious code</li>
                            <li>Attempting to gain unauthorized access to the system</li>
                            <li>Interfering with or disrupting the integrity of the service</li>
                            <li>Using automated systems to access the service without authorization</li>
                            <li>Making false emergency reports or prank calls</li>
                        </ul>
                    </div>

                    <div class="policy-section">
                        <h2><i class="fas fa-ban"></i> Prohibited Content</h2>
                        <p>You may not post or transmit content that:</p>
                        <ul>
                            <li>Is unlawful, harmful, threatening, abusive, or defamatory</li>
                            <li>Contains hate speech or promotes discrimination</li>
                            <li>Is sexually explicit or obscene</li>
                            <li>Infringes on intellectual property rights</li>
                            <li>Contains personal information of others without consent</li>
                            <li>Constitutes spam or unauthorized advertising</li>
                        </ul>
                    </div>

                    <div class="policy-section">
                        <h2><i class="fas fa-exclamation-triangle"></i> Emergency Use Disclaimer</h2>
                        <p>While we strive to provide reliable emergency communication services:</p>
                        <ul>
                            <li>Service availability depends on network conditions and infrastructure</li>
                            <li>Response times may vary based on emergency severity and resource availability</li>
                            <li>In life-threatening emergencies, always call 911 or emergency hotlines directly</li>
                            <li>This system supplements but does not replace official emergency services</li>
                        </ul>
                        <div class="warning-box">
                            <strong>Critical:</strong> For immediate life-threatening emergencies, dial 911 or contact emergency services directly. Do not rely solely on this application.
                        </div>
                    </div>

                    <div class="policy-section">
                        <h2><i class="fas fa-shield-alt"></i> Privacy and Data Protection</h2>
                        <p>Your use of this service is also governed by our <a href="privacy-policy.php">Privacy Policy</a>. By using the service, you consent to the collection and use of your information as described in the Privacy Policy, in compliance with the Data Privacy Act of 2012 (Republic Act No. 10173).</p>
                    </div>

                    <div class="policy-section">
                        <h2><i class="fas fa-copyright"></i> Intellectual Property</h2>
                        <p>All content, features, and functionality of the Emergency Communication System, including but not limited to text, graphics, logos, and software, are the property of LGU #4 Quezon City and are protected by Philippine and international copyright, trademark, and other intellectual property laws.</p>
                    </div>

                    <div class="policy-section">
                        <h2><i class="fas fa-times-circle"></i> Termination</h2>
                        <p>We reserve the right to suspend or terminate your account at any time for:</p>
                        <ul>
                            <li>Violation of these Terms of Service</li>
                            <li>Providing false information</li>
                            <li>Misuse of the emergency communication system</li>
                            <li>Extended periods of inactivity</li>
                            <li>Legal requirements or government requests</li>
                        </ul>
                        <p>Upon termination, your right to use the service will immediately cease.</p>
                    </div>

                    <div class="policy-section">
                        <h2><i class="fas fa-exclamation-circle"></i> Limitation of Liability</h2>
                        <p>To the maximum extent permitted by law, LGU #4 Quezon City shall not be liable for:</p>
                        <ul>
                            <li>Any indirect, incidental, special, or consequential damages</li>
                            <li>Loss of data, profits, or business opportunities</li>
                            <li>Service interruptions or technical failures</li>
                            <li>Delays in emergency response or communication</li>
                            <li>Actions taken based on information provided through the system</li>
                        </ul>
                    </div>

                    <div class="policy-section">
                        <h2><i class="fas fa-balance-scale"></i> Governing Law</h2>
                        <p>These Terms of Service shall be governed by and construed in accordance with the laws of the Republic of the Philippines. Any disputes arising from these terms shall be subject to the exclusive jurisdiction of the courts of Quezon City.</p>
                    </div>

                    <div class="policy-section">
                        <h2><i class="fas fa-sync-alt"></i> Changes to Terms</h2>
                        <p>We may modify these Terms of Service at any time. Changes will be effective immediately upon posting. Your continued use of the service after changes constitutes acceptance of the modified terms. We will notify users of significant changes through the application or via email.</p>
                    </div>

                    <div class="policy-section">
                        <h2><i class="fas fa-envelope"></i> Contact Information</h2>
                        <p>If you have questions about these Terms of Service, please contact us:</p>
                        <p><strong>Email:</strong> support@lgu4-emergency.gov.ph</p>
                        <p><strong>Phone:</strong> (02) 8-XXX-XXXX</p>
                        <p><strong>Address:</strong> LGU #4 Emergency Communication Center, Quezon City Hall, Quezon City</p>
                        <p><strong>Office Hours:</strong> Monday to Friday, 8:00 AM - 5:00 PM</p>
                    </div>

                    <div class="highlight-box">
                        <strong>Agreement:</strong> By clicking "I Agree" during registration or by continuing to use the LGU #4 Emergency Communication System, you acknowledge that you have read, understood, and agree to be bound by these Terms of Service and our Privacy Policy.
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php include 'includes/footer-snippet.php'; ?>
</body>
</html>
