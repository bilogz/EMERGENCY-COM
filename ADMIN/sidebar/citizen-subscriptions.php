<?php
/**
 * Citizen Subscription and Alert Preferences Page
 * Manage citizen subscriptions and their alert preferences
 */

// Start session and check authentication
session_start();

// Check if user is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../login.php');
    exit();
}

$pageTitle = 'Citizen Subscription and Alert Preferences';
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
       MAIN CONTENT - Citizen Subscriptions
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
                            <span>Citizen Subscriptions</span>
                        </li>
                    </ol>
                </nav>
                <h1>Citizen Subscription and Alert Preferences</h1>
                <p>Manage citizen subscriptions and allow citizens to customize their alert preferences for personalized emergency notifications.</p>
                <div class="info-box" style="background: #e3f2fd; border-left: 4px solid #2196f3; padding: 1rem; border-radius: 4px; margin-top: 1rem;">
                    <i class="fas fa-info-circle" style="color: #2196f3;"></i>
                    <strong>How to use:</strong> View all subscribers and their preferences. Click "View" to see or edit a subscriber's alert categories, notification channels, and preferred language. Use the search box to find specific subscribers.
                </div>
            </div>
            
            <div class="sub-container">
                <div class="page-content">
                    <!-- Subscription Statistics -->
                    <div class="module-card">
                        <div class="module-card-header">
                            <h2><i class="fas fa-chart-bar"></i> Subscription Statistics</h2>
                        </div>
                        <div>
                            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem;">
                                <div class="stat-card" style="text-align: center;">
                                    <div>
                                        <h3 style="font-size: 2.5rem; margin: 0; color: #4c8a89;" id="totalSubscribers">0</h3>
                                        <p>Total Subscribers</p>
                                    </div>
                                </div>
                                <div class="stat-card" style="text-align: center;">
                                    <div>
                                        <h3 style="font-size: 2.5rem; margin: 0; color: var(--primary-color-1);" id="activeSubscribers">0</h3>
                                        <p>Active Subscribers</p>
                                    </div>
                                </div>
                                <div class="stat-card" style="text-align: center;">
                                    <div>
                                        <h3 style="font-size: 2.5rem; margin: 0; color: var(--primary-color-1);" id="weatherSubscribers">0</h3>
                                        <p>Weather Alert Subscribers</p>
                                    </div>
                                </div>
                                <div class="stat-card" style="text-align: center;">
                                    <div>
                                        <h3 style="font-size: 2.5rem; margin: 0; color: #4c8a89;" id="earthquakeSubscribers">0</h3>
                                        <p>Earthquake Alert Subscribers</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Subscribers List -->
                    <div class="module-card">
                        <div class="module-card-header" style="display: flex; justify-content: space-between; align-items: center;">
                            <h2><i class="fas fa-users"></i> Subscribers</h2>
                            <button class="btn btn-primary" onclick="exportSubscribers()">
                                <i class="fas fa-download"></i> Export
                            </button>
                        </div>
                        <div>
                            <div style="margin-bottom: 1rem;">
                                <input type="text" id="searchSubscribers" placeholder="Search subscribers..." style="width: 100%; padding: 0.75rem;">
                            </div>
                            <table class="data-table" id="subscribersTable">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Phone</th>
                                        <th>Address</th>
                                        <th>Device</th>
                                        <th>Last Active</th>
                                        <th>Categories</th>
                                        <th>Channels</th>
                                        <th>Language</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Data will be loaded via API -->
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- View/Edit Subscription Modal -->
                    <div id="subscriptionModal" class="modal" style="display: none;">
                        <div class="modal-content" style="max-width: 900px; max-height: 90vh; overflow-y: auto;">
                            <div class="modal-header">
                                <h2 id="modalTitle">User Details & Subscription</h2>
                                <button class="modal-close" onclick="closeSubscriptionModal()">&times;</button>
                            </div>
                            <div class="modal-body">
                                <!-- Tabs for different sections -->
                                <div style="margin-bottom: 1rem; border-bottom: 2px solid #e5e7eb;">
                                    <button class="tab-btn active" onclick="switchTab('user-info')">User Info</button>
                                    <button class="tab-btn" onclick="switchTab('subscription')">Subscription</button>
                                    <button class="tab-btn" onclick="switchTab('devices')">Devices</button>
                                    <button class="tab-btn" onclick="switchTab('location')">Location</button>
                                    <button class="tab-btn" onclick="switchTab('activity')">Activity</button>
                                </div>
                                
                                <!-- User Info Tab -->
                                <div id="tab-user-info" class="tab-content">
                                    <h3>User Information</h3>
                                    <div id="userInfoDetails" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                                        <!-- Will be populated by JavaScript -->
                                    </div>
                                </div>
                                
                                <!-- Subscription Tab -->
                                <div id="tab-subscription" class="tab-content" style="display: none;">
                                    <form id="subscriptionForm">
                                        <input type="hidden" id="subscriberId" name="subscriber_id">
                                        <div class="form-group">
                                            <label>Subscribed Categories</label>
                                            <div style="display: flex; flex-direction: column; gap: 0.5rem; margin-top: 0.5rem;">
                                                <label><input type="checkbox" name="categories[]" value="weather"> Weather Alerts</label>
                                                <label><input type="checkbox" name="categories[]" value="earthquake"> Earthquake Alerts</label>
                                                <label><input type="checkbox" name="categories[]" value="bomb"> Bomb Threat Alerts</label>
                                                <label><input type="checkbox" name="categories[]" value="fire"> Fire Alerts</label>
                                                <label><input type="checkbox" name="categories[]" value="general"> General Alerts</label>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label>Notification Channels</label>
                                            <div style="display: flex; flex-direction: column; gap: 0.5rem; margin-top: 0.5rem;">
                                                <label><input type="checkbox" name="channels[]" value="sms"> SMS</label>
                                                <label><input type="checkbox" name="channels[]" value="email"> Email</label>
                                                <label><input type="checkbox" name="channels[]" value="push"> Push Notification</label>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label for="preferredLanguage">Preferred Language</label>
                                            <select id="preferredLanguage" name="preferred_language">
                                                <option value="">Loading languages...</option>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label for="subscriptionStatus">Status</label>
                                            <select id="subscriptionStatus" name="status">
                                                <option value="active">Active</option>
                                                <option value="inactive">Inactive</option>
                                                <option value="suspended">Suspended</option>
                                            </select>
                                        </div>
                                        <div class="form-actions">
                                            <button type="button" class="btn btn-secondary" onclick="closeSubscriptionModal()">Cancel</button>
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fas fa-save"></i> Save Changes
                                            </button>
                                        </div>
                                    </form>
                                </div>
                                
                                <!-- Devices Tab -->
                                <div id="tab-devices" class="tab-content" style="display: none;">
                                    <h3>Registered Devices</h3>
                                    <div id="devicesList">
                                        <!-- Will be populated by JavaScript -->
                                    </div>
                                </div>
                                
                                <!-- Location Tab -->
                                <div id="tab-location" class="tab-content" style="display: none;">
                                    <h3>Location History</h3>
                                    <div id="locationsList">
                                        <!-- Will be populated by JavaScript -->
                                    </div>
                                </div>
                                
                                <!-- Activity Tab -->
                                <div id="tab-activity" class="tab-content" style="display: none;">
                                    <h3>Recent Activity</h3>
                                    <div id="activitiesList">
                                        <!-- Will be populated by JavaScript -->
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <style>
                        .tab-btn {
                            padding: 0.5rem 1rem;
                            background: none;
                            border: none;
                            border-bottom: 2px solid transparent;
                            cursor: pointer;
                            font-size: 0.9rem;
                            color: #6b7280;
                            margin-right: 0.5rem;
                        }
                        .tab-btn:hover {
                            color: #4c8a89;
                        }
                        .tab-btn.active {
                            color: #4c8a89;
                            border-bottom-color: #4c8a89;
                            font-weight: 600;
                        }
                        .tab-content {
                            padding: 1rem 0;
                        }
                        .info-item {
                            margin-bottom: 0.75rem;
                        }
                        .info-label {
                            font-weight: 600;
                            color: #374151;
                            font-size: 0.85rem;
                            margin-bottom: 0.25rem;
                        }
                        .info-value {
                            color: #6b7280;
                            font-size: 0.9rem;
                        }
                    </style>
                </div>
            </div>
        </div>
    </div>

    <script>
        function loadSubscribers() {
            fetch('../api/citizen-subscriptions.php?action=list')
                .then(response => response.json())
                .then(data => {
                    const tbody = document.querySelector('#subscribersTable tbody');
                    tbody.innerHTML = '';
                    
                    if (data.success && data.subscribers) {
                        data.subscribers.forEach(sub => {
                            const row = document.createElement('tr');
                            const address = sub.address ? 
                                `${sub.address.house_number || ''} ${sub.address.street || ''}, ${sub.address.barangay || ''}`.trim() || 
                                sub.address.full_address || 'N/A' : 'N/A';
                            const deviceInfo = sub.device ? 
                                `${sub.device.latest_type || 'N/A'} (${sub.device.count || 0})` : 'N/A';
                            const lastActive = sub.device && sub.device.last_active ? 
                                new Date(sub.device.last_active).toLocaleDateString() : 'Never';
                            
                            row.innerHTML = `
                                <td>${sub.id}</td>
                                <td>${sub.name || 'N/A'}</td>
                                <td>${sub.email || 'N/A'}</td>
                                <td>${sub.phone || 'N/A'}</td>
                                <td style="max-width: 200px; overflow: hidden; text-overflow: ellipsis;" title="${address}">${address}</td>
                                <td>${deviceInfo}</td>
                                <td>${lastActive}</td>
                                <td>${sub.subscription.categories.join(', ') || 'None'}</td>
                                <td>${sub.subscription.channels.join(', ') || 'None'}</td>
                                <td>${sub.subscription.language || 'en'}</td>
                                <td><span class="badge ${sub.subscription.status}">${sub.subscription.status}</span></td>
                                <td>
                                    <button class="btn btn-sm btn-primary" onclick="viewSubscription(${sub.id})" title="View Details">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="btn btn-sm btn-danger" onclick="deleteSubscription(${sub.id})" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            `;
                            tbody.appendChild(row);
                        });
                    }
                });
        }
        
        function switchTab(tabName) {
            // Hide all tabs
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.style.display = 'none';
            });
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            
            // Show selected tab
            document.getElementById('tab-' + tabName).style.display = 'block';
            event.target.classList.add('active');
        }

        function loadStatistics() {
            fetch('../api/citizen-subscriptions.php?action=statistics')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('totalSubscribers').textContent = data.total || 0;
                        document.getElementById('activeSubscribers').textContent = data.active || 0;
                        document.getElementById('weatherSubscribers').textContent = data.weather || 0;
                        document.getElementById('earthquakeSubscribers').textContent = data.earthquake || 0;
                    }
                });
        }

        function viewSubscription(id) {
            // Ensure languages are loaded before opening modal
            ensureLanguagesLoaded().then(() => {
            fetch(`../api/citizen-subscriptions.php?action=get&id=${id}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.subscriber) {
                        const sub = data.subscriber;
                        document.getElementById('subscriberId').value = sub.id;
                        
                        // Populate User Info Tab
                        const userInfoHtml = `
                            <div class="info-item">
                                <div class="info-label">Name</div>
                                <div class="info-value">${sub.name || 'N/A'}</div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Email</div>
                                <div class="info-value">${sub.email || 'N/A'} ${sub.auth && sub.auth.email_verified ? '<span style="color: green;">✓ Verified</span>' : ''}</div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Phone</div>
                                <div class="info-value">${sub.phone || 'N/A'}</div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">User ID</div>
                                <div class="info-value">${sub.user_id || 'N/A'}</div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Status</div>
                                <div class="info-value"><span class="badge ${sub.user_status}">${sub.user_status || 'active'}</span></div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Registered</div>
                                <div class="info-value">${sub.user_created_at ? new Date(sub.user_created_at).toLocaleString() : 'N/A'}</div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Address</div>
                                <div class="info-value">${sub.address ? 
                                    `${sub.address.house_number || ''} ${sub.address.street || ''}, ${sub.address.barangay || ''}, ${sub.address.district || ''}`.trim() || 
                                    sub.address.full_address || 'N/A' : 'N/A'}</div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Nationality</div>
                                <div class="info-value">${sub.address && sub.address.nationality ? sub.address.nationality : 'N/A'}</div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Auth Method</div>
                                <div class="info-value">${sub.auth && sub.auth.google_id ? 'Google OAuth' : 'Email/Phone'}</div>
                            </div>
                        `;
                        document.getElementById('userInfoDetails').innerHTML = userInfoHtml;
                        
                        // Populate Subscription Tab
                        document.querySelectorAll('input[name="categories[]"]').forEach(cb => {
                            cb.checked = sub.subscription && sub.subscription.categories ? sub.subscription.categories.includes(cb.value) : false;
                        });
                        document.querySelectorAll('input[name="channels[]"]').forEach(cb => {
                            cb.checked = sub.subscription && sub.subscription.channels ? sub.subscription.channels.includes(cb.value) : false;
                        });
                        const langSelect = document.getElementById('preferredLanguage');
                        langSelect.value = (sub.subscription && sub.subscription.language) ? sub.subscription.language : 'en';
                        document.getElementById('subscriptionStatus').value = (sub.subscription && sub.subscription.status) ? sub.subscription.status : 'active';
                        
                        // Populate Devices Tab
                        if (sub.devices && sub.devices.length > 0) {
                            const devicesHtml = sub.devices.map(device => `
                                <div style="padding: 1rem; border: 1px solid #e5e7eb; border-radius: 8px; margin-bottom: 0.5rem;">
                                    <div><strong>${device.device_name || 'Unknown Device'}</strong></div>
                                    <div style="color: #6b7280; font-size: 0.85rem;">
                                        Type: ${device.device_type || 'N/A'} | 
                                        Status: ${device.is_active ? '<span style="color: green;">Active</span>' : '<span style="color: red;">Inactive</span>'} | 
                                        Last Active: ${device.last_active ? new Date(device.last_active).toLocaleString() : 'Never'}
                                    </div>
                                </div>
                            `).join('');
                            document.getElementById('devicesList').innerHTML = devicesHtml;
                        } else {
                            document.getElementById('devicesList').innerHTML = '<p>No devices registered.</p>';
                        }
                        
                        // Populate Location Tab
                        if (sub.locations && sub.locations.length > 0) {
                            const locationsHtml = sub.locations.map(loc => `
                                <div style="padding: 1rem; border: 1px solid #e5e7eb; border-radius: 8px; margin-bottom: 0.5rem;">
                                    <div><strong>${loc.address || 'No address'}</strong> ${loc.is_current ? '<span style="color: green;">(Current)</span>' : ''}</div>
                                    <div style="color: #6b7280; font-size: 0.85rem;">
                                        Coordinates: ${loc.latitude || 'N/A'}, ${loc.longitude || 'N/A'} | 
                                        Updated: ${loc.created_at ? new Date(loc.created_at).toLocaleString() : 'N/A'}
                                    </div>
                                </div>
                            `).join('');
                            document.getElementById('locationsList').innerHTML = locationsHtml;
                        } else {
                            document.getElementById('locationsList').innerHTML = '<p>No location data available.</p>';
                        }
                        
                        // Populate Activity Tab
                        if (sub.activities && sub.activities.length > 0) {
                            const activitiesHtml = sub.activities.map(act => `
                                <div style="padding: 1rem; border: 1px solid #e5e7eb; border-radius: 8px; margin-bottom: 0.5rem;">
                                    <div><strong>${act.activity_type || 'Unknown'}</strong> 
                                        <span class="badge ${act.status === 'success' ? 'success' : act.status}">${act.status || 'N/A'}</span>
                                    </div>
                                    <div style="color: #6b7280; font-size: 0.85rem; margin-top: 0.25rem;">
                                        ${act.description || 'No description'} | 
                                        IP: ${act.ip_address || 'N/A'} | 
                                        ${act.created_at ? new Date(act.created_at).toLocaleString() : 'N/A'}
                                    </div>
                                </div>
                            `).join('');
                            document.getElementById('activitiesList').innerHTML = activitiesHtml;
                        } else {
                            document.getElementById('activitiesList').innerHTML = '<p>No activity logs available.</p>';
                        }
                        
                        // Reset to first tab
                        document.querySelectorAll('.tab-content').forEach(tab => tab.style.display = 'none');
                        document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
                        document.getElementById('tab-user-info').style.display = 'block';
                        document.querySelector('.tab-btn').classList.add('active');
                        
                        document.getElementById('subscriptionModal').style.display = 'block';
                    }
                });
            });
        }

        function closeSubscriptionModal() {
            document.getElementById('subscriptionModal').style.display = 'none';
            document.getElementById('subscriptionForm').reset();
        }

        document.getElementById('subscriptionForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            
            fetch('../api/citizen-subscriptions.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Subscription updated successfully!');
                    closeSubscriptionModal();
                    loadSubscribers();
                    loadStatistics();
                } else {
                    alert('Error: ' + data.message);
                }
            });
        });

        function deleteSubscription(id) {
            if (confirm('Are you sure you want to delete this subscription?')) {
                fetch('../api/citizen-subscriptions.php', {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({id: id})
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Subscription deleted successfully!');
                        loadSubscribers();
                        loadStatistics();
                    } else {
                        alert('Error: ' + data.message);
                    }
                });
            }
        }

        function exportSubscribers() {
            window.location.href = '../api/citizen-subscriptions.php?action=export';
        }

        // Search functionality
        document.getElementById('searchSubscribers').addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            const rows = document.querySelectorAll('#subscribersTable tbody tr');
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchTerm) ? '' : 'none';
            });
        });

        // ===== Languages (80+) from supported_languages =====
        let cachedLanguages = null;
        async function loadLanguagesForSelect() {
            try {
                const res = await fetch('../api/language-management.php?action=list');
                const data = await res.json();
                if (data.success && Array.isArray(data.languages)) {
                    cachedLanguages = data.languages;
                    const select = document.getElementById('preferredLanguage');
                    select.innerHTML = '';
                    data.languages.forEach(lang => {
                        const opt = document.createElement('option');
                        opt.value = lang.language_code;
                        opt.textContent = (lang.flag_emoji ? (lang.flag_emoji + ' ') : '') + (lang.language_name || lang.language_code);
                        select.appendChild(opt);
                    });
                    // Ensure English is selected by default if present
                    if (!select.value && select.querySelector('option[value="en"]')) {
                        select.value = 'en';
                    }
                }
            } catch (e) {
                console.error('Failed to load languages', e);
            }
        }
        async function ensureLanguagesLoaded() {
            if (!cachedLanguages) {
                await loadLanguagesForSelect();
            }
        }

        // Load data on page load
        document.addEventListener('DOMContentLoaded', function() {
            loadSubscribers();
            loadStatistics();
            loadLanguagesForSelect();
        });
    </script>
</body>
</html>

