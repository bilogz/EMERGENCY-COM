<?php
/**
 * User Auto-Warning Settings Page
 * Allows users to control AI-powered automatic warnings
 */

session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$pageTitle = 'Auto-Warning Settings';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary-color: #667eea;
            --secondary-color: #764ba2;
            --success-color: #4caf50;
            --danger-color: #f44336;
            --warning-color: #ff9800;
            --info-color: #2196F3;
            --background-color: #f5f7fa;
            --card-background: #ffffff;
            --text-primary: #2c3e50;
            --text-secondary: #7f8c8d;
            --border-color: #e1e8ed;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 2rem;
            color: var(--text-primary);
        }

        .container {
            max-width: 1000px;
            margin: 0 auto;
        }

        .header {
            background: var(--card-background);
            padding: 2rem;
            border-radius: 12px 12px 0 0;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .header h1 {
            display: flex;
            align-items: center;
            gap: 1rem;
            color: var(--primary-color);
            font-size: 1.8rem;
            margin-bottom: 0.5rem;
        }

        .header p {
            color: var(--text-secondary);
            margin-left: 3.5rem;
        }

        .content {
            background: var(--card-background);
            padding: 2rem;
            border-radius: 0 0 12px 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }

        .toggle-section {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1.5rem;
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%);
            border-radius: 12px;
            margin-bottom: 2rem;
            border: 2px solid var(--primary-color);
        }

        .toggle-info {
            flex: 1;
        }

        .toggle-info h3 {
            color: var(--primary-color);
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .toggle-info p {
            color: var(--text-secondary);
            font-size: 0.9rem;
        }

        .switch {
            position: relative;
            display: inline-block;
            width: 60px;
            height: 34px;
        }

        .switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
            border-radius: 34px;
        }

        .slider:before {
            position: absolute;
            content: "";
            height: 26px;
            width: 26px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }

        input:checked + .slider {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
        }

        input:checked + .slider:before {
            transform: translateX(26px);
        }

        .section {
            margin-bottom: 2rem;
        }

        .section-title {
            font-size: 1.2rem;
            color: var(--text-primary);
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid var(--border-color);
        }

        .categories-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .category-card {
            border: 2px solid var(--border-color);
            border-radius: 8px;
            padding: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            background: white;
        }

        .category-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        .category-card.selected {
            border-color: var(--primary-color);
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.05) 0%, rgba(118, 75, 162, 0.05) 100%);
        }

        .category-card input[type="checkbox"] {
            display: none;
        }

        .category-header {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 0.5rem;
        }

        .category-icon {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            color: white;
        }

        .category-label {
            font-weight: 600;
            color: var(--text-primary);
        }

        .category-desc {
            font-size: 0.85rem;
            color: var(--text-secondary);
            margin-left: 52px;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
        }

        .form-group select {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid var(--border-color);
            border-radius: 8px;
            font-size: 1rem;
            background: white;
            cursor: pointer;
            transition: border-color 0.3s;
        }

        .form-group select:focus {
            outline: none;
            border-color: var(--primary-color);
        }

        .notification-channels {
            display: flex;
            gap: 1.5rem;
            flex-wrap: wrap;
        }

        .channel-option {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.25rem;
            border: 2px solid var(--border-color);
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
            background: white;
        }

        .channel-option:hover {
            border-color: var(--primary-color);
        }

        .channel-option.disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .channel-option input[type="checkbox"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
        }

        .btn {
            padding: 1rem 2rem;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }

        .btn-secondary {
            background: var(--text-secondary);
            color: white;
        }

        .actions {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 2px solid var(--border-color);
        }

        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            display: none;
        }

        .alert.success {
            background: #e8f5e9;
            border-left: 4px solid var(--success-color);
            color: #2e7d32;
        }

        .alert.error {
            background: #ffebee;
            border-left: 4px solid var(--danger-color);
            color: #c62828;
        }

        .loading {
            display: none;
            text-align: center;
            padding: 2rem;
        }

        .loading.active {
            display: block;
        }

        @media (max-width: 768px) {
            body {
                padding: 1rem;
            }

            .categories-grid {
                grid-template-columns: 1fr;
            }

            .notification-channels {
                flex-direction: column;
            }

            .actions {
                flex-direction: column;
            }

            .btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>
                <i class="fas fa-robot"></i>
                AI Auto-Warning Settings
            </h1>
            <p>Configure automatic disaster warnings powered by AI</p>
        </div>

        <div class="content">
            <div id="alertBox" class="alert"></div>
            
            <div class="loading" id="loadingIndicator">
                <i class="fas fa-spinner fa-spin" style="font-size: 2rem; color: var(--primary-color);"></i>
                <p>Loading your preferences...</p>
            </div>

            <form id="preferencesForm" style="display: none;">
                <!-- Main Toggle -->
                <div class="toggle-section">
                    <div class="toggle-info">
                        <h3>
                            <i class="fas fa-bell"></i>
                            Enable AI Auto-Warnings
                        </h3>
                        <p>Receive automatic alerts when AI detects dangerous conditions in your area</p>
                    </div>
                    <label class="switch">
                        <input type="checkbox" id="autoWarningEnabled" name="enabled" checked>
                        <span class="slider"></span>
                    </label>
                </div>

                <!-- Warning Categories -->
                <div class="section">
                    <div class="section-title">
                        <i class="fas fa-list-check"></i>
                        Alert Categories
                    </div>
                    <p style="color: var(--text-secondary); margin-bottom: 1rem;">Select which types of disasters you want to receive automatic warnings for:</p>
                    <div class="categories-grid" id="categoriesContainer">
                        <!-- Categories will be loaded dynamically -->
                    </div>
                </div>

                <!-- Frequency & Severity -->
                <div class="section">
                    <div class="section-title">
                        <i class="fas fa-sliders-h"></i>
                        Alert Preferences
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                        <div class="form-group">
                            <label for="frequency">
                                <i class="fas fa-clock"></i> Alert Frequency
                            </label>
                            <select id="frequency" name="frequency">
                                <option value="realtime">Real-time (Immediate)</option>
                                <option value="hourly">Hourly Summary</option>
                                <option value="daily">Daily Summary</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="severity">
                                <i class="fas fa-exclamation-triangle"></i> Minimum Severity
                            </label>
                            <select id="severity" name="severity">
                                <option value="all">All Alerts</option>
                                <option value="high">High Priority Only</option>
                                <option value="critical">Critical Only</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Notification Channels -->
                <div class="section">
                    <div class="section-title">
                        <i class="fas fa-paper-plane"></i>
                        Notification Channels
                    </div>
                    <p style="color: var(--text-secondary); margin-bottom: 1rem;">Choose how you want to receive alerts:</p>
                    <div class="notification-channels" id="channelsContainer">
                        <!-- Channels will be shown based on user's main notification preferences -->
                    </div>
                </div>

                <!-- Actions -->
                <div class="actions">
                    <button type="button" class="btn btn-secondary" onclick="window.history.back()">
                        <i class="fas fa-arrow-left"></i> Cancel
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Save Settings
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        let categoriesData = {};
        let currentPreferences = {};

        // Load preferences on page load
        document.addEventListener('DOMContentLoaded', function() {
            loadPreferences();
        });

        async function loadPreferences() {
            try {
                document.getElementById('loadingIndicator').classList.add('active');
                
                // Load categories
                const categoriesResponse = await fetch('api/auto-warning-preferences.php?action=getCategories');
                const categoriesData = await categoriesResponse.json();
                
                if (!categoriesData.success) {
                    throw new Error('Failed to load categories');
                }
                
                // Load user preferences
                const prefsResponse = await fetch('api/auto-warning-preferences.php?action=get');
                const prefsData = await prefsResponse.json();
                
                if (!prefsData.success) {
                    throw new Error('Failed to load preferences');
                }
                
                currentPreferences = prefsData.preferences;
                
                // Display categories
                displayCategories(categoriesData.categories);
                
                // Set form values
                document.getElementById('autoWarningEnabled').checked = currentPreferences.enabled;
                document.getElementById('frequency').value = currentPreferences.frequency;
                document.getElementById('severity').value = currentPreferences.severity;
                
                // Display notification channels
                displayChannels(currentPreferences.notification_channels);
                
                // Show form
                document.getElementById('loadingIndicator').classList.remove('active');
                document.getElementById('preferencesForm').style.display = 'block';
                
            } catch (error) {
                console.error('Error loading preferences:', error);
                showAlert('error', 'Failed to load preferences: ' + error.message);
                document.getElementById('loadingIndicator').classList.remove('active');
            }
        }

        function displayCategories(categories) {
            const container = document.getElementById('categoriesContainer');
            container.innerHTML = '';
            
            Object.keys(categories).forEach(key => {
                const category = categories[key];
                const isSelected = currentPreferences.categories.includes(key);
                
                const card = document.createElement('div');
                card.className = 'category-card' + (isSelected ? ' selected' : '');
                card.onclick = () => toggleCategory(key);
                
                card.innerHTML = `
                    <input type="checkbox" id="cat_${key}" name="categories[]" value="${key}" ${isSelected ? 'checked' : ''}>
                    <div class="category-header">
                        <div class="category-icon" style="background: ${category.color};">
                            <i class="fas ${category.icon}"></i>
                        </div>
                        <span class="category-label">${category.label}</span>
                    </div>
                    <div class="category-desc">${category.description}</div>
                `;
                
                container.appendChild(card);
            });
        }

        function toggleCategory(categoryKey) {
            const checkbox = document.getElementById('cat_' + categoryKey);
            checkbox.checked = !checkbox.checked;
            checkbox.closest('.category-card').classList.toggle('selected');
        }

        function displayChannels(channels) {
            const container = document.getElementById('channelsContainer');
            container.innerHTML = '';
            
            const channelInfo = {
                sms: { icon: 'fa-sms', label: 'SMS', color: '#4caf50' },
                email: { icon: 'fa-envelope', label: 'Email', color: '#2196F3' },
                push: { icon: 'fa-bell', label: 'Push Notifications', color: '#ff9800' }
            };
            
            Object.keys(channelInfo).forEach(key => {
                const info = channelInfo[key];
                const isEnabled = channels[key];
                
                const option = document.createElement('div');
                option.className = 'channel-option' + (!isEnabled ? ' disabled' : '');
                option.innerHTML = `
                    <input type="checkbox" id="channel_${key}" ${isEnabled ? 'checked' : ''} ${!isEnabled ? 'disabled' : ''}>
                    <i class="fas ${info.icon}" style="color: ${info.color};"></i>
                    <span>${info.label}</span>
                `;
                
                if (!isEnabled) {
                    option.title = 'Enable this in your main notification settings first';
                }
                
                container.appendChild(option);
            });
        }

        document.getElementById('preferencesForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const submitBtn = event.submitter;
            const originalHTML = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
            submitBtn.disabled = true;
            
            try {
                // Collect selected categories
                const selectedCategories = [];
                document.querySelectorAll('input[name="categories[]"]:checked').forEach(checkbox => {
                    selectedCategories.push(checkbox.value);
                });
                
                const data = {
                    enabled: document.getElementById('autoWarningEnabled').checked ? 1 : 0,
                    categories: selectedCategories,
                    frequency: document.getElementById('frequency').value,
                    severity: document.getElementById('severity').value
                };
                
                const response = await fetch('api/auto-warning-preferences.php?action=update', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showAlert('success', '✅ Settings saved successfully!');
                    setTimeout(() => {
                        window.location.href = 'dashboard.php';
                    }, 2000);
                } else {
                    throw new Error(result.message);
                }
                
            } catch (error) {
                console.error('Error saving preferences:', error);
                showAlert('error', '❌ Failed to save settings: ' + error.message);
                submitBtn.innerHTML = originalHTML;
                submitBtn.disabled = false;
            }
        });

        function showAlert(type, message) {
            const alertBox = document.getElementById('alertBox');
            alertBox.className = 'alert ' + type;
            alertBox.innerHTML = '<i class="fas fa-' + (type === 'success' ? 'check-circle' : 'exclamation-triangle') + '"></i> ' + message;
            alertBox.style.display = 'block';
            
            setTimeout(() => {
                alertBox.style.display = 'none';
            }, 5000);
        }
    </script>
</body>
</html>



