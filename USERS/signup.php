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
                        <div class="form-group form-group-half">
                            <div>
                                <label for="district">District</label>
                                <select id="district" name="district" required>
                                    <option value="" disabled selected>Select district</option>
                                    <option value="district1">District 1</option>
                                    <option value="district2">District 2</option>
                                    <option value="district3">District 3</option>
                                    <option value="district4">District 4</option>
                                    <option value="district5">District 5</option>
                                    <option value="district6">District 6</option>
                                </select>
                            </div>
                            <div>
                                <label for="barangay">Barangay</label>
                                <input list="barangayList" id="barangay" name="barangay" placeholder="Select Barangay" required>
                                <datalist id="barangayList"></datalist>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="house_number">House / Unit No.</label>
                            <input type="text" id="house_number" name="house_number" placeholder="e.g. #123" required>
                        </div>
                        <div class="form-group">
                            <label for="street">Street</label>
                            <input type="text" id="street" name="street" placeholder="Street name in Quezon City" required>
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
    <script>
        (function () {
            const districtBarangays = {
                district1: ["Vasra","Bagong Pag-asa","Sto. Cristo","Project 6","Ramon Magsaysay","Alicia","Bahay Toro","Katipunan","San Antonio","Veterans Village","Bungad","Phil-Am","West Triangle","Sta. Cruz","Nayong Kanluran","Paltok","Paraiso","Mariblo","Damayan","Del Monte","Masambong","Talayan","Sto. Domingo","Siena","St. Peter","San Jose","Manresa","Damar","Pag-ibig sa Nayon","Balingasa","Sta. Teresita","San Isidro Labrador","Paang Bundok","Salvacion","N.S Amoranto","Maharlika","Lourdes"],
                district2: ["Bagong Silangan","Batasan Hills","Commonwealth","Holy Spirit","Payatas"],
                district3: ["Silangan","Socorro","E. Rodriguez","West Kamias","East Kamias","Quirino 2-A","Quirino 2-B","Quirino 2-C","Quirino 3-A","Claro (Quirino 3-B)","Duyan-Duyan","Amihan","Matandang Balara","Pansol","Loyola Heights","San Roque","Mangga","Masagana","Villa Maria Clara","Bayanihan","Camp Aguinaldo","White Plains","Libis","Ugong Norte","Bagumbayan","Blue Ridge A","Blue Ridge B","St. Ignatius","Milagrosa","Escopa I","Escopa II","Escopa III","Escopa IV","Marilag","Bagumbuhay","Tagumpay","Dioquino Zobel"],
                district4: ["Sacred Heart","Laging Handa","Obrero","Paligsahan","Roxas","Kamuning","South Triangle","Pinagkaisahan","Immaculate Concepcion","San Martin De Porres","Kaunlaran","Bagong Lipunan ng Crame","Horseshoe","Valencia","Tatalon","Kalusugan","Kristong Hari","Damayang Lagi","Mariana","Doña Imelda","Santol","Sto. Niño","San Isidro Galas","Doña Aurora","Don Manuel","Doña Josefa","UP Village","Old Capitol Site","UP Campus","San Vicente","Teachers Village East","Teachers Village West","Central","Pinyahan","Malaya","Sikatuna Village","Botocan","Krus Na Ligas"],
                district5: ["Bagbag","Capri","Greater Lagro","Gulod","Kaligayahan","Nagkaisang Nayon","North Fairview","Novaliches Proper","Pasong Putik Proper","San Agustin","San Bartolome","Sta. Lucia","Sta. Monica","Fairview"],
                district6: ["Apolonio Samson","Baesa","Balon Bato","Culiat","New Era","Pasong Tamo","Sangandaan","Tandang Sora","Unang Sigaw","Sauyo","Talipapa"]
            };

            const districtSelect = document.getElementById('district');
            const barangayDatalist = document.getElementById('barangayList');

            function renderBarangays(key) {
                const barangays = districtBarangays[key] || [];
                barangayDatalist.innerHTML = barangays.map(name => `<option value="${name}"></option>`).join('');
            }

            if (districtSelect) {
                districtSelect.addEventListener('change', function () {
                    renderBarangays(this.value);
                });
                // Preload first district
                renderBarangays(districtSelect.value || 'district1');
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
            
            // Validation
            if (!fullName || !email || !phone || !nationality || !district || !barangay || !houseNumber || !street) {
                showError('Please fill out all required fields.');
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
    </script>
