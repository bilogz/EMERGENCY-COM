<?php
$assetBase = '../ADMIN/header/';
$current = 'profile.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile & Preferences</title>
    <link rel="icon" type="image/x-icon" href="<?= $assetBase ?>images/favicon.ico">
    <link rel="stylesheet" href="<?= $assetBase ?>css/global.css">
    <link rel="stylesheet" href="<?= $assetBase ?>css/buttons.css">
    <link rel="stylesheet" href="<?= $assetBase ?>css/hero.css">
    <link rel="stylesheet" href="<?= $assetBase ?>css/forms.css">
    <link rel="stylesheet" href="../ADMIN/sidebar/css/global.css">
    <link rel="stylesheet" href="../ADMIN/sidebar/css/sidebar.css">
    <link rel="stylesheet" href="../ADMIN/sidebar/css/content.css">
    <link rel="stylesheet" href="../ADMIN/sidebar/css/buttons.css">
    <link rel="stylesheet" href="css/user.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <style>
        .profile-skeleton {
            width: 100%;
            max-width: 520px;
        }
        .profile-skeleton .sk {
            border-radius: 12px;
            background: linear-gradient(90deg, rgba(255,255,255,0.06) 0%, rgba(255,255,255,0.12) 45%, rgba(255,255,255,0.06) 100%);
            background-size: 240% 100%;
            animation: profileShimmer 1.2s ease-in-out infinite;
            border: 1px solid rgba(255,255,255,0.08);
        }
        .profile-skeleton .sk-label { height: 14px; width: 42%; margin: 10px 0 10px; }
        .profile-skeleton .sk-input { height: 46px; width: 100%; margin-bottom: 18px; }
        .profile-skeleton .sk-button { height: 46px; width: 58%; margin: 10px auto 0; }
        @keyframes profileShimmer {
            0% { background-position: 100% 0; }
            100% { background-position: 0% 0; }
        }

        #edit_name {
            display: block !important;
            width: 100% !important;
            padding: 1.25rem 1.5rem !important;
            font-size: 1.2rem !important;
            border: 3px solid var(--card-border, #d1d5db) !important;
            border-radius: 12px !important;
            background: var(--card-bg, #ffffff) !important;
            color: var(--text-color, #171717) !important;
            box-sizing: border-box !important;
            min-height: 60px !important;
        }

        label[for="edit_name"] {
            justify-content: flex-start;
            text-align: left;
        }

        #profileEditForm > .form-group:first-of-type {
            display: block !important;
            visibility: visible !important;
            opacity: 1 !important;
            height: auto !important;
            max-height: none !important;
            overflow: visible !important;
        }
    </style>
    <script src="js/translations.js?v=<?= @filemtime(__DIR__ . '/js/translations.js') ?>"></script>
    <script src="js/language-manager.js?v=<?= @filemtime(__DIR__ . '/js/language-manager.js') ?>"></script>
    <script src="js/language-selector-modal.js?v=<?= @filemtime(__DIR__ . '/js/language-selector-modal.js') ?>"></script>
    <script src="js/language-sync.js?v=<?= @filemtime(__DIR__ . '/js/language-sync.js') ?>"></script>
    <script src="js/global-translator.js?v=<?= @filemtime(__DIR__ . '/js/global-translator.js') ?>"></script>
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
        <div class="hero-section" id="profile">
            <div class="main-container">
                <div class="sub-container">
                    <h1 data-translate="profile.title">Profile & Preferences</h1>
                    <p data-translate="profile.subtitle">Manage your contact methods, preferred languages, and alert categories.</p>
                </div>
            </div>
        </div>

        <div class="main-container">
            <div class="sub-container content-main">
                <section class="page-content">
                    <h2>Edit Your Information</h2>
                    <p>Update your personal information and contact details.</p>

                    <div id="profileSkeleton" class="profile-skeleton">
                        <div class="sk sk-label"></div>
                        <div class="sk sk-input"></div>
                        <div class="sk sk-label"></div>
                        <div class="sk sk-input"></div>
                        <div class="sk sk-label"></div>
                        <div class="sk sk-input"></div>
                        <div class="sk sk-label"></div>
                        <div class="sk sk-input"></div>
                        <div class="sk sk-label"></div>
                        <div class="sk sk-input"></div>
                        <div class="sk sk-label"></div>
                        <div class="sk sk-input"></div>
                        <div class="sk sk-label"></div>
                        <div class="sk sk-input"></div>
                        <div class="sk sk-button"></div>
                    </div>
                    
                    <form class="auth-form" id="profileEditForm" style="display:none;">
                        <div class="form-group" data-no-translate>
                            <label for="edit_name">
                                <i class="fas fa-user"></i> <span data-translate="form.fullName">Full Name</span>
                            </label>
                            <input type="text" id="edit_name" name="name" placeholder="Juan Dela Cruz" required autocomplete="name" data-translate-placeholder="form.enterName">
                        </div>
                        
                        <div class="form-group">
                            <label for="edit_email">
                                <i class="fas fa-envelope"></i> Email Address
                            </label>
                            <input type="email" id="edit_email" name="email" placeholder="juan@example.com">
                        </div>
                        
                        <div class="form-group">
                            <label for="edit_phone">
                                <i class="fas fa-phone"></i> Mobile Number
                            </label>
                            <div class="input-with-prefix">
                                <span class="prefix">+63</span>
                                <input type="tel" id="edit_phone" name="phone" pattern="[0-9]{10}" maxlength="10" placeholder="9XXXXXXXXX" autocomplete="tel">
                            </div>
                            <small class="form-hint">Enter your 10-digit mobile number (without spaces)</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="edit_nationality">
                                <i class="fas fa-flag"></i> Nationality
                            </label>
                            <input type="text" list="editNationalityList" id="edit_nationality" name="nationality" placeholder="Select nationality">
                            <datalist id="editNationalityList">
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
                            <label for="edit_district">
                                <i class="fas fa-map"></i> District (Quezon City)
                            </label>
                            <select id="edit_district" name="district">
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
                            <label for="edit_barangay">
                                <i class="fas fa-map-marker-alt"></i> Barangay (Quezon City)
                            </label>
                            <input type="text" list="editBarangayList" id="edit_barangay" name="barangay" placeholder="Select or type barangay..." autocomplete="off">
                            <datalist id="editBarangayList"></datalist>
                        </div>
                        
                        <div class="form-group">
                            <label for="edit_house_number">
                                <i class="fas fa-home"></i> House / Unit No.
                            </label>
                            <input type="text" id="edit_house_number" name="house_number" placeholder="e.g. #123">
                        </div>
                        
                        <div class="form-group">
                            <label for="edit_street">
                                <i class="fas fa-road"></i> Street (Quezon City)
                            </label>
                            <input type="text" id="edit_street" name="street" placeholder="Enter your street name">
                        </div>
                        
                        <div class="error-message" id="profileErrorMessage" style="display: none;">
                            <i class="fas fa-exclamation-circle"></i>
                            <span id="profileErrorText"></span>
                        </div>
                        
                        <button type="submit" class="btn btn-primary" id="saveProfileBtn">
                            <i class="fas fa-save"></i> Save Changes
                        </button>
                    </form>
                </section>
            </div>
        </div>
    </main>

    <?php include 'includes/footer-snippet.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="<?= $assetBase ?>js/mobile-menu.js"></script>
    <script src="<?= $assetBase ?>js/theme-toggle.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', async function () {
            const profileForm = document.getElementById('profileEditForm');
            const saveBtn = document.getElementById('saveProfileBtn');
            const errorMessage = document.getElementById('profileErrorMessage');
            const errorText = document.getElementById('profileErrorText');
            const profileSkeleton = document.getElementById('profileSkeleton');

            function ensureFullNameField() {
                if (!profileForm) return;

                // Check for existing full name fields - remove duplicates
                const existingNameInputs = document.querySelectorAll('#edit_name');
                if (existingNameInputs.length > 1) {
                    // Keep only the first one, remove the rest
                    for (let i = 1; i < existingNameInputs.length; i++) {
                        const duplicate = existingNameInputs[i];
                        const duplicateGroup = duplicate.closest('.form-group');
                        if (duplicateGroup && duplicateGroup !== existingNameInputs[0].closest('.form-group')) {
                            duplicateGroup.remove();
                        } else {
                            duplicate.remove();
                        }
                    }
                }

                let nameInput = document.getElementById('edit_name');
                if (!nameInput) {
                    console.warn('Full Name input missing; re-inserting it.');
                    const emailGroup = document.getElementById('edit_email')?.closest('.form-group') || null;
                    const group = document.createElement('div');
                    group.className = 'form-group';
                    group.innerHTML = `
                        <label for="edit_name">
                            <i class="fas fa-user"></i> <span data-translate="form.fullName">Full Name</span>
                        </label>
                        <input type="text" id="edit_name" name="name" placeholder="Juan Dela Cruz" required autocomplete="name" data-translate-placeholder="form.enterName">
                    `.trim();
                    profileForm.insertBefore(group, emailGroup || profileForm.firstChild);
                    nameInput = group.querySelector('#edit_name');
                }

                // Ensure the field value is not "Full Name" (should be empty or actual name)
                if (nameInput && (nameInput.value === 'Full Name' || nameInput.value === 'form.fullName')) {
                    nameInput.value = '';
                }

                const group = nameInput.closest('.form-group');
                if (group) {
                    group.style.display = 'block';
                    group.style.visibility = 'visible';
                    group.style.opacity = '1';
                    group.style.height = 'auto';
                    group.style.maxHeight = 'none';
                    group.style.overflow = 'visible';
                }
                nameInput.style.display = 'block';
                nameInput.style.visibility = 'visible';
                nameInput.style.opacity = '1';
            }

            function getApiPath(relativePath) {
                if (window.API_BASE_PATH && window.IS_ROOT_CONTEXT) {
                    if (relativePath.startsWith('api/')) {
                        return window.API_BASE_PATH + relativePath.substring(4);
                    }
                    return window.API_BASE_PATH + relativePath;
                }

                const currentPath = window.location.pathname;
                const isInUsersFolder = currentPath.includes('/USERS/');
                if (relativePath.startsWith('api/') && !isInUsersFolder) {
                    return 'USERS/' + relativePath;
                }
                return relativePath;
            }

            function setProfileLoading(isLoading) {
                if (profileSkeleton) {
                    profileSkeleton.style.display = isLoading ? 'block' : 'none';
                }
                if (profileForm) {
                    profileForm.style.display = isLoading ? 'none' : 'block';
                }

                if (!isLoading) {
                    ensureFullNameField();
                    // Remove duplicates after ensuring field exists
                    const nameInputs = document.querySelectorAll('#edit_name');
                    if (nameInputs.length > 1) {
                        // Keep the first one (original in HTML), remove others
                        const firstInput = nameInputs[0];
                        const firstGroup = firstInput.closest('.form-group');
                        for (let i = 1; i < nameInputs.length; i++) {
                            const duplicate = nameInputs[i];
                            const duplicateGroup = duplicate.closest('.form-group');
                            if (duplicateGroup && duplicateGroup !== firstGroup) {
                                duplicateGroup.remove();
                            } else {
                                duplicate.remove();
                            }
                        }
                    }
                }
            }

            async function fetchJsonWithTimeout(url, options = {}, timeoutMs = 15000) {
                const controller = new AbortController();
                const timeoutId = setTimeout(() => controller.abort(), timeoutMs);
                try {
                    const res = await fetch(url, { ...options, signal: controller.signal });
                    const text = await res.text();
                    let json = null;
                    try {
                        json = text ? JSON.parse(text) : null;
                    } catch (e) {
                        json = null;
                    }
                    if (!res.ok) {
                        const msg = (json && (json.message || json.error)) ? (json.message || json.error) : (text || `Request failed (${res.status})`);
                        throw new Error(msg);
                    }
                    if (!json) {
                        throw new Error(text || 'Invalid server response.');
                    }
                    return json;
                } catch (err) {
                    if (err && err.name === 'AbortError') {
                        throw new Error('Request timed out. Please try again.');
                    }
                    throw err;
                } finally {
                    clearTimeout(timeoutId);
                }
            }
            
            // Load current user data
            async function loadUserData() {
                setProfileLoading(true);
                try {
                    const data = await fetchJsonWithTimeout(getApiPath('api/get-user-profile.php'), { method: 'GET' }, 15000);
                    if (data.success && data.user) {
                        const user = data.user;
                            
                        // Populate form fields
                        if (document.getElementById('edit_name')) {
                            const nameValue = user.name || '';
                            // Don't set "Full Name" as the value - it's a label, not a value
                            if (nameValue && nameValue !== 'Full Name' && nameValue !== 'form.fullName') {
                                document.getElementById('edit_name').value = nameValue;
                            } else {
                                document.getElementById('edit_name').value = '';
                            }
                        }
                        if (document.getElementById('edit_email')) {
                            document.getElementById('edit_email').value = user.email || '';
                        }
                        if (document.getElementById('edit_phone')) {
                            const phone = user.phone || '';
                            document.getElementById('edit_phone').value = phone.replace(/^\+63/, '');
                        }
                        if (document.getElementById('edit_nationality')) {
                            document.getElementById('edit_nationality').value = user.nationality || '';
                        }
                        if (document.getElementById('edit_district')) {
                            const districtEl = document.getElementById('edit_district');
                            const rawDistrict = (user.district || '').toString().trim();
                            let districtValue = rawDistrict;
                            const m = rawDistrict.match(/district\s*(\d+)/i);
                            if (m && m[1]) districtValue = m[1];
                            districtEl.value = districtValue;
                        }
                        if (document.getElementById('edit_barangay')) {
                            document.getElementById('edit_barangay').value = user.barangay || '';
                        }
                        if (document.getElementById('edit_house_number')) {
                            document.getElementById('edit_house_number').value = user.house_number || '';
                        }
                        if (document.getElementById('edit_street')) {
                            document.getElementById('edit_street').value = user.street || '';
                        }
                    }
                } catch (error) {
                    console.error('Error loading user data:', error);
                    showError(error && error.message ? error.message : 'Failed to load your profile. Please refresh.');
                } finally {
                    setProfileLoading(false);
                }
            }
            
            // Load barangay suggestions (similar to signup)
            function setupBarangaySuggestions() {
                const districtSelect = document.getElementById('edit_district');
                const barangayInput = document.getElementById('edit_barangay');
                const barangayList = document.getElementById('editBarangayList');
                
                if (!districtSelect || !barangayInput || !barangayList) return;

                const barangaysByDistrict = {
                    '1': [
                        'Vasra','Bagong Pag-asa','Sto. Cristo','Project 6','Ramon Magsaysay','Alicia','Bahay Toro','Katipunan','San Antonio','Veterans Village','Bungad','Phil-Am','West Triangle','Sta. Cruz','Nayong Kanluran','Paltok','Paraiso','Mariblo','Damayan','Del Monte','Masambong','Talayan','Sto. Domingo','Siena','St. Peter','San Jose','Manresa','Damar','Pag-ibig sa Nayon','Balingasa','Sta. Teresita','San Isidro Labrador','Paang Bundok','Salvacion','N.S Amoranto','Maharlika','Lourdes'
                    ],
                    '2': [
                        'Bagong Silangan','Batasan Hills','Commonwealth','Holy Spirit','Payatas'
                    ],
                    '3': [
                        'Silangan','Socorro','E. Rodriguez','West Kamias','East Kamias','Quirino 2-A','Quirino 2-B','Quirino 2-C','Quirino 3-A','Claro (Quirino 3-B)','Duyan-Duyan','Amihan','Matandang Balara','Pansol','Loyola Heights','San Roque','Mangga','Masagana','Villa Maria Clara','Bayanihan','Camp Aguinaldo','White Plains','Libis','Ugong Norte','Bagumbayan','Blue Ridge A','Blue Ridge B','St. Ignatius','Milagrosa','Escopa I','Escopa II','Escopa III','Escopa IV','Marilag','Bagumbuhay','Tagumpay','Dioquino Zobel'
                    ],
                    '4': [
                        'Sacred Heart','Laging Handa','Obrero','Paligsahan','Roxas','Kamuning','South Triangle','Pinagkaisahan','Immaculate Concepcion','San Martin De Porres','Kaunlaran','Bagong Lipunan ng Crame','Horseshoe','Valencia','Tatalon','Kalusugan','Kristong Hari','Damayang Lagi','Mariana','Doña Imelda','Santol','Sto. Niño','San Isidro Galas','Doña Aurora','Don Manuel','Doña Josefa','UP Village','Old Capitol Site','UP Campus','San Vicente','Teachers Village East','Teachers Village West','Central','Pinyahan','Malaya','Sikatuna Village','Botocan','Krus Na Ligas'
                    ],
                    '5': [
                        'Bagbag','Capri','Greater Lagro','Gulod','Kaligayahan','Nagkaisang Nayon','North Fairview','Novaliches Proper','Pasong Putik Proper','San Agustin','San Bartolome','Sta. Lucia','Sta. Monica','Fairview'
                    ],
                    '6': [
                        'Apolonio Samson','Baesa','Balon Bato','Culiat','New Era','Pasong Tamo','Sangandaan','Tandang Sora','Unang Sigaw','Sauyo','Talipapa'
                    ]
                };

                const escapeHtml = (str) => {
                    return String(str)
                        .replace(/&/g, '&amp;')
                        .replace(/</g, '&lt;')
                        .replace(/>/g, '&gt;')
                        .replace(/"/g, '&quot;')
                        .replace(/'/g, '&#039;');
                };

                const populateBarangays = (districtVal) => {
                    const list = barangaysByDistrict[districtVal] || [];
                    barangayList.innerHTML = list
                        .map(b => `<option value="${escapeHtml(b)}"></option>`)
                        .join('');
                };
                
                districtSelect.addEventListener('change', function() {
                    if (this.value) {
                        barangayInput.disabled = false;
                        barangayInput.placeholder = 'Select or type barangay...';
                        populateBarangays(this.value);
                    } else {
                        barangayInput.disabled = true;
                        barangayInput.value = '';
                        barangayInput.placeholder = 'Select district first, then choose barangay...';
                        barangayList.innerHTML = '';
                    }
                });

                if (districtSelect.value) {
                    barangayInput.disabled = false;
                    barangayInput.placeholder = 'Select or type barangay...';
                    populateBarangays(districtSelect.value);
                } else {
                    barangayInput.disabled = true;
                    barangayInput.placeholder = 'Select district first, then choose barangay...';
                    barangayList.innerHTML = '';
                }
            }
            
            // Handle form submission
            if (profileForm) {
                profileForm.addEventListener('submit', async function(e) {
                    e.preventDefault();
                    
                    // Hide error message
                    if (errorMessage) {
                        errorMessage.style.display = 'none';
                    }
                    
                    // Disable save button
                    if (saveBtn) {
                        saveBtn.disabled = true;
                        saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
                    }
                    
                    // Get form data
                    const formData = {
                        name: (document.getElementById('edit_name')?.value || '').trim(),
                        email: (document.getElementById('edit_email')?.value || '').trim(),
                        phone: (document.getElementById('edit_phone')?.value || '').trim(),
                        nationality: (document.getElementById('edit_nationality')?.value || '').trim(),
                        district: (document.getElementById('edit_district')?.value || ''),
                        barangay: (document.getElementById('edit_barangay')?.value || '').trim(),
                        house_number: (document.getElementById('edit_house_number')?.value || '').trim(),
                        street: (document.getElementById('edit_street')?.value || '').trim()
                    };
                    
                    // Validate
                    if (!formData.name) {
                        showError('Name is required.');
                        enableSaveButton();
                        return;
                    }
                    
                    try {
                        const data = await fetchJsonWithTimeout(getApiPath('api/update-user-profile.php'), {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify(formData)
                        }, 20000);
                        
                        if (data.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Profile Updated',
                                text: 'Your profile has been updated successfully!',
                                timer: 2000,
                                showConfirmButton: false
                            });
                            
                            // Reload page after a short delay to show updated data
                            setTimeout(() => {
                                window.location.reload();
                            }, 2000);
                        } else {
                            showError(data.message || 'Failed to update profile. Please try again.');
                            enableSaveButton();
                        }
                    } catch (error) {
                        console.error('Error updating profile:', error);
                        showError(error && error.message ? error.message : 'An error occurred. Please try again.');
                        enableSaveButton();
                    }
                });
            }
            
            function showError(message) {
                if (errorMessage && errorText) {
                    errorText.textContent = message;
                    errorMessage.style.display = 'flex';
                }
            }
            
            function enableSaveButton() {
                if (saveBtn) {
                    saveBtn.disabled = false;
                    saveBtn.innerHTML = '<i class="fas fa-save"></i> Save Changes';
                }
            }
            
            // Initialize - ensure full name field exists and remove duplicates
            ensureFullNameField();
            
            // Remove any duplicate full name fields that might exist
            const nameInputs = document.querySelectorAll('#edit_name');
            if (nameInputs.length > 1) {
                // Keep the first one, remove duplicates
                for (let i = 1; i < nameInputs.length; i++) {
                    const duplicate = nameInputs[i];
                    const duplicateGroup = duplicate.closest('.form-group');
                    if (duplicateGroup && duplicateGroup !== nameInputs[0].closest('.form-group')) {
                        duplicateGroup.remove();
                    } else {
                        duplicate.remove();
                    }
                }
            }
            
            await loadUserData();
            setupBarangaySuggestions();
        });
    </script>
</body>
</html>

