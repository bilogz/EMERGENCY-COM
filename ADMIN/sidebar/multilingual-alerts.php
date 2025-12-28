<?php
/**
 * Multilingual Support for Alerts Page
 * Manage alert translations and multilingual content
 */

$pageTitle = 'Multilingual Support for Alerts';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <link rel="icon" type="image/x-icon" href="images/favicon.ico">
    <link rel="stylesheet" href="css/global.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/sidebar.css">
    <link rel="stylesheet" href="css/admin-header.css">
    <link rel="stylesheet" href="css/buttons.css">
    <link rel="stylesheet" href="css/forms.css">
    <link rel="stylesheet" href="css/datatables.css">
    <link rel="stylesheet" href="css/hero.css">
    <link rel="stylesheet" href="css/sidebar-footer.css">
    <link rel="stylesheet" href="css/modules.css">
</head>
<body>
    <!-- Include Sidebar Component -->
    <?php include 'includes/sidebar.php'; ?>

    <!-- Include Admin Header Component -->
    <?php include 'includes/admin-header.php'; ?>
    
    <!-- ===================================
       MAIN CONTENT - Multilingual Support
       =================================== -->
    <div class="main-content">
        <div class="main-container">
            <div class="title">
                <nav class="breadcrumb" aria-label="Breadcrumb">
                    <ol class="breadcrumb-list">
                        <li class="breadcrumb-item">
                            <a href="/" class="breadcrumb-link">
                                <span>Home</span>
                            </a>
                        </li>
                        <li class="breadcrumb-item">
                            <a href="/emergency-communication" class="breadcrumb-link">
                                <span>Emergency Communication</span>
                            </a>
                        </li>
                        <li class="breadcrumb-item active" aria-current="page">
                            <span>Multilingual Support</span>
                        </li>
                    </ol>
                </nav>
                <h1>Multilingual Support for Alerts</h1>
                <p>Manage alert translations to ensure alerts can be delivered in multiple languages for better accessibility.</p>
                <div class="info-box" style="background: #e3f2fd; border-left: 4px solid #2196f3; padding: 1rem; border-radius: 4px; margin-top: 1rem;">
                    <i class="fas fa-info-circle" style="color: #2196f3;"></i>
                    <strong>How to use:</strong> Select an alert, choose the target language, and provide the translated title and content. The system will automatically send alerts in the subscriber's preferred language.
                </div>
            </div>
            
            <div class="sub-container">
                <div class="page-content">
                    <!-- Supported Languages -->
                    <div class="module-card">
                        <div class="module-card-header">
                            <h2><i class="fas fa-language"></i> Supported Languages</h2>
                            <p style="margin-top: 0.5rem; color: #666; font-size: 0.9rem;">
                                <i class="fas fa-info-circle"></i> Languages marked with <i class="fas fa-robot"></i> support AI translation
                            </p>
                        </div>
                        <div>
                            <div id="languagesGrid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                                <div style="text-align: center; padding: 2rem; color: #999;">
                                    <i class="fas fa-spinner fa-spin"></i> Loading languages...
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Translate Alert -->
                    <div class="module-card">
                        <div class="module-card-header">
                            <h2><i class="fas fa-language"></i> Translate Alert</h2>
                            <div style="margin-top: 0.5rem;">
                                <label class="checkbox-label" style="display: flex; align-items: center; gap: 0.5rem;">
                                    <input type="checkbox" id="useAI" checked>
                                    <span><i class="fas fa-robot"></i> Use AI Translation (Gemini)</span>
                                </label>
                            </div>
                        </div>
                        <div>
                            <form id="translationForm">
                                <div class="form-group">
                                    <label for="alertSelect">Select Alert</label>
                                    <select id="alertSelect" name="alert_id" required>
                                        <option value="">-- Select an alert --</option>
                                        <!-- Options will be loaded via API -->
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="targetLanguage">Target Language</label>
                                    <select id="targetLanguage" name="target_language" required>
                                        <option value="">-- Loading languages --</option>
                                    </select>
                                </div>
                                <div id="manualTranslationFields" style="display: none;">
                                    <div class="form-group">
                                        <label for="translatedTitle">Translated Title *</label>
                                        <input type="text" id="translatedTitle" name="translated_title">
                                    </div>
                                    <div class="form-group">
                                        <label for="translatedContent">Translated Content *</label>
                                        <textarea id="translatedContent" name="translated_content" rows="6"></textarea>
                                    </div>
                                </div>
                                <div id="aiTranslationStatus" style="display: none; padding: 1rem; background: #f5f5f5; border-radius: 4px; margin-bottom: 1rem;">
                                    <div id="aiStatusMessage"></div>
                                </div>
                                <div class="form-actions">
                                    <button type="submit" class="btn btn-primary" id="translateBtn">
                                        <i class="fas fa-language"></i> <span id="translateBtnText">Translate with AI</span>
                                    </button>
                                    <button type="button" class="btn btn-secondary" onclick="loadPreview()" id="previewBtn" style="display: none;">
                                        <i class="fas fa-eye"></i> Preview Translation
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Translation Activity Logs -->
                    <div class="module-card">
                        <div class="module-card-header">
                            <h2><i class="fas fa-clipboard-list"></i> Translation Activity Logs</h2>
                            <button class="btn btn-sm btn-secondary" onclick="loadActivityLogs()">
                                <i class="fas fa-sync"></i> Refresh
                            </button>
                        </div>
                        <div>
                            <table class="data-table" id="activityLogsTable" style="display: none;">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Action</th>
                                        <th>Alert</th>
                                        <th>Languages</th>
                                        <th>Method</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Data will be loaded via API -->
                                </tbody>
                            </table>
                            <div id="activityLogsLoading" style="text-align: center; padding: 2rem;">
                                <button class="btn btn-primary" onclick="loadActivityLogs()">
                                    <i class="fas fa-eye"></i> View Activity Logs
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Translation History -->
                    <div class="module-card">
                        <div class="module-card-header">
                            <h2><i class="fas fa-history"></i> Translation History</h2>
                        </div>
                        <div>
                            <table class="data-table" id="translationsTable">
                                <thead>
                                    <tr>
                                        <th>Alert ID</th>
                                        <th>Original Language</th>
                                        <th>Translated Language</th>
                                        <th>Title</th>
                                        <th>Method</th>
                                        <th>Translated By</th>
                                        <th>Status</th>
                                        <th>Translated At</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Data will be loaded via API -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        let supportedLanguages = [];
        let aiServiceAvailable = false;
        
        // Load supported languages
        function loadLanguages() {
            fetch('../api/multilingual-alerts.php?action=languages')
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.languages) {
                        supportedLanguages = data.languages;
                        const select = document.getElementById('targetLanguage');
                        select.innerHTML = '<option value="">-- Select language --</option>';
                        
                        data.languages.forEach(lang => {
                            const option = document.createElement('option');
                            option.value = lang.language_code;
                            const displayName = lang.flag_emoji ? `${lang.flag_emoji} ${lang.language_name}` : lang.language_name;
                            option.textContent = displayName;
                            option.dataset.aiSupported = lang.is_ai_supported ? '1' : '0';
                            select.appendChild(option);
                        });
                        
                        // Check AI availability
                        checkAIAvailability();
                    }
                })
                .catch(error => {
                    console.error('Error loading languages:', error);
                    // Fallback to basic languages
                    const select = document.getElementById('targetLanguage');
                    select.innerHTML = `
                        <option value="fil">🇵🇭 Filipino</option>
                        <option value="ceb">🇵🇭 Cebuano</option>
                        <option value="es">🇪🇸 Spanish</option>
                        <option value="fr">🇫🇷 French</option>
                    `;
                });
        }
        
        // Check if AI translation is available
        function checkAIAvailability() {
            fetch('../api/ai-translation-service.php')
                .then(response => response.json())
                .then(data => {
                    aiServiceAvailable = data.available === true;
                    updateUIForAI();
                })
                .catch(error => {
                    console.error('Error checking AI availability:', error);
                    aiServiceAvailable = false;
                    updateUIForAI();
                });
        }
        
        // Update UI based on AI availability and checkbox state
        function updateUIForAI() {
            const useAI = document.getElementById('useAI').checked;
            const manualFields = document.getElementById('manualTranslationFields');
            const translateBtn = document.getElementById('translateBtn');
            const translateBtnText = document.getElementById('translateBtnText');
            const targetLanguage = document.getElementById('targetLanguage');
            const selectedOption = targetLanguage.options[targetLanguage.selectedIndex];
            const isAISupported = selectedOption && selectedOption.dataset.aiSupported === '1';
            
            if (useAI && aiServiceAvailable && isAISupported) {
                manualFields.style.display = 'none';
                translateBtnText.textContent = 'Translate with AI';
                translateBtn.querySelector('i').className = 'fas fa-robot';
            } else {
                manualFields.style.display = 'block';
                translateBtnText.textContent = 'Save Translation';
                translateBtn.querySelector('i').className = 'fas fa-save';
                
                if (!aiServiceAvailable) {
                    document.getElementById('useAI').disabled = true;
                    showAIStatus('AI translation is not available. Please configure Gemini API key.', 'warning');
                } else if (!isAISupported) {
                    showAIStatus('AI translation is not supported for this language. Please provide manual translation.', 'info');
                }
            }
        }
        
        function showAIStatus(message, type = 'info') {
            const statusDiv = document.getElementById('aiTranslationStatus');
            const messageDiv = document.getElementById('aiStatusMessage');
            const colors = {
                'info': '#2196f3',
                'warning': '#ff9800',
                'success': '#4caf50',
                'error': '#f44336'
            };
            messageDiv.innerHTML = `<i class="fas fa-info-circle"></i> ${message}`;
            statusDiv.style.borderLeft = `4px solid ${colors[type] || colors.info}`;
            statusDiv.style.display = 'block';
        }
        
        function loadAlerts() {
            fetch('../api/alerts.php')
                .then(response => response.json())
                .then(data => {
                    const select = document.getElementById('alertSelect');
                    select.innerHTML = '<option value="">-- Select an alert --</option>';
                    
                    if (Array.isArray(data)) {
                        data.forEach(alert => {
                            const option = document.createElement('option');
                            option.value = alert.id;
                            option.textContent = alert.title;
                            select.appendChild(option);
                        });
                    }
                });
        }

        document.getElementById('alertSelect').addEventListener('change', function() {
            const alertId = this.value;
            if (alertId) {
                fetch(`../api/alerts.php?id=${alertId}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data && data.length > 0) {
                            const alert = data[0];
                            document.getElementById('translatedTitle').placeholder = alert.title;
                            document.getElementById('translatedContent').placeholder = alert.message || alert.content;
                        }
                    });
            }
        });

        document.getElementById('translationForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const alertId = document.getElementById('alertSelect').value;
            const targetLanguage = document.getElementById('targetLanguage').value;
            const useAI = document.getElementById('useAI').checked;
            
            if (!alertId || !targetLanguage) {
                alert('Please select an alert and target language.');
                return;
            }
            
            // Show loading state
            const translateBtn = document.getElementById('translateBtn');
            const originalText = translateBtn.innerHTML;
            translateBtn.disabled = true;
            translateBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Translating...';
            
            if (useAI && aiServiceAvailable) {
                // AI Translation
                fetch('../api/multilingual-alerts.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        alert_id: alertId,
                        target_language: targetLanguage,
                        use_ai: true,
                        source_language: 'en'
                    })
                })
                .then(response => response.json())
                .then(data => {
                    translateBtn.disabled = false;
                    translateBtn.innerHTML = originalText;
                    
                    if (data.success) {
                        showAIStatus(`Translation completed successfully! (Method: ${data.method})`, 'success');
                        document.getElementById('translatedTitle').value = data.translated_title || '';
                        document.getElementById('translatedContent').value = data.translated_content || '';
                        document.getElementById('previewBtn').style.display = 'inline-block';
                        loadTranslations();
                        
                        // Auto-hide success message after 5 seconds
                        setTimeout(() => {
                            document.getElementById('aiTranslationStatus').style.display = 'none';
                        }, 5000);
                    } else {
                        showAIStatus('Translation failed: ' + data.message, 'error');
                        // Show manual fields as fallback
                        document.getElementById('manualTranslationFields').style.display = 'block';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    translateBtn.disabled = false;
                    translateBtn.innerHTML = originalText;
                    showAIStatus('An error occurred during AI translation. Please try manual translation.', 'error');
                    document.getElementById('manualTranslationFields').style.display = 'block';
                });
            } else {
                // Manual Translation
                const translatedTitle = document.getElementById('translatedTitle').value;
                const translatedContent = document.getElementById('translatedContent').value;
                
                if (!translatedTitle || !translatedContent) {
                    alert('Please provide translated title and content for manual translation.');
                    translateBtn.disabled = false;
                    translateBtn.innerHTML = originalText;
                    return;
                }
                
                const formData = new FormData();
                formData.append('alert_id', alertId);
                formData.append('target_language', targetLanguage);
                formData.append('translated_title', translatedTitle);
                formData.append('translated_content', translatedContent);
                formData.append('use_ai', '0');
                
                fetch('../api/multilingual-alerts.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    translateBtn.disabled = false;
                    translateBtn.innerHTML = originalText;
                    
                    if (data.success) {
                        alert('Translation saved successfully!');
                        this.reset();
                        loadTranslations();
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    translateBtn.disabled = false;
                    translateBtn.innerHTML = originalText;
                    alert('An error occurred while saving the translation.');
                });
            }
        });
        
        // Update UI when AI checkbox changes
        document.getElementById('useAI').addEventListener('change', updateUIForAI);
        document.getElementById('targetLanguage').addEventListener('change', updateUIForAI);
        
        function loadPreview() {
            const alertId = document.getElementById('alertSelect').value;
            const targetLanguage = document.getElementById('targetLanguage').value;
            
            if (!alertId || !targetLanguage) {
                alert('Please select an alert and target language first.');
                return;
            }
            
            fetch(`../api/multilingual-alerts.php?action=list&alert_id=${alertId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.translations) {
                        const translation = data.translations.find(t => t.target_language === targetLanguage);
                        if (translation) {
                            alert(`Preview:\n\nTitle: ${translation.translated_title}\n\nContent: ${translation.translated_content.substring(0, 200)}...`);
                        } else {
                            alert('No translation found for this language.');
                        }
                    }
                });
        }

        function loadTranslations() {
            fetch('../api/multilingual-alerts.php?action=list')
                .then(response => response.json())
                .then(data => {
                    const tbody = document.querySelector('#translationsTable tbody');
                    tbody.innerHTML = '';
                    
                    if (data.success && data.translations) {
                        data.translations.forEach(trans => {
                            const row = document.createElement('tr');
                            const methodBadge = trans.translation_method === 'ai' 
                                ? '<span class="badge" style="background: #2196f3;"><i class="fas fa-robot"></i> AI</span>'
                                : '<span class="badge" style="background: #666;"><i class="fas fa-user"></i> Manual</span>';
                            const languageName = trans.language_name || trans.target_language;
                            const flagEmoji = trans.flag_emoji || '🌐';
                            
                            row.innerHTML = `
                                <td>${trans.alert_id}</td>
                                <td>${trans.original_language || 'en'}</td>
                                <td>${flagEmoji} ${languageName}</td>
                                <td>${trans.translated_title}</td>
                                <td>${methodBadge}</td>
                                <td>${trans.translated_by_name || 'System'}</td>
                                <td><span class="badge ${trans.status === 'active' ? 'success' : 'warning'}">${trans.status}</span></td>
                                <td>${new Date(trans.translated_at).toLocaleString()}</td>
                                <td>
                                    <button class="btn btn-sm btn-primary" onclick="editTranslation(${trans.id})" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-sm btn-danger" onclick="deleteTranslation(${trans.id})" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            `;
                            tbody.appendChild(row);
                        });
                    }
                });
        }

        function loadLanguagesGrid() {
            fetch('../api/multilingual-alerts.php?action=languages')
                .then(response => response.json())
                .then(data => {
                    const grid = document.getElementById('languagesGrid');
                    grid.innerHTML = '';
                    
                    if (data.success && data.languages) {
                        data.languages.forEach(lang => {
                            const card = document.createElement('div');
                            card.className = 'channel-card';
                            card.innerHTML = `
                                <div>
                                    <h3>${lang.flag_emoji || '🌐'} ${lang.language_name}</h3>
                                    <p>Native: ${lang.native_name || lang.language_name}</p>
                                    <p>Status: <span class="badge ${lang.is_active ? 'success' : 'warning'}">${lang.is_active ? 'Active' : 'Inactive'}</span></p>
                                    ${lang.is_ai_supported ? '<p><i class="fas fa-robot" style="color: #2196f3;"></i> AI Supported</p>' : ''}
                                    <button class="btn btn-sm btn-primary" onclick="manageLanguage('${lang.language_code}')">
                                        <i class="fas fa-cog"></i> Manage
                                    </button>
                                </div>
                            `;
                            grid.appendChild(card);
                        });
                    }
                })
                .catch(error => {
                    console.error('Error loading languages:', error);
                });
        }
        
        function manageLanguage(langCode) {
            alert('Language management for ' + langCode + ' - Feature coming soon!');
        }

        function editTranslation(id) {
            alert('Edit translation ' + id);
        }

        function deleteTranslation(id) {
            if (confirm('Are you sure you want to delete this translation?')) {
                fetch('../api/multilingual-alerts.php', {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({id: id})
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Translation deleted successfully!');
                        loadTranslations();
                        loadActivityLogs(); // Refresh activity logs
                    } else {
                        alert('Error: ' + data.message);
                    }
                });
            }
        }
        
        function loadActivityLogs() {
            const loadingDiv = document.getElementById('activityLogsLoading');
            const table = document.getElementById('activityLogsTable');
            
            loadingDiv.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading activity logs...';
            
            fetch('../api/multilingual-alerts.php?action=activity')
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.logs) {
                        const tbody = document.querySelector('#activityLogsTable tbody');
                        tbody.innerHTML = '';
                        
                        if (data.logs.length === 0) {
                            tbody.innerHTML = '<tr><td colspan="6" style="text-align: center; padding: 2rem; color: #999;">No activity logs found</td></tr>';
                        } else {
                            data.logs.forEach(log => {
                                const row = document.createElement('tr');
                                const statusBadge = log.success 
                                    ? '<span class="badge success">Success</span>'
                                    : '<span class="badge error">Failed</span>';
                                const methodBadge = log.translation_method === 'ai'
                                    ? '<span class="badge" style="background: #2196f3;"><i class="fas fa-robot"></i> AI</span>'
                                    : log.translation_method === 'manual'
                                    ? '<span class="badge" style="background: #666;"><i class="fas fa-user"></i> Manual</span>'
                                    : '';
                                
                                row.innerHTML = `
                                    <td>${new Date(log.created_at).toLocaleString()}</td>
                                    <td><code>${log.action_type}</code></td>
                                    <td>${log.alert_title || 'Alert #' + (log.alert_id || 'N/A')}</td>
                                    <td>${log.source_language || 'en'} → ${log.target_language || 'N/A'}</td>
                                    <td>${methodBadge}</td>
                                    <td>${statusBadge}</td>
                                `;
                                tbody.appendChild(row);
                            });
                        }
                        
                        table.style.display = 'table';
                        loadingDiv.style.display = 'none';
                    } else {
                        loadingDiv.innerHTML = '<span style="color: #f44336;">Error loading activity logs: ' + (data.message || 'Unknown error') + '</span>';
                    }
                })
                .catch(error => {
                    console.error('Error loading activity logs:', error);
                    loadingDiv.innerHTML = '<span style="color: #f44336;">Error loading activity logs. Please try again.</span>';
                });
        }
        
        function loadActivityLogs() {
            const loadingDiv = document.getElementById('activityLogsLoading');
            const table = document.getElementById('activityLogsTable');
            
            loadingDiv.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading activity logs...';
            
            fetch('../api/multilingual-alerts.php?action=activity')
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.logs) {
                        const tbody = document.querySelector('#activityLogsTable tbody');
                        tbody.innerHTML = '';
                        
                        if (data.logs.length === 0) {
                            tbody.innerHTML = '<tr><td colspan="6" style="text-align: center; padding: 2rem; color: #999;">No activity logs found</td></tr>';
                        } else {
                            data.logs.forEach(log => {
                                const row = document.createElement('tr');
                                const statusBadge = log.success 
                                    ? '<span class="badge success">Success</span>'
                                    : '<span class="badge error">Failed</span>';
                                const methodBadge = log.translation_method === 'ai'
                                    ? '<span class="badge" style="background: #2196f3;"><i class="fas fa-robot"></i> AI</span>'
                                    : log.translation_method === 'manual'
                                    ? '<span class="badge" style="background: #666;"><i class="fas fa-user"></i> Manual</span>'
                                    : '';
                                
                                row.innerHTML = `
                                    <td>${new Date(log.created_at).toLocaleString()}</td>
                                    <td><code>${log.action_type}</code></td>
                                    <td>${log.alert_title || 'Alert #' + log.alert_id || 'N/A'}</td>
                                    <td>${log.source_language || 'en'} → ${log.target_language || 'N/A'}</td>
                                    <td>${methodBadge}</td>
                                    <td>${statusBadge}</td>
                                `;
                                tbody.appendChild(row);
                            });
                        }
                        
                        table.style.display = 'table';
                        loadingDiv.style.display = 'none';
                    } else {
                        loadingDiv.innerHTML = '<span style="color: #f44336;">Error loading activity logs: ' + (data.message || 'Unknown error') + '</span>';
                    }
                })
                .catch(error => {
                    console.error('Error loading activity logs:', error);
                    loadingDiv.innerHTML = '<span style="color: #f44336;">Error loading activity logs. Please try again.</span>';
                });
        }

        // Load data on page load
        document.addEventListener('DOMContentLoaded', function() {
            loadLanguages();
            loadLanguagesGrid();
            loadAlerts();
            loadTranslations();
        });
    </script>
</body>
</html>

