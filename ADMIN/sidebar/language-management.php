<?php
/**
 * Language Management Page for Admin
 * Manage supported languages - add, edit, activate/deactivate
 */

$pageTitle = 'Language Management';
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
       MAIN CONTENT - Language Management
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
                            <span>Language Management</span>
                        </li>
                    </ol>
                </nav>
                <h1>Language Management</h1>
                <p>Manage supported languages for the system. Languages added here will be available to users in real-time.</p>
                <div class="info-box" style="background: #e3f2fd; border-left: 4px solid #2196f3; padding: 1rem; border-radius: 4px; margin-top: 1rem;">
                    <i class="fas fa-info-circle" style="color: #2196f3;"></i>
                    <strong>Real-time Updates:</strong> When you add or modify languages, users will see the changes automatically without refreshing their pages.
                </div>
            </div>
            
            <div class="sub-container">
                <div class="page-content">
                    <!-- Add New Language -->
                    <div class="module-card">
                        <div class="module-card-header">
                            <h2><i class="fas fa-plus-circle"></i> Add New Language</h2>
                        </div>
                        <div>
                            <form id="addLanguageForm">
                                <div class="form-group">
                                    <label for="languageCode">Language Code *</label>
                                    <input type="text" id="languageCode" name="language_code" class="form-control" 
                                           placeholder="e.g., en, es, fr" required pattern="[a-z]{2}(-[A-Z]{2})?" 
                                           title="ISO 639-1 code (e.g., en, es, fr)">
                                    <small class="form-text">ISO 639-1 language code (e.g., en, es, fr, zh)</small>
                                </div>
                                <div class="form-group">
                                    <label for="languageName">Language Name *</label>
                                    <input type="text" id="languageName" name="language_name" class="form-control" 
                                           placeholder="e.g., English, Spanish" required>
                                </div>
                                <div class="form-group">
                                    <label for="nativeName">Native Name</label>
                                    <input type="text" id="nativeName" name="native_name" class="form-control" 
                                           placeholder="e.g., English, Español">
                                </div>
                                <div class="form-group">
                                    <label for="flagEmoji">Flag Emoji</label>
                                    <input type="text" id="flagEmoji" name="flag_emoji" class="form-control" 
                                           placeholder="🇺🇸" maxlength="10">
                                    <small class="form-text">Flag emoji for display (optional)</small>
                                </div>
                                <div class="form-group">
                                    <label for="priority">Priority</label>
                                    <input type="number" id="priority" name="priority" class="form-control" 
                                           value="0" min="0" max="100">
                                    <small class="form-text">Higher priority = shown first (0-100)</small>
                                </div>
                                <div class="form-group">
                                    <label>
                                        <input type="checkbox" id="isActive" name="is_active" checked>
                                        Active (available to users)
                                    </label>
                                </div>
                                <div class="form-group">
                                    <label>
                                        <input type="checkbox" id="isAISupported" name="is_ai_supported" checked>
                                        AI Translation Supported
                                    </label>
                                </div>
                                <div class="form-actions">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-plus"></i> Add Language
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Languages List -->
                    <div class="module-card">
                        <div class="module-card-header">
                            <h2><i class="fas fa-language"></i> Supported Languages</h2>
                            <button class="btn btn-sm btn-secondary" onclick="loadLanguages()">
                                <i class="fas fa-sync"></i> Refresh
                            </button>
                        </div>
                        <div>
                            <div id="languagesLoading" style="text-align: center; padding: 2rem;">
                                <i class="fas fa-spinner fa-spin"></i> Loading languages...
                            </div>
                            <table class="data-table" id="languagesTable" style="display: none;">
                                <thead>
                                    <tr>
                                        <th>Code</th>
                                        <th>Language</th>
                                        <th>Native Name</th>
                                        <th>Flag</th>
                                        <th>Priority</th>
                                        <th>Status</th>
                                        <th>AI Support</th>
                                        <th>Updated</th>
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
        function loadLanguages() {
            const loadingDiv = document.getElementById('languagesLoading');
            const table = document.getElementById('languagesTable');
            
            loadingDiv.style.display = 'block';
            table.style.display = 'none';
            
            fetch('../api/language-management.php?action=list&include_inactive=1')
                .then(response => response.json())
                .then(data => {
                    loadingDiv.style.display = 'none';
                    
                    if (data.success && data.languages) {
                        const tbody = document.querySelector('#languagesTable tbody');
                        tbody.innerHTML = '';
                        
                        data.languages.forEach(lang => {
                            const row = document.createElement('tr');
                            const statusBadge = lang.is_active 
                                ? '<span class="badge success">Active</span>'
                                : '<span class="badge warning">Inactive</span>';
                            const aiBadge = lang.is_ai_supported 
                                ? '<span class="badge" style="background: #2196f3;"><i class="fas fa-robot"></i> Yes</span>'
                                : '<span class="badge" style="background: #999;">No</span>';
                            
                            row.innerHTML = `
                                <td><code>${lang.language_code}</code></td>
                                <td><strong>${lang.language_name}</strong></td>
                                <td>${lang.native_name || '-'}</td>
                                <td style="font-size: 20px;">${lang.flag_emoji || '🌐'}</td>
                                <td>${lang.priority}</td>
                                <td>${statusBadge}</td>
                                <td>${aiBadge}</td>
                                <td>${new Date(lang.updated_at).toLocaleString()}</td>
                                <td>
                                    <button class="btn btn-sm btn-primary" onclick="editLanguage(${lang.id})" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-sm ${lang.is_active ? 'btn-warning' : 'btn-success'}" 
                                            onclick="toggleLanguage(${lang.id}, ${lang.is_active ? 0 : 1})" 
                                            title="${lang.is_active ? 'Deactivate' : 'Activate'}">
                                        <i class="fas fa-${lang.is_active ? 'eye-slash' : 'eye'}"></i>
                                    </button>
                                </td>
                            `;
                            tbody.appendChild(row);
                        });
                        
                        table.style.display = 'table';
                    }
                })
                .catch(error => {
                    console.error('Error loading languages:', error);
                    loadingDiv.innerHTML = '<span style="color: #f44336;">Error loading languages</span>';
                });
        }
        
        document.getElementById('addLanguageForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = {
                language_code: document.getElementById('languageCode').value.trim(),
                language_name: document.getElementById('languageName').value.trim(),
                native_name: document.getElementById('nativeName').value.trim() || document.getElementById('languageName').value.trim(),
                flag_emoji: document.getElementById('flagEmoji').value.trim() || '🌐',
                is_active: document.getElementById('isActive').checked ? 1 : 0,
                is_ai_supported: document.getElementById('isAISupported').checked ? 1 : 0,
                priority: parseInt(document.getElementById('priority').value) || 0
            };
            
            try {
                const response = await fetch('../api/language-management.php?action=add', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(formData)
                });
                
                const result = await response.json();
                
                if (result.success) {
                    alert('Language added successfully! Users will see it in real-time.');
                    this.reset();
                    loadLanguages();
                } else {
                    alert('Error: ' + result.message);
                }
            } catch (error) {
                console.error('Error:', error);
                alert('An error occurred while adding the language.');
            }
        });
        
        function toggleLanguage(id, newStatus) {
            fetch('../api/language-management.php?action=update', {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    id: id,
                    is_active: newStatus
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Language status updated!');
                    loadLanguages();
                } else {
                    alert('Error: ' + data.message);
                }
            });
        }
        
        function editLanguage(id) {
            alert('Edit language feature - Coming soon!');
        }
        
        // Load languages on page load
        document.addEventListener('DOMContentLoaded', function() {
            loadLanguages();
        });
    </script>
</body>
</html>

