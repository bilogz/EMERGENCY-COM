<?php
$assetBase = '../ADMIN/header/';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Sign Up</title>
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
    <script src="js/translations.js?v=<?= @filemtime(__DIR__ . '/js/translations.js') ?>"></script>
    <script src="js/language-manager.js?v=<?= @filemtime(__DIR__ . '/js/language-manager.js') ?>"></script>
    <script src="js/global-translator.js?v=<?= @filemtime(__DIR__ . '/js/global-translator.js') ?>"></script>
    <script src="js/language-selector-modal.js?v=<?= @filemtime(__DIR__ . '/js/language-selector-modal.js') ?>"></script>
    <script src="js/language-sync.js?v=<?= @filemtime(__DIR__ . '/js/language-sync.js') ?>"></script>
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
<body>
    <?php include 'includes/sidebar.php'; ?>

    <button class="sidebar-toggle-btn" aria-label="Toggle menu" onclick="window.sidebarToggle()" data-no-translate>
        <i class="fas fa-bars"></i>
    </button>

    <main class="main-content">
        <div class="main-container">
            <div class="sub-container content-main">
                <section class="page-content">
                    <h2 data-translate="signup.title">Create an Account</h2>
                    <p data-translate="signup.subtitle">Sign up to receive alerts, manage your preferences, and access emergency tools.</p>
                    
                    <!-- Step 1: Basic Info + Email Verification -->
                    <form class="auth-form" id="signupForm" style="display: block;">
                        <div class="form-group">
                            <label for="full_name" data-translate="signup.fullName">Full Name</label>
                            <input type="text" id="full_name" name="full_name" placeholder="Juan Dela Cruz" required>
                        </div>
                        <div class="form-group">
                            <label for="email" data-translate="signup.email">Email Address</label>
                            <input type="email" id="email" name="email" placeholder="juan@example.com" required>
                        </div>
                        <div class="form-group">
                            <label for="nationality" data-translate="signup.nationality">Nationality</label>
                            <input list="nationalityList" id="nationality" name="nationality" placeholder="Select nationality" required>
                            <datalist id="nationalityList">
                                <option value="Filipino"></option>
                                <option value="American"></option>
                                <option value="Canadian"></option>
                                <option value="British"></option>
                                <option value="Australian"></option>
                                <option value="Japanese"></option>
                                <option value="Chinese"></option>
                                <option value="Korean"></option>
                                <option value="Indian"></option>
                                <option value="German"></option>
                            </datalist>
                        </div>
                        <div class="form-group">
                            <label for="phone" data-translate="signup.mobileNumber">Mobile Number</label>
                            <div class="input-with-prefix">
                                <span class="prefix">+63</span>
                                <input type="tel" id="phone" name="phone" pattern="[0-9]{10}" maxlength="10" placeholder="9XXXXXXXXX" title="Enter 10 digits without spaces" required autocomplete="tel">
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="district">
                                <i class="fas fa-map"></i> District (Quezon City)
                            </label>
                            <select id="district" name="district" required>
                                <option value="">Select District</option>
                                <option value="1">District 1</option>
                                <option value="2">District 2</option>
                                <option value="3">District 3</option>
                                <option value="4">District 4</option>
                                <option value="5">District 5</option>
                                <option value="6">District 6</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="barangay">
                                <i class="fas fa-map-marker-alt"></i> <span data-translate="signup.barangay">Barangay (Quezon City)</span>
                            </label>
                            <input type="text" id="barangay" name="barangay" placeholder="Select district first, then type to search barangay..." required autocomplete="off" disabled>
                            <div id="barangaySuggestions" class="suggestions-dropdown" style="display: none;"></div>
                        </div>
                        <div class="form-group">
                            <label for="house_number">House / Unit No.</label>
                            <input type="text" id="house_number" name="house_number" placeholder="e.g. #123" required>
                        </div>
                        <div class="form-group">
                            <label for="street">
                                <i class="fas fa-road"></i> Street (Quezon City)
                            </label>
                            <input type="text" id="street" name="street" placeholder="Enter your street name" required>
                        </div>
                        
                        <div class="error-message" id="errorMessage" style="display: none;">
                            <i class="fas fa-exclamation-circle"></i>
                            <span id="errorText"></span>
                        </div>

                        <!-- Data Privacy Consent Section -->
                        <div class="form-group privacy-consent-section" style="margin-top: 1.5rem; padding: 1rem; background: rgba(76, 175, 80, 0.05); border: 1px solid rgba(76, 175, 80, 0.2); border-radius: 8px;">
                            <div class="privacy-header" style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.75rem;">
                                <i class="fas fa-shield-alt" style="color: #4caf50;"></i>
                                <strong style="color: #2e7d32;">Consent to Collect and Process Personal Information</strong>
                            </div>
                            <div class="privacy-content" style="font-size: 0.85rem; color: var(--text-color, #333); line-height: 1.6; max-height: 200px; overflow-y: auto; padding: 0.75rem; background: var(--card-bg, #fff); border-radius: 6px; margin-bottom: 1rem;">
                                <p>By providing my personal information, including my full name, contact number, address, and location, I hereby give my explicit consent to <strong>LGU #4 EMERGENCY COMMUNICATION SYSTEM</strong> to collect, store, and process my personal data in accordance with the <strong>Data Privacy Act of 2012 (Republic Act No. 10173)</strong>.</p>
                                
                                <p>I understand that the information I provide will be used solely for the purposes of emergency communication, public safety notifications, and other related services, and that it will be handled with strict confidentiality and security measures to prevent unauthorized access, disclosure, or misuse.</p>
                                
                                <p>I acknowledge that <strong>LGU #4 EMERGENCY COMMUNICATION SYSTEM</strong> will only collect the minimum amount of personal information necessary to provide its services and that my data will not be shared with any third party except as required by law or for the execution of emergency response protocols.</p>
                                
                                <p>I understand that I have the right to access my personal information at any time and request corrections to any inaccurate or incomplete data. I also have the right to request the deletion of my personal information if I no longer wish to participate in the system or withdraw my consent.</p>
                                
                                <p>I am aware that providing my personal information is voluntary, but that refusal to provide certain information may limit my ability to receive timely emergency alerts and notifications.</p>
                                
                                <p>I further acknowledge that I may withdraw my consent at any time by contacting the designated Data Protection Officer of <strong>LGU #4 EMERGENCY COMMUNICATION SYSTEM</strong> through the provided contact details, and that such withdrawal will not affect the legality of any data processing conducted prior to my withdrawal.</p>
                            </div>
                            <label class="privacy-checkbox-label" style="display: flex; align-items: flex-start; gap: 0.75rem; cursor: pointer; font-size: 0.9rem;">
                                <input type="checkbox" id="privacyConsent" name="privacy_consent" style="margin-top: 0.2rem; width: 18px; height: 18px; accent-color: #4caf50; flex-shrink: 0;">
                                <span>I consent to the collection, storage, and processing of my personal information by <strong>LGU #4 EMERGENCY COMMUNICATION SYSTEM</strong>.</span>
                            </label>
                            <div class="privacy-error" id="privacyError" style="display: none; color: #dc3545; font-size: 0.85rem; margin-top: 0.5rem;">
                                <i class="fas fa-exclamation-circle"></i> You must consent to the data privacy terms to proceed.
                            </div>
                        </div>
                        
                        <div class="auth-actions">
                            <button type="submit" class="btn btn-primary" id="signupButton" data-no-translate>
                                <i class="fas fa-user-plus"></i>
                                <span class="btn-text">Sign Up</span>
                                <span class="btn-spinner" style="display: none;">
                                    <i class="fas fa-spinner fa-spin"></i>
                                </span>
                            </button>
                        </div>

                        <!-- Google OAuth Sign Up -->
                        <div class="auth-divider">
                            <span>OR</span>
                        </div>
                        <p style="text-align: center; color: var(--text-muted, #6b7280); font-size: 1rem; font-weight: 600; margin-bottom: 0.75rem;">Or sign up with</p>
                        <div class="alternative-login-buttons" style="display: flex; gap: 1rem; flex-wrap: wrap; margin-bottom: 1rem;">
                            <button type="button" id="googleSignupBtn" class="btn btn-google" data-no-translate style="flex: 1; min-width: 200px;">
                                <span class="google-logo-wrapper">
                                    <svg width="18" height="18" viewBox="0 0 18 18" xmlns="http://www.w3.org/2000/svg">
                                        <g fill="#000" fill-rule="evenodd">
                                            <path d="M9 3.48c1.69 0 2.83.73 3.48 1.34l2.54-2.48C13.46.89 11.43 0 9 0 5.48 0 2.44 2.02.96 4.96l2.91 2.26C4.6 5.05 6.62 3.48 9 3.48z" fill="#EA4335"/>
                                            <path d="M17.64 9.2c0-.74-.06-1.28-.19-1.84H9v3.34h4.96c-.21 1.18-.84 2.18-1.79 2.87l2.75 2.13c1.66-1.52 2.72-3.76 2.72-6.5z" fill="#4285F4"/>
                                            <path d="M3.88 10.78A5.54 5.54 0 0 1 3.58 9c0-.62.11-1.22.29-1.78L.96 4.96A9.008 9.008 0 0 0 0 9c0 1.45.35 2.82.96 4.04l2.92-2.26z" fill="#FBBC05"/>
                                            <path d="M9 18c2.43 0 4.47-.8 5.96-2.18l-2.75-2.13c-.76.53-1.78.9-3.21.9-2.38 0-4.4-1.57-5.12-3.74L.96 13.04C2.45 15.98 5.48 18 9 18z" fill="#34A853"/>
                                        </g>
                                    </svg>
                                </span>
                                <span class="google-text">Google</span>
                            </button>

                            <!-- Facebook Sign Up -->
                            <button type="button" id="facebookSignupBtn" class="btn btn-facebook" data-no-translate style="flex: 1; min-width: 200px;">
                                <span class="facebook-logo-wrapper">
                                    <i class="fab fa-facebook-f"></i>
                                </span>
                                <span class="facebook-text">Facebook</span>
                            </button>
                        </div>

                        <div class="auth-switch">
                            <span>Already have an account?</span>
                            <a href="login.php" class="btn btn-secondary login-btn" data-no-translate>
                                <i class="fas fa-sign-in-alt"></i>
                                <span>Login</span>
                            </a>
                        </div>
                    </form>
                    
                    <!-- OTP Verification Modal -->
                    <div id="otpModal" class="modal" aria-hidden="true" style="display:none;">
                        <div class="modal-backdrop"></div>
                        <div class="modal-content" role="dialog" aria-modal="true" aria-labelledby="otpModalTitle">
                            <button class="modal-close" id="otpModalClose" aria-label="Close">&times;</button>
                            <h3 id="otpModalTitle">Verify Your Email</h3>
                            <p class="modal-sub">We've sent a 6-digit verification code to <strong id="otpEmailDisplay"></strong></p>

                            <div id="otpSentBanner" style="display:none; margin-bottom:0.75rem; padding:0.75rem; border-radius:6px; background: rgba(40,167,69,0.12); border:1px solid rgba(40,167,69,0.2); color: #28a745;">Verification code sent successfully.</div>
                            <div id="otpWarnBanner" style="display:none; margin-bottom:0.75rem; padding:0.75rem; border-radius:6px; background: rgba(255,193,7,0.12); border:1px solid rgba(255,193,7,0.2); color: #856404;">Verification code generated but email delivery failed. Use the debug code below for testing.</div>
                            <div id="otpDebugCode" style="display:none; margin-bottom:1rem; padding:1rem; background: #fffacd; border:2px solid #ffd700; border-radius:6px; font-weight:700; text-align:center; font-size:1.2rem; color: #d4941e;"></div>

                            <form id="otpModalForm" class="auth-form">
                                <div class="form-group">
                                    <label for="otp">
                                        <i class="fas fa-key"></i> Verification Code
                                    </label>
                                    <input type="text" id="otp" name="otp" placeholder="Enter 6-digit code" maxlength="6" pattern="[0-9]{6}" required autocomplete="one-time-code">
                                    <small class="form-hint">Enter the 6-digit code sent to your email</small>
                                </div>

                                <div class="error-message" id="otpModalErrorMessage" style="display: none;">
                                    <i class="fas fa-exclamation-circle"></i>
                                    <span id="otpModalErrorText"></span>
                                </div>

                                <div class="modal-actions">
                                    <button type="submit" class="btn btn-primary" id="modalVerifyButton">
                                        <i class="fas fa-check-circle"></i>
                                        <span class="btn-text">Verify & Complete Signup</span>
                                        <span class="btn-spinner" style="display: none;">
                                            <i class="fas fa-spinner fa-spin"></i>
                                        </span>
                                    </button>

                                    <button type="button" class="btn-link" id="modalResendButton">
                                        <i class="fas fa-redo"></i> Resend Code
                                    </button>

                                    <button type="button" class="btn-link" id="modalBackButton">
                                        <i class="fas fa-arrow-left"></i> Back
                                    </button>
                                </div>
                            </form>
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
    <!-- Google Sign-In API -->
    <script src="https://accounts.google.com/gsi/client" async defer></script>
    <script>
        // Google OAuth Sign Up
        (function() {
            let googleClientId = null;
            let googleApiLoaded = false;
            let initializationAttempts = 0;
            const maxInitializationAttempts = 50; // 5 seconds max wait time
            let clickHandlerAttached = false;

            // Wait for DOM to be ready
            function init() {
                console.log('Initializing Google OAuth...');
                const googleSignupBtn = document.getElementById('googleSignupBtn');
                if (!googleSignupBtn) {
                    console.error('Google sign-up button not found. Retrying...');
                    setTimeout(init, 200);
                    return;
                }
                
                console.log('Google sign-up button found');

                // Load Google Client ID
                console.log('Fetching Google config...');
                fetch('api/get-google-config.php')
                    .then(res => {
                        if (!res.ok) {
                            throw new Error('Failed to fetch Google config: HTTP ' + res.status);
                        }
                        return res.json();
                    })
                    .then(data => {
                        console.log('Google config response:', data);
                        console.log('Response type:', typeof data);
                        console.log('Has success:', data && data.success);
                        console.log('Has client_id:', data && data.client_id);
                        
                        if (data && data.success === true && data.client_id) {
                            googleClientId = data.client_id;
                            window.googleClientId = googleClientId; // Make available globally for debugging
                            console.log('Google Client ID loaded successfully:', googleClientId.substring(0, 20) + '...');
                            initializeGoogleSignUp();
                        } else {
                            console.error('Google Client ID not found in config. Response:', JSON.stringify(data, null, 2));
                            const errorMsg = data && data.message ? data.message : 'Google OAuth is not configured.';
                            console.error('Error message:', errorMsg);
                            showGoogleButtonError(errorMsg);
                            
                            // Show user-friendly message
                            if (data && data.debug) {
                                console.warn('Debug info:', data.debug);
                            }
                        }
                    })
                    .catch(err => {
                        console.error('Failed to load Google config:', err);
                        showGoogleButtonError('Unable to load Google sign-up. Please use the regular sign-up form.');
                    });
            }

            // Check if Google API is loaded
            function checkGoogleApiLoaded() {
                if (typeof google !== 'undefined' && google.accounts && google.accounts.oauth2) {
                    googleApiLoaded = true;
                    return true;
                }
                return false;
            }

            function initializeGoogleSignUp() {
                if (!googleClientId) {
                    console.error('Google Client ID not loaded');
                    return;
                }

                const googleSignupBtn = document.getElementById('googleSignupBtn');
                if (!googleSignupBtn) {
                    console.error('Google sign-up button not found');
                    return;
                }

                // Attach click handler only once
                if (!clickHandlerAttached) {
                    console.log('Attaching click handler to Google signup button');
                    // Remove any existing listeners by cloning the button
                    const newBtn = googleSignupBtn.cloneNode(true);
                    googleSignupBtn.parentNode.replaceChild(newBtn, googleSignupBtn);
                    
                    newBtn.addEventListener('click', function(e) {
                        e.preventDefault();
                        e.stopPropagation();
                        console.log('Google signup button clicked');
                        
                        if (!googleClientId) {
                            console.error('Google Client ID not available');
                            Swal.fire({
                                icon: 'error',
                                title: 'Configuration Error',
                                text: 'Google sign-up is not properly configured. Please use the regular sign-up form.'
                            });
                            return;
                        }

                        if (!checkGoogleApiLoaded()) {
                            console.warn('Google API not loaded yet');
                            Swal.fire({
                                icon: 'info',
                                title: 'Loading...',
                                text: 'Google sign-up service is still loading. Please wait a moment and try again.',
                                timer: 2000
                            });
                            // Retry initialization
                            setTimeout(initializeGoogleSignUp, 500);
                            return;
                        }
                        
                        console.log('Initializing Google OAuth token client...');
                        try {
                            // Use Google Identity Services OAuth 2.0
                            const tokenClient = google.accounts.oauth2.initTokenClient({
                                client_id: googleClientId,
                                scope: 'email profile',
                                callback: handleGoogleTokenResponse,
                            });
                            
                            console.log('Requesting access token...');
                            tokenClient.requestAccessToken({ prompt: 'consent' });
                        } catch (error) {
                            console.error('Error initializing Google OAuth:', error);
                            Swal.fire({
                                icon: 'error',
                                title: 'Authentication Error',
                                text: 'Failed to start Google sign-up: ' + error.message
                            });
                        }
                    });
                    clickHandlerAttached = true;
                    console.log('Click handler attached successfully');
                }

                // Wait for Google Identity Services to load
                if (!checkGoogleApiLoaded()) {
                    initializationAttempts++;
                    console.log('Waiting for Google API to load... Attempt ' + initializationAttempts);
                    if (initializationAttempts < maxInitializationAttempts) {
                        setTimeout(initializeGoogleSignUp, 100);
                    } else {
                        console.error('Google Identity Services failed to load after maximum attempts');
                        showGoogleButtonError('Google sign-up service is taking too long to load. Please refresh the page or use the regular sign-up form.');
                    }
                    return;
                }

                console.log('Google API loaded successfully');
                // Remove any error styling
                const btn = document.getElementById('googleSignupBtn');
                if (btn) {
                    btn.style.opacity = '1';
                    btn.style.cursor = 'pointer';
                    btn.disabled = false;
                    console.log('Google sign-up button initialized successfully');
                }
            }

            function showGoogleButtonError(message) {
                const googleSignupBtn = document.getElementById('googleSignupBtn');
                if (googleSignupBtn) {
                    googleSignupBtn.style.opacity = '0.6';
                    googleSignupBtn.style.cursor = 'pointer'; // Keep pointer so user can click
                    googleSignupBtn.disabled = false; // Don't disable, allow click to show setup message
                    googleSignupBtn.title = message + ' Click to setup.';
                    
                    // Add click handler to show setup instructions
                    googleSignupBtn.addEventListener('click', function(e) {
                        e.preventDefault();
                        Swal.fire({
                            icon: 'info',
                            title: 'Google OAuth Not Configured',
                            html: `
                                <p>Google sign-up is not configured yet.</p>
                                <p><strong>To enable Google sign-up:</strong></p>
                                <ol style="text-align: left; margin: 1rem 0;">
                                    <li>Get Google OAuth credentials from <a href="https://console.cloud.google.com/" target="_blank">Google Cloud Console</a></li>
                                    <li>Configure them using the setup page</li>
                                </ol>
                                <a href="api/setup-google-oauth.php" target="_blank" style="display: inline-block; margin-top: 1rem; padding: 0.75rem 1.5rem; background: #4285f4; color: white; text-decoration: none; border-radius: 5px; font-weight: bold;">Open Setup Page</a>
                            `,
                            confirmButtonText: 'OK',
                            width: '600px'
                        });
                    }, { once: true }); // Only attach once
                }
            }

            // Initialize when DOM is ready
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', function() {
                    setTimeout(init, 100); // Small delay to ensure everything is ready
                });
            } else {
                setTimeout(init, 100);
            }
        })();

        function handleGoogleTokenResponse(tokenResponse) {
            console.log('Google token response received:', tokenResponse);
            
            if (tokenResponse.error) {
                console.error('Google OAuth error:', tokenResponse.error);
                let errorMsg = 'Failed to authenticate with Google. Please try again.';
                
                if (tokenResponse.error === 'popup_closed_by_user') {
                    errorMsg = 'Authentication cancelled.';
                } else if (tokenResponse.error_description) {
                    errorMsg = tokenResponse.error_description;
                }
                
                Swal.fire({
                    icon: 'error',
                    title: 'Authentication Error',
                    text: errorMsg
                });
                return;
            }

            if (!tokenResponse.access_token) {
                console.error('No access token in response');
                Swal.fire({
                    icon: 'error',
                    title: 'Authentication Error',
                    text: 'No access token received from Google.'
                });
                return;
            }

            console.log('Fetching user info from Google...');
            fetch('https://www.googleapis.com/oauth2/v2/userinfo?access_token=' + tokenResponse.access_token)
                .then(res => {
                    if (!res.ok) {
                        throw new Error('Failed to fetch user info: HTTP ' + res.status);
                    }
                    return res.json();
                })
                .then(userInfo => {
                    console.log('User info received:', userInfo);
                    if (!userInfo || !userInfo.email) {
                        throw new Error('Invalid user info received from Google');
                    }
                    console.log('Verifying user with backend...');
                    verifyGoogleUser(userInfo);
                })
                .catch(err => {
                    console.error('Google token error:', err);
                    Swal.fire({
                        icon: 'error',
                        title: 'Authentication Error',
                        text: 'Failed to authenticate with Google: ' + (err.message || 'Unknown error')
                    });
                });
        }

        async function verifyGoogleUser(userInfo) {
            try {
                console.log('Sending user info to backend for verification...');
                const response = await fetch('api/google-oauth.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        action: 'verify',
                        user_info: userInfo
                    })
                });

                console.log('Backend response status:', response.status);
                if (!response.ok) {
                    const errorText = await response.text();
                    console.error('Backend error:', errorText);
                    throw new Error('Server error: HTTP ' + response.status + ' - ' + errorText);
                }

                const data = await response.json();
                console.log('Backend response:', data);

                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: data.is_new_user ? 'Account Created!' : 'Login Successful!',
                        text: 'Welcome, ' + (data.username || data.user_name || 'User'),
                        showConfirmButton: false,
                        timer: 1500
                    }).then(() => {
                        console.log('Redirecting to index.php...');
                        window.location.href = '../index.php';
                    });
                } else {
                    console.error('Backend returned error:', data.message);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: data.message || 'Failed to authenticate with Google.'
                    });
                }
            } catch (error) {
                console.error('Google OAuth error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Connection Error',
                    text: 'Please check your internet connection and try again. Error: ' + error.message
                });
            }
        }
        
        // Debug: Check if button exists after page load
        window.addEventListener('load', function() {
            setTimeout(function() {
                const btn = document.getElementById('googleSignupBtn');
                console.log('Page loaded. Google signup button exists:', !!btn);
                if (btn) {
                    console.log('Button disabled:', btn.disabled);
                    console.log('Button opacity:', btn.style.opacity);
                }
            }, 1000);
        });
    </script>

    <!-- Facebook Sign Up Script -->
    <script>
        // Facebook OAuth Sign Up
        document.addEventListener('DOMContentLoaded', function() {
            const facebookSignupBtn = document.getElementById('facebookSignupBtn');
            if (!facebookSignupBtn) return;

            // Load Facebook App ID from config
            fetch('api/get-facebook-config.php')
                .then(res => res.json())
                .then(data => {
                    if (data && data.success && data.app_id) {
                        window.facebookAppId = data.app_id;
                        console.log('Facebook App ID loaded successfully');
                    } else {
                        console.error('Facebook App ID not found in config');
                        showFacebookButtonError(data.message || 'Facebook OAuth is not configured.');
                    }
                })
                .catch(err => {
                    console.error('Failed to load Facebook config:', err);
                    showFacebookButtonError('Unable to load Facebook sign up configuration.');
                });

            function showFacebookButtonError(message) {
                console.warn('Facebook OAuth:', message);
                const btn = document.getElementById('facebookSignupBtn');
                if (btn) {
                    btn.style.opacity = '0.6';
                    btn.title = message + ' Click to retry.';
                    btn.addEventListener('click', function(e) {
                        e.preventDefault();
                        Swal.fire({
                            icon: 'warning',
                            title: 'Facebook Sign Up Unavailable',
                            text: message,
                            confirmButtonText: 'OK'
                        });
                    });
                }
            }

            const handleFacebookSignup = () => {
                if (!window.facebookAppId) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Configuration Error',
                        text: 'Facebook sign up is not properly configured. Please try again later.'
                    });
                    return;
                }

                // Build Facebook OAuth URL
                const redirectUri = window.location.origin + '/EMERGENCY-COM/USERS/api/facebook-callback.php';
                const scope = 'email,public_profile';
                const state = btoa(JSON.stringify({
                    timestamp: Date.now(),
                    source: 'signup'
                }));

                // Store state in session storage for verification
                sessionStorage.setItem('facebook_oauth_state', state);

                const fbAuthUrl = `https://www.facebook.com/v18.0/dialog/oauth?` +
                    `client_id=${window.facebookAppId}` +
                    `&redirect_uri=${encodeURIComponent(redirectUri)}` +
                    `&scope=${encodeURIComponent(scope)}` +
                    `&state=${encodeURIComponent(state)}` +
                    `&response_type=code`;

                // Redirect to Facebook OAuth
                window.location.href = fbAuthUrl;
            };

            facebookSignupBtn.addEventListener('click', handleFacebookSignup);

            // Check for Facebook errors in URL
            const queryParams = new URLSearchParams(window.location.search);
            const fbError = queryParams.get('error');
            if (fbError === 'facebook_denied') {
                Swal.fire({
                    icon: 'info',
                    title: 'Sign Up Cancelled',
                    text: 'You cancelled the Facebook sign up. Please try again or use another sign up method.'
                });
            } else if (fbError === 'facebook_auth_failed') {
                Swal.fire({
                    icon: 'error',
                    title: 'Sign Up Failed',
                    text: 'Facebook authentication failed. Please try again or use another sign up method.'
                });
            }

            // Check if this is a Facebook signup completion
            const facebookSignup = queryParams.get('facebook_signup');
            if (facebookSignup === '1') {
                // Pre-fill form with Facebook data if available
                fetch('api/get-facebook-session-data.php')
                    .then(res => res.json())
                    .then(data => {
                        if (data.success && data.user) {
                            const nameInput = document.getElementById('full_name');
                            const emailInput = document.getElementById('email');
                            
                            if (nameInput && data.user.name) {
                                nameInput.value = data.user.name;
                            }
                            if (emailInput && data.user.email) {
                                emailInput.value = data.user.email;
                            }
                            
                            Swal.fire({
                                icon: 'info',
                                title: 'Complete Your Profile',
                                text: 'Please fill in the remaining fields to complete your registration.',
                                confirmButtonText: 'OK'
                            });
                        }
                    })
                    .catch(err => console.error('Error loading Facebook session data:', err));
            }
        });
    </script>

    <script>
        // Barangay data organized by district
        const barangaysByDistrict = {
            '1': [
                'Vasra', 'Bagong Pag-asa', 'Sto. Cristo', 'Project 6', 'Ramon Magsaysay', 'Alicia',
                'Bahay Toro', 'Katipunan', 'San Antonio', 'Veterans Village', 'Bungad', 'Phil-Am',
                'West Triangle', 'Sta. Cruz', 'Nayong Kanluran', 'Paltok', 'Paraiso', 'Mariblo',
                'Damayan', 'Del Monte', 'Masambong', 'Talayan', 'Sto. Domingo', 'Siena',
                'St. Peter', 'San Jose', 'Manresa', 'Damar', 'Pag-ibig sa Nayon', 'Balingasa',
                'Sta. Teresita', 'San Isidro Labrador', 'Paang Bundok', 'Salvacion', 'N.S Amoranto',
                'Maharlika', 'Lourdes'
            ],
            '2': [
                'Bagong Silangan', 'Batasan Hills', 'Commonwealth', 'Holy Spirit', 'Payatas'
            ],
            '3': [
                'Silangan', 'Socorro', 'E. Rodriguez', 'West Kamias', 'East Kamias', 'Quirino 2-A',
                'Quirino 2-B', 'Quirino 2-C', 'Quirino 3-A', 'Claro (Quirino 3-B)', 'Duyan-Duyan',
                'Amihan', 'Matandang Balara', 'Pansol', 'Loyola Heights', 'San Roque', 'Mangga',
                'Masagana', 'Villa Maria Clara', 'Bayanihan', 'Camp Aguinaldo', 'White Plains',
                'Libis', 'Ugong Norte', 'Bagumbayan', 'Blue Ridge A', 'Blue Ridge B', 'St. Ignatius',
                'Milagrosa', 'Escopa I', 'Escopa II', 'Escopa III', 'Escopa IV', 'Marilag',
                'Bagumbuhay', 'Tagumpay', 'Dioquino Zobel'
            ],
            '4': [
                'Sacred Heart', 'Laging Handa', 'Obrero', 'Paligsahan', 'Roxas', 'Kamuning',
                'South Triangle', 'Pinagkaisahan', 'Immaculate Concepcion', 'San Martin De Porres',
                'Kaunlaran', 'Bagong Lipunan ng Crame', 'Horseshoe', 'Valencia', 'Tatalon',
                'Kalusugan', 'Kristong Hari', 'Damayang Lagi', 'Mariana', 'Doña Imelda', 'Santol',
                'Sto. Niño', 'San Isidro Galas', 'Doña Aurora', 'Don Manuel', 'Doña Josefa',
                'UP Village', 'Old Capitol Site', 'UP Campus', 'San Vicente', 'Teachers Village East',
                'Teachers Village West', 'Central', 'Pinyahan', 'Malaya', 'Sikatuna Village', 'Botocan',
                'Krus Na Ligas'
            ],
            '5': [
                'Bagbag', 'Capri', 'Greater Lagro', 'Gulod', 'Kaligayahan', 'Nagkaisang Nayon',
                'North Fairview', 'Novaliches Proper', 'Pasong Putik Proper', 'San Agustin',
                'San Bartolome', 'Sta. Lucia', 'Sta. Monica', 'Fairview'
            ],
            '6': [
                'Apolonio Samson', 'Baesa', 'Balon Bato', 'Culiat', 'New Era', 'Pasong Tamo',
                'Sangandaan', 'Tandang Sora', 'Unang Sigaw', 'Sauyo', 'Talipapa'
            ]
        };

        // Barangay Autocomplete with District Filtering
        (function () {
            let currentDistrict = null;
            let filteredBarangays = [];
            const districtSelect = document.getElementById('district');
            const barangayInput = document.getElementById('barangay');
            const barangaySuggestionsDiv = document.getElementById('barangaySuggestions');
            let selectedBarangay = null;

            // Handle district selection
            if (districtSelect) {
                districtSelect.addEventListener('change', function() {
                    const selectedDistrict = this.value;
                    currentDistrict = selectedDistrict;
                    
                    // Clear barangay field when district changes
                    if (barangayInput) {
                        barangayInput.value = '';
                        selectedBarangay = null;
                    }
                    
                    // Update filtered barangays based on district
                    if (selectedDistrict && barangaysByDistrict[selectedDistrict]) {
                        filteredBarangays = barangaysByDistrict[selectedDistrict];
                        // Enable barangay input
                        if (barangayInput) {
                            barangayInput.disabled = false;
                            barangayInput.placeholder = 'Type to search barangay...';
                        }
                    } else {
                        filteredBarangays = [];
                        // Disable barangay input if no district selected
                        if (barangayInput) {
                            barangayInput.disabled = true;
                            barangayInput.placeholder = 'Select district first, then type to search barangay...';
                        }
                    }
                    
                    // Hide suggestions
                    if (barangaySuggestionsDiv) {
                        barangaySuggestionsDiv.style.display = 'none';
                    }
                });
            }

            if (barangayInput && barangaySuggestionsDiv) {
                barangayInput.addEventListener('input', function() {
                    if (this.disabled || !currentDistrict) {
                        return;
                    }

                    const query = this.value.trim().toLowerCase();
                    barangaySuggestionsDiv.innerHTML = '';
                    barangaySuggestionsDiv.style.display = 'none';

                    if (query.length < 1) {
                        return;
                    }

                    const matches = filteredBarangays.filter(b => 
                        b.toLowerCase().includes(query)
                    ).slice(0, 15);

                    if (matches.length > 0) {
                        matches.forEach(barangay => {
                            const item = document.createElement('div');
                            item.className = 'suggestion-item';
                            item.textContent = barangay;
                            item.addEventListener('click', function() {
                                barangayInput.value = barangay;
                                selectedBarangay = barangay;
                                barangaySuggestionsDiv.style.display = 'none';
                            });
                            barangaySuggestionsDiv.appendChild(item);
                        });
                        barangaySuggestionsDiv.style.display = 'block';
                    } else if (query.length >= 2) {
                        const noResult = document.createElement('div');
                        noResult.className = 'suggestion-item';
                        noResult.style.color = '#999';
                        noResult.textContent = 'No barangay found in this district';
                        barangaySuggestionsDiv.appendChild(noResult);
                        barangaySuggestionsDiv.style.display = 'block';
                    }
                });

                // Show all barangays from selected district when focused
                barangayInput.addEventListener('focus', function() {
                    if (this.disabled || !currentDistrict) {
                        return;
                    }

                    if (this.value.length === 0 && filteredBarangays.length > 0) {
                        const topBarangays = filteredBarangays.slice(0, 15);
                        barangaySuggestionsDiv.innerHTML = '';
                        topBarangays.forEach(barangay => {
                            const item = document.createElement('div');
                            item.className = 'suggestion-item';
                            item.textContent = barangay;
                            item.addEventListener('click', function() {
                                barangayInput.value = barangay;
                                selectedBarangay = barangay;
                                barangaySuggestionsDiv.style.display = 'none';
                            });
                            barangaySuggestionsDiv.appendChild(item);
                        });
                        barangaySuggestionsDiv.style.display = 'block';
                    }
                });

                // Hide suggestions when clicking outside
                document.addEventListener('click', function(e) {
                    if (!barangayInput.contains(e.target) && !barangaySuggestionsDiv.contains(e.target) && !districtSelect.contains(e.target)) {
                        barangaySuggestionsDiv.style.display = 'none';
                    }
                });
            }
        })();

    </script>

    <script>
        // Form Elements
        const signupForm = document.getElementById('signupForm');
        const signupButton = document.getElementById('signupButton');
        const errorMessage = document.getElementById('errorMessage');
        const errorText = document.getElementById('errorText');
        const phoneInput = document.getElementById('phone');

        if (phoneInput) {
            phoneInput.addEventListener('input', function () {
                this.value = this.value.replace(/\D/g, '');
                if (this.value.length === 1 && this.value === '0') {
                    this.value = '';
                }
            });
        }
        
        function showError(message) {
            errorText.textContent = message;
            errorMessage.style.display = 'flex';
            setTimeout(() => {
                errorMessage.style.display = 'none';
            }, 5000);
        }
        
        function hideError() {
            errorMessage.style.display = 'none';
        }
        
        function setLoading(isLoading) {
            if (isLoading) {
                signupButton.disabled = true;
                signupButton.querySelector('.btn-text').style.display = 'none';
                signupButton.querySelector('.btn-spinner').style.display = 'inline-block';
            } else {
                signupButton.disabled = false;
                signupButton.querySelector('.btn-text').style.display = 'inline';
                signupButton.querySelector('.btn-spinner').style.display = 'none';
            }
        }
        
        // Store form data for later use
        let pendingSignupData = null;
        
        // Step 1: Send OTP for phone verification
        signupForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            hideError();
            
            const fullName = document.getElementById('full_name').value.trim();
            const email = document.getElementById('email').value.trim();
            const phone = document.getElementById('phone').value.trim();
            const nationality = document.getElementById('nationality').value.trim();
            const district = document.getElementById('district').value.trim();
            const barangay = document.getElementById('barangay').value.trim();
            const houseNumber = document.getElementById('house_number').value.trim();
            const street = document.getElementById('street').value.trim();
            const privacyConsent = document.getElementById('privacyConsent');
            const privacyError = document.getElementById('privacyError');
            
            // Comprehensive Field Validation
            const missingFields = [];
            
            if (!fullName) missingFields.push('Full Name');
            if (!email) missingFields.push('Email Address');
            if (!phone) missingFields.push('Mobile Number');
            if (!nationality) missingFields.push('Nationality');
            if (!district) missingFields.push('District');
            if (!barangay) missingFields.push('Barangay');
            if (!houseNumber) missingFields.push('House / Unit No.');
            if (!street) missingFields.push('Street');
            
            if (missingFields.length > 0) {
                showError('Please fill out the following required fields: ' + missingFields.join(', '));
                // Scroll to first empty field
                const firstEmptyField = document.getElementById(
                    missingFields[0].toLowerCase().replace(/ /g, '_').replace('full_name', 'full_name').replace('email_address', 'email').replace('mobile_number', 'phone').replace('house_/_unit_no.', 'house_number')
                );
                if (firstEmptyField) firstEmptyField.scrollIntoView({ behavior: 'smooth', block: 'center' });
                return;
            }
            
            // Validate Full Name (at least 2 words, minimum 3 characters each)
            const nameWords = fullName.split(/\s+/).filter(w => w.length > 0);
            if (nameWords.length < 2) {
                showError('Please enter your full name (first and last name).');
                document.getElementById('full_name').scrollIntoView({ behavior: 'smooth', block: 'center' });
                return;
            }
            if (fullName.length < 5) {
                showError('Full name must be at least 5 characters long.');
                document.getElementById('full_name').scrollIntoView({ behavior: 'smooth', block: 'center' });
                return;
            }
            
            // Validate Email Address
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                showError('Please enter a valid email address (e.g., name@example.com).');
                document.getElementById('email').scrollIntoView({ behavior: 'smooth', block: 'center' });
                return;
            }
            
            // Validate Phone Number (should be 10 digits, starting with 9)
            const phoneRegex = /^9\d{9}$/;
            if (!phoneRegex.test(phone)) {
                showError('Please enter a valid 10-digit mobile number starting with 9 (e.g., 9XXXXXXXXX).');
                document.getElementById('phone').scrollIntoView({ behavior: 'smooth', block: 'center' });
                return;
            }
            
            // Validate Nationality (minimum 2 characters)
            if (nationality.length < 2) {
                showError('Please enter a valid nationality.');
                document.getElementById('nationality').scrollIntoView({ behavior: 'smooth', block: 'center' });
                return;
            }
            
            // Validate District (must be 1-6)
            if (!['1', '2', '3', '4', '5', '6'].includes(district)) {
                showError('Please select a valid district (1-6).');
                document.getElementById('district').scrollIntoView({ behavior: 'smooth', block: 'center' });
                return;
            }
            
            // Validate Barangay is from selected district
            const districtBarangays = barangaysByDistrict[district] || [];
            const barangayLower = barangay.toLowerCase();
            const isValidBarangay = districtBarangays.some(b => b.toLowerCase() === barangayLower);
            
            if (!isValidBarangay) {
                showError('Please select a valid barangay from District ' + district + '.');
                document.getElementById('barangay').scrollIntoView({ behavior: 'smooth', block: 'center' });
                return;
            }
            
            // Validate House Number (minimum 1 character)
            if (houseNumber.length < 1) {
                showError('Please enter a valid house or unit number.');
                document.getElementById('house_number').scrollIntoView({ behavior: 'smooth', block: 'center' });
                return;
            }
            
            // Validate Street (minimum 2 characters)
            if (street.length < 2) {
                showError('Please enter a valid street name (at least 2 characters).');
                document.getElementById('street').scrollIntoView({ behavior: 'smooth', block: 'center' });
                return;
            }
            
            // Validate privacy consent checkbox
            if (!privacyConsent.checked) {
                privacyError.style.display = 'block';
                privacyConsent.scrollIntoView({ behavior: 'smooth', block: 'center' });
                return;
            } else {
                privacyError.style.display = 'none';
            }
            
            setLoading(true);
            
            try {
                // Add +63 prefix to phone number
                const phoneWithPrefix = '+63' + phone;
                const payload = { email: email, name: fullName, phone: phoneWithPrefix };

                const response = await fetch('api/send-signup-email-otp.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(payload)
                });
                
                const data = await response.json();
                
                if (data.success) {
                    // Store signup data for later use when verifying OTP
                    pendingSignupData = {
                        name: fullName,
                        email: email,
                        phone: phoneWithPrefix,
                        nationality: nationality,
                        district: district,
                        barangay: barangay,
                        house_number: houseNumber,
                        street: street
                    };
                    
                    // Open OTP modal
                    document.getElementById('otpEmailDisplay').textContent = email;
                    openOtpModal();
                    startResendCooldown(60);
                    
                    // Show appropriate banner
                    document.getElementById('otpSentBanner').style.display = 'none';
                    document.getElementById('otpWarnBanner').style.display = 'none';
                    document.getElementById('otpDebugCode').style.display = 'none';

                    if (data.otp_sent === true) {
                        document.getElementById('otpSentBanner').textContent = 'Verification code sent successfully to ' + email + '.';
                        document.getElementById('otpSentBanner').style.display = 'block';
                    } else {
                        document.getElementById('otpWarnBanner').style.display = 'block';
                        if (data.debug_otp) {
                            const debugBox = document.getElementById('otpDebugCode');
                            debugBox.innerHTML = '<strong>DEBUG OTP CODE:</strong><br>' + data.debug_otp;
                            debugBox.style.display = 'block';
                            console.log('DEBUG OTP:', data.debug_otp);
                        }
                    }

                    Swal.fire({
                        icon: 'success',
                        title: 'Code Sent!',
                        text: 'A verification code has been sent to your email. Please check your inbox.',
                        showConfirmButton: false,
                        timer: 2000
                    });
                } else {
                    showError(data.message || 'Failed to send verification code. Please try again.');
                }
                setLoading(false);
            } catch (error) {
                console.error('Send OTP error:', error);
                showError('A connection error occurred. Please check your internet connection and try again.');
                setLoading(false);
            }
        });
        
        // OTP Modal logic
        const otpModal = document.getElementById('otpModal');
        const otpModalForm = document.getElementById('otpModalForm');
        const modalVerifyButton = document.getElementById('modalVerifyButton');
        const otpModalErrorMessage = document.getElementById('otpModalErrorMessage');
        const otpModalErrorText = document.getElementById('otpModalErrorText');
        const modalResendButton = document.getElementById('modalResendButton');
        const modalBackButton = document.getElementById('modalBackButton');
        const otpModalClose = document.getElementById('otpModalClose');

        function openOtpModal() {
            otpModal.style.display = 'flex';
            otpModal.setAttribute('aria-hidden', 'false');
            document.getElementById('otp').value = '';
            document.getElementById('otp').focus();
        }

        function closeOtpModal() {
            otpModal.style.display = 'none';
            otpModal.setAttribute('aria-hidden', 'true');
            otpModalErrorMessage.style.display = 'none';
        }

        // Close modal buttons
        if (otpModalClose) otpModalClose.addEventListener('click', closeOtpModal);
        if (modalBackButton) modalBackButton.addEventListener('click', closeOtpModal);

        // Resend cooldown timer
        let resendCountdownTimer = null;
        function startResendCooldown(seconds) {
            if (resendCountdownTimer) clearInterval(resendCountdownTimer);
            let remaining = seconds;
            modalResendButton.disabled = true;
            modalResendButton.textContent = `Resend Code (${remaining}s)`;

            resendCountdownTimer = setInterval(() => {
                remaining--;
                if (remaining <= 0) {
                    clearInterval(resendCountdownTimer);
                    modalResendButton.disabled = false;
                    modalResendButton.innerHTML = '<i class="fas fa-redo"></i> Resend Code';
                } else {
                    modalResendButton.textContent = `Resend Code (${remaining}s)`;
                }
            }, 1000);
        }

        // Resend OTP
        if (modalResendButton) {
            modalResendButton.addEventListener('click', async function() {
                if (modalResendButton.disabled) return;
                
                const email = document.getElementById('otpEmailDisplay').textContent;
                const fullName = document.getElementById('full_name').value.trim();
                const phone = document.getElementById('phone').value.trim();
                const phoneWithPrefix = '+63' + phone;
                
                try {
                    const response = await fetch('api/send-signup-email-otp.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ email: email, name: fullName, phone: phoneWithPrefix })
                    });
                    
                    const data = await response.json();
                    if (data.success) {
                        Swal.fire({ icon: 'success', title: 'Code Resent', text: 'A new verification code has been sent to your email.', timer: 1500, showConfirmButton: false });
                        startResendCooldown(60);
                    } else {
                        Swal.fire({ icon: 'error', title: 'Error', text: data.message || 'Failed to resend code.' });
                    }
                } catch (error) {
                    console.error('Resend OTP error:', error);
                    Swal.fire({ icon: 'error', title: 'Error', text: 'A connection error occurred.' });
                }
            });
        }

        // Step 2: Verify OTP and complete signup
        otpModalForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            if (!pendingSignupData) {
                Swal.fire({ icon: 'error', title: 'Error', text: 'Session expired. Please start over.' });
                closeOtpModal();
                return;
            }
            
            const otp = document.getElementById('otp').value.trim();
            
            if (!otp || otp.length !== 6) {
                otpModalErrorText.textContent = 'Please enter a valid 6-digit code.';
                otpModalErrorMessage.style.display = 'flex';
                return;
            }
            
            setOtpLoading(true);
            
            try {
                // First verify the OTP
                const verifyResponse = await fetch('api/verify-signup-email-otp.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ otp: otp })
                });
                
                const verifyData = await verifyResponse.json();
                
                if (!verifyData.success) {
                    otpModalErrorText.textContent = verifyData.message || 'Invalid verification code.';
                    otpModalErrorMessage.style.display = 'flex';
                    setOtpLoading(false);
                    return;
                }
                
                // OTP verified, now register the user with stored data
                const registerResponse = await fetch('api/register-after-otp.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(pendingSignupData)
                });
                
                const registerData = await registerResponse.json();
                
                if (registerData.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Account Created!',
                        text: 'Your account has been created successfully. You will now be redirected to login.',
                        timer: 2000,
                        showConfirmButton: false
                    }).then(() => {
                        window.location.href = 'login.php';
                    });
                } else {
                    otpModalErrorText.textContent = registerData.message || 'Failed to create account.';
                    otpModalErrorMessage.style.display = 'flex';
                }
                
                setOtpLoading(false);
            } catch (error) {
                console.error('OTP verify/register error:', error);
                otpModalErrorText.textContent = 'A connection error occurred. Please try again.';
                otpModalErrorMessage.style.display = 'flex';
                setOtpLoading(false);
            }
        });
        
        function setOtpLoading(isLoading) {
            const btn = document.getElementById('modalVerifyButton');
            if (isLoading) {
                btn.disabled = true;
                btn.querySelector('.btn-text').style.display = 'none';
                btn.querySelector('.btn-spinner').style.display = 'inline-block';
            } else {
                btn.disabled = false;
                btn.querySelector('.btn-text').style.display = 'inline';
                btn.querySelector('.btn-spinner').style.display = 'none';
            }
        }

        // Real-time field validation with visual feedback
        function validateField(input, isValid, errorMessage) {
            const formGroup = input.closest('.form-group');
            let feedbackEl = formGroup.querySelector('.field-feedback');
            
            if (!feedbackEl) {
                feedbackEl = document.createElement('small');
                feedbackEl.className = 'field-feedback';
                feedbackEl.style.cssText = 'display: block; margin-top: 0.25rem; font-size: 0.8rem; transition: all 0.3s ease;';
                formGroup.appendChild(feedbackEl);
            }
            
            if (input.value.trim() === '') {
                input.style.borderColor = '';
                input.style.boxShadow = '';
                feedbackEl.style.display = 'none';
                return;
            }
            
            if (isValid) {
                input.style.borderColor = '#4caf50';
                input.style.boxShadow = '0 0 0 3px rgba(76, 175, 80, 0.2)';
                feedbackEl.style.color = '#4caf50';
                feedbackEl.innerHTML = '<i class="fas fa-check-circle"></i> Valid';
                feedbackEl.style.display = 'block';
            } else {
                input.style.borderColor = '#dc3545';
                input.style.boxShadow = '0 0 0 3px rgba(220, 53, 69, 0.2)';
                feedbackEl.style.color = '#dc3545';
                feedbackEl.innerHTML = '<i class="fas fa-exclamation-circle"></i> ' + errorMessage;
                feedbackEl.style.display = 'block';
            }
        }

        // Add real-time validation to fields
        document.addEventListener('DOMContentLoaded', function() {
            // Full Name validation
            const fullNameInput = document.getElementById('full_name');
            if (fullNameInput) {
                fullNameInput.addEventListener('blur', function() {
                    const value = this.value.trim();
                    const nameWords = value.split(/\s+/).filter(w => w.length > 0);
                    const isValid = value.length >= 5 && nameWords.length >= 2;
                    validateField(this, isValid, 'Enter first and last name (min 5 chars)');
                });
            }

            // Email validation
            const emailInput = document.getElementById('email');
            if (emailInput) {
                emailInput.addEventListener('blur', function() {
                    const value = this.value.trim();
                    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                    validateField(this, emailRegex.test(value), 'Enter a valid email address');
                });
            }

            // Phone validation - only allow digits
            const phoneInput = document.getElementById('phone');
            if (phoneInput) {
                phoneInput.addEventListener('input', function() {
                    // Remove non-digits
                    this.value = this.value.replace(/\D/g, '');
                    // Limit to 10 digits
                    if (this.value.length > 10) {
                        this.value = this.value.slice(0, 10);
                    }
                });
                
                phoneInput.addEventListener('blur', function() {
                    const value = this.value.trim();
                    const phoneRegex = /^9\d{9}$/;
                    validateField(this, phoneRegex.test(value), 'Must be 10 digits starting with 9');
                });
            }

            // Nationality validation
            const nationalityInput = document.getElementById('nationality');
            if (nationalityInput) {
                nationalityInput.addEventListener('blur', function() {
                    const value = this.value.trim();
                    validateField(this, value.length >= 2, 'Enter a valid nationality');
                });
            }

            // District validation
            const districtInput = document.getElementById('district');
            if (districtInput) {
                districtInput.addEventListener('change', function() {
                    const value = this.value;
                    const isValid = ['1', '2', '3', '4', '5', '6'].includes(value);
                    if (isValid) {
                        this.style.borderColor = '#4caf50';
                        this.style.boxShadow = '0 0 0 3px rgba(76, 175, 80, 0.2)';
                    }
                });
            }

            // Barangay validation
            const barangayInput = document.getElementById('barangay');
            if (barangayInput) {
                barangayInput.addEventListener('blur', function() {
                    const value = this.value.trim();
                    const district = document.getElementById('district').value;
                    if (district && value) {
                        const districtBarangays = barangaysByDistrict[district] || [];
                        const isValid = districtBarangays.some(b => b.toLowerCase() === value.toLowerCase());
                        validateField(this, isValid, 'Select a valid barangay from the list');
                    }
                });
            }

            // House Number validation
            const houseNumberInput = document.getElementById('house_number');
            if (houseNumberInput) {
                houseNumberInput.addEventListener('blur', function() {
                    const value = this.value.trim();
                    validateField(this, value.length >= 1, 'Enter house/unit number');
                });
            }

            // Street validation
            const streetInput = document.getElementById('street');
            if (streetInput) {
                streetInput.addEventListener('blur', function() {
                    const value = this.value.trim();
                    validateField(this, value.length >= 2, 'Enter street name (min 2 chars)');
                });
            }

            // Privacy consent validation
            const privacyCheckbox = document.getElementById('privacyConsent');
            if (privacyCheckbox) {
                privacyCheckbox.addEventListener('change', function() {
                    const privacyError = document.getElementById('privacyError');
                    if (this.checked) {
                        privacyError.style.display = 'none';
                        this.closest('.privacy-consent-section').style.borderColor = '#4caf50';
                        this.closest('.privacy-consent-section').style.boxShadow = '0 0 0 3px rgba(76, 175, 80, 0.2)';
                    } else {
                        this.closest('.privacy-consent-section').style.borderColor = '';
                        this.closest('.privacy-consent-section').style.boxShadow = '';
                    }
                });
            }
        });
    </script>
