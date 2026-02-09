<?php
/**
 * User Management Portal - Modern Responsive Version
 * Super Admin can create and manage Admin and Staff accounts
 */

// Start session and check authentication
session_start();

// Check if user is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../login.php');
    exit();
}

// Check if user is super_admin
$userRole = $_SESSION['admin_role'] ?? 'admin';
$isSuperAdmin = ($userRole === 'super_admin');

$pageTitle = 'User Management';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?> - Emergency Communication System</title>
    <link rel="icon" type="image/x-icon" href="images/favicon.ico">
    <link rel="stylesheet" href="css/global.css?v=<?php echo filemtime(__DIR__ . '/css/global.css'); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/sidebar.css?v=<?php echo filemtime(__DIR__ . '/css/sidebar.css'); ?>">
    <link rel="stylesheet" href="css/admin-header.css">
    <link rel="stylesheet" href="css/buttons.css">
    <link rel="stylesheet" href="css/hero.css">
    <link rel="stylesheet" href="css/sidebar-footer.css">
    <link rel="stylesheet" href="css/forms.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
        <link rel="stylesheet" href="css/module-users.css?v=<?php echo filemtime(__DIR__ . '/css/module-users.css'); ?>">
</head>
<body>
    <!-- Include Sidebar Component -->
    <?php include 'includes/sidebar.php'; ?>

    <!-- Include Admin Header Component -->
    <?php include 'includes/admin-header.php'; ?>
    
    <div class="main-content">
        <div class="main-container">
            <div class="title">
                <nav class="breadcrumb" aria-label="Breadcrumb">
                    <ol class="breadcrumb-list">
                        <li class="breadcrumb-item">
                            <a href="dashboard.php" class="breadcrumb-link">
                                <span>Dashboard</span>
                            </a>
                        </li>
                        <li class="breadcrumb-item active" aria-current="page">
                            <span>User Management</span>
                        </li>
                    </ol>
                </nav>
                <h1><i class="fas fa-users-cog"></i> User Management</h1>
                <p>Create and manage administrator and staff accounts for the Emergency Communication System.</p>
            </div>
            
            <div class="sub-container">
                <div class="page-content">
                    <?php if (!$isSuperAdmin): ?>
                    <!-- Access Denied for non-super admins -->
                    <div class="access-denied">
                        <i class="fas fa-lock"></i>
                        <h2>Access Restricted</h2>
                        <p>Only Super Administrators can access the User Management portal.</p>
                        <p>Your current role: <strong><?php echo htmlspecialchars(ucfirst($userRole)); ?></strong></p>
                        <br>
                        <a href="dashboard.php" class="btn btn-primary">
                            <i class="fas fa-arrow-left"></i> Back to Dashboard
                        </a>
                    </div>
                    <?php else: ?>
                    <!-- User Management Content -->
                    <div class="user-management-container">
                        <!-- Stats Cards -->
                        <div class="user-stats">
                            <div class="stat-card admin">
                                <div class="stat-value" id="totalAdmins">0</div>
                                <div class="stat-label">
                                    <i class="fas fa-user-shield"></i> Administrators
                                </div>
                            </div>
                            <div class="stat-card staff">
                                <div class="stat-value" id="totalStaff">0</div>
                                <div class="stat-label">
                                    <i class="fas fa-user-tie"></i> Staff Members
                                </div>
                            </div>
                            <div class="stat-card pending">
                                <div class="stat-value" id="totalPending">0</div>
                                <div class="stat-label">
                                    <i class="fas fa-clock"></i> Pending Approval
                                </div>
                            </div>
                            <div class="stat-card inactive">
                                <div class="stat-value" id="totalInactive">0</div>
                                <div class="stat-label">
                                    <i class="fas fa-user-slash"></i> Inactive
                                </div>
                            </div>
                        </div>
                        
                        <!-- Action Bar -->
                        <div class="action-bar">
                            <div class="search-filter-container">
                                <div class="search-box">
                                    <i class="fas fa-search"></i>
                                    <input type="text" id="searchInput" placeholder="Search users by name or email...">
                                </div>
                                
                                <div class="filter-buttons">
                                    <button class="filter-btn active" data-filter="all">All</button>
                                    <button class="filter-btn" data-filter="super_admin">Super Admin</button>
                                    <button class="filter-btn" data-filter="admin">Admin</button>
                                    <button class="filter-btn" data-filter="staff">Staff</button>
                                </div>
                            </div>
                            
                            <button type="button" class="btn btn-primary btn-add" id="addUserBtn" onclick="(function(){const t=document.getElementById('modalTitle');if(t)t.innerHTML='<i class=\'fas fa-user-plus\'></i> Create New User';const f=document.getElementById('userForm');if(f){f.reset();const id=document.getElementById('userId');if(id)id.value='';const p=document.getElementById('userPassword');if(p){p.required=true;p.value='';}}const m=document.getElementById('userModal');if(m){m.classList.add('show');document.body.style.overflow='hidden';}}());return false;">
                                <i class="fas fa-plus"></i> Add User
                            </button>
                        </div>
                        
                        <!-- Users Table (Desktop) -->
                        <div class="users-table-container">
                            <table class="users-table">
                                <thead>
                                    <tr>
                                        <th>User</th>
                                        <th>Role</th>
                                        <th>Status</th>
                                        <th>Created</th>
                                        <th>Last Login</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="usersTableBody">
                                    <tr>
                                        <td colspan="6">
                                            <div class="empty-state">
                                                <i class="fas fa-spinner fa-spin"></i>
                                                <p>Loading users...</p>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Users Cards (Mobile) -->
                        <div class="users-cards-container" id="usersCardsContainer">
                            <!-- Cards will be inserted here by JavaScript -->
                        </div>
                        
                        <!-- Pagination Container -->
                        <div class="pagination-container" id="paginationContainer">
                            <!-- Pagination controls will be inserted here by JavaScript -->
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Create/Edit User Modal -->
    <div class="modal-overlay" id="userModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle"><i class="fas fa-user-plus"></i> Create New User</h2>
                <button type="button" class="modal-close" id="modalCloseBtn" onclick="(function(){const m=document.getElementById('userModal');if(m){m.classList.remove('show');document.body.style.overflow='';}}());return false;" aria-label="Close">&times;</button>
            </div>
            <div class="modal-body">
                <form id="userForm">
                    <input type="hidden" id="userId" name="id">
                    
                    <div class="form-group">
                        <label for="userName"><i class="fas fa-user"></i> Full Name *</label>
                        <input type="text" id="userName" name="name" required placeholder="Enter full name">
                    </div>
                    
                    <div class="form-group">
                        <label for="userEmail"><i class="fas fa-envelope"></i> Email Address *</label>
                        <input type="email" id="userEmail" name="email" required placeholder="Enter email address">
                    </div>
                    
                    <div class="form-group" id="passwordGroup">
                        <label for="userPassword"><i class="fas fa-lock"></i> Password *</label>
                        <input type="password" id="userPassword" name="password" placeholder="Enter password (min 8 characters)">
                        <small>Leave blank to keep existing password (when editing)</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="userRole"><i class="fas fa-user-tag"></i> Role *</label>
                        <select id="userRole" name="role" required>
                            <option value="">Select a role</option>
                            <option value="admin">Administrator</option>
                            <option value="staff">Staff</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="userStatus"><i class="fas fa-toggle-on"></i> Status *</label>
                        <select id="userStatus" name="status" required>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                            <option value="pending_approval">Pending Approval</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" id="cancelBtn" onclick="(function(){const m=document.getElementById('userModal');if(m){m.classList.remove('show');document.body.style.overflow='';const f=document.getElementById('userForm');if(f)f.reset();}}());return false;">Cancel</button>
                <button type="button" class="btn btn-primary" id="saveUserBtn" onclick="window.saveUser && window.saveUser(); return false;">
                    <i class="fas fa-save"></i> Save User
                </button>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        <?php if ($isSuperAdmin): ?>
        let users = [];
        let currentFilter = 'all';
        
        // Load users on page load
        document.addEventListener('DOMContentLoaded', function() {
            loadUsers();

            // Search functionality
            document.getElementById('searchInput').addEventListener('input', filterUsers);

            // Filter buttons
            document.querySelectorAll('.filter-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
                    this.classList.add('active');
                    currentFilter = this.dataset.filter;
                    filterUsers();
                });
            });

            // Modal overlay click handler - only close when clicking directly on overlay
            const modalOverlay = document.getElementById('userModal');
            if (modalOverlay) {
                modalOverlay.addEventListener('click', function(e) {
                    // Only close if clicking directly on the overlay, not on modal content
                    if (e.target === modalOverlay) {
                        closeModal();
                    }
                });
            }

            // Prevent modal content clicks from closing the modal (but allow button clicks)
            const modalContent = document.querySelector('#userModal .modal-content');
            if (modalContent) {
                modalContent.addEventListener('click', function(e) {
                    // Don't stop propagation for buttons, links, or interactive elements
                    const isButton = e.target.closest('button, .btn, .modal-close, [role="button"]');
                    if (!isButton) {
                        // Only stop propagation for non-interactive elements
                        e.stopPropagation();
                    }
                    // Let buttons handle their own clicks
                });
            }
            
            // Set up button handlers as backup (inline onclick is primary)
            function setupButtons() {
                const closeBtn = document.getElementById('modalCloseBtn');
                const cancelBtn = document.getElementById('cancelBtn');
                const saveBtn = document.getElementById('saveUserBtn');
                const addUserBtn = document.getElementById('addUserBtn');
                
                // Helper to close modal directly
                function closeModalDirect() {
                    const modal = document.getElementById('userModal');
                    if (modal) {
                        modal.classList.remove('show');
                        document.body.style.overflow = '';
                        const form = document.getElementById('userForm');
                        if (form) form.reset();
                        const userId = document.getElementById('userId');
                        if (userId) userId.value = '';
                    }
                }
                
                // Helper to open create modal directly
                function openCreateModalDirect() {
                    const title = document.getElementById('modalTitle');
                    if (title) title.innerHTML = '<i class="fas fa-user-plus"></i> Create New User';
                    const form = document.getElementById('userForm');
                    if (form) {
                        form.reset();
                        const id = document.getElementById('userId');
                        if (id) id.value = '';
                        const pwd = document.getElementById('userPassword');
                        if (pwd) pwd.required = true;
                    }
                    const modal = document.getElementById('userModal');
                    if (modal) {
                        modal.classList.add('show');
                        document.body.style.overflow = 'hidden';
                    }
                }
                
                // Add backup listeners
                if (closeBtn) {
                    closeBtn.addEventListener('click', function(e) {
                        if (!e.defaultPrevented) closeModalDirect();
                    }, false);
                }
                
                if (cancelBtn) {
                    cancelBtn.addEventListener('click', function(e) {
                        if (!e.defaultPrevented) closeModalDirect();
                    }, false);
                }
                
                if (saveBtn) {
                    saveBtn.addEventListener('click', function(e) {
                        if (!e.defaultPrevented && typeof window.saveUser === 'function') {
                            window.saveUser();
                        }
                    }, false);
                }
                
                if (addUserBtn) {
                    addUserBtn.addEventListener('click', function(e) {
                        if (!e.defaultPrevented) openCreateModalDirect();
                    }, false);
                }
            }
            
            // Set up backup listeners
            setTimeout(setupButtons, 100);
        });
        
        // Pagination state
        let currentPage = 1;
        let totalPages = 1;
        let pageSize = 50;
        
        function loadUsers(page = 1) {
            currentPage = page;
            fetch(`../api/user-management.php?action=list&page=${page}&limit=${pageSize}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        users = data.users;
                        updateStats(data.stats);
                        
                        // Update pagination info
                        if (data.pagination) {
                            currentPage = data.pagination.page;
                            totalPages = data.pagination.total_pages;
                            updatePaginationControls();
                        }
                        
                        renderUsers(users);
                    } else {
                        showError(data.message || 'Failed to load users');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showError('Failed to load users. Please try again.');
                });
        }
        
        function updatePaginationControls() {
            const paginationContainer = document.getElementById('paginationContainer');
            if (!paginationContainer) return;
            
            if (totalPages <= 1) {
                paginationContainer.innerHTML = '';
                return;
            }
            
            let paginationHTML = '<div class="pagination">';
            
            // Previous button
            paginationHTML += `<button class="pagination-btn" ${currentPage === 1 ? 'disabled' : ''} onclick="loadUsers(${currentPage - 1})">
                <i class="fas fa-chevron-left"></i> Previous
            </button>`;
            
            // Page numbers
            let startPage = Math.max(1, currentPage - 2);
            let endPage = Math.min(totalPages, currentPage + 2);
            
            if (startPage > 1) {
                paginationHTML += `<button class="pagination-btn" onclick="loadUsers(1)">1</button>`;
                if (startPage > 2) paginationHTML += '<span class="pagination-ellipsis">...</span>';
            }
            
            for (let i = startPage; i <= endPage; i++) {
                paginationHTML += `<button class="pagination-btn ${i === currentPage ? 'active' : ''}" onclick="loadUsers(${i})">${i}</button>`;
            }
            
            if (endPage < totalPages) {
                if (endPage < totalPages - 1) paginationHTML += '<span class="pagination-ellipsis">...</span>';
                paginationHTML += `<button class="pagination-btn" onclick="loadUsers(${totalPages})">${totalPages}</button>`;
            }
            
            // Next button
            paginationHTML += `<button class="pagination-btn" ${currentPage === totalPages ? 'disabled' : ''} onclick="loadUsers(${currentPage + 1})">
                Next <i class="fas fa-chevron-right"></i>
            </button>`;
            
            paginationHTML += '</div>';
            paginationHTML += `<div class="pagination-info">Page ${currentPage} of ${totalPages}</div>`;
            
            paginationContainer.innerHTML = paginationHTML;
        }
        
        // Make loadUsers available globally
        window.loadUsers = loadUsers;
        
        function updateStats(stats) {
            document.getElementById('totalAdmins').textContent = stats.admins || 0;
            document.getElementById('totalStaff').textContent = stats.staff || 0;
            document.getElementById('totalPending').textContent = stats.pending || 0;
            document.getElementById('totalInactive').textContent = stats.inactive || 0;
        }
        
        function renderUsers(usersToRender) {
            // Render table (desktop)
            renderUsersTable(usersToRender);
            // Render cards (mobile)
            renderUsersCards(usersToRender);
        }
        
        function renderUsersTable(usersToRender) {
            const tbody = document.getElementById('usersTableBody');
            
            if (usersToRender.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="6">
                            <div class="empty-state">
                                <i class="fas fa-users"></i>
                                <p>No users found</p>
                            </div>
                        </td>
                    </tr>
                `;
                return;
            }
            
            tbody.innerHTML = usersToRender.map(user => `
                <tr data-id="${user.id}">
                    <td>
                        <div class="user-info">
                            <div class="user-avatar">${getInitials(user.name)}</div>
                            <div class="user-details">
                                <div class="user-name">${escapeHtml(user.name)}</div>
                                <div class="user-email">${escapeHtml(user.email)}</div>
                            </div>
                        </div>
                    </td>
                    <td><span class="role-badge ${user.role}">${formatRole(user.role)}</span></td>
                    <td><span class="status-badge ${user.status}">${formatStatus(user.status)}</span></td>
                    <td>${formatDate(user.created_at)}</td>
                    <td>${user.last_login ? formatDate(user.last_login) : '<span style="color: var(--text-secondary-1);">Never</span>'}</td>
                    <td>
                        <div class="action-buttons">
                            <button class="action-btn edit" onclick="editUser(${user.id})" title="Edit User" aria-label="Edit User">
                                <i class="fas fa-edit"></i>
                            </button>
                            ${user.role !== 'super_admin' ? `
                            <button class="action-btn delete" onclick="deleteUser(${user.id}, '${escapeHtml(user.name)}')" title="Delete User" aria-label="Delete User">
                                <i class="fas fa-trash"></i>
                            </button>
                            ` : ''}
                        </div>
                    </td>
                </tr>
            `).join('');
        }
        
        function renderUsersCards(usersToRender) {
            const container = document.getElementById('usersCardsContainer');
            
            if (usersToRender.length === 0) {
                container.innerHTML = `
                    <div class="empty-state">
                        <i class="fas fa-users"></i>
                        <p>No users found</p>
                    </div>
                `;
                return;
            }
            
            container.innerHTML = usersToRender.map(user => `
                <div class="user-card" data-id="${user.id}">
                    <div class="user-card-header">
                        <div class="user-info">
                            <div class="user-avatar">${getInitials(user.name)}</div>
                            <div class="user-details">
                                <div class="user-name">${escapeHtml(user.name)}</div>
                                <div class="user-email">${escapeHtml(user.email)}</div>
                            </div>
                        </div>
                    </div>
                    <div class="user-card-body">
                        <div class="user-card-field">
                            <span class="user-card-field-label">Role</span>
                            <span class="user-card-field-value">
                                <span class="role-badge ${user.role}">${formatRole(user.role)}</span>
                            </span>
                        </div>
                        <div class="user-card-field">
                            <span class="user-card-field-label">Status</span>
                            <span class="user-card-field-value">
                                <span class="status-badge ${user.status}">${formatStatus(user.status)}</span>
                            </span>
                        </div>
                        <div class="user-card-field">
                            <span class="user-card-field-label">Created</span>
                            <span class="user-card-field-value">${formatDate(user.created_at)}</span>
                        </div>
                        <div class="user-card-field">
                            <span class="user-card-field-label">Last Login</span>
                            <span class="user-card-field-value">${user.last_login ? formatDate(user.last_login) : 'Never'}</span>
                        </div>
                    </div>
                    <div class="user-card-footer">
                        <button class="btn btn-secondary" onclick="editUser(${user.id})">
                            <i class="fas fa-edit"></i> Edit
                        </button>
                        ${user.role !== 'super_admin' ? `
                        <button class="btn btn-primary" style="background: #e74c3c;" onclick="deleteUser(${user.id}, '${escapeHtml(user.name)}')">
                            <i class="fas fa-trash"></i> Delete
                        </button>
                        ` : ''}
                    </div>
                </div>
            `).join('');
        }
        
        function filterUsers() {
            // Note: With pagination, filtering should ideally be done server-side
            // For now, we'll filter the current page results client-side
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            
            let filtered = users.filter(user => {
                const matchesSearch = user.name.toLowerCase().includes(searchTerm) || 
                                     user.email.toLowerCase().includes(searchTerm);
                const matchesFilter = currentFilter === 'all' || user.role === currentFilter;
                return matchesSearch && matchesFilter;
            });
            
            renderUsers(filtered);
        }
        
        function openCreateModal() {
            document.getElementById('modalTitle').innerHTML = '<i class="fas fa-user-plus"></i> Create New User';
            document.getElementById('userForm').reset();
            document.getElementById('userId').value = '';
            document.getElementById('userPassword').required = true;
            document.getElementById('userModal').classList.add('show');
            document.body.style.overflow = 'hidden';
        }
        // Make available globally immediately
        window.openCreateModal = openCreateModal;
        
        function editUser(id) {
            const user = users.find(u => u.id === id);
            if (!user) return;
            
            document.getElementById('modalTitle').innerHTML = '<i class="fas fa-user-edit"></i> Edit User';
            document.getElementById('userId').value = user.id;
            document.getElementById('userName').value = user.name;
            document.getElementById('userEmail').value = user.email;
            document.getElementById('userPassword').value = '';
            document.getElementById('userPassword').required = false;
            document.getElementById('userRole').value = user.role;
            document.getElementById('userStatus').value = user.status;
            document.getElementById('userModal').classList.add('show');
            document.body.style.overflow = 'hidden';
        }
        
        function closeModal() {
            const modal = document.getElementById('userModal');
            if (modal) {
                modal.classList.remove('show');
                document.body.style.overflow = '';
                // Reset the form
                const form = document.getElementById('userForm');
                if (form) {
                    form.reset();
                }
                // Clear hidden userId field
                const userIdField = document.getElementById('userId');
                if (userIdField) {
                    userIdField.value = '';
                }
            }
        }
        // Make available globally immediately
        window.closeModal = closeModal;
        
        // Close modal on escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' || e.keyCode === 27) {
                const modal = document.getElementById('userModal');
                if (modal && modal.classList.contains('show')) {
                    closeModal();
                }
            }
        });
        
        function saveUser() {
            const form = document.getElementById('userForm');
            const formData = new FormData(form);
            
            const data = {
                action: formData.get('id') ? 'update' : 'create',
                id: formData.get('id'),
                name: formData.get('name'),
                email: formData.get('email'),
                password: formData.get('password'),
                role: formData.get('role'),
                status: formData.get('status')
            };
            
            // Validation
            if (!data.name || !data.email || !data.role || !data.status) {
                Swal.fire('Error', 'Please fill in all required fields', 'error');
                return;
            }
            
            if (!data.id && !data.password) {
                Swal.fire('Error', 'Password is required for new users', 'error');
                return;
            }
            
            if (data.password && data.password.length < 8) {
                Swal.fire('Error', 'Password must be at least 8 characters', 'error');
                return;
            }
            
            fetch('../api/user-management.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: result.message,
                        timer: 2000,
                        showConfirmButton: false,
                        toast: true,
                        position: 'top-end'
                    });
                    loadUsers();
                    setTimeout(() => {
                        closeModal();
                    }, 500);
                } else {
                    Swal.fire('Error', result.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire('Error', 'Failed to save user. Please try again.', 'error');
            });
        }
        // Make available globally immediately
        window.saveUser = saveUser;
        
        function deleteUser(id, name) {
            Swal.fire({
                title: 'Delete User?',
                html: `Are you sure you want to delete <strong>${escapeHtml(name)}</strong>?<br>This action cannot be undone.`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#e74c3c',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, delete',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch('../api/user-management.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ action: 'delete', id: id })
                    })
                    .then(response => response.json())
                    .then(result => {
                        if (result.success) {
                            Swal.fire('Deleted!', result.message, 'success');
                            loadUsers();
                        } else {
                            Swal.fire('Error', result.message, 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        Swal.fire('Error', 'Failed to delete user', 'error');
                    });
                }
            });
        }
        
        // Helper functions
        function getInitials(name) {
            return name.split(' ').map(n => n[0]).join('').toUpperCase().substring(0, 2);
        }
        
        function formatRole(role) {
            const roles = {
                'super_admin': 'Super Admin',
                'admin': 'Administrator',
                'staff': 'Staff'
            };
            return roles[role] || role;
        }
        
        function formatStatus(status) {
            const statuses = {
                'active': 'Active',
                'inactive': 'Inactive',
                'pending_approval': 'Pending'
            };
            return statuses[status] || status;
        }
        
        function formatDate(dateStr) {
            if (!dateStr) return 'N/A';
            const date = new Date(dateStr);
            return date.toLocaleDateString('en-US', { 
                year: 'numeric', 
                month: 'short', 
                day: 'numeric' 
            });
        }
        
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        function showError(message) {
            document.getElementById('usersTableBody').innerHTML = `
                <tr>
                    <td colspan="6">
                        <div class="empty-state">
                            <i class="fas fa-exclamation-triangle" style="color: #e74c3c;"></i>
                            <p>${message}</p>
                        </div>
                    </td>
                </tr>
            `;
            document.getElementById('usersCardsContainer').innerHTML = `
                <div class="empty-state">
                    <i class="fas fa-exclamation-triangle" style="color: #e74c3c;"></i>
                    <p>${message}</p>
                </div>
            `;
        }
        
        // Make functions globally accessible BEFORE PHP conditional ends
        window.closeModal = closeModal;
        window.openCreateModal = openCreateModal;
        window.editUser = editUser;
        window.saveUser = saveUser;
        window.deleteUser = deleteUser;
        <?php endif; ?>
        
        // Ensure closeModal is always accessible (even outside PHP conditional)
        if (typeof window.closeModal === 'undefined') {
            window.closeModal = function() {
                const modal = document.getElementById('userModal');
                if (modal) {
                    modal.classList.remove('show');
                    document.body.style.overflow = '';
                }
            };
        }
    </script>
</body>
</html>

