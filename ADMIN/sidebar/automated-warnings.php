<?php
/**
 * Automated Warning Integration Page
 * Integrate with external warning feeds like PAGASA and PHIVOLCS
 */

// Start session and check authentication
session_start();

// Check if user is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../login.php');
    exit();
}

// Avoid stale HTML when testing through tunnels/browsers.
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

$pageTitle = 'Automated Warning Integration';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <link rel="icon" type="image/x-icon" href="images/favicon.ico">
    <link rel="stylesheet" href="css/global.css?v=<?php echo filemtime(__DIR__ . '/css/global.css'); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/sidebar.css?v=<?php echo filemtime(__DIR__ . '/css/sidebar.css'); ?>">
    <link rel="stylesheet" href="css/admin-header.css">
    <link rel="stylesheet" href="css/buttons.css">
    <link rel="stylesheet" href="css/forms.css">
    <link rel="stylesheet" href="css/datatables.css">
    <link rel="stylesheet" href="css/hero.css">
    <link rel="stylesheet" href="css/sidebar-footer.css">
    <link rel="stylesheet" href="css/modules.css">
        <link rel="stylesheet" href="css/module-automated-warnings.css?v=<?php echo filemtime(__DIR__ . '/css/module-automated-warnings.css'); ?>">
</head>
<body>
    <!-- Include Sidebar Component -->
    <?php include 'includes/sidebar.php'; ?>

    <!-- Include Admin Header Component -->
    <?php include 'includes/admin-header.php'; ?>
    
    <!-- ===================================
       MAIN CONTENT - Automated Warning Integration
       =================================== -->
    <div class="main-content">
        <div class="main-container">
            <div class="title">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                    <div style="font-size: 0.9rem; color: var(--text-secondary-1);">
                        <i class="fas fa-user-circle" style="margin-right: 0.5rem;"></i>
                        <strong>Admin:</strong> <?php echo htmlspecialchars($_SESSION['admin_username'] ?? 'Admin User'); ?>
                    </div>
                </div>
                <nav class="breadcrumb" aria-label="Breadcrumb">
                    <ol class="breadcrumb-list">
                        <li class="breadcrumb-item">
                            <a href="dashboard.php" class="breadcrumb-link">Dashboard</a>
                        </li>
                        <li class="breadcrumb-item active" aria-current="page">Automated Warning Integration</li>
                    </ol>
                </nav>
                <h1><i class="fas fa-robot" style="color: var(--primary-color-1); margin-right: 0.5rem;"></i> Automated Warning Integration</h1>
                <p>Integrate with external warning feeds from PAGASA (weather) and PHIVOLCS (earthquake) for automated alert distribution.</p>
                <div class="info-box">
                    <i class="fas fa-info-circle"></i>
                    <div>
                        <strong>How to use:</strong> Monitor the integration status for PAGASA (weather) and PHIVOLCS (earthquake). Configure AI-powered warnings by entering your Gemini 2.5 API key and selecting the disaster types you want to monitor. Warnings will automatically sync and can be sent to subscribers.
                    </div>
                </div>
            </div>
            
            <div class="sub-container">
                <div class="page-content">
                    <!-- Integration Status -->
                    <div class="module-card">
                        <div class="module-card-header">
                            <h2><i class="fas fa-plug"></i> Integration Status</h2>
                        </div>
                        <div class="status-grid">
                            <div class="channel-card" id="pagasaCard">
                                <div class="channel-header">
                                    <div class="channel-title">
                                        <i class="fas fa-cloud-sun" style="color: #3498db; font-size: 1.5rem;"></i>
                                        <h3>PAGASA</h3>
                                    </div>
                                    <span class="badge" id="pagasaStatus">Connecting...</span>
                                </div>
                                <p class="channel-desc">Philippine Atmospheric, Geophysical and Astronomical Services Administration</p>
                                <div class="channel-footer">
                                    <small>Status: <span id="pagasaStatusText">Checking...</span></small>
                                </div>
                            </div>
                            <div class="channel-card" id="phivolcsCard">
                                <div class="channel-header">
                                    <div class="channel-title">
                                        <i class="fas fa-mountain" style="color: #e74c3c; font-size: 1.5rem;"></i>
                                        <h3>PHIVOLCS</h3>
                                    </div>
                                    <span class="badge" id="phivolcsStatus">Connecting...</span>
                                </div>
                                <p class="channel-desc">Philippine Institute of Volcanology and Seismology</p>
                                <div class="channel-footer">
                                    <small>Status: <span id="phivolcsStatusText">Checking...</span></small>
                                </div>
                            </div>
                            <div class="channel-card" id="geminiCard">
                                <div class="channel-header">
                                    <div class="channel-title">
                                        <i class="fas fa-robot" style="color: #9b59b6; font-size: 1.5rem;"></i>
                                        <h3>AI Powered</h3>
                                    </div>
                                    <span class="badge" id="geminiStatus">Checking...</span>
                                </div>
                                <p class="channel-desc">Google Gemini 2.5 AI Integration for Advanced Warning Analysis</p>
                                <div class="channel-footer">
                                    <small>Status: <span id="geminiStatusText">API Key Required</span></small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Settings Cards -->
                    <div class="module-card">
                        <div class="module-card-header">
                            <h2><i class="fas fa-cog"></i> Settings</h2>
                        </div>
                        <div class="settings-grid">
                            <div class="settings-card" onclick="openIntegrationSettingsModal()">
                                <div class="settings-icon"><i class="fas fa-sync-alt"></i></div>
                                <div class="settings-info">
                                    <h3>Integration Settings</h3>
                                    <p>Configure sync intervals and auto-publish</p>
                                </div>
                                <i class="fas fa-chevron-right" style="margin-left: auto; opacity: 0.3;"></i>
                            </div>
                            <div class="settings-card" onclick="openAISettingsModal()">
                                <div class="settings-icon"><i class="fas fa-robot"></i></div>
                                <div class="settings-info">
                                    <h3>AI Warning Settings</h3>
                                    <p>Configure Gemini API and disaster types</p>
                                </div>
                                <i class="fas fa-chevron-right" style="margin-left: auto; opacity: 0.3;"></i>
                            </div>
                            <a class="settings-card" href="automated-warnings-analytics.php" style="text-decoration: none; color: inherit;">
                                <div class="settings-icon"><i class="fas fa-chart-pie"></i></div>
                                <div class="settings-info">
                                    <h3>Warning Analytics</h3>
                                    <p>View weather and earthquake trends</p>
                                </div>
                                <i class="fas fa-chevron-right" style="margin-left: auto; opacity: 0.3;"></i>
                            </a>
                        </div>
                    </div>

                    <div class="module-card">
                        <div class="module-card-header">
                            <h2><i class="fas fa-vial"></i> System Testing</h2>
                        </div>
                        <div style="padding: 1.5rem; display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1rem;">
                            <button type="button" class="btn btn-warning" onclick="sendMockCriticalAlert('weather')" style="padding: 0.9rem 1rem; background: #f59e0b; border-color: #f59e0b; color: #fff;">
                                <i class="fas fa-cloud-showers-heavy"></i> Mock Critical Weather
                            </button>
                            <button type="button" class="btn btn-danger" onclick="sendMockCriticalAlert('earthquake')" style="padding: 0.9rem 1rem; background: #dc2626; border-color: #dc2626; color: #fff;">
                                <i class="fas fa-house-crack"></i> Mock Critical Earthquake
                            </button>
                        </div>
                    </div>

                    <!-- AI Disaster Monitoring Analysis Card -->
                    <div class="module-card">
                        <div class="module-card-header">
                            <h2><i class="fas fa-shield-alt"></i> Disaster Monitoring Analysis</h2>
                        </div>
                        <div id="aiWeatherAnalysisCard" class="ai-analysis-card">
                            <div class="ai-header">
                                <div class="ai-title">
                                    <i class="fas fa-robot"></i>
                                    <h3>AI Insights Engine</h3>
                                </div>
                                <span id="analysisStatusBadge" class="badge" style="background: rgba(255,255,255,0.2);">Loading...</span>
                            </div>
                            
                            <div id="analysisContent" class="ai-content">
                                <div style="text-align: center; padding: 2rem; opacity: 0.8;">
                                    <i class="fas fa-spinner fa-spin" style="font-size: 2rem; margin-bottom: 1rem;"></i>
                                    <p>Analyzing current conditions...</p>
                                </div>
                            </div>
                            
                            <div class="ai-footer">
                                <button onclick="refreshWeatherAnalysis()" class="btn btn-primary" style="width: 100%; background: rgba(255,255,255,0.2); border: 1px solid rgba(255,255,255,0.3); font-weight: 600;">
                                    <i class="fas fa-sync-alt"></i> Refresh AI Analysis
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Warnings -->
                    <div class="module-card">
                        <div class="module-card-header">
                            <h2><i class="fas fa-exclamation-triangle"></i> Recent Automated Warnings</h2>
                        </div>
                        <div style="padding: 0 1.5rem 1.5rem 1.5rem;">
                            <div style="overflow-x: auto;">
                                <table class="data-table" id="warningsTable">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Source</th>
                                            <th>Type</th>
                                            <th>Title</th>
                                            <th>Severity</th>
                                            <th>Status</th>
                                            <th>Received At</th>
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
    </div>

    <!-- Integration Settings Modal -->
    <div id="integrationSettingsModal" class="settings-modal" style="display: none;">
        <div class="modal-overlay" onclick="closeIntegrationSettingsModal()"></div>
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-cog"></i> Integration Settings</h2>
                <button class="modal-close" onclick="closeIntegrationSettingsModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form id="integrationSettingsForm">
                    <div class="form-group">
                        <label for="syncInterval">Sync Interval (minutes)</label>
                        <input type="number" id="syncInterval" name="sync_interval" value="15" min="1" max="60">
                        <small>Frequency of checks for new external warnings</small>
                    </div>
                    <div class="form-group">
                        <label for="autoPublish">Auto-Publish Warnings</label>
                        <label class="switch">
                            <input type="checkbox" id="autoPublish" name="auto_publish">
                            <span class="slider"></span>
                        </label>
                        <small>Automatically send warnings without manual approval</small>
                    </div>
                    <div class="form-group">
                        <label>Notification Channels</label>
                        <div style="display: flex; gap: 1rem; margin-top: 0.5rem; background: var(--bg-color-1); padding: 1rem; border-radius: 8px; border: 1px solid var(--border-color-1);">
                            <label style="display: flex; align-items: center; gap: 0.5rem;"><input type="checkbox" name="channels[]" value="sms" checked> SMS</label>
                            <label style="display: flex; align-items: center; gap: 0.5rem;"><input type="checkbox" name="channels[]" value="email" checked> Email</label>
                            <label style="display: flex; align-items: center; gap: 0.5rem;"><input type="checkbox" name="channels[]" value="pa"> PA System</label>
                        </div>
                    </div>
                    <div class="form-actions" style="margin-top: 2rem; display: flex; gap: 1rem;">
                        <button type="button" class="btn btn-secondary" onclick="closeIntegrationSettingsModal()" style="flex: 1;">Cancel</button>
                        <button type="submit" class="btn btn-primary" style="flex: 2;">Save Settings</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- AI Warning Settings Modal -->
    <div id="aiSettingsModal" class="settings-modal" style="display: none;">
        <div class="modal-overlay" onclick="closeAISettingsModal()"></div>
        <div class="modal-content modal-content-large">
            <div class="modal-header">
                <h2><i class="fas fa-robot"></i> AI Auto Warning System</h2>
                <button class="modal-close" onclick="closeAISettingsModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form id="aiWarningSettingsForm">
                    <div class="form-group">
                        <label for="geminiApiKey">Gemini 2.5 API Key</label>
                        <input type="password" id="geminiApiKey" name="gemini_api_key" placeholder="Enter API key">
                        <small>Required for AI-powered warning analysis</small>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                        <div class="form-group">
                            <label for="aiEnabled">Enable AI Analysis</label>
                            <label class="switch">
                                <input type="checkbox" id="aiEnabled" name="ai_enabled">
                                <span class="slider"></span>
                            </label>
                        </div>
                        
                        <div class="form-group">
                            <label for="aiCheckInterval">Analysis Interval (min)</label>
                            <input type="number" id="aiCheckInterval" name="ai_check_interval" value="30" min="5" max="120">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Danger Thresholds</label>
                        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; background: var(--bg-color-1); padding: 1rem; border-radius: 8px;">
                            <div>
                                <label style="font-size: 0.8rem;">Wind (km/h)</label>
                                <input type="number" id="windThreshold" name="wind_threshold" value="60">
                            </div>
                            <div>
                                <label style="font-size: 0.8rem;">Rain (mm/h)</label>
                                <input type="number" id="rainThreshold" name="rain_threshold" value="20">
                            </div>
                            <div>
                                <label style="font-size: 0.8rem;">Earthquake (Mag)</label>
                                <input type="number" id="earthquakeThreshold" name="earthquake_threshold" value="5.0" step="0.1">
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Disaster Monitor Types</label>
                        <div class="checkbox-group">
                            <label style="display: flex; align-items: center; gap: 0.5rem;"><input type="checkbox" name="warning_types[]" value="heavy_rain" checked> Heavy Rain</label>
                            <label style="display: flex; align-items: center; gap: 0.5rem;"><input type="checkbox" name="warning_types[]" value="flooding" checked> Flooding</label>
                            <label style="display: flex; align-items: center; gap: 0.5rem;"><input type="checkbox" name="warning_types[]" value="earthquake" checked> Earthquake</label>
                            <label style="display: flex; align-items: center; gap: 0.5rem;"><input type="checkbox" name="warning_types[]" value="strong_winds" checked> Strong Winds</label>
                            <label style="display: flex; align-items: center; gap: 0.5rem;"><input type="checkbox" name="warning_types[]" value="typhoon" checked> Typhoon</label>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Monitored Areas</label>
                        <textarea id="monitoredAreas" name="monitored_areas" rows="3" placeholder="List areas (one per line)"></textarea>
                    </div>
                    
                    <div class="form-actions" style="margin-top: 2rem; display: flex; gap: 1rem;">
                        <button type="button" class="btn btn-secondary" onclick="closeAISettingsModal()" style="flex: 1;">Cancel</button>
                        <button type="submit" class="btn btn-primary" style="flex: 2;">Save AI Settings</button>
                        <button type="button" class="btn btn-info" onclick="checkAIWarnings(event)" style="flex: 1;">Check Now</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function loadIntegrationStatus() {
            fetch('../api/automated-warnings.php?action=status')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        console.log('Integration Status:', data); // Debug log
                        // PAGASA Status
                        if (data.pagasa) {
                            const isConnected = data.pagasa.enabled;
                            document.getElementById('pagasaStatus').textContent = isConnected ? 'CONNECTED' : 'DISCONNECTED';
                            document.getElementById('pagasaStatus').className = 'badge ' + (isConnected ? 'success' : 'secondary');
                            document.getElementById('pagasaStatusText').textContent = isConnected ? 'Active and Syncing' : 'Not Connected';
                        } else {
                            document.getElementById('pagasaStatus').textContent = 'DISCONNECTED';
                            document.getElementById('pagasaStatus').className = 'badge secondary';
                            document.getElementById('pagasaStatusText').textContent = 'Not Configured';
                        }
                        
                        // PHIVOLCS Status
                        if (data.phivolcs) {
                            const isConnected = data.phivolcs.enabled;
                            document.getElementById('phivolcsStatus').textContent = isConnected ? 'CONNECTED' : 'DISCONNECTED';
                            document.getElementById('phivolcsStatus').className = 'badge ' + (isConnected ? 'success' : 'secondary');
                            document.getElementById('phivolcsStatusText').textContent = isConnected ? 'Active and Syncing' : 'Not Connected';
                        } else {
                            document.getElementById('phivolcsStatus').textContent = 'DISCONNECTED';
                            document.getElementById('phivolcsStatus').className = 'badge secondary';
                            document.getElementById('phivolcsStatusText').textContent = 'Not Configured';
                        }
                        
                        // Gemini Status
                        if (data.gemini) {
                            const isConnected = data.gemini.enabled && data.gemini.api_key_set;
                            const statusBadge = document.getElementById('geminiStatus');
                            const statusText = document.getElementById('geminiStatusText');
                            
                            if (isConnected) {
                                statusBadge.textContent = 'CONNECTED';
                                statusBadge.className = 'badge success';
                                statusText.textContent = data.gemini.status_message || 'AI Active and Monitoring';
                            } else if (data.gemini.api_key_set) {
                                statusBadge.textContent = 'READY';
                                statusBadge.className = 'badge warning';
                                statusText.textContent = data.gemini.status_message || 'API Key Set - Enable AI';
                            } else {
                                statusBadge.textContent = 'DISCONNECTED';
                                statusBadge.className = 'badge secondary';
                                statusText.textContent = data.gemini.status_message || 'API Key Required';
                            }
                        } else {
                            document.getElementById('geminiStatus').textContent = 'DISCONNECTED';
                            document.getElementById('geminiStatus').className = 'badge secondary';
                            document.getElementById('geminiStatusText').textContent = 'API Key Required';
                        }
                    }
                })
                .catch(error => {
                    console.error('Error loading integration status:', error);
                });
        }

        function loadWarnings() {
            if (!window.__warningsById) {
                window.__warningsById = new Map();
            }

            fetch('../api/automated-warnings.php?action=warnings')
                .then(response => response.json())
                .then(data => {
                    const tbody = document.querySelector('#warningsTable tbody');
                    tbody.innerHTML = '';
                    window.__warningsById.clear();
                    
                    if (data.success && data.warnings) {
                        data.warnings.forEach(warning => {
                            const warningId = Number(warning.id);
                            const warningStatus = String(warning.status || '').toLowerCase();
                            window.__warningsById.set(warningId, warning);

                            const publishButton = warningStatus === 'published'
                                ? `<button class="btn btn-sm btn-secondary" disabled title="Already published"><i class="fas fa-check"></i></button>`
                                : `<button class="btn btn-sm btn-success" onclick="publishWarning(${warningId})" title="Publish warning"><i class="fas fa-paper-plane"></i></button>`;

                            const row = document.createElement('tr');
                            row.innerHTML = `
                                <td>${warningId}</td>
                                <td><span class="badge" style="background: rgba(58, 118, 117, 0.1); color: var(--primary-color-1); font-weight: 700;">${warning.source.toUpperCase()}</span></td>
                                <td>${warning.type}</td>
                                <td><strong>${warning.title}</strong></td>
                                <td><span class="badge ${warning.severity.toLowerCase()}">${warning.severity}</span></td>
                                <td><span class="badge ${warning.status.toLowerCase()}">${warning.status}</span></td>
                                <td><small>${warning.received_at}</small></td>
                                <td>
                                    <button class="btn btn-sm btn-primary" onclick="viewWarning(${warningId})" title="View warning">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    ${publishButton}
                                </td>
                            `;
                            tbody.appendChild(row);
                        });
                    }
                });
        }

        function viewWarning(id) {
            const warning = window.__warningsById ? window.__warningsById.get(Number(id)) : null;
            if (!warning) {
                alert('Warning details unavailable.');
                return;
            }

            const details = [
                `ID: ${warning.id}`,
                `Source: ${warning.source || 'N/A'}`,
                `Type: ${warning.type || 'N/A'}`,
                `Severity: ${warning.severity || 'N/A'}`,
                `Status: ${warning.status || 'N/A'}`,
                `Received: ${warning.received_at || 'N/A'}`,
                '',
                `Title: ${warning.title || ''}`,
                '',
                `Content: ${warning.content || 'No content available.'}`
            ].join('\n');

            alert(details);
        }

        function publishWarning(id) {
            const warning = window.__warningsById ? window.__warningsById.get(Number(id)) : null;
            if (warning && String(warning.status || '').toLowerCase() === 'published') {
                alert('This warning is already published.');
                return;
            }

            if (!confirm('Publish this warning now?')) {
                return;
            }

            fetch('../api/automated-warnings.php?action=publish', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ id: Number(id) })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message || 'Warning published successfully.');
                    loadWarnings();
                } else {
                    alert('Error: ' + (data.message || 'Failed to publish warning.'));
                }
            })
            .catch(error => {
                console.error('Error publishing warning:', error);
                alert('Error publishing warning: ' + error.message);
            });
        }

        function sendMockCriticalAlert(type) {
            const pretty = type === 'earthquake' ? 'Earthquake' : 'Weather';
            if (!confirm(`Send MOCK CRITICAL ${pretty.toUpperCase()} alert and queue broadcast to all active citizens?`)) {
                return;
            }

            fetch('../api/automated-warnings.php?action=mock_alert', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                credentials: 'same-origin',
                body: JSON.stringify({
                    action: 'mock_alert',
                    type: type
                })
            })
            .then(async response => {
                const raw = await response.text();
                let data = null;
                try {
                    data = raw ? JSON.parse(raw) : {};
                } catch (parseErr) {
                    throw new Error(`HTTP ${response.status}: ${raw.slice(0, 180) || 'Invalid JSON response'}`);
                }
                if (!response.ok || !data.success) {
                    throw new Error(data.message || `HTTP ${response.status}`);
                }
                return data;
            })
            .then(data => {
                let noticeMessage = data.message || 'Mock alert queued successfully.';
                if (data.degraded && data.degraded_reason) {
                    noticeMessage += `\nReason: ${data.degraded_reason}`;
                }
                alert(noticeMessage);
                loadWarnings();
            })
            .catch(error => {
                console.error('Mock alert error:', error);
                alert('Mock alert failed: ' + error.message);
            });
        }

        // Modal Functions
        function openIntegrationSettingsModal() {
            document.getElementById('integrationSettingsModal').style.display = 'flex';
            document.body.style.overflow = 'hidden';
            loadIntegrationSettings();
        }

        function closeIntegrationSettingsModal() {
            document.getElementById('integrationSettingsModal').style.display = 'none';
            document.body.style.overflow = '';
        }

        function openAISettingsModal() {
            document.getElementById('aiSettingsModal').style.display = 'flex';
            document.body.style.overflow = 'hidden';
            loadAISettings();
        }

        function closeAISettingsModal() {
            document.getElementById('aiSettingsModal').style.display = 'none';
            document.body.style.overflow = '';
        }

        // Close modals on Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeIntegrationSettingsModal();
                closeAISettingsModal();
            }
        });

        function loadIntegrationSettings() {
            fetch('../api/automated-warnings.php?action=getSettings')
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.settings) {
                        const settings = data.settings;
                        document.getElementById('syncInterval').value = settings.sync_interval || 15;
                        document.getElementById('autoPublish').checked = settings.auto_publish || false;
                        
                        // Set notification channels
                        if (settings.notification_channels) {
                            const channels = settings.notification_channels.split(',');
                            document.querySelectorAll('input[name="channels[]"]').forEach(checkbox => {
                                checkbox.checked = channels.includes(checkbox.value);
                            });
                        }
                    }
                })
                .catch(error => {
                    console.error('Error loading integration settings:', error);
                });
        }

        document.getElementById('integrationSettingsForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            
            fetch('../api/automated-warnings.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Settings saved successfully!');
                    closeIntegrationSettingsModal();
                } else {
                    alert('Error: ' + data.message);
                }
            });
        });

        // AI Warning Settings Form Handler
        document.getElementById('aiWarningSettingsForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            
            // Check if API key is masked (contains asterisks), if so, don't send it
            const apiKeyInput = document.getElementById('geminiApiKey');
            const apiKeyValue = apiKeyInput.value;
            if (apiKeyValue.includes('*') || apiKeyValue.length <= 4) {
                // API key is masked, remove it from form data
                formData.delete('gemini_api_key');
            }
            
            fetch('../api/ai-warnings.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    return response.text().then(text => {
                        try {
                            const json = JSON.parse(text);
                            throw new Error(json.message || `HTTP ${response.status}: ${response.statusText}`);
                        } catch (e) {
                            if (e instanceof Error && e.message.includes('HTTP')) {
                                throw e;
                            }
                            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                        }
                    });
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    alert('AI Warning Settings saved successfully!');
                    loadAISettings();
                    // Reload integration status to update Gemini status
                    setTimeout(() => {
                        loadIntegrationStatus();
                    }, 500);
                    closeAISettingsModal();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error saving AI settings:', error);
                alert('Error saving settings: ' + error.message);
            });
        });

        // Load AI Settings
        function loadAISettings() {
            fetch('../api/ai-warnings.php?action=getSettings')
                .then(response => {
                    if (!response.ok) {
                        // If response is not ok, try to get error message
                        return response.text().then(text => {
                            // Handle empty response
                            if (!text || text.trim() === '') {
                                throw new Error(`HTTP ${response.status}: ${response.statusText} - Empty response`);
                            }
                            try {
                                const json = JSON.parse(text);
                                throw new Error(json.message || `HTTP ${response.status}: ${response.statusText}`);
                            } catch (e) {
                                if (e instanceof Error && (e.message.includes('HTTP') || e.message.includes('Empty'))) {
                                    throw e;
                                }
                                // If it's not JSON, return the text as error message
                                throw new Error(`HTTP ${response.status}: ${response.statusText} - ${text.substring(0, 100)}`);
                            }
                        });
                    }
                    // Check if response has content before parsing
                    const contentType = response.headers.get('content-type');
                    if (!contentType || !contentType.includes('application/json')) {
                        return response.text().then(text => {
                            throw new Error(`Invalid response format. Expected JSON but got: ${contentType}`);
                        });
                    }
                    return response.text().then(text => {
                        if (!text || text.trim() === '') {
                            throw new Error('Empty response from server');
                        }
                        try {
                            return JSON.parse(text);
                        } catch (e) {
                            throw new Error(`Invalid JSON response: ${text.substring(0, 100)}`);
                        }
                    });
                })
                .then(data => {
                    if (data.success && data.settings) {
                        const settings = data.settings;
                        // Only set API key if it's not masked (contains asterisks)
                        const apiKeyInput = document.getElementById('geminiApiKey');
                        if (apiKeyInput) {
                            if (settings.gemini_api_key && !settings.gemini_api_key.includes('*')) {
                                apiKeyInput.value = settings.gemini_api_key;
                            } else if (settings.gemini_api_key) {
                                // Keep masked value or empty
                                apiKeyInput.value = settings.gemini_api_key;
                            } else {
                                apiKeyInput.value = '';
                            }
                        }
                        const aiEnabled = document.getElementById('aiEnabled');
                        if (aiEnabled) aiEnabled.checked = settings.ai_enabled || false;
                        const aiCheckInterval = document.getElementById('aiCheckInterval');
                        if (aiCheckInterval) aiCheckInterval.value = settings.ai_check_interval || 30;
                        const windThreshold = document.getElementById('windThreshold');
                        if (windThreshold) windThreshold.value = settings.wind_threshold || 60;
                        const rainThreshold = document.getElementById('rainThreshold');
                        if (rainThreshold) rainThreshold.value = settings.rain_threshold || 20;
                        const earthquakeThreshold = document.getElementById('earthquakeThreshold');
                        if (earthquakeThreshold) earthquakeThreshold.value = settings.earthquake_threshold || 5.0;
                        const monitoredAreas = document.getElementById('monitoredAreas');
                        if (monitoredAreas) monitoredAreas.value = settings.monitored_areas || '';
                        
                        // Set warning types
                        if (settings.warning_types) {
                            const types = settings.warning_types.split(',');
                            document.querySelectorAll('input[name="warning_types[]"]').forEach(checkbox => {
                                checkbox.checked = types.includes(checkbox.value);
                            });
                        }
                        
                        // Set channels
                        if (settings.ai_channels) {
                            const channels = settings.ai_channels.split(',');
                            document.querySelectorAll('input[name="ai_channels[]"]').forEach(checkbox => {
                                checkbox.checked = channels.includes(checkbox.value);
                            });
                        }
                        
                        // Set weather analysis settings
                        const weatherAnalysisAutoSend = document.getElementById('weatherAnalysisAutoSend');
                        if (weatherAnalysisAutoSend) weatherAnalysisAutoSend.checked = settings.weather_analysis_auto_send || false;
                        const weatherAnalysisInterval = document.getElementById('weatherAnalysisInterval');
                        if (weatherAnalysisInterval) weatherAnalysisInterval.value = settings.weather_analysis_interval || 60;
                        const weatherAnalysisVerificationKey = document.getElementById('weatherAnalysisVerificationKey');
                        if (weatherAnalysisVerificationKey) weatherAnalysisVerificationKey.value = settings.weather_analysis_verification_key || '';
                    }
                })
                .catch(error => {
                    console.error('Error loading AI settings:', error);
                    // Don't show alert on page load, just log the error
                    // The error is likely due to missing database table or API key, which is expected on first use
                });
        }

        // Test AI Warning
        function testAIWarning() {
            if (confirm('This will send a test AI warning notification. Continue?')) {
                fetch('../api/ai-warnings.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        action: 'test'
                    })
                })
                .then(response => {
                    if (!response.ok) {
                        return response.text().then(text => {
                            try {
                                const json = JSON.parse(text);
                                throw new Error(json.message || `HTTP ${response.status}: ${response.statusText}`);
                            } catch (e) {
                                if (e instanceof Error && e.message.includes('HTTP')) {
                                    throw e;
                                }
                                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                            }
                        });
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        alert('Test warning sent successfully! Check mass notifications.');
                        loadWarnings(); // Refresh warnings table
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error sending test warning:', error);
                    alert('Error sending test warning: ' + error.message);
                });
            }
        }

        // Check AI Warnings Now
        function checkAIWarnings() {
            const btn = event.target;
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Checking...';
            btn.disabled = true;
            
            fetch('../api/ai-warnings.php?action=check')
                .then(response => {
                    if (!response.ok) {
                        return response.text().then(text => {
                            // Handle empty response
                            if (!text || text.trim() === '') {
                                throw new Error(`HTTP ${response.status}: ${response.statusText} - Empty response`);
                            }
                            try {
                                const json = JSON.parse(text);
                                throw new Error(json.message || `HTTP ${response.status}: ${response.statusText}`);
                            } catch (e) {
                                if (e instanceof Error && (e.message.includes('HTTP') || e.message.includes('Empty'))) {
                                    throw e;
                                }
                                throw new Error(`HTTP ${response.status}: ${response.statusText} - ${text.substring(0, 100)}`);
                            }
                        });
                    }
                    // Check if response has content before parsing
                    const contentType = response.headers.get('content-type');
                    if (!contentType || !contentType.includes('application/json')) {
                        return response.text().then(text => {
                            throw new Error(`Invalid response format. Expected JSON but got: ${contentType}`);
                        });
                    }
                    return response.text().then(text => {
                        if (!text || text.trim() === '') {
                            throw new Error('Empty response from server');
                        }
                        try {
                            return JSON.parse(text);
                        } catch (e) {
                            throw new Error(`Invalid JSON response: ${text.substring(0, 100)}`);
                        }
                    });
                })
                .then(data => {
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                    
                    if (data.success) {
                        const count = data.warnings_generated || 0;
                        if (count > 0) {
                            alert(`AI Warning System checked conditions and generated ${count} warning(s). Check Recent Warnings table.`);
                        } else {
                            alert('AI Warning System checked conditions. No dangerous conditions detected at this time.');
                        }
                        loadWarnings(); // Refresh warnings table
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                    alert('Error checking warnings: ' + error.message);
                });
        }

        // Load AI Disaster Monitoring Analysis
        function loadWeatherAnalysis() {
            const contentDiv = document.getElementById('analysisContent');
            const statusBadge = document.getElementById('analysisStatusBadge');
            
            contentDiv.innerHTML = `
                <div style="text-align: center; padding: 2rem; color: rgba(255,255,255,0.8);">
                    <i class="fas fa-spinner fa-spin" style="font-size: 2rem; margin-bottom: 1rem;"></i>
                    <p>Loading disaster monitoring analysis...</p>
                </div>
            `;
            statusBadge.textContent = 'Loading...';
            statusBadge.className = 'badge';
            statusBadge.style.background = 'rgba(255,255,255,0.2)';
            
            fetch('../api/ai-warnings.php?action=getWeatherAnalysis')
                .then(response => {
                    // First, try to get response as text to check if it's valid JSON
                    return response.text().then(text => {
                        // Check if response is empty
                        if (!text || text.trim() === '') {
                            throw new Error(`HTTP ${response.status}: Empty response from server`);
                        }
                        
                        // Try to parse as JSON
                        try {
                            const data = JSON.parse(text);
                            if (!response.ok) {
                                throw new Error(data.message || `HTTP ${response.status}`);
                            }
                            return data;
                        } catch (parseError) {
                            // If not JSON, it's an error
                            if (!response.ok) {
                                throw new Error(`HTTP ${response.status}: ${text.substring(0, 200)}`);
                            }
                            throw new Error('Invalid response format: ' + text.substring(0, 100));
                        }
                    });
                })
                .then(data => {
                    if (data.success && data.analysis) {
                        displayWeatherAnalysis(data);
                        statusBadge.textContent = 'Complete';
                        statusBadge.className = 'badge success';
                        statusBadge.style.background = 'rgba(76, 175, 80, 0.3)';
                    } else {
                        throw new Error(data.message || 'Failed to load analysis');
                    }
                })
                .catch(error => {
                    console.error('Error loading disaster monitoring analysis:', error);
                    const errorMsg = error.message || 'Failed to load disaster monitoring analysis';
                    contentDiv.innerHTML = `
                        <div style="text-align: center; padding: 2rem; color: rgba(255,255,255,0.9);">
                            <i class="fas fa-exclamation-triangle" style="font-size: 2rem; margin-bottom: 1rem; color: #ffeb3b;"></i>
                            <p style="margin: 0.5rem 0; font-weight: 500;">${errorMsg}</p>
                            <small style="opacity: 0.8; display: block; margin-top: 0.5rem;">Please check your API key configuration in AI Warning Settings</small>
                        </div>
                    `;
                    statusBadge.textContent = 'Error';
                    statusBadge.className = 'badge';
                    statusBadge.style.background = 'rgba(244, 67, 54, 0.3)';
                });
        }

        function displayWeatherAnalysis(data) {
            const contentDiv = document.getElementById('analysisContent');
            const analysis = data.analysis;
            const location = data.location || 'Quezon City';
            
            let html = '';
            
            // Summary Section
            html += `
                <div style="margin-bottom: 2rem;">
                    <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 1rem; color: rgba(255,255,255,0.9);">
                        <i class="fas fa-cloud-sun" style="color: #e1bee7;"></i>
                        <h4 style="margin: 0; font-size: 1rem; font-weight: 600;">Summary</h4>
                    </div>
                    <p style="margin: 0; color: rgba(255,255,255,0.95); line-height: 1.6;">${analysis.summary || 'No summary available'}</p>
                </div>
            `;
            
            // Recommendations Section
            if (analysis.recommendations && analysis.recommendations.length > 0) {
                html += `
                    <div style="margin-bottom: 2rem;">
                        <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 1rem; color: rgba(255,255,255,0.9);">
                            <i class="fas fa-list-ul" style="color: #e1bee7;"></i>
                            <h4 style="margin: 0; font-size: 1rem; font-weight: 600;">Recommendations</h4>
                        </div>
                        <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                `;
                
                analysis.recommendations.forEach((rec, index) => {
                    html += `
                        <div style="display: flex; align-items: start; gap: 0.75rem;">
                            <i class="fas fa-check-circle" style="color: #c8e6c9; margin-top: 0.25rem; flex-shrink: 0;"></i>
                            <span style="color: rgba(255,255,255,0.95); line-height: 1.6;">${index + 1}. ${rec}</span>
                        </div>
                    `;
                });
                
                html += `
                        </div>
                    </div>
                `;
            }
            
            // Risk Assessment Section
            if (analysis.risk_assessment) {
                const riskLevel = analysis.risk_assessment.level || 'MEDIUM';
                const riskDesc = analysis.risk_assessment.description || 'No risk assessment available';
                let riskColor = '#ff9800'; // Orange for MEDIUM
                if (riskLevel === 'LOW') riskColor = '#4caf50'; // Green
                if (riskLevel === 'HIGH') riskColor = '#f44336'; // Red
                
                html += `
                    <div>
                        <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 1rem; color: rgba(255,255,255,0.9);">
                            <i class="fas fa-shield-alt" style="color: #e1bee7;"></i>
                            <h4 style="margin: 0; font-size: 1rem; font-weight: 600;">Risk Assessment</h4>
                        </div>
                        <div style="background: rgba(0,0,0,0.2); padding: 1rem; border-radius: 8px; border-left: 4px solid ${riskColor};">
                            <p style="margin: 0; color: ${riskColor}; font-weight: 600; margin-bottom: 0.5rem;">${riskLevel}</p>
                            <p style="margin: 0; color: rgba(255,255,255,0.95); line-height: 1.6; font-size: 0.9rem;">${riskDesc}</p>
                        </div>
                    </div>
                `;
            }
            
            // Location and timestamp
            html += `
                <div style="margin-top: 1.5rem; padding-top: 1rem; border-top: 1px solid rgba(255,255,255,0.2);">
                    <small style="color: rgba(255,255,255,0.7); display: flex; align-items: center; gap: 0.5rem;">
                        <i class="fas fa-map-marker-alt"></i>
                        <span>Location: ${location}</span>
                        ${data.timestamp ? `<span style="margin-left: 1rem;"><i class="fas fa-clock"></i> ${new Date(data.timestamp).toLocaleString()}</span>` : ''}
                    </small>
                </div>
            `;
            
            contentDiv.innerHTML = html;
        }

        function refreshWeatherAnalysis() {
            const btn = event.target.closest('button');
            const originalHTML = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Refreshing...';
            btn.disabled = true;
            
            loadWeatherAnalysis();
            
            setTimeout(() => {
                btn.innerHTML = originalHTML;
                btn.disabled = false;
            }, 2000);
        }

        // Load data on page load
        document.addEventListener('DOMContentLoaded', function() {
            loadIntegrationStatus();
            loadWarnings();
            loadAISettings();
            loadWeatherAnalysis();
        });

    </script>
</body>
</html>


