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
    <style>
        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.5);
            animation: fadeIn 0.3s;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        .modal-content {
            background-color: #fff;
            margin: 5% auto;
            border-radius: 8px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
            animation: slideDown 0.3s;
        }
        
        @keyframes slideDown {
            from {
                transform: translateY(-50px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
        
        .modal-close {
            color: #999;
            font-size: 28px;
            font-weight: bold;
            transition: color 0.2s;
        }
        
        .modal-close:hover,
        .modal-close:focus {
            color: #333;
            text-decoration: none;
        }
        
        .language-list-item {
            cursor: default;
        }
        
        .language-list-item:hover {
            background-color: #f8f9fa !important;
        }
        
        #languageSearch:focus {
            outline: none;
            border-color: #2196f3;
            box-shadow: 0 0 0 2px rgba(33, 150, 243, 0.2);
        }
    </style>
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
                <p>AI-powered automatic translation ensures alerts are delivered in each user's preferred language.</p>
                <div class="info-box" style="background: #e3f2fd; border-left: 4px solid #2196f3; padding: 1rem; border-radius: 4px; margin-top: 1rem;">
                    <i class="fas fa-robot" style="color: #2196f3;"></i>
                    <strong>Automatic Translation:</strong> When alerts are sent, the AI automatically translates them based on each user's language preference (from login) or guest language preference. No manual translation needed!
                </div>
            </div>
            
            <div class="sub-container">
                <div class="page-content">
                    <!-- Supported Languages Info -->
                    <div class="module-card">
                        <div class="module-card-header">
                            <h2><i class="fas fa-language"></i> Supported Languages</h2>
                            <p style="margin-top: 0.5rem; color: #666; font-size: 0.9rem;">
                                <i class="fas fa-info-circle"></i> AI can translate alerts to any language supported by Gemini AI
                            </p>
                        </div>
                        <div>
                            <button class="btn btn-primary" onclick="openLanguagesModal()">
                                <i class="fas fa-list"></i> View Supported Languages (<span id="languageCount">-</span>)
                            </button>
                        </div>
                    </div>

                    <!-- Languages Modal -->
                    <div id="languagesModal" class="modal" style="display: none;">
                        <div class="modal-content" style="max-width: 600px; max-height: 80vh;">
                            <div class="modal-header" style="display: flex; justify-content: space-between; align-items: center; padding: 1rem; border-bottom: 1px solid #ddd;">
                                <h3 style="margin: 0;"><i class="fas fa-language"></i> Supported Languages</h3>
                                <span class="modal-close" onclick="closeLanguagesModal()" style="cursor: pointer; font-size: 1.5rem; color: #999;">&times;</span>
                            </div>
                            <div class="modal-body" style="padding: 1rem; max-height: 60vh; overflow-y: auto;">
                                <div style="margin-bottom: 1rem;">
                                    <input type="text" id="languageSearch" placeholder="Search languages..." 
                                           style="width: 100%; padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px;"
                                           onkeyup="filterLanguages()">
                                </div>
                                <div id="languagesList" style="list-style: none; padding: 0; margin: 0;">
                                    <div style="text-align: center; padding: 2rem; color: #999;">
                                        <i class="fas fa-spinner fa-spin"></i> Loading languages...
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer" style="padding: 1rem; border-top: 1px solid #ddd; text-align: right;">
                                <button class="btn btn-secondary" onclick="closeLanguagesModal()">Close</button>
                            </div>
                        </div>
                    </div>

                    <!-- Automatic Translation Info -->
                    <div class="module-card">
                        <div class="module-card-header">
                            <h2><i class="fas fa-robot"></i> Automatic AI Translation</h2>
                        </div>
                        <div>
                            <div style="padding: 1.5rem; background: #f8f9fa; border-radius: 8px;">
                                <h3 style="margin-top: 0; color: #2196f3;">
                                    <i class="fas fa-magic"></i> How It Works
                                </h3>
                                <ol style="line-height: 2; color: #555;">
                                    <li><strong>User Language Preference:</strong> When users log in or login as guests, their language preference is automatically detected and saved.</li>
                                    <li><strong>Automatic Translation:</strong> When alerts are sent, the AI automatically translates each alert to match the recipient's preferred language.</li>
                                    <li><strong>No Manual Work:</strong> Admins don't need to manually translate alerts - the AI handles it automatically!</li>
                                    <li><strong>Real-time:</strong> Translations happen in real-time when alerts are sent, ensuring accuracy and freshness.</li>
                                </ol>
                                <div style="margin-top: 1.5rem; padding: 1rem; background: #e8f5e9; border-left: 4px solid #4caf50; border-radius: 4px;">
                                    <i class="fas fa-check-circle" style="color: #4caf50;"></i>
                                    <strong>Status:</strong> <span id="aiServiceStatus">Checking AI service...</span>
                                </div>
                            </div>
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
        
        // Check if AI translation is available
        function checkAIAvailability() {
            fetch('../api/ai-translation-service.php')
                .then(response => response.json())
                .then(data => {
                    aiServiceAvailable = data.available === true;
                    const statusElement = document.getElementById('aiServiceStatus');
                    if (aiServiceAvailable) {
                        statusElement.innerHTML = '<span style="color: #4caf50;"><i class="fas fa-check-circle"></i> AI Translation Service is Active and Ready</span>';
                    } else {
                        statusElement.innerHTML = '<span style="color: #f44336;"><i class="fas fa-exclamation-triangle"></i> AI Translation Service is Not Available. Please configure Gemini API key.</span>';
                    }
                })
                .catch(error => {
                    console.error('Error checking AI availability:', error);
                    aiServiceAvailable = false;
                    const statusElement = document.getElementById('aiServiceStatus');
                    statusElement.innerHTML = '<span style="color: #f44336;"><i class="fas fa-exclamation-triangle"></i> Error checking AI service status.</span>';
                });
        }
        
        // Load supported languages for display
        function loadLanguages() {
            fetch('../api/multilingual-alerts.php?action=languages')
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.languages) {
                        supportedLanguages = data.languages;
                        document.getElementById('languageCount').textContent = data.languages.length;
                        loadLanguagesList();
                    }
                })
                .catch(error => {
                    console.error('Error loading languages:', error);
                    document.getElementById('languageCount').textContent = '0';
                });
        }
        
        // Load languages into modal list
        function loadLanguagesList() {
            const list = document.getElementById('languagesList');
            list.innerHTML = '';
            
            if (supportedLanguages.length === 0) {
                list.innerHTML = '<div style="text-align: center; padding: 2rem; color: #999;">No languages found</div>';
                return;
            }
            
            supportedLanguages.forEach(lang => {
                const listItem = document.createElement('div');
                listItem.className = 'language-list-item';
                listItem.dataset.languageName = (lang.language_name + ' ' + (lang.native_name || '') + ' ' + lang.language_code).toLowerCase();
                listItem.style.cssText = 'display: flex; align-items: center; justify-content: space-between; padding: 0.75rem; border-bottom: 1px solid #f0f0f0; transition: background 0.2s;';
                listItem.onmouseover = function() { this.style.background = '#f8f9fa'; };
                listItem.onmouseout = function() { this.style.background = ''; };
                
                const leftSection = document.createElement('div');
                leftSection.style.cssText = 'display: flex; align-items: center; gap: 0.75rem; flex: 1;';
                
                const flag = document.createElement('span');
                flag.style.cssText = 'font-size: 1.5rem;';
                flag.textContent = lang.flag_emoji || '🌐';
                
                const info = document.createElement('div');
                info.style.cssText = 'flex: 1;';
                
                const name = document.createElement('div');
                name.style.cssText = 'font-weight: 600; color: #333; margin-bottom: 0.25rem;';
                name.textContent = lang.language_name;
                
                const native = document.createElement('div');
                native.style.cssText = 'font-size: 0.85rem; color: #666;';
                native.textContent = lang.native_name || lang.language_name;
                
                info.appendChild(name);
                info.appendChild(native);
                
                const rightSection = document.createElement('div');
                rightSection.style.cssText = 'display: flex; align-items: center; gap: 0.5rem;';
                
                const statusBadge = document.createElement('span');
                statusBadge.className = 'badge ' + (lang.is_active ? 'success' : 'warning');
                statusBadge.style.cssText = 'font-size: 0.75rem; padding: 0.25rem 0.5rem;';
                statusBadge.textContent = lang.is_active ? 'Active' : 'Inactive';
                
                const aiBadge = document.createElement('span');
                if (lang.is_ai_supported) {
                    aiBadge.style.cssText = 'color: #2196f3; font-size: 1rem;';
                    aiBadge.innerHTML = '<i class="fas fa-robot" title="AI Supported"></i>';
                }
                
                rightSection.appendChild(statusBadge);
                if (lang.is_ai_supported) {
                    rightSection.appendChild(aiBadge);
                }
                
                leftSection.appendChild(flag);
                leftSection.appendChild(info);
                listItem.appendChild(leftSection);
                listItem.appendChild(rightSection);
                list.appendChild(listItem);
            });
        }
        
        // Filter languages in modal
        function filterLanguages() {
            const searchTerm = document.getElementById('languageSearch').value.toLowerCase();
            const items = document.querySelectorAll('.language-list-item');
            
            items.forEach(item => {
                const languageName = item.dataset.languageName || '';
                if (languageName.includes(searchTerm)) {
                    item.style.display = 'flex';
                } else {
                    item.style.display = 'none';
                }
            });
        }
        
        // Open languages modal
        function openLanguagesModal() {
            const modal = document.getElementById('languagesModal');
            modal.style.display = 'block';
            document.getElementById('languageSearch').focus();
            
            // Load languages if not already loaded
            if (supportedLanguages.length === 0) {
                loadLanguages();
            } else {
                loadLanguagesList();
            }
        }
        
        // Close languages modal
        function closeLanguagesModal() {
            const modal = document.getElementById('languagesModal');
            modal.style.display = 'none';
            document.getElementById('languageSearch').value = '';
            filterLanguages(); // Reset filter
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('languagesModal');
            if (event.target === modal) {
                closeLanguagesModal();
            }
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

        // Load data on page load
        document.addEventListener('DOMContentLoaded', function() {
            checkAIAvailability();
            loadLanguages();
            loadTranslations();
        });
    </script>
</body>
</html>

