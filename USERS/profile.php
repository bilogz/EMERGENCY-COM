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
    <script src="js/translations.js"></script>
    <script src="js/language-manager.js"></script>
    <script src="js/language-selector-modal.js"></script>
    <script src="js/language-sync.js"></script>
    <script src="js/global-translator.js"></script>
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
                    
                    <form class="auth-form" id="profileEditForm">
                        <div class="form-group">
                            <label for="edit_name">
                                <i class="fas fa-user"></i> Full Name
                            </label>
                            <input type="text" id="edit_name" name="name" placeholder="Juan Dela Cruz" required>
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
                            <input list="editNationalityList" id="edit_nationality" name="nationality" placeholder="Select nationality">
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
                            <input type="text" id="edit_barangay" name="barangay" placeholder="Type to search barangay..." autocomplete="off">
                            <div id="editBarangaySuggestions" class="suggestions-dropdown" style="display: none;"></div>
                            <small class="form-hint">Start typing to search for your barangay</small>
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
    <script src="js/translations.js"></script>
    <script src="js/language-manager.js"></script>
    <script src="js/language-selector-modal.js"></script>
    <script src="js/language-sync.js"></script>
    <script src="js/global-translator.js"></script>
    <script>
        // Connect language selector button to modal
        document.addEventListener('DOMContentLoaded', function() {
            const langBtn = document.getElementById('languageSelectorBtn');
            if (langBtn && window.languageSelectorModal) {
                langBtn.addEventListener('click', function() {
                    window.languageSelectorModal.open();
                });
            }
        });
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', async function () {
            const profileForm = document.getElementById('profileEditForm');
            const saveBtn = document.getElementById('saveProfileBtn');
            const errorMessage = document.getElementById('profileErrorMessage');
            const errorText = document.getElementById('profileErrorText');
            
            // Load current user data
            async function loadUserData() {
                try {
                    const response = await fetch('api/get-user-profile.php');
                    if (response.ok) {
                        const data = await response.json();
                        if (data.success && data.user) {
                            const user = data.user;
                            
                            // Populate form fields
                            if (document.getElementById('edit_name')) {
                                document.getElementById('edit_name').value = user.name || '';
                            }
                            if (document.getElementById('edit_email')) {
                                document.getElementById('edit_email').value = user.email || '';
                            }
                            if (document.getElementById('edit_phone')) {
                                const phone = user.phone || '';
                                // Remove +63 prefix if present
                                document.getElementById('edit_phone').value = phone.replace(/^\+63/, '');
                            }
                            if (document.getElementById('edit_nationality')) {
                                document.getElementById('edit_nationality').value = user.nationality || '';
                            }
                            if (document.getElementById('edit_district')) {
                                document.getElementById('edit_district').value = user.district || '';
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
                    }
                } catch (error) {
                    console.error('Error loading user data:', error);
                }
            }
            
            // Load barangay suggestions (similar to signup)
            function setupBarangaySuggestions() {
                const districtSelect = document.getElementById('edit_district');
                const barangayInput = document.getElementById('edit_barangay');
                const suggestionsDiv = document.getElementById('editBarangaySuggestions');
                
                if (!districtSelect || !barangayInput || !suggestionsDiv) return;
                
                districtSelect.addEventListener('change', function() {
                    if (this.value) {
                        barangayInput.disabled = false;
                        barangayInput.placeholder = 'Type to search barangay...';
                    } else {
                        barangayInput.disabled = true;
                        barangayInput.value = '';
                        barangayInput.placeholder = 'Select district first, then type to search barangay...';
                    }
                });
                
                barangayInput.addEventListener('input', async function() {
                    const query = this.value.trim();
                    if (query.length < 2) {
                        suggestionsDiv.style.display = 'none';
                        return;
                    }
                    
                    try {
                        const response = await fetch('api/get-barangays.php');
                        if (response.ok) {
                            const data = await response.json();
                            if (data.barangays) {
                                const filtered = data.barangays.filter(b => 
                                    b.toLowerCase().includes(query.toLowerCase())
                                ).slice(0, 10);
                                
                                if (filtered.length > 0) {
                                    suggestionsDiv.innerHTML = filtered.map(b => 
                                        `<div class="suggestion-item">${b}</div>`
                                    ).join('');
                                    suggestionsDiv.style.display = 'block';
                                    
                                    // Add click handlers
                                    suggestionsDiv.querySelectorAll('.suggestion-item').forEach(item => {
                                        item.addEventListener('click', function() {
                                            barangayInput.value = this.textContent;
                                            suggestionsDiv.style.display = 'none';
                                        });
                                    });
                                } else {
                                    suggestionsDiv.style.display = 'none';
                                }
                            }
                        }
                    } catch (error) {
                        console.error('Error loading barangays:', error);
                    }
                });
                
                // Hide suggestions when clicking outside
                document.addEventListener('click', function(e) {
                    if (!barangayInput.contains(e.target) && !suggestionsDiv.contains(e.target)) {
                        suggestionsDiv.style.display = 'none';
                    }
                });
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
                        name: document.getElementById('edit_name').value.trim(),
                        email: document.getElementById('edit_email').value.trim(),
                        phone: document.getElementById('edit_phone').value.trim(),
                        nationality: document.getElementById('edit_nationality').value.trim(),
                        district: document.getElementById('edit_district').value,
                        barangay: document.getElementById('edit_barangay').value.trim(),
                        house_number: document.getElementById('edit_house_number').value.trim(),
                        street: document.getElementById('edit_street').value.trim()
                    };
                    
                    // Validate
                    if (!formData.name) {
                        showError('Name is required.');
                        enableSaveButton();
                        return;
                    }
                    
                    try {
                        const response = await fetch('api/update-user-profile.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify(formData)
                        });
                        
                        const data = await response.json();
                        
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
                        showError('An error occurred. Please try again.');
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
            
            // Initialize
            await loadUserData();
            setupBarangaySuggestions();
        });
    </script>
</body>
</html>

