<?php
// User landing page that reuses the admin header/footer styling assets
$assetBase = '../ADMIN/header/';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Portal</title>
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
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <button class="sidebar-toggle-btn" aria-label="Toggle menu" onclick="window.sidebarToggle()">
        <i class="fas fa-bars"></i>
    </button>

    <main class="main-content">
        <div class="hero-section" id="features">
            <div class="main-container">
                <div class="sub-container">
                    <h1>Emergency Communication System</h1>
                    <p>Stay informed with real-time alerts, multilingual support, and two-way communication across web, SMS, email, and PA systems.</p>
                    <div class="hero-buttons">
                        <a href="#modules" class="btn btn-primary">Explore Features</a>
                        <a href="#alerts" class="btn btn-secondary">See Alerts</a>
                    </div>
                </div>
            </div>
        </div>

        <div class="main-container">
            <div class="sub-container">
                <section id="modules" class="page-content">
                    <h2>User-Facing Capabilities</h2>
                    <p>Access all emergency communication tools in one place.</p>
                    <div class="cards-grid">
                        <div class="card">
                            <h3>Mass Notification</h3>
                            <p>Receive coordinated SMS, email, and PA announcements for critical events.</p>
                        </div>
                        <div class="card">
                            <h3>Alert Categorization</h3>
                            <p>Weather, earthquake, bomb threat, and more—clearly tagged so you know what to do.</p>
                        </div>
                        <div class="card">
                            <h3>Two-Way Communication</h3>
                            <p>Reply to alerts to confirm safety, request help, or share field updates.</p>
                        </div>
                        <div class="card">
                            <h3>Automated Warnings</h3>
                            <p>Integrated with PAGASA, PHIVOLCS, and other trusted feeds for early warnings.</p>
                        </div>
                        <div class="card">
                            <h3>Multilingual Support</h3>
                            <p>Alerts available in multiple languages for inclusive communication.</p>
                        </div>
                        <div class="card">
                            <h3>Subscriptions & Preferences</h3>
                            <p>Opt in by channel and category to receive only what matters to you.</p>
                        </div>
                        <div class="card">
                            <h3>Audit & Transparency</h3>
                            <p>Log of sent notifications for accountability and follow-up.</p>
                        </div>
                    </div>
                </section>

                <section id="alerts" class="page-content">
                    <h2>Live & Recent Alerts</h2>
                    <p>View real-time and historical alerts with categories and statuses.</p>
                    <div class="cards-grid">
                        <div class="card">
                            <h4>Weather Advisory</h4>
                            <p>Rainfall alert from PAGASA. Stay indoors if possible.</p>
                            <button class="btn btn-primary">Acknowledge</button>
                        </div>
                        <div class="card">
                            <h4>Earthquake Update</h4>
                            <p>Aftershock notice from PHIVOLCS. Expect minor tremors.</p>
                            <button class="btn btn-secondary">View Details</button>
                        </div>
                    </div>
                </section>

                <section id="profile" class="page-content">
                    <h2>Profile & Preferences</h2>
                    <p>Manage your contact methods, preferred languages, and alert categories.</p>
                    <div class="cards-grid">
                        <div class="card">
                            <h4>Contact Channels</h4>
                            <p>Update phone, email, and notification channels.</p>
                            <button class="btn btn-primary">Manage Channels</button>
                        </div>
                        <div class="card">
                            <h4>Alert Preferences</h4>
                            <p>Choose categories: Weather, Earthquake, Bomb Threat, Health, and more.</p>
                            <button class="btn btn-secondary">Edit Preferences</button>
                        </div>
                        <div class="card">
                            <h4>Languages</h4>
                            <p>Select your preferred language for alerts.</p>
                            <button class="btn btn-secondary">Set Language</button>
                        </div>
                    </div>
                </section>

                <section id="support" class="page-content">
                    <h2>Support & Resources</h2>
                    <p>Get guidance on responding to alerts and requesting assistance.</p>
                    <div class="cards-grid">
                        <div class="card">
                            <h4>How to Respond</h4>
                            <p>Step-by-step instructions for common alert types.</p>
                            <button class="btn btn-primary">View Guide</button>
                        </div>
                        <div class="card">
                            <h4>Contact Dispatch</h4>
                            <p>Reach emergency dispatch or your local incident commander.</p>
                            <button class="btn btn-secondary">Contact Now</button>
                        </div>
                        <div class="card">
                            <h4>Audit & History</h4>
                            <p>See what was sent and when for transparency.</p>
                            <button class="btn btn-secondary">Open Log</button>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </main>

    <?php include 'includes/footer-snippet.php'; ?>

    <!-- Guest Privacy Agreement Modal -->
    <div class="modal-backdrop" id="privacyModal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-shield-alt"></i> Privacy & Security Agreement</h3>
                <button type="button" class="modal-close" id="closePrivacyModal">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <div class="agreement-content">
                    <div class="warning-box">
                        <i class="fas fa-exclamation-triangle"></i>
                        <h4>Important: Anonymous Guest Access</h4>
                        <p>You are about to access the system as an anonymous guest. Please read and accept the following terms:</p>
                    </div>
                    
                    <div class="agreement-section">
                        <h5><i class="fas fa-eye"></i> Activity Monitoring</h5>
                        <ul>
                            <li><strong>All actions are monitored and logged</strong> for security and safety purposes</li>
                            <li>Emergency calls and communications are <strong>recorded and monitored</strong></li>
                            <li>Your IP address, device information, and activity timestamps are logged</li>
                            <li>All data is stored securely and may be used for security analysis</li>
                        </ul>
                    </div>
                    
                    <div class="agreement-section">
                        <h5><i class="fas fa-phone"></i> Emergency Call Monitoring</h5>
                        <ul>
                            <li><strong>All emergency calls are monitored and recorded</strong></li>
                            <li>Call recordings may be used for emergency response and quality assurance</li>
                            <li>Your location may be tracked during emergency calls for faster response</li>
                            <li>Call data is retained for security and legal compliance purposes</li>
                        </ul>
                    </div>
                    
                    <div class="agreement-section">
                        <h5><i class="fas fa-lock"></i> Privacy & Data Protection</h5>
                        <ul>
                            <li>Your session is anonymous - no personal information is required</li>
                            <li>Session data is automatically deleted after 24 hours of inactivity</li>
                            <li>Monitoring data is used solely for security and emergency response</li>
                            <li>We comply with data protection regulations</li>
                        </ul>
                    </div>
                    
                    <div class="agreement-section">
                        <h5><i class="fas fa-info-circle"></i> Guest Access Limitations</h5>
                        <ul>
                            <li>Some features may be limited for guest users</li>
                            <li>To access full features, please create an account</li>
                            <li>Guest sessions expire after 24 hours</li>
                        </ul>
                    </div>
                    
                    <div class="agreement-checkbox">
                        <label>
                            <input type="checkbox" id="agreePrivacy" required>
                            <span>I understand and agree that:</span>
                        </label>
                        <ul class="agreement-points">
                            <li>All my actions, including calls, are monitored and logged</li>
                            <li>My IP address and device information will be recorded</li>
                            <li>Emergency calls are recorded for security and response purposes</li>
                            <li>I am accessing this system as an anonymous guest</li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" id="cancelGuestLogin">
                    <i class="fas fa-times"></i> Cancel
                </button>
                <button type="button" class="btn btn-primary" id="acceptAndContinue" disabled>
                    <i class="fas fa-check"></i> Accept & Continue as Guest
                </button>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="<?= $assetBase ?>js/mobile-menu.js"></script>
    <script src="<?= $assetBase ?>js/theme-toggle.js"></script>
    <style>
        /* Privacy Agreement Modal */
        .modal-backdrop {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.75);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 10000 !important;
            padding: 1rem;
            overflow-y: auto;
            backdrop-filter: blur(4px);
        }
        
        /* Prevent text selection highlighting in modal */
        .modal-backdrop * {
            -webkit-tap-highlight-color: transparent;
        }
        
        .modal-backdrop::selection,
        .modal-backdrop *::selection {
            background: transparent;
            color: inherit;
        }
        
        .modal-backdrop::-moz-selection,
        .modal-backdrop *::-moz-selection {
            background: transparent;
            color: inherit;
        }
        
        /* Prevent accidental text selection on mouse drag */
        .modal-content {
            -webkit-user-drag: none;
            -khtml-user-drag: none;
            -moz-user-drag: none;
            -o-user-drag: none;
            user-drag: none;
        }
        
        .modal-content {
            background: var(--card-bg);
            border-radius: 16px;
            max-width: 600px;
            width: 100%;
            height: auto;
            max-height: 90vh;
            display: flex;
            flex-direction: column;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.6);
            border: 1px solid var(--card-border);
            position: relative;
            z-index: 10001 !important;
            margin: auto;
            animation: modalFadeIn 0.3s ease-out;
            user-select: none;
            -webkit-user-select: none;
            -moz-user-select: none;
            -ms-user-select: none;
        }
        
        @keyframes modalFadeIn {
            from {
                opacity: 0;
                transform: scale(0.95) translateY(-10px);
            }
            to {
                opacity: 1;
                transform: scale(1) translateY(0);
            }
        }
        
        .modal-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1.5rem;
            border-bottom: 1px solid var(--card-border);
            flex-shrink: 0;
        }
        
        .modal-header h3 {
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            color: var(--text-color-1);
            user-select: none;
            -webkit-user-select: none;
            -moz-user-select: none;
            -ms-user-select: none;
        }
        
        .modal-header h3 i {
            color: var(--primary-color-1, #4c8a89);
        }
        
        .modal-close {
            background: none;
            border: none;
            font-size: 1.5rem;
            color: var(--text-muted);
            cursor: pointer;
            padding: 0.5rem;
            line-height: 1;
            transition: color 0.3s ease;
        }
        
        .modal-close:hover {
            color: var(--text-color-1);
        }
        
        .modal-body {
            padding: 1.5rem;
            overflow-y: auto;
            overflow-x: hidden;
            flex: 1;
            min-height: 0;
            max-height: calc(85vh - 200px);
            -webkit-overflow-scrolling: touch;
            padding-bottom: 1rem;
        }
        
        .modal-body::-webkit-scrollbar {
            width: 6px;
        }
        
        .modal-body::-webkit-scrollbar-track {
            background: transparent;
        }
        
        .modal-body::-webkit-scrollbar-thumb {
            background: rgba(0, 0, 0, 0.2);
            border-radius: 3px;
        }
        
        .modal-body::-webkit-scrollbar-thumb:hover {
            background: rgba(0, 0, 0, 0.3);
        }
        
        .modal-footer {
            padding: 1.25rem 1.5rem;
            border-top: 1px solid var(--card-border);
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
            flex-shrink: 0;
            background: var(--card-bg);
            border-radius: 0 0 16px 16px;
            margin-top: auto;
        }
        
        .agreement-content {
            line-height: 1.6;
            user-select: none;
            -webkit-user-select: none;
            -moz-user-select: none;
            -ms-user-select: none;
        }
        
        .warning-box {
            background: rgba(255, 193, 7, 0.1);
            border: 2px solid rgba(255, 193, 7, 0.3);
            border-radius: 8px;
            padding: 0.875rem 1rem;
            margin-bottom: 1.25rem;
        }
        
        .warning-box i {
            color: #ffc107;
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
            display: block;
        }
        
        .warning-box h4 {
            margin: 0.5rem 0;
            color: #856404;
            user-select: none;
            -webkit-user-select: none;
            -moz-user-select: none;
            -ms-user-select: none;
        }
        
        .warning-box p {
            margin: 0.5rem 0 0 0;
            color: #856404;
            user-select: none;
            -webkit-user-select: none;
            -moz-user-select: none;
            -ms-user-select: none;
        }
        
        .agreement-section {
            margin-bottom: 1.25rem;
        }
        
        .agreement-section:last-of-type {
            margin-bottom: 1rem;
        }
        
        .agreement-section h5 {
            margin: 0 0 0.75rem 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--text-color-1);
            user-select: none;
            -webkit-user-select: none;
            -moz-user-select: none;
            -ms-user-select: none;
        }
        
        .agreement-section h5 i {
            color: var(--primary-color-1, #4c8a89);
        }
        
        .agreement-section ul {
            margin: 0;
            padding-left: 1.5rem;
        }
        
        .agreement-section li {
            margin-bottom: 0.5rem;
            color: var(--text-color-1);
            user-select: none;
            -webkit-user-select: none;
            -moz-user-select: none;
            -ms-user-select: none;
        }
        
        .agreement-section li strong {
            color: #dc3545;
        }
        
        .agreement-checkbox {
            background: rgba(76, 138, 137, 0.05);
            border: 1px solid rgba(76, 138, 137, 0.2);
            border-radius: 8px;
            padding: 1rem;
            margin-top: 1rem;
            margin-bottom: 0.5rem;
            user-select: none;
            -webkit-user-select: none;
            -moz-user-select: none;
            -ms-user-select: none;
        }
        
        .agreement-checkbox label {
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
            cursor: pointer;
            font-weight: 500;
            color: var(--text-color-1);
            user-select: none;
            -webkit-user-select: none;
            -moz-user-select: none;
            -ms-user-select: none;
        }
        
        .agreement-checkbox input[type="checkbox"] {
            width: 20px;
            height: 20px;
            margin-top: 2px;
            cursor: pointer;
            accent-color: var(--primary-color-1, #4c8a89);
            flex-shrink: 0;
        }
        
        .agreement-checkbox input[type="checkbox"]:focus {
            outline: 2px solid var(--primary-color-1, #4c8a89);
            outline-offset: 2px;
        }
        
        .agreement-points {
            margin: 0.75rem 0 0 0;
            padding-left: 1.5rem;
            list-style: disc;
        }
        
        .agreement-points li {
            margin-bottom: 0.5rem;
            color: var(--text-color-1);
            user-select: none;
            -webkit-user-select: none;
            -moz-user-select: none;
            -ms-user-select: none;
            -webkit-tap-highlight-color: transparent;
        }
        
        .agreement-points li:focus,
        .agreement-section li:focus {
            outline: none;
        }
        
        .modal-footer .btn {
            min-width: 120px;
            cursor: pointer;
            pointer-events: auto;
        }
        
        .modal-footer .btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        /* Hide other elements when modal is open */
        body.modal-open {
            overflow: hidden !important;
        }
        
        body.modal-open .auth-icons,
        body.modal-open .chat-fab,
        body.modal-open .chat-modal,
        body.modal-open .theme-toggle,
        body.modal-open .sidebar-toggle-btn {
            z-index: 1 !important;
            pointer-events: none;
            opacity: 0.3;
        }
        
        /* Ensure modal is always on top */
        .modal-backdrop.show {
            display: flex !important;
        }
        
        @media (max-width: 768px) {
            .modal-backdrop {
                padding: 0.5rem;
            }
            
            .modal-content {
                max-height: 95vh;
                max-width: 100%;
                border-radius: 12px;
            }
            
            .modal-header {
                padding: 1rem 1.25rem;
            }
            
            .modal-body {
                padding: 1.25rem;
                max-height: calc(95vh - 180px);
            }
            
            .modal-footer {
                padding: 1rem 1.25rem;
                flex-direction: column-reverse;
            }
            
            .modal-footer .btn {
                width: 100%;
            }
            
            .modal-footer .btn-primary {
                order: -1;
            }
            
            .agreement-section {
                margin-bottom: 1rem;
            }
            
            .warning-box {
                padding: 0.75rem;
                margin-bottom: 1rem;
            }
        }
        
        [data-theme="dark"] .modal-content {
            background: #1c1c1f;
            border-color: #2a2d34;
        }
        
        [data-theme="dark"] .warning-box {
            background: rgba(255, 193, 7, 0.15);
            border-color: rgba(255, 193, 7, 0.4);
        }
        
        [data-theme="dark"] .warning-box h4,
        [data-theme="dark"] .warning-box p {
            color: #ffc107;
        }
        
        [data-theme="dark"] .agreement-section li strong {
            color: #ff6b6b;
        }
    </style>
    <script>
        // Privacy Agreement Modal
        document.addEventListener('DOMContentLoaded', function() {
            const privacyModal = document.getElementById('privacyModal');
            const agreeCheckbox = document.getElementById('agreePrivacy');
            const acceptButton = document.getElementById('acceptAndContinue');
            const cancelButton = document.getElementById('cancelGuestLogin');
            const closeModal = document.getElementById('closePrivacyModal');
            
            // Check if user has already accepted (stored in sessionStorage)
            const agreementAccepted = sessionStorage.getItem('privacyAgreementAccepted');
            if (!agreementAccepted) {
                // Show modal on page load
                showPrivacyModal();
            }
            
            // Enable/disable accept button based on checkbox
            if (agreeCheckbox) {
                agreeCheckbox.addEventListener('change', function() {
                    if (acceptButton) {
                        acceptButton.disabled = !this.checked;
                    }
                });
            }
            
            // Show privacy modal function
            function showPrivacyModal() {
                if (!privacyModal) return;
                
                // Clear any existing text selection
                if (window.getSelection) {
                    window.getSelection().removeAllRanges();
                } else if (document.selection) {
                    document.selection.empty();
                }
                
                privacyModal.style.display = 'flex';
                privacyModal.classList.add('show');
                document.body.classList.add('modal-open');
                
                if (agreeCheckbox) {
                    agreeCheckbox.checked = false;
                }
                if (acceptButton) {
                    acceptButton.disabled = true;
                }
                
                // Focus on checkbox for accessibility
                setTimeout(() => {
                    if (agreeCheckbox) {
                        agreeCheckbox.focus();
                    }
                }, 100);
            }
            
            // Close modal functions
            function closePrivacyModal() {
                if (!privacyModal) return;
                
                // Clear any text selection before closing
                if (window.getSelection) {
                    window.getSelection().removeAllRanges();
                } else if (document.selection) {
                    document.selection.empty();
                }
                
                privacyModal.style.display = 'none';
                privacyModal.classList.remove('show');
                document.body.classList.remove('modal-open');
                
                if (agreeCheckbox) {
                    agreeCheckbox.checked = false;
                }
                if (acceptButton) {
                    acceptButton.disabled = true;
                }
            }
            
            if (closeModal) {
                closeModal.addEventListener('click', closePrivacyModal);
            }
            if (cancelButton) {
                cancelButton.addEventListener('click', closePrivacyModal);
            }
            
            // Click outside modal to close (only on backdrop, not content)
            if (privacyModal) {
                privacyModal.addEventListener('click', function(e) {
                    if (e.target === privacyModal) {
                        closePrivacyModal();
                    }
                });
            }
            
            // Prevent modal content clicks from closing modal
            const modalContent = privacyModal ? privacyModal.querySelector('.modal-content') : null;
            if (modalContent) {
                modalContent.addEventListener('click', function(e) {
                    e.stopPropagation();
                });
            }
            
            // Close on Escape key
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && privacyModal && privacyModal.classList.contains('show')) {
                    closePrivacyModal();
                }
            });
            
            // Accept and continue as guest
            if (acceptButton) {
                acceptButton.addEventListener('click', async function() {
                    if (!agreeCheckbox || !agreeCheckbox.checked) {
                        return;
                    }
                    
                    // Store acceptance in sessionStorage
                    sessionStorage.setItem('privacyAgreementAccepted', 'true');
                    
                    closePrivacyModal();
                    
                    try {
                        const response = await fetch('api/user-login.php', {
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
                                icon: 'info',
                                title: 'Anonymous Guest Access',
                                html: `
                                    <div style="text-align: left; padding: 1rem;">
                                        <p><strong>You are now browsing as an anonymous guest.</strong></p>
                                        <hr style="margin: 1rem 0;">
                                        <p style="color: #dc3545;"><i class="fas fa-exclamation-triangle"></i> <strong>Important:</strong></p>
                                        <ul style="text-align: left; margin: 0.5rem 0;">
                                            <li>All your actions are <strong>monitored and logged</strong></li>
                                            <li>Emergency calls are <strong>recorded and monitored</strong></li>
                                            <li>Your IP address and device information are tracked</li>
                                            <li>Session expires after 24 hours</li>
                                        </ul>
                                        <p style="margin-top: 1rem; font-size: 0.9rem;">Some features may be limited. Create an account for full access.</p>
                                    </div>
                                `,
                                confirmButtonText: 'I Understand',
                                confirmButtonColor: '#4c8a89',
                                allowOutsideClick: false
                            });
                        } else {
                            console.error('Guest login failed:', data.message);
                        }
                    } catch (error) {
                        console.error('Guest login error:', error);
                    }
                });
            }
        });
    </script>
</body>
</html>