<?php
/**
 * Multilingual Support for Alerts Page
 * Manage alert translations and multilingual content
 */

// Start session and check authentication
session_start();

// Check if user is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../login.php');
    exit();
}

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
        /* Enhanced Multilingual Support Styles */
        :root {
            --card-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --card-shadow-hover: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --transition-speed: 0.2s;
        }

        .module-card {
            background: var(--card-bg-1);
            border-radius: 10px;
            box-shadow: var(--card-shadow);
            border: 1px solid var(--border-color-1);
            overflow: hidden;
            margin-bottom: 1.5rem;
            transition: box-shadow var(--transition-speed) ease;
        }

        .module-card:hover {
            box-shadow: var(--card-shadow-hover);
        }

        .module-card-header {
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid var(--border-color-1);
            background: var(--bg-color-1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .module-card-header h2 {
            margin: 0;
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--text-color-1);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 2000;
            left: 0; top: 0; width: 100%; height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(4px);
            animation: fadeIn 0.3s ease;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }
        
        .modal-content {
            background-color: var(--card-bg-1);
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            border: 1px solid var(--border-color-1);
            animation: slideUp 0.3s ease;
            width: 100%;
            max-width: 600px;
            max-height: 90vh;
            display: flex;
            flex-direction: column;
        }
        
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        @keyframes slideUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
        
        .modal-header {
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid var(--border-color-1);
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: var(--bg-color-1);
        }

        .modal-header h3 {
            margin: 0;
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--text-color-1);
        }
        
        .modal-close {
            color: var(--text-secondary-1);
            font-size: 1.5rem;
            cursor: pointer;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
        }
        
        .modal-close:hover {
            background-color: rgba(0,0,0,0.05);
            color: var(--text-color-1);
        }
        
        .modal-body {
            padding: 1.5rem;
            overflow-y: auto;
            flex: 1;
        }

        .modal-footer {
            padding: 1.25rem 1.5rem;
            border-top: 1px solid var(--border-color-1);
            text-align: right;
            background: var(--bg-color-1);
        }

        .language-search-box {
            position: relative;
            margin-bottom: 1.25rem;
        }

        .language-search-box input {
            width: 100%;
            padding: 0.75rem 1rem 0.75rem 2.5rem;
            border: 1px solid var(--border-color-1);
            border-radius: 8px;
            background: var(--bg-color-1);
            color: var(--text-color-1);
            font-size: 0.95rem;
            transition: border-color 0.2s ease;
        }

        .language-search-box i {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-secondary-1);
        }

        .language-search-box input:focus {
            outline: none;
            border-color: var(--primary-color-1);
            box-shadow: 0 0 0 3px rgba(76, 138, 137, 0.1);
        }

        .language-list-item {
            transition: background 0.2s ease;
            border-radius: 8px;
            margin-bottom: 0.25rem;
        }

        .language-list-item:hover {
            background-color: rgba(76, 138, 137, 0.05) !important;
        }

        .info-card {
            padding: 1.5rem;
            background: var(--bg-color-1);
            border-radius: 10px;
            border: 1px solid var(--border-color-1);
        }

        .info-card h3 {
            margin-top: 0;
            color: var(--primary-color-1);
            font-size: 1.1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1.25rem;
        }

        .info-card ol {
            padding-left: 1.25rem;
            margin: 0;
        }

        .info-card li {
            margin-bottom: 0.75rem;
            color: var(--text-color-1);
            line-height: 1.6;
        }

        .status-box {
            margin-top: 1.5rem;
            padding: 1rem 1.25rem;
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-weight: 600;
            font-size: 0.95rem;
        }

        .status-box.active {
            background: rgba(46, 204, 113, 0.1);
            border-left: 4px solid #2ecc71;
            color: #2ecc71;
        }

        .status-box.inactive {
            background: rgba(231, 76, 60, 0.1);
            border-left: 4px solid #e74c3c;
            color: #e74c3c;
        }

        .badge {
            padding: 0.35rem 0.65rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            display: inline-block;
        }
        
        .badge.success { background: rgba(46, 204, 113, 0.15); color: #2ecc71; }
        .badge.warning { background: rgba(243, 156, 18, 0.15); color: #f39c12; }
        .badge.ai { background: rgba(155, 89, 182, 0.15); color: #9b59b6; }

        .table-responsive {
            overflow-x: auto;
            width: 100%;
        }

        .data-table th {
            background: var(--bg-color-1);
            color: var(--text-secondary-1);
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .info-box {
            background-color: rgba(76, 138, 137, 0.1);
            border-left: 4px solid var(--primary-color-1);
            color: var(--text-color-1);
            padding: 1.25rem;
            border-radius: 8px;
            margin-top: 1.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .info-box i {
            color: var(--primary-color-1);
            font-size: 1.25rem;
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
                            <a href="dashboard.php" class="breadcrumb-link">Dashboard</a>
                        </li>
                        <li class="breadcrumb-item active" aria-current="page">Multilingual Support</li>
                    </ol>
                </nav>
                <h1><i class="fas fa-language" style="color: var(--primary-color-1); margin-right: 0.5rem;"></i> Multilingual Support for Alerts</h1>
                <p>AI-powered automatic translation ensures alerts are delivered in each user's preferred language.</p>
                <div class="info-box">
                    <i class="fas fa-robot"></i>
                    <div>
                        <strong>Automatic Translation:</strong> When alerts are sent, the AI automatically translates them based on each user's language preference. No manual translation needed!
                    </div>
                </div>
            </div>
            
            <div class="sub-container">
                <div class="page-content">
                    <!-- Supported Languages Info -->
                    <div class="module-card">
                        <div class="module-card-header">
                            <h2><i class="fas fa-globe"></i> Supported Languages</h2>
                            <button class="btn btn-primary" onclick="openLanguagesModal()">
                                <i class="fas fa-list"></i> View All (<span id="languageCount">-</span>)
                            </button>
                        </div>
                        <div class="module-card-content">
                            <p style="margin: 0; color: var(--text-secondary-1); font-size: 0.95rem;">
                                <i class="fas fa-info-circle"></i> AI can translate alerts to any language supported by Gemini AI. Current system supports <span id="languageCountText">-</span> languages.
                            </p>
                        </div>
                    </div>

                    <!-- Languages Modal -->
                    <div id="languagesModal" class="modal">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h3><i class="fas fa-language"></i> Supported Languages</h3>
                                <span class="modal-close" onclick="closeLanguagesModal()">&times;</span>
                            </div>
                            <div class="modal-body">
                                <div class="language-search-box">
                                    <i class="fas fa-search"></i>
                                    <input type="text" id="languageSearch" placeholder="Search languages..." onkeyup="filterLanguages()">
                                </div>
                                <div id="languagesList">
                                    <div style="text-align: center; padding: 2rem; color: var(--text-secondary-1);">
                                        <i class="fas fa-spinner fa-spin"></i> Loading languages...
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button class="btn btn-secondary" onclick="closeLanguagesModal()">Close</button>
                            </div>
                        </div>
                    </div>

                    <!-- Automatic Translation Info -->
                    <div class="module-card">
                        <div class="module-card-header">
                            <h2><i class="fas fa-magic"></i> How It Works</h2>
                        </div>
                        <div class="module-card-content">
                            <div class="info-card">
                                <ol>
                                    <li><strong>User Language Preference:</strong> When users log in, their language preference is automatically saved.</li>
                                    <li><strong>Automatic Translation:</strong> When alerts are sent, the AI translates each alert to match the recipient's preferred language.</li>
                                    <li><strong>No Manual Work:</strong> Admins don't need to manually translate - the AI handles it automatically!</li>
                                    <li><strong>Real-time:</strong> Translations happen in real-time, ensuring accuracy and freshness.</li>
                                </ol>
                                <div id="aiServiceStatus" class="status-box">
                                    <i class="fas fa-spinner fa-spin"></i> Checking AI service...
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
                        <div class="module-card-content table-responsive">
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
                        <div class="module-card-content table-responsive">
                            <table class="data-table" id="translationsTable">
                                <thead>
                                    <tr>
                                        <th>Alert ID</th>
                                        <th>Original</th>
                                        <th>Translated</th>
                                        <th>Title</th>
                                        <th>Method</th>
                                        <th>By</th>
                                        <th>Status</th>
                                        <th>Date</th>
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
                        statusElement.className = 'status-box active';
                        statusElement.innerHTML = '<i class="fas fa-check-circle"></i> AI Translation Service is Active and Ready';
                    } else {
                        statusElement.className = 'status-box inactive';
                        statusElement.innerHTML = '<i class="fas fa-exclamation-triangle"></i> AI Translation Service is Not Available. Please configure Gemini API key.';
                    }
                })
                .catch(error => {
                    console.error('Error checking AI availability:', error);
                    aiServiceAvailable = false;
                    const statusElement = document.getElementById('aiServiceStatus');
                    statusElement.className = 'status-box inactive';
                    statusElement.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Error checking AI service status.';
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
                        document.getElementById('languageCountText').textContent = data.languages.length;
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
                list.innerHTML = '<div style="text-align: center; padding: 2rem; color: var(--text-secondary-1);">No languages found</div>';
                return;
            }
            
            supportedLanguages.forEach(lang => {
                const listItem = document.createElement('div');
                listItem.className = 'language-list-item';
                listItem.dataset.languageName = (lang.language_name + ' ' + (lang.native_name || '') + ' ' + lang.language_code).toLowerCase();
                listItem.style.cssText = 'display: flex; align-items: center; justify-content: space-between; padding: 0.75rem 1rem; border-bottom: 1px solid var(--border-color-1); transition: background 0.2s;';
                
                const leftSection = document.createElement('div');
                leftSection.style.cssText = 'display: flex; align-items: center; gap: 1rem; flex: 1;';
                
                const flag = document.createElement('span');
                flag.style.cssText = 'font-size: 1.5rem;';
                flag.textContent = lang.flag_emoji || '🌐';
                
                const info = document.createElement('div');
                info.style.cssText = 'flex: 1;';
                
                const name = document.createElement('div');
                name.style.cssText = 'font-weight: 700; color: var(--text-color-1); margin-bottom: 0.15rem; font-size: 0.95rem;';
                name.textContent = lang.language_name;
                
                const native = document.createElement('div');
                native.style.cssText = 'font-size: 0.85rem; color: var(--text-secondary-1); font-weight: 500;';
                native.textContent = lang.native_name || lang.language_name;
                
                info.appendChild(name);
                info.appendChild(native);
                
                const rightSection = document.createElement('div');
                rightSection.style.cssText = 'display: flex; align-items: center; gap: 0.75rem;';
                
                const statusBadge = document.createElement('span');
                statusBadge.className = 'badge ' + (lang.is_active ? 'success' : 'warning');
                statusBadge.textContent = lang.is_active ? 'Active' : 'Inactive';
                
                const aiBadge = document.createElement('span');
                if (lang.is_ai_supported) {
                    aiBadge.style.cssText = 'color: #9b59b6; font-size: 1rem;';
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
            modal.style.display = 'flex';
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
                                ? '<span class="badge ai"><i class="fas fa-robot"></i> AI</span>'
                                : '<span class="badge" style="background: rgba(108, 117, 125, 0.15); color: #6c757d;"><i class="fas fa-user"></i> Manual</span>';
                            const languageName = trans.language_name || trans.target_language;
                            const flagEmoji = trans.flag_emoji || '🌐';
                            
                            row.innerHTML = `
                                <td>#${trans.alert_id}</td>
                                <td><code style="background: var(--bg-color-1); padding: 0.2rem 0.4rem; border-radius: 4px;">${trans.original_language || 'en'}</code></td>
                                <td><strong>${flagEmoji} ${languageName}</strong></td>
                                <td><div style="max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">${trans.translated_title}</div></td>
                                <td>${methodBadge}</td>
                                <td>${trans.translated_by_name || 'System'}</td>
                                <td><span class="badge ${trans.status === 'active' ? 'success' : 'warning'}">${trans.status}</span></td>
                                <td><small style="color: var(--text-secondary-1);">${new Date(trans.translated_at).toLocaleDateString()}</small></td>
                                <td>
                                    <div class="btn-group">
                                        <button class="btn btn-sm btn-primary" onclick="editTranslation(${trans.id})" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-sm btn-danger" onclick="deleteTranslation(${trans.id})" title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
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
                            tbody.innerHTML = '<tr><td colspan="6" style="text-align: center; padding: 2rem; color: var(--text-secondary-1);">No activity logs found</td></tr>';
                        } else {
                            data.logs.forEach(log => {
                                const row = document.createElement('tr');
                                const statusBadge = log.success 
                                    ? '<span class="badge success">Success</span>'
                                    : '<span class="badge error">Failed</span>';
                                const methodBadge = log.translation_method === 'ai'
                                    ? '<span class="badge ai"><i class="fas fa-robot"></i> AI</span>'
                                    : log.translation_method === 'manual'
                                    ? '<span class="badge" style="background: rgba(108, 117, 125, 0.15); color: #6c757d;"><i class="fas fa-user"></i> Manual</span>'
                                    : '';
                                
                                row.innerHTML = `
                                    <td><small>${new Date(log.created_at).toLocaleString()}</small></td>
                                    <td><code style="background: var(--bg-color-1); padding: 0.2rem 0.4rem; border-radius: 4px;">${log.action_type}</code></td>
                                    <td><div style="max-width: 150px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">${log.alert_title || 'Alert #' + (log.alert_id || 'N/A')}</div></td>
                                    <td><strong>${log.source_language || 'en'} → ${log.target_language || 'N/A'}</strong></td>
                                    <td>${methodBadge}</td>
                                    <td>${statusBadge}</td>
                                `;
                                tbody.appendChild(row);
                            });
                        }
                        
                        table.style.display = 'table';
                        loadingDiv.style.display = 'none';
                    } else {
                        loadingDiv.innerHTML = '<span style="color: #e74c3c;">Error loading activity logs: ' + (data.message || 'Unknown error') + '</span>';
                    }
                })
                .catch(error => {
                    console.error('Error loading activity logs:', error);
                    loadingDiv.innerHTML = '<span style="color: #e74c3c;">Error loading activity logs. Please try again.</span>';
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
