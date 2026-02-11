<?php
/**
 * Language Management Control Panel
 * STRICTLY OPERATIONAL - No explainer text.
 */

session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../../login.php');
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
    <link rel="icon" type="image/x-icon" href="../images/favicon.ico">
    <link rel="stylesheet" href="../css/global.css?v=<?php echo filemtime(__DIR__ . '/../css/global.css'); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../css/sidebar.css?v=<?php echo filemtime(__DIR__ . '/../css/sidebar.css'); ?>">
    <link rel="stylesheet" href="../css/admin-header.css">
    <link rel="stylesheet" href="../css/buttons.css">
    <link rel="stylesheet" href="../css/forms.css">
    <link rel="stylesheet" href="../css/datatables.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="../css/module-language-management.css?v=<?php echo filemtime(__DIR__ . '/../css/module-language-management.css'); ?>">
</head>
<body>
    <?php include '../includes/sidebar.php'; ?>
    <?php include '../includes/admin-header.php'; ?>
    
    <div class="main-content">
        <!-- Breadcrumb -->
        <div style="padding: 1rem 2rem 0;">
            <nav class="breadcrumb" aria-label="Breadcrumb">
                <ol class="breadcrumb-list">
                    <li class="breadcrumb-item"><a href="../dashboard.php" class="breadcrumb-link">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="overview.php" class="breadcrumb-link">Multilingual Support</a></li>
                    <li class="breadcrumb-item active">Language Management</li>
                </ol>
            </nav>
        </div>

        <div class="management-container">
            <div class="control-panel">
                <div class="panel-header">
                    <h2 class="panel-title">System Languages</h2>
                    <div class="search-wrapper">
                        <i class="fas fa-search search-icon"></i>
                        <input type="text" id="searchInput" class="table-search" placeholder="Search languages...">
                    </div>
                </div>
                
                <div class="table-responsive">
                    <div id="loading" style="padding: 4rem; text-align: center; color: #999;">
                        <i class="fas fa-spinner fa-spin fa-2x"></i>
                        <p style="margin-top: 1rem; font-weight: 500;">Loading languages...</p>
                    </div>
                    
                    <table class="ops-table" id="langTable" style="display: none;">
                        <thead>
                            <tr>
                                <th>Status</th>
                                <th>Language</th>
                                <th>Code</th>
                                <th>Native Name</th>
                                <th>Priority</th>
                                <th style="text-align: right;">Action</th>
                            </tr>
                        </thead>
                        <tbody id="langBody">
                            <!-- JS populated -->
                        </tbody>
                    </table>
                    
                    <div id="noResults" style="display: none; padding: 4rem; text-align: center; color: #999;">
                        <i class="fas fa-search fa-2x" style="margin-bottom: 1rem; opacity: 0.3;"></i>
                        <p>No languages found matching your search.</p>
                    </div>
                </div>

                <div class="panel-footer" id="paginationFooter" style="display: none;">
                    <div class="pagination-info" id="paginationInfo">
                        Showing 0 of 0 languages
                    </div>
                    <div class="pagination-controls" id="paginationControls">
                        <!-- Buttons injected via JS -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Floating Add Button -->
    <button class="fab" onclick="openModal()" title="Add New Language">
        <i class="fas fa-plus"></i>
    </button>

    <!-- Simple Add Modal -->
    <div class="modal-backdrop" id="addModal">
        <div class="modal-card">
            <h3 style="margin-top:0; margin-bottom: 1.5rem; color: #1a1a1a;">Add Language</h3>
            <form id="addForm">
                <div style="margin-bottom: 1rem;">
                    <label style="display:block; margin-bottom: 0.5rem; font-weight:600; color: #333; font-size: 0.9rem;">Code (ISO 639-1)</label>
                    <input type="text" id="code" class="form-control" placeholder="e.g. fr" required style="width:100%; padding: 0.7rem; border-radius: 8px; border:1px solid #e0e0e0;">
                </div>
                <div style="margin-bottom: 1rem;">
                    <label style="display:block; margin-bottom: 0.5rem; font-weight:600; color: #333; font-size: 0.9rem;">Name</label>
                    <input type="text" id="name" class="form-control" placeholder="e.g. French" required style="width:100%; padding: 0.7rem; border-radius: 8px; border:1px solid #e0e0e0;">
                </div>
                <div style="margin-bottom: 1rem;">
                    <label style="display:block; margin-bottom: 0.5rem; font-weight:600; color: #333; font-size: 0.9rem;">Native Name</label>
                    <input type="text" id="native" class="form-control" placeholder="e.g. Français" style="width:100%; padding: 0.7rem; border-radius: 8px; border:1px solid #e0e0e0;">
                </div>
                <div style="margin-bottom: 1rem;">
                    <label style="display:block; margin-bottom: 0.5rem; font-weight:600; color: #333; font-size: 0.9rem;">Flag Emoji</label>
                    <input type="text" id="flag" class="form-control" placeholder="🇫🇷" style="width:100%; padding: 0.7rem; border-radius: 8px; border:1px solid #e0e0e0;">
                </div>
                <div style="margin-bottom: 1.5rem;">
                    <label style="display:block; margin-bottom: 0.5rem; font-weight:600; color: #333; font-size: 0.9rem;">Priority</label>
                    <input type="number" id="priority" class="form-control" value="0" style="width:100%; padding: 0.7rem; border-radius: 8px; border:1px solid #e0e0e0;">
                </div>
                <div style="text-align: right; display: flex; gap: 1rem; justify-content: flex-end;">
                    <button type="button" class="btn btn-secondary" onclick="closeModal()" style="padding: 0.7rem 1.5rem; border-radius: 8px;">Cancel</button>
                    <button type="submit" class="btn btn-primary" style="padding: 0.7rem 1.5rem; border-radius: 8px;">Save Language</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const APP_BASE = window.location.pathname.split('/ADMIN/')[0] || '';
        const API_BASE = APP_BASE + '/ADMIN/api';

        function openModal() { document.getElementById('addModal').classList.add('show'); }
        function closeModal() { document.getElementById('addModal').classList.remove('show'); document.getElementById('addForm').reset(); }

        let languagesData = [];
        let filteredData = [];
        let currentPage = 1;
        const rowsPerPage = 10;

        function loadData() {
            fetch(`${API_BASE}/language-management.php?action=list&include_inactive=1`)
                .then(r => r.json())
                .then(data => {
                    document.getElementById('loading').style.display = 'none';
                    document.getElementById('langTable').style.display = 'table';
                    document.getElementById('paginationFooter').style.display = 'flex';
                    
                    if(data.languages) {
                        languagesData = data.languages;
                        filteredData = languagesData;
                        renderTable();
                    }
                })
                .catch((error) => {
                    console.error('Failed to load languages:', error);
                    const loadingEl = document.getElementById('loading');
                    if (loadingEl) {
                        loadingEl.innerHTML = '<p style="color:#ff9b9b; font-weight:600;">Failed to load languages. Please refresh and try again.</p>';
                    }
                });
        }

        function renderTable() {
            const tbody = document.getElementById('langBody');
            tbody.innerHTML = '';
            const noResults = document.getElementById('noResults');
            const table = document.getElementById('langTable');
            const footer = document.getElementById('paginationFooter');

            if (filteredData.length === 0) {
                table.style.display = 'none';
                footer.style.display = 'none';
                noResults.style.display = 'block';
                return;
            }

            table.style.display = 'table';
            footer.style.display = 'flex';
            noResults.style.display = 'none';

            // Pagination Logic
            const startIndex = (currentPage - 1) * rowsPerPage;
            const endIndex = startIndex + rowsPerPage;
            const pageData = filteredData.slice(startIndex, endIndex);

            pageData.forEach(l => {
                const activeClass = l.is_active ? 'badge-success' : 'badge-warning';
                const activeText = l.is_active ? 'Active' : 'Inactive';
                const checked = l.is_active ? 'checked' : '';
                
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>
                        <span class="badge ${activeClass}">${activeText}</span>
                    </td>
                    <td>
                        <div style="font-weight: 600; display: flex; align-items: center; gap: 0.8rem; color: #1a1a1a;">
                            <span style="font-size: 1.4rem;">${l.flag_emoji || ''}</span>
                            ${l.language_name}
                        </div>
                    </td>
                    <td><code style="background: #f0f0f0; padding: 0.3rem 0.6rem; border-radius: 6px; color: #555; font-weight: 600; font-size: 0.85rem;">${l.language_code}</code></td>
                    <td style="color: #666;">${l.native_name || '-'}</td>
                    <td>${l.priority}</td>
                    <td style="text-align: right;">
                        <label class="switch" title="Toggle Status">
                            <input type="checkbox" ${checked} onchange="toggleStatus(${l.id}, this.checked)">
                            <span class="slider"></span>
                        </label>
                    </td>
                `;
                tbody.appendChild(tr);
            });

            renderPagination();
        }

        function renderPagination() {
            const totalRows = filteredData.length;
            const totalPages = Math.ceil(totalRows / rowsPerPage);
            const startIndex = (currentPage - 1) * rowsPerPage + 1;
            const endIndex = Math.min(startIndex + rowsPerPage - 1, totalRows);

            // Update Info Text
            document.getElementById('paginationInfo').textContent = `Showing ${startIndex} to ${endIndex} of ${totalRows} languages`;

            // Update Controls
            const controls = document.getElementById('paginationControls');
            controls.innerHTML = '';

            // Previous Button
            const prevBtn = document.createElement('button');
            prevBtn.className = 'page-btn';
            prevBtn.innerHTML = '<i class="fas fa-chevron-left"></i>';
            prevBtn.disabled = currentPage === 1;
            prevBtn.onclick = () => changePage(currentPage - 1);
            controls.appendChild(prevBtn);

            // Page Numbers
            for (let i = 1; i <= totalPages; i++) {
                // Show limited range of pages for large datasets
                if (i === 1 || i === totalPages || (i >= currentPage - 1 && i <= currentPage + 1)) {
                    const btn = document.createElement('button');
                    btn.className = `page-btn ${i === currentPage ? 'active' : ''}`;
                    btn.textContent = i;
                    btn.onclick = () => changePage(i);
                    controls.appendChild(btn);
                } else if (i === currentPage - 2 || i === currentPage + 2) {
                    const dots = document.createElement('span');
                    dots.textContent = '...';
                    dots.style.margin = '0 0.2rem';
                    dots.style.color = '#999';
                    controls.appendChild(dots);
                }
            }

            // Next Button
            const nextBtn = document.createElement('button');
            nextBtn.className = 'page-btn';
            nextBtn.innerHTML = '<i class="fas fa-chevron-right"></i>';
            nextBtn.disabled = currentPage === totalPages;
            nextBtn.onclick = () => changePage(currentPage + 1);
            controls.appendChild(nextBtn);
        }

        function changePage(page) {
            currentPage = page;
            renderTable();
        }

        // Search functionality
        document.getElementById('searchInput').addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            filteredData = languagesData.filter(lang => 
                lang.language_name.toLowerCase().includes(searchTerm) ||
                lang.native_name?.toLowerCase().includes(searchTerm) ||
                lang.language_code.toLowerCase().includes(searchTerm)
            );
            currentPage = 1; // Reset to first page
            renderTable();
        });

        function toggleStatus(id, isChecked) {
            fetch(`${API_BASE}/language-management.php?action=update`, {
                method: 'PUT',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({id: id, is_active: isChecked ? 1 : 0})
            }).then(r => r.json()).then(res => {
                if(res.success) {
                    // Update local data
                    const index = languagesData.findIndex(l => l.id == id);
                    if(index !== -1) {
                        languagesData[index].is_active = isChecked ? 1 : 0;
                        renderTable(); // Re-render to update badges
                    }
                } else {
                    alert('Error: ' + res.message);
                    loadData(); // Revert on error
                }
            });
        }

        document.getElementById('addForm').addEventListener('submit', function(e){
            e.preventDefault();
            const data = {
                language_code: document.getElementById('code').value,
                language_name: document.getElementById('name').value,
                native_name: document.getElementById('native').value,
                flag_emoji: document.getElementById('flag').value,
                priority: document.getElementById('priority').value,
                is_active: 1,
                is_ai_supported: 1
            };
            fetch(`${API_BASE}/language-management.php?action=add`, {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify(data)
            }).then(r => r.json()).then(res => {
                if(res.success) { closeModal(); loadData(); }
                else alert(res.message);
            });
        });

        document.addEventListener('DOMContentLoaded', loadData);
    </script>
</body>
</html>
