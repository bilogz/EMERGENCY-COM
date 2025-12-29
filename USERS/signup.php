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
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <button class="sidebar-toggle-btn" aria-label="Toggle menu" onclick="window.sidebarToggle()">
        <i class="fas fa-bars"></i>
    </button>

    <main class="main-content">
        <div class="main-container">
            <div class="sub-container content-main">
                <section class="page-content">
                    <h2>Create an Account</h2>
                    <p>Sign up to receive alerts, manage your preferences, and access emergency tools.</p>
                    
                    <!-- Step 1: Basic Info + Email Verification -->
                    <form class="auth-form" id="signupForm" style="display: block;">
                        <div class="form-group">
                            <label for="full_name">Full Name</label>
                            <input type="text" id="full_name" name="full_name" placeholder="Juan Dela Cruz" required>
                        </div>
                        <div class="form-group">
                            <label for="email">Email Address</label>
                            <input type="email" id="email" name="email" placeholder="juan@example.com" required>
                        </div>
                        <div class="form-group">
                            <label for="nationality">Nationality</label>
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
                            <label for="phone">Mobile Number</label>
                            <div class="input-with-prefix">
                                <span class="prefix">+63</span>
                                <input type="tel" id="phone" name="phone" pattern="[0-9]{10}" maxlength="10" placeholder="9XXXXXXXXX" title="Enter 10 digits without spaces" required autocomplete="tel">
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="barangay">
                                <i class="fas fa-map-marker-alt"></i> Barangay (Quezon City)
                            </label>
                            <input type="text" id="barangay" name="barangay" placeholder="Type to search barangay..." required autocomplete="off">
                            <div id="barangaySuggestions" class="suggestions-dropdown" style="display: none;"></div>
                            <small class="form-hint">Start typing to search for your barangay</small>
                        </div>
                        <div class="form-group">
                            <label for="house_number">House / Unit No.</label>
                            <input type="text" id="house_number" name="house_number" placeholder="e.g. #123" required>
                        </div>
                        <div class="form-group">
                            <label for="street">
                                <i class="fas fa-road"></i> Street (Quezon City)
                            </label>
                            <input type="text" id="street" name="street" placeholder="Type to search street..." required autocomplete="off">
                            <div id="streetSuggestions" class="suggestions-dropdown" style="display: none;"></div>
                            <small class="form-hint">Start typing to search for your street</small>
                        </div>
                        
                        <div class="error-message" id="errorMessage" style="display: none;">
                            <i class="fas fa-exclamation-circle"></i>
                            <span id="errorText"></span>
                        </div>
                        
                        <div class="auth-actions">
                            <button type="submit" class="btn btn-primary" id="signupButton">
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
                        <button type="button" id="googleSignupBtn" class="btn btn-google">
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
                            <span class="google-text">Sign up with Google</span>
                        </button>

                        <div class="auth-switch">
                            <span>Already have an account?</span>
                            <a href="login.php" class="btn btn-secondary login-btn">
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
                const googleSignupBtn = document.getElementById('googleSignupBtn');
                if (!googleSignupBtn) {
                    console.error('Google sign-up button not found');
                    return;
                }

                // Load Google Client ID
                fetch('api/get-google-config.php')
                    .then(res => {
                        if (!res.ok) {
                            throw new Error('Failed to fetch Google config: HTTP ' + res.status);
                        }
                        return res.text().then(text => {
                            try {
                                return JSON.parse(text);
                            } catch (e) {
                                console.error('Invalid JSON response:', text);
                                throw new Error('Invalid JSON response from server');
                            }
                        });
                    })
                    .then(data => {
                        console.log('Google config response:', data);
                        if (data && data.success && data.client_id) {
                            googleClientId = data.client_id;
                            console.log('Google Client ID loaded successfully:', googleClientId);
                            initializeGoogleSignUp();
                        } else {
                            console.error('Google Client ID not found in config. Response:', data);
                            showGoogleButtonError('Google sign-up is not configured. Please use the regular sign-up form.');
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
                    googleSignupBtn.addEventListener('click', function(e) {
                        e.preventDefault();
                        e.stopPropagation();
                        
                        if (!googleClientId) {
                            Swal.fire({
                                icon: 'error',
                                title: 'Configuration Error',
                                text: 'Google sign-up is not properly configured. Please use the regular sign-up form.'
                            });
                            return;
                        }

                        if (!checkGoogleApiLoaded()) {
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
                        
                        try {
                            // Use Google Identity Services OAuth 2.0
                            const tokenClient = google.accounts.oauth2.initTokenClient({
                                client_id: googleClientId,
                                scope: 'email profile',
                                callback: handleGoogleTokenResponse,
                            });
                            
                            tokenClient.requestAccessToken({ prompt: 'consent' });
                        } catch (error) {
                            console.error('Error initializing Google OAuth:', error);
                            Swal.fire({
                                icon: 'error',
                                title: 'Authentication Error',
                                text: 'Failed to start Google sign-up. Please try again.'
                            });
                        }
                    });
                    clickHandlerAttached = true;
                }

                // Wait for Google Identity Services to load
                if (!checkGoogleApiLoaded()) {
                    initializationAttempts++;
                    if (initializationAttempts < maxInitializationAttempts) {
                        setTimeout(initializeGoogleSignUp, 100);
                    } else {
                        console.error('Google Identity Services failed to load after maximum attempts');
                        showGoogleButtonError('Google sign-up service is taking too long to load. Please refresh the page or use the regular sign-up form.');
                    }
                    return;
                }

                // Remove any error styling
                googleSignupBtn.style.opacity = '1';
                googleSignupBtn.style.cursor = 'pointer';
                googleSignupBtn.disabled = false;
                console.log('Google sign-up button initialized successfully');
            }

            function showGoogleButtonError(message) {
                const googleSignupBtn = document.getElementById('googleSignupBtn');
                if (googleSignupBtn) {
                    googleSignupBtn.style.opacity = '0.6';
                    googleSignupBtn.style.cursor = 'not-allowed';
                    googleSignupBtn.disabled = true;
                    googleSignupBtn.title = message;
                }
            }

            // Initialize when DOM is ready
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', init);
            } else {
                init();
            }
        })();

        function handleGoogleTokenResponse(tokenResponse) {
            if (tokenResponse.error) {
                console.error('Google OAuth error:', tokenResponse.error);
                Swal.fire({
                    icon: 'error',
                    title: 'Authentication Error',
                    text: tokenResponse.error_description || 'Failed to authenticate with Google. Please try again.'
                });
                return;
            }

            fetch('https://www.googleapis.com/oauth2/v2/userinfo?access_token=' + tokenResponse.access_token)
                .then(res => {
                    if (!res.ok) {
                        throw new Error('Failed to fetch user info');
                    }
                    return res.json();
                })
                .then(userInfo => {
                    verifyGoogleUser(userInfo);
                })
                .catch(err => {
                    console.error('Google token error:', err);
                    Swal.fire({
                        icon: 'error',
                        title: 'Authentication Error',
                        text: 'Failed to authenticate with Google. Please try again.'
                    });
                });
        }

        async function verifyGoogleUser(userInfo) {
            try {
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

                const data = await response.json();

                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: data.is_new_user ? 'Account Created!' : 'Login Successful!',
                        text: 'Welcome, ' + data.username,
                        showConfirmButton: false,
                        timer: 1500
                    }).then(() => {
                        window.location.href = '../index.php';
                    });
                } else {
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
                    text: 'Please check your internet connection and try again.'
                });
            }
        }
    </script>
    <script>
        // Barangay Autocomplete
        (function () {
            let barangays = [];
            const barangayInput = document.getElementById('barangay');
            const barangaySuggestionsDiv = document.getElementById('barangaySuggestions');
            let selectedBarangay = null;

            // Load barangays from API
            fetch('api/get-barangays.php')
                .then(res => res.json())
                .then(data => {
                    if (data.success && data.barangays) {
                        barangays = data.barangays;
                    }
                })
                .catch(err => {
                    console.error('Failed to load barangays:', err);
                });

            if (barangayInput && barangaySuggestionsDiv) {
                barangayInput.addEventListener('input', function() {
                    const query = this.value.trim().toLowerCase();
                    barangaySuggestionsDiv.innerHTML = '';
                    barangaySuggestionsDiv.style.display = 'none';

                    if (query.length < 1) {
                        return;
                    }

                    const matches = barangays.filter(b => 
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
                        noResult.textContent = 'No barangay found';
                        barangaySuggestionsDiv.appendChild(noResult);
                        barangaySuggestionsDiv.style.display = 'block';
                    }
                });

                // Show all barangays when focused
                barangayInput.addEventListener('focus', function() {
                    if (this.value.length === 0) {
                        const topBarangays = barangays.slice(0, 15);
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
                    if (!barangayInput.contains(e.target) && !barangaySuggestionsDiv.contains(e.target)) {
                        barangaySuggestionsDiv.style.display = 'none';
                    }
                });
            }
        })();

        // Street Autocomplete
        (function () {
            let streets = [];
            const streetInput = document.getElementById('street');
            const streetSuggestionsDiv = document.getElementById('streetSuggestions');
            let selectedStreet = null;

            // Load streets from API
            fetch('api/get-streets.php')
                .then(res => res.json())
                .then(data => {
                    if (data.success && data.streets) {
                        streets = data.streets;
                    }
                })
                .catch(err => {
                    console.error('Failed to load streets:', err);
                });

            if (streetInput && streetSuggestionsDiv) {
                streetInput.addEventListener('input', function() {
                    const query = this.value.trim().toLowerCase();
                    streetSuggestionsDiv.innerHTML = '';
                    streetSuggestionsDiv.style.display = 'none';

                    if (query.length < 1) {
                        return;
                    }

                    const matches = streets.filter(s => 
                        s.toLowerCase().includes(query)
                    ).slice(0, 15);

                    if (matches.length > 0) {
                        matches.forEach(street => {
                            const item = document.createElement('div');
                            item.className = 'suggestion-item';
                            item.textContent = street;
                            item.addEventListener('click', function() {
                                streetInput.value = street;
                                selectedStreet = street;
                                streetSuggestionsDiv.style.display = 'none';
                            });
                            streetSuggestionsDiv.appendChild(item);
                        });
                        streetSuggestionsDiv.style.display = 'block';
                    } else if (query.length >= 2) {
                        const noResult = document.createElement('div');
                        noResult.className = 'suggestion-item';
                        noResult.style.color = '#999';
                        noResult.textContent = 'No street found. You can type your street name.';
                        streetSuggestionsDiv.appendChild(noResult);
                        streetSuggestionsDiv.style.display = 'block';
                    }
                });

                // Show popular streets when focused
                streetInput.addEventListener('focus', function() {
                    if (this.value.length === 0) {
                        const popularStreets = streets.filter(s => 
                            s.includes('Avenue') || s.includes('Boulevard') || s.includes('Highway')
                        ).slice(0, 15);
                        streetSuggestionsDiv.innerHTML = '';
                        popularStreets.forEach(street => {
                            const item = document.createElement('div');
                            item.className = 'suggestion-item';
                            item.textContent = street;
                            item.addEventListener('click', function() {
                                streetInput.value = street;
                                selectedStreet = street;
                                streetSuggestionsDiv.style.display = 'none';
                            });
                            streetSuggestionsDiv.appendChild(item);
                        });
                        if (popularStreets.length > 0) {
                            streetSuggestionsDiv.style.display = 'block';
                        }
                    }
                });

                // Hide suggestions when clicking outside
                document.addEventListener('click', function(e) {
                    if (!streetInput.contains(e.target) && !streetSuggestionsDiv.contains(e.target)) {
                        streetSuggestionsDiv.style.display = 'none';
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
            const barangay = document.getElementById('barangay').value.trim();
            const houseNumber = document.getElementById('house_number').value.trim();
            const street = document.getElementById('street').value.trim();
            
            // Validation
            if (!fullName || !email || !phone || !nationality || !barangay || !houseNumber || !street) {
                showError('Please fill out all required fields.');
                return;
            }
            
            // Validate barangay is from Quezon City list
            const barangayLower = barangay.toLowerCase();
            const isValidBarangay = await fetch('api/get-barangays.php')
                .then(res => res.json())
                .then(data => {
                    if (data.success && data.barangays) {
                        return data.barangays.some(b => b.toLowerCase() === barangayLower);
                    }
                    return true; // Allow if API fails
                })
                .catch(() => true); // Allow if API fails
            
            if (!isValidBarangay) {
                showError('Please select a valid Quezon City barangay from the list.');
                return;
            }
            
            // Validate phone number (should be 10 digits)
            if (phone.length !== 10 || !/^[1-9]\d{9}$/.test(phone)) {
                showError('Please enter a valid 10-digit mobile number.');
                return;
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
    </script>
