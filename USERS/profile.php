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
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <button class="sidebar-toggle-btn" aria-label="Toggle menu" onclick="window.sidebarToggle()">
        <i class="fas fa-bars"></i>
    </button>

    <main class="main-content">
        <div class="hero-section" id="profile">
            <div class="main-container">
                <div class="sub-container">
                    <h1>Profile & Preferences</h1>
                    <p>Manage your contact methods, preferred languages, and alert categories.</p>
                </div>
            </div>
        </div>

        <div class="main-container">
            <div class="sub-container content-main">
                <section class="page-content">
                    <h2>Your Settings</h2>
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
                    </div>
                </section>

                <section class="page-content">
                    <h2 data-translate="profile.language.title">Language Settings</h2>
                    <p data-translate="profile.language.desc">Choose your preferred language. This will be used for alerts and interface text where available. Languages update automatically when new ones are added.</p>
                    
                    <div class="info-box" style="background: #e3f2fd; border-left: 4px solid #2196f3; padding: 1rem; border-radius: 4px; margin-bottom: 1.5rem;">
                        <i class="fas fa-info-circle" style="color: #2196f3;"></i>
                        <strong>Auto-Detection:</strong> Your device language has been detected. You can change it below or use the language selector icon in the top-right corner.
                    </div>
                    
                    <form class="auth-form" id="languageSettingsForm">
                        <div class="form-group">
                            <label data-translate="profile.language.label">Preferred Language</label>
                            <select name="preferred_language" id="preferredLanguageSelect" class="form-control">
                                <option value="">Loading languages...</option>
                            </select>
                            <small class="form-text" style="margin-top: 0.5rem; color: #666;">
                                <i class="fas fa-sync-alt"></i> Languages are updated in real-time. New languages will appear automatically.
                            </small>
                        </div>
                        <div class="form-group">
                            <label>
                                <input type="checkbox" id="autoDetectLanguage" checked>
                                <span>Auto-detect device language</span>
                            </label>
                            <small class="form-text" style="display: block; margin-top: 0.5rem; color: #666;">
                                Automatically use your device's language when available
                            </small>
                        </div>
                        <button type="button" class="btn btn-primary" id="saveLanguageBtn" data-translate="profile.language.save">
                            <i class="fas fa-save"></i> Save Language Settings
                        </button>
                    </form>
                    
                    <div id="languageInfo" style="margin-top: 1.5rem; padding: 1rem; background: #f8f9fa; border-radius: 8px;">
                        <h3 style="font-size: 14px; margin-bottom: 0.5rem;">Current Language Information</h3>
                        <div id="currentLanguageInfo" style="font-size: 13px; color: #666;">
                            Loading...
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
    <script src="js/translations.js"></script>
    <script src="js/language-manager.js"></script>
    <script src="js/language-selector-enhanced.js"></script>
    <script src="js/language-selector-modal.js"></script>
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
            const selectDropdown = document.getElementById('preferredLanguageSelect');
            const saveBtn = document.getElementById('saveLanguageBtn');
            const autoDetectCheckbox = document.getElementById('autoDetectLanguage');
            const languageInfo = document.getElementById('currentLanguageInfo');
            
            // Wait for language manager to initialize
            if (typeof window.languageManager === 'undefined') {
                await new Promise(resolve => {
                    const checkInterval = setInterval(() => {
                        if (typeof window.languageManager !== 'undefined') {
                            clearInterval(checkInterval);
                            resolve();
                        }
                    }, 100);
                });
            }
            
            const langManager = window.languageManager;
            
            // Load languages into dropdown
            async function loadLanguagesIntoDropdown() {
                if (!selectDropdown) return;
                
                selectDropdown.innerHTML = '<option value="">Select language...</option>';
                
                langManager.supportedLanguages.forEach(lang => {
                    const option = document.createElement('option');
                    option.value = lang.language_code;
                    const displayText = lang.flag_emoji ? `${lang.flag_emoji} ${lang.language_name}` : lang.language_name;
                    option.textContent = displayText;
                    if (lang.language_code === langManager.currentLanguage) {
                        option.selected = true;
                    }
                    selectDropdown.appendChild(option);
                });
            }
            
            // Update language info display
            function updateLanguageInfo() {
                if (!languageInfo) return;
                
                const currentLang = langManager.currentLanguage;
                const langInfo = langManager.getLanguageInfo(currentLang);
                const deviceLang = langManager.deviceLanguage;
                
                let infoHTML = `
                    <strong>Current:</strong> ${langManager.getLanguageDisplay(currentLang)}<br>
                `;
                
                if (langInfo && langInfo.native_name && langInfo.native_name !== langInfo.language_name) {
                    infoHTML += `<strong>Native Name:</strong> ${langInfo.native_name}<br>`;
                }
                
                if (deviceLang && deviceLang !== currentLang) {
                    infoHTML += `<strong>Device Language:</strong> ${langManager.getLanguageDisplay(deviceLang)}<br>`;
                }
                
                infoHTML += `<strong>Total Languages:</strong> ${langManager.supportedLanguages.length} available`;
                
                languageInfo.innerHTML = infoHTML;
            }
            
            // Load languages
            await loadLanguagesIntoDropdown();
            updateLanguageInfo();
            
            // Set auto-detect checkbox
            if (autoDetectCheckbox) {
                const autoDetectEnabled = localStorage.getItem('auto_detect_language') !== 'false';
                autoDetectCheckbox.checked = autoDetectEnabled;
                
                autoDetectCheckbox.addEventListener('change', function() {
                    localStorage.setItem('auto_detect_language', this.checked ? 'true' : 'false');
                });
            }
            
            // Listen for language updates
            document.addEventListener('languagesUpdated', async () => {
                await loadLanguagesIntoDropdown();
                updateLanguageInfo();
            });
            
            // Save language
            if (saveBtn) {
                saveBtn.addEventListener('click', async function () {
                    if (!selectDropdown) return;
                    const lang = selectDropdown.value;
                    
                    if (!lang) {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Please select a language',
                            text: 'You must select a language before saving.'
                        });
                        return;
                    }
                    
                    try {
                        await langManager.setLanguage(lang);
                        
                        // Update auto-detect setting
                        if (autoDetectCheckbox) {
                            localStorage.setItem('auto_detect_language', autoDetectCheckbox.checked ? 'true' : 'false');
                        }
                        
                        updateLanguageInfo();
                        
                        Swal.fire({
                            icon: 'success',
                            title: 'Language updated',
                            text: `Your preferred language has been set to ${langManager.getLanguageDisplay(lang)}.`,
                            timer: 2000,
                            showConfirmButton: false
                        });
                    } catch (error) {
                        console.error('Error saving language:', error);
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Failed to save language preference. Please try again.'
                        });
                    }
                });
            }
            
            // Update info when language changes
            document.addEventListener('languageChanged', () => {
                if (selectDropdown) {
                    selectDropdown.value = langManager.currentLanguage;
                }
                updateLanguageInfo();
            });
        });
    </script>
</body>
</html>

