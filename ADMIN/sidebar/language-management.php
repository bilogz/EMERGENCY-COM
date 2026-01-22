<?php
/**
 * Language Management Page for Admin
 * Manage supported languages - add, edit, activate/deactivate
 */

// Start session and check authentication
session_start();

// Check if user is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../login.php');
    exit();
}

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
        /* Enhanced Language Management Styles */
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
        .modal-backdrop {
            display: none;
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(4px);
            z-index: 9998;
            animation: fadeIn 0.3s ease;
            align-items: center;
            justify-content: center;
        }
        
        .modal-backdrop.show { display: flex; }
        
        .modal-dialog {
            background: var(--card-bg-1);
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            max-width: 500px;
            width: 90%;
            max-height: 90vh;
            overflow: hidden;
            animation: slideUp 0.3s ease;
            position: relative;
            z-index: 9999;
            border: 1px solid var(--border-color-1);
        }
        
        .modal-header {
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid var(--border-color-1);
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: var(--bg-color-1);
        }
        
        .modal-header h2 {
            margin: 0;
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--text-color-1);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .modal-close {
            background: transparent;
            border: none;
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
            background: rgba(0,0,0,0.05);
            color: var(--text-color-1);
        }
        
        .modal-body {
            padding: 1.5rem;
            overflow-y: auto;
        }
        
        .modal-footer {
            padding: 1.25rem 1.5rem;
            border-top: 1px solid var(--border-color-1);
            display: flex;
            justify-content: flex-end;
            gap: 0.75rem;
            background: var(--bg-color-1);
        }
        
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        @keyframes slideUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
        
        .form-group {
            margin-bottom: 1.25rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--text-color-1);
            font-size: 0.9rem;
        }

        .form-control {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid var(--border-color-1);
            border-radius: 8px;
            font-size: 0.95rem;
            background: var(--bg-color-1);
            color: var(--text-color-1);
            transition: border-color var(--transition-speed) ease;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary-color-1);
            box-shadow: 0 0 0 3px rgba(76, 138, 137, 0.1);
        }

        /* Floating Action Button */
        .add-language-btn {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            width: 56px;
            height: 56px;
            border-radius: 50%;
            background: var(--primary-color-1);
            color: white;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(76, 138, 137, 0.4);
            transition: all 0.3s ease;
            z-index: 100;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .add-language-btn:hover {
            transform: scale(1.1);
            box-shadow: 0 6px 16px rgba(76, 138, 137, 0.5);
            background: #3d7a79;
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
                            <a href="dashboard.php" class="breadcrumb-link">Dashboard</a>
                        </li>
                        <li class="breadcrumb-item active" aria-current="page">Language Management</li>
                    </ol>
                </nav>
                <h1><i class="fas fa-language" style="color: var(--primary-color-1); margin-right: 0.5rem;"></i> Language Management</h1>
                <p>Manage supported languages for the system. Languages added here will be available to users in real-time.</p>
                <div class="info-box" style="background: rgba(76, 138, 137, 0.1); border-left: 4px solid var(--primary-color-1); padding: 1.25rem; border-radius: 8px; margin-top: 1.5rem; display: flex; align-items: center; gap: 1rem;">
                    <i class="fas fa-info-circle" style="color: var(--primary-color-1); font-size: 1.2rem;"></i>
                    <div style="font-size: 0.95rem; color: var(--text-color-1);">
                        <strong>Real-time Updates:</strong> When you add or modify languages, users will see the changes automatically without refreshing their pages.
                    </div>
                </div>
            </div>
            
            <div class="sub-container">
                <div class="page-content">
                    <!-- Languages List -->
                    <div class="module-card">
                        <div class="module-card-header">
                            <h2><i class="fas fa-list"></i> Supported Languages</h2>
                            <button class="btn btn-sm btn-secondary" onclick="loadLanguages()">
                                <i class="fas fa-sync"></i> Refresh
                            </button>
                        </div>
                        <div class="module-card-content table-responsive">
                            <div id="languagesLoading" style="text-align: center; padding: 3rem; color: var(--text-secondary-1);">
                                <i class="fas fa-spinner fa-spin" style="font-size: 2rem; margin-bottom: 1rem;"></i>
                                <p>Loading languages...</p>
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
                        <small class="form-text" style="color: var(--text-secondary-1); font-size: 0.8rem; margin-top: 0.4rem; display: block;">ISO 639-1 language code (e.g., en, es, fr, zh)</small>
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
                        <small class="form-text" style="color: var(--text-secondary-1); font-size: 0.8rem; margin-top: 0.4rem; display: block;">Flag emoji for display (optional)</small>
                    </div>
                    <div class="form-group">
                        <label for="priority">Priority</label>
                        <input type="number" id="priority" name="priority" class="form-control" 
                               value="0" min="0" max="100">
                        <small class="form-text" style="color: var(--text-secondary-1); font-size: 0.8rem; margin-top: 0.4rem; display: block;">Higher priority = shown first (0-100)</small>
                    </div>
                    <div class="form-group" style="display: flex; gap: 1.5rem; margin-top: 1rem;">
                        <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                            <input type="checkbox" id="isActive" name="is_active" checked style="width: 1.1rem; height: 1.1rem;">
                            <span>Active</span>
                        </label>
                        <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                            <input type="checkbox" id="isAISupported" name="is_ai_supported" checked style="width: 1.1rem; height: 1.1rem;">
                            <span>AI Translation</span>
                        </label>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeAddLanguageModal()">
                    Cancel
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
                                ? '<span class="badge" style="background: rgba(33, 150, 243, 0.15); color: #2196f3;"><i class="fas fa-robot"></i> Yes</span>'
                                : '<span class="badge" style="background: rgba(158, 158, 158, 0.15); color: #9e9e9e;">No</span>';
                            
                            row.innerHTML = `
                                <td><code>${lang.language_code}</code></td>
                                <td><strong>${lang.language_name}</strong></td>
                                <td>${lang.native_name || '-'}</td>
                                <td style="font-size: 20px;">${lang.flag_emoji || '🌐'}</td>
                                <td>${lang.priority}</td>
                                <td>${statusBadge}</td>
                                <td>${aiBadge}</td>
                                <td><small style="color: var(--text-secondary-1);">${new Date(lang.updated_at).toLocaleString()}</small></td>
                                <td>
                                    <div class="btn-group">
                                        <button class="btn btn-sm btn-primary" onclick="editLanguage(${lang.id})" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-sm ${lang.is_active ? 'btn-warning' : 'btn-success'}" 
                                                onclick="toggleLanguage(${lang.id}, ${lang.is_active ? 0 : 1})" 
                                                title="${lang.is_active ? 'Deactivate' : 'Activate'}">
                                            <i class="fas fa-${lang.is_active ? 'eye-slash' : 'eye'}"></i>
                                        </button>
                                    </div>
                                </td>
                            `;
                            tbody.appendChild(row);
                        });
                        
                        table.style.display = 'table';
                    }
                })
                .catch(error => {
                    console.error('Error loading languages:', error);
                    loadingDiv.innerHTML = '<span style="color: #e74c3c;">Error loading languages</span>';
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

