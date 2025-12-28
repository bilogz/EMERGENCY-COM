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
    <style>
        /* Modal Styles */
        .modal-backdrop {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(4px);
            z-index: 9998;
            animation: fadeIn 0.3s ease;
        }
        
        .modal-backdrop.show {
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .modal-dialog {
            background: var(--card-bg, #ffffff);
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            max-width: 600px;
            width: 90%;
            max-height: 90vh;
            overflow: hidden;
            animation: slideUp 0.3s ease;
            position: relative;
            z-index: 9999;
        }
        
        .modal-header {
            padding: 1.5rem;
            border-bottom: 1px solid var(--border-color, #e5e7eb);
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: linear-gradient(135deg, #4c8a89 0%, #5ba3a2 100%);
            color: white;
        }
        
        .modal-header h2 {
            margin: 0;
            font-size: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .modal-close {
            background: rgba(255, 255, 255, 0.2);
            border: none;
            color: white;
            font-size: 1.5rem;
            cursor: pointer;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
        }
        
        .modal-close:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: rotate(90deg);
        }
        
        .modal-body {
            padding: 1.5rem;
            max-height: calc(90vh - 140px);
            overflow-y: auto;
        }
        
        .modal-footer {
            padding: 1rem 1.5rem;
            border-top: 1px solid var(--border-color, #e5e7eb);
            display: flex;
            justify-content: flex-end;
            gap: 0.5rem;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        /* Dark mode support */
        [data-theme="dark"] .modal-dialog {
            background: #1a1d24;
            border: 1px solid #2d3139;
        }
        
        [data-theme="dark"] .modal-header,
        [data-theme="dark"] .modal-footer {
            border-color: #2d3139;
        }
        
        /* Compact form in modal */
        .modal-body .form-group {
            margin-bottom: 1rem;
        }
        
        .modal-body .form-group:last-child {
            margin-bottom: 0;
        }
        
        /* Add language button styling */
        .add-language-btn {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: linear-gradient(135deg, #4c8a89 0%, #5ba3a2 100%);
            color: white;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            box-shadow: 0 4px 20px rgba(76, 138, 137, 0.4);
            transition: all 0.3s ease;
            z-index: 100;
        }
        
        .add-language-btn:hover {
            transform: scale(1.1) rotate(90deg);
            box-shadow: 0 6px 30px rgba(76, 138, 137, 0.6);
        }
        
        .add-language-btn:active {
            transform: scale(0.95);
        }
    </style>
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
    
    <!-- Floating Action Button to Add Language -->
    <button class="add-language-btn" onclick="openAddLanguageModal()" title="Add New Language">
        <i class="fas fa-plus"></i>
    </button>
    
    <!-- Add Language Modal -->
    <div class="modal-backdrop" id="addLanguageModal">
        <div class="modal-dialog">
            <div class="modal-header">
                <h2><i class="fas fa-plus-circle"></i> Add New Language</h2>
                <button class="modal-close" onclick="closeAddLanguageModal()" aria-label="Close">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
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
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeAddLanguageModal()">
                    <i class="fas fa-times"></i> Cancel
                </button>
                <button type="submit" form="addLanguageForm" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Add Language
                </button>
            </div>
        </div>
    </div>

    <script>
        // Modal functions
        function openAddLanguageModal() {
            document.getElementById('addLanguageModal').classList.add('show');
            document.body.style.overflow = 'hidden';
        }
        
        function closeAddLanguageModal() {
            document.getElementById('addLanguageModal').classList.remove('show');
            document.body.style.overflow = '';
            document.getElementById('addLanguageForm').reset();
        }
        
        // Close modal on backdrop click
        document.getElementById('addLanguageModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeAddLanguageModal();
            }
        });
        
        // Close modal on Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                const modal = document.getElementById('addLanguageModal');
                if (modal.classList.contains('show')) {
                    closeAddLanguageModal();
                }
            }
        });
    </script>

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
            
            // Disable submit button to prevent double submission
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Adding...';
            
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
                    closeAddLanguageModal();
                    loadLanguages();
                } else {
                    alert('Error: ' + result.message);
                }
            } catch (error) {
                console.error('Error:', error);
                alert('An error occurred while adding the language.');
            } finally {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
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

