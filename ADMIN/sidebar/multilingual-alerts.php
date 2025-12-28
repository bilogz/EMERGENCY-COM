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
                            <div id="languagesGrid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                                <div style="text-align: center; padding: 2rem; color: #999;">
                                    <i class="fas fa-spinner fa-spin"></i> Loading languages...
                                </div>
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
                        loadLanguagesGrid();
                    }
                })
                .catch(error => {
                    console.error('Error loading languages:', error);
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
            checkAIAvailability();
            loadLanguages();
            loadTranslations();
        });
    </script>
</body>
</html>

